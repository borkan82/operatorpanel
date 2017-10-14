<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;

use AppBundle\Entity\Main;
use AppBundle\Entity\Settings;

class AjaxController extends Controller
{
    /**
     * @Template(engine="php")
     */

    public function orderDataAction()
    {

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

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $user       = $queryArr['user'];
        $group      = $queryArr['group'];
        
        $brakeBy    = $queryArr['brake'];

        $brakeField = $brakeBy;
        $joinLine   = "";
        if ($brakeField == "tmp.product_main_id"){
            $brakeField = "prTitle";
            $joinLine   = "LEFT JOIN products ON tmp.product_main_id = products.id";
            $showField  = "products.title AS prTitle,";
        } else if ($brakeField == "tmp.state_id"){
            $brakeField = "stateTitle";
            $joinLine   = "LEFT JOIN states ON tmp.state_id = states.id";
            $showField  = "states.title_eng AS stateTitle,";
        } else if ($brakeField == "orders.extint2"){
            $brakeField = "typeName";
            $joinLine   = "LEFT JOIN phone_order_types ON orders.extint2 = phone_order_types.id";
            $showField  = "phone_order_types.typename AS typeName,";
        }

        if(isset($from) && !empty($from)) { $dfQ = "  and DATE(tmp.order_date) >= '$from' "; } else { $dfQ = "  and DATE(tmp.order_date) >= '$startDate' "; }
        if(isset($to) && !empty($to))     { $dtQ = " and DATE(tmp.order_date) <= '$to' ";    } else { $dtQ = " and DATE(tmp.order_date) <= '$todayDate' "; }
        if(isset($state) && !empty($state))   { $sQ = " and tmp.state_id = '$state' ";    } else { $sQ = ""; }
        if(isset($product) && !empty($product))   { $pQ = " and tmp.product_main_id = '$product' ";    } else { $pQ = ""; }
        if(isset($user) && !empty($user))   { $uQ = " and orders.extint1 = '$user' ";    } else { $uQ = ""; }
        if(isset($group) && !empty($group))   { $gQ = " and orders.extint1 = '$group' ";    } else { $gQ = ""; }
        if(isset($brakeBy) && !empty($brakeBy))  { $brakeQ = $brakeBy;  } else { $brakeQ = "order_date"; }


        $Query = " 1 ";   //default
        $Query .= $dfQ;   //date from
        $Query .= $dtQ;   //date to
        $Query .= $sQ;    //state
        $Query .= $pQ;    //product
        $Query .= $uQ;    //user
        $Query .= $gQ;    //group


        $conn = $this->get('database_connection');

        $_data = $conn->fetchAll("SELECT order_date, count(*) as revenueNo, {$showField}
                                        SUM(IFNULL(tmp.order_value, 0) + IFNULL(tmp.order_value_vat,0)) AS `revenue`,
                                        SUM(IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0)) AS `suminvoices`,
                                        SUM(IF(tmp.order_status = 103, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumpayments`,
                                        SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.document_value_vat,0),0)) as `valuevat`,
                                        SUM(IF(tmp.order_status = 104, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumreturn`,
                                        SUM(IF(tmp.order_status = 105, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumrefund`,
                                        SUM(IF(tmp.order_status =  103,IFNULL(tmp.document_value,0),0)) 
                                            - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.document_value_vat,0),0)) 
                                            - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0)) 
                                            - SUM(IF(tmp.document_id IS NOT NULL ,IFNULL(tmp.shipping_cost,0),0)) 
                                            AS `t_gross_profit`,
                                        SUM(IF(tmp.order_status NOT IN (103,104,105), IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `unknown`,
                                        SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0)) AS `t_products_cost`,
                                        SUM(IF(tmp.document_id IS NOT NULL, IFNULL(tmp.shipping_cost,0),0)) AS `t_sr_cost`
                                FROM `analytics_sales_upsell_order` as tmp
                                LEFT JOIN orders ON tmp.order_id = orders.order_id
                                {$joinLine}
                                WHERE {$Query} AND orders.ordersource = 'PHN'
                                GROUP BY {$brakeQ} ORDER BY tmp.order_id DESC");


        $html    ='<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr>
                        <td width="20px">Brake key</td>
                        <td >Revenue no.</td>
                        <td >Revenue</td>
                        <td >Invoices</td>
                        <td >Payments</td>
                        <td >VAT</td>
                        <td >Pr.Cost</td>
                        <td >S&R Cost</td>
                        <td >G.Profit</td>
                        <td >Returns</td>
                        <td >Refunds</td>
                        <td >Unknown</td>
                        <td >Revenue Pay. %</td>
                        <td >Invoices Pay. %</td>
                        <td >Returns %</td>
                        <td >No status %</td></thead><tbody>';

        $showColor  = "";
        $tRevNo     = "";
        $tReven     = "";
        $tInvoice   = "";
        $tPayments  = "";
        $tvaluevat  = "";
        $tsumreturn = "";
        $tsumrefund = "";
        $tunknown   = "";
        $tprodcost = "";
        $tsrcost = "";
        $tgross   = "";


        foreach ($_data AS $oRow) {

            $html    .= '<tr style="margin-top:1px; cursor:pointer;">
                            <td class="'.$showColor.'">'.$oRow[$brakeField].'</td>
                            <td class="'.$showColor.'">'.$oRow['revenueNo'].'</td>
                            <td class="'.$showColor.'">'.round($oRow['revenue'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['suminvoices'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['sumpayments'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['valuevat'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['t_products_cost'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['t_sr_cost'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['t_gross_profit'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['sumreturn'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['sumrefund'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['unknown'], 2).'</td>
                            <td class="'.$showColor.'">'.round($oRow['sumpayments']/$oRow['revenue']*100, 2).' %</td>
                            <td class="'.$showColor.'">'.round($oRow['sumpayments']/$oRow['suminvoices']*100, 2).' %</td>
                            <td class="'.$showColor.'">'.round($oRow['sumreturn']/$oRow['suminvoices']*100, 2).' %</td>
                            <td class="'.$showColor.'">'.round(($oRow['suminvoices']-$oRow['sumpayments'])/$oRow['suminvoices']*100, 2).' %</td>
                         </tr>';

            $tRevNo     = $tRevNo + $oRow['revenueNo'];
            $tReven     = $tReven + $oRow['revenue'];
            $tInvoice   = $tInvoice + $oRow['suminvoices'];
            $tPayments  = $tPayments + $oRow['sumpayments'];
            $tvaluevat  = $tvaluevat + $oRow['valuevat'];
            $tprodcost  = $tprodcost + $oRow['t_products_cost'];
            $tsrcost    = $tsrcost + $oRow['t_sr_cost'];
            $tgross     = $tgross + $oRow['t_gross_profit'];
            $tsumreturn = $tsumreturn + $oRow['sumreturn'];
            $tsumrefund = $tsumrefund + $oRow['sumrefund'];
            $tunknown   = $tunknown + $oRow['unknown'];


        }
        $tprReven   = $tPayments / $tReven * 100;
        $tpInvoice  = $tPayments / $tInvoice * 100;
        $tpReturns  = $tsumreturn / $tInvoice * 100;
        $tpNostatus = 100 - $tpInvoice - $tpReturns;

        $html   .= '</tbody>';
        $html   .= '<tfoot><tr style="margin-top:1px; cursor:pointer;font-weight: bold;text-align: center;">';
        $html   .= '<td>Totals:</td>
                    <td>'.round($tRevNo,2).'</td>
                    <td>'.round($tReven,2).'</td>
                    <td>'.round($tInvoice,2).'</td>
                    <td>'.round($tPayments,2).'</td>
                    <td>'.round($tvaluevat,2).'</td>
                    <td>'.round($tprodcost,2).'</td>
                    <td>'.round($tsrcost,2).'</td>
                    <td>'.round($tgross,2).'</td>
                    <td>'.round($tsumreturn,2).'</td>
                    <td>'.round($tsumrefund,2).'</td>
                    <td>'.round($tunknown,2).'</td>
                    <td>'.round($tprReven,2).'</td>
                    <td>'.round($tpInvoice,2).'</td>
                    <td>'.round($tpReturns,2).'</td>
                    <td>'.round($tpNostatus,2).'</td>';
        $html   .= '</tr></tfoot>';
        $html   .= '</table>';


        return new Response(json_encode($html));
    }

    /**
     * @Template(engine="php")
     */

    public function inboundOrdersAction()
    {

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $_main      = new Main();

        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $operator   = $queryArr['operator'];
        $callgroup  = $queryArr['callgroup'];
        $callcenter = $queryArr['callcenter'];
        $type       = $queryArr['orderType'];
        $datum      = $queryArr['datum'];
        $campaign   = $queryArr['campaign'];
        $ostatus    = $queryArr['order_status'];

        $brakeBy    = $queryArr['brake'];

        $brakeField = $brakeBy;
        $joinLine   = "";
        $rowField   = "";
        $qField     = "";
        if ($brakeField == "phn.product"){
            $brakeField = "product";
            $joinLine   = "LEFT JOIN products ON phn.product = products.id";
            $showField  = "products.title AS product,";
            $rowField   = "product";
            $qField     = "product";
        } else if ($brakeField == "phn.state"){
            $brakeField = "state";
            $joinLine   = "LEFT JOIN states ON phn.state = states.code2";
            $showField  = "states.code2 AS state,";
            $rowField   = "state";
            $qField     = "state";
        } else if ($brakeField == "phn.orderType"){
            $brakeField = "orderType";
            $joinLine   = "LEFT JOIN phone_order_orderTypes ON phn.orderType = phone_order_orderTypes.id";
            $showField  = "phone_order_orderTypes.title AS orderType,";
            $rowField   = "orderType";
            $qField     = "ordType";
        } else if ($brakeField == "phn.date" || $brakeField == "Date%28phn.date%29"){
            $brakeField = "datum";
            $brakeBy = "Date(phn.date)";
            $rowField   = "datum";
            $qField     = "from";
        } else if ($brakeField == "phn.operator"){
            $brakeField = "operator";
           // $joinLine   = "LEFT JOIN phone_order_users ON phn.operator = phone_order_users.id";
            $showField  = "user.fullname AS operator,";
            $rowField   = "operator";
            $qField     = "user";
        } else if ($brakeField == "phn.campaignId"){
            $brakeField = "campaign";
            $joinLine   = "";
            $showField  = "phn.campaignId AS campaign,";
            $rowField   = "campaign";
            $qField     = "campaign";
        } else if ($brakeField == "center.main_call_center_id"){
            $brakeField = "callcenter";
            $joinLine   = "
                           LEFT JOIN phone_order_callcenter AS center ON user.operatorGroup = center.id
                           LEFT JOIN phone_order_maincallcenter ON center.main_call_center_id = phone_order_maincallcenter.id";
            $showField  = "phone_order_maincallcenter.name AS callcenter,";
            $rowField   = "callcenter";
            $qField     = "callcenter";
        } else if ($brakeField == "tmp.order_status"){
            $brakeField = "order_status";
            $joinLine   = "LEFT JOIN analytics_sales_order_status ON tmp.order_status = analytics_sales_order_status.id";
            $showField  = "analytics_sales_order_status.status_name AS order_status,";
            $rowField   = "order_status";
            $qField     = "ostatus";
        }

        if(isset($from) && !empty($from)) { $dfQ = "  and Date(phn.date) >= '$from' "; } else { $dfQ = "  and Date(phn.date) >= '$startDate' "; $from = $startDate; }
        if(isset($to) && !empty($to))     { $dtQ = " and Date(phn.date) <= '$to' ";    } else { $dtQ = " and Date(phn.date) <= '$todayDate' "; $to = $todayDate; }
        if(isset($state) && !empty($state))   { $sQ = " and phn.state = '$state' ";    } else { $sQ = ""; }
        if(isset($product) && !empty($product))   { $pQ = " and phn.product = '$product' ";    } else { $pQ = ""; }
        if(isset($operator) && !empty($operator))   { $uQ = " and phn.operator = '$operator' ";    } else { $uQ = ""; }
        if(isset($type) && !empty($type))   { $tQ = " and phn.orderType = '$type' ";    } else { $tQ = ""; }
        if(isset($datum) && !empty($datum))   { $daQ = "  and Date(phn.date) = '$datum' ";   } else { $daQ = ""; }
        if(isset($campaign) && !empty($campaign))   { $caQ = "  and phn.campaignId = '$campaign' ";   } else { $caQ = ""; }
        if(isset($ostatus) && !empty($ostatus))   { $osQ = " and tmp.order_status = '$ostatus' ";    } else { $osQ = ""; }
        if(isset($callcenter) && !empty($callcenter))   { $joinLine   .= " 
                                                                           LEFT JOIN phone_order_callcenter AS center2 ON user.operatorGroup = center2.id ";
                                                          $ceQ = " and center2.main_call_center_id = '$callcenter' ";    } else { $ceQ = ""; }
        if(isset($callgroup) && !empty($callgroup))   { //$joinLine   .= " LEFT JOIN phone_order_users AS user3 ON phn.operator = user3.id ";
                                                        $gQ = " and user.operatorGroup = '$callgroup' ";    } else { $gQ = ""; }
        if(isset($brakeBy) && !empty($brakeBy))  { $brakeQ = $brakeBy;  } else { $brakeQ = "Date(phn.date)"; }


        $Query = " 1 ";    //default
        $Query .= $dfQ;    //date from
        $Query .= $dtQ;    //date to
        $Query .= $sQ;     //state
        $Query .= $pQ;     //product
        $Query .= $uQ;     //user
        $Query .= $tQ;     //type
        $Query .= $daQ;    //date
        $Query .= $caQ;    //campaign
        $Query .= $osQ;    //campaign
        $Query .= $ceQ;    //Call center
        $Query .= $gQ;     //group

        $conn = $this->get('database_connection');

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
        if(isset($callgroup) && !empty($callgroup)) {

            $_priceData = $conn->fetchAssoc("SELECT INperCall, INperOrder, periods.id as periodId FROM phone_order_callCenterPrice
                                             LEFT JOIN periods ON phone_order_callCenterPrice.period = periods.id
                                             WHERE phone_order_callCenterPrice.callCenterId = '{$callgroup}' AND periods.month = '{$selectM}' AND periods.year = '{$selectY}' LIMIT 1");

            $costpercall    = $_priceData['INperCall'];;
            $costperorder   = $_priceData['INperOrder'];;
            $period         = $_priceData['periodId'];;

        } else {
            $_priceData = $conn->fetchAssoc("SELECT periods.id as periodId FROM periods
                                             WHERE  periods.month = '{$selectM}' AND periods.year = '{$selectY}' LIMIT 1");
            $period     = $_priceData['periodId'];
        }



        $_data = $conn->fetchAll("SELECT phn.date as datum, count(*) as revenueNo, {$brakeBy} AS identif, {$showField}
                                        SUM(IFNULL(tmp.order_value, 0) + IFNULL(tmp.order_value_vat,0)) AS `revenue`,
                                        SUM(IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0)) AS `suminvoices`,
                                        SUM(IF(tmp.order_status = 103, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumpayments`,
                                        SUM(IF(tmp.order_status = 104, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumreturn`,
                                        SUM(IF(tmp.order_status = 105, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumrefund`,
                                        SUM(IF(tmp.order_status = 103,IFNULL(tmp.document_value,0),0)) 
                                            - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0)) 
                                            - SUM(IF(tmp.document_id IS NOT NULL ,IFNULL(tmp.shipping_cost,0),0)) 
                                            AS `t_gross_profit`,
                                        SUM(IF(tmp.order_status NOT IN (103,104,105), IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `unknown`,
                                        SUM(IF( phn.success = 'ORDERED!', 1, 0)) as orderedNum,
                                        SUM(IF( phn.success = 'CANCELED!', 1, 0)) as canceledNum,
                                        SEC_TO_TIME(SUM(TIME_TO_SEC(phn.duration))) AS total_duration,
                                        SUM(IF((tmp.no_of_items_count_in_order > 1 AND tmp.o_postage = 0) OR tmp.no_of_items_count_in_order > 2, 1, 0)) AS total_upsell,
                                        SUM(IF(phn.success = 'NO ORDER!', 1, 0)) AS total_other,
                                        SUM(IF((tmp.no_of_items_count_in_order > 1 AND tmp.o_postage = 0) OR tmp.no_of_items_count_in_order > 2, IFNULL(tmp.order_value, 0) + IFNULL(tmp.order_value_vat,0), 0)) AS `total_upsell_value`,
                                        SEC_TO_TIME(SUM(IF( phn.success = 'ORDERED!', TIME_TO_SEC(phn.duration),0))) AS total_order_duration,
                                        SUM(costs.INperCall) as callCosts
                                FROM `phone_order_calls` AS `phn`
                                LEFT JOIN orders ON (phn.orderSubmitId = orders.submitId AND phn.orderSubmitId != 0)
                                LEFT JOIN analytics_sales_upsell_order as tmp ON (orders.order_id = tmp.order_id AND orders.order_id != 0)
                                LEFT JOIN phone_order_users AS user ON phn.operator = user.id
                                LEFT JOIN phone_order_callCenterPrice AS costs ON (user.operatorGroup = costs.callCenterId AND costs.period = '{$period}')
                                {$joinLine}
                                WHERE {$Query}
                                GROUP BY {$brakeQ} ORDER BY phn.id DESC");

        //- SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.document_value_vat,0),0))

        $html    ='<div class="tableHolder" style="padding: 10px 10px 10px 10px;width: 1500px;">
                    <div class="dayTable" style="width: 1500px;">
                    <table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr>
                        <td width="20px">Brake key</td>
                        <td >Order list link</td>
                        <td ># Total Calls</td>
                        <td ># Order</td>
                        <td >% Order</td>
                        <td ># Cancel</td>
                        <td >% Cancel</td>
                        <td >Revenue</td>
                        <td >Invoices</td>
                        <td >Payments</td>
                        <td >G.Profit</td>
                        <td >Returns</td>
                        <td >Refunds</td>
                        <td >Unknown</td>
                        <td >Duration</td>
                        <td >% Revenue Pay.</td>
                        <td >% Invoices Pay.</td>
                        <td >% Returns</td>
                        <td >% No status</td></thead><tbody>';

        $showColor  = "greenLine";
        $tRevNo     = "";
        $tReven     = "";
        $tInvoice   = "";
        $tPayments  = "";
        $tvaluevat  = "";
        $tsumreturn = "";
        $tsumrefund = "";
        $tunknown   = "";
        $tprodcost  = "";
        $tsrcost    = "";
        $tgross     = "";
        $callCosts  = 0;

        $hGprofit   = 0;
        $hOrder     = 0;
        $hAnswered  = 0;
        $hOrderP    = 0;
        foreach ($_data AS $oHigh) {
            if ($oHigh['t_gross_profit'] > $hGprofit){
                $hGprofit = $oHigh['t_gross_profit'];
            }
            if ($oHigh['orderedNum'] > $hOrder){
                $hOrder = $oHigh['orderedNum'];
            }
            if (($oHigh['orderedNum']/$oHigh['revenueNo']*100) > $hOrderP){
                $hOrderP = ($oHigh['orderedNum']/$oHigh['revenueNo']*100);
            }
        }

        $tOrderNum          = "";
        $tupsell            = "";
        $tother             = "";
        $tupsellval         = "";
        $allCallDuration    = "";
        $allOrderDuration   = "";
        $tCancelNum         = "";

        foreach ($_data AS $oRow) {
            if ($oRow[$brakeField] == ""){
                $oRow[$brakeField] = "Unknown";
            }

            $hGclass = $_main->matchCellColor($hGprofit,$oRow['t_gross_profit']);
            $hOclass = $_main->matchCellColor($hOrder,$oRow['orderedNum']);
            $hOPclass = $_main->matchCellColor($hOrderP,round($oRow['orderedNum']/$oRow['revenueNo']*100, 2));

            $brakeValue     = $oRow[$brakeField];

            if ($brakeField == 'datum'){
                $brakeValue = $oRow['identif'];
            }

            $html    .= '<tr style="margin-top:1px; cursor:pointer;">
                            <td class=""><a onclick="getRowTableData(\''.$brakeBy.'\', \''.$rowField.'\', \''.$oRow['identif'].'\');">'.$brakeValue.'</a></td>
                            <td class=""><a onclick="redirectToInboundList(\'../statsin/viewData?state='.$state.'&product='.$product.'&user='.$operator.'&ordSource='.$type.'&campaign='.$campaign.'&from='.$from.'&to='.$to.'&'.$qField.'='.$oRow['identif'].'\')">link</a></td>
                            <td class="">'.$oRow['revenueNo'].'</td>
                            <td '.$hOclass.'>'.$oRow['orderedNum'].'</td>
                            <td '.$hOPclass.'>'.round($oRow['orderedNum']/$oRow['revenueNo']*100, 2).' %</td>
                            <td class="">'.$oRow['canceledNum'].'</td>
                            <td class="">'.round($oRow['canceledNum']/$oRow['revenueNo']*100, 2).' %</td>
                            <td class="">'.round($oRow['revenue'], 2).' €</td>
                            <td class="">'.round($oRow['suminvoices'], 2).' €</td>
                            <td class="">'.round($oRow['sumpayments'], 2).' €</td>
                            <td '.$hGclass.'>'.round($oRow['t_gross_profit']-$oRow['callCosts'], 2).' €</td>
                            <td class="">'.round($oRow['sumreturn'], 2).' €</td>
                            <td class="">'.round($oRow['sumrefund'], 2).' €</td>
                            <td class="">'.round($oRow['unknown'], 2).' €</td>
                            <td class="">'.$oRow['total_duration'].'</td>
                            <td class="">'.round($oRow['sumpayments']/$oRow['revenue']*100, 2).' %</td>
                            <td class="">'.round($oRow['sumpayments']/$oRow['suminvoices']*100, 2).' %</td>
                            <td class="">'.round($oRow['sumreturn']/$oRow['suminvoices']*100, 2).' %</td>
                            <td class="">'.round(($oRow['suminvoices']-$oRow['sumpayments'])/$oRow['suminvoices']*100, 2).' %</td>
                         </tr>';

            $tRevNo     = $tRevNo + $oRow['revenueNo'];
            $tReven     = $tReven + $oRow['revenue'];
            $tOrderNum  = $tOrderNum + $oRow['orderedNum'];
            $tCancelNum = $tCancelNum + $oRow['canceledNum'];
            $tInvoice   = $tInvoice + $oRow['suminvoices'];
            $tPayments  = $tPayments + $oRow['sumpayments'];
            $tgross     = $tgross + $oRow['t_gross_profit'] - $oRow['callCosts'];
            $tsumreturn = $tsumreturn + $oRow['sumreturn'];
            $tsumrefund = $tsumrefund + $oRow['sumrefund'];
            $tunknown   = $tunknown + $oRow['unknown'];
            $tupsell    = $tupsell + $oRow['total_upsell'];
            $tother     = $tother + $oRow['total_other'];
            $tupsellval = $tupsellval + $oRow['total_upsell_value'];
            $callCosts  = $callCosts + $oRow['callCosts'];

            //$date = new \DateTime();
            $eTime    = explode(":",$oRow['total_duration']);
            $oTime    = explode(":",$oRow['total_order_duration']);

            $thisDuration       = ($eTime[0]*3600) + ($eTime[1]*60) + $eTime[2];
            $thisOrderDuration  = ($oTime[0]*3600) + ($oTime[1]*60) + $oTime[2];

            //$thisDuration = $date->TimeToSec($row['total_duration']);
            $allCallDuration    = $allCallDuration + $thisDuration;
            $allOrderDuration   = $allOrderDuration + $thisOrderDuration;


        }
        $tpOrders   = $tOrderNum / $tRevNo * 100;
        $tpCancel   = $tCancelNum / $tRevNo * 100;
        $tprReven   = $tPayments / $tReven * 100;
        $tpInvoice  = $tPayments / $tInvoice * 100;
        $tpReturns  = $tsumreturn / $tInvoice * 100;
        $tpNostatus = 100 - $tpInvoice - $tpReturns;

        $hours          = floor($allCallDuration / 3600);
        $mins           = floor($allCallDuration / 60 % 60);
        $secs           = floor($allCallDuration % 60);
        $totalDuration  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

        $avgDuration       = round($allCallDuration/$tRevNo,2);
        $avghours          = floor($avgDuration / 3600);
        $avgmins           = floor($avgDuration / 60 % 60);
        $avgsecs           = floor($avgDuration % 60);
        $avgtotalDuration  = sprintf('%02d:%02d:%02d', $avghours, $avgmins, $avgsecs);

        $avgOrderDuration       = round($allOrderDuration/$tOrderNum,2);
        $avgOrderhours          = floor($avgOrderDuration / 3600);
        $avgOrdermins           = floor($avgOrderDuration / 60 % 60);
        $avgOrdersecs           = floor($avgOrderDuration % 60);
        $avgtotalOrderDuration  = sprintf('%02d:%02d:%02d', $avgOrderhours, $avgOrdermins, $avgOrdersecs);

        $html   .= '</tbody>';
        $html   .= '<tfoot><tr style="margin-top:1px; cursor:pointer;font-weight: bold;text-align: center;">';
        $html   .= '<td>Totals:</td>
                    <td></td>
                    <td>'.round($tRevNo,2).'</td>
                    <td>'.round($tOrderNum,2).'</td>
                    <td>'.round($tpOrders,2).' %</td>
                    <td>'.round($tCancelNum,2).'</td>
                    <td>'.round($tpCancel,2).' %</td>
                    <td>'.round($tReven,2).' €</td>
                    <td>'.round($tInvoice,2).' €</td>
                    <td>'.round($tPayments,2).' €</td>
                    <td>'.round($tgross,2).' €</td>
                    <td>'.round($tsumreturn,2).' €</td>
                    <td>'.round($tsumrefund,2).' €</td>
                    <td>'.round($tunknown,2).' €</td>
                    <td>'.$totalDuration.'</td>
                    <td>'.round($tprReven,2).' %</td>
                    <td>'.round($tpInvoice,2).' %</td>
                    <td>'.round($tpReturns,2).' %</td>
                    <td>'.round($tpNostatus,2).' %</td>';
        $html   .= '</tr></tfoot>';
        $html   .= '</table></div></div>';

        $html2  = '<div class="tableHolder" style="padding: 10px 10px 10px 10px;width: 1500px;">
                    <table class="statsData" style="width: 100%;font-size: 14px;border-spacing: 4px;">
                        <tbody>
                        <tr style="height:30px;!important">
                            <th style="text-align:left;padding: 0 10px;"></th>
                            <th colspan="2" style="text-align:center;">Volumes</th>
                            <th colspan="2" style="text-align:center;">Percentages</th>
                            <th colspan="2" style="text-align:center;">Sum values</th>
                            <th colspan="2" style="text-align:center;">Average values</th>
                            <th colspan="2" style="text-align:center;">Call center costs</th>
                            <th colspan="2" style="text-align:center;">Durations</th>
                        </tr>
                        <tr>
                            <th rowspan="2" style="text-align:left;padding: 0 10px;">Total Calls</th>
                            <td rowspan="2" style="text-align:left;">Calls:</td>
                            <td rowspan="2" style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="Total number of calls">'.$tRevNo.'</a></strong></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td style="text-align:left;">SUM gross profit:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Invoice value)- SUM(VAT)- SUM(shipping)- SUM(return)- SUM(refund)- SUM(product_price)- SUM(Call_costs)">'.round($tgross,2).'</a></strong></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td style="text-align:left;">Total monthly costs:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Total_calls) * cost_per_call">'.$callCosts.'</a> €</strong></td>
                            <td style="text-align:left;">Sum call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="Total call duration">'.$totalDuration.'</a></strong></td>
            
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:left;"></td>
                            <td style="text-align:left;">SUM payments:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(paid_invoices_value)">'.round($tPayments,2).'</a></strong></td>
                            <td style="text-align:left;">Average call value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(order_value) / COUNT(Calls)">'.round($tReven/$tRevNo,2).'</a></strong></td>
                            <td style="text-align:left;">Cost per call:</td>
                            <td style="background: #fff;text-align:right;"><strong>'.round($costpercall,2).' €</strong></td>
                            <td style="text-align:left;">Avg call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Total_call_duration) / COUNT(Calls)">'.$avgtotalDuration.'</a></strong></td>
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Orders</th>
                            <td style="text-align:left;">Orders:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Orders)">'.round($tOrderNum,2).'</a></strong></td>
            
                            <td style="text-align:left;">Order:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Orders) / COUNT(Calls) *100">'.round($tpOrders,2).'</a> %</strong></td>
            
                            <td style="text-align:left;">Sum order value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Order_value)">'.round($tReven,2).'</a> €</strong></td>
            
                            <td style="text-align:left;">Avg order value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Order_value) / COUNT(Orders)">'.round($tReven/$tOrderNum,2).'</a> €</strong></td>
            
                            <td style="text-align:left;">Cost per order:</td>
                            <td style="background: #fff;text-align:right;"><strong>'.round($costperorder,2).' €</strong></td>
            
                            <td style="text-align:left;">Avg order call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Order_call_duration) / COUNT(Orders)">'.$avgtotalOrderDuration.'</a></strong></td>
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Upsells</th>
                            <td style="text-align:left;">Upsells:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Upsells)">'.$tupsell.'</a></strong></td>
            
                            <td style="text-align:left;">Upsell:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Upsells) / COUNT(Orders) *100">'.round($tupsell/$tOrderNum*100,2).'</a> %</strong></td>
            
                            <td style="text-align:left;">Sum upsell value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Upsell_order_value)">'.round($tupsellval,2).'</a> €</strong></td>
            
                            <td style="text-align:left;">Avg upsell value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Upsell_order_value) / COUNT(Upsells)">'.round($tupsellval/$tupsell,2).'</a> €</strong></td>
            
                            <td colspan="4">
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Other</th>
                            <td style="text-align:left;">Other:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Other)">'.$tother.'</a></strong></td>
                            <td style="text-align:left;">Other:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Other) / COUNT(Calls) *100">'.round($tother/$tRevNo*100,2).'</a> %</strong></td>
                            <td style="text-align:left;">SUM refunds:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Refund_value)">'.round($tsumrefund,2).'</a></strong></td>
                            <td colspan="6">
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Cancel</th>
                            <td style="text-align:left;">Cancel:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Canceled)">'.$tCancelNum.'</a></strong></td>
                            <td style="text-align:left;">Canceled:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Canceled) / COUNT(Orders) *100">'.round($tCancelNum/$tOrderNum*100,2).'</a> %</strong></td>
                            <td style="text-align:left;">SUM returns:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Return_value)">'.round($tsumreturn,2).'</a></strong></td>
                            <td colspan="6">
                        </tr>
                        </tbody>
                    </table></div>';


        return new Response(json_encode($html2.$html));
    }

    /**
     * @Template(engine="php")
     */

    public function outboundOrdersAction()
    {

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $_main      = new Main();

        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $operator   = $queryArr['operator'];
        $callgroup  = $queryArr['callgroup'];
        $callcenter = $queryArr['callcenter'];
        $type       = $queryArr['type'];
        $status     = $queryArr['status'];
        $substatus  = $queryArr['substatus'];
        $datum      = $queryArr['datum'];
        $ostatus    = $queryArr['order_status'];
        $ftype      = $queryArr['flow_type'];

        $brakeBy    = $queryArr['brake'];

        $brakeField = $brakeBy;
        $joinLine   = "";
        $rowField   = "";
        $qField     = "";
        if ($brakeField == "phn.productID"){
            $brakeField = "product";
            $joinLine   = "LEFT JOIN products ON phn.productID = products.id";
            $showField  = "products.title AS product,";
            $rowField   = "product";
            $qField     = "product";
        } else if ($brakeField == "phn.state"){
            $brakeField = "state";
            $joinLine   = " LEFT JOIN states ON phn.state = states.code2 ";
            $showField  = "states.code2 AS state,";
            $rowField   = "state";
            $qField     = "state";
        } else if ($brakeField == "phn.type"){
            $brakeField = "type";
            $joinLine   = "LEFT JOIN phone_order_outbound_types ON phn.type = phone_order_outbound_types.id";
            $showField  = "phone_order_outbound_types.title AS type,";
            $rowField   = "type";
            $qField     = "ordType";
        } else if ($brakeField == "phn.submitDate" || $brakeField == "Date%28phn.submitDate%29"){
            $brakeField = "datum";
            $brakeBy = "Date(phn.submitDate)";
            $rowField   = "datum";
            $qField     = "from";
        } else if ($brakeField == "phn.operator"){
            $brakeField = "operator";
            $joinLine   = "";
            $showField  = "user.fullname AS operator,";
            $rowField   = "operator";
            $qField     = "user";
        } else if ($brakeField == "sub.status_id"){
            $brakeField = "status";
            $joinLine   = "LEFT JOIN phone_order_outbound_call_substatus AS sub ON phn.status = sub.id
                           LEFT JOIN phone_order_outbound_call_status ON sub.status_id = phone_order_outbound_call_status.id";
            $showField  = "phone_order_outbound_call_status.title AS status,";
            $rowField   = "status";
            $qField     = "ordStatus";
        } else if ($brakeField == "phn.status"){
            $brakeField = "substatus";
            $joinLine   = "LEFT JOIN phone_order_outbound_call_substatus ON phn.status = phone_order_outbound_call_substatus.id";
            $showField  = "phone_order_outbound_call_substatus.title AS substatus,";
            $rowField   = "substatus";
            $qField     = "subStatus";
        } else if ($brakeField == "center.main_call_center_id"){
            $brakeField = "callcenter";
            $joinLine   = "LEFT JOIN phone_order_callcenter center ON user.operatorGroup = center.id
                           LEFT JOIN phone_order_maincallcenter ON center.main_call_center_id = phone_order_maincallcenter.id";
            $showField  = "phone_order_maincallcenter.name AS callcenter,";
            $rowField   = "callcenter";
            $qField     = "callcenter";
        } else if ($brakeField == "tmp.order_status"){
            $brakeField = "order_status";
            $joinLine   = "LEFT JOIN analytics_sales_order_status ON tmp.order_status = analytics_sales_order_status.id";
            $showField  = "analytics_sales_order_status.status_name AS order_status,";
            $rowField   = "order_status";
            $qField     = "ostatus";
        } else if ($brakeField == "phn.splitType"){
            $brakeField = "flow_type";
            $joinLine   = "LEFT JOIN phone_order_split_types ON phn.splitType = phone_order_split_types.id";
            $showField  = "phone_order_split_types.title AS flow_type,";
            $rowField   = "flow_type";
            $qField     = "flow_type";
        }

        if(isset($from) && !empty($from)) { $dfQ = "  and Date(phn.submitDate) >= '$from' "; } else { $dfQ = "  and Date(phn.submitDate) >= '$startDate' "; }
        if(isset($to) && !empty($to))     { $dtQ = " and Date(phn.submitDate) <= '$to' ";    } else { $dtQ = " and Date(phn.submitDate) <= '$todayDate' "; }
        if(isset($state) && !empty($state))   { $sQ = " and phn.state = '$state' ";    } else { $sQ = ""; }
        if(isset($product) && !empty($product))   { $pQ = " and phn.productID = '$product' ";    } else { $pQ = ""; }
        if(isset($type) && !empty($type))   { $tQ = " and phn.type = '$type' ";    } else { $tQ = ""; }
        if(isset($operator) && !empty($operator))   { $uQ = " and phn.operator = '$operator' ";    } else { $uQ = ""; }
        if(isset($status) && !empty($status))   {
                                                  $joinLine   .= " LEFT JOIN phone_order_outbound_call_substatus AS sub2 ON phn.status = sub2.id ";
                                                  $csQ = " and sub2.status_id = '$status' ";
                                                    if ($status == 4){
                                                        $csQ = " and (sub.status_id = '$status' OR phn.status = 0) ";
                                                    }
                                                } else { $csQ = ""; }
        if(isset($substatus) && !empty($substatus))   { $ssQ = " and phn.status = '$substatus' ";    } else { $ssQ = ""; }
        if(isset($datum) && !empty($datum))   { $daQ = "  and Date(phn.submitDate) = '$datum' ";   } else { $daQ = ""; }
        if(isset($callcenter) && !empty($callcenter))   { $joinLine   .= " LEFT JOIN phone_order_callcenter center2 ON user.operatorGroup = center2.id ";
                                                            $ceQ = " and center2.main_call_center_id = '$callcenter' ";    } else { $ceQ = ""; }
        if(isset($callgroup) && !empty($callgroup))   {     $gQ = " and user.operatorGroup = '$callgroup' ";    } else { $gQ = ""; }
        if(isset($ostatus) && !empty($ostatus))     { $osQ = " and tmp.order_status = '$ostatus' ";    } else { $osQ = ""; }
        if(isset($ftype) && !empty($ftype))     { $ftQ = " and phn.splitType = '$ftype' ";    } else { $ftQ = ""; }
        if(isset($brakeBy) && !empty($brakeBy))     { $brakeQ = $brakeBy;  } else { $brakeQ = "Date(phn.submitDate)"; }


        $Query = " 1 ";   //default
        $Query .= $dfQ;   //date from
        $Query .= $dtQ;   //date to
        $Query .= $sQ;    //state
        $Query .= $pQ;    //product
        $Query .= $uQ;    //user
        $Query .= $tQ;    //type
        $Query .= $csQ;    //call status
        $Query .= $ssQ;    //call substatus
        $Query .= $daQ;    //call substatus
        $Query .= $ceQ;    //main center
        $Query .= $gQ;     //center group
        $Query .= $osQ;    //order status
        $Query .= $ftQ;    //flow type


        $conn = $this->get('database_connection');

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
        if((isset($callgroup) && !empty($callgroup)) || (isset($state) && !empty($state))) {


            if(isset($callgroup) && !empty($callgroup)) {

                $_priceData = $conn->fetchAssoc("SELECT OUTperCall, OUTperOrder, periods.id as periodId 
                                                 FROM phone_order_callCenterPrice
                                                 LEFT JOIN periods ON phone_order_callCenterPrice.period = periods.id
                                                 WHERE phone_order_callCenterPrice.callCenterId = '{$callgroup}' 
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

        $_data = $conn->fetchAll("SELECT phn.submitDate as datum, count(*) as revenueNo, {$brakeBy} AS identif, {$showField}
                                        SUM(IFNULL(tmp.order_value, 0) + IFNULL(tmp.order_value_vat,0)) AS `revenue`,
                                        SUM(IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0)) AS `suminvoices`,
                                        SUM(IF(tmp.order_status = 103, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumpayments`,
                                        SUM(IF(tmp.order_status = 104, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumreturn`,
                                        SUM(IF(tmp.order_status = 105, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumrefund`,
                                        SUM(IF(tmp.order_status =  103,IFNULL(tmp.document_value,0),0)) 
                                            - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0)) 
                                            - SUM(IF(tmp.document_id IS NOT NULL ,IFNULL(tmp.shipping_cost,0),0)) 
                                            AS `t_gross_profit`,
                                        SUM(IF(tmp.order_status NOT IN (103,104,105), IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `unknown`,
                                        SUM(IF( phn.status = 7, 1, 0)) as orderedNum,
                                        SUM(IF( phn.status = 6, 1, 0)) as canceledNum,
                                        SUM(IF( phn.status = 6 OR phn.status = 7 OR phn.status = 9 OR phn.status = 12, 1, 0)) as answeredNum,
                                        SUM(IF( phn.status != 6 AND phn.status != 7 AND phn.status != 9 AND phn.status != 12 AND phn.status != 0 AND phn.status != 13, 1, 0)) as otherNum,
                                        SUM(IF( phn.status = 0 OR phn.status = 13, 1, 0)) as notCalledNum,
                                        SUM(IF((tmp.no_of_items_count_in_order > 1 AND tmp.o_postage = 0) OR tmp.no_of_items_count_in_order > 2, 1, 0)) AS total_upsell,
                                        SUM(IF((tmp.no_of_items_count_in_order > 1 AND tmp.o_postage = 0) OR tmp.no_of_items_count_in_order > 2, IFNULL(tmp.order_value, 0) + IFNULL(tmp.order_value_vat,0), 0)) AS `total_upsell_value`,
                                        SUM(TIMESTAMPDIFF(SECOND, phn.called_time, phn.callEnd)) AS total_duration,
                                        SUM(IF( phn.status = 7, TIMESTAMPDIFF(SECOND, phn.called_time, phn.callEnd),0)) AS order_duration,
                                        SUM(IF( phn.status = 6 OR phn.status = 7 OR phn.status = 9 OR phn.status = 12, costs.OUTperCall, 0)) as callCosts
                                FROM `phone_order_outbound` AS `phn`
                                LEFT JOIN orders ON (phn.submitID = orders.submitId AND phn.submitID != 0)
                                LEFT JOIN analytics_sales_upsell_order as tmp ON (orders.order_id = tmp.order_id AND orders.order_id != 0)
                                LEFT JOIN phone_order_users AS user ON phn.operator = user.id
                                LEFT JOIN phone_order_callCenterPrice AS costs ON (user.operatorGroup = costs.callCenterId AND costs.period = '{$period}')
                                {$joinLine}
                                WHERE {$Query}
                                GROUP BY {$brakeQ} ORDER BY phn.id DESC");

        // - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.document_value_vat,0),0))

        $html    ='<div class="tableHolder" style="padding: 10px 10px 10px 10px;width: 1500px;">
                    <div class="dayTable" style="width: 1500px;">
                    <table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr>
                        <td width="20px">Brake key</td>
                        <td >Order list link</td>
                        <td ># Total Calls</td>
                        <td ># Answered</td>
                        <td ># Orders</td>
                        <td >% Orders</td>
                        <td ># Cancel</td>
                        <td >% Cancel</td>
                        <td >Revenue</td>
                        <td >Invoices</td>
                        <td >Payments</td>
                        <td >G.Profit</td>
                        <td >Returns</td>
                        <td >Refunds</td>
                        <td >Unknown</td>
                        <td >Duration</td>
                        <td >Revenue Pay. %</td>
                        <td >Invoices Pay. %</td>
                        <td >Returns %</td>
                        <td >No status %</td></thead><tbody>';

        $showColor  = 'greenLine';
        $tRevNo     = "";
        $tReven     = "";
        $tInvoice   = "";
        $tPayments  = "";
        $tvaluevat  = "";
        $tsumreturn = "";
        $tsumrefund = "";
        $tunknown   = "";
        $tprodcost  = "";
        $tsrcost    = "";
        $tgross     = "";
        $tother     = "";

        $hGprofit   = 0;
        $hOrder     = 0;
        $hAnswered  = 0;
        $hOrderP    = 0;
        $callCosts  = 0;
        foreach ($_data AS $oHigh) {
            if ($oHigh['t_gross_profit'] > $hGprofit){
                $hGprofit = $oHigh['t_gross_profit'];
            }
            if ($oHigh['orderedNum'] > $hOrder){
                $hOrder = $oHigh['orderedNum'];
            }
            if ($oHigh['answeredNum'] > $hAnswered){
                $hAnswered = $oHigh['answeredNum'];
            }
            if (($oHigh['orderedNum']/$oHigh['answeredNum']*100) > $hOrderP){
                $hOrderP = ($oHigh['orderedNum']/$oHigh['answeredNum']*100);
            }
        }

        $tupsell        = "";
        $tupsellval     = "";
        $tother         = "";
        $tDuration      = "";
        $tOrdDuration   = "";
        $tNotCalled     = "";
        $tAnswerNum     = "";
        $tCancelNum     = "";
        $tOrderNum      = "";

        foreach ($_data AS $oRow) {
            if ($oRow[$brakeField] == ""){
                $oRow[$brakeField] = "Unknown";
            }

            $hGclass = $_main->matchCellColor($hGprofit,$oRow['t_gross_profit']);
            $hOclass = $_main->matchCellColor($hOrder,$oRow['orderedNum']);
            $hAclass = $_main->matchCellColor($hAnswered,$oRow['answeredNum']);
            $hOPclass = $_main->matchCellColor($hOrderP,round($oRow['orderedNum']/$oRow['answeredNum']*100, 2));

            $hours          = floor($oRow['total_duration'] / 3600);
            $mins           = floor($oRow['total_duration'] / 60 % 60);
            $secs           = floor($oRow['total_duration'] % 60);
            $oRowDuration   = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);

            $brakeValue     = $oRow[$brakeField];

            if ($brakeField == 'datum'){
                $brakeValue = $oRow['identif'];
            }

            $html    .= '<tr style="margin-top:1px; cursor:pointer;">
                            <td class=""><a onclick="getRowTableData(\''.$brakeBy.'\', \''.$rowField.'\', \''.$oRow['identif'].'\');">'.$brakeValue.'</a></td>
                            <td class=""><a onclick="redirectToOutboundList(\'../statsout/viewData?state='.$state.'&product='.$product.'&user='.$operator.'&group='.$callgroup.'&ordType='.$type.'&ordStatus='.$status.'&subStatus='.$substatus.'&from='.$from.'&to='.$to.'&'.$qField.'='.$oRow['identif'].'\')">link</a></td>
                            <td class="">'.$oRow['revenueNo'].'</td>
                            <td '.$hAclass.'>'.$oRow['answeredNum'].' ('.round($oRow["callCosts"], 2).'€)</td>
                            <td '.$hOclass.'>'.$oRow['orderedNum'].'</td>
                            <td '.$hOPclass.'>'.round($oRow['orderedNum']/$oRow['answeredNum']*100, 2).' %</td>
                            <td class="">'.$oRow['canceledNum'].'</td>
                            <td class="">'.round($oRow['canceledNum']/$oRow['answeredNum']*100, 2).' %</td>
                            <td class="">'.round($oRow['revenue'], 2).' €</td>
                            <td class="">'.round($oRow['suminvoices'], 2).' €</td>
                            <td class="">'.round($oRow['sumpayments'], 2).' €</td>
                            <td '.$hGclass.'>'.round($oRow['t_gross_profit']-$oRow['callCosts'], 2).' €</td>
                            <td class="">'.round($oRow['sumreturn'], 2).' €</td>
                            <td class="">'.round($oRow['sumrefund'], 2).' €</td>
                            <td class="">'.round($oRow['unknown'], 2).' €</td>
                            <td class="">'.$oRowDuration.'</td>
                            <td class="">'.round($oRow['sumpayments']/$oRow['revenue']*100, 2).' %</td>
                            <td class="">'.round($oRow['sumpayments']/$oRow['suminvoices']*100, 2).' %</td>
                            <td class="">'.round($oRow['sumreturn']/$oRow['suminvoices']*100, 2).' %</td>
                            <td class="">'.round(($oRow['suminvoices']-$oRow['sumpayments'])/$oRow['suminvoices']*100, 2).' %</td>
                         </tr>';

            $tRevNo     = $tRevNo + $oRow['revenueNo'];
            $tReven     = $tReven + $oRow['revenue'];
            $tAnswerNum = $tAnswerNum + $oRow['answeredNum'];
            $tOrderNum  = $tOrderNum + $oRow['orderedNum'];
            $tCancelNum = $tCancelNum + $oRow['canceledNum'];
            $tInvoice   = $tInvoice + $oRow['suminvoices'];
            $tPayments  = $tPayments + $oRow['sumpayments'];
            $tgross     = $tgross + $oRow['t_gross_profit'] - $oRow['callCosts'];
            $tsumreturn = $tsumreturn + $oRow['sumreturn'];
            $tsumrefund = $tsumrefund + $oRow['sumrefund'];
            $tunknown   = $tunknown + $oRow['unknown'];
            $tupsell    = $tupsell + $oRow['total_upsell'];
            $tupsellval = $tupsellval + $oRow['total_upsell_value'];
            $tother     = $tother + $oRow['otherNum'];
            $tDuration  = $tDuration + $oRow['total_duration'];
            $tOrdDuration  = $tOrdDuration + $oRow['order_duration'];
            $tNotCalled = $tNotCalled + $oRow['notCalledNum'];
            $callCosts  = $callCosts + $oRow['callCosts'];



        }
        $tCalls     = $tAnswerNum + $tother;


        $tpOrders   = $tOrderNum / $tAnswerNum * 100;
        $tpCancel   = $tCancelNum / $tAnswerNum * 100;
        $tprReven   = $tPayments / $tReven * 100;
        $tpInvoice  = $tPayments / $tInvoice * 100;
        $tpReturns  = $tsumreturn / $tInvoice * 100;
        $tpNostatus = 100 - $tpInvoice - $tpReturns;
        $tpNotCalled= $tNotCalled / $tRevNo * 100;
        $tpCalls    = $tCalls / $tRevNo * 100;

        $avgCallDuration   = round($tDuration/$tAnswerNum);
        $avgOrderDuration  = round($tOrdDuration/$tOrderNum);

        $hours            = floor($tDuration / 3600);
        $mins             = floor($tDuration / 60 % 60);
        $secs             = floor($tDuration % 60);
        $totalDuration    = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);


        $chours             = floor($avgCallDuration / 3600);
        $cmins              = floor($avgCallDuration / 60 % 60);
        $csecs              = floor($avgCallDuration % 60);
        $totalAvgCallDuration  = sprintf('%02d:%02d:%02d', $chours, $cmins, $csecs);

        $ohours            = floor($avgOrderDuration / 3600);
        $omins             = floor($avgOrderDuration / 60 % 60);
        $osecs             = floor($avgOrderDuration % 60);
        $totalAvgOrderDuration    = sprintf('%02d:%02d:%02d', $ohours, $omins, $osecs);

        $html   .= '</tbody>';
        $html   .= '<tfoot><tr style="margin-top:1px; cursor:pointer;font-weight: bold;text-align: center;">';
        $html   .= '<td>Totals:</td>
                    <td></td>
                    <td>'.round($tRevNo,2).'</td>
                    <td>'.round($tAnswerNum,2).'</td>
                    <td>'.round($tOrderNum,2).'</td>
                    <td>'.round($tpOrders,2).' %</td>
                    <td>'.round($tCancelNum,2).'</td>
                    <td>'.round($tpCancel,2).' %</td>
                    <td>'.round($tReven,2).' €</td>
                    <td>'.round($tInvoice,2).' €</td>
                    <td>'.round($tPayments,2).' €</td>
                    <td>'.round($tgross,2).' €</td>
                    <td>'.round($tsumreturn,2).' €</td>
                    <td>'.round($tsumrefund,2).' €</td>
                    <td>'.round($tunknown,2).' €</td>
                    <td>'.$totalDuration.'</td>
                    <td>'.round($tprReven,2).' %</td>
                    <td>'.round($tpInvoice,2).' %</td>
                    <td>'.round($tpReturns,2).' %</td>
                    <td>'.round($tpNostatus,2).' %</td>';
        $html   .= '</tr></tfoot>';
        $html   .= '</table></div></div>';


        $html2  = '<div class="tableHolder" style="padding: 10px 10px 10px 10px;width: 1500px;">
                    <table class="statsData" style="width: 100%;font-size: 14px;border-spacing: 4px;">
                        <tbody>
                        <tr style="height:30px;!important">
                            <th style="text-align:left;padding: 0 10px;"></th>
                            <th colspan="2" style="text-align:center;">Volumes</th>
                            <th colspan="2" style="text-align:center;">Percentages</th>
                            <th colspan="2" style="text-align:center;">Sum values</th>
                            <th colspan="2" style="text-align:center;">Average values</th>
                            <th colspan="2" style="text-align:center;">Call center costs</th>
                            <th colspan="2" style="text-align:center;">Durations</th>
                        </tr>
                        <tr>
                            <th rowspan="3" style="text-align:left;padding: 0 10px;">Total Calls</th>
                            <td style="text-align:left;">Requests:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="Total requests">'.$tRevNo.'</a></strong></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td colspan="2" style="text-align:left;"></td>
                        </tr>
                        <tr>
                            <td style="text-align:left;">Calls:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="Total number of calls">'.$tCalls.'</a></strong></td>
                            <td style="text-align:left;">Calls:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Calls)/SUM(Requests)*100">'.round($tpCalls,2).' %</a></strong></td>
                            <td style="text-align:left;">SUM gross profit:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Invoice value)- SUM(VAT)- SUM(shipping)- SUM(return)- SUM(refund)- SUM(product_price)- SUM(call_costs)">'.round($tgross,2).'</a></strong></td>
                            <td colspan="2" style="text-align:left;"></td>
                            <td style="text-align:left;">Total monthly costs:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(answered) * cost_per_call">'.$callCosts.'</a> €</strong></td>
                            <td style="text-align:left;">Sum call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="Total Call Duration">'.$totalDuration.'</a></strong></td>
            
                        </tr>
                        <tr>
                            <td style="text-align:left;">Answered:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Answered calls)">'.$tAnswerNum.'</a></strong></td>
                            <td style="text-align:left;">Answered</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Answered_calls) / COUNT(Requests) * 100">'.round($tAnswerNum/$tCalls*100,2).'</a> %</strong></td>
                            <td style="text-align:left;">SUM payments:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Paid_invoices_value)">'.round($tPayments,2).'</a></strong></td>
                            <td style="text-align:left;">Average call value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(order_value) / COUNT(Answered_calls)">'.round($tReven/$tAnswerNum,2).'</a> €</strong></td>
                            <td style="text-align:left;">Cost per call:</td>
                            <td style="background: #fff;text-align:right;"><strong>'.round($costpercall,2).' €</strong></td>
                            <td style="text-align:left;">Avg call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Total_call_duration)/COUNT(Answered calls)">'.$totalAvgCallDuration.'</a></strong></td>
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Orders</th>
                            <td style="text-align:left;">Orders:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Orders)">'.$tOrderNum.'</a></strong></td>
            
                            <td style="text-align:left;">Order:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Orders) / COUNT(Answered_calls) *100">'.round($tOrderNum/$tAnswerNum*100,2).'</a> %</strong></td>
            
                            <td style="text-align:left;">Sum order value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(order_value)">'.round($tReven,2).'</a> €</strong></td>
            
                            <td style="text-align:left;">Avg order value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(order_value) / COUNT(Orders)">'.round($tReven/$tOrderNum,2).'</a> €</strong></td>
            
                            <td style="text-align:left;">Cost per order:</td>
                            <td style="background: #fff;text-align:right;"><strong>'.round($costperorder,2).' €</strong></td>
            
                            <td style="text-align:left;">Avg order call duration:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Order_duration) / COUNT(Total_Calls)">'.$totalAvgOrderDuration.'</a></strong></td>
                        </tr>
                        <tr>
                            <th style="text-align:left;padding: 0 10px;">Upsells</th>
                            <td style="text-align:left;">Upsells:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Upsells)">'.$tupsell.'</a></strong></td>
            
                            <td style="text-align:left;">Upsell:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Upsells) / COUNT(Orders) * 100">'.round($tupsell/$tOrderNum*100,2).'</a> %</strong></td>
            
                            <td style="text-align:left;">Sum upsell value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(upsell_value)">'.round($tReven-$tupsellval,2).'</a> €</strong></td>
            
                            <td style="text-align:left;">Avg upsell value:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(upsell_value)/COUNT(Upsells)">'.round(($tReven-$tupsellval)/$tupsell,2).' €</strong></td>
            
                            <td colspan="4">
                        </tr>
                        <tr>
            
                            <th style="text-align:left;padding: 0 10px;">Other</th>
                            <td style="text-align:left;"> Other:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Other_calls)">'.$tother.'</a></strong></td>
            
                            <td style="text-align:left;">Other:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Other_calls) / COUNT(Total_calls) * 100">'.round($tother/$tRevNo*100,2).'</a> %</strong></td>
                            <td style="text-align:left;">SUM refunds:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Refund_value)">'.round($tsumrefund,2).'</a></strong></td>
                            <td colspan="6">
                        </tr>
                        <tr>
            
                            <th style="text-align:left;padding: 0 10px;">Cancel</th>
                            <td style="text-align:left;padding: 0 10px;"> Canceled:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Cancel_calls)">'.$tCancelNum.'</a></strong></td>
            
                            <td style="text-align:left;">Canceled:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Cancel_calls) / COUNT(Answered_calls) * 100">'.round($tpCancel,2).'</a> %</strong></td>
                            <td style="text-align:left;">SUM returns:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="SUM(Return_value)">'.round($tsumreturn,2).'</a></strong></td>
                            <td colspan="6">
                        </tr>
                        <tr>
            
                            <th style="text-align:left;padding: 0 10px;">Not called</th>
                            <td style="text-align:left;padding: 0 10px;"> Not called:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Pending + Request gone)">'.$tNotCalled.'</a></strong></td>
                            <td style="text-align:left;">Not called:</td>
                            <td style="background: #fff;text-align:right;"><strong><a href="#" class="popElement" data-trigger="focus" data-toggle="popover" data-content="COUNT(Not_Called) / COUNT(Total_calls) * 100">'.round($tpNotCalled,2).'</a> %</strong></td>
                            <td colspan="8">
                        </tr>
                        </tbody>
                    </table></div>';


        return new Response(json_encode($html2.$html));
    }
}
