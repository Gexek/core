(function($) {
    $.fn.extend({
	gxScrollbar: function(){
	    /*xScroll = typeof xScroll == undefined? true: xScroll;
	    yScroll = typeof yScroll == undefined? true: yScroll;*/
	    return this.each(function(){
		var $frame = $(this).css({overflow: 'hidden'}),
		$content = $('>div.scroll-content', this),
		$y_bar 	 = $('>div.scroll-y-bar',   this),
		position = $frame.css('direction') == 'ltr'? 'left': 'right';
		
		if($content.length == 0){
		    $frame.wrapInner('<div class="scroll-content"></div>');
		    $content = $('>div.scroll-content', this);
		}
		
		if($y_bar.length == 0){
		    $y_bar = $('<div class="scroll-y-bar"><div class="handle"></div></div>').
		    css({height: '100%', position: 'absolute', top: 0}).css(position, 2);
		    $frame.prepend($y_bar);
		}
		var $handle = $('.handle', $y_bar);

		if($frame.height() < $content.height()){
		    $content.css('margin-'+position, $y_bar.width()+2);
		    $(this).css('position', 'relative');
		    var perc = ($frame.height()/$content.height())*100;
	    
		    $handle.draggable({
			containment: 'parent', axis: "y"
		    }).height($y_bar.height()/100*perc).
		    bind('drag', function(event, ui){
			var spc = $y_bar.height()-$handle.height();
			var ht = parseInt($(this).css('top'));
			var p = ht/spc*100;
			var y = $content.height()-$frame.height();
			y = -(y/100*p);
			$content.css({marginTop: y+'px'});
		    });
		    
		    $frame.mousewheel(function(e, step){
			var spc = $y_bar.height()-$handle.height();
			var t = parseFloat($handle.css('top'));
			var dir = step > 0? 'up': 'down';
			step = Math.abs(step)*2;
			
			if(dir == 'up') t -= step;
			else t += step;
			
			if(t > spc) t = spc;
			if(t < 0) t = 0;
			
			if(t >= 0 && t <= spc)
			    $handle.css('top', t+'px').trigger('drag');
			
			e.preventDefault();
		    }, function(){alert("end");});
		}
	    });
	},
	    
	gxscroll: function(){
	    $(".scroll").gxScrollbar();
	    
	    /*var $all = $(".scroll").filter(function(){
		var x = false, y = false;
		if($(this).css('overflow-x') == 'auto' || $(this).hasClass('scroll-x')){
		    x = true; $(this).addClass('scroll-x').
		    css('overflow-x', 'hidden').gxScrollbar(false, true);
		}
		if($(this).css('overflow-y') == 'auto' || $(this).hasClass('scroll-y')){
		    x = true; $(this).addClass('scroll-y').
		    css('overflow-y', 'hidden').gxScrollbar(true, false);
		}
		return x || y;
	    });*/
	}
    });
})(jQuery);