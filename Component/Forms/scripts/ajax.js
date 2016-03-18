$(function() {
	forms_initializeAjaxForms();
});

function forms_initializeAjaxForms() {
	$("form[data-ajax-form]").each(function(){
		forms_initializeAjaxForm(this);
	});	
}

function forms_initializeAjaxForm(form) {
	$(form).ajaxForm({success: forms_onAjaxSubmitSuccess});
}

function forms_onAjaxSubmitSuccess(data, status, xmh, form) {
	if(status == 'success') {
		window.TAO = window.TAO || {};
		window.TAO.helpers = window.TAO.helpers || {};
		var container = $(form).parents('[data-form-container]');
		var newHtml = $(data).html();
		$(container).html(newHtml);
		var newForm = $(container).find('form');
		if(typeof(TAO.helpers.form_validation) != 'undefined')
			TAO.helpers.form_validation(newForm);
		forms_initializeAjaxForm(newForm);
	} else {
		$(form).parent().html('При отправке формы произошла ошибка.');
	}
}