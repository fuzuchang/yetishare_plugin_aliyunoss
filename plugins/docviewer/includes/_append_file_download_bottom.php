<?php
// try to load the file object
$file = null;
if (isset($_REQUEST['_page_url']))
{
    // only keep the initial part if there's a forward slash
    $shortUrl = current(explode("/", $_REQUEST['_page_url']));
    $file     = file::loadByShortUrl($shortUrl);
}

/* load file details */
if (!$file)
{
    /* if no file found, redirect to home page */
    coreFunctions::redirect(WEB_ROOT . "/index." . SITE_CONFIG_PAGE_EXTENSION);
}

// for page footer link
if(!defined('REPORT_URL'))
{
    define('REPORT_URL', $file->getFullShortUrl());
}

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('docviewer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// Initialize current user
$Auth = Auth::getAuth();
define("CONTROLS_HEIGHT", 95);

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

// override settings for google callback
if (isset($_REQUEST['docdt']))
{
    // check token valid
    $downloadToken = $_REQUEST['docdt'];
    $db            = Database::getDatabase(true);
    $rs            = $db->getValue('SELECT token FROM plugin_docviewer_embed_token WHERE file_id=' . (int) $file->id . ' AND token="' . $db->escape($downloadToken) . '" LIMIT 1');
    if ($rs)
    {
        $extType = 'paid_document_types';
    }
}

$ext = explode(",", strtolower($pluginSettings[$extType]));

// if this is a download request
if ((!isset($_REQUEST['docdt'])) && (in_array(strtolower($file->extension), $ext)))
{
    // setup database
    $db = Database::getDatabase();

    // prepare trimmed header
    $headerTitle = $file->originalFilename;
    if (strlen($headerTitle) > 60)
    {
        $headerTitle = substr($headerTitle, 0, 55) . '...' . end(explode(".", $headerTitle));
    }

    // setup page
    define("PAGE_NAME", $file->originalFilename . ' ' . t("docviewer_plugin_watch_page_name", "Preview"));
    define("PAGE_DESCRIPTION", t("docviewer_plugin_page_description", "Preview ") . ' ' . $file->originalFilename);
    define("PAGE_KEYWORDS", strtolower($file->originalFilename) . t("docviewer_plugin_meta_keywords", ", preview, file, upload, download, site"));

    // include header
    require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
    ?>

<div class="animated" data-animation="fadeInUp" data-animation-delay="900">
    <div class="contentPageWrapper">
        <div class="pageSectionMainFull ui-corner-all">
            <div class="pageSectionMainInternal">
                <div id="pageHeader" class="first-header">
                    <h2><?php echo validation::safeOutputToScreen($headerTitle); ?></h2>
                </div>
                <div>
                    <?php
                    // check filesize
                    if ($file->fileSize >= 26214400)
                    {
                        echo t('plugin_docviewer_document_can_not_be_previewed', '- Document can not be previewed as it is too big.');
                    }
                    else
                    {
                        ?>
                        <iframe src="https://view.officeapps.live.com/op/view.aspx?src=<?php echo $file->generateDirectDownloadUrlForMedia(); ?>&embedded=true" height="700" width="100%" frameborder="0" style="border:1px solid #ddd;"></iframe>
                        <?php
                    }
                    ?>
                </div>
                <div class="clear"><!-- --></div>


                <div id="pageHeader" style="padding-top: 12px;">
                    <h2><?php echo UCWords(t('file_details', 'file details')); ?></h2>
                </div>
                <div>
                    <table class="accountStateTable table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <td class="first">
                                    <?php echo UCWords(t('filename', 'filename')); ?>:
                                </td>
                                <td>
                                    <?php echo validation::safeOutputToScreen($file->originalFilename); ?>&nbsp;&nbsp;
                                    <?php if ((int) $pluginSettings['show_download_link'] == 1): ?>
                                        <a href="<?php echo $file->generateDirectDownloadUrl(); ?>" target="_blank">(<?php echo t('download', 'download'); ?>)</a>
                                    <?php endif; ?>
                                    <?php if ($Auth->id != $file->userId): ?>
                                        &nbsp;&nbsp;<a href="<?php echo CORE_PAGE_WEB_ROOT . '/account_copy_file.php?f=' . $file->shortUrl; ?>">(<?php echo t('copy_into_your_account', 'copy file'); ?>)</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="first">
                                    <?php echo UCWords(t('filesize', 'filesize')); ?>:
                                </td>
                                <td>
                                    <?php echo coreFunctions::formatSize($file->fileSize); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="clear"><!-- --></div>

                <?php if ((int) $pluginSettings['show_embed'] == 1): ?>
                    <div id="pageHeader" style="padding-top: 12px;">
                        <h2><?php echo t("embed_code", "embed code"); ?></h2>
                    </div>
                    <div>
                        <table class="accountStateTable table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <td class="first">
                                        <?php echo t('embed_document', 'Embed Document'); ?>:
                                    </td>
                                    <td class="htmlCode ltrOverride">
                                        <?php
                                        $embedWidth  = (int) $pluginSettings['embed_document_size_w'];
                                        $embedHeight = (int) $pluginSettings['embed_document_size_h'];
                                        echo htmlentities('<iframe src="' . PLUGIN_WEB_ROOT . '/docviewer/site/_embed.php?u=' . $file->shortUrl . '&w=' . $pluginSettings['embed_document_size_w'] . '&h=' . $pluginSettings['embed_document_size_h'] . '" frameborder="0" scrolling="no" style="width: ' . $pluginSettings['embed_document_size_w'] . 'px; height: ' . $pluginSettings['embed_document_size_h'] . 'px; overflow: hidden;"></iframe>');
                                        ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"><!-- --></div>
                <?php endif; ?>

                <div id="pageHeader" style="padding-top: 12px;">
                    <h2><?php echo t("download_urls", "download urls"); ?></h2>
                </div>
                <div>
                    <table class="accountStateTable table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <td class="first">
                                    <?php echo t('html_code', 'HTML Code'); ?>:
                                </td>
                                <td class="htmlCode ltrOverride">
                                    <?php echo $file->getHtmlLinkCode(); ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="first">
                                    <?php echo UCWords(t('forum_code', 'forum code')); ?>
                                </td>
                                <td class="htmlCode ltrOverride">
                                    <?php echo $file->getForumLinkCode(); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="clear"><!-- --></div>

                <div id="pageHeader" style="padding-top: 12px;">
                    <h2><?php echo t("share", "share"); ?></h2>
                </div>
                <div>
                    <table class="accountStateTable table table-bordered table-striped">
                        <tbody>
                            <tr>
                                <td class="first">
                                    <?php echo UCWords(t('share_file', 'share file')); ?>:
                                </td>
                                <td>
                                    <!-- AddThis Button BEGIN -->
                                    <div class="addthis_toolbox addthis_default_style addthis_32x32_style">
                                        <a class="addthis_button_preferred_1" addthis:url="<?php echo $file->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $file->originalFilename); ?>"></a>
                                        <a class="addthis_button_preferred_2" addthis:url="<?php echo $file->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $file->originalFilename); ?>"></a>
                                        <a class="addthis_button_preferred_3" addthis:url="<?php echo $file->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $file->originalFilename); ?>"></a>
                                        <a class="addthis_button_preferred_4" addthis:url="<?php echo $file->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $file->originalFilename); ?>"></a>
                                        <a class="addthis_button_compact" addthis:url="<?php echo $file->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $file->originalFilename); ?>"></a>
                                        <a class="addthis_counter addthis_bubble_style" addthis:url="<?php echo $file->getFullShortUrl(); ?>" addthis:title="<?php echo str_replace("\"", "", $file->originalFilename); ?>"></a>
                                    </div>
                                    <script type="text/javascript" src="<?php echo _CONFIG_SITE_PROTOCOL; ?>://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4f10918d56581527"></script>
                                    <!-- AddThis Button END -->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="clear"><!-- --></div>
            </div>

        </div>
    </div>
</div>
    <div class="clear"></div>

    <?php
    // include footer
    require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
    exit;
}
elseif ((isset($_REQUEST['docdt'])) && (in_array(strtolower($file->extension), $ext)))
{
    $directDownloadUrl = $file->generateDirectDownloadUrl();
    coreFunctions::redirect($directDownloadUrl);
}
