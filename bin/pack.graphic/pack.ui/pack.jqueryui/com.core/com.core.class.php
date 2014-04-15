<?php
namespace Graphic\UI\jQueryUI;

abstract class Core extends \Graphic\Graphic {
    public function __construct($name, $options = array(), $size = MEDIUM_SIZE){
        parent::__construct($name, $size);
        $this->options = $options;
    }
}
?>
