<?php

/**
 * Standard database class using traditional PHP MySQL functions
 * 
 * @author Jamie Hurst
 * @version 1.0
 */

require 'iDB.interface.php';

/**
 * MySQL class
 */
public class mysqlDB implements iDB {
	
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
	}
	
	/**
	 * Destructor
	 * Close the database
	 */
	public function __destruct() {
		$this->closeDB();
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
	
	// TODO Do the rest of it
}