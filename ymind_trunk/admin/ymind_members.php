<?php

echo '<div class="wrap" id="poststuff">
		<h2>'.__('Your Minder Locked Members', 'ymind').'</h2>

			<div style="clear:left;">
				'.__('The following users are currently locked out. Click the unlock button to release the account.', 'ymind').'
			</div>';

echo ymind_start_box('Your Minder - Locked Members');

ymind_render_locked_out_user_table();

echo ymind_end_box();

echo '</div>';
ymind_render_footer();
?>