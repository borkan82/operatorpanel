<?php
namespace AjaxBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Main;
use AppBundle\Entity\Settings;
use AppBundle\Entity\OMG;
use AppBundle\Entity\SMS;

class MainAjaxController extends Controller
{


    public function ajaxAction()
    {

        $conn       = $this->get('database_connection');
        $_settings  = new Settings($conn);
        $_main      = new Main($conn);
        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);

        $request    = Request::createFromGlobals();

        $post = $request->request->get('action');

        if (isset($post)) {

            switch($post){
                case "writePhoneOrder":
                    $mjesec     = Date('Y-m');
                    $vrijeme    = Date('H:i:s');
                    $timestamp  = Date('Y-m-d H:i:s');

                    $state      = $request->request->get('country');
                    $date       = $request->request->get('date');
                    $code       = $request->request->get('codeNum');
                    $start      = $request->request->get('start');
                    $end        = $vrijeme;

                    $unixS      = strtotime($start);
                    $unixE      = strtotime($vrijeme);
                    $unixD      = $unixE - $unixS;

                    $duration   = gmdate('H:i:s', $unixD);

                    $otherOpt       = $request->request->get('otherOpt');
                    $productWork    = $request->request->get('productWork');
                    $getInvoice     = $request->request->get('getInvoice');
                    $buyStore       = $request->request->get('buyStore');
                    $other          = $request->request->get('other');
                    $sucess         = $request->request->get('sucess');
                    $cancel         = $request->request->get('cancel');
                    $cancelRe       = $request->request->get('cancelRe');
                    $cName          = $request->request->get('showName');
                    $cSurname       = $request->request->get('showSurname');
                    $cAddress       = $request->request->get('showStreet');
                    $cCity          = $request->request->get('showCity');
                    $cPhone         = $request->request->get('showPhone');
                    $cMail          = $request->request->get('showMail');
                    $type           = $request->request->get('type');
                    $quantity       = $request->request->get('quantity');
                    $orderType      = $request->request->get('orderType');
                    $campaignId     = $request->request->get('campaign');
                    $basePrice      = $request->request->get('baseInPrice');
                    $endPrice       = $request->request->get('endInPrice');
                    $sessionId      = (int)$request->request->get('writeSession');
                    $cancelStatus   = $request->request->get('cancelStat');
                    $flowType       = $request->request->get('showFlow');

                    $toLog          = "{".$timestamp.", ".$otherOpt.", ".$productWork.", ".$getInvoice.", ".$buyStore.", ".$other.", ".$sucess.", ".$cancel.", ".$cancelRe.", ".$cName.", ".$cSurname.", ".$cAddress.", ".$cCity.", ".$cPhone.", ".$cMail.", ".$type.", ".$quantity.", ".$orderType.", ".$campaignId.", ".$basePrice.", ".$endPrice.", ".$sessionId.", ".$cancelStatus.", ".$flowType." } \n ";

                    $file           = fopen("/var/www/sites/phone-sale.net/htdocs/clp456/logs/inbound/".$mjesec.".txt", "a");
                    file_put_contents("/var/www/sites/phone-sale.net/htdocs/clp456/logs/inbound/".$mjesec.".txt", $toLog, FILE_APPEND);
                    fclose($file);

                    $korisnik       = $request->request->get('korisnik');
                    $proizvod       = $request->request->get('proizvod');
                    $submitOrder    = $request->request->get('submitOrder');
                    $campaign       = $request->request->get('campaign');

                    if (isset($korisnik) && !empty($korisnik)){
                        $korisnik = $request->request->get('korisnik');;
                    } else {
                        $korisnik = "26";
                    }
                    if (isset($proizvod) && !empty($proizvod)){
                        $proizvod = $request->request->get('proizvod');;
                    } else {
                        $proizvod = "0";
                    }
                    if (isset($submitOrder) && !empty($submitOrder)){
                        $submitOrderId = $request->request->get('submitOrder');
                    } else {
                        $submitOrderId = "0";
                    }

                    if (isset($campaign) && !empty($campaign)){
                        $campaignId = $request->request->get('campaign');
                    } else {
                        $campaignId = "page";
                    }



                    $writeOrder = $_omg->writePhoneOrder($orderType, $state,$code,$start,$end,$duration,$type,$otherOpt,$productWork,$getInvoice,$buyStore,$other,$sucess,$cancel,$cancelRe,
                                                         $cName,$cSurname,$cAddress,$cCity,$cPhone,$cMail,$korisnik, $proizvod, $submitOrderId, $campaignId, $basePrice, $endPrice, $sessionId, $cancelStatus, $flowType);
                    if(isset($campaign) && $campaign !== ""){
                        $campaign   = $request->request->get('campaign');

                        $tip        = substr($campaign, 0, 3);
                        $tabela     = "CampManagement";

                        if ($tip == "sms") {
                            $tabela = "CampManagement";
                        } else if ($tip == "reo") {
                            $tabela = "phone_order_reorder";
                        }

                        // ***** filter za provjeru i ciscenje mobilnih brojeva na pravilnu strukturu
                        $filteredPhone  = $_sms->cleanMobile($cPhone,$state);

                        // ***** Brisanje korisnika iz liste ako je napravio order, da ne udje u slanje 2. poruke
                        $removeCaller   = $_sms->removeCall($campaign,$filteredPhone);

                        if ($sucess == "ORDERED!"){
                            $num = (int)$quantity;
                            $increaseOne = $_omg->increaseByOne($tabela,"Orders","CampaignName","$campaign");               // *** Broj ordera povecaj za 1
                            $increaseOne = $_omg->increaseByOne($tabela,"productSent","CampaignName","$campaign",$num);     // *** Broj prodatih proizvoda povecaj za 1
                        } else {
                            $increaseOne = $_omg->increaseByOne($tabela,"NotOrders","CampaignName","$campaign");            // *** Broj non-ordera  povecaj za 1
                        }
                    }

                   return new Response($writeOrder);
                break;
                case "addSpecialToCall":
                    $callId     = $request->request->get('callId');
                    $product_id = $request->request->get('product_id');
                    $table      = "phone_order_calls";
                    $field      = "special";

                    // **** Dodavanje specijalne ponude na poziv
                    $update = $_sms->changeFieldValue($callId,$table,$field,$product_id);
                    return new Response($update);

                    break;
                case "cancelPhoneOrder":
                    $state       = $request->request->get('country');
                    $date        = $request->request->get('date');
                    $code        = $request->request->get('codeNum');
                    $start       = $request->request->get('start');
                    $end         = $request->request->get('end');
                    $duration    = $request->request->get('duration');
                    $otherOpt    = $request->request->get('otherOpt');
                    $productWork = $request->request->get('productWork');
                    $getInvoice  = $request->request->get('getInvoice');
                    $buyStore    = $request->request->get('buyStore');
                    $other       = $request->request->get('other');
                    $sucess      = $request->request->get('sucess');
                    $cancel      = $request->request->get('cancel');
                    $cancelRe    = $request->request->get('cancelRe');
                    $cName       = $request->request->get('showName');
                    $cSurname    = $request->request->get('showSurname');
                    $cAddress    = $request->request->get('showStreet');
                    $cCity       = $request->request->get('showCity');
                    $cPhone      = $request->request->get('showPhone');
                    $cMail       = $request->request->get('showMail');
                    $type        = 2;

                    // **** Upisi Cancell Call za poziv
                    $writeOrder  = $_omg->writePhoneOrder($state,$code,$start,$end,$duration,$type,$otherOpt,$productWork,$getInvoice,$buyStore,$other,$sucess,$cancel,$cancelRe,$cName,$cSurname,$cAddress,$cCity,$cPhone,$cMail);

                    if ($writeOrder) {
                        return new Response("1");
                    } else {
                        return new Response("-1");
                    }
                break;
                case "getSalesPackages":
                    $state      = $request->request->get('state');
                    $product    = $request->request->get('product');

                    // **** povuci listu sales paketa
                    $getSP      = $_omg->getSPlist(" state='$state' AND product=$product");

                    return new Response(json_encode($getSP));
                    exit;
                break;
                case "getSenderId":
                    $state      = $request->request->get('state');

                    // **** Uzmi sender ID za SMS na osnovu drzave
                    $getSender  = $_omg->getSenderId($state);

                    return new Response(json_encode($getSender));
                    exit;
                break;

                case "addSpecialOffer":
                    $Product    = $request->request->get('Product');
                    $ProductOrd = $request->request->get('ProductOrd');
                    $state      = $request->request->get('country');
                    $salesPack  = $request->request->get('salesPack');
                    $offerText  = $request->request->get('offerText');

                    // **** Upisi specijalnu ponudu
                    $writeOffer = $_omg->writeSpecialOffer($Product,$ProductOrd,$state,$salesPack,$offerText);
                    return new Response($writeOffer);

                break;

                case "getOfferText":
                    $offerId = $request->request->get('offerId');

                    // **** Uzmi tekst za ponudu
                    $getText = $_omg->getOfferText($offerId);

                    return new Response(json_encode($getText));
                    exit;
                break;

                case "getSpecialList":
                    $State      = $request->request->get('state');
                    $Product    = $request->request->get('product');

                    $getList    = $_omg->getSpecialList($State,$Product);

                    return new Response(json_encode($getList));
                    exit;
                break;

                case "deleteRow":
                    $idNum = $request->request->get('id');
                    $table = $request->request->get('table');

                    $sql   = $_omg->delete($table,$idNum);

                    return new Response($sql);
                    exit;
                break;

                case "showBuyer":
                    $orderId = $request->request->get('id');
                    
                    $getBuyer = $_omg->getPhoneBuyer($orderId);

                    return new Response(json_encode($getBuyer));
                    exit;
                break;

                case "addCampaign":
                    $state          = $request->request->get('country');
                    $campName       = $request->request->get('campName');
                    $recNum         = $request->request->get('recNum');
                    $senderId       = $request->request->get('senderId');
                    $sentDate       = $request->request->get('sentDate');
                    $messText       = $request->request->get('messText');
                    $upsellText     = $request->request->get('upsellText');
                    $campProduct        = $request->request->get('product');
                    $perHour        = $request->request->get('perHour');
                    $price          = $request->request->get('price');
                    $fullsku        = $request->request->get('fullsku');
                    $freeShipping   = $request->request->get('freeShipping');
                    $upsellPrice    = $request->request->get('upPrice');
                    $minForFreeShip = $request->request->get('minimalUpsell');
                    $campType       = $request->request->get('campaignType');
                    $campLink       = $request->request->get('campLink');

                    $product        = $request->request->get('addProduct');
                    $product2       = $request->request->get('product2');
                    $product3       = $request->request->get('product3');

                    $noProduct      = $request->request->get('noproduct');
                    $noproduct2     = $request->request->get('noproduct2');
                    $noproduct3     = $request->request->get('noproduct3');

                    $buyF           = $request->request->get('buyF');
                    $buyT           = $request->request->get('buyT');
                    
                    $notPayed       = $request->request->get('notPayed');
                    $refundMade     = $request->request->get('refundMade');

                   
                    $isSplit        = 0;
                    if ($campType == 1) {
                        $isSplit = 0;
                    } else if ($campType == 2) {
                        $isSplit = 1;
                    }

                    if($campLink != 'No link http://' && $campLink != ''){
                        $campLink = $campLink . $campName;
                    }

                    $selectedMessages = json_encode($request->request->get('selectedMessages'));

                    $check = $_main->checkIfExist("CampManagement", " AND `CampaignName` = '{$campName}'");

                    $messageTranslations = $request->request->get('selectedMessages');

                    $inProcess = 0;
                    $processArray = Array("inProcess"=>Array());

                    foreach ($messageTranslations AS $k=>$v){
                        $checkIfInProcess = $_main->checkIfExist("phone_order_message_translation", " AND `ID` = '{$k}' AND inProcess=1 ");
                        if (!empty($checkIfInProcess)) {
                            $inProcess = 1;
                            array_push($processArray["inProcess"], $k);
                        }
                    }

                    if (!empty($check)) {
                        return new Response("-5");
                    } else {
                        // provjera ako je neke od selektovanih poruka u procesu
                        if ($inProcess == 1){

                            return new Response(json_encode($processArray));

                        } else {
                            if($isSplit == 1 ){

                                $buyF        = '0000-00-00';
                                $buyT        = '0000-00-00';
                                $product     = 0;
                                $product2    = 0;
                                $product3    = 0;
                                $noProduct   = 0;
                                $noproduct2  = 0;
                                $noproduct3  = 0;
                                $notPayed    = 1;
                                $refundMade  = 1;
                            }
                            $writeCampaign = $_sms->writeCampaign($state, $campName, $campProduct, $recNum, $senderId, $sentDate, $messText, $upsellText, $selectedMessages, $perHour, $price, $fullsku,
                                                                  $freeShipping, $upsellPrice, $minForFreeShip,$product, $product2, $product3, $noProduct, $noproduct2, $noproduct3, $buyF, $buyT, $isSplit, $campLink,$notPayed,$refundMade);

                            // Resetovanje brojaca prevoda poruke prilikom pravljenja kampanje
                            foreach ($messageTranslations AS $key=>$val){
                                $resetMessageStop = $_sms->resetMessageStop($key);
                            }

                            array_push($processArray, $writeCampaign);
                            return new Response(json_encode($processArray));
                        }
                    }
                break;
                case "addSuppression":
                    $state = $request->request->get('country');
                    $phone = $request->request->get('phNum');

                    $writeSuppression = $_sms->writeSuppression($state,$phone);
                    return new Response($writeSuppression);

                break;
                case "unsubNumber":
                    $state = $request->request->get('state');
                    $phone = $request->request->get('number');
                    
                    $writeSuppression = $_sms->writeSuppression($state,$phone);
                    return new Response($writeSuppression);
                break;
                case "unsubMail":
                    $state      = $request->request->get('state');
                    $email      = $request->request->get('mail');
                    $pullMail   = $_omg->getSenderId($state);
                    $support    = $pullMail['distro_supportemail'];

                    //****  posalji na unsubscribe na osnovu maila
                    $unsubMail  = $_sms->unsubMail($state,$email,$support);
                    return new Response($unsubMail);
                break;
                case "checkFromPhone":
                    $state      = $request->request->get('state');
                    $phone      = $request->request->get('phone');
                    $phone      = str_replace('+', "", $phone);
                    $phone      = str_replace('-', "", $phone);

                    $areaCodes  = array("HR"=>"385", "BA"=>"387", "RS"=>"381", "MK"=>"389", "SI"=>"386", "BG"=>"359", "IT"=>"39", "SK"=>"421", "PL"=>"48", "GR"=>"30", "LV"=>"371", "LT"=>"370", "AT"=>"43", "HU"=>"36", "CZ"=>"420", "RO"=>"40", "DE"=>"49","EE"=>"372", "FR"=>"33", "BE"=>"32", "ES"=>"34", "AL"=>"355", "XK"=>"377", "VN"=>"84", "NG"=>"234");

                   // $filteredPhone  = $_sms->cleanMobile($phone,$state);            // **** filtriraj pravilan broj telefona
                    $filteredPhone  = $_sms->cleanMobile($phone,$state)->getContent();
                    if (strlen($filteredPhone) > 6) {
                        $checkFromPhone = $_omg->checkFromPhone($state, $filteredPhone); // **** Povezi broj sa kupcem
                    }
                    $checkFromPhone      = str_replace('/', "", $checkFromPhone);
                    $checkFromPhone["proizvod"] = "";
                    $checkFromPhone["kampanja"] = "";
                    $checkFromPhone["hasOutbound"] = 0;

                    if (strlen($phone) > 6){

                        $phoneToOut = $phone;
                        if (substr($phoneToOut, 0 , 1) == 0){
                            $phoneToOut = substr($phoneToOut, 1);
                        }
                        $checkIfHasOutbound = $_omg->checkIfHasOutbound($state, $phoneToOut);
                        //$checkFromPhone["info"] = $phoneToOut;
                        if ($checkIfHasOutbound) {

                            $checkFromPhone["hasOutbound"] = 1;
                            $checkFromPhone["outboundID"] = $checkIfHasOutbound[0]['id'];
                            $checkFromPhone["type"] = $checkIfHasOutbound[0]['type'];
                        }
                    }

                    $finalNumber    = $areaCodes[$state]."".$filteredPhone;

                    if ($filteredPhone > 0) {
                        $checkLastCampaign = $_omg->checkLastCampaign($finalNumber); // **** Povezi broj sa zadnjom poslanom kampanjom

                        if ($checkFromPhone) {
                            $checkFromPhone["kampanja"] = $checkLastCampaign["messageId"];

                            if (!empty($checkLastCampaign["sProizvod"])){
                                    $checkFromPhone["proizvod"] = $checkLastCampaign["sProizvod"];
                            } else {
                                    $checkFromPhone["proizvod"] = $checkLastCampaign["rProizvod"];
                            }
                        }

                     }
                    return new Response(json_encode($checkFromPhone));

                break;
                
                case "listBoughtProducts":
                    $telephone   = $request->request->get('telephone');
                    $state       = $request->request->get('state');

                    $telephone = intval($telephone);
                    //print_r($telephone);
                    $checkAllBoughtProducts = $_omg->checkBoughtProducts($telephone,$state);
                    return new Response(json_encode($checkAllBoughtProducts));
                   // print_r($checkAllBoughtProducts);die();
                break;
                
                case "getCampaignInfoByName":
                    $campaign   = $request->request->get('campaign');
                    $state      = $request->request->get('state');

                    $campType   = substr($campaign, 0, 3);

                    if ($campType == "sms"){
                        $getCampaignInfoByName = $_sms->getBulkCampaignInfoByName($campaign, $state); // Povezi broj sa kupcem iz bulk kampanje
                    } else if ($campType == "reo") {
                        $getCampaignInfoByName = $_sms->getReorderCampaignInfoByName($campaign, $state); // Povezi broj sa kupcem iz bulk kampanje
                    }


                    if ($getCampaignInfoByName > 0) {
                        return new Response(json_encode($getCampaignInfoByName));
                    } else {
                        return new Response(false);
                    }

                break;
                case "getProductPriceAndUpsell":
                    $product    = $request->request->get('product');
                    $state      = $request->request->get('state');

                    $getProductPriceAndUpsell = $_settings->getProductPriceAndUpsell($product, $state); // Povezi broj sa kupcem

                    if ($getProductPriceAndUpsell > 0) {
                        return new Response(json_encode($getProductPriceAndUpsell));
                    } else {
                        return new Response(false);
                    }

                    break;
                case "getAllProductPrices":
                    $product    = $request->request->get('product');
                    $state      = $request->request->get('state');

                    $getProductPriceAndUpsell = $_settings->getProductPriceAndUpsell($product, $state); // Povezi broj sa kupcem

                    $sveCijeneArr = Array();
                    if ($getProductPriceAndUpsell > 0) {
                        $sveCijeneArr['page'] = $getProductPriceAndUpsell;
                        //return new Response(json_encode($getProductPriceAndUpsell));
                    }

                    $getCampaignPrices = $_settings->getCampaignPrices($product, $state);
                    if ($getCampaignPrices > 0) {
                        $sveCijeneArr['sms'] = $getCampaignPrices;
                        //return new Response(json_encode($getProductPriceAndUpsell));
                    }
                    return new Response(json_encode($sveCijeneArr));

                    break;
                case "faqInstro":
                    $state      = $request->request->get('state');
                    $operater   = $request->request->get('operater');
                    $from       = "operator@";
                    $pullMail   = $_omg->getSenderId($state);
                    $to         = $pullMail['distro_supportemail'];
                    $mailText   = $request->request->get('supporttext');

                    $subject    = "Phonecall - ".$operater." - Question - ".$state;

                    $faqMail    = $_sms->faqMail($subject,$from,$to,$mailText);
                    return new Response($faqMail);
                break;
                case "changeFieldValue":
                    $table  = $request->request->get('table');
                    $id     = $request->request->get('id');
                    $field  = $request->request->get('field');
                    $value  = $request->request->get('value');
                    
                    if ($field == 'password'){
                        $value = md5($value);
                    }

                    $update = $_sms->changeFieldValue($id,$table,$field,$value);

                    if ($table == "phone_order_callCenterPrice" && $field == "inboundPrice") {
                        $fields     = " phone_order_callCenterPrice.callCenterId AS callCenterId, periods.month AS month, periods.year AS year ";
                        $joins      = " LEFT JOIN periods ON  phone_order_callCenterPrice.period = periods.id ";

                        $ctr        = $_main->getRowById($fields,$table,$id,$joins);
                        $inMonth    = $ctr['month'];
                        $inYear     = $ctr['year'];
                        $callGroup  = $ctr['callCenterId'];
                        echo json_encode("promjena:".$callGroup);
                        $inbounddb  = $_settings->getTotalInbounds($inMonth,$inYear,$callGroup);
                        $inSum      = $inbounddb['totalInbound'];
                        $inOrd      = $inbounddb['orderedNum'];

                        $perCall    = round($value / $inSum,2);
                        $perOrder   = round($value / $inOrd,2);

                        $update2 = $_sms->changeFieldValue($id,$table,"INperCall",$perCall);
                        $update3 = $_sms->changeFieldValue($id,$table,"INperOrder",$perOrder);

                    }

                    if ($table == "phone_order_callCenterPrice" && $field == "outboundPrice") {
                        $fields     = " phone_order_callCenterPrice.callCenterId AS callCenterId, periods.month AS month, periods.year AS year ";
                        $joins      = " LEFT JOIN periods ON  phone_order_callCenterPrice.period = periods.id ";

                        $ctr        = $_main->getRowById($fields,$table,$id,$joins);
                        $inMonth    = $ctr['month'];
                        $inYear     = $ctr['year'];
                        $callGroup  = $ctr['callCenterId'];

                        $outbounddb  = $_settings->getTotalOutbounds($inMonth,$inYear,$callGroup);
                        $outSum      = $outbounddb['totalOutbound'];
                        $outOrd      = $outbounddb['orderedNum'];

                        $perCall    = round($value / $outSum,2);
                        $perOrder   = round($value / $outOrd,2);
echo json_encode("promjena:".$perOrder);
                        $update2 = $_sms->changeFieldValue($id,$table,"OUTperCall",$perCall);
                        $update3 = $_sms->changeFieldValue($id,$table,"OUTperOrder",$perOrder);

                    }

                    return new Response($update);

                break;

                case "insertFieldValue":
                    $table   = $request->request->get('table');
                    $state   = $request->request->get('state');
                    $product = $request->request->get('product');
                    $field   = $request->request->get('field');
                    $value   = $request->request->get('value');

                    $insert = $_sms->insertFieldValue($table, $state, $product, $field, $value);
                    return new Response($insert);

                    break;

                case "writeCallUp":
                    $callId         = $request->request->get('id');
                    $inspectletId   = $request->request->get('inspectletId');
                    $updateCall     = $_omg->panelCallUp($callId,$inspectletId);
//                    if ($updateCall == 1) {
//                        return new Response(json_encode(false));
//                    } else {
//                        return new Response(json_encode(false));
//                    }
                    return new Response(json_encode($updateCall));
                    //return new Response($updateCall);

                break;
                case "writeCallDown":
                    $callId     = $request->request->get('id');
                    $updateCall = $_omg->panelCallDown($callId);
                    return new Response(json_encode($updateCall));

                break;
                case "addPriceIfNotExists":
                    $productId      = $request->request->get('productId');
                    $state          = $request->request->get('state');
                    $price          = $request->request->get('price');
                    $upsellPrice    = $request->request->get('upsellPrice');

                    $insertPrice = $_settings->addPriceIfNotExists($productId,$state,$price,$upsellPrice);
                    return new Response($insertPrice);

                    break;
                case "getCampaigns":
                    $State  = $request->request->get('state');

                    $getCampaigns = $_sms->getCampaigns($State);

                    return new Response(json_encode($getCampaigns));
                    exit;
                break;
                case "getCampaignInfo":
                    $campName = $request->request->get('campName');

                    $getCampaignInfo = $_sms->getCampaignInfo($campName);

                    return new Response(json_encode($getCampaignInfo));
                    exit;
                break;
                case "takeServerTime":
                    $serverTime = Date('H:i:s');

                    return new Response(json_encode((string)$serverTime));
                    exit;
                break;
                case "showPendingCalls":

                    $state = $request->request->get('state');

                    $getPendingCalls = $_omg->countOutbound($state);
                    return new Response(json_encode($getPendingCalls));
                    exit;
                break;
                case "getCampaignForSplit":

                    $idBroj = $request->request->get('id');

                    $getCampaignForSplit = $_sms->getCampaignForSplit($idBroj);
                    return new Response(json_encode($getCampaignForSplit));
                    exit;
                break;
                case "saveSplitTest":

                    $arrKampanje    = $request->request->get('campaigns');
                    $splitName      = $request->request->get('splitName');
                    $splitState     = $request->request->get('state');
                    $splitProduct   = $request->request->get('product');
                    $splitLimit     = $request->request->get('splitLimit');

                    $saveSplitTest  = $_sms->saveSplitTest($arrKampanje, $splitName, $splitState, $splitProduct, $splitLimit);

                    return new Response(json_encode($saveSplitTest));
                    exit;
                break;
                case "saveSplitTest2":

                    $arrKampanje    = $request->request->get('campaigns');
                    $splitName      = $request->request->get('splitName');
                    $splitState     = $request->request->get('state');
                    $splitProduct   = $request->request->get('splitProduct');
                    $boughtFrom     = $request->request->get('boughtFrom');
                    $boughtTo       = $request->request->get('boughtTo');

                    $product        = $request->request->get('product');
                    $product2       = $request->request->get('product2');
                    $product3       = $request->request->get('product3');

                    $noproduct      = $request->request->get('noproduct');
                    $noproduct2     = $request->request->get('noproduct2');
                    $noproduct3     = $request->request->get('noproduct3');

                    $notPayed       = $request->request->get('notPayed');
                    $refundMade     = $request->request->get('refundMade');
                    
                    //print_r($campaigns);die();

                    $saveSplitTest  = $_sms->saveSplitTest2($arrKampanje, $splitName, $splitState, $splitProduct, $boughtFrom, $boughtTo, $product, $product2, $product3, $noproduct, $noproduct2, $noproduct3, $notPayed, $refundMade);
                    $firstSaturday = date("Y-m-d", strtotime('next Saturday'));

                    return new Response($firstSaturday);
                    exit;
                break;
                case "getCustomerNumbers":
                    $campName   = $request->request->get('campName');
                    $state      = $request->request->get('state');

                    $product    = $request->request->get('product');
                    $product2   = $request->request->get('product2');
                    $product3   = $request->request->get('product3');
                    $noproduct  = $request->request->get('noproduct');
                    $noproduct2 = $request->request->get('noproduct2');
                    $noproduct3 = $request->request->get('noproduct3');

                    if ($product !== "" && !empty($product)){
                        $product1 = "documentitems.product = ".$product." ";
                    }
                    
                    if ($product2 !== "" && !empty($product2)){
                        $product2 = "OR documentitems.product = ".$product2." ";
                    }
                    
                    if ($product3 !== "" && !empty($product3)){
                        $product3 = " OR documentitems.product = ".$product3." ";
                    }
                    // EXCLUDED PRODUCTS OPTION FROM LIST

                    if ($noproduct !== "" && !empty($noproduct)){
                        $noproduct1 = "documentitems.product = ".$noproduct." ";
                    }

                    if ($noproduct2 !== "" && !empty($noproduct2)){
                        $noproduct2 = " OR documentitems.product = ".$noproduct2." ";
                    }

                    if ($noproduct3 !== "" && !empty($noproduct3)){
                        $noproduct3 = " OR documentitems.product = ".$noproduct3." ";
                    }

                    $buyF           = $request->request->get('buyF');
                    $buyT           = $request->request->get('buyT');
                    $exclude1       = $request->request->get('exclude1');
                    $exclude2       = $request->request->get('exclude2');
                    $isSplit        = $request->request->get('split');
                    $getCustNumbers = $_sms->getCustomerNumbers($campName,$state,$product1,$product2,$product3,$noproduct1,$noproduct2,$noproduct3,$buyF,$buyT,$exclude1,$exclude2,$isSplit);

                    return new Response($getCustNumbers);
                    exit;
                break;

                case "getNewFlow":
                    $state          = $request->request->get('state');

                    $doc = new DOMDocument();
                    $doc->loadHTMLFile(URL.'languages/flows/flow1.php');
                    $file =  $doc->saveHTML();

                    return new Response($file);
                    exit;
                break;

                case "getSessionData":
                    session_start();
                    $tip    = $request->request->get('sessionType');
                    $ime    = $request->request->get('sessionName');

                    if ($tip == "sms"){

                        $count = $_SESSION['phUser']['sms'];
                        return new Response($count);
                    } else {
                        return new Response(false);
                    }
                    exit;
                break;



            exit;

            case "changeProductStatus":
                session_start();

                $productId  = $request->request->get('id');
                $state      = $request->request->get('state');
                $ordType    = $request->request->get('ordType');
                $toDO       =  $request->request->get('actionToDO');
                $statusId   = $request->request->get('statusId');
                $active     = '';
                if ($toDO == 'enable' && $statusId == 0){
                    $active = 1;
                    $response = $_settings->enableDisableProductsSwitch($productId, $state, $ordType, $active);
                    return new Response($response);
                }
                if ($toDO =='disable' && $statusId == 1){
                    $active = 0;
                    $response = $_settings->enableDisableProductsSwitch($productId, $state, $ordType, $active);
                    return new Response($response);

                }
                if ($toDO =='insert' && $statusId == ''){
                    $response = $_settings->insertAndEnableOutboundProduct($productId, $state, $ordType);
                    return new Response($response);

                }

            break;

            case 'findCancelOrderInPhoneAndOMG':
                $phone      = $request->request->get('phone');
                $state      = $request->request->get('state');
                $product    = $request->request->get('product');
    
                //ciscenje broja pre provere u bazi
                $areaCodes  = array("HR"=>"385", "BA"=>"387", "RS"=>"381", "MK"=>"389", "SI"=>"386", "BG"=>"359", "IT"=>"39", "SK"=>"421", "PL"=>"48", "GR"=>"30", "LV"=>"371", "LT"=>"370", "AT"=>"43", "HU"=>"36", "CZ"=>"420", "RO"=>"40", "DE"=>"49","EE"=>"372", "FR"=>"33", "BE"=>"32", "ES"=>"34", "AL"=>"355", "XK"=>"377", "VN"=>"84", "NG"=>"234");
               
                $phone = str_replace($areaCodes[$state],'',$phone);
                $phone = intval($phone);

                $cutomerOrderInformation = $_omg->findOrderToCancel($phone,$state,$product);
                if (count($cutomerOrderInformation) == 1){
                    
                }
                
                return new Response(json_encode($cutomerOrderInformation));
                //return new Response(json_encode([]));
                
            break;
                
            case 'cancelOrderBySubmitIDinPhoneAndOmg':
                $submitId = $request->request->get('submitId');
                $cancelOrder  =$_omg->cancelOrderBySubmitIDinPhoneAndOmg($submitId);
                return new Response($cancelOrder);
            break;
            }

        }

    }


}



?>