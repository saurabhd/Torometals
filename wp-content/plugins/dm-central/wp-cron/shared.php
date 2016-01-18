<?php
global $dealerToken, $logFilePath;
	
if (ini_get('date.timezone') === false)
	date_default_timezone_set('America/Chicago');

$logFilePath = dirname(__FILE__) . '/logs/' . date('Y-m-d');

// write to error log and cron log
function logError($msg) {	
	global $logFilePath;
	error_log('WP_Cron - ' . $msg);
	error_log(date('H:i:s') . ' - [error]'. $msg ."\n", 3, $logFilePath);
}

// write to cron log
function logInfo($msg) {	
	global $logFilePath;	
	error_log(date('H:i:s') . ' - [info]'. $msg ."\n", 3, $logFilePath);
}

?>
