<?php

print "\n\n";
$v = strtotime(trim(''));
if ($v === false) {
	print "strtotime() returned false\n";
	print date('Y-m-d', 100000);
} else {
	print date('Y-m-d', $v);
}
print "\n\n";

?>