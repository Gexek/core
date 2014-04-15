<?php
namespace Graphic\Editors;

class TinyMCE extends \Graphic\Editor {
    private $buttons = array();
    
    public function __construct($name, $options = array()){
		global $i18n, $Settings;
		
		$locale = explode('_', $i18n->locale);
        extend($options, array(
		    'value' => '',
            'width' => '100%',
            'height' => '400px',
            'language' => $locale[0],
		    'plugins' => null, 
		    'toolbars' => null, 
		    'menubars' => null,
		    'resizable' => false,
		    'invalidelements' => 'iframe,object',
	        'content_css' => '',
		    'visualblocks' => true
        ));
        
		$options['content_css'] = (array)$options['content_css'];
		$options['content_css'][] = \Utils\URL::create('themes/'.$Settings->themes[0].'/css/imports.css', true);
		$options['content_css'][] = 'static/'.$i18n->locale.'/ui/'.\Utils\URL::encode('themes/'.$Settings->themes[0]).'/';
		$options['content_css'][] = \Utils\URL::$base.'/bin/pack.graphic/pack.editors/com.tinymce/content.css';
		
		if(is_null($options['plugins']))
			$options['plugins'] = array(
				'fullscreen','visualblocks','autolink','table','image','link','anchor','media','searchreplace','textcolor',
				'directionality','nonbreaking','advlist','code','pagebreak','emoticons','template','hr','codemirror'
			);
		if(is_null($options['toolbars']))
		    $options['toolbars'] = array(
				array('ltr','rtl','|','alignleft','aligncenter','alignright','alignjustify','|','outdent','indent','|','bullist','numlist','|','link','unlink','anchor','|','image','media','emoticons'),
				array('bold','italic','underline','strikethrough','|','forecolor','backcolor','|','styleselect','fontselect','fontsizeselect','|','removeformat'),
			);
		if(is_null($options['menubars']))
			$options['menubars'] = array(
				'edit' => array('title' => 'Edit', 'items' => array('undo','redo','|','cut','copy',',paste','pastetext','|','selectall')),
				'insert' => array('title' => 'Insert', 'items' => array('link','media','|','template','hr')),
				'view' => array('title' => 'View', 'items' => array('visualblocks','code','fullscreen')),
				'format' => array('title' => 'Format', 'items' => array('bold','italic','underline','strikethrough','superscript','subscript','|','formats','|','removeformat')),
				'table' => array('title' => 'Table', 'items' => array('inserttable','tableprops','deletetable','|','cell','row','column'))
			);
		    
	    parent::__construct($name, $options);
		$this->clientCalls = array('value' => new \SuperClosure(function($obj, $args){
            if(isset($args[0]))
                return '$("#'.$obj->id.'_textarea").val("'.$args[0].'")';
            else
                return '$("#'.$obj->id.'_textarea").val()';
        }));
	}
	    
	public function addButton($name, $title, $icon, $options = array()){
		extend($options, array('onclick' => ''));
		$this->buttons[$name] = array('title' => $title, 'icon' => $icon, 'options' => $options);
    }
    
    public function generate(){//debug(implode(',', $this->options->content_css));
		global $Viewer, $i18n, $Engine;

		$onready = '$("#'.$this->id.'_textarea").tinymce({'.
		    'script_url: "'.\Utils\URL::$base.'/bin/pack.graphic/pack.editors/com.tinymce/tinymce.min.js",'.
		    'content_css: "'.trim(implode(',', $this->options->content_css), ',').'", '.

		    'theme: "modern",'.
		    'plugins: "'.implode(',', $this->options->plugins).'",';
		foreach ($this->options->toolbars as $i => $toolbar)
			$onready .= 'toolbar'.($i+1).': "'.implode(' ', $toolbar).'",';
		$onready .= (count($this->buttons>0)? 'toolbar'.($i+2).': "'.implode(' ', array_keys($this->buttons)): '').'",';
		$menubars = '';
		foreach ($this->options->menubars as $name => $menubar)
			$menubars .= $name.': {title: "'.$menubar['title'].'", items: "'.implode(' ', $menubar['items']).'"},';
		$onready .=
			'menu : {'.trim($menubars, ',').'},'.
			
			'codemirror:{'.
				'indentOnInit: true,'.
			    'path: "CodeMirror",'.
			    'config: {'.
					'align: "'.$i18n->align.'",'.
			        'mode: "htmlmixed",'.
			        'lineNumbers: true,'.
					'lineWrapping: true,'.
					'indentUnit: 4,'.
					'tabSize: 4,'.
					'indentWithTabs: true'.
			    '},'.
			    'jsFiles: []'.
			'},'.
			
		    'elements: "nourlconvert",'.
		    'language: "'.$this->options->language.'",'.
		    'width: "'.$this->options->width.'",'.
		    'height: "'.$this->options->height.'",'.
		    'directionality : "'.$i18n->dir.'",'.
		    'document_base_url: "'.\Utils\URL::$base.'/",'.
		    'end_container_on_empty_block: true,'.
		    'valid_elements: "*[*]",'.
		    'invalid_elements: "'.$this->options->invalidelements.'",'.
		    'extended_valid_elements: "textarea[cols|rows|disabled|name|readonly|class|style|title]",'.
		    'valid_children : "+a[p|h1|h2|h3|h4|h5|h6|img|blockquote|address|aside]",'.
		    'remove_linebreaks : false,'.
		    'visualblocks_default_state: '.($this->options->visualblocks? 'true': 'false').','.
		    
		    // HTML5 formats
		    'schema: "html5",'.
		    'style_formats : ['.
				'{title : "'.$i18n->html5_h1.'", block : "h1"},'.
				'{title : "'.$i18n->html5_h2.'", block : "h2"},'.
				'{title : "'.$i18n->html5_h3.'", block : "h3"},'.
				'{title : "'.$i18n->html5_h4.'", block : "h4"},'.
				'{title : "'.$i18n->html5_h5.'", block : "h5"},'.
				'{title : "'.$i18n->html5_h6.'", block : "h6"},'.
				'{title : "'.$i18n->html5_p.'", block : "p"},'.
				'{title : "'.$i18n->html5_rtl.'", inline : "span", classes: "gxui-rtl-text", wrapper: true},'.
				'{title : "'.$i18n->html5_ltr.'", inline : "span", classes: "gxui-ltr-text", wrapper: true},'.
				'{title : "'.$i18n->html5_pre.'", block : "pre"},'.
				'{title : "'.$i18n->html5_article.'", block : "article", wrapper: true, merge_siblings: false},'.
				'{title : "'.$i18n->html5_hgroup.'", block : "hgroup", wrapper: true},'.
				'{title : "'.$i18n->html5_aside.'", block : "aside", wrapper: true},'.
				'{title : "'.$i18n->html5_blockquote.'", block : "blockquote", wrapper: true},'.
				'{title : "'.$i18n->html5_div.'", block : "div"},'.
				'{title : "'.$i18n->html5_section.'", block : "section", wrapper: true, merge_siblings: false},'.
				'{title : "'.$i18n->html5_header.'", block : "header", wrapper: true, classes: "gxui-header"},'.
				'{title : "'.$i18n->html5_footer.'", block : "footer", wrapper: true, classes: "gxui-footer"},'.
				'{title : "'.$i18n->html5_figure.'", block : "figure", wrapper: true}'.
		    '],'.
		    
		    'setup: function(editor) {';
			    foreach($this->buttons as $name => $b){
					$onready .= 
					'editor.addButton("'.$name.'", {'.
					    'title : "'.$b['title'].'",'.
					    'image : "'.$b['icon'].'",'.
					    'onclick : function() { '.$b['options']['onclick'].' }'.
					'});';
			    }
			    $onready .= 
			    ($this->binded('onchange')? 'editor.on("change", function(e) {'.$this->join('onchange').'});': '').
			    ($this->binded('onfocus')? 'editor.on("focus", function(e) {'.$this->join('onfocus').'});': '').
			    ($this->binded('onblur')? 'editor.on("blur", function(e) {'.$this->join('onblur').'});': '').
			'}'.
		'});';
		
		
		$Viewer->bind('onready', $onready);
		$this->style('width', $this->options->width);
		$this->style('height', $this->options->height);
		$this->addClass('gxui-spinner small');
		return '<div '.$this->unify().'><textarea name="'.$this->id.'" id="'.$this->id.'_textarea">'.$this->options->value.'</textarea></div>';
    }
}
?>