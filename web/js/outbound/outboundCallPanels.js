var stateCode = $('#state').val();
var content =JSON.parse($('#content').val());

//*********************************************************************
//********** Promjena vremena za zvanje kupca **********
//*********************************************************************
function changeTimeToCall(element, wtFrom, wtTo) {
    var datumZvanja = "";
    var vrijeme = "";
    var wtFrom = wtFrom;
    var wtTo = wtTo;

    if (element == 1) {
        datumZvanja = $('#pickToCall').val();
        vrijeme = $('#pickTimeToCall').val();
        var checktime = checkWorkingHour(vrijeme, wtFrom, wtTo);
        if (checktime == true) {
            $('#o_quest4a').show();
        }
    } else if (element == 2) {
        datumZvanja = $('#pickToCall2').val();
        vrijeme = $('#pickTimeToCall2').val();
        var checktime = checkWorkingHour(vrijeme, wtFrom, wtTo);
        if (checktime == true) {
            $('#o_quest5e').show();
        }
    } else {
        return false;
    }

    var podaci = {};
    podaci["action"]    = "changeTimeToCall";
    podaci["timeVal"]   = datumZvanja+" "+vrijeme;
    podaci["recId"]     = $('#recordId').val();

    $.ajax({
        url: httpSiteURL+"MainOutboundAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data > 0)
            {
                console.log(data);
            }
        }
    });
    return false;
}

//*********************************************************************
//********** Provjera radnog vremena  *********************************
//*********************************************************************

function checkWorkingHour(workTime, wtFrom, wtTo){

    // var sati1   = workTime.split(" ");
    var sati2   = workTime.split(":");

    var satiCheck  = sati2[0];
    var wtFrom = wtFrom;
    var wtTo = wtTo;

    if (satiCheck < wtFrom || satiCheck > wtTo){
        showWarning(postponedText);
        return false;
    } else {
        return true;
    }

}

//*********************************************************************
//********** ALERT  BOXES ***********************************************
//*********************************************************************

function showError($msg) {

    $('#messageE').empty();
    $('#messageE').append($msg);
    $('.errorB').fadeIn('fast');
    setTimeout(function(){$('.errorB').fadeOut('fast');},3000);
}
function showSuccess($msg) {

    $('#messageS').empty();
    $('#messageS').append($msg);
    $('.successB').fadeIn('fast');
    setTimeout(function(){$('.successB').fadeOut('fast');},3000);
}
function showWarning($msg) {

    $('#messageW').empty();
    $('#messageW').append($msg);
    $('.warningB').fadeIn('fast');
    setTimeout(function(){$('.warningB').fadeOut('fast');},3000);
}