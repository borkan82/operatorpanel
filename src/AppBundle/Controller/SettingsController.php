<?php

namespace AppBundle\Controller;

use AppBundle\Entity\OMG;
use AppBundle\Entity\Settings;
use AppBundle\Entity\Main;
use AppBundle\Entity\Outbound;
use AppBundle\Controller\OutboundController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\GettersController;

class SettingsController extends Controller
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

    private function checkUserPrivileges(){

        $_main      = new Main();
        $loggedIn = $_main->checkUserIsLoggedIn();
        $roles = array(
            'A',
            'M',
            'D'
        );
        if($loggedIn == true){
            $checkUser  = $_main->checkPrivileges2($roles);
            if ($checkUser == false){
                return $this->redirectToRoute('login', array('status'=>'2'));
            } else {
                
            }
        } else {
            return $this->redirectToRoute('login', array('status'=>'3'));
        }
    }
    /**
     * @Template(engine="twig")
     */

    public function usersAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = "Users";

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);

        $_centers   = $_settings->getCallCenterList();
        $_states    = $_omg->getStates();

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();
    
       
        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
            
        }

        $role_user          = $queryArr['role_user'];
        $country_search     = $queryArr['country_search'];


        //Role user filter
        if(isset($role_user) && !empty($role_user))
        {
            $rQ   = " and phone_order_users.role='$role_user' ";
        }
        else{
            $rQ = "";
        }

        //Country filter
        if(isset($country_search) && !empty($country_search))
        {
            $rC      = " and phone_order_users.state='$country_search' ";
        }
        else{

            $rC      = "";
        }

        $query  = ""; //default
        $query .= $rQ; //role
        $query .= $rC; //state

        $users  = $_settings->getUserList($query);


        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>State</td>
                        <td>Name</td>
                        <td>Surname</td>
                        <td>E-mail</td>
                        <td>Username</td>
                        <td>Password</td>
                        <td>Role</td>
                        <td>Group</td>
                        <td>Status</td>
                        <td></td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';
                    $counter =0;
                    foreach ($users as $user){
                        $tabOdd = "";
                        $statusInd = "";
                        $counter++;
                        if ($counter % 2 != 0){
                            $tabOdd = "style='background-color:#eee'";
                        }
                        $aStatus = "Active";
                        if ($user["status"] == "0"){
                            $aStatus = "Inactive";
                        }
                        $html .= '<tr id="r'.$counter.'">';
                        $html .= '<td '.$tabOdd.'>'.$counter.'</td>
                                <td '.$tabOdd.'>'.$user["state"].'</td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$user["name"].'</span>
                                    <input type="text" class="fSel" data-field="name" data-id="'.$user["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$user["surname"].'</span>
                                    <input type="text" class="fSel" data-field="surname" data-id="'.$user["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$user["email"].'</span>
                                    <input type="text" class="fSel" data-field="email" data-id="'.$user["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$user["username"].'</span>
                                    <input type="text" class="fSel" data-field="username" data-id="'.$user["id"].'" style="width:100px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$user["password"].'</span>
                                    <input type="text" class="fSel" data-field="password" data-id="'.$user["id"].'" style="width:200px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$user["role"].'</span>
                                    <input type="text" class="fSel" data-field="role" data-id="'.$user["id"].'" style="width:30px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    '.$user["callCenter"].'
                                </td>
                                <td onclick="tdOption(this);"><span class="fSpan">'.$aStatus.'</span>
                                            <select class="fSel" data-field="status" data-id="'.$user["id"].'" style="width:70px;display:none">
                                                <option value=""></option>
                                                <option value="0">Inactive</option>
                                                <option value="1">Active</option>
                                            </select>
                                </td>
                                <td '.$tabOdd.'><button type="button" data-id="'.$user['id'].'" class="delButton" style="width:100px;font-size: 12px;" onclick="deleteRow(\'phone_order_users\',this,\'r'.$counter.'\');">Delete</button></td>';
                        $html .= '</tr>';
                    }

                    $html .= '</tbody></table>';


                    

        return $this->render('settings/users.html.twig', array(
                                                                    '_html' => $html,
                                                                    '_states' => $_states,
                                                                    '_centers' => $_centers,
                                                                    'title' =>$title
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function callCentersAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = "Call Center";

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);

        $_centers   = $_settings->getCallCenterList();
        $_states    = $_omg->getStates();


        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>State</td>
                        <td>Name</td>
                        <td>Page Phone</td>
                        <td>SMS Phone</td>
                        <td>Reorder Phone</td>
                        <td>E-mail</td>
                        <td></td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

                    $counter = 0;
                    foreach ($_centers as $center){
                        $tabOdd = "";
                        $statusInd = "";
                        $counter++;
                        if ($counter % 2 != 0){
                            $tabOdd = "style='background-color:#eee'";
                        }
                        $html .= '<tr id="r'.$counter.'">';
                        $html .= '<td '.$tabOdd.'>'.$counter.'</td>
                                <td '.$tabOdd.'>'.$center["state"].'</td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$center["name"].'</span>
                                    <input type="text" class="fSel" data-field="name" data-id="'.$center["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$center["pagePhone"].'</span>
                                    <input type="text" class="fSel" data-field="pagePhone" data-id="'.$center["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$center["phone"].'</span>
                                    <input type="text" class="fSel" data-field="phone" data-id="'.$center["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$center["reorderPhone"].'</span>
                                    <input type="text" class="fSel" data-field="reorderPhone" data-id="'.$center["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$center["mail"].'</span>
                                    <input type="text" class="fSel" data-field="mail" data-id="'.$center["id"].'" style="width:150px;display:none">
                                </td>

                                <td '.$tabOdd.'><button type="button" data-id="'.$center['id'].'" class="delButton" style="width:100px;font-size: 12px;" onclick="deleteRow(\'phone_order_callcenter\',this,\'r'.$counter.'\');">Delete</button></td>';
                        $html .= '</tr>';
                    }

                $html .= '</tbody></table>';

        return $this->render('settings/callCenters.html.twig', array(
                                                                    '_html' => $html,
                                                                    '_states' => $_states,
                                                                    'title' =>$title
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function callCenterCostsAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = "Call center costs";

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);

        $_states    = $_omg->getStates();

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $from              = urldecode($queryArr['from']);
        $to                = urldecode($queryArr['to']);
        $sType             = $queryArr['sType'];
        $current_year      = $queryArr['year'];
        $current_month     = $queryArr['month'];
        $chartTitle="";

        
        if(isset($current_year) && !empty($current_year))
        {
            $yQ   = " AND periods.year = '$current_year' ";
        }else{
            $current_year  = date("Y");
            $current_monthhh = date("m");
            if($current_monthhh == 1) $current_year--;
            $yQ        = " AND periods.year = '$current_year'";
        }

        //month filter
        if(isset($current_month) && !empty($current_month))
        {
            $mQ    = " AND periods.month = '$current_month'";
        } else {
            $current_month = date("m");
            if($current_month == 1) {
                $current_month = 12;
            } else {
                $current_month--;
            }
            $mQ = " AND periods.month = '$current_month'";
        }

        if (isset($from) && !empty($from))   {
            $monthF = date("n",strtotime($from));
            $yearF  = date("Y",strtotime($from));
            $dfQ    = " and periods.month >= '$monthF' and periods.year >= '$yearF' ";
        } else {
            $from   = date("M Y",strtotime("-3 Months"));
            $monthF = date("n",strtotime($from));
            $yearF  = date("Y",strtotime($from));
            $dfQ    = " and periods.month >= '$monthF' and periods.year >= '$yearF' ";
        }
        
        if (isset($to) && !empty($to))       {
            $monthT = date("n",strtotime($to));
            $yearT  = date("Y",strtotime($to));
            $dtQ    = " and periods.month <= '$monthT' and periods.year <= '$yearT' ";
        } else {
            $to   = date("M Y",strtotime("-1 Months"));
            $monthT = date("n",strtotime($to));
            $yearT  = date("Y",strtotime($to));
            $dtQ    = " and periods.month <= '$monthT' and periods.year <= '$yearT' ";
        }

        if (isset($sType) && !empty($sType) && $sType == 1 ) {
            $chartTitle = 'Out Per Order';
        } else {
            $sType = '';
            $chartTitle = 'In Per Order';
        }


        $chartQuery = "";
        $chartQuery.=$dfQ;
        $chartQuery.=$dtQ;
        $chartQuery.=' order by periods.year ASC, periods.month asc';
        $chartData = $_settings->getListOfCosts($chartQuery);

        $nizSve = Array();
        foreach ($chartData AS $allData) {
            if ((int) (log($allData['month'], 10) + 1) == 1){
                $key = $allData['year'].'-0'.$allData['month'];
            } else {
                $key = $allData['year'].'-'.$allData['month'];
            }
            $callCenterChart = $allData["name"].' ('.$allData['state'].')';

            $nizSve[$key][$callCenterChart] = array(
                'inPerOrder'  => $allData['INperOrder'],
                'outPerOrder' => $allData['OUTperOrder']
            );

        }

        
        $grouped_centers = $_settings->getCallCenterList(" GROUP BY name,state ");

        // kategorije chart-a
        // series - parametar charta
        $ccategories = "";
        $cseries = "";
        foreach ($grouped_centers as $centerChart) {
            $cName = $centerChart["name"].' ('.$centerChart['state'].')';
            $cseries .= "{ name: '$cName', data: [";

            foreach ($nizSve AS $key=>$cData) {
                if (strpos($ccategories, $key) === false) {
                    $ccategories .= "'".$key."', ";
                }
                foreach ($cData AS $k => $perOrder) {
                    if ($k == $cName) {
                        if ($sType == 1){
                            $cseries .= $perOrder['outPerOrder'] . ", ";
                        } else {
                            $cseries .= $perOrder['inPerOrder'] . ", ";
                        }
                    }
                }
            }
            $cseries .= "]}, ";
        }

        $query  = "";
        $query .= $yQ;
        $query .= $mQ;
        
        $centers = $_settings->getListOfCosts($query);

        $years = array('2010', '2011', '2012', '2013', '2014', '2015', '2016', '2017', '2018', '2019', '2020');
        $months = array(1=> 'January',2=> 'February',3=> 'March',4=> 'April',5=> 'May',6=> 'Juni',7=> 'July',8=> 'August',9=> 'September',10=> 'October',11=> 'November',12=> 'December');

        //print_r($current_month);die();

        $html = '<table class="dayView" id="example">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>State</td>
                        <td>Name</td>
                        <td>Year</td>
                        <td>Month</td>
                        <td>Inb Cost</td>
                        <td>Out Cost</td>
                        <td>IN cost per Call</td>
                        <td>IN cost per Order</td>
                        <td>OUT cost per Call</td>
                        <td>OUT cost per Order</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

                    $counter = 0;

                    foreach ($centers as $center){
                        $rowClass       = "style='background-color:#fff'";
                        $rowClassIn     = "style='background-color:#fff'";
                        $rowClassOut    = "style='background-color:#fff'";
                        $tabOdd = "";
                        $statusInd = "";
                        $counter++;
                        if ($counter % 2 != 0){
                            $tabOdd = "style='background-color:#eee'";
                        }

                        if ($center["INperOrder"] > 0 && $center["INperOrder"] < 5){
                            $rowClassIn = 'class="green2"';
                        } else if ($center["INperOrder"] >= 5 && $center["INperOrder"] < 10){
                            $rowClassIn = 'class="yell2"';
                        } else if ($center["INperOrder"] >= 10){
                            $rowClassIn = 'class="red2"';
                        }

                        if ($center["OUTperOrder"] > 0 && $center["OUTperOrder"] < 5){
                            $rowClassOut = 'class="green2"';
                        } else if ($center["OUTperOrder"] >= 5 && $center["OUTperOrder"] < 10){
                            $rowClassOut = 'class="yell2"';
                        } else if ($center["OUTperOrder"] >= 10){
                            $rowClassOut = 'class="red2"';
                        }

                        $html .= '<tr id="r'.$counter.'">';
                        $html .= '<td '.$tabOdd.'>'.$counter.'</td>
                                <td '.$tabOdd.'>'.$center["state"].'</td>
                                <td '.$tabOdd.'>'.$center["name"].'</td>
                                <td '.$tabOdd.'>'.$center["year"].'</td>
                                <td '.$tabOdd.'>'.$months[$center["month"]].'</td>
                                <td '.$rowClass.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$center["inboundPrice"].'</span>
                                    <input type="text" class="fSel" data-field="inboundPrice" data-id="'.$center["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$rowClass.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$center["outboundPrice"].'</span>
                                    <input type="text" class="fSel" data-field="outboundPrice" data-id="'.$center["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$rowClass.'>'.$center["INperCall"].'</td>
                                <td '.$rowClassIn.'>'.$center["INperOrder"].'</td>
                                <td '.$rowClass.'>'.$center["OUTperCall"].'</td>
                                <td '.$rowClassOut.'>'.$center["OUTperOrder"].'</td>';
                        $html .= '</tr>';
                    }

                    $html .= '</tbody></table>';
//        print_r($from.'<br>');
//        print_r($to.'<br>');die();

        return $this->render('settings/callCenterCosts.html.twig', array(
                                                                    '_html'   => $html,
                                                                    '_states' => $_states,
                                                                    '_years'  => $years,
                                                                    '_months' => $months,
                                                                    'title'   => $title,
                                                                    'current_month' => $current_month,
                                                                    'current_year'  => $current_year,
                                                                    'from'    => $from,
                                                                    'to'      =>$to,
            'charthead'=>$chartTitle,
            'ccategories'=>$ccategories,
            'cseries'=>$cseries,
            '_grouped_centers'=> $grouped_centers,
           
            
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function smsPricesAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = "SMS prices";

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);

        $prices = $_settings->getSMSPrices();
        $_states = $_omg->getStates();

        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>State</td>
                        <td>Price</td>
                        <td></td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

                    $counter = 0;
                    foreach ($prices as $price){
                        $tabOdd = "";
                        $statusInd = "";
                        $counter++;
                        if ($counter % 2 != 0){
                            $tabOdd = "style='background-color:#eee'";
                        }
                        $html .=  '<tr id="r'.$counter.'">';
                        $html .=  '<td '.$tabOdd.'>'.$counter.'</td>
                                <td '.$tabOdd.'>'.$price["title"].'</td>
                                <td '.$tabOdd.' onclick="tdOption(this);">
                                    <span class="fSpan">'.$price["price"].'</span> €
                                    <input type="text" class="fSel" data-field="price" data-id="'.$price["id"].'" style="width:150px;display:none">
                                </td>
                                <td '.$tabOdd.'><button type="button" data-id="'.$price['id'].'" class="delButton" style="width:100px;font-size: 12px;" onclick="deleteRow(\'phone_order_smsprices\',this,\'r'.$counter.'\');">Delete</button></td>';
                        $html .=  '</tr>';
                    }

                    $html .= '</tbody></table>';


        return $this->render('settings/smsPrices.html.twig', array(
                                                                    '_html' => $html,
                                                                    '_states' => $_states,
                                                                    'title' =>$title
                                                                    ));
    }

    /**
     * @Template(engine="twig")
     */

    public function productDescriptionAction()
    {
        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }
        
        
        $conn       = $this->get('database_connection');
//        error_reporting(E_ALL);
//        ini_set("display_errors", 1);
        $title      = "Product description";

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);

        $_products = $_omg->getProductListZ();
        $_states = $_omg->getStates();
       
       


        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        
        $pSel = $queryArr['pSel'];


        //year user filter
        if(isset($pSel) && !empty($pSel))
        {
            $spQ = " id = '$pSel' ";
        }
        else{

            $pSel = "";
            $spQ = "";
        }
      
        $html = '<table class="dayView compact" id="example">
                    <thead>
                    <tr>
                        <td >#</td>
                        <td >Product</td>
                        <td style="width:35%">Product Text</td>
                        <td >Languages</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';

        $counter = 0;
        foreach ($_products as $product){
          
            $idTeksta = $product['id'];
            $_translations = $_settings->getTranslationByProduct($idTeksta);

            $tabOdd = "";
            $counter++;
            if ($counter % 2 != 0){
                $tabOdd = "style='background-color:#eee'";
            }
            $translationArr = Array();
            $initialMessage = "";
            $tmDate     = "";
            $tmPull     = "0";
            $tmArr      = Array();
            foreach ($_translations AS $translation){
                array_push($translationArr, $translation["state"]);

                if ($translation["state"] == "HR"){
                    $initialMessage = $translation["productText"];
                }

                if (!empty($translation["sentTime"]) && $translation["sentTime"] != "" && $translation["sentTime"] != null){
                    $tmDate     = $translation["sentTime"];
                } else {
                    $tmDate     = "";
                }
                $tmPull = $translation["tmpull"];

                $tmArr[$translation["state"]] = Array($tmDate,$tmPull);


            }
            $html .= '<tr id="r'.$counter.'">';
            $html .= '<td '.$tabOdd.'>'.$counter.'</td>
                    <td '.$tabOdd.'>'.$product["title"].'</td>
                    <td class ="alignLeft" '.$tabOdd.'>'.$initialMessage.'</td>
                    <td '.$tabOdd.' style="width:35%">';

                    $stateExist = "";
                    $trAct      = "new";
                    foreach ($_states as $_state) {

                        if (in_array($_state["code2"], $translationArr)){
                            $stateExist = "existTrans";
                            $trAct      = "update";
                        } 

                    $html .=  '<span class="stateClick '.$stateExist.'" data-tmdate="'.$tmArr[$_state["code2"]][0].'" data-tmpull="'.$tmArr[$_state["code2"]][1].'" data-trid="'.$product['id'].'" onclick="showTextTranslation(this,\''.$trAct.'\');">'.$_state["code2"].'</span> ';
                                $stateExist = "";
                                $trAct = "new";
                    }

            $html .= '</td>';
            $html .= '</tr>';
           
        }
        //print_r($html);

        $html .= '</tbody></table>';



        return $this->render('settings/productDescription.html.twig', array(
                                                                    '_html' => $html,
                                                                    '_states' => $_states,
                                                                    'title' =>$title,
                                                                   
                                                                    ));
    }


    /**
     * @Template(engine="twig")
     */

    public function productPricesAction()
    {

        if( !is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn       = $this->get('database_connection');
        $title      = "Product price";

        $_omg       = new OMG($conn);
        $_settings  = new Settings($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state      = $queryArr['state'];
        $product    = $queryArr['product'];


        //state filter
        if(isset($state) && !empty($state))
        {
            $scQ    = " and states.code2 LIKE '$state' ";
        } else {
            $state  = "BA";
            $scQ    = " and states.code2 LIKE '$state' ";
        }   

        //product filter
        if(isset($product) && !empty($product))
        {
            $spQ        = "1 and products.id = '$product' ";
        } else {
            $product    = "";
            $spQ        = "1";
        }

        $centers    = $_settings->getCallCenterList();
        $_states    = $_omg->getStates($scQ);
        $allStates  = $_omg->getStates();
        $_products  = $_omg->getProductList("*",$spQ);
        $_prices    = $_settings->getPriceList();

        $_statelist    = $_omg->getStates();
        $_productlist  = $_omg->getProductList();

        $allPrices = Array();

        foreach ($_products AS $item){
            $allPrices[$item['id']] = Array();
            foreach ($_states AS $single){
                $allPrices[$item['id']][$single['code2']] = Array('base'=>0.00, 'idNum'=>0, 'upsell'=>0.00);
            }
        }

        foreach ($_prices AS $priceItem){
            $allPrices[$priceItem['productId']][$priceItem['state']]['idNum'] = $priceItem['id'];
            $allPrices[$priceItem['productId']][$priceItem['state']]['base'] = $priceItem['price'];
            $allPrices[$priceItem['productId']][$priceItem['state']]['upsell'] = $priceItem['upsellPrice'];


        }

        $availableProducts = Array();

        foreach ($_states AS $_st){
            $availableProducts[$_st['code2']] = Array();
            $proizvodi     = $_omg->getAvailableProducts($_st['code2']);

            foreach ($proizvodi AS $pro){
                if ($pro['suma'] != 0) {
                    $availableProducts[$_st['code2']][$pro["id"]] = 1;
                }
            }
        }


        $html = '<table class="display" cellspacing="0" id="example">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>SKU</th>
                        <th>Product</th>';

                    foreach ($_states AS $_state) {
                        $html .= '<th colspan="2">'.$_state["code2"].'</th>';
                    }

        $html .= '</tr><tr><th colspan="3"></th>';

                    foreach ($_states AS $_state) {
                        $html .= '<th>Base</th><th>Upsell</th>';
                    }

        $html .= '</tr></thead><tbody>';

                $counter = 0;
                foreach ($_products as $row){
                    $tabOdd = "";
                    $statusInd = "";
                    $counter++;

                    if ($counter % 2 != 0){
                        $tabOdd = "style='background-color:#eee'";
                    }

                    $prSku      = $row['sku'];
                    $prTip      = $row['productType'];
                    $prId       = $row['id'];
                    $fullsku    = $prSku."-".$prTip."-".sprintf("%04s", $prId);


                     $html .= '<tr id="r'.$counter.'">';
                     $html .= '<td '.$tabOdd.'>'.$counter.'</td>
                                <td '.$tabOdd.'>'.$fullsku.'</td>
                                <td '.$tabOdd.'>'.$row["title"].'</td>';

                        foreach ($_states AS $_state) {
                            $noPriceState= "";
                            $noPriceProduct= 0;
                            if ($allPrices[$row["id"]][$_state["code2"]]["idNum"] == 0){
                                $noPriceState   = $_state["code2"];
                                $noPriceProduct = $row["id"];
                            }
                            $availablecssg = $tabOdd;
                            $availablecssy = $tabOdd;

                            $availability = $availableProducts[$_state['code2']][$row["id"]];
                            if ($availability == 1){
                                $availablecssg = 'style="background-color:#CFC;"';
                                $availablecssy = 'style="background-color:#FFC;"';
                            }


                             $html .= '<td ' . $availablecssg . ' onclick="tdOption(this);" >
                                            <span class="fSpan">' . $allPrices[$row["id"]][$_state["code2"]]["base"] . '</span>
                                            <input type="text" class="fSel" data-state="'. $noPriceState.'"  data-productid="'.$row["id"].'" data-insert-prices ="productPrices" data-field="price" data-id="' . $allPrices[$row["id"]][$_state["code2"]]["idNum"] . '" style="width:60px;display:none">
                                        </td>
                                        <td  ' . $availablecssy . ' class="tdYellow" onclick="tdOption(this);">
                                            <span class="fSpan">' . $allPrices[$row["id"]][$_state["code2"]]["upsell"] . '</span>';


                             $html .= '<input type="text" class="fSel" data-insert-prices ="productPrices" data-field="upsellPrice" data-id="' . $allPrices[$row["id"]][$_state["code2"]]["idNum"] . '" style="width:60px;display:none">';

                             $html .= '</td>';
                        }

                     $html .= '</tr>';
                }

                $html .= '</tbody></table>';

        return $this->render('settings/productPrices.html.twig', array(
                                                                        '_html' => $html,
                                                                        '_states' => $allStates,
                                                                        'title' =>$title,
                                                                        'state' => $state
                                                            
                                                                        ));
    }

    public function reorderLinksAction()
    {

        if (!is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn = $this->get('database_connection');
        $title = "SMS Reorder Links";

        $_omg = new OMG($conn);
        $_settings = new Settings($conn);

        $_states = $_omg->getStatesWithId();
        $_products = $_omg->getProductList("id, title", "1");


        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }

        $product = $queryArr['product_search'];

        if (!empty($product) && isset($product)) {
            $pQ = " and phone_order_reorder_links.product_id='$product' ";
        } else {
            $pQ = "";
        }

        $qviri = ""; //default
        $qviri .= $pQ; //product

        $reorderLinks = $_settings->getReorderLinks($qviri);
        $newStates       = $_settings->getStatesReorderLinks();


        $_data = array();
        foreach ($reorderLinks as $liiink) {
            $_data[$liiink['product_id']]['title'] = $liiink['title'];
            $_data[$liiink['product_id']][$liiink['state']] = $liiink['link'];
        }

        $html = '<table class="dayView compact" id="example">
                <thead>
                <tr>
                    <td width="20px"> # </td>
                    <td width="100px">Product name</td>';

        foreach ($newStates AS $newState) {
            $html .= '<td style="width: 75px;">' . $newState["state"] . '</td>';
        }
        $html .= ' </tr>
                </thead>
                <tbody id="tabela">';
        $counter = 0;

        foreach ($_data as $key => $rlink) {

            $counter++;
            $html .= '<tr class="" style="margin-top:1px; cursor:pointer; height: 40px">
                            <td>' . $counter . '</td>
                            <td>' . $rlink["title"] . '</td>';


            foreach ($newStates as $neeewSt){
                if (array_key_exists($neeewSt['state'], $rlink)) {
                $html .=' <td>
                        <a href="'.$rlink[$neeewSt["state"]].'" target="_blank">
                            <button title="open link: '.$rlink[$neeewSt["state"]].'"><i style="font-size: 1.3em" class="glyphicon glyphicon-eye-open" aria-hidden="true"></i></button>
                        </a>
                        <button data-state="'.$neeewSt["state"].'"
                                data-state-id="'.$neeewSt["state_id"].'"
                                data-product-id="'.$key.'"
                                data-product-title="'.$rlink["title"] .'"
                                data-link="'.$rlink[$neeewSt["state"]] .'"
                                data-action="edit"
                                title="edit link for '.$rlink["title"].' ('.$neeewSt["state"].')">
                            <i style="font-size: 1.3em" class="glyphicon glyphicon-pencil" aria-hidden="true"></i>
                        </button>
                    </td>';

                } else {
                    $rlink[$neeewSt['state']] = 0;
                    $html .= ' <td title="you dont have link for '.$rlink["title"].' ('.$neeewSt["state"].')"><span style="   background-color: #f5f5f5;    color: #a94442;"><i class="glyphicon glyphicon-remove"></i></span></td>';
                }
            }
            $html .= '</tr>';
        }
        $html .=' </tbody>
            </table>';
//        /print_r($html);die();

        return $this->render('settings/reorderLinks.html.twig', array(
            '_html' => $html,
            '_states' => $_states,
            '_products' => $_products,
            'counter'  => $counter,
             'title' =>$title
        ));
    }
    
    public function outboundSwitchProductsAction()
    {
        if (!is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn = $this->get('database_connection');
        $title = "Switch Products";

        $_omg       = new OMG($conn);
        $_outbound  = new Outbound($conn);
        $_outbStats = new OutboundController();
        $_getters   = new GettersController($conn);

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $ordType     = $queryArr['ordType'];
        $product     = $queryArr['product'];
        if(isset($ordType) && !empty($ordType))
        {
            $spQ = " and phone_order_switch.orderType = '$ordType' ";
        } else {
            $ordType = 1;
            $spQ = " and phone_order_switch.orderType = '$ordType' ";
        }

        if(isset($product) && !empty($product))
        {
            $pQ = " and phone_order_switch.product = '$product' ";
        } else {
            $product ='';
            $pQ = "";
        }

        $query = " ";
        $query .= $spQ;
        $query .= $pQ;

        $_products = $_omg->getSimpleProductList();
        //$_states = $_omg->getStates(" AND hasSales = 1");
        //print_r($query);
        $_switchs = $_outbound->getOutboundSwitchProducts($query);

        $_states = array(
            37 => 'AL',
            1 => 'BA',
            6 => 'BG',
            9 => 'CZ',
            21 => 'EE',
            17 => 'GR',
            3 => 'HR',
            8 => 'HU',
            12 => 'IT',
            20 => 'LT',
            19 => 'LV',
            5 => 'MK',
            11 => 'PL',
            7 => 'RO',
            4 => 'RS',
            2 => 'SI',
            10 => 'SK',

            39 => 'XK'

        );

        $products = array();
        foreach ($_products as $prd){
            $products[$prd['id']] = $prd['title'];
        }
        $_data = array();
        //print_r($_data);

        if ($product != ''){
            $_data[$product]['title'] = $products[$product];
            foreach ($_states as $state){
                $_data[$product][$state] = '';
            }
            
        } else {
            foreach ($_products as $product){
                $_data[$product['id']]['title'] =$product['title'];
                foreach ($_states as $state){
                    $_data[$product['id']][$state] = '';
                }
            }
        }



        foreach ($_switchs as $_switch){
            if (!isset($_data[$_switch['product']])) {
                $_data[$_switch['product']] = Array();
            }

            $_data[$_switch['product']][$_switch['state']] = $_switch['active'];
        }

        $html = '<table class="dayView" id="example">
                <thead>
                <tr>
                    <td width="20px"> # </td>
                    <td>Product name</td>';

        foreach ($_states AS $_state){
            $html .= '<td>'.$_state.'</td>';
        }

        $html .= '</tr>
                </thead>
                <tbody id="tabela">';

        $counter = 0;

        foreach ($_data as $key=>$value) {

            $counter++;
            $html .= ' <tr class="" style="margin-top:1px; cursor:pointer;">
                        <td class="">'.$counter.'</td>
                        <td class="">'.$value['title'].'</td>';

            foreach($_states as $country) {
                $button = '';
                $collor = '';
                $status = '';

                if( $value[$country] == "" ) {
                    $button = 'fa fa-times';
                    $collor = 'darkred';
                    $status = 'insert';
                } else if($value[$country] == 0 ) {
                    $button = 'fa fa-times';
                    $collor = 'darkred';
                    $status = 'enable';
                } else if($value[$country] == 1 ) {
                    $button = 'fa fa-check';
                    $collor = 'green';
                    $status = 'disable';
                }
                $html .= '<td>
                            <button  data-value-status="'.$value[$country].'"
                                     title="'.$status.'"
                                     type="button"

                                     data-action="'.$status.'"
                                     data-product-id="'.$key.'"
                                     data-product-state ="'.$country.'"
                                     onclick="changeProductStatusSwitch(this ,'.$ordType.');"
                                     style="width:30px; height:30px; cursor: pointer; font-size: 18px;">
                                <i class="'.$button.'" aria-hidden="true" style="color:'.$collor.'"><span style="display: none">'.$value[$country].'</span></i>
                            </button>
                        </td>';

            }
            $html .= '</tr>';
        }
        $html .= '</tbody>
             </table>';



        return $this->render('settings/outboundSwitchProducts.html.twig', array(
            '_html'   => $html,
            '_types'  => $_getters->getOutboundTypesAction(),
            '_states' => $_states,
            'title'   => $title,
            'ordType' => $ordType,
            '_products'=>$_products
        ));

    }

    public function costsAction()
    {
        if (!is_null($this->checkThisSession())) {
            return $this->checkThisSession();
        }

        $conn = $this->get('database_connection');
        $title = "Costs";
        $thisYear      = date('Y');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }
        $year     = $queryArr['year'];
        if(isset($ordType) && !empty($ordType))
        {
            $yQ = " and phone_order_costs.year = '$year' ";
        } else {
            $year = $thisYear;
            $yQ = " and phone_order_costs.year = '$year' ";
        }

        $query = " ";
        $query .= $yQ;

        $_omg       = new OMG($conn);
        $_outbound  = new Outbound($conn);
        $_getters   = new GettersController($conn);

        $costs = $_outbound->getCosts('');
        $months = array(  
                'january',
                'february',
                'march',
                'april',
                'may',
                'june',
                'july',
                'avgust',
                'september',
                'october',
                'november',
                'december'
            );
       

        $_states = $_omg->getStates();
        $hello = array();
        foreach ($_states as $st){
            foreach ($months as $m){
                $hello[$st['code2']][$m]=0;
            }
        }
        
        foreach ($costs as $cost){
            foreach ($months as $mon){
                if (!empty($cost[$mon])){
                    $hello[$cost['state']][$mon]=$cost[$mon];
                }
            }
        }

      
        //print_r($hello);

        $html = '<table class="dayView compact" id="example" style="font-size: 12px;">
                    <thead style="cursor:pointer;">
                    <tr>     
                        <td >#</td>
                        <td >State</td>
                        <td >January</td>
                        <td >February</td>
                        <td >March</td>
                        <td >April</td>
                        <td >May</td>
                        <td >June</td>
                        <td >July</td>
                        <td >Avgust</td>
                        <td >September</td>
                        <td >October</td>
                        <td >November</td>
                        <td >December</td>
                    </tr>
                    </thead>
                    <tbody id="tabela">';
        $counter = 0;

        foreach ($hello as $key =>$value){
            $counter++;
            $html .= '<tr>
                      <td>'.$counter.'</td>
                      <td>'.$key.'</td>
                      <td>'.$value["january"].'</td>
                      <td>'.$value["february"].'</td>
                      <td>'.$value["march"].'</td>
                      <td>'.$value["april"].'</td>
                      <td>'.$value["may"].'</td>
                      <td>'.$value["june"].'</td>
                      <td>'.$value["july"].'</td>
                      <td>'.$value["avgust"].'</td>
                      <td>'.$value["september"].'</td>
                      <td>'.$value["october"].'</td>
                      <td>'.$value["november"].'</td>
                      <td>'.$value["december"].'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';



        return $this->render('settings/costs.html.twig', array(
            '_html'   => $html,
            '_states' => $_states,
            'title'   => $title,
            'year'    => $year
        ));

    }
    public function productProfilesAction()
    {

        if (!is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }

        $conn = $this->get('database_connection');
        $title = "Product Profiles";

        $_omg = new OMG($conn);
        $_settings = new Settings($conn);

        $_states = $_omg->getActiveStates();
        $_products = $_omg->getProductList("id, title", "1");

        //print_r($_states);die();
        $request = Request::createFromGlobals();
        $queryStr = explode("&", $request->getQueryString());
        $queryArr = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=", $q);
            $queryArr[$split[0]] = $split[1];
        }

        $product = $queryArr['product_search'];
        $state   = $queryArr['state_search'];

        if (!empty($product) && isset($product)) {
            $pQ = " and phone_order_productProfiles.product_id='$product' ";
        } else {
            $pQ = "";
        }

        if (!empty($state) && isset($state)) {
            $sQ = " and phone_order_productProfiles.state='$state' ";
        } else {
            $sQ = "";
        }

        $qviri = ""; //default
        $qviri .= $pQ; //product
        $qviri .= $sQ; //product

        $productProfiles = $_settings->getProductProfiles($qviri);
//        $newStates       = $_settings->getStatesFromProductProfiles($sQ);


        $_data = array();
        foreach ($productProfiles as $prProfile) {
            $_data[$prProfile['product_id']]['title'] = $prProfile['title'];
            $_data[$prProfile['product_id']][$prProfile['state']] = $prProfile['profile'];
        }

        $html = '<table class="dayView compact" id="example" style="    margin-top: 10px;">
                <thead>
                <tr>
                    <td width="20px"> # </td>
                    <td width="150px;"><strong>Product name</strong></td>';

        foreach ($_states AS $newState) {
            $html .= '<td style="width: 75px;"><strong>' . $newState["code2"] . '</strong></td>';
        }
        $html .= ' </tr>
                </thead>
                <tbody id="tabela">';
        $counter = 0;
       // print_r($_data);die();
        foreach ($_data as $key => $producProf) {

            $counter++;
            $html .= '<tr class="" style="margin-top:1px; cursor:pointer; height: 40px">
                            <td><strong>' . $counter . '</strong></td>
                            <td><strong>' . $producProf["title"] . '</strong></td>';


            foreach ($_states as $neeewSt){
                if (array_key_exists($neeewSt['code2'], $producProf)) {
                    $html .=' <td><strong style="font-size:18px">'.$producProf[$neeewSt['code2']].'</strong></td>';

                } else {
                    $producProf[$neeewSt['$_states']] = 0;
                    $html .= ' <td><span style="   background-color: #f5f5f5;    color: #a94442;"><i class="glyphicon glyphicon-remove"></i></span></td>';
                }
            }
            $html .= '</tr>';
        }
        $html .=' </tbody>
            </table>';
//        /print_r($html);die();
        $user = $_SESSION['phUser'];

        return $this->render('settings/productProfiles.html.twig', array(
            '_html' => $html,
            '_states' => $_states,
            '_products' => $_products,
            'counter'  => $counter,
            'title' =>$title,
            'user' =>$user
        ));
    }
    
   

}
