<?php
// get user
$Auth = Auth::getAuth();

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('ftpupload');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

$doRedirect = true;
if (($pluginSettings['paid_only'] == 1) && ($Auth->level_id <= 1))
{
    $doRedirect = false;
}

$showTab = false;
if (($pluginSettings['show_ftp_tab'] == 1) || ($doRedirect == true))
{
    $showTab = true;
}

if ($showTab == true)
{
    ?>

    <script>
        var ftpFileList = null;
        $(document).ready(function() {
            checkPendingTransfers();
        });
        
        function checkPendingTransfers()
        {
            // check for pending files
            $('#pendingFilesWrapper').load('<?php echo PLUGIN_WEB_ROOT . '/' . $pluginConfig['data']['folder_name'] . '/site/_pending_ftp_files.ajax.php'; ?>');
        }

        function transferFtpFiles()
        {
            // get pending file listing
            ftpFileList = $('#ftp_file_listing').val().split("|");

            // create table listing
            html = '';
            for (i in ftpFileList)
            {
                html += '<tr id="ftpRowId' + i + '"><td class="cancel"><a href="#" onClick="return false;"><img src="<?php echo SITE_IMAGE_PATH; ?>/processing_small.gif" class="processingIcon" height="16" width="16" alt="<?php echo str_replace("'", "\'", t('processing', 'processing')); ?>"/>';
                html += '</a></td><td class="name" colspan="3">' + ftpFileList[i] + '</td></tr>';
            }
            $('#pendingFilesWrapper #urls').html(html);

            // show file uploader screen
            $('#ftpUpload #ftpFileListingWrapper').removeClass('hidden');
            $('#ftpUpload #ftpFileUploader').addClass('hidden');
            $('#ftpUpload #fileUploadBadge').addClass('hidden');
            $('#ftpUpload #ftpUserConnectionDetails').addClass('hidden');

            // loop over urls and try to retieve the file
            getFileViaFTP(0);
        }
        
        function getFileViaFTP(rowIndex)
        {
            if(rowIndex > ftpFileList.length - 1)
            {
                return false;
            }
            
            // call ajax request to get file
            var request = $.ajax({
                url: "<?php echo crossSiteAction::appendUrl(file::getUploadUrl() . '/'.PLUGIN_DIRECTORY_NAME.'/' . $pluginConfig['data']['folder_name'] . '/site/ftpUploadHandler.ajax.php'); ?>",
                type: "POST",
                ysrowId: i,
                xhrFields: {
                    withCredentials: true
                },
                data: {fileName: ftpFileList[rowIndex], rowId: rowIndex},
                dataType: "json"
            });

            request.done(function(data) {
                var isSuccess = true;
                if (data.error != null)
                {
                    isSuccess = false;
                }

                var html = '';
                html += '<tr class="template-download';
                if (isSuccess == false)
                {
                    html += ' errorText';
                }
                html += '" onClick="return showAdditionalInformation(this);">'
                if (isSuccess == false)
                {
                    // add result html
                    html += data.error_result_html;
                }
                else
                {
                    // add result html
                    html += data.success_result_html;

                    // keep a copy of the urls globally
                    fileUrls.push(data.url);
                    fileDeleteHashes.push(data.delete_hash);
                    fileShortUrls.push(data.short_url);
                }

                html += '</tr>';

                $('#ftpRowId' + data.rowId).replaceWith(html);

                // completed uploading
                if (data.rowId == ftpFileList.length - 1)
                {
                    // show footer
                    $('#ftpUpload .fileSectionFooterText').removeClass('hidden');

                    <?php if(version_compare(_CONFIG_SCRIPT_VERSION, '3.3') >= 0): ?>
                        // update email/password
                        sendAdditionalOptions();
                    <?php endif; ?>
                }
                else
                {
                    // next item
                    nextItem = rowIndex+1;
                    getFileViaFTP(nextItem);
                }
            });

            request.fail(function(jqXHR, textStatus) {
                $('#ftpRowId'+this.ysrowId+' .cancel .processingIcon').attr('src', '<?php echo SITE_IMAGE_PATH; ?>/red_error_small.png');
                $('#ftpRowId'+this.ysrowId+' .name').html('Failed to get file, possible ajax issue');
                
                // next item
                nextItem = rowIndex+1;
                getFileViaFTP(nextItem);
            });
        }
    </script>

    <!-- FTP UPLOAD -->
    <div id="ftpUpload" class="tab-pane">
        <div class="urlUploadMain ui-corner-all">
            <div id="fileUploadBadge" class="fileUploadBadge"></div>
            <div class="urlUploadMainInternal contentPageWrapper" style="width: auto;">
                <div>
                    <div class="initialUploadText">
                        <div class="uploadText">
                            <h2><?php echo t('ftp_upload', 'FTP Upload'); ?>:</h2>
                        </div>
                        <div class="clearLeft"><!-- --></div>

                        <div>
                            <?php
                            $showContent = true;
                            if (($pluginSettings['paid_only'] == 1) && ($Auth->level_id <= 1))
                            {
                                $showContent = false;
                            }

                            if (($showContent == true) && ($Auth->loggedIn() == false))
                            {
                                $showContent = false;
                            }

                            if ($showContent)
                            {
                                // make sure we have an ftp account for the logged in user
                                $pluginObj = pluginHelper::getInstance('ftpupload');
                                $rs        = $pluginObj->getFTPAccountDetails($Auth->id);
                                if ($rs['success'] == false)
                                {
                                    echo t("plugin_ftp_error_loading_ftp_details", "There was an error loading your FTP details, please contact support for more information.");
                                    echo '<br/><br/>';
                                    echo t("error_message", "Error message");
                                    echo ": ";
                                    echo $rs['msg'];
                                }
                                else
                                {
                                    // max allowed upload size
                                    $maxUploadSize = (int)UserPeer::getMaxUploadFilesize();

                                    // get accepted file types
                                    $acceptedFileTypes = UserPeer::getAcceptedFileTypes();
                                    ?>

                                    <div id="pendingFilesWrapper"><!-- --></div>

                                    <div id="ftpUserConnectionDetails">
                                        <div class="ftpTextBox">
                                            <?php echo t("plugin_ftp_use_the_ftp_details_below_to_connect_updated", "Please use the FTP details below to connect and upload files using an FTP client such as <a href=\"http://filezilla-project.org/\" target=\"_blank\">FileZilla</a>. Once you've completed your uploads, <a href='#' onClick='checkPendingTransfers(); return false;'>click here</a> to import them into your account."); ?>
                                        </div>
                                        <div class="ftpTextBox2">
                                            <table class="accountStateTable table table-striped">
                                                <tbody>
                                                    <tr>
                                                        <td class="first"><?php echo t("plugin_ftp_ftp_host", "FTP Host"); ?>:</td>
                                                        <td><?php $ftpHost = strlen($pluginSettings['ftp_host_override'])?$pluginSettings['ftp_host_override']:$pluginSettings['connection_cpanel_host']; echo $ftpHost; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="first"><?php echo t("plugin_ftp_ftp_username", "FTP Username"); ?>:</td>
                                                        <td>
                                                            <?php
                                                            echo $rs['ftp_user'];
                                                            if((isset($pluginSettings['append_username'])) && (strlen($pluginSettings['append_username'])))
                                                            {
                                                                echo '@'.$pluginSettings['append_username'];
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="first"><?php echo t("plugin_ftp_ftp_password", "FTP Password"); ?>:</td>
                                                        <td><?php echo $rs['ftp_password']; ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="baseText" style="margin-top: 12px;">
                                            <?php if(version_compare(_CONFIG_SCRIPT_VERSION, '3.3') >= 0): ?><a class="showAdditionalOptionsLink"><?php echo UCFirst(t('options', 'options')); ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<?php endif; ?><?php echo t("plugin_ftp_maximum_filesize_of", "Maximum file size of"); ?> <?php echo coreFunctions::formatSize($maxUploadSize); ?> <?php echo t("plugin_ftp_applies", "applies"); ?>. <?php echo COUNT($acceptedFileTypes) ? (t('allowed_file_types', 'Allowed file types') . ': ' . str_replace(".", "", implode(", ", $acceptedFileTypes)) . '.') : ''; ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            else
                            {
                                echo t("plugin_ftp_you_need_an_account_upload_file_via_ftp", "You need an account to upload files using FTP. Go to the <a href='[[[WEB_ROOT]]]/register.[[[SITE_CONFIG_PAGE_EXTENSION]]]'>registration page</a> to create an account now.", array('WEB_ROOT'                   => WEB_ROOT, 'SITE_CONFIG_PAGE_EXTENSION' => SITE_CONFIG_PAGE_EXTENSION));
                            }
                            ?>
                        </div>
                    </div>
                    <div class="clear"><!-- --></div>
                </div>

                <div class="clear"><!-- --></div>
            </div>
        </div>
    </div>
    <?php
}
?>
