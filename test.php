<?php

class TestClass {
	
	private $_name;

	public function __get($name) {
		$method = 'get' . $name;
		if($name == 'mapper' || !method_exists($this, $method))
			throw new Exception('Invalid property!');
		return $this->$method();
	}
	
	public function setName($name) {
		$this->_name = $name;
		return $name;
	}
	
	public function getName() {
		return $this->_name;
	}
	
}

$test = new TestClass();
$test->setName('foo');
echo $test->name . "\n";

if(empty($test->name))
	echo 'Why is this property empty???';
else
	echo 'I guess I was wrong!';