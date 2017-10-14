//*************** JS FUNKCIJE ZA PHONE ORDER *********************
//
//                         2015.
//
// Author:
// Boris
//****************************************************************
// ********** VARIJABLE ZA MIXPANEL I UPISIVANJE EVENTA **********
var dateEvent = "";
var callIdEvent = "";
var startEvent = "";
var typeEvent = "";
var endEvent = "";
var durationEvent = "0";
var cancelEvent = "NO";
var cancelReason = "---"
var otherOptEvent = "NO";
var otherOptReason = "---";
var productWorkEvent = "NO";
var getInvoiceEvent = "NO";
var buyStoreEvent = "NO";
var otherEvent = "NO";
var sucessEvent = "NO ORDER";
var datumStart = "";
var datumEnd = "";
var descR = "";
var tss = 0;
var tse = 0;
var totalTS = 0;
var orderFinished = false;
var fillFormObj = {};
var multiProduct = 0;
//******************** VARIJABLE ZA INFORMACIJE O KUPCU (SAMO U JSON)
var jsonName ="";
var jsonSurname = "";
var jsonStreet = "";
var jsonCity = "";
var jsonPhone = "";
var jsonMail = "";
var jsonCode = "---";
var jsonCampaign = "";
var jsonPackage = "";
var jsonOrderType = "";
var jsonQuantNum = 0;
var jsonUser = "";
var jsonProduct = "";
var orderTypeDef = 0;
var konacnaKolicina = 1;
var hasPost = "1";
var fillIme = "";
var fillPrezime = "";
var bPrice = 0.00;
var ePrice = 0.00;
var sessionId = 0;
var cancelStatus = 0;
var htmlOfFlow = {};
/****************** PRIVREMENI FIX ZA VARIJABLE NA KRAJU NARUDZBE ****************
 var cancelText = "NARUDŽBA OTKAZANA!";
 var openText = "Otvori novu narudžbu";
 var unMsg = "Fill fields";
 if (countryLocation == "MK") {
   cancelText = "НАРАЧКАТА Е ОТКАЖАНА!";
   openText = "Отвори нова нарачка";
} else if (countryLocation == "SI") {
  cancelText = "NAROČILO PREKLICANO!";
  openText = "Odpri novo naročilo";
} else if (countryLocation == "BG") {
  cancelText = "ПОРЪЧКАТА Е ОТКАЗАНА!";
  openText = "Отваряне на нова поръчка";
} else if (countryLocation == "RO") {
  cancelText = "COMANDA ANULATĂ!";
  openText = "Deschide comanda nouă";
} else if (countryLocation == "LV") {
  cancelText = "PASŪTĪJUMS ATCELTS!";
  openText = "Veikt jaunu pasūtījumu";
} else if (countryLocation == "IT") {
  cancelText = "ORDINE ANNULLATA!";
  openText = "Aprire nuovo ordine";
}
 */


//********** FIX ZA DATUM I VRIJEME NA DVOCIFRENEBROJEVE  ************************
function addLeadingChars(string, nrOfChars, leadingChar) {
    string = string + '';
    return Array(Math.max(0, (nrOfChars || 2) - string.length + 1)).join(leadingChar || '0') + string;
}


//********** ZAPIS EVENTA U JSON  **********************************
function writeJSONfile(submitOrderId){
    totalTS = tse - tss;
    durS = addLeadingChars(Math.floor((totalTS / 1000) % 60));
    durM = addLeadingChars(Math.floor((totalTS / 1000) / 60));
    durationEvent = durM + ':' + durS;
    jsonOrderType = orderTypeDef;

    var tipOpPanela = $('#panelType').val();

    if (tipOpPanela == "multipanel"){
        jsonOrderType = 5;
    }

    jsonName = $('#ime').val();
    jsonSurname = $('#surname').val();
    jsonStreet = $('#address').val()+" "+$('#number').val();
    jsonCity = $('#city').val();
    jsonPhone = $('#phone').val();
    jsonMail = $('#email').val();
    jsonUser = $('#idHidden').val();
    var cancelParam = $('#cancelStatus').val();
    var flowType = $('#showPanel').val();




    if (jsonUser == "" || jsonUser == null || jsonUser == undefined || jsonUser === 'undefined' || typeof jsonUser === 'undefined'){
        jsonUser == "26"; // 26 is ID for unknown user if error occures
    }

    jsonProduct = $('#product_f').val();
    if (jsonProduct == "" || jsonProduct == null || jsonProduct == undefined || jsonProduct === 'undefined' || typeof jsonProduct === 'undefined'){
        jsonProduct == "0"; // 0 is ID for unknown product if error occures
    }

    jsonPackage = $('.aktivniSp').data('value');

    if (jsonPackage !== "" && jsonPackage !== null && jsonPackage !== undefined){
        var jsonQuantity = jsonPackage.split("|");
        jsonQuantNum = $.trim(jsonQuantity[1]);
        jsonQuantNum = jsonQuantNum.replace("x", "");
    }

    if ($('#code').val() !==""){
        jsonCode = $('#code').val();
    }

    var campa = $('#testCamp').text();
    if (campa !== "" && campa !== undefined && campa.length > 4){
        jsonCampaign = campa;
    }


    //var dataString = new Array();
    //CALLS TO DATABASE FIX .... ADAPTED OLD SYSTEM TO JSON
    if (typeEvent == "ORDER"){
        typeEvent = "1";
    } else {
        typeEvent = "2";
    }

    if (cancelEvent == "NO"){
        cancelEvent = "0";
    } else {
        cancelEvent = "1";
    }

    if (cancelParam != null && cancelParam != "" && cancelParam != undefined){
        cancelStatus = cancelParam;
    }

    sessionId = getCookie("__insp_sid");

    if (navigator.cookieEnabled == false){
        sessionId = "1";
    }


    dataString = {action:"writePhoneOrder", country:countryLocation, orderType:jsonOrderType, date:dateEvent, codeNum:jsonCode, start:startEvent, end:endEvent, duration:durationEvent, type:typeEvent, otherOpt:otherOptEvent, productWork:0, getInvoice:0, buyStore:0, other:otherEvent, sucess:sucessEvent, cancel:cancelEvent, cancelRe:cancelReason, showName:jsonName, showSurname:jsonSurname, showStreet:jsonStreet, showCity:jsonCity, showPhone:jsonPhone, showMail:jsonMail, campaign:jsonCampaign, quantity:konacnaKolicina, korisnik:jsonUser, proizvod:jsonProduct, submitOrder:submitOrderId, baseInPrice:bPrice, endInPrice:ePrice, writeSession:sessionId, cancelStat:cancelStatus, showFlow:flowType};

    //*********************************************************************
    // PROVJERI DA LI IMA PROIZVODA U SPECIJALNOJ PONUDI PA OTVORI PROZOR
    //*********************************************************************

    var productLen = $('#specialProd option').length;

    if (productLen > 0 && sucessEvent == "ORDERED!") {
        $('.specialPopup').fadeIn('slow');
    } else {
        $('#confirm').show('fast');
        $('html, body').animate({
            scrollTop: $("#confirm").offset().top
        }, 1000);
    }
    $('.newOrder').empty();
    $('.newOrder').append("<strong>"+openText+"</strong>");

    var callIdNum = $('#callId').val();
    callDown(callIdNum);

    $.ajax({
        type:"POST",
        dataType:"JSON",
        url: httpSiteURL+'MainAjax',
        data: dataString,
        success: function(msg) {
            $('#phCall_id').val(msg);
        }
    });
}
//********** Dodaj proizvod u phonecall ako je special napravljen  **********************
function addToPhoneCall(callId, product_id){
    var addProduct = {action:"addSpecialToCall", callId:callId, product_id:product_id};
    $.ajax({
        type:"POST",
        dataType:"JSON",
        url: httpSiteURL+'MainAjax',
        data: addProduct,
        success: function(msg) {
            console.log(msg);
        }
    });
}
//********** START ORDER - pocetna podesavanja  **********************
function startO(attMessage){
    var startTS = new Date();
    tss = startTS.getTime();
    datumStart = new Date();
    mjesecS = addLeadingChars(datumStart.getMonth() + 1);
    danS = addLeadingChars(datumStart.getDate());
    satS = addLeadingChars(datumStart.getHours());
    minutS = addLeadingChars(datumStart.getMinutes());
    sekundaS = addLeadingChars(datumStart.getSeconds());

    dateEvent = danS + '.' + mjesecS + '.' + datumStart.getFullYear() + '.';
    takeServerTime("s");
    //startEvent = satS + ':' + minutS + ':' + sekundaS;

    // addEvent(document, "mouseout", function(e) {
    //     e = e ? e : window.event;
    //     var from = e.relatedTarget || e.toElement;
    //     if (orderFinished == false){
    //        if (!from || from.nodeName == "HTML") {
    //           // stop your drag event here
    //          // for now we can just use an alert   ****   POPUP JS ALERT ********
    //           alert(attMessage);
    //        }
    //     }
    // });

    $(document).on("keydown", function(f){
        if (orderFinished == false){
            if ((f.which || f.keyCode) == 116) {
                f.preventDefault();
                alert(attMessage);
            }
        }
    });
}
//********** START ORDER - pocetna podesavanja - END **********************

//********** Zavrsetak narudzbe *************************
function endOrder(status, submitOrderId) {

    var endTS = new Date();
    tse = endTS.getTime();
    datumEnd = new Date();
    satE = addLeadingChars(datumEnd.getHours());
    minutE = addLeadingChars(datumEnd.getMinutes());
    sekundaE = addLeadingChars(datumEnd.getSeconds());

    sucessEvent = status;
    takeServerTime("e");
    //endEvent = satE + ':' + minutE + ':' + sekundaE;

    if (datumEnd < datumStart) {
        datumEnd.setDate(datumEnd.getDate() + 1);
    }
    // durationEvent = endEvent - startEvent;

    if (status == "ORDERED!"){
        var orderDatum = "Completed: " + getNowDate();

        $('.confirmBox').show();
        $('#ocDate').append(orderDatum);
    }
    orderFinished = true;
    // UPISIVANJE U JSON SAMO ZA HRVATSKU!!!
    // if (countryLocation == "HR"){
    writeJSONfile(submitOrderId);
    // }
}
//**********  END Zavrsetak narudzbe *************************

//**********  POTVRDA OTKAZA NARUDZBE *************************
function cancelConfirm(){
    var potvrda ={}
    potvrda.box = $('#confirm');
    $('.newOrder').empty();
    $('.newOrder').append("<strong>"+openText+"</strong>");
    $('#otkazi_Button').attr('disabled', 'disabled');
    $('#zakljuci_Button').attr('disabled', 'disabled');
    $('#otkazBox').fadeOut('slow');
    potvrda.box.empty();
    potvrda.box.text(cancelText);
    potvrda.box.show();
    potvrda.box.select();

    var cancelDatum = "Completed: " + getNowDate();

    $('.confirmBox').show();
    $('#ccDate').append(cancelDatum);

    $('html, body').animate({
        scrollTop: potvrda.box.offset().top
    }, 1000);
}
//**********  END POTVRDA OTKAZA NARUDZBE *************************

//********** Event listener za popup  *************************
function addEvent(obj, evt, fn) {

    if (obj.addEventListener) {
        obj.addEventListener(evt, fn, false);
    }
    else if (obj.attachEvent) {
        obj.attachEvent("on" + evt, fn);
    }

}
//********** END Event listener za popup  **********************


//************* OTKAZIVANJE NARUDZBE ***************************
function otkaz(razlog) {

    if (razlog <= 3) {
        cancelConfirm();
        if (razlog == 1){
            cancelReason = "PriceHigh";
            cancelEvent = "YES";
        }
        else if (razlog == 2){
            cancelReason = "Postage";
            cancelEvent = "YES";
        }
        else if (razlog == 3){
            cancelReason = "NeedTime";
            cancelEvent = "YES";
        }
        endOrder("CANCELED!");
    }
    else {
        $('.otherReason').fadeIn('slow');
    }
}
//************* END  OTKAZIVANJE NARUDZBE ***************************

//************* INFORMACIJE O PROIZVODU  ***************************
function faqReason(faqRazlog) {
    if (faqRazlog == 1) {
        otherOptEvent += " > work";
    }
    else if (faqRazlog == 2){
        otherOptEvent += " > invoice";
    }
    else if (faqRazlog == 3){
        otherOptEvent += " > store";
    }
    else {
        otherEvent = "YES";
    }
}
//*************END INFORMACIJE O PROIZVODU  ***************************

//*************PROVJERA PODATAKA - FINAL ORDER***************************

function provjera_podataka(){
    $('#podaci').slideDown("slow");
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

    var $nazivPr         = $('#product_f').find('option:selected').text();
    //var fulskuExp       = fullsku.split("~");
    //var $nazivPr        = $.trim(fulskuExp[1])

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

    checkForFreePost(1);


}

//*************END INFORMACIJE O PROIZVODU  ***************************

//*************PROVJERA PODATAKA - FINAL ORDER***************************
function provjera_podatakaZeljka(){
    $('#podaci').slideDown("slow");
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

    if($("#priceCustomerPays").val() === ''){
        var $P_proizvod     =$("#price").val();
    } else {
        var $P_proizvod     =$("#priceCustomerPays").val();
    }


    if ($('#product_f').find('option:selected').val() !== ""){
        var $nazivPr                = $('#product_f').find('option:selected').text();
    } else if($('#cancelOrderProduct').find('option:selected').val() !== ''){
        var $nazivPr                = $('#cancelOrderProduct').find('option:selected').text();
       // $P_proizvod     =$("#cancelPriceToShow").val();
    }
    // var basePrice = $('#cancelPriceToShow').val();
    
    //
    // var $nazivPr         = $('#product_f').find('option:selected').text();
    //var fulskuExp       = fullsku.split("~");
    //var $nazivPr        = $.trim(fulskuExp[1])

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


    if($('#freeDeliveryOperater').val() == 1) {
        hasPost = "0";
    } else {
        checkForFreePost(1);
    }


}
//*************END PROVJERA PODATAKA - FINAL ORDER************************


//************  PROVJERA TACNOSTI ADRESE ZA OPERATERA ********************
function gMapCheck () {
    var gUlica = $('#address').val();
    var gBroj = $('#number').val();
    var gGrad = $('#city').val();
    var gPost = $('#postal').val();
    var gSearch = gUlica + "+" + gBroj + "+" + gGrad + "+" + gPost;

    window.open('http://www.google.com/maps?q='+gSearch, "_blank", "width=600, height=600, top=30%, left=45%");
}
// **************************************************************************
// ************** VIEW DATA FUNKCIJE ****************************************
//***************************************************************************


var stil = "";
var sel = "";
var sel2 = "";
var sel3 = "";
var inp1a = "01.01.2015.";
var inp1b = "31.12.2018.";
var jsonCCode = "";
var arrayCountry = [];
var arrayType = [];
var numShow = 0;
var ordStat = "";
var tipO = "";
var typeVal ="";
var dateJson = new Array();
var dateFromArr = new Array();
var dateToArr = new Array();
var dateBool = true;
var ordStat ="";

var tStampA = 1411423200000;
var datumA = "";
var datumAarr = "";
var datumAall = "";

var tStampB = 1531423200000;
var datumB = "";
var datumBarr = "";
var datumBall = "";

var tStampJ = 0;

// ******************** SELECT ZA PROMJENU ZEMLJE ZA KOJU ZELIMO PODATKE ***
function changeCountryView () {
    dateBool = true;
    var redni = 0;
    $("#rezult").empty();
    sel = document.getElementById("country").value;
    sel2 = document.getElementById("ordType").value;
    sel3 = document.getElementById("ordNum").value;
    if ($('#datumFrom').val() != ""){
        datumA = String($('#datumFrom').val());
        datumAarr = datumA.split(".");
        datumAall = datumAarr[1] + "." + datumAarr[0] + "." + datumAarr[2] + ".";
        var dateConvertedA = new Date(datumAall);
        tStampA = dateConvertedA.getTime();
        dateBool = false;
    }
    // dateFromArr[0]  dateFromArr[1]  dateFromArr[2]   dan, mjesec, godina
    if ($('#datumTo').val() != ""){
        datumB = String($('#datumTo').val());
        datumBarr = datumB.split(".");
        datumBall = datumBarr[1] + "." + datumBarr[0] + "." + datumBarr[2] + ".";
        var dateConvertedB = new Date(datumBall);
        tStampB = dateConvertedB.getTime();       //******** Razdvoji datume na tri dijela radi poredjenja ****
        dateBool = false;
    }
    // dateToArr[0]  dateToArr[1]  dateToArr[2]    dan, mjesec, godina


    if (sel == "ALL"){
        arrayCountry = ["BA", "HR", "RS"];
    } else if (sel == "HR"){
        arrayCountry = ["HR"];
    } else if (sel == "BA") {        //*********  PRETRAGA PO ZEMLJI *******
        arrayCountry = ["BA"];
    } else if (sel == "RS") {
        arrayCountry = ["RS"];
    }

    if (sel2 == "ALL"){
        arrayType = ["ORDER", "OTHER"];
    } else if (sel2 == "ORD"){
        arrayType = ["ORDER"];
    } else if (sel2 == "OTH"){        //*********  PRETRAGA PO TIPU *******
        arrayType = ["OTHER"];
    }

    if (sel3 == "num10"){
        numShow = 10;
    } else if (sel3 == "num50"){      //********* BROJ REZULTATA  *******
        numShow = 50;
    }






    for (i = 0; i < arrayCountry.length; i++){
        $.getJSON("jsonDB/json-"+arrayCountry[i]+".json", function(data) {
            var zadnji = data.length-1;
            var redniBr = 1;
            var hrefB = "";
            var hrefE = "";

            if (sel3 == "numAll") {
                numShow = data.length;

            }



            for (var i = 0; redni < numShow; i++) {

                var dateJson = data[zadnji]["date"].split(".");
                var dateJSONall = dateJson[1] + "." + dateJson[0] + "." + dateJson[2] + ".";
                var dateConvertedJ = new Date(dateJSONall);
                tStampJ = dateConvertedJ.getTime();
                hrefB = "";
                hrefE = "";

                for (var j = 0; j < arrayType.length; j++) {
                    tipO = data[zadnji]["type"];
                    ordStat = data[zadnji]["sucess"];

                    typeVal = arrayType[j];

                    if (ordStat == "CANCELED!"){
                        stil = "style='background-color: #FF8686'";
                    } else if (ordStat == "ORDERED!") {
                        stil = "style='background-color: #64FC7D'";
                        hrefB = "<a href='javascript:showBuyer("+zadnji+");'>";
                        hrefE = "</a>";
                    } else {
                        stil = "";
                    }



                    if(tipO == typeVal){
                        if (tStampJ >= tStampA && tStampJ <= tStampB || dateBool == true) {


                            redni = redni+1
                            $("#rezult").append("<div class='tableLine'><div class='listBox uski'"+stil+">"+ redni +"</div><div class='listBox srednji'"+stil+">"+ data[zadnji]["country"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["date"] +"</div><div class='listBox srednji'"+stil+">"+ data[zadnji]["codeNum"] +"</div><div class='listBox srednji'"+stil+">"+hrefB+ data[zadnji]["callId"] +hrefE+"</div><div class='listBox'"+stil+">"+ data[zadnji]["start"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["end"] +"</div><div class='listBox srednji'"+stil+">"+ data[zadnji]["duration"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["type"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["otherOpt"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["productWork"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["getInvoice"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["buyStore"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["other"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["sucess"] +"</div><div class='listBox srednji'"+stil+">"+ data[zadnji]["cancel"] +"</div><div class='listBox'"+stil+">"+ data[zadnji]["cancelRe"] +"</div></div>");

                            redniBr++;
                            hrefB="";
                            hrefE="";

                        }
                    }
                } zadnji = zadnji - 1;
            }
        });
    }
}
//********** FUNKCIJE POPUPA ZA PRIKAZ INFORMACIJA O KUPCU ***************
function showBuyer(idPopup){

    $('.showBuyerPopup').fadeIn('slow');

    $('#showName,#showSurname,#showStreet,#showCity,#showPhone,#showMail').empty();

    var podaci = {action:"showBuyer",id:idPopup};

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data)
            {
                $('#showName').append(data.cName);
                $('#showSurname').append(data.cSurname);
                $('#showStreet').append(data.cAddress);
                $('#showCity').append(data.cCity);
                $('#showPhone').append(data.cPhone);
                $('#showMail').append(data.cMail);
            } else {
                showError("Error occured!");
            }
        }
    }); return false;
}
$(document).ready(function (){

    $('.showBuyerPopup').click(function(){
        $(this).hide();
    });

    $('#secondPhone').click(function(){
        if ($(this).val() == "Molimo unesite broj telefona"){
            $('#secondPhone').val("");
            $('#secondPhone').css("color","black");
        }

    });

    $('input').click(function(){
        if ($(this).val() == "ERROR"){
            $(this).val("");
            $(this).css("color","black");
        }

    });

});

//**********END FUNKCIJE POPUPA ZA PRIKAZ INFORMACIJA O KUPCU ************
////*********************************************************************
//********** TOOGLE za vrstu narudzbe standardna / sms ****************
//*********************************************************************
function selectOrderType(typeNum, obj){
    $('.orderType').removeClass('bChecked');
    //obj.addClass('bChecked');
    var tip = typeNum;
    $('.dynamicPost').empty();
    if (tip == "1") {
        $('.codeSection').show();
        $('#hidden_lp').val("");
        $('#http_rf').val("");
        $('#standardUpsell').show();
        $('#smsUpsell').hide();
        $('.dynamicPost').append($('#postHidden').val());
        $('#postText').show();
        $('#codeStandard').show();
        $('#codeMail').hide();
        var idProizvoda = $('#product_f').find('option:selected').val();
        getProductPriceAndUpsell(idProizvoda);
        orderTypeDef = 1;
    } else if (tip == "2") {
        $('.codeSection').hide();
        $('#hidden_lp').val("");
        $('#http_rf').val("");
        $('#code').val("");
        $('#standardUpsell').hide();
        $('.dynamicPost').append("0.00");
        $('#postText').hide();
        $('#codeStandard').hide();
        $('#codeMail').show();
        var campVal = $('#testCamp').text();
        if ((campVal == "" || campVal == undefined) && campVal.length < 5){
            var idProizvoda = $('#product_f').find('option:selected').val();
            getProductPriceAndUpsell(idProizvoda);
        } else {
            getCampaignInfoByName(campVal);
        }
        $(".korak1").show();
        orderTypeDef = 2;
        var reorderIdentify = campVal.slice(0,5);
        if (reorderIdentify == 'reord'){
            orderTypeDef = 4;
        }
    } else if (tip == "3") {
        $('.codeSection').show();
        $('#hidden_lp').val("");
        $('#http_rf').val("");
        $('#smsUpsell').hide();
        $('#standardUpsell').show();
        $('.dynamicPost').append($('#postHidden').val());
        $('#postText').hide();
        $('#codeStandard').hide();
        $('#codeMail').show();
        var idProizvoda = $('#product_f').find('option:selected').val();
        //getProductPriceAndUpsell(idProizvoda);

        var printPrice          = $('#printPrice').val();
        var printUpsellPrice    = $('#printUpsellPrice').val();

        $('#price').val(printPrice);
        $('#upsellPrice').val(printUpsellPrice);
        $(".upsellPriceText").empty();
        $(".upsellPriceText").append(parseInt(printUpsellPrice));
        $('#mfs').val("0");
        $('#freeShip').val("1");

        $('.singlePrice').empty();
        $('.singlePrice').append(printPrice);

        orderTypeDef = 3;
    } else if (tip == "4") {
        $('.codeSection').show();
        $('#hidden_lp').val("");
        $('#http_rf').val("");
        $('#standardUpsell').show();
        $('#smsUpsell').hide();
        $('.dynamicPost').append($('#postHidden').val());
        $('#postText').show();
        $('#codeStandard').show();
        $('#codeMail').hide();
        var idProizvoda = $('#product_f').find('option:selected').val();
        getProductPriceAndUpsell(idProizvoda);
        orderTypeDef = 1;
    }

    $('.loaderGreen').show();
    setTimeout(function(){
        $('.loaderGreen').hide();
        htmlOfFlow.flow1 = $('#flow1').html();
        htmlOfFlow.flow2 = $('#flow2').html();
        $('#container_2A').slideDown('slow');
        addHttpParams(tip);

    },3000);


    //scrollInterface();

}
//************* dodaj httpref i httpLp za sms narudzbe

function addHttpParams(oTip){

    var imeKampanje = $('#testCamp').text();

    var tipOpPanela = $('#panelType').val();

    if (tipOpPanela == "multipanel") {
        imeKampanje = "vipmail";
    }

    $('#hidden_lp').empty();
    $('#http_rf').empty();

    var selekcija = oTip;
    console.log(imeKampanje);
    if(selekcija == "1"){

    } else if (selekcija == "2" && imeKampanje !== ""){
        $('#hidden_lp').val(httpSiteURL+"?utm_source=sms&utm_campaign="+ imeKampanje);
        $('#http_rf').val(httpSiteURL+"?utm_source=sms&utm_campaign="+ imeKampanje);
    } else if (selekcija == "3"){
        $('#hidden_lp').val(httpSiteURL+"?utm_source=print&utm_campaign=mail");
        $('#http_rf').val(httpSiteURL+"?utm_source=print&utm_campaign=mail");
    } else if (selekcija == "4"){
        $('#hidden_lp').val(httpSiteURL+"?utm_source=sms&utm_campaign=print000");
        $('#http_rf').val(httpSiteURL+"?utm_source=sms&utm_campaign=print000");
    }
}


//*********************************************************************
//********** Pokupi informacije kampanje ******************************
//*********************************************************************

function getCampaignInfo(obj) {
    var param = $(obj).val();
    var podaci = {action:"getCampaignInfo",campName:param};

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data)
            {
                $('#smsUpsell').empty();
                $('#smsUpsell').show();
                $('#smsUpsell').append("<strong>"+data[0].upsellText+"</strong>");

                $('#standardUpsell').hide();
            } else {

            }
        }
    }); return false;

}

//*********************************************************************
//********** AUTO FILL FORME AKO POSTOJI KUPAC ************************
//*********************************************************************

function fillOrderForm(answer){
    if (answer == "No"){
        $('#fillInfo').hide('slow');
        return false;
    }

    var ime = $("#ime").val();
    var prezime = $("#surname").val();

    var dataString = new Array();
    dataString = {action:"fillOrderFormByName", cName:ime, cSurname:prezime, cCountry:countryLocation};

    $.ajax
    ({
        type: "POST",
        url: httpSiteURL+"fillOrderForm",
        data: dataString,
        cache: false,
        success: function(eData){
            if (eData != 0){
                var eData2 = $.parseJSON(eData);

                if (answer == "check"){
                    $('#fillPodaci').empty();
                    $('#fillPodaci').append(eData2.name +' '+ eData2.surname +', '+ eData2.address +', '+ eData2.postcode +' '+ eData2.city +', '+ eData2.email);
                    //$('#fillInfo').show('slow');

                } else if (answer == "Yes") {
                    $('#ime').val(eData2.name);
                    $('#surname').val(eData2.surname);
                    $('#address').val(eData2.address);
                    $('#number').val("-");
                    $('#postal').val(eData2.postcode);
                    $('#city').val(eData2.city);
                    $('#phone').val(eData2.telephone);
                    if (eData2.email !== "No mail"){
                        $('#email').val(eData2.email);
                    }
                    //$('.formGroup').css('border', '1px solid rgb(0, 240, 181)');
                    $('.formGroup').css('background', '#34B84F');
                    $('.formGroup').css('color', '#FFF');
                }
            }
        }
    });
    return false;
}
//*********************************************************************
//********** NEW AUTO FILL FORME AKO POSTOJI KUPAC ************************
//*********************************************************************

function fillOrderFormNew(){
    var nameInForm = $('#ime').val();
    var surnameInForm = $('#surname').val();

    if (nameInForm.length < 2 || nameInForm == "ERROR"){
        $('#ime').val("ERROR");
        $('#ime').css("color","red");

        return false;
    } else if (surnameInForm.length < 2 || surnameInForm == "ERROR"){
        $('#surname').val("ERROR");
        $('#surname').css("color","red");

        return false;
    }
    $('#ime').css("color","black");
    $('#surname').css("color","black");
    $('#korak1').addClass("bChecked");

    var nameLowerForm = nameInForm.trim().toLowerCase();
    var surnameLowerForm = surnameInForm.trim().toLowerCase();

    var nameDb = fillIme.toLowerCase();
    var surnameDb = fillPrezime.toLowerCase();



    var cleanNameForm = nameLowerForm.replace(/(č|ć|ž|đ|š)/g, function(matched){
        var map ={"č":"c","ć":"c","ž":"z","đ":"dj","š":"s"}
        return map[matched];
    });
    var cleanSurnameForm = surnameLowerForm.replace(/(č|ć|ž|đ|š)/g, function(matched){
        var map ={"č":"c","ć":"c","ž":"z","đ":"dj","š":"s"}
        return map[matched];
    });

    var cleanDbName = nameDb.replace(/(č|ć|ž|đ|š)/g, function(matched){
        var map ={"č":"c","ć":"c","ž":"z","đ":"dj","š":"s"}
        return map[matched];
    });
    var cleanDbSurname = surnameDb.replace(/(č|ć|ž|đ|š)/g, function(matched){
        var map ={"č":"c","ć":"c","ž":"z","đ":"dj","š":"s"}
        return map[matched];
    });

    console.log(cleanDbName);
    console.log(cleanDbSurname);

    console.log(cleanNameForm);
    console.log(cleanSurnameForm);

    if (cleanDbName == cleanNameForm && cleanDbSurname == cleanSurnameForm){

        $('#ime').val(fillFormObj[0].name);
        $('#surname').val(fillFormObj[0].surname);
        $('#address').val(fillFormObj[0].address);
        $('#number').val("-");
        $('#postal').val(fillFormObj[0].postoffice);
        $('#city').val(fillFormObj[0].city);
        $('#phone').val(fillFormObj[0].telephone);
        if (fillFormObj[0].email !== "No mail"){
            $('#email').val(fillFormObj[0].email);
        }
        //$('#quest_provjera').hide();
        //$('.formGroup').css('border', '1px solid rgb(0, 240, 181)');
        $('.formGroup').css('background', '#34B84F');
        $('.formGroup').css('color', '#FFF');
        $('.fillInfoBox').hide('slow');
        $('#noOMGbutton').hide();
        $('.korak2,.korak3,.korak4,.korak5').hide();
        $('.korak6').show();
        return false;
    } else {
        $('.korak2').show();
        return false;
    }



    if (answer == "No"){
        $('.fillInfoBox').hide('slow');
        return false;
    } else if (answer == "Yes") {
        $('#ime').val(fillFormObj[objNum].name);
        $('#surname').val(fillFormObj[objNum].surname);
        $('#address').val(fillFormObj[objNum].address);
        $('#number').val("-");
        $('#postal').val(fillFormObj[objNum].postoffice);
        $('#city').val(fillFormObj[objNum].city);
        $('#phone').val(fillFormObj[objNum].telephone);
        if (fillFormObj[objNum].email !== "No mail"){
            $('#email').val(fillFormObj[objNum].email);
        }
        //$('#quest_provjera').hide();
        //$('.formGroup').css('border', '1px solid rgb(0, 240, 181)');
        $('.formGroup').css('background', '#34B84F');
        $('.formGroup').css('color', '#FFF');
        $('.fillInfoBox').hide('slow');
        $('#noOMGbutton').hide();
        $('.korak2,.korak3,.korak4,.korak5').hide();
        $('.korak6').show();
        return false;
    }
    $('#fillInfo').empty();
    $('.loaderGreen').show();
    var ime = $("#ime").val();
    var prezime = $("#surname").val();

    var dataString = new Array();
    dataString = {action:"fillOrderFormByNameNew", cName:ime, cSurname:prezime, cCountry:countryLocation};

    $.ajax
    ({
        type: "POST",
        url: httpSiteURL+"fillOrderForm",
        data: dataString,
        cache: false,
        success: function(eData){

            if (eData != 0){
                var eData2 = $.parseJSON(eData);


                //$('#fillInfo').show();
                fillFormObj = eData2;
                for (var key in eData2) {
                    $('#fillInfo').append('<tr><td>'+eData2[key].name +'</td><td>'+ eData2[key].surname +'</td><td>'+ eData2[key].address +'</td><td>'+ eData2[key].postcode +'</td><td>'+ eData2[key].city +'</td><td>'+ eData2[key].email+'</td><td><button type="button" class="bigOrder GreyBtn" style="width:60px;height:28px;font-size: 12px;margin-left:5px;margin-top:0px;" onclick="fillOrderFormNew(\'Yes\', '+key+');" >OK</button></td></tr>');
                }

            }
        }
    }).done(function() {
        $('.loaderGreen').hide();
    });
    return false;
}
//*********************************************************************
//********** PROVJERA KODA KUPCA **************************************
//*********************************************************************
function getLandingPage()
{
    $("#code,#code2").addClass("assigned");
    var code=$("#code").val();
    var stateCurrent  =$("#stateHidden").val();
    $.ajax({
        url: httpSiteURL+'RequestHandler',
        type:"POST",
        dataType:"JSON",
        data:{action:"getLPdata",phonecode:code,state:stateCurrent},
        success:function(data)
        {
            /* append data on insert code click */
            try{
                var httpData=JSON.parse(data['httpData']);

                if(httpData.REMOTE_ADDR)
                {
                    var ip=httpData.REMOTE_ADDR;
                    $("#ip_address").val(ip);
                }
                if(httpData.HTTP_USER_AGENT)
                {
                    var ua=httpData.HTTP_USER_AGENT;
                    $("#http_ua").val(ua);
                }
                if(httpData.HTTP_REFERER)
                {
                    var rf=httpData.HTTP_REFERER;
                    $("#http_rf").val(rf);
                }
            }
            catch(err) {
                console.debug(err);
            }

            var landing_page=data['page'];
            var phonecode_id=data['id'];

            if (landing_page == "" || landing_page == undefined ){
                $("#hidden_lp").val(httpSiteURL+"index.php?utm_source=phone&utm_campaign=phone_wrongcode");
                $("#http_rf").val(httpSiteURL+"index.php?utm_source=phone&utm_campaign=phone_wrongcode");
            } else {
                //append values to hidden fields
                $("#hidden_lp").val(landing_page);
                $("#phone_code_hidden").val(phonecode_id);
            }

        }
    });
    return false;
}
function noCode(){
    $("#code").addClass("assigned");
    $("#hidden_lp").val(httpSiteURL+"index.php?utm_source=phone&utm_campaign=phone_nocode");
    $("#http_rf").val(httpSiteURL+"index.php?utm_source=phone&utm_campaign=phone_nocode");
}


//*********************************************************************
//**********LISTA SALESPACKAGEA ( STATE+PRODUCT ) *********************
//*********************************************************************

function getSalesPackagesList(){
    var selektOpt = $('#country').find('option:selected').val();
    var selektOpt2 = $('#Product').find('option:selected').val();
    var selektovan = {action:"getSalesPackages",state:selektOpt,product:selektOpt2};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:selektovan,
        async: true,
        success:function(data){
            if(data.length > 0)
            {
                $('#salesPack').empty();
                $('#salesPack').append('<option value="all">Choose Salespackage</option>');
                for(var i=0;i<data.length;i++)
                {
                    $('#salesPack').append('<option value="'+data[i].id+'">'+data[i].salespackagecode+'</option>');
                }
            } else {
                $('#salesPack').empty();
                $('#salesPack').append('<option value="all">Choose Salespackage</option>');
            }
        }
    });
    return false;
}


//*********************************************************************
//**********Uzmi senderID na osnovu state *****************************
//*********************************************************************

function getSenderId(){
    var selektOpt = $('#country').find('option:selected').val();

    var selektovan = {action:"getSenderId",state:selektOpt};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:selektovan,
        async: true,
        success:function(data){
            $('#senderId').val(data.distro_smsFrom);
            $('#centerPhone').val(data.phone);
            $('#centerMail').val(data.mail);
            $('#centerCurrency').val(data.currency_sbl);
            //$('.currencyBox').empty();
            //$('.currencyBox').append(data.currency_sbl); // dodavanje valute na cijenu ako bude trebalo
        }
    });
    return false;
}
//*********************************************************************
//**********SNIMANJE SPECIAL OFFER ITEM-a *****************************
//*********************************************************************


function addSpecialOffer(){
    var statecode = $('#country option:selected').val();
    var orderP = $('#ProductOrd option:selected').text();
    var offerP = $('#Product option:selected').text();
    var salesP = $('#salesPack option:selected').text();
//var state = $('#country').text();

    if (orderP == "Choose product" || offerP == "Choose product" || salesP == "Choose product" || salesP == "Choose Salespackage"){
        showWarning("You must fill out the form!");
        return false;
    }

    var podaciForme ={};
    podaciForme['action'] = 'addSpecialOffer';

    $("form [name]").each(function (){
        var kljuc = $(this).attr("name");
        var vrijednost = $(this).val();
        podaciForme[kljuc] = vrijednost;
    });
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaciForme,
        async: true,
        success:function(data){
            if(data > 0)
            {
                $('#tabela').append('' +
                    '<div class="tableLine" style="margin-top:1px;">'+
                    '<div class="listBox srednji">-</div>'+
                    '<div class="listBox">'+statecode+'</div>'+
                    '<div class="listBox sirokiBox">'+orderP+'</div>'+
                    '<div class="listBox sirokiBox">'+offerP+'</div>'+
                    '<div class="listBox sirokiBox">'+salesP+'</div>'+
                    '<div class="listBox"><button type="button" data-id="'+data+'" class="delButton" style="width:100px;font-size: 12px;" onclick="deleteRow(this);">Delete</button></div>'+
                    '</div>');
                showSuccess("Special Offer Added to database!");

            }
        }
    });
    return false;
}

//*********************************************************************
//********** FUNKCIJA ZA UZIMANJE PODATAKA SPECIJALNE PONUDE **********
//*********************************************************************

function getOfferText(){
    var selektOpt = $('#specialProd').find('option:selected').val();
    var selektovan = {action:"getOfferText",offerId:selektOpt};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:selektovan,
        async: true,
        success:function(data){
            $('#offerText').empty();

            var offerT = data.offerText;
            var imeKupca = $('#ime').val();
            var cijenaPon = $('#specialProd').find('option:selected').data("sp");
            var cijenaFix = cijenaPon.split(" ");

            offerT = offerT.replace(/\[\[name\]\]/g, imeKupca);
            offerT = offerT.replace(/\[\[price\]\]/g, cijenaFix[1]);

            $('#offerText').append(offerT);
            $('#offerTextHolder').fadeIn('fast');
        }
    });
    return false;
}
//*********************************************************************
//********** LISTA PROIZVODA KOJI SU NA SPEC. PONUDI ******************
//*********************************************************************

function getSpecialProducts(){
    var selektOpt = $('#stateHidden').val();
    var selektOpt2 = $('#product_f').find('option:selected').val();
    var selektovan = {action:"getSpecialList",state:selektOpt,product:selektOpt2};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:selektovan,
        async: true,
        success:function(data){
            if(data.length > 0)
            {
                $('#specialProd').empty();
                if(data.length > 1){
                    $('#specialProd').append('<option value="" data-sp="">'+choosePr+'</option>');
                }
                for(var i=0;i<data.length;i++)
                {
                    var prodCurrency = data[i].currency;
                    var prodPrice = data[i].price;
                    prodPrice = prodPrice.replace(".",",");
                    var prodQuant = data[i].quantity;
                    var paket = "STAR "+prodPrice+prodCurrency+" | "+ prodQuant +"x | "+prodPrice+prodCurrency;



                    $('#specialProd').append('<option value="'+data[i].idNum+'" data-sp="'+paket+'" data-pr="'+data[i].prodId+'">'+data[i].title+'</option>');
                }
            } else {
                $('#specialProd').empty();
            }

        }
    });
    return false;
}
//*********************************************************************
//********** KUPAC NE ZELI SPECIJALNU PONUDU **************************
//*********************************************************************
function noSpecialOffer() {
    $('.specialPopup').hide('fast');
    $('#confirm').show('fast');
    $('html, body').animate({
        scrollTop: $("#confirm").offset().top
    }, 1000);
}

//*********************************************************************
//********** FUNKCIJA ZA FILTERE - STAVLJA PARAMETRE U GET ************
//*********************************************************************
var SearchFormSimple = {};

SearchFormSimple.search = function(obj) {

    var button  = $(obj);
    var forma   = button.parents('form');
    var objekti = forma.find("input,select");

    var podaci = '';
    objekti.each(function(){

        var ime = $(this).attr("name");
        var val = $(this).val();
        podaci += ime + "=" + val + "&";

    });

    location.search = encodeURI(podaci);
    return false;

}
//*********************************************************************
//********** BRISANJE SPECIJALNE PONUDE *******************************
//*********************************************************************

function deleteRow(table,obj,rowNum) {
    var r = confirm("Are you shure you want to delete the record?");
    if (r == true) {

        var idNum = $(obj).data('id');
        var podaci = {action:"deleteRow",id:idNum,table:table};
        var x = obj.rowIndex;

        $.ajax({
            url: httpSiteURL+"MainAjax",
            type:"POST",
            dataType:"JSON",
            data:podaci,
            async: true,
            success:function(data){
                if(data == 1)
                {
                    deleteTableRow(rowNum);
                    showSuccess("Record removed from database!");
                } else {
                    showError("Error occured!");
                }
            }
        });

    } else {}

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
//*********************************************************************
//********** DELETE ROW FROM TABLE ************************************
//*********************************************************************
function deleteTableRow(rowid)
{
    var row = document.getElementById(rowid);
    if (row != null && row != undefined && row != ""){
        row.parentNode.removeChild(row);
    }
}
//*********************************************************************
//********** select option by GET *************************************
//*********************************************************************
function getToOption(elementId,getName){
    //uhvati parametre iz GET-a
    var parseQueryString = function() {

        var str = window.location.search;
        var objURL = {};

        str.replace(
            new RegExp( "([^?=&]+)(=([^&]*))?", "g" ),
            function( $0, $1, $2, $3 ){
                objURL[ $1 ] = $3;
            }
        );
        return objURL;
    };
//sredi parametre
    var params = parseQueryString();
    var getFromGet = params[getName];
//Funkcija za odabir opcije
    function setOption(selectElement, value) {
        var options = selectElement.options;
        for (var i = 0, optionsLength = options.length; i < optionsLength; i++) {
            if (options[i].value == value) {
                selectElement.selectedIndex = i;
                return true;
            }
        }
        return false;
    }
    //Zavrsi odabir opcije
    setOption(document.getElementById(elementId), getFromGet);
}
//*********************************************************************
//********** POVECAJ POLJA SA DUGIM TEKSTOM NA DataView ***************
//*********************************************************************

$(document).on('mouseover','.makeBigger',function () { $(this).addClass('bigger')})
    .on('mouseleave','.bigger',function () { $(this).removeClass('bigger')});

//*********************************************************************
//**********Selekcija polja tabele - GENERALIZOVANO *******************
//*********************************************************************

function tdOption(obj){
    if ($(obj).find(".fSpan").hasClass('fHidden') == false){
        var typeObj = $(obj).find(".fSel").attr('type');
        var obValue = $(obj).find(".fSpan").text();

        console.log(typeObj + " " + obValue);
        if (typeObj == "text"){
            $(obj).find(".fSel").val(obValue);
        }
        $(obj).find(".fSpan").hide();
        $(obj).find(".fSpan").addClass('fHidden');
        $(obj).find(".fSel").show();
        $(obj).find(".fSel").focus();
    }
}
function changeFieldValue(id,field,value){
    var podaci = {};
    podaci["action"] = "changeFieldValue";
    podaci["table"] = table;
    podaci["id"] = id;
    podaci["field"] = field;
    podaci["value"] = value;

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data > 0)
            {
                //location.reload();
            }
        }
    });
    return false;
}


function insertFieldValue(state,product,field,value){
    var podaci = {};
    podaci["action"] = "insertFieldValue";
    podaci["table"] = table;
    podaci["state"] = state;
    podaci["product"] = product;
    podaci["field"] = field;
    podaci["value"] = value;

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data > 0)
            {
                //location.reload();
            }
        }
    });
    return false;
}


$(document).ready(function(){
    $(".fSel").blur(function(){
        $(this).hide();

        if ($(this).data('option') === 'select-box'){
            var novaVrijednost = $(this).find('option:selected').text();
        }else {
            var novaVrijednost = $(this).val();
        }

        $('.fHidden').empty();
        
        if ($(this).data('field') === 'password'){
            $('.fHidden').append(md5(novaVrijednost));
        } else {
            $('.fHidden').append(novaVrijednost);
        }
        //$('.fHidden').append(novaVrijednost);
        $('.fHidden').show();
        $('.fHidden').removeClass('fHidden');
    });
    $(".fSel").change(function(){
        //var productPrices='';
        var vrijednost = $(this).val();
        var id = $(this).data('id');
        var field = $(this).data('field');

        var productPrices = $(this).data('insert-prices');

        if (id == 0 && productPrices === 'productPrices'){
            // console.log(productPrices + " " +id);
            var state = $(this).data('state');
            var product = $(this).data('productid');

            if (vrijednost !== "") {
                showSuccess("Value changed!");
                insertFieldValue(state,product,field,vrijednost);
                //                $(this).hide();
                //                $('.fHidden').empty();
                //                $('.fHidden').append(vrijednost);
                //                $('.fHidden').show();
                //                $('.fHidden').removeClass('stHidden');
            } else {
                //                $(this).hide();
                //                $('.fHidden').show();
                //                $('.fHidden').removeClass('fHidden');
            }
        } else {

            if (vrijednost !== "") {
                showSuccess("Value changed!");
                changeFieldValue(id,field,vrijednost);
                //                $(this).hide();
                //                $('.fHidden').empty();
                //                $('.fHidden').append(vrijednost);
                //                $('.fHidden').show();
                //                $('.fHidden').removeClass('stHidden');
            } else {
                //                $(this).hide();
                //                $('.fHidden').show();
                //                $('.fHidden').removeClass('fHidden');
            }

        }


    });
    $('.fSel').keyup(function(event){
        if(event.keyCode == 13){
            $('.fSel').trigger('change');
            $(this).trigger('blur');
        }
    });

    $('#specialProd').change(function(){
        getOfferText();
    });

    $('#initialPhone,#secondPhone,#cancelOrderPhone').on('change keyup', function() {
        var sadrzaj = $(this).val();
        // Remove invalid characters
        var sanitized = sadrzaj.replace(/[^0-9]/g, '');
        $(this).val(sanitized);


    });
});
//*********************************************************************
//********** LISTA KAMPANJA *******************************************
//*********************************************************************

function getCampaigns(){
    var selektOpt = $('#country').val();
    var selektovan = {action:"getCampaigns",state:selektOpt};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:selektovan,
        async: true,
        success:function(data){
            if(data.length > 0)
            {
                $('#campName').empty();
                $('#campName').append('<option value="">Choose Campaign</option>');
                for(var i=0;i<data.length;i++)
                {
                    $('#campName').append('<option value="'+data[i].id+'" data-ime="'+data[i].CampaignName+'">'+data[i].CampaignName+'</option>');
                }
            } else {
                $('#campName').empty();
                $('#campName').append('<option value="">Choose Campaign</option>');
            }
        }
    });
    return false;
}
//*********************************************************************
//**********DAJE KLASU TRENUTNO SELEKTOVANOM SLAEPAKETU ***************
//*********************************************************************
function markSalespackage(obj)
{
    $(".cijeneButton").each(function(){
        $(this).removeClass("aktivniSp");
        $(this).css('color', '#2D3D57');
        $(this).css('background', '#fff');
    });
    var ovaj=$(obj);
    ovaj.addClass("aktivniSp");
    ovaj.css('background', '#34B84F');
    ovaj.css('color', '#FFF');

    var aktivniInfo = $(".aktivniSp").data("value");
    var aktivniInfo2 = aktivniInfo.split(" ");
    var samoCijena = aktivniInfo2[0].replace(",",".");

    //Sama cijena bez valute float type
    var numb = parseFloat(samoCijena);

    var ukCijena = 0;
    var assignPost = $('.dynamicPost').text();
    ukCijena = numb + parseFloat(assignPost);

    $('.dynamicPrice').empty();
    $('.dynamicPrice').append(ukCijena);

}
//*********************************************************************
//********** OZNACAVA PHONECODE DA JE ISKORISTEN **********************
//*********************************************************************


function setPhonecodeused()
{
    var id=$("#phone_code_hidden").val();
    if(id.length>0)
    {
        $.ajax({
            url: httpSiteURL+'RequestHandler',
            type:"POST",
            dataType:"JSON",
            data:{action:"setCodeUsed",id:id},
            success:function(data)
            {

                //if wrong some log

            }
        });
    }
}
//*********************************************************************
//********** Provjerava polje za kod i dodjeljuje utmove **************
//*********************************************************************


function checkCodeField(codePos)
{

    if (codePos == 1){
        var discCode = $("#code").val();
        checkQuestionBox("quest3");
    } else {
        var discCode = $("#code2").val();
        checkQuestionBox("quest3a");
    }

    if(discCode == "" || discCode.length < 4 || discCode == "ERROR"){
        noCode();

        if (codePos == 1){
            $('#code').val("ERROR");
            $('#code').css("color","red");
        } else {
            $('#code2').val("ERROR");
            $('#code2').css("color","red");
        }


    } else {
        getLandingPage();
        if (codePos == 1){
            $('#yesCode1').addClass("bChecked");
        } else {
            $('#yesCode2').addClass("bChecked");
        }
        $(".korak1").show();
        $('#nocod1,#nocod2').attr('disabled','disabled');

    }


}

//*********************************************************************
//********** Funkcija za brojanje karaktera u elementu ****************
//*********************************************************************
function countChars(obj,pos) {
    $("#charCount").empty();
    $(".charCount").empty();

    $("#charCount").append(obj.value.length);
    $(".charCount").append(obj.value.length);
    var encodeT = obj.value;
    var encode = {action:"checkEncode",encText:encodeT};

    $.ajax({
        url:httpSiteURL+"CampaignsAjax",
        type:"POST",

        data:encode,
        success:function(data)
        {
            if (data > 0){
                $("#charSet").empty();
                $("#charSet").append("UTF-8");
                $(".charSet").empty();
                $(".charSet").append("UTF-8");
            } else {
                $("#charSet").empty();
                $(".charSet").empty();
                //$("#charSet").append("GSM");
            }
        }
    });
    return false;
}

function countCharsCamp(obj,pos) {

    if (pos == 1){
        $("#charCount").empty();
        $("#charCount").append(obj.val().length);
    } else {
        $("#charCount2").empty();
        $("#charCount2").append(obj.val().length);
    }

    var encodeT = obj.val();
    var encode = {action:"checkEncode",encText:encodeT};

    $.ajax({
        url:httpSiteURL+"CampaignsAjax",
        type:"POST",

        data:encode,
        success:function(data)
        {
            if (data > 0){
                if (pos == 1){
                    $("#charSet").empty();
                    $("#charSet").append("UTF-8");
                } else {
                    $("#charSet2").empty();
                    $("#charSet2").append("UTF-8");
                }
            } else {
                if (pos == 1){
                    $("#charSet").empty();
                } else {
                    $("#charSet2").empty();
                }
            }
        }
    });
    return false;
}
//*********************************************************************
//********** Funkcija za odjavu maila i broja korisnika ***************
//*********************************************************************
function unsubNumberold(unNumber, state) {

    var unData = {action:"unsubNumber",number:unNumber, state:state};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        data:unData,
        success:function(data)
        {
            if (data > 0){
                $(".odjavaNotify").fadeIn('slow');
                //$(".odjavaNotify").css("background-color","#cfc");
                $("#odjavaNotify").append(isDeleted);
                // $("#odjavaNotify").append(yNum+" "+unNumber+" "+isDeleted);

            } else {
                showError("Error");
            }
        }
    });
    return false;
}
function unsubMaillold(unMail, state) {

    var unData = {action:"unsubMail",mail:unMail, state:state};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        data:unData,
        success:function(data)
        {
            if (data > 0){
                $(".odjavaNotify").fadeIn('slow');
                //$(".odjavaNotify").css("background-color","#cfc");
                //$("#odjavaNotify").append(unMail+" removed.");
                $("#odjavaNotify").append(isDeleted);
                //$("#odjavaNotify").append(yMail+" "+unMail+" "+isDeleted);
            } else {
                showError("Error");
            }
        }
    });
    return false;
}

function unsubNumber(unNumber, state) {

    var unData = {action:"unsubscribePhoneOrder", APIKey:"2D5419EB5F4D79A0FB89737C153F129C" ,phone:unNumber, state:state};
    $.ajax({
        url: httpSiteURL+"api/unsubscribe.php",
        type:"POST",
        data:unData,
        success:function(data)
        {
            if (data !== 0){
                $(".odjavaNotify").fadeIn('slow');
                //$(".odjavaNotify").css("background-color","#cfc");
                $("#odjavaNotify").append(isDeleted);
                // $("#odjavaNotify").append(unNumber+" removed. ");
            } else {
                showError("Error");
            }
        }
    });
    return false;
}

function unsubMail(unMail, state) {



    var unData = {action:"unsubscribePhoneOrder", APIKey:"2D5419EB5F4D79A0FB89737C153F129C" ,email:unMail, state:state};
    $.ajax({
        url: httpSiteURL+"api/unsubscribe.php",
        type:"POST",
        data:unData,
        success:function(data)
        {
            if (data !== 0){
                $(".odjavaNotify").fadeIn('slow');
                //$(".odjavaNotify").css("background-color","#cfc");
                //$("#odjavaNotify").append(unMail+" removed.");
                $("#odjavaNotify").append(isDeleted);
            } else {
                showError("Error");
            }
        }
    });
    return false;
}

function unsubscribeUser() {
    $("#odjavaNotify").empty();
    var unNumber = $("#odjavaPhone").val();
    var unMail = $("#odjavaMail").val();
    var state = $('#stateHidden').val();

    if ((unNumber == "" && unMail == "") || unNumber == "ERROR" || unMail=="ERROR" ) {
        $('#odjavaPhone').val("ERROR");
        $('#odjavaPhone').css("color","red");

        $('#odjavaMail').val("ERROR");
        $('#odjavaMail').css("color","red");

        //showWarning(unMsg);
        return false;
    }

    if (unNumber.length > 0 && unNumber.length < 6) {
        $(".odjavaNotify").fadeIn('slow');
        //$(".odjavaNotify").css("background-color","#fcc");
        $("#odjavaNotify").append(supNum+" "+unNumber+" "+noValidNum);
        //$("#odjavaNotify").append(unNumber+" not valid.");
        return false;
    }

    //if (unNumber !== "") {
    //    unsubNumber(unNumber, state);
    //}
    //if (unMail !== "") {
    //    unsubMail(unMail, state);
    //}

    if (unNumber !== "" && unMail == "") {
        unsubNumber(unNumber, state);
    } else if (unMail !== "" && unNumber == "") {
        unsubMail(unMail, state);
    } else if (unMail !== "" && unNumber !== "") {
        unsubMail(unMail, state);
    }




}

//*********************************************************************
//********** Filtriranje ako korisnik zeli vise proizvoda *************
//*********************************************************************

function ChangeQuant(answer) {
    var selKampanja = $('#campaigns option:selected').val();
    var selTipOrdera = $('#orderType option:selected').val();
    $(".upsellText").hide();
    $(".noUpsellText").show();
    if (answer == "yes") {
        multiProduct = 1;
        $('#n_set').removeClass('bChecked');
        $('#quest8a2').slideDown();
    } else {
        multiProduct = 0;
        $('#z_set').removeClass('bChecked');
        $('#quest8a2').slideUp();
    }

    $('#product_f').trigger('change'); // potreban trigger da se povuku i filtriraju ispravni paketi
}
//*********************************************************************
//********** Show CSV in whiteBox *************************************
//*********************************************************************

function readTextFile(file)
{
    $('#csvViewBox').empty();
    $('.csvViewBox').show();
    var rawFile = new XMLHttpRequest();
    rawFile.open("GET", file, true);
    rawFile.onreadystatechange = function ()
    {
        if(rawFile.readyState === 4)
        {
            if(rawFile.status === 200 || rawFile.status == 0)
            {
                var allText = rawFile.responseText;
                $('#csvViewBox').append(allText);
            }
        }
    }
    rawFile.send(null);
}
function getInstro(){

    alert("Instructions module still in development!");
}
//*********************************************************************
//********** Brojac iz koliko dijelova se salje sms kampanja  *********
//*********************************************************************
function countSmsParts() {
    var perHours = $('#perHour').val();
    var smsNumber = $('#recNum').val();
    var smsparts = 0;

    if (smsNumber !== ""){

        smsparts  = smsNumber / perHours;

        $('.smsParts').empty();

        $('.smsParts').append(Math.ceil(smsparts));
    }
}

//*********************************************************************
//********** Preselektor proizvoda na osnovu kampanje *****************
//*********************************************************************

function selectProductByCamp(){
    // Selekcija proizvoda
    var productId = $('#campaigns option:selected').data('prid');
    $('#product_f').chosen();

    $('#product_f').val(productId);
    $('#product_f').trigger("chosen:updated");
    $('#product_f').trigger("change");


    // Upsell text
    var selKampanja = $('#campaigns option:selected').val();
    var selTipOrdera = orderTypeDef;

    if (typeof json_upsellText != "undefined" && json_upsellText[selKampanja] != "" && typeof(json_upsellText[selKampanja]) != "undefined" && selTipOrdera == 2){
        $(".upsellText").show();
        $(".upsellText").empty();
        $(".upsellText").append(json_upsellText[selKampanja]);
        $(".noUpsellText").hide();
    }
}

//*********************************************************************
//********** Provjera podataka od inicijalnog broja telefona **********
//*********************************************************************

function checkFromPhone(oId){
    var initialPhone = String($('#'+oId+'').val());
    var stateCurrent = $("#stateHidden").val();

    $('#testPodaci').empty();
    $('#testCamp').empty();
    $('#testProiz').empty();

    var ajaxData = {action:"checkFromPhone" ,phone:initialPhone, state:stateCurrent};
    if(oId == "secondPhone" && (initialPhone == "" || initialPhone.length < 7 || $('#secondPhone').val() == "ERROR")) {

        $('#secondPhone').val("ERROR");
        $('#secondPhone').css("color","red");

        return false;
    } else if ( oId == "secondPhone" && initialPhone.length > 7){
        $('#quest1').show();
        $('#phone').val(initialPhone);
    }


    if (initialPhone == "" || initialPhone.length < 7 || initialPhone == "ERROR"){
        $('#testPodaci').append("Not proper number format");
        $('#initialPhone').val("ERROR");
        $('#initialPhone').css("color","red");
        return false;
    }

    $('.initNumYes').addClass("bChecked");
    $('.secondNumYes').addClass("bChecked");
    $('#secondPhone').css("color","black");
    $('.introBox').show();


    $.ajax({
        url:httpSiteURL+"MainAjax",
        type:"POST",
        data:ajaxData,

        success:function(data)
        {
            console.log(data);
            var jsonObj = JSON.parse(data);
            console.log(jsonObj);

            if($.isEmptyObject(jsonObj))
            {
                $('#testPodaci').append("no data");
                $('.secondPhoneBox').show();

            } else {
                $('.fillInfoBox').addClass('filled');
                if (oId == 'initialPhone') {
                    $('.secondPhoneBox').hide();
                    $('#quest2a').hide();
                }

                if (jsonObj[0] != undefined && jsonObj[0] != null && jsonObj[0] != ""){
                    $('#testPodaci').append(jsonObj[0].name);


                    fillIme = jsonObj[0].name;
                    fillPrezime = jsonObj[0].surname;
                }
                $('#testCamp').append(jsonObj.kampanja);
                $('#testProiz').append(jsonObj.proizvod);

                if(jsonObj.hasOutbound == 1){
                    $('#notifyBox').show();

                    $('#outboundID').val(jsonObj.outboundID);
                    $('#outboundType').val(jsonObj.type);

                } else {
                    $('#notifyBox').hide();
                }

                fillFormObj = jsonObj;

                //for (var key in jsonObj) {
                //    $('#fillInfo').append('<tr><td>'+jsonObj[key].name +'</td><td>'+ jsonObj[key].surname +'</td><td>'+ jsonObj[key].address +'</td><td>'+ jsonObj[key].postoffice +'</td><td>'+ jsonObj[key].city +'</td><td>'+ jsonObj[key].email+'</td><td><button type="button" class="bigOrder GreyBtn" style="width:60px;height:28px;font-size: 12px;margin-left:5px;margin-top:0px;" onclick="fillOrderFormNew(\'Yes\', '+key+');" >OK</button></td></tr>');
                //}
                //$('#noOMGbutton').append('<button type="button" class="bluebutton orderType" id="sms_btn" style="width: 350px;display:none;" onclick="$(\'.korak2\').show();$(\'#noOMGbutton\').hide();$(\'.fillInfoBox\').hide()">ODGOVARAJUĆE IME NIJE PRONAĐENO</button>');

                //$('#fillInfo').show();

                //$('.noomg').hide();
                if (oId == 'secondPhone'){
                    getCampaignInfoByName(jsonObj.kampanja)
                }

            }
        }
    });
    return false;
}

//*********************************************************************
//********** promjena vrijednosti polja inputa  **********
//*********************************************************************
function routerToOutbound() {

    var fileNum = "";
    var outType = $('#outboundType').val();

    if (outType == 1 || outType == 5 || outType == 6) {
        fileNum = "";
    } else if (outType == 2) {
        fileNum = "3";
    } else if (outType == 3) {
        fileNum = "2";
    } else if (outType == 7) {
        fileNum = "4";
    } else if (outType == 8) {
        fileNum = "5";
    } else if (outType == 9) {
        fileNum = "6";
    } else if (outType == 10) {
        fileNum = "7";
    } else if (outType == 11) {
        fileNum = "8";
    }

    var outIdforStatus = $('#outboundID').val();

    changeFlagFromInbound(outIdforStatus,11);
    //  window.location.replace( 'outbound/'+ $('#stateHidden').val() +'/call' + $('#outboundType').val() + '.php?userId=' + $('#outboundID').val(), 'New tab', '' );
    window.open('web/outbound/call' + fileNum + '/'+ $('#stateHidden').val() +'?userId=' + $('#outboundID').val(), 'New tab', '') ;
    location.reload();
}




//*********************************************************************
//********** promjena vrijednosti polja inputa  **********
//*********************************************************************
function changeInputFieldValue(obj, inputID) {
    $('#'+inputID+'').val(obj.value);
}

//*********************************************************************
//********** Pokupi informacije kampanje **********
//*********************************************************************
function getCampaignInfoByName(smsKampanja){
    var ajaxData = {action:"getCampaignInfoByName" ,campaign:smsKampanja, state:countryLocation};

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        data:ajaxData,
        success:function(data)
        {
            if (data) {
                var jsonObj = JSON.parse(data);
            } else {
                var jsonObj = {};
            }

            var idProizvoda = $('#product_f').find('option:selected').val();
            if($.isEmptyObject(jsonObj) || jsonObj.pId !== idProizvoda)
            {

                getProductPriceAndUpsell(idProizvoda);

            } else {
                var priceForm = parseFloat(jsonObj.price).toFixed(2)+" | 1x | "+parseFloat(jsonObj.price).toFixed(2);
                $('#price').val(priceForm);
                $('#upsellPrice').val(jsonObj.upsellPrice);
                $('#mfs').val(jsonObj.mfs);
                //$('#product_f').chosen();
                $('#freeShip').val(jsonObj.freeShipping);

                $('#product_f').val(jsonObj.pId);
                //$('#product_f').trigger("chosen:updated");
                //$('#product_f').trigger("change");

                $('.singlePrice').empty();
                $('.singlePrice').append(jsonObj.price);

                var upsellCijena        = $('#upsellPrice').val();
                $(".upsellPriceText").empty();
                $(".upsellPriceText").append(upsellCijena);
            }
        }
    });
    return false;
}

//*********************************************************************
//**********Dodaj dodatni proizvod na narudzbu **********
//*********************************************************************
function addAdditionalProduct(num) {
    if (num == 0){
        $('#dodatni1,#dodatni2,#dodatni3').removeClass("bChecked");
        $('#additionalNum').hide();
        $('#dodajN').hide();
    } else if (num == 1){
        $('#dodatni0,#dodatni2,#dodatni3').removeClass("bChecked");
        $('#additionalNum').hide();
        $('#dodajN').hide();
    } else if (num == 2){
        $('#dodatni0,#dodatni1,#dodatni3').removeClass("bChecked");
        $('#additionalNum').hide();
        $('#dodajN').hide();
    } else if (num == "n"){
        $('#dodatni0,#dodatni1,#dodatni2').removeClass("bChecked");
        $('#additionalNum').show();
        $('#dodajN').show();
    }


    if (num != "n") {
        
        if ($('#product_f').find('option:selected').val() !== ""){
            var nazivPr                = $('#product_f').find('option:selected').text();
        } else if($('#cancelOrderProduct').find('option:selected').val() !== ''){
            var nazivPr                = $('#cancelOrderProduct').find('option:selected').text();
        }
        //var nazivPr                = $('#product_f').find('option:selected').text();
        //var fulskuExp              = fullsku.split("~");
        //var nazivPr = $.trim(fulskuExp[1]);
        //var fulskuExp              = fullsku.split("~");
        //var nazivPr = $.trim(fulskuExp[1]);

        var basePrice   = $('#price').val();
        var addingPrice = $('#upsellPrice').val();

        if (num == "d") {
            num = $('#additionalNum').val();
        }
        checkForFreePost(num);
        var newPrice    = (parseFloat(addingPrice)*num) + parseFloat(basePrice);

        konacnaKolicina = parseInt(num)+1;

        var priceForm   = newPrice.toFixed(2)+" | "+(parseInt(num)+1)+"x | "+newPrice.toFixed(2);

        $('.P_proizvod').empty();
        $('.P_proizvod').append(nazivPr+" "+priceForm);
    }

    $('.korak8').show();

}

function addAdditionalProductZeljka(num) {
    if (num == 0){
        $('#dodatni1,#dodatni2,#dodatni3').removeClass("bChecked");
        $('#additionalNum').hide();
        $('#dodajN').hide();
    } else if (num == 1){
        $('#dodatni0,#dodatni2,#dodatni3').removeClass("bChecked");
        $('#additionalNum').hide();
        $('#dodajN').hide();
    } else if (num == 2){
        $('#dodatni0,#dodatni1,#dodatni3').removeClass("bChecked");
        $('#additionalNum').hide();
        $('#dodajN').hide();
    } else if (num == "n"){
        $('#dodatni0,#dodatni1,#dodatni2').removeClass("bChecked");
        $('#additionalNum').show();
        $('#dodajN').show();
    }


    if (num != "n") {
        var nazivPr                = $('#product_f').find('option:selected').text();
        //var fulskuExp              = fullsku.split("~");
        //var nazivPr = $.trim(fulskuExp[1]);

        var basePrice   = $('#price').val();
        var addingPrice = $('#upsellPrice').val();

        if (num == "d") {
            num = $('#additionalNum').val();
        }
        hasPost = "0";
        var newPrice    = (parseFloat(addingPrice)*num) + parseFloat(basePrice);

        konacnaKolicina = parseInt(num)+1;

        var priceForm   = newPrice.toFixed(2)+" | "+(parseInt(num)+1)+"x | "+newPrice.toFixed(2);

        $('.P_proizvod').empty();
        $('.P_proizvod').append(nazivPr+" "+priceForm);
    }

    $('.korak8').show();

}
//*********************************************************************
//********** Uzmi cijenu i upsell na osnovu Product Id-a **********
//*********************************************************************
function getProductPriceAndUpsell(pId){
    var ajaxData = {action:"getProductPriceAndUpsell", product:pId, state:countryLocation};

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        data:ajaxData,
        success:function(data)
        {
            var jsonObj = JSON.parse(data);

            if($.isEmptyObject(jsonObj))
            {

            } else {
                var priceForm = jsonObj.price+" | 1x | "+jsonObj.price;
                $('#price').val(priceForm);
                $('#upsellPrice').val(jsonObj.upsellPrice);
                $(".upsellPriceText").empty();
                $(".upsellPriceText").append(parseInt(jsonObj.upsellPrice));
                $('#mfs').val("2");
                $('#freeShip').val("1");

                var priceDoubled = jsonObj.price * 2;

                $('.singlePrice').empty();
                $('.singlePrice').append('<span style="color:#900;text-decoration: line-through;">'+priceDoubled+'</span> - '+jsonObj.price);
            }
        }
    });
    return false;
}
//*********************************************************************
//********** Trenutni datum **********
//*********************************************************************
function getNowDate() {

    var datumSada       = new Date();
    var mjesecSad       = addLeadingChars(datumSada.getMonth() + 1);
    var danSad          = addLeadingChars(datumSada.getDate());
    var satSad          = addLeadingChars(datumSada.getHours());
    var minutSad        = addLeadingChars(datumSada.getMinutes());
    var sekundaSad      = addLeadingChars(datumSada.getSeconds());
    var godinaSad       = addLeadingChars(datumSada.getFullYear());

    var citavDatum      = godinaSad + '-' + mjesecSad + '-' + danSad;
    var citavoVrijeme   = satSad + ':' + minutSad + ':' + sekundaSad;

    var finalniTimestamp = citavDatum+" "+citavoVrijeme;

    return finalniTimestamp;
}

//*********************************************************************
//********** Provjera statusa postarine za order **********
//*********************************************************************
function checkForFreePost(numToAdd){
    var numForFree  = $('#mfs').val();
    var hasFreeShip = $('#freeShip').val();

    if (numForFree <= numToAdd && hasFreeShip == "1") {
        $('#noFreePost').hide();
        $('#yesFreePost').show();
        hasPost = "0";
    } else {
        $('#noFreePost').show();
        $('#yesFreePost').hide();
        hasPost = "1";
    }

}
//*********************************************************************
//********** Dodatni broj vise od 2 provjera **********
//*********************************************************************
function checkValidAdditional(){

    var  aditNum = $('#additionalNum').val();
    if (aditNum < 3) {
        $('#additionalNum').val(3);
    }
}

//*********************************************************************
//********** Dodatni broj vise od 2 provjera **********
//*********************************************************************
function checkValidAdditionalSecond(){

    var  aditNum = $('#additionalNum2').val();
    if (aditNum < 3) {
        $('#additionalNum2').val(3);
    }
}
//*********************************************************************
//**********Promjeni proizvod **********
//*********************************************************************
function changeProduct(pId) {


    $('#product_f').chosen();
    $('#product_f').val(pId);
    $('#product_f').trigger("chosen:updated");
    $('#product_f').trigger("change");

    $(".topProduct").removeClass("bChecked");
}
//*********************************************************************
//********** Selekcija other kategorija **********
//*********************************************************************
function selectOtherSection(catId){
    $('#container_2B').show();
    $('.otherBox,.conversation_cont_A0').hide();
    $('#fix_1,#quest1').hide();

    if (catId == 1){
        // Otkaz narudzbe
        $('#cancelBox').show();

    } else if (catId == 2){
        // Reklamacija
        $('#reklamacijaBox').show();

    } else if (catId == 3){
        // Odjava korisnika
        $('#odjavaBox').show();

        var broj = $('#initialPhone').val();

        if (broj !== ""){
            $('#unBut1').hide();

            $('#odjavaPhone').val(broj);
        }

    } else if (catId == 4){
        // Ostala pitanja
        $('#otherQBox').show();

    } else {}
    checkQuestionBox("quest0");
    $('.first_Button_A').attr("disabled","disabled");

}

//*********************************************************************
//********** Selekcija other kategorija vezano za novi flow Cancel panel **********
//*********************************************************************
function selectOtherSection2(catId){
    
    $('#container_2B').show();
    $('.otherBox,.conversation_cont_A0').hide();
    $('#fix_1,#quest1').hide();

    if (catId == 1){
        $('#container_2A').show();
        $('#fix_2').hide();
        $('.conversation_cont_A0').show();
        
        
       // $('#cancelFindOrder').show();
        $('#cancelBox').show();

    } else if (catId == 2){
        // Reklamacija
        $('#reklamacijaBox').show();

    } else if (catId == 3){
        // Odjava korisnika
        $('#odjavaBox').show();

        var broj = $('#initialPhone').val();

        if (broj !== ""){
            $('#unBut1').hide();

            $('#odjavaPhone').val(broj);
        }

    } else if (catId == 4){
        // Ostala pitanja
        $('#otherQBox').show();

    } else {}
    checkQuestionBox("quest0");
    $('.first_Button_A').attr("disabled","disabled");

}
//*********************************************************************
//********** Dodaj cijenu i upsell ako kampanja ne postoji **********
//*********************************************************************
function addPriceIfNotExists(productId, stateCode, price, upsellPrice){
    var podaci = {};
    podaci["action"] = "addPriceIfNotExists";
    podaci["productId"] = productId;
    podaci["state"] = stateCode;
    podaci["price"] = price;
    podaci["upsellPrice"] = upsellPrice;

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data > 0)
            {
                location.reload();
            }
        }
    });
    return false;
}
//*********************************************************************
//********** Skrolovanje interfacea **********
//*********************************************************************
function scrollInterface(){

    $('html, body').animate({
        scrollTop: $(document).height()
    }, 1000);
}

//*********************************************************************
//********** slusalica dignuta **********
//*********************************************************************
function callUp(callId){
    var inspectSessionId = 0;
    inspectSessionId = getCookie("__insp_sid");

    if (navigator.cookieEnabled == false){
        inspectSessionId = "1";
    }

    var unData = {action:"writeCallUp", id:callId, inspectletId:inspectSessionId};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        data:unData,
        success:function(data)
        {
        }
    });
    return false;
}

//*********************************************************************
//********** poziv zavrsen **********
//*********************************************************************
function callDown(callId){
    var unData = {action:"writeCallDown", id:callId};
    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        data:unData,
        success:function(data)
        {
        }
    });
    return false;
}

//*********************************************************************
//*** Kontakt forma funkcija / ako je prefill uradjen ili ne **********
//*********************************************************************
function openContactForm(){

    $('.fillInfoBox').show();


    if($('.fillInfoBox').hasClass('filled')){
        $('#noOMGbutton').append('<button type="button" class="bluebutton orderType" id="sms_btn" style="width: 350px;" onclick="$(\'.korak2\').show();$(\'#noOMGbutton\').hide();$(\'.fillInfoBox\').hide()">'+noNameFound+'</button>');
    } else {
        $('.korak2').show();
    }

}

function openNextField(field){
    var validAddress = $('#address').val();
    var validNum     = $('#number').val();
    var validCity    = $('#city').val();
    var validPostal  = $('#postal').val();
    var validPhone   = String($('#phone').val());
    var validMail    = $('#email').val();
    var validComment = $('#comment1').val();

    if (field == "korak3"){
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

function checkQuestionBox(objId){
    $("#"+objId).css('background-color', '#34B84F');
    $("#"+objId).css('color', '#fff');
    $("#"+objId).addClass('qChecked');
    $("#"+objId).css('background-image','url("../../images/check-mark-white.png")');
}

//*********************************************************************
//********** Uzmi cijenu i upsell na osnovu Product Id-a **********
//*********************************************************************
function getAllProductPrices(pId){
    var ajaxData = {action:"getAllProductPrices", product:pId, state:countryLocation};
    $('#pagedat').empty();
    $('#smsdat').empty();

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        data:ajaxData,
        success:function(data)
        {
            var jsonObj = JSON.parse(data);

            if($.isEmptyObject(jsonObj))
            {

            } else {

                var priceFormPage = "<b>Web:</b> 1x "+jsonObj.page['price']+" | 2x "+ (parseFloat(jsonObj.page['price'])+parseFloat(jsonObj.page['upsellPrice'])) + " | 3x "+ (parseFloat(jsonObj.page['price'])+(parseFloat(jsonObj.page['upsellPrice'])*2)).toFixed(2);
                $('#pagedat').append(priceFormPage);

                if (jsonObj.sms != undefined) {

                    var priceFormSms = "<b>SMS:</b> 1x " + jsonObj.sms['price'] + " | 2x " + (parseFloat(jsonObj.sms['price']) + parseFloat(jsonObj.sms['upsellPrice'])) + " | 3x " + (parseFloat(jsonObj.sms['price']) + (parseFloat(jsonObj.sms['upsellPrice']) * 2));
                    $('#smsdat').append(priceFormSms);
                    $('#printPrice').val(parseFloat(jsonObj.sms['price']));
                    $('#printUpsellPrice').val(parseFloat(jsonObj.sms['upsellPrice']));

                }

            }
        }
    });
    return false;
}

//*********************************************************************
//********** Uzmi cijenu i upsell na osnovu Product Id-a **********
//*********************************************************************
function defineCodeStatus(){
    if (orderTypeDef == 3){
        $('#quest3b').show();
    } else {
        $('#quest3a').show();
        $('.code2').show();
    }

    noCode();
    checkQuestionBox('quest3');
}

//*********************************************************************
//********** Funkcija za slanje js gresaka na server **********
//*********************************************************************
function logError(details,operatorId) {

    var podaci = {};
    podaci['context'] = navigator.userAgent;
    podaci['details'] = details;
    podaci['operatorId'] = operatorId;

    $.ajax({
        type:"POST",
        url: '../../../../cgi-bin/panelErrorHandler.cgi',
        data: podaci

    });
}
//********************************************************************************
//********** Funkcija za slanje js gresaka na server sa outbound panela **********
//********************************************************************************
function outboundLogError(details,operatorId) {

    var podaci = {};
    podaci['context'] = navigator.userAgent;
    podaci['details'] = details;
    podaci['operatorId'] = operatorId;

    $.ajax({
        type:"POST",
        url: '../../../../cgi-bin/outboundErrorHandler.cgi',
        data: podaci

    });
}

//********************************************************************************
//********** Funkcija za pozivanje timestamp-a sa servera **********
//********************************************************************************
function takeServerTime(type) {

    var podaci = {};
    podaci['action'] = "takeServerTime";

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){

            if (type == "s"){
                startEvent = data;
            } else if (type == "e"){
                endEvent = data;
            }

        }
    });
}
//********************************************************************************
//********** Citanje upisanih cookie parametara **********************************
//********************************************************************************

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}
//********************************************************************************
//********** Notifikacije novih poziva na outboundu ******************************
//********************************************************************************
function showPendingCalls(){
    var stateCurrent  =$("#stateHidden").val();
    var podaci = {};
    podaci["action"] = "showPendingCalls";
    podaci["state"] = stateCurrent;

    $.ajax({
        url: httpSiteURL+"MainAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            $('#callinfo1,#callinfo2,#callinfo3,#callinfo4,#callinfo5').css("display","none");
            $.each(data, function( key, value ) {
                if (value.Tip == 1){
                    if (value.broj > 0){
                        $('#callinfo1').css("display","block");
                        $('#callNum1').empty();
                        $('#callNum1').append(value.broj);
                    }
                } else if (value.Tip == 2){
                    if (value.broj > 0){
                        $('#callinfo2').css("display","block");
                        $('#callNum2').empty();
                        $('#callNum2').append(value.broj);
                    }
                } else if (value.Tip == 3){
                    if (value.broj > 0){
                        $('#callinfo3').css("display","block");
                        $('#callNum3').empty();
                        $('#callNum3').append(value.broj);
                    }
                } else if (value.Tip == 5){
                    if (value.broj > 0){
                        $('#callinfo4').css("display","block");
                        $('#callNum4').empty();
                        $('#callNum4').append(value.broj);
                    }
                } else if (value.Tip == 6){
                    if (value.broj > 0){
                        $('#callinfo5').css("display","block");
                        $('#callNum5').empty();
                        $('#callNum5').append(value.broj);
                    }
                }
            });

        }
    });
    return false;
    // window.clearInterval(handle);
}

//********************************************************************************
//********** Prisilna kalukacija po SMS cijenama  ********************************
//********************************************************************************

function forceSMSprice(){
    var hiddensms       = $('#printPrice').val();
    var hiddensmsUpsell = $('#printUpsellPrice').val();

    var priceForm = hiddensms+" | 1x | "+hiddensms;
    $('#price').val(priceForm);
    $('#upsellPrice').val(hiddensmsUpsell);

    $('.singlePrice').empty();
    $('.singlePrice').append(hiddensms);

    $('#mfs').val("0");
    $('#freeShip').val("1");

}

//********************************************************************************
//********** Skrolovanje na pocetak  ********************************
//********************************************************************************

function scrollToStart(){
    $('html, body').animate({
        scrollTop: $("#quest0").offset().top
    }, 1000);

    $('.cancelBox').hide();
}

//********************************************************************************
//********** Skrolovanje na pocetak  ********************************
//********************************************************************************

function scrollToCancel(){
    $('#cancel_1').show();
    $('html, body').animate({
        scrollTop: $(".cancelBox").offset().top
    }, 1000);

}

function getNewFlow(flow) {

    $('.conversation_cont_A0').empty();

    if (flow == "flow1"){
        $('.conversation_cont_A0').append(htmlOfFlow.flow1);
    } else if (flow == "flow2") {
        $('.conversation_cont_A0').append(htmlOfFlow.flow2);
    } else {
        alert("No flow defined");
    }

    $('#quest_provjera,#quest0,#quest1,#quest2,#quest2a,#quest3,#quest3a,#quest3b,#quest3c,#quest4,#quest5,#quest6,#quest7,#quest8,#quest8a,#quest8a1,' +
        '#quest8a2,#quest8b,#quest8c,#quest9,#quest9b,#quest9c,#quest11a,#quest11b,#quest12a,#quest12b,#quest12c,#quest7c,#cquest1').click(function()
    {
        var border=1;
        if($(this).attr("id")=="quest2a" &&  ($(this).outerWidth() - $(this).innerWidth())/2==3)
        {
            border=3;
        }
        $(this).css('background-color', '#34B84F');
        $(this).css('color', '#fff');
        $(this).addClass('qChecked');
        $(this).css('background-image','url("../../images/check-mark-white.png")');
    });

    $('button').click(function() {
        if ($(this).hasClass("initNumYes") ||
            $(this).hasClass("secondNumYes") ||
            $(this).hasClass("code1b") ||
            $(this).hasClass("code2b") ||
            $(this).hasClass("korak1b") ||
            $(this).hasClass("korak2b") ||
            $(this).hasClass("korak3b") ||
            $(this).hasClass("korak4b") ||
            $(this).hasClass("korak5b") ||
            $(this).hasClass("korak6b")) {

        } else {
            $(this).addClass('bChecked');
        }
    });

    //-------- Kazite mi ime i prezime ----------------
    $('#ime').click(function()
    {
        $('#quest5').addClass('qChecked');
    });
    $('#surname').click(function()
    {
        $('#quest5').addClass('qChecked');
    });
//-------- Odakle nas zovete ----------------
    $('#city').click(function()
    {
        $('#quest6').addClass('qChecked');
    });
//-------- Ulica, kucni broj, postanski broj--
    $('#address').click(function()
    {
        $('#quest7').addClass('qChecked');
    });
    $('#number').click(function()
    {
        $('#quest7').addClass('qChecked');
    });
    $('#postal').click(function()
    {
        $('#quest7').css('background', '#34B84F');
        $('#quest7').css('color', '#FFF');
    });
//-------- Broj telefona ----------------
    $('#phone').click(function()
    {
        $('#quest8').addClass('qChecked');
    });
//-------- Email kupca --------------------
    $('#email').click(function()
    {
        $('#quest8a').addClass('qChecked');
    });
//-------- Napomena kupca --------------------
    $('#komentar').click(function()
    {
        $('#quest8c').css('background', '#34B84F');
        //   $('#quest8c').css('border', '1px solid #00F0B5');
    });

}

//********************************************************************************
//******************** Outbound switcher **********************************
//********************************************************************************


function changeProductStatusSwitch(obj, ordType) {
    var $obj = $(obj);
    var st = $obj.attr('data-action');
    var orderType = ordType;



    var productId = $obj.attr('data-product-id');
    var productState = $obj.attr('data-product-state');
    var statusId = $obj.attr('data-value-status');

    var actionToDo = st;

    $.ajax({
        type: "POST",
        dataType: "JSON",
        url: httpSiteURL+"SettingsAjax",
        data: {
            id: productId,
            state: productState,
            ordType: orderType,
            action: 'changeProductStatus',
            actionToDO: actionToDo,
            statusId: statusId

        }

    }).done(function (data) {

        if (st === 'enable'  || st === 'insert') {
            $obj.attr('title', 'disable').attr('data-value-status', '1').attr('data-action', 'disable');
            $obj.find('i').attr('class', 'fa fa-check').attr('style', 'color:green');

        } else if (st === 'disable') {
            $obj.attr('title', 'enable').attr('data-value-status', '0').attr('data-action', 'enable');
            $obj.find('i').attr('class', 'fa fa-times').attr('style', 'color:darkred');
        }

        console.log(st + " product done");
    }).fail(function () {
        console.log('Errorrrrrr');
    }).always(function () {
        // console.log('always');
    });
    return false;
}

function resetSelectField(obj){
    document.getElementById(obj).selectedIndex = 0
    //borisovo ne radi 
    // var resetObj = obj.closest();
    // console.log(resetObj);
    // document.resetObj.selectedIndex = 0
}

//********************************************************************************
//******************** Change flag from Inbound **********************************
//********************************************************************************

function changeFlagFromInbound(rId,fId) {
    var podaci = {};
    podaci["action"]    = "changeOutboundFlag";
    podaci["table"]     = "phone_order_outbound";
    podaci["id"]        = rId;
    podaci["value"]     = fId;
    podaci["ouid"]      = $('#idHidden').val();
    podaci["validation"]= 0;
    podaci["state"]     = $('#stateHidden').val();

    $.ajax({
        url:httpSiteURL+"MainOutboundAjax",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data > 0)
            {
                console.log("Call redirected");
            }
        }
    });
    return false;
}
//********************************************************************************
//******************** Hardcoded profile settings for product/state **************
//********************************************************************************
function checkProfileByProduct(stateCo, proId){
    console.log("state:"+stateCo+" proizvod:"+proId+" OUTBOUND");

    var prObj = {SI:6,HR:7,IT:8,AT:9,CZ:10,PL:12,HU:13,BG:14,RO:15,GR:16,BA:17,RS:18,MK:19,SK:27,LT:29,LV:30,EE:31,DE:32};

    var profil = 0;
    if (stateCo == "SI") {
        if (proId == 55 ) {
            profil = 80;
        } else if( proId == 199 || proId == 248 || proId == 200 || proId == 235){
            profil = 106;
        } else if (proId == 465 || proId == 471 ||  proId == 314) {
            profil = 98;
        } else {
            profil = prObj[stateCo];
        }
    } else if (stateCo == "HU"){
        if(proId == 39 || proId == 248 || proId == 314 || proId == 199 || proId == 200 || proId == 524 || proId == 241 || proId == 467  ||  proId == 235) {
            profil = 78;
        } else {
            profil = prObj[stateCo];
        }
    } else if (stateCo == "PL"){
        if(proId == 200 || proId == 465 || proId == 199 || proId == 248 || proId == 241 || proId == 314  ||  proId == 235) {
            profil = 104;
        } else {
            profil = prObj[stateCo];
        }
    } else if (stateCo == "SK"){
        if(proId == 200 || proId == 524 || proId == 199 || proId == 248 || proId == 241 ||  proId == 314 ||  proId == 235) {
            profil = 100;
        } else {
            profil = prObj[stateCo];
        }
    } else if (stateCo == "IT"){
        if(proId == 200 || proId == 524 || proId == 248  || proId == 241 || proId == 314  ||  proId == 235 || proId == 524) {
            profil = 102;
        } else {
            profil = prObj[stateCo];
        }
    } else if (stateCo == "CZ"){
        if(proId == 200 || proId == 466 || proId == 524 || proId == 199 || proId == 248 || proId == 241 || proId == 314  ||  proId == 235) {
            profil = 97;
        } else {
            profil = prObj[stateCo];
        }
    } else if (stateCo == "HR"){
        if(proId == 200 || proId == 552 || proId == 553 || proId == 574 || proId == 199 || proId == 593 ||  proId == 248 || proId == 241 || proId == 596 || proId == 316 ||  proId == 235 || proId == 524) {
            profil = 73;
        } else {
            profil = prObj[stateCo];
        }
    } else {
        profil = prObj[stateCo];
    }

    return profil;
}

function insertCallerYears(){
    var years1      = $('#numYears1').val();
    var years2      = $('#numYears2').val();
    var callerId    = $('#callerId').val();

    var podaci = {};
    podaci['action'] = "insertCallerYears";
    podaci['years']  = "";

    if (years1 != '' && years1 != null && years1 != undefined){
        podaci['years']  = years1;
    } else if (years2 != '' && years2 != null && years2 != undefined){
        podaci['years']  = years2;
    } else {
        return false;
    }

    if (callerId != '' && callerId != null && callerId != undefined){
        podaci['callerId']  = callerId;
    } else {
        return false;
    }

    $.ajax({
        url: httpSiteURL+"MainOutboundAjax",
        // url:httpSiteURL+"outbound/adapter.php",
        type:"POST",
        dataType:"JSON",
        data:podaci,
        async: true,
        success:function(data){
            if(data > 0)
            {
                console.log("Years entered");
            }
        }
    });
    return false;
}