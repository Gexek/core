<?php
namespace Utils;

class String extends CustomString {
    const PREFIX = 0;
    const SUFFIX = 0;
    
    public function __construct($text){
        parent::__construct($text);
    }
    
    public function __get($var){ return $this->$var; }
    
    static public function isJSON($text){
        if(!is_string($text)) return false;
        return json_decode($text) != null;
    }
    
    public function concat($string, $pos = String::SUFFIX){
        switch($pos){
            case String::PREFIX:
                $this->text = $string.$this->text;
                break;
            
            case String::SUFFIX:
                $this->text .= $string;
                break;
        }
    }
    
    public function isRegex($text = ''){
        $text = empty($text)? $this->text: $text;
        return @preg_match($text, '') !== false;
    }
    
    public function replace($search, $replace, $limit = -1, $ignoreCase = false){
        extract($GLOBALS, EXTR_REFS);
        if($this->isRegex($search)){
        	if(is_callable($replace))
        		$this->text = preg_replace_callback($search, $replace, $this->text, $limit, $count);
            else 
        		$this->text = preg_replace($search, $replace, $this->text, $limit, $count);
        } else
            $this->text = str_replace($search, $replace, $this->text, $count);    
        return $count;
    }
    
    public function split($delimiter, $limit = -1, $callback = null){
        $splited = preg_split($delimiter, $this->text, $limit, PREG_SPLIT_NO_EMPTY);
        if(!is_null($callback)) $splited = array_map($callback, $splited);
        return $splited;
    }
    
    public function matches($pattern, $flags = PREG_PATTERN_ORDER, $offset = 0){
        preg_match_all($pattern, $this->text, $matches, $flags, $offset);
        return $matches;
    }
    
    public function match($pattern){
        return preg_match($pattern, $this->text) > 0;
    }
    
    public function toLower(){ $this->text = strtolower($this->text); }
    public function toUpper(){ $this->text = strtoupper($this->text); }
}
?>