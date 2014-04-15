<?php
namespace SysUtils;

class Administration extends SysUtil{
    private $url, $uri, $autoCheckAjax, $navs = array();
    
    public function __construct(){
		global $DB, $i18n, $Engine;
		
		$this->autoCheckAjax = new \Data\Ajax(array(
		    'url' => 'management/update/ajax.php'
		));
		$this->autoCheckAjax->data('action', 'check');
		$this->autoCheckAjax->bind('onsend', '$("#gxa-menu>ul>li[rel=\'#update-popup\']").removeClass("unauthed avail").addClass("checking");');
		$this->autoCheckAjax->bind('oncomplete', 
			'$("#gxa-menu>ul>li[rel=\'#update-popup\']").removeClass("checking");'.
		    'xml = XHR.responseText;'.
		    'if(XHR.status == 401)'.
			'$("#gxa-menu>ul>li[rel=\'#update-popup\']").addClass("unauthed").find(".gxa-iconmenu").attr("title", "'.$i18n->update_unauthed.'"); '.
		    'else if(XHR.status == 200)'.
			'$("#gxa-menu>ul>li[rel=\'#update-popup\']").addClass("avail").find(".gxa-iconmenu").attr("title", "'.$i18n->update_available.'"); '
		);
		
		
		$rs = $DB->select('rewrites', array('filter' => 'cName= "core::admin-url"', 'order'=>'cName'))->fetch(\PDO::FETCH_ASSOC);
		$this->uri = preg_replace('#\(\(\(\[a-z\]\{2\}\)_\(\[A-Z\]\{2\}\)\)\/\)\?(.*)\/\(\(\.\*\)\/\)\?#', '\1', $rs['cRule']);
		
		$this->uri = $Engine->pages->adminurl;
    }
    
    public function __get($var){
		if(in_array($var, array('uri')))
		    return $this->$var;
    }
    
    public function linkTo($uri = '', $absolute = false){
		global $Settings, $i18n;
		$url = $i18n->locale.'/'.\Utils\URL::trail($this->uri.'/'.$uri);
		if($absolute) $url = \Utils\URL::$base."/$url";
		return $url;
    }
    
    private function appendUpdatePopup(){
		$this->autoCheckAjax->data('newpass', '$("#gxa-upd-newpass").val()');
		return '
			$("body").append(
			    
			);
			$("#gxa-set-updpass").unbind("click").click(function(){
			    $(".gxa-icon.update").removeClass("unauthed");
			    $("#update-popup").hide();
			    '.$this->autoCheckAjax.'
			});
		';
    }
    
    public function autoCheckUpdate(){
		global $i18n, $Engine, $Viewer;
		if(!isset($_SESSION['gxa_updresult']) || !isset($_SESSION['gxa_updexpire'])){
		    $_SESSION['gxa_updresult'] = 204;
		    $_SESSION['gxa_updexpire'] = 0;
		}
		
		$adurl = \Utils\URL::$base.'/'.$this->linkTo('update');
		if(!(\Utils\URL::current() == $adurl || $_SESSION['gxa_updexpire'] > time())){
		    $Viewer->bind('onready', 'setTimeout(function(){'.$this->autoCheckAjax.'}, 3000);');
		} else{
		    if($_SESSION['gxa_updresult'] == 401)
			$Viewer->bind('onready', '$("#gxa-menu>ul>li[rel=\'#update-popup\']").addClass("unauthed").find(".gxa-iconmenu").attr("title", "'.$i18n->update_unauthed.'");');
		    else if($_SESSION['gxa_updresult'] == 200)
			$Viewer->bind('onready', '$("#gxa-menu>ul>li.update").addClass("avail").find(".gxa-iconmenu").attr("title", "'.$i18n->update_available.'");');
		    else
			$Viewer->bind('onready', '$("#gxa-menu>ul>li.update").find(".gxa-iconmenu").attr("title", "'.$i18n->update.'");');
		}
    }
    
    public function checkUpdate(){
		return $this->autoCheckAjax;
    }
    
    public function setNav($caption, $url = null){
		$this->navs[count($this->navs)-1] = array($caption, $url);
    }
    
    public function pushNav($caption, $url = null){
		$this->navs[] = array($caption, $url);
    }
    
    public function drawNavs(){
		global $Engine, $i18n, $Admin, $Viewer;
		$nav = array();
		$nav[] = '<a href="'.$this->linkTo('').'">'.$i18n->admin_dashboard.'</a>';
		foreach($this->navs as $n){
		    if(is_null($n[1])) $nav[] = $n[0];
		    else $nav[] = '<a href="'.$n[1].'">'.$n[0].'</a>';
		}
		$Viewer->assign('nav', implode('<span class="gxui-inline-block">></span>', $nav));
    }
}
?>