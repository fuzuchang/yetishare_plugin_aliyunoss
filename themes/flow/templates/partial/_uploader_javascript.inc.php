<?php

// for js translations
t('uploader_hour', 'hour');
t('uploader_hours', 'hours');
t('uploader_minute', 'minute');
t('uploader_minutes', 'minutes');
t('uploader_second', 'second');
t('uploader_seconds', 'seconds');

$fid = null;
if(isset($_REQUEST['fid']))
{
    $fid = (int)$_REQUEST['fid'];
}



?>
<script>
    var AliyunOSSEnabled = 0;
</script>
<?php if (is_dir('plugins/aliyunoss')) { ?>
<!--阿里云OSS ***************************************阿里云OSS ***************************************-->
<script>
    AliyunOSSEnabled    = <?php $plugin   = $db->getRow("SELECT * FROM plugin WHERE folder_name = 'aliyunoss' LIMIT 1"); echo (int) $plugin['plugin_enabled'];?>;
var AliyunOSSPolicyUrl  = "http://<?php echo _CONFIG_SITE_HOST_URL; ?>/plugins/aliyunoss/site/policy.php";
</script>
<script src="http://<?php echo _CONFIG_SITE_HOST_URL; ?>/plugins/aliyunoss/assets/js/alioss.js"></script>
<!--阿里云OSS ***************************************阿里云OSS ***************************************-->
<?php } ?>

<script>
    var fileUrls = [];
    var fileDeleteHashes = [];
    var fileShortUrls = [];
    var lastEle = null;
    var startTime = null;
    var fileToEmail = '';
    var filePassword = '';
    var fileFolder = '';
    var uploadComplete = true;
    $(document).ready(function() {
        document.domain = '<?php echo coreFunctions::getDocumentDomain(); ?>';

<?php
if ($showUploads == true)
{
    // figure out max files
    $maxFiles = UserPeer::getMaxUploadsAtOnce($Auth->package_id);
    
    // failsafe
    if((int)$maxFiles == 0)
    {
        $maxFiles = 50;
    }
    
    // if php restrictions are lower than permitted, override
    $phpMaxSize = coreFunctions::getPHPMaxUpload();
    $maxUploadSizeNonChunking = 0;
    if ($phpMaxSize < $maxUploadSize)
    {
        $maxUploadSizeNonChunking = $phpMaxSize;
    }
    
    ?>
        // figure out if we should use 'chunking'
        var maxChunkSize = 0;
        var uploaderMaxSize = <?php echo (int)$maxUploadSizeNonChunking; ?>;
        <?php if(USE_CHUNKED_UPLOADS == true): ?>
        if(browserXHR2Support() == true)
        {
            maxChunkSize = <?php echo (coreFunctions::getPHPMaxUpload()>CHUNKED_UPLOAD_SIZE?CHUNKED_UPLOAD_SIZE:coreFunctions::getPHPMaxUpload()-5000); // in bytes, allow for smaller PHP upload limits ?>;
            var uploaderMaxSize = <?php echo $maxUploadSize; ?>;

            if (AliyunOSSEnabled){
                maxChunkSize *= 10;
            }
        }
        <?php endif; ?>

        // Initialize the jQuery File Upload widget:
        $('#fileUpload #uploader').fileupload({
            sequentialUploads: true,
            url: '<?php echo crossSiteAction::appendUrl(file::getUploadUrl().'/core/page/ajax/file_upload_handler.ajax.php?r='.htmlspecialchars(_CONFIG_SITE_HOST_URL).'&p='.htmlspecialchars(_CONFIG_SITE_PROTOCOL)); ?>',
            maxFileSize: uploaderMaxSize,
            formData: {},
            xhrFields: {
                withCredentials: true
            },
            getNumberOfFiles: function () {
                return getTotalRows();
            },
            messages: {
                maxNumberOfFiles: '<?php echo str_replace("'", "\'", t('file_upload_maximum_number_of_files_exceeded', 'Maximum number of files exceeded')); ?>',
                acceptFileTypes: '<?php echo str_replace("'", "\'", t('file_upload_file_type_not_allowed', 'File type not allowed')); ?>',
                maxFileSize: '<?php echo str_replace("'", "\'", t('file_upload_file_is_too_large', 'File is too large')); ?>',
                minFileSize: '<?php echo str_replace("'", "\'", t('file_upload_file_is_too_small', 'File is too small')); ?>'
            },
            maxChunkSize: maxChunkSize,
<?php echo COUNT($acceptedFileTypes) ? ('acceptFileTypes: /(\\.|\\/)(' . str_replace(".", "", implode("|", $acceptedFileTypes) . ')$/i,')) : ''; ?> maxNumberOfFiles: <?php echo (int)$maxFiles; ?>
                })
                .on('fileuploadadd', function(e, data) {
                    $('#fileUpload #uploader #fileListingWrapper').removeClass('hidden');
                    $('#fileUpload #uploader #initialUploadSection').addClass('hidden');

                    // fix for safari
                    getTotalRows();
                    // end safari fix

                    totalRows = getTotalRows()+1;
                    updateTotalFilesText(totalRows);

                })
                .on('fileuploadstart', function(e, data) {
                    uploadComplete = false;

                    // hide/show sections
                    $('#fileUpload #addFileRow').addClass('hidden');
                    $('#fileUpload #processQueueSection').addClass('hidden');
                    $('#fileUpload #processingQueueSection').removeClass('hidden');

                    // set all cancel icons to processing
                    $('#fileUpload .cancel').html('<img class="processingIcon" src="<?php echo SITE_IMAGE_PATH; ?>/processing_small.gif" width="16" height="16"/>');
                    
                    // remove cancel file onclick option
                    $('#fileUpload .cancel').click(function() { return false; } );

                    // set timer
                    startTime = (new Date()).getTime();
                })
                .on('fileuploadstop', function(e, data) {
                    // finished uploading
                    updateTitleWithProgress(100);
                    updateProgessText(100, data.total, data.total);
                    $('#fileUpload #processQueueSection').addClass('hidden');
                    $('#fileUpload #processingQueueSection').addClass('hidden');
                    $('#fileUpload #completedSection').removeClass('hidden');

                    // set all remainging pending icons to failed
                    $('#fileUpload .processingIcon').parent().html('<img src="<?php echo SITE_IMAGE_PATH; ?>/red_error_small.png" width="16" height="16"/>');

                    uploadComplete = true;
                    sendAdditionalOptions();

                    // setup copy link
                    setupCopyAllLink();
                    
                    // refresh treeview
                    if(typeof(checkShowUploadFinishedWidget) === 'function')
                    {
                        refreshFolderListing(false);
						loadFolderFiles($('#nodeId').val());
                    }

                    if(typeof(checkShowUploadFinishedWidget) === 'function')
                    {
                        checkShowUploadFinishedWidget();
                    }
                })
                .on('fileuploadprogressall', function(e, data) {
                    // progress bar
                    var progress = parseInt(data.loaded / data.total * 100, 10);
                    $('#progress .progress-bar').css(
                        'width',
                        progress + '%'
                    );

                    // update page title with progress
                    updateTitleWithProgress(progress);
                    updateProgessText(progress, data.loaded, data.total);
                })
                .on('fileuploaddone', function(e, data) {
                    // keep a copy of the urls globally
                    if (typeof alioss == 'object'){
                        alioss.set_upload_done_param(data);
                    }

                    fileUrls.push(data['result'][0]['url']);
                    fileDeleteHashes.push(data['result'][0]['delete_hash']);
                    fileShortUrls.push(data['result'][0]['short_url']);

                    var isSuccess = true;
                    if(data['result'][0]['error'] != null)
                    {
                        isSuccess = false;
                    }

                    var html = '';
                    html += '<tr class="template-download';
                    if(isSuccess == false)
                    {
                        html += ' errorText';
                    }
                    html += '" ';
                    if(isSuccess == true)
                    {
                        html += 'onClick="return showAdditionalInformation(this);"';
                    }
                    html += '>';

                    if(isSuccess == true)
                    {
                        html += data['result'][0]['success_result_html'];
                    }
                    else
                    {
                        html += data['result'][0]['error_result_html'];
                    }
                    html += '</tr>';

                    // update screen with success content
                    $(data['context'])
                        .replaceWith(html);
                })
                .on('fileuploadfail', function(e, data) {
					// cancel button
					if(data.errorThrown == 'abort')
					{
						$(data['context']).remove();
						
						// count total rows
						totalRows = getTotalRows();
						
						// if zero rows, revert to the original display
						if(totalRows == 0)
						{
							// hide/show sections
							$('#fileUpload #uploader #fileListingWrapper').addClass('hidden');
							$('#fileUpload #uploader #initialUploadSection').removeClass('hidden');
						}
					}
					else
					{
						// update screen with error content, ajax issues
						var html = '';
						html += '<tr class="errorText"><td colspan="4">';
						html += '<?php echo t('indexjs_error_server_problem', 'ERROR: There was a server problem when attempting the upload, please try again later.'); ?>';
						html += '</td></tr>';
						$(data['context'])
							.replaceWith(html);
					}
                    
                    totalRows = getTotalRows();
                    if(totalRows > 0)
                    {
                        totalRows = totalRows-1;
                    }

                    updateTotalFilesText(totalRows);
                });

                // Open download dialogs via iframes,
                // to prevent aborting current uploads:
                $('#fileUpload #uploader #files a:not([target^=_blank])').on('click', function (e) {
                    e.preventDefault();
                    $('<iframe style="display:none;"></iframe>')
                    .prop('src', this.href)
                    .appendTo('body');
                });

                $('#fileUpload #uploader').bind('fileuploadsubmit', function (e, data) {
                    // The example input, doesn't have to be part of the upload form:
                    data.formData = {
                        _sessionid: '<?php echo session_id(); ?>',
                        cTracker: '<?php echo MD5(microtime()); ?>',
                        maxChunkSize: maxChunkSize,
                        folderId: fileFolder,
                        "x:filename":data.originalFiles[0].name,
                        "x:user":'<?php $Auth  = Auth::getAuth();$_x_user_id = $Auth->loggedIn() ? $Auth->id : -1 ; echo $_x_user_id; ?>'
                    };
                    if (typeof alioss == 'object') {
                        alioss.set_policy_param(data);
                    }
                });
    <?php
}
?>
        
        $('.showAdditionalOptionsLink').click(function (e) {
            // show panel
            showAdditionalOptions();
            
            // prevent background clicks
            e.preventDefault();

            return false;
        });
        
        <?php if($fid != null): ?>
        saveAdditionalOptions(true);
        <?php endif; ?>
    });
    
    function setUploadFolderId(folderId)
    {
        if (typeof (folderId != "undefined") && ($.isNumeric(folderId)))
        {
            $('#upload_folder_id').val(folderId);
        }
        else if ($('#nodeId').val() == '-1')
        {
            $('#upload_folder_id').val('');
        }
        else if ($.isNumeric($('#nodeId').val()))
        {
            $('#upload_folder_id').val($('#nodeId').val());
        }
        else
        {
            $('#upload_folder_id').val('');
        }
        saveAdditionalOptions(true);
    }
    
    function getSelectedFolderId()
    {
        return $('#upload_folder_id').val();
    }

    function setupCopyAllLink()
    {
		// update text
		$('#clipboard-placeholder').html(getUrlsAsText());
		copyToClipboard('.copyAllLink');
    }

    function updateProgessText(progress, uploadedBytes, totalBytes)
    {
        // calculate speed & time left
        nowTime = (new Date()).getTime();
        loadTime = (nowTime - startTime);
        if(loadTime == 0)
        {
            loadTime = 1;
        }
        loadTimeInSec = loadTime/1000;
        bytesPerSec = uploadedBytes / loadTimeInSec;

        textContent = '';
        textContent += '<?php echo t('indexjs_progress', 'Progress');?>: '+progress+'%';
        textContent += ' ';
        textContent += '('+bytesToSize(uploadedBytes, 2)+' / '+bytesToSize(totalBytes, 2)+')';
    
        $("#fileupload-progresstextLeft").html(textContent);
    
        rightTextContent = '';
        rightTextContent += '<?php echo t('indexjs_speed', 'Speed');?>: '+bytesToSize(bytesPerSec, 2)+'<?php echo t('indexjs_speed_ps', 'ps');?>. ';
        rightTextContent += '<?php echo t('indexjs_remaining', 'Remaining');?>: '+humanReadableTime((totalBytes/bytesPerSec)-(uploadedBytes/bytesPerSec));
    
        $("#fileupload-progresstextRight").html(rightTextContent);
        
        // progress widget for file manager
        if(typeof(updateProgressWidgetText) === 'function')
        {
            updateProgressWidgetText(textContent);
        }
    }

    function getUrlsAsText()
    {
        urlStr = '';
        for(var i=0; i<fileUrls.length; i++)
        {
            urlStr += fileUrls[i]+"\n";
        }

        return urlStr;
    }

    function updateTitleWithProgress(progress)
    {
        if(typeof(progress) == "undefined")
        {
            var progress = 0;
        }
        if(progress == 0)
        {
            $(document).attr("title", "<?php echo PAGE_NAME; ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>");
        }
        else
        {
            $(document).attr("title", progress+"% <?php echo t('indexjs_uploaded', 'Uploaded');?> - <?php echo PAGE_NAME; ?> - <?php echo SITE_CONFIG_SITE_NAME; ?>");
        }
    }

    function getTotalRows()
    {
        totalRows = $('#files .template-upload').length;
        if(typeof(totalRows) == "undefined")
        {
            return 0;
        }

        return totalRows;
    }

    function updateTotalFilesText(total)
    {
        // removed for now, might be useful in some form in the future
        //$('#uploadButton').html('upload '+total+' files');
    }

    function setRowClasses()
    {
        // removed for now, might be useful in some form in the future
        //$('#files tr').removeClass('even');
        //$('#files tr').removeClass('odd');
        //$('#files tr:even').addClass('odd');
        //$('#files tr:odd').addClass('even');
    }

    function showAdditionalInformation(ele)
    {
        // block parent clicks from being processed on additional information
        $('.sliderContent table').unbind();
        $('.sliderContent table').click(function(e){
            e.stopPropagation();
        });
	
        // make sure we've clicked on a new element
        if(lastEle == ele)
        {
            // close any open sliders
            $('.sliderContent').slideUp('fast');
            // remove row highlighting
            $('.sliderContent').parent().parent().removeClass('rowSelected');
            lastEle = null;
            return false;
        }
        lastEle = ele;

        // close any open sliders
        $('.sliderContent').slideUp('fast');

        // remove row highlighting
        $('.sliderContent').parent().parent().removeClass('rowSelected');

        // select row and popup content
        $(ele).addClass('rowSelected');

        // set the position of the sliderContent div
        $(ele).find('.sliderContent').css('left', 0);
        $(ele).find('.sliderContent').css('top', ($(ele).offset().top + $(ele).height())-$('.file-upload-wrapper .modal-content').offset().top);
        $(ele).find('.sliderContent').slideDown(400, function() {
        });

        return false;
    }

    function saveFileToFolder(ele)
    {
        // get variables
        shortUrl = $(ele).closest('.sliderContent').children('.shortUrlHidden').val();
        folderId = $(ele).val();
    
        // send ajax request
        var request = $.ajax({
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_update_folder.ajax.php",
            type: "POST",
            data: {shortUrl: shortUrl, folderId: folderId},
            dataType: "html"
        });
    }

    function showAdditionalOptions(hide)
    {
        if(typeof(hide) == "undefined")
        {
            hide = false;
        }
        
        if(($('#additionalOptionsWrapper').is(":visible")) || (hide == true))
        {
            $('#additionalOptionsWrapper').slideUp();
        }
        else
        {
            $('#additionalOptionsWrapper').slideDown();
        }
    }
    
    function saveAdditionalOptions(hide)
    {
        if(typeof(hide) == "undefined")
        {
            hide = false;
        }
        
        // save values globally
        fileToEmail = $('#send_via_email').val();
        filePassword = $('#set_password').val();
        fileFolder = $('#upload_folder_id').val();
        
        // attempt ajax to save
        processAddtionalOptions();
        
        // hide
        showAdditionalOptions(hide);
    }

    function processAddtionalOptions()
    {
        // make sure the uploads have completed
        if(uploadComplete == false)
        {
            return false;
        }
        
        return sendAdditionalOptions();
    }
    
    function sendAdditionalOptions()
    {
        // make sure we have some urls
        if(fileDeleteHashes.length == 0)
        {
            return false;
        }
        
        $.ajax({
            type: "POST",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_update_file_options.ajax.php",
            data: { fileToEmail: fileToEmail, filePassword: filePassword, fileDeleteHashes: fileDeleteHashes, fileShortUrls: fileShortUrls, fileFolder: fileFolder }
        }).done(function( msg ) {
            fileToEmail = '';
            filePassword = '';
            fileFolder = '';
            fileDeleteHashes = [];
            fileShortUrls = [];
            if(typeof updateStatsViaAjax === "function")
            {
                updateStatsViaAjax();
            }
            if(typeof refreshFileListing === "function")
            {
                refreshFileListing();
            }
            
        });
    }
</script>

<?php
if ($showUploads == true)
{
?>
<script>
    function findUrls(text)
    {
        var source = (text || '').toString();
        var urlArray = [];
        var url;
        var matchArray;
		
		// standardise
		source = source.replace("\n\r", "\n");
		source = source.replace("\r", "\n");
		source = source.replace("\n\n", "\n");
		
		// split apart urls
		source = source.split("\n");

        // find urls
        var regexToken = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~()_|\s!:,.;'\[\]]*[-A-Z0-9+&@#\/%=~()_'|\s])/ig;
		
		// validate urls
		for(i in source)
		{
			url = source[i];
			if(url.match(regexToken))
			{
				urlArray.push(url);
			}
		}

        return urlArray;
    }
    
    var currentUrlItem = 0;
    var totalUrlItems = 0;
    function urlUploadFiles()
    {
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

        // create table listing
        html = '';
        for(i in urlList)
        {
            html += '<tr id="rowId'+i+'"><td class="cancel"><a href="#" onClick="return false;"><img src="<?php echo SITE_IMAGE_PATH; ?>/processing_small.gif" class="processingIcon" height="16" width="16" alt="<?php echo str_replace("\"", "\\\"", t('processing', 'processing')); ?>"/>';
            html += '</a></td><td class="name" colspan="3">'+urlList[i]+'&nbsp;&nbsp;<span class="progressWrapper"><span class="progressText"></span></span></td></tr>';
        }
        $('#urlUpload #urls').html(html);
                
        // show file uploader screen
        $('#urlUpload #urlFileListingWrapper').removeClass('hidden');
        $('#urlUpload #urlFileUploader').addClass('hidden');
        
        // loop over urls and try to retrieve the file, if running in the background do all these at once, otherwise step over each one
		tracker = currentUrlItem;
		<?php if(SITE_CONFIG_REMOTE_URL_DOWNLOAD_IN_BACKGROUND == 'yes'): ?>
		for(i in urlList)
        {
		<?php endif; ?>
			startRemoteUrlDownload(tracker);
		<?php if(SITE_CONFIG_REMOTE_URL_DOWNLOAD_IN_BACKGROUND == 'yes'): ?>
			tracker++;
		}
		<?php endif; ?>
    }

    function updateUrlProgress(data)
    {
        $.each(data, function (key, value) {
            switch (key)
            {
                case 'progress':
                    percentageDone = parseInt(value.loaded / value.total * 100, 10);
                    
                    textContent = '';
                    textContent += 'Progress: '+percentageDone+'%';
                    textContent += ' ';
                    textContent += '('+bytesToSize(value.loaded, 2)+' / '+bytesToSize(value.total, 2)+')';
        
                    progressText = textContent;
                    $('#rowId'+value.rowId+' .progressText').html(progressText);
                    break;
                case 'done':
                    handleUrlUploadSuccess(value);
                    if((currentUrlItem+1) < totalUrlItems)
                    {
                        currentUrlItem = currentUrlItem+1;
                        startRemoteUrlDownload(currentUrlItem);
                    }
                    break;
            }
        });
    }

    function startRemoteUrlDownload(index)
    {
        // show progress
        $('#urlUpload .urlFileListingWrapper .processing-button').removeClass('hidden');
        
        // get file list as array
        urlList = $('#urlList').val();
        urlList = findUrls(urlList);
        
        // create iframe to track progress
        var iframe = $('<iframe src="javascript:false;" style="display:none;"></iframe>');
        iframe
            .prop('src', '<?php echo crossSiteAction::appendUrl(file::getUploadUrl()."/core/page/ajax/url_upload_handler.ajax.php"); ?>&rowId='+index+'&url=' + encodeURIComponent(urlList[index]) + '&folderId='+fileFolder)
            .appendTo(document.body);
    }

    function handleUrlUploadSuccess(data)
    {
        isSuccess = true;
        if(data.error != null)
        {
            isSuccess = false;
        }

        html = '';
        html += '<tr class="template-download';
        if(isSuccess == false)
        {
            html += ' errorText';
        }
        html += '" onClick="return showAdditionalInformation(this);">'
        if(isSuccess == false)
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

        $('#rowId'+data.rowId).replaceWith(html);

        if(data.rowId == urlList.length-1)
        {
            // show footer
            $('#urlUpload .urlFileListingWrapper .processing-button').addClass('hidden');
            $('#urlUpload .fileSectionFooterText').removeClass('hidden');

            // set additional options
            sendAdditionalOptions();

            // setup copy link
            setupCopyAllLink();
        }
    }
</script>
<?php
}
?>

<?php if((SITE_CONFIG_REMOTE_URL_DOWNLOAD_IN_BACKGROUND == 'yes') && ($Auth->loggedIn())): ?>
<script>
	var gBackgroundUrlTableLoaded = false;
	var gBackgroundUrlDoneInitialLoad = false;
	$(document).ready(function() {
		loadBackgroundUrlDownloadTable();

		// refresh every 10 seconds
		window.setInterval(function() {
			if (gBackgroundUrlTableLoaded == false)
			{
				return true;
			}
			gBackgroundUrlTableLoaded = false;
			loadBackgroundUrlDownloadTable();
		}, 10000);
	});
	
	function loadBackgroundUrlDownloadTable()
	{
		// only do this when tab is visible
		if($('#urlUpload').is(':visible') == false)
		{
			if(gBackgroundUrlDoneInitialLoad == true)
			{
				return;
			}
		}
		$('#urlBackgroundDownloadExistingWrapper').load("<?php echo CORE_AJAX_WEB_ROOT; ?>/_existing_background_url_download.ajax.php", function() {
			$('#urlUpload #urlFileListingWrapper').addClass('hidden');
			$('#urlUpload #urlFileUploader').removeClass('hidden');
			setupBackgroundUrlDatatable();
			gBackgroundUrlTableLoaded = true;
			gBackgroundUrlDoneInitialLoad = true;
		});
	}

	function setupBackgroundUrlDatatable()
	{
		$('#existingBackgroundUrlDownloadTable').dataTable({
			"sPaginationType": "full_numbers",
			"bAutoWidth": false,
			"bProcessing": false,
			"iDisplayLength": 20,
			"bFilter": false,
			"bSort": true,
			"bDestroy": true,
			"bLengthChange": false,
			"bPaginate": false,
			"bInfo": false,
			"aoColumns": [
				{sClass: "alignCenter text-center"},
				{},
				{sClass: "alignCenter text-center"},
				{sClass: "alignCenter text-center"}
			],
			"oLanguage": {
				"oPaginate": {
					"sFirst": "<?php

	echo t('datatable_first', 'First');

?>",
					"sPrevious": "<?php

	echo t('datatable_previous', 'Previous');

?>",
					"sNext": "<?php

	echo t('datatable_next', 'Next');

?>",
					"sLast": "<?php

	echo t('datatable_last', 'Last');

?>"
				},
				"sEmptyTable": "<?php

	echo t('datatable_no_data_available_in_table', 'No data available in table');

?>",
				"sInfo": "<?php

	echo t('datatable_showing_x_to_x_of_total_entries',
		'Showing _START_ to _END_ of _TOTAL_ entries');

?>",
				"sInfoEmpty": "<?php

	echo t('datatable_no_data', 'No data');

?>",
				"sLengthMenu": "<?php

	echo t('datatable_show_menu_entries', 'Show _MENU_ entries');

?>",
				"sProcessing": "<?php

	echo t('datatable_loading_please_wait', 'Loading, please wait...');

?>",
				"sInfoFiltered": "<?php

	echo t('datatable_base_filtered', ' (filtered)');

?>",
				"sSearch": "<?php

	echo t('datatable_search_text', 'Search:');

?>",
				"sZeroRecords": "<?php

	echo t('datatable_no_matching_records_found', 'No matching records found');

?>"
			}
		});
	}
	
	function confirmRemoveBackgroundUrl(urlId)
	{
		if(confirm('<?php echo str_replace("'", "", t('are_you_sure_you_want_to_remove_the_remote_url_download', 'Are you sure you want to cancel this download?')); ?>'))
		{
			return removeBackgroundUrl(urlId);            
		}
		
		return false;
	}
	
	function removeBackgroundUrl(urlId)
	{
		$.ajax({
			type: "POST",
			url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_remove_background_url_download.ajax.php",
			data: { gRemoveUrlId: urlId },
			dataType: 'json',
			success: function(json) {
				if(json.error == true)
				{
					alert(json.msg);
				}
				else
				{
					loadBackgroundUrlDownloadTable();
				}
				
			},
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				alert('Error getting response from server. '+XMLHttpRequest.responseText);
			}
		});
	}
</script>
<?php endif; ?>
