<?php
// get user
$Auth = Auth::getAuth();

// load plugin details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('fileleech');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

// check show tab setting
$showTab = false;
if($pluginSettings['show_leech_tab'] == 1)
{
    $showTab = true;
}

// check user can access it
$userAllowed = false;
if(($Auth->level_id == 0) && ($pluginSettings['enabled_non_user'] == 1))
{
    $userAllowed = true;
}
elseif(($Auth->level_id == 1) && ($pluginSettings['enabled_free_user'] == 1))
{
    $userAllowed = true;
}
elseif(($Auth->level_id >= 2) && ($pluginSettings['enabled_paid_user'] == 1))
{
    $userAllowed = true;
}
if($userAllowed == false)
{
    $showTab = false;
}

if ($showTab == true)
{
    // get restrictions
    $maxPermittedUrls = (int)UserPeer::getMaxRemoteUrls();
    ?>

    <script>
    function urlLeechUploadFiles()
    {
        $('#urlList').val($('#urlLeechList').val());
        
        // get textarea contents
        urlList = $('#urlList').val();
        if(urlList.length == 0)
        {
            alert('<?php echo str_replace("'", "\'", t('please_enter_the_urls_to_start', 'Please enter the urls to start.')); ?>');
            return false;
        }
        
        // get file list as array
        urlList = findUrls(urlList);
        totalUrlItems = urlList.length;
    
        // first check to make sure we have some urls
        if(urlList.length == 0)
        {
            alert('<?php echo str_replace("'", "\'", t('no_valid_urls_found_please_make_sure_any_start_with_http_or_https', 'No valid urls found, please make sure any start with http or https and try again.')); ?>');
            return false;
        }
        
        // make sure the user hasn't entered more than is permitted
        if(urlList.length > <?php echo (int)$maxPermittedUrls; ?>)
        {
            alert('<?php echo str_replace("'", "\'", t('you_can_not_add_more_than_x_urls_at_once', 'You can not add more than [[[MAX_URLS]]] urls at once.', array('MAX_URLS'=>(int)$maxPermittedUrls))); ?>');
            return false;
        }
        
        $('#fileLeech').hide();
        $('#urlUpload').show();
        urlUploadFiles();
    }
    </script>

    <!-- FILE LEECH -->
    <div id="fileLeech" class="tab-pane">
        <div class="urlUploadMain ui-corner-all">
            <div id="fileUploadBadge" class="fileUploadBadge"></div>
            <div class="urlUploadMainInternal contentPageWrapper" style="width: auto;">
                <div>
                    <div class="initialUploadText">
                        <div class="uploadText">
                            <h2><?php echo t('plugin_fileleech_file_leech', 'File Leech'); ?>:</h2>
                        </div>
                        <div class="clearLeft"><!-- --></div>

                        <div>
                            <?php
                            $showContent = true;
                            if ($showContent)
                            {
                                // max allowed upload size
                                $maxUploadSize = (int)UserPeer::getMaxUploadFilesize();

                                // get accepted file types
                                $acceptedFileTypes = UserPeer::getAcceptedFileTypes();
                                ?>
                                    <div id="urlFileLeechUploader">
                                        <form action="<?php echo file::getUploadUrl(); ?>/core/ajax/url_upload_handler.php" method="POST" enctype="multipart/form-data">
                                            <div class="fileLeechTextBox">
                                                <?php echo t("plugin_fileleech_tab_content_intro", "Instantly download files from other file hosting sites without a paid account. Just paste the urls below and we'll download it for you!"); ?> 
                                                <?php echo t('plugin_fileleech_supported_sites_list', '4Shared, Bitshare, FileFactory, MediaFire, Netload and more.'); ?><br/><br/>
                                            </div>
                                            <div class="initialUploadText">
                                                <div class="inputElement">
                                                    <textarea name="urlLeechList" id="urlLeechList" class="urlLeechList form-control" placeholder="http://file-hosting-site.com/file.zip"></textarea>
                                                    <div class="clear"><!-- --></div>
                                                </div>
                                            </div>
                                            <div class="urlUploadFooter">
                                                <div class="upload-button upload-button-v2">
                                                    <button id="transferFilesButton" onClick="urlLeechUploadFiles(); return false;" class="btn btn-green btn-lg" type="button"><?php echo t("set_transfer_files", "Transfer Files"); ?> <i class="entypo-upload"></i></button>
                                                </div>

                                                <div id="transferFilesButton" class="transferFilesButton" title="leech files" onClick="urlLeechUploadFiles();
                                                            return false;"><!-- --></div>
                                                <div class="baseText">
                                                    <a class="showAdditionalOptionsLink"><?php echo UCFirst(t('options', 'options')); ?></a>&nbsp;&nbsp;|&nbsp;&nbsp;<?php echo t('enter_up_to_x_file_urls', 'Enter up to [[[MAX_REMOTE_URL_FILES]]] file urls. Separate each url on it\'s own line.', array('MAX_REMOTE_URL_FILES' => $maxPermittedUrls)); ?>
                                                </div>
                                                <div class="clear"><!-- --></div>
                                            </div>
                                            <div class="clear"><!-- --></div>
                                        </form>
                                    </div>
                                <?php
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