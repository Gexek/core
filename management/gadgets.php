<?php
$gadgets = array();
foreach($Engine->plugins->all as $plugin){
	if($Firewall->client->permitted('manage', 'plugin', $plugin->name))
    if($plugin->active && $plugin->implement('IGadgetPlugin')){
        $outputs = array();
        
        $gadget = $plugin->getGadget();
        extend($gadget, array('title' => 'New Gadget', 'icon' => '', 'events' => array(), 'markup'=> ''));
        $gadgets[] = '<span id="'.$plugin->name.'_gadget" class="gxa-gadget" title="'.$gadget['title'].'" '.
        'style="background-image: url('.$gadget['icon'].');">'.$gadget['markup'].'</span>';
        
        $events = array();
        foreach($gadget['events'] as $e => $c)
            $events[] = 'bind("'.$e.'", function(e){'.$c.'})';
        if(count($events) > 0)
            $Viewer->bind('onready', '$("#'.$plugin->name.'_gadget").'.implode('.', $events).';');
    }
    
}

echo '<div id="gxa-gadgets-poniter"></div>'.implode('<hr />', $gadgets);
?>