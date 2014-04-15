$.extend($.fn, {
	fileTree: function(o){
		var $this = this;
		var path = o.root;
		
		function getSelectedFiles(){
			return $('li>div.file.gxui-selected', $this);
		};
		this.openFolder = function(foldepath, select){
			select = typeof select == undefined? false: select;
			var $folder = $('.file[rel="'+foldepath+'"]', this);
			var $li = $folder.closest('li');
			showTree($li, escape(foldepath));
			
			if(o.view == 'list' && select){
				if(!o.multiselect)
					$('li .file', $this).removeClass('gxui-selected');
	
				$li.find('>.file').toggleClass('gxui-selected');
				$li.toggleClass('gxui-selected');
			}
		};
		this.open = function(t){
			path = t;
			var $c = $(this);
			if(o.showroot) {
				$c = $('> ul > li', this);
				$c.find('>ul').remove();
				bindTree($(this));
			} else
				$c.html('');
			showTree($c, escape(t));
		};
		this.getFilename = function(){
			return path;
		};
		this.getSelectedFiles = function(){
			var selected_files = new Array();
			getSelectedFiles().each(function(){
				selected_files.push($(this).attr("rel")); 
			});
			return selected_files;
		};
		this.reload = function(current){
			current = typeof current == 'undefined'? false: current;
			if(o.view == 'list'){
				var selected_files = getSelectedFiles();
				if(!current || selected_files.length != 1) 
					$('.ext_home > .file', this).click();
				else 
					selected_files.click();
			} else
				this.open(path);
		};
		this.deleteSelected = function(){
			var files = this.getSelectedFiles();
			o.ajax(this, {
			    action: 'delete', files: files
			}, function(){
				$this.trigger('delete', files);
				$s.remove();
			});
		};
		function showTree(c, t){
			$this.trigger('load', t);
			var p = $(c).find('>ul');
			$(c).addClass('wait');
			o.ajax(c, {
			    action: 'expand',
			    folder: t
			}, function(data){
				$this.trigger('loaded', t);
				if(p.length > 0) {
					p.replaceWith(data);
					$(c).find('>ul:hidden').show();
				} else {
					p.remove();
					$(c).removeClass('wait').append(data);
					$(c).find('>ul:hidden').slideDown({
					    duration: o.expandspeed,
					    easing: o.expandeasing
					});
				}
				bindTree(c);
			});
		}
		function bindTree(t){
			$('li .file', t).mouseenter(function(e){
				$(this).addClass('gxui-hover');
				$(this).closest('li').addClass('gxui-hover');
			}).mouseleave(function(e){
				$(this).removeClass('gxui-hover');
				$(this).closest('li').removeClass('gxui-hover');
			}).click(function(){
				$this.trigger('fileclick', $(this).attr('rel'));
			}).dblclick(function(e){
				if($(this).closest('li').hasClass('ext_dir')) {
					if(o.view == 'thumbnail'){
						path = $(this).attr('rel');
						$this.open(path);
						e.preventDefault();
					}
					$this.trigger('folderdblclick', path);
				} else {
					filename = $(this).attr('rel');
					$this.trigger('filedblclick', filename);
				}
				return false;
			}).bind(o.folderevent, function(e){
				var $li = $(this).closest('li');
				var new_path = $(this).attr("rel");
				if(o.view == 'list' && ($li.hasClass('ext_dir') || $li.hasClass('ext_home'))) {
					path = new_path;
					if(!o.multifolder) {
						$li.parent().find('ul').slideUp({
						    duration: o.collapsespeed,
						    easing: o.collapseeasing
						});
						//$li.parent().find('li.ext_dir').removeClass('expanded').addClass('collapsed');
					}
					showTree($li, escape(path));
					$li.removeClass('collapsed').addClass('expanded');
				}
				
				if(!o.multiselect)
					$('li .file', $this).removeClass('gxui-selected');

				$(this).toggleClass('gxui-selected');
				$(this).closest('li').toggleClass('gxui-selected');

				var selected_files = $this.getSelectedFiles();
				if(selected_files.length > 0) 
					$this.trigger('select', selected_files);
				else 
					$this.trigger('deselect');
				
				e.stopPropagation();
				return false;
			});
		}
		if(o.autoopen) this.open(o.root);
		return this;
	}
});