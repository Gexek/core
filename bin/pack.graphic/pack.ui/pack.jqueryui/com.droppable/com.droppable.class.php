<?php
namespace Graphic\UI\jQueryUI;

abstract class Droppable extends \Graphic\UI\jQueryUI\Core {
    static public $depends = array(
        'Graphic\UI\jQueryUI\Mouse',
        'Graphic\UI\jQueryUI\Draggable'
    );
    static public function init(){}
}
?>