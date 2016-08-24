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
$aws_access_key = '';
$aws_secret_key = '';
$bucket_name    = '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $aws_access_key = $plugin_settings['aws_access_key'];
        $aws_secret_key = $plugin_settings['aws_secret_key'];
        $bucket_name    = $plugin_settings['bucket_name'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled = $plugin_enabled != 1 ? 0 : 1;
    $aws_access_key = trim($_REQUEST['aws_access_key']);
    $aws_secret_key = trim($_REQUEST['aws_secret_key']);
    $bucket_name    = trim($_REQUEST['bucket_name']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (strlen($aws_access_key) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_filestores3_please_enter_your_aws_access_key", "Please enter your AWS access key."));
    }
    elseif (strlen($aws_secret_key) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_filestores3_please_enter_your_aws_secret_key", "Please enter your AWS secret key."));
    }
    elseif (strlen($bucket_name) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_filestores3_please_enter_your_bucket_name", "Please enter your bucket name."));
    }

    // try to authenticate the details
    if (adminFunctions::isErrors() == false)
    {
        // get required classes
        require_once(PLUGIN_DIRECTORY_ROOT . 'filestores3/includes/s3/S3.php');
        
        // check that we can connect
        $s3 = new S3($aws_access_key, $aws_secret_key);
        if(!$s3)
        {
            // failed connecting
            adminFunctions::setError(adminFunctions::t("plugin_filestores3_could_not_autheticate_access_details", "Could not connect to S3 using the keys you entered, please try again."));
        }
        else
        {
            // check bucket
            if($s3->getBucket($bucket_name) === false)
            {
                // failed getting bucket
                adminFunctions::setError(adminFunctions::t("plugin_filestores3_could_not_load_bucket", "We could not find the bucket or the AWS keys are incorrect, please try again."));
            }
        }
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                   = array();
        $settingsArr['aws_access_key'] = $aws_access_key;
        $settingsArr['aws_secret_key'] = $aws_secret_key;
        $settingsArr['bucket_name']    = $bucket_name;
        $settings                      = json_encode($settingsArr);

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

if(_CONFIG_DEMO_MODE == true)
{
    $aws_secret_key = '**********************************';
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
                            <p>Whether the Amazon S3 Storage plugin is enabled. Do not disable this if you have active files stored on S3.</p>
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
                            <h3>S3 Credentials</h3>
                            <p>AWS access details and bucket name. Note: The bucket name should already exist. If you've just created it, it may take up to a minute for it to be distributed and this page to see it.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>AWS Access Key:</label>
                                    <div class="input"><input id="aws_access_key" name="aws_access_key" type="text" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($aws_access_key); ?>"/></div>
                                </div>

                                <div class="clearfix">
                                    <label>AWS Secret Key:</label>
                                    <div class="input"><input id="aws_secret_key" name="aws_secret_key" type="password" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($aws_secret_key); ?>"/></div>
                                </div>

                                <div class="clearfix alt-highlight">
                                    <label>AWS Bucket Name:</label>
                                    <div class="input"><input id="bucket_name" name="bucket_name" type="text" class="xlarge validate[required]" value="<?php echo adminFunctions::makeSafe($bucket_name); ?>"/></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="submit" value="Submit" class="button blue"/>
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