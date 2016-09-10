<?php 

// must pass in either:
//	1) the internet_sources id (is_id) to use
//
//		get_cvr_dt.php -s##
//
//	2) the execution_history id (eh_id) to reprocess the matches
//
//		get_cvr_dt.php -r##
//

// print_r($argv);

$debug = true;

$usage = "Usage:\n\n\tget_cvr_dt.php -s##\n\tget_cvr_dt.php -r##\n\nwhere\n\t-s## is the " . 
		"internet source id to use when performing an initial processing of a invoice file\n\t-r## " . 
		"is the execution id to reprocess an invoice file when all series/issue numbers were not matched\n\n";

$options = "Valid options are\n\t-s## " . 
		"- internet source id\n\t-r## - execution id\n";

// validate if the program was called propperly from the command line (ie. the correct # of arguments)
if ($argc <> 2) {
	print "\n\nInvalid usage: '" . implode(' ', $argv) . "'\n";
	print $usage;
	exit;
}

// validate if the option is in the correct format (ie. start with a dash)
if (substr($argv[1], 0, 1) != "-") {
	print "\n\nInvalid option format, options must start with a dash ('-') character\n" . $options;
	print $usage;
	exit;
}

// validate if the option is valid (i.e. s or r)
if (substr($argv[1], 1, 1) != "s" && substr($argv[1], 1, 1) != "r") {
	print "\n\nInvalid option: '" . substr($argv[1], 1, 1) . "'\n" . $options;
	print $usage;
	exit;
}

// temp value for the numeric portion of the first parameter passed in (either the internet source id or the execution id)
$t = substr($argv[1], 2);

// validate if the value of the option is valid (i.e. a positive integer)
if (!is_numeric($t) || intval($t) != $t || intval($t) < 1) {
	$p = substr($argv[1], 1, 1) == 's' ? 'internet source id' : 'execution id';
	print "\n\nInvalid $p: '$t'\nValid $p must be a positive integer greater than 1\n\n";
	exit;
}

// validate that the sqlite3 database file is in the same directory as the command
if (!is_file('comics_invoicing.db')) {
	print "\n\nCannot locate database file 'comics_invoicing.db'\nEither copy the database file to the same directory as the program or create the database in the same directory as the program if it does not exist\n\n";
	exit;
}
// maybe add logic to generate an empty db?

// connect to the db
$db = new SQLite3('comics_invoicing.db');

// enable foreign key support
if (!$db->exec('PRAGMA foreign_keys = ON')) {
	print "\n\ncould not enable foreign key support, exiting...\n\n";
	$db->close();
	exit;
}

// verify foreign key support was enabled
$fk_supp = $db->querySingle("PRAGMA foreign_keys");
if ($fk_supp === false or $fk_supp != 1) {
	print "\n\nforeign keys not supported, exiting...\n\n";
	$db->close();
	exit;
}

// if we are reprocessing a previous execution...
if (substr($argv[1], 1, 1) == 'r') {

	// get corresponding execution_history record for eh_id
	$row = $db->querySingle("select is_id, is_name, exec_status from execution_history eh join internet_sources tis on tis.is_id = eh.is_id where eh_id = $t", true);
	
	// validate the execution id that was passed in
	if (!$row) {
		print "\n\nExecution Id not found: '" . $t . "'\n\n";
		$db->close();
		exit;
	}
	
	$mode = 'R';	// reprocess
	$eh_id = $t;	// get the value of the execution id passed in on the command line which was temporarily stored in $t above
	$is_id = $row['is_id'];			// get internet source id retreived from the execution_history table
	$exec_sts = $row['status'];		// get the execution status (from the execution_history table)
	$src = $row['is_name'];			// get the name of the internet source id
	
	// validate the status of the execution id that is to be reprocessed
	if ( $exec_sts == 'FAILED') {
		print "\n\nExecution Status of FAILED, cannot reprocess untill issues have been corrected and status set to 'READY FOR REPROCESSING'\n\n";
		$db->close();
		exit;
	}
	
	// validate the status of the execution id that is to be reprocessed
	if ( $exec_sts == 'IN PROCESS') {
		print "\n\nExecution Status of IN PROCESS, cannot reprocess at this time'\n\n";
		$db->close();
		exit;
	}
	
	// print NOTICE if we're reprocessing an execution id that had been previously completed successfully
	if ( $exec_sts == 'INVOICE GENERATED') {
		print "\n\nNOTICE: Execution Status of INVOICE GENERATED'\n";
	} else {
		print "\n\nInfo: Execution Status of $exec_sts\n";
	}
	
	$sql = "update execution_history set exec_status = 'IN PROCESS' where eh_id = $eh_id";
	if (!$db->exec($sql)) {
		print "\n\ncould not update execution_history table status to IN PROCESS: " . $db->lastErrorMsg() . "\nExiting...\n\n";
		$db->close();
		exit;
	}
	
	print "Reprocessing with internet source of: $src...\n\n";
	
} else {

	// get correspondign internet_sources record for is_id
	$row = $db->querySingle("select is_name, is_base_url, is_series_index_page from internet_sources where is_id = $t", true);

	// validate the internet sourcd id passed in on the command line	
	if (!$row) {
		print 
		print "\n\Internet Source Id not found: '" . $t . "'\n\n";
		$db->close();
		exit;
	}
	
	$mode = 'I';	// initial processing
	$is_id = $t;	// get the value of the internet sourcd id passed in on the command line which was temporarily stored in $t above
	$url = $row['is_base_url'] . $row['is_series_index_page'];		// set the series index page URL for the internet source 
	$src = $row['is_name'];		// get the name of the internet source id
	
	print "Processing with internet source of: $src...\n\n";
	
	// validate the input file is present
	if (!is_file('cz_issues_input.txt')) {
		print "\n\nCannot locate the input file 'cz_issues_input.txt'. Please ensure the input file is in the same directory as the program. Exiting...\n\n";
		$db->close();
		exit;
	}
	
	// insert a new record into the execution history table for this execution
	if (!$db->exec("insert into execution_history (exec_dt, is_id, exec_status) values (datetime('now'), $is_id, 'IN PROCESS')")) {
		print "\n\ncould not insert into execution_history table: " . $db->lastErrorMsg() . "\nExiting...\n\n";
		$db->close();
		exit;
	}
	$eh_id = $db->lastInsertRowID();	// get the execution id that was just inserted
	
	print "Execution ID: $eh_id\n\n";
	
	// sql statment used to insert records into the collectorz_invoice_input table from the cz_issues_input.txt input file
	$stmt = $db->prepare("insert into collectorz_invoice_input (eh_id, " .
			"cii_release_dt, cii_cover_dt_disp, cii_cover_dt, cii_series, " . 
			"cii_issue, cii_issue_no, cii_issue_ext, cii_edition, cii_full_title, " . 
			"computed_issue_num) values (:eh_id, :release_dt, :cover_dt_disp, :cover_dt, " .
			":series, :issue, :issue_no, :issue_ext, :edition, :full_title, :computed_issue_num)");
	
	// read the entire file in as an array
	$input_arr = file('cz_issues_input.txt', FILE_IGNORE_NEW_LINES);
	
	$file_series = array();
	
	// process each line in the cz_issues_input.txt input file
	foreach ($input_arr as $line) {
		$fields = str_getcsv($line, ';');
		// var_dump($fields);
		
		//
		// compute the issue number that will be used when matching issues from the input file to the issues retrieved from the internet source
		//
		if ($fields[4] == "" & $fields[5] == "") {
			// if the issue number and issue extention are both blank then there is no computed issue number
			$computed_issue_num = "NA";
		} else {
			if ($fields[4] == "") {
				// if the issue number is blank then initialize the computed issue number to zero
				$computed_issue_num = "0";
			} else {
				// else initialize the computer issue number to the issue number
				$computed_issue_num = $fields[4];
			}
			
			// process the issue extention
			$t = '';	// temp variable to collect the characters of the issue extension
			$ext = trim($fields[3]);
			if (substr($ext, 0, 1) == '.' && strlen($ext) > 1) {
				// if the issue extention starts with a period and has a length greater than 1 character process the issue extension
				for ($i = 1; $i < strlen($ext); $i++) {
					if ($ext[$i] == ' ') {
						// skip any space characters that might be present after the period
						continue;
					}
					if (is_numeric($ext[$i])) {
						// if the character is numeric append it to the temp variable
						$t .= $ext[$i];
					} else {
						// exit processing of the issue extension on the first non-numeric character found
						break;
					}
				}
				// append the collected extension if we have collected 2 more characters
				if (strlen($t) > 1) {
					$computed_issue_num .= ".$t";
				}
			}
		}
		
		$time_stamp = strtotime($fields[0]);
		$release_dt = date('Y-m-d', $time_stamp);
		
		$time_stamp = strtotime($fields[1]);
		$cover_dt = date('Y-m', $time_stamp) . '-01';
		$cover_dt_disp = date('M-Y', $time_stamp);
		
		// track how many issues for each series are on the input file
		if (! array_key_exists($fields[2], $file_series)) {
		    // is this a bug? should it be initialized to a value of 1 instead of a value of 0?
			$file_series[$fields[2]] = 0;
		} else {
			$file_series[$fields[2]]++;
		}
		
		$stmt->bindValue(':eh_id', 			$eh_id, SQLITE3_INTEGER);
		$stmt->bindValue(':release_dt',		n($release_dt), SQLITE3_TEXT);
		$stmt->bindValue(':cover_dt_disp',	n($cover_dt_disp), SQLITE3_TEXT);
		$stmt->bindValue(':cover_dt',		n($cover_dt), SQLITE3_TEXT);
		$stmt->bindValue(':series',			$fields[2], SQLITE3_TEXT);
		$stmt->bindValue(':issue',			n($fields[3]), SQLITE3_TEXT);
		$stmt->bindValue(':issue_no',		n($fields[4]), SQLITE3_TEXT);
		$stmt->bindValue(':issue_ext',		n($fields[5]), SQLITE3_TEXT);
		$stmt->bindValue(':edition',		n($fields[6]), SQLITE3_TEXT);
		$stmt->bindValue(':full_title',		n($fields[7]), SQLITE3_TEXT);
		$stmt->bindValue(':computed_issue_num', $computed_issue_num, SQLITE3_TEXT);

		$rc = $stmt->execute();
		
		// verify the insert succeeded
		if (!$rc) {
			print "insert into collectorz_invoice_input failed: " . $db->lastErrorMsg() . "\nExiting...\n\n";
			
			$sql = "update execution_history set exec_status = 'FAILED' where eh_id = $eh_id";
			if (!$db->exec($sql)) {
				print "\n\ncould not update execution_history table status to IN PROCESS: " . $db->lastErrorMsg() . "\nExiting...\n\n";
				$db->close();
				exit;
			}
			
			$db->close();
			exit;
		}
	}
	
	$stmt->close();
	
	$issues_arr = array();
	$ch = curl_init();
	
	$sql = "select distinct issi.issi_series_id, ser.ser_id, ser.ser_name, ser.ser_year, cii.cii_series " . 
			"from execution_history eh " .
			"join is_series_ids issi on issi.is_id = eh.is_id " .
			"join series ser on ser.ser_id = issi.ser_id " .
			"join collectorz_invoice_input cii on cii.cii_series = ser.cii_series and cii.eh_id = eh.eh_id " .
			"where eh.eh_id = $eh_id " .
			"order by ser.ser_name_sort";
	
	$cii_series = array();
	
	$results = $db->query($sql);
	$series_arr = array();
	while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
		$series_arr[] = $row;
		$cii_series[$row['cii_series']] = true;
		
	}
	
	print '$' . "series_arr content:\n\n";
	
	print "series count " . count($series_arr) . "\n\n";
	
	print_r($series_arr);
	
	print "\n\n----------------------\n\n";
	
	foreach (array_keys($file_series) as $series) {
		if (! array_key_exists($series, $cii_series)) {
			print "### WARNING ###\n $series does not exist on the series tables\n";
		}
	}
	
	print "\n\n----------------------\n\n";

	$sql_hdr = "insert into is_series_header (eh_id, ser_id) values (:eh_id, :ser_id)";
	
	$sql_ndx = "insert into is_series_index (ish_id, isi_issue_id, isi_issue_type, " . 
	"isi_issue_num, isi_issue_name, isi_variant_seq, isi_variant_desc, isi_cover_dt_disp, " . 
	"isi_cover_dt, isi_story_arc_id, isi_story_arc) values (:ish_id, :isi_issue_id, " . 
	":isi_issue_type, :isi_issue_num, :isi_issue_name, :isi_variant_seq, :isi_variant_desc, " . 
	":isi_cover_dt_disp, :isi_cover_dt, :isi_story_arc_id, :isi_story_arc_name)";
	
	$sql_html = "insert into is_html (ish_id, html_text) values(:ish_id, :html_text)";
	
	foreach ($series_arr as $series) {
		print_r($series);
		
		// insert into is_series_header
		$stmt_hdr = $db->prepare($sql_hdr);
		
		$stmt_hdr->bindValue(':eh_id', $eh_id, SQLITE3_INTEGER);
		$stmt_hdr->bindValue(':ser_id', $series['ser_id'], SQLITE3_INTEGER);
		
		$rc = $stmt_hdr->execute();
		if(!$rc) {
			print "error inserting into is_series_header: " . $db->lastErrorMsg() . "\nExiting...\n\n";
			
			$sql = "update execution_history set exec_status = 'FAILED' where eh_id = $eh_id";
			if (!$db->exec($sql)) {
				print "\n\ncould not update execution_history table status to IN PROCESS: " . $db->lastErrorMsg() . "\nExiting...\n\n";
				$db->close();
				exit;
			}
			
			$db->close();
			exit;
		}
		$ish_id = $db->lastInsertRowID();
		
		$stmt_hdr->close();
		
		$html = get_iss_list_html($ch, $url . $series['issi_series_id']);
		
		$stmt_html = $db->prepare($sql_html);
		
		$stmt_html->bindValue(':ish_id', $ish_id, SQLITE3_INTEGER);
		$stmt_html->bindValue(':html_text', $html, SQLITE3_BLOB);
		
		if(!$stmt_html->execute()) {
			print "error inserting into is_series_header: " . $db->lastErrorMsg() . "\nExiting...\n\n";
			
			$sql = "update execution_history set exec_status = 'FAILED' where eh_id = $eh_id";
			if (!$db->exec($sql)) {
				print "\n\ncould not update execution_history table status to IN PROCESS: " . $db->lastErrorMsg() . "\nExiting...\n\n";
				$db->close();
				exit;
			}
			
			$db->close();
			exit;
		}
		$stmt_html->close();
		
		$issues_arr = get_series_issue_list($html);
		print "number of issues in {$series['ser_name']} ({$series['ser_year']}): " . count($issues_arr) . "\n\n";
		
		$stmt_ndx = $db->prepare($sql_ndx);
		
		foreach ($issues_arr as $issue) {
			print_r($issue);
			
			// insert the record into the is_series_index table
			$stmt_ndx->bindValue(':ish_id',					$ish_id,						SQLITE3_INTEGER);
			$stmt_ndx->bindValue(':isi_issue_id',			$issue['isi_issue_id'],			SQLITE3_INTEGER);
			$stmt_ndx->bindValue(':isi_issue_type',			$issue['isi_issue_type'],		SQLITE3_TEXT);
			$stmt_ndx->bindValue(':isi_issue_num',			$issue['isi_issue_num'],		SQLITE3_TEXT);
			$stmt_ndx->bindValue(':isi_issue_name',			$issue['isi_issue_name'],		SQLITE3_TEXT);
			$stmt_ndx->bindValue(':isi_variant_seq',		$issue['isi_variant_seq'],		SQLITE3_INTEGER);
			$stmt_ndx->bindValue(':isi_variant_desc',		$issue['isi_variant_desc'],		SQLITE3_TEXT);
			$stmt_ndx->bindValue(':isi_cover_dt_disp',		$issue['isi_cover_dt_disp'],	SQLITE3_TEXT);
			$stmt_ndx->bindValue(':isi_cover_dt',			$issue['isi_cover_dt'],			SQLITE3_TEXT);
			$stmt_ndx->bindValue(':isi_story_arc_id',		$issue['isi_story_arc_id'],		SQLITE3_INTEGER);
			$stmt_ndx->bindValue(':isi_story_arc_name',		$issue['isi_story_arc_name'],	SQLITE3_TEXT);
			
			$rc = $stmt_ndx->execute();
			if(!$rc) {
				print "error inserting into is_series_index: " . $db->lastErrorMsg() . "\nExiting...\n\n";
			
				$sql = "update execution_history set exec_status = 'FAILED' where eh_id = $eh_id";
				if (!$db->exec($sql)) {
					print "\n\ncould not update execution_history table status to IN PROCESS: " . $db->lastErrorMsg() . "\nExiting...\n\n";
					$db->close();
					exit;
				}
			
				$db->close();
				exit;
			}
			
		}
		$stmt_ndx->close();
		
	}
	
	$fh = fopen('invoice_ouput.csv', 'w');
	
	fputcsv($fh, array("Match Sts", "Title", "Year", "Notes", "Cover Date", "ISI Cover Dt", 
		"Num", "Cover Price", "CII Cover Dt", "CII Release Dt", "CII Series", 
		"CII Issue", "CII Full Title", "CII Edition", "ISI Variant", "ISI Issue Name", "ISI Story Arc",
		"Series ID", "Issue ID"));
	
	$sql = "select rec_type, ser_name, ser_year, null as notes, null as cover_dt, isi_cover_dt_disp, " .
	"isi_cover_dt, computed_issue_num, ser_cover_price, cii_cover_dt_disp, cii_release_dt, cii_series, " .
	"cii_issue, cii_full_title, cii_edition, isi_variant_desc, isi_issue_name, isi_story_arc, " .
	"issi_series_id, isi_issue_id from fnl_rpt_1 where eh_id = $eh_id " .
	"order by ser_name_sort, computed_issue_num, isi_variant_seq";
	
	$lookup_sts = array();
	$results = $db->query($sql);
	while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
		$lookup_sts[$row['cii_series']][$row['cii_issue']] = array(
				'sts'			=> $row['rec_type'], 
				'cover_dt'		=> $row['rec_type'] != 'Multiple Dates' ? $row['isi_cover_dt'] : null, 
				'cover_dt_disp'	=> $row['rec_type'] != 'Multiple Dates' ? $row['isi_cover_dt_disp'] : null
		);
		unset($row['isi_cover_dt']);
		fputcsv($fh, $row);
	}
	
	fclose($fh);
	
	print_r ($lookup_sts);
	
	$sql = "update collectorz_invoice_input set lookup_status = :status, isi_cover_dt = :cover_dt, isi_cover_dt_disp = :cover_dt_disp where cii_series = :series and cii_issue = :issue";
	$stmt = $db->prepare($sql);
	
	foreach ($lookup_sts as $series => $issues) {
		foreach ($issues as $issue => $data) {
			$stmt->bindValue(':series', 		$series, SQLITE3_TEXT);
			$stmt->bindValue(':issue', 			$issue, SQLITE3_TEXT);
			$stmt->bindValue(':status', 		$data['sts'], SQLITE3_TEXT);
			$stmt->bindValue(':cover_dt', 		$data['cover_dt'], SQLITE3_TEXT);
			$stmt->bindValue(':cover_dt_disp',	$data['cover_dt_disp'], SQLITE3_TEXT);
			
			$rc = $stmt->execute();
			if(!$rc) {
				print "error updating collectorz_invoice_input status: " . $db->lastErrorMsg() . "\nExiting...\n\n";
			
				$sql = "update execution_history set exec_status = 'FAILED' where eh_id = $eh_id";
				if (!$db->exec($sql)) {
					print "\n\ncould not update execution_history table status to IN PROCESS: " . $db->lastErrorMsg() . "\nExiting...\n\n";
					$db->close();
					exit;
				}
			
				$db->close();
				exit;
			}
		}
	}
	$stmt->close();
}	

$sql = "update execution_history set exec_status = 'INVOICE GENERATED' where eh_id = $eh_id";
if (!$db->exec($sql)) {
   print "\n\ncould not update execution_history table status to IN PROCESS: " . $db->lastErrorMsg() . "\nExiting...\n\n";
   $db->close();
   exit;
}

$db->close();
exit;

// ***************************************** //

	function get_series_issue_list($html) {
		$issues_list = array();
		
		// used to determine 'cb db issue number'
		$dups_ctr = array();
	
		// get the table html for the issue rows
		$rc = preg_match('/<table border="0" cellpadding="1" cellspacing="0">(.*)<\/table><br><br><br>/', $html, $matches);
		if ($rc != 1) {
			$rc = preg_match('/.*mysql_connect.*max_user_connections.*/', $html, $matches);
			$err_msg = ( $rc == 1 ?
					"CBDB has reached it maximum connection limit, please try again later." :
					"An unknown error has occured while connecting to the CBDB." );
			print $err_msg;
			exit;
		}
		$table_html = $matches[1];
	
		// put all html issue rows into an array
		preg_match_all('/<tr>(.*?)<\/tr>/', $table_html, $matches);
		$rows_html_array = $matches[1];
		array_shift($rows_html_array);
		
		// initialize the issue name and number outside of the for loop	
		$iss_num = null;
		$iss_name = null;
		$story_arc_id = null;
		$story_arc_name = null;


		// process each html issue row
		foreach ($rows_html_array as $row_html) {
		
			// parse out each html cell from the html issue row
			preg_match_all('/<td.*?' . '>(.*?)<\/td>/', $row_html, $matches);
			$cells_html_array = $matches[1];
		
			// initialize variables
			$row_type_ind = null;
			$cbdb_iss_id = null;
			// $iss_num = null;
			// $iss_name = null;
			$var_desc = 'Std Variant';
			// $story_arc_id = null;
			// $story_arc_name = null;
			$date = null;
		
		
			//
			// cell 1: match element #0 - parse out issue number & cbdb issue id
			//
			// <a href="issue.php?ID=216804" class="page_link">586</a><br>
			// <a href="javascript:blocking('issue_213863', 'anchor_213863');"><img src="graphics/icon_plus.gif" alt="" width="9" height="9" border="0" id="anchor_213863"></a> 
			//			<a href="issue.php?ID=213863" class="page_link">584</a><br>
			// &nbsp;
			//
			preg_match('/<a href="issue.php\?ID=(\d*)".*>(.*?)<\/a>|&nbsp;/', $cells_html_array[0], $matches);
			$results_cnt = count($matches);
			switch ($results_cnt) {
				case 1;
					// variant issue or multiple story arcs
					break;
				case 3:
					// non-variant issue
					$row_type_ind = 'NV';
					$var_seq = 1;
					$cbdb_iss_id = trim($matches[1]);
					$iss_num = trim($matches[2]);
					$iss_num = calc_iss_num($iss_num, $cbdb_iss_id, $dups_ctr);
					$story_arc_id = null;
					$story_arc_name = null;
					break;
				default:
					// error
					print "Error parsing issue id/number\n" . print_r($row_html, true);
					exit;
					break;
			}
		
			//
			// cell 3: match element #2 - parse out issue name or issue number & variant description
			//
			// <a href="issue.php?ID=216804">World-Eater!</a><br>
			// <a href="issue.php?ID=213891">(Arthur Adams Variant)</a><br>
			//
			preg_match('/<a href="issue.php\?ID=(\d*)".*>\(?(.*?)\)?<\/a>|&nbsp;/', $cells_html_array[2], $matches);
			$results_cnt = count($matches);
			switch ($results_cnt) {
				case 1;
					// multiple story arcs
					$row_type_ind = 'MSA';
					$var_desc = "";
					break;
				case 3:
					// non-variant or variant issue
					if ($row_type_ind == 'NV') {
						// non-variant
						$iss_name = trim($matches[2]);
					} else {
						// variant
						$row_type_ind = 'VAR';
						$cbdb_iss_id = trim($matches[1]);
						$var_desc = trim($matches[2]);
					}
					break;
				default:
					// error
					print "Error parsing issue name / variant description\n" . print_r($row_html, true);
					exit;
					break;
			}
		
			//
			// cell 5: match element #4 - parse out story arc name & id
			//
			// <a href="storyarc.php?ID=4133">Three</a><br>
			// &nbsp;
			//
			preg_match('/<a href="storyarc.php\?ID=(\d*)".*>(.*?)<\/a>|&nbsp;/', $cells_html_array[4], $matches);
			$results_cnt = count($matches);
			switch ($results_cnt) {
				case 1;
					// variant
					break;
				case 3:
					// non-variant issue or multiple story arcs
					$story_arc_id = trim($matches[1]);
					$story_arc_name = trim($matches[2]);
					break;
				default:
					// error
					print "Error parsing story arc id/name\n" . print_r($row_html, true);
					exit;
					break;
			}
		
			//
			// cell 7: match element #6 - parse out cover date
			//
			// get cover date if non variant or variant row type
			if ($row_type_ind == 'NV' || $row_type_ind == 'VAR') {
			
				// strip of the trailing <BR> tag if there is one
				$date_str = (strtoupper(substr($cells_html_array[6], -4)) == '<BR>' ? 
						trim(substr($cells_html_array[6], 0, -4)) : 
						trim($cells_html_array[6]));
						
				// break the string into its components 
				// if 2 elements --> month (or another word) & year
				// if 3 elements --> {early | late }, month & year
				$date_array = explode(' ', $date_str);
			
				// if only 1 element exists it is the year
				if (count($date_array) == 1) {
					$date_disp = $date_array[0];
					$date = "$date_disp-01-01";
				} elseif (count($date_array) == 3) {
					$day = ( strtoupper($date_array[0]) == 'LATE' ? 15 : 1 );
					$time_stamp = strtotime("{$date_array[1]} {$date_array[2]}");
					$date = date('Y-m', $time_stamp) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
					$date_disp = $date_array[0] . ' ' . date('M-Y', $time_stamp);
				// else if the first word is annual then we only have the year
				} elseif (strtoupper($date_array[0]) == 'ANNUAL') {
					$date_disp = $date_array[1];
					$date = "$date_disp-01-01";
				// else have just month and year
				} else {
					$time_stamp = strtotime($date_str);
					$date = date('Y-m', $time_stamp) . '-01';
					$date_disp = date('M-Y', $time_stamp);
				}
			}
		
			/*
			print "\n\n-----------------------------------------------\n";
			print "row_type_ind:   $row_type_ind\n";
			print "iss_num:        $iss_num\n";
			print "iss_name:       $iss_name\n";
			print "cbdb_iss_id:    $cbdb_iss_id\n";
			print "date:           $date\n";
			print "var_seq:        $var_seq\n";
			print "var_desc:       $var_desc\n";
			print "story_arc_id:   $story_arc_id\n";
			print "story_arc_name: $story_arc_name\n\n";
			*/
			
			if ($row_type_ind == 'MSA') {
				continue;
			}
			
			$issues_list[] = array(
				'isi_issue_id'			=> $cbdb_iss_id,
				'isi_issue_type'		=> $row_type_ind,
				'isi_issue_num'			=> $iss_num,
				'isi_issue_name'		=> ( strlen($iss_name) == 0 ? null : $iss_name ),
				'isi_variant_seq'		=> $var_seq++,
				'isi_variant_desc'		=> ( strlen($var_desc) == 0 ? null : $var_desc ),
				'isi_cover_dt_disp'		=> $date_disp,
				'isi_cover_dt'			=> $date,
				'isi_story_arc_id'		=> ( strlen($story_arc_id)   == 0 ? null : $story_arc_id ),
				'isi_story_arc_name'	=> ( strlen($story_arc_name) == 0 ? null : $story_arc_name )
			);
			
		
		} // ### end for each - html_row ###
		
		return $issues_list;
	}


	// either returns the passed in issue number or if the issue number exists multiple times, 
	// returns the issue number concated with a 2 digit sequence number (left padded with a 0) and
	// cbdb issue ID - this allows us to have unique issue number, even when the cbdb has muliple
	// comic books (most likely TBP or other books) with the same issue number, adding a sequence
	// number and the cbdb issue ID allows for sorting and identification of the duplicate issue
	// numbers.
	//
	function calc_iss_num($iss_num, $ciss_id, &$dups_ctr) {
		if (array_key_exists($iss_num, $dups_ctr)) {
			$dups_ctr[$iss_num]++;
			return $iss_num . ' ' . str_pad($dups_ctr[$iss_num], 2, '0', STR_PAD_LEFT) . '*' . $ciss_id;
		} else {
			$dups_ctr[$iss_num] = 0;
			return $iss_num;
		}
	}

	// gets the HTML web page for the issue list for the given cbdb series ID
	//
	function get_iss_list_html($ch, $url) {
	
		$tmpfns = tempnam(sys_get_temp_dir(), "http");
		$pfh = fopen($tmpfns, 'w+');
	
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-us) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_WRITEHEADER, $pfh);
	
		$html = curl_exec($ch);
		$html = preg_replace('/(\n|\r)/', '', $html);
	
		fseek($pfh, 0);
		$headers = explode("\n", fread($pfh, filesize($tmpfns)));
		fclose($pfh);
		unlink($tmpfns);
	
		if (trim($headers[0]) != 'HTTP/1.1 200 OK') {
			print "Error getting issue list html: $url {$headers[0]}";
			return "";
			// throw new exception("Error getting issue list html: $url {$headers[0]}", 2003);
		}
	
		return $html;
	}
	
	function n($val) {
		return $val == "" ? null : $val;
	}


	// convert from iso-8859-1 to utf-8 only
	// used when printing iso-8859-1 html text
	// when text is already utf-8 just print it
	//
	function hv($val) {
		return iconv("ISO-8859-1", "UTF-8", html_entity_decode($val, ENT_QUOTES));
	}

	// convert from iso-8859-1 to utf-8 and escape mysql characters
	// used when inserting/updating database with iso-8859-1 text
	// when text is already utf-8 call dbv() instead
	function hdbv($val) {
		if (!strlen($val)) {
			return "NULL";
		}
		$escd_val = mysql_real_escape_string(iconv("ISO-8859-1", "UTF-8", html_entity_decode($val, ENT_QUOTES)));
		return (is_numeric($val) ? $escd_val : "'$escd_val'");
	}

	// escape mysql characters only
	// used when inserting/updating database with utf-8 text
	// when text is not utf-8 call hdbv() instead
	function dbv($val) {
		if (!strlen($val)) {
			return "NULL";
		}
		$escd_val = mysql_real_escape_string($val);
		return (is_numeric($val) ? $escd_val : "'$escd_val'");
	}

	// use when you want numeric values trated as text
	function dbtv($val) {
		return (strlen($val) ? "'" . mysql_real_escape_string($val) . "'" : "NULL");
	}


?>