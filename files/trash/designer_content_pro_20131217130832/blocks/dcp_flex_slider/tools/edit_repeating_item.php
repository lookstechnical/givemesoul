<?php defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * WARNING: This is generated code.
 * Anything in this file may get overwritten or deleted without warning.
 * Do NOT edit, rename, move, or delete this file (doing so will cause errors).
 */

$btHandle = Request::get()->getBlock();
if (!empty($btHandle)) {
	$btClass = Loader::helper('text')->camelcase($btHandle) . 'BlockController';
	$controller = new $btClass;
	if (is_object($controller)) {
		Loader::element('block_edit_repeating_item', array('controller' => $controller), 'designer_content_pro');
	}
}
