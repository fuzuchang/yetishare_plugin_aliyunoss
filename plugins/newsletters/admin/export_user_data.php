<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'newsletters');
define('ADMIN_SELECTED_SUB_PAGE', 'newsletters_export_user_data');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// phpExcel
include_once(PLUGIN_DIRECTORY_ROOT . 'newsletters/includes/PHPExcel/PHPExcel.php');

// get instance
$newslettersObj      = pluginHelper::getInstance('newsletters');
$newslettersSettings = $newslettersObj->settings;

// title
define('ADMIN_PAGE_TITLE', 'Export User Data');

// prepare data
$availableColumns = array();
$availableColumns['email']            = 'Email Address';
$availableColumns['title']            = 'Title';
$availableColumns['firstname']        = 'First Name';
$availableColumns['lastname']         = 'Last Name';
$availableColumns['username']         = 'Username';
$availableColumns['level']            = 'Account Type';
$availableColumns['status']           = 'Account Status';
$availableColumns['datecreated']      = 'Date Created';
$availableColumns['paidExpiryDate']   = 'Paid Expiry Date';
$availableColumns['lastPayment']      = 'Last Payment Date';

// available formats
$availableFormats = array();
$availableFormats['csv'] = 'CSV';
$availableFormats['xls'] = 'XLS';
$availableFormats['xlsx'] = 'XLSX';
//$availableFormats['pdf'] = 'PDF';

// default values
$columns = array();
$include_unsubscribed = '0';
$export_format        = 'csv';
$user_group           = 'all registered';

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $columns              = $_REQUEST['columns'];
    $plugin_enabled       = (int) $_REQUEST['plugin_enabled'];
    $include_unsubscribed = (int) $_REQUEST['include_unsubscribed'];
    $export_format        = trim($_REQUEST['export_format']);
    $user_group           = trim($_REQUEST['user_group']);

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    elseif (COUNT($columns) == 0)
    {
        adminFunctions::setError(adminFunctions::t("plugin_newsletter_please_choose_at_least_1_column", "Please choose at least 1 column."));
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // get all emails for newsletter
        $emailRecipients = $newslettersObj->getRecipients($user_group, ($include_unsubscribed == 1 ? true : false));

        // export data
        $dataExport = array();
        if (COUNT($emailRecipients))
        {
            foreach ($emailRecipients AS $emailRecipient)
            {
                $lArr = array();
                foreach ($emailRecipient AS $columnName => $columnValue)
                {
                    if (in_array($columnName, $columns))
                    {
                        $lArr[$columnName] = $columnValue;
                    }
                }
                $dataExport[]      = $lArr;
            }
        }

        if (COUNT($dataExport) == 0)
        {
            adminFunctions::setError(adminFunctions::t("plugin_newsletter_no_data_found", "No data found."));
        }
        else
        {
            // filename
            $fileName = 'user_export_' . date('YmdHis') . '.' . $export_format;

            // Create new PHPExcel object
            $objPHPExcel = new PHPExcel();

            // Set document properties
            $objPHPExcel->getProperties()->setCreator(SITE_CONFIG_SITE_NAME)
                    ->setLastModifiedBy(SITE_CONFIG_SITE_NAME)
                    ->setTitle("User Data Export")
                    ->setSubject("User Data Export")
                    ->setDescription("User data export from ".SITE_CONFIG_SITE_NAME)
                    ->setKeywords("user data export ".SITE_CONFIG_SITE_NAME)
                    ->setCategory("data");

            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $objPHPExcel->setActiveSheetIndex(0);
            
            // get headers
            $headerCols = array();
            foreach ($dataExport AS $row)
            {
                foreach ($row AS $k=>$cell)
                {
                    $headerCols[$k] = $availableColumns[$k];
                }
            }
            $dataExport = array_merge(array($headerCols), $dataExport);

            // create data
            $rowNum = 1;
            foreach ($dataExport AS $row)
            {
                $col = 'A';
                foreach ($row AS $cell)
                {
                    $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNum, $cell);
                    $col = ++$col;
                }
                $rowNum++;
            }

            // Rename worksheet
            $objPHPExcel->getActiveSheet()->setTitle('UserData');

            // output
            $format      = 'Excel5';
            $contentType = 'application/vnd.ms-excel';
            switch ($export_format)
            {
                case 'xls':
                    $format      = 'Excel5';
                    $contentType = 'application/vnd.ms-excel';
                    break;
                case 'xlsx':
                    $format      = 'Excel2007';
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                    break;
                case 'csv':
                    $format      = 'CSV';
                    $contentType = 'text/plain';
                    break;
            }
            header('Content-Type: ' . $contentType);
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            $objWriter   = PHPExcel_IOFactory::createWriter($objPHPExcel, $format);
            $objWriter->save('php://output');
            exit;
        }
    }
}

// page header
include_once(ADMIN_ROOT . '/_header.inc.php');
?>

<script>
$(document).ready(function() {
    $('#columns').find('option').attr('selected','selected');
});
</script>

<div class="row clearfix">
    <div class="col_12">
        <div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
        <div class="widget clearfix">
            <h2>Export User Data</h2>
            <div class="widget_inside">
                <?php echo adminFunctions::compileNotifications(); ?>
                <form method="POST" action="export_user_data.php" name="pluginForm" id="pluginForm" autocomplete="off">
                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Included Columns</h3>
                            <p>Which columns to include within the export.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Select Columns: <a href="#" onClick="$('#columns').find('option').attr('selected','selected'); return false;">(all)</a></label>
                                    <div class="input">
                                        <select multiple name="columns[]" id="columns" class="xxlarge validate[required]" style="height:100px;">
                                            <?php
                                            foreach ($availableColumns AS $columnName => $columnLabel)
                                            {
                                                echo '<option value="' . $columnName . '"';
                                                if (in_array($columnName, $columns))
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $columnLabel . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <br/>
                                        <div class="formFieldFix">Use ctrl &amp; click to select multiple.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Other Options</h3>
                            <p>Restrict by group, include any unsubscribed users and the export file format.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>By Account Type:</label>
                                    <div class="input">
                                        <?php
                                        echo '
                                        <select name="user_group" id="user_group" class="large">
                                            <option value="all registered"' . ($user_group == 'all registered' ? ' SELECTED' : '') . '>All Registered Accounts</option>
                                            <option value="free only"' . ($user_group == 'free only' ? ' SELECTED' : '') . '>Free Accounts Only</option>
                                            <option value="premium only"' . ($user_group == 'premium only' ? ' SELECTED' : '') . '>Paid Accounts Only</option>
                                            <option value="moderator only"' . ($user_group == 'moderator only' ? ' SELECTED' : '') . '>Moderator Accounts Only</option>
                                            <option value="admin only"' . ($user_group == 'admin only' ? ' SELECTED' : '') . '>Admin Accounts Only</option>
                                        </select>';
                                        ?>
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <label for="include_unsubscribed">Include Unsubscribed:</label>
                                    <div class="input" style="padding-top: 2px; height: 24px;"><input id="include_unsubscribed" name="include_unsubscribed" type="checkbox" value="1" <?php echo $include_unsubscribed == 1 ? 'CHECKED' : ''; ?>/></div>
                                </div>
                                <div class="clearfix alt-highlight">
                                    <label>Export Format:</label>
                                    <div class="input">
                                        <select name="export_format" id="export_format" class="large">
                                            <?php
                                            foreach ($availableFormats AS $k => $availableFormat)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($k == $export_format)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $availableFormat . '</option>';
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
                                    <input type="submit" value="Export Data" class="button blue">
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

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>