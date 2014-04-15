<?php
namespace Data\Request;

class GETRequest extends RequestObject {
    const IGNORE_BOTH = 0, IGNORE_VALUE = 1, IGNORE_NAME = 2;
    
    private $__GET;
    private $rewrites = array();
    
    public function __construct(){
    	$this->__GET = $_GET;
    	$this->items['values'] = array();
        /*$qstr = explode('#', \Utils\URL::currentURI());
        $qstr = $this->parseQuery($qstr[0], '/', '\-');
        foreach($_GET as $name => $value)
            $qstr[] = array('name'=>$name, 'value'=>$value);
        parent::__construct($qstr);*/
    }
    
    private function prepareRule($rule){
        $chars = array('/', '%', '$');
        $replace = array('\\/', '\%', '\$');
        return str_replace($chars, $replace, $rule);
    }
    
    protected function import($source){
        foreach($source as $src){
            if($src['name'] == 'rewrite'){
            	if(!empty($src['value']))
                	$this->import($this->parseQuery($src['value'], '/', '\-'));
            } else {
                if(empty($src['value'])) {
                	if(!in_array($src['name'], $this->items['values']))
                		$this->items['values'][] = $src['name'];
                } else 
                	$this->items[$src['name']] = \Utils\URL::decode($src['value']);
            }
        }
    }
    
    private function parseQuery($query, $splt1 = '&', $splt2 = '='){
    	if(!is_string($query)) throw new \BadMethodCallException('GetRequest::parseQuery expects parameter 1 to be string', 0);
    	
    	$qstr = new \Utils\String($query);
    	$qstr = $qstr->split('['.$splt1.']', -1, function($item) use($splt2){
    		$item = new \Utils\String($item);
    		$item = $item->split('['.$splt2.']');
    		return array('name'=>$item[0], 'value'=>isset($item[1])? $item[1]: null);
    	});
    	return $qstr;
    }
    
    public function rewrite(){
    	global $DataModule;
		
    	$uri = \Utils\URL::currentURI();
    	foreach($DataModule->rewrites->all as $rw){
    		$rule = $this->prepareRule($rw->rule);
    		$uri = preg_replace("/^$rule$/", $rw->url, $uri);
    		if(preg_match("/^$rule$/", \Utils\URL::currentURI()))
    			$this->_rewrite($rule, $rw->url, $rw->name);
    	}

    	$qstr = explode('#', $uri);
    	$qstr = $this->parseQuery($qstr[0]);
    	foreach($this->__GET as $name => $value)
    		if(!$this->$name)
    			$qstr[] = array('name'=>$name, 'value'=>$value);
    		
    	$this->import($qstr);
    }
    
    private function _rewrite($rule, $url, $registrar = 'nothing'){
        $this->rewrites[$rule] = array('url' => $url, 'registrar' => $registrar);
        $this->ignoreURI(preg_replace("/^$rule$/", $url, \Utils\URL::currentURI()));
            
        $qstr = explode('#', preg_replace("/^$rule$/", $url, \Utils\URL::currentURI()));
        $this->import($this->parseQuery($qstr[0]));
    }
    
    public function ignoreURI($url, $type = GETRequest::IGNORE_BOTH){
        $chunks = preg_split('/\//', $url, -1, PREG_SPLIT_NO_EMPTY);
        $au = array();
        foreach($chunks as $i => $v){
            if(isset($this->items['values'][$i]) && $this->items['values'][$i] == $v){
                if($type == GETRequest::IGNORE_BOTH || $type == GETRequest::IGNORE_VALUE) {
                    unset($this->items['values'][$i]);
                    $au[$v] = isset($au[$v])? $au[$v]+1: 1;
                }
            }
        }
        
        foreach($au as $name => $count){
            if(!in_array($name, $this->items['values']))
                if($type == GETRequest::IGNORE_BOTH || $type == GETRequest::IGNORE_NAME)
                    if(isset($this->items[$name]))
                        unset($this->items[$name]);
        }
        
        $this->items['values'] = array_merge(array(), $this->items['values']);
    }
    
    public function ignore($var = null){
        global $Settings, $i18n, $Engine;
        $this->ignoreURI('locale', GETRequest::IGNORE_VALUE);
        $this->ignoreURI($Engine->driver);
        $this->ignoreURI($i18n->locale);
        $_GET->ignoreURI($_GET->page);
        
        if($Engine->adminmode)
            $this->ignoreURI($Settings->admin_uri);
        
        if($Engine->authmode)
            $this->ignoreURI($Settings->auth_uri);
    }

    public function exists($key){
        return array_key_exists($key, $this->items) || in_array($key, $this->items['values']);
    }
}
?>