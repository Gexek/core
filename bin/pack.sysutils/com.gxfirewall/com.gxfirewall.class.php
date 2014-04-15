<?php
namespace SysUtils;

abstract class FirewallProperty extends SysUtil{
    protected $params = array(), $session = null;
    
    public function __construct(){
		if(is_null($this->session))
		    $this->session = end(explode('\\', get_class($this)));
		$this->session = strtoupper($this->session);
		
		if(!isset($_SESSION[$this->session]))
		    $this->reset();
    }
    
    public function __isset($var){
    	if(in_array($var, array_keys($this->params)))
    		return isset($_SESSION[$this->session][$this->params[$var]]);
    	return isset($this->$var);
    }
    
    public function __get($var){
    	if($var == 'all')
    		return $_SESSION[$this->session];
    	
    	if(in_array($var, array_keys($this->params)))
    		return $_SESSION[$this->session][$this->params[$var]];
    	
    	return false;
    }
    
    public function __set($var, $value){
    	if(in_array($var, array_keys($this->params)))
    		$_SESSION[$this->session][$this->params[$var]] = $value;
    }
    
    protected function reset(){
		if(!isset($_SESSION[$this->session]))
		    $_SESSION[$this->session] = array();
		foreach($this->params as $p){
		    $rest = preg_match('/^_/i', $p);
		    if(!$rest || ($rest && !isset($_SESSION[$this->session][$p])))
			$_SESSION[$this->session][$p] = false;
		}
    }
}

class GXFirewallCache extends FirewallProperty{
    protected $params = array(
    		'permissions' => 'prmsn', 'auth' => 'auth'
    ), $session = 'GXF_CACHE';
    
    /*public function __isset($var){
		return isset($_SESSION[$this->session][$var]);
    }
    
    public function __get($var){
		if($var == 'all')
		    return $_SESSION[$this->session];
		
		if(isset($_SESSION[$this->session][$var]))
		    return $_SESSION[$this->session][$var];
		return false;
    }
    
    public function __set($var, $value){
		$_SESSION[$this->session][$var] = $value;
    }*/
}

class GXFirewallStatus extends FirewallProperty{
    protected $params = array(
		'action' => 'act',
		'message' => 'msg'
    ), $session = 'GXF_STATUS';
    
    /*public function __isset($var){
		if(in_array($var, array_keys($this->params)))
		    return isset($_SESSION[$this->session][$this->params[$var]]);
		return isset($this->$var);
    }
    */
    public function __get($var){
		if(in_array($var, array_keys($this->params)))
		    return $_SESSION[$this->session][$this->params[$var]];
		else if(isset($this->$var))
		    return $this->$var;
		return false;
    }/*
    
    public function __set($var, $value){
		if(in_array($var, array_keys($this->params)))
		    $_SESSION[$this->session][$this->params[$var]] = $value;
    }*/
}

class GXFirewallClient extends FirewallProperty{
    private $sessionid, $protocol, $referer, $ip, $localport,
    $remoteport, $id, $name, $groups, $gxid, $fields = array(
		'username' => 'GX_USER',
		'password' => 'GX_PASS',
		'password_confirm' => 'GX_PASS2',
		'email' => 'GX_EMAIL'
    );
    
    protected $params = array(
		'valid' => 'uv', 'id' => 'ui', 'user' => 'uu', 'name' => 'un', 'mail' => 'um',
		'pass' => 'up', 'groups' => 'ug', 'auth_return' => '_ar'
    ), $session = 'GXF_CLIENT';
    
    public function __construct(){
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->remoteport = $_SERVER['REMOTE_PORT'];
		$this->localport = $_SERVER['SERVER_PORT'];
		$this->protocol = 'http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"? "s": "");
		$this->referer = \Utils\URL::$base;
		if(isset($_SERVER['HTTP_REFERER']))
		    $this->referer = $_SERVER['HTTP_REFERER'];
		
		$this->gxid = md5(\Utils\URL::$base);
		$this->sessionid = md5($this->gxid.Security::getRTSC());
		
		parent::__construct();
    }
    
    public function __isset($var){
		if(in_array($var, array_keys($this->params)))
		    return isset($_SESSION[$this->session][$this->params[$var]]);
		return isset($this->$var);
    }
    
    public function __get($var){
		if(isset($this->fields[$var])) return $this->fields[$var];
		if(in_array($var, array_keys($this->params))) return $_SESSION[$this->session][$this->params[$var]];
		if(isset($this->$var)) return $this->$var;
		return false;
    }
    
    private function _get($var){
		if(in_array($var, array_keys($this->params)))
		    return $_SESSION[$this->session][$this->params[$var]];
		return false;
    }
    
    private function _set($var, $value){
		if(in_array($var, array_keys($this->params)))
		    $_SESSION[$this->session][$this->params[$var]] = $value;
    }
    
    public static function unique($action = 'website', $id = 0){
        $key = md5("__GX_$action"."_$id");
		if(!isset($_SESSION[$key])) {
		    $_SESSION[$key] = $this->ip;
		    return false;
		}
		$result = $_SESSION[$key] == $this->ip;
		$_SESSION[$key] = $this->ip;
		return $result;
    }
    
    public function rememberURL(){
		global $Engine;
		if($Engine->driver != 'auth'){
		    $url = \Utils\URL::current();
		    $this->_set('auth_return', $url);
		}
    }
    
    public function slientLogin(){
		return $this->login($this->_get('id'), $this->_get('pass'), 'login_unathorized');
    }
    
    public function login($user, $pass, $message = null){
		global $DB, $Firewall;
		Security::prepareInput($user);
		Security::prepareInput($pass);
		if(is_numeric($user))
		    $field = 'nID';
		else {
		    $field = 'cUsername';
		    $pass = md5($pass);
		}
		
		$rs = $DB->prepare("SELECT * FROM users WHERE $field = :user AND cPassword = :pass");
		$rs->execute(array(':user' => $user, ':pass' => $pass));
		
		$this->_set('valid', false);
		if($rs->rowCount() > 0){
		    $rs = $rs->fetch(\PDO::FETCH_ASSOC);
		    if($rs['bActive']){
				$this->_set('valid', true);
				$this->_set('id', $rs['nID']);
				$this->_set('user', $rs['cUsername']);
				$this->_set('name', $rs['cDisplayName']);
				$this->_set('mail', $rs['cEmail']);
				$this->_set('groups', preg_split('/,/', $rs['cGroups'], 0, PREG_SPLIT_NO_EMPTY));
				$this->_set('pass', $pass);
				return true;
		    } else {
				$Firewall->status->message = 'login_inactive';
				return false;
		    }
		}
		$message = is_null($message)? 'login_failed': $message;
		$Firewall->status->message = $message;
		return false;
    }
    
    public function register(){
		global $DB, $i18n, $Firewall;
		
		$inputs = array(
		    'username' => $_POST->{$this->fields['username']},
		    'password' => $_POST->{$this->fields['password']},
		    'password_confirm' => $_POST->{$this->fields['password_confirm']},
		    'email' => $_POST->{$this->fields['email']}
		);
		
		foreach($inputs as $n => $v)
		    $Firewall->cache->auth->$n = $v;
		$Firewall->cache->auth->{'password_confirm'} = '';
	
		foreach($inputs as $n => $v){
		    if(!$v || empty($v)){
			$Firewall->status->message = sprintf($i18n->error_no_empty, $i18n->$n);
			return false;
		    }
		}
		
		if($inputs['password'] !== $inputs['password_confirm']){
		    $Firewall->status->message = 'register_password_match';
		    return false;
		} else if(strlen($inputs['password']) < 6){
		    $Firewall->status->message = str_replace('%s', $i18n->password, $i18n->error_min_length);
		    $Firewall->status->message = str_replace('%n', 6, $Firewall->status->message);
		    return false;
		} else if(!preg_match('/^([a-z0-9_\.]+)$/i', $inputs['username'])){
		    $Firewall->status->message = sprintf($i18n->error_not_match_pattern, $i18n->username);
		    return false;
		} else if(!preg_match('/^([a-z0-9_\.]+)@([a-z0-9-]+).([a-z]{2,4})$/i', $inputs['email'])){
		    $Firewall->status->message = sprintf($i18n->error_not_match_pattern, $i18n->email);
		    return false;
		}
		
		Security::prepareInput($inputs['username']);
		Security::prepareInput($inputs['password']);
		Security::prepareInput($inputs['password_confirm']);
		Security::prepareInput($inputs['email']);
		
		
		$rs = $DB->prepare("SELECT * FROM users WHERE cUsername = :user OR cEmail = :email");
		$rs->execute(array(':user' => $inputs['username'], ':email' => $inputs['email']));
		if($rs->rowCount() > 0){
		    $rs = $rs->fetch(\PDO::FETCH_ASSOC);
		    if($rs['cUsername'] == $inputs['username'])
			$Firewall->status->message = 'register_username_taken';
		    else
			$Firewall->status->message = 'register_email_taken';
		    return false;
		}
		
		$rs = $DB->prepare("
		    INSERT INTO users (cUsername, cPassword, cEmail, cGroups, bActive)
		    VALUES(:user, :pass, :email, '', :active)
		");
		$active = $Settings->activation_type == 'auto'? 1: 0;
		if($rs->execute(array(
		    ':user' => $inputs['username'],
		    ':pass' => md5($inputs['password']),
		    ':email' => $inputs['email'],
		    ':active' => $active
		))){
		    if($active)
			$Firewall->status->message = 'register_successfull';
		    else
			$Firewall->status->message = 'register_need_admins_confirm';
		    $Firewall->changeAction('login');
		}
		$Firewall->status->message = 'register_failed';
		return false;
    }
    
    /**
     * Checks if the client can access to an element of the website.
     * 
     *** Note: By default, anything is allowed to access anything
     *** Note: The default rule limits all applicants to access to administration area.
     *** Note: Super administrator will bypass all the rules, even the default rule.   
     * 
     * @param string $action allowed values are "manage" and "access"
     * @param string $item type of the module which is accessed
     * @param string $name the module name
     */
    public function permitted($action, $module_type = null, $module_name = null){
    	global $Settings, $Firewall;
    	$permitted = true;
    	
    	// Return true if the Super Administrator is the client.
    	// This let the Super Administrator to bypass all the rulde and be permitted to do anything.
    	if($this->valid && $this->_get('id') == $Settings->admin_acc)
    		return true;
		
    	// Just for more readability of the source
    	$perms = $Firewall->cache->permissions;
    	
    	switch($action){
    		/*
    		 * ADMINISTRATION AREA:
    		 * 
    		 * Gexek is checking the administrating persmissions to check if client can manage each part of website.
    		 * 
    		 * Regarding to this, a client can access global seeting of the website such as website information, registration methods,
    		 * default theme & language customization, pages, update center and caching.
    		 * 
    		 * It also may be able to manage a plugin.
    		 * 
    		 */
    		case 'manage':
    			// Want to check if client can manage anything, so it can access to administration panel.
    			if(is_null($module_type))
    				// True, if client can manage global website settings or at least one of the plugins 
    				$permitted = $this->permitted('manage', 'website') || $this->permitted('manage', 'plugin');
    			else {
    				// Wnat to check if a client can manage a specific managable item.
    				switch ($module_type){
    					case 'website':
    						// True, if client can manage all website settings. plugins and security panel are not included 
    						$permitted = isset($perms['manage']['website']) && $perms['manage']['website'];
    						break;
    						
    					case 'security':
    						// True, if client can manage security items such as users, groups, rules and global security settings
    						$permitted = isset($perms['manage']['security']) && $perms['manage']['security'];
    						break;
    						
    					case 'plugin':
    						// check if client can manage one or more plugins.
    						if(is_null($module_name)){
    							// at least one plugin
    							$permitted = isset($perms['manage']['plugins']) && in_array(true, $perms['manage']['plugins']);
    						}else {
    							// a specific plugin
	    						if(isset($perms['manage']['plugins']) && isset($perms['manage']['plugins'][$module_name]))
	    							$permitted = $perms['manage']['plugins'][$module_name];
    						}
    						break;
    				}
    			}
    			break;
    			
    		/*
    		 * USERS AREA:
    		 * 
    		 * As user area in gexek 1.6 is just accessible via urls, which are rewritten by gexek internal rewrite engine, so 
    		 * every URL is page created by a user or a plugin. In order to this, each client canbe permitted to access or not access to a page.
    		 * In this part, we are checking the client's permissions to access a page.
    		 * 
    		 * Like other rule, all users are allowed to access all pages by default.
    		 */
    		case 'access':
    			if(is_null($module_type))
    				throw new \Exception('The type of module is not specified', 13001);
    			
    			switch($module_type){
    				case 'page':
    					if(is_null($module_name))
    						throw new \Exception('Page name is not specified', 13003);
    					
    					if(isset($perms['pages'][$module_name]))
    						$permitted = $perms['pages'][$module_name];
    					else if(isset($perms['pages']['all']))
    						$permitted = $perms['pages']['all'];
    					break;
    					
    				default:
   						throw new \Exception('"'.$module_type.'" is not a known accessible object!', 13002);
    					break;
    			}
    			break;
    	}
    	//debug($permitted);
    	return $permitted;
    }
    
    public function logout(){ $this->reset(); return true; }
    
    // Deprecated! use $Firewall->client->valid instead
    public function loggedin(){ return $this->_get('valid'); }
}

class GXFirewall extends SysUtil{
    private $cache, $client, $rules = array(), $states = array(),
    $authActs = array('register','login','logout');
    public $status;
    
    public function __construct(){
		$this->status = new GXFirewallStatus;
		$this->client = new GXFirewallClient;
		$this->cache = new GXFirewallCache;
	
		
		// Initial valid requests
		$RTSC = Security::getRTSC();
		$is = array(24, 11, 2, 7, 21, 8, 29, 23, 30, 19);
		$acts = $this->authActs;
		foreach($acts as $index => $act){
		    $key = str_split(md5($act.$RTSC));
		    $a = array();
		    foreach($is as $i) $a[] = $key[$i];
		    $this->authActs[$act] = implode($a);
		    unset($this->authActs[$index]);
		}
    }
    
    public function __isset($var){ return isset($this->fields[$var]); }
    public function __get($var){ return $this->$var; }
    
    public function perform(){
    	$this->cache->permissions = null;
    	
		$result = false;
		switch($_GET->action){
		    case $this->authActs['register']:
				$result = $this->client->register();
				break;
		    
		    case $this->authActs['login']:
				$user = $_POST->{$this->client->username};
				$pass = $_POST->{$this->client->password};
				if($user && $pass) $result = $this->client->login($user, $pass);
				//else $result = $this->client->slientLogin();
				else $result = false;
				break;
		    
		    case $this->authActs['logout']:
				$result = $this->client->logout();
				break;
		}
		return $result;
    }
    
    public function currentAction(){
		foreach($this->authActs as $name => $key)
		    if($_GET->action == $key)
				return $name;
    }

    public function formAction($action, $absolute = true){
		global $i18n; $fa = '';
		if(isset($this->authActs[$action]))
		    $fa = 'auth/'.$i18n->locale.'/'.$this->authActs[$action].'/'.Security::getRTSC().'/';
		if($absolute) $fa = \Utils\URL::$base.'/'.$fa;
		return $fa;
    }
    
    public function changeAction($action, $reset = false){
		global $Engine;
		if($reset) $this->reset();
		$Engine->redirect($this->formAction($action, true), false, false);
    }
    
    public function reset(){
		$this->status->reset();
		$this->cache->reset();
    }
    
    private function needRecollect(){
    	global $Settings;
    	if(!$this->cache->permissions) $this->cache->permissions = null;
    	return 
	    	(is_null($this->cache->permissions) || (
	    			isset($this->cache->permissions['time']) && $this->cache->permissions['time'] < $Settings->last_rule_change
		    )) && !($this->client->valid && $this->client->id == $Settings->admin_acc);
    }
    
    public function collectRules(){
    	global $Settings;
    	
    	if($this->needRecollect()){
			global $DB, $i18n, $Engine, $Admin, $Settings;
			$this->client->slientLogin();
			
			$op = $Engine->getOutputFilters();
			
			$sql = 'SELECT * FROM rules WHERE nID = 1 OR cApplicants '.jsonsql_object_var('all', 1);
			
			if($this->client->valid){
				$sql .= 
				' OR cApplicants '.jsonsql_in_array('users', 'all').
				' OR cApplicants '.jsonsql_in_array('users', $this->client->id);
					
				if(count($this->client->groups) > 0){
					$sql .= ' OR cApplicants '.jsonsql_in_array('groups', 'all');
					foreach($this->client->groups as $g)
						$sql .= ' OR cApplicants '.jsonsql_in_array('groups', $g);
				}
			} else
				$sql .= ' OR cApplicants '.jsonsql_object_var('unauth', 1);
			
			$sql .= ' ORDER BY nID ASC';
			
			$this->cache->permissions = array();
			$ruleset = array(
					'time' => time(), 'pages'=>array(), 
					'manage'=>array('plugins'=>array_fill_keys($Engine->plugins->names, true))
			);
			foreach ($DB->query($sql) as $rule){
				$this->rules[] = $rule;
				
				$access = (bool)$rule['bAccessType'];
				$pages = json_decode($rule['cPages']);
				$manages = json_decode($rule['cManage']);
				
				// Check if all pages are targeted
				if(in_array('all', $pages)) 
					// Set the value equal to $access, for all pages which have been targeted in previous rules 
					$ruleset['pages'] = array_merge($ruleset['pages'], array_fill_keys(array_keys($ruleset['pages']), $access));
				
				// Make a set of new pages with value of $access and merge with previous pages
				$ruleset['pages'] = array_merge($ruleset['pages'], array_fill_keys($pages, $access));
				
				
				// Check if all managables are targeted
				if(isset($manages->all)){
					$ruleset['manage']['website'] = $access;
					$ruleset['manage']['security'] = $access;
					$ruleset['manage']['plugins'] = array_merge($ruleset['manage']['plugins'], array_fill_keys($Engine->plugins->names, $access));
				} else {
					if(isset($manages->website)) $ruleset['manage']['website'] = $access;
					
					if(isset($manages->security)) $ruleset['manage']['security'] = $access;
					
					if(isset($manages->plugins)){
						if(in_array('all', $manages->plugins))
							$ruleset['manage']['plugins'] = array_merge($ruleset['manage']['plugins'], array_fill_keys($Engine->plugins->names, $access));
						else 
							$ruleset['manage']['plugins'] = array_merge($ruleset['manage']['plugins'], array_fill_keys($manages->plugins, $access));
					}
				}
			}
			$this->cache->permissions = $ruleset;
    	}
    }
    
    private function create_array($array){
		$result = array();
		foreach($array as $arr){
			$arr = explode(':', $arr);
		    $result[$arr[0]] = isset($arr[1])? $$arr[1]: 'all';
		}
		return $result;
    }
    
    public function allow(){
		/*if(count($this->rules) > 0){
		    //debug($this->rules, '', '', false);
		    foreach($this->rules as $i => $rule){
			//echo $i;
			$objs = $this->create_array(json_decode($rule['cObjects']));
			//debug(var_dump($this->states), '', '', false);
			$type = (boolean)$rule['bAccessType'];
			//debug(var_dump($this->states), '', '', false);
			foreach($this->states as $name => $value){
			    //debug($name, '', '', false);
			    if(isset($objs[$name]) || isset($objs['all']))
				$this->states[$name] = $type;
				
			    //debug(var_dump($this->states), '', '', false);
			}
		    }
		    //debug(var_dump($this->states), '', '', false);
		    //exit;
		    foreach($this->states as $state)
			if(!$state) return false;
		    
		    return true;
		} else*/
		    return true;
    }
}
?>