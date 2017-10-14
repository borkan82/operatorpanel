<?php

namespace AppBundle\Entity;

class STATS
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    /*
     * Brojanje izvora narudzbe
     */
    function countOrderSource($query)
    {
        $sql    = "SELECT phone_order_orderTypes.title as title, COUNT(*) as broj  FROM `phone_order_calls`
		           LEFT JOIN phone_order_orderTypes ON phone_order_calls.orderType = phone_order_orderTypes.id
		           WHERE {$query}
		           GROUP BY phone_order_calls.orderType";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Brojanje tipa narudzbe
     */
    function countCallType($query)
    {
        $sql = "SELECT phone_order_calls.type as tip, COUNT(*) as broj  FROM `phone_order_calls`
		        WHERE {$query}
		        GROUP BY phone_order_calls.type";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Brojanje tipa pitanja narudzbe
     */
    function countQuests($query)
    {
        $sql = "SELECT otherOpt, COUNT(*) as broj  FROM `phone_order_calls`
                WHERE {$query}
                GROUP BY otherOpt";
        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Brojanje ORDER/CANCELED/OTHER
     */
    function countOrderSucess($query)
    {
        $sql = "SELECT success, COUNT(*) as broj  FROM `phone_order_calls`
                WHERE {$query}
                GROUP BY success";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Brojanje Otkazanih narudzbi
     */
    function countCancel($query)
    {
        $sql = "SELECT cancelReason, COUNT(*) as broj  FROM `phone_order_calls`
                WHERE {$query}
                AND success LIKE 'CANCELED!'
                GROUP BY cancelReason";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Statistika za operatore
     */
    public function getDataOperator($columns="*",$kveri="1")
    {
        $sql = "SELECT phone_order_calls.id as id, phone_order_users.state AS state, date, phone_order_users.username AS opName,
                COUNT(*) as callNums, phone_order_calls.operator AS ouid,
                SUM(IF( phone_order_calls.success = 'ORDERED!', 1, 0)) as orderedNum,
                SUM(IF( phone_order_calls.success = 'CANCELED!', 1, 0)) as canceledNum,
                SUM(IF( phone_order_calls.success = 'NO ORDER!', 1, 0)) as otherNum,
                TIME(SUM(phone_order_calls.duration)) as durationTotal
                FROM phone_order_calls
                LEFT JOIN phone_order_orderTypes ON phone_order_calls.orderType = phone_order_orderTypes.id
                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                WHERE {$kveri} GROUP BY operator ORDER BY DATE DESC ";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Statistika za call centre
     */
    public function getDataCallCentar($columns="*",$kveri="1")
    {
        $sql = "SELECT phone_order_calls.id as id, phone_order_callcenter.state AS state, date, phone_order_callcenter.name AS opName,
                phone_order_calls.operator AS ouid, phone_order_users.operatorGroup,
                COUNT(*) as callCount,
                SUM(IF( phone_order_calls.success = 'ORDERED!' && phone_order_calls.cancel = 0 , 1, 0)) as orderCount,
                SUM(IF( phone_order_calls.success = 'ORDERED!' && phone_order_calls.cancel = 0 && phone_order_calls.ePrice > phone_order_calls.bPrice , 1, 0)) as upsellCount,
                SUM(IF( phone_order_calls.success = 'ORDERED!' && phone_order_calls.cancel = 0 , phone_order_calls.ePrice/phone_order_smsprices.exchange, 0)) as orderSum,
                SUM(IF( phone_order_calls.success = 'NO ORDER!' && phone_order_calls.cancel = 0 , 1, 0)) as otherCount,
                SUM(IF( phone_order_calls.success = 'CANCELED!' && phone_order_calls.cancel = 1, 1, 0)) as cancelCount,
                SUM(TIME_TO_SEC(phone_order_calls.duration)) as allDuration
            
                
                FROM phone_order_calls
                LEFT JOIN phone_order_smsprices ON phone_order_calls.state =phone_order_smsprices.state 
                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                JOIN phone_order_callcenter ON phone_order_callcenter.id = phone_order_users.operatorGroup
                WHERE {$kveri} ORDER BY DATE DESC ";
        //var_dump($sql);

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Duzine poziva po operatoru
     */
    public function getCallDurations($columns="*",$kveri="1")
    {
        $sql = "SELECT phone_order_calls.duration as durationTime, phone_order_calls.start as startT, phone_order_calls.end as endT, phone_order_users.username AS opName
                FROM phone_order_calls
                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                WHERE {$kveri} ORDER BY DATE DESC ";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     *  Duzine poziva po call centru
     */
    public function getCallDurationsCallCentres($columns="*",$kveri="1")
    {
        $sql = "SELECT phone_order_calls.duration as durationTime, phone_order_calls.start as startT, phone_order_calls.end as endT, phone_order_callcenter.name AS opName, phone_order_callcenter.id AS callcenterId
                FROM phone_order_calls
                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                JOIN phone_order_callcenter ON phone_order_callcenter.id = phone_order_users.operatorGroup
                WHERE {$kveri} ORDER BY DATE DESC ";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Orderi po pozivu
     */
    public function getOrdersByCall($query,$numQ="")
    {
        $sql = "SELECT phone_order_calls.id AS callId, orders.order_id, phone_order_users.name AS name, orders.product AS product, phone_order_users.username AS username FROM phone_order_calls
                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                LEFT JOIN orders ON phone_order_calls.orderSubmitId = orders.submitId
                WHERE {$query} AND phone_order_calls.orderSubmitId > 0 {$numQ} ";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Orderi po pozivu (CALL CENTRI)
     */
    public function getOrdersByCallCenter($query,$numQ="")
    {
        $sql = "SELECT phone_order_calls.id AS callId, orders.order_id, phone_order_callcenter.name AS callcenter, orders.product AS product, phone_order_users.operatorGroup AS callcenterId
                FROM phone_order_calls
                LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                LEFT JOIN orders ON phone_order_calls.orderSubmitId = orders.submitId
                LEFT JOIN phone_order_callcenter ON phone_order_callcenter.id = phone_order_users.operatorGroup
                WHERE {$query} AND phone_order_calls.orderSubmitId > 0 {$numQ} ";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }


    public function getDataForNewInboundStats($kveri)
    {
        $sql = "SELECT phone_order_calls.id as id,phone_order_calls.state, phone_order_calls.product,
                COUNT(*) as callCount,
                SUM(phone_order_calls.ePrice/phone_order_smsprices.exchange) AS revenue,
                SUM(IF( phone_order_calls.success = 'ORDERED!' && phone_order_calls.cancel = 0, 1, 0)) as orderCount,
                SUM(IF( phone_order_calls.success = 'ORDERED!' && phone_order_calls.cancel = 0 && phone_order_calls.ePrice > phone_order_calls.bPrice, 1, 0)) as upsellsCount,
                SUM(IF( phone_order_calls.success = 'CANCELED!' && phone_order_calls.cancel = 1, 1, 0)) as cancelCount,
                SUM(IF( phone_order_calls.success = 'NO ORDER!' && phone_order_calls.cancel = 0, 1, 0)) as otherCount,
                SUM(IF( phone_order_calls.success = 'ORDERED!' && phone_order_calls.cancel = 0, phone_order_calls.ePrice/phone_order_smsprices.exchange, 0)) as orderSum,
                SUM(IF( documents.orderstatus = 'R' || documents.orderstatus = 'M', 1, 0)) as countRe
                FROM phone_order_calls
                LEFT JOIN phone_order_smsprices ON phone_order_calls.state = phone_order_smsprices.state 
                LEFT JOIN orders ON (phone_order_calls.orderSubmitId = orders.submitId AND phone_order_calls.orderSubmitId != 0)
                LEFT JOIN documents ON orders.referenceinvoice = documents.id 
                WHERE 1 AND phone_order_calls.state != 'AL' phone_order_calls.state != 'XK' {$kveri} ";

        $results=$this->conn->fetchAll($sql);
        return $results;
    }
}
