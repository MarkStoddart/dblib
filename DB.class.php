<?php

/**
 * Superclass for all database classes, provides common functionality
 * 
 * @author Jamie Hurst
 * @version 1.0
 */

/**
 * Common DB class
 */
abstract class DB {
	
	/**
	 * Constructor
	 */
	protected function __construct() {
		
	}
	
	/**
	 * Handle any database errors
	 *
	 * @param	string	$p_error	Error context
	 * @param	string	$p_db_error	Error given from DB
	 * @param	string	$p_query	[Optional] Query from where the error happened
	 */
	protected function errorDB($p_error, $p_db_error = '', $p_query = '') {

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
	 * Prepare a variable to be used in a database query
	 *
	 * @param	mixed	$p_var	Any variable
	 * @return	mixed	Variable post-processing
	 */
	protected function preDB($p_var) {
		
		// Make sure any false variables are returned as passed
		if(!$p_var)
			return false;
		
		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($p_var)) {
			$new_array = array();
			foreach($p_var as $key => $value)
				$new_array[addslashes(htmlspecialchars_decode($key))] = self::preDB($value);
			return $new_array;
		} else
			return addslashes(htmlspecialchars_decode($p_var));
	}

	/**
	 * Prepare a variable result from a database to be used 
	 *
	 * @param	mixed	$p_var	Any variable
	 * @return	mixed	Variable post-processing
	 */
	protected function postDB($p_var) {
		
		// Make sure any false variables are returned as passed
		if(!$p_var)
			return false;

		// Use a recursive call if the variable is an array, to make sure it
		// is penetrated to the correct depth
		if(is_array($p_var)) {
			$new_array = array();
			foreach($p_var as $key => $value)
				$new_array[htmlspecialchars(stripslashes($key))] = self::postDB($value);
			return $new_array;
		} else
			return htmlspecialchars(stripslashes($p_var));
	}
	
	/**
	 * Build a SELECT string for a query using one or more fields
	 *
	 * @param	mixed	$p_fields	Fields to use
	 * @return	string	MySQL formatted string
	 */
	protected function buildSelectString($p_fields) {
		
		// Every field needs to be enclosed in ` characters
		if(is_array($p_fields)) {
			foreach($p_fields as $key => $field)
				$p_fields[$key] = preg_replace('/(\w+)/i', '`$1`', $field);
			$string = join(', ', $p_fields);
		} else
			$string = preg_replace('/(\w+)/i', '`$1`', $p_fields);
		
		// Return the correctly formatted string
		return $string;
	}
	
	/**
	 * Build a FROM string for a query using one or more tables
	 *
	 * @param	mixed	$p_tables	Tables to use
	 * @return	string	MySQL formatted string
	 */
	protected function buildFromString($p_tables) {
		
		// Every table name, with alias, needs to be enclosed in ` characters
		if(is_array($p_tables)) {
			foreach($p_tables as $key => $table)
				$p_tables[$key] = preg_replace('/(\w+)/i', '`$1`', $table);
			$string = join(', ', $p_tables);
		} else
			$string = preg_replace('/(\w+)/i', '`$1`', $p_tables);
		
		// Return the tables in brackets so joins can be made
		return '(' . $string . ')';
	}
	
	/**
	 * Build a JOIN string for a query using one or more joins
	 * 
	 * @param	array	$p_joins	Joins to perform (as passed from other functions)
	 * @return	string	MySQL formatted string
	 */
	protected function buildJoinString($p_joins) {
		$string = '';
		foreach($p_joins as $join) {
			// Build the join string each time by enclosing fields and tables in ` characters
			$string .= "\n"
					. strtoupper($join['type'])
					. ' JOIN '
					. preg_replace('/(\w+)/i', '`$1`', $join['table'])
					. ' ON '
					. preg_replace('/(\w+)/i', '`$1`', $join['local'])
					. ' = '
					. preg_replace('/(\w+)/i', '`$1`', $join['foreign']);
		}
		
		// Return the formatted string
		return $string;
	}
	
	/**
	 * Build a WHERE/GROUP/ORDER/LIMIT string using the given MySQL command string
	 * and a set of values that are to be used in replacements with ?
	 *
	 * @param string $p_opt Raw MySQL string
	 * @param array $p_opt_values Array of values to use in replacements
	 * @return string Correctly formatted and escaped string
	 */
	protected function buildOptString($p_opt, $p_opt_values) {
		
		// Get matches
		preg_match_all('/([^\\\]\?)/i', $p_opt, $matches);
		
		// Go through each match and replace the ? if a value exists
		foreach($matches as $id => $match) {
			if(isset($p_opt_values[$id]))
				$p_opt = preg_replace('/([^\\\])\?/i', "$1'" . $this->preDB($p_opt_values[$id]) . "'", $p_opt, 1);
		}
		
		// Return the finished string
		return $p_opt;
	}
	

}