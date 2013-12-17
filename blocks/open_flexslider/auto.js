ccmValidateBlockForm = function() {
	
	if ($('#field_1_textbox_text').val() == '') {
		ccm_addError('Missing required text: title');
	}

	if ($('#field_2_textbox_text').val() == '') {
		ccm_addError('Missing required text: speed');
	}

	if ($('select[name=field_3_select_value]').val() == '' || $('select[name=field_3_select_value]').val() == 0) {
		ccm_addError('Missing required selection: loop');
	}

	if ($('select[name=field_4_select_value]').val() == '' || $('select[name=field_4_select_value]').val() == 0) {
		ccm_addError('Missing required selection: direction');
	}

	if ($('select[name=field_5_select_value]').val() == '' || $('select[name=field_5_select_value]').val() == 0) {
		ccm_addError('Missing required selection: Controls');
	}

	if ($('select[name=field_6_select_value]').val() == '' || $('select[name=field_6_select_value]').val() == 0) {
		ccm_addError('Missing required selection: Nav');
	}


	return false;
}
