<?php
// pickup params
$file = $params['file'];
$Auth = $params['Auth'];
$isPublic = (int)$params['isPublic'];

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

    // check this is a video or audio, only 'mp4', 'webm', 'mp3', 'ogg' supported in this view
    if(in_array(strtolower($file->extension), $ext))
    {
		if ((int) $pluginSettings['show_embed'] == 1)
		{
		?>
			<h4><strong><?php echo UCWords(t("embed_code", "embed code")); ?></strong></h4>
			<table class="table table-bordered table-striped">
				<tbody>
					<tr>
						<td class="share-file-table-header">
							<?php echo t('embed_document', 'Embed Document'); ?>:
						</td>
						<td class="responsiveTable ltrOverride">
							<section onClick="selectAllText(this); return false;">
								<?php
								$embedWidth  = (int) $pluginSettings['embed_document_size_w'];
								$embedHeight = (int) $pluginSettings['embed_document_size_h'];
								echo htmlentities('<iframe src="' . PLUGIN_WEB_ROOT . '/docviewer/site/_embed.php?u=' . $file->shortUrl . '&w=' . $pluginSettings['embed_document_size_w'] . '&h=' . $pluginSettings['embed_document_size_h'] . '" frameborder="0" scrolling="no" style="width: ' . $pluginSettings['embed_document_size_w'] . 'px; height: ' . $pluginSettings['embed_document_size_h'] . 'px; overflow: hidden;"></iframe>');
								?>
							</section>
						</td>
					</tr>
				</tbody>
			</table>
		<?php
		}
	}
}
?>