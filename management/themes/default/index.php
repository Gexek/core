<?php
$this->import("index.tpl");

$this->bind('onready', 
    '$(".gxa-icon-set>*, .gxa-icon").hover(function(){'. 
        '$(".gxa-icon", this).andSelf().addClass("hover");'. 
    '}, function(){'. 
        '$(".gxa-icon", this).andSelf().removeClass("hover");'. 
    '});'. 
    
    '$("#support-popup>ul.icons>li").not(".spliter").click(function(){'. 
        'var i = $(this).index();'. 
        'i = i > 0? i / 2: i;'. 
        '$($("#support-popup .content > div").hide().get(i)).show();'. 
    '}).first().click();'. 
    
    '$(".gxa-icon-set li[rel=\'#update-popup\'] a").click(function(e){'. 
        'if($(this).parent().hasClass("unauthed"))'. 
            'e.preventDefault();'. 
    '});'. 
    
    '$(".gxa-popup").append("<div class=\"arrow\"></div>");'. 
    
    '$(\'a[href*="kassit.com"],a[href*="gexek.com"]\').attr("target", "_blank");'. 
    
    'var title_t;'.
	'$(document).on("mousemove", "[title], [data-title]", function(e){'. 
    'if(!$(this).hasClass("clicked") && $(this).attr("title") || $(this).attr("data-title")){'. 
            'var ev = e, $this = this;'. 
            
            '$(this).addClass("still-hover");'. 
            'var title = $(this).attr("title") || $(this).attr("data-title");'. 
            '$(this).attr("data-title", title).removeAttr("title");'. 
            
            'var t = ev.pageY + 30, l = ev.pageX, r = $(window).width() - l - 20;'. 
            '$("#gxa-hint").css({top: t, right: r, left: "auto"}).find(">span").html(title);'. 
            '$("#gxa-hint .arrow").css({right: 8, left: "auto"});'. 
            
            'if(l - $("#gxa-hint").width() < 20){'. 
                '$("#gxa-hint").css({left: l-10, right: "auto"});'. 
                '$("#gxa-hint .arrow").css({left: 8, right: "auto"});'. 
            '}'. 
            
            'if(!title_t) title_t = setTimeout(function(){'. 
                '$("#gxa-hint").fadeIn(500);'. 
            '}, 500);'. 
        '}'.
    '}).on("mouseleave", "[title], [data-title]", function(){'. 
        '$(this).removeClass("still-hover").removeClass("clicked");'. 
        '$("#gxa-hint").hide();'. 
        'title_t = clearTimeout(title_t);'. 
    '});'.
	
	'$("[title], [data-title]").click(function(){'. 
        '$(this).addClass("clicked");'. 
        '$("#gxa-hint").hide();'. 
        'title_t = clearTimeout(title_t);'. 
    '});'
);

$this->source->assign('gexekicons',
    '<li class="gxui-popuper" rel="#gexek-popup"><a class="gxa-iconmenu gexek" title="'.$i18n->manage_icon_home_title.'"><div class="gxai"></div>'.$i18n->manage_icon_home.'</a></li>'.

	($Firewall->client->permitted('manage', 'website')? '<li class="spliter"></li>'.
	'<li><a class="gxa-iconmenu pages" title="'.$i18n->manage_icon_pages_title.'" href="'.$Admin->linkTo('pages').'"><div class="gxai"></div>'.$i18n->manage_icon_pages.'</a></li>': '').
	
	($Firewall->client->permitted('manage', 'security')? '<li class="spliter"></li>'.
	'<li><a class="gxa-iconmenu security" title="'.$i18n->manage_icon_security_title.'" href="'.$Admin->linkTo('security').'global/"><div class="gxai"></div>'.$i18n->manage_icon_security.'</a></li>': '').
	
	($Firewall->client->permitted('manage', 'website')? '<li class="spliter"></li>'.
	'<li><a class="gxa-iconmenu settings" title="'.$i18n->manage_icon_settings_title.'" href="'.$Admin->linkTo('settings/system/').'"><div class="gxai"></div>'.$i18n->manage_icon_settings.'</a></li>': '').
    
	($Firewall->client->permitted('manage', 'website')? '<li class="spliter"></li>'.
    '<li class="gxui-popuper" rel="#update-popup"><div class="gxai notify"></div><a class="gxa-iconmenu update" title="'.$i18n->manage_icon_update_title.'" href="'.$Admin->linkTo('update').'"><div class="gxai"></div>'.$i18n->manage_icon_update.'</a></li>': '')/*.
    
    '<li class="spliter"></li><li class="gxui-popuper" rel="#support-popup"><a class="gxa-iconmenu support" title="'.$i18n->manage_icon_showcase_title.'"><div class="gxai"></div>'.$i18n->manage_icon_support.'</a></li>'*/
);
?>