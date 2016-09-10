<?php
$a = array(
'APR09',
'APR10',
'APR11',
'APR12',
'APR13',
'AUG09',
'AUG10',
'AUG11',
'AUG12',
'AUG14',
'DEC09',
'DEC10',
'DEC11',
'DEC12',
'FEB09',
'FEB10',
'FEB11',
'FEB12',
'FEB13',
'JAN09',
'JAN10',
'JAN11',
'JAN12',
'JAN13',
'JUL09',
'JUL10',
'JUL11',
'JUL12',
'JUN09',
'JUN10',
'JUN11',
'JUN12',
'MAR09',
'MAR10',
'MAR11',
'MAR12',
'MAR13',
'MAY09',
'MAY10',
'MAY11',
'MAY12',
'NOV09',
'NOV10',
'NOV11',
'NOV12',
'OCT09',
'OCT10',
'OCT11',
'OCT12',
'SEP09',
'SEP10',
'SEP11',
'SEP12');

$a[] = 'this-should-fail-badly';

print "\n\nThe count is: " . count($a) . "\n\n";

$d = array();
foreach ($a as $b) {
	$b2 = '01-' . substr($b, 0, 3) . '-20' . substr($b, 3, 2);
	$c = strtotime($b2);
	if ($c === false) {
		print "$b --> ERROR\n";
	} else {
		$f = date('Y-m-d', $c);
		$d[$f] = "$b --> $b2 --> $c --> $f";
	}
}
ksort($d);

foreach ($d as $g) {
	print "$g\n";
}

print "\n\n";

?>