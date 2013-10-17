jQuery(document).ready(function() {
	jQuery('#ym_members_create_user_form').hide();
	jQuery('#ym_members_create_user_form legend').hide();
	jQuery('#ym_members_create_user').click(function() {
		event.preventDefault();
		jQuery('#ym_members_create_user_form').dialog({
			modal: 1,
			width: 600,
			title: jQuery('#ym_members_create_user_form legend').html()
		});
	});
});
