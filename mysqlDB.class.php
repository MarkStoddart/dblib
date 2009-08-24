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
	
	private var $DBobject;
	private var $DBhost;
	private var $DBuser;
	private var $DBpass;
	private var $DBname;
	private var $queryCount;
	
	/**
	 * Constructor
	 * Use the provided DB variable if set, otherwise connect
	 * using the connectDB() method
	 * 
	 * @param	mixed	$p_db	[Optional] MySQL connection link
	 */
	public function __construct($p_db = null) {
		if(is_null($p_db)) {
			// Connect to the database, using the connect method
			$this->DBobject = $this->connectDB();
		} else {
			// Leave it otherwise, even if the variable isn't a correct link
			$this->DBobject = $p_db;
		}
	}
	
	/**
	 * Destructor
	 * Close the database
	 */
	public function __destruct() {
		if($this->DBobject)
			mysql_close($this->DBobject);
	}
	
	// TODO Do the rest of it
}