(function($){
    $.fn.extend({
        treeview: function(options) {
            options = $.extend({}, options);
            
            return this.each(function(){
                var $this = this;

                function init(){
                    $('li > table', $this).hover(function(e){
                        $(this).addClass('gxui-hover');
                        e.stopPropagation();
                    }, function(e){
                        $(this).removeClass('gxui-hover');
                        e.stopPropagation();
                    }).click(function(e){
                        if($(this).parent().hasClass('root'))
                            $('td.checkbox input.selector', $this).removeAttr('checked');
                        else
                            $('td.checkbox input.selector', this).click();
                        refresh();
                        if($('td.checkbox input.selector:checked', $this).length > 0)
                            $($this).trigger('select');
                        else
                            $($this).trigger('deselect');
                    })
                    
                    $('li > table td.checkbox input.selector').click(function(e){
                        e.stopPropagation();
                    });
                    
                    $('li .ui-icon', $this).click(function(e){
                        var $li = $($(this).parents('li').get(0));
                        
                        if($(this).hasClass('handle')){
                            $(this).toggleClass(options.icon[0]).toggleClass(options.icon[1]);
                            $li.find('>ul').animate({height: 'toggle', opacity: 'toggle'}, options.speed, function(){
                                $li.toggleClass('closed');
                            });
                        } else if($(this).hasClass('up')){
                            if($($this).trigger('move', ['up', $li, $li.prev()]))
                                $li.insertBefore($li.prev());
                        } else if($(this).hasClass('down')){
                            if($($this).trigger('move', ['down', $li, $li.next()]))
                                $li.insertAfter($li.next());
                        }
                        
                        e.stopPropagation();
                    });
                    
                    refresh();
                }
    
                function refresh(){
                    $('li > table', $this).each(function(){
                        if($('td.checkbox input.selector', this).is(':checked'))
                            $(this).addClass('gxui-selected').parent().addClass('selected');
                        else
                            $(this).removeClass('gxui-selected').parent().removeClass('selected');
                    });
                }
    
                init();
            });
        }
    });
})(jQuery);