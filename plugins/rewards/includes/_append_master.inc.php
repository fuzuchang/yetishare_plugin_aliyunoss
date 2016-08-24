<?php

// see if we get passed an affiliate id
if (isset($_REQUEST['aff']))
{
    // lookup user id
    $db        = Database::getDatabase();
    $affUserId = $db->getValue('SELECT user_id FROM plugin_reward_affiliate_id WHERE affiliate_id=' . $db->quote(trim($_REQUEST['aff'])) . ' LIMIT 1');

    if ($affUserId)
    {
        // store it within the session
        $_SESSION['plugin_rewards_aff_id']      = trim($_REQUEST['aff']);
        $_SESSION['plugin_rewards_aff_user_id'] = (int) $affUserId;
    }
}
