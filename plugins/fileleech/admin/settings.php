<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// clear old download logs, kept for 3 months
$db->query('DELETE FROM plugin_fileleech_download WHERE date_download < DATE_SUB(NOW(), INTERVAL 3 MONTH)');

// load premium site details
$siteDetails = $db->getRows('SELECT * FROM plugin_fileleech_site ORDER BY site_name ASC');

// prepare variables
$plugin_enabled                 = (int) $plugin['plugin_enabled'];
$enabled_non_user               = 1;
$enabled_free_user              = 1;
$enabled_paid_user              = 1;
$max_download_traffic_non_user  = '';
$max_download_traffic_free_user = '';
$max_download_traffic_paid_user = '';
$max_download_volume_non_user   = '';
$max_download_volume_free_user  = '';
$max_download_volume_paid_user  = '';
$show_leech_tab                 = 1;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $enabled_non_user               = $plugin_settings['enabled_non_user'];
        $enabled_free_user              = $plugin_settings['enabled_free_user'];
        $enabled_paid_user              = $plugin_settings['enabled_paid_user'];
        $max_download_traffic_non_user  = $plugin_settings['max_download_traffic_non_user'];
        $max_download_traffic_free_user = $plugin_settings['max_download_traffic_free_user'];
        $max_download_traffic_paid_user = $plugin_settings['max_download_traffic_paid_user'];
        $max_download_volume_non_user   = $plugin_settings['max_download_volume_non_user'];
        $max_download_volume_free_user  = $plugin_settings['max_download_volume_free_user'];
        $max_download_volume_paid_user  = $plugin_settings['max_download_volume_paid_user'];
        $show_leech_tab                 = $plugin_settings['show_leech_tab'];
    }
}

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled                 = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled                 = $plugin_enabled != 1 ? 0 : 1;
    $enabled_non_user               = (int) $_REQUEST['enabled_non_user'];
    $enabled_free_user              = (int) $_REQUEST['enabled_free_user'];
    $enabled_paid_user              = (int) $_REQUEST['enabled_paid_user'];
    $max_download_traffic_non_user  = (float) $_REQUEST['max_download_traffic_non_user'];
    $max_download_traffic_free_user = (float) $_REQUEST['max_download_traffic_free_user'];
    $max_download_traffic_paid_user = (float) $_REQUEST['max_download_traffic_paid_user'];
    $max_download_volume_non_user   = (float) $_REQUEST['max_download_volume_non_user'];
    $max_download_volume_free_user  = (float) $_REQUEST['max_download_volume_free_user'];
    $max_download_volume_paid_user  = (float) $_REQUEST['max_download_volume_paid_user'];
    $show_leech_tab                 = (int) $_REQUEST['show_leech_tab'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                                   = array();
        $settingsArr['enabled_non_user']               = $enabled_non_user;
        $settingsArr['enabled_free_user']              = $enabled_free_user;
        $settingsArr['enabled_paid_user']              = $enabled_paid_user;
        $settingsArr['max_download_traffic_non_user']  = $max_download_traffic_non_user;
        $settingsArr['max_download_traffic_free_user'] = $max_download_traffic_free_user;
        $settingsArr['max_download_traffic_paid_user'] = $max_download_traffic_paid_user;
        $settingsArr['max_download_volume_non_user']   = $max_download_volume_non_user;
        $settingsArr['max_download_volume_free_user']  = $max_download_volume_free_user;
        $settingsArr['max_download_volume_paid_user']  = $max_download_volume_paid_user;
        $settingsArr['show_leech_tab']                 = $show_leech_tab;
        $settings                                      = json_encode($settingsArr);

        // update the user
        $dbUpdate                  = new DBObject("plugin", array("plugin_enabled", "plugin_settings"), 'id');
        $dbUpdate->plugin_enabled  = $plugin_enabled;
        $dbUpdate->plugin_settings = $settings;
        $dbUpdate->id              = $pluginId;
        $dbUpdate->update();

        adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?se=1');
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<script>
    var gEditSiteId = null;
    $(function() {
        showHideSelects();

        // dialog box
        $("#addSiteForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 400,
            buttons: {
                "Add Site": function() {
                    processAddSite();
                },
                "Cancel": function() {
                    $("#addSiteForm").dialog("close");
                }
            },
            open: function() {
                gEditSiteId = null;
                setLoader();
                loadAddSiteForm();
                resetOverlays();
            }
        });

        $("#editSiteForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 400,
            buttons: {
                "Update Download Site": function() {
                    processAddSite();
                },
                "Cancel": function() {
                    $("#editSiteForm").dialog("close");
                }
            },
            open: function() {
                setEditLoader();
                loadEditSiteForm();
                resetOverlays();
            }
        });

        $("#editSiteLoginForm").dialog({
            modal: true,
            autoOpen: false,
            width: getDefaultDialogWidth(),
            height: 400,
            buttons: {
                "Update Premium Logins": function() {
                    processAddSiteLogin();
                },
                "Cancel": function() {
                    $("#editSiteLoginForm").dialog("close");
                }
            },
            open: function() {
                setEditLoginLoader();
                loadEditSiteLoginForm();
                resetOverlays();
            }
        });
    });

    function showHideSelects()
    {
        if ($('#enabled_non_user').find(":selected").val() == '0')
        {
            $('.nonUserHidden').hide();
        }
        else
        {
            $('.nonUserHidden').show();
        }

        if ($('#enabled_free_user').find(":selected").val() == '0')
        {
            $('.freeUserHidden').hide();
        }
        else
        {
            $('.freeUserHidden').show();
        }

        if ($('#enabled_paid_user').find(":selected").val() == '0')
        {
            $('.paidUserHidden').hide();
        }
        else
        {
            $('.paidUserHidden').show();
        }
    }

    function setLoader()
    {
        $('#addSiteForm').html('Loading, please wait...');
    }

    function setEditLoader()
    {
        $('#editSiteForm').html('Loading, please wait...');
    }

    function setEditLoginLoader()
    {
        $('#editSiteLoginForm').html('Loading, please wait...');
    }

    function addSiteForm()
    {
        gEditSiteId = null;
        $('#addSiteForm').dialog('open');
    }

    function loadAddSiteForm()
    {
        $('#popupMessageContainer').remove();
        $('#addSiteForm').html('');
        $('#editSiteForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/site_manage_add_form.ajax.php",
            data: {},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#addSiteForm').html(json.msg);
                }
                else
                {
                    $('#addSiteForm').html(json.html);
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#addSiteForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function editSiteForm(siteId)
    {
        gEditSiteId = siteId;
        $('#editSiteForm').dialog('open');
    }

    function loadEditSiteForm()
    {
        $('#popupMessageContainer').remove();
        $('#addSiteForm').html('');
        $('#editSiteForm').html('');
        $.ajax({
            type: "POST",
            url: "ajax/site_manage_add_form.ajax.php",
            data: {gEditSiteId: gEditSiteId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#editSiteForm').html(json.msg);
                }
                else
                {
                    $('#editSiteForm').html(json.html);
                    showHideLoginAdditionalElements();
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#editSiteForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function editSiteLoginForm(siteId)
    {
        gEditSiteId = siteId;
        $('#editSiteLoginForm').dialog('open');
    }

    function loadEditSiteLoginForm()
    {
        $('#popupMessageContainer').remove();
        $.ajax({
            type: "POST",
            url: "ajax/login_manage_add_form.ajax.php",
            data: {gEditSiteId: gEditSiteId},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    $('#editSiteLoginForm').html(json.msg);
                }
                else
                {
                    $('#editSiteLoginForm').html(json.html);
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('#editSiteLoginForm').html(XMLHttpRequest.responseText);
            }
        });
    }

    function showHideLoginAdditionalElements()
    {
        if ($('#supports_http_auth option:selected').val() == 0)
        {
            $('.additionalElements').show();
        }
        else
        {
            $('.additionalElements').hide();
        }
    }

    function processAddSite()
    {
        // get data
        site_name = $('#site_name').val();
        site_url = $('#site_url').val();
        min_account_type = $('#min_account_type option:selected').val();
        existing_site_id = gEditSiteId;

        $.ajax({
            type: "POST",
            url: "ajax/site_manage_add_process.ajax.php",
            data: {existing_site_id: existing_site_id, site_name: site_name, site_url: site_url, min_account_type: min_account_type},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    window.location = 'settings.php?id=<?php echo $pluginId; ?>';
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function processAddSiteLogin()
    {
        // get data
        login_details = $('#login_details').val();
        existing_site_id = gEditSiteId;

        $.ajax({
            type: "POST",
            url: "ajax/login_manage_add_process.ajax.php",
            data: {existing_site_id: existing_site_id, login_details: login_details},
            dataType: 'json',
            success: function(json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                }
                else
                {
                    window.location = 'settings.php?id=<?php echo $pluginId; ?>';
                }

            },
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Plugin Settings</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="settings.php" name="pluginForm" id="pluginForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Plugin State</h3>
                            <p>Whether the File Leech plugin is enabled.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Plugin Enabled:</label>
                                    <div class="input">
                                        <select name="plugin_enabled" id="plugin_enabled" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($plugin_enabled == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Premium Accounts</h3>
                            <p>Some file hosting sites need a free or paid account in order to enable direct downloading. If you don't set any account details when it's required, files won't attempt to be leeched.<br/><br/>
                                You may also need to enable 'direct download' or 'download managers' in the site premium account aswell.<br/><br/>
                                <strong>Installation Notes:</strong><br/><br/>
                                The File Leech Plugin requires <a href="https://code.google.com/p/plowshare/" target="_blank">plowshare</a> to be installed on your server to manage the leeching. It runs on Linux/BSD/Unix operating system, Windows is not currently supported. It should be installed on every active file server configured within the script.<br/><br/>
                                - <a href="https://code.google.com/p/plowshare/wiki/Readme4#Download_&_Install" target="_blank">Installation Instructions</a><br/><br/>
                                Note that Curl is also required on the same servers. If you have any issues, please refer to the script <a href="<?php echo ADMIN_WEB_ROOT; ?>/log_file_viewer.php">log files</a>.
                            </p>
                        </div>
                        <div class="col_8 last">
                            <table class="dataTable" style="border-top: 1px solid #ABABAB; border-bottom: 1px solid #ABABAB;">
                                <thead>
                                    <tr>
                                        <th class="ui-state-default align-left">Download Site:</th>
                                        <th class="ui-state-default adminResponsiveHide">Login Details:</th>
                                        <th class="ui-state-default adminResponsiveHide">24 Hour Traffic:</th>
                                        <th class="ui-state-default adminResponsiveHide">30 Day Traffic:</th>
                                        <th class="ui-state-default">Options:</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($siteDetails AS $siteDetail)
                                    {
                                        // get stats
                                        $totalDownloads24Hour = (int) $db->getValue('SELECT COUNT(id) FROM plugin_fileleech_download WHERE site_id = ' . (int) $siteDetail['id'] . ' AND date_download > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
                                        $totalDownloads30Days = (int) $db->getValue('SELECT COUNT(id) FROM plugin_fileleech_download WHERE site_id = ' . (int) $siteDetail['id'] . ' AND date_download > DATE_SUB(NOW(), INTERVAL 30 DAY)');
                                        $totalFilesize24Hour  = (int) $db->getValue('SELECT SUM(filesize) FROM plugin_fileleech_download WHERE site_id = ' . (int) $siteDetail['id'] . ' AND date_download > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
                                        $totalFilesize30Days  = (int) $db->getValue('SELECT SUM(filesize) FROM plugin_fileleech_download WHERE site_id = ' . (int) $siteDetail['id'] . ' AND date_download > DATE_SUB(NOW(), INTERVAL 30 DAY)');

                                        // prepare min account type details
                                        $minAccountType      = '';
                                        $totalLoginAvailable = '-';
                                        if ($siteDetail['min_account_type'] == 'free')
                                        {
                                            $minAccountType      = '<span title="A free account is required for leeching." style="color: #5BA85B;">(free)</a>';
                                            $totalLoginAvailable = (int) $db->getValue('SELECT COUNT(id) FROM plugin_fileleech_access_detail WHERE site_id = ' . (int) $siteDetail['id']);
                                            $totalLoginAvailable = '<a href="#" onClick="editSiteLoginForm(' . $siteDetail['id'] . '); return false;">' . $totalLoginAvailable . '</a>';
                                        }
                                        elseif ($siteDetail['min_account_type'] == 'paid')
                                        {
                                            $minAccountType      = '<span title="A paid account is required for leeching." style="color: #D17519;">(paid)</a>';
                                            $totalLoginAvailable = (int) $db->getValue('SELECT COUNT(id) FROM plugin_fileleech_access_detail WHERE site_id = ' . (int) $siteDetail['id']);
                                            $totalLoginAvailable = '<a href="#" onClick="editSiteLoginForm(' . $siteDetail['id'] . '); return false;">' . $totalLoginAvailable . '</a>';
                                        }

                                        echo '<tr>';
                                        echo '<td><a href="http://' . $siteDetail['site_url'] . '" target="_blank">' . $siteDetail['site_name'] . '</a>&nbsp;&nbsp;' . $minAccountType . '</td>';
                                        echo '<td class="center adminResponsiveHide">' . $totalLoginAvailable . '</td>';
                                        echo '<td class="center adminResponsiveHide">' . coreFunctions::formatSize($totalFilesize24Hour) . '&nbsp;&nbsp;<span style="color: #999;">(' . $totalDownloads24Hour . ' downloads)</span></td>';
                                        echo '<td class="center adminResponsiveHide">' . coreFunctions::formatSize($totalFilesize30Days) . '&nbsp;&nbsp;<span style="color: #999;">(' . $totalDownloads30Days . ' downloads)</span></td>';
                                        $links   = array();
                                        $links[] = '<a href="#" onClick="editSiteForm(' . $siteDetail['id'] . '); return false;">edit</a>';
                                        if (strlen($minAccountType))
                                        {
                                            $links[] = '<a href="#" onClick="editSiteLoginForm(' . $siteDetail['id'] . '); return false;">logins</a>';
                                        }
                                        echo '<td class="center">' . implode(' | ', $links) . '</td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <br/>
                            <div style="float: right;">
                                <input type="submit" value="Add Site To Leech" class="button blue adminResponsiveHide" onClick="addSiteForm();
        return false;"/>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Enable By User Type</h3>
                            <p>Whether each user type can leech files or not.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Non User Enabled:</label>
                                    <div class="input">
                                        <select name="enabled_non_user" id="enabled_non_user" class="medium validate[required]" onChange="showHideSelects();
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($enabled_non_user == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix nonUserHidden">
                                    <label>Daily Leech Size:</label>
                                    <div class="input">
                                        <input type="text" name="max_download_traffic_non_user" id="max_download_traffic_non_user" class="small" value="<?php echo $max_download_traffic_non_user; ?>" />&nbsp;&nbsp;MB allowed per day for each account. 0 for unlimited
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight nonUserHidden">
                                    <label>Daily Leech Files:</label>
                                    <div class="input">
                                        <input type="text" name="max_download_volume_non_user" id="max_download_volume_non_user" class="small" value="<?php echo $max_download_volume_non_user; ?>" />&nbsp;&nbsp;files allowed by account, per day. 0 for unlimited
                                    </div>
                                </div>
                            </div>
                            <br/>

                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Free User Enabled:</label>
                                    <div class="input">
                                        <select name="enabled_free_user" id="enabled_free_user" class="medium validate[required]" onChange="showHideSelects();
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($enabled_free_user == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix freeUserHidden">
                                    <label>Daily Leech Size:</label>
                                    <div class="input">
                                        <input type="text" name="max_download_traffic_free_user" id="max_download_traffic_free_user" class="small" value="<?php echo $max_download_traffic_free_user; ?>" />&nbsp;&nbsp;MB allowed per day for each account. 0 for unlimited
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight freeUserHidden">
                                    <label>Daily Leech Files:</label>
                                    <div class="input">
                                        <input type="text" name="max_download_volume_free_user" id="max_download_volume_free_user" class="small" value="<?php echo $max_download_volume_free_user; ?>" />&nbsp;&nbsp;files allowed by account, per day. 0 for unlimited
                                    </div>
                                </div>
                            </div>
                            <br/>

                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Paid User Enabled:</label>
                                    <div class="input">
                                        <select name="enabled_paid_user" id="enabled_paid_user" class="medium validate[required]" onChange="showHideSelects();
        return false;">
                                                    <?php
                                                    $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($enabledOptions AS $k => $enabledOption)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($enabled_paid_user == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $enabledOption . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix paidUserHidden">
                                    <label>Daily Leech Size:</label>
                                    <div class="input">
                                        <input type="text" name="max_download_traffic_paid_user" id="max_download_traffic_paid_user" class="small" value="<?php echo $max_download_traffic_paid_user; ?>" />&nbsp;&nbsp;MB allowed per day for each account. 0 for unlimited
                                    </div>
                                </div>
                                <div class="clearfix alt-highlight paidUserHidden">
                                    <label>Daily Leech Files:</label>
                                    <div class="input">
                                        <input type="text" name="max_download_volume_paid_user" id="max_download_volume_paid_user" class="small" value="<?php echo $max_download_volume_paid_user; ?>" />&nbsp;&nbsp;files allowed by account, per day. 0 for unlimited
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Other Options</h3>
                            <p>Show the tab on the homepage or just use the url uploader to automatically detect files to leech.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Show Leech Tab:</label>
                                    <div class="input">
                                        <select name="show_leech_tab" id="show_leech_tab" class="medium validate[required]">
                                            <?php
                                            $enabledOptions = array(0 => 'No', 1 => 'Yes');
                                            foreach ($enabledOptions AS $k => $enabledOption)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($show_leech_tab == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $enabledOption . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4 adminResponsiveHide">&nbsp;</div>
                        <div class="col_8 last">
                            <div class="clearfix">
                                <div class="input no-label">
                                    <input type="submit" value="Submit" class="button blue">
                                    <input type="reset" value="Reset" class="button grey">
                                </div>
                            </div>
                        </div>
                    </div>

                    <input name="submitted" type="hidden" value="1"/>
                    <input name="id" type="hidden" value="<?php echo $pluginId; ?>"/>
                </form>
            </div>
        </div>   
    </div>
</div>

<div id="addSiteForm" title="Add Site To Leech"></div>
<div id="editSiteForm" title="Edit Site Details"></div>
<div id="editSiteLoginForm" title="Edit Site Logins"></div>

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>
