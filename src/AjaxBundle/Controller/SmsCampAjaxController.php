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

    public function indexAction(){

        $todayDate = Date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime("yesterday"));
        $startDate = Date('Y-m-01');

        $conn = $this->get('database_connection');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }



        $from     = $queryArr['from'];
        $to       = $queryArr['to'];

        $cType    = $queryArr['cType'];
        $campType = $queryArr['campType'];
        $state    = $queryArr['state'];
        $product  = $queryArr['product_id'];

        //dodatak, akcija na klik brake key

        $camp = $queryArr['campaign_id'];
        $cronDate    = $queryArr['cronDate'];

        $brakeField  = $queryArr['brake'];

        $brakeBy = "";
        $joinLine = "";
        $showField = "";
        $rowField   = "";
        $ggggg = "";
        $link ='';
        $orderBy = ' cronDate DESC';
        if ($brakeField == "phn.product"){
            $link       = $brakeField;
            $brakeField = "product";
            $brakeBy    = "phone_order_sms_campaigns_analytics.product_id";
            $joinLine   = "";
             $showField  = "product";
            $rowField   = "product_id";
        } else if ($brakeField == "phn.state"){
            $link       = $brakeField;
            $brakeField = "state";
            $brakeBy    = "phone_order_sms_campaigns_analytics.state_id";
            $joinLine   = "";
            $showField  = "state";
            $rowField   = "state";
            $orderBy = ' state ASC ';
        } else if ($brakeField == "phn.cType"){
            $link       = $brakeField;
            $brakeField = "cType";
            $brakeBy    = "phone_order_sms_campaigns_analytics.splitType";
            $joinLine   = "";
            $showField  = "splitType";
            $rowField   = "splitType";
        } else if ($brakeField == "phn.campType"){
            $link       = $brakeField;
            $brakeField = "campType";
            $brakeBy    = "phone_order_sms_campaigns_analytics.campaignType";
            $joinLine   = "";
            $showField  = "campaignType";
            $rowField   = "campaignType";
        }  else if ($brakeField == "phn.date" || $brakeField == "Date%28phn.date%29"){
            $link       = $brakeField;
            $brakeField = "date";
            $brakeBy    = "phone_order_sms_campaigns_analytics.cronDate";
            $joinLine   = "";
            $showField  = "cronDate";
            $rowField   = "cronDate";

        } else if ($brakeField == "phn.campaignId"){
            $link       = $brakeField;
            $brakeField = "campaignId";
            $brakeBy    = "phone_order_sms_campaigns_analytics.campaign";
            $joinLine   = "";
            $showField  = "campaign";
            $rowField   = "campaign_id";
            $ggggg = 1;
        }

        if ($from > $yesterday){
            $from = $yesterday;
        }
        if ($to > $yesterday){
            $to = $yesterday;
        }
     
        //Campaign type filter
        if(isset($cType) && !empty($cType) || $cType === "0")  { $ctQ = " and phone_order_sms_campaigns_analytics.splitType = '$cType' ";        } else { $cType = ""; $ctQ = ""; }
        if(isset($campType) && !empty($campType)|| $campType === "0")      { $campQ = " and phone_order_sms_campaigns_analytics.campaignType = '$campType' ";        } else{ $campType = ""; $campQ = "";     }
        if(isset($state) && !empty($state))            { $sQ = " and phone_order_sms_campaigns_analytics.state = '$state' ";        } else{ $state = "";          $sQ = "";     }
        if(isset($product) && !empty($product))        { $pQ = " and phone_order_sms_campaigns_analytics.product_id = '$product' "; } else { $product = "";       $pQ = "";  }

        if(isset($from) && !empty($from))              { $dfQ = " and phone_order_sms_campaigns_analytics.cronDate >= '$from' ";   } else { $from = $startDate;   $dfQ = " and phone_order_sms_campaigns_analytics.cronDate >= '$from' "; }
        if(isset($to) && !empty($to))                  { $dtQ = " and phone_order_sms_campaigns_analytics.cronDate <= '$to' ";     } else { $to = $yesterday;     $dtQ = " and phone_order_sms_campaigns_analytics.cronDate <= '$to' "; }
        if(isset($brakeField) && !empty($brakeField))  { $brakeQ = $brakeBy;  } else { $brakeQ = "phone_order_sms_campaigns_analytics.campaign_id"; $ggggg = 1;}
        if(isset($camp) && !empty($camp))              { $cQ = " and phone_order_sms_campaigns_analytics.campaign_id = '$camp' ";         } else { $camp = "";           $cQ = "";   }
        if(isset($cronDate) && !empty($cronDate))      { $dmQ = " and phone_order_sms_campaigns_analytics.cronDate = '$cronDate' ";   } else { $cronDate = "";         $dmQ = "";   }
      //  print_r($campQ);
        //Campaign type filter

        $Query = " 1 ";   //default
        $Query .= $ctQ;   //cType
        $Query .= $campQ;   //$campQ
        $Query .= $sQ;    //state
        $Query .= $pQ;    //product
        $Query .= $dfQ;   //date from
        $Query .= $dtQ;   //date to


        $Query .= $cQ;   //campaign
        $Query .= $dmQ;   //date made
       // var_dump($Query);

        $cTypes =  array(
            0=>'Single',
            1=>'Split'
        );
        $campTypes     = array(
            0 => 'Reorder',
            1 => 'Sms',
        );


        $sql1 = "SELECT {$brakeBy} AS identif, campaign, campaign_id, phone_order_sms_campaigns_analytics.product, phone_order_sms_campaigns_analytics.splitType,
                                  phone_order_sms_campaigns_analytics.state, cronDate, phone_order_sms_campaigns_analytics.product_id,
                                  phone_order_sms_campaigns_analytics.campaignType,CampManagement.Datesend, CampManagement.Datemade,
                                  SUM(order_count) as order_count,
                                  SUM(noOrder_count) as noOrder_count,
                                  SUM(cancel_count) as cancel_count,
                                  SUM(total_calls) as total_calls,
                                  SUM(order_visited_count) as order_visited_count,
                                  SUM(product_sent_count) as product_sent_count,
                                  SUM(sent_count) as sent_count,
                                  SUM(delivered_count) as delivered_count,
                                  SUM(undelivered_count) as undelivered_count,
                                  SUM(opened_count) as opened_count,
                                  SUM(unopened_count) as unopened_count,
                                  SUM(sumReturn) as sumReturn,
                                  SUM(sumRefund)as sumRefund,
                                  SUM(sumInvoices) as sumInvoices,
                                  SUM(sumPayments)as sumPayments,
                                  SUM(t_gross_profit) as t_gross_profit,
                                  SUM(total_cost) as total_cost,
                                  SUM(sumDelivMess) as sumDelivMess,
                                  SUM(if(phone_order_callCenterPrice.INperCall !=0 && phone_order_callCenterPrice.INperCall IS NOT NULL && YEAR(cronDate)=periods.year && MONTH(cronDate)= periods.month, 
                                  phone_order_sms_campaigns_analytics.total_calls*phone_order_callCenterPrice.INperCall,0 )) as percall,
                                  delivery_ratio
                                  FROM  `phone_order_sms_campaigns_analytics`
                                  {$joinLine}
                                  LEFT JOIN CampManagement ON CampManagement.id = phone_order_sms_campaigns_analytics.campaign_id
                                  LEFT JOIN phone_order_callcenter ON phone_order_callcenter.state = phone_order_sms_campaigns_analytics.state
                                  LEFT JOIN phone_order_callCenterPrice ON phone_order_callcenter.id = phone_order_callCenterPrice.callCenterId
                                  LEFT JOIN periods ON (phone_order_callCenterPrice.period = periods.id  and YEAR(phone_order_sms_campaigns_analytics.cronDate)= periods.year and MONTH(phone_order_sms_campaigns_analytics.cronDate)=periods.month)
                                  WHERE {$Query} and periods.year IS NOT NULL
                                  GROUP  BY {$brakeQ} ORDER BY {$orderBy}";
        //print_r($sql1);
        $_data = $conn->fetchAll($sql1);
       
//        
        $html = '<div class="tableHolder" style="padding: 10px 10px 10px 10px;width: 1700px;">
                    <div class="dayTable" style="width: 1700px;">
                    <table class="dayView compact" id="example">
                <thead style="cursor:pointer;">
                <tr>
                    <td width="20px">#</td>
                    <td width="20px">Brake key</td>';

        $html .=   '<td>CR%</td>
                    <td>Prod. sent</td>
                    <td>Orders-total</td>
                    <td>Orders-phone</td>
                    <td>Orders-link</td>
                    
                    <td>Phone / All orders</td>
                    <td>Orders / All calls</td>
                    <td>Orders / Link open</td>
                    
                  
                    <td>Delivery %</td>
                    
                 
                    <td>Cost-total</td>
                    <td>Cost-sms</td>
                    <td>Cost-call</td>
                    <td>Total calls</td>
                    
                    <td>Gros profit</td>
                    
                    <td>Net profit</td>
                    <td>Return</td>
                    <td>Invoices</td>
                   
                    ';

//                    <td>Payments</td>


        $html .=   '</tr>
                </thead>
                <tbody id="tabela">';



        $counter = 0;
        //SVI TOTALI
        $tOrdersPhone = 0;
        $tOrdersLink = 0;
        $tNoOrders = 0;
        $tTotalOrders = 0;
        $tCanceledOrders = 0;


        $tTotalCalls = 0;

        $tPrSent = 0;
//        $tRecipients = 0;


        $tLinkOpened = 0;
        $tLinkUnopened = 0;

        $tSent        = 0;
        $tDelivered   = 0;
        $tUndelivered = 0;

        $tTotalGross = 0;

        $tSumReturn     = 0;
        $tSumRefund     = 0;
        $tSumInvoices   = 0;
        $tSumPayments   = 0;
        
        $tSumDelivMess  = 0;
        $tTotalCost     = 0;
        





        $span=2;


        foreach ($_data as $dat){

            $counter++;
            //orders
            $ordersPhone = $dat['order_count'];
            $ordersLink = $dat['order_visited_count'];
            $noOrders       =  $dat['noOrder_count'];
            $canceledOrders = $dat['cancel_count'];
            $totalCalls     = $dat['total_calls'];

            $totalOrders = $ordersPhone + $ordersLink;


            $productSent = $dat['product_sent_count'];
//            $recipientsNo = $dat['RecipientNo'];


            $linkOpened = $dat['opened_count'];
            $linkUnopened = $dat['unopened_count'];

            $sent        = $dat['sent_count'];
            $delivered   = $dat['delivered_count'];
            $undelivered = $dat['undelivered_count'];

            $tGross = $dat['t_gross_profit'];

            $sumReturn     = $dat['sumReturn'];
            $sumRefund     = $dat['sumRefund'];
            $sumInvoices     = $dat['sumInvoices'];
            $sumPayments     = $dat['sumPayments'];
            
            $sumDelivMess  = $dat['sumDelivMess'];
            $smsCost       = $dat['total_cost'];
            $callCost      = $dat['percall'];

            $totalCost = $smsCost+$callCost;
            $netProfit = $tGross-$totalCost;

            //percents
            $cRate      = ($totalOrders/$sumDelivMess)*100;
            $phoneAllOrders = ($ordersPhone/$totalOrders )*100;
            $ordersAllCalls = ($ordersPhone/$totalCalls)*100;
            $ordersLinkOpened = ($ordersLink/$linkOpened*100);
            $delivRatio = ($delivered/$sent)*100;
//            $notSent   = (($recipientsNo - $sent)/$recipientsNo)*100;


            // **** Sabiranje svih totala
            $tOrdersPhone = $tOrdersPhone + $ordersPhone;
            $tOrdersLink = $tOrdersLink + $ordersLink;
            $tNoOrders = $tNoOrders + $noOrders;
            $tCanceledOrders = $tCanceledOrders + $canceledOrders;

            $tTotalOrders = $tTotalOrders + $totalOrders;

            $tTotalCalls = $tTotalCalls + $totalCalls;

            $tPrSent = $tPrSent + $productSent;
//            $tRecipients = $tRecipients + $recipientsNo;


            $tLinkOpened = $tLinkOpened + $linkOpened;
            $tLinkUnopened = $tLinkUnopened + $linkUnopened;

            $tSent        = $tSent + $sent;
            $tDelivered   = $tDelivered + $delivered;
            $tUndelivered = $tUndelivered + $undelivered;

            $tTotalGross = $tTotalGross + $tGross;

            $tSumReturn     = $tSumReturn + $sumReturn;
            $tSumRefund     = $tSumRefund + $tSumRefund;
            $tSumInvoices   = $tSumInvoices + $sumInvoices;
            $tSumPayments   = $tSumPayments + $sumPayments;
            $tSumDelivMess  = $tSumDelivMess + $sumDelivMess;

            $tSmsCost     = $tSmsCost + $smsCost;
            $tCallCost     = $tCallCost + $callCost;

            $tTotalCost = $tSmsCost + $tCallCost;
            $tNetProfit = $tTotalGross - $tTotalCost;


            //print_r($dat[$rowField]);

            $html .= '<tr style="margin-top:1px; cursor:pointer;">
                        <td>'.$counter.'</td>';
            
            if ($brakeField == 'cType'){
                $redirectToCallPanel = $this->generateUrl('campaigns', array('cType' =>$dat[$showField], 'state' => $state,'product' =>$product,'camp' =>$camp), true);
                $html .= '<td class=""><a href="'.$redirectToCallPanel.'" target="_blank">'.$cTypes[$dat[$showField]].'</a></td>';
//                $html .= '<td class=""><a onclick="getRowTableData(\''.$link.'\', \''.$rowField.'\', \''.$dat[$rowField].'\');">'.$cTypes[$dat[$showField]].'</a></td>';
               
            } else if($brakeField == 'campType'){
                if($dat[$showField] == 1){
                    $redirectToCallPanel = $this->generateUrl('campaigns', array('cType' =>$cType, 'state' => $state, 'product' =>$product, 'camp' =>$camp), true);
                    $html .= '<td class=""><a href="'.$redirectToCallPanel.'" target="_blank">'.$campTypes[$dat[$showField]].'</a></td>';
                } else {
                    $html .= '<td class=""><a onclick="getRowTableData(\''.$link.'\', \''.$rowField.'\', \''.$dat[$rowField].'\');">'.$campTypes[$dat[$showField]].'</a></td>';
                }
            }else if($brakeField == 'state'){
                $redirectToCallPanel = $this->generateUrl('campaigns', array('cType' =>$cType, 'state' => $dat[$showField],'product' =>$product,'camp' =>$camp), true);
                $html .= '<td class=""><a href="'.$redirectToCallPanel.'" target="_blank">'.$dat[$showField].'</a></td>';
            }else if($brakeField == 'product'){
                $redirectToCallPanel = $this->generateUrl('campaigns', array('cType' =>$cType, 'state' =>$state, 'product' =>$dat['product_id'],'camp' =>$camp), true);
                $html .= '<td class=""><a href="'.$redirectToCallPanel.'" target="_blank">'.$dat[$showField].'</a></td>';
            }else if($brakeField == 'campaignId'){
                $redirectToCallPanel = $this->generateUrl('campaigns', array('cType' =>$cType, 'state' =>$state, 'product' =>$product,'camp' =>$dat['campaign_id'], 'from'=>$dat['Datemade'],'to'=>$dat['Datemade'] ), true);
                $html .= '<td class=""><a href="'.$redirectToCallPanel.'" target="_blank">'.$dat[$showField].'</a></td>';
            }else if($brakeField == 'date'){
                $html .= '<td class=""><a onclick="getRowTableData(\''.$link.'\', \''.$brakeField.'\', \''.$dat[$rowField].'\');">'.$dat[$showField].'</a></td>';
            } else{
                $html .= '<td class=""><a onclick="getRowTableData(\''.$link.'\', \''.$rowField.'\', \''.$dat[$rowField].'\');">'.$dat[$showField].'</a></td>';
            }



            $html     .= '<td>'.round($cRate,2).'%</td>
                          <td>'.$productSent.'</td>
                          <td>'.$totalOrders.'</td>
                          <td>'.$ordersPhone.'</td>
                          <td>'.$ordersLink.'</td>
                          
                          <td>'.round($phoneAllOrders).'%</td>
                          <td>'.round($ordersAllCalls).'%</td>
                          <td>'.round($ordersLinkOpened).'%</td>
                          
                          
                          
                          <td>'.round($delivRatio).'%</td>
                          
                         
                          <td>'.round($totalCost,2).'€</td>
                          <td>'.round($smsCost,2).'€</td>
                          <td>'.round($callCost,2).'€</td>
                          <td>'.$totalCalls.'</td>
                          
                          <td>'.round($tGross,2).'€</td>
                          
                          <td>'.round($netProfit,2).'€</td>
                          <td>'.round($sumReturn,2).'€</td>
                          <td>'.round($sumInvoices,2).'€</td>
                          ';
                //
            //<td>'.round($sumPayments,2).'€</td>

            $html     .= ' </tr>';

        }



        $cRatePerc = ($tTotalOrders/$tSumDelivMess)*100;
        $tPhoneAllOrdersPerc = ($tOrdersPhone/$tTotalOrders)*100;
        $tOrdersAllCallsPerc = ($tOrdersPhone/$tTotalCalls)*100;
        $tOrdersLinkOpenedPerc= ($tOrdersLink/$tLinkOpened)*100;
        $tDelivRatioPerc = ($tDelivered/$tSent)*100;
       



        $html   .= '</tbody>';
        $html   .= '<tfoot><tr style="margin-top:1px; cursor:pointer;font-weight: bold;text-align: center;">';
        $html   .= '<td colspan="2">Totals:</td>
        
                    <td>'.round($cRatePerc,2).' %</td>
                    <td>'.$tPrSent.'</td>                   
                    <td>'.$tTotalOrders.'</td>
                    <td>'.$tOrdersPhone.'</td>
                    <td>'.$tOrdersLink.'</td>
                    
                    
                    <td>'.round($tPhoneAllOrdersPerc).'%</td>
                    <td>'.round($tOrdersAllCallsPerc).'%</td>
                    <td>'.round($tOrdersLinkOpenedPerc).'%</td>
                    
                    
                   
                    <td>'.round($tDelivRatioPerc).'%</td>
                    
                   
                    <td>'.round($tTotalCost,2).' €</td>
                    <td>'.round($tSmsCost,2).' €</td>
                    <td>'.round($tCallCost,2).' €</td>
                    <td>'.$tTotalCalls.'</td>
                    
                    <td>'.round($tTotalGross,2).' €</td>
                    
                    <td>'.round($tNetProfit,2).' €</td>
                    <td>'.round($tSumReturn,2).' €</td>
                    <td>'.round($tSumInvoices,2).' €</td>
                     <td></td>
                   
                    ';
//        <td>'.round($tSumInvoices,2).' €</td>
//                    <td>'.round($tSumPayments,2).' €</td>


        $html   .= '</tr></tfoot>';
        $html   .= '</table></div></div>';

        //var_dump($html);



        return new Response(json_encode($html));

    }
}