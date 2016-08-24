<?php

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('aliyunoss');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

/*
 * available params
 * 
 * $params['file_path'];
 * $params['file'];
 * $params['uploadServerDetails'];
 * $params['fileUpload'];
 * $params['newFilename'];
 * $params['tmpFile'];
 * $params['uploader'];
 * */

$fileUpload = $params['fileUpload'];
$uploadServerDetails = $params['uploadServerDetails'];
$file_size = 0;
if ($uploadServerDetails['serverType'] == 'aliyun_oss')
{
    // increase server limits
    set_time_limit(60*60*6); // 6 hours
	ini_set('memory_limit', '2000M');
    
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
            // path on s3
            $newFilePath = substr($params['newFilename'], 0, 2).'/'.$params['newFilename'];
            // upload the files
            $rs = $OssClient->putObject($oss_bucket, $newFilePath, file_get_contents($params['tmpFile']));
            // upload done
            $file_size = filesize($params['tmpFile']);
            @unlink($params['tmpFile']);
        }
    }
    
    // on actioned
    $params['fileUpload'] = $fileUpload;
    $params['file_size'] = $file_size;
    $params['file_path'] = $newFilePath;
    $params['actioned'] = true;
}