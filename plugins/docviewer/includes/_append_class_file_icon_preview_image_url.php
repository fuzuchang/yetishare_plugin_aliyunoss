<?php
// available params
// $params['iconUrl']
// $params['fileArr']

// setup valid image extensions
$ext = array('pdf');

// load plugin details
$pluginDetails = pluginHelper::pluginSpecificConfiguration('docviewer');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// check this is a pdf
if ((in_array(strtolower($params['fileArr']['extension']), $ext)) && ((int)$pluginSettings['pdf_thumbnails'] == 1))
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
        
        // url
        $params['iconUrl'] = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($params['fileArr']['id'], $params['fileArr']['serverId'], true) . '/' . PLUGIN_DIRECTORY_NAME .'/docviewer/site/pdf_thumbnail.php?f='.$params['fileArr']['id'].'&w='.$w.'&h='.$h.'&m='.$m;
    }
}
