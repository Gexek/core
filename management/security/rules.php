<?php
$Engine->import('management/security/styles/rules.css');

/*
 * NAME DIALOG:
 * 
 * This dialog opens when user clicks on a rule name
 * It makes the user able to change the rule name 
 */
$rulename = new Graphic\Controls\TextBox('ndlg-input', 'width=100%');
$name_dialog = new Graphic\Dialog('ndlg', array('caption'=>$i18n->rule_name_change, 'width'=>300));
$name_dialog->addButton($i18n->ok, '
    var current = $currentCell.text();
    var newname = $("#ndlg-input").val();
    if(current != newname){
        $currentCell.text(newname);
        rule_changes++;
        refreshRules();
    }
');
$name_dialog->message = '<p>[i18n:enter_rule_name] :</p>'.$rulename;
/* end of name dialog */



/*
 * OBJECT DIALOG
 */
$object_dialog = new Graphic\Dialog('object_dialog', array('caption'=>$i18n->rule_new_applicant, 'width'=>750));
$object_dialog->addButton($i18n->change, '
    var current = $currentCell.html();
    var newhtml = $("#object_dialog section.result .obj-list").html();

    if(current != newhtml){
        $currentCell.html(newhtml);
        rule_changes++;
        refreshRules();
    }
');
$object_dialog->bind('onopen', '
	var $dlg_content = $("#fw-dialogs #fw-dialog-"+fw_dialog_type).clone().show();
	$("#object_dialog").html($dlg_content);
	$currentCell.find(".fw-object").each(function(){
		var type = $(this).data("type");
		var value = $(this).data("value");
		$dlg_content.find("section.list").find(".obj-list").
		find(".fw-object[data-type="+type+"][data-value=\""+value+"\"]").click();
	});
	'.$object_dialog->call('movecenter').'
');
/* end of object dialog */




/*
 * ACCESS TYPE DIALOG 
 */
$type_dialog = new Graphic\Dialog('tdlg', array('caption'=>$i18n->rule_type_change, 'width'=>300));
$type_dialog->addButton($i18n->change_it, '
    var val = $("#tdlg-select").val();
    var lbl = $("#tdlg-select option:selected").text();
    $(">span", $currentCell).removeClass("allow").
    removeClass("deny").addClass(val=="1"? "allow": "deny").
    html("<span class=\"gxai icon\"></span>"+lbl);
    
    rule_changes++;
    refreshRules();
');
$type_dialog->message = '
    <select id="tdlg-select">
        <option value="0">[i18n:rule_deny]</option>
        <option value="1">[i18n:rule_allow]</option>
    </select>
';
/* end of access type dialog */


$save = new Data\Ajax(array('url' => 'management/ajax.php'));
$save->arg('rules', true);
$save->data('manage', 'saverules');
$save->bind('oncomplete', '
    rule_changes = 0
    refreshRules();
    $("#gxui-overlay").hide();
    $("#gx-rules .ui-icon-check, #gx-rules .ui-icon-close").parent().remove();
');

$Viewer->bind('onready', '
    var $currentCell;
    
    $("#gx-rules .gxui-header > div").hover(function(){
        $(this).addClass("hover");
    }, function(){
        $(this).removeClass("hover");
    });
    
    $("#gx-rules .ui-icon-plus").click(function(){
		var $empty = $("#gx-rules > table tbody tr.default").clone();
		$empty.removeClass("default").find("td").not(".type").empty().filter(".name").text("'.$i18n->rule_new.'");
        $("#gx-rules > table tbody").prepend($empty);
        rule_changes++;
        refreshRules();
    });
    
    $("#gx-rules .ui-icon-trash").click(function(){
        $("#gx-rules > table tbody tr.gxui-selected").remove();
        rule_changes++;
        refreshRules();
    });
    
    $("#gx-rules > table").on("click", "td.move .ui-icon-arrowthick-1-n", function(e){
        var $row = $(this).parents("tr:first").toggleClass("gxui-selected");
        var $prev = $row.prev();
        if($prev.length > 0){
            $row.insertBefore($prev);
            rule_changes++; refreshRules();
        }
    });
    $("#gx-rules > table").on("click", "td.move .ui-icon-arrowthick-1-s", function(e){
        var $row = $(this).parents("tr:first").toggleClass("gxui-selected");
        var $next = $row.next();
        if(!$next.is(":last-child")){
            $row.insertAfter($next);
            rule_changes++; refreshRules();
        }
    });
    
    $("#gx-rules > table tbody td").disableSelection();
    
    $("#gx-rules > table").on("dblclick", "td.name, td.applicants, td.type, td.pages, td.manage", function(){
        if(!$(this).parent().hasClass("default")){
            $currentCell = $(this);
            if($currentCell.hasClass("name")){
                $("#ndlg-input").val($(this).text()).focus(function(){
                    this.select();
                }).focus();'.
                $name_dialog->call('open').
            '} else if($currentCell.hasClass("type")){
                $("#tdlg-select").val($(">span", this).hasClass("allow")? 1: 0);'.
                $type_dialog->call('open').
            '} else {
                $currentCell = $(this);
	            fw_dialog_type = $currentCell.data("role");
				'.$object_dialog->call('open').
            '}
        }
    });
		
	$("#object_dialog").on("click", ".fw-object", function(e){
		var $list = $("#object_dialog").find("section.list").find(".obj-list");
		var $result = $("#object_dialog").find("section.result").find(".obj-list");

		var type = $(this).data("type");
		var value = $(this).data("value");
		
		if($(this).closest("section").hasClass("list")){
			if(value == "all"){
				$list.append($result.find(".fw-object"+(type == "all"? "": "[data-type="+type+"]"))).sort(".fw-object", "data-order");
			}
			$result.append(this).sort(".fw-object", "data-order");
			if(value == "all"){
				 $list.find(".fw-object"+(type == "all"? "": "[data-type="+type+"]")).hide();
			}
			
		} else {
			$list.append(this).sort(".fw-object", "data-order");
	
			if(value == "all"){
				 $list.find(".fw-object"+(type == "all"? "": "[data-type="+type+"]")).show();
			}
		}
	});
');
?>
<script type="text/javascript">
    var rule_changes = 0, fw_dialog_type;
    
    function saveRules(){
        $("#gxui-overlay").show();
        var rules = [];
        $("#gx-rules > table tbody tr").not(".default").each(function(){
            var appls = {}, pages = [], manage = {};

            var users = [], groups = [];
            $("td.applicants > span", this).each(function(){
                switch($(this).data("type")){
                    case "all": appls.all = 1; break;
                    case "unauth": appls.unauth = 1; break;
                    case "group": groups.push($(this).data("value")); break;
                    case "user": users.push($(this).data("value")); break;
                }
            });
            if(users.length > 0) appls.users = users;
            if(groups.length > 0) appls.groups = groups;
            
            var type = $("td.type > span", this).hasClass("allow")? 1: 0;

            $("td.pages > span", this).each(function(){
            	pages.push($(this).data("value")); 
            });

            var plugins = [];
            $("td.manage > span", this).each(function(){
                switch($(this).data("type")){
                    case "all": manage.all = 1; break;
                    case "website": manage.website = 1; break;
                    case "security": manage.security = 1; break;
                    case "plugin": plugins.push($(this).data("value")); break;
                }
            });
            if(plugins.length > 0) manage.plugins = plugins;
            
            rules.push({
                name: $("td.name", this).text(),
                applicants: appls, type: type, pages: pages, manage: manage
            });
        });
        
        <?php echo $save; ?>(JSON.stringify(rules));
    }
    
    function refreshRules(){
        $(".caption").html("[i18n:security_rules]");
        if(rule_changes == 1){
            var submit = $("<div class=\"ui-corner-all gxui-clickable\"><span title=\"[i18n:ok]\" class=\"ui-icon ui-icon-check\"></span></div>").
            click(function(){ saveRules(); });
            var cancel = $("<div class=\"ui-corner-all gxui-clickable\"><span title=\"[i18n:cancel]\" class=\"ui-icon ui-icon-close\"></span></div>").
            click(function(){ window.location.reload(); });
            $("#gx-rules .gxui-header").append(cancel, submit);
        }
        
        if(rule_changes > 0){
            var Str = new String("[i18n:rule_changes]");
            $(".caption").html("[i18n:security_rules] ("+Str.replace("%s", rule_changes)+")");
        }
    }
</script>
<div id="fw-dialogs">
	<div class="fw-dialog gxui-clearfix" id="fw-dialog-applicants">
		<?php $i = 0; ?>
        <section class="list">
        	<h5>درخواست کننده ها</h5>
        	<div class="obj-list">
        		<span class="fw-object gxui-clickable all" 		data-order="<?php echo ++$i; ?>" data-type="all" 	data-value="all"><span class="gxai icon"></span>[i18n:rule_all]</span>
        		<span class="fw-object gxui-clickable unauth" 	data-order="<?php echo ++$i; ?>" data-type="unauth" 	data-value="unauth"><span class="gxai icon"></span>[i18n:unauthenticated_all]</span>

        		<span class="fw-object gxui-clickable group" data-order="<?php echo ++$i; ?>" data-type="group" data-value="all"><span class="gxai icon"></span>[i18n:users_all_group]</span>
        		<?php foreach($DB->select('users_groups') as $row): ?>
        		<span class="fw-object gxui-clickable group sub" data-order="<?php echo ++$i; ?>" data-type="group" data-value="<?php echo $row['nID']; ?>"><span class="gxai icon"></span><?php echo $row['cName']; ?></span>
        		<?php endforeach; ?>
        	
				<span class="fw-object gxui-clickable user" data-order="<?php echo ++$i; ?>" data-type="user" data-value="all"><span class="gxai icon"></span>[i18n:users_all]</span>
        		<?php foreach($DB->select('users', array('fields' => 'nID, cUsername, cDisplayName')) as $user): 
        		$name = empty($user['cDisplayName'])? $data['cUsername']: $user['cUsername'].' ('.$user['cDisplayName'].')'; ?>
        		<span class="fw-object gxui-clickable user sub" data-order="<?php echo ++$i; ?>" data-type="user" data-value="<?php echo $user['nID']; ?>"><span class="gxai icon"></span><?php echo $name; ?></span>
        		<?php endforeach; ?>
        	</div>
        </section>
        <section class="result"><h5>اضافه شده</h5><div class="obj-list"></div></section>
    </div>
    
    <div class="fw-dialog gxui-clearfix" id="fw-dialog-manage">
    	<?php $i = 0; ?>
        <section class="list">
        	<h5>عناصر مدیریتی</h5>
        	<div class="obj-list">
        		<span class="fw-object gxui-clickable all" 		data-order="<?php echo ++$i; ?>" data-type="all" 	  data-value="all"><span class="gxai icon"></span>[i18n:all]</span>
        		<span class="fw-object gxui-clickable website" 	data-order="<?php echo ++$i; ?>" data-type="website"  data-value="website"><span class="gxai icon"></span>[i18n:website]</span>
        		<span class="fw-object gxui-clickable security" data-order="<?php echo ++$i; ?>" data-type="security" data-value="security"><span class="gxai icon"></span>[i18n:security_center]</span>

        		<span class="fw-object gxui-clickable plugin" data-order="<?php echo ++$i; ?>" data-type="plugin" data-value="all"><span class="gxai icon"></span>[i18n:all_plugins]</span>
        		<?php foreach($Engine->plugins->all as $plugin): ?>
        		<span class="fw-object gxui-clickable plugin sub" data-order="<?php echo ++$i; ?>" data-type="plugin" data-value="<?php echo $plugin->name; ?>"><span class="gxai icon"></span><?php echo $plugin->name; ?></span>
        		<?php endforeach; ?>
        	</div>
        </section>
        <section class="result"><h5>اضافه شده</h5><div class="obj-list"></div></section>
    </div>
    
    <div class="fw-dialog gxui-clearfix" id="fw-dialog-pages">
    	<?php $i = 0; ?>
        <section class="list">
        	<h5>صفحات وب سایت</h5>
        	<div class="obj-list">
        		<span class="fw-object gxui-clickable page" data-order="<?php echo ++$i; ?>" data-type="page" data-value="all"><span class="gxai icon"></span><label class="title">[i18n:pages_all]</label></span>
        		<?php 
        		$max_len = 50;
        		foreach($DB->select('rewrites', array(
					'filter' => 'cType = "page"', 
					'fields' => 'cRule, cTitle', 'order'=>'cTitle ASC'
				)) as $page):
        			$rule = preg_replace('#/('.preg_quote(GX_PAGE_RX).')#', '', $page['cRule']);
        			$ut = trim($rule, '/'); 
        			if(!empty($page['cTitle'])):
        				$tt = $page['cTitle'];
        				$url = str_truncate($ut, 60, '..', false);
	        			$title = str_truncate($tt, 60, '..');
	        			?>
	        			<span class="fw-object gxui-clickable page sub" data-order="<?php echo ++$i; ?>" data-type="page" data-value="<?php echo $ut; ?>">
	        				<span class="gxai icon"></span>
	        				<label class="title" title="<?php echo $tt; ?>"><?php echo $title; ?></label>
	        				<label class="url" title="<?php echo $ut; ?>"><?php echo $url; ?></label>
	        			</span>
	        			<?php 
        			endif;
        		endforeach; 
        		?>
        	</div>
        </section>
        <section class="result"><h5>اضافه شده</h5><div class="obj-list"></div></section>
    </div>
</div>

<div id="gx-rules">
    <div class="gxui-header">
        <span class="caption">[i18n:security_rules]</span>
        <div class="ui-corner-all gxui-clickable"><span title="[i18n:add]" class="ui-icon ui-icon-plus"></span></div>
        <div class="ui-corner-all gxui-clickable"><span title="[i18n:delete]" class="ui-icon ui-icon-trash"></span></div>
    </div>
    <table id="gx-rules-table">
        <thead><tr>
            <th class="gxui-bevel move"></th>
            <th class="gxui-bevel name">[i18n:rule_name]</th>
            <th class="gxui-bevel applicants">[i18n:rule_applicants]</th>
            <th class="gxui-bevel type">[i18n:rule_type]</th>
            <th class="gxui-bevel pages">[i18n:rule_pages]</th>
            <th class="gxui-bevel manage">[i18n:rule_manage]</th>
        </tr></thead>
        <tbody>
            <?php
            $rs = $DB->select('rules', array('order' => 'nID DESC'));
            foreach($rs as $rowindex => $row):
            ?>
            <tr class="<?php echo $row['nID']>1? 'gxui-clickable gxui-selectable': 'default'; echo $rowindex%2==0? ' odd': ' pair'; ?>">
                <td class="move">
                    <?php if($row['nID']>1): ?>
                    <div class="gxui-inline-block"><span title="[i18n:rule_move_up]" class="ui-icon ui-icon-arrowthick-1-n"></span></div>
                    <div class="gxui-inline-block"><span title="[i18n:rule_move_down]" class="ui-icon ui-icon-arrowthick-1-s"></span></div>
                    <?php endif; ?>
                </td>
                <td class="name <?php echo $row['nID']>1? 'gxui-clickable': ''; ?>" data-role="name"><?php echo $row['nID'] > 1? $row['cName']: $i18n->rule_default; ?></td>
                <td class="applicants <?php echo $row['nID']>1? 'gxui-clickable': ''; ?>" data-role="applicants">
                    <?php
                    $applicants = (array)json_decode($row['cApplicants']);
                    
                    if(isset($applicants['all']) && $applicants['all'])
                    	echo '<span data-value="all" data-type="all" data-order="0" class="fw-object all"><span class="gxai icon"></span>'.$i18n->all.'</span>';
                    
                    if(isset($applicants['unauth']) && $applicants['unauth'])
                    	echo '<span data-value="unauth" data-type="unauth" data-order="0" class="fw-object unauth"><span class="gxai icon"></span>'.$i18n->unauthenticated_all.'</span>';
                    
                    if(isset($applicants['groups'])){
						$sth = $DB->prepare('SELECT nID, cName FROM users_groups WHERE nID = :id LIMIT 1');
	                    foreach ($applicants['groups'] as $g){
							if($g == 'all') $data = array('cName' => $i18n->users_all_group);
							else {$sth->execute(array(':id' => $g)); $data = $sth->fetch(PDO::FETCH_ASSOC);}
	                    	echo '<span data-value="'.$g.'" data-type="group" data-order="0" class="fw-object group"><span class="gxai icon"></span>'.$data['cName'].'</span>';
	                    }
					}
					if(isset($applicants['users'])){
						$sth = $DB->prepare('SELECT nID, cUsername, cDisplayName FROM users WHERE nID = :id LIMIT 1');
						foreach ($applicants['users'] as $u){
							if($u == 'all') $name = $i18n->users_all;
							else {
								$sth->execute(array(':id' => $u)); $data = $sth->fetch(PDO::FETCH_ASSOC);
								$name = empty($data['cDisplayName'])? $data['cUsername']: $data['cUsername'].' ('.$data['cDisplayName'].')';
							}
							echo '<span data-value="'.$u.'" data-type="user" data-order="0" class="fw-object user"><span class="gxai icon"></span>'.$name.'</span>';
						}
                    }
					?>
                </td>
                <td class="type <?php echo $row['nID']>1? 'gxui-clickable': ''; ?>" data-role="type">
                	<span class="fw-object <?php echo $row['bAccessType']? 'allow': 'deny'; ?>">
                        <span class="gxai icon"></span>
                        <?php echo $row['bAccessType']? $i18n->rule_allow: $i18n->rule_deny; ?>
                    </span>
                </td>
                <td class="pages <?php echo $row['nID']>1? 'gxui-clickable': ''; ?>" data-role="pages">
                	<?php
                    $pages = json_decode($row['cPages']);
                    $sth = $DB->prepare('SELECT cTitle FROM rewrites WHERE cName = :n LIMIT 1');
					foreach ($pages as $pagename){
						if($pagename == 'all'){
							$tt = $i18n->pages_all;
							$ut = 'all';
						} else {
							$ut = $pagename;
							$sth->execute(array(':n' => $pagename)); $page = $sth->fetch(PDO::FETCH_ASSOC);
							$tt = $page['cTitle'];
						}
						$url = str_truncate($ut, 60, '..', false);
						$title = str_truncate($tt, 60, '..');
						echo '<span data-value="'.$ut.'" data-type="page" data-order="0" class="fw-object page">'.
								'<span class="gxai icon"></span>'.
								'<label class="title" title="'.$tt.'">'.$title.'</label>'.
								'<label class="url" title="'.$ut.'">'.$url.'</label>'.
						'</span>';
					}
                    ?>
                </td>
                <td class="manage <?php echo $row['nID']>1? 'gxui-clickable': ''; ?>" data-role="manage">
                	<?php 
                    $manages = (array)json_decode($row['cManage']);
                    
                    if(isset($manages['all']) && $manages['all'])
                    	echo '<span data-value="all" data-type="all" data-order="0" class="fw-object all"><span class="gxai icon"></span>'.$i18n->all.'</span>';
                    
                    if(isset($manages['website']) && $manages['website'])
                    	echo '<span data-value="website" data-type="website" data-order="0" class="fw-object website"><span class="gxai icon"></span>'.$i18n->website.'</span>';
                    
                    if(isset($manages['security']) && $manages['security'])
                    	echo '<span data-value="security" data-type="all" data-order="0" class="fw-object security"><span class="gxai icon"></span>'.$i18n->security_center.'</span>';
                    
					if(isset($manages['plugins'])){
						foreach ($manages['plugins'] as $p){
							if($p == 'all') $label = $i18n->all_plugins;
							else $label = $p;
							echo '<span data-value="'.$p.'" data-type="plugin" data-order="0" class="fw-object plugin"><span class="gxai icon"></span>'.$label.'</span>';
						}
					}
                    ?>
                </td>
             </tr>
            <?php
            endforeach;
            ?>
        </tbody>
    </table>
</div>