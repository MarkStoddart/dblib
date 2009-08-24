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
	
	public const DB_HOST;
	public const DB_USER;
	public const DB_PASS;
	public const DB_NAME;
	public const DB_FILE;
	
	public var $DBmode;
	public var $DBobject;
	
	public function preDB($p_var);
	public function postDB($p_var);
	
	public function errorDB($p_context, $p_query);
	public function connectDB();
	public function closeDB();
	
	public function getField($p_field, $p_table, $p_opt);
	public function getRow($p_table, $p_opt);
	public function getRows($p_table, $p_opt);
	public function getNumRows($p_table, $p_opt);
	
	public function insertRow($p_table, $p_data, $p_opt);
	public function updateRows($p_table, $p_data, $p_opt);
	public function deleteRows($p_table, $p_opt);
	
}