<?php

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('aliyunoss');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

$params['html'] = '<option value="aliyun_oss"'.($params['server_type']=='aliyun_oss'?' SELECTED':'').'>AliYun OSS (bucket: '.(isset($pluginSettings['oss_bucket'])?$pluginSettings['oss_bucket']:'not set').')</option>';