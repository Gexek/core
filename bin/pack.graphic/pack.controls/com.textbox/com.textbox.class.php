<?php
namespace Graphic\Controls;

class TextBox extends Control {
    static public $depends = array();
    private $validations = array(
        'email' => '^([a-z0-9_\.]+)@([a-z0-9-]+).([a-z]{2,4})$'
    );
    protected $defualtOptions = array(
        'pattern' => null, 'type' => 'text', 'size' => 20, 'pattern'=>'', 'validchars'=>'',
        'value' => '', 'visuality' => 'border', 'prefix' => '', 'suffix' => '', 'select'=>false,
        'allowempty'=>true, 'validation'=>null, 'autocomplete' => true
    );
    
    static public function init(){}
    
    protected function generate(){
        global $i18n, $Viewer;
        
        if(!empty($this->options->validation) && empty($this->options->pattern))
            if(isset($this->validations[$this->options->validation]))
            $this->options->pattern = $this->validations[$this->options->validation];

        if(!$this->options->allowempty){ $this->bind('onkeyup',
            'if(value.trim() == "")'.
                '$(this).addClass("gxui-error");'.
            'else '.
                '$(this).removeClass("gxui-error");'
        );}
        
        if(!empty($this->options->pattern) || !empty($this->options->validchars))
            $this->bind('onkeyup', 'var val = $(this).val().trim();');
        
        if(!empty($this->options->validchars)){ $this->bind('onkeyup',
            'if(val != ""){'.
                'var pattern = new RegExp("^(['.$this->options->validchars.']+)$");'.
                'if(pattern.test(val))'.
                    '$(this).data("lastvalue", val);'.
                'else{'.
                    '$(this).val(lv);'.
                    'e.preventDefault();'.
                '}'.
            '}'
        );}
        if(!empty($this->options->pattern)){ $this->bind('onkeyup',
            'if(val != ""){'.
                'var pattern = new RegExp("'.$this->options->pattern.'");'.
                'if(!pattern.test(val))'.
                    '$(this).addClass("gxui-error");'.
                'else '.
                    '$(this).removeClass("gxui-error");'.
            '}'
        );}
        
        if($this->options->select){ $this->bind('onclick', '$(this).select();'); }
        
        if($this->binded())
        $Viewer->bind('onready', '$("#'.$this->id.'")'.
            ($this->binded('onkeydown')? '.keydown(function(e){'.$this->join('onkeydown').'})': '').
            ($this->binded('onkeypress')? '.keypress(function(e){'.$this->join('onkeypress').'})': '').
            ($this->binded('onchange')? '.change(function(e){'.$this->join('onchange').'})': '').
            ($this->binded('onfocus')? '.focus(function(e){'.$this->join('onfocus').'})': '').
            ($this->binded('onblur')? '.blur(function(e){'.$this->join('onblur').'})': '').
            ($this->binded('onclick')? '.click(function(e){'.$this->join('onclick').'})': '').
            ($this->binded('onkeyup')? '.keyup(function(e){'.
                'var lv = $(this).data("lastvalue");'.
                'if(!lv) lv = "";'.
                'var value = new String($(this).val());'.
                $this->join('onkeyup').
            '}).keyup()': '').';'
        );
        
        $prefix = '<span>'.$this->options->prefix.'</span>';
        $suffix = '<span>'.$this->options->suffix.'</span>';
        if($i18n->dir == 'rtl' && $this->options->ltr){
            $suffix = '<span class="gxui-inline-block" style="direction: ltr;">'.$this->options->prefix.'</span>';
            $prefix = '<span>'.$this->options->suffix.'</span>';
        }
        
        return $prefix.'<input '.$this->unify().' type="'.$this->options->type.'" '.
        'size="'.$this->options->size.'" value="'.$this->options->value.'" autocomplete="'.($this->options->autocomplete? 'on': 'off').'" />'.$suffix;
    }
}
?>