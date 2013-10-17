<?php

class ymind_auth {

	var $license_key=false;
	var $error_message = '';
	
	function __construct() {
		$this->check_activation_request();		
		
		$this->check_db_for_key();
	}
	
	function remote_request($url) {
		if (ini_get('allow_url_fopen')) {
			if (!$string = @file_get_contents($url)) {
				$string = 'Could not connect to the server to make the request.';
			}
		} else if (extension_loaded('curl')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$string = curl_exec($ch);
			curl_close($ch);
		} else {
			$string = 'This feature will not function until either CURL or fopen to urls is turned on.';
		}
	
		return $string;
	}
	
	function check_activation_request() {
		if (isset($_POST['activate_plugin']) && $_POST['registration_email'] != '') {
			$connection_string = YMIND_PLUGIN_LICENSING . '&email=' . $_POST['registration_email'];
			$activate = $this->remote_request($connection_string);

			if ($activate == '1') {
				$this->set_key($_POST['registration_email']);
			} else {
				$this->error_message = '<div class="error"><div style="padding: 5px;">' . $activate . '</div></div>';
			}
		}
	}

	function check_db_for_key() {
		$this->license_key = get_option('ymind_license_key');
	}

	function get_key() {
		if (!$this->license_key) {
			$this->check_db_for_key();
		}

		return $this->license_key;

	}

	function ymind_set_key($key) {
		$key = base64_encode(md5($key));
		
		$this->licence_key = $key;
		add_option('ymind_license_key', $key);
	}

	function check_key() {
		$key = $this->get_key();
		
		return ($key ? true:false);
	}
	
}

?>