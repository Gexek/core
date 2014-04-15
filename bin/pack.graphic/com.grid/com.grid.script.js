(function($){
    $.fn.extend({
        grid: function(options){
            var $grid = this;
            
            // initial options
            this.options = $.extend({
                showrowno: false, caption: null,
                multiselect: false,
                ajax: function(){}
            }, options);
            
            var $head = $('div.grid-thead table', this);
            var $body = $('div.grid-tbody table', this);
            var scrollW = $.fn.scrollWidth();
            $('tr:eq(0) > th:last-child', $head).width(scrollW);
            $head.width($head.width());
            var bw = $head.width() - scrollW + 1;
            $body.width(bw);
             
            if(this.options.showrowno){
                $rowNo = $('thead tr:first-child th.row-num', $head);
                if(!$rowNo.exists()){
                    $('thead tr:first-child', $head).prepend('<th class="gxui-bevel row-num">#</th>');
                    $('thead tr:first-child', $body).prepend('<th class="gxui-bevel row-num"></th>');
                }
            }
                
            this.$__preloader = $('div.grid-preloader', this);
            
            var $buttons = $('.grid-caption .icon');
            $buttons.click(function(){
                if(!$(this).hasClass('gxui-disabled')){
                    $name = $(this).data('name');
                    var result = options.toolbarButtons[$name]($grid);
                    result = typeof result == 'undefined'? true: result;
                    if(result) $grid.refreshButtons();
                }
            });
            
            /* Keyup capturing */
            $(this).keyup(function(e){
                var key = e.keyCode || e.which;
                switch(key){
                    case 13: $grid.submit(); e.preventDefault(); break;
                    case 27: $grid.cancel(); break;
                    case 35: $grid.submit(); break;
                    case 43: case 45: $grid.addRow(); break;
                    case 113: $grid.edit(); break;
                    case 116: if(!e.ctrlKey) {$grid.reload(); e.preventDefault();} break;
                }
                
            });
            
            
            this.refreshButtons = function(){
                $buttons.filter('[data-viewstate=enable]').removeClass('gxui-disabled');
                $buttons.filter('[data-viewstate=disable]').addClass('gxui-disabled');
                
                if($grid.isSelected()){
                    $buttons.filter('[data-selectstate=enable]').removeClass('gxui-disabled');
                    $buttons.filter('[data-selectstate=disable]').addClass('gxui-disabled');
                }
                
                if($grid.isEditing()){
                    $buttons.filter('[data-editstate=enable]').removeClass('gxui-disabled');
                    $buttons.filter('[data-editstate=disable]').addClass('gxui-disabled');
                }
            };
            
            this.isEditing = function(){
                return $('tbody tr.editing', $body).length > 0;
            };
            
            this.isSelected = function(){
                return $('tbody tr.gxui-selected', $body).length > 0;
            };
            
            function rowSingleClick(e) {
            	var sel = getSelection().toString();
        		if(!$(this).hasClass('editing') && !sel)
        			$(this).toggleClass('gxui-selected');
                $grid.refreshButtons();
                if($grid.isEditing()) $grid.edit();
            }

            function rowDblClick(e) {
                // for future use, but now, just for prevent triggering click, on doubleclick
            }
            this.addRow = function(values){
                $row = $('<tr class="gxui-flat"></tr>').hover(
                    function(){ $(this).addClass('gxui-hover'); },
                    function(){ $(this).removeClass('gxui-hover'); }
                );
                if(this.options.selectable){
                    $row.click(function(e) {
                        var that = this;
                        setTimeout(function() {
                            var dblclick = parseInt($(that).data('double'), 10);
                            if (dblclick > 0) {
                                $(that).data('double', dblclick-1);
                            } else {
                            	rowSingleClick.call(that, e);
                            }
                        }, 300);
                    }).dblclick(function(e) {
                        $(this).data('double', 2);
                        rowDblClick.call(this, e);
                    });
                }

                for(var i in options.cols){
                    var value = ''; caption = '';
                    if(typeof values != 'undefined' && typeof values[i] != 'undefined'){
                        value = values[i].original;
                        caption = values[i].value;
                    }
                    
                    var col = options.cols[i];
                    col.format = new String(col.format);
                    
                    var $col = $('<td valign="center"></td>').
                    data('format', col.format).
                    data('original', value).
                    data('old', value).
                    addClass(col.datatype+'_cell').
                    data('field', col.name);
                    
                    if(col.align != 'auto')
                        $col.css('text-align', col.align);
                    if(col.dir != 'auto')
                        $col.css('direction', col.dir);
                        
                    if(col.hidden) $col.addClass('gxui-hidden');
                    
                    if(col.datatype == 'datetime')
                        $col.html(caption);
                        
                    $row.append($col);
                }
                
                
                if(arguments.length == 0){
                    if(options.insert == 'first')
                        $('tbody', $body).prepend($row);
                    else
                        $('tbody', $body).append($row);
                        
                    this.refreshCells($row);
                    $row.addClass('gxui-selected new');
                    this.edit();
                } else {
                    $('tbody', $body).append($row);
                }
                
                if($row.index()%2 == 0)
                    $row.addClass('gxui-light');
                if(this.options.showrowno)
                    $row.prepend('<td class="gxui-bevel row-num">'+($row.index()+1)+'</td>');
            };
            
            this.refreshCells = function($rows){
                if(typeof $rows == 'object') $rows = $($rows);
                else $rows = $('tbody tr', $body);
                
                $('.gxui-error', $grid).fadeOut(100);
                
                $rows.each(function(){
                    var $row = this;
                    var values = {};
                    $.each(options.cols, function(i, col){
                        var $td = $($('td', $row).not('.row-num').get(i));
                        values[col.name] = $td.data('original');
                        switch(col.datatype){
                            case 'datetime':
                                values[col.name] = $td.find('.hasDatepicker').val();
                                break;
                            
                            case 'boolean':
                                values[col.name] = '<span class="ui-icon '+(values[col.name]>0? 'ui-icon-check': 'ui-icon-close')+'"></span>';
                                break;
                            
                            case 'list':
                            	var vals = new String(values[col.name]);
                            	if(col.multiple)
                            		vals = vals.split(';');
                            	
                            	var cv = [];
                                $.each(col.listitems, function(v, c){
                                    if($.inArray(v, vals) > -1)
                                    	cv.push(c);
                                });
                                values[col.name] = cv.join(';');
                                break;
                            
                            case 'money':
                                value = new String(col.format);
                                value = value.replace('%n', parseFloat(values[col.name]).formatMoney(col.decimals));
                                value = value.replace('%s', '[i18n:currency_symbol]');
                                values[col.name] = value.replace('%c', '<span class="currency">[i18n:currency]</span>');
                                break;
                            
                            case 'password':
                                values[col.name] = '************';
                                $td.data('original', '************');
                                break;
                        }
                    });
                    
                    $.each(options.cols, function(i, col){
                        var $td = $($('td', $row).not('.row-num').get(i));
                        if(col.datatype != 'boolean' && col.datatype != 'datetime'){
                            if(col.symbol != '')
                                values[col.name] = '<img src="'+col.symbol+'" title="'+values[col.name]+'" />';
                            
                            if(col.icon != ''){
                                $td.addClass('with-icon');
                                values[col.name] = '<img src="'+col.icon+'" />'+values[col.name];
                            }
                            
                            if(col.url != '')
                                values[col.name] = '<a href="'+col.url+'" >'+values[col.name]+'</a>';
                            
                            title = new String(col.title);
                            
                            $.each(options.cols, function(ci, c){
                                var $tc = $($('td', $row).not('.row-num').get(ci));
                                var val = new String(values[col.name]);
                                values[col.name] = val.replace('%'+c.name+'%', $tc.data('original'));
                                
                                var val = c.datatype == 'datetime'? $tc.text(): $tc.data('original');
                                title = title.replace('%'+c.name+'%', val);
                            });
                            
                            $td.attr("title", title);
                        }
                        $td.html(values[col.name]);
                    });
                    $($row).removeClass('editing').removeClass('new');
                });
                
                this.$__preloader.hide();
            };
            
            this.getColIndex = function(name){
                index = false;
                $.each(options.cols, function(i, c){
                    if(c.name == name) index = i;
                });
                return index;
            };
            
            this.clear = function(){
                $('.grid-table tr', $body).remove();
                this.$__preloader.show();
            };
            
            this.loadData = function(){
                this.clear();
                this.options.ajax(this, 'load', null, null, function(xml){
                    $('row', xml).each(function(){
                        var cols = []; var $row = this;
                        $.each(options.cols, function(i, v){
                            cols.push({
                                name: v.name,
                                original: $(v.name, $row).attr('original'),
                                value: $(v.name, $row).text()
                            });
                        });
                        $grid.addRow(cols);
                    });
                    
                    $grid.refreshCells();
                    Str = new String('[i18n:com_grid_rowcount]');
                    Str = Str.replace('%s', $("rowscount", xml).text());
                    $('.grid-status .total', $grid).text(Str);
                    $grid.refreshButtons();
                });
            };
            
            this.selectedRows = function(){
                return $('tbody tr.gxui-selected', $body);
            };
            
            this.getSelected = function(){
                var rows = []; var oldkeys = [];
                $('tbody tr.gxui-selected', $body).each(function(){
                    var $row = this;
                    var cols = {}; var ok_col = {};
                    $.each(options.cols, function(i, col){
                        if(col.datatype != 'static' && (col.editable || col.primary)){
                            var $td = $($('td', $row).not('.row-num').get(i));
                            cols[col.name] = $td.data('original');
                            if(col.primary){
                            	ok_col[col.name] = $td.data('old');
                            	if(ok_col[col.name] == '')
                            		ok_col[col.name] = $td.data('original');
                            }
                        }
                    });
                    rows.push(cols);
                    oldkeys.push(ok_col);
                });
                return {rows: rows, oldkeys: oldkeys};
            };
            
            this.edit = function(){
                $($('tbody tr.gxui-selected', $body).not('.editing').each(function(){
                    var $row = this;
                    var rowID = $(this).addClass('editing').index();

                    $.each(options.cols, function(i, col){
                        if(col.editable && col.datatype != 'autonumber'){
                            var $td = $($('td', $row).not('.row-num').get(i));
                            switch(col.datatype){
                                case 'datetime':
                                    var inputid = col.name+rowID+'_input';
                                    $td.html(
                                        '<input class="date-value" id="'+inputid+'" value="'+$td.data('original')+'" />'+
                                        '<input id="'+inputid+'_alt" value="'+$td.text()+'" />'
                                    );
                                    
                                    var options = {
                                        dateFormat: $td.data('format'),
                                        altField: '#'+inputid,
                                        altFormat: 'yy/mm/dd',
                                        timeFormat: 'hh:mm'+(col.editablesecond? ':ss': ''),
                                        showSecond: col.editablesecond,
                                        gotoCurrent: true
                                    };
                                    if(col.datepicker != null){
                                        options.minDate = col.datepicker.minDate;
                                        options.maxDate = col.datepicker.maxDate;
                                        options['beforeShowDay'] = function(date){
                                            if($.inArray(date.getDay(), col.datepicker.holidays) != -1)
                                                return [false];
                                                
                                            var m = date.getMonth(), d = date.getDate(), y = date.getFullYear();
                                            for(i = 0; i < col.datepicker.disabledDays.length; i++) {
                                                if($.inArray(y+"/"+(m+1)+"/"+d, col.datepicker.disabledDays) != -1)
                                                    return [false];
                                            }
                                            return [true];
                                        };
                                    }
                                    
                                    if(col.editabletime)
                                        $('#'+inputid+'_alt').datetimepicker(options);
                                    else
                                        $('#'+inputid+'_alt').datepicker(options);
                                        
                                    break;
                                
                                case 'boolean':
                                    var isn = isy = '';
                                    if(parseInt($td.data('original')) > 0)
                                        isy = ' selected';
                                    else
                                        isn = ' selected';
                                    
                                    var select = '<select>';
                                    select += '<option value="0"'+isn+'>[i18n:no]</option>';
                                    select += '<option value="1"'+isy+'>[i18n:yes]</option>';
                                    select += '<select>';
                                    $td.html(select);
                                    break;
                                
                                case 'list':
                                    value = $td.data('original');
                                    var select = '<select'+(col.multiple? ' multiple': '')+'>';
                                    $.each(col.listitems, function(v, c){
                                        var slctd = v==value? ' selected': '';
                                        select += '<option value="'+v+'"'+slctd+'>'+c+'</option>';
                                    });
                                    select += '<select>';
                                    $td.html(select);
                                    break;
                                
                                case 'money':
                                    $td.html('<input value="'+$td.data('original')+'">');
                                    break;
                                
                                case 'text':
                                    $td.html('<textarea>'+$td.text()+'</textarea>');
                                    break;
                                
                                case 'password':
                                    $td.html('<input type="password">');
                                    break;
                                
                                default:
                                    $td.html('<input value="'+$td.text()+'">');
                                    break;
                            }
                        }
                    });
                }).find('input').get(0)).focus();
            };
            
            this.customSubmit = function(request, reload, callback){
                reload = typeof reload == 'undefined'? false: reload;
                this.$__preloader.show();
                var selected = this.getSelected();
                this.options.ajax(this, request, JSON.stringify(selected.rows), JSON.stringify(selected.oldkeys), function(xml){
                    if(reload) $grid.reload();
                    if(typeof callback != 'undefined')
                        callback(xml);
                        
                    $grid.refreshCells($grid.selectedRows());
                    $grid.deselect();
                });
            };
            
            this.submit = function(){
                var $edited_rows = $('tbody tr.editing', $body);
                if($edited_rows.length > 0){
                    var validated = true;
                    $edited_rows.each(function(){
                        var $row = this;
                        if(!validated) return;
                        $.each(options.cols, function(i, col){
                            if(!validated) return;
                            var $td = $($('td', $row).not('.row-num').get(i));
                            $td.removeClass('error');
                            validated = $grid.validate(col, $td);
                            if(validated){
                            	$td.data('old', $td.data('original'));
                                switch(col.datatype){
                                    case 'datetime':
                                        $td.data('original', $('input.date-value', $td).val());
                                        break;
                                    case 'boolean':
                                    case 'list':
                                    	var val = $('select', $td).val();
                                    	if(typeof val == 'array')
                                    		val = JSON.stringify(val);
                                        $td.data('original', val);
                                        break;
                                    case 'text':
                                        $td.data('original', $('textarea', $td).val());
                                        break;
                                    default:
                                        $td.data('original', $('input', $td).val());
                                        break;
                                }
                            }
                        });
                    });
                    
                    if(validated){
                        this.$__preloader.show();
                        var selected = this.getSelected();
                        this.options.ajax(this, 'edit', JSON.stringify(selected.rows), JSON.stringify(selected.oldkeys), function(xml){
                            var $selected = $grid.selectedRows();
                            var $newRows = $selected.filter('.editing.new');
                            $('update', xml).each(function(n, upd){
                                $('key', upd).each(function(i, k){
                                    var key = $(this).text();
                                    var $td = $('td[data-field='+$(k).attr('name')+']', $newRows.filter(':eq('+n+')'));
                                    $td.html(key).data('original', key);
                                });
                            });
                            
                            $grid.refreshCells($selected);
                            $grid.deselect();
                            $grid.refreshButtons();
                        });
                    }
                }
                return false;
            };

            this.validate = function(col, $cell){
                var val = null;
                var error = false;
                $('.gxui-error', $grid).fadeOut(100);
                switch(col.datatype){
                    //varchar, text, autonumber, integer, float, boolean, money, datetime, list, password
                    case 'integer':
                    case 'float':
                    	var pattern = '//';
                        if(col.datatype == 'integer')
                            pattern = new RegExp(/[0-9]/);
                        else
                            pattern = new RegExp(/[0-9\.]/);
                        val = $('input', $cell).val();
                        if(pattern.test(val)){
                            val = parseFloat(val);
                            if (col.validation.min != null && val < col.validation.min){
                                error = new String('[i18n:error_min]');
                                error = error.replace('%s', '<strong>'+col.caption+'</strong>').replace('%n', col.validation.min);
                            } else if (col.validation.max != null && val > col.validation.max){
                                error = new String('[i18n:error_max]');
                                error = error.replace('%s', '<strong>'+col.caption+'</strong>').replace('%n', col.validation.max);
                            } else {
                                val = new String(val);
                                if (col.validation.minlength > 0 && val.length < col.validation.minlength){
                                    error = new String('[i18n:error_min_numlength]');
                                    error = error.replace('%s', '<strong>'+col.caption+'</strong>').replace('%n', col.validation.minlength);
                                } else if (col.validation.maxlength > 0 && val.length > col.validation.maxlength){
                                    error = new String('[i18n:error_max_numlength]');
                                    error = error.replace('%s', '<strong>'+col.caption+'</strong>').replace('%n', col.validation.maxlength);
                                }
                            }
                        } else {
                            if(col.datatype == 'integer')
                                error = new String('[i18n:error_should_be_integer]');
                            else
                                error = new String('[i18n:error_should_be_number]');
                            error = error.replace('%s', '<strong>'+col.caption+'</strong>');
                        }
                        break;
                    case 'password':
                    case 'varchar':
                        var pattern = new RegExp(col.validation.pattern);
                        val = new String($('input', $cell).val());
                        
                        if(val.length > 0 || col.datatype != 'password'){
                            if (val.length > 0){
                                if (col.validation.minlength > 0 && val.length < col.validation.minlength){
                                    error = new String('[i18n:error_min_length]');
                                    error = error.replace('%s', '<strong>'+col.caption+'</strong>').replace('%n', col.validation.minlength);
                                } else if (col.validation.maxlength > 0 && val.length > col.validation.maxlength){
                                    error = new String('[i18n:error_max_length]');
                                    error = error.replace('%s', '<strong>'+col.caption+'</strong>').replace('%n', col.validation.maxlength);
                                } else if(!pattern.test(val)){
                                    error = new String(col.validation.patternmessage);
                                    error = error.replace('%s', '<strong>'+col.caption+'</strong>');
                                } else {
                                    var values = new String(col.validation.invalidValues);
                                    values = values.split(',');
                                    if(values.length > 0){
                                        var pattern = new RegExp('^('+values.join('|')+')$');
                                        if(pattern.test(val)){
                                            error = new String('[i18n:error_invalid_usage]');
                                            error = error.replace('%s', val).replace('%f', col.caption);
                                        }
                                    }
                                }
                            } else if(!col.allownull){
                                error = new String('[i18n:error_not_null]');
                                error = error.replace('%s', '<strong>'+col.caption+'</strong>').replace('%n', col.validation.minlength);
                            }
                        }
                        break;
                }
                
                if(error){
                    this.showError($cell, error);
                    return false;
                } else
                    return true;
            };
            
            this.showError = function($cell, error){
                var hoffset = $('.grid-thead').offset();
                var minusHeight = hoffset.top + $('.grid-thead').outerHeight();
                var hRight = ($(window).width() - (hoffset.left + $('.grid-thead').outerWidth()));
                
                var offset = $cell.offset();
                var top = offset.top + $cell.height() - minusHeight + 7;
                hRight = ($(window).width() - (offset.left + $cell.outerWidth())) - hRight;
                var x = hRight + ($cell.outerWidth() / 2) - 14;
                $('.gxui-error', this).fadeIn(300).css({top: top, [i18n:align]: x}).
                find('span.text').html(error);
                $cell.find('input, select, textarea').focus();
            };

            this.removeRows = function(){
                this.$__preloader.show();
                var selected = this.getSelected();
                this.options.ajax(this, 'delete', JSON.stringify(selected.rows), JSON.stringify(selected.oldkeys), function(){
                    $grid.$__preloader.hide();
                    $('tbody tr.gxui-selected', $body).remove();
                    $grid.refreshButtons();
                });
            };
            
            this.deselect = function(){
                $('tbody tr', $body).removeClass('gxui-selected');
            };
            
            this.reload = function(){
                $('tbody tr', $body).remove();
                this.loadData();
            };
            
            this.cancel = function(){
                $('form', this).trigger('reset');
                $('tbody tr.new', $body).remove();
                
                this.refreshCells(this.selectedRows());
                this.refreshButtons();
                this.deselect();
            };

            this.reload();
            this.refreshButtons();
            
            return this;
        }
    });
})(jQuery);