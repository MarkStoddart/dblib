<?php

/**
 * Standard database class using new PHP MySQLi object
 * 
 * @author Jamie Hurst
 * @version 1.0
 */

require 'iDB.interface.php';
require 'DB.class.php';

/**
 * MySQLi class
 */
class mysqliDB extends DB implements iDB {
	
	private $db;
	private $host;
	private $user;
	private $pass;
	private $name;
	private $queryCount;
	
	/**
	 * Constructor
	 * Use the provided DB variable if set, otherwise don't connect just yet
	 * 
	 * @param	mixed	$p_db	[Optional] MySQL connection link
	 */
	public function __construct($p_db = false) {
		$this->db = $p_db;
		$this->queryCount = 0;
		parent::__construct();
	}
	
	/**
	 * Destructor
	 * Close the database
	 */
	public function __destruct() {
		$this->closeDB();
	}
	
	/**
	 * Handle any database errors
	 *
	 * @param	string	$p_error	Error context
	 * @param	string	$p_db_error	Error given from DB
	 * @param	string	$p_query	[Optional] Query from where the error happened
	 */
	protected function errorDB($p_error, $p_db_error = '', $p_query = '') {

		// Pass up to the parent class
		parent::errorDB($p_error, $p_db_error, $p_query);
		
	}
	
	/**
	 * Set the parameters used to connect to the database
	 * 
	 * @param	string	$p_host	[Optional] Database host
	 * @param	string	$p_user	[Optional] Database username
	 * @param	string	$p_pass	[Optional] Database password
	 * @param	string	$p_db	[Optional] Database to use
	 */
	public function setupDB($p_host = null, $p_user = null, $p_pass = null, $p_db = null) {
		if(!is_null($p_host))
			$this->host = $p_host;
		if(!is_null($p_user))
			$this->user = $p_user;
		if(!is_null($p_pass))
			$this->pass = $p_pass;
		if(!is_null($p_db))
			$this->name = $p_db;
	}
	
	/**
	 * Connect to the database using the parameters given, or those already present
	 * 
	 * @param	string	$p_host	[Optional] Database host
	 * @param	string	$p_user	[Optional] Database username
	 * @param	string	$p_pass	[Optional] Database password
	 * @param	string	$p_db	[Optional] Database to use
	 * @return	boolean	Success or not
	 */
	public function connectDB($p_host = null, $p_user = null, $p_pass = null, $p_db = null) {
		// Call setupDB() to handle the parameters
		$this->setupDB($p_host, $p_user, $p_pass, $p_db);
		
		// Attempt a connection
		$this->db = new mysqli($this->host, $this->user, $this->pass, $this->name);
		if(!$this->db) {
			$this->errorDB('connect');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Close the active database connection if one exists
	 */
	public function closeDB() {
		if($this->db)
			$this->db->close();
	}
	
	/**
	 * Return the current query count
	 * 
	 * @return	int	Query count
	 */
	public function getQueryCount() {
		return $this->queryCount;
	}
	
	/**
	 * Prepare a variable to be used in a database query
	 *
	 * @param mixed $p_var Any variable
	 * @return mixed Variable post-processing
	 */
	public function preDB($p_var) {
		
		// Pass control to parent
		return parent::preDB($p_var);
	}

	/**
	 * Prepare a variable result from a database to be used 
	 *
	 * @param mixed $p_var Any variable
	 * @return mixed Variable post-processing
	 */
	public function postDB($p_var) {
		
		// Pass control to parent
		return parent::postDB($p_var);
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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_field', $this->db->error, $query);
		
		// Return the resulting field
		$row = @$result->fetch_array();
		return $this->postDB($row[0]);
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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_row', $this->db->error, $query);
		
		// Return the resulting row
		return $this->postDB(@$result->fetch_array());
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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_rows', $this->db->error, $query);
		
		// Return the built array of rows
		$return = array();
		while($temp = $result->fetch_array())
			$return[] = $temp;
		return $this->postDB($return);
	}
	
	/**
	 * Get the number of rows returned from a query
	 *
	 * @param	string	$p_table		Table to get data from
	 * @param	string	$p_opt			[Optional] Any MySQL commands to pass, such as WHERE
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	int		Number of rows
	 */
	public function getNumRows($p_table, $p_opt = '', $p_opt_values = array()) {

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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_num_rows', $this->db->error, $query);
		
		// Return the number of rows
		return $result->num_rows;
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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_joined_fields', $this->db->error, $query);
		
		// Return the resulting field
		$row = @$result->fetch_array();
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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_joined_row', $this->db->error, $query);
		
		// Return the resulting row
		return $this->postDB(@$result->fetch_array());
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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_joined_rows', $this->db->error, $query);
		
		// Return the built array of rows
		$return = array();
		while($temp = $result->fetch_array())
			$return[] = $temp;
		return $this->postDB($return);
	}
	
	/**
	 * Get the number of rows returned from a query,
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
	 * @return	int		Number of rows
	 */
	public function getNumJoinedRows($p_tables, $p_joins = array(), $p_opt = '', $p_opt_values = array()) {

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
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_num_joined_rows', $this->db->error, $query);
		
		// Return the number of rows
		return $result->num_rows;
	}
	
	/**
	 * Update one or more table rows
	 *
	 * @param	string	$p_table		Table to perform update on
	 * @param	array	$p_data			K=>V array of columns and data
	 * @param	string	$p_opt			[Optional] Any WHERE clauses or other options
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	boolean	Successful update or not
	 */
	public function updateRows($p_table, $p_data, $p_opt = '', $p_opt_values = array()) {
		
		// Sort out values for database query
		$p_table = $this->preDB($p_table);
		$p_data = $this->preDB($p_data);
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
	
		// Join up data
		$updates = array();
		foreach($p_data as $key => $value)
			$updates[] = "`{$key}` = '{$value}'";
		$updates = join(', ', $updates);
		
		// Build the query
		$query = "
			UPDATE `{$p_table}`
			SET {$updates}
			{$p_opt}
		";
		
		// Get the result and sort out any errors
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			self::errorDB('update_rows', $this->db->error, $query);
		
		// Return the query result
		return $result;
	}
	
	/**
	 * Insert a row into the database
	 * 
	 * @param	string	$p_table		Table to perform update on
	 * @param	array	$p_data			K=>V array of columns and data
	 * @param	string	$p_opt			[Optional] Any WHERE clauses or other options
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	boolean	Successful update or not
	 */
	public function insertRow($p_table, $p_data, $p_opt = '', $p_opt_values = array()) {

		// Sort out values for database query
		$p_table = $this->preDB($p_table);
		$p_data = $this->preDB($p_data);
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
			
		// Join up data
		$fields = array();
		foreach(array_keys($p_data) as $field)
			$fields[] = '`' . $field . '`';
		$fields = join(', ', $fields);
		
		$values = array();
		foreach($p_data as $value)
			$values[] = "'" . $values . "'";
		$values = join(', ', $values);
		
		// Build the query
		$query = "
			INSERT INTO `{$p_table}`
			({$fields}) VALUES ({$values})
			{$p_opt}
		";
			
		// Get the result and sort out any errors
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			self::errorDB('insert_row', $this->db->error, $query);
		
		// Return the query result
		return $result;
	}
	
	/**
	 * Delete one or more rows from the database
	 *
	 * @param	string	$p_table		Table to delete from
	 * @param	string	$p_opt			[Optional] Any WHERE clauses or other options
	 * @param	array	$p_opt_values	[Optional] An optional set of values to escape and replace into the $p_opt string,
	 *										each ? will be replaced with a value, to escape use \?
	 * @return	boolean	Query was successful or not
	 */
	public function deleteRows($p_table, $p_opt = '', $p_opt_values = array()) {

		// Sort out values for database query
		$p_table = $this->preDB($p_table);
		$p_opt = parent::buildOptString($p_opt, $p_opt_values);
		
		// Build query
		$query = "
			DELETE FROM `{$p_table}`
			{$p_opt}
		";
		
		// Get the result and sort out any errors
		$result = $this->db->query($query);
		$this->queryCount++;
		if(!$result)
			self::errorDB('delete_rows', $this->db->error, $query);
		
		// Return the query result
		return $result;
	}
	
}