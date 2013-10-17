/**
 * jQuery yMenu
 *
 * @url         http://www.mewsoft.com/jquery/yMenu/
 * @author      Dr. Ahmed Amin Elsheshtawy, Ph.D. <sales@mewsoft.com>
 * @version     1.0
 * @date        02.07.2011
 * @Copyright 2011 Mewsoft Corp. http://www.mewsoft.com
 * @Released under the MIT and GPL licenses.
 */

(function ($) {
    $.fn.xyMenu = function (options) {

        var defaults = {
            speed: 300,
            folderclass: 'ymenu-folder',
			folderclasshover: 'ymenu-folder-hover'
        };

        var options = $.extend(defaults, options);

        return this.each(function () {
            var obj = $(this);
            if (obj.length < 1) return false;
            
			// Opera Fix
            $("ul ul", obj).css({
                display: "none"
            });

            $("ul ul", obj).each(function () {
				//loop through all sub menus again, and use "display:none" to hide menus (to prevent possible page scrollbars)
                $(this).css({
                    visibility: "hidden",
                    display: "none"
                });
            });
			
			//add the folder arrow classes
            $("ul ul", obj).each(function () {
				var li = $(this).parent();
				
				//add the link arrow class
				$(">a", li).addClass(options.folderclass);
				
				//add the link hover arrow classes
				$(">a", li).hover(function () {
					$(this).removeClass(options.folderclass).addClass(options.folderclasshover); //mouse over
				},
					function (){
					$(this).removeClass(options.folderclasshover).addClass(options.folderclass); //mouse out
				});
            });
			
			//add the menu items hover actions
            $("ul li", obj).hover(function () {
                $(this).find('ul:first').css({
                    visibility: "visible",
                    display: "none"
                }).show(options.speed); //'fast'=200, 'slow'=600
            }, function () {
                $(this).find('ul:first').css({
                    visibility: "hidden",
                    display: "none"
                });
            });

        }); //return this.each(function() {
    };//$.fn.xyMenu
})(jQuery);
