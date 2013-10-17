<?php

$data = get_option('ym_mm_invoicing');

$fields = array(
	'subscription_enabled',
	'subscription_subject',
	'subscription_message',
	'post_enabled',
	'post_subject',
	'post_message',
	'bundle_enabled',
	'bundle_subject',
	'bundle_message'
);

if ($_POST) {
	// updating
	foreach ($fields as $field) {
		if (strpos($field, 'enabled')) {
			$data->$field = ym_post($field);
		} else {
			$data->$field = nl2br(stripslashes(ym_post($field)));
		}
	}
	
	update_option('ym_mm_invoicing', $data);
	
	echo '<div id="message" class="updated"><p>' . __('Updated Invoicing Emails', 'ym_mailmanager') . '</p></div>';
}

foreach ($fields as $field) {
	if (strpos($field, 'enabled')) {
		if (!isset($data->$field)) {
			$data->$field = 0;
		}
	}
}

global $ym_formgen;

ym_box_top(__('Invoicing', 'ym_mailmanager'));

echo '<p>' . __('You can configure invoice style emails to be sent to a customer when a purchase is completed', 'ym_mailmanager') . '</p>';
echo '<p>' . __('Each email type allows you to use HTML in the body and the following short codes may be used in the subject or body:', 'ym_mailmanager') . '</p>';
echo '<ul>';
echo '<li>' . __('[blogname] for the name of the blog', 'ym_mailmanager') . '</li>';
echo '<li>' . __('[blogurl] for the url to the blog', 'ym_mailmanager') . '</li>';
echo '<li>' . __('[loginurl] for the url to login to the blog', 'ym_mailmanager') . '</li>';
echo '<li>' . __('You can use [ym_mm_custom_field field=""] [ym_mm_if_custom_field field=""]content[/ym_mm_if_custom_field] where the "" is a Custom Profile Field', 'ym_mailmanager') . '</p>';
echo '<li>' . __('[ym_mm_type] which is replaced with the Type of the item bought, Post/Subscription/Bundle', 'ym_mailmanager') . '</li>';
echo '<li>' . __('[ym_mm_title] which is replaced with the Name of the item bought, or the post title', 'ym_mailmanager') . '</li>';
echo '<li>' . __('[ym_mm_cost] which is replaced with the Amount Paid for the item', 'ym_mailmanager') . '</li>';
echo '</ul>';

ym_box_bottom();
echo '<div id="ym_mm_invoice_tabs">
<ul>
	<li><a href="#subscription">' . __('Subscription', 'ym_mailmanager') . '</a></li>
	<li><a href="#post">' . __('Post', 'ym_mailmanager') . '</a></li>
	<li><a href="#bundle">' . __('Bundle', 'ym_bundle') . '</a></li>
</ul>
';

echo '<div>';
echo '<form action="" method="post">';

echo '<div id="subscription">';
ym_box_top(__('Subscription Purchase Complete', 'ym_mailmanager'));

echo '<table class="form-table">';
echo $ym_formgen->render_form_table_radio_row(__('Enable This Message', 'ym_mailmanager'), 'subscription_enabled', $data->subscription_enabled);
echo $ym_formgen->render_form_table_text_row(__('Email Subject', 'ym_mailmanager'), 'subscription_subject', $data->subscription_subject, __('Subject of Message', 'ym_mailmanager'));
$ym_formgen->render_form_table_wp_editor_row(__('Email Message', 'ym_mailmanager'), 'subscription_message', $data->subscription_message);
echo '</table>';

ym_box_bottom();

echo '</div><div id="post">';

ym_box_top(__('Post Purchase Complete', 'ym_mailmanager'));

echo '<table class="form-table">';
echo $ym_formgen->render_form_table_radio_row(__('Enable This Message', 'ym_mailmanager'), 'post_enabled', $data->post_enabled);
echo $ym_formgen->render_form_table_text_row(__('Email Subject', 'ym_mailmanager'), 'post_subject', $data->post_subject, __('Subject of Message', 'ym_mailmanager'));
$ym_formgen->render_form_table_wp_editor_row(__('Email Message', 'ym_mailmanager'), 'post_message', $data->post_message);
echo '</table>';

ym_box_bottom();

echo '</div><div id="bundle">';

ym_box_top(__('Bundle Purchase Complete', 'ym_mailmanager'));

echo '<table class="form-table">';
echo $ym_formgen->render_form_table_radio_row(__('Enable This Message', 'ym_mailmanager'), 'bundle_enabled', $data->bundle_enabled);
echo $ym_formgen->render_form_table_text_row(__('Email Subject', 'ym_mailmanager'), 'bundle_subject', $data->bundle_subject, __('Subject of Message', 'ym_mailmanager'));
$ym_formgen->render_form_table_wp_editor_row(__('Email Message', 'ym_mailmanager'), 'bundle_message', $data->bundle_message);
echo '</table>';

ym_box_bottom();

echo '</div>
<p class="submit" style="text-align: right;"><input type="submit" name="submit" value="' . __('Update All Messages', 'ym_mailmanager') . '" /></p>
</form>
</div>';

echo '

<script type="text/javascript">
	jQuery(document).ready(function() {
		setTimeout(\'tabulate()\', 500);
	});
	function tabulate() {
		jQuery(\'#ym_mm_invoice_tabs\').tabs();
	}
</script>
';
