<?php
namespace Graphic;

class Dialog extends Graphic {
	private $create_call = null; 

    static public $depends = array(
        'Graphic\UI\jQueryUI\Button',
        'Graphic\UI\jQueryUI\Draggable',
        'Graphic\UI\jQueryUI\Position',
        'Graphic\UI\jQueryUI\Resizable'
    );
    
    public $message = null, $content = null, $buttons = array();
    static public $Instances = array();
    
    public function __construct($name, $options = array()){
        global $i18n;
        extend($options, array(
            'caption'       => '',
            'disabled'      => false,
            'autoOpen'      => false,
            'closeOnEscape' => true,
            'closeText'     => $i18n->close,
            'draggable'     => true,
            'height'        => 'auto',
            'maxHeight'     => false,
            'maxWidth'      => false,
            'minHeight'     => 150,
            'minWidth'      => 150,
            'modal'         => true,
            'position'      => 'center',
            'resizable'     => false,
            'stack'         => true,
            'width'         => 'auto'
        ));
        parent::__construct($name, $options);
        
        $this->clientCalls = array(
            'open'  => '$'.$this->name.'.dialog("open")',
            'close' => '$'.$this->name.'.dialog("close")',
        	'movecenter' => '$'.$this->name.'.dialog("option", "position", {my: "center", at: "center", of: window})',
        );
        Dialog::$Instances[] = &$this;
    }
    
    protected function call_create(){
    	if(is_null($this->create_call)){
	        $btns = array();
	        foreach($this->buttons as $n=>$c)
	            $btns[] = '"'.$n.'": function(e){e.stopPropagation(); '.$c.'}';
	        
	        $this->create_call = '$'.$this->name.' = $("#'.$this->id.'").dialog({'.
                $this->parseOptions(null, false).
                (count($btns)>0? ',buttons: {'.implode(',', $btns).'}': '').
                ', open: function(e, ui){'.
                    'var btns = $("#'.$this->id.'").dialog("option", "buttons");'.
                    '$.each(btns, function(i, b){'.
                        'var btn = $(".ui-dialog-buttonpane button:contains(\'"+i+"\')");'.
                        '$(btn).button("enable");'.
                    '});'.
                    $this->join('onopen').
                '}, beforeClose: function(e, ui){'.
                    $this->join('onbeforeclose').
                '}, close: function(e, ui){'.
                    $this->join('onclose').
                '}'.
            '}).click(function(e){e.stopPropagation();});';
    	}
    	return $this->create_call;
    }
    
    public function addButton($caption, $action, $close = true, $disableAfter = false){
        $this->buttons[$caption] = trim($action, ';').';'.
        ($disableAfter? 'var btn = $(".ui-dialog-buttonpane button:contains(\''.$caption.'\')");'.
        '$(btn).button("disable");': '').
        ($close? '$(this).dialog("close");': '');
    }
    
    public function __toString(){
        global $Viewer; 
        $Viewer->bind('onready', $this->call_create());
        // backward compatibility to gexek 1.5.0
        if(is_null($this->content)) $this->content = $this->message;
        return '<div '.$this->unify().' title="'.$this->options->caption.'">'.$this->content.'</div>';
    }
}
?>