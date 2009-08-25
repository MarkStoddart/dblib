<?php

/**
 * Standard database class using traditional PHP MySQL functions
 * 
 * @author Jamie Hurst
 * @version 1.0
 */

require 'iDB.interface.php';
require 'DB.interface.php';

/**
 * MySQL class
 */
public class mysqlDB extends DB implements iDB {
	
	private var $db;
	private var $host;
	private var $Duser;
	private var $pass;
	private var $name;
	private var $queryCount;
	
	/**
	 * Constructor
	 * Use the provided DB variable if set, otherwise don't connect just yet
	 * 
	 * @param	mixed	$p_db	[Optional] MySQL connection link
	 */
	public function __construct($p_db = false) {
		$this->db = $p_db;
		parent::__construct;
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
	private function errorDB($p_error, $p_db_error = '', $p_query = '') {

		// Only provide detailed output in debug mode
		echo '<p>';
		if(DBLIB_DEBUG) {
			echo 'Query error in context "' . $p_error . '":<br /><strong>'
					. $p_db_error . 
					'</strong><br /><br />';
			if(!empty($p_query))
				echo '<em>' . $p_query . '</em>';
		} else {
			echo 'A database error occurred when performing the last operation. The system administrator has been informed.';
			// TODO Need to configure an email for an error here!
		}
		echo '</p>';
		// TODO Think about whether we need this exit or not...
		exit;
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
		$this->db = mysql_connect($this->host, $this->user, $this->pass);
		if(!$this->db) {
			$this->errorDB('connect');
			return false;
		}
		
		// Select the database to use
		$select = mysql_select_db($this->name, $this->db);
		if(!$select) {
			$this->errorDB('connect_db');
			return false;
		}
		
		return true;
	}
	
	/**
	 * Close the active database connection if one exists
	 */
	public function closeDB() {
		if($this->db)
			mysql_close($this->db);
	}
	
	/**
	 * Prepare a variable to be used in a database query
	 *
	 * @param mixed $p_var Any variable
	 * @return mixed Variable post-processing
	 */
	public function preDB($p_var) {
		
		// Make sure any false variables are returned as passed
		if(!$p_var)
			return false;
		
		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($p_var)) {
			$new_array = array();
			foreach($p_var as $key => $value)
				$new_array[addslashes(htmlspecialchars_decode($key))] = $this->preDB($value);
			return $new_array;
		} else
			return addslashes(htmlspecialchars_decode($p_var));
	}

	/**
	 * Prepare a variable result from a database to be used 
	 *
	 * @param mixed $p_var Any variable
	 * @return mixed Variable post-processing
	 */
	public function postDB($p_var) {
		
		// Make sure any false variables are returned as passed
		if(!$p_var)
			return false;

		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($p_var)) {
			$new_array = array();
			foreach($p_var as $key => $value)
				$new_array[htmlspecialchars(stripslashes($key))] = $this->postDB($value);
			return $new_array;
		} else
			return htmlspecialchars(stripslashes($p_var));
	}
	
	/**
	 * Get a single field from a database using a table and a where query
	 *
	 * @param	string	$p_field	Field to fetch
	 * @param	string	$p_table	Table to get field from
	 * @param	string	$p_opt		[Optional] Any options, such as WHERE clauses
	 * @return	mixed	Result
	 */
	public function getField($p_field, $p_table, $p_opt = '') {

		// Prepare values for database checking
		$p_field = $this->preDB($p_field);
		$p_table = $this->preDB($p_table);
		
		// Build the query
		$query = "
			SELECT `{$p_field}`
			FROM `{$p_table}`
			{$p_opt}
			LIMIT 1
		";
		
		// Run the query and report all errors
		$result = mysql_query($query, $this->db);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_field', mysql_error($this->db), $query);
		
		// Return the resulting field
		return $this->postDB(@mysql_result($result, 0));
	}
	
	/**
	 * Get a single row from a database
	 *
	 * @param	string	$p_table	Table to get row from
	 * @param	string	$p_opt		[Optional] Any options, such as WHERE clauses
	 * @return	mixed	Result
	 */
	public function getRow($p_table, $p_opt = '') {
		
		// Prepare table for database
		$p_table = $this->preDB($p_table);
		
		// Build the query
		$query = "
			SELECT *
			FROM `{$p_table}`
			{$p_opt}
			LIMIT 1
		";
		
		// Get the result and report any errors
		$result = mysql_query($query, $this->db);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_row', mysql_error($this->db), $query);
		
		// Return the resulting row
		return $this->postDB(@mysql_fetch_array($result));
	}
	
	/**
	 * Get multiple rows from a database, fetch them in an array
	 *
	 * @param	string	$p_table	Table to get data from
	 * @param	string	$p_opt		[Optional] Any MySQL commands to pass, such as WHERE
	 * @return	mixed	Result
	 */
	public function getRows($p_table, $p_opt = '') {
		
		// Prepare table for database
		$p_table = $this->preDB($p_table);
		
		// Build the query
		$query = "
			SELECT *
			FROM `{$p_table}`
			{$p_opt}
		";
		
		// Get the result, report any errors
		$result = mysql_query($query, $this->db);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_rows', mysql_error($this->db), $query);
		
		// Return the built array of rows
		$return = array();
		while($temp = mysql_fetch_array($result))
			$return[] = $temp;
		return $this->postDB($return);
	}
	
	/**
	 * Get the number of rows returned from a query
	 *
	 * @param	string	$p_table	Table to get data from
	 * @param	string	$p_opt		[Optional] Any MySQL commands to pass, such as WHERE
	 * @return	int		Number of rows
	 */
	public function getNumRows($p_table, $p_opt = '') {

		// Prepare table for database
		$p_table = $this->preDB($p_table);
		
		// Build the query
		$query = "
			SELECT *
			FROM `{$p_table}`
			{$p_opt}
		";

		// Get the result, report any errors
		$result = mysql_query($query, $this->db);
		$this->queryCount++;
		if(!$result)
			$this->errorDB('get_num_rows', mysql_error($this->db), $query);
		
		// Return the number of rows
		return mysql_num_rows($result);
	}
	
	
	
	// TODO Do the rest of it
}