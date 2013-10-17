<?php

/*
* $Id: ym-system.class.php 2596 2013-02-07 16:41:09Z bcarlyon $
* $Revision: 2596 $
* $Date: 2013-02-07 16:41:09 +0000 (Thu, 07 Feb 2013) $
*/

class YourMember_System {
	var $admin_role, $account_interface_admin_role, $post_delay_start;
	var $item_name, $modified_registration, $allow_logged_out_ppp_purchase;
	var $from_name, $from_email, $filter_all_emails, $use_rss_token, $download_hook;

	var $required_custom_field_symbol, $upgrade_downgrade_string;

	var $hide_custom_fields, $hide_membership_content, $allow_upgrade_to_same;
	var $default_thanks_message, $default_thanks_title, $default_failed_message, $default_failed_title;
	var $default_ppp, $default_ppp_cost, $default_account_types, $vat_rate, $global_vat_applicable;

	var $login_redirect_url, $wpadmin_disable_redirect_url, $logout_redirect_url;
	var $ym_get_login_link_url, $ym_get_register_link_url, $upgrade_link, $membership_details_redirect_url;
	
	var $email_reminder_enable, $email_reminder_limit;
	var $email_reminder_subject, $email_reminder_subject_recur;
	var $email_reminder_message, $email_reminder_message_recur;
	var $email_drip_reminder_enable, $email_drip_message, $email_drip_subject;
	
	var $advertise_ym, $advertise_ym_affid, $advertise_ym_text;
	var $grace_enable, $grace_limit;

	var $protect_mode;
	var $redirect_on_homepage;
	var $no_access_redirect_lo, $no_access_redirect_li;
	var $wp_login_header_url,  $wp_login_header_logo;
	var $hide_pages, $hide_posts;

	var $block_wp_login_action_register;

	var $enable_manual_cron, $cron_notify_email, $cron_notify_subject;
	var $dev_tools;

	var $register_https_only, $register_https_pages, $register_https_escape;
	var $expire_time_hour, $expire_time_min, $expire_time_sec;

	var $export_last_tmp_path;

	var $enable_metered, $metered_posts, $metered_duration, $metered_duration_type, $metered_account_types, $enable_dnt_metered, $enable_fcf_metered; 
	
	function update($vars) {
		foreach (get_class_vars(get_class($this)) as $k=>$v) {
			if (isset($vars[$k])) {
				$this->$k = $vars[$k];
			}
		}
		$this->save();
	}

	function update_from_post() {
		foreach (get_class_vars(get_class($this)) as $k=>$v) {
			if (isset($_POST[$k])) {
				if (is_array($_POST[$k])) {
					$this->$k = implode(';', $_POST[$k]);
				} else {
					$this->$k = stripslashes($_POST[$k]);
				}
			}
		}
		$this->save();
	}

	function save() {
		update_option('ym_sys', $this);
	}

	function initialise($option = 'ym_sys') {
		$home_url = site_url('/');

		$thanks_message = __('<p>Thank you for your payment. You will be receiving an email shortly to confirm the transaction.</p><p>Use the menu to the right or click one of the links below to continue.</p><p>Go to:</p>', 'ym');
		$failed_message = __('<p>There was a problem with this transaction. You will be receiving an email shortly to confirm the problem.</p><p>Use the menu to the right or click one of the links below to continue.</p><p>Go to:</p>', 'ym');

		$ending = '	<ul>
				<li>
					<a href="' . $home_url . '">' . __('Homepage', 'ym') . '</a>
				</li>
				<li>
					<a href="' . $home_url . 'wp-admin">' . __('Your Profile', 'ym') . '</a>
				</li>
			</ul>';

		$thanks_message .= $ending;
		$failed_message .= $ending;

		$this->admin_role  = 'administrator';
		$this->account_interface_admin_role  = 'administrator';
		$this->post_delay_start = '';

		$this->item_name  = get_option('blogname') . ' ' . __('Subscription', 'ym');
		$this->modified_registration  = true;
		$this->allow_logged_out_ppp_purchase = false;

		$this->from_name  = get_option('blogname');
		$this->from_email  = get_option('admin_email');
		$this->filter_all_emails = TRUE;

		$this->use_rss_token  = true;
		$this->download_hook  = 'download';

		$this->required_custom_field_symbol = __('* Required', 'ym');
		$this->upgrade_downgrade_string = __('Upgrade / Downgrade your account', 'ym');

		$this->hide_custom_fields  = false;
		$this->hide_membership_content  = FALSE;
		$this->allow_upgrade_to_same = TRUE;

		$this->default_thanks_message  = $thanks_message;
		$this->default_thanks_title  = __('Thank You', 'ym');
		$this->default_failed_message  = $failed_message;
		$this->default_failed_title = __('There was a problem', 'ym');

		$this->default_ppp  = true;
		$this->default_ppp_cost  = '5.00';
		$this->default_account_types  = '';
		$this->vat_rate  = 0;
		$this->global_vat_applicable = false;

		$this->login_redirect_url  = '';
		$this->wpadmin_disable_redirect_url = '';
		$this->logout_redirect_url = '';

		$this->ym_get_login_link_url = '';
		$this->ym_get_register_link_url = '';
		$this->upgrade_link = '';
		$this->membership_details_redirect_url = '';

		$this->email_reminder_enable  = false;
		$this->email_reminder_limit  = 7;

		$this->email_reminder_subject  = __('[[site_name]] Your account will expire soon', 'ym');
		$this->email_reminder_message = __('This is just a reminder to say your account is due to expire soon.', 'ym');

		$this->email_reminder_subject_recur  = __('[[site_name]] Your account will renew soon', 'ym');
		$this->email_reminder_message_recur = __('This is just a reminder to say your subscription is soon to be renewed and payment taken.', 'ym');

		$this->email_drip_reminder_enable = false; 
		$this->email_drip_message = __('[[site_name]] New Content Available','ym'); 
		$this->email_drip_subject = __('This is just a reminder to say some new content is now available for you','ym');
		
		$this->advertise_ym  = false;
		$this->advertise_ym_affid  = '';
		$this->advertise_ym_text = __('Your Members', 'ym');

		$this->grace_enable  = false;
		$this->grace_limit = 7;

		$this->protect_mode = 0;//flexible

		$this->no_access_redirect_lo  = '/wp-login.php?checkemail=loginneeded';
		$this->no_access_redirect_li  = '/wp-login.php?checkemail=noaccess';

		$this->wp_login_header_url = '/';
		$this->wp_login_header_logo = '';

		$this->hide_pages = 0;
		$this->hide_posts = 0;

		$this->block_wp_login_action_register = '';

		$this->enable_manual_cron = false;
		$this->cron_notify_email = '';
		$this->cron_notify_subject = '';

		$this->register_https_only = false;
		$this->register_https_pages = '';
		$this->register_https_escape = false;

		$this->expire_time_hour = 0;
		$this->expire_time_min = 0;
		$this->expire_time_sec = 0;

		$this->metered_posts = 0;
		$this->enable_metered = false;
		$this->metered_duration = 0;
		$this->metered_duration_type = false;
		$this->metered_account_types = false;
		$this->enable_dnt_metered = false;
		$this->enable_fcf_metered = false;

		$this->export_last_tmp_path = '';

		// don't overwrite
		add_option($option, $this);
	}
}
