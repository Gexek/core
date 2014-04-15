<?php
$grid = new Graphic\Grid('security_groups', array(
    'caption'       => $i18n->users_groups,
    'multiselect'   => true,
    'table'         => 'users_groups',
    'primary'       => 'nID',
    'order'         => 'BINARY(cName)'
));
$cols = array();
$cols[] = new Graphic\GridColumn('nID', array('editable'=>false, 'caption'=>'', 'hidden'=>true, 'datatype'=>'autonumber', 'primary'=>true));
$cols[] = new Graphic\GridColumn('cName', array('caption'=>$i18n->users_groups_name, 'width'=>250, 'allownull' => false));
$cols[] = new Graphic\GridColumn('cDesc', array('caption'=>$i18n->users_groups_desc));

foreach($cols as &$col) $grid->addColumn($col);
echo $grid;
?>