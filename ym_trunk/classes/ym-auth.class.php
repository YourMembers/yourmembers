<?php

class YourMember_Authentication {

	var $ym_license_key=false;

	function YourMember_Authentication() {
		$this->ym_check_db_for_key();
	}

	function ym_check_db_for_key() {
		$this->ym_license_key = get_option('ym_license_key');
	}

	function ym_get_key() {
		if (!$this->ym_license_key) {
			$this->ym_check_db_for_key();
		}

		return $this->ym_license_key;

	}

	function ym_set_key($key) {
		$key = base64_encode(md5($key));
		
		$this->ym_licence_key = $key;
		update_option('ym_license_key', $key);
	}

	function ym_check_key() {
		
		return true;
	}

	function ym_authorize_key($key) {
		global $ym_version_resp;

		return TRUE;
	}
	function tos_submit() {
		global $ym_version_resp;

		$version_id = ym_post('tosversion');
		$choice = ym_post('tos');

		if ($choice == 'Continue') {
			if (!is_email(ym_post('confirm_email'))) {
				return new WP_Error('email', __('You must provide a valid Email Address', 'ym'));
			}
			if (!ym_post('tickbox')) {
				return new WP_Error('terms', __('You must check the Acceptance Tick Box', 'ym'));
			}
			// accepted
			update_option('ym_tos_version_accepted', $version_id);
			$connection_string = YM_TOS_INFORM_URL . '&email=' . rawurlencode(ym_post('confirm_email'));
			$connection_string .= '&nmp_tos_accept=yes&tos_version_id=' . $version_id . '&choice=' . $choice;
			ym_remote_request($connection_string);

		} else {
			delete_option('ym_license_key');
			delete_option('ym_tos_version_accepted');

			echo '<script>window.location=\'' . $ym_version_resp->tos->tos_no_url . '\';</script>';
			exit;
		}
	}
}
