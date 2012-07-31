<?php
/**
 * Interface Ormable
 *
 * Defines the ORM interface.
 *
 * if there is a need to pass compound "id" field(s) in a field list or a filter (for SELECT queries),
 * "id" keys must be prefixed by # char, e. g. ('foo' => ..., 'bar' => ...) "id" maps to
 * '#foo' and '#bar' "field names"
 *
 * @author Zakrevskiy Stanislav <shido.spb@gmail.com>
 *
 * @version 0.1.2
 * @package framework.core
 */

interface Ormable {
	/**#@+
	 * @param string $className Name of class.
	 * @param array $id Associative array which contains PK of the record
	 * @access public
	 */

	/**
	 * Get object's data by id
	 * @return mixed Associative array of objects data in case of success operation, false otherwise.
	 */
	public function getByid($className, $id);

	/**
	 * insert new object into DB
	 *
	 * $id is NULL if primary key is an autoincrement field; set otherwise
	 *
	 * @param array $fields associative array (class_field_name => value)
	 * @return array id of inserted record
	 */
	public function createObject($className, $fields, $id = NULL);

	/**
	 * update existing record in DB
	 *
	 * @param array $fields associative array (class_field_name => value)
	 * @return bool true if updated successfully; false otherwise
	 */
	public function updateObject($className, $fields, $id);

	/**
	 * Delete object from DB
	 * @return bool True if suceeded, false otherwise.
	 */
	public function deleteByid($className, $id);
	/**#@-*/

	/**#@+
	 * @param string $className name of the class that will recieve data
	 * @param array $filter array which conatins set of rules for narrowing search
	 * @access public
	 * @static
	 */
	/**
	 * Get data for certain set of objects, determined by filter settings.
	 *
	 * @param array $Param
	 * @return mixed Returns indexed array of associative arrays if operation succeded, otherwise returns false
	 */
	public function getByFilter($className, $filter, $param = NULL);

	/**
	 * Counts number of objects that satisfy filter's conditions
	 * @return int Number of objects
	 */
	public function countByFilter($className, $filter);
	/**#@-*/

	/**
	 * begins transaction
	 * @return bool true if success, false otherwise
	 */
	public function beginTransaction();

	/**
	 * Commits all delayed actions against database
	 * @return bool True if succeded, false otherwise
	 */
	public function commit();

	/**
	 * Cancel all delayed actions associated with this instance.
	 * @return bool Always true. In case of fail generate exception.
	 */
	public function rollback();


}