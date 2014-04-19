<?php
if($_POST->newpass){
	$Settings->update_key = md5($_POST->newpass);
	$Settings->save();
}

define('UPDATE_SERVER', $Settings->update_server);
define('USERNAME', md5(preg_replace('/http(s)?:\/\//', '', Utils\URL::$base)));
define('PASSWORD', $Settings->update_key);
define('VERSION', $Settings->version);
define('UPDATES_FODLER', Utils\File::$base.'/uploads/__updates');

if(!file_exists(UPDATES_FODLER))
	Utils\File::mkdir(UPDATES_FODLER);

if($_POST->file)
	define('DEST_FILE', UPDATES_FODLER.'/'.$_POST->file);

function getData($patch_id = null, $type = null){
	global $Engine, $DataModule;
	
	$update_object = new stdClass();

	if(is_null($patch_id)){
		$update_object->request = 'check';
		$update_object->version = VERSION;
		$update_object->modules = array();
		foreach ($Engine->plugins->all as $plugin)
			$update_object->modules[] = array('type'=>'plugin','name'=>$plugin->name, 'version'=>$plugin->version);
		foreach ($DataModule->themes->all as $theme)
			$update_object->modules[] = array('type'=>'theme','name'=>$theme->name, 'version'=>$theme->version);
	} else {
		$update_object->request = 'download';
		$update_object->release_id = $patch_id;
		$update_object->version = VERSION;
	}
	
	return 'data='.serialize($update_object);
}

function getPercent($done, $max){
	return round($done/($max/100), 2);
}

function getUpdatesXML(){
	$url = UPDATE_SERVER;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERPWD, USERNAME.':'.PASSWORD);
	curl_setopt($ch, CURLOPT_POSTFIELDS, getData());
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
	$data = curl_exec($ch);
	
	$info = curl_getinfo($ch);
	curl_close($ch);
	
	header('HTTP/1.0 '.$info['http_code']);
	header('Content-type: '.$info['content_type']);
	
	echo $data;
	
	$_SESSION['gxa_updresult'] = $info['http_code'];
	$_SESSION['gxa_updexpire'] = time()+60*30;
	
	exit();
}

function download(){
	//if(!file_exists(DEST_FILE) || $_POST->size!=filesize(DEST_FILE)){
	
	set_time_limit(0);
	$fp = fopen(DEST_FILE, 'w+'); // This is the file where we save the information
	$ch = curl_init(UPDATE_SERVER); // Here is the file we are downloading
	
	curl_setopt($ch, CURLOPT_USERPWD, USERNAME.':'.PASSWORD);
	curl_setopt($ch, CURLOPT_POSTFIELDS, getData($_POST->id, $_POST->type));
	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_exec($ch);
	
	$info = curl_getinfo($ch);
	if($info['http_code'] != 200) @unlink(DEST_FILE);
	header('HTTP/1.0 '.$info['http_code']);
	header('Content-type: '.$info['content_type']);
	
	curl_close($ch);
	fclose($fp);
	//} else 
		//header('HTTP/1.0 200');
}

function install(){
	global $DB;
	if(file_exists(DEST_FILE)){
		$dir = Utils\File::$base;
		if($_POST->type != 'core')
			$dir .= '/'.$_POST->type.'s/'.$_POST->name;
		$setup_file = $dir.'/setup.php';
		
		$zip = new ZipArchive();
		$res = $zip->open(DEST_FILE);
		if($res===TRUE){
			$zip->extractTo($dir);
			$zip->close();
			
			if(file_exists($setup_file)){
				include_once $setup_file;
				unlink($setup_file);
			}
			
			if($_POST->type == 'core'){
				global $Settings;
				$Settings->version = $_POST->ver;
				$Settings->save();
			}
			
			header('HTTP/1.0 200');
		}else
			header('HTTP/1.0 204');
	}else
		header('HTTP/1.0 404');
}

switch($_POST->action){
	case 'check':
		getUpdatesXML();
		break;
	case 'download':
		download();
		break;
	case 'install':
		install();
		break;
	case 'mode':
		$Settings->update_mode = $_POST->mode;
		$Settings->save();
		
		// Update completed ... finalizing now ...
		if(!$Settings->update_mode){
			$_SESSION['gxa_updresult'] = 204;
			$_SESSION['gxa_updexpire'] = time()+60*30;
		}
		break;
}
?>