<?php
namespace SysUtils;

class Security extends SysUtil{
    public function __construct(){ }
    
    static public function getRTSC(){
	$sid = session_id();
	return $sid;
    }
    
    static public function randIndex($length = 10, $count = 10){
	$indexes = array(); $i = 0;
	while($i++ < $length) {
	    $indexes[] = $i;
	    shuffle($indexes);
	}
	$res = array(); $i = 0;
	while($i++ < $count) {
	    $res[] = $indexes[$i];
	    shuffle($res);
	}
	return $res;
    }

    static public function prepareInput(&$input){
	if(!get_magic_quotes_gpc())
	    $input = addslashes(htmlspecialchars($input));
	return $input;
    }
    
    static public function repairInput(&$input){
	$input = htmlspecialchars_decode(stripslashes($input));
	return $input;
    }
}
?>