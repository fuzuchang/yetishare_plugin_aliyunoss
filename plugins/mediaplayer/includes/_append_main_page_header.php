<?php
// available params
// $params['file']
// $params['Auth']

// ignore if we don't have the file data
if($params['file'] != null)
{
    // load plugin details
    $pluginDetails  = pluginHelper::pluginSpecificConfiguration('mediaplayer');
    $pluginConfig   = $pluginDetails['config'];
    $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

    // Initialize current user
    $Auth = Auth::getAuth();
    $ext = explode("|", $pluginSettings['paid_media_types']);

    // check this is an image
    if (in_array(strtolower($params['file']->extension), $ext))
    {
        // only for active files
        if($params['file']->statusId == 1)
        {
            ?>
            <meta property="og:image" content="<?php echo file::getIconPreviewImageUrl((array)$params['file'], false, 160, false, 640, 320); ?>" />
            <?php
        }
    }
}