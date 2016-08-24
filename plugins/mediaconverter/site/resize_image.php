<?php

// setup includes
require_once('../../../core/includes/master.inc.php');

// load reward details
$pluginConfig   = pluginHelper::pluginSpecificConfiguration('mediaconverter');
$pluginSettings = json_decode($pluginConfig['data']['plugin_settings'], true);

function outputVideoFileIcon()
{
    $url = file::getIconPreviewImageUrlLarge(array('extension'=>'mp4'), false, false);
    header('Content-Type: image/jpeg');
    echo file_get_contents($url);
    exit;
}

function outputVideoFileIconLarge()
{
    $url = file::getIconPreviewImageUrl(array('extension'=>'mp4'), false, 512);
    header('Content-Type: image/jpeg');
    echo file_get_contents($url);
    exit;
}

// validation
$fileId     = (int) $_REQUEST['f'];
$width      = (int) $_REQUEST['w'];
$height     = (int) $_REQUEST['h'];
$method     = $_REQUEST['m'];
if(($method != 'padded') && ($method != 'middle'))
{
    $method = 'cropped';
}

// validate width & height
if(($width == 0) || ($height == 0))
{
    outputVideoFileIcon();
}

// memory saver
if(($width > 5000) || ($height > 5000))
{
    outputVideoFileIcon();
}

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
    outputVideoFileIcon();
}

// cache paths
$cacheFilePath = CACHE_DIRECTORY_ROOT . '/plugins/mediaconverter/'.(int)$file->id.'/';
$fullCachePath = null;
if (!is_dir($cacheFilePath))
{
    @mkdir($cacheFilePath, 0777, true);
}

$cacheFileName = (int) $width . 'x' . (int) $height . '_' . $method.'_'. MD5(json_encode($pluginSettings)) . '.jpg';
$fullCachePath = $cacheFilePath . $cacheFileName;

// check for cache
if (($fullCachePath == null) || (!file_exists($fullCachePath)))
{
    // get original screenshot
    $contents = coreFunctions::getRemoteUrlContent(CACHE_WEB_ROOT.'/plugins/mediaconverter/'.(int)$file->id.'/original_thumb.jpg');

    // load into memory
    $im = @imagecreatefromstring($contents);
    if ($im === false)
    {
        // failed reading image
        if(($width > 160) || ($height > 160))
        {
            outputVideoFileIconLarge();
        }
        else
        {
            outputVideoFileIcon();
        }
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

    // check height max
    if ($tmpH > (int) $height)
    {
        $newheight = (int) $height;
        $newwidth  = ($imageWidth / $imageHeight) * $newheight;
        $tmp       = imagecreatetruecolor($newwidth, $newheight);
    }
    
    // override method for small images
    if ($method == 'middle')
    {
        if($width > $imageWidth)
        {
            $method = 'padded';
        }
        elseif($height > $imageHeight)
        {
            $method = 'padded';
        }
    }
    
    if ($method == 'middle')
    {
        $tmp  = imagecreatetruecolor($width, $height);
        
        $newwidth  = (int) $width;
        $newheight = ($imageHeight / $imageWidth) * $newwidth;
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
            $destX = floor(($width-$newwidth)/2);
        }
        if ($newheight > $height)
        {
            $destY = floor(($height-$newheight)/2);
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
        if($w > $imageWidth)
        {
            $w = $imageWidth;
        }
        $h = $height;
        if($h > $imageHeight)
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

    $rs = false;
    if ($fullCachePath != null)
    {
        // save image
        $rs = imagejpeg($tmp, $fullCachePath, 90);
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

header('Content-Type: image/jpeg');
echo file_get_contents($fullCachePath);
