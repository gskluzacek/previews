<?php

require_once 'db_common.inc.php';

$pvh_id = null;
$pvl_id = null;
$pvl_seq = null;
$line_text = null;
$override_pvhl_id = null;

$mysqli = @new mysqli('localhost', 'root', 'root', 'bipolar');
if ($mysqli->connect_errno) {
	throw new mysqli_sql_exception('Could not conncet to database - ' . $mysqli->error, $mysqli->connect_errno);
}

$sql = "select pvl_id, pvl_seq, line_text, override_pvhl_id from previews_lines where pvh_id = ?";

// Prepares the SQL query, and returns a statement handle to be used for further operations on the statement
// All parameter markers must be bound to application variables using mysqli_stmt_bind_param() before executing the statement
// All returned columns results must be bound to application variables using mysqli_stmt_bind_result() before fetching rows
if (!($pvs_lns_stmt = $mysqli->prepare($sql))) {
	throw new mysqli_sql_exception('Error on PREPARE: SELECT from the previews_lines table ' . $mysqli->error, $mysqli->errno);
}

// Binds variables to a prepared statement as parameters
if (!$pvs_lns_stmt->bind_param("i", $pvh_id)) {
	throw new mysqli_sql_exception("Error on BIND PARAM: SELECT from the previews_lines table - " . $mysqli->error, $mysqli->errno);
}

$pvh_id = 1;


// the execute and bind_result must be executed as a unit in this order: execute, then bind_result
//

// Executes a query that has been previously prepared
// When executed any parameter markers which exist will automatically be replaced with the appropriate data
if (!$pvs_lns_stmt->execute()) {
	throw new mysqli_sql_exception("Error on EXECUTE: SELECT from the previews_lines table - " . $mysqli->error, $mysqli->errno);
}

// Note that all columns must be bound after mysqli_stmt_execute() and prior to calling mysqli_stmt_fetch()

// Binds columns in the result set to variables
// When mysqli_stmt_fetch() is called to fetch data, php places the data for the bound columns into the specified variables 
if (!$pvs_lns_stmt->bind_result($pvl_id, $pvl_seq, $line_text, $override_pvhl_id)) {
	throw new mysqli_sql_exception("Error on BIND RESULT: SELECT from the previews_lines table - " . $mysqli->error, $mysqli->errno);
}

//
// end -------------------------------------------------------------------------------------------


//if (!$pvs_lns_stmt->store_result()) {
//	throw new mysqli_sql_exception("Error on STORE RESULT: SELECT from the previews_lines table - " . $mysqli->error, $mysqli->errno);
//}
	
print "\n\nRetriving lines for pvh_id: $pvh_id\n";

// Fetch results from a prepared statement into the bound variables
$pvs_lns_stmt->fetch();
print "\n====\n";
print_r(array($pvl_id, $pvl_seq, $override_pvhl_id, $line_text));

$pvs_lns_stmt->fetch();
print "\n====\n";
print_r(array($pvl_id, $pvl_seq, $override_pvhl_id, $line_text));

// Frees stored result memory for the given statement handle
// $pvs_lns_stmt->free_result();

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
	$row = $result->fetch_assoc();
	print_r($row);
	$result->free();


$pvh_id = 2;

if (!$pvs_lns_stmt->execute()) {
	throw new mysqli_sql_exception("Error on EXECUTE: SELECT from the previews_lines table - " . $mysqli->error, $mysqli->errno);
}

//if (!$pvs_lns_stmt->store_result()) {
//	throw new mysqli_sql_exception("Error on STORE RESULT: SELECT from the previews_lines table - " . $mysqli->error, $mysqli->errno);
//}
	
print "\n\nRetriving lines for pvh_id: $pvh_id\n";

// Fetch results from a prepared statement into the bound variables
$pvs_lns_stmt->fetch();
print "\n====\n";
print_r(array($pvl_id, $pvl_seq, $override_pvhl_id, $line_text));

$pvs_lns_stmt->fetch();
print "\n====\n";
print_r(array($pvl_id, $pvl_seq, $override_pvhl_id, $line_text));

// Frees stored result memory for the given statement handle
$pvs_lns_stmt->free_result();


// Closes a prepared statement
if (!$pvs_lns_stmt->close()) {
	throw new mysqli_sql_exception("Error on CLOSE: SELECT from the previews_lines table - " . $mysqli->error, $mysqli->errno);
}

$mysqli->close();

?>