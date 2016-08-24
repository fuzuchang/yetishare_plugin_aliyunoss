<?php
// setup includes
require_once('../../../core/includes/master.inc.php');

// setup page
define("PAGE_NAME", t("rewards", "Rewards"));
define("PAGE_DESCRIPTION", t("rewards_meta_description", "Rewards"));
define("PAGE_KEYWORDS", t("rewards_meta_keywords", "earn, money, rewards, cash, sales, affiliate, file, hosting, site"));
define('CURRENT_PAGE_KEY', 'rewards');
define("MONTHLY_UPGRADE_EXAMPLE", 20);

// load reward details
$rewardsConfig   = pluginHelper::pluginSpecificConfiguration('rewards');
$rewardsSettings = json_decode($rewardsConfig['data']['plugin_settings'], true);

// include header
require_once(SITE_TEMPLATES_PATH . '/partial/_header.inc.php');
?>

<div class="contentPageWrapper">

    <!-- main section -->
    <div class="pageSectionMain ui-corner-all">
        <div class="pageSectionMainInternal">
            <?php
            if((int)$rewardsSettings['pps_enabled'] == 1)
            {
            ?>
            <div id="pageHeader">
                <h2><?php echo t("rewards", "Rewards"); ?></h2>
            </div>
            <div class="faq">
                <?php
                $content = '<p>
                    Earn [[[PERCENTAGE]]]% of each sale you refer to this site! You can earn money from upgrading users in 2 ways:
                </p>
                <ol class="rewardsTextList">
                    <li>Using your unique affiliate id, simply link to the site via your existing website. You\'ll earn [[[PERCENTAGE]]]% for any users which register for an account and subsequently upgrade.</li>
                    <li>From users which upgrade to download files within your account.</li>
                </ol>
                <p><strong>How much can you earn?</strong></p>
                
                <p>Based on [[[MONTHLY_UPGRADE_EXAMPLE]]] upgrades and each upgrade costing [[[SITE_CONFIG_COST_CURRENCY_SYMBOL]]][[[SITE_CONFIG_COST_FOR_30_DAYS_PREMIUM]]] per month, you could earn the following:</p>';
                $replacements = array();
                $replacements['PERCENTAGE'] = $rewardsSettings['user_percentage'];
                $replacements['WEB_ROOT'] = WEB_ROOT;
                $replacements['AFFILIATE_KEY'] = $affKey;
                $replacements['MONTHLY_UPGRADE_EXAMPLE'] = MONTHLY_UPGRADE_EXAMPLE;
                $replacements['SITE_CONFIG_COST_CURRENCY_SYMBOL'] = SITE_CONFIG_COST_CURRENCY_SYMBOL;
                $replacements['SITE_CONFIG_COST_FOR_30_DAYS_PREMIUM'] = SITE_CONFIG_COST_FOR_30_DAYS_PREMIUM;
                
                echo t('rewards_pps_info_text_logged_out', $content, $replacements);
                ?>
                <table width="100%" class="rewardsTable table table-bordered table-striped">
                    <tr>
                        <td style="width: 25%;"><?php echo t('rewards_month', 'Month:'); ?></td>
                        <td style="width: 25%; text-align: center;" class="mobileHide"><?php echo t('rewards_referrals', 'Referrals:'); ?></td>
                        <td style="width: 30%; text-align: center;"><?php echo t('rewards_total_referrals', 'Total Referrals: (inc renewals)'); ?></td>
                        <td><?php echo t('rewards_monthly_total', 'Monthly Total:'); ?></td>
                    </tr>
                    <?php
                    $overallTotal = 0;
                    for ($i            = 1; $i <= 10; $i++)
                    {
                        $totalRef      = MONTHLY_UPGRADE_EXAMPLE * $i;
                        $totalEarnings = (($totalRef * SITE_CONFIG_COST_FOR_30_DAYS_PREMIUM) / 100) * $rewardsSettings['user_percentage'];
                        ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime('+' . $i . ' month')); ?></td>
                            <td style="text-align: center;" class="mobileHide"><?php echo MONTHLY_UPGRADE_EXAMPLE; ?></td>
                            <td style="text-align: center;"><?php echo $totalRef; ?></td>
                            <td style="text-align: right;"><?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo number_format($totalEarnings, 2); ?></td>
                        </tr>
                        <?php
                        $overallTotal  = $overallTotal + $totalEarnings;
                    }
                    ?>
                    <tr>
                        <td></td>
                        <td class="mobileHide"></td>
                        <td style="text-align: right;"><?php echo t('rewards_total', 'Total:'); ?></td>
                        <td style="text-align: right;"><?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo number_format($overallTotal, 2); ?></td>
                    </tr>
                </table>
            </div>
            <div class="clear" style='padding-bottom: 16px;'><!-- --></div>
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
            
            <?php
            $content = '<p class="rewardsTopPadding"><strong>How can I claim my rewards?</strong></p>
            <p>
                Any rewards will take [[[PAYMENT_LEAD_TIME]]] days to clear within your account. Once your cleared rewards are over [[[SITE_CONFIG_COST_CURRENCY_SYMBOL]]][[[PAYMENT_THRESHOLD]]] you can request a payment via your account. Payments are made on the [[[PAYMENT_DATE]]] of every month via PayPal.
            </p>
            <p class="rewardsTopPadding"><strong>How do I get started?</strong></p>
            <p>
                Signup for an account on our <a href="[[[WEB_ROOT]]]/register.[[[SITE_CONFIG_PAGE_EXTENSION]]]">registration page</a>.
                [[[ADDITIONAL_TEXT]]]
            </p>';
            
            $additionalText = '';
            if((int)$rewardsSettings['pps_enabled'] == 1)
            {
                $additionalTextStr = '
                Once you\'ve completed your registration you\'ll find your affiliate id in the \'rewards\' section of your account. Begin by uploading and sharing your files or by linking from your existing site using your affiliate id like this:
                <ul>
                    <li><a href="[[[WEB_ROOT]]]/?aff=[AFFILIATE_ID]">[[[WEB_ROOT]]]/?aff=[AFFILIATE_ID]</a></li>
                </ul>';
                $additionalText .= t('rewards_pps_logged_out_faq_additional_text', $additionalTextStr, array('WEB_ROOT'=>WEB_ROOT));
            }
            elseif((int)$rewardsSettings['ppd_enabled'] == 1)
            {
                $additionalTextStr = '
                Once you\'ve completed your registration just start uploading your files and sharing the download links with your family and friends.<br/><br/>You\'ll be paid for any downloads outside of your account. Note: We only count completed downloads and downloads from unique IP addresses.
                ';
                $additionalText .= t('rewards_ppd_logged_out_faq_additional_text', $additionalTextStr, array('WEB_ROOT'=>WEB_ROOT));
            }
                
            $replacements = array();
            $replacements['PAYMENT_LEAD_TIME'] = $rewardsSettings['payment_lead_time'];
            $replacements['SITE_CONFIG_COST_CURRENCY_SYMBOL'] = SITE_CONFIG_COST_CURRENCY_SYMBOL;
            $replacements['PAYMENT_THRESHOLD'] = number_format($rewardsSettings['payment_threshold'], 2);
            $replacements['PAYMENT_DATE'] = date('jS', strtotime(date('Y-m-').$rewardsSettings['payment_date']));
            $replacements['WEB_ROOT'] = WEB_ROOT;
            $replacements['SITE_CONFIG_PAGE_EXTENSION'] = SITE_CONFIG_PAGE_EXTENSION;
            $replacements['ADDITIONAL_TEXT'] = $additionalText;
            
            echo t('rewards_logged_out_faq', $content, $replacements);
            ?>
            
        </div>
    </div>
</div>

<?php
// include footer
require_once(SITE_TEMPLATES_PATH . '/partial/_footer.inc.php');
?>
