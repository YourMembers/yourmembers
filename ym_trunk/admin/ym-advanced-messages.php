<?php

/*
* $Id: ym-advanced-messages.php 2444 2012-11-29 16:06:15Z bcarlyon $
* $Revision: 2444 $
* $Date: 2012-11-29 16:06:15 +0000 (Thu, 29 Nov 2012) $
* Payment Gateway base file
*/

global $ym_formgen, $ym_res, $ym_sys;

if (ym_post('msgs_update')) {
	$ym_res->update_from_post();

	ym_display_message(__('Messages Saved','ym'));

	do_action('ym_login_messages_extra_messages_save');
}

?>
<div class="wrap" id="poststuff">
<form action="" method="post">

<?php

echo '
<div id="ym_messages">
<ul>
	<li><a href="#ym_main_messages">' . __('Main Messages', 'ym') . '</a></li>
	<li><a href="#ym_login_messages">' . __('Login Messages', 'ym') . '</a></li>
	<li><a href="#ym_post_messages">' . __('Post Messages', 'ym') . '</a></li>
	<li><a href="#ym_cart_messages">' . __('Cart Style Messages', 'ym') . '</a></li>
	<li><a href="#ym_login_error_messages">' . __('Login Error Messages', 'ym') . '</a></li>
	<li><a href="#ym_regflow_error_messages">' . __('Reg. Flow Error Messages', 'ym') . '</a></li>
	<li><a href="#ym_templates">' . __('Templates', 'ym') . '</a></li>
</ul>
';
	echo '<div id="ym_main_messages">';
	echo ym_start_box(__('Main Messages', 'ym'));
	
	?>
		<table class="form-table">
			<?php
			$ym_formgen->render_form_table_text_row(__('Unbought Post Text Message', 'ym'), 'ym_ppp_none_msg', $ym_res->ym_ppp_none_msg, __('Text to use when a post can be bought', 'ym'));

			$ym_formgen->render_form_table_textarea_row(__('Subscription Introduction', 'ym'), 'subs_intro', $ym_res->subs_intro, __('Text that appears before the subscription options', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('Terms &amp; Conditions', 'ym'), 'tos', $ym_res->tos, __('Terms &amp; Conditions text that appears on registration screen, if user did not agree to this he can not register.', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('User Self Unsubscribe Message', 'ym'), 'unsubscribe_left_msg', $ym_res->unsubscribe_left_msg, __('The message displayed to a user, when using [ym_user_unsubscribe]', 'ym'));
			?>
		</table>
		
	<?php
	echo ym_end_box();
	echo '</div><div id="ym_login_messages">';
	echo ym_start_box(__('Login Messages', 'ym'));
		echo '<p>' . __('On Completion of a Purchase, or blocked access, a user may be directed to the Login Form and shown one of these messages', 'ym') . '</p>'
	?>
		<table class="form-table">
			<?php
			$ym_formgen->render_form_table_text_row('checkemail=subscribed', 'checkemail_subscribed', $ym_res->checkemail_subscribed, __('User Upgrades or Subscribes', 'ym'));
			$ym_formgen->render_form_table_text_row('checkemail=bundle', 'checkemail_bundle', $ym_res->checkemail_bundle, __('User Buys a Bundle', 'ym'));
			$ym_formgen->render_form_table_text_row('checkemail=post', 'checkemail_post', $ym_res->checkemail_post, __('User Buys a Post', 'ym'));

			$ym_formgen->render_form_table_text_row('checkemail=loginneeded', 'checkemail_loginneeded', $ym_res->checkemail_loginneeded, __('User is directed to login', 'ym'));
			$ym_formgen->render_form_table_text_row('checkemail=noaccess', 'checkemail_noaccess', $ym_res->checkemail_noaccess, __('User cannot access a post/page', 'ym'));
			
			do_action('ym_login_messages_extra_messages');

			?>
		</table>

	<?php
	echo ym_end_box();
	echo '</div><div id="ym_post_messages">';
	echo ym_start_box(__('Post Messages', 'ym'));
	?>

		<div><?php _e('You can use a number of tags within the messages in this section. HTML can be used within these messages. The tags are as follows:', 'ym') ?>
		<ul>
			<li>[[purchase_cost]] = <?php _e('Cost and currency of a purchasable post', 'ym'); ?></li>
			<li>[[login_register]] = <?php _e('Login or register form', 'ym'); ?></li>
			<li>[[login_register_links]] = <?php _e('Links for login and register', 'ym'); ?></li>
			<li>[[login_link]] = <?php _e('Login link only', 'ym'); ?></li>
			<li>[[register_link]] = <?php _e('Register link only', 'ym'); ?></li>
			<li>[[account_types]] = <?php _e('A list of membership levels that can see this post/page', 'ym'); ?></li>
			<li>[[duration]] = <?php _e('The number of days that the user will have access to the content for', 'ym'); ?></li>
			<li>[[this_page]] = <?php _e('This page URL for redirect_to and URL contruction', 'ym'); ?></li>
		</ul>
		</div>
		
		<table class="form-table">
			<?php
			$ym_formgen->render_form_table_textarea_row(__('Message Header', 'ym'), 'msg_header', $ym_res->msg_header, __('This is added before all of the messages below and can be used for encapsulating HTML or prefixed content in each message', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('Message Footer', 'ym'), 'msg_footer', $ym_res->msg_footer, __('This is added after all of the messages below and can be used for encapsulating HTML or content after each message', 'ym'));
			
			$ym_formgen->render_form_table_textarea_row(__('Message to replace private parts of a post', 'ym'), 'private_text', $ym_res->private_text);
			$ym_formgen->render_form_table_textarea_row(__('Message to replace private parts of a post when user logged in but is not allowed to see the rest of the post', 'ym'), 'no_access', $ym_res->no_access);
			$ym_formgen->render_form_table_textarea_row(__('Message to replace private parts of a purchasable post', 'ym'), 'private_text_purchasable', $ym_res->private_text_purchasable);
			$ym_formgen->render_form_table_textarea_row(__('Message to replace private parts of a purchasable post when the purchase limit is reached', 'ym'), 'purchasable_at_limit', $ym_res->purchasable_at_limit);
			$ym_formgen->render_form_table_textarea_row(__('Message to replace private parts of a purchasable post when the user isn\'t logged in', 'ym'), 'login_first_text', $ym_res->login_first_text);
			$ym_formgen->render_form_table_textarea_row(__('Message to replace private parts of a purchasable post when the post is part of a pack ONLY (ie: price set to 0)', 'ym'), 'purchasable_pack_only', $ym_res->purchasable_pack_only);
			
			$ym_formgen->render_form_table_textarea_row(__('Message divider to be shown on the membership details page between the "My Purchased Posts" and "Premium Content" sections', 'ym'), 'members_content_divider_html', $ym_res->members_content_divider_html);
			$ym_formgen->render_form_table_textarea_row(__('Message divider to be shown on the membership details page between the "Premium Content" and "My Sold Posts" sections', 'ym'), 'members_content_divider2_html', $ym_res->members_content_divider2_html);

			$ym_formgen->render_form_table_textarea_row(__('Message to show when a bundle when the purchase limit is reached', 'ym'), 'purchasable_at_limit', $ym_res->purchasable_bundle_at_limit);
			?>
		</table>

<?php
	echo ym_end_box();
	echo '</div><div id="ym_login_error_messages">';
	echo ym_start_box(__('Error Messages', 'ym'));
?>

		<p class="desc"><?php _e('In the below fields you can configure error messages that appear to users on some steps. If you want to show some user username just use <strong>&quot;[[USERNAME]]&quot;</strong> without the quotes and it will be replaced automatically by username.','ym') ?></p>
		
		<table class="form-table">
			<?php
			$ym_formgen->render_form_table_textarea_row(__('Login error message for Inactive accounts', 'ym'), 'login_errmsg_null', $ym_res->login_errmsg_null, __('Error message that appears when login fails because the user has not subscribed yet, or his/her account is inactive for another reason', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('Login error message for Expired accounts', 'ym'), 'login_errmsg_expired', $ym_res->login_errmsg_expired, __('Error message that appears when login fails because the user\'s subscription has expired', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('Login error message for Expired TRIAL accounts', 'ym'), 'login_errmsg_trial_expired', $ym_res->login_errmsg_trial_expired, __('Error message that appears when login fails because the user\'s trial account has expired', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('Login error message for Pending accounts', 'ym'), 'login_errmsg_pending', $ym_res->login_errmsg_pending, __('Error message that appears when login fails because the user\'s subscription payment is pending', 'ym'));

			$ym_formgen->render_form_table_textarea_row(__('Login error message for Group Leader Expired', 'ym'), 'login_errmsg_parent_expired', $ym_res->login_errmsg_parent_expired, __('Error message that appears when login fails because the group leader has expired', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('Login error message for Group Leader Cancelled', 'ym'), 'login_errmsg_parent_cancel', $ym_res->login_errmsg_parent_cancel, __('Error message that appears when login fails because the group leader has blocked the child account', 'ym'));
			$ym_formgen->render_form_table_textarea_row(__('Login error message for Child Account needs Configuration', 'ym'), 'login_errmsg_parent_config', $ym_res->login_errmsg_parent_config, __('Error message that appears when login fails because the child account has no Package/Package Type Applied', 'ym'));

			$ym_formgen->render_form_table_textarea_row(__('Login error message for unexpected reason', 'ym'), 'login_errmsg_default', $ym_res->login_errmsg_default, __('Error message that appears when login fails for an unexpected reason. This will never show up if all systems work as designed', 'ym'));
			?>
		</table>
<?php
	echo ym_end_box();
	echo '</div><div id="ym_regflow_error_messages">';
	echo ym_start_box(__('Registration Flow Error Messages', 'ym'));
?>
		<p class="desc"><?php _e('Registration Flow Error Messages','ym') ?></p>
		
		<table class="form-table">
			<?php
			$ym_formgen->render_form_table_textarea_row(__('Invalid Email Address', 'ym'), 'registration_flow_email_invalid', $ym_res->registration_flow_email_invalid);
			$ym_formgen->render_form_table_textarea_row(__('Email Address in Use', 'ym'), 'registration_flow_email_inuse', $ym_res->registration_flow_email_inuse);
			$ym_formgen->render_form_table_textarea_row(__('Username in Use', 'ym'), 'registration_flow_username_inuse', $ym_res->registration_flow_username_inuse);
			$ym_formgen->render_form_table_textarea_row(__('Required Fields are Blank', 'ym'), 'registration_flow_required_fields', $ym_res->registration_flow_required_fields);
			$ym_formgen->render_form_table_textarea_row(__('Invalid Coupon', 'ym'), 'registration_flow_invalid_coupon', $ym_res->registration_flow_invalid_coupon);
			?>
		</table>

<?php
	echo ym_end_box();
	echo '</div><div id="ym_cart_messages">';
	echo ym_start_box(__('Cart Style Messages', 'ym'));
?>		

		<p class="desc"><?php _e('These messages apply to the [ym_all_content], [ym_all_bundles], and [ym_featured_content] shortcodes','ym') ?></p>
		<p>Supports:
		<ul>
			<li>[[purchase_cost]] = <?php _e('Cost and currency of a purchasable post', 'ym'); ?></li>
			<li>[[login_register]] = <?php _e('Login or register form', 'ym'); ?></li>
			<li>[[login_register_links]] = <?php _e('Links for login and register', 'ym'); ?></li>
			<li>[[login_link]] = <?php _e('Login link only', 'ym'); ?></li>
			<li>[[register_link]] = <?php _e('Register link only', 'ym'); ?></li>
			<li>[[account_types]] = <?php _e('A list of membership levels that can see this post/page', 'ym'); ?></li>
			<li>[[duration]] = <?php _e('The number of days that the user will have access to the content for', 'ym'); ?></li>
			<li>[[this_page]] = <?php _e('This page URL for redirect_to and URL contruction', 'ym'); ?>
		</ul>
		
		<table class="form-table">
			<?php
			$ym_formgen->render_form_table_textarea_row(__('Message to show to a Non Logged in User (all content)', 'ym'), 'all_content_not_logged_in', $ym_res->all_content_not_logged_in);
			$ym_formgen->render_form_table_textarea_row(__('Message to show to a Non Logged in User (all bundles)', 'ym'), 'all_bundles_not_logged_in', $ym_res->all_bundles_not_logged_in);
			$ym_formgen->render_form_table_textarea_row(__('Message to show to a Non Logged in User (featured content)', 'ym'), 'featured_content_not_logged_in', $ym_res->featured_content_not_logged_in);
			//$ym_formgen->render_form_table_textarea_row(__('Message to show to a Non Logged in User (featured bundles)', 'ym'), 'featured_bundles_not_logged_in', $ym_res->featured_bundles_not_logged_in);
			?>
		</table>
<?php
	echo ym_end_box();
	echo '</div><div id="ym_templates">';
	echo ym_start_box(__('Templates', 'ym'));
?>		

		<table class="form-table">
			<?php
			$ym_formgen->render_form_table_textarea_row(__('Membership Pack Description Template', 'ym'), 'pack_string_template', $ym_res->pack_string_template, __('When the packs are shown to the user they are placed in a certain format (eg: Member - 5 USD per 3 Months), this allows you to change it using any or all of the following hooks: [account_type], [cost], [cost_units] (the cost without pence/cents so if the cost is 5.00 this shows 5 only), [currency], [duration], [duration_period]. If your membership packs are a recurring payment and you have limited the number then you can use [num_cycles] below to indicate the number of payments. If you would like to use a Paypal trial then indicate this in the string using [trial_cost] or [trial_cost_units] (no pence/cents), [trial_duration], [trial_duration_period] [description]. Encapsulate any trial specific parts of the string in [if_trial_on][/if_trial_on] and for those that arent using a trial it\'s contents will be removed', 'ym'));
			?>
		</table>			
		
<?php
	echo ym_end_box();
	echo '</div>
</div>';
?>		

<p class="submit" style="float: right;"><input type="submit" name="msgs_update" class="button-primary" value="<?php _e('Save Messages','ym'); ?> &raquo;" /></p>

</form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#ym_messages').tabs();
	});
</script>
