<?php
defined('GX_PAGE_RX') | define('GX_PAGE_RX', '([^/;]+)');

class EnginePages extends ServerSide {
	private $pages = array(), $pages_meta = array(), $changed = array();

	public function __construct(){
		$this->pages_meta = array(
			'authurl' => array('values' => array('authmode'=>'true'), 'rx' => array('action' => '([a-z0-9]+)/')),
			'adminurl' => array('values' => array('adminmode'=>'true'), 'rx' => array('rewrite' => '((.*)/)?'))
		);
		
		global $DB;
		$rs = $DB->select('rewrites', array('filter' => "cType = 'page' AND cOwnerType = 'core'", 'order'=>'nPriority DESC'));
		foreach($rs as $i => $row){
			$pagename = $row['cName'];
			
			if(isset($this->pages_meta[$pagename])){
				preg_match_all('#&([a-zA-Z0-9_\/\-]+)=\$[0-9]+#', $row['cURL'], $params);
				foreach ($params[1] as $param){
					$rx = $this->pages_meta[$pagename]['rx'][$param];
					$row['cRule'] = preg_replace('#/('.preg_quote($rx).')#', '', $row['cRule'], 1);
				}
	
				$this->pages[$pagename] = array(
						'rule' => trim($row['cRule'], '/'), 'url' => $row['cURL'],
						'title' => $row['cTitle'], 'desc' => $row['cDesc'], 'keywords' => $row['cKeywords']
				);
			}
		}
	}

	public function set($name, $url, $title = '', $desc = '', $keywords = ''){
		if(!empty($url) && isset($this->pages_meta[$name])){
			$url = trim($url, '/');
			$rule = $url;

			$pageurl = '';
			foreach ($this->pages_meta[$name]['values'] as $var => $value)
				$pageurl .= "&$var=$value";
			
			preg_match_all('#/\{([^}/]+)\}#', $url, $params);
			foreach ($params[1] as $i => $p){
				$rx = $this->pages_meta[$name]['rx'][$p];
				$rule = str_ireplace('/{'.$p.'}', '/'.$rx, $rule);
				$pageurl .= '&'.$p.'=$'.($i+1);
			}

			$this->pages[$name] = array('rule' => $rule, 'url' => trim($pageurl, '&'), 'title' => $title, 'desc' => $desc, 'keywords' => $keywords);
			$this->changed[$name] = true;

			return true;
		} else
			return false;
	}

	public function __get($var){
		if($var == 'changed') return count($this->changed) > 0;
		return isset($this->pages[$var])? $this->pages[$var]['rule']: false;
	}

	public function save(){
		global $DB;
		foreach($this->changed as $name => $state){
			$page = $this->pages[$name];
			$rs = $DB->query('SELECT cRule FROM rewrites WHERE cRule = "'.$page['rule'].'" AND NOT(cName = "'.$name.'")');
			if($rs->rowCount() > 0)
				throw new Exception('URL "'.$page['rule'].'" is already reserved and cannot be set for "'.$name.'"!', 12004);
			else 
				$DB->replace('rewrites', array(
						'cName'=>$name, 'cRule'=>$page['rule'], 'cURL'=>$page['url'], 'cOwnerType' => 'core', 'cOwnerName' => 'core',
						'cTitle'=>$page['title'], 'cDesc'=>$page['desc'], 'cKeywords'=>$page['keywords']
				));
		}
		$this->changed = array();
	}
}


class EnginePlugins {
    private $items = array();
    
    public function __construct(){
        global $Settings, $Engine, $i18n;
        
        // Initial Plugins
        $plugins_root = 'plugins'; $plugins = (object)$Settings->plugins;
        $plugins_dirs = \Utils\File::scandir($plugins_root, \Utils\File::SCAN_DIRS);
        foreach($plugins_dirs as $name){
            if(!isset($plugins->$name))
                $plugins->$name = (object)array('name' => $name, 'active' => 1);
            
            $i18n->localize('plugins/'.$name);
            
            $extender = "$plugins_root/$name/extender.class.php";
            if($plugins->$name->active && file_exists($extender)){
                include_once $extender;
                $classes = Utils\File::get_php_classes($extender);
                foreach($classes as $class){
                    $name = $plugins->$name->name;
                    $this->items[$name] = new $class($plugins->$name);
                }
            }
        }
        
        $Settings->plugins = $plugins;
        $Settings->save();
    }
    
    public function registered($name = null){
        return is_null($name)? count($this->items) > 0: isset($this->items[$name]);
    }
    
    public function __get($var){
        if($var == 'all') return $this->items;
        else if($var == 'names') return array_keys($this->items);
        else return isset($this->items[$var])? $this->items[$var]: false;
    }
}


/**
 * Engine class
 *
 * Events :
 *       onbeforeimport(params[request, &allow])
 *       onimport(params[package, class, filename])
 */
class GXEngine extends ServerSide {
    const VERSION = '1.6.0';
    
    const PACKFILE = '.PACKAGE';
    
    private $root, $PACKAGES = array();
    private $com_root = 'bin/';
    private $restrictedFiles;
    private $drivers = array('ajax', 'auth', 'html', 'static');
    
    private $readonly = array('started', 'plugins', 'driver', 'interface', 'adminmode', 'authmode', 'development', 'pages', 'html_status');
    // readonly properties
    private $started = false;
    private $plugins = null;
    private $pages = null;
    private $driver = 'html';
    private $authmode = false;
    private $adminmode = false;
    private $development = false;
    private $html_status = 200;
    
    private $output_filters = array(
        'locale' => null,
        'theme' => null,
        'homepage' => false,
        'page' => false
    );
    
    public function __construct(){
        parent::__construct();
        
        // root is the same location that engine class exists
        $this->root = $this->realpath(dirname(__FILE__));
        
        // Files restricted by engine to be import manually
        $this->restricted = array(
            'package' => array(
                
            ), 'class' => array(
                "$this->root/system.class.php", // System class
                "$this->root/engine.class.php", // Engine class
            )
        );
    }
    
    public function __get($var){
        if(in_array($var, $this->readonly))
            return $this->$var;
    }
    public function __isset($var){
        if(in_array($var, $this->readonly))
            return isset($this->$var);
    }
    
    public function getOutputFilters(){ 
    	global $DataModule;
    	global $Viewer, $i18n;
    	$this->output_filters['locale'] = $i18n->locale;
    	if(!is_null($Viewer))
    		$this->output_filters['theme'] = $Viewer->theme;
    	
    	if($_GET->page){
    		$this->output_filters['homepage'] = false;
    		$this->output_filters['page'] = $_GET->page;
    	} else {
    		$uri = Utils\URL::currentURI();
    		$this->output_filters['homepage'] = empty($uri);
    	}
    	
    	$this->output_filters['homepage'] =
    	$this->output_filters['homepage'] && !$_GET->adminmode;
    	
    	return $this->output_filters; 
    }
    // Deprecated! use getOutputFilters instead.
    public function getOP(){ return $this->getOutputFilters(); }
    
    /**
     * function will initial and prerequisite system to be run
     */
    public function start($lite = false){
        extract($GLOBALS, EXTR_REFS);
        
        // Set autoload function to use GXEngine::__autoload function
        spl_autoload_register(array($this, '__autoload'));
        
        //set_error_handler(array($this, '__error_handler' ));
        Utils\File::init();
        Utils\URL::init();
        $_GET = new Data\Request\GETRequest;
        $_POST = new Data\Request\POSTRequest;
        $this->connect();
        $Settings = new SysUtils\GXSettings;

        if(!$lite){
        	$i18n = new SysUtils\I18N($Settings->locale);
        	$DataModule = new SysUtils\DataModule;
        	
            $_GET->rewrite();

            $this->driver = $_GET->driver;
            $this->driver = empty($this->driver)? 'html': $this->driver;
            
            $this->development = $_SERVER["SERVER_NAME"] == 'localhost';
            
            $this->prepareResources();
            
            $this->plugins = new EnginePlugins;
            $this->pages = new EnginePages;
            
            if($this->driver == 'html'){
                $this->authmode = $_GET->authmode? true: false;
                $this->adminmode = $_GET->adminmode? true: false;
                if($this->adminmode)
                    $Admin = new SysUtils\Administration;
            }
            
            $_GET->ignore();
            $this->join('onstart');
            ob_start();
            $this->started = true;
        }
    }
    
    public function stop() {
        global $Settings;
        $output_buffer = array('content'=>ob_get_clean());
        // Trigerring event handler on get buffer
        $this->join('ongetbuffer', $output_buffer);
        if($Settings->caching){
            $expire_time = time() + (60 * 60 * 24 * $Settings->cache_priod);
            header("Cache-Control: must-revalidate");
            header('Expires: '.gmdate('D, d M Y H:i:s', $expire_time).' GMT');
        } else {
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        }
    
        header("ETag: ".md5(Utils\URL::current()));
        
        $output = '';
        if($Settings->compression)
            $output = ob_gzhandler($output_buffer['content'], 5);
        
        if(empty($output)) $output = $output_buffer['content'];
        
        echo $output;
    }
    
    private function validClassToRegister($class){
        return in_array(get_parent_class($class), array('Plugin')) && (
            $this->adminmode || (
                in_array('IWidgetPlugin', class_implements($class)) ||
                in_array('IHybridPlugin', class_implements($class)) ||
                in_array('IHiddenPlugin', class_implements($class))
            )
        );
    }
    /**
     * function for connection to database
     * $DB object should be used in global scop when connected
     */
    public function connect(){
        try {
            global $DB;
            $DB = new Data\DB\Connection('mysql:dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS);
            $DB->exec('SET NAMES UTF8');
            $DB->prefix = DB_PREFIX;
            $DB->dbname = DB_NAME;
        } catch (PDOException $e) {
            die('Connection failed: '.$e->getMessage());
        }
    }
    
    /* works as the same PHP internal realpath() function
     * externally converts an address to unix format
     * to be portable in both Windows and Unix base operation systems
     */
    public function realpath($path){
        $path = str_replace('\\', '/', realpath($path));
        $path = rtrim($path, '/');
        return $path;
    }
    
    /**
     * Function to check if a package or class is restricted to be import directly
     */
    private function restriced($type, $path){
        if(!isset($this->restricted[$type])) return false;
        return in_array($this->realpath($path), $this->restricted[$type]);
    }
    
    /*
     * Imports packages, classes and resources
     * using import function will guaranty your application
     * to use fewer resources and decrease your website loadtime
     *
     * it could be use to import all PHP Packages, Stylesheets and Scripts
     */
    public function import($resource){
        
        $eparams = array('request'=>$resource, 'allow'=>true);
        // Trigerring event handler on request a library
        $this->join('onimportrequest', $eparams);
        // if event handler allows to import this libs
        if($eparams['allow']){
            $res_session = 'GX';
            // request is a javascript or stylesheet
            switch(Utils\File::ext($resource)){
                case 'js': $res_session .= '_JS_'; break;
                case 'css': $res_session .= '_CSS_'; break;
            }
            $res_session .= Utils\URL::id();
            if(isset($_SESSION[$res_session]) && !in_array($resource, $_SESSION[$res_session]))
                if(file_exists($resource)) $_SESSION[$res_session][] = $resource;
        }
    }
    
    public function getComPath($com, $file = null){
        $packs = explode('\\', $com);
        $class = end($packs);
        array_splice($packs, -1);
        
        $path = $this->com_root;
        foreach($packs as $pack)
            $path .= 'pack.'.$pack.'/';
        $path = trim($path, '/').'/com.'.$class;
        $path .= is_null($file)? '': "/com.$class.$file";
        return strtolower($path);
    }
    
    private function uiRquested($packs){
        return strtolower($packs[0]) == 'graphic';
        /*return strtolower($packs[0]) == 'graphic' && (
            isset($packs[1]) && strtolower($packs[1]) == 'ui'
        );*/
    }
    private function __autoload($request){
        if(!class_exists($request)){
            $packs = explode('\\', $request);
            $class = end($packs);
            array_splice($packs, -1);
            
            $path = $this->com_root;
            $packStep = '\\';
            foreach($packs as $pack){
                $packStep .= $pack.'\\';
                $path .= strtolower('pack.'.$pack.'/');
                if(file_exists($path)) {
                    $packfile = $path.'.PACKAGE';
                    if(file_exists($packfile)) include_once $packfile;
                } else
                    throw new Exception('Gexek could not found "'.trim($packStep, '\\').'" package', 12001);
            }
            $path = trim($path, '/');
            
            $class_folder = strtolower("$path/com.$class");
            
            if(!file_exists($class_folder))
                throw new Exception('Gexek could not found "'.strtolower($request).'" class', 12002);
                
            $class_file = strtolower("$class_folder/com.$class.class.php");
            if(file_exists($class_file)) include_once $class_file;
            
            if(isset($request::$depends)){
                foreach($request::$depends as $dependency)
                    $this->__autoload($dependency);
            }
            
            if($this->started && $this->driver == 'html'){
                global $Viewer;
                $this->import(strtolower("$class_folder/com.$class.script.js"));
                $this->import(strtolower("$class_folder/com.$class.style.css"));
                if($this->uiRquested($packs)){
                    $extFolder = strtolower(implode('/', $packs));
                    $Viewer->import(strtolower("$extFolder/$class.css"));
                }
            }
        }
    }
    
    // Loading drivers
    public function loadDriver(){
        extract($GLOBALS, EXTR_REFS);
        $driver_file = "drivers/drv.$this->driver.php"; 
        if(!file_exists($driver_file))
            throw new Exception("The driver \"$this->driver\" is not installed on Gexek !", 12002);
        else {
            $params = array('driver' => $this->driver);
            $this->join('ondriverload', $params);
            include_once $driver_file;
        }
    }
    
    private function prepareResources(){
        if($this->driver == 'html'){
            $_SESSION['GX_JS_'.Utils\URL::id()] = array();
            $_SESSION['GX_CSS_'.Utils\URL::id()] = array();
            // Most odd error i got ever, apache craches if i delete this line
        }
    }
    public function createResourceLink($type){
		global $i18n;
		$reqid = strtoupper($type).'.'.Utils\URL::id();
		return Utils\URL::create('static/'.$i18n->locale.'/r-'.$reqid, true);
    }
    public function parseResourceRequest($request){
        $chunks = explode('.', $request);
        return array('type' => strtolower($chunks[0]), 'id' => $chunks[1]);
    }
    
    public function sendError($error_num){
         global $i18n; die($i18n->{"syserror_$error_num"});
    }
    
	public function setStatus($code, $message = null){
    	$this->html_status = $code;
    	if(is_null($message))
    		switch ($code){
    			case 200: $message = 'OK'; break;
    			case 301: $message = 'Moved Permanently'; break;
    			case 307: $message = 'Temporary Redirect'; break;
    			case 400: $message = 'Bad Request'; break;
    			case 401: $message = 'Unauthorized'; break;
    			case 403: $message = 'Forbidden'; break;
    			case 404: $message = 'Not Found'; break;
    			case 408: $message = 'Request Timeout'; break;
    			default:  $message = 'Unknown'; break;
    		}
        header('HTTP/1.1 '.$code.' '.$message);
    }
    
    public function redirect($url, $permanent = false, $setstatus = true){
        if($setstatus)
        	$this->setStatus($permanent? 301: 307);
        header("Location: $url");
        exit;
    }
    
    public function uniqid(){
		return md5(uniqid(rand(), true));
    }
    
    public function __error_handler($severity, $message, $filename, $lineno) {
        if (error_reporting() == 0) return;
        if (error_reporting() & $severity)
            throw new ErrorException($message, 0, $severity, $filename, $lineno);
    }

}
?>