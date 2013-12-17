<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


Loader::library('dcp_field_display', 'designer_content_pro');

abstract class DcpFieldType {
	protected $names; //array of input field names (most field types will just have one input field, but some are "compound" types that are made up of 2 or 3 -- e.g. "file" and "link")
	protected $label; //there's just one label for the whole field, even if there are multiple input fields (so it's really more of a "group heading" than a field label)
	protected $options; //array of options (differs from fieldtype to fieldtype -- e.g. "required", "searchable", "has_editable_text")
	protected $fh; //form helper -- used by outputEditFields function a lot so just instantiate it in the constructor
	
	public static function getGeneratorLabel() {
		//we want this to be an abstract static method,
		//but not all versions of php support that.
		//So fake it with an exception here in the base class:
		throw new Exception("DESIGNER CONTENT PRO ERROR: A DcpFieldType class does not have the 'getGeneratorLabel' static method implemented!");
	}
	
	public static function getGeneratorOptions() {
		//we want this to be an abstract static method,
		//but not all versions of php support that.
		//So fake it with an exception here in the base class:
		throw new Exception("DESIGNER CONTENT PRO ERROR: A DcpFieldType class does not have the 'getGeneratorOptions' static method implemented!");
	}
	
	public function __construct($handle, $label, $options = array()) {
		$this->names = array('text' => $handle . '_text');
		$this->label = $label;
		$this->options = $options;
		$this->fh = Loader::helper('form');
	}
	
	public function repeatingItemLabelFieldName() {
		//Override this in your sub-class if you create a new field type
		// which doesn't have a 'text' equivelant (because we need
		// something to use for repeating item list labels in the edit dialog).
		return $this->names['text'];
	}
	
	public function repeatingItemThumbFieldName() {
		//Override this in your sub-class if you can provide a file id (e.g. File and Image types)
		return '';
	}
	
	public function outputJQTmplHiddenFields() {
		foreach ($this->names as $name) {
			//Hidden fields will be outputted to a jquery template,
			// so we bracketize the name (because it will be one of many repeated rows in the edit form),
			// and the "value" is really just the name wrapped in "${...}" (because it's a jquery-tmpl placeholder).
			//Also note that we can't use C5's form helper to output the hidden field because it always adds dom id's,
			// but we can't have that since these fields will be repeated for every repeating item row in the edit form.
			echo '<input type="hidden" name="' . $name . '[]" value="${' . $name . '}" />';
		}
	}
	
	abstract public function outputEditFields();
	
	protected function outputEditFieldsWrapperStart($display_required_asterisk = false) {
		echo '<fieldset>';
		echo '<legend>';
		echo $this->label;
		if ($display_required_asterisk) {
			echo '<span style="font-size: 8px;">&nbsp;</span><span style="color: red;">*</span>';
		}
		echo '</legend>';
		
	}
	protected function outputEditFieldsWrapperEnd() {
		echo '</fieldset>';
	}
	
	protected function outputEditFieldRequiredValidation($type, $handle) {
		if ($this->option('required')) {
			echo '<input type="hidden" data-validation-rule="required-' . $type . '" data-validation-field="' . $handle . '" />';
		}
	}
	
	//Extract just the data pertaining to this field from $_POST.
	// Also ensures that an empty array is provided when no data exists
	// for each of this field's input components (so the caller can just
	// loop through arrays without having to check for empty() first).
	public function getPOSTDataPerInput() {
		$data = array();
		foreach ($this->names as $name) {
			$data[$name] = empty($_POST[$name]) ? array() : $_POST[$name];
		}
		return $data;
	}
	
	//Sub-classes usually don't need to override this.
	// The only reason is if they want to alter the data (e.g. wysiwyg).
	// Note that if you do override it, it must return an array!
	public function getDataArrayForEdit($record) {
		$data = array();
		foreach ($this->names as $name) {
			$data[$name] = $record[$name];
		}
		return $data;
	}
	
	//This must be implemented, and should return the appropriate displayObject.
	abstract public function getDataObjectForView($record);
	
	public function getSearchableContent($record) {
		return $this->option('searchable') ? $record[$this->names['text']] : '';
	}
	
	//Utility function for checking options without worrying about whether or not they exist
	protected function option($key, $default_if_missing = null) {
		return array_key_exists($key, $this->options) ? $this->options[$key] : $default_if_missing;
	}
}

class DcpFieldType_File extends DcpFieldType {
	
	public static function getGeneratorLabel() {
		return t('File');
	}
	
	public static function getGeneratorOptions() {
		return array(
			'searchable' => array('type' => 'checkbox', 'label' => t('Searchable'), 'description' => t('Check this box to include this field\'s file title in the site search index.')),
			'required' => array('type' => 'checkbox', 'label' => t('Required'), 'description' => t('Check this box to make this field required when adding/editing blocks.')),
			'has_editable_text' => array('type' => 'checkbox', 'label' => t('Editable Title'), 'description' => t('Check this box to provide a textbox for the file title when adding/editing blocks (if provided, this title will be used instead of the file\'s title attribute).')),
			'is_item_label' => array('type' => 'checkbox', 'label' => t('Block Item Label'), 'description' => t('Check this box to use this field\'s editable title as the label for repeating items when listed in the block add/edit dialog.')),
		);
	}

	public function __construct($handle, $label, $options = array()) {
		parent::__construct($handle, $label, $options);
		
		$this->names = array('fID' => $handle . '_fID');
		
		if ($this->option('has_editable_text')) {
			$this->names['text'] = $handle . '_title'; //for downloadable files this will be link text; for images this will be alt text
		}
	}
	
	public function repeatingItemLabelFieldName() {
		return $this->option('has_editable_text') ? $this->names['text'] : '';
	}
	
	public function outputEditFields($restrict_to_filetype = null, $text_field_label = null) { //<--$text_field_label defaults to t('Title')
		$this->outputEditFieldsWrapperStart($this->option('required'));
		
		$fID_name = $this->names['fID'];
		$file = empty($_POST[$fID_name]) ? null : File::getByID($_POST[$fID_name]);
		
		$al = Loader::helper('concrete/asset_library');
		$args = array();
		if (!empty($restrict_to_filetype)) {
			$args['fType'] = $restrict_to_filetype; //passing FileType::T_IMAGE into $al->file() is the same as calling $al->image()
		}
		echo $al->file($fID_name, $fID_name, $this->label, $file, $args);
		
		$this->outputEditFieldRequiredValidation('chooser', $fID_name);
		
		if ($this->option('has_editable_text')) {
			$text_name = $this->names['text'];
			$text_field_label = is_null($text_field_label) ? t('Title') : $text_field_label; //do this here instead of having a default value for the function param -- because php doesn't allow function calls [e.g. t()] as param defaults
			echo $this->fh->label($text_name, "{$text_field_label}: ");
			echo $this->fh->text($text_name, $_POST[$text_name], array('class' => 'file-alt-text'));
		}
		
		$this->outputEditFieldsWrapperEnd();		
	}
	
	public function getDataObjectForView($record) {
		$fID = $record[$this->names['fID']];
		$text = $this->option('has_editable_text') ? $record[$this->names['text']] : null; //pass null to the Display object if there's no "editable text" field so it knows to retrieve file title instead
		return new DcpFieldDisplay_File($fID, $text);
	}
	
	public function getSearchableContent($record) {
		return $this->option('searchable') ? $this->getDataObjectForView($record)->getTitle() : '';
	}
}

class DcpFieldType_Image extends DcpFieldType_File {
	
	public static function getGeneratorLabel() {
		return t('Image');
	}
	
	public static function getGeneratorOptions() {
		return array(
			'searchable' => array('type' => 'checkbox', 'label' => t('Searchable'), 'description' => t('Check this box to include this field\'s file title in the site search index.')),
			'required' => array('type' => 'checkbox', 'label' => t('Required'), 'description' => t('Check this box to make this field required when adding/editing blocks.')),
			'has_editable_text' => array('type' => 'checkbox', 'label' => t('Editable Alt Text'), 'description' => t('Check this box to provide a textbox for the image alt text when adding/editing blocks (if provided, this alt text will be used instead of the file\'s description or title attribute).')),
			'is_item_thumb' => array('type' => 'checkbox', 'label' => t('Block Item Image'), 'description' => t('Check this box to use this field\'s image as the thumbnail for repeating items when listed in the block add/edit dialog.')),
		);
	}
	
	public function repeatingItemThumbFieldName() {
		return $this->names['fID'];
	}
	
	public function outputEditFields() {
		parent::outputEditFields(FileType::T_IMAGE, t('Alt Text'));
	}
	
	public function getDataObjectForView($record) {
		$fID = $record[$this->names['fID']];
		$text = $this->option('has_editable_text') ? $record[$this->names['text']] : null; //pass null to the Display object if there's no "editable text" field so it knows to retrieve file title instead
		return new DcpFieldDisplay_Image($fID, $text);
	}
}

class DcpFieldType_Textbox extends DcpFieldType {
	
	public static function getGeneratorLabel() {
		return t('Textbox');
	}
	
	public static function getGeneratorOptions() {
		return array(
			'searchable' => array('type' => 'checkbox', 'label' => t('Searchable'), 'description' => t('Check this box to include this field\'s text in the site search index.')),
			'required' => array('type' => 'checkbox', 'label' => t('Required'), 'description' => t('Check this box to make this field required when adding/editing blocks.')),
			'is_item_label' => array('type' => 'checkbox', 'label' => t('Block Item Label'), 'description' => t('Check this box to use this field\'s text as the label for repeating items when listed in the block add/edit dialog.')),
		);
	}
	
	public function outputEditFields() {
		$this->outputEditFieldsWrapperStart($this->option('required'));
		
		$text_name = $this->names['text'];
		echo $this->fh->text($text_name, $_POST[$text_name], array('class' => 'input-xxlarge'));
		
		$this->outputEditFieldRequiredValidation('text', $text_name);
		
		$this->outputEditFieldsWrapperEnd();
	}
	
	public function getDataObjectForView($record) {
		return new DcpFieldDisplay_Textbox($record[$this->names['text']]);
	}
}

class DcpFieldType_Textarea extends DcpFieldType {
	
	public static function getGeneratorLabel() {
		return t('Text Area');
	}
	
	public static function getGeneratorOptions() {
		return array(
			'searchable' => array('type' => 'checkbox', 'label' => t('Searchable'), 'description' => t('Check this box to include this field\'s text in the site search index.')),
			'required' => array('type' => 'checkbox', 'label' => t('Required'), 'description' => t('Check this box to make this field required when adding/editing blocks.')),
			'is_item_label' => array('type' => 'checkbox', 'label' => t('Block Item Label'), 'description' => t('Check this box to use this field\'s text as the label for repeating items when listed in the block add/edit dialog.')),
		);
	}
	
	public function outputEditFields() {
		$this->outputEditFieldsWrapperStart($this->option('required'));
		
		$text_name = $this->names['text'];
		echo $this->fh->textarea($text_name, $_POST[$text_name], array('class' => 'input-xxlarge'));
		
		$this->outputEditFieldRequiredValidation('text', $text_name);
		
		$this->outputEditFieldsWrapperEnd();
	}
	
	public function getDataObjectForView($record) {
		return new DcpFieldDisplay_Textarea($record[$this->names['text']]);
	}
}

class DcpFieldType_Wysiwyg extends DcpFieldType {
	protected $wh; //wysiwyg helper
	
	public static function getGeneratorLabel() {
		return t('WYSIWYG');
	}
	
	public static function getGeneratorOptions() {
		return array(
			'searchable' => array('type' => 'checkbox', 'label' => t('Searchable'), 'description' => t('Check this box to include this field\'s content in the site search index.')),
			'required' => array('type' => 'checkbox', 'label' => t('Required'), 'description' => t('Check this box to make this field required when adding/editing blocks.')),
		);
	}
	
	public function __construct($handle, $label, $options = array()) {
		parent::__construct($handle, $label, $options);
		$this->names = array('text' => $handle . '_content');
		$this->wh = Loader::helper('wysiwyg_link_replacement', 'designer_content_pro');
	}
	
	public function outputEditFields() {
		$this->outputEditFieldsWrapperStart($this->option('required'));
		
		Loader::element('editor_controls');
		$text_name = $this->names['text'];
		echo $this->fh->textarea($text_name, $_POST[$text_name], array('class' => 'ccm-advanced-editor', 'id' => $text_name));

		$this->outputEditFieldRequiredValidation('text', $text_name);
		
		$this->outputEditFieldsWrapperEnd();
	}
	
	public function getPOSTDataPerInput() {
		$text_name = $this->names['text'];
		$data = empty($_POST[$text_name]) ? array() : $this->wh->translateTo($_POST[$text_name]);
		return array($text_name => $data);
	}
	
	public function getSearchableContent($record) {
		return $this->option('searchable') ? strip_tags($record[$this->names['text']]) : '';
	}
	
	public function getDataArrayForEdit($record) {
		$text_name = $this->names['text'];
		$content = $record[$text_name];
		$translatedContent = $this->wh->translateFromEditMode($content);
		return array($text_name => $translatedContent);
	}
	
	public function getDataObjectForView($record) {
		$text_name = $this->names['text'];
		$content = $record[$text_name];
		$translatedContent = $this->wh->translateFrom($content);
		return new DcpFieldDisplay_Wysiwyg($translatedContent);
	}
}

class DcpFieldType_Link extends DcpFieldType {
	private $original_handle;
	private static function availableControlTypes() { //ideally this would just be a private member, but we can't do that because we need to use the t() function.
		return array(
			'combo' => t('Combo'),
			'page' => t('Page Chooser'),
			'url' => t('External URL'),
		);
	}
	
	public static function getGeneratorLabel() {
		return t('Link');
	}
	
	public static function getGeneratorOptions() {
		return array(
			'control_type' => array('type' => 'select', 'label' => t('Type'), 'choices' => self::availableControlTypes(), 'description' => t('The type of field to provide in the block add/edit dialog:<br>sitemap page chooser only,<br>external url textbox only,<br>or both (combo).')),
			'searchable' => array('type' => 'checkbox', 'label' => t('Searchable'), 'description' => t('Check this box to include this field\'s link text in the site search index.')),
			'required' => array('type' => 'checkbox', 'label' => t('Required'), 'description' => t('Check this box to make this field required when adding/editing blocks.')),
			'has_editable_text' => array('type' => 'checkbox', 'label' => t('Editable Link Text'), 'description' => t('Check this box to provide a textbox for the link text when adding/editing blocks (if provided, this text will be used instead of the page title or external url).')),
			'is_item_label' => array('type' => 'checkbox', 'label' => t('Block Item Label'), 'description' => t('Check this box to use this field\'s editable link text as the label for repeating items when listed in the block add/edit dialog.')),
		);
	}
	
	public function __construct($handle, $label, $options = array()) {
		parent::__construct($handle, $label, $options);
		
		//check that controltype was provided and that it's one of the valid options
		if (!in_array($this->option('control_type'), array_keys(self::availableControlTypes()))) {
			$this->options['control_type'] = 'combo'; //default to 'combo' if not provided or invalid
		}
		
		$this->names = array(
			'cID' => $handle . '_cID',
			'url' => $handle . '_url',
		);
		if ($this->option('has_editable_text')) {
			$this->names['text'] = $handle . '_text';
		}

		$this->original_handle = $handle; //hold on to the original handle -- we'll need it for some temp fields later on
	}
	
	public function repeatingItemLabelFieldName() {
		return $this->option('has_editable_text') ? $this->names['text'] : '';
	}
	
	public function outputEditFields() {
		$this->outputEditFieldsWrapperStart($this->option('required'));
		
		$control_type_name = $this->original_handle . '_control_type'; //temporary field, never saved to db (so it's not in $this->names)
		$ps_wrapper_name = $this->original_handle . '_ps_wrapper'; //ditto
		$cID_name = $this->names['cID'];
		$url_name = $this->names['url'];
		if ($this->option('has_editable_text')) {
			$text_name = $this->names['text'];
		}
		
		$control_type = $this->option('control_type'); //no need to provide default fallback value here because the constructor already did that for us
		
		echo $this->fh->label($control_type_name, 'Link To: ', array('class' => 'link-to-label'));
		
		if ($control_type == 'url') {
			$default_control_type_is_page = false;
		} else if ($control_type == 'page') {
			$default_control_type_is_page = true;
		} else { //'combo'
			$default_control_type_is_page = empty($_POST[$url_name]); //note that this makes pageselector the default for new records
		}

		if ($control_type == 'combo') {
			//output a dropdown for 'combo' controltype:
			$select_options = array('page' => t('Page in Site'), 'url' => t('External URL'));
			$select_value = ($default_control_type_is_page ? 'page' : 'url');
			$select_onchange = "$(this).siblings('#{$ps_wrapper_name}').css('display', ($(this).val() === 'page') ? 'inline-block' : 'none');"
			                 . "$(this).siblings('#{$url_name}').css('display', ($(this).val() === 'url') ? 'inline-block' : 'none');";
			echo $this->fh->select($control_type_name, $select_options, $select_value, array('class' => 'link-to-type', 'onchange' => $select_onchange));
		
			echo ' ';
		} else if ($control_type == 'page') {
			echo $this->fh->hidden($control_type_name, 'page');
		} else if ($control_type == 'url') {
			echo $this->fh->hidden($control_type_name, 'url');
		}
		
		if ($control_type == 'url') {
			echo $this->fh->hidden($cID_name, '0');
		} else { //output a page selector for 'page' and 'combo' controltypes:
			echo '<div id="' . $ps_wrapper_name . '" class="link-to-ps-wrapper" style="display:' . ($default_control_type_is_page ? 'inline-block' : 'none') . ';">';
			$ps = Loader::helper('form/page_selector');
			echo $ps->selectPage($cID_name, $_POST[$cID_name]);
			echo '</div>';
		}
		
		if ($control_type == 'page') {
			echo $this->fh->hidden($url_name, '');
		} else { //output a textfield for 'url' and 'combo' controltypes:
			echo $this->fh->text($url_name, $_POST[$url_name], array('class' => 'link-to-url', 'style' => 'display:' . ($default_control_type_is_page ? 'none' : 'inline-block') . ';'));
		}
		
		if ($this->option('has_editable_text')) {
			echo '<br>';
			echo $this->fh->label($text_name, t('Link Text: '));
			echo $this->fh->text($text_name, $_POST[$text_name], array('class' => 'link-text-input'));
		}
		
		if ($this->option('required')) {
			echo '<input type="hidden"'
			   . ' name="' . $this->original_handle . '_validation"'
			   . ' data-validation-rule="required-link"'
			   . ' data-validation-field-type="' . $control_type_name . '"'
			   . ' data-validation-field-page="' . $cID_name . '"'
			   . ' data-validation-field-url="' . $url_name . '"'
			   . ' />';
		}
		
		//nullify the non-chosen control type value
		// (for example, if user chooses a page, but then changes to external url,
		// we want the external url being saved and the chosen page should be empty)
		echo "\n" . "<script>$(document).on('dcp_block_edit_repeating_item_save', function(e) {" . "\n";
		echo "\t" . "if ($('#{$control_type_name}').val() == 'page') {" . "\n";
		echo "\t\t" . "$('input[name=\"{$url_name}\"]').val('');" . "\n";
		echo "\t" . "} else if ($('#{$control_type_name}').val() == 'url') {" . "\n";
		echo "\t\t" . "$('input[name=\"{$cID_name}\"]').val('0');" . "\n";
		echo "\t" . "}" . "\n";
		echo "});</script>" . "\n";
		
		
		$this->outputEditFieldsWrapperEnd();
	}
	
	public function getDataObjectForView($record) {
		$text = $this->option('has_editable_text') ? $record[$this->names['text']] : null; //null indicates that the text should be ignored (it will use the collection name or external url instead)
		return new DcpFieldDisplay_Link($record[$this->names['cID']], $record[$this->names['url']], $text);
	}
	
	public function getSearchableContent($record) {
		return $this->option('searchable') ? $this->getDataObjectForView($record)->getText() : '';
	}
}

class DcpFieldType_Page extends DcpFieldType {
	
	public static function getGeneratorLabel() {
		return t('Page');
	}
	
	public static function getGeneratorOptions() {
		return array(
			'required' => array('type' => 'checkbox', 'label' => t('Required'), 'description' => t('Check this box to make this field required when adding/editing blocks.')),
		);
	}
	
	public function __construct($handle, $label, $options = array()) {
		parent::__construct($handle, $label, $options);
		$this->names = array('cID' => $handle . '_cID');
	}
	
	public function outputEditFields() {
		$this->outputEditFieldsWrapperStart($this->option('required'));
		
		$cID_name = $this->names['cID'];
		$ps_wrapper_name = $cID_name . '_ps_wrapper'; //temporary field, never saved to db (so it's not in $this->names)
		
		echo '<div id="' . $ps_wrapper_name . '" class="link-to-ps-wrapper">';
		$ps = Loader::helper('form/page_selector');
		echo $ps->selectPage($cID_name, $_POST[$cID_name]);
		echo '</div>';
		
		$this->outputEditFieldRequiredValidation('chooser', $cID_name);
		
		$this->outputEditFieldsWrapperEnd();
	}
	
	public function getDataObjectForView($record) {
		return new DcpFieldDisplay_Page($record[$this->names['cID']]);
	}
}
