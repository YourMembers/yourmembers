<?php

ob_start();

?>
<div id="loginform">
<?php

$_SESSION['facebook_use_last_page'] = 1;
echo ym_fbook_wp_login_form_top();

?>

<div>
<p class="center">
In order to continue you must permit the Application access to your Facebook Profile
<br /><br />
<a id="ym_fb_login_button" href="<?php

echo ym_fbook_oauth_go();

?>" target="_parent">Login</a>
</p>
</div>

</div>
<?php

if ($noecho) {
	$login = ob_get_contents();
	ob_end_clean();
} else {
	ob_end_flush();
}
