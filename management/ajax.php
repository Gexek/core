<?php
function saveRules(){
    global $DB, $Settings;
    $q = array();
    $q[] = "DELETE FROM rules WHERE nID > 1";
    $_POST->rules = array_reverse($_POST->rules);
    foreach($_POST->rules as $i => $rule){
        $a = json_encode($rule->applicants);
        $t = json_encode($rule->type);
        $p = json_encode($rule->pages);
        $m = json_encode($rule->manage);
        $q[] = "INSERT INTO rules VALUE(".($i+2).", '$rule->name', '$a', $t, '$p', '$m')";
    }
    $DB->exec($q);
    $Settings->last_rule_change = time();
    $Settings->save();
}

function saveUserGroups(){
    global $DB;
    $sth = $DB->prepare("UPDATE users SET cGroups = :groups WHERE nID = :id");
    $sth->execute(array('groups'=>$_POST->groups, 'id'=>$_POST->userid));
}

switch($_POST->manage){
    case 'saverules': saveRules(); break;
    case 'saveusergroups': saveUserGroups(); break;
    case 'save_support_password':
        $Settings->support_password = md5($_POST->support_password);
        $Settings->save();
        break;
    case 'manage_pages':
        $connector = new Data\GridConnector();
        $connector->manipulatedFields = array('cRule', 'cURL', 'cKeywords');
        
        $connector->open = function($dataset){
        	foreach($dataset as $row){
        		$row->cRule = trim($row->cRule, '/');
        		preg_match_all('#&([a-zA-Z0-9_\/\-]+)=\$[0-9]+#', $row->cURL, $params);
        		foreach ($params[1] as $param)
        			$row->cRule = preg_replace('#('.preg_quote(GX_PAGE_RX).')#', '{'.$param.'}', $row->cRule, 1);
        	}
        	return $dataset;
        };
        
		$connector->update = function ($updates){
			global $DB;
            foreach($updates as &$row){
            	$row->cRule = trim($row->cRule, '/');
            	$page = preg_replace('#/\{([^}/]+)\}#', '', $row->cRule);
            	$row->cURL = "page=$page";
            	$row->cName = $page;
            	$row->cKeywords = preg_replace('/[،\-;]/u', ',', $row->cKeywords);
            	$row->cType = 'page';
            	$row->cOwnerType = 'user';
            	$row->cOwnerName = 0;
            	
            	preg_match_all('#/\{([^}/]+)\}#', $row->cRule, $params);
            	foreach ($params[1] as $i => $p){
            		$row->cRule = str_ireplace('/{'.$p.'}', '/'.GX_PAGE_RX, $row->cRule);
            		$row->cURL .= '&'.$p.'=$'.($i+1);
            	}
            	
            	$row->cRule .= '/';
            }
            return $updates;
        };
        
		$connector->perform();
        break;
}
?>