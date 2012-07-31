<?php
/**
 * This interface contains basic mandatory functions for database interaction.
 * @author Zakrevskiy Stanislav <sz@7net.ru>
 *
 */
interface Databaseable
{
	/**#@+
	 * @access public
	 * @return bool true on success
	 */
	
	/**
	 * Loads configuration for database connection from variable
	 */
	public function configLoad();
	
	/**
	 * Initializes connection with database
	 */

	public function connectionInit();
	
	/**
	 * Closes connection with database
	 */
	public function connectionClose();
	

	/**
	 * Executes query against database. In case of transaction stacks operation.
	 * @param string $query
	 */
	public function queryExec($query);
	
	
	
	/**
	 * Starts transaction. Or enters continious query mode for nonTransactional DB
	 */
	public function transactionStart();
	
	/**
	 * Commits transaction
	 * 
	 */
	public function transactionCommit();
	
	/**
	 * Rolls back transaction
	 */
	public function transactionRollback();

	/**
	 * Returns detailed inforamtion about last encountered error
	 * @deprecated
	 */
	public function errorExplain();
	
	/**
	 * Initialize instance of the class
	 * @param bool $autoconnect whether automatically connect do database or not
	 * @return bool true on success
	 */
	public function init($autoconnect = true);
	
	/**
	 * Executes query with whuch need to return array result
	 * @param string $query
	 * @return array result of query
	 */
	public function queryExecReturn($query);
}