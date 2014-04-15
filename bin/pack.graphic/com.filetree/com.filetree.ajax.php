<?php
$ft = unserialize(urldecode($_POST->treeview));
switch($_POST->data['action']){
    case 'expand':
        $ft->expandFolder($_POST->data['folder']);
        break;
}

?>
