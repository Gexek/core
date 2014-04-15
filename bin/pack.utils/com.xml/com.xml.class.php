<?php
namespace Utils;

abstract class XML extends Utility{
    static public function sendHeader($charset = 'utf-8'){
        header("Content-type: text/xml; charset=$charset");
    }
    
    static private function _parse($obj){
        $xml = '';
        if(is_string($obj) && String::isJSON($obj))
            $obj = json_decode($obj);
            
        if(is_object($obj))
            $obj = get_object_vars($obj);
            
        if(is_array($obj)){
            foreach($obj as $index=>$value){
                $index = is_numeric($index)? 'item': $index;
                $xml .= "<$index>".XML::_parse($value, $index)."</$index>";
            }
        } else if(is_bool($obj))
            $xml .= (int)$obj;
        else
            $xml .= htmlspecialchars((string)$obj);
            
        return $xml;
    }
    
    static public function parse($input){
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><root>";
        $xml .= XML::_parse($input);
        return $xml."</root>";
    }
}
?>