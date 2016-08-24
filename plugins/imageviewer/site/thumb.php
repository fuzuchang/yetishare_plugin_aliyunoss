<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// constants
define('SUCCESS_THUMB_WIDTH', '200');
define('SUCCESS_THUMB_HEIGHT', '200');

// load reward details
$pluginObj      = pluginHelper::getInstance('imageviewer');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// do not allow if embed options are disabled
if ((int) $pluginSettings['show_embedding'] == 0)
{
    // embedding disabled
    coreFunctions::output404();
}

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

// cache paths
if ((int) $pluginSettings['caching'] == 1)
{
    $cacheFilePath = '../../../core/cache/plugins/imageviewer/';
    $cacheFilePath .= $file->id . '/';
    if (!file_exists($cacheFilePath))
    {
        mkdir($cacheFilePath, 0777, true);
    }
    $cacheFileName = (int) $pluginSettings['thumb_size_w'] . 'x' . (int) $pluginSettings['thumb_size_h'] . '_' . $pluginSettings['thumb_resize_method'] . '.jpg';
    if ($f == 1)
    {
        $cacheFileName = (int) SUCCESS_THUMB_WIDTH . 'x' . (int) SUCCESS_THUMB_HEIGHT . '_' . $pluginSettings['thumb_resize_method'] . '.jpg';
    }
    $fullCachePath = $cacheFilePath . $cacheFileName;
}

// check for cache
if (((int) $pluginSettings['caching'] == 0) || (!file_exists($fullCachePath)))
{
    // create embed token
    $embedToken           = md5(microtime());
    $dbInsert             = new DBObject("plugin_imageviewer_embed_token", array("token", "date_added", "file_id"));
    $dbInsert->token      = $embedToken;
    $dbInsert->date_added = coreFunctions::sqlDateTime();
    $dbInsert->file_id    = $file->id;
    $dbInsert->insert();

    // get image contents
    header('Content-Type: image/jpeg');
    $contents = $file->download(false);

    // load into memory
    $im = imagecreatefromstring($contents);
    if ($im === false)
    {
        // failed reading image
        coreFunctions::output404();
    }

    // get image size
    $imageWidth  = imagesx($im);
    $imageHeight = imagesy($im);

    $w = (int) $pluginSettings['thumb_size_w'];
    if ($f == 1)
    {
        $w = (int) SUCCESS_THUMB_WIDTH;
    }
    $newwidth  = $w;
    $newheight = ($imageHeight / $imageWidth) * $newwidth;
    if ($newwidth > $imageWidth)
    {
        $newwidth = $imageWidth;
    }
    if ($newheight > $imageHeight)
    {
        $newheight = $imageHeight;
    }
    $tmp  = imagecreatetruecolor($newwidth, $newheight);
    $tmpH = imagesy($tmp);

    // set background to white for transparent images
    $back = imagecolorallocate($tmp, 255, 255, 255);
    imagefilledrectangle($tmp, 0, 0, $newwidth, $newheight, $back);

    // check height max
    $h = $pluginSettings['thumb_size_h'];
    if ($f == 1)
    {
        $h = (int) SUCCESS_THUMB_HEIGHT;
    }
    if ($tmpH > (int) $h)
    {
        $newheight = (int) $h;
        $newwidth  = ($imageWidth / $imageHeight) * $newheight;
        $tmp       = imagecreatetruecolor($newwidth, $newheight);
    }

    // preserve transparency in gifs
    if ($file->extension == 'gif')
    {
        imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
    }

    // this line actually does the image resizing, copying from the original
    // image into the $tmp image
    imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newwidth, $newheight, $imageWidth, $imageHeight);

    // add white padding
    if ($pluginSettings['thumb_resize_method'] == 'padded')
    {
        // create base image
        $bgImg = imagecreatetruecolor((int) $w, (int) $h);

        // set background white
        $background = imagecolorallocate($bgImg, 255, 255, 255);  // white
        //$background = imagecolorallocate($bgImg, 0, 0, 0);  // black
        imagefill($bgImg, 0, 0, $background);

        // add on the resized image
        imagecopyresampled($bgImg, $tmp, ((int) $w / 2) - ($newwidth / 2), ((int) $h / 2) - ($newheight / 2), 0, 0, $newwidth, $newheight, $newwidth, $newheight);

        // reassign variable so the image is output below
        imagedestroy($tmp);
        $tmp = $bgImg;
    }

    // save image
    $rs = false;
    if ((int) $pluginSettings['caching'] == 1)
    {
        // save image
        ob_start();
        imagejpeg($tmp, null, 90);
        $imageContent = ob_get_clean();
        $rs           = cache::saveCacheToFile('plugins/imageviewer/' . $file->id . '/' . $cacheFileName, $imageContent);
    }

    if (!$rs)
    {
        // failed saving cache (or caching disabled), just output
        header('Content-Type: image/jpeg');
        imagejpeg($tmp, null, 80);
        exit;
    }

    // cleanup memory
    imagedestroy($tmp);
}

header('Content-Type: image/jpeg');
echo cache::getCacheFromFile('plugins/imageviewer/' . $file->id . '/' . $cacheFileName);
