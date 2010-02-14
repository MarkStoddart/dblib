<?php

require_once '../mysqlDB.class.php';
require_once '../mysqliDB.class.php';
require_once 'PHPUnit/Framework.php';

class mysqlDBTest extends PHPUnit_Framework_TestCase {
	
	protected $db;
	
	public function setUp() {
		// Establish connection and create testing table
		$init = mysql_connect('localhost', 'root', 'ga6-bxd');
		mysql_select_db('test', $init);
		mysql_query("
			DROP TABLE IF EXISTS testing;
			CREATE TABLE testing (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `subject` varchar(255) DEFAULT NULL,
			  `content` text NOT NULL,
			  PRIMARY KEY (id)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		", $init);
		mysql_close($init);
		
		$this->db = new mysqlDB();

		$this->db->setDebug(true);
		//$this->assertTrue($db->getDebug());

		$this->db->setExitOnError(false);
		//$this->assertFalse($db->getExitOnError());
	}

	public function testVariables() {
		$this->assertTrue($this->db->getDebug());
		$this->assertFalse($this->db->getExitOnError());
	}
	
	public function testConnect() {
		$connect = $this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$this->assertTrue($connect);
	}
	
	public function testInsertRow() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$insert = $this->db->insertRow('testing', array(
				'subject'	=>	'TEST',
				'content'	=>	'Test One'
			));
		$this->assertTrue($insert);
	}
}