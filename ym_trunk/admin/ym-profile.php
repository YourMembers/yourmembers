<?php

/*
* $Id: ym-profile.php 2493 2012-12-21 15:40:21Z davenaylor $
* $Revision: 2493 $
* $Date: 2012-12-21 15:40:21 +0000 (Fri, 21 Dec 2012) $
*/

$token = ym_get_rss_token();
$rss_url = home_url('/?feed=rss2&token=' . $token);

$html = '<div id="poststuff" class="wrap">
	<h2 style="margin: 5px 0px;">' . __('Your Members - Membership Information','ym') . '</h2>
	<div style="overflow: auto;">
	<div style="width: 500px; float: left">
	<div class="postbox" style="margin:10px 0px;">
		<h3>' . __('Subscription Information',"ym") . '</h3>
		<div class="inside">';
$html .= ym_get_user_profile();
$html .= '</div></div>';

$hist = ym_get_user_purchase_history();
if ($hist) {
	$html .= '<div class="postbox" style="margin:10px 0px;">
		<h3>' . __('Purchase History', "ym") . '</h3>
		<div class="inside">';
	$html .= $hist;
	$html .= '</div></div>';
}
$html .= '</div>';

$html .= '<div class="postbox" style="margin: 10px 0px; width: 320px; float: left; margin-left: 10px; overflow: auto;">
		<h3>' . __('Membership Information',"ym") . '</h3>
		<div class="inside">';

if (ym_use_rss_token()) {
	$html .= '<div style="margin-bottom: 10px;">
	<h4>' . __('RSS Tokens',"ym") . '</h4>
	<div style="margin-bottom: 10px;">' . sprintf(__('Your RSS Token is: <strong>%s</strong>', 'ym'), $token) . '</div>
	<div style="margin-bottom: 10px;">
	' . __('Use the following link to access your RSS feed with access to private parts of the site.',"ym") . '<br /><br /><a href="' . $rss_url . '">' . $rss_url . '</a>
</div></div>';
}

$html .= ym_get_user_unsub_button_gateway();
$html .= '</div></div>';

$html .= '</div></div>';
	
echo $html;
