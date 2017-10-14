<?php

/**********************************************************************
 *																	  *
 * ------ ADDRESSVALIDATOR klasa za verifikaciju adrese -------       *
 * 																	  *
 * 	@Author Boris  													  *
 *  08/2017															  *
 **********************************************************************/
class ADDRESSVALIDATOR
{
    /**********************************************************************
     * ------------------------ Priprema klase --------------------       *
     **********************************************************************/

    public function __construct($db)
    {
        if ($db) {
            $this->db = $db;
        }
    }

    function verifyAddress($submitId, $vCountry, $vAddress, $vCity, $vPostal)
    {
        $poDanu     = Date("Y-m-d");
        $states     = $this->getStateTitles();
        $csvText    = "";
        $finalStatusNum = 3;

        foreach ($states AS $state){
            $stateArr[$state['code2']] = $state['title_eng'];
        }

        $stateCode = $stateArr[$vCountry];

        if (!empty($submitId) && isset($submitId) && !empty($vAddress) && isset($vAddress) && !empty($vCity) && isset($vCity)){

            $APIUrl = 'https://api.address-validator.net/api/verify';
            $Params = array('StreetAddress' => $vAddress,
                'City' => $vCity,
                'PostalCode' => $vPostal,
                'State' => '',
                'CountryCode' => $stateCode,
                'Locale' => '',
                'APIKey' => 'iv-08cd980539863568207925c2de799b6f');
            $Request = http_build_query($Params, '', '&');
            $ctxData = array(
                'method' => "POST",
                'header' => "Connection: close\r\n" .
                    "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen($Request) . "\r\n",
                'content' => $Request);
            $ctx = stream_context_create(array('http' => $ctxData));

            // send API request
            $result = json_decode(file_get_contents($APIUrl, false, $ctx));

            $finalStatus= $result->{'status'};
            $fAddress   = addslashes($result->{'street'});
            $fHouseNo   = $result->{'streetnumber'};
            $fPostcode  = $result->{'postalcode'};
            $fCity      = addslashes($result->{'city'});
            $fRegion    = addslashes($result->{'state'});
            $fState     = $result->{'country'};

            if ($finalStatus == "VALID"){
                $chAddress  = trim($result->{'street'});
                if (strpos($vAddress, $chAddress) && $fPostcode == $vPostal && $fCity == $vCity){

                    $finalStatusNum = 1;

                } else {

                    $finalStatusNum = 4;

                }

            } else if ($finalStatus == "SUSPECT"){
                $finalStatusNum = 2;
            } else {
                $finalStatusNum = 3;
            }

        }

        $csvText .= '"' . $submitId . '","' . $vCountry . '","' . $vAddress . '","' . $vCity . '","' . $vPostal . '","' . $stateCode . '","' . $finalStatus . '", "' . $finalStatusNum . '"' . "\n";

        $sql = "INSERT INTO address_validation (`orderId`, `status`, `vAddress`, `vHouseno`, `vPostcode`, `vCity`, `vRegion`, `vState`) VALUES ('{$submitId}','{$finalStatusNum}','{$fAddress}','{$fHouseNo}','{$fPostcode}','{$fCity}','{$fRegion}','{$fState}')";
        $this->db->query($sql, 1);

        $file     = fopen("/var/www/sites/instanio.com/htdocs/dev/phoneorder/api/addressValidation/" . $poDanu . ".csv", "a");
        file_put_contents("/var/www/sites/instanio.com/htdocs/dev/phoneorder/api/addressValidation/" . $poDanu . ".csv", $csvText, FILE_APPEND);
        fclose($file);

        return json_encode($result);
    }




    /**********************************************************************
     * -- Selekcija zemalja za formiranje state niza  --------------------*
     **********************************************************************/

    public function getStateTitles($query = "")
    {
        $sql = "SELECT code2, title_eng FROM states WHERE 1 {$query}";
        $results = $this->db->query($sql, 2);
        return $results;
    }

}
?>