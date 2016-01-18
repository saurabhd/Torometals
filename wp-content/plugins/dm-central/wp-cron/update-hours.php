<?php
// wp_cron runs this

$options = get_option('imag_mall_options_tech');
if ($options['stage_prod'] == 'staging') {
	$svc_url = SERVICE_URL_STAGE . '/FizServices';
	$dealerToken = $options['dealer_token_staging'];
} elseif ($options['stage_prod'] == 'production') {
	$svc_url = SERVICE_URL . '/FizServices';
	$dealerToken = $options['dealer_token'];
}
if (empty($dealerToken)) {
	logError('No token supplied - can\'t retrieve products.');
	exit;
}

// database connection
// $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
// if ($mysqli->connect_errno) {
    // logError("Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	// exit;
// }

logInfo('Beginning retrieval of market hours.');

// web service connection
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'User-Agent: My-funky-user-agent'));

$url = $svc_url . "/GetMarketSchedule/" . $dealerToken;
curl_setopt($curl, CURLOPT_URL , $url);

$json = curl_exec($curl);

$result = json_decode($json, true);

// we don't write to the json file if there was an error retrieving data
if (isset($result['error'])) {
	logError("Couldn't retrieve market hours: " . $result['error']);
} else if (!isset($result[0]['dayId'])) {
	// there was an exception
	logError("Couldn't retrieve market hours: Got:\n" . $json);
} else {
	foreach($result as $session) {
		if ($session['status'] != 'open')  // don't care about closed sessions
			continue;
	
		$metal = strtolower($session['metalType']);
		$day = $session['dayId'];  // Sunday is 0
		$session_start = new DateTime($session['startTime'], new DateTimeZone('America/Chicago'));
		$session_end = new DateTime($session['endTime'], new DateTimeZone('America/Chicago'));
		
		$hours[$metal][$day][] = array('start' => $session_start, 'end' => $session_end);
	}
	
	$hours = serialize($hours);
	// $hours_gold = serialize($hours['gold']);
	// $hours_silver = serialize($hours['silver']);
	// $hours_platinum = serialize($hours['platinum']);
	// $hours_palladium = serialize($hours['palladium']);
	
	$changed = update_site_option('fiztrade_schedule', $hours);
	//$success = $mysqli->query("UPDATE ". MYSQLI_DB .".wp_sitemeta SET meta_value = '$hours' WHERE site_id = 1 AND meta_key = 'fiztrade_schedule';");
	
	if ($changed)
		logInfo('Market hours updated.');
	else
		logInfo('Market hours unchanged.');
}


?>