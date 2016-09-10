<?php

function db_connect($config) {
	$settings = $config['config_values'];
	
	@ $db = mysql_pconnect($settings['dbhost'], $settings['dbuser'], $settings['dbpass']);
	if (!$db) {
		echo "Error: Could not connect to database. Please try again later.\n" . mysql_error() . "\n";
		exit;
	} else {
		@ $result = mysql_select_db($settings['dbname']);
		if (!$result) {
			echo "Error: Could not select database. Please try again later.\n" . mysql_error() . "\n";
		}
	}
	mysql_query("SET NAMES utf8");
	
	return $db;
}

function parse_tables($sql) {
	global $db_prefix;
	
//	return preg_replace_callback('/`(.*?)`/', create_function ( '$matches', 'global $db_prefix; return $db_prefix . $matches[1];'), $sql);
	return preg_replace('/`(.*?)`/', $db_prefix . '$1', $sql);
}

function esc_str($val, $qts=1) {
	if (strlen($val)) {
		if ($qts == 0) {
			return $val;
		} else {
			return "'" . mysql_real_escape_string($val) . "'";
		}
	} else {
		return "NULL";
	}
}

	$table_list = array(
		'country'			=> 1,
		'enums'				=> 1,
		'ids_cache_refresh'	=> 1,
		'ids_content_cache'	=> 1,
		'indb_source'		=> 1,
		'issue'				=> 1,
		'issue_ref'			=> 1,
		'message_log'		=> 1,
		'object_source'		=> 1,
		'publisher'			=> 1,
		'publisher_ref'		=> 1,
		'series'			=> 1,
		'series_ref_dtl'	=> 1,
		'series_ref_hdr'	=> 1,
		'types'				=> 1,
		'users'				=> 1,
		'variant'			=> 1,
		'variations'		=> 1,
		'issue_temp'		=> 1,
		'issue_xref'		=> 1,
		'publisher_temp'	=> 1,
		'publisher_xref'	=> 1,
		'series_temp'		=> 1,
		'series_xref'		=> 1,
		'variant_temp'		=> 1,
		'variant_xref'		=> 1
	);


function parse_sql($sql, $db_prefix) {
	global $table_list;
	
	$processed_tables = array();
	$patterns = array();
	$replacements = array();

	preg_match_all('/`(.*?)`/', $sql, $matches, PREG_SET_ORDER);
	
	foreach ($matches as $table) {
//		if (isset($table_list[$table[1]])) {
//			print $table[1] . "\n";
//			print "found\n";
//		}
 		if (isset($table_list[$table[1]]) && !isset($processed_tables[$table[1]])) {
 			$processed_tables[$table[1]] = 1;
 			$patterns[] = '/' . $table[0] . '/';
 			$replacements[] = '`' . $db_prefix . $table[1] . '`';
 		}
	}
			
//	print_r($matches);
//	print_r($processed_tables);
//	print_r($patterns);
//	print_r($replacements);

	return preg_replace($patterns, $replacements, $sql);
}

// function get_tables($config) {
// 	$settings = $config['config_values'];
// 
// 	$prefix = "{$settings['appprefix']}_{$settings['envprefix']}_{$settings['dbtprefix']}";
// 	$pf_len = strlen($prefix) + 1;
// 	$sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = '{$settings['dbname']}' and table_name like '$prefix%';";
// 	$result = mysql_query($sql);
// 	
// 	$tables = array();
// 	
// 	if (!$result) {
// 		print "error \n";
// 	} else {
// 		while ($row = mysql_fetch_array($result)) {
// 			$full_name = $row['table_name'];
// 			$short_name = substr($full_name, $pf_len);
// 			$tables[$short_name] = $full_name;
// 		}
// 	}
// 	
// 	return $tables;
// }

?>