/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/

function redrawRepeatingItemFields() {
	clearRepeatingItemFields();
	$.each(repeatingItemFields.getFields(), function(i, field) {
		drawRepeatingItemField(field);
	});
}

function clearRepeatingItemFields() {
	$('#repeating-item-fields tr[id!="add-repeating-item-field"]').remove();
}

function drawRepeatingItemField(repeatingItemField) {
	if (!repeatingItemField.isDeleted) {
		var $fieldRow = $('#repeating-item-field-template').tmpl(repeatingItemField).insertBefore('#add-repeating-item-field');
		displayRepeatingItemFieldOptions($fieldRow, repeatingItemField.type, repeatingItemField.options);
		return $fieldRow;
	}
}

function displayRepeatingItemFieldOptions($fieldRow, type, options) {
	var $container = $fieldRow.find('.repeating-item-field-options');
	var $template = $('.repeating-item-field-options-template[data-repeating-item-field-type="' + type + '"]');
	$container.html($template.tmpl(options));
	
	//hacky thing: if a field type is set to something that has any 'select' options,
	// we want to explicitly set those options' values in the data model to the dom element's value.
	//The reason for this is because fields in the data model start out with an empty option set,
	// but <select> controls default to their first value when added to the dom,
	// so if the user never changes the <select> dropdown to another choice
	// then the data model doesn't wind up saving anything for that option
	// (which is the desired behaviour for boolean options, but not for select options
	// -- because then we could wind up with empty strings for their values
	// instead of the actual value, which causes weird problems).
	$container.find('select[name^="options["]').each(function() {
		var id = $(this).closest('tr').attr('data-id');
		var name = $(this).attr('name');
		var val = $(this).val();
		repeatingItemFields.setFieldData(id, name, val);
	});
}

function updateRepeatingItemFieldsDisplayOrders() {
	var i = 1;
	$('#repeating-item-fields tr[id!="add-repeating-item-field"]').each(function() {
		repeatingItemFields.setFieldData($(this).attr('data-id'), 'displayOrder', i++);
	});
}

function addEmptyRepeatingItemField() {
	var field = new RepeatingItemField();
	repeatingItemFields.addField(field);
	var $tr = drawRepeatingItemField(field);
	$tr.find('td').effect("highlight", {}, 500); //row effect interferes with bootstrap style, so run it on the cells instead (see http://stackoverflow.com/a/9490810/477513 )
}

function removeRepeatingItemField(id) {
	repeatingItemFields.setFieldData(id, 'isDeleted', true);
	
	//can't slideUp a table row,
	// so wrap the td's in divs and slide those up (see http://stackoverflow.com/a/3410943/477513 )
	//...but because of padding on td, just sliding up the inner div
	// looks yucky, so also combine with a fade.
	$('#repeating-item-fields tr[data-id="' + id + '"]')
	.find('td')
	.wrapInner('<div style="display: block;" />')
	.parent()
	.fadeTo(200, 0.00, function() { //don't use fadeOut, because then height is 0 afterwards and slideUp won't work (see http://stackoverflow.com/a/734834/477513 )
		$(this)
		.find('td > div')
		.slideUp(100, function() {
			$(this).parent().parent().remove();
		});
	});

}


/*** Event Handlers ***/
$(document).ready(function() {
	
	//add new repeating item
	$('#add-repeating-item-field').on('click', function() {
		addEmptyRepeatingItemField();
		return false;
	});
	
	//drag-n-drop sorting
	$('.sortable-container table tbody').sortable({
		handle: '.sortable-handle',
		axis: 'y',
		containment: '.sortable-container', //contain to a wrapper div (not the <tbody> itself), so there's room for dropping at top and bottom of list
		items: 'tr[id!="add-repeating-item-field"]',
		tolerance: 'pointer', //so dragging an item to the top row properly budges down the existing top item
		cursor: 'move',
		update: updateRepeatingItemFieldsDisplayOrders,
		helper: function(event, ui) {
			//prevent cell widths from collapsing while dragging (see http://www.foliotek.com/devblog/make-table-rows-sortable-using-jquery-ui-sortable/ )
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		}
	});
	
	//delete repeating item
	$('#repeating-item-fields').on('click', '.delete-repeating-item-field', function() {
		var id = $(this).closest('tr').attr('data-id');
		removeRepeatingItemField(id);
		return false;
	});
	
	//edit field data
	$('#repeating-item-fields').on('change', 'input, select', function() {
		var $tr = $(this).closest('tr');
		var id = $tr.attr('data-id');
		var name = $(this).attr('name');
		var val = ($(this).attr('type') == 'checkbox') ? $(this).prop('checked') : $(this).val();
		
		//toggle "exclusive" checkboxes (so only one is checked at a time)
		if ((name == 'options[is_item_label]' || name == 'options[is_item_thumb]') && val) {
			$('input[name="' + name + '"]').prop('checked', false);
			$(this).prop('checked', true);
		}
		
		//load a different options sub-template when the field type is changed
		if (name == 'type') {
			var existingOptions = {};
			$tr.find('.repeating-item-field-options').find('input, select').each(function() {
				//watch out for checkboxes -- only pass in value if they're actually checked!
				if ($(this).attr('type') != 'checkbox' || $(this).prop('checked')) {
					existingOptions[$(this).attr('data-option-handle')] = $(this).val();
				}
			});

			displayRepeatingItemFieldOptions($tr, val, existingOptions);
		}
		
		//update data model
		repeatingItemFields.setFieldData(id, name, val);
	});
	
	//form submit
	$('#dcp-dashboard-form').on('submit', function(event) {
  		event.preventDefault(); //stop form from submitting normally
		$(this).block({ //use the blockUI plugin to prevent form changes while we're waiting on ajax calls
			message: '<h1>' + ccm_t('blockUI-message-validating') + '</h1>',
			baseZ: 100
		});
		validate(this);
	});
	
	//initialize tooltips
	/* NOTES:
	 * 1) Twitter bootstrap's "tooltip()" functionality is buggy when you put a delay() on it (they stay open even after you hover away -- if you hover away onto another element that has a tooltip).
	 * 2) So we are using the "tipsy" library instead. (Note that we tweaked the font in tipsy.css).
	 * 3) We cannot use the classes "tooltip" or "tipsy" as a selector because that messes up the style of the element we put it on!
	 */
	$('.dcp-tooltip').tipsy({
		delayIn: 750,
		fade: true,
		gravity: 's', //position tooltip above the element
		html: true, //render line breaks (<br>) in titles
		live: true //automatically apply tooltip to new repeating item rows that are dynamically added to the DOM
	});
});


/*** Form Processing ***/
function validate(form) {
	//hide and clear any prior error messages
	$('#dcp-validation-errors').hide().html('');
	
	//validate data (using data from the data model, not the DOM)
	var data = combine_data_for_post(form);
	safe_post(action_validate, data, function(response) {
		if (response.success) {
			display_confirmation(data, form);
		} else {
			$(form).unblock();
			display_errors(response.errors);
		}
	});
}

//Gathers header data and CSRF token from the form elements, and repeating item data from the data model
function combine_data_for_post(form) {
	var data = {};
	
	$.each(['btHandle', 'btName', 'btDescription'], function(i, name) {
		data[name] = form[name].value;
		data['orig_' + name] = form['orig_' + name].value;
	});
	
	data['repeating_items'] = repeatingItemFields.getFields(true); //pass true to get booleans as either integer 1 (for true) or non-existent (for false) -- otherwise they get posted as strings ("true"/"false") which php always casts to TRUE [because non-empty strings are always TRUE -- even the string "false"]
	data['orig_repeating_items'] = orig_repeatingItemFields.getFields(true); //ditto
	
	data.ccm_token = form.ccm_token.value;
	
	return data;
}

//Does an ajax POST that hopes to receive JSON as a result,
// but if JSON isn't received then we display the response
// as raw text in an error message to the user.
function safe_post(url, data, callbackOnSuccess) {
	//EXPLANATION: The reason we go through this trouble
	// (instead of just specifying "json" as the ajax dataType)
	// is because if C5 has some kind of error that's outside our control
	// (e.g. user is logged out, CSRF token is invalid, etc.)
	// then we need to be able to access the raw text of the response
	// so we can show it to the user -- but if you specify the 'json'
	// dataType to $.ajax or $.post, then non-JSON responses are ignored.
	
	//TECHNIQUE: Note that we're doing 2 tricky things here:
	// 1) putting the call to $.parseJSON() in a try/catch block,
	//    so we can do something if parsing fails
	//    (see http://css-tricks.com/snippets/jquery/jquery-json-error-catching/).
	// 2) putting the try/catch block in a do/while loop,
	//    so it doesn't catch exceptions in the callback function
	//    (see http://stackoverflow.com/a/6534096/477513).
	
	$.post(url, data, function(maybe_json) {
		do {
			try {
				var response = $.parseJSON(maybe_json);
				break;
			} catch(e) {
				alert('ERROR: ' + maybe_json); //parseJSON failed, so this isn't actually JSON data (probably a C5 error, so just show the raw text to the user)
				return;
			}
		} while (false);
		
		callbackOnSuccess(response);
	});
}

function display_errors(errors) {
	var $container = $('#dcp-validation-errors');
	var html = errors.join('<br>');
	$container.html(html);
	$('html, body').animate({ scrollTop: 0 }); //don't bother with scrolling to $container.offset().top ... it isn't working right for some reason (maybe because of C5 toolbar? maybe because of dynamically-populated DOM elements in the repeating items list? I dunno)
	$container.slideDown();
}

var $confirmation = null;
var dialogWasClosedFromSave = false; //this flag lets the dialog close event know if it should un-block the UI or not
function display_confirmation(data) {
	safe_post(action_confirm, data, function(response) {
		if (response.isNew) {
			save(data); //no need to confirm changes for a new blocktype
		} else {
			var tmplData = {
				'installed': response.installed,
				'usage': response.usage,
				'changes': response.changes,
				'reindex': $.map(response.changes, function(change) { return change.reindex ? true : null; }).length
			};
			
			$confirmation = $('#confirmation-template').tmpl(tmplData);
			
			$confirmation.dialog({
				'width': '95%',
				'modal': true,
				'open': function(event, ui) {
					//remove default focus from links/buttons
					$('.ui-dialog-content input, .ui-dialog-content a').blur();
					
					//scroll to top in case dialog is extremely tall
					var scrolledTo = $(window).scrollTop();
					var dialogTop = $('.dcp-confirmation-container').offset().top;
					if (scrolledTo > dialogTop) {
						window.scrollTo(0, (dialogTop - 80));
					}
				},
				'close': function() {
					$(this).dialog('destroy');
					//completely eradicate this dialog from the DOM, otherwise the scrollTo stuff doesn't work the next time it's opened (e.g. if user cancels/closes this one, modifies their changes, then re-submits)
					//note that $(this).dialog('destroy').remove() (which seems to be the recommended way to achieve this) doesn't work for some reason
					$('.dcp-confirmation-container').remove();
					
					if (!dialogWasClosedFromSave) {
						$('#dcp-dashboard-form').unblock();
					}
					dialogWasClosedFromSave = false; //reset this for next time
				}
			});
			$('.ui-dialog-titlebar').hide();
		}
	});
}

function cancel_from_confirmation() {
	$confirmation.dialog('close');
}

function save_from_confirmation() {
	dialogWasClosedFromSave = true;
	$confirmation.dialog('close');
	var form = $('#dcp-dashboard-form').get(0);
	var data = combine_data_for_post(form);
	save(data);
}

function save(data) {
	$('#dcp-dashboard-form .blockMsg').html('<h1>' + ccm_t('blockUI-message-saving') + '</h1>');
	$.post(action_save, data, function(response) {
		if (response.length) {
			$('#dcp-dashboard-form').unblock();
			display_errors([response]);
		} else {
			location.href = url_after_save;
		}
	});
}