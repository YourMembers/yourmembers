<?php
global $wpdb;

echo '	<div class="wrap" id="poststuff">';
ym_check_for_gift_sub();

echo __('<p>Management of content purchased outside of a package subscription</p>','ym');

echo ym_start_box(__('Individual Purchases Made','ym'));
ym_render_all_posts_purchased(true);
echo ym_end_box();

echo ym_start_box(__('Content Purchased Count','ym'));
ym_render_posts_purchased(false);
echo ym_end_box();

echo ym_start_box(__('Gift a post/page','ym'));
ym_render_ppp_management();
echo ym_end_box();

echo '</div>';
