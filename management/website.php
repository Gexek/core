<?php
if($_POST->submit){
    $Settings->site_name = $_POST->sitename;
    $Settings->site_desc = $_POST->sitedesc;
    $keys = preg_split("/[\r\n]+/", $_POST->sitekeys);
    $Settings->site_keys = implode(',', $keys);
    
    $Settings->compression = $_POST->compress? true: false;
    $Settings->caching = $_POST->caching? true: false;
    $Settings->cache_priod = $_POST->cache_priod;
    
    $Settings->themes = implode(',', array_keys($_POST->themes));
    $Settings->theme_rpv = $_POST->theme_rpv? true: false;
    
    $Settings->save();
}
$keys = implode("\r\n", explode(',', $Settings->site_keys));

$Engine->import('Graphic.Form');
$Engine->import('Graphic.Controls.TextBox');
$Engine->import('Graphic.Controls.Textarea');
$Engine->import('Graphic.Controls.Checkbox');
$Engine->import('Graphic.GX.ThemeSelector');

$form = new Form('main_from');
$form->addSection($i18n->site_indentification, $i18n->site_indentification_help);
$form->addSection($i18n->theme_settings, $i18n->theme_settings_help);
$form->addSection($i18n->content_settings, $i18n->content_settings_help);



$label = new Label($i18n->site_id);
$span = new Tag('span', 'gxid');
$span->prefix = $label;
$span->append('<b>'.$Settings->gxid.'</b>');
$form->add($i18n->site_indentification, $span);

$input = new TextBox('sitename', $Settings->site_name);
$input->label = $i18n->site_name;
$input->options['size'] = 50;
$form->add($i18n->site_indentification, $input);

$input = new Textarea('sitedesc', $Settings->site_desc);
$input->label = $i18n->site_desc;
$form->add($i18n->site_indentification, $input);

$input = new Textarea('sitekeys', $keys);
$input->label = $i18n->site_keywords;
$form->add($i18n->site_indentification, $input);



$input = new Checkbox('compress', $Settings->compression);
$input->label = $i18n->content_compress;
$form->add($i18n->content_settings, $input);

$input = new Checkbox('caching', $Settings->caching);
$input->label = $i18n->content_cache;
$form->add($i18n->content_settings, $input);

$input = new TextBox('cache_priod', $Settings->cache_priod);
$input->label = $i18n->content_cache_priod;
$input->width = 2;
$input->suffix = " $i18n->content_cache_day";
$form->add($i18n->content_settings, $input);



$ts = new ThemeSelector('themes', $i18n->theme_for_site);
$ts->multiTheme = true;
$defs = preg_split('/,/', $Settings->themes, -1, PREG_SPLIT_NO_EMPTY);
$ts->setDefaults($defs);
$form->add($i18n->theme_settings, $ts);

$input = new Checkbox('theme_rpv', $Settings->theme_rpv);
$input->label = $i18n->theme_random_pv;
$form->add($i18n->theme_settings, $input);

echo $form;
?>