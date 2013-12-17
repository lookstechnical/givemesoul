<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


/* See css/block_edit.css and js/block_edit.js for styles and code that pertain to this dialog box! */

/* Usability note: we don't have to worry about user accidentally submitting form when hitting "enter"
 * because there is no <input type="submit"> on the form!
 */

Loader::element('editor_config'); //for wysiwyg editors
?>

<form id="repeating-item-edit" data-repeating-item-id="<?php  echo intval($_POST['repeatingItemId']); ?>" class="ccm-ui">
	
	<?php  echo $controller->outputAllRepeatingItemEditFields(); ?>
	
	<div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix" style="margin: 10px -10px -10px -10px;">
		<div class="ccm-buttons dialog-buttons">
			<a href="#close" class="btn ccm-button-left cancel"><?php  echo t('Cancel'); ?></a>
			<a href="#save" class="ccm-button-right accept btn primary"><?php  echo t('Save'); ?></a>
		</div>
	</div>
	
</form>
