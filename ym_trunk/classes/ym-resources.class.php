<?php

/*
* $Id: ym-resources.class.php 2523 2013-01-15 11:36:41Z tnash $
* $Revision: 2523 $
* $Date: 2013-01-15 11:36:41 +0000 (Tue, 15 Jan 2013) $
*/

class YourMember_Resources {
	var $login_errmsg_null, $login_errmsg_trial_expired, $login_errmsg_expired, $login_errmsg_pending, $login_errmsg_default;
	var $login_errmsg_parent_expired, $login_errmsg_parent_cancel, $login_errmsg_parent_config;

	var $currency, $subs_intro, $tos, $private_text, $private_text_purchasable, $login_first_text, $members_content_divider_html;
	var $activate_account, $no_access, $pack_string_template;
	var $ppp_no_login_email_body, $ppp_no_login_email_subject, $purchasable_at_limit, $purchasable_pack_only, $msg_header, $msg_footer;
	var $members_content_divider2_html;
	var $ym_ppp_none_msg, $unsubscribe_left_msg;

	var $payment_gateway_subject_post_success, $payment_gateway_subject_post_failed;
	var $payment_gateway_subject_ppack_success, $payment_gateway_subject_ppack_failed;
	var $payment_gateway_subject_subscription_success, $payment_gateway_subject_subscription_failed;

	var $payment_gateway_message_post_success, $payment_gateway_message_post_failed;
	var $payment_gateway_message_ppack_success, $payment_gateway_message_ppack_failed;
	var $payment_gateway_message_subscription_success, $payment_gateway_message_subscription_failed;

	var $payment_gateway_enable_post_success, $payment_gateway_enable_post_failed;
	var $payment_gateway_enable_ppack_success, $payment_gateway_enable_ppack_failed;
	var $payment_gateway_enable_subscription_success, $payment_gateway_enable_subscription_failed;

	// BCC Vars
	var $payment_gateway_email_post_success, $payment_gateway_email_post_failed;
	var $payment_gateway_email_ppack_success, $payment_gateway_email_ppack_failed;
	var $payment_gateway_email_subscription_success, $payment_gateway_email_subscription_failed;

	var $purchasable_bundle_at_limit;

	var $checkemail_subscribed, $checkemail_bundle, $checkemail_post, $checkemail_loginneeded, $checkemail_noaccess;

	var $all_content_not_logged_in, $all_bundles_not_logged_in, $featured_content_not_logged_in, $featured_bundles_not_logged_in;

	var $registration_flow_email_invalid, $registration_flow_email_inuse, $registration_flow_username_inuse, $registration_flow_required_fields, $registration_flow_invalid_coupon, $registration_flow_invalid_password;

	function __construct() {
		if ($data = get_option('ym_res')) {
			foreach ($data as $key => $item) {
				$this->$key = $item;
			}
		} else {
			$this->initialise();
		}
		$this->prepare();
	}

	function prepare() {
		if (!is_array($this->payment_gateway_email_post_success)) {
			$this->payment_gateway_email_post_success = explode(';', $this->payment_gateway_email_post_success);
			$this->payment_gateway_email_post_failed = explode(';', $this->payment_gateway_email_post_failed);
			$this->payment_gateway_email_ppack_success = explode(';', $this->payment_gateway_email_ppack_success);
			$this->payment_gateway_email_ppack_failed = explode(';', $this->payment_gateway_email_ppack_failed);
			$this->payment_gateway_email_subscription_success = explode(';', $this->payment_gateway_email_subscription_success);
			$this->payment_gateway_email_subscription_failed = explode(';', $this->payment_gateway_email_subscription_failed);
		}
	}

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
		if (is_array($this->payment_gateway_email_post_success)) {
			$this->payment_gateway_email_post_success = implode(';', $this->payment_gateway_email_post_success);
			$this->payment_gateway_email_post_failed = implode(';', $this->payment_gateway_email_post_failed);
			$this->payment_gateway_email_ppack_success = implode(';', $this->payment_gateway_email_ppack_success);
			$this->payment_gateway_email_ppack_failed = implode(';', $this->payment_gateway_email_ppack_failed);
			$this->payment_gateway_email_subscription_success = implode(';', $this->payment_gateway_email_subscription_success);
			$this->payment_gateway_email_subscription_failed = implode(';', $this->payment_gateway_email_subscription_failed);
		}

		update_option('ym_res', $this);

		$this->prepare();
	}

	function initialise() {
		$this->login_errmsg_null = sprintf(__('Your account is not active. To activate your account please complete the <a href="%s">subscription payment</a>','ym'), site_url('?ym_subscribe=1&username=[[USERNAME]]'));
		$this->login_errmsg_trial_expired = sprintf(__('Your trial account has expired. To continue using this site, please make a <a href="%s">subscription payment</a> to re-activate your account.','ym'), trailingslashit(get_option('siteurl')) . '?ym_subscribe=1&amp;username=[[USERNAME]]');
		$this->login_errmsg_expired = sprintf(__('Your Account has expired. Please make a new <a href="%s">subscription payment</a> to re-activate your account.','ym'), site_url('?ym_subscribe=1&username=[[USERNAME]]'));
		$this->login_errmsg_pending = __('Your Account is now PENDING for activation!','ym');

		$this->login_errmsg_parent_expired = __('The Group Leaders Account has Expired', 'ym');
		$this->login_errmsg_parent_cancel = __('Your Account has been Blocked by the Group Leader', 'ym');
		$this->login_errmsg_parent_config = __('Your Account needs to be Configured by the Group Leader', 'ym');

		$this->login_errmsg_default = __('Your account is not active','ym');

		$this->currency = 'USD';
		$this->subs_intro = __('After pressing the register button you will be asked to make payment for your registration.','ym');
		$this->tos = __('Please replace this text with your Terms & Conditions','ym');
		$this->private_text = __('You need to be logged in to see this part of the post','ym');
		$this->private_text_purchasable = __('This post is available for purchase', 'ym');
		$this->login_first_text = __('This text is available for purchase but you need to login or register first', 'ym');
		$this->members_content_divider_html = '';

		$this->activate_account = '';
		$this->no_access = __('Sorry but you do not have access to this post/page','ym');
		$this->pack_string_template = __('[account_type] - [cost] [currency] per [duration] [duration_period]', 'ym');

		$this->purchasable_at_limit = __('Only a limited number of this post were available. They have now all been bought, keep an eye on this page in case the quota is lifted', 'ym');
		$this->purchasable_pack_only = __('This post is available for purchase but only as part of a pack.', 'ym');
		$this->msg_header = '<div class="ym_message"><div class="ym_message_liner">';
		$this->msg_footer = '</div></div>';

		$this->members_content_divider2_html = '';

		$this->ym_ppp_none_msg = __('You have not yet purchased any posts.', 'ym');
		$this->unsubscribe_left_msg = __('Sorry to see you go', 'ym');


		$this->payment_gateway_subject_post_success = __('[blogname] - Your Purchase of Post: [post_title]', 'ym');
		$this->payment_gateway_subject_post_failed = __('[blogname] - Your Purchase of Post: [post_title]', 'ym');
		$this->payment_gateway_subject_ppack_success = __('[blogname] - Your Purchase of Post Pack: [pack_title]', 'ym');
		$this->payment_gateway_subject_ppack_failed = __('[blogname] - Your Purchase of Post Pack: [pack_title]', 'ym');
		$this->payment_gateway_subject_subscription_success = __('[blogname] - Your Purchase of Subscription: [pack_label]', 'ym');
		$this->payment_gateway_subject_subscription_failed = __('[blogname] - Your Purchase of Subscription: [pack_label]', 'ym');

		$this->payment_gateway_message_post_success = __('Hello [display_name],<br /><br />Thank you for your Purchase of Post: [post_title].<br />The transaction is complete and you can now view the post<br />[post_link]<br /><br />[blogname]', 'ym');
		$this->payment_gateway_message_post_failed = __('Hello [display_name],<br /><br />Thank you for your Purchase of Post: [post_title].<br />The transaction is incomplete or has failed, if you believe this is in error, Please contact Us<br /><br />[blogname]', 'ym');
		$this->payment_gateway_message_ppack_success = __('Hello [display_name],<br /><br />Thank you for your Purchase of Post Pack: [pack_name].<br />The transaction is complete and you can now view the posts within the pack<br />[posts_in_pack]<br /><br />[blogname]', 'ym');
		$this->payment_gateway_message_ppack_failed = __('Hello [display_name],<br /><br />Thank you for your Purchase of Post Pack: [pack_name].<br />The transaction is incomplete or has failed, if you believe this is in error, Please contact Us<br /><br />[blogname]', 'ym');
		$this->payment_gateway_message_subscription_success = __('Hello [display_name],<br /><br />Thank you for your Purchase of Subscription: [pack_label].<br />Your subscription has started and is valid until: [pack_expire]<br /><br />[blogname]', 'ym');
		$this->payment_gateway_message_subscription_failed = __('Hello [display_name],<br /><br />Thank you for your Purchase of Subscription: [pack_label].<br />The transaction is incomplete or has failed, if you believe this is in error, Please contact Us<br /><br />[blogname]', 'ym');

		$this->payment_gateway_enable_post_success = TRUE;
		$this->payment_gateway_enable_post_failed = TRUE;
		$this->payment_gateway_enable_ppack_success = TRUE;
		$this->payment_gateway_enable_ppack_failed = TRUE;
		$this->payment_gateway_enable_subscription_success = TRUE;
		$this->payment_gateway_enable_subscription_failed = TRUE;

		$this->payment_gateway_email_post_success = '';
		$this->payment_gateway_email_post_failed = '';
		$this->payment_gateway_email_ppack_success = '';
		$this->payment_gateway_email_ppack_failed = '';
		$this->payment_gateway_email_subscription_success = '';
		$this->payment_gateway_email_subscription_failed = '';

		$this->purchasable_bundle_at_limit = __('Only a limited number of this bundle were available. They have now all been bought, keep an eye on this page in case the quota is lifted', 'ym');

		$this->checkemail_subscribed = __('Subscription complete. You may now Login.', 'ym');
		$this->checkemail_bundle = __('Bundle Purchase complete. Please Login to continue.', 'ym');
		$this->checkemail_post = __('Post Purchase complete. Please Login to continue.', 'ym');
		$this->checkemail_loginneeded = __('Please Login to Continue', 'ym');
		$this->checkemail_noaccess = __('You cannot access this content', 'ym');

		$this->all_content_not_logged_in = __('To see all Purchasable Content, you need to be logged in', 'ym');
		$this->all_bundles_not_logged_in = __('To see all Purchasable Bundles, you need to be logged in', 'ym');
		$this->featured_content_not_logged_in = __('To see all Featured Content, you need to be logged in', 'ym');
		$this->featured_bundles_not_logged_in = __('To see all Featured Bundles, you need to be logged in', 'ym');

		$this->registration_flow_email_invalid = __('The Email Address is invalid', 'ym');
		$this->registration_flow_email_inuse = __('That Email Address is already in use', 'ym');
		$this->registration_flow_username_inuse = __('That Username is already in use', 'ym');
		$this->registration_flow_required_fields = __('Please fill in the required fields', 'ym');
		$this->registration_flow_invalid_coupon = __('The coupon is invalid, or has reached its usage limit', 'ym');
		$this->registration_flow_invalid_password = __('The password is invalid, or does not match', 'ym');


		// don't overwrite
		add_option('ym_res', $this);
	}
}
