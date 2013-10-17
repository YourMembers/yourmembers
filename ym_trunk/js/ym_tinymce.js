(function() {
	tinymce.create('tinymce.plugins.ym_private', {
			init : function(ed, url) {
					ed.addButton('ym_private', {
							title : 'Add Private Tags',
							image : url+'/../images/tinymce/private.png',
							onclick : function() {
									var text = ed.selection.getContent({ 'format' : 'text' });
									ed.execCommand('mceInsertContent', false, '[private]' + text + '[/private]');
							}
					});
			},
			createControl : function(n, cm) {
					return null;
			},
			getInfo : function() {
					return {
							longname : 'Add Private Tags',
							author : 'Coding Futures',
							authorurl : 'http://codingfutures.co.uk',
							infourl : 'http://codingfutures.co.uk',
							version : "1.1"
					};
			}
	});
	tinymce.PluginManager.add('ym_private', tinymce.plugins.ym_private);

	tinymce.create('tinymce.plugins.ym_no_access', {
			init : function(ed, url) {
					ed.addButton('ym_no_access', {
							title : 'Add No Access Tags',
							image : url+'/../images/tinymce/no_access.png',
							onclick : function() {
									var text = ed.selection.getContent({ 'format' : 'text' });
									ed.execCommand('mceInsertContent', false, '[no_access]' + text + '[/no_access]');
							}
					});
			},
			createControl : function(n, cm) {
					return null;
			},
			getInfo : function() {
					return {
							longname : 'Add No Access Tags',
							author : 'Coding Futures',
							authorurl : 'http://codingfutures.co.uk',
							infourl : 'http://codingfutures.co.uk',
							version : "1.1"
					};
			}
	});
	tinymce.PluginManager.add('ym_no_access', tinymce.plugins.ym_no_access);

	tinymce.create('tinymce.plugins.ym_user_has_access', {
			init : function(ed, url) {
					ed.addButton('ym_user_has_access', {
							title : 'Add Has Access Tags',
							image : url+'/../images/tinymce/user_has_access.png',
							onclick : function() {
									var text = ed.selection.getContent({ 'format' : 'text' });
									ed.execCommand('mceInsertContent', false, '[user_has_access]' + text + '[/user_has_access]');
							}
					});
			},
			createControl : function(n, cm) {
					return null;
			},
			getInfo : function() {
					return {
							longname : 'Add User Has Access Tags',
							author : 'Coding Futures',
							authorurl : 'http://codingfutures.co.uk',
							infourl : 'http://codingfutures.co.uk',
							version : "1.1"
					};
			}
	});
	tinymce.PluginManager.add('ym_user_has_access', tinymce.plugins.ym_user_has_access);
})();
