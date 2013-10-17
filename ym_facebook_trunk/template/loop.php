<?php

if (ym_get('login', FALSE)) {
	ym_fbook_wp_login_form();
	return;
}
if (ym_get('ym_fb_profile', FALSE)) {
	ym_fbook_profile(TRUE);
	return;
}

//	query_posts();
if (have_posts()) {
	while (have_posts()) {
		the_post();
		
		echo '
<div class="post">
	<h2><a href="' . get_permalink() . '" rel="bookmark" title="Permanent Link to ' . the_title_attribute(array('echo' => false)) . '"';
		
		if ($facebook_settings->post_breakout) {
			echo ' target="_parent" ';
		}
		
		echo '>' . get_the_title() . '</a></h2>
';
		
//		if ($facebook_settings->use_excerpt && !is_singular()) {
			//echo do_shortcode(get_the_excerpt());
//			the_excerpt();
//		} else {
			//echo do_shortcode(get_the_content());
			the_content();
//		}
		
		echo ym_fbook_render_like_button(get_permalink(), 'post');
		echo '</div>';
	}
} else {
	echo '<p>Sorry, no posts matched your criteria.</p>';
}
