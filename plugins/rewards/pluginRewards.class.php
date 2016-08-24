<?php

class PluginRewards extends Plugin
{

    public $config   = null;
    public $data     = null;
    public $settings = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include_once('_plugin_config.inc.php');

        // load config into the object
        $this->config = $pluginConfig;
        $this->data = $db->getRow('SELECT * FROM plugin WHERE folder_name = ' . $db->quote($this->config['folder_name']) . ' LIMIT 1');
        if ($this->data)
        {
            $this->settings = json_decode($this->data['plugin_settings'], true);
        }
    }

    public function getPluginDetails()
    {
        return $this->config;
    }

    public function uninstall()
    {
        // setup database
        $db = Database::getDatabase();

        // remove plugin specific tables
        $sQL = 'DROP TABLE plugin_reward_affiliate_id';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_reward';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_reward_aggregated';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_reward_withdraw_request';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_reward_ppd_complete_download';
        $db->query($sQL);
        $sQL = 'DELETE FROM site_config WHERE config_key=\'next_check_for_rewards_aggregation\'';
        $db->query($sQL);
        $sQL = 'DELETE FROM site_config WHERE config_key=\'next_check_for_ppd_aggregation\'';
        $db->query($sQL);

        return parent::uninstall();
    }

    public function clearPendingRewards()
    {
        // setup database
        $db = Database::getDatabase();

        // PPS - update any rewards awaiting clearing
        $db->query("UPDATE plugin_reward SET status = 'cleared' WHERE status IN ('pending') AND UNIX_TIMESTAMP(reward_date) < " . strtotime('-' . (int) $this->settings['payment_lead_time'] . ' days'));
        
        // PPD
        $db->query("UPDATE plugin_reward_ppd_detail SET status = 'cleared' WHERE status IN ('pending') AND UNIX_TIMESTAMP(download_date) < " . strtotime('-' . (int) $this->settings['payment_lead_time'] . ' days'));
    }
    
    public function pruneData()
    {
        // setup database
        $db = Database::getDatabase();
        
        // remove everything older than 30 days old
        $db->query('DELETE FROM `plugin_reward_ppd_complete_download` WHERE date_added < DATE_SUB(NOW(), INTERVAL 30 DAY)');
    }

    public function aggregateRewards()
    {
        // setup database
        $db = Database::getDatabase();

        // make sure we should aggregate the data
        $nextCheckTimestamp = (int) SITE_CONFIG_NEXT_CHECK_FOR_REWARDS_AGGREGATION;
        if ($nextCheckTimestamp < time())
        {
            // loop months and collate all cleared data older than $this->settings['payment_lead_time'] days.
            $monthToUpdate      = strtotime('-' . (int) $this->settings['payment_lead_time'] . ' days');
            $monthToUpdate      = strtotime(date("Y-m-01 00:00:00", $monthToUpdate));
            $monthToUpdateEnd   = $monthToUpdate - 1;
            $monthToUpdateStart = strtotime(date("Y-m-01 00:00:00", $monthToUpdateEnd));
            
            // PPS
            $data               = $db->getRows("SELECT SUM(reward_amount) AS total, COUNT(id) AS total_cleared, reward_user_id FROM plugin_reward WHERE UNIX_TIMESTAMP(reward_date) BETWEEN " . $monthToUpdateStart . " AND " . $monthToUpdateEnd . " AND status='cleared' GROUP BY reward_user_id");
            if ($data)
            {
                foreach ($data AS $row)
                {
                    // make sure there's no already an entry for that period under the same user
                    $rs = $db->getValue('SELECT id FROM plugin_reward_aggregated WHERE reward_user_id=' . $row['reward_user_id'] . ' AND period=\'' . date("Y-m-d H:i:s", $monthToUpdateStart) . '\' AND reward_type = \'PPS\' LIMIT 1');
                    if (!$rs)
                    {
                        // add aggregated data to db
                        $monthName   = date('F Y', $monthToUpdateStart);
                        $description = 'Cleared PPS rewards for ' . $monthName . ' (' . $row['total_cleared'] . ' item'.($row['total_cleared']!=1?'s':'').')';

                        $dbInsert = new DBObject("plugin_reward_aggregated",
                                        array("reward_user_id", "period", "amount",
                                            "description", "aggregated_date", "status", "reward_type")
                        );
                        $dbInsert->reward_user_id = $row['reward_user_id'];
                        $dbInsert->period = date("Y-m-d H:i:s", $monthToUpdateStart);
                        $dbInsert->amount = $row['total'];
                        $dbInsert->description = $description;
                        $dbInsert->aggregated_date = date("Y-m-d H:i:s", time());
                        $dbInsert->status = 'available';
                        $dbInsert->reward_type = 'PPS';
                        $dbInsert->insert();
                    }
                }
            }
            
            // set next check to start of next month
            $nextCheckNew = strtotime(date("Y-m-05 00:00:00", strtotime("+1 months")));
            $db->query("UPDATE site_config SET config_value='".$nextCheckNew."' WHERE config_key='next_check_for_rewards_aggregation' LIMIT 1");
        }
        
        
        $nextCheckTimestamp = (int) SITE_CONFIG_NEXT_CHECK_FOR_PPD_AGGREGATION;
        if ($nextCheckTimestamp < time())
        {
            // PPD
            $data               = $db->getRows("SELECT SUM(reward_amount) AS total, COUNT(id) AS total_cleared, reward_user_id FROM plugin_reward_ppd_detail WHERE UNIX_TIMESTAMP(download_date) < NOW() AND (status='cleared' OR status='pending') GROUP BY reward_user_id");
            if ($data)
            {
                foreach ($data AS $row)
                {
                    $periodDateTime = strtotime(date("Y-m-d 00:00:00"));

                    // add aggregated data to db
                    $description = 'Cleared PPD rewards for ' . coreFunctions::formatDate($periodDateTime, SITE_CONFIG_DATE_FORMAT) . ' (' . $row['total_cleared'] . ' item'.($row['total_cleared']!=1?'s':'').')';

                    $dbInsert = new DBObject("plugin_reward_aggregated",
                                    array("reward_user_id", "period", "amount",
                                        "description", "aggregated_date", "status", "reward_type")
                    );
                    $dbInsert->reward_user_id = $row['reward_user_id'];
                    $dbInsert->period = date("Y-m-d H:i:s", $periodDateTime);
                    $dbInsert->amount = $row['total'];
                    $dbInsert->description = $description;
                    $dbInsert->aggregated_date = date("Y-m-d H:i:s", time());
                    $dbInsert->status = 'available';
                    $dbInsert->reward_type = 'PPD';
                    $dbInsert->insert();

                    // update each item
                    $db->query("UPDATE plugin_reward_ppd_detail SET status='aggregated' WHERE UNIX_TIMESTAMP(download_date) < NOW() AND (status='cleared' OR status='pending') AND reward_user_id = ".$row['reward_user_id']);
                }
            }

            // set next check to start of next month
            $nextCheckNew = strtotime(date("Y-m-d 00:00:00", strtotime("+1 day")));
            $db->query("UPDATE site_config SET config_value='".$nextCheckNew."' WHERE config_key='next_check_for_ppd_aggregation' LIMIT 1");
        }
    }

}
