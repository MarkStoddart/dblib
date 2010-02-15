<?php

/**
 * Database interface, all classes must implement this
 * Provides function and variable definitions that must be used
 * 
 * @package dblib
 * @author Jamie Hurst
 * @version 1.1
 */

/**
 * Interface for database classes
 */
interface iDB {
	
	public function setupDB($host = null, $user = null, $pass = null, $db = null);
	public function connectDB($host = null, $user = null, $pass = null, $db = null);
	public function closeDB();
	public function getQueryCount();
	public function escape($str);
	
	public function getField($field, $table, $opt = '', $optValues = '');
	public function getRow($table, $opt = '', $optValues = '');
	public function getRows($table, $opt = '', $optValues = '');
	public function getNumRows($table, $opt = '', $optValues = '');
	
	public function getJoinedFields($fields, $table, $joins = array(), $opt = '', $optValues = '');
	public function getJoinedRow($table, $joins = array(), $opt = '', $optValues = '');
	public function getJoinedRows($table, $joins = array(), $opt = '', $optValues = '');
	public function getNumJoinedRows($table, $joins = array(), $opt = '', $optValues = '');
	
	public function insertRow($table, $data);
	public function updateRows($table, $data, $opt = '', $optValues = '');
	public function deleteRows($table, $opt = '', $optValues = '');
	
	public function insertID();
	
}