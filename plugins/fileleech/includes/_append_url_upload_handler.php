<?php

// some settings
//define('PLOWDOWN_PATH', 'plowdown');
define('PLOWDOWN_PATH', '/usr/local/bin/plowdown');

// get database connection
$db = Database::getDatabase();

// get url sections
$urlParts = parse_url($params['url']);
$siteName = strtolower($urlParts['host']);

if($params['rowId'])
{
    $rowId = (int)$params['rowId'];
}
else
{
    $rowId = (int)$_REQUEST['rowId'];
}

// check if we can leech the file
$foundSiteDetails = null;
$siteDetails      = $db->getRows('SELECT * FROM plugin_fileleech_site');
foreach ($siteDetails AS $siteDetail)
{
    if (strpos($siteName, strtolower($siteDetail['site_url'])) !== false)
    {
        $foundSiteDetails = $siteDetail;
    }
}

if ($foundSiteDetails !== null)
{
    // get user
    $Auth = Auth::getAuth();

    // load plugin details
    $pluginConfig   = pluginHelper::pluginSpecificConfiguration('fileleech');
    $pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

    // make sure the user is allowed to leech files
    $userAllowed = false;
    if (($Auth->level_id == 0) && ($pluginSettings['enabled_non_user'] == 1))
    {
        $userAllowed = true;
    }
    elseif (($Auth->level_id == 1) && ($pluginSettings['enabled_free_user'] == 1))
    {
        $userAllowed = true;
    }
    elseif (($Auth->level_id >= 2) && ($pluginSettings['enabled_paid_user'] == 1))
    {
        $userAllowed = true;
    }
    
    // make sure the user isn't above their leech limits
    if($userAllowed == true)
    {
        switch($Auth->level_id)
        {
            case 0:
                $leechDownloadTrafficLimit = (int)$pluginSettings['max_download_traffic_non_user'];
                $leechDownloadVolumeLimit = (int)$pluginSettings['max_download_volume_non_user'];
                break;
            case 1:
                $leechDownloadTrafficLimit = (int)$pluginSettings['max_download_traffic_free_user'];
                $leechDownloadVolumeLimit = (int)$pluginSettings['max_download_volume_free_user'];
                break;
            default:
                $leechDownloadTrafficLimit = (int)$pluginSettings['max_download_traffic_paid_user'];
                $leechDownloadVolumeLimit = (int)$pluginSettings['max_download_volume_paid_user'];
                break;
        }
        
        // get total already downloaded based on IP
        $restrictionError = null;
        $totalFilesize24Hour  = (int) $db->getValue('SELECT SUM(filesize) FROM plugin_fileleech_download WHERE user_ip_address = ' . $db->quote(coreFunctions::getUsersIPAddress()) . ' AND date_download > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
        if(($leechDownloadTrafficLimit > 0) && ($leechDownloadTrafficLimit*1048576 <= $totalFilesize24Hour))
        {
            $restrictionError = t('plugin_fileleech_leech_limit_reached', 'Leeching download size reached for today.');
        }
        else
        {
            $totalDownloads24Hour = (int) $db->getValue('SELECT COUNT(id) FROM plugin_fileleech_download WHERE user_ip_address = ' . $db->quote(coreFunctions::getUsersIPAddress()) . ' AND date_download > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
            if(($leechDownloadVolumeLimit > 0) && ($leechDownloadVolumeLimit <= $totalDownloads24Hour))
            {
                $restrictionError = t('plugin_fileleech_leech_volume_reached', 'Leeching volume reached for today.');
            }
        }
        
        // end with error
        if($restrictionError !== null)
        {
            $upload_handler = new uploader();
            $fileUploadError          = coreFunctions::createUploadError(t('plugin_fileleech_error', 'Error!'), $restrictionError);
            $fileUploadError          = json_decode($fileUploadError, true);
            $fileUploadError          = $fileUploadError[0];
            $fileUploadError['rowId'] = $rowId;
            $upload_handler->remote_url_event_callback(array("done" => $fileUploadError));
            exit;
        }
    }

    // user can leech
    if ($userAllowed == true)
    {
        // setup logging
        log::setContext('plugin_fileleech');
         
        // make imput safe
		$safeUrl = $params['url'];
		$safeUrl = str_replace('&&', '', $safeUrl);
		$safeUrl = str_replace(';', '', $safeUrl);
        
        // load random login details
        $cmd        = null;
        $totalItems = (int) $db->getValue('SELECT COUNT(id) FROM plugin_fileleech_access_detail WHERE site_id = ' . (int) $foundSiteDetails['id']);
        if ($totalItems > 0)
        {
            // get random
            $loginDetails = $db->getRow('SELECT * FROM plugin_fileleech_access_detail WHERE site_id = ' . (int) $foundSiteDetails['id'] . ' LIMIT ' . (rand(1, $totalItems) - 1) . ', 1');

            // create download url for plowdown
            $cmd = PLOWDOWN_PATH.' --max-retries=5 --no-plowsharerc --timeout=60 --temp-rename --output-directory=' . _CONFIG_FILE_STORAGE_PATH . '_tmp/ -a ' . $loginDetails['username'] . ':' . $loginDetails['password'] . ' ' . $safeUrl . ' 2>&1';
        }

        // fallback
        if ($cmd === null)
        {
            // prepare command
            $cmd = PLOWDOWN_PATH.' --max-retries=5 --no-plowsharerc --timeout=60 --temp-rename --output-directory=' . _CONFIG_FILE_STORAGE_PATH . '_tmp/ ' . $safeUrl . ' 2>&1';
        }

        // attempt to download file
        $lastLine          = '';
        $logMessage        = '';
        $foundTransferData = false;
        $transferStartStr  = '  % Total    % Received';
        log::info('Cmd: '.$cmd);
        if (($fp                = popen($cmd, "r")))
        {
            // 1KB of initial data, required by Webkit browsers
            echo "<span>" . str_repeat("0", 1000) . "</span>";

            // allow sub-domains for remote file servers
            echo "<script>document.domain = '" . _CONFIG_CORE_SITE_HOST_URL . "';</script>";

            // stream result
            $localData = '';
            while (!feof($fp))
            {
                $lastPart = '';
                $results = fgets($fp, 100);

                $localData .= $results;
                $logMessage .= $results;
                
                // should we update progress
                if ($foundTransferData == true)
                {
                    // get total size and download progress
                    $localData = str_replace("\r", "\n", $localData);
                    $lines = explode("\n", $localData);
                    if(COUNT($lines) >= 2)
                    {
                        $lastFullLine = $lines[COUNT($lines)-2];
                    }

                    // remove extra spaces
                    $lastFullLine = str_replace(array('   ', '  '), ' ', $lastFullLine);
                    $lastFullLine = str_replace(array('   ', '  '), ' ', $lastFullLine);
                    $lastFullLine = str_replace(array('   ', '  '), ' ', $lastFullLine);
                    if(substr($lastFullLine, 0, 1) == ' ')
                    {
                        $lastFullLine = substr($lastFullLine, 1);
                    }
                    
                    // extract progress
                    $lastLinePrepExp = explode(" ", $lastFullLine);
                    $downloadSize = fileLeechConvertToBytes($lastLinePrepExp[1]);
                    $downloadedSize = fileLeechConvertToBytes($lastLinePrepExp[3]);
                    if($downloadSize > 0)
                    {
                        // output progress on screen
                        $upload_handler = new uploader();
                        $upload_handler->remote_url_event_callback(array("progress" => array("loaded" => $downloadedSize, "total"  => $downloadSize, "rowId"  => (int)$rowId)));
                    }
                    unset($lines);
                }
                elseif (strpos($localData, $transferStartStr) !== false)
                {
                    $foundTransferData = true;
                }
            
            }
            pclose($fp);

            // log response
            if (strlen($logMessage))
            {
                log::info($logMessage);
                log::revertContext();
            }
            
            // get path in last line
            $lines = explode("\n", $localData);
            $lastLine = $lines[COUNT($lines)-2];
            $localFilePath  = str_replace("\n", "", $lastLine);

            // assume the last line is the file path
            $upload_handler = new uploader();
            if (!file_exists($localFilePath))
            {
                // handle errors
                $fileUploadError          = coreFunctions::createUploadError(t('plugin_fileleech_error', 'Error!'), $localFilePath);
                $fileUploadError          = json_decode($fileUploadError, true);
                $fileUploadError          = $fileUploadError[0];
                $fileUploadError['rowId'] = $rowId;
                $upload_handler->remote_url_event_callback(array("done" => $fileUploadError));
                exit;
            }

            // file has been downloaded, move into storage
            $fileDetails = pathinfo($localFilePath);
            $fileName    = $fileDetails['filename'];
            if (strlen($fileDetails['extension']))
            {
                $fileName .= '.' . $fileDetails['extension'];
            }

            // get mime type
            $mimeType = file::estimateMimeTypeFromExtension($fileName, 'application/octet-stream');
            if (($mimeType == 'application/octet-stream') && (class_exists('finfo', false)))
            {
                $finfo    = new finfo;
                $mimeType = $finfo->file($localFilePath, FILEINFO_MIME);
            }

            $fileUpload             = new stdClass();
            $fileUpload->name       = $fileName;
            $fileUpload->size       = filesize($localFilePath);
            $fileUpload->type       = $mimeType;
            $fileUpload->error      = null;
            $fileUpload->rowId      = $rowId;
            $fileUpload->requestUrl = $params['url'];
            $fileUpload             = $upload_handler->moveIntoStorage($fileUpload, $localFilePath);

            // make sure file has been removed
            @unlink($localFilePath);

            // no error, add success html
            if ($fileUpload->error === null)
            {
                $fileUpload->success_result_html = uploader::generateSuccessHtml($fileUpload);
            }
            else
            {
                $fileUpload->error_result_html = uploader::generateErrorHtml($fileUpload);
            }

            $upload_handler->remote_url_event_callback(array("done" => $fileUpload));
            exit;
        }
    }
}

function fileLeechConvertToBytes($formattedSize)
{
    $size = substr($formattedSize, 0, strlen($formattedSize)-1);
    switch(strtoupper(substr($formattedSize, strlen($formattedSize)-1, 1)))
    {
        case 'G':
            return ceil($size*1024*1024*1024);
            break;
        case 'M':
            return ceil($size*1024*1024);
            break;
        case 'K':
            return ceil($size*1024);
            break;
        case 'B':
            return ceil($size);
            break;
    }
    
    return ceil($size);
}