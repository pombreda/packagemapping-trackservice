<?php
/*
 *	PackageMapping.com
 *	API for PackageMapping Track Web Service
 *	Last Updated: January, 2014
 *
 *	Service: RESTful/JSON via POST
 *	Payload: application/json
 *	Transport: http
 *	Authentication: none (except API key)
 */
 
 

/**
 * GetCarrierCodeList
 *
 * Retrieves a list of supported Carriers including carrier code, name, label and legal terms.
 * Can be filtered by passing in a tracking number and/or an array of allowed carrier codes.
 *
 * Returns: either an object or array depending on $assoc flag. Default is object.
 */
function zcpmc_get_carrier_code_list( $web_service_key, $tracking_number = "", $allowed_carrier_codes = "", $assoc = false )
{
	$data = array();
	
	if (!empty($tracking_number))
		$data["TrackingNumber"] = $tracking_number;
		
	if (is_array($allowed_carrier_codes))
		$data["AllowedCarrierCodes"] = $allowed_carrier_codes;
		
	return zcpmc_send_request("GetCarrierCodeList", $web_service_key, $data, $assoc);
}

/**
 * GetTrackList
 *
 * Retrieves a list of Tracks containing tracking information for each search parameter.
 * Can be filtered by passing in an array of allowed carrier codes per search parameter.
 *
 * Returns: either an object or array depending on $assoc flag. Default is object.
 */
function zcpmc_get_track_list( $web_service_key, $data, $assoc = false )
{
	/* You can send multiple requests in one call. For best performance, we recommend no more than 50 requests per call. */
	/* $data should be similar to below:
		$data = array(
			'SearchParameters'		=> array(
				0 => array(
					"CarrierCode" 		=> "", //Provide Carrier Code if possible for best results, otherwise use "auto"
					"TrackingNumber" 	=> "", //Provide the Tracking Number
				),
				1 => array(
					"CarrierCode"		=> "",
					"TrackingNumber"	=> "",
				)
			),
			"AllowedCarrierCodes" => array() //Array of carrier codes as strings
		);
	*/
	return zcpmc_send_request("GetTrackList", $web_service_key, $data, $assoc);
}

/**
 * Send Request
 *
 * Sends the request to the web service using JSON.
 * This function is only meant to be called from the helper functions in this file.
 * 
 * $action is the web service method
 *
 * Returns: either an object or array depending on $assoc flag. Default is object.
 */
function zcpmc_send_request($action, $web_service_key, $data, $assoc = false)
{
	if (! is_array($data) || empty($web_service_key) || empty($action) ){
		return json_decode('{"Success":false, "FailureInformation":"Missing required input"}', $assoc);
	}
	
	try
	{
		/* The web service url and method */
		$uri = "https://ws.packagemapping.com/Services/PackageMapping/ITrackService/rest/json/" . $action;
		
		/* PackageMapping JSON Track Web Service */
		$prep_data = array(
		  'WebServiceKey' => $web_service_key //API Key for PackageMapping Web Service
		);
		$data = array_merge($prep_data, $data);
		
		/* The query string to be posted. */
		$query = json_encode($data);
	
		/* Setup header information	*/
		$header = array(
		    "Content-Type: application/json; charset=utf-8",
		    "Accept: application/json",
		    "Content-Length: " . strlen($query)
			);

		/////////////////// begin the cURL engine /////////////////////
		///////////////////////////////////////////////////////////////
		$ch = curl_init(); /// initialize a cURL session
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt ($ch, CURLOPT_URL,$uri); /// set the post-to url (do not include the ?query+string here!)
		curl_setopt ($ch, CURLOPT_HTTPHEADER, $header); /// Header control
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);/// Use this to prevent PHP from verifying the host (later versions of PHP including 5)
		/// If the script you were using with cURL has stopped working. Likely adding the line above will solve it.
		curl_setopt($ch, CURLOPT_POST, 1);  /// tell it to make a POST, not a GET
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);  /// put the query string here starting with "?"
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); /// This allows the output to be set into a variable $xyz
		$curl_response = curl_exec ($ch); /// execute the curl session and return the output to a variable $xyz
		curl_close ($ch); /// close the curl session
		/////////////////////////////////////////////////////////////
		///////////////////  end the cURL Engine  /////////////////
		
		/* Decode JSON */
		if (is_string($curl_response))
		{
			$response = json_decode( $curl_response, $assoc );
		}		
	
		/* Return the response */
		if (isset($response))
		{
			return $response;
		} else {
			return json_decode('{"Success":false, "FailureInformation":"Tracking service is temporarily unavailable. {100}"}', $assoc);
		}
	}
	catch (Exception $ex)
	{
		/* Log error message */
		error_log($ex->getMessage());
		return json_decode('{"Success":false, "FailureInformation":"Tracking service is temporarily unavailable. {101}"}', $assoc);
	}		
}

?>
