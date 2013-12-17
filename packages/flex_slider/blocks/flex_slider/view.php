<script type="text/javascript">
jQuery(window).load(function(){
      jQuery('.<?php echo $title ?>-flexslider').flexslider({
        animation:  "<?php echo $animation; ?>",
        animationLoop: <?php echo $animationLoop; ?>,
        direction: "<?php echo $direction; ?>",
        reverse: <?php echo $reverse; ?>,
        controlNav: <?php echo $controlNav; ?>,
      });
    });

</script>
<?php //var_dump($images);
//die();
    
?>

<div class="slider-container <?php echo $class ?>">

<div class="slider <?php echo $title ?>-slider <?php echo $title ?> " >
    <div class="<?php echo $title ?>-flexslider flexslider" >
      <ul class="slides <?php echo $title ?>-slides">
     
      <?php foreach($images as $imgInfo):?> 
			<?php
			$f = File::getByID($imgInfo['fID']);
			$imgHelper = Loader::helper('image'); 
			$imgLink = $imgInfo['imgLink'];
			$path = $f->getRelativePath();
			$imageThumb = $imgHelper->getThumbnail($f, 180,160)->src; 
			$description = $f->getDescription();
			?>
        <li><?php if($imgLink):?><a href="<?php echo $imgLink ?>"><?php endif;?><img src="<?php echo $path?>" width="<?php echo $maxWidth?>" alt="<?php echo $description ?>" title="<?php echo $f->getTitle(); ?>"/><?php if($imgLink):?></a><?php endif;?></li>
        <?php endforeach; ?>
      </ul>
    </div>
</div>

<?php if(true == $showTitle):?>
        <h2 class="slider-title"><a href="<?php echo $link; ?>"><?php echo $title ?></a></h2>
<?php endif; ?>
</div>
