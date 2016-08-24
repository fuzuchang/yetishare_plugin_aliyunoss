<div>
	<?php if (UserPeer::showSiteAdverts()): ?>
        <!-- top ads -->
        <div class="metaRedirectWrapperTopAds">
            <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_TOP; ?>
        </div>
    <?php endif; ?>
    <table style="width:100%;">
        <tbody>
            <tr>
                <td>
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <td class="share-file-table-header">
                                    <?php echo UCWords(t('filename', 'filename')); ?>:
                                </td>
                                <td class="responsiveInfoTable">
                                    <?php echo validation::safeOutputToScreen($file->originalFilename, null, 70); ?>
                                    &nbsp;&nbsp;<a href="<?php echo validation::safeOutputToScreen($file->getNextDownloadPageLink()); ?>">(<?php echo t('download', 'download'); ?>)</a>
                                    <?php if ($Auth->id != $file->userId): ?>
                                        &nbsp;&nbsp;<a href="<?php echo CORE_PAGE_WEB_ROOT . '/account_copy_file.php?f=' . $file->shortUrl; ?>">(<?php echo t('copy_into_your_account', 'copy file'); ?>)</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="share-file-table-header">
                                    <?php echo UCWords(t('filesize', 'filesize')); ?>:
                                </td>
                                <td class="responsiveInfoTable">
                                    <?php echo coreFunctions::formatSize($file->fileSize); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <td class="share-file-table-header">
                                    <?php echo UCWords(t('url', 'url')); ?>:
                                </td>
                                <td class="responsiveInfoTable">
                                    <?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <?php if ($file->getLargeIconPath()): ?>
                    <td class="share-file-last-cell animated reponsiveMobileHide"  data-animation="bounceIn" data-animation-delay="1300">
                        <img src="<?php echo $file->getLargeIconPath(); ?>" width="160" alt="<?php echo strtolower($file->extension); ?>"/>
                    </td>
                <?php endif; ?>

            </tr>
        </tbody>
    </table>
    <h2><?php echo t("download_urls", "download urls"); ?></h2>
    <table class="table table-bordered table-striped">
        <tbody>
            <tr>
                <td class="share-file-table-header">
                    <?php echo t('html_code', 'HTML Code'); ?>:
                </td>
                <td class="responsiveInfoTable">
                    <?php echo $file->getHtmlLinkCode(); ?>
                </td>
            </tr>
            <tr>
                <td class="share-file-table-header">
                    <?php echo UCWords(t('forum_code', 'forum code')); ?>:
                </td>
                <td class="responsiveInfoTable">
                    <?php echo $file->getForumLinkCode(); ?>
                </td>
            </tr>
        </tbody>
    </table>
    <h2><?php echo t("share", "share"); ?></h2>
    <table class="table table-bordered table-striped">
        <tbody>
            <tr>
                <td class="share-file-table-header">
                    <?php echo UCWords(t('share_file', 'share file')); ?>:
                </td>
                <td class="responsiveInfoTable">
                    <!-- AddThis Button BEGIN -->
                    <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                        <a class="addthis_button_preferred_1" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_2" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_3" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_4" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_5" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_6" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_7" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_8" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_preferred_9" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_button_compact" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                        <a class="addthis_counter addthis_bubble_style" addthis:url="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" addthis:title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"></a>
                    </div>
                    <script type="text/javascript" src="<?php echo _CONFIG_SITE_PROTOCOL; ?>://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4f10918d56581527"></script>
                    <!-- AddThis Button END -->
                </td>
            </tr>
        </tbody>
    </table>
	
	<?php if (UserPeer::showSiteAdverts()): ?>
        <!-- bottom ads -->
        <div class="metaRedirectWrapperBottomAds">
        <?php echo SITE_CONFIG_ADVERT_DELAYED_REDIRECT_BOTTOM; ?>
        </div>
	<?php endif; ?>