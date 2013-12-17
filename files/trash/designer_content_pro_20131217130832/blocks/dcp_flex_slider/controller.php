<?php defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * WARNING: This is generated code.
 * Anything in this file may get overwritten or deleted without warning.
 * Do NOT edit, rename, move, or delete this file (doing so will cause errors).
 *
 * To customize this controller, first copy it to SITEROOT/blocks/dcp_flex_slider/controller.php
 *
 * Visit http://theblockery.com/dcp for documentation.
 */

Loader::library('dcp_controller', 'designer_content_pro');

class DcpFlexSliderBlockController extends DcpController {

	protected $btHandle = 'dcp_flex_slider';
	protected $btName = 'Flex Slider';
	protected $btDescription = '';
	protected $btTable = 'btDcpFlexSlider';

	protected $btCacheBlockRecord = true;
	protected $btCacheBlockOutput = true;
	protected $btCacheBlockOutputOnPost = true;
	protected $btCacheBlockOutputForRegisteredUsers = false;
	protected $btCacheBlockOutputLifetime = CACHE_LIFETIME;

	public $btDCProGeneratorVersion = '1.0.1'; //Don't change this!

	public $btDCProRepeatingItemFields = array(
		'image' => array('type' => 'image', 'label' => 'Image', 'options' => array('required' => true, 'has_editable_text' => true)),
		'text' => array('type' => 'wysiwyg', 'label' => 'text', 'options' => array()),
		'link' => array('type' => 'link', 'label' => 'link', 'options' => array('control_type' => 'combo')),
	);
	
	public function save($args) {
		
		var_dump($args);
		die();
		parent::save($args);
	}


}
