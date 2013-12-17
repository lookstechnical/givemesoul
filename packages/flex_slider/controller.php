<?php  
defined('C5_EXECUTE') or die(_("Access Denied."));

class FlexSliderPackage extends Package {

	protected $pkgHandle = 'flex_slider';
	protected $appVersionRequired = '5.3.0';
	protected $pkgVersion = '1.1.0';
	
	public function getPackageDescription() {
		return t("Flex Slider integration");
	}
	
	public function getPackageName() {
		return t("Flex Slider 1.1.0");
	}
	
	public function install() {
		$pkg = parent::install();
		
		//install blocks
	  	BlockType::installBlockTypeFromPackage('flex_slider', $pkg);	
	  	
	 }
}
?>