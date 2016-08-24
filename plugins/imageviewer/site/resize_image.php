<?php

// validation
$fileId     = (int) $_REQUEST['f'];
$embedToken = !isset($_REQUEST['idt']) ? null : $_REQUEST['idt'];
$width      = (int) $_REQUEST['w'];
$height     = (int) $_REQUEST['h'];
$method     = isset($_REQUEST['m']) ? $_REQUEST['m'] : '';
if (($method != 'padded') && ($method != 'middle'))
{
    $method = 'cropped';
}

// validate width & height
if ($width <= 0)
{
    $width = 8;
}
if ($height <= 0)
{
    $height = 8;
}

// memory saver
if (($width > 5000) || ($height > 5000))
{
    header("HTTP/1.0 404 Not Found");
    exit;
}

// check and show cache before loading environment
$cacheFilePath = '../../../core/cache/plugins/imageviewer/';
$cacheFilePath .= $fileId . '/';
if (!file_exists($cacheFilePath))
{
    mkdir($cacheFilePath, 0777, true);
}
$cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method . '.jpg';
$fullCachePath = $cacheFilePath . $cacheFileName;
if (file_exists($fullCachePath))
{
    // output some headers
    header("Cache-Control: private, max-age=10800, pre-check=10800");
    header("Pragma: private");

    // Set to expire in 2 hours
    //header("Expires: " . date(DATE_RFC822, strtotime("2 hour"))); 
    //if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
    //{ 
    // if the browser has a cached version of this image, send 304 
    //    header('Last-Modified: '.$_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304); 
    //    exit; 
    //}
    header('Content-Type: image/jpeg');
    header("Pragma: public");
    header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
    header("Content-Description: File Transfer");
    echo file_get_contents($fullCachePath);
    exit;
}

// setup includes
require_once('../../../core/includes/master.inc.php');

// load reward details
$pluginObj      = pluginHelper::getInstance('imageviewer');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// try to load the file object
$file = null;
if ($fileId)
{
    $file = file::loadById($fileId);
}

// load file details
if (!$file)
{
    // no file found
    coreFunctions::output404();
}

// double check for token if not logged in as owner
// DISABLED
//if ($Auth->id != $file->userId)
//{
//    $db = Database::getDatabase(true);
//    $rs = $db->getValue('SELECT token FROM plugin_imageviewer_embed_token WHERE file_id=' . (int) $file->id . ' AND token="' . $db->escape($embedToken) . '" AND ip_address = ' . $db->quote(coreFunctions::getUsersIPAddress()) . ' LIMIT 1');
//    if (!$rs)
//    {
//        // show delay
//        coreFunctions::redirect($file->getFullShortUrl());
//        exit();
//    }
//}
// cache paths
if ((int) $pluginSettings['caching'] == 1)
{
    $cacheFilePath = '../../../core/cache/plugins/imageviewer/';
    $cacheFilePath .= $fileId . '/';
    if (!file_exists($cacheFilePath))
    {
        mkdir($cacheFilePath, 0777, true);
    }
    $cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method . '.jpg';
    $fullCachePath = $cacheFilePath . $cacheFileName;
}

// check for cache
if (((int) $pluginSettings['caching'] == 0) || (!cache::checkCacheFileExists($fullCachePath)))
{
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

    $newwidth  = (int) $width;
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
    if ($tmpH > (int) $height)
    {
        $newheight = (int) $height;
        $newwidth  = ($imageWidth / $imageHeight) * $newheight;
        $tmp       = imagecreatetruecolor($newwidth, $newheight);
    }

    // preserve transparency in gifs
    if ($file->extension == 'gif')
    {
        imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
    }

    // override method for small images
    if ($method == 'middle')
    {
        if ($width > $imageWidth)
        {
            $method = 'padded';
        }
        elseif ($height > $imageHeight)
        {
            $method = 'padded';
        }
    }

    if ($method == 'middle')
    {
        $tmp  = imagecreatetruecolor($width, $height);
        $back = imagecolorallocate($tmp, 255, 255, 255);
        imagefilledrectangle($tmp, 0, 0, $width, $height, $back);

        // preserve transparency in gifs
        if ($file->extension == 'gif')
        {
            imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
            imagealphablending($tmp, false);
            imagesavealpha($tmp, true);
        }

        if ($imageWidth > $imageHeight)
        {
            $newheight = (int) $height;
            $newwidth  = ($imageWidth / $imageHeight) * $newheight;
        }
        elseif ($imageHeight > $imageWidth)
        {
            $newwidth  = (int) $width;
            $newheight = ($imageHeight / $imageWidth) * $newwidth;
        }
        else
        {
            $newwidth  = (int) $width;
            $newheight = (int) $height;
        }

        $destX = 0;
        $destY = 0;
        if ($newwidth > $imageWidth)
        {
            $newwidth = $imageWidth;
        }
        if ($newheight > $imageHeight)
        {
            $newheight = $imageHeight;
        }

        // calculate new x/y positions
        if ($newwidth > $width)
        {
            $destX = floor(($width - $newwidth) / 2);
        }
        if ($newheight > $height)
        {
            $destY = floor(($height - $newheight) / 2);
        }

        imagecopyresampled($tmp, $im, $destX, $destY, 0, 0, $newwidth, $newheight, $imageWidth, $imageHeight);
    }
    else
    {
        // this line actually does the image resizing, copying from the original
        // image into the $tmp image
        imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newwidth, $newheight, $imageWidth, $imageHeight);
    }

    // add white padding
    if ($method == 'padded')
    {
        $w = $width;
        if ($w > $imageWidth)
        {
            $w = $imageWidth;
        }
        $h = $height;
        if ($h > $imageHeight)
        {
            $h = $imageHeight;
        }

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

    // add on the watermark after resizing
    if (((int) $pluginSettings['watermark_enabled'] == 1) && ($width > 140))
    {
        // load watermark image
        $watermark = $db->getRow("SELECT file_name, image_content FROM plugin_imageviewer_watermark LIMIT 1");
        if ($watermark)
        {
            $watermark_image = @imagecreatefromstring($watermark['image_content']);
            if ($watermark_image)
            {
                // get new image size
                $tmpW = imagesx($tmp);
                $tmpH = imagesy($tmp);

                // Set the margins for the stamp and get the height/width of the stamp image
                $margin = (int) $pluginSettings['watermark_padding'];
                $wx     = imagesx($watermark_image);
                $wy     = imagesy($watermark_image);

                // calculate positioning
                switch ($pluginSettings['watermark_position'])
                {
                    case 'top-left':
                        $waterX = $margin;
                        $waterY = $margin;
                        break;
                    case 'top-middle':
                        $waterX = ($tmpW / 2) - ($wx / 2);
                        $waterY = $margin;
                        break;
                    case 'top-right':
                        $waterX = $tmpW - $wx - $margin;
                        $waterY = $margin;
                        break;
                    case 'right':
                        $waterX = $tmpW - $wx - $margin;
                        $waterY = ($tmpH / 2) - ($wy / 2);
                        break;
                    case 'bottom-right':
                        $waterX = $tmpW - $wx - $margin;
                        $waterY = $tmpH - $wy - $margin;
                        break;
                    case 'bottom-middle':
                        $waterX = ($tmpW / 2) - ($wx / 2);
                        $waterY = $tmpH - $wy - $margin;
                        break;
                    case 'bottom-left':
                        $waterX = $margin;
                        $waterY = $tmpH - $wy - $margin;
                        break;
                    case 'left':
                        $waterX = $margin;
                        $waterY = ($tmpH / 2) - ($wy / 2);
                        break;
                    case 'middle':
                        $waterX = ($tmpW / 2) - ($wx / 2);
                        $waterY = ($tmpH / 2) - ($wy / 2);
                        break;
                }

                // Copy the stamp image onto our photo using the margin offsets and the photo 
                // width to calculate positioning of the stamp. 
                imagecopy($tmp, $watermark_image, $waterX, $waterY, 0, 0, imagesx($watermark_image), imagesy($watermark_image));
            }
        }
    }

    $rs = false;
    if ((int) $pluginSettings['caching'] == 1)
    {
        // save image
        ob_start();
        imagejpeg($tmp, null, 90);
        $imageContent = ob_get_clean();
        $rs           = cache::saveCacheToFile('plugins/imageviewer/' . $fileId . '/' . $cacheFileName, $imageContent);
    }

    if (!$rs)
    {
        // failed saving cache (or caching disabled), just output
        header('Content-Type: image/jpeg');
        imagejpeg($tmp, null, 90);
        exit;
    }

    // cleanup memory
    imagedestroy($tmp);
}

$size     = $width . 'x' . $height;
$filename = $file->originalFilename;
$filename = str_replace(array('.' . $file->extension), "", $filename);
$filename .= '_' . $size;
$filename .= '.' . $file->extension;
$filename = str_replace("\"", "", $filename);

// output some headers
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Content-Description: File Transfer");
echo cache::getCacheFromFile('plugins/imageviewer/' . $fileId . '/' . $cacheFileName);
