<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


Loader::model('dcp_block_type', 'designer_content_pro');

Loader::library('crud_controller', 'designer_content_pro');
class DashboardBlocksDesignerContentProController extends CrudController {
	
	public function on_before_render() {
		$this->addHeaderItem(Loader::helper('html')->css('dashboard.css', 'designer_content_pro'));
	}
	
	public function view() {
		$this->set('bts', DcpBlockTypeModel::getAllBlockTypes());
	}
	
	
	public function add() {
		$this->edit(null);
	}
	
	public function edit($original_btHandle = null) {
		$is_new = empty($original_btHandle);
		
		if ($is_new) {
			$btHandle = null;
			$btName = null;
			$btDescription = null;
			$repeating_item_fields = array();
		} else {
			//load existing record data from database...
			$btInfo = DcpBlockTypeModel::getBlockTypeInfoByHandle($original_btHandle);
			if (is_null($btInfo)) {
				$this->render404AndExit(); //btHandle not found
			} else if ($btInfo->hasCustomController) {
				$this->flash(t('This blocktype cannot be edited in the dashboard because it has a controller override.'), 'error');
				$this->redirect('view');
			}
			$btHandle = $btInfo->btHandle;
			$btName = $btInfo->btName;
			$btDescription = $btInfo->btDescription;
			$repeating_item_fields = DcpBlockTypeModel::getRepeatingItemFields($btHandle);
		}
		
		$this->set('is_new', $is_new);
		$this->set('is_installed', DcpBlockTypeModel::isBlockTypeInstalled($original_btHandle));
		
		$this->set('can_write_site_blocks', DcpBlockTypeModel::isCustomViewsDirWritable());
		$this->set('can_write_pkg_blocks', DcpBlockTypeModel::isPkgBlocksDirWritable());
		
		list($field_type_labels, $field_type_options) = DcpBlockTypeModel::getAvailableFieldTypeLabelsAndOptions();
		$this->set('field_type_labels', $field_type_labels);
		$this->set('field_type_options', $field_type_options);
		
		$this->set('btHandle', $btHandle);
		$this->set('original_btHandle', $original_btHandle);
		$this->set('btName', $btName);
		$this->set('btDescription', $btDescription);
		$this->set('repeating_item_fields', $repeating_item_fields);
		
		$hh = Loader::helper('html');
		$this->addFooterItem($hh->javascript('jquery.tmpl.min.js', 'designer_content_pro'));
		$this->addFooterItem($hh->javascript('jquery.blockUI.js', 'designer_content_pro'));
		$this->addFooterItem($hh->javascript('dashboard_edit_model.js', 'designer_content_pro'));
		$this->addFooterItem($hh->javascript('dashboard_edit_ui.js', 'designer_content_pro'));
		$this->addHeaderItem($hh->css('tipsy.css', 'designer_content_pro'));
		$this->addFooterItem($hh->javascript('jquery.tipsy.js', 'designer_content_pro'));
		
		$this->render('edit');
	}
	
	
	/** DEV NOTE about comparing new data to "original" data:
	 *
	 * Some validation rules and most confirm messages require that we compare
	 * new data to the "original" data for a particular field.
	 *
	 * Note that we do *not* retrieve the existing field definitions from the block controller class
	 * to compare it to new POSTed data -- instead we rely on front-end javascript
	 * to store the original data when the page is first loaded and then POST
	 * that back to the ajax_validate() and ajax_confirm() methods.
	 *
	 * The reason we do it this way is because the field handle could be changed due to a user edit,
	 * so we need to be able to differentiate between a field handle change versus the deletion
	 * of a field and then an addition of a different field that happens to have the same handle
	 * (and other edge cases).
	 */
	public function ajax_validate() {
		$post = $this->post();
		if (empty($post)) {
			exit;
		}
		
		$errors = array();
		$e = DcpBlockTypeModel::validate($post);
		foreach ($e->getList() as $key => $value) {
			$errors[$key] = htmlentities($value, ENT_QUOTES, APP_CHARSET);
		}
		
		$response = array(
			'success' => !$e->has(),
			'errors' => $errors,
		);
		
		echo Loader::helper('json')->encode($response);
		exit;
	}
	
	public function ajax_confirm() {
		$post = $this->post();
		if (empty($post)) {
			exit;
		}
		
		$is_new = empty($post['orig_btHandle']);
		
		$response = array( 'isNew' => $is_new );
		if (!$is_new) {
			$response['installed'] = DcpBlockTypeModel::getBlockTypeInfoByHandle($post['orig_btHandle'])->installed;
			$response['usage'] = DcpBlockTypeModel::getPageUsage($post['orig_btHandle']);
			$response['changes'] = DcpBlockTypeChangeReport::getList($post);
		}
		
		echo Loader::helper('json')->encode($response);
		exit;
	}
	
	public function ajax_save() {
		//Upon success, we set a flash message (assuming that the front-end
		// will redirect back to the top-level 'view' action for us) and output nothing.
		//Upon failure, we output the error message.
		//(So you can check for an empty response to determine if the save was successful)
		
		$post = $this->post();
		if (empty($post)) {
			echo t('ERROR: Could not save because no data was sent');
			exit;
		}
		
		//run validation again just to be sure
		$error = DcpBlockTypeModel::validate($post);
		if ($error->has()) {
			echo t('ERROR: Could not save because data was invalid');
			exit;
		}
		
		$btInfo = DcpBlockTypeModel::save($post);
		if (empty($post['orig_btHandle'])) {
			DcpBlockTypeModel::install($btInfo->btHandle);
			$msg = t('New Block Type created and installed.');
		} else {
			$msg = t('Block Type changes have been saved.');
		}
		$this->flash($msg, 'success');
		exit;
	}
	
	public function ajax_show_sample_code($btHandle) {
		$lines = array();
		foreach (array_keys(DcpBlockTypeModel::getRepeatingItemFields($btHandle)) as $handle) {
			$line = '<' . '?' . 'p' . 'h' . 'p' . ' '; //outsmart c5 marketplace process that inserts extra spaces after every php opening tag
			$line .= '$item->' . $handle . '->display();';
			$line .= ' ?>';
			$lines[] = htmlentities($line, ENT_QUOTES, APP_CHARSET);
		}
		
		echo '<p>' . t("Paste this code inside the <code>foreach</code> loop in <code>SITEROOT/blocks/%s/view.php</code>", $btHandle) . '</p>';
		echo '<textarea style="-webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; width:100%; height: 70%; font-family: monospace; white-space: nowrap; resize: none;">';
		echo implode("\n", $lines);
		echo '</textarea>';
		echo '<p>' . t('Visit %s for complete documentation.', '<a href="http://theblockery.com/dcp" target="_blank">http://theblockery.com/dcp</a>') . '</p>';
		exit;
	}
	
	public function refresh($btHandle) {
		//refresh block so C5 recognizes update btHandle/btDescription
		$btInfo = $this->validateAndGetBtInfo($btHandle);
		BlockType::getByID($btInfo->btID)->refresh();
		
		//Set flash message (one was probably set by the ajax_save method,
		// but now that we're redirecting it will get cleared...
		// but since refresh() is only called for edits of existing
		// blocktypes, we know which message it should be).
		$this->flash(t('Block Type changes have been saved.'), 'success');
		$this->redirect('view');
	}
	
	public function install($btHandle) {
		$btInfo = $this->validateAndGetBtInfo($btHandle);
		
		DcpBlockTypeModel::install($btHandle);
		
		$msg = t("Block type '%s' successfully installed.", $btInfo->btName);
		$this->flash($msg, 'success');
		$this->redirect('view');
	}
	
	public function uninstall($btHandle) {
		$btInfo = $this->validateAndGetBtInfo($btHandle);
		
		if ($this->post()) {
			DcpBlockTypeModel::uninstall($btHandle);
			$msg = t("Block type '%s' has been uninstalled.", $btInfo->btName);
			$this->flash($msg);
			$this->redirect('view');
		}
		
		$this->set('btInfo', $btInfo);
		$this->set('usage', DcpBlockTypeModel::getPageUsage($btHandle));
		$this->render('uninstall');
	}
		
	public function delete($btHandle) {
		$btInfo = $this->validateAndGetBtInfo($btHandle);
		
		if ($this->post()) {
			DcpBlockTypeModel::delete($btHandle);
			$msg = t("Block type '%s' has been permanently deleted.", $btInfo->btName);
			$this->flash($msg);
			$this->redirect('view');
		}
		
		$this->set('btInfo', $btInfo);
		$this->render('delete');
	}
	
	private function validateAndGetBtInfo($btHandle) {
		$btInfo = DcpBlockTypeModel::getBlockTypeInfoByHandle($btHandle);
		if (is_null($btInfo)) {
			$msg = t('ERROR: Cannot identify block handle');
			$this->flash($msg, 'error');
			$this->redirect('view');
		}
		return $btInfo;
	}

}