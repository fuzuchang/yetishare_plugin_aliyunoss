<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup database
$db = Database::getDatabase(true);

// load file
$fileId = (int)$_REQUEST['fileId'];
$file = file::loadById($fileId);
$folder = fileFolder::loadById($file->folderId);
if(!$file)
{
	// exit
	coreFunctions::output404();
}

// make sure the logged in user owns this file
if($file->userId != $Auth->id)
{
	// exit
	coreFunctions::output404();
}

// Get users account lock status
$accountLocked = 0;
$lockStatus    = corefunctions::getUsersAccountLockStatus($Auth->id);
if(SITE_CONFIG_SECURITY_ACCOUNT_LOCK == 'yes' && $lockStatus == '1')
{
    $accountLocked = 1;
}

$userIsPublic   = 1;
$folderIsPublic = 1;
$globalPublic   = 1;
$fileIsPublic   = 1;
if(coreFunctions::getUserPublicStatus($Auth->id) === false)
{
    $userIsPublic = 0;
}

if(corefunctions::getUserFoldersPublicStatus($editFolderId) === false)
{
    $folderIsPublic = 0;
}

if(corefunctions::getUserFilesPublicStatus($file->id) === false)
{
    $fileIsPublic = 0;
}

if(corefunctions::getOverallSitePrivacyStatus() === false)
{
    $globalPublic = 0;
}
// load folder structure as array
$folderListing = fileFolder::loadAllForSelect($Auth->id);

?>

<form action="<?php echo WEB_ROOT; ?>/ajax/_account_edit_file.process.ajax.php" autocomplete="off">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo t("edit_existing_item", "Edit Existing Item"); ?> (<?php echo validation::safeOutputToScreen($file->originalFilename, null, 55); ?>)</h4>
    </div>

    <div class="modal-body">
        <?php if($accountLocked == 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="filename" class="control-label"><?php echo UCWords(t("filename", "filename")); ?></label>
                    <input type="text" class="form-control" name="filename" id="filename" value="<?php echo validation::safeOutputToScreen($file->getFilenameExcExtension()); ?>"/>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="folder" class="control-label"><?php echo UCWords(t("folder", "folder")); ?></label>
                    <select class="form-control" name="folder" id="folder">
                        <option value=""><?php echo t('_default_', '- Default -'); ?></option>
                        <?php
                        foreach ($folderListing AS $k => $folderListingItem)
                        {
                            if($editFolderId !== null)
                            {
                                // ignore this folder and any children
                                if(substr($folderListingItem, 0, strlen($currentFolderStr)) == $currentFolderStr)
                                {
                                    continue;
                                }
                            }
                            
                            echo '<option value="' . (int) $k . '"';
                            if ($file->folderId == (int) $k)
                            {
                                echo ' SELECTED';
                            }
                            echo '>' . validation::safeOutputToScreen($folderListingItem) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
		
		<div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password" class="control-label"><?php echo UCWords(t("access_password", "access password")); ?></label>
                    <div class="row">
                        <div class="col-md-2 inline-checkbox">
                            <input type="checkbox" name="enablePassword" id="enablePassword" value="1" <?php echo strlen($file->accessPassword)?'CHECKED':''; ?> onClick="toggleFilePasswordField();">
                        </div>
                        <div class="col-md-10">
                            <input type="password" class="form-control" name="password" id="password" autocomplete="off"<?php echo strlen($file->accessPassword)?' value="**********"':''; ?> <?php echo strlen($file->accessPassword)?'':'READONLY'; ?>/>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="reset_stats" class="control-label"><?php echo UCWords(t("reset_stats", "reset stats")); ?></label>
                    <select class="form-control" name="reset_stats" id="reset_stats">
                        <option value="0" SELECTED><?php echo t('no_keep_stats', 'No, keep stats'); ?></option>
                        <option value="1"><?php echo t('yes_remove_stats', 'Yes, remove stats'); ?></option>
                    </select>
                </div>
            </div>
		</div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="folder" class="control-label"><?php echo UCWords(t("file_privacy", "file privacy")); ?></label>                    
                    <?php if($userIsPublic == 0): ?>
                    <select class="form-control" name="isPublic" id="isPublic" disabled="disabled">
                    <?php else: ?>                    
                    <select class="form-control" name="isPublic" id="isPublic">
                    <?php endif; ?>
                        <option value="1" <?php echo (($fileIsPublic == 1) ? ' selected' : ''); ?>><?php echo t('privacy_public_access', 'Public - access only if users know the sharing link.'); ?></option>
                        <option value="0" <?php echo (($fileIsPublic == 0 || ($userIsPublic == 0 && $fileIsPublic == 1)) ? ' selected' : ''); ?>><?php echo t('privacy_private_no_access', 'Private - no access outside of your account.'); ?></option>
                    </select>
                    <br />
                    <p><?php
                    if($userIsPublic == 0)
                    {
                        echo t('file_folder_privacy_notice', 'You can not update this [[[FILEFOLDER]]] permissions as your account settings are set to make all files private.', array('FILEFOLDER' => 'files'));
                    }
                    ?></p>
                </div>
            </div>
        </div>
        <?php elseif($accountLocked == 1): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <?php echo t('account_locked_file_edit_error_message', 'This account has been locked, please unlock the account to regain full functionality.'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php if($accountLocked == 0): ?>
    <div class="modal-footer">
        <input type="hidden" name="submitme" id="submitme" value="1"/>
        <input type="hidden" value="<?php echo (int) $fileId; ?>" name="fileId"/>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t("cancel", "cancel"); ?></button>
        <button type="button" class="btn btn-info" onClick="processAjaxForm(this, function() { if(<?php echo (int)$file->folderId; ?> != $('.edit-file-modal #folder').val()) refreshFolderListing(); else refreshFileListing(); $('.modal').modal('hide'); }); return false;"><?php echo UCWords(t("update_item", "update item")); ?> <i class="entypo-check"></i></button>
    </div>
    <?php endif; ?>
</form>