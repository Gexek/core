<?php
global $upd_img_path;
$upd_img_path = 'management/update/images';

$setModeAjax = new Data\Ajax(array('url' => 'management/update/ajax.php')); 
$setModeAjax->arg('action', 'mode');
$setModeAjax->arg('mode', true);

$ajaxCall = new Data\Ajax(array('url' => 'management/update/ajax.php', 'async' => false)); 
$ajaxCall->arg('action', true);
$ajaxCall->arg('successCallback');
$ajaxCall->arg('errorCallback');
$ajaxCall->arg('id', true);
$ajaxCall->arg('file', true);
$ajaxCall->arg('size', true);
$ajaxCall->arg('type', true);
$ajaxCall->arg('name', true);
$ajaxCall->arg('ver', true);
$ajaxCall->bind('onsuccess', 'successCallback(XHR, result);');
$ajaxCall->bind('onerror', 'errorCallback(XHR, status)');

$Engine->import('management/update/styles.css');
$Engine->import('management/update/index.js');
$Engine->import('management/update/index.css');

$Viewer->bind('ondeclare', 'var gxu_ajax_call = '.$ajaxCall.'; var gxu_setmode_ajax = '.$setModeAjax.';');
$Viewer->bind('onready', '$("#gxa-content").sizeToPixel("height");');
?>
<img src="management/update/images/checked.png" class="cache-image"	alt="" />
<img src="management/update/images/cross.png" class="cache-image" alt="" />

<div id="update-wizard" class="gxui-clearfix">
	<div id="wizard-anchor" class="gxui-clearfix gxui-flat">
		<ul>
			<li class="gui-inline-block icon-[i18n:align]" id="welcome-anchor"><b>[i18n:update_1st]</b><span>[i18n:update_1st_desc]</span></li>
			<li class="gui-inline-block icon-[i18n:align]" id="check-anchor"><b>[i18n:update_2nd]</b><span>[i18n:update_2nd_desc]</span></li>
			<li class="gui-inline-block icon-[i18n:align]" id="install-anchor"><b>[i18n:update_3rd]</b><span>[i18n:update_3rd_desc]</span></li>
			<li class="gui-inline-block icon-[i18n:align]" id="review-anchor"><b>[i18n:update_4th]</b><span>[i18n:update_4th_desc]</span></li>
		</ul>
	
		<div id="wizard-buttons">
			<span class="gxui-button gxui-clickable icon-search" data-role="recheck">[i18n:update_reconnect]</span>
			<span class="gxui-button gxui-clickable icon-search" data-role="check">[i18n:update_check]</span>
			<span class="gxui-button gxui-clickable icon-cog" data-role="install">[i18n:update_install]</span>
			<span class="gxui-button gxui-clickable icon-check" data-role="apply">[i18n:update_apply]</span>
		</div>
	</div>
	

	<div id="wizard-body" class="gxui-clearfix"></div>
</div>