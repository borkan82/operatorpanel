function step1(){
    $('#o_quest2').show();
}

function step2(){
    $('#o_quest4').show();
}

function step3(){
    $('#o_quest4b').show();
}

function step4(){
    $('#o_quest0a').show();
}

function step5(){
    $('#o_quest0c').show();
}

function step6(){
    $('#o_quest6').show();
}

function step7(){
    $('#o_quest5b').show();
}

function step8(){
    $('#o_quest5d').show();
}

function step9(){
    $('#o_quest5f').show();
}

function step10(){
    $('#o_quest9a').show();
}
function step11(){
    $('#o_quest9').show();
}
function step12(){
    $('#o_quest9a').show();
}
function step13(){
    $('#o_quest9a1').show();
}
function step14(){
    $('#o_quest10').show();
}
function step15(){
    $('#o_quest12').show();
}

/*
DEFINED BUTTON STEPS
 */
function bstep1(){
    $('#o_quest7').show();
    $('#o_quest9a').show();
    $('#o_quest50').show();
    $('#o_quest3').hide();
    $('#o_quest4').hide();
    $('#o_quest5').hide();
    $('#o_quest5a').hide();
    $('#o_quest6').hide();
    $('#o_quest0').hide();
    $('#o_quest0a').hide();
    $('#o_quest0b').hide();
    $('#o_quest0c').hide();
}
function bstep2(){
    $('#o_quest3').show();
    $('#o_quest0').hide();
    $('#o_quest0a').hide();
    $('#o_quest0b').hide();
    $('#o_quest7').hide();
    $('#o_quest9a').hide();
    $('#o_quest9a1').hide();
    $('#o_quest10').hide();
    $('#o_quest11').hide();
    $('#o_quest12').hide();
    $('#o_quest50').hide();
}
function bstep3(){
    $('#o_quest0').show();
    $('#o_quest7').hide();
    $('#o_quest3').hide();
    $('#o_quest4').hide();
    $('#o_quest5').hide();
    $('#o_quest6').hide();
    $('#o_quest50').hide();
    $('#o_quest9a').hide();
    $('#o_quest9a1').hide();
    $('#o_quest10').hide();
    $('#o_quest11').hide();
    $('#o_quest12').hide();
}
function bstep4(){
    $('.pickDate').show();
    $('#noCall').hide();
}
function bstep5(){
    $('#o_quest5').show();
}
function bstep6(){
    showSuccess('Call finished');
    setTimeout(function(){
        window.close();
    },1510);
}
function bstep7(){
    $('#o_quest7').show();
    $('#o_quest9a').show();
    $('#o_quest50').show();
    $('#o_quest5a').hide();
    $('#o_quest0b').hide();
    $('#o_quest0c').hide();
}
function bstep8(){
    $('#o_quest0b').show();
    $('#o_quest7').hide();
    $('#o_quest9a').hide();
    $('#o_quest50').hide();
}
function bstep9(){
    $('#o_quest5a').show();
    $('#FAQholder').hide();
    $('#o_quest12').hide();
    $('#outCancelStatus').val("51");
    setValidate(1);
}
function bstep10(){
    $('#FAQholder').show();
    $('#o_quest11').show();
    $('#o_quest50').show();
    $('#o_quest5a').hide();
}
function bstep11(){
    $('#o_quest5c').show();
    $('#o_quest21').show();
    $('#o_quest50').show();
}
function bstep12(){
    $('#o_quest5a').show();
    $('#outCancelStatus').val("52");
}
function bstep13(){
    $('#o_quest7').show();
    $('#o_quest9a').show();
    $('#o_quest21').show();
}
function bstep14(){
    $('.pickDate2').show();
    $('#noCall2').hide();
}
function bstep15(){
    $('#o_quest11').show();
}
function bstep16(){
    $('#FAQholder').show();
    $('#o_quest11').show();
}
function bstep17(){
    $('#o_quest17').show();
}
function bstep18(){

}
function bstep20(){
    $('#postponedDeliv').show();
    $('#buttonDeliveryDate').hide();
    $('#delivDate').show();
    $('#appendToText').show();
}
function bstep21(){
    var dateToPostponeDeliv =  $('#postponedDeliv').val();
    var content =JSON.parse($('#content').val());

    $('#noFreePost').empty();
    $('#yesFreePost').empty();

    $('#noFreePost').append('<strong>'+ content[26]+'<span style="color:#1137FE">'+$('#dynPostar').val() +' '+$('#dynCurrency').val()+'. </span>'+content[93]+'<span style="color:#1137FE">' +dateToPostponeDeliv+'</span>.</strong>');
    $('#yesFreePost').append('<strong><span style="color:#1137FE">'+ content[68]+'. </span>'+content[93]+'<span style="color:#1137FE"> ' +dateToPostponeDeliv+'</span></strong>');


}