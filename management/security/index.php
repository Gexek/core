<?php
$a1 = $a2 = $a3 = $a4 = '';
switch($_GET->values[0]){
    case 'global': $a4 = ' gxui-selected'; $file = 'global.php'; break;
    case 'groups': $a1 = ' gxui-selected'; $file = 'groups.php'; break;
    case 'users':  $a2 = ' gxui-selected'; $file = 'users.php'; break;
    case 'rules':  $a3 = ' gxui-selected'; $file = 'rules.php'; break;
}
?>
<style>
    ul#gxa-sec-menu{margin: 0; padding: 0; clear: both; overflow: hidden; clear: both; list-style: none;}
    ul#gxa-sec-menu > li{float: [i18n:align]; margin: 0 1px; padding: 5px 10px;}
</style>
<ul id="gxa-sec-menu">
    <li class="gxui-bevel gxui-clickable gxui-corner-top<?php echo $a4; ?>"><a href="<?php echo $Admin->linkTo('security/global/'); ?>">[i18n:security_global_settings]</a></li>
    <li class="gxui-bevel gxui-clickable gxui-corner-top<?php echo $a1; ?>"><a href="<?php echo $Admin->linkTo('security/groups/'); ?>">[i18n:users_groups]</a></li>
    <li class="gxui-bevel gxui-clickable gxui-corner-top<?php echo $a2; ?>"><a href="<?php echo $Admin->linkTo('security/users/'); ?>">[i18n:users]</a></li>
    <li class="gxui-bevel gxui-clickable gxui-corner-top<?php echo $a3; ?>"><a href="<?php echo $Admin->linkTo('security/rules/'); ?>">[i18n:security_rules]</a></li>
</ul>
<?php
include_once $file;
?>