<?php

/*
* $Id: facebook.php 1864 2012-02-13 18:19:23Z bcarlyon $
* $Date: 2012-02-13 18:19:23 +0000 (Mon, 13 Feb 2012) $
* $Revision: 1864 $
* $Author: bcarlyon $
*/

class Facebook {
	private $base_url = 'https://graph.facebook.com/';
	private $access_token = '';
	
	public $user_data;
	
	private $method;
	private $submethod;
	private $additional;
	
	function __construct($oauth_token, $code = FALSE) {
		if ($code) {
			// authenticate the oauth code
			
			$this->auth = $this->oauthenticate($code);
			
			return;
		}
		$this->access_token = $oauth_token;
		
		// fetch me
		$this->user_data = $this->me();
		
		if (!$this->user_data) {
			$this->initok = FALSE;
			return;
		} else {
			$this->initok = TRUE;
		}
		$_SESSION['ym_facebook_me_cache'] = $this->user_data;
		
		$username = $this->user_data->username;
		$this->photo = 'http://graph.facebook.com/' . $username . '/picture';
		
		$likes = $this->likes();
		
		$this->likes = $likes->data;
	}
	
	/*
	* Helper
	*/
	private function run($json = TRUE) {
		$url = $this->base_url;
		$url .= $this->method;
		
		if ($this->submethod) {
			$url .= '/' . $this->submethod;
		}
		if ($this->access_token) {
			$url .= '?access_token=' . $this->access_token;
		}
		if ($this->additional) {
			$url .= $this->additional;
			$this->additional = '';
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		curl_close($ch);
		
		if ($json) {
			$result = json_decode($result);
		} else {
			return $result;
		}
		
		if (isset($result->error) && $result->error) {
			// error
			$this->error = $result->error;
			return false;
		}
		
		return $result;
	}
	
	/*
	* OAUTH
	*/
	private function oauthenticate($code) {
		$this->method = 'oauth';
		$this->submethod = 'access_token';
		
		$this->additional = $code;
		
		return $this->run(FALSE);
	}
	
	public function custom($method, $submethod = '') {
		$this->method = $method;
		$this->submethod = $submethod;
		
		return $this->run();
	}
	
	/*
	* API Functions
	*/
	public function me() {
		$this->method = 'me';
		
//		if ($_SESSION['ym_facebook_me_cache']) {
//			return $_SESSION['ym_facebook_me_cache'];
//		}
		
		return $this->run();
	}
	public function permissions() {
		$this->method = 'me';
		$this->submethod = 'permissions';
		
		return $this->run();
	}
	public function likes($like = 'me') {
		$this->method = $like;
		$this->submethod = 'likes';
		
		$result = $this->run();
		
		if (isset($_SESSION['facebook_page']) && $_SESSION['facebook_page']) {
			// have page data
			$page_id = $_SESSION['facebook_page']->id;
			$liked = $_SESSION['facebook_page']->liked;
			
			if ($liked) {
				$packet = array(
					'name'			=> 'The Fan Page',
					'category'		=> 'unknown',
					'id'			=> $page_id,
					'created_time'	=> time()
				);
				$result->likes[] = $packet;
			}
		}
		
		return $result;
	}
	
	public function fql_query($query, $json = TRUE) {
		$url = 'https://api.facebook.com/method/fql.query?';
		$url .= 'access_token=' . $this->access_token . '&';
		$url .= 'format=JSON&';
		$url .= 'query=' . urlencode($query);
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		curl_close($ch);
		
		if ($json) {
			$result = json_decode($result);
		} else {
			return $result;
		}
		
		if ($result->error) {
			// error
			$this->error = $result->error;
			return false;
		}
		
		return $result;
	}
	/**
	* Handy queries
	*/
	public function get_url_by_id($id) {
		$query = 'SELECT url FROM link WHERE link_id = ' . $id;
		
		$data = $this->fql_query($query);
		
		if (sizeof($data)) {
			return $data[0]->url;
		}
		return FALSE;
	}
	public function get_id_by_url($url) {
		$url = 'https://graph.facebook.com/?id=' . urlencode(strtolower($url));
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$result = json_decode($result);
		$id = $result->id;
		
		if ($id) {
			return $id;
		} else {
			return FALSE;
		}
	}
}

function facebook_uncode($data) {
	global $facebook_settings;
	list($encoded_sig, $payload) = explode('.', $data, 2);

	$sig	= base64_decode(strtr($encoded_sig, '-_', '+/'));
	$data	= base64_decode(strtr($payload, '-_', '+/'));
	$data	= json_decode($data);

	if (strtoupper($data->algorithm) !== 'HMAC-SHA256') {
		// bad alg
		return false;
	}

	// check sig
	$expected_sig = hash_hmac('sha256', $payload, $facebook_settings->app_secret, $raw = true);
	if ($sig !== $expected_sig) {
		// bad json
		return false;
	}

	if ($data) {
		return $data;
	}
	return;
}
