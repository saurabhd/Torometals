<?php 

error_reporting(E_ALL);
ini_set('display_errors', '1');

require('../../../wp-load.php');
global $wpdb;

if ( isset($_REQUEST['site_id']) && is_site_archived($_REQUEST['site_id']) ) {
        die("Invalid Site ID");
}

switch ($_REQUEST['op']) {

	case 'get_store':
		get_store($_REQUEST['site_id'], $_REQUEST['store_id']);			
		break;

	case 'get_stores':
		get_stores($_REQUEST['site_id']);			
		break;

	case 'get_hours':
		get_hours($_REQUEST['site_id']);			
		break;

	case 'get_contacts':
		get_property_contacts($_REQUEST['site_id']);			
		break;

	case 'get_events':
		get_property_events($_REQUEST['site_id']);			
		break;

	case 'get_info':
		get_info($_REQUEST['site_id']);			
		break;

	case 'get_social_feed':
		if ( isset($_REQUEST['store_id']) ) {
			get_social_feed($_REQUEST['site_id'], $_REQUEST['number_posts'], $_REQUEST['store_id']);			
		} else {
			get_social_feed($_REQUEST['site_id'], $_REQUEST['number_posts']);			
		}	
		break;

	default:
		print_help();

}


function print_help()  {
	$help .= 'get_stores($site_id) - Returns the complete store directory for the given site' . "\n";
	$help .= 'get_info($site_id) - Returns property information (address, phone number, etc.)' . "\n";
	$help .= 'get_hours($site_id) - Returns hours for the current week. Hours are broken down by days of the week; days of the week start with Sunday (0).' . "\n";
	$help .= 'get_events($site_id) - Returns the complete list of events for the property.' . "\n";
	$help .= 'get_contacts($site_id) - Returns the Contact Us information from the property site.' . "\n";
	$help .= 'get_social_feed($site_id,$number_posts,$store_id) - Returns the social feed of the given property.  The store_id is optional (by default will return all feeds). To get the property\'s feeds, use store_id of 0.' . "\n";

	print($help);
}

function get_property_contacts($site_id)  {

	//Get the contact page id from the WP options
	$sql = "select option_value from wp_{$site_id}_options where option_name='imag_mall_options_tech'";
	global $wpdb;
        $results = $wpdb->get_results($sql);
	foreach ($results as $result)  {
		$imag_mall_options_tech = unserialize($result->option_value);
		$contact_page_id = $imag_mall_options_tech['contact_form_page_id'];
	}

	//Get the page content
	$sql = "select post_content from wp_{$site_id}_posts where id={$contact_page_id}";
        $results = $wpdb->get_results($sql);
	foreach ($results as $result)  {
		//Strip any short-codes from the content
		$contact_page_content = trim(preg_replace('/\[.*\]/', '', $result->post_content));
	}

	//var_dump($contact_page_content);	

	//$contact_page_content = nl2br($contact_page_content);
	$order   = array("\r\n", "\n", "\r");
	$replace = '<br />';
	$contact_page_content = str_replace($order, $replace, $contact_page_content);

	//Create tel links
	$contact_page_content = preg_replace('/\+?[0-9][0-9()-\s+]{4,20}[0-9]/', "<a href='tel:$0'>$0</a>", $contact_page_content);
	print(json_encode($contact_page_content));

}
	

function get_stores($site_id)  {

	$sql = "select ID, post_title from wp_{$site_id}_posts where post_type='imag_store' and post_status='publish' order by post_title";

	//print($sql);
	global $wpdb;
        $results = $wpdb->get_results($sql);

	$stores = array();

	foreach($results as $result)  {
		//var_dump($result);
		$stores[$result->ID]['name'] = str_replace('#038;', '', $result->post_title);
		$stores[$result->ID]['name'] = str_replace('&amp;', '&', $result->post_title);

	}

	//var_dump($stores);
        print(json_encode($stores));

}

function get_store($site_id, $store_id)  {

include ('../imag-store-directory/imag-store-directory.php');

$blog_details = get_blog_details(array( 'blog_id' => $site_id ));
$mall_options = get_blog_option($site_id,'imag_mall_options');


	//Get data from Retail Central first
	$sql = "select ID, meta_key, meta_value, post_title, post_content
from wp_26_posts posts, wp_26_postmeta postmeta 
where id=(
    select ID
    from wp_26_posts posts, wp_26_postmeta postmeta 
    where meta_key='imag_store_feed_facebook'
    and meta_value=(
        select meta_value 
        from wp_{$site_id}_posts, wp_{$site_id}_postmeta 
        where meta_key='imag_store_feed_facebook' 
        and post_id={$store_id}
        and wp_{$site_id}_posts.id=wp_{$site_id}_postmeta.post_id
    )
    and posts.ID=postmeta.post_id
    )
and posts.ID=postmeta.post_id 
and post_type='imag_store' 
and post_status='publish'
order by post_title";


	//print($sql);
	global $wpdb;
        $results = $wpdb->get_results($sql);
	//var_dump($results);

	$store = array();

	foreach($results as $result)  {


		//var_dump($result);

		$store['name'] = str_replace('&amp;', '&', $result->post_title);
		$store['description'] = trim(strip_tags($result->post_content));

		if ( $result->meta_key == "Location" )  {
			$store['location'] = $result->meta_value;
		} else if ( $result->meta_key == "imag_store_logo" )  {
			$store['logo'] = get_post_guid(26, $result->meta_value);
		} else if ( $result->meta_key == "imag_store_user_facebook" )  {
			$store['user_facebook'] = $result->meta_value;
		} else if ( $result->meta_key == "imag_store_web" )  {
			$store['web'] = $result->meta_value;
		} else if ( $result->meta_key == "imag_store_phone" )  {
			$store['phone'] = $result->meta_value;
		
		}

	}

	//var_dump($store);


	//Now, get the store data from the specific site	
	$sql = "select ID, post_title, meta_key, meta_value, post_content from wp_{$site_id}_posts posts, wp_{$site_id}_postmeta postmeta where id=$store_id and posts.ID=postmeta.post_id and post_type='imag_store' and post_status='publish' order by post_title";

	//print($sql);
	global $wpdb;
        $results = $wpdb->get_results($sql);
	//var_dump($results);

	foreach($results as $result)  {


		//var_dump($result);

		$store['name'] = str_replace('&amp;', '&', $result->post_title);

		if ( strlen($store['description']) <= 0 ) {
			$store['description'] = trim(strip_tags($result->post_content));
		}		

		if ( $result->meta_key == "imag_store_location" )  {
			$store['location'] = $result->meta_value;
		} else if ( $result->meta_key == "imag_store_logo" )  {
			$store['logo'] = get_post_guid($site_id, $result->meta_value);
		} else if ( $result->meta_key == "imag_store_user_facebook" )  {
			$store['user_facebook'] = $result->meta_value;
		} else if ( $result->meta_key == "imag_store_web" )  {
			$store['web'] = $result->meta_value;
		} else if ( $result->meta_key == "imag_store_phone" )  {
			$store['phone'] = $result->meta_value;
		
		}

	}


	$store['description'] = replace_retailer_central_tags($store['description'] ,$blog_details->blogname,$mall_options);
	
	//var_dump($store);

        print(json_encode($store));

}

function get_post_guid($site_id, $post_id)  {

	$sql = "select guid from wp_" . $site_id . "_posts where id=" . $post_id;
	global $wpdb;
        $results = $wpdb->get_results($sql);	
	foreach ($results as $result)  {
		return $result->guid;
	}

}

function get_hours($site_id)  {

	$sql = "select option_value from wp_" . $site_id . "_options where option_name='imag_hours'";

	//print($sql);
        global $wpdb;
        $results = $wpdb->get_results($sql);	

	//var_dump($results);

	foreach ($results as $result)  {
		$imag_hours = unserialize($result->option_value);
	}

	//var_dump($imag_hours);

	$sql = "select option_value from wp_" . $site_id . "_options where option_name='imag_hours_exceptions'";

	//print($sql);
        global $wpdb;
        $results = $wpdb->get_results($sql);	

	//var_dump($results);

	foreach ($results as $result)  {
		$imag_hours_exceptions = unserialize($result->option_value);
	}

	//var_dump($imag_hours_exceptions);

	$replacements = array('day', ':00');
	$imag_hours['hours_string'] = str_replace($replacements, '', $imag_hours['hours_string']);
	$imag_hours['hours_string'] = str_replace('PM', 'p', $imag_hours['hours_string']);
	$imag_hours['hours_string'] = str_replace('AM', 'a', $imag_hours['hours_string']);

	//print($imag_hours['hours_string']);

	print(json_encode($imag_hours));

}

function get_property_events($site_id)  {

	$sql = "select id, post_content, post_title from wp_{$site_id}_posts where post_type='sp_events' and post_status='publish'";

	//print($sql);
        global $wpdb;
        $results = $wpdb->get_results($sql);	

	//Get the events
	$events = array();
	foreach ($results as $result)  {
		$events[$result->id][description] = $result->post_content;
		$events[$result->id][title] = str_replace('&amp;', '&',$result->post_title);
	}

	//Get the meta data
	foreach ($events as $event_id => $event)  {
		$sql = "select meta_key, meta_value from wp_" . $site_id . "_postmeta where post_id=" . $event_id . " and meta_key in ('_EventStartDate', '_EventEndDate', '_EventAllDay')";
	
		$results = $wpdb->get_results($sql);
		foreach($results as $result)  {
			switch ($result->meta_key) {
				case '_EventStartDate':
					$events[$event_id]['start'] = $result->meta_value;
					break;
				case '_EventEndDate':
					$events[$event_id]['end'] = $result->meta_value;
					break;
				case '_EventAllDay':
					$events[$event_id]['all_day'] = $result->meta_value;
					break;
					
			}	
		}	
	}

	//Remove any events that happened in the past
	foreach ($events as $key => $event)  {

		//print($events[$key]['end']);

		if ( strtotime($events[$key]['end']) < strtotime(date("Y-m-d")) ) {
			unset($events[$key]);
		}

	}

	//Now, update the id to include the date (this will be used by the devices to order the events
	$ordered_events = array();
	foreach($events as $event_id => $event)  {
		$ordered_events[$event['start'].'_'.$event_id] = $event;

	}

	//var_dump($ordered_events);
	print(json_encode($ordered_events));

}

function get_social_feed($site_id, $number_posts, $store_id=-1)  {


	if ( $store_id >= 0 ) {
		$sql = "select * from wp_{$site_id}_social_feeds where store_id=$store_id and post_content not like '%This feed URL is no longer valid%' order by post_date desc limit $number_posts";
	}  else  {
		$sql = "select * from wp_{$site_id}_social_feeds where post_content not like '%This feed URL is no longer valid%' order by post_date desc limit $number_posts";
	}

	//print($sql);
        global $wpdb;
        $results = $wpdb->get_results($sql);	

	foreach($results as $key => $result)  {

		$result->post_content = strip_tags($result->post_content);
		$result->post_content = str_replace('&#039;', "'", $result->post_content);

	}

	//var_dump($results);

	print(json_encode($results));
}

function get_info($site_id)  {

	$sql = "select option_value from wp_" . $site_id . "_options where option_name='imag_mall_options'";

	//print($sql);
        global $wpdb;
        $results = $wpdb->get_results($sql);	

	//var_dump($results);

	foreach ($results as $result)  {
		$property_info = unserialize($result->option_value);
	}

	//var_dump($property_info);

	$info = array();
	$info['address1'] = $property_info['address1'];
	$info['address2'] = $property_info['address2'];
	$info['city'] = $property_info['city'];
	$info['state'] = $property_info['state'];
	$info['zip'] = $property_info['zip'];
	$info['phone'] = $property_info['phone'];
	$info['google_maps_url'] = $property_info['google_maps_url'];
	$info['twitter_user'] = $property_info['twitter_user'];
	$info['facebook_page_id'] = $property_info['facebook_page_id'];

	//Get the mall map
	$info['mall_map_url'] = get_post_guid($site_id, $property_info['map']);

	$sql = "select option_value from wp_{$site_id}_options where option_name='imag_mall_options_tech'";
        $results = $wpdb->get_results($sql);	
	foreach ($results as $result)  {
		$property_info = unserialize($result->option_value);
	}
	$info['amazons3'] = $property_info['amazons3'];
	$info['mall_map_url'] = str_replace('http://www.imaginuitycenters.com/', $info['amazons3'], $info['mall_map_url']); 



	print(json_encode($info));

}

function is_site_archived($site_id)  {

        $sql = "select archived from wp_blogs where blog_id=" . $site_id;
        global $wpdb;
        $results = $wpdb->get_results($sql);

        $archived = 0;
        foreach ($results as $result)  {
                $archived = $result->archived;
        }

        return $archived;

}
		
