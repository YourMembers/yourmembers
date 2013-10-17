<?php

/**
* Version 1.0.0
* 
* $Id: campaign_monitor_class.php 165 2011-03-22 16:25:12Z bcarlyon $
* $Date: 2011-03-22 16:25:12 +0000 (Tue, 22 Mar 2011) $
* $Revision: 165 $
* $Author: bcarlyon $
* 
* Barry Carlyon
* 23/02/2011
* Initial Creation based on mailchimp
*/

class campaign_monitor {
	private $apikey;
	private $client;
	private $add;
	private $submethod;
	private $url;
	private $method;
	private $parameters;
	private $jsonobject;
	public $result;
	public $error;
	private $post;
	
	function __construct($apikey, $client = FALSE, $assoc = FALSE) {
		$this->apikey = $apikey;
		$this->client = $client;

		$this->url = 'https://api.createsend.com/api/v3/';
		
		$this->jsonobject = $assoc;
	}
	
	private function addParameter($name, $value) {
		$this->parameters[$name] = $value;
	}
	
	//******************************************************************************************************************************************/
	// campaign_monitor RTFM
	// http://www.campaignmonitor.com/api/getting-started/
	//******************************************************************************************************************************************/
	private function run() {
		$url = $this->url . $this->method;
		
		if ($this->add) {
			$add = $this->add;
			$url .= '/' . $this->$add;
		}
		if ($this->submethod) {
			$url .= '/' . $this->submethod;
		}
		
		$url .= '.json';
		// reset flag
		$this->add = $this->submethod = '';

		if ($this->post) {
		} else if ($this->parameters) {
			$url .= '?' . http_build_query($this->parameters);
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if ($this->post) {
			$this->post = false;
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array_pop($this->parameters));
		}
		$this->parameters = array();
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));

		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $this->apikey . ':magic');
		if ($json = curl_exec($ch)) {
			$return = json_decode($json, $this->jsonobject);
			$this->return = $return;
			$error = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($error != 200) {
				curl_close($ch);
				return $this->error($error);
			} else {
				curl_close($ch);
				$this->error = 'ok';
			}
		} else {
			return $return;
		}
		return $return;
	}
	private function error($error) {
		$str = 'Error Code: ' . $error . ' ' . $this->return->Code . ': ' . $this->return->Message . ' -- ' . $this->method . '/' . $this->submethod;
		trigger_error($str, E_USER_WARNING);
		$this->error = $str;
		return false;
	}
	
	//******************************************************************************************************************************************/
	// campaign_monitor Account
	// http://www.campaignmonitor.com/api/account/
	//******************************************************************************************************************************************/
	function clients() {
		$this->method = 'clients';
		
		return $this->run();
	}
	//******************************************************************************************************************************************/
	// campaign_monitor Client
	// http://www.campaignmonitor.com/api/campaigns
	//******************************************************************************************************************************************/
	function campaign_create($name, $subject, $fromname, $fromname, $replyto, $htmlurl, $texturl, $listIDS, $SegmentIds = array()) {
		
	}
	//******************************************************************************************************************************************/
	// campaign_monitor Client
	// http://www.campaignmonitor.com/api/clients/
	//******************************************************************************************************************************************/
	function client_create() {
		
	}
	function client_details() {
		$this->method = 'clients';
		$this->add = 'client';
		
		return $this->run();
	}
	
	function lists() {
		$this->method = 'clients';
		$this->add = 'client';
		$this->submethod = 'lists';
		
		return $this->run();
	}
	//******************************************************************************************************************************************/
	// campaign_monitor Client
	// http://www.campaignmonitor.com/api/lists/
	//******************************************************************************************************************************************/
	function list_create() {
		
	}
	function list_details() {
		
	}
	
	function active_subscribers($listId, $date = '1980-01-01', $page = 1, $pagesize = 1000, $orderfield = 'date', $orderdir = 'asc') {
		$this->method = 'lists';
		$this->add = 'list';
		$this->list = $listId;
		$this->submethod = 'active';
		
		$this->addParameter('date', $date);
		$this->addParameter('page', $page);
		$this->addParameter('pagesize', $pagesize);
		$this->addParameter('orderfield', $orderfield);
		$this->addParameter('orderdir', $orderdir);
		
		return $this->run();
	}
	function unsubscribed_subscribers($listId, $date = '1980-01-01', $page = 1, $pagesize = 1000, $orderfield = 'date', $orderdir = 'asc') {
		$this->method = 'lists';
		$this->add = 'list';
		$this->list = $listId;
		$this->submethod = 'unsubscribed';

		$this->addParameter('date', $date);
		$this->addParameter('page', $page);
		$this->addParameter('pagesize', $pagesize);
		$this->addParameter('orderfield', $orderfield);
		$this->addParameter('orderdir', $orderdir);

		return $this->run();
	}
	
	function create_custom_field($name, $type = 'MultiSelectOne', $options = array('HTML', 'Text')) {
		$internal = $this;
		$internal->method = 'lists';
//		$internal->
	}
	//******************************************************************************************************************************************/
	// campaign_monitor Client
	// http://www.campaignmonitor.com/api/subscribers/
	//******************************************************************************************************************************************/
	function add_subscriber($listId, $email, $name = '', $custom_fields = array()) {
		$this->post = TRUE;
		$this->method = 'subscribers';
		$this->add = 'list';
		$this->list = $listId;
		
		$custom = array();
		foreach ($custom_fields AS $key => $value) {
			$custom[] = array(
				'Key'	=> utf8_encode($key),
				'Value'	=> utf8_encode($value)
			);
//			$this->
		}
		$subscriber = json_encode(array(
			'EmailAddress'	=> utf8_encode($email),
			'Name'			=> utf8_encode($name),
			'CustomFields'	=> $custom,
			'Resubscribe'	=> false
		));
		$this->addParameter('subscriber', $subscriber);

		return $this->run();
	}
}
