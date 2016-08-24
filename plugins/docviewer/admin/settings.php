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

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('docviewer');
$pluginConfig  = $pluginDetails['config'];

// prepare variables
$plugin_enabled        = (int) $plugin['plugin_enabled'];
$non_document_types    = 'doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,psd,tiff,dxf,svg,eps,ps,ttf,otf,xps';
$free_document_types   = 'doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,psd,tiff,dxf,svg,eps,ps,ttf,otf,xps';
$paid_document_types   = 'doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,psd,tiff,dxf,svg,eps,ps,ttf,otf,xps';
$show_download_link    = 1;
$show_embed            = 1;
$embed_document_size_w = 450;
$embed_document_size_h = 600;
$pdf_thumbnails = 1;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $non_document_types    = $plugin_settings['non_document_types'];
        $free_document_types   = $plugin_settings['free_document_types'];
        $paid_document_types   = $plugin_settings['paid_document_types'];
        $show_download_link    = (int) $plugin_settings['show_download_link'];
        $show_embed            = (int) $plugin_settings['show_embed'];
        $embed_document_size_w = (int) $plugin_settings['embed_document_size_w'];
        $embed_document_size_h = (int) $plugin_settings['embed_document_size_h'];
        $pdf_thumbnails = isset($plugin_settings['pdf_thumbnails'])?(int) $plugin_settings['pdf_thumbnails']:0;
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled        = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled        = $plugin_enabled != 1 ? 0 : 1;
    $non_document_types    = strtolower(str_replace(' ', '', ($_REQUEST['non_document_types'])));
    $free_document_types   = strtolower(str_replace(' ', '', ($_REQUEST['free_document_types'])));
    $paid_document_types   = strtolower(str_replace(' ', '', ($_REQUEST['paid_document_types'])));
    $show_download_link    = (int) $_REQUEST['show_download_link'];
    $show_embed            = (int) $_REQUEST['show_embed'];
    $embed_document_size_w = (int) $_REQUEST['embed_document_size_w'];
    $embed_document_size_h = (int) $_REQUEST['embed_document_size_h'];
    $pdf_thumbnails = (int) $_REQUEST['pdf_thumbnails'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif ($show_embed == 1)
    {
        if ((int) $embed_document_size_w == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_media_player_set_video_width", "Please set a width for the embed code. Recommend using 640."));
        }
        elseif ((int) $embed_document_size_h == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_media_player_set_video_height", "Please set a height for the embed code. Recommend using 320."));
        }
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                          = array();
        $settingsArr['non_document_types']    = $non_document_types;
        $settingsArr['free_document_types']   = $free_document_types;
        $settingsArr['paid_document_types']   = $paid_document_types;
        $settingsArr['show_download_link']    = (int) $show_download_link;
        $settingsArr['show_embed']            = (int) $show_embed;
        $settingsArr['embed_document_size_w'] = (int) $embed_document_size_w;
        $settingsArr['embed_document_size_h'] = (int) $embed_document_size_h;
        $settingsArr['pdf_thumbnails'] = (int) $pdf_thumbnails;
        $settings                             = json_encode($settingsArr);

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

// check for ImageMagick functions in PHP
if($pdf_thumbnails == 1)
{
    if(!class_exists("imagick"))
    {
        adminFunctions::setError(adminFunctions::t("plugin_docviewer_imagick_not_installed", "In order to generate PDF thumbnails you need ImageMagik installed within PHP. Alternatively, disable the PDF thumbnail option below."));
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
                            <p>Whether the document viewer is available.</p>
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
                            <h3>Document Types</h3>
                            <p>Set which types of documents should be previewed. Comma separated of extensions. Supported extensions: doc,docx,xls,xlsx,ppt,pptx,pdf,pages,ai,psd, tiff,dxf,svg,eps,ps,ttf,otf,xps,zip,rar. For free users, the document preview is shown after the download countdown, if set. Note: Only files of less than 25MB can be previewed.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Non Registered Users:</label>
                                    <div class="input">
                                        <input type="text" name="non_document_types" id="non_document_types" class="xxlarge" value="<?php echo adminFunctions::makeSafe($non_document_types); ?>"/>
                                    </div>
                                </div>

                                <div class="clearfix">
                                    <label>Free Accounts:</label>
                                    <div class="input">
                                        <input type="text" name="free_document_types" id="free_document_types" class="xxlarge" value="<?php echo adminFunctions::makeSafe($free_document_types); ?>"/>
                                    </div>
                                </div>

                                <div class="clearfix alt-highlight">
                                    <label>Paid Accounts:</label>
                                    <div class="input">
                                        <input type="text" name="paid_document_types" id="paid_document_types" class="xxlarge" value="<?php echo adminFunctions::makeSafe($paid_document_types); ?>"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Other Options</h3>
                            <p>Show download link on preview page and whether to show embed code.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Show download link:</label>
                                    <div class="input">
                                        <select name="show_download_link" id="show_download_link" class="medium">
                                            <?php
                                            $downloadOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($downloadOptions AS $k => $downloadOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($show_download_link == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $downloadOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Show Embed Code:</label>
                                    <div class="input">
                                        <select name="show_embed" id="show_embed" class="medium">
                                            <?php
                                            $options = array(0 => 'No', 1 => 'Yes');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($show_embed == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Embedded Size:</label>
                                    <div class="input">
                                        <input type="text" name="embed_document_size_w" id="embed_document_size_w" class="small" value="<?php echo (int) adminFunctions::makeSafe($embed_document_size_w); ?>" placeholder="width"/> px
                                        &nbsp;by&nbsp;
                                        <input type="text" name="embed_document_size_h" id="embed_document_size_h" class="small" value="<?php echo (int) adminFunctions::makeSafe($embed_document_size_h); ?>" placeholder="height"/> px
                                        <br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'>This is the size of the iframe and hence document when embedded on an external site.</div>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>PDF Thumbnails:</label>
                                    <div class="input">
                                        <select name="pdf_thumbnails" id="pdf_thumbnails" class="medium">
                                            <?php
                                            $options = array(0 => 'No', 1 => 'Yes');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($pdf_thumbnails == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'>If 'yes', a thumbnail will be shown for PDF documents instead of the standard PDF icon. ImageMagick is required within PHP (on all servers) for these to be created.</div>
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