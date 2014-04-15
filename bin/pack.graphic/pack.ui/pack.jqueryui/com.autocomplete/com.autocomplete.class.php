<?php
namespace Graphic\UI\jQueryUI;

abstract class AutoComplete extends \Graphic\UI\jQueryUI\Core {
    static public $depends = array(
        'Graphic\UI\jQueryUI\Menu'
    );
    static public function init(){}
}
?>