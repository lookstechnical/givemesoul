<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


$dh = Loader::helper('concrete/dashboard');
$ih = Loader::helper('concrete/interface');
?>

<?php  echo $dh->getDashboardPaneHeaderWrapper(t('Designer Content Pro'), t('Quickly and easily create custom block types for repeating items.'), 'span12'); ?>
	
	<?php  if (empty($bts)): ?>
		
		<div class="alert alert-success">
			<p style="font-size: 18px; margin-bottom: 20px;"><?php  echo t('Welcome to Designer Content Pro!'); ?></p>
			
			<p style="font-size: 18px; margin-bottom: 20px;"><?php  echo t('Visit %s for documentation.', '<a href="http://theblockery.com/dcp" target="_blank">http://theblockery.com/dcp</a>'); ?></p>
			
			<p style="font-size: 18px;"><?php  echo t('Or click the "Create New Block Type" button below to get started.'); ?></p>
		</div>
		
	<?php  else: ?>
		
		<table class="table table-striped">
			<thead>
				<tr>
					<th><?php  echo t('Name'); ?></th>
					<th><?php  echo t('Handle'); ?></th>
					<th><?php  echo t('Status'); ?></th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php  foreach ($bts as $btInfo): ?>
				<tr>
					<td><?php  echo htmlentities($btInfo->btName, ENT_QUOTES, APP_CHARSET); ?></td>
					<td><?php  echo $btInfo->btHandle; ?></td>
					<td>
						<?php 
						if ($btInfo->installed) {
							$status_class = 'label label-success';
							$status_text = t('Installed');
						} else {
							$status_class = 'label';
							$status_text = t('Not Installed');
						}
						?>
						<span class="<?php  echo $status_class; ?>"><?php  echo $status_text; ?></span>
						
						<?php  if ($btInfo->hasCustomController): ?>
						<br><span class="label label-warning" style="line-height: 20px;"><?php  echo t('Controller Override'); ?></span>
						<?php  endif; ?>
						
						<?php  if (!$btInfo->hasCustomViewTemplate): ?>
						<br><span class="label label-important" style="line-height: 20px;"><?php  echo t('Missing View Override'); ?></span>
						<?php  endif; ?>
						
						<?php  if ($btInfo->hasCustomAddTemplate): ?>
						<br><span class="label label-important" style="line-height: 20px;"><?php  echo t('add.php overridden!'); ?></span>
						<?php  endif; ?>
						
						<?php  if ($btInfo->hasCustomEditTemplate): ?>
						<br><span class="label label-important" style="line-height: 20px;"><?php  echo t('edit.php overridden!'); ?></span>
						<?php  endif; ?>
					</td>
					<td style="white-space: nowrap;">
						
						<!-- CODE BUTTON -->
						<a class="btn dcp-sample-code" href="<?php  echo $this->action('ajax_show_sample_code', $btInfo->btHandle); ?>"><i class="icon-list-alt"></i> <?php  echo t('Sample Code'); ?></a>
						
						<!-- EDIT BUTTON -->
						<?php  $btn_class = $btInfo->hasCustomController ? 'btn disabled' : 'btn'; ?>
					  	<a class="<?php  echo $btn_class; ?>" href="<?php  echo $this->action('edit', $btInfo->btHandle); ?>"><i class="icon-pencil"></i> <?php  echo t('Edit'); ?></a>
						
						<!-- INSTALL/UNINSTALL BUTTON -->
						<?php 
						$action = $btInfo->installed ? 'uninstall' : 'install';
						$url = $this->action($action, $btInfo->btHandle);
						$icon_class = $btInfo->installed ? 'icon-remove' : 'icon-download-alt';
						$btn_text = $btInfo->installed ? t('Uninstall') : t('Install&nbsp;&nbsp;&nbsp;&nbsp;');
						$btn_color_class = $btInfo->installed ? 'btn-danger' : 'btn-info';
						?>
						<a class="btn" href="<?php  echo $url; ?>"><i class="<?php  echo $icon_class; ?>"></i> <?php  echo $btn_text; ?></a>
						
						<!-- DELETE BUTTON -->
						<?php  if (!$btInfo->installed): ?>
						<a class="btn" href="<?php  echo $this->action('delete', $btInfo->btHandle); ?>"><i class="icon-trash"></i> <?php  echo t('Delete'); ?></a>
						<?php  endif; ?>
						
					</td>
				</tr>
				<?php  endforeach; ?>
			</tbody>
		</table>
		
	<?php  endif; ?>
	
	<hr>
	<p><?php  echo $ih->button(t('Create New Block Type'), $this->action('add'), 'right', 'primary'); ?></p>
	<div style="clear:both;"></div>
	
<?php  echo $dh->getDashboardPaneFooterWrapper(); ?>

<script type="text/javascript">
$(document).ready(function() {
	$('.dcp-sample-code').on('click', function() {
		$('<div>').load($(this).attr('href')).dialog({
			'close': function() { $(this).remove(); },
			'modal': true,
			'closeOnEscape': true,
			'title': '<?php  echo t('Sample Code'); ?>',
			'width': '600',
			'height': '400'
		});
		return false;
	});
});
</script>