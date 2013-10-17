jQuery(document).ready(function() {
	if (jQuery('#sourcerow').size()) {
		jQuery('#sourcerow').hide();
		jQuery('#addlink').click(function() {
//			jQuery('#sourcerow').parents('table').children('tbody').append(jQuery('#sourcerow').html());
			jQuery('#sourcerow').show();
			jQuery('#sourcerow input:first').focus();
			jQuery(this).hide();
			return false;
		});
	}
	
	jQuery('.editorContent').each(function() {
			jQuery(this).attr('id', 'editorContent_' + jQuery(this).attr('name'));
			jQuery(this).parents('td').prepend('<p align="right"><a class="button" id="toggleVisual' + jQuery(this).attr('name') + '">Visual</a><a class="button" id="toggleHTML' + jQuery(this).attr('name') + '">HTML</a></p>');
			
			jQuery('#toggleVisual' + jQuery(this).attr('name')).click(function() {
				tinyMCE.execCommand('mceAddControl', false, 'editorContent_' + jQuery(this).attr('id').substr(12));
			});

			jQuery('#toggleHTML' + jQuery(this).attr('name')).click(function() {
				tinyMCE.execCommand('mceRemoveControl', false, 'editorContent_' + jQuery(this).attr('id').substr(10));
			});
	});
	
	jQuery('.previewemail').click(function() {
		url = jQuery(this).attr('href');
		jQuery('<div><iframe style="width: 900px; height: 100%;" src="' + url + '" /></div>').dialog({
			draggable: false,
			modal: true,
			resizable: false,
			title: jQuery(this).html(),
			width: 930,
			height: 600
		});
		return false;
	});
	
	jQuery('.deletecheck').submit(function() {
		theoption = jQuery(this).find('select').val();
		if (theoption == 'delete') {
			if (confirm('Are you sure?')) {
				jQuery(this).find('select').replaceWith('<input type="hidden" name="action" value="deletego" />');
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	});
});
