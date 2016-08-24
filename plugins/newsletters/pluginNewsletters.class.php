<?php

class PluginNewsletters extends Plugin
{

    public $config   = null;
    public $data     = null;
    public $settings = null;

    public function __construct()
    {
        // setup database
        $db = Database::getDatabase();

        // get the plugin config
        include('_plugin_config.inc.php');

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
        $sQL = 'DROP TABLE plugin_newsletter';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_newsletter_sent';
        $db->query($sQL);
        $sQL = 'DROP TABLE plugin_newsletter_unsubscribe';
        $db->query($sQL);

        return parent::uninstall();
    }
    
    public function getRecipients($userGroup, $includeUnsubs = false)
    {
        // setup database
        $db = Database::getDatabase();
        
        $clause = '';
        switch($userGroup)
        {
			// string versions kept for older data
            case 'free only':
                $clause = 'level_id = 1';
                break;
            case 'premium only':
                $clause = 'level_id = 2';
                break;
            case 'moderator only':
                $clause = 'level_id = 10';
                break;
            case 'admin only':
                $clause = 'level_id = 20';
                break;
			case is_numeric($userGroup):
				if((int)$userGroup > 0)
				{
					$clause = 'level_id = '.(int)$userGroup;
				}
				else
				{
					// all registered
					$clause = '1=1';
				}
				break;
            default:
                // all registered
                $clause = '1=1';
                break;
        }

        $sQL = 'SELECT * FROM users WHERE status=\'active\' AND '.$clause;
        if($includeUnsubs == false)
        {
            $sQL .= ' AND id NOT IN (SELECT user_id FROM plugin_newsletter_unsubscribe)';
        }
        
        return $db->getRows($sQL);
    }
    
    public function sendNewsletter($subject, $htmlContent, $toEmail, $fromEmail)
    {
        return coreFunctions::sendHtmlEmail($toEmail, $subject, $htmlContent, $fromEmail);
    }
}
