<?php
namespace Graphic\UI\jQueryUI;

abstract class Resizable extends \Graphic\UI\jQueryUI\Core {
    static public $depends = array(
        'Graphic\UI\jQueryUI\Widget'
    );
    static public function init(){}
}
?>