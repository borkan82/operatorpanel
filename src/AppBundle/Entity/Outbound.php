<?php

namespace AppBundle\Entity;

class Outbound
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    /**********************************************************************
     * --- Provjera da li postoji entry ---       *
     **********************************************************************/

    public function checkIfExist($table,$kveri)
    {
        $sql = "SELECT * FROM {$table}
				WHERE 1 $kveri LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /*
     * Lista submit tabele
     */
    public function getOrderSubmits($query = "", $defaultId = 350000)
    {
        $fiveMins = Date('Y-m-d H:i:s', strtotime('-5 minutes')); // ostavljamo 5min u slucaju upsella

        $sql = "SELECT * FROM order_submits
                WHERE 1 {$query} AND datetime < '$fiveMins' AND id > $defaultId and domain != 'domain.com'";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * ------------------- Napravi submit upsell ------------------       *
     **********************************************************************/
    public function makeSubmitUpsell($sId,$new_pack)
    {
        exit;
        if($sId > 0)
        {
            $q = "SELECT post_data from order_submits where id='{$sId}' AND `upsellMade` != '1' limit 1";
            //exit;
            $row=$this->conn->fetchAssoc($q);

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
    /**********************************************************************
     * ------ Promjena statusa poziva i vremena za ponovno zvanje ---     *
     **********************************************************************/
    public function changeOrder($today,$new_time,$akcija,$redBr)
    {
        $sql = "UPDATE phone_order_outbound SET status='$akcija', called_time='$today', tocall_time='$new_time' WHERE id='$redBr'";
        $all = $this->conn->executeQuery($sql);

        return $all;
    }

    /**********************************************************************
     * ------------------- Dodavanje komentara --------------------       *
     **********************************************************************/
    public function updateComment($komentar,$redBr)
    {
        $sql = "UPDATE phone_order_outbound SET comment='$komentar' WHERE id='$redBr'";
        $all = $this->conn->executeQuery($sql);

        return $all;
    }

    /*
     *  Skupljanje podataka iz outbound tabele
     */
    public function getOutboundQuery($query)
    {
        $sql     = "SELECT phone_order_outbound.*, products.title AS title, oitem.newQuant AS newQuantity, phone_order_users.name AS opName, phone_order_smsprices.exchange AS exchange, 
                    documents.orderstatus AS docStatus, phone_order_split_types.title AS splitType
                    FROM phone_order_outbound
                    JOIN products ON products.id = phone_order_outbound.productID
                    LEFT JOIN orders ON (phone_order_outbound.submitID = orders.submitId AND phone_order_outbound.submitID != 0)
                    LEFT JOIN (SELECT SUM(orderitems.decrease_quantity) as newQuant, orderitems.order FROM orderitems WHERE product != 38 AND product != 283 ) as oitem ON orders.order_id = oitem.order
                    LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
                    LEFT JOIN phone_order_smsprices ON phone_order_outbound.state = phone_order_smsprices.state
                    LEFT JOIN documents ON orders.order_no = documents.doc_reference
                    LEFT JOIN phone_order_split_types ON phone_order_outbound.splitType = phone_order_split_types.id
                    WHERE 1 {$query}";
        //var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }


    /*
   *  Skupljanje podataka za tabelu u zaglavlju za outbound
   */
    public function getOutboundHeaderData($query,$period='')
    {
        $sql     = "SELECT phone_order_outbound.id as id,phone_order_outbound.state, phone_order_outbound.productID,
                    COUNT(*) as callCount,
                    SUM(IF( phone_order_outbound.called_time != '' && phone_order_outbound.callEnd != '' && UNIX_TIMESTAMP(phone_order_outbound.callEnd) > UNIX_TIMESTAMP(phone_order_outbound.called_time), UNIX_TIMESTAMP(phone_order_outbound.callEnd)-UNIX_TIMESTAMP(phone_order_outbound.called_time), 0)) as allDuration,
                    SUM(IF( phone_order_outbound.called_time != '' && phone_order_outbound.callEnd != '' && UNIX_TIMESTAMP(phone_order_outbound.callEnd) > UNIX_TIMESTAMP(phone_order_outbound.called_time), 1, 0)) as countCalled,
                    SUM(IF( 
                          (phone_order_outbound.called_time != '' && phone_order_outbound.callEnd != '' && UNIX_TIMESTAMP(phone_order_outbound.callEnd) > UNIX_TIMESTAMP(phone_order_outbound.called_time) && phone_order_outbound.status = 7)
                       || (phone_order_outbound.called_time != '' && phone_order_outbound.callEnd != '' && UNIX_TIMESTAMP(phone_order_outbound.callEnd) > UNIX_TIMESTAMP(phone_order_outbound.called_time) && phone_order_outbound.status = 12), UNIX_TIMESTAMP(phone_order_outbound.callEnd)-UNIX_TIMESTAMP(phone_order_outbound.called_time), 0)) as allOrderDuration,
                    SUM(IF( 
                          (phone_order_outbound.called_time != '' && phone_order_outbound.callEnd != '' && UNIX_TIMESTAMP(phone_order_outbound.callEnd) > UNIX_TIMESTAMP(phone_order_outbound.called_time) && phone_order_outbound.status = 7)
                       || (phone_order_outbound.called_time != '' && phone_order_outbound.callEnd != '' && UNIX_TIMESTAMP(phone_order_outbound.callEnd) > UNIX_TIMESTAMP(phone_order_outbound.called_time) && phone_order_outbound.status = 12), 1, 0)) as countOrderCalled,
                    SUM(IF( phone_order_outbound.newPrice > phone_order_outbound.price, 1, 0 )) AS upsellsCount,
                    SUM(IF( phone_order_outbound.newPrice > phone_order_outbound.price, (phone_order_outbound.newPrice - phone_order_outbound.price)/phone_order_smsprices.exchange, 0 )) AS upsellPriceDiff,
                    SUM(IF( phone_order_outbound.status = 7 || phone_order_outbound.status = 12, phone_order_outbound.newPrice/phone_order_smsprices.exchange, 0)) as orderSum,
                    SUM(IF( phone_order_outbound.status = 7, 1, 0)) as status7,
                    SUM(IF( phone_order_outbound.status = 6, 1, 0)) as status6, 
                    SUM(IF( phone_order_outbound.status = 12, 1, 0)) as status12, 
                    SUM(IF( phone_order_outbound.status = 9, 1, 0)) as status9,
                    SUM(IF( phone_order_outbound.status = 0, 1, 0)) as status0, 
                    SUM(IF( phone_order_outbound.status = 13, 1, 0)) as status13, 
                    SUM(IF( phone_order_outbound.status != 6 && phone_order_outbound.status != 7 && phone_order_outbound.status != 9 && phone_order_outbound.status != 12 && phone_order_outbound.status != 0 && phone_order_outbound.status != 13, 1, 0)) as countOther,
                     SUM(IF( phone_order_outbound.status = 6 OR phone_order_outbound.status = 7 OR phone_order_outbound.status = 9 OR phone_order_outbound.status = 12, costs.OUTperCall, 0)) as callCosts
                    FROM phone_order_outbound
                    LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
                    LEFT JOIN phone_order_smsprices ON phone_order_outbound.state = phone_order_smsprices.state 
                    LEFT JOIN phone_order_callCenterPrice AS costs ON (phone_order_users.operatorGroup = costs.callCenterId AND costs.period = '{$period}')
				    WHERE 1 {$query} ";

//        LEFT JOIN orders ON (phone_order_outbound.submitID = orders.submitId AND phone_order_outbound.submitID != 0)
//                    LEFT JOIN documents ON orders.referenceinvoice = documents.id
       // var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        return $results;
    }


    /*
     * Skupljanje podataka za chart Orders Products- po call type-
     */
    public function getProductsOrderData($query)
    {
        $sql     = "SELECT phone_order_outbound.id, phone_order_outbound.state, phone_order_outbound.type, phone_order_outbound.status, phone_order_outbound.submitdate,phone_order_outbound.productID,
                    products.title AS title,
                    SUM(IF( phone_order_outbound.status = 6  || phone_order_outbound.status = 7 || phone_order_outbound.status = 9 ||  phone_order_outbound.status = 12 , 1, 0)) as countAnswered,
                    SUM(IF( phone_order_outbound.status = 7 ||  phone_order_outbound.status = 12 , 1, 0)) as outOrderSuccessfull
                    FROM phone_order_outbound
                    JOIN products ON products.id = phone_order_outbound.productID
                    WHERE 1 {$query} group by phone_order_outbound.state,phone_order_outbound.productID";

        $results = $this->conn->fetchAll($sql);
        return $results;

    }

    /*
     * States iz phone order outbound
     */
    public function getOutboundStateList()
    {
        $sql = "SELECT DISTINCT phone_order_outbound.state
                FROM phone_order_outbound
                ORDER BY phone_order_outbound.state ASC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Lista poziva za panel iz outbound tabele
     */
    public function getOutboundList($state)
    {
        $sql = "SELECT phone_order_outbound.*, products.title AS title, order_submits.upsellMade AS upsellMade, phone_order_users.name AS imeOperatora FROM phone_order_outbound
                LEFT JOIN order_submits ON phone_order_outbound.submitID = order_submits.id
                JOIN products ON products.id = phone_order_outbound.productID
                LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
                WHERE 1 AND phone_order_outbound.state = '$state' AND phone_order_outbound.name NOT LIKE '%test%' AND (upsellMade = 0 OR upsellMade IS NULL) AND phone_order_outbound.productID != 314 AND submitDate > NOW() - INTERVAL 5 DAY  ORDER BY tocall_time DESC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Lista poziva - export na svaka 24 h iz outbound tabele
     */
    public function getExportList($state,$start, $end){
        $sql = "SELECT phone_order_outbound.state AS state, phone_order_outbound.name AS firstLastName, phone_order_outbound.phone AS phone, phone_order_outbound.submitDate AS submitDate
                FROM phone_order_outbound
                WHERE phone_order_outbound.state = '$state' AND phone_order_outbound.submitDate BETWEEN '$start' AND '$end'
                ORDER BY phone_order_outbound.submitDate DESC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /**********************************************************************
     * ------------ Ubacivanje OUTLP tipa u zahtjev za zvanje -----       *
     **********************************************************************/
    public function insertOUTLP($state, $randomId, $typeID, $ip, $phone, $name, $product, $price, $status, $url, $postData, $timeToCall = "", $ptc = 0)
    {
        $todayTime = Date("Y-m-d H:i:s");
        $nowHour = Date("H");

        if ($timeToCall != "" && $timeToCall > 0){
            $todayTime = Date("Y-m-d {$timeToCall}:i:s");

            if ($nowHour >= $timeToCall){
                $todayTime = Date("Y-m-d {$timeToCall}:i:s", strtotime('+1 days'));
            }
        }
        $intPhone = (int)$phone;

        $checkValidation  = $this->checkIfExist("phone_order_validation", " AND state = '{$state}' AND phone = '{$phone}' ");

        $checkSuppression = $this->checkIfExist("suppressionList", " AND state = '{$state}' AND number LIKE '%{$intPhone}' ");


        if($checkValidation == false || ($typeID != 7 && $typeID != 8 && $typeID != 9 && $typeID != 10 && $typeID != 11) && $checkSuppression == false) {
            $sql = "INSERT INTO phone_order_outbound (`state`, `randomID`, `type`, `ip`, `phone`, `name`, `productID`, `price`, `status`, `referer`, `postdata`, `tocall_time`, `questionType`)
                                          VALUES ('$state', '$randomId', '$typeID', '$ip', '$phone', '$name', '$product', '$price', '$status', '$url', '$postData', '$todayTime', '$ptc')";
            
            $this->conn->executeQuery($sql);
            return $this->conn->lastInsertId();
        } else {
            return false;
        }

    }
    /**********************************************************************
     * ------------ Ubacivanje OUTUP tipa u zahtjev za zvanje -----       *
     **********************************************************************/
    public function insertOUTUP($state, $submitId, $typeID, $ip, $phone, $name, $product, $price, $upsellPrice, $quantity, $status, $url, $postData)
    {
        $todayTime = Date("Y-m-d H:i:s");
        $sql = "INSERT INTO phone_order_outbound (`state`, `submitID`, `type`, `ip`, `phone`, `name`, `productID`, `price`, `upsellPr`, `quantity`, `status`, `referer`, `postdata`, `tocall_time`)
                                          VALUES ('$state', '$submitId', '$typeID', '$ip', '$phone', '$name', '$product', '$price', '$upsellPrice', '$quantity', '$status', '$url', '$postData', '$todayTime')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }
    /**********************************************************************
     * ------------ Ubacivanje OUTCA tipa u zahtjev za zvanje -----       *
     **********************************************************************/
    public function insertOUTCA($submitId, $state, $name, $phone, $product, $ip, $url, $typeID, $status, $caPrice)
    {
        $todayTime = Date("Y-m-d H:i:s");
        $sql = "INSERT INTO phone_order_outbound (`state`, `submitID`, `type`, `ip`, `phone`, `name`, `productID`, `status`, `referer`, `tocall_time`, `price`)
                                          VALUES ('$state', '$submitId', '$typeID', '$ip', '$phone', '$name', '$product', '$status', '$url', '$todayTime', '$caPrice')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /**********************************************************************
     * ---- Izmjena OUTLP upisanog zahtjeva pri promjeni polja ----       *
     **********************************************************************/
    public function updateOUTLP($typeID, $phone, $name, $randomId, $state, $postData)
    {
        $sql = "UPDATE phone_order_outbound SET type='$typeID', phone='$phone', name='$name', postdata='$postData' WHERE randomId='$randomId' AND state='$state' ORDER BY id DESC";
        $all = $this->conn->executeQuery($sql);

        return $all;
    }

    /**********************************************************************
     * ------------ Promjena flagova-statusa poziva ---------------       *
     **********************************************************************/
    public function changeOutboundFlag($called,$tocall,$redBr,$table,$value,$submit,$ouid,$newPrice = "")
    {
        $callEnd = "";
        if ($value == 2 || $value == 6 || $value == 7 || $value == 9 || $value == 12){
            $callEnd = Date('Y-m-d H:i:s');
        }

        if ($value == 2 || $value == 3){
            $sql = "UPDATE $table SET status='$value', called_time='$called', callEnd='$callEnd', tocall_time='$tocall', callCount = callCount + 1, operator='$ouid', comment = '' WHERE id='$redBr' LIMIT 1";
        } else if ($value == 1 || $value == 4 || $value == 5){
            $sql = "UPDATE $table SET status='$value', called_time='$called', callEnd='$callEnd', callCount = callCount + 1, operator='$ouid' WHERE id='$redBr' LIMIT 1";
        } else if ($value == 7){
            $sql = "UPDATE $table SET status='$value', submitID='$submit', callEnd='$callEnd', operator='$ouid' {$newPrice} WHERE id='$redBr' LIMIT 1";
        } else if ($value == 11)  {
            $sql = "UPDATE $table SET status='$value', called_time='$called', callEnd='$callEnd', operator='$ouid' WHERE id='$redBr' LIMIT 1";
        } else  {
            $sql = "UPDATE $table SET status='$value', callEnd='$callEnd', operator='$ouid' WHERE id='$redBr' LIMIT 1";
        }

        $all = $this->conn->executeQuery($sql);
        /*
         * STATUS LOG FOR OUTBOUND CALLS
         */
        $sql2 = "INSERT INTO phone_order_outbound_status_log (`phone_order_outbound_id`, `status`)
                                              VALUES ('$redBr', '$value')";
        $this->conn->executeQuery($sql2);

        return $all;
    }

    public function changeCallStatus($rId,$status)
    {
        $sql = "UPDATE phone_order_outbound SET status='$status', operator='0' WHERE id='$rId' LIMIT 1";
        $all = $this->conn->executeQuery($sql);

        return $all;
    }

    /**********************************************************************
     * ------ Skupljanje podataka pojedinacnog zahtjeva pri pozivu ---    *
     **********************************************************************/
    public function getOutboundRow($rowId)
    {
        $sql = "SELECT phone_order_outbound.*, phone_order_outbound.price AS formPrice, products.title AS prTitle, phone_order_prices.price AS price, phone_order_prices.upsellPrice AS upsellPrice,
                outQ, outQ1, outQ2, outQ3, outA1, outA2, outA3, outDQ1, outDQ2, outDQ3, outDQ4, outDQ5, outAQ1, outAQ2, outAQ3, outAQ4, outAQ5, prGroup, products.id AS proID, products.sku AS prSKU,
                products.productType AS prType,phone_order_outbound.type AS outType, phone_order_outbound.caller_id AS callerId
                FROM phone_order_outbound
                LEFT JOIN products ON phone_order_outbound.productID = products.id
                LEFT JOIN phone_order_prices ON phone_order_outbound.productID = phone_order_prices.productId AND phone_order_outbound.state = phone_order_prices.state
                LEFT JOIN productDescription ON phone_order_outbound.productID = productDescription.productId AND phone_order_outbound.state = productDescription.state
				WHERE 1 AND phone_order_outbound.id = '$rowId' LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /**********************************************************************
     * ------------ Promjena vremena za ponovno zvanje ------------       *
     **********************************************************************/
    public function changeTimeToCall($redBr,$timeToCall,$comment)
    {
        $callEnd = Date('Y-m-d H:i:s');
        $sql = "UPDATE phone_order_outbound SET status='9', tocall_time='$timeToCall', callEnd='{$callEnd}', comment='$comment' WHERE id='$redBr' LIMIT 1";

        $all = $this->conn->executeQuery($sql);

        $sql2 = "INSERT INTO phone_order_outbound_status_log (`phone_order_outbound_id`, `status`)
                                                      VALUES ('$redBr', '9')";
        $this->conn->executeQuery($sql2);

        return $all;
    }

    /**********************************************************************
     * -------Skupljanje podataka sa ordera iz OMGA za upsell  ----       *
     **********************************************************************/
    public function selectOMGOrder($orderId)
    {
        $sql = "SELECT country, post_data, ip, rf, product FROM order_submits
				WHERE (country = 'BA' || country = 'RS' || country = 'HR' || country = 'SI' || country = 'MK') AND id = '$orderId' and `upsellMade` != '1' LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }
    /*************************************************************************************
     * -------Skupljanje podataka sa ordera iz OMGA Orders tabele za cancel   ----       *
     *************************************************************************************/
    public function selectOMGOrderFromOrders($orderId)
    {
        $sql = "SELECT orders.*, orderitems.product AS prItem,
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
                   ) AS documentAmount
                FROM orders
                LEFT JOIN orderitems ON orders.order_id = orderitems.order
				WHERE 1 AND (state = 'BA' OR state = 'SI')
				AND submitId = '$orderId'
				GROUP BY orders.order_id
				LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /**********************************************************************
     * ------ uzimanje informacija sa omg order za update ---------       *
     **********************************************************************/
    public function selectOMGOrderForUpdate($orderId)
    {
        $sql = "SELECT post_data FROM order_submits
				WHERE id = '$orderId' LIMIT 1";

        $results=$this->conn->fetchAssoc($sql);
        return $results;
    }

    /**********************************************************************
     * -------- VAZNO ! VAZNO !VAZNO !VAZNO !VAZNO !VAZNO ! -------       *
     * -- Mijenjanje vec upisanog OMG ordera SUBMIT tabela --------       *
     **********************************************************************/
    public function changeOMGorder($orderId, $recordId, $postData, $status, $upsellQuery="", $newPrice="")
    {
        $sql = "UPDATE order_submits
                SET post_data='$postData' {$upsellQuery}
                WHERE id='$orderId'
                LIMIT 1";


        $all = $this->conn->executeQuery($sql);

        $callEnd = Date('Y-m-d H:i:s');

        $sql2 = "UPDATE phone_order_outbound
                SET status = '{$status}', callEnd='{$callEnd}' {$newPrice}
                WHERE id='$recordId'
                LIMIT 1";
        $all2 = $this->conn->executeQuery($sql2);

        return $all;
    }

    /**********************************************************************
     * -------- VAZNO ! VAZNO !VAZNO !VAZNO !VAZNO !VAZNO ! -------       *
     * -- Mijenjanje vec upisanog OMG ordera ORDERS tabela --------       *
     **********************************************************************/
    public function changeOrderStatus($submitId, $recordId, $OUTstatus, $OMGstatus, $OMGcomment, $formPrice)
    {
        $sql = "UPDATE orders
                SET orderstatus='$OMGstatus', comment = CONCAT(comment, '$OMGcomment')
                WHERE submitId='$submitId'
                LIMIT 1";

        $all =$this->conn->executeQuery($sql);

        $callEnd = Date('Y-m-d H:i:s');

        $sql2 = "UPDATE phone_order_outbound
                SET status = '{$OUTstatus}', callEnd='{$callEnd}', newPrice='{$formPrice}'
                WHERE id='$recordId'
                LIMIT 1";
        $all2 = $this->conn->executeQuery($sql2);

        return $all;
    }

    /**********************************************************************
     * -------- B/W lista --------       *
     **********************************************************************/
    public function insertValidate($callerPhone,$validate,$state, $source)
    {
        $sql = "INSERT INTO phone_order_validation (`state`, `phone`, `vType`, `source`)
                                          VALUES ('$state', '$callerPhone', '$validate', '$source')";
        $this->conn->executeQuery($sql);
        return $this->conn->lastInsertId();
    }

    /**********************************************************************
     * -------------- Brisanje outbounda iz baze ------------------       *
     **********************************************************************/
    function removeOutbound($rId, $state)
    {
        //pokupi informacije requesta da se mogu pobrisati dupli

        $sql="SELECT * FROM phone_order_outbound WHERE randomID='$rId' ORDER BY id DESC LIMIT 1";
        $var=$this->conn->fetchAssoc($sql);

        $ime    = $var['name'];
        $phone  = $var['phone'];

        //BRISANJE REQUESTA PO ID-u
        $sql="DELETE FROM phone_order_outbound WHERE randomID='$rId' AND state = '$state' AND (phone_order_outbound.type = 6 OR phone_order_outbound.type = 9  OR phone_order_outbound.type = 7 OR phone_order_outbound.type = 8 OR phone_order_outbound.type = 10) LIMIT 1";
        $this->conn->executeQuery($sql);

        // BRISANJE REQUESTA PO IMENU I BROJU
        $sql="DELETE FROM phone_order_outbound WHERE state = '$state' AND (`name`= '{$ime}' OR `phone` = '{$phone}') AND (phone_order_outbound.type = 6  OR phone_order_outbound.type = 7  OR phone_order_outbound.type = 8 OR phone_order_outbound.type = 9 OR phone_order_outbound.type = 10 OR phone_order_outbound.type = 11) AND `submitDate` > NOW() - INTERVAL 2 HOUR";
        $this->conn->executeQuery($sql);



        $sql="SELECT * FROM phone_order_outbound WHERE randomID='$rId' ORDER BY id DESC LIMIT 1";
        $var=$this->conn->fetchAssoc($sql);

        if(empty($var['id']))
        {
            echo 1;
        } else {
            echo -1;
        }
    }

    /**********************************************************************
     * ----------- Preuzmi radne sate callcentra  -------       *
     **********************************************************************/
    public function getWorkingHours($state)
    {
        $sql = "SELECT * FROM phone_order_callcenter
                WHERE 1 AND state = '$state' AND wtFrom > 0 AND wtTo > 0 LIMIT 1";

        $all = $this->conn->fetchAssoc($sql);
        return $all;
    }

    /*
     * Preuzmi count poziva po type  Order Fill Break, Canceled User i Reorder Call, i statistiku za average call value i order percent
     */
    public function getCounCallTypes($state, $from, $to, $product)
    {
        $orderArr = Array();
        $extendedTypes = array(
            1 => array(
                'name' => 'Adcombo Call',
                'shortName' => 'adambo-cal'
            ),
            2 => array(
                'name' => 'Canceled User',
                'shortName' => 'canceled-user'
            ),
            3 => array(
                'name' => 'Upsell Call',
                'shortName' => 'upsell-call'
            ),
            5 => array(
                'name' => 'Form fill brake',
                'shortName' => 'form-fill-break'
            ),
            6 => array(
                'name' => 'Order Fill Brake',
                'shortName' => 'order-fill-break'
            ),
            7 => array(
                'name' => 'Reorder call',
                'shortName' => 'reorder-call'
            ),
            8 => array(
                'name' => 'Bulk call',
                'shortName' => 'bulk-call',
            ),
            9 => array(
                'name' => 'Undecided call',
                'shortName' => 'undecided-call'
            ),
            10 => array(
                'name' => 'Mailreorder call',
                'shortName' => 'mr-call'
            )
        );
        $callCount = array();
        foreach ($extendedTypes as $key => $value){
            $Query = "";
            if($product !=""){
                $Query.= " and phone_order_outbound.productID = '$product' ";
            }


            $Query.= " and phone_order_outbound.state = '$state' ";
            $Query.= " and DATE(phone_order_outbound.submitDate) >= '$from' ";
            $Query.= " and DATE(phone_order_outbound.submitDate) <= '$to' ";
            $Query.= " and phone_order_outbound.type = '$key' ";

            $results = $this->getOutboundQuery($Query);
            //var_dump($results);die();

            $outOrderSuccessful = 0;
            $countAnswered      = 0;
            $countFinish        = 0;
            $orderSum           = 0;
            foreach ($results as $result){

                if ($result['status'] == 7 || $result['status'] == 6 || $result['status'] == 12){
                    $countAnswered++;
                    if ($result['status'] == 7 || $result['status'] == 12){
                        $outOrderSuccessful++;
                    }
                }

                if ($result['status'] == 7 || $result['status'] == 12){
                    $countFinish++;
                    $orderSum  = $orderSum + ($result['newPrice'] / $result['exchange']);
                    $orderArr[$result['state']] = $orderArr[$result['state']] + 1;
                }
            }

            $orderPercent  = round(($outOrderSuccessful/$countAnswered)*100, 2);
            $perCall       = round($orderSum/$countAnswered, 2);

            $callCount['avg-value-' . $value['shortName']] = $perCall;
            $callCount['order-percent-' . $value['shortName']] = $orderPercent;

        }
        return $callCount;
    }

    /*
     * Blacklist / whitelist za outbound modul
     */
    public function getValidationList($query)
    {
        $sql = "SELECT phone_order_validation.*, states.title_eng AS stateTitle
                FROM phone_order_validation
                LEFT JOIN states ON phone_order_validation.state = states.code2
                WHERE {$query}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Average call value za za order source iz tabele phone_order_calls - podaci za glavni chart -
     */
    public function getDataForInboundCharts($statesInbound, $Query)
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

            $chartsQuery = $Query . $sQ;

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
     * Statistika za operatore
     */
    public function getDataOperator($columns="*",$kveri="1")
    {
        $kveri = "SELECT phone_order_outbound.id as id, phone_order_users.state AS state, called_time, phone_order_users.username, phone_order_outbound.newPrice, phone_order_outbound.price,
                    phone_order_outbound.operator AS ouid,
                    COUNT(*) as callNums,
                    SUM(IF( phone_order_outbound.status = 7, 1, 0)) as status7,
                    SUM(IF( phone_order_outbound.status = 6, 1, 0)) as status6,
                    SUM(IF( phone_order_outbound.status = 12, 1, 0)) as status12,
                    SUM(IF( phone_order_outbound.status = 9, 1, 0)) as status9,
                    SUM(IF( phone_order_outbound.status != 6  && phone_order_outbound.status != 7 && phone_order_outbound.status != 12, 1, 0)) as otherNum,
                    SUM(IF( phone_order_outbound.newPrice > phone_order_outbound.price, 1, 0 )) AS upsells,
                    SUM(IF( phone_order_outbound.called_time ='' || phone_order_outbound.callEnd = '', 0, TIMESTAMPDIFF(SECOND, phone_order_outbound.called_time , phone_order_outbound.callEnd))) as duration
                    FROM phone_order_outbound
                    LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
				    WHERE {$kveri} GROUP BY phone_order_outbound.operator ORDER BY called_time DESC ";
        //var_dump($kveri);
        $results = $this->conn->fetchAll($kveri);
        return $results;
    }

    /*
     * Statistika za  CALL CENTRE
     */
    public function getDataOperatorCallCenter($columns="*",$kveri="1")
    {
        $kveri = "SELECT phone_order_outbound.id as id, phone_order_callcenter.state AS state, called_time, phone_order_outbound.newPrice, phone_order_outbound.price,
                    phone_order_outbound.operator AS ouid,phone_order_callcenter.name as callcentar,
                    COUNT(*) as callNums,
                    SUM(IF( phone_order_outbound.status = 7, 1, 0)) as status7,
                    SUM(IF( phone_order_outbound.status = 6, 1, 0)) as status6,
                    SUM(IF( phone_order_outbound.status = 12, 1, 0)) as status12,
                    SUM(IF( phone_order_outbound.status = 9, 1, 0)) as status9,
                    SUM(IF( phone_order_outbound.status != 6  && phone_order_outbound.status != 7 && phone_order_outbound.status != 12, 1, 0)) as otherNum,
                    SUM(IF( phone_order_outbound.newPrice > phone_order_outbound.price, 1, 0 )) AS upsells,
                    SUM(IF( phone_order_outbound.called_time ='' || phone_order_outbound.callEnd = '', 0, TIMESTAMPDIFF(SECOND, phone_order_outbound.called_time , phone_order_outbound.callEnd))) as duration
                    FROM phone_order_outbound
                    LEFT JOIN phone_order_users ON phone_order_outbound.operator = phone_order_users.id
                    LEFT JOIN phone_order_callcenter ON phone_order_callcenter.id = phone_order_users.operatorGroup
				    WHERE {$kveri} ORDER BY called_time DESC ";
        //var_dump($kveri);
        $results = $this->conn->fetchAll($kveri);
        return $results;
    }

    public function getRequestItems($from, $to)
    {
        $sql = "SELECT * FROM phone_order_outbound_status_log
                WHERE 1
                AND status != 1
                AND status != 11
                AND Date(datetime) >= '{$from}'
                AND Date(datetime) <= '{$to}'";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    public function getDataForNewOutboundStats($kveri)
    {
        $kveri = "SELECT phone_order_outbound.id as id,phone_order_outbound.state, phone_order_outbound.productID,
                   
                    SUM(IF( phone_order_outbound.status = 7 || phone_order_outbound.status = 12, phone_order_outbound.newPrice/phone_order_smsprices.exchange, 0)) as orderSum,
                    COUNT(*) as callNums,
                    SUM(IF( phone_order_outbound.status = 7, 1, 0)) as status7,
                    SUM(IF( phone_order_outbound.status = 6, 1, 0)) as status6, 
                    SUM(IF( phone_order_outbound.status = 12, 1, 0)) as status12, 
                    SUM(IF( phone_order_outbound.status = 9, 1, 0)) as status9, 
                    SUM(IF( phone_order_outbound.status != 6 && phone_order_outbound.status != 7 && phone_order_outbound.status != 9 && phone_order_outbound.status != 12, 1, 0)) as countOther,
                    SUM(IF( phone_order_outbound.newPrice > phone_order_outbound.price, 1, 0 )) AS upsellsCount,
                    SUM(IF( documents.orderstatus = 'R' || documents.orderstatus = 'M', 1, 0)) as countRe
                    FROM phone_order_outbound
                    LEFT JOIN phone_order_smsprices ON phone_order_outbound.state = phone_order_smsprices.state 
                    LEFT JOIN orders ON (phone_order_outbound.submitID = orders.submitId AND phone_order_outbound.submitID != 0)
                    LEFT JOIN documents ON orders.referenceinvoice = documents.id 
				    WHERE 1 AND phone_order_outbound.state != 'AL' AND phone_order_outbound.state != 'XK' {$kveri} ";
        // var_dump($kveri);
        $results = $this->conn->fetchAll($kveri);
        return $results;
    }

    /*
     * Kupljenje podataka iz phone_order_switch
     */
    public function getOutboundSwitchProducts($query)
    {
        $sql = "SELECT *
                FROM phone_order_switch
                WHERE 1 {$query}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
    * Kupljenje podataka iz phone_order_costs
    */
    public function getCosts($query)
    {
        $sql = "SELECT *
                FROM phone_order_costs
                WHERE 1 {$query}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    public function updateCallersData($id,$name,$surname,$address,$homeNo,$city,$postal,$telephone,$email,$birthdate){

        $sql = "UPDATE phone_order_callers
                SET name='$name', surname = '$surname', address = '$address', houseno = '$homeNo', postal = '$postal', city = '$city', phone = '$telephone', mail = '$email', birthdate = '$birthdate'
                WHERE id='$id'
                LIMIT 1";


        $all = $this->conn->executeQuery($sql);
        return $all;

    }

    public function updateCallerYears($id,$years){

        $sql = "UPDATE phone_order_callers
                SET years='$years' 
                WHERE id='$id'
                LIMIT 1";

        $all = $this->conn->executeQuery($sql);
       return $all;

    }

    public function getTestUser43()
    {
        $sql = "SELECT *
                FROM phone_order_outbound
                WHERE id = 43";


        $results = $this->conn->fetchAssoc($sql);
        return $results;
    }
    public function restTestUser43()
    {

    }
    public function setForMainPanelTestUser43()
    {

    }

    public function getCancelRowOmg($submitId){
//        $sql =  "SELECT *
//                 FROM orders 
//                 WHERE submitId = '$submitId'";

        $sql = "SELECT DATE(orders.orderdate) AS orderDate, orders.product_name,  orders.product,
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
                WHERE submitId = '$submitId' 
                GROUP BY orders.order_id
                ORDER BY orders.orderdate DESC              
                ";
        $results = $this->conn->fetchAssoc($sql);
        return $results;
        
        
    }
}