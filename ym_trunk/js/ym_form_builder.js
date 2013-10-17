
var logic_index = 0;

jQuery(document).ready(function() {
	if (jQuery('#flow_pages_flow').size()) {
		jQuery('#flow_pages_flow, #flow_pages_source').sortable({
			connectWith:			'.flow_page_creator',
			placeholder:			'ui-state-highlight',
			grid:					[5, 5],
			forcePlaceholderSize:	true
		}).disableSelection();
		
		jQuery('#flowcreatepageform').click(function() {
			var order = jQuery('#flow_pages_flow').sortable('serialize');
			jQuery('#flow_controller').val(order);
			jQuery('#flowcreatepageform').parents('form').submit();
		});
	}
	
	if (jQuery('#ym_available_form_elements_tabs').size()) {
		jQuery('#ym_available_form_elements_tabs').tabs();
		
		jQuery('.ym_available_form_elements').click(function() {
			var copy = jQuery('#source_' + jQuery(this).attr('id')).html();
			var triggerlogic = false;
			
			if (jQuery(this).attr('id') == 'block_logic') {
				logic_index++;
				var element = '<div class="ym_form_element is_logic_block" logicindex="' + logic_index + '">' + copy + '</div>';
				triggerlogic = true;
			} else {
				var element = '<div class="ym_form_element">' + copy + '</div>';
			}
			
			
			jQuery(element).hide().appendTo('#ym_form_builder').slideDown();
			
			if (jQuery(this).attr('single') == 1) {
				jQuery(this).attr('disabled', 'disabled');
				jQuery(this).attr('readonly', 'readonly');
				jQuery(this).addClass('ym_reg_flow_disabled');
			}
			
			if (triggerlogic) {
				jQuery('.is_logic_block').trigger('logicblockspawned');
			}

			jQuery(':disabled').disableSelection();
		});
		
		jQuery('.is_logic_block').live('logicblockspawned', function() {
			if (!jQuery(this).find('.logicindex').val()) {
				jQuery(this).find('.logicindex').val(logic_index);
			}
			/*
			jQuery(this).find('.blocklogiccontents').sortable({
				connectWith:			'.isconnected',
				placeholder:			'ui-state-highlight',
				grid:					[5, 5],
				forcePlaceholderSize:	true
			}).disableSelection();*/
			ym_setup_sortable();
		});
		
		jQuery('.ym_delete_field').live('click', function() {
			var id = jQuery(this).parents('.ym_form_element:first').find('.thisid').val();
			jQuery('#' + id).removeAttr('disabled');
			jQuery('#' + id).removeAttr('readonly');
			jQuery('#' + id).removeClass('ym_reg_flow_disabled');
			jQuery(this).parents('.ym_form_element:first').slideUp(function() {
				jQuery(this).remove();
			});
		});
		
		ym_setup_sortable();
		
		jQuery('#munch').click(function() {
			jQuery('.is_logic_block .then').each(function() {
				jQuery(this).find('.iflogic_parent').first().val(jQuery(this).parents('.is_logic_block').first().attr('logicindex'));
				jQuery(this).find('.iflogic_logic').first().val('then');
			});
			jQuery('.is_logic_block .else').each(function() {
				jQuery(this).find('.iflogic_parent').first().val(jQuery(this).parents('.is_logic_block').first().attr('logicindex'));
				jQuery(this).find('.iflogic_logic').first().val('else');
			});
			
			jQuery('input[type="checkbox"]').each(function() {
				if (jQuery(this).attr('checked')) {
					var thechecked = jQuery(this).attr('checked');
				} else {
					var thechecked = '';
				}
				
				var element = '<input type="text" name="' + jQuery(this).attr('name') + '" value="' + thechecked + '" />';
				jQuery(this).replaceWith(element);
			});
			jQuery('input').each(function() {
				jQuery(this).removeAttr('disabled');
				jQuery(this).removeAttr('readonly');
				jQuery(this).removeClass('ym_reg_flow_disabled');

			});
			
			jQuery('#generateform').submit();
		});
	}
	
	jQuery('.iflogic').live('change', function() {
		var thisvalue = jQuery(this).val();
		jQuery(this).parents('.iflogiccontrol').find('.logicoption').hide();
		if (thisvalue) {
			jQuery(this).parents('.iflogiccontrol').find('.' + thisvalue).show();
		}
	});
	
	jQuery('.freetextarea').live('focus', function() {
		jQuery(this).attr('rows', 10);
	});
	jQuery('.freetextarea').live('blur', function() {
		jQuery(this).attr('rows', 3);
	});
});

function ym_setup_sortable() {
	jQuery('.isconnected').sortable({
		connectWith:			'.isconnected',
		placeholder:			'ui-state-highlight',
		grid:					[5, 5],
		forcePlaceholderSize:	true,
		start:					function(event, ui) {
									jQuery('.isconnected').css('background', 'yellow');
								},
		stop:					function(event,ui) {
									jQuery('.isconnected').css('background', '#FFFFFF');
								}
	}).disableSelection();
}
