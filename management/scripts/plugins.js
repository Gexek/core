function refreshList(){
    $('.plugin').each(function(){
        if($('input', this).is(':checked'))
            $(this).addClass('gxui-selected');
        else
            $(this).removeClass('gxui-selected');
    });
}

$(function(){
    $('.plugin').click(function(){
        $('input', this).click();
        refreshList();
    }).find('input').click(function(e){
        e.stopPropagation();
    });
    
    refreshList();
});