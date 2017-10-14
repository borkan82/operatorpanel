<?php

namespace AppBundle\Controller;

use AppBundle\Entity\OMG;
use AppBundle\Entity\TOTAL;
use AppBundle\Entity\Main;
use AppBundle\Entity\SMS;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\GettersController;

class SummaryController extends Controller
{
    private function checkThisSession(){
//        $_main      = new Main();
//        $checkUser  = $_main->checkUserIfAdmin();
//        if ($checkUser == false){
//            return $this->redirectToRoute('login', array('status'=>'3'));
//           // return $this->redirect('../login?status=3');
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
    /**
     * @Template(engine="twig")
     */

    public function ordersAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn     = $this->get('database_connection');
        $title    = 'Orders';
        $_omg     = new OMG($conn);
        $_total = new TOTAL($conn);

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');
        $getters   = new GettersController($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $ordType    = $queryArr['ordType'];
        $outcome    = $queryArr['outcome'];
        $reason     = $queryArr['reason'];
        $ordSource  = $queryArr['ordSource'];
        $ordNum     = $queryArr['ordNum'];
        $user       = $queryArr['user'];
        $group      = $queryArr['group'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];


        if(isset($product) && !empty($product))     {
                                                        $prQ = " AND phone_order_calls.product  = '$product' ";
                                                        $spoutQ  = " and phone_order_outbound.productID = '$product' ";
                                                    } else {
                                                        $prQ = ""; $product = ""; $spoutQ  = "";

                                                    }

        if(isset($from) && !empty($from))           {
                                                        $dfQ = " AND DATE(phone_order_calls.date) >= '$from' ";
                                                        $dfoutQ = " and DATE(phone_order_outbound.callEnd) >= '$from' ";
                                                    } else {
                                                        $from = $startDate;
                                                        $dfQ = "  and DATE(phone_order_calls.date) >= '$from' ";
                                                        $dfoutQ = " and DATE(phone_order_outbound.callEnd) >= '$from' ";
                                                    }
        if(isset($to) && !empty($to))               {
                                                        $dtQ = " AND DATE(phone_order_calls.date) <= '$to' ";
                                                        $dtoutQ = " and DATE(phone_order_outbound.callEnd) <= '$to' ";
                                                    } else {
                                                        $to = $todayDate;
                                                        $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";
                                                        $dtoutQ = " and DATE(phone_order_outbound.callEnd) <= '$to' ";
                                                    }

        $Query = " 1 ";  //default
        $Query .= $prQ;  //product
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to

        $QueryOut = " 1 ";  //default
        $QueryOut .= $spoutQ;  //product
        $QueryOut .= $dfoutQ;  //date from
        $QueryOut .= $dtoutQ;  //date to

        $getInboundOrders   = $_total->getInboundOrders($Query);
        $getOutboundOrders  = $_total->getOutboundOrders($QueryOut);
        $_states            = $_omg->getPhoneStates(" AND hasSales = 1 ");
        $_products          = $_omg->getProductList();


        $ordersAll           = Array();
        $totalPhoneOrders    = Array();
        $totalByState        = Array();
        $totalAll            = 0;
        $countPerCountry     = Array();

        foreach ($getInboundOrders AS $inbounds){
            $ordersAll[$inbounds['callDate']] = Array();
            $totalPhoneOrders[$inbounds['callDate']]   = $totalPhoneOrders[$inbounds['callDate']] + $inbounds['broj'];
            $totalByState[$inbounds['state']]   = $totalByState[$inbounds['state']] + $inbounds['broj'];
            $totalAll = $totalAll + $inbounds['broj'];
        }
        foreach ($getInboundOrders AS $inbounds){
            $ordersAll[$inbounds['callDate']][$inbounds['state']] = $inbounds['broj'];
            //  print_r($inbounds);
        }

        foreach ($getOutboundOrders AS $outbounds){
            $ordersAll[$outbounds['callDate']][$outbounds['state']] = $ordersAll[$outbounds['callDate']][$outbounds['state']] + $outbounds['broj'];
            $totalPhoneOrders[$outbounds['callDate']]   = $totalPhoneOrders[$outbounds['callDate']] + $outbounds['broj'];
            $totalByState[$outbounds['state']]   = $totalByState[$outbounds['state']] + $outbounds['broj'];
            $totalAll = $totalAll + $outbounds['broj'];
        }

        $totalDays = count($ordersAll);


        $html =  '<table class="dayView compact" id="example">
                                <thead style="cursor:pointer;">
                                <tr>
                                    <td width="20px">#</td>
                                    <td >Date</td>
                                    <td>ALL</td>';

                                    foreach ($_states AS $each) {
                                        $html .= '<td>' . $each['code2'] . '</td>';
                                    }

        $html .= '</tr></thead>';

                

        $html .= '<tbody id="tabela">';

                $counter    = 0;
                $totalSMS   = 0;
                $showColor  = "";
                $totalPrice = 0.00;
                $totalMessages   = 0;

                foreach ($ordersAll as $k=>$v){
                    $counter++;

                    $html .= '<tr style="margin-top:1px; cursor:pointer;">
                                            <td class="'.$showColor.'">'.$counter.'</td>
                                            <td class="'.$showColor.'">'.$k.'</td>
                                            <td class="'.$showColor.'">'.$totalPhoneOrders[$k].'</td>';
                    foreach ($_states AS $each) {
                        $html .= '<td class="'.$showColor.'">'.(int)$v[$each['code2']].'</td>';
                    }


                    $html .='</tr>';
                }

        $html .= '</tbody>';
        $html .= '<tfoot>';

        $html .= '<tr style="text-align: center;font-weight: bold;">
                
                    <td class="" colspan="2">TOTAL:</td>
                    <td class="">'.(int)$totalAll.'</td>';

        foreach ($_states AS $each) {
            $html .= '<td class="">' . (int)$totalByState[$each["code2"]] . '</td>';
        }

        $html .= '</tr>
                  <tr style="text-align: center;font-weight: bold;">
                
                    <td class=""  colspan="2">Average:</td>
                    <td class="">'.round($totalAll/$totalDays,2).'</td>';

        foreach ($_states AS $each) {
            $html .= '<td class="">' . round($totalByState[$each["code2"]]/$totalDays,2) . '</td>';
        }

        $html .= '</tr>';

        $html .= '</tfoot></table>';


        return $this->render('summary/orders.html.twig', array(
            '_html' => $html,
            '_products' => $_products,
            'from' => $from,
            'to' => $to,
            'title' => $title));
    }

    /**
     * @Template(engine="php")
     */

    public function ordersEurAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn     = $this->get('database_connection');
        $title    = 'Orders (EUR)';

        $_omg     = new OMG($conn);
        $_total = new TOTAL($conn);

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');
        $getters   = new GettersController($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $ordType    = $queryArr['ordType'];
        $outcome    = $queryArr['outcome'];
        $reason     = $queryArr['reason'];
        $ordSource  = $queryArr['ordSource'];
        $ordNum     = $queryArr['ordNum'];
        $user       = $queryArr['user'];
        $group      = $queryArr['group'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];


        if(isset($product) && !empty($product))     {
            $prQ = " AND phone_order_calls.product  = '$product' ";
            $spoutQ  = " and phone_order_outbound.productID = '$product' ";
        } else {
            $prQ = ""; $product = ""; $spoutQ  = "";

        }

        if(isset($from) && !empty($from))           {
            $dfQ = " AND DATE(phone_order_calls.date) >= '$from' ";
            $dfoutQ = " and DATE(phone_order_outbound.callEnd) >= '$from' ";
        } else {
            $from = $startDate;
            $dfQ = "  and DATE(phone_order_calls.date) >= '$from' ";
            $dfoutQ = " and DATE(phone_order_outbound.callEnd) >= '$from' ";
        }
        if(isset($to) && !empty($to))               {
            $dtQ = " AND DATE(phone_order_calls.date) <= '$to' ";
            $dtoutQ = " and DATE(phone_order_outbound.callEnd) <= '$to' ";
        } else {
            $to = $todayDate;
            $dtQ = " and DATE(phone_order_calls.date) <= '$to' ";
            $dtoutQ = " and DATE(phone_order_outbound.callEnd) <= '$to' ";
        }

        $Query = " 1 ";  //default
        $Query .= $prQ;  //product
        $Query .= $dfQ;  //date from
        $Query .= $dtQ;  //date to

        $QueryOut = " 1 ";  //default
        $QueryOut .= $spoutQ;  //product
        $QueryOut .= $dfoutQ;  //date from
        $QueryOut .= $dtoutQ;  //date to

        $getInboundOrders   = $_total->getInboundEurOrders($Query);
        $getOutboundOrders  = $_total->getOutboundEurOrders($QueryOut);
        $_states            = $_omg->getPhoneStates(" AND hasSales = 1 ");
        $_products          = $_omg->getProductList();


        $ordersAll           = Array();
        $totalPhoneOrders    = Array();
        $totalByState        = Array();
        $totalAll            = 0;
        $countPerCountry     = Array();

        foreach ($getInboundOrders AS $inbounds){
            $ordersAll[$inbounds['callDate']] = Array();
            $totalPhoneOrders[$inbounds['callDate']]   = $totalPhoneOrders[$inbounds['callDate']] + ($inbounds['broj'] / $inbounds['exchange']);
            $totalByState[$inbounds['state']]   = $totalByState[$inbounds['state']] + ($inbounds['broj'] / $inbounds['exchange']);
            $totalAll = $totalAll + $inbounds['broj'] / $inbounds['exchange'];
        }
        foreach ($getInboundOrders AS $inbounds){
            $ordersAll[$inbounds['callDate']][$inbounds['state']] = $inbounds['broj'] / $inbounds['exchange'];
            //  print_r($inbounds);
        }

        foreach ($getOutboundOrders AS $outbounds){
            $ordersAll[$outbounds['callDate']][$outbounds['state']] = $ordersAll[$outbounds['callDate']][$outbounds['state']] + ($outbounds['broj'] / $outbounds['exchange']);
            $totalPhoneOrders[$outbounds['callDate']]   = $totalPhoneOrders[$outbounds['callDate']] + ($outbounds['broj'] / $outbounds['exchange']);
            $totalByState[$outbounds['state']]   = $totalByState[$outbounds['state']] + ($outbounds['broj'] / $outbounds['exchange']);
            $totalAll = $totalAll + $outbounds['broj'] / $outbounds['exchange'];

        }


        $totalDays = count($ordersAll);


        $html =  '<table class="dayView compact" id="example">
                                <thead style="cursor:pointer;">
                                <tr>
                                    <td width="20px">#</td>
                                    <td >Date</td>
                                    <td>ALL</td>';

        foreach ($_states AS $each) {
            $html .= '<td>' . $each['code2'] . '</td>';
        }

        $html .= '</tr></thead>';



        $html .= '<tbody id="tabela">';

        $counter    = 0;
        $totalSMS   = 0;
        $showColor  = "";
        $totalPrice = 0.00;
        $totalMessages   = 0;

        foreach ($ordersAll as $k=>$v){
            $counter++;

            $html .= '<tr style="margin-top:1px; cursor:pointer;">
                                            <td class="'.$showColor.'">'.$counter.'</td>
                                            <td class="'.$showColor.'">'.$k.'</td>
                                            <td class="'.$showColor.'">'.(int)$totalPhoneOrders[$k].'</td>';
            foreach ($_states AS $each) {
                $html .= '<td class="'.$showColor.'">'.(int)$v[$each['code2']].'</td>';
            }


            $html .='</tr>';
        }

        $html .= '</tbody>';
        $html .= '<tfoot>';

        $html .= '<tr style="text-align: center;font-weight: bold;">
                
                    <td class="" colspan="2">TOTAL:</td>
                    <td class="">'.(int)$totalAll.' €</td>';

        foreach ($_states AS $each) {
            $html .= '<td class="">' . (int)$totalByState[$each["code2"]] . ' €</td>';
        }

        $html .= '</tr>
                  <tr style="text-align: center;font-weight: bold;">
                
                    <td class=""  colspan="2">Average:</td>
                    <td class="">'.round($totalAll/$totalDays,2).' €</td>';

        foreach ($_states AS $each) {
            $html .= '<td class="">' . round($totalByState[$each["code2"]]/$totalDays,2) . ' €</td>';
        }

        $html .= '</tr>';

        $html .= '</tfoot></table>';


        return $this->render('summary/ordersEur.html.twig', array(
            '_html' => $html,
            '_products' => $_products,
            'from' => $from,
            'to' =>$to,
            'title' => $title
        ));
    }

    /**
     * @Template(engine="php")
     */

    public function ordersDocumentAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        $title    = '';

        return $this->render('summary/ordersDocument.html.twig', array(
            'title' => $title
        ));
    }

    /**
     * @Template(engine="twig")
     */

    public function reportAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        $title    = 'Sales report';

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        if(isset($from) && !empty($from))   {
                                                $dfQ    = "  and DATE(orders.orderdate) >= '$from' ";
//                                                $dfQLW  = "  and DATE(orders.orderdate) >= DATE_SUB('$from', INTERVAL 7 DAY) ";
                                            } else {
                                                $from   = $startDate;
                                                $dfQ    = "  and DATE(orders.orderdate) >= '$startDate' ";

        }
        if(isset($to) && !empty($to))       { $dtQ      = " and DATE(orders.orderdate) <= '$to' ";
//                                              $dtQLW    = " and DATE(orders.orderdate) <= DATE_SUB('$to', INTERVAL 7 DAY ) ";
                                            } else {
                                              $to       = $todayDate;
                                              $dtQ      = " and DATE(orders.orderdate) <= '$todayDate' ";
//                                              $dtQLW    = " and DATE(orders.orderdate) <= DATE_SUB('$todayDate', INTERVAL 7 DAY )";
                                            }

        $f = date_create($from);
        $t = date_create($to);

        $periodBetweenTwoDays = date_diff($f, $t);
        $period =$periodBetweenTwoDays->days +1;
        $dfQLW  = " and DATE(orders.orderdate) >= DATE_SUB('$from', INTERVAL ".$period." DAY) ";
        $dtQLW  = " and DATE(orders.orderdate) <= DATE_SUB('$to', INTERVAL ".$period." DAY) ";

        $Query = " 1 ";   //default
        $Query .= $dfQ;   //date from
        $Query .= $dtQ;   //date to

        $QueryLW = " 1 ".$dfQLW."".$dtQLW;
        //print_r($QueryLW);die();


        $conn = $this->get('database_connection');
        $_data = $conn->fetchAll("SELECT state, DATE(orders.orderdate) AS orderDate, extint2, utm_source, utm_campaign, ordersource
                                  FROM orders
                                  WHERE {$Query}
                                  AND (ordersource = 'PHN' || (ordersource = 'LPB' && utm_source='sms'))");

        $_data2 = $conn->fetchAll("SELECT state, DATE(orders.orderdate) AS orderDate, extint2, utm_source, utm_campaign, comment
                                      FROM orders
                                      WHERE {$Query}
                                      AND ordersource != 'PHN' 
                                      AND (utm_source = 'mail' OR utm_source = 'mailreorder' OR utm_source = 'mailwarehouse' OR (comment LIKE 'Canceled%' AND orderstatus != 12 AND orderstatus != 8 AND orderstatus != 10))");

        $_states = $conn->fetchAll("SELECT code2, title_eng FROM `phone_order_smsprices`
                                      LEFT JOIN states ON phone_order_smsprices.state = states.code2
                                      WHERE 1 AND hasSales = 1 AND stateIsActive = 1 ORDER BY code2 ASC");

        // Data for last week
        $_dataLW = $conn->fetchAll("SELECT state, DATE(orders.orderdate) AS orderDate, extint2, utm_source, utm_campaign, ordersource
                                  FROM orders
                                  WHERE {$QueryLW}
                                  AND (ordersource = 'PHN' || (ordersource = 'LPB' && utm_source='sms'))");

        $_data2LW = $conn->fetchAll("SELECT state, DATE(orders.orderdate) AS orderDate, extint2, utm_source, utm_campaign, comment
                                      FROM orders
                                      WHERE {$QueryLW}
                                      AND ordersource != 'PHN' 
                                      AND (utm_source = 'mail' OR utm_source = 'mailreorder' OR utm_source = 'mailwarehouse' OR (comment LIKE 'Canceled%' AND orderstatus != 12 AND orderstatus != 8 AND orderstatus != 10))");




        $phntypesArr        = Array(
            1 => Array("title"=>"Inbound Panel","url"=>"../summary/summaryInboundReport?orderType=1&user=&product=&state=&from=$from&to=$to"),
            2 => Array("title"=>"E-Mail","url"=>""),
            3 => Array("title"=>"Reorder Mail","url"=>""),
            4 => Array("title"=>"SMS","url"=>"../summary/summaryInboundReport?orderType=2&user=&product=&state=&from=$from&to=$to"),
            5 => Array("title"=>"SMS reorder","url"=>"../summary/summaryInboundReport?orderType=4&product=&state=&from=$from&to=$to"),
            6 => Array("title"=>"Cancel Mail","url"=>""),
            7 => Array("title"=>"Undecided panel","url"=>"../summary/summaryOutboundReport?type=9&product=&state=&from=$from&to=$to"),
            8 => Array("title"=>"Order fill brake","url"=>"../summary/summaryOutboundReport?type=6&product=&state=&from=$from&to=$to"),
            9 => Array("title"=>"Reorder call","url"=>"../summary/summaryOutboundReport?type=7&product=&state=&from=$from&to=$to"),
            10 => Array("title"=>"Reorder mail call","url"=>"../summary/summaryOutboundReport?type=10&product=&state=&from=$from&to=$to"),
            11 => Array("title"=>"Bulk call","url"=>"../summary/summaryOutboundReport?type=8&product=&state=&from=$from&to=$to"),
            12 => Array("title"=>"SMS Link (Outbound)","url"=>"../summary/summaryOutboundReport?type=11&product=&state=&from=$from&to=$to"),
            13 => Array("title"=>"Warehouse mails"),
            14 => Array("title"=>"SMS link (Pageorder)"),
            15 => Array("title"=>"Undecided Presell","url"=>"../summary/summaryOutboundReport?type=12&product=&state=&from=$from&to=$to"),
            16 => Array("title"=>"AdCombo","url"=>"../summary/summaryOutboundReport?type=1&product=&state=&from=$from&to=$to"),
            17 => Array("title"=>"RE SMS Link (Outbound)","url"=>"../summary/summaryOutboundReport?type=13&product=&state=&from=$from&to=$to")
        );

        $totalByState        = Array();
        $totalAll            = 0;
        $totalAllLW          = 0;
        $totalByType         = Array();
        $totalByTypeLW       = Array();

        $hiValue    = 0;
       // print_r($_data);die();
        foreach ($_data AS $oRow){

            if ($oRow['extint2'] == 1 || $oRow['extint2'] == 2 || $oRow['utm_source'] == 'sms'){
                if ($oRow['utm_source'] == "sms") {

                    if (strpos($oRow['utm_campaign'], 'sms') !== false && $oRow['ordersource'] == "PHN"){
                        $phntypesArr[4][$oRow['state']] = $phntypesArr[4][$oRow['state']] + 1;
                        $totalByType[4] = $totalByType[4] + 1;
                        $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                        if ($phntypesArr[4][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[4][$oRow['state']];
                        }
                        $totalAll++;
                    } else if (strpos($oRow['utm_campaign'], 'sms') !== false && $oRow['ordersource'] == "LPB"){
                        $phntypesArr[14][$oRow['state']] = $phntypesArr[14][$oRow['state']] + 1;
                        $totalByType[14] = $totalByType[14] + 1;
                        $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                        if ($phntypesArr[14][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[14][$oRow['state']];
                        }
                        $totalAll++;
                    } else if (strpos($oRow['utm_campaign'], 'reord') !== false){
                        $phntypesArr[5][$oRow['state']] = $phntypesArr[5][$oRow['state']] + 1;
                        $totalByType[5] = $totalByType[5] + 1;
                        $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                        if ($phntypesArr[5][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[5][$oRow['state']];
                        }
                        $totalAll++;
                    }
                } else if ($oRow['utm_source'] != "sms" && $oRow['utm_source'] != "" && $oRow['utm_source'] != "outbound" && $oRow['extint2'] == 1 && $oRow['ordersource'] == "PHN"){
                    $phntypesArr[1][$oRow['state']] = $phntypesArr[1][$oRow['state']] + 1;
                    $totalByType[1] = $totalByType[1] + 1;
                    $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                    if ($phntypesArr[1][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[1][$oRow['state']];
                        }
                    $totalAll++;
                }

            } else if ($oRow['extint2'] == 3) {
                $phntypesArr[8][$oRow['state']] = $phntypesArr[8][$oRow['state']] + 1;
                $totalByType[8] = $totalByType[8] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[8][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[8][$oRow['state']];
                        }
                $totalAll++;
            } else if ($oRow['extint2'] == 4) {
                $phntypesArr[9][$oRow['state']] = $phntypesArr[9][$oRow['state']] + 1;
                $totalByType[9] = $totalByType[9] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[9][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[9][$oRow['state']];
                        }
                $totalAll++;
            } else if ($oRow['extint2'] == 5) {
                $phntypesArr[11][$oRow['state']] = $phntypesArr[11][$oRow['state']] + 1;
                $totalByType[11] = $totalByType[11] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[11][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[11][$oRow['state']];
                        }
                $totalAll++;
            } else if ($oRow['extint2'] == 6) {
                $phntypesArr[7][$oRow['state']] = $phntypesArr[7][$oRow['state']] + 1;
                $totalByType[7] = $totalByType[7] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[7][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[7][$oRow['state']];
                        }
                $totalAll++;
            } else if ($oRow['extint2'] == 7) {
                $phntypesArr[10][$oRow['state']] = $phntypesArr[10][$oRow['state']] + 1;
                $totalByType[10] = $totalByType[10] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[10][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[10][$oRow['state']];
                        }
                $totalAll++;
            } else if ($oRow['extint2'] == 8) {
                $phntypesArr[12][$oRow['state']] = $phntypesArr[12][$oRow['state']] + 1;
                $totalByType[12] = $totalByType[12] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[12][$oRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[12][$oRow['state']];
                        }
                $totalAll++;
            } else if ($oRow['extint2'] == 9) {
                $phntypesArr[15][$oRow['state']] = $phntypesArr[15][$oRow['state']] + 1;
                $totalByType[15] = $totalByType[15] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[15][$oRow['state']] > $hiValue){
                    $hiValue = $phntypesArr[15][$oRow['state']];
                }
                $totalAll++;
            } else if ($oRow['extint2'] == 10) {
                $phntypesArr[16][$oRow['state']] = $phntypesArr[16][$oRow['state']] + 1;
                $totalByType[16] = $totalByType[16] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[16][$oRow['state']] > $hiValue){
                    $hiValue = $phntypesArr[16][$oRow['state']];
                }
                $totalAll++;
            } else if ($oRow['extint2'] == 11) {
                $phntypesArr[17][$oRow['state']] = $phntypesArr[17][$oRow['state']] + 1;
                $totalByType[17] = $totalByType[17] + 1;
                $totalByState[$oRow['state']] = $totalByState[$oRow['state']] + 1;
                if ($phntypesArr[17][$oRow['state']] > $hiValue){
                    $hiValue = $phntypesArr[17][$oRow['state']];
                }
                $totalAll++;
            }

        }

        foreach ($_data2 AS $mRow){

            if ($mRow['utm_source'] == "mail") {
                $phntypesArr[2][$mRow['state']] = $phntypesArr[2][$mRow['state']] + 1;
                $totalByType[2] = $totalByType[2] + 1;
                $totalByState[$mRow['state']] = $totalByState[$mRow['state']] + 1;
                if ($phntypesArr[2][$mRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[2][$mRow['state']];
                        }
                $totalAll++;
            } else if ($mRow['utm_source'] == "mailreorder") {
                $phntypesArr[3][$mRow['state']] = $phntypesArr[3][$mRow['state']] + 1;
                $totalByType[3] = $totalByType[3] + 1;
                $totalByState[$mRow['state']] = $totalByState[$mRow['state']] + 1;
                if ($phntypesArr[3][$mRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[3][$mRow['state']];
                        }
                $totalAll++;
            } else if ($mRow['utm_source'] == "mailwarehouse") {
                $phntypesArr[13][$mRow['state']] = $phntypesArr[13][$mRow['state']] + 1;
                $totalByType[13] = $totalByType[13] + 1;
                $totalByState[$mRow['state']] = $totalByState[$mRow['state']] + 1;
                if ($phntypesArr[13][$mRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[13][$mRow['state']];
                        }
                $totalAll++;
            } else if (strpos($mRow['comment'], 'Canceled') !== false) {
                $phntypesArr[6][$mRow['state']] = $phntypesArr[6][$mRow['state']] + 1;
                $totalByType[6] = $totalByType[6] + 1;
                $totalByState[$mRow['state']] = $totalByState[$mRow['state']] + 1;
                if ($phntypesArr[6][$mRow['state']] > $hiValue){
                            $hiValue = $phntypesArr[6][$mRow['state']];
                        }
                $totalAll++;
            }


        }

        $step1 = round($hiValue/5);
        $step2 = round($hiValue/5*2);
        $step3 = round($hiValue/5*3);
        $step4 = round($hiValue/5*4);


        foreach ($_dataLW AS $oRowLW){

            if ($oRowLW['extint2'] == 1 || $oRowLW['extint2'] == 2 || $oRowLW['utm_source'] == 'sms'){
                if ($oRowLW['utm_source'] == "sms") {

                    if (strpos($oRowLW['utm_campaign'], 'sms') !== false && $oRowLW['ordersource'] == "PHN"){
                        $totalByTypeLW[4] = $totalByTypeLW[4] + 1;
                        $totalAllLW++;
                    } else if (strpos($oRowLW['utm_campaign'], 'sms') !== false && $oRowLW['ordersource'] == "LPB"){
                        $totalByTypeLW[14] = $totalByTypeLW[14] + 1;
                        $totalAllLW++;
                    } else if (strpos($oRowLW['utm_campaign'], 'reord') !== false){
                        $totalByTypeLW[5] = $totalByTypeLW[5] + 1;
                        $totalAllLW++;
                    }
                } else if ($oRowLW['utm_source'] != "sms" && $oRowLW['utm_source'] != "" && $oRowLW['utm_source'] != "outbound" && $oRowLW['extint2'] == 1 && $oRowLW['ordersource'] == "PHN") {
                    $totalByTypeLW[1] = $totalByTypeLW[1] + 1;
                    $totalAllLW++;
                }

            } else if ($oRowLW['extint2'] == 3) {
                $totalByTypeLW[8] = $totalByTypeLW[8] + 1;
                $totalAllLW++;
            } else if ($oRowLW['extint2'] == 4) {
                $totalByTypeLW[9] = $totalByTypeLW[9] + 1;
                $totalAllLW++;
            } else if ($oRowLW['extint2'] == 5) {
                $totalByTypeLW[11] = $totalByTypeLW[11] + 1;
                $totalAllLW++;
            } else if ($oRowLW['extint2'] == 6) {
                $totalByTypeLW[7] = $totalByTypeLW[7] + 1;
                $totalAllLW++;
            } else if ($oRowLW['extint2'] == 7) {
                $totalByTypeLW[10] = $totalByTypeLW[10] + 1;
                $totalAllLW++;
            } else if ($oRowLW['extint2'] == 8) {
                $totalByTypeLW[12] = $totalByTypeLW[12] + 1;
                $totalAllLW++;
            } else if ($oRowLW['extint2'] == 9) {
                $totalByTypeLW[15] = $totalByTypeLW[15] + 1;
                $totalAllLW++;
            } else if ($oRowLW['extint2'] == 10) {
                $totalByTypeLW[16] = $totalByTypeLW[16] + 1;
                $totalAllLW++;
            }  else if ($oRowLW['extint2'] == 11) {
                $totalByTypeLW[17] = $totalByTypeLW[17] + 1;
                $totalAllLW++;
            }

        }

        foreach ($_data2LW AS $mRowLW){

            if ($mRowLW['utm_source'] == "mail") {
                $totalByTypeLW[2] = $totalByTypeLW[2] + 1;
                $totalAllLW++;
            } else if ($mRowLW['utm_source'] == "mailreorder") {
                $totalByTypeLW[3] = $totalByTypeLW[3] + 1;
                $totalAllLW++;
            } else if ($mRowLW['utm_source'] == "mailwarehouse") {
                $totalByTypeLW[13] = $totalByTypeLW[13] + 1;
                $totalAllLW++;
            } else if (strpos($mRowLW['comment'], 'Canceled') !== false) {
                $totalByTypeLW[6] = $totalByTypeLW[6] + 1;
                $totalAllLW++;
            }


        }


        $html    ='<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr>
                        <td width="20px">#</td>
                        <td >Order Type</td>';


                        foreach ($_states AS $each) {
                            $html    .= '<td>' . $each['code2'] . '</td>';
                        }

        $html    .= ' <td>ALL</td><td>Week Difference</td>
                     </tr>
                     <tr >
                       <td class="" colspan="2">TOTAL:</td>';

                        foreach ($_states AS $each) {

                            $html    .='<td >' . (int)$totalByState[$each["code2"]] . '</td>';
                        }

        $percentStyle = '<span style="color:green">+';
        $difference = $totalAll - $totalAllLW;
        if ($difference < 0) {
            $percentStyle = '<span style="color:red">';
        } else if ($difference == 0) {
            $percentStyle = '<span style="color:green">+/-';
        }
        $totalPercent   =  round(($totalAll - $totalAllLW) *100 / $totalAllLW, 2);
        $html    .=  '  <td>'.$totalAll.'</span></td><td>'.$percentStyle.''.$totalPercent.'%</span> ('.$totalAllLW.')</td>
                        </tr>
                    </thead>
                    <tbody id="tabela">';

        $counter    = 0;
        $totalSMS   = 0;
        $showColor  = "";
        $totalPrice = 0.00;
        $totalMessages   = 0;

        foreach ($phntypesArr as $k=>$v){
            $counter++;


            $externalLink = $v['title'];
            if ($v['url'] != ""){
                $externalLink = '<a href="'.$v['url'].'" target="_blank">'.$v['title'].'</a>';
            }
            $html    .= '<tr style="margin-top:1px; cursor:pointer;">
                            <td class="'.$showColor.'">'.$counter.'</td>
                            <td class="'.$showColor.'">'.$externalLink.'</td>';

            foreach ($_states AS $each) {

                            $stValue = (int)$v[$each['code2']];

                            $klasa  = "";
                            if ($stValue > 0 && $stValue < $step1) {
                                $klasa = ' class="green0" ';
                            } else if ($stValue > $step1 && $stValue < $step2) {
                                $klasa = ' class="green1" ';
                            } else if ($stValue > $step2 && $stValue < $step3) {
                                $klasa = ' class="green2" ';
                            } else if ($stValue > $step3 && $stValue < $step4) {
                                $klasa = ' class="green3" ';
                            } else if ($stValue > $step4) {
                                $klasa = ' class="green4" ';
                            }

                $html    .= '<td '.$klasa.'>'.(int)$v[$each['code2']].'</td>';
            }

            $percentStyle = '<span style="color:green">+';
            $difference = $totalByType[$k] - $totalByTypeLW[$k];
            if ($difference < 0) {
                $percentStyle = '<span style="color:red"> ';
            } else if ((int)$totalByTypeLW[$k] == 0 || $difference == 0 ) {
                $percentStyle = '<span style="color:green">+/-';
            }
            $totalPercent   =  round(($totalByType[$k] - $totalByTypeLW[$k]) *100 / $totalByTypeLW[$k], 2);
            $html    .= '<td class="'.$showColor.'">'.(int)$totalByType[$k].' </td><td>'.$percentStyle.''.$totalPercent.'%</span> <span style="color:#777">('.(int)$totalByTypeLW[$k].')</span></td>';
            $html    .='</tr>';
        }


        $html   .= '</tbody>
                    </table>';
        $test = "";
        return $this->render('summary/report.html.twig', array('test' => $test,
                                                                '_html' => $html,
                                                                '_states' => $_states,
                                                                'from' => $from,
                                                                'to' =>$to,
                                                                'title' => $title));
    }

    /**
     * @Template(engine="twig")
     */

    public function summaryReportAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        $title    = ' Summary report';

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $conn = $this->get('database_connection');
        $getters    = new GettersController($conn);

        $_users     = $getters->getUsersAction();
        $_products  = $getters->getProductListAction("id, title", "1");
        $_centers   = $getters->getCallCenterListAction();
        $_states    = $getters->getStatesAction();

        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        if(isset($from) && !empty($from))   { $dfQ = "  and DATE(orders.orderdate) >= '$from' ";   } else { $dfQ = "  and DATE(orders.orderdate) >= '$startDate' "; $from=$startDate;}
        if(isset($to) && !empty($to))       { $dtQ = " and DATE(orders.orderdate) <= '$to' ";      } else { $dtQ = " and DATE(orders.orderdate) <= '$todayDate' "; $to = $todayDate;}

        $html = "";

        return $this->render('summary/summaryReport.html.twig', array(
            '_html' => $html,
            '_states' => $_states,
            '_products' => $_products,
            '_centers' => $_centers,
            '_users' => $_users,
            'from' => $from,
            'to' =>$to,
            'title' => $title));
    }

    /**
     * @Template(engine="twig")
     */

    public function summaryInboundReportAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        $title    = ' Inbound Summary report';

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $conn = $this->get('database_connection');
        $getters    = new GettersController($conn);
        $_sms       = new SMS($conn);

        $_users     = $getters->getUsersAction();
        $_products  = $getters->getProductListAction("id, title", "1");
        $_centers   = $getters->getCallCenterListAction();
        $_mainCenters   = $getters->getMainCallCenterListAction("", " AND id != 17 ");
        $_states    = $getters->getStatesAction();
        $_ostatuses = $getters->getOrderStatusesAction();
        $_campaigns = $_sms->getCampaignList();

        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        if(isset($from) && !empty($from))   { $dfQ = "  and DATE(phn.date) >= '$from' ";   } else { $dfQ = "  and DATE(phn.date) >= '$startDate' "; $from=$startDate;}
        if(isset($to) && !empty($to))       { $dtQ = " and DATE(phn.date) <= '$to' ";      } else { $dtQ = " and DATE(phn.date) <= '$todayDate' "; $to = $todayDate;}

        $html = "";

        return $this->render('summary/summaryInbound.html.twig', array(
            '_html' => $html,
            '_states' => $_states,
            '_products' => $_products,
            '_centers' => $_centers,
            '_maincenters' => $_mainCenters,
            '_users' => $_users,
            '_campaigns' => $_campaigns,
            '_ostatuses' => $_ostatuses,
            'from' => $from,
            'to' =>$to,
            'title' => $title));
    }

    /**
     * @Template(engine="twig")
     */

    public function summaryOutboundReportAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        $title    = ' Outbound Summary report';

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $conn = $this->get('database_connection');
        $getters    = new GettersController($conn);

        $_users         = $getters->getUsersAction();
        $_products      = $getters->getProductListAction("id, title", "1");
        $_centers       = $getters->getCallCenterListAction();
        $_mainCenters   = $getters->getMainCallCenterListAction("", " AND id != 17 "); // AL AlbaContact excluded from list
        $_states        = $getters->getStatesAction();
        $_types         = $getters->getOutboundCallTypes();
        $_statuses      = $getters->getOutboundStatusesAction();
        $_substatuses   = $getters->getOutboundSubStatusesAction();
        $_ostatuses     = $getters->getOrderStatusesAction();
        $_ftypes        = $getters->getFlowTypesAction();

        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        if(isset($from) && !empty($from))   { $dfQ = "  and DATE(phn.date) >= '$from' ";   } else { $dfQ = "  and DATE(phn.date) >= '$startDate' "; $from=$startDate;}
        if(isset($to) && !empty($to))       { $dtQ = " and DATE(phn.date) <= '$to' ";      } else { $dtQ = " and DATE(phn.date) <= '$todayDate' "; $to = $todayDate;}

        $html = "";

        return $this->render('summary/summaryOutbound.html.twig', array(
            '_html' => $html,
            '_states' => $_states,
            '_products' => $_products,
            '_centers' => $_centers,
            '_maincenters' => $_mainCenters,
            '_users' => $_users,
            '_types' => $_types,
            '_statuses' => $_statuses,
            '_substatuses' => $_substatuses,
            '_ostatuses' => $_ostatuses,
            '_ftypes' => $_ftypes,
            'from' => $from,
            'to' =>$to,
            'title' => $title));
    }
}
