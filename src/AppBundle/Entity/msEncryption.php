<?php

class msEncryption{

	private static $_instance = false;
	
	private $defaultKey = "xCFo9N4";
	private $defaultMethod;
	
	/**
	 * 
	 * @return Encryption
	 */
	public static function model(){
		
		if(self::$_instance == false){
			self::$_instance = new msEncryption();
		}
		
		return self::$_instance;
	}


	function __construct($conn){
		$this->defaultMethod = MCRYPT_RIJNDAEL_128;

		if ($conn) {
			$this->conn = $conn;
		}
	}
	
	/**
	 * 
	 * @param strint to be encoded $value
	 * @param key if not set default is set $key
	 * @param method if not set default is used $method
	 * @return encoded string
	 */	
	public function encrypt($value, $key = false, $method = false){
		if(!$key) $key = $this->defaultKey;
		if(!$method) $method = $this->defaultMethod;
		
		$iv_size = mcrypt_get_iv_size($method, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
						
		$ciphertext = mcrypt_encrypt($method, $key, $value, MCRYPT_MODE_ECB, $iv);
				
		
		$enValue = base64_encode($ciphertext);
	
		return $enValue;
	}
	
	public function decrypt($value, $key = false, $method = false){
		if(!$key) $key = $this->defaultKey;
		if(!$method) $method = $this->defaultMethod;
		
		$iv_size = mcrypt_get_iv_size($method, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		
		$value = base64_decode($value);
				
		$ciphertext = mcrypt_decrypt($method, $key, $value, MCRYPT_MODE_ECB, $iv);
						
		return $ciphertext;
	}


	/**
	 * SREDITI QUERY!!!!!!!!!!
	 *
	 *
	 */
	Public function checkUser($username,$pass){


		$enc_pass = md5($pass);	
		$kveri = "SELECT * FROM phone_order_users WHERE username='$username' and password='$enc_pass' LIMIT 1";
		return $this->db->query($kveri,3);
	}




}
