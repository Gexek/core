<?php
$Firewall = new SysUtils\GXFirewall;
$permitted = true; 

if(!$this->authmode){
	$Firewall->client->rememberURL();
	$Firewall->collectRules();
	
    if($this->adminmode)
	    $permitted = $Firewall->client->permitted('manage');
    else {
    	$of = $Engine->getOutputFilters();
    	if(!$of['homepage']){
	    	if(!$of['page']) $Engine->setStatus(404);
	    	else $permitted = $Firewall->client->permitted('access', 'page', $of['page']);
    	}
    }
} else
	$Engine->setStatus(403);

include_once 'bin/viewer.class.php';
$Viewer = new GXViewer;

if(!$permitted) $Viewer->blockClient();

$Viewer->initial();

$of = $Engine->getOutputFilters();

if($of['page']){
	$pg_info = $DB->select('rewrites', array('filter' => '"'.Utils\URL::currentURI().'" REGEXP cRule', 'limit' => 1));
	
	if(!empty($pg_info['cTitle'])) {
		$Viewer->title = $pg_info['cTitle'];
		$Viewer->setMeta('og:title', $pg_info['cTitle']);
	}
	
	if(!empty($pg_info['cDesc'])) {
		$Viewer->setMeta('description', $pg_info['cDesc']);
		$Viewer->setMeta('og:description', $pg_info['cDesc']);
	}
	if(!empty($pg_info['cKeywords'])) 
		$Viewer->setMeta('keywords', $pg_info['cKeywords']);
}

$Viewer->generate();

$this->bind('ongetbuffer', function($params){
    global $Viewer;
    $params->content = SysUtils\SmartTag::compile($params->content);
    $params->content = SysUtils\SmartTag::unescape($params->content);
    $params->content = $Viewer->unescape($params->content);
});

echo 
'<!doctype html>'.
'<html dir="[i18n:dir]">'.
    '<head>'.
        $Viewer->joinMetatags().
        '<base href="'.Utils\URL::$base.'/" />'.
        '<title>'.$Viewer->title.'</title>'.
        '<link rel="icon" type="image/png" href="static/favicon.png/" />'.
        '<link rel="stylesheet" type="text/css" href="'.$this->createResourceLink('CSS').'" />'.
		'<script type="text/javascript" src="http://cdn.gexek.com/js/modernizr/modernizr.2.7.1.js"></script>'.
		'<script type="text/javascript" src="http://cdn.gexek.com/js/browser.detect.js"></script>'.
    '</head><body>'.
    '<div id="gxui-overlay" class="gxui-overlay"></div>';
 	
	$content = /*str_replace(array(
		"\r\n", "\r", ">\n"
	), array(
		"\n", "\n", '>'
	), */$Viewer->_toString()/*)*/;
    echo $content;
    
    if(class_exists('Graphic\Dialog'))
        foreach(Graphic\Dialog::$Instances as &$dlg)
            echo $dlg;

    echo
    $Viewer->getContents('append').
    '<script type="text/javascript" src="'.$this->createResourceLink('JS').'"></script>'.
    '<script type="text/javascript">'.
        $Viewer->join('ondeclare').
        'var gxTotalImages = [];'.
        'var gxLoadedImages = 0;'.
        'var gxFailedImages = 0;';
        if(class_exists('Data\Ajax'))
            foreach(Data\Ajax::$Instances as &$ajax)
                echo $ajax->declaration();
          echo 
        'function ongxcomplete(){'.
            'if(gxLoadedImages+gxFailedImages == gxTotalImages.length){'.
                $Viewer->join('oncomplete').
                ';$.fn.gxscroll();'.
            '}'.   
        '}'. 
        '$(document).ready(function(e){'.
            '$("#gxui-overlay").height($(document).height()).'.
            'beforeShow(function(){'.
                '$("body").css({overflow: "hidden"});'.
            '}).beforeHide(function(){'.
                '$("body").css({overflow: "auto"});'.
            '});'.
            '$("input, textarea").emptyValue({emptyClass: "empty-input"});'.
            '$(window).resize(function(){ $.fn.gxscroll(); });'.
            
            $Viewer->join('onready').
            
            '$("*").each(function(){'.
                'var bg = $(this).css("background-image");'.
                'if(bg != "none"){'.
                    'bg = bg.replace(/^url|[\(\)\"]/g, "").'.
                    'replace(/css-driver\/locale-([a-z]{2})_([A-Z]{2})\//, "");'.
                    'if($.inArray(bg, gxTotalImages) == -1){'.
                        'gxTotalImages.push(bg);'.
                    '}'.
                '}'.
            '});'.
            '$.each(gxTotalImages, function(i, v){'.
                'var img = new Image();'.
                '$(img).load(function(){'.
                    '++gxLoadedImages;'.
                    'ongxcomplete();'.
                '}).error(function(){'.
                    '++gxFailedImages;'.
                    'ongxcomplete();'.
                '});'.
                'img.src = v;'.
                'if(img.completed) img.trigger("onload");'.
            '});'. 
       
            // GXUI preparation
            '$("button, input[type=submit], input[type=reset], input[type=button], a.gxui-button").not(".ui-button").'.
            'addClass("gxui-button gxui-clickable");'.
            
            '$("input[type=text], input[type=password]").addClass("gxui-border");'.
            
            '$("header").addClass("gxui-header");'.
            '$("footer").addClass("gxui-footer");'.
            
        '}).click(function(e){'.
            $Viewer->join("onclick").
            	'var $target = $(e.target);'.
            	'$popuper = $target.closest(".gxui-popuper");'.
            	'if($popuper.length){'.
            		'var $popup = $($popuper.attr("rel"));'.
		            'if($popup.length){'.
		                'var coords = $popup.attr("data-coords"),'.
		                's = $popup.attr("data-side"),'.
		                'y = 0, x = 0, offset = $popuper.offset(),'.
		                't = $popuper.outerHeight() + offset.top + 13, l = offset.left,'.
		                'r = $(window).width() - l - $popuper.outerWidth() - 10;'.
		                
		                'if(!s) s = "[i18n:align]";'.
		                
		            	'if(coords != "css"){'.
			                'if(coords){'.
			                    'coords = String.split(coords, ",");'.
			                    'x = coords[0]; y = coords[1];'.
			                '} else {'.
			                    'y = t;'.
			                    'if($(window).width() - l < $popup.width() + 20)'.
			                        's = "right";'.
			                    'else if($(window).width() - r < $popup.width() + 20)'.
			                        's = "left";'.
			                    'if(s == "right") x = r;'.
			                    'else x = l;'.
			                '}'.
			                
			                'if($popup.css("position") == "fixed")'.
			                    'y -= $(window).scrollTop();'.
		            		
		            		'$popup.css("top", y+"px").css(s, x+"px").toggle("fast").'.
		                	'find(".arrow").css({left: "auto", right: "auto"}).css(s, $popuper.width()/2+"px");'.
		            	'} else '.
		            		'$popup.toggle("fast");'.
		                '$(".gxui-popup").not($popup).hide();'.
		                'e.stopPropagation();'.
		                //'e.preventDefault();'.
		            '}'.
            	'} else {'.
					'$(".gxui-popup").hide();'.         			
            	'}'.
        '}).keypress(function(e){'.
            $Viewer->join("onkeypress").
        '}).on("click", ".gxui-popup", function(e){'.
            'e.stopPropagation();'.
        '}).on("click", ".gxui-selectable", function(e){'.
			'var sel = getSelection().toString();'.
    		'if(!sel) $(this).toggleClass("gxui-selected");'.
        '}).on("mouseenter", ".gxui-clickable", function(){'.
            '$(this).addClass("gxui-hover");'.
        '}).on("mouseleave", ".gxui-clickable", function(){'.
            '$(this).removeClass("gxui-hover");'.
        '}).on("mousedown", ".gxui-clickable", function(){'.
            '$(this).addClass("gxui-down");'.
        '}).on("mouseup", ".gxui-clickable", function(){'.
            '$(this).removeClass("gxui-down");'.
        '})'.
        ';';
        echo $Viewer->join('onload');
echo '</script></body></html>';
?>