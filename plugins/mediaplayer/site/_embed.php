<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('mediaplayer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
$pluginObj = pluginHelper::getInstance('mediaplayer');

// do not allow if embed options are disabled
if ((int) $pluginSettings['show_embed'] == 0)
{
    // embedding disabled
    coreFunctions::output404();
}

// try to load the file object
$file = null;
if (isset($_REQUEST['u']))
{
    $file = file::loadByShortUrl($_REQUEST['u']);
}

/* load file details */
if (!$file)
{
    /* if no file found, redirect to home page */
    coreFunctions::redirect(WEB_ROOT . "/index." . SITE_CONFIG_PAGE_EXTENSION);
}

// embed size
if (isset($_REQUEST['w']))
{
    $w = (int) $_REQUEST['w'];
}
if (isset($_REQUEST['h']))
{
    $h = (int) $_REQUEST['h'];
}
define("EMBED_WIDTH", $w ? $w : $pluginSettings['embed_video_size_w']);
define("EMBED_HEIGHT", $h ? $h : $pluginSettings['embed_video_size_h']);

// load available extensions for non_media_types user
$ext = explode("|", $pluginSettings['non_media_types']);

// if this is a download request
if (!in_array(strtolower($file->extension), $ext))
{
    // file not permitted
    coreFunctions::output404();
}

// setup database
$db = Database::getDatabase();

// get player
$mediaPlayer = $pluginConfig['players'][strtolower($file->extension)];
if ($mediaPlayer == 'jplayer_video')
{
    $mediaPlayer = 'html5_video';
}
elseif ($mediaPlayer == 'jplayer_audio')
{
    $mediaPlayer = 'html5_audio';
}

// which html5 player to use
$html5Player = $pluginSettings['html5_player'];
if (strlen($html5Player) == 0)
{
    $html5Player = 'jplayer';
}

// prepare trimmed header
$headerTitle = $file->originalFilename;
if (strlen($headerTitle) > 60)
{
    $headerTitle = substr($headerTitle, 0, 55) . '...' . end(explode(".", $headerTitle));
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo validation::safeOutputToScreen(PAGE_NAME); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?></title>
        <meta name="description" content="<?php echo validation::safeOutputToScreen(PAGE_DESCRIPTION); ?>" />
        <meta name="keywords" content="<?php echo validation::safeOutputToScreen(PAGE_KEYWORDS); ?>" />
        <meta name="copyright" content="Copyright &copy; <?php echo date("Y"); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>" />
        <meta name="robots" content="all" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="Expires" content="-1" />
        <meta http-equiv="Pragma" content="no-cache" />
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/jquery-1.11.0.min.js"></script>
        <style>
            body, html
            {
                background-color: #fff;
				padding: 0px;
				margin: 0px;
            }
			
            .embedded div.jp-audio div.jp-type-single div.jp-progress, .embedded div.jp-audio div.jp-type-single div.jp-time-holder
            {
                width: 65%;
            }

            .embedded div.jp-audio div.jp-volume-bar
            {
                right: 36px;
                left: auto !important;
            }

            .embedded div.jp-audio div.jp-type-single a.jp-mute, div.jp-audio div.jp-type-single a.jp-unmute
            {
                margin-left: <?php echo EMBED_WIDTH - 200; ?>px;
            }
			
			.embedded .jwlogo
			{
				display: none; /* hidden as sits over volume on mp3s */
			}
        </style>
    </head>

    <body style="width: <?php echo EMBED_WIDTH; ?>px;">
        <div class="embedded">

            <?php if (in_array($mediaPlayer, array('html5_video', 'html5_audio'))): ?>
                <?php
                $jPlayerCat = $file->extension;
                $jwPlayerCat = $file->extension;
                switch ($file->extension)
                {
                    case 'mp4':
                        $jPlayerCat = 'm4v';
                        break;
                    case 'm4v':
                        $jwPlayerCat = 'mp4';
                        break;
                    case 'ogg':
                        $jwPlayerCat = 'webm';
                        break;
                    case 'webm':
                        $jPlayerCat = 'webmv';
                        break;
                }
                ?>

                <?php if ($html5Player == 'jplayer'): ?>
                    <!-- jplayer -->
                    <link href="<?php echo PLUGIN_WEB_ROOT; ?>/mediaplayer/assets/players/jplayer/skin/blue.monday/jplayer.blue.monday.css" rel="stylesheet" type="text/css" />
                    <script type="text/javascript" src="<?php echo PLUGIN_WEB_ROOT; ?>/mediaplayer/assets/players/jplayer/jquery.jplayer.min.js"></script>
                    <!-- end jplayer -->

                    <script type="text/javascript">
                        //<![CDATA[
                        $(document).ready(function() {
                            $("#jplayer_container").jPlayer({
                                ready: function() {
                                    $(this).jPlayer("setMedia", {
        <?php echo $jPlayerCat; ?>: "<?php echo $file->generateDirectDownloadUrlForMedia(); ?>"
                                    })<?php echo $pluginSettings['auto_play'] == 1 ? '.jPlayer("play")' : ''; ?>;

                                    $('body').keyup(function(e) {
                                        if (e.keyCode == 27)
                                        {
                                            $('#jplayer_container').data("jPlayer").restoreScreen();
                                        }
                                    });
                                },
                                swfPath: "<?php echo PLUGIN_WEB_ROOT; ?>/mediaplayer/assets/players/jplayer",
                                supplied: "<?php echo $jPlayerCat; ?>",
                                solution: "html, flash",
                                size: {
                                    width: "100%",
                                    height: "<?php echo $mediaPlayer == 'html5_video' ? EMBED_HEIGHT . 'px' : ''; ?>",
                                    cssClass: "<?php echo $mediaPlayer == 'html5_video' ? 'jp-video-360p' : 'jp-audio'; ?>"
                                }
                            });
                        });
                        //]]>
                    </script>
                <?php endif; ?>

                <?php if ($html5Player == 'jwplayer'): ?>
                    <!-- jwplayer -->
                    <script type="text/javascript" src="<?php echo PLUGIN_WEB_ROOT; ?>/mediaplayer/assets/players/jwplayer/jwplayer.js"></script>
                    <?php
                    if (isset($pluginSettings['html5_player_license_key']) && strlen($pluginSettings['html5_player_license_key']))
                    {
                        echo '<script type="text/javascript">jwplayer.key="' . validation::safeOutputToScreen($pluginSettings['html5_player_license_key']) . '";</script>';
                        echo "\n";
                    }
                    ?>
                    <!-- end jwplayer -->
                <?php endif; ?>
            <?php endif; ?>

            <div style="width: <?php echo EMBED_WIDTH; ?>px;">

                <?php if ($mediaPlayer == 'html5_video'): ?>

                    <?php if ($html5Player == 'jplayer'): ?>
                        <div id="jp_container_1" class="jp-video jp-video-360p">
                            <div class="jp-type-single">
                                <div id="jplayer_container" class="jp-jplayer"></div>
                                <div class="jp-gui">
                                    <div class="jp-video-play">
                                        <a href="javascript:;" class="jp-video-play-icon" tabindex="1">play</a>
                                    </div>
                                    <div class="jp-interface">
                                        <div class="jp-progress">
                                            <div class="jp-seek-bar">
                                                <div class="jp-play-bar"></div>
                                            </div>
                                        </div>
                                        <div class="jp-current-time"></div>
                                        <div class="jp-duration"></div>
                                        <div class="jp-controls-holder">
                                            <ul class="jp-controls">
                                                <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                                                <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
                                                <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                                                <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
                                                <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
                                                <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
                                            </ul>
                                            <div class="jp-volume-bar">
                                                <div class="jp-volume-bar-value"></div>
                                            </div>
                                            <ul class="jp-toggles">
                                                <li><a href="javascript:;" class="jp-full-screen" tabindex="1" title="full screen">full screen</a></li>
                                                <li><a href="javascript:;" class="jp-restore-screen" tabindex="1" title="restore screen">restore screen</a></li>
                                                <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
                                                <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
                                            </ul>
                                        </div>
                                        <div class="jp-title">
                                            <ul>
                                                <li><?php echo validation::safeOutputToScreen($file->originalFilename); ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="jp-no-solution">
                                    <span>Update Required</span>
                                    To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($html5Player == 'jwplayer'): ?>
                        <?php
                        // check for subtitles
                        $subtitleArr = $pluginObj->getSubtitlesForJWPlayer($file);
                        ?>
                        <div id="jwPlayerContainer">Loading media...</div>
                        <script type="text/javascript">
                            //<![CDATA[
                            $(document).ready(function() {
                                jwplayer("jwPlayerContainer").setup({
                                    file: "<?php echo $file->generateDirectDownloadUrlForMedia(); ?>",
									type: "<?php echo $jwPlayerCat; ?>",
                                    title: "<?php echo validation::safeOutputToScreen($file->originalFilename); ?>",
                                    width: "100%",
                                    startparam: "start",
                                    abouttext: '<?php echo str_replace("'", "\'", SITE_CONFIG_SITE_NAME); ?>',
                                    aboutlink: '<?php echo str_replace("'", "\'", $file->getFullShortUrl()); ?>',
                                    sharing: {
                                        link: '<?php echo str_replace("'", "\'", $file->getFullShortUrl()); ?>'
                                    },
                                    logo: {
                                        file: '<?php echo SITE_IMAGE_PATH; ?>/main_logo.jpg',
                                        link: '<?php echo coreFunctions::getCoreSitePath(); ?>',
                                        linktarget: '_blank',
                                        hide: 'false'
                                    },
                                    tracks: [<?php echo implode(',', $subtitleArr); ?>],
                                    height: "<?php echo EMBED_HEIGHT + 96; ?>",
                                    autostart: <?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>,
									image: "<?php echo file::getIconPreviewImageUrl((array)$file, false, 160, false, 640, 320); ?>"
                                });
                            });
                            //]]>
                        </script>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($mediaPlayer == 'html5_audio'): ?>

                    <?php if ($html5Player == 'jplayer'): ?>
                        <div id="jplayer_container" class="jp-jplayer"></div>
                        <div id="jp_container_1" class="jp-audio">
                            <div class="jp-type-single-embeded jp-type-single">
                                <div class="jp-gui jp-interface">
                                    <ul class="jp-controls">
                                        <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                                        <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
                                        <li><a href="javascript:;" class="jp-stop" tabindex="1">stop</a></li>
                                        <li><a href="javascript:;" class="jp-mute" tabindex="1" title="mute">mute</a></li>
                                        <li><a href="javascript:;" class="jp-unmute" tabindex="1" title="unmute">unmute</a></li>
                                        <li><a href="javascript:;" class="jp-volume-max" tabindex="1" title="max volume">max volume</a></li>
                                    </ul>
                                    <div class="jp-progress">
                                        <div class="jp-seek-bar">
                                            <div class="jp-play-bar"></div>
                                        </div>
                                    </div>
                                    <div class="jp-volume-bar">
                                        <div class="jp-volume-bar-value"></div>
                                    </div>
                                    <div class="jp-time-holder">
                                        <div class="jp-current-time"></div>
                                        <div class="jp-duration"></div>

                                        <ul class="jp-toggles">
                                            <li><a href="javascript:;" class="jp-repeat" tabindex="1" title="repeat">repeat</a></li>
                                            <li><a href="javascript:;" class="jp-repeat-off" tabindex="1" title="repeat off">repeat off</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="jp-title">
                                    <ul>
                                        <li><?php echo validation::safeOutputToScreen($file->originalFilename); ?></li>
                                    </ul>
                                </div>
                                <div class="jp-no-solution">
                                    <span>Update Required</span>
                                    To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($html5Player == 'jwplayer'): ?>
                        <div id="jwPlayerContainer">Loading media...</div>
                        <script type="text/javascript">
                            //<![CDATA[
                            $(document).ready(function() {
                                jwplayer("jwPlayerContainer").setup({
                                    file: "<?php echo $file->generateDirectDownloadUrlForMedia(); ?>",
									type: "<?php echo $jwPlayerCat; ?>",
                                    title: "<?php echo validation::safeOutputToScreen($file->originalFilename); ?>",
                                    width: "100%",
                                    startparam: "start",
                                    height: "30",
                                    autostart: <?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>
                                });
                            });
                            //]]>
                        </script>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($mediaPlayer == 'divx_web_player'): ?>
                    <object classid="clsid:67DABFBF-D0AB-41fa-9C46-CC0F21721616"
                            width="100%" height="530"
                            codebase="http://go.divx.com/plugin/DivXBrowserPlugin.cab">
                        <param name="src" value="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>"/>
                        <param name="autoPlay" value="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>"/>

                        <embed
                            type="video/divx"
                            src="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>"
                            width="100%" height="530"
                            autoPlay="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>"
                            pluginspage="http://go.divx.com/plugin/download/">
                        </embed>
                    </object>
                <?php endif; ?>

                <?php if ($mediaPlayer == 'windows_media_player'): ?>
                    <object classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" id="player" width="100%" height="530">
                        <param name="url" value="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>" />
                        <param name="src" value="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>" />
                        <param name="showcontrols" value="true" />
                        <param name="autostart" value="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>" />
                        <!--[if !IE]>-->
                        <object type="video/x-ms-wmv" data="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>" width="100%" height="530">
                            <param name="src" value="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>" />
                            <param name="controller" value="true" />
                            <param name="autostart" value="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>" />
                        </object>
                        <!--<![endif]-->
                    </object>

                <?php endif; ?>

            </div>
        </div>
    </body>
</html>
