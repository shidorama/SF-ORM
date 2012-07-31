<?php

/**
 * New ORM (nORM) class
 * 
 * @author Stanislav Zakrevskiy
 *
 */

/**
 * TODO: for simplicity fo the custom select queries AS must be used: SELECT t1.asd AS asd, t2.dds AS dds FROM t1 JOIN t2 ON (t1.id = t2.id); and columns in where clause must be duplicated in fieldset with corresponding AS 
 * TODO: any unexpected behavior (invalid index, invalid value for filter or field) must end in detailed exception
 * 
 * 
 */
/*
 * ORM feature list
 * * Single table auto queries generation
 * *
 * 
 */
class ORM implements Ormable
{
	static private $instance;
	protected $dbc;
	protected $config;
	
	protected $query = array();
	
	/**
	 * Private function construct to exclude cases of direct class instatiation
	 */
	protected function __construct() {
		/**
		 * generic select syntax
		 * SELECT <fieldset> FROM <table> WHERE <id filter>|<generic filter> [ORDER BY <fieldname> DESC|ASC]
		 */
		$this->config = OrmConfig::getClassesConfig();
	}
	
	protected function loadConf() {
		;
	}
	
	static public function init() {
		if(!self::$instance) {
			$class = __CLASS__;
			self::$instance = new $class;
			self::$instance->dbc = DatabaseControl::init();
		}
		return self::$instance;
		
	}
	
	public function getById($module, $id) {
		//print_r($id);
		$this->moduleCheckExsitance($module);
		$tailParams = $this->byIdCommonCalls($module, $id);
		$tailParams = $this->filterParamsProcess($module, $tailParams['filter'], $tailParams['params']);
		$query = $this->querySelectConstruct($module,$tailParams['filter'], $tailParams['params']);
		$array = $this->dbc->queryExecReturn($query);
		$array = $this->resultIdReparse($module,$array);
		return $array[0];
		
	}
	public function getByFilter($module, $filter, $param = NULL) {
		$this->moduleCheckExsitance($module);
		$filter = $this->filterConstruct($module, $filter);
		$param = $this->additionalParametersQueryConstruct($module, $param);
		$query = $this->querySelectConstruct($module,$filter,$param);
		$array = $this->dbc->queryExecReturn($query);
		$array = $this->resultIdReparse($module,$array);
		return $array;
	}
	
	public function createObject($module, $fields, $id = NULL) {
		$this->moduleCheckExsitance($module);
		if(is_array($id))
			$fields = array_merge($fields,$id);
		$fields = $this->fieldsetWriteDataBuild($module, $fields);
		$table = $this->config[$module]['table'];
		$query = 'INSERT INTO `'.$table.'` ('.$fields['fields'].') VALUES ('.$fields['data'].')';
		$result = $this->dbc->queryExec($query);
		if(is_null($id))
			return true;
		$id = $this->dbc->queryExecReturn('SELECT LAST_INSERT_ID() AS id');
		return $id;
		
	}

	public function updateObject($module, $fields, $id) {
		$this->moduleCheckExsitance($module);
		if(is_array($id))
			$fields = array_merge($fields,$id);
		$tailParams = $this->byIdCommonCalls($module, $id);
		$tailParams = $this->filterParamsProcess($module, $tailParams['filter'], $tailParams['params']);
		$fields = $this->fieldsetUpdateDataBuild($module, $fields);
		$table = $this->config[$module]['table'];
		$query = 'UPDATE `'.$table.'` SET '.$fields['fields'].' '.$tailParams['filter'].$tailParams['params'];
		$result = $this->dbc->queryExec($query);
		return true;
	}

	public function deleteById($module, $id) {
		$this->moduleCheckExsitance($module);
		$tailParams = $this->byIdCommonCalls($module, $id);
		$tailParams = $this->filterParamsProcess($module, $tailParams['filter'], $tailParams['params']);
		echo $query = 'DELETE FROM `'.$this->config[$module]['table'].'` '.$tailParams['filter'].$tailParams['params'];
		$result = $this->dbc->queryExec($query);
		return true;
	}

	public function countByFilter($module, $filter) {
		$this->moduleCheckExsitance($module);
		$filter = $this->filterConstruct($module, $filter);
		$query = $this->querySelectConstruct($module,$filter,'',true);
		$array = $this->dbc->queryExecReturn($query);
		return $array[0]['numRows'];
	}

	public function beginTransaction() {
		$this->dbc->transactionStart();
		return true;
	}


	public function commit() {
		$this->dbc->transactionCommit();
		return true;
	}

	public function rollback() {
		$this->dbc->transactionRollback();
		return true;
	}
	
	public function transactionStatusGet(){
		return $this->dbc->transaction;
	}
	
	//private function fieldSetConstructor($module, $id, $data = NULL) {
	//	
	//}
	/**#@+
	 * @access protected
	 * @throws something
	 * 
	 */

	
	/**
	 * This method builds fieldset and dataset for any write operation (UPDATE/INSERT)
	 * @param string $module name of the module
	 * @param array $data 
	 * @return string
	 */
	protected function fieldsetWriteDataBuild($module, $data) {
		$data = $this->dbc->dataPrepare($data);
		$this->moduleCheckExsitance($module);
/* 		foreach($this->config as $table => $tableConfig)
		{
			if(isset($data)){
				print_r($tableConfig);
				foreach ($data as $akey => $val) {
					if (key_exists($akey, $this->config[$module]['fields'])) {
						$fieldset[]= '`'.$table.'`.`'.$akey.'`';
						$dataset[]= '\''.$val.'\'';
					}
				}	
			}
		} */
		$table = $this->config[$module]['table'];
		if(isset($data)){
			foreach ($data as $akey => $val) {
				if(!isset($val) || $val == '' || $val == NULL)
					continue;
				if (key_exists($akey, $this->config[$module]['fields'])) {
					$fieldset[]= '`'.$table.'`.`'.$akey.'`';
					$dataset[]= '\''.$val.'\'';
				}
			}
		}
		$fields = implode(', ', $fieldset);
		$data = implode(', ',$dataset);
		$readyData['fields'] = $fields;
		$readyData['data'] = $data;
		return $readyData;
	}
	
	protected function fieldsetUpdateDataBuild($module, $data) {
		$data = $this->dbc->dataPrepare($data);
		$this->moduleCheckExsitance($module);
		$table = $this->config[$module]['table'];
		if(isset($data)){
			foreach ($data as $akey => $val) {
				if (key_exists($akey, $this->config[$module]['fields'])) {
					$fieldset[]= '`'.$table.'`.`'.$akey.'` = '.'\''.$val.'\'';
					//$dataset[]= '\''.$val.'\'';
				}
			}
		}
		$fields = implode(', ', $fieldset);
		//$data = implode(', ',$dataset);
		$readyData['fields'] = $fields;
		//$readyData['data'] = $data;
		return $readyData;
	}
	
	/**
	 * checks existance of module. Throws exception on failure.
	 * @param string $module name of module
	 * @throws Exception
	 * @return boolean true on success
	 */
	protected function moduleCheckExsitance($module) {
		if (!key_exists($module, $this->config))
			throw new Exception('Nonexistent module requested', -98);
		return true;
	}
	
	/**
	 *  @param unknown_type $module
	 * @return boolean
	 * @deprecated
	 */
	protected function overrideCheck($module) {
		if(!key_exists($module, $this->config))
			return true;
		return false;
	}
	
	/**
	 * generic select query constructor. Automatically constructs query from given components, executes it and returns ready for use query.
	 * @param string $module Module name
	 * @param string $filter Ready filter (WHERE) part of the query
	 * @param string $params additional parts of the query (GROUP, ORDER, LIMIT)
	 * @param string $count Do we need array of results or just number of rows
	 * @return string
	 */
	protected function querySelectConstruct($module,$filter,$params,$count=false) {
		$fields = ' COUNT(*) AS \'numRows\'';
		if ($count == false)
			$fields = $this->fieldsetSelectConstructor($module);
		$table = $this->config[$module]['table'];
		$query = 'SELECT '.$fields.' FROM `'.$table.'` '.$filter.$params;
		return $query;
	}
	
	/*
	 * 'eq': field = value
	 * 'ne': field <> value
	 * 'gt': field > value
	 * 'le': field <= value
	 * 'ge': field >= value
	 * 'lt': field < value
	 * 'in': field in value list
	 */
	/**
	 * Parses filter array and replaces internal compare operators with SQL ones
	 * @param string $module Module for which filter are designated
	 * @param array $filter Filter array that will be altered
	 * @throws Exception
	 * @return array
	 */
	protected function filterConditionsParse($module, $filter) {
		if(is_array($filter))
		foreach($filter as &$filterPart) {
			if(!isset($filterPart['operator'])) {
				throw new Exception("Filter operator is not set!", ord('S'));
				unset ($filterPart);
				continue;
			}
			switch($filterPart['operator']){
				case 'lt';
					$filterPart['operator'] = '<';
					break;
				case 'le';
					$filterPart['operator'] = '<=';
					break;
				case 'eq';
					$filterPart['operator'] = '=';
					break;
				case 'gt';
					$filterPart['operator'] = '>';
					break;
				case 'ge';
					$filterPart['operator'] = '>=';
					break;
				case 'ne';
					$filterPart['operator'] = '<>';
					break;
				case 'lk';
					$filterPart['operator'] = 'LIKE';
					break;
				default;
					throw new Exception('Filter operator malformed', ord('M'));
					//unset ($filterPart);
					continue;
			}
			$filterPart['value'] = $this->dbc->dataPrepare($filterPart['value']);
			if(!$this->fieldInModule($module, $filterPart['name']))
				unset ($filterPart);
		}
		return $filter;
	}
		
	/**
	 * Constructs valid SQL WHERE statement from $filter array for specific $module
	 * @see nORM::filterConditionsParse
	 * @param string $module
	 * @param array $filter Filter array
	 * @return string|NULL returns filter string or NULL if filter array contains 0 elements
	 */
	protected function filterConstruct($module,$filter) {
		$filter = $this->filterConditionsParse($module, $filter);
		//print_r($filter);
		foreach ($filter AS &$filterPart) {
			$filterPart = '`'.$filterPart['name'].'` '.$filterPart['operator']." '".$filterPart['value']."' ";
		}
		if (sizeof($filter)>0)
			return " WHERE ".implode(" AND ", $filter);
		return NULL;
	}
	
	/**
	 * Checks whether specific $module contains $field. Throws error on failure.
	 * @param string $module
	 * @param string $field
	 * @throws Exception
	 * @return boolean returns true on success
	 */
	protected function fieldInModule($module, $field) {
		if (key_exists($field, $this->config[$module]['fields']))
			return true;
		//return false;
		throw new Exception('Nonexistent field in filter, select or update clause', ord('F'));
	}
	
	/**
	 * Method parses $params array and converts it into valid SQL LIMI, ORDER OR GROUP clauses
	 * @param string $module
	 * @param array $params
	 * @return string
	 */
	protected function additionalParametersQueryConstruct($module,$params) {
		$addParams = array();
		if(isset($params['order'])) {
			$fs = $this->orderGroupStatementConstruct($module, $params);
			if(strlen($fs) > 1)
				$addParams['order'] = ' ORDER BY '.$fs;
		}
		if(isset($params['group'])) {
			$fs = $this->orderGroupStatementConstruct($module, $params);
			if(strlen($fs) > 1)
				$addParams['group'] = ' GROUP BY '.$fs;
		}
		if(isset($params['pagination'])) {
			$finish = $params['pagination']['numOfRows'] * $params['pagination']['page'];
			$start = $finish - $params['pagination']['numOfRows'];
			$addParams['limit'] = ' LIMIT '.$start.','.$finish;
		}
		return implode('',$addParams);
	}
	
	/**
	 * Part of common operations for constructing GROUP/ORDER clause. Check if the action is valid and if the designated field is present in the module then performs strtoupper() on it.
	 * @param string $module
	 * @param array $params
	 * @throws Exception
	 * @return string
	 */
	protected function orderGroupStatementConstruct($module,$params) {
		$actions = array('ASC', 'DESC');
		foreach($params['order'] as $field => $order) {
			$order = strtoupper($order);
			if ($order == 'RAND') {
				$tmporder[] = 'RAND()';
				continue;
			}
			$this->fieldInModule($module, $field);
			if (!in_array($order, $actions))
				throw new Exception('Malformed order/group directive!', ord('d'));
			$tmporder[] = '`'.$field.'` '.$order;
		};
		return implode(', ', $tmporder);
	}
	
	/**
	 * Constructs default filter for *ById operations
	 * @param string $module
	 * @param array $id
	 * @return array
	 */
	protected function filterIdConstruct($module, $id) {
		foreach($id as $idPartKey => $idPart) {
			$filter[] = array("name" => $idPartKey, "operator" => "eq", "value" => $idPart);
		}
		return $filter;
	}
	
	/**
	 * Performs first stage of result array preparation: collects all id and puts them into another array
	 * @param string $module
	 * @param array $array
	 * @return array returns prepared array
	 */
	protected function resultIdReparse($module, $array) {
		foreach ($array as &$arrayPart) {
			foreach ($arrayPart as $resultPartKey => $resultPart) {
				if (isset($this->config[$module]['fields'][$resultPartKey]) && $this->config[$module]['fields'][$resultPartKey] == 'id') {
					$arrayPart['id'][$resultPartKey] = $resultPart;
					unset($arrayPart[$resultPartKey]);
				}
			}
		}
		//$array = $array['id']+$array[0];
		return $array;
	}
	
	/**
	 * Constructs part of SELECT's clause (fieldset)
	 * @param array $module
	 * @return string
	 */
	protected function fieldsetSelectConstructor($module) {
		foreach ($this->config[$module]['fields'] as $akey => $adata) {
			$fieldset[] = '`'.$akey.'`';
		}
		$fieldset = implode(',', $fieldset);
		return $fieldset;
	}
	
	/**
	 * Some common calls that meant for all 'ById' functions. Checks existence of module, constructs filter array and additional params array.
	 * @param string $module
	 * @param array $id
	 * @return array
	 */
	protected function byIdCommonCalls($module,$id) {
		$this->moduleCheckExsitance($module);
		$tailConstruction['filter'] = $this->filterIdConstruct($module,$id);
		//$tailConstruction['params'] = array("pagination" => array("numOfRows" => 1, "page" => 1));
		$tailConstruction['params'] = NULL;
		return $tailConstruction;
	}
	
	/**
	 * processes trailing clauses of query (WHERE/ORDER/GROUP/LIMIT) and returns valid clauses
	 * @param string $module
	 * @param array $filter
	 * @param array $params
	 * @return string
	 */
	protected function filterParamsProcess($module,$filter,$params) {
		$tailConstruction['filter'] = $this->filterConstruct($module, $filter);
		$tailConstruction['params'] = $this->additionalParametersQueryConstruct($module, $params);
		return $tailConstruction;
	}
	
	/**
	 * @deprecated
	 */
	protected function outputPrepare($model,$array) {
		
	}
	
	/**
	 * Remaps array keys to comply with expected output
	 * @param string $module Module name
	 * @param array $array Result array for remapping
	 * @return array remapped array for model
	 */
	protected function outputArrayKeyRemap($module,$array) {
		//TODO: implemt this and mapping configuration
		return array();
	}
	
	public function getObjectFieldsStructure($module) {
		$this->moduleCheckExsitance($module);
		return $this->config[$module]['fields'];
	}
	
}