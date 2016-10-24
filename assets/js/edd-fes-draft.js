(function($) {
	if($('form[name="fes-submission-form"]').length != 0) {
		$('#edd-fes-draft-button').click(function () {
			var form = $('form[name="fes-submission-form"]');

			if (form.find('input[type="hidden"][name="save_draft"]').length == 0) {
				form.append('<input type="hidden" name="save_draft" value="true" />');
			}

			form.submit();
		});

		if (edd_fes_draft.auto_save) {
			var doing_auto_save;

			$('form[name="fes-submission-form"] input:not(#edd_fes_draft_pending_checkbox), form[name="fes-submission-form"] textarea, form[name="fes-submission-form"] select').change(function () {
				var form = $(this).closest('form');
				var data = validate_form(form);
				var submit_selector = '#edd-fes-draft-button, input[type="submit"]';

				if (data) {
					data += '&save_draft=1';
					data.replace('action=fes_submit_submission_form', 'action=edd_fes_draft_auto_save');

					$('.edd-fes-draft-submit').append('<span class="edd-fes-draft-auto-save-text">Saving...</span>');
					$(submit_selector).attr('disabled', 'disabled').addClass('button-primary-disabled');

					doing_auto_save = true;

					$.post(edd_fes_draft.ajax_url, data, function (response) {
						if(response.post_id !== undefined) {
							window.history.pushState(document.innerHTML, document.title, window.location.origin + window.location.pathname + '?task=edit-product&post_id=' + response.post_id);
							form.find('[name="post_id"]').val(response.post_id);
						} else {
							console.log(response);
						}

						$('.edd-fes-draft-submit .edd-fes-draft-auto-save-text').fadeOut(function () {
							$(this).remove();
						});

						$(submit_selector).removeAttr('disabled').removeClass('button-primary-disabled');

						doing_auto_save = false;
					});
				}
			});

			function validate_form(form) {
				var temp, val,
					form_data = form.serialize(),
					rich_texts = [];

				// Grab rich texts from tinyMCE
				$('.fes-rich-validation').each(function (index, item) {
					temp = $(item).data('id');
					val = $.trim(tinyMCE.get(temp).getContent());
					rich_texts.push(temp + '=' + encodeURIComponent(val));
				});

				// Append them to the form var
				form_data += '&' + rich_texts.join('&');
				return form_data;
			}
		}

		if (edd_fes_draft.pending_checkbox) {
			$('input[type="submit"]').attr('disabled', 'disabled');
			$('#edd_fes_draft_pending_checkbox').change(function () {
				// If is doing an auto save then does not enables the submit button (this prevents duplications)
				if (edd_fes_draft.auto_save && doing_auto_save) {
					return false;
				}

				if ($(this).prop('checked')) {
					$('input[type="submit"]').removeAttr('disabled');
				} else {
					$('input[type="submit"]').attr('disabled', 'disabled');
				}
			});
		}
	}
})(jQuery);