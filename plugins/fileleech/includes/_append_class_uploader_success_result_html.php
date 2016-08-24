<?php

$fileUpload = $params['fileUpload'];
if (isset($fileUpload->requestUrl))
{
    // get database connection
    $db = Database::getDatabase();

    // get url sections
    $urlParts = parse_url($fileUpload->requestUrl);
    $siteName = strtolower($urlParts['host']);

    // check if we can leech the file
    $foundSiteDetails = null;
    $siteDetails      = $db->getRows('SELECT * FROM plugin_fileleech_site');
    foreach ($siteDetails AS $siteDetail)
    {
        if (strpos(strtolower($siteDetail['site_url']), $siteName) !== false)
        {
            $foundSiteDetails = $siteDetail;
        }
    }

    if ($foundSiteDetails !== null)
    {
        // log download for site usage restrictions
        $dbInsert = new DBObject("plugin_fileleech_download", array("site_id", "file_url", "filesize", "user_id", "user_ip_address", "date_download"));
        $dbInsert->site_id  = $foundSiteDetails['id'];
        $dbInsert->file_url = $fileUpload->requestUrl;
        $dbInsert->filesize = $fileUpload->size;
        $dbInsert->user_id  = NULL;
        $Auth               = Auth::getAuth();
        if ($Auth->loggedIn())
        {
            $dbInsert->user_id = (int) $Auth->id;
        }
        $dbInsert->user_ip_address = coreFunctions::getUsersIPAddress();
        $dbInsert->date_download = coreFunctions::sqlDateTime();
        $dbInsert->insert();
    }
}
