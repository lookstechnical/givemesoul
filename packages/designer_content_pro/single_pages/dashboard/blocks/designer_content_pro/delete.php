<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


$dh = Loader::helper('concrete/dashboard');
$ih = Loader::helper('concrete/interface');
?>

<?php  echo $dh->getDashboardPaneHeaderWrapper(t('Delete Block Type'), false, 'span9 offset1', false); ?>
	
	<div class="ccm-pane-body">

		<h3><?php  echo htmlentities($btInfo->btName, ENT_QUOTES, APP_CHARSET); ?> (<?php  echo htmlentities($btInfo->btHandle, ENT_QUOTES, APP_CHARSET); ?>)</h3>
	
		<br>
	
		<div class="alert alert-error">
			<?php  echo t('Warning: All contents of this block directory (including your view.php file and all custom templates) will be permanently deleted!'); ?>
		</div>

	</div>
	
	<div class="ccm-pane-footer">
	
		<form method="post" action="<?php  echo $this->action('delete', $btInfo->btHandle); ?>" style="margin: 0;">
			<?php  echo $token; ?>
			<?php  echo $ih->submit(t('Delete Block Type (including view.php and custom templates)'), false, 'right', 'error'); ?>
			<?php  echo $ih->button(t('Cancel'), $this->action('view'), 'left'); ?>
		</form>
		
	</div>
	
<?php  echo $dh->getDashboardPaneFooterWrapper(); ?>