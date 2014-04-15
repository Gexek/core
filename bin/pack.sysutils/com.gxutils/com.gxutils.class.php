<?php
namespace SysUtils;

class GXUtils extends SysUtil{
    const APIPATH = 'http://clientapi.gexek.com/';
    
    static public function copyright(){
	global $Settings;
	$url = GXUtils::APIPATH;
	if($_SERVER['HTTP_HOST'] == 'localhost')
	    $url = 'http://localhost/gexek/clientapi/';
	return getContent($url.'copyright/'.$Settings->version.'/');
    }
}
?>