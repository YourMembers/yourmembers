
jQuery(document).ready(function() {
	jQuery('.menu').xyMenu({
		speed: 100
	});
	
	jQuery('#ym_fbook_nav .menu').hide();
	jQuery('#ym_fbook_nav').each(function() {
		jQuery(this).prepend('<ul id="ym_fbook_nav_top"><li><a href="#nowhere">Open Nav</a></li></ul>');
	});
	jQuery('#ym_fbook_nav').mouseenter(function() {
		jQuery('#ym_fbook_nav .menu').slideDown();
		clearTimeout(timeouthandler);
	});
	jQuery('#ym_fbook_nav').mouseleave(function() {
		timeouthandler = setTimeout('bindleave()', 1000);
	});
});
function bindleave() {
	jQuery('#ym_fbook_nav .menu').slideUp();
};
