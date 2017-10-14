<?php

namespace AppBundle\Entity;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;


class Report
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    /*
     * Funkcija za listanje Izvjestaja o poslanim porukama
     */
    public function getSentSMS($columns,$query="") {
        $sql = "SELECT * FROM smsMessages
               	WHERE {$query}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }
    
    /*
     * Funkcija za listanje statusa brojeva
     */
    public function getPhoneStatus($query="") {
        $sql = "SELECT orders.submitId AS submitId, orders.orderdate AS orderDate, orders.state AS state, orders.telephone AS phone, sms_notifications.confirm AS delivered,
                        sms_notifications.hlrConfirm AS hlrstatus, sms_notifications.twillioConfirm AS twstatus
                FROM orders
                LEFT JOIN sms_notifications ON orders.submitId = sms_notifications.submitID
               	WHERE {$query}";
       $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Funkcija za listanje Responsa lookupa na Twillio servis
     */
    public function getTwillioStatus($query="") {
        $sql = "SELECT orders.submitId AS submitId, orders.orderdate AS orderDate, orders.state AS state, orders.telephone AS phone, sms_twillio.lookup_response AS lookup,
                        sms_twillio.call_response AS callr, sms_twillio.filteredPhone AS filteredPhone
                FROM sms_twillio
                LEFT JOIN orders ON sms_twillio.submitID = orders.submitId
               	WHERE {$query}";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Funkcija za listanje Izvjestaja o poslanim porukama
     */
    public function getSentSMSCount($columns,$query="") {
        $sql = "SELECT COUNT(*) as broj, smsMessages.from AS sender, SUM(smsMessages.smsCount) AS quantity, states.title_eng AS title, states.code2 AS code2 
                FROM smsMessages
                LEFT JOIN smsMessages_ID on smsMessages.id = smsMessages_ID.sms_ID
                LEFT JOIN states ON smsMessages_ID.state_id = states.id
               	WHERE {$query} GROUP BY smsMessages.from, smsMessages_ID.state_id ";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Funkcija za izlistavanje zahteva prevoda za proizvode
     */
    public function getTmRequestReport($query)
    {
        $sql         = "SELECT phone_order_TM.id AS tm_id, products.title as product_title, productDescription.state, phone_order_TM.sentTime, phone_order_TM.getTime, products.id AS product_id, phone_order_TM.TMPullBack as status
                          FROM phone_order_TM
                          LEFT JOIN productDescription ON productDescription.id = phone_order_TM.descID
                          LEFT JOIN products ON products.id = productDescription.productId
                          WHERE 1 {$query}";
        //var_dump($q);
        $results = $this->conn->fetchAll($sql);

        return $results;
    }

    /*
     * Funkcija za izlistavanje usera i njihovih logovanja-
     */
//    public function getUserLogsInformations($query="")
//    {
//        $sql         = "SELECT phone_order_users.fullname, phone_order_users.state, phone_order_users.operatorGroup, phone_order_callcenter.name as call_centar_group, phone_order_user_logs.datetime_login, phone_order_user_logs.datetime_logout, phone_order_user_logs.logout_type_id
//                          FROM phone_order_users
//                          INNER JOIN phone_order_callcenter ON phone_order_callcenter.id = phone_order_users.operatorGroup
//                          LEFT JOIN phone_order_user_logs ON phone_order_user_logs.phone_order_user_id = phone_order_users.id
//                          WHERE {$query}";
//        //var_dump($q);
//        $results = $this->conn->fetchAll($sql);
//        //var_dump($q );
//        return $results;
//    }

    public function getUserLogsInformations($query="")
    {
        $sql         = "SELECT phone_order_users.fullname, phone_order_users.state, phone_order_users.operatorGroup, phone_order_callcenter.name as call_centar_group, phone_order_user_logs.datetime_login,
                      phone_order_user_logs.datetime_logout, phone_order_user_logs.logout_type_id, phone_order_user_logs.ip_address, phone_order_user_logs.datetime_activity
                      FROM phone_order_user_logs
                      INNER JOIN phone_order_users ON phone_order_user_logs.phone_order_user_id = phone_order_users.id
                      INNER JOIN phone_order_callcenter ON phone_order_callcenter.id = phone_order_users.operatorGroup
                      WHERE {$query}";
        //var_dump($q);
        $results = $this->conn->fetchAll($sql);
        //var_dump($q );
        return $results;
    }

    
    public function getUserQueryLogsInf($query="")
    {
        $sql         = "SELECT phone_order_users.fullname, phone_order_users.state, phone_order_users.operatorGroup, phone_order_callcenter.name as call_centar_group,
                      phone_order_user_query_log.phone_order_user_id, phone_order_user_query_log.query_type, phone_order_user_query_log.query_string, phone_order_user_query_log.execution_datetime,
                      phone_order_user_query_log.row_id
                      FROM phone_order_user_query_log
                      INNER JOIN phone_order_users ON phone_order_user_query_log.phone_order_user_id = phone_order_users.id
                      INNER JOIN phone_order_callcenter ON phone_order_callcenter.id = phone_order_users.operatorGroup
                      WHERE {$query}";
        //var_dump($q);
        $results = $this->conn->fetchAll($sql);
        //var_dump($q );
        return $results;
    }
    
    /*
     * call centri
     */
    public function getCallCenterList(){
        $sql = "SELECT phone_order_callcenter.id, phone_order_callcenter.name, phone_order_callcenter.state
                FROM phone_order_callcenter
                ORDER BY phone_order_callcenter.id ASC";
       $results = $this->conn->fetchAll($sql);

        return $results;
    }

    /*
     * callers
     */
    public function getCallersinfo($query="")
    {
        $sql         = "SELECT *
                      FROM phone_order_callers
                      WHERE {$query}";
       // var_dump($sql);
        $results = $this->conn->fetchAll($sql);
        //var_dump($q );
        return $results;
    }
}