<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


$hh = Loader::helper('html');
?>

<script type="text/javascript">
	ccm_addHeaderItem('<?php  echo $hh->javascript('jquery.tmpl.min.js', 'designer_content_pro')->href; ?>', 'JAVASCRIPT');
	ccm_addHeaderItem('<?php  echo $hh->javascript('jquery.validate.min.js', 'designer_content_pro')->href; ?>', 'JAVASCRIPT');
	ccm_addHeaderItem('<?php  echo $hh->javascript('block_edit.js', 'designer_content_pro')->href; ?>', 'JAVASCRIPT');
	ccm_addHeaderItem('<?php  echo $hh->css('block_edit.css', 'designer_content_pro')->href; ?>', 'CSS');
</script>

<div id="repeating-items-sortable-container">
<ul id="repeating-items">
	<script id="repeating-item-template" type="text/x-jquery-tmpl">
	<li class="repeating-item" data-repeating-item-id="${repeatingItemId}">
		<div class="repeating-item-thumb repeating-item-edit-open-dialog"></div>
		<div class="repeating-item-label {{if !hasThumb}}repeating-item-label-full-width{{/if}} repeating-item-edit-open-dialog"></div>
		
		<div class="repeating-item-action repeating-item-action-delete"><i class="icon-trash"></i></div><?php  /* MUST be direct child of <li>, otherwise JQUI sortable doesn't work! */ ?>
		<div class="repeating-item-action repeating-item-action-move"><i class="icon-resize-vertical"></i></div>
		
		<?php  $controller->outputAllRepeatingItemJQTmplHiddenFields(); ?>
	</li>
	</script>
</ul>
</div>

<div id="repeating-item-action-add">
	<?php  echo t('Add New Item...'); ?>
</div>

<script type="text/javascript">
<?php  //Populate data (applies only to edit, not add)
$jh = Loader::helper('json');
$repeating_items = $controller->getRepeatingItems('edit');
foreach ($repeating_items as $repeating_item) {
	$data = $jh->encode($repeating_item);
	
	$label = $controller->getRepeatingItemLabelValue($repeating_item);
	$label = substr($label, 0, 75); //redundant (because it gets truncated by js prior to output), but an "optimization" so we don't have to send a ton of text over the wire in case it's from a textarea or wysiwyg field
	$label = addslashes($label);
	$label = str_replace(array("\r", "\n"), ' ', $label); //newlines cause js errors (this could happen if a textarea field is the item label)
	
	$thumbSrc = $controller->getRepeatingItemThumbSrc($repeating_item);
	
	echo "addRepeatingItem({$data}, '{$label}', '{$thumbSrc}', false);\n";
}
?>
</script>