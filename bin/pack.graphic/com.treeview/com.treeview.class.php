<?php
namespace Graphic;

class Treeview extends \Graphic\HTMLList {
    public function __construct($name, $options = array()){
        extend($options, array(
            'items'         => array(),
            'default'       => array('label' => 'New Item'),
            'template'      => '[%label%]',
            'icon'          => 'triangle',
            'speed'         => 500,
            'opened'        => false,
            'checkbox'      => false,
            'multiple'      => true,
            'movable'       => false,
            'showroot'      => false,
            'rootlabel'     => 'Root'
        ));
        $this->items = $options['items'];
        unset($options['items']);
        parent::__construct($name, $options);
        $this->prepareIcons();
    }
    
    protected function prepareIcons(){
        global $i18n;
        switch($this->options->icon){
            case 'carat':
                $this->options->icon = array(
                    'ui-icon-carat-1-'.$this->s[$i18n->_align],
                    'ui-icon-carat-1-s',
                    'ui-icon-arrow-1-'.$this->s[$i18n->_align]
                );
                break;
                
            case 'bullet':
                $this->options->icon = array(
                    'ui-icon-bullet',
                    'ui-icon-bullet',
                    'ui-icon-stop'
                );
                break;
                
            case 'radio':
                $this->options->icon = array(
                    'ui-icon-radio-off',
                    'ui-icon-radio-off',
                    'ui-icon-radio-on'
                );
                break;
            
            case 'plus':
                $this->options->icon = array(
                    'ui-icon-plus',
                    'ui-icon-minus',
                    'ui-icon-arrow-1-'.$this->s[$i18n->_align]
                );
                break;
            
            case 'triangle':
            default:
                $this->options->icon = array(
                    'ui-icon-triangle-1-'.$this->s[$i18n->_align],
                    'ui-icon-triangle-1-s',
                    'ui-icon-carat-1-'.$this->s[$i18n->_align]
                );
                break;
        }
    }

    protected function toHTML($items, $level){
        global $i18n;
        $list = $level>0? '<ul level="'.$level.'">': '';
        
        foreach($items as $item){
            $label = $item['label'];
            $value = isset($item['value'])? $item['value']: $item['label'];
            $hasList = isset($item['items']) && is_array($item['items']) && count($item['items']) > 0;
            
            if($hasList){
                $class = 'list '.($this->options->opened? '': 'closed');
                $items = $this->toHTML($item['items'], $level+1);
                $icon = 'handle '.($this->options->opened? $this->options->icon[1]: $this->options->icon[0]);
            } else {
                $class = 'item';
                $items = '';
                $icon = $this->options->icon[2];
            }
            
            $template = preg_replace('/\[%(\w+)%\]/ie', 'isset($item[\'\1\'])? $item[\'\1\']: \'\'', $this->options->template);
            
            $list .=
            '<li class="'.$class.' gxui-clearfix">'.
                '<table cellspacing="0" cellpadding="0" class="gxui-flat gxui-light"><tr>'.
                    '<td class="icon"><span class="ui-icon '.$icon.'"></span></td>'.
                    ($this->options->checkbox? ($this->options->multiple?
                        '<td class="checkbox"><input type="checkbox" /></td>':
                        '<td class="checkbox"><input autocomplete="off" class="selector" name="'.$this->id.'_radio" type="radio" /></td>'
                    ): '').
                    '<td class="content">'.$template.'</td>'.
                    ($this->options->movable? '<td class="movement"><span class="ui-icon ui-icon-triangle-1-n move up" title="'.$i18n->com_treeview_moveup.'"></span></td>': '').
                    ($this->options->movable? '<td class="movement"><span class="ui-icon ui-icon-triangle-1-s move down" title="'.$i18n->com_treeview_movedown.'"></span></td>': '').
                '</tr></table>'.
                $items.
            '</li>';
        }
        
        if($level == 0)
        $list .=
        '<li class="item blank gxui-clearfix">'.
            '<table cellspacing="0" cellpadding="0" class="gxui-flat gxui-light"><tr>'.
                '<td class="icon"><span class="ui-icon '.$this->options->icon[2].'"></span></td>'.
                ($this->options->checkbox? ($this->options->multiple?
                    '<td class="checkbox"><input type="checkbox" /></td>':
                    '<td class="checkbox"><input autocomplete="off" class="selector" name="'.$this->id.'_radio" type="radio" /></td>'
                ): '').
                '<td class="content">'.preg_replace('/\[%(\w+)%\]/i', '', $this->options->template).'</td>'.
                ($this->options->movable? '<td class="movement"><span class="ui-icon ui-icon-triangle-1-n move up" title="'.$i18n->com_treeview_moveup.'"></span></td>': '').
                ($this->options->movable? '<td class="movement"><span class="ui-icon ui-icon-triangle-1-s move down" title="'.$i18n->com_treeview_movedown.'"></span></td>': '').
            '</tr></table>'.
        '</li>';
        
        return $list.($level>0? '</ul>': '');
    }
    
    public function __toString(){
        global $Engine, $Viewer, $i18n;
        $tree = $this->toHTML($this->items, 0);
        if($this->options->showroot)
            $tree =
            '<li class="root">'.
                '<table cellspacing="0" cellpadding="0" class="gxui-flat gxui-light"><tr>'.
                    '<td class="content"><b>'.$this->options->rootlabel.'</b></td>'.
                '</tr></table>'.
                '<ul>'.$tree.'</ul>'.
            '</li>';
            
        $tree = '<ul level="0" '.$this->unify().'>'.$tree.'</ul>';
        unset($this->options->template);
        $Viewer->bind('onready',
            '$("#'.$this->id.'").treeview('.$this->parseOptions().')'.
            ($this->binded('onselect')? '.bind("select", function(){'.$this->join('onselect').'})': '').
            ($this->binded('ondeselect')? '.bind("deselect", function(){'.$this->join('ondeselect').'})': '').
            ($this->binded('onmove')? '.bind("move", function(e, side, $element, $replace){'.$this->join('onmove').'})': '').
            ';'
        );
        return $tree;
    }
}
?>
