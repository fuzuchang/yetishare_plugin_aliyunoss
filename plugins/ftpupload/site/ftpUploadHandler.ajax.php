<?php

error_reporting(E_ALL | E_STRICT);

// setup includes
require_once('../../../core/includes/master.inc.php');

// for cross domain access
coreFunctions::allowCrossSiteAjax();

header('Content-Disposition: inline; filename="files.json"');

// process csaKeys and authenticate user
$csaKey1 = trim($_REQUEST['csaKey1']);
$csaKey2 = trim($_REQUEST['csaKey2']);
if(strlen($csaKey1) && strlen($csaKey1))
{
    crossSiteAction::setAuthFromKeys($csaKey1, $csaKey2, false);
}

// require login
$Auth->requireUser(WEB_ROOT.'/login.'.SITE_CONFIG_PAGE_EXTENSION);

// get url
$fileName = trim($_REQUEST['fileName']);
$rowId = (int)$_REQUEST['rowId'];

// double check user is logged in if required
$Auth = Auth::getAuth();
if (UserPeer::getAllowedToUpload() == false)
{
    echo coreFunctions::createUploadError(t('unavailable', 'Unavailable.'), t('uploading_has_been_disabled', 'Uploading has been disabled.'));
    exit;
}

// check for banned ip
$bannedIP = bannedIP::getBannedType();
if (strtolower($bannedIP) == "uploading")
{
    echo coreFunctions::createUploadError(t('unavailable', 'Unavailable.'), t('uploading_has_been_disabled', 'Uploading has been disabled.'));
    exit;
}

// check that the user has not reached their max permitted uploads
$fileRemaining = UserPeer::getRemainingFilesToday();
if($fileRemaining == 0)
{
    echo coreFunctions::createUploadError(t('max_uploads_reached', 'Max uploads reached.'), t('reached_maximum_uploads', 'You have reached the maximum permitted uploads for today.'));
    exit;
}

// check the user hasn't reached the maximum storage on their account
if((UserPeer::getAvailableFileStorage($Auth->id) !== null) && UserPeer::getAvailableFileStorage($Auth->id) <= 0)
{
    echo coreFunctions::createUploadError(t('file_upload_space_full', 'File upload space full.'), t('file_upload_space_full_text', 'Upload storage full, please delete some active files and try again.'));
    exit;
}

$pluginObj = pluginHelper::getInstance('ftpupload');
$pluginObj->handleFileTransfer($fileName, $Auth->id, $rowId);
