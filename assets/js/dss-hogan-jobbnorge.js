/**
 * Adjust heights
 *
 * @author: Per Søderlind / DSS
 * @since: 29/09/2019
 */
jQuery(function ($) {
	$('.hogan-module-jobbnorge').each(function () {
		// Cache the highest
		var highestBox = 0;
		// Select and loop the elements you want to equalise
		$('.list-item', this).each(function () {

			// If this box is higher than the cached highest then store it
			if ($(this).height() > highestBox) {
				highestBox = $(this).height();
			}
		});
		// Set the height of all those children to whichever was highest
		$('.list-item', this).height(highestBox + 20);
	});
});