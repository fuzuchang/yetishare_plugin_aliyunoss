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
define('DEFAULT_SUPPORTED_FILE_EXTENSIONS', 'avi,3gp,ogg,mpg,mpeg,mov,mj2,flv,wmv,webm,mp4,m4v');

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
$plugin_enabled     = (int) $plugin['plugin_enabled'];
$max_conversions    = 1;
$video_size_w       = 640;
$video_size_h       = 320;
$output_messages    = 0;
$ssh_host           = current(explode(":", _CONFIG_SITE_HOST_URL));
$ssh_user           = 'root';
$ssh_password       = '';
$local_storage_path = _CONFIG_FILE_STORAGE_PATH;
$convert_files      = DEFAULT_SUPPORTED_FILE_EXTENSIONS;
$output_type = 'mp4';
$watermark_enabled   = 0;
$watermark_contents  = '';
$watermark_filename  = '';
$watermark_position  = 'bottom-right';
$watermark_padding   = 10;
$script_path_root    = DOC_ROOT;
$keep_original    = 0;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        // load settings locally
        $max_conversions    = (int) $plugin_settings['max_conversions'];
        $video_size_w       = (int) $plugin_settings['video_size_w'];
        $video_size_h       = (int) $plugin_settings['video_size_h'];
        $output_messages    = (int) $plugin_settings['output_messages'];
        $ssh_host           = $plugin_settings['ssh_host'];
        $ssh_user           = $plugin_settings['ssh_user'];
        $ssh_password       = $plugin_settings['ssh_password'];
        $local_storage_path = $plugin_settings['local_storage_path'];
        $convert_files      = $plugin_settings['convert_files'];
        $output_type = $plugin_settings['output_type'];
        $watermark_enabled   = (int) $plugin_settings['watermark_enabled'];
        $watermark_position  = $plugin_settings['watermark_position'];
        $watermark_padding   = (int) $plugin_settings['watermark_padding'];
        $keep_original   = (int) $plugin_settings['keep_original'];
        
        // load watermark
        if ($watermark_enabled == 1)
        {
            $watermark = $db->getRow("SELECT file_name, image_content FROM plugin_mediaconverter_watermark");
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
    $plugin_enabled     = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled     = $plugin_enabled != 1 ? 0 : 1;
    $max_conversions    = (int) $_REQUEST['max_conversions'];
    $video_size_w       = (int) $_REQUEST['video_size_w'];
    $video_size_h       = (int) $_REQUEST['video_size_h'];
    $output_messages    = (int) $_REQUEST['output_messages'];
    $ssh_host           = strtolower(trim($_REQUEST['ssh_host']));
    $ssh_user           = trim($_REQUEST['ssh_user']);
    $ssh_password       = trim($_REQUEST['ssh_password']);
    $local_storage_path = trim($_REQUEST['local_storage_path']);
    $convert_files      = str_replace(array(".", " "), "", strtolower(trim($_REQUEST['convert_files'])));
    $output_type = $_REQUEST['output_type'];
    $watermark_enabled   = (int) $_REQUEST['watermark_enabled'];
    $watermark_position  = $_REQUEST['watermark_position'];
    $watermark_padding   = (int) $_REQUEST['watermark_padding'];
    $keep_original   = (int) $_REQUEST['keep_original'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif ($plugin_enabled == 1)
    {
        if ($max_conversions == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_mediaconverter_max_concurrent_conversions_can_not_be_zero", "Max concurrent conversions can not be zero."));
        }
        elseif ($video_size_w == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_mediaconverter_set_max_video_width", "Please set the maximum video width."));
        }
        elseif ($video_size_h == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_mediaconverter_set_max_video_height", "Please set the maximum video height."));
        }
        elseif(strlen($convert_files) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_mediaconverter_set_file_extensions", "Please set the file types to convert. i.e. avi,3gp,ogg"));
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
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                       = array();
        $settingsArr['max_conversions']    = $max_conversions;
        $settingsArr['video_size_w']       = $video_size_w;
        $settingsArr['video_size_h']       = $video_size_h;
        $settingsArr['output_messages']    = $output_messages;
        $settingsArr['ssh_host']           = $ssh_host;
        $settingsArr['ssh_user']           = $ssh_user;
        $settingsArr['ssh_password']       = $ssh_password;
        $settingsArr['local_storage_path'] = $local_storage_path;
        $settingsArr['convert_files']      = $convert_files;
        $settingsArr['output_type'] = $output_type;
        $settingsArr['watermark_enabled']   = (int) $watermark_enabled;
        $settingsArr['watermark_position']  = $watermark_position;
        $settingsArr['watermark_padding']   = (int) $watermark_padding;
        $settingsArr['script_path_root']    = $script_path_root;
        $settingsArr['keep_original']       = $keep_original;
        $settings                          = json_encode($settingsArr);

        // update the user
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();
        
        // update watermark, delete exists
        $db->query("DELETE FROM plugin_mediaconverter_watermark");
        $dbInsert                = new DBObject("plugin_mediaconverter_watermark", array("file_name", "image_content"));
        $dbInsert->file_name     = $watermark_filename;
        $dbInsert->image_content = $watermark_contents;
        $dbInsert->insert();

        adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?se=1');
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>
<link rel="stylesheet" href="<?php echo PLUGIN_WEB_ROOT; ?>/mediaconverter/assets/css/admin_styles.css" type="text/css" media="screen" />
<script>
    $(function() {
        // formvalidator
        $("#userForm").validationEngine();
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
                            <p>Whether video conversion is enabled.</p>
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
                            <h3>Conversion Options</h3>
                            <p>The options for converting videos.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Convert Files:</label>
                                    <div class="input">
                                        <input id="convert_files" name="convert_files" type="text" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($convert_files); ?>"/>
                                        <br/>
                                        <div class="conversionOptionsText formFieldFix">The file types to convert, separated by a comma like this: "avi,3gp,mpg".<br/>Supported file types are: <?php echo str_replace(',',', ', DEFAULT_SUPPORTED_FILE_EXTENSIONS); ?></div>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Output Type:</label>
                                    <div class="input">
                                        <select name="output_type" id="output_type" class="medium validate[required]">
                                            <?php
                                            $options = array('mp4' => 'MP4 (default)', 'flv' => 'FLV', 'webm' => 'WEBM');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($output_type == $k)
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
                                    <label>Max Conversions:</label>
                                    <div class="input">
                                        <input type="text" name="max_conversions" id="max_conversions" class="small" value="<?php echo (int) adminFunctions::makeSafe($max_conversions); ?>"/>
                                        <br/>
                                        <div class="conversionOptionsText formFieldFix">The maximum concurrent conversions that can run at once.</div>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label>Max Video Size:</label>
                                    <div class="input">
                                        <input type="text" name="video_size_w" id="video_size_w" class="small" value="<?php echo (int) adminFunctions::makeSafe($video_size_w); ?>" placeholder="width"/> px
                                        &nbsp;by&nbsp;
                                        <input type="text" name="video_size_h" id="video_size_h" class="small" value="<?php echo (int) adminFunctions::makeSafe($video_size_h); ?>" placeholder="height"/> px
                                        <br/>
                                        <div class="conversionOptionsText formFieldFix">The maximum size to resize videos to. i.e. 640x320. Video will be contrained to their original proportions but within these maximum size limits.</div>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Keep Original File:</label>
                                    <div class="input">
                                        <select name="keep_original" id="keep_original" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($keep_original == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div class="conversionOptionsText formFieldFix">If 'yes', the converted file will be added to the users account as a new file, the original will be kept. Both will count towards account storage stats. Viewing either file within the media player will still show the converted version.</div>
                                    </div>
                                </div>
                                
                                <div class="clearfix">
                                    <label>Output Cron Messages:</label>
                                    <div class="input">
                                        <select name="output_messages" id="output_messages" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($output_messages == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div class="conversionOptionsText formFieldFix">Whether to output messages when running the cron script. Set to 'No' if you are unsure or running in live.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Watermark</h3>
                            <p>Whether to overlay a watermark on the video. Image should be a png file. If using transparency ensure it's at least 24bit for the best results.</p>
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
                            <h3>SSH Server Details</h3>
                            <p>If you're running the conversions on another server and use local file storage (not-FTP), you'll need to set the SSH details for this server below. This enables the conversion script to access the files in storage. These should be set as root or a user which has permissions to read/write the stored files.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix">
                                    <label>SSH Host:</label>
                                    <div class="input"><input id="ssh_host" name="ssh_host" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($ssh_host); ?>"/></div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>SSH User:</label>
                                    <div class="input"><input id="ssh_user" name="ssh_user" type="text" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($ssh_user); ?>"/></div>
                                </div>
                                <div class="clearfix">
                                    <label>SSH Password:</label>
                                    <div class="input"><input id="ssh_password" name="ssh_password" type="password" class="large validate[required]" value="<?php echo adminFunctions::makeSafe($ssh_password); ?>"/></div>
                                </div>
                                <div class="clearfix">
                                    <label>Default Storage Path:</label>
                                    <div class="input"><input id="local_storage_path" name="local_storage_path" type="text" class="xxlarge validate[required]" value="<?php echo adminFunctions::makeSafe($local_storage_path); ?>"/></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Install FFMPEG</h3>
                            <p>Details on how to install FFMPEG.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        FFMPEG is required in order for the converter to work. You'll need root access to your server to install this.<br/><br/>
                                        There's a handy utility by <a href="http://ffmpeginstaller.com" target="_blank">ffmpeginstaller.com</a> which sets up all the codecs and FFMPEG for you. Install via SSH as root user: (note: the install may take up to 15 minutes to complete)<br/><br/>
                                        <pre>wget http://mirror.ffmpeginstaller.com/old/scripts/ffmpeg8/ffmpeginstaller.8.0.tar.gz<br/>tar zxvf ffmpeginstaller.8.0.tar.gz<br/>cd ffmpeginstaller.8.0<br/>sh install.sh</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Cron Task</h3>
                            <p>Details of how to setup the converter.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <div style="margin: 8px;">
                                        The converter script should be set to run as a cron task every minute. It will only start a conversion if the amount of running conversions is less than 'Max Conversions' above.<br/><br/>
                                        The cron script is located here:<br/><br/>
                                        <code>
                                            <?php echo DOC_ROOT; ?>/plugins/mediaconverter/converter/convert.php
                                        </code>
                                        <br/><br/><br/>
                                        To execute it call it on the command line like this:<br/><br/>
                                        <code>
                                            php <?php echo DOC_ROOT; ?>/plugins/mediaconverter/converter/convert.php
                                        </code>
                                        <br/><br/><br/>
                                        See <a href="http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/" target="_blank">here for more information</a> on executing the script via a cron task.<br/><br/>
                                        The script can be moved to another server for better performance. To do this, copy the entire 'converter' folder to your server and set the database connection details at the top of convert.php. Assuming you have PHP, PDO module and FFMPEG, the conversion should work. Set 'Output Cron Messages' to 'Yes' above to debug on the command line, if it's not working as expected.
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
                                    <input type="submit" value="Submit" class="button blue"/>
                                    <input type="reset" value="Reset" class="button grey"/>
                                    <input type="reset" value="View Conversion Queue" class="button grey" onClick="window.location='view_queue.php';"/>
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