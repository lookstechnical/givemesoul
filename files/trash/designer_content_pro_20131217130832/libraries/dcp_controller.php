<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


Loader::library('dcp_field_types', 'designer_content_pro');
Loader::model('dcp_block_type', 'designer_content_pro');

//NOTE: Do *NOT* name this class DcpBlockController
// because having the words "BlockController" in the class name
// confuses C5's auto-loader, and weird errors ensue (but only
// under certain circumstances... like it happens with APC enabled
// but not without... under 5.6.x... maybe).
class DcpController extends BlockController {
	
	protected $btWrapperClass = 'ccm-ui'; //so twitter bootstrap styles are available in edit dialog
	protected $btInterfaceWidth = 800;
	protected $btInterfaceHeight = 500;
	
	private $btRepeatingItemDataColName = 'repeatingItemData'; //this field must exist (should be "X2" type) in block's db.xml file
	private $btRepeatingItemDataFieldObjects = null; //Array of "DcpFieldType" objects that represents the repeating item data's schema
	private $btRepeatingItemDataRecords = null; //Holds the unserialized repeating item data. USE getRepeatingItemDataRecords() TO ACCESS THIS!
	private $btRepeatingItemLabelField = ''; //field name containing text to use as label in edit dialog's repeating item list (or leave blank to use placeholder text)
	private $btRepeatingItemThumbField = ''; //field name containing fID of image to use as thumbnail in edit dialog's repeating item list (or leave blank to use placeholder image)
	
	public function __construct($obj = null) {
		
		parent::__construct($obj);
		
		//Set up our repeating item fields schema...
		if (is_null($this->btRepeatingItemDataFieldObjects)) { //just in case we've already been instantiated (yeah, I don't know how the constructor would be called twice, but this seems to prevent some random errors)
			$this->initializeSchema(); //Convert the controller array that describe the repeating item schema into an array of DcpFieldType_xxx objects
		}
	}
	
	private function btTableExists() {
		$sql = "SHOW TABLES LIKE '" . $this->getBlockTypeDatabaseTable() . "'";
		$tables = Loader::db()->GetCol($sql);
		return count($tables);
	}
	
	private function getBtTableColNames() {
		$col_names = array();
		$col_objects = Loader::db()->MetaColumns($this->getBlockTypeDatabaseTable());
		foreach ($col_objects as $col) {
			$col_names[] = $col->name;
		}
		return $col_names;
	}
	
	private function initializeSchema() {
		foreach ($this->btDCProRepeatingItemFields as $field_handle => $field_info) {
			$class = 'DcpFieldType_' . ucfirst(strtolower($field_info['type']));
			
			if (!class_exists($class)) {
				throw new Exception("DESIGNER CONTENT PRO ERROR: Invalid field type '{$field_info['type']}' declared for Block Type " . $this->btHandle);
			}
			
			$field_object = new $class($field_handle, $field_info['label'], $field_info['options']);
			$this->btRepeatingItemDataFieldObjects[$field_handle] = $field_object;
			
			if ($field_info['options']['is_item_label']) {
				$this->btRepeatingItemLabelField = $field_handle;
			}
			if ($field_info['options']['is_item_thumb']) {
				$this->btRepeatingItemThumbField = $field_handle;
			}
		}
	}
	
	private function getRepeatingItemDataRecords() {
		if (is_null($this->btRepeatingItemDataRecords)) {
			$serialized_data = $this->{$this->btRepeatingItemDataColName};
			$this->btRepeatingItemDataRecords = empty($serialized_data) ? array() : unserialize($serialized_data);
		}
		return $this->btRepeatingItemDataRecords;
	}
	
	private function getRepeatingItemDataFromPOST() {
		//$_POST will contain an array like this:
		// ['file1_cID'][0] = #
		// ['file1_cID'][1] = #
		// ['file1_cID'][2] = #
		// ['file1_title'][0] = '...'
		// ['file1_title'][1] = '...'
		// ['file1_title'][2] = '...'
		// ['textbox1_text'][0] = '...'
		// ['textbox1_text'][1] = '...'
		// ['textbox1_text'][2] = '...'
		// ['textbox2_text'][0] = '...'
		// ['textbox2_text'][1] = '...'
		// ['textbox2_text'][2] = '...'
		//
		//We want to convert that to this (for serialization):
		// [0]['file1_cID'] = #
		// [0]['file1_title'] = '...'
		// [0]['textbox1_text'] = '...'
		// [0]['textbox2_text'] = '...'
		// [1]['file1_cID'] = #
		// [1]['file1_title'] = '...'
		// [1]['textbox1_text'] = '...'
		// [1]['textbox2_text'] = '...'
		// [2]['file1_cID'] = #
		// [2]['file1_title'] = '...'
		// [2]['textbox1_text'] = '...'
		// [2]['textbox2_text'] = '...'
		//
		//BUT, $_POST will also contain other stuff we're not concerned with,
		// so we can't just do this to every $_POST field -- instead we need
		// to go through the field definitions to know which $_POST items we want.
		
		$return_data = array();
		
		foreach ($this->btRepeatingItemDataFieldObjects as $field) {
			$post_data_per_input = $field->getPOSTDataPerInput();
			foreach ($post_data_per_input as $input_name => $input_data) {
				for ($i = 0, $cnt = count($input_data); $i < $cnt; $i++) {
					$return_data[$i][$input_name] = $input_data[$i];
				}
			}
		}
		
		return $return_data;
	}
	
	/*************************************************************************/
	
	public function save($args) {
		$args[$this->btRepeatingItemDataColName] = serialize($this->getRepeatingItemDataFromPOST());
		
		parent::save($args);
	}
	
	final public function getSearchableContent() { //use "final" to prevent block controllers from bypassing this
		$content = array();
		
		foreach ($this->getRepeatingItemDataRecords() as $record) {
			foreach ($this->btRepeatingItemDataFieldObjects as $field) {
				$content[] = $field->getSearchableContent($record);
			}
		}
		
		$content = array_filter($content); //removes empty items from array
		$content = implode(' - ', $content);
		return $content;
	}
	
	//Note that we use this for both the intended "translatable strings" functionality,
	// *and* as a means of passing php variables to javascript.
	final public function getJavaScriptStrings() { //use "final" to prevent block controllers from bypassing this
		return array(
			//translatable strings
			'edit_repeating_item_dialog_title_prefix' => t('Edit Item #'),
			'edit_repeating_item_default_label' => t('click to edit'),
			'edit_repeating_item_error_missing_link' => t('You must provide a link'),
			//php vars to send to js
			'repeatingItemLabelField' => $this->getRepeatingItemLabelFieldName(),
			'repeatingItemThumbField' => $this->getRepeatingItemThumbFieldName(),
			'editRepeatingItemDialogURL' => REL_DIR_FILES_TOOLS_BLOCKS . '/' . $this->btHandle . '/edit_repeating_item', //much easier than calling getBlockTypeToolsURL() in the concrete/urls helper
		);
	}
	
	/*************************************************************************/
	
	public function outputAllRepeatingItemJQTmplHiddenFields() {
		foreach ($this->btRepeatingItemDataFieldObjects as $field) {
			$field->outputJQTmplHiddenFields();
		}
	}
	
	public function outputAllRepeatingItemEditFields() {
		foreach ($this->btRepeatingItemDataFieldObjects as $field) {
			$field->outputEditFields();
		}
	}
	
	public function getRepeatingItemLabelFieldName() {
		return empty($this->btRepeatingItemLabelField) ? '' : $this->btRepeatingItemDataFieldObjects[$this->btRepeatingItemLabelField]->repeatingItemLabelFieldName();
	}
	
	public function getRepeatingItemThumbFieldName() {
		return empty($this->btRepeatingItemThumbField) ? '' : $this->btRepeatingItemDataFieldObjects[$this->btRepeatingItemThumbField]->repeatingItemThumbFieldName();
	}
	
	public function getRepeatingItemLabelValue($repeating_item) {
		$field_name = $this->getRepeatingItemLabelFieldName();
		if (empty($field_name) || empty($repeating_item[$field_name])) {
			return '';
		} else {
			return $repeating_item[$field_name];
		}
	}
	
	public function getRepeatingItemThumbSrc($repeating_item) {
		$field_name = $this->getRepeatingItemThumbFieldName();
		if (empty($field_name) || empty($repeating_item[$field_name])) {
			return '';
		} else {
			return File::getByID($repeating_item[$field_name])->getThumbnailSRC(1);
		}
	}
	
	/*************************************************************************/
	
	public function getRepeatingItems($mode = 'view') {
		if ($mode == 'edit') {
			return $this->getRepeatingItemsForEdit();
		} else {
			return $this->getRepeatingItemsForView();
		}
	}
	
	//returns repeating items as associative arrays (for easy json_encoding)
	private function getRepeatingItemsForEdit() {
		$repeating_items = array();
		
		foreach ($this->getRepeatingItemDataRecords() as $record) {
			$repeating_item = array();
			foreach ($this->btRepeatingItemDataFieldObjects as $fieldObject) {
				$fieldDataArray = $fieldObject->getDataArrayForEdit($record);
				foreach ($fieldDataArray as $key => $val) {
					$repeating_item[$key] = $val;
				}
			}
			$repeating_items[] = $repeating_item;
		}
		
		return $repeating_items;
	}
	
	//returns repeating items as simple objects (for better-looking code in block view.php files)
	private function getRepeatingItemsForView() {
		$repeating_items = array();
		
		foreach ($this->getRepeatingItemDataRecords() as $record) {
			$repeating_item = new StdClass;
			foreach ($this->btRepeatingItemDataFieldObjects as $fieldName => $fieldObject) {
				$repeating_item->$fieldName = $fieldObject->getDataObjectForView($record);
			}
			$repeating_items[] = $repeating_item;
		}
		
		return $repeating_items;
	}
	
}
