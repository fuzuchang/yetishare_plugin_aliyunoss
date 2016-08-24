<?php

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('filestores3');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

$params['html'] = '<option value="amazon_s3"'.($params['server_type']=='amazon_s3'?' SELECTED':'').'>Amazon S3 (bucket: '.(isset($pluginSettings['bucket_name'])?$pluginSettings['bucket_name']:'not set').')</option>';