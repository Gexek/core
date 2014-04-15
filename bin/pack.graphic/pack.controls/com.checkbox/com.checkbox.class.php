<?php
namespace Graphic\Controls;

class Checkbox extends Control {
    static public $depends = array();
    protected $defualtOptions = array(
        'checked' => false
    );
    static public function init(){}
    
    public function __construct($name, $options = array()){
        parent::__construct($name, $options);
        $this->clientCalls = array('value' => function($obj, $args){
            if(isset($args[0])){
                if($args[0]) return '$("#'.$obj->id.'").attr("checked", "true")';
                else return '$("#'.$obj->id.'").removeAttr("checked")';
            } else
                return '$("#'.$obj->id.'").is(":checked")';
        });
    }
    
    protected function generate(){
        if($this->binded('onclick')){
            global $Viewer;
            $Viewer->bind('onready',
                '$("#'.$this->id.'").click(function(){'.
                    'var state = '.$this->call('value').';'.
                    $this->join('onclick').
                '});'
            );
        }
        return '<input type="checkbox" '.$this->unify().($this->options->checked? ' checked': '').'>';
    }
}
?>