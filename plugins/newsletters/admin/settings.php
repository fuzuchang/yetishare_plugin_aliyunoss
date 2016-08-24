<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'newsletters');
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
$plugin_enabled     = (int) $plugin['plugin_enabled'];
$test_email_address = SITE_CONFIG_SITE_ADMIN_EMAIL;
$unsubscribe_text   = 'You are receiving this email as you have an account on our site. If you wish to unsubscribe from future emails, please <a href="[[[unsubscribe_link]]]">click here</a>.';
$send_email_from_email = 'email@yoursite.com';
//$send_email_from_name = SITE_CONFIG_SITE_NAME;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $test_email_address = $plugin_settings['test_email_address'];
        $unsubscribe_text   = $plugin_settings['unsubscribe_text'];
        $send_email_from_email = $plugin_settings['send_email_from_email'];
        //$send_email_from_name = $plugin_settings['send_email_from_name'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled     = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled     = $plugin_enabled != 1 ? 0 : 1;
    $test_email_address = strtolower(trim($_REQUEST['test_email_address']));
    $unsubscribe_text   = trim($_REQUEST['unsubscribe_text']);
    $send_email_from_email = strtolower(trim($_REQUEST['send_email_from_email']));
    //$send_email_from_name   = trim($_REQUEST['send_email_from_name']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr = array();
        $settingsArr['test_email_address'] = $test_email_address;
        $settingsArr['unsubscribe_text']   = $unsubscribe_text;
        $settingsArr['send_email_from_email']   = $send_email_from_email;
        $settingsArr['send_email_from_name']   = $send_email_from_name;
        $settings                          = json_encode($settingsArr);

        // update the user
        $dbUpdate = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id = $pluginId;
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
                            <p>Whether the newsletter plugin is available.</p>
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
                            <h3>Other Settings</h3>
                            <p>Default test email address, un-subscribe text.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Send From Email:</label>
                                    <div class="input"><input id="send_email_from_email" name="send_email_from_email" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($send_email_from_email); ?>"/></div>
                                </div>
                                <div class="clearfix">
                                    <label>Newsletter Test Email:</label>
                                    <div class="input"><input id="test_email_address" name="test_email_address" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($test_email_address); ?>"/></div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Unsubscribe Text:</label>
                                    <div class="input">
                                        <textarea id="unsubscribe_text" name="unsubscribe_text" class="xxlarge"><?php echo adminFunctions::makeSafe($unsubscribe_text); ?></textarea>
                                        <br/>
                                        <div class="formFieldFix" style="width: 500px; color: #777; font-size: 11px;">Use <strong>[[[unsubscribe_link]]]</strong> in the text above to automatically have the users unsubscribe link added within each newsletter.</div>
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