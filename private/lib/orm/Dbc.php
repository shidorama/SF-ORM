<?php
class DatabaseControl implements Databaseable {
	
	protected $connection;
	
	protected $config;
	
	/**
	 * Is transaction active or not. Possible to get this param dynamically from DB, but frequent calls will impede performance queries to DB.
	 * @var bool
	 */
	protected $transaction = false;
	
	static protected $instance;
	
	/**
	 * protected constructor, can't be called from outside.
	 * @return boolean always true
	 */
	protected function __construct() {
		return true;
	}
	
	public function configLoad() {
		$this->config = OrmConfig::getDbConfig();
	}
	
	
	public function connectionInit() {
		$this->connectionStatusCheck(false);
		$cf = &$this->config;
		$this->connection = mysqli_connect($cf['host'],$cf['user'],$cf['pass'],$cf['database'],$cf['port']);
		//echo mysqli_connect_errno();
		//echo mysqli_connect_error();
		$this->connectionStatusCheck();
		$query = "SET NAMES '".$cf['names']."'";
		$this->queryExec($query);
	}
	
	/**
	 * Checks status of connection.
	 * @param bool $expect which result are we expecting. Expecting true by default
	 * @throws Exception
	 * @return boolean Returns true if everything went as expected
	 */
	protected function connectionStatusCheck($expect = true) {
		if ($this->connection instanceof mysqli) {
			//TODO: replace with valid Logger exception and error code
			if (!$expect) 
				throw new Exception('',1);
			return true;
		} 
		if ($expect) 
			throw new Exception('', 2);
		return true;
	}
	
	public function connectionClose() {
		$this->connectionStatusCheck();
		$this->connection = mysqli_close($this->connection);
	}
	
	
	public function queryExec($query) {
		if(is_array($query)) {
			$this->transactionStart();
			foreach($query as $singleQuery) {
				$this->queryExec($query);
			}
			$this->transactionEnd();
		} else {
			$this->connectionStatusCheck();
			$result = mysqli_query($this->connection,$query);
			//echo mysqli_error($this->connection);
			$this->errorCheck($result);
			return $result;
		}
	}
	
	public function dataPrepare($data) {
		$this->connectionStatusCheck();
		if (is_array($data)) {
			foreach($data as &$val) {
				//TODO: consider changing to dumb but more quick method with direct call to escape string
				$val = $this->dataPrepare($val);
			}
			return $data;
		} else {
			$data = mysqli_real_escape_string($this->connection,$data);
			return $data;
		}
	}
	
	public function transactionStart() {
		$this->connectionStatusCheck();
		if(!$this->transaction)
			return false;
		$this->transaction = true;
		$result = mysqli_autocommit($this->connection,false);
		$this->errorCheck($result);
		return true;
	}
	
	
	public function transactionCommit() {
		$this->connectionStatusCheck();
		if(!$this->transaction)
			return false;
		$result = mysqli_commit($this->connection);
		$this->errorCheck($result);
		$this->transactionEnd();
		return true;
	}
	
	
	public function transactionRollback() {
		$this->connectionStatusCheck();
		if(!$this->transaction)
			return false;
		$result = mysqli_rollback($this->connection);
		$this->errorCheck($result);
		$this->transactionEnd();
		return true;
	}
	
	/**
	 * Finalizes transaction by enabling autocommit
	 * @return boolean
	 */
	protected function transactionEnd() {
		$result = mysqli_autocommit($this->connection,true);
		if(!$this->transaction)
			return false;
		$this->errorCheck($result);
		$this->transaction = false;
		return true;
	}

	
	public function errorExplain() {
		//TODO: consider removing this function
		;
	}
	
	
	public function queryExecReturn($query) {
		$data = array();
		$result = $this->queryExec($query);
		while($arr = mysqli_fetch_assoc($result)) {
				$data[] = $arr;
		}
		return $data;
	}
	
	
	public function init($autoconnect = true) {
		if(!isset(self::$instance)) {
			$class = __CLASS__;
			self::$instance = new $class;
			self::$instance->configLoad();
			if ($autoconnect)
				self::$instance->connectionInit();
		}
		return self::$instance;
	}
	

	/**
	 * Method which checks for errors after each query
	 * @param mysqli $result result of query
	 * @throws Exception
	 * @return boolean true on success
	 */
	protected function errorCheck($result) {
		//questionable
		//$this->connectionStatusCheck();
		if($result) {
			return true; //no error, so no reason for us to proceed
		}
		$err = mysqli_error($this->connection);
		$errno = mysqli_errno($this->connection);
		if($this->transaction) {
			if($this->transactionRollback())
			//TODO: Logger.....
				 throw new Exception($err, $errno);
		}
	}
}