<?php
namespace Graphic\UI\jQueryUI;

abstract class Menu extends \Graphic\UI\jQueryUI\Core {
    static public $depends = array(
        'Graphic\UI\jQueryUI\Position'
    );
    
    static public function init(){}
}
?>