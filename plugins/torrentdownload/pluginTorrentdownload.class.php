<?php

class PluginTorrentdownload extends Plugin
{

    public $config = null;
    public $data = null;
    public $settings = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include (DOC_ROOT . '/plugins/torrentdownload/_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
        $this->data = $db->getRow('SELECT * FROM plugin WHERE folder_name = ' . $db->
            quote($this->config['folder_name']) . ' LIMIT 1');
        if ($this->data)
        {
            $this->settings = json_decode($this->data['plugin_settings'], true);
        }
    }

    public function getPluginDetails()
    {
        return $this->config;
    }

    public function uninstall()
    {
        // setup database
        $db = Database::getDatabase();

        // remove plugin specific tables
        $sQL = 'DROP TABLE plugin_torrentdownload_torrent';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_torrentdownload_torrent_file';
        $db->query($sQL);

        return parent::uninstall();
    }

    public function connectUTorrent()
    {
        // utorrent library
        require_once (PLUGIN_DIRECTORY_ROOT.'torrentdownload/includes/uTorrentRemote.class.php');

        // connect torrent
        $uTorrentHost = $this->settings['utorrent_host'] . (strlen($this->settings['utorrent_port']) ?
            (':' . $this->settings['utorrent_port']) : '');
        $uTorrent = new uTorrentRemote($uTorrentHost, $this->settings['utorrent_username'],
            $this->settings['utorrent_password']);

        return $uTorrent;
    }
    
    public function connectTransmission()
    {
        // transmission library
        require_once (PLUGIN_DIRECTORY_ROOT.'torrentdownload/includes/TransmissionRPC.class.php');

        // connect transmission
        $rpc = new TransmissionRPC('http://'.$this->settings['transmission_host'].':'.$this->settings['transmission_port'].'/transmission/rpc', $this->settings['transmission_username'], $this->settings['transmission_password']);

        return $rpc;
    }

    public function addUpdateTorrentUtorrent($torrentDetail, $userId)
    {
        // setup database
        $db = Database::getDatabase();

        // get torrent hash for lookups
        $torrentHash = strtoupper($torrentDetail[0]);

        // connect uTorrent
        $uTorrent = $this->connectUTorrent();

        // check for update or add
        $existingTorrent = $db->getRow('SELECT id FROM plugin_torrentdownload_torrent WHERE user_id = ' .
            (int)$userId . ' AND torrent_hash = ' . $db->quote($torrentHash) .
            ' AND save_status=\'downloading\' LIMIT 1');
        if ($existingTorrent)
        {
            // update torrent details
            $dbQuery = new DBObject("plugin_torrentdownload_torrent", array(
                "status",
                "torrent_name",
                "torrent_size",
                "download_percent",
                "downloaded",
                "uploaded",
                "download_speed",
                "upload_speed",
                "time_remaining",
                "save_path",
                "peers_connected",
                "peers_in_swarm",
                "seeds_connected",
                "seeds_in_swarm"), 'id');
            $dbQuery->id = $existingTorrent['id'];
            $dbQuery->status = $torrentDetail[21];
            $dbQuery->torrent_name = substr($torrentDetail[2], 0, 255);
            $dbQuery->torrent_size = $torrentDetail[3];
            $dbQuery->download_percent = $torrentDetail[4];
            $dbQuery->downloaded = $torrentDetail[5];
            $dbQuery->uploaded = $torrentDetail[6];
            $dbQuery->download_speed = $torrentDetail[8];
            $dbQuery->upload_speed = $torrentDetail[9];
            $dbQuery->time_remaining = $torrentDetail[10];
            $dbQuery->save_path = $torrentDetail[26];
            $dbQuery->peers_connected = $torrentDetail[12];
            $dbQuery->peers_in_swarm = $torrentDetail[13];
            $dbQuery->seeds_connected = $torrentDetail[14];
            $dbQuery->seeds_in_swarm = $torrentDetail[15];

            $rs = $dbQuery->update() === false ? false : true;

            $torrentFiles = $this->getUTorrentFileListing($torrentHash);
            if (COUNT($torrentFiles[1]))
            {
                // delete any existing
                $db->query('DELETE FROM plugin_torrentdownload_torrent_file WHERE torrent_id=' .
                    (int)$existingTorrent['id']);

                // add new
                foreach ($torrentFiles[1] as $torrentFile)
                {
                    // add torrent details into database
                    $dbQuery = new DBObject("plugin_torrentdownload_torrent_file", array(
                        "torrent_id",
                        "file_name",
                        "filesize"));
                    $dbQuery->torrent_id = substr($existingTorrent['id'], 0, 255);
                    $dbQuery->file_name = $torrentFile[0];
                    $dbQuery->filesize = $torrentFile[1];
                    $dbQuery->insert();
                }
            }

            return $rs;
        }
        else
        {
            // add torrent details into database
            $dbQuery = new DBObject("plugin_torrentdownload_torrent", array(
                "user_id",
                "torrent_hash",
                "date_added",
                "status",
                "torrent_name",
                "torrent_size",
                "download_percent",
                "downloaded",
                "uploaded",
                "download_speed",
                "upload_speed",
                "time_remaining",
                "save_path",
                "peers_connected",
                "peers_in_swarm",
                "seeds_connected",
                "seeds_in_swarm"));
            $dbQuery->user_id = (int)$userId;
            $dbQuery->torrent_hash = $torrentHash;
            $dbQuery->date_added = date("Y-m-d H:i:s", time());
            $dbQuery->status = $torrentDetail[21];
            $dbQuery->torrent_name = substr($torrentDetail[2], 0, 255);
            $dbQuery->torrent_size = $torrentDetail[3];
            $dbQuery->download_percent = $torrentDetail[4];
            $dbQuery->downloaded = $torrentDetail[5];
            $dbQuery->uploaded = $torrentDetail[6];
            $dbQuery->download_speed = $torrentDetail[8];
            $dbQuery->upload_speed = $torrentDetail[9];
            $dbQuery->time_remaining = $torrentDetail[10];
            $dbQuery->save_path = $torrentDetail[26];
            $dbQuery->peers_connected = $torrentDetail[12];
            $dbQuery->peers_in_swarm = $torrentDetail[13];
            $dbQuery->seeds_connected = $torrentDetail[14];
            $dbQuery->seeds_in_swarm = $torrentDetail[15];

            $rs = $dbQuery->insert();

            return $rs;
        }
    }
    
    public function addUpdateTorrentTransmission($torrentDetail, $userId)
    {
        // setup database
        $db = Database::getDatabase();

        // get torrent hash for lookups
        $torrentHash = strtoupper($torrentDetail->hashString);
        
        // connect transmission
        $rpc = $this->connectTransmission();
        
        // precent progress
        $percent = floor(($torrentDetail->downloadedEver/$torrentDetail->totalSize)*1000);
        if($percent > 1000)
        {
            $percent = 1000;
        }

        // check for update or add
        $existingTorrent = $db->getRow('SELECT id FROM plugin_torrentdownload_torrent WHERE user_id = ' .
            (int)$userId . ' AND torrent_hash = ' . $db->quote($torrentHash) .
            ' AND save_status=\'downloading\' LIMIT 1');
        if ($existingTorrent)
        {
            // update torrent details
            $dbQuery = new DBObject("plugin_torrentdownload_torrent", array(
                "status",
                "torrent_name",
                "torrent_size",
                "download_percent",
                "downloaded",
                "uploaded",
                "download_speed",
                "upload_speed",
                "time_remaining",
                "save_path",
                "peers_connected",
                "peers_in_swarm",
                "seeds_connected",
                "seeds_in_swarm"), 'id');
            $dbQuery->id = $existingTorrent['id'];
            $dbQuery->status = $rpc->getStatusString($torrentDetail->status);
            $dbQuery->torrent_name = substr($torrentDetail->name, 0, 255);
            $dbQuery->torrent_size = $torrentDetail->totalSize;
            $dbQuery->download_percent = $percent;
            $dbQuery->downloaded = $torrentDetail->downloadedEver;
            $dbQuery->uploaded = $torrentDetail->uploadedEver;
            $dbQuery->download_speed = $torrentDetail->rateDownload;
            $dbQuery->upload_speed = $torrentDetail->rateUpload;
            $dbQuery->time_remaining = $torrentDetail->eta;
            $dbQuery->save_path = $torrentDetail->downloadDir;
            $dbQuery->peers_connected = $torrentDetail->peersConnected;
            $dbQuery->peers_in_swarm = COUNT($torrentDetail->peers);
            $dbQuery->seeds_connected = COUNT($torrentDetail->webseeds);
            $dbQuery->seeds_in_swarm = 0;

            $rs = $dbQuery->update() === false ? false : true;

            if (COUNT($torrentDetail->files))
            {
                // delete any existing
                $db->query('DELETE FROM plugin_torrentdownload_torrent_file WHERE torrent_id=' .
                    (int)$existingTorrent['id']);

                // add new
                foreach ($torrentDetail->files as $torrentFile)
                {
                    // add torrent details into database
                    $dbQuery = new DBObject("plugin_torrentdownload_torrent_file", array(
                        "torrent_id",
                        "file_name",
                        "filesize"));
                    $dbQuery->torrent_id = $existingTorrent['id'];
                    $dbQuery->file_name = $torrentFile->name;
                    $dbQuery->filesize = $torrentFile->length;
                    $dbQuery->insert();
                }
            }

            return $rs;
        }
        else
        {
            // add torrent details into database
            $dbQuery = new DBObject("plugin_torrentdownload_torrent", array(
                "user_id",
                "torrent_hash",
                "date_added",
                "status",
                "torrent_name",
                "torrent_size",
                "download_percent",
                "downloaded",
                "uploaded",
                "download_speed",
                "upload_speed",
                "time_remaining",
                "save_path",
                "peers_connected",
                "peers_in_swarm",
                "seeds_connected",
                "seeds_in_swarm"));
            $dbQuery->user_id = (int)$userId;
            $dbQuery->torrent_hash = $torrentHash;
            $dbQuery->date_added = date("Y-m-d H:i:s", time());
            $dbQuery->status = $rpc->getStatusString($torrentDetail->status);
            $dbQuery->torrent_name = substr($torrentDetail->name, 0, 255);
            $dbQuery->torrent_size = $torrentDetail->totalSize;
            $dbQuery->download_percent = $percent;
            $dbQuery->downloaded = $torrentDetail->downloadedEver;
            $dbQuery->uploaded = $torrentDetail->uploadedEver;
            $dbQuery->download_speed = $torrentDetail->rateDownload;
            $dbQuery->upload_speed = $torrentDetail->rateUpload;
            $dbQuery->time_remaining = $torrentDetail->eta;
            $dbQuery->save_path = $torrentDetail->downloadDir;
            $dbQuery->peers_connected = $torrentDetail->peersConnected;
            $dbQuery->peers_in_swarm = COUNT($torrentDetail->peers);
            $dbQuery->seeds_connected = COUNT($torrentDetail->webseeds);
            $dbQuery->seeds_in_swarm = 0;

            $rs = $dbQuery->insert();

            return $rs;
        }
    }

    public function getUTorrentFileListing($torrentHash)
    {
        // connect
        $uTorrent = $this->connectUTorrent();

        // get files
        return $uTorrent->GrabListOfFiles($torrentHash);
    }

    public function validateAccountLimits()
    {
        // setup database
        $db = Database::getDatabase();
        
        // load plugin details
        $pluginDetails = pluginHelper::pluginSpecificConfiguration('torrentdownload');
        $pluginConfig = $pluginDetails['config'];
        $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
        $pluginInstance = pluginHelper::getInstance('torrentdownload');

        // get all pending/download torrents
        $localTorrentData = $db->getRows('SELECT users.level_id, plugin_torrentdownload_torrent.id AS torrent_id, plugin_torrentdownload_torrent.torrent_size, plugin_torrentdownload_torrent.torrent_hash, plugin_torrentdownload_torrent.user_id FROM plugin_torrentdownload_torrent LEFT JOIN users ON plugin_torrentdownload_torrent.user_id = users.id WHERE plugin_torrentdownload_torrent.save_status=\'downloading\' AND plugin_torrentdownload_torrent.torrent_size > 0');
        if($localTorrentData)
        {
            foreach($localTorrentData AS $localTorrentItem)
            {
                $cancelTorrent = false;
                $cancelReason = null;
 
                // make sure the user account wont go over storage limits
                $availableStorage = UserPeer::getAvailableFileStorage($localTorrentItem['user_id']);
                
                // allow for unlimited storage (return value is null)
                if($availableStorage !== null)
                {
                    if($localTorrentItem['torrent_size'] > $availableStorage)
                    {
                        $cancelTorrent = true;
                        $cancelReason = 'Error: Torrent is larger than the available space within the account.';
                    }
                }

                if($cancelTorrent == false)
                {
                    // if we should check filesize against account limits
                    if((int)$pluginSettings['use_max_upload_settings'] == 1)
                    {
                        // load user object so we can access the account level
                        $user = UserPeer::loadUserById($localTorrentItem['user_id']);

                        // make sure the user account is allowed to upload a file this size
                        $maxUploadSize = UserPeer::getMaxUploadFilesize($user->level_id);
                        if(($maxUploadSize > 0) && ($localTorrentItem['torrent_size'] > $maxUploadSize)) 
                        {
                            $cancelTorrent = true;
                            $cancelReason = 'Error: Torrent is larger that your max permitted upload size.';
                        }
                    }
                }
                
                // cancel torrent
                if($cancelTorrent == true)
                {
                    $this->failTorrent($localTorrentItem['torrent_id'], $cancelReason);
                }
            }
        }
    }
    
    public function failTorrent($torrentId, $reason = null)
    {
        // setup database
        $db = Database::getDatabase();
        
        // load torrent details
        $torrentData = $db->getRow('SELECT * FROM plugin_torrentdownload_torrent WHERE id=' .
            (int)$torrentId . ' LIMIT 1');
        if ($torrentData)
        {
            // load plugin details
            $pluginDetails = pluginHelper::pluginSpecificConfiguration('torrentdownload');
            $pluginConfig = $pluginDetails['config'];
            $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
            $pluginInstance = pluginHelper::getInstance('torrentdownload');

            // utorrent
            if($pluginSettings['torrent_server'] == 'utorrent')
            {
                // remove torrent from uTorrent
                $uTorrent = $this->connectUTorrent();
                $uTorrent->ExecAction('removedata', $torrentData['torrent_hash']);
            }
            // transmission
            elseif($pluginSettings['torrent_server'] == 'transmission')
            {
                // remove torrent from transmission
                $rpc = $this->connectTransmission();
                $rpc->remove($torrentListItem->hashString, true);
            }
    
            // delete local record
            $db->query('DELETE FROM plugin_torrentdownload_torrent_file WHERE torrent_id = :id',
                array('id' => $torrentData['id']));
            $db->query('UPDATE plugin_torrentdownload_torrent SET save_status = "cancelled", status_notes = :status_notes, date_completed = NOW() WHERE id = :id', array('id' =>
                    $torrentData['id'], 'status_notes'=>$reason));
        }
        
        return true;
    }
}
