/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/

//variables sent by php
var editRepeatingItemDialogURL = ccm_t('editRepeatingItemDialogURL');
var repeatingItemLabelField = ccm_t('repeatingItemLabelField');
var repeatingItemThumbField = ccm_t('repeatingItemThumbField');

var repeatingItemIdCounter = 0; //will be appended to repeating item row dom id's so we can identify them later and change data (e.g. if user changes dropdown)
var repeatingItemIsSaved = false; //flag the lets us know if the user saved (versus cancelled) an edit-repeating-item dialog

//Event handler for "add item" button
$('#repeating-item-action-add').on('click', function() {
	editRepeatingItem(addRepeatingItem(), true);
});

function addRepeatingItem(data, label, thumbSrc, doHighlight) {
	var data = ((typeof data !== 'undefined') && (data !== null) && (typeof data === 'object')) ? data : {};
	data['repeatingItemId'] = ++repeatingItemIdCounter;
	data['hasThumb'] = (repeatingItemThumbField.length > 0);
	var $repeatingItem = $('#repeating-item-template').tmpl(data).appendTo('#repeating-items');
	
	var label = (typeof label !== 'undefined') ? label : '';
	var thumbSrc = (typeof thumbSrc !== 'undefined') ? thumbSrc : '';
	setRepeatingItemLabelAndThumb($repeatingItem, label, thumbSrc);
	
	var doHighlight = (typeof doHighlight !== 'undefined') ? doHighlight : true;
	if (doHighlight) {
		$repeatingItem.effect('highlight');
	}
	
	return $repeatingItem;
}

//Edit Item Popup
$('#repeating-items').on('click', '.repeating-item-edit-open-dialog', function() {
	editRepeatingItem($(this).closest('.repeating-item'));
	return false;
});

function editRepeatingItem($repeatingItem, isNew) {
	var isNew = (typeof isNew !== 'undefined') ? isNew : false;
	
	var data = { 'repeatingItemId' : $repeatingItem.attr('data-repeating-item-id') };
	
	//Populate data object with names/values of all hidden fields in this repeating item
	// (Dev note: this is easier than iterating through the repeatingItemFields properties,
	//  because we don't need logic here for splitting our abstract "types" into separate fields.)
	$repeatingItem.find('input[type="hidden"]').each(function() {
		data[$(this).attr('name').replace('[]', '')] = $(this).val();
	});
	
	$('<div>').dialog({
		'open': function() {
			openRepeatingItemDialog($(this), data);
		},
		'close': function() {
			if (isNew && !repeatingItemIsSaved) {
				$repeatingItem.remove();
			}
			repeatingItemIsSaved = false;
			$(this).remove();
		},
		'modal': true,
		'closeOnEscape': true,
		'title': ccm_t('edit_repeating_item_dialog_title_prefix') + $repeatingItem.index(),
		'width': 800,
		'height': 600
	});
}

function openRepeatingItemDialog($dialog, data) {
	
	$dialog.load(editRepeatingItemDialogURL, data, function() {
		
		var $form = $('#repeating-item-edit');
		
		//Override default error message for the "min" rule,
		// because we use it for file chooser and page selector
		// widgets (which have a value of "0" when nothing is chosen).
		$.extend($.validator.messages, { min: 'This field is required.' });
		
		$form.on('submit', function() {//declare before $form.validate so tinymce writes to textareas before validation
			tinyMCE.triggerSave();
		});
		
		$.validator.addMethod('validateRequiredLink', function(value, element) {
			var $typeInput = $('#' + $(element).attr('data-validation-field-type'));
			var $pageInput = $('[name="' + $(element).attr('data-validation-field-page') + '"]');
			var $urlInput = $('#' + $(element).attr('data-validation-field-url'));
			var type = $typeInput.val();
			var cID = $pageInput.val();
			var url = $urlInput.val();
			
			if (type == 'page' && (!cID.length || !parseInt(cID))) {
				return false;
			} else if (type == 'url' && !url.length) {
				return false;
			} else {
				//nullify the non-chosen type (otherwise both cID and url get saved, which prevents us from knowing what the link type is)
				//[it's yucky that we're doing this here in the validator, but it's the only place we can!]
				if (type == 'page') {
					$urlInput.val('');
				} else if (type == 'url') {
					$pageInput.val(0);
				}
				
				return true;
			}
		}, ccm_t('edit_repeating_item_error_missing_link'));
		
		$form.validate({
			rules: repeatingItemValidationRules($form),
			ignore: [], //must do this otherwise hidden fields don't get validated (which messes up image chooser and page selector)
			
			//the following 3 options disable "real-time" validation messages
			// (we do this because it only works on some field types and hence
			// is inconsistent which is worse than not being there at all)
			onfocusout: false,
			onkeyup: false,
			onclick: false,
			
			submitHandler: function(form) {
				$.event.trigger('dcp_block_edit_repeating_item_save'); //give fields an opportunity to "finalize" their data (e.g. the "link" type field needs to null-ify the non-chosen control type so page chooser doesn't get accidentally saved if user switches to external url)
				
				var data = {};
				$.each($form.serializeArray(), function() {
					data[this.name] = this.value;
				});
				
				var label = '';
				if (repeatingItemLabelField.length) {
					label = $form.find('[name="' + repeatingItemLabelField + '"]').val();
				}
				
				var thumbSrc = '';
				if (repeatingItemThumbField.length) {
					thumbSrc = $form.find('#' + repeatingItemThumbField + '-fm-selected .ccm-file-selected-thumbnail img').attr('src');
				}
				
				saveRepeatingItem($form.attr('data-repeating-item-id'), data, label, thumbSrc);
			
				closeRepeatingItemDialog();
			}
		});
		
		$form.find('.cancel').on('click', function() {
			closeRepeatingItemDialog();
			return false;
		});

		$form.find('.accept').on('click', function() {
			$form.trigger('submit');
			return false;
		});
		
		function closeRepeatingItemDialog() {
			$('#repeating-item-edit').closest('.ui-dialog-content').jqdialog('close');
		}
		
		function repeatingItemValidationRules($form) {
			
			var rules = {};
			
			var $defs = $form.find('input[type="hidden"][data-validation-rule]');
			
			//textboxes & textareas
			$defs.filter('[data-validation-rule="required-text"]').each(function() {
				rules[$(this).attr('data-validation-field')] = 'required';
			});
			
			//file/image choosers & page choosers (treat 0 as empty)
			$defs.filter('[data-validation-rule="required-chooser"]').each(function() {
				rules[$(this).attr('data-validation-field')] = { required: true, min: 1 };
			});
			
			//link (only one of the two fields is required -- cID or url -- depending on chosen type)
			$defs.filter('[data-validation-rule="required-link"]').each(function() {
				rules[$(this).attr('name')] = 'validateRequiredLink';
			});
			
			return rules;
		}
		
	});
}

//This is called from the popup dialog when user saves
function saveRepeatingItem(repeatingItemId, data, label, thumbSrc) {
	repeatingItemIsSaved = true; //lets the "close dialog" event know that data was saved
	
	var $repeatingItem = $('#repeating-items .repeating-item[data-repeating-item-id="' + repeatingItemId + '"]');
	
	//Populate this repeating item's hidden fields with the given data
	$repeatingItem.find('input[type="hidden"]').each(function() {
		var fieldName = $(this).attr('name').replace('[]', '');
		var val = data[fieldName];
		$(this).val(val); //We do *NOT* need to escape this (see http://stackoverflow.com/a/10604369/477513 )
	});
	
	var label = (typeof label !== 'undefined') ? label : '';
	var thumbSrc = (typeof thumbSrc !== 'undefined') ? thumbSrc : '';
	setRepeatingItemLabelAndThumb($repeatingItem, label, thumbSrc);
	
	$repeatingItem.effect('highlight', 2000);
}

function setRepeatingItemLabelAndThumb($repeatingItem, label, thumbSrc) {
	//label text (*MUST* be escaped!)
	var label = (typeof label !== 'undefined') ? label : '';
	label = label.substring(0, 75); //truncate in case it was wysiwyg or textarea content
	label = $.trim(label);
	var labelHtml = label;
	if (label.length) {
		labelHtml = String(label)
					.replace(/&/g, '&amp;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#39;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;');
		//see http://stackoverflow.com/a/7124052/477513
	} else {
		labelHtml = '<i>' + ccm_t('edit_repeating_item_default_label') + '</i>';
	}
	$repeatingItem.find('.repeating-item-label').html(labelHtml);
	
	//thumbnail image
	var thumbSrc = (typeof thumbSrc !== 'undefined') ? thumbSrc : '';
	var thumbHtml = thumbSrc.length ? '<img src="' + thumbSrc + '" alt="" />' : '';
	$repeatingItem.find('.repeating-item-thumb').html(thumbHtml);
	
}

//Drag-n-drop
$('#repeating-items').sortable({
	handle: '.repeating-item-action-move',
	axis: 'y',
	containment: '#repeating-items-sortable-container', //contain to a wrapper div (not the <ul> itself), so there's room for dropping at top and bottom of list
	cursor: 'move'
}).disableSelection();

//Trash button
$('#repeating-items').on('click', '.repeating-item-action-delete', function() {
	var $repeatingItem = $(this).closest('.repeating-item');
	$repeatingItem.slideUp('fast', function() {
		$repeatingItem.remove();
	});
});
