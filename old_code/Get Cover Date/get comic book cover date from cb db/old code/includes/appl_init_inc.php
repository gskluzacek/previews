<?php
	
	// can override the location of the config file and server name if need to use a different 
	// config file and server setting or if calling applicaiton initialization from a command line 
	// script by setting the variables $CFOR & $SVOR to the location of the config file and the host
	// name of the server to use.
	//
	$config_file = (isset($CFOR) ? $CFOR : $_SERVER['DOCUMENT_ROOT'] . '/cbudb_config.json');
	$server_name = (isset($SVOR) ? $SVOR : $_SERVER['SERVER_NAME']);
	$appl_config = get_config($config_file, $server_name);
	
	// debugging
	// print_r($appl_config);
	
	$sts = $appl_config['config_status']['sts_value'];
	if ($sts == 4){
		print "\n\none or more errors occurred in retrieving the application configuration\n\n";
		exit;
	}
	$settings = $appl_config['config_values'];
	$db_prefix = "{$settings['appprefix']}_{$settings['envprefix']}_{$settings['dbtprefix']}_";
	$appl_db = db_connect($appl_config);


?>