<?php
// available params
// $params['file']
// $params['Auth']

// ignore if we don't have the file data
if($params['file'] != null)
{
    $ext = array('jpg', 'jpeg', 'png', 'gif');

    // check this is an image
    if (in_array(strtolower($params['file']->extension), $ext))
    {
        // load plugin details
        $pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
        $pluginConfig   = $pluginDetails['config'];
        $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
    
        // only for active files
        if($params['file']->statusId == 1)
        {
            ?>
            <meta property="og:image" content="<?php echo file::getIconPreviewImageUrl((array)$params['file'], false, 160, false, 320, 320); ?>&.png" />
            <?php
        }
    }
}