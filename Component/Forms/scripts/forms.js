$(function () {
	var placeholder_support = !!('placeholder' in document.createElement('input'));
	if (!placeholder_support) {
		$('[placeholder]').each(function () {
			var input = $(this);
			var placeholder = $('<div class="form-placeholder" style="position:absolute;overflow:hidden;"/>')
				.appendTo(input.offsetParent()).text(input.attr('placeholder')).addClass(input.attr('class'));

			initializePlaceholderStyle(placeholder, input);

			placeholder.bind('click focus', function () {
				input.focus();
			});
			input.bind('keydown', function () {
				placeholder.hide();
			});
			input.bind('keyup change', function () {
				if($(this).val() == '')
					placeholder.show();
				else
					placeholder.hide();
			});

			input.bind('blur', function () {
				if (input.val() == '') placeholder.show()
			});

			function placeholderOnWindowResize() {
				initializePlaceholderStyle(placeholder, input);
			}

			$(window).resize(placeholderOnWindowResize);

		});

	}
});

function initializePlaceholderStyle(placeholder, input) {
	placeholder.css({
		top: input.offset().top - input.offsetParent().offset().top,
		left: input.offset().left - input.offsetParent().offset().left,
		width: input.width(),
		height: input.height(),
		padding: ((input.outerHeight() - input.height()) / 2) + 'px ' + ((input.outerWidth() - input.width()) / 2) + 'px'
	})
}