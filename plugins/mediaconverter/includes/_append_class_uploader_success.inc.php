<?php

// pick up variables
if (isset($params['file']))
{
    // uploaded file object
    $file = $params['file'];
    
    // load plugin details
    $pluginConfig   = pluginHelper::pluginSpecificConfiguration('mediaconverter');
    $pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

    // check file type
    $fileTypes = explode(",", strtolower($pluginSettings['convert_files']));
    if (in_array(strtolower($file->extension), $fileTypes))
    {
        // schedule for converting
        $dbInsert = new DBObject("plugin_mediaconverter_queue", array("file_id", "status", "date_added"));
        $dbInsert->file_id    = $file->id;
        $dbInsert->status     = 'pending';
        $dbInsert->date_added = coreFunctions::sqlDateTime();
        $dbInsert->insert();
    }
}
