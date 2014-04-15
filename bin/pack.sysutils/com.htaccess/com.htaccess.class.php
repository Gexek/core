<?php
namespace SysUtils;

class Htaccess {
    private $file, $scopeStart, $scopeEnd, $rewrites = array();
    public $scope;
    
    public function __construct($file, $scope){
	$this->file = $file;
	$this->scope = $scope;
	$this->scopeStart = '############'.$this->scope;
	$this->scopeEnd = '###EOF_'.$this->scope;
	
	$this->extractRules();
    }
    
    public function get($url){
	return isset($this->rewrites[$url])? $this->rewrites[$url]: false;
    }
    
    public function getRewrites(){
        return $this->rewrites;
    }
    
    public function exists($url){
	return isset($this->rewrites[$url]);
    }
    
    public function update($url, $rewrite){
        $this->rewrites[$url] = $rewrite;
    }
    
    public function delete($url){
        unset($this->rewrites[$url]);
    }
    
    public function extractRules(){
	$htc = \Utils\File::read($this->file);
	if(!preg_match('/'.$this->scopeStart.'(.*)'.$this->scopeEnd.'/s', $htc)){
	    $htc = str_replace('#-- Custom Rewrites', "#-- Custom Rewrites\r\n\r\n$this->scopeStart\r\n$this->scopeEnd", $htc);
	    \Utils\File::write($this->file, $htc);
	} else {
            preg_match_all("/$this->scopeStart(.*)$this->scopeEnd/s", $htc, $m);
            preg_match_all("/##(.*)\r\nRewriteRule\s\^([^\$]+)\\\$\s(.*)\s\[L\]/", $m[1][0], $m);
            foreach($m[3] as $i => $n)
                $this->update($n, $m[2][$i]);
        }
    }
    
    public function truncate(){
	$this->rewrites = array();
    }
    
    public function save(){
	/*$rules = '';
        asort($this->rewrites, 1);
	foreach($this->rewrites as $url => $rewrite)
	    $rules .=
		'##'.$url."\r\n".
		'RewriteRule ^'.$rewrite.'$ '.$url." [L]\r\n";
	$htc = \Utils\File::read($this->file);
	$htc = preg_replace("/$this->scopeStart(.*)$this->scopeEnd/s", "$this->scopeStart\r\n$rules$this->scopeEnd", $htc);
	//debug($this->rewrites);
	\Utils\File::write($this->file, $htc);*/
    }
}
?>
