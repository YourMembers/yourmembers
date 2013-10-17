jQuery(document).ready(function() {
	if (jQuery('.ymhandlediv').size()) {
		jQuery('.ymhandlediv').click(function() {
			jQuery(this).parents('.postbox').children('.inside').toggle();
		});
		jQuery('.ym_postbox').mouseenter(function() {
			jQuery(this).find('.ymhandlediv').show();
		}).mouseleave(function() {
			jQuery(this).find('.ymhandlediv').hide();
		});
	}
	jQuery('.deletelink').click(function() {
		if (confirm('Are you sure?')) {
			return true;
		} else {
			return false;
		}
	});
	jQuery('.ym_packetize_show').find('.ym_packetize_packet').hide();
	jQuery('.ym_packetize_show').click(function() {
		var node = jQuery(this).find('.ym_packetize_packet').clone();
		node.prependTo('body');
		jQuery(node).dialog({
			modal: true,
			width: 600,
			height: 600,
			close: function(event, ui) {
				jQuery(node).remove();
			}
		});
	});
	if (jQuery('.fade').size()) {
		setTimeout('ym_fade_out()', 5000);
	}

	jQuery('.ym_dialog_form_submit').click(function(event) {
		event.preventDefault();
		var thetitle = jQuery(this).parents('tr').find('td').first('td').find('span').html();
		jQuery('<div><iframe id="ym_dialog_form_submit_target" name="ym_dialog_form_submit_target" style="width: 400px; height: 400px;"></iframe></div>').dialog({
			modal: 1,
			width: 430,
			title: thetitle,
			close: function(event, ui) {
				jQuery(this).remove();
			}
		});
		jQuery(this).parents('form').attr('target', 'ym_dialog_form_submit_target');
		jQuery(this).parents('form').submit();
	});

	jQuery('.ym_select_all').click(function() {
		if (jQuery(this).attr('checked') == 'checked') {
			jQuery('.' + jQuery(this).attr('data-target')).attr('checked', true);
			jQuery('.ym_select_all').attr('checked', true);
		} else {
			jQuery('.' + jQuery(this).attr('data-target')).attr('checked', false);
			jQuery('.ym_select_all').attr('checked', false);
		}
	})
});

function ym_fade_out() {
	jQuery('.fade').animate({opacity: 0, height: 0}, 1500, function() {
		jQuery(this).remove();
	});
}
