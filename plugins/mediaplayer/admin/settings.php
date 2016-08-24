<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// prepare payment days
$days = array();
for ($i = 1; $i <= 28; $i++)
{
    $date     = strtotime(date('Y-m-') . str_pad($i, 2, "0", STR_PAD_LEFT) . ' 00:00:00');
    $days[$i] = date('jS', $date);
}

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('mediaplayer');
$pluginConfig  = $pluginDetails['config'];
$filePlayers   = $pluginConfig['players'];

// prepare variables
$plugin_enabled           = (int) $plugin['plugin_enabled'];
$non_media_types          = array_keys($filePlayers);
$free_media_types         = array_keys($filePlayers);
$paid_media_types         = array_keys($filePlayers);
$show_download_link       = 1;
$auto_play                = 1;
$show_embed               = 1;
$embed_video_size_w       = 640;
$embed_video_size_h       = 320;
$html5_player_license_key = '';
$jwplayer_lights_out      = 0;
$ignore_download_timer    = 0;
$html5_player             = 'jwplayer';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $non_media_types          = explode("|", $plugin_settings['non_media_types']);
        $free_media_types         = explode("|", $plugin_settings['free_media_types']);
        $paid_media_types         = explode("|", $plugin_settings['paid_media_types']);
        $show_download_link       = (int) $plugin_settings['show_download_link'];
        $auto_play                = (int) $plugin_settings['auto_play'];
        $show_embed               = (int) $plugin_settings['show_embed'];
        $embed_video_size_w       = (int) $plugin_settings['embed_video_size_w'];
        $embed_video_size_h       = (int) $plugin_settings['embed_video_size_h'];
        $html5_player             = $plugin_settings['html5_player'];
        $html5_player_license_key = $plugin_settings['html5_player_license_key'];
        $jwplayer_lights_out      = (int) $plugin_settings['jwplayer_lights_out'];
        $ignore_download_timer    = (int) $plugin_settings['ignore_download_timer'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled           = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled           = $plugin_enabled != 1 ? 0 : 1;
    $non_media_types          = $_REQUEST['non_media_types'];
    $free_media_types         = $_REQUEST['free_media_types'];
    $paid_media_types         = $_REQUEST['paid_media_types'];
    $show_download_link       = (int) $_REQUEST['show_download_link'];
    $auto_play                = (int) $_REQUEST['auto_play'];
    $show_embed               = (int) $_REQUEST['show_embed'];
    $embed_video_size_w       = (int) $_REQUEST['embed_video_size_w'];
    $embed_video_size_h       = (int) $_REQUEST['embed_video_size_h'];
    $html5_player             = trim($_REQUEST['html5_player']);
    $html5_player_license_key = trim($_REQUEST['html5_player_license_key']);
    $jwplayer_lights_out      = (int) $_REQUEST['jwplayer_lights_out'];
    $ignore_download_timer    = (int) $_REQUEST['ignore_download_timer'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif ($show_embed == 1)
    {
        if ((int) $embed_video_size_w == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_media_player_set_video_width", "Please set a width for the embed code. Recommend using 640."));
        }
        elseif ((int) $embed_video_size_h == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_media_player_set_video_height", "Please set a height for the embed code. Recommend using 320."));
        }
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                             = array();
        $settingsArr['non_media_types']          = implode("|", $non_media_types);
        $settingsArr['free_media_types']         = implode("|", $free_media_types);
        $settingsArr['paid_media_types']         = implode("|", $paid_media_types);
        $settingsArr['show_download_link']       = (int) $show_download_link;
        $settingsArr['auto_play']                = (int) $auto_play;
        $settingsArr['show_embed']               = (int) $show_embed;
        $settingsArr['embed_video_size_w']       = (int) $embed_video_size_w;
        $settingsArr['embed_video_size_h']       = (int) $embed_video_size_h;
        $settingsArr['html5_player']             = $html5_player;
        $settingsArr['html5_player_license_key'] = $html5_player_license_key;
        $settingsArr['jwplayer_lights_out']      = (int) $jwplayer_lights_out;
        $settingsArr['ignore_download_timer']    = (int) $ignore_download_timer;
        $settings                                = json_encode($settingsArr);

        // update the user
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

<script>
    $(function() {
        // formvalidator
        $("#userForm").validationEngine();
        showHidePlayerOptions();
    });

    function showHidePlayerOptions()
    {
        $('.player_options').hide(0, function() {
            if ($('#html5_player').val() == 'jwplayer')
            {
                $('.jwplayer_license_key').show();
                $('.jwplayer_lights_out').show();
            }
        });
    }
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
                            <p>Whether the media player is available.</p>
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
                            <h3>Active Media Types</h3>
                            <p>Select which media to stream by default for which users. Any which aren't selected will just be sent the file as a download. For free users, the video/audio stream is shown after the download countdown, if set.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Non Registered Users:</label>
                                    <div class="input">
                                        <select multiple name="non_media_types[]" id="non_media_types" class="xlarge">
                                            <?php
                                            foreach ($filePlayers AS $ext => $filePlayer)
                                            {
                                                echo '<option value="' . $ext . '"';
                                                if (in_array($ext, $non_media_types))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $ext . ' (' . $filePlayer . ')</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div class="mediaPlayerText formFieldFix">Hold ctrl &amp; click to select multiple.</div>
                                    </div>
                                </div>

                                <div class="clearfix">
                                    <label>Free Accounts:</label>
                                    <div class="input">
                                        <select multiple name="free_media_types[]" id="free_media_types" class="xlarge">
                                            <?php
                                            foreach ($filePlayers AS $ext => $filePlayer)
                                            {
                                                echo '<option value="' . $ext . '"';
                                                if (in_array($ext, $free_media_types))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $ext . ' (' . $filePlayer . ')</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div class="mediaPlayerText formFieldFix">Hold ctrl &amp; click to select multiple.</div>
                                    </div>
                                </div>

                                <div class="clearfix alt-highlight">
                                    <label>Paid Accounts:</label>
                                    <div class="input">
                                        <select multiple name="paid_media_types[]" id="paid_media_types" class="xlarge">
                                            <?php
                                            foreach ($filePlayers AS $ext => $filePlayer)
                                            {
                                                echo '<option value="' . $ext . '"';
                                                if (in_array($ext, $paid_media_types))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $ext . ' (' . $filePlayer . ')</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div class="mediaPlayerText formFieldFix">Hold ctrl &amp; click to select multiple.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>HTML5 Player</h3>
                            <p>Which player to use for HTML5 playable video/audio. Note: JWPlayer is only permitted for non-commercial sites, you'll need a license from <a href="http://www.jwplayer.com">JWPlayer</a> for other types of websites.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>HTML5 Player:</label>
                                    <div class="input">
                                        <select name="html5_player" id="html5_player" class="xxlarge" onChange="showHidePlayerOptions();
        return false;">
                                            <?php
                                            $playerOptions = array('jwplayer' => 'JWPlayer - (Recommended - free only for non-commercial sites, license needed for others)', 'jplayer'  => 'JPlayer (free for all types of sites including commercial)');
                                            foreach ($playerOptions AS $k => $playerOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($html5_player == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $playerOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix player_options jwplayer_license_key">
                                    <label>JWPlayer License Key:<br/>(if commercial site)</label>
                                    <div class="input">
                                        <input type="text" name="html5_player_license_key" id="html5_player_license_key" class="large" value="<?php echo adminFunctions::makeSafe($html5_player_license_key); ?>"/>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight player_options jwplayer_lights_out">
                                    <label>Lights Out On Play:</label>
                                    <div class="input">
                                        <select name="jwplayer_lights_out" id="jwplayer_lights_out" class="large">
                                            <?php
                                            $downloadOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($downloadOptions AS $k => $downloadOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($jwplayer_lights_out == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $downloadOption . '</option>';
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
                            <p>Show download link on streaming page, auto play setting and whether to show embed code.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Show download link:</label>
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
                                    <label>Auto Play Media:</label>
                                    <div class="input">
                                        <select name="auto_play" id="auto_play" class="large">
                                            <?php
                                            $options = array(0 => 'No', 1 => 'Yes');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($auto_play == $k)
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
                                    <label>Show Embed Code:</label>
                                    <div class="input">
                                        <select name="show_embed" id="show_embed" class="large">
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
                                            If set to 'yes', the download timer will not be shown for videos. Even for free/non-users users.
                                        </div>
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Embedded Video Size:</label>
                                    <div class="input">
                                        <input type="text" name="embed_video_size_w" id="embed_video_size_w" class="small" value="<?php echo (int) adminFunctions::makeSafe($embed_video_size_w); ?>" placeholder="width"/> px
                                        &nbsp;by&nbsp;
                                        <input type="text" name="embed_video_size_h" id="embed_video_size_h" class="small" value="<?php echo (int) adminFunctions::makeSafe($embed_video_size_h); ?>" placeholder="height"/> px
                                        <br/>
                                        <div class="mediaPlayerText formFieldFix">This is the size of the iframe and hence video when embedded on an external site.</div>
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