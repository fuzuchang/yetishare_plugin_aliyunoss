<?php

/*
 * available params
 * 
 * $params['actioned'];
 * $params['filePath'];
 * $params['storageType'];
 * $params['storageLocation'];
 * $params['file'];
 * */

// media file types
$ext = array('mp4', 'flv', 'webm', 'avi', 'divx', 'mkv', 'ogg', 'ogv', 'm4v', 'mp3', 'wav', 'm4a', 'wmv');

// check this is a video
$file = $params['file'];
if (in_array(strtolower($file->extension), $ext))
{
    // database
    $db = Database::getDatabase();
    
    // cancel any pending records from the converter queue
    $db->query('UPDATE plugin_mediaconverter_queue SET status = \'cancelled\', notes=\'Cancelled due to file removal.\' WHERE file_id = '.(int)$file->id);
    
    // load plugin details
    $pluginObj      = pluginHelper::getInstance('mediaconverter');
    
    // queue any screenshot cache for delete
    $pluginObj->deleteMediaCache($file->id);
}
