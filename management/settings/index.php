<?php
$a1 = $a2 = '';
switch($_GET->values[0]){
    case 'system': $a1 = ' gxui-selected'; $file = 'global.php'; break;
    case 'plugins': $a2 = ' gxui-selected'; $file = 'plugins.php'; break;
}
?>
<style>
    ul#gxa-sec-menu{margin: 0; padding: 0; clear: both; overflow: hidden; clear: both; list-style: none;}
    ul#gxa-sec-menu > li{float: [i18n:align]; margin: 0 1px; padding: 5px 10px;}
</style>
<ul id="gxa-sec-menu">
    <li class="gxui-bevel gxui-clickable gxui-corner-top<?php echo $a1; ?>"><a href="<?php echo $Admin->linkTo('settings/system/'); ?>">[i18n:global_settings]</a></li>
    <li class="gxui-bevel gxui-clickable gxui-corner-top<?php echo $a2; ?>"><a href="<?php echo $Admin->linkTo('settings/plugins/'); ?>">[i18n:plugins]</a></li>
</ul>
<?php
include_once $file;
?>