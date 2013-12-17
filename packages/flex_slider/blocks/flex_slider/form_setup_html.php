<?php    
defined('C5_EXECUTE') or die(_("Access Denied."));
$al = Loader::helper('concrete/asset_library');
$ah = Loader::helper('concrete/interface');
$fm = Loader::helper('form'); 
$fmc = Loader::helper('form/color'); 
Loader::element('editor_init');
Loader::element('editor_config', array('editor_mode' => 'SIMPLE')); 
?>
<style>
#flexgalleryBlock-imgRows a{cursor:pointer}
#flexgalleryBlock-imgRows .flexgalleryBlock-imgRow,
#flexgalleryBlock-fsRow {margin-bottom:16px;clear:both;padding:7px;background-color:#eee}
#flexgalleryBlock-imgRows .flexgalleryBlock-imgRow a.moveUpLink{ display:block; background:url(<?php    echo DIR_REL?>/concrete/images/icons/arrow_up.png) no-repeat center; height:10px; width:16px; }
#flexgalleryBlock-imgRows .flexgalleryBlock-imgRow a.moveDownLink{ display:block; background:url(<?php    echo DIR_REL?>/concrete/images/icons/arrow_down.png) no-repeat center; height:10px; width:16px; }
#flexgalleryBlock-imgRows .flexgalleryBlock-imgRow a.moveUpLink:hover{background:url(<?php    echo DIR_REL?>/concrete/images/icons/arrow_up_black.png) no-repeat center;}
#flexgalleryBlock-imgRows .flexgalleryBlock-imgRow a.moveDownLink:hover{background:url(<?php    echo DIR_REL?>/concrete/images/icons/arrow_down_black.png) no-repeat center;}
#flexgalleryBlock-imgRows .cm-GalleryBlock-imgRowIcons{ float:right; width:35px; text-align:left; }
</style>

<div id="newImg">
	<?php    echo t('Type')?>
	<select name="type" style="vertical-align: middle">
		<option value="CUSTOM"<?php     if ($type == 'CUSTOM') { ?> selected<?php     } ?>><?php    echo t('Custom Gallery')?></option>
		<option value="FILESET"<?php     if ($type == 'FILESET') { ?> selected<?php     } ?>><?php    echo t('Pictures from File Set')?></option>
	</select>
	<br/><br/>

    <div class="row">
      <div class="span6" style="float: left;">
      	<div class="control-group">
      	 <label class="control-label"><?php echo t('Title')?></label>
           <input type="text" name="title" value="<?php echo $title ?>" > 
      	</div>  
      	<div class="control-group">
          <label class="checkbox">
            <input type="checkbox" name="showTitle" value="true" <?php  if($showTitle == '1'){echo 'checked';}?>> <?php echo t('Show Title')?>
          </label>
      	</div>
      	<div class="control-group">
          <label class="control-label"><?php echo t('Link')?></label>
            <input type="text" name="link" value="<?php echo $link ?>" > 
          
      	</div>
      <!--	<div class="control-group">
    	  <label class="control-label"><?php echo t('Namespace')?></label>
            <input type="text" name="namespace" value="<?php echo $namespace ?>" > <?php echo t('Namespace')?>
      	</div>
      	<div class="control-group">
          <label class="control-label"><?php echo t('Selector')?></label>
            <input type="text" name="selector" value="<?php echo $selector ?>" > 
      	</div>-->
      	<div class="control-group">
          <label class="control-label"> <?php echo t('Animation')?></label>
            <select name="animation">
                <option value="slide" <?php if($animation == 'slide') echo 'selected' ?>>Slide</option>
                <option value="fade" <?php if($animation == 'fade') echo 'selected' ?>>Fade</option>
            </select>
      	</div>
      
      	<div class="control-group">
          <label class="control-label"><?php echo t('Direction')?> </label>
            <select name="direction">
                <option value="" >-</option>
                <option value="horizontal" <?php if($direction == 'horizontal') echo 'selected' ?>>Slide</option>
                <option value="vertical" <?php if($direction == 'vertical') echo 'selected' ?>>Vertical</option>
            </select>
      	</div>
      	<div class="control-group">
          <label class="checkbox">
            <input type="checkbox" name="reverse" value="true" <?php  if($reverse == '1'){echo 'checked';}?>> <?php echo t('Reverse')?>
          </label>
      	</div>
      	<div class="control-group">
          <label class="checkbox">
            <input type="checkbox" name="animationLoop" value="true" <?php  if($animationLoop == '1'){echo 'checked';}?>> <?php echo t('Loop')?>
          </label>
      	</div>
      	<div class="control-group">
          <label class="checkbox">
            <input type="text" name="animationSpeed" value="<?php echo $animationSpeed ?>" > <?php echo t('Speed ms')?>
          </label>
      	</div>
      	<div class="control-group">
          <label class="checkbox">
            <input type="checkbox" name="controlNav" value="true" <?php  if($controlNav == '1'){echo 'checked';}?>> <?php echo t('Display Control nav')?>
          </label>
      	</div>
      	<div class="control-group">
          <label class="checkbox">
            <input type="checkbox" name="directionNav" value="true" <?php  if($directionNav == '1'){echo 'checked';}?>> <?php echo t('Display Direction Nav')?>
          </label>
      	</div>
      	<div class="control-group">
          <label class="control-label"><?php echo t('Class')?></label>
            <input type="text" name="class" value="<?php echo $class ?>" >
        </div>
      </div>
      

    <br/><br/>
	<table cellspacing="0" cellpadding="0" border="0" width="370px">
		<tr>
		<td>
		<?php   echo $form->label('maxWidth', 'Max image width');?>
		<?php   $mh = $maxHeight!= null ? $maxHeight : "600";
			$mw = $maxWidth != null ? $maxWidth : "800"; ?>
<?php   echo $form->text('maxWidth', $mw, array('style' => 'width: 30px'));?>
		</td>
		<td>
				<?php   echo $form->label('maxHeight','Max image height');?>
<?php   echo $form->text('maxHeight', $mh, array('style' => 'width: 30px'));?>
		</td>
	</tr>
	</table>
	<table>
	<tr style="padding-top: 2px">
	<td colspan="2">
	<span id="flexgalleryBlock-chooseImg"><?php    echo $ah->button_js(t('Add Image'), 'flexgalleryBlock.chooseImg()', 'left');?></span>
	</td>
	</tr>
	</table>
</div>
<h3><?php echo t('Selected Images')?></h3>
<div id="flexgalleryBlock-imgRows">
<?php     if ($fsID <= 0) {
	foreach($images as $imgInfo){ 
		$f = File::getByID($imgInfo['fID']);
		$fp = new Permissions($f);
		$imgInfo['thumbPath'] = $f->getThumbnailSRC(1);
		$imgInfo['fileName'] = $f->getTitle();
		$imgInfo['description'] = $f->getDescription();
		if ($fp->canRead()) { 
			$this->inc('image_row_include.php', array('imgInfo' => $imgInfo));
		}
	}
} ?>
</div>

<?php    
Loader::model('file_set');
$s1 = FileSet::getMySets();
$sets = array();
foreach ($s1 as $s){
    $sets[$s->fsID] = $s->fsName;
}
$fsInfo['fileSets'] = $sets;

if ($fsID > 0) {
	$fsInfo['fsID'] = $fsID;
} else {
	$fsInfo['fsID']='0';
}
$this->inc('fileset_row_include.php', array('fsInfo' => $fsInfo)); ?> 

<div id="imgRowTemplateWrap" style="display:none">
<?php    
$imgInfo['GalleryImgId']='tempGalleryImgId';
$imgInfo['fID']='tempFID';
$imgInfo['fileName']='tempFilename';
$imgInfo['origfileName']='tempOrigFilename';
$imgInfo['thumbPath']='tempThumbPath';
$imgInfo['imgHeight']='tempHeight';
$imgInfo['imgWidth']='tempWidth';
$imgInfo['class']='flexgalleryBlock-imgRow';
?>
<?php     $this->inc('image_row_include.php', array('imgInfo' => $imgInfo)); ?> 
</div>


