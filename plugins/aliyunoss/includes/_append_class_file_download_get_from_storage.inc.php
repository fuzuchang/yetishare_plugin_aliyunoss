<?php

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('aliyunoss');
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
if ($storageType == 'aliyun_oss')
{
    // get required classes
    require_once 'alioss/autoload.php';
    
    // check for CURL
    if (!extension_loaded('curl') && !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll'))
    {
        // handle error
        $fileUpload->error = 'Could not load curl extension for OSS file server.';
    }
    else
    {
        // instantiate the class
        $oss_access_key = $pluginSettings['oss_access_key'];
        $oss_secret_key = $pluginSettings['oss_secret_key'];
        $oss_host       = $pluginSettings['oss_host'];
        $oss_endpoint   = $pluginSettings['oss_endpoint'];
        $oss_bucket     = $pluginSettings['oss_bucket'];
        $oss_iscname    = $pluginSettings['oss_iscname'];


        $OssClient = new \OSS\OssClient($oss_access_key, $oss_secret_key,$oss_endpoint,$oss_iscname);
        if(is_null($OssClient))
        {
            // failed connecting
            $fileUpload->error = 'Could not connect to OSS file server.';
        }
        else
        {
/*            // 下载object到内存
            $fileContent = $OssClient->getObject($oss_bucket, $file->alioss_object_name);

            // move to starting position
                if ($forceDownload == true)
                {
                    echo $fileContent;
                }
                // update download status every DOWNLOAD_TRACKER_UPDATE_FREQUENCY seconds
                if (($timeTracker + DOWNLOAD_TRACKER_UPDATE_FREQUENCY) < time())
                {
                    $timeTracker = time();
                    if (SITE_CONFIG_DOWNLOADS_TRACK_CURRENT_DOWNLOADS == 'yes')
                    {
                        $downloadTracker->update();
                    }
                }*/
            $downloadUrl  =  $oss_host. "/".$file->alioss_object_name;

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
    
    // on actioned
    $params['fileContent'] = $fileContent;
    $params['actioned'] = true;
}