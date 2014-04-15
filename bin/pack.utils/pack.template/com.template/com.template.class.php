<?php
namespace Utils\Template;

class Template extends \Utils\Utility {
    protected $source, $tags, $specialTags;
    
    public function __construct($tpl_path, $specialTags = ''){
        $this->specialTags = explode(',', $specialTags);
        $this->htmls = array();
        $this->tags = array();
		if(\Utils\File::ext($tpl_path) != 'tpl')
		    $this->source = new \Utils\String($tpl_path);
        else if(file_exists($tpl_path)){
	    	global $Engine, $Viewer;
            $this->source = new \Utils\String(\Utils\File::read($tpl_path));
		    $tpl_path = \Utils\File::removeExt($tpl_path);
		    $jsfile = "$tpl_path.js";
		    if(file_exists($jsfile))
				$Viewer->bind('onready', \Utils\File::read($jsfile));
		    
		    $cssfile = "$tpl_path.css";
		    if(file_exists($cssfile))
				$Engine->import($cssfile);
        }
		if(is_null($this->source))
		    die('Template is not installed correctly !');
        $this->extract($this->source);
    }
    
    private function extract($source){
		$source = new \Utils\String($source);
		    
		// extract all tags
		$m = $source->matches('/\{%(\w+)(:[^%]+)?%\}/i');
		
		foreach($m[1] as $index=>$loc){
		    // extract start and of a tag
	        $mt = $source->matches("/(\{$loc\}(.*))?\{%$loc(:([^%]+))?%\}((.*)\{\/$loc\})?/is");
		    //if($loc == 'microblog_post_thumb') debug($mt);
		    $params = array();
		    foreach(preg_split('/[\;]/', $mt[4][0], 0, PREG_SPLIT_NO_EMPTY) as $p){
				@list($name, $value) = preg_split('/[=]/', $p, 0, PREG_SPLIT_NO_EMPTY);
				$params[$name] = $value;
		    }
		    $this->tags[$loc] = (object)array(
				'htmls' => array(),
				'text' => $mt[0][0],
				'start' => trim($mt[2][0]),
				'end' => $mt[6][0],
				'permanent' => empty($mt[2][0]),
				'params' => $params
		    );
		}
		
		foreach($this->tags as $name => &$tag){
		    if(preg_match_all('/\{%(\w+)(:[^%]+)?%\}/i', $tag->start.$tag->end, $ch)){
				// Set child tags to compile after parents
				foreach($ch[1] as $childname){
				    $child = $this->tags[$childname];
				    unset($this->tags[$childname]);
				    $this->tags[$childname] = $child;
				}
		    }
		}
    }
    
    public function getParam($tag, $name = null, $default = null){
		if(is_null($name)) return $this->tags[$tag]->params;
		
		if(isset($this->tags[$tag]->params[$name]))
		    return $this->tags[$tag]->params[$name];
		else
		    return is_null($default)? false: $default;
    }
    
    public function importFiles(){
		$files = $this->source->matches('/\[file:([^\]]+)\]/ie');
		foreach($files[1] as $b7285b4fd8efc55=>$c7da94fc880eac){
		    ob_start();
		    extract($GLOBALS, EXTR_REFS);
		    include \Utils\File::$base."/$c7da94fc880eac";
		    $tag = str_replace('[', '\[', str_replace(']', '\]', $files[0][$b7285b4fd8efc55]));
		    $fc = ob_get_clean();
		    $this->source->replace("($tag)", $fc);
		    $this->extract($fc);
		}
    }
    
    public function each($all = false){
        if($all){
            $tags = array();
            foreach($this->tags as $name=>$tag){
                if(!in_array($name, $this->specialTags))
                    $tags[] = $tag;
            }
            return $tags;
        } else {
            $p = each($this->tags);
            if($p === false) return false;
            if(!in_array($p[0], $this->specialTags))
                return $p[0];
            else
                return $this->each();
        }
    }
    
    public function assign($name, $value){
        if(isset($this->tags[$name]))
            $this->tags[$name]->htmls[] = $value;
		$this->extract($value);
    }
    
    public function __toString(){
        $output = $this->source->__toString();
		foreach($this->tags as $name => &$tag){
		    $content = implode($tag->htmls);
		    
		    $replace = "$tag->start$content$tag->end";
            if(!$tag->permanent && empty($content)) $replace = '';
            
            $output = str_replace($tag->text, $replace, $output);
		}
        return $output;
    }
}
?>