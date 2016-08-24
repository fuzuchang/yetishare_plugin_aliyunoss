<script type="text/javascript">
    var selectedItems = [];
    var cur = -1, prv = -1;
    var pageStart = 0;
    var perPage = <?php echo $defaultPerPage; ?>;
    var fileId = 0;
    var intialLoad = true;
    var uploaderShown = false;
    var fromFilterModal = false;
    var doubleClickTimeout = null;
	var triggerTreeviewLoad = true;
	var filePopupSelectedTab = 0;
    $(function() {
        // initial button state
        updateFileActionButtons();

        // load folder listing
        $("#folderTreeview").jstree({
            "plugins": [
                "themes", "json_data", "ui", "types", "crrm", "contextmenu", "cookies"
            ],
            "themes": {
                "theme": "default",
                "dots": false,
                "icons": true
            },
            "core": {"animation": 150},
            "json_data": {
                "data": [
                    {
                        "data": "<?php echo t('your_uploads', 'Your Uploads'); ?>",
                        "state": "closed",
                        "attr": {"id": "-1", "rel": "home", "original-text": "<?php echo str_replace("\"", "'", t('your_uploads', 'Your Uploads')); ?>"}
                    },
                    {
                        "data": "<?php echo t('recent_uploads', 'Recent Uploads'); ?>",
                        "attr": {"id": "recent", "rel": "recent", "original-text": "<?php echo str_replace("\"", "'", t('recent_uploads', 'Recent Uploads')); ?>"}
                    },
                    {
                        "data": "<?php echo t('all_files', 'All Files'); ?><?php echo ($totalActive > 0) ? (' (' . $totalActive . ')') : ''; ?>",
                        "attr": {"id": "all", "rel": "all", "original-text": "<?php echo str_replace("\"", "'", t('all_files', 'All Files')); ?>"}
                    },
                    {
                        "data": "<?php echo t('trash_can', 'Trash Can'); ?><?php echo (isset($totalTrash) && ($totalTrash > 0)) ? (' (' . $totalTrash . ')') : ''; ?>",
                        "attr": {"id": "trash", "rel": "bin", "original-text": "<?php echo str_replace("\"", "'", t('trash_can', 'Trash Can')); ?>"}
                    }
                ],
                "ajax": {
                    "url": function(node) {
                        var nodeId = "";
                        var url = ""
                        if (node == -1)
                        {
                            url = "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_folder_listing.ajax.php";
                        }
                        else
                        {
                            nodeId = node.attr('id');
                            url = "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_folder_listing.ajax.php?folder=" + nodeId;
                        }

                        return url;
                    }
                }
            },
            "contextmenu": {
                "items": buildTreeViewContextMenu
            },
            'progressive_render': true
        }).bind("dblclick.jstree", function(event, data) {
            if (doubleClickTimeout != null)
            {
                clearTimeout(doubleClickTimeout);
                doubleClickTimeout = null;
            }
            var node = $(event.target).closest("li");
            if ($(node).hasClass('jstree-leaf') == true)
            {
                return false;
            }

            //$("#folderTreeview").jstree("toggle_node", node.data("jstree"));
        }).bind("select_node.jstree", function(event, data) {
			// use this to stop the treeview from triggering a reload of the file manager
			if(triggerTreeviewLoad == false)
			{
				triggerTreeviewLoad = true;
				return false;
			}
            // add a slight delay encase this is a double click
            if (intialLoad == false)
            {
                clickTreeviewNode(event, data);

                return false;
            }

            clickTreeviewNode(event, data);
        }).bind("load_node.jstree", function(event, data) {
            // assign click to icon
            assignNodeExpandClick();
        }).bind("open_node.jstree", function(event, data) {
            // reassign drag crop for sub-folder
            setupTreeviewDropTarget();
        }).delegate("a", "click", function(event, data) {
            event.preventDefault();
        }).bind('loaded.jstree', function(e, data) {
            // reload stats
            updateStatsViaAjax();
        });

        var doIntial = inFileManager();
        if (typeof($.cookie("jstree_select")) != "undefined")
        {
            if ($.cookie("jstree_select").length > 0)
            {
                doIntial = false;
            }
        }
        if (doIntial == true)
        {
            // load file listing
            $('#nodeId').val('-1');
            loadFiles();
        }

        resetStartPoint();

        if (inFileManager() == true)
        {
            $('.file-manager-container').bind('drop', function (e) {
				// blocks upload popup on internal moves / folder icons
				if($(e.target).hasClass('folderIconLi') == false)
				{
					uploadFiles();
				}
            });
            
            $("#fileManager").click(function(event) {
                if (ctrlPressed == false)
                {
                    if ($(event.target).is('ul') || $(event.target).hasClass('fileManager')) {
                        clearSelected();
                    }
                }
            });

            setupFileDragSelect();

<?php if (SITE_CONFIG_FILE_MANAGER_DEFAULT_VIEW == 'list'): ?>
                toggleViewType();
<?php endif; ?>

            // setup key shortcuts
            $(window).keyup(function(e) {
                // escape, hide any context menus
                if (e.keyCode == 27) {
                    hideOpenContextMenus();
                }
                // delete key
                if (e.keyCode == 46) {
                    deleteFiles();
                }

                // navigate files
                if (e.keyCode == 37)
                {
                    selectPreviousFile();
                    return false;
                }
                else if (e.keyCode == 39)
                {
                    selectNextFile();
                    return false;
                }
            });

            // make sure the user wants to exit is they are uploading
            $(window).bind('beforeunload', function() {
                if (uploadComplete == false)
                {
                    return 'You still have 1 or more uploads in progress, are you sure you want to exit?';
                }
            });
        }
    });

    function assignNodeExpandClick()
    {
        $('.jstree-icon').off('click');
        $('.jstree-icon').on('click', function(event) {
            var node = $(event.target).parent().parent();
            if ($(node).hasClass('jstree-leaf') != true)
            {
                // expand
                $("#folderTreeview").jstree("toggle_node", $(node));

                // stop the node from being selected
                event.stopPropagation();
                event.preventDefault();
            }
        });
    }

    function clickTreeviewNode(event, data)
    {
        if (doubleClickTimeout != null)
        {
            clearTimeout(doubleClickTimeout);
            doubleClickTimeout = null;
        }

        clearSelected();
        clearSearchFilters(false);
        if (inFileManager() == true)
        {
            // load via ajax
            if (intialLoad == true)
            {
                intialLoad = false;
            }
            $('#nodeId').val(data.rslt.obj.attr("id"));
            refreshFolderBreadcrumbs();
            $('#folderIdDropdown').val($('#nodeId').val());
            if (typeof(setUploadFolderId) === 'function')
            {
                setUploadFolderId($('#nodeId').val());
            }
            refreshFileListing();
        }
        else
        {
            // block initial load so we don't get random redirects
            if (intialLoad == true)
            {
                intialLoad = false;
            }
            else
            {
                // do full page load as not currently in file manager
                window.location = "<?php echo WEB_ROOT; ?>/account_home.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>?folderId=" + data.rslt.obj.attr("id");
            }
        }
    }
	
	function refreshFileListing()
	{
		resetStartPoint();
		loadFiles();
		updateFolderDropdownMenuItems();
		assignNodeExpandClick();
	}

    function updateFolderDropdownMenuItems()
    {
        // not a sub folder
        if (isPositiveInteger($('#nodeId').val()) == false)
        {
            $('#subFolderOptions').hide();
            $('#topFolderOptions').show();
        }
        // all sub folders / menu options
        else
        {
            $('#topFolderOptions').hide();
            $('#subFolderOptions').show();
        }
    }

    function setupFileDragSelect()
    {
        if (isDesktopUser() == true)
        {
            $('.file-manager-container')
                    .drag("start", function(ev, dd) {
                //unbindLiOnClick();
                return $('<div class="fileManagerDraggleSelection" />')
                        .css('opacity', .50)
                        .appendTo(document.body);
            })
                    .drag(function(ev, dd) {
                $(dd.proxy).css({
                    top: Math.min(ev.pageY, dd.startY),
                    left: Math.min(ev.pageX, dd.startX),
                    height: Math.abs(ev.pageY - dd.startY),
                    width: Math.abs(ev.pageX - dd.startX)
                });
            })
                    .drag("end", function(ev, dd) {
                //assignLiOnClick();
                $(dd.proxy).remove();
            }, {distance: 10, not: $('li, span, a, img')});

            $('.fileIconLi:not(.fileDeletedLi)').draggable({
                revert: function(event, ui) {
                    return !event;
                },
                containment: 'body',
                helper: function(event) {
                    selectFile($(this).attr('fileId'), true);
                    var ret = $(this).clone();
                    ret.find('.filename').html('<?php echo t('file_manager_moving', 'Moving'); ?> ' + countSelected() + ' <?php echo t('file_manager_moving_files', 'file(s)'); ?>');
                    ret.find('.fileUploadDate').remove();
                    ret.find('.filesize').remove();
                    ret.find('.fileOptions').remove();
                    ret.find('.downloads').remove();
                    return ret;
                },
                opacity: 0.50,
                cursorAt: {left: 5, top: 5},
                distance: 10,
                start: function(event, ui)
                {
                    selectFile($(this).attr('fileId'), true);
                },
                stop: function(event, ui)
                {
                    // clear selected if only 1
                    if (countSelected() == 1)
                    {
                        elementId = 'fileItem' + $(this).attr('fileId');
                        $('.' + elementId).removeClass('selected');
                        delete selectedItems['k' + $(this).attr('fileId')];
                    }
                }
            });

            setupTreeviewDropTarget();
        }
    }
    
    function setupTreeviewDropTarget()
    {
        $(".jstree-no-dots li a").droppable({
            hoverClass: 'jstree-hovered',
            tolerance: "pointer",
            drop: function(event, ui) {
                folderId = $(this).parent().attr('id');
                moveFiles(folderId);
            }
        });
		
		$(".fileManagerWrapper .fileListing .folderIconLi").droppable({
            hoverClass: 'jstree-hovered',
            tolerance: "pointer",
            drop: function(event, ui) {
                folderId = $(this).attr('folderid');
                moveFiles(folderId);
            }
        });
    }

    function moveFiles(newFolderId)
    {
        if ((newFolderId == 'recent') || (newFolderId == 'all'))
        {
            return true;
        }

        if (newFolderId == 'trash')
        {
            deleteFiles();
            return true;
        }

        moveFilesIntoFolder(newFolderId);

        return true;
    }

    function moveFilesIntoFolder(newFolderId)
    {
        fileIds = [];
        for (i in selectedItems)
        {
            fileIds.push(i.replace('k', ''));
        }

        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_move_file_in_folder.ajax.php",
            data: {folderId: newFolderId, fileIds: fileIds},
            success: function(data) {
                if (data.error == true)
                {
                    alert(data.msg);
                }
                else
                {
                    // refresh treeview
                    refreshFolderListing(false);
					refreshFileListing();

                    // clear selected
                    clearSelected();

                    // reload stats
                    updateStatsViaAjax();
                }
            }
        });
    }

    function reloadDragItems()
    {
        $('.fileIconLi')
                .drop("start", function() {
            $(this).removeClass("active");
            if ($(this).hasClass("selected") == false)
            {
                $(this).addClass("active");
            }
        })
                .drop(function(ev, dd) {
            selectFile($(this).attr('fileId'), true);
        })
                .drop("end", function() {
            $(this).removeClass("active");
        });
        $.drop({multi: true});
    }
	
	function setLastLoadedFolderCookie(folderId)
	{
		$.cookie("jstree_select", "#"+folderId);
	}

    function refreshFolderListing(triggerLoad)
    {
		if(typeof(triggerLoad) != "undefined")
		{
			triggerTreeviewLoad = triggerLoad;
		}
		
        $("#folderTreeview").jstree("refresh");
    }

    function resetStartPoint()
    {
        pageStart = 0;
    }

    function setPerPage()
    {
        perPage = parseInt($('#perPageElement').val());
        doFilter();
    }

    function buildTreeViewContextMenu(node)
    {
        var items = {};
        if (inFileManager() == false)
        {
            return null;
        }

        if ($(node).attr('id') == 'trash')
        {
            var items = {
                "Empty": {
                    "label": "<?php echo t('empty_trash', 'Empty Trash'); ?>",
					"icon": "glyphicon glyphicon-trash",
                    "action": function(obj) {
                        confirmEmptyTrash();
                    }
                }
            };
        }
        else if ($(node).attr('id') == '-1')
        {
            var items = {
                "Upload": {
                    "label": "<?php echo t('upload_files', 'Upload Files'); ?>",
                    "separator_after": true,
					"icon": "glyphicon glyphicon-cloud-upload",
                    "action": function(obj) {
                        uploadFiles('');
                    }
                },
                "Add": {
                    "label": "<?php echo t('add_folder', 'Add Folder'); ?>",
					"icon": "glyphicon glyphicon-plus",
                    "action": function(obj) {
                        showAddFolderForm(obj.attr("id"));
                    }
                }
            };
        }
        else if ($.isNumeric($(node).attr('id')))
        {
            var items = {
                "Upload": {
                    "label": "<?php echo t('upload_files', 'Upload Files'); ?>",
					"icon": "glyphicon glyphicon-cloud-upload",
                    "separator_after": true,
                    "action": function(obj) {
                        uploadFiles(obj.attr("id"));
                    }
                },
				"Add": {
                    "label": "<?php echo t('add_sub_folder', 'Add Sub Folder'); ?>",
					"icon": "glyphicon glyphicon-plus",
                    "action": function (obj) {
                        showAddFolderForm(obj.attr("id"));
                    }
                },
                "Edit": {
                    "label": "<?php echo t('edit_folder', 'Edit'); ?>",
					"icon": "glyphicon glyphicon-pencil",
                    "action": function(obj) {
                        showAddFolderForm(null, obj.attr("id"));
                    }
                },
                "Delete": {
                    "label": "<?php echo t('delete_folder', 'Delete'); ?>",
					"icon": "glyphicon glyphicon-remove",
                    "action": function(obj) {
                        confirmRemoveFolder(obj.attr("id"));
                    }
                },
                "Download": {
                    "label": "<?php echo t('download_all_files', 'Download All Files (Zip)'); ?>",
					"icon": "glyphicon glyphicon-floppy-save",
                    "separator_before": true,
                    "action": function(obj) {
                        downloadAllFilesFromFolder(obj.attr("id"));
                    }
                },
                "Share": {
                    "label": "<?php echo t('share_folder', 'Share Folder'); ?>",
					"icon": "glyphicon glyphicon-share",
                    "separator_before": true,
                    "action": function(obj) {
                        showFolderSharingForm(obj.attr("id"));
                    }
                }
            };
        }

        return items;
    }

    function uploadFiles(folderId)
    {
        if (typeof(folderId) != 'undefined')
        {
            $('#upload_folder_id').val(folderId);
        }

        showUploaderPopup();
    }

    function isPositiveInteger(str)
    {
        var n = ~~Number(str);
        return n > 0;
    }

    function confirmRemoveFolder(folderId)
    {
        // only allow actual sub folders
        if (isPositiveInteger(folderId) == false)
        {
            return false;
        }
        <?php if(corefunctions::getUsersAccountLockStatus($Auth->id) == 1): ?>
        if (alert('<?php echo str_replace('\'', '', t('account_locked_folder_delete_error_message', 'This account has been locked, please unlock the account to regain full functionality.')); ?>'))
        {
            return false;
        }
        <?php elseif(corefunctions::getUsersAccountLockStatus($Auth->id) == 0): ?>
        if (confirm('<?php echo str_replace('\'', '', t('are_you_sure_you_want_to_remove_this_folder', 'Are you sure you want to remove this folder? Any files within the folder will be moved into your default root folder and remain active.')); ?>'))
        {
            removeFolder(folderId);
        }
        <?php endif; ?>
        return false;
    }

    function removeFolder(folderId)
    {
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_delete_folder.ajax.php",
            data: {folderId: folderId},
            success: function(data) {
                if (data.error == true)
                {
                    showErrorNotification('Error', data.msg);
                }
                else
                {
                    // refresh treeview
                    showSuccessNotification('Success', data.msg);
                    refreshFolderListing(false);
					refreshFileListing();
                }
            }
        });
    }

    function confirmEmptyTrash()
    {
        if (confirm('<?php echo str_replace('\'', '', t('are_you_sure_you_want_to_empty_the_trash', 'Are you sure you want to empty the trash can? Any statistics and other file information will be permanently deleted.')); ?>'))
        {
            emptyTrash();
        }

        return false;
    }

    function emptyTrash()
    {
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_empty_trash.ajax.php",
            success: function(data) {
                if (data.error == true)
                {
                    alert(data.msg);
                }
                else
                {
                    // reload file listing
                    loadFiles();

                    // reload stats
                    updateStatsViaAjax();
                }
            }
        });
    }

    var hideLoader = false;
    function loadFiles()
    {
        hideLoader = false;
        setLoaderImage();

        // get variables
        folderId = $('#nodeId').val();
        filterText = $('#filterText').val();
        filterUploadedDateRange = $('#filterUploadedDateRange').val();
        filterOrderBy = $('#filterOrderBy').val();
		
		if(folderId == -1)
		{
			$("#folderTreeview").jstree("open_node", $('#-1'));
		}

        // reload file listing
        $('#fileManager').load("<?php echo WEB_ROOT; ?>/ajax/_account_home_v2_file_listing.ajax.php", {nodeId: folderId, filterText: filterText, filterUploadedDateRange: filterUploadedDateRange, filterOrderBy: filterOrderBy, pageStart: pageStart, perPage: perPage}, function(data) {
				// if there is zero results on the current page yet we are on a subpage, resend request from page zero
				if(pageStart > 0)
				{
					rspCurrentStart = parseInt($('#rspCurrentStart').val());
					rspTotalResults = parseInt($('#rspTotalResults').val());
					if(rspCurrentStart >= rspTotalResults)
					{
						resetStartPoint();
						loadFiles();
						
						return;
					}
				}
				// ensure any selected icons are reselected
				hideLoader = true;
				setFolderStatusText();
				highlightSelected();
				updatePaging();
				updateActiveFilters();
				setupFileDragSelect();
				reloadDragItems();
				assignLiOnClick();
				pageTitle = $('#rspPageTitle').val();
				setPageTitle(pageTitle);

	<?php if (isset($_REQUEST['upload'])): ?>
					if (inFileManager() == true)
					{
						if (uploaderShown == false)
						{
							uploaderShown = true;
							showUploaderPopup();
						}
					}
	<?php endif; ?>

				window.history.pushState({"html":$('#fileManagerContainer').html(), "pageTitle":pageTitle}, "", "account_home.html");
        });
    }
	
	// for browser history
	window.onpopstate = function(e){
		if(e.state)
		{
			if((typeof(e.state.html) != 'undefined') && (e.state.html != null))
			{
				$('#fileManagerContainer').html(e.state.html);
				setPageTitle(e.state.pageTitle);
				pageStart = $('#rspCurrentStart').val();
				setFolderStatusText();
				highlightSelected();
				updatePaging();
				updateActiveFilters();
				setupFileDragSelect();
				reloadDragItems();
				assignLiOnClick();
			}
		}
	};
	
	function setPageTitle(pageTitle)
	{
		document.title = pageTitle + " - <?php echo validation::safeOutputToScreen(str_replace(array("\""), "'", SITE_CONFIG_SITE_NAME)); ?>";
	}

    function updateActiveFilters()
    {
        if ($('#nodeId').val() == 'recent')
        {
            $('#filterOrderBy').prop('disabled', 'disabled');
        }
        else
        {
            $('#filterOrderBy').prop('disabled', false);
        }
    }

    function setFolderStatusText()
    {
        totalFiles = $('#rspFolderTotalFiles').val();
        totalFileSize = $('#rspFolderTotalSize').val();

        statusText = totalFiles + ' <?php echo t('file', 'file'); ?>';
        if (totalFiles != 1)
        {
            statusText = totalFiles + ' <?php echo t('files', 'files'); ?>';
        }
        if (totalFileSize > 0)
        {
            statusText += '&nbsp;&nbsp;(' + bytesToSize(totalFileSize, 2) + ')';
        }

        $('#totalFilesText').html(statusText);
    }

    function setLoaderImage()
    {
        // introduce delay to only show on slower connections, restricts flickering
        setTimeout(function() {
            if (hideLoader == false)
            {
                $('#fileManager').html('<div class="fileManagerLoading"><img src="<?php echo SITE_IMAGE_PATH; ?>/file_browser/throbber_large.gif" width="64" height="64"/></div>');
            }
        }, 500);
    }

    function dblClickFile(fileId)
    {

    }

    function assignLiOnClick()
    {
        unbindLiOnClick();
        $(".fileManager .fileIconLi a.fileDownload").click(function(e) {
            e.stopPropagation();
			liElement = $(this).parents('.fileIconLi');
			return showFileMenu(liElement, e);
        });
		$(".fileManager .folderIconLi a.fileDownload").click(function(e) {
            e.stopPropagation();
			liElement = $(this).parents('.folderIconLi');
			return showFolderMenu(liElement, e);
        });
        $(".fileManager .fileIconLi").click(function(e) {
            e.stopPropagation();
            fileId = $(this).attr('fileId');
            selectFile(fileId);
        });
        assignLiRightClick();
    }

    function unbindLiOnClick()
    {
        $(".fileManager .fileIconLi").unbind('click');
		$(".fileManager .folderIconLi").unbind('click');
        unbindLiRightClick();
    }
	
	function clearExistingHoverFileItem()
	{
		$('.hoverItem').removeClass('hoverItem');
	}
	
	function hoverFileItem(ele)
	{
		clearExistingHoverFileItem();
		$(ele).addClass('hoverItem');
	}
	
	function showFileMenu(liEle, clickEvent)
	{
		clickEvent.stopPropagation();
		hideOpenContextMenus();
		hoverFileItem(liEle);
		fileId = $(liEle).attr('fileId');
        downloadUrl = $(liEle).attr('dtfullurl');
        statsUrl = $(liEle).attr('dtstatsurl');
        isDeleted = $(liEle).hasClass('fileDeletedLi');
        fileName = $(liEle).attr('dtfilename');
        extraMenuItems = $(liEle).attr('dtextramenuitems');
		var items = {
			"Stats": {
				"label": "<?php echo UCWords(t('account_file_details_stats', 'Stats')); ?>",
				"icon": "glyphicon glyphicon-stats",
				"action": function(obj) {
					showStatsPopup(fileId);
				}
			}
		};

		if (isDeleted == false)
		{
			var items = {};

			// replace any items for overwriting (plugins)
			if (extraMenuItems.length > 0)
			{
				items = JSON.parse(extraMenuItems);
				for (i in items)
				{
					// setup click action on menu item
					eval("items['" + i + "']['action'] = " + items[i]['action']);
				}
			}

			// default menu items
			items["Download"] = {
				"label": "<?php echo UCWords(t('account_file_details_download', 'Download')); ?> " + fileName,
				"icon": "glyphicon glyphicon-download-alt",
				"separator_after": true,
				"action": function(obj) {
					openUrl('<?php echo CORE_PAGE_WEB_ROOT; ?>/account_home_v2_direct_download.php?fileId=' + fileId);
				}
			};

			items["Edit"] = {
				"label": "<?php echo UCWords(t('account_file_details_edit_file', 'Edit File')); ?>",
				"icon": "glyphicon glyphicon-pencil",
				"action": function(obj) {
					showEditFileForm(fileId);
				}
			};
			
			items["Duplicate"] = {
				"label": "<?php echo UCWords(t('account_file_details_create_copy', 'Create Copy')); ?>",
				"icon": "glyphicon glyphicon-plus-sign",
				"action": function (obj) {
					selectFile(fileId, true);
					duplicateFiles();
				}
			};

			items["Delete"] = {
				"label": "<?php echo UCWords(t('account_file_details_delete', 'Delete')); ?>",
				"icon": "glyphicon glyphicon-remove",
				"separator_after": true,
				"action": function(obj) {
					selectFile(fileId, true);
					deleteFiles();
				}
			};
			
			items["Copy"] = {
				"label": "<?php echo t('copy_url_to_clipboard', 'Copy Url to Clipboard'); ?>",
				"icon": "glyphicon glyphicon-link",
				"classname": "fileMenuItem"+fileId,
				"separator_after": true,
				"action": function (obj) {
					selectFile(fileId, true);
					fileUrlText = '';
					for (i in selectedItems)
					{
						fileUrlText += selectedItems[i][3] + "<br/>";
					}
					$('#clipboard-placeholder').html(fileUrlText);
					copyToClipboard('.fileMenuItem'+fileId);
				}
			};
			
			items["Select"] = {
				"label": "<?php echo UCWords(t('account_file_details_select_file', 'Select File')); ?> ",
				"icon": "glyphicon glyphicon-check",
				"action": function (obj) {
					selectFile(fileId, true);
				}
			};

			items["Links"] = {
				"label": "<?php echo UCWords(t('file_manager_links', 'Links')); ?>",
				"icon": "glyphicon glyphicon-link",
				"action": function(obj) {
					selectFile(fileId, true);
					viewFileLinks();
					// clear selected if only 1
					if (countSelected() == 1)
					{
						clearSelected();
					}
				}
			};

			items["Stats"] = {
				"label": "<?php echo UCWords(t('account_file_details_stats', 'Stats')); ?>",
				"icon": "glyphicon glyphicon-stats",
				"action": function(obj) {
					showStatsPopup(fileId);
				}
			};

			// replace any items for overwriting
			for (i in extraMenuItems)
			{
				if (typeof(items[i]) != 'undefined')
				{
					items[i] = extraMenuItems[i];
				}
			}
		}
		$.vakata.context.show(items, $(liEle), clickEvent.pageX, clickEvent.pageY, liEle);
		return false;
    }
	
	function showFolderMenu(liEle, clickEvent)
    {
        clickEvent.stopPropagation();
        folderId = $(liEle).attr('folderId');
		folderName = $(liEle).attr('dtfoldername');
		var items = {
                "Upload": {
                    "label": "<?php echo t('upload_to', 'Upload to'); ?> " + folderName,
					"icon": "glyphicon glyphicon-cloud-upload",
                    "separator_after": true,
                    "action": function (obj) {
                        uploadFiles(folderId);
                    }
                },
				"Add": {
                    "label": "<?php echo t('add_sub_folder', 'Add Sub Folder'); ?>",
					"icon": "glyphicon glyphicon-plus",
                    "action": function (obj) {
                        showAddFolderForm(folderId);
                    }
                },
                "Edit": {
                    "label": "<?php echo t('edit_folder', 'Edit'); ?>",
					"icon": "glyphicon glyphicon-pencil",
                    "action": function (obj) {
                        showAddFolderForm(null, folderId);
                    }
                },
                "Delete": {
                    "label": "<?php echo t('delete_folder', 'Delete'); ?>",
					"icon": "glyphicon glyphicon-remove",
                    "action": function (obj) {
                        confirmRemoveFolder(folderId);
                    }
                },
                "Download": {
                    "label": "<?php echo t('download_all_files', 'Download All Files (Zip)'); ?>",
					"icon": "glyphicon glyphicon-floppy-save",
                    "separator_before": true,
                    "action": function (obj) {
                        downloadAllFilesFromFolder(folderId);
                    }
                },
				"Copy": {
                    "label": "<?php echo t('copy_url_to_clipboard', 'Copy Url to Clipboard'); ?>",
					"icon": "glyphicon glyphicon-link",
					"classname": "folderMenuItem"+folderId,
                    "separator_before": true,
                    "action": function (obj) {
						$('#clipboard-placeholder').html($('#folderItem'+folderId).attr('sharing-url'));
						copyToClipboard('.folderMenuItem'+folderId);
                    }
                },
                "Share": {
                    "label": "<?php echo t('share_folder', 'Share Folder'); ?>",
					"icon": "glyphicon glyphicon-share",
                    "action": function (obj) {
						showFolderSharingForm(folderId);
                    }
                }
            };

        $.vakata.context.show(items, $(liEle), clickEvent.pageX - 15, clickEvent.pageY - 8, liEle);
        return false;
    }

    function assignLiRightClick()
    {
		$(".fileManager .fileIconLi").bind('contextmenu', function(e) {
			return showFileMenu(this, e);
		});
		
		$(".fileManager .folderIconLi").bind('contextmenu', function(e) {
			return showFolderMenu(this, e);
		});

        $(".file-manager-container").bind('contextmenu', function(e) {	
            e.stopPropagation();
			hideOpenContextMenus();
            var items = {
				"Upload": {
					"label": "<?php echo UCWords(t('upload_files', 'Upload Files')); ?>",
					"icon": "glyphicon glyphicon-cloud-upload",
					"separator_after": true,
					"action": function (obj) {
						uploadFiles();
					}
				},
				"Add": {
					"label": "<?php echo UCWords(t('add_sub_folder', 'Add Sub Folder')); ?>",
					"icon": "glyphicon glyphicon-plus",
					"separator_after": true,
					"action": function (obj) {
						showAddFolderForm();
					}
				},
                "SelectAll": {
                    "label": "<?php echo UCWords(t('account_file_details_select_all_files', 'Select All Files')); ?>",
					"icon": "glyphicon glyphicon-check",
                    "action": function(obj) {
                        selectAllFiles();
                    }
                },
				"ClearAll": {
					"label": "<?php echo UCWords(t('account_file_details_clear_selected', 'Clear Selected')); ?>",
					"icon": "glyphicon glyphicon-unchecked",
					"action": function(obj) {
						clearSelected();
					}
				}
            };
            $.vakata.context.show(items, $(this), e.pageX, e.pageY, this);
            return false;
        });
		
		// enable closing of context menus on left click
		$("body").click(function() {
			hideOpenContextMenus();
		});
    }
	
	function hideOpenContextMenus()
	{
		// hide any exiting context menus
		$.vakata.context.hide();
		$('[data-toggle="dropdown"]').parent().removeClass('open');
		clearExistingHoverFileItem();
	}

    function unbindLiRightClick()
    {
        $(".fileManager .fileIconLi").unbind('contextmenu');
		$(".fileManager .folderIconLi").unbind('contextmenu');
        $(".fileManager").unbind('contextmenu');
    }

    function selectAllFiles()
    {
        $('.fileIconLi').each(function() {
            selectFile($(this).attr('fileId'), true);
        });
    }

    function selectFile(fileId, onlySelectOn)
    {
        if (typeof(onlySelectOn) == "undefined")
        {
            onlySelectOn = false;
        }

        // clear any selected if ctrl key not pressed
        if ((ctrlPressed == false) && (onlySelectOn == false))
        {
            showFileInformation(fileId);

            return false;
        }

        elementId = 'fileItem' + fileId;
        if (($('.' + elementId).hasClass('selected')) && (onlySelectOn == false))
        {
            $('.' + elementId).removeClass('selected');
            if (typeof(selectedItems['k' + fileId]) != 'undefined')
            {
                delete selectedItems['k' + fileId];
            }
        }
        else
        {
            $('.' + elementId + ':not(.fileDeletedLi)').addClass('selected');
            if ($('.' + elementId).hasClass('selected'))
            {
                selectedItems['k' + fileId] = [fileId, $('.' + elementId).attr('dttitle'), $('.' + elementId).attr('dtsizeraw'), $('.' + elementId).attr('dtfullurl'), $('.' + elementId).attr('dturlhtmlcode'), $('.' + elementId).attr('dturlbbcode')];
            }
        }

        updateSelectedFilesStatusText();
        updateFileActionButtons();
    }

    function clearSelected()
    {
        selectedItems = [];
        $('.selected').removeClass('selected');
        updateSelectedFilesStatusText();
        updateFileActionButtons();
    }

    function highlightSelected()
    {
        for (i in selectedItems)
        {
            elementId = 'fileItem' + selectedItems[i][0];
            $('.' + elementId).addClass('selected');
        }
    }

    function countSelected()
    {
        count = 0;
        for (i in selectedItems)
        {
            count = count + 1;
        }

        return count;
    }

    function getSizeSelected()
    {
        total = 0;
        for (i in selectedItems)
        {
            itemSize = parseInt(selectedItems[i][2]);
            total = total + itemSize;
        }

        return total;
    }

    function updateSelectedFilesStatusText()
    {
        count = countSelected();
        if (count > 1)
        {
            totalFilesize = getSizeSelected();
            updateStatusText(count + ' <?php echo t('selected_files', 'selected files'); ?> (' + bytesToSize(totalFilesize, 2) + ')');
        }
        else if (count == 1)
        {
            for (i in selectedItems)
            {
                itemId = selectedItems[i][0];
                itemTitle = selectedItems[i][1];
                itemSize = selectedItems[i][2];
                updateStatusText(itemTitle + ' (' + bytesToSize(itemSize, 2) + ')');
            }
        }
        else if (count == 0)
        {
            updateStatusText(null);
        }
    }

    function updateStatusText(text)
    {
        if (text != null)
        {
            text = '<i class="entypo-bag"></i> ' + text;
        }

        $('#statusText').html(text);
    }

    function toggleViewType()
    {
        if ($('#fileManager').hasClass('fileManagerList'))
        {
            $('#fileManager').removeClass('fileManagerList');
            $('#fileManager').addClass('fileManagerIcon');
            $('#viewTypeText').html('<?php echo t('list_view', 'List View'); ?>  <i class="entypo-list"></i>');
        }
        else
        {
            $('#fileManager').addClass('fileManagerList');
            $('#fileManager').removeClass('fileManagerIcon');
            $('#viewTypeText').html('<?php echo t('icon_view', 'Icon View'); ?>  <i class="entypo-layout"></i>');
        }
    }

    var ctrlPressed = false;
    $(window).keydown(function(evt) {
        if (evt.which == 17) {
            ctrlPressed = true;
        }
    }).keyup(function(evt) {
        if (evt.which == 17) {
            ctrlPressed = false;
        }
    });
	
	$(window).keydown(function(evt) {
        if (evt.which == 65) {
            if(ctrlPressed == true)
			{
				selectAllFiles();
				return false;
			}
        }
    })

    function updateFileActionButtons()
    {
        totalSelected = countSelected();
        if (totalSelected > 0)
        {
            $('#viewFileLinks .btn').removeClass('disabled');

        }
        else
        {
            $('#viewFileLinks .btn').addClass('disabled');
        }
    }

    function viewFileLinks()
    {
        count = countSelected();
        if (count > 0)
        {
            fileUrlText = '';
            htmlUrlText = '';
            bbCodeUrlText = '';
            for (i in selectedItems)
            {
                fileUrlText += selectedItems[i][3] + "<br/>";
                htmlUrlText += selectedItems[i][4] + "&lt;br/&gt;<br/>";
                bbCodeUrlText += selectedItems[i][5] + "<br/>";
            }

            $('#popupContentUrls').html(fileUrlText);
            $('#popupContentHTMLCode').html(htmlUrlText);
            $('#popupContentBBCode').html(bbCodeUrlText);

            jQuery('#fileLinksModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal');
        }
    }

    function showLightboxNotice()
    {
        jQuery('#generalModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
            $('.general-modal .modal-body').html($('#filePopupContentWrapperNotice').html());
        });
    }

    function showFileInformation(fileId)
    {
        // hide any context menus
        hideOpenContextMenus();

        // load popup
        showLoaderModal();
        jQuery('#fileDetailsModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_account_file_details.ajax.php", {u: fileId}, function() {
			reselectFileInfoTab();
			trackSelectedFileInfoTab();
            hideLoaderModal();
            jQuery('#fileDetailsModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
                addthis.toolbox('.addthis_toolbox');
            });
        });
    }
	
	function reselectFileInfoTab()
	{
		$('a[data-toggle="tab"]:eq('+filePopupSelectedTab+')').tab('show');
	}
	
	function trackSelectedFileInfoTab()
	{
		$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			// show selected tab / active
			filePopupSelectedTab = $('a[data-toggle="tab"]').index($(e.target));
		});
	}

    function refreshFileListing()
    {
        hideLoader = false;
        setLoaderImage();
        loadFiles();
    }

    function updatePaging()
    {
        totalResults = parseInt($('#rspTotalResults').val());
        totalPerPage = parseInt($('#rspTotalPerPage').val());
        currentStart = parseInt($('#rspCurrentStart').val());

        // setup paging
        pagingHtml = '';
        if (totalResults > 0)
        {
            pagingHtml += '<ul class="pagination">';
            pagingHtml += '<li><a href="#" id="previousLink" onClick="loadPreviousPage(); return false;" class="previous"><i class="entypo-left-thin"></i> <?php echo UCWords(t('previous', 'previous')); ?></a></li>';
            totalPages = Math.ceil(totalResults / totalPerPage);
            overallPages = totalPages;
            if (totalPages > 11)
            {
                totalPages = 11;
            }
            r = 0;
            if (pageStart > 0)
            {
                r = (pageStart / totalPerPage) - 5;
                if (r < 0)
                {
                    r = 0;
                }
            }
            endPoint = (r + totalPages);
            while (r < endPoint)
            {
                pageNum = r + 1;
                if (pageNum <= overallPages)
                {
                    pagingHtml += '<li';
                    if (pageStart == (r * totalPerPage))
                    {
                        pagingHtml += ' class="active"';
                    }
                    pagingHtml += '><a href="#" onClick="loadPage(' + (r * totalPerPage) + '); return false;">' + pageNum + '</a></li>';
                }
                r++;
            }
            pagingHtml += '<li><a href="#" id="nextLink" onClick="loadNextPage(); return false;" class="next"><?php echo UCWords(t('next', 'next')); ?> <i class="entypo-right-thin"></i></a></li>';
            pagingHtml += '</ul>';
        }
        $('#pagination').html(pagingHtml);

        if (totalResults > 0)
        {
            // set disabled paging links
            $('#previousLink').parent().removeClass('disabled');
            if (currentStart == 0)
            {
                $('#previousLink').parent().addClass('disabled');
            }

            $('#nextLink').parent().removeClass('disabled');
            if ((currentStart + perPage) >= totalResults)
            {
                $('#nextLink').parent().addClass('disabled');
            }
        }
    }

    function loadPreviousPage()
    {
        currentStart = parseInt($('#rspCurrentStart').val());
        if (currentStart > 0)
        {
            pageStart = pageStart - perPage;
            refreshFileListing();
        }
    }

    function loadNextPage()
    {
        totalResults = parseInt($('#rspTotalResults').val());
        if ((pageStart + perPage) < totalResults)
        {
            pageStart = pageStart + perPage;
            refreshFileListing();
        }
    }

    function loadPage(startPos)
    {
        $('html, body').animate({
            scrollTop: $(".page-body").offset().top
        }, 700);
        pageStart = startPos;
        refreshFileListing();
    }

    function doFilter(fromFilterLocal)
    {
        if (typeof(fromFilterLocal) == 'undefined')
        {
            fromFilterLocal = false;
        }

        fromFilterModal = fromFilterLocal;
        resetStartPoint();

        // if we need to filter all folders
        if (($('#filterFolderAll').is(":checked")) && (fromFilterModal == true))
        {
            $('#nodeId').val('all');
        }

        refreshFolderBreadcrumbs();
        loadFiles();
    }

    function deleteFileFromDetailPopup(fileId)
    {
        selectFile(fileId, true);
        deleteFiles(true);
    }

    function deleteFiles(fromFileDetails)
    {
        if (typeof(fromFileDetails) == 'undefined')
        {
            fromFileDetails = false;
        }

        if (countSelected() > 0)
        {
            <?php if(corefunctions::getUsersAccountLockStatus($Auth->id) == 0): ?>
            text = "<?php echo str_replace('"', '\"', t('file_manager_are_you_sure_you_want_to_delete_x_files', 'Are you sure you want to remove the selected [[[TOTAL_FILES]]] file(s)?')); ?>";
            text = text.replace('[[[TOTAL_FILES]]]', countSelected());
            
            if (confirm(text))
            {
                deleteFilesConfirm(fromFileDetails);
            }
            else
            {
                // clear selected if only 1
                if (countSelected() == 1)
                {
                    clearSelected();
                }
            }
            <?php elseif(corefunctions::getUsersAccountLockStatus($Auth->id) == 1): ?>
            text = "<?php echo t('account_locked_folder_edit_error_message', 'This account has been locked, please unlock the account to regain full functionality.'); ?>";
            if (alert(text))
            {
                return false;
            }
            <?php endif; ?>
        }

        return false;
    }

    var bulkError = '';
    var bulkSuccess = '';
    var totalDone = 0;
    function addBulkError(x)
    {
        bulkError += x;
    }
    function getBulkError(x)
    {
        return bulkError;
    }
    function addBulkSuccess(x)
    {
        bulkSuccess += x;
    }
    function getBulkSuccess(x)
    {
        return bulkSuccess;
    }
    function clearBulkResponses()
    {
        bulkError = '';
        bulkSuccess = '';
    }
    function deleteFilesConfirm(fromFileDetails)
    {
        if (typeof(fromFileDetails) == 'undefined')
        {
            fromFileDetails = false;
        }

        // clear file details popup
        if (fromFileDetails == true)
        {
            jQuery('#fileDetailsModal').modal('hide');
        }

        // show loader
        showLoaderModal(0);

        // prepare file ids
        fileIds = [];
        for (i in selectedItems)
        {
            fileIds.push(i.replace('k', ''));
        }

        // get server list first
        $.ajax({
            type: "POST",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_get_all_file_server_paths.ajax.php",
            data: {fileIds: fileIds},
            dataType: 'json',
            success: function(jsonOuter) {
                if (jsonOuter.error == true)
                {
                    // hide loader
                    hideLoaderModal();
                    $('#filePopupContentNotice').html(jsonOuter.msg);
                    showLightboxNotice();
                }
                else
                {
                    // loop file servers and attempt to remove files
                    totalDone = 0;
                    filePathsObj = jsonOuter.filePaths;
                    affectedServers = 0;
                    for (filePath in filePathsObj)
                    {
                        affectedServers++;
                    }
                    for (filePath in filePathsObj)
                    {
                        //  call server with file ids to delete
                        $.ajax({
                            type: "POST",
                            url: "<?php echo _CONFIG_SITE_PROTOCOL; ?>://" + filePath + "/core/page/ajax/_file_manage_bulk_delete.ajax.php",
                            data: {fileIds: filePathsObj[filePath]['fileIds'], csaKey1: filePathsObj[filePath]['csaKey1'], csaKey2: filePathsObj[filePath]['csaKey2']},
                            dataType: 'json',
                            xhrFields: {
                                withCredentials: true
                            },
                            success: function(json) {
                                if (json.error == true)
                                {
                                    addBulkError(filePath + ': ' + json.msg + '<br/>');
                                }
                                else
                                {
                                    addBulkSuccess(filePath + ': ' + json.msg + '<br/>');
                                }

                                totalDone++;
                                if (totalDone == affectedServers)
                                {
                                    finishBulkProcess();
                                }
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                addBulkError(filePath + ": Failed connecting to server to remove files.<br/>");
                                totalDone++;
                                if (totalDone == affectedServers)
                                {
                                    finishBulkProcess();
                                }
                            }
                        });
                    }
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#popupContentNotice').html('Failed connecting to server to get the list of servers, please try again later.');
                showLightboxNotice();
            }
        });
    }

    function finishBulkProcess()
    {
        // get final response
        bulkError = getBulkError();
        bulkSuccess = getBulkSuccess();

        // compile result
        if (bulkError.length > 0)
        {
            // hide loader
            hideLoaderModal();
            $('#filePopupContentNotice').html(bulkError + bulkSuccess);
            showLightboxNotice();
        }
        else
        {
            // hide loader
            hideLoaderModal();
        }
        clearBulkResponses();
        clearSelected();
        refreshFileListing();
        refreshFolderListing(false);

        // reload stats
        updateStatsViaAjax();
    }

    function selectPreviousFile()
    {
        // only continue if popup showing
        if (jQuery('#fileDetailsModal').data('bs.modal').isShown == true)
        {
            // get currently selected tab
            //fileDetailsSelectedTab = $('.file-details-modal .nav-tabs li.active').index();

            // get prev file id
            liItem = $('.fileItem' + fileId).prev('.fileIconLi');
            if (typeof($(liItem).attr('fileid')) != 'undefined')
            {
                fileId = $(liItem).attr('fileid');
                selectFile(fileId);
            }
        }
    }

    function selectNextFile()
    {
        // only continue if popup showing
        if (jQuery('#fileDetailsModal').data('bs.modal').isShown == true)
        {
            // get currently selected tab
            //fileDetailsSelectedTab = $('.file-details-modal .nav-tabs li.active').index();

            // get next file id
            liItem = $('.fileItem' + fileId).next('.fileIconLi');
            if (typeof($(liItem).attr('fileid')) != 'undefined')
            {
                fileId = $(liItem).attr('fileid');
                selectFile(fileId);
            }
        }
    }

    function showUploaderPopup()
    {
        jQuery('#fileUploadWrapper').modal('show', {backdrop: 'static'}).on('shown.bs.modal').on('hidden.bs.modal', function() {
            checkShowUploadProgressWidget();
        });
    }

    function downloadAllFilesFromFolder(folderId)
    {
        // only allow actual sub folders
        if (isPositiveInteger(folderId) == false)
        {
            return false;
        }

        if (confirm("<?php echo t('account_home_are_you_sure_download_all_files', 'Are you sure you want to download all the files in this folder? This may take some time to complete.'); ?>"))
        {
            downloadAllFilesFromFolderConfirm(folderId);
        }

        return false;
    }

    function downloadAllFilesFromFolderConfirm(folderId)
    {
        jQuery('#downloadFolderModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
            $('.download-folder-modal .modal-body').html('<iframe src="<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_download_all_folder_files.ajax.php?folderId=' + folderId + '" style="zoom:0.60" width="99.6%" height="100%" frameborder="0"></iframe>');
        });
    }
</script>


<script>
    function updateSorting(key, label, ele)
    {
        $('#filterOrderBy').val(key);
        $('#filterButton').html(label + ' <i class="entypo-arrow-combo"></i>');
        doFilter();
    }

    function showFilterModal()
    {
        jQuery('#filterModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
            $('#filterModal #filterFolderId').val($('#nodeId').val());
            $('#filterModal input').first().focus();
        });
    }

    function toggleFullScreenMode()
    {
        if ((document.fullScreenElement && document.fullScreenElement !== null) ||
                (!document.mozFullScreen && !document.webkitIsFullScreen)) {
            if (document.documentElement.requestFullScreen) {
                document.documentElement.requestFullScreen();
            } else if (document.documentElement.mozRequestFullScreen) {
                document.documentElement.mozRequestFullScreen();
            } else if (document.documentElement.webkitRequestFullScreen) {
                document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
            }
        } else {
            if (document.cancelFullScreen) {
                document.cancelFullScreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitCancelFullScreen) {
                document.webkitCancelFullScreen();
            }
        }
    }

    function clearSearchFilters(doFilterLocal)
    {
        if (typeof(doFilterLocal) == 'undefined')
        {
            doFilterLocal = true;
        }

        $('#filterText').val('');
        $('#filterUploadedDateRange').val('');
        $('#filterUploadedDateRange').parent().find('.daterange span').html('<?php echo str_replace("'", "\'", t('file_manager_select_range', 'Select range...')); ?>');

        if (doFilterLocal == true)
        {
            doFilter();
        }
    }

    function showAddFolderForm(parentId, editFolderId)
    {
        // only allow actual sub folders on edit
        if ((typeof(editFolderId) != 'undefined') && (isPositiveInteger(editFolderId) == false))
        {
            return false;
        }

        showLoaderModal();
        if (typeof(parentId) == 'undefined')
        {
            parentId = $('#nodeId').val();
        }

        if (typeof(editFolderId) == 'undefined')
        {
            editFolderId = 0;
        }

        jQuery('#addEditFolderModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_account_add_edit_folder.ajax.php", {parentId: parentId, editFolderId: editFolderId}, function() {
            hideLoaderModal();
            jQuery('#addEditFolderModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
                $('#addEditFolderModal input').first().focus();
            });
        });
    }

    function showLinkSection(sectionId, ele)
    {
        $('.link-section').hide();
        $('#' + sectionId).show();
        $(ele).parent().children('.active').removeClass('active');
        $(ele).addClass('active');
        $('.file-links-wrapper .modal-header .modal-title').html($(ele).html());
    }

    function selectAllText(el)
    {
        if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined")
        {
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        }
        else if (typeof document.selection != "undefined" && typeof document.body.createTextRange != "undefined")
        {
            var textRange = document.body.createTextRange();
            textRange.moveToElementText(el);
            textRange.select();
        }
    }

<?php
// load folder structure as array
$folderListing    = fileFolder::loadAllForSelect($Auth->id, '|||');
$folderListingArr = array();
foreach ($folderListing AS $k => $folderListingItem)
{
    $folderListingArr[$k] = validation::safeOutputToScreen($folderListingItem);
}
$jsArray = json_encode($folderListing);
echo "var folderArray = " . $jsArray . ";\n";
?>
    function refreshFolderBreadcrumbs()
    {
        html = '';

        // add root
        html += '<li><a href="#" onClick="$(\'#folderTreeview\').jstree(\'select_node\', $(\'#-1\'));"><i class="entypo-folder"></i><?php echo t('your_uploads', 'Your Uploads'); ?></a></li>';

        if ($('#nodeId').val() != '-1')
        {
            if (typeof(folderArray[$('#nodeId').val()]) != 'undefined')
            {
                var breadcrumbItems = folderArray[$('#nodeId').val()].split('|||');
                total = breadcrumbItems.length;
                tracker = 1;
                pathSoFar = '';
                for (i in breadcrumbItems)
                {
                    pathSoFar += breadcrumbItems[i];
                    // for last item
                    if (total == tracker)
                    {
                        // add last item
                        html += '<li class="active">' + breadcrumbItems[i] + '</li>';
                    }
                    else
                    {
                        // add item
                        html += '<li><a href="#" onClick="$(\'#folderTreeview\').jstree(\'select_node\', $(\'#' + lookupFolderIdBasedOnPath(pathSoFar) + '\'));">' + breadcrumbItems[i] + '</a></li>';
                    }
                    tracker++;
                    pathSoFar += '|||';
                }
            }

            if ($('#nodeId').val() == 'recent')
            {
                // add last item
                html += '<li class="active"><?php echo t('recent_uploads', 'Recent Uploads'); ?></li>';
            }

            if ($('#nodeId').val() == 'all')
            {
                // add last item
                html += '<li class="active"><?php echo t('all_files', 'All Files'); ?></li>';
            }

            if ($('#nodeId').val() == 'trash')
            {
                // add last item
                html += '<li class="active"><?php echo t('trash_can', 'Trash Can'); ?></li>';
            }
        }

        html += '<li id="totalFilesText" class="active">...</li>';

        $('#folderBreadcrumbs').html(html);
    }

    function lookupFolderIdBasedOnPath(path)
    {
        // lookup id
        for (a in folderArray)
        {
            if (path == folderArray[a])
            {
                return a;
            }
        }

        return -1;
    }

    function updatePerPage(key, label, ele)
    {
        $('#perPageElement').val(key);
        $('#perPageButton').html(label + ' <i class="entypo-arrow-combo"></i>');
        setPerPage();
    }

    function markInternalNotificationsRead()
    {
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_internal_notification_mark_all_read.ajax.php",
            success: function(data) {
                $('.internal-notification .unread').addClass('read').removeClass('unread');
                $('.internal-notification .text-bold').removeClass('text-bold');
                $('.internal-notification .badge').hide();
                $('.internal-notification .unread-count').html('You have 0 new notifications.');
                $('.internal-notification .mark-read-link').hide();
            }
        });
    }

    function inFileManager()
    {
<?php if (defined('FROM_ACCOUNT_HOME')): ?>
            return true;
<?php else: ?>
            return false;
<?php endif; ?>
    }

    progressWidget = null;
    function showProgressWidget(intialText, title, complete)
    {
        if (inFileManager() == false)
        {
            return false;
        }

        if (progressWidget != null)
        {
            progressWidget.hide();
        }

        var opts = {
            "closeButton": false,
            "debug": false,
            "positionClass": "toast-bottom-right",
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "0",
            "extendedTimeOut": "0",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut",
            "onclick": function() {
                showUploaderPopup();
            }
        };

        if (complete == true)
        {
            progressWidget = toastr.success(intialText, title, opts);
        }
        else
        {
            progressWidget = toastr.info(intialText, title, opts);
        }
    }

    function updateProgressWidgetText(text)
    {
        if (inFileManager() == false)
        {
            return false;
        }

        if (progressWidget == null)
        {
            return false;
        }

        $(progressWidget).find('.toast-message').html(text);
    }

    function checkShowUploadProgressWidget()
    {
        if ((inFileManager() == true) && (uploadComplete == false))
        {
            showProgressWidget('<?php echo str_replace("'", "", t('file_manager_uploading', 'Uploading...')); ?>', '<?php echo str_replace("'", "", t('file_manager_upload_progress', 'Upload Progress:')); ?>', false);
        }
    }

    function checkShowUploadFinishedWidget()
    {
        if (inFileManager() == true)
        {
            showProgressWidget('<?php echo str_replace("'", "", t('file_manager_upload_complete_click_here_to_view', 'Upload complete. Click here to view links.')); ?>', '<?php echo str_replace("'", "", t('file_manager_upload_progress', 'Upload Progress:')); ?>', true);
        }
    }

    function triggerFileDownload(fileId)
    {
        openUrl("<?php echo CORE_PAGE_WEB_ROOT; ?>/account_home_v2_direct_download.php?fileId=" + fileId);
    }

    function openUrl(url)
    {
        if (uploadComplete == false)
        {
            window.open(url);
        }
        else
        {
            window.location = url;
        }
    }

    function showEditFileForm(fileId)
    {
        showLoaderModal();
        jQuery('#editFileModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_account_edit_file.ajax.php", {fileId: fileId}, function() {
            hideLoaderModal();
            jQuery('#editFileModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
                toggleFilePasswordField();
                $('#editFileModal input').first().focus();
            });
        });
    }

    function toggleFilePasswordField()
    {
        if ($('.edit-file-modal #enablePassword').is(':checked'))
        {
            $('.edit-file-modal #password').attr('READONLY', false);
        }
        else
        {
            $('.edit-file-modal #password').attr('READONLY', true);
        }
    }

    function toggleFolderPasswordField()
    {
        if ($('.edit-folder-modal #enablePassword').is(':checked'))
        {
            $('.edit-folder-modal #password').attr('READONLY', false);
        }
        else
        {
            $('.edit-folder-modal #password').attr('READONLY', true);
        }
    }

    function updateStatsViaAjax()
    {
        // first request stats via ajax
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_get_account_file_stats.ajax.php",
            success: function(data) {
                updateOnScreenStats(data);
            }
        });
    }
	
	function updateJSFolderArray(lFolderArray)
	{
		// update list of folders for breadcrumbs
        folderArray = jQuery.parseJSON(lFolderArray);
	}

    function updateOnScreenStats(data)
    {
        // update list of folders for breadcrumbs
        updateJSFolderArray(data.folderArray);

        // update folder drop-down list in the popup uploader
        $("#upload_folder_id").html(data.folderSelectForUploader);
		
		// reselect selected items
		if (typeof(setUploadFolderId) === 'function')
		{
			setUploadFolderId($('#nodeId').val());
		}

        // update root folder stats
        if (data.totalRootFiles > 0)
        {
            $("#folderTreeview").jstree('set_text', '#-1', $('#-1').attr('original-text') + ' (' + data.totalRootFiles + ')');
        }
        else
        {
            $("#folderTreeview").jstree('set_text', '#-1', $('#-1').attr('original-text'));
        }

        // update trash folder stats
        if (data.totalTrashFiles > 0)
        {
            $("#folderTreeview").jstree('set_text', '#trash', $('#trash').attr('original-text') + ' (' + data.totalTrashFiles + ')');
        }
        else
        {
            $("#folderTreeview").jstree('set_text', '#trash', $('#trash').attr('original-text'));
        }

        // update all folder stats
        $("#folderTreeview").jstree('set_text', '#all', $('#all').attr('original-text') + ' (' + data.totalActiveFiles + ')');

        // update total storage stats
        $(".remaining-storage .progress .progress-bar").attr('aria-valuenow', data.totalStoragePercentage);
        $(".remaining-storage .progress .progress-bar").width(data.totalStoragePercentage + '%');
        $("#totalActiveFileSize").html(data.totalActiveFileSizeFormatted);
		
		// ensure breadcrumbs are up to date
		refreshFolderBreadcrumbs();
    }

    function isDesktopUser()
    {
        if ((getBrowserWidth() <= 1024) && (getBrowserWidth() > 0))
        {
            return false;
        }

        return true;
    }

    function getBrowserWidth()
    {
        return $(window).width();
    }
    
    function duplicateFiles(fromFileDetails)
    {
        if (typeof(fromFileDetails) == 'undefined')
        {
            fromFileDetails = false;
        }

        if (countSelected() > 0)
        {
            text = "<?php echo str_replace('"', '\"', t('file_manager_are_you_sure_you_want_to_duplicate_x_files', 'Are you sure you want to duplicate the selected [[[TOTAL_FILES]]] file(s)?')); ?>";
            text = text.replace('[[[TOTAL_FILES]]]', countSelected());
            if (confirm(text))
            {
                duplicateFilesConfirm(fromFileDetails);
            }
            else
            {
                // clear selected if only 1
                if (countSelected() == 1)
                {
                    clearSelected();
                }
            }
        }

        return false;
    }
    
    function duplicateFilesConfirm(fromFileDetails)
    {
        if (typeof(fromFileDetails) == 'undefined')
        {
            fromFileDetails = false;
        }

        // clear file details popup
        if (fromFileDetails == true)
        {
            jQuery('#fileDetailsModal').modal('hide');
        }

        // show loader
        showLoaderModal(0);

        // prepare file ids
        fileIds = [];
        for (i in selectedItems)
        {
            fileIds.push(i.replace('k', ''));
        }

        // duplicate files
        $.ajax({
            type: "POST",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_file_manage_bulk_duplicate.ajax.php",
            data: {fileIds: fileIds},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    // hide loader
                    hideLoaderModal();
                    $('#filePopupContentNotice').html(json.msg);
                    showLightboxNotice();
                }
                else
                {
                    // done
                    addBulkSuccess(json.msg);
                    finishBulkProcess();
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#popupContentNotice').html('Failed connecting to server, please try again later.');
                showLightboxNotice();
            }
        });
    }
    
    function loadFolderFiles(folderId)
    {
		setLastLoadedFolderCookie(folderId);
        $('#nodeId').val(folderId);
		if (typeof(setUploadFolderId) === 'function')
		{
			setUploadFolderId(folderId);
		}
		$('#folderIdDropdown').val(folderId);
		
		refreshFolderBreadcrumbs();
		loadFiles();
    }
	
	function showFolderSharingForm(folderId)
    {
		showLoaderModal();
        jQuery('#shareFolderModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_account_share_folder.ajax.php", {folderId: folderId}, function () {
            hideLoaderModal();
            jQuery('#shareFolderModal').modal('show', {backdrop: 'static'});
			createdUrl = false;
			
			// hover over tooptips
			setupToolTips();
        });
    }
	
	callbackcheck = false;
	function showStatsPopup(fileId)
    {
		showLoaderModal();
        jQuery('#statsModal .modal-content').load("<?php echo WEB_ROOT; ?>/ajax/_file_stats.ajax.php", {fileId: fileId}, function () {
            hideLoaderModal();
            jQuery('#statsModal').modal('show', {backdrop: 'static'}).on('show');
			
			// redraw charts, this is needed otherwise the charts wont show
			callbackcheck = setTimeout(function(){
				redrawCharts();
				clearTimeout(callbackcheck);
			}, 200);
        });
    }
	
	var createdUrl = false;
	function generateFolderSharingUrl(folderId)
	{
		$.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_generate_folder_sharing_url.ajax.php",
            data: {folderId: folderId},
            success: function (data) {
                if (data.error == true)
                {
                    showErrorNotification('Error', data.msg);
                }
                else
                {
                    $('#sharingUrlInput').html(data.msg);
					$('#shareEmailFolderUrl').html(data.msg);
					$('#nonPublicSharingUrls').fadeIn();
					$('#nonPublicSharingUrls').html($('.social-wrapper-template').html().replace(/SHARE_LINK/g, data.msg));
					createdUrl = true;
                }
            }
        });
	}
</script>

<?php
// output any extra account home javascript
pluginHelper::includeAppends('account_home_javascript.php');
?>