var $txt = [], $packs = [], gxupd_funcs = [];
$txt['core'] = '[i18n:core]';
$txt['plugin'] = '[i18n:plugin]';
$txt['theme'] = '[i18n:theme]';
$txt['new'] = '[i18n:update_new]';
$txt['fixed'] = '[i18n:update_fixed]';
$txt['improved'] = '[i18n:update_improved]';
$txt[204] = '[i18n:update_already_last]';
$txt[301] = '[i18n:update_server_not_accessible]';
$txt[401] = '[i18n:update_unauthorized_access]';
$txt[404] = '[i18n:update_server_not_found]';
$txt[500] = '[i18n:update_connection_failed]';
function showButton(state){
	$("#wizard-buttons > span").hide();
	$("#wizard-buttons > span[data-role=" + state + "]").show();
}
function setPackState(id, state){
	$("li#pack"+id).removeClass('spinning checking done failed').addClass(state);
}
function checkSpinner(state, text){
	switch (state) {
		case 'spinner': file = 'management/update/images/spinner_connect.gif';break;
		case 'checked':	file = 'management/update/images/checked.png';break;
		case 'cross':	file = 'management/update/images/connect_cross.png';break;
		case 'notice':	file = 'management/update/images/connect_notice.png';break;
		case 'updated':	file = 'management/update/images/connect_updated.png';break;
	}
	setBodyHTML(
		'<div id="spinner_connect">'+
			'<img class="status_img" src="'+file+'" width="72px" height="72px" />'+
			'<b><span class="status_txt connect_status">'+text+'</span></b>'+
		'</div>'
	);
}
function setBodyHTML(html, append){
	append = typeof append == 'undefined'? false: append;
	if(append) $("#wizard-body").append(html);
	else $("#wizard-body").html(html);
}
gxupd_funcs['welcome'] = function(){
	setBodyHTML('[i18n:update_eula]');
	showButton('check');
};
gxupd_funcs['check'] = function(){
	checkSpinner('spinner', '[i18n:update_connectiong_to_server]');
	
	gxu_ajax_call('check', function(xhr, xml){
		switch (xhr.status) {
			case 200:
				setBodyHTML(
					'<input type="checkbox" id="updates_checkall" checked />'+
					'<h4 class="available_updates_header">[i18n:update_availables]</h4>'+
					'<ul id="available_packages"><ul>'
				);
				var $all_packs_ul = $('#available_packages');
				$('package', xml).each(function(packID){
					var packtype = $(this).attr('type');
					var packname = $(this).attr('name');
					var packsize = $(this).attr('size');
					var $pathces = [];
					var $pack_ul = $(
					    '<li id="pack' + packID + '" data-type="' + packtype + '" class="gxui-clearfix checking">' +
					    	'<input type="checkbox" data-id="' + packID + '" checked' + (packtype=='core'? ' disabled class="required"': '') + ' />'+
					    	'<span class="gxui-spinner gxui-spinner-block tiny"></span>'+
					    	'<span class="icon icon-ok"></span>'+
					    	'<span class="icon icon-cancel"></span>'+
					        '<label class="name">' + $txt[packtype] + ' ' + packname + '</label>' + 
					        '<label class="size">' + (packsize / 1024).toFixed(2) + ' KB</label>' + 
					        '<ul class="patches"></ul>' +
					    '</li>'
					);
					$all_packs_ul.append($pack_ul);

					$('patch', this).each(function(patchID){
						var patch = {
						    downloaded: false, installed: false,
						    id: $(this).attr('releaseid'),
							date: $(this).attr('date'),
							size: $(this).attr('size'),
							ver: $(this).attr('version'),
							file: $(this).attr('file')
						};
						
						var $patch_ul = $(
						 	'<li id="patch' + patch.id + '" class="' + packtype + ' gxui-clearfix">' +
						 		'<span class="gxui-spinner gxui-spinner-block tiny"></span>'+
								'<label class="date">' + patch.date + '</label>' + 
								'<label class="size">' + (patch.size / 1024).toFixed(2) + ' KB</label>' + 
								'<ul class="changes"></ul>' +
							'</li>'
						);
						
						$('change', this).each(function(){
							$patch_ul.find('>ul.changes').append(
								'<li class="gxui-clearfix">' +
									'<label class="type">' + $txt[$(this).attr('type')] + '</label>' + $(this).text() +
								'</li>'
							);
						});
						
						$pack_ul.find('>ul.patches').append($patch_ul);
						$pathces.push(patch);
					});

					$packs.push({
						downloaded: false,
						installed: false,
						type: packtype,
						name: packname,
						size: packsize,
						patches: $pathces
					});
				});
				
				$all_packs_ul.find('li').click(function(e){
					$(this).find('>ul').slideToggle();
					e.stopPropagation();
				}).find('input').click(function(e){
					e.stopPropagation();
				});
				
				$("#updates_checkall").click(function(){
					$all_packs_ul.find('input').not('.required').prop('checked', $(this).is(':checked'));
				});
				
				showButton('install');
				break;
				
			case 204:
				showButton('recheck');
				checkSpinner('updated', '[i18n:update_already_last]');
				break;
		}
		
	}, function(xhr, status){
		showButton('recheck');
		checkSpinner('cross', $txt[xhr.status]);
	});
};
gxupd_funcs['install'] = function(){
	gxu_setmode_ajax(1);
	$("#available_packages").find('input').prop('disabled', true).filter(':checked').each(function(){
		var packID = $(this).data('id'); 
		var pack = $packs[packID];
		
		setPackState(packID, 'spinning');
		
		var failed = 0;
	    $.each(pack.patches, function(patchID, patch){
	    	gxu_ajax_call('download', function(){
	    		patch.downloaded = true;
	    	}, function(){
	    		patch.downloaded = false;
	    		failed++;
	    	}, patch.id, patch.file, patch.size);
	    });

	    if(failed > 0){
	    	setPackState(packID, 'failed');
	    	pack.downloaded = false;
	    } else {
	    	pack.downloaded = true;
	    	
	    	var failed = 0;
		    $.each(pack.patches, function(patchID, patch){
		    	gxu_ajax_call('install', function(){
		    		patch.installed = true;
		    	}, function(){
		    		patch.installed = false;
		    		failed++;
		    	}, null, patch.file, patch.size, pack.type, pack.name, patch.ver);
		    });
		    
		    if(failed > 0){
		    	setPackState(packID, 'failed');
		    	pack.installed = false;
		    } else {
		    	setPackState(packID, 'done');
		    	pack.installed = true;
		    }
	    }
	});
	
	gxu_setmode_ajax(0);
	showButton('apply');
};
gxupd_funcs['apply'] = function(){
	window.location.href = '[i18n:locale]/[smart:$Engine->pages->adminurl]';
};
function gxuw_go(state){
	if(state == 'recheck') state = 'check';
	
	$("#wizard-anchor li").removeClass("active");
	$("#" + state + "-anchor").addClass("active");
	$("#wizard-buttons > span").hide();
	
	gxupd_funcs[state]();
}

gxuw_go("welcome");

$("#wizard-buttons > span").click(function(){ 
	var role = $(this).data('role');
	gxuw_go(role); 
});