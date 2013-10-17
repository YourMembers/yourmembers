jQuery(document).ready(function() {
	jQuery('#ym_pack_views').change(function() {
		ym_do_pack_views();
	});
	ym_do_pack_views();

	jQuery('.new_account_type_entry').hide();
	if (jQuery('.account_type_selector').size()) {
		jQuery('.account_type_selector').change(function() {
			if (jQuery(this).val() == 'new') {
				jQuery(this).parents('table').find('.new_account_type_entry').show();
			} else {
				jQuery(this).parents('table').find('.new_account_type_entry').hide();
			}
		})
	}

	jQuery('.ym_inherit_mode_off').hide();
	if (!jQuery('#ym_inherit_mode').attr('checked')) {
		jQuery('.ym_inherit_mode_off').show();
	}
	jQuery('#ym_inherit_mode').click(function() {
		if (jQuery(this).attr('checked')) {
			jQuery('.ym_inherit_mode_off').hide();
		} else {
			jQuery('.ym_inherit_mode_off').show();
		}
	});
});


function ym_do_pack_views() {
	theval = jQuery('#ym_pack_views option:selected').val();
	if (theval == 0) {
		jQuery('.introtexts').hide();
		jQuery('.basic_text').show();
		jQuery('.basic_with_trial').hide();
		jQuery('.advanced').hide();
	}
	if (theval == 1) {
		jQuery('.introtexts').hide();
		jQuery('.basic_with_trial_text').show();
		jQuery('.basic_with_trial').show();
		jQuery('.advanced').hide();
	}
	if (theval == 2) {
		jQuery('.introtexts').hide();
		jQuery('.advanced_text').show();
		jQuery('.basic_with_trial').show();
		jQuery('.advanced').show();

		jQuery('.ym_inherit_mode_off').hide();

		if (!jQuery('#ym_inherit_mode').attr('checked')) {
			jQuery('.ym_inherit_mode_off').show();
		}
	}
}
