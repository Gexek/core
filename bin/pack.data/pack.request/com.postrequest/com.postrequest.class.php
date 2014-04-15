<?php
namespace Data\Request;

class POSTRequest extends RequestObject {
    public function __construct(){
        parent::__construct($_POST);
        unset($_POST);
    }
}
?>