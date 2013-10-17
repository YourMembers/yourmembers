<?php

/**
* 
* $Id$
* $Revision$
* $Date$
* $Author$
* 
*/

class mailmanager_getresponse_gateway {
	var $getresponse;
	
	function __construct() {
		$this->ybuy_id		= 0;
		$this->name			= 'GetResponse';
		$this->safe_name	= 'getresponse';
		$this->description	= 'You can use GetResponse to Send your Newsletter!';
		$this->logo			= YM_MM_GATEWAY_URL . $this->safe_name . '/gr_logo_blue_solved.png';
		$this->settings		= TRUE;
		
		$this->option_name	= 'ym_other_mm_' . $this->safe_name;
		$this->options		= get_option($this->option_name);
		
		if (mailmanager_active_gateway() == $this->safe_name) {
			if (!$this->options->apikey) {
				// no key
				if ($_GET['mm_action'] != 'gateway') {
					add_action('mailmanager_precontent', array($this, 'noapikey'));
				}
			} else {
				if (!$this->getresponse) {
					if (!$this->class_construct()) {
						ym_box_top('Error');
						echo '<p>GetResponse Error: ' . $this->getresponse->error . '</p>';
						ym_box_bottom();
					}
				}
				 
				//hooks
				if ($_GET['mm_action'] == 'welcome') {
					add_action('mailmanager_precontent', array($this, 'welcome_sorry'));
				}
				
				// broadcast
				add_action('mailmanager_broadcast_precontent', array($this, 'broadcast_pre'));
				add_action('mailmanager_broadcast_create', array($this, 'broadcast_create'), 10, 5);
				
				// create
				add_action('mailmanager_create_form', array($this, 'create_form'), 10, 1);
				add_action('mailmanager_create_create', array($this, 'create_form'), 10, 1);
				
				// view
				add_action('mailmanager_view_emails', array($this, 'view_emails'), 10, 1);
				
				// filter
				add_filter('mailmanager_adjust_recipients', array($this, 'mm_adjust_recipients'), 10, 1);
				
				// series
				add_filter('mailmanager_series_replace', array($this, 'series_replace'), 10);
				
				// EPIC OVERRIDE
				remove_all_actions('mm_scheduled_email');
				add_action('mm_scheduled_email', array($this, 'mailmanager_go_send_email'), 10, 2);
			}
		}
	}
	
	function activate() {
		get_currentuserinfo();
		global $current_user;
		
		$settings = new StdClass();
		$settings->apikey = '';
		
		$settings->from_email = $current_user->user_email;
		
		$this->options = $settings;
		$this->saveoptions();
	}
	function deactivate() {
		delete_option($this->option_name);
		$this->options = '';
	}
	function saveoptions() {
		update_option($this->option_name, $this->options);
	}
	
	function settings(&$break) {
		global $ym_formgen, $mm;
		
		$break = TRUE;
		
		// apikey
		if ($_POST['apikey']) {
			$apikey = $_POST['apikey'];
			
			if (!$this->class_construct($apikey)) {
				echo '<div id="message" class="error"><p>' . __('GetResponse Returned an Error') . ': ' . $this->getresponse->error . '</p></div>';
			} else {
				echo '<div id="message" class="updated"><p>' . __('GetReponse API Key was Accepted and has been Saved') . '</p></div>';
				$this->options->apikey = $apikey;
				$this->saveoptions();
			}
		}
		if ($_POST['from_email']) {
			$this->options->from_email = $_POST['from_email'];
			$this->options->campaign_id = $_POST['campaign_id'];
			
			$this->saveoptions();
			
			echo '<div id="message" class="updated"><p>' . __('General GetResponse Settings were Saved') . '</p></div>';
		} else {
			if (!$this->options->first_run_complete) {
				// call sync
				$this->options->first_run_complete = TRUE;
				$this->saveoptions();
				$_POST['manual_sync'] = 1;
			}
		}
		
		if ($this->options->apikey) {
			$allcampaigns = array();
			$campaigns = $this->getresponse->get_campaigns();
		}
		
		if ($_POST['manual_sync']) {
			// do sync
			ym_box_top('Sync');
			$this->sync_with_gateway();
			ym_box_bottom();
		}
		
		// settings page
		if ($this->options->apikey) {
			foreach ($campaigns->result as $id => $data) {
				$allcampaigns[$id] = $data->name;
			}
			
			if ($this->options->first_run_complete) {
				ym_box_top(__('Additional Options'));
			} else {
				ym_box_top(__('Additional Options'), TRUE, TRUE);
			}
			echo '<p>' . __('Details to use when sending a Broadcast or Series Email') . '</p>';

			echo '<form action="" method="post">';
			echo '<table class="form-table">';

			echo $ym_formgen->render_form_table_text_row(__('From Address'), 'from_email', $this->options->from_email, __('Address Emails appear to come from'));
//			echo $ym_formgen->render_form_table_text_row(__('From Name'), 'from_name', $this->options->from_name, __('The Name emails appear to be from'));
			
			echo $ym_formgen->render_combo_from_array_row(__('Campaign'), 'campaign_id', $allcampaigns, $this->options->campaign_id, __('Select a Campaign to associate with'));

			echo '</table>';

			echo '<p style="text-align: right;"><input type="submit" value="' . __('Save Settings') . '" /></p>';
			echo '</form>';

			ym_box_bottom();
			
			if (!$this->options->first_run_complete) {
				ym_box_top('First Run');
				echo '<p>GetResponse uses Campaigns as Lists</p><p>MailManager will perform the initial sync when you hit ok</p>';

				echo '<form action="" method="post" style="text-align: center;">';
				echo '<input type="hidden" name="do_first_run" value="1" />';
				echo '<input type="submit" value="Ok" />';
				echo '</form>';
				ym_box_bottom();
			} else {
				echo '<form action="" method="post">';
				ym_box_top(__('Manual Sync'));//, TRUE, TRUE);
				
				echo '<input type="hidden" name="manual_sync" value="true" />';
				echo '<p style="text-align: right;"><input type="submit" value="' . __('Run a manual List Sync') . '" /></p>';
				
				ym_box_bottom();
				echo '</form>';
			}
			
			ym_box_top(__('GetResponse Settings API Key'), TRUE, TRUE);
		} else {
			ym_box_top(__('GetResponse Settings API Key'));
		}
		
		echo '<form action="" method="post">';
		echo '<table class="form-table">';
		echo $ym_formgen->render_form_table_text_row(__('GetResponse API Key'), 'apikey', $this->options->apikey, __('Your GetResponse API Key'));
		echo '</table>';
		echo '<p style="text-align: right;"><input type="submit" value="' . __('Save API Key') . '" /></p>';
		echo '</form>';
		
		ym_box_bottom();
	}
	
	// Hooks
	function noapikey() {
		global $mm;
		ym_box_top(__('GetResponse: No ApiKey'));
		echo '<p>' . __('You need to Supply your GetResponse API Key') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">' . __('GetResponse Settings') . '</a></p>';
		ym_box_bottom();
	}
	function welcome_sorry() {
		ym_box_top(__('Welcome Messages'));
		echo '<p>' . __('WordPress Welcome emails if enabled here are still sent via WP Mail') . '</p>';
		ym_box_bottom();
	}
	
	/*************************************************************************
	* BROADCAST
	**************************************************************************/
	function broadcast_pre() {
		echo '<p>' . __('A Get Response broadcast is called a Newsletter', 'ym') . '</p>';
	}
	function broadcast_create($email_id, $email_subject, $email_content, $recipient_list, $time) {
		define('STOP_BROADCAST', TRUE);
		
		// burn baby burn
		//send_newsletter($campaign_id, $from_field = FALSE, $subject, $contents, $flags, $contacts = FALSE, $get_contacts = FALSE, $suppressions = FALSE, $get_suppressions = FALSE) {
		$flags = array(
			'clicktrack',
			'openrate'
		);
		
		$test = explode('_', $recipient_list);
		if ($test[0] == 'wordpress') {
			// everyone
			$key = 'everyone';
		} else if ($test[1] == 'ac') {
			$key = 'segment_account_type';
		} else if ($test[1] == 'pack') {
			$key = 'segment_pack_id';
		} else if ($test[1] == 'all') {
			$key = 'segment_all';
		} else {
			echo '<div id="message" class="error"><p>An error occured with determining who to send to</p></div>';
			return;
		}
		
		if ($key != 'everyone') {
			$paras = array(
				'customs' => array(array(
					'name'		=> $key,
					'content'	=> array('EQUALS' => $recipient_list)
				))
			);
		} else {
			$paras = array(
				'campaigns' => array($this->options->campaign_id)
			);
		}
		
		$from_email_id = $this->from_id();
		if (!$from_email_id) {
			echo '<div id="message" class="error"><p>An error occured with determining a Send From ID</p></div>';
			return;
		}
		
		$contents = array(
			'plain'	=> strip_tags($email_content),
			'html'	=> $email_content
		);
		
		if ($time < time()) {
			// now
			$r = $this->getresponse->send_newsletter($this->options->campaign_id, $from_email_id, $email_subject, $contents, $flags, FALSE, $paras, FALSE, FALSE);

			echo '<div id="message" class="';
			if ($r) {
				echo 'updated"><p>Message was sent successfully</p></div>';
			} else {
				echo 'error"><p>An Error Occured</p></div>';
			}
		} else {
			$email_json = array(
				'campaign_id' => $this->options->campaign_id,
				'from_email_id' => $from_email_id,
				'email_subject' => $email_subject,
				'contents' => $contents,
				'flags' => $flags,
				'paras' => $paras
			);
			$email_json = json_encode($email_json);

			// scheduler
			global $wpdb;
			
			if (!$email_id) {
				$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_email(name, subject, body) VALUES (\'broadcast_' . $email_subject . '\', \'' . $email_subject . '\', \'' . $email_json . '\')';
				$wpdb->query($sql);
				$email_id = $wpdb->insert_id;
			} else {
				// build json packet
				$sql = 'UPDATE ' . $wpdb->prefix . 'mm_email SET subject = \'json_encode_for_schedule\', body = \'' . $email_json . '\' WHERE id = ' . $email_id;
				$wpdb->query($sql);
			}
			$args = array($email_id, $recipient_list);

			wp_schedule_single_event($time, 'mm_scheduled_email', $args);

			ym_box_top('Broadcast');
			echo 'Scheduled for ' . date('r', $time) . ' it is currently ' . date('r', time());
			ym_box_bottom();
		}
		
		return;
	}
	
	/*************************************************************************
	* BROADCAST SCHEDULED
	**************************************************************************/
	function mailmanager_go_send_email($email_id, $recipient_list) {
		global $wpdb;
		
		$email = 'SELECT * FROM ' . $wpdb->prefix . 'mm_email WHERE id = ' . $email_id;
		$email = $wpdb->get_results($email);
		foreach ($email as $e) {
			$data = $e->body;
			$data = json_decode($data);
			
			// go
			$this->getresponse->send_newsletter($data->campaign_id, $data->from_email_id, $data->email_subject, $data->contents, $data->flags, FALSE, $data->paras, FALSE, FALSE);

			mailmanager_log_email_send($user_id, '100' . $email_id);
			
			$query = 'DELETE FROM ' . $wpdb->prefix . 'mm_email WHERE id = ' . $email_id;
			$wpdb->query($query);
		}
	}
	/*************************************************************************
	* Create
	**************************************************************************/
	function create_form($has_id = FALSE) {
		define('STOP_CREATE', TRUE);
		define('MAILMANAGER_FORM_REPLACED', TRUE);
		echo '<p>Drafts are disabled for GetResponse</p>';
	}
	
	function view_emails() {
		define('STOP_VIEW_EMAILS', TRUE);
		echo '<p>Nothing to see here as Drafts are disabled</p>';
	}

	/*************************************************************************
	* Series
	**************************************************************************/
	function series_replace() {
		define('MM_SERIES_REPLACED', TRUE);
		
		echo '<p>Auto Responders are disabled for GetResponse</p>';
	}
	
	// End Hooks
	
	// handy functions for this gateway
	private function class_construct($key = FALSE) {
		require_once(YM_MM_GATEWAY_DIR . $this->safe_name . '/getresponse_class.php');
		if (!$key) {
			$key = $this->options->apikey;
		}
		$this->getresponse = new GetResponse($key);
		return $this->getresponse->ping();
	}
	// end handy
	
	// cron
	// sync sync reboot
	// sync sync
	function sync_with_gateway() {
		global $wpdb;
		
		include(YM_MM_INCLUDES_DIR . 'countries.inc.php');
		$language_code = 'en';
		
		$fp = fopen('/Users/barrycarlyon/WebWork/CodingFutures/yourmembers/wordpress_dev/wordpress/wp-content/plugins/ym_mailmanager/mailgateway/getresponse/log.log', 'w');
		fwrite($fp, 'a');
		fclose($fp);
		
		if ($_GET['mm_action']) {
			ob_end_flush();
			// echo as we go
			ob_implicit_flush(true);
			
			echo '<pre>';
			echo 'Syncing with Gateway' . "\n";
		}

		// from check
		if ($_GET['mm_action']) {
			echo 'From Check ';
		}
		$from_email_id = $this->from_id();
		if (!$from_email_id) {
			if ($_GET['mm_action']) {
				echo 'No From Email ';
			}
			return;
		}
		
		if ($_GET['mm_action']) {
			echo 'Confirmations';
		}
		
		$confirm_subjects = $this->getresponse->get_confirmation_subjects();
		$confirm_subject_id = FALSE;
		$confirm_subjects = get_object_vars($confirm_subjects->result);
		foreach ($confirm_subjects as $id => $details) {
			if ($details->language_code == $language_code) {
				$confirm_subject_id = $id;
			}
		}
		if (!$confirm_subject_id) {
			if ($_GET['mm_action']) {
				echo 'AN ERROR ORRCURED: Subject';
			}
			return;
		}
		
		$confirm_bodies = $this->getresponse->get_confirmation_bodies();
		$confirm_bodies_id = FALSE;

		$confirm_bodies = get_object_vars($confirm_bodies->result);
		foreach ($confirm_bodies as $id => $details) {
			if ($details->language_code == $language_code) {
				$confirm_bodies_id = $id;
			}
		}
		if (!$confirm_bodies_id) {
			if ($_GET['mm_action']) {
				echo 'AN ERROR ORRCURED: Body';
			}
			return;
		}
		echo ' values ' . $from_email_id . '-' . $from_email_id . '-' . $confirm_subject_id . '-' . $confirm_bodies_id;

		if ($_GET['mm_action']) {
			echo "\n" . 'Syncing All Users to list. Insert/update' . "\n";
		}
		
		$campaignid = $this->options->campaign_id;
		
		$local_users = array();
		foreach ($wpdb->get_results(mailmanager_get_sql('wordpress_users')) AS $row) {
			$local_users[] = $row->email;
		}
		
		if ($_GET['mm_action']) {
			echo 'There are locally: ' . count($local_users) . "\n";
		}
		
		if ($_GET['mm_action']) {
			echo 'There are ' . count($local_users) . ' Users to sync' . "\n";
		}
		
		foreach ($local_users as $user_email) {
			$data = get_user_by_email($user_email);
			
			if ($data->first_name || $data->last_name) {
				$name = $data->first_name . ' ' . $data->last_name;
			} else {
				$name = $data->display_name;
				$data->first_name = '';
				$data->last_name = $name;
			}
			$person_name = $name;
			
			$merge = array(
				'LNAME'	=> $data->last_name
			);
			
			if ($data->first_name) {
				$merge['FNAME'] = $data->first_name;
			}
			
			$fields = ym_get_custom_field_array($data->ID);
			foreach ((array) $fields as $field => $value) {
				$merge[strtoupper(str_replace(' ', '_', $field))] = $value;
			}
			if ($merge['COUNTRY']) {
				$merge['COUNTRY'] = $countries[$merge['COUNTRY']];
			}
			// list
			$id = $data->ID;
			$data = get_user_meta($id, 'ym_user');
			
			$account_type = $data->account_type;
			$pack_id = $data->pack_id;
			$status = get_user_meta($id, 'ym_status', TRUE);

			// new data
			$segment_account_type = 'ym_ac_' . strtolower($account_type);
			$segment_pack_id = 'ym_pack_' . $pack_id;
			$segment_all = 'ym_all_' . strtolower($status);
			
			$merge['segment_account_type'] = $segment_account_type;
			$merge['segment_pack_id'] = $segment_pack_id;
			$merge['segment_all'] = $segment_all;
			
			if ($_GET['mm_action']) {
				echo 'Syncing ' . $user_email . ' ';
			}
			// build customs
			$customs = array();
			foreach ($merge as $name => $value) {
				if ($value) {
					$customs[] = array(
						'name'		=> $name,
						'content'	=> $value
					);
				} else {
					$customs[] = array(
						'name'		=> $name,
						'content'	=> 'empty'
					);
				}
			}
			
			// standard does insert or update
			if ($this->getresponse->add_contact($campaignid, 'standard', $person_name, $user_email, FALSE, FALSE, $customs)) {
				if ($_GET['mm_action']) {
					echo 'OK queued/pending';
				}
			} else {
				if ($_GET['mm_action']) {
					echo 'Failed';
				}
			}
			if ($_GET['mm_action']) {
				echo "\n";
			}
		}
		
		if ($_GET['mm_action']) {
			echo 'All Done' . "\n";
			echo '</pre>';
		}
	}
	
	function mm_adjust_recipients($r) {
		unset($r['wordpress_commenters']);
		unset($r['wordpress_guest_commenters']);
		unset($r['wordpress_registered_commenters']);
		return $r;
	}
	
	private function from_id() {
		$fromemails = $this->getresponse->get_account_from_fields();
		if (!is_array($fromemails->result)) {
			$fromemails->result = array($fromemails->result);
		}
		$from_email_id = FALSE;
		foreach ($fromemails->result as $fromemail) {
			$fromemail = get_object_vars($fromemail);
			foreach ($fromemail as $id => $details) {
				if ($details->email == $this->options->from_email) {
					$from_email_id = $id;
				}
			}
		}
		if (!$from_email_id) {
			// create
			$result = $this->getresponse->add_account_from_field(get_bloginfo(), $this->options->from_email);
			$from_email_id = $result->FROM_FIELD_ID;
		}
		
		return $from_email_id;
	}
}
