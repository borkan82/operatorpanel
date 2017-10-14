<?php
namespace AjaxBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


use AppBundle\Entity\Main;
use AppBundle\Entity\SMS;
use AppBundle\Entity\OMG;
use AppBundle\Entity\STATS;
use AppBundle\Entity\Settings;


class InboundAjaxController extends Controller
{


    public function ajaxAction()
    {

        $conn       = $this->get('database_connection');

        $_sms       = new SMS($conn);
        $_main      = new Main($conn);
        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);
        $_stats  = new STATS($conn);

        $DOWNLOAD = '/var/www/sites/phone-sale.net/htdocs/ver3/web/Download/';

        $request    = Request::createFromGlobals();

        if ($request->isMethod('POST')) {
            $post = $request->request->get('action');
        } elseif ($request->isMethod('GET')){
            $post = $request->query->get('action');
        }


        if (isset($post)) {

            switch ($post) {
                case "exportOrderList":

                    $datumDanas = date('Y-m-d');
                    $random = $request->query->get('random');

                    $state      = $request->query->get('state');
                    $product    = $request->query->get('product');
                    $user       = $request->query->get('user');
                    $group      = $request->query->get('group');
                    $ordType    = $request->query->get('ordType');
                    $outcome    = $request->query->get('outcome');
                    $reason     = $request->query->get('reason');
                    $ordSource  = $request->query->get('ordSource');
                    $ordNum     = $request->query->get('ordNum');
                    $from       = $request->query->get('from');
                    $to         = $request->query->get('to');

                    if(isset($state) && !empty($state))         { $scQ = " AND phone_order_calls.state = '$state' ";                                    } else { $scQ = ""; $state = ""; }
                    if(isset($product) && !empty($product))     { $prQ = " AND phone_order_calls.product  = '$product' ";                               } else { $prQ = ""; $product = ""; }
                    if(isset($ordType) && !empty($ordType))           { $tQ = " AND phone_order_calls.type  = '$ordType' ";                                      } else { $tQ = ""; $ordType = "";}
                    if(isset($outcome) && !empty($outcome))     { if ($outcome == 1){
                        $oQ = " and phone_order_calls.success = 'ORDERED!' AND phone_order_calls.ePrice = phone_order_calls.bPrice ";
                    } else if ($outcome == 2) {
                        $oQ = " and phone_order_calls.success = 'ORDERED!' AND phone_order_calls.ePrice > phone_order_calls.bPrice ";
                    } else if ($outcome == 3) {
                        $oQ = " and phone_order_calls.success = 'CANCELED!' ";
                    } else if ($outcome == 4) {
                        $oQ = " and phone_order_calls.success = 'NO ORDER!' ";
                    } else {
                        $oQ = "";
                    }                                                                                   } else { $oQ = ""; $outcome = ""; }

                    if(isset($reason) && !empty($reason))       { $reaQ = " AND phone_order_calls.cancelReason = '$reason' ";                           } else { $reaQ = ""; $reason=""; }
                    if(isset($ordSource) && !empty($ordSource))       { $sQ = " AND phone_order_calls.orderType = '$ordSource' ";
//                        if ($ordSource == "2"){
//                            $sQ .= " AND campaignId LIKE 'sms%' ";
//                        } else if ($ordSource == "5"){
//                            $sQ = " and phone_order_calls.orderType = '2' ";
//                            $sQ .= " AND campaignId LIKE 'reord%' ";
//                        }
                    } else { $sQ = ""; $ordSource=""; }

                    if(isset($ordNum) && !empty($ordNum))             { $nQ = " ORDER BY date DESC LIMIT $ordNum ";                                              } else { $nQ = ""; $ordNum = ""; }
                    if(isset($user) && !empty($user))           { $uQ = " AND phone_order_calls.operator = $user ";                                     } else { $uQ = ""; $user = ""; }
                    if(isset($group) && !empty($group))         { $grQ = " AND phone_order_users.operatorGroup = $group ";                              } else { $grQ = ""; $group = ""; }
                    if(isset($from) && !empty($from))           { $dfQ = " AND DATE(phone_order_calls.date) >= '$from' ";                               } else { $from = date('Y-m-01'); $dfQ = "  and DATE(phone_order_calls.date) >= '$from' "; }
                    if(isset($to) && !empty($to))               { $dtQ = " AND DATE(phone_order_calls.date) <= '$to' ";                                 } else { $to = $datumDanas; $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";}

                    $Query = " 1 ";  //default
                    $Query .= $scQ;  //state
                    $Query .= $prQ;  //product
                    $Query .= $tQ;   //Order type
                    $Query .= $oQ;   //Outcome
                    $Query .= $reaQ;   //Reason
                    $Query .= $sQ;   //Order source
                    $Query .= $uQ;   //User
                    $Query .= $grQ;  //Group
                    $Query .= $dfQ;  //date from
                    $Query .= $dtQ;  //date to
                    $Query .= $nQ;  //limit rows

                    $_data = $_omg->getDataRows("*",$Query);
                    $_products = $_omg->getProductList("id, title", "1");

                    $proizvod = Array();
                    $proizvod[0] = "No product";
                    foreach ($_products as $_product) {

                        $proizvod[$_product["id"]] = $_product["title"];
                    }

                    $counter = 0;

                    $dataToExport = '"NO.","STATE","OPERATOR","PHONE","TYPE","PRODUCT","SP. OFFER","DATE","CODE","CALL START","CALL END","CALL DURATION","CALL TYPE","QUESTIONS","OTHER","SUCCESS","CANCEL","REASON"
';

                    foreach ($_data as $row){
                        $type = $row['type']; $showType = "ORDER";
                        if ( $type == 2 ) {
                            $showType = "OTHER";
                        }

                        $cancel = $row['cancel']; $showCancel = "NO";
                        if ( $cancel == 1 ) {
                            $showCancel = "Yes";
                        }
                        $success = $row['success']; $showColor = "";

                        if ( $success == "CANCELED!" ) {
                            $showColor = "redLine";
                        } else if ( $success == "ORDERED!" ) {
                            $showColor = "greenLine";
                        }

                        $datum = substr($row['date'], 0, 10);
                        $counter++;


                        $dataToExport .= '"'.$counter.'","'.$row['state'].'","'.$row['opName'].'","'.$row['cPhone'].'","'.$row['title'].'","'.$proizvod[$row["product"]].'","'.$proizvod[$row["special"]].'","'.$datum.'","'.$row['code'].'","'.$row['start'].'","'.$row['end'].'","'.$row['duration'].'","'.$showType.'","'.$row['otherOpt'].'","'.$row['other'].'","'.$row['success'].'","'.$showCancel.'","'.$row['cancelReason'].'"
    ';
                    }

                    $exportFile = "(".$from."_".$to.")-".$random.".csv";
                    $downloadName = 'InboundStats/OrderListInb';
                    if ($state !=""){
                        $downloadName = 'InboundStats/OrderListInb-';
                    }

                    $file = fopen($DOWNLOAD . $downloadName.$state."-".$exportFile, "a");
                    file_put_contents($DOWNLOAD . $downloadName.$state."-".$exportFile, $dataToExport, FILE_APPEND);
                    fclose($file);

                    return new Response("Upisan ".$downloadName.$state."-".$exportFile." fajl");


                    break;
                    case "exportOperatorStats":
                        $datumDanas = date('Y-m-d');
                        $random = $request->query->get('random');

                        $state      = $request->query->get('state');
                        $product    = $request->query->get('product');
                        $user       = $request->query->get('user');
                        $group      = $request->query->get('group');
                        $ordType    = $request->query->get('ordType');
                        $ordSource  = $request->query->get('ordSource');
                        $from       = $request->query->get('from');
                        $to         = $request->query->get('to');

                        if(isset($state) && !empty($state))         { $scQ = " AND phone_order_calls.state = '$state' ";                                    } else { $scQ = ""; $state = ""; }
                        if(isset($product) && !empty($product))     { $prQ = " AND phone_order_calls.product  = '$product' ";                               } else { $prQ = ""; $product = ""; }
                        if(isset($user) && !empty($user))           { $uQ = " AND phone_order_calls.operator = $user ";                                     } else { $uQ = ""; $user = ""; }
                        if(isset($group) && !empty($group))         { $grQ = " AND phone_order_users.operatorGroup = $group ";                              } else { $grQ = ""; $group = ""; }

                        if(isset($ordType) && !empty($type))           { $tQ = " AND phone_order_calls.type  = '$ordType' ";                                      } else { $tQ = ""; $ordType = "";}
                        if(isset($ordSource) && !empty($ordSource))       { $sQ = " AND phone_order_calls.orderType = '$ordSource' ";
//                            if ($ordSource == "2"){
//                                $sQ .= " AND campaignId LIKE 'sms%' ";
//                            } else if ($ordSource == "5"){
//                                $sQ = " and phone_order_calls.orderType = '2' ";
//                                $sQ .= " AND campaignId LIKE 'reord%' ";
//                            }
                        } else { $sQ = ""; $ordSource=""; }
                        if(isset($from) && !empty($from))           { $dfQ = " AND DATE(phone_order_calls.date) >= '$from' ";                               } else { $from =date('Y-m-01'); $dfQ = "  and DATE(phone_order_calls.date) >= '$from' "; }
                        if(isset($to) && !empty($to))               { $dtQ = " AND DATE(phone_order_calls.date) <= '$to' ";                                 } else { $to = $datumDanas; $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";}

                        $Query = " 1 ";  //default
                        $Query .= $prQ;  //product
                        $Query .= $scQ;  //state
                        $Query .= $tQ;  //Order type
                        $Query .= $sQ;  //Order source
                        $Query .= $uQ;  //User
                        $Query .= $grQ;  //Group
                        $Query .= $dfQ;  //date from
                        $Query .= $dtQ;  //date to

                        $_data = $_stats->getDataOperator("*",$Query);
                        $_callDuration = $_stats->getCallDurations("*",$Query);
                        $_countupsells = $_stats->getOrdersByCall($Query);

                        $upsellArr = Array();
                        $durations = Array();
                        foreach ($_callDuration as $callD) {

                            $from_time = strtotime($callD['startT']);
                            $to_time = strtotime($callD['endT']);
                            $sekundi = abs($to_time - $from_time);

                            $durations[$callD["opName"]] = $durations[$callD["opName"]] + $sekundi;
                            $upsellArr[$callD["opName"]] = 0;
                        }



                        foreach ($_countupsells as $upsell) {

                            $quantity = substr($upsell["product"], 0, 1);

                            if ($quantity > 1){
                                $upsellArr[$upsell["username"]] = $upsellArr[$upsell["username"]] + 1;
                            }
                        }

                        $counter = 0;


                        $dataToExport = '"NO.","STATE","OPERATOR","CALLS","DURATION","AVG. CALL DURATION","ORDERS","SUCSESSFUL ORDERS","UPSELLS","CANCELED ORDERS","OTHER","ORDER (%)","CANCEL (%)","OTHER (%)"
';
                        foreach ($_data as $row){
                            $datum = substr($row['date'], 0, 10);
                            $counter++;
                            $totalOrder = $row['orderedNum']+$row['canceledNum'];

                            $orderPerc = ($row['orderedNum'] / $row['callNums']) * 100;
                            $cancelPerc = ($row['canceledNum'] / $totalOrder) * 100;
                            $otherPerc = ($row['otherNum'] / $row['callNums']) * 100;
                            $splitTime = explode(":", $row['durationTotal']);
                            $upsellPerc = ($upsellArr[$row['opName']] / $row['orderedNum']) * 100;


                            $sati = "";
                            $setHours = 0;
                            if ($splitTime[0] >= 60) {
                                $setHours = round($splitTime[0]/60, 0);
                                $rest = $splitTime%60;
                                $splitTime[0] = $rest;
                            }
                            if ($setHours > 0){
                                $sati = $setHours."h ";
                            }

                            $avgDurationUnix = (int)$durations[$row['opName']]/(int)$row['callNums'];
                            $avgDuration = gmdate('H:i:s', $avgDurationUnix);


                            //RACUNANJE TOTALA U FOOTERU

                            $tCalls      = $tCalls +  $row['callNums'];
                            $tOrders     = $tOrders +  $totalOrder;
                            $tSuccess    = $tSuccess +  $row['orderedNum'];
                            $tOther      = $tOther +  $row['otherNum'];
                            $tUpsellCp   = $tUpsellCp +  $upsellArr[$row['opName']];
                            $tCancell    = $tCancell +  $row['canceledNum'];
                            $tOrderCp    = $tOrderCp +  $orderPerc;
                            $tCancelCp   = $tCancelCp +  $cancelPerc;
                            $tOtherCp    = $tOtherCp +  $otherPerc;
                            $tDurations  = $tDurations + $durations[$row['opName']];
                            $tAvgDuration= $tAvgDuration + $avgDurationUnix;

                            $dataToExport .= '"'.$counter.'","'.$row['state'].'","'.$row['opName'].'","'.$row['callNums'].'","'.gmdate('H:i:s', $durations[$row['opName']]).'","'.$avgDuration.'","'.$totalOrder.'","'.$row['orderedNum'].'","'.number_format($upsellPerc, 2).' %","'.$row['canceledNum'].'","'.$row['otherNum'].'","'.number_format($orderPerc, 2).' %","'.number_format($cancelPerc, 2).' %","'.number_format($otherPerc, 2).' %"
    ';
                        }
                        $tUpsellsP  = ($tUpsellCp / $tSuccess) * 100;
                        $tOrdersP   = ($tSuccess / $tCalls) * 100;
                        $tCancellP  = ($tCancell / $tOrders) * 100;
                        $tOtherP    = ($tOther / $tCalls) * 100;
                        $avgDur     = $tAvgDuration / $counter;

                        $dataToExport .= '"","","TOTAL:","'.$tCalls.'","'.gmdate('H:i:s', $tDurations).'","'.gmdate('H:i:s', $avgDur).'","'.$tOrders.'","'.$tSuccess.'","'.number_format($tUpsellsP, 2).' %","'.$tCancell.'","'.$tOther.'","'.number_format($tOrdersP, 2).' %","'.number_format($tCancellP, 2).' %","'.number_format($tOtherP, 2).' %"
        ';

                        echo $dataToExport;

                        $exportFile = "(".$from."_".$to.")-".$random.".csv";
                        $downloadName = 'InboundStats/OperatorStatsInb';
                        if ($state !=""){
                            $downloadName = 'InboundStats/OperatorStatsInb-';
                        }

                        $file = fopen($DOWNLOAD . $downloadName.$state."-".$exportFile, "a");
                        file_put_contents($DOWNLOAD . $downloadName.$state."-".$exportFile, $dataToExport, FILE_APPEND);
                        fclose($file);

                        return new Response("Upisan ".$downloadName.$state."-".$exportFile." fajl");
//
//                        $file = fopen($DOWNLOAD . "InboundStats/OperatorStatsInb-".$datumDanas."-".$random.".csv", "a");
//                        file_put_contents($DOWNLOAD . "InboundStats/OperatorStatsInb-".$datumDanas."-".$random.".csv", $dataToExport, FILE_APPEND);
//                        fclose($file);
//
//                        return new Response("Upisan InboundStats/OperatorStatsInb-" . $datumDanas."-".$random.".csv fajl");
                        break;

                   
            }
//            return new Response(json_encode($post));
        }



    }


}



?>