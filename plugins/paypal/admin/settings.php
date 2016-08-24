<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// prepare variables
$plugin_enabled = (int) $plugin['plugin_enabled'];
$paypal_email   = 'paypal@yoursite.com';
$enable_sandbox_mode = 0;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $paypal_email = $plugin_settings['paypal_email'];
		$enable_sandbox_mode = (int)$plugin_settings['enable_sandbox_mode'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled = $plugin_enabled != 1 ? 0 : 1;
    $paypal_email   = trim(strtolower($_REQUEST['paypal_email']));
	$enable_sandbox_mode = (int) $_REQUEST['enable_sandbox_mode'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (strlen($paypal_email) == 0)
    {
        adminFunctions::setError(adminFunctions::t("please_enter_your_paypal_email_address", "Please enter your PayPal account email address."));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                 = array();
        $settingsArr['paypal_email'] = $paypal_email;
		$settingsArr['enable_sandbox_mode'] = $enable_sandbox_mode;
        $settings                    = json_encode($settingsArr);

        // update the user
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        // update plugin config
        pluginHelper::loadPluginConfigurationFiles(true);
        adminFunctions::setSuccess('Plugin settings updated.');
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
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Plugin State</h3>
                            <p>Whether the PayPal payment option for upgrades is enabled.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Plugin Enabled:</label>
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
                            <h3>PayPal Settings</h3>
                            <p>Your PayPal account details, email address etc.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>PayPal Email:</label>
                                    <div class="input"><input id="paypal_email" name="paypal_email" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($paypal_email); ?>"/></div>
                                </div>
								<div class="clearfix">
                                    <label>Sandbox or Live:</label>
									<div class="input">
										<select name="enable_sandbox_mode" id="enable_sandbox_mode" class="xxlarge validate[required]">
											<?php
											$enabledOptions = array(0 => 'Live Transactions - Use this on your live site', 1 => 'Sandbox Mode - For testing only, these payments wont actually be charged');
											foreach ($enabledOptions AS $k => $enabledOption)
											{
												echo '<option value="' . $k . '"';
												if ($enable_sandbox_mode == $k)
												{
													echo ' SELECTED';
												}
												echo '>' . $enabledOption . '</option>';
											}
											?>
										</select>
									</div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>PayPal Approval:</label>
                                    <div class="input">
                                        You may need prior-approval from PayPal to run a file hosting site using their<br/>gateway. Please <a href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_help&t=escalateTab" target="_blank">contact PayPal</a> directly for more information.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
					
					<div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Set Callback</h3>
                            <p>Url callback to auto-upgrade accounts. Add this url to <a href="https://paypal.com" target="_blank">PayPal.com</a> in 'Profile', 'Profile &amp; Settings', 'My Selling Tools', then click update on 'Instant payment notifications'. Set the notification url to this value.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div class="input">
                                        <?php echo PLUGIN_WEB_ROOT . '/paypal/site/_payment_ipn.php'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="submit" value="Submit" class="button blue">
                                    <input type="reset" value="Cancel" class="button" onClick="window.location='<?php echo ADMIN_WEB_ROOT; ?>/plugin_manage.php';"/>
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
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>