<?php
	
	define('REMOTE', 'REMOTE');
	define('LOCAL', 'LOCAL');
	define('ENV',REMOTE);
	
	define('CBDB_PUB_URL', 'http://www.comicbookdb.com/title_edit.php?ID=');
	
	// gets intput file from the following URL
	//
	// http://www.comicbookdb.com/title_edit.php?ID=25730
	//
	// finds the select tag with the name of form_pubID
	// then parse all the options tag from the select tag.
	//
	// the value of the option tag is the publisher ID and the
	// options text is the publishers name
	//
	// 4/18/2011 -- In this most recent update added support to get the
	// 	input file using curl instead of manually saving it to the local
	// 	machine. This required a user id to authenticate to the cbdb
	// 	web site. Also added support for updating existing cbdb_publishers
	// 	records and renaming if one publisher was being replaces by
	// 	another publisher of the same name. Finally, added support
	// 	for marking records as logically deleted if the record
	// 	was not updated in the most recent execution of the program
	
	if (ENV == REMOTE) {
		define('ROOT', $_ENV['HOME'] . '/indexwizard');
		$SVOR = 'indexwizard.comicbookupcdb.com';
		$CFOR = ROOT . '/cbudb_config.json';
	} else {
		define('ROOT', '/Applications/MAMP/workflow');
		$SVOR = 'workflow';
		$CFOR = ROOT . '/cbudb_config.json';
	}
	
	require_once(ROOT . "/includes/config_inc.php");
	require_once(ROOT . "/includes/database_inc.php");
	require_once(ROOT . "/includes/appl_init_inc.php");
	
	$ch = curl_init();
	
	try {
		$html = get_html($ch, CBDB_PUB_URL, 25730);
	} catch (Exception $e) {
		print 'err_code ' . $e->getCode() . ' err_msg ' . $e->getMessage() . "\n";
	}
	
	// $html = file_get_contents('title_edit.php.html');
	$html = html_entity_decode(preg_replace('/(\n|\r)/', '', $html), ENT_QUOTES);
	$html = iconv("ISO-8859-1", "UTF-8", $html);
	
	// considering adding support to parse out the imprints from CBDB too... ?
	//
	// preg_match('/<select name="form_imprintID">(.*)<select name="form_begindate_month">/', $html, &$matches);
	// <option value="691" selected>Aztech Toys</option>
	
	preg_match('/<select name="form_pubID">(.*)<select name="form_imprintID">/', $html, &$matches);
	$select_html = $matches[1];

	//		<option value="1403">Limestone Press</option>
	
	preg_match_all('/<option value="(\d+)">(.*?)<\/option>/', $select_html, &$matches, PREG_SET_ORDER);
	$pub_recs = $matches;
	
	$sql = "select now() as now_dt";
	$result1 = mysql_query($sql);
	if (!$result1) {
		print 'ERROR on current date-time select: ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
		exit;
	}
	$row1 = mysql_fetch_assoc($result1);
	$now = $row1['now_dt'];
	
	print "\n\ncurrent data time: $now\n\n";

	$len = 0;
	$count = 0;
	foreach($pub_recs as $pub_rec) {
		$pub_name = (strlen($pub_rec[2]) > 0 ? $pub_rec[2] : "blank pub name $count");
		$len = (strlen($pub_name) > $len ? strlen($pub_name) : $len);
		
		$sql = sprintf("select cp_id, cpub_name from cbdb_publishers where cpub_id = %s", dbv($pub_rec[1]));
		$result2 = mysql_query($sql);
		if (!$result2) {
			print 'ERROR on cbdb_publisher select: ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
			exit;
		}
		$row2 = mysql_fetch_assoc($result2);
		$cp_id = $row2['cp_id'];
		$cpub_name = $row2['cpub_name'];
		
		if ($row2 === false) {
			$sql = sprintf("insert into cbdb_publishers (cpub_id, cpub_name, crt_dt, crt_usr_id, updt_dt, updt_usr_id) values(%s, %s, %s, 2, %s, 2)", dbv($pub_rec[1]), dbv($pub_name), dbv($now), dbv($now));
			$result3 = mysql_query($sql);
			
			if (!$result3) {
				
				if (mysql_errno() != 1062) {
					print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## INSERT ##\n";
					print 'ERROR: on cbdb_pub insert ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
					exit;
				} else {
					// rename [update the cpub_name of] the existing record
					$sql2 = sprintf("update cbdb_publishers set cpub_name = concat(cpub_name, ' [DUP: %s - %s]'), updt_dt = updt_dt where cpub_name = %s", dbv($pub_rec[1]), $now, dbv($pub_name));
					$result3 = mysql_query($sql2);
					if (!$result3) {
						print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## RENAME ##\n";
						print 'ERROR: on cbdb_pub rename [update] ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
						exit;
					}
					print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## RENAME ##\n";
					
					// redo insert
					$result3 = mysql_query($sql);
					if (!$result3) {
						print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## INSERT [2x] ##\n";
						print 'ERROR: on cbdb_pub insert ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
						exit;
					}
				}
			}
			print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## INSERT " . mysql_insert_id() . " ##\n";
		} else {
			$sql = sprintf("update cbdb_publishers set cpub_name = %s, updt_dt = %s, updt_usr_id = 2 where cp_id = %s", dbv($pub_name), dbv($now), $cp_id);
			$result3 = mysql_query($sql);
			
			if (!$result3) {
			
				$sql_err = mysql_error();
				if ($sql_err != 1062) {
					print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## UPDATE $cp_id ##\n";
					print 'ERROR: on cbdb_pub insert ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
					exit;
				} else {
					// rename [update the cpub_name of] the existing record
					$sql2 = sprintf("update cbdb_publishers set cpub_name = concat(cpub_name, ' [DUP: %s - %s]'), updt_dt = updt_dt where cpub_name = %s", $pub_rec[1], $now, dbv($pub_name));
					$result3 = mysql_query($sql2);
					if (!$result3) {
						print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## RENAME ##\n";
						print 'ERROR: on cbdb_pub rename [update] ' . mysql_errno() . ': ' . mysql_error() . "\n$sql2\n\n";
						exit;
					}
					print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## RENAME ##\n";
					
					// redo update
					$result3 = mysql_query($sql);
					if (!$result3) {
						print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## UPDATE [2x] $cp_id ##\n";
						print 'ERROR: on cbdb_pub insert ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
						exit;
					}
				}
			}
			print "Pub ID: {$pub_rec[1]}\tPub Name: $pub_name\t\t\t## UPDATE $cp_id ##\n";
		}
		$count++;
	}
	
	$sql = sprintf("update cbdb_publishers set del_ind = 'Y', updt_dt = updt_dt where updt_dt <> %s", dbv($now));
	$result2 = mysql_query($sql);
	if (!$result2) {
		print 'ERROR: on cbdb_publisher mark deleted [insert] ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n\n";
	}
	$logical_deleted = mysql_affected_rows();
	
	print "\n\ncount: $count\n\nmax len: $len\nlogically deleted: $logical_deleted\n\n";
	
function dbv($val) {
	if (!strlen($val)) {
		return "NULL";
	}
	$escd_val = mysql_real_escape_string($val);
	return (is_numeric($val) ? $escd_val : "'$escd_val'");
}



function get_html() {
	
	$ch = curl_init();

	$user = 'skinner';
	$pw = 'bartfart';
	
	// Set general curl options for all request
	//
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-us) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, 'publisher_cookiejar.txt');
//	curl_setopt($ch, CURLOPT_COOKIESESSION, true);
//	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	// make sure we are logged out before begining
	//
	$tmpfns = tempnam(sys_get_temp_dir(), "http");
	$pfh = fopen($tmpfns, 'w+');
	
	$url = 'http://www.comicbookdb.com/logout.php';
	
	curl_setopt($ch, CURLOPT_WRITEHEADER, $pfh);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_URL, $url);

	curl_exec($ch);
	
	fseek($pfh, 0);
	$headers = explode("\n", fread($pfh, filesize($tmpfns)));
	fclose($pfh);
	unlink($tmpfns);

	if (trim($headers[0]) != 'HTTP/1.1 302 Moved Temporarily') {
		throw new exception("Error calling cbdb logout: $url {$headers[0]}", 1001);
	}
	
	// login to the cbdb with user id and password
	//
	$tmpfns = tempnam(sys_get_temp_dir(), "http");
	$pfh = fopen($tmpfns, 'w+');
	
	$post_string = "form_username=$user&form_password=$pw&submit=Log+In";
	$url = 'http://www.comicbookdb.com/login.php';
	
	curl_setopt($ch, CURLOPT_WRITEHEADER, $pfh);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);

	curl_exec($ch);
	
	fseek($pfh, 0);
	$headers = explode("\n", fread($pfh, filesize($tmpfns)));
	fclose($pfh);
	unlink($tmpfns);

	if (trim($headers[0]) != 'HTTP/1.1 302 Moved Temporarily') {
		throw new exception("Error calling cbdb login: $url {$headers[0]}", 1002);
	}
	
	// get the title_edit.php file which has the form_pubID <select> tag and the form_imprintID <select> tag
	//
	$tmpfns = tempnam(sys_get_temp_dir(), "http");
	$pfh = fopen($tmpfns, 'w+');
	
	$url = 'http://www.comicbookdb.com/title_edit.php?ID=25730';
	
	curl_setopt($ch, CURLOPT_WRITEHEADER, $pfh);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	
	$html = curl_exec($ch);
	$html = preg_replace('/(\n|\r)/', '', $html);
	
	fseek($pfh, 0);
	$headers = explode("\n", fread($pfh, filesize($tmpfns)));
	fclose($pfh);
	unlink($tmpfns);

	if (trim($headers[0]) != 'HTTP/1.1 200 OK') {
		throw new exception("Error geting the publishers & imprints: $url {$headers[0]}", 1003);
	}
	
	return $html;
}

?>