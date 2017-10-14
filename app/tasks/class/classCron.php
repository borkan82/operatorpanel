<?php
/**********************************************************************
 *																	  *
 * --------- Klasa za pozive na OMG bazu vezano za ordere -----       *
 * 																	  *
 * 	@Author Boris  							  						  *
 *  10/2015															  *
 **********************************************************************/
class Cron
{
    /**********************************************************************
     * ------------------------ Priprema klase --------------------       *
     **********************************************************************/
    public function __construct($db) {
        if ($db) {
            $this->db = $db;
        }
    }
    public function getCampaignListNames(){

        $sql ="SELECT CampManagement.CampaignName as campaign, CampManagement.id as id
                FROM CampManagement
                GROUP BY CampManagement.id";
        var_dump($sql);
        $results = $this->db->query($sql,2);
        $camps=array();
        foreach ($results as $result){
            $camps[$result['campaign']]= $result['id'];
        }
        return $camps;

    }
    public function getCampaignList($query){

        $sql ="SELECT CampManagement.id as campaign_id, CampManagement.CampaignName as campaign, states.id as state_id, CampManagement.Country as state, CampManagement.product as product_id,  products.title as product,
                       Datesend as date_sent, Datemade as date_made, status, RecipientNo,  price, upsellPrice as upsell, active as campaign_status, selectedMessages, splitType
                FROM CampManagement
                INNER JOIN products ON CampManagement.product = products.id
                INNER JOIN states on CampManagement.Country = states.code2
                WHERE 1 {$query}
                GROUP BY CampManagement.Datesend,CampManagement.id ORDER BY CampManagement.id";
        //var_dump($sql); die();
        $results = $this->db->query($sql,2);
        return $results;

    }

//    public function getOrdersForUpdate ($query){
//
//        $sql ="SELECT CampManagement.CampaignName as campaign, CampManagement.id as campaign_id,
//                COUNT(*) as total_calls,
//                SUM(IF( phone_order_calls.success = 'ORDERED!' , 1, 0)) as order_count,
//                SUM(IF( phone_order_calls.success = 'CANCELED!', 1, 0)) as cancel_count,
//                SUM(IF(phone_order_calls.success = 'NO ORDER!', 1, 0)) AS noOrder_count,
//
//                SUM(IF(tmp.order_status = 104, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumReturn`,
//                SUM(IF(tmp.order_status = 105, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumRefund`,
//                SUM(IF(tmp.order_status = 103,IFNULL(tmp.document_value,0),0))
//                    - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.document_value_vat,0),0))
//                    - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0))
//                    - SUM(IF(tmp.document_id IS NOT NULL ,IFNULL(tmp.shipping_cost,0),0)) AS `t_gross_profit`
//                FROM CampManagement
//                LEFT JOIN phone_order_calls ON phone_order_calls.campaignId = CampManagement.CampaignName
//                LEFT JOIN orders ON (phone_order_calls.orderSubmitId = orders.submitId AND phone_order_calls.orderSubmitId != 0)
//                LEFT JOIN analytics_sales_upsell_order as tmp ON (orders.order_id = tmp.order_id AND orders.order_id != 0)
//                WHERE 1 {$query}
//
//                GROUP BY CampManagement.id ORDER BY phone_order_calls.id DESC";
//        var_dump($sql);
//        $results = $this->db->query($sql,2);
//        return $results;
//    }

    public function getOrdersForUpdate ($query){

        $sql ="SELECT CampManagement.CampaignName as campaign, CampManagement.id as campaign_id, 
                COUNT(*) as total_calls,
                SUM(IF( phone_order_calls.success = 'ORDERED!' , 1, 0)) as order_count,
                SUM(IF( phone_order_calls.success = 'CANCELED!', 1, 0)) as cancel_count,
                SUM(IF(phone_order_calls.success = 'NO ORDER!', 1, 0)) AS noOrder_count,
                
                SUM(IF(tmp.order_status = 104, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumReturn`,
                SUM(IF(tmp.order_status = 105, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumRefund`,
                SUM(IF(tmp.order_status = 103,IFNULL(tmp.document_value,0),0)) 
                    - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0)) 
                    - SUM(IF(tmp.document_id IS NOT NULL ,IFNULL(tmp.shipping_cost,0),0)) AS `t_gross_profit`
                FROM CampManagement
                LEFT JOIN phone_order_calls ON phone_order_calls.campaignId = CampManagement.CampaignName
                LEFT JOIN orders ON (phone_order_calls.orderSubmitId = orders.submitId AND phone_order_calls.orderSubmitId != 0)
                LEFT JOIN analytics_sales_upsell_order as tmp ON (orders.order_id = tmp.order_id AND orders.order_id != 0)
                WHERE 1 {$query}
                
                GROUP BY CampManagement.id ORDER BY phone_order_calls.id DESC";
        var_dump($sql);
        $results = $this->db->query($sql,2);
        return $results;
    }

//    public function getOrdersCampUpd ($query){
//
//        $sql ="SELECT orders.utm_campaign as campaign,
//                SUM(IF(tmp.order_status = 104, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumReturnCamp`,
//                SUM(IF(tmp.order_status = 105, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumRefundCamp`,
//                SUM(IF(tmp.order_status = 103,IFNULL(tmp.document_value,0),0))
//                    - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.document_value_vat,0),0))
//                    - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0))
//                    - SUM(IF(tmp.document_id IS NOT NULL ,IFNULL(tmp.shipping_cost,0),0)) AS `t_gross_profitCamp`
//                FROM orders
//
//                LEFT JOIN analytics_sales_upsell_order as tmp ON (orders.order_id = tmp.order_id AND orders.order_id != 0)
//                WHERE 1 AND orders.utm_source = 'sms' and orders.ordersource = 'LPB'
//
//                GROUP BY campaign";
//        //var_dump($sql);
//        $results = $this->db->query($sql,2);
//        return $results;
//    }

    public function getOrdersCampUpd ($query){

        $sql ="SELECT orders.utm_campaign as campaign, 
                SUM(IF(tmp.order_status = 104, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumReturnCamp`,
                SUM(IF(tmp.order_status = 105, IFNULL(tmp.document_value, 0) + IFNULL(tmp.document_value_vat,0), 0 )) AS `sumRefundCamp`,
                SUM(IF(tmp.order_status = 103,IFNULL(tmp.document_value,0),0)) 
                    - SUM(IF(tmp.order_status IN (103,105,106,107,108),IFNULL(tmp.products_cost,0),0)) 
                    - SUM(IF(tmp.document_id IS NOT NULL ,IFNULL(tmp.shipping_cost,0),0)) AS `t_gross_profitCamp`
                FROM orders
                
                LEFT JOIN analytics_sales_upsell_order as tmp ON (orders.order_id = tmp.order_id AND orders.order_id != 0)
                WHERE 1 AND orders.utm_source = 'sms' and orders.ordersource = 'LPB' 
                
                GROUP BY campaign";
        //var_dump($sql);
        $results = $this->db->query($sql,2);
        return $results;
    }

    public function getTranslationID(){

        $sql ="SELECT phone_order_message_translation.ID AS id,  phone_order_message_translation.state AS state,  phone_order_message_translation.messageID AS messageID,
                       phone_order_messages.position AS pozicija
                FROM phone_order_message_translation
                LEFT JOIN phone_order_messages ON phone_order_message_translation.messageID = phone_order_messages.ID";

        $results = $this->db->query($sql,2);
        return $results;
    }

    public function countBulkMessages($query){

        $sql ="SELECT messageId, COUNT(*) as broj, SUM(smsCount) AS smsCount FROM smsMessages
                WHERE 1 {$query} AND messageId LIKE 'sms%' GROUP BY messageId ORDER BY id desc";
        // var_dump($sql);
        $results = $this->db->query($sql,2);
        return $results;
    }

    public function getBulkOrders($query){

        $sql ="SELECT orders.utm_campaign AS utm_campaign, SUM(orderitems.decrease_quantity) AS quantity FROM orderitems
                  LEFT JOIN orders ON orderitems.order = orders.order_id
                  LEFT JOIN products ON orderitems.product = products.id
				  WHERE 1
				  {$query}
				  AND orders.utm_source = 'sms'
				  AND LEFT(orders.utm_campaign, 3) = 'sms'
				  AND orders.name NOT LIKE '%test%'
				  AND orders.surname NOT LIKE '%test%'
				  AND products.isPostage != 'Yes'
				  AND products.isService != 'Yes'
				  AND products.productType != '888'
				  AND products.productType != '999'
				  GROUP BY orderitems.order";
        //var_dump($sql);
        $results = $this->db->query($sql,2);
        return $results;
    }

    public function getSMSprices(){

        $sql ="SELECT phone_order_smsprices.id AS id, phone_order_smsprices.state AS state, phone_order_smsprices.price AS price, phone_order_smsprices.exchange AS exchval, states.title_eng AS title 
                        FROM phone_order_smsprices
                        LEFT JOIN states on phone_order_smsprices.state = states.code2
                        WHERE 1";

        $results = $this->db->query($sql,2);
        return $results;
    }

    public function getBulkShortLinkList($query){

        $sql ="SELECT phone_order_shorturlbulk.id as id,  phone_order_shorturlbulk.campaignID, CampManagement.CampaignName,
                SUM(IF( phone_order_shorturlbulk.dateVisited = '0000-00-00 00:00:00' , 1, 0)) as unopened,
                SUM(IF( phone_order_shorturlbulk.dateVisited != '0000-00-00 00:00:00', 1, 0)) as opened,
                COUNT(*) as sent
                FROM phone_order_shorturlbulk 
                LEFT JOIN CampManagement ON phone_order_shorturlbulk.campaignID = CampManagement.id
                WHERE 1 {$query}
                GROUP BY phone_order_shorturlbulk.campaignID";
        //var_dump($sql);
        $results = $this->db->query($sql,2);
        return $results;
    }

    public function getCampaignOrders(){

        $sql ="SELECT orders.utm_campaign, COUNT(*) as orderCount

                FROM orders
                WHERE 1 and orders.utm_source = 'sms' and orders.ordersource = 'LPB'
                
                GROUP BY orders.utm_campaign";

        $results = $this->db->query($sql,2);
        return $results;
    }

    public function insertOrUpdate(array $rows, $table){

        $first = reset($rows);

        $columns = implode( ',',
            array_map( function( $value ) { return "$value"; } , array_keys($first) )
        );
        $values = implode( ',', array_map( function( $row ) {
                return '('.implode( ',',
                    array_map( function( $value ) { if($value!=NULL && $value!='') return '"'.str_replace('"', '""', $value).'"'; else {return "NULL";}  ; }  , $row )
                ).')';
            } , $rows )
        );
        $updates = implode( ',',
            array_map( function( $value ) { return "$value = VALUES($value)"; } , array_keys($first) )
        );
        $sql = "INSERT INTO {$table}({$columns}) VALUES {$values} ON DUPLICATE KEY UPDATE {$updates}";


        $sql1 = str_replace("NULL", "0", $sql);
        //var_dump($sql1); die();

        $this->db->query($sql1,1);
    }
    
    public function getPeriodPerDay($startDate, $endDate){
        $begin = new DateTime( $startDate );
        $end = new DateTime( $endDate );
        $end = $end->modify( '+1 day' );

        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($begin, $interval ,$end);

        $period = array();
        foreach($daterange as $date) {
            $period[]=$date->format("Y-m-d");
        }
        return $period;
    }


    /**
     * @param array $rows
     * @param $table - strin(table name)
     * @param $columnForUpdate (string, columns, comma separator)
     * @param $int (1- write columns you want) or (2 - exlude columns from default insert query)
     */
    public function insertOrUpdateNew(array $rows, $table, $columnForUpdate, $int){

        $first = reset($rows);

        $columns = implode( ',',
            array_map( function( $value ) { return "$value"; } , array_keys($first) )
        );
        $values = implode( ',', array_map( function( $row ) {
                return '('.implode( ',',
                    array_map( function( $value ) { if($value!=NULL && $value!='') return '"'.str_replace('"', '""', $value).'"'; else {return "NULL";}  ; }  , $row )
                ).')';
            } , $rows )
        );


        if (isset($columnForUpdate) && !empty($columnForUpdate) ){
            $columnForUpdate = explode(",", $columnForUpdate);
            if ($int == 1){
                var_dump('uslo u 1');
            } elseif ($int == 2){
                var_dump('uslo u 2');
                $countColumn = count($columnForUpdate);
                $countFirst  = count(array_keys($first));
                if ($countFirst > $countColumn){
                    $columnForUpdate = array_diff(array_keys($first), $columnForUpdate);
                } else{
                    $columnForUpdate = array_keys($first);
                    var_dump('uslo else');
                }
            }else{
                $columnForUpdate = array_keys($first);
            }
            $updates = implode( ',',
                array_map( function( $value ) { return "$value = VALUES($value)"; } , $columnForUpdate )
            );
        }  else {

            $updates = implode( ',',
                array_map( function( $value ) { return "$value = VALUES($value)"; } , array_keys($first) )
            );
        }
        $sql = "INSERT INTO {$table}({$columns}) VALUES {$values} ON DUPLICATE KEY UPDATE {$updates}";
        $sql1 = str_replace("NULL", "0", $sql);
        var_dump($sql1);

        $this->db->query($sql1,1);
    }

}
?>