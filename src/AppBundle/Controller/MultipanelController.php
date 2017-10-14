<?php

namespace AppBundle\Controller;

use AppBundle\Controller\LanguagesHelperController;

use AppBundle\Entity\Main;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use AppBundle\Controller\LanguagesHelperController;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\OMG;
use AppBundle\Entity\SMS;




class MultipanelController extends Controller
{
    private function checkUserPrivileges(){
//        $_main      = new Main($conn);
//        $checkUser = $_main->checkMutipanelPrivileges();
//        if ($checkUser == false) {
//            return $this->redirectToRoute('login', array('status'=>'3'));
//            //return $this->redirect('../login?status=3');
//
//        }

        $_main      = new Main();
        $loggedIn = $_main->checkUserIsLoggedIn();
        if($loggedIn == true){
            $checkUser  = $_main->checkMutipanelPrivileges();
            if ($checkUser == false){
                // return $this->redirect('./login?status=3');
                return $this->redirectToRoute('login', array('status'=>'2'));
            }
        } else {
            return $this->redirectToRoute('login', array('status'=>'3'));
        }
    }
    public function indexAction()
    {
        if( !is_null($this->checkUserPrivileges())) {
            return $this->checkUserPrivileges();
        }
        
        $state = $_SESSION['phUser']['state'];
        if ($_SESSION['phUser']['role'] == "A" && $state == "BA") {
            $request    = Request::createFromGlobals();
            $queryStr   = explode("&",$request->getQueryString());
            $queryArr   = Array();
            foreach ($queryStr AS $q) {
                $split = explode("=",$q);
                $queryArr[$split[0]] = $split[1];
            }
            $stateCode   = $queryArr['state'];

            if (isset($stateCode) && !empty($stateCode)){
                $stil = "display:block;";
            } else {
                $stil = 'display:none;';
            }


            $title = 'Multi panel';

            $user = $_SESSION['phUser'];
            $korisnik = array(

                'korisnickoIme' => $user['username'],
                'Ime'=> $user['name'],
                'Prezime' =>$user['surname']

            );

            $conn     = $this->get('database_connection');

            $_omg     = new OMG($conn);
            $_sms     = new SMS($conn);


            $countOutbound  = $_omg->countOutbound($state);
            $typeArr = Array(
                "1"  =>"AdCombo",
                "2"  =>"Cancel User",
                "3"  =>"Upsell",
                "5  "=>"Form fill brake",
                "6"  =>"Order fill brake",
                "7"  => 'Reorder call',
                "8"  => 'Bulk call',
                "9"  => 'Undecided call',
                "10" => 'Mailreorder call',
                "11" => 'SMS Link'
            );


            $visibility1     = "display:none";
            $visibility2     = "display:none";
            $visibility3     = "display:none";
            $visibility4     = "display:none";
            $visibility5     = "display:none";
            $visibility6     = "display:none";
            $visibility7     = "display:none";
            $visibility8     = "display:none";
            $visibility9     = "display:none";
            $visibility10    = "display:none";

            foreach ($countOutbound AS $outrow) {
                $boxClass = "";
                if ($outrow['Tip'] == 1 && $outrow['broj'] > 0){
                    $visibility1    = "display:block";
                    $count1         = $outrow['broj'];
                } else if($outrow['Tip'] == 2 && $outrow['broj'] > 0) {
                    $visibility2    = "display:block";
                    $count2         = $outrow['broj'];
                } else if($outrow['Tip'] == 3 && $outrow['broj'] > 0) {
                    $visibility3    = "display:block";
                    $count3         = $outrow['broj'];
                } else if($outrow['Tip'] == 5 && $outrow['broj'] > 0) {
                    $visibility4    = "display:block";
                    $count4         = $outrow['broj'];
                } else if($outrow['Tip'] == 6 && $outrow['broj'] > 0) {
                    $visibility5    = "display:block";
                    $count5         = $outrow['broj'];
                } else if($outrow['Tip'] == 7 && $outrow['broj'] > 0) {
                    $visibility6    = "display:block";
                    $count6         = $outrow['broj'];
                } else if($outrow['Tip'] == 8 && $outrow['broj'] > 0) {
                    $visibility7    = "display:block";
                    $count7         = $outrow['broj'];
                } else if($outrow['Tip'] == 9 && $outrow['broj'] > 0) {
                    $visibility8    = "display:block";
                    $count8         = $outrow['broj'];
                } else if($outrow['Tip'] == 10 && $outrow['broj'] > 0) {
                    $visibility9    = "display:block";
                    $count9         = $outrow['broj'];
                } else if($outrow['Tip'] == 11 && $outrow['broj'] > 0) {
                    $visibility10    = "display:block";
                    $count10         = $outrow['broj'];
                }
            }
            $_callInfoBox = '<div class="callInfoBox">
                            <table>
                                <tr class="callInfoItem" id="callinfo1" style="padding: 2px 10px;'.$visibility1.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FFF;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>AdCombo:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum1">'.$count1.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo2" style="padding: 2px 10px;'.$visibility2.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FC6;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>Cancel User:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum2">'.$count2.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo3" style="padding: 2px 10px;'.$visibility3.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #F33;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div class="blinkingText">Upsell:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum3">'.$count3.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo4" style="padding: 2px 10px;'.$visibility4.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FFF;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>Form fill brake:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum4">'.$count4.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo5" style="padding: 2px 10px;'.$visibility5.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FF0;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>Order fill brake:</div> </a></td>
                                <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum5">'.$count5.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo6" style="padding: 2px 10px;'.$visibility6.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FFF;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>Reorder call:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum6">'.$count6.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo7" style="padding: 2px 10px;'.$visibility7.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FFF;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>Bulk call:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum7">'.$count7.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo8" style="padding: 2px 10px;'.$visibility8.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FFF;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>Undecided call:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum8">'.$count8.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo9" style="padding: 2px 10px;'.$visibility9.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FFF;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>Mailreorder call:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum9">'.$count9.'</span></td>
                                </tr>
                                <tr class="callInfoItem" id="callinfo10" style="padding: 2px 10px;'.$visibility10.';">
                                    <td style="width:150px; padding:7px;border: 1px solid #333;border-left:5px solid #FFF;" ><a href="{{ URL }}outbound/'.$state.'" target="_blank" ><div>SMS Link:</div> </a></td>
                                    <td style="width:50px; text-align:center;border: 1px solid #333"><span id="callNum10">'.$count10.'</span></td>
                                </tr>
                        </table>
                        </div>';

            $predefinedPhone = $_GET['number'];
            $idKorisnika=$user['ouid'];

            $postarinaInfo  = $_omg->getPostagePrices($stateCode);
            $campaigns      = $_sms->getCampaigns($stateCode, "CampManagement.active DESC, CampManagement.id DESC");
            //$rcampaigns    = $_sms->getReorderCampaigns($stateCode);
            $descriptions   = $_omg->getAvailableDescriptions($stateCode);
            // $activeCamp     = $_omg->getActiveCampaigns($stateCode);
            //$countOutbound  = $_omg->countOutbound($state);
            // $allStates      = $_omg->getStates();
            $allStates       = $_omg->getStates();

            // $postarina  = '';
            $postar     = "";
            $valuta     = "";

            if (!empty($postarinaInfo)) {
                foreach ($postarinaInfo as $key => $pinfo) {
                    $key++;
                    if (strpos($pinfo['title'], 'POST') !== false){
                        $postarina = "<p style='text-align:left;' >{$key}. {$pinfo['title']} -{$pinfo['price']} {$pinfo['sbl']}</p>";
                        if ($pinfo['price'] > 0) {
                            if ($state == "BA"){
                                $postar = $pinfo['price'] + 1; // exception for BA +1 pakovanje
                            } else {
                                $postar = $pinfo['price'];
                            }

                        }
                        $valuta = $pinfo['sbl'];
                    }
                }
            } else {
                $postarina = "";
            }

            // opis proizvoda za operatere
            $descArr = Array();
            if (!empty($descriptions)) {
                foreach ($descriptions as $desc) {
                    //if ($desc['suma'] != 0) {
                    $descArr[$desc['id']] = $desc['pText'];
                    // }
                }
            }
            // $descJS = json_encode($descArr);
            $descJS = $descArr;

            $upsellArr = Array();
            // Upsell tekstovi za svaku kampanju
            if (!empty($campaigns)) {
                foreach ($campaigns as $campaign) {
                    $upsellArr[$campaign['CampaignName']] = $campaign['upsellText'];
                }
            }
            //$upsellJS = json_encode($upsellArr);
            $upsellJS = $upsellArr;

            $ouid = $_SESSION['phUser']['ouid'];
            $callTrack      = $_omg->startFlowTrack($state,$user["id"]);
            $proizvodi      = $_omg->getProductsOnPanel($stateCode);

            if (!empty($proizvodi)) {
                $proizvOption='';
                foreach ($proizvodi as $proizvod) {

                    if ($proizvod['suma'] != 0) {
                        $prSku = $proizvod['sku'];
                        $prTip = $proizvod['productType'];
                        $prId = $proizvod['id'];

                        $fullsku = $prSku."-".$prTip."-".sprintf("%04s", $prId);


                        $proizvOption .=' <option value="'. $prId .'" data-fullsku="'. $fullsku.'">'.$proizvod['title'].'</option>';

                    }
                }
            }
            $langHelp = new LanguagesHelperController();
            $content = $langHelp->getContent($state, $korisnik['Ime'], $korisnik['Prezime'], $postar);
            // print_r($stateCode);die();

            return $this->render('inbound/MULTI.html.twig', array(

                'content' => $content,
                'stil'  => $stil,
                'callTrack' => $callTrack,
                'predefinedPhone' => $predefinedPhone,
                'idKorisnika' => $idKorisnika,
                'postar' => $postar,
                'valuta' => $valuta,
                'ouid' => $ouid,
                'state' => $state,
                'valut' =>$valuta,
                'title' => $title,
                'korisnik' => $korisnik,
                'proizvodi' => $proizvodi,
                'proizvOption'=>$proizvOption,
                'descJS' =>$descJS,
                '_callInfoBox' => $_callInfoBox,
                'upsellJS' =>$upsellJS,
                'stateCode'=> $stateCode,
                'allStates'=>$allStates,
            ));
           
        } else {
            return $this->redirect('../login?status=2');
        }

       
    }
    
}