<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// load image details
$pluginObj      = pluginHelper::getInstance('imageviewer');
$pluginDetails  = pluginHelper::pluginSpecificConfiguration('imageviewer');
$pluginSettings = json_decode($pluginDetails['data']['plugin_settings'], true);

// validation
$fileId     = (int) $_REQUEST['f'];
$embedToken = $_REQUEST['idt'];

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

$db = Database::getDatabase(true);
$rs = $db->getValue('SELECT token FROM plugin_imageviewer_embed_token WHERE file_id=' . (int) $file->id . ' AND token="' . $db->escape($embedToken) . '" LIMIT 1');
if (!$rs)
{
    // show delay
    coreFunctions::redirect($file->getFullShortUrl());
    exit();
}

// cache paths
if ((int) $pluginSettings['caching'] == 1)
{
    $cacheFilePath = '../../../core/cache/plugins/imageviewer/';
    $cacheFilePath .= $fileId . '/';
    if (!file_exists($cacheFilePath))
    {
        mkdir($cacheFilePath, 0777, true);
    }
    $cacheFileName = (int) $pluginSettings['image_size_w'] . 'x' . (int) $pluginSettings['image_size_h'] . '_' . MD5(json_encode($pluginSettings)) . '.jpg';
    $fullCachePath = $cacheFilePath . $cacheFileName;
}

// check for cache
if (((int) $pluginSettings['caching'] == 0) || (!file_exists($fullCachePath)))
{
    // get image contents
    $contents = $file->download(false);

    // load into memory
    $im = imagecreatefromstring($contents);
    if ($im === false)
    {
        // show original image fallback
        if (strlen($contents))
        {
            header('Content-Type: ' . $file->fileType);
            echo $contents;
            exit;
        }

        // failed reading image
        coreFunctions::output404();
    }

    // if animated gif output the original file
    if ($file->extension == 'gif')
    {
        if ($pluginObj->isAnimatedGif($contents) == true)
        {
            header('Content-Type: ' . $file->fileType);
            echo $contents;
            exit;
        }
    }

    // preserve transparency in gifs
    if ($file->extension == 'gif')
    {
        imagecolortransparent($im, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
        imagealphablending($im, false);
        imagesavealpha($im, true);
    }

    // get image size
    $imageWidth  = imagesx($im);
    $imageHeight = imagesy($im);

    $newwidth  = (int) $pluginSettings['image_size_w'];
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
    if ($tmpH > (int) $pluginSettings['image_size_h'])
    {
        $newheight = (int) $pluginSettings['image_size_h'];
        $newwidth  = ($imageWidth / $imageHeight) * $newheight;
        $tmp       = imagecreatetruecolor($newwidth, $newheight);
    }

    // this line actually does the image resizing, copying from the original
    // image into the $tmp image
    imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newwidth, $newheight, $imageWidth, $imageHeight);

    // add on the watermark after resizing
    if ((int) $pluginSettings['watermark_enabled'] == 1)
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
        header('Content-Type: image/jpeg');
        echo cache::getCacheFromFile('plugins/imageviewer/' . $fileId . '/' . $cacheFileName);
        exit;
    }

    if (!$rs)
    {
        // failed saving cache (or caching disabled), just output
        header('Content-Type: image/jpeg');
        imagejpeg($tmp, null, 80);
        exit;
    }

    // cleanup memory
    imagedestroy($src);
    imagedestroy($tmp);
}

header('Content-Type: image/jpeg');
echo cache::getCacheFromFile('plugins/imageviewer/' . $fileId . '/' . $cacheFileName);
