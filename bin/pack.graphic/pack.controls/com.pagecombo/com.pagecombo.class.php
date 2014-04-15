<?php
namespace Graphic\Controls;

class PageCombo extends Combo{

	public function __construct($name, $options = array()){
		parent::__construct($name, $options);
		
		global $DB;
		$rs = $DB->select('rewrites', array('filter' => 'cType = "page"'));
		foreach ($rs as $page){
			$this->add($page['cName'], $page['cTitle'], $page['cName']);
		}
	}
}
?>
