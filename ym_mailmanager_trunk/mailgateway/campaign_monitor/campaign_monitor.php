<?php

/**
* 
* $Id: campaign_monitor.php 165 2011-03-22 16:25:12Z bcarlyon $
* $Revision: 165 $
* $Date: 2011-03-22 16:25:12 +0000 (Tue, 22 Mar 2011) $
* $Author: bcarlyon $
* 
*/

class mailmanager_campaign_monitor_gateway {
	var $campaign_monitor;
	
	function __construct() {
		$this->ybuy_id		= 43;
		$this->name			= 'Campaign Monitor';
		$this->safe_name	= 'campaign_monitor';
		$this->description	= 'Send beautiful email campaigns, track the results';
		$this->logo			= YM_MM_GATEWAY_URL . $this->safe_name . '/campaignMonitor.png';
		$this->settings		= TRUE;
		
		$this->option_name	= 'ym_other_mm_campaign_monitor';
		$this->options		= get_option($this->option_name);
		$this->construct	= '';
		
		if (mailmanager_active_gateway() == $this->safe_name) {
			if (!$this->options->apikey) {
				add_action('mailmanager_precontent', array($this, 'noapikey'));
			} else if (!$this->options->client) {
				if (!$this->construct) {
					if (!$this->class_construct()) {
						ym_box_top('Error');
						echo '<p>Error: ' . $this->construct->return . '</p>';
						ym_box_bottom();
					}
				}
				add_action('mailmanager_precontent', array($this, 'noclient'));
			} else {
				// success
				if (!$this->construct) {
					if (!$this->class_construct()) {
						ym_box_top('Error');
						echo '<p>CM Error: ' . $this->construct->return . '</p>';
						ym_box_bottom();
					}
				}
				$this->associations = get_option($this->option_name . '_associations');
				// hooks
				add_filter('mailmanager_adjust_recipients', array($this, 'filter_lists_add_name'), 10, 1);
				add_action('mailmanager_broadcast_precontent', array($this, 'broadcast_precontent'));
				add_action('mailmanager_series_precontent', array($this, 'series_precontent'));
				
				add_action('mailmanager_broadcast_create', array($this, 'broadcast_create'), 10, 5);
				add_action('mailmanager_series_create', array($this, 'series_create'), 10, 4);
				
				add_action('mailmanager_cron_check', array($this, 'sync_with_gateway'));
			}
		}
	}
	
	function activate() {
		
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
		
		if ($_POST) {
			if ($apikey = ym_post('apikey')) {
				// verify
				ym_box_top($this->name . ' Settings API Key: Result');
				
				$this->class_construct($apikey);
				echo '<p>';
				if ($this->construct->error != 'ok') {
					echo 'Error: ' . $this->construct->error;
				} else {
					$this->options->apikey = $apikey;
					$this->options->client = '';
					$this->saveoptions();
					echo '</p><p>ApiKey was saved</p>';
					ym_box_bottom();
					echo '<meta http-equiv="refresh" content="5" />';
					return;
				}
				echo '</p>';
				ym_box_bottom();
			}
			if ($client = ym_post('client')) {
				// verify
				ym_box_top($this->name . ' Settings Client: Result');
				
				$this->class_construct($apikey, $client);
				echo '<p>';
				if ($this->construct->error != 'ok') {
					echo 'Error: ' . $this->construct->error;
				} else {
					$this->options->client = $client;
					$this->saveoptions();
					echo '</p><p>Client was saved</p>';
					ym_box_bottom();
					echo '<meta http-equiv="refresh" content="5" />';
					return;
				}
				echo '</p>';
				ym_box_bottom();
			}
		}
		
		// the settings page
		if ($this->options->apikey) {
			ym_box_top($this->name . ' Settings API Key', TRUE, TRUE);
		} else {
			ym_box_top($this->name . ' Settings API Key');
		}
		
		echo '<form action="" method="post">';
		echo '<table class="form-table">';
		echo $ym_formgen->render_form_table_text_row($this->name . ' API Key', 'apikey', $this->options->apikey, 'Your ' . $this->name . ' API Key');
		echo '</table>';
		echo '<p style="text-align: right;"><input type="submit" value="' . __('Save API Key') . '"</p>';
		echo '</form>';
		
		ym_box_bottom();
		
		if ($this->options->apikey) {
			if ($this->options->client) {
				ym_box_top($this->name . ' Settings Client', TRUE, TRUE);
			} else {
				ym_box_top($this->name . ' Settings Client');
			}
			
			$clients = $this->get_clients(TRUE);

			echo '<form action="" method="post">';
			echo '<table class="form-table">';
			
			$ym_formgen->render_combo_from_array_row('Client To Send Via', 'client', $clients, $this->options->client);
			echo '</table>';
			echo '<p style="text-align: right;"><input type="submit" value="' . __('Save Client') . '"</p>';
			echo '</form>';

			ym_box_bottom();
			
			if ($this->options->client) {
				// rest of form
				
				if ($_POST) {
					foreach (mailmanager_get_recipients() AS $list => $text) {
						if ($value = ym_post($list)) {
							$this->associations->$list = $value;
						} else if ($this->associations->$list) {
							unset($this->associations->$list);
						}
					}
					$this->saveassociations();
					ym_box_top($this->name);
					echo '<p>' . __('Associations were updated') . '</p>';
					ym_box_bottom();

					ym_box_top('Syncing with ' . $this->name);
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
		}
	}
	
	// Hooks
	function noapikey() {
		if ($_POST['apikey']) {
			return;
		}
		global $mm;
		ym_box_top($this->name . ': No ApiKey');
		echo '<p>' . __('You need to Supply your ' . $this->name . ' API Key') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">' . $this->name . ' Settings</a></p>';
		ym_box_bottom();
	}
	function noclient() {
		if ($_POST['client']) {
			return;
		}
		global $mm;
		ym_box_top($this->name . ': No Client');
		echo '<p>' . __('You need to select a ' . $this->name . ' Client') . '</p>';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">' . $this->name . ' Settings</a></p>';
		ym_box_bottom();
	}
	// End Hooks
	
	private function class_construct($key = FALSE, $client = FALSE) {
		require_once(YM_MM_GATEWAY_DIR . $this->safe_name .'/'. $this->safe_name . '_class.php');
		if (!$key) {
			$key = $this->options->apikey;
		}
		if (!$client) {
			$client = $this->options->client;
		}
		$this->construct = new campaign_monitor($key, $client);
		if ($client) {
			$this->construct->client_details($client);
		} else {
			$this->construct->clients();
		}
		if ($this->construct->error != 'ok') {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	private function get_clients($is_option = FALSE) {
		$clients = $this->construct->clients();
		if (!count($clients)) {
			ym_box_top($this->name . ' Error');
			echo '<p>' . __('You have no Clients on ' . $this->name . 'r to associate with') . '</p>';
			ym_box_bottom();
			return;
		}
		$client_data = array();
		if ($is_option) {
			$client_data[] = __('--Select--');
		}
		foreach ($clients as $client) {
			$client_data[$client->ClientID] = $client->Name;
		}
		return $client_data;
	}
	private function get_lists($is_option = FALSE) {
		$lists = $this->construct->lists();
		if (!count($lists)) {
			ym_box_top($this->name . ' Error');
			echo '<p>' . __('You have no Lists on this Client on ' . $this->name . ' to associate with') . '</p>';
			ym_box_bottom();
			return;
		}
		$list_data = array();
		if ($is_option) {
			$list_data[] = __('--Select--');
		}
		foreach ($lists as $list) {
			$list_data[$list->ListID] = $list->Name;
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
		echo '<p>' . __('You are using ' . $this->name . ', Associated Lists can only be sent to via ' . $this->name) . '</p>';
		ym_box_bottom();

		add_filter('mailmanager_adjust_recipients', array($this, 'filter_lists_remove'), 10, 1);
	}
	function series_precontent() {
		ym_box_top('Message');
		echo '<p>' . __('Series are being kept synced with') . ' ' . $this->name . '</p>';
		echo '<p>' . __('The ' . $this->name . ' Associated Lists can only be sent to via') . ' ' . $this->name . ' A series link exists only for Sync Purposes</p>';
		ym_box_bottom();
		
		add_filter('mailmanager_adjust_recipients', array($this, 'filter_lists_remove'), 10, 1);
	}
	
	// sync up
	function broadcast_create($email_id, $email_subject, $email_content, $recipient_list, $time) {
		global $wpdb;
		
		$list_id = $this->associations->$recipient_list ? $this->associations->$recipient_list : FALSE;
		
		if (!$list_id) {
			return;
		}
		define('STOP_BROADCAST', TRUE);
		//
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
		foreach (mailmanager_get_recipients() as $list => $text) {
			if ($_GET['mm_action']) {
				echo $list;
			}
			if ($listid = $this->associations->$list) {
				if ($_GET['mm_action']) {
					echo ' has ';
				}
				$sql = 'SELECT id FROM ' . $wpdb->prefix . 'mm_series WHERE description = \'' . $listid . '\'';
				$series_id = $wpdb->get_var($sql);
				if (!$series_id) {
					$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_series(name, description, recipient_list) VALUES (\'' . $this->name . ': ' . $text . '\', \'' . $listid . '\', \'' . $list . '\')';
					$wpdb->query($sql);
					$series_id = $wpdb->insert_id;
				}
				
				// its associated
				$users_sub = array();
				$users_unsub = array();
				// get lists
				$subscribed = $this->construct->active_subscribers($listid);
				$unsubscribed = $this->construct->unsubscribed_subscribers($listid);
				
				// hope no list is greater than 1000
				foreach ($subscribed->Results as $user) {
					$users_sub[] = $user->EmailAddress;
					$user_id = get_user_by_email($user->EmailAddress);
					$user_id= $user_id->ID;
					$timestamp = strtotime($user->Date);
					
					mailmanager_get_user_in_series($user_id, $series_id, $list, $timestamp);
				}

				// unsub
				// hope no list is greater than 1000
				$sql = 'DELETE FROM ' . $wpdb->prefix . 'mm_list_unsubscribe WHERE list_name = \'' . $list . '\'';
				$wpdb->query($sql);
				foreach ($unsubscribed->Results as $user) {
					$users_unsub[] = $user->EmailAddress;
					
					$user = get_user_by_email($user->EmailAddress);
					$user_id = $user_id->ID;
					$timestamp = strtotime($user->Date);
					
					// log unsub
					mailmanager_unsubscribe_user($list, $user_id);
				}
				
				$users = array();
				foreach ($wpdb->get_results(mailmanager_get_sql($list)) AS $row) {
					$users[] = $row->email;
				}
				
				$users_to_add = array_diff($users, $users_sub, $users_unsub);
				if ($_GET['mm_action']) {
					echo 'Live: ' . sizeof($users_sub) . ' Local: ' . sizeof($users) . ' To Add: ' . sizeof($users_to_add);
				}
				
				foreach ($users_to_add as $user) {
					// fields
					$data = get_user_by_email($user);
					$theirname = $data->first_name . ' ' . $data->last_name;
					// assemble and add custom fields
					$custom = array();
					$fields = ym_get_custom_field_array($data->ID);
					if (sizeof($fields)) {
						foreach ($fields as $field => $value) {
							$custom[strtoupper($field)] = $value;
						}
					}
					if ($_GET['mm_action']) {
						echo "\n" . ' Adding ' . $user;
					}
					$this->construct->add_subscriber($listid, $user, $theirname, $custom);
				}
				if ($_GET['mm_action']) { echo "\n"; }
			}
			if ($_GET['mm_action']) { echo "\n"; }
		}
		if ($_GET['mm_action']) {
			echo '</pre>';
		}
	}
}
