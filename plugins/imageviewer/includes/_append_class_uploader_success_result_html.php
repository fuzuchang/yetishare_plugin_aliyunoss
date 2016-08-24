<?php

$ext = array('jpg', 'jpeg', 'png', 'gif');

$fileUpload    = $params['fileUpload'];
$userFolders   = $params['userFolders'];
$fileParts     = explode(".", $fileUpload->name);
$fileExtension = strtolower(end($fileParts));
if (in_array($fileExtension, $ext))
{
    // get auth
    $Auth = Auth::getAuth();
    
    // load file
    $file = file::loadByShortUrl($fileUpload->short_url);

    // load plugin details
    $pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
    $pluginConfig   = $pluginDetails['config'];
    $pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

    // generate html
    $success_result_html = '';
    $success_result_html .= '<td class="cancel">';
    $success_result_html .= '   <img src="' . coreFunctions::getCoreSitePath() . '/themes/' . SITE_CONFIG_SITE_THEME . '/images/green_tick_small.png" height="16" width="16" alt="success"/>';
    $success_result_html .= '</td>';
    $success_result_html .= '<td class="name">';
    $success_result_html .= $fileUpload->name;
    $success_result_html .= '<div class="sliderContent" style="display: none; background-color: #ffffff;">';
    $success_result_html .= '        <!-- popup content -->';

    $thumbUrl = _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($file->id, $file->serverId, true) . '/' . PLUGIN_DIRECTORY_NAME . '/imageviewer/site/thumb.php?f=1&s=' . $fileUpload->short_url;
    $success_result_html .= '<div style="float: right;">';
    $success_result_html .= '<img src="' . $thumbUrl . '" style="margin: 5px;"/>';
    $success_result_html .= '<div class="clear"></div>';
    
    // try to load width/height
    $db = Database::getDatabase();
    $row = $db->getRow('SELECT width, height FROM plugin_imageviewer_meta WHERE file_id = '.$file->id.' LIMIT 1');
    if($row)
    {
        $success_result_html .= '<div class="txtCenter">';
        $success_result_html .= t('image_size', 'Size').': '.$row['width'].'px x '.$row['height'].'px';
        $success_result_html .= '</div>';
    }
    $success_result_html .= '</div>';

    $success_result_html .= '        <table>';
    $success_result_html .= '            <tr>';
    $success_result_html .= '                <td class="odd" style="width: 90px; border-top:1px solid #fff;">';
    $success_result_html .= '                    <label>' . t('image_url', 'Image Url') . ':</label>';
    $success_result_html .= '                </td>';
    $success_result_html .= '                <td class="odd ltrOverride" style="border-top:1px solid #fff;">';
    $success_result_html .= '                    <a href="' . $fileUpload->url . '" target="_blank">' . $fileUpload->url . '</a>';
    $success_result_html .= '                </td>';
    $success_result_html .= '            </tr>';

    if ((int) $pluginSettings['show_direct_link'] == 1)
    {
        $success_result_html .= '            <tr>';
        $success_result_html .= '                <td class="even">';
        $success_result_html .= '                    <label>' . t('direct_link', 'Direct Link') . ':</label>';
        $success_result_html .= '                </td>';
        $success_result_html .= '                <td class="even htmlCode ltrOverride" onClick="return false;">';
        $success_result_html .= '                    '.WEB_ROOT.'/plugins/imageviewer/site/direct.php?s='.$file->shortUrl.'&/'.$file->getSafeFilenameForUrl();
        $success_result_html .= '                </td>';
        $success_result_html .= '            </tr>';
    }
    
    if ((int) $pluginSettings['show_embedding'] == 1)
    {
        $success_result_html .= '            <tr>';
        $success_result_html .= '                <td class="even">';
        $success_result_html .= '                    <label>' . t('html_code', 'HTML Code') . ':</label>';
        $success_result_html .= '                </td>';
        $success_result_html .= '                <td class="even htmlCode ltrOverride" onClick="return false;">';
        $success_result_html .= '                    &lt;a href=&quot;' . $file->getFullShortUrl() . '&quot; target=&quot;_blank&quot; title=&quot;' . t('download_from', 'Download from') . ' ' . SITE_CONFIG_SITE_NAME . '&quot;&gt;&lt;img src=&quot;' . WEB_ROOT . '/plugins/imageviewer/site/thumb.php?s=' . $file->shortUrl . '&amp;/'.$file->getSafeFilenameForUrl().'&quot;/&gt;&lt;/a&gt;';
        $success_result_html .= '                </td>';
        $success_result_html .= '            </tr>';

        $success_result_html .= '            <tr>';
        $success_result_html .= '                <td class="odd">';
        $success_result_html .= '                    <label>' . t('forum_code', 'Forum Code') . ':</label>';
        $success_result_html .= '                </td>';
        $success_result_html .= '                <td class="odd htmlCode ltrOverride">';
        $success_result_html .= '                    [URL=' . $file->getFullShortUrl() . '][IMG]' . WEB_ROOT . '/plugins/imageviewer/site/thumb.php?s=' . $file->shortUrl . '&/'.$file->getSafeFilenameForUrl().'[/IMG][/URL]';
        $success_result_html .= '                </td>';
        $success_result_html .= '            </tr>';
    }
    else
    {
        $success_result_html .= '            <tr>';
        $success_result_html .= '                <td class="even">';
        $success_result_html .= '                    <label>' . t('html_code', 'HTML Code') . ':</label>';
        $success_result_html .= '                </td>';
        $success_result_html .= '                <td class="even htmlCode ltrOverride" onClick="return false;">';
        $success_result_html .= '                    &lt;a href=&quot;' . $fileUpload->info_url . '&quot; target=&quot;_blank&quot; title=&quot;' . t('download from', 'Download From') . ' ' . SITE_CONFIG_SITE_NAME . '&quot;&gt;' . t('download', 'Download') . ' ' . $fileUpload->name . ' ' . t('from', 'from') . ' ' . SITE_CONFIG_SITE_NAME . '&lt;/a&gt;';
        $success_result_html .= '                </td>';
        $success_result_html .= '            </tr>';

        $success_result_html .= '            <tr>';
        $success_result_html .= '                <td class="odd">';
        $success_result_html .= '                    <label>' . t('forum_code', 'Forum Code') . ':</label>';
        $success_result_html .= '                </td>';
        $success_result_html .= '                <td class="odd htmlCode ltrOverride">';
        $success_result_html .= '                    [url]' . $fileUpload->url . '[/url]';
        $success_result_html .= '                </td>';
        $success_result_html .= '            </tr>';
    }

    $success_result_html .= '            <tr>';
    $success_result_html .= '                <td class="even">';
    $success_result_html .= '                    <label>' . t('stats_url', 'Stats Url') . ':</label>';
    $success_result_html .= '                </td>';
    $success_result_html .= '                <td class="even ltrOverride">';
    $success_result_html .= '                    <a href="' . $fileUpload->stats_url . '" target="_blank">' . $fileUpload->stats_url . '</a>';
    $success_result_html .= '                </td>';
    $success_result_html .= '            </tr>';
    $success_result_html .= '            <tr>';
    $success_result_html .= '                <td class="odd">';
    $success_result_html .= '                    <label>' . t('delete_url', 'Delete Url') . ':</label>';
    $success_result_html .= '                </td>';
    $success_result_html .= '                <td class="odd ltrOverride">';
    $success_result_html .= '                    <a href="' . $fileUpload->delete_url . '" target="_blank">' . $fileUpload->delete_url . '</a>';
    $success_result_html .= '                </td>';
    $success_result_html .= '            </tr>';
    
    $success_result_html .= '            <tr>';
        $success_result_html .= '                <td class="even">';
        $success_result_html .= '                    <label>' . t('full_info',
                                                                  'Full Info') . ':</label>';
        $success_result_html .= '                </td>';
        $success_result_html .= '                <td class="even htmlCode ltrOverride">';
        $success_result_html .= '                    <a href="' . $fileUpload->info_url . '" target="_blank" onClick="window.open(\'' . $fileUpload->info_url . '\'); return false;">[' . t('click_here',
                                                                                                                                                                                            'click here') . ']</a>';
        $success_result_html .= '                </td>';
        $success_result_html .= '            </tr>';

    $success_result_html .= '        </table>';
    $success_result_html .= '        <input type="hidden" value="' . $fileUpload->short_url . '" name="shortUrlHidden" class="shortUrlHidden"/>';
    $success_result_html .= '    </div>';
    $success_result_html .= '</td>';
    $success_result_html .= '<td class="rightArrow"><img src="' . coreFunctions::getCoreSitePath() . '/themes/' . SITE_CONFIG_SITE_THEME . '/images/blue_right_arrow.png" width="8" height="6" /></td>';
    $success_result_html .= '<td class="url urlOff">';
    $success_result_html .= '    <a href="' . $fileUpload->url . '" target="_blank">' . $fileUpload->url . '</a>';
    $success_result_html .= '    <div class="fileUrls hidden">' . $fileUpload->url . '</div>';
    $success_result_html .= '</td>';

    $params['success_result_html'] = $success_result_html;
}
