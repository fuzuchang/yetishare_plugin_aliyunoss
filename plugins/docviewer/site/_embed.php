<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('docviewer');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// do not allow if embed options are disabled
if ((int) $pluginSettings['show_embed'] == 0)
{
    // embedding disabled
    coreFunctions::output404();
}

// try to load the file object
$file = null;
if (isset($_REQUEST['u']))
{
    $file = file::loadByShortUrl($_REQUEST['u']);
}

/* load file details */
if (!$file)
{
    /* if no file found, redirect to home page */
    coreFunctions::redirect(WEB_ROOT . "/index." . SITE_CONFIG_PAGE_EXTENSION);
}

if($file->fileSize >= 26214400)
{
    // file to big
    coreFunctions::output404();
}

// embed size
if(isset($_REQUEST['w']))
{
    $w = (int)$_REQUEST['w'];
}
if(isset($_REQUEST['h']))
{
    $h = (int)$_REQUEST['h'];
}
define("EMBED_WIDTH", $w?$w:$pluginSettings['embed_document_size_w']);
define("EMBED_HEIGHT", $h?$h:$pluginSettings['embed_document_size_h']);

// load available extensions for non_document_types user
$ext = explode(",", $pluginSettings['non_document_types']);

// if this is a download request
if (!in_array(strtolower($file->extension), $ext))
{
    // file not permitted
    coreFunctions::output404();
}

// setup database
$db = Database::getDatabase();

// prepare trimmed header
$headerTitle = $file->originalFilename;
if (strlen($headerTitle) > 60)
{
    $headerTitle = substr($headerTitle, 0, 55) . '...' . end(explode(".", $headerTitle));
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo validation::safeOutputToScreen(PAGE_NAME); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?></title>
        <meta name="description" content="<?php echo validation::safeOutputToScreen(PAGE_DESCRIPTION); ?>" />
        <meta name="keywords" content="<?php echo validation::safeOutputToScreen(PAGE_KEYWORDS); ?>" />
        <meta name="copyright" content="Copyright &copy; <?php echo date("Y"); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>" />
        <meta name="robots" content="all" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="Expires" content="-1" />
        <meta http-equiv="Pragma" content="no-cache" />
        <link rel="stylesheet" href="<?php echo SITE_CSS_PATH; ?>/screen.css" type="text/css" charset="utf-8" />
        <script type="text/javascript" src="<?php echo SITE_JS_PATH; ?>/js/jquery-1.9.1.js"></script>
    </head>

    <body style="width: <?php echo EMBED_WIDTH; ?>px;">
        <div class="embedded">
            <div style="width: <?php echo EMBED_WIDTH; ?>px;">
                <iframe src="https://view.officeapps.live.com/op/view.aspx?src=<?php echo $file->generateDirectDownloadUrlForMedia(); ?>&embedded=true" height="<?php echo EMBED_HEIGHT; ?>" width="100%" frameborder="0" style="border:1px solid #ddd;"></iframe>
            </div>
        </div>
    </body>
</html>
