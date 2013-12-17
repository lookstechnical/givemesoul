<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


class DesignerContentProPackage extends Package {

	protected $pkgHandle = 'designer_content_pro';
	public function getPackageName() { return t('Designer Content Pro'); }
	public function getPackageDescription() { return t('Quickly create custom blocktypes with repeating items.'); }
	protected $appVersionRequired = '5.6.0';
	protected $pkgVersion = '1.0.1';
	
	public function install() {
		$pkg = parent::install();
		$this->installOrUpgrade($pkg);
	}
	
	public function upgrade() {
		$this->installOrUpgrade($this);
		parent::upgrade();
	}
	
	private function installOrUpgrade($pkg) {
		$c = $this->getOrAddSinglePage($pkg, '/dashboard/blocks/designer_content_pro', t('Designer Content Pro'));
		$this->setPageDashboardIcon($c, 'icon-plus-sign');
	}
	
	
/*** Utility Functions ***/
	private function getOrAddSinglePage($pkg, $cPath, $cName = '', $cDescription = '') {
		Loader::model('single_page');

		$sp = SinglePage::add($cPath, $pkg);

		if (is_null($sp)) {
			//SinglePage::add() returns null if page already exists
			$sp = Page::getByPath($cPath);
		} else {
			//Set page title and/or description...
			$data = array();
			if (!empty($cName)) {
				$data['cName'] = $cName;
			}
			if (!empty($cDescription)) {
				$data['cDescription'] = $cDescription;
			}

			if (!empty($data)) {
				$sp->update($data);
			}
		}

		return $sp;
	}
	
	public function setPageDashboardIcon($c, $icon_glyph_name) {
		$cak = CollectionAttributeKey::getByHandle('icon_dashboard');
		if (is_object($cak)) {
			$c->setAttribute('icon_dashboard', $icon_glyph_name);
		}
	}
}