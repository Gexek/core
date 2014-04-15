<?php
namespace Data;

class GridConnector {
    public $request, $table, $order, $primary, $filter, $oldkeys,
    $updates, $grid, $open = null, $update = null, $delete = null;
    
    public $fields = null;
    public $ignoreOldKeys = false;
    public $manipulatedFields = array();
    public $sql = null;
    
    public function __construct(){
        $this->request  = $_POST->request;
        $this->table    = $_POST->table;
        $this->primary  = $_POST->primary;
        $this->order    = $_POST->order;
        $this->filter   = $_POST->filter;
        $this->updates  = $_POST->rows;
        $this->oldkeys  = $_POST->oldkeys;
        $this->grid     = unserialize(urldecode($_POST->grid));
        
        $this->primary = gx_force_var($this->primary, 'array');
        $this->order = empty($this->order)? null: $this->order;
        
        if(empty($this->filter))
            $this->filter = array();
        else if(is_string($this->filter))
            $this->filter = array($this->filter);
        else if(is_object($this->filter))
            $this->filter = get_object_vars($this->filter);
    }
    
    public function perform(){
        switch($this->request){
            case 'load': $this->_open(); break;
            case 'edit': $this->_update(); break;
            case 'delete': $this->_delete(); break;
        }
    }
    
    private function sendOutput($output){
        header("Content-type: text/xml; charset=utf-8");
        echo '<?xml version="1.0" encoding="UTF-8"?><root>'.$output.'</root>';
    }
    
    private function sqlValue($value){
        if(is_string($value))
            return $this->quote($value);
        else if(is_bool($value))
            return $value? 1: 0;
        else return $value;
    }
    
    private function _open(){
        if(empty($this->sql)) {
            global $DB;
            if(!is_null($this->fields)){
                $this->fields = is_array($this->fields)? $this->fields: array($this->fields);
                $fields = implode(',', $this->fields);
            } else $fields = '*';
            $sql = array('SELECT '.$fields.' FROM '.$this->table);
            
            $this->filter = (array)$this->filter;
            if(count($this->filter) > 0){
                $sql[] = 'WHERE';
                foreach($this->filter as $k => $v)
                    if(is_numeric($k)) $sql[] = $v;
                    else $sql[] = $k.' = '.$this->sqlValue($v);
            }
            
            if(!is_null($this->order))
                $sql[] = 'ORDER BY '.$this->order;
                
            $dataset = $DB->query(implode(' ', $sql))->fetchAll(\PDO::FETCH_OBJ);
        } else 
        	$dataset = $DB->query($this->sql)->fetchAll(\PDO::FETCH_OBJ);
        
        if(!is_null($this->open)){
			$open = $this->open;
			$dataset = $open($dataset);
		}
		
        $this->sendOutput($this->grid->encodeData($dataset));
    }
    
    private function _update(){
        $affected = 0; $inserts = array();
        if(!is_null($this->update)){
            $update = $this->update;
            $this->updates = $update($this->updates);
        }
        
        global $DB; 
        foreach($this->updates as $i => $row){
            $row = get_object_vars($row);
            $row = $this->grid->prepareUpdates($row);
            $oldkeys = $this->ignoreOldKeys? array(): get_object_vars($this->oldkeys[$i]);
            foreach ($oldkeys as $i => $k){
            	if(empty($k) || $this->ignoreOldKeys || in_array($i, $this->manipulatedFields)) 
            		unset($oldkeys[$i]);
            }
            
            foreach ($row as &$col)	if(is_array($col)) $col = implode(';', $col);
            
            $affected += (int)$DB->replace($this->table, $row, $oldkeys);
            
            $err_inf = $DB->errorInfo();
            if($err_inf[1] > 1)	throw new \Exception($err_inf[2], $err_inf[1]);
            
            $liid = $DB->lastInsertId();

            $keys = '';
            foreach($this->primary as $k){
            	if($liid) $nkv = $liid;
            	else $nkv = $row[$k];
                $keys .= '<key name="'.$k.'">'.$nkv.'</key>';
            }
            $updates[] = '<update>'.$keys.'</update>';
        }
        
        $this->sendOutput(
        		'<updates><count>'.$affected.'</count>'.implode('', $updates).'</updates>'.
        		'<message></message>'
        );
    }
    
    private function _delete(){
        $affected = 0;
        if(!is_null($this->delete)){
            $delete = $this->delete;
            $affected = $delete();
        } else {
            global $DB;
            foreach($this->updates as $row){
                $values = array();
                $sql = array('DELETE FROM '.$this->table);
                
                $sql[] = 'WHERE';
                if(count($this->primary) > 0){
                    foreach($this->primary as $key){
                        $sql[] = "$key = :$key";
                        $values[":$key"] = $row->$key;
                    }
                } else {
                    $ss = array();
                    foreach($this->grid->getColumns() as $col){
                        $key = $col->name;
                        if(isset($row->$key)){
                            $ss[] = "$key = :$key";
                            $values[":$key"] = $row->$key;
                        }
                    }
                    $sql[] = implode(' AND ', $ss);
                }
                
                $dbh = $DB->prepare(implode(' ', $sql));
                $affected += $dbh->execute($values);
            }
        }
        $this->sendOutput('<updates><count>'.$affected.'</count></updates>');
    }
}
?>
