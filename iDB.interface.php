<?php

/**
 * Database interface, all classes must implement this
 * Provides function and variable definitions that must be used
 * 
 * @author Jamie Hurst
 * @version 1.0
 */

/**
 * Interface for database classes
 */
interface iDB {
	
	//protected function errorDB($p_error, $p_db_error, $p_query);
	public function setupDB($p_host = null, $p_user = null, $p_pass = null, $p_db = null);
	public function connectDB($p_host = null, $p_user = null, $p_pass = null, $p_db = null);
	public function closeDB();
	public function getQueryCount();
	
	public function preDB($p_var);
	public function postDB($p_var);
	
	public function getField($p_field, $p_table, $p_opt = '', $p_opt_values = '');
	public function getRow($p_table, $p_opt = '', $p_opt_values = '');
	public function getRows($p_table, $p_opt = '', $p_opt_values = '');
	public function getNumRows($p_table, $p_opt = '', $p_opt_values = '');
	
	public function insertRow($p_table, $p_data, $p_opt = '', $p_opt_values = '');
	public function updateRows($p_table, $p_data, $p_opt = '', $p_opt_values = '');
	public function deleteRows($p_table, $p_opt = '', $p_opt_values = '');
	
}