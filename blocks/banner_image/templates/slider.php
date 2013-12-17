<?php  defined('C5_EXECUTE') or die("Access Denied.");?>

<li class="banner_image">
	<?php  if (!empty($field_1_image)): ?>
	<?php  if (!empty($field_1_image_externalLinkURL)) { ?><a href="<?php  echo $this->controller->valid_url($field_1_image_externalLinkURL); ?>"><?php  } ?><img src="<?php  echo $field_1_image->src; ?>"  alt="<?php  echo $field_1_image_altText; ?>" /><?php  if (!empty($field_1_image_externalLinkURL)) { ?></a><?php  } ?>
<?php  endif; ?>

	<div class="banner_text">
		<?php  if (!empty($field_2_wysiwyg_content)): ?>
	<?php  echo $field_2_wysiwyg_content; ?>
<?php  endif; ?>
	</div>
</li>