<?php

	define('DB_HOST', 'yourhost');
    define('DB_USER', 'youruser');
    define('DB_PASSWORD', 'yourpassword');
   	define('DB_DATABASE', 'yourdatabase');
   	
   	//Connect to mysql server
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(!$link) {
		die('Failed to connect to server: ' . mysql_error());
	}
	
	//Select database
	$db = mysql_select_db(DB_DATABASE);
	if(!$db) {
		die("Unable to select database");
	}

?>