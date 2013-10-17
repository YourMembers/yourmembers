<?php

/*
* $Id: ym-user.class.php 2612 2013-03-13 09:30:27Z tnash $
* $Revision: 2612 $
* $Date: 2013-03-13 09:30:27 +0000 (Wed, 13 Mar 2013) $
*/

class YourMember_User {
	var $account_type_join_date = '';
	var $custom_fields = '';
	var $rss_token = '';
	var $status = '';

	var $trial_on = 0;
	var $trial_cost = 0;
	var $trial_duration = 0;
	var $trial_duration_type = 'd';
	var $trial_taken = 0;//will contain pack ID of trial
	var $duration = 0;
	var $duration_type = 'm';
	var $amount = 0;
	var $currency = '';
	var $last_pay_date = '';
	var $expire_date = '';

	var $account_type = 'guest';
	var $status_str = '';
	var $payment_type = '';

	var $pack_id = '';
	var $role = 'subscriber';
	//var $cycle_count = '';

	var $reminder_email_sent = FALSE;
	var $gateway_used = '';

	var $hide_old_content = FALSE;

	// Group Membership
	var $parent_id = FALSE;
	var $child_ids = array();
	var $child_accounts_allowed = 0;
	var $child_accounts_package_types = array();
	var $child_accounts_packages = array();

	// Admin Bar Control
	var $hide_admin_bar = FALSE;

	// operators
	function __construct($ID = FALSE) {
		$this->name = 'ym_user';
		if ($ID) {
			return $this->load($ID);
		}
		return $this;
	}

	public function api_expose() {
		// these elements get stored separately as well as in the user object
	
		// get vars to expose in single keys
		$api_expose = array(
			'account_type',
			'account_type_join_date',
			'rss_token',
			'status',
			'hide_old_content',
			'payment_type'
		);

		$extras = apply_filters('ym_user_api_expose', array());
		foreach ($extras as $extra) {
			$this->$extra = isset($this->$extra) ? $this->$extra : '';
			$api_expose[] = $extra;
		}

		return $api_expose;
	}

	private function load($ID) {
		$this->ID = $ID;

		// get everything in one place.
		// it is never stored
		$this->data = get_user_by('id', $ID);

		$data = get_user_meta($ID, $this->name, TRUE);
		if (!$data) {
			// no object
			$this->valid = FALSE;
			return $this;
		}
		$this->valid = TRUE;

		foreach ($data as $key => $value) {
			$this->$key = $value;
		}

		/**
		DO NOT LOAD API EXPOSED FIELDS
		FROM THE USER META
		AS THEY WILL BE SYNCED BACK IN VIA API_SAVE()!
		*/

		// http://codex.wordpress.org/Plugin_API/Filter_Reference/show_admin_bar
		if ( $this->hide_admin_bar )
			add_filter( 'show_admin_bar', '__return_false' );

		do_action('ym_user_is_loaded');

		return $this;
	}

	/**
	make private
	always call with specific user ID and on the static function
	*/
	private function api_save() {
		// update api exposed fields into the ym user object
		// call this when you updated one of the API fields and
		// want to sync it back into the core object
		foreach ($this->api_expose() as $field) {
			$this->$field = get_user_meta($this->ID, 'ym_' . $field, TRUE);
			echo 'Loadined ' . $field . ' to ' . $this->$field . '<br />';
		}
		return $this->save();
	}
	static function api_update($user_id) {
		$user = new YourMember_User($user_id);
		return $user->api_save();
	}

	public function save() {
		$ID = $this->ID;
		$name = $this->name;
		$data = FALSE;
		if (isset($this->data)) {
			$data = $this->data;
		}
		unset($this->ID, $this->name, $this->data);

		foreach ($this->api_expose() as $field) {
			update_user_meta($ID, 'ym_' . $field, $this->$field);
		}

		$result = update_user_meta($ID, $name, $this);

		// key reset
		$this->ID = $ID;
		$this->name = $name;
		if ($data) {
			$this->data = $data;
		}

		return $result;
	}

	// helpers

	function update($vars, $save = FALSE) {
		$class_vars = $this->get_class_vars();

		foreach ($class_vars as $key => $value) {
			if (isset($vars[$key])) {
				$this->$key = $vars[$key];
			}
		}

		if ($save) {
			$this->save();
		}
	}

	function update_from_post($save = FALSE) {
		$class_vars = $this->get_class_vars();

		foreach ($class_vars as $key=>$value) {
			if (isset($_POST[$key])) {
				$this->$key = stripslashes($_POST[$key]);
			}
		}

		if ($save) {
			$this->save();
		}
	}

	private function get_class_vars() {
		$class = get_class($this);
		$vars = get_class_vars($class);
		// catch additional vars, but only additional
		$vals = apply_filters('ym_user_api_expose', array());
		foreach ($vals as $val) {
			$vars[$val] = '';
		}

		return $vars;
	}

	// login
	function is_logging_in() {
		// a superflous check.....
		if (!$this->ID)
			return;
		return update_user_meta( $this->ID, 'ym_user_last_login', time() );
	}

	// generate
	// custom_fields contains the WHOLE record
	function create($email, $sub_id=false, $smflag=false, $username=false, $password=false, $custom_fields=false, $package=false, $expire_date=false) {
		global $wpdb;

		// is email a email?
		if (empty($email)) {
			return new WP_Error( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.' ) );
		} else if (!is_email($email)) {
			return new WP_Error( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.' ) );
		}
		if(email_exists($email)){
			return new WP_Error('existing_user_email', __('This email address is already registered.') );
		}

		if(!$username){
			$username = $email;
		}
		if($username){
			if(username_exists($username)){
				return new WP_Error('existing_user_login', __('This Username is already registered.') );
			}
		}

		if(!$password){
			$password = wp_generate_password( 12, false );
		}
			
		$pw_hash = wp_hash_password($password);
			
		//$user_id = wp_create_user($username,$password,$email); - can't be used due to register action
		$user_login = $username;
		$user_pass = $pw_hash;
		$user_email = $email;
		$user_nicename = $username;
		$display_name = $username;
		$user_registered = gmdate('Y-m-d H:i:s');

		$user_url = $custom_fields['user_url'];

		$data = compact( 'user_pass', 'user_email', 'user_url', 'user_nicename', 'display_name', 'user_registered' );
		$data = stripslashes_deep( $data );
			
		$wpdb->insert( $wpdb->users, $data + compact( 'user_login' ) );
		$user_id = (int)$wpdb->insert_id;

		$rich_editing = 'true';
		$comment_shortcuts = 'false';
		$admin_color = 'fresh';
		$use_ssl = 0;
			
		update_user_meta( $user_id, 'rich_editing', $rich_editing);
		update_user_meta( $user_id, 'comment_shortcuts', $comment_shortcuts);
		update_user_meta( $user_id, 'admin_color', $admin_color);
		update_user_meta( $user_id, 'use_ssl', $use_ssl);

		$this->ID = $user_id;

		//Custom Fields
		if($custom_fields){
			//take the array and check the field names
			if(is_array($custom_fields)){
					$ym_custom = get_user_meta($user_id, 'ym_custom_fields', TRUE);
					
				foreach($custom_fields as $field => $value){
					$custom_field = ym_get_custom_field_by_name($field);
					if($custom_field){
						
						$ym_custom[$custom_field['id']] = $value;
						//Patch to fix first_name & last_name not populating on import
						if (in_array($custom_field['name'], array('first_name', 'last_name'))) {
								update_user_meta($user_id, $custom_field['name'], $value);
						}
					}
				}
				update_user_meta($user_id, 'ym_custom_fields', $ym_custom);
			}
		}

		// package
		if(isset($sub_id) || isset($package)) {
			if(isset($sub_id)) {
				// pass to payment engine
				$pay = new ym_payment_gateway();
				$pay->code = 'ym_create';
				$pay->name = 'ym_create';
				$pay->nomore_email = TRUE;
				// call full update
				$pay->do_buy_subscription($sub_id, $user_id, TRUE);
				//Override the expire date if its set
				if($expire_date){
					$data = array(
						'expire_date'	=> $expire_date,
					);
					//Update the user data
					$this->update($data);
					$this->save();
				}

			} elseif (isset($package) && is_array($package)) {
				$this->account_type = $package['account_type'];
				$this->duration = $package['duration'];
				$this->duration_type = $package['duration_type'];
				if($package['expire_date']) {
					$this->expire_date = intval($package['expire_date']);
				} else {
					$this->expire_date = $this->expiry_time($package['duration'], $package['duration_type']);
				}
				$this->role = $package['role'];

				$this->last_pay_date = time();
				$this->status_str = __('API Account: ', 'ym') . ucwords($this->account_type);

				// make active
				$this->status = YM_STATUS_ACTIVE;
				
				@ym_log_transaction(YM_ACCOUNT_TYPE_ASSIGNATION,  $this->account_type, $user_id);
				
				//Update the user data
				$this->save();

				//log in transaction table
				@ym_log_transaction(YM_ACCESS_EXTENSION, date(YM_DATEFORMAT, time()), $user_id);
				@ym_log_transaction(YM_USER_STATUS_UPDATE, 'Active', $user_id);

				//Set a role
				if (!$this->role) {
					$this->role = 'subscriber';
				}
				$this->updaterole($this->role);
			}
		}
		
		//last thing, send notification if flag is set
		if($smflag){
			ym_email_add_filters();
			wp_new_user_notification($user_id,$password);
			ym_email_remove_filters();
		}
		do_action('yourmember_user_created', $user_id, $password);
		
		//tidy up after ourselves
		wp_cache_delete($user_id, 'users');
		wp_cache_delete($user_login, 'userlogins');

		// call user_register?
		//do_action('user_register', $user_id);
		
		return $user_id;
	}
	
	function expiry_time($duration, $duration_type, $expiry = FALSE) {
		global $duration_str, $ym_sys;

		if ($expiry) {
			// extension
			$time = $expiry;
		} else {
			$time = time();
		}

		$dt = getdate($time);

		// validate duration_type
		if (!array_key_exists($duration_type, $duration_str)) {
			// invalid do not extend
			return $time;
		}

		if (strtolower($duration_type) == 'm') {
			$time = mktime($ym_sys->expire_time_hour, $ym_sys->expire_time_min, $ym_sys->expire_time_min, $dt['mon'] + $duration, $dt['mday'], $dt['year']);
		} elseif (strtolower($duration_type) == 'd') {
			$time = mktime($ym_sys->expire_time_hour, $ym_sys->expire_time_min, $ym_sys->expire_time_min, $dt['mon'], $dt['mday'] + $duration, $dt['year']);
		} else {
			$time = mktime($ym_sys->expire_time_hour, $ym_sys->expire_time_min, $ym_sys->expire_time_min, $dt['mon'], $dt['mday'], $dt['year'] + $duration);
		}

		// idiot check
		if ($time < time()) {
			// fu...... extended subscription to a time that is in the past
			// was extending from last pay date but use now instead of last pay date snafu
			// or not saved/updated by gateway
			$time = $this->expiry_time($duration, $duration_type, time());
		}

		return $time;
	}

	// maintaiecne

	function updaterole($role) {
		if (user_can($this->ID, $role)) {
			// no update needed role uncahgned
			return;
		}
		$user = new WP_User($this->ID);
		$user->set_role($role);
		@ym_log_transaction(YM_USER_STATUS_UPDATE, __('Role Updated ', 'ym') . $role, $this->ID);
		return;
	}

	// perform a expure check for the currently loaded user
	// return false if expired/not logged in
	function expire_check() {
		global $ym_sys;

		if (!isset($this->ID)) {
			return;
		}

		$ID = $this->ID;

		// check for parent
		if ($this->parent_id != FALSE) {
			// has a parent account
			$parent_user = new YourMember_User($this->parent_id);
			$not_expired = $parent_user->expire_check();
			if ($not_expired) {
				// check special case expires
				if ($this->status == YM_STATUS_PARENT_CANCEL) {
					return FALSE;
				}

				// check user is is in parents child account list ie is orphaned
				if (!in_array($ID, $parent_user->child_ids)) {
					// orphaned transistion to a normal account
					// which is pending a sub payment
					$this->status = YM_STATUS_EXPIRED;//explict active set to clear child account status
					$this->parent_id = false;
					$this->save();
					@ym_log_transaction(YM_USER_STATUS_UPDATE,  __('Child Account Orphaned: Expired', 'ym'), $ID);
					return FALSE;
				}

				// if the account type is blank
				// and parent only allows a single package type
				// set the child to that package type
				if (!$this->account_type) {
					$allowed_types = count($parent_user->child_accounts_package_types);
					$allowed_packs = count($parent_user->child_accounts_packages);
					$error = FALSE;
					if ($allowed_types >= 1 && $allowed_packs >= 1) {
						// well fuck
						$error = TRUE;
					} else if ($allowed_types == 1) {
						$this->account_type = $parent_user->child_accounts_package_types[0];
						@ym_log_transaction(YM_ACCOUNT_TYPE_ASSIGNATION,  $this->account_type, $ID);
					} else if ($allowed_packs == 1) {
						ym_group_apply_package($parent_user->child_accounts_packages[0]);
						// go drop for status check.....
					} else {
						// if drop thru well deny. Account not configured
						$error = TRUE;
					}

					if ($error) {
						$this->status = YM_STATUS_PARENT_CONFIG;
						$this->save();
						return FALSE;
					}
				}

				if ($this->status != $parent_user->status) {
					$this->status = $parent_user->status;
					@ym_log_transaction(YM_USER_STATUS_UPDATE, $this->status, $ID);
				}
				// if expose expire date to child
				// update expiry
				$this->save();
				return TRUE;
			}
			// check for status update at this point the child account should be expired
			// but the parent account can be of any status (such as pending)
			if ($this->account_type != YM_STATUS_PARENT_EXPIRED) {
				$this->status = YM_STATUS_PARENT_EXPIRED;
				@ym_log_transaction(YM_USER_STATUS_UPDATE, $this->status, $ID);
				$this->save();
			}
			return FALSE;
		}

		if (ym_superuser($ID)) {
			return TRUE;
		}

		$current_status = $this->status;
		if ($current_status === false) {
			return TRUE;
		}

		if ($current_status == YM_STATUS_EXPIRED || $current_status == YM_STATUS_TRIAL_EXPIRED) {
			return FALSE;
		}

		$grace_limit_user = $ym_sys->grace_limit;
		$grace_limit_user = apply_filters('ym_user_grace_limit_adjust', $grace_limit_user, $this);

		$new = FALSE;
		$reg_date = get_userdata($ID);
		$reg_date = strtotime($reg_date->user_registered);
		if ($reg_date > (time() - (86400 * $grace_limit_user))) {
			$new = TRUE;
		}

		if ($current_status == YM_STATUS_ACTIVE || $current_status == YM_STATUS_GRACE) {
			// time
			$expire = $this->expire_date;
			if ($expire > time()) {
				// expire is in the future
				// safe/not expired
				return TRUE;
			}
			// expired

			if ($this->ym_expiry_sub_dropdown_check()) {
				return;
			}

			if ($this->trial_on) {
				$user_status = YM_STATUS_TRIAL_EXPIRED;
			} else {
				$user_status = YM_STATUS_EXPIRED;
			}

			@ym_log_transaction(YM_ACCESS_EXPIRY, time(), $ID);
			@ym_log_transaction(YM_USER_STATUS_UPDATE, $user_status, $ID);

			$data = array(
				'status'		=> $user_status,
				'status_str'	=> __('User has expired', 'ym'),
			);

			$data = apply_filters('ym_user_expire_check_into_expire', $data, $this);

			$this->update($data);
			$this->save();
			do_action('ym_user_is_expired',$ID,$data);
			
			return FALSE;
		} else if ($current_status == YM_STATUS_PENDING && $ym_sys->grace_enable && !$new) {
			// grace is only applied to pending users

			// eligable
			$last_pay_date = $this->last_pay_date;
			$limit = (time() - (86400 * $grace_limit_user));
			if ($last_pay_date > $limit) {
				// lets put them into grace
				$data = array(
					'status'		=> YM_STATUS_GRACE,
					'status_str'	=> __('User is entering Grace', 'ym'),
					'expire_date'	=> (time() + (86400 * $grace_limit_user)),
				);

				@ym_log_transaction(YM_ACCESS_EXPIRY, $data['expire_date'], $ID);
				@ym_log_transaction(YM_USER_STATUS_UPDATE, $data['status'], $ID);

				$data = apply_filters('ym_user_expire_check_into_grace', $data, $this);

				$this->update($data);
				$this->save();
				do_action('ym_user_is_in_grace',$ID,$data);

				// recheck
				return $this->expire_check();
			} else {
				// not eligable
				return FALSE;
			}
		}
		return FALSE;
	}

	function ym_expiry_sub_dropdown_check() {
		$expired_pack_id = $this->pack_id;

		global $ym_packs;
		$packs = $ym_packs->packs;

		$pack_data = ym_get_pack_by_id($expired_pack_id);
		if ($pack_data) {
			// pack found
			if (isset($pack_data['on_expire_drop_to']) && $pack_data['on_expire_drop_to']) {
				// target package to drop to
				$new_pack = ym_get_pack_by_id($pack_data['on_expire_drop_to']);
				if ($new_pack) {
					// it exists
					$ypg = new ym_payment_gateway();
					$ypg->name == 'ym_dropdown';
					$ypg->code == 'ym_dropdown';
					$ypg->nomore_eamil = TRUE;
					$ypg->do_buy_subscription($pack_data['on_expire_drop_to'], $this->ID, TRUE);
					return TRUE;
				}
			}
		}

		return FALSE;
	}
}

/**
Shortcode functions
*/
function ym_user_is($args, $content = '') {
	$result = ym_user_is_test($args);
	if ($result) {
		return do_shortcode($content);
	} else {
		return '';
	}
}
function ym_user_is_not($args, $content = '') {
	$result = ym_user_is_test($args);
	
	//Check if it's a null result and pass it back as well.
	if ($result || is_null($result)) {
		return '';
	} else {
		return do_shortcode($content);
	}
}
function ym_user_is_test($args = array()) {
	if(!is_array($args)){
		return NULL;
	}

	if (count($args) != 1) {
		// error
		return NULL;
	}

	//Ok now to match an argument
	$package_id = isset($args['package']) ? $args['package'] : '';
	$type = isset($args['package_type']) ? $args['package_type'] : '';
	$username = isset($args['username']) ? $args['username'] : '';
	$role = isset($args['role']) ? $args['role'] : '';
	$purchased = isset($args['purchased']) ? $args['purchased'] : '';

	global $ym_user;
	// cron call check
	if (empty($ym_user)) {
		// grab current user
		global $current_user;
		get_currentuserinfo();
		$ym_user = new YourMember_User($current_user->ID);
	}

	// commence checks
	if ($package_id) {
		if ($ym_user->pack_id == $package_id) {
			// woot
			return TRUE;
		}
	}

	if ($type) {
		if (strtolower($ym_user->account_type) == strtolower($type)) {
			return TRUE;
		}
	}

	get_currentuserinfo();
	global $current_user;
	if ($username) {
		if (strtolower($current_user->user_login) == strtolower($username)) {
			return TRUE;
		}
	}

	if ($role) {
		if (current_user_can($role)) {
			return TRUE;
		}
	}

	if($purchased){
		$ipns = ym_get_all_logs(10,$current_user->ID);
		if($ipns){
			foreach ($ipns as $ipn) {
				$data = unserialize($ipn->data);
				//payment was processed by another gateway
				if($data['ym_process'] != 'ym_free'){
					return TRUE;
				} 
			}
		}
		else{
			//If user hasn't an IPN he definately hasn't purchased!
			return FALSE;
		}
	}

	return FALSE;
}

function ym_user_custom_is($args, $content = '') {
	$result = ym_user_custom_is_test($args);

	if ($result) {
		return do_shortcode($content);
	} else {
		return '';
	}
}

function ym_user_custom_is_not($args, $content = '') {
	$result = ym_user_custom_is_test($args);

	if ($result || is_null($result)) {
		return '';
	} else {
		return do_shortcode($content);
	}
}

function ym_user_custom_is_test($args = array()) {

	if(!is_array($args)){
		return NULL;
	}

	if (count($args) != 1) {
		// error
		return NULL;
	}
	//Call the Users custom fields :)
	global $current_user;
	get_currentuserinfo();

	$customfields = ym_get_custom_fields($current_user->ID);
	//OK let's see what we got hiding in the array
	foreach ($args as $key => $value) {
		if(is_numeric($key))
		{
			//naughty hack to save rewriting the function.
			$field = array();
			$field['id'] = $key;
		}
		else{
			$field = ym_get_custom_field_by_name($key);
		}
		//Quick check to make sure we have a field to test against
		if(is_null($field)){
			return NULL;
		}

		//Check the custom field against the users and if doesn't match return false or carry through the loop
		if($customfields[$field['id']] != $value)
		{
			return FALSE;
		}
	}
	return TRUE;
}

function ym_gravatar_render($args){

	$email = $args['email'];
	$username = $args['username'];
	$size = $args['size'];
	if(!$size) $size = 96;
	$gravataremail = false;

	if($email || $username){
		if($username){
			$user = get_userdatabylogin('myusername');
			$gravataremail = $user->user_email;

		}
		if($email){
			$gravataremail = $email;
		}
	}
	//Else get current user
	else{
		$user = wp_get_current_user();
		$gravataremail = $user->user_email;
	}

	//No user at this point time to give up
	if(!$gravataremail) return;

	$gravatar = get_avatar($gravataremail,$size);	

	return $gravatar;
}

/**
counts
*/
function ym_users_on_pack($pack_id) {
	global $wpdb;
	
	$test = 's:7:"pack_id";s:' . strlen($pack_id) . ':"' . $pack_id . '";';
	$sql = 'SELECT DISTINCT(u.user_email) AS email
			FROM
				' . $wpdb->users . ' u
				JOIN ' . $wpdb->usermeta . ' m ON (u.id = m.user_id) 
			WHERE 
				meta_key = \'ym_user\'
				AND meta_value LIKE \'%' . $test . '%\'';
	return $wpdb->get_results($sql);
}
function ym_users_on_pack_count($pack_id) {
	global $wpdb;
	ym_users_on_pack($pack_id);
	$result = $wpdb->num_rows;
	return $result;
}
