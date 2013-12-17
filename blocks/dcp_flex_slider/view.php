<?php defined('C5_EXECUTE') or die(_("Access Denied.")); ?>
<script src="/js/jquery.flexslider.js"></script>
<?php
$title = "my";
?>
<script type="text/javascript">
jQuery(window).load(function(){
      jQuery('.my-flexslider').flexslider({
      	namespace: "my",
      	animation: "slide",
      	animationLoop: true,
        animationSpeed: "1",
        controlNav: false,
        directionNav: false, 
      });
    });

</script>

<div class="slider-container <?php echo $class ?>">

<div class="slider <?php echo $title ?>-slider <?php echo $title ?> " >
    <div class="<?php echo $title ?>-flexslider flexslider" >
      <ul class="slides <?php echo $title ?>-slides">

<?php foreach ($controller->getRepeatingItems() as $item): ?>

	  <li>
	  	<?php $item->image->display(); ?>
	  	<div class="text">
		  	<?php $item->text->display(); ?>
	  	</div>	
	  </li>

<?php endforeach; ?>
	 </ul>
    </div>
</div>

</div>