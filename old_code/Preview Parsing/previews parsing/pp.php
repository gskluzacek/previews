<?php 

require_once 'db_common.inc.php';

// PHP script to parse the text version of the Previews World Customer Order Form (COF)
//
// Greg Skluzacek
// Created: 2013-03-11
//
// Note: Diamond updates the current months order form the Wednesday of the 3RD week of the month
//
// source: http://www.previewsworld.com/Home/1/1/71/949
// or via the nav bar: Resources --> User Guide
// FAQ #2
//
// 2. How often is PREVIEWSworld.com updated?
// Just like PREVIEWS, PREVIEWSworld.com is "published" every month, with the advantage 
// that we go "live" a week before you can find the print catalog in stores! Look for 
// updates on Wednesdays, on the third week of each month.
// 
// to get the COF 
// 1) get the html source from: http://www.previewsworld.com
// 2) parse the html to get the link the 'Order Forms' navigation item
//
// sample html from www.previewsworld.com ** Order Forms nav item ***
//
//	<div class="SectionGroup">
//		<div class="SectionGroupName">Resources</div>
//		<hr class="SectionGroupRule" />
//
//			<div class="NavigationItem">
//				<div class="NavigationImg">
//				</div>
//		
//				<div class="NavigationLink">
//								<a href="/Home/1/1/71/936">Subscribe</a>
//				</div>
//			</div>
//			<div class="NavigationItem">
//				<div class="NavigationImg">
//				</div>
//		
//				<div class="NavigationLink">
//								<a href="/Home/1/1/71/947">Order Forms</a>
//				</div>
//			</div>
//
// 3) get the html source for the url obtained in step 2 above
// 4) parse the html and get the link for the COF text file after the text 'Customer Order Form - '
//
// sample html from the 
// 
// <p><strong>Customer Order Form - <a target="_blank" href="/support/previews_docs/orderforms/MAR13_COF.pdf">PDF</a> | <a target="_blank" href="/support/previews_docs/orderforms/MAR13_COF.txt">TXT</a></strong><br />
//
// 5) download the COF text file
// 6) parse the file...
//
// Alternately the COF might be able to be downloaded from the following URL
//
// http://www.previewsworld.com/support/previews_docs/orderforms/MAR13_COF.txt
//
// simply by replacing the MAR13 with the month and year for the desired order form
//
// COF Archvie URLs
// 
// http://www.previewsworld.com/support/previews_docs/orderforms/archive/2013/JAN13_COF.txt
// http://www.previewsworld.com/support/previews_docs/orderforms/archive/2009/JAN09/JAN09_COF.txt
//
//
// For 'New Releases This Week,' go to: 
// http://www.previewsworld.com/Home/1/1/71/952
// Archive:
// http://www.previewsworld.com/Archive/1/1/71/994
// Upcoming Releases for next week:
// http://www.previewsworld.com/Home/1/1/71/954
// New Printings & Variants:
// http://www.previewsworld.com/Home/1/1/71/955
//
// For cancellation documents, go to: 
// http://www.previewsworld.com/Home/1/1/71/956
// Cancellation Codes: What They Mean
//
//  1 - Lateness
//  2 - Will Resolicit
//  3 - Cancelled by PREVIEWS
//  4 - Cancelled by Publisher
//  5 - Out of Stock
//  6 - Sold Out
//  7 - Publisher increments too high
//  8 - Resolicited in a prior Previews
//  9 - Series/Product line cancelled
// 10 - Supplier Out of Business
//
// Caution codes:
//
// International Rights (I-Rights) — the item is restricted into what countries it can be sold.
//   0 = No International Rights restriction
//   1 = International Rights are restricted
//
// Content Changes — the content may change after solicitation.
//   0 = No Content disclaimer
//   1 = Content disclaimer enforced
//
// for shipping updates, go to: 
// http://www.previewsworld.com/Home/1/1/71/957
// Ship Date Changes — the final shipping date may differ or change from the scheduled ship date.
//   0 = Product will ship according to schedule
//   1 = Product may ship an additional 60 days beyond scheduled ship date
//   2 = Additional 90 days beyond scheduled ship date
//   3 = Additional 120 days beyond scheduled ship date
//   4 = Product may ship at any time beyond scheduled ship date



//mysqli_report(MYSQLI_REPORT_STRICT);

// change this once move script and schedule it
$path = '/Users/gskluzacek/Documents/Development/previews parsing/';

try {
	
	// open a database connection
	$mysqli = @new mysqli('localhost', 'root', 'root', 'bipolar');
	if ($mysqli->connect_errno) {
		throw new mysqli_sql_exception('Could not conncet to database - ' . $mysqli->error, $mysqli->connect_errno);
	}

	// reading in the reverse heading name lookup data and store it into the reverse heading name lookup structure
	//
	// The reverse heading name lookup data structure is only used in conjunction with the heading id override (previews_lines.override_pvhl_id)
	//
	// reverse heading name lookup data structure layout
	//
	// heading_levels[pvhl_id][lvl] = 1
	// heading_levels[pvhl_id][name]
	//
	// heading_levels[pvhl_id][lvl] = 2
	// heading_levels[pvhl_id][name]
	// heading_levels[pvhl_id][h1_id]
	// heading_levels[pvhl_id][h1_name]
	//
	// heading_levels[pvhl_id][lvl] = 3
	// heading_levels[pvhl_id][name]
	// heading_levels[pvhl_id][h2_id]
	// heading_levels[pvhl_id][h2_name]
	// heading_levels[pvhl_id][h1_id]
	// heading_levels[pvhl_id][h1_name]
	//
	$heading_levels = array();
	
	$sql = "select h1.pvhl_id as h1_id, h1.heading_name as h1_name, null as h2_id, null as h2_name, null as h3_id, null as h3_name
		from previews_hdg_lvls h1
		where h1.pvhl_level = 1
		union
		select h1.pvhl_id as h1_id, h1.heading_name as h1_name, h2.pvhl_id as h2_id, h2.heading_name as h2_name, null as h3_id, null as h3_name
		from previews_hdg_lvls h2, previews_hdg_lvls h1
		where h2.pvhl_level = 2 and h1.pvhl_id = h2.parent_pvhl_id
		union
		select h1.pvhl_id as h1_id, h1.heading_name as h1_name, h2.pvhl_id as h2_id, h2.heading_name as h2_name, h3.pvhl_id as h3_id, h3.heading_name as h3_name
		from previews_hdg_lvls h3, previews_hdg_lvls h2, previews_hdg_lvls h1
		where h3.pvhl_level = 3 and h2.pvhl_id = h3.parent_pvhl_id and h1.pvhl_id = h2.parent_pvhl_id
		order by h1_name, h2_name, h3_name";
	if (!($result = $mysqli->query($sql))) {
		throw new mysqli_sql_exception('Error on SELECT from the previews_hdg_lvls table (level = 1) - ' . $mysqli->error, $mysqli->errno);
	}
	while ($row = $result->fetch_assoc()) {
		if (isset($row['h3_id'])) {
			$heading_levels[$row['h3_id']] = array('lvl' => 3, 'name' => $row['h3_name'], 'h2_id' => $row['h2_id'], 'h2_name' => $row['h2_name'], 'h1_id' => $row['h1_id'], 'h1_name' => $row['h1_name']);
		} elseif (isset($row['h2_id'])) {
			$heading_levels[$row['h2_id']] = array('lvl' => 2, 'name' => $row['h2_name'], 'h1_id' => $row['h1_id'], 'h1_name' => $row['h1_name']);
		} else {
			$heading_levels[$row['h1_id']] = array('lvl' => 1, 'name' => $row['h1_name']);
		}
	}
	$result->free();

	// reading in the heading hierarchy data and store it into the headings data structure
	//
	// headings hierarchy data structure layout
	//
	// headings[h1][level 1 heading name][id]
	// headings[h1][level 1 heading name][h2][level 2 heading name][id]
	// headings[h1][level 1 heading name][h2][level 2 heading name][h3][level 3 heading name][id]
	//
	$headings = array();
	
	// read and process level 1 headings
	$sql = crtsql($mysqli, "select pvhl_id, heading_name from previews_hdg_lvls where pvhl_level = 1 order by heading_name");
	if (!($result = $mysqli->query($sql))) {
		throw new mysqli_sql_exception('Error on SELECT from the previews_hdg_lvls table (level = 1) - ' . $mysqli->error, $mysqli->errno);
	}
	while ($row = $result->fetch_assoc()) {
		$headings['h1'][$row['heading_name']] = array('id' => $row['pvhl_id'], 'h2' => null);
	}
	$result->free();

	// read and process level 2 headings
	$sql = crtsql($mysqli, "select 
		h2.pvhl_id as h2_id, h2.heading_name as h2_name, 
		h1.pvhl_id as h1_id, h1.heading_name as h1_name
		from previews_hdg_lvls as h2,
		previews_hdg_lvls as h1
		where h2.pvhl_level = 2
		and h1.pvhl_id = h2.parent_pvhl_id
		order by 
		h1.heading_name, h2.heading_name");
	if (!($result = $mysqli->query($sql))) {
		throw new mysqli_sql_exception('Error on SELECT from the previews_hdg_lvls table (level = 2) - ' . $mysqli->error, $mysqli->errno);
	}
	while ($row = $result->fetch_assoc()) {
		$headings['h1'][$row['h1_name']]['h2'][$row['h2_name']] = array('id' => $row['h2_id'], 'h3' => null);
	}
	$result->free();

	// read and process level 3 headings
	$sql = crtsql($mysqli, "select 
		h3.pvhl_id as h3_id, h3.heading_name as h3_name, 
		h2.pvhl_id as h2_id, h2.heading_name as h2_name, 
		h1.pvhl_id as h1_id, h1.heading_name as h1_name
		from 
		previews_hdg_lvls as h3,
		previews_hdg_lvls as h2,
		previews_hdg_lvls as h1
		where h3.pvhl_level = 3
		and h2.pvhl_id = h3.parent_pvhl_id
		and h1.pvhl_id = h2.parent_pvhl_id
		order by 
		h1.heading_name, h2.heading_name, h3.heading_name");
	if (!($result = $mysqli->query($sql))) {
		throw new mysqli_sql_exception('Error on SELECT from the previews_hdg_lvls table (level = 2) - ' . $mysqli->error, $mysqli->errno);
	}
	while ($row = $result->fetch_assoc()) {
		$headings['h1'][$row['h1_name']]['h2'][$row['h2_name']]['h3'][$row['h3_name']] = array('id' => $row['h3_id'], 'h4' => null);
	}
	$result->free();
	
	// define and prepare insert statements to insert into the previews raw/detail table	
	if (!($pvs_raw_stmt = $mysqli->prepare("insert into previews_raw " . 
	"(pvh_id, pv_seq, pvl_id, pv_type, pv_value, h1_pvhl_id, h2_pvhl_id, h3_pvhl_id, pv_source, sol_page, sol_code, sol_text, release_dt, unit_price, pi_ind) " . 
	"values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"))) {
		throw new mysqli_sql_exception("Error on PREPAIR: INSERT into previews_hdr table - " . $mysqli->error, $mysqli->errno);
	}
	if (!$pvs_raw_stmt->bind_param("iiissiiisisssds", $pvh_id, $pv_seq, $pvl_id, $pv_type, $pv_value, $h1_pvhl_id, $h2_pvhl_id, $h3_pvhl_id, $pv_source, $sol_page, $sol_code, $sol_text, $release_dt, $unit_price, $pi_ind)) {
		throw new mysqli_sql_exception("Error on BIND: INSERT into previews_hdr table - " . $mysqli->error, $mysqli->errno);
	}
	
	$pvh_id = null;
	$pvl_id = null;
	$pvl_seq = null;
	$line_text = null;
	$override_pvhl_id = null;
	
	// define and prepare select statement to retrive lines from the previews_lines table
	if (!($pvs_lns_stmt = $mysqli->prepare("select pvl_id, pvl_seq, line_text, override_pvhl_id from previews_lines where pvh_id = ? order by pvl_seq"))) {
		throw new mysqli_sql_exception('Error on PREPARE: SELECT from the previews_hdr table ' . $mysqli->error, $mysqli->errno);
	}
	
	// bind input variables
	if (!$pvs_lns_stmt->bind_param("i", $pvh_id)) {
		throw new mysqli_sql_exception("Error on BIND PARAM: SELECT from the previews_hdr table - " . $mysqli->error, $mysqli->errno);
	}

	// get the list of preview header records to be processed
	$sql = "select pvh_id from previews_hdr where proc_status in ('NEW', 'REPROCESS') order by period_dt";
	if (!($pvs_hdr_stmt = $mysqli->query($sql))) {
		throw new mysqli_sql_exception('Error on SELECT from the previews_hdr table - ' . $mysqli->error, $mysqli->errno);
	}
	
	// process each previews header record in NEW or REPROCESS status
	while ($pvh_id_row = $pvs_hdr_stmt->fetch_assoc()) {
		
		// get the pvh_id of the previews header record to process the previews lines records for
		$pvh_id = $pvh_id_row['pvh_id'];
		
		print "processing lines for pvh_id: $pvh_id\n\n";
		
		// initialize varible used in processing
		$pv_seq = 1;
		$curr_page = null;
		$hdg_1 = null;
		$hdg_2 = null;
		$hdg_3 = null;
		$h1_pvhl_id = null;
		$h2_pvhl_id = null;
		$h3_pvhl_id = null;
		
		// execute the SQL statement to get the previews lines records for the current value of $pvh_id
		if (!$pvs_lns_stmt->execute()) {
			throw new mysqli_sql_exception("Error on EXECUTE: SELECT from the previews_hdr table - " . $mysqli->error, $mysqli->errno);
		}
		
		// bind the output of the results column to the application variables
		if (!$pvs_lns_stmt->bind_result($pvl_id, $pvl_seq, $line_text, $override_pvhl_id)) {
			throw new mysqli_sql_exception("Error on BIND RESULT: SELECT from the previews_hdr table - " . $mysqli->error, $mysqli->errno);
		}
		
		// store the result set so that we process them unbuffered (needed to execute multiple SQL statements on one db conn)
		if (!$pvs_lns_stmt->store_result()) {
			throw new mysqli_sql_exception("Error on GET RESULT: SELECT from the previews_hdr table - " . $mysqli->error, $mysqli->errno);
		}
		
		// read and process each line of the previews customer order file
		while ($pvs_lns_stmt->fetch()) {
			// each time the fetch() method is executed, the the application variables are set to the results column values
			
			print "==> $pvl_id <==\n";
			
			$row = explode("\t", $line_text);
			print_r($row);
		
			// initialize the values
			$pv_type = 'UNKNOWN';
			$h1_pvhl_id = null;
			$h2_pvhl_id = null;
			$h3_pvhl_id = null;
			$pv_source = null;
			$sol_page = null;
			$sol_code = null;
			$sol_text = null;
			$release_dt = null;
			$unit_price = null;
			$pi_ind = null;

			$pv_value = trim($row[0]);
		
			// check to see if the heading id override is set
			if (isset($override_pvhl_id)) {
			
				// if the override is set to 0 then skip processing it, I guess...
				if ($override_pvhl_id == 0) {
					continue;
				
				// if the override is set AND it is NOT equal to 0 then check to make sure it is in the: reverse heading name lookup data structure
				//		lookup is performed by pvhl_id (preview heading level id) and returns the heading level, heading name, and the pvhl_id and name
				//		of any parent headings
				} else if (!array_key_exists($override_pvhl_id, $heading_levels)) {
					throw new Exception("Could not find override_pvhl_id: $override_pvhl_id");
				}
				
				// get the level & name for the heading level id
				$lvl = $heading_levels[$override_pvhl_id]['lvl'];
				$name = $heading_levels[$override_pvhl_id]['name'];
				
				// set the pv_type, and heading level ids and names accordingly based on the level...
				if ($lvl == 1) {
					$pv_type = 'H1';
					$hdg_3 = null;
					$hdg_2 = null;
					$hdg_1 = $name;
					$h3_id = null;
					$h2_id = null;
					$h1_id = $override_pvhl_id;
					
				} elseif ($lvl == 2) {
					$pv_type = 'H2';
					$hdg_3 = null;
					$hdg_2 = $name;
					$hdg_1 = $heading_levels[$override_pvhl_id]['h1_name'];
					$h3_id = null;
					$h2_id = $override_pvhl_id;
					$h1_id = $heading_levels[$override_pvhl_id]['h1_id'];
					
				} elseif ($lvl == 3) {
					$pv_type = 'H3';
					$hdg_3 = $name;
					$hdg_2 = $heading_levels[$override_pvhl_id]['h2_name'];
					$hdg_1 = $heading_levels[$override_pvhl_id]['h1_name'];
					$h3_id = $override_pvhl_id;
					$h2_id = $heading_levels[$override_pvhl_id]['h2_id'];
					$h1_id = $heading_levels[$override_pvhl_id]['h1_id'];
				}
			
			// check to see if the solicitation code is set
			} elseif (isset($row[1]) && strlen(trim($row[1])) != 0) {
			
				// process the item line
				$sol_code = trim($row[1]);
				$sol_code = substr($sol_code, 0, 5) . substr($sol_code, 6);
				$pv_type = 'ITEM';
				$h1_pvhl_id = $h1_id;
				$h2_pvhl_id = $h2_id;
				$h3_pvhl_id = $h3_id;
				
				// the next line was needed because in the FEB09_COF.txt file had 2 tab characters
				// between the solicitation code and the solicitation text (instead of just 1 tab)
				// so if the normal position for the solicitation text is blank
				// then adjust the index for all columns past the solicitation code by 1 
				$off_set = (strlen(trim($row[2])) == 0 ? 1 : 0 );
				
				$sol_text = trim($row[2 + $off_set]);
				$sol_page = $curr_page;
				// 2014-08-24: note perviously, there was a bug in the next line - when the release date
				// in the text is blank, strtotime returns a value of false, so date() is
				// not returing the proper value. This has been corrected
				// old code
				// $release_dt = date('Y-m-d', strtotime(trim($row[3 + $off_set])));
				// new code start
				$rel_dt = strtotime(trim($row[3 + $off_set]));
				$release_dt = ( $rel_dt === false ? null : date('Y-m-d', $rel_dt) );
				// new code end
				$unit_price = trim($row[4 + $off_set]);
			
				// set the value to null if it is blank for the item line
				if (strlen($pv_value) == 0) {
					$pv_value = null;
				}
			
				// set the source to the level 1 heading / level 2 heading (if level 2 present) / level 3 heading (if level 2 and level 3 present)
				// there should always be a be a level 1 heading.... if there isn't then the pv_type should be unknown
				$pv_source = (isset($hdg_1) ? $hdg_1 . (isset($hdg_2) ? " / $hdg_2" . (isset($hdg_3) ? " / $hdg_3" : '') : '') : null);
		
				// determine the unit price / please inquire indicator
				if (substr($unit_price, -2) == 'PI') {
					$pi_ind = 'Y';
					$unit_price = null;
				} else {
					$pi_ind = null;
					if (substr($unit_price, 0, 3) == 'SRP') {
						$unit_price = floatval(substr($unit_price, 6));
					} elseif (substr($unit_price, 0, 4) == 'MSRP') {
						$unit_price = floatval(substr($unit_price, 7));
					} else {
						// if the unit price didn't begin with SRP or MSPR
						// then is it an unhandled unit price type
						$unit_price = null;
						$pi_ind = 'E';
					}
				}
			
			// else its not an item (must be a blank, page, ident or heading line)
			} else {
				// the solicitation code is blank 
			
				// if the solicitation code is blank and the value is blank, then it is a blank line
				if ($pv_value == '') {
					$pv_type = 'BLANK';
					$pv_value = null;
				
				// if the solicitation code is blank and the value equals to page, then it is a page number line
				} elseif (substr($pv_value, 0, 4) == 'PAGE') {
					$pv_type = 'PAGE';
					$curr_page = substr($pv_value, 5);
				
				// if the solicitation code is blank and the value matches the reglare expression for the identificaiton line, then it is a identification line
				} elseif (preg_match('/^PREVIEWS (JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC).* V(OL){0,1}\.{0,1} {0,1}\d\d #\d{1,2}$/', $pv_value)) {
					$pv_type = 'IDENT';
				
				// if the solicitation code is blank and none of the above types match, it should be a heading line (or an unknown line type)
				} else {
				
					//
					// if there is a level 2 heading (with or without a level 3 heading), then check to see if we can find a level 3 heading match
					//
					if (isset($hdg_2) && isset($headings['h1'][$hdg_1]['h2'][$hdg_2]['h3'][$pv_value]['id'])) {
						$pv_type = 'H3';
						$hdg_3 = $pv_value;
						$h3_id = $headings['h1'][$hdg_1]['h2'][$hdg_2]['h3'][$pv_value]['id'];
				
					//	
					// if there is a level 2 heading 
					//    and if a level 3 heading match was not found
					//    then check if there is a level 2 heading match
					// or
					// if there is a level 1 heading (with or without a level 2 heading)
					//    then check if there is a level 2 heading match
					//
					} elseif ((isset($hdg_2) || isset($hdg_1)) && isset($headings['h1'][$hdg_1]['h2'][$pv_value]['id'])) {
						$pv_type = 'H2';
						$hdg_3 = null;
						$hdg_2 = $pv_value;
						$h3_id = null;
						$h2_id = $headings['h1'][$hdg_1]['h2'][$pv_value]['id'];
					
					//
					// if there is a level 2 heading 
					//    and if a level 3 heading match was not found
					//    and if a level 2 heading match was not found
					//    then check if there is a level 1 heading match
					// or
					// if there is a level 1 heading (with or without a level 2 heading)
					//    and if a level 2 heading match was not found
					//    then check if there is a level 1 heading match
					// or 
					// if a level 1 heading has not yet be set
					//    then check if there is a level 1 heading match
					} elseif (isset($headings['h1'][$pv_value]['id'])) {
						$pv_type = 'H1';
						$hdg_3 = null;
						$hdg_2 = null;
						$hdg_1 = $pv_value;
						$h3_id = null;
						$h2_id = null;
						$h1_id = $headings['h1'][$pv_value]['id'];
					
					//
					// if none of the above heading checks did not find a match
					//    then set pv_type to heading not found
					} else {
						$pv_type = 'NOTFOUND';
					}
				}
			}
			
			if ($pv_type == 'H1' || $pv_type == 'H2' || $pv_type == 'H3') {
				$h1_pvhl_id = $h1_id;
				$h2_pvhl_id = $h2_id;
				$h3_pvhl_id = $h3_id;
			}
			
			// insert record into the previews raw/details table
			if (!$pvs_raw_stmt->execute()) {
				throw new mysqli_sql_exception("Error on EXECUTE: INSERT into previews_hdr table - " . $mysqli->error, $mysqli->errno);
			}
		
			$pv_seq++;
		}
	
	}
	$pvs_hdr_stmt->free();
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


print "\n\n**** ENDING ****\n\n";
exit;

// get headings for level 1 
// SELECT pvhl_id, heading_name FROM previews_hdg_lvls WHERE pvhl_level = 1 order by heading_name

// get headings for level 2 with level 1 parent heading
// select 
// h2.pvhl_id, h2.heading_name, 
// h1.pvhl_id, h1.heading_name
// from previews_hdg_lvls as h2,
// previews_hdg_lvls as h1
// where h2.pvhl_level = 2
// and h1.pvhl_id = h2.parent_pvhl_id
// order by 
// h1.heading_name, h2.heading_name

// get headings for level 3 with level 2 parent heading
// select 
// h3.pvhl_id, h3.heading_name, h2.pvhl_id, h2.heading_name
// from 
// previews_hdg_lvls as h3,
// previews_hdg_lvls as h2
// where h3.pvhl_level = 3
// and h2.pvhl_id = h3.parent_pvhl_id
// order by 
// h2.heading_name, h3.heading_name

// query to get a hierarchial listing of preview headings
// select 
// h1.pvhl_id, h1.heading_name, 
// h2.pvhl_id, h2.heading_name,
// h3.pvhl_id, h3.heading_name
// from previews_hdg_lvls as h1 
// left join previews_hdg_lvls as h2 on h2.parent_pvhl_id = h1.pvhl_id 
// left join previews_hdg_lvls as h3 on h3.parent_pvhl_id = h2.pvhl_id
// where h1.pvhl_level = 1
// order by 
// h1.pvhl_level, h1.parent_pvhl_id, h1.heading_name, 
// h2.pvhl_level, h2.parent_pvhl_id, h2.heading_name,
// h3.pvhl_level, h3.parent_pvhl_id, h3.heading_name


// query to find and add missing headings
// SELECT pv_seq, h1_pvhl_id, h2_pvhl_id, h3_pvhl_id, pv_type, pv_value  FROM `previews_raw` WHERE `pvh_id` = 11 AND `pv_type` IN ('H1', 'H2', 'H3', 'H4', 'NOTFOUND') order by pv_seq

// multiple record insert
// insert into previews_hdg_lvls
// (heading_name, pvhl_level, parent_pvhl_id, pull_list_ind) values
// ('JAPANESE ANIME MAGAZINES', 2, 319, NULL),
// ('JAPANESE BOOKS', 2, 319, NULL),

// example of an excel spreadsheet to build the insert statements
// seq	type	value	lvl	parent id	pii	parent	sql
// 5012	NOTFOUND	IMPORTED ADULT PUBLICATIONS	2	319			=CONCATENATE("('",C2,"', ",D2,", ",IF(E2<>"",CONCATENATE(E2,", "),"NULL, "),IF(F2="Y","'Y'","NULL"),"),")

// seq	h1	h2	h3	type	value	lvl	parent id	pii	parent	sql
// =CONCATENATE("('",F2,"', ",G2,", ",IF(H2<>"",CONCATENATE(H2,", "),"NULL, "),IF(I2="Y","'Y'","NULL"),"),")

// sql to remove records from the previews_raw table that are marked with a proc_status of NEW
// delete from previews_raw where pvh_id in (SELECT PVH_ID FROM PREVIEWS_HDR WHERE PROC_STATUS = 'NEW')

?>











