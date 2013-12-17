<?php     
defined('C5_EXECUTE') or die(_("Access Denied.")); 

?> 
<div id="flexgalleryBlock-imgRow<?php    echo $imgInfo['GalleryImgId']?>" class="flexgalleryBlock-imgRow" >
	<div class="backgroundRow" style="background: url(<?php    echo $imgInfo['thumbPath']?>) no-repeat left top; padding-left: 100px">
		<div class="cm-GalleryBlock-imgRowIcons" >
			<div style="float:right">
				<a onclick="flexgalleryBlock.moveUp('<?php    echo $imgInfo['GalleryImgId']?>')" class="moveUpLink"></a>
				<a onclick="flexgalleryBlock.moveDown('<?php    echo $imgInfo['GalleryImgId']?>')" class="moveDownLink"></a>									  
			</div>
			<div style="margin-top:4px"><a onclick="flexgalleryBlock.removeImage('<?php    echo $imgInfo['GalleryImgId']?>')"><img src="<?php    echo ASSETS_URL_IMAGES?>/icons/delete_small.png" /></a></div>
		</div>
		<strong><?php    echo $imgInfo['fileName']?></strong>
		<br/><br/><br/>
		<div style="margin-top:4px">
		<input type="hidden" name="imgFIDs[]" value="<?php    echo $imgInfo['fID']?>">
		<input type="hidden" name="imgHeight[]" value="<?php    echo $imgInfo['imgHeight']?>">
		<input type="hidden" name="imgWidth[]" value="<?php   echo $imgInfo['imgWidth']?>">
		<label for="imgLink[]">Url</label><br/><br/>
		<input type="text" name="imgLink[]"  value="<?php  echo $imgInfo['imgLink']?>"><br/><br/>
		<?php echo $form->textarea('imgText[]', $imgInfo['imgText'], array('class' => 'ccm-advanced-editor', 'style' => 'width: 120px'));?>
				</div>
	</div>
</div>