<?php
include 'config.php';
include '../class/classCron2.php';

function getDataSmsCampaign($date){
    print_r($date);
    die();
    $niz = array();
    $_cron   = new Cron2($db);
    $campaigns = $_cron->getCampaignList();

   
    foreach ($campaigns as $camk){
        $niz[$camk['campaign']]['campaign_id'] = $camk['campaign_id'];
        $niz[$camk['campaign']]['campaign'] = $camk['campaign'];
        $niz[$camk['campaign']]['state_id'] = $camk['state_id'];
        $niz[$camk['campaign']]['state'] = $camk['state'];
        $niz[$camk['campaign']]['product_id'] = $camk['product_id'];
        $niz[$camk['campaign']]['product'] = $camk['product'];
        $niz[$camk['campaign']]['status'] = $camk['status'];
        $niz[$camk['campaign']]['campaign_status'] = $camk['campaign_status'];
        $niz[$camk['campaign']]['splitType'] = $camk['splitType'];
        $niz[$camk['campaign']]['cronDate'] = $date;
        $niz[$camk['campaign']]['campaignType'] = 1;

        $niz[$camk['campaign']]['total_calls'] = 0;
        $niz[$camk['campaign']]['order_count'] = 0;
        $niz[$camk['campaign']]['cancel_count'] = 0;
        $niz[$camk['campaign']]['noOrder_count'] = 0;

        $niz[$camk['campaign']]['sumReturn'] = 0;
        $niz[$camk['campaign']]['sumRefund'] = 0;
        $niz[$camk['campaign']]['t_gross_profit'] = 0;

        $niz[$camk['campaign']]['sent_count'] = 0;
        $niz[$camk['campaign']]['delivered_count'] = 0;
        $niz[$camk['campaign']]['sumDelivMess'] = 0;

        $niz[$camk['campaign']]['opened_count'] = 0;
        $niz[$camk['campaign']]['unopened_count'] = 0;
        $niz[$camk['campaign']]['order_visited_count'] = 0;

        $niz[$camk['campaign']]['undelivered_count'] = 0;
        $niz[$camk['campaign']]['total_cost'] = 0;
        $niz[$camk['campaign']]['product_sent_count'] = 0;

        $niz[$camk['campaign']]['avgIncome'] = 0;
        $niz[$camk['campaign']]['ROI_avgIncome'] = 0;
        $niz[$camk['campaign']]['conversion_rate'] = 0;
        $niz[$camk['campaign']]['delivery_ratio'] = 0;

        $niz[$camk['campaign']]['message_id'] = 0;
        $niz[$camk['campaign']]['message_squize'] = 0;
    }

    $campaignsOrders = $_cron->getOrdersForUpdate(" and DATE(orders.orderdate)= '$date' ");;

    foreach($campaignsOrders AS $OrdRow){
        if (array_key_exists($OrdRow['campaign'],$niz)){
            $niz[$OrdRow['campaign']]['total_calls'] = $OrdRow['total_calls'];
            $niz[$OrdRow['campaign']]['order_count'] = $OrdRow['order_count'];
            $niz[$OrdRow['campaign']]['cancel_count'] = $OrdRow['cancel_count'];
            $niz[$OrdRow['campaign']]['noOrder_count'] = $OrdRow['noOrder_count'];
            $niz[$OrdRow['campaign']]['sumReturn'] = $OrdRow['sumReturn'];
            $niz[$OrdRow['campaign']]['sumRefund'] = $OrdRow['sumRefund'];
            $niz[$OrdRow['campaign']]['t_gross_profit'] = $OrdRow['t_gross_profit'];
        }
    }

    $campaignsOrdersCamp = $_cron->getOrdersCampUpd(" and DATE(orders.orderdate)= '$date' ");

    foreach ($campaignsOrdersCamp as $ordRowCamp){

        if(array_key_exists($ordRowCamp['campaign'],$niz)){
            print_r('aa');
            $niz[$ordRowCamp['campaign']]['sumReturn']      = $niz[$ordRowCamp['campaign']]['sumReturn']+ $ordRowCamp['sumReturnCamp'];
            $niz[$ordRowCamp['campaign']]['sumRefund']      = $niz[$ordRowCamp['campaign']]['sumRefund']+ $ordRowCamp['sumRefundCamp'];
            $niz[$ordRowCamp['campaign']]['t_gross_profit'] = $niz[$ordRowCamp['campaign']]['t_gross_profit']+ $ordRowCamp['t_gross_profitCamp'];
        }
    }

    $sMess            = $_cron-> countBulkMessages(" and DATE(DateSent) = '$date' ");  // za ove ide array_key_exists
    $dMess            = $_cron-> countBulkMessages(" and DATE(DateSent) = '$date' AND status = 2 ");  // za ove ide array_key_exists



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

    $_orders  = $_cron->getBulkOrders(" and DATE(orders.orderdate)= '$date ");

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

    $visitedBulkLinks = $_cron->getBulkShortLinkList(" '$date' ");

    $visitedOrders    = $_cron->getCampaignOrders(" and DATE(orders.orderdate)= '$date' ");

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
        $niz[$visBulLi['CampaignName']]['unopened_count']      = $niz[$visBulLi['CampaignName']]['sent_count'] - $visBulLi['opened'];
        $niz[$visBulLi['CampaignName']]['order_visited_count'] = $displayVisited[$visBulLi['CampaignName']];
    }

    foreach ($campaigns as $nizCamp){
        $totalOrderCount = $niz[$nizCamp['campaign']]["order_count"] + $niz[$nizCamp['campaign']]['order_visited_count'];

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
    
    return $niz;
}
