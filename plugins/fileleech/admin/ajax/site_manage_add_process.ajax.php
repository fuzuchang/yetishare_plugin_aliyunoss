<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

$site_name = trim($_REQUEST['site_name']);
$site_url  = trim(strtolower($_REQUEST['site_url']));
$site_url  = str_replace(array('http://', 'https://'), '', $site_url);
if (substr($site_url, 0, 3) == 'www')
{
    $site_url = substr($site_url, 3, strlen($site_url));
}
$existing_site_id = null;
if (isset($_REQUEST['existing_site_id']))
{
    $existing_site_id = (int) $_REQUEST['existing_site_id'];
}
$min_account_type = trim(strtolower($_REQUEST['min_account_type']));

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

// validate submission
if (strlen($site_name) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("plugin_fileleech_enter_site_name", "Please enter the site name to use as a label.");
}
elseif (strlen($site_url) == 0)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("plugin_fileleech_enter_site_url", "Please enter the site url.");
}
elseif (_CONFIG_DEMO_MODE == true)
{
    $result['error'] = true;
    $result['msg']   = adminFunctions::t("no_changes_in_demo_mode");
}

if (strlen($result['msg']) == 0)
{
    $row = $db->getRow('SELECT id FROM plugin_fileleech_site WHERE site_name = ' . $db->quote($site_name) . ' AND id != ' . $existing_site_id);
    if (is_array($row))
    {
        $result['error'] = true;
        $result['msg']   = adminFunctions::t("plugin_fileleech_label_already_in_use", "A site with that name already exists, please choose another.");
    }
    else
    {
        if ($existing_site_id > 0)
        {
            // update the existing record
            $dbUpdate                   = new DBObject("plugin_fileleech_site", array("site_name", "site_url", "min_account_type"), 'id');
            $dbUpdate->site_name        = $site_name;
            $dbUpdate->site_url         = $site_url;
            $dbUpdate->min_account_type = $min_account_type;
            $dbUpdate->id               = $existing_site_id;
            $dbUpdate->update();

            $result['error'] = false;
            $result['msg']   = 'Site \'' . $site_name . '\' updated.';
        }
        else
        {
            // add the file server
            $dbInsert                   = new DBObject("plugin_fileleech_site", array("site_name", "site_url", "min_account_type"));
            $dbInsert->site_name        = $site_name;
            $dbInsert->site_url         = $site_url;
            $dbInsert->min_account_type = $min_account_type;
            if (!$dbInsert->insert())
            {
                $result['error'] = true;
                $result['msg']   = adminFunctions::t("plugin_fileleech_error_problem_record", "There was a problem adding the site, please try again.");
            }
            else
            {
                $result['error'] = false;
                $result['msg']   = 'Site \'' . $site_name . '\' has been added. Now set any paid account logins for the leeching to work.';
            }
        }
    }
}

echo json_encode($result);
exit;
