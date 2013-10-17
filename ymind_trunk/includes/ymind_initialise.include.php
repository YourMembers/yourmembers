<?php

function ymind_activate() {
	get_currentuserinfo();
	global $current_user, $wpdb;

	if (!get_option('ymind_redirect_url')) {
		//delete cookie and log ou5
		$url = get_option('siteurl') . '/wp-login.php';
		$mail_subject = ''.__('Multiple Users Detected on user account', 'ymind').'';
		$mail_message = ''.__('Your User Account was accessed from two seperate IP addresses at the same time. Click the following link to reactivate your account:', 'ymind').' [activation_link]';
		$email_offender = '0';
		$timeout_minutes = 5;
		$timeout_logins = 2;
		$lockout_option = 1;
		$lockout_minutes = 5;
		$login_error = ''.__('Your account has been locked out.', 'ymind').'';
		$activation_url = get_option('siteurl') . '/wp-login.php';

		add_option('ymind_redirect_url', $url);
		add_option('ymind_email_offender', $email_offender);
		add_option('ymind_mail_subject', $mail_subject);
		add_option('ymind_mail_message', $mail_message);
		add_option('ymind_timeout_minutes', $timeout_minutes);
		add_option('ymind_timeout_logins', $timeout_logins);
		add_option('ymind_lockout_option', $lockout_option);
		add_option('ymind_locked_out_error', $login_error);
		add_option('ymind_lockout_minutes', $lockout_minutes);
		add_option('ymind_activate_redirect', $activation_url);

	}

	ymind_mysql_import(YMIND_SQL_IMPORT_FILE);
}

function ymind_deactivate() {
	global $wpdb;

	$sql = 'DELETE FROM ' . $wpdb->options . '
			WHERE option_name LIKE "ymind_%" ';
	$wpdb->query($sql);

	$tables_to_drop = array(
	'ymind_ip_log'
	, 'ymind_block_list'
	); //add your own table names in here to drop

	foreach ($tables_to_drop as $table) {
		$sql = 'DROP TABLE ' . $wpdb->prefix . $table;
		$wpdb->query($sql);
	}
/*
	delete_option('ymind_redirect_url');
	delete_option('ymind_email_offender');
	delete_option('ymind_mail_subject');
	delete_option('ymind_mail_message');
	delete_option('ymind_timeout_minutes');
	delete_option('ymind_timeout_logins');
	delete_option('ymind_lockout_option');
	delete_option('ymind_locked_out_error');
	delete_option('ymind_lockout_minutes');
	delete_option('ymind_activate_redirect');
	*/
}

function ymind_display_feedback($msg) {
	echo '<div id="message" class="updated fade" style="margin-top: 5px; padding: 7px;">' . $msg . '</div>';
}

function ymind_display_error($msg) {
	echo '<div id="message" class="error" style="margin-top: 5px; padding: 7px;">' . $msg . '</div>';
}

//leave this function alone. just pass it a .sql file and use its return (true or false) to show an error/feedback message
function ymind_mysql_import($filename) {
	global $wpdb;

	$return = false;
	$sql_start = array('INSERT', 'UPDATE', 'DELETE', 'DROP', 'GRANT', 'REVOKE', 'CREATE', 'ALTER');

	if (file_exists($filename)) {
		$query_string = false;
		$lines = file($filename);

		if (is_array($lines)) {
			foreach ($lines as $line) {
				$line = trim($line);

				if(!preg_match("'^--'", $line)) {
					$query_string.=" ".$line;
				}
			}

			if ($query_string) {
				$queries = explode(";", $query_string);
				$to_add = false;

				if (is_array($queries)) {
					$queries = array_reverse($queries, true);

					foreach ($queries as $sql) {
						$sql = trim($sql);

						if ($to_add) {
							$sql .= $to_add;
							$to_add = false;
						}

						$space = strpos($sql, ' ');
						$first_word = trim(strtoupper(substr($sql, 0, $space)));
						if (in_array($first_word, $sql_start)) {
							$pos = strpos($sql, '`')+1;
							$sql = substr($sql, 0, $pos) . $wpdb->prefix . substr($sql, $pos);

							$wpdb->query($sql);
							$to_add = false;
						} else {
							$to_add .= $sql;
						}
					}

					$return = true;
				}
			}
		}
	}

	return $return;
}

function ymind_start_box($title , $return=true){

	$html = '	<div class="postbox" style="margin: 5px 0px;">
					<h3>' . $title . '</h3>
					<div class="inside">';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

function ymind_end_box($return=true) {
	$html = '</div>
		</div>';

	if ($return) {
		return $html;
	} else {
		echo $html;
	}
}

?>