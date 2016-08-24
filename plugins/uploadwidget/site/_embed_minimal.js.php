<?php
$fid = null;
if(isset($_REQUEST['fid']))
{
    $fid = (int)$_REQUEST['fid'];
}
?>
<script>
	function browserXHR2Support()
	{
		if (new XMLHttpRequest().upload)
		{
			return true;
		}

		return false;
	}
	
	function bytesToSize(bytes, precision)
	{
		var kilobyte = 1024;
		var megabyte = kilobyte * 1024;
		var gigabyte = megabyte * 1024;
		var terabyte = gigabyte * 1024;

		if ((bytes >= 0) && (bytes < kilobyte)) {
			return bytes + ' B';

		} else if ((bytes >= kilobyte) && (bytes < megabyte)) {
			return (bytes / kilobyte).toFixed(precision) + ' KB';

		} else if ((bytes >= megabyte) && (bytes < gigabyte)) {
			return (bytes / megabyte).toFixed(precision) + ' MB';

		} else if ((bytes >= gigabyte) && (bytes < terabyte)) {
			return (bytes / gigabyte).toFixed(precision) + ' GB';

		} else if (bytes >= terabyte) {
			return (bytes / terabyte).toFixed(precision) + ' TB';

		} else {
			return bytes + ' B';
		}
	}

	function humanReadableTime(seconds)
	{
		var numhours = Math.floor(((seconds % 31536000) % 86400) / 3600);
		var numminutes = Math.floor((((seconds % 31536000) % 86400) % 3600) / 60);
		var numseconds = Math.floor((((seconds % 31536000) % 86400) % 3600) % 60);

		rs = '';
		if (numhours > 0)
		{
			rs += numhours + " hour";
			if (numhours != 1)
			{
				rs += "s";
			}
			rs += " ";
		}
		if (numminutes > 0)
		{
			rs += numminutes + " minute";
			if (numminutes != 1)
			{
				rs += "s";
			}
			rs += " ";
		}
		rs += numseconds + " second";
		if (numseconds != 1)
		{
			rs += "s";
		}

		return rs;
	}

    var fileUrls = [];
    var fileDeleteHashes = [];
    var fileShortUrls = [];
    var lastEle = null;
    var startTime = null;
    var fileToEmail = '';
    var filePassword = '';
    var fileFolder = '';
    var uploadComplete = false;
    $(document).ready(function() {
        'use strict';
<?php
if ($showUploads == true)
{
    // figure out max files
    $maxFiles = SITE_CONFIG_FREE_USER_MAX_CONCURRENT_UPLOADS;
    if($Auth->loggedIn())
    {
        if(($Auth->level == 'paid user') || ($Auth->level == 'admin'))
        {
            $maxFiles = SITE_CONFIG_PREMIUM_USER_MAX_CONCURRENT_UPLOADS;
        }
    }
    else
    {
        $maxFiles = SITE_CONFIG_NON_USER_MAX_CONCURRENT_UPLOADS;
    }
    
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
                maxChunkSize = <?php echo (coreFunctions::getPHPMaxUpload()>5000000?5000000:coreFunctions::getPHPMaxUpload()-5000); // in bytes, allow for smaller PHP upload limits ?>;
                var uploaderMaxSize = <?php echo (int)$maxUploadSize; ?>;
            }
            <?php endif; ?>
            
            // Initialize the jQuery File Upload widget:
            $('#fileUpload #fileupload').fileupload({
                sequentialUploads: true,
                url: '<?php echo file::getUploadUrl(); ?>/core/page/ajax/file_upload_handler.ajax.php?r=<?php echo htmlspecialchars(_CONFIG_SITE_HOST_URL); ?>&p=<?php echo htmlspecialchars(_CONFIG_SITE_PROTOCOL); ?>',
                maxFileSize: uploaderMaxSize,
				replaceFileInput: false,
                formData: {_sessionid: '<?php echo session_id(); ?>', cTracker: '<?php echo MD5(microtime()); ?>', maxChunkSize: maxChunkSize},
                xhrFields: {
                    withCredentials: true
                },
                maxChunkSize: maxChunkSize,
    <?php echo COUNT($acceptedFileTypes) ? ('acceptFileTypes: /(\\.|\\/)(' . str_replace(".", "", implode("|", $acceptedFileTypes) . ')$/i,')) : ''; ?> maxNumberOfFiles: <?php echo (int)$maxFiles; ?>
                    })
                    .on('fileuploadadd', function(e, data) {            
                        $('#fileUpload #fileupload #fileListingWrapper').removeClass('hidden');
                        $('#fileUpload #fileupload #initialUploadSection').addClass('hidden');
                        $('#fileUpload #fileUploadBadge').addClass('hidden');
            
                        // fix for safari
                        getTotalRows();
                        // end safari fix
                    })
                    .on('fileuploadstart', function(e, data) {
                        // set all cancel icons to processing
                        $('#fileUpload .cancel').html('<img class="processingIcon" src="<?php echo SITE_IMAGE_PATH; ?>/processing_small.gif" width="16" height="16"/>');
            
                        // set timer
                        startTime = (new Date()).getTime();
                    })
                    .on('fileuploadstop', function(e, data) {
                        // finished uploading
                        $('#fileUpload #processQueueSection').addClass('hidden');
                        $('#fileUpload #processingQueueSection').addClass('hidden');
                        $('#fileUpload #completedSection').removeClass('hidden');

                        // set all remainging pending icons to failed
                        $('#fileUpload .processingIcon').parent().html('<img src="<?php echo SITE_IMAGE_PATH; ?>/red_error_small.png" width="16" height="16"/>');

                        uploadComplete = true;

                        // setup copy link
                        setupCopyAllLink();
                    })
                    .on('fileuploadprogressall', function(e, data) {

                    })
                    .on('fileuploaddone', function(e, data) {
                        // keep a copy of the urls globally
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
                        // update screen with success content
                        $(data['context']).find('.name')
                            .html('ERROR: There was a server problem when attempting the upload, please try again later.');
                    });

                    // Open download dialogs via iframes,
                    // to prevent aborting current uploads:
                    $('#fileUpload #fileupload #files a:not([target^=_blank])').on('click', function (e) {
                        e.preventDefault();
                        $('<iframe style="display:none;"></iframe>')
                        .prop('src', this.href)
                        .appendTo('body');
                    });
    <?php
}
?>
    });
    
    $(function() {
        $("#tabs").tabs();
	$("#tabs").css("display", "block");
        $("#tabs").mouseover(function() {
            $("#tabs").addClass("tabsHover");
        });
        
        $("#tabs").mouseout(function() {
            $("#tabs").removeClass("tabsHover");
        });
    });

    function setupCopyAllLink()
    {
        // update text
        $('.copyAllLink').attr('data-clipboard-text', getUrlsAsText());
        
        $('.copyAllLink').each(function() {
            // setup copy to clipboard
            var clip = new ZeroClipboard( this, {
                moviePath: "<?php echo PLUGIN_ASSET_PATH; ?>js/zeroClipboard/ZeroClipboard.swf",
                text: getUrlsAsText()
              } );

            clip.on( 'complete', function(client, args) {
                alert("<?php echo t('links_copies_to_clipboard', 'Links copied to clipboard:\n\n'); ?>" + args.text );
            } );
        });
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

    function getTotalRows()
    {
        totalRows = $('#files .template-upload').length;
        if(typeof(totalRows) == "undefined")
        {
            return 0;
        }

        return totalRows;
    }
</script>
