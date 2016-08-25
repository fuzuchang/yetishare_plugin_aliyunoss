
<?php

// load plugin details
$pluginObj      = pluginHelper::getInstance('aliyunoss');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('aliyunoss');
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
if ($storageType == 'aliyun_oss')
{
    // get required classes
    require_once 'alioss/autoload.php';
    
    // check for CURL
    if (!extension_loaded('curl') && !@dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll'))
    {
        // handle error
        $params['errorMsg'] = 'Could not load curl extension for OSS file server.';
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
            $params['errorMsg'] = 'Could not connect to OSS file server.';
        }
        else
        {
            // path on s3
            $ossObject = $file->alioss_object_name;
            // delete the file
            $rs = $OssClient->deleteObject($oss_bucket,$ossObject);
			$pluginObj->deleteOssCache($file->id);
            if($OssClient->doesObjectExist($oss_bucket,$ossObject))
            {
                // failed delete
                $params['errorMsg'] = 'Failed to remove file from OSS file server. Please try again later.';
            }
        }
    }
    
    // on actioned
    $params['actioned'] = true;
}