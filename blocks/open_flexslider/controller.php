<?php  defined('C5_EXECUTE') or die("Access Denied.");

class OpenFlexsliderBlockController extends BlockController {
	
	protected $btName = 'Open FlexSlider';
	protected $btDescription = 'use this before a collection of banner images to turn them into a slider. You will also need to add a flex slider close block after your image collection';
	protected $btTable = 'btDCOpenFlexslider';
	
	protected $btInterfaceWidth = "700";
	protected $btInterfaceHeight = "450";
	
	protected $btCacheBlockRecord = true;
	protected $btCacheBlockOutput = true;
	protected $btCacheBlockOutputOnPost = true;
	protected $btCacheBlockOutputForRegisteredUsers = false;
	protected $btCacheBlockOutputLifetime = CACHE_LIFETIME;
	
	public function getSearchableContent() {
		$content = array();
		$content[] = $this->field_1_textbox_text;
		$content[] = $this->field_2_textbox_text;
		return implode(' - ', $content);
	}








}
