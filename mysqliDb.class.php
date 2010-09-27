<?php

/**
 * Standard database class using new PHP MySQLi object
 * 
 * @package dblib
 * @author Jamie Hurst
 * @version 1.2.3
 */

require_once 'Db.class.php';

/**
 * MySQLi class
 */
class mysqliDb extends Db {
	
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
		if($this->getConfig('autoClose')) {
			$this->closeDb();
		}
	}
	
	/**
	 * Get the singleton instance of this class
	 *
	 * @return mysqliDb Instance
	 */
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new mysqliDb();
		}
		return self::$_instance;
	}
	
	/**
	 * Apply an existing database object (PHP MySQLi object) to the mysqliDb object
	 *
	 * @param MySQLi $object PHP MySQLi Object
	 * @return mysqliDb This object
	 */
	public function applyObject($object) {
		if($this->_db == null) {
			$this->_db = $object;
		}
		return $this;
	}
	
	/**
	 * Set the parameters used to connect to the database
	 * 
	 * @param string $host [Optional] Database host
	 * @param string $user [Optional] Database username
	 * @param string $pass [Optional] Database password
	 * @param string $db [Optional] Database to use
	 */
	public function setupDb($host = null, $user = null, $pass = null, $db = null) {
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
	public function connectDb($host = null, $user = null, $pass = null, $db = null) {
		// Check if connection is lready established
		if($this->isConnected()) {
			return true;
		}
		
		// Call setupDb() to handle the parameters
		$this->setupDb($host, $user, $pass, $db);
		
		// Attempt a connection
		$this->_db = new mysqli($this->_host, $this->_user, $this->_pass, $this->_name);
		if(!$this->_db) {
			$this->errorDB('connect');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Check whether this instance is connected to the server or not
	 *
	 * @return boolean Connected
	 * @since 1.2
	 */
	public function isConnected() {
		if($this->_db) {
			// Dislike supressing the warning but can't be helped
			$stat = @$this->_db->stat();
			if(!empty($stat)) {
				return true;
			}
			return false;
		}
		return false;
	}
	
	/**
	 * Close the active database connection if one exists
	 */
	public function closeDb() {
		if($this->isConnected()) {
			$this->_db->close();
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
	 * Return the field names for a given table that need to be pulled out
	 *
	 * @param string $table Table name
	 * @return array Array of fields
	 */
	public function getFieldsFromTable($table) {
		$query = 'SHOW COLUMNS FROM `' . $table . '`';

		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_fields_from_table', $this->_db->error, $query);
			return false;
		}
		$fields = array();
		while($row = $result->fetch_assoc()) {
			$fields[] = $row['Field'];
		}
		return $fields;
	}
	
	/**
	 * Get a single field from a database using a table and a where query
	 *
	 * @param string $field Field to fetch
	 * @param string $table Table to get field from
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return string|boolean Result
	 * @deprecated Use getFields instead
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
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return string|array|boolean Result
	 * @since 1.1.1
	 */
	public function getFields($fields, $table, $opt = '', $optValues = '') {

		// Check if fields is an array
		if(is_array($fields)) {
			$fieldsArray = true;
		} else {
			$fieldsArray = false;
		}
		
		// Prepare values for database checking
		$fields = $this->buildSelect($fields);
		$table = $this->buildFrom($table);
		$opt = $this->buildOpt($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT {$fields}
			FROM {$table}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_fields', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting field
		if($fieldsArray) {
			$row = $result->fetch_assoc();
			return $this->postDb($row);
		}
		$row = $result->fetch_array();
		return $this->postDb($row[0]);
	}
	
	/**
	 * Get a single row from a database
	 *
	 * @param string $table Table to get row from
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return array|boolean Result
	 */
	public function getRow($table, $opt = '', $optValues = '') {
		
		// Prepare values for database
		$table = $this->buildFrom($table);
		$opt = $this->buildOpt($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$table}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}
		
		// Get the result and report any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_row', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting row
		return $this->postDb($result->fetch_assoc());
	}
	
	/**
	 * Get multiple rows from a database, fetch them in an array
	 *
	 * @param string $table Table to get data from
	 * @param string $opt [Optional] Any MySQL commands to pass, such as WHERE
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return array|boolean Result
	 */
	public function getRows($table, $opt = '', $optValues = '') {
		
		// Prepare values for database
		$table = $this->buildFrom($table);
		$opt = $this->buildOpt($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$table}
			{$opt}
		";
		
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}
			
		// Get the result, report any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		while($temp = $result->fetch_assoc()) {
			$return[] = $temp;
		}
		return $this->postDb($return);
	}
	
	/**
	 * Get the number of rows returned from a query
	 *
	 * @param string $table Table to get data from
	 * @param string $opt [Optional] Any MySQL commands to pass, such as WHERE
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return int|boolean Number of rows
	 */
	public function getNumRows($table, $opt = '', $optValues = '') {

		// Prepare values for database
		$table = $this->buildFrom($table);
		$opt = $this->buildOpt($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$table}
			{$opt}
		";

		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Get the result, report any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_num_rows', $this->_db->error, $query);
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
	 *						{string 'type' Type of join, e.g. left},
	 *						{string 'table' Table to join with optional alias},
	 *						{string 'local' Local key to join on},
	 *						{string 'foreign' Foreign key to join on}
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return string|array|boolean Result
	 */
	public function getJoinedFields($fields, $tables, $joins = array(), $opt = '', $optValues = '') {
		
		// Check if fields is an array
		if(is_array($fields)) {
			$fieldsArray = true;
		} else {
			$fieldsArray = false;
		}
		
		// Prepare values for database checking
		$fields = $this->buildSelect($fields);
		$tables = $this->buildFrom($tables);
		$joins = $this->buildJoin($joins);
		$opt = $this->buildOpt($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT {$fields}
			FROM {$tables}
			{$joins}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_fields', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting field
		if($fieldsArray) {
			$row = $result->fetch_assoc();
			return $this->postDb($row);
		}
		$row = $result->fetch_array();
		return $this->postDb($row[0]);
	}
	
	/**
	 * Get one row from a database using a table and a where query,
	 * also make use of MySQL joins in query
	 *
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						{string 'type' Type of join, e.g. left},
	 *						{string 'table' Table to join with optional alias},
	 *						{string 'local' Local key to join on},
	 *						{string 'foreign' Foreign key to join on}
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return array|boolean Result
	 */
	public function getJoinedRow($tables, $joins = array(), $opt = '', $optValues = '') {
		
		// Prepare values for database checking
		$tables = $this->buildFrom($tables);
		$joins = $this->buildJoin($joins);
		$opt = $this->buildOpt($opt, $optValues);
		
		// Build the query
		$query = "
			SELECT *
			FROM {$tables}
			{$joins}
			{$opt}
			LIMIT 1
		";
		
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_row', $this->_db->error, $query);
			return false;
		}
		
		// Return the resulting field
		return $this->postDb($result->fetch_assoc());
	}
	
	/**
	 * Get multiple rows from a database, fetch them in an array,
	 * also make use of MySQL joins in query
	 *
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						{string 'type' Type of join, e.g. left},
	 *						{string 'table' Table to join with optional alias},
	 *						{string 'local' Local key to join on},
	 *						{string 'foreign' Foreign key to join on}
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return array|boolean Result
	 */
	public function getJoinedRows($tables, $joins = array(), $opt = '', $optValues = '') {

		// Prepare values for database checking
		$tables = $this->buildFrom($tables);
		$joins = $this->buildJoin($joins);
		$opt = $this->buildOpt($opt, $optValues);

		// Build the query
		$query = "
			SELECT *
			FROM {$tables}
			{$joins}
			{$opt}
		";

		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		while($temp = $result->fetch_assoc()) {
			$return[] = $temp;
		}
		return $this->postDb($return);
	}
	
	/**
	 * Get multiple rows of specific fields from a database, fetch them in an array,
	 * also make use of MySQL joins in query
	 *
	 * @param string $fields Fields to fetch
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						{string 'type' Type of join, e.g. left},
	 *						{string 'table' Table to join with optional alias},
	 *						{string 'local' Local key to join on},
	 *						{string 'foreign' Foreign key to join on}
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return array|boolean Result
	 */
	public function getJoinedRowsOfFields($fields, $tables, $joins = array(), $opt = '', $optValues = '') {

		// Check if fields is an array
		if(is_array($fields)) {
			$fieldsArray = true;
		} else {
			$fieldsArray = false;
		}
		
		// Prepare values for database checking
		$fields = $this->buildSelect($fields);
		$tables = $this->buildFrom($tables);
		$joins = $this->buildJoin($joins);
		$opt = $this->buildOpt($opt, $optValues);

		// Build the query
		$query = "
			SELECT {$fields}
			FROM {$tables}
			{$joins}
			{$opt}
		";

		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		if($fieldsArray) {
			while($temp = $result->fetch_assoc()) {
				$return[] = $temp;
			}
		} else {
			while($temp = $result->fetch_array()) {
				$return[] = $temp[0];
			}
		}
		return $this->postDb($return);
	}
	
	/**
	 * Get the number of rows returned from a query,
	 * also make use of MySQL joins in query
	 *
	 * @param string $tables Tables to get fields from
	 * @param array	$joins [Optional] An array that contains an array of items with the following options:
	 *						{string 'type' Type of join, e.g. left},
	 *						{string 'table' Table to join with optional alias},
	 *						{string 'local' Local key to join on},
	 *						{string 'foreign' Foreign key to join on}
	 * @param string $opt [Optional] Any options, such as WHERE clauses
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return int|boolean Result
	 */
	public function getNumJoinedRows($tables, $joins = array(), $opt = '', $optValues = '') {

		// Prepare values for database checking
		$tables = $this->buildFrom($tables);
		$joins = $this->buildJoin($joins);
		$opt = $this->buildOpt($opt, $optValues);

		// Build the query
		$query = "
			SELECT *
			FROM {$tables}
			{$joins}
			{$opt}
		";

		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Run the query and report all errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_num_rows', $this->_db->error, $query);
			return false;
		}

		// Return the number of rows
		return $result->num_rows;
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
		$table = $this->buildFrom($table);
		$data = $this->preDb($data);
			
		// Join up data
		$fields = $this->buildSelect(array_keys($data));
		$values = join(', ', array_values($data));
		
		// Build the query
		$query = "
			INSERT INTO {$table}
			({$fields}) VALUES ({$values})
		";
			
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('insert_row', $this->_db->error, $query);
			return false;
		}
		
		// Return the query result
		return $result;
	}
	
	/**
	 * Replace a row into the database
	 * 
	 * @param string $table Table to perform replacement on
	 * @param array $data K=>V array of columns and data
	 * @return boolean Successful replacement or not
	 */
	public function replaceRow($table, $data) {

		// Sort out values for database query
		$table = $this->buildFrom($table);
		$data = $this->preDb($data);
			
		// Join up data
		$fields = $this->buildSelect(array_keys($data));
		$values = join(', ', array_values($data));
		
		// Build the query
		$query = "
			REPLACE INTO {$table}
			({$fields}) VALUES ({$values})
		";
			
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('replace_row', $this->_db->error, $query);
			return false;
		}
		
		// Return the query result
		return $result;
	}
	
	/**
	 * Update one or more table rows
	 *
	 * @param string $table Table to perform update on
	 * @param array $data K=>V array of columns and data
	 * @param string $opt [Optional] Any WHERE clauses or other options
	 * @param string|boolean $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return boolean Successful update or not
	 */
	public function updateRows($table, $data, $opt = '', $optValues = '') {
		
		// Sort out values for database query
		$table = $this->buildFrom($table);
		$data = $this->preDb($data);
		$opt = $this->buildOpt($opt, $optValues);
	
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
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('update_rows', $this->_db->error, $query);
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
	 * @param string|array	$optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
	 * @return boolean Query was successful or not
	 */
	public function deleteRows($table, $opt = '', $optValues = '') {

		// Sort out values for database query
		$table = $this->buildFrom($table);
		$opt = $this->buildOpt($opt, $optValues);
		
		// Build query
		$query = "
			DELETE FROM {$table}
			{$opt}
		";
		
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('delete_rows', $this->_db->error, $query);
			return false;
		}
		
		// Return the query result
		return $result;
	}

	/**
	 * Perform a raw query on the MySQL object returning either a set of rows or a boolean result
	 *
	 * @param string $query Query string
	 * @param string $optValues [Optional] Replacements for ? values in the query
	 * @return array|boolean Array of rows, boolean true or false depending on query
	 * @since 1.2.3
	 */
	public function rawQuery($query, $optValues = '') {
		$query = $this->buildOpt($query, $optValues);
		
		// Check if the query needs to be printed
		if($this->getConfig('getQueries')) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = $this->_db->query($query);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('raw', mysql_error($this->_db), $query);
			return false;
		}
		
		// If there are one or more rows, return them
		if($result->num_rows > 0) {
			// Return the built array of rows
			$return = array();
			while($temp = $result->fetch_assoc()) {
				$return[] = $temp;
			}
			return $this->postDb($return);
		}
		return true;
	}
	
	/**
	 * Get the last inserted row's ID
	 *
	 * @return int Auto-increment ID value of last insert
	 */
	public function insertId() {
		return $this->_db->insert_id;
	}
		
}