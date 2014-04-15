<?php
namespace Graphic;

class UploadForm extends Form{
    public function __construct($name, $options = array(), $size = MEDIUM_SIZE){
        extend($options, array(
            'action' => '',
            'submit' => true,
            'ajaxSubmit' => false,
            'preventResend' => true,
            'showButtons' => true,
            'showBorders' => true,
            'ajaxData' => array(),
            'hidden'=>false,
            'maxInput'=>3
        ));
        parent::__construct($name, $options, $size);
        
        $this->options['submit'] = false;
        $this->options['action'] = 'ajax-driver/'.URL::trail(URL::encode($this->options['action']));
    }

    public function __toString(){
        global $i18n, $Viewer;
        $Viewer->bind('onready', '$(".uploadform").uploadform({maxInput: '.$this->options['maxInput'].'});');
        $action = ' action="'.$this->options['action'].'"';
        
        if($this->options['hidden']) $this->addClass('hidden');
        if(!$this->options['showBorders']) $this->addClass('noborder');
        
        $form = '<form '.$this->unify().$action.' method="post" enctype="multipart/form-data" target="'.$this->id.'_upload_frame">'.
        '<input value="" class="upload-target" name="target" type="hidden" />'.
        '<input value="'.Security::getRTSC().'" name="RTSC" type="hidden" />';
        
        foreach($this->options['ajaxData'] as $n=>$v)
            $form .= "<input value=\"$v\" name=\"$n\" type=\"hidden\" />";
        
        $form .= '<iframe name="'.$this->id.'_upload_frame" src="" style="display: none;"></iframe>'.
        ($this->options['showBorders']? '<fieldset class="ui-widget-content ui-corner-all">': '');
        
        $form .= '<p><a class="add-new">+ '.$i18n->add.'</a><p>';
        $form .= '<span class="inputs"></span>';
        
        if($this->options['submit'] && $this->options['showButtons'])
        $form .= '<p class="buttons"><input type="submit" name="submit" value="[i18n:submit]"><input type="reset" value="[i18n:reset]"></p>';
        
        return $form.($this->options['showBorders']? '</fieldset>': '').'</form>';
    }
}
?>