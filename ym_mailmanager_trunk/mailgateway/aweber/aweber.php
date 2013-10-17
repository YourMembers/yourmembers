<?php

/**
* 
* $Id: aweber.php 162 2011-03-21 11:26:39Z bcarlyon $
* $Revision: 162 $
* $Date: 2011-03-21 11:26:39 +0000 (Mon, 21 Mar 2011) $
* $Author: bcarlyon $
* 
*/

class mailmanager_aweber_gateway {
	var $aweber;
	
	function __construct() {
		$this->ybuy_id		= 44;
		$this->name			= 'Aweber';
		$this->safe_name	= 'aweber';
		$this->description	= 'You can use Aweber to Syncronise your Subscribers!';
		$this->logo			= YM_MM_GATEWAY_URL . $this->safe_name . '/aweber_logo.gif';
		$this->settings		= TRUE;
		
		$this->option_name	= 'ym_other_mm_aweber';
		$this->options		= get_option($this->option_name);
		$this->construct	= '';
		
		$this->aweber_id	= 'c2ade626';
		
		if (mailmanager_active_gateway() == $this->safe_name) {
			$this->class_construct($this->options->oauth->consumer_key, $this->options->oauth->consumer_secret);
			$this->construct->access_token = $this->options->oauth->access_token;
			$this->construct->access_token_secret = $this->options->oauth->access_token_secret;
			
			if (!$this->options->oauth->connected) {
				// no key
				if ($_GET['mm_action'] != 'gateway') {
					add_action('mailmanager_precontent', array($this, 'nopair'));
				}
			} else {
				if (!$this->options->account) {
					add_action('mailmanager_precontent', array($this, 'noaccount'));
				}
				$this->associations = get_option($this->option_name . '_associations');
				// hooks
				add_filter('mailmanager_adjust_recipients', array($this, 'filter_lists_add_name'), 10, 1);
				add_action('mailmanager_broadcast_precontent', array($this, 'broadcast_precontent'));
				add_action('mailmanager_series_precontent', array($this, 'series_precontent'));
				
				add_action('mailmanager_broadcast_create', array($this, 'broadcast_create'), 10, 5);
				add_action('mailmanager_series_create', array($this, 'series_create'), 10, 4);
				
				add_action('mailmanager_cron_check', array($this, 'sync_with_aweber'));
			}
		}
	}
	
	function activate() {
		get_currentuserinfo();
		global $current_user;
		
		$settings = new StdClass();
		
		$settings->oauth->consumer_key = '';
		$settings->oauth->consumer_secret = '';
		$settings->oauth->access_token = '';
		$settings->oauth->access_token_secret = '';
		$settings->oauth->connected = FALSE;
		
		$settings->account = '';
		
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
	function saveassociations() {
		update_option($this->option_name . '_associations', $this->associations);
	}
	
	function settings(&$break) {
		global $ym_formgen, $mm;

 		$break = TRUE;

		if ($_POST['distro_code']) {
			// going for connect
			//This code is actually just an application key, application secret, request token, token secret, and oauth_verifier, delimited by pipes (|).
			list($key, $secret, $request_token, $token_secret, $oauth_verifier) = explode('|', $_POST['distro_code']);
			// rebuild with keys
			$this->class_construct($key, $secret);
			// pass in secret
			$this->construct->token_secret = $token_secret;
			// exchange request token for access tokens
			$this->construct->access($request_token, $oauth_verifier);

			// store and set to live
			$this->options->oauth->consumer_key = $key;
			$this->options->oauth->consumer_secret = $secret;
			$this->options->oauth->access_token = $this->construct->access_token;
			$this->options->oauth->access_token_secret = $this->construct->access_token_secret;
			$this->options->oauth->connected = TRUE;
			$this->saveoptions();
			
			// test connection
			ym_box_top('Aweber Account Selection');
			echo '<p>Testing connection</p>';
			$data = $this->construct->accounts();
			if ($this->construct->disconnected) {
				// destory
				$this->activate();
				echo '<p>Connection Failed</p>';
			} else {
				echo '<p>Connection Complete</p>';
				if (sizeof($data->entries) == 1) {
					// only 1 account auto associate
					$this->options->account = $data->entries[0]->id;
					$this->saveoptions();
					echo '<p>Associating with the account ' . $data->entries[0]->id . '</p>';
					ym_box_bottom();
					unset($_POST);
				}
			}
		}
		
		if ($this->options->oauth->connected) {
			
			if ($_POST['account']) {
				$this->options->account = $_POST['account'];
				$this->saveoptions();
				ym_box_top('Settings Updated');
				echo '<p>Settings were updated</p>';
				ym_box_bottom();
			}
			
			if (!$this->options->account) {
				if (!$data) {
					ym_box_top('Aweber Account Selection');
					$data = $this->construct->accounts();
					$this->construct_check();
				}
				
				echo '<form action="" method="post">';
				echo '<fieldset><legend>Please Select an Aweber Account to connect to</legend>';
				echo '<table class="form-table">';
				
				$entries = array();
				if (!$this->options->account) {
					$entries[] = 'Select';
				}
				foreach ($data->entries as $entry) {
					$entries[] = $entry->id;
				}

				$ym_formgen->render_combo_from_array_row('Account To Use', 'account', $entries, $this->options->account);
				echo '</table>';
				echo '<p style="text-align: right;"><input type="submit" value="' . __('Save Client') . '"</p>';
				echo '</fieldset></form>';
				
				ym_box_bottom();
			} else {
				// normal form
				// remove the filter
				remove_filter('mailmanager_adjust_recipients', array($this, 'filter_lists_add_name'));
				
				if ($_POST) {
					foreach (mailmanager_get_recipients() AS $list => $text) {
						if ($value = ym_post($list)) {
							$this->associations->$list = $value;
						} else if ($this->associations->$list) {
							unset($this->associations->$list);
						}
					}
					$this->saveassociations();
					ym_box_top('Aweber');
					echo '<p>' . __('Associations were updated') . '</p>';
					ym_box_bottom();
					
					ym_box_top('Syncing with Aweber');
					echo '<pre>';
					$this->sync_with_gateway();
					echo '</pre>';
					ym_box_bottom();
				}
				
				echo '<form action="" method="post">';
				
				ym_box_top('List Associations');
				
				$lists =  $this->get_lists(TRUE);
				
				echo '<table class="form-table">';
				
				foreach (mailmanager_get_recipients() AS $list => $text) {
					echo $ym_formgen->render_combo_from_array_row($text, $list, $lists, $this->associations->$list, 'Select a ' . $this->name . ' List to associate with');
				}
				
				echo '</table>';
				echo '<p style="text-align: right;"><input type="submit" value="' . __('Save Associations') . '" /></p>';
				
				ym_box_bottom();
				echo '</form>';
			}
		} else {
			ym_box_top('Aweber Connect: Instructions');
			echo '<table style="width: 100%;">
			<tr>
			<td style="width: 33%; text-align: center;">Step 1) Login to Aweber</td>
			<td style="width: 33%; text-align: center;">Step 2) Copy the Authorization Code Supplied</td>
			<td style="width: 33%; text-align: center;">Step 3) Paste into the Box Below</td>
			</tr>
			</table>';
			ym_box_bottom();
			ym_box_top('Aweber Connect');
			echo '<iframe src="' . $this->construct->distro_url($this->aweber_id) . '" style="width: 800px; height: 560px;" id="aweberiframe"></iframe>';
			ym_box_bottom();
			ym_box_top('Aweber Authorization Code');
			echo '
			<form action="" method="post">
				<fieldset>
					<legend>Provide your Authorization Code here, the Authorization Code is specific to Your Aweber Account</legend>
					<input type="text" name="distro_code" id="distro_code" style="width: 100%;" />
					<br />
					<input type="submit" />
				</fieldset>
			</form>';
			ym_box_bottom();
		}
	}
	
	function nopair() {
		global $mm;
		
		ym_box_top('Aweber: Not Connected');
		echo '<p>' . __('You need to Connect Your Members MailManager to Aweber') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">Aweber Settings</a></p>';
		ym_box_bottom();
	}
	function noaccount() {
		global $mm;
		
		ym_box_top('Aweber: No Account');
		echo '<p>' . __('You need to Select a Aweber Account for Your Members MailManager to use') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">Aweber Settings</a></p>';
		ym_box_bottom();
	}
	
	private function class_construct($key = '', $secret = '') {
		require_once(YM_MM_GATEWAY_DIR . $this->safe_name . '/aweber_class.php');
		$this->construct = new aweber_oauth($key, $secret);
		if ($this->options->account) {
			$this->construct->account_id = $this->options->account;
		}
	}
	private function construct_check() {
		if ($this->construct->disconnected) {
			// we have lost the aweber connection
			$this->activate();
			return TRUE;
		}
		return FALSE;
	}
	
	private function get_lists($is_option = FALSE) {
		$lists = $this->construct->lists($this->options->account);
		$this->construct_check();
		if ($lists->total_size == 0) {
			ym_box_top($this->name . ' Error');
			echo '<p>' . __('You have no Lists on this Account on ' . $this->name . ' to associate with') . '</p>';
			ym_box_bottom();
			return;
		}
		$list_data = array();
		if ($is_option) {
			$list_data[] = __('--Select--');
		}
		foreach ($lists->entries as $list) {
			$list_data[$list->id] = $list->name;
		}
		
		return $list_data;
	}
	function filter_lists_add_name($lists) {
		foreach ($lists as $list => $text) {
			if ($this->associations->$list) {
				$text .= ' (' . $this->name . ')';
				$lists[$list] = $text;
			}
		}
		return $lists;
	}
	function filter_lists_remove($lists) {
		foreach ($lists as $list => $text) {
			if ($this->associations->$list) {
				unset($lists[$list]);
			}
		}
		return $lists;
	}
	
	// messages
	function broadcast_precontent() {
		ym_box_top('Message');
		echo '<p>' . __('The Aweber Associated Lists can only be sent to via Aweber') . '</p>';
		ym_box_bottom();

		add_filter('mailmanager_adjust_recipients', array($this, 'filter_lists_remove'), 10, 1);
	}
	function series_precontent() {
		ym_box_top('Message');
		echo '<p>' . __('Series are being kept synced with Aweber.') . '</p>';
		echo '<p>' . __('The Aweber Associated Lists can only be sent to via Aweber, a series entry exists for Sync Reasons') . '</p>';
		ym_box_bottom();
		
		add_filter('mailmanager_adjust_recipients', array($this, 'filter_lists_remove'), 10, 1);
	}
	
	function broadcast_create($email_id, $email_subject, $email_content, $recipient_list, $time) {
		global $wpdb;
		$aweber_list_id = $this->associations->$recipient_list ? $this->associations->$recipient_list : FALSE;
		
		if (!$aweber_list_id) {
			return;
		}
		define('STOP_BROADCAST', TRUE);
		return;
	}
	function series_create(&$name, &$recipient_list, &$show, &$bypass) {
		// interrupting the normal flow
		// create a new broadcast
		echo 'NOT IMPLEMENTED';
		$bypass = TRUE;
		$name = '';
	}
	
	// sync sync
	function sync_with_gateway() {
		global $wpdb;
		if ($_GET['mm_action']) {
			echo '<pre>';
			echo 'sync' . "\n";
		}
		//print_r($this->associations);
		// this function will iterate thru all the local lists
		// it the list is associated
		// if will go get all the subscribed/unsubscribed users from aweber
		// then update the local tables
		// if it hits a user email that doth not exists locally it will? (skip it)

		foreach (mailmanager_get_recipients() as $list => $text) {
			if ($_GET['mm_action']) {
				echo $list;
			}
			if ($aweberid = $this->associations->$list) {
				if ($_GET['mm_action']) {
					echo ' has ';
				}
				$sql = 'SELECT id FROM ' . $wpdb->prefix . 'mm_series WHERE description = \'' . $aweberid . '\'';
				$series_id = $wpdb->get_var($sql);
				if (!$series_id) {
					$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_series(name, description, recipient_list) VALUES (\'Aweber: ' . $text . '\', \'' . $aweberid . '\', \'' . $list . '\')';
					$wpdb->query($sql);
					$series_id = $wpdb->insert_id;
				}

				// its associated
				$users_sub = array();
				$users_unsub = array();
				// go aweber
				// will contain all subed and unsubbed users
				$list_members = $this->construct->subscribers($aweberid);
				if ($this->construct_check()) {
					// has d/c
					if ($_GET['mm_action']) {
						echo 'DISCONNECTED';
					}
					return;
				}
				$subscribed = $unsubscribed = array();
				// sort
				foreach ($list_members->entries as $entry) {
					$status = $entry->status;
					
					switch ($status) {
						case 'unsubscribed':
							$unsubscribed[] = $entry;
							break;
						case 'subscribed':
						case 'unconfirmed':
						default:
							$subscribed[] = $entry;
							break;
					}
				}
				
				// le run
				foreach ($subscribed as $user) {
					$users_sub[] = $user->email;
					$user_id = get_user_by_email($user->email);
					$user_id = $user_id->ID;
					$timestamp = strtotime($user->subscribed_at);

					// return timestamp....
					mailmanager_get_user_in_series($user_id, $series_id, $list, $timestamp);
				}
				// umsubed
				// clean all in order to handle resubscribe.....
				$sql = 'DELETE FROM ' . $wpdb->prefix . 'mm_list_unsubscribe WHERE list_name = \'' . $list . '\'';
				$wpdb->query($sql);
				foreach ($unsubscribed as $user) {
					$users_unsub[] = $user->email;

					$user_id = get_user_by_email($user->email);
					$user_id = $user_id->ID;
					$timestamp = strtotime($user->unsubscribed_at);

					// log unsub
					mailmanager_unsubscribe_user($list, $user_id);
				}

				$users = array();
				foreach ($wpdb->get_results(mailmanager_get_sql($list)) AS $row) {
					$users[] = $row->email;
				}

				$users_to_add = array_diff($users, $users_sub, $users_unsub);
				if ($_GET['mm_action']) {
					echo ' Live: ' . sizeof($users_sub) . ' Local: ' . sizeof($users) . ' To Add: ' . sizeof($users_to_add);
					//print_r($mc_users_sub);
					//print_r($mc_users_unsub);
					//print_r($users);
					//print_r($users_to_add);
				}

				// need list name
				$list_data = $this->construct->alist($aweberid);
				$awebername = $list_data->name;
				// get the web form
				$forms = $this->construct->web_forms($aweberid);
				foreach ($forms->entries as $entry) {
					if ($entry->is_active) {
						$form_id = $entry->id;
					}
				}

				foreach ($users_to_add as $user) {
					// get fname lname
					$data = get_user_by_email($user);
					$theirname = $data->first_name . ' ' . $data->last_name;
					if ($theirname == ' ') {
						list($theirname, $null) = explode('@', $user);
					}
					// assemble and add custom fields
					$fields = ym_get_custom_field_array($data->ID);
/*
					foreach ($fields as $field => $value) {
						$custom[strtoupper($field)] = $value;
					}
*/
					if ($_GET['mm_action']) {
						echo "\n" . ' Adding ' . $user;
					}					
					
					$aweber_data = array(
						'listname'			=> $awebername,
						'redirect'			=> get_bloginfo('siteurl'),
						'meta_adtracking'	=> 'MailManager',
//						'meta_message'		=> '0',
						'meta_required'		=> 'name,email',
						'meta_forward_vars'	=> '0',
						'name'				=> $theirname,
						'email'				=> $user,
						'submit'			=> 'Subscribe',
					);
					
					// prepare to hack the form
					$this->construct->add_subscriber($aweberid, $aweber_data);
				}
				/*
				update
				foreach ($mc_users_sub as $user) {
					// get fname lname
					$data = get_user_by_email($user);
					$theirname = array(
						'FNAME'	=> $data->first_name,
						'LNAME'	=> $data->last_name
					);
					// assemble and add custom fields
					$fields = ym_get_custom_field_array($data->ID);
					foreach ($fields as $field => $value) {
						$theirname[strtoupper($field)] = $value;
					}
					if ($_GET['mm_action']) {
						echo "\n" . ' Updating ' . $user;
					}
					$this->mailchimp->listUpdateMember($mcid, $user, $theirname);
				}
				*/
				if ($_GET['mm_action']) { echo "\n"; }
			}
			if ($_GET['mm_action']) { echo "\n"; }
		}
		if ($_GET['mm_action']) {
			echo '</pre>';
		}
	}
}
