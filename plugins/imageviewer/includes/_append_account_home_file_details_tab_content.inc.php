<?php
// pickup params
$file = $params['file'];
$Auth = $params['Auth'];

// setup valid image extensions
$ext = array('jpg', 'jpeg', 'png', 'gif');

// check this is an image
if (in_array(strtolower($file->extension), $ext))
{
    // get database connection
    $db = Database::getDatabase();
    
    // get meta information
    $metaData = $db->getRow('SELECT * FROM plugin_imageviewer_meta WHERE file_id = '.(int)$file->id.' LIMIT 1');
    $exifData = false;
    if(strlen($metaData['raw_data']))
    {
        if($rawDataArr = json_decode($metaData['raw_data'], true))
        {
            if(COUNT($rawDataArr))
            {
                $exifData = $rawDataArr;
            }
        }
    }
    
    ?>
    <?php if ($file->statusId == 1): ?>
    <div class="tab-pane" id="imageviewer-preview" style="text-align: center;">
        <?php if ($imageLink = file::getIconPreviewImageUrl((array) $file, false, 160, false, 1100, 1100, 'cropped')): ?>
        <img src="<?php echo $imageLink; ?>" style="max-width: 100%; max-height: 800px;"/>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="tab-pane" id="imageviewer-extra-info">
        <?php
        if(!$metaData)
        {
            echo t('plugin_imageviewer_no_extra_information_found', 'No extra file information found.');
        }
        else
        {
            // load plugin details
            $pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
            $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);
        ?>
            <div style="max-height: 580px; overflow: scroll; overflow-x: hidden;">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <?php
                        if ((int) $pluginSettings['show_direct_link'] == 1)
                        {
                            ?>
                            <tr>
                                <td>
                                    <label><?php echo t('direct_link', 'Direct Link'); ?>:</label>
                                </td>
                                <td class="htmlCode" onClick="return false;">
                                    <?php echo WEB_ROOT; ?>/plugins/imageviewer/site/direct.php?s=<?php echo $file->shortUrl; ?>&/<?php echo $file->getSafeFilenameForUrl(); ?>
                                </td>
                            </tr>
                            <?php
                        }

                        if ((int) $pluginSettings['show_embedding'] == 1)
                        {
                            ?>
                            <tr>
                                <td>
                                    <label><?php echo t('embed_html_code', 'Embed HTML Code'); ?>:</label>
                                </td>
                                <td class="htmlCode ltrOverride" onClick="return false;">
                                    &lt;a href=&quot;<?php echo $file->getFullShortUrl(); ?>&quot; target=&quot;_blank&quot; title=&quot;<?php echo t('download_from', 'Download from'); ?> <?php echo SITE_CONFIG_SITE_NAME; ?>&quot;&gt;&lt;img src=&quot;<?php echo WEB_ROOT; ?>/plugins/imageviewer/site/thumb.php?s=<?php echo $file->shortUrl; ?>&amp;/<?php echo $file->getSafeFilenameForUrl(); ?>&quot;/&gt;&lt;/a&gt;
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <label><?php echo t('embed_forum_code', 'Embed Forum Code'); ?>:</label>
                                </td>
                                <td class="htmlCode ltrOverride">
                                    [URL=<?php echo $file->getFullShortUrl(); ?>][IMG]<?php echo WEB_ROOT; ?>/plugins/imageviewer/site/thumb.php?s=<?php echo $file->shortUrl; ?>&/<?php echo $file->getSafeFilenameForUrl(); ?>[/IMG][/URL]
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <td class="share-file-table-header">
                                <?php echo UCWords(t('plugin_imageviewer_image_size', 'image size')); ?>:
                            </td>
                            <td class="responsiveTable ltrOverride">
                                <?php echo validation::safeOutputToScreen($metaData['width']); ?><?php echo t('plugin_imageviewer_px', 'px'); ?> <?php echo t('plugin_imageviewer_w', '(w)'); ?> x <?php echo validation::safeOutputToScreen($metaData['height']); ?><?php echo t('plugin_imageviewer_px', 'px'); ?> <?php echo t('plugin_imageviewer_h', '(h)'); ?>
                            </td>
                        </tr>
                        <?php
                        if($exifData == false)
                        {
                            echo '<tr><td colspan="2">- '.t('plugin_imageviewer_no_exif_found_on_this_image', 'No EXIF data found for this image.').'</td></tr>';
                        }
                        else
                        {
                            foreach($exifData AS $exifDataKey=>$exifDataItem)
                            {
                            ?>
                                <tr>
                                    <td class="share-file-table-header">
                                        <?php echo validation::safeOutputToScreen($exifDataKey); ?>:
                                    </td>
                                    <td class="responsiveTable">
                                        <?php echo validation::safeOutputToScreen($exifDataItem); ?>
                                    </td>
                                </tr>
                            <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        ?>
    </div>
<?php
}
?>