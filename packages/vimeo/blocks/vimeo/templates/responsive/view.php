<?php       
defined('C5_EXECUTE') or die(_("Access Denied."));
$c = Page::getCurrentPage();
$playerID = uniqid();
?>
<div id="vimeo-area-<?php       echo $playerID;?>" class="vimeo-block">
	<?php      if (strlen($title)>0){?><h2><?php       echo $title; ?></h2><?php      } ?>
	<?php      if (strlen($previewText)>0){?><p><?php       echo $previewText; ?></p><?php      } ?>
<?php       if ($c->isEditMode()) { ?>
	<div class="ccm-edit-mode-disabled-item" style="width:<?php       echo $videoWidth?>px; height:<?php       echo $videoHeight?>px;">
		<div style="padding:8px 0px; padding-top: <?php       echo round($videoHeight/2)-10;?>px;"><?php       echo t('Content disabled in edit mode.')?></div>
	</div>
<?php        }else{ ?>
	<div id="vimeo-player-<?php       echo $playerID;?>" class="vimeo-embed-container">
		<?php      if ($haveVideoID){?>
		<iframe src="http://player.vimeo.com/video/<?php   echo $videoID;?>" 
			   frameborder="0" 
			   webkitAllowFullScreen 
			   mozallowfullscreen 
			   allowFullScreen>
		</iframe>
		<?php      } else { ?>
		<div class="ccm-edit-mode-disabled-item" style="width:<?php       echo $videoWidth?>px; height:<?php       echo $videoHeight?>px;">
			<div style="padding:8px 0px; padding-top: <?php       echo round($videoHeight/2)-10?>px;"><?php      echo t('Could not determine video ID, Vimeo URL was not parseable.');?></div>
		</div>
		<?php      } ?>
	</div>
<?php        } ?>
</div>