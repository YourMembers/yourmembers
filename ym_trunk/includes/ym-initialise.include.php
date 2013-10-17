<?php

/*
* $Id: ym-initialise.include.php 2460 2012-12-06 12:29:23Z bcarlyon $
* $Revision: 2460 $
* $Date: 2012-12-06 12:29:23 +0000 (Thu, 06 Dec 2012) $
*/

function ym_activate() {
	global $wpdb;

	ym_create_tables();
	add_option('ym_db_version', YM_DATABASE_VERSION);

	$init_options = array(
	'ym_sys'=>array('construct'=>'YourMember_System', 'init_func'=>'ym_new_system')
	, 'ym_packs'=>array('construct'=>'YourMember_Packs', 'init_func'=>'ym_new_packs')
	, 'ym_package_types'=>array('construct'=>'YourMember_Package_Types', 'init_func'=>'ym_new_account_types')
	);

	foreach ($init_options as $option=>$array) {
		if (get_option($option) === false) {
			$obj = new $array['construct']();
			if (method_exists($obj, 'initialise')) {
				$obj->initialise($option);
			} else {
				if (function_exists($array['init_func'])) {
					$obj->update($array['init_func']());
					add_option($option, $obj);
				}
			}
		}
	}

	if (get_option('ym_custom_fields') === false) {
		$obj = new StdClass();

		// create entries for terms and conditions and subscription intro
		$obj->entries = array(
			array(
				'id'=>1,
				'name'=>'subscription_introduction',
				'label'=> __('Subscription Introduction', 'ym'),
				'type'=>'textarea',
				'caption'=>'',
				'available_values'=>'',
				'required'=>false,
				'readonly'=>true,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id'=>2,
				'name'=>'subscription_options',
				'label'=> __('Subscription Options', 'ym'),
				'type'=>'textarea',
				'caption'=>'',
				'available_values'=>'',
				'required'=>true,
				'readonly'=>true,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id'=>3,
				'name'=>'terms_and_conditions',
				'label'=> __('Terms and Conditions', 'ym'),
				'type'=>'textarea',
				'caption'=>'',
				'available_values'=>'',
				'required'=>true,
				'readonly'=>true,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id' => 4,
				'name' => 'user_email',
				'label' => __('Email Address', 'ym'),
				'type' => 'text',
				'caption'=>'',
				'available_values'=>'',
				'required'=>true,
				'readonly'=>false,
				'profile_only'=>true,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id' => 5,
				'name' => 'first_name',
				'label' => __('First Name', 'ym'),
				'type' => 'text',
				'caption'=>'',
				'available_values'=>'',
				'required'=>false,
				'readonly'=>false,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id' => 6,
				'name' => 'last_name',
				'label' => __('Last Name', 'ym'),
				'type' => 'text',
				'caption'=>'',
				'available_values'=>'',
				'required'=>false,
				'readonly'=>false,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id' => 7,
				'name' => 'ym_password',
				'label' => __('Password', 'ym'),
				'type' => 'password',
				'caption'=>'',
				'available_values'=>'',
				'required'=>false,
				'readonly'=>false,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id'=>8,
				'name'=>'birthdate',
				'label'=> __('Birthdate', 'ym'),
				'caption'=>'',
				'available_values'=>'',
				'type'=>'text',
				'required'=>false,
				'readonly'=>false,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id'=>9,
				'name'=>'coupon',
				'label'=> __('Coupon Code', 'ym'),
				'caption'=>'',
				'available_values'=>'',
				'type'=>'text',
				'required'=>false,
				'readonly'=>false,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id'=>10,
				'name'=>'country',
				'label'=> __('Country', 'ym'),
				'caption'=>'',
				'available_values'=>'',
				'type'=>'text',
				'required'=>false,
				'readonly'=>false,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id'=>11,
				'name'=>'user_url',
				'label'=> __('Website', 'ym'),
				'caption'=>'',
				'available_values'=>'',
				'type'=>'text',
				'required'=>false,
				'readonly'=>false,
				'profile_only'=>false,
				'no_profile'=>false,
				'value'=>'',
				'builtin' => true
			),
			array(
				'id' => 12,
				'name' => 'user_description',
				'label' => __('Biographical Info', 'ym'),
				'available_values' => '',
				'caption' => __('Share a little biographical information to fill out your profile. This may be shown publicly', 'ym'),
				'type' => 'textarea',
				'required' => false,
				'readonly' => false,
				'profile_only' => false,
				'no_profile' => false,
				'value' => '',
				'builtin' => true
			)
		);
		$obj->next_id = (count($obj->entries))+1;

		$obj->order = '1;2;4;5;6';
		add_option('ym_custom_fields', $obj);
	}

	$to_activate = array();
	$modules = array(
		'ym_free'
		, 'ym_paypal'
	);

	foreach ($modules as $module) {
		if (!ym_is_active_module($module)) {
			require_once(YM_MODULES_DIR.$module.'.php');
			$obj = new $module();
			$obj->activate();
			$to_activate[] = $module;
		} else {
			// already active *cough*
			// ym conf
			$to_activate[] = $module;
		}
	}

	update_option('ym_modules', $to_activate);
	
	update_option('ym_excluded_pages', array());

	do_action('ym_activate_additional');

	// flush rewrite rules
	global $wp_rewrite;
	$wp_rewrite->flush_rules();

} // end_of ym_activate()

function ym_deactivate() {
	global $wpdb;

	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'deactivate') {
		delete_option('ym_license_key');
		delete_option('ym_licensing_activation_date');
		delete_option('ym_vc');
		delete_option('ym_tos_version_accepted');
		delete_option('ym_db_version');
	} else if (isset($_REQUEST['ym_uninstall'])) {
		// options clean
		$sql = 'DELETE FROM ' . $wpdb->options . '
				WHERE option_name LIKE "ym_%" ';
		$wpdb->query($sql);
		
		// updater
		$sql = 'DELETE FROM ' . $wpdb->options . '
				WHERE option_name LIKE "external_updates-ym" ';
		$wpdb->query($sql);

		$tables_to_drop = array(
			'posts_purchased',
			'ym_coupon',
			'ym_coupon_use',
			'ym_download',
			'ym_download_attribute',
			'ym_download_attribute_type',
			'ym_download_post_assoc',
			'ym_post_pack',
			'ym_post_packs_purchased',
			'ym_post_pack_post_assoc',
			'ym_register_flows',
			'ym_register_pages',
			'ym_transaction',
			'ym_transaction_action',
		);

		foreach ($tables_to_drop as $table) {
			$sql = 'DROP TABLE ' . $wpdb->prefix . $table;
			$wpdb->query($sql);
		}

		$sql = 'DELETE FROM ' . $wpdb->postmeta . '
			WHERE meta_key LIKE "_ym_%"';
		$wpdb->query($sql);

		$sql = 'DELETE FROM ' . $wpdb->usermeta . '
			WHERE meta_key LIKE "ym_%"';
		$wpdb->query($sql);
	}

	do_action('ym_deactivate_additional');
}

function ym_create_tables() {
	global $wpdb;
	
	/**
	posts_purchased
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'posts_purchased` (
		id int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		user_id integer NULL,
		post_id integer NOT NULL,
		payment_method varchar(255) NULL,
		unixtime integer
	) ENGINE=MYISAM;';
	$wpdb->query($sql);

	/**
	ym_coupon
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_coupon` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL,
			`value` varchar(255) NOT NULL,
			`allowed` VARCHAR( 4 ) NOT NULL DEFAULT  "0000",
			`usage_limit` INT( 11 ) NOT NULL DEFAULT  "0",
			`description` text,
			`unixtime` int(11) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM';
	$wpdb->query($sql);
	
	/**
	ym_coupon_use
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_coupon_use` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `coupon_id` int(11) NOT NULL,
		  `user_id` int(11) NOT NULL,
		  `purchased` varchar(255) NOT NULL,
		  `tos` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM;';
	$wpdb->query($sql);

	/**
	ym_download
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_download` (
		 `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		 `title` VARCHAR (200) NOT NULL ,
		 `filename` LONGTEXT NOT NULL ,
		 `postDate` DATETIME NOT NULL ,
		 `members` INT (12) UNSIGNED NOT NULL ,
		 `user` VARCHAR (200) NOT NULL
	 ) ENGINE=MYISAM;';
	$wpdb->query($sql);

	/**
	ym_download_attribute
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_download_attribute` (
	  `id` int(11) NOT NULL auto_increment,
	  `download_id` int(11) NOT NULL,
	  `attribute_id` int(11) NOT NULL,
	  `value` varchar(255) default NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM;';
	$wpdb->query($sql);

	/**
	ym_download_attribute_type
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_download_attribute_type` (
	  `id` int(11) NOT NULL auto_increment,
	  `field_type_id` int NOT NULL,
	  `name` varchar(255) NOT NULL,
	  `description` text NOT NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM;';
	$wpdb->query($sql);

	/**
	ym_download_post_assoc
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_download_post_assoc` (
		 `download_id` int(11) NOT NULL,
		 `post_id` int(11) NOT NULL,
		 PRIMARY KEY (`download_id`,`post_id`)
	 ) ENGINE=MyISAM;';
	$wpdb->query($sql);

	/**
	ym_post_pack
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_post_pack` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL,
			`cost` int(11) NOT NULL,
			`description` text,
			`unixtime` int(11) NOT NULL,
			`expiretime` int(11) NOT NULL,
			`additional` VARCHAR( 255 ) NOT NULL ,
			`purchaseexpire` INT( 11 ) NOT NULL ,
			`purchaselimit` INT( 11 ) NOT NULL ,
			`saleend` INT( 11 ) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM';
	$wpdb->query($sql);

	/**
	ym_post_packs_purchased
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_post_packs_purchased` (
		id int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		user_id integer NULL,
		pack_id integer NOT NULL,
		payment_method varchar(255) NULL,
		unixtime integer
	) ENGINE=MYISAM;';
	$wpdb->query($sql);
	
	/**
	ym_post_pack_post_assoc
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_post_pack_post_assoc` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`pack_id` int(11) NOT NULL,
			`post_id` int(11) NOT NULL,
			`unixtime` int(11) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM';
	$wpdb->query($sql);

	/**
	ym_register_flows
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_register_flows` (
		  `flow_id` int(11) NOT NULL AUTO_INCREMENT,
		  `flow_name` varchar(255) NOT NULL,
		  `flow_pages` text NOT NULL,
		  `complete_url` varchar(255) NOT NULL,
		  `complete_text` text NOT NULL,
		  `complete_button` VARCHAR(255) NOT NULL DEFAULT "Complete",
		  PRIMARY KEY (`flow_id`),
		  UNIQUE KEY `flow_name` (`flow_name`)
		) ENGINE=MYISAM;';
	$wpdb->query($sql);

	/**
	ym_register_pages
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_register_pages` (
		  `page_id` int(11) NOT NULL AUTO_INCREMENT,
		  `page_name` varchar(255) NOT NULL,
		  `page_fields` text NOT NULL,
		  `button_text` VARCHAR(255) NOT NULL DEFAULT "Next",
		  PRIMARY KEY (`page_id`)
		) ENGINE=MYISAM;';
	$wpdb->query($sql);

	/**
	ym_transaction
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_transaction` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`transaction_id` int(11) NOT NULL DEFAULT "0",
			`user_id` int(11) NULL,
			`action_id` int(11) NOT NULL,
			`data` text,
			`unixtime` int(11) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM';
	$wpdb->query($sql);
	
	/**
	ym_transaction_action
	*/
	$sql = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'ym_transaction_action` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`name` varchar(255) NOT NULL,
			`description` text,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM';
	$wpdb->query($sql);
	
	/**
	Gracefully catch reinstall
	*/
	$sql = 'TRUNCATE ' . $wpdb->prefix . 'ym_transaction_action';
	$wpdb->query($sql);

	/**
	Prep Data
	*/
	$sql = 'INSERT INTO ' . $wpdb->prefix . 'ym_transaction_action (id, name, description)
		VALUES
			(1, "Payment", "When a payment occurs")
			, (2, "Account Type Assignation", "When an account type is assigned/re-applied to an account")
			, (3, "Refund Processed", "When a refund has been requested")
			, (4, "PPP Purchased", "When a user buys a post")
			, (5, "PPP Pack Purchased", "When a user buys a pack of posts")
			, (6, "Download Started", "When a user tries to download a file")
			, (7, "Download Completed", "When a user completes a file download")
			, (8, "Access Expiry", "When a users paid access expires")
			, (9, "Access Extension", "When a users paid access is set/extended")
			, (10, "IPN", "When an Instant Payment Notification (Callback) is received")
			, (11, "User Status Update", "When a users status is updated (Expired, Active, Pending, etc..)")
			, (12, "Package Purchased", "The Package ID Purchased")
		';
	$wpdb->query($sql);
}

/**
Misc Functions
*/
function ym_activate_last_step($key) {
	global $ym_auth;
	$ym_auth->ym_set_key($key);
	$this_version = YM_PLUGIN_VERSION . ':' . strtoupper(YM_PLUGIN_PRODUCT) . ':' . YM_PLUGIN_VERSION_ID;
	update_option('ym_current_version', $this_version);
	ym_check_version(TRUE);
	echo '<script>window.location=\'' . YM_ADMIN_URL . '&' . YM_ADMIN_FUNCTION . '_activated=1\';</script>';
	exit;
}

function ym_tos_checks() {
	global $ym_version_resp, $ym_auth;

	$key = ym_post('registration_email', FALSE);

	$tos_result = FALSE;

	$ym_tos_version_accepted = get_option('ym_tos_version_accepted', 0);
	if ($ym_tos_version_accepted < $ym_version_resp->tos->tos_version_id) {
		if (ym_post('activate_plugin') == 'tosterms') {
			// submitted form
			$tos_result = $ym_auth->tos_submit();
			if (!is_wp_error($tos_result) && $key) {
				// reload for recon
				ym_check_version(TRUE);
				ym_activate_last_step($key);
			}
		}

		// Show FORM
		echo '
<div class="wrap" id="poststuff">
	<h2>' . YM_ADMIN_NAME . '</h2>';

		ym_box_top(__('The End User License has been Updated', 'ym'));

		if (is_wp_error($tos_result)) {
			echo '<div id="message" class="error ym_auth">';
			echo '<div style="margin: 5px 0px; color:red; font-weight:bold;">';
			echo $tos_result->get_error_message();
			echo '</div></div>';
		}

		echo '<iframe src="' . $ym_version_resp->tos->tos_text_url . '" style="width: 100%; height: 500px;"></iframe>';
		echo '<p style="float: right;"><a href="' . $ym_version_resp->tos->tos_text_url . '">' . __('Download EULA', 'ym') . '</a></p>';

		echo '
	<form action="" method="post">
		<fieldset>
			<table class="form-table" style="width: 50%; margin: 10px auto; text-align: center;" >
				<tr>
					<td>
			<label for="confirm_email">' . __('Please confirm your Email', 'ym') . '</label>
					</td><td>
			<input type="email" name="confirm_email" id="confirm_email" value="' . ym_post('confirm_email') . '" style="width: 300px;" />
			<input type="hidden" name="registration_email" value="' . ym_post('registration_email') . '" />
					</td>
				</tr>
				<tr>
					<td colspan="2">

			<p>' . __('To continue you must accept the terms of this agreement:', 'ym') . '</p>
				<input type="hidden" name="activate_plugin" value="tosterms" />
				<input type="hidden" name="tosversion" value="' . $ym_version_resp->tos->tos_version_id . '" />
					</td>
				</tr><tr>
					<td colspan="2">
						<label for="tickbox">' . __('I accept the terms of this agreement:', 'ym') . '</label>
						<input type="checkbox" name="tickbox" id="tickbox" value="ticked" />
					</td>
				</tr><tr>
					<td colspan="2">
						<p class="submit" style="text-align: center;">
							<input type="submit" class="button-secondary" name="tos" value="Uninstall" />
							<input type="submit" class="button-primary" name="tos" value="Continue" style="font-weight: 700;" />
						</p>
					</td>
				</tr>
			</table>
		</fieldset>
	</form>';

		ym_box_bottom();
		echo '
</div>
';
	} else if ($key) {
		// TOS OK/already accepted
		ym_activate_last_step($key);
	} else {
		return FALSE;
	}
	return TRUE;
}
