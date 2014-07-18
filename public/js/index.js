$(document).ready(function() {
    var $currencies = $('.currencies');
    $currencies.hover(function(){
        $(this).addClass('open');
    },function(){
        $(this).removeClass('open');
    });
    //Change selected currencies
    $currencies.each(function(i,element){
        $(element).find('li').click(function(){
            $(element).find('.active').removeClass('active');
            $(element).prepend( $(this) ).removeClass('open');
            $(this).addClass('active');
            updateRates();
        });
    });
    $('#curr_amount_in').on('input',function(){
        $(this).removeClass('error');
        updateRates()
    });
    //Protects bacspace-button & delete-button in input fields
    $('input').on('keydown', function() {
        var key = event.keyCode || event.charCode;
        if( key == 8 || key == 46 ){
            $('#curr_amount_in').val('');
            return false;
        }
    });
    $('.reflector').on('click touchend', function(){
        // todo: selector to variable
        var $firstInputLi = $('.currencies.input li').first();
        var $firstOutputLi = $('.currencies.output li').first();
        var inCur = $firstInputLi.attr('class').substring(0,3);
        var outCur = $firstOutputLi.attr('class').substring(0,3);
        var $inCurNew = $('.currencies.input').find('.'+outCur);
        var $outCurNew = $('.currencies.output').find('.'+inCur);
        $firstInputLi.removeClass('active');
        $firstOutputLi.removeClass('active');
        $inCurNew.addClass('active');
        $outCurNew.addClass('active');
        $('.currencies.input').prepend( $inCurNew ).removeClass('open');
        $('.currencies.output').prepend( $outCurNew ).removeClass('open');
        updateRates();
    })
    var updateRates = function () {
        var amount = $('#curr_amount_in').val();
        if (!amount || amount=='0'){
            $('#curr_amount_in').addClass('error');
            return false;
        }
        var data = {
            amount : amount,
            from : $('.currencies.input li:eq(0)').attr('class').substring(0,3),
            to : $('.currencies.output li:eq(0)').attr('class').substring(0,3)
        };
        $.ajax({
            data: data,
            url: '/shroff',
            cache: false,
            dataType: 'json',
            success: function(response) {
                if (response.result){
                    $('#curr_amount_out').val(response.response);
                }else{
                    alert(response.response);
                }
            },
            error: function (a) {
                alert(response.response);
            }
        });
    }
});