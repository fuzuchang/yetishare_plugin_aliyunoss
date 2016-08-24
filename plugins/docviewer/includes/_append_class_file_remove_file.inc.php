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
$ext = array('pdf');

// check this is a video
$file = $params['file'];
if (in_array(strtolower($file->extension), $ext))
{
    // load plugin details
    $pluginObj      = pluginHelper::getInstance('docviewer');
    
    // queue any screenshot cache for delete
    $pluginObj->deleteDocCache($file->id);
}
