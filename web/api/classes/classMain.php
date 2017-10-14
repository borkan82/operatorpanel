<?php
/**********************************************************************
 *																	  *
 * --------- Main klasa za pozive sa homepagea ----------------       *
 * 																	  *
 * 	@Author Boris  													  *
 *  09/2015															  *
 **********************************************************************/
class Main {
/**********************************************************************
 * ------------------------ Priprema klase --------------------       *
 **********************************************************************/

	public function __construct($db) {
        if ($db) {
            $this->db = $db;
        }
    }

/**********************************************************************
 * --------- Preuzimanje podataka za chart --------------------       *
 **********************************************************************/

	public function getChartData() {
		$sql = "SELECT count(*) FROM phone_order_calls
				WHERE 1 GROUP BY state";
		$results=$this->db->query($sql,2);
        return $results;
	}

/**********************************************************************
 * --- Brojanje ordera "Ordered" "Canceled" "Other" "Total" ---       *
 **********************************************************************/

	public function countOrders($kveri){
		$sql = "SELECT count(*) AS broj FROM phone_order_calls
				WHERE 1 $kveri";

		$results=$this->db->query($sql,3);
        return $results;
	}

/**********************************************************************
 * --- Brojanje ordera "Ordered" "Canceled" "Other" "Total"  za outbound---       *
 **********************************************************************/

	public function countOrdersOubound($kveri){
		$sql = "SELECT count(*) AS broj FROM phone_order_outbound
				WHERE 1 $kveri";
		//var_dump($sql);
		$results=$this->db->query($sql,3);
		return $results;
	}

/**********************************************************************
 * --- Provjera da li postoji entry ---       *
 **********************************************************************/

	public function checkIfExist($table,$kveri){
		$sql = "SELECT * FROM {$table}
				WHERE 1 $kveri";

		$results=$this->db->query($sql,2);
        return $results;
	}
/**********************************************************************
 * --- Nadji broj telefona---       *
 **********************************************************************/

	public function getPhoneByMail($mail){
		$sql = "SELECT namesurname, phone, state FROM customers
				WHERE 1 AND email = '$mail' GROUP BY phone ORDER BY id DESC";

		$results=$this->db->query($sql,2);
		return $results;
	}
/**********************************************************************
 * --- Nadji Email ---       *
 **********************************************************************/

	public function getMailByPhone($phone,$state){
		$sql = "SELECT namesurname, email FROM customers
				WHERE 1 AND state = '$state' AND phone LIKE '%$phone%' ORDER BY id DESC";
		$results=$this->db->query($sql,2);
		return $results;
	}
/**********************************************************************
 * --- Nadji telefon po racunu ---       *
 **********************************************************************/
	public function getPhoneByInvoice($invoice, $state){
		$sql = "SELECT customers.phone AS phoneNumber FROM documents
				LEFT JOIN customers ON documents.customer = customers.id
				WHERE 1
				AND documents.doc_number LIKE '%$invoice%'
				AND documents.state = '$state'
				AND documents.doc_type = '1'
				LIMIT 1";

		$results=$this->db->query($sql,3);
		return $results;
	}
/**********************************************************************
 * --- Posalji na mailstorm unsubscribe ---       *
 **********************************************************************/

	public function sendToMailStorm($email){
		$dan= Date("Y-m-d");
		//var_dump($email);exit;
		$fields_string = "";
		$url = 'http://mail-storm.net/apix9j0b/post';
		$fields = array(
				'username' => urlencode("PhoneOrder"),
				'password' => urlencode("P&U?C?iL%_BbD5Gy+M~oPNU;-_i!5s"),
				'action' => urlencode("unsubscribePhoneOrder"),
				'email' => urlencode($email)
		);

//url-ify the data for the POST
		foreach($fields as $key=>$value) {
			$fields_string .= $key.'='.$value.'&';
		}
		rtrim($fields_string, '&');

//open connection
		$ch = curl_init();

//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

//execute post
		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
//close connection
		curl_close($ch);

		$podaci = json_decode($info[4]);
		$file = fopen("/var/www/sites/phone-sale.net/htdocs/clp456/api/responselog/".$dan.".txt", "a");
		file_put_contents("/var/www/sites/phone-sale.net/htdocs/clp456/api/responselog/".$dan.".txt", $info, FILE_APPEND);
		fclose($file);
	}
/**********************************************************************
 * --- Brojanje danasnjih ordera po drzavama ------------------       *
 **********************************************************************/

    public function dailyTableData($datum){
        $sql = "SELECT states.title_eng AS stateTitle,
					SUM(IF(success = 'ORDERED!', 1,0)) AS `sumOrder`,
					SUM(IF(success = 'CANCELED!', 1,0)) AS `sumCancel`,
					SUM(IF(success = 'NO ORDER!', 1,0)) AS `sumOther`,
					COUNT(*) AS sumTotal,
					MAX(date) AS noviDatum
					FROM `phone_order_calls` 
					INNER JOIN states ON phone_order_calls.state = states.code2
					WHERE phone_order_calls.date LIKE '%$datum%' GROUP BY phone_order_calls.state";

        $results=$this->db->query($sql,2);

        return $results;
    }

/**********************************************************************
 * --- Brojanje ordera "Ordered" "Canceled" "Other" "Total" ---       *
 **********************************************************************/

	public function countOutbounds(){
		$sql = "SELECT count(*) AS broj, state,
					SUM(IF(type = '1', 1,0)) AS `sumACall`,
					SUM(IF(type = '5', 1,0)) AS `sumABrake`,
					SUM(IF(type = '3', 1,0)) AS `sumUpsell`,
					SUM(IF(type = '2', 1,0)) AS `sumCancel`,
					SUM(IF(type = '6', 1,0)) AS `sumOBrake`,
					SUM(IF(type = '7', 1,0)) AS `sumReorder`,
					SUM(IF(type = '8', 1,0)) AS `sumBulk`,
					SUM(IF(type = '9', 1,0)) AS `sumUndec`,
					SUM(IF(type = '10', 1,0)) AS `sumRemail`,
					SUM(IF(type = '11', 1,0)) AS `sumSLink`,
					MAX(submitDate) AS noviDatum
				FROM phone_order_outbound
				WHERE 1
				AND Date(called_time) = Date(NOW())
				GROUP BY state";
		//print_r($sql);die();

		$results=$this->db->query($sql,2);
		return $results;
	}

/**********************************************************************
 * --- Postavka novog parametra unutar sesije -----------------       *
 **********************************************************************/
	public function setSession($sessionName){
		session_start();
		$_SESSION['phUser'][$sessionName] = 0;
	}
  
}
?>