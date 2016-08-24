<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// validation
$shortUrl = trim($_REQUEST['s']);
$f        = 0;
if (isset($_REQUEST['f']))
{
    $f = trim($_REQUEST['f']);
}

// try to load the file object
$file = null;
if ($shortUrl)
{
    $file = file::loadByShortUrl($shortUrl);
}

// load file details
if (!$file)
{
    // no file found
    coreFunctions::output404();
}

// file must be active
if ($file->statusId != 1)
{
    coreFunctions::output404();
}

// file must not have a password
if (strlen($file->accessPassword) > 0)
{
    coreFunctions::redirect($file->getFullShortUrl());
}

// create token
$downloadToken = $file->generateDirectDownloadToken(0, 0);

// download file
$rs = $file->download(true, true, $downloadToken, false);
if (!$rs)
{
    $errorMsg = t("error_can_not_locate_file", "File can not be located, please try again later.");
    if ($file->errorMsg != null)
    {
        $errorMsg = t("file_download_error", "Error").': ' . $file->errorMsg;
    }
    coreFunctions::redirect(coreFunctions::getCoreSitePath() . "/error." . SITE_CONFIG_PAGE_EXTENSION . "?e=" . urlencode($errorMsg));
}