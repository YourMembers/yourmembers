var originalvalue = '';

jQuery(document).ready(function() {
	jQuery('.ym_ajax_error').hide();
	jQuery('.ym_form_submit_clone').click(function() {
		jQuery('<form id="ajax_clone"></form>').prependTo('body');
		jQuery('#ajax_clone').html(jQuery(this).parents('.ym_ajax_call').clone());
		data = jQuery('#ajax_clone').serializeArray();
		jQuery('#ajax_clone').remove();

		target = jQuery(this);

		ym_ajax_make_call(target, data, jQuery(this).attr('data-html'));
	});

    //Serialize the data

	jQuery('.ym_form_submit').click(function() {
		data = jQuery(this).parents('.ym_ajax_call').serializeArray();
		target = jQuery(this);//.parent();

		ym_ajax_make_call(target, data, jQuery(this).attr('data-html'));
	});

	jQuery('.ym_form_submit_select').focus(function() {
		originalvalue = jQuery(this).val();
	}).change(function() {
		data = jQuery(this).parents('.ym_ajax_call').serializeArray();
		target = jQuery(this);
		ym_ajax_make_call(target, data, jQuery(this).attr('data-html'));
	});

	jQuery('.ym_form_submit_prompt').click(function() {
		var howmany = prompt('How Many');
		jQuery(this).parents('.ym_ajax_call').find('.ym_ajax_prompt_value').val(howmany);
		data = jQuery(this).parents('.ym_ajax_call').serializeArray();
		target = jQuery(this);
		ym_ajax_make_call(target, data, jQuery(this).attr('data-html'));
	})
});

function ym_ajax_make_call(target, data, dohtml) {
	event.preventDefault();

	jQuery(target).addClass('ym_ajax_loading_image');

	jQuery('.ym_ajax_error').slideUp();

	jQuery.post(ajaxurl, data, function(response) {
		jQuery(target).removeClass('ym_ajax_loading_image');

		if (response == '0') {
			if (originalvalue) {
				target.val(originalvalue);
				originalvalue = false;
			}
			target.parent().addClass('ym_ajax_failed');

			jQuery('.ym_ajax_error').slideDown();
		} else {
			target.parent().addClass('ym_ajax_success');

			target.parent().find('.ym_tick').removeClass('ym_tick').addClass('ym_crossed');
			target.parent().find('.ym_cross').removeClass('ym_cross').addClass('ym_tick');
			target.parent().find('.ym_crossed').removeClass('ym_crossed').addClass('ym_cross');

			target.parent().find('.ym_accept').removeClass('ym_accept').addClass('ym_canceled');
			target.parent().find('.ym_cancel').removeClass('ym_cancel').addClass('ym_accept');
			target.parent().find('.ym_canceled').removeClass('ym_canceled').addClass('ym_cancel');
		}
		target.parent().delay(1500).animate({
			backgroundColor: '#FFFFFF'
		}, function() {
			jQuery(this).removeClass('ym_ajax_success');
			jQuery(this).removeClass('ym_ajax_failed');
			target.parent().attr('style', '');
		});

		if (dohtml == "1" && response != '1') {
			target.html(response);
		}
	});
}
