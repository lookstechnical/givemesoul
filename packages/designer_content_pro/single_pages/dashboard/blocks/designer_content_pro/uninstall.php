<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


$dh = Loader::helper('concrete/dashboard');
$ih = Loader::helper('concrete/interface');
?>

<?php  echo $dh->getDashboardPaneHeaderWrapper(t('Uninstall Block Type'), false, 'span10 offset1', false); ?>

	<div class="ccm-pane-body">

		<h3><?php  echo htmlentities($btInfo->btName, ENT_QUOTES, APP_CHARSET); ?> (<?php  echo htmlentities($btInfo->btHandle, ENT_QUOTES, APP_CHARSET); ?>)</h3>
	
		<br>
	
		<?php  if (empty($usage)): ?>
			<div class="alert alert-success">
				<?php  echo t('This block type has not been added to any pages or stacks, so existing content will not be affected.'); ?>
			</div>
		<?php  else: ?>
			<div class="alert alert-error">
				<?php  echo t('This block type has been added to pages and/or stacks.<br><b>Content will be permanently deleted!</b>'); ?>
			</div>
			<table class="table">
				<tr>
					<th style="border-top: none; vertical-align: bottom;"><?php  echo t('cID'); ?></th>
					<th style="border-top: none; vertical-align: bottom;"><?php  echo t('Page Name'); ?></th>
					<th style="border-top: none; text-align: center;"><?php  echo t('Approved<br>Blocks'); ?></th>
					<th style="border-top: none; text-align: center;"><?php  echo t('Version/Preview<br>Blocks'); ?></th>
				</tr>
				<?php  foreach ($usage as $cID => $info): ?>
				<tr>
					<td><a href="<?php  echo Loader::helper('navigation')->getLinkToCollection(Page::getByID($cID)); ?>" target="_blank"><?php  echo $cID; ?></a></td>
					<td><?php 
						echo htmlentities($info['name'], ENT_QUOTES, APP_CHARSET);
						if (!empty($info['special'])) {
							echo ' [' . htmlentities($info['special'], ENT_QUOTES, APP_CHARSET) . ']';
						}
					?></td>
					<td style="text-align: center; font-weight: bold;"><?php  echo $info['approved_count']; ?></td>
					<td style="text-align: center;"><?php  echo $info['unapproved_count']; ?></td>
				</tr>
				<?php  endforeach; ?>
			</table>
		<?php  endif; ?>
		
	</div>
	
	<div class="ccm-pane-footer">

		<form method="post" action="<?php  echo $this->action('uninstall', $btInfo->btHandle); ?>" style="margin: 0;">
			<?php 
			echo $token;
			$uninstall_button_text = empty($usage) ? t('Uninstall') : t('Uninstall And Permanently Delete All Content');
			echo $ih->submit($uninstall_button_text, false, 'right', 'error');
			echo $ih->button(t('Cancel'), $this->action('view'), 'left');
			?>
		</form>
		
	</div>
		
<?php  echo $dh->getDashboardPaneFooterWrapper(); ?>