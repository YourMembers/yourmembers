<?php

/**
* Version 1.0.0
* GetResponse API V1.8.2 2011-05-05
* 
* $Id: mailchimp_class.php 38 2011-04-28 10:21:05Z bcarlyon $
* $Date: 2011-04-28 11:21:05 +0100 (Thu, 28 Apr 2011) $
* $Revision: 38 $
* $Author: bcarlyon $
* 
*/

class GetResponse {
	private $apikey;
	private $url;
	private $method;
	private $parameters;
	private $jsonobject;
	public $result;
	public $error;
	
	private $secure = 0;
	
	function __construct($apikey, $assoc = FALSE) {
		$this->apikey = $apikey;
		
		$this->url = 'http';
		if ($this->secure) {
			$this->url .= 's';
		}
		$this->url .= '://api2.getresponse.com/';
		
		$this->jsonobject = $assoc;
	}

	private function addParameter($name, $value) {
		$this->parameters[$name] = $value;
	}
	
	private function run() {
		// construct url
		$url = $this->url;
		
		$packet = array(
			'method'	=> $this->method,
			'params'	=> array(
				$this->apikey
			)
		);
		if ($this->parameters) {
			$packet['params'][] = $this->parameters;
		}
		$this->parameters = array();
		$packet = json_encode($packet);
		$this->packet = $packet;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $packet);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);

		if ($json = curl_exec($ch)) {
			$info = curl_getinfo($ch);
			$this->info = $info;
			curl_close($ch);
			
			if ($info['http_code'] != 200) {
				$this->error = $info['http_code'];
				return '';
			}

			$return = json_decode($json, $this->jsonobject);
			
			if ($return->error) {
				
				return $this->error($return);
			} else {
				$this->error = 'ok';
			}
			$this->return = $return;
			
			return $return;
		} else {
			$this->error = 'FAILED';
			$info = curl_getinfo($ch);
			$this->info = $info;
			
			return FALSE;
		}
	}
	private function error($error) {
		$str = 'Error Code: ' . $error->code . ': ' . $error->error;
		trigger_error($str, E_USER_WARNING);
		$this->error = $str;
		$this->error_code = $error->code;
		$this->error_message = $error->error;
		
		if ($this->logmode) {
			$this->log_end();
		}
		
		return false;
	}
	
	//******************************************************************************************************************************************/
	// Get Response: http://dev.getresponse.com/api-doc/
	//******************************************************************************************************************************************/
	
	//******************************************************************************************************************************************/
	// Get Response: Connection Test
	//******************************************************************************************************************************************/	
	function ping() {
		$this->method = 'ping';
		
		$result = $this->run();
		if ($result->result->ping == 'pong') {
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	//******************************************************************************************************************************************/
	// Get Response: Account
	//******************************************************************************************************************************************/
	function get_account_info() {
		$this->method = 'get_account_info';
		
		return $this->run();
	}
	
	function get_account_from_fields() {
		$this->method = 'get_account_from_fields';
		
		return $this->run();
	}
	function get_account_from_field($field = FALSE) {
		$this->method = 'get_account_from_field';
		if (!$field) {
			return;
		}
		
		$this->addParameter('account_from_field', $field);
		
		return $this->run();
	}
	function add_account_from_field($name = FALSE, $email = FALSE) {
		if (!$name || !$email) {
			return;
		}
		// check exist
		
		$this->method = 'add_account_from_field';
		
		$this->addParameter('name', $name);
		$this->addParameter('email', $email);
		
		return $this->run();
	}
	function get_account_domains() {
		$this->method = 'get_account_domains';
		
		return $this->run();
	}
	function get_account_domain($domain = FALSE) {
		$this->method = 'get_account_domain';
		
		if (!$domain) {
			return;
		}
		
		$this->addParameter('account_domain', $domain);
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// Get Response: Campaigns -> A Campaign is sort of like a list
	//******************************************************************************************************************************************/
	function get_campaigns($operator = FALSE, $value = FALSE) {
		$this->method = 'get_campaigns';
		
		if ($operator && $value) {
			$this->addParameter('name', array($operator, $value));
		}
		
		return $this->run();
	}
	function get_campaign($campaign_id = FALSE) {
		$this->method = 'get_campaign';
		
		if (!$campaign_id) {
			return;
		}
		
		$this->addParameter('campaign', $campaign_id);
		
		return $this->run();
	}
	
	function add_campaign($name, $description = '', $from, $reply_to, $confirmation_subject, $confirmation_body, $language_code = 'en') {
		// patch n switch
		/*
		if (!is_int($from)) {
			// get
		} else {
			$from_code = $from;
		}
		if (!is_int($reply_to)) {
			if ($from == $reply_to) {
				$reply_to_code = $from_code;
			}
			// get
		} else {
			$reply_to_code = $reply_to;
		}
		if (!is_int($confirmation_subject)) {
			
		} else {
			$confirmation_subject_code = $confirmation_subject;
		}
		if (!is_int($confirmation_body)) {
			
		} else {
			$confirmation_body_code = $confirmation_body;
		}
		*/
		$this->method = 'add_campaign';
		
		$this->addParameter('name',					$name);
		if ($description) {
			$this->addParameter('description',			$description);
		}
		$this->addParameter('from_field',			$from);
		$this->addParameter('reply_to_field',		$reply_to);
		$this->addParameter('confirmation_subject',	$confirmation_subject);
		$this->addParameter('confirmation_body',	$confirmation_body);
//		$this->addParameter('from_field',			$from_code);
//		$this->addParameter('reply_to_field',		$reply_to_code);
//		$this->addParameter('confirmation_subject',	$confirmation_subject_code);
//		$this->addParameter('confirmation_body',	$confirmation_body_code);
		$this->addParameter('language_code',		$language_code);
		
		return $this->run();
	}
	
	function get_campaign_domain($campaign_id = FALSE) {
		$this->method = 'get_campaign_domain';
		
		$this->addParameter('compaign', $campaign_id);
		
		return $this->run();
	}
	function set_campaign_domain($campaign_id = FALSE, $domain_id = FALSE) {
		if (!$campaign_id) {
			return;
		}
		
		// patch n switch
		if (!is_int($domain_id)) {
			// get
		} else {
			$domain_id_code = $domain_id;
		}
		
		$this->mehtod = 'set_campaign_domain';
		
		$this->addParameter('campaign', $campaign_id);
		$this->addParameter('account_domain', $domain_id);
		
		return $this->run();
	}
	
	function delete_campaign_domain($campaign_id = FALSE) {
		$this->method = 'delete_campaign_domain';
		
		if (!$campaign_id) {
			return;
		}
		
		$this->addParameter('campaign', $campaign_id);
		
		return $this->run();
	}
	
	function get_campaign_postal_address($campaign_id = FALSE) {
		$this->method = 'get_campaign_postal_address';
		
		if (!$campaign_id) {
			return;
		}
		
		$this->addParameter('campaign', $campaign_id);
		
		return $this->run();
	}
	
	function set_campaign_postal_address($campaign, $name = FALSE, $address, $city, $state, $zip, $country, $design = '[[name]], [[address]], [[city]], [[state]] [[zip]], [[country]]') {
		$this->method = 'set_campaign_postal_address';
		
		$this->addParameter('campaign', $campaign_id);
		if ($name) {
			$this->addParameter('name', $name);
		}
		$this->addParameter('address',	$address);
		$this->addParameter('city',		$city);
		$this->addParameter('state',	$state);
		$this->addParameter('zip',		$zip);
		$this->addParameter('country',	$country);
		$this->addParameter('design',	$desgin);
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// Get Response: Messages
	//******************************************************************************************************************************************/
	function get_messages($campaigns = array(), $get_campaigns = '', $type = '', $subject = '', $draft_mode = 'false') {
		$this->method = 'get_messages';
		
		if ($campaigns) {
			$this->addParameter('campaigns', $campaigns);
		}
		if ($get_campaigns) {
			$this->addParameter('get_campaigns', $get_campaigns);
		}
		if ($type) {
			$this->addParameter('type', $type);
		}
		if ($subject) {
			$this->addParameter('subject', $subject);
		}
		$this->addParameter('draft_mode', $draft_mode);
		
		return $this->run();
	}
	
	function get_message($message_id) {
		$this->method = 'get_message';
		
		$this->addParameter('messsage', $message_id);
		
		return $thus->run();
	}
	
	function get_message_contents($message_id) {
		$this->method = 'get_message_contents';
		
		$this->addParameter('message', $message_id);
		
		return $this->run();
	}
	
	function get_message_stats($message_id) {
		$this->method = 'get_message_stats';
		
		$this->addParameter('get_message_stats', $message_id);
		
		return $this->run();
	}
	
	/*
	* contents is array
	* key plain for plain text content
	* key html for html text content
	*
	* include one of
	* contacts array of contact ids
	* get_contacts  search to and send
	*
	* suppression optional but same format as contats
	*/
	function send_newsletter($campaign_id, $from_field = FALSE, $subject, $contents, $flags, $contacts = FALSE, $get_contacts = FALSE, $suppressions = FALSE, $get_suppressions = FALSE) {
		if (!$contacts && !$get_contacts) {
			return;
		}
		$this->method = 'send_newsletter';
		
		$this->addParameter('campaign', $campaign_id);
		if ($from_field) {
			$this->addParameter('from_field', $from_field);
		}
		$this->addParameter('subject', $subject);
		$this->addParameter('contents', $contents);
		$this->addParameter('flags', $flags);
		if ($contacts) {
			$this->addParameter('contacts', $contacts);
		}
		if ($get_contacts) {
			$this->addParameter('get_contacts', $get_contacts);
		}
		if ($suppressions) {
			$this->addParameter('suppressions', $suppressions);
		}
		if ($get_suppressions) {
			$this->addParameter('get_suppressions', $get_suppression);
		}
		
		return $this->run();
	}
	
	// responder
	// day of cycle in range of 0-1000
	function add_follow_up($campaign_id, $from_field = FALSE, $subject, $contents, $flags, $day_of_cycle) {
		$form_field_code = '';
		if ($from_field) {
			if (!is_int($from_field)) {
				//get 
			} else {
				$from_field_code = $form_field;
			}
		}
		
		$this->method = 'add_follow_up';
		
		$this->addParameter('campaign', $campaign_id);
		if ($from_field_code) {
			$this->addParameter('from_field', $from_field_code);
		}
		$this->addParameter('subject', $subject);
		$this->addParameter('contents', $contents);
		if ($flags) {
			$this->addParameter('flags', $flags);
		}
		$this->day_of_cycle('day_of_cycle', $day_of_cycle);
		
		return $this->run();
	}
	
	function add_draft($campaign_id, $form_field = FALSE, $subject, $contents, $flags, $type) {
		$form_field_code = '';
		if ($from_field) {
			if (!is_int($from_field)) {
				//get 
			} else {
				$from_field_code = $form_field;
			}
		}
		
		$this->method = 'add_draft';
		
		$this->addParameter('campaign', $campaign_id);
		if ($from_field_code) {
			$this->addParameter('from_field', $from_field_code);
		}
		$this->addParameter('subject', $subject);
		$this->addParameter('contents', $contents);
		if ($flags) {
			$this->addParameter('flags', $flags);
		}
		$this->addParameter('tpye', $type);

		return $this->run();
	}
	
	function delete_newsletter($message_id) {
		$this->method = 'delete_newsletter';
		
		$this->addParameter('message', $message_id);
		
		return $this->run();
	}
	
	function delete_follow_up($message_id) {
		$this->method = 'delete_follow_up';
		
		$this->addParameter('message', $message_id);
		
		return $this->run();
	}
	
	function set_follow_up_cycle($message_id, $cycle) {
		$this->method = 'set_follow_up_cycle';
		
		$this->addParameter('message', $message_id);
		$this->addParameter('cycle', $cycle);
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// Get Response: Contacts
	// http://dev.getresponse.com/api-doc/#get_contacts
	//******************************************************************************************************************************************/
	function get_contacts($parameters = array()) {
		/// omg
		
		$this->method = 'get_contacts';
		
		$this->parameters = $parameters;
		
		return $this->run();
	}
	
	function get_contact($contact_id) {
		$this->method = 'get_contact';
		
		$this->addParameter('contact', $contact_id);
		
		return $this->run();
	}
	function set_contact_name($contact_id, $name) {
		$this->method = 'set_contact_name';
		
		$this->addParameter('contact', $contact_id);
		$this->addParameter('name', $name);
		
		return $this->run();
	}
	function get_contact_customs($contact_id) {
		$this->method = 'get_contact_customs';
		
		$this->addParameter('contact', $contact_id);
		
		return $this->run();
	}
	
	/*
	* custs array key name => name, content => value
	* removed if content value is null
	* updated to new content value if already present
	* added if not present
	*/
	function set_contact_customs($contact_id, $customs) {
		$this->method = 'set_contact_custsom';
		
		$this->addParameter('contact', $contact_id);
		$this->addParameter('customs', $customs);
		
		return $this->run();
	}
	
	// gets the GEO location based on the sign up IP, generally useless for the implementation
	function get_contact_geoip($contact_id) {
		$this->method = 'get_contact_geoip';
		
		$this->addParameter('contact', $contact_id);
		
		return $this->run();
	}
	
	// stats
	function get_contact_opens($contact_id) {
		$this->method = 'get_contact_opens';
		
		$this->addParameter('contact', $contact_id);
		
		return $this->run();
	}
	function get_contact_clicks($contact_id) {
		$this->method = 'get_contact_clicks';
		
		$this->addParameter('contact', $contact_id);
		
		return $this->run();
	}
	
	// change where a user is in the auto responder cycle
	function set_contact_cycle($contact_id, $cycle_day) {
		$this->method = 'set_contact_cycle';
		
		$this->addParameter('contact', $contact_id);
		$this->addParameter('cycle_day', $cycle_day);
		
		return $this->run();
	}
	
	// add a contact
	// contacts are specific to a campaign......
	function add_contact($campaign_id, $action = 'standard', $name, $email, $cycle_day = FALSE, $ip = FALSE, $customs = FALSE) {
		$this->method = 'add_contact';
		
		$this->addParameter('campaign', $campaign_id);
		$this->addParameter('action', $action);
		$this->addParameter('name', $name);
		$this->addParameter('email', $email);
		if ($cycle_day) {
			$this->addParameter('cycle_day', $cycle_day);
		}
		if ($ip) {
			$this->addParameter('ip', $ip);
		}
		if ($customs) {
			$this->addParameter('customs', $customs);
		}
		
		return $this->run();
	}
	
	function move_contact($contact_id, $target_campaign_id) {
		$this->method = 'move_contact';
		
		$this->addParameter('contact', $contact_id);
		$this->addParameter('campaign', $target_campaign_id);
		
		return $this->run();
	}
	function delete_contact($contact_id) {
		$this->method = 'delete_contact';
		
		$this->addParameter('contact', $contact_id);
		
		return $this->run();
	}
	
	// list contacts that have been deleted
	// campaigns is a array of ids
	function get_contacts_deleted($campaigns = FALSE, $get_campaigns = FALSE, $email = FALSE, $created_on = FALSE, $deleted_on = FALSE) {
		$this->method = 'get_contacts_deleted';
		
		if ($campaigns) {
			$this->addParameter('campaigns', $campaigns);
		}
		if ($get_campaigns) {
			$this->addParameter('get_campaigns', $get_campaigns);
		}
		if ($email) {
			$this->addParameter('email', $email);
		}
		if ($created_on) {
			$this->addParameter('created_on', $created_on);
		}
		if ($deleted_on) {
			$this->addParameter('deleted_on', $deleted_on);
		}
		
		return $this->run();
	}
	
	function get_contacts_subscription_stats($created_on = FALSE) {
		$this->method = 'get_contacts_subscription_stats';
		
		if ($created_on) {
			$this->addParameter('created_on', $created_on);
		}
		
		return $this->run();
	}
	
	function get_contacts_amount_per_account() {
		$this->method = 'get_contacts_amount_per_account';
		
		return $this->run();
	}
	function get_contacts_amount_per_campaign() {
		$this->method = 'get_contacts_amount_per_campaign';
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// Get Response: Links
	//******************************************************************************************************************************************/
	// message array of message id
	function get_links($messages = FALSE, $get_messages = FALSE, $url = FALSE) {
		$this->method = 'get_links';
		
		$this->addParameter('messages', $messages);
		$this->addParameter('get_messages', $get_messages);
		$this->addParameter('url', $url);
		
		return $this->run();
	}
	
	function get_link($link) {
		$this->method = 'get_link';
		
		$this->addParameter('link', $link);
		
		return $this->run();
	}
	
	//******************************************************************************************************************************************/
	// Get Response: Blacklists
	//******************************************************************************************************************************************/
	function get_account_blacklist() {
		$this->method = 'get_account_blacklist';
		
		return $this->run();
	}

	//******************************************************************************************************************************************/
	// Get Response: Implementation stopped
	//******************************************************************************************************************************************/

	//******************************************************************************************************************************************/
	// Get Response: Confirmation
	//******************************************************************************************************************************************/
	function get_confirmation_subjects() {
		$this->method = 'get_confirmation_subjects';
		
		return $this->run();
	}
	function get_confirmation_bodies() {
		$this->method = 'get_confirmation_bodies';
		
		return $this->run();
	}
}
