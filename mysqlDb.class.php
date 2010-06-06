<?php

/**
 * Standard database class using traditional PHP MySQL functions
 * 
 * @package dblib
 * @author Jamie Hurst
 * @version 1.2.2
 */

require_once 'iDb.interface.php';
require_once 'Db.class.php';

/**
 * MySQL class
 */
class mysqlDb extends Db {
	
	protected $_db;
	protected $_host;
	protected $_user;
	protected $_pass;
	protected $_name;
	protected $_queryCount;
	
	/**
	 * Constructor
	 * Use the provided Db variable if set, otherwise don't connect just yet
	 * 
	 * @param int $db [Optional] MySQL connection link
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
			$this->closeDb();
		}
	}
	
	/**
	 * Get the singleton instance of this class
	 *
	 * @return mysqlDb Instance
	 */
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new mysqlDB();
		}
		return self::$_instance;
	}
	
	/**
	 * Apply an existing database object (PHP MySQL resource) to the mysqlDB object
	 *
	 * @param int $object PHP MySQL Resource
	 * @return mysqlDb This object
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
		// Check if already connected
		if($this->isConnected()) {
			return true;
		}
		
		// Call setupDb() to handle the parameters
		$this->setupDb($host, $user, $pass, $db);
		
		// Attempt a connection
		$this->_db = mysql_connect($this->_host, $this->_user, $this->_pass);
		if(!$this->_db) {
			$this->errorDb('connect');
			return false;
		}
		
		// Select the database to use
		$select = mysql_select_db($this->_name, $this->_db);
		if(!$select) {
			$this->errorDb('connect_db');
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
			if(is_resource($this->_db)) {
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
			mysql_close($this->_db);
		}
	}
	
	/**
	 * Return the current query count
	 * 
	 * @return	int	Query count
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
		return mysql_real_escape_string($str, $this->_db);
	}
	
	/**
	 * Return the field names for a given table that need to be pulled out
	 *
	 * @param string $table Table name
	 * @return array Array of fields
	 * @since 1.2
	 */
	public function getFieldsFromTable($table) {
		$query = 'SHOW COLUMNS FROM `' . $table . '`';

		// Check if the query needs to be printed
		if($this->_getQueries) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_fields_from_table', mysql_error($this->_db), $query);
			return false;
		}
		$fields = array();
		while($row = mysql_fetch_assoc($result)) {
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
	 * @deprecated Use getFields() instead
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
		if($this->_getQueries) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_fields', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the resulting fields
		if($fieldsArray) {
			return $this->postDb(mysql_fetch_assoc($result));
		}
		return $this->postDb(mysql_result($result, 0));
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
		if($this->_getQueries) {
			return $query;
		}
		
		// Get the result and report any errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_row', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the resulting row
		return $this->postDb(mysql_fetch_assoc($result));
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
		if($this->_getQueries) {
			return $query;
		}
			
		// Get the result, report any errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_rows', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		while($temp = mysql_fetch_assoc($result)) {
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
		if($this->_getQueries) {
			return $query;
		}

		// Get the result, report any errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_num_rows', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the number of rows
		return mysql_num_rows($result);
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
		if($this->_getQueries)
			return $query;
		
		// Run the query and report all errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_fields', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the resulting field
		if($fieldsArray) {
			return $this->postDb(mysql_fetch_assoc($result));
		} else {
			return $this->postDb(mysql_result($result, 0));
		}
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
		if($this->_getQueries) {
			return $query;
		}
		
		// Run the query and report all errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_row', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the resulting field
		return $this->postDb(mysql_fetch_assoc($result));
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
		if($this->_getQueries) {
			return $query;
		}

		// Run the query and report all errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_rows', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		while($temp = mysql_fetch_assoc($result)) {
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
		if($this->_getQueries) {
			return $query;
		}

		// Run the query and report all errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_rows_fields', mysql_error($this->_db), $query);
			return false;
		}
		
		// Return the built array of rows
		$return = array();
		if($fieldsArray) {
			while($temp = mysql_fetch_assoc($result)) {
				$return[] = $temp;
			}
		} else {
			while($temp = mysql_result($result, 0)) {
				$return[] = $temp;
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
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $p_opt string, each ? will be replaced with a value, to escape use \?
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
		if($this->_getQueries) {
			return $query;
		}

		// Run the query and report all errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('get_joined_num_rows', mysql_query($this->_db), $query);
			return false;
		}

		// Return the number of rows
		return mysql_num_rows($result);
	}
	
	/**
	 * Insert a row into the database
	 * 
	 * @param string $table Table to perform insert on
	 * @param array $data K=>V array of columns and data
	 * @return boolean Successful insert or not
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
		if($this->_getQueries) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('insert_row', mysql_error($this->_db), $query);
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
		if($this->_getQueries) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('replace_row', mysql_error($this->_db), $query);
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
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
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
		if($this->_getQueries) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('update_rows', mysql_error($this->_db), $query);
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
	 * @param string|array $optValues [Optional] An optional set of values to escape and replace into the $opt string, each ? will be replaced with a value, to escape use \?
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
		if($this->_getQueries) {
			return $query;
		}

		// Get the result and sort out any errors
		$result = mysql_query($query, $this->_db);
		$this->_queryCount++;
		if(!$result) {
			$this->errorDb('delete_rows', mysql_error($this->_db), $query);
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
	public function insertId() {
		return mysql_insert_id($this->_db);
	}
	
}