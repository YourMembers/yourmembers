<?php

/*
* $Id: ym-advanced-payment_gateway_email.php 2297 2012-08-09 13:30:50Z bcarlyon $
* $Revision: 2297 $
* $Date: 2012-08-09 14:30:50 +0100 (Thu, 09 Aug 2012) $
*/

global $ym_formgen, $ym_res, $ym_sys;
if (ym_post('msgs_update')) {
	$ym_res->update_from_post();

	ym_display_message(__('Messages Saved','ym'));

	do_action('ym-advanced-payment_gateway_email_save');
}

?>
<div class="wrap" id="poststuff">
<form action="" method="post">

<div id="ym_messages">
<ul>
	<li><a href="#ym_payment_gateway_base"><?php echo __('Payment Gateway Base Emails', 'ym'); ?></a></li>
	<?php

do_action('ym-advanced-payment_gateway_email_tabs');

	?>
	<li><a href="#ym_payment_gateway_copy_to"><?php echo __('Payment Gateway Base Emails CC/BCC', 'ym'); ?></a></li>
</ul>

<?php
	echo '<div id="ym_payment_gateway_base">';

	echo ym_start_box(__('Available Shortcodes', 'ym'));
	echo '<ul>';
	echo '<li>' . __('Subjects Supports [blogname], and [pack_label] for Packages or [post_title] for Posts or [pack_title] for Bundles', 'ym') . '</li>';
	echo '<li>' . __('Message Body Supports [display_name], [pack_label], [blogname], [ym_log_id]', 'ym') . '</li>';
	echo '<li>' . __('Message Body Success supports [pack_expire]', 'ym') . '</li>';
	echo '</ul>';
	echo ym_end_box();

	echo ym_start_box(__('Post Purchase Receipt', 'ym'));
	echo '<table class="form-table">';

	$ym_formgen->render_form_table_radio_row(__('Enable Post Purchase Success Receipt', 'ym'), 'payment_gateway_enable_post_success', $ym_res->payment_gateway_enable_post_success);
	$ym_formgen->render_form_table_text_row(__('Subject for Post Purchase Success', 'ym'), 'payment_gateway_subject_post_success', $ym_res->payment_gateway_subject_post_success);
	$ym_formgen->render_form_table_wp_editor_row(__('Post Purchase Success', 'ym'), 'payment_gateway_message_post_success', $ym_res->payment_gateway_message_post_success, __('Sent to User on Successful Post Purchase', 'ym'));

	$ym_formgen->render_form_table_radio_row(__('Enable Post Purchase Failed Receipt', 'ym'), 'payment_gateway_enable_post_failed', $ym_res->payment_gateway_enable_post_failed);
	$ym_formgen->render_form_table_text_row(__('Subject for Post Purchase Failed', 'ym'), 'payment_gateway_subject_post_failed', $ym_res->payment_gateway_subject_post_failed);
	$ym_formgen->render_form_table_wp_editor_row(__('Post Purchase Failed', 'ym'), 'payment_gateway_message_post_failed', $ym_res->payment_gateway_message_post_failed, __('Sent to User on Post Purchase Failed', 'ym'));

	echo '</table>';
	echo ym_end_box();
	echo ym_start_box(__('Bundle Purchase Receipt', 'ym'));
	echo '<table class="form-table">';



	$ym_formgen->render_form_table_radio_row(__('Enable Bundle Purchase Success Receipt', 'ym'), 'payment_gateway_enable_ppack_success', $ym_res->payment_gateway_enable_ppack_success);
	$ym_formgen->render_form_table_text_row(__('Subject for Bundle Purchase Success', 'ym'), 'payment_gateway_subject_ppack_success', $ym_res->payment_gateway_subject_ppack_success);
	$ym_formgen->render_form_table_wp_editor_row(__('Bundle Purchase Success', 'ym'), 'payment_gateway_message_ppack_success', $ym_res->payment_gateway_message_ppack_success, __('Sent to User on Successful Bundle Purchase', 'ym'));

	$ym_formgen->render_form_table_radio_row(__('Enable Bundle Purchase Failed Receipt', 'ym'), 'payment_gateway_enable_ppack_failed', $ym_res->payment_gateway_enable_ppack_failed);
	$ym_formgen->render_form_table_text_row(__('Subject for Bundle Purchase Failed', 'ym'), 'payment_gateway_subject_ppack_failed', $ym_res->payment_gateway_subject_ppack_failed);
	$ym_formgen->render_form_table_wp_editor_row(__('Bundle Purchase Failed', 'ym'), 'payment_gateway_message_ppack_failed', $ym_res->payment_gateway_message_ppack_failed, __('Sent to User on Bundle Purchase Failed', 'ym'));

	echo '</table>';
	echo ym_end_box();
	echo ym_start_box(__('Subscription Purchase Receipt', 'ym'));
	echo '<table class="form-table">';


	$ym_formgen->render_form_table_radio_row(__('Enable Subscription Purchase Success Receipt', 'ym'), 'payment_gateway_enable_subscription_success', $ym_res->payment_gateway_enable_subscription_success);
	$ym_formgen->render_form_table_text_row(__('Subject for Subscription Purchase Success', 'ym'), 'payment_gateway_subject_subscription_success', $ym_res->payment_gateway_subject_subscription_success);
	$ym_formgen->render_form_table_wp_editor_row(__('Subscription Purchase Success', 'ym'), 'payment_gateway_message_subscription_success', $ym_res->payment_gateway_message_subscription_success, __('Sent to User on Successful Bundle Purchase', 'ym'));

	$ym_formgen->render_form_table_radio_row(__('Enable Subscription Purchase Failed Receipt', 'ym'), 'payment_gateway_enable_subscription_failed', $ym_res->payment_gateway_enable_subscription_failed);
	$ym_formgen->render_form_table_text_row(__('Subject for Subscription Purchase Failed', 'ym'), 'payment_gateway_subject_subscription_failed', $ym_res->payment_gateway_subject_subscription_failed);
	$ym_formgen->render_form_table_wp_editor_row(__('Subscription Purchase Failed', 'ym'), 'payment_gateway_message_subscription_failed', $ym_res->payment_gateway_message_subscription_failed, __('Sent to User on Bundle Purchase Failed', 'ym'));

	echo '</table>';
	echo ym_end_box();
	echo '</div>';

	do_action('ym-advanced-payment_gateway_email_tab_content');

	echo '<div id="ym_payment_gateway_copy_to">';
	echo '<p>' . __('When an Payment Gateway Receipt Email is sent you can copy the email to another (or more) email address(es)', 'ym') . '</p>';
	echo '<p>' . __('If an address is specied and the relevant email is disabled, the CC/BCC will still execute', 'ym') . '</p>';

	echo '<table>';
	foreach ($ym_res->payment_gateway_email_post_success as $email) {
		if ($email) {
			$ym_formgen->render_form_table_email_row(__('Post Purchase Success Include', 'ym'), 'payment_gateway_email_post_success[]', $email);
		}
	}
	$ym_formgen->render_form_table_email_row(__('Post Purchase Success Include', 'ym'), 'payment_gateway_email_post_success[]', '');
	foreach ($ym_res->payment_gateway_email_post_failed as $email) {
		if ($email) {
			$ym_formgen->render_form_table_email_row(__('Post Purchase Failed Include', 'ym'), 'payment_gateway_email_post_failed[]', $email);
		}
	}
	$ym_formgen->render_form_table_email_row(__('Post Purchase Failed Include', 'ym'), 'payment_gateway_email_post_failed[]', '');
	echo '</table>';

	echo '<hr />';

	echo '<table>';
	foreach ($ym_res->payment_gateway_email_ppack_success as $email) {
		if ($email) {
			$ym_formgen->render_form_table_email_row(__('Bundle Purchase Success Include', 'ym'), 'payment_gateway_email_ppack_success[]', $email);
		}
	}
	$ym_formgen->render_form_table_email_row(__('Bundle Purchase Success Include', 'ym'), 'payment_gateway_email_ppack_success[]', '', '');
	foreach ($ym_res->payment_gateway_email_ppack_failed as $email) {
		if ($email) {
			$ym_formgen->render_form_table_email_row(__('Bundle Purchase Failed Include', 'ym'), 'payment_gateway_email_ppack_failed[]', $email);
		}
	}
	$ym_formgen->render_form_table_email_row(__('Bundle Purchase Failed Include', 'ym'), 'payment_gateway_email_ppack_failed[]', '');
	echo '</table>';

	echo '<hr />';

	echo '<table>';
	foreach ($ym_res->payment_gateway_email_subscription_success as $email) {
		if ($email) {
			$ym_formgen->render_form_table_email_row(__('Subscription Purchase Success Include', 'ym'), 'payment_gateway_email_subscription_success[]', $email);
		}
	}
	$ym_formgen->render_form_table_email_row(__('Subscription Purchase Success Include', 'ym'), 'payment_gateway_email_subscription_success[]', '');
	foreach ($ym_res->payment_gateway_email_subscription_failed as $email) {
		if ($email) {
			$ym_formgen->render_form_table_email_row(__('Subscription Purchase Failed Include', 'ym'), 'payment_gateway_email_subscription_failed[]', $email);
		}
	}
	$ym_formgen->render_form_table_email_row(__('Subscription Purchase Failed Include', 'ym'), 'payment_gateway_email_subscription_failed[]', '');
	echo '</table>';

	echo '</div>';
	echo '</div>';
?>

<p class="submit"><input type="submit" class="button-primary" style="float: right;" name="msgs_update" value="<?php _e('Save Message Settings','ym'); ?>" /></p>

</form>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#ym_messages').tabs();
	});
</script>

