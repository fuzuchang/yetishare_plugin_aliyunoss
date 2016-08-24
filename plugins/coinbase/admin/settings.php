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
$apiKey		= '';
$apiSecret	= '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $apiKey		= $plugin_settings['apiKey'];
		$apiSecret	= $plugin_settings['apiSecret'];
		if (_CONFIG_DEMO_MODE == true)
		{
			$apiKey		= '[HIDDEN]';
			$apiSecret	= '[HIDDEN]';
		}
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled = $plugin_enabled != 1 ? 0 : 1;
    $apiKey			= trim($_REQUEST['apiKey']);
	$apiSecret		= trim($_REQUEST['apiSecret']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif(strlen($apiKey) == 0)
    {
        adminFunctions::setError(adminFunctions::t("please_enter_your_api_key", "Please enter your API key."));
    }
	elseif(strlen($apiSecret) == 0)
    {
        adminFunctions::setError(adminFunctions::t("please_enter_your_secret_key", "Please enter your API Secret."));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr = array();
        $settingsArr['apiKey']		= $apiKey;
		$settingsArr['apiSecret']	= $apiSecret;
        $settings                   = json_encode($settingsArr);

        // update the plugin settings
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
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Plugin State</h3>
                            <p>Whether the Coinbase payment option for upgrades is enabled.</p>
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
                            <h3>Coinbase Settings</h3>
                            <p>Your Coinbase API &amp; Secret keys. Generate Your keys <a href="https://coinbase.com/settings/api" target="_blank"> here</a>.</p>
							<p>You need to set the following permissions in the API for the plugin to work correctly.</p>
							<code style="padding:5px;">buttons</code> (Leave all others blank).
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>API Key:</label>
                                    <div class="input"><input id="apiKey" name="apiKey" type="text" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($apiKey); ?>"/></div>
                                </div>
                            </div>
                        </div>
						
						<div class="col_8 last" style="margin-top:5px;">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Secret Key:</label>
                                    <div class="input"><input id="apiSecret" name="apiSecret" type="text" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($apiSecret); ?>"/></div>
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
                                    <input type="reset" value="Reset" class="button grey">
                                </div>
                            </div>
                        </div>
                    </div>
					<div align="center"> For support with this plugin, please visit the <a href="https://forums.ysmods.com" target="_blank">YSMods Forums</a>.</div>
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