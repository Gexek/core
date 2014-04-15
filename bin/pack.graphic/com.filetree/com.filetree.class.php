<?php
namespace Graphic;

class FileTree extends Graphic {
    private $data, $ajax, $master = null;
    
    public function __construct($name, $options = array()){
        global $i18n;
        extend($options, array(
        	'id'			 	=> $name,
            'root'           	=> '',
            'folderevent'   	 => 'click',
            // list, thumbnail
            'view'           	=> 'list',
            'expandspeed'    	=> 500,
            'collapsespeed'  	=> 500,
            'autoopen'       	=> true,
            'expandeasing'   	=> null,
            'collapseeasing' 	=> null,
            'multiselect'    	=> false,
            'multifolder'    	=> true,
            'showfiles'      	=> true,
            'showfolders'    	=> true,
            'showroot'       	=> false,
            'fileclass'      	=> '',
            'loadmessage'    	=> $i18n->loading,
            'ajax'           	=> 'function(){}',
            'filter'         	=> '',
            'thumbsfolder'	 	=> null,
            'thumbssuffix'	 	=> '',
            'thumbwidth'		=> 100
        ));
        $options['root'] = trim($options['root'], '/\\');
        $options['to'] = urlencode(serialize($this));
        parent::__construct($name, $options);
        
        $this->ajax = new \Data\Ajax();
        $this->ajax->setCaller($this);
        
        $this->ajax->arg('tree');
        $this->ajax->arg('data', true);
        $this->ajax->arg('callback');
        $this->ajax->bind('oncomplete', 'callback(XHR.responseText);');
        
        $this->options->ajax = "js:$this->ajax";
        
        $this->clientCalls = array(
            'getFilename' => "$this->name.getFilename();",
            'removeSelected' => "$this->name.removeSelected()",
            'reload' => "$this->name.reload()",
            'getSelectedFiles' => "$this->name.getSelectedFiles()",
            'open' => new \SuperClosure(function ($obj, $args){
            	$folder = $obj->fixJS($args[0], 'value');
				return "$obj->name.open($folder);";
            }),
            'openFolder' => new \SuperClosure(function ($obj, $args){
            	$folder = $obj->fixJS($args[0], 'value');
            	$sel = isset($args[1])? (int)$args[1]: 0;
            	return "$obj->name.openFolder($folder, $sel);";
            }),
            'deleteSelected' => "$this->name.deleteSelected()"
        );
    }
    
    private function getThumb($filename){
    	if(file_exists($filename)){
	    	$ext = \Utils\File::ext($filename);
	    	$size = $this->options->thumbwidth;
	    	$suffix = $this->options->thumbssuffix;
	    	$thumbname = $this->options->thumbsfolder."/$filename$suffix/x$size.$ext";
	    	if(file_exists($thumbname))	return $thumbname;
    	}
    	return false;
    }

    public function expandFolder($folder){
        $root = \Utils\File::trail(\Utils\File::$base);
        $folder = urldecode($folder);
        if(file_exists("$root/$folder")){
            $folders = $files = array();
            if($this->options->showfolders)
                $folders = \Utils\File::scandir("$root/$folder", \Utils\File::SCAN_DIRS, 0, $this->options->filter);
            if($this->options->showfiles)
                $files = \Utils\File::scandir("$root/$folder", \Utils\File::SCAN_FILES, 0, $this->options->filter);
            
            natcasesort($folders); natcasesort($files);
            $list = array_merge($folders, $files);
            
            if( count($list) > 0 ) {
                echo "<ul class=\"graphic-filetree\">";

                foreach($list as $file) {
                    $fpath = "$root/$folder/$file";
                    $ext = \Utils\File::ext($fpath);
                    if(file_exists($fpath)){
                        $img = $class = '';
                        
                        if(is_dir($fpath)) $class = 'ext_dir collapsed';
                        else if(\Utils\File::isImage($fpath)) $class = 'file ext_img';
                        else $class = 'file ext_'.$ext;
                        
                        switch($this->options->view){
                            case 'thumbnail':
                                if(\Utils\File::isImage($fpath)) {
                                	$class .= ' ext_img';
                                	$thumb = $this->getThumb($folder.'/'.$file);
                                	if(!is_null($this->options->thumbsfolder) && $thumb)
                                		$img = '<div class="image_thumb" style="background-image: url('.$thumb.');"></div>';
                                	else
                                    	$img = '<div class="image_thumb"><img src="'.$folder.'/'.$file.'" /></div>';
                                };
                                break;
                        }
                        
                        $class .= ' '.$this->options->view;
                        
                        echo
                        '<li class="'.$class.'">'.
                            '<div class="file '.$this->options->fileclass.'" rel="'.$folder.'/'.$file.'">'.$img.'<label>'.$file.'</label>'.'</div>'.
                        '</li>';
                    }
                }
                echo "</ul>";	
            }
        }
    }
    
    public function linkTo(FileTree $master){
        $this->master = $master;
        $master->bind('onselect', $this->call('open', $master->call('getFilename')));
        if($this->options->view == 'thumbnail')
        	$this->bind('onfolderdblclick', $master->call('openFolder', array('js:filename', true)));
    }
    
    public function __toString(){
        global $Viewer;
        $this->ajax->data('treeview', $this);
        if(!is_null($this->master))
            $this->options->autoopen = false;
        $Viewer->bind('ondeclare', "var $this->name;");
        $Viewer->bind('onready',
            $this->name.' = $("#'.$this->id.'").'.
            'fileTree('.$this->parseOptions().')'.
            ($this->binded('onload')? '.bind("load", function(e, filename){'.$this->join('onload').'})': '').
            ($this->binded('onloaded')? '.bind("loaded", function(e, filename){'.$this->join('onloaded').'})': '').
            ($this->binded('onselect')? '.bind("select", function(e, files){'.$this->join('onselect').'})': '').
        	($this->binded('ondeselect')? '.bind("deselect", function(e, files){'.$this->join('ondeselect').'})': '').
            ($this->binded('onclick')? '.bind("click", function(e){'.$this->join('onclick').'})': '').
        	($this->binded('onfolderdblclick')? '.bind("folderdblclick", function(e, filename){'.$this->join('onfolderdblclick').'})': '').
            ($this->binded('onfileclick')? '.bind("fileclick", function(e, filename){'.$this->join('onfileclick').'})': '').
            ($this->binded('onfiledblclick')? '.bind("filedblclick", function(e, filename){'.$this->join('onfiledblclick').'})': '').
            ($this->binded('ondelete')? '.bind("delete", function(e, files){'.$this->join('ondelete').'})': '').
            ';'.(!is_null($this->master)? $this->call('open', $this->master->options->root): '')
        );
        return '<div '.$this->unify().'>'.(
            !$this->options->showroot || $this->options->view == 'thumbnail'? '':
            '<ul class="graphic-filetree">
                <li class="ext_home list expanded">
                    <div class="file" rel="'.$this->options->root.'"><label>'.$this->options->root.'</label></div>
                </li>
            </ul>').
        '</div>';
    }
}
?>
