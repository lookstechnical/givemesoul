<?php  defined('C5_EXECUTE') or die("Access Denied.");
?>

<style type="text/css" media="screen">
	.ccm-block-field-group h2 { margin-bottom: 5px; }
	.ccm-block-field-group td { vertical-align: middle; }
</style>

<div class="ccm-block-field-group">
	<h2>title</h2>
	<?php  echo $form->text('field_1_textbox_text', $field_1_textbox_text, array('style' => 'width: 95%;')); ?>
</div>

<div class="ccm-block-field-group">
	<h2>speed</h2>
	<?php  echo $form->text('field_2_textbox_text', $field_2_textbox_text, array('style' => 'width: 95%;')); ?>
</div>

<div class="ccm-block-field-group">
	<h2>loop</h2>
	<?php 
	$options = array(
		'1' => 'Yes',
		'2' => 'No',
	);
	echo $form->select('field_3_select_value', $options, $field_3_select_value);
	?>
</div>

<div class="ccm-block-field-group">
	<h2>direction</h2>
	<?php 
	$options = array(
		'1' => 'Horizontal',
		'2' => 'Vertical',
	);
	echo $form->select('field_4_select_value', $options, $field_4_select_value);
	?>
</div>

<div class="ccm-block-field-group">
	<h2>Controls</h2>
	<?php 
	$options = array(
		'1' => 'No',
		'2' => 'Yes',
	);
	echo $form->select('field_5_select_value', $options, $field_5_select_value);
	?>
</div>

<div class="ccm-block-field-group">
	<h2>Nav</h2>
	<?php 
	$options = array(
		'1' => 'No',
		'2' => 'Yes',
	);
	echo $form->select('field_6_select_value', $options, $field_6_select_value);
	?>
</div>


