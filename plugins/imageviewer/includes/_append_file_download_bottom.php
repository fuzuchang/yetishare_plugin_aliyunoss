<?php
$ext = array('jpg', 'jpeg', 'png', 'gif');

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
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// Initialize current user
$Auth = Auth::getAuth();

// make sure we should be showing the image for this user type
$showPage = false;
if ((int) $pluginSettings['non_show_viewer'] == 1)
{
    $showPage = true;
}

// logged in free users
if (($Auth->level == 'free user') && ($Auth->loggedIn == true))
{
    $showPage = false;
    if ((int) $pluginSettings['free_show_viewer'] == 1)
    {
        $showPage = true;
    }
}

// paid users
if (($Auth->level == 'paid user') || ($Auth->level == 'admin'))
{
    $showPage = false;
    if ((int) $pluginSettings['paid_show_viewer'] == 1)
    {
        $showPage = true;
    }
}

// if this is a download request
if ((!isset($_REQUEST['idt'])) && (in_array(strtolower($file->extension), $ext)) && ($showPage == true))
{
    // setup database
    $db = Database::getDatabase();

    // create embed token
    $embedToken           = md5(microtime());
    $dbInsert             = new DBObject("plugin_imageviewer_embed_token", array("token", "date_added", "file_id", "ip_address"));
    $dbInsert->token      = $embedToken;
    $dbInsert->date_added = coreFunctions::sqlDateTime();
    $dbInsert->file_id    = $file->id;
    $dbInsert->ip_address = coreFunctions::getUsersIPAddress();
    $dbInsert->insert();

    // prepare trimmed header
    $headerTitle = $file->originalFilename;
    if (strlen($headerTitle) > 60)
    {
        $headerTitle = substr($headerTitle, 0, 55) . '...' . end(explode(".", $headerTitle));
    }

    // setup page
    define("PAGE_NAME", $file->originalFilename . ' ' . t("image_viewer_plugin_page_name", "Watch"));
    define("PAGE_DESCRIPTION", t("image_viewer_plugin_page_description", "Watch or listen to ") . ' ' . $file->originalFilename);
    define("PAGE_KEYWORDS", strtolower($file->originalFilename) . t("image_viewer_plugin_meta_keywords", ", view, picture, file, upload, download, site"));

    // include header
    require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
    ?>

    <script>
    function validateResizeForm()
    {
        if ($('#resize_w').length == 0)
        {
            alert("<?php echo t('please_enter_the_width', 'Please enter the width'); ?>");
            return false;
        }
        else if ($('#resize_h').length == 0)
        {
            alert("<?php echo t('please_enter_the_height', 'Please enter the height'); ?>");
            return false;
        }
        else if(isNaN($('#resize_w').val()) == true)
        {
            alert("<?php echo t('please_enter_a_valid_number_for_the_width', 'Please enter a valid number for the width'); ?>");
            return false;
        }
        else if(isNaN($('#resize_h').val()) == true)
        {
            alert("<?php echo t('please_enter_a_valid_number_for_the_height', 'Please enter a valid number for the height'); ?>");
            return false;
        }
        
        return true;
    }
    </script>

    <div class="animated" data-animation="fadeInUp" data-animation-delay="900">
    <div class="contentPageWrapper">
        <div class="pageSectionMainFull ui-corner-all">
            <div class="pageSectionMainInternal">
                <div id="pageHeader" class="first-header">
                    <h2><?php echo validation::safeOutputToScreen($headerTitle); ?></h2>
                </div>
                <div style="text-align: center; background-color: #F4F4F4; padding: 3px; border: 1px solid #ccc;">
                    <?php
                    if ((int) $pluginSettings['show_download_link'] == 1)
                    {
                        echo '<a href="' . $file->generateDirectDownloadUrl() . '" target="_blank">';
                    }
                    ?>

                    <img src="<?php echo _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($file->id, $file->serverId, true) . '/' . PLUGIN_DIRECTORY_NAME; ?>/imageviewer/site/show_image.php?idt=<?php echo $embedToken; ?>&f=<?php echo (int) $file->id; ?>" style="max-width: 100%;"/>

                    <?php
                    if ((int) $pluginSettings['show_download_link'] == 1)
                    {
                        echo '</a>';
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
                                <td class="first share-file-table-header">
                                    <?php echo UCWords(t('filename', 'filename')); ?>:
                                </td>
                                <td>
                                    <?php echo validation::safeOutputToScreen($file->originalFilename); ?>&nbsp;&nbsp;
                                    <?php if ((int) $pluginSettings['show_download_link'] == 1): ?>
                                        <a href="<?php echo $file->generateDirectDownloadUrl(); ?>" target="_blank">(<?php echo t('download', 'download'); ?>)</a>
                                    <?php endif; ?>
                                    <?php if($Auth->id != $file->userId): ?>
                                    &nbsp;&nbsp;<a href="<?php echo CORE_PAGE_WEB_ROOT.'/account_copy_file.php?f='.$file->shortUrl; ?>">(<?php echo t('copy_into_your_account', 'copy file'); ?>)</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            // try to load width/height
                            $originalImageWidth = 0;
                            $originalImageHeight = 0;
                            $db  = Database::getDatabase();
                            $row = $db->getRow('SELECT width, height FROM plugin_imageviewer_meta WHERE file_id = ' . $file->id . ' LIMIT 1');
                            if ($row)
                            {
                                $originalImageWidth = $row['width'];
                                $originalImageHeight = $row['height'];
                                ?>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('image_size', 'Size')); ?>:
                                    </td>
                                    <td>
                                        <?php echo $originalImageWidth . 'px x ' . $originalImageHeight . 'px'; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            <tr>
                                <td class="first share-file-table-header">
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

                <?php
                if ((int) $pluginSettings['show_download_sizes'] == 1)
                {
                    ?>
                    <div id="pageHeader" style="padding-top: 12px;">
                        <h2><?php echo UCWords(t('resize_image', 'resize image')); ?></h2>
                    </div>
                    <div>
                        <table class="accountStateTable table table-bordered table-striped">
                            <tbody>
                                <?php if($originalImageWidth != 0): ?>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('keep_proportion', 'keep proportion')); ?>:
                                    </td>
                                    <td>
                                        <?php
                                        foreach ($pluginConfig['scaledPercentages'] AS $percentage)
                                        {
                                            $linkWidth  = ceil(($originalImageWidth/100)*$percentage);
                                            $linkHeight = ceil(($originalImageHeight/100)*$percentage);

                                            ?>
                                            <a href="<?php echo _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($file->id, $file->serverId, true) . '/' . PLUGIN_DIRECTORY_NAME; ?>/imageviewer/site/resize_image.php?idt=<?php echo $embedToken; ?>&f=<?php echo (int) $file->id; ?>&w=<?php echo $linkWidth; ?>&h=<?php echo $linkHeight; ?>"><?php echo $linkWidth; ?>x<?php echo $linkHeight; ?></a>&nbsp;
                                            <?php
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('fixed_size', 'fixed size')); ?>:
                                    </td>
                                    <td>
                                        <?php
                                        foreach ($pluginConfig['fixedSizes'] AS $size)
                                        {
                                            $linkWidth  = (int) current(explode("x", $size));
                                            $linkHeight = (int) end(explode("x", $size));
                                            $showLink = false;
                                            
                                            if($originalImageWidth == 0)
                                            {
                                                $showLink = true;
                                            }
                                            elseif(($linkWidth < $originalImageWidth) && ($linkHeight < $originalImageHeight))
                                            {
                                                $showLink = true;
                                            }
                                            
                                            if($showLink == true)
                                            {
                                            ?>
                                            <a href="<?php echo _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($file->id, $file->serverId, true) . '/' . PLUGIN_DIRECTORY_NAME; ?>/imageviewer/site/resize_image.php?idt=<?php echo $embedToken; ?>&f=<?php echo (int) $file->id; ?>&w=<?php echo $linkWidth; ?>&h=<?php echo $linkHeight; ?>&m=padded"><?php echo $size; ?></a>&nbsp;
                                            <?php
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="first share-file-table-header">
                                        <?php echo UCWords(t('custom_size', 'custom size')); ?>:
                                    </td>
                                    <td>
                                        <form id="form-join" method="POST" action="<?php echo _CONFIG_SITE_PROTOCOL . '://' . file::getFileDomainAndPath($file->id, $file->serverId, true) . '/' . PLUGIN_DIRECTORY_NAME; ?>/imageviewer/site/resize_image.php?idt=<?php echo $embedToken; ?>&f=<?php echo (int) $file->id; ?>" onSubmit="return validateResizeForm();">
                                            <input type="text" id="resize_w" name="w" placeholder="w" style="width: 30px; padding: 4px;"/>px&nbsp;
                                            <input type="text" id="resize_h" name="h" placeholder="h" style="width: 30px; padding: 4px;"/>px&nbsp;
                                            <input type="submit" name="submit" value="<?php echo t('resize', 'resize'); ?>" class="submitInput ui-button ui-widget ui-state-default ui-corner-all"/>
                                        </form>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clear"><!-- --></div>
                    <?php
                }
                ?>

                <div id="pageHeader" style="padding-top: 12px;">
                    <h2>
                        <?php
                        if ((int) $pluginSettings['show_embedding'] == 1)
                        {
                            echo t("embed_code", "embed code");
                        }
                        else
                        {
                            echo t("download_urls", "download urls");
                        }
                        ?>
                    </h2>
                </div>
                <div>
                    <table class="accountStateTable table table-bordered table-striped">
                        <tbody>
    <?php
    if ((int) $pluginSettings['show_embedding'] == 1)
    {
        ?>
                                <tr>
                                    <td class="first share-file-table-header">
        <?php echo t('html_thumb_code', 'HTML Thumbnail Code'); ?>:
                                    </td>
                                    <td class="htmlCode ltrOverride">
                                        &lt;a href=&quot;<?php echo $file->getFullShortUrl(); ?>&quot; target=&quot;_blank&quot; title=&quot;<?php echo t('download_from', 'Download from'); ?> <?php echo SITE_CONFIG_SITE_NAME; ?>&quot;&gt;&lt;img src=&quot;<?php echo WEB_ROOT; ?>/plugins/imageviewer/site/thumb.php?s=<?php echo $file->shortUrl; ?>&quot;/&gt;&lt;/a&gt;
                                    </td>
                                </tr>
                                <tr>
                                    <td class="first share-file-table-header">
        <?php echo UCWords(t('forum_thumb_code', 'Forum Thumbnail Code')); ?>
                                    </td>
                                    <td class="htmlCode ltrOverride">
                                        [URL=<?php echo $file->getFullShortUrl(); ?>][IMG]<?php echo WEB_ROOT; ?>/plugins/imageviewer/site/thumb.php?s=<?php echo $file->shortUrl; ?>[/IMG][/URL]
                                    </td>
                                </tr>
                                <tr>
                                    <td class="first share-file-table-header">
        <?php echo UCWords(t('thumb_url', 'Thumbnail Url')); ?>
                                    </td>
                                    <td class="htmlCode">
        <?php echo WEB_ROOT; ?>/plugins/imageviewer/site/thumb.php?s=<?php echo $file->shortUrl; ?>
                                    </td>
                                </tr>
        <?php
    }
    else
    {
        ?>
                                <tr>
                                    <td class="first share-file-table-header">
        <?php echo t('html_code', 'HTML Code'); ?>:
                                    </td>
                                    <td class="htmlCode">
                                        <?php echo $file->getHtmlLinkCode(); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="first share-file-table-header">
        <?php echo UCWords(t('forum_code', 'forum code')); ?>
                                    </td>
                                    <td class="htmlCode">
                                        <?php echo $file->getForumLinkCode(); ?>
                                    </td>
                                </tr>
        <?php
    }
    ?>
                            <tr>
                                <td class="first share-file-table-header">
    <?php echo UCWords(t('full_image_url', 'Full Image Url')); ?>
                                </td>
                                <td class="htmlCode">
    <?php echo $file->getFullShortUrl(); ?>
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
                                <td class="first share-file-table-header">
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
elseif ((isset($_REQUEST['idt'])) && (in_array(strtolower($file->extension), $ext)))
{
    $directDownloadUrl = $file->generateDirectDownloadUrl();
    coreFunctions::redirect($directDownloadUrl);
}
