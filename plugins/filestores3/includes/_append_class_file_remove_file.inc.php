
<?php

// load plugin details
$pluginObj      = pluginHelper::getInstance('filestores3');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filestores3');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

/*
 * available params
 * 
 * $params['actioned'];
 * $params['filePath'];
 * $params['storageType'];
 * $params['storageLocation'];
 * $params['file'];
 * */

$file = $params['file'];
$storageType = $params['storageType'];
if ($storageType == 'amazon_s3')
{
    // get required classes
    require_once 's3/S3.php';
    
    // check for CURL
    if (!extension_loaded('curl') && !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll'))
    {
        // handle error
        $params['errorMsg'] = 'Could not load curl extension for S3 file server.';
    }
    else
    {
        // instantiate the class
        $s3 = new S3($pluginSettings['aws_access_key'], $pluginSettings['aws_secret_key']);
        if(!$s3)
        {
            // failed connecting
            $params['errorMsg'] = 'Could not connect to S3 file server.';
        }
        else
        {
            // path on s3
            $s3FilePath = $file->localFilePath;

            // delete the file
            $rs = $s3->deleteObject($pluginSettings['bucket_name'], $s3FilePath);
			$pluginObj->deleteS3Cache($file->id);
            if(!$rs)
            {
                // failed delete
                $params['errorMsg'] = 'Failed to remove file from S3 file server. Please try again later.';
            }
        }
    }
    
    // on actioned
    $params['actioned'] = true;
}