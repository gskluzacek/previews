<?php 

// FILENAME:	db_common.inc.php
//
// AUTHORE:		Greg Skluzacek
// CREATED:		2013-03-23
//
// The db_common.inc.php include file contains common database functions for working
// with MySQL databases and the Mysqli php API: http://www.php.net/manual/en/book.mysqli.php
//
// List of Functions:
//
// crtsql		- used to create SQL statements from a SQL statement with placeholders
//


// 
// FUNCTION:	crtsql()
// 
// PARMETERS:	$conn		- a mysqli object representing the db connection to use
//				$sql		- a string representing the sql statement to parse, containing
//							  0 or more sql parameter placeholders of either: '%n' or '%s'
//				$1v...$Vn	- variable number of 0 or more parameter values 
//
// FUNCTION SIGNATURE:
//
// str $sql crtsql(mysqli $conn, str $sql, [mixed $pval,...])
//
// FUNCTION SUMMARY:
//
// The crtsql() funciton is used to create an fully escapped SQL statement string.
//
// FUNCTION DETAILS:
//
// As the 2nd parameter, the function takes a SQL statement string that contains 
// 0 to n sql parameter placeholders. Place holders start with a '%' (percent sign) and
// are followd by either an 'n' (numeric) or an 's' (for all other data types, including
// date-time data types). For example, %n or %s. To specify a '%' use '%%'.
//
// Values for each of the place holders are specified after the 2nd parameter ($sql) and
// there must be an equal number of paramter values and placeholder. The function can handle
// any number of sql parameter/value pairs. To specify a SQL NULL value pass in the PHP null
// value.
//
// All non null parameter values are escapped with the mysqli::real_escape_string() method
// that escapes the following characters: ASCII 0, \n, \r, backslash (\), single quote ('),
// double quote (") and ctrl-Z. This is to prevent any SQL injection attacks.
// Refer to: http://www.php.net/manual/en/mysqli.real-escape-string.php
//
// Note: currently the function does not handle the scenario when NULL is a valid value
// as part of a SQL WHERE clause. For example, "select * from a_tbl_name where col1 = %s"
// if null is passed as the value for the sql parameter placeholder the following would
// be returned "select * from a_tbl_name where col1 = NULL" which is NOT valid SQL. This
// functionality may be included in the future...
// one posibility would be to use %S and %N sql parameter placeholders and then pass a sql
// string like "select * from a_tbl_name where col1 %S" and the function would return either:
// "select * from a_tbl_name where col1 = 'a value'" or "select * from a_tbl_name where col1 is NULL"
// 
// EXAMPLE CALLS:
//
// $sql = crtsql($conn, "select col1, col2, col3 from a_tbl_name where col4 = %n and col5 = %s",
// 123, 'abd');
//
// $sql = crtsql($conn, "update a_table_name set col2 = %s where col1 = %n", 'abd', 123);
//
// $sql = crtsql($conn, "insert into a_tbl_name (col_str1, col_num1, col_num2, col_str2, col_dt) " . 
// "values(%s, %n, %n, %s, %s)", 'a string', 1.2, 3, null, '2013-03-23 14:18:10');
// $id = $conn->insert_id;
//
// Note if your sql insert statement inserts into a table with an auto-incrementing integer
// for the primary key, you can get the pk value for the newly inserted record via 
// mysqli::$insert_id property as shown above.
// Refer to: http://www.php.net/manual/en/mysqli.insert-id.php
//

function crtsql($conn, $sql) {
	// get all the sql parameter values that follow the 2nd parameter (can have 0 to n pvals)
	$vals = array_slice(func_get_args(), 2);
	
	// replace any %% before processing the $sql string
	$sql = str_replace('%%', '~~~PERCENT_SIGN_CHARACTER~~~', $sql);
	
	// parse the $sql sting into chuncks. the resulting chunks will contain both sql fragments
	// as well as the sql paramater placeholders.
	$sql_chunks = preg_split("/(%[sn])/", $sql, null, PREG_SPLIT_DELIM_CAPTURE);
	
	$param = 0;
	$sql_prepared = '';
	
	// process each sql chunk, either adding it to the parsed sql string or replacing the chunk
	// with the corresponding paramater placeholder value.
	foreach($sql_chunks as $chnk) {
		
		// check if the chunk is a sql parameter placeholder
		if ($chnk == '%s' || $chnk == '%n') {
			
			// check if the number of sql parmeter placeholders has exceeded the number of
			// passed in parameter values
			if ($param + 1 > count($vals)) {
				throw new Exception('The number of sql parameters (' . ($param + 1) . ') exceeded the number of values passed in (' . count($vals) . ').');
			}
			$val = $vals[$param++];
			
			// get the data type of the parameter value and do basic type type checking
			$typ = gettype($val);
			
			// if the parameter value is PHP null then append the SQL NULL constant to the parsed SQL String
			if (isset($val)) {
			
				// if the sql parameter placeholder is not the numeric type
				if ($chnk == '%s') {
					// then data type can be any string (including validly formated date string), integer or floating point number
					if ($typ != 'string' && $typ != 'integer' && $typ != 'double') {
						throw new Exception("Invalid type of $typ found for parameter: $param");
					}
					$val = "'" . $conn->escape_string($val) . "'";
				
				} else {
					// for numeric type sql parameter placeholders, make sure we have an integer or floating point number
					if ($typ != 'integer' && $typ != 'double') {
						throw new Exception("Expected a numberic value, found type of $typ instead, for parameter: $param");
					}
					$val = $conn->escape_string($val);
				}
				$sql_prepared .= $val;
			} else {
				$sql_prepared .= 'NULL';
			}
			
		// if not a sql parameter placeholder then just append the chunk to the end of the prepared sql string
		} else {
			$sql_prepared .= $chnk;
		}
	}
	
	// make sure the number of parameter values did not exceed the number of sql parameter placeholders
	if ($param < count($vals)) {
		throw new Exception('The number of values passed in (' . count($vals) . ') exceeded the number of sql parameters (' . $param . ').');
	}
	
	// now unescape any %% to %
	$sql_prepared = str_replace('~~~PERCENT_SIGN_CHARACTER~~~', '%', $sql_prepared);
	
	return $sql_prepared;
}

?>