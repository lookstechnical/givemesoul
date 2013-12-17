<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


Loader::model('dcp_block_type', 'designer_content_pro');

class DesignerContentProBlockGenerator {

	public function createPkgBlockType($btHandle, $btName, $btDescription, $btTable, $repeating_item_fields) {
		$dir = $this->createPkgDir($btHandle);
		$this->createPkgAddPhp($dir);
		$this->createPkgControllerPhp($dir, $btHandle, $btName, $btDescription, $btTable, $repeating_item_fields);
		$this->createPkgDbXml($dir, $btTable);
		$this->createPkgEditPhp($dir);
		$this->createPkgTools($dir);
		$this->createPkgViewPhp($dir, $btHandle);
	}
	
	public function createOverrideView($btHandle) {
		$dir = $this->createOverrideDir($btHandle);
		$this->createOverrideViewPhp($dir);
	}
	
	public function updatePkgBlockTypeController($btHandle, $btName, $btDescription, $btTable, $repeating_item_fields) {
		$dir = DcpBlockTypeModel::getPkgBlockDir($btHandle);
		$this->createPkgControllerPhp($dir, $btHandle, $btName, $btDescription, $btTable, $repeating_item_fields);
		//Note that we don't delete the existing file first,
		// because this class's outputFile() function uses
		// file_put_contents() (which overwrites if the file already exists).
	}

/*** GENERATOR FUNCTIONS ***/
	private function createPkgDir($btHandle) {
		$dir = DcpBlockTypeModel::getPkgBlockDir($btHandle);
		$this->outputDir($dir);
		return $dir;
	}
	
	private function createOverrideDir($btHandle) {
		$dir = DcpBlockTypeModel::getCustomViewDir($btHandle);
		$this->outputDir($dir);
		return $dir;
	}
	
	private function createPkgAddPhp($dir) {
		$contents = $this->getC5Header() . PHP_EOL . PHP_EOL
		          . $this->getHeaderComments() . PHP_EOL . PHP_EOL
		          . '$this->inc(\'edit.php\');' . PHP_EOL;
		$path = $dir . '/add.php';
		$this->outputFile($path, $contents);
	}
	
	private function createPkgControllerPhp($dir, $btHandle, $btName, $btDescription, $btTable, $repeating_item_fields) {
		$escaped_btName = $this->esc($btName);
		$escaped_btDescription = $this->esc($btDescription);
		$escaped_btTable = $this->esc($btTable);
		$escaped_DCProVersion = $this->esc(Package::getByHandle('designer_content_pro')->getPackageVersion());
		
		$contents = $this->getC5Header()
		          . PHP_EOL . PHP_EOL
		          . $this->getHeaderComments(false, true, $btHandle)
		          . PHP_EOL . PHP_EOL
		          . 'Loader::library(\'dcp_controller\', \'designer_content_pro\');'
		          . PHP_EOL . PHP_EOL
		          . 'class ' . DcpBlockTypeModel::getBlockTypeControllerName($btHandle) . ' extends DcpController {'
		          . PHP_EOL . PHP_EOL
		          . "\tprotected \$btHandle = '{$btHandle}';\n"
		          . "\tprotected \$btName = '{$escaped_btName}';\n"
		          . "\tprotected \$btDescription = '{$escaped_btDescription}';\n"
		          . "\tprotected \$btTable = '{$escaped_btTable}';\n"
		          . PHP_EOL
		          . "\tprotected \$btCacheBlockRecord = true;\n"
		          . "\tprotected \$btCacheBlockOutput = true;\n"
		          . "\tprotected \$btCacheBlockOutputOnPost = true;\n"
		          . "\tprotected \$btCacheBlockOutputForRegisteredUsers = false;\n"
		          . "\tprotected \$btCacheBlockOutputLifetime = CACHE_LIFETIME;\n"
		          . PHP_EOL
		          . "\tpublic \$btDCProGeneratorVersion = '{$escaped_DCProVersion}'; //" . t("Don't change this!") . "\n"
		          . PHP_EOL
		          . "\tpublic \$btDCProRepeatingItemFields = array(\n"
		          ;
		
		foreach ($repeating_item_fields as $field) {
			$escaped_label = $this->esc($field['label']);
			$contents .= "\t\t'{$field['handle']}' => array("
			           . "'type' => '{$field['type']}'"
			           . ", 'label' => '{$escaped_label}'"
			           . ", 'options' => array("
			           ;
			
			$option_strings = array();
			foreach ($field['options'] as $key => $val) {
				if ($val === true) { //if we don't do this then booleans TRUE gets written as string '1'
					$option_strings[] = "'{$key}' => true";
				} else if ($val === false) { //if we don't do this then booleans FALSE gets written as string '0'
					$option_strings[] = "'{$key}' => false";
				} else {
					$escaped_option_value = $this->esc($val);
					$option_strings[] = "'{$key}' => '{$escaped_option_value}'";
				}
			}
			$contents .= implode(', ', $option_strings); //yes this is extra anal-retentive :)
			$contents .= ")),\n";
		}
		
		$contents .= "\t);\n\n}\n";
		
		$path = $dir . '/controller.php';
		$this->outputFile($path, $contents);
	}
	
	private function esc($str) {
		return str_replace("'", "\\'", $str);
	}
	
	private function createPkgDbXml($dir, $table) {
		$contents = '<?php xml version="1.0"?>' . PHP_EOL . PHP_EOL
		          . $this->getHeaderComments(true) . PHP_EOL . PHP_EOL
		          . '<schema version="0.3">' . PHP_EOL
		          . "\t" . '<table name="' . $table . '">' . PHP_EOL
		          . "\t\t" . '<field name="bID" type="I"><key /><unsigned /></field>' . PHP_EOL
		          . "\t\t" . '<field name="repeatingItemData" type="X2"></field>' . PHP_EOL
		          . "\t" . '</table>' . PHP_EOL
		          . '</schema>' . PHP_EOL;
		
		$path = $dir . '/db.xml';
		$this->outputFile($path, $contents);
	}
	
	private function createPkgEditPhp($dir) {
		$contents = $this->getC5Header() . PHP_EOL . PHP_EOL
		          . $this->getHeaderComments() . PHP_EOL . PHP_EOL
		          . 'Loader::element(\'block_edit\', array(\'controller\' => $controller), \'designer_content_pro\');' . PHP_EOL;
		$path = $dir . '/edit.php';
		$this->outputFile($path, $contents);
	}
	
	private function createPkgTools($dir) {
		$dir = $dir . '/tools';
		$this->outputDir($dir);
		
		$contents = $this->getC5Header() . PHP_EOL . PHP_EOL
		          . $this->getHeaderComments() . PHP_EOL . PHP_EOL
		          . '$btHandle = Request::get()->getBlock();' . PHP_EOL
		          . 'if (!empty($btHandle)) {' . PHP_EOL
		          . "\t" . '$btClass = Loader::helper(\'text\')->camelcase($btHandle) . \'BlockController\';' . PHP_EOL
		          . "\t" . '$controller = new $btClass;' . PHP_EOL
		          . "\t" . 'if (is_object($controller)) {' . PHP_EOL
		          . "\t\t" . 'Loader::element(\'block_edit_repeating_item\', array(\'controller\' => $controller), \'designer_content_pro\');' . PHP_EOL
		          . "\t" . '}' . PHP_EOL
		          . '}' . PHP_EOL;
		
		$path = $dir . '/edit_repeating_item.php';
		$this->outputFile($path, $contents);
	}
	
	private function createPkgViewPhp($dir, $btHandle) {
		$contents = $this->getC5Header() . PHP_EOL . PHP_EOL
		          . $this->getHeaderComments() . PHP_EOL . PHP_EOL
                  . '?>'
		          . PHP_EOL . PHP_EOL
		          . '<p style="color: white; background-color: red;">' . PHP_EOL
		          . "\t" . t('This Designer Content Pro block view must be overridden in <?php  echo DIR_BASE; ?>/blocks/%s/view.php', $btHandle)
		          . PHP_EOL . '</p>'
		          . PHP_EOL;
		
		$path = $dir . '/view.php';
		$this->outputFile($path, $contents);
	}
	
	private function createOverrideViewPhp($dir) {
		$contents = $this->getC5Header() . ' ?>'
		          . PHP_EOL
		          . $this->getPhpTag() . '/* ' . t('This block was made with Designer Content Pro. Visit %s for documentation.', 'http://theblockery.com/dcp') . ' */ ?>'
		          . PHP_EOL . PHP_EOL . PHP_EOL
		          . $this->getPhpTag() . 'foreach ($controller->getRepeatingItems() as $item): ?>'
		          . PHP_EOL . PHP_EOL
		          . "\t" . '<!-- ' . "\n"
		          . "\t" . t('Place markup for each repeating item here.') . "\n"
		          . "\t" . t('Sample code can be found in the Designer Content Pro dashboard page.') . "\n"
		          . "\t" . ' -->'
		          . PHP_EOL . PHP_EOL
		          . $this->getPhpTag() . 'endforeach; ?>'
		          . PHP_EOL;
		
		$path = $dir . '/view.php';
		$this->outputFile($path, $contents);
	}

/*** UTILITY FUNCTIONS ***/
	private function getPhpTag() {
		return '<' . '?' . 'p' . 'h' . 'p' . ' '; //outsmart c5 marketplace process that inserts extra spaces after every php opening tag
	}

	private function getC5Header() {
		return $this->getPhpTag() . 'defined(\'C5_EXECUTE\') or die(_("Access Denied."));';
	}
	
	private function outputDir($path) {
		$path = rtrim($path, '/'); //remove trailing slash
		mkdir($path);
	}

	private function outputFile($path, $contents) {
		//Normally we'd just call: file_put_contents($path, $contents);
		//But we want to use C5's 'file' helper instead
		// to work within marketplace guidelines.
		//Note that the c5 helper only offers file_put_contents() in APPEND mode,
		// so first we must clear the file (if it exists), then append to the empty file.
		$fh = Loader::helper('file');
		$fh->clear($path);
		$fh->append($path, $contents);
	}
	
	//Pass in a btHandle for controller.php, otherwise leave it empty.
	private function getHeaderComments($is_dbxml = false, $is_controllerphp = false, $btHandle = '') {
		
		if ($is_controllerphp) {
			if (empty($btHandle)) {
				throw new Exception('DESIGNER CONTENT PRO ERROR: You must provide a btHandle when calling DesignerContentProBlockGenerator::getHeaderComments() for the controller.php file');
			}
			$comments = 
t('/**
 * WARNING: This is generated code.
 * Anything in this file may get overwritten or deleted without warning.
 * Do NOT edit, rename, move, or delete this file (doing so will cause errors).
 *
 * To customize this controller, first copy it to SITEROOT/blocks/%s/controller.php
 *
 * Visit %s for documentation.
 */', $btHandle, 'http://theblockery.com/dcp');

		} else if ($is_dbxml) {
			$comments =
t('<!--
WARNING: This is generated code.
Anything in this file may get overwritten or deleted without warning.
Do NOT edit, rename, move, or delete this file (doing so will cause errors).
-->');

		} else {
			$comments =
t('/**
 * WARNING: This is generated code.
 * Anything in this file may get overwritten or deleted without warning.
 * Do NOT edit, rename, move, or delete this file (doing so will cause errors).
 */');
		}
		
		return $comments;
	}
	
}