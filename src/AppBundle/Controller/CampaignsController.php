<?php

namespace AppBundle\Controller;

use AppBundle\Entity\OMG;
use AppBundle\Entity\SMS;
use AppBundle\Entity\Settings;
use AppBundle\Entity\Main;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;



class CampaignsController extends Controller
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
    /**
     * @Template(engine="twig")
     */

    public function addBulkAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        
        $title      = 'Add bulk';
        $conn       = $this->get('database_connection');

        $_omg       = new OMG($conn);


        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $cloneCamp       = $queryArr['campaign_id'];
//        $cloneState      = $queryArr['country'];
//        $cloneProduct    = $queryArr['product'];

        if (isset($cloneCamp) && !empty($cloneCamp))       {
            $campToClone = $_omg->getRowByID('CampManagement', $cloneCamp);
        } else {
            $cloneCamp ='';
            $campToClone='';
        }


        $getLastCampaignName = $_omg->getAllCampaignsWithProduct(" ORDER BY id DESC LIMIT 1");
       // print_r($getLastCampaignName);die();
        if (is_array($campToClone) && isset($getLastCampaignName[0]['CampaignName'])){
            $nOCampName = filter_var($getLastCampaignName[0]['CampaignName'], FILTER_SANITIZE_NUMBER_INT);
            $explode = explode('-',$nOCampName);
            
            $newNumberforCampain = $explode[0]+1;
            $campToClone['cloneCampName'] = "sms". $newNumberforCampain .strtolower($campToClone['Country']);
//        
        } 

        
        $_states     = $_omg->getStatesWithId();
        $_products   = $_omg->getProductList();
        $_campaigns  = $_omg->getAllCampaigns();

        $monthLess = date('Y-m-d', strtotime('-30 days'));

        return $this->render('campaigns/addBulk.html.twig', array(
            '_states'     => $_states,
            '_products'   => $_products,
            '_monthLess'  => $monthLess,
            'title'       => $title,
            '_campaigns'  => $_campaigns,
            'campToClone' => $campToClone
            ));
    
    }

    /**
     * @Template(engine="twig")
     */

    public function addReorderAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = 'Reorder Management';

        $_omg       = new OMG($conn);

        $_states    = $_omg->getStatesWithId();
        $_products  = $_omg->getProductList();

        return $this->render('campaigns/addReorder.html.twig', array(
            '_states' => $_states,
            '_products' => $_products,
            'title' =>$title
            ));
    }

    /**
     * @Template(engine="twig")
     */

    public function addSplitAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = 'Split test';

        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);

        $_states    = $_omg->getStates();
        $_products  = $_omg->getProductList();
        $_campaigns = $_sms->getCampaignList(" AND splitType = 1 ");
        $monthLess = date('Y-m-d', strtotime('-30 days'));

        return $this->render('campaigns/addSplit.html.twig', array(
                                                                    '_states' => $_states,
                                                                    '_products' => $_products,
                                                                    '_campaigns' => $_campaigns,
                                                                    '_monthLess' => $monthLess,
                                                                    'title' =>$title
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function campaignsAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }


        $conn       = $this->get('database_connection');
        $title      = 'SMS Campaigns';

        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);
        $_settings  = new Settings($conn);

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

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $cType      = $queryArr['cType'];
        $camp       = $queryArr['camp'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        //Campaign type filter
        if(isset($cType) && !empty($cType) || $cType === "0")
        {
            $ctQ = " and CampManagement.splitType = '$cType' ";
        }
        else{

            $cType = "";
            $ctQ = "";
        }

        //State filter
        if(isset($state) && !empty($state))
        {
            $scQ = " and CampManagement.Country = '$state' ";
        }
        else{

            $state = "";
            $scQ = "";
        }

        //Product filter
        if(isset($product) && !empty($product))
        {
            $spQ = " and products.id = '$product' ";
            $viewPQ  = " and CampManagement.product = '$product' ";
        }
        else{

            $product = "";
            $spQ = "";
            $viewPQ  = "";
        }

        //Campaign name filter
        if(isset($camp) && !empty($camp))
        {
            $cQ = " and CampManagement.id = '$camp' ";
            $viewcQ = " and CampManagement.id = '$camp' ";
        }
        else{

            $camp = "";
            $cQ = "";
            $viewcQ = "";
        }
        //Date from Filter
        if(isset($from) && !empty($from))
        {
            $dfQ = " and CampManagement.Datemade >= '$from' ";
            $supfQ = " AND DATE(entryDate) >= '$from' ";
            $cdfQ = " and Date(dateSent) >= '$from' ";
            $ordfQ = " and Date(phone_order_calls.date) >= '$from' ";
            $orderfQ = " and Date(orders.orderdate) >= '$from' ";
        }elseif (isset($from) && empty($from)){
            $dfQ = "";
            $supfQ = "";
            $cdfQ = "";
            $ordfQ = "";
            $orderfQ = "";
        }else{

            $from = $defaultDateFrom;
            $dfQ = " and CampManagement.Datemade >= '$from' ";
            $supfQ = " AND DATE(entryDate) >= '$from' ";
            $cdfQ = " and Date(dateSent) >= '$from' ";
            $ordfQ = " and Date(phone_order_calls.date) >= '$from' ";
            $orderfQ = " and Date(orders.orderdate) >= '$from' ";
        }
        //Date from Filter
        if(isset($to) && !empty($to))
        {
            $dtQ = " and CampManagement.Datemade <= '$to' ";
            $suptQ = " AND DATE(entryDate) <= '$to' ";
            $cdtQ = " and Date(dateSent) <= '$to' ";
            $ordtQ = " and Date(phone_order_calls.date) <= '$to' ";
            $ordertQ = " and Date(orders.orderdate) <= '$to' ";
        }elseif (isset($to) && empty($to)){
            
            $dtQ = "";
            $suptQ = "";
            $cdtQ = "";
            $ordtQ = "";
            $ordertQ = "";
        }else{

            $to = $defaultDateTo;
            $dtQ = " and CampManagement.Datemade <= '$to' ";
            $suptQ = " AND DATE(entryDate) <= '$to' ";
            $cdtQ = " and Date(dateSent) <= '$to' ";
            $ordtQ = " and Date(phone_order_calls.date) <= '$to' ";
            $ordertQ = " and Date(orders.orderdate) <= '$to' ";
        }


        $countQ     = $cdfQ."".$cdtQ;
        $countordQ  = $ordfQ."".$ordtQ;
        $queryOrders = $orderfQ."".$ordertQ;

        $query = "";
        $query .= $ctQ;
        $query .= $scQ;
        $query .= $spQ;
        $query .= $cQ;
        $query .= $dfQ;
        $query .= $dtQ;

        $suppressionQuery = $supfQ."".$suptQ;

        $viewQ = "";
        $viewQ .= $viewSQ;
        $viewQ .= $viewPQ;
        //print_r($query);die();
        $campaignsall  = $_sms->getCampaignListAll();
        $campaigns      = $_sms->getCampaignListForStats($query);
        $campaignsorder = $_sms->getBulkCampaignListOrder($countordQ);
        $campaignsnoorder = $_sms->getBulkCampaignListNoOrder($countordQ);
        $_products      = $_omg->getProductList();
        $_states        = $_omg->getStates();
//        $sMess          = $_sms->countBulkMessages($countQ); // Counts all messages
//        $dMess          = $_sms->countBulkMessages($countQ.' AND status = 2 ');
        $_orders        = $_omg->getBulkOrders($queryOrders);
        $_messages      = $_sms->getTranslationID($queryOrders);
        $_smsprices     = $_settings->getSMSprices();
        //$_suppression = $_sms->getSuppressionList($suppressionQuery);

        $cpl            = Array();
        $sentNum        = Array();
        $deliverNum     = Array();
        $noOrdArr       = Array();
        $OrdArr         = Array();
        $QuantArr       = Array();
        $sentMess       = Array();
        $deliveredMess  = Array();
        $sumDeliveredMess = Array();

        foreach($campaigns AS $eachc){
            $cpl[$eachc['CampaignName']] = Array("base"=>$eachc['price'],"upsell"=>$eachc['upsellPrice']);
            $sentNum[$eachc['CampaignName']]        = 0;
            $deliverNum[$eachc['CampaignName']]     = 0;

            $noOrdArr[$eachc['CampaignName']]       = 0;
            $OrdArr[$eachc['CampaignName']]         = 0;
            $QuantArr[$eachc['CampaignName']]       = 0;
            $sentMess[$eachc['CampaignName']]       = 0;
            $deliveredMess[$eachc['CampaignName']]  = 0;
        }

        foreach($campaignsorder AS $OrdRow){
            $OrdArr[$OrdRow['CampaignName']] = $OrdRow['Orders'];
        }

        foreach($campaignsnoorder AS $noOrdRow){
            $noOrdArr[$noOrdRow['CampaignName']] = $noOrdRow['noOrders'];
        }

        // pocetak algoritma za racunanje avgIncome
        $incomeArr = Array();
        $broj = 0;

        foreach ($_orders as $_orderItem){
            $incomeAdd = $cpl[$_orderItem['utm_campaign']]['base'];
            $broj++;
            $kampanja = $_orderItem['utm_campaign'];
            //$kolicina = explode(" ", $_orderItem['product']);
            //$kolicinaNum = number_format($kolicina[0], 0);
            $kolicinaNum    = $_orderItem['quantity'];

            $QuantArr[$_orderItem['utm_campaign']] = $QuantArr[$_orderItem['utm_campaign']] + $kolicinaNum;
            if ($kolicinaNum > 1){

                $incomeAdd = $cpl[$_orderItem['utm_campaign']]['base'] + ($cpl[$_orderItem['utm_campaign']]['upsell'] * ($kolicinaNum-1));

            }

            $incomeArr[$kampanja] = $incomeArr[$kampanja] + $incomeAdd;
        }

        //exception za stare kampanje
        $incomeArr["sms004ba"] = $incomeArr["smsBA004"] + $incomeArr["basms001"];
        $incomeArr["sms007rs"] = $incomeArr["rssms001"];
        $incomeArr["sms003mk"] = $incomeArr["mksms001"];

        $monthLess = date('Y-m-d', strtotime('-30 days'));

        $messageArr = Array('initial','squeeze');
        $messageArr['initial'] = Array();
        foreach ($_messages AS $poruka){
            if($poruka['pozicija'] == 1){

                $messageArr['initial'][$poruka['id']] = $poruka['messageID'];
            } else if ($poruka['pozicija'] == 2) {
                $messageArr['squeeze'][$poruka['id']] = $poruka['messageID'];
            }
        }

        //print_r($messageArr);
        $smsPrice   = Array();
        $exchval    = Array();
        foreach ($_smsprices AS $smspr){
            $smsPrice[$smspr['state']]  = $smspr['price'];
            $exchval[$smspr['state']]   = $smspr['exchval'];

        }

        $suppressionAll = Array();

        $campaignOptout = Array();
        foreach ($campaigns AS $eachCamp){
            $campaignOptout[$eachCamp['CampaignName']] = 0;
        }


        foreach ($suppressionAll AS $odjava=>$val) {
        //    $_smsMessage = $_sms->countOptedOut($odjava);
        //    if ($_smsMessage){
        //        $campaignOptout[$_smsMessage['messageId']] = $campaignOptout[$_smsMessage['messageId']] + 1;
        //    }
            $campaignOptout[$_smsMessage['messageId']] = 0;
        }



        $html = '<table class="dayView compact" id="example">
                <thead>
                <tr>
                    <td width="20px"> # </td>
                    <td>State code</td>
                    <td>Campaign name</td>
                    <td>Product</td>
                    <td>Date made</td>
                    <td>Message</td>
                    <td>Squeeze</td>
                    <td>Status</td>
                    <td>Orders</td>
                    <td>No orders</td>
                    <td>Prod. sent</td>
                    <td>Recipients</td>
                    <td>Sent</td>
                    <td>Delivered</td>
                    <td>Undelivered</td>
                    <td>Optout</td>
                    <td>Price</td>
                    <td>Upsell</td>
                    <td>Delivery ratio</td>
                    <td>Total cost (€)</td>
                    <td>avgIncome (€)</td>
                    <td>ROI avgIncome(€)</td>
                    <td>Conversion rate</td>
                    <td>Campaign status</td>
                    <td></td>
                    <td></td>
                </tr>
                </thead>
                <tbody id="tabela">';
        $counter = 0;
        //SVI TOTALI
        $tOrders = 0;
        $tNoOrders = 0;
        $tPrSent = 0;
        $tRecipients = 0;
        $tSent = 0;
        $tDelivered = 0;
        $tUndelivered = 0;
        $tDeliveryRatio = 0;
        $tCost = 0;
        $tAvgIncome = 0;
        $tRoi = 0;
        $tcRate = 0;
        $tOpted = 0;

//        foreach ($sMess as $sent) {
//            $sentMess[$sent['messageId']] = $sent['broj'];
//        }
//
//        foreach ($dMess as $deliv) {
//            $deliveredMess[$deliv['messageId']] = $deliv['broj'];
//            $sumDeliveredMess[$deliv['messageId']] = $deliv['smsCount'];
//        }

       // print_r($campaigns);die();
        foreach ($campaigns as $camp){

            //$undelivered = $sentMess[$camp["CampaignName"]] - $deliveredMess[$camp["CampaignName"]];
//            $numSentMessages = $sentMess[$camp["CampaignName"]];
            $numSentMessages = $camp["sent_count"];
//            $numDeliveredMessages = $deliveredMess[$camp["CampaignName"]];
            $numDeliveredMessages = $camp["delivered_count"];
            $undelivered = $camp["sent_count"] -  $camp["delivered_count"];
//            $numSumDeliveredMessages = $sumDeliveredMess[$camp["CampaignName"]];
            $numSumDeliveredMessages = $camp["sumDelivMess"];


//                    $sentTolerance = $numSentMessages + 50;
//                    if ($sentTolerance < $numDeliveredMessages){
//                        $numDeliveredMessages = $numDeliveredMessages / 2;
//                    }

            $totalCost = $smsPrice[$camp["Country"]] * (int)$numSumDeliveredMessages;
            if(isset($incomeArr[$camp["CampaignName"]]) && !empty($incomeArr[$camp["CampaignName"]])){
                $avgIncome = round($incomeArr[$camp["CampaignName"]] / $exchval[$camp["Country"]], 2);
            } else {
                $avgIncome = 0;
            }

            if($camp["active"] == 0){
                $statusStyle = ' style="background-color:#C00!important;color:#fff!important;" ';
                $statusText = "Inactive";
            } else {
                $statusStyle = ' style="background-color:#0C0!important;color:#fff!important;" ';
                $statusText = "Active";
            }

            //$avgIncome = 6 * (int)$camp["Orders"];
            $roi        = $avgIncome - $totalCost;
            $cRate      = ((int)$camp["Orders"]/$numDeliveredMessages) * 100;
            $optedRate  = ((int)$campaignOptout[$camp["CampaignName"]] / (int)$camp["RecipientNo"]) * 100;


            // **** Sabiranje svih totala
            $tOrders        = $tOrders + (int)$camp["Orders"];
            $tNoOrders      = $tNoOrders + (int)$camp["NotOrders"];
            $tPrSent        = $tPrSent + (int)$camp["productSent"];
            $tRecipients    = $tRecipients + (int)$camp["RecipientNo"];
            $tSent          = $tSent + $numSentMessages;
            $tDelivered     = $tDelivered + $numDeliveredMessages;
            $tOpted         = $tOpted + $optedRate;



            $tUndelivered = $tUndelivered + $undelivered;

            $tCost = $tCost + $totalCost;
            $tAvgIncome = $tAvgIncome + $avgIncome;
            $tRoi = $tAvgIncome - $tCost;
            $tcRate = $tcRate + $cRate;


            $deliveryRatio = ($numDeliveredMessages/$numSentMessages) * 100;
            $tDeliveryRatio = $tDeliveryRatio + $deliveryRatio;
                //*******************************
            $tabOdd = "";
            $statusInd = "";
            $counter++;
            
            if ($counter % 2 != 0){
                $tabOdd = "style='background-color:#eee'";
            }
            if ($camp["status"] == "Sent"){
                $statusInd = "style='color:#0c0'";
            } else if ($camp["status"] == "Pending"){
                $statusInd = "style='color:#cb0'";
            } else {
                $statusInd = "style='color:#c00'";
            }


            $selectedMessage = json_decode($camp["selectedMessages"]);

            $initialMess = "none";
            $squeezeMess = "none";
               foreach ($selectedMessage as $_singleMessage => $value){
                   if (isset($messageArr['initial'][$_singleMessage]) && !empty($messageArr['initial'][$_singleMessage])){
                       $initialMess = $messageArr['initial'][$_singleMessage];
                   }
                   if (isset($messageArr['squeeze'][$_singleMessage]) && !empty($messageArr['squeeze'][$_singleMessage])){
                       $squeezeMess = $messageArr['squeeze'][$_singleMessage];
                   }

               }
            //racunanje total cost za smsove
                

            $html .= '<tr id="r'.$counter.'">';
            $html .= '<td  '.$tabOdd.'>'.$counter.'</td>
                    <td '.$tabOdd.'>'.$camp["Country"].'</td>
                    <td '.$tabOdd.'><a href="bulkShortLinks?campaign='.$camp["CampaignName"].'" target="_blank">'.$camp["CampaignName"].'</a></td>
                    <td '.$tabOdd.'>'.$camp["title"].'</td>
                    <td '.$tabOdd.'>'.$camp["Datemade"].'</td>
                    <td '.$tabOdd.'><a href="messages?selectedmessage='.$initialMess.'#'.$initialMess.'" target="_blank">M-'.$initialMess.'</a></td>
                    <td '.$tabOdd.'><a href="messages?selectedmessage='.$squeezeMess.'#'.$squeezeMess.'" target="_blank">M-'.$squeezeMess.'</a></td>
                    <td '.$tabOdd.' onclick="tdOption(this);">
                      <span '.$statusInd.' class="fSpan">'.$camp["status"].'</span>
                      <select class="fSel" data-field="status" data-id="'.$camp["id"].'" style="width:100px;display:none">
                        <option value="">Choose status</option>
                        <option value="Pending">Pending</option>
                        <option value="Prepared">Prepared</option>
                        <option value="Sent">Sent</option>
                      </select>
                    </td>
                    <td '.$tabOdd.'>'.$OrdArr[$camp["CampaignName"]].'</td>
                    <td '.$tabOdd.'>'.$noOrdArr[$camp["CampaignName"]].'</td>
                    <td '.$tabOdd.'>'.$QuantArr[$camp["CampaignName"]].'</td>
                    <td '.$tabOdd.'>'.$camp["RecipientNo"].'</td>
                    <td '.$tabOdd.'>'.$numSentMessages.'</td>
                    <td '.$tabOdd.'>'.$numDeliveredMessages.'</td>
                    <td '.$tabOdd.'>'.round($undelivered).'</td>
                     <td '.$tabOdd.'><strong>'.round($optedRate,2).'%</strong></td>
                     <td '.$tabOdd.' onclick="tdOption(this);">
                        <span class="fSpan"><strong>'.$camp["price"].'</strong></span>
                        <input type="text" class="fSel" data-field="price" data-id="'.$camp["id"].'" style="width:30px;display:none">
                     <td '.$tabOdd.' onclick="tdOption(this);">
                        <span class="fSpan"><strong>'.$camp["upsellPrice"].'</strong></span>
                        <input type="text" class="fSel" data-field="upsellPrice" data-id="'.$camp["id"].'" style="width:30px;display:none">
                     </td>
                    <td '.$tabOdd.'><strong>'.round($deliveryRatio).'%</strong></td>
                    <td '.$tabOdd.'><strong>'.$totalCost.'</strong></td>
                    <td '.$tabOdd.'><strong>'.$avgIncome.'</strong></td>
                    <td '.$tabOdd.'><strong>'.round($roi,2).'</strong></td>
                    <td '.$tabOdd.'><strong>'.round($cRate, 3).' %</strong></td>
                    <td '.$statusStyle.' onclick="tdOption(this);">
                    <span class="fSpan">'.$statusText.'</span>
                      <select class="fSel" data-field="active" data-id="'.$camp["id"].'" style="width:70px;height:20px;display:none">
                        <option value="">Choose status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                      </select>
                    </td>
                    <td '.$tabOdd.'>
                   ';

            if ($camp["active"] == 1){
                $html .= '<button class="bigOrder GreyBtn" onclick="showCampaignStats('.$camp["id"].');" style="margin-top:0px;cursor:pointer;width:60px;font-size:12px;height:30px;">Edit</button>';
            }

            $html .= '</td>
                         <td '.$tabOdd.'>
                    <button>
                        <a href="'.$this->generateUrl('addBulk', array('campaign_id' => $camp['id']),true).'" target="_blank">Clone</a>
                    </button>
                    </td>';
            $html .= '</tr>';
        }
        $tDeliveryRatio     = round($tDeliveryRatio / $counter);
        $totalcRate         = $tcRate / $counter;
        $totalOptedRate     = round($tOpted / $counter,2);

        $html .= '</tbody>
            <tfoot>
                <td colspan="8" style="text-align:right;">TOTAL:</td>
                <td style="text-align:center;">'. $tOrders .'</td>
                <td style="text-align:center;">'.$tNoOrders .'</td>
                <td style="text-align:center;">'.$tPrSent .'</td>
                <td style="text-align:center;">'.$tRecipients .'</td>
                <td style="text-align:center;">'. $tSent .'</td>
                <td style="text-align:center;">'. $tDelivered .'</td>
                <td style="text-align:center;">'. $tUndelivered .'</td>
                <td style="text-align:center;">'. $totalOptedRate .'%</td>
                <td style="text-align:center;"></td>
                <td style="text-align:center;"></td>
                <td style="text-align:center;">'. $tDeliveryRatio .'%</td>
                <td style="text-align:center;">'. $tCost.'</td>
                <td style="text-align:center;">'.$tAvgIncome .'</td>
                <td style="text-align:center;">'.  round($tRoi, 3) .'</td>
                <td style="text-align:center;">'. round($totalcRate, 3).'%</td>
                <td></td>
                <td></td>
                <td></td>
            </tfoot>
        </table>';
       // print_r($campaigns);

        return $this->render('campaigns/campaigns.html.twig', array(
                                                                    '_html' => $html,
                                                                    '_states' => $_states,
                                                                    '_products' => $_products,
                                                                    '_campaigns' => $campaignsall,
                                                                    'from' => $from,
                                                                    'to' => $to,
                                                                    'title' =>$title
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function reorderAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }


        $conn       = $this->get('database_connection');
        $title      = 'Reorder Campaigns';

        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);
        $_settings  = new Settings($conn);

        $lastMonth  = date('Y-m');
        $firstDay   = $lastMonth."-01";

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $camp       = $queryArr['camp'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];


        //State filter
        if(isset($state) && !empty($state))
        {
            $scQ = " and orders.state = '$state' ";
            $viewSQ  = " and phone_order_reorder.Country = '$state' ";
        }
        else{

            $state = "";
            $scQ = "";
            $viewSQ  = "";
        }

        //Product filter
        if(isset($product) && !empty($product))
        {
            $spQ = " and orderitems.product = '$selProduct' ";
            $viewPQ  = " and phone_order_reorder.product = '$selProduct' ";
        }
        else{

            $product = "";
            $spQ = "";
            $viewPQ  = "";
        }

        //Campaign name filter
        if(isset($camp) && !empty($camp))
        {

            $viewcQ = " and phone_order_reorder.id = '$camp' ";
        }
        else{

            $camp = "";
            $viewcQ = "";
        }
        //Date from Filter
        if(isset($from) && !empty($from))
        {
            $dfQ = " and Date(orders.orderdate) >= '$from' ";
            $cdfQ = " and Date(dateSent) >= '$from' ";
            $ordfQ = " and Date(phone_order_calls.date) >= '$from' ";
        }
        else{

            $from = $firstDay;
            $dfQ = " and Date(orders.orderdate) >= '$from' ";
            $cdfQ = " and Date(dateSent) >= '$from' ";
            $ordfQ = " and Date(phone_order_calls.date) >= '$from' ";
        }
        //Date from Filter
        if(isset($to) && !empty($to))
        {
            $dtQ = " and Date(orders.orderdate) <= '$to' ";
            $cdtQ = " and Date(dateSent) <= '$to' ";
            $ordtQ = " and Date(phone_order_calls.date) <= '$to' ";
        }
        else{

            $to = "";
            $dtQ = "";
            $cdtQ = "";
            $ordtQ = "";
        }


        $countQ     = $cdfQ."".$cdtQ;
        $countordQ  = $ordfQ."".$ordtQ;

        $query = "";
        $query .= $scQ;
        $query .= $spQ;
        //$query .= $cQ;
        $query .= $dfQ;
        $query .= $dtQ;

        $viewQ = "";
        $viewQ .= $viewSQ;
        $viewQ .= $viewPQ;
        //$viewQ .= $viewcQ;


        // var_dump("OK");exit;
        $campaignsall       = $_sms->getReorderCampaignListAll($viewQ);
        //$campaigns          = $_sms->getReorderCampaignList($query);
        $campaignsnoorder   = $_sms->getReorderCampaignListNoOrder($countordQ);
        $campaignsorder     = $_sms->getReorderCampaignListOrder($countordQ);
        $_products          = $_omg->getProductList();
        $_states            = $_omg->getStates();
        $sMess              = $_sms->countMessages($countQ); // Counts all messages
        $dMess              = $_sms->countMessages($countQ.' AND status = 2 '); // Number 2 is assigned for delivered messages
        $_orders            = $_omg->getReorderOrders($query);
        $_smsprices         = $_settings->getSMSprices();


        $cpl = Array();
        $noOrdArr = Array();
        $OrdArr = Array();
        foreach($campaignsall AS $eachc){
            $cpl[$eachc['CampaignName']] = Array("base"=>$eachc['price'],"upsell"=>$eachc['upsellPrice']);
            $noOrdArr[$eachc['CampaignName']]       = 0;
            $OrdArr[$eachc['CampaignName']]         = 0;
            $QuantArr[$eachc['CampaignName']]       = 0;
            $sentMess[$eachc['CampaignName']]       = 0;
            $deliveredMess[$eachc['CampaignName']]  = 0;

        }

        foreach($campaignsnoorder AS $noOrdRow){
            $noOrdArr[$noOrdRow['CampaignName']] = $noOrdRow['noOrders'];
        }

        foreach($campaignsorder AS $OrdRow){
            $OrdArr[$OrdRow['CampaignName']] = $OrdRow['Orders'];
        }

        $incomeArr = Array();
        $broj = 0;

        foreach ($_orders as $_orderItem){
            $incomeAdd      = $cpl[$_orderItem['utm_campaign']]['base'];
            $broj++;

            $kampanja       = $_orderItem['utm_campaign'];
        //    $kolicina       = explode(" ", $_orderItem['product']);
        //    $kolicinaNum    = number_format($kolicina[0], 0);
            $kolicinaNum    = $_orderItem['quantity'];

            $QuantArr[$_orderItem['utm_campaign']] = $QuantArr[$_orderItem['utm_campaign']] + $kolicinaNum;
            if ($kolicinaNum > 1){

                $incomeAdd = $cpl[$_orderItem['utm_campaign']]['base'] + ($cpl[$_orderItem['utm_campaign']]['upsell'] * ($kolicinaNum-1));

            }

            $incomeArr[$kampanja] = $incomeArr[$kampanja] + $incomeAdd;
        }


        $monthLess = date('Y-m-d', strtotime('-30 days'));

        $smsPrice = Array();
        $exchval    = Array();
        foreach ($_smsprices AS $smspr){
            $smsPrice[$smspr['state']]  = $smspr['price'];
            $exchval[$smspr['state']]   = $smspr['exchval'];

        }

        $html = '<table class="dayView compact" id="example">
                <thead>
                <tr>
                    <td width="20px"> # </td>
                    <td>State code</td>
                    <td>Campaign name</td>
                    <td>Product</td>
                    <td>Sender</td>
                    <td>Date sent</td>
                    <td width="100px">Message</td>
                    <td>After Days</td>
                    <td>Day Hour</td>
                    <td>Orders</td>
                    <td>Not orders</td>
                    <td>Prod. sent</td>
                    <td>Sent</td>
                    <td>Delivered</td>
                    <td>Undelivered</td>
                    <td>Price</td>
                    <td>Upsell</td>
                    <td>Total cost (€)</td>
                    <td>avgIncome (€)</td>
                    <td>ROI avgIncome(6€)</td>
                    <td>Conversion rate</td>
                    <td>Status</td>
                    <td></td>
                </tr>
                </thead>
                <tbody id="tabela">';

                $counter = 0;
                //SVI TOTALI
                $tOrders = 0;
                $tNoOrders = 0;
                $tPrSent = 0;
                $tRecipients = 0;
                $tSent = 0;
                $tDelivered = 0;
                $tUndelivered = 0;
                $tCost = 0;
                $tAvgIncome = 0;
                $tRoi = 0;

                foreach ($sMess as $sent) {
                        $sentMess[$sent['messageId']] = $sent['broj'];
                }

                foreach ($dMess as $deliv) {
                        $deliveredMess[$deliv['messageId']] = $deliv['broj'];
                }

                foreach ($campaignsall as $camp){
                    $undelivered = $sentMess[$camp["CampaignName"]] - $deliveredMess[$camp["CampaignName"]];
                    $totalCost = $smsPrice[$camp["Country"]] * $deliveredMess[$camp["CampaignName"]];

                    if(isset($incomeArr[$camp["CampaignName"]]) && !empty($incomeArr[$camp["CampaignName"]])){
                        $avgIncome = round($incomeArr[$camp["CampaignName"]] / $exchval[$camp["Country"]], 2);
                    } else {
                        $avgIncome = 0;
                    }

                    $roi = $avgIncome - $totalCost;
                    $cRate = ((int)$OrdArr[$camp["CampaignName"]]/(int)$deliveredMess[$camp["CampaignName"]]) * 100;
                    // **** Sabiranje svih totala
                    $tOrders = $tOrders + (int)$OrdArr[$camp["CampaignName"]];
                    $tNoOrders = $tNoOrders + (int)$noOrdArr[$camp["CampaignName"]];
                    $tPrSent = $tPrSent + (int)$QuantArr[$camp["CampaignName"]];
                    $tSent = $tSent + (int)$sentMess[$camp["CampaignName"]];
                    $tDelivered = $tDelivered + (int)$deliveredMess[$camp["CampaignName"]];
                    $tUndelivered = $tUndelivered + $undelivered;
                    $tCost = $tCost + $totalCost;
                    $tAvgIncome = $tAvgIncome + $avgIncome;
                    $tRoi = $tAvgIncome - $tCost;
                    $tcRate = ($tOrders/$tDelivered) * 100;

                    //*******************************
                    $tabOdd = "";
                    $statusInd = "";
                    $counter++;
                    
                    if ($counter % 2 != 0){
                        $tabOdd = "style='background-color:#eee'";
                    }
                    if ($camp["active"] == "1"){
                        $statusInd = "style='color:#0c0'";
                        $statusText = "Active";
                    } else {
                        $statusInd = "style='color:#c00'";
                        $statusText = "Inactive";
                    }

                    //racunanje total cost za smsove
                        

                    $html .= '<tr id="r'.$counter.'">';
                    $html .= '<td  '.$tabOdd.'>'.$counter.'</td>
                            <td '.$tabOdd.'>'.$camp["Country"].'</td>
                            <td '.$tabOdd.'>'.$camp["CampaignName"].'</td>
                            <td '.$tabOdd.'>'.$camp["title"].'</td>
                            <td '.$tabOdd.'>'.$camp["SenderId"].'</td>
                            <td '.$tabOdd.'>'.$camp["Datesend"].'</td>
                            <td '.$tabOdd.' style="overflow:hidden;"><div class="makeBigger">'.$camp["Message"].'</span></td>
                            <td '.$tabOdd.'>'.$camp["afterDays"].'</td>
                            <td '.$tabOdd.'>'.$camp["dayHour"].'</td>
                            <td '.$tabOdd.'>'.$OrdArr[$camp["CampaignName"]].'</td>
                            <td '.$tabOdd.'>'.$noOrdArr[$camp["CampaignName"]].'</td>
                            <td '.$tabOdd.'>'.$QuantArr[$camp["CampaignName"]].'</td>
                            <td '.$tabOdd.'>'.$sentMess[$camp["CampaignName"]].'</td>
                            <td '.$tabOdd.'>'.$deliveredMess[$camp["CampaignName"]].'</td>
                            <td '.$tabOdd.'><a href="../../bulksms/reports/undelivered/'.$camp['CampaignName'].'.csv" target="_blank">'.$undelivered.'</a></td>
                            <td '.$tabOdd.'><strong>'.$camp["price"].'</strong></td>
                            <td '.$tabOdd.'><strong>'.$camp["upsellPrice"].'</strong></td>
                            <td '.$tabOdd.'><strong>'.$totalCost.'</strong></td>
                            <td '.$tabOdd.'><strong>'.$avgIncome.'</strong></td>
                            <td '.$tabOdd.'><strong>'.$roi.'</strong></td>
                            <td '.$tabOdd.'><strong>'.round($cRate, 3).' %</strong></td>
                            <td '.$tabOdd.' onclick="tdOption(this);">
                              <span '.$statusInd.' class="fSpan">'.$statusText.'</span>
                              <select class="fSel" data-field="active" data-id="'.$camp["id"].'" style="width:100px;display:none">
                                <option value="">Choose status</option>
                                <option value="0">Not Active</option>
                                <option value="1">Active</option>
                              </select>
                            </td>
                            <td '.$tabOdd.'><button type="button" data-id="'.$camp['id'].'" class="delButton" style="width:100px;font-size: 12px;" onclick="deleteRow(\'phone_order_reorder\',this,\'r'.$counter.'\');">Delete</button></td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody>
                            <tfoot>
                                <td colspan="9" style="text-align:right;">TOTAL:</td>
                                <td style="text-align: center">'.$tOrders.'</td>
                                <td style="text-align: center">'.$tNoOrders.'</td>
                                <td style="text-align: center">'.$tPrSent.'</td>
                                <td style="text-align: center">'.$tSent.'</td>
                                <td style="text-align: center">'.$tDelivered.'</td>
                                <td style="text-align: center">'.$tUndelivered.'</td>
                                <td style="text-align: center"></td>
                                <td style="text-align: center"></td>
                                <td style="text-align: center">'.$tCost.'</td>
                                <td style="text-align: center">'.$tAvgIncome.'</td>
                                <td style="text-align: center">'.$tRoi.'</td>
                                <td style="text-align: center">'.round($tcRate, 3).' %</td>
                                <td></td>
                                <td></td>
                            </tfoot>
                        </table>';

       return $this->render('campaigns/reorder.html.twig', array(
            '_html' => $html,
            '_states' => $_states,
            '_products' => $_products,
            'from' => $from,
            'to' => $to,
            'title'    => $title
       ));
    }

    /**
     * @Template(engine="twig")
     */

    public function splitAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = 'Split Campaigns';

        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);

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

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        //State filter
        if(isset($state) && !empty($state))
        {
            $scQ = " and state = '$state' ";
        }
        else{

            $state = "";
            $scQ = "";
        }

        //Product filter
        if(isset($product) && !empty($product))
        {
            $spQ = " and product = '$selProduct' ";
        }
        else{

            $product = "";
            $spQ = "";
        }

        //Date from Filter
        if(isset($from) && !empty($from))
        {
            $dfQ = " and DATE(datetime) >= '$from' ";
        }
        else{

            $from = $defaultDateFrom;
            $dfQ = " and DATE(datetime) >= '$from' ";
        }

        //Date from Filter
        if(isset($to) && !empty($to))
        {
            $dtQ = " and DATE(datetime) <= '$to' ";
        }
        else{

            $to = $defaultDateTo;
            $dtQ = " and DATE(datetime) <= '$to' ";
        }

        $query = "";
        $query .= $scQ;
        $query .= $spQ;
        $query .= $dfQ;
        $query .= $dtQ;

        $campaigns = $_sms->getSplitCampaignList($query);
        $_products = $_omg->getProductList();
        $_states = $_omg->getStates();

        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td width="20px"> # </td>
                        <td>State code</td>
                        <td>Campaign name</td>
                        <td>Product</td>
                        <td>Date created</td>
                        <td>Test Recipients</td>
                        <td>Total Recipients</td>
                        <td>Recipients left</td>
                        <td>Campaigns</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

                    $counter = 0;

                    foreach ($campaigns as $camp){

                        $counter++;
                        $recipientsLeft = (int)$camp["totalRecipients"] - (int)$camp["recipients"];

                        $campaignList   = $camp["campaigns"];
                        $eachCamp       = explode(",", $campaignList);
                        $disabled       = "";
                        $disabledStyle  = "";
                        if ($recipientsLeft <= 0){
                            $disabled       = "disabled=disabled";
                            $disabledStyle  = "opacity: 0.4;";
                        }

                        $html .= '<tr id="r'.$counter.'">';
                        $html .= '<td  '.$tabOdd.'>'.$counter.'</td>
                                <td '.$tabOdd.'>'.$camp["state"].'</td>
                                <td '.$tabOdd.'>'.$camp["campName"].'</td>
                                <td '.$tabOdd.'>'.$camp["title"].'</td>
                                <td '.$tabOdd.'>'.$camp["datetime"].'</td>
                                <td '.$tabOdd.'>'.$camp["recipients"].'</td>
                                <td '.$tabOdd.'>'.$camp["totalRecipients"].'</td>
                                <td '.$tabOdd.'>'.$recipientsLeft.'</td>
                                <td '.$tabOdd.'>';

                            foreach ($eachCamp AS $item){
                                $html .= '<button type="button" id="'.$item.'" class="bigOrder win'.$counter.'" style="width:auto;font-size: 12px;height:30px;line-height:20px;margin-top:0px;color:#fff;'.$disabledStyle.'" onclick="setWinner(this,\'win'.$counter.'\',\''.$item.'\',\''.$camp["recipients"].'\',\''.$camp["totalRecipients"].'\');" '.$disabled.'>'.$item.'</button>';
                            }

                        $html .=        '</td>';
                        $html .= '</tr>';
                    }

                $html .= '</tbody></table>';


        return $this->render('campaigns/split.html.twig', array(
                                                                '_html' => $html,
                                                                '_states' => $_states,
                                                                '_products' => $_products,
                                                                'from' => $from,
                                                                'to' => $to,
                                                                'title'    => $title
                                                                ));
    }

    /**
     * @Template(engine="twig")
     */

    public function shortLinksAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = 'Short Link';
        $_sms       = new SMS($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $from               = $queryArr['from'];
        $to                 = $queryArr['to'];

        //Date from Filter
        if(isset($from) && !empty($from))
        {
            $dfQ = " and DATE(phone_order_shorturl.createDate) >= '$from' ";
        }
        else{

            $from = "";
            $dfQ = "";

        }
        //Date from Filter
        if(isset($to) && !empty($to))
        {
            $dtQ = " and DATE(phone_order_shorturl.createDate) <= '$to' ";
        }
        else{

            $to = "";
            $dtQ = "";
        }


        $query = "";
        $query .= $dfQ;
        $query .= $dtQ;

        $_data = $_sms->getShortLinkList($query);


        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Date Created</td>
                        <td>Long URL</td>
                        <td>Short Code</td>
                        <td>Visits</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

                $counter = 0;

                foreach ($_data as $row){

                    $counter++;

                        $html .= '<tr>';
                        $html .= '<td>'.$counter.'</td>
                                    <td>'.$row["createDate"].'</td>
                                    <td title ="'.$row["longURL"].'" >'.substr($row["longURL"], 0, 140).'</td>
                                    <td>'.$row["shortCode"].'</td>
                                    <td>'.$row["visits"].'</td>';
                        $html .= '</tr>';
                }

                $html .= '</tbody></table>';
            

       return $this->render('campaigns/shortLinks.html.twig', array(
                                                                '_html' => $html,
                                                                'from' => $from,
                                                                'to' => $to,
                                                                'title'    => $title
                                                                ));
    }

    /**
     * @Template(engine="twig")
     */

    public function messagesAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = 'Message templates';

        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $selectedmessage    = $queryArr['selectedmessage'];
        $product            = $queryArr['selProduct'];
        $selType            = $queryArr['selType'];
        $from               = $queryArr['from'];
        $to                 = $queryArr['to'];

        //Selected message filter
        if(isset($selectedmessage) && !empty($selectedmessage))
        {
           
        }
        else{
            $selectedmessage = "";
        }

        //Product filter
        if(isset($product) && !empty($product))
        {
            $spQ = " and products.id = '$product' ";
        }
        else{

            $product = "";
            $spQ = "";
        }

        //Campaign name filter
        if(isset($selType) && !empty($selType))
        {
            $tQ = " and position = '$selType' ";
        }
        else{

            $type = "";
            $tQ = "";
        }
        //Date from Filter
        if(isset($from) && !empty($from))
        {
            $dfQ = " and phone_order_messages.entryDate >= '$from' ";
        }
        else{

            $from = "";
            $dfQ = "";

        }
        //Date from Filter
        if(isset($to) && !empty($to))
        {
            $dtQ = " and phone_order_messages.entryDate <= '$to' ";
        }
        else{

            $to = "";
            $dtQ = "";
        }


        $query = "";
        $query .= $spQ;
        $query .= $tQ;
        $query .= $dfQ;
        $query .= $dtQ;

        $_products  = $_omg->getProductList();
        $_states    = $_omg->getStates();
        $_messages  = $_sms->getMessageList($query);
        //print_r($_messages);die();


        $html = '<table class="dayView" id="example">
                    <thead>
                    <tr>
                        <td width="20px"> # </td>
                        <td>Type</td>
                        <td>Product</td>
                        <td>MessageId</td>
                        <td>Entry date</td>
                        <td>Message</td>
                        <td>Length</td>
                        <td style="width:400px;">Languages</td>
                        <td></td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

                    $counter = 0;

                    foreach ($_messages as $message){

                        $tabOdd = "";
                        $counter++;
                        $idPoruke = $message['id'];

                        $markSelect = "";
                        if ($idPoruke == $selectedmessage){
                            $markSelect = 'style="background-color: #FF0!important;"';
                        }

                        $_translations = $_sms->getTranslationByMessage($idPoruke);

                        if ($counter % 2 != 0){
                            //$tabOdd = "style='background-color:#eee'";
                        }

                        $tipPoruke = "";
                            if ($message['type'] == 1){
                                $tipPoruke = "initial";
                            } else if ($message['type'] == 2){
                                $tipPoruke = "squeeze";
                            }
    //
                        $html .= '<tr id="r'.$counter.'">';
                        $html .= '<td  '.$tabOdd.'><a name="'.$idPoruke.'">'.$counter.'</td>
                                    <td '.$tabOdd.'>'.$tipPoruke.'</td>
                                    <td '.$tabOdd.'>'.$message["productID"].'</td>
                                    <td '.$tabOdd.'>M-'.$message["id"].'</td>
                                    <td '.$tabOdd.'>'.$message['entryDate'].'</td>
                                    <td '.$tabOdd.' '.$markSelect.'>'.$message["message"].'</td>
                                    <td '.$tabOdd.'>'.$message["mLength"].'</td>
                                    <td '.$tabOdd.'>';

                                $translationArr = Array();
                                foreach ($_translations AS $translation){
                                            array_push($translationArr, $translation["state"]);
                                }
                                $stateExist = "";
                                $trAct = "new";
                                foreach ($_states as $_state) {
                                    if (in_array($_state["code2"], $translationArr)){
                                        $stateExist = "existTrans";
                                        $trAct = "update";
                                    }

                                    $html .= '<span class="stateClick '.$stateExist.'" data-trid="'.$message['id'].'" onclick="showMessageTranslation(this,\''.$trAct.'\');">'.$_state["code2"].'</span> ';
                                    $stateExist = "";
                                    $trAct = "new";
                                }

                        $html .= '</td>
                                    <td '.$tabOdd.'><button type="button" data-id="'.$message['id'].'" class="delButton" style="width:100px;font-size: 12px;" onclick="deleteRow(\'phone_order_messages\',this,\'r'.$counter.'\');">Delete</button></td>';
                        $html .= '</tr>';
                    }                   
                
        $html .= '</tbody></table>';


        return $this->render('campaigns/messages.html.twig', array(
                                                                    '_html' => $html,
                                                                    '_states' => $_states,
                                                                    '_products' => $_products,
                                                                    'from' => $from,
                                                                    'to' => $to,
                                                                    'title'    => $title
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function messagePerformanceAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = 'Message performance';

        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $product    = $queryArr['product'];
        $product2   = $queryArr['product2'];
        $product3   = $queryArr['product3'];

        //Product filter
        if(isset($_GET['product']) && !empty($_GET['product']))
        {
            $spQ = " and products.id = '$product' ";
        }
        else{

            $product = "0";
            $spQ = " and products.id = '$product' ";
        }
        //Product2 filter
        if(isset($_GET['product2']) && !empty($_GET['product2']))
        {
            $spQ2 = " or products.id = '$product2' ";
        }
        else{

            $product2 = "";
            $spQ2 = "";
        }
        //Product3 filter
        if(isset($_GET['product3']) && !empty($_GET['product3']))
        {
            $spQ3 = " or products.id = '$product3' ";
        }
        else{

            $product3 = "";
            $spQ3 = "";
        }

        $query = "";
        $query .= $spQ;
        $query .= $spQ2;
        $query .= $spQ3;

        // var_dump("OK");exit;
        $campaigns      = $_sms->getCampaignList($query);
        $_products      = $_omg->getProductList();
        $_states        = $_omg->getStates(" AND hasSales = 1");
        $_mesages       = $_sms->showMessagePerformance($query);
        $_translations  = $_sms->getTranslationID();

        $_translationsI = $_sms->getTranslationIDByPosition("1");
        $_translationsS = $_sms->getTranslationIDByPosition("2");


        $nizPrevoda = Array();
        foreach ($_translations AS $prevod ){
            $nizPrevoda[$prevod['id']] = Array();
        }

        $campaignRate   = Array();
        $campaignState  = Array();
        $messageEqual   = Array();
        foreach ($campaigns as $camp){
            $cRate      = ((int)$camp["Orders"]/(int)$camp["delivered"]) * 100;
            $messageObj = json_decode($camp["selectedMessages"]);

                $translationID = 0;
                foreach ($messageObj as $_singleMessage => $value) {
                        if($translationID != 0){
                            $messageEqual[$translationID]   = $_singleMessage;
                            $messageEqual[$_singleMessage]  = $translationID;
                        } else {
                            $messageEqual[$_singleMessage]  = 0;
                        }

                        $translationID = $_singleMessage;
                        $campaignRate[$camp["Country"]][$translationID] = $cRate;
                         // PROVJERITI DA LI JE MOGUCE DA POSTOJI VISE VRIJEDNOSTI ZA PORUKU
                    array_push($nizPrevoda[$_singleMessage], $camp['id']);
                }
        }

        foreach ($_states as $state){ 
        $arCount = 0;
        arsort($campaignRate[$state["code2"]]);

            foreach($campaignRate[$state["code2"]] AS $key=>$value){

                $arCount++;
                $rateLevel[$state['code2']][$key] = $arCount;

            }

        }

        $translationArr = Array();
        foreach ($_states as $state){
            $translationArr[$state['code2']]    = Array();
            $translationArrI[$state['code2']]   = Array();
        }

        foreach ($_translations as $translation){
            $trState = $translation['state'];

                foreach ($translationArr as $key => $val) {
                        if ($key == $trState) {
                            $translationArr[$key][$translation['messageID']] = $translation['id'];
                        }
                }
        }

        //initial messages
        $translationArrI = Array();
        foreach ($_translations as $translation){
                if ($translation['pozicija'] == 1) {
                    $translationArrI[$translation['messageID']] = $translation['id'];
                }
        }
        //squeeze messages
        $translationArrS = Array();
        $translationArrS[0] = "none";
        foreach ($_translations as $translation){
                if ($translation['pozicija'] == 2) {
                    $translationArrS[$translation['id']] = $translation['messageID'];
                }
        }


        $html = '<table class="dayView" id="example">
                    <thead>
                    <tr>
                        <td width="20px"> # </td>
                        <td >Initial Code</td>
                        <td >Product name</td>';

        foreach ($_states AS $_state){
            $html .= "<td>".$_state['code2']."</td>";
        }

        $html .= '</tr>
                    </thead>
                    <tbody id="tabela">';

                $counter = 0;

                foreach ($_mesages as $message){
                    $totalConvRate = 0;
                    $convCount = 0;
                    //*******************************
                    $tabOdd = "";
                    $statusInd = "";
                    $counter++;
                    
                    if ($counter % 2 != 0){
                        $tabOdd = "style='background-color:#eee'";
                    }

                    $html .= '<tr id="r'.$counter.'">';
                    $html .= '<td '.$tabOdd.'>'.$counter.'</td>';
                    $html .= '<td '.$tabOdd.'><button type="button" onclick="initialMessage('.$message["id"].');" style="width:100%">M-'.$message["id"].'</button></td>';

                    $html .=    ' <td '.$tabOdd.'>'.$message["productID"].'</td>';
                            
                            foreach ($_states AS $_state){
                                $idPrevoda = $translationArr[$_state["code2"]][$message["id"]];

                                if($rateLevel[$_state["code2"]][$idPrevoda]) {
                                    $rateNum = $rateLevel[$_state["code2"]][$idPrevoda];
                                    if(strlen($rateLevel[$_state["code2"]][$idPrevoda]) == 1) {
                                        $rateNum = "0".$rateNum;
                                    }

                                    $convRate = round($campaignRate[$_state["code2"]][$idPrevoda],2);

                                    $totalConvRate = $totalConvRate + $convRate;
                                    $convCount++;
                                    //$rateNum  varijabla je rating konverzije
                                    $html .= '<td '.$tabOdd.'><span style="font-size:13px;color:#66a;cursor:pointer;" onclick="showCampaignStats('.$nizPrevoda[$idPrevoda][0].');">'.round($campaignRate[$_state["code2"]][$idPrevoda],2).'</span></td>';
                                } else {
                                    $html .= '<td '.$tabOdd.'><span style="color:#bbb;">x</span></td>';
                                }
                            }
                    $totalRate = $totalConvRate / $convCount;
                    $html .= '<td  '.$tabOdd.'>'.round($totalRate, 2).'</td>';
                    $html .= '</tr>';
                }

            $html .=    '</tbody></table>';



            $st_num = count($_states);
            $html2 = '<input type="hidden" id="st_num" value="'.$st_num.'">';
            $state_counter = 0;
            foreach($_states as $_state){
                $html2 .= '<button type="button" id="btn-'.$state_counter.'" onclick="initialMessageData('.$state_counter.');" style="width:8%;">'.$_state['code2']."</button>";
                $html2 .= '<input type="hidden" id="state_id'.$state_counter++.'" value='.$_state["code2"].'>';

            }

        return $this->render('campaigns/messagePerformance.html.twig', array(
                                                                    '_html' => $html,
                                                                    '_html2' => $html2,
                                                                    '_products' => $_products,
                                                                    'title'    => $title
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function suppressionAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        return $this->render('::campaigns/suppression.html.php');
    }

    /**
     * @Template(engine="twig")
     */

    public function smsDifferenceAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        
        return $this->render('::campaigns/smsDifference.html.php');
    }


    public function bulkShortLinksAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = 'Bulk Short Link';
        $_sms       = new SMS($conn);
        $_omg       = new OMG($conn);

        $a_date = Date("Y-m-h");
        $godina = Date("Y");
        $mjesec = Date("m");
        $_products = $_omg->getProductList("id, title", "1");
        $ppppp= array();
        foreach ($_products as $majProduct){
            $ppppp[$majProduct['id']] = $majProduct['title'];
        }


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

        $state       = $queryArr['state'];
        $product     = $queryArr['product'];
        $campaign    = $queryArr['campaign'];
        $from        = $queryArr['from'];
        $to          = $queryArr['to'];


        if(isset($state) && !empty($state)) {
            $scQ = " and CampManagement.Country = '$state' ";
            $ordSt     = " and orders.state = '$state'";
        } else {
            $state = "";
            $scQ  = "";
            $oordSt="";
        }
        if(isset($product) && !empty($product)) {
            $prQ = " and CampManagement.product = '$product' ";
//            $ordProduc = " and products.id = '$product' ";
            $ordProduc = " and orders.product_name = '$ppppp[$product]' ";
        } else {
            $product = "";
            $prQ = "";
            $ordProduc="";
        }
        if(isset($campaign) && !empty($campaign)) {
            $cQ  = " and CampManagement.CampaignName = '$campaign' ";
            $ordCamp   = " and orders.utm_campaign = '$campaign'";
        } else {
            $campaign = "";
            $cQ = ""; $ordCamp="";
        }
        if(isset($from) && !empty($from))        {
            $dfQ = " and CampManagement.Datemade >= '$from' ";
            $ordFrom   = " and orders.orderdate >= '$from' ";
        } else {
            $from = $defaultDateFrom;
            $dfQ = " and CampManagement.Datemade >= '$from' ";
            $ordFrom = " and orders.orderdate >= '$from' ";
        }
        if(isset($to) && !empty($to))              {
            $dtQ = " and CampManagement.Datemade <= '$to' ";
            $ordTo     = " and orders.orderdate <= '$to' ";
        } else {
            $to = $defaultDateTo;
            $dtQ = " and CampManagement.Datemade <= '$to' ";
            $ordTo = " and orders.orderdate <= '$to' ";
        }


        $query = "";
        $query .= $scQ;
        $query .= $prQ;
        $query .= $cQ;
        $query .= $dfQ;
        $query .= $dtQ;
        
        $queryOrders = "";
        $queryOrders .= $ordSt;
        $queryOrders .= $ordProduc;
        $queryOrders .= $ordCamp;
        $queryOrders .= $ordFrom;
        $queryOrders .= $ordTo;

        $_data     = $_sms->getBulkShortLinkList($query);

        $_states   = $_omg->getStates();
        $_products = $_omg->getProductList("id, title", "1");
        $orders    = $_sms->getCampaignOrders($queryOrders);
        //print_r($orders);
        $displayOrders =  array();
        foreach ($orders as $ord){
            $displayOrders[$ord['utm_campaign']]=$ord['orderCount'];
        }
        //print_r($displayOrders);
       // print_r($_data);
        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Campaign name</td>
                        <td>Sent</td>
                        <td>Opened</td>
                        <td>Unopened</td>
                        <td>Number of orders</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

        $counter = 0;

        foreach ($_data as $row){

            $counter++;

            $html .= '<tr>';
            $html .= '<td>'.$counter.'</td>
                                    <td>'.$row["CampaignName"].'</td>
                                    <td>'.$row["sent"].'</td>
                                    <td>'.$row["opened"].'</td>
                                    <td>'.$row["unopened"].'</td>';
            if (array_key_exists($row["CampaignName"],$displayOrders )){
                $html .= '<td>'.$displayOrders[$row["CampaignName"]].'</td>';
            } else {
                $html .=  '<td>0</td>';
            }


            $html .= '</tr>';
        }

        $html .= '</tbody></table>';


        return $this->render('campaigns/bulkShortLinks.html.twig', array(
            '_html' => $html,
            '_products' =>$_products,
            '_states'  =>$_states,
            'title'    => $title,
            'campaign' => $campaign,
            'from' =>$from,
            'to' =>$to,
        ));



    }


    public function smsCampaignsAction()
    {


        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        $title    = 'SMS Bulk Payments';

        $todayDate = Date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime("yesterday"));
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
        $_camp      = $queryArr['campaign_id'];

        $conn = $this->get('database_connection');
        $_omg   = new OMG($conn);
        $_sms       = new SMS($conn);


        $_products      = $_omg->getProductList("id, title", "1");
        $_states        = $_omg->getStates();
        $_campaigns     = $_sms->getCampaignList();
        $_campTypes     = array(
            0 => 'Reorder campaign',
            1 => 'Sms campaign',
        );

        if ($startDate == date('Y-m-d')){
          $startDate = date("Y-m-01", strtotime("-1 month"));
        }


        if ($from > $yesterday){
            $from = $yesterday;
        }
        if ($to > $yesterday){
            $to = $yesterday;
        }

        if(isset($from) && !empty($from))   { $dfQ = "  and DATE(orders.orderdate) >= '$from' ";   } else { $from = $startDate;  $dfQ = " and CampManagement.Datemade >= '$from' ";  }
        if(isset($to) && !empty($to))       { $dtQ = " and DATE(orders.orderdate) <= '$to' ";      } else { $to = $yesterday; $dtQ = " and CampManagement.Datemade <= '$to' ";}


        $html = "";

        return $this->render('campaigns/smsCampaigns.html.twig', array(
            '_html' => $html,
            '_states' => $_states,
            '_products' => $_products,
            '_campTypes' => $_campTypes,
            'from' => $from,
            'to' =>$to,
            'title' => $title,
            'yesterday' =>$yesterday,
            '_campaigns' =>$_campaigns,
            '_camp' => $_camp));

    }
}
