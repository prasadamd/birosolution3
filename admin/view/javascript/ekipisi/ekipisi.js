$(document).ready(function () {
	$('.auto-slide-up')
		.delay(3000)
		.fadeOut(600, function () {
			$(this).show().css({ visibility: 'hidden' });
		})
		.slideUp(600);
});
