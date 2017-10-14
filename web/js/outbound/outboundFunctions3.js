/**
 * Created by Boris on 7/29/2016.
 * Java Script functions for Outbound panel
 */
$(document).ready(function() {
    $('.quest_cont').click(function () {
        //var border=1;
        //if($(this).attr("id")=="quest2a" &&  ($(this).outerWidth() - $(this).innerWidth())/2==3)
        //{
        //    border=3;
        //}
        $(this).css('background-color', '#34B84F');
        $(this).css('color', '#fff');
        $(this).addClass('qChecked');
        $(this).css('background-image', 'url("../../images/check-mark-white.png")');

    });

    $('button').click(function()
    {
        if ($(this).hasClass("initNumYes")){

        } else {
            $(this).addClass('bChecked');
        }
    });



});
//*********************************************************************
//********** Skrolanje na operatorskom panelu **********
//*********************************************************************
function scrollToTarget(){
    $('html, body').animate({
        scrollTop: $("#operatorPanel").offset().top
    }, 1000);
}

//*********************************************************************
//********** Promjena statusa outbound recorda **********
//*********************************************************************
function changeFlag(rId,fId,rowid,submitId) {
    var callCounter = $('#callCount').val();

    if((fId == 2 || fId ==4) && callCounter >= 3 ){
        confirmCancelOutbound(1);
    } else {

        var podaci = {};
        podaci["action"] = "changeOutboundFlag";
        podaci["table"] = "phone_order_outbound";
        podaci["id"] = rId;
        podaci["value"] = fId;
        podaci["submit"] = submitId;
        podaci["ouid"] = $('#operator').val();
        podaci["state"] = $('#stateCode').val();

        if (fId == 6) {
            deleteTableRow(rowid);
        }


        podaci["source"]    = "3";
        $.ajax({
             url: httpSiteURL+"MainOutboundAjax",
            type:"POST",
            dataType:"JSON",
            data:podaci,
            async: true,
            success:function(data){
                if(data > 0)
                {
                    if (fId == 2 || fId == 3 || fId == 4 || fId == 5) {
                        $('#callConfirm').show('slow');
                        setTimeout(function(){
                            $('#callConfirm').hide();
                            window.close();
                        },1510);
                    }
                }
            }
        });
        return false;
    }
}

//*********************************************************************
//********** Promjena vremena za zvanje kupca **********
//*********************************************************************
// function changeTimeToCall(element) {
// var vrijeme = "";
//
//     if (element == 1) {
//         vrijeme = $('#pickToCall').val();
//         var checktime = checkWorkingHour(vrijeme);
//         if (checktime == true) {
//             $('#o_quest4a').show();
//         }
//     } else if (element == 2) {
//         vrijeme = $('#pickToCall2').val();
//         var checktime = checkWorkingHour(vrijeme);
//         if (checktime == true) {
//             $('#o_quest5e').show();
//         }
//     } else {
//         return false;
//     }
//
//     var podaci = {};
//     podaci["action"]    = "changeTimeToCall";
//     podaci["timeVal"]   = vrijeme;
//     podaci["recId"]     = $('#recordId').val();
//
//     $.ajax({
//          url: httpSiteURL+"MainOutboundAjax",
//         type:"POST",
//         dataType:"JSON",
//         data:podaci,
//         async: true,
//         success:function(data){
//             if(data > 0)
//             {
//                 console.log(data);
//             }
//         }
//     });
//     return false;
// }
//
// //*********************************************************************
// //********** Provjera radnog vremena  *********************************
// //*********************************************************************
//
// function checkWorkingHour(workTime){
//
//     var sati1   = workTime.split(" ");
//     var sati2   = sati1[1].split(":");
//
//     var satiCheck  = sati2[0];
//
//     if (satiCheck < 8 || satiCheck > 13){
//         showWarning("Zadato vrijeme nije u radnom vremenu call centra!");
//         return false;
//     } else {
//         return true;
//     }
//
// }
//*********************************************************************
//********** Promjena kolicine proizvoda **********
//*********************************************************************

function changeUpsellOutboundQuantity(quant) {

    var actualQuant = $('#actualQuant').val();
    var addQuantToField = parseInt(quant) + parseInt(actualQuant);
    var calcPrice = "";

    if (quant == 'n') {
        $('#additionalNum').show();
        $('#dodajN').show();
        $('#qb1,#qb2,#qb3').removeClass("bChecked");
        $('#hasPost').val("0");
    } else {

        if (quant == 'd') {
            addQuantToField = parseInt($('#additionalNum').val()) + parseInt(actualQuant);
            $('#hasPost').val("0");
        } else {
            if (quant == "0"){
                $('#qb2,#qb3,#qb4').removeClass("bChecked");
            } else if (quant == "1") {
                $('#qb1,#qb3,#qb4').removeClass("bChecked");
            } else if (quant == "2") {
                $('#qb1,#qb2,#qb4').removeClass("bChecked");
            }
        }
    }

    $('#quantity').val(addQuantToField);

    if (addQuantToField == "1"){
        calcPrice = parseFloat($('#price').val()).toFixed(2);
    } else {
        calcPrice = parseFloat($('#price').val()) + (parseFloat($('#upsell').val()) * (parseInt(addQuantToField)-1));
    }
    $('#calcPrice').val(parseFloat(calcPrice).toFixed(2));

    $('.firstPriceBox').show();
    $('.priceField').empty();
    $('.priceField').append('Total price: '+calcPrice+' KM');

    if (addQuantToField == "1"){
        $('#hasPost').val("1");
    } else if (addQuantToField == "2") {
        $('#hasPost').val("1");
    } else  {
        $('#hasPost').val("0");
    }

    if (addQuantToField > actualQuant){
        $('#upsellChangeMade').val("1");
    } else {
        $('#upsellChangeMade').val("0");
    }

}

//*********************************************************************
//********** Provjera podataka forme **********************************
//*********************************************************************

function provjera_podataka_outbound(){
    $('#o_quest41').show();
    $('#podaci').show();
    $('#podaci').select();
    $('html, body').animate({
        scrollTop: $("#podaci").offset().top
    }, 1000);

    var $P_ime          =$('#ime').val();
    var $P_prezime      =$('#surname').val();
    var $P_adresa       =$('#address').val();
    var $P_number       =$('#number').val();
    var $P_grad         =$('#city').val();
    var $P_postanski    =$('#postal').val();
    var $P_telefon      =$('#phone').val();
    var $P_mail         =$('#email').val();
    var $P_proizvod     =$("#price").val();
    var $nazivPr        = $('#prodName').val();
    var komada          = $('#quantity').val();

    $(".P_proizvod").empty();
    $(".P_ime, .P_prezime, .P_adresa, .P_number, .P_grad, .P_postanski, .P_telefon, .P_mail").val("");
    $(".P_ime").val($P_ime);
    $(".P_prezime").val($P_prezime);
    $(".P_adresa").val($P_adresa);
    $(".P_number").val($P_number);
    $(".P_grad").val($P_grad);
    $(".P_postanski").val($P_postanski);
    $(".P_telefon").val($P_telefon);
    $(".P_mail").val($P_mail);
    $(".P_proizvod").append($nazivPr + " " + $P_proizvod);

    if (komada > 1){
        var cijena = (parseFloat($P_proizvod) + (parseFloat($('#upsell').val())*parseInt(komada-1)));
        $(".P_proizvod").empty();
        $(".P_proizvod").append($nazivPr + " " + cijena);
    }

    checkForFreePost(komada);
}
//*********************************************************************
//********** Provjera podataka forme posebno svaki element ************
//*********************************************************************
function elementActive(element, val, greska, blur){
    // Funkcija za fokus i blur input elementa argumenti element, value i value kod greške
    var $element = $(element);

    if(arguments.length == 4){
        // Element je upravo izgubio fokus
        if($element.val().length < 1 || $element.val() === val || $element.val() === greska){
            $element.val(greska).addClass('crvenaS');
        }
    }
    else{
        // Element je u fokusu
        if(element != document.getElementById('city')){
            if($element.val() == val || $element.val() == greska) {
                $element.val('').removeClass('crvenaS');
            }
        }

        if(element == document.getElementById('city')){
            $element.removeClass('crvenaS');
            if(($element.val() == content[109]) || ($element.val() == content[108])){
                $element.val('');
            }
        }
    }
}

//*********************************************************************
//********** ZAKLJUCIVANJE NARUDZBE ***********************************
//*********************************************************************
function confirmCancelOutbound(tip){
        var podaci = {};
        podaci["action"]    = "changeOrderStatus";
        podaci["submitId"]  = $('#submitId').val();
        podaci["recordId"]  = $('#recordId').val();
        podaci["formPrice"] = $('#formPrice').val();

        podaci["status"]    = tip;

        $.ajax({
             url: httpSiteURL+"MainOutboundAjax",
            type:"POST",
            dataType:"JSON",
            data:podaci,
            async: true,
            success:function(data){
                console.log(data);
                if(data > 0)
                {
                    $('#confirm').slideDown("slow");
                    setTimeout(function(){
                       $('#confirm').hide();
                        window.close();
                    },1510);
                } else {
                    $('#confirm').empty().html('Error!').delay(3000).fadeOut(3000);
                    changeFlag($('#recordId').val(),8);
                    setTimeout(function(){
                        $('#confirm').hide();
                        window.close();
                    },1510);
                }
            }
        });
        return false;
}

//*********************************************************************
//********** Otkazivanje narudzbe *************************************
//*********************************************************************
function cancelOrderOutbound(cType){

    var podaci = {};
    podaci["action"]    = "changeOMGcomment";
    podaci["submitId"]  = $('#submitId').val();
    podaci["recordId"]  = $('#recordId').val();
    podaci["cType"]     = cType;


    $.ajax({
         url: httpSiteURL+"MainOutboundAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data > 0)
            {
                $('#cancel').slideDown("slow");
                setTimeout(function(){
                    $('#cancel').hide();
                    window.close();
                },1510);
            } else {
                $('#confirm').empty().html('Error!').delay(3000).fadeOut(3000);
                changeFlag($('#recordId').val(),8);
                setTimeout(function(){
                    $('#confirm').hide();
                    window.close();
                },1510);
            }
        }
    });
    return false;
}

//*********************************************************************
//********** Indikator promjene podataka u formi **********************
//*********************************************************************

function dataHasChanged(){
    $('#dataChangeMade').val("1");
}

//*********************************************************************
//********** OTKAZIVANJE NARUDZBE ***********************************
//*********************************************************************
function cancelOutbound() {
    $('#cancel').slideDown("slow");
    changeFlag($('#recordId').val(),6);
    setTimeout(function(){
        $('#cancel').hide();
        window.close();
    },1510);
}