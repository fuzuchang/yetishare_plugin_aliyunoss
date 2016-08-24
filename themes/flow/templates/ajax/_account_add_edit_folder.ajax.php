<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup database
$db = Database::getDatabase(true);

// load folder structure as array
$folderListing = fileFolder::loadAllForSelect($Auth->id);
// initial parent folder
$parentId = '-1';
if(isset($_REQUEST['parentId']))
{
    $parentId = (int)$_REQUEST['parentId'];
}
// Get users account lock status
$accountLocked = 0;
$lockStatus    = corefunctions::getUsersAccountLockStatus($Auth->id);
if(SITE_CONFIG_SECURITY_ACCOUNT_LOCK == 'yes' && $lockStatus == '1')
{
    $accountLocked = 1;
}

// defaults
$fileFolderData = fileFolder::loadById((int)$_REQUEST['editFolderId']);
$editFolderId = null;
$accessPassword = null;
if((int)$_REQUEST['editFolderId'])
{
    // load existing folder data
    $fileFolder = fileFolder::loadById((int)$_REQUEST['editFolderId']);
    if ($fileFolder)
    {
        // check current user has permission to edit the fileFolder
        if ($fileFolder->userId == $Auth->id)
        {
            // setup edit folder
            $editFolderId   = $fileFolder->id;
            $folderName     = $fileFolder->folderName;
            $parentId       = $fileFolder->parentId;
            $isFolderPublic = $fileFolder->isPublic;
            $accessPassword = $fileFolder->accessPassword;
        }
    }
}

$userIsPublic   = 1;
$folderIsPublic = 1;
$globalPublic   = 1;

if(coreFunctions::getUserPublicStatus($Auth->id) === false)
{
    $userIsPublic = 0;
}

if(corefunctions::getUserFoldersPublicStatus($editFolderId) === false || corefunctions::getUserFoldersPublicStatus($parentId) === false)
{
    $folderIsPublic = 0;
}

if(corefunctions::getOverallSitePrivacyStatus() === false)
{
    $globalPublic = 0;
}

?>

<form action="<?php echo WEB_ROOT; ?>/ajax/_account_add_edit_folder.process.ajax.php" autocomplete="off">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"><?php echo $editFolderId==null?t("add_folder", "add folder"):(t("edit_existing_folder", "Edit Existing Folder").' ('.validation::safeOutputToScreen($fileFolder->folderName).')'); ?></h4>
    </div>

    <div class="modal-body">
        <?php if($accountLocked == '0'): ?>        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="folderName" class="control-label"><?php echo t("edit_folder_name", "Folder Name:"); ?></label>
                    <input type="text" class="form-control" name="folderName" id="folderName" value="<?php echo isset($folderName) ? validation::safeOutputToScreen($folderName) : ''; ?>"/>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="parentId" class="control-label"><?php echo t('edit_folder_parent_folder', 'Parent Folder:'); ?></label>
                    <select class="form-control" name="parentId" id="parentId">
                        <option value="-1"><?php echo t('_none_', '- none -'); ?></option>
                        <?php
                        $currentFolderStr = $editFolderId!==null?$folderListing[$editFolderId]:0;
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
                            if ($parentId == (int) $k)
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
        <?php //echo 'isPublic: '.$editFolderId.' - '.$isPublic; ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="isPublic" class="control-label"><?php echo t('edit_file_privacy', 'File Privacy:'); ?></label>
                    <?php if($userIsPublic == 0): ?>
                    <select class="form-control" name="isPublic" id="isPublic" disabled="disabled">
                    <?php else: ?>                    
                    <select class="form-control" name="isPublic" id="isPublic">
                    <?php endif; ?>
                        <option value="1" <?php echo (($folderIsPublic == 1) ? ' selected' : ''); ?>><?php echo t('privacy_public_access_search', 'Public - shown in search results and if someone knows the url.'); ?></option>
                        <option value="0" <?php echo (($folderIsPublic == 0 || ($userIsPublic == 0 && $folderIsPublic == 1)) ? ' selected' : ''); ?>><?php echo t('privacy_private_no_access', 'Private - no access outside of your account.'); ?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="accessPassword" class="control-label"><?php echo t("edit_folder_optional_password", "Optional Password:"); ?></label>
                    <div class="row">
                        <div class="col-md-2 inline-checkbox">
                            <input type="checkbox" name="enablePassword" id="enablePassword" value="1" <?php echo strlen($accessPassword)?'CHECKED':''; ?> onClick="toggleFolderPasswordField();">
                        </div>
                        <div class="col-md-10">
                            <input type="password" class="form-control" name="password" id="password" autocomplete="off"<?php echo strlen($accessPassword)?' value="**********"':''; ?> <?php echo strlen($accessPassword)?'':'READONLY'; ?>/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <p>
                    <?php
                    if($userIsPublic == 0)
                    {
                        echo t('file_folder_privacy_notice', 'You can not update this [[[FILEFOLDER]]] permissions as your account settings are set to make all files private.', array('FILEFOLDER' => 'folders'));
                    }
                    ?></p>
                </div>
            </div>
        </div>
        <?php elseif($accountLocked == 1): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <div class="input-group">
                        <?php echo t('account_locked_folder_edit_error_message', 'This account has been locked, please unlock the account to regain full functionality.'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php if($accountLocked == 0): ?>
    <div class="modal-footer">
        <input type="hidden" name="submitme" id="submitme" value="1"/>
        <?php if($editFolderId !== null): ?>
        <input type="hidden" value="<?php echo (int) $editFolderId; ?>" name="editFolderId"/>
        <?php endif; ?>
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t("cancel", "cancel"); ?></button>
        <button type="button" class="btn btn-info" onClick="processAjaxForm(this, function(data) { <?php if($editFolderId == null): ?>setUploaderFolderList(data['folder_listing_html']);       loadFolderFiles(data['folder_id']);<?php endif; ?> refreshFolderListing(false); $('.modal').modal('hide'); updateStatsViaAjax(); }); return false;"><?php echo $editFolderId==null?t("add_folder", "add folder"):t("update_folder", "update folder"); ?> <i class="entypo-check"></i></button>
    </div>
    <?php endif; ?>
</form>