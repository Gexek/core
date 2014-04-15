<?php
namespace SysUtils;

abstract class SmartTag extends SysUtil{
    static public function compile($source){
	$source = new \Utils\String($source);
	
	$source->replace('/\[smart:([^\]]+)\]/i', function($m){ return SmartTag::evaluate($m[1]); });
	
	// internationalization
	global $i18n;
	$source->replace('/\[i18n:(date|strtotime)\(([^\)\,]+)(\s?+,\s?+[0-9]+)?\)\]/i', function($m){global $i18n; return $i18n->{$m[1]}($m[2], isset($m[3])?$m[3]:null); });
	$source->replace('/\[i18n:([^\]]+)\]/i', function($m){global $i18n; return isset($i18n->{$m[1]})? $i18n->{$m[1]}: "";});
	
	return $source->__toString();
    }
    
    static public function escape($str){
	return preg_replace(
	    array(
		'/\[smart:([^\]]+)\]/i',
		'/\[i18n:([^\]]+)\]/i'
	    ), array(
		'[_smart_:\1]',
		'[_i18n_:\1]'
	    ), $str
	);
    }
    
    static public function unescape($str){
	return preg_replace(
	    array(
		'/\[_smart_:([^\]]+)\]/i',
		'/\[_i18n_:([^\]]+)\]/i'
	    ), array(
		'[smart:\1]',
		'[i18n:\1]'
	    ), $str
	);
    }
    
    static public function evaluate($tag){
	extract($GLOBALS, EXTR_REFS);
	switch($tag){
	    case 'login_url': return $Firewall->formAction('login'); break;
	    case 'logout_url': return $Firewall->formAction('logout'); break;
	    case 'register_url': return $Firewall->formAction('register'); break;
	    case 'username_field': return $Firewall->client->username; break;
	    case 'password_field': return $Firewall->client->password; break;
	    case 'gx_version': return $Settings->version; break;
	    case 'gx_username': return $Firewall->client->name; break;
	    case 'gx_userid': return $Firewall->client->id; break;
	    default: return eval('return @('.trim($tag, ';').');'); break;
	}
    }
}
?>