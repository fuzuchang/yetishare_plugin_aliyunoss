<?php
// pickup params
$file = $params['file'];
$Auth = $params['Auth'];

if ($file->statusId == 1)
{
    // load plugin details
    $pluginDetails  = pluginHelper::pluginSpecificConfiguration('mediaplayer');
    $pluginConfig   = $pluginDetails['config'];
    $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

    // load available extensions for this user
    $extType = 'non_media_types';
    if ($Auth->level_id == 1)
    {
        $extType = 'free_media_types';
    }
    elseif ($Auth->level_id > 1)
    {
        $extType = 'paid_media_types';
    }

    $ext = explode("|", $pluginSettings[$extType]);

    // check this is a video or audio, only 'mp4', 'webm', 'mp3', 'ogg' supported in this tab view
    if ((in_array(strtolower($file->extension), $ext) && (in_array(strtolower($file->extension), array('mp4', 'webm', 'mp3', 'ogg')))))
    {
        // get plugin object for subtitles later
        $pluginObj = pluginHelper::getInstance('mediaplayer');
        
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
        ?>
        <?php if ($file->extension == 'webm'): ?>
            <!-- for testing IE support with webm files -->
            <script type="text/javascript">
                function videoFail(vid)
                {
                    var ua = window.navigator.userAgent;
                    var msie = ua.indexOf("MSIE ");
                    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./))
                    {
                        //	some complications so that in IE9 we offer to install WebM
                        $('#videoFailIECheck').show();
                        $('#iEWebmSupport').show();
                        $('#jwPlayerContainer').remove();
                        $('#jplayer_container').remove();
                        $('#videoFailText').html(getMediaErrorString(vid));
                    }
                }

                function getMediaErrorString(vid)
                {
                    try {
                        switch (vid.error.code) {
                            case vid.error.MEDIA_ERR_ABORTED:
                                $('#iEWebmSupport').hide();
                                return 'You aborted the video playback.';
                            case vid.error.MEDIA_ERR_NETWORK:
                                $('#iEWebmSupport').hide();
                                return 'A network error caused the video download to fail part-way.';
                            case vid.error.MEDIA_ERR_DECODE:
                                return 'The video playback was aborted due to a corruption problem or because the video used features your browser did not support.';
                            case vid.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
                                return 'The video could not be loaded, either because the server or network failed or because the format is not supported.';
                            default:
                                return 'An unknown error occurred.';
                        }
                    }
                    catch (exp) {
                        return 'Your browser does not fully implement the HTML5 video element.';
                    }
                }
            </script>
            <div style="display: none;">
                <video src="<?php echo $file->generateDirectDownloadUrlForMedia(); ?>" controls preload="metadata" onerror="videoFail(this)"></video>
            </div>
        <?php endif; ?>
        
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
                        });
                                $('body').keyup(function(e) {
                            if (e.keyCode == 27)
                            {
                                $('#jplayer_container').data("jPlayer").restoreScreen();
                            }
                        });
                    },
                    swfPath: "<?php echo PLUGIN_WEB_ROOT; ?>/mediaplayer/assets/players/jplayer/Jplayer.swf",
                    supplied: "<?php echo $jPlayerCat; ?>",
                    solution: "html,flash",
                    size: {
                        width: "100%",
                        height: "<?php echo $mediaPlayer == 'html5_video' ? '530px' : ''; ?>",
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
        
        <div class="tab-pane" id="mediaplayer-preview" style="text-align: center;">
            <script>
                // remove any existing callbacks
                $('#fileDetailsModal').off('hidden.bs.modal');
                // stop any video and audio on close of the modal
                $('#fileDetailsModal').on('hidden.bs.modal', function () {
                    <?php if ($html5Player == 'jwplayer'): ?>
                    if(typeof($('#jwPlayerContainer')) != 'undefined')
                    {
                        jwplayer('jwPlayerContainer').stop();
                    }
                    <?php endif; ?>
                    <?php if ($html5Player == 'jplayer'): ?>
                    if(typeof($('#jplayer_container')) != 'undefined')
                    {
                        $('#jplayer_container').jPlayer("stop");
                    }
                    <?php endif; ?>
                });
            </script>

        <?php
        // append any plugin includes
        //pluginHelper::includeAppends('media_player_file_download_bottom_header.php', array('file' => $file, 'Auth' => $Auth));
        ?>

        <?php if ($mediaPlayer == 'html5_video'): ?>

            <div id="videoFailIECheck" style="display: none; vertical-align: middle; text-align: center; background-color: #dedede; padding: 30px;">
                <a id="iEWebmSupport" href="https://tools.google.com/dlpage/webmmf/" target="_blank">
                    <img style="border: none" alt="Install WebM support from webmproject.org" src="<?php echo PLUGIN_WEB_ROOT; ?>/mediaplayer/assets/img/Install-WebM-Support.png" />
                </a>
                <br/><br/><span id="videoFailText">WebM Support Required</span>
            </div>

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
    tracks: [<?php echo str_replace('\/', '/', implode(',', $subtitleArr)); ?>], <?php echo $pluginSettings['jwplayer_lights_out'] == 1 ? ("plugins: { '" . PLUGIN_WEB_ROOT . "/mediaplayer/assets/js/lightsout.js':{} },\n") : ''; ?>
    aspectratio: "16:9",
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
                    <div class="jp-type-single">
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
                            height: "120",
                            startparam: "start",
                            autostart: false
                    });
                    });
                            //]]>
                </script>
            <?php endif; ?>

        <?php endif; ?>

    </div>
  
    <?php
    }
}
?>