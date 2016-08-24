<?php
// available params
// $params['file']
// $params['Auth']

// ignore if we don't have the file data
if($params['file'] != null)
{
    // load plugin details
    $pluginDetails  = pluginHelper::pluginSpecificConfiguration('docviewer');
    $pluginConfig   = $pluginDetails['config'];
    $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

    // which extensions are valid?
    $ext = explode(",", strtolower($pluginSettings['paid_document_types']));

    // check this is a document
    if (in_array(strtolower($params['file']->extension), $ext))
    {
        // only for active files
        if($params['file']->statusId == 1)
        {
            ?>
            <meta property="og:image" content="<?php echo file::getIconPreviewImageUrlLarger((array)$params['file'], false, false); ?>" />
            <?php
        }
    }
}