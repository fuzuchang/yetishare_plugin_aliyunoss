<?php

// Cron task to sync torrent information with uTorrent
// Also imports and deletes any finished torrents.
// Should be run every minute via the command line like this:
// * * * * * php /path/to/yetishare/plugins/torrentdownload/site/track_torrents.cron.php
// This cron script needs to be run on the same server as uTorrent
// Setup uTorrent so it saves in the _tmp folder:
//   /your/home/dir/path/public_html/files/_tmp

// setup includes
define('CURRENT_FILE_PATH', dirname(__FILE__));
require_once (CURRENT_FILE_PATH.'/../../../core/includes/master.inc.php');

// setup logging
log::setContext('plugin_torrentdownload');
log::breakInLogFile();

// php script timeout for long file moves (12 hours)
set_time_limit(60 * 60 * 12);

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('torrentdownload');
$pluginConfig = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
$pluginInstance = pluginHelper::getInstance('torrentdownload');

// first delete any on uTorrent which don't exist in our local database
$localTorrentHashLookup = array();
$localTorrentData = $db->getRows('SELECT id, torrent_hash FROM plugin_torrentdownload_torrent');
if ($localTorrentData)
{
    foreach ($localTorrentData as $localTorrentDataItem)
    {
        $localTorrentHashLookup[] = strtoupper($localTorrentDataItem['torrent_hash']);
    }
}

// utorrent
if($pluginSettings['torrent_server'] == 'utorrent')
{
    // utorrent library
    require_once (PLUGIN_DIRECTORY_ROOT.'torrentdownload/includes/uTorrentRemote.class.php');
    
    // connect
    $uTorrentHost = $pluginSettings['utorrent_host'] . (strlen($pluginSettings['utorrent_port']) ?
        (':' . $pluginSettings['utorrent_port']) : '');
    $uTorrent = new uTorrentRemote($uTorrentHost, $pluginSettings['utorrent_username'],
        $pluginSettings['utorrent_password']);

    $torrentInfo = $uTorrent->GrabTorrents();
    if (is_array($torrentInfo))
    {
        foreach ($torrentInfo as $torrentInfoItem)
        {
            // lookup locally
            if (!in_array(strtoupper($torrentInfoItem[0]), $localTorrentHashLookup))
            {
                // remove torrent from uTorrent
                $uTorrent->ExecAction('removedata', $torrentInfoItem[0]);
                
                // log
                log::info('Removed torrent download on uTorrent as it was not found in our YetiShare database. '.print_r($torrentInfoItem, true));
            }
        }
    }
}
// transmission
elseif($pluginSettings['torrent_server'] == 'transmission')
{
    // transmission library
    require_once (PLUGIN_DIRECTORY_ROOT.'torrentdownload/includes/TransmissionRPC.class.php');

    // connect
    $rpc = new TransmissionRPC('http://'.$pluginSettings['transmission_host'].':'.$pluginSettings['transmission_port'].'/transmission/rpc', $pluginSettings['transmission_username'], $pluginSettings['transmission_password']);
    
    $torrentList = $rpc->get(array(), array("id", "name", "status", "doneDate", "haveValid", "totalSize", "hashString"));
    $torrentListArr = array();
    if(isset($torrentList->arguments->torrents))
    {
        if (COUNT($torrentList->arguments->torrents))
        {
            foreach ($torrentList->arguments->torrents as $torrentListItem)
            {
                // lookup locally
                if (!in_array(strtoupper($torrentListItem->hashString), $localTorrentHashLookup))
                {
                    // remove torrent from transmission
                    $rpc->remove($torrentListItem->hashString, true);
                    
                    // log
                    log::info('Removed torrent download on Transmission as it was not found in our YetiShare database. '.print_r($torrentListItem, true));
                }
            }
        }
    }
}

// sync all data
$localTorrentData = $db->getRows('SELECT id, torrent_hash, user_id FROM plugin_torrentdownload_torrent WHERE save_status=\'downloading\'');

// utorrent
if($pluginSettings['torrent_server'] == 'utorrent')
{
    $torrentInfo = $uTorrent->GrabTorrents();
    if ($localTorrentData)
    {
        foreach ($localTorrentData as $localTorrentDataItem)
        {
            // get hash and lookup from actual torrents
            $torrentHash = $localTorrentDataItem['torrent_hash'];
            foreach ($torrentInfo as $torrentInfoItem)
            {
                // if we've found the hash on uTorrent, sync progress
                if (strtoupper($torrentInfoItem[0]) == $torrentHash)
                {
                    // sync progress
                    $pluginInstance->addUpdateTorrentUtorrent($torrentInfoItem, $localTorrentDataItem['user_id']);
                }
            }
        }
    }
}
// transmission
elseif($pluginSettings['torrent_server'] == 'transmission')
{
    $torrentList = $rpc->get(array(), array("id", "name", "status", "doneDate", "haveValid", "totalSize", "hashString", "status", "name", "totalSize", "percentDone", "downloadedEver", "uploadedEver", "rateDownload", "rateUpload", "leftUntilDone", "peersConnected", "webseeds", "files", "activityDate", "doneDate", "eta", "peers"));
    if ($localTorrentData)
    {
        foreach ($localTorrentData as $localTorrentDataItem)
        {
            // get hash and lookup from actual torrents
            $torrentHash = $localTorrentDataItem['torrent_hash'];
            foreach ($torrentList->arguments->torrents as $torrentInfoItem)
            {
                // if we've found the hash on Transmission, sync progress
                if (strtoupper($torrentInfoItem->hashString) == $torrentHash)
                {
                    // sync progress
                    $pluginInstance->addUpdateTorrentTransmission($torrentInfoItem, $localTorrentDataItem['user_id']);
                }
            }
        }
    }
}

// make sure all torrents are within the account limits
$pluginInstance->validateAccountLimits();

// update any completed downloading
$db->query('UPDATE plugin_torrentdownload_torrent SET save_status=\'pending\' WHERE download_percent=1000 AND save_status=\'downloading\'');

// move any into storage which have a 'save_status' of 'pending'
$finishedItems = $db->getRows('SELECT * FROM plugin_torrentdownload_torrent WHERE save_status=\'pending\' ORDER BY id ASC LIMIT 1');
if ($finishedItems)
{
    foreach ($finishedItems as $finishedItem)
    {
        // log
        log::info('Starting import process. Found finished torrent. '.print_r($finishedItem, true));
        
        // set to processing
        $db->query('UPDATE plugin_torrentdownload_torrent SET save_status=\'processing\' WHERE id=' .
            (int)$finishedItem['id'] . ' LIMIT 1');

        // create folder in users accounts to save files
        $fileNameParts = pathinfo($finishedItem['torrent_name']);
        $folderName = isset($fileNameParts['filename']) ? $fileNameParts['filename'] : $finishedItem['torrent_name']; // reguires PHP v5.2+

        // check for existing folder
        $rs = $db->getRow('SELECT id FROM file_folder WHERE folderName = ' . $db->quote
            ($folderName) . ' AND userId = ' . (int)$finishedItem['user_id']);
        if ($rs)
        {
            $folderName .= ' (' . date('H:i:s') . ')';
        }

        // add folder
        $db->query('INSERT INTO file_folder (folderName, isPublic, userId, parentId, accessPassword) VALUES (:folderName, :isPublic, :userId, :parentId, :accessPassword)',
            array(
            'folderName' => $folderName,
            'isPublic' => 0,
            'userId' => (int)$finishedItem['user_id'],
            'parentId' => null,
            'accessPassword' => ''));

        // get folder id
        $folderId = (int)$db->getValue('SELECT id FROM file_folder WHERE folderName=' .
            $db->quote($folderName) . ' AND userId=' . (int)$finishedItem['user_id'] .
            ' LIMIT 1');
            
        // log
        log::info('Created new directory for torrent contents called "'.$folderName.'".');

        // get actual file list
        $fileArr = array();
        
        // utorrent
        if($pluginSettings['torrent_server'] == 'utorrent')
        {
            $fileList = $uTorrent->GrabListOfFiles($finishedItem['torrent_hash']);
            foreach ($fileList[1] as $fileListItem)
            {
                $fileArr[] = $finishedItem['save_path'] . '/' . $fileListItem[0];
            }
        }       
        // transmission
        elseif($pluginSettings['torrent_server'] == 'transmission')
        {
            $torrentList = $rpc->get(array(), array("id", "name", "status", "doneDate", "haveValid", "totalSize", "hashString", "files", "downloadDir"));
            foreach ($torrentList->arguments->torrents as $torrentInfoItem)
            {
                // check if we have a match
                if (strtoupper($torrentInfoItem->hashString) == strtoupper($finishedItem['torrent_hash']))
                {
                    // get list of files
                    if (COUNT($torrentInfoItem->files))
                    {
                        // store in array
                        foreach ($torrentInfoItem->files as $torrentFile)
                        {
                            $fileArr[] = $torrentInfoItem->downloadDir . '/' . $torrentFile->name;
                        }
                    }
                }
            }
        }
        
        // log
        log::info('List of files to import from torrent: '.print_r($finishedItem, true).' ||| '.print_r($fileArr, true).'.');

        // move into storage
        if (COUNT($fileArr))
        {
            // consider any other users also downloading it
            //$otherUsersDownloading = $db->getRows('SELECT id, user_id FROM plugin_torrentdownload_torrent WHERE torrent_hash='.$db->quote($finishedItem['torrent_hash']).' AND (save_status=\'downloading\' OR save_status=\'pending\') AND id !='.(int)$finishedItem['id']);

            // loop files
            foreach ($fileArr as $fileArrItem)
            {
                // log
                log::info('Attemptimg import of file: '.$fileArrItem);
                
                $fileUpload = new stdClass();
                $realFilename = trim(end(explode('/', $fileArrItem)));
                $fileUpload->name = $realFilename;
                $fileUpload->size = filesize($fileArrItem);
                $mimeType = file::estimateMimeTypeFromExtension($fileUpload->name,
                    'application/octet-stream');
                if (($mimeType == 'application/octet-stream') && (class_exists('finfo', false)))
                {
                    $finfo = new finfo;
                    $mimeType = $finfo->file($fileArrItem, FILEINFO_MIME);
                }
                $fileUpload->type = $mimeType;

                $uploader = new uploader(
                    array(
                         'folder_id' => (int) $folderId,
                         'user_id'   => (int) $finishedItem['user_id'],
                    )
                );
                $fileUpload = $uploader->moveIntoStorage($fileUpload, $fileArrItem);
                
                // log
                log::setContext('plugin_torrentdownload');
                log::info('Import result: '.print_r($fileUpload, true));

                // success
                if ($fileUpload->error === null)
                {
                    // update folder
                    $shortUrl = $fileUpload->short_url;
                    $db->query('UPDATE file SET userId=' . (int)$finishedItem['user_id'] .
                        ', folderId=' . (int)$folderId . ' WHERE shortUrl=' . $db->quote($shortUrl) .
                        ' LIMIT 1');
                }
            }
        }
        else
        {
            // log
            log::error('No files found to import on torrent: '.print_r($finishedItem, true).' ||| '.print_r($fileArr, true).'.');
        }

        // set to complete
        $db->query('UPDATE plugin_torrentdownload_torrent SET save_status=\'complete\', status=\'Stopped\', date_completed=NOW() WHERE id=' .
            (int)$finishedItem['id'] . ' LIMIT 1');

        // log
        log::info('Finished import file process.');
            
        // utorrent
        if($pluginSettings['torrent_server'] == 'utorrent')
        {
            // remove torrent from uTorrent
            $uTorrent->ExecAction('removedata', $finishedItem['torrent_hash']);
            
            // log
            log::info('Removed torrent via uTorrent.');
        }
        // transmission
        elseif($pluginSettings['torrent_server'] == 'transmission')
        {
            // remove torrent from transmission
            $rpc->remove($torrentListItem->hashString, true);
            
            // log
            log::info('Removed torrent via Transmission.');
        }
        
        // log
        log::info('End of process. '.print_r($finishedItem, true));
        log::breakInLogFile();
    }
}

// clean up old torrent data
define('TORRENT_PLUGIN_KEEP_DATA_DAYS', 90);
$db->query("DELETE FROM plugin_torrentdownload_torrent_file WHERE torrent_id IN (SELECT id FROM plugin_torrentdownload_torrent WHERE date_completed < DATE_SUB(NOW(), INTERVAL ".TORRENT_PLUGIN_KEEP_DATA_DAYS." day) AND save_status = 'complete')");
$db->query("DELETE FROM plugin_torrentdownload_torrent WHERE date_completed < DATE_SUB(NOW(), INTERVAL ".TORRENT_PLUGIN_KEEP_DATA_DAYS." day) AND save_status = 'complete'");
