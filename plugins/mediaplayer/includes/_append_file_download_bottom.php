<?php
// try to load the file object
$file = null;
if (isset($_REQUEST['_page_url']))
{
    // only keep the initial part if there's a forward slash
    $shortUrl = current(explode("/", $_REQUEST['_page_url']));
    $file = file::loadByShortUrl($shortUrl);
}

/* load file details */
if (!$file)
{
    /* if no file found, redirect to home page */
    coreFunctions::redirect(WEB_ROOT . "/index." . SITE_CONFIG_PAGE_EXTENSION);
}

// for page footer link
if (!defined('REPORT_URL'))
{
    define('REPORT_URL', $file->getFullShortUrl());
}

// check for linked files
$originalFileObj = null;
$childFileObj = null;
if(property_exists($file, 'linkedFileId'))
{
	// get parent file object if this is the linked child, used for later download links
	if($file->linkedFileId != null)
	{
		$currentFileObj = file::loadById((int)$file->linkedFileId);
		if($currentFileObj)
		{
			$originalFileObj = $currentFileObj;
		}
	}
	else
	{
		// setup database
        $db = Database::getDatabase();
		
		// get child file object if this is a 'parent'
		$childFile = (int)$db->getValue('SELECT id FROM file WHERE linkedFileId = '.(int)$file->id.' AND statusId = 1 LIMIT 1');
		if($childFile)
		{
			$currentFileObj = file::loadById((int)$childFile);
			if($currentFileObj)
			{
				$childFileObj = $currentFileObj;
			}
		}
	}
}
if($originalFileObj === null)
{
	$originalFileObj = $file;
}
if($childFileObj === null)
{
	$childFileObj = $file;
}

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('mediaplayer');
$pluginConfig = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// Initialize current user
$Auth = Auth::getAuth();
define("CONTROLS_HEIGHT", 95);

// load available extensions for this user
$extType = 'non_media_types';
if (($Auth->level == 'free user') && ($Auth->loggedIn == true))
{
    $extType = 'free_media_types';
}
elseif (($Auth->level == 'paid user') || ($Auth->level == 'admin'))
{
    $extType = 'paid_media_types';
}
$ext = explode("|", $pluginSettings[$extType]);

// if this is a download request
if ((!isset($_REQUEST['dt'])) && (in_array(strtolower($originalFileObj->extension), $ext)))
{
    // get plugin object for subtitles later
    $pluginObj = pluginHelper::getInstance('mediaplayer');

    // setup database
    $db = Database::getDatabase();

    // get player
    $mediaPlayer = $pluginConfig['players'][strtolower($childFileObj->extension)];
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
    $headerTitle = $originalFileObj->originalFilename;
    if (strlen($headerTitle) > 60)
    {
        $headerTitle = substr($headerTitle, 0, 55) . '...' . end(explode(".", $headerTitle));
    }

    // setup page
    define("PAGE_NAME", $originalFileObj->originalFilename . ' ' . t("media_player_plugin_watch_page_name", "Watch"));
    define("PAGE_DESCRIPTION", t("media_player_plugin_page_description", "Watch or listen to ") . ' ' . $originalFileObj->originalFilename);
    define("PAGE_KEYWORDS", strtolower($originalFileObj->originalFilename) . t("media_player_plugin_meta_keywords", ", watch, listen, file, upload, download, site"));

    // include header
    require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
    ?>
    
    <style>
    .jwlogo
	{
		display: none; /* hidden as sits over volume on mp3s */
	}
    </style>
    
    <?php if ($childFileObj->extension == 'webm'): ?>
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
            <video src="<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>" controls preload="metadata" onerror="videoFail(this)"></video>
        </div>
    <?php endif; ?>

    <?php if (in_array($mediaPlayer, array('html5_video', 'html5_audio'))): ?>
        <?php
        $jPlayerCat = $childFileObj->extension;
        $jwPlayerCat = $childFileObj->extension;
        switch ($childFileObj->extension)
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
            <?php echo $jPlayerCat; ?>: "<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>"
                    })<?php echo $pluginSettings['auto_play'] == 1 ? '.jPlayer("play")' : ''; ?>;
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
			
			<script>
			var trackJWTime = false;
			var jwTimer = null;
			var timerPeriod = 5; // after how many seconds to check for the video state again
			var totalPlayedTime = 0;
			var mediaTotalDuration = -1;
			<?php
			if(pluginHelper::pluginEnabled('rewards'))
			{
				$rewardObj = pluginHelper::getInstance('rewards');
				$ackPercentage = (int)$rewardObj->settings['ppd_media_percentage'];
				if(($ackPercentage >= 1) && ($ackPercentage <= 99))
				{
					echo "var ackPercentage = ".$ackPercentage.";\n";
					echo "var trackJWTime = true;\n";
				}
			}
			?>
			</script>
			
            <!-- end jwplayer -->
        <?php endif; ?>

    <?php endif; ?>

    <?php
    // append any plugin includes
    pluginHelper::includeAppends('media_player_file_download_bottom_header.php', array('file' => $originalFileObj, 'Auth' => $Auth));
    ?>

        <div class="contentPageWrapper">
            <div class="pageSectionMainFull ui-corner-all">
                <div class="pageSectionMainInternal">
                    <div id="pageHeader" class="first-header">
                        <h2><?php echo validation::safeOutputToScreen($headerTitle); ?></h2>
                    </div>
                    <div>

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
                                                        <li><?php echo validation::safeOutputToScreen($originalFileObj->originalFilename); ?></li>
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
                                $subtitleArr = $pluginObj->getSubtitlesForJWPlayer($originalFileObj);
                                ?>
                                <div id="jwPlayerContainer">Loading media...</div>
                                <script type="text/javascript">
            //<![CDATA[
            $(document).ready(function() {
				<?php $downloadUrlForMedia = $childFileObj->generateDirectDownloadUrlForMedia(); ?>
				jwplayer("jwPlayerContainer").setup({
					file: "<?php echo $downloadUrlForMedia; ?>",
					type: "<?php echo $jwPlayerCat; ?>",
					title: "<?php echo validation::safeOutputToScreen($originalFileObj->originalFilename); ?>",
					width: "100%",
					startparam: "start",
					abouttext: '<?php echo str_replace("'", "\'", SITE_CONFIG_SITE_NAME); ?>',
					aboutlink: '<?php echo str_replace("'", "\'", $originalFileObj->getFullShortUrl()); ?>',
					sharing: {
					link: '<?php echo str_replace("'", "\'", $originalFileObj->getFullShortUrl()); ?>'
					},
					logo: {
					file: '<?php echo SITE_IMAGE_PATH; ?>/main_logo.jpg',
							link: '<?php echo coreFunctions::getCoreSitePath(); ?>',
							linktarget: '_blank',
							hide: 'false'
					},
					tracks: [<?php echo str_replace('\/', '/', implode(',', $subtitleArr)); ?>], <?php echo $pluginSettings['jwplayer_lights_out'] == 1 ? ("plugins: { '" . PLUGIN_WEB_ROOT . "/mediaplayer/assets/js/lightsout.js':{} },\n") : ''; ?>
					aspectratio: "16:9",
					image: "<?php echo file::getIconPreviewImageUrl((array)$originalFileObj, false, 160, false, 640, 320); ?>"  
				});

				// track total played in seconds
				jwTimer = setInterval(trackJWPlayedTime, (timerPeriod*1000));
            });
			
			function trackJWPlayedTime()
			{
				if(trackJWTime == false)
				{
					// stop any future attempts of the timer
					clearInterval(jwTimer);
					return false;
				}
				
				if(jwplayer("jwPlayerContainer").getState() == 'PLAYING')
				{
					// track actual played time
					totalPlayedTime = totalPlayedTime+timerPeriod;
				}

				if(mediaTotalDuration == -1)
				{
					// get total video length
					mediaTotalDuration = jwplayer("jwPlayerContainer").getDuration();
				}
				else
				{
					// calculate percentage
					percent = 0;
					if(totalPlayedTime > 0)
					{
						percent = (parseInt(totalPlayedTime)/parseInt(mediaTotalDuration))*100;
					}
					
					// should we log yet?
					if(percent >= ackPercentage)
					{
						// clear for next time
						trackJWTime = false;
						
						// log PPD
						$.ajax({
							method: "POST",
							url: "<?php echo PLUGIN_WEB_ROOT; ?>/rewards/site/_log_media_percentage.php",
							data: { fileId: "<?php echo $childFileObj->id; ?>", tracker: "<?php echo base64_encode($downloadUrlForMedia); ?>", percent: percent }
						});
					}
				}
			}
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
                                                <li><?php echo validation::safeOutputToScreen($originalFileObj->originalFilename); ?></li>
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
											file: "<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>",
                                            type: "<?php echo $jwPlayerCat; ?>",
                                            title: "<?php echo validation::safeOutputToScreen($childFileObj->originalFilename); ?>",
                                            width: "100%",
                                            height: "30",
                                            startparam: "start",
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
                                <param name="src" value="<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>"/>
                                <param name="autoPlay" value="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>"/>

                                <embed
                                    type="video/divx"
                                    src="<?php echo $childFileObj->generateDirectDownloadUrl(); ?>"
                                    width="100%" height="530"
                                    autoPlay="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>"
                                    pluginspage="http://go.divx.com/plugin/download/">
                                </embed>
                            </object>
                        <?php endif; ?>

                        <?php if ($mediaPlayer == 'windows_media_player'): ?>
                            <object classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" id="player" width="100%" height="530">
                                <param name="url" value="<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>" />
                                <param name="src" value="<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>" />
                                <param name="showcontrols" value="true" />
                                <param name="autostart" value="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>" />
                                <!--[if !IE]>-->
                                <object type="video/x-ms-wmv" data="<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>" width="100%" height="530">
                                    <param name="src" value="<?php echo $childFileObj->generateDirectDownloadUrlForMedia(); ?>" />
                                    <param name="controller" value="true" />
                                    <param name="autostart" value="<?php echo $pluginSettings['auto_play'] == 1 ? 'true' : 'false'; ?>" />
                                </object>
                                <!--<![endif]-->
                            </object>

                        <?php endif; ?>

                    </div>
                    <div class="clear"><!-- --></div>

                    <div id="pageHeader" style="padding-top: 12px;">
                        <h2><?php echo UCWords(t('file_details', 'file details')); ?></h2>
                    </div>
                    <div>
                        <table class="accountStateTable table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('filename', 'filename')); ?>:
                                    </td>
                                    <td>
                                        <?php echo validation::safeOutputToScreen($originalFileObj->originalFilename); ?>&nbsp;&nbsp;
                                        <?php if ((int) $pluginSettings['show_download_link'] == 1): ?>
                                            <a href="<?php echo $originalFileObj->generateDirectDownloadUrl(); ?>" target="_blank">(<?php echo t('download', 'download'); ?>)</a>
                                        <?php endif; ?>
                                        <?php if ($Auth->id != $originalFileObj->userId): ?>
                                            &nbsp;&nbsp;<a href="<?php echo CORE_PAGE_WEB_ROOT . '/account_copy_file.php?f=' . $originalFileObj->shortUrl; ?>">(<?php echo t('copy_into_your_account', 'copy file'); ?>)</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('filesize', 'filesize')); ?>:
                                    </td>
                                    <td>
                                        <?php echo coreFunctions::formatSize($originalFileObj->fileSize); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"><!-- --></div>

                    <?php if ((int) $pluginSettings['show_embed'] == 1): ?>
                        <div id="pageHeader" style="padding-top: 12px;">
                            <h2><?php echo t("embed_code", "embed code"); ?></h2>
                        </div>
                        <div>
                            <table class="accountStateTable table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <td class="first share-file-table-header">
                                            <?php echo t('embed_video', 'Embed Video'); ?>:
                                        </td>
                                        <td class="htmlCode ltrOverride">
                                            <?php
                                            // embed size
                                            $embedWidth = (int) $pluginSettings['embed_video_size_w'];
                                            $embedHeight = (int) $pluginSettings['embed_video_size_h'] + (int) CONTROLS_HEIGHT + 1;
                                            if ($mediaPlayer == 'html5_audio')
                                            {
                                                $embedHeight = '109';
                                            }
                                            echo htmlentities('<iframe src="' . PLUGIN_WEB_ROOT . '/mediaplayer/site/_embed.php?u=' . $originalFileObj->shortUrl . '&w=' . $pluginSettings['embed_video_size_w'] . '&h=' . $pluginSettings['embed_video_size_h'] . '" frameborder="0" scrolling="no" style="width: ' . $embedWidth . 'px; height: ' . $embedHeight . 'px; overflow: hidden;" webkitAllowFullScreen="true" mozallowfullscreen="true" allowFullScreen="true"></iframe>');
                                            ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="clear"><!-- --></div>
                    <?php endif; ?>

                    <div id="pageHeader" style="padding-top: 12px;">
                        <h2><?php echo t("download_urls", "download urls"); ?></h2>
                    </div>
                    <div>
                        <table class="accountStateTable table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo t('html_code', 'HTML Code'); ?>:
                                    </td>
                                    <td class="htmlCode ltrOverride">
                                        <?php echo $originalFileObj->getHtmlLinkCode(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('forum_code', 'forum code')); ?>
                                    </td>
                                    <td class="htmlCode ltrOverride">
                                        <?php echo $originalFileObj->getForumLinkCode(); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"><!-- --></div>

                    <div id="pageHeader" style="padding-top: 12px;">
                        <h2><?php echo t("share", "share"); ?></h2>
                    </div>
                    <div>
                        <table class="accountStateTable table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('share_file', 'share file')); ?>:
                                    </td>
                                    <td>
                                        <!-- AddThis Button BEGIN -->
                                        <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                                            <a class="addthis_button_preferred_1" addthis:url="<?php echo $originalFileObj->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $originalFileObj->originalFilename); ?>"></a>
                                            <a class="addthis_button_preferred_2" addthis:url="<?php echo $originalFileObj->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $originalFileObj->originalFilename); ?>"></a>
                                            <a class="addthis_button_preferred_3" addthis:url="<?php echo $originalFileObj->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $originalFileObj->originalFilename); ?>"></a>
                                            <a class="addthis_button_preferred_4" addthis:url="<?php echo $originalFileObj->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $originalFileObj->originalFilename); ?>"></a>
                                            <a class="addthis_button_compact" addthis:url="<?php echo $originalFileObj->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $originalFileObj->originalFilename); ?>"></a>
                                            <a class="addthis_counter addthis_bubble_style" addthis:url="<?php echo $originalFileObj->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $originalFileObj->originalFilename); ?>"></a>
                                        </div>
                                        <script type="text/javascript" src="<?php echo _CONFIG_SITE_PROTOCOL; ?>://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4f10918d56581527"></script>
                                        <!-- AddThis Button END -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"><!-- --></div>
					<?php pluginHelper::includeAppends('file_download_bottom_extra.php', array('file' => $file)); ?>
            </div>
        </div>
    </div>
    <div class="clear"></div>

    <?php
    // include footer
    require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
    exit;
}
elseif ((isset($_REQUEST['dt'])) && (in_array(strtolower($originalFileObj->extension), $ext)))
{
    $directDownloadUrl = $originalFileObj->generateDirectDownloadUrl();
    coreFunctions::redirect($directDownloadUrl);
}
