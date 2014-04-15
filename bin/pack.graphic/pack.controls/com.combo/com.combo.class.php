<?php
namespace Graphic\Controls;

class Combo extends Control{
	private $items;
	protected $defualtOptions = array(
			'value' => array(), 'visuality' => 'border', 'multiple' => false, 
			'cols' => 1, 'height' => 160
	);

	public function __construct($name, $options = array()){
		global $DataModule;
		parent::__construct($name, $options);
		$this->clientCalls = array('value' => '$("#'.$this->id.'").val()');
		$this->items = array();
		
		if(is_numeric($this->options->height))
			$this->options->height .= 'px';
	}
	
	public function add($value, $caption, $desc = null){
		$this->items[] = array($value, $caption, $desc);
	}

	protected function generate(){
		global $Viewer, $i18n;
		$Viewer->bind('onready', '$("#'.$this->id.'_panel").__gx_combo({multiple: '.($this->options->multiple? 1: 0).'});');
		$selector =
		(empty($this->label)? '': '<label>'.$this->label.' : </label>').
		'<div class="graphic-controls-combo gxui-flat gxui-light gxui-bordered" id="'.$this->id.'_panel">'.
			'<select name="'.$this->id.($this->options->multiple? '[]': '').'" id="'.$this->id.'" multiple="'.($this->options->multiple? 'true': 'false').'"></select>'.
			'<input type="text" class="input" readonly>'.
			'<span class="handle gxui-popuper gxui-flat gxui-clickable gxui-dark icon-down-open" rel="#'.$this->id.'_popup"></span>'.
		'</div>'.
		'<div class="popup gxui-popup" id="'.$this->id.'_popup" data-coords="css">'.
				($this->options->multiple?
				'<ul class="header gxui-bevel">'.
					'<li><input type="checkbox" /></li>'.
					'<li><input type="radio" id="'.$this->id.'_radio1" name="'.$this->id.'_radio" value="1" checked="true" />'.$i18n->all.'</li>'.
					'<li><input type="radio" id="'.$this->id.'_radio2" name="'.$this->id.'_radio" value="2" />'.$i18n->selected.'</li>'.
					'<li><input type="radio" id="'.$this->id.'_radio3" name="'.$this->id.'_radio" value="3" />'.$i18n->unselected.'</li>'.
				'</ul>': '').
				'<ul class="list" style="height: '.$this->options->height.';">';
					foreach($this->items as $item){
						if(is_array($this->options->value))
							$selected = in_array('every', $this->options->value) || in_array($item[0], $this->options->value);
						else 
							$selected = $item[0] == $this->options->value;
						
						$selector .=
						'<li class="gxui-flat gxui-light gxui-corner gxui-bordered gxui-clickable'.($selected? ' gxui-selected': '').'" style="width: '.round(100/$this->options->cols, 2).'%;" data-value="'.$item[0].'" data-caption="'.$item[1].'" data-desc="'.$item[2].'">'.
							'<label>'.$item[1].'</label>'.(empty($item[2])? '': '<span>'.str_truncate($item[2], 150, '..').'</span>').
						'</li>';
					}
					$selector .=
				'</ul>'.
			'</div>';
		return $selector;
	}
}
?>