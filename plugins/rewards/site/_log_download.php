<?php

// used for NGINX callback on PPD download finish. See the admin area, reward
// plugin settings for more information.
// global includes
define('CLI_MODE', true);
require_once('../../../core/includes/master.inc.php');

// log callback data
log::setContext('plugin_reward_nginx_complete_download');
log::breakInLogFile();
log::info('Data received: ' . http_build_query($_REQUEST));

// ignore if not OK response
//if ((!isset($_REQUEST['status'])) || ((strtoupper($_REQUEST['status']) != 'OK') && (strtoupper($_REQUEST['status']) != '')))
if ((!isset($_REQUEST['status'])) || ((strtoupper($_REQUEST['status']) != 'OK')))
{
    exit;
}

// prepare response for inserting
$downloadTokenExp = explode('download_token=', $_REQUEST['request_uri']);
$downloadToken    = current(explode('&', $downloadTokenExp[1]));
if (!strlen($downloadToken))
{
    exit;
}

// initial variables
$payPPD       = false;

// load file details
$fileSize   = -1;
$fileUserId = NULL;
$fileId     = -1;
$fileDetail = $db->getRow('SELECT file.fileSize, file.userId, file.id FROM file LEFT JOIN download_token ON file.id = download_token.file_id WHERE download_token.token=' . $db->quote($downloadToken) . ' LIMIT 1');
if ($fileDetail)
{
    $fileSize   = $fileDetail['fileSize'];
    $fileUserId = $fileDetail['userId'];
    $fileId     = $fileDetail['id'];
}

// if total bytes sent equals file size pay PPD, NOT ACTUALLY REQUIRED
/*
if ($fileSize == $_REQUEST['body_bytes_sent'])
{
    $payPPD = true;
}
*/

// log in database
$payPPD                   = true;
$dbInsert                 = new DBObject("plugin_reward_ppd_complete_download", array("download_token", "date_added", "download_ip", "bytes_sent", "pay_ppd"));
$dbInsert->download_token = $downloadToken;
$dbInsert->date_added     = coreFunctions::sqlDateTime();
$dbInsert->download_ip    = $_REQUEST['remote_addr'];
//$dbInsert->bytes_sent     = $_REQUEST['body_bytes_sent'];
$dbInsert->bytes_sent     = $fileSize;
$dbInsert->pay_ppd        = (int) $payPPD;
$rowId                    = $dbInsert->insert();

// lookup for partial completed downloads for this token, NOT ACTUALLY REQUIRED
/*
if ($payPPD == false)
{
    $totalDownloadedSize = $db->getValue('SELECT SUM(plugin_reward_ppd_complete_download.bytes_sent) AS total FROM plugin_reward_ppd_complete_download WHERE download_token=' . $db->quote($downloadToken) . ' AND pay_ppd=0');
    if ($totalDownloadedSize == $fileSize)
    {
        $payPPD       = true;
    }
}
*/

if ($payPPD == true)
{
    // check owner isn't the person downloading it, we don't log PPD payments
    $userDetail = $db->getRow('SELECT users.username, users.level_id, users.id FROM users LEFT JOIN download_token ON users.id = download_token.user_id WHERE download_token.token=' . $db->quote($downloadToken) . ' LIMIT 1');
    if ($userDetail)
    {
        $tokenUsername = $userDetail['username'];
        $tokenLevelId  = $userDetail['level_id'];
        $tokenUserId   = $userDetail['id'];

        // check downloader against file owner
        if ($tokenUserId == $fileUserId)
        {
            // log
            log::info('No PPD logged as downloaded by file owner.');
            $payPPD = false;
        }
        elseif ($tokenLevelId >= 20)
        {
            // log
            log::info('No PPD logged as downloaded by admin user.');
            $payPPD = false;
        }
    }
}

// update older records to set paid
if ($payPPD == true)
{
    $db->query('UPDATE plugin_reward_ppd_complete_download SET pay_ppd=1 WHERE pay_ppd=0 AND download_token=' . $db->quote($downloadToken));
}

// log PPD if not file owner
if ($payPPD)
{
    // log
    log::info('Total file downloaded, logging PPD.');
    
    // rebuild plugin cache, needed as 'server' session caches plugin data so any plugin config changes are not applied
    $_SESSION['pluginConfigs'] = pluginHelper::loadPluginConfigurationFiles();

    // include download complete process to actually log the PPD
    pluginHelper::includeAppends('class_file_download_complete.php', array('origin' => '_log_download.php', 'forceDownload'    => true, 'fileOwnerUserId'  => $tokenUserId, 'userLevelId'      => $tokenLevelId, 'file'             => file::loadById($fileId), 'doPluginIncludes' => false, 'ipOverride'=>$_REQUEST['remote_addr']));
}