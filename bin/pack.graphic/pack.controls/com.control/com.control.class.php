<?php
namespace Graphic\Controls;

abstract class Control extends \Graphic\Graphic{
    private $controlClientCalls = array();
    abstract protected function generate();
    
    public function __construct($name, $options = array()){
    	if(is_string($options)){
    		$opts = explode(';', trim($options));
    		$options = array();
    		foreach($opts as $opt){
    			$opt = explode('=', $opt);
    			$opt[0] = trim($opt[0]);
    			
    			if(isset($opt[1])){
    				$opt[1] = trim($opt[1]);
    				switch ($opt[1]){
    					case 'true': $opt[1] = true; break;
    					case 'false': $opt[1] = false; break;
    					case 'null': $opt[1] = null; break;
    				}
    				$options[$opt[0]] = $opt[1];
    			} else
    				$options[$opt[0]] = null;
    		}
    	}
    		
        extend($options, array('label' => '', 'note' => '', 'ltr' => false, 'hidden' => false, 'value' => null));
        parent::__construct($name, $options);
        $this->controlClientCalls = array(
            'value' => new \SuperClosure(function($obj, $args){
                return '$("#'.$obj->id.'").val('.implode(',', $args).')';
            })
        );
        extend($this->clientCalls, $this->controlClientCalls);
    }
    
    public function __get($var){
    	if(isset($this->options->$var))
    		return $this->options->$var;
    	else if(isset($this->$var))
    		return $this->$var;
    	return false;
    }
    
    public function __set($var, $value){
    	if(isset($this->options->$var))
    		$this->options->$var = $value;
    }
    
    final public function __toString(){
        if($this->options->ltr) $this->style('direction', 'ltr');
        $arr = explode('\\', get_class($this));
        return
        '<div style="'.($this->options->hidden? 'display: none !important;': '').'" type="'.strtolower(end($arr)).'" class="gxui-control gxui-inline-block">'.
            (!empty($this->options->label)? '<label class="gxui-inline-block default"><b>'.$this->options->label.'</b></label>': '').
            $this->generate().
            (!empty($this->options->note)? '<label class="gxui-inline-block note">'.$this->options->note.'</label>': '').
        '</div>';
    }
}
?>