jQuery(document).ready(function() {
	jQuery('#ym_firesale_tier_source').hide();
	var source = '';
	jQuery('#ym_firesale_tier_source').find('tr').each(function() {
		source = source + '<tr>' + jQuery(this).html() + '</tr>';
	});
	
	jQuery('#ym_firesale_addtier').click(function() {
		jQuery(source).appendTo(jQuery(this).parents('table'));
	});
	jQuery('.ym_firesale_add_post_tier').click(function() {
		jQuery('#ym_firesale_add_post_tiers_form').show();
		tierid = jQuery(this).attr('id');
		jQuery(source).appendTo('#ym_firesale_add_post_tiers');
		jQuery('#ym_firesale_add_post_tiers_fire_id').val(tierid);
	});
	
	jQuery('.ym_showtiers').click(function() {
		jQuery('.' + jQuery(this).attr('id')).toggle();
	});
});
