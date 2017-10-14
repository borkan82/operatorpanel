<?php
include 'includes/config.php';
include CLASS_PATH.'classMain.php';
include CLASS_PATH.'classSMS.php';

$_main = new Main($db);
$_sms = new SMS($db);



$mjesec= Date("m-Y");
$datum = Date("Y-m-d H:i:s");

$podaci  = "Time: ".$datum."\n";
$podaci .= "State: ".$_POST["state"]."\n";
$podaci .= "Source: ".$_POST["action"]."\n";
$podaci .= "APIKey: ".$_POST["APIKey"]."\n";
$podaci .= "Phone: ".$_POST["phone"]."\n";
$podaci .= "Email: ".$_POST["email"]."\n";
$podaci .= "Invoice: ".$_POST["invoice"]."\n\n";

$file = fopen("/var/www/sites/domain.com/htdocs/clp456/api/log/".$mjesec.".txt", "a");
file_put_contents("/var/www/sites/domain.com/htdocs/clp456/api/log/".$mjesec.".txt", $podaci, FILE_APPEND);
fclose($file);

if (isset($_POST['APIKey']) && isset($_POST['action']) && $_POST['APIKey'] == "" && $_POST['action'] == ""){

    if (isset($_POST['email']) && !empty($_POST['email']) && $_POST['email'] != "No mail" && strpos($_POST['email'], '@') !== false){

        $email = $_POST['email'];
        $getPhone = $_main->getPhoneByMail($email);

        if ($getPhone) {
            foreach ($getPhone AS $row){
                $telefon = $row['phone'];
                $state = $row['state'];

                $filteredPhone = $_sms->cleanMobile($telefon,$state);
                if (strlen($filteredPhone) > 6){

                    $checkIfExists = $_main->checkIfExist("suppressionList", " AND state = '{$state}' AND number = '{$telefon}' ");

                    if ($checkIfExists == false) {
                        $writeSuppression = $_sms->writeSuppression($state,$filteredPhone,1);
                    }

                }
            }
            $status = Array("status"=>"success", "data"=>"unsubscribed");
            echo json_encode($status);
        } else {

            $status = Array("status"=>"success", "data"=>"No match");
            echo json_encode($status);
        }
    } else {
        $status = Array("status"=>"failed", "data"=>"No mail");
        echo json_encode($status);
    }

} else if (isset($_POST['APIKey']) && isset($_POST['action']) && $_POST['APIKey'] == "2D5419EB5F4D79A0FB89737C153F129C" && $_POST['action'] == "unsubscribePhoneOrder") {


    if (isset($_POST['phone']) && !empty($_POST['phone']) && isset($_POST['state']) && !empty($_POST['state']) && strlen($_POST['phone']) > 6) {

        $phone = $_POST['phone'];
        $state = $_POST['state'];
        $filteredPhone = $_sms->cleanMobile($phone,$state);
        if (strlen($filteredPhone) > 5) {
            $writeSuppression = $_sms->writeSuppression($state,$filteredPhone );
            $getMail = $_main->getMailByPhone((int)$filteredPhone,$state);

            if($getMail) {
                $tempMail = Array();
                foreach ($getMail AS $row){
                    $email = $row['email'];
                    if (in_array($email, $tempMail) == false){
                        $sendToMailStorm = $_main->sendToMailStorm($email); //Send email to Mailstorm API
                        array_push($tempMail, $email);
                    }
                }
            }

            $status = Array("status"=>"success");
            echo "1";
        }

        //echo json_encode($status);
    } else if (isset($_POST['email']) && !empty($_POST['email'])){

        $email = $_POST['email'];
        $sendToMailStorm = $_main->sendToMailStorm($email);

        $getPhone = $_main->getPhoneByMail($email);

        if ($getPhone) {
            foreach ($getPhone AS $row){
                $telefon = $row['phone'];
                $state = $row['state'];

                $filteredPhone = $_sms->cleanMobile($telefon,$state);
                if (strlen($filteredPhone) > 5) {
                    $writeSuppression = $_sms->writeSuppression($state, $filteredPhone);
                }
            }
            $status = Array("status"=>"success");
            echo "1";
            //echo json_encode($status);
        } else {

            $status = Array("status"=>"failed");
            echo "0";
            //echo json_encode($status);
        }
    } else {
        $status = Array("status"=>"failed");
        echo "0";
        //echo json_encode($status);
    }

} else if (isset($_POST['APIKey']) && isset($_POST['action']) && $_POST['APIKey'] == "2D5419EB5F4D79A0FB89737C153F129C" && $_POST['action'] == "unsubscribeRefund") {


    if (isset($_POST['invoice']) && !empty($_POST['invoice']) && strlen($_POST['invoice']) > 10 && isset($_POST['state']) && !empty($_POST['state'])) {

        $invoice            = $_POST['invoice'];
        $state              = $_POST['state'];

        $phone              = $_main->getPhoneByInvoice($invoice, $state);
        $filteredPhone      = $_sms->cleanMobile($phone['phoneNumber'],$state);

        if (strlen($filteredPhone)>5) {
        $writeSuppression   = $_sms->writeSuppression($state,$filteredPhone );

            $getMail            = $_main->getMailByPhone((int)$filteredPhone,$state);

            if($getMail) {
                $tempMail = Array();
                foreach ($getMail AS $row){
                    $email = $row['email'];
                    if (in_array($email, $tempMail) == false){
                        $sendToMailStorm = $_main->sendToMailStorm($email); //Send email to Mailstorm API
                        array_push($tempMail, $email);
                    }
                }
            }
        }
        $status = Array("status"=>"success");
        echo "1";
        //echo json_encode($status);
    } else {
        $status = Array("status"=>"failed");
        echo "0";
        //echo json_encode($status);
    }

}
else
{
    $status = Array("status"=>"failed", "data"=>"No valid access data");
    echo json_encode($status);
}
?>