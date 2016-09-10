<?php
	print "\n----------------------\n/include/inc1.php\n";

	print "__FILE__ " . __FILE__ . "\n";
	print "HTTP_HOST " . $_SERVER['HTTP_HOST'] . "\n";
	print "PHP_SELF " . $_SERVER['PHP_SELF'] . "\n";
	print "dirname(PHP_SELF) " . dirname($_SERVER['PHP_SELF']) . "\n";
	print "DOCUMENT_ROOT " . $_SERVER['DOCUMENT_ROOT'] . "\n";
	print "SCRIPT_FILENAME " . $_SERVER['SCRIPT_FILENAME'] . "\n";
	print "SCRIPT_NAME " . $_SERVER['SCRIPT_NAME'] . "\n";
	print "REQUEST_URI " . $_SERVER['REQUEST_URI'] . "\n";

?>
