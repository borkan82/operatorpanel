<?php

namespace AjaxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\OMG;
use AppBundle\Entity\SMS;

class AjaxBulkLinksController extends Controller
{


    /**
     * @Template(engine="php")
     */

    public function indexAction()
    {
        $conn       = $this->get('database_connection');
        $_omg       = new OMG($conn);
        $_sms       = new SMS($conn);

        $todayDate = Date('Y-m-d');
        $startDate = Date('Y-m-01');

        $request    = Request::createFromGlobals();
        $queryStr   = explode("&",$request->getQueryString());
        $queryArr   = Array();

        foreach ($queryStr AS $q) {
            $split = explode("=",$q);
            $queryArr[$split[0]] = $split[1];
        }

        $state       = $queryArr['state'];
        $product     = $queryArr['product'];
        $campaign    = $queryArr['campaign'];
        $from        = $queryArr['from'];
        $to          = $queryArr['to'];

        $brakeBy    = $queryArr['brake'];
        //var_dump($brakeBy);

        $_products = $_omg->getProductList("id, title", "1");

        $ppppp= array();

        foreach ($_products as $majProduct){
            $ppppp[$majProduct['id']] = $majProduct['title'];
        }



        
        $joinLine   = "";
        $rowField   = "";
        $rowFieldOrders = '';
        if ($brakeBy == "CampManagement.product"){
            $joinLine   = "LEFT JOIN products ON phn.product = products.id";
            $rowField  = "products.title AS title,";
            $rowFieldOrders = '';

            // $rowFieldOrders
        } else if ($brakeBy == "CampManagement.Country"){
            $joinLine   = "LEFT JOIN states ON CampManagement.Country = states.code2";
            $rowField  = "states.title_eng AS title,";
          
        } else if ($brakeBy == "phn.campaignID"){
            $rowField  =  "CampManagement.CampaignName as title,";
        }
        //var_dump($rowField);

        if(isset($state) && !empty($state)) {
            $scQ = " and CampManagement.Country = '$state' ";
            $ordSt     = " and orders.state = '$state'";
        } else {
            $state = "";
            $scQ  = "";
            $oordSt="";
        }
        if(isset($product) && !empty($product)) {
            $prQ = " and CampManagement.product = '$product' ";
//            $ordProduc = " and products.id = '$product' ";
            $ordProduc = " and orders.product_name = '$ppppp[$product]' ";
        } else {
            $product = "";
            $prQ = "";
            $ordProduc="";
        }
        if(isset($campaign) && !empty($campaign)) {
            $cQ  = " and CampManagement.CampaignName = '$campaign' ";
            $ordCamp   = " and orders.utm_campaign = '$campaign'";
        } else {
            $campaign = "";
            $cQ = ""; $ordCamp="";
        }
        if(isset($from) && !empty($from))        {
            $dfQ = " and CampManagement.Datemade >= '$from' ";
            $ordFrom   = " and orders.orderdate >= '$from' ";
        } else {
            $from = $startDate;
            $dfQ = " and CampManagement.Datemade >= '$from' ";
            $ordFrom = " and orders.orderdate >= '$from' ";
        }
        if(isset($to) && !empty($to))              {
            $dtQ = " and CampManagement.Datemade <= '$to' ";
            $ordTo     = " and orders.orderdate <= '$to' ";
        } else {
            $to = $todayDate;
            $dtQ = " and CampManagement.Datemade <= '$to' ";
            $ordTo = " and orders.orderdate <= '$to' ";
        }
        if(isset($brakeBy) && !empty($brakeBy))  { $brakeQ = $brakeBy;  } else { $brakeQ = "phn.campaignId"; }

        $query = "";
        $query .= $scQ;
        $query .= $prQ;
        $query .= $cQ;
        $query .= $dfQ;
        $query .= $dtQ;

        $queryOrders = "";
        $queryOrders .= $ordSt;
        $queryOrders .= $ordProduc;
        $queryOrders .= $ordCamp;
        $queryOrders .= $ordFrom;
        $queryOrders .= $ordTo;
        var_dump("SELECT {$rowField}
                                    SUM(IF( phn.dateVisited = '0000-00-00 00:00:00' , 1, 0)) as unopened,
                                    SUM(IF( phn.dateVisited != '0000-00-00 00:00:00', 1, 0)) as opened,
                                    COUNT(*) as sent
                                    FROM phone_order_shorturlbulk as phn
                                    LEFT JOIN CampManagement ON phn.campaignID = CampManagement.id
                                    {$joinLine}
                                    WHERE 1 {$query} 
                                    GROUP BY {$brakeQ}");


        $_data = $conn->fetchAll("SELECT {$rowField}
                                    SUM(IF( phn.dateVisited = '0000-00-00 00:00:00' , 1, 0)) as unopened,
                                    SUM(IF( phn.dateVisited != '0000-00-00 00:00:00', 1, 0)) as opened,
                                    COUNT(*) as sent
                                    FROM phone_order_shorturlbulk as phn
                                    LEFT JOIN CampManagement ON phn.campaignID = CampManagement.id
                                    {$joinLine}
                                    WHERE 1 {$query} 
                                    GROUP BY {$brakeQ}");
        $orders    = $_sms->getCampaignOrders($queryOrders);

        $ord = $conn->fetchAll("SELECT orders.utm_campaign, 
                                COUNT(*) as orderCount
                                FROM orders
                                WHERE 1 {$queryOrders} and orders.utm_source = 'sms' and orders.ordersource = 'LPB'
                                GROUP BY orders.utm_campaign");
        // var_dump($_data);
        $displayOrders =  array();
        foreach ($orders as $ord){
            $displayOrders[$ord['utm_campaign']]=$ord['orderCount'];
        }

        $html = '<div class="tableHolder" style="padding: 10px 10px 0 10px;width: 1400px;">
                    <div class="dayTable" style="width: 1400px;">
                    <table class="dayView compact" id="example">
                        <thead>
                            <tr>
                                <td>#</td>
                                <td>Campaign name</td>
                                <td>Sent</td>
                                <td>Opened</td>
                                <td>Unopened</td>
                                <td>Number of orders</td>
                            </tr>
                        </thead>
                        <tbody id="tabela">';

        $counter = 0;

        foreach ($_data as $row){

            $counter++;

            $html .= '<tr>';
            $html .= '<td>'.$counter.'</td>
                                    <td>'.$row["title"].'</td>
                                    <td>'.$row["sent"].'</td>
                                    <td>'.$row["opened"].'</td>
                                    <td>'.$row["unopened"].'</td>';
            if (array_key_exists($row["title"],$displayOrders )){
                $html .= '<td>'.$displayOrders[$row["title"]].'</td>';
            } else {
                $html .=  '<td>0</td>';
            }


            $html .= '</tr>';
        }

        $html .= '</tbody>
                </table>
            </div>
        </div>';

      


        return new Response(json_encode($html));
    }



}