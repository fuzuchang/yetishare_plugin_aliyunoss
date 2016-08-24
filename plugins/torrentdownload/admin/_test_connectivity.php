<?php

// includes and security
include_once ('../../../core/includes/master.inc.php');
include_once (DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');

// validate submission
if (_CONFIG_DEMO_MODE == true)
{
    die(adminFunctions::t("no_changes_in_demo_mode"));
}

// load plugin details
$plugin = $db->getRow("SELECT * FROM plugin WHERE folder_name = 'torrentdownload' LIMIT 1");

// plugin object for functions
$pluginObj = pluginHelper::getInstance('torrentdownload');

// prepare variables
$plugin_enabled = (int)$plugin['plugin_enabled'];
$utorrent_host = _CONFIG_SITE_HOST_URL;

// remove port from host, if exists
$url_parts = parse_url($utorrent_host);
if (!isset($url_parts['host']))
{
    $utorrent_host = $url_parts['path'];
}
else
{
    $utorrent_host = $url_parts['host'];
}

$utorrent_port = '8080';
$utorrent_username = 'admin';
$utorrent_password = '';

// load existing settings
if (strlen($plugin['plugin_settings']))
{
    $plugin_settings = json_decode($plugin['plugin_settings'], true);
    if ($plugin_settings)
    {
        $utorrent_host = $plugin_settings['utorrent_host'];
        $utorrent_port = $plugin_settings['utorrent_port'];
        $utorrent_username = $plugin_settings['utorrent_username'];
        $utorrent_password = $plugin_settings['utorrent_password'];
    }
}

// prepare host
$uTorrentHost = $utorrent_host . (strlen($utorrent_port) ? (':' . $utorrent_port) :
    '');
$Crl = curl_init();

// token url for access
echo "***************************************************************************************************<br/>";
$tokenUrl = 'http://' . $utorrent_username . ":" . $utorrent_password . "@" . $uTorrentHost .
    '/gui/token.html';
echo "REQUEST: Inital Authentication Request.<br/>";
echo $tokenUrl;
echo "<br/>";
$authToken = strip_tags(SendRequest($tokenUrl, $utorrent_username, $utorrent_password,
    $Crl));
echo "RESPONSE: " . $authToken . "<br/><br/>";
echo "***************************************************************************************************<br/>";

// list torrents
echo "***************************************************************************************************<br/>";
$listUrl = 'http://' . $uTorrentHost . '/gui/?list=1&token=' . $authToken;
$JsonResponse = SendRequest($listUrl, $utorrent_username, $utorrent_password, $Crl);
$Torrents = json_decode($JsonResponse, true);
$list = $Torrents['torrents'];
echo "REQUEST: List torrents.<br/>";
echo $listUrl;
echo "<br/>";
echo "RESPONSE: <pre>" . print_r($list, true) . "</pre><br/><br/>";
echo "***************************************************************************************************<br/>";

// local functions
function SendRequest($Url, $utorrent_username, $utorrent_password, $Crl)
{
    curl_setopt($Crl, CURLOPT_URL, $Url);
    curl_setopt($Crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($Crl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($Crl, CURLOPT_USERPWD, $utorrent_username . ':' . $utorrent_password);
    curl_setopt($Crl, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($Crl, CURLOPT_COOKIEFILE, 'cookie.txt');

    $Ret = curl_exec($Crl);

    return $Ret;
}
