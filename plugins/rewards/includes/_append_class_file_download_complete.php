<?php

// load reward details
$rewardsConfig   = pluginHelper::pluginSpecificConfiguration('rewards');
$rewardsSettings = json_decode($rewardsConfig['data']['plugin_settings'], true);

$file            = $params['file'];
$fileOwnerUserId = $params['fileOwnerUserId'];
$userLevelId     = $params['userLevelId'];
$origin          = isset($params['origin']) ? $params['origin'] : 'file.class.php';

// logging
log::setContext('plugin_rewards_ppd_log');
log::breakInLogFile();
log::info('Request received to log PPD. (origin: ' . $origin . ', use_download_complete_callback = ' . $rewardsSettings['use_download_complete_callback'] . ') FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' FileOwnerId: '.$fileOwnerUserId);

// check to make sure we're invoking this in the correct place
$continue = true;
if (($origin == 'file.class.php') && ((int) $rewardsSettings['use_download_complete_callback'] == 1))
{
    // only for nginx
    if (fileServer::nginxXAccelRedirectEnabled())
    {
        // log
        log::info('Skipping PPD log from origin \'' . $origin . '\' as use_download_complete_callback is enabled. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
        $continue = false;
    }
}
elseif (($origin == '_log_download.php') && ((int) $rewardsSettings['use_download_complete_callback'] == 0))
{
    // log
    log::info('Skipping PPD log from origin \'' . $origin . '\' as use_download_complete_callback is disabled. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
    $continue = false;
}

// make sure the file is associated with a user
if (((int) $file->userId > 0) && ($continue == true))
{
    // log download
    if (($fileOwnerUserId == $file->userId) || ($userLevelId == 20))
    {
        // ignore - this was triggered by an admin user or file owner
        log::info('Ignoring, this was triggered by an admin user or file owner. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
    }
    else
    {
        // check filesize
        $count         = false;
        $countFilesize = (int) $rewardsSettings['ppd_min_file_size'];
        if ($countFilesize == 0)
        {
            $count = true;
        }
        else
        {
            // check download size counts
            if (($countFilesize * 1024 * 1024) <= $file->fileSize)
            {
                $count = true;
            }
            else
            {
                // log
                log::info('Ignoring, size is less than permitted. Permitted: ' . ($countFilesize * 1024 * 1024) . '. Filesize: ' . $file->fileSize.' FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
            }
        }

        // log download
        if ($count == true)
        {
            // log
            $db = Database::getDatabase();

            // check whether the user has already downloaded today
            $usersIp = Stats::getIP();
            if (isset($params['ipOverride']))
            {
                $usersIp = $params['ipOverride'];
            }

            $sql = "SELECT * FROM plugin_reward_ppd_detail WHERE download_ip = " . $db->quote($usersIp) . " AND file_id = " . $file->id . " AND DATE(download_date) = " . $db->quote(date('Y-m-d'));
            $row = $db->getRows($sql);
            if (COUNT($row) == 0)
            {
                // log
                log::info('User IP: ' . $usersIp . '. Nothing found for this IP and file in the last 24 hours. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);

                // lookup country group
                $countryGroupId = null;
                $rewardAmount   = false;
                $country        = Stats::getCountry($usersIp);

                // log
                log::info('User country: ' . $country.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);

                $groupCountry = $db->getRow("SELECT id, group_id FROM plugin_reward_ppd_group_country WHERE country_code = " . $db->quote($country) . " LIMIT 1");
                if ($groupCountry)
                {
                    $countryGroupId = $groupCountry['group_id'];
                    
                    // log
                    log::info('Found country group id: ' . $countryGroupId.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);

                    // lookup reward amount
                    $rewardAmount = $db->getValue('SELECT plugin_reward_ppd_group_rate.payout_rate FROM plugin_reward_ppd_group_rate LEFT JOIN plugin_reward_ppd_range ON plugin_reward_ppd_group_rate.range_id = plugin_reward_ppd_range.id WHERE plugin_reward_ppd_range.from_filesize <= ' . $db->escape($file->fileSize) . ' AND plugin_reward_ppd_range.to_filesize > ' . $db->escape($file->fileSize) . ' AND plugin_reward_ppd_group_rate.group_id=' . (int) $countryGroupId . ' LIMIT 1');
                    if (($rewardAmount === false) || (strlen($rewardAmount) == 0))
                    {
                        // try upper end
                        $rewardAmount = $db->getValue('SELECT plugin_reward_ppd_group_rate.payout_rate FROM plugin_reward_ppd_group_rate LEFT JOIN plugin_reward_ppd_range ON plugin_reward_ppd_group_rate.range_id = plugin_reward_ppd_range.id WHERE plugin_reward_ppd_range.from_filesize <= ' . $db->escape($file->fileSize) . ' AND plugin_reward_ppd_range.to_filesize IS NULL AND plugin_reward_ppd_group_rate.group_id=' . (int) $countryGroupId . ' LIMIT 1');
                        if (($rewardAmount !== false) || (strlen($rewardAmount) > 0))
                        {
                            // log
                            log::info('Failed finding reward amount based on country group and filesize boundaries but found at upper end: CountryGroupId: ' . $countryGroupId.'. Reward: '.$rewardAmount.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
                        }
                    }
                    else
                    {
                        // log
                        log::info('Found reward amount based on country group id and filesize boundaries. Filesize: '.$file->fileSize.'. CountryGroupId: ' . $countryGroupId.'. Reward: '.$rewardAmount.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
                    }
                }

                if (($rewardAmount === false) || (strlen($rewardAmount) == 0))
                {
                    // log
                    log::info('Payment group not found, using highest group id and lowest payment rate. Country: ' . $country . '. CountryGroupId: '.$countryGroupId.'. Filesize: ' . $file->fileSize.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);

                    // get fall back group
                    $countryGroupId = $db->getValue('SELECT id FROM plugin_reward_ppd_group ORDER BY id DESC LIMIT 1');

                    // load the lowest outpayment group and use this
                    $rewardDefault = $db->getRow('SELECT payout_rate FROM plugin_reward_ppd_group_rate WHERE group_id = ' . (int) $countryGroupId . ' ORDER BY payout_rate ASC LIMIT 1');
                    if ($rewardDefault)
                    {
                        $rewardAmount = $rewardDefault['payout_rate'];
                        
                        // log
                        log::info('Final fallback for rate using lowest rate of '.$rewardAmount.'. CountryGroupId: ' . $countryGroupId.'. Reward: '.$rewardAmount.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
                    }
                    else
                    {
                        // log
                        log::info('Failed fallback, unable to find any rate. CountryGroupId: ' . $countryGroupId.'. Reward: '.$rewardAmount.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
                    }
                }

                // break download $rewardAmount into thousands
                $rewardAmount = $rewardAmount / 10000;
                $status       = 'pending';
                
                // log
                log::info('Reward / 1000 = '.$rewardAmount.'. CountryGroupId: ' . $countryGroupId.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);

                // make sure user is within the limits
                $dailyIpLimit = floatval($rewardsSettings['ppd_max_by_ip']);
                if ($dailyIpLimit > 0)
                {
                    // get total downloaded already today by this IP
                    $total = $db->getValue('SELECT SUM(reward_amount) AS total FROM plugin_reward_ppd_detail WHERE download_ip=' . $db->quote($usersIp) . ' AND download_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
                    if ($total >= $dailyIpLimit)
                    {
                        $rewardAmount = 0;
                        $status       = 'ip_limit_reached';
                    }
                }

                // make sure user is within the limits
                $dailyFileLimit = floatval($rewardsSettings['ppd_max_by_file']);
                if ($dailyFileLimit > 0)
                {
                    // get total downloaded already today by this IP
                    $total = $db->getValue('SELECT SUM(reward_amount) AS total FROM plugin_reward_ppd_detail WHERE file_id=' . $db->escape($file->id) . ' AND download_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
                    if ($total >= $dailyFileLimit)
                    {
                        $rewardAmount = 0;
                        $status       = 'file_limit_reached';
                    }
                }

                // make sure user is within the limits
                $dailyUserLimit = floatval($rewardsSettings['ppd_max_by_user']);
                if ($dailyUserLimit > 0)
                {
                    // get total downloaded already today by this IP
                    $total = $db->getValue('SELECT SUM(reward_amount) AS total FROM plugin_reward_ppd_detail WHERE reward_user_id=' . $db->escape($file->userId) . ' AND download_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)');
                    if ($total >= $dailyUserLimit)
                    {
                        $rewardAmount = 0;
                        $status       = 'user_limit_reached';
                    }
                }
                
                // log
                log::info('Completed checks for limitations. Status: '.$status.' RewardAmount: '.$rewardAmount.'. ' . $countryGroupId.'. FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);

                // add entry
                $sql  = "INSERT INTO plugin_reward_ppd_detail (reward_user_id, download_ip, file_id, download_country_group_id, download_date, reward_amount, status)
                    VALUES (:reward_user_id, :download_ip, :file_id, :download_country_group_id, NOW(), :reward_amount, :status)";
                $vals = array('reward_user_id'            => $file->userId,
                    'download_ip'               => $usersIp,
                    'file_id'                   => $file->id,
                    'download_country_group_id' => $countryGroupId,
                    'reward_amount'             => $rewardAmount,
                    'status'                    => $status);
                $db->query($sql, $vals);

                // log
                log::info('PPD logged (' . $status . ' @ ' . $rewardAmount . '. Country: ' . $country . '). FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.' LoggedInUserId: '.$fileOwnerUserId);
            }
        }
    }
}
else
{
    // log
    log::info('PPD NOT logged as file owned by a user outside of the PPD scheme (i.e. free) (' . $status . ' @ ' . $rewardAmount . '. Country: ' . $country . '). FileId: #'.$file->id.', Name: '.$file->originalFilename.', UserLevel: '.$userLevelId.', Origin: '.$origin.', FileUserId: '.$file->userId.', LoggedInUserId: '.$fileOwnerUserId);
}