<?php

namespace AjaxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\OMG;
use AppBundle\Entity\SMS;

class SmsCampAjaxController extends Controller
{

    public function index(){

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $conn = $this->get('database_connection');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $_main     = new Main();

        $from     = $queryArr['from'];
        $to       = $queryArr['to'];

        $cType    = $queryArr['cType'];
        $state    = $queryArr['state'];
        $product  = $queryArr['product'];
//        $campaign = $queryArr['campaign'];

        $brakeField  = $queryArr['brake'];

        $brakeBy = "";
        $joinLine = "";
        $showField = "";
        $rowField   = "";
        $ggggg = "";
        $link ='';
        if ($brakeField == "phn.product"){
            $link       = $brakeField;
            $brakeField = "product";
            $brakeBy    = "phone_order_sms_campaigns_analytics.product_id";
            $joinLine   = "";
            // $showField  = "phone_order_sms_campaigns_analytics.product";
            $rowField   = "product";
        } else if ($brakeField == "phn.state"){
            $link       = $brakeField;
            $brakeField = "state";
            $brakeBy    = "phone_order_sms_campaigns_analytics.state_id";
            $joinLine   = "";
            //  $showField  = "phone_order_sms_campaigns_analytics.state";
            $rowField   = "state";
        } else if ($brakeField == "phn.cType"){
            $link       = $brakeField;
            $brakeField = "cType";
            $brakeBy    = "phone_order_sms_campaigns_analytics.state_id";
            $joinLine   = "";
            $showField  = "phone_order_sms_campaigns_analytics.splitType";
            $rowField   = "splitType";
        } else if ($brakeField == "phn.date" || $brakeField == "Date%28phn.date%29"){
            $link       = $brakeField;
            $brakeField = "date";
            $brakeBy    = "phone_order_sms_campaigns_analytics.date_made";
            $joinLine   = "";
            //  $showField  = "phone_order_sms_campaigns_analytics.date_made";
            $rowField   = "datum";

        } else if ($brakeField == "phn.campaignId"){
            $link       = $brakeField;
            $brakeField = "campaignId";
            $brakeBy    = "phone_order_sms_campaigns_analytics.campaign_id";
            $joinLine   = "";
            //   $showField  = "phn.campaignId AS campaign,";
            $rowField   = "campaign";
            $ggggg = 1;
        }
        //Campaign type filter
        if(isset($cType) && !empty($cType) || $cType === "0")  { $ctQ = " and CampManagement.splitType = '$cType' ";               } else { $cType = ""; $ctQ = ""; }
        if(isset($state) && !empty($state))            { $sQ = " and phone_order_sms_campaigns_analytics.state_id = '$state' ";    } else{ $state = "";          $sQ = "";     }
        if(isset($product) && !empty($product))        { $pQ = " and phone_order_sms_campaigns_analytics.product_id = '$product' ";} else { $product = "";       $pQ = "";  }
//        if(isset($camp) && !empty($camp))             { $cQ = " and CampManagement.id = '$camp' ";         } else { $camp = "";           $cQ = "";   }
        if(isset($from) && !empty($from))              { $dfQ = " and phone_order_sms_campaigns_analytics.date_made >= '$from' ";  } else { $from = $startDate;   $dfQ = " and phone_order_sms_campaigns_analytics.date_made >= '$from' "; }
        if(isset($to) && !empty($to))                  { $dtQ = " and phone_order_sms_campaigns_analytics.date_made <= '$to' ";    } else { $to = $todayDate;     $dtQ = " and phone_order_sms_campaigns_analytics.date_made <= '$to' "; }
        if(isset($brakeField) && !empty($brakeField))  { $brakeQ = $brakeBy;  } else { $brakeQ = "phone_order_sms_campaigns_analytics.campaign_id"; $ggggg = 1;}

        $Query = " 1 ";   //default
        $Query .= $ctQ;   //cType
        $Query .= $sQ;    //state
        $Query .= $pQ;    //product
        $Query .= $dfQ;   //date from
        $Query .= $dtQ;   //date to



        $_data = $conn->fetchAll("SELECT  phone_order_sms_campaigns_analytics.*, {$brakeBy} AS identif, 
                                  COUNT (*) as count_all
                                  FROM  `phone_order_sms_campaigns_analytics`
                                  {$joinLine}
                                  WHERE 1 {$Query}
                                  GROUP  BY {$brakeQ}");
        $html1 = '';
        $html2 = '';
        $html3 = '';
        if ($ggggg == 1 ){
            $html1 =   '<td>State</td>
                        <td>Product</td>
                        <td>Date sent</td>
                        <td>Message</td>
                        <td>Squeeze</td>
                        <td>Status</td>';

            $html2 =   '<td>Price</td>
                        <td>Upsell</td>';

            $html3 =   '<td>Campaign status</td>
                        <td>Type</td>';
        }

        $html = '<table class="dayView compact" id="example">
                <thead>
                <tr>
                    <td width="20px">#</td>
                    <td width="20px">Brake key</td>'.$html1;

        $html .=   '<td>Orders</td>
                    <td>No orders</td>
                    <td>Canceled orders</td>
                    <td>Total cals </td>
                    <td>Order visited</td>
                    <td>Opened count </td>
                    <td>Unopened count </td>
                    <td>Prod. sent</td>
                    <td>Recipients</td>
                    <td>Sent</td>
                    <td>Delivered</td>
                    <td>Undelivered</td>'.$html2;

        $html .=   '<td>Return</td>
                    <td>Refund</td>
                    <td>Gros profit</td>
                    <td>Delivery ratio</td>
                    <td>Total cost (€)</td>
                    <td>avgIncome (€)</td>
                    <td>ROI avgIncome(€)</td>
                    <td>Conversion rate</td>'.$html3;
        $html .=   '<td></td>
                </tr>
                </thead>
                <tbody id="tabela">';



        $counter = 0;
        //SVI TOTALI
        $tOrders = 0;
        $tNoOrders = 0;
        $tCanceled = 0;
        $tTotalCalls = 0;
        $tOrdVisited = 0;
        $tOpened = 0;
        $tUnopened= 0;
        $tPrSent = 0;
        $tRecipients = 0;
        $tSent = 0;
        $tDelivered = 0;
        $tUndelivered = 0;
        $tReturn = 0;
        $tRefund = 0;
        $tGrosProfit = 0;
        $tDeliveryRatio = 0;
        $tTotalCost = 0;
        $tAvgIncome = 0;
        $tROI = 0;
        $tcRate = 0;

        foreach ($_data as $dat){
            $counter++;

            // **** Sabiranje svih totala
            $tOrders        = $tOrders + (int)$dat["order_count"];
            $tNoOrders      = $tNoOrders + (int)$dat["noOrder_count"];
            $tCanceled      = $tCanceled + (int)$dat['cancel_count'];
            $tTotalCalls    = $tTotalCalls + (int)$dat['total_calls'];
            $tOrdVisited    = $tOrdVisited + (int)$dat['order_visited_count'];
            $tOpened        = $tOpened + (int)$dat['opened_count'];
            $tUnopened      = $tUnopened + (int)$dat['unopened_count'];
            $tPrSent        = $tPrSent + (int)$dat["product_sent_count"];
            $tRecipients    = $tRecipients + (int)$dat["RecipientNo"];
            $tSent          = $tSent + $dat['sent_count'];
            $tDelivered     = $tDelivered + $dat['delivered_count'];
            $tUndelivered   = $tUndelivered + $dat['undelivered_count'];
            $tReturn        = $tReturn + $dat['sumReturn'];
            $tRefund        = $tRefund + $dat['sumRefund'];
            $tGrosProfit    = $tGrosProfit + $dat['t_gros_profit'];
            $tDeliveryRatio = $tDeliveryRatio + $dat['delivery_ratio'];
            $tTotalCost     = $tTotalCost + $dat['total_cost'];
            $tAvgIncome     = $tAvgIncome + $dat['avgIncome'];
            $tROI           = $tROI + (int)$dat['ROI_avgIncome'];
            $tcRate         = $tcRate + (int)$dat['conversion_rate'];

            $statusMessage ='';
            $statusSquize ='';

            if($dat['message_id'] == 0){
                $statusMessage = 'none';
            }
            if($dat['message_squize'] != 0){
                $statusSquize = 'none';
            }


            $html .= '<tr id="r'.$counter.'">';
            $html .= '<td>'.$counter.'</td>
                         <td class=""><a onclick="getRowTableData(\''.$link.'\', \''.$rowField.'\', \''.$oRow['identif'].'\');">'.$dat[$brakeField].'</a></td>';
            if ($ggggg == 1 ){
                $html .= '<td>'.$dat['state'].'</td>
                          <td>'.$dat['product'].'</td>
                          <td>'.$dat['date_made'].'</td>
                          <td><a href="messages?selectedmessage='.$dat['message_id'].'#'.$dat['message_id'].'" target="_blank">M-'.$dat['message_id'].'</a></td>
                          <td><a href="messages?selectedmessage='.$dat['message_squize'].'#'.$dat['message_squize'].'" target="_blank">M-'.$dat['message_squize'].'</a></td>
                           <td>'.$dat['status'].'</td>';
            }
            $html     .= '<td>'.$dat['order_count'].'</td>
                          <td>'.$dat['noOrder_count'].'</td>
                          <td>'.$dat['cancel_count'].'</td>
                          <td>'.$dat['total_calls'].'</td>
                          <td>'.$dat['order_visited_count'].'</td>
                          <td>'.$dat['opened_count'].'</td>
                          <td>'.$dat['unopened_count'].'</td>
                          <td>'.$dat['product_sent_count'].'</td>
                          <td>'.$dat['RecipientNo'].'</td>
                          <td>'.$dat['sent_count'].'</td>
                          <td>'.$dat['delivered_count'].'</td>
                          <td>'.$dat['undelivered_count'].'</td>';

            if ($ggggg == 1 ){
                $html .= '<td>'.round($dat['price'], 2).'€</td>
                          <td>'.round($dat['upsell'], 2).'€</td>';
            }

            $html     .= '<td>'.round($dat['sumReturn'],2).'€</td>
                          <td>'.round($dat['sumRefund'],2).'€</td>
                          <td>'.round($dat['t_gros_profit'],2).'€</td>
                          <td>'.round($dat['delivery_ratio']).'%</td>
                          <td>'.round($dat['total_cost'], 3).'€</td>
                          <td>'.round($dat['avgIncome'], 2).'€</td>
                          <td>'.round($dat['ROI_avgIncome'], 2).'€</td>
                          <td>'.round($dat['conversion_rate'],3).'%</td>';

            if ($ggggg == 1 ){
                $html .= '<td>'.$dat['campaign_status'].'</td>
                          <td>'.$dat['splitType'].'</td>';
            }


        }
        $tPerDeliveryRatio = ($tDeliveryRatio/$counter)*100;
        $tAvgggggIncome = $tAvgIncome/$counter;
        $tROIncome = $tROI/$counter;
        $cRatePerc = ($tcRate/$counter)*100;

        $html   .= '</tbody>';
        $html   .= '<tfoot><tr style="margin-top:1px; cursor:pointer;font-weight: bold;text-align: center;">';
        $html   .= '<td>Totals:</td>
                    <td></td>
                    <td>'.$tOrders.'</td>
                    <td>'.$tNoOrders.'</td>
                    <td>'.$tCanceled.'</td>
                    <td>'.$tTotalCalls.'</td>
                    <td>'.$tOrdVisited.'</td>
                    <td>'.$tOpened.'</td>
                    <td>'.$tUnopened.'</td>
                    <td>'.$tPrSent.'</td>
                    <td>'.$tRecipients.'</td>
                    <td>'.$tSent.'</td>
                    <td>'.$tDelivered.'</td>
                    <td>'.$tUndelivered.'</td>
                    <td>'.round($tReturn,2).' €</td>
                    <td>'.round($tRefund,2).' €</td>
                    <td>'.round($tGrosProfit,2).' €</td>
                    <td>'.round($tPerDeliveryRatio).'%</td>
                    <td>'.round($tTotalCost,3).' €</td>
                    <td>'.round($tAvgggggIncome,2).' €</td>
                    <td>'.round($tROIncome,2).' €</td>
                    <td>'.round($cRatePerc,3).' %</td>';
        $html   .= '</tr></tfoot>';
        $html   .= '</table></div></div>';



        return new Response(json_encode($html));

    }
}