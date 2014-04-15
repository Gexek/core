<?php
use Graphic\jQuery\Core;

class GXViewer extends ClientSide{
    private $source, $contents = array('append'=>array(), 'prepend'=>array()), 
    		$meta = array(), $proccess = array();
    
    // Readonly property
    private $root, $theme;
    
    private $title;
    
    public function __construct(){
		global $Engine, $Settings, $i18n;
		$Engine->import('Utils.Template');
		$this->selectTheme();
		
		$this->meta = array(
			'charset' 			=> array('charset' => 'utf-8'),
			'canonical' 		=> array('rel' => 'canonical', 'href' => ''),
			
			'viewport' 			=> array('name' => 'viewport', 'content' => 'width=device-width'),
			'generator' 		=> array('name' => 'generator', 'content' => 'Gexek'),
						
			'description' 		=> array('name' => 'description', 'content' => ''),
			'keywords' 			=> array('name' => 'keywords', 'content' => ''),
						
			'google-verification'		=> array('name' => 'google-site-verification', 'content' => ''),
			'microsoft-verification' 	=> array('name' => 'msvalidate.01', 'content' => ''),
						
			'og:url' 			=> array('property' => 'og:url', 'content' => ''),
			'og:title' 			=> array('property' => 'og:title', 'content' => ''),
			'og:description' 	=> array('property' => 'og:description', 'content' => ''),
			'og:type' 			=> array('property' => 'og:type', 'content' => ''),
			'og:locale' 		=> array('property' => 'og:locale', 'content' => ''),
			'og:image' 			=> array('property' => 'og:image', 'content' => '')
		);
		
		$this->title = $Settings->site_name;
		
		$this->setMeta('charset', 'utf-8');
		$this->setMeta('description', $Settings->site_desc);
		$this->setMeta('keywords', $Settings->site_keys);
		$this->setMeta('generator', 'Gexek v'.$Settings->version);

		$this->setMeta('og:url', Utils\URL::current());
		$this->setMeta('og:title', $this->title);
		$this->setMeta('og:description', $Settings->site_desc);
		$this->setMeta('og:locale', $i18n->locale);
    }
    
    public function __get($var){
		if(in_array($var, array('theme', 'root', 'title')))
		    return $this->$var;
    }
    
    public function __set($var, $value){
    	if($var == 'title'){
    		return $this->title = $value;
    		$this->setMeta('og:title', $value);
    	}
    }
    
    public function initial(){
		global $Engine;
		$this->import('css/imports.css');

		Graphic\jQuery\Core::init();
		Graphic\UI\GXUI::init();
		
		if($Engine->html_status == 200){
			$Engine->import('bin/js.extender.js');
			Graphic\jQuery\History::init();
			Graphic\jQuery\ResizeEvent::init();
			Graphic\jQuery\GX::init();
			Graphic\UI\Scrollbar::init();
		}
    }
    
    private function createWidget($output){
		if(!isset($output->caption))
		    $output->caption = '';
		
		$fstyles = '';
		$cstyles = '';
		if(empty($output->options->width)) $fstyles .= 'width: auto;';
		else $fstyles .= 'width: '.$output->options->width.';';
		
		if(!empty($output->options->margin))
		    $fstyles .= 'margin: '.$output->options->margin.';';
		    
		if(!empty($output->options->height)){
		    $cstyles .=
			'min-height: '.$output->options->height.';'.
			'max-height: '.$output->options->height.';'.
			'height: '.$output->options->height.';'.
			'overflow-y: auto;';
		}
		
		if($output->options->positioning == 'float'){
		    switch($output->options->position){
			case 'right': $fstyles .= 'float: right;'; break;
			case 'center': $fstyles .= 'margin: 0 auto;'; break;
			case 'left': $fstyles .= 'float: left;'; break;
			case 'absolute':
			    $fstyles .= 'position: absolute;';
			    if(!empty($output->options->top))
					$fstyles .= 'top: '.$output->options->top.';';
			    if(!empty($output->options->right))
					$fstyles .= 'right: '.$output->options->right.';';
			    if(!empty($output->options->bottom))
					$fstyles .= 'bottom: '.$output->options->bottom.';';
			    if(!empty($output->options->left))
					$fstyles .= 'left: '.$output->options->left.';';
			    break;
		    }
		} else
		    $fstyles .= 'clear: both;';
	
		if($output->options->frame != 'noframe'){
		    global $Engine;
		    $root = "$this->root/frames/".$output->options->frame;
		    if(file_exists($root)){
				$Engine->import("$root/index.css");
				$tpl = new Utils\Template\Template("$root/index.tpl");
				$tpl->assign('caption', $output->caption);
				$tpl->assign('content', $output->content);
				$tpl->assign('frame_styles', $fstyles);
				$tpl->assign('content_styles', $cstyles);
				return $tpl;
		    }
		}
		
		return '<div style="'.$fstyles.$cstyles.'">'.$output->content.'</div>';
    }
    
    public function proccessOutput($func){
		$this->proccess[] = $func;
    }
    
    public function assign($name, $value){
		$file = "$this->root/$value";
		if(Utils\File::ext($file) == 'tpl' && file_exists($file)){
		    ob_start(); include_once $file;
		    $this->import(Utils\File::removeExt($value).'.css');
		    $this->import(Utils\File::removeExt($value).'.js');
		    $value = ob_get_clean();
		}
		$this->source->assign($name, $value);
    }
    
    public function setMeta($tag, $value, $attr = 'content'){
    	// If multiple attributes are being set
    	if(is_array($value)){
    		// if tag name is known for Gexek
    		if(isset($this->meta[$tag]))
    			$this->meta[$tag] = array_merge($this->meta[$tag], $value);
    		else 
    			// create it
    			$this->meta[$tag] = $value;
    	} else {
    		// if tag name is known for Gexek
    		if(isset($this->meta[$tag])){
    			// if the metatag has such attribute
    			if(isset($this->meta[$tag][$attr]))
    				$this->meta[$tag][$attr] = $value;
    			// if the metatag has an attribute equal to it's name
    			else if($this->meta[$tag][$tag])
    				$this->meta[$tag][$tag] = $value;
    		} else 
    			// create a new standard meta tag
    			$this->setMeta($tag, array('name'=>$tag, "$attr"=>$value));
    	}
    }

    public function joinMetatags(){
    	$meta_tags_html = '';
    	foreach ($this->meta as $meta){
    		$meta_html = '<meta'; 
    		$has_empty = false;
    		foreach ($meta as $attr => $value){
    			if(empty($value)) $has_empty = true;
    			$meta_html .= " $attr=\"$value\"";
    		}
    		$meta_html .= ' />';
    		
    		if(!$has_empty)	$meta_tags_html .= $meta_html;
    	}
    	return $meta_tags_html;
    }
    
    private function selectTheme(){
		global $Engine;
		if($Engine->adminmode){
		    $this->theme = 'default';
		    $this->root = 'management/themes/default';
		} else {
		    global $Settings;
		    if(count($Settings->themes) > 0)
				$this->theme = $Settings->themes[0];
		    else
				$Engine->sendError(911);
		    $this->root = 'themes/'.$this->theme;
		}
    }
    
    public function append($html){
		$this->contents['append'][] = $html;
    }
    
    public function prepend($html){
		$this->contents['prepend'][] = $html;
    }
    
    public function getContents($loc){
		return implode('', $this->contents[$loc]);
    }
    
    public function blockClient(){
    	global $Engine, $i18n;
    	$login_url = $i18n->locale.'/'.$Engine->pages->authurl.'/login/';
    	$Engine->redirect(Utils\URL::create($login_url, true), false, false);
    }
    
    public function generate(){
		extract($GLOBALS, EXTR_REFS);

		switch($Engine->html_status){
			case 200:
				if(file_exists("$this->root/index.php"))
					include_once "$this->root/index.php";
				else if(file_exists("$this->root/tpl/index.tpl"))
					$this->import("index.tpl");
				else
					$Engine->sendError(912);// Template file missing
				
				if(is_null($this->source)) $Engine->sendError(913); // Invalid template output !
				
				if($Engine->adminmode){
					ob_start();
					include_once 'management/index.php';
					$this->assign('content', ob_get_clean());
					$Settings->site_name .= " > $i18n->admin_panel";
				
					$Admin->autoCheckUpdate();
				} else {
					foreach($Engine->plugins->all as $plugin){
						if($plugin->active){
							if($plugin->implement('IWidgetPlugin')){
								$outputs = array();
								$plugin->output($this->theme, $outputs);
								if(is_array($outputs)){
									foreach($outputs as $out){
										if(isset($out->options->locations[0]))
											$this->assign($out->options->locations[0], $this->createWidget($out));
									}
								}
							} else if($plugin->implement('IHiddenPlugin'))
								$plugin->proccess();
						}
					}
					$this->assign('copyright', SysUtils\GXUtils::copyright());
				}
				break;
				
			case 403:
				$this->import("auth.tpl");
				if(is_null($this->source)) $Engine->sendError(913); // Invalid template output !
				
				$this->assign('firewall_act', strtolower($_GET->action));
				$this->assign('return_url', Utils\URL::create('', true));
				$message = $Firewall->status->message;
				
				if(!empty($message)){
					$act = $Firewall->status->action;
					$this->assign($act.'_message', isset($i18n->$message)? $i18n->$message: $message);
				}
				
				if($Firewall->cache->auth)
				foreach($Firewall->cache->auth as $n => $v)
					$this->assign("cache_$n", $v);
				break;
				
			default:
				$this->import("error.tpl");
				if(is_null($this->source)) $Engine->sendError(913); // Invalid template output !
				
				$this->assign('error_number', $Engine->html_status);
				$this->assign('error_title', $i18n->{'html_error_'.$Engine->html_status});
				$this->assign('error_message', $i18n->{'html_error_'.$Engine->html_status.'_desc'});
				$this->assign('page_url', Utils\URL::current());
				break;
		}
    }
    
    public function escape($str){
		return preg_replace('/\{%(\w+)(:[^%]+)?%\}/i', '{%#\1\2#%}', $str);
    }
    
    public function unescape($str){
		return preg_replace('/\{%#(\w+)(:[^%]+)?#%\}/i', '{%\1\2%}', $str);
    }
    
    public function import($tpl_name){
    	$tpl_file = "$this->root/tpl/$tpl_name";
    	if(Utils\File::ext($tpl_name) == 'tpl' && file_exists($tpl_file)){
    		$this->source = new Utils\Template\GXTemplate($tpl_file, 'copyright');
    		$this->import('css/'.Utils\File::removeExt($tpl_name).'.css');
    		$this->import('js/'.Utils\File::removeExt($tpl_name).'.js');
    	} else {
			global $Engine;
			$Engine->import("$this->root/$tpl_name");
		}
    }
    
    public function _toString(){
		$ba94fc85d8efc5 = new Utils\String($this->source->__toString());
		$b724fc55 = $ba94fc85d8efc5->matches('/\[file:([^\]]+)\]/ie');
		foreach($b724fc55[1] as $b7285b4fd8efc55=>$c7da94fc880eac){
		    ob_start();
		    extract($GLOBALS, EXTR_REFS);
		    include Utils\File::$base."/$c7da94fc880eac";
		    $tag = str_replace('[', '\[', str_replace(']', '\]', $b724fc55[0][$b7285b4fd8efc55]));
		    $ba94fc85d8efc5->replace("($tag)", ob_get_clean());
		}
		
		$ba94fc85d8efc5 = $ba94fc85d8efc5->__toString();
		
		//foreach($this->proccess as $proc) $proc($ba94fc85d8efc5);
		
		return $ba94fc85d8efc5;
    }
}
?>