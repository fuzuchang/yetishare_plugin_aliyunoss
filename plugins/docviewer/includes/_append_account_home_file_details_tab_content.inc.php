<?php
// pickup params
$file = $params['file'];
$Auth = $params['Auth'];

if ($file->statusId == 1)
{
    // load plugin details
    $pluginDetails  = pluginHelper::pluginSpecificConfiguration('docviewer');
    $pluginConfig   = $pluginDetails['config'];
    $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

    // load available extensions for this user
    $extType = 'non_document_types';
    if ($Auth->level_id == 1)
    {
        $extType = 'free_document_types';
    }
    elseif ($Auth->level_id > 1)
    {
        $extType = 'paid_document_types';
    }

    $ext = explode(",", strtolower($pluginSettings[$extType]));

    // check this is an image
    if (in_array(strtolower($file->extension), $ext))
    {
        echo '<div class="tab-pane" id="docviewer-preview" style="text-align: center;">';

        // check filesize
        if ($file->fileSize >= 26214400)
        {
            echo '<div style="text-align: left; width: 100%;">'.t('plugin_docviewer_document_can_not_be_previewed', '- Document can not be previewed as it is too big.').'</div>';
        }
        else
        {
            ?>
            <iframe src="https://view.officeapps.live.com/op/view.aspx?src=<?php echo $file->generateDirectDownloadUrlForMedia(); ?>&embedded=true" frameborder="0" style="width: 100%; height: 600px; border: 1px solid #ddd;"></iframe>
            <?php
        }
        
        echo '</div>';
    }
}
?>