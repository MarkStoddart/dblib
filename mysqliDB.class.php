<?php

/**
 * Standard database class using new PHP MySQLi object
 * 
 * @package dblib
 * @author Jamie Hurst
 * @version 1.1
 */

require_once 'iDB.interface.php';
require_once 'DB.class.php';

/**
 * MySQLi class
 */
class mysqliDB extends DB implements iDB {
	
	protected $_db;
	protected $_host;
	protected $_user;
	protected $_pass;
	protected $_name;
	protected $_queryCount;
	
	/**
	 * Constructor
	 * Use the provided DB variable if set, otherwise don't connect just yet
	 * 
	 * @param mixed $db [Optional] MySQL connection link
	 */
	public function __construct($db = null) {
		$this->_db = $db;
		$this->_queryCount = 0;
		parent::__construct();
	}
	
	/**
	 * Destructor
	 * Close the database
	 */
	public function __destruct() {
		if($this->_autoClose) {
			$this->closeDB();
		}
	}
	
	/**
	 * Set the parameters used to connect to the database
	 * 
	 * @param string $host [Optional] Database host
	 * @param string $user [Optional] Database username
	 * @param string $pass [Optional] Database password
	 * @param string $db [Optional] Database to use
	 */
	public function setupDB($host = null, $user = null, $pass = null, $db = null) {
		if(!is_null($host)) {
			$this->_host = $host;
		}
		if(!is_null($user)) {
			$this->_user = $user;
		}
		if(!is_null($pass)) {
			$this->_pass = $pass;
		}
		if(!is_null($db)) {
			$this->_name = $db;
		}
	}
	
	/**
	 * Connect to the database using the parameters given, or those already present
	 * 
	 * @param string $host [Optional] Database host
	 * @param string $user [Optional] Database username
	 * @param string $pass [Optional] Database password
	 * @param string $db [Optional] Database to use
	 * @return boolean Success or not
	 */
	public function connectDB($host = null, $user = null, $pass = null, $db = null) {
		// Call setupDB() to handle the parameters
		$this->setupDB($host, $user, $pass, $db);
		
		// Attempt a connection
		$this->_db = new mysqli($this->_host, $this->_user, $this->_pass, $this->_name);
		if(!$this->_db) {
			$this->errorDB('connect');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Close the active database connection if one exists
	 */
	public function closeDB() {
		if($this->_db) {
			@$this->_db->close();
		}
	}
	
	/**
	 * Return the current query count
	 * 
	 * @return int Query count
	 */
	public function getQueryCount() {
		return $this->_queryCount;
	}
	
	/**
	 * Escape the given string
	 *
	 * @param string $str String to escape
	 * @return string Escaped string
	 */
	public function escape($str) {
		return $this->_db->real_escape_string($str);
	}
	
	/**
	 * Get a single field from a database using a table and a where query
	 *
	 * @param string $field Field to fetch
	 * @param string $table Table to get field from
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 * @deprecated
	 */
	public function getField($field, $table, $opt = '', $optValues = '') {
		return $this->getFields($field, $table, $opt, $optValues);
	}

	/**
	 * Get one or more fields from a database using a table and a where query
	 *
	 * @param mixed $fields Fields to fetch
	 * @param string $table Table to get field from
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 */
	public function getFields($fields, $table, $opt = '', $optValues = '') {

		// Check if fields is an array
		if(is_array($fields)) {
			$fieldsArray = true;
		} else {
			$fieldsArray = false;
		}
		
		// Prepare values for database checking
		$fields = $this->buildSelectString($fields);
		$table = $this->buildFromString($table);
		$opt = $this->buildOptString($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT {$fields}
			FROM {$table}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_fields', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting field
		if($fieldsArray) {
			$row = @$result->fetch_assoc();
			return $this->postDB($row);
		} else {
			$row = @$result->fetch_array();
			return $this->postDB($row[0]);
		}
	}
	
	/**
	 * Get a single row from a database
	 *
	 * @param string $table Table to get row from
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 */
	public function getRow($table, $opt = '', $optValues = '') {
		
		// Prepare values for database
		$table = $this->buildFromString($table);
		$opt = $this->buildOptString($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$table}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}
		
		// Get the result and report any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_row', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting row
		return $this->postDB(@$result->fetch_assoc());
	}
	
	/**
	 * Get multiple rows from a database, fetch them in an array
	 *
	 * @param string $table Table to get data from
	 * @param string $opt [Optional] Any MySQL commands to pass, such as WHERE
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 */
	public function getRows($table, $opt = '', $optValues = '') {
		
		// Prepare values for database
		$table = $this->buildFromString($table);
		$opt = $this->buildOptString($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$table}
			{$opt}
		";
		
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}
			
		// Get the result, report any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		while($temp = $result->fetch_assoc()) {
			$return[] = $temp;
		}
		return $this->postDB($return);
	}
	
	/**
	 * Get the number of rows returned from a query
	 *
	 * @param string $table Table to get data from
	 * @param string $opt [Optional] Any MySQL commands to pass, such as WHERE
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return int Number of rows
	 */
	public function getNumRows($table, $opt = '', $optValues = '') {

		// Prepare values for database
		$table = $this->buildFromString($table);
		$opt = $this->buildOptString($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$table}
			{$opt}
		";

		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}

		// Get the result, report any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_num_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the number of rows
		return $result->num_rows;
	}
	
	/**
	 * Get one or more fields from a database using a table and a where query,
	 * also make use of MySQL joins in query
	 *
	 * @param string $fields Fields to fetch
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						string type Type of join, e.g. left
	 *						string table Table to join with optional alias
	 *						string local Local key to join on
	 *						string foreign Foreign key to join on
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $p_opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 */
	public function getJoinedFields($fields, $tables, $joins = array(), $opt = '', $optValues = '') {
		
		// Check if fields is an array
		if(is_array($fields)) {
			$fieldsArray = true;
		} else {
			$fieldsArray = false;
		}
		
		// Prepare values for database checking
		$fields = $this->buildSelectString($fields);
		$tables = $this->buildFromString($tables);
		$joins = $this->buildJoinString($joins);
		$opt = $this->buildOptString($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT {$fields}
			FROM {$tables}
			{$joins}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_joined_fields', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting field
		if($fieldsArray) {
			$row = @$result->fetch_assoc();
			return $this->postDB($row);
		} else {
			$row = @$result->fetch_array();
			return $this->postDB($row[0]);
		}
	}
	
	/**
	 * Get one row from a database using a table and a where query,
	 * also make use of MySQL joins in query
	 *
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						string type Type of join, e.g. left
	 *						string table Table to join with optional alias
	 *						string local Local key to join on
	 *						string foreign Foreign key to join on
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $p_opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 */
	public function getJoinedRow($tables, $joins = array(), $opt = '', $optValues = '') {
		
		// Prepare values for database checking
		$tables = $this->buildFromString($tables);
		$joins = $this->buildJoinString($joins);
		$opt = $this->buildOptString($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$tables}
			{$joins}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_joined_row', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting field
		return $this->postDB(@$result->fetch_assoc());
	}
	
	/**
	 * Get multiple rows from a database, fetch them in an array,
	 * also make use of MySQL joins in query
	 *
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						string type Type of join, e.g. left
	 *						string table Table to join with optional alias
	 *						string local Local key to join on
	 *						string foreign Foreign key to join on
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $p_opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 */
	public function getJoinedRows($tables, $joins = array(), $opt = '', $optValues = '') {

		// Prepare values for database checking
		$tables = $this->buildFromString($tables);
		$joins = $this->buildJoinString($joins);
		$opt = $this->buildOptString($opt, $optValues);

		// Build the query
		$query = "
			SELECT *
			FROM {$tables}
			{$joins}
			{$opt}
		";

		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}

		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_joined_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		while($temp = $result->fetch_assoc()) {
			$return[] = $temp;
		}
		return $this->postDB($return);
	}
	
	/**
	 * Get the number of rows returned from a query,
	 * also make use of MySQL joins in query
	 *
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						string type Type of join, e.g. left
	 *						string table Table to join with optional alias
	 *						string local Local key to join on
	 *						string foreign Foreign key to join on
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $p_opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return mixed Result
	 */
	public function getNumJoinedRows($tables, $joins = array(), $opt = '', $optValues = '') {

		// Prepare values for database checking
		$tables = $this->buildFromString($tables);
		$joins = $this->buildJoinString($joins);
		$opt = $this->buildOptString($opt, $optValues);

		// Build the query
		$query = "
			SELECT *
			FROM {$tables}
			{$joins}
			{$opt}
		";

		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}

		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('get_joined_num_rows', $this->_db->error, $query);
			return false;
		}

		// Return the number of rows
		return $result->num_rows;
	}
	
	/**
	 * Update one or more table rows
	 *
	 * @param string $table Table to perform update on
	 * @param array $data K=>V array of columns and data
	 * @param string $opt [Optional] Any WHERE clauses or other options
	 * @param mixed $optValues [Optional] An optional set of values to escape and replace into the $opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return boolean Successful update or not
	 */
	public function updateRows($table, $data, $opt = '', $optValues = '') {
		
		// Sort out values for database query
		$table = $this->buildFromString($table);
		$data = $this->preDB($data);
		$opt = $this->buildOptString($opt, $optValues);
	
		// Join up data
		$updates = array();
		foreach($data as $key => $value) {
			$updates[] = "`{$key}` = {$value}";
		}
		$data = join(', ', $updates);
		
		// Build the query
		$query = "
			UPDATE {$table}
			SET {$data}
			{$opt}
		";
		
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('update_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the query result
		return $result;
	}
	
	/**
	 * Insert a row into the database
	 * 
	 * @param string $table Table to perform update on
	 * @param array $data K=>V array of columns and data
	 * @return boolean Successful update or not
	 */
	public function insertRow($table, $data) {

		// Sort out values for database query
		$table = $this->buildFromString($table);
		$data = $this->preDB($data);
			
		// Join up data
		$fields = array();
		foreach(array_keys($data) as $field) {
			$fields[] = '`' . $field . '`';
		}
		$fields = join(', ', $fields);
		
		$values = array();
		foreach($data as $value) {
			$values[] = $value;
		}
		$values = join(', ', $values);
		
		// Build the query
		$query = "
			INSERT INTO {$table}
			({$fields}) VALUES ({$values})
		";
			
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('insert_row', $this->_db->error, $query);
			return false;
		}
		
		// Return the query result
		return $result;
	}
	
	/**
	 * Delete one or more rows from the database
	 *
	 * @param string $table Table to delete from
	 * @param string $opt [Optional] Any WHERE clauses or other options
	 * @param mixed	$optValues [Optional] An optional set of values to escape and replace into the $p_opt string,
	 *							each ? will be replaced with a value, to escape use \?
	 * @return boolean Query was successful or not
	 */
	public function deleteRows($table, $opt = '', $optValues = '') {

		// Sort out values for database query
		$table = $this->buildFromString($table);
		$opt = $this->buildOptString($opt, $optValues);
		
		// Build query
		$query = "
			DELETE FROM {$table}
			{$opt}
		";
		
		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDB('delete_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the query result
		return $result;
	}

	/**
	 * Get the last inserted row's ID
	 *
	 * @return int Auto-increment ID value of last insert
	 */
	public function insertID() {
		return $this->_db->insert_id;
	}
		
}