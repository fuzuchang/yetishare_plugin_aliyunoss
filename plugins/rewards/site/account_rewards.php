<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// check to make sure the current user has an affiliate id
$affKey = $db->getValue("SELECT affiliate_id FROM plugin_reward_affiliate_id WHERE user_id = " . (int) $Auth->id . " LIMIT 1");
if (!$affKey)
{
    $foundKey = true;

    // create key
    while ($foundKey == true)
    {
        $affKey   = MD5(time() . $Auth->id);
        $affKey   = substr($affKey, 0, 16);
        $foundKey = $db->getValue("SELECT user_id FROM plugin_reward_affiliate_id WHERE affiliate_id = " . $db->quote($affKey) . " LIMIT 1");
    }

    // update db with new user key
    $db->query('INSERT INTO plugin_reward_affiliate_id (user_id, affiliate_id) VALUES (:user_id, :affiliate_id)', array('user_id'      => (int) $Auth->id, 'affiliate_id' => $affKey));
}

// get existing paypal email address
$paypalEmail = $db->getValue("SELECT paypal_email FROM plugin_reward_affiliate_id WHERE user_id = " . (int) $Auth->id . " LIMIT 1");

// get instance
$rewardObj       = pluginHelper::getInstance('rewards');
$rewardObj->clearPendingRewards();
$rewardObj->aggregateRewards();
$rewardsSettings = $rewardObj->settings;

// get rewards available for withdrawal
$availableForWithdraw = $db->getValue("SELECT SUM(amount) AS total FROM plugin_reward_aggregated WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('available')");
$availableForWithdrawRaw = $availableForWithdraw;
$availableForWithdraw = substr(number_format($availableForWithdraw, 3), 0, strlen(number_format($availableForWithdraw, 3))-1);

// get total aggregated rewards
$totalAggregated = (float) $db->getValue("SELECT SUM(amount) AS total FROM plugin_reward_aggregated WHERE reward_user_id = " . (int) $Auth->id);

// get total pending payment
$totalPendingPayment = (float) $db->getValue("SELECT SUM(amount) AS total FROM plugin_reward_withdraw_request WHERE reward_user_id = " . (int) $Auth->id." AND status='pending'");
$totalPendingPayment = number_format($totalPendingPayment, 2);

// get total paid
$totalPaid = (float) $db->getValue("SELECT SUM(amount) AS total FROM plugin_reward_withdraw_request WHERE reward_user_id = " . (int) $Auth->id." AND status='paid'");
$totalPaidRaw = $totalPaid;
$totalPaid = number_format($totalPaid, 2);

// get current user total rewards
$totalRewards = $db->getValue("SELECT SUM(reward_amount) AS total FROM plugin_reward WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('pending', 'cleared')");
$totalRewards = number_format($totalRewards, 2);

// get current user total PPD downloads
$totalPPDDownloads = $db->getValue("SELECT COUNT(id) AS total FROM plugin_reward_ppd_detail WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('pending', 'cleared', 'aggregated')");

// get current user total PPD downloads uncleared
$totalPPDDownloadsUncleared = $db->getValue("SELECT COUNT(id) AS total FROM plugin_reward_ppd_detail WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('pending', 'cleared')");

// get current user total PPD downloads
$totalPPDDownloadsUnclearedValue = (float)$db->getValue("SELECT SUM(reward_amount) AS total FROM plugin_reward_ppd_detail WHERE reward_user_id = " . (int) $Auth->id . " AND status IN ('pending', 'cleared')");
if($totalPPDDownloadsUnclearedValue == 0)
{
    $totalPPDDownloadsUnclearedValue = number_format($totalPPDDownloadsUnclearedValue, 2);
}

// get payment method data
$paymentMethods = $db->getRows('SELECT * FROM plugin_reward_outpayment_method WHERE is_enabled = 1');

// setup page
define("PAGE_NAME", t("rewards", "Rewards"));
define("PAGE_DESCRIPTION", t("rewards_meta_description", "Rewards"));
define("PAGE_KEYWORDS", t("rewards_meta_keywords", "earn, money, rewards, cash, sales, affiliate, file, hosting, site"));
define('CURRENT_PAGE_KEY', 'rewards_logged_in');

// include header
require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
?>

<script>
    $(document).ready(function() {
        $('#rewardsData').dataTable({
            "sPaginationType": "full_numbers",
            "bAutoWidth": false,
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": '_account_rewards.ajax.php',
            "iDisplayLength": 50,
            "bFilter": false,
            "bSort": false,
            "bLengthChange": false,
            "aoColumns": [
                {sClass: "alignCenter"},
                {sClass: "alignCenter"},
                {sClass: "alignCenter mobileHide"},
                {sClass: "alignCenter"}
            ],
            "oLanguage": {
                "oPaginate": {
                    "sFirst": "<?php echo t('datatable_first', 'First'); ?>",
                    "sPrevious": "<?php echo t('datatable_previous', 'Previous'); ?>",
                    "sNext": "<?php echo t('datatable_next', 'Next'); ?>",
                    "sLast": "<?php echo t('datatable_last', 'Last'); ?>"
                },
                "sEmptyTable": "<?php echo t('datatable_no_data_available_in_table', 'No data available in table'); ?>",
                "sInfo": "<?php echo t('datatable_showing_x_to_x_of_total_entries', 'Showing _START_ to _END_ of _TOTAL_ entries'); ?>",
                "sInfoEmpty": "<?php echo t('datatable_no_data', 'No data'); ?>",
                "sLengthMenu": "<?php echo t('datatable_show_menu_entries', 'Show _MENU_ entries'); ?>",
                "sProcessing": "<?php echo t('datatable_loading_please_wait', 'Loading, please wait...'); ?>",
                "sInfoFiltered": "<?php echo t('datatable_base_filtered', ' (filtered)'); ?>",
                "sSearch": "<?php echo t('datatable_search_text', 'Search:'); ?>",
                "sZeroRecords": "<?php echo t('datatable_no_matching_records_found', 'No matching records found'); ?>"
            }
        });

        $('#rewardsDataPPD').dataTable({
            "sPaginationType": "full_numbers",
            "bAutoWidth": false,
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": '_account_rewards_ppd.ajax.php',
            "iDisplayLength": 50,
            "bFilter": false,
            "bSort": false,
            "bLengthChange": false,
            "aoColumns": [
                {sClass: "alignCenter mobileHide"},
                {sClass: "alignCenter"},
                {sClass: "alignCenter mobileHide"},
                {sClass: "alignCenter"}
            ],
            "oLanguage": {
                "oPaginate": {
                    "sFirst": "<?php echo t('datatable_first', 'First'); ?>",
                    "sPrevious": "<?php echo t('datatable_previous', 'Previous'); ?>",
                    "sNext": "<?php echo t('datatable_next', 'Next'); ?>",
                    "sLast": "<?php echo t('datatable_last', 'Last'); ?>"
                },
                "sEmptyTable": "<?php echo t('datatable_no_data_available_in_table', 'No data available in table'); ?>",
                "sInfo": "<?php echo t('datatable_showing_x_to_x_of_total_entries', 'Showing _START_ to _END_ of _TOTAL_ entries'); ?>",
                "sInfoEmpty": "<?php echo t('datatable_no_data', 'No data'); ?>",
                "sLengthMenu": "<?php echo t('datatable_show_menu_entries', 'Show _MENU_ entries'); ?>",
                "sProcessing": "<?php echo t('datatable_loading_please_wait', 'Loading, please wait...'); ?>",
                "sInfoFiltered": "<?php echo t('datatable_base_filtered', ' (filtered)'); ?>",
                "sSearch": "<?php echo t('datatable_search_text', 'Search:'); ?>",
                "sZeroRecords": "<?php echo t('datatable_no_matching_records_found', 'No matching records found'); ?>"
            }
        });

        $('#aggregatedData').dataTable({
            "sPaginationType": "full_numbers",
            "bAutoWidth": false,
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": '_account_rewards_aggregated.ajax.php',
            "iDisplayLength": 50,
            "bFilter": false,
            "bSort": false,
            "bLengthChange": false,
            "aoColumns": [
                {},
                {},
                {sClass: "alignCenter"},
                {sClass: "alignCenter"},
                {sClass: "alignCenter"}
            ],
            "oLanguage": {
                "oPaginate": {
                    "sFirst": "<?php echo t('datatable_first', 'First'); ?>",
                    "sPrevious": "<?php echo t('datatable_previous', 'Previous'); ?>",
                    "sNext": "<?php echo t('datatable_next', 'Next'); ?>",
                    "sLast": "<?php echo t('datatable_last', 'Last'); ?>"
                },
                "sEmptyTable": "<?php echo t('datatable_no_data_available_in_table', 'No data available in table'); ?>",
                "sInfo": "<?php echo t('datatable_showing_x_to_x_of_total_entries', 'Showing _START_ to _END_ of _TOTAL_ entries'); ?>",
                "sInfoEmpty": "<?php echo t('datatable_no_data', 'No data'); ?>",
                "sLengthMenu": "<?php echo t('datatable_show_menu_entries', 'Show _MENU_ entries'); ?>",
                "sProcessing": "<?php echo t('datatable_loading_please_wait', 'Loading, please wait...'); ?>",
                "sInfoFiltered": "<?php echo t('datatable_base_filtered', ' (filtered)'); ?>",
                "sSearch": "<?php echo t('datatable_search_text', 'Search:'); ?>",
                "sZeroRecords": "<?php echo t('datatable_no_matching_records_found', 'No matching records found'); ?>"
            }
        });

        $("#confirmWithdrawal").dialog({
            autoOpen: false,
            height: 205,
            width: 350,
            modal: true,
            buttons: {
                "Withdraw <?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL . $availableForWithdraw; ?>": function() {
                    failed = false;
                    $('#withdrawForm input:visible, #withdrawForm textarea:visible').each(function(key, value){
                        if(($(this).val().length == 0) && (failed == false))
                        {
                            alert('<?php echo t("rewards_error_please_enter_all_the_details", "Error: Please enter all the outpayment details."); ?>');
                            $('#withdrawForm input:visible:first').focus();
                            failed = true;
                        }
                    });
                    
                    if(failed == false)
                    {
                        $.ajax({
                            url: '_request_withdrawal.ajax.php',
                            data: $('#withdrawForm select:visible, #withdrawForm input:visible, #withdrawForm textarea:visible').serializeArray(),
                            dataType: 'json',
                            method: 'POST',
                            success: function(data) {
                                alert(data.msg);
                                $("#confirmWithdrawal").dialog("close");
                                window.location = 'account_rewards.html';
                            },
                            error: function(data) {
                                alert("<?php echo t("rewards_there_was_a_problem_requesting_the_withdraw", "There was a problem requesting the withdrawal, please try again later."); ?>");
                            }
                        });
                    }
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            }
        });
    });

    function requestWithdrawal()
    {
        changePaymentMethod();
        $("#confirmWithdrawal").dialog("open");
    }
    
    function changePaymentMethod()
    {
        outpayment_method = $("#outpayment_method").val();
        $('.reward_group_field').hide();
        $('.group_'+outpayment_method).show();
    }
</script>

<div class="animated" data-animation="fadeInUp" data-animation-delay="900">
<div class="contentPageWrapper">
    <div class="pageSectionMainFull ui-corner-all">
        <div class="pageSectionMainInternal">
            <div id="pageHeader">
                <h2><?php
                    echo t("overview", "overview");
                    ?></h2>
            </div>
            <div>
                <table class="accountStateTable table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td class="first">
                                <?php
                                echo UCWords(t('total_unpaid_earnings', 'total unpaid earnings'));
                                ?>:
                            </td>
                            <td>
                                <?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo $availableForWithdraw; ?>
                                <?php
                                if ((float)$availableForWithdrawRaw >= (float)$rewardsSettings['payment_threshold'])
                                {
                                    echo '&nbsp;<a href="#" onClick="requestWithdrawal(); return false;">(request withdrawal)</a>';
                                }

                                if ($totalPendingPayment > 0)
                                {
                                    echo '<br/><span style="color: #999; margin-top: 4px; display: block;">(' . SITE_CONFIG_COST_CURRENCY_SYMBOL . $totalPendingPayment . ' pending payment)</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        
                        <?php if($totalPaidRaw > 0): ?>
                        <tr>
                            <td class="first">
                                <?php
                                echo UCWords(t('total_paid', 'total paid'));
                                ?>:
                            </td>
                            <td>
                                <?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL.$totalPaid; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <table class="accountStateTable table table-bordered table-striped" style="margin-top: 12px;">
                    <tbody>
                        <?php
                        if ((int) $rewardsSettings['pps_enabled'] == 1)
                        {
                            ?>
                            <tr>
                                <td class="first">
                                    <?php
                                    echo UCWords(t('estimated_pps_earnings', 'PPS estimated earnings'));
                                    ?>:
                                </td>
                                <td>
                                    <?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo $totalRewards; ?>
                                </td>
                            </tr>
                            <?php
                        }

                        if ((int) $rewardsSettings['ppd_enabled'] == 1)
                        {
                            ?>
                            <tr>
                                <td class="first">
                                    <?php
                                    echo UCWords(t('recent_ppd_earnings', 'PPD recent earnings'));
                                    ?>:
                                </td>
                                <td>
                                    <?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo $totalPPDDownloadsUnclearedValue; ?>&nbsp;&nbsp;<span style='color: #999;'><?php
                                    if($totalPPDDownloadsUncleared > 0)
                                    {
                                        echo t('from_x_items_across_all_ppd_rate_groups', '(from [[[ITEMS]]] items accross all PPD rate groups)', array('ITEMS'=>  number_format($totalPPDDownloadsUncleared, 0)));
                                    }
                                    ?></span>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="clear"><!-- --></div>
            <div style="padding-top: 3px;">
                <span style="color: #999;">
                    <?php 
                    if ((int) $rewardsSettings['pps_enabled'] == 1)
                    {
                        echo t('rewards_day_clearing_on_all_pps_rewards_next_update', '[[[DAYS]]] day clearing period on all PPS rewards. Next update [[[NEXT_UPDATE]]].', array('DAYS'=>$rewardsSettings['payment_lead_time'], 'NEXT_UPDATE'=>date('jS F Y', SITE_CONFIG_NEXT_CHECK_FOR_REWARDS_AGGREGATION)));
                    }
                    if ((int) $rewardsSettings['ppd_enabled'] == 1)
                    {
                        echo ' ';
                        echo t('rewards_ppd_recent_earnings_are_added', 'PPD recent earnings are added to your unpaid earnings each night.');
                    }
                    
                    echo ' ';
                    echo t('rewards_earnings_can_be_withdrawn_when_balance', 'Earnings can be withdrawn when balance is over [[[SYMBOL]]][[[PAYMENT_THRESHOLD]]].', array('SYMBOL'=>SITE_CONFIG_COST_CURRENCY_SYMBOL, 'PAYMENT_THRESHOLD'=>$rewardsSettings['payment_threshold']));
                    ?>
                </span>
            </div>
            <div class="clear"><!-- --></div>

            <?php
            //if ($totalAggregated > 0)
            if (1==2) // disabled for now
            {
                ?>
                <div id="pageHeader" style="padding-top: 18px;">
                    <h2><?php
                        echo t("monthly_totals", "monthly totals");
                        ?></h2>
                </div>
                <div>
                    <p class="introText">
                        <?php
                        echo '<table id="aggregatedData" width="100%" cellpadding="3" cellspacing="0" class="table table-bordered table-striped">';
                        echo '<thead>';
                        echo '<th style="width: 15%; text-align: left;" class="ui-state-default">' . t('month', 'Month') . ':</th>';
                        echo '<th style="width: 40%; text-align: left;" class="ui-state-default">' . t('description', 'Description') . ':</th>';
                        echo '<th style="width: 15%; text-align: center;" class="ui-state-default">' . t('amount', 'Amount') . ':</th>';
                        echo '<th style="width: 15%; text-align: center;" class="ui-state-default">' . t('status', 'Status') . ':</th>';
                        echo '<th style="width: 15%; text-align: center;" class="ui-state-default">' . t('options', 'Options') . ':</th>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '</tbody>';
                        echo '</table>';
                        ?>
                    </p>
                    <div class="clear"></div>
                </div>
                <div class="clear"><!-- --></div>
                <?php
            }
            ?>

            <?php
            if (($totalRewards > 0) && ((int) $rewardsSettings['pps_enabled'] == 1))
            {
                ?>
                <div id="pageHeader" style="padding-top: 18px;">
                    <h2><?php
                        echo t("pps_recent_rewards", "pps recent rewards");
                        ?></h2>
                </div>
                <div>
                    <p class="introText">
                        <?php
                        echo '<table id="rewardsData" width="100%" cellpadding="3" cellspacing="0" class="table table-bordered table-striped">';
                        echo '<thead>';
                        echo '<th style="width: 25%; text-align: center;" class="ui-state-default">' . t('reward_date', 'Reward Date') . ':</th>';
                        echo '<th style="width: 25%; text-align: center;" class="ui-state-default">' . t('reward_amount', 'Amount') . ':</th>';
                        echo '<th style="width: 25%; text-align: center;" class="ui-state-default">' . t('percentage', 'Percentage') . ':</th>';
                        echo '<th style="width: 25%; text-align: center;" class="ui-state-default">' . t('status', 'Status') . ':</th>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '</tbody>';
                        echo '</table>';
                        ?>
                    </p>
                    <div class="clear"></div>
                </div>
                <div class="clear"><!-- --></div>
                <?php
            }
            ?>

            <?php
            if ((int) $rewardsSettings['pps_enabled'] == 1)
            {
                ?>
                <div id="pageHeader" style="padding-top: 18px;">
                    <h2><?php echo t("how_to_start_earning", "how to start earning"); ?></h2>
                </div>
                <?php
                $content = '<p>
                    Earn [[[PERCENTAGE]]]% of each sale you refer to this site! You can earn money from upgrading users in 2 ways:
                </p>
                <ol class="rewardsTextList">
                    <li>Using your unique affiliate id, simply link to the site via your existing website. You\'ll earn [[[PERCENTAGE]]]% for any users which register for an account and subsequently upgrade.</li>
                    <li>From users which upgrade to download files within your account.</li>
                </ol>
                <p>
                    Begin by uploading and sharing your files or by linking from your existing site using your affiliate id like this:
                </p>
                <ul>
                    <li><a href="[[[WEB_ROOT]]]/?aff=[[[AFFILIATE_KEY]]]">[[[WEB_ROOT]]]/?aff=[[[AFFILIATE_KEY]]]</a></li>
                </ul>';
                $replacements = array();
                $replacements['PERCENTAGE'] = $rewardsSettings['user_percentage'];
                $replacements['WEB_ROOT'] = WEB_ROOT;
                $replacements['AFFILIATE_KEY'] = $affKey;
                echo t('rewards_pps_info_text_logged_in', $content, $replacements);
                ?>
                
                <div class="clear"><!-- --></div>
                <?php
            }
            ?>

            <?php
            if (($totalPPDDownloads > 0) && ((int) $rewardsSettings['ppd_enabled'] == 1))
            {
                ?>
                <div id="pageHeader" style="padding-top: 18px;">
                    <h2><?php
                        echo t("ppd_recent_downloads", "ppd recent downloads");
                        ?></h2>
                </div>
                <div>
                    <p class="introText">
                        <?php
                        echo '<table id="rewardsDataPPD" width="100%" cellpadding="3" cellspacing="0" class="table table-bordered table-striped">';
                        echo '<thead>';
                        echo '<th style="width: 20%; text-align: center;" class="ui-state-default">' . t('download_date', 'Download Date') . ':</th>';
                        echo '<th style="text-align: center;" class="ui-state-default">' . t('file', 'File') . ':</th>';
                        echo '<th style="width: 20%; text-align: center;" class="ui-state-default">' . t('group', 'Group') . ':</th>';
                        echo '<th style="width: 20%; text-align: center;" class="ui-state-default">' . t('status', 'Status') . ':</th>';
                        echo '</thead>';
                        echo '<tbody>';
                        echo '</tbody>';
                        echo '</table>';
                        ?>
                    </p>
                    <div class="clear"></div>
                </div>
                <div class="clear"><!-- --></div>
                <?php
            }
            ?>

            <?php
            if ((int) $rewardsSettings['ppd_enabled'] == 1)
            {
                $lowestValue = $db->getValue("SELECT payout_rate FROM plugin_reward_ppd_group_rate ORDER BY payout_rate ASC LIMIT 1");
                ?>
                <div id="pageHeader" style="padding-top: 18px;">
                    <h2><?php
                        echo t("pay_per_download_rates", "Pay Per Download (PPD) Rates");
                        ?></h2>
                </div>
                <p>
                    <?php
                    echo t('upload_your_files_and_youll_be_paid_for_every', 'Upload your files and you\'ll be paid for every file downloaded on your account.');
                    ?> <?php
                    if ((int) $rewardsSettings['ppd_min_file_size'] > 0)
                        echo t('files_above_x_will_count', 'Files above [[[FILE_SIZE]]]MB will count.', array('FILE_SIZE' => (int) $rewardsSettings['ppd_min_file_size']));
                    ?> <?php
                    echo t('see_the_payment_rates_below', 'See the payment rates below:');
                    ?>
                </p>
                <?php
                // load ranges
                $payout_ranges = $db->getRows('SELECT * FROM plugin_reward_ppd_range ORDER BY from_filesize');
                $colWidth = ceil(80/COUNT($payout_ranges));
                ?>
                <table class="ppdRateTable table table-bordered table-striped">
                    <thead>
                    <td style="text-align: center;"><?php
                        echo t('plugin_rewards_size_group', 'Size / Group');
                        ?>:</td>
                    <?php foreach($payout_ranges AS $payout_range): ?>
                    <td style="width: <?php echo $colWidth; ?>%; text-align: center;"><?php echo coreFunctions::formatSize($payout_range['from_filesize']); ?><?php echo $payout_range['to_filesize'] != NULL ? ' - ' . coreFunctions::formatSize($payout_range['to_filesize']) : '+'; ?> *</td>
                    <?php endforeach; ?>
                    </thead>
                    <tbody>
                        <?php
                        // track countries
                        $countriesArr = array();
                        
                        // get ppd groups
                        $groups      = $db->getRows('SELECT id, group_label, payout_rate FROM plugin_reward_ppd_group ORDER BY id');
                        foreach ($groups AS $group)
                        {
                            $countryData = $db->getRows('SELECT plugin_reward_ppd_group_country.country_code, plugin_reward_country_list.name FROM plugin_reward_ppd_group_country LEFT JOIN plugin_reward_country_list ON plugin_reward_ppd_group_country.country_code = plugin_reward_country_list.iso_alpha2 WHERE group_id=' . $group['id']);
                            $countries   = array();
                            if (COUNT($countryData))
                            {
                                foreach ($countryData AS $countryRow)
                                {
                                    $countries[] = $countryRow['name'];
                                }
                            }

                            // for no countries, assume all others
                            if (COUNT($countries) == 0)
                            {
                                $countries[] = UCWords(t('other', 'Other'));
                            }

                            $countriesStr = implode(", ", $countries);
                            $countriesArr[$group{'group_label'}] = $countriesStr;
                            ?>
                            <tr>
                                <td style="text-align: center;"><?php echo validation::safeOutputToScreen($group['group_label']); ?></td>
                                <?php
                                foreach($payout_ranges AS $payout_range)
                                {
                                    // load actual rate
                                    $payout_rate = $db->getValue('SELECT payout_rate FROM plugin_reward_ppd_group_rate WHERE group_id = ' . (int) $group['id'] . ' AND range_id = ' . $payout_range['id'] . ' LIMIT 1');
                                    if (!$payout_rate)
                                    {
                                        $payout_rate = 0;
                                    }
                                    ?>
                                    <td style="text-align: center;"><?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL . validation::safeOutputToScreen($payout_rate); ?></td>
                                    <?php
                                }
                                ?>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr><td colspan="<?php echo COUNT($payout_ranges)+1; ?>">* <?php echo t('plugin_rewards_payout_rates_are_per_1000_downloads', 'Payout rates are per 1,000 downloads.'); ?></td></tr>
                    </tbody>
                </table>
                
                <?php
                echo '<ul>';
                foreach($countriesArr AS $groupName => $countries)
                {
                    echo '<li><strong>'.$groupName.'</strong>:&nbsp;&nbsp;'.$countries.'</li>';
                }
                echo '</ul>';
                ?>
                
                <div class="clear" style="padding-top: 20px;"><!-- --></div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<div class="clear"></div>

<div id="confirmWithdrawal" title="Confirm Withdrawal" class="popupForm">
    <p><?php echo t('rewards_please_confirm_your_withdrawal', 'Please confirm your withdrawal of [[[SITE_CONFIG_COST_CURRENCY_SYMBOL]]][[[AVAILABLE_FOR_WITHDRAWAL]]]:', array('SITE_CONFIG_COST_CURRENCY_SYMBOL'=>SITE_CONFIG_COST_CURRENCY_SYMBOL, 'AVAILABLE_FOR_WITHDRAWAL'=>$availableForWithdraw)); ?></p>
    <form method="POST" id="withdrawForm" action="">
        <label for="outpayment_method">
            <?php echo t('rewards_select_payment_method', 'Payment Method:'); ?><br/>
            <select name="outpayment_method" id="outpayment_method" onChange="changePaymentMethod();">
                <?php foreach($paymentMethods AS $paymentMethod): ?>
                <option value="<?php echo validation::safeOutputToScreen($paymentMethod['name_key']); ?>"><?php echo validation::safeOutputToScreen($paymentMethod['label']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="clear"><!-- --></div>
        
        <?php
        foreach($paymentMethods AS $paymentMethod)
        {
            // get fields
            $fieldsArr = array();
            $fieldsJson = $paymentMethod['fields_json'];
            if(strlen($fieldsJson))
            {
                $fieldsArr = json_decode($fieldsJson, true);
            }
            
            if(is_array($fieldsArr) && COUNT($fieldsArr))
            {
                foreach($fieldsArr AS $k=>$field)
                {
                    ?>
                    <label for="<?php echo validation::safeOutputToScreen($k); ?>" class='group_<?php echo validation::safeOutputToScreen($paymentMethod['name_key']); ?> reward_group_field'>
                        <?php echo t('rewards_field_label_'.$k, UCWords(str_replace('_', ' ', $k)).':'); ?><br/>
                        <?php if($field[0] == 'text'): ?>
                            <input name="<?php echo validation::safeOutputToScreen($k); ?>" id="<?php echo validation::safeOutputToScreen($k); ?>" type="text"/>
                        <?php elseif($field[0] == 'textarea'): ?>
                            <textarea name="<?php echo validation::safeOutputToScreen($k); ?>" id="<?php echo validation::safeOutputToScreen($k); ?>"></textarea>
                        <?php endif; ?>
                    </label>
                    <div class="clear"><!-- --></div>
                    <?php
                }
            }
        }
        ?>
        
    </form>
</div>
</div>

<?php
// include footer
require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
?>
