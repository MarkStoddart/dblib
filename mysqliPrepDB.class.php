<?php

/**
 * Class extending original MySQLi DB class to make use of prepared queries
 * 
 * @author Jamie Hurst
 * @version 1.0
 */

require 'iDB.interface.php';
require 'mysqliDB.class.php';

/**
 * MySQLi class
 */
class mysqliPrepDB extends mysqliDB implements iDB {
	
	/**
	 * Constructor
	 * Use the provided DB variable if set, otherwise don't connect just yet
	 * 
	 * @param	mixed	$p_db	[Optional] MySQL connection link
	 */
	public function __construct($p_db = false) {
		parent::__construct($p_db);
	}
	
	/**
	 * Destructor
	 * Close the database
	 */
	public function __destruct() {
		parent::closeDB();
	}
	
	/**
	 * Bind parameters and get the affected rows back into an array of values
	 * 
	 * @param	object	$p_object	MySQLi statement object returned from prepared query
	 * @param	array	$p_params	[Optional] An array of values to replace parameters in the query with
	 * @return	mixed	Array of results on success, false on failure
	 */
	public static function getResult($p_object, $p_params = array()) {
		
		// Check the parameters are the same
		if($p_object->param_count != count($p_params))
			return false;
		else {
			// Bind each parameter
			$bind_string = '';
			foreach($p_params as $param_id => $param) {
				if(is_float($param))
					$bind_string .= 'd';
				elseif(is_int($param))
					$bind_string .= 'i';
				else
					$bind_string .= 's';
			}
			// Esacpe the parameters using the preDB function
			$p_params = parent::preDB($p_params);
			$params = array_merge(array($bind_string), $p_params);
			call_user_func_array(array($p_object, 'bind_param'), $params);
			
			// Execute the query
			$p_object->execute();
			$this->queryCount++;
			$p_object->store_result();
			
			// Get the metadata and the fields to use for the result
			$result = $p_object->result_metadata();
			$fields = $result->fetch_fields();
			
			// Set up the fields in an array
			$row = array();
			foreach($fields as $field)
				$row[$field] = false;
			// Bind the results
			call_user_func_array(array($p_object, 'bind_param'), $row);
			
			// Get the number of rows affected
			$rows = $p_object->affected_rows;
			
			$result = array();
			for($i = 0; $i < $rows; $i++) {
				$p_object->fetch();
				$rows[] = $row;
			}
			
			// Free the result
			$p_object->free_result();
			
			// Return single array of results or otherwise
			if($count($rows) == 1)
				return parent::postDB($rows[0]);
			else
				return parent::postDB($rows);
		}
	}
	
	/**
	 * Get a single field from a database using a table and a where query
	 *
	 * @param	string	$p_field		Field to fetch
	 * @param	string	$p_table		Table to get field from
	 * @param	string	$p_opt			[Optional] Any options, such as WHERE clauses
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	mixed	Result
	 */
	public function getField($p_field, $p_table, $p_opt = '', $p_opt_values = array()) {

		// Prepare values for database checking
		$p_field = $this->preDB($p_field);
		$p_table = $this->preDB($p_table);
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
		
		// Build the query
		$query = "
			SELECT `{$p_field}`
			FROM `{$p_table}`
			{$p_opt}
			LIMIT 1
		";
		
		// Run the query and report all errors
		$result = $this->db->prepare($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_field', $this->db->error, $query);
		
		// Return the query object
		return $result;
	}
	
	/**
	 * Get a single row from a database
	 *
	 * @param	string	$p_table		Table to get row from
	 * @param	string	$p_opt			[Optional] Any options, such as WHERE clauses
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	mixed	Result
	 */
	public function getRow($p_table, $p_opt = '', $p_opt_values = array()) {
		
		// Prepare values for database
		$p_table = $this->preDB($p_table);
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
		
		// Build the query
		$query = "
			SELECT *
			FROM `{$p_table}`
			{$p_opt}
			LIMIT 1
		";
		
		// Get the result and report any errors
		$result = $this->db->prepare($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_row', $this->db->error, $query);
		
		// Return the query object
		return $result;
	}
	
	/**
	 * Get multiple rows from a database, fetch them in an array
	 *
	 * @param	string	$p_table		Table to get data from
	 * @param	string	$p_opt			[Optional] Any MySQL commands to pass, such as WHERE
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	mixed	Result
	 */
	public function getRows($p_table, $p_opt = '', $p_opt_values = array()) {
		
		// Prepare values for database
		$p_table = $this->preDB($p_table);
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
		
		// Build the query
		$query = "
			SELECT *
			FROM `{$p_table}`
			{$p_opt}
		";
		
		// Get the result, report any errors
		$result = $this->db->prepare($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_rows', $this->db->error, $query);
		
		// Return the query object
		return $result;
	}
	
	/**
	 * Get one or more fields from a database using a table and a where query,
	 * also make use of MySQL joins in query
	 *
	 * @param	string	$p_fields		Fields to fetch
	 * @param	string	$p_tables		Tables to get fields from
	 * @param	array	$p_joins		[Optional] An array that contains an array of items with the following options:
	 *									string type Type of join, e.g. left
	 *									string table Table to join with optional alias
	 *									string local Local key to join on
	 *									string foreign Foreign key to join on
	 * @param	string	$p_opt			[Optional] Any options, such as WHERE clauses
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	mixed	Result
	 */
	public function getJoinedFields($p_fields, $p_tables, $p_joins = array(), $p_opt = '', $p_opt_values = array()) {

		// Prepare values for database checking
		$p_fields = parent::buildSelectString($this->preDB($p_fields));
		$p_tables = parent::buildFromString($this->preDB($p_tables));
		$p_joins = parent::buildJoinString($this->preDB($p_joins));
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
		
		// Build the query
		$query = "
			SELECT {$p_fields}
			FROM {$p_tables}
			{$p_joins}
			{$p_opt}
			LIMIT 1
		";
				
		// Run the query and report all errors
		$result = $this->db->prepare($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_joined_fields', $this->db->error, $query);
		
		// Return the resulting field
		$row = @$result->fetch_row();
		if(!is_array($p_fields))
			return $this->postDB($row[0]);
		else
			return $this->postDB($row);
	}
	
	/**
	 * Get a single row from a database,
	 * also make use of MySQL joins in query
	 *
	 * @param	string	$p_tables		Tables to get row from
	 * @param	array	$p_joins		[Optional] An array that contains an array of items with the following options:
	 *									string type Type of join, e.g. left
	 *									string table Table to join with optional alias
	 *									string local Local key to join on
	 *									string foreign Foreign key to join on
	 * @param	string	$p_opt			[Optional] Any options, such as WHERE clauses
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	mixed	Result
	 */
	public function getJoinedRow($p_tables, $p_joins = array(), $p_opt = '', $p_opt_values = array()) {
		
		// Prepare values for database
		$p_tables = parent::buildFromString($this->preDB($p_tables));
		$p_joins = parent::buildJoinString($this->preDB($p_joins));
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
				
		// Build the query
		$query = "
			SELECT *
			FROM {$p_tables}
			{$p_joins}
			{$p_opt}
			LIMIT 1
		";
		
		// Get the result and report any errors
		$result = $this->db->prepare($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_joined_row', $this->db->error, $query);
		
		// Return the resulting row
		return $this->postDB(@$result->fetch_row());
	}
	
	/**
	 * Get multiple rows from a database, fetch them in an array,
	 * also make use of MySQL joins in query
	 *
	 * @param	string	$p_tables		Tables to get data from
	 * @param	array	$p_joins		[Optional] An array that contains an array of items with the following options:
	 *									string type Type of join, e.g. left
	 *									string table Table to join with optional alias
	 *									string local Local key to join on
	 *									string foreign Foreign key to join on
	 * @param	string	$p_opt			[Optional] Any MySQL commands to pass, such as WHERE
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	mixed	Result
	 */
	public function getJoinedRows($p_tables, $p_joins = array(), $p_opt = '', $p_opt_values = array()) {
		
		// Prepare values for database
		$p_tables = parent::buildFromString($this->preDB($p_tables));
		$p_joins = parent::buildJoinString($this->preDB($p_joins));
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
				
		// Build the query
		$query = "
			SELECT *
			FROM {$p_tables}
			{$p_joins}
			{$p_opt}
		";
		
		// Get the result, report any errors
		$result = $this->db->prepare($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_joined_rows', $this->db->error, $query);
		
		// Return the built array of rows
		$return = array();
		while($temp = $result->fetch_row())
			$return[] = $temp;
		return $this->postDB($return);
	}
	
}