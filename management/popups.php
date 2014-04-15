<div id="gexek-popup" class="gxa-popup gxui-popup">
	<div class="head icon-list"><?php echo $i18n->plugins_features;  ?></div>
    <div class="content">
    <?php
    //echo $accordion;
    foreach($Engine->plugins->all as $plugin){
    	if($Firewall->client->permitted('manage', 'plugin', $plugin->name))
        // If plugin is active and implements API to be managed
        if($plugin->active && $plugin->implement('IManagablePlugin')){
            echo '<div class="gxui-clearfix gxui-bordered gxui-flat gxui-light">'.
                    '<p title="'.$i18n->{$plugin->name.'_plugin_desc'}.'"><b>'.$i18n->{$plugin->name.'_plugin_name'}.'</b></p>';
            // get administration items
            $mi = $plugin->getManageItems();
            // if any items had been specified
            if(is_array($mi) && count($mi) > 0){
                echo '<ul>';
                foreach($mi as $url=>$item){
                    $caption = isset($item[1])? $item[1]: null;
                    if(!is_null($caption)){
                        $url = $Admin->linkTo($plugin->name.'/'.Utils\URL::encode($url));
                        $caption = $i18n->$caption? $i18n->$caption: $caption;
                        echo '<li class="icon-leaf"><a href="'.$url.'">'.$caption.'</a></li>';
                    }
                }
                echo '</ul>';
            }
            echo '</div>';
        }
    }
    ?>
    </div>
    <div class="foot">
        <a class="gxai logout" href="<?php echo $Firewall->formAction('logout'); ?>" title="<?php echo $i18n->logout;  ?>"></a>
    </div>
</div>

<?php if($Firewall->client->permitted('manage', 'website') && $_SESSION['gxa_updresult'] == 401):?>
<div id="update-popup" class="gxa-popup gxui-popup">
    <p class="gxui-error"><?php echo $i18n->support_unauthorized;  ?></p>
    <div class="gxui-panel"><b><?php echo $i18n->support_may_expire;  ?></b></div>
    <?php
    $form = new Graphic\Form('support_password_from', array('ajaxsubmit'=>true,'autoComplete' => 'off', 'visuality' => ''));
    $form->ajax->data('manage', 'save_support_password');
    $form->ajax->bind('oncomplete', '$("#update-popup").hide();'.$Admin->checkUpdate());

    $support_pass = new Graphic\UI\Controls\TextBox('support_password', array(
        'size' => '50', 'type'=>'password', 'ltr' => true, 'label' => $i18n->support_password,
        'note' => $i18n->support_password_note, 'value' => $Settings->support_password
    ));
    $section = new Graphic\FormSection();
    $section->add($support_pass);
    $form->add($section);
    echo $form;
    ?>
</div>
<?php endif; ?>