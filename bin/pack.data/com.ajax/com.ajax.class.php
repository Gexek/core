<?php
namespace Data;

class Ajax extends \ClientSide{
    private $caller = null, $declared;
    public $name, $options, $meta = array(
        'args' => array(), 'data' => array(),
    );
    public $asCallback = false;
    static public $Instances = array();
    
    public function __construct($options = array()){
        extend($options, array(
            'name' => null,
            'url' => '',
            'async' => true,
            'result' => 'xml',
            'method' => 'post'
        ));
        $this->options = $options;
        if(empty($options['name']))
            $this->name = '__ajaxCall'.count(Ajax::$Instances);
        else {
            $options['name'] = '__'.trim($options['name'], '_');
            if(isset(Ajax::$Instances[$options['name']]))
                throw new \Exception('An Ajax instance already exists with selected name "'.$options['name'].'"');
            $this->name = $options['name'];
        }
        Ajax::$Instances[$this->name] = &$this;
    }
    
    public function __toString(){
        global $Engine;
        if(count($this->meta['args']) > 0)
            $this->asCallback = true;
        return $this->name.($this->asCallback? '': '();');
    }
    
    public function setCaller($name, $type = 'component'){
        $this->caller = new \stdClass;
        if(is_object($name)){
            if($name instanceof \Graphic\Graphic){
                $this->caller->type = 'component';
                $name = addslashes(get_class($name));
                $this->caller->name= $name;
            } else if($name instanceof \Plugin) {
                $this->caller->type = 'plugin';
                $this->caller->name= $name->name;
                $this->data('__path', $this->options['url']);
                $this->options['url'] = '';
            }
        } else {
            $this->caller->type = $type;
            $this->caller->name = $name;
        }
    }
    
    public function arg($name, $bind = false){
        if(isset($this->meta['args'][$name]))
            unset($this->meta['args'][$name]);
        else
            $this->meta['args'][$name] = $bind;
    }
    
    public function data(){
        $args = func_get_args();
        if(count($args) > 0){
        	// set or get a data
            if(isset($args[1])){
            	// set data
                if(is_object($args[1]))
                    $args[1] = urlencode(serialize($args[1]));
                else if(is_array($args[1]))
                    $args[1] = json_encode($args[1]);
                $this->meta['data'][$args[0]] = $args[1];
            } else {
                if(is_array($args[0])){
                	// import data from an array
                    foreach($args[0] as $name=>$value)
                        $this->meta['data'][$name] = $value;
                } else {
                	// get a data
                    if(isset($this->meta['data'][$args[0]]))
                        return $this->meta['data'][$args[0]];
                    else
                        return 'Data.'.$args[0];
                }
            }
        } else
        	// return all data
            return $this->meta['data'];
    }
    
    private function __restrictMeta($name){
        return in_array($name, array('__locale', 'RTSC'));
    }
    
    private function getData(){
        global $i18n;
        $data = array(
        	'__RTSC: "'.\SysUtils\Security::getRTSC().'",'.
        	'__locale: "'.$i18n->locale.'",'.
        	'__path: "'.$this->options['url'].'"'
        );
        
        foreach($this->meta['data'] as $n=>$v)
            if(!$this->__restrictMeta($n))
                $data[] = $n.': '.$this->fixJS($v);

        foreach($this->meta['args'] as $n=>$v)
            if($v && !$this->__restrictMeta($n)) $data[] = "$n: $n";
            
        return '{'.implode(', ', $data).'}';
    }
    
    public function setMethod($method){
        $method = strtolower($type);
        $this->setValue($this->method, $method, array('post','get'));
    }
    
    public function declaration(){
        if($this->declared) return '';
        global $i18n;
        
        if(!is_null($this->caller))
            $this->data('__caller', $this->caller->type.':'.$this->caller->name);
        
        return
            "function $this->name(".implode(',', array_keys($this->meta['args'])).'){'.
                'var Data = '.$this->getData().';'.
                'var jxReq = $.ajax({'.
                    'url: "ajax/'.$i18n->locale.'/",'.
                    'async: '.($this->options['async']? 'true': 'false').','.
                    'type: "'.$this->options['method'].'",'.
                    'dataType: "'.$this->options['result'].'",'.
                    'data: Data,'.
                    'beforeSend: function(XHR){'.$this->join('onsend').'},'.
                    'error: function(XHR, status, error){'.$this->join('onerror').'},'.
                    //var response = result; is for backward compatibility.
                    'success: function(result, status, XHR){var response = result;'.$this->join('onsuccess').'},'.
                    'complete: function(XHR, status){'.$this->join('oncomplete').';$.fn.gxscroll();}'.
                '});'.
                'return false;'.
            '}';
    }
}
?>