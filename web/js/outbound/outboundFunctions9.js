/**
 * Created by Boris on 7/29/2016.
 * Java Script functions for Outbound panel
 */

var setValidation = 0;

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

    $('.faqQuest').click(function(){
        $(this).addClass('bChecked');;
    });
    $(".quest_cont_drugo,.firstInfo").click(function(){
        if ($(this).hasClass('noCheck') == false) {
            $(this).addClass('bChecked');
        }
    });

});

window.onerror = function(message, file, line) {
    var opId = $('#operator').val();
    outboundLogError('Panel:' + countryLocation + '\nErr: ' + file + ':' + line + '\n' + message, opId);
};

$(document).ajaxError(function(e, xhr, settings) {
    var opId = $('#operator').val();
    outboundLogError('Panel:' + countryLocation + '\n Err: ' + settings.url + ':' + xhr.status + '\n\n' + xhr.responseText, opId);
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
function changeFlag(rId,fId,rowid,submitId,newPrice) {
    var podaci = {};
    podaci["action"]    = "changeOutboundFlag";
    podaci["table"]     = "phone_order_outbound";
    podaci["id"]        = rId;
    podaci["value"]     = fId;
    podaci["submit"]    = submitId;
    podaci["ouid"]      = $('#operator').val();
    podaci["newPrice"]  = newPrice;
    podaci["validation"]= 0;
    podaci["phonenum"]  = $('#callerPhone').text();
    podaci["state"]     = $('#stateCode').val();

    if ($('#outCancelStatus').val() != ""){
        podaci["source"]    = $('#outCancelStatus').val();
    } else {
        podaci["source"]    = "1";
    }


    if (setValidation == 1 || setValidation == 2){
        podaci["validation"]= setValidation;
    }



    if (fId == 14) {
        deleteTableRow(rowid);
    }



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

// //*********************************************************************
// //********** Promjena vremena za zvanje kupca **********
// //*********************************************************************
// function changeTimeToCall(element, wtGrom, wtTo) {
//     var datumZvanja = "";
//     var vrijeme = "";
//     var wtFrom = wtFrom;
//     var wtTo = wtTo;
//
//     if (element == 1) {
//         datumZvanja = $('#pickToCall').val();
//         vrijeme = $('#pickTimeToCall').val();
//         var checktime = checkWorkingHour(vrijeme, wtFrom, wtTo);
//         if (checktime == true) {
//             $('#o_quest4a').show();
//         }
//     } else if (element == 2) {
//         datumZvanja = $('#pickToCall2').val();
//         vrijeme = $('#pickTimeToCall2').val();
//         var checktime = checkWorkingHour(vrijeme, wtFrom, wtTo);
//         if (checktime == true) {
//             $('#o_quest5e').show();
//         }
//     } else {
//         return false;
//     }
//
//     var podaci = {};
//     podaci["action"]    = "changeTimeToCall";
//     podaci["timeVal"]   = datumZvanja+" "+vrijeme;
//     podaci["recId"]     = $('#recordId').val();
//
//     $.ajax({
//         url: httpSiteURL+"MainOutboundAjax",
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

// //*********************************************************************
// //********** Provjera radnog vremena  *********************************
// //*********************************************************************
//
// function checkWorkingHour(workTime, wtFrom, wtTo){
//
//     // var sati1   = workTime.split(" ");
//     var sati2   = workTime.split(":");
//
//     var satiCheck  = sati2[0];
//     var wtFrom = wtFrom;
//     var wtTo = wtTo;
//
//     if (satiCheck < wtFrom || satiCheck > wtTo){
//         showWarning(postponedText);
//         return false;
//     } else {
//         return true;
//     }
//
// }
//*********************************************************************
//********** Promjena kolicine proizvoda **********
//*********************************************************************

function changeOutboundQuantity(quant) {

    if (quant == 'n') {
        $('#additionalNum').show();
        $('#additionalNum2').show();
        $('#dodajN').show();
        $('.qb1,.qb2,.qb3').removeClass("bChecked");
        $('#hasPost').val("0");
        $('#dodajN2').show();
    } else {
        var addQuantToField = quant;
        if (quant == 'd') {
            addQuantToField = $('#additionalNum').val();

        } else if (quant == 'd2') {
            addQuantToField = $('#additionalNum2').val();

        } else {
            if (quant == "1"){
                $('.qb2,.qb3.qb4').removeClass("bChecked");
                $('#hasPost').val("1");
            } else if (quant == "2") {
                $('.qb1,.qb3,.qb4').removeClass("bChecked");
                $('#hasPost').val("1");
            } else if (quant == "3") {
                $('.qb1,.qb2,.qb4').removeClass("bChecked");
                $('#hasPost').val("0");
            }

        }

        $('#quantity').val(addQuantToField);
        var calcPrice = "";
        var aktuelnaValuta = $('#dynCurrency').val();

        if (quant == "1"){
            calcPrice = parseFloat($('#price').val()).toFixed(2);
        } else {
            calcPrice = (parseFloat($('#price').val()) + (parseFloat($('#upsell').val()) * (parseInt(addQuantToField)-1))).toFixed(2);
        }
        $('#calcPrice').val(parseFloat(calcPrice).toFixed(2));

        $('.firstPriceBox').show();
        $('.priceFirst').empty();
        $('.priceFirst').append('Total price: '+calcPrice+' '+aktuelnaValuta);

    }
    $('#outCancelStatus').val("13");
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
function formaCheckOutbound(){

    // Provera podataka iz forme

    var error = false;
    var a = {};// Promjenjiva za smještanje podataka iz forme
    // Provera imena

    var $ime = $('#ime');
    if($ime.val().length < 1 || $ime.val() == content[100] || $ime.val() == content[101]){
        $ime.addClass('crvenaS');
        $ime.val(content[101]);
        error = true;
    }
    else {
        a.name=$.trim($ime.val());
    }
    // Provera prezimena

    var $prezime = $('#surname');
    if($prezime.val().length < 1 || $prezime.val() == content[102] || $prezime.val() == content[103]) {
        $prezime.addClass('crvenaS');
        $prezime.val(content[103]);
        error = true;
    }
    else{
        a.surname=$.trim($prezime.val());
    }
    // Provera ulice

    var street_name = "";
    var $adresa = $('#address');
    if($adresa.val().length < 1 || $adresa.val() == content[104] || $adresa.val() == content[105]){
        $adresa.addClass('crvenaS');
        $adresa.val(content[105]);
        error = true;
    }
    else{
        a.address = $.trim($adresa.val());
    }
// Provera kucnog broja

    var street_number = "";
    var $number = $('#number');
    if($number.val().length < 1 || $number.val() == content[106] || $number.val() == content[107]){
        $number.addClass('crvenaS');
        $number.val(content[107]);
        error = true;
    }
    else{
        a.houseno=$.trim($number.val());
    }
    // Provera poštanskog broja

    var $brPoste = $('#postal');
    if($brPoste.val().length < 1 || $brPoste.val() == content[110] || $brPoste.val() == content[111]) {
        $brPoste.addClass('crvenaS').val(content[111]);
        error = true;
    }
    else {
        a.postcode=$.trim($brPoste.val());
    }
    // Provera grada
    var $grad = $('#city');
    if($grad.val().length < 1 || $grad.val() == content[108] || $grad.val() == content[109]){
        $grad.addClass('crvenaS').val(content[109]);
        error = true;
    }
    else {
        a.city=$.trim($grad.val());
    }
    // Provera telefona
    var $tel = $('#phone');

    // Provera telefona  // - uklonjena validacija broja s obzirom da korisnik moze da ne da info o broju

    a.telephone=$.trim($tel.val());
    // Provera email-a

    var $email = $('#email');
    if ($email == '') {

        a.email=("No mail");

    }  else {

        a.email=$.trim($email.val());
    }

    //landingpage
    var landingpageVal=$("#hidden_lp").val();
    a.landingpage=landingpageVal;
    a.landing_page=landingpageVal;

    //ip address
    var ip_address=$("#ip_address").val();
    a.ip=ip_address;

    //user agent address
    var ua=$("#http_ua").val();
    a.HTTP_USER_AGENT=ua;

    //user agent address
    var rf=$("#http_rf").val();
    a.HTTP_REFERER=rf;

    // Komentar ako postoji

    var postponedDeliv  = $('#postponedDeliv').val();
    var postponedAppend = "";

    if (postponedDeliv != undefined && postponedDeliv != "" && postponedDeliv != null && postponedDeliv.length > 5){
        postponedAppend = " [DELIVERY POSTPONED FOR: " + postponedDeliv + "] ";
    }

    var $komentar = "Outbound[cid" + $('#recordId').val() + "]" + postponedAppend + $('#comment1').val();
    if($komentar != 'Komentar' && $komentar.length > 0){
        a.comment=$.trim($komentar);
    }

    var jedinicnaCijena = $('#price').val();
    var totalCijena     = jedinicnaCijena;
    var totalSum        = 0;
    var productO = {};
    var productU = {};
    var productE = {};
    productO.full_sku = $('#full_sku').val();
    productO.discount = 0;
    a.order_items = [];
    var konacnaKolicina = $('#quantity').val();
    var hasPost = $('#hasPost').val();
    var stateCode = $('#stateCode').val();

    if (konacnaKolicina > 1){
        totalCijena = (parseFloat(jedinicnaCijena) + (parseFloat($('#upsell').val())*parseInt(konacnaKolicina-1)));// / parseFloat(konacnaKolicina);
        totalSum = totalCijena;

        if (stateCode == "BA"){
            totalCijena = jedinicnaCijena;
            productO.price = totalCijena;
            productO.quantity = 1;
            a.order_items.push(JSON.stringify(productO));

            for (i = 0; i < konacnaKolicina-1; i++) {
                var upsellCijenaO = parseFloat($('#upsell').val());
                productU.full_sku = $('#full_sku').val();
                productU.discount = 0;
                productU.price = upsellCijenaO;
                productU.quantity = 1;
                a.order_items.push(JSON.stringify(productU));
            }

            if(hasPost == "1"){
                productE.full_sku = "0011-666-0283";
                productE.discount = 0;
                productE.price = 2.00;
                productE.quantity = 1;

                a.order_items.push(JSON.stringify(productE));
            }
        } else {

            productO.price = totalCijena;
            productO.quantity = konacnaKolicina;


            a.order_items.push(JSON.stringify(productO));
        }
    } else {

        totalSum = totalCijena;
        productO.price = totalCijena;
        productO.quantity = konacnaKolicina;

        a.order_items.push(JSON.stringify(productO));

        if (stateCode == "BA"){
            if(hasPost == "1"){
                productE.full_sku = "0011-666-0283";
                productE.discount = 0;
                productE.price = 2.00;
                productE.quantity = 1;

                a.order_items.push(JSON.stringify(productE));
            }
        }
    }
    var stCo         = $('#stateCode').val();
    var fullSku      = $('#full_sku').val();
    var skusplit    = fullSku.split("-");
    var prId        = parseInt(skusplit[2]);
    
    console.log(stCo + ' stCo');
    console.log(prId + ' prId');
    //var profilId    = checkProfileByProduct(stCo, prId);

    var imeProizvoda= $('#prodName').val();
    a.product       = konacnaKolicina+"x "+totalSum;
    a.product_name  = imeProizvoda;
    a.ordersource   = "PHN";
    //a.profile = profilId;
    a.state         = stateCode;
    a.paymentmethod = "COD";
    a.postage       = hasPost;
    a.codservice    = "No";
    a.orderdate     = getNowDate();
    a.extint2       = 3;
    a.rpd_id        = $('#rpdID').val();
    a.product_id    = prId;

    var outPanelType = $("#outReqType").val();
    if (outPanelType == "1"){
        a.extint2       = 10;
    }

    //definisanje paketa proizvoda koji se salje

    if(error){
        return false;
    }
    else {

        console.log(a);
        //return false;
        //Slanje podataka
        $('#zakljuci').attr('disabled', 'disabled');
        $.post(httpSiteURL+"api/kontakt.deamonTest.php", a, function (res) {

            console.debug(res);
            if (res.result == "OK") {
                $('#confirm').slideDown("slow");
                var orderSubmitId = res.rand;
                $('#confirm').show();
                // Blokada dugmeta za otkazivanje narudzbe i za ponovno narucivanje
                $('#otkazi_Button').attr('disabled', 'disabled');
                //$('#zakljuci').attr('disabled', 'disabled');
                //Salje END event u varijablu
                changeFlag($('#recordId').val(),7,0,orderSubmitId, ' , newPrice = '+String(totalSum));
                setTimeout(function(){
                    $('#confirm').hide();
                    window.close();
                },1510);
            } else {
                $('#confirm').empty().html('Error!').delay(3000).fadeOut(3000);
                changeFlag($('#recordId').val(),8);
            } return false;
        }, 'json');
    }
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

//*********************************************************************
//********** SETUP ZA B/W LISTU ***************************************
//*********************************************************************
function setValidate(parameter){
    setValidation = parameter;

    // Parameter description
    // 1 - Black list
    // 2 - White List
}

function checkIfUpsellMade(){
    var quantCheck = $('#quantity').val();
    if (quantCheck == 1){
        $('#o_quest42a').show();
    } else {
        $('#o_quest44').show();
    }
}

function openNextField(field){
    var validName    = $('#ime').val();
    var validSurname = $('#surname').val();
    var validAddress = $('#address').val();
    var validNum     = $('#number').val();
    var validCity    = $('#city').val();
    var validPostal  = $('#postal').val();
    var validPhone   = String($('#phone').val());
    var validMail    = $('#email').val();
    var validComment = $('#comment1').val();

    if (field == "korak2"){

        if (validName.length > 2 && validSurname.length > 2 && validName !== "ERROR" && validSurname !== "ERROR"){
            $('#ime').css("color","black");
            $('#surname').css("color","black");
            $('#korak1').addClass("bChecked");
            $('.korak2').show();
        } else {
            if (validName.length < 3){
                $('#ime').val("ERROR");
                $('#ime').css("color","red");
            } else if (validSurname.length < 3){
                $('#surname').val("ERROR");
                $('#surname').css("color","red");
            }
        }

    } else if (field == "korak3"){
        if (validAddress.length > 2 && validNum.length > 0 && validAddress !== "ERROR" && validNum !== "ERROR") {
            $('.korak3').show();
            $('#address').css("color","black");
            $('#number').css("color","black");
            $('#korak2').addClass("bChecked");
        } else {
            if (validAddress.length < 3){
                $('#address').val("ERROR");
                $('#address').css("color","red");
            } else if (validNum.length < 1){
                $('#number').val("ERROR");
                $('#number').css("color","red");
            }
        }

    } else if (field == "korak4"){

        if (validCity.length > 1 && validPostal.length > 0 && validCity !== "ERROR" && validPostal !== "ERROR") {
            $('.korak4').show();
            $('#postal').css("color","black");
            $('#city').css("color","black");
            $('#korak3').addClass("bChecked");
        } else {
            if (validCity.length < 2){
                $('#city').val("ERROR");
                $('#city').css("color","red");
            } else if (validPostal.length < 1){
                $('#postal').val("ERROR");
                $('#postal').css("color","red");
            }
        }


    } else if (field == "korak5"){
        if (validPhone.length > 4 && validPhone !== "ERROR") {
            $('.korak5').show();
            $('#phone').css("color","black");
            $('#korak4').addClass("bChecked");
        } else {
            $('#phone').val("ERROR");
            $('#phone').css("color","red");
        }

    } else if (field == "korak6"){

        if (validMail.length > 7  && validMail !== "ERROR") {
            $('.korak6').show();
            $('#email').css("color","black");
            $('#korak5').addClass("bChecked");
        } else {
            $('#email').val("ERROR");
            $('#email').css("color","red");
        }
    } else if (field == "korak6a"){

        if (validComment.length > 3 && validComment !== "ERROR") {
            $('.korak6a').show();
            $('#comment1').css("color","black");
            $('#korak6').addClass("bChecked");
        } else {
            $('#comment1').val("ERROR");
            $('#comment1').css("color","red");
        }


    }

}

function updateCallersData(position){
    var podaci = {};
    var dated   = "";
    var datem   = "";
    var datey   = "";

    podaci["action"]    = "updateCallersData";


    if (position == 1){

        podaci["callerId"]  = $('#callerId').val();
        podaci["name"]      = $('#ui_name').val();
        podaci["surname"]   = $('#ui_surname').val();
        podaci["address"]   = $('#ui_address').val();
        podaci["homeNo"]    = $('#ui_houseno').val();
        podaci["city"]      = $('#ui_city').val();
        podaci["postal"]    = $('#ui_postal').val();
        podaci["telephone"] = $('#ui_telephone').val();
        podaci["email"]     = $('#ui_mail').val();


        dated   = $('#ui_birthd option:selected').val();
        datem   = $('#ui_birthm option:selected').val();
        datey   = $('#ui_birthy option:selected').val();

    } else if (position == 2){

        podaci["callerId"]  = $('#callerId').val();
        podaci["name"]      = $('#_name').val();
        podaci["surname"]   = $('#_surname').val();
        podaci["address"]   = $('#_address').val();
        podaci["homeNo"]    = $('#_houseno').val();
        podaci["city"]      = $('#_city').val();
        podaci["postal"]    = $('#_postal').val();
        podaci["telephone"] = $('#_telephone').val();
        podaci["email"]     = $('#_mail').val();


        dated   = $('#_birthd option:selected').val();
        datem   = $('#_birthm option:selected').val();
        datey   = $('#_birthy option:selected').val();
    }


    if (dated != "" && datem != "" && datey != ""){
        podaci["birthdate"] = datey+"-"+datem+"-"+dated;
    } else {
        podaci["birthdate"] = "";
    }

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