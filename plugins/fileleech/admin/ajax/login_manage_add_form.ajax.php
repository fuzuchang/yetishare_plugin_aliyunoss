<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// prepare variables
$site_name           = '';
$site_url            = '';
$login_url           = '';
$login_referrer      = '';
$login_form_elements = '';
$supports_http_auth  = '1';
$gEditSiteId = (int) $_REQUEST['gEditSiteId'];

$sQL         = "SELECT * FROM plugin_fileleech_site WHERE id=" . $gEditSiteId;
$siteDetails = $db->getRow($sQL);
if ($siteDetails)
{
    $site_name           = $siteDetails['site_name'];
    $site_url            = $siteDetails['site_url'];
    $min_account_type            = $siteDetails['min_account_type'];
}

// load existing logins
$loginDetailsStr = '';
$sQL         = "SELECT username, password FROM plugin_fileleech_access_detail WHERE site_id=" . $gEditSiteId;
$loginDetails = $db->getRows($sQL);
if ($loginDetails)
{
    foreach($loginDetails AS $loginDetail)
    {
        $loginDetailsStr .= $loginDetail['username'].'|'.$loginDetail['password']."\n";
    }
}

if (_CONFIG_DEMO_MODE == true)
{
    $loginDetailsStr = 'hidden_in|demo_mode';
}

 // prepare min account type details
$minAccountType = 'no account';
if($min_account_type == 'free')
{
    $minAccountType = 'at least a free account';
}
elseif($min_account_type == 'paid')
{
    $minAccountType = 'a paid account';
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] = '<p style="padding-bottom: 4px;">Use the form below to manage premium account access details for '.adminFunctions::makeSafe($site_name).'. These will be used randomly on each download request. Note: You need '.$minAccountType.' on this site for leeching to work.</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addSiteLoginForm">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("plugin_fileleech_login_details", "Login Details")) . ':</label>
                        <div class="input">
                            <textarea name="login_details" id="login_details" class="xxlarge">' . adminFunctions::makeSafe($loginDetailsStr) . '</textarea><br/><br/>
                            Split login details on one for each line, separating the username and password<br/>with a pipe. i.e.<br/><br/>username1|password1<br/>username2|password2<br/>username3|password3
                        </div>
                    </div>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
