<?php

namespace AjaxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\OMG;
use AppBundle\Entity\SMS;

class RequestHandlerAjaxController extends Controller
{
    public function indexAction()
    {
        ignore_user_abort(true);
        
        $conn = $this->get('database_connection');
        $_omg = new OMG($conn);
        
        $request    = Request::createFromGlobals();
        $action = $request->request->get('action');
        
        if(isset($action) && $action=='getLPdata') {
            $code = $request->request->get('phonecode');
            $state = $request->request->get('state');
//            $code=$_POST['phonecode'];
//            $state=$_POST['state'];
            if(!empty($code) && !empty($state))  {
                $lpData=$_omg->getLpData($code,$state);
                //echo lp data 
                echo json_encode($lpData);
                exit();
            }
            exit;
        }


        //get lp data by phonecode
        if(isset($_POST['action']) && $_POST['action']=='setCodeUsed')
        {

            if(!empty($_POST['id']))
            {
                // for tracking conversion
                $lpData=$_omg->getLpDataByID($_POST['id']);

                //set code as used
                $_omg->setUsed($_POST['id']);

                // track conversion
                if($lpData)
                {
                    $lpData['httpData'] = json_decode($lpData['httpData'], true);
                    if(isset($lpData['httpData']['rpdid'])){
                        $this->triggerConversion($lpData['httpData']['rpdid']);
                        echo $lpData['httpData']['rpdid'];
                        return new Response();
                    } else {
                        echo "-1";
                        return new Reponse(false);
                    }

                } else {
                    echo "-2";
                    return new Reponse(false);
                }

            } else {
                echo "-3";
                return new Reponse(false);
            }

        }
       
    }

    public function triggerConversion($rpdid, $value=1)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://www.rapidtrk.net/tracking/api/log_conversion.php?api=56745374');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "utm_medium={$rpdid}&value={$value}");
        curl_exec ($ch);
        curl_close($ch);
    }
}