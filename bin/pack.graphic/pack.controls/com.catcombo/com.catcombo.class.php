<?php
namespace Graphic\Controls;

class CatCombo extends Combo{

	public function __construct($name, $options = array()){
		parent::__construct($name, $options);
		
		global $DB;
		$rs = $DB->select('categories');
		foreach ($rs as $page){
			$value = preg_replace('/^\(\(\(\[a-z\]\{2\}\)_\(\[A-Z\]\{2\}\)\)\/\)\?/', '', $page['cRule']);
			$this->add($value, $page['cTitle'], $value);
		}
	}
}
?>
