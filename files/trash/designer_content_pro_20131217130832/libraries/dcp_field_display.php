<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


abstract class DcpFieldDisplay {
	// abstract public function __construct(); //accept data for the object as instantiation params
	//DEV NOTE: As of PHP 5.4, constructor method signatures can no longer differ between abstract base classes and their subclasses.
	//          Since each of our subclass accepts different kinds of data (and a different number of variables),
	//          we can no longer put the constructor in the abstract base class.

	abstract public function getDisplay(); //returns the "most common" HTML for this data (e.g. if image field, an <img> tag, if link, an <a> tag... etc.)
	public function display() { //override this in child classes when getDisplay() has params (display() params should always match getDisplay() params so they can be called interchangeably)
		echo $this->getDisplay();
	}
}

class DcpFieldDisplay_File extends DcpFieldDisplay {
	protected $fID;
	protected $fileObj = null; //will be lazy-loaded
	protected $title_raw;
	protected $title_escaped;
	
	public function __construct($fID, $title) {
		$this->fID = $fID;
		if (empty($title)) {
			$file = $this->getFileObj();
			if (!is_null($file)) {
				$title = $this->getTitleForFileObj($file);
			}
		}
		$this->title_raw = $title;
		$this->title_escaped = is_null($title) ? '' : htmlentities($title, ENT_QUOTES, APP_CHARSET);
	}
	
	protected function getTitleForFileObj($file) { //so Image class can override this without having to override the whole constructor
		return is_null($file) ? null : $file->getTitle();
	}

	public function display($force_download = true) {
		echo $this->getDisplay($force_download);
	}
	public function getDisplay($force_download = true) {
		$output = '';
		
		if (!empty($this->fID)) {
			$output .= '<a href="' . $this->getHref($force_download) . '">';
			$output .= $this->getTitle();
			$output .= '</a>';		
		}
		
		return $output;
	}
	
	public function getFileObj() {
		if (empty($this->fID)) {
			return null;
		}
		
		if (is_null($this->fileObj)) {
			$this->fileObj = File::getByID($this->fID);
		}
		
		if (!is_null($this->fileObj)) {
			$fp = new Permissions($this->fileObj);
			if(!$fp->canViewFile()) {
				return null;
			}
		}
				
		return $this->fileObj;
	}
	
	public function getTitle($escaped = true) {
		return $escaped ? $this->title_escaped : $this->title_raw;
	}
	
	public function getHref($force_download = true) {
		if (empty($this->fID)) {
			return '';
		}
		
		$path = '/download_file' . ($force_download ? '' : '/view_inline');
		$cID = Page::getCurrentPage()->getCollectionID();
		return View::url($path, $this->fID, $cID); //the cID is used for site statistics (so you know which page the file was downloaded from -- see file properties in the dashboard file manager)
	}
}

class DcpFieldDisplay_Image extends DcpFieldDisplay_File {
	
	protected function getTitleForFileObj($file) {
		if (is_null($file)) {
			return null;
		} else {
			//If file has a description, use that for alt text.
			//Otherwise fallback to title.
			$description = $file->getDescription();
			$title = $file->getTitle();
			return empty($description) ? $title : $description;
		}
	}

	public function display($width = 0, $height = 0, $crop = false) {
		echo $this->getDisplay($width, $height, $crop);
	}
	public function getDisplay($width = 0, $height = 0, $crop = false) {
		$output = '';
		
		if (!empty($this->fID)) {
			$img = $this->getImageObj($width, $height, $crop);
		
			$output .= '<img';
			$output .= ' src="' . $img->src . '"';
			$output .= ' width="' . $img->width . '"';
			$output .= ' height="' . $img->height . '"';
			$output .= ' alt="' . $this->getAltText() . '"';
			$output .= ' />';
		}
		
		return $output;
	}
	
	public function getImageObj($width = 0, $height = 0, $crop = false) {
		$file = $this->getFileObj();
		if (is_null($file)) {
			return null;
		}
		
		$oWidth = $file->getAttribute('width');
		$oHeight = $file->getAttribute('height');
		
		$generate_thumbnail = true;
		if (empty($width) && empty($height)) {
			$generate_thumbnail = false;
		} else if ($width >= $oWidth && $height >= $oHeight) {
			$generate_thumbnail = false;
		}
		
		if ($generate_thumbnail) {
			$width = empty($width) ? 9999 : $width;
			$height = empty($height) ? 9999 : $height;
			$image = Loader::helper('image')->getThumbnail($file, $width, $height, $crop);
		} else {
			$image = new stdClass;
			$image->src = $file->getRelativePath();
			$image->width = $oWidth;
			$image->height = $oHeight;
		}
		
		return $image;
	}
	
	public function getAltText($escaped = true) {
		return $this->getTitle($escaped);
	}
	
}

class DcpFieldDisplay_Wysiwyg extends DcpFieldDisplay {
	protected $content;
	
	public function __construct($content) {
		$this->content = $content;
	}
	
	public function getDisplay() {
		return $this->getContent();
	}	
	
	public function getContent() {
		return $this->content;
	}
}

class DcpFieldDisplay_Textbox extends DcpFieldDisplay {
	protected $text_raw;
	protected $text_escaped;
	
	public function __construct($text) {
		$this->text_raw = $text;
		$this->text_escaped = htmlentities($text, ENT_QUOTES, APP_CHARSET);
	}
	
	public function getDisplay() {
		return $this->getText();
	}
	
	public function getText($escaped = true) {
		return $escaped ? $this->text_escaped : $this->text_raw;
	}
}

class DcpFieldDisplay_Textarea extends DcpFieldDisplay_Textbox {
	
	public function getText($escaped = true) {
		return $escaped ? nl2br($this->text_escaped) : $this->text_raw;
	}
}

class DcpFieldDisplay_Link extends DcpFieldDisplay {
	protected $cID;
	protected $pageObj = null; //will be lazy-loaded
	protected $url_raw;
	protected $url_normalized;
	protected $text_raw;
	protected $text_escaped;
	
	public function __construct($cID, $url, $text) {
		$this->cID = $cID;
		$this->url_raw = $url;
		$this->url_normalized = $this->normalizeUrl($url);
		
		if (empty($text)) {
			if (!empty($cID)) {
				$text = $this->getPageObj()->getCollectionName();
			} else if (!empty($url)) {
				$text = $this->getHref();
			} else {
				$text = null;
			}
		}
		
		$this->text_raw = $text;
		$this->text_escaped = is_null($text) ? '' : htmlentities($text, ENT_QUOTES, APP_CHARSET);
	}
	
	//pass true to always open link in new window,
	//pass false to never open link in new window,
	//pass null (or don't pass anything) to open external links in new window and internal page links in same window
	public function display($open_in_new_window = null) {
		echo $this->getDisplay($open_in_new_window = null);
	}

	public function getDisplay($open_in_new_window = null) {
		$output = '';
		
		$href = $this->getHref();
		if (!empty($href)) {
			$open_in_new_window = is_null($open_in_new_window) ? empty($this->cID) : $open_in_new_window;
			$target = $open_in_new_window ? ' target="_blank"' : '';
			
			echo '<a href="' . $href . '"' . $target . '>';
			echo $this->getText();
			echo '</a>';
		}
		
		return $output;
	}
	
	private function getPageObj() { //don't expose this publicly -- only ->href() should be used externally!
		if (empty($this->cID)) {
			return null;
		}
		
		if (is_null($this->pageObj)) {
			$this->pageObj = Page::getByID($this->cID);
		}
		
		//Watch out: Page::getByID() returns a page object even if cID doesn't exist!
		if (is_object($this->pageObj) && ($this->pageObj->getCollectionID() == 0)) {
			return null;
		}
		
		return $this->pageObj;
	}
	
	//Returns the full url to whichever link type is appropriate (either internal page or external url)
	public function getHref() {
		if (empty($this->cID)) {
			return $this->url_normalized;
		} else {
			return Loader::helper('navigation')->getLinkToCollection($this->getPageObj());
		}
	}
	
	public function getText($escaped = true) {
		return $escaped ? $this->text_escaped : $this->text_raw;
	}
	
	//For "combo" fields, this tells you whether the user provided a site page or a text url.
	//Returns TRUE if user chose a site page from the page chooser control.
	//Returns FALSE if user entered a text url (aka "external url").
	//Also returns FALSE if user left both fields blank.
	public function isPageLink() {
		return !empty($this->cID);
	}
	
	//internal helper function -- attempts to create a full and valid url from potentially incomplete data
	private function normalizeUrl($url) {
		if (empty($url)) {
			return '';
		} else if ($this->colonExistsBeforeDot($url)) {
			return $url; //probably already has "http://", "https://", "mailto:", "tel:", etc.
		} else if (strpos($url, '@') !== false) {
			return 'mailto:' . $url;
		} else if (strpos($url, '/') === 0) {
			return View::url($url); //site path (not an external url)
		} else {
			return 'http://' . $url;
		}
	}
	//internal helper function -- checks if a colon (:) exists in the string AND it appears before any dots (.)
	//The purpose of this is to guess whether or not a string contains a URI scheme (http://, mailto:, tel:)
	// (since colons can legitemately appear in the path of URL's, we can't just check for their existence).
	private function colonExistsBeforeDot($str) {
		$colonPos = strpos($str, ':');
		$dotPos = strpos($str, '.');
		if ($colonPos === false) {
			return false;
		} else if ($dotPos === false) {
			return true;
		} else {
			return $colonPos < $dotPos;
		}
	}
}

class DcpFieldDisplay_Page extends DcpFieldDisplay {
	protected $cID;
	protected $pageObj = null; //will be lazy-loaded
	
	public function __construct($cID) {
		$this->cID = $cID;
	}
	
	public function getDisplay() {
		$output = '';
		
		$page = $this->getPageObj();
		if (!is_null($page)) {
			$href = Loader::helper('navigation')->getLinkToCollection($page);
			$text = $page->getCollectionName();
			$output = '<a href="' . $href . '"><?php  echo $text; ?></a>';
		}
		
		return $output;
	}
	
	public function getPageObj() {
		if (empty($this->cID)) {
			return null;
		}
		
		if (is_null($this->pageObj)) {
			$this->pageObj = Page::getByID($this->cID);
		}
		
		//Watch out: Page::getByID() returns a page object even if cID doesn't exist!
		if (is_object($this->pageObj) && ($this->pageObj->getCollectionID() == 0)) {
			return null;
		}
		
		return $this->pageObj;
	}
	
}
