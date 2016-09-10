<?php

define('WEB_ROOT', (substr($_SERVER['DOCUMENT_ROOT'], -1) == '/' ? substr($_SERVER['DOCUMENT_ROOT'], 0, -1) : $_SERVER['DOCUMENT_ROOT']));
define('IMG_PATH', '/cover_images');
define('CBDB_IMG_PATH', IMG_PATH . '/cbdb');
define('LOCAL_IMG_PATH', IMG_PATH . '/local');
define('CURRENT_IMG_PATH', IMG_PATH . '/current');
define('NO_IMG', IMG_PATH . '/nocover.png');


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

function get_post_num($name, $default) {
	return (isset($_POST[$name]) && is_numeric($_POST[$name])) ? $_POST[$name] : $default;
}

function get_post_val($name, $default) {
	return (isset($_POST[$name]) && $_POST[$name] != '') ? $_POST[$name] : $default;
}

// use to create a new element in the errors array used by ExtJS to display post
// server side process validation messages
function val_error($field, $msg) {
	global $errors;
	$errors[$field] = $msg;
}

function mk_fn_compat($val) {
	return strtolower(preg_replace('/\W/','-', preg_replace('/\s/', '_', $val)));
}

function get_img_path($pub_name, $ser_name, $ser_year) {
	$pub_name = mk_fn_compat($pub_name);
	$pub_init = substr($pub_name, 0, 1);

	$ser_name = mk_fn_compat($ser_name);
	$ser_init = substr($ser_name, 0, 1);

	$ser_year = mk_fn_compat($ser_year);

	return "/$pub_init/$pub_name/$ser_init/$ser_name/$ser_year";
}

function make_dir($path) {
	if (!file_exists($path)) {
		if (!mkdir($path, 0777, true)) {
			throw new exception("Error could not create path $path for image", 2101);
		}
	}
}

function rrmdir($p) {
	if (is_dir($p)) {
		foreach (scandir($p) as $d) {
			if ($d != '.' && $d != '..') {
				if (!rrmdir("$p/$d")) {
					return false;
				}
			}
		}
		if (!rmdir($p)) {
			return false;
		}
	} else {
		if (!unlink($p)) {
			return false;
		}
	}
	return true;
}

function is_dir_empty($p) {
	$da = scandir($p);
	foreach ($da as $d) {
		if($d != '.' && $d != '..') {
			return false;
		}
	}
	return true;
}

?>