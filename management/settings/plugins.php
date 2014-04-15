<style>
    .plugin{margin: 5px; padding: 5px; opacity: 0.7; filter: alpha(opacity=70);}
    .plugin.gxui-selected{opacity: 1.0; filter: alpha(opacity=100);}
    .plugin .gxui-control{width: 20px; float: [i18n:align]; height: 90px; min-width: 20px;}
    .plugin p{margin: 0;}
</style>

<?php
$Engine->import('management/scripts/plugins.js');
use Graphic\Controls as Controls;

if($_POST->submit){
    foreach($Settings->plugins as &$plugin)
        $plugin->active = isset($_POST->plugin_state[$plugin->name])? true: false;
    $Settings->save();
}

ob_start();
foreach($Settings->plugins as $plugin):
    $checkbox = new Controls\Checkbox('plugin_state['.$plugin->name.']', array('checked' => $plugin->active));
?>

<div class="plugin gxui-flat gxui-light gxui-float gxui-corner gxui-g30 gxui-bordered gxui-clickable">
    <?php echo $checkbox.'<b>'.$i18n->{$plugin->name.'_plugin_name'}.'</b>'; ?>
    <p><?php echo $i18n->{$plugin->name.'_plugin_desc'}; ?></p>
</div>

<?php
endforeach;

$form = new Graphic\Form('plugin_form');
$form->bind('onreset', 'refreshList();');
$sections = array();

/***** Theme settings ******/
$sections['theme'] = new Graphic\FormSection(array(
    'caption' => $i18n->manage_icon_plugins_title,
    'note' => $i18n->manage_icon_plugins_help
));
$sections['theme']->add(ob_get_clean());
/****************************/

foreach($sections as $s) $form->add($s);
echo $form;
?>