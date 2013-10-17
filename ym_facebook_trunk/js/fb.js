
var timeouthandler;
jQuery(document).ready(function() {
	jQuery('.ym_fbook_logout').click(function() {
		FB.logout(function(response) {
			jQuery('#ym_fb_loggedoutform').submit();
		});
	});
	jQuery('.ym_leave_facebook_link').click(function() {
		top.location.href="?leavefacebook=1";
	});
});
