<?php
$Engine->import('Graphic.Accordion');
$accordion = new Accordion('admin_left');

$tabs = array(); $i = -1;

$tabs[++$i] =  new AccordionTab('admin_left_dashboard', '[i18n:general_management]');
$tabs[$i]->add($i18n->website, $Admin->linkTo('website'));
$tabs[$i]->add($i18n->users, $Admin->linkTo('users'));
$tabs[$i]->add($i18n->plugins, $Admin->linkTo('plugins'));
$tabs[$i]->add($i18n->categorys, $Admin->linkTo('departments'));
$tabs[$i]->add($i18n->intro, $Admin->linkTo('intro'));
$tabs[$i]->add($i18n->update, $Admin->linkTo('update'));

// Loop through plugins
foreach($Engine->plugins->all as $plugin){
    // If plugin is active and implements API to be managed
    if($plugin->active && $plugin->implement('IManagablePlugin')){
        // get administration items
        $mi = $plugin->getManageItems();
        // if any items had been specified
        if(isset($mi->items) && count($mi->items) > 0){
            // Create a new tab for plugin
            $tabs[++$i] =  new AccordionTab('admin_left_'.$plugin->name, $i18n->{$plugin->name.'_plugin_name'});
            // Add administration items to tab
            foreach($mi->items as $caption=>$file)
                $tabs[$i]->add($caption, $Admin->linkTo("plugin-$plugin->name/".URL::encode($file)), '_self');
            
            // Mark this tab as active if it own management area
            if($_GET->plugin && $_GET->plugin == $plugin->name)
                $accordion->options['active'] = $i;
        }
    }
}

foreach($tabs as &$tab) $accordion->add($tab);
echo $accordion;
?>