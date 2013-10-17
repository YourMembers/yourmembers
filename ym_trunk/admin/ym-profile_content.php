<?php

/*
* $Id: ym-profile_content.php 1670 2011-12-16 14:12:47Z BarryCarlyon $
* $Revision: 1670 $
* $Date: 2011-12-16 14:12:47 +0000 (Fri, 16 Dec 2011) $
*/

get_currentuserinfo();
global $current_user, $ym_res;

$html = '';

$html .= '<div class="wrap" id="poststuff">
	<h2>' . __('Members Content','ym') . '</h2>';

$html .= ym_membership_content_page(true);

$html .= @$ym_res->members_content_divider2_html;

if (current_user_can('publish_posts')) {
	$html .= ym_start_box(__('My Sold Posts', 'ym'));
	$html .= __('<p>This section displays a list of the posts and pages that you have authored and have sold.</p>', 'ym');

	echo $html;
	$html = '';

	ym_render_all_posts_purchased(false, false, $current_user->ID);

	$html .= ym_end_box();
}

$html .= '</div>';

echo $html;
