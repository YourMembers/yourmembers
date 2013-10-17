jQuery(document).ready(function() {
	jQuery('.ym_datepicker').each(function() {
		jQuery(this).datepicker({
			dateFormat: ymdateFormat,
			minDate: '-1d'
		});
		jQuery(this).attr('readonly', 'readonly');
	});
	jQuery('.ym_yearpicker').each(function() {
		jQuery(this).datepicker({
			numberOfMonths: [2, 3],
			showButtonPanel: true,
			dateFormat: ymdateFormat,
			minDate: '-1d'
		});
		jQuery(this).attr('readonly', 'readonly');
	});

	jQuery('.ym_datepicker_checkbox').each(function() {
		jQuery(this).click(function() {
			jQuery('#cbExpireDate').attr('checked', 'checked');
		});
	});
});

function ym_clear_target(target) {
	jQuery('#' + target).val('');
}
