<script type="text/javascript">
    $.datepicker._defaults.onAfterUpdate = null;

    var datepicker__updateDatepicker = $.datepicker._updateDatepicker;
    $.datepicker._updateDatepicker = function( inst ) {
       datepicker__updateDatepicker.call( this, inst );

       var onAfterUpdate = this._get(inst, 'onAfterUpdate');
       if (onAfterUpdate)
          onAfterUpdate.apply((inst.input ? inst.input[0] : null),
             [(inst.input ? inst.input.val() : ''), inst]);
    }

    var selectedItems = [];
    var cur = -1, prv = -1;
    var pageStart = 0;
    var perPage = <?php echo $defaultPerPage; ?>;
    var fileId = 0;
    $(function() {
        // initial button state
        updateFileActionButtons();

        // load folder listing
        $("#folderTreeview").jstree({
            "plugins": [
                "themes", "json_data", "ui", "types", "crrm", "contextmenu", "cookies"
            ],
            "themes" : {
                "theme": "default",
                "dots": false,
                "icons": true,
                "url": "<?php echo SITE_CSS_PATH; ?>/jstree.css"
            },
            "core": {"animation": 150},
            "json_data": {
                "data": [
                    {
                        "data": "<?php echo t('your_uploads', 'Your Uploads'); ?>",
                        "state": "closed",
                        "attr": {"id": "-1", "rel": "home"}
                    },
                    {
                        "data": "<?php echo t('recent_uploads', 'Recent Uploads'); ?>",
                        "attr": {"id": "recent", "rel": "recent"}
                    },
                    {
                        "data": "<?php echo t('all_files', 'All Files'); ?><?php //echo ($totalActive>0)?(' ('.$totalActive.')'):''; ?>",
                        "attr": {"id": "all", "rel": "all"}
                    },
                    {
                        "data": "<?php echo t('trash_can', 'Trash Can'); ?><?php echo ($totalTrash>0)?(' ('.$totalTrash.')'):''; ?>",
                        "attr": {"id": "trash", "rel": "bin"}
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
            'types': {
                'types': {
                    'home': {
                        'icon': {'image': '<?php echo SITE_IMAGE_PATH; ?>/file_browser/icons/cloud_comment.png'}
                    },
                    'recent': {
                        'icon': {'image': '<?php echo SITE_IMAGE_PATH; ?>/file_browser/icons/clock.png'}
                    },
                    'all': {
                        'icon': {'image': '<?php echo SITE_IMAGE_PATH; ?>/file_browser/icons/folder_full.png'}
                    },
                    'bin': {
                        'icon': {'image': '<?php echo SITE_IMAGE_PATH; ?>/file_browser/icons/trash_can.png'}
                    },
                    'folderpassword': {
                        'icon': {'image': '<?php echo SITE_IMAGE_PATH; ?>/file_browser/icons/folder_password.png'}
                    },
                    'folderfull': {
                        'icon': {'image': '<?php echo SITE_IMAGE_PATH; ?>/file_browser/icons/folder_full.png'}
                    }
                }
            },
            "contextmenu": {
                "items": buildTreeViewContextMenu
            },
            'progressive_render': true
        }).bind("select_node.jstree", function(event, data) {
            clearSelected();
            $('#nodeId').val(data.rslt.obj.attr("id"));
            $('#folderIdDropdown').val($('#nodeId').val());
            setUploadFolderId($('#nodeId').val());
            resetStartPoint();
            loadFiles();
        }).delegate("a", "click", function(event, data) {
            event.preventDefault();
        }).bind('loaded.jstree', function(e, data) {
            // load default view if not stored in cookie
            var doIntial = true;
            if(typeof($.cookie("jstree_open")) != "undefined")
            {
                if($.cookie("jstree_open").length > 0)
                {
                    doIntial = false;
                }
            }
            if(doIntial == true)
            {
                $("#folderTreeview").jstree("open_node", $("#-1"));
            }
        });
        
        var doIntial = true;
        if(typeof($.cookie("jstree_select")) != "undefined")
        {
            if($.cookie("jstree_select").length > 0)
            {
                doIntial = false;
            }
        }
        if(doIntial == true)
        {
            // load file listing
            $('#nodeId').val('-1');
            loadFiles();
        }

        resetStartPoint();
        setupDatePicker();
        
        $("#fileManager").click(function (event){
            if (ctrlPressed == false)
            {
                if($(event.target).is('ul') || $(event.target).hasClass('fileManager')) {
                    clearSelected();
                }
            }
        });
        
        // do filter on return key
        $('#filterElements #filterText, #filterElements #filterUploadedDateRange').bind('keypress', function(e) {
            if(e.keyCode == 13){
                doFilter();
            }
        });
        
        $( window ).resize(function() {
            resizeElements();
        });
		
		setupFileDragSelect();
        
        <?php if(SITE_CONFIG_FILE_MANAGER_DEFAULT_VIEW == 'list'): ?>
        toggleViewType();
        <?php endif; ?>
    });
    
    $(document).keydown(function(e){
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
	
	function loadFolderFiles(folderId)
	{
		clearSelected();
		$('#nodeId').val(folderId);
		$('#folderIdDropdown').val($('#nodeId').val());
		setUploadFolderId($('#nodeId').val());
		resetStartPoint();
		loadFiles();
	}
	
	function setupFileDragSelect()
	{
		$('.fileListing')
			.drag("start",function( ev, dd ){
                //unbindLiOnClick();
				return $('<div class="fileManagerDraggleSelection" />')
					.css('opacity', .50 )
					.appendTo( document.body );
			})
			.drag(function( ev, dd ){
				$( dd.proxy ).css({
					top: Math.min( ev.pageY, dd.startY ),
					left: Math.min( ev.pageX, dd.startX ),
					height: Math.abs( ev.pageY - dd.startY ),
					width: Math.abs( ev.pageX - dd.startX )
				});
			})
			.drag("end",function( ev, dd ){
                //assignLiOnClick();
				$( dd.proxy ).remove();
			}, { distance: 10, not: $('li, span, div, a, img') });

        $('.fileIconLi:not(.fileDeletedLi)').draggable({
            revert: true,
            containment: '.fileManagerMain',
            helper: function (event) {
                selectFile($(this).attr('fileId'), true);
                var ret = $(this).clone();
                ret.find('.filename').html('<?php echo t('file_manager_moving', 'Moving'); ?> '+countSelected()+' <?php echo t('file_manager_moving_files', 'file(s)'); ?>');
                ret.find('.filename').width(100);
                ret.find('.fileUploadDate').remove();
                ret.find('.filesize').remove();
                return ret;
            },
            opacity: 0.50,
            cursorAt: { left: 5, top: 5 },
            distance: 10,
            start: function(event, ui)
            {    
                selectFile($(this).attr('fileId'), true);
                
            },
            stop: function(event, ui)
            {
                // clear selected if only 1
                if(countSelected() == 1)
                {
                    elementId = 'fileItem' + $(this).attr('fileId');
                    $('.' + elementId).removeClass('selected');
                    delete selectedItems['k'+$(this).attr('fileId')];
                }
            }
        });
        
        $(".jstree-no-dots li a").droppable({
            hoverClass: 'jstree-hovered',
            tolerance: "pointer",
            drop: function(event, ui) {
                folderId = $(this).parent().attr('id');
                moveFiles(folderId);
            }
        });
	}
    
    function moveFiles(newFolderId)
    {
        if((newFolderId == 'recent') || (newFolderId == 'all'))
        {
            return true;
        }
        
        if(newFolderId == 'trash')
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
        for(i in selectedItems)
        {
            fileIds.push(i.replace('k', ''));
        }

        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_move_file_in_folder.ajax.php",
            data: {folderId: newFolderId, fileIds: fileIds},
            success: function(data) {
                if(data.error == true)
                {
                    alert(data.msg);
                }
                else
                {
                    // refresh treeview
                    refreshFolderListing();
                    // clear selected
                    clearSelected();
                }
            }
        });
    }
	
	function reloadDragItems()
	{
		$('.fileIconLi')
			.drop("start",function(){
				$( this ).removeClass("active");
				if($( this ).hasClass("selected") == false)
				{
					$( this ).addClass("active");
				}
			})
			.drop(function( ev, dd ){
				selectFile($( this ).attr('fileId'), true);
			})
			.drop("end",function(){
				$( this ).removeClass("active");
			});
		$.drop({ multi: true });
	}
    
    function refreshFolderListing()
    {
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
    
    function setupDatePicker()
    {
        $('#uploadedDateRangePicker div').datepicker("destroy");
        
        // date range filter
        $('#uploadedDateRangePicker div')
           .datepicker({
                 dateFormat: "<?php echo coreFunctions::dateformatPhpToJqueryUi(SITE_CONFIG_DATE_FORMAT); ?>",
                 showButtonPanel: true,
                 beforeShowDay: function ( date ) {
                       return [true, ( (date.getTime() >= Math.min(prv, cur) && date.getTime() <= Math.max(prv, cur)) ? 'date-range-selected' : '')];
                    },

                 onSelect: function ( dateText, inst ) {
                       var d1, d2;

                       prv = cur;
                       cur = (new Date(inst.selectedYear, inst.selectedMonth, inst.selectedDay)).getTime();
                       if ( prv == -1 || prv == cur ) {
                          prv = cur;
                          $('#uploadedDateRangePicker input').val( dateText );
                       } else {
                          d1 = $.datepicker.formatDate( '<?php echo coreFunctions::dateformatPhpToJqueryUi(SITE_CONFIG_DATE_FORMAT); ?>', new Date(Math.min(prv,cur)), {} );
                          d2 = $.datepicker.formatDate( '<?php echo coreFunctions::dateformatPhpToJqueryUi(SITE_CONFIG_DATE_FORMAT); ?>', new Date(Math.max(prv,cur)), {} );
                          $('#uploadedDateRangePicker input').val( d1+' - '+d2 );
                       }
                    },

                 onAfterUpdate: function ( inst ) {
                       $('#uploadedDateRangePicker div .ui-datepicker-buttonpane').html('<input type="submit" class="ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all ui-state-hover" onClick="$(\'#uploadedDateRangePicker div\').hide(); return false;" value="<?php echo t('close', 'Close'); ?>"/>');
                    }
              })
           .position({
                 my: 'left top',
                 at: 'left bottom',
                 of: $('#uploadedDateRangePicker input')
              })
           .hide();

        $('#uploadedDateRangePicker input').on('focus', function (e) {
              var v = this.value,
                  d;

              try {
                 if ( v.indexOf(' - ') > -1 ) {
                    d = v.split(' - ');

                    prv = $.datepicker.parseDate( '<?php echo coreFunctions::dateformatPhpToJqueryUi(SITE_CONFIG_DATE_FORMAT); ?>', d[0] ).getTime();
                    cur = $.datepicker.parseDate( '<?php echo coreFunctions::dateformatPhpToJqueryUi(SITE_CONFIG_DATE_FORMAT); ?>', d[1] ).getTime();

                 } else if ( v.length > 0 ) {
                    prv = cur = $.datepicker.parseDate( '<?php echo coreFunctions::dateformatPhpToJqueryUi(SITE_CONFIG_DATE_FORMAT); ?>', v ).getTime();
                 }
              } catch ( e ) {
                 cur = prv = -1;
              }

              if ( cur > -1 )
                 $('#uploadedDateRangePicker div').datepicker('setDate', new Date(cur));

              $('#uploadedDateRangePicker div').datepicker('refresh').show();
           });
        
        // hide datepicker on escape
        $(window).keyup(function(e) {
            // escape
            if (e.keyCode == 27) {
                $("#uploadedDateRangePicker div").hide();
            }
            // delete key
            if (e.keyCode == 46) {
                deleteFiles();
            }
        });
    }

    function buildTreeViewContextMenu(node)
    {
        var items = {};
        if ($(node).attr('id') == 'trash')
        {
            <?php if($totalTrash > 0): ?>
            var items = {
                "Empty": {
                    "label": "<?php echo t('empty_trash', 'Empty Trash'); ?>",
                    "action": function(obj) {
                        confirmEmptyTrash();
                    }
                }
            };
            <?php endif; ?>
        }
        else if ($(node).attr('id') == '-1')
        {
            var items = {
                "Upload": {
                    "label": "<?php echo t('upload_files', 'Upload Files'); ?>",
                    "separator_after": true,
                    "action": function(obj) {
                        window.location='<?php echo WEB_ROOT; ?>/';
                    }
                },
                "Add": {
                    "label": "<?php echo t('add_folder', 'Add Folder'); ?>",
                    "action": function(obj) {
                        window.location='<?php echo WEB_ROOT; ?>/account_add_folder.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>?p='+obj.attr("id");
                    }
                }
            };
        }
        else if ($.isNumeric($(node).attr('id')))
        {
            var items = {
                "Upload": {
                    "label": "<?php echo t('upload_files', 'Upload Files'); ?>",
                    "separator_after": true,
                    "action": function(obj) {
                        window.location='<?php echo WEB_ROOT; ?>/?fid='+obj.attr("id");
                    }
                },
                "Edit": {
                    "label": "<?php echo t('edit_folder', 'Edit'); ?>",
                    "action": function(obj) {
                        window.location='<?php echo WEB_ROOT; ?>/account_edit_folder.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>?u='+obj.attr("id");
                    }
                },
                "Delete": {
                    "label": "<?php echo t('delete_folder', 'Delete'); ?>",
                    "action": function(obj) {
                        confirmRemoveFolder(obj.attr("id"));
                    }
                },
                "Add": {
                    "label": "<?php echo t('add_folder', 'Add Folder'); ?>",
                    "action": function(obj) {
                        window.location='<?php echo WEB_ROOT; ?>/account_add_folder.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>?p='+obj.attr("id");
                    }
                },
				"Download": {
                    "label": "<?php echo t('download_all_files', 'Download All Files (Zip)'); ?>",
                    "separator_before": true,
                    "action": function(obj) {
                        downloadAllFilesFromFolder(obj.attr("id"));
                    }
                },
                "Share": {
                    "label": "<?php echo t('share_folder', 'Share Folder'); ?>",
                    "separator_before": true,
                    "action": function(obj) {
                        window.location='<?php echo WEB_ROOT; ?>/'+obj.attr("id")+'~f';
                    }
                }
            };
        }

        return items;
    }
    
    function uploadFiles()
    {
        folderId = '';
        if(parseInt($('#nodeId').val()))
        {
            folderId = $('#nodeId').val();
        }
        //window.location='<?php echo WEB_ROOT; ?>/?fid='+folderId;
		showUploaderPopup();
    }
    
    function addFolder()
    {
        folderId = '';
        if(parseInt($('#nodeId').val()))
        {
            folderId = $('#nodeId').val();
        }
        window.location='<?php echo WEB_ROOT; ?>/account_add_folder.<?php echo SITE_CONFIG_PAGE_EXTENSION; ?>?p='+folderId;
    }
    
    function confirmRemoveFolder(folderId)
    {
        if(confirm('<?php echo str_replace('\'', '', t('are_you_sure_you_want_to_remove_this_folder', 'Are you sure you want to remove this folder? Any files within the folder will be moved into your default root folder and remain active.')); ?>'))
        {
            removeFolder(folderId);
        }
        
        return false;
    }
    
    function removeFolder(folderId)
    {
        $.ajax({
            dataType: "json",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_delete_folder.ajax.php",
            data: {folderId: folderId},
            success: function(data) {
                if(data.error == true)
                {
                    alert(data.msg);
                }
                else
                {
                    // refresh treeview
                    refreshFolderListing();
                }
            }
        });
    }
    
    function confirmEmptyTrash()
    {
        if(confirm('<?php echo str_replace('\'', '', t('are_you_sure_you_want_to_empty_the_trash', 'Are you sure you want to empty the trash can? Any statistics and other file information will be permanently deleted.')); ?>'))
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
                if(data.error == true)
                {
                    alert(data.msg);
                }
                else
                {
                    // reload file listing
                    loadFiles();

                    // clear the number from the trash can
                    currHtml = $('#trash a').html();
                    currHtmlSplit = currHtml.split("(");
                    $('#trash a').html(currHtmlSplit[0]);
                }
            }
        });
    }

    var hideLoader = false;
    function loadFiles()
    {
        hideLoader = false;
        setLoaderImage();
        $('#fileManager').load("<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_file_listing.ajax.php", { nodeId: $('#nodeId').val(), filterText: $('#filterText').val(), filterUploadedDateRange: $('#filterUploadedDateRange').val(), filterOrderBy: $('#filterOrderBy').val(), pageStart: pageStart, perPage: perPage }, function() {
            // ensure any selected icons are reselected
            hideLoader = true;
            setFolderStatusText();
            highlightSelected();
            updatePaging();
            updateCurrentPageText();
            updateActiveFilters();
			setupFileDragSelect();
			reloadDragItems();
            assignLiOnClick();
        });
    }
    
    function updateActiveFilters()
    {
        if($('#nodeId').val() == 'recent')
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

        statusText = totalFiles+' <?php echo t('file', 'file'); ?>';
        if(totalFiles != 1)
        {
            statusText = totalFiles+' <?php echo t('files', 'files'); ?>';
        }
        statusText += ' ('+bytesToSize(totalFileSize, 2)+')'

        updateStatusText(statusText);
    }

    function setLoaderImage()
    {
        // introduce delay to only show on slower connections, restricts flickering
        setTimeout(function() {
            if(hideLoader == false)
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
        $(".fileIconLi").click(function(e){
            e.stopPropagation();
            fileId = $(this).attr('fileId');
            selectFile(fileId);
        });
        assignLiRightClick();
    }
    
    function unbindLiOnClick()
    {
        $(".fileIconLi").unbind('click');
        unbindLiRightClick();
    }
    
    function assignLiRightClick()
    {
        $(".fileManager li").bind('contextmenu', function(e){ 
            e.stopPropagation();
            fileId = $(this).attr('fileId');
            downloadUrl = $(this).attr('dtfullurl');
            statsUrl = $(this).attr('dtstatsurl');
            isDeleted = $(this).hasClass('fileDeletedLi');
            fileName = $(this).attr('dtfilename');
            extraMenuItems = $(this).attr('dtextramenuitems');
            var items = {
                "Stats": {
                    "label": "<?php echo UCWords(t('account_file_details_stats', 'Stats')); ?>",
                    "action": function(obj) {
                        window.location=statsUrl;
                    }
                }
            };
            
            if(isDeleted == false)
            {
                var items = {};

				// replace any items for overwriting (plugins)
				if(extraMenuItems.length > 0)
				{
					items = JSON.parse(extraMenuItems);
					for(i in items)
					{
						// setup click action on menu item
						eval("items['"+i+"']['action'] = " + items[i]['action']);
					}
				}
				
				// default menu items
                items["Download"] = {
                        "label": "<?php echo UCWords(t('account_file_details_download', 'Download')); ?> "+fileName,
						"separator_after": true,
                        "action": function(obj) {
                            window.location='<?php echo CORE_PAGE_WEB_ROOT; ?>/account_home_v2_direct_download.php?fileId='+fileId;
                        }
                    };
					
                items["Edit"] = {
                        "label": "<?php echo UCWords(t('account_file_details_edit_file', 'Edit File')); ?>",
                        "action": function(obj) {
                            window.location='<?php echo WEB_ROOT; ?>/account_edit_item.html?u='+fileId;
                        }
                    };
					
                items["Delete"] = {
                        "label": "<?php echo UCWords(t('account_file_details_delete', 'Delete')); ?>",
                        "separator_after": true,
                        "action": function(obj) {
                            selectFile(fileId, true);
                            deleteFiles();
                        }
                    };
					
                items["Links"] = {
                        "label": "<?php echo UCWords(t('file_manager_links', 'Links')); ?>",
                        "action": function(obj) {
                            selectFile(fileId, true);
                            viewFileLinks();
                            // clear selected if only 1
                            if(countSelected() == 1)
                            {
                                clearSelected();
                            }
                        }
                    };
				
                items["Stats"] = {
                        "label": "<?php echo UCWords(t('account_file_details_stats', 'Stats')); ?>",
                        "action": function(obj) {
                            window.location=statsUrl;
                        }
                    };
				
				// replace any items for overwriting
				for(i in extraMenuItems)
				{
					if(typeof(items[i]) != 'undefined')
					{
						items[i] = extraMenuItems[i];
					}
				}
            }
            $.vakata.context.show(items, $(this), e.pageX, e.pageY, this);
            return false;
        });
        
        $(".fileManager").bind('contextmenu', function(e){ 
            e.stopPropagation();
            var items = {
                "SelectAll": {
                    "label": "<?php echo UCWords(t('account_file_details_select_all_files', 'Select All Files')); ?>",
                    "action": function(obj) {
                        selectAllFiles();
                    }
                }
            };
            $.vakata.context.show(items, $(this), e.pageX, e.pageY, this);
            return false;
        });
    }
    
    function unbindLiRightClick()
    {
        $(".fileManager li").unbind('contextmenu');
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
		if(typeof(onlySelectOn) == "undefined")
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
            if (typeof(selectedItems['k'+fileId]) != 'undefined')
            {
                delete selectedItems['k'+fileId];
            }
        }
        else
        {
            $('.' + elementId+':not(.fileDeletedLi)').addClass('selected');
            if($('.' + elementId).hasClass('selected'))
            {
                selectedItems['k'+fileId] = [fileId, $('.'+elementId).attr('dttitle'), $('.'+elementId).attr('dtsizeraw'), $('.'+elementId).attr('dtfullurl'), $('.'+elementId).attr('dturlhtmlcode'), $('.'+elementId).attr('dturlbbcode')];
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
            count=count+1;
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
        if(count > 1)
        {
            totalFilesize = getSizeSelected();
            updateStatusText(count+' <?php echo t('selected_files', 'selected files'); ?> ('+bytesToSize(totalFilesize, 2)+')');
        }
        else if(count == 1)
        {
            for (i in selectedItems)
            {
                itemId = selectedItems[i][0];
                itemTitle = selectedItems[i][1];
                itemSize = selectedItems[i][2];
                updateStatusText(itemTitle+' ('+bytesToSize(itemSize, 2)+')');
            }
        }
        else if(count == 0)
        {
            setFolderStatusText();
        }
    }
    
    function updateStatusText(text)
    {
        $('#statusText').html(text);
    }

    function toggleFullScreen()
    {
        if ($('#fileManagerWrapper').hasClass('fileManagerWrapper'))
        {
            $('#fileManagerWrapper').removeClass('fileManagerWrapper');
            $('#fileManagerWrapper').addClass('fileManagerWrapperFullScreen');
            $('#fullscreenText').html('<?php echo t('close_fullscreen', 'Close Fullscreen'); ?>');
            resizeElements();
        }
        else
        {
            
            $('#fileManagerWrapper').addClass('fileManagerWrapper');
            $('#fileManagerWrapper').removeClass('fileManagerWrapperFullScreen');
            $('#fullscreenText').html('<?php echo t('fullscreen', 'Fullscreen'); ?>');
            resizeElements();
        }

        setupDatePicker();
    }
    
    function toggleViewType()
    {
        if ($('#fileManager').hasClass('fileManagerList'))
        {
            $('#fileManager').removeClass('fileManagerList');
            $('#fileManager').addClass('fileManagerIcon');
            $('#viewTypeText').html('<?php echo t('list_view', 'List View'); ?>');
        }
        else
        {
            $('#fileManager').addClass('fileManagerList');
            $('#fileManager').removeClass('fileManagerIcon');
            $('#viewTypeText').html('<?php echo t('icon_view', 'Icon View'); ?>');
        }
    }
    
    function toggleTreeView()
    {
        if ($('.folderTreeCell').is(":visible"))
        {
            $('.dividerCell').hide();
            $('.folderTreeCell').hide();
            $('#toggleTreeViewText').attr('title', '<?php echo t('show_tree', 'Show Tree'); ?>');
        }
        else
        {
            $('.dividerCell').show();
            $('.folderTreeCell').show();
            $('#toggleTreeViewText').attr('title', '<?php echo t('hide_tree', 'Hide Tree'); ?>');
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
    
    function updateFileActionButtons()
    {
        totalSelected = countSelected();
        if(totalSelected > 0)
        {
            $('#viewFileLinks .button').removeClass('ui-state-disabled');
            
        }
        else
        {
            $('#viewFileLinks .button').addClass('ui-state-disabled');
        }
    }
    
    function viewFileLinks()
    {
        count = countSelected();
        if(count > 0)
        {
            fileUrlText = '';
            htmlUrlText = '';
            bbCodeUrlText = '';
            for (i in selectedItems)
            {
                fileUrlText += selectedItems[i][3]+"<br/>";
                htmlUrlText += selectedItems[i][4]+"&lt;br/&gt;<br/>";
                bbCodeUrlText += selectedItems[i][5]+"<br/>";
            }

            $('#popupContentUrls').html(fileUrlText);
            $('#popupContentHTMLCode').html(htmlUrlText);
            $('#popupContentBBCode').html(bbCodeUrlText);
            toggleUrlDiv('popupContentUrls');
        }
    }
    
    function showLightbox()
    {
        $.colorbox({maxWidth:"95%", maxHeight: "100%", html: $('#popupContentWrapper').html() });
    }
    
    function showLightboxNotice()
    {
        $.colorbox({width:"950px", maxHeight: "100%", html: $('#filePopupContentWrapperNotice').html() });
    }
    
    function showLoaderbox()
    {
        $.colorbox({width:"550px", height: "190px", html: $('#filePopupContentWrapperNotice').html(), transition: 'none' });
    }
    
    function toggleUrlDiv(eleId)
    {
        $('.popupContentUrlDiv').hide(0, function() {
            $('#'+eleId).show(0, function() {
                if(eleId == 'popupContentUrls') fileUrlText = '<?php echo t('file_urls', 'File Urls'); ?>';
                else if(eleId == 'popupContentHTMLCode') fileUrlText = '<?php echo t('urls_html_code', 'HTML Code'); ?>';
                else fileUrlText = '<?php echo t('urls_bb_code', 'Forum BBCode'); ?>';
                
                $('#urlLinkHeader h2').html(fileUrlText);
                $('.pageHeaderPopupButtons .active').removeClass('active');
                $('.'+eleId+'Button').addClass('active');
                showLightbox();
            });
        });
    }
    
    function showFileInformation(fileId)
    {
        $.colorbox({maxWidth:"95%", maxHeight: "95%", href: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_file_details.ajax.php?u="+fileId, ajax: true, onComplete: function() {
            addthis.toolbox('.addthis_toolbox');
        }
        });
    }
    
    function refreshFileListing()
    {
        hideLoader=false;
        setLoaderImage();
        loadFiles();
    }
    
    function resizeElements()
    {
        pageHeight = $(document).outerHeight();

        // for filter
        filterHeight = 0;
        if ($('#filterElements').is(":visible"))
        {
            // resize screen elements
            filterHeight = $('#filterElements').outerHeight();
        }

        // for full screen
        if ($('#fileManagerWrapper').hasClass('fileManagerWrapperFullScreen'))
        {
            $('.folderTreeCell').height(pageHeight-32);
            $('.fileManagerCell').height(pageHeight-35);
            $('.fileManager').height(pageHeight-(107+filterHeight));
            $('.folderTreeview').height(pageHeight-47);
        }
        // normal view
        else
        {
            // remove any height css
            $('.folderTreeCell').css({'height': ''});
            $('.fileManagerCell').css({'height': ''});
            $('.fileManager').css({'height': ''});
            $('.folderTreeview').css({'height': ''});
            
            // allow for filter
            if(filterHeight > 0)
            {
                $('.fileManager').height($('.fileManager').height()-filterHeight);
            }
        }
    }
    
    function toggleFilters()
    {
        if ($('#filterElements').is(":visible"))
        {
            $('#filterElements').hide();
            $('#toggleFiltersLink').removeClass("active");
            $('#uploadedDateRangePicker div').datepicker("destroy");
        }
        else
        {
            $('#filterElements').show(0, function() {
                setupDatePicker();
            });
            $('#toggleFiltersLink').addClass("active");
        }
        
        resizeElements();
    }
    
    function updatePaging()
    {
        totalResults = parseInt($('#rspTotalResults').val());
        totalPerPage = parseInt($('#rspTotalPerPage').val());
        currentStart = parseInt($('#rspCurrentStart').val());
        $('#previousLink').removeClass('disable');
        if(currentStart == 0)
        {
            $('#previousLink').addClass('disable');
        }
        
        $('#nextLink').removeClass('disable');
        if((currentStart+perPage) >= totalResults)
        {
            $('#nextLink').addClass('disable');
        }
    }
    
    function updateCurrentPageText()
    {
        currentPage = parseInt($('#rspCurrentPage').val());
        totalPages = parseInt($('#rspTotalPages').val());
        text = '';
        if(totalPages > 0)
        {
            $('.currentPageText').show();
            text = '<?php echo t('page', 'Page'); ?> '+currentPage+' <?php echo t('of', 'of'); ?> '+totalPages;
        }
        else
        {
            $('.currentPageText').hide();
        }
        
        $('.currentPageText').html(text);
    }
    
    function loadPreviousPage()
    {
        currentStart = parseInt($('#rspCurrentStart').val());
        if(currentStart > 0)
        {
            pageStart = pageStart-perPage;
            refreshFileListing();
        }
    }
    
    function loadNextPage()
    {
        totalResults = parseInt($('#rspTotalResults').val());
        if((pageStart+perPage) < totalResults)
        {
            pageStart = pageStart+perPage;
            refreshFileListing();
        }
    }
    
    function doFilter()
    {
        $("#uploadedDateRangePicker div").hide();
        resetStartPoint();
        loadFiles();
    }
    
    function deleteFileFromDetailPopup(fileId)
    {
        selectFile(fileId, true);
        deleteFiles();
    }
    
    function deleteFiles()
    {
        if(countSelected() > 0)
        {
            text = "<?php echo str_replace('"', '\"', t('file_manager_are_you_sure_you_want_to_delete_x_files', 'Are you sure you want to remove the selected [[[TOTAL_FILES]]] file(s)?')); ?>";
            text = text.replace('[[[TOTAL_FILES]]]', countSelected());
            if(confirm(text))
            {
                deleteFilesConfirm();
            }
            else
            {
                // clear selected if only 1
                if(countSelected() == 1)
                {
                    clearSelected();
                }
            }
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
    function deleteFilesConfirm()
    {
        // show loader
        showPopupLoader();
        
        // prepare file ids
        fileIds = [];
        for(i in selectedItems)
        {
            fileIds.push(i.replace('k', ''));
        }
        
        // get server list first
        $.ajax({
            type: "POST",
            url: "<?php echo CORE_AJAX_WEB_ROOT; ?>/_get_all_file_server_paths.ajax.php",
            data: { fileIds: fileIds },
            dataType: 'json',
            success: function(jsonOuter) {
                if(jsonOuter.error == true)
                {
                    $('#filePopupContentNotice').html(jsonOuter.msg);
                    showLightboxNotice();
                }
                else
                {
                    // loop file servers and attempt to remove files
                    totalDone = 0;
                    filePathsObj = jsonOuter.filePaths;
                    affectedServers = 0;
                    for(filePath in filePathsObj)
                    {
                        affectedServers++;
                    }
                    for(filePath in filePathsObj)
                    {
                        //  call server with file ids to delete
                        $.ajax({
                            type: "POST",
                            url: "<?php echo _CONFIG_SITE_PROTOCOL; ?>://"+filePath+"/core/page/ajax/_file_manage_bulk_delete.ajax.php",
                            data: {fileIds: filePathsObj[filePath]['fileIds'], csaKey1: filePathsObj[filePath]['csaKey1'], csaKey2: filePathsObj[filePath]['csaKey2']},
                            dataType: 'json',
                            xhrFields: {
                                withCredentials: true
                            },
                            success: function(json) {
                                if(json.error == true)
                                {
                                    addBulkError(filePath+': '+json.msg+'<br/>');
                                }
                                else
                                {
                                    addBulkSuccess(filePath+': '+json.msg+'<br/>');
                                }
                                
                                totalDone++;
                                if(totalDone == affectedServers)
                                {
                                    finishBulkProcess();
                                }
                            },
                            error: function (XMLHttpRequest, textStatus, errorThrown) {
                                addBulkError(filePath+": Failed connecting to server to remove files.<br/>");
                                totalDone++;
                                if(totalDone == affectedServers)
                                {
                                    finishBulkProcess();
                                }
                            }
                        });
                    }
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#popupContentNotice').html('Failed connecting to server to get the list of servers, please try again later.');
                showLightboxNotice();
            }
        });
    }
    
    function showPopupLoader()
    {
        $('#filePopupContentNotice').html('<div style="margin-left: auto; margin-right: auto; width: 64px; padding-top: 40px;"><img src="<?php echo SITE_IMAGE_PATH; ?>/file_browser/throbber_large.gif" width="64" height="64"/></div>');
        showLoaderbox();
    }
    
    function finishBulkProcess()
    {
        // get final response
        bulkError = getBulkError();
        bulkSuccess = getBulkSuccess();

        // compile result
        if(bulkError.length > 0)
        {
            $('#filePopupContentNotice').html(bulkError+bulkSuccess);
            showLightboxNotice();
        }
        else
        {
            //$('#filePopupContentNotice').html(bulkSuccess);
            //showLightboxNotice();
            $.colorbox.close();
        }
        clearBulkResponses();
        clearSelected();
        refreshFileListing();
        refreshFolderListing();
    }
    
    function selectPreviousFile()
    {
        // only continue if popup showing
        if($('#colorbox').is(":visible") == true)
        {
            // get prev file id
            liItem = $('.fileItem'+fileId).prev('.fileIconLi');
            if(typeof($(liItem).attr('fileid')) != 'undefined')
            {
                fileId = $(liItem).attr('fileid');
                selectFile(fileId);
            }
        }
    }
    
    function selectNextFile()
    {
        // only continue if popup showing
        if($('#colorbox').is(":visible") == true)
        {
            // get prev file id
            liItem = $('.fileItem'+fileId).next('.fileIconLi');
            if(typeof($(liItem).attr('fileid')) != 'undefined')
            {
                fileId = $(liItem).attr('fileid');
                selectFile(fileId);
            }
        }
    }
	
	function showUploaderPopup()
    {
		if($('#fileUploadWrapper').is(":visible") == false)
		{
			$('#uploadToolbarButton').addClass('active');
		}
		else
		{
			$('#uploadToolbarButton').removeClass('active');
		}
		if ($('#fileManagerWrapper').hasClass('fileManagerWrapperFullScreen'))
        {
			toggleFullScreen();
		}
        $('#fileUploadWrapper').slideToggle(300);
		
    }
	
	function downloadAllFilesFromFolder(folderId)
	{
		if(confirm("<?php echo t('account_home_are_you_sure_download_all_files', 'Are you sure you want to download all the files in this folder? This may take some time to complete.'); ?>"))
		{
			downloadAllFilesFromFolderConfirm(folderId);
		}
		
		return false;
	}
	
	function downloadAllFilesFromFolderConfirm(folderId)
	{
		$.colorbox({iframe:true, width:"750px", height: "400px", href: '<?php echo CORE_AJAX_WEB_ROOT; ?>/_account_home_v2_download_all_folder_files.ajax.php?folderId='+folderId, transition: 'none' });
	}
</script>
