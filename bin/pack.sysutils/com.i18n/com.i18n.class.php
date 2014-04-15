<?php
namespace SysUtils;

class I18N extends SysUtil{
    private $date = null, $properties = array(),
    $readonly = array('dir', '_dir', 'align', '_align', 'default', 'locale', 'smallLocale');
    
    public function __construct($name){
		$this->properties['default'] = 'fa_IR';
		
		if($_GET->locale){
		    global $DataModule;
		    if($DataModule->exist('locales', 'cName', $_GET->locale))
			$name = $_GET->locale;
		}
		$this->properties['locale'] = $name;
		
		$smallLocale = explode('_', $this->locale);
		$this->properties['smallLocale'] = $smallLocale[0];
		
		setlocale(LC_ALL, $this->locale);
		
		include_once $this->_file('config.php');
		switch($this->dir){
		    case 'rtl':
				$this->properties['_dir'] = 'ltr';
				$this->properties['align'] = 'right';
				$this->properties['_align'] = 'left';
				break;
		    case 'ltr':
				$this->properties['_dir'] = 'rtl';
				$this->properties['align'] = 'left';
				$this->properties['_align'] = 'right';
				break;
		}
		include_once $this->_file('texts.php');
		include_once $this->_file('date.php');
		if(!in_array('IDate', class_implements($this->date)))
		    include_once $this->_file('date.php', true);
		if(!in_array('IDate', class_implements($this->date)))
		    die('class <b>\''.get_class($this->date).'\'</b> should implement IDate');
    }
    
    public function __get($var){
    	switch ($var){
    		case 'update_eula':
    			$text = file_get_contents($this->_file('update-eula.txt'));
    			break;
    		case 'date':
    			$text = $this->date;
    			break;
    		default:
    			if(!isset($this->properties[$var])) $text = '';
    			else $text = $this->properties[$var];
    			break;
    	}
    	
    	if(!in_array($var, $this->readonly) && $var != 'date'){
    		// standardize line breaks
    		$text = str_replace("\r\n", "\n", $text);
    		$text = str_replace("\r", "\n", $text);
    		
    		// replace tabs with spaces
    		$text = str_replace("\t", '    ', $text);
    		
    		// remove surrounding line breaks
    		$text = trim($text, "\n");

	    	$text = preg_replace(
	    		array('/\*{2}(.*?)\*{2}/i', '/\[([^\]\|]+)\]\((https?:\/\/|ftp:\/\/|mailto:)([^\]]+)\)/i'), 
	    		array('<strong>$1</strong>', '<a href="$2$3">$1 </a>'), 
	    		$text
	    	);
	    	
	    	$text = preg_replace(
	    		array("/\*+(.*)?/i", "/(\<\/ul\>\n(.*)\<ul\>*)+/", "/[0-9]+\.(.*)?/i", "/(\<\/ol\>\n(.*)\<ol\>*)+/"),
	    		array("<ul><li>$1</li></ul>", '', "<ol><li>$1</li></ol>", ''),
	    		$text
	    	);
	    	
	    	$text = preg_replace(
		    	array("/([\n]{2,})/i", "/([\n]{3,})/i", "/([^>])\n([^<])/i"),
		    	array("</p>\n<p>", "</p>\n<p>", '$1<br/>$2'),
		    	$text, -1, $count
	    	);
	    	
	    	$text = str_replace("\n", '', $text);

	    	if($count<=1) return $text;
	    	else return '<p>'.$text.'</p>';
    	} else 
    		return $text;
    }
    
    public function __set($var, $value){
		if(!in_array($var, $this->readonly) || !isset($this->properties[$var]))
		    throw new \Exception('Call to undefined property "'.$var.'"', 0);
		else
		    $this->properties[$var] = $value;
    }
    
    public function __isset($var){
		return isset($this->$var) || isset($this->properties[$var]) || $var == 'update_eula';
    }
    
    private function _file($file, $default = false){
		$name = $default? $this->default: $this->locale;
		$filepath = "i18n/$name/$file";
		if(file_exists($filepath)) return $filepath;
		return "i18n/$this->default/$file";
    }
    
    public function localize($folder){
    	$folder = rtrim($folder, '/');
    	$lf = "$folder/i18n/$this->locale/texts.php";
    	if(!file_exists($lf))
    		$lf = "$folder/i18n/$this->default/texts.php";
    	if(file_exists($lf)) include_once $lf;
    }

    public function importCallender(){
		global $Engine;
		$Engine->import($this->_file('callender.js'));
    }
    
    public function date($format, $time = -1){
		if($time == -1) $time = time();
		return $this->date->toString($format, $time);
    }
    
    public function strtotime($format, $time = -1){
		if($time == -1) $time = time();
		return $this->date->fromString($format, $time);
    }
    
    public function toLocale($g_y, $g_m, $g_d, $seperator = null){
		return $this->date->toLocale($g_y, $g_m, $g_d, $seperator);
    }
    
    public function toGregorian($l_y, $l_m, $l_d, $seperator = null){
		return $this->date->toGregorian($l_y, $l_m, $l_d, $seperator);
    }
    
    public function timeElapsed($ptime, $max = '1day', $format = 'd F Y H:i') {
		$result = '';
		$etime = time() - $ptime;
		
		if ($etime < 1) {
		    return '0 seconds';
		}
		
		$a = array(
		    'year'   => array($this->year,   12 * 30 * 24 * 60 * 60),
		    'month'  => array($this->month,  30 * 24 * 60 * 60),
		    'week'   => array($this->week,   7 * 24 * 60 * 60),
		    'day'    => array($this->day,    24 * 60 * 60),
		    'hour'   => array($this->hour,   60 * 60),
		    'minute' => array($this->minute, 60),
		    'second' => array($this->second, 1)
		);
		
		$maxsec = 0;
		preg_match_all('/([0-9]{1,2})(\w+)/i', $max, $m);
		foreach($m[2] as $n)
		    if(isset($a[$n])) $maxsec += $a[$n][1];
		    
		//debug($m);
		    
		if($etime > $maxsec){
		    $result = $this->date($format, $ptime);
		} else if($etime < 2*$a['day'][1] && date('d') != date('d', $ptime)) {
		    $result = $this->yesterday.' '.$this->date('H:i', $ptime);
		} else {
		    foreach ($a as $name => $var) {
				$d = $etime / $var[1];
				//$b = $etime % $secs;
				if ($d >= 1) {
				    $r = round($d);
				    $result .= $r . ' ' . $var[0];
				    break;
				}
		    }
		    $result .= ' '.$this->ago;
		}
	
		return $result;
    }
    
    public function extend($values){
		foreach($values as $var=>$value){
		    $var = strtolower($var);
		    if(!isset($this->properties[$var]))
			$this->properties[$var] = $value;
		}
    }
}
?>