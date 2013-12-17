<?php     defined('C5_EXECUTE') or die(_("Access Denied.")); ?> 
<div id="awkwardgalleryBlock-fsRow" class="awkwardgalleryBlock-fsRow" >
	<div class="backgroundRow" style="padding-left: 100px">
		<strong>File Set:</strong> <span class="ez-file-set-pick-cb"><?php    echo $form->select('fsID', $fsInfo['fileSets'], $fsInfo['fsID'])?></span>
	</div>
</div>
