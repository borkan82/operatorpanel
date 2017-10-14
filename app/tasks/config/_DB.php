<?php
ini_set("display_errors",0);

/**
 * 
 * @desc
 * MySQL data base Driver
 * 
 * 
 * @version
 * 2.3 
 * 
 * 
 * @phpversion
 * 5
 * 
 * @package
 * DB driver
 * 
 */


class Database
{
	
	/**
	 * Array with connection params
	 *
	 * @var array
	 */
	private $_connectionArray = array();
	
	
	/**
	 * Errors buffer
	 *
	 * @var array
	 */
	private $_errors = array();
	
	
	/**
	 * Persistant
	 *
	 * @var boolean
	 */
	private $_persistent = FALSE;
	
	
	/**
	 * Connection 
	 *
	 * @var object
	 */
	public $_connect;
	
	
	/**
	 * Select DB 
	 *
	 * @var object
	 */
	private $_sldb;
	
	
	/**
	 * Query results
	 *
	 * @var object
	 */
	private $_queryResult;
	
	
	/**
	 * Counter
	 *
	 * @var integer
	 */
	private $_queryCounter=0;
	
	
	/**
	 * SQL code buffer
	 *
	 * @var string
	 */
	private $_query;
	
	
	/**
	 * Lista tabela
	 *
	 * @var array
	 */
	public $tables;
	
	
	/**
	 * @desc
	 * Database driver constructor
	 * 
	 * @return boolean
	 * @param $connectionParams array
	 * @param $autoConnect boolean[optional]
	 */
	public function __construct($connectionParams, $autoConnect=TRUE)
	{
		if( count($connectionParams) > 1)
		{
			$this->loadConnectionParams($connectionParams, 'SINGLE');
		}
		else
		{
			$this->loadConnectionParams($connectionParams, 'MULTI');
		}
		
		if( $autoConnect == TRUE ) { return $this->connect(); }
		else { return TRUE; }
	}
	
	
	
	
	
	
	/**
	 * @desc
	 * Submit connection paramters
	 * 
	 * @return boolean
	 * @param $connectionParams array
	 * @param $type string(STINGLE|MULTI)[optional]
	 */
	public function loadConnectionParams($connectionParams, $type='SINGLE')
	{
		switch($type)
		{
			case 'SINGLE':
				$this->_connectionArray[] = $connectionParams;
				break;
				
			case 'MULTI':
				$this->_connectionArray = $connectionParams;
				break;
				
			default:
				return NULL;
		}
		
		return TRUE;
	}
	
	
	
	
	/**
	 * Connection to db server
	 * 
	 * @return connection Object
	 */
	public function connect()
	{
		if( !$this->_connectionArray ) { $this->_error(1, 'MISSING CONNECTION ARRAY', ''); return FALSE; }

		foreach( $this->_connectionArray as $priority=>$connectionDetails )
		{
			if($this->_persistent == TRUE)
			//if(1==2)
			{
				$this->_connect = mysql_pconnect( $connectionDetails['address'].':'.$connectionDetails['port'], $connectionDetails['username'], $connectionDetails['password'] );
				
			}
			// simple connection
			else
			{
				$this->_connect = mysql_connect( $connectionDetails['address'].':'.$connectionDetails['port'], $connectionDetails['username'], $connectionDetails['password'] );
			}
			
			if( $this->_connect )
			{
				$this->selectDB( $connectionDetails['database'] );
				return $this->_connect;
			}
				
			else
			{
				$this->_error(2, "Can't connect to {$connectionDetails['address']}", 'PHP - MySQL error: '. mysql_errno() .' - '. mysql_error());
			}			
		}
		
		return FALSE;
	}
	
	
	/**
	 * Select db 
	 * 
	 * @return select Object
	 * @param $database string
	 */
	public function selectDB($database)
	{
		$this->_sldb = mysql_select_db($database, $this->_connect);
		if( $this->_sldb )
		{
			return $this->_sldb;
		}
		else
		{
			$this->_error(3, "Can't select db {$database}", 'PHP - MySQL error: '. mysql_errno() .' - '. mysql_error());
			return FALSE;
		}
	}
	
	
	
	/**
	 * Execute SQL query
	 *
	 * @param string $sql
	 * @param enum $type
	 * @param  $cache
	 * @return unknown
	 */
	public function query($sql, $type=1, $cache=false)
	{
		// version compatibility
		$__OLD2NEW = array( 1=>'OBJECT', 2=>'FETCH', 3=>'SMART_FETCH', 4=>'PAGING' );
		if( is_numeric($type) ) { $type = $__OLD2NEW[$type]; }
		
		if($type == 'PAGING')
		{
			$sql = trim($sql);
			$upper_sql = strtoupper($sql);
			$pos = strpos($upper_sql, 'SELECT');
			if($pos === 0)
			{
				$sql_head = 'SELECT';
				$sql_tail = substr($sql, 6);
				$sql = $sql_head . ' SQL_CALC_FOUND_ROWS ' . $sql_tail;
			}
			else 
			{
				$type = 'FETCH';
			}
		}
		
		
		// drop results
		$this->_queryResult = null;
		
		// execute query
		// execute query
		$startTime = microtime(true);
		$this->_query = mysql_query($sql, $this->_connect);
		$endTime = microtime(true);
		$queryTime = $endTime - $startTime;
		if ($queryTime > 10){

			if(ROOT){

				$logFile = fopen(ROOT ."/logs/dbslow/". date('d-m-Y') .".txt", "a");
				try{

					$text = $sql ."\n\n";
					$text .= date('H:i').' '. $_SERVER['REQUEST_URI'] . "\n";
					$text .= "time in sec: ".$queryTime."\n";
					$text .= "--------------------------------------------------------------------------------------------\n";
					fwrite($logFile, $text);
					fclose($logFile);
				}
				catch (Exception $e){

					if($logFile){

						fclose($logFile);
					}
				}
				if(file_exists(ROOT. "/logs/dbslow/". date('d-m-Y') .".txt")){

					chmod(ROOT. "/logs/dbslow/". date('d-m-Y') .".txt", 0777);
				}
			}
		}
		
		
		
		if( $this->_query )
		{
			/**
			 * update counter
			 */
			$this->_queryCounter++;
			
			switch ($type)
			{
				
				default:
				case 'OBJECT':
					$this->_queryResult = $this->_query;
					break;
					
				case 'FETCH':
					$this->_queryResult = $this->fetch('ARRAY');
					break;
					
				case 'SMART_FETCH':
					$this->_queryResult = $this->fetch('SMART');
					break;
					
				case 'PAGING':
					$ret['data'] = $this->fetch('ARRAY');
					$sql = "SELECT FOUND_ROWS()";
					$result = mysql_query($sql, $this->_connect);
					$ret['count'] = mysql_result($result, 0); 
					$this->_queryResult = $ret;
					break;
			}
		}
		else
		{
			$this->_error(4, 'MySQL query error', 'PHP - MySQL error: '. mysql_errno() .' - '. mysql_error());

			if(ROOT){

				$logFile = fopen(ROOT ."/logs/db/". date('d-m-Y') .".txt", "a");
				try{

					$text = $sql ."\n\n";
					$text .= date('H:i').' '. $_SERVER['REQUEST_URI'] .' PHP - MySQL error: '. mysql_errno() .' - '. mysql_error() . "\n";
					$text .= "--------------------------------------------------------------------------------------------\n";
					fwrite($logFile, $text);
					fclose($logFile);
				}
				catch (Exception $e){

					if($logFile){

						fclose($logFile);
					}
				}
				if(file_exists(ROOT. "/logs/db/". date('d-m-Y') .".txt")){

					chmod(ROOT. "/logs/db/". date('d-m-Y') .".txt", 0777);
				}
			}
		}
		
		return $this->_queryResult;
		
	}
	
	
	
	
	
	public function fetch($type='ARRAY')
	{
		switch ($type)
		{
			case 'ARRAY':
				$returnArray = array();
				while ($row = mysql_fetch_assoc( $this->_query )) 
				{
					$returnArray[] = $row;
				}
				
				break;
				
				
				
				
			case 'SMART':
				$returnArray = array();
				$numOfRows = mysql_num_rows( $this->_query );
				
				if($numOfRows>1)
				{
					while ($row = mysql_fetch_assoc( $this->_query )) 
					{
						$returnArray[] = $row;
					}
				}
				else
				{
					$returnArray =  mysql_fetch_assoc( $this->_query );
				}
				break;
		}
		
		return $returnArray;
	}
	
	
	
	/**
	 * Last insert id
	 *
	 * @return integer
	 */
	public function lastInsertID()
	{
		return mysql_insert_id($this->_connect);
	}
	
	
	
	/**
	 * Affected rows
	 *
	 * @return integer
	 */
	public function affectedRows()
	{
		return mysql_affected_rows($this->_connect);
	}
	
	
	 
	
	/**
	 * Disconect from server
	 *
	 * @return boolean
	 */
	public function disconnect()
	{
		return mysql_close($this->_connect);
	}
	
	
	
	/**
	 * Return errors
	 *
	 * @return array
	 */
	public function showErrors()
	{
		return $this->_errors;
	}
	
	
	
	/**
	 * Add error to error list
	 *
	 * @param integer $errorId
	 * @param string $errorMsg
	 * @param string $extraData
	 */
	private function _error($errorId, $errorMsg, $extraData=NULL)
	{
		$this->_errors[]=array('errorId'=>$errorId, 'errorMsg'=>$errorMsg, 'extraData'=>$extraData);
	}
	
	
}


?>