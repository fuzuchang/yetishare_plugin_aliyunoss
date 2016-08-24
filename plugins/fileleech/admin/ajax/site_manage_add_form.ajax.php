<?php

// includes and security
include_once('../../../../core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// prepare variables
$site_name           = '';
$site_url            = '';
$min_account_type            = 'paid';

// is this an edit?
$gEditSiteId = null;
$formType = 'add the';
$extraDetail = 'Note: The site must be supported by plowshare. You can <a href="https://code.google.com/p/plowshare/wiki/Readme4#Introduction" target="_blank">check here</a> for supported download sites. Also note that any which require captcha verification on logged in users can not be supported.';
if (isset($_REQUEST['gEditSiteId']))
{
    $gEditSiteId = (int) $_REQUEST['gEditSiteId'];
    if ($gEditSiteId)
    {
        $sQL         = "SELECT * FROM plugin_fileleech_site WHERE id=" . $gEditSiteId;
        $siteDetails = $db->getRow($sQL);
        if ($siteDetails)
        {
            $site_name           = $siteDetails['site_name'];
            $site_url            = $siteDetails['site_url'];
            $min_account_type            = $siteDetails['min_account_type'];
            
            $formType = 'update';
            $extraDetail = '';
        }
    }
}

// prepare result
$result          = array();
$result['error'] = false;
$result['msg']   = '';

$result['html'] = '<p style="padding-bottom: 4px;">Use the form below to ' . $formType . ' site details. '.$extraDetail.'</p>';
$result['html'] .= '<span id="popupMessageContainer"></span>';
$result['html'] .= '<form id="addSiteForm">';

$result['html'] .= '<div class="form">';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("plugin_fileleech_site_name", "Site Name")) . ':</label>
                        <div class="input">
                            <input name="site_name" id="site_name" type="text" value="' . adminFunctions::makeSafe($site_name) . '" class="large"/>
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix">
                        <label>' . UCWords(adminFunctions::t("plugin_fileleech_site_url", "Site Url")) . ':</label>
                        <div class="input">
                            <input name="site_url" id="site_url" type="text" value="' . adminFunctions::makeSafe($site_url) . '" class="large" placeholder="downloadsite.com"/><br/><br/>
                            Main domain, in the format downloadsite.com. Exclude the http, www, forward slashes<br/>and sub-domains.
                        </div>
                    </div>';
$result['html'] .= '<div class="clearfix alt-highlight">
                        <label>' . UCWords(adminFunctions::t("plugin_fileleech_min_account_type", "Minimum Required Account Type")) . ':</label>
                        <div class="input">
                            <select name="min_account_type" id="min_account_type">';
                            $options = array('none'=>'None', 'free'=>'Free', 'paid'=>'Paid');
                            foreach ($options AS $k=>$option)
                            {
                                $result['html'] .= '        <option value="' . $k . '"';
                                if ($min_account_type == $k)
                                {
                                    $result['html'] .= '        SELECTED';
                                }
                                $result['html'] .= '        >' . $option . '</option>';
                            }
                            $result['html'] .= '        </select><br/><br/>
                            If this site needs a free or paid account to enable leeching, please set here. This should match<br/>the information from <a href="https://code.google.com/p/plowshare/wiki/Readme4#Introduction" target="_blank">plowshare</a>. Once a site is configured, you can add logins.
                        </div>
                    </div>';
$result['html'] .= '</form>';

echo json_encode($result);
exit;
