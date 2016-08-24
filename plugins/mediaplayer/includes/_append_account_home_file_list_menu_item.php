<?php
// available params
// $params['fileObj']
// $params['extraMenuItems']

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('mediaplayer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// Initialize current user
$Auth = Auth::getAuth();

// load available extensions for this user
$extType = 'non_media_types';
if (($Auth->level_id == 1) && ($Auth->loggedIn == true))
{
    $extType = 'free_media_types';
}
elseif ($Auth->level_id >= 2)
{
    $extType = 'paid_media_types';
}
$ext = explode("|", $pluginSettings[$extType]);

// check this is an image
if (in_array(strtolower($params['fileObj']->extension), $ext))
{
    // only for active files
    if($params['fileObj']->statusId == 1)
    {
        $params['extraMenuItems']['View'] = array("label"=>UCWords(t('account_file_details_view', 'View')), "separator_after"=>true, "action"=>"function() { window.open(downloadUrl); }");
    }
}
