<?php
namespace AjaxBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Outbound;
use AppBundle\Entity\Main;
use AppBundle\Entity\SMS;

class MainOutboundAjaxController extends Controller
{


    public function ajaxAction()
    {

        $conn = $this->get('database_connection');
      
        $_outbound  = new Outbound($conn);
        $_sms       = new SMS($conn);
        $_main       = new Main($conn);

        $request = Request::createFromGlobals();

        $post = $request->request->get('action');

        if (isset($post)) {
            switch($_POST['action']){
                case "getNewSubmits":

                    $stateQ     = $_POST["state"];
                    $state      = " and order_submits.country = '$stateQ'";
                    $highestId  = $_POST['hId']; // trazenje po ID-u ordera vecem od onih koji su zadnji stigli

                    $getSubmits = $_outbound->getOrderSubmits($state,$highestId);

                    echo json_encode($getSubmits);

                    break;

                case "makeSubmitUpsell":
                    $submitId = $_POST["sId"];

                    $makeUpsell = $_outbound->makeSubmitUpsell($submitId);
                    echo "90909090";

                    break;

                case 'nowork':
                    $today      = date("Y-m-d H:i");
                    $new_time   = "- - -";
                    $akcija     = "errorphone";
                    $redBr      = $_POST['redBr'];
                    $nowork     = $_outbound->changeOrder($today,$new_time,$akcija,$redBr);

                    echo $new_time;
                    exit();
                    break;

                case 'noanswer':
                    $today      = date("Y-m-d H:i");
                    $new_time   = date("Y-m-d H:i", strtotime('+2 hours'));
                    $akcija     = "errornoanswer";
                    $redBr      = $_POST['redBr'];
                    $noanswer   = $_outbound->changeOrder($today,$new_time,$akcija,$redBr);

                    echo json_encode(array("time1" => $today,"time2"=> $new_time));

                    exit();
                    break;

                case 'cancelname':
                    $today      = date("Y-m-d H:i");
                    $new_time   = "- - -";
                    $akcija     = "errorname";
                    $redBr      = $_POST['redBr'];
                    $cancelname = $_outbound->changeOrder($today,$new_time,$akcija,$redBr);

                    echo $new_time;
                    exit();
                    break;

                case 'decline':
                    $today      = date("Y-m-d H:i");
                    $new_time   = "- - -";
                    $akcija     = "errornoorder";
                    $redBr      = $_POST['redBr'];
                    $decline    = $_outbound->changeOrder($today,$new_time,$akcija,$redBr);

                    echo $new_time;
                    exit();
                    break;

                case 'confirm':
                    $today      = date("Y-m-d H:i");
                    $new_time   = "- - -";
                    $akcija     = "confirmed";
                    $redBr      = $_POST['redBr'];
                    $confirm    = $_outbound->changeOrder($today,$new_time,$akcija,$redBr);

                    echo $new_time;
                    exit();
                    break;

                case 'updateComment':
                    $komentar   = $_POST['comment'];
                    $redBr      = $_POST['redBr'];
                    $confirm    = $_outbound->updateComment($komentar,$redBr);

                    echo "OK";
                    exit();
                    break;

                case 'changeOutboundFlag':
                    $called      = date("Y-m-d H:i:s");
                    $tocall      = date("Y-m-d H:i:s", strtotime('+2 hours'));

                    $redBr      = $_POST['id'];
                    $table      = $_POST['table'];
                    $value      = $_POST['value'];
                    $submit     = $_POST['submit'];
                    $ouid       = $_POST['ouid']; // Operator ID u slucaju da dolazi sa stranice
                    $newPrice   = $_POST['newPrice'];
                    $validate   = $_POST['validation'];
                    $callerPhone= $_POST['phonenum'];
                    $state      = $_POST['state'];
                    $source     = $_POST['source']; // Ako je bio cancel koji je razlog cancelovanja

                    $getWorkingHours = $_outbound->getWorkingHours($state);

                    $endWorking 	= strtotime(date("Y-m-d ".$getWorkingHours["wtTo"].":00:00"));
                    $toCallUnix		= strtotime($tocall);

                    if ($toCallUnix > $endWorking) {
                        $weekDay 	= date("w", $toCallUnix);
                        if ($weekDay == 5){
                            $tocall      = date("Y-m-d ".$getWorkingHours["wtFrom"].":i:s", strtotime('+3 days'));

                        } else {
                            $tocall      = date("Y-m-d ".$getWorkingHours["wtFrom"].":i:s", strtotime('+1 days'));
                        }

                    }

                    $updateRow    = $_outbound->changeOutboundFlag($called,$tocall,$redBr,$table,$value,$submit,$ouid,$newPrice);

                    if (($value == 6) && strlen($callerPhone) > 7){
                        $validate       = 1;
                        $insertRow      = $_outbound->insertValidate($callerPhone,$validate,$state,$source);

                        $filteredPhone = $_sms->cleanMobile($callerPhone,$state);
                        if (strlen($filteredPhone) > 6) {

                            $checkIfExists = $_main->checkIfExist("suppressionList", " AND state = '{$state}' AND number = '{$filteredPhone}' ");

                            if ($checkIfExists == false) {
                                $insertSup = $_sms->writeSuppression($state, $filteredPhone, 0);
                            }
                        }
                    }

                    echo "1";
                    exit();
                    break;

                case 'changeTimeToCall':
                    $timeToCall = $_POST['timeVal'];
                    $redBr      = $_POST['recId'];

                    $updateTime    = $_outbound->changeTimeToCall($redBr,$timeToCall, "Call postponed for later");

                    echo "1";
                    exit();
                    break;

                case 'changeOMGcomment':
                    $upsellQuery   = "";
                    $submitId      = $_POST['submitId'];
                    $recordId      = $_POST['recordId'];
                    $cType         = $_POST['cType'];

                    $selectOMGorder = $_outbound->selectOMGOrderForUpdate($submitId);

                    if(count($selectOMGorder)>0) {
                        $post_data	= (array)json_decode($selectOMGorder['post_data']);

                        // NOVO SETOVANJE JSONA
                        if ($cType == "1"){
                            $post_data['comment']  .= " [PHN][ORDER CANCELED] ";
                        } else if ($cType == "2"){
                            $post_data['comment']  .= " [PHN][Not verified] ";
                        }

                        $new_data= addslashes(json_encode($post_data,JSON_UNESCAPED_UNICODE));

                        $changeOMGorder = $_outbound->changeOMGorder($submitId, $recordId, $new_data, 6);

                        echo "1";

                    } else {

                        echo "0";
                    }

                    exit();
                    break;

                case 'changeOrderStatus':
                    $submitId      = $_POST['submitId'];
                    $recordId      = $_POST['recordId'];
                    $status        = $_POST['status'];
                    $formPrice     = 0.00;

                    if($status == 1 || $status == 2) {

                        if ($status == 1){
                            $OMGstatus = 8;
                            $OUTstatus = 6;
                            $OMGcomment = "[OUT] Order Canceled";
                        } else if ($status == 2){
                            $OMGstatus = 6;
                            $OUTstatus = 7;
                            $OMGcomment = "[OUT] Cancel Accept";
                            $formPrice     = $_POST['formPrice'];
                        }

                        $changeOrderStatus = $_outbound->changeOrderStatus($submitId, $recordId, $OUTstatus, $OMGstatus, $OMGcomment, $formPrice);

                        echo "1";

                    } else {

                        echo "0";
                    }

                    exit();
                    break;

                case "updateCallersData":

                    $id        = $_POST['callerId'];
                    $name      = $_POST['name'];
                    $surname   = $_POST['surname'];
                    $address   = $_POST['address'];
                    $homeNo    = $_POST['homeNo'];
                    $city      = $_POST['city'];
                    $postal    = $_POST['postal'];
                    $telephone = $_POST['telephone'];
                    $email     = $_POST['email'];
                    $birthdate = $_POST['birthdate'];

                    if ( $id > 0 ){
                        $updateCallersData = $_outbound->updateCallersData($id,$name,$surname,$address,$homeNo,$city,$postal,$telephone,$email,$birthdate);
                        if ($updateCallersData == true){
                            $response = '1';
                        } else{
                            $response = '0';
                        }
                    }

                    return new Response($response);
                    break;

                case "insertCallerYears":

                    $id        = $_POST['callerId'];
                    $years     = $_POST['years'];

                    if ( $id > 0 ){
                        $updateCallersYears = $_outbound->updateCallerYears($id,$years);
                        if ($updateCallersYears == true){
                            $response = '1';
                        } else{
                            $response = '0';
                        }
                    }

                   // var_dump($updateCallersYears);
                    return new Response($response);

                    break;


                case 'changeOMGorder':
                    $upsellQuery   = "";
                    $submitId      = $_POST['submitId'];
                    $recordId      = $_POST['recordId'];
                    $upsellChange  = $_POST['upsellChangeMade'];
                    $dataChange    = $_POST['dataChangeMade'];
                    $initialStatus = 12;

                    $name          = $_POST['name'];
                    $surname       = $_POST['surname'];
                    $address       = $_POST['address'];
                    $houseno       = $_POST['houseno'];
                    $city          = $_POST['city'];
                    $postal        = $_POST['postal'];
                    $telephone     = $_POST['telephone'];
                    $mail          = $_POST['mail'];

                    $quantity      = $_POST['quantity'];
                    $price         = $_POST['price'];
                    $upsell        = $_POST['upsell'];
                    $postage       = $_POST['postage'];

                    $newPrice         = (string)$_POST['newPrice'];

                    $comment       = $_POST['comment'];

                    $selectOMGorder = $_outbound->selectOMGOrderForUpdate($submitId);

                    if(count($selectOMGorder)>0) {
                        $post_data	= (array)json_decode($selectOMGorder['post_data']);
                        $old_quant = $post_data['quantity'];

                        // NOVO SETOVANJE JSONA
                        if ($dataChange == "1"){
                            $post_data['name']	    = $name;
                            $post_data['surname']	= $surname;
                            $post_data['address']	= $address;
                            $post_data['houseno']	= $houseno;
                            $post_data['city']	    = $city;
                            $post_data['postal']	= $postal;
                            $post_data['telephone']	= $telephone;
                            $post_data['email']	    = $mail;
                            $post_data['comment']  .= $comment;
                        }

                        if ( $upsellChange == "1"){
                            $post_data['quantity']	= $quantity;
                            $post_data['price']	    = $price;
                            $post_data['upsell']	= $upsell;
                            $post_data['postage']	= $postage;
                            $post_data['comment']  .= " [PHN] Order upgraded from {$old_quant}X to {$quantity}X ";
                            $upsellQuery            = " ,`upsellMade`='1'  ";
                            $initialStatus          = 7;
                        }

                        $post_data['comment']  .= " [Order verified] ";
                        $new_data= addslashes(json_encode($post_data,JSON_UNESCAPED_UNICODE));

                        $changeOMGorder = $_outbound->changeOMGorder($submitId, $recordId, $new_data, $initialStatus, $upsellQuery, ' , newPrice = '.$newPrice);

                        echo "1";

                    } else {

                        echo "0";
                    }

                    exit();
                    break;

                    exit;
            }
        }
    }
}