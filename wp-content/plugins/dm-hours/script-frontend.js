jQuery('document').ready(function ($) {
	$('#market-hours-link').hover(function () {
		var pos = $(this).position();
		var hoursBlock = $(this).parent().nextAll('div#market-hours').first();
		hoursBlock.css('display', 'block')
		hoursBlock.css('position', 'absolute')
		hoursBlock.css('z-index', '1')
		hoursBlock.css('left', pos.left - $(this).width() + 50)
		hoursBlock.css('top', pos.top + 30);
	}, function () {
		var hoursBlock = $(this).parent().nextAll('div#market-hours').first();
		hoursBlock.hide();
	});
	$('#market-hours-link').click(function (e) {
		e.preventDefault();
	});
});