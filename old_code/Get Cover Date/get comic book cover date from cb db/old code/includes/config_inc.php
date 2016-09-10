<?php

///////////////////////////////////////
//
// Config Include PHP
//
// read configuration setting in from a JSON file
//
///////////////////////////////////////
//
// version: 1.0
// Date: 18-Feb-2010
// Author: Greg Skluzacek - maccodemonkey.com
//
// initial release
//
///////////////////////////////////////
//
// A signle config file can be used accross all the different environments
// (dev, test, qa, prod, etc.) used in developing a web site. Each web site
// must have a distinct domain/subdomain name and a distinct environment name
// i.e., the combination of environment and hostname must be unique, for example:
//		dev		sandbox.example.com
//		test	testbox.example.com
//		qa		alpha.example.com
//		qa		beta.example.com
//		prod	www.example.com
//		prod	dr.example.com
//
// additionally there are 3 hierarchical levels of settings:
//		default		base level, all other setting levels are applied on top of the default level
//		<env_name>	where <env_name> is the name of the environment, this level is applied next
//		<host_name> where <host_name> is the name of the host, this level is applied last
//
// values for names at higher levels will override the same name at a lower level
// for example: if at the default level you have a config setting name called dbuser and its
// value is set to 'dbadmin' and at the host name level, you have the same config setting
// name, but with a value of 'superuser', the final value will be 'superuser' as the values
// at the hostname level override the values at the default level.
//
// to read the configuration setting, use the get_config() function
// The funciton takes the following parameters
//		$file - the file name of the configuration file
//		$host - the host name of the web server hosting the web site
//				this can be obtained from  $_SERVER['SERVER_NAME']
//
// the get_config() function returns a nested associative array with the following main keys:
//
//		[config_values]			these are the config values that the script should use for the
//								environment and host passed in
//		[config_all]			these are all config settings as read in from the .json config file
//		[config_params]			these are the paramters used to process the config file:
//			[env]				the environment parsed from the hostname setting
//			[host]				the hostname passed in
//			[config_file]		the name of the config file passed in
//		[config_status]			nested associative array with parsing status and messages:
//			[sts_value]			0 - OK, 1 - NOTICE, 2 - WARNING, 4 - ERROR
//			[sts_code]			OK, NOTICE, WARNING, ERROR
//			[config_msgs]		an array of associative arrays with details of each processing message
//				[msg_value] 	0 - OK, 1 - NOTICE, 2 - WARNING, 4 - ERROR
//				[msg_status]	OK, NOTICE, WARNING, ERROR
//				[msg_code]		unique code used to represent the message text
//				[msg_text]		text with the details of the message
//
// example of the minimum config file:
//
// { "default" : {},
//   "<hostname>": { "env" : "<environment name>" },
//   "<environment name>" : { } 
// }
// 
// the above config will generate 3 notices as the default, environment and hostname do not have
// any config setting. The "env" setting in the hostname config section is required and is linked
// to a corresponding environment config section below it and hence is not considered an actual
// config setting.
//
// a more realistic example would have multiple keys/value pairs for each section and have multiple
// hostnames and enviroments:
//
// { "default" : {
//			"key1" : "val1",
//			"key2" : "val2"
// }, "hostname1" : {
//			"env"  : "test",
//			"key1" : "hn1-val1",
//			"key3" : "hn1-val3"
// }, "hostname2" : {
//			"env"  : "prod",
//			"key1" : "hn2-val1",
//			"key3" : "hn2-val3"
// }, "test" : {
//			"key2" : "env1-val2",
//			"key4" : "env1-val4"
// }, "prod": {
//			"key2" : "env2-val2",
//			"key4" : "env2-val4"
// }}
//
///////////////////////////////////////
// 
// The following PHP code below, will create the preceeding example json config file
//
// example of how to generate the json config file, the last line outputs the content that 
// should be saved in the config file.
//
// $aa_config = array(
// 	'default' => array(
// 		'key1' => 'val1',
// 		'key2' => 'val2'),
// 	'hostname1' => array(
// 		'env' => 'test',
// 		'key1' => 'hn1-val1',
// 		'key3' => 'hn1-val3'),
// 	'hostname2' => array(
// 		'env' => 'prod',
// 		'key1' => 'hn2-val1',
// 		'key3' => 'hn2-val3'),
// 	'test' => array(
// 		'key2' => 'env1-val2',
// 		'key4' => 'env1-val4'),
// 	'prod' => array(
// 		'key2' => 'env2-val2',
// 		'key4' => 'env2-val4')
// 	);
//
// print json_encode($aa_config);
//
///////////////////////////////////////
// 
// example of how to read and subsequently process the config settings
//
// $config = get_config('test2_config.json', $_SERVER['SERVER_NAME']);
// 
// foreach($config['config_values'] as $key => $val) {
// 	 print "$key => $val\n";
// }
// 
// print_r($config);
//
///////////////////////////////////////


function conf_sts_msg(&$sts, $sts_value = 0, $msg_code = '', $msg_text = '') {
	$sts_code = array(0 => 'OK', 1 => 'NOTICE', 2 => 'WARNING', 4 => 'ERROR');
	if ($sts_value == 0) {
		$sts = array('config_status' => array('sts_value' => $sts_value, 'sts_code' => $sts_code[$sts_value], 'config_msgs' => array()));
	} else {
		if ($sts_value > $sts['config_status']['sts_value']) {
			$sts['config_status']['sts_value'] = $sts_value;
			$sts['config_status']['sts_code'] = $sts_code[$sts_value];
		}
		$sts['config_status']['config_msgs'][] = array('msg_value' => $sts_value, 'msg_status' => $sts_code[$sts_value], 'msg_code' => $msg_code, 'mgs_text' => $msg_text);
	}
}

function get_config($file, $host) {
	$config = array();
	$host_env = null;
	$def_config = array();
	$env_config = array();
	$host_config = array();
	$all_config = array();

	conf_sts_msg($sts);
	
	if (!is_file($file)) {
		conf_sts_msg($sts, 4, 'CF1', 'config file not found or is not a readable file entity');
		$config_txt = '';
	} else {
		$config_txt = file_get_contents($file);
		if (strlen($config_txt) == 0) {
			conf_sts_msg($sts, 4, 'CF2', 'config file empty or not readable');
		} else {
		
			$all_config = json_decode($config_txt, TRUE);
			if (!isset($all_config)) {
				conf_sts_msg($sts, 4, 'CF3', 'config file not in valid format');
			} else {
				if (count($all_config) == 0) {
					conf_sts_msg($sts, 4, 'CF4', 'config file empty');
				} else {
					
					// check for default configuration
					if (! array_key_exists('default', $all_config)) {
						conf_sts_msg($sts, 4, 'CF10', 'no default configuration found');
					} else {
					
						if (!isset($all_config['default'])) {
							conf_sts_msg($sts, 2, 'CF5', 'default configuration not set');
						} else {
							
							if (! is_array($all_config['default'])) {
								conf_sts_msg($sts, 4, 'CF14', 'invalid default configuration');
							} else {
								$def_config = $all_config['default'];
								if (count($def_config) == 0) {
									conf_sts_msg($sts, 1, 'CF15', 'default configuration has no key/value pairs');
								}
							}
						}
					}
					
					// make sure host param is set
					if(!isset($host)) {
						conf_sts_msg($sts, 4, 'CF11', 'host name is not set');
					} else {
						
						// check for host configuration
						if (! array_key_exists($host, $all_config)) {
							conf_sts_msg($sts, 4, 'CF12', 'no host configuration found');
						} else {
							if (!isset($all_config[$host])) {
								conf_sts_msg($sts, 4, 'CF6', 'host configuration not set');
							} else {
								
								if (! is_array($all_config[$host])) {
									conf_sts_msg($sts, 4, 'CF16', 'invalid host configuration');
								} else {
									$host_config = $all_config[$host];
									if (count($host_config) == 0) {
										conf_sts_msg($sts, 4, 'CF15', 'host configuration has no key/value pairs');
									} else {
										
										// make sure env configuration in host config is set
										if (! array_key_exists('env',$host_config)) {
											conf_sts_msg($sts, 4, 'CF7', 'no host environment key found');
										} else {
											if (! isset($host_config['env'])) {
												conf_sts_msg($sts, 2, 'CF8', 'host environment key not set');
											} else {
											
												$host_env = $host_config['env'];
												
												// make sure specified env config exists
												if (! array_key_exists($host_env, $all_config)) {
													conf_sts_msg($sts, 4, 'CF9', 'no environment configuration found');
												} else {
													if (! isset($all_config[$host_env])) {
														conf_sts_msg($sts, 1, 'CF13', 'environment configuration not set');
													} else {
													
														if (! is_array($all_config[$host_env])) {
															conf_sts_msg($sts, 4, 'CF18', 'invalid environment configuration');
														} else {
															$env_config = $all_config[$host_env];
															if (count($env_config) == 0) {
																conf_sts_msg($sts, 1, 'CF19', 'environment configuration has no key/value pairs');
															}
														}
													}
												}
											}
											if (count($host_config) == 1) {
												conf_sts_msg($sts, 1, 'CF17', 'other than the requried host environment key, host configuration has no additional key/value pairs');
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	$config = $sts + 
		array('config_params' => array('env' => $host_env, 'host' => $host, 'config_file' => $file)) +
		array('config_values' => array_merge(
			$def_config, 
			$env_config, 
			$host_config)) +
		array('config_all' => $all_config);
	
	return $config;
}

?>
