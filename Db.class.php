<?php

/**
 * Superclass for all database classes, provides common functionality
 * 
 * @package dblib
 * @author Jamie Hurst
 * @version 1.3
 */

require_once 'iDb.interface.php';

/**
 * Common DB class
 */
abstract class Db implements iDb {
    
    // Config file definition, normally this shouldn't need to be changed
	const CONFIG_FILE = 'config.ini';
	
	// Version constants
	const VERSION_MAJOR = 1;
	const VERSION_MINOR = 2;
	const VERSION_REVISION = 3;
	
	// Default configuration options that can be overwritten in config.ini (NOT HERE!)
	private $_config = array(
		'stripEnabled'		=>	true,
		'debug'				=>	false,
		'autoClose'			=>	false,
		'caching'			=>	true,
		'exitOnError'		=>	true,
		'getQueries'		=>	false,
		'adminEmail'		=>	false,
		'tableSeparator'	=>	'|'
	);
	
	// Define a quick path variable that will be resolved later
	private $_path = '.';
	
	// New singleton instance for getInstance() calls
	protected static $_instance = null;
	
	/**
	 * Constructor
	 */
	protected function __construct() {
	    // Resolve the path of this DBlib installation
		$includes = get_included_files();
		$basepath = realpath('./');
		foreach ($includes as $include) {
		    if (strpos($include, 'Db.class.php')) {
		        $basepath = realpath(dirname($include));
		    }
		}
		$this->_path = $basepath;
		unset($includes, $include, $basepath);

		// Check the configuration file exists
		if (!file_exists($this->_path . '/' . self::CONFIG_FILE)) {
			// Throw a warning
			trigger_error('Could not open configuration file for DBlib, using default options.', E_USER_WARNING);
		} else {
			// Parse config file
			$config = parse_ini_file($this->_path . '/' . self::CONFIG_FILE);
			
			// Merge the custom configuration with the default
			$this->_config = array_merge($this->_config, $config);
		}
		
		// Check for magic quotes (This needs to be removed at some point, probably causes more problems than it solves!)
		if (get_magic_quotes_gpc()) {
			$this->_stripEnabled = false;
		}
	}
	
	/**
	 * Set a configuration option
	 *
	 * @param string $key Configuration option
	 * @param mixed $value Option value
	 * @return Db Object for chaining
	 * @since 1.3
	 */
	public function setConfig($key, $value) {
		$this->_config[$key] = $value;
		return $this;
	}
	
	/**
	 * Get configuration option
	 *
	 * @param string $key Config option key
	 * @return mixed Configuration setting
	 * @since 1.3
	 */
	public function getConfig($key) {
		return $this->_config[$key];
	}
	
	/**
	 * Set the value of strip enabled
	 *
	 * @param mixed $value Value to set
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setStripEnabled($value) {
		return $this->setConfig('stripEnabled', $value);
	}
	
	/**
	 * Get strip enabled value
	 *
	 * @return mixed strip enabled value
	 * @deprecated Use getConfig() instead
	 */
	public function getStripEnabled() {
		return $this->getConfig('stripEnabled');
	}
		
	/**
	 * Set the value of debug
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setDebug($value) {
		return $this->setConfig('debug', $value);
	}
	
	/**
	 * Get debug value
	 *
	 * @return boolean Debug value
	 * @deprecated Use getConfig() instead
	 */
	public function getDebug() {
		return $this->getConfig('debug');
	}
		
	/**
	 * Set the value of auto close
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setAutoClose($value) {
		return $this->setConfig('autoClose', $value);
	}
	
	/**
	 * Get auto close value
	 *
	 * @return boolean Auto close value
	 * @deprecated Use getConfig() instead
	 */
	public function getAutoClose() {
		return $this->getConfig('autoClose');
	}
		
	/**
	 * Set the value of caching
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setCaching($value) {
		return $this->setConfig('caching', $value);
	}
	
	/**
	 * Get caching value
	 *
	 * @return boolean Caching value
	 * @deprecated Use getConfig() instead
	 */
	public function getCaching() {
		return $this->getConfig('caching');
	}
		
	/**
	 * Set the value of exit on error
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setExitOnError($value) {
		return $this->setConfig('exitOnError', $value);
	}
	
	/**
	 * Get exit on error value
	 *
	 * @return boolean Exit on error value
	 * @deprecated Use getConfig() instead
	 */
	public function getExitOnError() {
		return $this->getConfig('exitOnError');
	}
		
	/**
	 * Set the value of get queries
	 *
	 * @param boolean $value Value to set
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setGetQueries($value) {
		return $this->setConfig('getQueries', $value);
	}
	
	/**
	 * Get get queries value
	 *
	 * @return boolean Get queries value
	 * @deprecated Use getConfig() instead
	 */
	public function getGetQueries() {
		return $this->getConfig('getQueries');
	}
		
	/**
	 * Set the value of admin email
	 *
	 * @param mixed $value Value to set
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setAdminEmail($value) {
		return $this->setConfig('adminEmail', $value);
	}
	
	/**
	 * Get admin email value
	 *
	 * @return mixed Admin email value
	 * @deprecated Use getConfig() instead
	 */
	public function getAdminEmail() {
		return $this->getConfig('adminEmail');
	}
	
	/**
	 * Set the table separator
	 *
	 * @param string $sep Table separator
	 * @return Db For chaining
	 * @deprecated Use setConfig() instead
	 */
	public function setTableSeparator($sep) {
		return $this->setConfig('tableSeparator', $value);
	}
	
	/**
	 * Get the table separator
	 *
	 * @return string Table separator
	 * @since 1.2
	 * @deprecated Use getConfig() instead
	 */
	public function getTableSeparator() {
		return $this->getConfig('tableSeparator');
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
		if ($this->getConfig('debug')) {
			echo 'Query error in context "' . $error . '":<br /><strong>'
					. $dbError . 
					'</strong><br /><br />';
			if(!empty($query)) {
				echo '<em>' . $query . '</em>';
			}
		} else {
			echo 'A database error occurred when performing the last operation. The system administrator has been informed.';
			if($this->getConfig('adminEmail')) {
				mail($this->_adminEmail, 'DBlib -> DB Error (' . $error . ')', 'A database error occurred in context "' . $error . '".' . "\n\n" . $dbError . "\n\n" . 'Query: ' . $query);
			}
		}
		echo '</p>';
		if ($this->getConfig('exitOnError')) {
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
		if (is_null($var) || $var === 'NULL') {
			return 'NULL';
		}
		
		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if (is_array($var)) {
			$newArray = array();
			foreach ($var as $key => $value) {
				if ($this->getConfig('stripEnabled') && strpos($value, "\'") === false) {
					$newArray[$this->escape(html_entity_decode($key))] = $this->preDb($value);
				} else {
					$newArray[html_entity_decode($key)] = $this->preDb($value);
				}
			}
			return $newArray;
		} else {
			if($this->getConfig('stripEnabled')) {
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
		if ($var === false) {
			return false;
		}

		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if (is_array($var)) {
			$newArray = array();
			foreach ($var as $key => $value) {
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
		if (is_array($vars)) {
			// Get matches
			$numMatches = preg_match_all('/((?<!\\\)\?)/', $stmt, $matches);
			
			// Throw an error if the matches aren't the same
			if ($numMatches > count($vars)) {
				$this->errorDb('escape_count');
			}
			
			// Replace all matches found
			for ($i = 0; $i < $numMatches; $i++) {
				if (isset($vars[$i])) {
					$stmt = preg_replace('/((?<!\\\)\?)/', $this->preDb($vars[$i]), $stmt, 1);
				}
			}
		} else {
			$stmt = preg_replace('/((?<!\\\)\?)/', $this->preDb($vars), $stmt, 1);
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
		if (is_array($fields)) {
			foreach ($fields as $key => $field) {
				$fields[$key] = $this->prepareFields($field);
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
		if (is_array($tables)) {
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
		foreach ($joins as $join) {
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