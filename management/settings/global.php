<?php
use Graphic\Controls as Controls;

if($_POST->submit){
    $Settings->site_name = $_POST->sitename;
    $Settings->site_desc = $_POST->sitedesc;
    $keys = preg_split("/[\r\n]+/", $_POST->sitekeys);
    $Settings->site_keys = implode(',', $keys);
    
    $Settings->compression = $_POST->compress? true: false;
    $Settings->caching = $_POST->caching? true: false;
    $Settings->cache_priod = $_POST->cache_priod;
    
    $Settings->locale = $_POST->locale;

    $Settings->themes = json_encode(array_keys(get_object_vars($_POST->themes)));
    //$Settings->theme_rpv = $_POST->theme_rpv? true: false;
    
    $Settings->save();
}

$keys = implode("\r\n", explode(',', $Settings->site_keys));

$form = new Graphic\Form('main_from', array('autocomplete' => false));

$sections = array();

/***** Site indentifications ******/
$sitename = new Controls\TextBox('sitename', array(
    'size' => '50', 'label' => $i18n->site_name, 'note' => $i18n->site_name_note, 'value' => $Settings->site_name
));
$sitedesc = new Controls\Textarea('sitedesc', array('label' => $i18n->site_desc, 'note' => $i18n->site_desc_note, 'value' => $Settings->site_desc));
$sitedesc->style('height', '80px');
$sitekeys = new Controls\Textarea('sitekeys', array('label' => $i18n->site_keywords, 'note' => $i18n->site_keywords_note, 'value' => $keys));
$sitekeys->style('height', '150px');

$sections['idn'] = new Graphic\FormSection(array(
    'caption' => $i18n->site_indentification,
    'expandable' => true,
    'note' => $i18n->site_indentification_help
));
$sections['idn']->add($sitename)->add($sitedesc)->add($sitekeys);
/****************************/


/***** Default locale settings ******/
$ls =  new Graphic\Controls\Combo('locale', array('label'=>$i18n->locale_select, 'height' => 100, 'multiple' => false, 'value' => (array)$Settings->locale));
foreach ($DataModule->locales->all as $locale)
	$ls->add($locale->name, $locale->caption);

$sections['locale'] = new Graphic\FormSection(array(
    'caption' => $i18n->default_locale, 'note' => $i18n->default_locale_help
));
$sections['locale']->add($ls);
/****************************/


/***** Theme settings ******/
$ts = new Graphic\GX\ThemeSelector('themes', array('selectlocation' => false, 'multitheme' => false, 'label' => $i18n->theme_for_site, 'value' => $Settings->themes));
$sections['theme'] = new Graphic\FormSection(array(
    'caption' => $i18n->theme_settings,
    'note' => $i18n->theme_settings_help
));
$sections['theme']->add($ts);
/****************************/



/***** Content & output settings ******/
$compress = new Controls\Checkbox('compress', array('label' => $i18n->content_compress, 'checked' => $Settings->compression));
$caching = new Controls\Checkbox('caching', array('label' => $i18n->content_cache, 'checked' => $Settings->caching));
$cache_priod = new Controls\TextBox('cache_priod', array(
    'size' => '2', 'label' => $i18n->content_cache_priod,
    'note' => $i18n->content_cache_enter_day, 'value' => $Settings->cache_priod
));
$sections['content'] = new Graphic\FormSection(array(
    'caption' => $i18n->content_settings,
    'note' => $i18n->content_settings_help
));
$sections['content']->add($compress)->add($caching)->add($cache_priod);
/****************************/


foreach($sections as $s) $form->add($s);
echo $form;
?>