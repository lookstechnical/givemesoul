/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/

function RepeatingItemFields() {
	var sortedFieldIds = [];
	var fieldData = {};
	
	this.addField = function(field) {
		fieldData[field.id] = field;
		sortedFieldIds.push(field.id);
		sortIdsByDisplayOrder();
	}
	
	var sortIdsByDisplayOrder = function() {
		sortedFieldIds.sort(function(a, b) {
			return fieldData[a].displayOrder - fieldData[b].displayOrder;
		});
	}
	
	this.getFields = function(convert_bools_for_server) {
		convert_bools_for_server = typeof(convert_bools_for_server) === 'undefined' ? false : convert_bools_for_server;
		
		var sortedFields = [];
		
		$.each(sortedFieldIds, function(i, id) {
			sortedFields.push(fieldData[id]);
		});
		
		if (convert_bools_for_server) {
			convertFieldDataBoolsForServer(sortedFields);
		}
		
		return sortedFields;
	}
	
	//converts all boolean TRUE to integer 1, and removes boolean FALSE properties entirely
	var convertFieldDataBoolsForServer = function(fields) {
		//I wish there were a more elegant way to iterate through everything,
		// but I couldn't figure one out :(
		$.each(fields, function(fieldKey) {
			$.each(this, function(componentKey, val) {
				if (componentKey == 'options') {
					$.each(this, function(optionKey, val) {
						if (val === true) {
							fields[fieldKey][componentKey][optionKey] = 1;
						} else if (val === false) {
							delete fields[fieldKey][componentKey][optionKey];
						}
					});
				} else if (val === true) {
					fields[fieldKey][componentKey] = 1;
				} else if (val === false) {
					delete fields[fieldKey][componentKey];
				}
			});
		});
	}
	
	this.getMaxDisplayOrder = function() {
		var max = 0;
		$.each(sortedFieldIds, function(i, id) {
			var current = fieldData[id].displayOrder;
			max = (current > max) ? current : max;
		});
		return max;
	}
	
	this.setFieldData = function(id, key, value) {
		var isOption = (key.substring(0, 7) == 'options');
		
		if (isOption) {
			var optionKey = key.match(/options\[(.*)\]/)[1];
			if (optionKey == 'is_item_label' || optionKey == 'is_item_thumb') {
				for (i in fieldData) {
					fieldData[i]['options'][optionKey] = false; //enforce exclusivity of these fields (only one can be true at a time)
				}
			}
			fieldData[id]['options'][optionKey] = value;
		} else {
			fieldData[id][key] = value;
		}
		
		if (key == 'displayOrder') {
			sortIdsByDisplayOrder();
		}
	}
	
}

var nextRepeatingItemFieldId = 1;
function RepeatingItemField(data) {
	this.id = nextRepeatingItemFieldId++;
	if ((typeof data === 'undefined') || $.isEmptyObject(data)) {
		this.isNew = true;
		this.isDeleted = false;
		this.handle = '';
		this.type = '';
		this.label = '';
		this.displayOrder = repeatingItemFields.getMaxDisplayOrder() + 1;
		this.options = {};
	} else {
		for (key in data) {
			if (data.hasOwnProperty(key)) {
				this[key] = data[key];
			}
		}
	}
}

var repeatingItemFields = new RepeatingItemFields();
var orig_repeatingItemFields = new RepeatingItemFields();
function initializeRepeatingItemFields(initialData) {
	var displayOrder = 1;
	$.each(initialData, function(field_handle, field_info) {
		var repeatingItemField = new RepeatingItemField({
			'isNew': false,
			'isDeleted': false,
			'handle': field_handle,
			'type': field_info.type,
			'label': field_info.label,
			'displayOrder': displayOrder++,
			'options': $.isEmptyObject(field_info.options) ? {} : field_info.options //watch out for empty arrays ("[]") due to how php's json_encode function outputs empty php arrays
		});
		repeatingItemFields.addField(repeatingItemField);
		orig_repeatingItemFields.addField($.extend(true, {}, repeatingItemField)); //pass in a clone so changes to the repeating item don't affect its "original" data
	});
}
