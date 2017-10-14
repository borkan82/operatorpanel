<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Settings;
use AppBundle\Entity\Main;

class DefaultController extends Controller
{
    private function checkThisSession(){

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
    public function indexAction(Request $request)
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $title = 'Dashboard';
        $conn = $this->get('database_connection');


        $_main = new Main($conn);
        $today 			= Date("Y-m-d");

        // Qveri-ji za ukupan broj ordera inbound
        $countOrder       = $_main->countOrders(" AND success = 'ORDERED!' AND date LIKE '$today%'");
        $countCancel      = $_main->countOrders(" AND success = 'CANCELED!' AND date LIKE '$today%'");
        $countOther       = $_main->countOrders(" AND type = 2 AND success = 'NO ORDER!' AND date LIKE '$today%'");
        $countAll         = $_main->countOrders(" AND date LIKE '$today%'");
        $dailyTable       = $_main->dailyTableData($today);
        $countOutbounds   = $_main->countOutbounds();

        // Qveri-ji za ukupan broj ordera outbound
        $countOrderOubound       = $_main->countOrdersOubound(" AND (status = 7 OR status = 12) AND called_time LIKE '$today%'");
        $countCancelOubound      = $_main->countOrdersOubound(" AND status = 6 AND called_time LIKE '$today%'");
        $countOtherOubound       = $_main->countOrdersOubound(" AND status != 6 AND status != 7 AND status != 12 AND called_time LIKE '$today%'");
        $countAllOubound         = $_main->countOrdersOubound(" AND called_time LIKE '$today%'");

        // Definisanje zadnjih 7 dana koji se koriste za Chart
        $lastSevenDays = array();
        for ($i=1; $i<8; $i++){
            $lastSevenDays[$i] =date('Y-m-d', strtotime('-' .$i.' days'));
        }
        $daysDesc = krsort($lastSevenDays);

        // Priprema niza po datumima i drzavama
        $drzave = Array("HR","BA","RS","MK","SI","BG","RO","LV","IT","DE","HU","EE","GR","PL","LT","CZ","SK");
        $datumi = array();
        foreach ($lastSevenDays as $key=>$value){
            $datumi[$key] = $value;
        }
        $datumi[0]= $today;
        $nizSve = array();
        $rez = array();

        foreach ($drzave as $d2){
            $nizSve[$d2] = array();
            foreach ($datumi as $d){
                $nizSve[$d2][$d] = 0;
            }
        }

        //Uzimanje podataka potrebnih za Chart
        $getChartData = "SELECT state,date(date) as datum,count(*) as broj FROM `phone_order_calls` WHERE 1 group by date(date),state ORDER BY date DESC";
        $chart = $conn->query($getChartData,2);

        // Dodavanje podataka u pripremljen niz
        foreach ($chart as $rezultat){
            array_push($rez, array("drzava"=>$rezultat['state'],'datum'=>$rezultat['datum'],'broj'=>$rezultat['broj']));
        }

        foreach($rez as $r)
        {
            $drzava = $r['drzava'];
            $datum = $r['datum'];
            $broj = $r['broj'];

            $nizSve[$drzava][$datum] = $broj;
        }

        //print_r($nizSve);die();
        $html = '<div class="dayTable">
                    <div style="width:49%;float:left">
                        <h3>Inbound calls</h3>
                        <table class="dayView compact" id="example">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>State</td>
                                <td>Ordered</td>
                                <td>Canceled</td>
                                <td>Other</td>
                                <td>Total</td>
                                <td>Last Call Made<BR></td>
                            </tr>
                            </thead>
                            <tbody>';

        $countTab = 1;
        if (!empty($dailyTable)){
            foreach ($dailyTable as $row) {
                $html .= '<tr>';
                $html .= '<td>'.$countTab.'</td><td style="text-align:left;">'.$row['stateTitle'].'</td><td>'.$row['sumOrder'].'</td><td>'.$row['sumCancel'].'</td><td>'.$row['sumOther'].'</td><td>'.$row['sumTotal'].'</td><td>'.$row['noviDatum'].'</td>';
                $html .= '</tr>';
                $countTab++;
            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="7"><span style="color:#f26100;font-weight: bold;">There are no calls recieved today!</span></td>';
            $html .= '</tr>';
        }

        $html .=  '</tbody>
                </table>
                </div>';

        $html .='<div style="width:50%;float:left;margin-left:3px;">
                    <h3>Outbound calls</h3>
                    <table class="dayView compact" id="example2">
                        <thead>
                        <tr>
                            <td>#</td>
                            <td>State</td>
                            <td>AdCombo call</td>
                            <!--                    <td>AdCombo Break</td>-->
                            <td>Reorder call</td>
                            <td>Order Break</td>
                            <!--                    <td>Upsell</td>-->
                            <td>Bulk call</td>
                            <td>Cancel</td>
                            <td>Last Call</td>
                        </tr>
                        </thead>
                        <tbody>';

        $countTab = 1;

        if (!empty($countOutbounds)){
            foreach ($countOutbounds as $rowo) {

                $html .= '<tr>';
                $html .= '<td>'.$countTab.'</td>
                                <td style="text-align:left;">'.$rowo['state'].'</td>
                                                         <td>'.$rowo['sumACall'].'</td>
                                                         <td>'.$rowo['sumReorder'].'</td>
                                                         <td>'.$rowo['sumOBrake'].'</td>
                                                         <td>'.$rowo['sumBulk'].'</td>
                                                         <td>'.$rowo['sumCancel'].'</td>
                                                         <td>'.$rowo['noviDatum'].'</td>';
                $html .= '</tr>';

                $countTab++;

            }
        } else {
            $html .= '<tr>';
            $html .= '<td colspan="8"><span style="color:#f26100;font-weight: bold;">There are no calls recieved today!</span></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody>
                </table>
            </div>
        </div>';

        return $this->render('default/default.html.twig', array(
            'countOrder' => $countOrder,
            'countCancel' => $countCancel,
            'countOther' => $countOther,
            'countAll' => $countAll,
            'countOther' => $countOther,
            'countAll' => $countAll,
            'dailyTable' => $dailyTable,
            'countOutbounds' => $countOutbounds,
            'countOrderOubound' => $countOrderOubound,
            'countCancelOubound' => $countCancelOubound,
            'countOtherOubound' => $countOtherOubound,
            'countAllOubound' => $countAllOubound,
            'chart' => $chart,
            'datumi' => $datumi,
            'nizSve' => $nizSve,
            '_html'  =>$html,
            'title' => $title,
        ));

    }
//    /**
//     * @Template(engine="php")
//     */
//    public function indexAction(Request $request)
//    {
//    	$today 			= Date("Y-m-d");
//
//
//		$conn 			= $this->get('database_connection');
//		$countSql 		= "SELECT count(*) AS broj FROM phone_order_calls WHERE 1 ";
//		$countOutSql 	= "SELECT count(*) AS broj FROM phone_order_outbound WHERE 1 ";
//
//        $countOrder 	= $conn->fetchArray($countSql." AND success = 'ORDERED!' AND date LIKE '$today%'");
//        $countCancel 	= $conn->fetchArray($countSql." AND success = 'CANCELED!' AND date LIKE '$today%'");
//        $countOther 	= $conn->fetchArray($countSql." AND type = 2 AND success = 'NO ORDER!' AND date LIKE '$today%'");
//        $countAll 		= $conn->fetchArray($countSql." AND date LIKE '$today%'");
//
//        $dailyTable 	= $conn->fetchAll("SELECT states.title_eng AS stateTitle,
//											SUM(IF(success = 'ORDERED!', 1,0)) AS `sumOrder`,
//											SUM(IF(success = 'CANCELED!', 1,0)) AS `sumCancel`,
//											SUM(IF(success = 'NO ORDER!', 1,0)) AS `sumOther`,
//											COUNT(*) AS sumTotal,
//											MAX(date) AS noviDatum
//											FROM `phone_order_calls`
//											INNER JOIN states ON phone_order_calls.state = states.code2
//											WHERE phone_order_calls.date LIKE '%$today%' GROUP BY phone_order_calls.state");
//
//        $countOutbounds 	= $conn->fetchAll("SELECT count(*) AS broj, state,
//													SUM(IF(type = '1', 1,0)) AS `sumACall`,
//													SUM(IF(type = '5', 1,0)) AS `sumABrake`,
//													SUM(IF(type = '3', 1,0)) AS `sumUpsell`,
//													SUM(IF(type = '2', 1,0)) AS `sumCancel`,
//													SUM(IF(type = '6', 1,0)) AS `sumOBrake`,
//													SUM(IF(type = '7', 1,0)) AS `sumReorder`,
//													SUM(IF(type = '8', 1,0)) AS `sumBulk`,
//													MAX(submitDate) AS noviDatum
//												FROM phone_order_outbound
//												WHERE 1
//												AND Date(called_time) = Date(NOW())
//												GROUP BY state");
//
//        $countOrderOubound 	= $conn->fetchAll($countOutSql." AND (status = 7 OR status = 12) AND called_time LIKE '$today%'");
//        $countCancelOubound = $conn->fetchAll($countOutSql." AND status = 6 AND called_time LIKE '$today%'");
//        $countOtherOubound 	= $conn->fetchAll($countOutSql." AND status != 6 AND status != 7 AND status != 12 AND called_time LIKE '$today%'");
//        $countAllOubound 	= $conn->fetchAll($countOutSql." AND called_time LIKE '$today%'");
//        $chart				= $conn->fetchAll("SELECT state,date(date) as datum,count(*) as broj FROM `phone_order_calls` WHERE 1 group by date(date),state ORDER BY date DESC");
//
//        return $this->render('::default/default.html.php', array('countOrder' => $countOrder, 'countCancel' => $countCancel, 'countOther' => $countOther, 'countAll' => $countAll,
//        												   'countOther' => $countOther,'countAll' => $countAll,'dailyTable' => $dailyTable,'countOutbounds' => $countOutbounds,
//        												   'countOrderOubound' => $countOrderOubound,'countCancelOubound' => $countCancelOubound,'countOtherOubound' => $countOtherOubound,
//        												   'countAllOubound' => $countAllOubound, 'chart' => $chart));
//    }

    /**
     * @Template(engine="twig")
     */

    public function loginAction()
    {
        $conn       = $this->get('database_connection');
        $_settings  = new Settings($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $logout  = $queryArr['logout'];
        $status  = $queryArr['status'];
        $username    = $request->request->get('username');
        $password    = $request->request->get('password');
        $loginMessage = "";

        session_start();

        if (isset($logout) && $logout == "1" && isset($_SESSION['infomedia_session_id'])){
            $insertTimeOfLogout = $_settings->insertUserLogoutInfo($_SESSION['infomedia_session_id']);
        }

        if (isset($logout) || $logout == "1"){
            session_unset();
            session_destroy();
        }

        if (isset($username)){
        $pass = $password;

        $_checkUser = $_settings->checkUser($username,$pass);
        }

        if ($_checkUser >0){
            if ($_checkUser['status'] == 1){
                session_unset();

                $_SESSION["phUser"] = Array("ouid"=>$_checkUser['id'],"username"=>$_checkUser['username'],"state"=>$_checkUser['state'],"role"=>$_checkUser['role'],"name"=>$_checkUser['name'],"surname"=>$_checkUser['surname']);
                $ouid = $_checkUser['id'];


                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $ip = $_SERVER['REMOTE_ADDR'];
                }

                $userLogs = $_settings->insertUserLogInformation($ouid,$ip);
                $_SESSION['infomedia_session_id']=$userLogs;
                $loginMessage = "Logged in";

                //return $this->redirect('redirect?ouid='.$_checkUser['id']);
                return $this->redirectToRoute('redirect', array('ouid' => $_checkUser['id']));
            } else {

                $loginMessage = "Account not active! ";
            }
        } else {

            $loginMessage = "Username or password incorrect!".$username." ".$pass;
        }
        switch($status){
            case "1":
                $loginMessage = "Username or password incorrect!";
            break;
            case "2":
                $loginMessage = "You don't have permission to that page!";
            break;
            case "3":
                $loginMessage = "You are not logged in!";
            break;
            default:
            break;
        }



        return $this->render('default/login.html.twig', array(
            '_html' => "",
            'loginMessage' => $loginMessage
            ));
    }

    /**
     * @Template(engine="twig")
     */

    public function redirectAction()
    {

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $ouid           = $queryArr['ouid'];

    session_start();

    $drzava = $_SESSION["phUser"]["state"];
    $uloga = $_SESSION["phUser"]["role"];
    $loginMessage = "";
        if ($uloga == "U" || $uloga == "OA") {
            return $this->redirectToRoute('languages', array('state' => $drzava, 'ouid' => $ouid));
        } else if ($uloga == "A" || $uloga == "M") {
            return $this->redirectToRoute('default', array());
        } else if ($uloga == "D") {
            return $this->redirectToRoute('productProfiles', array());
        } else {
            return $this->redirectToRoute('login', array());
        }
    }
}
