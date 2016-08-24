<?php
// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

coreFunctions::limitEmailsSentPerHour();

/* load file */
if (isset($_REQUEST['u']))
{
    $file = file::loadById($_REQUEST['u']);
    if (!$file)
    {
        // failed lookup of file
        coreFunctions::redirect(WEB_ROOT . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION);
    }

    // check current user has permission to edit file
    if ($file->userId != $Auth->id)
    {
        coreFunctions::redirect(WEB_ROOT . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION);
    }
}
else
{
    coreFunctions::redirect(WEB_ROOT . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION);
}
$accountLocked = 0;
if(SITE_CONFIG_SECURITY_ACCOUNT_LOCK == 'yes' && coreFunctions::getUsersAccountLockStatus($Auth->id))
{
    $accountLocked = 1;
}

$isPublic = 1;
if(corefunctions::getOverallPublicStatus($Auth->id, $file->folderId, $file->id) == false)
{
    $isPublic = 0;
}
$isFolderPublic = 1;
if(coreFunctions::getUserFoldersPublicStatus($file->folderId) == false)
{
    $isFolderPublic = 0;
}

?>

<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
<ul class="nav nav-tabs" id="tabContent">
    <li class="active" title="<?php echo validation::safeOutputToScreen($file->originalFilename); ?>"><a href="#details" data-toggle="tab"><i class="entypo-info"></i> <?php echo t('account_home_file_details', 'File Details'); ?></a></li>
    <?php if ($file->statusId == 1): ?>
        <li><a href="#send-via-email" data-toggle="tab"><i class="entypo-mail"></i> <?php echo t('account_home_send_via_email', 'Send Via Email'); ?></a></li>
    <?php endif; ?>
    <?php
    // append any plugin includes
    pluginHelper::includeAppends('account_home_file_details_tab.inc.php', array('file' => $file, 'Auth' => $Auth));
    ?>
</ul>

<div class="modal-body">
    <div class="tab-content">
        <div class="tab-pane active" id="details">
            <table class="top-detail-wrapper">
                <tr>
                    <td>
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td class="share-file-table-header">
                                        <?php echo UCWords(t('filename', 'filename')); ?>:
                                    </td>
                                    <td class="responsiveTable">
                                        <?php echo validation::safeOutputToScreen($file->originalFilename); ?><?php if ($file->statusId == 1): ?>&nbsp;&nbsp;<a href="<?php echo validation::safeOutputToScreen(CORE_PAGE_WEB_ROOT.'/account_home_v2_direct_download.php?fileId='.$file->id); ?>" target="_blank">(<?php echo t('download', 'download'); ?>)</a><?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="share-file-table-header">
                                        <?php echo UCWords(t('filesize', 'filesize')); ?>:
                                    </td>
                                    <td class="responsiveTable">
                                        <?php echo coreFunctions::formatSize($file->fileSize); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="share-file-table-header">
                                        <?php echo UCWords(t('added', 'added')); ?>:
                                    </td>
                                    <td class="responsiveTable">
                                        <?php echo coreFunctions::formatDate($file->uploadedDate); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <?php if ($isPublic == 1): ?>
                        
                         <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <?php if (($file->statusId == 1) && ($isPublic == 1) && ($isFolderPublic == 1)): ?>                                    
                                        <td class="share-file-table-header">
                                            <?php echo UCWords(t('url', 'url')); ?>:
                                        </td>
                                        <td class="responsiveTable">
                                            <a href="<?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?>" target="_blank"><?php echo validation::safeOutputToScreen($file->getFullShortUrl()); ?></a>
                                        </td>                                    
                                    <?php else: ?>
                                        <td class="share-file-table-header">
                                            <?php echo UCWords(t('status', 'status')); ?>:
                                        </td>
                                        <td class="responsiveTable">
                                            <?php echo validation::safeOutputToScreen(UCWords(file::getStatusLabel($file->statusId))); ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            </tbody>
                        </table>
                        <?php endif; ?>
						
                        <table class="table table-bordered table-striped">
                            <tbody>
								<?php if ($file->statusId == 1): ?>
								<tr>
                                    <td class="share-file-table-header">
                                        <?php echo UCWords(t('sharing', 'Sharing')); ?>:
                                    </td>
                                    <td class="responsiveTable">
										<?php echo ($isPublic == true) ? '<i class="entypo-lock-open"></i>' : '<i class="entypo-lock"></i>'; ?>
                                        <?php echo ($isPublic == true) ? t('public_file', 'Public File - Can be Shared') : t('private_file', 'Private File - Only Available via Your Account'); ?>
                                    </td>
                                </tr>
								<?php endif; ?>
                                <tr>
                                    <td class="share-file-table-header">
                                        <?php echo UCWords(t('downloads', 'downloads')); ?>:
                                    </td>
                                    <td class="responsiveTable">
                                        <strong><?php echo validation::safeOutputToScreen($file->visits); ?></strong>&nbsp;&nbsp;<?php echo ($file->lastAccessed != null) ? ('(' . UCWords(t('last_accessed', 'last accessed')) . ': ' . coreFunctions::formatDate($file->lastAccessed) . ')') : ''; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="button-wrapper responsiveMobileAlign">
                            <?php if ($file->statusId == 1): ?>
                            <div class="btn-group responsiveMobileMargin">
                                <button type="button" class="btn btn-default" data-dismiss="modal" onClick="showEditFileForm(<?php echo (int) $file->id; ?>); return false;"><?php echo UCWords(t('account_file_details_edit_file', 'Edit File')); ?> <i class="entypo-pencil"></i></button>
                            </div>
                            
                            <div class="btn-group responsiveMobileMargin">
                                <button type="button" class="btn btn-default" data-dismiss="modal" onClick="showStatsPopup(<?php echo (int)$file->id; ?>); return false;"><?php echo UCWords(t('account_file_details_stats', 'Stats')); ?> <i class="entypo-chart-line"></i></button>
                            </div>
                            <?php endif; ?>
                            <?php if ($file->statusId == 1): ?>
                            <div class="btn-group responsiveMobileMargin">
                                <button type="button" class="btn btn-info" onClick="triggerFileDownload(<?php echo $file->id; ?>); return false;"><?php echo UCWords(t('account_file_details_download', 'Download')); ?> <i class="entypo-down"></i></button>
                            </div>
                            <div class="btn-group responsiveMobileMargin">
                                <button type="button" class="btn btn-red" onClick="deleteFileFromDetailPopup(<?php echo $file->id; ?>); return false;"><?php echo UCWords(t('account_file_details_delete', 'Delete')); ?> <i class="entypo-trash"></i></button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="last-cell responsiveHide">
                        <?php if ($imageLink = file::getIconPreviewImageUrl((array) $file, false, 160, false, 300, 300)): ?>
                        <div>
                            <?php if ($file->statusId == 1): ?><a href="<?php echo validation::safeOutputToScreen(CORE_PAGE_WEB_ROOT.'/account_home_v2_direct_download.php?fileId='.$file->id); ?>" target="_blank"><?php endif; ?>
                                <img src="<?php echo $imageLink; ?>" width="<?php echo (substr($imageLink, strlen($imageLink)-4, 4)== '.png')?'160':'300'; ?>" alt="" style="padding-left: <?php echo (substr($imageLink, strlen($imageLink)-4, 4)== '.png')?'':'2'; ?>0px;"/>
                        <?php if ($file->statusId == 1): ?></a><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <?php if ($isPublic == 1): ?>
				<?php if ($file->statusId == 1): ?>
                <h4><strong><?php echo UCWords(t("download_urls", "download urls")); ?></strong></h4>
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td class="share-file-table-header">
                                <?php echo t('html_code', 'HTML Code'); ?>:
                            </td>
                            <td class="responsiveTable ltrOverride">
                                <section onClick="selectAllText(this); return false;"><?php echo $file->getHtmlLinkCode(); ?></section>
                            </td>
                        </tr>
                        <tr>
                            <td class="share-file-table-header">
                                <?php echo UCWords(t('forum_code', 'forum code')); ?>
                            </td>
                            <td class="responsiveTable ltrOverride">
                                <section onClick="selectAllText(this); return false;"><?php echo $file->getForumLinkCode(); ?></section>
                            </td>
                        </tr>
                    </tbody>
                </table>
				<?php endif; ?>
            <?php endif; ?>
			
			<?php
			// append any plugin includes
			pluginHelper::includeAppends('account_home_file_details_tab_1.inc.php', array('file' => $file, 'Auth' => $Auth, 'isPublic' => $isPublic));
			?>

            <h4><strong><?php echo UCWords(t("options", "options")); ?></strong></h4>
            <table class="table table-bordered table-striped no-bottom-margin">
                <tbody>
                    <tr>
                        <td class="share-file-table-header">
                            <?php echo UCWords(t('statistics_url', 'statistics url')); ?>:
                        </td>
                        <td class="responsiveTable">
                            <a href="<?php echo validation::safeOutputToScreen($file->getStatisticsUrl()); ?>" target="_blank"><?php echo validation::safeOutputToScreen($file->getStatisticsUrl()); ?></a>
                        </td>
                    </tr>

                    <?php if ($file->statusId == 1): ?>
                        <tr>
                            <td class="share-file-table-header">
                            <?php if ($isPublic ==1): ?>
                                <?php echo UCWords(t('public_info_page', 'public info page')); ?>:
                            <?php else: ?>
                                <?php echo UCWords(t('private_info_page', 'private info page')); ?>:
                            <?php endif; ?>
                            </td>
                            <td class="responsiveTable">
                                <a href="<?php echo validation::safeOutputToScreen($file->getInfoUrl()); ?>" target="_blank"><?php echo current(explode("?", validation::safeOutputToScreen($file->getInfoUrl()))); ?></a>
                            </td>
                        </tr>
                        <?php if($accountLocked == 0): ?>
                        <tr>
                            <td class="share-file-table-header">
                                <?php echo UCWords(t('delete_file_url', 'delete file url')); ?>:
                            </td>
                            <td class="responsiveTable">
                                <a href="<?php echo validation::safeOutputToScreen($file->getDeleteUrl()); ?>" target="_blank"><?php echo validation::safeOutputToScreen($file->getDeleteUrl()); ?></a>
                            </td>
                        </tr>
                        <?php endif; ?>
						<?php if ($isPublic == 1): ?>
                        <tr>
                            <td class="share-file-table-header">
                                <?php echo UCWords(t('share_file', 'share file')); ?>:
                            </td>
                            <td style="height: 33px;" class="responsiveTable">
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
                                </div>
                                <!-- AddThis Button END -->
                            </td>
                        </tr>
						<?php endif; ?>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>
        <?php if ($file->statusId == 1): ?>
        <div class="tab-pane" id="send-via-email">
            <div class="row">
                <form action="<?php echo WEB_ROOT; ?>/ajax/_account_file_details_send_email.process.ajax.php" autocomplete="off">
                    <div class="col-md-12">
                    <?php if ($isPublic == 1): ?>
                        <div class="form-group"> 
                            <p><?php echo t('account_file_details_intro_user_the_form_below_send_email', 'Use the form below to share this file via email. The recipient will receive a link to download the file.') ; ?></p>
                        </div>
                        <div class="form-group">
                            <label class="control-label" for="shareRecipientName"><?php echo UCWords(t("recipient_name", "recipient full name")); ?>:</label>
                            <input type="text" id="shareRecipientName" name="shareRecipientName" class="form-control"/>
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="shareEmailAddress"><?php echo UCWords(t("recipient_email_address", "recipient email address")); ?>:</label>
                            <input type="text" id="shareEmailAddress" name="shareEmailAddress" class="form-control"/>
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="shareExtraMessage"><?php echo UCWords(t("extra_message", "extra message")); ?>:</label>
                            <textarea id="shareExtraMessage" name="shareExtraMessage" class="form-control"></textarea>
                        </div>

                        <div class="form-group">
                            <input type="hidden" name="submitme" id="submitme" value="1"/>
                            <input type="hidden" value="<?php echo (int) $file->id; ?>" name="fileId"/>
                            <button type="button" class="btn btn-info" onClick="processAjaxForm(this, function() { $('#shareRecipientName').val(''); $('#shareEmailAddress').val(''); $('#shareExtraMessage').val(''); }); return false;"><?php echo UCWords(t("send_email", "send email")); ?> <i class="entypo-mail"></i></button>
                        </div>
                        <?php else: ?>
                        <div class="form-group"> 
                            <p><?php echo t('account_file_details_send_email_links_disabled', 'Sharing links has been disabled on this file.') ; ?></p> 
                        </div>
                        <div class="form-group">
                            <label class="control-label" for="shareRecipientName"><?php echo UCWords(t("recipient_name", "recipient full name")); ?>:</label>
                            <input type="text" id="shareRecipientName" name="shareRecipientName" class="form-control" disabled/>
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="shareEmailAddress"><?php echo UCWords(t("recipient_email_address", "recipient email address")); ?>:</label>
                            <input type="text" id="shareEmailAddress" name="shareEmailAddress" class="form-control" disabled/>
                        </div>

                        <div class="form-group">
                            <label class="control-label" for="shareExtraMessage"><?php echo UCWords(t("extra_message", "extra message")); ?>:</label>
                            <textarea id="shareExtraMessage" name="shareExtraMessage" class="form-control" disabled></textarea>
                        </div>

                        <div class="form-group">
                            <input type="hidden" name="submitme" id="submitme" value="1"/>
                            <input type="hidden" value="<?php echo (int) $file->id; ?>" name="fileId"/>
                            <button type="button" class="btn btn-info" onClick="processAjaxForm(this, function() { $('#shareRecipientName').val(''); $('#shareEmailAddress').val(''); $('#shareExtraMessage').val(''); }); return false;" disabled><?php echo UCWords(t("send_email", "send email")); ?> <i class="entypo-mail"></i></button>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
		
        <?php
        // append any plugin includes
        pluginHelper::includeAppends('account_home_file_details_tab_content.inc.php', array('file' => $file, 'Auth' => $Auth));
        ?>
    </div>
</div>

<div class="modal-footer">
    <div class="row">
        <div class="col-md-8 text-left">
            
        </div>
        <div class="col-md-4">
            <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo UCWords(t("close", "close")); ?> <i class="entypo-check"></i></button>
        </div>
    </div>
</div>