<?php

namespace AppBundle\Entity;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class Main extends Controller
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }

        // if ($redi) {
        //     $this->redi = $redi;
        // }
    }

    /*
     * Preuzimanje podataka za chart
     */
    public function getChartData()
    {
        $sql = "SELECT count(*) FROM phone_order_calls
				WHERE 1 GROUP BY state";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * --- Brojanje ordera "Ordered" "Canceled" "Other" "Total" ---       *
     **********************************************************************/
    public function countOrders($kveri)
    {
        $sql = "SELECT count(*) AS broj FROM phone_order_calls
				WHERE 1 $kveri";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /**********************************************************************
     * --- Brojanje ordera "Ordered" "Canceled" "Other" "Total"  za outbound---       *
     **********************************************************************/
    public function countOrdersOubound($kveri)
    {
        $sql = "SELECT count(*) AS broj FROM phone_order_outbound
				WHERE 1 $kveri";
        //var_dump($sql);
        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     *  Provjera da li postoji entry
     */
    public function checkIfExist($table,$kveri)
    {
        $sql = "SELECT * FROM {$table}
				WHERE 1 $kveri";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Nadji broj telefona-
     */
    public function getPhoneByMail($mail)
    {
        $sql = "SELECT namesurname, phone, state FROM customers
				WHERE 1 AND email = '$mail' 
				GROUP BY phone
				ORDER BY id DESC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Nadji Email
     */
    public function getMailByPhone($phone,$state){
        $sql = "SELECT namesurname, email FROM customers
				WHERE 1 AND state = '$state' AND phone LIKE '%$phone%' 
				ORDER BY id DESC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /**********************************************************************
     * --- Nadji telefon po racunu ---       *
     **********************************************************************/
    public function getPhoneByInvoice($invoice, $state){
        $sql = "SELECT customers.phone AS phoneNumber FROM documents
				LEFT JOIN customers ON documents.customer = customers.id
				WHERE 1
				AND documents.doc_number LIKE '%$invoice%'
				AND documents.state = '$state'
				AND documents.doc_type = '1'
				LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     * Posalji na mailstorm unsubscribe
     */
    public function sendToMailStorm($email)
    {
        $dan= Date("Y-m-d");
        //var_dump($email);exit;
        $fields_string = "";
        $url = 'http://*******';
        $fields = array(
            'username' => urlencode(""),
            'password' => urlencode(""),
            'action' => urlencode(""),
            'email' => urlencode($email)
        );

        //url-ify the data for the POST
        foreach($fields as $key=>$value) {
            $fields_string .= $key.'='.$value.'&';
        }
        rtrim($fields_string, '&');

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        //execute post
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        //close connection
        curl_close($ch);

        $podaci = json_decode($info[4]);
        $file = fopen("/var/www/sites/domain.com/htdocs/clp456/api/responselog/".$dan.".txt", "a");
        file_put_contents("/var/www/sites/domain.com/htdocs/clp456/api/responselog/".$dan.".txt", $info, FILE_APPEND);
        fclose($file);
    }

    /*
     * Brojanje danasnjih ordera po drzavama
     */
    public function dailyTableData($datum){
        $sql = "SELECT states.title_eng AS stateTitle,
                SUM(IF(success = 'ORDERED!', 1,0)) AS `sumOrder`,
                SUM(IF(success = 'CANCELED!', 1,0)) AS `sumCancel`,
                SUM(IF(success = 'NO ORDER!', 1,0)) AS `sumOther`,
                COUNT(*) AS sumTotal,
                MAX(date) AS noviDatum
                FROM `phone_order_calls` 
                INNER JOIN states ON phone_order_calls.state = states.code2
                WHERE phone_order_calls.date LIKE '%$datum%' 
                GROUP BY phone_order_calls.state";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Brojanje ordera "Ordered" "Canceled" "Other" "Total"
     */
    public function countOutbounds(){
        $sql = "SELECT count(*) AS broj, state,
                SUM(IF(type = '1', 1,0)) AS `sumACall`,
                SUM(IF(type = '5', 1,0)) AS `sumABrake`,
                SUM(IF(type = '3', 1,0)) AS `sumUpsell`,
                SUM(IF(type = '2', 1,0)) AS `sumCancel`,
                SUM(IF(type = '6', 1,0)) AS `sumOBrake`,
                SUM(IF(type = '7', 1,0)) AS `sumReorder`,
                SUM(IF(type = '8', 1,0)) AS `sumBulk`,
                SUM(IF(type = '9', 1,0)) AS `sumUndec`,
                SUM(IF(type = '10', 1,0)) AS `sumRemail`,
                SUM(IF(type = '11', 1,0)) AS `sumSLink`,
                MAX(submitDate) AS noviDatum
				FROM phone_order_outbound
				WHERE 1
				AND Date(called_time) = Date(NOW())
				GROUP BY state";
        //print_r($sql);die();

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Postavka novog parametra unutar sesije
     */
    public function setSession($sessionName){
        session_start();
        $_SESSION['phUser'][$sessionName] = 0;
    }

    /*
     *  Provjera da li je korisnik ulogovan
     */
    public function checkUserIsLoggedIn(){
        session_start();
        if(isset($_SESSION['phUser'])){
            return true;
        } else {
            return false;
        }
        
    }

    /*
     *  Provjera statusa korisnika
     */
    public function checkUserIfAdmin(){
        session_start();
        if ($_SESSION['phUser']['role'] !== "A" && $_SESSION['phUser']['role'] !== "M"){
            return false;
        } else {
            return true;
        }
    }
    public function checkPrivileges(){
        session_start();
        $userPrivileges = array(
            'A',
            'M',
            'OA',
            'TE',
            'U',
           
        );


        if(!empty($_SESSION['phUser'])){
            if(in_array($_SESSION['phUser']['role'], $userPrivileges)){
               return true;
            }else{
                return false;
            }
        }

    }

    public function checkPrivileges2($roles){
        session_start();

        if(!empty($_SESSION['phUser'])){
            if(in_array($_SESSION['phUser']['role'], $roles)){
                return true;
            }else{
                return false;
            }
        }

    }

    public function checkBorisRodaMartiZeljka($allowedUsers){
        session_start();

        if(!empty($_SESSION['phUser'])){
            if(in_array($_SESSION['phUser']['username'], $allowedUsers) && array_key_exists($_SESSION['phUser']['ouid'], $allowedUsers)){
                return true;
            }else{
                return false;
            }
        }

    }
    public function checkMutipanelPrivileges(){
        session_start();
        if ($_SESSION['phUser']['role'] !== "A" &&  $_SESSION['phUser']['role'] !== "OA" && $_SESSION['phUser']['state'] != 'BA'){
            return false;
        } else {
            return true;
        }

    }



    /*
     * matchovanje boje polja na osnovu Najvece vrijednosti i zadanje vrijednosti
     */

    public function matchCellColor($highest,$current){

//Positive
        $step1 = round($highest/5);
        $step2 = round($highest/5*2);
        $step3 = round($highest/5*3);
        $step4 = round($highest/5*4);

//Negative
        $step5 = "-".round($highest/5);
        $step6 = "-".round($highest/5*2);
        $step7 = "-".round($highest/5*3);
        $step8 = "-".round($highest/5*4);

        $klasa  = "";
        if ($current > 0 && $current < $step1) {
            $klasa = ' class="green0" ';
        } else if ($current > $step1 && $current < $step2) {
            $klasa = ' class="green1" ';
        } else if ($current > $step2 && $current < $step3) {
            $klasa = ' class="green2" ';
        } else if ($current > $step3 && $current < $step4) {
            $klasa = ' class="green3" ';
        } else if ($current > $step4) {
            $klasa = ' class="green4" ';
        }

        if ($current < 0 && $current > $step5) {
            $klasa = ' class="red0" ';
        } else if ($current < $step5 && $current > $step6) {
            $klasa = ' class="red1" ';
        } else if ($current < $step6 && $current > $step7) {
            $klasa = ' class="red2" ';
        } else if ($current < $step7 && $current > $step8) {
            $klasa = ' class="red3" ';
        } else if ($current < $step8) {
            $klasa = ' class="red4" ';
        }

        return $klasa;

    }

    /*
     * univerzalna funkcija za vracanje vrijednosti za zadatu tabelu i id
     */
    public function getRowById($fields="*",$table,$id,$joins="",$query=""){
        $sql = "SELECT {$fields} FROM `{$table}` 
                {$joins}
                WHERE {$table}.id = '{$id}' {$query}";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }
}