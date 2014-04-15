(function($){
    $.fn.extend({
        gxform: function(options){
            return this.each(function(){
                this.clear = function(){
                    $('input[type=text]', this).val('');
                    $('input[type=checkbox]', this).attr('checked', false);
                    $('select', this).val('undefined');
                    $('textarea', this).val('');
                }
                
                this.reset = function(f){
                    $(this).bind('reset', f);
                    return this;
                }
                
                this.submit = function(f){
                    $(this).submit(f);
                    return this;
                }
                
                if(typeof options == 'string'){
                    switch(options){
                        case 'clear': this.clear(); break;
                    }
                } else {
                    $('.graphic-formsection.expandable .gxui-header', this).click(function(){
                        $(this).parent().find('>section').slideToggle(500, function(){
                            $(this).parent().toggleClass('collapsed');
                        });
                    });
                    
                    if(options.autosave > 0)
                        setInterval(function(){ options.ajax(true); }, options.autosave);
                }
            });
        }
    });
})(jQuery);