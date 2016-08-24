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
        ?>
        <li>
            <a href="#docviewer-preview" data-toggle="tab"><i class="entypo-doc-text"></i> <?php echo UCWords(t('view_document', 'view document')); ?></a>
        </li>
        <?php
    }
}
?>