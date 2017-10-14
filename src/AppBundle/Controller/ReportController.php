<?php

namespace AppBundle\Controller;

use AppBundle\Entity\OMG;
use AppBundle\Entity\Report;
use AppBundle\Entity\Settings;
use AppBundle\Entity\SMS;
use AppBundle\Entity\Main;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\HelpersController;
use Symfony\Component\Filesystem\Filesystem;




class ReportController extends Controller
{
    private function checkThisSession(){
//        $conn       = $this->get('database_connection');
//        $_main      = new Main($conn);
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

    public function smsSentAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Sent SMS Report';

        $conn     = $this->get('database_connection');
        $_reports = new Report($conn);
        $_omg     = new OMG($conn);

        $yesterday = Date("Y-m-d", strtotime('-1 days'));

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $sender   = $queryArr['sender'];
        $status   = $queryArr['status'];
        $_smstype = $queryArr['smstype'];
        $num      = $queryArr['ordNum'];
        $from     = $queryArr['from'];
        $to       = $queryArr['to'];
        $phone    = $queryArr['phone'];

        if (isset($sender) && !empty($sender)) {
            $sQ = " and smsMessages.from LIKE '$sender' ";
        } else {
            $sQ = "";
        }
        if (isset($status) && !empty($status)) {
            $stQ = " and status = '$status' ";
        } else {
            $stQ = "";
        }
        if (isset($_smstype) && !empty($_smstype)) {
            $_smsQ = " AND LEFT(messageId,3) = '$_smstype'";
        } else {
            $_smsQ = "";
        }
        if (isset($num) && !empty($num)){
            $nQ = "ORDER BY id DESC LIMIT $num";
        } else {
            $nQ = "";
        }
        if (isset($from) && !empty($from)) {
            $dfQ = " and DATE(dateSent) >= '$from' ";
        } else {
            $from = $yesterday;
            $dfQ = " and DATE(dateSent) >= '$yesterday' ";
            $dfQ = " and dateSent >= '2017-04-24 17:58:00' ";
        }
        if (isset($to) && !empty($to)) {
            $dtQ = " and DATE(dateSent) <= '$to' ";
        } else {
            $to = $yesterday;
           // $dtQ = " and DATE(dateSent) <= '$yesterday' ";
            $dtQ = " and dateSent <=  '2017-04-24 18:00:00' ";
        }
        if (isset($phone) && !empty($phone)){
            $phoneQ   = " AND `origin` LIKE '%$phone%' ";
        } else {
            $phoneQ = "";
        }

        $Query = " 1 ";    //default
        $Query .= $sQ;     //sender
        $Query .= $stQ;    //status
        $Query .= $_smsQ;  //sms type
        $Query .= $dfQ;    //date from
        $Query .= $dtQ;    //date to
        $Query .= $phoneQ; //phone criteria
        $Query .= $nQ;     //Order Num

        $_states = $_omg->getCompanyInfo();
        $_data   = $_reports->getSentSMS("*",$Query);

        $html = '<table width="1300px" class="dayView compact" id="example">
                        <thead style="cursor:pointer;">
                        <tr >
                          <td width="20px">#</td>
                          <td >Date sent</td>
                          <td >SMS ID</td>
                          <td >From</td>
                          <td >To</td>
                          <td >Message ID</td>
                          <td >Message text</td>
                          <td >Status</td>
                          <td >Response</td>
                        </tr>
                       </thead>
                       <tbody id="tabela">';

        $counter = 0;
        $showColor = "";
        $countSent = 0;
        $countDeliv = 0;
        $countError = 0;
        foreach ($_data as $row){
            $statusText = "";
            if ($row['status'] == 1) {
                $statusText = "Sent";
                $countSent++;
            }  else if ($row['status'] == 2) {
                $statusText = "Delivered";
                $countDeliv++;
            }  else {
                $statusText = "Undelivered";
                $countError++;
            }
            $counter++;
            $html.= '<tr style="margin-top:1px; cursor:pointer;">
                     <td class="'.$showColor.'">'.$counter.'</td>
                     <td class="'.$showColor.'">'.$row['dateSent'].'</td>
                     <td class="'.$showColor.'">'.$row['smsId'].'</td>
                     <td class="'.$showColor.'">'.$row['from'].'</td>
                     <td class="'.$showColor.'">'.$row['origin'].'</td>
                     <td class="'.$showColor.'">'.$row['messageId'].'</td>
                     <td class="'.$showColor.'" style="text-align:left;">'.$row['message'].'</td>
                     <td class="'.$showColor.'">'.$statusText.'</td>
                     <td class="'.$showColor.'">'.$row['response'].'</td>
                   </tr>';
        }

        $html.= '</tbody>
                 <tfoot>
                 <tr >
                     <td colspan="4"><strong>Total delivered : '.$countDeliv.' | Total Errors: '.$countError.'| No status: '.$countSent.'</strong></td>
                     <td></td>
                     <td ></td>
                     <td ></td>
                     <td ></td>
                     <td ></td>
                 </tr>
                 </tfoot>
                 </table>';
//        /print_r($html);die();

        $response = $this->render('report/smsSent.html.twig', array(
                '_html'   => $html,
                '_states' => $_states,
                'from'    => $from,
                'to'      => $to,
                'title'   => $title)
        );
        return $response;
    }


    public function phoneStatusAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);
//
        $title    = 'Phone statuses Report';

        $conn     = $this->get('database_connection');
        $_reports = new Report($conn);
        $_omg     = new OMG($conn);
        $_sms     = new SMS($conn);

        $yesterday = Date("Y-m-d", strtotime('-1 days'));

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state      = $queryArr['state'];
        $_smsstatus = $queryArr['smsstatus'];
        $hlrstatus  = $queryArr['hlrstatus'];
        $twstatus   = $queryArr['twstatus'];
        $_omgstatus = $queryArr['omgstatus'];
        $num        = $queryArr['ordNum'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];
        $phone      = $queryArr['phone'];
        $checkbox   = $queryArr['nonumber'];


        if(isset($state) && !empty($state))           { $sQ = " and orders.state = '$state' ";                                                } else { $sQ = ""; }
        if(isset($_smsstatus) && !empty($_smsstatus)) { $stQ = " and sms_notifications.confirm  = '$_smsstatus' ";                            } else { $stQ = ""; }
        if(isset($hlrstatus) && !empty($hlrstatus))   { $hstQ = " and sms_notifications.hlrConfirm  = '$hlrstatus' ";                         } else { $hstQ = ""; }
        if(isset($twstatus) && !empty($twstatus))     { $twstQ = " and sms_notifications.twillioConfirm  = '$twstatus' ";                     } else { $twstQ = ""; }
        if(isset($_omgstatus) && !empty($_omgstatus)) {
            if($_omgstatus == 1){
                $ostQ = " and (sms_notifications.hlrConfirm = 1 OR sms_notifications.confirm = 2) ";
            } elseif ($_omgstatus == 2){
                $ostQ = " and sms_notifications.hlrConfirm != 1 AND sms_notifications.confirm != 2 and sms_notifications.twillioConfirm != 1 ";
            }
        } else { $ostQ = ""; }
        if(isset($num) && !empty($num))               { $nQ = " ORDER BY id DESC LIMIT $num ";                                                } else { $nQ = ""; }
        if(isset($from) && !empty($from))             { $dfQ = "  and DATE(orders.orderdate) >= '$from' ";                                    } else { $dfQ = "  and DATE(orders.orderdate) >= '$yesterday' "; $from =$yesterday;}
        if(isset($to) && !empty($to))                 { $dtQ = " and DATE(orders.orderdate) <= '$to' ";                                       } else { $dtQ = " and DATE(orders.orderdate) <= '$yesterday' "; $to = $yesterday;}
        if(isset($phone) && !empty($phone))           { $phoneQ = " AND orders.telephone LIKE '%$phone%' ";                                   } else { $phoneQ = ""; }
        if($checkbox =='on')                          { $nnQ = " AND char_length(orders.telephone) > 3 ";                                     } else { $nnQ = "";}

        $Query  = " 1 ";   //default
        $Query .= $sQ;     //sender
        $Query .= $stQ;    //sms status
        $Query .= $hstQ;   //hlr status
        $Query .= $twstQ;  //twillio status
        $Query .= $ostQ;   //OMG status
        $Query .= $dfQ;    //date from
        $Query .= $dtQ;    //date to
        $Query .= $phoneQ; //phone criteria
        $Query .= $nnQ;    //no number checkbox
        $Query .= $nQ;     //Order Num

        //print_r($Query);die();

        $_states = $_omg ->getStates();
        $_data = $_reports->getPhoneStatus($Query);

        $areaCodes = array(
            "HR"=>"385",
            "BA"=>"387",
            "RS"=>"381",
            "MK"=>"389",
            "SI"=>"386",
            "BG"=>"359",
            "IT"=> "39",
            "SK"=>"421",
            "PL"=> "48",
            "GR"=> "30",
            "LV"=>"371",
            "LT"=>"370",
            "AT"=> "43",
            "HU"=> "36",
            "CZ"=>"420",
            "RO"=> "40",
            "DE"=> "49",
            "EE"=>"372",
            "FR"=> "33",
            "BE"=> "32",
            "ES"=> "34",
            "AL"=>"355",
            "XK"=>"377",
            "VN"=>"84",
            "NG"=>"234");

        $html    ='<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                        <tr >
                            <td width="20px">#</td>
                            <td >Submit ID</td>
                            <td >Order Date</td>
                            <td >State</td>
                            <td >Phone</td>
                            <td >Filtered Phone</td>
                            <td >Filter status</td>
                            <td >SMS status</td>
                            <td >HLR status</td>
                            <td >Twillio status</td>
                            <td >OMG status</td>
                        </tr>
                    </thead>
                    <tbody id="tabela">';

        $counter        = 0;
        $showColor      = "";
        $countSent      = 0;
        $countDeliv     = 0;
        $countError     = 0;
        $hlrcountDeliv  = 0;
        $hlrcountError  = 0;
        $twcountDeliv   = 0;
        $twcountError   = 0;

        foreach ($_data as $row){

            $filteredPhone = 0;
            //$filteredPhone = $view['actions']->render(new \Symfony\Component\HttpKernel\Controller\ControllerReference('AppBundle:Helpers:cleanMob', array('phoneNo'  => $row['phone'], 'state'  => $row['state'] )));

            $filteredPhone = $_sms->cleanMobile($row['phone'], $row['state'])->getContent();
            $filterStatus = "Rejected";
            $endFiltered = "none";
            if (strlen($filteredPhone) > 4){
                //$endFiltered = '+'.$areaCodes[$row["state"]].''.$filteredPhone;
                $endFiltered = '+'.$areaCodes[$row["state"]].''.$filteredPhone;
                $filterStatus = "Filtered";
            }

            $statusText = "";
            if ($row['delivered'] == 2) {
                $statusText = "Delivered";
                $countDeliv++;
            }  else {
                $statusText = "Undelivered";
                $countError++;
            }
            $hlrstatusText = "";
            if ($row['hlrstatus'] == 1) {
                $hlrstatusText = "Delivered";
                $hlrcountDeliv++;
            }  else {
                $hlrstatusText = "Undelivered";
                $hlrcountError++;
            }

            if ($row['twstatus'] == 1) {
                $twstatusText = "Delivered";
                $twcountDeliv++;
            }  else {
                $twstatusText = "Undelivered";
                $twcountError++;
            }

            if ($row['delivered'] == 2){
                $sshowColor = "greeny";
            } else {
                $sshowColor = "";
            }

            if ($row['hlrstatus'] == 1){
                $hshowColor = "greeny";
            } else {
                $hshowColor = "";
            }

            if ($row['delivered'] == 2 || $row['hlrstatus'] == 1 || $row['twstatus'] == 1){
                $oshowColor = "greeny";
                $_omgstatus = "OK";
            } else {
                $oshowColor = "";
                $_omgstatus = "NOT OK";
            }
            if ($row['twstatus'] == 1){
                $tshowColor = "greeny";
            } else {
                $tshowColor = "";
            }

            $custPhone = $row['phone'];

            if ($custPhone == "123456789" || $custPhone == "12345678" || $custPhone == "0123456789" || $custPhone == "012345678"){

                $endFiltered = "TEST";
            }

            $counter++;
             $html .= '<tr class="'.$oshowColor.'" style="margin-top:1px; cursor:pointer;">
                        <td class="'.$oshowColor.'">'.$counter.'</td>
                        <td class="'.$oshowColor.'">'.$row['submitId'].'</td>
                        <td class="'.$oshowColor.'">'.$row['orderDate'].'</td>
                        <td class="'.$oshowColor.'">'.$row['state'].'</td>
                        <td class="'.$oshowColor.'">'.$row['phone'].'</td>
                        <td class="'.$oshowColor.'">'.$endFiltered.'</td>
                        <td class="'.$oshowColor.'">'.$filterStatus.'</td>
                        <td class="'.$sshowColor.'">'.$statusText.'</td>
                        <td class="'.$hshowColor.'">'.$hlrstatusText.'</td>
                        <td class="'.$tshowColor.'">'.$twstatusText.'</td>
                        <td class="'.$oshowColor.'">'.$_omgstatus.'</td>
                      </tr>';
        }

        $html   .= '</tbody>
                    <tfoot>
                    <tr >
                        <td colspan="4"><strong>SMS delivered : '. $countDeliv .' | SMS Undelivered: '.$countError.' | HLR delivered: '.$hlrcountDeliv.' | HLR undelivered: '.$hlrcountError.' </strong></td>
                        <td></td>
                        <td ></td>
                        <td ></td>
                        <td ></td>
                        <td ></td>
                        <td ></td>
                        <td ></td>
                    
                    </tr>
                    </tfoot>
                    </table>';

        $response = $this->render('report/phoneStatus.html.twig', array(
                '_html'     => $html,
                '_states'   => $_states,
                'from'      => $from,
                'to'        => $to,
                'title'     => $title)
        );
        return $response;
    }


    public function twillioStatusAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title    ='Phone statuses Report';
        $conn     = $this->get('database_connection');
        $_omg     = new OMG($conn);
        $_reports = new Report($conn);

        $yesterday = Date("Y-m-d", strtotime('-1 days'));

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $state     = $queryArr['state'];
        $lresponse = $queryArr['lresponse'];
        $cresponse = $queryArr['cresponse'];
        $num       = $queryArr['ordNum'];
        $from      = $queryArr['from'];
        $to        = $queryArr['to'];
        $phone     = $queryArr['phone'];

       // print_r($queryStr);die();
        if(isset($state) && !empty($state))           { $sQ = " and orders.state = '$state' ";                     } else { $sQ = ""; }
        if(isset($lresponse) && !empty($lresponse))   { $lrQ = " and sms_twillio.lookup_response = '$lresponse' "; } else { $lrQ = ""; }
        if(isset($cresponse) && !empty($cresponse))   { $crQ = " and sms_twillio.call_response = '$cresponse' ";   } else { $crQ = ""; }
        if(isset($num) && !empty($num))               { $nQ = "ORDER BY id DESC LIMIT $num";                       } else { $nQ = ""; }
        if(isset($from) && !empty($from))             { $dfQ = " and DATE(orders.orderdate) >= '$from' ";          } else { $dfQ = " and DATE(orders.orderdate) >= '$yesterday' "; $from = $yesterday;}
        if(isset($to) && !empty($to))                 { $dtQ = " and DATE(orders.orderdate) <= '$to' ";            } else { $dtQ = " and DATE(orders.orderdate) <= '$yesterday' "; $to = $yesterday; }
        if(isset($phone) && !empty($phone))           { $phoneQ   = " AND orders.telephone LIKE '%$phone%' ";      } else { $phoneQ = ""; }

        $Query = " 1 ";   //default
        $Query .= $sQ;    //sender
        $Query .= $lrQ;   //sms status
        $Query .= $crQ;   //hlr status
        $Query .= $dfQ;   //date from
        $Query .= $dtQ;   //date to
        $Query .= $phoneQ; //phone criteria
        $Query .= $nQ;    //Order Num

        $_states    = $_omg->getStates();
        $_data      = $_reports->getTwillioStatus($Query);

        $html = ' <table style="width: 1300px" class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr>
                      <td width="20px">#</td>
                      <td >Submit ID</td>
                      <td >Order Date</td>
                      <td >State</td>
                      <td >Phone</td>
                      <td >Filtered Phone</td>
                      <td >Lookup response</td>
                      <td >Call Response</td>
                    </tr>
                   </thead>
                   <tbody id="tabela">';
        $counter    = 0;
        $showColor  = "";
        $oshowColor = "";
        $countSent  = 0;
        $countDeliv = 0;
        $countError = 0;
        $lcountDeliv= 0;
        $lcountError= 0;

        foreach ($_data as $row){
            $endFiltered = $row['filteredPhone'];

            if ($row['callr'] == "completed" || $row['callr'] == "no-answer" || $row['callr'] == "busy" || $row['callr'] == "ringing") {
                $countDeliv++;
            }  else {
                $countError++;
            }
            $lstatusText = "";
            if ($row['lookup'] == "landline") {
                $lstatusText = "landline";
                $lcountDeliv++;
            }  else if ($row['lookup'] == "mobile"){
                $lstatusText = "mobile";
                $lcountError++;
            }  else {
                $lstatusText = "Error";
                $lcountError++;
            }

            if ($row['callr'] == "completed" || $row['callr'] == "no-answer" || $row['callr'] == "busy" || $row['callr'] == "ringing"){
                $sshowColor = "greeny";
            } else {
                $sshowColor = "";
            }

            if ($row['lookup'] == "landline"){
                $lshowColor = "greeny";
            } else {
                $lshowColor = "";
            }

            $custPhone = $row['phone'];

            if ($custPhone == "123456789" || $custPhone == "12345678" || $custPhone == "0123456789" || $custPhone == "012345678"){

                $endFiltered = "TEST";
            }

            $counter++;
            $html .= '<tr class="'.$oshowColor.'" style="margin-top:1px; cursor:pointer;">
                        <td class="'.$oshowColor.'">'.$counter.'</td>
                        <td class="'.$oshowColor.'">'.$row['submitId'].'</td>
                        <td class="'.$oshowColor.'">'.$row['orderDate'].'</td>
                        <td class="'.$oshowColor.'">'.$row['state'].'</td>
                        <td class="'.$oshowColor.'">'.$row['phone'].'</td>
                        <td class="'.$oshowColor.'">'.$endFiltered.'</td>
                        <td class="'.$lshowColor.'">'.$lstatusText.'</td>
                        <td class="'.$sshowColor .'">' . $row['callr'] . '</td>
                      </tr>';
        }

        $html .= '</tbody>
                    <tfoot>
                    <tr >
                        <td colspan="4"><strong>Call OK : '. $countDeliv .' | Call not OK: '.$countError .' | Lookup OK: '.$lcountDeliv .' | Lookup not OK: '. $lcountError.'</strong></td>
                        <td></td>
                        <td ></td>
                        <td ></td>
                        <td ></td>
                        <td ></td>
            
                    </tr>
                    </tfoot>
                    </table>';

        $response = $this->render('report/twillioStatus.html.twig', array(
                '_html'   => $html,
                '_states' => $_states,
                'from'    => $from,
                'to'      => $to,
                'title'   => $title)
        );
        return $response;
    }


    public function smsAnalyticsAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }


        $title     = 'SMS analytics';
        $conn      = $this->get('database_connection');
        $_omg      = new OMG($conn);
        $_reports  = new Report($conn);
        $_settings = new Settings($conn);

        $yesterday = Date("Y-m-d", strtotime('-1 days'));

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $sender     = $queryArr['sender'];
        $status     = $queryArr['status'];
        $_state     = $queryArr['state'];
        $_smstype   = $queryArr['smstype'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        if (isset($_state) && !empty($_state)) {
            $scQ = " and smsMessages_ID.state_id = '$_state' ";
        } else {
            $scQ = "";
        }

        if (isset($sender) && !empty($sender)) {
            $sQ = " and smsMessages.from LIKE '$sender' ";
        } else {
            $sQ = "";
        }
        if (isset($status) && !empty($status)) {

            if ($status == 2) {
                $stQ = " and LEFT(smsMessages.response,3) != '-3,' and LEFT(smsMessages.response,3) != '-20' ";
            } else {
                $stQ = " and status = '$status' ";
            }
        } else {
            $stQ = "";
        }
        if (isset($_smstype) && !empty($_smstype)) {
            $_smsQ = " AND LEFT(messageId,3) = '$_smstype'";
        } else {
            $_smsQ = "";
        }
        if (isset($from) && !empty($from)) {
            $dfQ = " and DATE(dateSent) >= '$from' ";
        } else {
            $from = $yesterday;
            $dfQ = " and DATE(dateSent) >= '$from' ";
        }
        if (isset($to) && !empty($to)) {
            $dtQ = " and DATE(dateSent) <= '$to' ";
        } else {
            $to  = $yesterday;
            $dtQ = " and DATE(dateSent) <= '$to' ";
        }

        $Query = " 1 ";     //default
        $Query .= $sQ;      //sender
        $Query .= $scQ;     //state
        $Query .= $stQ;     //status
        $Query .= $_smsQ;   //sms type
        $Query .= $dfQ;     //date from
        $Query .= $dtQ;     //date to
        //print_r($Query);die();

        $_states    = $_omg->getCompanyInfo();
        $_prices    = $_settings->getSMSprices();
        $_data      = $_reports->getSentSMSCount("*",$Query);
        $_stateArr  = $_omg ->getStates();


        $stateArr = Array();
        foreach ($_states AS $eachs) {
            $stateArr[$eachs['smsSender']] = $eachs['code2'];
        }

        $priceArr = Array();
        foreach ($_prices AS $eachp) {
            $priceArr[$eachp['state']] = $eachp['price'];
        }

        $html = '<table style="width: 1300px" class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr >
                      <td width="20px">#</td>
                      <td >State</td>
                      <td >Sender</td>
                      <td >Number of Sendout</td>
                      <td >Total Num. of SMS</td>
                      <td >Price (€)</td>
                    </tr>
                   </thead>
                   <tbody id="tabela">';

        $counter    = 0;
        $totalSMS   = 0;
        $showColor  = "";
        $totalPrice = 0.00;
        $totalMessages   = 0;

        foreach ($_data as $row){
            $counter++;
            $totalSMS = $totalSMS + $row['broj'];

            $quantity       = $row['quantity'];
            $totalMessages  = $totalMessages + $quantity;

            $statePrice = $quantity * $priceArr[$row['code2']];
            $totalPrice = $totalPrice + $statePrice;

            $html.= '<tr style="margin-top:1px; cursor:pointer;">
            <td class="'.$showColor.'">'.$counter.'</td>
            <td class="'.$showColor.'">'.$row['title'].'</td>
            <td class="'.$showColor.'">'.$row['sender'].'</td>
            <td class="'.$showColor.'">'.$row['broj'].'</td>
            <td class="'.$showColor.'">'.$quantity.'</td>
            <td class="'.$showColor.'">'.$statePrice.'</td>
          </tr>';
        }

        $html.= '</tbody>
                <tfoot>
                    <td colspan = "2"></td>
                    <td style="text-align: right;"><strong>TOTAL:  </strong></td>
                    <td style="text-align: center;"><strong>' . $totalSMS  . '</strong></td>
                    <td style="text-align: center;"><strong>' . $totalMessages  . '</strong></td>
                    <td style="text-align: center;"><strong>' .  $totalPrice . '</strong></td>
            </tfoot>
            </table>';

        //print_r($html);die();
        $response = $this->render('report/smsAnalytics.html.twig', array(
                '_html' => $html,
                '_states' => $_states,
                '_stateArr' => $_stateArr,
                'from' => $from,
                'to' =>$to,
                'title'=>$title)
        );
        return $response;
    }


    public function TmRequestAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title     = 'TM request';
        $conn      = $this->get('database_connection');
        $_omg      = new OMG($conn);
        $_reports  = new Report($conn);
        $_settings = new Settings($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state    = $queryArr['country'];
        $product  = $queryArr['product_id'];
        $status   = $queryArr['status'];

        if(isset($state) && !empty($state))     { $scQ = " AND productDescription.state = '$state' ";         } else { $scQ = ""; }
        if(isset($product) && !empty($product)) { $pcQ = " AND products.id = " . $product . " ";    } else {  $pcQ = ""; }
        if(isset($status) && !empty($status))   {
            if ($_GET['status'] == 0 || $_GET['status'] == 1) {

                $status = $_GET['status'];
                $stQ = " AND  phone_order_TM.TMPullBack = " . $status . " ";
            }
        } else {  $stQ = ""; }

        $Query = " ";       //default
        $Query .= $scQ;     //state
        $Query .= $pcQ;     //product from
        $Query .= $stQ;     //status from

        $_states   = $_omg->getStates();
        $_products = $_omg->getProductList();
        $_data     = $_reports->getTmRequestReport($Query);

        $yearForSignature = date(Y);

        $html = '<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr >
                        <td width="20px">#</td>
                        <td >TM ID</td>
                        <td >Product</td>
                        <td >State</td>
                        <td >Sent time</td>
                        <td >Get Time</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';
        $counter    = 0;
        foreach ($_data as $row){
            $counter++;
            $showColor = "";
            if ($row['status'] == "1") {
                $showColor = "greenLine";
            }

            $html .= '<tr style="margin-top:1px; cursor:pointer;">
                <td class="'. $showColor .'">'.  $counter . '</td>
                <td class="'. $showColor .'">'. $row['tm_id'].'</td>
                <td class="'. $showColor .'">'. $row['product_title'].'</td>
                <td class="'. $showColor .'">'. $row['state'].'</td>
                <td class="'. $showColor .'">'. $row['sentTime'].'</td>
                <td class="'. $showColor .'">'. $row['getTime'].'</td>
            </tr>';
        }
        $html .= '</tbody>
             </table>';

        $response = $this->render('report/TmRequest.html.twig', array(
                '_html'     => $html,
                '_states'   => $_states,
                '_products' => $_products,
                'signDate'  => $yearForSignature,
                'title'     => $title)
        );
        return $response;
    }


    public function callTrackAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }


        $title     = 'Call track';
        $conn      = $this->get('database_connection');
        $_omg      = new OMG($conn);
        $_reports  = new Report($conn);
        $_settings = new Settings($conn);

        $todayDate = Date("Y-m-d");

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $country    = $queryArr['country'];
        $operator   = $queryArr['operator'];
        $callcenter = $queryArr['callcenter'];
        $answered   = $queryArr['answered'];
        $ended      = $queryArr['ended'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];

        if(isset($country) && !empty($country))       { $cQ  = " AND phone_order_users.state = '$country'";           } else { $cQ = ""; }
        if(isset($operator) && !empty($operator))     { $oQ  = " AND phone_order_users.username = '$operator'";       } else { $oQ = ""; }
        if(isset($callcenter) && !empty($callcenter) ) { $ccQ = " AND phone_order_callcenter.id = '$callcenter'";      } else { $ccQ = ""; }
        if(isset($answered) && !empty($answered) || $answered ==='0')  { $aQ  = " AND phone_order_tracker.callUp = '$answered'";       } else { $aQ = "";}
        if(isset($ended) && !empty($ended) || $ended ==='0')           { $eQ  = " AND phone_order_tracker.callEnd = '$ended'";         } else { $eQ = ""; }
        if(isset($from) && !empty($from))             { $dfQ = " and DATE(phone_order_tracker.opentime) >= '$from' "; } else { $from =  $from = date('Y-m-01'); $dfQ = " and DATE(phone_order_tracker.opentime) >= '$from' "; }
        if(isset($to) && !empty($to))                 { $dtQ = " and DATE(phone_order_tracker.opentime) <= '$to' ";   } else { $to = $todayDate;  $dtQ = " and DATE(phone_order_tracker.opentime) <= '$to' ";}

        $query  = "";
        $query .= $cQ;
        $query .= $oQ;
        $query .= $ccQ;
        $query .= $aQ;
        $query .= $eQ;
        $query .= $dfQ;
        $query .= $dtQ;

        $_centers        = $_settings->getCallCenterList();
        $_states        = $_omg->getStates();
        $_users          = $_settings->getOperators();
        $callTrackTable = $_settings->callTrackInfo($query);

        $html = '<table class="dayView" id="example">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>Operator name</td>
                        <td>State</td>
                        <td>Call center</td>
                        <td>Panel opened</td>
                        <td>Call answered</td>
                        <td>Call ended</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';
        $counter = 0;
        foreach ($callTrackTable as $table_row) {
            $tabOdd = "";
            $statusInd = "";
            $counter++;
            if ($counter % 2 != 0) {
                $tabOdd = "style='background-color:#eee'";
            }

            $call_status_answer = "";
            $call_status_ended = "";
            $color1 = "";
            $color2 = "";

            if ($table_row["answered"] == 0) {
                $call_status_answer = "NO";
                $color1 = "style = background-color:#FF8888;";
            }

            if ($table_row["answered"] == 1) {
                $call_status_answer = "YES";
                $color1 = "style = background-color:#97e0ae;";
            }

            if ($table_row["ended"] == 0) {
                $call_status_ended = "NO";
                $color2 = "style = background-color:#FF8888;";
            }

            if ($table_row["ended"] == 1) {
                $call_status_ended = "YES";
                $color2 = "style = background-color:#97e0ae;";
            }

            $html .= '<tr id="r' . $counter . '">';
            $html .= '<td ' . $tabOdd . '>' . $counter . '</td>
                         <td ' . $tabOdd . '>
                             <a href="inspectleturl' . $table_row['sId'] . '?pn=1" target="_blank">' . $table_row["operatorName"] . '</a>
                         </td>
                         <td ' . $tabOdd . '>
                             ' . $table_row["state"] . '
                         </td>
                         <td ' . $tabOdd . '>
                             ' . $table_row["callcenter"] . '
                         </td>
                         <td ' . $tabOdd . '>
                             ' . $table_row["opentime"] . '
                         </td>
                         <td ' . $color1 . '>
                             ' . $call_status_answer . '
                         </td>
                         <td ' . $color2 . '>
                            ' . $call_status_ended . '
                         </td>
                         ';
            $html .= '</tr>';
        }
        $html .= '</tbody>
            </table>';

        $response = $this->render('report/callTrack.html.twig', array(
                '_html'      => $html,
                '_states'    => $_states,
                '_centers'   => $_centers,
                '_users'     => $_users,
                'from'       => $from,
                'to'         => $to,
                'title'      =>$title)
        );
        return $response;
    }


    public function userLogsAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title     = 'User Logs Report';
        $conn      = $this->get('database_connection');
        $_omg      = new OMG($conn);
        $_reports  = new Report($conn);

        $today = date("Y-m-d");
        $yesterday = Date("Y-m-d", strtotime('-1 days'));

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state       = $queryArr['state'];
        $group       = $queryArr['opratorGroup'];
        $logoutType  = $queryArr['logoutType'];
        $loginFrom   = $queryArr['loginFrom'];
        $loginTo     = $queryArr['loginTo'];

        if(isset($state) && !empty($state))              { $stateQ = " and phone_order_users.state = '$state' ";                              } else { $stateQ = ""; }
        if(isset($group) && !empty($group))              { $groupQ   = " AND phone_order_users.operatorGroup = '$group' ";                    } else {  $groupQ = ""; }
        if(isset($logoutType) && !empty($logoutType))    { $logoutTypeQ   = " AND phone_order_user_logs.logout_type_id = '$logoutType' ";     } else { $logoutTypeQ = ""; }
        if(isset($loginFrom) && !empty($loginFrom))      { $loginFromQ = " and DATE(phone_order_user_logs.datetime_login) >= '$loginFrom' ";  } else { $loginFromQ = " and DATE(phone_order_user_logs.datetime_login) >= '$yesterday' "; $loginFrom =$yesterday;}
        if(isset($loginTo) && !empty($loginTo))          { $loginToQ = " and DATE(phone_order_user_logs.datetime_login) >= '$loginTo' ";      } else { $loginToQ = " and DATE(phone_order_user_logs.datetime_login) >= '$today' "; $loginTo =$today; }

        $Query = " 1 ";          //default
        $Query .= $stateQ;       //state
        $Query .= $loginFromQ;   //login from datetime
        $Query .= $loginToQ;  //login to datetime
        $Query .= $groupQ;       //call centar group
        $Query .= $logoutTypeQ;  //logout type


        $_callCentres = $_reports->getCallCenterList();
        $_states      = $_omg->getStates();
        $_data        = $_reports->getUserLogsInformations($Query);

        $html ='<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr >
                        <td width="20px">#</td>
                        <td >State</td>
                        <td >Call Center</td>
                        <td >Full name</td>
                        <td >IP address</td>
                        <td >Login datetime</td>
                        <td >Logout datetime</td>
                        <td >Timestamp activity</td>
                        <td >Logout Type</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';


        $counter = 0;
        foreach ($_data as $row){
            $counter++;
    
            $html .= '<tr class="" style="margin-top:1px; cursor:pointer;">
                <td class="">' . $counter . '</td>
                <td class="">' . $row['state']. '</td>
                <td class="">' . $row['call_centar_group']. '</td>
                <td class="">' . $row['fullname']. '</td>
                <td class="">' . $row['ip_address']. '</td>
                <td class="">' . $row['datetime_login'] . '</td>
                <td class="">' . $row['datetime_logout'] . '</td>
                <td class="">' . $row['datetime_activity']. '</td>';

            if($row['logout_type_id'] == 0 ){
                $html .=   '<td class="greeny">Active</td>';
            } else if ($row['logout_type_id'] == 1){
                $html .=   '<td class="">Logout</td>';
            } else if($row['logout_type_id'] == 2) { 
                $html .= '<td class="redLine">Kicked from session</td>';
            }
        }
        $html .=  '</tbody>
                </table>';

        $response = $this->render('report/userLogs.html.twig', array(
                '_html'        => $html,
                '_states'      => $_states,
                '_callCentres' => $_callCentres,
                'loginFrom'    => $loginFrom,
                'loginTo'      => $loginTo,
                'title'        => $title)
        );
        return $response;
    }

    public function callersAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title     = 'Callers Report';
        $conn      = $this->get('database_connection');
        $_omg      = new OMG($conn);
        $_reports  = new Report($conn);

        $today = date("Y-m-d");
        $fisrtDay = date("Y-m-01");

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state      = $queryArr['state'];
        $from       = $queryArr['from'];
        $to         = $queryArr['to'];
        $ordNum     = $queryArr['ordNum'];
        $phone      = $queryArr['phone'];

        $dateFromQ = date('m-d', strtotime($from));
        $dateToQ = date('m-d', strtotime($to));



        if(isset($state) && !empty($state))    { $stateQ = " and phone_order_callers.state = '$state' ";   } else { $stateQ = ""; }
        if(isset($from) && !empty($from))      {
            //$dfQ = " and DAYOFYEAR(phone_order_callers.birthdate) >= '$dateFromQ' ";
        } else {
            $from = $fisrtDay;
            $dateFromQ = date('m-d', strtotime($fisrtDay));
            //$dfQ = " and DAYOFYEAR(phone_order_callers.birthdate) >= '$dateFromQ' ";
            $dfQ ='';
        }
        if(isset($to) && !empty($to))          {
           // $dtQ = " and DAYOFYEAR(phone_order_callers.birthdate) <= '$dateToQ' ";
        } else {
            $to = $fisrtDay;
            $dateToQ = date('m-d', strtotime($today));
            //$dtQ = " and DAYOFYEAR(phone_order_callers.birthdate) <= '$dateToQ' ";
            $dtQ ='';
        }
        if(isset($phone) && !empty($phone))           { $phoneQ = " AND phone_order_callers.phone LIKE '%$phone%' ";           } else { $phone ='';$phoneQ = ""; }
        if(isset($ordNum) && !empty($ordNum))        { $nQ = " ORDER BY id DESC LIMIT $ordNum ";    } else { $nQ = " ORDER BY id DESC LIMIT 500 "; $ordNum = 500; }


        $Query = " 1 ";      //default
        $Query .= $stateQ;   //state
        $Query .= $dfQ;      //from
        $Query .= $dtQ;      //to
        $Query .= $phoneQ;   //phone
        $Query .= $nQ;       //state

        $_states      = $_omg->getStates();
        $_data        = $_reports->getCallersinfo($Query);

        $html ='<table class="dayView compact" id="example">
                    <thead style="cursor:pointer;">
                    <tr >
                        <td width="20px">#</td>
                        <td >State</td>
                        <td >Name</td>
                        <td >Surname</td>
                        <td >Address</td>
                        <td >HouseNo</td>
                        <td >Postal</td>
                        <td >City</td>
                        <td >Phone</td>
                        <td >Email</td>
                        <td >Sex</td>
                        <td >Birthdate</td>
                        <td >Years</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';


        $counter = 0;
        foreach ($_data as $row){
            $counter++;

            $html .= '<tr class="" style="margin-top:1px; cursor:pointer;">
                <td class="">' . $counter . '</td>
                <td class="">' . $row['state']. '</td>
                <td class="">' . $row['name']. '</td>
                <td class="">' . $row['surname']. '</td>
                <td class="">' . $row['address']. '</td>
                <td class="">' . $row['houseno'] . '</td>
                <td class="">' . $row['postal'] . '</td>
                <td class="">' . $row['city']. '</td>
                <td class="">' . $row['phone']. '</td>
                <td class="">' . $row['mail']. '</td>
                <td class="">' . $row['sex']. '</td>
                <td class="">' . $row['birthdate']. '</td>
                <td class="">' . $row['years']. '</td></tr>';

        }
        $html .=  '</tbody>
                </table>';

        $response = $this->render('report/callers.html.twig', array(
                '_html'        => $html,
                '_states'      => $_states,
                'title' => $title,
                'from'  => $from,
                'to'   => $to
                )
        );
        return $response;
    }
}
?>