<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// load plugin details
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('uploadwidget');
$pluginConfig   = $pluginDetails['config'];
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// if plugin not installed 
if (!$pluginConfig)
{
    die('Upload widget unavailable, please <a href="'.WEB_ROOT.'" target="_blank">go here</a> for our main site.');
}

// if plugin disabled
if (pluginHelper::pluginEnabled('uploadwidget') == false)
{
    die('Upload widget unavailable, please <a href="'.WEB_ROOT.'" target="_blank">go here</a> for our main site.');
}

// whether to allow chunked uploaded. Recommend to keep as true unless you're experiencing issues.
define('USE_CHUNKED_UPLOADS', true);

// asset path
define('PLUGIN_ASSET_PATH', PLUGIN_WEB_ROOT.'/uploadwidget/assets/');

// get Auth
$Auth = Auth::getAuth();

// max allowed upload size & max permitted urls
$maxUploadSize    = (int)UserPeer::getMaxUploadFilesize();
$maxPermittedUrls = (int)UserPeer::getMaxRemoteUrls();

// get accepted file types
$acceptedFileTypes = UserPeer::getAcceptedFileTypes();

// whether to allow uploads or not
$showUploads = true;
if (UserPeer::getAllowedToUpload() == false)
{
    $showUploads = false;
}

// setup database
$db = Database::getDatabase();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Embedded Uploader - <?php echo SITE_CONFIG_SITE_NAME; ?></title>
        <meta name="description" content="Embedded uploader" />
        <meta name="keywords" content="embedded, uploader" />
        <meta name="copyright" content="Copyright &copy; <?php echo date("Y"); ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>" />
        <meta name="robots" content="all" />
        <meta http-equiv="Cache-Control" content="no-cache" />
        <meta http-equiv="Expires" content="-1" />
        <meta http-equiv="Pragma" content="no-cache" />
        <link rel="stylesheet" href="<?php echo PLUGIN_ASSET_PATH; ?>css/minimal.css" type="text/css" charset="utf-8" />
        <script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery-1.9.1.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery-ui.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery.tmpl.min.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/load-image.min.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/canvas-to-blob.min.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery.iframe-transport.js"></script>
        <script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery.fileupload.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery.fileupload-process.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery.fileupload-resize.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery.fileupload-validate.js"></script>
        <script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/jquery.fileupload-ui.js"></script>
		<script type="text/javascript" src="<?php echo PLUGIN_ASSET_PATH; ?>js/zeroClipboard/ZeroClipboard.js"></script>
    </head>
	
	<?php
	require_once('_embed_minimal.js.php');
	?>

    <body>
		<!-- FILE UPLOAD -->
        <div id="fileUpload">
            <div class="fileUploadMain ui-corner-all">
                <div class="fileUploadMainInternal contentPageWrapper" <?php if ($showUploads == false) echo 'onClick="window.top.location.href=\''.WEB_ROOT.'/register.' . SITE_CONFIG_PAGE_EXTENSION . '\';"'; ?>>

                    <!-- uploader -->
                    <div id="uploaderContainer" class="uploaderContainer">

                        <div id="fileupload">
                            <form action="<?php echo file::getUploadUrl(); ?>/core/page/ajax/file_upload_handler.ajax.php?r=<?php echo htmlspecialchars(_CONFIG_SITE_HOST_URL); ?>&p=<?php echo htmlspecialchars(_CONFIG_SITE_PROTOCOL); ?>" method="POST" enctype="multipart/form-data">
                                <div class="fileupload-buttonbar">
                                    <label class="fileinput-button">
                                        <span><?php echo t('select_file_max', 'Select File (max: [[[MAX_SIZE]]])...', array('MAX_SIZE'=>coreFunctions::formatSize($maxUploadSize))); ?></span>
                                        <?php
                                        if ($showUploads == true)
                                        {
                                            echo '<input id="add_files_btn" type="file" name="files[]" multiple>';
                                        }
                                        ?>
                                    </label>
                                    <button id="start_upload_btn" type="submit" class="start"><?php echo t('start_upload', 'Start upload'); ?></button>
                                </div>
                                <div class="fileupload-content">
                                    <div id="fileListingWrapper" class="fileListingWrapper hidden">
                                        <div class="fileSection">
                                            <table id="files" class="files" width="100%"><tbody></tbody></table>
                                            <table id="addFileRow" class="addFileRow" width="100%">
                                                <tr class="template-upload">
                                                </tr>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </form>
                        </div>
                        <script id="template-upload" type="text/x-jquery-tmpl">
                            {% for (var i=0, file; file=o.files[i]; i++) { %}
                            <tr class="template-upload{% if (file.error) { %} errorText{% } %}" id="fileUploadRow{%=i%}">
                            <td class="cancel">
                            <a href="#" onClick="return false;">
                            <img src="<?php echo SITE_IMAGE_PATH; ?>/delete_small.png" height="10" width="10" alt="<?php echo t('delete', 'delete'); ?>"/>
                            </a>
                            </td>
                            <td class="name">{%=file.name%}&nbsp;&nbsp;{%=o.formatFileSize(file.size)%}
                            {% if (!file.error) { %}
                            <div class="start hidden"><button>start</button></div>
                            {% } %}
                            <div class="cancel hidden"><button>cancel</button></div>
                            </td>
                            {% if (file.error) { %}
                            <td colspan="2" class="error">Error:
                            {%=file.error%}
                            </td>
                            {% } else { %}
                            <td colspan="2"><span class="fade"></span></td>
                            {% } %}
                            </tr>
                            {% } %}
                        </script>

                        <script id="template-download" type="text/x-jquery-tmpl">
                        </script>

                    </div>
                    <!-- end uploader -->

                </div>

                <div class="clear"><!-- --></div>
            </div>
        </div>
    </body>
</html>
