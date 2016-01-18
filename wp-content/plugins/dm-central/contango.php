<?php

// returns an array of ['bid', 'ask']
// $productID is the Dillon Gage code for the product, tier is the volume tier (see below)
function dm_inv_dg_prices($productID, $tier = 1) {

	// web service connection
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // this needs to go
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'User-Agent: My-funky-user-agent'));

	$options = get_option('imag_mall_options_tech');
	
	if ($options['stage_prod'] == 'staging') {
		$svcURL = SERVICE_URL_STAGE;
		$dealerToken = $options['dealer_token_staging'];
	} else {
		$svcURL = SERVICE_URL;
		$dealerToken = $options['dealer_token'];
	}
	
	$url = $svcURL . '/FizServices/GetPrices/' . $dealerToken . '/' . $productID;
	curl_setopt($curl, CURLOPT_URL , $url);
	
	$json = curl_exec($curl);
	
	curl_close($curl);
	
	$result = json_decode($json, true);

	if (isset($result['error'])) {
		$msg = "Couldn't retrieve ". $productID ." price: " . $result['error'];
		error_log($msg);
		return new WP_Error('CURL_Error', $msg);
	} else if (!isset($result['code'])) {
		// there was an exception
		$msg = "Couldn't retrieve ". $productID ." price: Got:\n" .  
			str_replace('&nbsp;', '', htmlspecialchars_decode(strip_tags($json)));
		error_log($msg);
		return new WP_Error('FizServices_Error', $msg);
	} 
	
	return $result['tiers'][$tier];
}

// returns an array of ['bid', 'ask']
// $productID is the Dillon Gage code for the product (or an array of DG codes), tier is the volume tier (see below)
function dm_inv_dg_prices_for_products($productIDs, $tier = 1) {
	if (!is_array($productIDs))
		$productIDs = array($productIDs);
		
	$post_body = '["'. implode('","', $productIDs) . '"]';

	// web service connection
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // this needs to go
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/plain', 'User-Agent: My-funky-user-agent'));
	curl_setopt($curl, CURLOPT_POST , true);
	curl_setopt($curl, CURLOPT_POSTFIELDS , $post_body);

	$options = get_option('imag_mall_options_tech');
	
	if ($options['stage_prod'] == 'staging') {
		$svcURL = SERVICE_URL_STAGE;
		$dealerToken = $options['dealer_token_staging'];
	} else {
		$svcURL = SERVICE_URL;
		$dealerToken = $options['dealer_token'];
	}
	
	$url = $svcURL . '/FizServices/GetPricesForProducts/' . $dealerToken;
	curl_setopt($curl, CURLOPT_URL , $url);
	
	$json = curl_exec($curl);
	
	curl_close($curl);
	
	$result = json_decode($json, true);

	if (isset($result['error'])) {
		$msg = "Couldn't retrieve prices for ". implode(',', $productIDs) .": " . $result['error'];
		error_log($msg);
		return new WP_Error('CURL_Error', $msg);
	} else if (!isset($result[0]['code'])) {
		// there was an exception
		$msg = "Couldn't retrieve prices for ". implode(',', $productIDs) .": Got:\n" .  
			str_replace('&nbsp;', '', htmlspecialchars_decode(strip_tags($json)));
		error_log($msg);
		return new WP_Error('FizServices_Error', $msg);
	} 
	
	$output = array();
	foreach($result as $product) {
		$output[$product['code']] = $product['tiers'][$tier];
	}	
	
	return $output;
}

function dm_inv_dg_ask($productCode, $tier = 1) {
	$prices = dm_inv_dg_prices($productCode, $tier);
	if (is_wp_error($prices))
		return $prices;
	else
		return $prices['ask'];
}

function dm_inv_dg_bid($productCode, $tier = 1) {
	$prices = dm_inv_dg_prices($productCode, $tier);
	if (is_wp_error($prices))
		return $prices;
	else
		return $prices['bid'];
}
// TODO: price tiers

?>