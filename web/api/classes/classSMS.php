<?php
/**********************************************************************
 *																	  *
 * --------- SMS BULK Management klasa ------------------------       *
 * 																	  *
 * 	@Author Boris  													  *
 *  09/2015															  *
 **********************************************************************/
include_once ("classPARAMETERS.php");

class SMS extends PARAMETERS {
 /**********************************************************************
 * ------------------------  --------------------       *
 **********************************************************************/

	public function __construct($db) {
        if ($db) {
            $this->db = $db;
        }
    }

/**********************************************************************
 * --------- Funkcija za listanje SMS kampanja ----------------       *
 **********************************************************************/

	public function getCampaignList($query="") {
		$sql = "SELECT Country, CampaignName, products.title as title, SenderId, Datesend, Datemade, Message, status, CampManagement.id as id, RecipientNo, sent, delivered,
                       Orders, NotOrders, productSent, selectedMessages, price, upsellPrice, active FROM CampManagement
                INNER JOIN products ON CampManagement.product = products.id
				WHERE 1 {$query} ORDER BY CampManagement.Datesend DESC";
		$results=$this->db->query($sql,2);
        return $results;
	}
/**********************************************************************
 * --------- Funkcija za listanje REORDER SMS kampanja ---------------*
 **********************************************************************/

    public function getReorderCampaignListAll($query="") {
        $sql = "SELECT Country, CampaignName, products.title as title, states.distro_smsFrom as SenderId, Datesend, Message, phone_order_reorder.id as id, sent,
                        delivered, Orders, NotOrders, productSent, afterDays, dayHour, price, upsellPrice, active FROM phone_order_reorder
                INNER JOIN products ON phone_order_reorder.product = products.id
                INNER JOIN states ON phone_order_reorder.Country = states.code2
				WHERE 1 {$query} ORDER BY phone_order_reorder.Datesend DESC";

        $results=$this->db->query($sql,2);
        return $results;
    }


/****************************************************************************************
 * ---------NEW Funkcija za listanje REORDER SMS kampanja SVE---------------*
 ****************************************************************************************/

    public function getReorderCampaignList($query="") {
        $sql = "SELECT Count(*) as Orders, phone_order_reorder.Country AS Country, phone_order_reorder.CampaignName AS CampaignName, products.title as title, states.distro_smsFrom as SenderId, phone_order_reorder.Datesend AS Datesend,
                       phone_order_reorder.Message AS Message, phone_order_reorder.id as id, afterDays, dayHour, price, upsellPrice, active
                FROM phone_order_calls
                LEFT JOIN phone_order_reorder ON phone_order_calls.campaignId = phone_order_reorder.CampaignName
                INNER JOIN products ON phone_order_reorder.product = products.id
                INNER JOIN states ON phone_order_reorder.Country = states.code2
				WHERE 1 {$query} GROUP BY phone_order_calls.campaignId ORDER BY phone_order_reorder.Datesend DESC";

        $results=$this->db->query($sql,2);
        return $results;
    }
/****************************************************************************************
 * ---------NEW Funkcija za listanje REORDER SMS kampanja STATUS ordered ---------------*
 ****************************************************************************************/

    public function getReorderCampaignListOrder($query="") {
        $sql = "SELECT Count(*) as Orders, phone_order_reorder.CampaignName AS CampaignName
                FROM phone_order_calls
                LEFT JOIN phone_order_reorder ON phone_order_calls.campaignId = phone_order_reorder.CampaignName
				WHERE 1 {$query} AND type = 1 AND cancel = 0 GROUP BY phone_order_calls.campaignId ORDER BY phone_order_reorder.Datesend DESC";
        $results=$this->db->query($sql,2);
        return $results;
    }

/****************************************************************************************
 * ---------NEW Funkcija za listanje BULK SMS kampanja STATUS ordered ---------------*
 ****************************************************************************************/

    public function getBulkCampaignListOrder($query="") {
        $sql = "SELECT Count(*) as Orders, CampManagement.CampaignName AS CampaignName
                FROM phone_order_calls
                LEFT JOIN CampManagement ON phone_order_calls.campaignId = CampManagement.CampaignName
				WHERE 1 {$query}
				AND type = 1
				AND cancel = 0
				GROUP BY phone_order_calls.campaignId
				ORDER BY CampManagement.Datesend DESC";
        $results=$this->db->query($sql,2);
        return $results;
    }
/****************************************************************************************
 * ---------NEW Funkcija za listanje REORDER SMS kampanja status no order---------------*
 ****************************************************************************************/

    public function getReorderCampaignListNoOrder($query="") {
        $sql = "SELECT Count(*) as noOrders, phone_order_reorder.CampaignName AS CampaignName
                FROM phone_order_calls
                LEFT JOIN phone_order_reorder ON phone_order_calls.campaignId = phone_order_reorder.CampaignName
				WHERE 1 {$query} AND (type = 2 OR cancel = 1) GROUP BY phone_order_calls.campaignId";
        $results=$this->db->query($sql,2);
        return $results;
    }

/****************************************************************************************
 * ---------NEW Funkcija za listanje BULK SMS kampanja status no order---------------*
 ****************************************************************************************/

    public function getBulkCampaignListNoOrder($query="") {
        $sql = "SELECT Count(*) as noOrders, CampManagement.CampaignName AS CampaignName
                FROM phone_order_calls
                LEFT JOIN CampManagement ON phone_order_calls.campaignId = CampManagement.CampaignName
				WHERE 1 {$query}
				AND (type = 2 OR cancel = 1)
				GROUP BY phone_order_calls.campaignId";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * -------------- Upis nove kampanje --------------------------       *
 **********************************************************************/
    public function writeCampaign($state,$campName,$product,$recNum,$senderId,$sentDate,$messText,$upsellText,$selectedMessages,$perHour, $price, $fullsku, $freeShipping,
                                  $upsellPrice, $minForFreeShip, $product2, $product3, $noProduct, $noproduct2, $noproduct3, $buyF, $buyT, $isSplit, $campLink){
        $orders = 0;
        $notOrders = 0;
        $activated = 1;
        if($isSplit == 1) {
            $activated = 0;
        }


        $writekveri = "INSERT INTO CampManagement (`CampaignName`, `Country`, `RecipientNo`, `product`, `Message`, `SenderId`, `Datemade`, `Datesend`, `Orders`, `NotOrders`, `status`, `upsellText`,`selectedMessages`,
                                                   `perHour`,`messageCount`,`active`,`price`,`fullsku`,`upsellPrice`,`freeShipping`,`minForFreeShip`,`include1`,`include2`,`include3`,`exclude1`,`exclude2`,`exclude3`,`boughtFrom`,`boughtTo`,`splitType`,`campURL`)
                                           VALUES ('$campName', '$state','$recNum', $product, '$messText','$senderId','$sentDate','$sentDate','$orders','$notOrders','Prepared','$upsellText','$selectedMessages', $perHour, 0, '$activated','$price','$fullsku',
                                                   '$upsellPrice', '$freeShipping', '$minForFreeShip', $product, '$product2', '$product3', '$noProduct', '$noproduct2', '$noproduct3', '$buyF', '$buyT','$isSplit','$campLink')";

//        echo $writekveri;
//        exit;
        $this->db->query($writekveri,1);
        $upis = mysql_insert_id();

        $myFile = fopen('/var/www/sites/instanio.com/htdocs/dev/bulksms/reports/undelivered/'.$campName.'.csv', 'w');
        chmod('/var/www/sites/instanio.com/htdocs/dev/bulksms/reports/undelivered/'.$campName.'.csv', 0777);

        fclose($myFile);
        return mysql_insert_id();
    }
/**********************************************************************
 * -------------- Upis nove REORDER kampanje -----------------------  *
 **********************************************************************/
    public function writeReorderCampaign($state,$campName,$product,$afterDays,$dayHour,$sentDate,$messText,$active,$selectedM, $price, $freeShipping, $upsellPrice, $minForFreeShip, $campLink){
        $orders = 0;
        $notOrders = 0;
        //$messText = mysql_real_escape_string($messText, $this->db->_connect);
        $writekveri = "INSERT INTO phone_order_reorder (`CampaignName`, `Country`, `afterDays`, `dayHour`,`product`, `Message`, `Datesend`, `Orders`, `NotOrders`, `active`,`selectedMessage`,`price`,`upsellPrice`,`freeShipping`,`minForFreeShip`,`siteURL`)
                                                VALUES ('$campName', '$state','$afterDays', '$dayHour',$product, '$messText', '$sentDate','$orders','$notOrders','$active','$selectedM','$price', '$upsellPrice', '$freeShipping', '$minForFreeShip', '$campLink')";
        $this->db->query($writekveri,1);
//        $upis = mysql_insert_id();
//
//        $myFile = fopen('/var/www/sites/instanio.com/htdocs/dev/bulksms/reports/undelivered/'.$campName.'.csv', 'w');
//        chmod('/var/www/sites/instanio.com/htdocs/dev/bulksms/reports/undelivered/'.$campName.'.csv', 0777);
//
//        fclose($myFile);
        echo mysql_insert_id();
    }
/**********************************************************************
 * --- Izlistaj kampanje za operatora  ------------------------       *
 **********************************************************************/
    public function getCampaigns($state, $orderMethod="CampManagement.id DESC") {
        $sql = "SELECT CampManagement.id AS id, CampManagement.CampaignName AS CampaignName, CampManagement.Country AS Country, products.title AS title, products.id AS prId, CampManagement.upsellText AS upsellText, CampManagement.active AS active FROM CampManagement
                INNER JOIN products ON CampManagement.product = products.id
				WHERE Country='$state' ORDER BY {$orderMethod}";
        $results=$this->db->query($sql,2);
        return $results;
    }

/**********************************************************************
 * --- Izvlacenje informacija za kampanju ---------------------       *
 **********************************************************************/
    public function getCampaignInfo($campaignName) {
        $sql = "SELECT id, CampaignName, upsellText FROM CampManagement
               	WHERE CampaignName = '{$campaignName}' LIMIT 1";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * --- Izlistaj reorder kampanje za operatora  ----------------       *
 **********************************************************************/
public function getReorderCampaigns($state) {
    $sql = "SELECT phone_order_reorder.id AS id, phone_order_reorder.CampaignName AS CampaignName, phone_order_reorder.Country AS Country, products.title AS title FROM phone_order_reorder
            INNER JOIN products ON phone_order_reorder.product = products.id
            WHERE Country='$state' ORDER BY phone_order_reorder.id DESC";
    $results=$this->db->query($sql,2);
    return $results;
}
/**********************************************************************
 * --- Izlistaj odjavljene brojeve sa  ------------------------       *
 **********************************************************************/

    public function getSuppressionList($scQ) {
        $sql = "SELECT * FROM suppressionList
                WHERE 1 {$scQ} ORDER BY id DESC";
        $results=$this->db->query($sql,2);
        return $results;
    }

/**********************************************************************
* -------------- Upis novog broja za odjavu -------------------       *
**********************************************************************/
    public function writeSuppression($state,$phNum, $type="0"){
        $writekveri = "INSERT INTO suppressionList (`number`, `state`, `type`)
                                                VALUES ('$phNum', '$state', '$type')";
        $suppression  = $this->db->query($writekveri,1);
        //echo mysql_insert_id();
        return $suppression;
    }

/**********************************************************************
 * ---  Filtrirana lista odjavljenih brojeva  -----------------       *
 **********************************************************************/

    public function getFilteredSuppressionList($scQ) {
        $sql = "SELECT * FROM suppressionList
                WHERE 1 {$scQ} ORDER BY id DESC";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * ------------- Odjavljivanje Emaila ======-------------------       *
 **********************************************************************/
    public function unsubMail($state,$mail,$support){
    // The message
            $to      = $support;
            $subject = 'Unsubscribe phone or email / Odjava telefona ili emaila';
            $message = $mail;
           // var_dump($to." ".$subject." ".$message);exit;
    // Send
         mail($to, $subject, $message);
         echo "1";
    }
/**********************************************************************
 * ------------- Pitanje operatera na mail --------------------       *
 **********************************************************************/
    public function faqMail($subject,$from,$to,$mailText){
    // The message
           var_dump($to." ".$subject." ".$mailText);exit;
    // Send
         mail($to, $subject, $mailText);
         echo "1";
    }
/**********************************************************************
 * -------------- Update polja --------------------------------       *
 **********************************************************************/
    public function changeFieldValue($id,$table,$field,$value){
        $updatekveri = "UPDATE `{$table}` SET `{$field}`='{$value}' WHERE id={$id}";
        $all = $this->db->query($updatekveri,1);

        if ($all){
            echo "1";
        } else {
            echo "-1";
        }
    }
/**********************************************************************
* -------------- Uvecavanje polja za 1 jedinicu ---------------       *
**********************************************************************/
    public function incrementField($table,$field,$kveri){
        $updatekveri = "UPDATE `{$table}` SET `{$field}`= {$field} + 1 WHERE 1 {$kveri}";
        $all = $this->db->query($updatekveri,1);
        if ($all){
            echo "1";
        } else {
            echo "-1";
        }
    }

/**********************************************************************
 * --------- Izdvajanje mobilnog broja ako su dva upisana -----       *
 **********************************************************************/
    public function pullCellNumber ($phoneNum, $state, $reportNum){

        $alowedNo = array(
            'BA' => Array('38760','38761','38762','38763','38764','38765','38766','38767','060','061','062','063','064','065','066','067','60','61','62','63','64','65','66','67'),
            'RS' => Array('38160','38161','38162','38163','38164','38165','38166','38167','38168','38169','060','061','062','063','064','065','066','067','068','069','60','61','62','63','64','65','66','67','68','69'),
            'HR' => Array('38598','38599','38591','38595','385970','385976','385977','385979','38592','091','092','095','0970','0976','0977','0979','098','099','91','92','95','970','976','977','979','98','99'),
            'MK' => Array('38970','38971','38972','38973','38975','38976','38977','38978','070','071','072','073','075','076','077','078','70','71','72','73','75','76','77','78'),
            'BG' => Array('35987','35988','35989','359984','359985','359986','359987','359988','359989','087','088','089','0984','0985','0986','0987','0988','0989','87','88','89','984','985','986','987','988','989'),
            'SI' => Array('38630','38631','38640','38641','38651','38664','38668','38670','38671','030','031','040','041','051','064','068','070','071','30','31','40','41','51','64','68','70','71'),
            'IT' => Array('3932','3933','3934','3935','3936','3937','3938','3939','3510','032','033','034','036','037','038','039','32','33','34','36','37','38','39'),
            'SK' => Array('42190','42191','42194','090','091','094','90','91','94'),
            'PL' => Array('4850','4851','4853','4857','4860','4866','4869','4872','4873','4878','4879','4888','050','051','053','057','060','066','069','072','073','078','079','088','50','51','53','57','60','66','69','72','73','78','79','88'),
            'GR' => Array('30685','30690','30691','30693','30694','30695','30696','30697','30698','30699','685','690','691','693','694','695','696','697','698','699'),
            'LV' => Array('3712','20','21','22','23','24','25','26','27','28','29'),
            'LT' => Array('37086','3706','86','60','61','62','63','61','64','65','66','67','68','69'),
            'AT' => Array('43650','43660','43664','43676','43677','43680','43681','43688','43699','0650','0660','0664','0676','0677','0680','0681','0688','0699','650','660','664','676','677','680','681','688','699'),
            'HU' => Array('3620','3630','3631','3670','020','030','031','070','20','30','31','70'),
            'CZ' => Array('42070','42072','42073','42077','42079','42091','420601','420602','420603','420604','420605','420606','420607','420608','070','072','073','077','079','091','0601','0602','0603','0604','0605','0606','0607','0608','70','72','73','77','79','91','601','602','603','604','605','606','607','608'),
            'RO' => Array('4071','4072','4073','4074','4075','4076','4077','4078','4079','071','072','073','074','075','076','077','078','079','71','72','73','74','75','76','77','78','79'),
            'DE' => Array('4915','4916','4917','015','016','017','15','16','17'),
            'EE' => Array('37250','37251','37252','37253','37254','37255','37256','37257','37258','37259','37281','37282','37283','050','051','052','053','054','055','056','057','058','059','081','082','083','50','51','52','53','54','55','56','57','58','59','81','82','83')
        );
        $splitPhone = "";
        $pulledNum = "";

        //Razdvajanje brojeva zavisno od toga kojim su znakom razdvojeni
        if (strpos($phoneNum,' ') !== false) {
            $splitPhone = explode(" ", $phoneNum);
        } else if (strpos($phoneNum,',') !== false) {
            $splitPhone = explode(",", $phoneNum);
        } else if (strpos($phoneNum,';') !== false) {
            $splitPhone = explode(";", $phoneNum);
        } else if (strpos($phoneNum,'+') !== false) {
            $splitPhone = explode("+", $phoneNum);
        } else {
            $splitPhone = Array($phoneNum);
        }

        foreach ($splitPhone as $brojevi) {

            $brojInt = preg_replace('/[^0-9]/', '', $brojevi);

            if (substr($brojInt, 0, 2) == "00"){
                $brojInt = substr($brojInt, 2);
            }

            foreach($this->_allowedArr[$state] as $area){

                $duzina = strlen($area);
                $cutPhone = substr($brojInt, 0, $duzina);

                if ($area == $cutPhone) {
                    $pulledNum = $brojevi;

                    break;
                }
            }
        }
        $pulledNum = preg_replace('/[^0-9]/', '', $pulledNum);
        $pulledNum = str_replace(' ', "", $pulledNum);

        return $pulledNum;
    }
/**********************************************************************
 * --------- Uzimanje brojeva kupaca za SMS bulk --------------       *
 **********************************************************************/
    /**********************************************************************
     * ------------------------ Ocisti imena ----------------------       *
     **********************************************************************/
    public function getCustomerNumbers($campName,$state,$product1,$product2,$product3,$noproduct1,$noproduct2,$noproduct3,$buyF,$buyT,$exclude1,$exclude2,$isSplit){

        function cleanUTF($name,$statecode){
            $name = str_replace(array('š','č','đ','č','ć','ž','ñ','â','î','ă','ő','ř','í','á','ł','ż','ň','ó','ů','ě','ј','ѓ','ρ','κ','ľ','ą','ĺ','ń','ș','ď','ț','ā','ý','ė','ú','ē','ī','ū','ģ','ņ','ļ','ę'),array('s','c','dj','c','c','z','n','','i','a','o','r','i','a','l','z','n','o','u','e','j','g','r','k','l','a','l','n','s','d','t','a','y','e','u','e','i','u','g','n','l','e'), $name);
            $name = str_replace(array('Š','Č','Đ','Č','Ć','Ž','Ñ','Â',' ','–','Î','Ă','Ő','Ř','Í','Á','Ł','Ż','Ň','Ó','Ů','Ě','Ј','Ѓ','Ρ','Κ','Ľ','Ą','Ĺ','Ń','Ș','Ď','—','Ț','Ā','Ý','Ė','Ú','Ē','Ī','Ū','Ģ','Ņ','Ļ','Ę'),array('S','C','D','C','C','Z','N','',' ','-','I','A','O','R','I','A','L','Z','N','O','U','E','J','G','R','K','L','A','L','N','S','D','-','T','A','Y','E','U','E','I','U','G','N','L','E'), $name);
            //if ($statecode != "BG" && $statecode != "MK") {
            if ($statecode != "BG") {
                $name = str_replace(array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','љ','м','н','њ','о','п','р','с','т','у','ф','х','ц','ч','џ','ш','щ','ъ','ы','ь','э','ю','я','ѝ','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','Љ','М','Н','Њ','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Џ','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'),
                                    array('a','b','v','g','d','e','e','z','z','i','j','k','l','lj','m','n','nj','o','p','r','s','t','u','f','h','c','c','dz','s','s','i','j','j','e','ju','ja','i','A','B','V','G','D','E','E','Z','Z','I','J','K','L','Lj','M','N','Nj','O','P','R','S','T','U','F','H','C','C','Dz','S','S','I','J','J','E','Ju','Ja'), $name);
            }

            //if ($state !== "GR"){
                $name = str_replace(array('α','β','γ','δ','ε','ζ','η','θ','ι','κ','λ','μ','ν','ξ','ο','π','ρ','σ','τ','υ','φ','χ','ψ','ω'),array('a','b','g','d','e','z','h','th','i','k','l','m','n','x','o','p','r','s','t','y','f','ch','ps','w'), $name);
                $name = str_replace(array('Α','Β','Γ','Δ','Ε','Ζ','Η','Θ','Ι','Κ','Λ','Μ','Ν','Ξ','Ο','Π','Ρ','Σ','Τ','Υ','Φ','Χ','Ψ','Ω'),array('A','B','G','D','E','Z','H','TH','I','K','L','M','N','X','O','P','R','S','T','Y','F','CH','PS','W'), $name);
            //}
            return $name;
        }


        /**********************************************************************
         * --------- Upit na bazu za izvlacenje brojeva ---------------       *
         **********************************************************************/
        $sql = "SELECT customers.namesurname AS custName, customers.phone AS custPhone, products.title, documents.orderdate AS orderdate, orders.utm_source as utms,
                SUBSTRING_INDEX(GROUP_CONCAT(`utm_source`), ',', 1) AS lastutm
                FROM `documents`
                LEFT JOIN customers ON documents.customer = customers.id
                INNER JOIN documentitems ON documents.id = documentitems.document
                INNER JOIN products ON documentitems.product = products.id
                INNER JOIN orders ON documents.id = orders.referenceinvoice
                WHERE documents.doc_type = 1
                AND documents.state LIKE '$state'
                AND orders.paymentmethod != 'AMZ'
                AND DATE(documents.orderdate) > '{$buyF}'
                AND DATE(documents.orderdate) < '{$buyT}'
                {$exclude1}
                {$exclude2}
                AND ({$product1} {$product2} {$product3} {$noproduct1} {$noproduct2} {$noproduct3})
                GROUP BY customers.phone";

        $exQuery = "";
        $exCondition = "";
        if ($noproduct1 != ""){
            $exQuery = "LEFT JOIN ( SELECT customers.email FROM customers
                            JOIN documents ON documents.customer = customers.id
                            LEFT JOIN documentitems ON documents.id = documentitems.document
                            WHERE 1
                            AND ({$noproduct1} {$noproduct2} {$noproduct3})
                            AND documents.state = '$state'
                            AND DATE(documents.orderdate) > '{$buyF}'
                            AND DATE(documents.orderdate) < NOW()
                            {$exclude1}
                            {$exclude2}
                            AND documents.doc_type = 1 ) AS exclusion ON exclusion.email = customers.email";

            $exCondition = " AND exclusion.email IS NULL ";
        }


        $sql2 = "SELECT customers.namesurname AS custName, customers.phone AS custPhone, customers.email
                    FROM customers
                    JOIN documents ON documents.customer = customers.id
                    LEFT JOIN documentitems ON documents.id = documentitems.document
                    LEFT JOIN orders ON documents.id = orders.referenceinvoice
                    {$exQuery}

                    LEFT JOIN ( SELECT customers.email FROM customers
                            JOIN documents ON documents.customer = customers.id
                            LEFT JOIN orders ON documents.id = orders.referenceinvoice
                            LEFT JOIN documentitems ON documents.id = documentitems.document
                            WHERE 1
                            AND ({$product1} {$product2} {$product3})
                            AND documents.state = '$state'
                            AND DATE(documents.orderdate) > '{$buyT}'
                            AND DATE(documents.orderdate) < NOW()
                            AND orders.utm_source = 'sms' ) AS hassms ON hassms.email = customers.email

                    WHERE 1
                    AND ({$product1} {$product2} {$product3})
                    AND documents.state = '$state'
                    AND DATE(documents.orderdate) > '{$buyF}'
                    AND DATE(documents.orderdate) < '{$buyT}'
                    {$exclude1}
                    {$exclude2}
                    AND documents.doc_type = 1
                    AND orders.paymentmethod != 'AMZ'
                    {$exCondition}
                    AND hassms.email IS NULL
                    GROUP BY customers.email";

        if ($isSplit == 1){
            //print_r($sql2);
        }
// SUBSTRING_INDEX(GROUP_CONCAT(`utm_source` ORDER BY `orderdate` DESC), ',', 1) AS lastutm  // ovo je skinuto zbog duplog orderdate fielda
        $all = $this->db->query($sql2,2);

        //Niz dozvoljenih brojeva za svaku drzavu
        $areaLen = strlen($this->_areaCodes[$state]);

        $reportNum = Array();
        //$reportNum[0] = $row['custPhone'];
        $brojevi = "";
        $count = 0;
        $repairNum = "";
        $importedNum = 0;
        $removedNum = 0;
        $doubleNum = 0;
            foreach($all as $row){

                $imeprezime = explode(" ", $row['custName']); //podijeli ime i prezime iz baze na dva dijela

                $samoIme = $imeprezime[0]; //izvuci samo ime
                $samoIme = urldecode($samoIme);
                $ime = cleanUTF($samoIme,$state);

                $phoneNum = $row['custPhone'];

                //1. report za popravku--------
                    $repairNum .= $phoneNum;
                    $importedNum++;
                //END report za popravku----

                $phone = $phoneNum;
                    if (strlen($phone) > 15){

                        $phone = $this->pullCellNumber($phoneNum, $state, $reportNum); // Ako broj duzi od 15, pretpostavka je da su dva broja upisana. Upotrijebi funkciju za izvlacenje mobilnog
                            //2. report za popravku--------
                                $repairNum .= " > ".$phone;
                                $doubleNum++;
                            //END report za popravku----
                    }

                if (substr($phone, 0, 2) == "00"){
                    $phone = substr($phone, 2);
                }

                $areaCheck = substr($phone, 0, $areaLen);

                if ($areaCheck == $this->_areaCodes[$state]){
                    $phone = substr($phone, $areaLen);
                }

                // Exception za prefix 06 u Madjarskoj
                if ($state == "HU"){
                    $checkHU = substr($phone, 0, 2);
                    if ($checkHU == "06") {
                        $phone = substr($phone, 2);
                    }
                }
                // Exception za prefix 8 u Litvaniji
                if ($state == "LT"){
                    $checkLT = substr($phone, 0, 1);
                    if ($checkLT == "8") {
                        $phone = substr($phone, 1);
                    }
                }

                $potvrda = false;
                $duzina = 0;

                //Poredjenje prvih brojeva sa nizom dozvoljenih brojeva za drzave i vraca TRUE ako je broj pronadjen
                foreach($this->_allowedArr[$state] as $area){

                    $duzina = strlen($area);
                    $cutPhone = substr($phone, 0, $duzina);

                    if ($area == $cutPhone) {
                        $potvrda = true;
                        break;
                    }
                }
                    if ($duzina == 3 && substr($phone, 0, 1) == 0){
                        $phone = substr($phone, 1);
                    }
//                    else if ($duzina == 4){
//                        $phone = substr($phone, 1);
//                    } else if ($duzina == 5){
//                        $phone = substr($phone, 3);
//                    }
                    $repairNum .= " > ".$phone;
                    $phoneCompare = str_replace(' ', "", $phone);
                //Zavrsno sredjivanje, brisanje whitespacea i formiranje pravilnog broja za unos u csv
                // postavljen je uslov za provjeru da li je korisnik vec imao sms narudzbi. ako jeste, broj se odbacuje
                if ($potvrda && $row['lastutm'] != "sms" && strpos($brojevi,(string)$phoneCompare) == false){
                    $count++;
                    $phoneFinal = str_replace(' ', "", $phone);
                    $repairNum .= " > ".$phoneFinal."\n";
                    $brojevi .= $ime.",".$phoneFinal."\n";
                   
                } else {
                    $repairNum .= "\n";
                    $removedNum++;
                }
            }
        if ($isSplit == 1){
            $folder = "split";
        } else {
            $folder = "csv";
        }
        if ($count > 0){
            $myFile = fopen('../includes/'.$folder.'/'.$campName.'.csv', 'w');
            chmod('../includes/'.$folder.'/'.$campName.'.csv', 0777);
            file_put_contents('../includes/'.$folder.'/'.$campName.'.csv', $brojevi);
            fclose($myFile);

            $myFile2 = fopen($this->_includesFolderInst.''.$folder.'/'.$campName.'.csv', 'w');
            chmod($this->_includesFolderInst.''.$folder.'/'.$campName.'.csv', 0777);
            file_put_contents($this->_includesFolderInst.''.$folder.'/'.$campName.'.csv', $brojevi);
            fclose($myFile2);

            $repairNum .= "------------------------------------------------------ \n";
            $repairNum .= "Total filtered: ".$importedNum."\n";
            $repairNum .= "Numbers exported: ".$count."\n";
            $repairNum .= "Removed (not mobile or invalid): ".$removedNum."\n";
            $repairNum .= "Double numbers fixed to mobile: ".$doubleNum."\n";

            $reportFile =  fopen('../reports/phoneImport/'.$campName.'.txt', 'w');
            chmod('../reports/phoneImport/'.$campName.'.txt', 0777);
            file_put_contents('../reports/phoneImport/'.$campName.'.txt', $repairNum);
            fclose($myFile);

            session_start();
            $_SESSION['phUser']['sms'] = $count;

            echo $count;
        } else {
            echo "0";
        }


    }

/**********************************************************************
 * -------------- Brojanje poruka sent/delivered ---------------       *
 **********************************************************************/
    public function countMessages($kveri = ""){
        $sql = "SELECT messageId, COUNT(*) as broj, SUM(smsCount) AS smsCount FROM smsMessages
                WHERE 1 {$kveri} AND messageId LIKE '%reord%' GROUP BY messageId";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * -------------- Brojanje poruka sent/delivered ---------------       *
 **********************************************************************/
    public function countBulkMessages($kveri = ""){
        $sql = "SELECT messageId, COUNT(*) as broj, SUM(smsCount) AS smsCount FROM smsMessages
                WHERE 1 {$kveri} AND messageId LIKE 'sms%' GROUP BY messageId";
        $results=$this->db->query($sql,2);
        return $results;
    }

/**********************************************************************
 * -------------- Listanje SMS poruka -------------------------       *
 **********************************************************************/
    public function getMessageList($query="") {
        $sql = "SELECT phone_order_messages.ID AS id, phone_order_messages.message AS message, phone_order_messages.mLength AS mLength, products.title AS productID,
                phone_order_messages.position AS type, phone_order_messages.entryDate AS entryDate FROM phone_order_messages
                LEFT JOIN products ON phone_order_messages.productID = products.id
                WHERE 1 {$query} ORDER BY phone_order_messages.id DESC";

        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * -------------- Dodavanje SMS templatea HR ------------------       *
 **********************************************************************/
    public function writeMessage($message, $productId, $mType) {
        $writekveri = "INSERT INTO phone_order_messages (`message`, `mLength`, `productID`, `position`) VALUES ('{$message}','Short',{$productId},{$mType})";
        $this->db->query($writekveri,1);
        echo mysql_insert_id();
    }
/**********************************************************************
 * -------------- Dodavanje SMS prevoda -----------------------       *
 **********************************************************************/
    public function writeTranslation($message,$state,$reference) {
        $writekveri = "INSERT INTO phone_order_messages (`messageID`, `translation`, `state`) VALUES ({$reference},'{$message}','{$state}')";
        $this->db->query($writekveri,1);
        echo mysql_insert_id();
    }

    /**********************************************************************
     * -------------- Listanje SMS poruka -------------------------       *
     **********************************************************************/
    public function getTranslationByMessage($query) {
        $sql = "SELECT phone_order_message_translation.ID AS id, phone_order_message_translation.translation AS message, state
                FROM phone_order_message_translation
                WHERE messageID = {$query}";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * -------------- Listanje SMS poruka -------------------------       *
 **********************************************************************/
    public function getMessageTrans($mID,$state) {
        $sql = "SELECT ID, phone_order_message_translation.translation AS message, state FROM phone_order_message_translation
                WHERE messageID = {$mID} AND state = '{$state}' LIMIT 1";
        $results=$this->db->query($sql,3);
        return $results;
    }

/**********************************************************************
 * -------------- Izmjena prevoda poruke ----------------------       *
 **********************************************************************/
    public function updateMessageTrans($mID,$state,$message) {
        $sql = "UPDATE phone_order_message_translation SET translation = '{$message}' WHERE messageID = {$mID} AND state = '{$state}'";

        $results=$this->db->query($sql,1);

        if ($results){
            echo "1";
        } else {
            echo "-1";
        }
    }
/**********************************************************************
 * -------------- Dodavanje novog prevoda poruke iz kampanje---       *
 **********************************************************************/
    public function addMessageByCampaign($state,$productId,$message,$mType) {
        $writekveri = "INSERT INTO phone_order_messages (`message`, `mLength`, `productID`, `position`) VALUES ('{$message}','Short',{$productId},{$mType})";
        $this->db->query($writekveri,1);
        $holderMessage =  mysql_insert_id();

            if($holderMessage > 0) {
                $sql = "INSERT INTO phone_order_message_translation (`messageID`, `state`, `translation`)
                                                VALUES ($holderMessage, '$state','$message')";
                $results=$this->db->query($sql,1);
                echo mysql_insert_id();
            }
    }

/**********************************************************************
 * -------------- Dodavanje novog prevoda poruke --------------       *
 **********************************************************************/
    public function newMessageTrans($mID,$state,$message) {
        $sql = "INSERT INTO phone_order_message_translation (`messageID`, `state`, `translation`)
                                                VALUES ($mID, '$state','$message')";

        $results=$this->db->query($sql,1);

        echo mysql_insert_id();
    }
/**********************************************************************
 * -------------- Dodavanje novog prevoda prve poruke ---------       *
 **********************************************************************/
    public function getMessageTranslationList($state,$product) {
        $sql = "SELECT phone_order_message_translation.translation as message, phone_order_message_translation.ID as idNum,
                       phone_order_messages.message AS initialMessage, phone_order_message_translation.messageID as idOrigin
                FROM phone_order_message_translation
                LEFT JOIN phone_order_messages ON phone_order_message_translation.messageID = phone_order_messages.ID
                WHERE phone_order_message_translation.state = '{$state}'
                AND phone_order_messages.productID = '{$product}' AND phone_order_messages.position = 1 ORDER BY phone_order_messages.ID DESC";
        $results=$this->db->query($sql,2);
        return $results;

    }
/**********************************************************************
 * -------------- Dodavanje novog prevoda poruke --------------       *
 **********************************************************************/
    public function getSecondMessageTranslationList($state,$product) {
        $sql = "SELECT phone_order_message_translation.translation as message, phone_order_message_translation.ID as idNum,
                       phone_order_messages.message AS initialMessage, phone_order_message_translation.messageID as idOrigin
                FROM phone_order_message_translation
                LEFT JOIN phone_order_messages ON phone_order_message_translation.messageID = phone_order_messages.ID
                WHERE phone_order_message_translation.state = '{$state}'
                AND phone_order_messages.productID = '{$product}' AND phone_order_messages.position = 2 ORDER BY phone_order_messages.ID DESC";
        $results=$this->db->query($sql,2);
        return $results;

    }
/**********************************************************************
 * -------------- Promijeni sat slanja poruke -----------------       *
 **********************************************************************/
    public function changeMessageHour($campID,$newTime) {
        $updatekveri = "UPDATE `CampManagement` SET `selectedMessages`='{$newTime}' WHERE id={$campID}";
        $all = $this->db->query($updatekveri,1);
        return $all;
    }
/**********************************************************************
 * -------------- Promijeni limit poruka ----------------------       *
 **********************************************************************/
    public function changeMessageLimit($mID,$limit) {
        $updatekveri = "UPDATE `phone_order_message_translation` SET `messageStop`='{$limit}' WHERE id={$mID}";
        $all = $this->db->query($updatekveri,1);
        return $all;
    }
/**********************************************************************
 * -------------- Promijeni datum slanja poruke -----------------       *
 **********************************************************************/
    public function changeSendDate($campID,$newDate) {
        $updatekveri = "UPDATE `CampManagement` SET `Datesend`='{$newDate}' WHERE id={$campID}";
        $all = $this->db->query($updatekveri,1);
        return $all;
    }

    /**********************************************************************
     * -------------- Message performance tabela-------------------       *
     **********************************************************************/

    public function showMessagePerformance($kveri) {
        $sql = "SELECT phone_order_messages.id AS id, phone_order_messages.message AS message, phone_order_messages.mLength AS mLength, products.title AS productID FROM phone_order_messages
                LEFT JOIN products ON phone_order_messages.productID = products.id
                WHERE 1 {$kveri} AND phone_order_messages.position = 1 ORDER BY phone_order_messages.id DESC";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * -------------- Funkcija za niz prevoda-------------------       *
 **********************************************************************/

    public function getTranslationID() {
        $sql = "SELECT phone_order_message_translation.ID AS id,  phone_order_message_translation.state AS state,  phone_order_message_translation.messageID AS messageID,
                       phone_order_messages.position AS pozicija
                FROM phone_order_message_translation
                LEFT JOIN phone_order_messages ON phone_order_message_translation.messageID = phone_order_messages.ID
                WHERE 1";
        $results=$this->db->query($sql,2);
        return $results;
    }

/**********************************************************************
 * -------------- Funkcija za niz prevoda-------------------       *
 **********************************************************************/

    public function getTranslationIDByPosition($pos) {
        $sql = "SELECT phone_order_message_translation.ID AS id,  phone_order_message_translation.state AS state,  phone_order_message_translation.messageID AS messageID,
                       phone_order_messages.position AS pozicija
                FROM phone_order_message_translation
                LEFT JOIN phone_order_messages ON phone_order_message_translation.messageID = phone_order_messages.ID
                WHERE phone_order_messages.position = {$pos}";
        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
 * -------------- Resetovanje countera poruke -----------------       *
 **********************************************************************/

    public function resetMessageStop($mId) {
        $sql        = "UPDATE `phone_order_message_translation` SET `messageStop`=0, `inProcess`=1 WHERE ID = {$mId} ";
        $results    = $this->db->query($sql,1);
        return $results;
    }

/**********************************************************************
 * -------------- Promijeni limit poruka ----------------------       *
 **********************************************************************/
    public function removeCall($campaign,$filteredPhone) {

        $myFile = $this->_includesFolderPhn.'csv/'.$campaign.'.csv';


        $file_content = file_get_contents($myFile);
        $lines = explode("\n", $file_content);
        $new_file = array();
        foreach($lines as $num => $line){
            $pos = strpos($line, $filteredPhone);
            if($pos !== false){
                $new_file[] = "excluded,0\n";
             } else {
                $new_file[] = $line."\n";
            }
        }
        file_put_contents($myFile, $new_file);


        $oldFile = $this->_includesFolderPhn.'csv/'.$campaign.'.csv';
        file_put_contents($oldFile, $new_file);

    }
/**********************************************************************
 * -------------- Funkcija za direktno slanje SMS-a------------       *
 **********************************************************************/

    public function sendDirectSMS($messageID,$from,$to,$msg){
        $ch = curl_init('http://bulk.mobile-gw.com:9000/?');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "username=hccinter&password=ZrDqBzb6&allow_adaption=1&messageid={$messageID}&status_report=3&origin={$from}&call-number={$to}&text=".urlencode($msg));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $send_response = curl_exec ($ch);
        curl_close ($ch);
        echo "1";
    }
    /**********************************************************************
     * ---------------------------- Precisti broj ---------------       *
     **********************************************************************/
    public function cleanMobile($phoneNo, $state){
        $areaLen = strlen($this->_areaCodes[$state]);

        $exceptions = array(
          'BA' => Array(),
          'RS' => Array(),
          'HR' => Array(),
          'MK' => Array(),
          'BG' => Array(),
          'SI' => Array(),
          'IT' => Array('391','392','393'),
          'SK' => Array(),
          'PL' => Array(),
          'GR' => Array(),
          'LV' => Array(),
          'LT' => Array(),
          'AT' => Array(),
          'HU' => Array(),
          'CZ' => Array(),
          'RO' => Array(),
          'DE' => Array(),
          'EE' => Array()
        );

        $phoneNo = trim($phoneNo);
        $phoneNo = str_replace('o', "0", $phoneNo);
        $phoneNo = str_replace('O', "0", $phoneNo);

        if (strlen($phoneNo) > 15 && substr_count($phoneNo," ") < 4 && substr_count($phoneNo,"/") < 3){
            $phoneNo = $this->pullCellNumber($phoneNo, $state); // Ako broj duzi od 15, pretpostavka je da su dva broja upisana. Upotrijebi funkciju za izvlacenje mobilnog
        }
        $phoneNo = preg_replace('/[^0-9]/', '', $phoneNo);
        // Obrisi nule ako postoje na pocetku broja
        if (substr($phoneNo, 0, 2) == "00"){
            $phoneNo = substr($phoneNo, 2);
        }

        $potvrdae = false;

        if(!empty($exceptions[$state])){
            foreach($exceptions[$state] as $except){
                $duzinae = strlen($except);
                $cutPhonee = substr($phoneNo, 0, $duzinae);

                if ($except == $cutPhonee) {
                    $potvrdae = true;
                    break;
                }
            }
        }

        $areaCheck = substr($phoneNo, 0, $areaLen);
        if ($areaCheck == $this->_areaCodes[$state] && $potvrdae == false){
            $phoneNo = substr($phoneNo, $areaLen);
        }
        // Exception za prefix 06 u grckoj
        if ($state == "HU"){
            $checkGR = substr($phoneNo, 0, 2);
            if ($checkGR == "06") {
                $phoneNo = substr($phoneNo, 2);
            }
        }
        // Exception za prefix 8 u Litvaniji
        if ($state == "LT"){
            $checkLT = substr($phoneNo, 0, 1);
            if ($checkLT == "8") {
                $phoneNo = substr($phoneNo, 1);
            }
        }
        $potvrda = false;
        $duzina = 0;

        foreach($this->_allowedArr[$state] as $area){

            $duzina = strlen($area);
            $cutPhone = substr($phoneNo, 0, $duzina);

            if ($area == $cutPhone) {
                $potvrda = true;
                break;
            }
        }
        if ($duzina == 3 && substr($phoneNo, 0, 1) == 0){
            $phoneNo = substr($phoneNo, 1);
        }
        //Zavrsno sredjivanje, brisanje whitespacea i formiranje pravilnog broja za unos u csv
        $phoneNo = str_replace(' ', "", $phoneNo);

        if ($potvrda){
            return $phoneNo;
        } else {
            return false;
        }
    }
/**********************************************************************
 * ---------------------- Precisti FIXNI broj -----------------       *
 **********************************************************************/
    public function cleanLandline($phoneNo, $state){
        $areaLen = strlen($this->_areaCodes[$state]);

        $phoneNo = preg_replace('/[^0-9]/', '', $phoneNo);

        if (substr($phoneNo, 0, 2) == "00"){
            $phoneNo = substr($phoneNo, 2);
        }

        $areaCheck = substr($phoneNo, 0, $areaLen);
        if ($areaCheck == $this->_areaCodes[$state]){
            $phoneNo = substr($phoneNo, $areaLen);
        }

        // Exception za prefix 06 u Madjarsko
        if ($state == "HU"){
            $checkHU = substr($phoneNo, 0, 2);
            if ($checkHU == "06") {
                $phoneNo = substr($phoneNo, 2);
            }
        }

        // Exception za prefix 8 u Litvaniji
        if ($state == "LT"){
            $checkLT = substr($phoneNo, 0, 1);
            if ($checkLT == "8") {
                $phoneNo = substr($phoneNo, 1);
            }
        }
        if (substr($phoneNo, 0, 1) == "0"){
            $phoneNo = substr($phoneNo, 1);
        }

        // izuzetak za italiju posto je twillio broj italijanski, mora da zove u lokalu
        if ($state == "IT"){
            if (substr($phoneNo,0,1) == 0){
                $finalPhone = "+".$this->_areaCodes[$state]."".$phoneNo;
            } else {
                $finalPhone = "+".$this->_areaCodes[$state]."0".$phoneNo;
            }

        } else {
            $finalPhone = "+".$this->_areaCodes[$state]."".$phoneNo;
        }

        if (strlen($phoneNo) > 7){
            return $finalPhone;
        } else {
            return "wrong landline";
        }


    }
    /**********************************************************************
     * ------------- Preuzmi statistiku za kampanju ---------------       *
     **********************************************************************/
    public function showCampaignStats($cId){
        $sql = "SELECT CampManagement.*, states.title_eng AS stateTitle, products.title AS productTitle,
                inc1.title AS incPro1, inc2.title AS incPro2, inc3.title AS incPro3, exc1.title AS excPro1, exc2.title AS excPro2, exc3.title AS excPro3
                FROM CampManagement
                JOIN states ON CampManagement.Country = states.code2
                JOIN products ON CampManagement.product = products.id
                LEFT JOIN products inc1 ON CampManagement.include1 = inc1.id
                LEFT JOIN products inc2 ON CampManagement.include2 = inc2.id
                LEFT JOIN products inc3 ON CampManagement.include3 = inc3.id
                LEFT JOIN products exc1 ON CampManagement.exclude1 = exc1.id
                LEFT JOIN products exc2 ON CampManagement.exclude2 = exc2.id
                LEFT JOIN products exc3 ON CampManagement.exclude3 = exc3.id
                WHERE CampManagement.id = {$cId}
                LIMIT 1";
        $results=$this->db->query($sql,3);

        $selMessages = $results['selectedMessages'];

        $selektovane = json_decode($selMessages);

        foreach ($selektovane AS $key=>$val) {
            $sql2 = "SELECT phone_order_messages.ID AS messageId, phone_order_messages.position AS pozicija FROM phone_order_message_translation
                JOIN phone_order_messages ON phone_order_message_translation.messageID = phone_order_messages.ID
                WHERE phone_order_message_translation.ID = {$key}";


            $results2=$this->db->query($sql2,3);

            if ($results2['pozicija'] == 1) {
                $results['initial'] = $results2['messageId'];
            } else if($results2['pozicija'] == 2) {
                $results['squeeze'] = $results2['messageId'];
            } else {
                $results['unknownmsg'] = $results2['messageId'];
            }

        }

        return $results;
    }
/**********************************************************************
 * ------------- Preuzmi statistiku za REORDER kampanju -------       *
 **********************************************************************/
    public function showReorderCampaignStats($cId){
        $sql = "SELECT phone_order_reorder.*, states.title_eng AS stateTitle, products.title AS productTitle, phone_order_messages.ID AS messageId, phone_order_messages.position AS pozicija
                FROM phone_order_reorder
                JOIN states ON phone_order_reorder.Country = states.code2
                JOIN products ON phone_order_reorder.product = products.id
                JOIN phone_order_message_translation ON phone_order_reorder.selectedMessage = phone_order_message_translation.ID
                JOIN phone_order_messages ON phone_order_message_translation.messageID = phone_order_messages.ID
                WHERE phone_order_reorder.id = {$cId}
                LIMIT 1";
        $results=$this->db->query($sql,3);

        return $results;
    }
/**********************************************************************
 * --- Prebroj neisporucene spremne za suppression listu -------      *
 **********************************************************************/
    public function countMoreUndelivered(){
        $sql = "SELECT countTbl.Phone AS PhoneNumber, countTbl.broj AS undeliveredCount, smsMessages.from AS sender
                FROM `smsMessages`
                LEFT JOIN (SELECT origin as Phone, count(*) as broj
                            FROM `smsMessages`
                            WHERE status != 2 AND dateSent > '2016-03-09'
                            GROUP BY origin) countTbl ON smsMessages.origin = countTbl.Phone
                WHERE countTbl.broj > 3 AND dateSent > '2016-03-09'
                GROUP BY countTbl.Phone
                ORDER BY `countTbl`.`broj` DESC";

        $results=$this->db->query($sql,2);
        return $results;
    }
/**********************************************************************
* --- Prebroj korisnike koji su se odjavili po kampanji --------      *
**********************************************************************/
    public function countOptedOut($phoneNumber){
        $sql = "SELECT messageId
                FROM `smsMessages`
                WHERE origin = '$phoneNumber'
                ORDER BY id DESC LIMIT 1";

        $results=$this->db->query($sql,3);
        return $results;
    }
/**********************************************************************
 * ------------------- Uhvati informacije Bulk kampanje --------      *
 **********************************************************************/
    public function getBulkCampaignInfoByName($campaignName, $state){
        $sql = "SELECT products.title AS title, products.id AS pId, CampManagement.price AS price, CampManagement.upsellPrice AS upsellPrice,
                       CampManagement.freeShipping AS freeShipping, minForFreeShip AS mfs
                FROM `CampManagement`
                LEFT JOIN products ON CampManagement.product = products.id
                WHERE CampaignName LIKE '$campaignName'
                AND Country LIKE '$state'
                LIMIT 1";

        $results=$this->db->query($sql,3);
        return $results;
    }
/**********************************************************************
 * ---------------- Uhvati informacije Reorder kampanje --------      *
 **********************************************************************/
    public function getReorderCampaignInfoByName($campaignName, $state){
        $sql = "SELECT products.title AS title, products.id AS pId, phone_order_reorder.price AS price, phone_order_reorder.upsellPrice AS upsellPrice,
                       phone_order_reorder.freeShipping AS freeShipping, minForFreeShip AS mfs
                FROM `phone_order_reorder`
                LEFT JOIN products ON phone_order_reorder.product = products.id
                WHERE CampaignName LIKE '$campaignName'
                AND Country LIKE '$state'
                LIMIT 1";

        $results=$this->db->query($sql,3);
        return $results;
    }

    /**********************************************************************
     * ---------------- Uhvati informacije Reorder kampanje --------      *
     **********************************************************************/
    public function getLastCampaignPrice($state,$product)
    {
        $sql = "SELECT price, upsellPrice
                FROM `CampManagement`
                WHERE product = {$product}
                AND Country = '{$state}'
                ORDER BY id DESC
                LIMIT 1";
        $results=$this->db->query($sql,3);
        return $results;
    }
    public function getMessageByState($state, $message_id)
    {
        $q = "SELECT `translation` FROM `phone_order_message_translation` WHERE `state`='$state' AND `messageID`='$message_id'";

        $result = $this->db->query($q, 2);

        return $result[0]['translation'];

    }

    /**********************************************************************
     * -- Get info za kampanju koja se stavlja na listu Split testa --    *
     **********************************************************************/
    public function getCampaignForSplit($id){
        $sql = "SELECT id, CampaignName, Country, selectedMessages, price, upsellPrice
                FROM `CampManagement`
                WHERE id = {$id}
                LIMIT 1";
        $results=$this->db->query($sql,3);
        return $results;
    }
    /**********************************************************************
     * -- Pravljenje nove split kampanje -----------------------------    *
     **********************************************************************/
    public function saveSplitTest($arrKampanje, $splitName, $splitState, $splitProduct, $splitLimit){

        $file = fopen($this->_includesFolderPhn.'split/'.$splitName.'.csv', 'r');

        if ($file == false) {
            return "-5";
        }

        while (($result = fgetcsv($file)) !== false)
        {
            $csv[] = $result;
        }
        fclose($file);

        $campaignNum        = count($arrKampanje);
        $numberCountTotal   = count($csv);
        $numberCount        = $splitLimit;
        $countQueue         = round($numberCount / $campaignNum, 0);

        $brojevi        = "";
        $countPart      = 0;
        $countCampArr   = 0;
        $nizSve         = Array();
        $countAll       = 0;
        $arrCounter     = Array();

        foreach ($csv as $broj) {
            $countAll++;

            $brojevi .= $broj[0].",".$broj[1]."\n";

            $countPart++;

            if ($countPart == $countQueue && $countCampArr+1 < $campaignNum) {
                $nizSve[$arrKampanje[$countCampArr]] =  Array($countPart, $brojevi);
                $countPart  = 0;




                $brojevi = "";
                $countCampArr++;
            } else if ($countCampArr+1 == $campaignNum && $countAll == $numberCount){
                $nizSve[$arrKampanje[$countCampArr]] =  Array($countPart, $brojevi);

                $brojevi = "";
                $countCampArr++;
            }

            if($countAll >= $numberCount){
                break;
            }

        }

        $campField  = implode(",", $arrKampanje);
        foreach($nizSve AS $key=>$val){


            $myFile = fopen($this->_includesFolderPhn.'csv/'.$key.'.csv', 'w');
            chmod($this->_includesFolderPhn.'csv/'.$key.'.csv', 0777);
            file_put_contents($this->_includesFolderPhn.'csv/'.$key.'.csv', $val[1]);
            fclose($myFile);

            $myFile2 = fopen($this->_includesFolderInst.'csv/'.$key.'.csv', 'w');
            chmod($this->_includesFolderInst.'csv/'.$key.'.csv', 0777);
            file_put_contents($this->_includesFolderInst.'csv/'.$key.'.csv', $val[1]);
            fclose($myFile2);
        }

        $sql = "INSERT INTO phone_order_splittest (`campName`, `product`, `state`, `campaigns`, `recipients`, `totalRecipients`)
                                           VALUES ('$splitName', '$splitProduct', '$splitState', '$campField', {$numberCount}, {$numberCountTotal})";
        $this->db->query($sql,1);
        $upis = mysql_insert_id();

        foreach ($arrKampanje AS $kamp) {
            $updatekveri = "UPDATE `CampManagement` SET `RecipientNo`='{$nizSve[$kamp][0]}', `splitCampaign`={$upis}, `active`= 1, `splitType`=1 WHERE CampaignName='{$kamp}'";
            $this->db->query($updatekveri,1);
        }


        return $nizSve;



    }
    /**********************************************************************
     * -- Uzimanje liste split kampanja za split report page ---------    *
     **********************************************************************/
    public function getSplitCampaignList($query) {

        $sql = "SELECT phone_order_splittest.*, products.title as title FROM phone_order_splittest
                LEFT JOIN products ON phone_order_splittest.product = products.id
                WHERE 1 {$query}
                ORDER BY phone_order_splittest.id DESC";

        $results=$this->db->query($sql,2);
        return $results;
    }
    /**********************************************************************
     * -- Setovanje najbolje kampanje da se ostatak salje na nju -----    *
     **********************************************************************/
    public function setWinner($campaign, $recipients, $totalRecipients){
        /*
         * ostatak na koji treba poslati
         */
        $recipientsLeft = $totalRecipients - $recipients;

        $toSend  = "";

        /*
         * Ispitivanje danasnjeg dana u sedmici, ako je dan kojim se ne salje da ide na ponedeljak, za sve ostale vrijedi slanje za sljedeci dan
         */
        $dan    = Date('w');

        if ($dan == 4){
            $toSend = Date('Y-m-d', strtotime('+4 days'));
        } else if ($dan == 5){
            $toSend = Date('Y-m-d', strtotime('+3 days'));
        } else if ($dan == 6){
            $toSend = Date('Y-m-d', strtotime('+2 days'));
        } else {
            $toSend = Date('Y-m-d', strtotime('+1 days'));
        }

        /*
         * Selekcija poruka za izabranu kampanju
         */
        $sqlCampaign = "SELECT CampManagement.selectedMessages AS selectedMessages, phone_order_splittest.campName AS campName, CampManagement.splitCampaign AS splitCampaign
                        FROM CampManagement
                        LEFT JOIN phone_order_splittest ON CampManagement.splitCampaign = phone_order_splittest.id
                        WHERE CampaignName='{$campaign}'";
        $singleCamp =$this->db->query($sqlCampaign,3);

        $splitName = $singleCamp["campName"];
        $splitId = $singleCamp["splitCampaign"];
        $messageObj = json_decode($singleCamp["selectedMessages"]);
        $messAr     = get_object_vars($messageObj);

        foreach($messAr AS $k=>$v){
            $messAr[$k] = sprintf("%02d", "9");;
        }

        $messEncoded = json_encode($messAr);

        if ($campaign != "" && !empty($messAr)){
        /*
         * Update Kampanje, broj primaoca, dan slanja, vrijeme slanja
         */

            $updatekveri = "UPDATE `CampManagement` SET `RecipientNo`='{$recipientsLeft}', Datesend='{$toSend}', status='Prepared', selectedMessages='{$messEncoded}' WHERE CampaignName='{$campaign}'";
            $this->db->query($updatekveri,1);

            foreach($messAr AS $k=>$v){
                /*
                 * Update prevoda poruke, pocetak od "0", setovanje da je u procesu slanja
                 */
                $updatekveri2 = "UPDATE `phone_order_message_translation` SET `messageStop`='0', inProcess='1' WHERE ID='{$k}'";
                $this->db->query($updatekveri2,1);
            }

            /*
             * Update splittest kampanje - izjednacavanje broja na koji se salje na ostatak brojeva, tako da TOTAL LEFT mora osatti "0"
             */
            $updatekveri3 = "UPDATE `phone_order_splittest` SET `recipients`=`totalRecipients` WHERE id='{$splitId}'";
            $this->db->query($updatekveri3,1);

            /*
             * Otvaranje liste brojeva za kompletnu split test kampanju
             */
            $file = fopen($this->_includesFolderPhn.'split/'.$splitName.'.csv', 'r');

            if ($file == false) {
                return "-5";
            }

            /*
             * Ubacivanje ostatka brojeva koji nisu dobili poruku
             */
            $countRow = 0;
            while (($result = fgetcsv($file)) !== false)
            {
                $countRow++;
                if($countRow > $recipients ){
                    $csv[] = $result;
                }

            }
            fclose($file);

            $numberCountTotal   = count($csv);

            $brojevi = "";
            foreach ($csv as $broj) {

                $brojevi .= $broj[0] . "," . $broj[1] . "\n";
            }

            /*
             * Upis brojeva u fajl za slanje
             */
            $myFile = fopen($this->_includesFolderPhn.'csv/'.$campaign.'.csv', 'w');
            chmod($this->_includesFolderPhn.'csv/'.$campaign.'.csv', 0777);
            file_put_contents($this->_includesFolderPhn.'csv/'.$campaign.'.csv', $brojevi);
            fclose($myFile);

            $myFile2 = fopen($this->_includesFolderInst.'csv/'.$campaign.'.csv', 'w');
            chmod($this->_includesFolderInst.'csv/'.$campaign.'.csv', 0777);
            file_put_contents($this->_includesFolderInst.'csv/'.$campaign.'.csv', $brojevi);
            fclose($myFile2);

            return Array($recipientsLeft,$toSend);
        }



    }
    /**********************************************************************
     * -------------------- Getting numbers for REORDER CALL ---------    *
     **********************************************************************/
    public function getReorderNumbers($state, $dateQuery  = ""){
        $sql = "SELECT customers.namesurname AS custName, customers.phone AS custPhone, customers.address AS custAddress, products.title, products.id AS prId,
                       documents.orderdate AS orderdate, campTbl.price AS smsPrice, documents.state AS state, SUM(orderitems.decrease_quantity) AS quantity
                    FROM `documents`
                    LEFT JOIN customers ON documents.customer = customers.id
                    LEFT JOIN orders ON documents.id = orders.referenceinvoice
                    LEFT JOIN orderitems ON orders.order_id = orderitems.order
                    LEFT JOIN products ON orderitems.product = products.id
                    LEFT JOIN (
                              SELECT Country, product, price
                              FROM CampManagement WHERE price != '0.00'
                              GROUP BY CampManagement.product, CampManagement.Country
                              ORDER BY CampManagement.id DESC
                              ) AS campTbl ON products.id = campTbl.product AND documents.state = campTbl.Country
                    WHERE documents.doc_type = 1
                    AND documents.state LIKE '$state'
                    AND documents.orderstatus = 'C'
                    {$dateQuery}
                    AND products.isPostage != 'Yes'
		            AND products.isService != 'Yes'
                    GROUP BY orders.order_id";

        //AND ( products.id = 200 OR products.id = 248 OR products.id = 55 OR products.id = 3 OR products.id = 169 OR products.id = 241 )
        $all = $this->db->query($sql,2);

        return $all;

    }

    /**********************************************************************
     * -- GET LIST of orders to compare with pulled reorder numbers --    *
     **********************************************************************/
    public function getOrdersForOutboundCall($state) {
        $dayBefore = Date("Y-m-d", strtotime('-1 days'));

        $today = Date("Y-m-d");
        $datum = new DateTime($today);
        $dayToday = $datum->format("w");
        if ($dayToday == "1") {
            $dayBefore = Date("Y-m-d", strtotime('-3 days'));
        }


        $sql    = " SELECT name, surname, telephone, orderdate
                    FROM orders
                    WHERE 1
                    AND state = '{$state}'
                    AND orderdate > '{$dayBefore}'";

        $all = $this->db->query($sql,2);

        return $all;

    }

    /****************************************************************************
     * -- GET LIST of Outbound requests to compare with bulk call numbers --    *
     ****************************************************************************/
    public function getOutboundsForOutboundCall($days,$state) {
        $daysBefore = Date("Y-m-d", strtotime('-'.$days.' days'));

        $today = Date("Y-m-d");
        $datum = new DateTime($today);
        $dayToday = $datum->format("w");
        if ($dayToday == "1") {
            $moreDays = $days + 2;
            $daysBefore = Date("Y-m-d", strtotime('-'.$moreDays.' days'));
        }


        $sql    = " SELECT name, phone, submitDate
                    FROM phone_order_outbound
                    WHERE 1
                    AND state = '{$state}'
                    AND Date(submitDate) > '{$daysBefore}'";

        $all = $this->db->query($sql,2);

        return $all;
    }

/**********************************************************************
 * ---------------- Uhvati informacije Reorder kampanje --------      *
 **********************************************************************/
    public function getSmsDifferenceList($query)
    {
        $sql = "SELECT sms_sentByOrder.smsMessages_Id AS osmsId, phone_order_calls.orderSubmitId AS submitId, phone_order_calls.state AS state, phone_order_calls.cName AS cName, phone_order_calls.cSurname AS cSurname,
                      products.title AS prName, smsMessages.dateSent AS mSent, sms_status.statusDate AS mDelivered, phone_order_calls.date AS mOrdered, Date(smsMessages.dateSent) AS mSentDate
                FROM `sms_sentByOrder`
                LEFT JOIN phone_order_calls ON sms_sentByOrder.order_submitId = phone_order_calls.orderSubmitId
                LEFT JOIN smsMessages ON sms_sentByOrder.smsMessages_Id = smsMessages.id
                LEFT JOIN products ON phone_order_calls.product = products.id
                LEFT JOIN sms_status ON sms_sentByOrder.smsMessages_Id = sms_status.smsId AND (sms_status.status = 2 OR sms_status.status = 5)
                WHERE 1  {$query}
                ORDER BY sms_sentByOrder.id DESC";

        $results=$this->db->query($sql,2);
        return $results;
    }


/**********************************************************************
 * ----------Funkcija za URL shortening ------------------------      *
 **********************************************************************/
    public function getShortURLOLD($longURL)
    {

        $randomStr = substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(5/strlen($x)) )),1,5);


        $sql = "SELECT * FROM phone_order_shorturl
				WHERE shortCode = '{$randomStr}'";

        $checkresults=$this->db->query($sql,2);


        if ($checkresults == false){

            $writekveri = "INSERT INTO phone_order_shorturl (`longURL`, `shortCode`, `visits`)
                                                VALUES ('$longURL', '$randomStr', 0)";
            $this->db->query($writekveri,1);

            $returnURL = "devinfopoint.com/boris/shorty/".$randomStr;
        } else {
            $returnURL = "error";
        }

        return $returnURL;
    }
    /**********************************************************************
     * ----------- Slanje requesta na outbound u listu za pozivanje ----  *
     **********************************************************************/
    public function getShortURL($longURL){
        $encodedUrl = urlencode($longURL);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://www.givv.me/api/shortme.php');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "app=PHN&long={$encodedUrl}&APIKey=38H4W1D93J7HFK2K8DMJ2JD8SK7AD8SK2J38D8PK9");
        $response = curl_exec ($ch);
        return $response;
        curl_close($ch);
    }
    /**********************************************************************
     * -- Uzimanje liste za statistiku skracenih ilnkova -------------    *
     **********************************************************************/
    public function getShortLinkList($query = "") {

        $sql = "SELECT * FROM phone_order_shorturl
                WHERE 1 {$query}";

        $results=$this->db->query($sql,2);
        return $results;
    }
}
?>