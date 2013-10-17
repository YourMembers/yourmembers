<?php

echo '	<div class="wrap" id="poststuff">
			<h2 style="margin-bottom: 0px;">'.__('Your Minder','ymind').'</h2>';
		
ymind_get_messages();                

echo '<div style="float:left; width: 75%; padding-right: 15px;">';
                
echo ymind_start_box('Your Minder');
echo __('<p>Welcome to Your Minder. This plugin offers protection for your blog in that only one IP address per login can ever be used.</p><p>Use the settings page to set up your preferences. You can configure Your Minder allow a variable number of logins from the same IP over a variable number of minutes.</p><p>You have the option to lock out the account for a number of minutes or just log them out. If a lockout is chosen then you can set a time period for the lockout or require an email activation.</p><p>Using the admin links within Your Minder you can block IP addresses manually if need be and view any existing lockouts on the "Members" page.</p>', 'ymind');
echo ymind_end_box();

echo '</div>';

echo '<div style="float:left; width: 15%;">';
echo ymind_start_box(__('Version Check', 'ymind'));
ymind_check_version();
echo ymind_end_box();
echo '	</div>';

echo '	</div>';

echo '<div style="clear: both; height: 1px;">&nbsp;</div>';
		
ymind_render_footer();		
?>