<?php
// wp_cron runs this
$debugging = false;

global $new_products, $deleted_products;

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
if (defined('MYSQLI_PORT'))
	$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME, MYSQLI_PORT);
else
	$mysqli = new mysqli(MYSQLI_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	
if ($mysqli->connect_errno) {
    logError("Failed to connect to MySQL: \n(" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	exit;
}

// web service connection
$curl = curl_init();
curl_setopt($curl, CURLOPT_RETURNTRANSFER , true);
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/json', 'User-Agent: My-funky-user-agent'));

$new_products = array();
$deleted_products = array();

logInfo('Beginning catalog mirroring process.');

foreach (array('Gold', 'Silver', 'Platinum', 'Palladium') as $metal) {
	$url = $svc_url .'/GetProductsByMetal/' . $dealerToken . '/' . $metal;
	curl_setopt($curl, CURLOPT_URL , $url);

	$json = curl_exec($curl);
	
	$result = json_decode($json, true);
	
	if ($debugging) {
		logInfo($url);
		logInfo('JSON: '. $json);
	}

	// we don't write to the json file if there was an error retrieving data
	if (isset($result['error'])) {
		logError("Couldn't retrieve ". $metal ." products: " . $result['error']);
	} else if (!isset($result[0]['code'])) {
		// there was an exception
		$errMsg = "Couldn't retrieve ". $metal ." products: ";
		
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$errMsg .= ' No reported errors. Response was: ' . $json;
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
		logError($errMsg);
		
	} else {
		// check from products that have been removed from fiztrade
		$errors = check_for_deleted($mysqli, $result, $metal);
		if (count($errors) > 0) {
			logError("Failed checking for deleted ". $metal ." products in database:");
			foreach ($errors as $error) {
				logError($error);
			}
		}
	
		// write product data to central product table
		$errors = write_to_db($mysqli, $result);
		if (count($errors) > 0) {
			logError("Couldn't write ". $metal ." products to database:");
			foreach ($errors as $error) {
				logError($error);
			}
		} else {
			logInfo('Updated '. $metal .' products in central catalog.');	
		}
		
		// update product ask price on each site table
		$errors = write_prices_to_products($mysqli, $result);
		if (count($errors) > 0) {
			logError("Couldn't update price metadata:");
			foreach ($errors as $error) {
				logError($error);
			}
		} else {
			logInfo('Updated '. $metal .' dealer site products.');	
		}
	}	
}
curl_close($curl);

// deactivate deleted products
if (count($deleted_products) > 0) {
	$errors = deactivate_products($deleted_products, $mysqli);
	if (count($errors) > 0) {
		logError("Failed to deactivate products:");
		foreach ($errors as $error) {
			logError($error);
		}
	} else {
		logInfo('Deactivated products.');	
	}
}

// let interested parties know about new/deleted products
if (count($new_products) > 0 || count($deleted_products) > 0) {
	$errors = email_product_changes($new_products, $deleted_products, $mysqli);
	if (count($errors) > 0) {
		logError("Couldn't email users:");
		foreach ($errors as $error) {
			logError($error);
		}
	} else {
		logInfo('Emailed administrators.');
	}
	// read on stdout instead of emailing everyone
	// echo "New Products\n";
	// print_r($new_products);
	// echo "Deleted Products\n";
	// print_r($deleted_products);
}

logInfo('Finished catalog mirroring process.');



function check_for_deleted($mysqli, $fiztradeProducts, $metal) {
	global $wpdb, $deleted_products;
	
	$errors = array();
	$table_name = $wpdb->prefix . "products"; 
	$local_products = $mysqli->query('SELECT * FROM '. $table_name .' WHERE MetalType="'. $metal .'"');
	
	if (!$local_products) {
		$errors[] = $mysqli->error;
		return $errors;
	}
	
	while($row = $local_products->fetch_assoc()) {
		$found = false;
		foreach ($fiztradeProducts as $product) {
			if ($product['code'] == $row['Code']) {
				$found = true;
				break;
			}
		}
		
		if (!$found)
			$deleted_products[] = $row;
	}
	// no errors
	return array();
}

function write_to_db($mysqli, $productData) {
	global $wpdb, $new_products, $deleted_products;
	$table_name = $wpdb->prefix . "products"; 
	
	$errors = array();
	// wrap strings in single-quotes and convert Y/N to bit
	for ($j=0; $j<count($productData); $j++) {
		$product = $productData[$j];
		// echo "\n\n";
		// print_r($product);
		// echo "\n";
		$myProduct = array();
		$colNames = array('code', 'name', 'metalType', 'origin', 'weight', 'description', 'familyId',
			'qty', 'image', 'purity', 'quote', 'imagePath', 'isActiveBuy', 'isActiveSell', 'isIRAEligible', 'details');
		foreach($colNames as $attr) {
			$value = $product[$attr];
			if ($attr == 'quote') {
				$myProduct['quote'] = str_replace(',', '', trim($value, '$')); // remove formatting from quote price
			} else if (empty($value)) {
				$myProduct[$attr] = "''";
			} else if (is_string($value)) {
				if (strtolower($value) == 'y') {			// convert Y/N to bit
					$myProduct[$attr] = 1;
				} else if (strtolower($value) == 'n') {
					$myProduct[$attr] = 0;	
				} else {									// wrap strings in quotes
					$myProduct[$attr] = "'" . $mysqli->escape_string($value) . "'";
				}
			} else {
				$myProduct[$attr] = $value;
			}
		}
		
		// pull images and other data out of specs array
		if (array_key_exists('frontImage', $product['specs']))
			$myProduct['obverse'] = "'" . $product['specs']['frontImage'] . "'";
		if (array_key_exists('backImage', $product['specs']))
			$myProduct['inverse'] = "'" . $product['specs']['backImage'] . "'";
		if (array_key_exists('mint', $product['specs']))
			$myProduct['mint'] = "'" . $product['specs']['mint'] . "'";
		if (array_key_exists('strike', $product['specs']))
			$myProduct['strike'] = "'" . $product['specs']['strike'] . "'";
		
		// check if this product is already in the products table
		$result = $mysqli->query("SELECT code FROM $table_name WHERE code=". $myProduct['code']);
		if ($result) {
			if ($result->num_rows == 0) {
				// this is a new product
				$new_products[] = $myProduct;
			}
			$result->free();
		}
		
		$keyStr = implode(',', array_keys($myProduct));
		$valueStr = implode(',', array_values($myProduct));
		// echo $keyStr ."\n";
		// echo $valueStr ."\n";
		//echo "REPLACE INTO products (". $keyStr .") VALUES (". $valueStr .")";
		echo "Replacing ". $myProduct['code'] ."\n";
		$success = $mysqli->query("REPLACE INTO ". $table_name ." (". $keyStr .") VALUES (". $valueStr .")") . PHP_EOL . PHP_EOL;
		if (!$success) {
			$errors[] = 'Line '. __LINE__ .': '. $mysqli->error;
			break;
		}
		
		// remove deleted products from central products table
		if (count($deleted_products) > 0) {
			$del_codes = array();
			foreach ($deleted_products as $product) {
				$del_codes[] = "'". $product['Code'] ."'";
			}
			$success = $mysqli->query("DELETE FROM ". $table_name ." WHERE Code IN (". implode(",", $del_codes) .")");
			if (!$success) {
				$errors[] = 'Line '. __LINE__ .': '. $mysqli->error;
				break;
			}
		}
	}
	
	return $errors;
}

// update the _price meta for every product the site
// also deactivates products deleted from FizTrade
function write_prices_to_products($mysqli, $productData) {
	global $deleted_products;
	$table_name = $wpdb->prefix . "postmeta"; 
	$errors = array();
		
	foreach ($productData as $product) {
		$product_code = $product['code'];
		$quote = str_replace(',', '', trim($product['quote'], '$'));
		//$debug = $product_code == '1KR' ? true : false;
		$debug = false;
		
		// get the premiums
		$product_meta = $mysqli->query('SELECT * FROM '. $table_name .
			' WHERE post_id=(SELECT m.post_id FROM '. $table_name .' AS m '.
										'JOIN '. str_replace('postmeta', 'posts', $table_name) .' AS p ON p.ID=m.post_id '.
										'WHERE p.post_status!="trash" AND m.meta_key="_product_id" AND m.meta_value="'. $product_code .'")');
			
		
		if ($product_meta === false) 
			continue;
			
		if ($debug) {
			echo 'SELECT * FROM '. $table_name .
			' WHERE post_id=(SELECT m.post_id FROM '. $table_name .' AS m '.
										'JOIN '. str_replace('postmeta', 'posts', $table_name) .' AS p ON p.ID=m.post_id '.
										'WHERE p.post_status!="trash" AND m.meta_key="_product_id" AND m.meta_value="'. $product_code .'")'.PHP_EOL;
		}
		// if the product isn't in this site, we stop here
		
		if ($product_meta->num_rows > 0) {
			$sell_flat_premium = $sell_percent_premium = $post_id = null;
			for ($i=0; $i<$product_meta->num_rows; $i++) { 
				$row = $product_meta->fetch_assoc();
				
				if ($debug)
					print_r($row);
				
				if (!isset($post_id))
					$post_id = $row['post_id'];
				
				if ($row['meta_key'] == '_sell_flat_premium')
					$sell_flat_premium = floatval($row['meta_value']);
				if ($row['meta_key'] == '_sell_percent_premium')
					$sell_percent_premium = floatval($row['meta_value']);
				if (isset($sell_flat_premium) && isset($sell_percent_premium))
					break; // that's all we were looking for
			}
			$product_meta->free();
			
			// calculate price
			$sell_flat_premium = isset($sell_flat_premium) ? $sell_flat_premium : 0;
			$sell_percent_premium = isset($sell_percent_premium) ? $sell_percent_premium : 0;
			
			$new_price = $quote + $quote * ($sell_percent_premium / 100) + $sell_flat_premium;
			
			if ($debug) {
				echo 'sell_flat_premium='. $sell_flat_premium .' sell_percent_premium='. $sell_percent_premium .' quote='. $quote .PHP_EOL;
				echo 'New price='. $new_price.PHP_EOL;
			}
			
			// update the price
			$result = $mysqli->query('UPDATE '. $table_name .' SET meta_value='. $new_price .
				' WHERE post_id='. $post_id .' AND meta_key="_price"');
			
			if ($debug)				
				echo 'Updated post '. $post_id .' in '. $table_name .' to $'. $new_price . PHP_EOL; 
			
			if (!$result) 
				$errors[] = 'Line '. __LINE__ .': '. $mysqli->error;
		}
		
	}
	
	// deactivate deleted products
	foreach ($deleted_products as $product) {
		$del_code = $product['Code'];
		
		$term_table_name = str_replace('postmeta', 'terms', $table_name);
		$rel_table_name = str_replace('postmeta', 'term_relationships', $table_name);
		
		// get post id
		$result = $mysqli->query('SELECT m.post_id FROM '. $table_name .' AS m '.
								'JOIN '. str_replace('postmeta', 'posts', $table_name) .' AS p ON p.ID=m.post_id '.
								'WHERE p.post_status!="trash" AND m.meta_key="_product_id" AND m.meta_value="'. $del_code .'"');
		if (!$result) {
			$errors[] = 'Line '. __LINE__ .': '. $mysqli->error;
			break;
		}
		
		$row = $result->fetch_row();
		$result->free();
		if ($row == null) 
			continue; // product not in this site, go to the next one
			
		
		$post_id = $row[0];
						
		// set to dealer item
		$success = $mysqli->query('UPDATE '. $rel_table_name .
			' SET term_taxonomy_id=(SELECT term_id FROM '. $term_table_name .' WHERE slug="dealer")'.
			' WHERE term_taxonomy_id=(SELECT term_id FROM '. $term_table_name .' WHERE slug="fiztrade")'.
			' AND object_id="'. $post_id .'"');
		if (!$success)
			$errors[] = 'Line '. __LINE__ .': '. $mysqli->error;
		
		// deactivate
		$success = $mysqli->query('UPDATE '. $table_name .
			' SET meta_value="no"'.
			' WHERE meta_key IN ("_will_buy","_will_sell","_sell_option","_buy_option")'.
			' AND post_id="'. $post_id .'"');
		if (!$success)
			$errors[] = 'Line '. __LINE__ .': '. $mysqli->error;
				
	}
	
	return $errors;
}

function email_product_changes($new_products, $deleted_products, $mysqli) {
	global $wpdb;
	//define( 'ABSPATH', dirname(dirname(__FILE__)) . '/' );
	//require_once( ABSPATH . 'wp-config.php' );
	$table_name = $wpdb->prefix . "options"; 
	$errors = array();
	$email_html = $email_text = "";
	
	if (count($new_products) > 0) {
		$email_html .= '<p>The following products have been added to the FizTrade Inventory:</p><br/>';
		$email_text .= "The following products have been added to the FizTrade Inventory:". PHP_EOL . PHP_EOL;
		
		$email_html .= '<table><thead><tr><th>Product Code</th><th>Product Title</th></tr><tbody>';
		$email_text .= "Product Code\tProduct Title". PHP_EOL;
		
		foreach ($new_products as $product) {
			$email_html .= sprintf('<th>%s</th><th>%s</th></tr>', 
				trim($product['code'], "'"), 
				trim($product['description'], "'") .' '. trim($product['name'], "'"));
			$email_text .= sprintf("%s\t\t%s". PHP_EOL, 
				trim($product['code'], "'"), 
				trim($product['description'], "'") .' '. trim($product['name'], "'"));
		}
		
		$email_html .= '</tbody></table>';
		$email_text .= PHP_EOL . PHP_EOL;
	}
	
	if (count($deleted_products) > 0) {
		$email_html .= '<p>The following products have been removed from the FizTrade Inventory:</p><br/>';
		$email_text .= "The following products have been removed the FizTrade Inventory:". PHP_EOL . PHP_EOL;
		
		$email_html .= '<table><thead><tr><th>Product Code</th><th>Product Title</th></tr><tbody>';
		$email_text .= "Product Code\tProduct Title". PHP_EOL;
		
		foreach ($deleted_products as $product) {
			$email_html .= sprintf('<th>%s</th><th>%s</th></tr>', 
				trim($product['Code'], "'"), 
				trim($product['Description'], "'") .' '. trim($product['Name'], "'"));
			$email_text .= sprintf("%s\t\t%s". PHP_EOL, 
				trim($product['Code'], "'"), 
				trim($product['Description'], "'") .' '. trim($product['Name'], "'"));
		}
		
		$email_html .= '</tbody></table>';
		
		$email_html .= "<p>If any of these products were listed on your site, they have been switched from FizTrade Item to My Item and deactivated.
			To display them on the site again, enter just enter buy/sell prices and reactivate.</p>";
		$email_text .= "If any of these products were listed on your site, they have been switched from FizTrade Item to My Item and deactivated.  To display them on the site again, enter just enter buy/sell prices and reactivate.";
	}
	
	$recipients = dm_get_recipients('new-product');
	
	// Get the site domain and get rid of www.
	$sitename = strtolower( $_SERVER['SERVER_NAME'] );
	if ( substr( $sitename, 0, 4 ) == 'www.' ) {
		$sitename = substr( $sitename, 4 );
	}
	
	$success = wp_mail(
		'', 
		'Digital Metals Products Added/Removed', 
		$email_text, 
		array(
			'From: Digital Metals Products Daemon <do-not-reply@'. $sitename .'>',
			'Bcc: '. $recipients
		)
	);

	if ($success)
		logInfo("Digital Metals Products Daemon sent email to " . $recipients);
	else
		logError('Digital Metals Products Daemon failed sending email to '. $recipients);
	
	return $errors;
}

// find each product in every site, set it to dealer item and unset buy and sell
function deactivate_products($products, $mysqli) {
	
	
	return array();
}

?>
