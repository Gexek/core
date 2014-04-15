<?php
namespace Graphic\UI;

abstract class Scrollbar extends \Graphic\UI\jQueryUI\Core{
    static public $depends = array(
        'Graphic\UI\jQueryUI\Draggable',
        'Graphic\UI\jQueryUI\Mousewheel'
    );
    static public function init(){}
}
?>