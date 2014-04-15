<?php
namespace Utils;

abstract class URL extends Utility{
    private static $specialChars = array(
        array('char'=>'-', 'mod_rewrite'=>'!11'),
        array('char'=>'/', 'mod_rewrite'=>'!12')
    );
    static public $host, $base;
    
    static public function init(){
        URL::$host = 'http'.(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"? "s": "")."://".
            $_SERVER["SERVER_NAME"].(!in_array($_SERVER['SERVER_PORT'], array(80, 443))? ":".$_SERVER['SERVER_PORT']: '');
        URL::$base = trim(URL::trail(URL::$host.dirname($_SERVER['PHP_SELF'])), '/');
        $url = parse_url(URL::$base);
    }
    
    static public function trail($url){
        $url = trim($url);
        if(empty($url)) return '';
        $url =  str_replace('\\', '/', $url);
        return trim($url, '/').'/';
    }
    
    static public function current($absolute = true){
        return ($absolute? URL::$base.'/': '').URL::currentURI();
    }
    
    static public function currentURI(){
        $leading_path = dirname($_SERVER['PHP_SELF']);
        $URI = preg_replace('/^'.URL::createRegex(URL::$host).'/i', '', $_SERVER["REQUEST_URI"]);
        if($leading_path != '/')
            $URI = preg_replace('/^'.URL::createRegex($leading_path).'/i', '', $URI);
        
        global $i18n;
        $URI = preg_replace('/'.$i18n->locale.'\/(.*)/i', '\1', $URI);
        
        return ltrim($URI, '/');
    }

    static public function domain(){
        return $_SERVER["SERVER_NAME"];
    }

    static public function create($path, $absolute = false){
        global $Engine, $i18n; $driver = '';
        if(preg_match('/^(.*)\.([a-z0-9]{2,20})$/i', trim(URL::trail($path), '/'))){
            $path = 'static/'.$i18n->locale.'/'.$path;
        }
        $path = str_replace(URL::$base.'/', '', $path);
        return URL::trail($absolute? URL::$base.'/'.$path: $path);
    }
    
    static public function createQuery($query){
        $q = array();
        foreach($query as $n=>$v){
            if(is_numeric($n)) $q[] = $v;
            else $q[] = "$n-$v";
        }
        return implode('/', $q);
    }
    
    static public function createTiny($strURL) {
        $tinyurl = file_get_contents("http://tinyurl.com/api-create.php?url=".$strURL);
        return $tinyurl;
    }
    
    static public function id($url = null){
        if(is_null($url)) $url = URL::current();
        return md5($url);
    }
    
    static public function encode($value){
        $string = new String($value);
        foreach(URL::$specialChars as $charset)
            $string->replace("[$charset[char]]", $charset['mod_rewrite']);
        return $string;
    }
    
    static public function decode($value){
        $string = new String($value);
        foreach(URL::$specialChars as $charset)
            $string->replace("[$charset[mod_rewrite]]", $charset['char']);
        return $string->__toString();
    }
    
    static public function createRegex($url, $full = false, $params = ''){
        $url = str_ireplace('/', '\/', $url);
        return ($full? '/': '').$url.($full? '/': '').$params;
    }
    
    static public function getResponseCode($url){
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }
}


?>