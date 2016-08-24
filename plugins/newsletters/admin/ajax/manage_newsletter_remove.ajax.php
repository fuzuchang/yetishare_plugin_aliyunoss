<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$gRemoveNewsletterId = (int) $_REQUEST['gRemoveNewsletterId'];

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
    $db->query('DELETE FROM plugin_newsletter WHERE id = :id', array('id' => $gRemoveNewsletterId));
    if ($db->affectedRows() == 1)
    {
        $result['error'] = false;
        $result['msg']   = 'Newsletter removed.';
    }
    else
    {
        $result['error'] = true;
        $result['msg']   = 'Could not remove newsletter, please try again later.';
    }
}

echo json_encode($result);
exit;
