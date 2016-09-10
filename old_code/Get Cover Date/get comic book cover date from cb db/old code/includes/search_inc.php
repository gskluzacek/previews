<?php

function full_upc_search_results($full_upc, &$arr, &$local_hdr_arr, &$local_dtl_arr, &$cbdb_arr) {
	
	$local_hdr_arr = array();
	$local_dtl_arr = array();
	$cbdb_arr = array();
	
	$sql = sprintf("select var.var_id, var.cvl_id, var.var_ident, var.var_name, var.full_upc, iss.iss_id, iss.ser_id from variants as var join issues as iss on iss.iss_id = var.iss_id where full_upc = %s", dbtv($full_upc));
	
	If (!$result = mysql_query($sql)) {
		$err_msg = 'A database error occurred, could not complete requested operation - ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
		throw new Exception($err_msg, 201);
	}
	
	// if no results then return false - full upc not found
	if (mysql_num_rows($result) != 1) {
		return false;
	}
	
	$row = mysql_fetch_assoc($result);
	$arr[] = array('var_lable' => $row['var_ident'] . ' - ' . $row['var_name'], 'full_upc' => $row['full_upc']);
	
	//
	// get local issue data  (only 1 record)
	//
	$sql = sprintf("select pub.name as pub, ctry.ctry_code as ser_ctry, ctry.name as ctry_name, ser.name as ser_name, " . 
			"year(ser.start_dt) as ser_year, iss.disp_num as iss_num, iss.iss_name, iss.iss_tag_line, " . 
			"ser_typ.description as ser_typ, ser_sts.description as ser_sts, med_typ.description as iss_format, " . 
			"iss.page_count, iss_month.description as iss_month, year(iss.cover_dt) as iss_year, " . 
			"format(iss.cover_price, 2) as cover_price, curr.curr_code, curr.currency as curr_name, iss.notes as iss_notes " . 
			"from publishers as pub join series as ser on pub.pub_id = ser.pub_id " . 
			"join issues as iss on iss.ser_id = ser.ser_id " . 
			"left join country as ctry on ctry.ctry_id = ser.ctry_id " . 
			"left join currency as curr on curr.curr_id = iss.curr_id " . 
			"join enums as ser_typ on ser_typ.enum_id = ser.typ_id and ser_typ.type = 'series.typ_id' " . 
			"left join enums as ser_sts on ser_sts.enum_id = ser.status_id and ser_sts.type = 'series.status_id' " . 
			"join enums as med_typ on med_typ.enum_id = ser.media_typ_id and med_typ.type = '.media_typ_id' " . 
			"join enums as iss_month on iss_month.enum_id = iss.cover_period_id and iss_month.type = '.cover_period_id' and iss_month.category = 'MONTHLY' " . 
			"where ser.ser_id = %s and iss.iss_id = %s",
			$row['ser_id'], $row['iss_id']);
	
	If (!$result = mysql_query($sql)) {
		$err_msg = 'A database error occurred, could not complete requested operation - ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
		throw new Exception($err_msg, 207);
	}
	$local_hdr_row = mysql_fetch_assoc($result);
	$local_hdr_arr[] = $local_hdr_row;
	
	//
	// get local variant data (all variants for a given issue id)
	//
	
	// ### if we've found the exact match, then why are we selecting all the variants? Espeically if the user will not be allowed to
	//     change the variant? ###
	
	$sql = sprintf("select var.var_ident, var.var_name, var_typ.description as var_typ, printing.description as printing, " . 
			"var.full_upc, var_month.description as var_month, year(cover_dt) as var_year, format(cover_price, 2) as cover_price, " . 
			"curr.curr_code, curr.currency as curr_name, var.var_desc, var.notes as var_notes " . 
			"from variants as var " . 
			"left join currency  as curr on curr.curr_id = var.curr_id " . 
			"join enums as var_typ on var_typ.enum_id = var.var_typ_id and var_typ.type = 'variant.variant_typ_id' " . 
			"join enums as printing on printing.enum_id = var.printing_id and printing.type = 'variants.printing_id' " . 
			"join enums as var_month on var_month.enum_id = var.cover_period_id and var_month.type = '.cover_period_id' and var_month.category = 'MONTHLY' " . 
			"where var.var_id = %s",
			$row['var_id']);
	
	If (!$result = mysql_query($sql)) {
		$err_msg = 'A database error occurred, could not complete requested operation - ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
		throw new Exception($err_msg, 208);
	}
	
	while ($local_dtl_row = mysql_fetch_assoc($result)) {
		$local_dtl_arr[] = $local_dtl_row;
	}
	
	//
	// get cbdb issue data (only 1 record)
	//
	
	$sql = sprintf("select cs.cser_name, cs.cser_year, cp.cpub_name, cil.cil_id, cil.iss_num, " .
			"cil.iss_name, cvl.cvl_id, cvl.ciss_id, cvl.var_seq, cvl.cover_dt, cvl.var_desc, " .
			"imgt.link_name as thumb, imgl.link_name as large, imgi.link_name as indicia, " .
			"imgu.link_name as upc, ihdr.img_path " .
			"from variants as var " .
			"join issues as iss on iss.iss_id = var.iss_id " .
			"join series as ser on ser.ser_id = iss.ser_id " .
			"join cbdb_series as cs on cs.cs_id = ser.cs_id " .
			"join cbdb_variant_list as cvl on cvl.cvl_id = var.cvl_id " .
			"join cbdb_issue_list as cil on cil.cil_id = cvl.cil_id " .
			"join cbdb_publishers as cp on cp.cp_id = cs.cp_id " .
			"join images as imgt on imgt.img_id = var.thumb_img_id " .
			"join images as imgl on imgl.img_id = var.large_img_id " .
			"join images as imgi on imgi.img_id = var.indicia_img_id " .
			"join images as imgu on imgu.img_id = var.upc_img_id " .
			"join image_hdr as ihdr on ihdr.img_hdr_id = cs.img_hdr_id " .
			"where var.var_id = %s", 
			$row['var_id']);
	
	If (!$result = mysql_query($sql)) {
		$err_msg = 'A database error occurred, could not complete requested operation - ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
		throw new Exception($err_msg, 209);
	}
	$cbdb_row = mysql_fetch_assoc($result);
	$cbdb_row['img_path'] = CURRENT_IMG_PATH . $cbdb_row['img_path'] . '/';
	
	$cbdb_arr[] = $cbdb_row;
	
	return true;
}

function base_upc_search_results($full_upc, &$arr) {
	
	$base_upc = substr($full_upc, 0, 12);
	$iss_num_upc = intval(substr($full_upc, 12,3));
	$var_upc = intval(substr($full_upc, 15, 1));
	$prntg_upc = intval(substr($full_upc, 16, 1));
	
	$sql = sprintf("select ser.ser_id, ser.media_typ_id, ctry.def_curr_id from series as ser left join country as ctry on ctry.ctry_id = ser.ctry_id where ser.base_upc = %s limit 0, 1", $base_upc);
	
	If (!$result = mysql_query($sql)) {
		$err_msg = 'A database error occurred, could not complete requested operation - ' . 
				mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
		throw new Exception($err_msg, 203);
	}
	
	// if no results then return false - base upc not found
	if (mysql_num_rows($result) != 1) {
		return false;
	}
	
	$row = mysql_fetch_assoc($result);
	
	$ser_id = $row['ser_id'];
	$media_typ_id = $row['media_typ_id'];
	$def_curr_id = $row['def_curr_id'];
	
	if ($var_upc > 1) {
		$variant_code = 'ALT';
	} elseif ($prntg_upc > 1) {
		$variant_code = 'ADPRNT';
	} else {
		$variant_code = 'STD';
	}
	
	$var_ident1c = ($prntg_upc == 1 ? '' : chr(ord('A') + $prntg_upc - 2));
	$var_ident2n = ord('A') + $var_upc - 1;
	$var_ident = $var_ident1c . chr($var_ident2n);
	
	// get form data needed to display the series/issue and variant forms' defaults
	// fyi... sort sequence is the printing sort sequence as derived from the 17 digit of the UPC. in the front end code, it is used to lookup the sort sequence of the printing enum and the the printing description (i.e., first, second, third, etc.)
	$arr[] = array('ser_id' => $ser_id, 'media_typ_id' => $media_typ_id, 'full_upc' => $full_upc, 'iss_num' => $iss_num_upc, 
			'sort_seq' => $prntg_upc, 'variant_code' => $variant_code, 'var_ident' => $var_ident, 'curr_id' => $def_curr_id);
	$count = count($arr);
	
	return true;
	
}

function publisher_upc_search_results($full_upc, &$arr) {
	
	$arr = array();
	
	$sql = sprintf("select pub_id, company_prefix from publishers where company_prefix = %s or company_prefix = %s or company_prefix = %s or company_prefix = %s",
			dbtv(substr($full_upc, 0, 9)), dbtv(substr($full_upc, 0, 8)), dbtv(substr($full_upc, 0, 7)), dbtv(substr($full_upc, 0, 7)));
	
	If (!$result = mysql_query($sql)) {
		$err_msg = 'A database error occurred, could not complete requested operation - ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
		throw new Exception($err_msg, 204);
	}
	
	// if no results then return false - company prefix not found
	if (mysql_num_rows($result) != 1) {
		return false;
	}
	
	// only will pass back the first publisher ID found
	// ### need a way to pass back multiple & to allow the user to select the correct one ###
	$row = mysql_fetch_assoc($result);
	
	// need to pass back the pub_id to default the publisher drop down
	// and the base_upc of the series
	$arr[] = array('pub_id' => $row['pub_id'], 'company_prefix' => $row['company_prefix'], 'base_upc' => substr($full_upc, 0, 12));
	
	return true;
}

function get_all_cbdb_variants($ser_id, $iss_num, &$cbdb_hdr_arr, &$cbdb_dtl_arr) {

		$cbdb_hdr_arr = array();
		$cbdb_dtl_arr = array();

		// get the header level cbdb variant details: series name & yr, pub name, issue number & name, cil_id and image path
		//
		$sql = sprintf("select cs.cser_name, cs.cser_year, cp.cpub_name, cil.cil_id, cil.iss_num, cil.iss_name, ihdr.img_path " . 
				"from series as ser join cbdb_series as cs on cs.cs_id = ser.cs_id " .
				"join cbdb_publishers as cp on cp.cp_id = cs.cp_id " . 
				"join cbdb_issue_list as cil on cil.ser_id = ser.ser_id " . 
				"join image_hdr as ihdr on ihdr.img_hdr_id = cs.img_hdr_id " .
				"where ser.ser_id = %s and cil.iss_num = %s", 
				$ser_id, $iss_num);
		
		If (!$result = mysql_query($sql)) {
			$err_msg = 'A database error occurred, could not complete requested operation - ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
			throw new Exception($err_msg, 205);
		}
		$cbdb_hdr_row = mysql_fetch_assoc($result);
		
		$cbdb_hdr_arr[] = array('cser_name' => $cbdb_hdr_row['cser_name'], 'cser_year' => $cbdb_hdr_row['cser_year'], 'cpub_name' => $cbdb_hdr_row['cpub_name'], 
				'cil_id' => $cbdb_hdr_row['cil_id'], 'iss_num' => $cbdb_hdr_row['iss_num'], 'iss_name' => $cbdb_hdr_row['iss_name'], 
				'img_path' => CBDB_IMG_PATH . $cbdb_hdr_row['img_path'] . '/', 'no_img' => NO_IMG);
		
		// get the cbdb variant details: cvl_id, cbdb issue id, variant cover date, description & sequence #, cover thumbnail & large cover image file names
		//
		$sql = sprintf("select cvl.cvl_id, cvl.ciss_id, cvl.var_seq, cvl.cover_dt, cvl.var_desc, imgt.file_name as thumb, imgl.file_name as large " .
				"from cbdb_variant_list as cvl left join images as imgt on imgt.img_id = cvl.thumb_img_id " . 
				"left join images as imgl on imgl.img_id = cvl.large_img_id " . 
				"where cvl.cil_id = %s order by cvl.var_seq", $cbdb_hdr_row['cil_id']);
		
		If (!$result = mysql_query($sql)) {
			$err_msg = 'A database error occurred, could not complete requested operation - ' . mysql_errno() . ': ' . mysql_error() . "\n$sql\n"; 
			throw new Exception($err_msg, 206);
		}
		
		while ($cbdb_dtl_row = mysql_fetch_assoc($result)) {
			$cbdb_dtl_arr[] = $cbdb_dtl_row;
		}

}

?>