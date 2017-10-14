<?php
namespace AjaxBundle\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use AppBundle\Entity\Outbound;
use AppBundle\Entity\Main;
use AppBundle\Entity\SMS;
use AppBundle\Entity\OMG;
use AppBundle\Entity\Settings;

class OutboundAjaxController extends Controller
{

    public function ajaxAction()
    {

        $conn       = $this->get('database_connection');
        $_outbound  = new Outbound($conn);
        $_sms       = new SMS($conn);
        $_main      = new Main($conn);
        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);
        $DOWNLOAD = '/var/www/sites/phone-sale.net/htdocs/ver3/web/Download/';

        $request    = Request::createFromGlobals();

        if ($request->isMethod('POST')) {
            $post = $request->request->get('action');
        } elseif ($request->isMethod('GET')){
            $post = $request->query->get('action');
          //  var_dump()

        }
      

        if (isset($post)) {

            switch ($post) {
                case "exportOrderList":

                    $datumDanas = date('Y-m-d');
                    $random = $request->query->get('random');

                    $state     = $request->query->get('state');
                    $type      = $request->query->get('ordType');
                    $ostatus   = $request->query->get('ordStatus');
                    $subStatus = $request->query->get('subStatus');
                    $product   = $request->query->get('product');
                    $num       = $request->query->get('ordNum');
                    $user      = $request->query->get('user');
                    $group     = $request->query->get('group');
                    $from      = $request->query->get('from');
                    $to        = $request->query->get('to');
                    $cfrom     = $request->query->get('cfrom');
                    $cto       = $request->query->get('cto');

                    if (isset($state) && !empty($state))         { $scQ = " and phone_order_outbound.state = '$state' ";               } else { $scQ = ""; $state =""; }
                    if (isset($type) && !empty($type))           { $tQ = " and phone_order_outbound.type = '$type' ";                  } else { $tQ = "";    }
                    if (isset($subStatus) && !empty($subStatus)) { $ssQ = " and phone_order_outbound.status = '$subStatus' ";         } else { $ssQ = "";    }
                    if (isset($product) && !empty($product))     { $prQ = " and phone_order_outbound.productID = '$product' ";         } else { $prQ = "";  }
                    if (isset($num) && !empty($num))             { $nQ = " ORDER BY tocall_time DESC LIMIT $num";                       } else { $num = "10000";   $nQ = "ORDER BY tocall_time DESC LIMIT 10000";    }
                    if (isset($user) && !empty($user))           { $uQ = " and phone_order_outbound.operator = $user ";                } else { $uQ = "";  }
                    if (isset($group) && !empty($group))         { $grQ = " and phone_order_users.operatorGroup = $group ";            } else { $grQ = "";    }
                    if (isset($from) && !empty($from))           { $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' ";    } else { $from = date('Y-m-01');  $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' ";       }
                    if (isset($to) && !empty($to))               { $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";      } else { $to = date('Y-m-d');     $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";     }
                    if (isset($cfrom) && !empty($cfrom))         { $cdfQ = " and DATE(phone_order_outbound.called_time) >= '$cfrom' "; } else { $cdfQ = "";  }
                    if (isset($cto) && !empty($cto))             { $cdtQ = " and DATE(phone_order_outbound.called_time) <= '$cto' ";   } else { $cdtQ = "";    }
                    if (isset($ostatus) && !empty($ostatus) OR $ostatus === "0") {
                        if ($ostatus == 1) {
                            $sQ = " and (phone_order_outbound.status = '7' || phone_order_outbound.status = '12') ";
                        } else if ($ostatus == 2) {
                            $sQ = " and phone_order_outbound.status = '6' ";
                        } else if ($ostatus == 3) {
                            $sQ = " and (phone_order_outbound.status = '1' || phone_order_outbound.status = '2' ||
                                    phone_order_outbound.status = '4' || phone_order_outbound.status = '8' ||
                                    phone_order_outbound.status = '9' || phone_order_outbound.status = '10') ";
                        } else {
                            $sQ = " and phone_order_outbound.status = '$ostatus' ";
                        }
                    } else {
                        $sQ = "";
                    }

                    $Query = " ";  //default
                    $Query .= $scQ;  //state
                    $Query .= $tQ;  //Order type
                    $Query .= $sQ;  //Order status
                    $Query .= $ssQ;  //Order substatus
                    $Query .= $prQ;  //date called to
                    $Query .= $uQ;  //User
                    $Query .= $grQ;  //Group
                    $Query .= $dfQ;  //date from
                    $Query .= $dtQ;  //date to
                    $Query .= $cdfQ;  //date called from
                    $Query .= $cdtQ;  //date called to

                    $Query .= $nQ;  //Order Num
                    $_products        = $_omg->getProductList("id, title", "1");
                    $_outbound_result = $_outbound->getOutboundQuery($Query);
                    $statuses   = Array(
                                    0=>Array("name"=>"OTHER (Pending)","num"=>0),
                                    1=>Array("name"=>"OTHER (Answered)","num"=>0),
                                    2=>Array("name"=>"OTHER (Busy)","num"=>0),
                                    4=>Array("name"=>"OTHER (Fake)","num"=>0),
                                    6=>Array("name"=>"CANCELED","num"=>0),
                                    7=>Array("name"=>"FINISHED (Order)","num"=>0),
                                    8=>Array("name"=>"ERROR","num"=>0),
                                    9=>Array("name"=>"OTHER (Postponed)","num"=>0),
                                    10=>Array("name"=>"OTHER (Inbound)","num"=>0),
                                    11=>Array("name"=>"OTHER (Calling)","num"=>0),
                                    12=>Array("name"=>"FINISHED (Verify)","num"=>0),
                                    13=>Array("name"=>"OTHER (Not Called)","num"=>0),
                                    14=>Array("name"=>"OTHER (Removed)","num"=>0)
                                );
                    $proizvod = Array();
                    $proizvod[0] = "No product";
                    foreach ($_products as $_product) {
                        $proizvod[$_product["id"]] = $_product["title"];
                    }

                    $counter = 0;

                    $dataToExport = '"NO.","STATE","OPERATOR","TYPE","SUBMIT ID","RANDOM","PRODUCT","PRICE","FINAL PRICE","NAME","PHONE","TO CALL","CALLED TIME","DURATION","STATUS","ANSWERED"
';

                    foreach ($_outbound_result as $out){

                        $type = $out['type'];
                        $status = $out['status'];



                        $types = Array(
                            1=>"Adcombo-Call",
                            2=>"Canceled User",
                            3=>$out['quantity']."x order - upsell",
                            4=>"Verify OMG",
                            5=>"Form Fill Brake",
                            6=>"Order Fill Brake",
                            7=>"Reorder call",
                            8=>"Bulk call",
                            9=>"Undecided",
                            10=>"Reorder Mail");

                        if (!empty($out['called_time']) && !empty($out['callEnd']) && $out['called_time'] !== "" && $out['callEnd'] !== ""){
                            $unixStart      = strtotime($out['called_time']);
                            $unixEnd        = strtotime($out['callEnd']);
                            $unixDuration   = $unixEnd - $unixStart;
                            $singleCallDuration = gmdate('H:i:s', $unixDuration);
                        } else {
                            $singleCallDuration = "00:00:00";
                        }

                        if ($out['status'] == 7 || $out['status'] == 6 || $out['status'] == 9){
                            $answered = 'Yes';
                        } else {
                            $answered = 'No';
                        }

                        $counter++;

                        $dataToExport .= '"'.$counter.'","'.$out['state'].'","'.$out['opName'].'","'.$types[$type].'","'.$out['submitID'].'","'.$out['randomID'].'","'.$out["title"].'","'.$out['price'].'","'.$out['newPrice'].'","'.$out['name'].'","'.$out['phone'].'","'.$out['tocall_time'].'","'.$out['called_time'].'","'.$singleCallDuration.'","'.$statuses[$out['status']]['name'].' ('.$out['callCount'].')","'.$answered.'"
    ';

                    }
                    echo $dataToExport;

                    $exportFile = "(".$from."_".$to.")-".$random.".csv";
                    $downloadName = 'OutboundStats/OrderListOut';
                    if ($state !=""){
                        $downloadName = 'OutboundStats/OrderListOut-';
                    }


                    $file = fopen($DOWNLOAD . $downloadName.$state."-".$exportFile, "a");
                    file_put_contents($DOWNLOAD . $downloadName.$state."-".$exportFile, $dataToExport, FILE_APPEND);
                    fclose($file);

                    return new Response("Upisan ".$downloadName.$state."-".$exportFile." fajl");

                        break;


                case "exportOperatorStats":

                    $datumDanas = date('Y-m-d');
                    $random = $request->query->get('random');

                    $state     = $request->query->get('state');
                    $type      = $request->query->get('ordType');
                    $user      = $request->query->get('user');
                    $group     = $request->query->get('group');
                    $from      = $request->query->get('from');
                    $to        = $request->query->get('to');

                    if (isset($state) && !empty($state))         { $scQ = " and phone_order_outbound.state = '$state' ";               } else { $scQ = ""; $state=""; }
                    if (isset($type) && !empty($type))           { $tQ = " and phone_order_outbound.type = '$type' ";                  } else { $tQ = "";    }
                    if (isset($user) && !empty($user))           { $uQ = " and phone_order_outbound.operator = $user ";                } else { $uQ = "";  }
                    if (isset($group) && !empty($group))         { $grQ = " and phone_order_users.operatorGroup = $group ";            } else { $grQ = "";    }
                    if (isset($from) && !empty($from))           { $dfQ = " and DATE(phone_order_outbound.called_time) >= '$from' ";   } else { $from = date('Y-m-01');  $dfQ = " and DATE(phone_order_outbound.called_time) >= '$from' ";       }
                    if (isset($to) && !empty($to))               { $dtQ = " and DATE(phone_order_outbound.called_time) <= '$to' ";     } else { $to = date('Y-m-d');     $dtQ = " and DATE(phone_order_outbound.called_time) <= '$to' ";     }


                    $Query = " 1 ";  //default
                    $Query .= $scQ;  //state
                    $Query .= $tQ;   //Order type
                    $Query .= $uQ;   //User
                    $Query .= $grQ;  //Group
                    $Query .= $dfQ;  //date from
                    $Query .= $dtQ;  //date to
                    
                    $_data = $_outbound->getDataOperator("*",$Query);  //koristim
                    //print_r($_data);

                    $myTableData =  array();

                    foreach ($_data as $operator){

                        $newOperator = $operator;
                        $newOperator['orderedNum']= $operator['status7'] + $operator['status12'];
                        $newOperator['canceledNum']= $operator['status6'];
                        $newOperator['answeredNum']= $operator['status6'] + $operator['status7'] + $operator['status12'] +$operator['status9'];

                        $myTableData[$operator['ouid']] = $newOperator;
                    }


                    $counter = 0;
                    $dataToExport = '"NO.","STATE","OPERATOR","CALLS","ANSWERED","ANSWERED (%)","DURATION","AVG. CALL DURATION","ORDERS","SUCSESSFUL ORDERS","UPSELLS","CANCELED ORDERS","OTHER","ORDER (%)","CANCEL (%)","OTHER (%)"
';
//print_r($myTableData);
                    foreach ($myTableData as $row){

                        $counter++;
                        $totalOrder = $row['orderedNum'] + $row['canceledNum'];
                        $answPerc = ($row['answeredNum'] / $row['callNums']) * 100;
                        $orderPerc = ($row['orderedNum'] / $row['answeredNum']) * 100;
                        $cancelPerc = ($row['canceledNum'] / $row['answeredNum']) * 100;
                        $otherPerc = ($row['otherNum'] / $row['callNums']) * 100;
                        $splitTime = $row['duration'];
                        $upsellPerc = ($row['upsells'] / $row['orderedNum']) * 100;


                        $avgDurationUnix = (int)$row['duration'] / (int)$row['callNums'];
                        $avgDuration = gmdate('H:i:s', $avgDurationUnix);

                        //RACUNANJE TOTALA U FOOTERU

                        $tCalls      = $tCalls +  $row['callNums'];
                        $tAnswer     = $tAnswer +  $row['answeredNum'];
                        $tOrders     = $tOrders +  $totalOrder;
                        $tSuccess    = $tSuccess +  $row['orderedNum'];
                        $tOther      = $tOther +  $row['otherNum'];
                        $tUpsellCp   = $tUpsellCp +  $row['upsells'];
                        $tCancell    = $tCancell +  $row['canceledNum'];
                        $tOrderCp    = $tOrderCp +  $orderPerc;
                        $tCancelCp   = $tCancelCp +  $cancelPerc;
                        $tOtherCp    = $tOtherCp +  $otherPerc;
                        $tDurations  = $tDurations + $row['duration'];
                        $tAvgDuration= $tAvgDuration + $avgDurationUnix;


                        $dataToExport .= '"'.$counter.'","'.$row['state'].'","'.$row['username'].'","'.$row['callNums'].'","'.$row['answeredNum'].'","'.number_format($answPerc, 2).' %","'.gmdate('H:i:s', $row['duration']).'","'.$avgDuration.'","'.$totalOrder.'","'.$row['orderedNum'].'","'.number_format($upsellPerc, 2).' %","'.$row['canceledNum'].'","'.$row['otherNum'].'","'.number_format($orderPerc, 2).' %","'.number_format($cancelPerc, 2).' %","'.number_format($otherPerc, 2).' %"
    ';
                    }
                    $tUpsellsP  = ($tUpsellCp / $tSuccess) * 100;
                    $tOrdersP   = ($tSuccess / $tAnswer) * 100;
                    $tCancellP  = ($tCancell / $tAnswer) * 100;
                    $tOtherP    = ($tOther / $tCalls) * 100;
                    $avgDur     = $tAvgDuration / $counter;
                    $tAnswPerc  = ($tAnswer/$tCalls)*100;


                    $dataToExport .= '"","","TOTAL:","'.$tCalls.'","'.$tAnswer.'","'.number_format($tAnswPerc, 2).' %","'.gmdate('H:i:s', $tDurations).'","'.gmdate('H:i:s', $avgDur).'","'.$tOrders.'","'.$tSuccess.'","'.number_format($tUpsellsP, 2).' %","'.$tCancell.'","'.$tOther.'","'.number_format($tOrdersP, 2).' %","'.number_format($tCancellP, 2).' %","'.number_format($tOtherP, 2).' %"
        ';


                    echo $dataToExport;

                    $exportFile = "(".$from."_".$to.")-".$random.".csv";
                    $downloadName = 'OutboundStats/OperatorStatsOut';
                    if ($state !=""){
                        $downloadName = 'OutboundStats/OperatorStatsOut-';
                    }

                    $file = fopen($DOWNLOAD . $downloadName.$state."-".$exportFile, "a");
                    file_put_contents($DOWNLOAD . $downloadName.$state."-".$exportFile, $dataToExport, FILE_APPEND);
                    fclose($file);

                    return new Response("Upisan ".$downloadName.$state."-".$exportFile." fajl");
                    break;
            }
        }

    }



}



?>