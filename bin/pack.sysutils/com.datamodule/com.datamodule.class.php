<?php
namespace SysUtils;

class __GXDM_DATASET {
    protected $items = array();
    public function __get($var){
        if($var == 'all') return count($this->items) > 0? $this->items: array();
        else return isset($this->items[$var])? $this->items[$var]: false;
    }
    
    public function count(){
		return count($this->items);
    }
}

class __GXDM_REWITES_DATASET extends __GXDM_DATASET {
	public function __construct(){
		global $DB;
		$rs = $DB->select('rewrites', array(
		    'filter'=>'(bActive OR cOwnerType = "core") AND  "'.\Utils\URL::currentURI().'" REGEXP cRule',
		    'order' => 'CASE WHEN cOwnerType = "core" THEN 1 WHEN cOwnerType = "plugin" THEN 2 ELSE 3 END ASC, nPriority DESC'
		));
		foreach($rs as $rw)
			$this->items[] = (object)array('name' => $rw['cName'], 'url' => $rw['cURL'], 'rule' => $rw['cRule']);
	}
}

class __GXDM_LOCALES_DATASET extends __GXDM_DATASET {
	public function __construct(){
		$locales_root = 'i18n';
		$locales_dirs = \Utils\File::scandir($locales_root, \Utils\File::SCAN_DIRS);
		foreach($locales_dirs as $locale){
			$meta_file = "$locales_root/$locale/meta.txt";
			if(file_exists($meta_file)){
				$meta_data = file_get_contents($meta_file);
				$data = json_decode($meta_data);
				
				if($data !== false)
				$this->items[$locale] = (object)array(
						'name' => $locale, 'caption' => $data->caption, 'icon' => $data->icon				
				);
			}
		}
	}
}

class __GXDM_THEMES_DATASET extends __GXDM_DATASET {
    public function __construct(){
		global $Settings, $Engine, $i18n;
		
		// Initial Plugins
		$themes_root = 'themes';
		$theme_dirs = \Utils\File::scandir($themes_root, \Utils\File::SCAN_DIRS);
		foreach($theme_dirs as $name){
			$meta_file = "$themes_root/$name/index.meta";
			$preview_file = "$themes_root/$name/preview.jpg";
			if(file_exists($meta_file) && file_exists($preview_file)){
				$meta = $this->parseMeta($meta_file);
				$this->items[$name] = (object)array(
					'name' => $name, 'preview' => $preview_file, 
					'version' => $meta['version'], 'map' => $meta['map']
				);
			}
		}
    }

    private function parseMeta($meta_file){
    	$metas = array();
    	$meta_string = \Utils\File::read($meta_file);
    	preg_match_all('/<meta\s*name="([^"]+)"\s*content="([^"]+)"\s*\/?>/', $meta_string, $matches);
    	foreach ($matches[1] as $i => $meta)
    		$metas[$meta] = $matches[2][$i];
    	
    	$metas['map'] = array();
    	preg_match_all('/<area\s*shape="([^"]+)"\s*coords="([^"]+)"\s*(?:alt|name)="([^"]+)"\s*\/?>/', $meta_string, $matches);
    	foreach ($matches[3] as $i => $name)
    		$metas['map'][$name] = array('shape'=>$matches[1][$i], 'coords'=>$matches[2][$i]);
    	
    	extend($metas, array(
    		'version' => '1.0.0'
    	));
    	
    	return $metas;
    }
}

class DataModule extends SysUtil{
    private $items;
    
    public function __construct(){
        global $DB, $Engine, $Admin, $Settings;
	
		$this->items['locales'] = new __GXDM_LOCALES_DATASET;
		$this->items['themes'] = new __GXDM_THEMES_DATASET;
		$this->items['rewrites'] = new __GXDM_REWITES_DATASET;
    }
    
    public function __get($var){
        return isset($this->items[$var])? $this->items[$var]: false;
    }
    
    public function exist($module, $prop, $value){
		if(isset($this->items[$module])){
		    foreach($this->items[$module] as &$m)
			if(!isset($m[$prop]))
			    return false;
			else if($m[$prop] == $value)
			    return true;
		}
		return false;
    }
}
?>