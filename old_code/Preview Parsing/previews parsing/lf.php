<?php 

require_once 'db_common.inc.php';

// change this once the script is moved to the production location and scheduled
$path = '/Users/gskluzacek/Documents/Development/previews parsing/';

// change the line below to get the file name as the first paramater passed in from the command line
// $file = 'JAN13_COF.txt';

try {

	// open a database connection
	$mysqli = @new mysqli('localhost', 'root', 'root', 'bipolar');
	if ($mysqli->connect_errno) {
		throw new mysqli_sql_exception('Could not conncet to database - ' . $mysqli->error, $mysqli->connect_errno);
	}

	// define and prepare insert statements to insert into the previews raw/detail table	
	if (!($pvs_lns_stmt = $mysqli->prepare("insert into previews_lines (pvh_id, pvl_seq, line_text) values(?, ?, ?)"))) {
		throw new mysqli_sql_exception("Error on PREPAIR: INSERT into previews_lines table - " . $mysqli->error, $mysqli->errno);
	}
	if (!$pvs_lns_stmt->bind_param("iis", $pvh_id, $pvl_seq, $line_text)) {
		throw new mysqli_sql_exception("Error on BIND: INSERT into previews_lines table - " . $mysqli->error, $mysqli->errno);
	}
	
	$files = glob($path . '*_COF.txt');

	$months = array('JAN'=>'01','FEB'=>'02','MAR'=>'03','APR'=>'04','MAY'=>'05','JUN'=>'06','JUL'=>'07','AUG'=>'08','SEP'=>'09','OCT'=>'10','NOV'=>'11','DEC'=>'12');
	$files_sorted = array();

	foreach ($files as $file) {
		$f = basename($file);
		$mo = substr($f,0,3);
		$yr = substr($f,3,2);
		$files_sorted[$yr . '_' . $months[$mo]] = $f;
	}
	ksort($files_sorted, SORT_STRING);

	foreach ($files_sorted as $file) {

		// open the customer order form text file for read access
		$fh = @fopen($path . $file, 'r');
		if (!$fh) {
			throw new Exception('Could not open file: $file.');
		}
		
		// NOTE: we could improve our identity string parsing by validating if the first line
		//       matches the regEx used in the pp.php script to determine if the line is the
		//       IDENT line type... may be even able to use the redEx capture (match option)
		//       to do the parsing
		//
		// preg_match('/^PREVIEWS (JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC).* V(OL){0,1}\.{0,1} {0,1}\d\d #\d{1,2}$/', $line)
		//
		// if the file was opened successfully then get and parse the identity string
		// PREVIEWS AUGUST VOL. 24 #8
		// [0] PREVIEWS		constant
		// [1] AUGUST		month - full name
		// [2] VOL.			constant
		// [3] 24			volume number
		// [4] #8			issue number
		// PREVIEWS VOL. {format: left padded with zeros to 3 digits <<volume number>>} {format: left padded with zeros to 2 digits <<issue number>>} {derived value: mo_abbr = substr(<<month>>, 0, 3)} - {derived value: year == <<volume number>> + 2000 - 10}
		if (($line = fgets($fh)) !== false) {
			$ident = explode(' ', rtrim($line));
			if ($ident[0] == 'PREVIEWS') {
				$ident_str = 'PREVIEWS VOL ' . str_pad($ident[3], 3, '0', STR_PAD_LEFT) . ' ' . str_pad($ident[4], 2, '0', STR_PAD_LEFT) . ' ' . substr($ident[1], 0, 3) . '-' . ($ident[3] + 2000 - 10);
			} else {
				$ident_str = 'UNKNOWN IDENTITY STRING';
			}
		} else {
			if (!feof($fh)) {
				throw new Exception('Unexpected error while reading file: $file.');
			}
			throw new Exception('Empty file: $file.');
		}
		
		//
		// ### bug below ###
		// ### 2014-08-16 bug should be fixed now ###
		//
		// the code below is not correctly parsing the date from the file name
		//
		// parse the period string from the name of the customer order form file
		// AUG14_COF.txt
		list($period_str) = explode('_', $file);
		// ### OLD CODE ### $period_dt = date('Y-m-01', strtotime($period_str));
		// ### NEW CODE on next line ###
		$period_dt = date('Y-m-01', strtotime('01-' . substr($period_str, 0, 3) . '-20' . substr($period_str, 3, 2)));
	
		// set this to null for now, but later add code to set it to the actual url as parsed out from the instructions above.
		$url_to_cof = null;
	
		// insert a record into the previews header table 
		$sql = crtsql($mysqli, "insert into previews_hdr (period_dt, period_str, ident_str, local_file, url_to_cof) values (%s, %s, %s, %s, %s)",
				$period_dt, $period_str, $ident_str, $file, $url_to_cof);
		if (($result = $mysqli->query($sql)) === false) {
			throw new mysqli_sql_exception('Error on INSERT into the previews_hdr table - ' . $mysqli->error, $mysqli->errno);
		}
		$pvh_id = $mysqli->insert_id;
		
		rewind($fh);
		$pvl_seq = 1;
		while (($line_text = fgets($fh)) !== false) {
			if (!$pvs_lns_stmt->execute()) {
				throw new mysqli_sql_exception("Error on EXECUTE: INSERT into previews_lines table - " . $mysqli->error, $mysqli->errno);
			}
			$pvl_seq++;
		}
		fclose($fh);
	}
	$mysqli->close();

} catch (mysqli_sql_exception $e) { 
	print "Caught MYSQL exception: " . $e->getMessage() . "\n";
	print $e->getCode()."\n";
	print $e->getFile()."\n";
	print $e->getLine()."\n";
	
} catch (Exception $e) {
	print "Caught exception: " . $e->getMessage() . "\n";
	print $e->getCode()."\n";
	print $e->getFile()."\n";
	print $e->getLine()."\n";

}


exit;


?>