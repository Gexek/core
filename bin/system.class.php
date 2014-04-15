<?php
abstract class System{
	public static $depends = array();
	protected $events = array();

	public function __construct(){}

	final public function implement($intfs, $operator = 'and'){
		$intfs = (array)$intfs;
		$operator = in_array($operator, array('and', 'or'))? $operator: 'and';
		$ci = class_implements(get_class($this));
		
		switch(strtolower($operator)){
			case 'and':
				foreach($intfs as $i)
					if(!in_array($i, $ci))
						return false;
				return true;
				break;
			
			case 'or':
				foreach($intfs as $i)
					if(in_array($i, $ci))
						return true;
				return false;
				break;
		}
	}
}
abstract class ServerSide extends System{

	public function bind($events, $function){
		if(is_string($events)){
			$events = explode(' ', $events);
			foreach($events as $event){
				if(!isset($this->events[$event]))
					$this->events[$event] = array();
				if(!in_array($function, $this->events[$event]))
					$this->events[$event][] = $function;
			}
		}
	}

	public function binded($events = null){
		if(count($this->events)>0){
			if(is_null($events))
				return count($this->events)>0;
			else{
				$events = explode(' ', $events);
				foreach($events as $e)
					if(key_exists($e, $this->events))
						return true;
				return false;
			}
		}else
			return false;
	}

	public function join($event, &$params = array()){
		if(isset($this->events[$event])){
			$eParams = new stdClass();
			$eParams->event = $event;
			$eParams->eventIndex = 0;
			foreach($params as $n => &$p)
				$eParams->$n = &$p;
			
			foreach($this->events[$event] as $e)
				$e($eParams);
		}
	}

	public function unbind($events = null){
		if(is_null($events))
			$this->events = array();
		else{
			$events = explode(' ', $events);
			foreach($events as $e)
				unset($this->events[$e]);
		}
	}
}
abstract class ClientSide extends System{
	protected $clientCalls = array();

	final public function bind(){
		$args = func_get_args();
		if(count($args)>1){
			$events = $args[0];
			$args = array_slice($args, 1);
			if(is_string($events)){
				$events = explode(' ', $events);
				foreach($events as $event){
					foreach($args as $command){
						if(!isset($this->events[$event]))
							$this->events[$event] = array();
						
						if(!$this->binded($event, $command)){
							$command = $this->fixJS($command, 'command');
							array_push($this->events[$event], $command);
						}
					}
				}
			}
		}
		return $this;
	}

	public function binded($events = null, $command = null){
		if(is_null($events))
			return count($this->events)>0;
		else{
			$events = explode(' ', $events);
			foreach($events as $e)
				if(key_exists($e, $this->events)){
					if(!is_null($command))
						return in_multiarray($command, $this->events);
					return true;
				}
			return false;
		}
	}

	public function join($event = null){
		if(is_null($event)){
			$event_array = array();
			foreach($this->events as $name => $e){
				$event_str = $name.'="'.implode($this->events[$name]).'"';
				array_push($event_array, $event_str.'"');
			}
			return implode(' ', $event_array);
		}else if(isset($this->events[$event]))
			return implode('', $this->events[$event]);
		return '';
	}

	public function unbind($events = null){
		if(is_null($events))
			$this->events = array();
		else{
			$events = explode(' ', $events);
			foreach($events as $e)
				unset($this->events[$e]);
		}
	}

	public function call($callname, $args = null, $usage = 'command'){
		if(!isset($this->clientCalls[$callname]))
			$call = '';
		else{
			if(!is_null($args)){
				if(!is_array($args))
					$args = array($args);
			}else
				$args = array();
			
			$call = $this->clientCalls[$callname];
			if(is_object($call))
				$call = $call($this, $args);
			$call = $this->fixJS($call, $usage);
		}
		return $call;
	}

	static public function js_call(&$str){
		if(preg_match('/(\w+):(.*)/i', $str)){
			$chunks = explode(':', $str, 2);
			if($chunks[0]=='js'){
				$str = $chunks[1];
				return true;
			}
		}
		
		return preg_match('/^\$\(/', $str)||preg_match('/^\$./', $str)||preg_match('/^([a-zA-Z0-9_]+)([\.a-zA-Z0-9]+)\(([^\)]+)?\);?$/', $str)||preg_match('/^new\s/', $str);
	}

	public function fixJS($string, $usage = 'value'){
		if(is_array($string)){
			foreach($string as &$v)
				$v = $this->fixJS($v, $usage);
		}else{
			switch($usage){
				case 'value':
					if(is_numeric($string)||ClientSide::js_call($string))
						$string = $string;
					else if(is_string($string))
						$string = "'$string'";
					else if(is_bool($string))
						$string = $string? 'true': 'false';
					else
						$string = 'null';
					$string = trim($string, ';');
					break;
				
				case 'command':
					$string = trim($string, ';').';';
					break;
			}
		}
		return $string;
	}
}
abstract class Plugin extends ServerSide{
	const PLUGIN_VERSION = '1.0.0';
	
	private $name, $active, $folder, $settings, $pages;
	protected $defaultSettings = array(), $settingsFilter = '';

	protected function _create(){}

	protected function _initial(){}

	final public function __construct($data){
		global $i18n;
		$this->name = $data->name;
		$this->active = $data->active;
		$this->folder = Utils\File::$base."/plugins/$this->name";
		
		$this->_initial();
		$this->settings = new PluginSettings($this->name, $this->defaultSettings);
		$this->pages = new PluginPages($this->name);
		$this->_create();
	}
	
	public function __get($var){
		if($var == 'version')
			return static::PLUGIN_VERSION;
		return $this->$var;
	}

	public function pathTo($path, $absolute = false, $trail = false){
		return $this->linkTo($path, $absolute, $trail);
	}

	public function linkTo($url, $absolute = false, $trail = false){
		$ext = Utils\File::ext($url);
		$root = 'plugins/'.$this->name;
		if(in_array($ext, array('php', 'js', 'css'))){
			$url = "$root/$url";
			if($trail)
				$url = Utils\File::trail($url);
			$base = Utils\File::$base;
		}else if(in_array($ext, array('html', 'htm'))){
			$url = "$root/$url";
			$base = Utils\URL::$base;
		}else{
			$url = Utils\URL::create("$root/$url");
			$base = Utils\URL::$base;
		}
		return $absolute? $base.'/'.$url: $url;
	}

	public function pluginArea(){
		return $_GET->plugin==$this->name;
	}

	public function itsArea(){
		return $this->pluginArea();
	}

	public function linkToContent($query, $absolute = false){
		global $i18n;
		$url = $i18n->locale.'/'.'plugin-'.$this->name.'/';
		if($absolute)
			$url = Utils\URL::$base.'/'.$url;
		return $url.Utils\URL::createQuery($query).'/';
	}

	public function linkToAdmin($url, $absolute = false){
		global $Admin;
		return $Admin->linkTo($this->name.'/'.$url, $absolute);
	}

	public function import($file){
		if(Utils\File::ext($file)=='php'){
			extract($GLOBALS, EXTR_REFS);
			include_once $this->linkTo($file, true);
		}else{
			global $Engine;
			$Engine->import($this->linkTo($file, false));
		}
	}
}
class PluginPages extends ServerSide{
	private $pages = array(), $pluginname, $changed = array();

	public function __construct($pluginname){
		global $DB;
		$this->pluginname = $pluginname;
		$rs = $DB->select('rewrites', array('filter' => "cType = 'page' AND cOwnerType = 'plugin' AND cOwnerName = '$this->pluginname'", 'order' => 'nPriority DESC'));
		foreach($rs as $i => $row){
			preg_match_all('#&([a-zA-Z0-9_\/\-]+)=\$[0-9]+#', $row['cURL'], $params);
			
			foreach($params[1] as $param)
				$row['cRule'] = preg_replace('#('.preg_quote(GX_PAGE_RX).')#', '{'.$param.'}', $row['cRule'], 1);
			
			$this->pages[$row['cName']] = array('rule' => trim($row['cRule'], '/'), 'url' => $row['cURL'], 'title' => $row['cTitle'], 'desc' => $row['cDesc'], 'keywords' => $row['cKeywords']);
		}
	}

	public function create($name, $url, $title = '', $desc = '', $keywords = ''){
		if(!empty($url)){
			$url = trim($url, '/');
			$rule = $url;
			
			$pageurl = "page=$name&plugin=$this->pluginname";
			preg_match_all('#/\{([^}/]+)\}#', $url, $params);
			foreach($params[1] as $i => $p){
				$rule = str_ireplace('/{'.$p.'}', '/'.GX_PAGE_RX, $rule);
				$pageurl .= '&'.$p.'=$'.($i+1);
			}
			
			$this->pages[$name] = array('rule' => $rule.'/', 'url' => $pageurl, 'title' => $title, 'desc' => $desc, 'keywords' => $keywords);
			$this->changed[$name] = true;
			
			return true;
		}else
			return false;
	}

	public function __get($var){
		if($var=='changed')
			return count($this->changed)>0;
		return isset($this->pages[$var])? $this->pages[$var]['rule']: false;
	}

	public function save(){
		global $DB;
		foreach($this->changed as $name => $state){
			$page = $this->pages[$name];
			$rs = $DB->query("SELECT cRule FROM rewrites WHERE cType = 'page' AND cOwnerType = 'plugin' AND cOwnerName = '$this->pluginname' AND cRule = '$page[rule]' AND NOT(cName = '$name')");
			if($rs->rowCount()>0)
				debug('URL Pattern <br>'.$page['rule'].'</b> is already reserved !');
			else
				$DB->replace('rewrites', array('cName' => $name, 'cRule' => $page['rule'], 'cURL' => $page['url'], 'cTitle' => $page['title'], 'cDesc' => $page['desc'], 'cKeywords' => $page['keywords'], 'cType' => 'page', 'cOwnerType' => 'plugin', 'cOwnerName' => $this->pluginname));
		}
		$this->changed = array();
	}
}
class PluginSettings extends ServerSide{
	private $items, $db, $pluginname, $defaults, $loaded = false;

	public function __construct($pluginname, $defaults = array()){
		global $DB;
		$this->db = &$DB;
		$this->pluginname = $pluginname;
		$this->items = $defaults;
	}

	public function load($filter = ''){
		$rs = $this->db->select('settings', array('filter' => "cType = 'plugin' AND cName = '$this->pluginname' $filter"));
		
		foreach($rs as $row){
			$name = $row['cVariable'];
			$val = stripslashes($row['cValue']);
			
			if(($obj = json_decode($val))!==false && !is_null($obj)){
				$def = $this->items[$name];
				$this->items[$name] = $obj;
				extend($this->items[$name], $def);
			}else
				$this->items[$name] = stripslashes($val);
		}
		$this->loaded = true;
	}

	public function __get($var){
		if(!$this->loaded)
			$this->load();
		return isset($this->items[$var])? $this->items[$var]: false;
	}

	public function __set($var, $value){
		$this->items[$var] = $value;
	}

	public function __isset($var){
		return isset($this->items[$var]);
	}

	public function save(){
		foreach($this->items as $name => $value){
			if(is_object($value) || is_array($value))
				$value = addslashes(json_encode($value));
			$values = array('cType' => 'plugin', 'cName' => $this->pluginname, 'cVariable' => $name, 'cValue' => $value);
			$this->db->replace('settings', $values);
		}
	}
}
class SuperClosure{
	protected $closure = NULL;
	protected $reflection = NULL;
	protected $code = NULL;
	protected $used_variables = array();

	public function __construct($function){
		if(!$function instanceof Closure)
			throw new InvalidArgumentException();
		
		$this->closure = $function;
		$this->reflection = new ReflectionFunction($function);
		$this->code = $this->_fetchCode();
		$this->used_variables = $this->_fetchUsedVariables();
	}

	public function __invoke(){
		$args = func_get_args();
		return $this->reflection->invokeArgs($args);
	}

	public function getClosure(){
		return $this->closure;
	}

	protected function _fetchCode(){
		// Open file and seek to the first line of the closure
		$file = new SplFileObject($this->reflection->getFileName());
		$file->seek($this->reflection->getStartLine()-1);
		
		// Retrieve all of the lines that contain code for the closure
		$code = '';
		while($file->key()<$this->reflection->getEndLine()){
			$code .= $file->current();
			$file->next();
		}
		
		// Only keep the code defining that closure
		$begin = strpos($code, 'function');
		$end = strrpos($code, '}');
		$code = substr($code, $begin, $end-$begin+1);
		
		return $code;
	}

	public function getCode(){
		return $this->code;
	}

	public function getParameters(){
		return $this->reflection->getParameters();
	}

	protected function _fetchUsedVariables(){
		// Make sure the use construct is actually used
		$use_index = stripos($this->code, 'use');
		if(!$use_index)
			return array();
			
			// Get the names of the variables inside the use statement
		$begin = strpos($this->code, '(', $use_index)+1;
		$end = strpos($this->code, ')', $begin);
		$vars = explode(',', substr($this->code, $begin, $end-$begin));
		
		// Get the static variables of the function via reflection
		$static_vars = $this->reflection->getStaticVariables();
		
		// Only keep the variables that appeared in both sets
		$used_vars = array();
		foreach($vars as $var){
			$var = trim($var, ' $&amp;');
			$used_vars[$var] = $static_vars[$var];
		}
		
		return $used_vars;
	}

	public function getUsedVariables(){
		return $this->used_variables;
	}

	public function __sleep(){
		return array('code', 'used_variables');
	}

	public function __wakeup(){
		extract($this->used_variables);
		
		eval('$_function = '.$this->code.';');
		if(isset($_function) and $_function instanceof Closure){
			$this->closure = $_function;
			$this->reflection = new ReflectionFunction($_function);
		}else
			throw new Exception();
	}
}
class StandardOutput{
	public $caption, $content, $options;

	public function __construct($caption = '', $content = '', $options = array()){
		global $i18n;
		$this->caption = $caption;
		$this->content = $content;
		$options = (array)$options;
		extend($options, array('frame' => 'noframe', 'width' => 0, 'height' => 0, 'positioning' => 'static', 'position' => $i18n->align, 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0, 'locations' => array('content')));
		$this->options = (object)$options;
	}
}

/* Plugins with unlinkable contents in frontend */
interface IWidgetPlugin{

	public function output($theme, &$outputs);
}

/* Plugins with linkable contents in backend */
interface IHybridPlugin{}

/* Hidden plugin in frontend */
interface IHiddenPlugin{

	public function proccess();
}

/* Plugins with unlinkable contents in backend */
interface IGadgetPlugin{

	public function getGadget();
}

/* Plugins with linkable contents in backend */
interface IManagablePlugin{

	public function getManageItems();
}
interface IDate{

	public function toString($format, $timestamp = -1);

	public function fromString($strtime, $timestamp);

	public function toLocale($g_y, $g_m, $g_d, $seperator = null);

	public function toGregorian($l_y, $l_m, $l_d, $seperator = null);
}
?>