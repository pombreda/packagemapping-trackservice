<?php
/*
 *	PackageMapping.com
 *	Example client for PackageMapping Track Web Service
 *	Last Updated: January, 2014
 *
 */

/* Required: Web Service API class */
require_once("packagemapping_trackservice.class.php");


/**
 * Example usage for zcpmc_get_carrier_code_list()
 *
 * Retrieves a list of supported Carriers.
 * Can be filtered by passing in a tracking number and/or an array of allowed carrier codes.
 *
 * Returns: array.
 */
function example_get_carrier_code_list(){
	/* Instantiate results array */
	$results = array();
	
	/* Enter your Web Service Key */
	$web_service_key = "";
	
	/* Enter your Tracking Number */
	$tracking_number = "";

	/* Filter carriers */
	$allowed_carrier_codes = array();
	//$allowed_carrier_codes[] = "ups"; //Uncomment to filter
	//$allowed_carrier_codes[] = "fedex"; //Uncomment to filter
	
	/* Get filtered carrier list based on tracking number and allowed carriers. Return as array. */
	$carrier_list = zcpmc_get_carrier_code_list($web_service_key, $tracking_number, $allowed_carrier_codes, true);

	if ($carrier_list["Success"] == "1"){
		foreach ( $carrier_list["CarrierInfo"] as $carrier_info ){
			$results[$carrier_info["Code"]] = array();
			
			/* Return code */
			$results[$carrier_info["Code"]]["Code"] = $carrier_info["Code"];

			/* Return name */
			$results[$carrier_info["Code"]]["Name"] = $carrier_info["Name"];

			/* Return label */
			$results[$carrier_info["Code"]]["Label"] = $carrier_info["Label"];

			/* Return terms of service */
			$results[$carrier_info["Code"]]["TermsOfService"] = $carrier_info["TermsOfService"];
			
			/* Consult documentation for a full list of properties. */
		}
	} else {
		/* Return error message */
		$results[] = $carrier_list["FailureInformation"];
	}
	
	return $results;
}

/**
 * Helper function to get array value instead of throwing exception if key does not exist
 *
 * Returns: value or empty string if value is not set.
 */
function getval(&$val){
	if(isset($val)){
		return $val;
	}
	return "";
}


/**
 * Example usage for zcpmc_get_track_list()
 *
 * Retrieves a list of Tracks containing tracking information for each search parameter.
 * Can be filtered by passing in an array of allowed carrier codes per search parameter.
 *
 * Outputs: array to screen.
 */
function example_get_track_list() {

	/* Enter your Web Service Key */
	$web_service_key = "";

	/* Filter carriers to only the carriers that you support */
	$allowed_carrier_codes = array();
	//$allowed_carrier_codes[] = "ups"; //Uncomment to filter
	//$allowed_carrier_codes[] = "fedex"; //Uncomment to filter

	/* You can send multiple requests in one call. For best performance, we recommend no more than 50 requests per call. */
	$data = array(
	  	'SearchParameters'		=> array(
			0 => array(
				"CarrierCode" 		=> "", //Provide Carrier Code if possible for best results, otherwise use "auto"
				"TrackingNumber" 	=> "" //Provide the Tracking Number
			),
			1 => array(
				"CarrierCode" 		=> "", 
				"TrackingNumber" 	=> "" 
			)
		),
		"AllowedCarrierCodes" => $allowed_carrier_codes //Array of carrier codes as strings
	);

	/* Get track response */
	$track_response = zcpmc_get_track_list($web_service_key, $data, true);

	/* Handle connection error */
	if (!isset($track_response) || $track_response["Success"] == false){
		die('There was a problem.  Please try again later or contact us directly.');
	}

	if (getval($track_response["Success"]) != "1"){
		/* Handle web service error */
		if(isset($track_response["FailureInformation"])) { 
			echo $track_response["FailureInformation"] . "<br/>";
		} else {
			echo "There was a problem with your request. Please try again later or contact support." . "<br/>";
		}
	} else {
		/* Cache list of carriers */
		$carriers = example_get_carrier_code_list();

		/* Iterate through tracks */
		foreach($track_response["Tracks"] as $track){
			if( getval($track["Success"]) != "1"){	
				/* Handle tracking error */		
				/* Note: Providing the correct carrier will reduce the chances of getting here.
				 * (Assuming the correct carrier has information to return.)
				 */
				echo "We are still waiting for information about your shipment. Please try again later." . "<br/>";
			} else {
				/* Get detailed information for carrier from cache */
				$carrier_label = getval($carriers[$track["CarrierCode"]]["Label"]);
				
				/* Examples for getting values.
				 * Consult documentation or print_r($track) for more properties.
				 */
				$latest_status = getval($track["LatestStatus"]["Status"]); //PackageMapping standardized status
				$latest_status_message = getval($track["LatestStatus"]["StatusMessage"]); //Carrier status
				/* Date and Time are local to location */
				$latest_status_date = getval($track["LatestStatus"]["Date"]);
				$latest_status_time = getval($track["LatestStatus"]["Time"]);
				$latest_status_location = getval($track["LatestStatus"]["Location"]);
				
				$origin = getval($track["Origin"]);
				$destination = getval($track["Destination"]);
				$address_reroute_to_destination = getval($track["AddressRerouteToDestination"]);
				
				/* Local to destination */
				if (!empty($track["ScheduledDeliveryDateTime"])){
					$scheduled_delivery_date = date("l, F j g:i a", strtotime(getval($track["ScheduledDeliveryDateTime"])));
				}

				/* UTC */
				$last_updated = getval($track["ResultsLastUpdatedUtcDateTime"]);

				if(isset($track["Activities"])){
					foreach($track["Activities"] as $activity){
						$status = getval($activity["Status"]);
						$status_message = getval($activity["StatusMessage"]);
						/* Date and Time are local to location */
						$date = getval($activity["Date"]);
						$time = getval($activity["Time"]);
						$location = getval($activity["Location"]);
					}
				}

				/* Additional properties */
				if( isset($track["CustomProperties"])){
					foreach($track["CustomProperties"] as $prop){
						$prop_name = getval($prop["Name"]);
						$prop_value = getval($prop["Value"]);
					}
				}
				
				//Echo response for demonstration purposes
				echo "<pre>" . print_r($track, true) . "</pre><br/><hr><br/>";
			}
		}
	}

}

/* Call function */
example_get_track_list();

?>