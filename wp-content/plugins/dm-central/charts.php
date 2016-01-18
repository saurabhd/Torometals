<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// gets the intraday spot prices of metals
// $start and $end should be DateTime objects, $metal is one of 'gold', 'silver', 'platinum', or 'palladium'
// returns an indeterminate number of prices as an array of ('timestamp' => epoch milliseconds, 'data' => spot price) arrays
function dm_inv_spot_history_intraday($start, $end, $metal) {
	//$fd = fopen(dirname(__FILE__) . '/logs/err_log' . date('Y-m-d'), 'a');
	//echo 'Getting intraday prices of '. $metal .' from '. $start->format('Y-m-d H') .' to '. $end->format('Y-m-d H');
	
	// web service connection
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'User-Agent: My-funky-user-agent'));

	$options = get_option('imag_mall_options_tech');
	
	if ($options['stage_prod'] == 'staging') {
		$svcURL = SERVICE_URL_STAGE;
		$token = INT_TOKEN_STAGE;
	} else {
		$svcURL = SERVICE_URL;
		$token = INT_TOKEN;
	}
	
	$url = $svcURL .'/FizServices/GetSpotTickHistoryData/' . $token 
		. '/' . $start->format('Y-m-d%20H') . '/' 
		. $end->format('Y-m-d%20H') . '/' . $metal;
	
	curl_setopt($curl, CURLOPT_URL , $url);

	$json = curl_exec($curl);
	
	$result = json_decode($json, true);
	
	if (isset($result['error'])) {
		// TODO: work out logging
		error_log("Couldn't retrieve ". $metal ." spot history: " . $result['error'], $fd);
	} else if (count($result[0]) < 2) {
		// there was an exception
		$errMsg = "Couldn't retrieve ". $metal ." spot history: ";
		
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$errMsg .= ' No reported errors. Response was: '. $json;
			break;
			case JSON_ERROR_DEPTH:
				$errMsg .= ' Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				$errMsg .= ' Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				$errMsg .= ' Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				$errMsg .= ' Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				$errMsg .= ' Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				$errMsg .= ' Unknown error';
			break;
		}
		error_log($errMsg, $fd);
	} else {
		
	}
	
	$output = array();
	foreach ($result as $dataPoint) {
		$data = $dataPoint['ask'];
		
		$parsed = new DateTime($dataPoint['timestamp'], new DateTimeZone('America/Chicago')); // service returns Central time
		$output[] = array('timestamp' => $parsed->getTimestamp(), 'value' => $data);
	}
	//$output = $result;
		
	curl_close($curl);
	return $output;
}

// gets the market close spot prices of metals
// $start and $end should be DateTime objects, $metal is one of 'gold', 'silver', 'platinum', or 'palladium'
// returns a number of prices as an array of ('timestamp' => epoch milliseconds, 'data' => spot price) arrays
function dm_inv_spot_history_close($start, $end, $metal) {
	//$fd = fopen(dirname(__FILE__) . '/logs/err_log' . date('Y-m-d'), 'a');
	
	// web service connection
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'User-Agent: My-funky-user-agent'));

	$options = get_option('imag_mall_options_tech');
	
	if ($options['stage_prod'] == 'staging') {
		$svcURL = SERVICE_URL_STAGE;
		$token = INT_TOKEN_STAGE;
	} else {
		$svcURL = SERVICE_URL;
		$token = INT_TOKEN;
	}
	
	$url = $svcURL .'/FizServices/GetSpotCloseHistoryData/' . $token 
		. '/' . $start->format('Y-m-d') . '/' 
		. $end->format('Y-m-d') . '/' . $metal;

	curl_setopt($curl, CURLOPT_URL , $url);

	$json = curl_exec($curl);
	
	$result = json_decode($json, true);
	
	if (isset($result['error'])) {
		// TODO: work out logging
		error_log("Couldn't retrieve ". $metal ." close history: " . $result['error'], $fd);
	} else if (count($result[0]) < 2) {
		// there was an exception
		$errMsg = "Couldn't retrieve ". $metal ." spot history: ";
		
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$errMsg .= ' No reported errors. Response was: '. $json;
			break;
			case JSON_ERROR_DEPTH:
				$errMsg .= ' Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				$errMsg .= ' Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				$errMsg .= ' Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				$errMsg .= ' Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				$errMsg .= ' Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				$errMsg .= ' Unknown error';
			break;
		}
		error_log($errMsg, $fd);
	} else {
		
	}
	
	$output = array();
	foreach ($result as $dataPoint) {
		$data = $dataPoint['ask'];
		
		$parsed = new DateTime($dataPoint['date'], new DateTimeZone('America/Chicago')); // service returns Central time
		$output[] = array('timestamp' => $parsed->getTimestamp(), 'value' => $data);
	}
	//$output = $result;
		
	curl_close($curl);
	return $output;
}

// gets the price ratios of gold to other metals
// $start and $end should be DateTime objects, $metal is one of 'gold-silver', 'gold-platinum', or 'gold-palladium'
// returns a number of prices as an array of ('timestamp' => epoch milliseconds, 'data' => ratio) arrays
function dm_inv_spot_history_ratio($start, $end, $metal) {
	//$fd = fopen(dirname(__FILE__) . '/logs/err_log' . date('Y-m-d'), 'a');
	
	// web service connection
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'User-Agent: My-funky-user-agent'));

	$options = get_option('imag_mall_options_tech');
	
	if ($options['stage_prod'] == 'staging') {
		$svcURL = SERVICE_URL_STAGE;
		$token = INT_TOKEN_STAGE;
	} else {
		$svcURL = SERVICE_URL;
		$token = INT_TOKEN;
	}
	
	$url = $svcURL .'/FizServices/GetSpotRatioHistoryData/' . $token 
		. '/' . $start->format('Y-m-d') . '/' 
		. $end->format('Y-m-d') . '/' . $metal;

	curl_setopt($curl, CURLOPT_URL , $url);

	$json = curl_exec($curl);
	
	$result = json_decode($json, true);
	
	if (isset($result['error'])) {
		// TODO: work out logging
		error_log("Couldn't retrieve ". $metal ." ratio history: " . $result['error'], $fd);
	} else if (count($result[0]) < 2) {
		// there was an exception
		$errMsg = "Couldn't retrieve ". $metal ." ratio history: ";
		
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$errMsg .= ' No reported errors. Response was: '. $json;
			break;
			case JSON_ERROR_DEPTH:
				$errMsg .= ' Maximum stack depth exceeded';
			break;
			case JSON_ERROR_STATE_MISMATCH:
				$errMsg .= ' Underflow or the modes mismatch';
			break;
			case JSON_ERROR_CTRL_CHAR:
				$errMsg .= ' Unexpected control character found';
			break;
			case JSON_ERROR_SYNTAX:
				$errMsg .= ' Syntax error, malformed JSON';
			break;
			case JSON_ERROR_UTF8:
				$errMsg .= ' Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
			default:
				$errMsg .= ' Unknown error';
			break;
		}
		error_log($errMsg, $fd);
	} else {
		
	}
	
	$output = array();
	foreach ($result as $dataPoint) {
		$output[] = array('timestamp' => $dataPoint[0] / 1000, 'value' => $dataPoint[1]);
	}
	//$output = $result;
		
	curl_close($curl);
	return $output;
}

// ajax callback
function dm_inv_update_spot_history() {	
	$startES = intval($_GET['start'] / 1000);  // javascript supplies time in epoch milliseconds
	$endES = intval($_GET['end'] / 1000);
	
	$startTime = new DateTime('@'. $startES);
	$startTime->setTimeZone(new DateTimeZone('America/Chicago'));
	$endTime = new DateTime('@'. $endES, new DateTimeZone('America/Chicago'));
	$endTime->setTimeZone(new DateTimeZone('America/Chicago'));
	
	switch ($_GET['series']) {
		case 'intraday':
			$data = dm_inv_spot_history_intraday($startTime, $endTime, $_GET['metal']);
			// convert seconds to milliseconds for javascript
			for ($i = 0; $i<count($data); $i++) {
				$data[$i]['timestamp'] = $data[$i]['timestamp'] * 1000;
			}
			echo json_encode($data);
			break;
		case 'close':
			$data = dm_inv_spot_history_close($startTime, $endTime, $_GET['metal']);
			// convert seconds to milliseconds for javascript
			for ($i = 0; $i<count($data); $i++) {
				$data[$i]['timestamp'] = $data[$i]['timestamp'] * 1000;
			}
			echo json_encode($data);
			break;
		case 'ratio':
			$metal = 'gold-' . $_GET['metal'];
			$data = dm_inv_spot_history_ratio($startTime, $endTime, $metal);
			// convert seconds to milliseconds for javascript
			for ($i = 0; $i<count($data); $i++) {
				$data[$i]['timestamp'] = $data[$i]['timestamp'] * 1000;
			}
			echo json_encode($data);
			break;
	}
	die();
}
add_action('wp_ajax_spot_history', 'dm_inv_update_spot_history');
add_action('wp_ajax_nopriv_spot_history', 'dm_inv_update_spot_history');