<?php
// available params
// $params['iconUrl']
// $params['fileArr']

// setup valid image extensions
$ext = array('jpg', 'jpeg', 'png', 'gif');

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('imageviewer');
$pluginConfig  = $pluginDetails['config'];

// check this is an image
if (in_array(strtolower($params['fileArr']['extension']), $ext))
{
    // only for active files
    if($params['fileArr']['statusId'] == 1)
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
        
        $m = 'middle';
        if(trim($params['type']))
        {
            $m = trim($params['type']);
        }
        
        $params['iconUrl'] = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($params['fileArr']['id'], $params['fileArr']['serverId'], true) . '/' . PLUGIN_DIRECTORY_NAME . '/imageviewer/site/resize_image_inline.php?f='.($params['fileArr']['id']).'&w='.$w.'&h='.$h.'&m='.$m;
    }
}
