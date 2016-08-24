<?php

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filestores3');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

/*
 * available params
 * 
 * $params['fileContent'];
 * $params['downloadTracker'];
 * $params['forceDownload'];
 * $params['file'];
 * $params['doPluginIncludes'];
 * $params['storageType'];
 * $params['seekStart'];
 * $params['seekEnd'];
 * */

$file = $params['file'];
$storageType = $params['storageType'];
$forceDownload = $params['forceDownload'];
$downloadTracker = $params['downloadTracker'];
if ($storageType == 'amazon_s3')
{
    // get required classes
    require_once 's3/S3.php';
    
    // check for CURL
    if (!extension_loaded('curl') && !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll'))
    {
        // handle error
        $fileUpload->error = 'Could not load curl extension for S3 file server.';
    }
    else
    {
        // instantiate the class
        $s3 = new S3($pluginSettings['aws_access_key'], $pluginSettings['aws_secret_key']);
        if(!$s3)
        {
            // failed connecting
            $fileUpload->error = 'Could not connect to S3 file server.';
        }
        else
        {
            // get temp url for the file and stream to the user, valid only for 30 seconds
            $downloadUrl = $s3->getAuthenticatedURL($pluginSettings['bucket_name'], $file->localFilePath, 3000);
            if(!$downloadUrl)
            {
                // failed download
                $fileUpload->error = 'Failed download from S3 file server. Please try again later.';
            }
            else
            {
                // download found, stream to the user
                $handle = fopen($downloadUrl, "r");

                // move to starting position
                fseek($handle, (int)$params['seekStart']);
                while (($buffer = fgets($handle, 4096)) !== false)
                {
                    if ($forceDownload == true)
                    {
                        echo $buffer;
                    }
                    else
                    {
                        $fileContent .= $buffer;
                    }
                    $length = $length + strlen($buffer);

                    // update download status every DOWNLOAD_TRACKER_UPDATE_FREQUENCY seconds
                    if (($timeTracker + DOWNLOAD_TRACKER_UPDATE_FREQUENCY) < time())
                    {
                        $timeTracker = time();
                        if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
                        {
                            $downloadTracker->update();
                        }
                    }
                }
            }
        }
    }
    
    // on actioned
    $params['fileContent'] = $fileContent;
    $params['actioned'] = true;
}