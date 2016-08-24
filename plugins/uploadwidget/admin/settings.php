<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// path base
define('PLUGIN_EMBED_PATH', PLUGIN_WEB_ROOT.'/uploadwidget/site/');
define('PLUGIN_ASSET_PATH', PLUGIN_WEB_ROOT.'/uploadwidget/assets/css/');

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('uploadwidget');
$pluginConfig  = $pluginDetails['config'];

// prepare variables
$plugin_enabled      = (int) $plugin['plugin_enabled'];

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        // kept if needed later
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled      = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled      = $plugin_enabled != 1 ? 0 : 1;

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                        = array();
        $settings                           = json_encode($settingsArr);

        // update the settings
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?se=1');
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off" enctype="multipart/form-data">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Plugin State</h3>
                            <p>Whether the upload widget is available.</p>
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
                            <h3>Upload Widgets</h3>
                            <p>Try out any of the widget below by using the examples. Copy &amp; paste any of the HTML code into your own site to embed the uploader. There are 3 default variations of the uploader widget, you can amend the styles of these by editing the css file listed. Site upload settings still apply to widget uploaders.</p>
                        </div>
                   
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Large Uploader</h3>
                            <p>Matches the main site styling. Larger format, includes progress uploader, multiple file upload, chunked uploading and custom buttons.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        <p>Widget Example:</p><br/>
                                        <iframe src="<?php echo PLUGIN_EMBED_PATH; ?>_embed_large.php" width="100%" height="164" frameborder="0" style="border: 1px solid #ccc;"></iframe>
                                        
                                        <p style="margin-top: 16px;">Widget HTML Code:</p>
                                        <pre><code><?php
                                            $htmlCode = '<iframe src="'.PLUGIN_EMBED_PATH.'_embed_large.php" width="100%" height="164" frameborder="0"></iframe>';
                                            echo htmlspecialchars($htmlCode);
                                            ?></code></pre>
                                        
                                        <p style="margin-top: 16px;">CSS File:</p>
                                        <code><?php echo PLUGIN_ASSET_PATH; ?>large.css</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Minimal Uploader</h3>
                            <p>Less formatting, button uploaders, multiple file upload, chunked uploading, useful as a good starting point for customising or when space is limited.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        <p>Widget Example:</p><br/>
                                        <iframe src="<?php echo PLUGIN_EMBED_PATH; ?>_embed_minimal.php" width="100%" height="90" frameborder="0" style="border: 1px solid #ccc;"></iframe>
                                        
                                        <p style="margin-top: 16px;">Widget HTML Code:</p>
                                        <pre><code><?php
                                            $htmlCode = '<iframe src="'.PLUGIN_EMBED_PATH.'_embed_minimal.php" width="100%" height="90" frameborder="0"></iframe>';
                                            echo htmlspecialchars($htmlCode);
                                            ?></code></pre>
                                        
                                        <p style="margin-top: 16px;">CSS File:</p>
                                        <code><?php echo PLUGIN_ASSET_PATH; ?>minimal.css</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Large Uploader, No Title</h3>
                            <p>Same as the large upload above however with less text content such as the header. Useful for when you apply your own text around the widget.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        <p>Widget Example:</p><br/>
                                        <iframe src="<?php echo PLUGIN_EMBED_PATH; ?>_embed_large_nh.php" width="100%" height="113" frameborder="0" style="border: 1px solid #ccc;"></iframe>
                                        
                                        <p style="margin-top: 16px;">Widget HTML Code:</p>
                                        <pre><code><?php
                                            $htmlCode = '<iframe src="'.PLUGIN_EMBED_PATH.'_embed_large_nh.php" width="100%" height="112" frameborder="0"></iframe>';
                                            echo htmlspecialchars($htmlCode);
                                            ?></code></pre>
                                        
                                        <p style="margin-top: 16px;">CSS File:</p>
                                        <code><?php echo PLUGIN_ASSET_PATH; ?>large_nh.css</code>
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
                                    <input type="reset" value="Reset" class="button grey">
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