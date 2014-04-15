var _jqOldShow = $.fn.show;
var _jqOldHide = $.fn.hide;

$.fn.extend({
    swapClass: function(c1, c2) {
        if ($(this).hasClass(c1)) $(this).removeClass(c1).addClass(c2);
        else $(this).removeClass(c2).addClass(c1);
        return this;
    },
    
    isAutoWidth: function(p) {
        var el = this[0];
        if ($(this).width() == ($(this).parent().width() - $(this).calcWidthBorders()))
            $(this).css({width: 'auto'});
        return el.style.width == 'auto' || (el.style.width) == '';
    },

    isAutoHeight: function(p) {
        var el = this[0];
        return el.style.height == 'auto' || (el.style.height) == '';
    },

    calcWidthBorders: function() {
        var el = $(this); var tb = 0;
        tb += parseInt(el.css("padding-left"), 10) + parseInt(el.css("padding-right"), 10); /*Total Padding Width*/
        var bl = parseInt(el.css("borderLeftWidth"), 10);
        var br = parseInt(el.css("borderRightWidth"), 10); /*Total Border Width*/
        bl = isNaN(bl) ? 0 : bl;
        br = isNaN(br) ? 0 : br;
        tb += bl + br;
        return tb;
    },

    calcHeightBorders: function() {
        var el = $(this);
        var tb = 0;
        tb += parseInt(el.css("padding-top"), 10) + parseInt(el.css("padding-bottom"), 10); /*Total Padding Width*/
        var bl = parseInt(el.css("borderTopWidth"), 10);
        var br = parseInt(el.css("borderBottomWidth"), 10); /*Total Border Width*/
        bl = isNaN(bl) ? 0 : bl;
        br = isNaN(br) ? 0 : br;
        tb += bl + br;
        return tb;
    },
    
    sizeToPixel: function(dim) {
        if (typeof dim == 'undefined' || dim == 'width') $(this).width($(this).width() - $(this).calcWidthBorders());
        if (typeof dim == 'undefined' || dim == 'height') $(this).height($(this).height() - $(this).calcHeightBorders());
        return this;
    },

    direction: function() {
        var dir = $(document.body).css('direction');
        if (dir == undefined) dir = 'ltr';
        return dir;
    },

    align: function(vv) {
        if ((typeof vv) != 'boolean') vv = false;
        if ($.fn.direction() == 'ltr') 
            return vv? 'right': 'left';
        else 
            return vv? 'left': 'right';
    },

    exists: function() { return $(this).length > 0; },

    fadeInOut: function(speed, wait) {
        speed = ((typeof speed) == 'undefined') ? 400 : speed;
        wait = ((typeof wait) == 'undefined') ? 1000 : wait;
        $(this).fadeIn(speed).delay(wait).fadeOut(speed);
    },

    scrollWidth: function() {
        var div = $('<div style="width:50px;height:50px;overflow:hidden;position:absolute;top:-200px;left:-200px;"><div style="height:100px;"></div>');
        $('body').append(div);
        var w1 = $('div', div).innerWidth();
        div.css('overflow-y', 'scroll');
        var w2 = $('div', div).innerWidth();
        $(div).remove();
        return (w1 - w2);
    },

    hoverIntent: function(f, g) {
        var cfg = {
            sensitivity: 7,
            interval: 100,
            timeout: 0
        };
        cfg = $.extend(cfg, g ? {
            over: f,
            out: g
        } : f);
        var cX, cY, pX, pY;
        var track = function(ev) {
            cX = ev.pageX;
            cY = ev.pageY;
        };
        var compare = function(ev, ob) {
            ob.hoverIntent_t = clearTimeout(ob.hoverIntent_t);
            if ((Math.abs(pX - cX) + Math.abs(pY - cY)) < cfg.sensitivity) {
                $(ob).unbind("mousemove", track);
                ob.hoverIntent_s = 1;
                return cfg.over.apply(ob, [ev]);
            } else {
                pX = cX;
                pY = cY;
                ob.hoverIntent_t = setTimeout(function() {
                    compare(ev, ob);
                }, cfg.interval);
            }
        };
        var delay = function(ev, ob) {
            ob.hoverIntent_t = clearTimeout(ob.hoverIntent_t);
            ob.hoverIntent_s = 0;
            return cfg.out.apply(ob, [ev]);
        };
        var handleHover = function(e) {
            var p = (e.type == "mouseover" ? e.fromElement : e.toElement) || e.relatedTarget;
            while (p && p != this) {
                try {
                    p = p.parentNode;
                } catch (e) {
                    p = this;
                }
            }
            if (p == this) {
                return false;
            }
            var ev = jQuery.extend({}, e);
            var ob = this;
            if (ob.hoverIntent_t) {
                ob.hoverIntent_t = clearTimeout(ob.hoverIntent_t);
            }
            if (e.type == "mouseover") {
                pX = ev.pageX;
                pY = ev.pageY;
                $(ob).bind("mousemove", track);
                if (ob.hoverIntent_s != 1) {
                    ob.hoverIntent_t = setTimeout(function() {
                        compare(ev, ob);
                    }, cfg.interval);
                }
            } else {
                $(ob).unbind("mousemove", track);
                if (ob.hoverIntent_s == 1) {
                    ob.hoverIntent_t = setTimeout(function() {
                        delay(ev, ob);
                    }, cfg.timeout);
                }
            }
        };
        return this.mouseover(handleHover).mouseout(handleHover);
    },
    
    basename: function(path) {
        return path.replace(/.*\//, "");
    },
     
    dirname: function(path) {
        path = path.replace(/\\/g, '/');
        path = new String(path.match(/.*\//));
        return path.replace(/\/+$/, '');
    },
    
    inViewport: function(whole){
        var docTop = $(window).scrollTop();
        var docBottom = docTop + $(window).height();
        
        var offsetTop = $(this).offset().top;
        var offsetBottom = offsetTop + $(this).height();
        
        if(whole) 
            return (offsetBottom >= docTop && offsetTop <= docBottom) &&
            (offsetBottom <= docBottom && offsetTop >= docTop);
        else
            return offsetBottom >= docTop && offsetTop <= docBottom;
	},
	    
	availWidth: function(){
	    var w = $(this).calcWidthBorders();
	    return $(this).width() - w;
	},
	
	unAvailWidth: function(){
	    return $(this).calcWidthBorders();
	},
	
	availHeight: function(){
	    var h = $(this).calcHeightBorders();
	    return $(this).height() - h;
	},
	unAvailHeight: function(){
	    return $(this).calcHeightBorders();
	},
	
	showCenter: function(opt1, opt2) {
	    return $(this).each(function() {
            var t = ($(window).height() - $(this).height()) / 2 + $(window).scrollTop();
            var l = ($(window).width() - $(this).width()) / 2 + $(window).scrollLeft();
            $(this).css({top: t, left: l}).show(opt1, opt2);
	    });
	},
	
	showModal: function(speed, easing, callback) {
	        return $(this).each(function() {
	            $('#gxui-overlay').show();
	            $(this).showCenter(speed, easing, callback).
	            unbind('beforehide').bind('beforehide', function(){
	                $('#gxui-overlay').hide();
	            });
	    });
	},
	
	show: function(speed, easing, oldCallback) {
	    //return $(this).each(function() {
		var obj = $(this),
		newCallback = function() {
		    if ($.isFunction(oldCallback)) 
			oldCallback.apply(obj);
		    obj.trigger('aftershow');
		};
	
		// you can trigger a before show if you want
		obj.trigger('beforeshow');
	
		// now use the old function to show the element passing the new callback
		_jqOldShow.apply(obj, [speed, easing, newCallback]);
	            
	     return this;
	    //});
	},
	
	beforeShow: function(callback){
	    return $(this).each(function(){
	    	$(this).bind('beforeshow', callback);
	    });
	},
	
	afterShow: function(callback){
	    return $(this).each(function(){
	    	$(this).bind('aftershow', callback);
	    });
	},
	
	hide: function(speed, easing, oldCallback) {
	    return $(this).each(function() {
			$(this).trigger('beforehide');
			_jqOldHide.apply($(this));
	    });
	},
	
	beforeHide: function(callback){
	    return $(this).each(function(){
	    	$(this).bind('beforehide', callback);
	    });
	},
	
	afterHide: function(callback){
	    return $(this).each(function(){
	    	$(this).bind('afterhide', callback);
	    });
	},
	
	emptyValue: function(options){
	    options = $.extend({
	    	emptyClass: ''
	    }, options);
		
	    return this.each(function() {
			var val = $(this).val();
			var title = $(this).attr('title');
			if (!val) {$(this).val(title); val = title;}
			if (val == title) $(this).addClass(options.emptyClass);
		            $(this).attr('data-title', title);
		            
			$(this).focus(function(){
			    $(this).removeClass(options.emptyClass);
			    var val = $.trim($(this).val());
			    var title = $(this).attr('data-title');
			    if(val == title) $(this).val(''); 
			}).blur(function(){
			    var val = $.trim($(this).val());
			    var title = $(this).attr('data-title');
			    if (!val || val == title)
				$(this).addClass(options.emptyClass).val(title);
			}).blur();
	    });
	},
	
	fitToWidth: function(el, availSize){
	    availSize = typeof availSize == 'undefined'? true: availSize;
	    return this.each(function(){
		var trgt = el == 'parent'? $(this).parent(): el;
		var w = availSize? $(trgt).width(): $(trgt).innerWidth();
		$(this).innerWidth(w);
	    });
	},
	fitToHeight: function(el, availSize){
	    availSize = typeof availSize == 'undefined'? true: availSize;
	    return this.each(function(){
			var trgt = el == 'parent'? $(this).parent(): el;
			var h = availSize? $(trgt).height(): $(trgt).innerHeight();
			$(this).innerHeight(h);
	    });
	},
	
	fitTo: function(el, availSize){
	    return this.each(function(){
		$(this).fitToWidth(el, availSize).fitToHeight(el, availSize);
	    });
	},
	
	autoSelect: function(){
	    return this.each(function(){
			$(this).css({
			    position: 'relative', display: 'block'
			}).click(function(){
			    var clicked = $(this);
			    var clickText = $(this).text();
			    if ($('textarea', this).length) $('textarea', this).remove();
			    $('<textarea readonly />').appendTo($(this)).val(clickText).focus().select().css({
					position: 'absolute', top: 0, left: 0, width: '100%', height: '100%',
					border: '0 none transparent', margin: 0, padding: 0,
					outline: 'none', resize: 'none', overflow: 'hidden',
					'font-size': clicked.css('font-size'),
					'font-weight': clicked.css('font-weight'),
					'font-family': clicked.css('font-family')
			    });
			    return false;
			});
	    });
	},
	    
    randString: function(length) {
        var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz'.split('');
        if (! length) 
            length = Math.floor(Math.random() * chars.length);
        var str = '';
        for (var i = 0; i < length; i++) 
            str += chars[Math.floor(Math.random() * chars.length)];
        return str;
    },
    
    sort: function(childselector, attr){
    	return this.each(function(){
    		var $wrapper = $(this);
			var $sortables = $(childselector, this);		
			[].sort.call($sortables, function(a,b) {
		        return +$(a).attr(attr) - +$(b).attr(attr);
		    });
			$sortables.each(function(){ $wrapper.append(this); });
    	});
    },
	
    safeDblClick: function(callback){
    	return this.each(function(){
    		var elSingleClick = $(this).Click;
    		var elDblClick = $(this).dblClick;
    		
    		$(this).click(function(e) {
                var that = this;
                setTimeout(function() {
                    var dblclick = parseInt($(that).data('double'), 10);
                    if (dblclick > 0) {
                        $(that).data('double', dblclick-1);
                    } else {
                    	elSingleClick.call(that, e);
                    }
                }, 300);
            }).dblclick(function(e) {
                $(this).data('double', 2);
                elDblClick.call(this, e);
            });
    	});
    }
});


