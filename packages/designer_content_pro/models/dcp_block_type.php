<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


class DcpBlockTypeModel {
	
	public static $available_field_type_handles = array(
		'textbox',
		'image',
		'wysiwyg',
		'link',
		'textarea',
		'file',
		'page',
	);
	
	//Returns TWO arrays, so call it like this:
	// list($labels, $options) = getAvailableFieldTypeLabelsAndOptions()
	public static function getAvailableFieldTypeLabelsAndOptions() {
		$labels = array();
		$options = array();
		
		Loader::library('dcp_field_types', 'designer_content_pro');
		foreach (self::$available_field_type_handles as $type_handle) {
			$class = 'DcpFieldType_' . ucfirst(strtolower($type_handle));
			if (!class_exists($class)) {
				throw new Exception("DESIGNER CONTENT PRO ERROR: Cannot find class definition '{$class}' for field type '{$type_handle}'");
			}
			$labels[$type_handle] = call_user_func(array($class, 'getGeneratorLabel')); //I think this is the only way to call a static method on a variable class
			$options[$type_handle] = call_user_func(array($class, 'getGeneratorOptions')); //ditto
		}
		
		return array($labels, $options);
	}
	
	//Returns array of installed AND not-installed BlockType objects
	// for every blocktype found in the package's /blocks/ dir.
	public static function getAllBlockTypes() {
		$bts = array();
		$pkg_blocks_dir = self::getPkgBlocksDir();
		
		if (is_dir($pkg_blocks_dir)) {
			$file_handle = opendir($pkg_blocks_dir);
			while(($btHandle = readdir($file_handle)) !== false) { //block's folder name *is* the handle
				if (strpos($btHandle, '.') === false) {
					$bt = self::getBlockTypeInfoByHandle($btHandle);
					if (!is_null($bt)) {
						$bts[$btHandle] = $bt;
					}
				}				
			}
		}
		
		return $bts;
	}
	
	public static function getBlockTypeInfoByHandle($btHandle) {
		//Check that block files actually exist
		//(Note that we must manually check for block overrides
		// otherwise we could get a fatal error due to class re-declarations!)
		$override_controller_path = self::getCustomViewControllerPath($btHandle);
		$package_controller_path = self::getPkgBlockControllerPath($btHandle);
		$hasCustomController = false;
		if (file_exists($override_controller_path)) {
			$controller_path = $override_controller_path;
			$hasCustomController = true;
		} else if (file_exists($package_controller_path)) {
			$controller_path = $package_controller_path;
		} else {
			return null;
		}
		
		//DEV NOTE: much of the following logic was copied from
		// Concrete5_Model_BlockTypeList::getAvailableList()
		// (from core version 5.6.0.2).
		$bt = new BlockType;
		$bt->btHandle = $btHandle;
		$bt->pkgID = Package::getByHandle('designer_content_pro')->getPackageID(); //<--we must do this otherwise the next line fails (because it looks in the wrong place)
		$class = $bt->getBlockTypeClass();
		
		require_once($controller_path);
		if (!class_exists($class)) {
			return null;
		}
		$bta = new $class;
		$bt->btName = $bta->getBlockTypeName();
		$bt->btDescription = $bta->getBlockTypeDescription();
		$bt->hasCustomViewTemplate = file_exists(DIR_FILES_BLOCK_TYPES . '/' . $btHandle . '/' . FILENAME_BLOCK_VIEW);
		$bt->hasCustomEditTemplate = file_exists(DIR_FILES_BLOCK_TYPES . '/' . $btHandle . '/' . FILENAME_BLOCK_EDIT);
		$bt->hasCustomAddTemplate = file_exists(DIR_FILES_BLOCK_TYPES . '/' . $btHandle . '/' . FILENAME_BLOCK_ADD);
		$bt->hasCustomController = $hasCustomController; //<--this line is specific to DCP (not copied from core logic)
		
		$btID = Loader::db()->GetOne("select btID from BlockTypes where btHandle = ?", array($btHandle));
		$bt->installed = ($btID > 0);
		$bt->btID = $btID;
		
		return $bt;
	}
	
	public static function isBlockTypeInstalled($btHandle) {
		return !empty($btHandle) && self::getBlockTypeInfoByHandle($btHandle)->installed;
	}
	
	public static function isPkgBlocksDirWritable() {
		return is_writable(self::getPkgBlocksDir());
	}
	
	public static function getPkgBlocksDir() {
		$pkg = Package::getByHandle('designer_content_pro');
		$dir = $pkg->getPackagePath() . '/' . DIRNAME_BLOCKS;
		return $dir;
	}
	
	public static function getPkgBlockDir($btHandle) {
		return self::getPkgBlocksDir() . '/' . $btHandle;
	}
	
	public static function getPkgBlockControllerPath($btHandle) {
		return self::getPkgBlockDir($btHandle) . '/' . FILENAME_BLOCK_CONTROLLER;
	}
	
	public static function isCustomViewsDirWritable() {
		return is_writable(DIR_FILES_BLOCK_TYPES);
	}
	
	public static function getCustomViewDir($btHandle) {
		return DIR_FILES_BLOCK_TYPES . '/' . $btHandle;
	}
	public static function getCustomViewTemplatePath($btHandle) {
		return self::getCustomViewDir($btHandle) . '/' . FILENAME_BLOCK_VIEW;
	}
	
	public static function getCustomViewControllerPath($btHandle) {
		return self::getCustomViewDir($btHandle) . '/' . FILENAME_BLOCK_CONTROLLER;
	}
	
	public static function getBlockTypeDatabaseTableName($btHandle) {
		return 'bt' . Loader::helper('text')->camelcase($btHandle);
	}
	
	public static function getBlockTypeControllerName($btHandle) {
		return Loader::helper('text')->camelcase($btHandle) . 'BlockController';
	}
	
	public static function getRepeatingItemFields($btHandle) {
		//Dev note:
		// If the blocktype isn't installed,
		// an error occurs when trying to instantiate the controller class below.
		// (This happens when generating "sample code" in the dashboard list.)
		//Ideally we'd solve this by calling Loader::block($btHandle) first,
		// but that causes an error as well.
		//The solution is to make a call to getBlockTypeInfoByHandle($btHandle)
		// -- we don't actually use the info it returns, but just calling it
		// has the happy side-effect of loading the controller file
		// (as Loader::blocK() should do, but doesn't in this case)
		// which then allows us to successfully instantiate the blocktype controller.
		self::getBlockTypeInfoByHandle($btHandle);
		
		$btClass = self::getBlockTypeControllerName($btHandle);
		$btInstance = new $btClass;
		return $btInstance->btDCProRepeatingItemFields;
	}
	
	public static function validate($data) {
		$e = Loader::helper('validation/error');
		
		//pkg and blocks dirs must be writable
		if (!self::isPkgBlocksDirWritable()) {
			$e->add(t("Warning: The package blocks directory ('%s') is not writeable. Blocks types cannot be created or edited until permissions are changed on your server.", self::getPkgBlocksDir()));
		}
		if (!self::isCustomViewsDirWritable()) {
			$e->add(t("Your site's top-level /blocks/ directory is not writeable. Blocks types cannot be created or edited until permissions are changed on your server."));
		}
		
		$is_installed = empty($data['orig_btHandle']) ? false : self::isBlockTypeInstalled($data['orig_btHandle']);
		
		//handle is required
		if (empty($data['btHandle'])) {
			$e->add(t('BlockType Handle is required.'));
		//handle maxlen
		} else if (strlen($data['btHandle']) > 32) {
			$e->add(t('BlockType Handle cannot exceed 32 characters in length.'));
		//handle must be handle-like
		} else if (!preg_match('/^[a-z_]+$/', $data['btHandle'])) {
			$e->add(t('BlockType Handle can only contain lowercase letters and underscores'));
		//handle cannot be changed on installed blocktypes
		} else if ($is_installed && ($data['btHandle'] !== $data['orig_btHandle'])) {
			$e->add(t('BlockType Handle cannot be changed while the BlockType is installed.'));
		//new/changed handle must not already be in use (in wider c5 system, or in our blocks dir?)
		} else if (!self::validateBtHandleUniqueness($data['btHandle'], $data['orig_btHandle'])) {
			$e->add(t('BlockType Handle is already in use by another block type. Note that the other block type might be installed or uninstalled, and it might be a Designer Content Pro block or it might be a "normal" C5 block.'));
		//no changes are allowed if an override controller.php file exists
		} else if (file_exists(self::getCustomViewControllerPath($data['btHandle']))) {
			$e->add(t("No changes can be made because a custom controller override exists in '%s'", self::getCustomViewControllerPath($data['btHandle'])));
		}
		
		//name is required
		if (empty($data['btName'])) {
			$e->add(t('BlockType Name is required.'));
		//name maxlen
		} else if (strlen($data['btName']) > 128) {
			$e->add(t('BlockType Name cannot exceed 128 characters in length.'));
		//name cannot have quotation marks (due to C5 js bug -- it's okay to have them but then they don't get displayed properly in the "add blocks" dialog)
		} else if (strpos($data['btName'], '"') !== false) {
			//bug was fixed in 5.6.2.1
			if (version_compare(APP_VERSION, '5.6.2.1', '<')) {
				$e->add(t('BlockType Name cannot contain quotation marks (").'));
			}
		}
		
		//FIELDS...
		//must be at least one field
		$is_at_least_one_field = false;
		if (!empty($data['repeating_items'])) {
			foreach ($data['repeating_items'] as $repeating_item) {
				if (!$repeating_item['isDeleted']) {
					$is_at_least_one_field = true;
					break;
				}
			}
		}
		if (!$is_at_least_one_field) {
			$e->add(t('BlockType must have at least one repeating item field.'));
		} else {
			//field handles must be unique
			// (ignore empty fields in this check -- they will be caught in the per-field checks,
			//  and if we catch them here too it will be a confusing error message to the user)
			self::validateNonEmptyFieldHandleUniqueness($data['repeating_items'], $e);
			
			$orig_repeating_items = array();
			if (!empty($data['orig_repeating_items'])) { //orig_repeating_items is not sent at all for new records (because jquery $.ajax() doesn't send empty arrays)
				foreach ($data['orig_repeating_items'] as $orig_repeating_item) {
					$orig_repeating_items[$orig_repeating_item['id']] = $orig_repeating_item; //make orig_repeating_items easily fetchable via id so we can compare data between current item and its original data
				}
			}
			$row_num = 1; //for error messages (so we can tell user which row has a problem)
			foreach ($data['repeating_items'] as $repeating_item) {
				if ($repeating_item['isDeleted']) {
					continue; //ignore this field -- it has been deleted by the user
				}
				
				$ordinal = ($row_num == 1) ? t('1st') : (($row_num == 2) ? t('2nd') : (($row_num == 3) ? t('3rd') : $row_num . t('th')));
				$row_num++;
				
				$orig_repeating_item = empty($orig_repeating_items[$repeating_item['id']]) ? null : $orig_repeating_items[$repeating_item['id']];
				$is_new_field = is_null($orig_repeating_item);
				
				//field handle required
				if (empty($repeating_item['handle'])) {
					$e->add(t('%s field is missing a handle.', $ordinal));
				//field handle handle-ness (a bit more lenient than btHandle, because this "handle" becomes an array key or object member name -- not a class name like btHandle)
				} else if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]+$/', $repeating_item['handle'])) {
					$e->add(t('%s field\'s handle contains invalid character(s) (it must start with a letter, and can only contain letters, numbers, and underscores)', $ordinal));
				//field handles cannot be changed on installed blocktypes
				} else if ($is_installed && !$is_new_field && ($repeating_item['handle'] != $orig_repeating_item['handle'])) {
					$e->add(t('%s field\'s handle cannot be changed because the block type is currently installed.', $ordinal));
				}
			
				//field type is required
				if (empty($repeating_item['type'])) {
					$e->add(t('%s field is missing a type.', $ordinal));
				//field type must be valid (in the list of available types)
				} else if (!in_array($repeating_item['type'], self::$available_field_type_handles)) {
					$e->add(t('%s field\'s type is invalid -- you must choose from the dropdown list.', $ordinal)); //not sure how this would happen, but just in case
				//field type cannot be changed on installed blocktypes
				} else if ($is_installed && !$is_new_field && ($repeating_item['type'] != $orig_repeating_item['type'])) {
					$e->add(t('%s field\'s type cannot be changed because the block type is currently installed.', $ordinal));
				}
			
				//field label is required
				if (empty($repeating_item['label'])) {
					$e->add(t('%s field is missing a label.', $ordinal));
				}
				
			}//END per-field validations
		}//END at-least-one-field-is-required check
		
		return $e;
	}
	
	//Returns true (valid) if the given "new handle" isn't already in use by another blocktype.
	//Returns false (INvalid) if the given "new handle" IS already in use.
	//Note that we check both installed and not-installed blocktypes
	// in the normal C5 system and DC Pro (and we also check in the top-level /blocks/ directory).
	// The only place we don't check is not-installed blocktypes in other packages (which shouldn't cause problems).
	private static function validateBtHandleUniqueness($new_btHandle, $original_btHandle) {
		if (!is_null($original_btHandle) && ($new_btHandle === $original_btHandle)) {
			//handle wasn't changed, so this validation doesn't apply
			return true;
		}
		
		$bts_c5_not_installed = BlockTypeList::getAvailableList();
		$bts_c5_installed = BlockTypeList::getInstalledList();
		$bts_dcp_all = self::getAllBlockTypes();
		$bts_combined = array_merge($bts_c5_not_installed, $bts_c5_installed, $bts_dcp_all);
		
		$existing_btHandles = array();
		foreach ($bts_combined as $bt) {
			$existing_btHandles[] = $bt->btHandle;
		}
		
		//now check the top-level /blocks/ directory (just in case user has custom override code in there)
  		$toplevel_blocks_dir = @glob(DIR_FILES_BLOCK_TYPES . '/*');
		if ($toplevel_blocks_dir === false) { //glob should return an empty array if nothing is in the directory, but sometimes it returns FALSE for no apparent reason (has something to do with the php installation, or some combination of the php version with certain OS's???)
			$toplevel_blocks_dir = array();
		}
		foreach ($toplevel_blocks_dir as $dir) {
			$existing_btHandles[] = basename($dir);
		}
		
		
		return !in_array($new_btHandle, $existing_btHandles);
	}
	
	private static function validateNonEmptyFieldHandleUniqueness($repeating_items, &$e) {
		$handles = array();
		$dups = array();
		foreach ($repeating_items as $repeating_item) {
			if (!empty($repeating_item['handle'])) {
				$handle = $repeating_item['handle'];
				if (in_array($handle, $handles)) {
					if (!in_array($handle, $dups)) {
						$dups[] = $handle;
					}
				} else {
					$handles[] = $handle;
				}
			}
		}
		
		foreach ($dups as $dup) {
			$e->add(t("Field handle '%s' is used more than once. Each field must have a unique handle.", $dup));
		}
	}
	
	public static function getPageUsage($btHandle) {
		$db = Loader::db();
		$sql = "SELECT cv.cID, cv.cvName, cv.cvIsApproved, p.cIsTemplate, p.cIsSystemPage, p2.cFilename AS parent_cFilename, COUNT(*) as block_count"
		     . ", (p2.cFilename = '/dashboard/view.php') AS is_newsflow"
		     . ", (p2.cFilename = '/!stacks/view.php') AS is_stack"
		     . ", COUNT(*) AS count"
		     . " FROM BlockTypes bt"
		     . " INNER JOIN Blocks b on bt.btID = b.btID"
		     . " INNER JOIN CollectionVersionBlocks cvb ON b.bID = cvb.bID"
		     . " INNER JOIN CollectionVersions cv ON cv.cvID = cvb.cvID AND cv.cID = cvb.cID"
		     . " INNER JOIN Pages p ON cv.cID = p.cID"
		     . " LEFT JOIN Pages p2 ON p.cParentID = p2.cID"
		     . " WHERE bt.btHandle = ?"
		     . " GROUP BY cv.cID, cv.cvName, cv.cvIsApproved, p.cIsTemplate,  p.cIsSystemPage"
		     . " ORDER BY cv.cID ASC";
		$vals = array($btHandle);
		$records = $db->GetArray($sql, $vals);
		
		$usage = array();
		foreach ($records as $record) {
			$cID = $record['cID'];
			
			if (!array_key_exists($cID, $usage)) {
				if ($record['parent_cFilename'] == '/!stacks/view.php') {
					$special = t('Stack');
				} else if ($record['parent_cFilename'] == '/dashboard/view.php') {
					$special = t('News Flow');
				} else if (!empty($record['cIsTemplate'])) {
					$special = t('Page Type Defaults Template');
				} else if (!empty($record['cIsSystemPage'])) {
					$special = t('System Page');
				} else {
					$special = '';
				}
			
				$usage[$cID] = array(
					'name' => htmlentities($record['cvName'], ENT_QUOTES, APP_CHARSET),
					'special' => htmlentities($special, ENT_QUOTES, APP_CHARSET),
					'approved_count' => 0,
					'unapproved_count' => 0,
				);
			}
			
			$count_key = $record['cvIsApproved'] ? 'approved_count' : 'unapproved_count';
			$usage[$cID][$count_key] += $record['block_count'];
		}
		
		return $usage;
	}
		
	public static function save($data) {
		Loader::library('block_generator', 'designer_content_pro');
		$generator = new DesignerContentProBlockGenerator;
		
		$is_new = empty($data['orig_btHandle']);
		$is_installed = $is_new ? false : self::isBlockTypeInstalled($data['orig_btHandle']);
		
		//Gather blocktype data
		$btHandle = $is_installed ? $data['orig_btHandle'] : $data['btHandle']; //this is probably redundant (because validation should prevent btHandle changes on installed blocktypes), but we want to be extra safe.
		$btName = $data['btName'];
		$btDescription = $data['btDescription'];
		$btTable = self::getBlockTypeDatabaseTableName($btHandle);
		
		//Gather repeating item field data
		list($field_type_labels, $field_type_options) = self::getAvailableFieldTypeLabelsAndOptions(); //we only care about $field_type_options
		$repeating_item_fields = array();
		foreach ($data['repeating_items'] as $repeating_item) {
			if (!$repeating_item['isDeleted']) {
				$repeating_item_fields[$repeating_item['displayOrder']] = array(
					'handle' => $repeating_item['handle'],
					'type' => $repeating_item['type'],
					'label' => $repeating_item['label'],
					'options' => self::massageOptionsForSave($repeating_item, $field_type_options),
				);
			}
		}
		ksort($repeating_item_fields);
		$repeating_item_fields = array_values($repeating_item_fields); //we don't need the keys now that we've sorted the array
		
		//Write files
		if ($is_installed) {
			//This is an edit to an installed blocktype.
			//We want to minimize destruction on this, so only overwrite the block controller.php file.
			$generator->updatePkgBlockTypeController($btHandle, $btName, $btDescription, $btTable, $repeating_item_fields);
		} else {
			//This is either a brand new blocktype, or an edit to an existing uninstalled one.
			//For existing uninstalled blocktypes, just delete everything and recreate it
			// with the new info (since uninstalling a blocktype deletes all of its page placement
			// and saved content data, there is no harm in wiping everything out and recreating it).
			//The only exception to this is for the custom block view dir (in the site's top-level /blocks/
			// directory) -- this we want to keep (or rename if btHandle was changed).

			if (!$is_new) {
				//delete everything (except the custom block view dir) -- it will all be recreated in subsequent steps
				self::delete($data['orig_btHandle'], false);
			}
			
			//create blocktype files in package /blocks/ directory
			$generator->createPkgBlockType($btHandle, $btName, $btDescription, $btTable, $repeating_item_fields);
			
			//create "view override" file in site's top-level /blocks/ directory
			if ($is_new) {
				$generator->createOverrideView($btHandle);
			//...or move the existing one if this was an existing blocktype that had its handle changed...
			} else if ($data['btHandle'] != $data['orig_btHandle']) {
				$old_dir = self::getCustomViewDir($data['orig_btHandle']);
				$new_dir = self::getCustomViewDir($data['btHandle']);
				
				//just to be safe, don't do anything if a custom block view dir already exists for the new handle
				if (!is_dir($new_dir)) {
					//IF the old dir exists, rename it to the new one...
					if (is_dir($old_dir)) {
						rename($old_dir, $new_dir);
					//BUT if the old dir didn't exist for some reason, just create a new one from scratch
					} else {
						$generator->createOverrideView($btHandle);
					}
				}
			}
			//end "view override" stuff
				
		}
		
		return self::getBlockTypeInfoByHandle($btHandle);
	}
	//Internal helper function for save()
	//Makes option values consistent for writing to the controller array.
	//As of now, we only modify "checkbox" option types (convert "1" to true,
	// and don't write anything at all for falsey values)
	//
	//Note that the $field_type_options arg should be the 2nd result of getAvailableFieldTypeLabelsAndOptions()
	// (passing it in is an optimization, so the caller can retrieve that info one time
	// and pass it back here as needed).
	private static function massageOptionsForSave(&$repeating_item, &$field_type_options) {
		if (empty($repeating_item['options'])) {
			return array();
		}
		
		$options_info = $field_type_options[$repeating_item['type']];
		
		$return_options = array();
		foreach ($repeating_item['options'] as $option_key => $option_value) {
			if ($options_info[$option_key]['type'] == 'checkbox') {
				if (!empty($option_value)) {
					$return_options[$option_key] = true;
				}
			} else {
				$return_options[$option_key] = $option_value;
			}
		}
		
		return $return_options;
	}
	
	public static function install($btHandle) {
		$btInfo = self::getBlockTypeInfoByHandle($btHandle);
		if (is_null($btInfo)) {
			return;
		}
		
		$pkg = Package::getByHandle('designer_content_pro');
		Loader::library('dcp_controller', 'designer_content_pro'); //must include this here, otherwise next line fails
		BlockType::installBlockTypeFromPackage($btHandle, $pkg);
	}
	
	public static function uninstall($btHandle) {
		if (!self::isBlockTypeInstalled($btHandle)) {
			return; //already uninstalled, so do nothing
		}
		
		//Uninstall
		BlockType::getByHandle($btHandle)->delete();
		
		//Delete its primary (db.xml) table too
		// (even though it means losing all entered data)
		//BECAUSE: users can do things to block definitions when it's not-installed
		// that they otherwise couldn't do when it is installed [e.g. change field handle])
		//SO: if they uninstall a block, change its definitions, then re-install it...
		//     we don't want leftover weird data hanging around!
		//(BESIDES... C5 will remove the association of that block with its areas anyway,
		//  so there's no realistic way for users to get their data back anyway).
		$sql = 'DROP TABLE ' . self::getBlockTypeDatabaseTableName($btHandle);
		Loader::db()->Execute($sql);
	}
	
	public static function delete($btHandle, $delete_custom_view_dir = true) {
		self::uninstall($btHandle);
		
		if ($delete_custom_view_dir) {
			self::rrmdir(self::getCustomViewDir($btHandle));
		}
		
		self::rrmdir(self::getPkgBlockDir($btHandle));
	}
	
		private function rrmdir($dir) {
			//Use c5's file helper (if available) to work within marketplace guidelines
			if (version_compare(APP_VERSION, '5.6.1', '<')) {
			    self::recursivelyRemoveDirectory($dir);
			} else {
				Loader::helper('file')->removeAll($dir);
			}
		}
		
		//This is an exact copy of the "removeAll" function from the C5 core 'file' helper in 5.6.2.1
		private function recursivelyRemoveDirectory($source) {
			$r = @glob($source);
			if (is_array($r)) {
				foreach($r as $file) {
					if (is_dir($file)) {
						self::recursivelyRemoveDirectory("$file/*");
						rmdir($file);
					} else {
						unlink($file);
					}
				}
			}
		}
	//END delete() helpers
}

//This class generates a detailed report of changes that will happen
// when the given data (which was POSTed by the user) is saved.
// (The code in here is a bit messy -- it is isolated into its own class
// in preparation for a future refactoring.)
class DcpBlockTypeChangeReport {
	
	//Returns an array, each item representing a change.
	//
	//Each "change" item is itself an array, with 2 elements:
	// -'impact': denotes if the change is "safe" in terms of data loss, etc.
	//            Possible values are:
	//            -'safe': this change results in no data loss and no file changes
	//            -'data': this change may result in data loss
	//            -'files': this change will result in files and/or directories being renamed on the server
	//            -'': empty string is used in lieu of 'safe' and 'data' when the blocktype isn't installed
	// -'desc': human-readable HTML string that explains the change.
	// -'note': human-readable HTML string with secondary info for user (usually about the side-effects of the change).
	//          The note will often be an empty string, but the description is always provided.
	// -'reindex': boolean, TRUE if we recommend a re-index of the search engine due to this change (otherwise FALSE)
	//
	//Note that we only report on edits to existing blocktypes.
	// (If this is a brand new blocktype we return an empty array.)
	public function getList($data) {
		$ret = array();
		
		$changes = self::getChanges($data);
		$is_installed = $changes['is_new'] ? false : DcpBlockTypeModel::isBlockTypeInstalled($data['orig_btHandle']);
		
		//load up descriptive info about each field type and field option,
		// so we can give more human-readable messages (i.e. we won't have to display type/option handles)
		list($field_type_labels, $field_type_options) = DcpBlockTypeModel::getAvailableFieldTypeLabelsAndOptions();
		
		if (!empty($changes['btHandle'])) {
			$ret[] = array(
				'impact' => 'files', //unlike 'safe' and 'data' changes, files will be changed regardless of whether or not the blocktype is installed
//				'desc' => t("Change <b>blocktype handle</b><br>from <code>%s</code> to <code>%s</code>", $changes['btHandle']['from'], $changes['btHandle']['to']),
				'desc' => t("Change <b>blocktype handle</b> to <code>%s</code>", $changes['btHandle']['to']),
				'note' => t("The block <b>template directory</b> will be renamed<br>from <code>%s</code> to <code>%s</code><br>(in <code>%s</code>)", $changes['btHandle']['from'], $changes['btHandle']['to'], DIR_FILES_BLOCK_TYPES),
				'reindex' => false,
			);
		}
		
		if (!empty($changes['btName'])) {
			$ret[] = array(
				'impact' => ($is_installed ? 'safe' : ''),
//				'desc' => t("Change <b>blocktype name</b><br>from <code>%s</code> to <code>%s</code>", $changes['btName']['from'], $changes['btName']['to']),
				'desc' => t("Change <b>blocktype name</b> to <code>%s</code>", $changes['btName']['to']),
				'note' => '',
				'reindex' => false,
			);
		}
		
		if (!empty($changes['btDescription'])) {
			//btDescription is an optional field, so display placeholder text (e.g. "<blank>")
			// when changing to or from an empty description.
			$from = empty($changes['btDescription']['from']) ? t('&lt;blank&gt;') : $changes['btDescription']['from'];
			$to = empty($changes['btDescription']['to']) ? t('&lt;blank&gt;') : $changes['btDescription']['to'];
			$ret[] = array(
				'impact' => ($is_installed ? 'safe' : ''),
//				'desc' => t("Change <b>blocktype description</b><br>from <code>%s</code> to <code>%s</code>", $from, $to),
				'desc' => t("Change <b>blocktype description</b> to <code>%s</code>", $to),
				'note' => '',
				'reindex' => false,
			);
		}
		
		foreach ($changes['added_fields'] as $field_handle) {
			$ret[] = array(
				'impact' => ($is_installed ? 'safe' : ''),
				'desc' => t("Added new field <b>%s</b>.", $field_handle),
				'note' => t("Remember to update the block's view.php so this field gets displayed.<br>(But keep in mind that existing blocks will be missing data for this field until they are edited.)"),
				'reindex' => $is_installed,
			);
		}
		
		foreach ($changes['removed_fields'] as $field_handle) {
			$note = t("<b>You must remove this field from the block's view.php file to prevent PHP errors!</b>");
			if ($is_installed) {
				$note = '<ol><li style="font-weight: bold;">'
				      .	t('This will cause permanent data loss to this field if the blocktype is placed on pages!')
				      . '</li><li style="font-weight: bold;">'
				      . $note
				      . '</li></ol>';
			}
			$ret[] = array(
				'impact' => ($is_installed ? 'data' : ''),
				'desc' => t("Removed existing field <b>%s</b>", $field_handle),
				'note' => $note,
				'reindex' => $is_installed,
			);
		}
		
		foreach ($changes['field_handle'] as $change) {
			if (!is_null($change['from']) && !is_null($change['to'])) {
				$ret[] = array(
					'impact' => ($is_installed ? 'safe' : ''),
//					'desc' => t("Changed <b>field handle</b><br>from <code>%s</code> to <code>%s</code>", $change['from'], $change['to']),
					'desc' => t("Changed <b>field handle</b> to <code>%s</code>", $change['to']),
					'note' => t("Remember to update the code in the block's view.php file accordingly."),
					'reindex' => false,
				);
			}
		}
		
		foreach ($changes['field_type'] as $field_handle => $change) {
			if (!is_null($change['from']) && !is_null($change['to'])) {
				$type_label_from = $field_type_labels[$change['from']];
				$type_label_to = $field_type_labels[$change['to']];
				$ret[] = array(
					'impact' => ($is_installed ? 'safe' : ''),
//					'desc' => t("<b>%s</b>: changed <b>field type</b><br>from <code>%s</code> to <code>%s</code>", $field_handle, $type_label_from, $type_label_to),
					'desc' => t("<b>%s</b>: changed <b>field type</b> to <code>%s</code>", $field_handle, $type_label_to),
					'note' => t("You may need to change code in the block's view.php file (depending on which functions you are using to display the field's data)."),
					'reindex' => false, //no need to reindex because field_type cannot be changed on installed blocktypes
				);
			}
		}
		
		foreach ($changes['field_label'] as $field_handle => $change) {
			if (!is_null($change['from']) && !is_null($change['to'])) {
				$ret[] = array(
					'impact' => ($is_installed ? 'safe' : ''),
//					'desc' => t("<b>%s</b>: changed <b>field label</b><br>from <code>%s</code> to <code>%s</code>", $field_handle, $change['from'], $change['to']),
					'desc' => t("<b>%s</b>: changed <b>field label</b> to <code>%s</code>", $field_handle, $change['to']),
					'note' => '',
					'reindex' => false,
				);
			}
		}
		
		//In preparation for option changes, map field handles to their field types
		// (so we can get human-readable option info for each option handle).
		$field_types_per_handle = array();
		foreach ($data['repeating_items'] as $repeating_item) {
			$field_types_per_handle[$repeating_item['handle']] = $repeating_item['type'];
		}
		
		//Now go through the option changes
		foreach ($changes['field_options'] as $field_handle => $option_change) {
			
			if (!in_array($field_handle, $changes['added_fields']) && !in_array($field_handle, $changes['removed_fields'])) { //don't report option changes for new fields or deleted fields.
				
				$available_options_info = $field_type_options[$field_types_per_handle[$field_handle]];
				foreach ($option_change as $option_handle => $change) {
					$option_info = $available_options_info[$option_handle];
					
					if (is_null($change['from'])) {
						
						$desc = t("<b>%s</b>: set option <code>%s</code>", $field_handle, $option_info['label']);
						
						if ($option_info['type'] == 'select') {
							$desc .= t(" to <code>%s</code>", $option_info['choices'][$change['to']]);
						} else if ($option_info['type'] == 'checkbox') {
							//for checkboxes, we'll just say "set option x on field y"
							// (as opposed to "set option x to true" or "set option x to yes")
							//so do nothing here.
						} else {
							$desc .= t(" to <code>%s</code>", $change['to']);
						}
						
					} else if (is_null($change['to'])) {
					
						$desc = t("<b>%s</b>: cleared option <code>%s</code>", $field_handle, $option_info['label']);
					
					} else {
						$from_value = ($option_info['type'] == 'select') ? $option_info['choices'][$change['from']] : $change['from'];
						$from_value = empty($from_value) ? t('&lt;blank&gt;') : $from_value;
						$to_value = ($option_info['type'] == 'select') ? $option_info['choices'][$change['to']] : $change['to'];
						$to_value = empty($to_value) ? t('&lt;blank&gt;') : $to_value;
//						$desc = t("<b>%s</b>: changed option <b>%s</b><br>from <code>%s</code> to <code>%s</code>", $field_handle, $option_info['label'], $from_value, $to_value);
						$desc = t("<b>%s</b>: changed option <b>%s</b> to <code>%s</code>", $field_handle, $option_info['label'], $to_value);
					}
					
					$note = '';
					if ($option_handle == 'required') {
						if (is_null($change['from'])) {
							$note = t('This field will now be required when users add/edit blocks, but blocks that existed before this change may still contain missing data.');
						} else if (is_null($change['to'])) {
							$note = t("This field will no longer be required. You may want to update your code in the block's view.php file if it assumes this field's data will always exist (for example, you may now want to check if the field is empty before displaying it).");
						}
					}
					
					$ret[] = array(
						'impact' => ($is_installed ? 'safe' : ''),
						'desc' => $desc,
						'note' => $note,
						'reindex' => ($is_installed && ($option_handle == 'searchable')),
					);
				}
			}
		}
		
		$display_order_changed = false;
		foreach ($changes['field_displayOrder'] as $change) {
			if (!is_null($change['from']) && !is_null($change['to'])) {
				$display_order_changed = true;
				break;
			}
		}
		if ($display_order_changed) {
			$ret[] = array(
				'impact' => ($is_installed ? 'safe' : ''),
				'desc' => t("Changed the <b>display order</b> of fields."),
				'note' => t("Note that this only affects the block add/edit dialog (not the view template)."),
				'reindex' => false,
			);
		}
		
		return $ret;
	}
	
	//Returns an array of information about what needs to be changed for a particular edit.
	//This info can be used by the getChangeMessages() function so it knows what to say.
	//
	//Historical Note: originally both the getChangeMessages() and save() functions used this info,
	// but now save() always just overwrites everything so it doesn't care what changed.
	//Since we already did the work to abstract this logic out, though, we're keeping this function here.
	//
	//Note that the array is structured with the change operation at the top-level,
	// then the relevant fields below that (because this is how the save() function
	// needed the data... back when the save() function actually called this).
	private static function getChanges($data) {
		$changes = array(
			'is_new' => false,
			'is_installed' => false, //this one doesn't pertain to whether data was changed or not, but it is useful to have
			'btHandle' => array(),
			'btName' => array(),
			'btDescription' => array(),
			'added_fields' => array(),
			'removed_fields' => array(),
			'field_handle' => array(),
			'field_type' => array(),
			'field_label' => array(),
			'field_options' => array(),
			'field_displayOrder' => array(),
		);
		
		$is_new = empty($data['orig_btHandle']);
		$changes['is_new'] = $is_new;
		
		$is_installed = $is_new ? false : DcpBlockTypeModel::isBlockTypeInstalled($data['orig_btHandle']);
		$changes['is_installed'] = $is_installed;
		
		//btHandle change (only valid for uninstalled blocktypes -- we assume validate() prevents this for installed blocktypes)
		if ($data['btHandle'] != $data['orig_btHandle']) {
			$changes['btHandle'] = array(
				'from' => $is_new ? null : $data['orig_btHandle'],
				'to' => $data['btHandle'],
			);
		}
		
		//name change
		if ($data['btName'] != $data['orig_btName']) {
			$changes['btName'] = array(
				'from' => $is_new ? null : $data['orig_btName'],
				'to' => $data['btName'],
			);
		}
		
		//description change
		if ($data['btDescription'] != $data['orig_btDescription']) {
			$changes['btDescription'] = array(
				'from' => $is_new ? null : $data['orig_btDescription'],
				'to' => $data['btDescription'],
			);
		}
		
		//data prep for per-field changes...
		$orig_repeating_items = array();
		if (!empty($data['orig_repeating_items'])) { //orig_repeating_items is not sent at all for new records (because jquery $.ajax() doesn't send empty arrays)
			foreach ($data['orig_repeating_items'] as $orig_repeating_item) {
				$orig_repeating_items[$orig_repeating_item['id']] = $orig_repeating_item; //make orig_repeating_items easily fetchable via id so we can compare data between current item and its original data
			}
		}
		
		//per-field repeating item changes
		foreach ($data['repeating_items'] as $repeating_item) {
			
			$orig_repeating_item = empty($orig_repeating_items[$repeating_item['id']]) ? null : $orig_repeating_items[$repeating_item['id']];
			
			$is_new_field = is_null($orig_repeating_item);
			$is_deleted_field = $repeating_item['isDeleted'];
				
			//added fields
			if ($is_new_field && !$repeating_item['isDeleted']) {
				$changes['added_fields'][] = $repeating_item['handle'];
				
			}
			
			//deleted fields
			if (!$is_new_field && $repeating_item['isDeleted']) {
				$changes['removed_fields'][] = $repeating_item['handle'];
			}
			
			//edited field handles (only valid for uninstalled blocktypes -- we assume validate() prevents this for installed blocktypes)
			if ($is_new_field || ($orig_repeating_item['handle'] != $repeating_item['handle'])) {
				$changes['field_handle'][] = array(
					'from' => $is_new_field ? null : $orig_repeating_item['handle'],
					'to' => $is_deleted_field ? null : $repeating_item['handle'],
				);
			}
			
			//edited field types (only valid for uninstalled blocktypes -- we assume validate() prevents this for installed blocktypes)
			if ($is_new_field || ($orig_repeating_item['type'] != $repeating_item['type'])) {
				$changes['field_type'][$repeating_item['handle']] = array(
					'from' => $is_new_field ? null : $orig_repeating_item['type'],
					'to' => $is_deleted_field ? null : $repeating_item['type'],
				);
			}
			
			//edited field labels (no prob!)
			if ($is_new_field || ($orig_repeating_item['label'] != $repeating_item['label'])) {
				$changes['field_label'][$repeating_item['handle']] = array(
					'from' => $is_new_field ? null : $orig_repeating_item['label'],
					'to' => $is_deleted_field ? null : $repeating_item['label'],
				);
			}
			
			//changed display order
			if ($is_new_field || ($orig_repeating_item['displayOrder'] != $repeating_item['displayOrder'])) {
				$changes['field_displayOrder'][$repeating_item['handle']] = array(
					'from' => $is_new_field ? null : $orig_repeating_item['displayOrder'],
					'to' => $is_deleted_field ? null : $repeating_item['displayOrder'],
				);
			}
			
			//edited field options:
			$options = empty($repeating_item['options']) ? array() : $repeating_item['options'];
			$orig_options = empty($orig_repeating_item['options']) ? array() : $orig_repeating_item['options'];
			foreach ($options as $option_handle => $value) {
				if (!array_key_exists($option_handle, $orig_options)) {
					$changes['field_options'][$repeating_item['handle']][$option_handle] = array(
						'from' => null,
						'to' => $value,
					);
				} else if ($orig_options[$option_handle] != $options[$option_handle]) {
					$changes['field_options'][$repeating_item['handle']][$option_handle] = array(
						'from' => $orig_options[$option_handle],
						'to' => $options[$option_handle],
					);
				}
			}
			foreach ($orig_options as $option_handle => $value) {
				if (!array_key_exists($option_handle, $options)) {
					$changes['field_options'][$repeating_item['handle']][$option_handle] = array(
						'from' => $option_handle,
						'to' => null,
					);
				}
			}
		} //END per-repeating-item loop
		
		return $changes;
	}
		
}