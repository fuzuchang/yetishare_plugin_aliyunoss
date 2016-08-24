<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Newsletters');
define('ADMIN_SELECTED_PAGE', 'newsletters');
define('ADMIN_SELECTED_SUB_PAGE', 'newsletters_manage_newsletter');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// get instance
$newslettersObj      = pluginHelper::getInstance('newsletters');
$newslettersSettings = $newslettersObj->settings;

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<!-- Load jQuery build -->
<script type="text/javascript" src="../assets/js/tinymce/jscripts/tiny_mce/jquery.tinymce.js"></script>
<script type="text/javascript">
    oTable = null;
    gRemoveNewsletterId = null;
    gEditNewsletterId = null;
    $(document).ready(function(){
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/manage_newsletter.ajax.php',
            "bJQueryUI": true,
            "iDisplayLength": 25,
            "aaSorting": [[ 1, "desc" ]],
            "aoColumns" : [   
                { bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide" },
                { sName: 'date', sWidth: '15%', sClass: "adminResponsiveHide"},
                { sName: 'title', sWidth: '15%' },
                { sName: 'subject', sClass: "adminResponsiveHide" },
                { sName: 'status', sWidth: '19%', sClass: "center adminResponsiveHide" },
                { bSortable: false, sWidth: '15%', sClass: "center" }
            ],
            "fnServerData": function ( sSource, aoData, fnCallback ) {
                aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": "ajax/manage_newsletter.ajax.php",
                    "data": aoData,
                    "success": fnCallback
                });
            }
        });
        
        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());

        // dialog box
        $( "#addNewsletterForm" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 618,
            buttons: {
                "Save Draft": function() {
                    processCreateNewsletter(0);
                },
                "Test": function() {
                    <?php
                    if(strlen($newslettersSettings['test_email_address']) == 0)
                    {
                        ?>
                        alert('Could not find your test email address. Please set the it via the newsletter settings page in plugin management.');
                        <?php
                    }
                    else
                    {
                    ?>
                    if(confirm("Send this newsletter as a test to <?php echo htmlentities($newslettersSettings['test_email_address']); ?>? This will not send the newsletter to the selected recipients in the 'send to' drop-down."))
                    {
                        processCreateNewsletter(2);
                    }
                    <?php
                    }
                    ?>
                },
                "Send Newsletter": function() {
                    if(confirm("Are you sure you want to send this newsletter to the selected recipients?"))
                    {
                        processCreateNewsletter(1);
                    }
                },
                "Cancel": function() {
                    $("#addNewsletterForm").dialog("close");
                }
            },
            open: function() {
                setLoader();
                loadAddNewsletterForm();
            }
        });
        
        // dialog box
        $( "#confirmDelete" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Confirm Removal": function() {
                    removeNewsletter();
                    $("#confirmDelete").dialog("close");
                },
                "Cancel": function() {
                    $("#confirmDelete").dialog("close");
                }
            }
        });
        
        // view dialog box
        $( "#viewNewsletter" ).dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            buttons: {
                "Close": function() {
                    $("#viewNewsletter").dialog("close");
                }
            }
        });
        
        <?php if(isset($_REQUEST['create'])): ?>
        addNewsletterForm();
        <?php endif; ?>
    });
    
    function setLoader()
    {
        $('#addNewsletterFormInner').html('Loading, please wait...');
    }
    
    function loadAddNewsletterForm()
    {
        $('#addNewsletterFormInner').html();
        $('#editFileServerForm').html();
        $.ajax({
            type: "POST",
            url: "ajax/manage_newsletter_add_form.ajax.php",
            data: { gEditNewsletterId: gEditNewsletterId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $('#addNewsletterFormInner').html(json.msg);
                }
                else
                {
                    $('#addNewsletterFormInner').html(json.html);
                    loadEditor();
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $('#addNewsletterFormInner').html(XMLHttpRequest.responseText);
            }
        });
    }
    
    function processCreateNewsletter(send)
    {
        if(typeof(send) == "undefined")
        {
            send = 0;
        }
        
        // get data
        title = $('#title').val();
        user_group = $('#user_group').val();
        subject = $('#subject').val();
        html_content = tinyMCE.activeEditor.getContent();
        if(title.length == 0)
        {
            showError('Please enter the newsletter title.', 'popupMessageContainer');
            return false;
        }
        else if(subject.length == 0)
        {
            showError('Please enter the newsletter subject.', 'popupMessageContainer');
            return false;
        }
        else if(html_content.length == 0)
        {
            showError('Please enter the newsletter content.', 'popupMessageContainer');
            return false;
        }
        
        $(".ui-dialog-buttonset").prepend("<span class='sendingText' style='color: red; font-weight: bold;'>Sending, please wait...&nbsp;&nbsp;</span>");
        $(".ui-dialog-buttonpane button:contains('Send Now')").button("disable");
        $(".ui-dialog-buttonpane button").button("disable");
        $.ajax({
            type: "POST",
            url: "ajax/manage_newsletter_add_process.ajax.php",
            data: { title: title, user_group: user_group, subject: subject, html_content: html_content, send: send, gEditNewsletterId: gEditNewsletterId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    $(".sendingText").remove();
                    $(".ui-dialog-buttonpane button").button("enable");
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    $(".sendingText").remove();
                    $(".ui-dialog-buttonpane button").button("enable");
                    showSuccess(json.msg);
                    reloadTable();
                    $("#addNewsletterForm").dialog("close");
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                $(".sendingText").remove();
                $(".ui-dialog-buttonpane button").button("enable");
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }
    
    function addNewsletterForm()
    {
        gEditNewsletterId = null;
        $(".sendingText").remove();
        $(".ui-dialog-buttonpane button").button("enable");
        $('#addNewsletterForm').dialog('open');
    }
    
    function editNewsletterForm(newsletterId)
    {
        gEditNewsletterId = newsletterId;
        $('#addNewsletterForm').dialog('open');
    }
    
    function reloadTable()
    {
        oTable.fnDraw();
    }

    function loadEditor()
    {
        $('#html_content').tinymce({
            script_url : '../assets/js/tinymce/jscripts/tiny_mce/tiny_mce.js',
            theme : "advanced",
            plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
            theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontselect,fontsizeselect",
            theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,|,forecolor,backcolor",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_statusbar_location : "none",
            theme_advanced_resizing : true,
            width: getDefaultDialogWidth(),
            height: 260,
            content_css : "../assets/css/styles.css",
            convert_urls : false
        });
    }
    
    function confirmRemoveNewsletter(newsletterId)
    {
        $('#confirmDelete').dialog('open');
        gRemoveNewsletterId = newsletterId;
    }
    
    function removeNewsletter()
    {
        $.ajax({
            type: "POST",
            url: "ajax/manage_newsletter_remove.ajax.php",
            data: { gRemoveNewsletterId: gRemoveNewsletterId },
            dataType: 'json',
            success: function(json) {
                if(json.error == true)
                {
                    showError(json.msg);
                }
                else
                {
                    showSuccess(json.msg);
                    reloadTable();
                }
                
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }
    
    function insertReplacement(text)
    {
        tinyMCE.activeEditor.execCommand('mceInsertContent', false, text);
    }
    
    function viewNewsletter(newsletterId)
    {
        $("#viewNewsletterIFrame").attr("src", '#');
        $('#viewNewsletter').dialog('open');
        $("#viewNewsletterIFrame").attr("src", 'manage_newsletter_view.php?id='+newsletterId);
    }
</script>

<div class="row clearfix">
    <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
    <div class="widget clearfix">
        <h2>Newsletters</h2>
        <div class="widget_inside responsiveTable">
            <?php echo adminFunctions::compileNotifications(); ?>
            <div class="col_12">
                <table id='fileTable' class='dataTable'>
                    <thead>
                        <tr>
                            <th></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("newsletter_created", "created")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("newsletter_title", "title")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("newsletter_subject", "subject")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("newsletter_status", "status")); ?></th>
                            <th class="align-left"><?php echo UCWords(adminFunctions::t("action", "action")); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="clear"></div>
            
            <div class="newsletterButton" style="float: right; padding-top: 9px;">
                <a href="<?php echo PLUGIN_WEB_ROOT . '/newsletters/site/unsubscribe.php'; ?>" class="button blue" target="_blank">Unsubscribe Form</a>
                <a href="<?php echo PLUGIN_WEB_ROOT . '/newsletters/site/subscribe.php'; ?>" class="button blue" target="_blank">Subscribe Form</a>
            </div>
            <div class="newsletterButton2" style="float: left;">
                <input type="submit" value="Create Newsletter" class="button blue" onClick="addNewsletterForm(); return false;"/>
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
    </label>
</div>

<div id="addNewsletterForm" title="Create Newsletter">
    <span id="addNewsletterFormInner"></span>
</div>

<div id="confirmDelete" title="Confirm Action">
    <p>Are you sure you want to remove this draft newsletter?</p>
</div>

<div id="viewNewsletter" title="Viewing Newsletter">
    <iframe id="viewNewsletterIFrame" src="#" frameborder="0" scrolling="auto" width="780" height="350" marginwidth="0"></iframe>
</div>

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>