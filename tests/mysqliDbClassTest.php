<?php

require_once '../mysqliDb.class.php';
require_once 'PHPUnit/Framework.php';

class mysqliDbTest extends PHPUnit_Framework_TestCase {
	
	protected $db;
	
	public function setUp() {
		// Establish connection and create testing table
		$init = mysql_connect('localhost', 'root', 'ga6-bxd');
		mysql_select_db('test', $init);
		mysql_query("
			DROP TABLE testing
		", $init);
		mysql_query("
			DROP TABLE testing2
		", $init);
		mysql_query("
			CREATE TABLE testing (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `subject` varchar(255) DEFAULT NULL,
			  `content` text NOT NULL,
			  PRIMARY KEY (id)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		", $init);
		mysql_query("
			CREATE TABLE testing2 (
			  `subject2` varchar(255) DEFAULT NULL,
			  `content2` text NOT NULL,
			  PRIMARY KEY (subject2)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		", $init);
		mysql_close($init);
		
		$this->db = new mysqliDb();

		$this->db->setDebug(true);
		$this->db->setExitOnError(false);
	}

	public function testVariables() {
		$this->assertTrue($this->db->getDebug());
		$this->assertFalse($this->db->getExitOnError());
	}
	
	public function testConnect() {
		$connect = $this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->assertTrue($this->db->isConnected());
	}
	
	public function testClose() {
		$connect = $this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->closeDb();
		$this->assertFalse($this->db->isConnected());
	}
	
	public function testInsertRow() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$insert = $this->db->insertRow('testing', array(
			'subject'	=>	'TEST1',
			'content'	=>	'Test One'
		));
		$this->assertTrue($insert);
		$this->assertEquals($this->db->getQueryCount(), 1);
	}
	
	public function testReplaceRow() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$insert = $this->db->insertRow('testing', array(
			'id'		=>	1,
			'subject'	=>	'TEST1',
			'content'	=>	'Test One'
		));
		$this->assertTrue($insert);
		$replace = $this->db->replaceRow('testing', array(
			'id'		=>	1,
			'subject'	=>	'TEST1.1',
			'content'	=>	'Test One Point One'
		));
		$this->assertTrue($replace);
		$this->assertEquals($this->db->getQueryCount(), 2);
	}
	
	public function testGetField() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST2',
			'content'	=>	'Test Two'
		));
		$field = $this->db->getField('content', 'testing', 'WHERE `subject` = ?', 'TEST2');
		$this->assertEquals($field, 'Test Two');
		$this->assertEquals($this->db->getQueryCount(), 2);
	}
	
	public function testGetFields() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST2',
			'content'	=>	'Test Two'
		));
		$field = $this->db->getFields(array('subject', 'content'), 'testing', 'ORDER BY `id` DESC');
		$this->assertEquals($field['subject'], 'TEST2');
		$this->assertEquals($field['content'], 'Test Two');
		$this->assertEquals($this->db->getQueryCount(), 2);
	}

	public function testGetRow() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST3',
			'content'	=>	'Test Three'
		));
		$row = $this->db->getRow('testing', 'WHERE `subject` = ?', 'TEST3');
		$this->assertEquals($row['content'], 'Test Three');
		$this->assertEquals($this->db->getQueryCount(), 2);
	}

	public function testGetRows() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST_MULTIPLE',
			'content'	=>	'Test Four'
		));
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST_MULTIPLE',
			'content'	=>	'Test Five'
		));
		$rows = $this->db->getRows('testing', 'WHERE `subject` = ?', 'TEST_MULTIPLE');
		$this->assertEquals($rows[0]['content'], 'Test Four');
		$this->assertEquals($rows[1]['content'], 'Test Five');
		$this->assertEquals($this->db->getQueryCount(), 3);
	}

	public function testGetNumRows() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST_MULTIPLE',
			'content'	=>	'Test Four'
		));
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST_MULTIPLE',
			'content'	=>	'Test Five'
		));
		$rows = $this->db->getNumRows('testing', 'WHERE `subject` = ?', 'TEST_MULTIPLE');
		$this->assertEquals($rows, 2);
		$this->assertEquals($this->db->getQueryCount(), 3);
	}

	public function testUpdateRow() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$insert = $this->db->insertRow('testing', array(
			'subject'	=>	'TEST6',
			'content'	=>	'Testing'
		));
		$update = $this->db->updateRows('testing', array('content' => 'Test Six'), 'WHERE `subject` = ?', 'TEST6');
		$this->assertTrue($update);
		$this->assertEquals($this->db->getQueryCount(), 2);
	}

	public function testDeleteRow() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$insert = $this->db->insertRow('testing', array(
			'subject'	=>	'TEST7',
			'content'	=>	'Test Seven'
		));
		$delete = $this->db->deleteRows('testing', 'WHERE `subject` = ?', 'TEST7');
		$this->assertTrue($delete);
		$this->assertEquals($this->db->getQueryCount(), 2);
	}
	
	public function testGetJoinedField() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST8',
			'content'	=>	'Test Eight'
		));
		$this->db->insertRow('testing2', array(
			'subject2'	=>	'TEST8',
			'content2'	=>	'Test Eight Point One'
		));
		$field = $this->db->getJoinedFields(
			'content2',
			'testing t1',
			array(
				array(
					'type'		=>	'LEFT',
					'table'		=>	'testing2 t2',
					'local'		=>	't1.subject',
					'foreign'	=>	't2.subject2'
				)
			),
			'WHERE `t1`.`subject` = ?',
			'TEST8'
		);
		$this->assertEquals($field, 'Test Eight Point One');
		$this->assertEquals($this->db->getQueryCount(), 3);
	}

	public function testGetJoinedFields() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST9',
			'content'	=>	'Test Nine'
		));
		$this->db->insertRow('testing2', array(
			'subject2'	=>	'TEST9',
			'content2'	=>	'Test Nine Point One'
		));
		$fields = $this->db->getJoinedFields(
			array('t2.content2', 't1.content'),
			'testing t1',
			array(
				array(
					'type'		=>	'LEFT',
					'table'		=>	'testing2 t2',
					'local'		=>	't1.subject',
					'foreign'	=>	't2.subject2'
				)
			),
			'WHERE `t1`.`subject` = ?',
			'TEST9'
		);
		$this->assertEquals($fields['content'], 'Test Nine');
		$this->assertEquals($fields['content2'], 'Test Nine Point One');
		$this->assertEquals($this->db->getQueryCount(), 3);
	}

	public function testGetJoinedRow() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST10',
			'content'	=>	'Test Ten'
		));
		$this->db->insertRow('testing2', array(
			'subject2'	=>	'TEST10',
			'content2'	=>	'Test Ten Point One'
		));
		$row = $this->db->getJoinedRow(
			'testing t1',
			array(
				array(
					'type'		=>	'LEFT',
					'table'		=>	'testing2 t2',
					'local'		=>	't1.subject',
					'foreign'	=>	't2.subject2'
				)
			),
			'WHERE `t1`.`subject` = ?',
			'TEST10'
		);
		$this->assertEquals($row['content'], 'Test Ten');
		$this->assertEquals($row['content2'], 'Test Ten Point One');
		$this->assertEquals($this->db->getQueryCount(), 3);
	}

	public function testGetJoinedRows() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST11',
			'content'	=>	'Test Eleven'
		));
		$this->db->insertRow('testing2', array(
			'subject2'	=>	'TEST11',
			'content2'	=>	'Test Eleven Point One'
		));
		$rows = $this->db->getJoinedRows(
			'testing t1',
			array(
				array(
					'type'		=>	'LEFT',
					'table'		=>	'testing2 t2',
					'local'		=>	't1.subject',
					'foreign'	=>	't2.subject2'
				)
			),
			'WHERE `t1`.`subject` = ?',
			'TEST11'
		);
		$this->assertEquals($rows[0]['content'], 'Test Eleven');
		$this->assertEquals($rows[0]['content2'], 'Test Eleven Point One');
		$this->assertEquals($this->db->getQueryCount(), 3);
	}

	public function testGetJoinedRowsOfFields() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST11',
			'content'	=>	'Test Eleven'
		));
		$this->db->insertRow('testing2', array(
			'subject2'	=>	'TEST11',
			'content2'	=>	'Test Eleven Point One'
		));
		$rows = $this->db->getJoinedRowsOfFields(
			array('t1.content AS c', 't2.content2 AS c2'),
			'testing t1',
			array(
				array(
					'type'		=>	'LEFT',
					'table'		=>	'testing2 t2',
					'local'		=>	't1.subject',
					'foreign'	=>	't2.subject2'
				)
			),
			'WHERE `t1`.`subject` = ?',
			'TEST11'
		);
		$this->assertEquals($rows[0]['c'], 'Test Eleven');
		$this->assertEquals($rows[0]['c2'], 'Test Eleven Point One');
		$this->assertEquals($this->db->getQueryCount(), 3);
	}
	
	public function testGetNumJoinedRows() {
		$this->db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST12',
			'content'	=>	'Test Twelve'
		));
		$this->db->insertRow('testing2', array(
			'subject2'	=>	'TEST12',
			'content2'	=>	'Test Twelve Point One'
		));
		$rows = $this->db->getNumJoinedRows(
			'testing t1',
			array(
				array(
					'type'		=>	'LEFT',
					'table'		=>	'testing2 t2',
					'local'		=>	't1.subject',
					'foreign'	=>	't2.subject2'
				)
			),
			'WHERE `t1`.`subject` = ?',
			'TEST12'
		);
		$this->assertEquals($rows, 1);
		$this->assertEquals($this->db->getQueryCount(), 3);
	}
	
	public function testGetFieldsFromTable() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$fields = $this->db->getFieldsFromTable('testing');
		$this->assertEquals($fields[0], 'id');
		$this->assertEquals($fields[1], 'subject');
		$this->assertEquals($fields[2], 'content');
		$this->assertEquals($this->db->getQueryCount(), 1);
	}

	public function testRawQuery() {
		$this->db->connectDb('localhost', 'root', 'ga6-bxd', 'test');
		$this->db->insertRow('testing', array(
			'subject'	=>	'TEST13',
			'content'	=>	'Test THIRTEEN'
		));
		$result = $this->db->rawQuery('SELECT * FROM testing WHERE subject = ?', 'TEST13');
		$this->assertEquals($result[0]['content'], 'Test THIRTEEN');
		$this->assertEquals($this->db->getQueryCount(), 2);
	}

}