$(function() {
    $('.collapsible .nodeHeader').click(function() {
        $(this).closest('.nodeBlock').toggleClass('collapsed');
    });
    
    $('#page-toggleButtons a:not(.empty)').click(function() {
        if($(this).hasClass('on')) {
            $(this).removeClass('on');
            $('div.node-'+$(this).attr('data-node')).hide();
        } else {
            $(this).addClass('on');
            $('.node-'+$(this).attr('data-node')).show();
        }
    }).click().click();

    $('a.dump-ref').hover(function() {
        $($(this).attr('href')).toggleClass('hover');
    }, function() {
        $($(this).attr('href')).toggleClass('hover');
    });
});