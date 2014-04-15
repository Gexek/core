    $.fn.extend({
        __gx_combo: function(options){
            return this.each(function() {
                var $this = this,
                $input = $('>input.input', this),
                $select = $('>select', this),
                $handle = $('>.handle', this),
                $popup = $(this).parent().find('>.popup'),
                $list = $('ul.list', $popup);
                
                $('li', $list).hover(
                    function(){ $(this).addClass('hover'); },
                    function(){ $(this).removeClass('hover'); }
                ).click(function(){
                    if($(this).hasClass('gxui-selected'))
                        $(this).removeClass('gxui-selected');
                    else {
                        if(!options.multiple)
                            $(this).siblings().removeClass('gxui-selected');
                        $(this).addClass('gxui-selected');
                    }
                    
                    if(!options.multiple) $popup.hide();
                    $this.refresh();
                });
                
                $('.header input[type=checkbox]', $popup).click(function(){
                    if($(this).is(':checked'))
                        $('li', $list).addClass('gxui-selected');
                    else
                        $('li', $list).removeClass('gxui-selected');
                    $this.refresh();
                });
                
                $('.header input[type=radio]', $popup).click(function(){
                    $this.refresh();
                }).first().attr('checked', true);

                this.refresh = function(){
                	$('option', $select).remove();

                    switch($('>ul.header input[type=radio]:checked', $popup).val()){
                        case '1':
                            $('li', $list).show();
                            break;
                        case '2':
                            $('li', $list).hide();
                            $('li.gxui-selected', $list).show();
                            break;
                        case '3':
                            $('li', $list).hide();
                            $('li', $list).not('.gxui-selected').show();
                            break;
                    }
                    
                    var captions = [];
                    if($('li.gxui-selected', $list).length == $('li', $list).length){
                    	$('.header input[type=checkbox]', $popup).attr('checked', true);
                    	$select.append('<option selected="true" value="every"></option>');
                    	captions.push(':: [i18n:all] ::');
                    } else {
                    	$('.header input[type=checkbox]', $popup).attr('checked', false);
	                    $('li.gxui-selected', $list).each(function(){
	                        var val = $(this).attr('data-value');
	                        var caption = $(this).attr('data-caption');
	                        $select.append('<option value="'+val+'" selected="true"></option>');
	                        captions.push(caption == ''? val: caption);
	                    });
                    }
                    $input.val(captions.join(';'));
                }
                
                this.refresh();
            });
        }
    });
