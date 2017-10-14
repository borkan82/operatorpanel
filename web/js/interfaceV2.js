

var stateCode = $('#state').val();
var content =JSON.parse($('#content').val());

$(document).ready(function(){
    console.log(stateCode);
    $('#b0').click(function()
    {
        //$('#b1').attr('disabled', 'disabled');
        $('#b0').css('background', '#34B84F');
        //$('#b0').css('border', '1px solid #00F0B5');
    });
//produkt pretraga inicijalizacija
    $(".chosen-select-product").chosen({no_results_text: content[200]});


    
//get value from given element and append it to appendelement
    function setPrice(element,appendElement)
    {
        var cijenaInfo=$(element).val();
        var splited =  cijenaInfo.split("|");
        if(splited.length>0)
        {
            var cijena = splited[0];
        }
        else
        {
            var cijena='';
        }
        $(appendElement).html(cijena);
    }
//END get price
    $('button').click(function()
    {
        if ($(this).hasClass("initNumYes") ||
            $(this).hasClass("secondNumYes") ||
            $(this).hasClass("code1b") ||
            $(this).hasClass("code2b") ||
            $(this).hasClass("korak1b") ||
            $(this).hasClass("korak2b") ||
            $(this).hasClass("korak3b") ||
            $(this).hasClass("korak4b") ||
            $(this).hasClass("korak5b") ||
            $(this).hasClass("korak6b")){

        } else {
            $(this).addClass('bChecked');
        }
    });
// ----------- TOP FORM --------------------------------------

    $('#open_order').click(function(){
        $('#conversation_flow').slideDown("slow");
       // newRequest();
        startO("WARNING: ORDER NOT COMPLETED!");
    });
    $('#quest_provjera,#quest0,#quest1,#quest2,#quest2a,#quest3,#quest3a,#quest3b,#quest3c,#quest4,#quest5,#quest6,#quest7,#quest8,#quest8a,#quest8a1,#freeDelivery2,' +
        '#quest8a2,#quest8b,#quest8c,#quest9,#quest9b,#quest9c,#quest11a,#quest11b,#quest12a,#quest12b,#quest12c,#quest7c,#cquest1,#priceDiscount2,#priceDiscount4').click(function()
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
    $('.quest_xml').click(function()
    {
        $(this).css('background-color', '#34B84F');
        $(this).css('color', '#fff');
        $(this).addClass('qChecked');
        $(this).css('background-image','url("../../images/check-mark-white.png")');
    });
    $(".quest_cont_drugo,.firstInfo").click(function(){
        if ($(this).hasClass('noCheck') == false) {
            $(this).addClass('bChecked');
        }
    });
//----------- promjena tipa ordera ------------------
// ----------- Prvo pitanje u konverzaciji --------------------------------------
    $('.first_Button_A').click(function()
    {
        $('#first_Button_B').removeClass('bChecked');
        $('#container_Form').fadeIn("slow");
        $('#quest0').addClass('qChecked');
        // $('#quest0').css('border', '1px solid #00F0B5');
        //$('#container_2A').slideDown("slow");
        $('#container_2B').slideUp("slow");
        $('#general_info2').fadeOut("slow");
        $(this).addClass('bChecked');
        $('#orderType').fadeIn();

        $('.commentBoxHolder,.campaignsHolder').fadeIn();
        $('#fix_1').show();
        if ($('.first_Button_A').hasClass('showSectionA')){
            $('#container_2A').slideDown('slow');
            $('.first_Button_A').removeClass('showSectionA');
        }
        $('#fix_xx').show();
        $('.otherButtons').attr('disabled','disabled');
        checkQuestionBox("quest0");
        //trackView('ticket_order');
        typeEvent = "ORDER";
        //var smsKampanja = $('#testCamp').text();
        //console.log(smsKampanja);
       // if (smsKampanja.length > 5){
              // getCampaignInfoByName(smsKampanja);
       // }
    });

    $('#first_Button_B').click(function()
    {
        $('#first_Button_A').removeClass("bChecked");
        $('.first_Button_A').addClass("showSectionA");
        $('#container_Form').fadeOut("slow");
        $('#container_2B').slideDown("slow");
        $('#container_2A').slideUp("slow");
        $('#quest0').addClass('qChecked');
        // $('#quest0').css('border', '1px solid #00F0B5');
        $('#general_info2').fadeIn("slow");
        $(this).addClass('bChecked');
        $('.commentBoxHolder,.campaignsHolder').fadeOut();
        $('#orderType,#rcampaigns,#campaigns,#quest1').fadeOut();
        //trackView('ticket_other');
        typeEvent = "OTHER";
    });
    $('#quest2').click(function(){
        $('#fix_1').show();
    });

    $('#quest2a').click(function(){
        $('.secondPhoneBox').show();
    });

    $('#quest1').click(function(){
        $('.orderType').show();
    });
//------------ opcija DRUGO (4 dugmeta) -------------------
    $('#POVRAT_B').click(function(){
        otherOptEvent += " REFUND ";

    });

    $('#REKLAMACIJA_B').click(function(){
        otherOptEvent += " WRONG ORDER ";
    });

    $('#ODJAVA_B').click(function(){
        otherOptEvent += " UNSUBSCRIBE ";
    });

    $('#INFO_B,#INFO_B1').click(function()
    {
        $('.faqBox').slideDown('slow');
        otherOptEvent += " PRODUCT ";
    });

    $('.faqQuest').click(function(){
        $(this).addClass('bChecked');;
    });

    $('#faqA,#faqB,#faqC,#faqD').click(function(){
        var faqRaz = $(this).attr('faq-id');
        faqReason(faqRaz)
        $(this).addClass('bChecked');
    });

    $('#DOSTAVA_B,#DOSTAVA_B1').click(function()
    {
        otherOptEvent += " SHIPPING ";
    });

    $('#snimirazlog').click(function(){
            cancelReason = $('#razlogOtk').val();
        if (cancelReason != "" && cancelReason != "ERROR!"){
            cancelEvent = 'YES';
            cancelConfirm();
            endOrder("CANCELED!",0);
        } else {
            $('#razlogOtk').val("ERROR!");
            $('#razlogOtk').css("color","#C00");
            showWarning("Please enter the reason!");
        }
    });

    $('#zakljuci_Button').click(function(){

        $('#quest11').css('background', '#34B84F');
        //$('#quest11').css('border', '1px solid #00F0B5');
        $('#confirm').select();
        //trackSuccess('ticket_order');
        $('html, body').animate({
            scrollTop: $("#confirm").offset().top
        }, 1000);
    });

    $('#otkazi_Button').click(function(){
        $('#otkazBox').fadeIn('slow');
    });
// Selekcija razloga za otkazivanje narudzbe
    $('.cReason').change(function (){
        otkaz($(this).val());
    });

    $('#freeDelivery2').click(function(){
        $('.secondFreeDelivery').show();
    });
    $('#priceDiscount2').click(function(){
        $('.secondPriceDisc1').show();
        var discount = getDiscounts(1);
        $('#discount1').val(discount);
        $('#discount1').text('Cijena sa popustom '+discount+' KM');
        
    });
    $('#priceDiscount4').click(function(){
        $('.secondPriceDisc2').show();
        var discount2 = getDiscounts(2);
        $('#discount2').val(discount2);
        $('#discount2').text('Cijena sa popustom '+discount2+' KM');
    });
    
    


    $('#zakljuci_Button2').click(function()
    {
        $('.newOrder2').empty();
        $('.newOrder2').append("<strong>"+ content[201]+ "</strong>");
        var faqComment = $('#comment1').val();
        if (faqComment != "") {
            otherEvent = faqComment;
        }
        $('#zakljuci_Button2').attr("disabled","disabled");
        $('#confirm2').show();
        endOrder("NO ORDER!",0);
    });
//----------- Koji proizvod zelite - NARUCI----------------
    $('#product_f').change(function()
    {
        var $proizv             = $('#product_f');
        $proizv                 = $proizv.val();
        var upsellCijena        = $('#upsellPrice').val();

        var prName                = $('#product_f').find('option:selected').text();
        var fulskuExp              = $("#product_f option:selected").data("fullsku"); //fullsku.split("~");
        $('#full_sku').val(fulskuExp);

        //setProduct($(".chosen-single span").text());
        if ($proizv == "55" || $proizv == "3"){
            $('#qid9').show();
        } else {
            $('#qid9').hide();
        }

        $(".cijene_cont").empty();
        $(".prodViewList").empty();
        $(".appendPaketa").empty();
        $("#pDescBox").hide();
        $(".productDescr").empty();

        if (typeof json_desc != "undefined" && json_desc[$proizv] != "" && typeof(json_desc[$proizv]) != "undefined"){
            $("#pDescBox").show('slow');
            $(".productDescr").append(json_desc[$proizv]);
        }

        var $nazivPr = $(".chosen-single span").text();
        $(".stanjeLine").empty();

        $(".stanjeLine").append("<strong>"+content[205]+ " "+content[206]+"</strong>");
        $(".pro_stanje").empty();
        $(".pro_stanje").append(prName);
        $(".P_proizvod").empty();
        $(".P_proizvod").append(prName);
        $(".upsellPriceText").empty();
        $(".upsellPriceText").append(upsellCijena);
        $(".productNameText").empty();
        $(".productNameText").append(prName);

        $('#phoneSelectBox').show();

        var smsKampanja = $('#testCamp').text();
        var inicijalniBr = $('#initialPhone').val();

        if (smsKampanja.length > 5 || (smsKampanja.length < 5 && inicijalniBr.length > 6) ){
            $('#quest1').fadeIn();
        }

        getAllProductPrices($proizv);

        $(".stanjeLine").empty();



        scrollInterface();

    });
//----------- Imate li kod za popust ----------------
    $('#A1_Button').click(function()
    {
        $('#A2_Button').attr('disabled', 'disabled');
        $('#quest3').css('background', '#34B84F');
        // $('#quest3').css('border', '1px solid #00F0B5');
        //updateCode($('#code').val());
    });
    $('#A2_Button').click(function()
    {
        $('#A1_Button').attr('disabled', 'disabled');
        $('#quest3').css('background', '#34B84F');
        // $('#quest3').css('border', '1px solid #00F0B5');
        $('#quest4').slideDown("slow");

    });
    $('#quest4').click(function()
    {
        $('#quest4').css('background', '#34B84F');
        // $('#quest4').css('border', '1px solid #00F0B5');
    });

    $('#code').click(function()
    {
        $('#quest3').addClass('qChecked');
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

// Autocomplete opcija
    $( "#product_f" ).autocomplete({
        source: function( request, response ) {
            var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( request.term ), "i" );
            response( $.grep( tags, function( item ){
                return matcher.test( item );
            }) );
        }
    });
    $( "#product_f" ).autocomplete({
        select: function( event, ui ) {
        }
    });

});

window.onerror = function(message, file, line) {
    var opId = $('#idHidden').val();
    logError('Panel:' + stateCode + '\nErr: ' + file + ':' + line + '\n' + message, opId);
};

$(document).ajaxError(function(e, xhr, settings) {
    var opId = $('#idHidden').val();
    logError('Panel:' + stateCode + '\n Err: ' + settings.url + ':' + xhr.status + '\n\n' + xhr.responseText, opId);
});

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
//prices for discounts
function newPrice(price){
//         $('#dodatni0').attr('onclick','newPrice('+newPrice+')');
//         $('#dodatni1').attr('onclick','newPrice('+newPrice*2+')');
//         $('#dodatni2').attr('onclick','newPrice('+newPrice*3+')');
    // $('#dodatni3').prop('onclick',newPrice($('#dicount1').val()*4)).on('click');
    // $('#dodajN').prop('onclick',newPrice($('#dicount1').val()*n)).on('click');

    $('#noFreePost').hide();
    $('#yesFreePost').show();
    var nazivPr                = $('#product_f').find('option:selected').text();
    hasPost = "0";
    $('.P_proizvod').empty();
    $('.P_proizvod').append(nazivPr+" "+price);


    $('#freeDelivery1').hide();
    $('#priceDiscount1').hide();
    $('#priceDiscount3').hide();
    // newPrice($('#dicount1').val())
}

var specialOfferData = {}; // Priprema podataka za special order

function formaCheck(){

    // Provera podataka iz forme

    var error = false;
    var a = {};// Promjenjiva za smještanje podataka iz forme
    // Provera imena

    var $ime = $('#ime');
    if($ime.val().length < 1 || $ime.val() == content[100]  || $ime.val() == content[101] ){
        $ime.addClass('crvenaS');
        $ime.val(content[101]);
        error = true;
    }
    else {
        a.name=$.trim($ime.val());
    }
    // Provera prezimena

    var $prezime = $('#surname');
    if($prezime.val().length < 1 || $prezime.val() == content[102]  || $prezime.val() == content[103] ) {
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
    if($adresa.val().length < 1 || $adresa.val() == content[104]  || $adresa.val() ==  content[105] ){
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
    if($brPoste.val().length < 1 || $brPoste.val() == content[110]  || $brPoste.val() == content[111]) {
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
    if(orderTypeDef == "1" || orderTypeDef == "3"){
        if($("#code").hasClass("assigned") == false){
            if($("#code").val() == ""){
                noCode();
            } else {
                getLandingPage();
            }
        }
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

    // Komentar ako postoji
    // var postponedDeliv  = $('#postponedDeliv').val();
    // var postponedAppend = "";
    //
    // if (postponedDeliv != undefined && postponedDeliv != "" && postponedDeliv != null && postponedDeliv.length > 5){
    //     postponedAppend = " [DELIVERY POSTPONED FOR: " + postponedDeliv + "] ";
    // }
    //
    // var $komentar = "PhoneCode[" + $('#code').val() + "]" + postponedAppend + $('#comment1').val();
    // if($komentar != 'Komentar' && $komentar.length > 0){
    //     a.comment=$.trim($komentar);
    // }
    //
    
    var $komentar = "PhoneCode[" + $('#code').val() + "]" + $('#comment1').val();
    if($komentar != 'Komentar' && $komentar.length > 0){
        a.comment=$.trim($komentar);
    }

    var imeProizvoda    = $('#product_f').find('option:selected').text();
    var jedinicnaCijena = $('#price').val().split(" ");
    var totalCijena     = jedinicnaCijena[0];
    var totalSum        = 0;
    var productO = {};
    var productU = {};
    var productE = {};
    productO.full_sku = $('#full_sku').val();
    productO.discount = 0;
    a.order_items = [];
    bPrice = parseFloat(totalCijena);

    if (konacnaKolicina > 1){
        totalCijena = (parseFloat(jedinicnaCijena) + (parseFloat($('#upsellPrice').val())*parseInt(konacnaKolicina-1)));// / parseFloat(konacnaKolicina);
        totalSum = totalCijena;

        if (productO.full_sku == "0262-911-0252"){
            vivalFix(konacnaKolicina);
        } else {

            if (stateCode == "BA"){
                totalCijena = jedinicnaCijena[0];
                productO.price = totalCijena;
                productO.quantity = 1;
                a.order_items.push(JSON.stringify(productO));

                for (i = 0; i < konacnaKolicina-1; i++) {
                    var upsellCijenaO = parseFloat($('#upsellPrice').val());
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
        }

    } else {

        if (productO.full_sku == "0262-911-0252"){
            vivalFix(1);
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

    }
    function vivalFix(endQuant){

        if (endQuant > 1){

            if (stateCode == "BA") {
                totalCijena = parseFloat(jedinicnaCijena[0] / 3);
                productO.full_sku = "0262-911-0252";
                productO.price = totalCijena;
                productO.quantity = 1;
                a.order_items.push(JSON.stringify(productO));


                var upsellCijenaO = parseFloat($('#upsellPrice').val() / 3);
                productU.discount = 0;
                productU.price = upsellCijenaO;
                productU.quantity = 1;

                for (i = 0; i < konacnaKolicina - 1; i++) {
                    productU.full_sku = "0262-911-0252";
                    a.order_items.push(JSON.stringify(productU));
                }

                productO.full_sku = "0262-911-0253";
                a.order_items.push(JSON.stringify(productO));


                for (i = 0; i < konacnaKolicina - 1; i++) {
                    productU.full_sku = "0262-911-0253";
                    a.order_items.push(JSON.stringify(productU));
                }

                productO.full_sku = "0262-911-0254";
                a.order_items.push(JSON.stringify(productO));

                for (i = 0; i < konacnaKolicina - 1; i++) {
                    productU.full_sku = "0262-911-0254";
                    a.order_items.push(JSON.stringify(productU));
                }

                if (hasPost == "1") {
                    productE.full_sku = "0011-666-0283";
                    productE.discount = 0;
                    productE.price = 2.00;
                    productE.quantity = 1;

                    a.order_items.push(JSON.stringify(productE));
                }
            } else {

                var vivalTotal = totalCijena/3;
                productO.full_sku = "0262-911-0252";
                productO.discount = 0;
                productO.price = vivalTotal;
                productO.quantity = endQuant;
                a.order_items.push(JSON.stringify(productO));

                productO.full_sku = "0262-911-0253";
                a.order_items.push(JSON.stringify(productO));

                productO.full_sku = "0262-911-0254";
                a.order_items.push(JSON.stringify(productO));

            }

        } else {

            var vivalTotal = totalCijena/3;
            productO.full_sku = "0262-911-0252";
            productO.discount = 0;
            productO.price = vivalTotal;
            productO.quantity = endQuant;
            a.order_items.push(JSON.stringify(productO));

            productO.full_sku = "0262-911-0253";
            a.order_items.push(JSON.stringify(productO));

            productO.full_sku = "0262-911-0254";
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
    }
    var fullSku      = $('#full_sku').val();
    var skusplit    = fullSku.split("-");
    var prId        = parseInt(skusplit[2]);


    //var profilId    = checkProfileByProduct(stateCode, prId);
    var idKorisnika = $('#idHidden').val();
    var tipOpPanela = $('#panelType').val();

    ePrice          = parseFloat(totalSum);
    a.product       = konacnaKolicina+"x "+totalSum;
    a.product_name  = imeProizvoda;
    a.ordersource   = "PHN";
    //a.profile       = profilId;
    a.state         = stateCode;
    a.paymentmethod = "COD";
    a.postage       = hasPost;
    a.codservice    = "No";
    a.orderdate     = getNowDate();
    a.extint1       = parseInt(idKorisnika);
    a.extint2       = 1;
    a.product_id    = prId;

    if (tipOpPanela == "multipanel"){
        a.extint2       = 2;
    }
    console.log(a);
    //definisanje paketa proizvoda koji se salje

    if(error){
        return false;
    }
    else {

        console.log(a);
        //return false;
        //Slanje podataka

        specialOfferData = a;
        $('#confirm').slideDown("slow");
        $('#zakljuci_Button').attr('disabled', 'disabled');
         // PROMIJENITI ... OVDJE JE ZAUSTAVLJENA NARUDZBA!!!!!!!!!
        $.ajax({
            type: "POST",
            dataType: "JSON",
            // $.post(httpSiteURL+"api/kontakt.deamon.php"
            url: httpSiteURL+'api/kontakt.deamon.php',
            data: a,
            success: function (res) {
                console.log(content);
                var otvoriNovuDaBogDaCrko = content[201];
                
                setTimeout(function(){$('#confirm').hide();},1510);
                console.log(res);
                if (res.result == "OK") {
                    console.log(otvoriNovuDaBogDaCrko);
                    var orderSubmitId = res.rand;
                    $('#confirm').show();
                    $('.newOrder').empty();
                    $('.newOrder').append("<strong>"+otvoriNovuDaBogDaCrko+"</strong>");
                    // Blokada dugmeta za otkazivanje narudzbe i za ponovno narucivanje
                    $('#otkazi_Button').attr('disabled', 'disabled');

                    //Salje END event u varijablu
                    endOrder("ORDERED!",orderSubmitId);
                    //set phoencode to used
                    var orderType = $('#code').val();
                    var codeLen = orderType.length;
                    if (orderType !== "6969" && codeLen !== 5){
                        setPhonecodeused();
                    }

                } else {
                    $('#confirm').show();
                    $('#confirm').empty().html('Error!').delay(3000).fadeOut(3000);
                    endOrder("ERROR!",0);
                }
            },
            error: function (xhr, status, errorThrown) {
                var opId = $('#idHidden').val();
                //Here the status code is can be retrieved like;
                $('#confirm').show();
                $('#confirm').empty().html('Order successful').delay(3000).fadeOut(4000);
                endOrder("ERROR!",0);

                logError('Data:' + a + '\nErr: ' + status + '\n', opId);
            }
        });
        return false;
    }
}

//*********************************************************************
//********** FUNKCIJA ZA SPECIAL OFFER  *******************************
//*********************************************************************

function makeSpecialOffer(){
    var product_id = $('#specialProd option:selected').data('pr');
    var callId = $('#phCall_id').val();
    
    specialOfferData.product_name = $('#specialProd option:selected').text();
    specialOfferData.product      = $('#specialProd option:selected').data('sp');
    specialOfferData.product_id   = product_id;
    
    addToPhoneCall(callId, product_id);
    $.post(httpSiteURL+"api/kontakt.deamon.php", specialOfferData, function (res) {
        setTimeout(function(){$('#confirm').hide();},1510);
        console.debug(res);
        if (res.result == "OK") {
            $('.specialPopup').hide('fast');
            $('#confirm').show('fast');
            $('html, body').animate({
                scrollTop: $("#confirm").offset().top
            }, 1000);

        } else {
            $('#confirm').empty().html('Error!').delay(3000).fadeOut(3000);
            endOrder("ERROR!",0);
        } return false;
    }, 'json');


}

function checkPackage (orderType, package) {

    var paketArr = package.split(" ");
    var paketType = "";
    if ( $.inArray('SMS', paketArr) > -1 ) {
        paketType = "BULK";
    } else if ( $.inArray('RE', paketArr) > -1 ){
        paketType = "REORDER";
    } else if ( $.inArray('RABAHO', paketArr) > -1 || package.indexOf("Rabaho") != -1) {
        paketType = "RABAHO";
    } else if ( $.inArray('Special', paketArr) > -1 ) {
        paketType = "SPECIAL";
    }

    if (orderType == 1 && paketType != "BULK" && paketType != "REORDER" && paketType != "RABAHO" && paketType != "SPECIAL") {
        return true;
    } else if (orderType == 2 && paketType == "BULK") {
        return true;
    } else if (orderType == 4 && paketType == "REORDER") {
        return true;
    } else {
        return false;
    }
}

function checkProfileByProduct(stateCo, proId){
    console.log("state:"+stateCo+" proizvod:"+proId+" INBOUND");

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

    $('#noFreePost').append('<strong>'+ content[34]+'<span style="color:#1137FE">'+$('#postHidden').val() +' '+$('#currencyHidden').val()+'. </span>'+content[510]+'<span style="color:#1137FE">' +dateToPostponeDeliv+'</span>.</strong>');
    $('#yesFreePost').append('<strong><span style="color:#1137FE">'+ content[119]+'. </span>'+content[510]+'<span style="color:#1137FE"> ' +dateToPostponeDeliv+'</span></strong>');
    

}
function bstep23(){
    $('#changeOrderCondButton').show();
}
function bstep24(){
    $('#changeOrderCondButton').hide();
    $('#showFreeDelivDisc').hide();
    $('#changeOrderCondButton').removeClass('bChecked');
}

function operaterFreeDelivery() {
    hasPost = "0";
    //
    //     var nazivPr     = $('#product_f').find('option:selected').text();
    //
    //     var basePrice   = $('#price').val();
    //
    //
    //     checkForFreePost(3);
    //     var newPrice    = basePrice;
    //
    //     konacnaKolicina = parseInt(num)+1;
    //
    //     var priceForm   = newPrice.toFixed(2)+" | "+(parseInt(num)+1)+"x | "+newPrice.toFixed(2);
    //
    //     $('.P_proizvod').empty();
    //     $('.P_proizvod').append(nazivPr+" "+priceForm);
    //
    //
    // if ($('#postponedDeliv').val() === ''){
    //
    // }
  
    $('#freeDelivery1').hide();
    $('#priceDiscount1').hide();
    $('#priceDiscount3').hide();
}
function operaterDiscount1(){

    $('#dodatni0,#dodatni1,#dodatni2,#dodajN').attr('onclick','').unbind('click');
  
    var newPrice = $('#discount1').val();
  
    $('#dodatni0').attr('onclick','newPrice('+newPrice+')');
    $('#dodatni1').attr('onclick','newPrice('+newPrice*2+')');
    $('#dodatni2').attr('onclick','newPrice('+newPrice*3+')');
    // $('#dodatni3').prop('onclick',newPrice($('#dicount1').val()*4)).on('click');
    // $('#dodajN').prop('onclick',newPrice($('#dicount1').val()*n)).on('click');

    $('#noFreePost').hide();
    $('#yesFreePost').show();
    var nazivPr                = $('#product_f').find('option:selected').text();
    hasPost = "0";
    $('.P_proizvod').empty();
    $('.P_proizvod').append(nazivPr+" "+newPrice);
    
    
    $('#freeDelivery1').hide();
    $('#priceDiscount1').hide();
    $('#priceDiscount3').hide();
    // newPrice($('#dicount1').val())
}

function newPrice(price){
    
}
function operaterDiscount2(){
    $('#freeDelivery1').hide();
    $('#priceDiscount1').hide();
    $('#priceDiscount3').hide();

}
function hideFreeDeliveryDiscount() {
    $('#freeDelivery1').hide();
    $('#priceDiscount1').hide();
    $('#priceDiscount3').hide();
    $('#freeDelivery').removeClass('bChecked');
    
}

function getDiscounts(n){
    var prices =  pricesForThreeandTwoProducts();
    console.log(prices);
    console.log(n);
    if (n === 1){
        var priceWithDiscount =   (prices.forTwo/2).toFixed(2);
    }else if(n === 2){
        var priceWithDiscount =   (prices.forThree/3).toFixed(2);
    }
    console.log(priceWithDiscount);
    return priceWithDiscount;
}

function pricesForThreeandTwoProducts(){
    var arrayPrice  = $('#price').val().split("|");
    var upsell = $('#upsellPrice').val();
    var price = arrayPrice[0].trim();
    
    var prices = {};
    prices.forOne = parseFloat(price);
    prices.forTwo = parseFloat(price)+ parseFloat(upsell);
    prices.forThree = parseFloat(price) +  parseFloat(upsell)*2;
    console.log(prices);
    return prices;
    
}



