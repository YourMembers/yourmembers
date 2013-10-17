<?php

function ym_fbook_admin() {
	global $wpdb, $ym_formgen, $facebook_settings;
	
	include(YM_FBOOK_BASE_DIR . 'includes/ym_facebook_constants.php');
	
	ym_facebook_settings(TRUE);
	$pricing_data = get_option('ym_fbook_pricing');
	
	if ($_POST) {
		foreach ($settings as $setting) {
			$facebook_settings->$setting = $_POST[$setting];
		}
		
		// images
		foreach ($images as $image) {
			if (is_uploaded_file($_FILES[$image]['tmp_name'])) {
				$file = $_FILES[$image];

				$ym_upload = new ym_dl_file_upload;
				$ym_upload->upload_dir = $ym_upload_root;
				$ym_upload->max_length_filename = 100;
				$ym_upload->rename_file = false;

				$ym_upload->the_temp_file = $file['tmp_name'];
				$ym_upload->the_file = $file['name'];
				$ym_upload->http_error = $file['error'];
				$ym_upload->replace = "y";
				$ym_upload->do_filename_check = "n";

				if ($ym_upload->upload()) {
					$filename = $ym_upload_url . $ym_upload->file_copy;
					$facebook_settings->$image = $filename;
				}
				else {
					ym_display_message(sprintf(__('unable to move file to %s','ym'), $ym_upload->upload_dir), 'error');
				}
			}
		}
		
		update_option('ym_fbook_options', $facebook_settings);
		echo '<div id="message" class="updated fade"><p>Settings were updated</p></div>';
		
		$packs = ym_get_packs();
		foreach ($packs as $pack) {
			$id = 'pack_' . $pack['id'];
			$post = 'override_price_' . $id;
			
			$price = ym_post($post);
			if ($price) {
				$price = number_format((float)$price, 0);
			}
			$pricing_data->$id = $price;
		}
		$query = 'SELECT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = \'ym_post_purchasable\' AND meta_value = 1';
		foreach ($wpdb->get_results($query) as $post) {
			$id = 'post_' . $post->post_id;
			$post = 'override_price_' . $id;

			$price = ym_post($post);
			if ($price) {
				$price = number_format((float)$price, 0);
			}
			$pricing_data->$id = $price;
		}
		$query = 'SELECT id, name FROM ' . $wpdb->prefix . 'ym_post_pack ORDER BY id ASC';
		foreach ($wpdb->get_results($query) as $bundle) {
			$id = 'bundle_' . $bundle->id;
			$post = 'override_price_' . $id;

			$price = ym_post($post);
			if ($price) {
				$price = number_format((float)$price, 0);
			}
			$pricing_data->$id = $price;
		}
		$price = ym_post('override_price_post_override');
		if ($price) {
			$price = number_format((float)$price, 0);
		}
		$pricing_data->post_override = $price;
		
		$price = ym_post('override_price_bundle_override');
		if ($price) {
			$price = number_format((float)$price, 0);
		}
		$pricing_data->bundle_override = $price;
		
		update_option('ym_fbook_pricing', $pricing_data);
		echo '<div id="message" class="updated fade"><p>Pricings were updated</p></div>';
	}
	
	echo '
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery(\'#ym_fbook_tabs\').tabs({
			fx: {opacity: \'toggle\'},
			selected: ' . ym_post('ym_fb_tab_select', ym_get('ym_fb_tab_select', 0)) . '
		});
		jQuery(\'.subtabs\').tabs({
			fx: {opacity: \'toggle\'}
		});
		jQuery(\'#tabkiller\').click(function() {
			jQuery(this).hide();
			jQuery(\'.subtabs\').slideUp(function() {
				jQuery(\'.subtabs\').tabs(\'destroy\');
				jQuery(\'.subtabs ul\').hide();
				jQuery(\'.subtabs\').slideDown();
			});
			jQuery(\'#ym_fbook_tabs\').slideUp(function() {
				jQuery(\'#ym_fbook_tabs\').tabs(\'destroy\');
				jQuery(\'#ym_fbook_tabs ul\').hide();
				jQuery(\'#transaction_logging\').hide();
				jQuery(\'#ym_fbook_tabs\').slideDown();
			});
		});
		jQuery(\'#ym_fb\').submit(function() {
			var selected = jQuery(\'#ym_fbook_tabs\').tabs(\'option\', \'selected\');
			jQuery(\'#ym_fb_tab_select\').val(selected);
		});
		jQuery(\'table\').after(\'<p class="submit" style="text-align: right;"><input type="submit" value="Save Settings" /></p>\');
	});
</script>
';
	
	echo '<div class="wrap" id="poststuff">';
//	echo '<h2>YourMembers in Facebook | Settings</h2>';
	
//	echo '<p style="text-align: right;"><a href="#nowhere" id="tabkiller">Remove Tabs/All Settings on a single page</a></p>';
	
	echo '<div id="ym_fbook_tabs">';
	echo '<form action="" method="post" enctype="multipart/form-=data" id="ym_fb">';
	
	$credits = FALSE;
	global $ym_active_modules;
	if (in_array('ym_facebook_credits', $ym_active_modules)) {
//	if (get_option('ym_facebook_credits')) {
		$credits = TRUE;
	}

	echo '
<ul>
	<li><a href="#guide">Guide</a></li>
	<li><a href="#facebook_settings">Facebook</a></li>
	<li><a href="#settings_settings">Settings</a></li>
	<li><a href="';
	
	if ($credits) {
		echo '#facebook_credits';
	}
	
	echo '">Facebook Credits</a></li>
	<li><a href="';
	
	if ($credits) {
		echo '#facebook_pricing';
	}
	
	echo '">Facebook Pricing</a></li>
	<li><a href="#share_control">Like/Share</a></li>
	<li><a href="#open_graph">Open Graph</a></li>
	<li><a href="';
	
	if ($credits) { 
		echo '#transaction_logging'; 
	} 
	echo '">Transaction Log</a></li>
</ul>';

	echo '<div id="guide">';
	ym_box_top('Guide');
	
	echo '<div id="message" class="updated">';
	echo '<p>We have written a guide that should help you get Your Members Facebook Integration Up and Running, you can read it <a href="http://www.yourmembers.co.uk/the-support/guides-tutorials/your-members-facebook-integration/" target="_blank">here</a></p>';
	echo '</div>';
	
	ym_box_bottom();

	echo '</div>';
	echo '<div id="facebook_settings" class="subtabs">';
	
	echo '
<ul>
	<li><a href="#master_enable">Master Enable</a></li>
	<li><a href="#keys_settings">Application Keys</a></li>
	<li><a href="#canvas_settings">Canvas Settings</a></li>
	<li><a href="#page_settings">Page Settings</a></li>
	<li><a href="#dim_settings">Dimensions</a></li>
	<li><a href="#permissions">Permissions</a></li>
</ul>
';

	echo '<div id="master_enable">';
	ym_box_top('Enable Facebook');
	
	$review = '';
	if (!$facebook_settings->app_id) {
		$review .= '<div id="message" class="updated"><p>If you havn&#39;t created an app yet, you can do so <a href="http://developers.facebook.com/setup" target="_new">here</a></p></div>';
	}
	
	echo $review;
	
	echo '<table class="form-table">';

	$ym_formgen->render_form_table_radio_row('Enable Facebook', 'enabled', $facebook_settings->enabled, 'If not enabled if a users access the app, they are redirected to the site');
	
	echo '</table>';
	ym_box_bottom();
	echo '</div>
<div id="keys_settings">';
	ym_box_top('Application Keys');

	$review .= '<p>You can find and review these settings <a href="https://developers.facebook.com/apps/';
	if ($facebook_settings->app_id) {
		$review .= $facebook_settings->app_id;
	}
	$review .= '" target="_new">Here</a></p>';
	
	echo $review;
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_text_row('Facebook Application ID', 'app_id', $facebook_settings->app_id, 'The application ID');
	$ym_formgen->render_form_table_text_row('Facebook Application Secret', 'app_secret', $facebook_settings->app_secret, 'The application secret');
	
	echo '</table>';
	ym_box_bottom();
	echo '</div>';
	echo '<div id="canvas_settings">';
	ym_box_top('Canvas Settings');
	
	echo $review;
	
	echo '<table class="form-table">';
	
	echo '<tr><th>Facebook Canvas Name</th><td>http://apps.facebook.com/<input class="ym_input" type="text" name="canvas_url" id="canvas_url" value="' . $facebook_settings->canvas_url . '" /></td></tr>';
	echo '<tr><th>Facebook Canvas Landing</th><td>' . site_url('/') . '<input class="ym_input" type="text" name="canvas_landing" id="canvas_landing" value="' . $facebook_settings->canvas_landing . '" /></td></tr>';
	
	echo '</table>';
	ym_box_bottom();
	echo '</div>';
	echo '<div id="page_settings">';
	ym_box_top('Page Settings');
	
	echo $review;
	echo '<p>If you have a (fan) page vanity url specified the app will do its best to stay in the (fan) page if the session starts on the (fan) page</p>';
	if ($facebook_settings->app_id) {
		echo '<p>You will have needed to have added your Application as a Tab to your Facebook Fan Page, you can do that <a href="http://www.facebook.com/apps/application.php?id=' . $facebook_settings->app_id . '">here</a> and then click <strong>Add to my Page</strong></p>';
	}
	
	echo '<table class="form-table">';
	
	echo '<tr><th>Facebook (fan) Page Vanity Url</th><td>http://www.facebook.com/<input class="ym_input" type="text" name="page_url" id="page_url" value="' . $facebook_settings->page_url . '" /></td></tr>';
	echo '<tr><th>Facebook Page Landing</th><td>' . site_url('/') . '<input class="ym_input" type="text" name="page_landing" id="page_landing" value="' . $facebook_settings->page_landing . '" /></td></tr>';

	echo '</table>';
	ym_box_bottom();
	echo '</div>';
	echo '<div id="dim_settings">';
	ym_box_top('Dimension Settings');
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_combo_from_array_row('IFrame Size', 'iframe_size', $iframe_options, $facebook_settings->iframe_size, 'Make sure this setting is set identical to the setting in Facebook Application settings');
	$ym_formgen->render_form_table_text_row('IFrame Height', 'iframe_size_height', $facebook_settings->iframe_size_height, 'If you are using Scrollbars you can specify the height you want here, in px');
	
	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
	echo '<div id="permissions">';
	
	ym_box_top('Permissions');
	
	echo '<table class="form-table">';
	echo '<tr><td></td><td style="width: 50px;"></td></tr>';

	$ym_formgen->render_form_table_radio_row('Likewalls - user_likes', 'permission_likewall', $facebook_settings->permission_likewall, 'If using likewalls, we need to extended permissions to get User Likes, as some users have their Likes set to Private');
	$ym_formgen->render_form_table_radio_row('Email Address - email', 'permission_email', $facebook_settings->permission_email, 'For the registration with Facebook you can enable this to pre fill the email entry with their Primary Facebook Email Address. Users will be asked to accept additional permissions.');
	$ym_formgen->render_form_table_radio_row('Offline Access - offline_access', 'permission_offline_access', $facebook_settings->permission_offline_access, 'Access Tokens are on average valid for about an hour. Which means once an hour we have to send the user thru a loop, normally this is transparent. However if you do not want this you can enable offline access to get a longer access key');
	$ym_formgen->render_form_table_radio_row('Offline Access - publish_actions', 'permission_publish_actions', $facebook_settings->permission_publish_actions, 'Part of the new Open Graph Actions');

	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
	
	echo '</div>';
	echo '<div id="settings_settings" class="subtabs">';
	
	echo '
<ul>
	<li><a href="#access_settings">Access Settings</a></li>
	<li><a href="#registration_settings">Registration Settings</a></li>
	<li><a href="#content_settings">Content Settings</a></li>
	<li><a href="#comment_settings">Comment Settings</a></li>
	<li><a href="#analytics_settings">Analytics Settings</a></li>
</ul>
';
	echo '<div id="access_settings">';
	ym_box_top('Access Settings');
	
	echo '<table class="form-table">';

	$ym_formgen->render_form_table_radio_row('Force Facebook', 'force_facebook', $facebook_settings->force_facebook, 'Make YM Facebook only, force users visting the Website to access via Facebook');

	$ym_formgen->render_form_table_radio_row('Force Application Add', 'force_facebook_auth', $facebook_settings->force_facebook_auth, 'Force a user to be logged into Facebook and authorised the Application');
	$ym_formgen->render_form_table_radio_row('Force WordPress Login', 'force_wordpress_auth', $facebook_settings->force_wordpress_auth, 'Force a user to be logged into WordPress');
	$ym_formgen->render_form_table_radio_row('Require Link', 'require_link', $facebook_settings->require_link, 'Require a User to link their Facebook and WordPress Accounts if Logged in (unless superseeded by above)');
	$ym_formgen->render_form_table_radio_row('Disable the Link Suggested Message', 'disable_link_message', $facebook_settings->disable_link_message, 'When a user is logged out do not prompt them to link/login');

	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
	echo '<div id="registration_settings">';
	ym_box_top('Registration Settings');
	
	echo '<p>Using Hidden Register? You might want to turn on the Email Permission on the Facebook->Permissions Tab</p>';
	echo '<table class="form-table">';
	
//	$ym_formgen->render_form_table_radio_row('Register with Facebook', 'register_with_facebook', $facebook_settings->register_with_facebook, 'Allow a user to register a WordPress accout using their Facebook Account as a Base');
	$ym_formgen->render_form_table_radio_row('Hidden Register with Facebook', 'register_with_facebook_hidden', $facebook_settings->register_with_facebook_hidden, 'If a User uses the Facebook App and are not logged into WordPress create them a WordPress Account. If they are found by their username or email address, the two accounts are Auto Linked. (Implies Require Link and Force Redirect)');
//	$ym_formgen->render_form_table_radio_row('Email Address', 'permission_emailb', $facebook_settings->permission_email, 'For the registration with Facebook you can enable this to pre fill the email entry with their Primary Facebook Email Address. Users will be asked to accept additional permissions.');
	
	$packs = ym_get_packs();
	$ym_packs = array();
	$ym_packs[0] = 'No Account';
	foreach ($packs as $pack) {
		$ym_packs[$pack['id']] = ym_get_pack_label($pack['id']);
	}
	
	$ym_formgen->render_combo_from_array_row('Hidden Register Subscription', 'register_with_facebook_hidden_subid', $ym_packs, $facebook_settings->register_with_facebook_hidden_subid, 'Which Subscription To Put a Hidden Regsiter User on. It will <strong>not</strong> prompt for Payment');
	$ym_formgen->render_form_table_text_row('Hidden Register Redirect', 'register_with_facebook_hidden_redirect', $facebook_settings->register_with_facebook_hidden_redirect, 'On Hidden Register Complete Redirect the users to a page');

	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
	echo '<div id="content_settings">';
	ym_box_top('Content Settings');

	echo '<p>fb.php status is: ';

	$result = locate_template('fb.php');
	if (empty($result)) {
		echo 'Not Present';
	} else {
		echo 'Present';
	}
	echo '</p>';
		
	echo '<table class="form-table">';
	$ym_formgen->render_form_table_radio_row('fb.php', 'enable_fb_php', $facebook_settings->enable_fb_php, 'Enable the use of the Theme File fb.php instead of YM FB Theme, if fb.php is present');
	echo '</table>';

	echo '<p>If you use a theme fb.php file, most of these options are redundant, unless you implement them in your Theme File</p>';
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_radio_row('Allow Leave Facebook', 'enable_leave_facebook', $facebook_settings->enable_leave_facebook, 'Allow a user to start a website session from inside facebook, if a user vists the blog on Facebook first, they will stay inside Facebook, (is overriden by force facebook)');
	
	$ym_formgen->render_form_table_radio_row('Post Breakout', 'post_breakout', $facebook_settings->post_breakout, 'when viewing the end post, breakout (overrides Force Facebook)');
	$ym_formgen->render_form_table_radio_row('Page Breakout', 'page_breakout', $facebook_settings->page_breakout, 'when viewing the end page, breakout (overrides Force Facebook)');
	
	$ym_formgen->render_form_table_radio_row('Use excerpt', 'use_excerpt', $facebook_settings->use_excerpt, 'Use excerpts on post pages? (Template dependant)');

	$menus = array(
		'Auto',
		'Slug'
	);
	$query = 'SELECT name FROM ' . $wpdb->prefix . 'term_taxonomy tt LEFT JOIN ' . $wpdb->prefix . 'terms t ON t.term_id = tt.term_id WHERE taxonomy = \'nav_menu\'';
	foreach ($wpdb->get_results($query) as $row) {
		$menus[] = $row->name;
	}
	
	$ym_formgen->render_combo_from_array_row('Menu Control', 'menu', $menus, $facebook_settings->menu, 'We can use the First non blank menu, or you can pick your own. Menus are controlled <a href="' . site_url('/wp-admin/nav-menus.php') . '">here</a>');

	echo '</table>';
	
	ym_box_bottom();
	
	echo '</div>';
	echo '<div id="comment_settings">';
	ym_box_top('Comment Settings');
	
	echo '<p>You can replace the standard comment form with a Facebook Powered comment form</p>';
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_radio_row('Use Facebook Comments on Facebook', 'use_facebook_comments', $facebook_settings->use_facebook_comments);
	$ym_formgen->render_form_table_radio_row('Use Facebook Comments on the Site', 'use_facebook_comments_on_site', $facebook_settings->use_facebook_comments_on_site);
	
	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
	echo '<div id="analytics_settings">';
	ym_box_top('Analytics');
	
	echo '<p>You need to set the Website URL of the Profile to <strong>' . site_url('?ymfbook=googleanalytics') . '</strong> in order for Check Status to succeed</p>';
	echo '<p>Its recommended you use a separate profile under the same domain to track the Facebook Application</p>';
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_text_row('Google Analytics Profile ID', 'google_analytics_profile_id', $facebook_settings->google_analytics_profile_id, 'Uses the Standard code with this ID');
	$ym_formgen->render_form_table_textarea_row('Tracking Code', 'analytics_tracking_code', $facebook_settings->analytics_tracking_code, 'This will override the standard Google Analytics Code');
	
	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
	echo '</div>';
	
	if ($credits) {
		echo '<div id="facebook_credits">';
	
		ym_box_top(__('Facebook Credits', 'ym_facebook'));
		
		echo __('<p>Facebook Credits can only be used inside Facebook</p>', 'ym_facebook');
		echo sprintf(__('<p>You will need a Credits Callback URL, please use this: <strong>%s</strong></p>', 'ym_facebook'), site_url('?ym_process=ym_facebook_credits'));

		ym_box_bottom();
		ym_box_top(__('Primary Button', 'ym_facebook'));

		echo __('<p>In line with the Facebook Credits branding guide, you have a choice of three Pay with Facebook Credits Icons</p>', 'ym_facebook');

		$select = $facebook_settings->logo;

		echo '<table class="form-table">';
		echo '<tr><th>' . __('Option A', 'ym_facebook') . '</th>
			<td>
				<input type="radio" name="logo" id="logoa" value="' . YM_IMAGES_DIR_URL . 'pg/facebook_credits_a.png" ' . ($select == YM_IMAGES_DIR_URL . 'pg/facebook_credits_a.png' ? 'checked="checked"' : '') . ' />
				<label for="logoa">
					<img src="' . YM_IMAGES_DIR_URL . 'pg/facebook_credits_a.png" />
				</label>
			</td>
		</tr>';
		echo '<tr><th>' . __('Option B', 'ym_facebook') . '</th>
			<td>
				<input type="radio" name="logo" id="logob" value="' . YM_IMAGES_DIR_URL . 'pg/facebook_credits_b.png" ' . ($select == YM_IMAGES_DIR_URL . 'pg/facebook_credits_b.png' ? 'checked="checked"' : '') . ' />
				<label for="logob">
					<img src="' . YM_IMAGES_DIR_URL . 'pg/facebook_credits_b.png" />
				</label>
			</td>
		</tr>';
		echo '<tr><th>' . __('Option C', 'ym_facebook') . '</th>
			<td>
				<input type="radio" name="logo" id="logoc" value="' . YM_IMAGES_DIR_URL . 'pg/facebook_credits_c.png" ' . ($select == YM_IMAGES_DIR_URL . 'pg/facebook_credits_c.png' ? 'checked="checked"' : '') . ' />
				<label for="logoc">
					<img src="' . YM_IMAGES_DIR_URL . 'pg/facebook_credits_c.png" />
				</label>
			</td>
		</tr>';
		echo '</table>';

		ym_box_bottom();
		ym_box_top(__('Credits Dialog Images', 'ym_facebook'));

		echo __('<p>When purchasing a item users are shown a title, description, cost and a icon/logo. You can crontol these logos here</p>', 'ym_facebook');
		
		echo '<table class="form-table">';
		
		echo '
		<tr>
			<th>' . __('Subscription Purchase Image', 'ym_facebook') . '</th>
			<td>
				<input type="file" name="credits_purchase_sub_image" id="credits_purchase_sub_image" />';

			if ($facebook_settings->credits_purchase_sub_image) {
				echo '<div style="margin-top: 5px;"><img src="' . $facebook_settings->credits_purchase_sub_image . '" alt="' . __('Subscription Purchase Image', 'ym_facebook') . '" /></div>';
			}

		echo '
			</td>
		</tr>
		<tr>
			<th>' . __('Post Purchase Image', 'ym_facebook') . '</th>
			<td>
				<input type="file" name="credits_purchase_post_image" id="credits_purchase_post_image" />';

			if ($facebook_settings->credits_purchase_post_image) {
				echo '<div style="margin-top: 5px;"><img src="' . $facebook_settings->credits_purchase_post_image . '" alt="' . __('Post Purchase Image', 'ym_facebook') . '" /></div>';
			}

		echo '
			</td>
		</tr>
		<tr>
			<th>' . __('Bundle Purchase Image', 'ym_facebook') . '</th>
			<td>
				<input type="file" name="credits_purchase_bundle_image" id="credits_purchase_bundle_image" />';

			if ($facebook_settings->credits_purchase_bundle_image) {
				echo '<div style="margin-top: 5px;"><img src="' . $facebook_settings->credits_purchase_bundle_image . '" alt="' . __('Bundle Purchase Image', 'ym_facebook') . '" /></div>';
			}

		echo '
			</td>
		</tr>
		';

		echo '</table>';

		ym_box_bottom();
		ym_box_top(__('Other Settings', 'ym_facebook'));

		echo '<table class="form-table">';
		
		$ym_formgen->render_form_table_radio_row('Exclusive Facebook Credits', 'credits_exclusive', $facebook_settings->credits_exclusive, 'Use only Facebook Credits when inside Facebook');
		
		echo '<tr><td></td><td><p>';
		
		echo 'Facebook takes a 30% fee on all transactions.<br />' .
		'For Transactions in non USD Facebook pays out based on:<br />' .
		'Each Facebook Credit is $0.10 and then converts this into your native currency based on that days exchange rate<br />' .
		'So 10 Credits is $1 and 100 is $10<br />' .
		'Transactions can only occur in whole credits, so if any math involved results in a decimal prices will be rounded up<br />' .
		'Costs can only be in Whole Credits';
		
		
		global $ym_res;

		if ($ym_res->currency != 'USD') {
			echo '<br /><br />So you can either specify an exchange rate, or set a Facebook credits price per item on the Facebook Pricing Tab';
			echo '</p></td></tr>';
		
			$ym_formgen->render_form_table_text_row('Specify a Exchange Rate', 'exchange_rate', $facebook_settings->exchange_rate, 'If you specify an exchange rate, it will be used. Its the Exchange rate for your Currency to USD');
		} else {
			echo '<br /><br />You are using USD, so you do not need to worry about an exchange rate, but you can still set a Facebook credits price per item on the Facebook Pricing Tab';
			echo '</p></td></tr>';
		}
		$ym_formgen->render_combo_from_array_row('Rounding', 'exchange_round', $round_options, $facebook_settings->exchange_round, 'You can control the rounding method if any');
		echo '</table>';

		
		ym_box_bottom();
		
		echo '</div>';
		
		echo '<div id="facebook_pricing" class="subtabs">';
		
		echo '
<ul>
	<li><a href="#pack_pricing">Pack</a></li>
	<li><a href="#post_pricing">Post</a></li>
	<li><a href="#bundle_pricing">Bundle</a></li>
</ul>
';
		
		echo '<div id="pack_pricing">';
		ym_box_top('Pack Pricing');
		echo '<p>Remember: 1 Credit is USD 0.10 and pricing is in whole credits, if a override price is set Exchange Rates and Rouding is ignored</p>';
		
		echo '<table class="form-table">';
		
		$pricing_data = get_option('ym_fbook_pricing');
		// subs
		$packs = ym_get_packs();
		
		foreach ($packs as $pack) {
			$id = 'pack_' . $pack['id'];
			$ym_formgen->render_form_table_text_row('Pack Price: ' . ym_get_pack_label($pack['id']), 'override_price_' . $id, $pricing_data->$id);
		}
		
		echo '</table>';
		ym_box_bottom();
		echo '</div>';
		echo '<div id="post_pricing">';
		ym_box_top('Post Pricing');
		echo '<p>Remember: 1 Credit is USD 0.10 and pricing is in whole credits, if a override price is set Exchange Rates and Rouding is ignored</p>';
		
		echo '<table class="form-table">';
		
		$query = 'SELECT post_id FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = \'ym_post_purchasable\' AND meta_value = 1';
		foreach ($wpdb->get_results($query) as $post) {
			$id = 'post_' . $post->post_id;
			
			$postdata = get_post($post);
			
			$ym_formgen->render_form_table_text_row('Post Price: ' . $postdata->post_title, 'override_price_' . $id, $pricing_data->$id);
		}
		if (!$wpdb->num_rows) {
			echo '<tr><td></td><th>No Available Posts</th></tr>';
		}
		$ym_formgen->render_form_table_text_row('Default Override Pack Price', 'override_price_post_override', $pricing_data->post_override, 'You can set a default price to override if one is not set');
		
		echo '</table>';
		ym_box_bottom();
		echo '</div>';
		echo '<div id="bundle_pricing">';
		ym_box_top('Bundle Pricing');
		echo '<p>Remember: 1 Credit is USD 0.10 and pricing is in whole credits, if a override price is set Exchange Rates and Rouding is ignored</p>';
		echo '<table class="form-table">';
		
		$query = 'SELECT id, name FROM ' . $wpdb->prefix . 'ym_post_pack ORDER BY id ASC';
		foreach ($wpdb->get_results($query) as $bundle) {
			$id = 'bundle_' . $bundle->id;
			$ym_formgen->render_form_table_text_row('Bundle Price: ' . $bundle->name, 'override_price_' . $id, $pricing_data->$id);
		}
		if (!$wpdb->num_rows) {
			echo '<tr><td></td><th>No Available Bundles</th></tr>';
		}
		$ym_formgen->render_form_table_text_row('Default Override Bundle Price', 'override_price_bundle_override', $pricing_data->bundle_override, 'You can set a default price to override if one is not set');
		
		echo '</table>';
		ym_box_bottom();
		echo '</div>';
		
		echo '</div>';
	}
	
	echo '<div id="share_control" class="subtabs">';
	
	echo '
<ul>
	<li><a href="#post_control">Post Like/Share</a></li>
	<li><a href="#footer_control">Footer Like/Share</a></li>
	<li><a href="#shortcode_control">Shortcode Like/Share</a></li>
	<li><a href="#likewall_control">Likewall Like/Share</a></li>
</ul>
';
	
	echo '<div id="post_control">';
	
	ym_box_top('Post Share Control');
	
	echo '<p>This are the options for adding Share/Send buttons to Posts/Pages</p>';
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_radio_row('Enable Facebook Share', 'enable_share', $facebook_settings->enable_share, 'Allow people to share content to Facebook Feeds from within the App');

	$ym_formgen->render_form_table_radio_row('Enable Facebook Send', 'enable_send', $facebook_settings->enable_send, 'Allow people to share content via PM (needs Share to be on)');
	
	$ym_formgen->render_combo_from_array_row('Share Box to Use', 'share_box', $sharebox_options, $facebook_settings->share_box);
	$ym_formgen->render_form_table_radio_row('Show faces', 'show_faces', $facebook_settings->show_faces, 'Show the faces of Friend who have shared the same link');
	$ym_formgen->render_combo_from_array_row('Verb to Use', 'verb', $verbs, $facebook_settings->verb);
	$ym_formgen->render_combo_from_array_row('Color Scheme', 'color_scheme', $color_schemes, $facebook_settings->color_scheme);
	$ym_formgen->render_combo_from_array_row('Font', 'font', $fonts, $facebook_settings->font, 'The Font to use for the Buttons');

//	$ym_formgen->render_form_table_text_row('Add a Ref', 'ref', $facebook_settings->ref, 'A reference for tracking');

	echo '</table>';
	
	
	ym_box_bottom();
	
	echo '</div>';
	echo '<div id="footer_control">';
	
	ym_box_top('Footer Share Control');
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_radio_row('Enable Facebook Share Footer', 'enable_share_footer', $facebook_settings->enable_share_footer, 'Add a Share button for the whole site in the footer');
	
	$ym_formgen->render_form_table_radio_row('Enable Facebook Send Footer', 'enable_send_footer', $facebook_settings->enable_send_footer, 'Allow people to share content via PM (needs Share to be on)');
	
	$ym_formgen->render_combo_from_array_row('Share Box to Use', 'share_box_footer', $sharebox_options, $facebook_settings->share_box_footer);
	$ym_formgen->render_form_table_radio_row('Show faces', 'show_faces_footer', $facebook_settings->show_faces_footer, 'Show the faces of Friend who have shared the same link');
	$ym_formgen->render_combo_from_array_row('Verb to Use', 'verb_footer', $verbs, $facebook_settings->verb_footer);
	$ym_formgen->render_combo_from_array_row('Color Scheme', 'color_scheme_footer', $color_schemes, $facebook_settings->color_scheme_footer);
	$ym_formgen->render_combo_from_array_row('Font', 'font_footer', $fonts, $facebook_settings->font_footer, 'The Font to use for the Buttons');
	
//	$ym_formgen->render_form_table_text_row('Add a Ref', 'ref_footer', $facebook_settings->ref_footer, 'A reference for tracking');

	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
	echo '<div id="shortcode_control">';
	
	ym_box_top('Shortcode Share Control');
	
	echo '<p>You can use the shortcode [ym_fb_like]</p>';
	echo '<p>You can specify a shareurl to use, if not the post permalink will be used</p>';
//	echo '<p>You can specify a type to use, if not the "post" tab settings will be used, specify "shortcode" to use the below settings, or "footer" to use the footer tab settings</p>';
	
	echo '<table class="form-table">';
	
	$ym_formgen->render_form_table_radio_row('Enable Facebook Share Shortcode', 'enable_share_shortcode', $facebook_settings->enable_share_shortcode);
	$ym_formgen->render_form_table_radio_row('Auto Add to the bottom of posts on non Facebook Pages', 'enable_share_auto_nonfb', $facebook_settings->enable_share_auto_nonfb, 'Add a Share button to the bottom of all posts on non Facebook Framed Pages');
	
	$ym_formgen->render_form_table_radio_row('Enable Facebook Send Shortcode', 'enable_send_shortcode', $facebook_settings->enable_send_shortcode, 'Allow people to share content via PM (needs Share to be on)');
	
	$ym_formgen->render_combo_from_array_row('Share Box to Use', 'share_box_shortcode', $sharebox_options, $facebook_settings->share_box_shortcode);
	$ym_formgen->render_form_table_radio_row('Show faces', 'show_faces_shortcode', $facebook_settings->show_faces_shortcode, 'Show the faces of Friend who have shared the same link');
	$ym_formgen->render_combo_from_array_row('Verb to Use', 'verb_shortcode', $verbs, $facebook_settings->verb_shortcode);
	$ym_formgen->render_combo_from_array_row('Color Scheme', 'color_scheme_shortcode', $color_schemes, $facebook_settings->color_scheme_shortcode);
	$ym_formgen->render_combo_from_array_row('Font', 'font_shortcode', $fonts, $facebook_settings->font_shortcode, 'The Font to use for the Buttons');
	
//	$ym_formgen->render_form_table_text_row('Add a Ref', 'ref_shortcode', $facebook_settings->ref_shortcode, 'A reference for tracking');

	echo '</table>';
	
	ym_box_bottom();
	echo '</div>';
		echo '<div id="likewall_control">';

		ym_box_top('Likewall Share Control');

		echo '<table class="form-table">';

//		$ym_formgen->render_form_table_radio_row('Enable Facebook Share LikeWall', 'enable_share_likewall', $facebook_settings->enable_share_likewall, 'Add a Share button for the whole site in the likewall');

//		$ym_formgen->render_form_table_radio_row('Enable Facebook Send LikeWall', 'enable_send_likewall', $facebook_settings->enable_send_likewall, 'Allow people to share content via PM (needs Share to be on)');

		$ym_formgen->render_combo_from_array_row('Share Box to Use', 'share_box_likewall', $sharebox_options, $facebook_settings->share_box_likewall);
		$ym_formgen->render_form_table_radio_row('Show faces', 'show_faces_likewall', $facebook_settings->show_faces_likewall, 'Show the faces of Friend who have shared the same link');
		$ym_formgen->render_combo_from_array_row('Verb to Use', 'verb_likewall', $verbs, $facebook_settings->verb_likewall);
		$ym_formgen->render_combo_from_array_row('Color Scheme', 'color_scheme_likewall', $color_schemes, $facebook_settings->color_scheme_likewall);
		$ym_formgen->render_combo_from_array_row('Font', 'font_likewall', $fonts, $facebook_settings->font_likewall, 'The Font to use for the Buttons');

	//	$ym_formgen->render_form_table_text_row('Add a Ref', 'ref_likewall', $facebook_settings->ref_likewall, 'A reference for tracking');

		echo '</table>';

		ym_box_bottom();
		echo '</div>';
	
	echo '</div>';
	echo '<div id="open_graph">';
	
	ym_box_top('Open Graph Options');
	
	echo '<table class="form-table">';
	
	echo '
	<tr>
		<th>Open Graph Image
			<div style="color: gray; margin-top: 5px; font-size: 11px;">This image is used when a user links/shares content from your site.</div>
		</th>
		<td>
			<input type="file" name="open_graph_image" id="open_graph_image" />';

		if ($facebook_settings->open_graph_image) {
			echo '<div style="margin-top: 5px;"><img src="' . $facebook_settings->open_graph_image . '" alt="Open Graph Image" /></div>';
		}

	echo '
		</td>
	</tr>
	';
	
	$ym_formgen->render_combo_from_array_row('Default Open Graph Type', 'open_graph_type', $types, $facebook_settings->open_graph_type);
	$ym_formgen->render_form_table_text_row('Admin Ids', 'open_graph_admins', $facebook_settings->open_graph_admins, 'Users who should be linked/denoted as admins for your YM in Facebook, comma separated');
	
	echo '</table>';
	
	
	ym_box_bottom();
	
	echo '</div>';
	
	echo '<input type="hidden" name="ym_fb_tab_select" id="ym_fb_tab_select" value="0" />';
	
	echo '</form>';

	if ($credits) {
		// facebook credits appears to be enabled in YM
		echo '<div id="transaction_logging">';
		
		ym_box_top('Facebook Credits Transaction Logging');
		
		include(YM_FBOOK_BASE_DIR . 'admin/ym_facebook_transaction_log.php');
		
		ym_box_bottom();
		
		echo '</div>';
	}

	echo '</div>';
	
	echo '</div>';
}

// am in init so no need for extra add_action init

/*
YM HOOKS
*/
add_filter('ym_plugin_preappstore', 'ym_ym_menu');
function ym_ym_menu($links) {
	$links['Facebook'] = 'other_ymfacebook';
	return $links;
}
add_action('ym_admin_other', 'ymfacebook', 10, 1);
function ymfacebook($action) {
	if ($action == 'ymfacebook') {
		ym_fbook_admin();
	}
}
