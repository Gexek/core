<?php
namespace Graphic;

class GridColumn extends \ServerSide {
    private $name, $options;
    public function __construct($name, $options = array()){
        global $i18n;
        $this->name = $name;
        extend($options, array(
            'caption'           => null,
            //varchar, text, autonumber, integer, float, boolean, money, datetime, list, password, static
            'datatype'          => 'varchar',
            'align'             => 'auto',
            'dir'               => 'auto',
            'datepicker'        => null,
            'validation'        => array(
                'pattern'       => '',
                'patternmessage'=> $i18n->error_not_match_pattern,
                'minlength'     => 0,
                'maxlength'     => 0,
                'min'           => null,
                'max'           => null,
                'invalidValues' => ''
            ),
            'decimals'          => 2,
            // true: will convert to timestamp on update
            // false: keep as string date or time
            'timestamp'         => true,
            // default datetime format is 'Y F d'
            // default money format is '%s %n %c'
            // %s = sybmol      %n = number value       %c = currency
            'staticvalue'       => null,
            'format'            => null,
            'primary'           => false,
            'editabletime'      => false,
            'editablesecond'    => false,
            'listitems'         => array(),
            'multiple'          => false,
            'symbol'            => '',
            'icon'              => '',
            'url'               => '',
            'title'             => '',
            'width'             => null,
            'editable'          => true,
            'hidden'            => false,
            'allownull'         => true
        ));
        
        $this->options = $this->prepare($options);
        if(empty($this->options['format'])){
            if($this->options['datatype'] == 'datetime')
                $this->options['format'] = 'd F Y';
            else if($this->options['datatype'] == 'money')
                $this->options['format'] = '%s %n %c';
            else
                $this->options['format'] = '';
        }
        
        $this->clientCalls = array(
            'refreshButtons'  => $this->name.'.refreshButtons()'
        );
    }
    
    public function __get($var){
    	if(isset($this->options[$var]))
    		return $this->options[$var];
    	else if(isset($this->$var))
    		return $this->$var;
    	else 
    		return false;
    }
    
    public function __isset($var){
    	return isset($this->options[$var]) || isset($this->$var);
    }
    
    private function prepare($options){
        $options['datatype'] = strtolower($options['datatype']);
        
        switch($options['datatype']){
            case 'static':
                $options['editable'] = false;
                break;
            case 'boolean':
                if($options['width'] < 70) $options['width'] = 70;
                break;
        }

        if($options['primary'])
            $options['allownull'] = false;
        
        $dpopt = array();
        if($options['datepicker'] != null){
            $dpopt = $options['datepicker']->options;
            if(!is_null($dpopt['minDate']))
            $dpopt['minDate'] = '$.datepicker.Date('.$i18n->date('Y,m,d', $dpopt['minDate']).')';
            if(!is_null($dpopt['maxDate']))
            $dpopt['maxDate'] = '$.datepicker.Date('.$i18n->date('Y,m,d', $dpopt['maxDate']).')';
        }
        $options['datepicker'] = $dpopt;
        
        
        return $options;
    }
}

class Grid extends Graphic{
    private $hasAutonumber = false, $ajaxLoad, $cols;
    static public $depends = array(
        'Graphic\DatePicker'
    );
    /**
    * true = enable, false = disable
    * 1st boolean: when viewing
    * 2nd boolean: when selected
    * 3rd boolean: when editing
    */
    private $toolbarButtons = array(
        'refresh' => array('refresh', '[i18n:refresh]', '$grid.reload();',      true, true, false),
        'add'     => array('plus',    '[i18n:add]',     '$grid.addRow();',      true, false, false),
        'edit'    => array('pencil',  '[i18n:edit]',    '$grid.edit();',        false, true, false),
        'submit'  => array('disk',    '[i18n:submit]',  'return $grid.submit();',      false, false, true),
        'delete'  => array('trash',   '[i18n:delete]',  'if(confirm("[i18n:com_grid_confirm_delete]")) $grid.removeRows(); else return flase',  false, true, false),
        'cancel'  => array('close',   '[i18n:cancel]',  '$grid.cancel();',      false, false, true)
    );
    protected $defaultOptions = array('visuality' => 'border');
    
    public function __construct($name, $options = array()){
        $this->cols = array();
        extend($options, array(
            'width'             => '100%',
            'height'            => '422px',//'320px',
            'showrowno'         => true,   // Show columns number
            'caption'           => null,   // Grid caption
            'server'            => '',     // server file to load and update data
            'data'              => array(),// data to post to server
            'table'             => '',
            'order'             => '',
            'filter'            => '',
            'selectable'        => true,   // specifies that rows are selectable or not
            'multiselect'       => false,  // multirows could be seelct or not
            // specifies how icons show
            'buttons'           => 'refresh,add,edit,delete,submit,cancel',
            'insert'            => 'first',
            'custombuttons'     => array()
        ));
        
        $options['primary'] = array();
        
        parent::__construct($name, $options);
        foreach($this->options->custombuttons as $name => $button)
            $this->toolbarButtons[$name] = $button;
        unset($this->options->custombuttons);
        
        $this->ajax = new \Data\Ajax(array('url' => $this->options->server));
        if(empty($this->options->server))
            $this->ajax->setCaller($this);
        $this->ajax->arg('grid');
        $this->ajax->arg('request', true);
        $this->ajax->arg('rows', true);
        $this->ajax->arg('oldkeys', true);
        $this->ajax->arg('loadCallback');
        $this->ajax->arg('failCallback');
        $this->ajax->data($this->options->data);
        $this->ajax->data('request', 'load');
        $this->ajax->data('table', $this->options->table);
        $this->ajax->data('order', $this->options->order);
        $this->ajax->data('filter', $this->options->filter);
        $this->ajax->bind('onsuccess', 'loadCallback(response);');
        $this->ajax->bind('onfail', 'if(typeof failCallback != "undefined") failCallback(response);');
        
        $this->clientCalls = array(
            'reload' => $this->id.'.reload()',
            'submit' => $this->id.'.submit()',
            'cancel' => $this->id.'.cancel()'
        );
    }
    
    public function getColumns(){
        return $this->cols;
    }
    
    private function getOptions(){
        $opts = array('ajax: '.$this->ajax);
        $v = trim(fe9b868990cffe6fa7cf765df33ac3e6($this->options), '{');
        $v = trim($v, '}');
        $opts[] = $v;
        
        $buttons = 'toolbarButtons: {';
        foreach($this->toolbarButtons as $name => $button){
            $buttons .= '"'.$name.'": function($grid){'.str_replace('"', '\'', $button[2]).'},';
        }
        $buttons = rtrim($buttons, ',').'}';
        $opts[] = $buttons;
        
        // Columns
        $colopts = array();
        foreach($this->cols as $col){
            $format = $col->format;
            if($col->datatype == 'datetime')
                $format = $this->php2ui_dateformat($format);
                
            $min = $col->validation['min'];
            $max = $col->validation['max'];
            
            $colopts[] = '{'.
                '"name": "'.$col->name.'", '.
                '"primary": '.fe9b868990cffe6fa7cf765df33ac3e6($col->primary).', '.
                '"allownull": "'.$col->allownull.'", '.
                '"caption": "'.$col->caption.'", '.
                '"datatype": "'.$col->datatype.'", '.
                '"align": "'.$col->align.'", '.
                '"dir": "'.$col->dir.'", '.
                '"decimals": "'.$col->decimals.'", '.
                '"datepicker": '.(count($col->datepicker) == 0? 'null': 
                    fe9b868990cffe6fa7cf765df33ac3e6($col->datepicker)).','.
                '"listitems": '.json_encode($col->listitems).', '.
                '"multiple": "'.$col->multiple.'", '.
                '"icon": "'.$col->icon.'", '.
                '"symbol": "'.$col->symbol.'", '.
                '"editable": "'.$col->editable.'", '.
                '"url": "'.$col->url.'", '.
                '"title": "'.$col->title.'",'.
                '"width": "'.$col->width.'", '.
                '"format": "'.$format.'",'.
                '"hidden": "'.$col->hidden.'",'.
                '"validation": {'.
                    '"pattern": "'.$col->validation['pattern'].'",'.
                    '"patternmessage": "'.$col->validation['patternmessage'].'",'.
                    '"minlength": '.$col->validation['minlength'].','.
                    '"maxlength": '.$col->validation['maxlength'].','.
                    '"invalidValues": "'.$col->validation['invalidValues'].'",'.
                    '"min": '.(is_null($min)? 'null': $min).','.
                    '"max": '.(is_null($max)? 'null': $max).''.
                '},'.
                '"editabletime": "'.$col->editabletime.'",'.
                '"editablesecond": "'.$col->editablesecond.'"'.
            '}';
        }
        $cols = '"cols":['.implode(',', $colopts).']';
        $opts[] = $cols;
        return '{'.implode(', ', $opts).'}';
    }
    
    public function addColumn(GridColumn &$col){
        $this->cols[$col->name] = $col;
        if($col->datatype == 'autonumber')
            $this->hasAutonumber = true;
        if($col->primary)
        	$this->options->primary[] = $col->name;
    }

    public function encodeData($dataset, $total_rows = 0, $total_pages = 0){
        function xmlentities($string) {
            return str_replace(
                array("<", ">", "\"", "'", "&"),
                array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"),
                $string
            );
        }
        
        if(is_object($dataset) && get_class($dataset) == 'PDOStatement')
        	$dataset = $dataset->fetchAll(\PDO::FETCH_ASSOC);
        
        global $i18n;
        $xml = '<rowscount>'.count($dataset).'</rowscount>';
        
        if(count($dataset) > 0){
            foreach($dataset as $i=>$row){
            	$row = gx_force_var($row, 'array');
                $xml .= '<row num="'.(++$i).'">';
                foreach($this->cols as $col){
                	try { 
                		$value = $origin = $row[$col->name];
                    } catch (\Exception $e){
                    
                    }
                    $extra = '';
                    
                    switch($col->datatype){
                        case 'static':
                            $value = $origin = $col->staticvalue;
                            break;
                        
                        case 'datetime':
                            if(empty($value)){ $value = '-'; break; }
                            if(!is_numeric($value))
                                $value = $i18n->strtotime($value);
                            if((int)$value == 0) { $value = '-'; break; }
                            
                            $value = $i18n->date($col->format, $value);
                            break;
                        
                        case 'password':
                            $value = '';
                            $row[$col->name] = '';
                            break;
                        
                        default:
                            $value = xmlentities($value);
                            $origin = xmlentities($origin);
                            break;
                    }
                    $xml .= "<$col->name original=\"$origin\" type=\"".$col->datatype."\">$value</$col->name>";
                }
                $xml .= '</row>';
            }
        }
        return $xml;
    }
    
    public function prepareUpdates($updates){
        global $i18n;
        foreach($updates as $field => &$value){
            if(isset($this->cols[$field])){
                $options = $this->cols[$field]->options;
                switch($options['datatype']){
                    case 'datetime':
                        if($options['timestamp']){
                            if(!is_numeric($value))
                                $value = $i18n->strtotime($value);
                        } else {
                            if(!preg_match('/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})/', $value))
                                $value = $i18n->date('Y/m/d H:i:s', $value);
                        }
                        break;
                    case 'password':
                        if(empty($value)) unset($updates[$field]);
                        else $value = md5($value);
                        break;
                }
            }
        }
        return $updates;
    }
    
    public function php2ui_dateformat($php_format){
        $pattern = array(
            '/d/','/j/','/l/','/z/',//day
            '/F/','/M/','/n/','/m/',//month
            '/Y/','/y/'//year
        );
        $replace = array(
            'dd','d','DD','o',
            'MM','M','m','mm',
            'yy','y'
        );
        $ui_format = preg_replace($pattern, $replace, $php_format);
        return trim(preg_replace(array('/h/i','/i/i','/s/i', '/\:/'), '', $ui_format));
    }
    
    public function __toString(){
        global $Engine, $Viewer, $i18n;
        $this->ajax->data('grid', $this);
        $this->ajax->data('primary', $this->options->primary);
        $this->addClass('gxui-border');
        
        $grid = 
        '<div '.$this->unify().' tabindex="0">
            <div class="grid-caption gxui-header gxui-clearfix">';
            foreach(preg_split('/[,;]/', $this->options->buttons, -1, PREG_SPLIT_NO_EMPTY) as $b){
                if(isset($this->toolbarButtons[$b])){
                    $btn = $this->toolbarButtons[$b];
                    $grid .=
                    '<div class="icon gxui-clickable gxui-inline-block" '.
                        'data-viewstate="'.(isset($btn[3]) && $btn[3]? 'enable': 'disable').'" '.
                        'data-selectstate="'.(isset($btn[4]) && $btn[4]? 'enable': 'disable').'" '.
                        'data-editstate="'.(isset($btn[5]) && $btn[5]? 'enable': 'disable').'" '.
                        'data-name="'.$b.'">'.
                        '<span class="ui-icon ui-icon-'.$btn[0].' gxui-float-right"></span>'.
                        '<span class="label gxui-float-right">'.$btn[1].'</span>'.
                    '</div>';
                }
            }

        $grid .=
            '</div>
        
            <div class="grid-thead">
                <table cellspacing="0" cellpadding="0">
                    <thead><tr>';
            foreach($this->cols as &$col){
                $c = empty($col->caption)? $col->name: $col->caption;
                $w = $col->width;
                $grid .= '<th width="'.$w.'" class="gxui-bevel'.($col->hidden? ' gxui-hidden': '').'"><div>'.$c.'</div></th>';
            }
        
            $grid .= 
                        '<th class="gxui-bevel" style="padding: 0px; margin: 0px;"></th>
                    </tr></thead>
                </table>
            </div>
            
            <div class="grid-tbody" style="height: '.$this->options->height.';">
                <div class="grid-preloader" style="display: block;"><div class="cover">
                    <div class="text gxui-highlight gxui-border">Loading... </div>
                </div></div>
                
                <div class="gxui-error gxui-border"><span class="arrow"></span><span class="text"></span></div>
                
                <form autocomplete="off" onsubmit="return false;"><table cellspacing="0" cellpadding="0">
                    <thead><tr>';
            foreach($this->cols as &$col){
                $c = empty($col->caption)? $col->name: $col->caption;
                $w = $col->width;
                $grid .= '<th width="'.$w.'" class="'.($col->hidden? 'gxui-hidden': '').'"></th>';
            }
        
            $grid .= 
                        '
                    </tr></thead>
                    <tbody></tbody>
                </table></form>
                
            </div>
            
            <div class="grid-status gxui-footer gxui-clearfix gxui-border gxui-border-top">
                <div class="caption">'.$this->options->caption.'</div>
                <div class="total"></div>
            </div>
        </div>';
        
        $Viewer->bind('ondeclare', "var $this->name;");
        $this->options->caption = addslashes($this->options->caption);
        $Viewer->bind('onready', $this->name.' = $("#'.$this->id.'").grid('.$this->getOptions().');');
        $this->options->caption = stripslashes($this->options->caption);
        //$Viewer->bind('onready', '$($(".graphic-grid").get(0)).focus();');
        
        return $grid;
    }
}
?>
