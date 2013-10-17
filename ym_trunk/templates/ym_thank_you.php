<?php

$home_url = trailingslashit(get_option('siteurl'));

if (!isset($_GET['ym_module'])) {
	wp_redirect($home_url);
} else {
	$this_module = $_GET['ym_module'];
	$modules = get_option('ym_modules');
	$found = false;

	foreach ($modules as $module) {
		if ($this_module == $module) {
			$found = true;
			break;
		}
	}

	if (!$found) {
		wp_redirect($home_url);
	}
	
	do_action('ym_thank_you_page');
}

$obj = get_option($this_module);

$sys = get_option('ym_sys');

if (!isset($_GET['status']) || $_GET['status'] == 'complete') {
	$message = ($obj->thanks_message ? $obj->thanks_message:$sys->default_thanks_message);
	$title = ($obj->thanks_title ? $obj->thanks_title:$sys->default_thanks_title);
} else {
	$message = ($obj->failed_message ? $obj->failed_message:$sys->default_failed_message);
	$title = ($obj->failed_title ? $obj->failed_title:$sys->default_failed_title);
}

get_header();

//Layout HTML Start
echo '
<div id="content" class="narrowcolumn">
	<div class="post">
		<h2>' . $title . '</h2>
		<div class="entry">';

//Page Content Start
echo $message;

if (isset($_GET['errors']) && $_GET['errors'] != '') {
	$errors = explode('|', $_GET['errors']);

	echo '<h3>' . __('Messages', 'ym') . '</h3>';
	echo '<div>
			<ul>';

	foreach ($errors as $error) {
		echo '<li>' . $error . '</li>';
	}

	echo '</ul>
		</div>';
}
//Page Content End

// End Layout HTML
echo '	</div>
	</div>
</div>';

get_sidebar();
get_footer();
