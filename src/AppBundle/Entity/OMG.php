<?php

namespace AppBundle\Entity;

class OMG
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    /*
     * Get Company info / state / sender
     */
    function getCompanyInfo($sqluery = "")
    {
        $sql = "SELECT companiesSupport.state AS code2, states.title_eng AS title_eng, companiesSupport.smsFrom AS smsSender
                 FROM `companiesSupport`
                 LEFT JOIN states ON companiesSupport.state = states.code2
                 WHERE 1 {$sqluery}
                 GROUP BY companiesSupport.state
                 ORDER BY companiesSupport.state ASC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Get states 
     */
    function getStates($sqluery = "")
    {
        $sql = "SELECT id, code2, title_eng, distro_smsFrom AS smsSender FROM `states` WHERE 1 and hasSales = 1 {$sqluery} ORDER BY code2 ASC";
        $results = $this->conn->fetchAll($sql);
        //var_dump($sql);
        return $results;
    }

    /*
     * Get states with statesID
     */
    function getStatesWithId($sqluery = "")
    {
        $sql = "SELECT id, code2, title_eng, distro_smsFrom AS smsSender  FROM `states` WHERE 1 {$sqluery}";
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Get states with statesID
     */
    function getActiveStates()
    {
        $sql = "SELECT phone_order_smsprices.state as code2, states.id, states.title_eng
                FROM `phone_order_smsprices` 
                LEFT JOIN states ON phone_order_smsprices.state= states.code2
                WHERE 1 and phone_order_smsprices.stateIsActive = 1
                ORDER BY states.id";
        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /*
     * Get state Id
     */
    function getStateId($code2)
    {
        $sql = "SELECT id
                FROM `states` 
                WHERE code2 = '$code2'";
        $results = $this->conn->fetchAll($sql);
        return $results[0]['id'];
    }

    /*
     * Get state Id
     */
    function getCampaignId($campName)
    {
        $sql = "SELECT id
                FROM `CampManagement` 
                WHERE CampaignName = '$campName'";
        $results = $this->conn->fetchAll($sql);
        return $results[0]['id'];
    }

    /*
     *  Lista proizvoda
     */
    public function getProductList($columns="*",$sql="1")
    {
        $sql = "SELECT $columns FROM products
                WHERE $sql ORDER BY title ASC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /*
   *  Lista proizvoda
   */
    public function getProductListZeljka($columns="*",$sql="1")
    {
        $sql = "SELECT $columns FROM products
                WHERE $sql ORDER BY title ASC  LIMIT 500 ";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Lista drzava phoneordera
     */

    function getPhoneStates($sqluery = "")
    {
        $sql = "SELECT code2, title_eng FROM `phone_order_smsprices`
                  LEFT JOIN states ON phone_order_smsprices.state = states.code2
                  WHERE 1 {$sqluery}";
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    
    /*
     * Listanje proizvoda 
     */
    function getRows($params=' * ',$table,$condition="productStatus=7 and isPostage!='Yes' and isService!='Yes'")
    {
        $sql = "SELECT $params  FROM `{$table}` WHERE {$condition}  ";
        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    
    /*
     * Salespackages za spisak proizvoda koji se narucuju
     */
    function getSalepackages($state){

        if(strtoupper($state)=='ALL')
        {
            $stateKveri='';
        }
        else
        {
            $stateKveri=" and state LIKE '{$state}' ";

        }
        $sql="SELECT salespackagecode as title,sum(quantity) as quantity,price,product
		FROM `salespackages`
		LEFT JOIN salespackagesproducts ON salespackages.id = salespackagesproducts.salespackage
		LEFT JOIN products ON salespackagesproducts.product = products.id
		WHERE 1 $stateKveri
		AND products.isPostage != 'Yes'
		AND products.isService != 'Yes'
		AND products.productStatus =7 and salespackages.active='Yes' GROUP BY salespackagecode";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /**********************************************************************
     * --- Sender na osnovu state ---------------------------------       *
     **********************************************************************/
    function getSenderId($state){

        $sql="SELECT distro_smsFrom, distro_supportemail, currency_sbl, phone_order_callcenter.phone AS phone, phone_order_callcenter.mail AS mail
        FROM `states`
        LEFT JOIN phone_order_callcenter ON states.code2 = phone_order_callcenter.state
        WHERE code2 = '{$state}' LIMIT 1";

        return $this->conn->fetchAssoc($sql);
    }

    /*
     * Uzimanje cijena postarine
     */
    public function getPostagePrices($state)
    {
        $sql = "SELECT products.title,states.currency_sbl as sbl,round(productvat.price * (1+(productvat.vat_rate/100)),2) as price FROM products 
				  left join productvat on products.id=productvat.product 
				  left join states on productvat.state=states.code2
				  WHERE isPostage='Yes' and productvat.state like '$state'";
        // and products.title LIKE '%POST%'
        $results = $this->conn->fetchAll($sql);
        return $results;

    }
    /**********************************************************************
     * ---- Uzimanje podataka za LP gdje je korisnik uzeo code ----       *
     **********************************************************************/
    public function getLpData($code,$state)
    {
        $code=trim($code);
        $sql = "";
        if ($code == "9999" || $code == "6969" || strlen($code) > 4) {
            $sql = "SELECT * FROM phonecodes WHERE genCode='$code' and `state` like '{$state}' and used='1' order by id desc LIMIT 1";
        }else{
            $sql = "SELECT * FROM phonecodes WHERE genCode='$code' and `state` like '{$state}' and used!='1' order by id desc LIMIT 1";
        }
        return $this->conn->fetchAssoc($sql);
    }
    /**********************************************************************
     * ---- Uzimanje LP podataka sa vec definisanim ID  -----------       *
     **********************************************************************/
    public function getLpDataByID($id)
    {
        $id=intval($id);
        $sql = "SELECT * FROM phonecodes WHERE id='$id' and used!='1' LIMIT 1";
        return $this->conn->fetchAssoc($sql);
    }
    /**********************************************************************
     * -------------- Updateovanje koda kada se iskoristi ---------       *
     **********************************************************************/
    public function setUsed($id)
    {
        $code=trim($id);
        $sql = "UPDATE phonecodes SET used='1' WHERE id='$id' ";
        return $this->conn->executeQuery($sql);
    }
    /**********************************************************************
     * -------------- Upis telefonskog ordera ---------------------       *
     **********************************************************************/
    public function writePhoneOrder($orderType,$state,$code,$start,$end,$duration,$type,$otherOpt,$productWork,$getInvoice,$buyStore,$other,$sucess,$cancel,$cancelRe,$cName,$cSurname,$cAddress,$cCity,$cPhone,$cMail,$korisnik, $proizvod, $submitOrderId, $campaignId="page", $baseInPrice=0.00, $endInPrice=0.00, $sessionId, $cancelStatus, $flowType){
        // $cName = mysql_real_escape_string($cName, $this->db->_connect);
        // $cSurname = mysql_real_escape_string($cSurname, $this->db->_connect);
        // $cAddress = mysql_real_escape_string($cAddress, $this->db->_connect);
        // $cCity = mysql_real_escape_string($cCity, $this->db->_connect);

        $sql = "INSERT INTO phone_order_calls (`orderType`,`state`, `code`, `start`, `end`, `duration`, `type`, `otherOpt`, `productWork`, `getInvoice`, `buyStore`, `other`, `success`, `cancel`, `cancelReason`,
                                                      `cName`, `cSurname`, `cAddress`, `cCity`, `cPhone`, `cMail`, `operator`, `product`, `orderSubmitId`, `campaignId`, `bPrice`, `ePrice`, `sessionID`, `cancelStatus`, `flowType`)
                                              VALUES ('$orderType','$state','$code','$start','$end','$duration',$type,'$otherOpt',$productWork,$getInvoice,$buyStore,'$other','$sucess',$cancel,'$cancelRe',
                                                      '$cName','$cSurname','$cAddress','$cCity','$cPhone','$cMail',$korisnik, $proizvod,$submitOrderId,'$campaignId','$baseInPrice','$endInPrice','$sessionId','$cancelStatus', '$flowType')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }
    /**********************************************************************
     * -------------- Lista ordera za statisku --------------------       *
     **********************************************************************/
    public function getDataRows($columns="*",$sql="1",$period="")
    {
        $sql = "SELECT phone_order_calls.cPhone as cPhone, phone_order_calls.id as id, phone_order_calls.orderSubmitId as orderSubmitId, phone_order_calls.sessionID as sId, phone_order_calls.state AS state, title, code, start, end, duration, otherOpt, other, success,
                         cancelReason, date, type, productWork, getInvoice, buyStore, cancel, product, phone_order_users.name AS opName, special, bPrice, ePrice,
                         phone_order_smsprices.exchange AS exchange, phone_order_users.operatorGroup AS callCenterId, phone_order_calls.orderType, phone_order_calls.cancelStatus AS cancelStatus, phone_order_calls.flowType as flowType, costs.INperCall as callCosts
                  FROM phone_order_calls
                  LEFT JOIN phone_order_orderTypes ON phone_order_calls.orderType = phone_order_orderTypes.id
                  LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                  LEFT JOIN phone_order_smsprices ON phone_order_calls.state = phone_order_smsprices.state
                  LEFT JOIN phone_order_callCenterPrice AS costs ON (phone_order_users.operatorGroup = costs.callCenterId AND costs.period = '{$period}')
				  WHERE {$sql}";
        //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }


    /*
     * States iz phone order outbound
     */
    public function getInboundStateList(){
        $sql = "SELECT DISTINCT phone_order_calls.state
                FROM phone_order_calls
                ORDER BY phone_order_calls.state ASC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Lista specijalnih ponuda
     */
    public function getSpecialOffers($columns="*",$sql="1")
    {
        $sql = "SELECT $columns FROM phone_order_special
                  LEFT JOIN products ON phone_order_special.product = products.id
                  LEFT JOIN products AS OrderedProduct ON phone_order_special.productOrder = OrderedProduct.id
                  LEFT JOIN salespackagesproducts ON phone_order_special.spItem = salespackagesproducts.id
                  LEFT JOIN salespackages ON salespackagesproducts.salespackage = salespackages.id
				  WHERE $sql";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Lista salespackage-a na osnovu kverija
     */
    public function getSPlist($sql=" 1 ")
    {
        $sql = "SELECT * FROM salespackages
                LEFT JOIN salespackagesproducts ON salespackages.id = salespackagesproducts.salespackage
                WHERE $sql";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /**********************************************************************
     * ------------ Uzimanje kurirske sluzbe za odabranu drzavu ---       *
     **********************************************************************/
    public function getCourierByState($state)
    {
        $sql = "SELECT * FROM couriers
                LEFT JOIN profiles ON couriers.id = profiles.courier
                LEFT JOIN states ON profiles.id = states.profileId
                WHERE states.code2='$state' LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }
    /**********************************************************************
     * -------------- Upis nove specijalne ponude -----------------       *
     **********************************************************************/
    public function writeSpecialOffer($Product,$ProductOrd,$state,$salesPack,$offerText){
        $sql = "INSERT INTO phone_order_special (`productOrder`, `product`, `offerText`, `state`, `spItem`)
                                                VALUES ('$ProductOrd', '$Product','$offerText','$state','$salesPack')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }
    /**********************************************************************
     * -------------- Preuzmi tekst za specijalnu ponudu ----------       *
     **********************************************************************/

    public function getOfferText($offerId){
        $sql = "SELECT offerText FROM phone_order_special
                  WHERE id=$offerId LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     * Lista specijalnih ponuda
     */
    public function getSpecialList($State,$Product){
        $sql = "SELECT phone_order_special.id as idNum, phone_order_special.spItem AS sPack, salespackagesproducts.price AS price, salespackagesproducts.quantity AS quantity, products.title as title, states.currency_sbl AS currency, phone_order_special.product as prodId FROM phone_order_special
                INNER JOIN products ON phone_order_special.product = products.Id
                INNER JOIN salespackagesproducts ON phone_order_special.spItem = salespackagesproducts.id
                INNER JOIN states ON phone_order_special.state = states.code2
                WHERE phone_order_special.state='$State' AND productOrder=$Product";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    /**********************************************************************
     * -------------- Osnova za Brisanje iz baze ------------------       *
     **********************************************************************/
    function delete($table,$id)
    {
        $sql="DELETE FROM $table WHERE id='$id'";
        $this->conn->executeQuery($sql);
        $sql="SELECT * FROM $table WHERE id='$id' LIMIT 1";
        $var=$this->conn->fetchAssoc($sql);

        if(empty($var['id']))
        {
            echo 1;
        }else{
            echo -1;
        }
    }

    /**********************************************************************
     * -------------- Uzmi podatke kupca sa PH ordera -------------       *
     **********************************************************************/
    function getPhoneBuyer($buyerId) {
        $sql = "SELECT * FROM phone_order_calls
                  WHERE id=$buyerId LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }
    /**********************************************************************
     * -------------- Uzmi ProfileID by State ---------------------       *
     **********************************************************************/
    function getProfiles($state){

        $sql = "SELECT profiles.id FROM profiles
                  LEFT JOIN states ON profiles.id=states.profileId
                  WHERE states.code2 = '$state' AND profiles.active=1 LIMIT 1";

        $all = $this->conn->fetchAssoc($sql);
        return $all;
    }

    /**********************************************************************
     * -------------- Povecaj Ordered vrijednost  -----------------       *
     **********************************************************************/
    function increaseByOne($table,$field,$compare,$value,$num = 1){

        $sql = "UPDATE $table SET `{$field}`= $field + $num WHERE {$compare} LIKE '$value'";
        $all = $this->conn->executeQuery($sql);
        return $all;
    }

    /*
     * Uzmi proizvode koji su na stanju
     */
    function getAvailableProducts($state){

        $sql = "SELECT products.id as id, products.title as title, (SUM(documentitems.increase_quantity)-SUM(documentitems.decrease_quantity)) as suma,
                       products.sku AS sku, products.productType AS productType FROM `documentitems`
                INNER JOIN documents ON documentitems.document = documents.id
                INNER JOIN products ON documentitems.product = products.id
                WHERE documents.state = '{$state}'
                AND productStatus=7 
                AND products.isPostage!='Yes' 
                AND products.isService!='Yes'
                GROUP BY products.id ORDER BY products.title";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Uzmi proizvode koji su na stanju
     */
    function getProductsOnPanel($state){

        $sql = "SELECT products.id as id, products.title as title, products.sku AS sku, products.productType AS productType, @suma :=1 AS suma FROM `products`
                LEFT JOIN phone_order_prices ON products.id = phone_order_prices.productId
                WHERE phone_order_prices.state = '{$state}'
                AND phone_order_prices.price > 0.00
                AND productStatus = 7
                AND products.isPostage != 'Yes'
                AND products.isService != 'Yes'
                ORDER BY products.title";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Uzmi opise proizvoda koji su na stanju
     */
    function getAvailableDescriptions($state){

        $sql = "SELECT products.id as id, products.title as title, productDescription.productText AS pText FROM `products`
                INNER JOIN productDescription ON products.id = productDescription.ProductId
                WHERE productStatus = 7
                AND products.isPostage != 'Yes'
                AND products.isService != 'Yes'
                AND productDescription.state = '{$state}'";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Uzmi listu aktivnih kampanja
     */
    function getActiveCampaigns($state){

        $sql = "SELECT CampManagement.CampaignName AS CampaignName, products.title AS title, spTab.sPrice
                FROM CampManagement
                INNER JOIN products ON CampManagement.product = products.id
                LEFT JOIN (SELECT product AS spProduct, salespackage AS sPackage, price AS sPrice FROM salespackagesproducts WHERE quantity = 1) AS spTab ON spTab.spProduct = products.id
                LEFT JOIN (SELECT id AS spId, active AS sActive FROM salespackages WHERE state = '{$state}' AND salespackagecode LIKE '%SMS%') saleTab ON saleTab.spId = spTab.sPackage
                WHERE country ='{$state}'
                AND products.isPostage != 'Yes'
                AND products.isService != 'Yes'
                AND products.productStatus =7 
                AND saleTab.sActive='Yes'
                AND CampManagement.active = 1
                ORDER BY CampManagement.id DESC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
    * Uzmi listu kampanja
    */
    function getAllCampaigns($query){

        $sql = "SELECT *
                FROM CampManagement
                WHERE 1 $query ";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
   * Uzmi listu kampanja
   */
    function getAllCampaignsWithProduct($query){

        $sql = "SELECT CampManagement.*, products.title as product_name
                FROM CampManagement
                JOIN products ON CampManagement.product = products.id
                WHERE 1 $query ";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

     /*
     * Get  row from table by ID
     */
    function getRowByID($table, $id){

        $sql = "SELECT *
                FROM $table
                WHERE id = $id ";

        $results = $this->conn->fetchAssoc($sql);
        return $results;
    }

    /**********************************************************************
     * --- Get ID by field  ---------------------------------------       *
     **********************************************************************/

    function getIdByField($table, $sql){
        $sql = "SELECT id FROM {$table}
				WHERE 1 $sql";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     * Get users
     */
    function getUsers()
    {
        $sql = "SELECT id, name, surname FROM `phone_order_users` WHERE 1";
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Get all Bulk orders
     */
    function getBulkOrders($sqluery)
    {
        $sql = "SELECT orders.utm_campaign AS utm_campaign, SUM(orderitems.decrease_quantity) AS quantity FROM orderitems
                  LEFT JOIN orders ON orderitems.order = orders.order_id
                  LEFT JOIN products ON orderitems.product = products.id
				  WHERE 1 {$sqluery}
				  AND orders.utm_source = 'sms'
				  AND LEFT(orders.utm_campaign, 3) = 'sms'
				  AND orders.name NOT LIKE '%test%'
				  AND orders.surname NOT LIKE '%test%'
				  AND products.isPostage != 'Yes'
				  AND products.isService != 'Yes'
				  AND products.productType != '888'
				  AND products.productType != '999'
				  GROUP BY orderitems.order";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Get all Bulk orders
     */
    function getReorderOrders($sqluery)
    {
        $sql = "SELECT orders.utm_campaign AS utm_campaign, SUM(orderitems.decrease_quantity) AS quantity FROM orderitems
                  LEFT JOIN orders ON orderitems.order = orders.order_id
                  LEFT JOIN products ON orderitems.product = products.id
				  WHERE 1 {$sqluery}
				  AND orders.utm_source = 'sms'
				  AND LEFT(orders.utm_campaign, 5) = 'reord'
				  AND orders.name NOT LIKE '%test%'
				  AND orders.surname NOT LIKE '%test%'
				  AND products.isPostage != 'Yes'
				  AND products.isService != 'Yes'
				  AND products.productType != '888'
				  AND products.productType != '999'
				  GROUP BY orderitems.order";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Lista submit tabele
     */
    public function getOrderSubmits($sqluery = "", $defaultId = 350000)
    {

        $fiveMins = Date('Y-m-d H:i:s', strtotime('-5 minutes')); // ostavljamo 5min u slucaju upsella

        $sql = "SELECT * FROM order_submits
                WHERE 1 {$sqluery} AND datetime < '$fiveMins' AND id > $defaultId and domain != 'domain.com'";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Citava Lista submit tabele
     */
    public function getRecentInboundCalls(){

        $sql = "SELECT * FROM phone_order_calls
                WHERE 1 AND phone_order_calls.date > NOW() - INTERVAL 15 MINUTE";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * -- Update starih pending ordera na OTHER (Not Called) status ----- *
     **********************************************************************/
    public function updateOldPendings(){

        $sql = "UPDATE `phone_order_outbound` SET `status`= 13 WHERE (`status`= 0 AND submitDate < NOW() - INTERVAL 5 DAY) OR (`type`= 3 AND `status`= 0 AND submitDate < NOW() - INTERVAL 1 HOUR)";

        $update=$this->conn->executeQuery($sql);
        return $update;
    }

    /**********************************************************************
     * ------------------- Napravi submit upsell ------------------       *
     **********************************************************************/

    public function makeSubmitUpsell($sId,$new_pack){
        exit;
        if($sId > 0)
        {

            $sql = "SELECT post_data from order_submits where id='{$sId}' AND `upsellMade` != '1' limit 1";
            //exit;
            $row=$this->conn->fetchAssoc($sql);

            if(count($row)>0)
            {

                $post_data=(array)json_decode($row['post_data']);

                $old_packet = $post_data['product'];
                $komentar='';
                $post_data['product']= $new_pack;

                $exp1=explode("|", $old_packet);
                $exp2=explode("|", $new_pack);

                $komentar=" [OMG-O] Salespackage upgraded from {$exp1[1]} to {$exp2[1]}";
                $post_data['komentar'].=$komentar;
                $new_data= addslashes(json_encode($post_data,JSON_UNESCAPED_UNICODE));

                $updateKveri="UPDATE order_submits SET post_data = '{$new_data}',`upsellMade`='1' where id='$sId' and `upsellMade` != '1'  ";
                echo $updateKveri;
                //$this->conn->executeQuery($updateKveri);
                //echo mysql_affected_rows();
            }
            else
            {
                //napisi log nije nasao id
            }
            exit;

        }

    }

    /*
     * Hvatanje podataka od broja iz panela operatora
     */
    function checkFromPhone($state,$phone) {
//        $sql = "SELECT * FROM customers
//                  WHERE 1 AND state = '$state' AND phone LIKE '%$phone%' ORDER BY id DESC LIMIT 10";

        $sql = "SELECT orders.*, customers.postoffice AS postoffice FROM orders
			    LEFT JOIN customers ON orders.customer_id = customers.id
				WHERE 1 AND orders.state = '$state' AND customers.phone LIKE '%$phone%' 
				ORDER BY order_id DESC LIMIT 1 ";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * ---- Provera zadnje poslane kampanje -----------------------       *
     **********************************************************************/
    function checkLastCampaign($phone) {
        $phoneFix   = "00".$phone;
        $sql = "SELECT smsMessages.messageId AS messageId, products.title AS sProizvod, reoprod.title AS rProizvod
                  FROM `smsMessages`
                  LEFT JOIN CampManagement ON smsMessages.messageId = CampManagement.CampaignName
                  LEFT JOIN phone_order_reorder ON smsMessages.messageId = phone_order_reorder.CampaignName
                  LEFT JOIN products ON CampManagement.product = products.id
                  LEFT JOIN products as reoprod ON phone_order_reorder.product = reoprod.id
                  WHERE origin = '$phone' OR origin = '$phoneFix' AND (LEFT(messageId, 3) = 'sms' OR LEFT(messageId, 5) = 'reord')
                  ORDER BY smsMessages.id DESC LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }
    /**********************************************************************
     * ---- Unesi nove cijene -----------------------       *
     **********************************************************************/
    function insertNewProductPrice($param1,$param2){
        $sql = "INSERT INTO phone_order_prices (`productId`,`state`,`price`,`upsellPrice`,`type`) VALUES ('{$param2}','{$param1}','0.00','0.00','1')";

        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }
    /**********************************************************************
     * ---- updatuj cijene proizvoda -----------------------       *
     **********************************************************************/
    function updateProductPrice($productId, $state, $price, $upsellPrice){
        $sql = "UPDATE phone_order_prices SET `price`= '$price', `upsellPrice`= '$upsellPrice' WHERE productId = {$productId} AND state LIKE '{$state}' ";

        $update = $this->conn->executeQuery($sql);

        return $update;
    }

    /**********************************************************************
     * -------------- Pocetak trakovanja poziva -------------------       *
     **********************************************************************/
    public function startFlowTrack($state,$operator){
        $sql = "INSERT INTO phone_order_tracker (`state`, `operator`)
                                          VALUES ('$state', '$operator')";

        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }
    /**********************************************************************
     * -------------- POZIV - slusalica dignuta -------------------       *
     **********************************************************************/
    public function panelCallUp($callId,$inspectletId){
        $call=trim($callId);
        $sql = "UPDATE phone_order_tracker SET callUp='1', inspectletID = '$inspectletId' WHERE id='$call' ";
        return $this->conn->executeQuery($sql);
    }
    /**********************************************************************
     * -------------- POZIV - slusalica spustena -------------------       *
     **********************************************************************/
    public function panelCallDown($callId){
        $call=trim($callId);
        $sql = "UPDATE phone_order_tracker SET callEnd='1' WHERE id='$call' ";
        return $this->conn->executeQuery($sql);
    }

    /*
     *  Lista submit tabele
     */
    public function getCanceledOrders(){

        $fourdays = Date('Y-m-d H:i:s', strtotime('-4 days')); // ostavljamo 5min u slucaju upsella

        $sql = "SELECT * FROM orders
                WHERE 1
                AND orderdate > '$fourdays'
                AND orderstatus = 24
                AND (state = 'BA')";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Provera da li ima outbound poziva
     */
    function checkIfHasOutbound($state, $phone)
    {
        $threeBefore = Date("Y-m-d", strtotime('-3 days'));
        $sql = "SELECT *
                  FROM `phone_order_outbound`
                  WHERE state = '$state' AND replace(phone,'/','') LIKE '%$phone%' AND Date(submitDate) > '$threeBefore'
                  ORDER BY phone_order_outbound.id DESC LIMIT 1";
        //echo $sql;
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Info Broj outbound poziva
     */
    public function countOutbound($state)
    {

        $sql = "SELECT COUNT(*) AS broj, phone_order_outbound.type AS Tip
                FROM phone_order_outbound
                LEFT JOIN order_submits ON phone_order_outbound.submitID = order_submits.id
                WHERE 1
                AND (phone_order_outbound.status = 0 OR phone_order_outbound.status = 2 OR phone_order_outbound.status = 9)
                AND (phone_order_outbound.type = 1 OR phone_order_outbound.type = 2 OR phone_order_outbound.type = 5 OR phone_order_outbound.type = 6 OR phone_order_outbound.type = 7 OR phone_order_outbound.type = 8 OR phone_order_outbound.type = 9 OR phone_order_outbound.type = 10 OR phone_order_outbound.type = 11 OR (phone_order_outbound.type = 3 AND phone_order_outbound.submitDate
                > DATE_ADD(NOW(), INTERVAL -50 MINUTE) AND phone_order_outbound.submitDate < DATE_ADD(NOW(), INTERVAL -10 MINUTE)))
                AND submitDate > NOW() - INTERVAL 5 DAY
                AND callCount < 3
                AND phone_order_outbound.state ='{$state}'
                AND (upsellMade = 0 OR upsellMade IS NULL)
                GROUP BY phone_order_outbound.type
                ORDER BY tocall_time DESC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * ---- Brojanje otvorenih poziva  ----------------------------       *
     **********************************************************************/
    function countPanelOpenings($state) {
        $sql = "SELECT Count(*) AS broj
                  FROM `phone_order_tracker`
                  WHERE 1
                  AND state = '{$state}'";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     * Average call value za za order source iz tabele phone_order_calls - podaci za glavni chart
     */
    public function getDataForInboundCharts($statesInbound, $sqluery)
    {
        //var_dump($statesInbound);die();

        $exeptionOrderSources =  array(

            2 => array(
                'name' => 'SMS Bulk',
                'shortName' => 'sms-bulk'
            ),
            5 => array(
                'name' => 'SMS Reorder',
                'shortName' => 'sms-reorder'
            )
        );
        foreach ($exeptionOrderSources as $key=>$value) {

            $sQ = " and phone_order_calls.orderType = '$key' ";
//            if ($key == "2"){
//                $sQ .= " AND campaignId LIKE 'sms%' ";
//            } else if ($key == "5"){
//                $sQ = " and phone_order_calls.orderType = '2' ";
//                $sQ .= " AND campaignId LIKE 'reord%' ";
//            }

            $chartsQuery = $sqluery . $sQ;

            $results = $this->getDataRows("*",$chartsQuery);

            foreach ($results as $result){
                $statesInbound[$result['state']]['revenue-' . $value['shortName']] = $statesInbound[$result['state']]['revenue-' . $value['shortName']] + ($result['ePrice'] / $result['exchange']);

                $statesInbound[$result['state']]['count-' . $value['shortName']]++;
            }
        }
        //var_dump($statesInbound);die();
        return $statesInbound;
    }



    /*
     * $Lista proizvoda SAMO ID I TITLE PROIZVODA
     */
    public function getSimpleProductList($columns="*",$sql="1")
    {
        $sql = "SELECT id, title FROM products
                  WHERE $sql ORDER BY title ASC";


        $results = $this->conn->fetchAll($sql);
        return $results;
    }

//    /*
//    * $Lista proizvoda SAMO ID I TITLE PROIZVODA
//    */
//    public function checkBoughtProducts( $phone,$state)
//    {
//        $sql = "SELECT order_id, product_name, telephone,
//                REPLACE (REPLACE (REPLACE (REPLACE (REPLACE (telephone,  \" \",  \"\" ) ,  \"/\",  \"\" ) ,  \"-\",  \"\" ) ,  \"(\",  \"\" ) ,  \")\",  \"\" ) AS mrs,
//                name, surname
//                FROM orders
//                WHERE 1
//                AND REPLACE (REPLACE (REPLACE (REPLACE (REPLACE (telephone,  \" \",  \"\" ) ,  \"/\",  \"\" ) ,  \"-\",  \"\" ) ,  \"(\",  \"\" ) ,  \")\",  \"\" ) LIKE '%{$phone}'
//                AND  state = '{$state}'
//                ";
//
////
//        $results = $this->conn->fetchAll($sql);
//        return $results;
//    }

    /*
    * $Lista proizvoda SAMO ID I TITLE PROIZVODA
    */
//    public function checkBoughtProducts($phone,$state)
//    {
//        $sql = "SELECT DATE(orders.orderdate) AS orderDate, orders.product_name, orders.product, orders.order_id, 
//                orders.name, orders.surname, documents.orderstatus AS docStatus
//                FROM orders
//                
//                LEFT JOIN documents ON orders.order_no = documents.doc_reference
//                WHERE 1 
//                AND  orders.state = '{$state}'
//                AND phoneorder_digits(orders.telephone) like '%{$phone}'
//                ORDER BY orders.orderdate DESC              
//                ";
//
//       // var_dump($sql);
//        $results = $this->conn->fetchAll($sql);
//        return $results;
//    }

    
    public function checkBoughtProducts($phone,$state)
    {
        $sql = "SELECT DATE(orders.orderdate) AS orderDate, orders.product_name,  orders.product,
                orders.name, orders.surname, documents.orderstatus AS docStatus,orders.telephone,
                orders.submitId, orders.state,orders.ordersource, orders.utm_source, 
                orders.address, orders.city, orders.email,
                IF(orders.utm_campaign like 'sms%' or orders.utm_campaign like 'reord%', orders.utm_campaign,'/') as campaignSource,
                IF(orders.ordersource = 'PHN', 'Phone order','Page') as appSource,
                   IFNULL(
                      round(
                         sum(
                            (
                               decrease_price * (1 -(decrease_discount / 100)) * decrease_quantity
                            ) + (
                               (
                                  decrease_price * (1 -(decrease_discount / 100)) * decrease_quantity
                               ) * (vat_rate / 100)
                            )
                         ),
                         2
                      ),
                      0
                   )  AS productCost, sum(orderitems.decrease_quantity) as quantity
                   
                FROM orders
                LEFT JOIN documents ON orders.order_no = documents.doc_reference
                LEFT JOIN orderitems ON orders.order_id = orderitems.order
                WHERE 1 
                AND  orders.state = '{$state}'
                AND orders.telephone like '%{$phone}'
                GROUP BY orders.order_id
                ORDER BY orders.orderdate DESC
              
                ";

         //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
   * $Lista proizvoda SAMO ID I TITLE PROIZVODA
   */
    public function findOrderToCancel($phone,$state,$product)
    {
        $newResults = array();

        $sql1 = "SELECT DATE(orders.orderdate) AS orderDate, orders.product_name,  orders.product,
                orders.name, orders.surname, documents.orderstatus AS docStatus,orders.telephone,
                orders.submitId, orders.state,orders.ordersource, orders.utm_source, 
                orders.address, orders.city, orders.email,
                IF(orders.utm_campaign like 'sms%' or orders.utm_campaign like 'reord%', orders.utm_campaign,'/') as campaignSource,
                IF(orders.ordersource = 'PHN', 'Phone order','Page') as appSource,
                IF (orders.utm_source = 'mail' OR orders.utm_source = 'mailreorder' OR orders.utm_source = 'mailwarehouse',orders.utm_source, '/' ) as reorderMail,
                IFNULL(
                  round(
                     sum(
                        (
                           decrease_price * (1 -(decrease_discount / 100)) * decrease_quantity
                        ) + (
                           (
                              decrease_price * (1 -(decrease_discount / 100)) * decrease_quantity
                           ) * (vat_rate / 100)
                        )
                     ),
                     2
                  ),
                  0
                ) AS productCost, sum(orderitems.decrease_quantity) as quantity
                   
                FROM orders
                LEFT JOIN documents ON orders.order_no = documents.doc_reference
                LEFT JOIN orderitems ON orders.order_id = orderitems.order
                WHERE 1 
                AND orders.state = '{$state}'
                AND orders.orderdate > DATE (NOW() - INTERVAL 63 DAY)
                AND phoneorder_digits(orders.telephone) like '%{$phone}'
                AND orders.product_name = '{$product}' 
               GROUP BY orders.order_id
                ORDER BY orders.orderdate DESC              
                ";
        //var_dump($sql1);
//        AND (documents.orderstatus is NULL or documents.orderstatus ='S')
        $results1 = $this->conn->fetchAll($sql1);


        foreach ($results1 as $res1){
            $res1['docStatus'] = $this->getDocumentsOrderStatuses($res1['docStatus']);
            $res1['number'] = '-';
            $res1['postal'] = '';
            $res1['priceQuantityToShow'] = $res1['productCost']." | ".$res1['quantity']."x | ".$res1['productCost'];
            $newResults[] = $res1;


        }



        $sql2 = "SELECT id as submitId, country as state,product as product_name, post_data
            FROM order_submits
            WHERE 1 
            AND  country = '{$state}'
            AND post_data like '%{$phone}%'
            AND product = '{$product}'
            ";


        $results2 = $this->conn->fetchAll($sql2);
        foreach ($results2 as $res2) {
           // print_r($res2);
          
            $prepare = array();
            $postdata = json_decode($res2['post_data'],true);
            //print_r($postdata);
            $prepare['submitId']  = $res2['submitId'];
            $prepare['orderDate'] = date('Y-m-d', strtotime($postdata['orderdate']));
            $prepare['name']      = $postdata['name'];
            $prepare['surname']   = $postdata['surname'];
            $prepare['docStatus'] = '/';
            $prepare['telephone'] = $postdata['telephone'];
            $prepare['product']   = $postdata['product'];
            $prepare['product_name'] = $res2['product_name'];
            $prepare['state']     = $res2['state'];
            $prepare['city']      = $postdata['city'];
            $prepare['address']   = $postdata['address'];
            $prepare['number']    = $postdata['number'];
            $prepare['postal']    = $postdata['postal'];
            $prepare['email']     = $postdata['email'];
            if(substr_count($postdata['product'], '|') == 2){
                $prepare['maybe_price'] = $postdata['product'];
            } else{
                $prepare['maybe_price'] = $postdata['product'];
            }

            if ($postdata['ordersource'] == 'PHN') {
                $prepare['appSource'] = 'Phone order';
            } else {
                $prepare['appSource'] = 'Page';
            }

            $landingPage = explode('?', $postdata['landingpage']);
            $landingPage = parse_str($landingPage[1]);
            if (strpos($landingPage['utm_campaign'], 'sms') || strpos($landingPage['utm_campaign'], 'reord')) {
                $prepare['campaignSource'] = $landingPage['utm_campaign'];
            } else {
                $prepare['campaignSource'] = '/';
            }
            $newResults[] = $prepare;
            //print_r($prepare);

        }
        array_multisort(array_column($newResults, 'orderDate'), SORT_DESC, $newResults);
        $results = $newResults;
        return $results;
    }

    public function getOrdersFromPhoneBySubmitId($submitId){
        $sql = "SELECT submitID, 
                FROM phone_order_outbound
                WHERE sumbitID = '{$submitId}'
                ";
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    public function getDocumentsOrderStatuses($status){
        
        $statusesDefault = array(
            'S',
            'O',
            'C',
            'R',
            'M',
            'L',
            'D',
            'F'
        );
        if(in_array($status, $statusesDefault)){
            $sql = "SELECT description 
                    FROM `documentstatuses`
                    WHERE statuscode ='{$status}'
                    LIMIT 1";
            $results = $this->conn-> fetchAssoc($sql);
            return $results['description'];
        } else {
            return '/';
        }
       
       
    }

    public function cancelOrderBySubmitIDinPhoneAndOmg($submitId){
        
        if($submitId != 0){
            $sql1 = "SELECT post_data
                 FROM order_submits
                 WHERE id ='$submitId' ";
            $orderSubmits = $this->conn->fetchAssoc($sql1);

            if (!empty($orderSubmits)){
                $postData = json_decode($orderSubmits['post_data'], true);
                $postData['temp_cancel'] = 1;
                $newPostData = addslashes(json_encode($postData, JSON_UNESCAPED_UNICODE));


                $sql2 = "UPDATE `order_submits` SET `post_data`= '$newPostData' WHERE id ='$submitId' LIMIT 1 ";
                $this->conn->executeQuery($sql2);
            }



//
//        $sql3 = "UPDATE `orders` SET `orderstatus`= 8 WHERE submitId ='$submitId' LIMIT 1";
//        $this->conn->executeQuery($sql3);



            $sql4 = "UPDATE `phone_order_calls` 
                     SET `success`= 'CANCELED!', `cancel` = 1, `cancelReason` = 'Accepted NEW Offer', `cancelStatus` = 5
                     WHERE orderSubmitId ='$submitId' LIMIT 1";
            $this->conn->executeQuery($sql4);
        }
       
    }
}


