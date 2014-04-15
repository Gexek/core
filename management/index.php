<?php
$__admin_inc_file = null; $__p = null;

if(isset($_GET->values[0])){
	$ignoreURI = $_GET->values[0];
    switch($_GET->values[0]){
        case 'pages':
            $Admin->pushNav($i18n->pages);
            $__admin_inc_file = 'management/pages.php';
            break;
        case 'settings':
            $Admin->pushNav($i18n->site_settings);
            $__admin_inc_file = 'management/settings/index.php';
            break;
        case 'security':
        	if(!$Firewall->client->permitted('manage', 'security'))
        		$Viewer->blockClient();
        	
            $Admin->pushNav($i18n->security_center);
            $__admin_inc_file = 'management/security/index.php';
            break;
        case 'update':
            $Admin->pushNav($i18n->update_center);
            $__admin_inc_file = 'management/update/index.php';
            break;
        default:
            if($Engine->plugins->registered($_GET->values[0])){
            	if(!$Firewall->client->permitted('manage', 'plugin', $_GET->values[0]))
            		$Viewer->blockClient();
            	
                $p = $Engine->plugins->{$_GET->values[0]};
                $Admin->pushNav($i18n->{$p->name.'_plugin_name'});
                if(isset($_GET->values[1])){
                    $items = $p->getManageItems();
                    if(isset($items[$_GET->values[1]][1])){
                        $caption = $i18n->{$items[$_GET->values[1]][1]};
                        $Admin->pushNav($caption);
                    }
                    $__admin_inc_file = $items[$_GET->values[1]][0];
                    $__p = $p;
                    $ignoreURI .= '/'.$_GET->values[1];
                }
            }
            break;
    }
    $_GET->ignoreURI($ignoreURI, Data\Request\GETRequest::IGNORE_VALUE);
} else {
    $__admin_inc_file = 'management/dashboard.php';
    $Viewer->import('css/dashboard.css');
}

if(!is_null($__admin_inc_file)){
	if(!is_null($__p))
		$__p->import($__admin_inc_file);
	else
		include_once $__admin_inc_file;
}

$Admin->drawNavs();
?>