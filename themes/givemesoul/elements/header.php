<?php  defined('C5_EXECUTE') or die("Access Denied.");
global $u;
 ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="<?php echo LANGUAGE?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php  Loader::element('header_required'); ?>

<meta name="viewport" content="width=device-width, initial-scale=1" />
<script type="text/javascript" src="//use.typekit.net/kcy3vvf.js"></script>
<script type="text/javascript">try{Typekit.load();}catch(e){}</script>
<!-- Site Header Content //-->
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $this->getThemePath();?>/stylesheets/screen.css" />
<link rel="stylesheet" media="screen" type="text/css" href="<?php echo $this->getThemePath(); ?>/stylesheets/typography.css" />

				<script type="text/javascript" src="<?php echo $this->getThemePath();?>/js/fluidvids.js"></script>


</head>
<body>

<div class="container">
<header <?php  if ($u->isLoggedIn()) {?> class="editable" <?php } ?>>
	<div class="centered">
		<section class="icon">
			<?php 
				$a = new GlobalArea('Icon');
				$a->display($c);
			?>
		</section>
		<section class="logo">
			<?php 
				$a = new GlobalArea('Logo');
				$a->display($c);
			?>
		</section>
		<section class="tagline">
			<?php 
				$a = new GlobalArea('Tagline');
				$a->display($c);
			?>
		</section>
		<div class="clear"></div>
	</div>
</header>
<section class="banner">
	<?php 
		$a = new Area('Banner');
		$a->display($c);
	?>
</section>
