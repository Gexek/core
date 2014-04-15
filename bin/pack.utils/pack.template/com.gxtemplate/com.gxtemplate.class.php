<?php
namespace Utils\Template;

class GXTemplate extends Template {
    public function __construct($tpl_path, $specialTags = ''){
        parent::__construct($tpl_path, $specialTags);
        $this->source->concat('{%document%}', \Utils\String::PREFIX);
        $this->tags['document'] = new \stdClass;
        $this->tags['document']->params = array();
        $this->tags['document']->htmls = array();
        $this->tags['document']->text = '{%document%}';
        $this->tags['document']->start = '';
        $this->tags['document']->end = '';
        $this->tags['document']->permanent = true;
    }
    
    private function checkParams($params){
        /* Backward compatibility */
        if(in_array('login', $params)) $params[] = 'validuser';
        if(in_array('nologin', $params)) $params[] = 'invaliduser';
        /***/
        global $Firewall; return
        (in_array('validuser', $params) && !$Firewall->client->loggedin()) ||
        (in_array('invaliduser', $params) && $Firewall->client->loggedin());
    }
    
    public function __toString(){
	foreach($this->tags as $name=>$tag){
	    $content = implode($tag->htmls);
	    if($this->checkParams($tag->params)) $c = '';
            else if(!empty($content) || in_array('allowempty', $tag->params)) {
		$c = $tag->start;
		if(in_array('relative', $tag->params))
		    $c .= '<div style="height: 100%; position: relative; width: 100%;">';
		$c .= $content;
		if(in_array('relative', $tag->params))
		    $c .= '</div>';
		$c .= $tag->end;
	    } else
		$c = '';

            $this->source = str_replace($tag->text, $c, $this->source);
	}
        return $this->source;
    }
}
?>