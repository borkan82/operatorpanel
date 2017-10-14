<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;


class GettersController extends Controller
{

    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    public function getHoursMinuteSecunds($secunds)
    {
        //$secunds        = round($secunds,2);
        $hours          = floor($secunds / 3600);
        $mins           = floor($secunds / 60 % 60);
        $secs           = floor($secunds % 60);
        $hms  = sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
        return $hms;

    }
    /**
     * @Template(engine="php")
     */

    public function getUsersAction()
    {
        $kveri = "SELECT id, name, surname FROM `phone_order_users` WHERE 1";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getProductListAction($columns="*",$kveri="1")
    {
        $kveri = "SELECT $columns FROM products
                  WHERE $kveri ORDER BY title ASC";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }


    /**
     * @Template(engine="php")
     */

    public function getCallCenterListAction($group = "")
    {
        $kveri = "SELECT * FROM phone_order_callcenter
				  WHERE 1 {$group}";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getMainCallCenterListAction($group = "", $query = "")
    {
        $kveri = "SELECT * FROM phone_order_maincallcenter
				  WHERE 1 {$query} {$group}";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getStatesAction($query = "")
    {
        $kveri = "SELECT id, code2, title_eng, distro_smsFrom AS smsSender  FROM `states` WHERE 1 AND hasSales = 1 {$query} ORDER BY code2 ASC";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }


    /**
     * @Template(engine="php")
     */

    public function getDataRowsAction($columns="*",$kveri="1")
    {
    $kveri = "SELECT phone_order_calls.cPhone as cPhone, phone_order_calls.id as id, phone_order_calls.orderSubmitId as orderSubmitId, phone_order_calls.sessionID as sId, phone_order_calls.state AS state, title, code, start, end, duration, otherOpt, other, success,
                         cancelReason, date, type, productWork, getInvoice, buyStore, cancel, product, phone_order_users.name AS opName, special, bPrice, ePrice,
                         phone_order_smsprices.exchange AS exchange, phone_order_users.operatorGroup AS callCenterId, phone_order_calls.orderType, phone_order_calls.cancelStatus AS cancelStatus, phone_order_calls.flowType as flowType
                  FROM phone_order_calls
                  LEFT JOIN phone_order_orderTypes ON phone_order_calls.orderType = phone_order_orderTypes.id
                  LEFT JOIN phone_order_users ON phone_order_calls.operator = phone_order_users.id
                  LEFT JOIN phone_order_smsprices ON phone_order_calls.state = phone_order_smsprices.state
          WHERE {$kveri}";

    $results=$this->conn->fetchAll($kveri);
    return $results;
  }

    /**
     * @Template(engine="php")
     */
    public function getDataForInboundChartsAction($statesInbound, $Query){

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

            $results = $this->getDataRowsAction("*",$chartsQuery);

            foreach ($results as $result){
                $statesInbound[$result['state']]['revenue-' . $value['shortName']] = $statesInbound[$result['state']]['revenue-' . $value['shortName']] + ($result['ePrice'] / $result['exchange']);

                $statesInbound[$result['state']]['count-' . $value['shortName']]++;
            }
        }

        return $statesInbound;
    }

    /**
     * @Template(engine="php")
     */
    function getCompanyInfoAction($query = "")
    {
        $kveri = "SELECT companiesSupport.state AS code2, states.title_eng AS title_eng, companiesSupport.smsFrom AS smsSender
                  FROM `companiesSupport`
                  LEFT JOIN states ON companiesSupport.state = states.code2
                  WHERE 1 {$query}
                  GROUP BY companiesSupport.state
                  ORDER BY companiesSupport.state ASC";

        $results=$this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getOutboundCallTypes()
    {
        $kveri = "SELECT * FROM `phone_order_outbound_types` WHERE 1";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getOutboundStatusesAction()
    {
        $kveri = "SELECT * FROM `phone_order_outbound_call_status` WHERE 1";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getOutboundSubStatusesAction()
    {
        $kveri = "SELECT phone_order_outbound_call_substatus.id AS id, phone_order_outbound_call_substatus.title AS title, phone_order_outbound_call_status.id AS sid, phone_order_outbound_call_status.title AS stitle 
                  FROM `phone_order_outbound_call_substatus` 
                  LEFT JOIN phone_order_outbound_call_status ON phone_order_outbound_call_substatus.status_id = phone_order_outbound_call_status.id
                  WHERE 1 AND status_id != 0";
        //var_dump($kveri);
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getOutboundTypesAction()
    {
        $kveri = "SELECT * FROM `phone_order_outbound_types` ";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getOrderStatusesAction()
    {
        $kveri = "SELECT * FROM `analytics_sales_order_status` ";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getFlowTypesAction()
    {
        $kveri = "SELECT * FROM `phone_order_split_types` ";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

    /**
     * @Template(engine="php")
     */

    public function getActiveStatesAction()
    {
        $kveri = "SELECT code2, title_eng, name AS callCenterName FROM `phone_order_smsprices`
                      LEFT JOIN states ON phone_order_smsprices.state = states.code2
                      LEFT JOIN phone_order_callcenter ON states.code2 = phone_order_callcenter.state
                      WHERE 1 
                      AND hasSales = 1 
                      AND stateIsActive = 1 
                      AND phone_order_callcenter.state != 'TE'
                      ORDER BY code2 ASC";
        $results= $this->conn->fetchAll($kveri);
        return $results;
    }

}
?>