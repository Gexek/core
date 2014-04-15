<?php
if(SysUtils\Security::getRTSC() != $_POST->__RTSC) exit;

$Firewall = new SysUtils\GXFirewall;

if($_GET->path)
    include_once $_GET->path;
else {
    if($_POST->__caller){
        list($type, $name) = explode(':', $_POST->__caller);
        switch(strtolower($type)){
            case 'component':
                $path = $Engine->getComPath($name, 'ajax.php');
                if(file_exists($path)) include_once $path;
                break;
            case 'plugin':
                $Engine->plugins->{$name}->import($_POST->__path);
                break;
        }
        
    } else if($_POST->__path){
    	if(file_exists($_POST->__path))
    	include_once $_POST->__path;
    } else if($_POST->sql){
        if($rs = $DB->query($sql)){
            if($_POST->tree)
                $rs = $DB->makeTree($rs, $_POST->tree_key, $_POST->tree_rel, $_POST->tree_init);
            switch(strtolower($_POST->result)){
                case 'xml': $DB->xmlEncode($rs); break;
                case 'json': json_encode($rs->fetchAll()); break;
            }
        }
    }
}
?>