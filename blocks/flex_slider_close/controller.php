<?php  defined('C5_EXECUTE') or die("Access Denied.");

class FlexSliderCloseBlockController extends BlockController {
	
	protected $btName = 'Close Flex Slider';
	protected $btDescription = 'close a flex slider';
	protected $btTable = 'btDCFlexSliderClose';
	
	protected $btInterfaceWidth = "700";
	protected $btInterfaceHeight = "450";
	
	protected $btCacheBlockRecord = true;
	protected $btCacheBlockOutput = true;
	protected $btCacheBlockOutputOnPost = true;
	protected $btCacheBlockOutputForRegisteredUsers = false;
	protected $btCacheBlockOutputLifetime = CACHE_LIFETIME;
	
	public function getSearchableContent() {
		return $this->field_1_textbox_text;
	}








}
