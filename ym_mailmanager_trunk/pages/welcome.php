<?php

global $ym_formgen;

$current_welcome = get_option('ym_other_mm_welcome');

if ($_POST) {
	$defaults = array(
        'welcome_subject' => '[blogname] Your Username and Password',
        'welcome_message' => 'Welcome to [blogname],
<br /><br />You can now Sign at [loginurl] using:
<br />
<br />Login: [login]
<br />Password: [password]
<br /><br />Thanks
<br />[blogname]
<br />[blogurl]
'
	);
	$postarr = wp_parse_args($_POST, $defaults);
	$postarr = sanitize_post($postarr, 'db');

	// export array as variables
	extract($postarr, EXTR_SKIP);

	$current_welcome->enable = ym_post('enable');
<<<<<<< .mine
	$current_welcome->subject = ym_post('welcome_subject');
	$current_welcome->message = nl2br(stripslashes(ym_post('welcome_message',,'<br><strong><em>')));
=======
	$current_welcome->subject = $welcome_subject;
	$current_welcome->message = $welcome_message;
>>>>>>> .r2601
	
	update_option('ym_other_mm_welcome', $current_welcome);
	
	ym_box_top(__('Welcome Message was updated', 'ym_mailmanager'));
	echo '<p>' . __('Welcome Message was updated', 'ym_mailmanager');
	ym_box_bottom();
}

echo '<form action="" method="post">';

ym_box_top(__('Welcome Message Settings', 'ym_mailmanager'));

echo '<p>' . __('You can Specify a personal welcome message to replace the User Welcome/Password Email', 'ym_mailmanager') . '</p>';

echo '<form action="" method="post">';
echo '<table class="form-table">';

$current_welcome->enable = $current_welcome->enable ? $current_welcome->enable : 0;
$current_welcome->subject = $current_welcome->subject ? $current_welcome->subject : '[blogname] Your Username and Password';
$current_welcome->message = $current_welcome->message ? $current_welcome->message : "Welcome to [blogname],
<br /><br />You can now Sign at [loginurl] using:
<br />
<br />Login: [login]
<br />Password: [password]
<br /><br />Thanks
<br />[blogname]
<br />[blogurl]
";

echo $ym_formgen->render_form_table_radio_row(__('Enable This Welcome Message', 'ym_mailmanager'), 'enable', $current_welcome->enable);
echo $ym_formgen->render_form_table_text_row(__('Welcome Subject', 'ym_mailmanager'), 'welcome_subject', $current_welcome->subject, __('You can use the [blogname] and [blogurl] short codes here', 'ym_mailmanager'));
$ym_formgen->render_form_table_wp_editor_row(__('Welcome Message', 'ym_mailmanager'), 'welcome_message', $current_welcome->message, __('Message to Send, you can use HTML. You need to include the [login] and [password] short tags, other wise the New User will not be able to log in! You can use [blogname], [blogurl], [loginurl]. You can use [ym_mm_custom_field field=""] [ym_mm_if_custom_field field=""] where the "" is a Custom Profile Field', 'ym_mailmanager'));
echo '</table>';

echo '<p class="submit" style="text-align: right;"><input type="submit" value="' . __('Update', 'ym_mailmanager') . '" /></p>';

echo '</form>';

ym_box_bottom();
