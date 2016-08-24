<?php

/*
 * available params
 * 
 * $params['oldFile'];
 * $params['newFile'];
 * */

// media file types
$ext = array('mp4', 'flv', 'webm', 'avi', 'divx', 'mkv', 'ogg', 'ogv', 'm4v', 'mp3', 'wav', 'm4a', 'wmv');

// check this is a video
$oldFile = $params['oldFile'];
$newFile = $params['newFile'];
if (in_array(strtolower($newFile->extension), $ext))
{
    // load plugin details
    $pluginObj      = pluginHelper::getInstance('mediaconverter');
    
    // copy cache
    $pluginObj->duplicateMediaCache($oldFile->id, $newFile->id);
}
