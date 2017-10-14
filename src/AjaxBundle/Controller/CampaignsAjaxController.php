<?php
namespace AjaxBundle\Controller;
use AppBundle\Entity\Settings;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Main;
use AppBundle\Entity\SMS;

class CampaignsAjaxController extends Controller
{



    public function ajaxAction()
    {

        $conn     = $this->get('database_connection');
        $_smsIntern = new SMS($conn);
        $_main      = new Main($conn);
        $_settings  = new Settings($conn);

        $request    = Request::createFromGlobals();

        $post = $request->request->get('action');

        if (isset($post)) {
            switch($post){
                case "sendSMS":
                    $campName   = $request->request->get('ime');
                    $state      = $request->request->get('country');
                    $campNum    = $request->request->get('campName');
                    $recNum     = $request->request->get('recNum');
                    $senderID   = $request->request->get('senderId');
                    $messText   = $request->request->get('messText');
                    $parametri  = array("bulk",$state,$campName,$campNum,$recNum,$senderID,$messText);

                    $sendSMS    = "";
                    //$sendSMS  = $_sms->sendBULKsms($parametri); // Disabled da se slucajno ne aktivira slanje smsa

                    if($sendSMS){
                        return new Response("1");
                    }

                    break;

                case "checkEncode":
                    $encText = $request->request->get('encText');

                    $gsm0338 = array(
                        '@','Δ',' ','0','¡','P','¿','p',
                        '£','_','!','1','A','Q','a','q',
                        '$','Φ','"','2','B','R','b','r',
                        '¥','Γ','#','3','C','S','c','s',
                        'è','Λ','¤','4','D','T','d','t',
                        'é','Ω','%','5','E','U','e','u',
                        'ù','Π','&','6','F','V','f','v',
                        'ì','Ψ','\'','7','G','W','g','w',
                        'ò','Σ','(','8','H','X','h','x',
                        'Ç','Θ',')','9','I','Y','i','y',
                        "\n",'Ξ','*',':','J','Z','j','z',
                        'Ø',"\x1B",'+',';','K','Ä','k','ä',
                        'ø','Æ',',','<','L','Ö','l','ö',
                        "\r",'æ','-','=','M','Ñ','m','ñ',
                        'Å','ß','.','>','N','Ü','n','ü',
                        'å','É','/','?','O','§','o','à','[',']'
                    );
                    $len = mb_strlen( $encText, 'UTF-8');

                    for( $i=0; $i < $len; $i++){
                        if (!in_array(mb_substr($encText,$i,1,'UTF-8'), $gsm0338)){
                            return new Response("1");
                            exit;
                        }
                    }
                    return new Response("-1");

                    break;

                case "addMessage":
                    $message    = $request->request->get('messText');
                    //$mLength  = $request->request->get('mLength');
                    $productId  = $request->request->get('product');
                    $messType   = $request->request->get('messType');
                    $state      = $request->request->get('country');
                    $reference  = $request->request->get('refMess');
                    $mType      = $request->request->get('mType');

                    if ($messType == "new" ){
                        $writeMessage = $_smsIntern->writeMessage($message, $productId, $mType);
                    } else {
                        $writeMessage = $_smsIntern->writeTranslation($message,$state,$reference);
                    }

                    return new Response($writeMessage);

                break;
//                case "getMessageTrans":
//                    $mID                = $request->request->get('mID');
//                    $state              = $request->request->get('state');
//
//                    $getMessageTrans    = $_smsIntern->getMessageTrans($mID,$state);
//
//                    return new Response(json_encode($getMessageTrans));
//                    exit;
//                break;
                case "getMessageTrans":
                    $mID                = $request->request->get('mID');
                    $state              = $request->request->get('state');

                    $getMessageTrans         = $_smsIntern->getMessageTrans($mID,$state);
                    $getExistingSmsTranslations = $_smsIntern->getExistingSmsTranslations($mID);

                    $getMessageTrans["existing"] = Array();
                    
                    foreach ($getExistingSmsTranslations AS $ext) {
                        $getMessageTrans["existing"][$ext['state']] = $ext['TMPullBack'];
                    }

                    return new Response(json_encode($getMessageTrans));
                    exit;
                break;

                case 'sendToTranslation':

                    $state = $request->request->get('state');
                    $mID   = $request->request->get('messageId');

                    $smsTrans = $request->request->get('text');
                   // print_r($mID);die();


                    $translationsArr = Array(
                        "app" => "ph",
                        "target" => $state,
                        "text"   => array($smsTrans));

                    $requests = Array();
                    $unsentArr = Array();
                    foreach ($state AS $key => $st) {
                        $getSmsId = $_smsIntern->getSmsText(" AND state = '{$st}' AND phone_order_message_translation.messageID = {$mID} LIMIT 1 ");
                        $smsTranslID = $getSmsId[0]['id'];


                        if ($smsTranslID == 0 || empty($smsTranslID)) {
                            $insertSms = $_smsIntern->insertNewSmsTransField($st, $mID);
                            $smsTranslID = $insertSms;
                        }

                        $checkIfPullMade = $_main->checkIfExist("phone_order_TM", " AND `smsID`={$smsTranslID} AND TMPullBack = 0 ");

                        if ($checkIfPullMade) {
                            unset($translationsArr["target"][$key]);
                            array_push($unsentArr, $st);
                        }


                        $requests[$st] = $smsTranslID;
                    }
                    
                    $sendToTM = $_settings->sendToTM($translationsArr);
                    $obj = json_decode($sendToTM);
                    $reqId = $obj->requestID;
                    //$reqId = 123; // testni request broj .... da zahtjev ne ide na TM (uz zakomentarisane linije iznad)
                   // print_r($reqId);die();
                    if ($reqId > 0) {
                        foreach ($state AS $sta) {

                            $smsID = $requests[$sta];
                            if (in_array($sta, $translationsArr["target"])) {
                                $insertTMRequest = $_settings->insertSmsTMRequest($smsID, $reqId);
                            }
                        }
                        $statusBack = Array("statusnum"=>"0", "stvalue"=>0);
                        if (empty($unsentArr)) {
                            $statusBack["statusnum"] = "1";
                            $statusBack["stvalue"] = $reqId;
                            return new Response(json_encode($statusBack));
                        } else {
                            $unsentStr = implode(",", $unsentArr);

                            $statusBack["statusnum"] = "-3";
                            $statusBack["stvalue"] = $unsentStr;
                            return new Response(json_encode($statusBack));
                        }


                    } else {
                        return new Response('false');
                    }

                break;

                case 'getTMtranslation':

                    $state = $request->request->get('state');
                    $product = $request->request->get('product');
                    $mID   = $request->request->get('messageId');

                    $getTMRequestId = $_smsIntern->getSmsTMRequestId($state, $mID);

                    $st = $_smsIntern->getSourceSmsTranslation($mID,$state);
                  

                    $nizPrevoda = Array(
                        "translation" => $st["translation"],
                    );

                    //print_r($nizPrevoda);
//                    print_r($st);
//                    print_r($nizPrevoda);
//                    print_r($nizPrevoda['text']);
//                    $kme = $nizPrevoda['text'];
                    $nizAll = Array();
                   
                    if ($getTMRequestId[0]["TMid"] == true && $getTMRequestId[0]["TMid"] != "" && !empty($getTMRequestId[0]["TMid"])) {

                        $getTMtranslation = $_settings->getTMtranslation($getTMRequestId[0]["TMid"]);
                     
                
                        if ($getTMtranslation) {
                       //print_r($getTMtranslation);
                            $convert = json_decode($getTMtranslation);
                            foreach ($nizPrevoda AS $k => $v) {
                                if ($v != "" && !empty($v) && $v != null) {
                                    $nizAll[$k] = $convert->$v->$state->text;
                                    if ($nizAll[$k] != null) {
                                        $translated = true;
                                    }
                                }
                            }
                            if ($translated == true) {
                                $setPullTM = $_settings->setPullTM($getTMRequestId[0]["maxId"]);
                                $encodedArr = json_encode($nizAll);
                                return new Response($encodedArr);
                            } else {
                                return new Response("-5");
                            }

                            
                        } else {
                            return new Response(false);
                        }
                    } else {
                        return new Response("-3");
                    }

                    break;
                
                case "updateMessageTrans":
                    $mID                = $request->request->get('mID');
                    $state              = $request->request->get('state');
                    $messText           = $request->request->get('messageText');

                    $updateMessageTrans = $_smsIntern->updateMessageTrans($mID,$state,$messText);

                    return new Response($updateMessageTrans);
                break;
                case "sendTestMessage":
                    $number             = $request->request->get('number');
                    //$state            = $request->request->get('state');
                    $messText           = $request->request->get('message');

                    $sendTestMessage    = $_smsIntern->sendDirectSMS("Test", "TestSMS", $number, $messText);
                    return $sendTestMessage;
                    break;
                case "newMessageTrans":
                    $mID                = $request->request->get('mID');
                    $state              = $request->request->get('state');
                    $messText           = $request->request->get('messageText');

                    $newMessageTrans    = $_smsIntern->newMessageTrans($mID,$state,$messText);

                    return new Response($newMessageTrans);
                break;
                case "getMessageList":
                    $state      = $request->request->get('state');
                    $product    = $request->request->get('product');

                    $getMessageTranslationList = $_smsIntern->getMessageTranslationList($state,$product);

                    return new Response(json_encode($getMessageTranslationList));
                break;
                case "getSecondMessageList":
                    $state      = $request->request->get('state');
                    $product    = $request->request->get('product');

                    $getMessageTranslationList = $_smsIntern->getSecondMessageTranslationList($state,$product);

                    return new Response(json_encode($getMessageTranslationList));
                    break;
                case "addMessageByCampaign":
                    $state          = $request->request->get('state');
                    $product        = $request->request->get('product');
                    $message        = $request->request->get('message');
                    $position       = $request->request->get('position');

                    $addNewMessage  = $_smsIntern->addMessageByCampaign($state,$product,$message,$position);

                    return $addNewMessage;
                    break;
                case "showCampaignStats":
                    $cId            = $request->request->get('cId');
                    $showStats      = $_smsIntern->showCampaignStats($cId);

                    return new Response(json_encode($showStats));
                    break;
                case "showReorderCampaignStats":
                    $cId            = $request->request->get('cId');
                    $showStats      = $_smsIntern->showReorderCampaignStats($cId);

                    return new Response(json_encode($showStats));
                    break;
                case "bounceUnsubscribe":
                    $phNumbers            = $request->request->get('numbers');
                    $phNumbersArr         = json_decode($phNumbers);

                    foreach ($phNumbersArr AS $key=>$value){

                        $filteredPhone = $_smsIntern->cleanMobile($key,$value);
                        // echo $filteredPhone." - ".$value;
                        $unsubscribeNumbers = $_smsIntern->writeSuppression($value, $filteredPhone, "1");
                    }
                    return new Response(true);
                    break;
                case "getLastCampaignPrice":
                    $state      = $request->request->get('state');
                    $product    = $request->request->get('product');

                    $getLastCampaignPrice = $_smsIntern->getLastCampaignPrice($state,$product);

                    return new Response(json_encode($getLastCampaignPrice));
                break;

                case "getCampainLink":
                    $stateId = $_POST['state_id'];
                    $productId = $_POST['product_id'];

                    $reorderLink_check = $_main->checkIfExist('phone_order_reorder_links', " AND `state_id`='$stateId' AND `product_id`='$productId'");

                    if ($reorderLink_check) {
                        $link = $reorderLink_check[0]["link"];
                        return new Response(json_encode($reorderLink_check[0]['link']));
                        exit;
                    } else {
                        $return = 'No link http://';
                        return new Response(json_encode($return));
                        exit;
                    }
                    
                    break;
                case "addReorderCampaign":
                    $state          = $request->request->get('country');
                    $campName       = $request->request->get('campName');
                    $afterDays      = $request->request->get('afterDays');
                    $dayHour        = $request->request->get('hoursH').":".$request->request->get('hoursM');
                    $sentDate       = $request->request->get('sentDate');
                    $messText       = $request->request->get('endMessageBox');
                    $product        = $request->request->get('product');
                    $active         = $request->request->get('activeSel');
                    $selectedM      = $request->request->get('selectedmessage');
                    $price          = $request->request->get('price');
                    $freeShipping   = $request->request->get('freeShipping');
                    $upsellPrice    = $request->request->get('upPrice');
                    $minForFreeShip = $request->request->get('minimalUpsell');

                    $result         = $_main->checkIfExist('phone_order_reorder', " AND `CampaignName`='$campName'");

                    if($result) { return new Response(json_encode("-5")); }
                    else {
                        $writeCampaign  = $_smsIntern->writeReorderCampaign($state,$campName,$product,$afterDays,$dayHour,$sentDate,$messText,$active,$selectedM, $price, $freeShipping, $upsellPrice, $minForFreeShip);

                        return new Response($writeCampaign); }
                break;

                case 'showMessageTranslation':

                    $state      = $request->request->get('state');
                    $message_id = $request->request->get('msg');

                    if($state && $message_id) {
                        $translation = $_smsIntern->getMessageByState($state, $message_id);
                        return new Response(json_encode($translation));
                    }
                    else return new Response(json_encode('0'));
                break;

                case 'setWinner':

                    $campaign           = $request->request->get('campaign');
                    $recipients         = $request->request->get('recipientNum');
                    $totalRecipients    = $request->request->get('recipientTotalNum');

                    $setWinner = $_smsIntern->setWinner($campaign, $recipients, $totalRecipients);

                    return new Response(json_encode($setWinner));

                break;

                case 'getShortURL':

                    $url           = $request->request->get('longURL');

                    $getShortURL = $_smsIntern->getShortURL($url);

                    return new Response('');

                break;

            exit;
            }

        }



    }


}



?>