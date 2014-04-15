<?php
namespace SysUtils;

class GXSettings extends SysUtil{
    private $items, $db;
    
    public function __construct(){
        global $DB;
        $this->db = &$DB;
		$this->load();
    }
    
    private function setDefaults(){
		$this->items = array(
		    'admin_uri' => 'admin/',
		    'admin_acc' => 1,
            'caching' => 1,
            'cache_priod' => 30,
            'compression' => 1,
		    'locale' => 'fa_IR',
		    'site_name' => '',
		    'site_desc' => '',
		    'site_keys' => '',
		    'recovery_email' => '',
		    'register_allowed' => 1,
		    'activation_type' => 'auto',
	        'themes' => 'default',
		    'update_mode' => 0,
		    'update_server' => 'http://updates.kassit.com/',
		    'support_user' => \Utils\URL::$base,
		    'support_password' => '',
		    'version' => \GXEngine::VERSION,
		    'auth_uri' => 'users/',
		    'admin_uri' => 'admin/',
		    'plugins' => array(),
			'last_rule_change' => 0
		);
    }

    private function validate($value){
		$value = stripslashes($value);
		$v = json_decode($value);
		if(!is_null($v)) $value = $v;
		return $value;
    }
    
    private function load(){
		$this->setDefaults();
		$rs = $this->db->query('SELECT * FROM settings WHERE cType = "core" AND cName = "core"');
		foreach($rs as $row){
			$value = $row['cValue'];
			if($row['cVariable'] != 'version')
				$value = $this->validate($value);
		    $this->items[$row['cVariable']] = $value;
		}
    }
   
    public function __get($var){
		return isset($this->items[$var])? $this->items[$var]: false;
    }
    
    public function __set($var, $value){
		if(isset($this->items[$var]))
		    $this->items[$var] = $value;
    }
    
    public function __isset($var){
        return isset($this->items[$var]);
    }
    
    public function save(){
		foreach($this->items as $name=>$value){
		    $value = is_array($value) || is_object($value)? json_encode($value): $value;
		    $values = array( 'cType' => 'core', 'cName' => 'core', 'cVariable' => $name, 'cValue' => addslashes($value) );
		    $this->db->replace('settings', $values);
		}
    }
}
?>