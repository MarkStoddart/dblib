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
	
	private var $db;
	private var $host;
	private var $user;
	private var $pass;
	private var $name;
	private var $queryCount;
	
	private function errorDB($p_context, $p_query);
	public function setupDB($p_host, $p_user, $p_pass, $p_db);
	public function connectDB($p_host, $p_user, $p_pass, $p_db);
	public function closeDB();
	public function getQueryCount();
	
	public function preDB($p_var);
	public function postDB($p_var);
	
	public function getField($p_field, $p_table, $p_opt);
	public function getRow($p_table, $p_opt);
	public function getRows($p_table, $p_opt);
	public function getNumRows($p_table, $p_opt);
	
	public function insertRow($p_table, $p_data, $p_opt);
	public function updateRows($p_table, $p_data, $p_opt);
	public function deleteRows($p_table, $p_opt);
	
}