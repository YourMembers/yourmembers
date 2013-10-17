<?php

if (comments_open() && post_type_supports(get_post_type(), 'comments' )) {
	if (!defined('YM_FBOOK_NO_COMMENTS')) {
		echo '<div class="fb-comments" data-href="' . get_permalink() . '" data-num-posts="20" data-width="' . do_shortcode('[ym_fb_width]') . '"></div>';
	}
} else {
	echo '<p class="nocomments">' . __('Comments are closed.') . '</p>';
}
