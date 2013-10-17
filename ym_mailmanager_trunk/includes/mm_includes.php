<?php

// braodcast handler
add_action('mailmanager_cron_check', 'mailmanager_check_series_queue', 10);

// move to wp_mail class?
add_action('mm_scheduled_email', 'mailmanager_go_send_email', 10, 2);
function mailmanager_go_send_email($email_id, $recipient_list) {
	global $wpdb;
	var_dump($recipient_list);
	if ($sql = mailmanager_get_sql($recipient_list)) {
		if ($users = $wpdb->get_results($sql)) {
			var_dump($users);
			foreach ($users as $i=>$user) {
				$user_id = mailmanager_get_user_id($user->email);

				list($subject, $body) = mailmanager_process_hooks($email_id, false, $user_id);

				mailmanager_send_email($user->email, $subject, $body);
				mailmanager_log_email_send($user_id, $email_id);
			}
		}
	}
}
// end

function mailmanager_get_sql($recipient_list) {
	global $wpdb;
	
	$sql = false;

	if ($recipient_list == 'wordpress_users') {
		$sql = 'SELECT DISTINCT(user_email) AS email
				FROM ' . $wpdb->prefix . 'users';
	} else if ($recipient_list == 'wordpress_commenters') {
		$sql = 'SELECT DISTINCT(comment_author_email)
				FROM ' . $wpdb->comments;
	} else if ($recipient_list == 'wordpress_registered_commenters') {
		$sql = 'SELECT DISTINCT(comment_author_email)
				FROM ' . $wpdb->comments . '
				WHERE user_id > 0';
	} else if ($recipient_list == 'wordpress_guest_commenters') {
		$sql = 'SELECT DISTINCT(comment_author_email)
				FROM 
					' . $wpdb->comments . ' c
					LEFT JOIN ' . $wpdb->users . ' u ON (c.comment_author_email = u.user_email)
				WHERE u.id IS NULL';
	} else if ($recipient_list == 'ym_all_active') {
		$sql = 'SELECT DISTINCT(user_email) AS email
				FROM 
					' . $wpdb->prefix . 'users u
					JOIN ' . $wpdb->usermeta . ' um ON (
						u.ID = um.user_id
						AND um.meta_key = "ym_status"
						AND um.meta_value = "Active"
					)';
	} else if ($recipient_list == 'ym_all_inactive') {
		$sql = 'SELECT DISTINCT(user_email) AS email
				FROM 
					' . $wpdb->prefix . 'users u
					JOIN ' . $wpdb->usermeta . ' um ON (
						u.ID = um.user_id
						AND um.meta_key = "ym_status"
						AND um.meta_value = "Inactive"
					)';
	} else if ($recipient_list == 'ym_all_expired') {
		$sql = 'SELECT DISTINCT(user_email) AS email
				FROM 
					' . $wpdb->prefix . 'users u
					JOIN ' . $wpdb->usermeta . ' um ON (
						u.ID = um.user_id
						AND um.meta_key = "ym_status"
						AND um.meta_value = "Expired"
					)';
	} else if (substr($recipient_list, 0, 6) == 'ym_ac_') {
		$account_type = substr($recipient_list, 6);

		$sql = 'SELECT DISTINCT(u.user_email) AS email
				FROM 
					' . $wpdb->prefix . 'users u 
					JOIN ' . $wpdb->prefix . 'usermeta m ON (u.id = m.user_id) 
				WHERE 
					meta_key = \'ym_user\' 
					AND meta_value LIKE \'%account_type";s:' . strlen($account_type) . ':"' . $account_type . '";%\'';
	} else if (substr($recipient_list, 0, 8) == 'ym_pack_') {
		$pack_id = substr($recipient_list, 8);
		
		// get all users with this pack
		$test = 's:7:"pack_id";s:' . strlen($pack_id) . ':"' . $pack_id . '";';
		$sql = 'SELECT DISTINCT(u.user_email) AS email
				FROM
					' . $wpdb->prefix . 'users u
					JOIN ' . $wpdb->prefix . 'usermeta m ON (u.id = m.user_id) 
				WHERE 
					meta_key = \'ym_user\'
					AND meta_value LIKE \'%' . $test . '%\'';
	}
	return $sql;
}

$ym_mm_custom_field_user_id = 0;
function mailmanager_process_hooks($email_id, $series_id, $user_id=false) {
	$settings = get_option('ym_other_mm_settings');
	$email = mailmanager_get_email($email_id);

	$subject = $email->subject;
	if ($series_id) {
		$body = $settings->generic_header . $email->body . $settings->generic_footer;
	} else {
		$body = $settings->broadcast_header . $email->body . $settings->broadcast_footer;
	}

	$home = get_option('siteurl');

	//hook processing here
	$unsub_url = mailmanager_get_unsubscribe_url($user_id, $series_id);
	$body = str_replace('[unsubscribe]', $unsub_url, $body);
	$body = str_replace('[address]', $settings->postal_address, $body);
	
	global $ym_mm_custom_field_user_id;
	$ym_mm_custom_field_user_id = $user_id;
	add_shortcode('ym_mm_custom_field', 'mailmanager_custom_fields_shortcode');
	add_shortcode('ym_mm_if_custom_field', 'mailmanager_custom_fields_shortcode');
	
	$body = do_shortcode($body);

	return array($subject, $body);
}

function mailmanager_get_recipients() {
	$recipients = array(
		'wordpress_users'					=> __('Entire Wordpress User Database', 'ym_mailmanager'),
		'wordpress_commenters'				=> __('All Blog Commenters', 'ym_mailmanager'),
		'wordpress_guest_commenters'		=> __('All Guest Commenters (No user account)', 'ym_mailmanager'),
		'wordpress_registered_commenters'	=> __('All Registered Commenters', 'ym_mailmanager'),
	);
	if ($ym_account_types = get_option('ym_account_types')) {
		$recipients['ym_all_active']		= __('Your Members Active Users', 'ym_mailmanager');
		$recipients['ym_all_inactive']		= __('Your Members Inactive Users', 'ym_mailmanager');
		$recipients['ym_all_expired']		= __('Your Members Expired Users', 'ym_mailmanager');

		foreach ($ym_account_types->types as $type) {
			$recipients['ym_ac_' . strtolower($type)]	=  __('Your Members active members of type: ', 'ym_mailmanager') . $type;
		}
	}
	if ($ym_packs = ym_get_packs()) {
		foreach ($ym_packs as $pack) {
			$recipients['ym_pack_' . $pack['id']] = strip_tags(ym_get_pack_label($pack));
		}
	}
	
 	return apply_filters('mailmanager_adjust_recipients', $recipients);
}

function mailmanager_get_unsub_count($list) {
	global $wpdb;
	
	$sql = 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'mm_list_unsubscribe
	WHERE list_name = \'' . $list . '\'';
	return $wpdb->get_var($sql);
}
function mailmanager_get_unsubs($recipient_list) {
	global $wpdb;
	
	$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mm_list_unsubscribe
	WHERE list_name = \'' . $recipient_list . '\'';
	return $wpdb->get_results($sql);
}
function mailmanager_get_size_of_list($list) {
	global $wpdb;
	$count_sql = mailmanager_get_sql($list);
	$wpdb->query($count_sql);
	$users_in_list = $wpdb->num_rows;
	$unsubscribe_count = mailmanager_get_unsub_count($list);
	$total = $users_in_list - $unsubscribe_count;
	return $total;
}

function mailmanager_get_email($id=false) {
	global $wpdb;

	$email = new stdClass();
	$email->id = $email->name = $email->subject = $email->body = false;
	$email->active = true;

	if ($id) {
		$sql = 'SELECT id, name, subject, body, active
				FROM ' . $wpdb->prefix . 'mm_email
				WHERE id = ' . $id;
		$email = $wpdb->get_row($sql);
	}

	return $email;
}
function mailmanager_get_user_id($email, $name=false) {
	global $wpdb;
	
	return get_user_by_email($email)->ID;
}

function mailmanager_send_email($to, $subject, $message) {
	$settings = get_option('ym_other_mm_settings');
	$from_email = $settings->from_email;
	$from_name = $settings->from_name;

	$headers = "MIME-Version: 1.0\r\n";
	
	if ($from_name) {
		$headers .= "From: " . $from_name . ($from_email ? " <" . $from_email . ">":'') . "\n";
	}
	
	if ($from_email) {
		$headers .= "Reply-To: " . $from_email . "\n";
		$headers .= "Return-Path: " . $from_email . "\n";
		$headers .= "X-Sender: " . $from_email . "\n";
	}
	
	$headers .= "	X-Mailer: PHP/" . phpversion() . "
			Content-Type: text/html; charset=\"iso-8859-1\"
			Content-Transfer-Encoding: 8bit";

	if ($settings->mail_gateway != 'wp_mail') {
		// test me test me test me
//		$callback = array($mailgateway, 'mailmanager_' . $settings->mail_gateway . '_send_email');
//		if (function_exists($callback)) {
//			return call_user_func($callback, $to, $subject, $message, $headers);
//		}
	}
	return wp_mail($to, $subject, $message, $headers);
}

function mailmanager_log_email_send($user_id, $email_id) {
	global $wpdb;
	
	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_email_sent(user_id, email_id, sent_date) VALUES (' . $user_id . ', ' . $email_id . ', ' . time() . ')';
	$wpdb->query($sql);
	return;
}

if (!function_exists('mailmanager_get_emails')) {
	function mailmanager_get_emails($add_blank = FALSE) {
		global $wpdb;

		$emails = array();

		if ($add_blank) {
			$emails[] = 'Select';
		}

		$sql = 'SELECT id, name FROM ' . $wpdb->prefix . 'mm_email WHERE active = 1 ORDER BY name';
		foreach ($wpdb->get_results($sql) as $email) {
			$emails[$email->id] = $email->name;
		}
		return $emails;
	}
}

function mailmanager_get_user_in_series($user_id, $series_id, $recipient_list, $timestamp = FALSE) {
	global $wpdb;
	
	if (!$timestamp) {
		$timestamp = time();
	}
	
	$sql = 'SELECT user_id FROM ' . $wpdb->prefix . 'mm_list_unsubscribe WHERE list_name = \'' . $recipient_list . '\' AND user_id = ' . $user_id;
	if ($wpdb->get_var($sql)) {
		
		return FALSE;// user is unsubscribed
	}
	
	$sql = 'SELECT start_date FROM ' . $wpdb->prefix . 'mm_user_series_assoc WHERE user_id = ' . $user_id . ' AND series_id = \'' . $series_id . '\'';
	if ($r = $wpdb->get_var($sql)) {
		// user is on step two
		
		return $r;
	} else {
		// new user
		
		$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_user_series_assoc(user_id, series_id, start_date) VALUES (' . $user_id . ', ' . $series_id . ', ' . $timestamp . ')';		
		$wpdb->query($sql);
		
		return $timestamp;
	}
}

function mailmanager_get_unsubscribe_url($user_id, $series_id) {
	$url = get_bloginfo('wpurl');
	$url .= '?ym_mm_action=unsubscribe';
	$url .= '&series_id=' . $series_id;
	$url .= '&user_id=' . $user_id;
	
	return $url;
}
function mailmanager_unsub_check() {
	if (ym_get('ym_mm_action') == 'unsubscribe') {
		/*
		$current_settings = get_option('ym_other_mm_settings');
		if ($current_settings->mail_gateway != 'wp_mail') {
			// hook
			$callback = 'ym_mm_' . $current_settings->mail_gateway . '_unsub_block';
			if (function_exists($callback)) {
				$break = FALSE;
				call_user_func($callback, $list, $user, &$break);
				if ($break) {
					return;
				}
			}
		}
		*/
		
		$series = ym_get('series_id');
		$user = ym_get('user_id');
		if ($series && $user) {
			// the_content
			global $wpdb;
			$sql = 'SELECT recipient_list FROM ' . $wpdb->prefix . 'mm_user_series_assoc WHERE series_id = \'' . $series . '\'';
			$list = $wpdb->get_var($sql);
			
			$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_list_unsubscribe (list_name, user_id) VALUES (' . $list . ', ' . $user . ')';
			$wpdb->query($sql);
			
			$current_settings = get_option('ym_other_mm_settings');
			header('Location: ' . $current_settings->unsubscribe_page);
			exit;
		}
	}
}
function mailmanager_unsubscribe_user($list_name, $user_id) {
	global $wpdb;
	$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_list_unsubscribe(list_name, user_id) VALUES (\'' . $list_name . '\', \'' . $user_id . '\')';
	$wpdb->query($sql);
	return $wpdb->insert_id;
}

function mailmanager_check_series_queue() {
	$mm_action = $_GET['mm_action'];
	if ($mm_action) {
		echo __('The MM Check Series Queue is being manually run', 'ym_mailmanager') . '<br />';
	}
	global $wpdb;
	
	// contains a element with target email_id
	$emails_to_send = array();
	
	//get series
	$sql = 'SELECT id, recipient_list FROM ' . $wpdb->prefix . 'mm_series WHERE enabled = 1';
	foreach ($wpdb->get_results($sql) AS $row) {
		//get target users
		if ($sql = mailmanager_get_sql($row->recipient_list)) {
			if ($users = $wpdb->get_results($sql)) {
				foreach ($users as $i=>$user) {
					if ($mm_action) {
						echo '<br />' . __('Found', 'ym_mailmanager') . ' '. $user->email;
					}
					//add users not in table
					$user_id = mailmanager_get_user_id($user->email);
					// when did user join series
					$user_join = mailmanager_get_user_in_series($user_id, $row->id, $row->recipient_list);
					if ($user_join) {
						if ($mm_action) {
							echo ' ' . __('user is subscribed', 'ym_mailmanager');
						}
						// get what has been sent to this users
						$sql = 'SELECT email_id FROM ' . $wpdb->prefix . 'mm_email_sent WHERE user_id = ' . $user_id;
						$ignore = array();
						foreach ($wpdb->get_results($sql) AS $sent) {
							$ignore[] = $sent->email_id;
						}

						// series emails
						$sql = 'SELECT email_id, delay_days FROM ' . $wpdb->prefix . 'mm_email_in_series WHERE series_id = ' . $row->id;

						$emails = array();
						$one_day = 86400;

						foreach ($wpdb->get_results($sql) AS $email) {
							$offset = $email->delay_days * $one_day;
							$send_email = $user_join + $offset;
							// already sent this email id?
							if (!in_array($email->email_id, $ignore)) {
								// check if need senting
								if ($send_email <= time()) {
									$emails[] = $email->email_id;
								}
							}
						}

						// emails now contains the emails in this series that are due to be sent
						// in theory this should only be a array of size 1
						// depends how many emails of day delay 0 there are
						foreach ($emails as $email_id) {
							if ($mm_action) {
								echo ' ' . __('sending EID:', 'ym_mailmanager') . $email_id;
							}
							list($subject, $body) = mailmanager_process_hooks($email_id, $row->id, $user_id);

							mailmanager_send_email($user->email, $subject, $body);
							mailmanager_log_email_send($user_id, $email_id);
						}
					}
				}
			}
		}
	}
}

function mailmanager_load_gateways() {
	if ($handle = opendir(YM_MM_GATEWAY_DIR)) {
		while (FALSE !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && is_dir(YM_MM_GATEWAY_DIR . $file)) {
				if ($sub_handle = opendir(YM_MM_GATEWAY_DIR . $file)) {
					while (FALSE !== ($sub_file = readdir($sub_handle))) {
						if (substr($sub_file, -4, 4) == '.php') {
							require_once(YM_MM_GATEWAY_DIR . $file . '/' . $sub_file);
							$sub_file = substr($sub_file,0,-4);
							// instantiate
							$class = 'mailmanager_' . $sub_file . '_gateway';
							if (class_exists($class)) {
								$class = new $class();
								$gateways[$class->safe_name] = array(
									'name'		=> $class->name,
									'safe'		=> $class->safe_name,
									'desc'		=> $class->description,
									'logo'		=> $class->logo,
									'settings'	=> $class->settings,
								);
								unset($class);
							}
						}
					}
				}
				closedir($sub_handle);
			}
		}
		closedir($handle);
	}
	return $gateways;
}
function mailmanager_load_active_gateway(&$nav_pages) {
	global $mailgateway;
	$current_settings = get_option('ym_other_mm_settings');
	if ($current_settings->mail_gateway) {
		if (!is_file(YM_MM_GATEWAY_DIR . $current_settings->mail_gateway . '/' . $current_settings->mail_gateway . '.php')) {
			// its gone!
			// revert
			$current_settings->mail_gateway = 'wp_mail';
			update_option('ym_other_mm_settings', $current_settings);
			echo '<div><p>' . __('Gateway Files missing Reset to WPMail', 'ym_mailmanager') . '</p></div>';
		}
		require_once(YM_MM_GATEWAY_DIR . $current_settings->mail_gateway . '/' . $current_settings->mail_gateway . '.php');
		$mailgateway = 'mailmanager_' . $current_settings->mail_gateway . '_gateway';
		$mailgateway = new $mailgateway();
		if (method_exists($mailgateway, 'settings')) {
			$nav_pages[$mailgateway->name . ' ' . __('Settings', 'ym_mailmanager')] = 'mailmanager&mm_action=gateway';
		}
	}
	return;
}
function mailmanager_active_gateway() {
	$current_settings = get_option('ym_other_mm_settings');
	return $current_settings->mail_gateway;
}

function mailmanager_email_stats() {
	global $wpdb;
	
	do_action('mailmanager_email_stats');
	if (defined('STOP_EMAIL_STATS')) {
		return;
	}
	
	$sql = 'SELECT COUNT(ID) FROM ' . $wpdb->prefix . 'mm_email_sent';
	echo '<p>' . sprintf(__('You have sent %s Emails', 'ym_mailmanager'), $wpdb->get_var($sql)) . '</p>';
	
	$count = 0;
	$cron = get_option('cron');
	$first_time = FALSE;
	foreach ($cron as $time => $stuff) {
		$first_time = $first_time ? $first_time : $time;
		if (is_array($stuff)) {
			foreach ($stuff as $hook => $uniquedata) {
				if ($hook == 'mm_scheduled_email') {
					$count ++;
					foreach ($uniquedata as $schedule) {
						// $schedule['schedule'] = schdule on (usually blank)
						// $schedule['args'] 0 is email id 1 is targets
					}
				}
			}
		}
	}
	
	echo '<p>' . sprintf(__('There are %s Broadcast Emails Scheduled', 'ym_mailmanager'), $count);
	if ($first_time) {
		echo ', ' . __('the next is due:', 'ym_mailmanager') . ' ' . date(get_option('time_format') . ' ' . get_option('date_format'), $first_time);
	}
	echo '</p>';
	
	$sql = 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'mm_series WHERE enabled = 1';
	$count1 = $wpdb->get_var($sql);

	$sql = 'SELECT COUNT(id) FROM ' . $wpdb->prefix . 'mm_series';
	$count2 = $wpdb->get_var($sql);
	
	echo '<p>';
	echo sprintf(__('There are %s/%s Series currently active', 'ym_mailmanager'), $count1, $count2);
	echo '</p>';
}
function mailmanager_email_stats_output() {
	global $mm;
	ym_box_top(__('MailManager Email Stats', 'ym_mailmanager'));
	mailmanager_email_stats();
	echo '<p style="text-align: right;"><a href="' . $mm->page_root . '">' . __('MailManager', 'ym_mailmanager') . '</a></p>';
	ym_box_bottom();
}
add_action('ym_nm_news_box', 'mailmanager_email_stats_output');
function mailmanager_list_stats() {
	ym_box_top('List Stats');
	
	echo '<ul>';
	foreach (mailmanager_get_recipients() AS $value => $text) {
		echo '<li>';
		echo $text;
		echo ' - ';
		echo mailmanager_get_size_of_list($value);
		echo ' ' . __('Subscribers', 'ym_mailmanager');
		echo '</li>';
	}
	echo '</ul>';
	
	ym_box_bottom();
}
add_action('ym_nm_news_box', 'mailmanager_list_stats');

// SHORTCODE
function mailmanager_custom_fields_shortcode($atts, $content = '') {
	global $ym_mm_custom_field_user_id;
	if ($atts['field']) {
		$field = strtolower($atts['field']);
		if ($content) {
			// if
			if (get_usermeta($ym_mm_custom_field_user_id, $field)) {
				return $content;
			}
			$fields = ym_get_custom_field_array($ym_mm_custom_field_user_id);
			if (isset($fields[$field])) {
				return $content;
			}
		} else {
			// try for user_meta
//			$user_meta = get_userdata($ym_mm_custom_field_user_id);
//			if ($d = $user_meta->$field) {
			if ($d = get_usermeta($ym_mm_custom_field_user_id, $field)) {
				return $d;
			}
			//$ym_user = get_usermeta($ym_mm_custom_field_user_id, 'ym_user');
			//print_r($ym_user);
			$fields = ym_get_custom_field_array($ym_mm_custom_field_user_id);
			if (isset($fields[$field])) {
				return $fields[$field];
			}
		}
	}
	return '';
}
