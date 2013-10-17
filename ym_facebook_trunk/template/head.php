<?php

global $page, $paged;
$title = wp_title('|', false, 'right') . get_bloginfo('name');

echo '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://ogp.me/ns/fb#" ';

language_attributes();

echo '>
<head>
<meta charset="' . get_bloginfo('charset') . '" />
<title>' . $title . '</title> 
<link rel="profile" href="http://gmpg.org/xfn/11" />
<!--
<link rel="stylesheet" type="text/css" media="all" href="http://cf.barry/wordpress_dev/wp-content/themes/twentyten/style.css" /> 
<link rel="pingback" href="' . site_url('xmlrpc.php') . '" />
-->

<style type="text/css" media="all">#wpadminbar { display:none; }</style>
';

// since we have fired the facebook templates
// lets make sure we are inside the facebook iframe
echo '
<!-- killer -->
<script type="text/javascript">
if (window.parent == window.self) {
	top.location.href="' . ($facebook_settings->page_url ? YM_FBOOK_PAGE_TARGET : YM_FBOOK_APP_TARGET) . '";
}
</script>
';

wp_head();

//echo ym_fbook_og();

echo '
</head>
<body ';

body_class();

echo '>';

do_action('ym_fbook_init');

echo '
	<div id="ym_facebook">
		<div id="wrapper">
';

echo '<div id="ym_fbook_nav" class="ymenu">';

if ($facebok_settings->ym_fbook_page_breakout) {
	ob_start();
}

$menu = array();
$menus = array('', 'slug');
global $wpdb;
$query = 'SELECT slug FROM ' . $wpdb->prefix . 'term_taxonomy tt LEFT JOIN ' . $wpdb->prefix . 'terms t ON t.term_id = tt.term_id WHERE taxonomy = \'nav_menu\'';
foreach ($wpdb->get_results($query) as $row) {
	$menus[] = $row->slug;
}
if ($menus[$facebook_settings->menu]) {
	$menu['menu'] = $menus[$facebook_settings->menu];
}

wp_nav_menu($menu);

if ($facebok_settings->ym_fbook_page_breakout) {
	$content = ob_get_contents();
	ob_end_clean();
	$content = explode('</li>', $content);
	foreach ($content as $key => $line) {
		if ($key != 0) {
			$content[$key] = str_replace('href', 'target="_parent" href', $line) . '</li>';
		}
	}
	echo implode($content);
}

echo '</div>';

do_action('ym_fbook_messages');
