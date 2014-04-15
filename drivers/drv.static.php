<?php
$this->bind('ongetbuffer', function($params){
    $params->content = SysUtils\SmartTag::compile($params->content);
});

function replacePath($string, $path){
    $out = new Utils\String($string);
    $out->replace('/url\s*\("?(?!http)([^\)\"]+)?"?\)/i', function($m) use($path){
    	return 'url("'.Utils\URL::create(dirname($path).'/'.$m[1], true).'")';
    });
    return $out;
}

$output = ''; $mimetype = '';
$resources = false;
if($_GET->resource){
    $resInfo = $Engine->parseResourceRequest($_GET->resource);
    $mimetype = Utils\File::$mime_types[$resInfo['type']];
    $resource_name = 'GX_'.strtoupper($resInfo['type']).'_'.$resInfo['id'];
    if(isset($_SESSION[$resource_name])){
        $resources = $_SESSION[$resource_name];
        unset($_SESSION[$resource_name]);
    }
    $_GET->path = false;
}

if($resources){
    foreach($resources as $file){
        if(file_exists($file)){
            if($resInfo['type'] == 'js')
                $output .= @Utils\File::read($file).';';
            else
                $output .= replacePath(@Utils\File::read($file), $file);
        }
    }
} else if($_GET->ui){
    $global_ui = 'bin/pack.graphic/pack.ui/com.gxui/com.gxui.style.css';
    $theme_ui = Utils\URl::trail($_GET->ui).'graphic/ui/gxui.css';
    
    $output .= 'html, body{direction: '.$i18n->dir.';}';
    $output .= replacePath(Utils\File::read($global_ui), $global_ui);
    $output .= replacePath(Utils\File::read($theme_ui), $theme_ui);
    $mimetype = Utils\File::mime($global_ui);
} else if($_GET->path){
    $output = Utils\File::read(rtrim($_GET->path, '/'));
    $mimetype = Utils\File::mime($_GET->path);
    if(is_string($output))
        $output = replacePath($output, $_GET->path);
} 

/* set the header information */
header("Content-Type: $mimetype; charset=utf-8");
echo $output;
?> 
