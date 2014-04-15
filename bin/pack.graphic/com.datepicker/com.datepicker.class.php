<?php
namespace Graphic;

global $i18n;
$i18n->importCallender();

class DatePicker extends Graphic{
    public function __construct($name, $options = array(), $size = MEDIUM_SIZE){
        global $i18n;
        extend($options, array(
            'autoshow'           => true,
            'label'              => null,
            'inline'             => false,
            'minDate'            => null,
            'maxDate'            => null,
            'dateFormat'         => 'yy/m/d',
            'defaultDate'        => time(),
            'numberOfMonths'     => 1,
            'enableTime'         => false,
            'disabledDays'       => array(),
            'holidays'           => array()
        ));
        parent::__construct($name, $options, $size);
        
        if(!is_null($this->options['minDate']) && !is_numeric($this->options['minDate']))
            $this->options['minDate'] = $i18n->strtotime($this->options['minDate']);
        if(!is_null($this->options['maxDate']) && !is_numeric($this->options['maxDate']))
            $this->options['maxDate'] = $i18n->strtotime($this->options['maxDate']);
        if(!is_numeric($this->options['defaultDate']))
            $this->options['defaultDate'] = $i18n->strtotime($this->options['defaultDate']);
    }
    
    public function value(){
        return "$(\"#$this->name\").val()";
    }
    
    public function init($options = array()){
        global $Engine, $Viewer, $i18n;
        
        extend($options, $this->options);
        
        $minDate = '';
        if(!is_null($this->options['minDate'])){
            $minDate = 'minDate: $.datepicker.Date('.
            $i18n->date('Y', $this->options['minDate']).','.
            ($i18n->date('m', $this->options['minDate'])-1).','.
            $i18n->date('d', $this->options['minDate']).'),';
        }
        $maxDate = '';
        if(!is_null($this->options['maxDate'])){
            $maxDate = 'maxDate: $.datepicker.Date('.
            $i18n->date('Y', $this->options['maxDate']).','.
            ($i18n->date('m', $this->options['maxDate'])-1).','.
            $i18n->date('d', $this->options['maxDate']).'),';
        }
        
        $defaultDate = '$.datepicker.Date('.
            $i18n->date('Y', $this->options['defaultDate']).','.
            ($i18n->date('m', $this->options['defaultDate'])-1).','.
            $i18n->date('d', $this->options['defaultDate']).')';
            
        return
        '$("#'.$this->id.($this->options['inline']? '_box': '').'").datepicker({'.
            'inline: '.($this->options['inline']? 'true': 'false').','.
            $minDate.$maxDate.
            'defaultDate: '.$defaultDate.','.
            'altField: "#'.$this->id.'",'.
            'altFormat: "'.$this->options['dateFormat'].'",'.
            'dateFormat: "'.$this->options['dateFormat'].'",'.
            'numberOfMonths: '.$this->options['numberOfMonths'].','.
            'constraintInput: true,'.
            'beforeShowDay: function(date){'.
                'if($.inArray(date.getDay(), ['.implode(',', $this->options['holidays']).']) != -1)'.
                    'return [false];'.
                'var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();'.
                'for(i = 0; i < '.$this->id.'_disables.length; i++){'.
                    'if($.inArray(y+"/"+(m+1)+"/"+d, '.$this->id.'_disables) != -1){'.
                        'return [false];'.
                    '}'.
                '}'.
                'return [true];'.
            '}'.
        '})';
    }
    
    public function __toString(){
        global $Engine, $Viewer, $i18n;
            
        $Viewer->bind('ondeclare', 'var '.$this->id.'_disables = ["'.implode('", "', $this->options['disabledDays']).'"];');
        if($this->options['autoshow'])
        $Viewer->bind('onready', $this->init().';');

        if($this->options['inline']){
            $label = '';
            if(!empty($this->options['label'])){
                $Engine->import('Graphic.Label');
                $label = new Label($this->options['label']);
            }
            return $label.'<div id="'.$this->id.'_box"><input name="'.$this->id.'" id="'.$this->id.'" style="display: none;" /></div>';
        } else {
            $Engine->import('Graphic.Controls.TextBox');
            $inp = new TextBox($this->id, '');
            $inp->label = $this->options['label'];
            return $inp->__toString();
        }
    }
}
?>
