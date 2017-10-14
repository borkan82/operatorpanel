<?php

namespace AppBundle\Entity;

class TOTAL
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    /*
     *  Listanje ordera inbounda
     */
    public function getInboundOrders($query = " 1 ")
    {
        $sql = "SELECT state, DATE(phone_order_calls.date) AS callDate, COUNT(*) AS broj
                  FROM phone_order_calls
                  WHERE {$query}
                  AND phone_order_calls.success = 'ORDERED!'
                  GROUP BY DATE(phone_order_calls.date), state";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Listanje ordera outbounda
     */
    public function getOutboundOrders($query = " 1 ")
    {
        $sql = "SELECT state, DATE(phone_order_outbound.callEnd) AS callDate, COUNT(*) AS broj
                  FROM phone_order_outbound
                  WHERE {$query}
                  AND phone_order_outbound.status = '7'
                  AND phone_order_outbound.callEnd != ''
                  GROUP BY DATE(phone_order_outbound.callEnd), state";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Listanje ordera inbounda
     */
    public function getInboundEurOrders($query = " 1 ")
    {
        $sql = "SELECT phone_order_calls.state AS state, DATE(phone_order_calls.date) AS callDate, COUNT(*) AS counted, SUM(ePrice) AS broj, exchange
                  FROM phone_order_calls
                  LEFT JOIN phone_order_smsprices ON phone_order_calls.state = phone_order_smsprices.state
                  WHERE {$query}
                  AND phone_order_calls.success = 'ORDERED!'
                  GROUP BY DATE(phone_order_calls.date), phone_order_calls.state";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Listanje ordera outbounda
     */
    public function getOutboundEurOrders($query = " 1 ")
    {
        $sql = "SELECT phone_order_outbound.state AS state, DATE(phone_order_outbound.callEnd) AS callDate, COUNT(*) AS counted, SUM(newPrice) AS broj, exchange
                  FROM phone_order_outbound
                  LEFT JOIN phone_order_smsprices ON phone_order_outbound.state = phone_order_smsprices.state
                  WHERE {$query}
                  AND phone_order_outbound.status = '7'
                  AND phone_order_outbound.callEnd != ''
                  GROUP BY DATE(phone_order_outbound.callEnd), phone_order_outbound.state";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Listanje ordera inbounda
     */
    public function getInboundOrdersStatuses($query = " 1 ")
    {
        $sql = "SELECT DATE(phone_order_calls.date) AS callDate, COUNT(*) AS broj, documents.orderstatus AS docStatus
                  FROM phone_order_calls
                  LEFT JOIN orders ON (phone_order_calls.orderSubmitId = orders.submitId AND phone_order_calls.orderSubmitId != 0)
                  LEFT JOIN documents ON orders.order_no = documents.doc_reference
                  WHERE {$query}
                  AND phone_order_calls.success = 'ORDERED!'
                  GROUP BY DATE(phone_order_calls.date), documents.orderstatus";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Listanje ordera outbounda
     */
    public function getOutboundOrdersStatuses($query = " 1 ")
    {
        $sql = "SELECT DATE(phone_order_outbound.callEnd) AS callDate, COUNT(*) AS broj, documents.orderstatus AS docStatus
                  FROM phone_order_outbound
                  LEFT JOIN orders ON (phone_order_outbound.submitID = orders.submitId AND phone_order_outbound.submitID != 0)
                  LEFT JOIN documents ON orders.order_no = documents.doc_reference
                  WHERE {$query}
                  AND phone_order_outbound.status = '7'
                  AND phone_order_outbound.callEnd != ''
                  GROUP BY DATE(phone_order_outbound.callEnd), documents.orderstatus";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Listanje svih OMG PHN ordera
     */
    public function getAllOMGOrders($query = " 1 ")
    {
        $sql = "SELECT state, DATE(orders.orderdate) AS orderDate, extint2, utm_source, utm_campaign
                  FROM orders
                  WHERE {$query}
                  AND ordersource = 'PHN'";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Listanje svih OMG Mail ordera
     */
    public function getAllOMGMailOrders($query = " 1 ")
    {
        $sql = "SELECT state, DATE(orders.orderdate) AS orderDate, extint2, utm_source, utm_campaign, comment
                  FROM orders
                  WHERE {$query}
                  AND ordersource != 'PHN' AND (utm_source = 'mail' OR utm_source = 'mailreorder' OR (comment LIKE 'Canceled%' AND orderstatus != 12 AND orderstatus != 8 AND orderstatus != 10))";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

}
