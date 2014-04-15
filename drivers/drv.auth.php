<?php
$Firewall = new SysUtils\GXFirewall;
$perform = $Firewall->perform();
$Firewall->status->action = $Firewall->currentAction();

if($perform)
    $return_url = $Firewall->client->auth_return;
else
    $return_url = Utils\URL::create($i18n->locale.'/'.$Settings->auth_uri.$Firewall->status->action.'/', true);

$Engine->redirect($return_url, false, false);
?>