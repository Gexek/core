(function($){
    $.fn.extend({
        dropdown: function(options){
            
            return this.each(function(){
                var $li = $("li", this);
                $("li", this).each(function(){
                    var $submenu = $(">ul", this);
                    if($submenu.length > 0) $(this).addClass('parent');
                    
                    $(this).hoverIntent(
                        function(){ $submenu.slideDown(); },
                        function(){ $submenu.hide('fast'); }
                    );
                }).hover(
                    function(){ $(this).addClass('hover'); },
                    function(){ $(this).removeClass('hover'); }
                );
            });
        }
    });
})(jQuery);