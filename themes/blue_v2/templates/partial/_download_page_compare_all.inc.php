<?php
// calculate account type to use for stats below
$nonLevelId = 0;
$nonLevelIdRs = (int)$db->getValue('SELECT id FROM user_level WHERE level_type = \'nonuser\' ORDER BY id ASC LIMIT 1');
if($nonLevelIdRs)
{
	$nonLevelId = (int)$nonLevelIdRs;
}
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
$daysToKeepFilesNon = UserPeer::getDaysToKeepInactiveFiles($nonLevelId);
$daysToKeepFilesFree = UserPeer::getDaysToKeepInactiveFiles($freeLevelId);
$daysToKeepFilesPaid = UserPeer::getDaysToKeepInactiveFiles($paidLevelId);
$maxUploadSizeFree = UserPeer::getMaxUploadFilesize($freeLevelId);
$maxUploadSizePaid = UserPeer::getMaxUploadFilesize($paidLevelId);
?>
<script>
    <!--
    var milisec = 0;
    var seconds = <?php echo (int) $additionalSettings['download_wait']; ?>;

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
<?php if ((int) $additionalSettings['download_wait'] > 0): ?>
            $('.download-timer-seconds').html(<?php echo (int) $additionalSettings['download_wait']; ?>);
<?php endif; ?>
        display();
        countdownTimer = setInterval('display()', 1000);
    });
    -- >
</script>

<?php
if (isset($downloadPage['additional_javascript_code']))
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

            <?php if (UserPeer::showSiteAdverts()): ?>
                <!-- top ads -->
                <div class="metaRedirectWrapperTopAds">
                    <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_TOP; ?>
                </div>
            <?php endif; ?>

            <div class="downloadPageTableV2">
                <table>
                    <tbody>
                        <tr>
                            <th class="descr responsiveWordWrap">
                                <strong>
                                    <?php echo wordwrap(validation::safeOutputToScreen($file->originalFilename), 28, ' ', true); ?> (<?php echo coreFunctions::formatSize($file->fileSize); ?>)<br/>
                                </strong>
                                <?php echo t('choose_free_or_premium_download', 'Choose free or premium download'); ?>
                            </th>
                            <th class="typeHeader reponsiveMobileHide" style="color: red;">
                                <?php echo strtoupper(t('free', 'free')); ?>
                            </th>
                            <th class="typeHeader reponsiveMobileHide" style="color: green;">
                                <?php echo strtoupper(t('registered', 'registered')); ?>
                            </th>
                            <th class="typeHeader">
                                <a class="link premiumBtn" href="<?php echo $url; ?>">
                                    <?php echo strtoupper(t('download_page_premium', 'PREMIUM')); ?>                          
                                </a>
                            </th>
                        </tr>
                        <tr>
                            <td class="descr">
                                <?php echo UCFirst(t('download_speed', 'download speed')); ?>:
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php echo UserPeer::getMaxDownloadSpeed(0) > 0 ? coreFunctions::formatSize(UserPeer::getMaxDownloadSpeed(0)) . 'ps' : UCFirst(t('limited', 'limited')); ?>
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php echo UserPeer::getMaxDownloadSpeed(1) > 0 ? coreFunctions::formatSize(UserPeer::getMaxDownloadSpeed(1)) . 'ps' : UCFirst(t('limited', 'limited')); ?>
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
                            <td class="reponsiveMobileHide">
                                <?php
                                $totalTime   = (int) UserPeer::getTotalWaitingTime(0);
                                echo $totalTime > 0 ? $totalTime . ' ' . UCFirst(t('seconds', 'seconds')) : UCFirst(t('instant', 'instant'));
                                ?>
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php
                                $totalTime   = (int) UserPeer::getTotalWaitingTime(1);
                                echo $totalTime > 0 ? $totalTime . ' ' . UCFirst(t('seconds', 'seconds')) : UCFirst(t('instant', 'instant'));
                                ?>
                            </td>
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
                            <td class="reponsiveMobileHide">
                                <?php
                                $showAdverts = UserPeer::showSiteAdverts(0);
                                if ($showAdverts)
                                {
                                    echo UCFirst(t('download_page_yes', 'yes'));
                                }
                                else
                                {
                                    echo UCFirst(t('download_page_none', 'none'));
                                }
                                ?>                            
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php
                                $showAdverts = UserPeer::showSiteAdverts(1);
                                if ($showAdverts)
                                {
                                    echo UCFirst(t('download_page_yes', 'yes'));
                                }
                                else
                                {
                                    echo UCFirst(t('download_page_none', 'none'));
                                }
                                ?>                            
                            </td>
                            <td>
                                <strong>
                                    <?php
                                    $showAdverts = UserPeer::showSiteAdverts(2);
                                    if ($showAdverts)
                                    {
                                        echo UCFirst(t('download_page_yes', 'yes'));
                                    }
                                    else
                                    {
                                        echo UCFirst(t('download_page_none', 'none'));
                                    }
                                    ?>                            
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="descr">
                                <?php echo UCFirst(t('how_long_to_keep_files', 'how long to keep files')); ?>:
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php
                                if ((int) $daysToKeepFilesNon == 0)
                                {
                                    echo UCFirst(t('forever', 'forever'));
                                }
                                else
                                {
                                    echo $daysToKeepFilesNon . ' ' . UCFirst(t('days', 'days'));
                                }
                                ?>
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php
                                if ((int) $daysToKeepFiles == 0)
                                {
                                    echo UCFirst(t('forever', 'forever'));
                                }
                                else
                                {
                                    echo $daysToKeepFiles . ' ' . UCFirst(t('days', 'days'));
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ((int) $daysToKeepFilesPaid == 0)
                                {
                                    echo UCFirst(t('forever', 'forever'));
                                }
                                else
                                {
                                    echo $daysToKeepFilesPaid . ' ' . UCFirst(t('days', 'days'));
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="descr">
                                <?php echo UCFirst(t('maximum_file_upload_size', 'Maximum file upload size')); ?>:
                            </td>
                            <td class="reponsiveMobileHide"><?php echo UserPeer::getMaxUploadFilesize(0) > 0 ? coreFunctions::formatSize(UserPeer::getMaxUploadFilesize(0)) : UCFirst(t('unlimited', 'unlimited')); ?></td>
                            <td class="reponsiveMobileHide"><?php echo UserPeer::getMaxUploadFilesize(1) > 0 ? coreFunctions::formatSize(UserPeer::getMaxUploadFilesize(1)) : UCFirst(t('unlimited', 'unlimited')); ?></td>
                            <td><?php echo UserPeer::getMaxUploadFilesize(2) > 0 ? coreFunctions::formatSize(UserPeer::getMaxUploadFilesize(2)) : UCFirst(t('unlimited', 'unlimited')); ?></td>
                        </tr>
                        <tr>
                            <td class="descr">
                                <?php echo UCFirst(t('interface_to_manage_uploaded_files', 'interface to manage uploaded files')); ?>:
                            </td>
                            <td class="reponsiveMobileHide"><?php echo UCFirst(t('not_available', 'not available')); ?></td>
                            <td class="reponsiveMobileHide"><?php echo UCFirst(t('available', 'available')); ?></td>
                            <td><?php echo UCFirst(t('available', 'available')); ?></td>
                        </tr>
                        <tr>
                            <td class="descr">
                                <?php echo UCFirst(t('fast_download_even_when_servers_are_busy', 'fast download even when servers are busy')); ?>:
                            </td>
                            <td class="reponsiveMobileHide"><?php echo UCFirst(t('not_available', 'not available')); ?></td>
                            <td class="reponsiveMobileHide"><?php echo UCFirst(t('not_available', 'not available')); ?></td>
                            <td><?php echo UCFirst(t('available', 'available')); ?></td>
                        </tr>
                        <tr>
                            <td class="descr">
                                <?php echo UCFirst(t('estimated_download_time', 'estimated Download time')); ?>:
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php
                                echo coreFunctions::calculateDownloadSpeedFormatted($file->fileSize, UserPeer::getMaxDownloadSpeed(0));
                                ?>
                            </td>
                            <td class="reponsiveMobileHide">
                                <?php
                                echo coreFunctions::calculateDownloadSpeedFormatted($file->fileSize, UserPeer::getMaxDownloadSpeed(1));
                                ?>
                            </td>
                            <td>
                                <?php echo coreFunctions::calculateDownloadSpeedFormatted($file->fileSize, 0); ?>                              
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- download buttons -->
            <div class="largeDownloadButtons">
                <a href="<?php echo $file->getNextDownloadPageLink(); ?>">
                    <img src="<?php echo SITE_IMAGE_PATH; ?>/slow_download_button.png" alt="<?php echo t('download_page_slow_download', 'slow download'); ?>"/></a>
                <a href="<?php echo $url; ?>"><img src="<?php echo SITE_IMAGE_PATH; ?>/high_speed_download.png" width="344" height="138" alt="<?php echo t('download_page_high_speed_download', 'high speed download'); ?>"/></a>
                <div class="clear"><!-- --></div>
            </div>
            <!-- end download buttons -->


            <div id="pageHeader" style="padding-top: 18px;">
                <h2><?php echo t("account_benefits", "account benefits"); ?></h2>
            </div>
            <div class="clear"><!-- --></div>

            <?php include_once(SITE_TEMPLATES_PATH . '/partial/_upgrade_benefits.inc.php'); ?>

        </div>
    </div>
</div>
