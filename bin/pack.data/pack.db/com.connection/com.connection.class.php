<?php
namespace Data\DB;

class Connection extends \PDO {
    public $prefix, $dbname, $error_info = null, $debugSQL = false;
    
    private function multirow($array){
        if(isset($array[0])) return is_array($array[0]);
        else return false;
    }
    
    private function prepareSQL($sql){
        $s = $sql;
        $sql = str_ireplace('`', '', $sql);
        $sql = str_ireplace(' = ', '=', $sql);
        preg_match_all('/((?:^select .+?(?:from|into))|^update|^table|join) (`?\w+`?)\s/i', $sql, $tables);
        $prefix = empty($this->prefix)? '': "|$this->prefix";
        foreach($tables[0] as $i=>$s){
            $t = "$this->dbname.$prefix".preg_replace("/($this->dbname\.$prefix)/", '', $tables[2][$i]);
            $cm = $tables[1][$i];
            $sql = str_ireplace($s, "$cm $t ", $sql);
        }

        global $Engine;
        if($Engine->started && $Engine->driver == 'html'){
            $options = $Engine->getOP(); $that = $this;
            $sql = preg_replace_callback('/\{viewfilter:([^\}]+)\}/i', function($m) use($that, $options){
            	return $that->encodeViewFilters($m[1], $options);
           	}, $sql);
        }
        
        if($this->debugSQL) debug($sql);
        return $sql;
    }
    
    private function mustCheck($opt, $params, $var){
        return isset($opt[$var]) && (in_array($var, $params) || count($params) == 0);
    }
    
    private function createRX($field, $name, $value, $value_type = 'array'){
    	$bs = '(\\\\\\\\)?';
    	$lsb = '[[.left-square-bracket.]]';
    	$rsb = '[[.right-square-bracket.]]';
    	
    	if($value_type == 'object')
    		return "$field REGEXP '$bs\"$name$bs\":{(.*)($bs\"$value$bs\":{)(.*)}'";
    	else if($value_type == 'boolean')
    		return "($field REGEXP '$bs\"$name$bs\":$bs\"?".($value? '1': '0')."$bs\"?'".
            	" OR $field REGEXP '$bs\"$name$bs\":$bs\"?".($value? 'true': 'false')."$bs\"?')";
    	else
	    	return "($field REGEXP '$bs\"$name$bs\":$lsb($bs\"every$bs\")(.*)?$rsb'".
				" OR $field REGEXP '$bs\"$name$bs\":$lsb(.*)($bs\"".str_replace('/', "$bs/", $value)."$bs\")(.*)$rsb')";
    }
    
    public function encodeViewFilters($vars, $output_opt){
        preg_match('/^(\w+)(\[([a-z,\s]+)\])?/i', $vars, $match);
        $field = $match[1]; $params = array();
        if(isset($match[3])) $params = explode(',', $match[3]);
            
        $output_opt = gx_force_var($output_opt, 'array');
        
        $op_opt = array();

        $op_opt[] = $this->createRX($field, 'themes', $output_opt['theme'], 'object');
        
        $op_opt[] = $this->createRX($field, 'locales', $output_opt['locale']);
		
        if(!$output_opt['homepage'])
        	$op_opt[] = $this->createRX($field, 'pages', $output_opt['page']);
        
        if($output_opt['homepage'])
            $op_opt[] = $this->createRX($field, 'homepage', true, 'boolean');
        //die(implode(' AND ', $op_opt));
        return implode(' AND ', $op_opt);
    }
    
    public function sqlValue($value){
        if(is_string($value))
            return $this->quote($value);
        else if(is_bool($value))
            return $value? 1: 0;
        else return $value;
    }
    
    public function query ($statement){
    	$this->error_info = null;
        return parent::query($this->prepareSQL($statement));
    }
    
    public function exec($statement){
        $statements = is_array($statement)? $statement: array($statement);
        if(count($statements) > 1){
            $this->beginTransaction();
            foreach($statements as $statement){
                if(parent::exec($this->prepareSQL($statement)) === false){
                    $this->rollBack();
                    $this->sendError($statement);
                    return false;
                }
            }
            $this->commit();
            return true;
        } else
            return parent::exec($this->prepareSQL($statements[0]));
    }
    
    public function select($table, $options = array()){
    	$this->error_info = null;
        extend($options, array(
            'fields' => '*', 'filter' => null, 
            'order' => null, 'limit' => null,
            'fetchType' => \PDO::FETCH_ASSOC
        ));
        $options['filter'] = is_null($options['filter'])? '': "WHERE $options[filter]";
        $options['order'] = is_null($options['order'])? '': "ORDER BY $options[order]";
        $options['limit'] = is_numeric($options['limit'])? $options['limit']: -1;
        $limitStr = $options['limit']<0? '': "LIMIT $options[limit]";
        
        $sql = $this->prepareSQL("SELECT $options[fields] FROM $table $options[filter] $options[order] $limitStr");
        
        if(($rs = $this->query($sql)) === false){
            $this->sendError($sql);
        } else {
            if($options['limit'] == 1 && $rs->rowCount() > 0)
                return $rs->fetch($options['fetchType']);
            else if($options['limit'] == 1 && $rs->rowCount() == 0)
                return false;
            else
                return $rs;
        }
    }
    
    public function sendError($sql = ''){
        $debug = $this->errorInfo();
        debug($debug[2], 'SQL #'.$debug[1].(empty($sql)? '': ' Error for "'.$sql.'"'));
    }
    
    public function replace($table, $values, $keyvalues = array()){
        global $DB;
        $sql = array('INSERT INTO '.$table); 
        $fields = array_keys($values);
        $sql[] = '('.implode(', ', $fields).')';
        $sql[] = 'VALUES(:ins_'.implode(', :ins_', $fields).')';
        $sql[] = 'ON DUPLICATE KEY UPDATE';
        
        $updates = '';
        foreach($fields as $f)
           $updates .= "$f = :upd_$f, ";
        $sql[] = trim(trim($updates), ',');
        
        $sql = implode(' ', $sql);
        $dbh = $this->prepare($sql);
        
        $exec = array();
        foreach($values as $field => $value){
        	$ins_val = isset($keyvalues[$field])? $keyvalues[$field]: $value;
            $exec[':ins_'.$field] = $ins_val;
            $exec[':upd_'.$field] = $value;
        }
        
        $result = $dbh->execute($exec);
        $this->error_info = $dbh->errorInfo();
        
        return $result;
    }
    
    public function errorInfo(){
    	if(!empty($this->error_info))
    		return $this->error_info;
    	else
    		return parent::errorInfo();
    }
    
    public function xmlEncode($dataset, $sendHeader = false){
        function parseXML($obj){
            $xml = ' inner="'.gettype($obj).'">';
            if(is_array($obj)){
                foreach($obj as $item)
                    $xml .= "<item".parseXML($item).'</item>';
            } else if(is_object($obj)){
                $vars = get_object_vars($obj);
                  foreach($vars as $index=>$value)
                      $xml .= "<$index".parseXML($value)."</$index>";
            } else 
                $xml .= htmlspecialchars($obj);
            return $xml;
        }
        
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><root>";
        
        if(is_object($dataset) && get_class($dataset) == 'PDOStatement')
            $dataset = $dataset->fetchAll(\PDO::FETCH_ASSOC);

        if(count($dataset) > 0){
            $i = 0;
            foreach($dataset as $row){
                $xml .= '<row num="'.(++$i).'">';
                foreach($row as $field => $value){
                    $xml .= "<$field";
                    if(\Utils\String::isJSON($value)){
                        $xml .= parseXML(json_decode($value));
                        $xml .= "</$field>";
                    } else if(is_object($value) || is_array($value)){
                        $xml .= parseXML($value);
                        $xml .= "</$field>";
                    } else
                        $xml .= parseXML($value)."</$field>";
                }
                $xml .= '</row>';
            }
        }
        if($sendHeader) header("Content-type: text/xml; charset=utf-8");
        return $xml."</root>";
    }
    
    public function makeTree($dataset, $key, $relation, $start){
        $array = array();
        if($dataset instanceof PDOStatement)
            $dataset = $dataset->fetchAll(\PDO::FETCH_ASSOC);
        foreach($dataset as $row){
            if($row[$relation] == $start){
                $array[$row[$key]] = new \stdClass();
                foreach($row as $field=>$value)
                        $array[$row[$key]]->$field = $value;
                $array[$row[$key]]->childs = $this->makeTree($dataset, $key, $relation, $row[$key]);
            }
        }
        return $array;
    }
    
    public function parseArray($array, $mode = 'insert', $field = ''){
        $mode = strtolower($mode);
        switch($mode){
            case 'insert': return '\'['.implode('][', $array).']\''; break;
            case 'select': return $field.' LIKE \'%['.implode(']%\' OR '.$field.' LIKE \'%[', $array).']%\''; break;
        }
    }
}
?>