<?php

namespace AppBundle\Controller;

use AppBundle\Controller\LanguagesHelperController;

use AppBundle\Entity\Main;
use AppBundle\Entity\Outbound;
use AppBundle\Entity\Settings;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use AppBundle\Controller\LanguagesHelperController;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\OMG;
use AppBundle\Entity\SMS;




class LanguagesOutController extends Controller
{

    private function checkUserPrivileges(){

        $_main      = new Main();
        $loggedIn = $_main->checkUserIsLoggedIn();
        if($loggedIn == true){
            $checkUser  = $_main->checkPrivileges();
            
            if ($checkUser == false){
                // return $this->redirect('./login?status=3');
                return $this->redirectToRoute('login', array('status'=>'2'));
            }
            
        } else {
            return $this->redirectToRoute('login', array('status'=>'3'));
        }
    }

    private function checkifisadmin(){
        $allowedAdmins = array(
            20  => '',
            14  => '',
            179 => '',
            176 => '',
            24  => ''
        );
        
        $_main      = new Main();
        $loggedIn = $_main->checkUserIsLoggedIn();
        if($loggedIn == true){
            $checkUser  = $_main->checkBorisRodaMartiZeljka($allowedAdmins);
            if ($checkUser == false){
                // return $this->redirect('./login?status=3');
                return $this->redirectToRoute('login', array('status'=>'2'));
            }
        } else {
            return $this->redirectToRoute('login', array('status'=>'3'));
        }
    }

    public function indexAction($state)
    {

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Out Panel -'. $state;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];

        $operatorId = $user['ouid'];
        $data = $_outbound->getOutboundList($state);

        $langHelp = new LanguagesHelperController();
        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime']);
        
        $html = '<table class="new-call-requests">
                <thead>
                    <tr >
                        <th>ID</th>
                        <th>Datetime</th>
                        <th>Phone</th>
                        <th>Name and Place</th>
                        <th>Flow</th>
                        <th>Product</th>
                        <th>Price 1x</th>
                        <th>Last Call</th>
                        <th>Status</th>
                        <th colspan="7">Flag</th>
                    </tr>
                </thead>
                <tbody>';

        $counter = 0;
        $splitTypes = array(
            1=>'Type A',
            2=>'Type B'
        );

        $statuses   = Array(0=>"TO BE CALLED",1=>"ANSWERED",2=>"BUSY",3=>"NOT AVAILABLE",9=>"POSTPONED",11=>"CALLING");
        $callstatus = "NO CALL";

        //print_r($data);
        foreach ($data AS $row){
           
            // if($row['type'] == 9 ){ continue; }
            if($row['type'] == 10 ){ continue; }
            $types      = Array(1=>"Adcombo-Call",2=>"Canceled User",3=>$row['quantity']."x order - upsell",4=>"Verify OMG",5=>"Form Fill Brake",6=>"Order Fill Brake",7=>"Reorder Call",8=>"Bulk Call",9=>"Undecided Call",10=>"MailReorder Call",11=>"SMS Link",12=>"Undecided presell",13=>"RE SMS Link");
            $callpanel  = "call";

            $submitDate = strtotime($row['submitDate']);
            $timeAdd10  = $submitDate + 600;
            $timeAdd50  = $submitDate + 3000;
            //$timeAdd3d  = $submitDate + 39600; //11 sati razmaka za order fill brake
            $timeAdd4d  = $submitDate + 345600;

            $timeNow    = strtotime('now');

            $upsellLeftTimestamp = $timeAdd50 - $timeNow;

            $upsellLeftMin  = Date('i', $upsellLeftTimestamp);
            $upsellLeftSec  = Date('s', $upsellLeftTimestamp);

            $timeInfo       = $row['tocall_time'];

            $toCallStamp    = strtotime($timeInfo);
            $hideButton     = false;
           // print_r($row);
            if ($timeNow<$toCallStamp){
                $hideButton = true;
            }
            
            $hourLess       = $toCallStamp - 3600;
            if ($row['status'] == 2 && $timeNow<$hourLess){
                $hideButton = true;
                //print_r($row);
               
            }

            $changeStyle  = "";
            if($row['type'] == 3 && ($timeAdd10 > $timeNow OR $timeAdd50 < $timeNow)){ continue; }
            if($row['type'] == 6 && ($timeAdd10 > $timeNow OR $timeAdd4d < $timeNow)){ continue; }
            if($row['type'] == 7 && ($timeAdd4d < $timeNow)){ continue; }
            if($row['type'] == 8 && ($timeAdd4d < $timeNow)){ continue; }
            if($row['type'] == 9 && ($toCallStamp > $timeNow OR $timeAdd4d < $timeNow)){ continue; }
            if($row['type'] == 10 && ($timeAdd10 > $timeNow OR $timeAdd4d < $timeNow)){ continue; }
            if($row['type'] == 11 && ($timeAdd10 > $timeNow OR $timeAdd4d < $timeNow)){ continue; }
            if($row['type'] == 13 && ($timeAdd10 > $timeNow OR $timeAdd4d < $timeNow)){ continue; }

            if ($row['called_time'] != ""){
                $callstatus = $row['called_time'];
            } else {
                $callstatus = "NO CALL";
            }

            if (($row['status'] == 0 || $row['status'] == 1 || $row['status'] == 2 || $row['status'] == 3 || $row['status'] == 11) && 
                ($row['callCount'] < 3 || ($row['callCount'] < 11 && ($row['state']=='BA' || $row['state']=='RS'))) || 
                $row['status'] == 9) {

               // print_r($row);
                $styleOver = '';


                $showCount = "";
                if ($row['callCount'] > 0) {
                    $showCount = $row['callCount'];
                }

                if ($row['type'] == "1"){
                    $callpanel = "call";
                    $styleOver = 'style="background:#fff;"';
                    if ($row['productID'] == 3 && $row['state'] =='BA'){
                        $callpanel = "callt2";
                        //$styleOver = 'style="background:#a0a;"';
                    }
                    if ($row['productID'] == 248 && $row['state'] =='HR'){
                        $callpanel = "callt4";
                        //$styleOver = 'style="background:#a0a;"';
                    }

                    if ($row['productID'] == 200 && $row['state'] = 'BA'){
                        $callpanel = "callt3";
                        //$styleOver = 'style="background:#a0a;"';
                    }

                } else if ($row['type'] == "3") {
                    $callpanel = "call2";
                    $styleOver = 'style="background:#f88;"';
                    $timeInfo = $upsellLeftMin . " min " . $upsellLeftSec . " sec left to call!";
                    $changeStyle = 'style="color:#fff"';
                } else if ($row['type'] == "2") {
                    $callpanel = "call3";
                    $styleOver = 'style="background:#fc6;"';

                } else if ($row['type'] == "6") {
                    $callpanel = "call";
                    $styleOver = 'style="background:#ff0;"';
                    
                    if ($row['splitType'] == 2 && $row['productID'] == 200 && $row['state'] = 'BA'){
                        $callpanel = "callt3";
                        //$styleOver = 'style="background:#a0a;"';
                    }
                    if ($row['splitType'] == 2 && $row['productID'] == 248 && $row['state'] = 'HR'){
                        $callpanel = "callt4";
                        //$styleOver = 'style="background:#a0a;"';
                    }

                } else if ($row['type'] == "7") {
                    $callpanel = "call4";
                    $styleOver = 'style="background:#fc6;"';

                } else if ($row['type'] == "8") {
                    $callpanel = "call5";
                    $styleOver = 'style="background:#fc6;"';

                } else if ($row['type'] == "9") {
                    $callpanel = "call6";
                    $styleOver = 'style="background:#fc6;"';

                } else if ($row['type'] == "10") {
                    $callpanel = "call7";
                    $styleOver = 'style="background:#fc6;"';

                } else if ($row['type'] == "11") {
                    $callpanel = "call8";
                    $styleOver = 'style="background:#fc6;"';

                } else if ($row['type'] == "12"){
                    $callpanel = "call6";
                    $styleOver = 'style="background:#fc6;"';

                } else if ($row['type'] == "13"){
                    $callpanel = "call8";
                    $styleOver = 'style="background:#fc6;"';

                }

                if ($row['status'] == 1 || $row['status'] == 11) {
                    $styleOver = 'style="background:#cfc;"';
                }


                $counter++;
                $redirectToCallPanel = $this->generateUrl($callpanel, array('state' => $state, 'userId' =>$row['id']), true);
                //print_r($redirectToCallPanel);die();
                //print_r($redirectToPanel);die();
                $html .= '<tr id="r' . $counter . '" ' . $styleOver . '>
                            <td ' . $styleOver . '>' . $counter . '<input type="hidden" id="operator" value="' . $operatorId . '"></td>
                            <td ' . $styleOver . '>' . $row["submitDate"] . '</td>
                            <td ' . $styleOver . '>' . $row["phone"] . '</td>
                            <td ' . $styleOver . '><b>' . $row["name"] . '</b></td>
                            <td ' . $styleOver . '>' . $splitTypes[$row["splitType"]] . '</td>
                            <td ' . $styleOver . '>' . $row["title"] . '</td>
                            <td ' . $styleOver . '>' . $row["price"] . '</td>
                            <td ' . $styleOver . '>
                                ' . $callstatus . '
                                <span style="color:#c00;margin-left: 10px;background: #fd0;"> ' . $showCount . '</span><br>
                                <span class="date-time-initial-call" style="color:#000;">' . $row["comment"] . '</span>
                            </td>
                            <td ' . $styleOver . '>' . $statuses[$row["status"]] . '<br>
                                <span class="date-time-initial-call" ' . $changeStyle . '>' . $timeInfo . '</span>
                            </td>
                            <td ' . $styleOver . '>' . $types[$row["type"]] . '</td>
                            <td ' . $styleOver . '>';
                if ($row['status'] != 1 && $row['status'] != 11 && $hideButton == false) {
//                    $html .= '<button class="green-success-btn btn"   onclick="changeFlag(' . $row["id"] . ',11);window.open( "' . $callpanel . '?userId=' . $row["id"] . '", "New tab", "" )">Call</button>';
                    $html .= '<button class="green-success-btn btn"   onclick="changeFlag(' . $row["id"] . ',11);window.open(\''.$redirectToCallPanel.'\' , \'_blank\', \'\' )">Call</button>';
                }
                $html .= ' </td>
                            <td ' . $styleOver . '>';

                if ($row['status'] != 1 && $row['status'] != 11 && $hideButton == false) {
                    $html .= '<button class="red-alert-btn btn" onclick="changeFlag(' . $row["id"] . ',14,\'r' . $counter .'\');">Remove</button>';
                } else {
                    $html .= '<span style="font-size:10px!important;">Last Call:' . $row["imeOperatora"] . '</style>';
                }

                $html .= ' </td>
                        </tr>';
            }
        }
        $html .= ' </tbody>
            </table>';

        $icon = 'phoneOut-';
                    //print_r($korisnik);
        return $this->render('outbound/index.html.twig', array(

            'content' => $content,
            'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'korisnik' => $korisnik,
            '_html' =>$html,
            'icon' => $icon,
            'user' =>$user,
            'stateFromSession' =>$stateFromSession

            ));
    }


    public function callAction($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
       
        $operatorId = $user['ouid'];
        
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];
        
        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );
    
        

        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);
        if(isset($postdata['rpdID'])){
            $rpdID = $postdata['rpdID'];
        } else {
            $rpdID = "";
        }
      

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $one    = $data['price'];
        $two    = (float)$data['price'] + (float)$data['upsellPrice'];
        $three  = (float)$data['price'] + ((float)$data['upsellPrice'] * 2);

        $outType = $data['outType'];

        $panelType      = "adcombo";
        $callType = array(
            'type' => $panelType,
            'upsellPrice'=> $upsellPrice
        );
       //print_r($data);die();

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call.html.twig', array(

            'content' => $content,
            'data'    => $data,
//          'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'one' => $one,
            'two' => $two ,
            'three' => $three,
            'workingHours' => $workingHours,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'outType' => $outType,
            'rpdID'=>$rpdID,
            'user' =>$user,

        ));

    }
    public function calltAction($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutTHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];

        $operatorId = $user['ouid'];

        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call  - '. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );



        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);
        if(isset($postdata['rpdID'])){
            $rpdID = $postdata['rpdID'];
        } else {
            $rpdID = "";
        }

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $one    = $data['price'];
        $two    = (float)$data['price'] + (float)$data['upsellPrice'];
        $three  = (float)$data['price'] + ((float)$data['upsellPrice'] * 2);

        $outType = $data['outType'];

        $panelType      = "adcombo";
        $callType = array(
            'type' => $panelType,
            'upsellPrice'=> $upsellPrice,
            'forTwo' =>  $forTwo,
            'forThree' =>  $forThree,
            'forFour' =>  $forFour
        );
        //print_r($data);die();

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/callt.html.twig', array(

            'content' => $content,
            'data'    => $data,
//          'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'one' => $one,
            'two' => $two ,
            'three' => $three,
            'workingHours' => $workingHours,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'outType' => $outType,
            'rpdID'=>$rpdID,
            'user' =>$user,

        ));

    }

    public function callvsAction($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutTHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];

        $operatorId = $user['ouid'];

        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call  - '. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );



        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);
        if(isset($postdata['rpdID'])){
            $rpdID = $postdata['rpdID'];
        } else {
            $rpdID = "";
        }

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $one    = $data['price'];
        $two    = (float)$data['price'] + (float)$data['upsellPrice'];
        $three  = (float)$data['price'] + ((float)$data['upsellPrice'] * 2);

        $outType = $data['outType'];

        $panelType      = "orderFillBrake";
        $callType = array(
            'type' => $panelType,
            'upsellPrice'=> $upsellPrice,
            'forTwo' =>  $forTwo,
            'forThree' =>  $forThree,
            'forFour' =>  $forFour
        );
        //print_r($data);die();

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/callvs.html.twig', array(

            'content' => $content,
            'data'    => $data,
//          'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'one' => $one,
            'two' => $two ,
            'three' => $three,
            'workingHours' => $workingHours,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'outType' => $outType,
            'rpdID'=>$rpdID,
            'user' =>$user,

        ));

    }
    public function callHRgsAction($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutTHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];

        $operatorId = $user['ouid'];

        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call  - '. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );



        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);
        if(isset($postdata['rpdID'])){
            $rpdID = $postdata['rpdID'];
        } else {
            $rpdID = "";
        }

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $one    = $data['price'];
        $two    = (float)$data['price'] + (float)$data['upsellPrice'];
        $three  = (float)$data['price'] + ((float)$data['upsellPrice'] * 2);

        $outType = $data['outType'];

        $panelType      = "orderFillBrake";
        $callType = array(
            'type' => $panelType,
            'upsellPrice'=> $upsellPrice,
            'forTwo' =>  $forTwo,
            'forThree' =>  $forThree,
            'forFour' =>  $forFour
        );
        //print_r(json_decode($data['postdata']));die();

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/callHRgs.html.twig', array(

            'content' => $content,
            'data'    => $data,
//          'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'one' => $one,
            'two' => $two ,
            'three' => $three,
            'workingHours' => $workingHours,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'outType' => $outType,
            'rpdID'=>$rpdID,
            'user' =>$user,

        ));

    }
    public function callt2Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutTHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];

        $operatorId = $user['ouid'];

        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call - '. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );



        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);
        if(isset($postdata['rpdID'])){
            $rpdID = $postdata['rpdID'];
        } else {
            $rpdID = "";
        }

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $one    = $data['price'];
        $two    = (float)$data['price'] + (float)$data['upsellPrice'];
        $three  = (float)$data['price'] + ((float)$data['upsellPrice'] * 2);

        $outType = $data['outType'];

        $panelType      = "adcombo";
        $callType = array(
            'type' => $panelType,
            'upsellPrice'=> $upsellPrice,
            'forTwo' =>  $forTwo,
            'forThree' =>  $forThree,
            'forFour' =>  $forFour
        );
        //print_r($data);die();

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/callt2.html.twig', array(

            'content' => $content,
            'data'    => $data,
//          'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'one' => $one,
            'two' => $two ,
            'three' => $three,
            'workingHours' => $workingHours,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'outType' => $outType,
            'rpdID'=>$rpdID,
            'user' =>$user,

        ));

    }

    public function call2Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();

        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call2 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );

        
        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
       

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        
        $postdata = json_decode($data['postdata'], true);
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $fPrice     = $data['formPrice'];
        $fUpsell    = $data['upsellPr'];
        $panelType  = "upsell";

        $actualPr = $fPrice;
        if ($data['quantity'] > 1){
            $actualPr = (float)$fPrice + ((float)$fUpsell * ((int)$data['quantity'] - 1));
        }

        $panelType      = "adcombo";
        $callType = array(
            'type'           => $panelType,
            'dataUpsellPrice'=> $fUpsell,
            'dataQuantity'   => $data['quantity'],
            'actualPr'       => $actualPr,
            'forTwo'         => $forTwo,
            'forThree'       => $forThree,
            'forFour'        => $forFour,
            
        );

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';

        return $this->render('outbound/call2.html.twig', array(

            'content' => $content,
            'data'    => $data,
            'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'user' =>$user,

        ));
    }

    public function call2tAction($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutTHelperController();

        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call2 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );


        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);


        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        //header('Content-Type: application/json; charset=utf-8');
        $postdata = json_decode($data['postdata'], true);
        //$postdata = json_decode($data['postdata'], false, 512, JSON_UNESCAPED_UNICODE);
        
       // print_r($postdata);die();
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $fPrice     = $data['formPrice'];
        $fUpsell    = $data['upsellPr'];
        $panelType  = "upsell";

        $actualPr = $fPrice;
        if ($data['quantity'] > 1){
            $actualPr = (float)$fPrice + ((float)$fUpsell * ((int)$data['quantity'] - 1));
        }

        $panelType      = "adcombo";
        $callType = array(
            'type'           => $panelType,
            'dataUpsellPrice'=> $fUpsell,
            'dataQuantity'   => $data['quantity'],
            'actualPr'       => $actualPr,
            'forTwo'         => $forTwo,
            'forThree'       => $forThree,
            'forFour'        => $forFour,

        );

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';

        return $this->render('outbound/call2t.html.twig', array(

            'content' => $content,
            'data'    => $data,
            'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'user' =>$user,

        ));
    }

    public function call3Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();

        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $userId = $queryArr['userId'];
        
        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call3 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );


        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);


        $customerName   = $data['name'];
        $callCount      = $data['callCount'];

        $submitId       = $data['submitID'];
        $productName    = $data['prTitle'];
        
        
        $panelType      = "cancel";

        $callType = array(
            'type'            => $panelType,
           
        );
        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call3.html.twig', array(
            'content' => $content,
            'data'    => $data,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'operatorId' => $operatorId,
            'customerName' => $customerName,
            'productName'=> $productName,
            'centerInfo' => $Info,
            'submitId' => $submitId,
            'callCount' =>$callCount,
            'icon' => $icon, 
            'panelType'=> $panelType,
            'user' =>$user,
           

        ));

       
    }
    public function callZeljkaAction($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_sms      = new SMS($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();
        $br = '036580377';
        $state='BA';

        $checj=$_sms->cleanMobile($br,$state);
        //print_r($checj);die();
        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call3 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );


        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);


        $customerName   = $data['name'];
        $callCount      = $data['callCount'];

        $submitId       = $data['submitID'];
        $productName    = $data['prTitle'];


        $panelType      = "cancel";

        $callType = array(
            'type'            => $panelType,

        );
        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/zeljka.html.twig', array(
            'content' => $content,
            'data'    => $data,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'operatorId' => $operatorId,
            'customerName' => $customerName,
            'productName'=> $productName,
            'centerInfo' => $Info,
            'submitId' => $submitId,
            'callCount' =>$callCount,
            'icon' => $icon,
            'panelType'=> $panelType,
            'user' =>$user,


        ));


    }

    public function call4Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_settings = new Settings($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];
        
        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call4 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );

       
        $data           = $_outbound->getOutboundRow($userId);
        $Info           = $langHelp->getCallCentarInfo($state);
        $productId      = $data['productID'];

        $setPrice       = $_settings->getCampaignPrices($productId, $state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $setPrice['upsellPrice'];
        $basePrice      = (float)$setPrice['price'];
        $doubleBasePrice= (float)$setPrice['price'] * 2;
        $forTwo         = ((float)$setPrice['price'] * 2) - ((float)$setPrice['price'] + (float)$setPrice['upsellPrice']);
        $forThree       = ((float)$setPrice['price'] * 3) - ((float)$setPrice['price'] + (2*(float)$setPrice['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$setPrice['price'] * 4) - ((float)$setPrice['price'] + (3*(float)$setPrice['upsellPrice'])) + $Info['postar'];

        $panelType      = "callpanel";

        $callType = array(
            'type'            => $panelType,
            'doubleBasePrice' => $doubleBasePrice,
            'basePrice'       => $basePrice,
            'forTwo'          => $forTwo,
            'forThree'        => $forThree,
            'forFour'         => $forFour,
            'upsellPrice'     => $upsellPrice
        );

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call4.html.twig', array(
            'content' => $content,
            'data'    => $data,
            'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'korisnik' => $korisnik,
            'setPrice' => $setPrice,
            'workingHours' =>$workingHours,
            'icon' => $icon,
            'postdata' =>$postdata,
            'user' =>$user,
        ));
    }

    public function call5Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_settings = new Settings($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();

        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $userId = $queryArr['userId'];
        
        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call5 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );

       
        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);

        $productId      = $data['productID'];

        $setPrice       = $_settings->getCampaignPrices($productId, $state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $setPrice['upsellPrice'];
        $basePrice      = (float)$setPrice['price'];
        $doubleBasePrice= (float)$setPrice['price'] * 2;
        $forTwo         = ((float)$setPrice['price'] * 2) - ((float)$setPrice['price'] + (float)$setPrice['upsellPrice']);
        $forThree       = ((float)$setPrice['price'] * 3) - ((float)$setPrice['price'] + (2*(float)$setPrice['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$setPrice['price'] * 4) - ((float)$setPrice['price'] + (3*(float)$setPrice['upsellPrice'])) + $Info['postar'];

        $panelType      = "BulkCall";
        $callType = array(
            'type'            => "bulk",
            'doubleBasePrice' => $doubleBasePrice,
            'basePrice'       => $basePrice,
            'forTwo'          => $forTwo,
            'forThree'        => $forThree,
            'forFour'         => $forFour,
            'upsellPrice'     => $upsellPrice
        );

        //print_r($setPrice);die();
        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call5.html.twig', array(

            'content' => $content,
            'data'    => $data,
            'doubleBasePrice' => $doubleBasePrice,
          
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'korisnik' => $korisnik,
            'setPrice' => $setPrice,
            'workingHours' =>$workingHours,
            'icon' => $icon,
            'postdata' =>$postdata,
            'user' =>$user,

        ));
    }

    public function call6Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call6 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );

       


        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);
      

        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $one    = $data['price'];
        $two    = (float)$data['price'] + (float)$data['upsellPrice'];
        $three  = (float)$data['price'] + ((float)$data['upsellPrice'] * 2);

        $panelType      = "undecided";
        $callType = array(
            'type' => $panelType,
            'upsellPrice'=> $upsellPrice
        );

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call6.html.twig', array(

            'content' => $content,
            'data'    => $data,
            'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'one' => $one,
            'two' => $two ,
            'three' => $three,
            'workingHours' => $workingHours,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'postdata' =>$postdata,
            'user' =>$user,

        ));
    }

    public function call7Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call7 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );

        


        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);



        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $basePrice      = (float)$data['price'];
        $doubleBasePrice= (float)$data['price'] * 2;
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $panelType      = "reordermail";


        $callType = array(
            'type'            => $panelType,
            'upsellPrice'     => $upsellPrice,
            'doubleBasePrice' => $doubleBasePrice,
            'basePrice'       => $basePrice,
            'forTwo'          => $forTwo,
            'forThree'        => $forThree,
            'forFour'         => $forFour,
            'workingHours'    => $workingHours


        );

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call7.html.twig', array(

            'content' => $content,
            'data'    => $data,
            'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'korisnik' => $korisnik,
            'workingHours'    => $workingHours,
            'icon' => $icon,
            'postdata' =>$postdata,
            'user' =>$user,

        ));
    }

    public function call8Action($state){


        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_settings = new Settings($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];
        $operatorId = $user['ouid'];
        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];


        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call8 -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );

        


        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);

        $productId      = $data['productID'];

        $setPrice       = $_settings->getCampaignPrices($productId, $state);


        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $basePrice      = (float)$data['price'];
        $doubleBasePrice= (float)$data['price'] * 2;
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $discount       = ceil($data['price'] - $setPrice['price']);
        $outType = $data['outType'];
        $panelType      = "SmsLink";
        $callType = array(
            'type'            => $panelType,
            'upsellPrice'     => $upsellPrice,
            'doubleBasePrice' => $doubleBasePrice,
            'basePrice'       => $basePrice,
            'forTwo'          => $forTwo,
            'forThree'        => $forThree,
            'forFour'         => $forFour,
            'discount' =>$discount

        );

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call8.html.twig', array(

            'content' => $content,
            'data'    => $data,
            'doubleBasePrice' => $doubleBasePrice,
            'setPrice' =>$setPrice,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'korisnik' => $korisnik,
            'discount' =>$discount,
            'workingHours'    => $workingHours,
            'icon' =>$icon,
            'postdata' =>$postdata,
            'outType' => $outType,
            'user' =>$user,

        ));
    }

    public function call9Action($state){

        $urlState = $state;

        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);
        $langHelp  = new LanguagesOutHelperController();


        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        $user = $_SESSION['phUser'];

        $operatorId = $user['ouid'];

        $privileges = $_SESSION['phUser']['role'];
        $stateFromSession = $_SESSION['phUser']['state'];

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $predefinedPhone = $queryArr['number'];
        $userId = $queryArr['userId'];

        if ($urlState != $stateFromSession){
            if (isset($urlState) && ($privileges == "A"  || $privileges == "M")) {
                $state = $urlState;
            } else{
                $state = $stateFromSession;
                return $this->redirect($state.'?userId='.$userId);
            }
        } else {
            $state = $stateFromSession;
        }

        $title = 'Call9 TESTNI -'. $state . ' Panel' ;

        $korisnik = array(

            'korisnickoIme' => $user['username'],
            'Ime'=> $user['name'],
            'Prezime' =>$user['surname']

        );



        $data = $_outbound->getOutboundRow($userId);
        $Info = $langHelp->getCallCentarInfo($state);
        $workingHours = $_outbound->getWorkingHours($state);
        $postdata = json_decode($data['postdata'], true);
        if(isset($postdata['rpdID'])){
            $rpdID = $postdata['rpdID'];
        } else {
            $rpdID = "";
        }


        $customerName   = $data['name'];
        $productName    = $data['prTitle'];
        $productSKU     = $data['prSKU']."-".$data['prType']."-".$data['proID'];

        $upsellPrice    = $data['upsellPrice'];
        $forTwo         = ((float)$data['price'] * 2) - ((float)$data['price'] + (float)$data['upsellPrice']);
        $forThree       = ((float)$data['price'] * 3) - ((float)$data['price'] + (2*(float)$data['upsellPrice'])) + $Info['postar'];
        $forFour        = ((float)$data['price'] * 4) - ((float)$data['price'] + (3*(float)$data['upsellPrice'])) + $Info['postar'];

        $one    = $data['price'];
        $two    = (float)$data['price'] + (float)$data['upsellPrice'];
        $three  = (float)$data['price'] + ((float)$data['upsellPrice'] * 2);

        $outType = $data['outType'];

        $panelType      = "adcombo";
        $callType = array(
            'type' => $panelType,
            'upsellPrice'=> $upsellPrice
        );
       // print_r($data);die();

        $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $customerName, $productName,$callType );
        $icon = 'phoneOut-';
        return $this->render('outbound/call9.html.twig', array(

            'content' => $content,
            'data'    => $data,
//          'predefinedPhone' => $predefinedPhone,
            'state' => $state,
            'title' => $title,
            'userId' => $userId,
            'productSKU' => $productSKU,
            'operatorId' => $operatorId,
            'customerName' =>$customerName,
            'productName'=>$productName,
            'centerInfo' => $Info,
            'forTwo' => $forTwo,
            'forThree' => $forThree,
            'forFour' => $forFour,
            'one' => $one,
            'two' => $two ,
            'three' => $three,
            'workingHours' => $workingHours,
            'postdata' => $postdata,
            'korisnik' => $korisnik,
            'icon' => $icon,
            'outType' => $outType,
            'rpdID'=>$rpdID,
            'user' =>$user,

        ));

    }

    public function testPhonePanelUsersAction(){


        $conn     = $this->get('database_connection');

        $_omg      = new OMG($conn);
        $_outbound = new Outbound($conn);

        if( !is_null($this->checkifisadmin())) {
            return $this->checkifisadmin();
        }

        $user = $_SESSION['phUser'];
        $products          = $_omg->getProductList("id, title", "1");
        $states            = $_omg->getStates();
        $_testUser43        = $_outbound->getTestUser43();

        $_states = array();
        $_products = array();
        foreach ($products as $prd){
            $_products[$prd['id']] = $prd['title'];
        }
        foreach ($states as $st){
            $_states[$st['code2']] = $st['title_eng'];
        }
        //print_r($_states);die();

        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td style="width: 25px">ID</td>
                        <td>State</td>
                        <td>Name</td>
                        <td>Phone</td>
                        <td>Product</td>
                        <td style="width:70px;">Call count</td>
                        
                        <td style="width:70px;">Status</td>
                         <td style="width:70px;">Outbound type</td>
                        <td style="width:70px;">To call time</td>
                        <td style="width:70px;">Submit Date</td>
                        
                        <td style="width:70px;">Split type</td>
                        <td style="width:70px;">Quantity</td>
                        <td style="width:150px;">Set for outb main panel</td>
                        
                        
                        
                    </tr>
                    </thead>
                    <tbody id="tabela">';

            $html .= '<tr style="height: 50px!important;">';
            $html .= '<td>'.$_testUser43['id'].'</td>
                               
                               
                               <td onclick="tdOption(this);"  style="width:100px;"><span class="fSpan">'.$_testUser43['state'].'</span>
                                            <select id ="stateUrl" class="fSel" data-field="state" data-id="'.$_testUser43["id"].'" style="width:200px;height:35px!important;display:none">';
                                            
                                            foreach ($_states as $key => $_state){
                                                $selecState = '';
                                                if($key == $_testUser43['state']){
                                                    $selecState = 'selected';
                                                }
                                                $html .= '<option value="'.$key.'" '.$selecState.'>'.$_state.'</option>';
                                               
                                            }
                                $html .=   '</select>
                               
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["name"].'</span>
                                    <input type="text" class="fSel" data-field="name" data-id="'.$_testUser43["id"].'" style="width:150px;display:none">
                                </td>
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["phone"].'</span>
                                    <input type="text" class="fSel" data-field="phone" data-id="'.$_testUser43["id"].'" style="width:150px;display:none">
                                </td>
                                 <td onclick="tdOption(this);"  style="width:210px;"><span class="fSpan">'.$_products[$_testUser43['productID']].'</span>
                                            <select id ="productUrl" data-option = "select-box" class="fSel" data-field="productID" data-id="'.$_testUser43["id"].'" style="width:200px; height:35px!important;display:none">';

                                            foreach ($_products as $key => $_product){
                                                $selecProduct = '';
                                                if($key == $_testUser43['productID']){
                                                    $selecProduct = 'selected';
                                                }
                                                $html .= '<option value="'.$key.'" '.$selecProduct.'>'.$_product.'</option>';
                                               
                                            }
                                $html .=   '</select>
                                
                                 <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["callCount"].'</span>
                                    <input type="text" class="fSel" data-field="callCount" data-id="'.$_testUser43["id"].'" style="width:60px;display:none">
                                </td>
                                
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["status"].'</span>
                                    <input type="text" class="fSel" data-field="status" data-id="'.$_testUser43["id"].'" style="width:60px;display:none">
                                </td>
                                
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["type"].'</span>
                                    <input type="text" class="fSel" data-field="type" data-id="'.$_testUser43["id"].'" style="width:60px;display:none">
                                </td>
                                  
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["tocall_time"].'</span>
                                    <input type="text" class="fSel" data-field="tocall_time" data-id="'.$_testUser43["id"].'" style="width:120px;display:none">
                                </td>
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["submitDate"].'</span>
                                    <input type="text" class="fSel" data-field="callEnd" data-id="'.$_testUser43["id"].'" style="width:120px;display:none">
                                </td>
                                
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["splitType"].'</span>
                                    <input type="text" class="fSel" data-field="splitType" data-id="'.$_testUser43["id"].'" style="width:30px;display:none">
                                </td>
                                <td onclick="tdOption(this);">
                                    <span class="fSpan">'.$_testUser43["quantity"].'</span>
                                    <input type="text" class="fSel" data-field="quantity" data-id="'.$_testUser43["id"].'" style="width:60px;display:none">
                                </td>
                                <td>
                                <button onclick="resetUserData('.$_testUser43["id"].')"  type="submit" name="set-for-main-panel" style="width: 120px;" class="btn btn-success">Set</button>
                                </td>';

                               
                            $html .=     '</td>
                                ';
            $html .= '</tr>';

        $panels = array(
            'call' => array(
                'Adcombo',
                'Form Fill Brake',
                'Order Fill Brake'
            ),
            'call2' => array(
                'Upsell Call'
            ),
            'call3' => array(
                'Cancell User'
            ),
            'call4' => array(
                'Reorder CallPanel'
            ),
            'call5' => array(
                'Bulk CallPanel'
            ),
            'call6' => array(
                'Undecided CallPanel',
                'Undecided from Presel'
            ),
            'call7' => array(
                'Reorder Mail CallPanel'
            ),
            'call8' => array(
                'SMS Link CallPanel'
            ),
        );

        $spliTestPanels = array(
            'callt' => array(
                'panelType' => 'Order Fill Brake',
                'state' => 'BA',
                'product'=> ''
            ),
            'callt2' => array(
                'panelType' => 'Adcombo',
                'state' => 'BA',
                'product'=> ''
            ),
            'callt3' => array(
                'panelType' => 'Order Fill Brake',
                'state' => 'BA',
                'product'=> ''
            ),
            'callt4' => array(
                'panelType'=> array(
                    'Adcombo',
                    'Order Fill Brake'
                ),
                'state' => 'HR',
                'product'=> ''
               
            ),

        );
        $title = 'Settings for test user43';


        $html .= '</tbody></table>';

        return $this->render('outbound/testPhonePanelUsers.html.twig', array(

            '_products'      => $_products,
            '_states'        => $_states, 
            '_testUser43'    => $_testUser43,
            'user'           => $user,
            '_html'          => $html,
            '_panels'        => $panels,
            '_splitPanels'   => $spliTestPanels,
            'title'          => $title,

        ));
    }
  


}