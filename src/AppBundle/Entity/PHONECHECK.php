<?php

//include_once ("classSMS.php"); // ukljucivanje SMS klase sa filterima za mobilni i fixni telefon
use AppBundle\Entity\SMS;

/**
 * SREDITI PUTANJA ZA API!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 */
require('/var/www/sites/domain.com/htdocs/api/HlrLookupClient.php');
require_once "/var/www/sites/domain.com/htdocs/vendor/autoload.php";

use Twilio\Rest\Client;

$client = new \VmgLtd\HlrLookupClient('', '');

$AccountSid = "";
$AuthToken = "";
$clientTw = new Client($AccountSid, $AuthToken);

class PHONECHECK extends SMS
{
    public function __construct() {
        global $client;
        $this->_client = $client;

        global $clientTw;
        $this->_clientTw = $clientTw;
    }
    /*
     * Inicijalna funkcija za provjeru broja telefona
     */
    public function checkPhoneNumber($phone, $state){
        $checkFinalStatus = "";
        /*
         * Ciscenje mobilnog telefona
         */
        $checkMobile = $this->cleanMobile($phone, $state);

        if ($checkMobile != false){

            if (strlen($checkMobile) < 8){
                $checkFinalStatus = "Mobile number filtering failed";
                $this->writeLog($phone,$checkFinalStatus);
                return false;
            }

            /*
             * HLR lookup mobilnog telefona
             */
            $phoneToCheck = "+".$this->_areaCodes[$state].$checkMobile;
            $checkHLR       = json_decode($this->_client->submitSyncLookupRequest($phoneToCheck), true);
            $responseIsValid= $checkHLR['results'][0]['isvalid'];

            /*
             * Response za mobilni telefon
             */
            if ($responseIsValid == "Yes"){
                $checkFinalStatus = "HLR lookup OK";
                $this->writeLog($phone,$checkFinalStatus);
                return true;
            } else {
                $checkFinalStatus = "HLR Lookup NOT OK";
                $this->writeLog($phone,$checkFinalStatus);
                return false;
            }
        } else {
            /*
             * Ciscenje fixnog telefona
             */
            $checkLandline = $this->cleanLandline($phone, $state);
            $filteredLandline = $checkLandline;

            if (strlen($filteredLandline) < 8){
                $checkFinalStatus = "Landline number filtering failed";
                $this->writeLog($phone,$checkFinalStatus);
                return false;
            }

            if ($checkLandline != "wrong landline"){
                /*
                 * Twilio lookup za fixni telefon
                 */
                try {                    //start lookup numbers
                    $num = $this->_clientTw->lookups
                        ->phoneNumbers($filteredLandline)
                        ->fetch(
                            array("type" => "carrier")
                        );
                    $exception = null;
                } catch (Exception $e) {
                    $exception = $e->getMessage();
                } //end lookup

                $checkTwilioLookup = $num->carrier["type"];
                /*
                 * Response za Twilio lookup
                 */
                if ($checkTwilioLookup == "landline"){
                    $checkFinalStatus = "Landline lookup OK";
                    $this->writeLog($phone,$checkFinalStatus);
                    return true;
                } else {
                    $checkFinalStatus = "Landline lookup NOT OK";
                    $this->writeLog($phone,$checkFinalStatus);
                    return false;
                }
            } else {
                $checkFinalStatus = "Landline Filtered NOT OK";
                $this->writeLog($phone,$checkFinalStatus);
                return false;
            }
        }


    }

    /**
     *
     * SREDITI PUTANJA !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
     */
    public function writeLog($number,$message){
        $mjesec = Date("Y-m");
        $report = Date('Y-m-d H:i:s').", ".$number.", ".$message." \n";
        $file = fopen("/var/www/sites/domain.com/htdocs/logs/validation/".$mjesec.".txt", "a");
        file_put_contents("/var/www/sites/domain.com/htdocs/logs/validation/".$mjesec.".txt", $report, FILE_APPEND);
        fclose($file);
    }
}