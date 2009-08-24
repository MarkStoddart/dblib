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
public interface iDB {
	
	private var $DBobject;
	private var $DBhost;
	private var $DBuser;
	private var $DBpass;
	private var $DBname;
	private var $queryCount;
	
	public function preDB($p_var);
	public function postDB($p_var);
	
	private function errorDB($p_context, $p_query);
	public function connectDB();
	public function closeDB();
	public function setupDB();
	
	public function getField($p_field, $p_table, $p_opt);
	public function getRow($p_table, $p_opt);
	public function getRows($p_table, $p_opt);
	public function getNumRows($p_table, $p_opt);
	
	public function insertRow($p_table, $p_data, $p_opt);
	public function updateRows($p_table, $p_data, $p_opt);
	public function deleteRows($p_table, $p_opt);
	
}