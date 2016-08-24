<?php
// available params
// $params['iconUrl']
// $params['fileArr']

// setup valid image extensions
$ext = array('mp4', 'flv', 'webm');

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('mediaconverter');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// check this is a video
if (in_array(strtolower($params['fileArr']['extension']), $ext))
{
    // only for active files
    if(isset($params['fileArr']['statusId']) && ($params['fileArr']['statusId'] == 1))
    {
        $w = 99;
        if((int)$params['width'])
        {
            $w = (int)$params['width'];
        }
        
        $h = 60;
        if((int)$params['height'])
        {
            $h = (int)$params['height'];
        }
        
        $m = 'cropped';
        
        // check for cache
        $cacheFilePath = PLUGIN_DIRECTORY_ROOT . 'mediaconverter/converter/_cache/screenshot_cache/';
        $cacheFileName = (int)$params['fileArr']['id'].'_'.(int) $w . 'x' . (int) $h . '_' . $m.'_'. MD5(json_encode($pluginSettings)) . '.jpg';
        $fullCachePath = $cacheFilePath . $cacheFileName;
        if(file_exists($fullCachePath))
        {
            $params['iconUrl'] = PLUGIN_WEB_ROOT.'/mediaconverter/converter/_cache/screenshot_cache/'.$cacheFileName;
        }
        else
        {
            $params['iconUrl'] = PLUGIN_WEB_ROOT.'/mediaconverter/site/resize_image.php?f='.$params['fileArr']['id'].'&w='.$w.'&h='.$h.'&m='.$m;
        }
    }
}
