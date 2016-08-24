<script>
    <!--
    var milisec = 0;
    var seconds = <?php echo (int)$additionalSettings['download_wait']; ?>;

    function display()
    {
        $('.btn-free').hide();
        $('.download-timer').show();
        if (seconds == 0)
        {
            $('.download-timer').html("<a href='<?php echo validation::safeOutputToScreen($file->getNextDownloadPageLink()); ?>'><?php echo pluginHelper::pluginEnabled('mediaplayer') ? t("download_view_now", "download/view now") : t("download_now", "download now"); ?></a>");
        }
        else
        {
            $('.download-timer-seconds').html(seconds);
        }
        seconds--;
    }

    $(document).ready(function() {
        $('.download-timer-seconds').html(<?php echo (int)$additionalSettings['download_wait']; ?>);
        countdownTimer = setInterval('display()', 1000);
    });
    -->
</script>

<?php
if(isset($downloadPage['additional_javascript_code']))
{
    echo $downloadPage['additional_javascript_code'];
}
?>

<?php
// figure out upgrade url
$auth = Auth::getAuth();
$url  = coreFunctions::getCoreSitePath() . "/register." . SITE_CONFIG_PAGE_EXTENSION . "?f=" . urlencode($file->shortUrl);
if ($auth->loggedIn == true)
{
    $url = coreFunctions::getCoreSitePath() . "/upgrade." . SITE_CONFIG_PAGE_EXTENSION;
}
?>

<div class="contentPageWrapper">
    <div class="pageSectionMainFull ui-corner-all">
        <div class="pageSectionMainInternal">

            <?php if(UserPeer::showSiteAdverts()): ?>
            <!-- top ads -->
            <div class="metaRedirectWrapperTopAds">
                <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_TOP; ?>
            </div>
            <?php endif; ?>

            <div class="downloadPageTableV3">
                <table>
                    <tbody>
                        <tr>
                            <td class="descr responsiveWordWrap">
                                <?php echo t('download_page_file', 'File'); ?>: <?php echo wordwrap(validation::safeOutputToScreen($file->originalFilename), 28, ' ', true); ?><br/>
                                <?php echo t('download_page_size', 'Size'); ?>: <?php echo coreFunctions::formatSize($file->fileSize); ?><br/>
                                <br/>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a class="link btn-free" href="#">
                                    wait...
                                </a>
                    <div class="download-timer" style="display:none;">
                        <?php echo UCFirst(t('wait', 'wait')); ?> <span class="download-timer-seconds"></span>&nbsp;<?php echo t('sec', 'sec'); ?>.<br/>
                        <span id="loadingSpinner">
                            <img src="<?php echo SITE_IMAGE_PATH; ?>/loading_small.gif" alt="<?php echo t("please_wait", "please wait"); ?>" width="16" height="16" style="padding-top: 8px;"/><br/>
                        </span>
                    </div>
                    </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div id="pageHeader" style="padding-top: 18px;">
                <h2><?php echo t("download_page_upgrade_to_premium", "upgrade to premium"); ?></h2>
            </div>
            <div class="clear"><!-- --></div>

            <div class="upgradeBoxContainer">
            <?php include_once(SITE_TEMPLATES_PATH . '/partial/_upgrade_boxes.inc.php'); ?>
</div>
                
        </div>
    </div>
</div>
