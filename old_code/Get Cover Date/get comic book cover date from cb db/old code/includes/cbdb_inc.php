<?php

/* *******************************
 *
 *        CBdb Library
 *
 *  A library of functions for interacting with the CBdb website
 *
 * *******************************/


	// the hour of the day when pulls are reset (24 hr based)
	define('PULL_TIME', 6);
	
	$root_img_url = 'http://comicbookdb.com/graphics/comic_graphics';
	$root_img_path = WEB_ROOT . CBDB_IMG_PATH;
	
	
// ### # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # ####  
// ###																									###
// ###			public functions																		###
// ###																									###
// ### # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # ####  


//	
// for the series ID passed in, checks to see if the issue list for the 
// given series id can be pulled. If not formats a message indicating
// when the issue list for the given series ID can next be pulled.
//
function get_pull_status($ser_id, &$hms_to_next_pull) {
	$pull_status = false;
	$hms_to_next_pull = null;
	
	$sql = sprintf("select cs.cil_series_pull_dt from series as ser join cbdb_series as cs on cs.cs_id = ser.cs_id where ser.ser_id = %s", $ser_id);
	
	$result = mysql_query($sql);
	if ($result === false) {
		throw new exception("Get cil_series_pull_dt - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1300);
	}
	$row = mysql_fetch_assoc($result);
	if ($row === false) {
		throw new exception("The error while retrieving the last pull date", 1002);
	}
	
	// if the last time the series was pulled is null
	// then the series has not been pulled before and return true
	if (!isset($row['cil_series_pull_dt'])) {
		$pull_status = true;
		
	} else {
		
		// get the date-time when the issue list can be pulled next
		$next_pull_date = get_next_pull_date($row['cil_series_pull_dt'], PULL_TIME);
		
		// get the current date-time
		$now = time();
		
		// if the current date-time is greater than or equal to the next date-time the 
		// issue list can be pulled then return true
		if ($now >= $next_pull_date) {
			$pull_status = true;
			
		} else {
	
			// if pull_status is false then determine how long before the issue list can pulled again
			$hms_to_next_pull = secs_to_hms($next_pull_date - $now);
		}
	}
	
	return $pull_status;
}


function pull_issue_list($ser_id, &$hms_to_next_pull) {

	$sql = sprintf(
			"select ser.cs_id, cs.cser_id, csih.csih_id " . 
			  "from series as ser " . 
			  "join cbdb_series as cs on cs.cs_id = ser.cs_id " .
		 "left join cbdb_series_issue_html as csih on csih.ser_id = ser.ser_id " .
			 "where ser.ser_id = %s", $ser_id);
	
	$result = mysql_query($sql);
	if ($result === false) {
		throw new exception("Get cbdb_series - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1301);
	}
	$row = mysql_fetch_assoc($result);
	if ($row === false) {
		throw new exception("The error while retrieving the cbdb series ID", 1002);
	}
	
	// get the current date-time
	$now = time();
	
	get_series_issue_list($ser_id, $row['cser_id'], $row['cs_id'], $row['csih_id'], $now);
	
	$hms_to_next_pull = secs_to_hms($now);
	
}

function cbdb_issue_exists($ser_id, $iss_num) {
	$iss_exists = false;
	
	// just wip out a quick select count(*) from cbdb_issue_list where ser_id = $ser_id and iss_num = $iss_num
	$sql = sprintf("select count(*) as iss_count from cbdb_issue_list where ser_id = %s and iss_num = %s", $ser_id, $iss_num);
	
	$result = mysql_query($sql);
	if ($result === false) {
		throw new exception("Get cbdb_issue_list count - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1302);
	}
	$row = mysql_fetch_assoc($result);

	// if count > 0 then return true
	if ($row['iss_count'] > 0) {
		$iss_exists = true;
	}
	
	return $iss_exists;
}

function pull_images($ser_id, $iss_num) {
	global $root_img_path;
	
	d("ser_id: $ser_id, iss_num $iss_num");
	
	$session_usr_id = isset($_SESSION['usr_id']) ? $_SESSION['usr_id'] : 2;
	
	$ch = curl_init();
	
	$sql = sprintf("select ihdr.img_path, ihdr.img_hdr_id from series as ser join image_hdr as ihdr on ihdr.img_hdr_id = ser.img_hdr_id where ser.ser_id = %s", $ser_id);
	
	$result = mysql_query($sql);
	if ($result === false) {
		throw new exception("Get image path - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1303);
	}
	$row = mysql_fetch_assoc($result);
	if ($row === false) {
		throw new exception("The error while retrieving the image path for the series", 1002);
	}
	$img_path = $row['img_path'];
	$img_hdr_id = $row['img_hdr_id'];
	
	// get the list of variants for the issue number
	$sql = sprintf(
			"select cil.cil_id, cvl.cvl_id, cvl.thumb_img_id, cvl.large_img_id, cvl.ciss_id, cvl.var_seq, cih.cih_id " . 
			  "from cbdb_issue_list as cil " . 
			  "join cbdb_variant_list as cvl on cvl.cil_id = cil.cil_id " . 
		 "left join cbdb_issue_html as cih on cih.cvl_id = cvl.cvl_id " . 
		     "where cil.ser_id = %s and cil.iss_num = %s", $ser_id, dbtv($iss_num));
	
	$result = mysql_query($sql);
	if ($result === false) {
		throw new exception("Get variants for image pull - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1304);
	}

	// for each variant
	$time_stamp = date('YmdHis');
	while ($dtl_row = mysql_fetch_assoc($result)) {
		d("cil_id: {$dtl_row['cil_id']}, cvl_id: {$dtl_row['cvl_id']}, var_seq: {$dtl_row['var_seq']}");
	
		// ### add logic to check the last time the issue HTML & images were pulled
		//		if more than XX days, repull issue HTML & pull images, if they are different

		// if thumbnail (or large) image id is null then pull the images
		if (!isset($dtl_row['thumb_img_id'])) {
			
			// get cbdb issue html for the cbdb issue id
			$html = get_issue_html($ch, $dtl_row['ciss_id']);
			
			// insert/update the iss_html record for the variant
			// check if cbdb issue html id exists for this issue/variant
			if (isset($dtl_row['cih_id'])) {
				// it is not null - record exists - update html to tabl
				$sql = sprintf("update cbdb_issue_html set iss_html = %s, updt_dt = now() where cih_id = %s", hdbv($html), $dtl_row['cih_id']);
				$result2 = mysql_query($sql);
				if ($result2 === false) {
					throw new exception("update issue html - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1305);
				}
				
			} else {
				// it is null - record does not exist - insert html to table
				$sql = sprintf("insert into cbdb_issue_html (cvl_id, iss_html) values(%s, %s)", $dtl_row['cvl_id'], hdbv($html));
				$result2 = mysql_query($sql);
				if ($result2 === false) {
					throw new exception("insert issue html - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1306);
				}
			}
			
			$thumb_img_file_name = get_img_file_name($iss_num, $dtl_row['var_seq'], 'thumb', $time_stamp);
			$large_img_file_name = get_img_file_name($iss_num, $dtl_row['var_seq'], 'large', $time_stamp);
			
			// parse out image urls from the issue HTML web page
			$no_img = false;
			$no_lg_img = false;

			preg_match('/graphics\/comic_graphics((\/\d*?\/\d*?\/' . $dtl_row['ciss_id'] . '_\d{14}_thumb\.jpg)|\/(nocover).gif)/', $html, &$matches);
			if (count($matches) == 3) {
				$thumb_img_url = $matches[2];
				
				preg_match('/graphics\/comic_graphics(\/\d*?\/\d*?\/' . $dtl_row['ciss_id'] . '_\d{14}_large\.jpg)/', $html, &$matches);
				if (count($matches) != 0) {
					$large_img_url = $matches[1];
				} else {
					$no_lg_img = true;
				}
				
			} else {
				$no_img = true;
			}
			
			// if there was a thumb nail image
			if (!$no_img) {
				// updae current_flg for other records belonging to the same cvl_id to null
				$sql = sprintf("update images set current_flg = NULL where cvl_id = %s and img_type = 'THUMB' and current_flg = 'Y'", $dtl_row['cvl_id']);
				$result2 = mysql_query($sql);
				if ($result2 === false) {
					throw new exception("update images current_flg - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1307);
				}
				
				// insert thumbnail & large image records
				$sql = sprintf("insert into images (img_hdr_id, img_source, cvl_id, img_type, img_url, file_name, current_flg, crt_dt, crt_usr_id, updt_usr_id, revw_status_id) " . 
						"values(%s, 'CBDB', %s, 'THUMB', %s, %s, 'Y', now(), %s, %s, %s)", 
						$img_hdr_id, $dtl_row['cvl_id'], dbv($thumb_img_url), dbv($thumb_img_file_name), $session_usr_id, $session_usr_id, 1);
				$result2 = mysql_query($sql);
				if ($result2 === false) {
					throw new exception("insert thumbnail image - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1308);
				}
				$thumb_img_id = mysql_insert_id();
				
				get_image_file($ch, $thumb_img_url, $root_img_path . $img_path . '/' . $thumb_img_file_name);
	
				$sql = sprintf("update cbdb_variant_list set thumb_img_id = %s where cvl_id = %s",
						$thumb_img_id, $dtl_row['cvl_id']);
				$result2 = mysql_query($sql);
				if ($result2 === false) {
					throw new exception("updae cbdb variants with image ids - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1325);
				}
				
				if (!$no_lg_img) {
					// updae current_flg for other records belonging to the same cvl_id to null
					$sql = sprintf("update images set current_flg = NULL where cvl_id = %s and img_type = 'LARGE' and current_flg = 'Y'", $dtl_row['cvl_id']);
					$result2 = mysql_query($sql);
					if ($result2 === false) {
						throw new exception("update images current_flg - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1307);
					}
					
					$sql = sprintf("insert into images (img_hdr_id, img_source, cvl_id, img_type, img_url, file_name, current_flg, crt_dt, crt_usr_id, updt_usr_id, revw_status_id) " . 
							"values(%s, 'CBDB', %s, 'LARGE', %s, %s, 'Y', now(), %s, %s, %s)", 
							$img_hdr_id, $dtl_row['cvl_id'], dbv($large_img_url), dbv($large_img_file_name), $session_usr_id, $session_usr_id, 1);
					$result2 = mysql_query($sql);
					if ($result2 === false) {
						throw new exception("insert large cover image - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1309);
					}
					$large_img_id = mysql_insert_id();
					
					get_image_file($ch, $large_img_url, $root_img_path . $img_path . '/' . $large_img_file_name);
					
					$sql = sprintf("update cbdb_variant_list set large_img_id = %s where cvl_id = %s",
							$large_img_id, $dtl_row['cvl_id']);
					$result2 = mysql_query($sql);
					if ($result2 === false) {
						throw new exception("updae cbdb variants with image ids - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1325);
					}
				}
			}

					
			
		} // end if
	} // next variant
	
}

function cbdb_unlnk_vars_exists($ser_id, $iss_num) {
	$unlnk_vars_exists = false;
	
	return $unlnk_vars_exists;
}

// ### # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # ####  
// ###																									###
// ###			private functions																		###
// ###																									###
// ### # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # ####  


// inserts or updates the following tables:
//		cbdb_issue_list
//		cbdb_variant_list
//		cbdb_storyarc_list
//		cbdb_sta_issues_list
//		cbdb_series_issue_html
//
// updates the following tables:
//		cbdb_series
//
// selects from the following tables:
//		cbdb_issue_list
//		cbdb_storyarc_list
//		cbdb_sta_issues_list
//
// -- parameters --
//
// $ser_id - passed in from external function call
//		used to insert into cbdb_series_issue_html table
//		used to select from cbdb_issue_list table
//		used to insert into cbdb_issue_list table
// $cser_id - looked up by ser_id from cbdb_series table (get-hdr-info($ser_id) function)
//		used to get (curl) the issue list HTML web page
// $cs_id - looked up by ser_id from series table (get-hdr-info($ser_id) function)
//		used to update the pull date for the cbdb_series table
// $pull_dt - current date-time from the PHP time() function
//		used to update the pull date for the cbdb_series table
//
function get_series_issue_list($ser_id, $cser_id, $cs_id, $csih_id, $pull_dt) {
	$ch = curl_init();
	
	// initialize the array used to look up the cil_ids
	$csta_ids = array();
	$dups_ctr = array();
	
	// initialize record creation count variables
	$cil_cnt = 0;
	$csil_cnt = 0;
	$csl_cnt = 0;
	$cvl_cnt = 0;
	
	$html = get_iss_list_html($ch, $cser_id);
	
	// get the table html for the issue rows
	$rc = preg_match('/<table border="0" cellpadding="1" cellspacing="0">(.*)<\/table><br><br><br>/', $html, &$matches);
	if ($rc != 1) {
		$rc = preg_match('/.*mysql_connect.*max_user_connections.*/', $html, &$matches);
		$err_msg = ( $rc == 1 ?
				"CBDB has reached it maximum connection limit, please try again later." :
				"An unknown error has occured while connecting to the CBDB." );
		throw new Exception($err_msg, 10);
	}
	$table_html = $matches[1];
	
	// write html to table
	if (isset($csih_id)) {
		$sql = sprintf("update cbdb_series_issue_html set ser_iss_list_html = %s where ser_id = %s", hdbv($html), $ser_id);
		$result2 = mysql_query($sql);
		if ($result2 === false) {
			throw new exception("update cbdb_series_issue_html - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1324);
		}
	} else {
		$sql = sprintf("insert into cbdb_series_issue_html (ser_id, ser_iss_list_html) values(%s, %s)", $ser_id, hdbv($html));
		$result2 = mysql_query($sql);
		if ($result2 === false) {
			throw new exception("insert cbdb_series_issue_html - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1310);
		}
	}
	
	// put all html issue rows into an array
	preg_match_all('/<tr>(.*?)<\/tr>/', $table_html, &$matches);
	$rows_html_array = $matches[1];
	array_shift($rows_html_array);
	
	// process each html issue row
	foreach ($rows_html_array as $row_html) {
		
		// parse out each html cell from the html issue row
		preg_match_all('/<td.*?' . '>(.*?)<\/td>/', $row_html, &$matches);
		$cells_html_array = $matches[1];
		
		// initialize variables
		$row_type_ind = null;
		$cbdb_iss_id = null;
		$iss_num = null;
		$iss_name = null;
		$var_desc = null;
		$story_arc_id = null;
		$story_arc_name = null;
		$date = null;
		$csl_id = null;
		
		
		//
		// cell 1: match element #0 - parse out issue number & cbdb issue id
		//
		// <a href="issue.php?ID=216804" class="page_link">586</a><br>
		// <a href="javascript:blocking('issue_213863', 'anchor_213863');"><img src="graphics/icon_plus.gif" alt="" width="9" height="9" border="0" id="anchor_213863"></a> 
		//			<a href="issue.php?ID=213863" class="page_link">584</a><br>
		// &nbsp;
		//
		preg_match('/<a href="issue.php\?ID=(\d*)".*>(.*?)<\/a>|&nbsp;/', $cells_html_array[0], &$matches);
		$results_cnt = count($matches);
		switch ($results_cnt) {
			case 1;
				// variant issue or multiple story arcs
				break;
			case 3:
				// non-variant issue
				$row_type_ind = 'NV';
				$var_seq = 1;
				$cil_id = null;
				$new_cil_rec_flag = false;
				$cbdb_iss_id = trim($matches[1]);
				$iss_num = trim($matches[2]);
				$iss_num = calc_iss_num($iss_num, $cbdb_iss_id, &$dups_ctr);
				break;
			default:
				// error
				throw new exception("Error parsing issue id/number\n" . print_r($row_html, true), 2201);
				continue;
				break;
		}
		
		//
		// cell 3: match element #2 - parse out issue name or issue number & variant description
		//
		// <a href="issue.php?ID=216804">World-Eater!</a><br>
		// <a href="issue.php?ID=213891">(Arthur Adams Variant)</a><br>
		//
		preg_match('/<a href="issue.php\?ID=(\d*)".*>\(?(.*?)\)?<\/a>|&nbsp;/', $cells_html_array[2], &$matches);
		$results_cnt = count($matches);
		switch ($results_cnt) {
			case 1;
				// multiple story arcs
				$row_type_ind = 'MSA';
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
				throw new exception("Error parsing issue name / variant description\n" . print_r($row_html, true), 2202);
				continue;
				break;
		}
		
		//
		// cell 5: match element #4 - parse out story arc name & id
		//
		// <a href="storyarc.php?ID=4133">Three</a><br>
		// &nbsp;
		//
		preg_match('/<a href="storyarc.php\?ID=(\d*)".*>(.*?)<\/a>|&nbsp;/', $cells_html_array[4], &$matches);
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
				throw new exception("Error parsing story arc id/name\n" . print_r($row_html, true), 2203);
				continue;
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
			// break the string into its 2 components month (or other word) & year
			$date_array = explode(' ', $date_str);
			
			// if only 1 element exists it is the year
			if (count($date_array) == 1) {
				$date = $date_array[0];
			// else if the first word is annual then we only have the year
			} elseif (strtoupper($date_array[0]) == 'ANNUAL') {
				$date = $date_array[1];
			// else have both month and year
			} else {
				$date = $date_str;
			}
		}
		
		
		//
		// based on the row type (non-variant issue, variant issue or multiple story arcs)
		// insert the appropriate records:
		//		non-variant issue
		//			- issue
		//			- variant
		//			- story arc (if present and not already processed)
		//			- story arc issue (if present)
		//		variant issue
		//			- variant
		//		multiple story arcs
		//			- story arc (if not already processed)
		//			- story arc issue
		//
		switch ($row_type_ind) {
			case 'NV';
				// non-variant issue
				
				d(sprintf("processeing issue num: %s, var seq: 1, var desc: STD Issue", hdbv($iss_num)));
				
				// get the cil_id if a cbdb_issue_list record exists for the ser_id & iss_num
				$sql = sprintf("select cil_id from cbdb_issue_list where ser_id = %s and iss_num = %s", $ser_id, hdbv($iss_num));
				$result = mysql_query($sql);
				if ($result === false) {
					throw new exception("Get issue html - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1311);
				}
				$row = mysql_fetch_assoc($result);
				
				// if a cil_id doesn't exist for the ser_id & iss_num
				if ($row === false) {
					
					// insert an issue record
					$sql = sprintf("insert into cbdb_issue_list (ser_id, iss_num, iss_name, cover_dt, crt_dt, crt_usr_id, updt_usr_id) " . 
							"values(%s, %s, %s, %s, now(), 2, 2)",
							hdbv($ser_id), hdbv($iss_num), hdbv($iss_name), hdbv($date));
					$result = mysql_query($sql);
					if ($result === false) {
						throw new exception("insert cbdb issue - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1312);
					}
					$cil_id = mysql_insert_id();
					
					// insert a variant record
					$sql = sprintf("insert into cbdb_variant_list (cil_id, var_seq, ciss_id, var_desc, cover_dt, crt_dt, crt_usr_id, updt_usr_id) " . 
							"values(%s, 1, %s, 'STD Issue', %s, now(), 2, 2)",
							hdbv($cil_id), hdbv($cbdb_iss_id), hdbv($date));
					$result = mysql_query($sql);
					if ($result === false) {
						throw new exception("insert cbdb variant - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1312);
					}
					
					// set the new rec flag so we don't have to requery for the other variants if present
					$new_cil_rec_flag = true;
							
				} else {
					
					// update the issue record
					$cil_id = $row['cil_id'];
					
					$sql = sprintf("update cbdb_issue_list set iss_name = %s, cover_dt = %s , updt_dt = now() " . 
							"where cil_id = %s",
							hdbv($iss_name), hdbv($date), hdbv($cil_id));
					$result = mysql_query($sql);
					if ($result === false) {
						throw new exception("update cbdb issue - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1313);
					}
					
					// update the variant record
					$sql = sprintf("update cbdb_variant_list set var_seq = 1, var_desc = 'STD Issue', " . 
							"cover_dt = %s, updt_dt = now() where ciss_id = %s",
							hdbv($date), hdbv($cbdb_iss_id));
					$result = mysql_query($sql);
					if ($result === false) {
						throw new exception("update cbdb variant - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1314);
					}
					
				} // end if cil_id exists
				
				$cil_cnt++;
				$cvl_cnt++;
				$var_seq++;
				
				// if we have a story arc
				if (strlen($story_arc_id) != 0 && strlen($story_arc_name) != 0) {
					process_story_arc($cil_id, $story_arc_id, $story_arc_name, $csta_ids, $csl_cnt, $csil_cnt);
				}
				
				break;
			case 'VAR':
				// variant issue
				
				d(sprintf("processeing variant: sequence %s, desc %s", hdbv($var_seq), hdbv($var_desc)));
				
				if ($new_cil_rec_flag) {
					// insert a variant record
					$sql = sprintf("insert into cbdb_variant_list (cil_id, var_seq, ciss_id, var_desc, cover_dt, crt_dt, crt_usr_id, updt_usr_id) " . 
							"values(%s, %s, %s, %s, %s, now(), 2, 2)",
							hdbv($cil_id), hdbv($var_seq), hdbv($cbdb_iss_id), hdbv($var_desc), hdbv($date));
					$result = mysql_query($sql);
					if ($result === false) {
						throw new exception("insert cbdb variant - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1315);
					}
					d(sprintf("Inserted variant record (id: %s) - rows affected: %s", mysql_insert_id(), mysql_affected_rows()));
					
				} else {
					// update the variant record
					$sql = sprintf("update cbdb_variant_list set var_seq = %s, var_desc = %s, " . 
							"cover_dt = %s, updt_dt = now() where ciss_id = %s",
							hdbv($var_seq), hdbv($var_desc), hdbv($date), hdbv($cbdb_iss_id));
					$result = mysql_query($sql);
					if ($result === false) {
						throw new exception("update cbdb variant - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1316);
					}
					d(sprintf("updated variant record (ciss_id: %s) - rows affected: %s", $cbdb_iss_id, mysql_affected_rows()));
				}
				
				$cvl_cnt++;
				$var_seq++;
				
				break;
			case 'MSA':
				// multiple story arcs
				
				if (strlen($story_arc_id) != 0 && strlen($story_arc_name) != 0) {
					process_story_arc($cil_id, $story_arc_id, $story_arc_name, $csta_ids, $csl_cnt, $csil_cnt);
				} else {
					// ### emit a warning for blank story arc info	### //
					// ### should log it to the log table			### //
				}

				break;
			default:
				// error

				throw new exception("Error determining row type\n" . print_r($row_html, true), 2203);
				continue;

				break;
		}
		
	} // ### end for each - html_row ###
	
	// update the last pull date for the cbdb_series
	$sql = sprintf("update cbdb_series set cil_series_pull_dt = '%s' where cs_id = %s", date('Y-m-d H:i:s', $pull_dt), $cs_id);
	$result = mysql_query($sql);
	if ($result === false) {
		throw new exception("update series last pull date - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1317);
	}

}


// process a story arc html row from the issue list HTML
//
function process_story_arc($cil_id, $story_arc_id, $story_arc_name, &$csta_ids, &$csl_cnt, &$csil_cnt) {
	
	// check if we've previously processed the cbdb story arc id
	if (!array_key_exists($story_arc_id, $csta_ids)) {
		
		// if not attempt to get the csl_id (pk to the story arc table)
		$sql = "select csl_id from cbdb_storyarc_list where csta_id = $story_arc_id";
		$result = mysql_query($sql);
		if ($result === false) {
			throw new exception("get story arc - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1318);
		}
		$row = mysql_fetch_assoc($result);
		
		// if the result is false, the cbdb story arc id doesn't exist on the table
		if ($row === false) {
		
			// insert a story arc record
			$sql = sprintf("insert into cbdb_storyarc_list (csta_id, sta_name, crt_dt, crt_usr_id, updt_usr_id) " . 
					"values(%s, %s, now(), 2, 2)",
					hdbv($story_arc_id), hdbv($story_arc_name));
			// #1062 - Duplicate entry '20' for key 'csta_id_unq'
			$result = mysql_query($sql);
			if ($result === false) {
				throw new exception("insert story arc - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1319);
			}
			$csl_id = mysql_insert_id();
			
		} else {
			// else the csl_id exists on the story arc table
			$csl_id = $row['csl_id'];
			
			// update the story arc record cbdb_storyarc_list set sta_name, updp_dt where csl_id
			$sql = sprintf("update cbdb_storyarc_list set sta_name = %s, updt_dt = now() where csl_id = %s",
					hdbv($story_arc_name), hdbv($csl_id));
			$result = mysql_query($sql);
			if ($result === false) {
				throw new exception("update story arc - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1320);
			}
		}
		$csl_cnt++;
		
		// add it to the associative array so we don't have to look it up again
		$csta_ids[$story_arc_id] = $csl_id;
	} // ### end if - check if we've previously processed the cbdb story arc id ###
	
	// attempt to read the story arc issue record
	$sql = sprintf("select csil_id from cbdb_sta_issues_list where cil_id = %s and csl_id = %s",
			hdbv($cil_id), hdbv($csta_ids[$story_arc_id]));
	$result = mysql_query($sql);
	if ($result === false) {
		throw new exception("get story arc issue - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1321);
	}
	$row = mysql_fetch_assoc($result);
	
	// check if the story arc issue record exists
	if ($row === false) {
	
		// insert a story arc issue record
		$sql = sprintf("insert into cbdb_sta_issues_list (cil_id, csl_id, crt_dt, crt_usr_id, updt_usr_id) " . 
				"values(%s, %s, now(), 2, 2)",
				hdbv($cil_id), hdbv($csta_ids[$story_arc_id]));
		$result = mysql_query($sql);
		if ($result === false) {
			throw new exception("insert story arc issue - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1322);
		}
		
	} else {
		// update the story arc issue record
		$sql = sprintf("update cbdb_sta_issues_list set updt_dt = now() where csil_id = %s", $row['csil_id']);
		$result = mysql_query($sql);
		if ($result === false) {
			throw new exception("update story arc issue - SQL ERR: " . mysql_error() . " (". mysql_errno() . ")\n$sql\n", 1323);
		}
	}
	$csil_cnt++;
}

//
// calculate the next pull date base the last time the issue list for the series was pulled
//
// sch_pull_time must be an integer representing the scheduled hour to pull the data on
//
// the next pull date is either the scheduled pull date/time or 24 hrs after
// the last pull date, which ever is sooner.
//
function get_next_pull_date($last_pull, $sch_pull_time) {

	$last_pull_dt = (substr($last_pull, 4, 1) == '-' ? strtotime($last_pull) : $last_pull);
		
	$schd_pull_dt = mktime($sch_pull_time, 0, 0, date('m', $last_pull_dt), date('d', $last_pull_dt) + 1, date('Y', $last_pull_dt));
	$hr24_pull_dt = mktime(date('H', $last_pull_dt), date('i', $last_pull_dt), date('s', $last_pull_dt), date('m', $last_pull_dt), date('d', $last_pull_dt) + 1, date('Y', $last_pull_dt));
	
	return ($hr24_pull_dt < $schd_pull_dt ? $hr24_pull_dt : $schd_pull_dt);
}


// convert secs to Hour Minutes Seconds format, where secs is from 0 secs to 86400 secs
//
function secs_to_hms($secs) {
	return ($secs == 86400 ? '24 hrs 00 mins 00 secs' : date("H \h\\r\s i \m\i\\n\s s \s\e\c\s", mktime(0,0,0,1,1,2011) + $secs));
}

function get_img_file_name($iss_num, $var_seq, $size, $time_stamp) {
	return str_pad($iss_num, 3, '0', STR_PAD_LEFT) . '_' . str_pad($var_seq, 2, '0', STR_PAD_LEFT) . "_{$size}_{$time_stamp}.jpg";
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
function get_iss_list_html($ch, $cser_id) {
	
	$url = "http://comicbookdb.com/title.php?ID=$cser_id";
	
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
		throw new exception("Error getting issue list html: $url {$headers[0]}", 2003);
	}
	
	return $html;
}

// gets the HTML web page for the issue for the given cbdb issue ID
//
function get_issue_html($ch, $ciss_id) {
	
	$url = "http://comicbookdb.com/issue.php?ID=$ciss_id";
	
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
		throw new exception("Error getting issue html: $url {$headers[0]}", 2002);
	}
	
	return $html;
}

// get the image specified by the URL and saves it to the path/filename.ext as specified in $file_spec
//
function get_image_file($ch, $url, $file_spec) {
	global $root_img_url;
	
	$url = $root_img_url . $url;
	
// 	$path = dirname($file_spec);
// 	if (!file_exists($path)) {
// 		if (!mkdir($path, 0777, true)) {
// 			throw new exception("Error could not create path $path for image", 2101);
// 		}
// 	}
	
	$tmpfns = tempnam(sys_get_temp_dir(), "http");
	$pfh = fopen($tmpfns, 'w+');

	$fh = fopen($file_spec, 'wb');
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-us) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4');
	curl_setopt($ch, CURLOPT_FILE, $fh);
	curl_setopt($ch, CURLOPT_WRITEHEADER, $pfh);
	
	curl_exec($ch);
	fclose($fh);
	
	fseek($pfh, 0);
	$headers = explode("\n", fread($pfh, filesize($tmpfns)));
	fclose($pfh);
	unlink($tmpfns);

	if (trim($headers[0]) != 'HTTP/1.1 200 OK') {
		throw new exception("Error getting image: $url {$headers[0]}", 2001);
	}
	
}

function d($msg = null, $var = null) {
	global $debug;
	
	if (!$debug) {
		return;
	}
	
	$val = isset($var) && is_array($var) ? print_r($var, true) : $var;
	$label = isset($msg) ? "$msg: " : '';
	
	print $label . $val . "\n";
}

?>