<?php

/* 
* Aweber Class
* Uses Google Code OAuth Class PHP
* http://code.google.com/p/oauth/
* 
* $Id: aweber_class.php 159 2011-03-21 10:48:31Z bcarlyon $
* $Date: 2011-03-21 10:48:31 +0000 (Mon, 21 Mar 2011) $
* $Revision: 159 $
* $Author: bcarlyon $
*
*/

//include('common.inc.php');
require_once(YM_MM_CLASSES_DIR . 'OAuth.php');

class aweber_oauth {
	var $request_url = 'https://auth.aweber.com/1.0/oauth/request_token';
	var $auth_url = 'https://auth.aweber.com/1.0/oauth/authorize';
	var $token_url = 'https://auth.aweber.com/1.0/oauth/access_token';
	var $base_url = 'https://api.aweber.com/1.0/';
	var $application = 'https://auth.aweber.com/1.0/oauth/authorize_app/';
	
	var $callback_url = '';
	
	var $consumer = '';
	var $consumer_key = '';
	var $token = '';
	var $token_secret = '';
	var $oauth_data = '';
	
	var $action = '';
	var $method = 'get';
	
	var $disconnected = FALSE;
	
	function __construct($key = '', $secret = '', $callback_url = '') {
		if (!$key) {
			return;
		}
		
		$this->callback_url = $callback_url;

		// generate the oauth object
		$this->consumer = new OAuthConsumer($key, $secret, NULL);
		$this->consumer_key = $key;
		
		$this->hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
	}
	
	function distro_url($app_id) {
		return $this->application . $app_id;
	}
	
	// Attach to Aweber
	function connect($token = '', $token_secret = '') {
		$req_req = OAuthRequest::from_consumer_and_token($this->consumer, NULL, 'POST', $this->request_url, array('oauth_token' => '', 'oauth_callback' => $this->callback_url));
		$req_req->sign_request($this->hmac_method, $this->consumer, NULL);

		// post to request tokent url
		$result = $this->connect_run($req_req, $_GET);
		parse_str($result, $data);
		
		// rediret to aweber
		header('Location: ' . $this->auth_url . '?oauth_token=' . $data['oauth_token']);// . '&oauth_callback=' . $this->callback_url);
		exit;
	}

	function access($token = '', $verifier = '') {
		$this->token = new OAuthConsumer($token, $this->token_secret);
		$acc_req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, "POST", $this->token_url, array('oauth_token_secret' => $this->token_secret, 'oauth_verifier' => $verifier));//, $_GET);//array('oauth_verifier' => $token_secret));//, array('oauth_token' => $token));
		$acc_req->sign_request($this->hmac_method, $this->consumer, $this->token);

		$result = $this->connect_run($acc_req);
		parse_str($result, $data);
		
		$this->access_token = $data['oauth_token'];
		$this->access_token_secret = $data['oauth_token_secret'];
		
		return;
	}
	
	private function connect_run($url, $data = array()) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
		
//		$useragent="Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
//		curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
		
		$return = curl_exec($ch);
		curl_close($ch);

		return $return;
	}
	
	private function pairconnect(&$url, $method) {
		$this->token = new OAuthConsumer($this->access_token, $this->access_token_secret);
		
		$req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, strtoupper($method), $url, array());
		$req->sign_request($this->hmac_method, $this->consumer, $this->token);
		
		$url = $req->to_url();
	}
	
	private function run() {
		$url = $this->base_url . $this->action;

		$this->pairconnect($url, $this->method);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 0);

		switch ($this->method) {
			case 'patch':
			case 'delete':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
//				curl_setopt($ch, CURLOPT_POSTFIELDS, $this->oauth_data);		
				break;
			case 'post':
				curl_setopt($ch, CURLOPT_POST, TRUE);
//				curl_setopt($ch, CURLOPT_POSTFIELDS, $this->oauth_data);		
				break;
			case 'get':
			default:
				// nothing
		}
		if ($this->bodydata) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->bodydata);
			$this->bodydata = '';
		}

		//reset
		$this->method = 'get';

		$json = curl_exec($ch);
		$info = curl_getinfo($ch);
		if ($info['http_code'] == '401') {
			$this->disconnected = TRUE;
		}
		curl_close($ch);

		$this->info = $info;
		
		$return = json_decode($json);

		return $return;		
	}
	
	// helper
	function account_id($account_id = FALSE) {
		if ($account_id) {
			return $account_id;
		} else {
			return $this->account_id;
		}
	}
	
	// awber API
	// https://labs.aweber.com/docs/reference/1.0
	
	// A collection of accounts that you are authorized to access.
	function accounts() {
		$this->action = 'accounts';
		
		return $this->run();
	}
		// details about an account
		function account($account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->action = 'accounts/' . $account_id;
			
			return $this->run();
		}
	
	// A collection of lists for a given account.
	function lists($account_id = FALSE) {
		$account_id = $this->account_id($account_id);
		$this->action = 'accounts/' . $account_id . '/lists';
		
		return $this->run();
	}
		function alist($list_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id;
			
			return $this->run();
		}
	// A collection of subscribers for a given list.
	function subscribers($list_id, $account_id = FALSE) {
		$account_id = $this->account_id($account_id);
		$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/subscribers';
		
		return $this->run();
	}
		function get_subscriber($list_id, $sub_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/subscribers/' . $sub_id;
			
			return $this->run();
		}
			function get_subscriber_activity($list_id, $sub_id, $account_id = FALSE) {
				$account_id = $this->account_id($account_id);
				$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/subscribers/' . $sub_id . '?ws.op=getActivity';

				return $this->run();
			}
		// aka update a subscriber
		function patch_subscriber($list_id, $sub_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->method = 'patch';
			return $this->get_subscriber($account_id, $list_id, $sub_id);
		}
		// delete a subscriber
		function delete_subscriber($list_id, $sub_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->method = 'delete';
			return $this->get_subscriber($account_id, $list_id, $sub_id);
		}
		
		// create a subscriber
		// this doesn't actually do subscriber addition, api notes says this
//		function put_subscriber($list_id, $sub_id, $account_id = FALSE) {
		function put_subscriber($list_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->method = 'put';
			//return $this->get_subscriber($account_id, $list_id);//, $sub_id);
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/subscribers';
			
			return $this->run();
		}
	
	// A collection of campaign types (followups or broadcasts) available for a given list.
	function campaigns($list_id, $account_id = FALSE) {
		$account_id = $this->account_id($account_id);
		$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/campaigns';
		
		return $this->run();
	}
		// um what?
		// A single campaign type for a given list (followups or broadcasts)
		function campaign($list_id, $campaign_id = '', $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/campaigns';//as doc
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/campaigns/' . $campaign_id;
			
			return $this->run();
		}
		// A single broadcast campaign for a given list.
		function broadcast_campaign($list_id, $campaign_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/campaigns/' . $campaign_id;
			
			return $this->run();
		}
		function followup_campaign($list_id, $campaign_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/campaigns/' . $campaign_id;
			
			return $this->run();
		}
		
		// stopped integrating at this point....cant tell aweber to send only sync sync
	
	function web_forms($list_id, $account_id = FALSE) {
		$account_id = $this->account_id($account_id);
		$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/web_forms';
		return $this->run();
	}
		function web_form($list_id, $form_id, $account_id = FALSE) {
			$account_id = $this->account_id($account_id);
			$this->action = 'accounts/' . $account_id . '/lists/' . $list_id . '/web_forms/' . $form_id;
			return $this->run();
		}
	
	
	// hack
	function add_subscriber($list_id, $data) {
		$url = 'http://www.aweber.com/scripts/addlead.pl';
		
		// aweber needs curl to do post as a string for the right mime tpye, rather than editing the fuction
		// just pass a string
		$string = '';
		foreach ($data as $key => $item) {
			$string .= $key . '=' . urlencode($item) . '&';
		}
		
		$this->connect_run($url, $string);
	}
}
