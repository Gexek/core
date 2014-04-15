<?php
if($_POST->submit){
    $html = $_POST->intro_html;
    Security::prepareInput($html);
    $Settings->intro_html = $html;
    $Settings->intro_enabled = $_POST->intro_enabled;
    $Settings->save();
}

$Engine->import('Graphic.Form');
$Engine->import('Graphic.Editors.TinyMCE');
$Engine->import('Graphic.Controls.Checkbox');

$form = new Form('introform', array('preventResend'=>false));

$enabled = new Checkbox('intro_enabled', $Settings->intro_enabled);
$enabled->label = $i18n->intro_enabled;
$form->add($i18n->intro, $enabled);

$TinyMCE = new TinyMCE('intro_html');
$html = $Settings->intro_html;
$TinyMCE->content = Security::repairInput($html);
$form->add($i18n->intro, $TinyMCE);

echo $form;
?>