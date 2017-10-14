<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\GettersController;
use Symfony\Component\Validator\Constraints\DateTime;

use AppBundle\Entity\OMG;
use AppBundle\Entity\Settings;
use AppBundle\Entity\STATS;
use AppBundle\Entity\Outbound;
use AppBundle\Entity\Main;

class OutboundController extends Controller
{
    private function checkThisSession(){

//        $_main      = new Main();
//        $checkUser  = $_main->checkUserIfAdmin();
//        if ($checkUser == false){
//            return $this->redirectToRoute('login', array('status'=>'3'));
//            //return $this->redirect('../login?status=3');
//        }

        $_main      = new Main();
        $loggedIn = $_main->checkUserIsLoggedIn();
        if($loggedIn == true){
            $checkUser  = $_main->checkUserIfAdmin();
            if ($checkUser == false){
                // return $this->redirect('./login?status=3');
                return $this->redirectToRoute('login', array('status'=>'2'));
            }
        } else {
            return $this->redirectToRoute('login', array('status'=>'3'));
        }
    }

    public function viewDataAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }


        $title = 'Order list - Out';
        $conn = $this->get('database_connection');

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);
        $_outbound  = new Outbound($conn);
        $_getters   = new GettersController($conn);

        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }

        $state = $queryArr['state'];
        $type = $queryArr['ordType'];
        $ostatus = $queryArr['ordStatus'];
        $subStatus = $queryArr['subStatus'];
        $product = $queryArr['product'];
        $num = $queryArr['ordNum'];
        $user = $queryArr['user'];
        $group = $queryArr['group'];
        $from = $queryArr['from'];
        $to = $queryArr['to'];
        $cfrom = $queryArr['cfrom'];
        $cto = $queryArr['cto'];

        if (isset($state) && !empty($state))         { $scQ = " and phone_order_outbound.state = '$state' ";                } else { $scQ = "";  }
        if (isset($type) && !empty($type))           { $tQ = " and phone_order_outbound.type = '$type' ";                   } else { $tQ = "";    }
        if (isset($subStatus) && !empty($subStatus)) { $ssQ = " and phone_order_outbound.status = '$subStatus' ";           } else { $ssQ = "";    }
        if (isset($product) && !empty($product))     { $prQ = " and phone_order_outbound.productID = '$product' ";          } else { $prQ = "";  }
        if (isset($num) && !empty($num))             { $nQ = "ORDER BY tocall_time DESC LIMIT $num";                        } else { $num = "10000";   $nQ = "ORDER BY tocall_time DESC LIMIT 10000";    }
        if (isset($user) && !empty($user))           { $uQ = " and phone_order_outbound.operator = $user ";                 } else { $uQ = "";  }
        if (isset($group) && !empty($group))         { $grQ = " and phone_order_users.operatorGroup = $group ";             } else { $grQ = "";    }
        if (isset($from) && !empty($from))           { $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' ";     } else { $from = date('Y-m-01');  $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' ";      }
        if (isset($to) && !empty($to))               { $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";       } else { $to = date('Y-m-d');     $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";     }
        if (isset($cfrom) && !empty($cfrom))         { $cdfQ = " and DATE(phone_order_outbound.called_time) >= '$cfrom' "; } else { $cdfQ = ""; $cfrom=""; }
        if (isset($cto) && !empty($cto))             { $cdtQ = " and DATE(phone_order_outbound.called_time) <= '$cto' ";   } else { $cdtQ = "";  $cto="";  }
        if (isset($ostatus) && !empty($ostatus) OR $ostatus === "0") {
            if ($ostatus == 1) {
                $sQ = " and (phone_order_outbound.status = '7' || phone_order_outbound.status = '12') ";
            } else if ($ostatus == 2) {

                $sQ = " and phone_order_outbound.status = '6' ";
            } else if ($ostatus == 3) {

                $sQ = " and (phone_order_outbound.status = '1' || phone_order_outbound.status = '2' ||
                                    phone_order_outbound.status = '4' || phone_order_outbound.status = '8' ||
                                    phone_order_outbound.status = '9' || phone_order_outbound.status = '10') ";
            } else if ($ostatus == 4) {

                $sQ = " and (phone_order_outbound.status = '0' || phone_order_outbound.status = '13' ) ";
            } else {

                $sQ = " and phone_order_outbound.status = '$ostatus' ";
            }
        } else {
            $sQ = "";
        }

        $random     = rand(1000,9999);
        $today      = date('Y-m-d');
        $exportFile = "(".$from."_".$to.")-".$random.".csv";

       
        $explodedDate   = explode("-", $from);
        $selectY        = (int)$explodedDate[0];
        $selectM        = (int)$explodedDate[1];
        $thisMonth      = Date('m');
        if ($selectM == (int)$thisMonth){
            if($selectM == 1) {
                $selectY = $selectY - 1;
                $selectM = 12;
            } else {
                $selectM = $selectM - 1;
            }
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
//        $Query .= ' GROUP BY phone_order_outbound.id ';
        $Query .= $nQ;  //Order Num


        $costpercall    = 0;
        $costperorder   = 0;
        $period         = 0;
        if((isset($group) && !empty($group)) || (isset($state) && !empty($state))) {
            if(isset($group) && !empty($group)) {

                $_priceData = $conn->fetchAssoc("SELECT OUTperCall, OUTperOrder, periods.id as periodId 
                                                 FROM phone_order_callCenterPrice
                                                 LEFT JOIN periods ON phone_order_callCenterPrice.period = periods.id
                                                 WHERE phone_order_callCenterPrice.callCenterId = '{$group}' 
                                                 AND periods.month = '{$selectM}' 
                                                 AND periods.year = '{$selectY}' 
                                                 LIMIT 1");

            }   else if(isset($state) && !empty($state)) {

                $_priceData = $conn->fetchAssoc("SELECT OUTperCall, OUTperOrder, periods.id as periodId 
                                                FROM phone_order_callcenter
                                                LEFT JOIN phone_order_callCenterPrice ON phone_order_callcenter.id = phone_order_callCenterPrice.callCenterId
                                                LEFT JOIN periods ON phone_order_callCenterPrice.period = periods.id
                                                WHERE phone_order_callcenter.state = '{$state}' 
                                                AND periods.month = '{$selectM}' 
                                                AND periods.year = '{$selectY}' 
                                                LIMIT 1");
            }

            $costpercall    = $_priceData['OUTperCall'];;
            $costperorder   = $_priceData['OUTperOrder'];;
            $period         = $_priceData['periodId'];;

        } else {
            $_priceData = $conn->fetchAssoc("SELECT periods.id as periodId FROM periods
                                             WHERE  periods.month = '{$selectM}' AND periods.year = '{$selectY}' LIMIT 1");
            $period     = $_priceData['periodId'];
        }

        $_users             = $_omg->getUsers();
        $_products          = $_omg->getProductList("id, title", "1");
        $_centers           = $_settings->getCallCenterList();
        $_states            = $_omg->getStates();
        $_outbound_result   = $_outbound->getOutboundQuery($Query);
        $_statuses          = $_getters->getOutboundSubStatusesAction();
        
        
       
        $headerTable = $_outbound->getOutboundHeaderData($Query,$period);
    
        $headerTable                        = $headerTable[0];
       // $headerTable['totalDuration']       = gmdate('H:i:s', $headerTable['allDuration']);
        $headerTable['totalDuration']       = $_getters->getHoursMinuteSecunds($headerTable['allDuration']);

        $headerTable['countFinish']         = $headerTable['status7'] + $headerTable['status12'];
        $headerTable['countCancell']        = $headerTable['status6'];
        $headerTable['countAnswered']       = $headerTable['status6'] + $headerTable['status7'] + $headerTable['status12'] + $headerTable['status9'];
        $headerTable['outOrderSuccessfull'] = $headerTable['status7'] + $headerTable['status12'];
        //$headerTable['answerPercent']       = round(($headerTable['countAnswered'] / $headerTable['callCount']) * 100, 2);
        $headerTable['perCall']             = round($headerTable['orderSum'] / $headerTable['countAnswered'], 2);
        //$headerTable['avgCallDuration']     = gmdate('H:i:s', round($headerTable['allDuration'] / $headerTable['countCalled']));
        $headerTable['avgCallDuration']     = $_getters->getHoursMinuteSecunds(round($headerTable['allDuration'] / $headerTable['countCalled'],2));
        $headerTable['orderPercent']        = round(($headerTable['outOrderSuccessfull'] / $headerTable['countAnswered']) * 100, 2);
        $headerTable['perOrder']            = round($headerTable['orderSum'] / $headerTable['countFinish'], 2);
        //$headerTable['avgOrderDuration']    = gmdate('H:i:s', round($headerTable['allOrderDuration'] / $headerTable['countOrderCalled']));
        $headerTable['avgOrderDuration']    = $_getters->getHoursMinuteSecunds(round($headerTable['allOrderDuration'] / $headerTable['countOrderCalled'],2));
        $headerTable['upsellPercent']       = round(($headerTable['upsellsCount'] / $headerTable['countFinish']) * 100, 2);
        $headerTable['upsellValue']         = round($headerTable['upsellPriceDiff'] / $headerTable['countFinish'], 2);
        $headerTable['otherPercent']        = round(($headerTable['countOther'] / $headerTable['callCount']) * 100, 2);
        $headerTable['cancelPercent']       = round(($headerTable['countCancell'] / $headerTable['countAnswered']) * 100, 2);
        $headerTable['notCalled']           = $headerTable['status0'] + $headerTable['status13'];
        $headerTable['notCalledPercent']    = round(($headerTable['notCalled'] / $headerTable['callCount']) * 100, 2);
        $headerTable['costpercall']         = $costpercall;
        $headerTable['costperorder']        = $costperorder;




        $statuses = Array();
        $statuses[0] = Array("name" => "OTHER (Pending)", "num" => 0);
       

        foreach ($_statuses AS $st) {
            $statuses[$st['id']] = Array("name" => "{$st['stitle']} ({$st['title']})", "num" => 0);
        }

       
        $html = '<table class="dayView" id="example" style="font-size: 12px;">
                    <thead style="cursor:pointer;">
                    <tr >
                      <td width="20px">#</td>
                      <td >Country</td>
                      <td >Operator</td>
                      <td >Type</td>
                      <td >OMG Submit Id</td>
                      <td >Random</td>
                      <td >Product</td>
                      <td >Price</td>
                      <td >Final Price</td>
                      <td >Name</td>
                      <td >Phone</td>
                      <td >To call time</td>
                      <td >Called time</td>
                      <td >Duration</td>
                      <td >Status</td>
                      <td >Answered</td>
                    </tr>
                   </thead>
                   <tbody id="tabela">';


        $counter = 0;
        

        foreach ($_outbound_result as $out) {

            if (isset($statuses[$out['status']])) {
                $statuses[$out['status']]['num'] = $statuses[$out['status']]['num'] + 1;
            }
            $status = $out['status'];
            //$success = $row['success'];
            $showColor = "";

            if ($status == "6") {
                $showColor = "redLine";
            } else if ($status == "7" || $status == "12") {
                $showColor = "greenLine";
            }

            //$datum = substr($row['date'], 0, 10);
            $types = Array(1 => "Adcombo-Call", 2 => "Canceled User", 3 => $out['quantity'] . "x order - upsell", 4 => "Verify OMG", 5 => "Form Fill Brake", 6 => "Order Fill Brake", 7 => "Reorder call", 8 => "Bulk call", 9 => "Undecided", 10 => "Reorder Mail", 11 => "SMS Link", 12 =>'Undecided presell');

            if (!empty($out['called_time']) && !empty($out['callEnd']) && $out['called_time'] !== "" && $out['callEnd'] !== "") {
                $unixStart = strtotime($out['called_time']);
                $unixEnd = strtotime($out['callEnd']);
                $unixDuration = $unixEnd - $unixStart;
                //$singleCallDuration = gmdate('H:i:s', $unixDuration);
                $singleCallDuration = $_getters->getHoursMinuteSecunds($unixDuration);
            } else {
                $singleCallDuration = "00:00:00";
            }

            if ($out['status'] == 7 || $out['status'] == 6 || $out['status'] == 9) {
                $answered = 'Yes';
            } else {
                $answered = 'No';
            }
            $showOutColor = "outSubRow";
            $counter++;
            $html .= '<tr onclick="openItemList(' . $out['id'] . ');" class="showBuyer ' . $showColor . '" style="margin-top:1px; cursor:pointer;">
                        <td class="' . $showColor . '">' . $counter . '</td>
                        <td class="' . $showColor . '">' . $out['state'] . '</td>
                        <td class="' . $showColor . '"><a href="inspectleturl'. $out['id'] . '%22%7D%5D%2C%22operator%22%3A%22and%22%7D" target="_blank">' . $out['opName'] . '</a></td>
                        <td class="' . $showColor . '">' . $types[$out['type']] . ' <span style="color:#444;font-size:9px;">(' . $out['splitType'] . ')</span></td>
                        <td class="' . $showColor . '">' . $out['submitID'] . '</td>
                        <td class="' . $showColor . '">' . $out['randomID'] . '</td>
                        <td class="' . $showColor . '">' . $out['title'] . '</td>
                        <td class="' . $showColor . '">' . $out['price'] . '</td>
                        <td class="' . $showColor . '">' . $out['newPrice'] . '</td>
                        <td class="' . $showColor . '">' . $out['name'] . ' <span style="color:red">(' . $out['docStatus'] . ')</span></td>
                        <td class="' . $showColor . '">' . $out['phone'] . '</td>
                        <td class="' . $showColor . '">' . $out['tocall_time'] . '</td>
                        <td class="' . $showColor . '">' . $out['called_time'] . '</td>
                        <td class="' . $showColor . '">' . $singleCallDuration . '</td>
                        <td class="' . $showColor . '">' . $statuses[$out['status']]['name'] . ' (' . $out['callCount'] . ')</td>
                        <td class="' . $showColor . '">' . $answered . '</td>
                      </tr>';
        }
        $html .= '</tbody>
            </table>';

       
        $headerTable['callMade']            = ($headerTable['callCount'] - $headerTable['notCalled'] - $statuses[10]['num'] - $statuses[14]['num']  );
        $headerTable['answerPercent']       = round(($headerTable['countAnswered'] / $headerTable['callMade']) * 100, 2);
        echo new Response($statuses[10]['num']);
        //print_r($statuses);die();
        $response = $this->render('statsout/viewData.html.twig', array(

                '_html'       => $html,
                '_states'     => $_states,
                '_users'      => $_users,
                '_products'   => $_products,
                '_centers'    => $_centers,
                '_statuses'   => $statuses,
                '_types'      => $_getters->getOutboundTypesAction(),
                'from'        => $from,
                'to'          => $to,
                'cfrom'       => $cfrom,
                'cto'         => $cto,
                'title'       => $title,
                'headerTable' => $headerTable,
                'random'      => $random,
                'exportFile'  => $exportFile
            )
        );
        return $response;

    }

    public function outboundChartsAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Charts - Out';
        $conn = $this->get('database_connection');

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);
        $_outbound  = new Outbound($conn);
        $_getters   = new GettersController($conn);

        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }

        $state = $queryArr['state'];
        $type = $queryArr['ordType'];
        $product = $queryArr['product'];
        $from = $queryArr['from'];
        $to = $queryArr['to'];

        if (isset($state) && !empty($state))     { $scQ = " and phone_order_outbound.state = '$state' ";            } else { $state = "";     $scQ = "";      }
        if (isset($type) && !empty($type))       { $tQ = " and phone_order_outbound.type = '$type' ";               } else { $type = "";      $tQ = "";       }
        if (isset($product) && !empty($product)) { $pQ = " and phone_order_outbound.productID = '$product' ";       } else { $pQ = "";        }
        if (isset($from) && !empty($from))       { $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' "; } else { $from = date('Y-m-01');   $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' ";   }
        if (isset($to) && !empty($to))           { $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";   } else { $to = date('Y-m-d');      $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";    }

        $Query = " ";    //default
        $Query .= $scQ;  //state
        $Query .= $tQ;   //Order type
        $Query .= $pQ;   //product
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to

        $explodedDate   = explode("-", $from);
        $selectY        = (int)$explodedDate[0];
        $selectM        = (int)$explodedDate[1];

        $thisMonth      = Date('m');
        if ($selectM == (int)$thisMonth){
            if($selectM == 1) {
                $selectY = $selectY - 1;
                $selectM = 12;
            } else {
                $selectM = $selectM - 1;
            }
        }

        $costpercall    = 0;
        $costperorder   = 0;
        $period         = 0;
        if(isset($state) && !empty($state)) {

            $_priceData = $conn->fetchAssoc("SELECT OUTperCall, OUTperOrder, periods.id as periodId 
                                            FROM phone_order_callcenter
                                            LEFT JOIN phone_order_callCenterPrice ON phone_order_callcenter.id = phone_order_callCenterPrice.callCenterId
                                            LEFT JOIN periods ON phone_order_callCenterPrice.period = periods.id
                                            WHERE phone_order_callcenter.state = '{$state}' 
                                            AND periods.month = '{$selectM}' 
                                            AND periods.year = '{$selectY}' 
                                            LIMIT 1");
         

            $costpercall    = $_priceData['OUTperCall'];;
            $costperorder   = $_priceData['OUTperOrder'];;
            $period         = $_priceData['periodId'];;

        } else {
            $_priceData = $conn->fetchAssoc("SELECT periods.id as periodId FROM periods
                                             WHERE  periods.month = '{$selectM}' AND periods.year = '{$selectY}' LIMIT 1");
            $period     = $_priceData['periodId'];
        }

        $_products          = $_omg->getProductList("id, title", "1");
        $_outbound_result   = $_outbound->getOutboundQuery($Query);
        $_states            = $_omg->getStates();
        $_statuses          = $_getters->getOutboundSubStatusesAction();

        $statuses = Array();
        $statuses[0] = Array("name" => "OTHER (Pending)", "num" => 0);


        foreach ($_statuses AS $st) {
            $statuses[$st['id']] = Array("name" => "{$st['stitle']} ({$st['title']})", "num" => 0);
        }

        /*
         * POCETAK STATISIKE ZA CHARTOVE ZA SVE DRZAVE
         */

        $statesFromOutbound = $_outbound->getOutboundStateList();

        $statesOutbound = array();

        foreach ($statesFromOutbound as $stateInb) {

            $statesOutbound[$stateInb['state']] = array(

                'count-adambo-cal' => 0,
                'count-canceled-user' => 0,
                'count-upsell-call' => 0,
                'count-form-fill-break' => 0,
                'count-order-fill-break' => 0,
                'count-reorder-call' => 0,
                'count-bulk-call' => 0,
                'count-undecided-call' => 0,
                'count-mr-call' => 0,

                'order-sum-adambo-cal' => 0,
                'order-sum-canceled-user' => 0,
                'order-sum-upsell-call' => 0,
                'order-sum-form-fill-break' => 0,
                'order-sum-order-fill-break' => 0,
                'order-sum-reorder-call' => 0,
                'order-sum-bulk-call' => 0,
                'order-sum-undecided-call' => 0,
                'order-sum-mr-call' => 0,

                'order-successful-adambo-cal' => 0,
                'order-successful-canceled-user' => 0,
                'order-successful-upsell-call' => 0,
                'order-successful-form-fill-break' => 0,
                'order-successful-order-fill-break' => 0,
                'order-successful-reorder-call' => 0,
                'order-successful-bulk-call' => 0,
                'order-successful-undecided-call' => 0,
                'order-successful-mr-call' => 0,

                'avg-value-adambo-cal' => 0,
                'avg-value-canceled-user' => 0,
                'avg-value-upsell-call' => 0,
                'avg-value-form-fill-break' => 0,
                'avg-value-order-fill-break' => 0,
                'avg-value-reorder-call' => 0,
                'avg-value-bulk-call' => 0,
                'avg-value-undecided-call' => 0,
                'avg-value-mr-call' => 0,

                'order-percent-adambo-cal' => 0,
                'order-percent-canceled-user' => 0,
                'order-percent-upsell-call' => 0,
                'order-percent-form-fill-break' => 0,
                'order-percent-order-fill-break' => 0,
                'order-percent-reorder-call' => 0,
                'order-percent-bulk-call' => 0,
                'order-percent-undecided-call' => 0,
                'order-percent-mr-call' => 0
            );
        }

        $extendedTypes = array(
            1 => array(
                'name' => 'Adcombo Call',
                'shortName' => 'adambo-cal'
            ),
            2 => array(
                'name' => 'Canceled User',
                'shortName' => 'canceled-user'
            ),
            3 => array(
                'name' => 'Upsell Call',
                'shortName' => 'upsell-call'
            ),
            5 => array(
                'name' => 'Form fill brake',
                'shortName' => 'form-fill-break'
            ),
            6 => array(
                'name' => 'Order Fill Brake',
                'shortName' => 'order-fill-break'
            ),
            7 => array(
                'name' => 'Reorder call',
                'shortName' => 'reorder-call'
            ),
            8 => array(
                'name' => 'Bulk call',
                'shortName' => 'bulk-call'
            ),
            9 => array(
                'name' => 'Undecided call',
                'shortName' => 'undecided-call'
            ),
            10 => array(
                'name' => 'Mailreorder call',
                'shortName' => 'mr-call'
            ),
            11 => array(
                'name' => 'Sms Link',
                'shortName' => 'sms-link'
            ),
            12 => array(
                'name' => 'Undecided presell',
                'shortName' => 'undecided-presell'
            )
        );

        $dataForCharts = $_outbound_result;
        if ($type != "" || $state != "") {
            $QueryNew = " ";    //default
            //$QueryNew .= $scQ;  //state
            $QueryNew .= $pQ;   //product
            $QueryNew .= $dfQ;  //date from
            $QueryNew .= $dtQ;  //date to

            $dataCharts = $_outbound->getOutboundQuery($QueryNew);
            $dataForCharts = $dataCharts;
        }
        $forCount = 0;
        foreach ($extendedTypes as $key => $value) {
            $forCount++;
            foreach ($dataForCharts as $result) {

                if (isset($statuses[$result['status']]) && $forCount == 1) {
                    $statuses[$result['status']]['num'] = $statuses[$result['status']]['num'] + 1;
                }
                if ($result['type'] == $key) {

                    if ($result['status'] == 7 || $result['status'] == 6 || $result['status'] == 12 || $result['status'] == 9) {

                        //$countAnswered++;
                        $statesOutbound[$result['state']]['count-' . $value['shortName']]++;
                        if ($result['status'] == 7 || $result['status'] == 12) {
                            //$outOrderSuccessful++;
                            $statesOutbound[$result['state']]['order-successful-' . $value['shortName']]++;
                        }
                    }
                    if ($result['status'] == 7 || $result['status'] == 12) {
                        //$orderSum  = $orderSum + ($result['newPrice'] / $result['exchange']);
                        $statesOutbound[$result['state']]['order-sum-' . $value['shortName']] = $statesOutbound[$result['state']]['order-sum-' . $value['shortName']] + ($result['newPrice'] / $result['exchange']);
                    }
                }
            }
        }

        $dataPanelPerformanceChart = array();
        foreach ($statesOutbound as $key => $stateOutb) {
            foreach ($extendedTypes as $exType) {

                $percent = round(($stateOutb['order-successful-' . $exType['shortName']] / $stateOutb['count-' . $exType['shortName']]) * 100, 2);

                $avg = round($stateOutb['order-sum-' . $exType['shortName']] / $stateOutb['count-' . $exType['shortName']], 2);

                $statesOutbound[$key]['avg-value-' . $exType['shortName']] = $avg;
                $statesOutbound[$key]['order-percent-' . $exType['shortName']] = $percent;
            }

        }
        //print_r($statesOutbound);die();
        /*
         * KRAJ STATISIKE ZA CHARTOVE ZA SVE DRZAVE
         */


        /*
         * POCETAK STATISIKE ZA CHART Reorder call broj Ordera
         */
        if ($type == 7) {

            $someProducts = array(
                248 => '',
                200 => '',
                3 => '',
                55 => '',
            );

            $productsCharts = array();
            foreach ($statesFromOutbound as $statee) {
                foreach ($someProducts as $key => $productt) {
                    $podaci[$statee['state']][$key] = 0;
                    $productsCharts = $podaci;
                }
            }

            //print_r($productsCharts);die();
            $totProducts = array(
                248 => array(
                    'countAnswered' => 0,
                    'outOrderSuccessfull' => 0,
                    'orderPercent' => 0
                ),
                200 => array(
                    'countAnswered' => 0,
                    'outOrderSuccessfull' => 0,
                    'orderPercent' => 0
                ),
                3 => array(
                    'countAnswered' => 0,
                    'outOrderSuccessfull' => 0,
                    'orderPercent' => 0
                ),
                55 => array(
                    'countAnswered' => 0,
                    'outOrderSuccessfull' => 0,
                    'orderPercent' => 0
                )
            );

            $QProducts = " " . $dfQ . $dtQ . $tQ;
            $productsForChart = $_outbound->getProductsOrderData($QProducts);

            foreach ($productsForChart as $podaci) {

                $productsCharts[$podaci['state']][$podaci['productID']] = round(($podaci['outOrderSuccessfull'] / $podaci['countAnswered']) * 100, 2);

                $totProducts[$podaci['productID']]['countAnswered'] = $totProducts[$podaci['productID']]['countAnswered'] + $podaci['countAnswered'];
                $totProducts[$podaci['productID']]['outOrderSuccessfull'] = $totProducts[$podaci['productID']]['outOrderSuccessfull'] + $podaci['outOrderSuccessfull'];
                $totProducts[$podaci['productID']]['orderPercent'] = round(($totProducts[$podaci['productID']]['outOrderSuccessfull'] / $totProducts[$podaci['productID']]['countAnswered']) * 100, 2);
            }
        }
        /*
         * KRAJ STATISIKE ZA CHART Reorder call broj Ordera
         */

        $headerTable = $_outbound->getOutboundHeaderData($Query, $period);
        $headerTable = $headerTable[0];
        $headerTable['totalDuration'] = gmdate('H:i:s', $headerTable['allDuration']);
        $headerTable['countFinish'] = $headerTable['status7'] + $headerTable['status12'];
        $headerTable['countCancell'] = $headerTable['status6'];
        $headerTable['countAnswered'] = $headerTable['status6'] + $headerTable['status7'] + $headerTable['status12'] + $headerTable['status9'];
        $headerTable['outOrderSuccessfull'] = $headerTable['status7'] + $headerTable['status12'];
        //$headerTable['answerPercent'] = round(($headerTable['countAnswered'] / $headerTable['callCount']) * 100, 2);
        $headerTable['perCall'] = round($headerTable['orderSum'] / $headerTable['countAnswered'], 2);
        $headerTable['avgCallDuration'] = gmdate('H:i:s', round($headerTable['allDuration'] / $headerTable['countCalled']));
        $headerTable['orderPercent'] = round(($headerTable['outOrderSuccessfull'] / $headerTable['countAnswered']) * 100, 2);
        $headerTable['perOrder'] = round($headerTable['orderSum'] / $headerTable['countFinish'], 2);
        $headerTable['avgOrderDuration'] = gmdate('H:i:s', round($headerTable['allOrderDuration'] / $headerTable['countOrderCalled']));
        $headerTable['upsellPercent'] = round(($headerTable['upsellsCount'] / $headerTable['countFinish']) * 100, 2);
        $headerTable['upsellValue'] = round($headerTable['upsellPriceDiff'] / $headerTable['countFinish'], 2);
        $headerTable['otherPercent'] = round(($headerTable['countOther'] / $headerTable['callCount']) * 100, 2);
        $headerTable['cancelPercent'] = round(($headerTable['countCancell'] / $headerTable['countAnswered']) * 100, 2);
        $headerTable['costpercall']         = $costpercall;
        $headerTable['costperorder']        = $costperorder;
        $headerTable['notCalled']           = $headerTable['status0'] + $headerTable['status13'];
        $headerTable['callMade']            = ($headerTable['callCount'] - $headerTable['notCalled'] - $statuses[10]['num'] - $statuses[14]['num'] );
        $headerTable['answerPercent']       = round(($headerTable['countAnswered'] / $headerTable['callMade']) * 100, 2);
        echo new Response($statuses[10]['num']);
        /*
         * Outbound Panel Performance chart
         */
        $stateCategories = "";
        foreach ($statesOutbound AS $key => $performance) {
            $stateCategories .= "'" . $key . "', ";
        }
        $ordFillBreakSeries = "";
        foreach ($statesOutbound AS $performance) {
            $ordFillBreakSeries .= $performance['avg-value-order-fill-break'] . ", ";
        }
        $cancelSeries = "";
        foreach ($statesOutbound AS $performance) {
            $cancelSeries .= $performance['avg-value-canceled-user'] . ", ";
        }
        $reordCallSeries = "";
        foreach ($statesOutbound AS $performance) {
            $reordCallSeries .= $performance['avg-value-reorder-call'] . ", ";
        }
        $undecCallSeries = "";
        foreach ($statesOutbound AS $performance) {
            $undecCallSeries .= $performance['avg-value-undecided-call'] . ", ";
        }
        $smsLinkSeries = "";
        foreach ($statesOutbound AS $performance) {
            $smsLinkSeries .= $performance['avg-value-sms-link'] . ", ";
        }
        $undecidedPresellSeries = "";
        foreach ($statesOutbound AS $performance) {
            $undecidedPresellSeries .= $performance['avg-value-undecided-presell'] . ", ";
        }

        /*
         * Reorder call Orders charts
         */
        $recallCategor = "";
        foreach ($productsCharts AS $key => $performance) {
            $recallCategor .= "'" . $key . "', ";
        }

        $recallSlimSeries = "";
        foreach ($productsCharts AS $order) {
            $recallSlimSeries .= $order['248'] . ", ";
        }
        $recallSlimSeries .= $totProducts ['248']['orderPercent'];

        $recallVeinSeries = "";
        foreach ($productsCharts AS $order) {
            $recallVeinSeries .= $order['200'] . ", ";
        }
        $recallVeinSeries .= $totProducts['200']['orderPercent'];

        $recallPhirSeries = "";
        foreach ($productsCharts AS $order) {
            $recallPhirSeries .= $order['3'] . ", ";
        }
        $recallPhirSeries .= $totProducts['3']['orderPercent'];

        $recallTmaxSeries = "";
        foreach ($productsCharts AS $order) {
            $recallTmaxSeries .= $order['55'] . ", ";
        }
        $recallTmaxSeries .= $totProducts ['55']['orderPercent'];

        /*
         * charts za tipove poziva 
         */
        $chartTypeShortName = "";
        $chartTypeName = "";
        $typeChartsCategories = "";
        $ordPercSeries = "";
        $avgcallValSeries = "";
        $conditionForChart = "";

        if (array_key_exists($type, $extendedTypes)) {

            foreach ($extendedTypes as $key => $value) {
                if ($type == $key) {
                    $chartTypeShortName = $value['shortName'];
                    $chartTypeName = $value['name'];
                    $conditionForChart = 1;


                    foreach ($statesOutbound AS $key => $performance) {
                        $typeChartsCategories .= "'" . $key . "', ";
                    }
                    $typeChartsCategories .= "'TOT'";

                    foreach ($statesOutbound AS $performance) {
                        $ordPercSeries .= $performance['order-percent-' . $value['shortName']] . ", ";
                    }
                    $ordPercSeries .= $headerTable['orderPercent'];
                    foreach ($statesOutbound AS $performance) {
                        $avgcallValSeries .= $performance['avg-value-' . $value['shortName']] . ", ";
                    }
                    $avgcallValSeries .= $headerTable['perCall'];

                }
            }
        }
//        /print_r($avgcallValSeries);

        $response = $this->render('statsout/outboundCharts.html.twig', array(


                '_states' => $_states,
                '_products' => $_products,
                '_type' => $type,
                '_types'      => $_getters->getOutboundTypesAction(),
                'from' => $from,
                'to' => $to,
                'stateCategories' => $stateCategories,
                'ordFillBreakSeries' => $ordFillBreakSeries,
                'cancelSeries' => $cancelSeries,
                'reordCallSeries' => $reordCallSeries,
                'undecCallSeries' => $undecCallSeries,
                'smsLinkSeries' => $smsLinkSeries,
                'undecidedPresellSeries' => $undecidedPresellSeries,
                'recallCategor' => $recallCategor,
                'recallSlimSeries' => $recallSlimSeries,
                'recallVeinSeries' => $recallVeinSeries,
                'recallPhirSeries' => $recallPhirSeries,
                'recallTmaxSeries' => $recallTmaxSeries,
                'chartTypeShortName' => $chartTypeShortName,
                'chartTypeName' => $chartTypeName,
                'typeChartsCategories' => $typeChartsCategories,
                'ordPercSeries' => $ordPercSeries,
                'avgcallValSeries' => $avgcallValSeries,
                'conditionForChart' => $conditionForChart,
                'headerTable' => $headerTable,
                'title' => $title,
            )
        );
        return $response;

    }

    public function operatorAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Operator Out';
        $conn = $this->get('database_connection');

        $_omg       = new OMG($conn);
        $_stats     = new STATS($conn);
        $_settings  = new Settings($conn);
        $_outbound  = new Outbound($conn);
        $_getters   = new GettersController($conn);



        $a_date = Date("Y-m-h");
        $godina = Date("Y");
        $mjesec = Date("m");
        $defaultDateFrom = $godina . "-" . $mjesec . "-01";
        $daysNum = date("Y-m-t", strtotime($a_date));
        $defaultDateTo = $daysNum;

        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }

        $state = $queryArr['state'];
        $type = $queryArr['ordType'];
        $user = $queryArr['user'];
        $group = $queryArr['group'];
        $from = $queryArr['from'];
        $to = $queryArr['to'];

        if (isset($state) && !empty($state)) { $scQ = " and phone_order_outbound.state = '$state' ";             } else { $scQ = "";  }
        if (isset($type) && !empty($type))   { $tQ = " and phone_order_outbound.type = '$type' ";                } else { $tQ = "";   }
        if (isset($user) && !empty($user))   { $uQ = " and phone_order_outbound.operator = $user ";              } else { $uQ = "";   }
        if (isset($from) && !empty($from))   { $dfQ = " and DATE(phone_order_outbound.called_time) >= '$from' "; } else { $from = date('Y-m-01');  $dfQ = " and DATE(phone_order_outbound.called_time) >= '$from' ";    }
        if (isset($to) && !empty($to))       { $dtQ = " and DATE(phone_order_outbound.called_time) <= '$to' ";   } else { $to = date('Y-m-d');     $dtQ =  " and DATE(phone_order_outbound.called_time) <= '$to' ";    }
        if (isset($group) && !empty($group)) { $grQ = " and phone_order_users.operatorGroup = $group ";  $grQ2 = " and id = $group ";    } else {   $grQ = "";   $grQ2 = " ";    }

        $random     = rand(1000,9999);
        $today      = date('Y-m-d');
        $exportFile = "(".$from."_".$to.")-".$random.".csv";

        $Query = " 1 ";  //default
        $Query .= $scQ;  //state
        $Query .= $tQ;   //Order type
        $Query .= $uQ;   //User
        $Query .= $grQ;  //Group
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to
        //print_r($Query);die();

        /***************** CHART QUERY SHOW TYPE **********************/
        $show = $queryArr['sType'];
        $rateT = $queryArr['rType'];
        if (isset($show) && !empty($show)) {
        } else {
            $show = "1";
        }
        if (isset($rateT) && !empty($rateT)) {
        } else {
            $rateT = "1";
        }

        if ($rateT == "1") {
            $chartHead = "Call centers success rates";
        } else {
            $chartHead = "Call centers cancel rates";
        }

        $_users = $_omg->getUsers();
        $_data = $_outbound->getDataOperator("*", $Query);  //koristim
        $_products = $_omg->getProductList("id, title", "1");

        $_centers = $_settings->getCallCenterList();

        $_call_centers = array();
        foreach ($_centers as $center) {
            if ($center['state'] != 'TE') {
                $_call_centers[] = $center;
            }
        }
        $_states = $_omg->getStates();

        $_call_centers = array();
        foreach ($_centers as $center) {
            if ($center['state'] != 'TE') {
                $_call_centers[] = $center;
            }
        }

        $myTableData = array();

        foreach ($_data as $operator) {

            $newOperator = $operator;
            $newOperator['orderedNum'] = $operator['status7'] + $operator['status12'];
            $newOperator['canceledNum'] = $operator['status6'];
            $newOperator['answeredNum'] = $operator['status6'] + $operator['status7'] + $operator['status12'] + $operator['status9'];

            $myTableData[$operator['ouid']] = $newOperator;
        }

        // ------------------ CHART SETUP ---------------------------------
        $dateType = "DATE(called_time)";
        $dateFormat = "Y-m-d";

        if ($show == "2") {
            $dateType = "DATE_FORMAT(called_time,'%Y-%m')";
            $dateFormat = "Y-m";
        } else if ($show == "3") {
            $dateType = "WEEK(called_time)";
            $dateFormat = "W";
        }
        // ------------------ CHART SETUP END -----------------------------
        $grouped_centers = $_settings->getCallCenterList($grQ2 . " GROUP BY name ");

        //centers withou 'TE'
        $groupedCentresCharts = array();
        foreach ($grouped_centers as $centerChart) {
            if ($centerChart['state'] != 'TE') {
                $groupedCentresCharts[] = $centerChart;
            }
        }

        //---------------------------------------------------------------------------------------------------------------------------------
        $getChartDataSve = "SELECT {$dateType} AS datum1, phone_order_callcenter.name AS center, count(*) as broj1, phone_order_callcenter.state
                    FROM phone_order_outbound
                    LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
                    LEFT JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
                    WHERE 1 {$dfQ} {$dtQ} {$grQ} {$uQ} and phone_order_callcenter.state != 'TE'
                    GROUP BY {$dateType},phone_order_callcenter.name
                    ORDER BY called_time DESC";
        $chart1 = $conn->fetchAll($getChartDataSve);
        //---------------------------------------------------------------------------------------------------------------------------------
        $getChartDataOrder = "SELECT {$dateType} AS datum2, phone_order_callcenter.name AS center, count(*) as broj2
                        FROM phone_order_outbound
                        LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
                        LEFT JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
                        WHERE 1 {$dfQ} {$dtQ} {$grQ} {$uQ} AND phone_order_outbound.status = 7 AND  phone_order_callcenter.state != 'TE'
                        GROUP BY {$dateType},phone_order_callcenter.name
                        ORDER BY called_time DESC";
        $chart2 = $conn->fetchAll($getChartDataOrder);
        //---------------------------------------------------------------------------------------------------------------------------------
        $getChartDataCancel = "SELECT {$dateType} AS datum3, phone_order_callcenter.name AS center, count(*) as broj3
                        FROM phone_order_outbound
                        LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
                        LEFT JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
                        WHERE 1 {$dfQ} {$dtQ} {$grQ} {$uQ} AND phone_order_outbound.status = 6 AND  phone_order_callcenter.state != 'TE'
                        GROUP BY {$dateType},phone_order_callcenter.name
                        ORDER BY called_time DESC";
        $chart3 = $conn->fetchAll($getChartDataCancel);
        //---------------------------------------------------------------------------------------------------------------------------------

        $startDate = Date($from);
        $utmstF = strtotime($from);
        $utmstT = strtotime($to);


        // SVI POZIVI PO CALL CENTRIMA ZA CHART
        $nizS = Array();
        $nizO = Array();
        $nizC = Array();

        while ($utmstF <= $utmstT) {

            $date = new \DateTime();
            // $date->setTimestamp($utmstF);
            $nizS[$date->format($dateFormat)] = Array();

            foreach ($chart1 AS $allData) {

                $nizS[$date->format($dateFormat)][$allData['center']] = 0;
            }
            $utmstF = $utmstF + 86400;
        }
        $nizO = $nizS;
        $nizC = $nizS;

        foreach ($chart1 AS $allData) {
            $addZero = "";
            if ($allData['datum1'] >= 0 && $allData['datum1'] < 10) {
                $addZero = "0";
            }
            if ($show == 3) {
                $nizS[$addZero . $allData['datum1']][$allData['center']] = $allData['broj1'];
            } else {
                $nizS[$allData['datum1']][$allData['center']] = $allData['broj1'];
            }
        }

        foreach ($chart2 AS $allData) {
            $addZero = "";
            if ($allData['datum2'] >= 0 && $allData['datum2'] < 10) {
                $addZero = "0";
            }
            if ($show == 3) {
                $nizO[$addZero . $allData['datum2']][$allData['center']] = $allData['broj2'];
            } else {
                $nizO[$allData['datum2']][$allData['center']] = $allData['broj2'];
            }
        }
//        print_r($chart1);
//        print_r($nizS);

        foreach ($chart3 AS $allData) {
            $addZero = "";
            if ($allData['datum3'] >= 0 && $allData['datum3'] < 10) {
                $addZero = "0";
            }
            if ($show == 3) {
                $nizC[$addZero . $allData['datum3']][$allData['center']] = $allData['broj3'];
            } else {
                $nizC[$allData['datum3']][$allData['center']] = $allData['broj3'];
            }
        }

        // TOTAL ZA SUCCESS PROCENAT CHARTA
        $utmstF = strtotime($from);
        $nizSve = Array();
        while ($utmstF <= $utmstT) {
            $date = new \DateTime();
            $date->setTimestamp($utmstF);
            $nizSve[$date->format($dateFormat)] = Array();

            foreach ($groupedCentresCharts AS $center) {
                if ($rateT == 1) {
                    $percent = round(($nizO[$date->format($dateFormat)][$center['name']] /($nizC[$date->format($dateFormat)][$center['name']] + $nizO[$date->format($dateFormat)][$center['name']])) * 100, 2);

                } else {
                    $percent = round(($nizC[$date->format($dateFormat)][$center['name']] / ($nizC[$date->format($dateFormat)][$center['name']] + $nizO[$date->format($dateFormat)][$center['name']])) * 100, 2);
                }

                $nizSve[$date->format($dateFormat)][$center['name']] = $percent;
            }
            $utmstF = $utmstF + 86400;
        }

        //kategorije chart-a

        $ccategories = "";
        foreach ($nizSve AS $key => $val) {
            $ccategories .= "'$key', ";
        }

        // series - parametar charta
        $cseries = "";
        foreach ($groupedCentresCharts AS $center) {
            $cName = $center["name"];
            $cseries .= "{ name: '$cName', data: [";

            foreach ($nizSve AS $key => $ccenters) {
                foreach ($ccenters AS $center => $percData) {
                    if ($center == $cName) {
                        $cseries .= $percData . ", ";
                    }
                }
            }
            $cseries .= "]}, ";
        }

        $html = '<table class="dayView compact" id="example" style="font-size: 12px;">
                        <thead style="cursor:pointer;">
                        <tr >
                            <td width="20px">#</td>
                            <td >Country</td>
                            <td >Operator</td>
                            <td >Calls</td>
                            <td >Answered</td>
                            <td >Answered (%)</td>
                            <td >Duration</td>
                            <td >Avg Call Duration</td>
                            <td >Orders</td>
                            <td >Order succesfull</td>
                            <td >Upsells (%)</td>
                            <td >Order Cancelled</td>
                            <td >Other</td>
                            <td >Order (%)</td>
                            <td >Cancel (%)</td>
                            <td >Other (%)</td>
                        </tr>
                        </thead>
                        <tbody id="tabela">';

        $counter = 0;
        $showDiff = "colDiff";

        $tCalls         = "";
        $tAnswer        = "";
        $tOrders        = "";
        $tSuccess       = "";
        $tOther         = "";
        $tUpsellCp      = "";
        $tCancell       = "";
        $tOrderCp       = "";
        $tCancelCp      = "";
        $tOtherCp       = "";
        $tDurations     = "";
        $tAvgDuration   = "";

        foreach ($myTableData as $row) {

            $datum = substr($row['called_time'], 0, 10);
            $counter++;
            $totalOrder = $row['orderedNum'] + $row['canceledNum'];
            $answPerc = ($row['answeredNum'] / $row['callNums']) * 100;
            $orderPerc = ($row['orderedNum'] / $row['answeredNum']) * 100;
            $cancelPerc = ($row['canceledNum'] / $row['answeredNum']) * 100;
            $otherPerc = ($row['otherNum'] / $row['callNums']) * 100;
            $splitTime = $row['duration'];
            $upsellPerc = ($row['upsells'] / $row['orderedNum']) * 100;

            $duration = $_getters->getHoursMinuteSecunds($row['duration']);

            //$avgDurationUnix = (int)$row['duration'] / (int)$row['callNums'];
            $avgDuration = $_getters->getHoursMinuteSecunds(round($row['duration'] / (int)$row['callNums'],2));

            //RACUNANJE TOTALA U FOOTERU

            $tCalls = $tCalls + $row['callNums'];
            $tAnswer = $tAnswer + $row['answeredNum'];
            $tOrders = $tOrders + $totalOrder;
            $tSuccess = $tSuccess + $row['orderedNum'];
            $tOther = $tOther + $row['otherNum'];
            $tUpsellCp = $tUpsellCp + $row['upsells'];
            $tCancell = $tCancell + $row['canceledNum'];
            $tOrderCp = $tOrderCp + $orderPerc;
            $tCancelCp = $tCancelCp + $cancelPerc;
            $tOtherCp = $tOtherCp + $otherPerc;
            $tDurationsSec = $tDurationsSec + $row['duration'];



            $html .= '<tr style="margin-top:1px; cursor:pointer;">
                        <td>' . $counter . '</td>
                        <td>' . $row['state'] . '</td>
                        <td><a href=inspectleturl' . $row['username'] . '%22%7D&tags=%7B%22paneopen%22%3A%22basic%22%2C%22tagslist%22%3A%5B%7B%22tag%22%3A%22state%22%2C%22value%22%3A%22%22%7D%5D%2C%22operator%22%3A%22and%22%7D" target="_blank">' . $row['username'] . '</a></td>
                        <td>' . $row['callNums'] . '</td>
                        <td>' . $row['answeredNum'] . '</td>
                        <td>' . number_format($answPerc, 2) . ' %</td>
                        <td>' . $duration . '</td>
                        <td>' . $avgDuration . '</td>
                        <td>' . $totalOrder . '</td>
                        <td class="greenLine">' . $row['orderedNum'] . '</td>
                        <td>' . number_format($upsellPerc, 2) . ' %</td>
                        <td class="redLine">' . $row['canceledNum'] . '</td>
                        <td>' . $row['otherNum'] . '</td>
                        <td class="' . $showDiff . '">' . number_format($orderPerc, 2) . ' %</td>
                        <td class="' . $showDiff . '">' . number_format($cancelPerc, 2) . ' %</td>
                        <td class="' . $showDiff . '">' . number_format($otherPerc, 2) . ' %</td>
                    </tr>';
        }
        $tDurations = $_getters->getHoursMinuteSecunds($tDurationsSec);
        $tUpsellsP = ($tUpsellCp / $tSuccess) * 100;
        $tOrdersP = ($tSuccess / $tAnswer) * 100;
        $tCancellP = ($tCancell / $tAnswer) * 100;
        $tOtherP = ($tOther / $tCalls) * 100;
        $tAvgDuration = $_getters->getHoursMinuteSecunds(round($tDurationsSec/$tCalls,2));
        //$avgDur = $tAvgDuration / $counter;
        $tAnswPerc = ($tAnswer / $tCalls) * 100;

        $html .= '</tbody>
                  <tfoot>
                  <td colspan="3" style="text-align:right;">TOTAL:</td>
                  <td>' . $tCalls . '</td>
                  <td>' . $tAnswer . '</td>
                  <td>' . number_format($tAnswPerc, 2) . '%</td>
                  <td>' . $tDurations . '</td>
                  <td>' . $tAvgDuration . '</td>
                  <td>' . $tOrders . '</td>
                  <td>' . $tSuccess . '</td>
                  <td>' . number_format($tUpsellsP, 2) . '%</td>
                  <td>' . $tCancell . '</td>
                  <td>' . $tOther . '</td>
                  <td>' . number_format($tOrdersP, 2) . '%</td>
                  <td>' . number_format($tCancellP, 2) . '%</td>
                  <td>' . number_format($tOtherP, 2) . '%</td>
                  </tfoot>
              </table>';

        $response = $this->render('statsout/operator.html.twig', array(

                '_html'         => $html,
                '_users'        => $_users,
                '_call_centers' => $_call_centers,
                '_states'       => $_states,
                '_types'        => $_getters->getOutboundTypesAction(),
                'charthead'     => $chartHead,
                'ccategories'   => $ccategories,
                'cseries'       => $cseries,
                'from'          => $from,
                'to'            => $to,
                'title'         => $title,
                'random'        => $random,
                'exportFile'    => $exportFile)
        );
        return $response;
    }

    public function validationAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Validation List';
        $conn = $this->get('database_connection');

        $_omg = new OMG($conn);
        $_outbound = new Outbound($conn);

        $yesterday = Date("Y-m-d", strtotime('-1 days'));
        $today = Date("Y-m-d");

        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }

        $state = $queryArr['state'];
        $num = $queryArr['ordNum'];
        $from = $queryArr['from'];
        $to = $queryArr['to'];
        $phone = $queryArr['phone'];

        if (isset($state) && !empty($state)) {
            $sQ = " and phone_order_validation.state = '$state' ";
        } else {
            $sQ = "";
        }
        if (isset($num) && !empty($num)) {
            $nQ = " ORDER BY id DESC LIMIT $num ";
        } else {
            $num = "100";
            $nQ = " ORDER BY id DESC LIMIT $num ";
        }
        if (isset($from) && !empty($from)) {
            $dfQ = " and DATE(phone_order_validation.dateTime) >= '$from' ";
        } else {
            $from = $yesterday;
            $dfQ = " and DATE(phone_order_validation.dateTime) >= '$yesterday' ";
        }
        if (isset($to) && !empty($to)) {
            $dtQ = " and DATE(phone_order_validation.dateTime) <= '$to' ";
        } else {
            $to = $today;
            $dtQ = " and DATE(phone_order_validation.dateTime) <= '$to' ";
        }
        if (isset($phone) && !empty($phone)) {
            $phoneQ = " AND phone_order_validation.phone LIKE '%$phone%' ";
        } else {
            $phoneQ = "";
        }

        $Query = " 1 ";   //default
        $Query .= $sQ;    //state
        $Query .= $dfQ;   //date from
        $Query .= $dtQ;   //date to
        $Query .= $phoneQ; //phone criteria
        $Query .= $nQ;    //Order Num

        $_states = $_omg->getStates();
        $_data = $_outbound->getValidationList($Query);
        $areaCodes = array("HR" => "385",
            "BA" => "387",
            "RS" => "381",
            "MK" => "389",
            "SI" => "386",
            "BG" => "359",
            "IT" => "39",
            "SK" => "421",
            "PL" => "48",
            "GR" => "30",
            "LV" => "371",
            "LT" => "370",
            "AT" => "43",
            "HU" => "36",
            "CZ" => "420",
            "RO" => "40",
            "DE" => "49",
            "EE" => "372",
            "FR" => "33",
            "BE" => "32",
            "ES" => "34",
            "AL" => "355",
            "XK" => "377",
            "VN"=>"84",
            "NG"=>"234"
        );
        $vTypeTitle = array("1" => "Black List",
            "2" => "White List");
        $sourceTitle = array("0" => "Unknown reason",
            "1" => "FillBrake - cancel on start",
            "11" => "FillBrake - Wrong person",
            "12" => "FillBrake - Dont want to talk",
            "13" => "FillBrake - cancel on end",
            "4" => "Reorded call - cancel on start",
            "41" => "Reorded call - Wrong person",
            "42" => "Reorded call - Dont want to talk",
            "43" => "Reorded call - cancel on end",
            "5" => "Bulk call - cancel on start",
            "51" => "Bulk call - Wrong person",
            "52" => "Bulk call - Dont want to talk",
            "53" => "Bulk call - cancel on end",
            "6" => "Undecided call - cancel on start",
            "61" => "Undecided call - Wrong person",
            "62" => "Undecided call - Dont want to talk",
            "63" => "Undecided call - cancel on end"
        );

        $html = '<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr >
                      <td width="20px">#</td>
                      <td >State</td>
                      <td >Telephone</td>
                      <td >List Type</td>
                      <td >Date/Time</td>
                      <td >Source/Reason</td>
                    </tr>
                   </thead>
                   <tbody id="tabela">';
        $counter = 0;
        foreach ($_data as $row) {

            $counter++;
            $html .= '<tr style="margin-top:1px; cursor:pointer;">
                        <td>' . $counter . '</td>
                        <td>' . $row['stateTitle'] . '</td>
                        <td>' . $row['phone'] . '</td>
                        <td>' . $vTypeTitle[$row['vType']] . '</td>
                        <td>' . $row['dateTime'] . '</td>
                        <td>' . $sourceTitle[$row['source']] . '</td>
                      </tr>';
        }
        $html .= '</tbody>
              </table>';

        $response = $this->render('statsout/validation.html.twig', array(

                '_html' => $html,
                '_states' => $_states,
                'from' => $from,
                'to' => $to,
                'title' => $title,
                'phone' => $phone)
        );
        return $response;
    }

    public function callCenterAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Call center Out';
        $conn = $this->get('database_connection');

        $_omg       = new OMG($conn);
        $_stats     = new STATS($conn);
        $_settings  = new Settings($conn);
        $_outbound  = new Outbound($conn);
        $_getters   = new GettersController($conn);

        $a_date = Date("Y-m-h");
        $godina = Date("Y");
        $mjesec = Date("m");
        $defaultDateFrom = $godina . "-" . $mjesec . "-01";
        $daysNum = date("Y-m-t", strtotime($a_date));
        $defaultDateTo = $daysNum;

        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }

        $type = $queryArr['ordType'];
        $group = $queryArr['group'];
        $from = $queryArr['from'];
        $to = $queryArr['to'];

        if (isset($type) && !empty($type))                  { $tQ = " and phone_order_outbound.type = '$type' ";                } else { $tQ = "";      }
        if (isset($group) && !empty($group) && $group == 1) { $groupBy = " GROUP BY phone_order_callcenter.id ";                } else { $group = '';   $groupBy = " GROUP BY phone_order_callcenter.main_call_center_id ";  }
        if (isset($from) && !empty($from))                  { $dfQ = " and DATE(phone_order_outbound.called_time) >= '$from' "; } else { $from = $defaultDateFrom;   $dfQ = " and DATE(phone_order_outbound.called_time) >= '$from' ";   }
        if (isset($to) && !empty($to))                      { $dtQ = " and DATE(phone_order_outbound.called_time) <= '$to' ";   } else { $to = $defaultDateTo;       $dtQ = " and DATE(phone_order_outbound.called_time) <= '$to' ";     }

        $Query = " 1 ";  //default
        $Query .= $tQ;   //Order type
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to

        $mainQuery = $Query . " and phone_order_callcenter.state != 'TE' " . $groupBy;

        $_data = $_outbound->getDataOperatorCallCenter("*", $mainQuery);  //koristim
        $_centers = $_settings->getCallCenterList(" GROUP BY name ");

        $call_centers = array();
        foreach ($_centers as $center) {
            if ($center['state'] != 'TE') {
                $call_centers[] = $center;
            }
        }

        $myTableData = array();

        foreach ($_data as $operator) {

            $newOperator = $operator;
            $newOperator['orderedNum'] = $operator['status7'] + $operator['status12'];
            $newOperator['canceledNum'] = $operator['status6'];
            $newOperator['answeredNum'] = $operator['status6'] + $operator['status7'] + $operator['status12'] + $operator['status9'];

            $myTableData[$operator['ouid']] = $newOperator;
        }
        //moguce da ni ne treba
        $call_centers = array();
        $groupedCentresCharts = array();
        foreach ($_centers as $center) {

            $call_centers[] = $center;
            $groupedCentresCharts[] = $center;
        }
       // print_r($myTableData);die();
        $html = '<table class="dayView compact" id="example" style="font-size: 12px;">
                        <thead style="cursor:pointer;">
                        <tr>
                            <td width="20px">#</td>';
        if ($group == 1) {
            $html .= '<td>Country</td>';
        }
        $html .= '<td >Call center</td>
                             <td >Calls</td>
                             <td >Answered</td>
                             <td >Answered (%)</td>
                             <td >Duration</td>
                             <td >Avg Call Duration</td>
                             <td >Orders</td>
                             <td >Order succesfull</td>
                             <td >Upsells </td>
                             <td >Upsells (%)</td>
                             <td >Order Cancelled</td>
                             <td >Other</td>
                             <td >Order (%)</td>
                             <td >Cancel (%)</td>
                             <td >Other (%)</td>
                         </tr>
                         </thead>
                         <tbody id="tabela">';
        $counter = 0;
        $showDiff = "colDiff";

        $tCalls         = "";
        $tUpsell        = "";
        $tCancel        = "";
        $tOther         = "";
        $tAnswer        = "";
        $tSuccess       = "";
        $tOrders        = "";
        $tDurations     = "";
        $tAvgDuration   = "";

        foreach ($myTableData as $row) {
            $counter++;

            $splitTime = $row['duration'];
            $datum = substr($row['called_time'], 0, 10);

            $callCount = $row['callNums'];
            $upsellCount = $row['upsells'];
            $orderCount = $row['orderedNum'];
            $cancelCount = $row['canceledNum'];
            $otherCount = $row['otherNum'];
            $answeredCount = $row['answeredNum'];

            $totalOrder = $orderCount + $cancelCount;

            $answPerc = ($answeredCount / $callCount) * 100;
            $duration = $_getters->getHoursMinuteSecunds($row['duration']);
            $avgDuration = $_getters->getHoursMinuteSecunds(round($row['duration'] / (int)$answeredCount),2);

            $upsellPerc = ($upsellCount / $orderCount) * 100;
            $orderPerc = ($orderCount / $answeredCount) * 100;
            $cancelPerc = ($cancelCount / $answeredCount) * 100;
            $otherPerc = ($otherCount / $callCount) * 100;


            //RACUNANJE TOTALA U FOOTERU

            $tCalls = $tCalls + $callCount;
            $tUpsell = $tUpsell + $upsellCount;

            $tCancel = $tCancel + $cancelCount;
            $tOther = $tOther + $otherCount;
            $tAnswer = $tAnswer + $answeredCount;

            $tSuccess = $tSuccess + $orderCount;
            $tOrders = $tOrders + $totalOrder;

            $tDurationsSec = $tDurationsSec + $row['duration'];
            $tDurations = $_getters->getHoursMinuteSecunds($tDurationsSec);

            $html .= '<tr style="margin-top:1px; cursor:pointer;">
                        <td>' . $counter . '</td>';
            if ($group == 1) {
                $html .= '<td>' . $row["state"] . '</td>';
            }
            $html .= '<td>' . $row["callcentar"] . '</td>
                        <td>' . $callCount . '</td>
                        <td >' . $answeredCount . '</td>
                        <td >' . number_format($answPerc, 2) . '%</td>
                        <td >' . $duration . '</td>
                        <td >' . $avgDuration . '</td>
                        <td >' . $totalOrder . '</td>
                        <td class="greenLine">' . $orderCount . '</td>
                        <td >' . $upsellCount . '</td>
                        <td >' . number_format($upsellPerc, 2) . '%</td>
                        <td class="redLine">' . $cancelCount . '</td>
                        <td >' . $otherCount . '</td>
                        <td class="' . $showDiff . '">' . number_format($orderPerc, 2) . '%</td>
                        <td class="' . $showDiff . '">' . number_format($cancelPerc, 2) . '%</td>
                        <td class="' . $showDiff . '">' . number_format($otherPerc, 2) . '%</td>
                    </tr>';
        }

        
//        print_r($html);

        $tAvgDuration = $_getters->getHoursMinuteSecunds(round($tDurationsSec / $tAnswer),2);
        $tUpsellsP = ($tUpsell / $tSuccess) * 100;
        $tOrdersP = ($tSuccess / $tAnswer) * 100;
        $tCancelP = ($tCancel / $tAnswer) * 100;
        $tOtherP = ($tOther / $tCalls) * 100;
        //$avgDur = $tAvgDuration / $counter;
        $tAnswPerc = ($tAnswer / $tCalls) * 100;

        $html .= ' </tbody>
                <tfoot>';
        if ($group == 1) {
            $span = 3;
        } else {
            $span = 2;
        }
        $html .= '<td colspan="' . $span . '" style="text-align:right;">TOTAL:</td>
                <td>' . $tCalls . '</td>
                <td>' . $tAnswer . '</td>
                <td>' . number_format($tAnswPerc, 2) . '%</td>
                <td>' . $tDurations . '</td>
                <td>' . $tAvgDuration . '</td>
                <td>' . $tOrders . '</td>
                <td>' . $tSuccess . '</td>
                <td>' . $tUpsell . '</td>
                <td>' . number_format($tUpsellsP, 2) . '%</td>
                <td>' . $tCancel . '</td>
                <td>' . $tOther . '</td>
                <td>' . number_format($tOrdersP, 2) . '%</td>
                <td>' . number_format($tCancelP, 2) . '%</td>
                <td>' . number_format($tOtherP, 2) . '%</td>
                </tfoot>
            </table>';


        $response = $this->render('statsout/callCenter.html.twig', array(

                '_html'   => $html,
                '_types'  => $_getters->getOutboundTypesAction(),
                'from'    => $from,
                'to'      => $to,
                'title'   => $title,)
        );
        return $response;

    }

    public function projectedStatisticAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Projected Statistic Out';
        $conn = $this->get('database_connection');

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);
        $_outbound  = new Outbound($conn);
        $_getters   = new GettersController($conn);

        $a_date = Date("Y-m-h");
        $godina = Date("Y");
        $mjesec = Date("m");
        $defaultDateFrom = $godina . "-" . $mjesec . "-01";
        $daysNum = date("Y-m-t", strtotime($a_date));
        $defaultDateTo = $daysNum;


        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }
        $tableType = $queryArr['tableType'];
        $type      = $queryArr['ordType'];
        $product   = $queryArr['product'];
        $state     = $queryArr['state'];
        $from      = $queryArr['from'];
        $to        = $queryArr['to'];

        if (isset($tableType) && !empty($tableType)) { $groupBy = " GROUP BY phone_order_outbound.productID ";           } else { $tableType = "";  $groupBy = " GROUP BY phone_order_outbound.state ";   }
        if (isset($type) && !empty($type))           { $tQ = " and phone_order_outbound.type = '$type' ";                } else { $type = "";       $tQ = "";   }
        if (isset($product) && !empty($product))     { $prQ = " and phone_order_outbound.productID = '$product' ";       } else { $product = "";    $prQ = "";  }
        if (isset($state) && !empty($state))         { $scQ = " and phone_order_outbound.state = '$state' ";             } else { $state = "";      $scQ = "";  }
        if (isset($from) && !empty($from))           { $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' ";  } else { $from = $defaultDateFrom;  $dfQ = " and DATE(phone_order_outbound.submitDate) >= '$from' ";   }
        if (isset($to) && !empty($to))               { $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";    } else { $to = $defaultDateTo;      $dtQ = " and DATE(phone_order_outbound.submitDate) <= '$to' ";      }

        $Query = " ";  //default
        $Query .= $tQ;  //Order type
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to

        if ($tableType == "") {
            $Query .= $prQ;  //product
        }
        if ($tableType == 1) {
            $Query .= $scQ;  //state
        }
        $Query .= $groupBy;  //GROUP BY


        $_products = $_omg->getProductList("id, title", "1");
        $_states = $_omg->getStates(" AND code2 != 'TE' and code2 != 'AZ'  and code2 != 'AL'  and code2 != 'XK'");
        $_outbound_result = $_outbound->getDataForNewOutboundStats($Query);
        //print_r($_states);
        $mainTable = array();
        $newProducts = array();

        $outboundChartRedirectLink = "/statsout/outboundCharts?";

        if ($tableType == "") {
            $outboundChartRedirectLink .= "ordType=" . $type . "&product=" . $product . "&from=" . $from . "&to=" . $to;
            foreach ($_states as $prdc) {
                $mainTable[$prdc['code2']] = array();
            }
        }


        if ($tableType == 1) {
            $outboundChartRedirectLink .= "ordType=" . $type . "&country=" . $state . "&from=" . $from . "&to=" . $to;
            foreach ($_products as $prdc) {
                $mainTable[$prdc['id']] = array();
                $newProducts[$prdc['id']] = $prdc['title'];
            }
        }
        //print_r($_outbound_result);
        foreach ($_outbound_result as $result) {
            $newResult = $result;
            $newResult['countFinish'] = $result['status7'] + $result['status12'];
            $newResult['countCancell'] = $result['status6'];
            $newResult['countAnswered'] = $result['status6'] + $result['status7'] + $result['status12'] + $result['status9'];
            $newResult['outOrderSuccessfull'] = $result['status7'] + $result['status12'];

            if ($tableType == "") {
                $mainTable[$result['state']] = $newResult;

            }
            if ($tableType == "1") {
                $mainTable[$result['productID']] = $newResult;
            }
        }

        $html = '<table class="dayView compact" id="example" style="font-size: 12px;">
                    <thead style="cursor:pointer;">
                    <tr>
                        <td width="20px">#</td>';
        if ($tableType == "") {
            $html .= '<td >Country</td>';
        }
        if ($tableType == 1) {
            $html .= '<td >Product</td>';
        }

        $html .= '<td >% Orders</td>
                          <td ># Orders</td>
                          <td ># Upsell</td>
                          <td >% Cancell</td>
                          <td ># Cancell</td>
                          <td ># Total calls</td>
                          <td ># Answered</td>
                          <td >% Other</td>
                          <td >Sum order value</td>
                          <td >Avg.call value</td>
                          <td >Avg.order value</td>
                          <td ># RE</td>
                          <td style="width: 120px;" >Avg. projection ord. value</td>
                          <td >SUM projection</td>
                      </tr>
                      </thead>
                      <tbody id="tabela">';
        $counter = 0;
        $showDiff = "colDiff";

        $tOrders        = "";
        $tUpsell        = "";
        $tCancell       = "";
        $tSumOrder      = "";
        $tSumRe         = "";
        $tSumProjection = "";
        $tCallCount     = "";
        $tCountOther    = "";
        $tCountFinish   = "";
        $tCountAnswered = "";
        foreach ($mainTable as $row) {
            //print_r($tableType);die();
            if ((!empty ($row['state']) && $tableType == "") || (!empty ($row['productID']) && $tableType == 1)) {
                $counter++;

                $countCancell = $row['countCancell'];
                $countAnswered = $row['countAnswered'];
                $countFinish = $row['countFinish'];
                $callCount = $row['callNums'];
                $countOther = $row['countOther'];

                $countFinish = $row['countFinish'];
                $orderPercent = round(($countFinish / $countAnswered) * 100, 2);
                $upsellCount = $row['upsellsCount'];
                $orderSum = $row['orderSum'];
                $perCall = round($orderSum / $countAnswered, 2);
                $perOrder = round($orderSum / $countFinish, 2);
                $sumStatusRe = $row['countRe'];
                $sumProjection = $orderSum - $sumStatusRe * $perOrder - $countAnswered;
                $otherPercent = round(($countOther / $callCount) * 100, 2);
                $projectionOrderValue = round($sumProjection / $countFinish, 2);
                $cancelPercent = round(($countCancell / $countAnswered) * 100, 2);

                //RACUNANJE TOTALA U FOOTERU
                $tCountFinish = $tCountFinish + $countFinish;
                $tCountAnswered = $tCountAnswered + $countAnswered;
                $tOrders = $tOrders + $countFinish;
                $tUpsell = $tUpsell + $upsellCount;
                $tCancell = $tCancell + $countCancell;
                $tSumOrder = $tSumOrder + $orderSum;
                $tSumRe = $tSumRe + $sumStatusRe;
                $tSumProjection = $tSumProjection + $sumProjection;
                $tCallCount = $tCallCount + $callCount;
                $tCountOther = $tCountOther + $countOther;

                $html .= ' <tr style="margin-top:1px; cursor:pointer;">
                            <td>' . $counter . '</td>';
                if ($tableType == "") {
                    $html .= '<td>' . $row['state'] . '</td>';
                }
                if ($tableType == 1 && array_key_exists($row['productID'], $newProducts)) {
                    $html .= '<td>' . $newProducts[$row['productID']] . '</td>';
                }

                $html .= '<td>' . number_format($orderPercent, 2) . ' %</td>
                          <td style="background-color: ' . $this->ordersChangeCollor($countFinish) . '">' . $countFinish . '</td>
                          <td>' . $upsellCount . '</td>
                          <td>' . number_format($cancelPercent, 2) . ' %</td>
                          <td>' . $countCancell . '</td>
                          <td>' . $callCount . '</td>
                          <td>' . $countAnswered . '</td>
                          <td>' . number_format($otherPercent, 2) . ' %</td>
                          <td style="text-align: right;">€ ' . number_format($orderSum, 2) . '</td>
                          <td style="text-align: right; background-color: ' . $this->avgCallChangeCollor($perCall) . '">€ ' . number_format($perCall, 2) . '</td>
                          <td style="text-align: right;">€ ' . number_format($perOrder, 2) . '</td>
                          <td>' . $sumStatusRe . '</td>
                          <td style="text-align: right;">€ ' . number_format($projectionOrderValue, 2) . '</td>
                          <td style="text-align: right; background-color: ' . $this->sumChangeCollor($sumProjection) . '">€ ' . number_format($sumProjection, 2) . '</td>
                      </tr>';

            }
        }
        $tOrdersP = ($tCountFinish / $tCountAnswered) * 100;
        $tOtherP = round(($tCountOther / $tCallCount) * 100, 2);
        $tAvgCallValue = round($tSumOrder / $tCountAnswered, 2);
        $tAvgOrderValue = round($tSumOrder / $tCountFinish, 2);
        $tProjectionOrdValue = round($tSumProjection / $tCountFinish, 2);
        $tCancelP = round(($tCancell / $tCountAnswered) * 100, 2);

        $html .= '</tbody>
                    <tfoot style="text-align: center">
                    <td colspan="2" style="text-align:right;">TOTAL:</td>
                    <td>' . number_format($tOrdersP, 2) . ' %</td>
                    <td>' . $tOrders . '</td>
                    <td>' . $tUpsell . '</td>
                    <td>' . number_format($tCancelP, 2) . ' %</td>
                    <td>' . $tCancell . '</td>
                    <td>' . $tCallCount . '</td>
                    <td>' . $tCountAnswered . '</td>
                    <td>' . number_format($tOtherP, 2) . ' %</td>
                    <td style="text-align: right">€ ' . number_format($tSumOrder, 2) . '</td>
                    <td style="text-align: right">€ ' . number_format($tAvgCallValue, 2) . '</td>
                    <td style="text-align: right">€ ' . number_format($tAvgOrderValue, 2) . '</td>
                    <td>' . $tSumRe . '</td>
                    <td style="text-align: right">€ ' . number_format($tProjectionOrdValue, 2) . '</td>
                    <td style="text-align: right">€ ' . number_format($tSumProjection, 2) . '</td>
                    </tfoot>
                </table>';

        $response = $this->render('statsout/projectedStatistic.html.twig', array(

                '_html'     => $html,
                '_products' => $_products,
                '_states'   => $_states,
                '_types'    => $_getters->getOutboundTypesAction(),
                'tableType' => $tableType,
                'from'      => $from,
                'to'        => $to,
                'title'     => $title,
                'outboundChartRedirectLink' => $outboundChartRedirectLink)
        );
        return $response;


    }

    /**
     * @Template(engine="twig")
     */

    public function notCalledAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        $title    = 'Not called numbers report';

        $today = Date("Y-m-d");
        $datum = new \DateTime($today);
        $weekToday = $datum->format("W");
        $yearToday = $datum->format("Y");

        $weekStart = $weekToday-1;
        $weekNum = Array();
        $weekCount = 11;
        for ($i = 1; $i<13; $i++ ){
            $weekNum[$i] = $weekStart - $weekCount;

            $weekCount--;
        }


        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }


        $conn = $this->get('database_connection');
        $getters    = new GettersController($conn);

        $_states    = $getters->getActiveStatesAction();

        $_data = $conn->fetchAll("SELECT state, WEEK(DATE(submitDate),1) AS weekNum, 
                                    COUNT(*) as totalCalls,
                                    SUM(IF(status = 13, 1, 0)) as notCalled
                                    FROM phone_order_outbound
                                    WHERE WEEK(DATE(submitDate),1) >= '{$weekNum[1]}'
                                    AND YEAR(submitDate) = '{$yearToday}'
                                    GROUP BY state, WEEK(DATE(submitDate),1)");


        $allData    = Array();
        $avgWeek    = Array();
        $numStates  = 0;

        foreach($_states AS $eachstate){
            $allData[$eachstate['code2']] = Array();
            $numStates++;
        }

        foreach($_data AS $row){
            $rowPercent = round($row['notCalled'] / $row['totalCalls'] * 100, 2);

            $allData[$row['state']][$row['weekNum']] = $rowPercent;

        }
        //echo new Response(json_encode($allData));

        $html    ='<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr>
                        <td width="20px">#</td>
                        <td >State</td>
                        <td >Provider</td>';


        foreach ($weekNum AS $i=>$j) {
            $dateWs     = date( "d", strtotime($yearToday."W".$j."1"));
            $dateWe     = date( "d.m.", strtotime($yearToday."W".$j."7"));

            $dateRes    = $dateWs."-".$dateWe;

            $html    .= '<td>' . $j . '<BR>'.$dateRes.'</td>';
        }

        $html    .= ' <td>Average by provider</td>
                     </tr>
                     </thead>
                      <tbody id="tabela">';

        $counter    = 0;
        $totalSMS   = 0;
        $showColor  = "";
        $totalPrice = 0.00;
        $totalMessages   = 0;

        foreach ($_states as $eachState){
            $counter++;

            $html    .= '<tr style="margin-top:1px; cursor:pointer;">
                            <td class="'.$showColor.'">'.$counter.'</td>
                            <td class="'.$showColor.'">'.$eachState["code2"].'</td>
                            <td class="'.$showColor.'">'.$eachState["callCenterName"].'</td>';

            $avgProvider = 0;
            foreach ($weekNum AS $k=>$v) {



                $finalValue = $allData[$eachState['code2']][$v];
                $stValue = $finalValue;
                if(!isset($finalValue) || empty($finalValue)){
                    $finalValue = 0;
                }
                $avgWeek[$v]    = $avgWeek[$v] + $finalValue;
                $avgProvider    = $avgProvider + $finalValue;

                $klasa  = "";
                if ($stValue < 4.99) {
                    $klasa = ' class="green3" ';
                } else if ($stValue >= 4.99 && $stValue < 9.99) {
                    $klasa = ' class="yell3" ';
                } else if ($stValue > 9.99) {
                    $klasa = ' class="red3" ';
                }

                $html    .= '<td '.$klasa.'>'.$finalValue.'%</td>';
            }

            $html    .= '<td class="'.$showColor.'"><strong>'.round($avgProvider/12, 2).'%</strong></td>';
            $html    .='</tr>';
        }

        $html   .= '</tbody><tfoot><td colspan="3" style="text-align: center;"><strong>AVERAGE BY WEEK</strong></td>';

        foreach ($weekNum AS $l=>$m) {
            $html    .= '<td style="text-align: center;"><strong>' . round($avgWeek[$m]/$numStates, 2) . '%</strong></td>';
        }

        $html   .= '<td></td></tfoot></table>';
        $test = "";
        return $this->render('statsout/notCalled.html.twig', array('test' => $test,
            '_html' => $html,
            '_states' => $_states,
            'title' => $title));
    }

    function sumChangeCollor($value)
    {
        $collor = '';
        if ($value < 0) {
            //crvena
            $collor = '#FF7575';
        } else {
            //zeleno
            $collor = '#5EAE9E';
        }
        return $collor;
    }

    function avgCallChangeCollor($value)
    {
        if (0 <= $value && $value <= 1.5) {
            //crvena
            $collor = '#FF7575';
        } elseif (1.5 < $value && $value <= 5) {
            //roze
            $collor = '#E697E6';
        } elseif (5 < $value && $value <= 10) {
            //svetlo zeleno
            $collor = '#93EEAA';
        } else {
            //zeleno
            $collor = '#5EAE9E';
        }
        return $collor;
    }

    function ordersChangeCollor($value)
    {
        $collor = '';

        if ($value <=0) {
            //crvena
            $collor = '#FF7575';
        } elseif (0 < $value && $value <= 10) {
            //roze
            $collor = '#E697E6';
        } elseif (10 < $value && $value <= 20) {
            //svetlo zeleno
            $collor = '#93EEAA';
        } else {
            //zeleno
            $collor = '#5EAE9E';
        }
        return $collor;
    }
}
