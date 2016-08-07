/**
 * Off-Canvas Sidebars plugin fixed-scrolltop.js
 *
 * Compatibility for fixed elements with Slidebars
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package off-canvas-slidebars
 * @version 0.1.2
 */
;( function ( $ ) {
	
	$(window).load(function () {
		
		if ($('#sb-site').css('transform') != 'none') {
			var curScrollTopElements = $('#sb-site *').filter(function(){ return $(this).css('position') === 'fixed' });
			ocsScrollTopFixed();
			$(window).on('scroll resize', function() {
				curScrollTopElements;
				var newScrollTopElements = $('#sb-site *').filter(function(){ return $(this).css('position') === 'fixed' });
				curScrollTopElements = curScrollTopElements.add(newScrollTopElements);
				ocsScrollTopFixed();
			});
		}
		
		function ocsScrollTopFixed() {
			curScrollTopElements;
			if (curScrollTopElements.length > 0) {
				var scrollTop = $(window).scrollTop();
				var winHeight = $(window).height();
				var conOffset = $('#sb-site').offset();
				var conHeight = $('#sb-site').outerHeight();
				curScrollTopElements.each(function(){
					if ($(this).css('position') == 'fixed') {
						var top = $(this).css('top');
						var bottom = $(this).css('bottom');
						if ( top == 'auto' && bottom != 'auto' ) {
							var px = (scrollTop + winHeight) - (conOffset.top + conHeight);
						} else {
							var px = scrollTop - conOffset.top;
						}
						$(this).css('-webkit-transform', 'translateY('+px+'px)');
						$(this).css('-moz-transform', 'translateY('+px+'px)');
					} else {
						$(this).css({'-webkit-transform' : ''});
						$(this).css({'-moz-transform' : ''});
					}
				});
			}
		}
		
	});
	
} ) ( jQuery );