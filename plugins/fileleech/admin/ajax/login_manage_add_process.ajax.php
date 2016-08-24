<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$existing_site_id = (int) $_REQUEST['existing_site_id'];
$login_details    = trim($_REQUEST['login_details']);
$login_details    = str_replace(array("\n\r", "\r", "\n\n"), "\n", $login_details);

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}
elseif (strlen($login_details) > 0)
{
    // make sure we have the correct format
    $rows = explode("\n", $login_details);
    foreach ($rows AS $row)
    {
        if (strlen($row) == 0)
        {
            continue;
        }

        // check items
        $items = explode('|', $row);
        if (COUNT($items) != 2)
        {
            $result['error'] = true;
            $result['msg']   = adminFunctions::t("plugin_fileleech_logins_incorrect_format", "Logins are in the wrong format, please check.");
        }
    }
}

if (strlen($result['msg']) == 0)
{
    // remove existing logins
    $db->query('DELETE FROM plugin_fileleech_access_detail WHERE site_id = ' . $existing_site_id);

    // add logins
    $rows = explode("\n", $login_details);
    foreach ($rows AS $row)
    {
        if (strlen($row) == 0)
        {
            continue;
        }

        // check items
        $items = explode('|', $row);
        if (COUNT($items) == 2)
        {
            // add the file server
            $dbInsert           = new DBObject("plugin_fileleech_access_detail", array("site_id", "username", "password"));
            $dbInsert->site_id  = $existing_site_id;
            $dbInsert->username = trim($items[0]);
            $dbInsert->password = trim($items[1]);
            $dbInsert->insert();
        }
    }
}

echo json_encode($result);
exit;
