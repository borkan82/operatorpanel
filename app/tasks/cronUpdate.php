<?php
/**
 * Created by PhpStorm.
 * User: PC11
 * Date: 1/17/2017
 * Time: 2:00 PM
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);
include 'config/config.php';
include 'class/classCron.php';

$_cron   = new Cron($db);

$niz = array();



$campaignsNameGroupBy = $_cron->getCampaignListNames();
//print_r($campaignsNameGroupBy);
//die();

//$campaigns = $_cron->getCampaignList(" and CampManagement.Datemade > NOW() - INTERVAL 120 DAY ");
$campaigns = $_cron->getCampaignList();




$campaignsUpdate =  array();

foreach ($campaigns as $camk){
    $niz[$camk['campaign']]['price'] = $camk['price'];
    $niz[$camk['campaign']]['upsell'] = $camk['upsell'];
    $niz[$camk['campaign']]['campaign_id'] = $camk['campaign_id'];

    $campaignsUpdate[$camk['campaign']]['campaign_id'] = $camk['campaign_id'];
    $campaignsUpdate[$camk['campaign']]['campaign'] = $camk['campaign'];
    $campaignsUpdate[$camk['campaign']]['state_id'] = $camk['state_id'];
    $campaignsUpdate[$camk['campaign']]['state'] = $camk['state'];
    $campaignsUpdate[$camk['campaign']]['product_id'] = $camk['product_id'];
    $campaignsUpdate[$camk['campaign']]['product'] = $camk['product'];
    $campaignsUpdate[$camk['campaign']]['date_sent'] = $camk['date_sent'];
    $campaignsUpdate[$camk['campaign']]['date_made'] = $camk['date_made'];
    $campaignsUpdate[$camk['campaign']]['status'] = $camk['status'];
    $campaignsUpdate[$camk['campaign']]['RecipientNo'] = $camk['RecipientNo'];
    $campaignsUpdate[$camk['campaign']]['price'] = $camk['price'];
    $campaignsUpdate[$camk['campaign']]['upsell'] = $camk['upsell'];
    $campaignsUpdate[$camk['campaign']]['campaign_status'] = $camk['campaign_status'];
    $campaignsUpdate[$camk['campaign']]['splitType'] = $camk['splitType'];

}


$columns = 'date_sent,status,price,upsell,campaign_status';
$napuni= $_cron->insertOrUpdateNew($campaignsUpdate, 'phone_order_sms_campaigns_analytics',$columns,1 );



//$campaignsOrders = $_cron->getOrdersForUpdate(" and CampManagement.Datemade > NOW() - INTERVAL 120 DAY ");
$campaignsOrders = $_cron->getOrdersForUpdate();






//ovo nece biti potrebno (foreach) u produkciji jer necemo koristiti PDO
$ordersUpdate =  array();
foreach($campaignsOrders AS $OrdRow){
    $ordersUpdate[$OrdRow['campaign']]['campaign_id'] = $OrdRow['campaign_id'];
    $ordersUpdate[$OrdRow['campaign']]['campaign'] = $OrdRow['campaign'];
    $ordersUpdate[$OrdRow['campaign']]['total_calls'] = $OrdRow['total_calls'];
    $ordersUpdate[$OrdRow['campaign']]['order_count'] = $OrdRow['order_count'];
    $ordersUpdate[$OrdRow['campaign']]['cancel_count'] = $OrdRow['cancel_count'];
    $ordersUpdate[$OrdRow['campaign']]['noOrder_count'] = $OrdRow['noOrder_count'];
    $ordersUpdate[$OrdRow['campaign']]['sumReturn'] = $OrdRow['sumReturn'];
    $ordersUpdate[$OrdRow['campaign']]['sumRefund'] = $OrdRow['sumRefund'];
    $ordersUpdate[$OrdRow['campaign']]['t_gross_profit'] = $OrdRow['t_gross_profit'];

}

//$campaignsOrdersCamp = $_cron->getOrdersCampUpd(" and CampManagement.Datemade > NOW() - INTERVAL 120 DAY ");
$campaignsOrdersCamp = $_cron->getOrdersCampUpd();

foreach ($campaignsOrdersCamp as $ordRowCamp){
    //if(in_array($ordRowCamp['campaign'],$campaignsNameGroupBy)){
        if(array_key_exists($ordRowCamp['campaign'],$campaignsNameGroupBy)){
        print_r('aa');
        $ordersUpdate[$ordRowCamp['campaign']]['sumReturn']      = $ordersUpdate[$ordRowCamp['campaign']]['sumReturn']+ $ordRowCamp['sumReturnCamp'];
        $ordersUpdate[$ordRowCamp['campaign']]['sumRefund']      = $ordersUpdate[$ordRowCamp['campaign']]['sumRefund']+ $ordRowCamp['sumRefundCamp'];
        $ordersUpdate[$ordRowCamp['campaign']]['t_gross_profit'] = $ordersUpdate[$ordRowCamp['campaign']]['t_gross_profit']+ $ordRowCamp['t_gross_profitCamp'];
    }

}
//print_r($campaignsOrdersCamp);die();


//print_r($ordersUpdate);die();
$columns = 'campaign_id, campaign';
$napuni= $_cron->insertOrUpdateNew($ordersUpdate, 'phone_order_sms_campaigns_analytics',$columns,2 );
//die();




//$sMess            = $_cron-> countBulkMessages(" and DateSent > NOW() - INTERVAL 135  DAY  ");  // za ove ide array_key_exists
//$dMess            = $_cron-> countBulkMessages(' and DateSent > NOW() - INTERVAL 135  DAY AND status = 2 ');  // za ove ide array_key_exists

$sMess            = $_cron-> countBulkMessages();  // za ove ide array_key_exists
$dMess            = $_cron-> countBulkMessages(' AND status = 2 ');  // za ove ide array_key_exists

//print_r($dMess);die();
foreach ($sMess as $sent) {

    if (array_key_exists($sent['messageId'],$niz)){
        $niz[$sent['messageId']]['sent_count'] = $sent['broj'];
    }
}

foreach ($dMess as $deliv) {
    if (array_key_exists($deliv['messageId'],$niz)) {
        $niz[$deliv['messageId']]['delivered_count'] = $deliv['broj'];
        $niz[$deliv['messageId']]['sumDelivMess'] = $deliv['smsCount'];
    }
}

//print_r($niz);die();

//$_orders  = $_cron->getBulkOrders('and orders.orderdate > NOW() - INTERVAL 135 DAY');  // za ove  ne treba array key exist jer ga tek dole ukljucujemo
$_orders  = $_cron->getBulkOrders();  // za ove  ne treba array key exist jer ga tek dole ukljucujemo

$incomeQuantity= array();
foreach ($_orders as $_orderItem){
    $kampanja = $_orderItem['utm_campaign'];
    $incomeAdd = $niz[$kampanja]['price'];

    $kolicinaNum    = $_orderItem['quantity'];

    $incomeQuantity[$kampanja]['Quantity'] = $incomeQuantity[$kampanja]['Quantity'] + $kolicinaNum;
    if ($kolicinaNum > 1){

        $incomeAdd = $niz[$kampanja]['price'] + ($niz[$kampanja]['upsell'] * ($kolicinaNum-1));
    }
    $incomeQuantity[$kampanja]['income'] = $incomeQuantity[$kampanja]['income'] + $incomeAdd;
}
//exception za stare kampanje
$incomeQuantity["sms004ba"]['income'] = $incomeQuantity["smsBA004"]['income'] + $incomeQuantity["basms001"]['income'];
$incomeQuantity["sms007rs"]['income'] = $incomeQuantity["rssms001"]['income'];
$incomeQuantity["sms003mk"]['income'] = $incomeQuantity["mksms001"]['income'];


$_smsprices       = $_cron->getSMSprices();
$smsPrice   = Array();
$exchval    = Array();
foreach ($_smsprices as $smspr){
    $smsPrice[$smspr['state']]  = $smspr['price'];
    $exchval[$smspr['state']]   = $smspr['exchval'];
}

//$visitedBulkLinks = $_cron->getBulkShortLinkList(" and CampManagement.Datemade > NOW() - INTERVAL 120 DAY ");
$visitedBulkLinks = $_cron->getBulkShortLinkList();

$visitedOrders    = $_cron->getCampaignOrders();

$displayVisited = array();
foreach ($visitedOrders as $vOrd){
    $displayVisited[$vOrd['utm_campaign']] = $vOrd['orderCount'];
}

$_messages        = $_cron->getTranslationID();
$messageArr = Array('initial','squeeze');
$messageArr['initial'] = Array();
foreach ($_messages AS $poruka){
    if($poruka['pozicija'] == 1){

        $messageArr['initial'][$poruka['id']] = $poruka['messageID'];
    } else if ($poruka['pozicija'] == 2) {
        $messageArr['squeeze'][$poruka['id']] = $poruka['messageID'];
    }
}


foreach ($visitedBulkLinks as $visBulLi){
    $niz[$visBulLi['CampaignName']]['opened_count']        = $visBulLi['opened'];
    $niz[$visBulLi['CampaignName']]['unopened_count']      = $visBulLi['unopened'];
    $niz[$visBulLi['CampaignName']]['order_visited_count'] = $displayVisited[$visBulLi['CampaignName']];
}

//var_dump($niz);die();

foreach ($campaigns as $nizCamp){
   // print_r($niz[$nizCamp['campaign']]['order_visited_count']);
    $totalOrderCount = $ordersUpdate[$nizCamp['campaign']]["order_count"] + $niz[$nizCamp['campaign']]['order_visited_count'];
    $niz[$nizCamp['campaign']]['total_order_count'] = $totalOrderCount;

    $niz[$nizCamp['campaign']]['undelivered_count']  = $niz[$nizCamp['campaign']]['sent_count'] - $niz[$nizCamp['campaign']]['delivered_count'];
    $niz[$nizCamp['campaign']]['total_cost']         = $smsPrice[$nizCamp["state"]] * (int)$niz[$nizCamp['campaign']]['sumDelivMess'];
    $niz[$nizCamp["campaign"]]['product_sent_count'] = $incomeQuantity[$nizCamp["campaign"]]['Quantity'];


    if(isset($incomeQuantity[$nizCamp["campaign"]]['income']) && !empty($incomeQuantity[$nizCamp["campaign"]]['income'])){
        $niz[$nizCamp["campaign"]]['avgIncome'] = round($incomeQuantity[$nizCamp["campaign"]]['income'] / $exchval[$nizCamp["state"]], 2);

    } else {
        $niz[$nizCamp["campaign"]]['avgIncome'] = 0;
    }

    $niz[$nizCamp['campaign']]['ROI_avgIncome']    =  $niz[$nizCamp['campaign']]['avgIncome'] -  $niz[$nizCamp['campaign']]['total_cost'];

    $niz[$nizCamp['campaign']]['conversion_rate']  = ($totalOrderCount/ $niz[$nizCamp['campaign']]["sumDelivMess"]);


    $niz[$nizCamp['campaign']]['delivery_ratio']  =  $niz[$nizCamp['campaign']]['delivered_count'] / $niz[$nizCamp['campaign']]['sent_count'];

   // print_r($niz[$nizCamp['campaign']]['conversion_rate'] );
    $selectedMessage = json_decode($nizCamp["selectedMessages"]);

    foreach ($selectedMessage as $_singleMessage => $value){
        $initialMess = 0;
        $squeezeMess = 0;

        if (isset($messageArr['initial'][$_singleMessage]) && !empty($messageArr['initial'][$_singleMessage])){
            $initialMess = $messageArr['initial'][$_singleMessage];
        }
        if (isset($messageArr['squeeze'][$_singleMessage]) && !empty($messageArr['squeeze'][$_singleMessage])){
            $squeezeMess = $messageArr['squeeze'][$_singleMessage];
        }

        $niz[$nizCamp['campaign']]['message_id'] = $initialMess;
        $niz[$nizCamp['campaign']]['message_squize'] = $squeezeMess;
    }



}

//print_r($niz);
//die();
$insertNizRows = array();
$coutnNiz = count($niz);
$count = array();

foreach ($niz as $key => $updRow) {
    // print_r($niz[$key]['upsell']);die();
    unset($updRow['price']);
    unset($updRow['upsell']);
    //unset($updRow['sumDelivMess']);

    unset($niz[$key]['price']);
    unset($niz[$key]['upsell']);
   // unset($niz[$key]['sumDelivMess']);


    if (!isset($updRow['message_id']) && empty($updRow['message_id'])){
        $updRow['message_id'] =  0;
        $niz[$key]['message_id'] = 0;
    }
    if (!isset($updRow['message_squize']) && empty($updRow['message_squize'])){
        $updRow['message_squize']=  0;
        $niz[$key]['message_squize'] = 0;
    }

    if (!isset($updRow['order_visited_count']) && empty($updRow['order_visited_count'])){
        $updRow['order_visited_count'] =  0;
        $niz[$key]['order_visited_count'] = 0;
    }
    if (!isset($updRow['product_sent_count']) && empty($updRow['product_sent_count'])){
        $updRow['product_sent_count']=  0;
        $niz[$key]['product_sent_count'] = 0;
    }
    if (!isset($updRow['sent_count']) && empty($updRow['sent_count'])){
        $updRow['sent_count']=  0;
        $niz[$key]['sent_count'] = 0;

    }
    if (!isset($updRow['delivered_count']) && empty($updRow['delivered_count'])){
        $updRow['delivered_count'] =  0;
        $niz[$key]['delivered_count'] = 0;
    }
    if (!isset($updRow['undelivered_count']) && empty($updRow['undelivered_count'])){
        $updRow['undelivered_count']=  0;
        $niz[$key]['undelivered_count'] = 0;
    }
    if (!isset($updRow['opened_count']) && empty($updRow['opened_count'])){
        $updRow['opened_count']=  0;
        $niz[$key]['opened_count'] = 0;
    }
    if (!isset($updRow['unopened_count']) && empty($updRow['unopened_count'])){
        $updRow['unopened_count']=  0;
        $niz[$key]['unopened_count'] = 0;
    }
    if (!isset($updRow['delivery_ratio']) && empty($updRow['delivery_ratio'])){
        $updRow['delivery_ratio'] =  0;
        $niz[$key]['delivery_ratio'] = 0;
    }
    if (!isset($updRow['total_cost']) && empty($updRow['total_cost'])){
        $updRow['total_cost'] =  0;
        $niz[$key]['total_cost'] = 0;
    }
    if (!isset($updRow['avgIncome']) && empty($updRow['avgIncome'])){
        $updRow['avgIncome'] =  0;
        $niz[$key]['avgIncome'] = 0;
    }
    if (!isset($updRow['ROI_avgIncome']) && empty($updRow['ROI_avgIncome'])){
        $updRow['ROI_avgIncome']=  0;
        $niz[$key]['ROI_avgIncome'] = 0;
    }
    if (!isset($updRow['conversion_rate']) && empty($updRow['conversion_rate'])){
        $updRow['conversion_rate'] =  0;
        $niz[$key]['conversion_rate'] =  0;
    }
    if (!isset($updRow['sumDelivMess']) && empty($updRow['sumDelivMess'])){
        $updRow['sumDelivMess'] =  0;
        $niz[$key]['sumDelivMess'] =  0;
    }


    if (isset ($key) && !empty($key)){
        $d = ksort($updRow);
        $count[$updRow['campaign_id']] = count($updRow);
        $insertNizRows[$key]= $updRow;
    }

}
//print_r($insertNizRows);die();

$columns = "campaign_id";

$napuni  = $_cron->insertOrUpdateNew($insertNizRows, 'phone_order_sms_campaigns_analytics',$columns,2 );

