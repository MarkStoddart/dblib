<?php

// Connect to database
require 'mysqliDB.class.php';
$db = new mysqliDB();
$db->connectDB('localhost', 'root', 'ga6-bxd', 'test');
$db->debug = true;

// Setup some data
$name = null;
$content = "Some test content that includes &pound; signs and &lt; &gt; and is still Testname's...";

?>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Testing</title>
</head>
<body>
	<?php
	
	$db->insertRow('testing', array('subject' => $name, 'content' => $content));
	
	$testrow = $db->getRow('testing', 'ORDER BY id DESC');
	
	echo $testrow['subject'] . ' - ' . $testrow['content'];
	
	?>
</body>
</html>