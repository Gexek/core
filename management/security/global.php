<?php
if($_POST->submit){
    $adminurl = trim(str_replace(Utils\URL::$base, '', $_POST->admin_uri), '/');
    if(empty($adminurl)) $adminurl = 'admin';
    
    $authurl = trim(str_replace(Utils\URL::$base, '', $_POST->auth_uri), '/');
    if(empty($authurl)) $authurl = 'users';
    
    $Engine->pages->set('adminurl',  $adminurl.'/{rewrite}');
    $Engine->pages->set('authurl',  $authurl.'/{action}');
    $Engine->pages->save();
    
    //$Settings->recovery_email = $_POST->recovery_email;
    $Settings->register_allowed = $_POST->register_allowed;
    $Settings->activation_type = $_POST->activation_type;
    $Settings->save();

    $Engine->redirect(Utils\URL::$base.'/'.$i18n->locale.'/'.$adminurl.'/security/global/', false, false);
}

use Graphic\Controls as Controls;

$form = new Graphic\Form('global_form', array('autoComplete' => false, 'visuality'=>'bordered flat'));
$form->style('margin', '0');

/**** Managemenet Section ****/
$manage = new Graphic\FormSection(array('caption'=>$i18n->security_admin_settings, 'note' => $i18n->security_admin_help));
$admin_uri = new Controls\TextBox('admin_uri', array(
    'label'=>$i18n->security_admin_uri, 'ltr'=>true, 'size'=>12, 'value' => $Engine->pages->adminurl,
    'prefix'=>Utils\URL::trail(Utils\URL::$base), 'suffix'=>'/{rewrite}/', 'note'=> $i18n->security_admin_uri_note,
    'validchars'=>'a-z0-9', 'autocomplete' => false
));
$auth_uri = new Controls\TextBox('auth_uri', array(
    'label'=>$i18n->security_auth_uri, 'ltr'=>true, 'size'=>12, 'value' => $Engine->pages->authurl,
    'prefix'=>Utils\URL::trail(Utils\URL::$base), 'suffix'=>'/{action}/', 'note'=> $i18n->security_auth_uri_note,
    'validchars'=>'a-z0-9\/', 'autocomplete' => false
));
/*$rcvemail = new Controls\TextBox('recovery_email', array(
    'label'=>$i18n->security_recovery_email, 'ltr'=>true, 'size'=>35, 'value' => $Settings->recovery_email,
    'note'=> $i18n->security_recovery_email_note, 'validation'=>'email', 'allowempty'=>false
));*/
$manage->add($admin_uri)->add($auth_uri)/*->add($rcvemail)*/;
/* Management */


/**** managemenet Section ****/
$registeration = new Graphic\FormSection(array('caption'=>$i18n->security_register_settings, 'note' => $i18n->security_register_help));
$register_allowed = new Controls\Checkbox('register_allowed', array(
    'label'=>$i18n->security_register_allowed, 'note'=> $i18n->security_register_allowed_note,
    'checked' => $Settings->register_allowed
));
$activation_type = new Controls\Combo('activation_type', array(
    'label'=>$i18n->security_activation_type, 'note'=> $i18n->security_activation_type_note, 'value' => $Settings->activation_type
));
$activation_type->add('auto', $i18n->security_activation_type_auto);
$activation_type->add('admins', $i18n->security_activation_type_admins);

$registeration->add($register_allowed)->add($activation_type);
/* Management */



echo $form->add($manage)->add($registeration);
?>