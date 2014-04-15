(function($){
    $.fn.extend({
        getCoords: function(){
            var crds = new String($(this).attr("coords"));
            crds = crds.split(",");
            
            var coords = new Array();
            var c = 0;
            for(var i=1; i<crds.length; i+=2){
                coords[c++] = {x: crds[i-1], y: crds[i]};
            }
            return coords;
        },
        
        themeMap: function(multi, $value, defaultOptions){
            var mapname = $(this).attr('data-name');
            var $map = this;
            var $popup = $("#themeselector-popup");
            var $selected = $('.map-option-locs', $popup);
            
            this.refresh = function(){
                $('option', $selected).remove();
                $('.map-image div.hilight.selected', $popup).each(function(){
                    $selected.append('<option value="'+$(this).attr('data-name')+'" selected="true"></option>');
                });
            };
            
            this.getImage = function(){
                return $('span.image', this).text();
            };
            
            this.getFrames = function(){
                return eval($('span.frames', this).text());
            };
            
            this.getOptions = function(){
                var opt = {};
                var defaults = eval('('+$value.val()+')');
                if(typeof defaults[mapname] != 'undefined')
                    opt = defaults[mapname];
                opt = $.extend(defaultOptions, opt);
                return opt;
            };
            
            this.createMap = function(){
                $('area[shape=rect]', this).each(function(){
                    var name = $(this).attr('title')? $(this).attr('title'): $(this).attr('alt');
                    var coords = $(this).getCoords();
    
                    $highlight = $(
                        '<div class="hilight" data-name="'+name+'">'+
                            '<div class="cover icon-ok"></div>'+
                            //'<span class="text">'+name+'</span>'+
                        '</div>'
                    ).css({
                        top: (coords[0].y)+'px',
                        left: (coords[0].x)+'px',
                        width: (coords[1].x-coords[0].x)+'px',
                        height: (coords[1].y-coords[0].y)+'px'
                    }).click(function(){
                        if($(this).hasClass('selected'))
                            $(this).removeClass('selected');
                        else {
                            if(!multi) $(this).siblings().removeClass('selected');
                            $(this).addClass('selected');
                        }
                        $map.refresh();
                    }).hover(
                        function(){ $(this).addClass('hover'); },
                        function(){ $(this).removeClass('hover'); }
                    );
                    
                    var opt = $map.getOptions();
                    if($.inArray(name, opt.locations) == 0)
                        $highlight.addClass('selected');
                        
                    $('div.map-image', $popup).append($highlight);
                });
            };
            
            this.showPopup = function(){
                var opt = this.getOptions();
               
                var themeframes = this.getFrames();
                if(themeframes.length > 0){
                    $(".map-option-frame select option", $popup).not(':first-child').remove();
                    $(".map-option-frame select", $popup).append('<option>'+themeframes.join('</option><option>')+'</option>');
                }
                
                $(".map-option-frame select", $popup).val(opt.frame);
                $(".map-option-fw input", $popup).val(opt.width);
                $(".map-option-fh input", $popup).val(opt.height);
                $(".map-option-margin input", $popup).val(opt.margin);
                $(".map-option-positioning select", $popup).val(opt.positioning).change();
                $(".map-option-position select", $popup).val(opt.position).change();
                $(".map-option-coords input.coord_t", $popup).val(opt.top);
                $(".map-option-coords input.coord_r", $popup).val(opt.right);
                $(".map-option-coords input.coord_b", $popup).val(opt.bottom);
                $(".map-option-coords input.coord_l", $popup).val(opt.left);
                if(opt.locations.length > 0)
                    $(".map-option-locs", $popup).append('<option selected>'+opt.locations.join('</option><option selected>')+'</option>');
                
                $('.map-image img', $popup).attr('src', this.getImage());
                
                this.createMap();
                
                $popup.showModal().attr('data-name', mapname);
                $('.header span', $popup).text(mapname);
            };
            
            return this;
        },
        
        themeSelector: function(options){
            return this.each(function() {
                var $themes = this;
                var $maps = new Array();
                var $suggestion = $('>div.content ul.suggestions', this);
                var $selected = $('>div.content > ul.selected-items', this);
                var defaultOptions = {
                    "frame": "noframe", "positioning": "static", "position": "right",
                    "top": "0", "right": "0", "bottom": "0", "left": "0",
                    "width": "0", "height": "0", "locations": []
                };
                var value = $('input', this);
                var $popup = $("#themeselector-popup");
                var $positioning = $(".map-option-positioning select", $popup);
                var $position = $(".map-option-position select", $popup);
                var $coords = $('div.map-option .map-option-coords', $popup);
     
                this.create = function(){
                    $('map', this).each(function(){
                        var name = $(this).attr('data-name');
                        $maps[name] = $(this).themeMap(options.multilocation, value, defaultOptions);
                        $suggestion.append(
                            '<li class="gxui-flat gxui-light gxui-float gxui-bordered gxui-clickable" data-name="'+name+'">'+
                                '<img src="'+$maps[name].getImage()+'"><span>'+name+'</span>'+
                            '</li>'
                        );
                    }).click(function(e){
                        e.stopPropagation();
                    });
                    
                    var defaults = eval('('+value.val()+')');
                    $.each(defaults, function(t, l){
                        if(t != '' && typeof $maps[t] != 'undefined')
                            $selected.append($themes.addItem(t));
                    });
                    
                    this.init();
                };
                
                this.addItem = function(name){
                    var opts = {};
                    var opt = $.extend(defaultOptions, {});
                    opts[name] = opt;
                    
                    var val = new String(value.val());
                    if(val.trim() !== '')
                        var defaults = eval('('+value.val()+')');
                    else
                        var defaults = {};
                    defaults = $.extend(opts, defaults);
                    value.val(JSON.stringify(defaults));
                    
                    return $('<li class="gxui-flat gxui-light gxui-float" data-name="'+name+'"></li>').
                    fadeTo(0, 0.1).fadeTo(500, 1).append($(
                        '<img src="'+$maps[name].getImage()+'" />'
                    )).click(function(e){
                        e.stopPropagation();
                        $(this).addClass('selected').siblings().removeClass('selected');
                        
                        if(options.selectlocation)
                            $maps[name].showPopup();
                    }).append(
                        $(
                            '<div data-name="'+name+'" class="close-icon ui-state-default ui-corner-all">'+
                                '<span class="ui-icon ui-icon-close"></span>'+
                            '</div>'
                        ).click(function(e){
                            $('li[data-name='+name+']', $suggestion).removeClass('selected');
                            $(this).parent().remove();
                            
                            var defaults = eval('('+value.val()+')');
                            delete defaults[name];
                            value.val(JSON.stringify(defaults));
                            
                            e.stopPropagation();
                        })
                    );
                };
                
                this.init = function(){
                    // init template lists to select
                    $('li', $suggestion).hover(
                        function(){ $(this).addClass('hover'); },
                        function(){ $(this).removeClass('hover'); }
                    ).click(function(e){
                        if(!options.multitheme) {
                            $selected.html("");
                            value.val("");
                        }

                        var name = $(this).attr('data-name');
                        $selected.append($themes.addItem(name));
                        $(this).addClass('selected').parent().hide();
                        
                        e.stopPropagation();
                    });
                    
                    $('select', $popup).change(function(){
                        switch($positioning.val()){
                            case 'static':
                                $position.parent().css({display: 'none'});
                                $coords.css({display: 'none'});
                                break;
                            
                            case 'float':
                                $position.parent().show();
                                switch($position.val()){
                                    case 'absolute': $coords.css({display: 'block'}); break;
                                    default: $coords.css({display: 'none'}); break;
                                }
                                break;
                        }
                    });
                    
                    $('.header a.ui-icon-check', $popup).click(function(){
                        var defaults = eval('('+value.val()+')');
                        var locs = $('.map-option-locs', $popup ).val();
                        defaults[$popup.attr('data-name')] = {
                            frame: $('.map-option-frame select', $popup).val(),
                            width: $('.map-option-fw input', $popup ).val(),
                            height: $('.map-option-fh input', $popup ).val(),
                            margin: $('.map-option-margin input', $popup ).val(),
                            positioning: $('.map-option-positioning select', $popup ).val(),
                            position: $('.map-option-position select', $popup ).val(),
                            top: $('.map-option-coords input.coord_t', $popup ).val(),
                            right: $('.map-option-coords input.coord_r', $popup ).val(),
                            bottom: $('.map-option-coords input.coord_b', $popup ).val(),
                            left: $('.map-option-coords input.coord_l', $popup ).val(),
                            locations: locs == null? []: locs
                        };
                        value.val(JSON.stringify(defaults));
                        $popup.hide();
                    });
                    $('.header a.ui-icon-close', $popup).click(function(){
                        $popup.hide();
                    });
                };
                
                this.create();
            });
        }
    });
})(jQuery);