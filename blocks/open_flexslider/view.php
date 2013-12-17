<?php  defined('C5_EXECUTE') or die("Access Denied.");
global $c;
?>

<?php $animationType = 'slide'; ?>

<?php  if (!empty($field_1_textbox_text)): ?>
	<?php $title = $field_1_textbox_text;?>
<?php  endif; ?>

<?php  if (!empty($field_2_textbox_text)): ?>
	<?php  $animationSpeed = $field_2_textbox_text ?>
	
<?php  endif; ?>

<?php  if ($field_3_select_value == 1): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "loop" : CHOICE "Yes" -->
	<?php $loop = true;?>
<?php  endif; ?>

<?php  if ($field_3_select_value == 2): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "loop" : CHOICE "No" -->
	<?php $loop = false;?>
<?php  endif; ?>

<?php  if ($field_4_select_value == 1): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "direction" : CHOICE "Horizontal" -->
	<?php $direction = 'horizontal';?>
<?php  endif; ?>

<?php  if ($field_4_select_value == 2): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "direction" : CHOICE "Vertical" -->
	<?php $direction = 'vertical';?>
<?php  endif; ?>

<?php  if ($field_5_select_value == 1): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "Controls" : CHOICE "No" -->
	<?php $direction = 'horizontal';?>
<?php  endif; ?>

<?php  if ($field_5_select_value == 2): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "Controls" : CHOICE "Yes" -->
<?php  endif; ?>

<?php  if ($field_6_select_value == 1): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "Nav" : CHOICE "No" -->
	<?php $controlNav = "true"; ?>
<?php  endif; ?>

<?php  if ($field_6_select_value == 2): ?>
	<!-- ENTER MARKUP HERE FOR FIELD "Nav" : CHOICE "Yes" -->
	<?php $controlNav = "false"; ?>
<?php  endif; ?>

<?php if(!$c->isEditMode()): ?>
<link rel="stylesheet" media="screen" type="text/css" href="/css/flexslider.css" />
<script type="text/javascript" src="/js/jquery.flexslider.js"></script>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.<?php echo $title ?>-flexslider').flexslider({
        animation:  "<?php echo $animationType; ?>",
        animationSpeed:  "<?php echo $animationSpeed; ?>",
        animationLoop: <?php echo $loop; ?>,
        direction: "<?php echo $direction; ?>",
        controlNav: false,
        controla: false,
      });
	});
</script>




<div class="slider-container <?php echo $class ?>">

<div class="slider <?php echo $title ?>-slider <?php echo $title ?> " >
    <div class="<?php echo $title ?>-flexslider flexslider" >
      <ul class="slides <?php echo $title ?>-slides">
<?php endif; ?>