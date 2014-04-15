<?php
session_start();

include_once 'config.php';
include_once 'bin/php.extender.php';
include_once 'bin/system.class.php';
include_once 'bin/engine.class.php';

global
    $DB, $DataModule, $Engine, $Viewer, $Settings, 
    $i18n, $Admin, $Firewall;

$Engine = new GXEngine;

try {
	
    $Engine->start();
    $Engine->loadDriver();
    $Engine->stop();

} catch(Exception $e) {
	$i = 0;
	$e_code = $e->getCode();
	
	$type = 'Exception';
	if($e_code>=13000) $type = 'Firewall '.$type;
	else if($e_code>=12000) $type = 'Engine '.$type;
	
	echo '<pre style="direction: ltr; text-align: left; font-size: 11px;">';
    echo '<h3>'.$type.'('.$e_code.'): <span style="color: red;">'.$e->getMessage().'</span></h3>';
    
    if($e_code< 12000)
    	echo '<b>'.(++$i).' '.str_repeat('<', $i).'</b> '.$e->getFile().':'.$e->getLine()."<br />";
    
    $tracebacks = array_filter($e->getTrace(), function($t){ if(isset($t['file'])) return $t; else return false;});
    foreach($tracebacks as $trace) echo '<b>'.(++$i).' '.str_repeat('<', $i).'</b> '.$trace['file'].':'.$trace['line']."<br />";
    
    echo '</pre>';
}

?>