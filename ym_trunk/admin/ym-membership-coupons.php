<?php

echo '	<div class="wrap" id="poststuff">';

ym_coupon_update();

if ($coupon_id = ym_get('coupon_id')) {
	$coupon = ym_get_coupon($coupon_id);
	
	if (ym_post('edit')) {
		echo ym_start_box(__('Edit coupon: "', 'ym') . $coupon->name . '"');
		ym_render_coupon_edit($coupon_id);
		echo ym_end_box();
	} 
	if (ym_post('view')){
		echo ym_start_box(__('View Users who used Coupon: "', 'ym') . $coupon->name . '"');
		ym_render_coupon_view($coupon_id);
		echo '<form method="POST">
		<input type="hidden" name="ym_coupon_id" value="'.$coupon_id.'" />
		<input class="button" type="submit" name="ym_start_xls_coupon" value="' . __('Export Data', 'ym') . '" />
		 </form>';
		echo ym_end_box();
	}

} else {	
	echo ym_start_box(__('Coupon','ym'));
	ym_render_coupons();
	echo ym_end_box();
}

echo '</div>';
