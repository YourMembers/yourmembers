<?php

global $ym_formgen;

$defaults = array(
	'email_name' => '',
    'email_subject' => '',
    'email_message' => '',
);
$postarr = wp_parse_args($_POST, $defaults);
$postarr = sanitize_post($postarr, 'db');

// export array as variables
extract($postarr, EXTR_SKIP);

do_action('mailmanager_create_precontent');

$title = __('Create', 'ym_mailmanager');
$titleU = __('Created', 'ym_mailmanager');
if ($email_id = ym_get('email_id')) {
	$title = __('Edit', 'ym_mailmanager');
	$titleU = __('Edited', 'ym_mailmanager');
	if (!$_POST) {
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'mm_email WHERE id = ' . $email_id;
		$r = $wpdb->get_results($sql);
		if ($r = $r[0]) {
			$email_name = $r->name;
			$email_subject = $r->subject;
			$email_message = $r->body;
		}
	}
}

ym_box_top($title . __(' a Email', 'ym_mailmanager'));

if (ym_post('submit')) {
	if ($email_name && ($email_subject || $email_message)) {
		$email_message = stripslashes($email_message);
		
		$email_id = ym_post('email_id');
		
		do_action('mailmanager_create_create', $email_id, $email_subject, $email_content);
		if (defined('STOP_CREATE')) {
			return;
		}
		
		if ($email_id) {
			$sql = 'UPDATE ' . $wpdb->prefix . 'mm_email SET name = \'' . $email_name . '\', subject = \'' . $email_subject . '\', body = \'' . $email_message . '\' WHERE id = ' . $email_id;
		} else {
			$sql = 'INSERT INTO ' . $wpdb->prefix . 'mm_email(name, subject, body) VALUES (\'' . $email_name . '\', \'' . $email_subject . '\', \'' . $email_message . '\')';
		}
		$wpdb->query($sql);
		if ($wpdb->insert_id || $wpdb->rows_affected) {
			echo '<div id="message" class="updated"><p>' . __('Email was ', 'ym_mailmanager') . $titleU . '</p></div>';

			$passed_id = $email_id ? $email_id : $wpdb->insert_id;
			include('preview.php');
			return;
		} else {
			echo '<p>' . sprintf(__('Failed to %s the email, please try again', 'ym_mailmanager'), $title) . '</p>';
		}
	} else {
		echo '<p>' . __('You must provide one of Email Subject or Content, in order to save a email', 'ym_mailmanager') . '</p>';
	}
}

echo '<form action="" method="post">';
echo '<table class="form-table">';

do_action('mailmanager_create_form');

if (!defined('MAILMANAGER_FORM_REPLACED')) {
	if ($email_id) {
		echo '<input type="hidden" name="email_id" value="' . $email_id . '" />';
	}


	echo $ym_formgen->render_form_table_text_row(__('Email Name', 'ym_mailmanager'), 'email_name', $email_name, __('Handy reference name', 'ym_mailmanager'));
	echo $ym_formgen->render_form_table_text_row(__('Email Subject', 'ym_mailmanager'), 'email_subject', $email_subject, __('Subject of Message', 'ym_mailmanager'));
	echo $ym_formgen->render_form_table_wp_editor_row(__('Email Message', 'ym_mailmanager'), 'email_message', $email_message, __('Message to Send, you can use HTML. You can use [ym_mm_custom_field field=""] [ym_mm_if_custom_field field=""]content[/ym_mm_if_custom_field] where the "" is a Custom Profile Field', 'ym_mailmanager'));
}

echo '</table>';
echo '<p class="submit" style="text-align: right;">
	<input type="submit" name="submit" value="' . __('Save Email', 'ym_mailmanager') . ' &raquo;" />
</p>';
echo '</form>';

ym_box_bottom();
