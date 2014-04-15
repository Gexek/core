<?php
namespace Graphic\Controls;

class Textarea extends Control {
    static public $depends = array();
    protected $defualtOptions = array(
        'value' => '', 'visuality' => 'border', 'resizable' => true,
        'width' => 'auto', 'height' => 'auto'
    );
    
    static public function init(){}
    
    protected function br2nl($html){
        return preg_replace('#<br\s*/?>#i', "\n", $html);
    }
    
    protected function generate(){
        if(!$this->options->resizable)
            $this->style('resize', 'none');
        if($this->options->width != 'auto') 
            $this->style('width', $this->options->width);
        if($this->options->height != 'auto') 
            $this->style('height', $this->options->height);
            
        return '<textarea '.$this->unify().'>'.$this->options->value.'</textarea>';
    }
}
?>