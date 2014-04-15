<?php
namespace Graphic;

class FormSection extends Graphic {
    protected $controls = array(
        'panel' => array(),
        'popup' => array(),
    );
    
    public function __construct($options = array()){
        global $Engine;
        extend($options, array(
            'expandable' => false,
            'collapsed' => null,
            'caption' => '',
            'note' => '',
            'visuality' => '',
            'gridlayout' => array(50,50),
            'popupcaption' => '[i18n:com_form_popup]',
            'popuptitle' => '[i18n:com_form_popup_title]'
        ));
        if(!is_array($options['gridlayout']))
            $options['gridlayout'] = array($options['gridlayout']);
            
        if(is_null($options['collapsed']))
            $options['collapsed'] = $options['expandable'];
        
        $name = $Engine->uniqid();
        parent::__construct($name, $options);
    }
    
    public function __get($var){
        return $this->$var;
    }
    
    public function add($control, $location = 'panel', $col = 1){
        if(!isset($this->controls[$location])) return $this;
        
        // Min 1 and max 2 columns
        $col = $col < 1? 1: ($col > 2? 2: $col);
        
        if(!isset($this->controls[$location][$col]))
            $this->controls[$location][$col] = array();
        $this->controls[$location][$col][] = &$control;
        
        return $this;
    }
    
    public function addSplitter($caption, $location = 'panel', $col = 1){
        if(!isset($this->controls[$location])) return $this;
        
        // Min 1 and max 2 columns
        $col = $col < 1? 1: ($col > 2? 2: $col);
        
        if(!isset($this->controls[$location][$col]))
            $this->controls[$location][$col] = array();
        
        $this->controls[$location][$col][] = '<legend class="splitter"><span class="gxui-flat">'.$caption.'</span><div></div></legend>';
        
        return $this;
    }
    
    private function createGrid($loc){
        global $i18n;
        $grid = '<div class="gxui-grid">';
        foreach($this->controls[$loc] as $i => $column){
            $c = $i - 1; $l = $this->options->gridlayout; $gl = 100;
            
            if(count($this->controls[$loc]) != count($l))
                $gl = (100/count($this->controls[$loc]));
            else
                $gl = $l[$c];
            
            $hiddens = array();
            $grid .= '<div class="gxui-g'.$gl.' gxui-float">';
            foreach($column as $control) 
                if(!is_string($control) && $control->options->hidden)
                    $hiddens[] = $control;
                else
                    $grid .= '<div class="fieldrow">'.$control.'</div>';
            foreach($hiddens as $control)
                $grid .= $control;
            $grid .= '</div>';
        }
        $grid .= '</div>';
        return $grid;
    }
    
    public function __toString(){
        if($this->options->expandable){
            $this->addClass('expandable');
            $this->addClass('gxui-clickable', 'header');
        }
        if($this->options->collapsed)
            $this->addClass('collapsed');
        
        $hasPopup = count($this->controls['popup']) > 0;
        
        $section =
        '<div '.$this->unify().'>'.
            (empty($this->options->caption)? '':
            '<header class="gxui-header '.$this->getClass('header').'">'.
                '<b>'.$this->options->caption.'</b><span class="gxui-spinner-fix"><div class="gxui-spinner-block gxui-spinner small ajax hidden gxui-inline-block"></div></span>'.
                ($hasPopup? '<span class="popup-handle gxui-popuper" rel="#'.$this->id.'_popup" title="'.$this->options->popuptitle.'"><span class="ui-icon ui-icon-carat-1-s"></span>'.$this->options->popupcaption.'</span>': '').
            '</header>').
            '<section class="gxui-clearfix">'.
                '<div class="ajax-cover gxui-overlay"></div>'.
                '<div class="fields '.(empty($this->options->note)? 'gxui-g100': 'gxui-g70').' gxui-float">'.$this->createGrid('panel').'</div>'.
                (!empty($this->options->note)? '<cite class="note gxui-g30 gxui-float gxui-border gxui-border-[i18n:align]">'.$this->options->note.'</cite>': '').
            '';
        
        if($hasPopup) {
            $section .=
            '<div id="'.$this->id.'_popup" data-coords="5,0" data-side="[i18n:_align]" class="gxui-clearfix gxui-popup">'.
                '<div class="ajax-cover gxui-overlay"></div>'.
                $this->createGrid('popup').
            '</div>';
        }
        
        return $section.'</section></div>';
    }
}

class Form extends Graphic{
    protected $sections = array();
    protected $ajax; // readonly
    
    public function __construct($name, $options = array()){
        extend($options, array(
            'action' => null,
            'submit' => true,
            'ajaxsubmit' => false,
            'spinnertext' => '',
            'hidespinner' => false,
            'spinnerdelay' => 3000,
            'preventresend' => true,
            'showbuttons' => true,
            'ajaxdata' => array(),
            'hidden'=>false,
            'expandedsection' => 0,
            'autocomplete' => true,
            'autosave' => 0, // 0 = disable
            'visuality' => 'bordered flat'
        ));
        parent::__construct($name, $options);
        
        if($_POST->submit && $this->options->preventresend){
            global $Engine;
            $Engine->redirect(\Utils\URL::current(), false, false);
        }
        
        if($options['ajaxsubmit']){
            global $Engine, $i18n;
            $Engine->import('Data.Ajax');
            $this->ajax = new \Data\Ajax(array('url'=>$options['action']));
            $this->ajax->arg('autosave', true);
            $this->ajax->data($this->options->ajaxdata);
            $this->ajax->data('test', true);
            
            $this->ajax->bind('onsend',
                'if(!autosave){'.
                //'$("#'.$this->id.' .ajax-loader .emptyspinner").addClass("spinner");'.
                '$("#'.$this->id.' .ajax-cover").show();'.
                '$("#'.$this->id.' .gxui-spinner.ajax").removeClass("hidden");'.
                //'$("#'.$this->id.' .ajax-loader .text").text("'.$i18n->sending_data.'");'.
                '}'
            );
            $this->ajax->bind('oncomplete',
                'if(!autosave){'.
                '$("#'.$this->id.' .ajax-cover").hide();'.
                '$("#'.$this->id.' .gxui-spinner.ajax").addClass("hidden");'.
                '$("#'.$this->id.' .ajax-loader .text").html(XHR.responseText+"<br />'.$options['spinnertext'].'");'.
                '}'
            );
        }
        
        $this->clientCalls = array(
            'submit'  => '$("#'.$this->id.'").submit()',
            'clear' => '$("#'.$this->id.'").trigger("reset")'
        );
    }
    
    public function add(FormSection $section){
        $this->sections[] = $section;
        return $this;
    }

    public function __toString(){
        global $Viewer;
        
        $action = !empty($this->options->action)? ' action="'.$this->options->action.'"': '';
        
        $onsubmit = '';
        if($this->options->submit){
            if($this->options->ajaxsubmit){
                $onsubmit = " return $this->ajax(false);";
                foreach($this->sections as $s) foreach($s->controls as $loc) foreach($loc as $col)
                    foreach($col as $c) if(!is_string($c) && method_exists($c, 'call'))
                        $this->ajax->data($c->name, $c->call('value', null, 'value'));
            }
        } else
            $onsubmit = 'return false;';
            
        $Viewer->bind('onready',
            '$("#'.$this->id.'").gxform({ajax: '.(is_null($this->ajax)? 'null': $this->ajax).', autosave: '.$this->options->autosave.'}).'.
            'submit(function(){'.$onsubmit.'})'.
            ($this->binded('onreset')? '.bind("reset", function(){'.
                'setTimeout(function(){'.$this->join('onreset').'}, 50);'.
            '})': '').
            ';'
        );
        
        if($this->options->hidden) $this->addClass('hidden');
        
        $form =
        '<form '.$this->unify().' method="post" '.$action.' enctype="multipart/form-data" autocomplete="'.($this->options->autocomplete? 'on': 'off').'">'.
            '<fieldset>';
                foreach($this->sections as $i => $section){
                    if($i == $this->options->expandedsection){
                        $section->options->expanded = true;
                        $section->options->collapsed = false;
                    }
                    $form .= $section;
                }
                if($this->options->submit && $this->options->showbuttons)
                    $form .=
                    '<footer class="gxui-border gxui-border-top">'.
                        '<input type="submit" name="submit" value="[i18n:submit]">'.
                        '<input type="reset" value="[i18n:reset]">'.
                        '<span class="gxui-spinner-fix"><div class="gxui-spinner-block small ajax hidden gxui-inline-block"></div></span>'.
                    '</footer>';
                
                return $form.
            '</fieldset>'.
        '</form>';
    }
}
?>