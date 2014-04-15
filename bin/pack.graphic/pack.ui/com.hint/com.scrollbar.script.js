(function($) {
    $.fn.extend({
	customScroll: function(xScroll, yScroll){
	    xScroll = typeof xScroll == undefined? true: xScroll;
	    yScroll = typeof yScroll == undefined? true: yScroll;
	    
	    var $frame = $(this);
	    var $content = $('>div.scroll-content', this);
	    var $y_bar 	 = $('>div.scroll-y-bar',   this);
	    
	    if(xScroll || yScroll){
		$(this).css('position', 'relative');
		if($content.length == 0){
		    $frame.wrapInner('<div class="scroll-content">');
		    $content = $('>div.scroll-content', this);
		}
	    }

	    if(yScroll){
		if($y_bar.length == 0){
		    $y_bar = $('<div class="scroll-y-bar"><div><div class="handle"></div></div></div>').
		    css({position: 'absolute', top: 0, right: 0, zIndex: 2});
		    $frame.prepend($y_bar);
		}
		
		$content.fitToWidth('parent');
		$('>div', $y_bar).fitTo('parent', false).css({position: 'relative'});
		
		if($frame.height() < $content.height()){
		    var perc = ($frame.height()/$content.height())*100;
		    var hpads = $frame.unAvailHeight();
		    var vpads = $frame.unAvailWidth();

		    $content.css({
			position: 'absolute', zIndex: 1, left: 'auto',
			right: (vpads/2 + $y_bar.width())+'px'
		    });
		    //if($.browser.msie)
			//$content.css({width: ($content.width() + vpads/2)+'px'});
		    //else
			$content.css({width: ($content.width() - vpads/2)+'px'});
	    
		    var $handle = $('.handle', $y_bar);
		    $handle.draggable({
			containment: 'parent', axis: "y"
			
		    }).height($y_bar.height()/100*perc).
		    bind('drag', function(event, ui){
			var spc = $y_bar.height()-$handle.height();
			var ht = parseInt($(this).css('top'));
			var p = ht/spc*100;
			var y = $content.height()-$frame.height();
			y = -(y/100*p) + hpads/2;
			$content.css({top: y+'px'});
		    });
		    
		    $frame.mousewheel(function(e, step){
			var spc = $y_bar.height()-$handle.height();
			var t = parseFloat($handle.css('top'));
			var dir = step > 0? 'up': 'down';
			step = Math.abs(step)*3;
			
			if(dir == 'up') t -= step*20;
			else t += step*20;
			
			if(t > spc) t = spc;
			if(t < 0) t = 0;
			
			if(t >= 0 && t <= spc)
			    $handle.css('top', t+'px').trigger('drag');
		    });
		} else {
		    $frame.unbind('mousewheel');
		    $content.css({
			position: 'absolute', zIndex: 1, left: 'auto',
			right: (vpads/2)+'px'
		    });
		    $content.css({width: ($content.width() + vpads/2)+'px'});
		    $y_bar.remove();
		}
	    }
	    
	    return this;
	},
	    
	gxscroll: function(){
	    var $all = $(".scroll").filter(function(){
		var x = false, y = false;
		if($(this).css('overflow-x') == 'auto' || $(this).hasClass('scroll-x')){
		    x = true; $(this).addClass('scroll-x').
		    css('overflow-x', 'hidden').customScroll(false, true);
		}
		if($(this).css('overflow-y') == 'auto' || $(this).hasClass('scroll-y')){
		    x = true; $(this).addClass('scroll-y').
		    css('overflow-y', 'hidden').customScroll(true, false);
		}
		return x || y;
	    });
	}
    });
})(jQuery);