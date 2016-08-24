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
$pluginDetails = pluginHelper::pluginSpecificConfiguration('imageviewer');
$pluginConfig  = $pluginDetails['config'];

// dropdowns
$watermarkPositionOptions                  = array();
$watermarkPositionOptions['top-left']      = 'Top-Left';
$watermarkPositionOptions['top-middle']    = 'Top-Middle';
$watermarkPositionOptions['top-right']     = 'Top-Right';
$watermarkPositionOptions['right']         = 'Right';
$watermarkPositionOptions['bottom-right']  = 'Bottom-Right';
$watermarkPositionOptions['bottom-middle'] = 'Bottom-Middle';
$watermarkPositionOptions['bottom-left']   = 'Bottom-Left';
$watermarkPositionOptions['left']          = 'Left';
$watermarkPositionOptions['middle']        = 'Middle';

// prepare variables
$plugin_enabled      = (int) $plugin['plugin_enabled'];
$non_show_viewer     = 1;
$free_show_viewer    = 1;
$paid_show_viewer    = 1;
$show_download_link  = 1;
$image_size_w        = 920;
$image_size_h        = 700;
$watermark_enabled   = 0;
$watermark_contents  = '';
$watermark_filename  = '';
$watermark_position  = 'bottom-right';
$watermark_padding   = 10;
$show_embedding      = 1;
$thumb_size_w        = 180;
$thumb_size_h        = 150;
$thumb_resize_method = 'cropped';
$caching             = 1;
$show_download_sizes = 1;
$ignore_download_timer = 0;
$show_direct_link = 0;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $non_show_viewer     = (int) $plugin_settings['non_show_viewer'];
        $free_show_viewer    = (int) $plugin_settings['free_show_viewer'];
        $paid_show_viewer    = (int) $plugin_settings['paid_show_viewer'];
        $show_download_link  = (int) $plugin_settings['show_download_link'];
        $image_size_w        = (int) $plugin_settings['image_size_w'];
        $image_size_h        = (int) $plugin_settings['image_size_h'];
        $watermark_enabled   = (int) $plugin_settings['watermark_enabled'];
        $watermark_position  = $plugin_settings['watermark_position'];
        $watermark_padding   = (int) $plugin_settings['watermark_padding'];
        $show_embedding      = (int) $plugin_settings['show_embedding'];
        $thumb_size_w        = (int) $plugin_settings['thumb_size_w'];
        $thumb_size_h        = (int) $plugin_settings['thumb_size_h'];
        $thumb_resize_method = $plugin_settings['thumb_resize_method'];
        $caching             = (int) $plugin_settings['caching'];
        $show_download_sizes = (int) $plugin_settings['show_download_sizes'];
        $ignore_download_timer = (int) $plugin_settings['ignore_download_timer'];
        $show_direct_link = (int) $plugin_settings['show_direct_link'];

        // load watermark
        if ($watermark_enabled == 1)
        {
            $watermark = $db->getRow("SELECT file_name, image_content FROM plugin_imageviewer_watermark");
            if ($watermark)
            {
                $watermark_contents = $watermark['image_content'];
                $watermark_filename = $watermark['file_name'];
            }
            else
            {
                $watermark_enabled = 0;
            }
        }
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled      = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled      = $plugin_enabled != 1 ? 0 : 1;
    $non_show_viewer     = isset($_REQUEST['non_show_viewer']) ? 1 : 0;
    $free_show_viewer    = isset($_REQUEST['free_show_viewer']) ? 1 : 0;
    $paid_show_viewer    = isset($_REQUEST['paid_show_viewer']) ? 1 : 0;
    $show_download_link  = (int) $_REQUEST['show_download_link'];
    $image_size_w        = (int) $_REQUEST['image_size_w'];
    $image_size_h        = (int) $_REQUEST['image_size_h'];
    $watermark_enabled   = (int) $_REQUEST['watermark_enabled'];
    $watermark_position  = $_REQUEST['watermark_position'];
    $watermark_padding   = (int) $_REQUEST['watermark_padding'];
    $show_embedding      = (int) $_REQUEST['show_embedding'];
    $thumb_size_w        = (int) $_REQUEST['thumb_size_w'];
    $thumb_size_h        = (int) $_REQUEST['thumb_size_h'];
    $thumb_resize_method = $_REQUEST['thumb_resize_method'];
    $caching             = (int) $_REQUEST['caching'];
    $show_download_sizes = (int) $_REQUEST['show_download_sizes'];
    $ignore_download_timer = (int) $_REQUEST['ignore_download_timer'];
    $show_direct_link = (int) $_REQUEST['show_direct_link'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif ($image_size_w == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_image_viewer_please_set_a_width", "Please set a width."));
    }
    elseif ($image_size_h == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_image_viewer_please_set_a_height", "Please set a height."));
    }
    elseif ($watermark_enabled == 1)
    {
        // new uploaded image
        if (strlen($_FILES["watermark_image"]["name"]))
        {
            // make sure we've got an image
            $file      = $_FILES["watermark_image"]["name"];
            $extension = strtolower(end(explode(".", $file)));
            if ($extension != 'png')
            {
                adminFunctions::setError(adminFunctions::t("plugin_image_viewer_watermark_must_be_a_png", "Watermark image must be a png image."));
            }
            else
            {
                $watermark_filename = $_FILES["watermark_image"]["name"];
                $watermark_contents = file_get_contents($_FILES["watermark_image"]["tmp_name"]);
            }
        }
    }
    else
    {
        $watermark_contents = '';
        $watermark_filename = '';
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                        = array();
        $settingsArr['non_show_viewer']     = (int) $non_show_viewer;
        $settingsArr['free_show_viewer']    = (int) $free_show_viewer;
        $settingsArr['paid_show_viewer']    = (int) $paid_show_viewer;
        $settingsArr['show_download_link']  = (int) $show_download_link;
        $settingsArr['image_size_w']        = (int) $image_size_w;
        $settingsArr['image_size_h']        = (int) $image_size_h;
        $settingsArr['watermark_enabled']   = (int) $watermark_enabled;
        $settingsArr['watermark_position']  = $watermark_position;
        $settingsArr['watermark_padding']   = (int) $watermark_padding;
        $settingsArr['show_embedding']      = (int) $show_embedding;
        $settingsArr['thumb_size_w']        = (int) $thumb_size_w;
        $settingsArr['thumb_size_h']        = (int) $thumb_size_h;
        $settingsArr['thumb_resize_method'] = $thumb_resize_method;
        $settingsArr['caching']             = (int) $caching;
        $settingsArr['show_download_sizes'] = (int) $show_download_sizes;
        $settingsArr['ignore_download_timer'] = (int) $ignore_download_timer;
        $settingsArr['show_direct_link'] = (int) $show_direct_link;
        $settings                           = json_encode($settingsArr);

        // update the settings
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        // update watermark, delete exists
        $db->query("DELETE FROM plugin_imageviewer_watermark");
        $dbInsert                = new DBObject("plugin_imageviewer_watermark", array("file_name", "image_content"));
        $dbInsert->file_name     = $watermark_filename;
        $dbInsert->image_content = $watermark_contents;
        $dbInsert->insert();

        adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?se=1');
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<script>
    $(function() {
        $("#pluginForm").validationEngine();
        showHideWatermark();
    });

    function showHideWatermark()
    {
        if ($('#watermark_enabled').val() == '1')
        {
            $('.watermarkHiddenRow').show();
        }
        else
        {
            $('.watermarkHiddenRow').hide();
        }
    }
</script>

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
                            <p>Whether the image viewer is available.</p>
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
                            <h3>Account Types</h3>
                            <p>Select which types of users should be able to see the images directly. Any which aren't selected will just be sent the file as a download. For free users, the image is shown after the download countdown, if set.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label for="non_show_viewer">Non Registered Users:</label>
                                    <div class="input" style="padding-top: 2px;">
                                        <input type="checkbox" name="non_show_viewer" id="non_show_viewer" <?php echo ($non_show_viewer == 1) ? 'CHECKED' : ''; ?>/>
                                    </div>
                                </div>

                                <div class="clearfix">
                                    <label for="free_show_viewer">Free Users:</label>
                                    <div class="input" style="padding-top: 2px;">
                                        <input type="checkbox" name="free_show_viewer" id="free_show_viewer" <?php echo ($free_show_viewer == 1) ? 'CHECKED' : ''; ?>/>
                                    </div>
                                </div>

                                <div class="clearfix alt-highlight">
                                    <label for="paid_show_viewer">Paid Users:</label>
                                    <div class="input" style="padding-top: 2px;">
                                        <input type="checkbox" name="paid_show_viewer" id="paid_show_viewer" <?php echo ($paid_show_viewer == 1) ? 'CHECKED' : ''; ?>/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Watermark</h3>
                            <p>Whether to overlay a watermark on the image. Image should be a png file. If using transparency ensure it's at least 24bit for the best results.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Watermark Enabled:</label>
                                    <div class="input">
                                        <select name="watermark_enabled" id="watermark_enabled" class="medium validate[required]" onChange="showHideWatermark();
        return false;">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($watermark_enabled == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="clearfix watermarkHiddenRow">
                                    <label for="watermark_image">Watermark PNG Image:</label>
                                    <div class="input" style="padding-top: 2px;">
                                        <input type="file" name="watermark_image" id="watermark_image"/>
                                        <?php
                                        if (strlen($watermark_contents))
                                        {
                                            echo '<br/><img src="data:image/png;base64,' . base64_encode($watermark_contents) . '" style="padding-top: 8px;"/>';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="clearfix alt-highlight watermarkHiddenRow">
                                    <label for="watermark_position">Watermark Position:</label>
                                    <div class="input" style="padding-top: 2px;">
                                        <select name="watermark_position" id="watermark_position" class="medium">
                                            <?php
                                            foreach ($watermarkPositionOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($watermark_position == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="clearfix watermarkHiddenRow">
                                    <label for="watermark_padding">Watermark Padding:</label>
                                    <div class="input" style="padding-top: 2px;">
                                        <input type="text" name="watermark_padding" id="watermark_padding" class="small" value="<?php echo (int) $watermark_padding; ?>"/> px
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Image Options</h3>
                            <p>The options for resizing the main image.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Max Full Image Size:</label>
                                    <div class="input">
                                        <input type="text" name="image_size_w" id="image_size_w" class="small" value="<?php echo (int) adminFunctions::makeSafe($image_size_w); ?>" placeholder="width"/> px
                                        &nbsp;by&nbsp;
                                        <input type="text" name="image_size_h" id="image_size_h" class="small" value="<?php echo (int) adminFunctions::makeSafe($image_size_h); ?>" placeholder="height"/> px
                                        <br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'>The maximum size to show the full image preview. Recommended is 920x700 to fit the width of the preview page.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Thumbnail Options</h3>
                            <p>Thumbnail size and method of resizing. Thumbnails are used when link to the image from an external site.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Max Thumbnail Size:</label>
                                    <div class="input">
                                        <input type="text" name="thumb_size_w" id="thumb_size_w" class="small" value="<?php echo (int) adminFunctions::makeSafe($thumb_size_w); ?>" placeholder="width"/> px
                                        &nbsp;by&nbsp;
                                        <input type="text" name="thumb_size_h" id="thumb_size_h" class="small" value="<?php echo (int) adminFunctions::makeSafe($thumb_size_h); ?>" placeholder="height"/> px
                                        <br/>
                                        <div class="formFieldFix" style='width: 500px; color: #777; font-size: 11px;'>The maximum size to create the thumbnail. Default is 180x150.</div>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Resize Method:</label>
                                    <div class="input">
                                        <select name="thumb_resize_method" id="thumb_resize_method" class="xxlarge validate[required]">
                                            <?php
                                            $enabledOptions = array('cropped' => 'Cropped (no white padding)', 'padded'  => 'Fixed Size (padded white so image is always the size above)');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($thumb_resize_method == $k)
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
                            <h3>Other Options</h3>
                            <p>Show download link on image viewer page.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Show Download Link:</label>
                                    <div class="input">
                                        <select name="show_download_link" id="show_download_link" class="large">
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
                                    <label>Show Embedding Opt:</label>
                                    <div class="input">
                                        <select name="show_embedding" id="show_embedding" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($show_embedding == $k)
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
                                    <label>Show Resizing Opt:</label>
                                    <div class="input">
                                        <select name="show_download_sizes" id="show_download_sizes" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($show_download_sizes == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Ignore Download Wait:</label>
                                    <div class="input">
                                        <select name="ignore_download_timer" id="ignore_download_timer" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($ignore_download_timer == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            If set to 'yes', the download timer will not be shown for images. Even for free/non-users users.
                                        </div>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Show Direct Link:</label>
                                    <div class="input">
                                        <select name="show_direct_link" id="show_direct_link" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($show_direct_link == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            If set to 'yes', a direct link to the image will be available to the user. This will permit<br/>direct hot-linking of images.
                                        </div>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Image Caching:</label>
                                    <div class="input">
                                        <select name="caching" id="caching" class="xxlarge validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'Disabled (uses more cpu but saves space)', 1 => 'Enabled (recommended - caches resized images, less cpu usage)');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($caching == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            If enabled ensure that the following path has at least chmod 777 permissions:
                                        </div>
                                        <div style="padding-top: 7px; color: #777; font-size: 11px;">
                                            <?php echo PLUGIN_DIRECTORY_ROOT; ?>imageviewer/site/cache/
                                        </div>
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