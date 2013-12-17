<?php 
defined('C5_EXECUTE') or die("Access Denied.");
$this->inc('elements/header.php'); 
global $u;?>



<section class="main-content <?php  if ($c->isEditMode()) {?> editable <?php } ?>   <?php  if (!$u->isLoggedIn()) {?> overlaycollumn <?php } ?> " >
	<div class="full-width">
		<?php 
			$a = new Area('Full-width');
			$a->display($c);
		?>
	</div>
	<div class="twocolumns">
		<section>
			<?php 
				$a = new Area('col1');
				$a->display($c);
			?>
		</section>
		<section>
			<?php 
				$a = new Area('col2');
				$a->display($c);
			?>
		</section>
	</div>
	<div class="threecolumns">
		<section>
			<?php 
				$a = new Area('col3');
				$a->display($c);
			?>
		</section>
		<section>
			<?php 
				$a = new Area('col4');
				$a->display($c);
			?>
		</section>
		<section>
			<?php 
				$a = new Area('col5');
				$a->display($c);
			?>
		</section>
	</div>
	<div class="fourcolumns">
		<section>
			<?php 
				$a = new Area('col6');
				$a->display($c);
			?>
		</section>
		<section>
			<?php 
				$a = new Area('col7');
				$a->display($c);
			?>
		</section>
		<section>
			<?php 
				$a = new Area('col8');
				$a->display($c);
			?>
		</section>
		<section>
			<?php 
				$a = new Area('col9');
				$a->display($c);
			?>
		</section>
	</div>
	<div class="clear"></div>
</section>



<?php $this->inc('elements/footer.php'); ?>