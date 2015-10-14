<?php
/*
The MIT License (MIT)

Copyright (c) 2015 Daniel Bunyard

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

//The email address associated with the CloudFlare account
$email = 'person@example.com';
//The API key for your account
$api_key = '1234567891234567891234567891234567891';
//IP address we are searching for
$search_ip = '4.3.2.1';
//The IP address we are going to update the records to
$update_ip = '1.2.3.4';
//The domain(s) we are searching for the above IP address
//Seperate multiple entries with a comma
//Set this to ALL if you want all domains on your account
$search_domains = 'example1.com,example2.com';
//$search_domains = 'ALL';
//Set this to true to actually update the records
//WARNING YOU WILL NOT GET ANY CONFIMATION
//PLEASE USE WITH CAUTION
$update_records = false;

/*******************************************************************************************************
********************************************************************************************************
! ! ! UNLESS YOU KNOW WHAT YOU ARE DOING DON'T EDIT BELOW THIS LINE ! ! !
********************************************************************************************************
*******************************************************************************************************/

//Require the CloudFlare PHP API class
require_once('class_cloudflare.php');
//Create the new API class with email and API key
$cf = new cloudflare_api($email, $api_key);

//Check if we are loading all domains from the account
if ($search_domains == 'ALL') {
	//Get all the domains from the account
	$response = $cf->zone_load_multi();
	//Instead of modifying the PHP API helper we are going to re-encode then re-decode the array
	//This is hacky but keeps us from changing the core API helper
	$response = json_decode(json_encode($response), true);
	//Loop through all records in the array
	$domains_array = array();
	foreach ($response['response']['zones']['objs'] as $value) {
		$domains_array[] = $value['zone_name'];
	} //end foreach ($response['response']['zone']['objs'] as $value) {
}else{
	//Break out the domains from the list given
	$domains_array = explode(",",$search_domains);
} //end if ($search_domains == 'ALL') {

//Loop through all the domains looking for a match on the IP address above
$i = 0; //Global counter for the to_modify array
$to_modify = array(); //Array to add the records to
foreach($domains_array as $domain) {
	//Get all the records for the domain
	$response = $cf->rec_load_all($domain);
	//Same as above, re-encode then re-decode the JSON responce
	$response = json_decode(json_encode($response), true);
	
	//Loop through all records in the array
	foreach ($response['response']['recs']['objs'] as $value) {
		//Make sure we have an IP address
		if (filter_var($value['content'], FILTER_VALIDATE_IP)) {
			//Check if the IP is one we are searching for
			if ($value['content']==$search_ip) {
				//Add the record to the array
				$to_modify[$i]['z'] = $value['zone_name'];
				$to_modify[$i]['type'] = $value['type'];
				$to_modify[$i]['id'] = $value['rec_id'];
				$to_modify[$i]['name'] = $value['name'];
				$to_modify[$i]['content'] = $value['content'];
				$to_modify[$i]['ttl'] = $value['ttl'];
				$to_modify[$i]['service_mode'] = $value['service_mode'];
				
				//Increment the counter
				$i++;
			} //end if ($value['content']==$search_ip) {
		} //end if (filter_var($value['content'], FILTER_VALIDATE_IP)) {
	} //end foreach ($response['response']['recs']['objs'] as $value) {
} //end foreach($domains_array as $domain) {
	
//See if the array above was empty and let the user know there is nothing to modify
if (empty($to_modify)) {
	Echo "Search IP not found - nothing to modify!<br>";
} //end if (empty($to_modify)) {

//If the update record variable is set to true we will do the update
if ($update_records == true) {
	//Loop through the records to update
	foreach ($to_modify as $record) {
		//Update the record to the IP from the config section above
		$response = $cf->rec_edit($record['z'], $record['type'], $record['id'], $record['name'], $update_ip, $record['ttl'], $record['service_mode']);
		//Same as above, re-encode then re-decode the JSON responce
		$response = json_decode(json_encode($response), true);
		//Make sure we had a valid response
		if ($response['result']=='success') {
			Echo 'Record ' . $record['id'] . " updated!<br>";
		}else{
			Echo 'There was an error updating record ' . $record['id'] . ".<br>";
		} //end if ($response['result']==''success) {
	} //end foreach ($to_modify as $record) {
} //end if ($update_records == true) {
//Let the user know that the script is done
echo 'Done!';
?>
