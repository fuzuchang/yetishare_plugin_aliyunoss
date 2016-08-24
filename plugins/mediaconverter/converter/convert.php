<?php

/*
 * Title: Video Converter Script.
 * Author: YetiShare.com
 * Period: Run by cron every minute or as required. (it won't trigger a new
 * conversion until the last one is finished)
 * 
 * Description:
 * Script to process any pending video conversions to mp4 via the YetiShare
 * mediaconverter plugin. It can be run on a different server than the main site
 * as long as the server meets the requirements below.
 * 
 * How To Call:
 * On the command line via PHP, like this:
 * php convert.php
 * 
 * Requirements:
 * - PHP 5.2+.
 * - MySQL PDO module enabled in PHP.
 * - FTP module enabled in PHP.
 * - FFMPEG.
 * - MySQL connectivity to main site database.
 * - qt-faststart (sometimes part of FFMPEG install) to enable streaming mp4s.
 * - Folder in the same location as this script called '_cache'. Set permissions
 * to CHMOD 777.
 * 
 * Direct File Servers:
 * Ensure you set the SSH access details for each server below in the $directFileServers
 * variable. The SSH user must have access to read and write the stored files.
 * 
 * Install FFMPEG:
 * See http://ffmpeginstaller.com/ for an installer, details below.
 * CLI Code: (note: the install may take up to 15 minutes to complete)
 * wget http://mirror.ffmpeginstaller.com/old/scripts/ffmpeg8/ffmpeginstaller.8.0.tar.gz
 * tar zxvf ffmpeginstaller.8.0.tar.gz
 * cd ffmpeginstaller.8.0
 * sh install.sh
 * 
 * To install on Ubuntu:
 * sudo apt-get install ffmpeg libavcodec-extra-53
 * sudo apt-get install libmp3lame0
 *
 * Test FFMPEG:
 * You can use this command to test that ffmpeg is converting to mp4 correctly. Upload
 * an avi file onto your server and execute this:
 * 
 * ffmpeg -i video1.avi -vcodec libx264 -b:v 1400k -flags +aic+mv4 -vf "scale=640:-1" video1.mp4
 */

/*
 * **************************************************
 * CHANGE THESE IF RUNNING ON SEPARATE SERVER
 * **************************************************
 * Database connection details.
 * Note: If you are running this script on another server, ensure your database
 * isn't locked down to just localhost. It'll need privileges to allow connections
 * from this host.
 */
$databaseHost = "";
$databaseUser = "";
$databasePass = "";
$databaseName = "";

$directFileServers = array();
/*
 * **************************************************
 * DIRECT FILE SERVER ACCESS DETAILS
 * **************************************************
 * Set these if you have direct file servers. Create a new set of the following values for
 * each file server. The 'file_server_id' must match the id set in your database
 * table 'file_server'. The 'file_storage_path' is the full base path, generally ending with
 * '/files'. Exclude the final forward slash.
 *
 $directFileServers[] = array(
	 'file_server_id' => 2,
	 'ssh_host' => 'fs1.yourhost.com',
     'ssh_port' => '22',
	 'ssh_username' => 'username',
	 'ssh_password' => 'password',
	 'file_storage_path' => '/path/to/your/files',
 );
 */
/*
 * **************************************************
 * EXAMPLE
 * **************************************************
 $directFileServers[] = array(
	 'file_server_id' => 2,
	 'ssh_host' => 'fs1.yourhost.com',
     'ssh_port' => '22',
	 'ssh_username' => 'username',
	 'ssh_password' => 'password',
	 'file_storage_path' => '/path/to/your/files',
 );
 ***************************************************
 * END EXAMPLE
 * **************************************************
 */

/*
 * **************************************************
 * DONT CHANGE ANYTHING BELOW HERE
 * **************************************************
 */

// if this is running in the same location as the script
define('DOC_ROOT', dirname(__FILE__));
$configPath = DOC_ROOT . '/../../../_config.inc.php';
if (file_exists($configPath))
{
    include_once($configPath);

    $databaseHost = _CONFIG_DB_HOST;
    $databaseUser = _CONFIG_DB_USER;
    $databasePass = _CONFIG_DB_PASS;
    $databaseName = _CONFIG_DB_NAME;

    define('ON_SCRIPT_INSTALL', true);
}
else
{
    define('ON_SCRIPT_INSTALL', false);
}

define("DATABASE_HOST", $databaseHost);
define("DATABASE_USER", $databaseUser);
define("DATABASE_PASS", $databasePass);
define("DATABASE_NAME", $databaseName);

// make file server ssh details available in constant
define("DIRECT_FILE_SERVER_DETAILS", serialize($directFileServers));
define("CONVERT_BITRATE", "1400k");
define("CONVERT_FRAMERATE", "30");

/*
 * Connect database and load config from plugin settings.
 */
try
{
    $db = dbConnect();
    if ($db)
    {
        $stmt           = $db->query("SELECT * FROM plugin WHERE folder_name='mediaconverter' LIMIT 1");
        $pluginDetails  = $stmt->fetch(PDO::FETCH_ASSOC);
        $pluginSettings = $pluginDetails['plugin_settings'];
        if ($pluginSettings)
        {
            $pluginSettingsArr = json_decode($pluginSettings, true);
        }
    }
}
catch (Exception $e)
{
    echo "\n" . $e->getMessage() . "\n";
    exit;
}

/*
 * For local storage this script needs to have access via SSH to the files. Set
 * the SSH details below.
 */
define("LOCAL_STORAGE_SSH_HOST", $pluginSettingsArr['ssh_host']);
define("LOCAL_STORAGE_SSH_USER", $pluginSettingsArr['ssh_user']);
define("LOCAL_STORAGE_SSH_PASS", $pluginSettingsArr['ssh_password']);
define("LOCAL_STORAGE_DEFAULT_PATH", $pluginSettingsArr['local_storage_path']);

/*
 * General config.
 */

// change this to set the maximum conversions that can be done at once.
define("MAX_CONCURRENT_CONVERSIONS", $pluginSettingsArr['max_conversions']);

// maximum video size, all videos are contrained to these max widths/heights
define("VIDEO_MAX_WIDTH", $pluginSettingsArr['video_size_w']);
define("VIDEO_MAX_HEIGHT", $pluginSettingsArr['video_size_h']);

// FFMPEG path
define("FFMPEG_PATH", "ffmpeg");

// show output, used for debugging
define("SHOW_OUTPUT", $pluginSettingsArr['output_messages']);

// local paths, shouldn't need changed
define("SCRIPT_ROOT_FOLDER", dirname(__FILE__));
define("CACHE_PATH", SCRIPT_ROOT_FOLDER . '/_cache');
define("CACHE_SCREENSHOT_PATH", sys_get_temp_dir());
define("SCREENSHOT_SECONDS", '15'); // in 2 digit format, i.e. 05 = 5 seconds.

// export file type
define("EXPORT_FILE_EXTENSION", $pluginSettingsArr['output_type']);
define("EXPORT_FILE_MIMETYPE", "video/".$pluginSettingsArr['output_type']);

/*
 * External includes
 */
set_include_path(get_include_path() . PATH_SEPARATOR . SCRIPT_ROOT_FOLDER . '/phpseclib');

include_once('Net/SFTP.php');

/*
 * Main conversion code.
 */

// php script timeout for long conversions (12 hours)
set_time_limit(60 * 60 * 12);

// report simple running errors
error_reporting(E_ERROR | E_WARNING | E_PARSE);

// make sure the local cache path is writable
if (!is_writable(CACHE_PATH))
{
    output("Error: Cache path is not writable, please ensure it's set to CHMOD 755 or 777 depending on your server setup: " . CACHE_PATH . ".\n");
    exit;
}

// make sure shell_exec is available
if(!function_exists('shell_exec'))
{
    output("Error: The PHP function shell_exec() is not available and may be blocked within your php.ini file. Please check and try again.\n");
    exit;
}

// make sure we have mcrypt for speed purposes
if(!function_exists('mcrypt_create_iv'))
{
	output("Error: Mcrypt functions not found in PHP. This script will run VERY slow if you don't enable them. Please check and try again.\n");
    exit;
}

// connect db and get any pending rows
try
{
    $db = new PDO('mysql:host=' . DATABASE_HOST . ';dbname=' . DATABASE_NAME . ';charset=utf8', DATABASE_USER, DATABASE_PASS);
    if ($db)
    {
        // fail any which have been converting for more than 1 day, sorts occasional issues with timeouts
        $stmt = $db->query("UPDATE plugin_mediaconverter_queue SET status='failed', date_finished=NOW() WHERE status='processing' AND date_started < NOW() - INTERVAL 1 DAY");
        
        // make sure there's none being processed
        $stmt = $db->query("SELECT COUNT(id) AS total FROM plugin_mediaconverter_queue WHERE status='processing'");
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        if ((int) $row['total'] >= MAX_CONCURRENT_CONVERSIONS)
        {
            // max concurrent
            output("Already " . (int) $row['total'] . " conversion(s) processing.\n");
            exit;
        }

        // check for pending conversions
        $stmt       = $db->query("SELECT id, file_id FROM plugin_mediaconverter_queue WHERE status='pending' ORDER BY date_added ASC LIMIT 1");
        $pendingRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pendingRow)
        {
            output("No pending conversions found.\n");
            exit;
        }
    }
}
catch (Exception $e)
{
    output("\n");
    output("ERROR: " . $e->getMessage() . "\n");
    output("\n");
    exit;
}

// log
output("Found 1 pending conversion, id #" . $pendingRow['id'] . ".\n");

// load file record
$stmt = $db->query("SELECT * FROM file WHERE id=" . $pendingRow['file_id'] . " AND statusId=1 LIMIT 1");
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file)
{
    // log
    output("Error: Could not load file data. It may be inactive or missing.\n");

    // set to failed
    $db->query("UPDATE plugin_mediaconverter_queue SET status='failed', date_started=NOW(), date_finished=NOW(), notes='Error: Could not load file data. It may be inactive or missing.' WHERE id=" . $pendingRow['id'] . " LIMIT 1");

    exit;
}

// set to processing
$db->query("UPDATE plugin_mediaconverter_queue SET status='processing', date_started=NOW() WHERE id=" . $pendingRow['id'] . " AND status='pending' LIMIT 1");

// log
output("Getting file from storage...\n");

// grab file
$localFile = getFileContent($db, $file);
if (!$localFile)
{
    // log
    output("Error: Could not get file contents.\n");

    // set to failed
    $db->query("UPDATE plugin_mediaconverter_queue SET status='failed', date_finished=NOW(), notes='Error: Could not get file contents.' WHERE id=" . $pendingRow['id'] . " LIMIT 1");

    exit;
}

// get original video size
$originalWidth  = null;
$originalHeight = null;
if (file_exists($localFile))
{
    $command = FFMPEG_PATH . ' -i ' . $localFile . ' -vstats 2>&1';
    $output  = shell_exec($command);

    $result = ereg('[0-9]?[0-9][0-9][0-9]x[0-9][0-9][0-9][0-9]?', $output, $regs);
    if (isset($regs[0]))
    {
        $vals           = (explode('x', $regs[0]));
        $originalWidth  = $vals[0] ? $vals[0] : null;
        $originalHeight = $vals[1] ? $vals[1] : null;
    }
}

// prepare resize, contraining proportions
$scale = '-vf "scale=' . VIDEO_MAX_WIDTH . ':trunc(ow/a/2)*2"';
if ((int) $originalWidth > 0)
{
    $x_ratio = VIDEO_MAX_WIDTH / $originalWidth;
    $y_ratio = VIDEO_MAX_HEIGHT / $originalHeight;

    if (($originalWidth <= VIDEO_MAX_WIDTH) && ($originalHeight <= VIDEO_MAX_HEIGHT))
    {
        $tn_width  = $originalWidth;
        $tn_height = $originalHeight;
    }
    elseif (($x_ratio * $originalHeight) < VIDEO_MAX_HEIGHT)
    {
        $tn_height = ceil($x_ratio * $originalHeight);
        $tn_width  = VIDEO_MAX_WIDTH;
    }
    else
    {
        $tn_width  = ceil($y_ratio * $originalWidth);
        $tn_height = VIDEO_MAX_HEIGHT;
    }

    // make sure numbers are even for scaling
    if ($tn_width % 2 == 1)
    {
        $tn_width = $tn_width + 1;
    }
    if ($tn_height % 2 == 1)
    {
        $tn_height = $tn_height + 1;
    }

    $scale = '-vf "scale=' . $tn_width . ':' . $tn_height . '"';
}

// log
output("Converting file...\n");

// convert via ffmpeg
$localFileParts         = pathinfo($localFile);
$convertedFilename      = $localFileParts['dirname'] . '/' . $localFileParts['filename'] . '_new.' . EXPORT_FILE_EXTENSION;
$convertedFilenameFinal = $localFileParts['dirname'] . '/' . $localFileParts['filename'] . '.' . EXPORT_FILE_EXTENSION;

$videoCodec = 'libx264';
if ($pluginSettingsArr['output_type'] == 'webm')
{
	$videoCodec = 'libvpx';
}

//$conversionPathCmd      = FFMPEG_PATH . ' -i ' . $localFile . ' -vcodec '.$videoCodec.' -r '.CONVERT_FRAMERATE.' -b:v '.CONVERT_BITRATE.' -flags +aic+mv4 ' . $scale . ' ' . $convertedFilename;
// force 2 channels to fix issues with 5.1 on libvo_aacenc
$conversionPathCmd      = FFMPEG_PATH . ' -i ' . $localFile . ' -vcodec '.$videoCodec.' -ac 2 -r '.CONVERT_FRAMERATE.' -b:v '.CONVERT_BITRATE.' -flags +aic+mv4 ' . $scale . ' ' . $convertedFilename;

// should we add a watermark
if ((int) $pluginSettingsArr['watermark_enabled'] == 1)
{
    // save watermark image as file
    $watermarkFile      = CACHE_PATH . '/_watermark.png';
    $watermark_contents = '';
    $stmt               = $db->query("SELECT file_name, image_content FROM plugin_mediaconverter_watermark");
    $row                = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['image_content'])
    {
        $watermark_contents = $row['image_content'];
    }

    if (strlen($watermark_contents))
    {
        $rs = file_put_contents($watermarkFile, $watermark_contents);
        if ($rs)
        {
            // position
            $overlay = 'main_w-overlay_w-' . (int) $pluginSettingsArr['watermark_padding'] . ':main_h-overlay_h-' . (int) $pluginSettingsArr['watermark_padding'];
            switch ($pluginSettingsArr['watermark_position'])
            {
                case 'top-left':
                    $overlay = (int) $pluginSettingsArr['watermark_padding'] . ':' . (int) $pluginSettingsArr['watermark_padding'];
                    break;
                case 'top-middle':
                    $overlay = 'main_w/2-overlay_w/2:' . (int) $pluginSettingsArr['watermark_padding'];
                    break;
                case 'top-right':
                    $overlay = 'main_w-overlay_w-' . (int) $pluginSettingsArr['watermark_padding'] . ':' . (int) $pluginSettingsArr['watermark_padding'];
                    break;
                case 'right':
                    $overlay = 'main_w-overlay_w-' . (int) $pluginSettingsArr['watermark_padding'] . ':main_h/2-overlay_h/2';
                    break;
                case 'bottom-right':
                    $overlay = 'main_w-overlay_w-' . (int) $pluginSettingsArr['watermark_padding'] . ':main_h-overlay_h-' . (int) $pluginSettingsArr['watermark_padding'];
                    break;
                case 'bottom-middle':
                    $overlay = 'main_w/2-overlay_w/2:main_h-overlay_h-' . (int) $pluginSettingsArr['watermark_padding'];
                    break;
                case 'bottom-left':
                    $overlay = (int) $pluginSettingsArr['watermark_padding'] . ':main_h-overlay_h-' . (int) $pluginSettingsArr['watermark_padding'];
                    break;
                case 'left':
                    $overlay = (int) $pluginSettingsArr['watermark_padding'] . ':main_h/2-overlay_h/2';
                    break;
                case 'middle':
                    $overlay = 'main_w/2-overlay_w/2:main_h/2-overlay_h/2';
                    break;
            }

            $conversionPathCmd = FFMPEG_PATH . ' -i ' . $localFile . ' -vcodec '.$videoCodec.' -r '.CONVERT_FRAMERATE.' -b:v '.CONVERT_BITRATE.' -flags +aic+mv4 ' . $scale . '  -vf "movie=' . $watermarkFile . ' [watermark]; [in][watermark] overlay=' . $overlay . ' [out]" ' . $convertedFilename;
        }
    }
}

// output to screen
$notesMessage = "FFMPEG Command:\n";
$notesMessage .= $conversionPathCmd . "\n\n";
output($notesMessage);

if (SHOW_OUTPUT != 1)
{
    $conversionPathCmd .= ' 2>&1';
}

$output = shell_exec($conversionPathCmd);

// prepare notes
$notesMessage .= "Result:\n";
$notesMessage .= $output;
output($notesMessage);
output("\n");

// standardise response
$success = false;
$status  = 'failed';
if ((file_exists($convertedFilename)) && (filesize($convertedFilename) > 0))
{
    // only for mp4 files
    if ($pluginSettingsArr['output_type'] == 'mp4')
    {
        // log
        output("Moving MOOV atom via qt-faststart to enable streaming...\n");

        $conversionPathCmd2 = 'qt-faststart ' . $convertedFilename . ' ' . $convertedFilename . '_tmp';
        $output2            = shell_exec($conversionPathCmd2);

        // sucessfully converted for streaming
        if (file_exists($convertedFilename . '_tmp'))
        {
            $conversionPathCmd3 = 'mv ' . $convertedFilename . '_tmp ' . $convertedFilename;
            shell_exec($conversionPathCmd3);
        }
    }

    $success = true;
    $status  = 'completed';
}

// get screenshot from new file, problem with cross server file storage for these
if ($success == true)
{
    // log
    output("Getting " . SCREENSHOT_SECONDS . " second screenshot...\n");

    // thumb path
    $thumbFilename = current(explode(".", MD5($file['id'])));
    $fullThumbPath = CACHE_SCREENSHOT_PATH;
    $fullThumbPath .= '/' . $thumbFilename . '.jpg';
    
    // ensure it doesn't exist from a previous convert
    if(file_exists($fullThumbPath))
    {
        unlink($fullThumbPath);
    }

    $thumbPathCmd = FFMPEG_PATH . ' -ss 00:00:' . SCREENSHOT_SECONDS . '.01 -i ' . $convertedFilename . ' -vframes 1 ' . $fullThumbPath;
    if (SHOW_OUTPUT != 1)
    {
        $thumbPathCmd .= ' 2>&1';
    }
    $output = shell_exec($thumbPathCmd);

    // prepare notes
    $notesMessage = "ScreenShot Command:\n";
    $notesMessage .= $thumbPathCmd . "\n\n";
    $notesMessage .= "Result:\n";
    $notesMessage .= $output;
    output($notesMessage);
    output("\n");

    // if screen exists, move it to the core server for storage
    if ((file_exists($fullThumbPath)) && (filesize($fullThumbPath) > 0))
    {
        $remoteScriptRoot      = $pluginSettingsArr['script_path_root'];
        $remoteScriptThumbPath = $remoteScriptRoot . '/core/cache/plugins/mediaconverter/'.$file['id'].'/original_thumb.jpg';

        // first try setting the file locally
        $done = false;
        if (ON_SCRIPT_INSTALL == true)
        {
            if (!file_exists($remoteScriptThumbPath))
            {
                @mkdir(dirname($remoteScriptThumbPath), 0777, true);
            }
            $done = rename($fullThumbPath, $remoteScriptThumbPath);
        }

        // try over ssh
        if ($done == false)
        {
            // connect to 'local' storage via SSH
            $sftp = new Net_SFTP(LOCAL_STORAGE_SSH_HOST);
            if (!$sftp->login(LOCAL_STORAGE_SSH_USER, LOCAL_STORAGE_SSH_PASS))
            {
                output("Error: Failed logging into " . LOCAL_STORAGE_SSH_HOST . " via SSH to transfer screenshot.\n");
            }
            else
            {
                // create folder structure
                $sftp->mkdir($remoteScriptRoot . '/core/cache/plugins/');
                $sftp->mkdir($remoteScriptRoot . '/core/cache/plugins/mediaconverter/');
                $sftp->mkdir($remoteScriptRoot . '/core/cache/plugins/mediaconverter/'.$file['id'].'/');
                
                // set folder to chmod 777
                //$sftp->chmod(0777, $remoteScriptRoot . '/core/cache/plugins/mediaconverter/'.$file['id'].'/');

                // upload screen
                $rs = $sftp->put($remoteScriptThumbPath, $fullThumbPath, NET_SFTP_LOCAL_FILE);
                if (!$rs)
                {
                    output("Error: Failed uploading thumb to " . LOCAL_STORAGE_SSH_HOST . " via SSH. Local file: " . $fullThumbPath . ". Remote path: " . $remoteScriptThumbPath . "\n");
                }
                @unlink($fullThumbPath);
                
                // set file to chmod 777
                //$sftp->chmod(0777, $remoteScriptThumbPath);
            }
        }
    }
}

// rename to match original file
$movePathMp3 = 'mv ' . $convertedFilename . ' ' . $convertedFilenameFinal;
shell_exec($movePathMp3);

// reconnect to database encase it's gone away
$db = dbConnect();

if ($success == true)
{
    // log
    output("Moving converted file into storage...\n");

    // load hash for later
    $fileHash = md5_file($convertedFilenameFinal);
    
    // should we keep the original file?
	if((int)$pluginSettingsArr['keep_original'] == 1)
	{
		// setup new file entry
		$newOriginalFilename = str_replace(array('.' . $file['extension'], '.' . strtoupper($file['extension'])), '', $file['originalFilename']) . ' (converted).' . EXPORT_FILE_EXTENSION;
		$newFilePath = current(explode('/', $file['localFilePath'])).'/'.MD5(microtime().rand(10000,999999).$file['id']);
		$stmt                = $db->prepare("INSERT INTO file (folderId, originalFilename, shortUrl, fileType, extension, fileSize, localFilePath, userId, totalDownload, uploadedIP, uploadedDate, statusId, deleteHash, serverId, fileHash, adminNotes, linkedFileId) VALUES (:folderId, :originalFilename, :shortUrl, :fileType, :extension, :fileSize, :localFilePath, :userId, :totalDownload, :uploadedIP, :uploadedDate, :statusId, :deleteHash, :serverId, :fileHash, :adminNotes, :linkedFileId)");
		$replacements        = array(':folderId'=>$file['folderId'], ':originalFilename' => $newOriginalFilename, ':shortUrl' => $file['shortUrl'].'_'.rand(1000,9999).'_converted', ':fileType'         => EXPORT_FILE_MIMETYPE, ':extension'        => EXPORT_FILE_EXTENSION, ':fileSize'         => filesize($convertedFilenameFinal), ':localFilePath'               => $newFilePath, ':userId' => $file['userId'], ':totalDownload' => 0, ':uploadedIP' => $file['uploadedIP'], ':uploadedDate' => $file['uploadedDate'], ':statusId' => $file['statusId'], ':deleteHash' => MD5($file['deleteHash'].rand(10000,99999)), ':serverId' => $file['serverId'], ':fileHash' => $fileHash, ':adminNotes' => 'Converted from original file id '.$file['id'], ':linkedFileId' => $file['id']);
		$rs                  = $stmt->execute($replacements);

        // overwrite our file object
		$stmt = $db->query("SELECT * FROM file WHERE id=" . (int)$db->lastInsertId() . " LIMIT 1");
		$file = $stmt->fetch(PDO::FETCH_ASSOC);
	}

    // upload back into storage
    $rs = setFileContent($db, $file, $convertedFilenameFinal);
    if (!$rs)
    {
        // log
        output("Error: Could not set file contents back into storage: ".$convertedFilenameFinal.".\n");

        // remove converted file
        @unlink($convertedFilenameFinal);

        // set to failed
        $db->query("UPDATE plugin_mediaconverter_queue SET status='failed', date_finished=NOW(), notes='Error: Could not set file contents back into storage.' WHERE id=" . $pendingRow['id'] . " LIMIT 1");

        exit;
    }

    // update existing
	if((int)$pluginSettingsArr['keep_original'] == 0)
	{
        // reconnect to database encase it's gone away
        $db = dbConnect();
        
        // update database with new file information
        $newOriginalFilename = str_replace(array('.' . $file['extension'], '.' . strtoupper($file['extension'])), '', $file['originalFilename']) . '.' . EXPORT_FILE_EXTENSION;
        $stmt                = $db->prepare("UPDATE file SET originalFilename=:originalFilename, fileType=:fileType, extension=:extension, fileSize=:fileSize WHERE id=:id");
        $replacements        = array(':originalFilename' => $newOriginalFilename, ':fileType'         => EXPORT_FILE_MIMETYPE, ':extension'        => EXPORT_FILE_EXTENSION, ':fileSize'         => filesize($convertedFilenameFinal), ':id'               => $file['id']);
        $rs                  = $stmt->execute($replacements);
    
        if (!$rs)
        {
            // log
            output("Error: Could not update the remote database.\n");
    
            // remove converted file
            @unlink($convertedFilenameFinal);
    
            // set to failed
            $db->query("UPDATE plugin_mediaconverter_queue SET status='failed', date_finished=NOW(), notes='Error: Could not update the remote database.' WHERE id=" . $pendingRow['id'] . " LIMIT 1");
    
            exit;
        }
    }
}

// log
if ($success == true)
{
    // remove converted file
    @unlink($convertedFilenameFinal);

    output("Completed conversion, id #" . $pendingRow['id'] . ".\n");
}
else
{
    output("Failed conversion, id #" . $pendingRow['id'] . ".\n");
}

// remove original file
@unlink($localFile);

// update md5 hash on file
$db->query("UPDATE file SET fileHash='" . $fileHash . "' WHERE id=" . (int) $file['id'] . " LIMIT 1");

// update database to completed
$db->query("UPDATE plugin_mediaconverter_queue SET status='" . $status . "', date_finished=NOW(), notes='" . str_replace("'", "\\'", $notesMessage) . "' WHERE id=" . $pendingRow['id'] . " LIMIT 1");

/*
 * Functions
 */

function output($msg)
{
    if (SHOW_OUTPUT == 1)
    {
        echo $msg;
    }
}

function getFileContent($db, $file)
{
    // setup local cached file
    $localFilename = MD5(microtime()) . '.' . $file['extension'];
    $localFilePath = CACHE_PATH . '/' . $localFilename;

    // figure out server storage setup
    $storageType         = 'local';
    $storageLocation     = LOCAL_STORAGE_DEFAULT_PATH;
    $uploadServerDetails = loadServer($db, $file);
    if ($uploadServerDetails != false)
    {
        $storageLocation = $uploadServerDetails['storagePath'];
        $storageType     = $uploadServerDetails['serverType'];

        // if no storage path set & local, use system default
        if ((strlen($storageLocation) == 0) && ($storageType == 'local'))
        {
            $storageLocation = LOCAL_STORAGE_DEFAULT_PATH;
        }

        if ($storageType == 'direct')
        {
            $storageLocation = LOCAL_STORAGE_DEFAULT_PATH;
        }
    }

    // use ssh to get contents of 'local' files
    if (($storageType == 'local') || ($storageType == 'direct'))
    {
        // get remote file path
        $remoteFilePath = $storageLocation . $file['localFilePath'];

        // first try getting the file locally
        $done = false;
        if ((ON_SCRIPT_INSTALL == true) && file_exists($remoteFilePath))
        {
            
            $done = copy($remoteFilePath, $localFilePath);
            if ($done)
            {
                return $localFilePath;
            }
        }

        // try over ssh
        if ($done == false)
        {
			$sshHost = LOCAL_STORAGE_SSH_HOST;
			$sshUser = LOCAL_STORAGE_SSH_USER;
			$sshPass = LOCAL_STORAGE_SSH_PASS;

			// if 'direct' file server, get SSH details
			$serverDetails = getDirectFileServerSSHDetails($file['serverId']);
			if($serverDetails)
			{
				$sshHost = $serverDetails['ssh_host'];
                $sshPort = $serverDetails['ssh_port'];
				$sshUser = $serverDetails['ssh_username'];
				$sshPass = $serverDetails['ssh_password'];
				$basePath = $serverDetails['file_storage_path'];
				if(substr($basePath, strlen($basePath)-1, 1) == '/')
				{
					$basePath = substr($basePath, 0, strlen($basePath)-1);
				}
				$remoteFilePath = $basePath . '/' . $file['localFilePath'];
			}
            
            if(strlen($sshPort) == 0)
            {
                $sshPort = 22;
            }
			
            // connect to 'local' storage via SSH
            $sftp = new Net_SFTP($sshHost, $sshPort);
            if (!$sftp->login($sshUser, $sshPass))
            {
                output("Error: Failed logging into " . $sshHost . " (port: ".$sshPort.") via SSH..\n");

                return false;
            }

            // get file
            $rs = $sftp->get($remoteFilePath, $localFilePath);
            if ($rs)
            {
                return $localFilePath;
            }
        }

        return false;
    }

    // ftp
    if ($storageType == 'ftp')
    {
        // setup full path
        $prePath = $uploadServerDetails['storagePath'];
        if (substr($prePath, strlen($prePath) - 1, 1) == '/')
        {
            $prePath = substr($prePath, 0, strlen($prePath) - 1);
        }
        $remoteFilePath = $prePath . '/' . $file['localFilePath'];

        // connect via ftp
        $conn_id = ftp_connect($uploadServerDetails['ipAddress'], $uploadServerDetails['ftpPort'], 30);
        if ($conn_id === false)
        {
            output('Could not connect to ' . $uploadServerDetails['ipAddress'] . ' to upload file.');
            return false;
        }

        // authenticate
        $login_result = ftp_login($conn_id, $uploadServerDetails['ftpUsername'], $uploadServerDetails['ftpPassword']);
        if ($login_result === false)
        {
            output('Could not login to ' . $uploadServerDetails['ipAddress'] . ' with supplied credentials.');
            return false;
        }

        // get content
        $ret = ftp_get($conn_id, $localFilePath, $remoteFilePath, FTP_BINARY);
        while ($ret == FTP_MOREDATA)
        {
            $ret = ftp_nb_continue($conn_id);
        }
    }

    if (file_exists($localFilePath) && (filesize($localFilePath) > 0))
    {
        return $localFilePath;
    }

    return false;
}

function setFileContent($db, $file, $localFilePath)
{
    // figure out server storage setup
    $storageType         = 'local';
    $storageLocation     = LOCAL_STORAGE_DEFAULT_PATH;
    $uploadServerDetails = loadServer($db, $file);
    if ($uploadServerDetails != false)
    {
        $storageLocation = $uploadServerDetails['storagePath'];
        $storageType     = $uploadServerDetails['serverType'];

        // if no storage path set & local, use system default
        if ((strlen($storageLocation) == 0) && ($storageType == 'local'))
        {
            $storageLocation = LOCAL_STORAGE_DEFAULT_PATH;
        }

        if ($storageType == 'direct')
        {
            $storageLocation = LOCAL_STORAGE_DEFAULT_PATH;
        }
    }

    // get subfolder
    $subFolder        = current(explode("/", $file['localFilePath']));
    $originalFilename = end(explode("/", $file['localFilePath']));

    // use ssh to get contents of 'local' files
    if (($storageType == 'local') || ($storageType == 'direct'))
    {
        // get remote file path
        $remoteFilePath = $storageLocation . $file['localFilePath'];
        // first try setting the file locally
        $done           = false;
        if ((ON_SCRIPT_INSTALL == true) && file_exists($remoteFilePath))
        {
            $done = copy($localFilePath, $remoteFilePath);
            if ($done)
            {
                return true;
            }
        }

        // try over ssh
        if ($done == false)
        {
			$sshHost = LOCAL_STORAGE_SSH_HOST;
			$sshUser = LOCAL_STORAGE_SSH_USER;
			$sshPass = LOCAL_STORAGE_SSH_PASS;

			// if 'direct' file server, get SSH details
			$serverDetails = getDirectFileServerSSHDetails($file['serverId']);
			if($serverDetails)
			{
				$sshHost = $serverDetails['ssh_host'];
                $sshPort = $serverDetails['ssh_port'];
				$sshUser = $serverDetails['ssh_username'];
				$sshPass = $serverDetails['ssh_password'];
				$basePath = $serverDetails['file_storage_path'];
				if(substr($basePath, strlen($basePath)-1, 1) == '/')
				{
					$basePath = substr($basePath, 0, strlen($basePath)-1);
				}
				$remoteFilePath = $basePath . '/' . $file['localFilePath'];
			}
            
            if(strlen($sshPort) == 0)
            {
                $sshPort = 22;
            }
			
            // connect to 'local' storage via SSH
            $sftp = new Net_SFTP($sshHost, $sshPort);
            if (!$sftp->login($sshUser, $sshPass))
            {
                output("Error: Failed logging into " . $sshHost . " (Port: ".$sshPort.") via SSH..\n");

                return false;
            }

            // overwrite file
            $rs = $sftp->put($remoteFilePath, $localFilePath, NET_SFTP_LOCAL_FILE);
            if ($rs)
            {
                return true;
            }

            output("Error: Failed uploading converted file to " . LOCAL_STORAGE_SSH_HOST . " (" . $remoteFilePath . ") via SSH..\n");
        }

        return false;
    }

    // ftp
    if ($storageType == 'ftp')
    {
        // setup full path
        $prePath = $uploadServerDetails['storagePath'];
        if (substr($prePath, strlen($prePath) - 1, 1) == '/')
        {
            $prePath = substr($prePath, 0, strlen($prePath) - 1);
        }
        $remoteFilePath = $prePath . '/' . $file['localFilePath'];

        // connect via ftp
        $conn_id = ftp_connect($uploadServerDetails['ipAddress'], $uploadServerDetails['ftpPort'], 30);
        if ($conn_id === false)
        {
            output('Could not connect to ' . $uploadServerDetails['ipAddress'] . ' to upload file.');
            return false;
        }

        // authenticate
        $login_result = ftp_login($conn_id, $uploadServerDetails['ftpUsername'], $uploadServerDetails['ftpPassword']);
        if ($login_result === false)
        {
            output('Could not login to ' . $uploadServerDetails['ipAddress'] . ' with supplied credentials.');
            return false;
        }

        // get content
        $rs = ftp_put($conn_id, $remoteFilePath, $localFilePath, FTP_BINARY);
        if ($rs == true)
        {
            return true;
        }
    }

    output("Error: Failed uploading converted file to " . $uploadServerDetails['ipAddress'] . " via FTP..\n");

    return false;
}

function loadServer($db, $file)
{
    // load the server the file is on
    if ((int) $file['serverId'])
    {
        // load from the db
        $db = dbConnect();
        $stmt                = $db->query("SELECT * FROM file_server WHERE id = " . (int) $file['serverId']);
        $uploadServerDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$uploadServerDetails)
        {
            return false;
        }

        return $uploadServerDetails;
    }

    return false;
}

function getDirectFileServerSSHDetails($serverId)
{
	// get direct file server ssh details
	$directFileServers = unserialize(DIRECT_FILE_SERVER_DETAILS);
	if(COUNT($directFileServers) == 0)
	{
		return false;
	}
	
	foreach($directFileServers AS $directFileServer)
	{
		if($directFileServer['file_server_id'] == $serverId)
		{
			return $directFileServer;
		}
	}
	
	return false;
}

function dbConnect()
{
    return new PDO('mysql:host=' . DATABASE_HOST . ';dbname=' . DATABASE_NAME . ';charset=utf8', DATABASE_USER, DATABASE_PASS);
}