<?php

namespace AppBundle\Entity;

class Settings
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    /*
     *  Upis novog product texta
     */
    public function getSMSprices()
    {
        $sql = "SELECT phone_order_smsprices.id AS id, phone_order_smsprices.state AS state, phone_order_smsprices.price AS price, phone_order_smsprices.exchange AS exchval, states.title_eng AS title FROM phone_order_smsprices
                LEFT JOIN states on phone_order_smsprices.state = states.code2
                WHERE 1";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    public function callTrackInfo($query)
    {
        $sql   = "SELECT phone_order_users.username AS operatorName, phone_order_users.state, phone_order_callcenter.name AS callcenter,
                  phone_order_tracker.opentime, phone_order_tracker.callUp AS answered, phone_order_tracker.callEnd AS ended, phone_order_tracker.inspectletId AS sId
                  FROM phone_order_users
                  INNER JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
                  INNER JOIN phone_order_tracker    ON phone_order_users.id = phone_order_tracker.operator
                  WHERE 1 {$query}
                  ORDER BY phone_order_tracker.id DESC";
        //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Funkcija za listanje call centara
     */
    public function getCallCenterList($grupa = "")
    {
        $sql = "SELECT * FROM phone_order_callcenter
				WHERE 1 AND state != 'TE' {$grupa}";
       
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Funkcija za listanje operatera
     */
    public function getOperators()
    {
        $sql = "SELECT `username`
                FROM `phone_order_users`";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Funkcija za listanje Korisnika
     */
    public function getUserList($kveri="")
    {
        $sql = "SELECT phone_order_users.*,  phone_order_callcenter.name AS callCenter FROM phone_order_users
                LEFT JOIN phone_order_callcenter ON phone_order_users.operatorGroup = phone_order_callcenter.id
				WHERE 1 $kveri";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Funkcija za listanje mj. cijena callcentara
     */
    public function getCallCenterPrices($period)
    {
        $splitPeriod = explode("-", $period);
        $year        = (int)$splitPeriod[0];
        $month       = (int)$splitPeriod[1];

        $sql = "SELECT phone_order_callCenterPrice.callCenterId AS centerId, phone_order_callCenterPrice.inboundPrice AS inboundPrice, phone_order_callCenterPrice.outboundPrice AS outboundPrice, phone_order_callcenter.state AS centerState FROM periods
                LEFT JOIN phone_order_callCenterPrice ON periods.id = phone_order_callCenterPrice.period
                LEFT JOIN phone_order_callcenter ON phone_order_callCenterPrice.callCenterId = phone_order_callcenter.id
				WHERE 1 AND periods.year = {$year} AND periods.month = {$month} ";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Funkcija za listanje mj. cijena callcentara za outbound
     */
    public function getCallCenterOutboundPrices($period)
    {
        $splitPeriod = explode("-", $period);
        $year        = (int)$splitPeriod[0];
        $month       = (int)$splitPeriod[1];

        $sql = "SELECT phone_order_callCenterPrice.callCenterId AS centerId, phone_order_callCenterPrice.inboundPrice AS inboundPrice, phone_order_callCenterPrice.outboundPrice AS outboundPrice, phone_order_callcenter.state AS centerState FROM periods
                LEFT JOIN phone_order_callCenterPrice ON periods.id = phone_order_callCenterPrice.period
                LEFT JOIN phone_order_callcenter ON phone_order_callCenterPrice.callCenterId = phone_order_callcenter.id
				WHERE 1 AND periods.year = {$year} AND periods.month = {$month} ";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * --------- Funkcija za listanje call centara ------------------       *
     **********************************************************************/
    public function addCenter($state, $name, $pagephone, $phone, $reorderphone, $email)
    {
        $sql = "INSERT INTO phone_order_callcenter (`name`,`state`,`pagePhone`,`phone`,`reorderPhone`,`mail`)
                                             VALUES('$name','$state','$pagephone','$phone','$reorderphone','$email')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /**********************************************************************
     * -------------- Upis novog korisnika -------------------------       *
     **********************************************************************/
    public function writeUser($state, $name, $surname, $email, $fullname, $username, $password, $role, $status, $group)
    {
        $encrypt = md5($password);

        $sql = "INSERT INTO phone_order_users (`state`, `name`, `surname`, `email`, `fullname`, `username`, `password`, `role`, `status`,`operatorGroup`)
                                              VALUES ('$state', '$name', '$surname', '$email', '$fullname','$username','$encrypt','$role','$status','$group')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /*
     * Funkcija za listanje Korisnika
     */
    public function getProductText($query)
    {
        $sql = "SELECT productDescription.id AS id, products.title AS product, state, productText 
                FROM productDescription
                LEFT JOIN products on productDescription.productId = products.id
                WHERE 1 {$query}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * -------------- Provjeri ulogovanog korisnika ---------------       *
     **********************************************************************/
    public function checkUser($username, $pass)
    {

        $enc_pass = md5($pass);
        $kveri = "SELECT * FROM phone_order_users WHERE username='$username' and password='$enc_pass' LIMIT 1";

        return $this->conn->fetchAssoc($kveri);
    }

    public function insertUserLogInformation($user_id, $ipAddress)
    {
        $sql = "INSERT INTO phone_order_user_logs (`phone_order_user_id`, `ip_address`)
                                              VALUES ('$user_id', '$ipAddress')";

        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /******************************************************************************************
     * -------------- Upisivanje podataka korisnika koji je otisaso na logout --------------- *
     ******************************************************************************************/
    public function insertUserLogoutInfo($userIdLog){
        $sql = "UPDATE phone_order_user_logs
                SET datetime_logout = NOW(), logout_type_id = 1
                WHERE id = {$userIdLog}";
        //var_dump($sql);die();

        $results = $this->conn->executeQuery($sql);
        return $results;
    }

    /**********************************************************************
     * -------------- Upis novog product texta -------------------------  *
     **********************************************************************/
    public function writeText($state, $product, $prodText)
    {
        //$prodText = mysql_real_escape_string($productText, $this->db->_connect);

        $sql = "INSERT INTO productDescription (`state`, `productId`, `productText`)
                                              VALUES ('$state', '$product', '$prodText')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /*
     * Listanje Tekstova product description
     */
    public function getTranslationByProduct($query)
    {

        $sql = "SELECT productDescription.id AS id, productDescription.productText AS productText, state, t2.sentTime AS sentTime, t2.TMPullBack AS tmpull
                FROM productDescription
                LEFT JOIN
                    (
                    SELECT MAX(id) as maxid, descID, sentTime, TMPullBack
                    FROM phone_order_TM
                    GROUP BY id
                    ) t2
                    ON productDescription.id = t2.descID
                WHERE productId = {$query}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * -------------- Listanje product description-a --------------       *
     **********************************************************************/
    public function getTextTrans($pID, $state)
    {
        $sql = "SELECT id, productDescription.productText AS productText, state, outQ, outQ1, outQ2, outQ3, outA1, outA2, outA3,
                        outDQ1, outDQ2, outDQ3, outDQ4, outDQ5, outAQ1, outAQ2, outAQ3, outAQ4, outAQ5, t2.sentTime as sentTime,
                        t2.getTime as getTime, t2.TMPullBack AS TMPullBack
                        FROM productDescription
                        LEFT JOIN (
                                  SELECT MAX(id) AS maxId, sentTime, getTime, descID, TMPullBack FROM phone_order_TM GROUP BY id
                                  ) as t2 ON productDescription.id = t2.descID
                        WHERE productId = {$pID} AND state = '{$state}' LIMIT 1";

        $results = $this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     * Listanje product description-a
     */
    public function getExistingTranslations($pID){
        $sql = "SELECT state, t2.sentTime as sentTime, t2.getTime as getTime, t2.TMPullBack AS TMPullBack
                        FROM productDescription
                        LEFT JOIN (
                                  SELECT MAX(id) AS maxId, sentTime, getTime, descID, TMPullBack FROM phone_order_TM GROUP BY id
                                  ) as t2 ON productDescription.id = t2.descID
                        WHERE productId = {$pID}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * -------------- Izmjena prevoda product opisa ---------------       *
     **********************************************************************/
    public function updateTextTrans($pID, $state, $text, $outQ, $outQ1, $outQ2, $outQ3, $outA1, $outA2, $outA3, $outDQ1,
                                    $outDQ2, $outDQ3, $outDQ4, $outDQ5, $outAQ1, $outAQ2, $outAQ3, $outAQ4, $outAQ5)
    {
        $text   = addslashes($text);
        $outQ   = addslashes($outQ);
        $outQ1  = addslashes($outQ1);
        $outQ2  = addslashes($outQ2);
        $outQ3  = addslashes($outQ3);
        $outA1  = addslashes($outA1);
        $outA2  = addslashes($outA2);
        $outA3  = addslashes($outA3);
        $outDQ1 = addslashes($outDQ1);
        $outDQ2 = addslashes($outDQ2);
        $outDQ3 = addslashes($outDQ3);
        $outDQ4 = addslashes($outDQ4);
        $outDQ5 = addslashes($outDQ5);
        $outAQ1 = addslashes($outAQ1);
        $outAQ2 = addslashes($outAQ2);
        $outAQ3 = addslashes($outAQ3);
        $outAQ4 = addslashes($outAQ4);
        $outAQ5 = addslashes($outAQ5);


        $sql = "UPDATE productDescription
                SET productText = '{$text}', outQ = '{$outQ}', outQ1 = '{$outQ1}', outQ2 = '{$outQ2}', outQ3 = '{$outQ3}', outA1 = '{$outA1}', outA2 = '{$outA2}',
                    outA3 = '{$outA3}', outDQ1 = '{$outDQ1}', outDQ2 = '{$outDQ2}', outDQ3 = '{$outDQ3}', outDQ4 = '{$outDQ4}', outDQ5 = '{$outDQ5}', outAQ1 = '{$outAQ1}',
                    outAQ2 = '{$outAQ2}', outAQ3 = '{$outAQ3}', outAQ4 = '{$outAQ4}', outAQ5 = '{$outAQ5}'
                WHERE productId = {$pID} AND state = '{$state}'";

        $results = $this->conn->executeQuery($sql);

        if ($results) {
            echo "1";
        } else {
            echo "-1";
        }
    }

    /**********************************************************************
     * -------------- Dodavanje novog prevoda product opisa -------       *
     **********************************************************************/
    public function newTextTrans($pID, $state, $text, $outQ, $outQ1, $outQ2, $outQ3, $outA1, $outA2, $outA3, $outDQ1,
                                 $outDQ2, $outDQ3, $outDQ4, $outDQ5, $outAQ1, $outAQ2, $outAQ3, $outAQ4, $outAQ5)
    {
        //$text = mysql_real_escape_string($text, $this->db->_connect);
        $sql = "INSERT INTO productDescription (`productId`, `state`, `productText`, `outQ`, `outQ1`, `outQ2`, `outQ3`, `outA1`, `outA2`, `outA3`, `outDQ1`, `outDQ2`, `outDQ3`, `outDQ4`, `outDQ5`,
                                                `outAQ1`, `outAQ2`, `outAQ3`, `outAQ4`, `outAQ5`)
                                                VALUES ($pID, '$state','$text','$outQ','$outQ1','$outQ2','$outQ3','$outA1','$outA2','$outA3','$outDQ1','$outDQ2','$outDQ3','$outDQ4','$outDQ5',
                                                        '$outAQ1','$outAQ2','$outAQ3','$outAQ4','$outAQ5')";

        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /*
     * Listanje product description-a
     */
    public function getPriceList()
    {
        $sql = "SELECT * FROM phone_order_prices
                WHERE 1";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * ------------------------ Uhvati cijene -----------------------      *
     **********************************************************************/
    public function getProductPriceAndUpsell($product, $state)
    {
        $sql = "SELECT *
                FROM `phone_order_prices`
                WHERE productId = '$product' AND state LIKE '$state' LIMIT 1";

        $results = $this->conn->fetchAssoc($sql);
        return $results;
    }

    /**********************************************************************
     * -------------- Dodavanje cijene ako ne postoji -------       *
     **********************************************************************/
    public function addPriceIfNotExists($productId, $state, $price, $upsellPrice)
    {

        $sql = "INSERT INTO phone_order_prices (`productId`, `state`, `price`, `upsellPrice`)
                                                VALUES ($productId, '$state','$price','$upsellPrice')";

        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /**********************************************************************
     * -------------- Hvatanje cijena za kampanju -----------------       *
     **********************************************************************/
    public function getCampaignPrices($product, $state)
    {
        $sql = "SELECT *
                FROM `CampManagement`
                WHERE product = '$product' AND Country LIKE '$state' ORDER BY id DESC LIMIT 1";

        $results = $this->conn->fetchAssoc($sql);
        return $results;
    }

    /**********************************************************************
     * -------------- Pretraga po proizvodu -----------------------       *
     **********************************************************************/

    public function searchProducts($phrase)
    {
        $sql     = "SELECT `title`, `productText` FROM `products`
                INNER JOIN `productDescription` ON `productDescription`.productId = `products`.id
                WHERE `title` LIKE '%$phrase%' AND `state`='BA' OR `state`='HR'";

        $result   = $this->conn->fetchAssoc($sql);

        if($result) return $result;

        else return false;
    }

    /*
     * Pretraga po proizvodu
     */
    public function searchStateAndRole($query)
    {
        $sql   =   "SELECT * FROM `phone_order_users` WHERE"; // `state`='$state' && `role`='$role'";
        $sql  .=   $query;

        $results = $this->conn->fetchAll($sql);

        if($results) return $results;
        else return false;
    }

    public function getListOfCosts($query = "")
    {
        $sql   = "SELECT * , phone_order_callCenterPrice.id AS id FROM phone_order_callcenter
                  LEFT JOIN phone_order_callCenterPrice ON phone_order_callcenter.id = phone_order_callCenterPrice.callCenterId
                  LEFT JOIN periods ON phone_order_callCenterPrice.period = periods.id
                  WHERE phone_order_callcenter.state != 'TE' {$query}";
        //var_dump($sql);die();
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    public function sendToTM($translationArr)
    {
        $tmAPIURL  = "http://new.mytranslations.info/tm_api.php";
        $post_url = 'req='.urlencode(serialize($translationArr));

        $ch = curl_init($tmAPIURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $url = curl_exec($ch);
        $return = trim($url);
        curl_close($ch);

        return $return;
    }


    public function insertTMRequest($descId, $reqId, $selectedJSON)
    {
        $sql = "INSERT INTO phone_order_TM (`descID`,`TMid`,`descFields`)
                                     VALUES('$descId','$reqId','$selectedJSON')";
        $this->conn->executeQuery($sql);
    }
    public function insertSmsTMRequest($smsId, $reqId)
    {
        $sql = "INSERT INTO phone_order_TM (`smsID`,`TMid`)
                                     VALUES('$smsId','$reqId')";
        $this->conn->executeQuery($sql);
    }

    public function getTMtranslation($getTMRequestId)
    {
        $tmAPIURL  = "http://new.mytranslations.info/phoneorder_answ.php";

        $ch = curl_init($tmAPIURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'request_id='.$getTMRequestId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $url = curl_exec($ch);
        $return = trim($url);
        curl_close($ch);
//echo json_encode($return);
        //exit;
        return $return;
    }


    public function getTMRequestId($state,$product)
    {
        $sql = "SELECT t2.maxid AS maxId, t2.TMid AS TMid
                FROM productDescription
                LEFT JOIN
                    (
                    SELECT MAX(id) as maxid, descID, TMid
                    FROM phone_order_TM
                    GROUP BY id
                    ) t2
                    ON productDescription.id = t2.descID
                WHERE productDescription.productId = {$product} AND productDescription.state = '{$state}' LIMIT 1";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    public function getSourceTranslation($product){
        $q         = "SELECT * FROM productDescription
                      WHERE productId = {$product} AND state = 'BA' ORDER BY id DESC LIMIT 1";

        $result    = $this->conn->fetchAssoc($q);
        return $result;
    }


    public function setPullTM($tmId){
        $sql = "UPDATE phone_order_TM
                SET TMPullBack = 1
                WHERE id = {$tmId}";

        $results = $this->conn->executeQuery($sql);
        return $results;
    }

    public function insertNewDesc($st, $product) {
        $sql = "INSERT INTO productDescription (`productId`, `state`)
                                                VALUES ($product, '$st')";

        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }


    /***********************************************************************
     *------ Unosenje proizvoda u phone order switch outbound products-----*
     **********************************************************************/
    public function insertAndEnableOutboundProduct($product, $state, $ordType, $active='1') {
        $sql = "INSERT INTO phone_order_switch (`product`,`state`,`orderType` ,`active`)
                VALUES ('$product', '$state', '$ordType', '$active')";

        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();;
    }
    /************************************************************************************
     *------ Enable (1) or Disable (2) outbound product in phone prder switch table-----*
     ***********************************************************************************/
    public function enableDisableProductsSwitch($product, $state, $ordType, $active) {
        $sql = "UPDATE phone_order_switch
                SET active = '$active'
                WHERE product = '$product' AND state = '$state' AND orderType = '$ordType'";

        $results = $this->conn->executeQuery($sql);
        return $results;
    }
    /*
     *  Funkcija za listanje reorder linkova
     */
    public function getReorderLinks($kveri="")
    {
        $sql = "SELECT phone_order_reorder_links.*, states.code2 AS state, products.title
                FROM phone_order_reorder_links
                JOIN states ON phone_order_reorder_links.state_id = states.id
                JOIN products ON phone_order_reorder_links.product_id = products.id
				WHERE 1 $kveri";
        //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /*
     * Funkcija za drzava reorder linkova-
     */
    public function getStatesReorderLinks($kveri="")
    {
        $sql = "SELECT phone_order_reorder_links.state_id, states.code2 AS state
                FROM phone_order_reorder_links
                JOIN states ON phone_order_reorder_links.state_id = states.id
                WHERE 1 $kveri
                GROUP BY state
                ORDER BY state
                ";
        //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Update reorder linka
     */
    public function updateReorderLink($state, $product, $link)
    {

        $writekveri = "UPDATE phone_order_reorder_links
                       SET link = '{$link}'
                       WHERE state_id = {$state} and product_id = {$product}";

        //var_dump($writekveri);

        $results = $this->conn->executeQuery($writekveri);

        var_dump($results);

        if ($results) {
            echo "1";
        } else {
            echo "-1";
        }


    }
    /*
     * Upis reorder linka
     */
    public function writeReorderLink( $state, $product, $link)
    {

        $writekveri = "INSERT INTO phone_order_reorder_links ( `state_id`,`product_id`, `link`)
                       VALUES ('$state','$product',  '$link')";
        $this->conn->executeQuery($writekveri);
        return $this->conn->lastInsertId();;
    }

    /*
 *  Funkcija za listanje reorder linkova
 */
    public function getProductProfiles($kveri="")
    {
        $sql = "SELECT phone_order_productProfiles.*, products.title
                FROM phone_order_productProfiles
                JOIN products ON phone_order_productProfiles.product_id = products.id
				WHERE 1 $kveri";
        //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /*
     * Funkcija za drzava reorder linkova-
     */
    public function getStatesFromProductProfiles($kveri="")
    {
        $sql = "SELECT phone_order_productProfiles.state_id, states.code2 AS state
                FROM phone_order_productProfiles
                JOIN states ON phone_order_productProfiles.state_id = states.id
                WHERE 1 $kveri
                GROUP BY state_id
                ORDER BY state
                ";
        //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /*
   * Upis produkt profila
   */
    public function writeProductProfile( $state, $product, $profile)
    {

        $writekveri = "INSERT INTO phone_order_productProfiles ( `state`,`product_id`, `profile`)
                       VALUES ('$state','$product',  '$profile')";
        $this->conn->executeQuery($writekveri);
        return $this->conn->lastInsertId();;
    }

    /*
  * Editovanje postojeceg produkt profila
  */
    public function updateProductProfile($id, $profile)
    {
        $writekveri = "UPDATE phone_order_productProfiles SET profile='$profile' WHERE id='$id' ";
        return $this->conn->executeQuery($writekveri);

    }

    /*
    * Editovanje postojeceg test usera 43 profila
    */
    public function setUserDataForMainOutboundPanel($id,$time)
    {
        $writekveri = "UPDATE phone_order_outbound 
                       SET tocall_time='$time', status = 0, callCount = 0, submitDate = '$time'
                       WHERE id='$id' ";
        $response = $this->conn->executeQuery($writekveri);
        //var_dump($response);
        return $response;

    }


    /*
     * Total Inbound poziva za bi se izracunao cost per call i cost per order
     */
    public function getTotalInbounds($month,$year,$group){

        $date = $year."-".$month."-01";

        $sql     = "SELECT count(*) as totalInbound, SUM(IF( phn.success = 'ORDERED!', 1, 0)) as orderedNum
                    FROM `phone_order_calls` AS `phn`
                    LEFT JOIN orders ON (phn.orderSubmitId = orders.submitId AND phn.orderSubmitId != 0)
                    LEFT JOIN phone_order_users AS user3 ON phn.operator = user3.id
                    WHERE 1 
                    AND MONTH(phn.date) = MONTH('{$date}') AND YEAR(phn.date) = YEAR('{$date}')
                    and user3.operatorGroup = '{$group}'
                    ORDER BY phn.id DESC";

        $results = $this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     * Total Outbound poziva za bi se izracunao cost per call i cost per order
     */
    public function getTotalOutbounds($month,$year,$group){

        $date = $year."-".$month."-01";

        $sql     = "SELECT SUM(IF( phn.status = 7, 1, 0)) as orderedNum, SUM(IF( phn.status = 6 OR phn.status = 7 OR phn.status = 9 OR phn.status = 12, 1, 0)) as totalOutbound
                    FROM `phone_order_outbound` AS `phn`
                    LEFT JOIN orders ON (phn.submitID = orders.submitId AND phn.submitID != 0)
                    LEFT JOIN phone_order_users AS user3 ON phn.operator = user3.id
                    WHERE 1 
                    AND MONTH(phn.called_time) = MONTH('{$date}') AND YEAR(phn.called_time) = YEAR('{$date}')
                    AND user3.operatorGroup = '{$group}' 
                    ORDER BY phn.id DESC";

        $results = $this->conn->fetchAssoc($sql);
        return $results;
    }

}