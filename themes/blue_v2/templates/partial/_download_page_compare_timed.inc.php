<?php
// calculate account type to use for stats below
$freeLevelId = 1;
$freeLevelIdRs = (int)$db->getValue('SELECT id FROM user_level WHERE level_type = \'free\' AND id > 0 ORDER BY id ASC LIMIT 1');
if($freeLevelIdRs)
{
	$freeLevelId = (int)$freeLevelIdRs;
}
$paidLevelId = 2;
$paidLevelIdRs = (int)$db->getValue('SELECT id FROM user_level WHERE level_type = \'paid\' AND id > 0 ORDER BY id ASC LIMIT 1');
if($paidLevelIdRs)
{
	$paidLevelId = (int)$paidLevelIdRs;
}
$daysToKeepFiles = UserPeer::getDaysToKeepInactiveFiles($freeLevelId);
$maxUploadSizeFree = UserPeer::getMaxUploadFilesize($freeLevelId);
$maxUploadSizePaid = UserPeer::getMaxUploadFilesize($paidLevelId);
?>
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
        display();
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
            <div class="downloadPageTable">
                <table>
                    <tbody>
                        <tr>
                            <th class="descr responsiveWordWrap">
                                <strong>
                                    <?php echo wordwrap(validation::safeOutputToScreen($file->originalFilename), 28, ' ', true); ?> (<?php echo coreFunctions::formatSize($file->fileSize); ?>)<br/>
                                </strong>
                                <?php echo t('choose_free_or_premium_download', 'Choose free or premium download'); ?>
                            </th>
                            <th>
                                <a class="link btn-free" href="#" onClick="display(); return false;">
                                    <?php echo strtoupper(t('slow_download', 'slow download')); ?>
                                </a>
                    <div class="download-timer" style="display:none;">
                        <?php echo UCFirst(t('wait', 'wait')); ?> <span class="download-timer-seconds"></span>&nbsp;<?php echo t('sec', 'sec'); ?>.<br/>
                        <span id="loadingSpinner">
                            <img src="<?php echo SITE_IMAGE_PATH; ?>/loading_small.gif" alt="<?php echo t("please_wait", "please wait"); ?>" width="16" height="16" style="padding-top: 8px;"/><br/>
                        </span>
                    </div>
                    </th>
                    <th>
                        <a class="link premiumBtn" href="<?php echo $url; ?>">
                            <?php echo strtoupper(t('fast_instant_download', 'FAST INSTANT DOWNLOAD')); ?>                          
                        </a>
                    </th>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('download_type', 'download type')); ?>:
                        </td>
                        <td><?php echo UCFirst(t('free', 'free')); ?></td>
                        <td>
                            <strong>
                                <?php echo UCFirst(t('premium', 'premium')); ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('download_speed', 'download speed')); ?>:
                        </td>
                        <td>
                            <?php echo UserPeer::getMaxDownloadSpeed($freeLevelId) > 0 ? coreFunctions::formatSize(UserPeer::getMaxDownloadSpeed($freeLevelId)) . 'ps' : UCFirst(t('limited', 'limited')); ?>
                        </td>
                        <td>
                            <strong>
                                <?php echo UCFirst(t('maximum', 'maximum')); ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('waiting_time', 'waiting time')); ?>:
                        </td>
                        <td><?php echo (int)$additionalSettings['download_wait'] > 0 ? (int)$additionalSettings['download_wait'] . ' ' . UCFirst(t('seconds', 'seconds')) : UCFirst(t('instant', 'instant')); ?></td>
                        <td>
                            <strong>
                                <?php echo UCFirst(t('instant', 'instant')); ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('site_advertising', 'site advertising')); ?>:
                        </td>
                        <td>
                            <?php echo UCFirst(t('yes', 'yes')); ?>                            
                        </td>
                        <td>
                            <strong>
                                <?php echo UCFirst(t('none', 'none')); ?>
                            </strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('how_long_to_keep_files', 'how long to keep files')); ?>:
                        </td>
                        <td><?php echo $daysToKeepFiles; ?> <?php echo UCFirst(t('days', 'days')); ?></td>
                        <td>
                            <?php
                            if ((int)$daysToKeepFiles == 0)
                            {
                                echo UCFirst(t('forever', 'forever'));
                            }
                            else
                            {
                                echo $daysToKeepFiles . UCFirst(t('days', 'days'));
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('maximum_file_upload_size', 'Maximum file upload size')); ?>:
                        </td>
                        <td><?php echo $maxUploadSizeFree > 0 ? coreFunctions::formatSize($maxUploadSizeFree) : UCFirst(t('unlimited', 'unlimited')); ?></td>
                        <td><?php echo $maxUploadSizePaid > 0 ? coreFunctions::formatSize($maxUploadSizePaid) : UCFirst(t('unlimited', 'unlimited')); ?></td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('interface_to_manage_uploaded_files', 'interface to manage uploaded files')); ?>:
                        </td>
                        <td><?php echo UCFirst(t('not_available', 'not available')); ?></td>
                        <td><?php echo UCFirst(t('available', 'available')); ?></td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('fast_download_even_when_servers_are_busy', 'fast download even when servers are busy')); ?>:
                        </td>
                        <td><?php echo UCFirst(t('not_available', 'not available')); ?></td>
                        <td><?php echo UCFirst(t('available', 'available')); ?></td>
                    </tr>
                    <tr>
                        <td class="descr">
                            <?php echo UCFirst(t('estimated_download_time', 'estimated Download time')); ?>:
                        </td>
                        <td>
                            <a class="link btn-free" href="#" onClick="display(); return false;">
                                <?php
                                echo coreFunctions::calculateDownloadSpeedFormatted($file->fileSize, UserPeer::getMaxDownloadSpeed());
                                ?>
                            </a>
                            <div class="download-timer" style="display:none;">
                                <?php echo UCFirst(t('wait', 'wait')); ?> <span class="download-timer-seconds"></span>&nbsp;<?php echo t('sec', 'sec'); ?>.                                
                            </div>
                        </td>
                        <td>
                            <a class="link premiumBtn" href="<?php echo $url; ?>">
                                <?php echo coreFunctions::calculateDownloadSpeedFormatted($file->fileSize, 0); ?>                              
                            </a>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <?php if(UserPeer::showSiteAdverts()): ?>
            <!-- bottom ads -->
            <div class="metaRedirectWrapperBottomAds">
                <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_BOTTOM; ?>
            </div>
            <?php endif; ?>

            <div id="pageHeader" style="padding-top: 18px;">
                <h2><?php echo t("account_benefits", "account benefits"); ?></h2>
            </div>
            <div class="clear"><!-- --></div>

            <?php include_once(SITE_TEMPLATES_PATH . '/partial/_upgrade_benefits.inc.php'); ?>

        </div>
    </div>
</div>
