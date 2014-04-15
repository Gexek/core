(function($){
    $.fn.extend({
        uploadform: function(defaults){
            var options = $.extend({maxInput: 3}, defaults)
            
            var form = this;
            this.clear = function(){
                $('span.inputs', this).html('');
                this.addInput();
            }
            
            this.addInput = function(){
                var count = $('span.inputs input[type=file]', this).length;
                if(count < options.maxInput){
                    name  = 'file'+(count + 1);
                    $('span.inputs', this).append(
                        '<p>'+
                            '<a href="javascript:void(0);" onclick="$(this).parent().remove();"> X </a>'+
                            '<input type="file" name="'+name+'" size="70" />'+
                        '</p>'
                    );
                }
            }
            
            if(typeof options == 'string'){
                switch(options){
                    case 'clear': this.clear(); break;
                    case 'add': this.addInput(); break;
                }
            } else {
                $('fieldset.collapsable', this).each(function(){
                    var $fieldset = $(this);
                    $(this).children().not('legend').wrapAll('<div class="content"></div>');
                    $('legend', this).click(function(){
                        $('div.content', $fieldset).slideToggle(500);
                    });
                });
                
                $('a.add-new', this).click(function(){
                    form.addInput();
                });
                
                this.clear();
            }
        }
    });
})(jQuery);