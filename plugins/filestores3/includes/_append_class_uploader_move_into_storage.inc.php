<?php

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filestores3');
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
if ($uploadServerDetails['serverType'] == 'amazon_s3')
{
    // increase server limits
    set_time_limit(60*60*6); // 6 hours
	ini_set('memory_limit', '2000M');
    
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
            // path on s3
            $newFilePath = substr($params['newFilename'], 0, 2).'/'.$params['newFilename'];

            // upload the files
            $rs = $s3->putObjectFile($params['tmpFile'], $pluginSettings['bucket_name'], $newFilePath);
            if(!$rs)
            {
                // failed upload
                $fileUpload->error = 'Failed upload to S3 file server. Please try again later.';
            }
            else
            {
                // upload done
                $file_size = filesize($params['tmpFile']);
                @unlink($params['tmpFile']);
            }
        }
    }
    
    // on actioned
    $params['fileUpload'] = $fileUpload;
    $params['file_size'] = $file_size;
    $params['file_path'] = $newFilePath;
    $params['actioned'] = true;
}