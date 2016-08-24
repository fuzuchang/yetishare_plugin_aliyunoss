<?php
// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
define('ADMIN_SELECTED_SUB_PAGE', 'plugin_manage');

// includes and security
include_once('../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// prepare payment days
$days = array();
for ($i = 1; $i <= 28; $i++)
{
    $date     = strtotime(date('Y-m-') . str_pad($i, 2, "0", STR_PAD_LEFT) . ' 00:00:00');
    $days[$i] = date('jS', $date);
}

// load plugin details
$pluginId = (int) $_REQUEST['id'];
$plugin   = $db->getRow("SELECT * FROM plugin WHERE id = " . (int) $pluginId . " LIMIT 1");
if (!$plugin)
{
    adminFunctions::redirect(ADMIN_WEB_ROOT . '/plugin_manage.php?error=' . urlencode('There was a problem loading the plugin details.'));
}
define('ADMIN_PAGE_TITLE', $plugin['plugin_name'] . ' Plugin Settings');

// pre-load countries
$countries = $db->getRows('SELECT iso_alpha2, name FROM plugin_reward_country_list ORDER BY name');

// pre-load ppd payout groups
$payout_groups = $db->getRows('SELECT id, group_label, payout_rate FROM plugin_reward_ppd_group ORDER BY id');

// if upgrade populate new rate structure
if ($payout_groups)
{
    foreach ($payout_groups AS $payout_group)
    {
        if ($payout_group['payout_rate'] != 0)
        {
            $db->query('UPDATE plugin_reward_ppd_group_rate SET payout_rate = ' . $db->quote($payout_group['payout_rate']) . ' WHERE payout_rate = 0 AND group_id = ' . (int) $payout_group['id']);
            $db->query('UPDATE plugin_reward_ppd_group SET payout_rate = 0 WHERE id = ' . (int) $payout_group['id'] . ' LIMIT 1');
        }
    }
}

// load ranges
$payout_ranges = $db->getRows('SELECT * FROM plugin_reward_ppd_range ORDER BY from_filesize');

// prepare variables
$plugin_enabled                 = (int) $plugin['plugin_enabled'];
$payment_lead_time              = 60;
$payment_threshold              = 50;
$payment_date                   = '05';
$ppd_enabled                    = 0;
$ppd_group_id                   = array();
$ppd_min_file_size              = 10;
$pps_enabled                    = 0;
$user_percentage                = 30;
$ppd_max_by_ip                  = '';
$ppd_max_by_file                = '';
$ppd_max_by_user                = '';
$use_download_complete_callback = 0;

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {

        $payment_lead_time = $plugin_settings['payment_lead_time'];
        $payment_threshold = $plugin_settings['payment_threshold'];
        $payment_date      = $plugin_settings['payment_date'];
        $ppd_enabled       = (int) $plugin_settings['ppd_enabled'];
        if ($ppd_enabled == 1)
        {
            $ppd_min_file_size = (int) $plugin_settings['ppd_min_file_size'];
            $ppd_max_by_ip     = floatval($plugin_settings['ppd_max_by_ip']);
            if ($ppd_max_by_ip == 0)
            {
                $ppd_max_by_ip = '';
            }
            $ppd_max_by_file = floatval($plugin_settings['ppd_max_by_file']);
            if ($ppd_max_by_file == 0)
            {
                $ppd_max_by_file = '';
            }
            $ppd_max_by_user = floatval($plugin_settings['ppd_max_by_user']);
            if ($ppd_max_by_user == 0)
            {
                $ppd_max_by_user = '';
            }
        }
        $pps_enabled = (int) $plugin_settings['pps_enabled'];
        if ($pps_enabled == 1)
        {
            $user_percentage = $plugin_settings['user_percentage'];
        }
        $use_download_complete_callback = (int) $plugin_settings['use_download_complete_callback'];
    }
}

// load outpayment types
$paymentMethods = $db->getRows('SELECT * FROM plugin_reward_outpayment_method ORDER BY label');

// handle page submissions
if (isset($_REQUEST['submitted']))
{
    // get variables
    $plugin_enabled    = (int) $_REQUEST['plugin_enabled'];
    $plugin_enabled    = $plugin_enabled != 1 ? 0 : 1;
    $payment_lead_time = (int) $_REQUEST['payment_lead_time'];
    $payment_lead_time = $payment_lead_time > 365 ? 365 : $payment_lead_time;
    $payment_threshold = (float) str_replace(array(SITE_CONFIG_COST_CURRENCY_SYMBOL, '$', '£'), '', $_REQUEST['payment_threshold']);
    $payment_threshold = $payment_threshold < 0 ? 0 : $payment_threshold;
    $payment_date      = $_REQUEST['payment_date'];
    $ppd_enabled       = (int) $_REQUEST['ppd_enabled'];
    if ($ppd_enabled == 1)
    {
        $ppd_min_file_size = (int) $_REQUEST['ppd_min_file_size'];
        $ppd_max_by_ip     = floatval($_REQUEST['ppd_max_by_ip']);
        $ppd_max_by_file   = floatval($_REQUEST['ppd_max_by_file']);
        $ppd_max_by_user   = floatval($_REQUEST['ppd_max_by_user']);
        if (isset($_REQUEST['ppd_group_id']))
        {
            $ppd_group_id = $_REQUEST['ppd_group_id'];
        }
    }

    $pps_enabled = (int) $_REQUEST['pps_enabled'];
    if ($pps_enabled == 1)
    {
        $user_percentage = (int) $_REQUEST['user_percentage'];
        $user_percentage = $user_percentage > 100 ? 100 : $user_percentage;
    }
    $use_download_complete_callback = (int) $_REQUEST['use_download_complete_callback'];

    // validate submission
    if (_CONFIG_DEMO_MODE == true)
    {
        adminFunctions::setError(adminFunctions::t("no_changes_in_demo_mode"));
    }
    else
    {
        // validate ppd groups, payouts and countries
        $currentRow = 1;
        $oneBlank   = null;
        foreach ($payout_groups AS $payout_group)
        {
            $group_id = $payout_group['id'];
            if (isset($_REQUEST['group_name_id_' . $group_id]))
            {
                // pickup variables
                $group_name         = $_REQUEST['group_name_id_' . $group_id];
                $selected_countries = $_REQUEST['selected_countries_id_' . $group_id];

                if (strlen($group_name) == 0)
                {
                    adminFunctions::setError(translate::getTranslation('rewards_plugin_error_please_enter_group_name_for_row', 'Please enter a group name for row [[[ROW_NUMBER]]]', 1, array('ROW_NUMBER' => $currentRow)));
                }
                elseif (COUNT($selected_countries) == 0)
                {
                    // allow for 1 row with blank coutries for 'default' group.
                    if ($oneBlank == null)
                    {
                        $oneBlank = translate::getTranslation('rewards_plugin_error_please_select_at_least_1_country_for_row', 'Please select at least 1 country for row [[[ROW_NUMBER]]]', 1, array('ROW_NUMBER' => $currentRow));
                    }
                    else
                    {
                        if ($oneBlank != -1)
                        {
                            adminFunctions::setError($oneBlank);
                            $oneBlank = -1;
                        }
                        adminFunctions::setError(translate::getTranslation('rewards_plugin_error_please_select_at_least_1_country_for_row', 'Please select at least 1 country for row [[[ROW_NUMBER]]]', 1, array('ROW_NUMBER' => $currentRow)));
                    }
                }
            }

            $currentRow++;
        }
    }

    // update the settings
    if (adminFunctions::isErrors() == false)
    {
        // compile new settings
        $settingsArr                      = array();
        $settingsArr['payment_lead_time'] = $payment_lead_time;
        $settingsArr['payment_threshold'] = number_format($payment_threshold, 2);
        $settingsArr['payment_date']      = $payment_date;
        $settingsArr['ppd_enabled']       = (int) $ppd_enabled;
        if ($ppd_enabled == 1)
        {
            $settingsArr['ppd_min_file_size'] = (int) $ppd_min_file_size;
            $settingsArr['ppd_max_by_ip']     = floatval($ppd_max_by_ip);
            $settingsArr['ppd_max_by_file']   = floatval($ppd_max_by_file);
            $settingsArr['ppd_max_by_user']   = floatval($ppd_max_by_user);
        }
        $settingsArr['pps_enabled'] = (int) $pps_enabled;
        if ($pps_enabled == 1)
        {
            $settingsArr['user_percentage'] = $user_percentage;
        }
        $settingsArr['use_download_complete_callback'] = $use_download_complete_callback;
        $settings                                      = json_encode($settingsArr);

        if ($ppd_enabled == 1)
        {
            foreach ($payout_groups AS $payout_group)
            {
                $group_id = $payout_group['id'];
                if (isset($_REQUEST['group_name_id_' . $group_id]))
                {
                    // pickup variables
                    $group_name         = $_REQUEST['group_name_id_' . $group_id];
                    $selected_countries = $_REQUEST['selected_countries_id_' . $group_id];

                    // update group info
                    $dbUpdate              = new DBObject("plugin_reward_ppd_group", array("group_label"), 'id');
                    $dbUpdate->group_label = $group_name;
                    $dbUpdate->id          = $group_id;
                    $dbUpdate->update();

                    // set group countries
                    $db->query('DELETE FROM plugin_reward_ppd_group_country WHERE group_id = ' . (int) $group_id);
                    if (COUNT($selected_countries))
                    {
                        foreach ($selected_countries AS $selected_country)
                        {
                            $dbInsert               = new DBObject("plugin_reward_ppd_group_country", array("group_id", "country_code"));
                            $dbInsert->group_id     = (int) $group_id;
                            $dbInsert->country_code = $selected_country;
                            $dbInsert->insert();
                        }
                    }

                    // update rates
                    foreach ($payout_ranges AS $payout_range)
                    {
                        // get rate
                        $payout_rate           = $_REQUEST['payout_rate_id_' . $payout_range['id'] . '_group_id_' . $group_id];
                        
                        // load group rate record
                        $groupRate = $db->getValue('SELECT id FROM plugin_reward_ppd_group_rate WHERE group_id = '.(int)$group_id.' AND range_id = '.$payout_range['id'].' LIMIT 1');
                        if(!$groupRate)
                        {
                            // insert
                            $dbInsert               = new DBObject("plugin_reward_ppd_group_rate", array("group_id", "range_id", "payout_rate"));
                            $dbInsert->group_id    = $group_id;
                            $dbInsert->range_id    = $payout_range['id'];
                            $dbInsert->payout_rate = $payout_rate;
                            $dbInsert->insert();
                        }
                        else
                        {
                            // update
                            $dbUpdate              = new DBObject("plugin_reward_ppd_group_rate", array("group_id", "range_id", "payout_rate"), 'id');
                            $dbUpdate->group_id    = $group_id;
                            $dbUpdate->range_id    = $payout_range['id'];
                            $dbUpdate->payout_rate = $payout_rate;
                            $dbUpdate->id          = $groupRate;
                            $dbUpdate->update();
                        }
                    }
                }
            }
        }

        // update payment active methods
        $db->query('UPDATE plugin_reward_outpayment_method SET is_enabled = 0');
        foreach ($_REQUEST['withdrawal_methods'] AS $withdrawal_method_id)
        {
            $db->query('UPDATE plugin_reward_outpayment_method SET is_enabled = 1 WHERE id = ' . (int) $withdrawal_method_id . ' LIMIT 1');
        }

        // update the plugin
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
<link rel="stylesheet" href="<?php echo PLUGIN_WEB_ROOT; ?>/rewards/assets/css/admin_styles.css" type="text/css" media="screen" />
<script>
    $(function() {
        // formvalidator
        $("#userForm").validationEngine();

        // date picker
        $("#expiry_date").datepicker({
            "dateFormat": "dd/mm/yy"
        });

        showHidePPS();
        showHidePPD();
    });

    function showHidePPS()
    {
        if ($('#pps_enabled').val() == 1)
        {
            $('.ppsOptions').show();
        }
        else
        {
            $('.ppsOptions').hide();
        }
    }

    function showHidePPD()
    {
        if ($('#ppd_enabled').val() == 1)
        {
            $('.ppdOptions').show();
        }
        else
        {
            $('.ppdOptions').hide();
        }
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
                            <p>Whether the rewards program is available.</p>
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
                            <h3>Outpayment Calculations</h3>
                            <p>Payment lead times, minimum payment threshold and the monthly payout date.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Min Payout Threshold:</label>
                                    <div class="input"><input id="payment_threshold" name="payment_threshold" type="text" class="small validate[required]" value="<?php echo adminFunctions::makeSafe($payment_threshold); ?>"/>&nbsp;<?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?></div>
                                </div>
                                <div class="clearfix">
                                    <label>Monthly Payout Date:</label>
                                    <div class="input">
                                        <select name="payment_date" id="payment_date">
                                            <?php
                                            foreach ($days AS $k => $day)
                                            {
                                                $k = str_pad($k, 2, "0", STR_PAD_LEFT);
                                                echo '<option value="' . $k . '"';
                                                if ($k == $payment_date)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $day . '</option>';
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
                            <h3>PPS Settings</h3>
                            <p>If PPS (pay per sale) is enabled, what percentage the user earns for each upgrade.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable PPS Scheme:</label>
                                    <div class="input">
                                        <select name="pps_enabled" id="pps_enabled" class="medium validate[required]" onChange="showHidePPS();
        return false;">
                                                    <?php
                                                    $options = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($options AS $k => $option)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($pps_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $option . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix ppsOptions">
                                    <label>Payout Percentage:</label>
                                    <div class="input"><input id="user_percentage" name="user_percentage" type="text" class="small validate[required]" value="<?php echo adminFunctions::makeSafe($user_percentage); ?>"/>&nbsp;%</div>
                                </div>
                                <div class="clearfix alt-highlight ppsOptions">
                                    <label>PPS Payout Lead Time:</label>
                                    <div class="input"><input id="payment_lead_time" name="payment_lead_time" type="text" class="small validate[required]" value="<?php echo adminFunctions::makeSafe($payment_lead_time); ?>"/>&nbsp;days</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>PPD Settings</h3>
                            <p>If PPD (pay per download) is enabled, manage any group names, outpayment rates and the applicable countries within each.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable PPD Scheme:</label>
                                    <div class="input">
                                        <select name="ppd_enabled" id="ppd_enabled" class="medium validate[required]" onChange="showHidePPD();
        return false;">
                                                    <?php
                                                    $options = array(0 => 'No', 1 => 'Yes');
                                                    foreach ($options AS $k => $option)
                                                    {
                                                        echo '<option value="' . $k . '"';
                                                        if ($ppd_enabled == $k)
                                                        {
                                                            echo ' SELECTED';
                                                        }
                                                        echo '>' . $option . '</option>';
                                                    }
                                                    ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="clearfix ppdOptions">
                                    <label>Minimum Filesize:</label>
                                    <div class="input">
                                        <input id="ppd_min_file_size" name="ppd_min_file_size" type="text" class="small" value="<?php echo adminFunctions::makeSafe($ppd_min_file_size); ?>"/>&nbsp;MB
                                        <br/>
                                        <div style="color: #777; font-size: 11px; padding-top: 2px;">Files below this size won't be counted in the PPD scheme. Set to 0 (zero) to ignore.</div>
                                    </div>
                                </div>

                                <div class="clearfix ppdOptions alt-highlight">
                                    <label>Max Limit By IP:</label>
                                    <div class="input">
                                        <input id="ppd_max_by_ip" name="ppd_max_by_ip" type="text" class="small" value="<?php echo adminFunctions::makeSafe($ppd_max_by_ip); ?>"/>&nbsp;<?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?>
                                        <br/>
                                        <div style="color: #777; font-size: 11px; padding-top: 2px;">The maximum amount the same IP address can earn in 1 day. Set to empty to ignore.</div>
                                    </div>
                                </div>

                                <div class="clearfix ppdOptions">
                                    <label>Max Limit By File:</label>
                                    <div class="input">
                                        <input id="ppd_max_by_file" name="ppd_max_by_file" type="text" class="small" value="<?php echo adminFunctions::makeSafe($ppd_max_by_file); ?>"/>&nbsp;<?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?>
                                        <br/>
                                        <div style="color: #777; font-size: 11px; padding-top: 2px;">The maximum amount a single file can pay out in 1 day. Set to empty to ignore.</div>
                                    </div>
                                </div>

                                <div class="clearfix ppdOptions alt-highlight">
                                    <label>Max Limit By User:</label>
                                    <div class="input">
                                        <input id="ppd_max_by_user" name="ppd_max_by_user" type="text" class="small" value="<?php echo adminFunctions::makeSafe($ppd_max_by_user); ?>"/>&nbsp;<?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?>
                                        <br/>
                                        <div style="color: #777; font-size: 11px; padding-top: 2px;">The maximum amount a user can earn in 1 day. Set to empty to ignore.</div>
                                    </div>
                                </div>
                            </div>
                            <br/>

                            <div class="form ppdOptions">
                                <?php
                                $tracker = 0;
                                foreach ($payout_groups AS $payout_group)
                                {
                                    // load existing countries
                                    $group_countries   = $db->getRows('SELECT country_code FROM plugin_reward_ppd_group_country WHERE group_id = ' . (int) $payout_group['id']);
                                    $group_country_arr = array();
                                    if ($group_countries)
                                    {
                                        foreach ($group_countries AS $group_country)
                                        {
                                            $group_country_arr[] = $group_country['country_code'];
                                        }
                                    }

                                    $group_id    = $payout_group['id'];
                                    $group_name  = $payout_group['group_label'];
                                    $payout_rate = $payout_group['payout_rate'];

                                    // pickup existing submit
                                    if (COUNT($ppd_group_id))
                                    {
                                        $group_name        = $_REQUEST['group_name_id_' . $group_id];
                                        $payout_rate       = $_REQUEST['payout_rate_id_' . $group_id];
                                        $group_country_arr = $_REQUEST['selected_countries_id_' . $group_id];
                                    }

                                    $rowClass = '';
                                    if ($tracker % 2 == 0)
                                    {
                                        $rowClass = ' alt-highlight';
                                    }
                                    $tracker++;
                                    ?>
                                    <div class="clearfix<?php echo $rowClass; ?> ppdOptions">
                                        <div>
                                            <div class="left">
                                                <label class="ppdOptionsLabel" style="font-weight: bold;">Group Name:</label>
                                                <input id="group_name" name="group_name_id_<?php echo $payout_group['id']; ?>" type="text" class="small validate[required]" value="<?php echo adminFunctions::makeSafe($group_name); ?>"/>
                                            </div>
                                            <div>
                                                <label>Applies to Countries:</label>
                                                <div class='input'>
                                                    <select multiple name="selected_countries_id_<?php echo $payout_group['id']; ?>[]" id="selected_countries" class="xlarge">
                                                        <?php
                                                        foreach ($countries AS $k => $country)
                                                        {
                                                            echo '<option value="' . $country['iso_alpha2'] . '"';
                                                            if (in_array($country['iso_alpha2'], $group_country_arr))
                                                            {
                                                                echo ' SELECTED';
                                                            }
                                                            echo '>' . $country['name'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                    <br/>
                                                    <div class="formFieldFix" style="color: #777; font-size: 11px; padding-top: 2px; padding-bottom: 8px; float: right;">Hold ctrl &amp; click to select multiple.</div>
                                                </div>
                                            </div>

                                            <?php
                                            foreach ($payout_ranges AS $payout_range)
                                            {
                                                // load actual rate
                                                $payout_rate = $db->getValue('SELECT payout_rate FROM plugin_reward_ppd_group_rate WHERE group_id = ' . (int) $group_id . ' AND range_id = ' . $payout_range['id'] . ' LIMIT 1');
                                                if (!$payout_rate)
                                                {
                                                    $payout_rate = 0;
                                                }
                                                ?>
                                                <div class="left">
                                                    <label class="ppdOptionsLabel"><?php echo adminFunctions::formatSize($payout_range['from_filesize']); ?><?php echo $payout_range['to_filesize'] != NULL ? ' - ' . adminFunctions::formatSize($payout_range['to_filesize']) : '+'; ?></label>
                                                    <input id="payout_rate" name="payout_rate_id_<?php echo $payout_range['id']; ?>_group_id_<?php echo $group_id; ?>" type="text" class="small validate[required]" value="<?php echo adminFunctions::makeSafe($payout_rate); ?>"/>&nbsp;<?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?> per 1000
                                                </div>
                                                <?php
                                            }
                                            ?>
                                            <input name="ppd_group_id[]" type="hidden" value="<?php echo $payout_group['id']; ?>"/>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>Withdrawal Methods</h3>
                            <p>Which withdrawal methods to show users. You can add new withdrawal types by adding the data to the plugin_reward_outpayment_method table in the database.</p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Active Methods:</label>
                                    <div class="input">
                                        <select name="withdrawal_methods[]" id="withdrawal_methods" class="xxlarge" MULTIPLE>
                                            <?php
                                            foreach ($paymentMethods AS $paymentMethod)
                                            {
                                                echo '<option value="' . (int) $paymentMethod['id'] . '"';
                                                if ((int) $paymentMethod['is_enabled'] == 1)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $paymentMethod['label'] . '</option>';
                                            }
                                            ?>
                                        </select><br/>
                                        <div class="formFieldFix" style="color: #777; font-size: 11px; padding-top: 2px; float: right;">Hold ctrl &amp; click to select multiple.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix col_12">
                        <div class="col_4">
                            <h3>NGINX Complete Download Log</h3>
                            <p>
                                <?php if (fileServer::nginxXAccelRedirectEnabled() == true): ?>
                                    You are using XAccelRedirect on NGINX which means PPD downloads, by default, are counted when they start, rather than when they complete. You can fix this by applying this mod so PPD earnings will only count after a file has finished downloading.
                                <?php elseif (fileServer::apacheXSendFileEnabled() == true): ?>
                                    You are using XSendFile on Apache which unfortunately does not support any callbacks after the file has finished downloading. PPD earnings are logged when downloads start. If you want this to be logged after a download finishes, switch to NGINX.
                                <?php else: ?>
                                    You do not need this mod as you're not using NGINX and XAccelRedirect for downloads. Downloads are counted when they complete already.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col_8 last">
                            <div class="form">
                                <div class="clearfix alt-highlight">
                                    <label>Enable:</label>
                                    <div class="input">
                                        <select name="use_download_complete_callback" id="use_download_complete_callback" class="medium">
                                            <?php
                                            $options = array(0 => 'No', 1 => 'Yes');
                                            foreach ($options AS $k => $option)
                                            {
                                                echo '<option value="' . $k . '"';
                                                if ($use_download_complete_callback == $k)
                                                {
                                                    echo ' SELECTED';
                                                }
                                                echo '>' . $option . '</option>';
                                            }
                                            ?>
                                        </select>&nbsp;&nbsp;NGINX only
                                    </div>
                                </div>
                                <div class="clearfix">
                                    <div style="margin: 8px;">
                                        To setup NGINX complete download logging you just need NGINX to callback the script after the download has finished. NGINX has this functionality built in so no additional modules are required. Apply the following changes to your main server and any file servers you have.<br/><br/>
                                        In /etc/nginx/conf.d/default.conf add:<br/>
                                        <pre>location /files {
    root /root/path/to/yetishare;
    post_action @afterdownload;
    internal;
}

location @afterdownload {
    proxy_pass <?php echo PLUGIN_WEB_ROOT; ?>/rewards/site/_log_download.php?request_uri=$request_uri&amp;remote_addr=$remote_addr&amp;body_bytes_sent=$body_bytes_sent&amp;status=$request_completion&amp;content_length=$content_length&amp;http_user_agent=$http_user_agent&amp;http_referer=$http_referer&amp;args=$args;
    internal;
}</pre>
                                        <strong>Note:</strong> If any of the above 'location' sections already exist, just add the additional entries into them. Replace '/root/path/to/yetishare' with the full server path to your root YetiShare install.<br/><br/>
                                        To test whether this, download a file and check the database table 'plugin_reward_ppd_complete_download' for some data.<br/><br/>
                                        If it doesn't work, try replacing the url above (<?php echo _CONFIG_CORE_SITE_HOST_URL; ?>) with the main server IP address. (you can also try the local IP if all servers are on the same network)
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

<?php
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>