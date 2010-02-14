<?php

/**
 * Superclass for all database classes, provides common functionality
 * 
 * @package dblib
 * @author Jamie Hurst
 * @version 1.1
 */

/**
 * Common DB class
 */
abstract class DB {
	
	// Set up some useful options
	protected $_stripEnabled = true;
	protected $_debug = false;
	protected $_autoClose = true;
	protected $_caching = true;
	protected $_exitOnError = true;
	protected $_getQueries = false;
	protected $_adminEmail = false;
	
	/**
	 * Constructor
	 */
	protected function __construct() {
		// Check for magic quotes
		if(get_magic_quotes_gpc())
			$this->_stripEnabled = false;
	}
	
	/**
	 * Set the value of debug
	 *
	 * @param boolean $value Value to set
	 * @return object For chaining
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
	 * @return object For chaining
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
	 * @return object For chaining
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
	 * @return object For chaining
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
	 * @return object For chaining
	 */
	public function setGettQueries($value) {
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
	 * @return object For chaining
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
	 * Handle any database errors
	 *
	 * @param string $error Error context
	 * @param string $dbError Error given from DB
	 * @param string $query [Optional] Query from where the error happened
	 */
	protected function errorDB($error, $dbError = '', $query = '') {

		// Only provide detailed output in debug mode
		echo '<p class="db_error">';
		if($this->_debug) {
			echo 'Query error in context "' . $error . '":<br /><strong>'
					. $dbError . 
					'</strong><br /><br />';
			if(!empty($query))
				echo '<em>' . $query . '</em>';
		} else {
			echo 'A database error occurred when performing the last operation. The system administrator has been informed.';
			if($this->_adminEmail)
				mail($this->_adminEmail, 'DBLIB -> DB Error (' . $error . ')', 'A database error occurred in context "' . $error . '".' . "\n\n" . $dbError . "\n\n" . 'Query: ' . $query);
		}
		echo '</p>';
		if($this->_exitOnError)
			exit;
	}
	
	/**
	 * Prepare a variable to be used in a database query
	 *
	 * @param mixed	$var Any variable
	 * @return mixed Variable post-processing
	 */
	protected function preDB($var) {
		
		// Make sure any false variables are returned as passed and the same with nulls
		if($var === false)
			return false;
		elseif($var === null || $var == 'NULL')
			return 'NULL';
		
		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($var)) {
			$newArray = array();
			foreach($var as $key => $value)
				if($this->_stripEnabled)
					$newArray[mysql_real_escape_string(html_entity_decode($key))] = self::preDB($value);
				else
					$newArray[html_entity_decode($key)] = self::preDB($value);
			return $newArray;
		} else {
			if($this->_stripEnabled)
				return "'" . mysql_real_escape_string(html_entity_decode($var)) . "'";
			else
				return "'" . html_entity_decode($var) . "'";
		}
	}

	/**
	 * Prepare a variable result from a database to be used 
	 *
	 * @param mixed $var Any variable
	 * @return mixed Variable post-processing
	 */
	protected function postDB($var) {
		
		// Make sure any false and null variables are returned as passed
		if($var === false)
			return false;
		elseif($var === null || $var == 'NULL')
			return null;

		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($var)) {
			$newArray = array();
			foreach($var as $key => $value)
				$newArray[htmlentities(stripslashes($key))] = self::postDB($value);
			return $newArray;
		} else
			return htmlentities(stripslashes($var));
	}
	
	/**
	 * Build a SELECT string for a query using one or more fields
	 *
	 * @param mixed $fields Fields to use
	 * @return string MySQL formatted string
	 */
	protected function buildSelectString($fields) {
		
		// Every field needs to be enclosed in ` characters
		if(is_array($fields)) {
			foreach($fields as $key => $field)
				$fields[$key] = preg_replace('/(\w+)/i', '`$1`', $field);
			return str_replace('`AS`', 'AS', join(', ', $fields));
		} else
			return str_replace('`AS`', 'AS', preg_replace('/(\w+)/i', '`$1`', $fields));
	}
	
	/**
	 * Build a FROM string for a query using one or more tables
	 *
	 * @param mixed $tables Tables to use
	 * @return string MySQL formatted string
	 */
	protected function buildFromString($tables) {
		
		// Every table name, with alias, needs to be enclosed in ` characters
		if(is_array($tables)) {
			foreach($tables as $key => $table)
				$tables[$key] = preg_replace('/(\w+)/i', '`$1`', $table);
			return '(' . join(', ', $tables) . ')';
		} else
		 	return '(' . preg_replace('/(\w+)/i', '`$1`', $tables) . ')';
	}
	
	/**
	 * Build a JOIN string for a query using one or more joins
	 * 
	 * @param array	$joins Joins to perform (as passed from other functions)
	 * @return string MySQL formatted string
	 */
	protected function buildJoinString($joins) {
		$string = '';
		foreach($joins as $join) {
			// Build the join string each time by enclosing fields and tables in ` characters
			$string .= "\n"
					. strtoupper($join['type'])
					. ' JOIN '
					. preg_replace('/(\w+)/i', '`$1`', $join['table'])
					. ' ON '
					. preg_replace('/(\w+)/i', '`$1`', $join['local'])
					. ' ' . (isset($join['condition']) ? $join['condition'] : '=') . ' '
					. preg_replace('/(\w+)/i', '`$1`', $join['foreign']);
		}
		
		// Return the formatted string
		return $string;
	}
	
	/**
	 * Build a WHERE/GROUP/ORDER/LIMIT string using the given MySQL command string
	 * and a set of values that are to be used in replacements with ?
	 *
	 * @param string $opt Raw MySQL string
	 * @param mixed	$optValues Array of values or string to use in replacements
	 * @return string Correctly formatted and escaped string
	 */
	protected function buildOptString($opt, $optValues) {
		
		// Go through each match and replace the ? if a value exists
		if(is_array($optValues)) {

			// Get matches
			$numMatches = preg_match_all('/([^\\\]\?)/i', $opt, $matches);
			
			// Replace all matches found
			for($i = 0; $i < $numMatches; $i++) {
				if(isset($optValues[$i]))
					$opt = preg_replace('/([^\\\])\?/i', "$1" . $this->preDB($optValues[$i]), $opt, 1);
			}
		} else
			$opt = preg_replace('/([^\\\])\?/i', "$1" . $this->preDB($optValues), $opt, 1);
		
		// Return the finished string
		return $opt;
	}
	

}