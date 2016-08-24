<?php

// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');

// includes and security
include_once('/home/resasundoro/public_html/core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');
include_once('../includes/functions.php');

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
	adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// prepare variables
$plugin_enabled  = (int) $plugin['plugin_enabled'];
if (strlen($plugin['plugin_settings']))
{
	$plugin_settings = json_decode($plugin['plugin_settings'], true);
	if ($plugin_settings)
	{
		$disable_selling = $plugin_settings['disable_selling'];
		$dburl			 = $plugin_settings['url'];
	}
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
	// get variables
	$plugin_enabled		= (int) $_REQUEST['plugin_enabled'];
	$disable_selling	= (int) $_REQUEST['disable_selling'];
	$plugin_enabled		= $plugin_enabled != 1 ? 0 : 1;
	$url			    = $_REQUEST['url'];

	// validate submission
	if (_CONFIG_DEMO_MODE == true)
	{
		adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
	}
	$settingsArr					= array();
	$settingsArr['disable_selling'] = $disable_selling;
	$settingsArr['url']				= $url;
	$settings						= json_encode($settingsArr);

	// update the settings
	if (adminFunctions::isErrors() == false)
	{
		// update the user
		$dbUpdate = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
		$dbUpdate->plugin_enabled = $plugin_enabled;
		$dbUpdate->plugin_settings = $settings;
		$dbUpdate->id = $pluginId;
		$dbUpdate->update();
		adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?se=1');
	}
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');

		?>
		<script>
			$(function() {
				// formvalidator
				$("#userForm").validationEngine();
			});
		</script>
		<div class="row clearfix">
			<div class="col_12">
				<div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
				<div class="widget clearfix">
					<h2><?php echo adminFunctions::t('voucher_plugin_settings', 'Plugin Settings'); ?></h2>
					<div class="widget_inside">
						<?php echo adminFunctions::compileNotifications(); ?>
						<form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off">
							<div class="clearfix col_12">
								<div class="col_4">
									<h3><?php echo adminFunctions::t('vouchers_plugin_state', 'Plugin State'); ?></h3>
									<p><?php echo adminFunctions::t('vouchers_plugin_enabled', 'Whether the voucher system is enabled'); ?>.</p>
								</div>
								<div class="col_8 last">
									<div class="form">
										<div class="clearfix alt-highlight">
											<label><?php echo adminFunctions::t('vouchers_enabled', 'Plugin Enabled'); ?>:</label>
											<div class="input">
												<select name="plugin_enabled" id="plugin_enabled" class="medium validate[required]">
													<?php
													$enabledOptions = array(0 => 'No', 1 => 'Yes');
													foreach ($enabledOptions AS $k => $enabledOption)
													{
														echo '<option value="' . $k . '"';
														if ($plugin_enabled == $k)
														{
															echo ' SELECTED';
														}
														echo '>' . $enabledOption . '</option>';
													}
													?>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="clearfix col_12">
								<div class="col_4">
									<h3><?php echo adminFunctions::t('vouchers_disable_selling', 'Disable Buy Voucher Button'); ?></h3>
									<p><?php echo adminFunctions::t('vouchers_disable_selling_desc', 'If you do not want to sell vouchers, set this option to "Disable". It will also hide the "Buy a voucher" button on the upgrade page'); ?>.</p>
								</div>
								<div class="col_8 last">
									<div class="form">
										<div class="clearfix alt-highlight">
											<label><?php echo adminFunctions::t('vouchers_disable_label', 'Disable Selling'); ?>:</label>
											<div class="input">
												<select name="disable_selling" id="disable_selling" class="medium validate[required]">
													<?php
													$enabledOptions = array(0 => 'Enable', 1 => 'Disable');
													foreach ($enabledOptions AS $k => $enabledOption)
													{
														echo '<option value="' . $k . '"';
														if ($disable_selling == $k)
														{
															echo ' SELECTED';
														}
														echo '>' . $enabledOption . '</option>';
													}
													?>
												</select>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="clearfix col_12">
								<div class="col_4">
									<h3><?php echo adminFunctions::t('vouchers_purchase_url', 'Voucher Purchase URL'); ?></h3>
									<p><?php echo adminFunctions::t('vouchers_purchase_url_desc', 'Where users can buy vouchers from'); ?>.</p>
								</div>
								<div class="col_8 last">
									<div class="form">
										<div class="clearfix alt-highlight">
											<label><?php echo adminFunctions::t('vouchers_plugin_url', 'Purchase URL'); ?>:</label>
											<div class="input">
												<input type="text" name="url" id="url" style="width:250px" value="<?php echo $dburl; ?>" />
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="clearfix col_12">
								<div class="col_4">&nbsp;</div>
								<div class="col_8 last">
									<div class="clearfix">
										<div class="input no-label">
											<input type="submit" value="Submit" class="button blue">
										</div>
									</div>
								</div>
							</div>
							<input name="submitted" type="hidden" value="1"/>
							<input name="id" type="hidden" value="<?php echo $pluginId; ?>"/>
						</form>
					</div>
				</div> 
			</div>
		</div>
	<?php
	
include_once (ADMIN_ROOT.'/_footer.inc.php');

?>