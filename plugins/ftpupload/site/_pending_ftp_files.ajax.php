<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// setup db connection
$db = Database::getDatabase(true);

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('ftpupload');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

// get ftp details
$pluginObj = pluginHelper::getInstance('ftpupload');
$ftpAcc    = $pluginObj->getFTPAccountDetails($Auth->id);
if ($ftpAcc['success'] == false)
{
    exit;
}

// check for pending files, connect via ftp
$ftpHost = strlen($pluginSettings['ftp_host_override']) ? $pluginSettings['ftp_host_override'] : $pluginSettings['connection_cpanel_host'];
$conn_id = ftp_connect($ftpHost, 21, 10);
if ($conn_id === false)
{
    echo "FTP ERROR: Failed connecting to " . $ftpHost . " via FTP.<br/><br/>";
    exit;
}

// authenticate
$username = $ftpAcc['ftp_user'];
if ((isset($pluginSettings['append_username'])) && (strlen($pluginSettings['append_username'])))
{
    $username = $ftpAcc['ftp_user'] . '@' . $pluginSettings['append_username'];
}
$login_result = ftp_login($conn_id, $username, $ftpAcc['ftp_password']);
if ($login_result === false)
{
    echo "FTP ERROR: Could not authenticate with FTP server " . $ftpHost . " with user " . $username . "<br/><br/>";
    exit;
}

// look for any uploaded files
$file_listing = ftp_nlist($conn_id, '.');

// loop result
$files = array();
if (COUNT($file_listing))
{
    foreach ($file_listing AS $file_listing_item)
    {
        $file_listing_item = utf8_encode($file_listing_item);
        if ((in_array($file_listing_item, array('..', '.'))) || (strpos($file_listing_item, '.', 1) === false))
        {
            continue;
        }

        $files[] = $file_listing_item;
    }
}

// close ftp connection
ftp_close($conn_id);

if (COUNT($files) > 0)
{
    ?>
    <div id="ftpFileUploader" class="ftp-file-uploader">
        <div>
            <?php echo t("plugin_ftp_your_pending_ftp_uploads_are_shown_below", "Your pending FTP uploads are shown below. Once these have finished uploading via FTP, click the 'transfer files' button to move them into your account."); ?>
        </div>
        <div>
            <table class="accountStateTable table table-striped">
                <tbody>
                    <tr>
                        <td class="first"><?php echo t("plugin_ftp_pending_files", "Pending Files"); ?>:</td>
                        <td><?php echo COUNT($files); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="clear"><!-- --></div>
        <div class="transfer-files-button-image">
            <div onclick="transferFtpFiles(); return false;" title="transfer files" class="transferFilesButton" id="transferFilesButton"><!-- --></div>
        </div>
        <div class="transfer-files-button-html">
            <button type="button" class="btn btn-green btn-lg" onclick="transferFtpFiles(); return false;"><?php echo t("set_transfer_files", "Transfer Files"); ?> <i class="entypo-upload"></i></button>
        </div>
        <textarea id="ftp_file_listing" style="display:none;"><?php echo validation::safeOutputToScreen(implode("|", $files)); ?></textarea>
        <div class="clear"><!-- --></div>

        <div class="uploadText">
            <h2><?php echo t('ftp_details', 'FTP Details'); ?>:</h2>
        </div>
        <div class="clearLeft"><!-- --></div>
    </div>

    <div class="ftpFileListingWrapper hidden" id="ftpFileListingWrapper">
        <div class="fileSection">
            <table width="100%" class="files table table-striped" id="urls">
                <tbody>
                </tbody>
            </table>
        </div>
        <div class="clearLeft"><!-- --></div>

        <div class="fileSectionFooterText hidden">
            <div class="baseText" style="margin-top: 12px;">
                <?php echo t("plugin_ftp_file_transfer_completed", "File transfers completed."); ?>
            </div>
            <div class="clear"><!-- --></div>
        </div>
    </div>
    <?php
}
