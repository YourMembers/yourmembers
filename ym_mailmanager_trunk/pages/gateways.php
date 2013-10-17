<?php

global $ym_formgen, $mm;

$mailmanager_gateways = mailmanager_load_gateways();
$mm->version_check(TRUE);
if ($ym_mm_store = $mm->version_check->ym_mm_store) {
	// additional
	foreach ($ym_mm_store as $item) {
		if (!$mailmanager_gateways[$item->safe]) {
			// installable
			$install = explode('/', $item->logo);
			array_pop($install);
			array_pop($install);
			$install[] = 'zips/' . $item->safe . '.zip';
			$install = implode('/', $install);
			$mailmanager_gateways[$item->safe] = array(
				'name'			=> $item->name,
				'safe'			=> $item->safe,
				'desc'			=> $item->desc,
				'logo'			=> $item->logo,
				'settings'		=> 2,
				'install'		=> $install,
			);
		}
	}
}

$current_settings = get_option('ym_other_mm_settings');

global $install;
if ($_POST) {
	global $mm;
	
	$current_settings->first_run_done = TRUE;
	$way = ym_post('mail_gateway');
	
	$current_settings->mail_gateway = $way;
	update_option('ym_other_mm_settings', $current_settings);
	
	ym_box_top(__('MailManager: Mail Gateways', 'ym_mailmanager'));
	echo '<p>' . sprintf(__('Your Selection of <strong>%s</strong> has been saved</p>', 'ym_mailmanager'), $mailmanager_gateways[$way]['name']);
	
	if ($mailmanager_gateways[$way]['settings']) {
		echo '<meta http-equiv="refresh" content="5;' . $mm->page_root . '&mm_action=gateway" />';
		echo '<p><a href="' . $mm->page_root . '&mm_action=gateway">' . __('Visit the Settings page for this Gatway for Setup', 'ym_mailmanager') . '</a></p>';
	}
	
	$class = 'mailmanager_' . $way . '_gateway';
	$class = new $class;
	$class->activate();

	ym_box_bottom();
	return;
}

if (!$current_settings->first_run_done) {
	ym_box_top(__('MailManager FirstRun', 'ym_mailmanager'));
	echo '<p>' . __('This is your First Visit to MailManager please select a Mail Gateway', 'ym_mailmanager') . '</p>';
	ym_box_bottom();
}

ym_box_top(__('MailManager: MailMail Gateways', 'ym_mailmanager'));

//http://www.brunildo.org/test/img_center.html
echo '<style type="text/css">
.mailmanager_gateway {
	float: left;
	width: 200px;
	height: 180px;
	border: 1px solid #9F9F9F;
	padding: 5px;
	margin: 5px;
}
.mailmanager_gateway .use_gateway {
	margin-left: auto;
	margin-right: auto;
	width: 200px;
	display: block;
}
.mailmanager_gateway .gateway_desc {
	height: 50px;
}
.wraptocenter {
	width: 200px;
	height: 70px;
	display: table-cell;
	vertical-align: middle;
	text-align: center;
}
.wraptocenter * {
	vertical-align: middle;
}
/*\*//*/
.wraptocenter {
    display: block;
}
.wraptocenter span {
    display: inline-block;
    height: 100%;
    width: 1px;
}
/**/
</style>
<!--[if lt IE 8]><style>
.wraptocenter span {
    display: inline-block;
    height: 100%;
}
</style><![endif]-->';
// width = 222 * count
echo '<div>';

shuffle($mailmanager_gateways);
foreach ($mailmanager_gateways as $gateway) {
	echo '<div class="mailmanager_gateway">';
	
	echo '<div class="wraptocenter">';
	echo '<span></span>';
	echo '<img src="' . $gateway['logo'] . '" alt="' . $gateway['name'] . '" style="';
	
	$size = getimagesize($gateway['logo']);
	if ($size[1] >= $size[0]) {
		echo 'height: 65px;';
	} else {
		echo 'width: 150px;';
	}
	
	echo '" />';
	echo '</div>';
	echo '<p style="font-weight: 700; text-align: center;">' . $gateway['name'] . '</p>';
	echo '<p class="gateway_desc" style="text-align: justify;">' . $gateway['desc'] . '</p>';

	echo '<form action="" method="post">';
	if ((int)$gateway['settings'] == 2) {
		echo '<input type="hidden" name="install_gateway" value="' . $gateway['safe'] . '" />';
		echo '<input type="submit" class="use_gateway" value="Install ' . $gateway['name'] . '" />';
	} else {
		echo '<input type="hidden" name="mail_gateway" value="' . $gateway['safe'] . '" />';
		echo '<input type="submit" class="use_gateway" value="Use ' . $gateway['name'] . '" />';
	}
	echo '</form>';
	
	echo '</div>';
}

echo '<div style="clear: both;"></div>';
echo '</div>';

ym_box_bottom();
