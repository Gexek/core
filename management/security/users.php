<style>
    #gdlg-groups{width: 70%;}
    
    #gdlg-result{padding-right: 10px; width: 50%; float: left; margin: 0 0 3px; -webkit-user-select: none; -moz-user-select: none; user-select: none;}
    #gdlg-result span{cursor: pointer; float: right; padding: 0; width: 100%;}
    #gdlg-result span.gxai{width: 16px; height: 16px; float: right; margin: 4px 0 0 5px;}
    #gdlg-result span .icon{background-position: -112px -100px;}
    
    #gdlg-select{float: right; width: 50%; padding-left: 10px;}
    #gdlg-select select{width: 100%;}
</style>

<script>
    function addToGroup(id, label){
        if($("#gdlg-result span[data-id="+id+"]").length == 0){
            var $group = $("<span data-id=\""+id+"\"><span class=\"gxai icon\"></span>"+label+"</span>").
            click(function(){ $(this).remove(); });
            $("#gdlg-result").append($group);
        }
    }
</script>
<?php

$save = new Data\Ajax(array('url' => 'management/ajax.php'));
$save->arg('userid', true);
$save->arg('groups', true);
$save->data('manage', 'saveusergroups');

/***** Groups Dialog *****/
$gDLG = new Graphic\Dialog('gdlg', array('caption'=>$i18n->users_join_remove_group, 'width'=>500));
$save->bind('oncomplete', $gDLG->call('close'));
$gDLG->addButton($i18n->change, '
    var groups = new Array();
    $("#gdlg-result > span", this).each(function(){
        groups.push($(this).attr("data-id"));
    });
    $($row.find("td").get(4)).attr("data-original", groups.join(","));
    
    '.$save.'($($row.find("td").get(1)).attr("data-original"), groups.join(","));
', false, true);
$gDLG->message = '
    <div id="gdlg-result"></div>
    <div id="gdlg-select" class="gxui-border gxui-border-left">
        <input id="gdlg-uid" type="hidden" />
        <select id="gdlg-groups" class="combobox" size="10">';
        foreach($DB->select('users_groups') as $row)
            $gDLG->message .= '<option value="'.$row['nID'].'">'.$row['cName'].'</option>';
        $gDLG->message .= '
        </select>
        <input type="button" id="gdlg-add" value="[i18n:add_it] >>" class="ui-state-default" />
    </div>
';
$gDLG->bind('onopen', '
    $("#gdlg-result span").remove();
    var groups = new String($($row.find("td").get(4)).attr("data-original"));
    $.each(groups.split(","), function(i, v){
        if($.trim(v) != ""){
            var label = $("#gdlg-groups option[value="+v+"]").text()
            addToGroup(v, label);
        }
    });
');
/* end of applicant dialog */














$Viewer->bind('onready', '
    var $row;
    $("#plugins").on("click", "td.static_cell a", function(e){
        $row = $(this).closest("tr");
        $row.toggleClass("gxui-selected");'.
        $gDLG->call('refreshButtons').
        $gDLG->call('open').
    '});
    
    $("#gdlg-add").click(function(){
        var group = $("#gdlg-groups").val();
        var lbl = $("#gdlg-groups option:selected").text();
        addToGroup(group, lbl);
    });
');

$grid = new Graphic\Grid('plugins', array(
    'caption'       => $i18n->users,
    'multiselect'   => true,
    'table'         => 'users',
    'primary'       => 'nID',
    'order'         => 'nID'
));
$cols = array();
$cols[] = new Graphic\GridColumn('nID', array('editable'=>false, 'caption'=>'', 'hidden'=>true, 'datatype'=>'autonumber', 'primary'=>true));
$cols[] = new Graphic\GridColumn('cUsername', array(
    'caption'=>$i18n->username,
    'validation' => array(
        'pattern'   => '^([a-z0-9\._]+)$',
        'minlength' => 3,
        'maxlength' => 50
    ), 'width'=>400, 'allownull' => false
));
$cols[] = new Graphic\GridColumn('cPassword', array(
    'caption'=>$i18n->password, 'datatype'=>'password',
    'validation' => array(
        'minlength' => 6,
        'maxlength' => 50,
        'invalidValues'=>''
    )
));
$cols[] = new Graphic\GridColumn('cGroups', array('editable'=>false, 'caption'=>'', 'hidden'=>true, 'primary'=>true));
$cols[] = new Graphic\GridColumn('cDisplayName', array('caption'=>$i18n->displayname));
$cols[] = new Graphic\GridColumn('groups', array(
    'caption'=>$i18n->users_group, 'width'=>150, 'url' => 'javascript: void(0);',
	'datatype'=>'static', 'staticvalue' => $i18n->users_group_change
));

$cols[] = new Graphic\GridColumn('bActive', array('caption'=>'[i18n:active]', 'width'=>70, 'datatype'=>'boolean'));

foreach($cols as &$col) $grid->addColumn($col);
echo $grid;
?>