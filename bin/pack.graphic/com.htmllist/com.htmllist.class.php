<?php
namespace Graphic;

class HTMLList extends Graphic{
    protected $items, $s = array('right'=>'e', 'left'=>'w');
    protected $defaultOptions = array('visuality' => 'border');
    
    static public $depends = array();
    
    public function __construct($name, $options = array()){
        global $i18n;
        $this->items = array();
        extend($options, array(
            'items'     => array(),
            'default'   => array('label' => 'New Item'),
            'template'  => '<span class="gxui-inline-block ui-icon [%icon%]"></span>[%label%]',
            'icon'      => 'triangle'
        ));
        $this->items = $options['items'];
        unset($options['items']);
        parent::__construct($name, $options);
        $this->prepareIcons();
    }
    
    protected function prepareIcons(){
        global $i18n;
        switch($this->options->icon){
            case 'carat':  $this->options->icon = 'ui-icon-carat-1-'.$this->s[$i18n->_align]; break;
            case 'bullet': $this->options->icon = 'ui-icon-bullet'; break;
            case 'radio':  $this->options->icon = 'ui-icon-radio-off'; break;
            case 'triangle':
            default:       $this->options->icon = 'ui-icon-triangle-1-'.$this->s[$i18n->_align]; break;
        }
    }
    
    private function append($lists){
        if(isset($lists['label']))
            $this->items[] = $lists;
        else {
            foreach($lists as $list)
                $this->append($list);
        }
    }
    
    public function makeTree($dataset, $options = array()){
        extend($options, array(
            'childkey' => 'nID', 'parentkey' => 'nParentID',
            'label' => 'cCaption', 'start' => 0
        ));
        
        if($dataset instanceof \PDOStatement)
            $dataset = $dataset->fetchAll(\PDO::FETCH_ASSOC);
            
        $tree = array();
        foreach($dataset as $row){
            
            if($row[$options['parentkey']] == $options['start']){
                $array = array();
                foreach($row as $field => $value){
                    if($field == $options['label'])
                        $array['label'] = $value;
                    else if($field == $options['childkey'])
                        $array['value'] =  $value;
                    else
                        $array[$field] =  $value;
                }
                
                $opt = $options;
                $opt['start'] = $row[$options['childkey']];
                
                $array['items'] = $this->makeTree($dataset, $opt);
                $tree[] = $array;
            }
        }
        return $tree;
    }
    
    public function import(){
        $args = func_get_args();
        if(count($args) > 0){
            $lists = $args[0];
            $this->append($lists);
        }
    }
    
    protected function toHTML($items, $level){
        $list = $level>0? '<ul level="'.$level.'">': '';
        
        foreach($items as $item){
            $label = $item['label'];
            $value = isset($item['value'])? $item['value']: $item['label'];
            $hasList = isset($item['items']) && is_array($item['items']) && count($item['items']) > 0;
            if($hasList)
                $items = $this->toHTML($item['items'], $level+1);
            else
                $items = '';
            $template = preg_replace('/\[%(\w+)%\]/ie', 'isset($item[\'\1\'])? $item[\'\1\']: \'\'', $this->options->template);
            $list .= '<li>'.$template.$items.'</li>';
        }
        
        return $list.($level>0? '</ul>': '');
    }
    
    public function __toString(){
        return '<ul level="0" '.$this->unify().'>'.$this->toHTML($this->items, 0).'</ul>';
    }
}
?>
