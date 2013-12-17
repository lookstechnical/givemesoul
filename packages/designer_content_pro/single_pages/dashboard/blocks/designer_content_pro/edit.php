<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

/***************************************************************************
 * Copyright (C) Web Concentrate - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited.
 * Written by Jordan Lev <jordan@webconcentrate.com>, January-August 2013
 ***************************************************************************/


$dh = Loader::helper('concrete/dashboard');
$ih = Loader::helper('concrete/interface');
$fh = Loader::helper('form');

$heading = $is_new ? t('Create New Block Type') : t('Edit Block Type');
$help_text = t('Hover over fields to see brief explanations,<br>or visit %s for complete documentation.', '<a href="http://theblockery.com/dcp" target="_blank">http://theblockery.com/dcp</a>');
?>


<script>
var action_validate = '<?php  echo $this->action('ajax_validate'); ?>';
var action_confirm = '<?php  echo $this->action('ajax_confirm'); ?>';
var action_save = '<?php  echo $this->action('ajax_save'); ?>';
var url_after_save = '<?php  echo $is_installed ? $this->action('refresh', $btHandle) : $this->action('view'); ?>';

var initialRepeatingItemFields = <?php  echo Loader::helper('json')->encode($repeating_item_fields); ?>;
$(document).ready(function() {
	initializeRepeatingItemFields(initialRepeatingItemFields);
	redrawRepeatingItemFields();
	<?php  if ($is_new): /* set default prefix for new blocktypes -- must do this AFTER initializing the data model, otherwise it gets confused by the non-null btHandle and thinks it's an edit (not an add) */ ?>
	$('#dcp-dashboard-form #btHandle').val('dcp_');
	<?php  endif; ?>
});
</script>

<?php  echo $dh->getDashboardPaneHeaderWrapper($heading, $help_text, 'span10 offset1', false); ?>

	<form method="post" class="form-horizontal" id="dcp-dashboard-form">
		<?php  echo $token; ?>
		
		<div class="ccm-pane-body">
			
			<?php  if (!$can_write_site_blocks): ?>
				<div class="alert alert-error">
					<?php  echo t("Warning: Your site's top-level /blocks/ directory is not writeable. Blocks types cannot be created or edited until permissions are changed on your server."); ?>
				</div>
			<?php  endif; ?>
	
			<?php  if (!$can_write_pkg_blocks): ?>
				<div class="alert alert-error">
					<?php  echo t("Warning: The package blocks directory ('%s') is not writeable. Blocks types cannot be created or edited until permissions are changed on your server.", DcpBlockTypeModel::getPkgBlocksDir()); ?>
				</div>
			<?php  endif; ?>
			
			<div id="dcp-validation-errors" style="display: none;" class="alert alert-error">
				<?php  /* error messages will be placed here via javascript */ ?>
			</div>
	
			<div class="control-group">
				<label class="control-label" for="btHandle"><?php  echo t('Handle'); ?></label>
				<div class="controls">
					<?php 
					if ($is_installed) {
						echo $fh->hidden('btHandle', $btHandle);
						echo '<span class="input-large uneditable-input dcp-tooltip" title="' . htmlentities(t('Cannot be changed while<br>blocktype is installed'), ENT_QUOTES, APP_CHARSET) . '">' . (empty($_POST['btHandle']) ? $btHandle : $_POST['btHandle']) . '</span>';
					} else {
						echo $fh->text('btHandle', $btHandle, array('class' => 'input-xlarge dcp-tooltip', 'maxlength' => '32', 'title' => t('Blocktype handle can only contain lowercase letters and underscores.<br>A &quot;dcp_&quot; prefix is recommnded (but not required).')));
					}
					echo $fh->hidden('orig_btHandle', $btHandle);
					?>
				</div>
			</div>
	
			<div class="control-group">
				<label class="control-label" for="btName"><?php  echo t('Name'); ?></label>
				<div class="controls">
					<?php  echo $fh->text('btName', $btName, array('class' => 'input-xlarge dcp-tooltip', 'maxlength' => '128', 'title' => t('Blocktype Name is displayed in the &quot;Add Block&quot; list (when adding blocks to page areas).'))); ?>
					<?php  echo $fh->hidden('orig_btName', $btName); ?>
				</div>
			</div>
	
			<div class="control-group" style="display: none;"><!-- btDescription isn't used at all in 5.5 and 5.6 so don't bother with even showing it -->
				<label class="control-label" for="btDescription"><?php  echo t('Description'); ?></label>
				<div class="controls">
					<?php  echo $fh->textarea('btDescription', $btDescription, array('class' => 'input-xlarge dcp-tooltip', 'title' => t('Blocktype Description is optional, and is not displayed to users in Concrete 5.5 and 5.6.'))); ?>
					<?php  echo $fh->hidden('orig_btDescription', $btDescription); ?>
				</div>
			</div>

			<hr>
		
			<div class="control-group">
				<h3>Repeating Item Fields</h3>
			
				<div class="sortable-container">
					<table class="table table-striped" style="width:100%;">
						<tbody id="repeating-item-fields">
							<script id="repeating-item-field-template" type="text/x-jQuery-tmpl">
								<tr data-id="${id}">
									<td>
										<div class="form-inline">
									
											{{if isNew || <?php  echo $is_installed ? 'false' : 'true'; ?>}}
										
												<select name="type" style="width:auto;" class="dcp-tooltip" title="<?php  echo htmlentities(t('Field Type'), ENT_QUOTES, APP_CHARSET); ?>">
													<option value=""><?php  echo t('&lt;Type&gt;'); ?></option>
													<?php  foreach ($field_type_labels as $type_handle => $type_label): ?>
													<option value="<?php  echo $type_handle; ?>" {{if type == '<?php  echo $type_handle; ?>'}}selected="selected"{{/if}}><?php  echo $type_label; ?></option>
													<?php  endforeach; ?>
												</select>
										
												<input type="text" name="handle" value="${handle}" maxlength="32" placeholder="<?php  echo t('handle'); ?>" class="input-medium dcp-tooltip" title="<?php  echo htmlentities(t('Field Handle (how you will refer to this field in your view.php / custom template code)'), ENT_QUOTES, APP_CHARSET); ?>" />
											
											{{else}}
												<?php 
												//super hacky way to map type handles to type names in JS
												// (it would be easier if we could just run arbitrary js code inside the template,
												// but that's tricky so instead make a js object on-the-fly and iterate through it with {{each}}).
												$type_key_vals = array();
												foreach ($field_type_labels as $type_handle => $type_label) {
													$type_key_vals[] = "'{$type_handle}': '{$type_label}'";
												}
												$js_type_map = '{' . implode(',', $type_key_vals) . '}';
												?>
												<span class="input-small uneditable-input dcp-tooltip" style="font-weight: bold;" title="<?php  echo htmlentities(t('Field Type (cannot be changed<br>while blocktype is installed)'), ENT_QUOTES, APP_CHARSET); ?>">{{each(typeHandle, typeName) <?php  echo $js_type_map; ?> }}{{if type == typeHandle}}${typeName}{{/if}}{{/each}}</span>
												<span class="input-medium uneditable-input dcp-tooltip" style="font-weight: bold;" title="<?php  echo htmlentities(t('Field Handle (cannot be changed<br>while blocktype is installed'), ENT_QUOTES, APP_CHARSET); ?>">${handle}</span>
											
												<input type="hidden" name="type" value="${type}" />
												<input type="hidden" name="handle" value="${handle}" />
											
											{{/if}}
										
											<input type="text" name="label" value="${label}" maxlength="255" placeholder="<?php  echo t('Label'); ?>" class="input-large dcp-tooltip" title="<?php  echo htmlentities(t('Field Label (what users will see in the block add/edit dialog)'), ENT_QUOTES, APP_CHARSET); ?>" />
										</div>
									
										<div class="repeating-item-field-options form-inline">
											<?php  /* This will be dynamically populated from a sub-template, depending on the field type dropdown choice */ ?>
										</div>
									</td>
									<td class="row-button-container"><span class="row-button sortable-handle" title="drag to sort"><i class="icon-resize-vertical"></i></span></td>
									<td class="row-button-container"><a href="#" class="row-button delete-repeating-item-field" title="delete"><i class="icon-trash"></i></a></td>
								</tr>
							</script>
						
							<?php  foreach ($field_type_options as $type_handle => $type_options): ?>
								<script class="repeating-item-field-options-template" data-repeating-item-field-type="<?php  echo $type_handle; ?>" type="text/x-jQuery-tmpl">
									<?php  foreach ($type_options as $option_handle => $option_settings): ?>
								
										<?php  if ($option_settings['type'] == 'checkbox'): ?>
											<label class="checkbox dcp-tooltip" title="<?php  echo htmlentities($option_settings['description'], ENT_QUOTES, APP_CHARSET); ?>">
												<input
													type="checkbox"
													name="options[<?php  echo $option_handle; ?>]"
													data-option-handle="<?php  echo $option_handle; ?>"
													value="1"
													{{if (typeof <?php  echo $option_handle; ?> !== 'undefined') && (<?php  echo $option_handle; ?>) && (<?php  echo $option_handle; ?> !== '0')}}checked="checked"{{/if}}
												/>
												<?php  echo $option_settings['label']; ?>
											</label>
										<?php  endif; ?>
									
										<?php  if ($option_settings['type'] == 'select'): ?>
											<label class="select dcp-tooltip" title="<?php  echo htmlentities($option_settings['description'], ENT_QUOTES, APP_CHARSET); ?>">
												<span style="vertical-align: middle;"><?php  echo $option_settings['label']; ?>:</span>
												<select name="options[<?php  echo $option_handle; ?>]" data-option-handle="<?php  echo $option_handle; ?>" style="width: auto; height: auto;">
													<?php  foreach ($option_settings['choices'] as $val => $text): ?>
														<option
															value="<?php  echo $val; ?>"
															{{if (typeof <?php  echo $option_handle; ?> !== 'undefined') && (<?php  echo $option_handle; ?> === '<?php  echo $val; ?>')}}
															selected="selected"
															{{/if}}
															>
															<?php  echo $text; ?>
														</option>
													<?php  endforeach; ?>
												</select>
											</label>
										<?php  endif; ?>
									
									<?php  endforeach; ?>
								</script>
							<?php  endforeach; ?>
						
							<tr id="add-repeating-item-field"><td colspan="3"><?php  echo t('Add another field...'); ?></td></tr>
						</tbody>
					</table>
				</div><!-- .sortable-container -->
			</div>
		
		</div>
		
		<div class="ccm-pane-footer">
			<?php  echo $ih->submit(t('Continue'), false, 'right', 'primary'); ?>
			<?php  echo $ih->button(t('Cancel'), $this->action('view'), 'left'); ?>
		</div>
	</form>
	
	
<?php  echo $dh->getDashboardPaneFooterWrapper(); ?>


<?php  /************************************************************************/ ?>

<input type="hidden" name="ccm-string-blockUI-message-validating" value="<?php  echo t('Validating...'); ?>" />
<input type="hidden" name="ccm-string-blockUI-message-saving" value="<?php  echo t('Saving...'); ?>" />

<script id="confirmation-template" type="text/x-jQuery-tmpl">
	<div class="ccm-ui dcp-confirmation-container" style="margin: 20px; border: 2px solid #ccc;">
		<h2><?php  echo t('Confirm Changes'); ?></h2>
		
		<hr>
		
		{{if changes.length > 0}}
			<table class="table">
				<thead><tr>
					<th>&nbsp;</th>
					<th><?php  echo t('Change'); ?></th>
					<th><?php  echo t('Notes'); ?></th>
				</tr></thead>
				
				<tbody>
					{{each(index, change) changes}}
					<tr class="{{if change.impact == 'safe'}}success{{else change.impact == 'data'}}error{{else change.impact == 'files'}}warning{{/if}}">
						<td>{{if change.reindex}}<span style="color: blue;">*</span>{{/if}}</td>
						<td>{{html change.desc}}</td>
						<td style="{{if change.impact == 'data'}}font-weight:bold;{{else}}font-style:italic;{{/if}}">
							{{html change.note}}
						</td>
					</tr>
					{{/each}}
					
					{{if reindex}}
					<tr><td colspan="3" style="font-style: italic; color: blue;">
						*
						<?php  echo t("It is recommended that you run the 'Index Search Engine' job after saving because you either added a field, removed a field, or changed a field's 'Searchable' option."); ?>
					</td></tr>
					{{/if}}
				</tbody>
			</table>
						
			<br>
			<hr>
			
			{{if !installed}}
				<div class="alert alert-success">
					<?php  echo t('This block type is not currently installed, so no existing content will be affected.'); ?>
				</div>
			{{else $.isEmptyObject(usage)}}
				<div class="alert alert-success">
					<?php  echo t('This block type has not been added to any pages or stacks, so existing content will not be affected.'); ?>
				</div>
			{{else}}
				<div class="alert alert-dcp-black-text">
					<h3>Page Usage</h3>
					<p><?php  echo t('This block type has been added to the following pages and/or stacks, so existing content may be affected...'); ?></p>
					
					<table class="table">
						<thead>
							<tr>
								<th style="border-top: none; vertical-align: bottom;"><?php  echo t('cID'); ?></th>
								<th style="border-top: none; vertical-align: bottom;"><?php  echo t('Page Name'); ?></th>
								<th style="border-top: none; text-align: center;"><?php  echo t('Approved<br>Blocks'); ?></th>
								<th style="border-top: none; text-align: center;"><?php  echo t('Version/Preview<br>Blocks'); ?></th>
							</tr>
						</thead>
						<tbody>
							{{each(cID, info) usage}}
							<tr>
								<td><a href="${CCM_DISPATCHER_FILENAME}?cID=${cID}" target="_blank">${cID}</a></td>
								<td>${info.name}{{if info.special.length > 0}} [${info.special}]{{/if}}</td>
								<td style="text-align: center; font-weight: bold;">${info.approved_count}</td>
								<td style="text-align: center;">${info.unapproved_count}</td>
							</tr>
							{{/each}}
						</tbody>
					</table>
					
				</div>
			{{/if}}
			
			<hr>
			
			<?php  echo $ih->button_js(t('Cancel'), 'cancel_from_confirmation(); return false;', 'left'); ?>
			<?php  echo $ih->button_js(t('Confirm &amp; Save'), 'save_from_confirmation(); return false;', 'right', 'primary'); ?>
		
		{{else}}
			<p><?php  echo t('You did not make any changes to the blocktype.'); ?></p>
			
			<?php  echo $ih->button_js(t('&lt; Go Back'), 'cancel_from_confirmation(); return false;', 'left'); ?>
		{{/if}}
	</div>
</script>