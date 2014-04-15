<?php
namespace Graphic\UI;

abstract class Scrollbar extends \Graphic\UI\jQueryUI{
    static public $depends = array(
        'Graphic\UI\jQueryUI\Draggable'
    );
    static public function init(){}
}
?>