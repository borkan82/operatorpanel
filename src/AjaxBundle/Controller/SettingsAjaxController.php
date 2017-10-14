<?php
namespace AjaxBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Main;
use AppBundle\Entity\Settings;
use AppBundle\Entity\OMG;

class SettingsAjaxController extends Controller
{


    public function ajaxAction()
    {

        $conn       = $this->get('database_connection');
        $_settings  = new Settings($conn);
        $_main      = new Main($conn);
        $_omg       = new OMG($conn);

        $request    = Request::createFromGlobals();

        $post = $request->request->get('action');

        if (isset($post)) {

            switch ($post) {
                case "addUser":
                    $state = $request->request->get('country');
                    $name = $request->request->get('name');
                    $surname = $request->request->get('surname');
                    $username = $request->request->get('username');
                    $email = $request->request->get('email');
                    $fullname = $name . " " . $surname;
                    $password = $request->request->get('password');
                    $role = $request->request->get('role');
                    $status = $request->request->get('active');
                    $group = $request->request->get('operatorGroup');

                    $user_chk = $_main->checkIfExist('phone_order_users', " AND `username`='$username'");

                    if ($user_chk) {
                        return new Response(json_encode("-1"));
                    } else {
                        $writeUser = $_settings->writeUser($state, $name, $surname, $email, $fullname, $username, $password, $role, $status, $group);

                        return new Response($writeUser);
                    }
                    break;
                case "addCenter":
                    $state = $request->request->get('country');
                    $name = $request->request->get('name');
                    $pagephone = $request->request->get('pagephone');
                    $phone = $request->request->get('phone');
                    $reorderphone = $request->request->get('reorderphone');
                    $email = $request->request->get('email');

                    $writeCenter = $_settings->addCenter($state, $name, $pagephone, $phone, $reorderphone, $email);

                    return new Response($writeCenter);

                    break;

                case "getTextTrans":
                    $pID = $request->request->get('pID');
                    $state = $request->request->get('state');

                    $getTextTrans = $_settings->getTextTrans($pID, $state);

                    $getExistingTranslations = $_settings->getExistingTranslations($pID);


                    $getTextTrans["existing"] = Array();
                    $getTextTrans["existing"] = Array();

                    foreach ($getExistingTranslations AS $ext) {
                        $getTextTrans["existing"][$ext['state']] = $ext['TMPullBack'];
                    }


                    ob_start('htmlspecialchars');
                    return new Response(json_encode($getTextTrans));
                    exit;
                    break;
                case "updateTextTrans":
                    $pID = $request->request->get('pID');
                    $state = $request->request->get('state');
                    $prodText = nl2br($request->request->get('productText'));
                    $outQ = nl2br($request->request->get('outQ'));
                    $outQ1 = nl2br($request->request->get('outQ1'));
                    $outQ2 = nl2br($request->request->get('outQ2'));
                    $outQ3 = nl2br($request->request->get('outQ3'));
                    $outA1 = nl2br($request->request->get('outA1'));
                    $outA2 = nl2br($request->request->get('outA2'));
                    $outA3 = nl2br($request->request->get('outA3'));
                    $outDQ1 = nl2br($request->request->get('outDQ1'));
                    $outDQ2 = nl2br($request->request->get('outDQ2'));
                    $outDQ3 = nl2br($request->request->get('outDQ3'));
                    $outDQ4 = nl2br($request->request->get('outDQ4'));
                    $outDQ5 = nl2br($request->request->get('outDQ5'));
                    $outAQ1 = nl2br($request->request->get('outAQ1'));
                    $outAQ2 = nl2br($request->request->get('outAQ2'));
                    $outAQ3 = nl2br($request->request->get('outAQ3'));
                    $outAQ4 = nl2br($request->request->get('outAQ4'));
                    $outAQ5 = nl2br($request->request->get('outAQ5'));

                    $updateTextTrans = $_settings->updateTextTrans($pID, $state, $prodText, $outQ, $outQ1, $outQ2, $outQ3, $outA1, $outA2, $outA3, $outDQ1,
                        $outDQ2, $outDQ3, $outDQ4, $outDQ5, $outAQ1, $outAQ2, $outAQ3, $outAQ4, $outAQ5);

                    return new Response($updateTextTrans);
                    break;
                case "newTextTrans":
                    $pID = $request->request->get('pID');
                    $state = $request->request->get('state');
                    $prodText = nl2br($request->request->get('productText'));
                    $outQ  = nl2br($request->request->get('outQ'));
                    $outQ1 = nl2br($request->request->get('outQ1'));
                    $outQ2 = nl2br($request->request->get('outQ2'));
                    $outQ3 = nl2br($request->request->get('outQ3'));
                    $outA1 = nl2br($request->request->get('outA1'));
                    $outA2 = nl2br($request->request->get('outA2'));
                    $outA3 = nl2br($request->request->get('outA3'));
                    $outDQ1 = nl2br($request->request->get('outDQ1'));
                    $outDQ2 = nl2br($request->request->get('outDQ2'));
                    $outDQ3 = nl2br($request->request->get('outDQ3'));
                    $outDQ4 = nl2br($request->request->get('outDQ4'));
                    $outDQ5 = nl2br($request->request->get('outDQ5'));
                    $outAQ1 = nl2br($request->request->get('outAQ1'));
                    $outAQ2 = nl2br($request->request->get('outAQ2'));
                    $outAQ3 = nl2br($request->request->get('outAQ3'));
                    $outAQ4 = nl2br($request->request->get('outAQ4'));
                    $outAQ5 = nl2br($request->request->get('outAQ5'));

                    $newProductTrans = $_settings->newTextTrans($pID, $state, $prodText, $outQ, $outQ1, $outQ2, $outQ3, $outA1, $outA2, $outA3, $outDQ1,
                        $outDQ2, $outDQ3, $outDQ4, $outDQ5, $outAQ1, $outAQ2, $outAQ3, $outAQ4, $outAQ5);

                    return new Response($newProductTrans);
                    break;
                case "addProductText":
                    $state = $request->request->get('country');
                    $product = $request->request->get('product');
                    $productText = nl2br($request->request->get('productText'));

                    $check = $_main->checkIfExist("productDescription", " AND `state` = '{$state}' AND `productId` = {$product} ");

                    if (!empty($check)) {
                        return new Response("-1");
                    } else {

                        $writeText = $_settings->writeText($state, $product, $productText);

                        return new Response($writeUser);
                    }

                    break;

                case 'search':

                    $phrase = $request->request->get('phrase');

                    $result = $_settings->searchProducts($phrase);

                    if ($result) {
                        return new Response(json_encode($result));
                    } else return new Response(false);

                    break;

                case 'sendToTranslation':

                    $state = $request->request->get('state');
                    $product = $request->request->get('product');

                    $fOutP = $request->request->get('fOutP');
                    $foutQ = $request->request->get('foutQ');
                    $foutQ1 = $request->request->get('foutQ1');
                    $foutQ2 = $request->request->get('foutQ2');
                    $foutQ3 = $request->request->get('foutQ3');
                    $foutA1 = $request->request->get('foutA1');
                    $foutA2 = $request->request->get('foutA2');
                    $foutA3 = $request->request->get('foutA3');
                    $foutDQ1 = $request->request->get('foutDQ1');
                    $foutDQ2 = $request->request->get('foutDQ2');
                    $foutDQ3 = $request->request->get('foutDQ3');
                    $foutDQ4 = $request->request->get('foutDQ4');
                    $foutDQ5 = $request->request->get('foutDQ5');
                    $foutAQ1 = $request->request->get('foutAQ1');
                    $foutAQ2 = $request->request->get('foutAQ2');
                    $foutAQ3 = $request->request->get('foutAQ3');
                    $foutAQ4 = $request->request->get('foutAQ4');
                    $foutAQ5 = $request->request->get('foutAQ5');


                    $translationsArr = Array("app" => "ph",
                                             "target" => $state,
                                             "text" => Array(
                                                 $fOutP,
                                                 $foutQ,
                                                 $foutQ1,
                                                 $foutQ2,
                                                 $foutQ3,
                                                 $foutA1,
                                                 $foutA2,
                                                 $foutA3,
                                                 $foutDQ1,
                                                 $foutDQ2,
                                                 $foutDQ3,
                                                 $foutDQ4,
                                                 $foutDQ5,
                                                 $foutAQ1,
                                                 $foutAQ2,
                                                 $foutAQ3,
                                                 $foutAQ4,
                                                 $foutAQ5
                                             ));

                    $selectedArr = Array();
                    foreach ($translationsArr["text"] AS $k => $v) {
                        if (is_int($k) && strlen($v) > 0) {
                            array_push($selectedArr, $k);
                        }
                    }

                    foreach ($translationsArr["text"] AS $k => $v) {
                        if (is_null($k) || $v == null) {
                            unset($translationsArr["text"][$k]);
                        }
                    }

                    $selectedJSON = json_encode($selectedArr);

                    $requests = Array();
                    $unsentArr = Array();
                    foreach ($state AS $key => $st) {
                        $getDescId = $_settings->getProductText(" AND state = '{$st}' AND productId = {$product} LIMIT 1 ");
                        $descId = $getDescId[0]['id'];

                        if ($descId == 0 || empty($descId)) {
                            $insertNewDesc = $_settings->insertNewDesc($st, $product);
                            $descId = $insertNewDesc;
                        }

                        $checkIfPullMade = $_main->checkIfExist("phone_order_TM", " AND `descID`={$descId} AND TMPullBack = 0 ");

                        if ($checkIfPullMade) {
                            unset($translationsArr["target"][$key]);
                            array_push($unsentArr, $st);
                        }


                        $requests[$st] = $descId;
                    }

                    $sendToTM = $_settings->sendToTM($translationsArr);
                    $obj = json_decode($sendToTM);
                    $reqId = $obj->requestID;
                    //$reqId = 123; // testni request broj .... da zahtjev ne ide na TM (uz zakomentarisane linije iznad)

                    if ($reqId > 0) {
                        foreach ($state AS $sta) {

                            $descriptionID = $requests[$sta];
                            if (in_array($sta, $translationsArr["target"])) {
                                $insertTMRequest = $_settings->insertTMRequest($descriptionID, $reqId, $selectedJSON);
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
                        return new Response(false);
                    }

                    break;

                case 'getTMtranslation':

                    $state = $request->request->get('state');
                    $product = $request->request->get('product');

                    $getTMRequestId = $_settings->getTMRequestId($state, $product);
                    //print_r($getTMRequestId);

                    $st = $_settings->getSourceTranslation($product);
                   
                  
                    $nizPrevoda = Array("outP" => $st["productText"],
                        "outQ" => $st["outQ"],
                        "outQ1" => $st["outQ1"],
                        "outQ2" => $st["outQ2"],
                        "outQ3" => $st["outQ3"],
                        "outA1" => $st["outA1"],
                        "outA2" => $st["outA2"],
                        "outA3" => $st["outA3"],
                        "outDQ1" => $st["outDQ1"],
                        "outDQ2" => $st["outDQ2"],
                        "outDQ3" => $st["outDQ3"],
                        "outDQ4" => $st["outDQ4"],
                        "outDQ5" => $st["outDQ5"],
                        "outAQ1" => $st["outAQ1"],
                        "outAQ2" => $st["outAQ2"],
                        "outAQ3" => $st["outAQ3"],
                        "outAQ4" => $st["outAQ4"],
                        "outAQ5" => $st["outAQ5"]
                    );

                    //print_r($nizPrevoda);

                    $nizAll = Array();
                    $kljuc = $st["outA2"];
        //return new Response(json_encode($getTMRequestId[0]["TMid"]));

                    if ($getTMRequestId[0]["TMid"] == true && $getTMRequestId[0]["TMid"] != "" && !empty($getTMRequestId[0]["TMid"])) {

                        $getTMtranslation = $_settings->getTMtranslation($getTMRequestId[0]["TMid"]);
                        //print_r($getTMtranslation);
                     
                        if ($getTMtranslation) {

                            $convert = json_decode($getTMtranslation);
                          
        //return new Response(json_encode($nizPrevoda['outP']));
        //return new Response("</br>");
        //return new Response(json_encode($convert));exit;
                            $translated = false;
                            foreach ($nizPrevoda AS $k => $v) {
                                if ($v != "" && !empty($v) && $v != null) {
                                    $nizAll[$k] = $convert->$v->$state->text;
                                   //return new Response(json_encode($convert));exit;
                                    if ($nizAll[$k] != null) {
                                        $translated = true;
                                    }
                                }
                            }

                            if ($translated == true) {
                                $setPullTM = $_settings->setPullTM($getTMRequestId[0]["maxId"]);
                                $encodedArr = json_encode($nizAll);
                            } else {
                                return new Response("-5");
                            }

                            return new Response($encodedArr);
                        } else {
                            return new Response(false);
                        }
                    } else {
                        return new Response("-3");
                    }

                    break;

                case 'sendSmsToTranslation':

                    $state = $request->request->get('state');
                    $product = $request->request->get('product');

                    $smsText = $request->request->get('text');
                   


                    $translationsArr = Array("app" => "ph",
                        "target" => $state,
                        "text" => $smsText
                    );

                    $selectedArr = Array();
                    foreach ($translationsArr["text"] AS $k => $v) {
                        if (is_int($k) && strlen($v) > 0) {
                            array_push($selectedArr, $k);
                        }
                    }

                    foreach ($translationsArr["text"] AS $k => $v) {
                        if (is_null($k) || $v == null) {
                            unset($translationsArr["text"][$k]);
                        }
                    }

                    $selectedJSON = json_encode($selectedArr);

                    $requests = Array();
                    $unsentArr = Array();
                    foreach ($state AS $key => $st) {
                        $getDescId = $_settings->getProductText(" AND state = '{$st}' AND productId = {$product} LIMIT 1 ");
                        $descId = $getDescId[0]['id'];

                        if ($descId == 0 || empty($descId)) {
                            $insertNewDesc = $_settings->insertNewDesc($st, $product);
                            $descId = $insertNewDesc;
                        }

                        $checkIfPullMade = $_main->checkIfExist("phone_order_TM", " AND `descID`={$descId} AND TMPullBack = 0 ");

                        if ($checkIfPullMade) {
                            unset($translationsArr["target"][$key]);
                            array_push($unsentArr, $st);
                        }


                        $requests[$st] = $descId;
                    }

                    $sendToTM = $_settings->sendToTM($translationsArr);
                    $obj = json_decode($sendToTM);
                    $reqId = $obj->requestID;
                    //$reqId = 123; // testni request broj .... da zahtjev ne ide na TM (uz zakomentarisane linije iznad)

                    if ($reqId > 0) {
                        foreach ($state AS $sta) {

                            $descriptionID = $requests[$sta];
                            if (in_array($sta, $translationsArr["target"])) {
                                $insertTMRequest = $_settings->insertTMRequest($descriptionID, $reqId, $selectedJSON);
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
                        return new Response(false);
                    }

                    break;

                case 'searchCountryAndRole':

                    $state = $request->request->get('state');
                    $role = $request->request->get('role');

                    $result = $_settings->searchStateAndRole($state, $role);

                    if ($result) return new Response(json_encode($result));
                    else return new Response(false);

                    break;
               

                    exit;

                case "editProductLink":

                    $stateId   = $request->request->get('stateId');
                    $productId      = $request->request->get('productId');
                    $link    = $request->request->get('editLInk');
                    
                    
                    
                    $stateId = $_POST['stateId'];
                    $productId = $_POST['productId'];
                    $link= $_POST['editLInk'];

                    $reorderLink_check = $_main->checkIfExist('phone_order_reorder_links', " AND `state_id`='$stateId' AND `product_id`='$productId'");

                    if (!$reorderLink_check) {
                        return new Response("-1");

                    } else {
                        $editReorderLink = $_settings->updateReorderLink( $stateId,$productId, $link);
                        var_dump($editReorderLink);

                        return new Response('proslo');
                    }
                    break;

                case "addProductLink":
                    $state = $_POST['state_id'];
                    $product = $_POST['product'];
                    $link= $_POST['link'];

                    $reorderLink_check = $_main->checkIfExist('phone_order_reorder_links', " AND `state_id`='$state' AND `product_id`='$product'");

                    if ($reorderLink_check) {
                        return new Response("-1");
                    } else {
                        $writeReorderLink = $_settings->writeReorderLink($state,$product, $link);

                        var_dump ($writeReorderLink);
                        return new Response('proslo');
                    }
                    break;
                case "changeProductStatus":
                    session_start();

                    $productId = $_POST['id'];
                    $state = $_POST['state'];
                    $ordType = $_POST['ordType'];
                    $toDO =  $_POST['actionToDO'];
                    $statusId = $_POST['statusId'];
                    $active = '';
                    $response='';
                    if ($toDO == 'enable' && $statusId == 0){
                        $active = 1;
                        $enable = $_settings->enableDisableProductsSwitch($productId, $state, $ordType, $active);
                        $response = new Response(json_encode(array(
                            "enable" =>  $enable
                        )));

                       // return new Response(true);
                    }
                    if ($toDO =='disable' && $statusId == 1){
                        $active = 0;
                        $disable = $_settings->enableDisableProductsSwitch($productId, $state, $ordType, $active);
                        $response = new Response(json_encode(array(
                            "disable" =>  $disable
                        )));
                       // return new Response(true);

                    }
                    if ($toDO =='insert' && $statusId == ''){
                        $insert = $_settings->insertAndEnableOutboundProduct($productId, $state, $ordType);
                        $response = new Response(json_encode(array(
                            "insert" =>  $insert
                        )));
                        //return new Response(true);

                    }
                    //$response->headers->set('Content-Type', 'application/json');

                    return $response;

                    break;

                case "addProfileToProduct":
                    $state = $_POST['state'];
                    $productsJson = $_POST['products'];
                    $profile = $_POST['profile'];
                
                    $products = json_decode($productsJson);
                  
                    foreach ($products as $inserProd){
                      
                        $profile_check = $_main->checkIfExist('phone_order_productProfiles', " AND `state`='$state' AND `product_id`='$inserProd'");
                        if ($profile_check) {
                            $updateExistingProfile = $_settings->updateProductProfile($profile_check[0]['id'],$profile);
                        } else{
                            $writeProfile = $_settings->writeProductProfile($state, $inserProd, $profile);
                           
                        }
                        
                    }
                     
                    return new Response('proslo');

                    break;
                
                case "setUserDataForMainOutboundPanel":
                   
                    $userId = $_POST['id'];
                    
                    $now = date("Y-m-d H:i:s");
                    $toCallTime = date('Y-m-d H:i:s', strtotime($now . ' -1 hour'));
                   
                    $user_check = $_main->checkIfExist('phone_order_outbound', " AND `id`='$userId'");
                    
                    if ($user_check) {
                        $user_update = $_settings->setUserDataForMainOutboundPanel($userId, $toCallTime);

                        return new Response('proslo');
                    } else{
//                        $writeProfile = $_settings->writeProductProfile($state, $inserProd, $profile);
                        return new Response('error');
                    }

                    break;

                case "checkProductsProfile":
                    $state = $_POST['state'];
                    $productsJson = $_POST['products'];
                    $profile = $_POST['profile'];

                    $getProducts = $_omg->getSimpleProductList();
                    $allProducts = array();
                    foreach ($getProducts as $prdc){
                        $allProducts[$prdc['id']] = $prdc['title'];
                    }
                    $products = json_decode($productsJson);

                    $response = array();
                    if (count($products)>0){

                        foreach ($products as $inserProd){

                            $profile_check = $_main->checkIfExist('phone_order_productProfiles', " AND `state`='$state' AND `product_id`='$inserProd'");
                            if ($profile_check) {
                                $response['update'][$allProducts[$inserProd]]['old'] = $profile_check[0]['profile'];
                                $response['update'][$allProducts[$inserProd]]['new'] = $profile;

                            } else{
                                $response['insert'][$allProducts[$inserProd]] = $profile;
                            }
                        }
                    } 

                    return new Response(json_encode($response));

                    break;
            }


        }


    }


}



?>