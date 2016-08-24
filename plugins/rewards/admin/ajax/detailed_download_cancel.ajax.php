<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$gRewardId     = (int) $_REQUEST['gRewardId'];

// prepare result
$result = array();
$result['error'] = false;
$result['msg']   = '';

if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
else
{
    $db->query('UPDATE plugin_reward_ppd_detail SET status = \'cancelled\' WHERE id = :id', array('id'         => $gRewardId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'Download reward cancelled.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not cancel the download reward.';
    }
}

echo json_encode($result);
exit;
