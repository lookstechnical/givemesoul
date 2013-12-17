<?php defined('C5_EXECUTE') or die(_("Access Denied."));

/**
 * WARNING: This is generated code.
 * Anything in this file may get overwritten or deleted without warning.
 * Do NOT edit, rename, move, or delete this file (doing so will cause errors).
 */
?>
<div class="clearfix">
	<label>Name</label>
	<div class="input">
		<input type="text" placeholder="unique name" required value=""/>
	</div>
</div>
<div class="clearfix">
	<label>Loop?</label>
	<div class="input">
		<input type="checkbox"  value=""/>
	</div>
</div>
<div class="clearfix">
	<label>Animation Speed</label>
	<div class="input">
		<input type="text" placeholder="speed in ms" value=""/>
	</div>
</div>
<?php
Loader::element('block_edit', array('controller' => $controller), 'designer_content_pro');
