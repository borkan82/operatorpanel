<?php

namespace AppBundle\Controller;

use AppBundle\Entity\STATS;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\GettersController;
use Symfony\Component\Validator\Constraints\DateTime;

use AppBundle\Entity\OMG;
use AppBundle\Entity\Settings;
use AppBundle\Entity\Main;



class InboundController extends Controller
{
    private function checkThisSession(){
//        $_main      = new Main($conn);
//        $checkUser  = $_main->checkUserIfAdmin();
//        if ($checkUser == false){
//            //return $this->redirect('../login?status=3');
//            return $this->redirectToRoute('login', array('status'=>'3'));
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
        $conn       = $this->get('database_connection');
        $title      = 'Order list - Inb';

        $_omg      = new OMG($conn);
        $_settings = new Settings($conn);
        $_getters  = new GettersController($conn);

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $todayDate = Date("Y-m-d");

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $type       = $queryArr['ordType'];
        $outcome    = $queryArr['outcome'];
        $reason     = $queryArr['reason'];
        $ordSource  = $queryArr['ordSource'];
        $ordNum     = $queryArr['ordNum'];
        $user       = $queryArr['user'];
        $group      = $queryArr['group'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

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

        if(isset($state) && !empty($state))         { $scQ = " AND phone_order_calls.state = '$state' ";                                    } else { $scQ = ""; $state = ""; }
        if(isset($product) && !empty($product))     { $prQ = " AND phone_order_calls.product  = '$product' ";                               } else { $prQ = ""; $product = ""; }
        if(isset($type) && !empty($type))           { $tQ = " AND phone_order_calls.type  = '$type' ";                                      } else { $tQ = ""; $type = "";}
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
//                                                        if ($ordSource == "2"){
//                                                            $sQ .= " AND campaignId LIKE 'sms%' ";
//                                                        } else if ($ordSource == "5"){
//                                                            $sQ = " and phone_order_calls.orderType = '2' ";
//                                                            $sQ .= " AND campaignId LIKE 'reord%' ";
//                                                        }
                                                                                  } else { $sQ = ""; $ordSource=""; }

        if(isset($ordNum) && !empty($ordNum))        { $nQ = " ORDER BY date DESC LIMIT $ordNum ";                                           } else { $nQ = ""; $ordNum = ""; }
        if(isset($user) && !empty($user))            { $uQ = " AND phone_order_calls.operator = $user ";                                     } else { $uQ = ""; $user = ""; }
        if(isset($group) && !empty($group))          { 
            $grQ = " AND phone_order_users.operatorGroup = $group ";
            $_priceData = $conn->fetchAssoc("SELECT INperCall, INperOrder, periods.id as periodId FROM phone_order_callCenterPrice
                                             LEFT JOIN periods ON phone_order_callCenterPrice.period = periods.id
                                             WHERE phone_order_callCenterPrice.callCenterId = '{$group}' AND periods.month = '{$selectM}' AND periods.year = '{$selectY}' LIMIT 1");

            $costpercall    = $_priceData['INperCall'];
            $costperorder   = $_priceData['INperOrder'];
            $period         = $_priceData['periodId'];
        } else { 
            $grQ = ""; $group = "";
            $_priceData = $conn->fetchAssoc("SELECT periods.id as periodId FROM periods
                                             WHERE  periods.month = '{$selectM}' AND periods.year = '{$selectY}' LIMIT 1");
            $period     = $_priceData['periodId'];
        }
        if(isset($from) && !empty($from))            { $dfQ = " AND DATE(phone_order_calls.date) >= '$from' ";                               } else { $from = $todayDate; $dfQ = "  and DATE(phone_order_calls.date) >= '$from' "; }
        if(isset($to) && !empty($to))                { $dtQ = " AND DATE(phone_order_calls.date) <= '$to' ";                                 } else { $to = $todayDate; $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";}

       // print_r($_priceData);
        
        $random     = rand(1000,9999);
        $today      = date('Y-m-d');
        $exportFile = "(".$from."_".$to.")-".$random.".csv";

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

        $_users = $_omg->getUsers();
        $_data     = $_omg->getDataRows("*",$Query, $period);
        $_products = $_omg->getProductList("id, title", "1");
        $_centers  = $_settings->getCallCenterList();
        $_states   = $_omg->getStates();

        $proizvod       = Array();
        $proizvod[0]    = "No product";
        $upsellPriceDiff= 0.00;
        $upsellPercent  = 0.00;

        $orderCount         = 0;
        $otherCount         = 0;
        $cancelCount        = 0;
        $callCount          = 0;
        $revenue            = 0.00;
        $upsellCount        = 0;
        $orderSum           = 0.00;
        $allDuration        = 0;
        $allOrderDuration   = 0;
        $avgCallDuration    = 0;
        $avgOrderDuration   = 0;
        $orderArr           = Array();
        $costArr            = Array(); // Niz gdje ce se postavljati kvote call centra prema aktuelnoj listi
        $priceArr           = Array();
        $callCosts  = 0;

        function TimeToSec($time) {
            $sec = 0;
            foreach (array_reverse(explode(':', $time)) as $k => $v) $sec += pow(60, $k) * $v;
            return $sec;
        }

        foreach ($_data as $row){
            $thisDuration = TimeToSec($row['duration']);
            if($row['success'] == "ORDERED!" && $row['cancel'] == 0){

                $orderCount = $orderCount + 1;
                if ($row['ePrice'] > $row['bPrice']) {
                    $upsellCount = $upsellCount + 1;
                }
                $orderSum   = $orderSum + ($row['ePrice'] / $row['exchange']);
                $allOrderDuration= $allOrderDuration + $thisDuration;
                $orderArr[$row['callCenterId']] = $orderArr[$row['callCenterId']] + 1;
            } else if($row['success'] == "NO ORDER!" && $row['cancel'] == 0){
                $otherCount  = $otherCount + 1;
            } else if($row['success'] == "CANCELED!" && $row['cancel'] == 1){
                $cancelCount = $cancelCount + 1;
            }

            $costArr[$row['callCenterId']] = $priceArr[$row['callCenterId']];
            $callCount  = $callCount + 1;

            $revenue    = $revenue + ($row['ePrice']/$row['exchange']);
            $upsellPriceDiff = $upsellPriceDiff + (($row['ePrice'] - $row['bPrice']) / $row['exchange']);


            $allDuration= $allDuration + $thisDuration;
            $callCosts  = $callCosts + $row['callCosts'];

            }
            $upsellPriceDiff= round($upsellPriceDiff, 2);
            $perCall        = round($revenue/$callCount, 2);
            $perOrder       = round($orderSum/$orderCount, 2);

           foreach ($_products as $_product) {

               $proizvod[$_product["id"]] = $_product["title"];
           }
            $totalQuote = 0; //ukupna cijena usluga call centara
            foreach ($costArr AS $_cost){
                $totalQuote = $totalQuote + $_cost;
            }


            $upsellPercent    = round(($upsellCount/$orderCount) * 100, 2);
            $orderPercent     = round(($orderCount/$callCount) * 100, 2);
            $upsellValue      = round(($upsellPriceDiff /$orderCount ), 2);
            $otherPercent     = round(($otherCount /$callCount ) * 100, 2);
            $cancelPercent    = round(($cancelCount /($orderCount))* 100, 2);
            //$totalDuration    = gmdate('H:i:s', $allDuration);
            $totalDuration    = $_getters->getHoursMinuteSecunds($allDuration);
            //$avgCallDuration  = gmdate('H:i:s', round($allDuration/$callCount));
            $avgCallDuration  = $_getters->getHoursMinuteSecunds(round($allDuration/$callCount,2));
            //$avgOrderDuration = gmdate('H:i:s', round($allOrderDuration/$orderCount));
            $avgOrderDuration   = $_getters->getHoursMinuteSecunds(round($allOrderDuration/$orderCount,2));





            $html = '<table class="statsData" style="width: 100%;font-size: 14px;border-spacing: 4px;">
                        <tbody>
                        <tr style="height:30px;!important">
                            <th style="text-align:left;padding: 0 10px;"></th>
                            <th colspan="2" style="text-align:center;">Volumes</th>
                            <th colspan="2" style="text-align:center;">Percentages</th>
                            <th colspan="2" style="text-align:center;">Sum values</th>
                            <th colspan="2" style="text-align:center;">Average values</th>
                            <th colspan="2" style="text-align:center;">Average costs</th>
                            <th colspan="2" style="text-align:center;">Durations</th>
                        </tr>
                        <tr>
                            <th rowspan="2" style="text-align:left;padding: 0 10px;">Total Calls</th>
                            <td rowspan="2" style="text-align:left;">Calls:</td>
                            <td rowspan="2" style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="Total number of calls">'.$callCount.'</a></strong></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td colspan="2" style="text-align:left;"></td>
                           <td style="text-align:left;">Total monthly costs:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(answered) * cost_per_call">'.$callCosts.'</a> €</strong></td>
                            <td style="text-align:left;">Sum call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="Total call duration">'.$totalDuration.'</a></strong></td>
            
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:left;"></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td style="text-align:left;">Average call value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(order_value) / COUNT(Calls)">'.$perCall.'</a> €</strong></td>
                            <td style="text-align:left;">Cost per call:</td>
                            <td style="background: #fff;text-align:right;"><strong>'.round($costpercall,2).' €</strong></td>
                            <td style="text-align:left;">Avg call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Total_call_duration) / COUNT(Calls)">'.$avgCallDuration.'</a></strong></td>
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Orders</th>
                            <td style="text-align:left;">Orders:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Orders)">'.$orderCount.'</a></strong></td>
            
                            <td style="text-align:left;">Order:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Orders) / COUNT(Calls) *100">'.$orderPercent.'</a> %</strong></td>
            
                            <td style="text-align:left;">Sum order value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Order_value)">'.round($orderSum,2).'</a> €</strong></td>
            
                            <td style="text-align:left;">Avg order value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Order_value) / COUNT(Orders)">'.$perOrder.'</a> €</strong></td>
            
                            <td style="text-align:left;">Cost per order:</td>
                            <td style="background: #fff;text-align:right;"><strong>'.round($costperorder,2).' €</strong></td>
            
                            <td style="text-align:left;">Avg order call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Order_call_duration) / COUNT(Orders)">'.$avgOrderDuration.'</a></strong></td>
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Upsells</th>
                            <td style="text-align:left;">Upsells:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Upsells)">'.$upsellCount.'</a></strong></td>
            
                            <td style="text-align:left;">Upsell:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Upsells) / COUNT(Orders) *100">'.$upsellPercent.'</a> %</strong></td>
            
                            <td style="text-align:left;">Sum upsell value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Upsell_order_value)">'.$upsellPriceDiff.'</a> €</strong></td>
            
                            <td style="text-align:left;">Avg upsell value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Upsell_order_value) / COUNT(Upsells)">'.$upsellValue.'</a> €</strong></td>
            
                            <td colspan="4">
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Other</th>
                            <td style="text-align:left;">Other:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Other)">'.$otherCount.'</a></strong></td>
                            <td style="text-align:left;">Other:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Other) / COUNT(Calls) *100">'.$otherPercent.'</a> %</strong></td>
            
                            <td colspan="8">
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Cancel</th>
                            <td style="text-align:left;">Cancel:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Canceled)">'.$cancelCount.'</a></strong></td>
                            <td style="text-align:left;">Canceled:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Canceled) / COUNT(Orders) *100">'.$cancelPercent.'</a> %</strong></td>
            
                            <td colspan="8">
                        </tr>
                        </tbody>
                    </table>
                    </div>
            <div class="tableHolder" style="width: 1400px;">
                    <div class="dayTable" style="width: 1400px;">
                        <table class="dayView compact" id="example" style="font-size: 12px;">
                            <thead style="cursor:pointer;background-color: #eee!important;">
                            <tr >
                              <td width="20px">#</td>
                              <td >SubmitId</td>
                              <td >Country</td>
                              <td >Operator</td>
                              <td >Order source</td>
                              <td >Product</td>
                              <td >Price</td>
                              <td >Final Price</td>
                              <td >Date</td>
                              <td >Code</td>
                              <td >Start</td>
                              <td >End</td>
                              <td >Duration</td>
                              <td >Call type</td>
                              <td >Questions</td>
                              <td width="150px">Other</td>
                              <td >Outcome</td>
                              <td >Canceled</td>
                              <td width="100px">Reason</td>
                              <td >Flow</td>
                            </tr>
                           </thead>
                           <tbody id="tabela">';
        $counter = 0;
        foreach ($_data as $row){
            $tip = $row['type']; $showType = "ORDER";
            if ( $tip == 2 ) {
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

            $showOutcome = $row['success'];
            if ($row['success'] == "ORDERED!"){
                $showOutcome = "ORDER";
                if ($row['ePrice'] > $row['bPrice']){
                    $showOutcome = "UPSELL";
                }

                if ($row['cancelStatus'] == "3"){
                    $showOutcome = "ORDER FROM CANCEL";
                }




            } else if ($row['success'] == "CANCELED!") {
                $showOutcome = "CANCELED";
            } else if ($row['success'] == "NO ORDER!") {
                $showOutcome = "NO ORDER";


                if ($row['cancelStatus'] == "1"){
                    $showOutcome = "ORDER CONFIRMED";
                } else if($row['cancelStatus'] == "2"){
                    $showOutcome = "CANCEL PREVIOUS";
                }
            }


            $datum = substr($row['date'], 0, 10);
            $counter++;

            $html .= '<tr onclick="showBuyer('.$row['id'].');" class="showBuyer '.$showColor.'" style="margin-top:1px; cursor:pointer;">
            <td class="'.$showColor.'">'.$counter.'</td>
            <td class="'.$showColor.'"><a href="**********?submitId='.$row['orderSubmitId'].'" target="_blank">'.$row['orderSubmitId'].'</a></td>
            <td class="'.$showColor.'">'.$row['state'].'</td>
            <td class="'.$showColor.'">';

            if ($_SESSION['phUser']['role'] == "A"){
                $html .= '<a href="instpectletURL'.$row['sId'].'?pn=1" target="_blank">'.$row['opName'].'</a>';
            } else {
                $html .= $row['opName'];
            }

            $izvor = $row['title'];
            if ($ordSource == "5"){
                $izvor = "SMS Reorder";
            }

            $flowType = $row['flowType'];

            if ($flowType == 0){
                $flowType = 1;
            }

            $html .= '</td>
            <td class="'.$showColor.'">'.$izvor.'</td>
            <td class="'.$showColor.'">'.$proizvod[$row["product"]].'</td>
            <td class="'.$showColor.'">'.$row['bPrice'].'</td>
            <td class="'.$showColor.'">'.$row['ePrice'].'</td>
            <td class="'.$showColor.'">'.$datum.'</td>
            <td class="'.$showColor.'">'.$row['code'].'</td>
            <td class="'.$showColor.'">'.$row['start'].'</td>
            <td class="'.$showColor.'">'.$row['end'].'</td>
            <td class="'.$showColor.'">'.$row['duration'].'</td>
            <td class="'.$showColor.'">'.$showType.'</td>
            <td class="'.$showColor.'">'.$row['otherOpt'].'</td>
            <td class="'.$showColor.'" style="overflow:hidden;"><div class="makeBigger">'.$row['other'].'</div></td>
            <td class="'.$showColor.'">'.$showOutcome.'</td>
            <td class="'.$showColor.'">'.$showCancel.'</td>
            <td class="'.$showColor.'" style="overflow:hidden;"><div class="makeBigger">'.$row['cancelReason'].'</div></td>
            <td class="'.$showColor.'">'.$flowType.'</td>
          </tr>';
        }

        $html .= '</tbody></table></div>';


        return $this->render('statsin/viewData.html.twig', array(
            '_html' => $html,
            '_users' => $_users,
            '_products' => $_products,
            '_centers' => $_centers,
            '_states' => $_states,
            'from' => $from,
            'to' => $to,
            'title' =>$title,
            'random' =>$random,
            'exportFile' => $exportFile));
    }

    public function statChartAction()
    {
        $title      = 'Order Charts';
        $conn = $this->get('database_connection');

        $_stats = new STATS($conn);
        $_omg = new OMG($conn);
        $_getters  = new GettersController($conn);

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }


        $a_date = Date("Y-m-h");
        $godina = Date("Y");
        $mjesec = Date("m");
        $defaultDateFrom = $godina."-".$mjesec."-01";
        $daysNum = date("Y-m-t", strtotime($a_date));
        $defaultDateTo = $daysNum;

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state = $queryArr['state'];
        $user  = $queryArr['user'];
        $from  = $queryArr['from'];
        $to    = $queryArr['to'];

        if (isset($state) && !empty($state)) { $scQ = " and phone_order_calls.state = '$state' ";       } else { $state = ""; $scQ = "";  }
        if (isset($user) && !empty($user))   { $uQ = " and phone_order_calls.operator = $user ";        } else { $user = "";    $uQ = ""; }
        if (isset($from) && !empty($from))   { $dfQ = " and DATE(phone_order_calls.date) >= '$from' ";  } else { $from = $defaultDateFrom; $dfQ = " and DATE(phone_order_calls.date) >= '$from' ";  }
        if (isset($to) && !empty($to))       { $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";    } else { $to = $defaultDateTo;  $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";   }

        $Query = " 1 ";  //default
        $Query .= $scQ;  //state
        $Query .= $uQ;   //users
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to

        $_users = $_omg->getUsers();
        $_sources = $_stats->countOrderSource($Query);
        $_calls = $_stats->countCallType($Query);
        $_questions = $_stats->countQuests($Query);
        $_sucesses = $_stats->countOrderSucess($Query);
        $_cancels = $_stats->countCancel($Query);
        $_states = $_omg->getStates();

        $arrSource = Array();
        $arrCall = Array();
        $arrQuestion = Array();
        $arrSucess = Array();
        $arrCancel = Array();
        foreach ($_sources as $source){
            $arrSource[$source["title"]] = $source["broj"];
        }
        foreach ($_calls as $call){
            $tip = "";
            if ($call["tip"] == 1) {
                $tip = "ORDER";
            }else if ($call["tip"] == 2){
                $tip = "OTHER";
            } else {
                $tip = "UNKNOWN";
            }
            $arrCall[$tip] = $call["broj"];
        }
        foreach ($_questions as $question){
            $arrQuestion[$question["otherOpt"]] = $question["broj"];
        }
        foreach ($_sucesses as $sucess){
            $arrSucess[$sucess["success"]] = $sucess["broj"];
        }
        foreach ($_cancels as $cancel){
            $arrCancel[$cancel["cancelReason"]] = $cancel["broj"];
        }

        $response = $this->render('statsin/statChart.html.twig', array(

//                '_html' => $html,
                '_states' =>$_states,
                '_users' => $_users,
                'arrCall' =>$arrCall,
                'arrSource' => $arrSource,
                'arrQuestion' => $arrQuestion,
                'arrSucess'  => $arrSucess,
                'arrCancel' =>$arrCancel,
                'from' => $from,
                'to' => $to,
                'title' => $title,
              )
        );
        return $response;


//return $this->render('::inbound/statChart.html.php');
    }

    public function operatorAction()
    {
        $conn       = $this->get('database_connection');
        $title      = 'Operator';

        $_omg = new OMG($conn);
        $_stats = new STATS($conn);
        $_settings = new Settings($conn);
        $_getters  = new GettersController($conn);

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }


        $todayDate = Date("Y-m-d");

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $type       = $queryArr['ordType'];
        $ordSource  = $queryArr['ordSource'];
        $user       = $queryArr['user'];
        $group      = $queryArr['group'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        $sType      = $queryArr['sType'];
        $rType      = $queryArr['rType'];



        if(isset($state) && !empty($state))         { $scQ = " AND phone_order_calls.state = '$state' ";                                    } else { $scQ = ""; $state = ""; }
        if(isset($product) && !empty($product))     { $prQ = " AND phone_order_calls.product  = '$product' ";                               } else { $prQ = ""; $product = ""; }
        if(isset($type) && !empty($type))           { $tQ = " AND phone_order_calls.type  = '$type' ";                                      } else { $tQ = ""; $type = ""; }

        if(isset($ordSource) && !empty($ordSource)) { $sQ = " AND phone_order_calls.orderType = '$ordSource' ";
//                                                        if ($ordSource == "2"){
//                                                            $sQ .= " AND campaignId LIKE 'sms%' ";
//                                                        } else if ($ordSource == "5"){
//                                                            $sQ = " AND phone_order_calls.orderType = '2' ";
//                                                            $sQ .= " AND campaignId LIKE 'reord%' ";
//                                                        }
        } else { $sQ = ""; $ordSource=""; }

        if(isset($user) && !empty($user))           { $uQ = " AND phone_order_calls.operator = $user ";                                     } else { $uQ = ""; $user = ""; }
        if(isset($group) && !empty($group))         { $grQ = " AND phone_order_users.operatorGroup = $group ";                              } else { $grQ = ""; $group = ""; }
        if(isset($from) && !empty($from))           { $dfQ = " AND DATE(phone_order_calls.date) >= '$from' ";                               } else { $from = date('Y-m-01'); $dfQ = "  and DATE(phone_order_calls.date) >= '$from' "; }
        if(isset($to) && !empty($to))               { $dtQ = " AND DATE(phone_order_calls.date) <= '$to' ";                                 } else { $to = $todayDate; $dtQ = " and DATE(phone_order_calls.date) <= '$to' "; }

        if(isset($sType) && !empty($sType))         { $show = $sType;                                                                       } else { $show = "1";}
        if(isset($rType) && !empty($rType))         { $rateT = $rType;                                                                      } else { $rateT = "1"; }

        if ($rateT == "1"){
            $chartHead = "Call centers success rates";
        } else {
            $chartHead = "Call centers cancel rates";
        }

        $random     = rand(1000,9999);
        $today      = date('Y-m-d');
        $exportFile = "(".$from."_".$to.")-".$random.".csv";

        $Query = " 1 ";  //default
        $Query .= $scQ;  //state
        $Query .= $prQ;  //product
        $Query .= $tQ;   //Order type
        $Query .= $sQ;   //Order source
        $Query .= $uQ;   //User
        $Query .= $grQ;  //Group
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to


        $_users = $_omg->getUsers();
        $_data = $_stats->getDataOperator("*",$Query);
        $_products = $_omg->getProductList("id, title", "1");
        $_callDuration = $_stats->getCallDurations("*",$Query);
        $_countupsells = $_stats->getOrdersByCall($Query);
        $_centers = $_settings->getCallCenterList();
        $_states = $_omg->getStates();



        $call_centers = array();
        foreach ($_centers as $center){
            if ($center['state'] != 'TE'){
                $call_centers[]=$center;
            }
        }

        $upsellArr = Array();
        $durations = Array();
        foreach ($_callDuration as $callD) {

            $dbDuration =  $callD['durationTime'];
            $sekundi = strtotime("1970-01-01 $dbDuration UTC");
            $durations[$callD["opName"]] = $durations[$callD["opName"]] + $sekundi;
            $upsellArr[$callD["opName"]] = 0;
        }


        $proizvod = Array();
        $proizvod[0] = "No product";
        foreach ($_products as $_product) {

           $proizvod[$_product["id"]] = $_product["title"];
        }


        foreach ($_countupsells as $upsell) {
        //echo $upsell["order_id"]."-".$upsell["username"]."-".$upsell["product"]."<BR>";
            $quantity = substr($upsell["product"], 0, 1);

            if ($quantity > 1){
                $upsellArr[$upsell["username"]] = $upsellArr[$upsell["username"]] + 1;
            }
        }
        // ------------------ CHART SETUP ---------------------------------
        $dateType = "DATE(date)";
        $dateFormat = "Y-m-d";

        if ($show == "2"){
            $dateType = "DATE_FORMAT(date,'%Y-%m')";
            $dateFormat = "Y-m";
        } else if ($show == "3"){
            $dateType = "WEEK(date)";
            $dateFormat = "W";
        }

        // ------------------ CHART SETUP END -----------------------------

        $grouped_centers = $_settings->getCallCenterList($grQ2." GROUP BY name ");
        //centers withou 'TE'
        $groupedCentresCharts = array();
        foreach ($grouped_centers as $centerChart){
            if ($centerChart['state'] != 'TE'){
                $groupedCentresCharts[]=$centerChart;
            }
        }

        //print_r($_data);die();
        //---------------------------------------------------------------------------------------------------------------------------------
        $getChartDataSve = "SELECT {$dateType} AS datum1, phone_order_callcenter.name AS center, count(*) as broj1
                            FROM phone_order_calls
                            LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                            LEFT JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
                            WHERE 1 {$dfQ} {$dtQ} {$grQ} {$uQ} and phone_order_callcenter.state != 'TE'
                            GROUP BY {$dateType},phone_order_callcenter.name
                            ORDER BY date DESC";
        $chart1=$conn->fetchAll($getChartDataSve);
        //---------------------------------------------------------------------------------------------------------------------------------
        $getChartDataOrder = "SELECT {$dateType} AS datum2, phone_order_callcenter.name AS center, count(*) as broj2
                                FROM phone_order_calls
                                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                                LEFT JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
                                WHERE 1 {$dfQ} {$dtQ} {$grQ} {$uQ} AND phone_order_calls.type = 1 AND phone_order_calls.cancel = 0 and phone_order_callcenter.state != 'TE'
                                GROUP BY {$dateType},phone_order_callcenter.name
                                ORDER BY date DESC";
        $chart2=$conn->fetchAll($getChartDataOrder);
        //---------------------------------------------------------------------------------------------------------------------------------
        $getChartDataCancel = "SELECT {$dateType} AS datum3, phone_order_callcenter.name AS center, count(*) as broj3
                                FROM phone_order_calls
                                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                                LEFT JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
                                WHERE 1 {$dfQ} {$dtQ} {$grQ} {$uQ} AND phone_order_calls.type = 1 AND phone_order_calls.cancel = 1 and phone_order_callcenter.state != 'TE'
                                GROUP BY {$dateType},phone_order_callcenter.name
                                ORDER BY date DESC";
        $chart3=$conn->fetchAll($getChartDataCancel);
        //---------------------------------------------------------------------------------------------------------------------------------

        $startDate = Date($from);
        $utmstF     = strtotime($from);
        $utmstT     = strtotime($to);



        // SVI POZIVI PO CALL CENTRIMA ZA CHART
        $nizS = Array();
        $nizO = Array();
        $nizC = Array();

        while ($utmstF <= $utmstT){
            $date = new \DateTime();
            $date->setTimestamp($utmstF);
            $nizS[$date->format($dateFormat)] = Array();

            foreach ($chart1 AS $allData){

                $nizS[$date->format($dateFormat)][$allData['center']] = 0;
            }
            $utmstF = $utmstF + 86400;
        }
        $nizO = $nizS;
        $nizC = $nizS;

        foreach ($chart1 AS $allData){
            $addZero = "";
            if ($allData['datum1'] >= 0 && $allData['datum1'] < 10) {
                $addZero = "0";
            }
            if ($show == 3){
                $nizS[$addZero.$allData['datum1']][$allData['center']] = $allData['broj1'];
            } else {
                $nizS[$allData['datum1']][$allData['center']] = $allData['broj1'];
            }

        }

        foreach ($chart2 AS $allData){
            $addZero = "";
            if ($allData['datum2'] >= 0 && $allData['datum2'] < 10) {
                $addZero = "0";
            }
            if ($show == 3){
                $nizO[$addZero.$allData['datum2']][$allData['center']] = $allData['broj2'];
            } else {
                $nizO[$allData['datum2']][$allData['center']] = $allData['broj2'];
            }


        }

        foreach ($chart3 AS $allData){
            $addZero = "";
            if ($allData['datum3'] >= 0 && $allData['datum3'] < 10) {
                $addZero = "0";
            }
            if ($show == 3) {
                $nizC[$addZero.$allData['datum3']][$allData['center']] = $allData['broj3'];
            } else {
                $nizC[$allData['datum3']][$allData['center']] = $allData['broj3'];
            }
        }

        // TOTAL ZA SUCCESS PROCENAT CHARTA
        $utmstF     = strtotime($from);
        $nizSve = Array();
        while ($utmstF <= $utmstT){
            $date = new \DateTime();
            $date->setTimestamp($utmstF);
            $nizSve[$date->format($dateFormat)] = Array();

            foreach ($groupedCentresCharts AS $center){
                if($rateT == 1){
                    $percent = round(($nizO[$date->format($dateFormat)][$center['name']] / $nizS[$date->format($dateFormat)][$center['name']]) * 100, 2);

                } else {
                    $percent = round(($nizC[$date->format($dateFormat)][$center['name']] / ($nizC[$date->format($dateFormat)][$center['name']] + $nizO[$date->format($dateFormat)][$center['name']])) * 100, 2);
                }

                $nizSve[$date->format($dateFormat)][$center['name']] = $percent;
                }
            $utmstF = $utmstF + 86400;
        }

        //kategorije chart-a

        $ccategories = "";
        foreach ($nizSve AS $key=>$val) {
            $ccategories .= "'$key', ";
        }

        // series - parametar charta
        $cseries = "";
        foreach ($groupedCentresCharts AS $center){
                    $cName = $center["name"];
                    $cseries .= "{ name: '$cName', data: [";

                    foreach ($nizSve AS $key=>$ccenters) {
                            foreach($ccenters AS $center=>$percData){
                                if($center==$cName){
                                    $cseries .= $percData.", ";
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

            $duration = $_getters->getHoursMinuteSecunds($durations[$row['opName']]);
            $avgDurationUnix = (int)$durations[$row['opName']]/(int)$row['callNums'];
            //$avgDuration = gmdate('H:i:s', $avgDurationUnix);
            $avgDuration = $_getters->getHoursMinuteSecunds(round($durations[$row['opName']]/(int)$row['callNums'],2));

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
            $tDurationsSec  = $tDurationsSec + $durations[$row['opName']];
            $tDurations = $_getters->getHoursMinuteSecunds($tDurationsSec);


        $html .= '<tr class="'.$showColor.'" style="margin-top:1px; cursor:pointer;">
                    <td class="'.$showColor.'">'.$counter.'</td>
                    <td class="'.$showColor.'">'.$row['state'].'</td>
                    <td class="'.$showColor.'"><a href="inspectleturl'.$row['ouid'].'%22%7D&tags=%7B%22paneopen%22%3A%22basic%22%2C%22tagslist%22%3A%5B%7B%22tag%22%3A%22state%22%2C%22value%22%3A%22%22%7D%5D%2C%22operator%22%3A%22and%22%7D" target="_blank">'.$row['opName'].'</a></td>
                    <td class="'.$showColor.'">'.$row['callNums'].'</td>
                    <td class="'.$showColor.'">'.$duration.'</td>
                    <td class="'.$showColor.'">'.$avgDuration.'</td>
                    <td class="'.$showColor.'">'.$totalOrder.'</td>
                    <td class="greenLine">'.$row['orderedNum'].'</td>
                    <td class="'.$showColor.'">'.number_format($upsellPerc, 2).' %</td>
                    <td class="redLine">'.$row['canceledNum'].'</td>
                    <td class="'.$showColor.'">'.$row['otherNum'].'</td>
                    <td class="'.$showDiff.' '.$showColor.'">'.number_format($orderPerc, 2).' %</td>
                    <td class="'.$showDiff.' '.$showColor.'">'.number_format($cancelPerc, 2).' %</td>
                    <td class="'.$showDiff.' '.$showColor.'">'.number_format($otherPerc, 2).' %</td>
                  </tr>';

        }
        $tUpsellsP  = ($tUpsellCp / $tSuccess) * 100;
        $tOrdersP   = ($tSuccess / $tCalls) * 100;
        $tCancellP  = ($tCancell / $tOrders) * 100;
        $tOtherP    = ($tOther / $tCalls) * 100;
        $tAvgDuration = $_getters->getHoursMinuteSecunds(round($tDurationsSec/$tCalls,2));

        $html .= '</tbody>
                        <tfoot>
                        <tr style="text-align:center;font-weight:bold;">
                        <td colspan="3">TOTAL:</td>
                        <td>'.$tCalls.'</td>
                        <td>'.$tDurations.'</td>
                        <td>'.$tAvgDuration.'</td>
                        <td>'.$tOrders.'</td>
                        <td>'.$tSuccess.'</td>
                        <td>'.number_format($tUpsellsP, 2).' %</td>
                        <td>'.$tCancell.'</td>
                        <td>'.$tOther.'</td>
                        <td>'.number_format($tOrdersP, 2).' %</td>
                        <td>'.number_format($tCancellP, 2).' %</td>
                        <td>'.number_format($tOtherP, 2).' %</td>
                        </tr>
                        </tfoot>
                </table>';


        return $this->render('statsin/operator.html.twig', array(
                    '_html'      => $html,
                    '_users'     => $_users,
                    '_products'  => $_products,
                    '_centers'   => $_centers,
                    '_states'    => $_states,
                    'charthead'  => $chartHead,
                    'ccategories'=> $ccategories,
                    'cseries'    => $cseries,
                    'from'       => $from,
                    'to'         => $to,
                    'title'      => $title,
                    'random'     => $random,
                    'exportFile' => $exportFile));

    }

    public function inboundChartsAction()
    {
        $conn       = $this->get('database_connection');
        $title      = 'Charts - Inb';
        
        $getters    = new GettersController($conn);
        $_omg      = new OMG($conn);
        $_settings = new Settings($conn);
        $_stats    = new STATS($conn);
        $_getters  = new GettersController($conn);

        
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $todayDate = Date("Y-m-d");

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $type       = $queryArr['ordType'];
        $outcome    = $queryArr['outcome'];
        $reason     = $queryArr['reason'];
        $ordSource  = $queryArr['ordSource'];
        $ordNum     = $queryArr['ordNum'];
        $user       = $queryArr['user'];
        $group      = $queryArr['group'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        if(isset($state) && !empty($state))         { $scQ = " AND phone_order_calls.state = '$state' ";                                    } else { $scQ = ""; $state = ""; }
        if(isset($product) && !empty($product))     { $prQ = " AND phone_order_calls.product  = '$product' ";                               } else { $prQ = ""; $product = ""; }
        if(isset($type) && !empty($type))           { $tQ = " AND phone_order_calls.type  = '$type' ";                                      } else { $tQ = ""; $type = "";}
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
//                                                        if ($ordSource == "2"){
//                                                            $sQ .= " AND campaignId LIKE 'sms%' ";
//                                                        } else if ($ordSource == "5"){
//                                                            $sQ = " and phone_order_calls.orderType = '2' ";
//                                                            $sQ .= " AND campaignId LIKE 'reord%' ";
//                                                        }
        } else { $sQ = ""; $ordSource=""; }

        if(isset($ordNum) && !empty($ordNum))             { $nQ = " ORDER BY date DESC LIMIT $ordNum ";                                              } else { $nQ = ""; $ordNum = ""; }
        if(isset($user) && !empty($user))           { $uQ = " AND phone_order_calls.operator = $user ";                                     } else { $uQ = ""; $user = ""; }
        if(isset($group) && !empty($group))         { $grQ = " AND phone_order_users.operatorGroup = $group ";                              } else { $grQ = ""; $group = ""; }
        if(isset($from) && !empty($from))           { $dfQ = " AND DATE(phone_order_calls.date) >= '$from' ";                               } else { $from = date('Y-m-01');  $dfQ = "  and DATE(phone_order_calls.date) >= '$from' "; }
        if(isset($to) && !empty($to))               { $dtQ = " AND DATE(phone_order_calls.date) <= '$to' ";                                 } else { $to = $todayDate; $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";}

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

        $_users = $_omg->getUsers();
        $_products = $_omg->getProductList("id, title", "1");
        $_centers  = $_settings->getCallCenterList();
        $_states   = $_omg->getStates();

        $_data     = $_omg->getDataRows("*",$Query);



        $chartsQuery = " 1 "  . $tQ . $oQ . $reaQ . $uQ . $grQ . $dfQ . $dtQ;

        $statesFromInbound = $_omg->getInboundStateList();

        $extendedOrderSources = array(
            1 => array(
                'name' => 'Page order',
                'shortName' => 'page-order'
            ),
            3 => array(
                'name' => 'Mail Order',
                'shortName' => 'mail-order'
            ),
            4 => array(
                'name' => 'Print Order',
                'shortName' => 'print-order'
            )
        );

        $statesInbound =array();

        foreach ($statesFromInbound as $stateInb) {

            $statesInbound[$stateInb['state']] = array(

                'count-page-order'=> 0,
                'count-sms-bulk'=> 0,
                'count-sms-reorder'=> 0,
                'count-mail-order'=> 0,
                'count-print-order'=> 0,

                'revenue-page-order'=> 0,
                'revenue-sms-bulk'=> 0,
                'revenue-sms-reorder'=> 0,
                'revenue-mail-order'=> 0,
                'revenue-print-order'=> 0,
            );
        }

        $dataChartStates = $_data;

        if($state != "" || $ordSource != ""){

            $dataCharts = $_omg->getDataRows("*",$chartsQuery);
            $dataChartStates = $dataCharts;
        }

        foreach ($extendedOrderSources as $key=>$value) {
            foreach ($dataChartStates as $result) {

                if($result['orderType'] == $key){
                    $statesInbound[$result['state']]['revenue-' . $value['shortName']] = $statesInbound[$result['state']]['revenue-' . $value['shortName']] + ($result['ePrice'] / $result['exchange']);
                    $statesInbound[$result['state']]['count-' . $value['shortName']]++;

                }
            }
        }

        $panelPerformances = $_omg->getDataForInboundCharts($statesInbound, $chartsQuery);

        /*
         * KRAJ STATISIKE ZA GLAVNI CHART INBOUND PANEL PERFORMANCE ZA SVE DRZAVE
         */

        /*
         * POCETAK STATISIKA ZA CHART PO DRZAVI POJEDINACNI (ORDER OTHER)
         */
        if($state != "") {
            $start = strtotime($from);
            $end = strtotime($to);

            $days_between = ceil(abs($end - $start) / 86400) +1;
            //print_r($days_between);die();

            $timeSevenDays = array();
            for ($i=0; $i<$days_between ; $i++){
                $date = date("Y-m-d",strtotime($to . '-'.$i.' days'));
                $timeSevenDays[] = $date;
            }

            //print_r($timeSevenDays);die();
            $reverseTimeSevenDays = array_reverse ($timeSevenDays);

            $chartForState = array();
            foreach ($reverseTimeSevenDays as $dateState){
                $chartForState[$dateState] = Array("order"=>0, "other"=>0);
            }

            $newDateFormat = array();
            foreach($chartForState as $key=> $value) {
                $newDateFormat[] = date('l d/m/Y', strtotime($key));
            }
            //print_r($newDateFormat);die();
            $toDateStateChart = $to;
            $fromDateStateChart = $from;

            //print_r($fromDateStateChart . $toDateStateChart);die();

            $stateQuery = " 1 ";  //default
            $stateQuery .= $scQ;  //state
            $stateQuery .= $reaQ;   //Reason
            $stateQuery .= $sQ;   //Order source
            $stateQuery .= $uQ;   //User
            $stateQuery .= $grQ;  //Group
            $stateQuery .= " and DATE(phone_order_calls.date) >= '$fromDateStateChart' ";  //date from
            $stateQuery .= " and DATE(phone_order_calls.date) <= '$toDateStateChart' ";  //date to
            $stateQuery .= "ORDER BY date DESC";

            $dataChartState = $_omg->getDataRows("*",$stateQuery);

            foreach($dataChartState AS $data) {
                $fullDateTime = $data['date'];
                $dateAndTime = explode(" ", $fullDateTime);
                $data['date']=$dateAndTime[0];

                if ($data['type'] == "1" && $data['cancel'] !=1 ){
                    $chartForState[$data['date']]['order']++; //= $chartForState[$data['date']]['order'] + 1;
                }
                if ($data['type'] == "2"){
                    $chartForState[$data['date']]['other']++; // = $chartForState[$data['date']]['other'] + 1;
                }
            }

        }
        /*
         * KRAJ STATISIKA ZA CHART PO DRZAVI POJEDINACNI (ORDER OTHER)
         */


        // KONACNE SUME ZA POZIVE I ORDERE ---- INBOUND + OUTBOUND
//        $sumCallCount = $countTCalls + $countTCallsOut;         //POZIVI
//        $sumOrderCount= $countTOrders + $countTOrdersOut;    //ORDER SUCCESS
        /**
        END Racunanje totala poziva i ordera za izabrani period, iskljucujuci ostale parametre
         */

        $proizvod       = Array();
        $proizvod[0]    = "No product";
        $upsellPriceDiff= 0.00;
        $upsellPercent  = 0.00;

        $orderCount         = 0;
        $otherCount         = 0;
        $cancelCount        = 0;
        $callCount          = 0;
        $revenue            = 0.00;
        $upsellCount        = 0;
        $orderSum           = 0.00;
        $allDuration        = 0;
        $allOrderDuration   = 0;
        $avgCallDuration    = 0;
        $avgOrderDuration   = 0;
        $orderArr           = Array();
        $costArr            = Array(); // Niz gdje ce se postavljati kvote call centra prema aktuelnoj listi

        function TimeToSec($time) {
            $sec = 0;
            foreach (array_reverse(explode(':', $time)) as $k => $v) $sec += pow(60, $k) * $v;
            return $sec;
        }

        foreach ($_data as $row){
            $thisDuration = TimeToSec($row['duration']);
            if($row['success'] == "ORDERED!" && $row['cancel'] == 0){

                $orderCount = $orderCount + 1;
                if ($row['ePrice'] > $row['bPrice']) {
                    $upsellCount = $upsellCount + 1;
                }
                $orderSum   = $orderSum + ($row['ePrice'] / $row['exchange']);
                $allOrderDuration= $allOrderDuration + $thisDuration;
                $orderArr[$row['callCenterId']] = $orderArr[$row['callCenterId']] + 1;
            } else if($row['success'] == "NO ORDER!" && $row['cancel'] == 0){
                $otherCount  = $otherCount + 1;
            } else if($row['success'] == "CANCELED!" && $row['cancel'] == 1){
                $cancelCount = $cancelCount + 1;
            }

            $costArr[$row['callCenterId']] = $priceArr[$row['callCenterId']];
            $callCount  = $callCount + 1;

            $revenue    = $revenue + ($row['ePrice']/$row['exchange']);
            $upsellPriceDiff = $upsellPriceDiff + (($row['ePrice'] - $row['bPrice']) / $row['exchange']);


            $allDuration= $allDuration + $thisDuration;

        }
        $upsellPriceDiff= round($upsellPriceDiff, 2);
        $perCall        = round($revenue/$callCount, 2);
        $perOrder       = round($orderSum/$orderCount, 2);

        foreach ($_products as $_product) {

            $proizvod[$_product["id"]] = $_product["title"];
        }
        $totalQuote = 0; //ukupna cijena usluga call centara
        foreach ($costArr AS $_cost){
            $totalQuote = $totalQuote + $_cost;
        }


        $upsellPercent  = round(($upsellCount/$orderCount) * 100, 2);
        $orderPercent   = round(($orderCount/$callCount) * 100, 2);
        $upsellValue    = round(($upsellPriceDiff /$orderCount ), 2);
        $otherPercent   = round(($otherCount /$callCount ) * 100, 2);
        $cancelPercent  = round(($cancelCount /($cancelCount+$orderCount))* 100, 2);
        $totalDuration  = gmdate('H:i:s', $allDuration);
        $avgCallDuration= gmdate('H:i:s', round($allDuration/$callCount));
        $avgOrderDuration= gmdate('H:i:s', round($allOrderDuration/$orderCount));

        if (($state == "" || empty($state)) && ($group == "" || empty($group)) ){
            $totalQuote = $allInvoices;
        }

        $ccategories = "";
        foreach ($panelPerformances AS $key=>$performance) {
            $ccategories .= "'" . $key . "', ";
        }

        $cseriespage = "";
        foreach ($panelPerformances AS $performance) {
            $cseriespage .= round($performance['revenue-page-order']/$performance['count-page-order'],2) . ", ";
        }

        $cseriesbulk = "";
        foreach ($panelPerformances AS $performance) {
            $cseriesbulk .= round($performance['revenue-sms-bulk']/$performance['count-sms-bulk'],2) . ", ";
        }

        $cseriesreorder = "";
        foreach ($panelPerformances AS $performance) {
            $cseriesreorder .= round($performance['revenue-sms-reorder']/$performance['count-sms-reorder'],2) . ", ";
        }

        $ccategories2 = "";
        foreach ($newDateFormat as $date){
            $ccategories2 .= "'" . $date . "', ";
        }

        $cseriesorder = "";
        foreach ($chartForState as $date ) {
            $cseriesorder .= $date['order'] . ", ";
        }

        $cseriesother = "";
        foreach ($chartForState as $date ) {
            $cseriesother .= $date['other'] . ", ";
        }


         return $this->render('statsin/inboundCharts.html.twig', array(
//             'test' => $test,
//             '_html' => $html,
             'ccategories' => $ccategories,
             'ccategories2' => $ccategories2,
             'cseriespage' => $cseriespage,
             'cseriesbulk' => $cseriesbulk,
             'cseriesreorder' => $cseriesreorder,
             'cseriesorder' => $cseriesorder,
             'cseriesother' => $cseriesother,
             '_users' => $_users,
             '_products' => $_products,
             '_centers' => $_centers,
             '_states' => $_states,
             'from' => $from,
             'to' =>$to,
             'title' =>$title));
    }

    public function callCenterAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Call center';
        $conn = $this->get('database_connection');

        $_omg = new OMG($conn);
        $_stats = new STATS($conn);
        $_getters = new GettersController($conn);

        $today      = date('Y-m-d');


        $a_date = Date("Y-m-h");
        $godina = Date("Y");
        $mjesec = Date("m");
        $defaultDateFrom = $godina."-".$mjesec."-01";
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
        $source = $queryArr['ordSource'];
        $group = $queryArr['group'];
        $from = $queryArr['from'];
        $to = $queryArr['to'];

        if (isset($type) && !empty($type))                  { $tQ = " and phone_order_calls.type = '$type' ";         } else { $type = ""; $tQ = "";}
        if (isset($source) && !empty($source))              { $sQ = " and phone_order_calls.orderType = '$source' ";  } else { $source = "";  $sQ = "";}
        if (isset($group) && !empty($group) && $group == 1) { $groupBy = " GROUP BY phone_order_callcenter.id ";      } else { $group = '';  $groupBy = " GROUP BY phone_order_callcenter.main_call_center_id "; }
        if (isset($from) && !empty($from))                  { $dfQ = " and DATE(phone_order_calls.date) >= '$from' "; } else { $from = $defaultDateFrom;   $dfQ = " and DATE(phone_order_calls.date) >= '$from' "; }
        if (isset($to) && !empty($to))                      {  $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";  } else { $to = $defaultDateTo;  $dtQ = " and DATE(phone_order_calls.date) <= '$to' "; }

        $Query = " 1 ";  //default
        $Query .= $tQ;  //Order type
        $Query .= $sQ;  //Order source
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to



        $mainQuery = $Query . " and phone_order_callcenter.state != 'TE' " . $groupBy;
        $_data = $_stats->getDataCallCentar("*",$mainQuery);
        $_callDuration = $_stats->getCallDurationsCallCentres("*",$Query);

        $_products = $_omg->getProductList("id, title", "1");
        $_countupsells = $_stats->getOrdersByCallCenter($Query);


        $html = '<table class="dayView compact" id="example" style="font-size: 12px;">
                        <thead style="cursor:pointer;">
                        <tr>
                            <td width="20px">#</td>';
                    if ($group == 1) {
                        $html .= '<td >Country</td>';
                    }
                   $html.=  '<td >Call center</td>
                             <td >Calls</td>
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
        foreach ($_data as $row){

            $counter++;
            $totalOrder     =  $row['orderCount']+$row['cancelCount'];

            $orderPercent   = ($row['orderCount'] / $row['callCount']) * 100;
            $cancelPercent  = ($row['cancelCount'] / $totalOrder) * 100;
            $otherPercent   = ($row['otherCount'] / $row['callCount']) * 100;
            $upsellPercent  = ($row['upsellCount']/$row['orderCount']) * 100;
            $duration = $_getters->getHoursMinuteSecunds($row['allDuration']);
            $avgDuration    = $_getters->getHoursMinuteSecunds(round($row["allDuration"]/$row['callCount'],2));
            //$avgDuration    = gmdate("H:i:s", $row["allDuration"]/$row['callCount']);




            //RACUNANJE TOTALA U FOOTERU
            $tCalls      = $tCalls +  $row['callCount'];
            $tOrders     = $tOrders +  $totalOrder;
            $tSuccess    = $tSuccess +  $row['orderCount'];
            $tOther      = $tOther +  $row['otherCount'];
            $tUpsell     = $tUpsell + $row['upsellCount'];
            $tCancel     = $tCancel +  $row['cancelCount'];
            $tDurationSec   = $tDurationSec + $row['allDuration'];
            $tDuration = $_getters->getHoursMinuteSecunds($tDurationSec);
            $tAvgDur = $_getters->getHoursMinuteSecunds(round($tDurationSec/$tCalls,2));
           // print_r($tDurationSec);
//            $tAvgDur     = $tAvgDur + $row["allDuration"]/$row['callCount'];
//            $tAvgDur     = $tAvgDur + $row["allDuration"]/$row['callCount'];



            $html .= '<tr class="' . $showColor . '" style="margin-top:1px; cursor:pointer;">
                        <td class="' . $showColor . '">' . $counter . '</td>';
            if ($group == 1) {
                $html .= '<td class="' . $showColor . '">' . $row["state"] . '</td>';
            }
            $html .= '<td class="' . $showColor . '">' . $row["opName"] . '</td>
                        <td class="' . $showColor . '">' . $row["callCount"] . '</td>
                        <td class="' . $showColor . '">' . $duration . '</td>
                        <td class="' . $showColor . '">' . $avgDuration . '</td>
                        <td class="' . $showColor . '">' . $totalOrder . '</td>
                        <td class="greenLine">' . $row['orderCount'] . '</td>
                        <td class="' . $showColor . '">' . $row['upsellCount'] . '</td>
                        <td class="' . $showColor . '">' . number_format($upsellPercent, 2) . '%</td>
                        <td class="redLine">' . $row['cancelCount'] . '</td>
                        <td class="' . $showColor . '">' . $row['otherCount'] . '</td>
                        <td class="' . $showDiff . ' ' . $showColor . '">' . number_format($orderPercent, 2) . '%</td>
                        <td class="' . $showDiff . ' ' . $showColor . '">' . number_format($cancelPercent, 2) . '%</td>
                        <td class="' . $showDiff . ' ' . $showColor . '">' . number_format($otherPercent, 2) . '%</td>
                    </tr>';
        }
//        print_r($html);
        $tOrdersP  = ($tSuccess / $tCalls) * 100;
        $tCancelP  = ($tCancel / $tOrders) * 100;
        $tOtherP   = ($tOther / $tCalls) * 100;
        $tUpsellsP = ($tUpsell / $tSuccess) * 100;
        //$tAvgDurat = $tAvgDur / $counter;


        $html .= ' </tbody>
                <tfoot>';
        if ($group == 1) {
            $span = 3;
        } else {
            $span = 2;
        }
        $html .= '<td colspan="' . $span . '" style="text-align:right;">TOTAL:</td>
                <td>' . $tCalls . '</td>
                <td>' . $tDuration . '</td>
                <td>' . $tAvgDur . '</td>
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



        $response = $this->render('statsin/callCenter.html.twig', array(

                '_html' => $html,
                'from' => $from,
                'to' => $to,
                'title' => $title,)
        );
        return $response;

    }

    public function projectedStatisticAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Projected Statistic';
        $conn = $this->get('database_connection');

        $_omg = new OMG($conn);
        $_stats = new STATS($conn);
        $_settings = new Settings($conn);

        $a_date = Date("Y-m-h");
        $godina = Date("Y");
        $mjesec = Date("m");
        $defaultDateFrom = $godina."-".$mjesec."-01";
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
        $source = $queryArr['ordSource'];
        $product = $queryArr['product'];
        $state = $queryArr['state'];
        $from = $queryArr['from'];
        $to = $queryArr['to'];

        if (isset($tableType) && !empty($tableType)) { $groupBy= " GROUP BY phone_order_calls.product ";        } else { $tableType = "";          $groupBy= " GROUP BY phone_order_calls.state "; }
        if (isset($product) && !empty($product))     { $prQ = " and phone_order_calls.product = '$product' ";   } else { $product = "";            $prQ = "";  }
        if (isset($state) && !empty($state))         { $scQ = " and phone_order_calls.state = '$state' ";       } else { $state = "";              $scQ = "";  }
        if (isset($from) && !empty($from))           { $dfQ = " and DATE(phone_order_calls.date) >= '$from' ";  } else { $from = $defaultDateFrom; $dfQ = " and DATE(phone_order_calls.date) >= '$from' ";  }
        if (isset($to) && !empty($to))               { $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";    } else { $to = $defaultDateTo;     $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";   }
        if (isset($source) && !empty($source))       {
            $sQ = " and phone_order_calls.orderType = '$source' ";
//            if ($source == "2"){
//                $sQ .= " AND campaignId LIKE 'sms%' ";
//            } else if ($source == "5"){
//                $sQ = " and phone_order_calls.orderType = '2' ";
//                $sQ .= " AND campaignId LIKE 'reord%' ";
//            }
        } else {
            $source = "";
            $sQ = "";
        }

        $Query = " ";  //default
        $Query .= $sQ;  //Order source
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
        $_states = $_omg->getStates();
        $_inbound_result = $_stats->getDataForNewInboundStats($Query);

        $mainTable = array();
        $newProducts = array();


        if($tableType == "") {
            foreach ($_states as $prdc){
                $mainTable[$prdc['code2']] =array();
            }
        }

        $inboundChartRedirectLink='/statsin/inboundCharts?';
        if($tableType == 1) {

            $inboundChartRedirectLink .= "ordSource=" . $source . "&country=" . $state . "&from=" . $from . "&to=" . $to;
            foreach ($_products as $prdc){
                $mainTable[$prdc['id']] =array();
                $newProducts[$prdc['id']] = $prdc['title'];
            }
        }

        foreach ($_inbound_result as $result){

            if($tableType == "") {
                $mainTable[$result['state']] = $result;
            }
            if($tableType == "1") {
                $mainTable[$result['product']] = $result;
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

        $html .=   '<td >% Orders</td>
                    <td ># Orders</td>
                    <td ># Upsell</td>
                    <td >% Cancell</td>
                    <td ># Cancell</td>
                    <td ># Total calls</td>
                    <td >% Other</td>
                    <td >Sum order</td>
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

        foreach ($mainTable as $row){
            //print_r($tableType);die();
            if((!empty ($row['state']) && $tableType == "") || (!empty ($row) && $tableType == 1) ) {
                $counter++;
                $callCount  = $row['callCount'];
                $orderCount    = $row['orderCount'];
                $otherCount    = $row['otherCount'];
                $orderPercent   = round(($orderCount/$callCount) * 100, 2);
                $upsellCount   = $row['upsellsCount'];
                $cancelCount = $row['cancelCount'];
                $orderSum = $row['orderSum'];
                $revenue = $row['revenue'];

                $perCall        = round($revenue/$callCount, 2);
                $perOrder       = round($orderSum/$orderCount, 2);
                $otherPercent   = round(($otherCount /$callCount ) * 100, 2);
                $sumStatusRe = $row['countRe'];
                $cancelPercent  = round(($cancelCount /($cancelCount+$orderCount))* 100, 2);

                $sumProjection = $orderSum - $sumStatusRe * $perOrder - $callCount;
                $projectionOrderValue = round($sumProjection/$orderCount, 2);

                //RACUNANJE TOTALA U FOOTERU
                $tCallCount     = $tCallCount + $callCount;
                $tOrderCount    = $tOrderCount + $orderCount;
                $tOtherCount    = $tOtherCount + $otherCount;
                $tUpsell        = $tUpsell +  $upsellCount;
                $tRevenue       = $tRevenue + $revenue;
                $tCancell       = $tCancell +  $cancelCount;
                $tSumOrder      = $tSumOrder + $orderSum;
//                        $tAvgCallValue  = $tAvgCallValue + $perCall;
//                        $tAvgOrderValue = $tAvgCallValue + $perOrder;
                $tSumRe         = $tSumRe + $sumStatusRe;
                $tSumProjection = $tSumProjection + $sumProjection;
                $tProjectionOrdValue = $tProjectionOrdValue + $projectionOrderValue;

                $html .= '<tr  style="margin-top:1px; cursor:pointer;">
                            <td>' . $counter . '</td>';
                if ($tableType == "") {
                    $html .= '<td>' . $row['state'] . '</td>';
                }
                if ($tableType == 1 && array_key_exists($row['product'], $newProducts)) {
                    $html .= '<td>' . $newProducts[$row['product']] . '</td>';
                }

                $html .= '<td>' . number_format($orderPercent, 2) . ' %</td>
                          <td style="background-color: ' . $this->ordersChangeCollor($orderCount) . '">' . $orderCount . '</td>
                          <td>' . $upsellCount . '</td>
                          <td>' . number_format($cancelPercent, 2) . ' %</td>
                          <td>' . $cancelCount . '</td>
                          <td>' . $callCount . '</td>
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
        $tOrdersP   = ($tOrderCount / $tCallCount) * 100;
        $tOtherP   = ($tOtherCount / $tCallCount) * 100;
        $tAvgCallValue  = round($tRevenue/$tCallCount, 2);
        $tAvgOrderValue = round($tSumOrder/$tOrderCount, 2);
        $tProjectionOrdValue = round($tSumProjection/$tOrderCount, 2);
        $tCancelPercent  = round(($tCancell /($tCancell+$tOrderCount))* 100, 2);

        $html .= '</tbody>
                    <tfoot style="text-align: center">
                    <td colspan="2" style="text-align:right;">TOTAL:</td>
                    <td>' . number_format($tOrdersP, 2) . ' %</td>
                    <td>' . $tOrderCount . '</td>
                    <td>' . $tUpsell . '</td>
                    <td>' . number_format($tCancelPercent, 2) . ' %</td>
                    <td>' . $tCancell . '</td>
                    <td>' . $tCallCount . '</td>
                    <td>' . number_format($tOtherP, 2) . ' %</td>
                    <td style="text-align: right">€ ' . number_format($tSumOrder, 2) . '</td>
                    <td style="text-align: right">€ ' . number_format($tAvgCallValue, 2) . '</td>
                    <td style="text-align: right">€ ' . number_format($tAvgOrderValue, 2) . '</td>
                    <td>' . $tSumRe . '</td>
                    <td style="text-align: right">€ ' . number_format($tProjectionOrdValue, 2) . '</td>
                    <td style="text-align: right">€ ' . number_format($tSumProjection, 2) . '</td>
                    </tfoot>
                </table>';

        $response = $this->render('statsin/projectedStatistic.html.twig', array(

                '_html' => $html,
                '_products' => $_products,
                '_states' =>$_states,
                'tableType' => $tableType,
                'from' => $from,
                'to' => $to,
                'title' => $title,
                'inboundChartRedirectLink' => $inboundChartRedirectLink)
        );
        return $response;


    }

    function sumChangeCollor($value){
        $collor = '';
        if ($value < 0){
            $collor = '#FF7575';
        } else {
            $collor = '#5EAE9E';
        }
        return $collor;
    }
    function avgCallChangeCollor($value){
        if ( 0<= $value && $value <=5 ){
            $collor = '#FF7575';
        } elseif (5 < $value && $value <= 10){
            $collor = '#E697E6';

        } elseif (10 < $value && $value <= 15){
            $collor = '#93EEAA';

        } else {
            $collor = '#5EAE9E' ;
        }
        return $collor;
    }
    function ordersChangeCollor($value){
        $collor = '';

        if (0 <= $value && $value <=10){
            $collor = '#FF7575';
        } elseif (10 < $value && $value <=20){
            $collor = '#E697E6';

        } elseif (20 < $value && $value <= 30){
            $collor = '#93EEAA';

        } else {
            $collor = '#5EAE9E' ;
        }
        return $collor;
    }
}
