<?php 

require_once 'db_common.inc.php';

try {
	// open a database connection
	$mysqli = @new mysqli('localhost', 'root', 'root', 'bipolar');
	if ($mysqli->connect_errno) {
		throw new mysqli_sql_exception('Could not conncet to database - ' . $mysqli->error, $mysqli->connect_errno);
	}
	
	$sql = "select pv_source, sol_text from bipolar.previews_raw where pv_type = 'ITEM' " . 
//		"and pvh_id = 52 " . 
//		"and pvr_id in (1029522) " .
		"and (pv_source like 'COMICS%' or pv_source like 'PREMIER PUBLISHERS%' " . 
		"or pv_source like 'COMICS%' or pv_source like 'BOOKS & MAGAZINES / COMICS%' " . 
		"or pv_source like 'BOOKS / COMICS%') and (sol_text like '%#%' or sol_text like '%ONE SHOT%')" . 
		"order by pv_source, sol_text";

	if (!($result = $mysqli->query($sql))) {
		throw new mysqli_sql_exception('Error on SELECT from the previews_raw table - ' . $mysqli->error, $mysqli->errno);
	}
	while ($row = $result->fetch_assoc()) {
		if (preg_match('/\b(T\/S|GN|HC|SC|TP|STATUE|POSTER|IPHONE|FIG|FIGURE|MAG|AF|MICRO SERIES|MICROSERIES)\b/i', $row['sol_text']) == 1) {
			continue;
		}
		
		$issue_num = '';
		$title_type = '';
		
		if (preg_match('/(.*?) (((ONE SHOT) #([\w.#\-]+))|(ONE SHOT)|(#([\w.#\-]+))) ?(.*)/', $row['sol_text'], $matches) == 1) {
			$title = $matches[1];
			$str_tokens = $matches[9];
			
			if ($matches[6] == 'ONE SHOT') {
				$issue_num = '1';
				$title_type = 'ONE SHOT';
			} elseif ($matches[4] == 'ONE SHOT') {
				$issue_num = $matches[5];
				$title_type = 'ONE SHOT';
			} else {
				$issue_num = $matches[8];
			}
			
			$issue_num = str_replace('#', '', $issue_num);
			
			if (preg_match('/(.*?) ?\(?ONGOING\)? ?(.*)/i', $title, $matches_ongoing) == 1) {
				$title = $matches_ongoing[1] . (strlen($matches_ongoing[2]) != 0 ? " {$matches_ongoing[2]}" : '');
				$title_type = 'ONGOING';
			}
			
			$total_issues = '';
			$caution_code = '';
			$cover_variant = '';
			$advisory_code = '';
			$sol_info_code = '';
			$prev_sol_code = '';
			$printing = '';
			$other_designations = '';
			$other_tokens = '';
			$packaged_set = '';
			
			if ($str_tokens != '') {
				preg_match_all('/(\([^(]+\))|([^(]+)\b/', $str_tokens, $matches_tokens);
				$arr_tokens = $matches_tokens[0];
				$ot = array();
				
				for ($i = 0; $i < count($arr_tokens); $i++) {
					$t = trim($arr_tokens[$i]);
					
					// doesn't parse correctly
					// STAR WARS KNIGHT ERRANT #4 AFLAME PT 4 ( OF 5) (C: 1-0-0)
					// thinking the should be 4 of 5 ?
					
					// issue number n of m: (OF m)
					if (preg_match('/^\(OF (\d+)\)$/i', $t, $m) == 1) {
						if ($total_issues == '' && $i == 0) {
							$total_issues = $m[1];
							$title_type = 'LIMIT SERIES';
							continue;
						}
					}

					// check for certain values that should not be tokenized, 
					// add them back to the previous token and next token as necessary
					if (preg_match('/\(((((PT )?\d{1,2})? )?OF \d{1,2}) ?\)/i', $t, $m) == 1) {
						$ndx = count($ot) - 1;
						$ndx = ($ndx < 0 ? 0 : $ndx);
						
						$ot[$ndx] .= " " . trim($m[1]);
						
						if ($i + 1 < count($arr_tokens)) {
							$next_token = trim($arr_tokens[$i + 1]);
							if (substr($next_token, 0, 1) != '(') {
								$ot[$ndx] .= " " . $next_token;
								$t = $ot[$ndx];
								array_pop($ot);
								$i++;
							} else {
								continue;
							}
						} else {
							continue;
						}
					}
					
					// caution code: (C: n-n-n)
					if (preg_match('/^\(C: (\d-\d-\d)\)$/i', $t, $m) == 1) {
						$caution_code = $m[1];
						continue;
					}
					
					// printing: nth PTG
					if (preg_match('/([^(]*)\b((CURR|NEW|(\d{1,2}\w\w)) PTG)\b([^)]*)/i', $t, $m) == 1) {
						$printing = $m[2];
						$t = trim(trim($m[1]) . ' ' . trim($m[5]));
						if (strlen($t) == 0) {
							continue;
						}
					}
					
					// printing: nth PRINTING
					if (preg_match('/\b((CURR|NEW|\d{1,2}\w\w) PRINTING)\b/i', $t, $m) == 1) {
						$printing = "{$m[2]} PTG";
						continue;
					}
					
					// variants
					if (preg_match('/\b(SUB(S?CRIPTION)? VAR(IANT)?|AUXILIARY|AUTHENTIX|C2E2|WIZARD WORLD CHICAGO|VARIANT|VAR|SE|ED|EDITION|NUDE|BUDD ROOT SE|EX|EXC|EXCLUSIVE|INCV|LITHO|COPY)$/i', $t, $m) == 1 || 
							preg_match('/\b(CVRS?|COVERS?|CV|S\/N|W\/ SKETCH|SGN|SGND|SIGNED|NEWSSTAND|CGC)\b/i', $t, $m) == 1) {
						$cover_variant = $t;
						continue;
					}
					
					// packaged sets, bundles & combo-packs
					if (preg_match('/(BOX|BAG|VAR|VARIANT|LTD ED|COMPLETE|COLLECTOR|REMARK) SET$/i', $t, $m) == 1 ||
							preg_match('/(COMBO|COLLECTORS|COMPLETE) PACK$/i', $t, $m) == 1 || $t == 'SET' || $t == 'PACK'
//							preg_match('//i', $t, $m) == 1
					) {
						$packaged_set = $t;
//						var_dump($m);
//						print "### pkg set ###\n";
						continue;
					}
					
					// advisory code: (MR) or (A)
					if ($t == '(MR)' || $t == '(A)') {
						$advisory_code = substr($t, 1, -1);
						continue;
					}
					
					// solicitation information code: (RES) or (O/A)
					if ($t == '(RES)' || $t == '(O/A)') {
						$sol_info_code = substr($t, 1, -1);
						continue;
					}
					
					// other designations: (xxxxx)
					if ($t == '(Net)' || $t == '(NOTE PRICE)' || $t == '(PD)' || $t == '(OSW)') {
						$other_designations .= (strlen($other_designations) != 0 ? ', ' : '') . substr($t, 1, -1);
						continue;
					}
					
					// other designations: xxxxx
					if ($t == 'NOW') {
						$other_designations .= (strlen($other_designations) != 0 ? ', ' : '') . $t;
						continue;
					}
					
					if (preg_match('/^\((((JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)\d{6})|(STAR\d{5}))\)$/', $t, $m) == 1) {
						$prev_sol_code = $m[1];
						continue;
					}
					
					if (preg_match('/^(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC|JANUARY|FEBRUARY|MARCH|APRIL|JUNE|JULY|AUGUST|SEPT|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER) \d{4}$/', $t, $m) == 1) {
						$prev_sol_code = $m[1];
						continue;
					}
					
					$ot[] = str_replace(array('(', ')'), '', $t);
				}
				$other_tokens = implode("\t", $ot);
			}
// text	title	iss num	of num	type	printing	caution	advisory	sol info	other	variant	prev sol	pkg set	x1	x2	x3			
			print "{$row['sol_text']}\t{$title}\t{$issue_num}\t{$total_issues}\t{$title_type}\t{$printing}\t{$caution_code}\t{$advisory_code}\t{$sol_info_code}\t{$other_designations}\t{$cover_variant}\t{$prev_sol_code}\t{$packaged_set}\t{$other_tokens}\n";
		}
	
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


print "\n\n**** ENDING ****\n\n";
exit;



?>