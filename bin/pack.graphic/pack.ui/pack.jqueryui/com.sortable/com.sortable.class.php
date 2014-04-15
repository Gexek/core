<?php
namespace Graphic\UI\jQueryUI;

abstract class Sortable extends \Graphic\UI\jQueryUI\Core {
    static public $depends = array(
        'Graphic\UI\jQueryUI\Mouse'
    );
    static public function init(){}
}
?>