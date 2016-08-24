<?php

// setup includes
require_once ('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('torrentdownload');
$pluginConfig = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
$pluginInstance = pluginHelper::getInstance('torrentdownload');

// setup initial params
$result = array();
$result['error'] = false;
$result['msg'] = '';
$db = Database::getDatabase(true);

$torrentUrl = trim($_REQUEST['urlTorrentList']);
$torrentFile = $_FILES['torrentFile'];
if ((strlen($torrentUrl) == 0) && (strlen($torrentFile['name']) == 0))
{
    $result['error'] = true;
    $result['msg'] = 'No torrent url or file attached.';
}
else
{
    // check user has not reached their concurrent limits for downloading torrents
    $allowedConcurrent = (int)$pluginSettings['max_concurrent_free'];
    if ($Auth->level_id >= 2)
    {
        $allowedConcurrent = (int)$pluginSettings['max_concurrent_paid'];
    }
    $concurrentTorrents = $db->getValue('SELECT COUNT(id) AS total FROM plugin_torrentdownload_torrent WHERE (save_status = \'downloading\' AND user_id=' .
        (int)$Auth->id . ')');
    if ($concurrentTorrents >= $allowedConcurrent)
    {
        $result['error'] = true;
        $result['msg'] = 'You have reached the maximum permitted concurrent torrent downloads for your account type. Please try again when some have completed downloading.';
    }

    // check user has not reached their daily limits for downloading torrents
    if ($result['error'] == false)
    {
        $allowedDaily = (int)$pluginSettings['max_torrents_per_day_free'];
        if ($Auth->level_id >= 2)
        {
            $allowedDaily = (int)$pluginSettings['max_torrents_per_day_paid'];
        }
        $daysTorrents = $db->getValue('SELECT COUNT(id) AS total FROM plugin_torrentdownload_torrent WHERE user_id=' .
            (int)$Auth->id . ' AND date_added >= NOW() - INTERVAL 1 DAY');
        if ($daysTorrents >= $allowedDaily)
        {
            $result['error'] = true;
            $result['msg'] = 'You have reached the maximum permitted daily torrent downloads for your account type. Please try again later.';
        }
    }

    // check url
    if ($result['error'] == false)
    {
        if (strlen($torrentUrl) > 0)
        {
            // extract hash from magnet link
            preg_match('#magnet:\?xt=urn:btih:(?<hash>.*?)&dn=(?<filename>.*?)&tr=(?<trackers>.*?)$#',
                $torrentUrl, $torrentUrlParts);
            $torrentHash = $torrentUrlParts['hash'];
            if (strlen($torrentHash) == 0)
            {
                $result['error'] = true;
                $result['msg'] = 'Torrent url is invalid, please check and try again.';
            }
        }
    }

    // check file
    if (($result['error'] == false) && ($torrentFile['size'] > 1024000))
    {
        $result['error'] = true;
        $result['msg'] = 'Torrent file is too large.';
    }

    if ($result['error'] == false)
    {
        // utorrent
        if($pluginSettings['torrent_server'] == 'utorrent')
        {
            // utorrent library
            require_once ('../includes/uTorrentRemote.class.php');
    
            // add torrent
            $uTorrentHost = $pluginSettings['utorrent_host'] . (strlen($pluginSettings['utorrent_port']) ?
                (':' . $pluginSettings['utorrent_port']) : '');
            $uTorrent = new uTorrentRemote($uTorrentHost, $pluginSettings['utorrent_username'],
                $pluginSettings['utorrent_password']);
            if (strlen($torrentUrl) > 0)
            {
                // torrent url
                $torrentInfo = $uTorrent->ExecAction('add-url', null, 0, 0, $torrentUrl);
            }
            else
            {
                // get original torrent list
                $torrentList = $uTorrent->GrabTorrents();
                $torrentListArr = array();
                if (COUNT($torrentList))
                {
                    foreach ($torrentList as $torrentListItem)
                    {
                        $torrentListArr[] = $torrentListItem[0];
                    }
                }
    
                // file
                $torrentInfo = $uTorrent->ExecAction('add-file', null, 0, 0, $torrentFile['tmp_name']);
                if ($torrentInfo)
                {
                    $torrentInfoArr = json_decode($torrentInfo, true);
                    if (isset($torrentInfoArr['error']))
                    {
                        $result['error'] = true;
                        $result['msg'] = $torrentInfoArr['error'];
                    }
                }
    
                // get torrent hash for later on
                $torrentInfo = $uTorrent->GrabTorrents();
                if (is_array($torrentInfo))
                {
                    if (COUNT($torrentListArr) == COUNT($torrentInfo))
                    {
                        $result['error'] = true;
                        $result['msg'] = 'Failed adding torrent file, or it may already be being downloaded. Please try again later.';
                    }
                    else
                    {
                        foreach ($torrentInfo as $torrentInfoItem)
                        {
                            if (!in_array($torrentInfoItem[0], $torrentListArr))
                            {
                                $torrentHash = $torrentInfoItem[0];
                            }
                        }
                    }
                }
            }
    
            if ($result['error'] == false)
            {
                // lookup to see if it was added
                $torrentInfo = $uTorrent->GrabTorrents();
                if (!is_array($torrentInfo))
                {
                    $result['error'] = true;
                    $result['msg'] = 'Error: Problem getting response from uTorrent on host ' . $uTorrentHost .
                        '. Please check the host, access details and that utorrent is running.';
                }
                else
                {
                    foreach ($torrentInfo as $torrentInfoItem)
                    {
                        if (strtoupper($torrentInfoItem[0]) == strtoupper($torrentHash))
                        {
                            $rs = $pluginInstance->addUpdateTorrentUtorrent($torrentInfoItem, $Auth->id);
                            if (!$rs)
                            {
                                $result['error'] = true;
                                $result['msg'] = 'Error: Failed scheduling torrent, please try again later.';
                            }
                        }
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
            
            // add by torrent/magnet url
            if (strlen($torrentUrl) > 0)
            {
                // add torrent
                $resultTrans = $rpc->add($torrentUrl);
                $torrentId = $resultTrans->arguments->torrent_added->id; // hashString also available
            }
            // add by torrent file data
            else
            {
                // get original torrent list
                $torrentList = $rpc->get(array(), array("id", "name", "status", "doneDate", "haveValid", "totalSize", "hashString"));
                $torrentListArr = array();
                if(isset($torrentList->arguments->torrents))
                {
                    if (COUNT($torrentList->arguments->torrents))
                    {
                        foreach ($torrentList->arguments->torrents as $torrentListItem)
                        {
                            $torrentListArr[] = $torrentListItem->hashString;
                        }
                    }
                }

                // file
                $resultTrans = $rpc->add_metainfo(file_get_contents($torrentFile['tmp_name']));
                $torrentId = $resultTrans->arguments->torrent_added->id; // hashString also available
                if (!$resultTrans)
                {
                    $result['error'] = true;
                    $result['msg'] = 'Failed adding torrent. Please try again later';
                }
    
                // get torrent hash for later on
                $torrentInfo = $rpc->get(array(), array("id", "name", "status", "doneDate", "haveValid", "totalSize", "hashString"));
                if(isset($torrentInfo->arguments->torrents))
                {
                    if (COUNT($torrentListArr) == COUNT($torrentInfo->arguments->torrents))
                    {
                        $result['error'] = true;
                        $result['msg'] = 'Failed adding torrent file, or it may already be being downloaded. Please try again later.';
                    }
                    else
                    {
                        foreach ($torrentInfo->arguments->torrents as $torrentInfoItem)
                        {
                            if (!in_array($torrentInfoItem->hashString, $torrentListArr))
                            {
                                $torrentHash = $torrentInfoItem->hashString;
                            }
                        }
                    }
                }
            }

            if ($result['error'] == false)
            {
                // lookup to see if it was added
                $torrentInfo = $rpc->get(array(), array("id", "name", "status", "doneDate", "haveValid", "totalSize", "hashString", "status", "name", "totalSize", "percentDone", "downloadedEver", "uploadedEver", "rateDownload", "rateUpload", "leftUntilDone", "peersConnected", "webseeds", "files"));
                if(!isset($torrentInfo->arguments->torrents))
                {
                    $result['error'] = true;
                    $result['msg'] = 'Error: Problem getting response from Transmission on host ' . $pluginSettings['transmission_host'] .
                        '. Please check the host, access details and that Transmission is running.';
                }
                else
                {
                    foreach ($torrentInfo->arguments->torrents as $torrentInfoItem)
                    {
                        if (strtoupper($torrentInfoItem->hashString) == strtoupper($torrentHash))
                        {
                            $rs = $pluginInstance->addUpdateTorrentTransmission($torrentInfoItem, $Auth->id);
                            if (!$rs)
                            {
                                $result['error'] = true;
                                $result['msg'] = 'Error: Failed scheduling torrent, please try again later.';
                            }
                        }
                    }
                }
            }
        }
    }
}

echo json_encode($result);
