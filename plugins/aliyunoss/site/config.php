<?php
/**
 * 配置文件
 * Created by PhpStorm.
 * User: fuzuchang
 * Date: 2016-08-23
 * Time: 13:05
 */

// includes and security
include_once('../../../core/includes/master.inc.php');

$plugin   = $db->getRow("SELECT * FROM plugin WHERE folder_name = 'aliyunoss' LIMIT 1");
$oss_access_key = '';
$oss_secret_key = '';
$oss_host       = '';
$oss_endpoint   = '';
$oss_bucket     = '';
$oss_iscname    = '0';
$oss_dir_name    = 'files';
$oss_max_upload_bytes    = 1048576000;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $oss_access_key = $plugin_settings['oss_access_key'];
        $oss_secret_key = $plugin_settings['oss_secret_key'];
        $oss_host       = $plugin_settings['oss_host'];
        $oss_endpoint   = $plugin_settings['oss_endpoint'];
        $oss_bucket     = $plugin_settings['oss_bucket'];
        $oss_iscname    = $plugin_settings['oss_iscname'];
        $oss_dir_name    = $plugin_settings['oss_dir_name']?$plugin_settings['oss_dir_name']:'files';
        $oss_max_upload_bytes = $plugin_settings['oss_max_upload_bytes']?$plugin_settings['oss_max_upload_bytes']:$oss_max_upload_bytes;
    }
}

//Access Key ID
define("ALI_OSS_ACCESS_KEY_ID",$oss_access_key);
//Access Key Secret
define("ALI_OSS_ACCESS_KEY_SECRET",$oss_secret_key);
//OSS主机地址
define("ALI_OSS_HOST",$oss_host);
//回调服务器地址
define("ALI_OSS_CALLBACK_URL","http://"._CONFIG_SITE_HOST_URL."/plugins/aliyunoss/site/notify.php");
//OSS请求时，发送给应用服务器的内容
define("ALI_OSS_CALLBACK_BODY",'user=${x:user}&filename=${x:filename}&objectName=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}');
//OSS请求时,请求发送的Content-Type
define("ALI_OSS_CALLBACK_BODY_TYPE",'application/x-www-form-urlencoded');
//上传策略失效时间
define("ALI_OSS_EXPIRE",'30');
//上传目录
define("ALI_OSS_UPLOAD_DIR",$oss_dir_name.'/');
//最大上传文件大小 以字节为单位
define("ALI_OSS_MAX_UPLOAD_BYTES",(int)$oss_max_upload_bytes);