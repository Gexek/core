<?php
namespace Graphic;

class DropDown extends \Graphic\HTMLList {
    public function __construct($name, $options = array()){
        global $i18n;
        $this->items = array();
        extend($options, array(
            'items'     => array(),
            'default'   => array('label' => 'New Item'),
            'template'  => '<li>[%label%]</li>'
        ));
        $this->items = $options['items'];
        unset($options['items']);
        parent::__construct($name, $options);
        $this->prepareIcons();
    }
    
    protected function toHTML($items, $level){
        $list = array();
        
        foreach($items as $item){
            $label = $item['label'];
            $value = isset($item['value'])? $item['value']: $item['label'];
            $hasList = isset($item['items']) && is_array($item['items']) && count($item['items']) > 0;
            if($hasList){
                $items = $this->toHTML($item['items'], $level+1);
                $class = 'list';
            } else {
                $items = '';
                $class = 'item';
            }
            $template = preg_replace('/\[%(\w+)%\]/ie', 'isset($item[\'\1\'])? $item[\'\1\']: \'\'', $this->options->template);
            $class .= $level>0? '': ' gxui-inline-block';
            
            $list[] = '<li class="'.$class.'">'.$template.$items.'</li>';
        }
        
        $class = $level>0? 'splitter': 'splitter gxui-inline-block';
        return ($level>0? '<ul level="'.$level.'">': '').
        implode('<li class="'.$class.'"></li>', $list).
        ($level>0? '</ul>': '');
    }
    
    public function __toString(){
        global $Viewer;
        $dropdown = '<ul level="0" '.$this->unify().'>'.$this->toHTML($this->items, 0).'</ul>';
        $options = get_object_vars($this->options);
        unset($options['template']);
        $Viewer->bind('onready', '$("#'.$this->id.'").dropdown('.$this->parseOptions((object)$options).');');
        return $dropdown;
    }
}
?>