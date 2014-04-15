<?php
namespace Graphic\Controls;

class TagInput extends Control {
    static public $depends = array(
        'Graphic\UI\JQueryUI\Sortable',
        'Graphic\UI\JQueryUI\AutoComplete'
    );

    protected $defualtOptions = array(
        'tagSource' => array(), 'triggerKeys' => array('enter', 'space', 'comma', 'tab'), 
        'allowNewTags' => true, 'initialTags' => array(), 'sortable' => false, 'select' => true
    );
    
    public function __construct($name, $options){
        parent::__construct($name, $options);
        $this->clientCalls = array(
            'value' => '$("#'.$this->id.'_select").val()'
        );
        $source = $this->options->tagSource;
        $this->options->tagSource = array();
        foreach($source as $v => $n)
            $this->options->tagSource[] = (object)array('label' => $n, 'value' => $v);
    }
    
    protected function generate(){
        global $i18n, $Viewer;
        $Viewer->bind('onready', '$("#'.$this->id.'").tagit('.$this->parseOptions().');');
        $list = '<ul name="'.$this->id.'" id="'.$this->id.'">';
        foreach($this->options->initialTags as $v => $n)
            if(!empty($n) && !empty($v))
                $list .= '<li tagvalue="'.$v.'">'.$n.'</li>';
        return $list.'</ul>';
    }
}
?>