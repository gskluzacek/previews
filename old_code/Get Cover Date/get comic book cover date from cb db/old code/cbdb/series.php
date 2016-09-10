<?php
	
	define('REMOTE', 'REMOTE');
	define('LOCAL', 'LOCAL');
	define('ENV',REMOTE);
	
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
	
	$pub_read = 0;
	$pub_proc = 0;
	$cnt = 0;
	$ins = 0;
	$updt = 0;
		
	$sql = "select now() as now_dt";
	$result = mysql_query($sql);
	if (!$result) {
		print 'ERROR on current date-time select: ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n";
		exit;
	}
	$row = mysql_fetch_assoc($result);
	$now = $row['now_dt'];
	
	$src = (isset($argv[1]) ? $argv[1] : 'WEB');
	if ($src != 'DB' && $src != 'WEB') {
		print "\n\n\nSource must be 'DB' or 'WEB' (default is 'WEB')\n\n\n";
		exit;
	}
	
	$and = (isset($argv[2]) ? "and cp_id = {$argv[2]} " : '');
	$sql = "select cp_id, cpub_id, cpub_name from cbdb_publishers where del_ind is null $and order by cpub_name";
	$result = mysql_query($sql);
	
	print "\n\ncurrent data time: $now\n\n";
	
	while($cpub_row = mysql_fetch_assoc($result)){
		print "processing\t{$cpub_row['cp_id']}\t{$cpub_row['cpub_id']}\t{$cpub_row['cpub_name']}\n";
		
		if ($src == 'DB') {
			$sql = sprintf("select ser_html from cbdb_series_html where cp_id = %s", $cpub_row['cp_id']);
			$result2 = mysql_query($sql);
			if (!$result2) {
				print 'ERROR: on cbdb_series_html select ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n";
				exit;
			}
			$row = mysql_fetch_assoc($result2);
			$html = $row['ser_html'];
		} else {
			$html = get_pub_html($ch, $cpub_row['cpub_id']);
			
			$sql = sprintf("select csh_id from cbdb_series_html where cp_id = %s", $cpub_row['cp_id']);
			$result2 = mysql_query($sql);
			if (!$result2) {
				print 'ERROR: on cbdb_series_html select ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n";
				exit;
			}
			$row = mysql_fetch_assoc($result2);
			
			if ($row === false) {
				$sql = sprintf("insert into cbdb_series_html (cp_id, ser_html) values(%s, %s)", $cpub_row['cp_id'], dbv($html));
				$result2 = mysql_query($sql);
				if (!$result2) {
					print 'ERROR: on cbdb_series_html insert ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n";
					exit;
				}
			} else {
				$sql = sprintf("update cbdb_series_html set ser_html = %s where csh_id = %s", dbv($html), $row['csh_id']);
				$result2 = mysql_query($sql);
				if (!$result2) {
					print 'ERROR: on cbdb_series_html update ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n";
					exit;
				}
			}
			if ($and != '') {
				file_put_contents(preg_replace('/\W/','', preg_replace('/\s/', '_', $cpub_row['cpub_name'])) . ".html", $html);
			}
		}
		parse_series($cpub_row['cp_id'], $html);
		$pub_read++;
	}
	curl_close($ch);
	
	$sql = sprintf("update cbdb_series set del_ind = 'Y', updt_dt = updt_dt where updt_dt <> %s", dbv($now));
	$result2 = mysql_query($sql);
	if (!$result2) {
		print 'ERROR: on cbdb_series mark deleted [update] ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n";
	}
	print mysql_info() . "\n";
	$logical_deleted = mysql_affected_rows();
	
	print "pubs read: $pub_read\npubs processed: $pub_proc\ncnt $cnt\nins: $ins\nupdt: $updt\nlogically deleted: $logical_deleted\n";
	
function parse_series($cp_id, $html) {
	global $cnt;
	global $ins;
	global $updt;
	global $pub_proc;
	global $now;
	
	$ds = array(
		0 => array('pipe','r'),		// sdtin
		1 => array('pipe','w'),		// stdout
		2 => array('pipe','r')		// stderr
	);
	
//	$ph = proc_open('./st.pl', $ds, $stdio);
	$ph = proc_open('perl st.pl', $ds, $stdio);
	
	if (!is_resource($ph)) {
		print "error while opening process\n";
		exit;
	}
	
	// send the data
	fwrite($stdio[0], $html);
	fclose($stdio[0]);
	
	// get the results
	$table_html = stream_get_contents($stdio[1]);
	fclose($stdio[1]);
	
	// check for errors
	$std_err = stream_get_contents($stdio[2]);
	fclose($stdio[2]);
	
	proc_close($ph);
	
	if (strlen($table_html) == 0) {
		print "\t\t\t\t\t\t### no titles\n";
	} elseif ($table_html != 'ERROR') {
		$pub_proc++;
		preg_match_all('/<a href="title.php\?ID=(\d+)">(.*?)<\/a>/', $table_html, &$matches, PREG_SET_ORDER);
		$cbdb_series = $matches;
		
		foreach($cbdb_series as $cser_rec) {
			$cser_id = $cser_rec[1];
			$cser_name_yr = $cser_rec[2];
			preg_match('/(.*)\((\d{4})\)$/', $cser_name_yr, &$matches);
			$cser_name = trim($matches[1]);
			$cser_yr = $matches[2];
			
			$cnt++;
			
			$sql = sprintf("select cs_id from cbdb_series where cp_id = %s and cser_id = %s", $cp_id, $cser_id);
			$result = mysql_query($sql);
			if (!$result) {
				print 'ERROR on cbdb_series select: ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n";
				exit;
			}
			$row = mysql_fetch_assoc($result);
			
			if ($row === false) {
				
				$ins++;
				$sql = sprintf("insert into cbdb_series (cp_id, cser_id, cser_name, cser_year, crt_dt, crt_usr_id, updt_dt, updt_usr_id) values(%s, %s, %s, %s, %s, 2, %s, 2)",
						hdbv($cp_id), hdbv($cser_id), hdbv($cser_name), hdbv($cser_yr), dbv($now), dbv($now));
				$result = mysql_query($sql);
				
				if (!$result) {
					
					if (mysql_errno() != 1062) {
						print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## ERROR INSERT\t\t##\t");
						print 'ERROR: on cbdb_series insert ' . mysql_errno() . ': ' . mysql_error() . "\t$sql\n";
						exit;
					} else {
						// rename [update the cser_name of] the existing record
						$sql2 = sprintf("update cbdb_series set cser_name = concat(cser_name, ' [DUP: %s - %s]'), updt_dt = updt_dt where cp_id = %s and cser_name = %s and cser_year = %s", 
								$cser_id, $now, $cp_id, hdbv($cser_name), hdbv($cser_yr));
						$result = mysql_query($sql2);
						if (!$result) {
							print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## RENAME\t\t##\t");
							print 'ERROR: on cbdb_series rename [update] ' . mysql_errno() . ': ' . mysql_error() . "\t$sq2l\n";
							exit;
						}
						print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## RENAME\t\t##\n");
						
						// redo insert
						$result = mysql_query($sql);
						if (!$result) {
							print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## ERROR INSERT [2x]\t\t##\t");
							print 'ERROR: on cbdb_series insert ' . mysql_errno() . ': ' . mysql_error() . "\t$sql\n";
							exit;
						}
					}
				}
				print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## INSERT\t" . mysql_insert_id() . "\t##\n");
			} else {
			
				$updt++;
				$cs_id = $row['cs_id'];
			
				$sql = sprintf("update cbdb_series set cser_name = %s, cser_year = %s, updt_dt = %s, updt_usr_id = %s where cs_id = %s",
						hdbv($cser_name), hdbv($cser_yr), dbv($now), 2, $cs_id);
				$result = mysql_query($sql);
				
				if (!$result) {
					
					if (mysql_errno() != 1062) {
						print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## ERROR UPDATE\t$cs_id\t##\t");
						print 'ERROR: on cbdb_series update ' . mysql_errno() . ': ' . mysql_error() . "\t$sql\n";
						exit;
					} else {
						// rename [update the cser_name of] the existing record
						$sql2 = sprintf("update cbdb_series set cser_name = concat(cser_name, ' [DUP: %s - %s]'), updt_dt = updt_dt where cp_id = %s and cser_name = %s and cser_year = %s", 
								$cser_id, $now, $cp_id, hdbv($cser_name), hdbv($cser_yr));
						$result = mysql_query($sql2);
						if (!$result) {
							print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## RENAME\t\t##\t");
							print 'ERROR: on cbdb_series rename [update] ' . mysql_errno() . ': ' . mysql_error() . "\t$sq2l\n";
							exit;
						}
						print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## RENAME\t\t##\n");
						
						// redo update
						$result = mysql_query($sql);
						if (!$result) {
							print hv("ID:\t$cser_id\tname:t$cser_name\tyr:t$cser_yr\t## ERROR UPDATE [2x]\t\t##\t");
							print 'ERROR: on cbdb_series insert ' . mysql_errno() . ': ' . mysql_error() . "\t$sql\n";
							exit;
						}
					}
				}
				print hv("ID:\t$cser_id\tname:\t$cser_name\tyr:\t$cser_yr\t## UPDATE\t$cs_id\t##\n");
			}
				
			$cser_id = null;
			$cser_name = null;
			$cser_yr = null;
			
		}
	} else {
		print "\t\t\t\t\t\t### error on processing\n";
	}

	
}

function hv($val) {
	return iconv("ISO-8859-1", "UTF-8", html_entity_decode($val, ENT_QUOTES));
}

// why did I create a function that only calls another function
function hdbv($val) {
	return dbv($val);
}

function dbv($val) {
	if (!strlen($val)) {
		return "NULL";
	}
	// note we're calling hv() below...
	$escd_val = mysql_real_escape_string(hv($val));
	return (is_numeric($val) ? $escd_val : "'$escd_val'");
}
	
function get_pub_html($ch, $pub_id) {
	
	$nbsp = chr(160);
	// get the series index html for the publisher
	$url = "http://www.comicbookdb.com/publisher.php?ID=$pub_id";
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$html = curl_exec($ch);
	$html = preg_replace('/(\n|\r)/', '', $html);
//	$html = html_entity_decode(preg_replace('/(\n|\r)/', '', $html), ENT_QUOTES);
//	$html = iconv("ISO-8859-1", "UTF-8", $html);
	
	return $html;
}
	
?>