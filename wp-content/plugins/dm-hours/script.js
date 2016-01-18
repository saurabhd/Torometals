
jQuery(document).ready(function($) {

// to be called on .time - checks the checkbox and
// decides whether fiztrade or manual times should be shown
$.fn.toggleTimeInput = function() {
	// true if there is a checked box, false otherwise
	var useFT = $('input[type="checkbox"]:checked', this).length;
	
	if (useFT) {
		$('.fiztrade-hours', this).show();
	}
	else {
		$('.fiztrade-hours', this).hide();
	}
};

$('.time input[type="text"]').timepicker({ scrollbar: true });

$('input[type="submit"]').on('click', function (e) {
	var valid = true;
	$('.time input[type="text"]')
	.css('border-color', '#AAA')
	.each(function () {
		if (isNaN( Date.parse('1/1/1970 ' + $(this).val()) ) && $(this).val() != '') {
			//console.log(Date.parse($(this).val()));
			$(this).css('border-color', 'red');
			valid = false;
		}			
	});
	if (!valid) {
		e.preventDefault();
		alert('Please ensure all time fields are empty or contain valid times.');
	}
});

$('.time input[type="checkbox"]').change(function () {
	$(this).parents('.time').toggleTimeInput();
	
	// check if we need to swap to unselect
	var nUnchecked = $(this).parents('.primary').find('.time input[type="checkbox"]').not(':checked').length;
	if (nUnchecked == 0) {
		$(this).parents('.primary').find('.select-all.check').hide();
		$(this).parents('.primary').find('.select-all.uncheck').show();
	}
}).change();

$('.select-all.check').click(function () {
	// select all checkboxes in this section
	$(this).parent().find('.time input[type="checkbox"]').each(function () {
		$(this).attr('checked', 'checked');
		$(this).change();
	});
	
	// swap to unselect
	$(this).hide();
	$(this).parent().find('.select-all.uncheck').show();
});
$('.select-all.uncheck').click(function () {
	// unselect all checkboxes in this section
	$(this).parent().find('.time input[type="checkbox"]').each(function () {
		$(this).removeAttr('checked');
		$(this).change();
	});
	
	// swap to select
	$(this).hide();
	$(this).parent().find('.select-all.check').show();
});
});
