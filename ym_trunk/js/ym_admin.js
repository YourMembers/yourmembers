jQuery(document).ready(function() {
	jQuery('#ym_graph_nav a').click(function() {
		if (jQuery('#ym_graph_' + jQuery(this).attr('href').replace('#', '') + ':hidden').length) {
			jQuery('#ym_graph_holder img:visible').slideUp();
			jQuery('#ym_graph_' + jQuery(this).attr('href').replace('#', '')).slideDown();
		}
	})
	jQuery('#ym_graph_holder img').slideUp();
	jQuery('#ym_graph_holder img:first').slideDown();

	if (jQuery('.ym_migrate_table_div').size()) {
		jQuery('.ym_migrate_table_div').each(function() {
			var Id = jQuery(this).get(0).id;
			
			jQuery('#' + Id + ' .ym_migrate_table').fixedTable({
				fixedColumns: 1,
				fixedColumnWidth: 200,
				
				width: jQuery('#masspostsetuptabs').width() - 100,
				height: 450,
				
				classHeader: 'fixedHead',
				classFooter: 'fixedFoot',
				classColumn: 'fixedColumn',
				outerId: Id,
				Contentbackcolor: '#FFFFFF',
				Contenthovercolor: '#99CCFF',
				fixedColumnbackcolor: '#FFFFFF',
				fixedColumnhovercolor: '#99CCFF',
			});
		});
	}
	jQuery('.ymselectpost').click(function() {
		var id = jQuery(this).get(0).id;
		id = id.split('_');
		id = id[1];
		jQuery('#post_' + id).attr('checked', 'checked');
	});
	jQuery('.ymselectpage').click(function() {
		var id = jQuery(this).get(0).id;
		id = id.split('_');
		id = id[1];
		jQuery('#page_' + id).attr('checked', 'checked');
	});
	jQuery('.ymselectcategory').click(function() {
		var id = jQuery(this).get(0).id;
		id = id.split('_');
		id = id[2];
		jQuery('#category_' + id).attr('checked', 'checked');
	});
	
	jQuery('#toplevel_page_' + ymadminfunction + ' .wp-submenu li a').each(function () {
		jQuery(this).click(function() {
			tabid = jQuery(this).attr('href');
			tabid = tabid.split('ym_tab=');
			tabid = tabid[1];
			if (tabid) {
				ym_tab_change(tabid);

				jQuery('#' + ymadminfunction).tabs('select', parseInt(tabid));
				return false;
			}
		});
	});
	jQuery('#wp-admin-bar-' + ymadminfunction + '_adb ul li a').each(function () {
		jQuery(this).click(function() {
			tabid = jQuery(this).attr('href');
			tabid = tabid.split('ym_tab=');
			tabid = tabid[1];
			if (tabid) {
				jQuery('#' + ymadminfunction).tabs('select', parseInt(tabid));

				ym_tab_change(tabid);

				return false;
			}
		});
	});

	if (jQuery('#filter_by_option').size()) {
		jQuery('.filter_by_text').hide();
		var name = 'filter_by_text_' + jQuery('#filter_by_option').val();
		jQuery('.' + name).show();

		jQuery('#filter_by_option').change(function() {
			jQuery('.filter_by_text').hide();
			var val = jQuery(this).val();
			if (val) {
				var name = 'filter_by_text_' + jQuery(this).val();
				jQuery('.' + name).show();
			}
		});
	}

	jQuery('#search_user_name').suggest(ajaxurl + '?action=ym_logs_search_users');
});

function ym_tab_change(tabid) {
	var tabid = parseInt(tabid);
	jQuery('#toplevel_page_' + ymadminfunction + ' .wp-submenu li').each(function() {
		jQuery(this).removeClass('current');
	});
	if (tabid != 0) {
		jQuery('#toplevel_page_' + ymadminfunction + ' .wp-submenu li a[href^="admin.php?page=' + ymadminfunction + '&ym_tab=' + tabid + '"]').parents('li').addClass('current');
	} else {
		jQuery('#toplevel_page_' + ymadminfunction + ' .wp-submenu li .wp-first-item').parents('li').addClass('current');
	}
}
