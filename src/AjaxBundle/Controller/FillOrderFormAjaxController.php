<?php
namespace AjaxBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


use AppBundle\Entity\Main;
use AppBundle\Entity\SMS;
use AppBundle\Entity\OMG;
use AppBundle\Entity\STATS;
use AppBundle\Entity\Settings;


class FillOrderFormAjaxController extends Controller
{
    public function ajaxAction()
    {

        $conn = $this->get('database_connection');

        $_sms = new SMS($conn);
        $_main = new Main($conn);
        $_omg = new OMG($conn);
        $_settings = new Settings($conn);
        $_stats = new STATS($conn);


        $request    = Request::createFromGlobals();

        $post = $request->request->get('action');


        if (isset($post)) {

            switch ($post) {

                case "fillOrderFormByName":
                    $name_content = $_POST['cName'];
                    $surname_content = $_POST['cSurname'];
                    $state_content = $_POST['cCountry'];
                    //$ed_content = urlencode($ee_content);
                    $q = "SELECT orders.*, customers.postoffice AS postoffice FROM orders
					LEFT JOIN customers ON orders.customer_id = customers.id
					WHERE name = '$name_content' AND surname = '$surname_content' AND orders.state='$state_content' ORDER BY order_id DESC LIMIT 1 ";
                    //$eQuery = mysql_query($q);
                    $eQuery = $conn->fetchAssoc($q);
                    if (count($eQuery) > 0){

                        while($eQuery)
                        {
                            $eArr = array(
                                'name'      => $eQuery["name"],
                                'surname'   => $eQuery["surname"],
                                'address'   => $eQuery["address"],
                                'postcode'  => $eQuery["postoffice"],
                                'city'      => $eQuery["city"],
                                'telephone' => $eQuery["telephone"],
                                'email'     => $eQuery["email"]
                            );
                            return new Response(json_encode($eArr)) ;  // Send back to AJAX
                        }
                    } else {
                        return new Response("0");				  // No records
                    }
                break;
                
                case "fillOrderFormByNameNew":
                    $name_content    = $_POST['cName'];
                    $surname_content = $_POST['cSurname'];
                    $state_content   = $_POST['cCountry'];
                    //$ed_content = urlencode($ee_content);

                    $q = "SELECT orders.*, customers.postoffice AS postoffice
					FROM orders
					LEFT JOIN customers ON orders.customer_id = customers.id
						WHERE name LIKE '%$name_content%'
						AND surname LIKE '%$surname_content%'
						AND orders.state='$state_content'
						GROUP BY email
						ORDER BY order_id DESC";

                    //$eQuery = mysql_query($q);
                    $eQuery = $conn->fetchAssoc($q);
                    if (count($eQuery) > 0){
                        $increment = 0;
                        $eArr = array();
                        while($eQuery)
                        {
                            $eArr[] = array(
                                'name'      => $eQuery["name"],
                                'surname'   => $eQuery["surname"],
                                'address'   => $eQuery["address"],
                                'postcode'  => $eQuery["postoffice"],
                                'city'      => $eQuery["city"],
                                'telephone' => $eQuery["telephone"],
                                'email'     => $eQuery["email"]
                            );

                            //print_r($eArr);  // Send back to AJAX

                        }

                        return new Response(json_encode($eArr)) ;  // Send back to AJAX
                    } else {
                        return new Response("0");				  // No records
                    }
                break;
//                case "fillOrderFormByPhone":
//                    $phone_content 		= $_POST['cName'];
//                    $state_content 		= $_POST['cCountry'];
//                    //$ed_content = urlencode($ee_content);
//                    $q = "SELECT orders.*, customers.postoffice AS postoffice FROM orders
//					LEFT JOIN customers ON orders.customer_id = customers.id
//					WHERE name = '$name_content' AND surname = '$surname_content' AND orders.state='$state_content' ORDER BY order_id DESC LIMIT 10 ";
//                    //$eQuery = mysql_query($q);
//                    $eQuery = $conn->fetchAssoc($q);
//                    if (count($eQuery) > 0){
//
//                        while($eQuery=mysql_fetch_array($eQuery))
//                        {
//                            $eArr = array(
//                                'name' => $eQuery["name"],
//                                'surname' => $eQuery["surname"],
//                                'address' => $eQuery["address"],
//                                'postcode' => $eQuery["postoffice"],
//                                'city' => $eQuery["city"],
//                                'telephone' => $eQuery["telephone"],
//                                'email' => $eQuery["email"]
//                            );
//                            echo json_encode($eArr);  // Send back to AJAX
//                        }
//                    } else {
//                        echo "0";				  // No records
//                    }
//                    break;


            }

        }






    }

}