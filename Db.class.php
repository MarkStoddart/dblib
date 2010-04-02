<?php

/**
 * Superclass for all database classes, provides common functionality
 * 
 * @package dblib
 * @author Jamie Hurst
 * @version 1.2
 */

/**
 * Common DB class
 */
abstract class Db {
	
	// Version constants
	const VERSION_MAJOR = 1;
	const VERSION_MINOR = 2;
	const VERSION_REVISION = 0;
	
	// Set up some useful options
	protected $_stripEnabled = true;
	protected $_debug = false;
	protected $_autoClose = false;
	protected $_caching = true;
	protected $_exitOnError = true;
	protected $_getQueries = false;
	protected $_adminEmail = false;
	protected $_tableSeparator = '|';
	
	// New singleton instance
	protected static $_instance = null;
	
	/**
	 * Constructor
	 */
	protected function __construct() {
		// Check for magic quotes
		if(get_magic_quotes_gpc()) {
			$this->_stripEnabled = false;
		}
	}
	
	/**
	 * Set the value of strip enabled
	 *
	 * @param mixed $value Value to set
	 * @return Db For chaining
	 */
	public function setStripEnabled($value) {
		$this->_stripEnabled = $value;
		return $this;
	}
	
	/**
	 * Get strip enabled value
	 *
	 * @return mixed strip enabled value
	 */
	public function getStripEnabled() {
		return $this->_stripEnabled;
	}
		
	/**
	 * Set the value of debug
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 */
	public function setDebug($value) {
		$this->_debug = $value;
		return $this;
	}
	
	/**
	 * Get debug value
	 *
	 * @return boolean Debug value
	 */
	public function getDebug() {
		return $this->_debug;
	}
		
	/**
	 * Set the value of auto close
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 */
	public function setAutoClose($value) {
		$this->_autoClose = $value;
		return $this;
	}
	
	/**
	 * Get auto close value
	 *
	 * @return boolean Auto close value
	 */
	public function getAutoClose() {
		return $this->_autoClose;
	}
		
	/**
	 * Set the value of caching
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 */
	public function setCaching($value) {
		$this->_caching = $value;
		return $this;
	}
	
	/**
	 * Get caching value
	 *
	 * @return boolean Caching value
	 */
	public function getCaching() {
		return $this->_caching;
	}
		
	/**
	 * Set the value of exit on error
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 */
	public function setExitOnError($value) {
		$this->_exitOnError = $value;
		return $this;
	}
	
	/**
	 * Get exit on error value
	 *
	 * @return boolean Exit on error value
	 */
	public function getExitOnError() {
		return $this->_exitOnError;
	}
		
	/**
	 * Set the value of get queries
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 */
	public function setGetQueries($value) {
		$this->_getQueries = $value;
		return $this;
	}
	
	/**
	 * Get get queries value
	 *
	 * @return boolean Get queries value
	 */
	public function getGetQueries() {
		return $this->_getQueries;
	}
		
	/**
	 * Set the value of admin email
	 *
	 * @param mixed $value Value to set
	 * @return Db For chaining
	 */
	public function setAdminEmail($value) {
		$this->_adminEmail = $value;
		return $this;
	}
	
	/**
	 * Get admin email value
	 *
	 * @return mixed Admin email value
	 */
	public function getAdminEmail() {
		return $this->_adminEmail;
	}
	
	/**
	 * Set the table separator
	 *
	 * @param string $sep Table separator
	 * @return Db For chaining
	 */
	public function setTableSeparator($sep) {
		$this->_tableSeparator = $sep;
		return $this;
	}
	
	/**
	 * Get the table separator
	 *
	 * @return string Table separator
	 * @since 1.2
	 */
	public function getTableSeparator() {
		return $this->getTableSeparator;
	}

	/**
	 * Handle any database errors
	 *
	 * @param string $error Error context
	 * @param string $dbError Error given from DB
	 * @param string $query [Optional] Query from where the error happened
	 */
	protected function errorDb($error, $dbError = '', $query = '') {

		// Only provide detailed output in debug mode
		echo '<p class="db_error">';
		if($this->_debug) {
			echo 'Query error in context "' . $error . '":<br /><strong>'
					. $dbError . 
					'</strong><br /><br />';
			if(!empty($query)) {
				echo '<em>' . $query . '</em>';
			}
		} else {
			echo 'A database error occurred when performing the last operation. The system administrator has been informed.';
			if($this->_adminEmail) {
				mail($this->_adminEmail, 'DBlib -> DB Error (' . $error . ')', 'A database error occurred in context "' . $error . '".' . "\n\n" . $dbError . "\n\n" . 'Query: ' . $query);
			}
		}
		echo '</p>';
		if($this->_exitOnError) {
			exit;
		}
	}
	
	/**
	 * Prepare a variable to be used in a database query
	 *
	 * @param mixed	$var Any variable
	 * @return mixed Variable post-processing
	 */
	protected function preDb($var) {
		
		// Make sure any null variables are returned as passed
		if(is_null($var) || $var === 'NULL') {
			return 'NULL';
		}
		
		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($var)) {
			$newArray = array();
			foreach($var as $key => $value) {
				if($this->_stripEnabled && strpos($value, "\'") === false) {
					$newArray[$this->escape(html_entity_decode($key))] = $this->preDb($value);
				} else {
					$newArray[html_entity_decode($key)] = $this->preDb($value);
				}
			}
			return $newArray;
		} else {
			if($this->_stripEnabled) {
				return "'" . $this->escape(html_entity_decode($var)) . "'";
			}
			return "'" . html_entity_decode($var) . "'";
		}
	}
	
	/**
	 * Prepare a variable result from a database to be used 
	 *
	 * @param mixed $var Any variable
	 * @return mixed Variable post-processing
	 */
	protected function postDb($var) {
		
		// Make sure any false and null variables are returned as passed
		if($var === false) {
			return false;
		}

		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($var)) {
			$newArray = array();
			foreach($var as $key => $value) {
				$newArray[htmlentities(stripslashes($key))] = $this->postDb($value);
			}
			return $newArray;
		}
		return htmlentities(stripslashes($var));
	}
	
	/**
	 * Prepare a set of tables or fields by escaping them
	 *
	 * @param string $stmt Statement to prepare
	 * @return string Escaped statement
	 * @since 1.2
	 */
	protected function prepareFields($stmt) {
		// Escape everything
		$stmt = preg_replace('/(`+)/', '`', preg_replace('/(\w+)/i', '`$1`', $stmt));
		
		// Sort out the 'AS' keywords
		$stmt = str_ireplace('`AS`', 'AS', $stmt);
		
		// Replace any literals
		$stmt = preg_replace('/(\'`|`\')/', "'", $stmt);
		
		// Return the finished statement
		return $stmt;
	}
	
	/**
	 * Prepare a statement to be used in a database, replacing ? with variables
	 *
	 * @param string $stmt Statement to prepare
	 * @param string|array $vars [Optional] Variables to insert
	 * @return string Prepared statement
	 * @since 1.2
	 */
	protected function prepareStatement($stmt, $vars) {
		// Go through each match and replace the ? if a value exists
		if(is_array($vars)) {

			// Get matches
			$numMatches = preg_match_all('/([^\\]\?)/i', $stmt, $matches);
			
			// Throw an error if the matches aren't the same
			if($numMatches > count($vars)) {
				$this->errorDb('escape_count');
			}
			
			// Replace all matches found
			for($i = 0; $i < $numMatches; $i++) {
				if(isset($vars[$i])) {
					$stmt = preg_replace('/([^\\])\?/i', "$1" . $this->preDb($vars[$i]), $stmt, 1);
				}
			}
		} else {
			$stmt = preg_replace('/([^\\])\?/i', "$1" . $this->preDb($vars), $stmt, 1);
		}
		return $stmt;
	}
	
	/**
	 * Build a SELECT string for a query using one or more fields
	 *
	 * @param string|array $fields Fields to use
	 * @return string MySQL formatted string
	 */
	protected function buildSelect($fields) {
		
		// Every field needs to be enclosed in ` characters
		if(is_array($fields)) {
			foreach($fields as $key => $field) {
				$fields[$key] = prepareFields($field);
			}
			return join(', ', $fields);
		}
		return $this->prepareFields($fields);
	}
	
	/**
	 * Build a FROM string for a query using one or more tables
	 *
	 * @param string|array $tables Tables to use
	 * @return string MySQL formatted string
	 */
	protected function buildFrom($tables) {
		
		// Every table name, with alias, needs to be enclosed in ` characters
		if(is_array($tables)) {
			foreach($tables as $key => $table) {
				$tables[$key] = $this->prepareFields($table);
			}
			return '(' . join(', ', $tables) . ')';
		}
	 	return $this->prepareFields($tables);
	}
	
	/**
	 * Build a JOIN string for a query using one or more joins
	 * 
	 * @param array	$joins Joins to perform (as passed from other functions)
	 * @return string MySQL formatted string
	 */
	protected function buildJoin($joins) {
		$string = '';
		foreach($joins as $join) {
			// Build the join string each time by enclosing fields and tables in ` characters
			$string .= "\n"
				. strtoupper($join['type'])
				. ' JOIN '
				. $this->prepareFields($join['table'])
				. ' ON '
				. $this->prepareFields($join['local'])
				. ' ' . (isset($join['condition']) ? $join['condition'] : '=') . ' '
				. $this->prepareFields($join['foreign']);
		}
		
		// Return the formatted string
		return $string;
	}
	
	/**
	 * Build a WHERE/GROUP/ORDER/LIMIT string using the given MySQL command string
	 * and a set of values that are to be used in replacements with ?
	 *
	 * @param string $opt Raw MySQL string
	 * @param string|array $optValues [Optional] Array of values or string to use in replacements
	 * @return string Correctly formatted and escaped string
	 */
	protected function buildOpt($opt, $optValues = null) {
		
		// Return the finished string
		return $this->prepareStatement($opt, $optValues);
	}
	
}