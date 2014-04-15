<?php
namespace Graphic\UI\jQueryUI;

abstract class Widget extends \Graphic\UI\jQueryUI\Core {
    static public $depends = array(
        'Graphic\UI\jQueryUI\Core'
    );
    static public function init(){}
}
?>