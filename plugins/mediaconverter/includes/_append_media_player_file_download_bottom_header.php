<?php

// pickup params
$file = $params['file'];
$Auth = $params['Auth'];

$db = Database::getDatabase();

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('mediaconverter');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// see if the file has a pending conversion
$queueState = $db->getValue('SELECT status FROM plugin_mediaconverter_queue WHERE file_id = '.$file->id.' LIMIT 1');
$message = '';
if($queueState == 'pending')
{
    $message = t('media_converter_item_pending', 'This video is awaiting conversion, please check back again later.');
}
elseif($queueState == 'processing')
{
    $message = t('media_converter_item_processing', 'This video is in the process of being converted, please check back again soon.');
}

if(strlen($message))
{
    notification::setError($message);
    echo notification::outputErrors();
}
