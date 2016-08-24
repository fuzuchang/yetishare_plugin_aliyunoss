<?php

// require login
$Auth->requireUser(WEB_ROOT . '/login.' . SITE_CONFIG_PAGE_EXTENSION);

// some initial headers
header("HTTP/1.0 200 OK");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

// setup initial params
$pageTitle = t('your_account', 'Your Account');
$pageUrl = WEB_ROOT.'/account_home.html';
$s = (int)$_REQUEST['pageStart'];
$l = (int)$_REQUEST['perPage']>0?(int)$_REQUEST['perPage']:30;
$sortCol = $_REQUEST['filterOrderBy'];
$filterUploadedDateRange = strlen($_REQUEST['filterUploadedDateRange'])?$_REQUEST['filterUploadedDateRange']:null;
$sSearch = trim($_REQUEST['filterText']);
$nodeId = $_REQUEST['nodeId'];

$db = Database::getDatabase(true);
$filesClause = "WHERE userId = " . (int)$Auth->id;
$foldersClause = "WHERE userId = " . (int)$Auth->id;
if(strlen($sSearch))
{
    $filesClause .= " AND (originalFilename LIKE '%".$db->escape($sSearch)."%' OR shortUrl LIKE '%".$db->escape($sSearch)."%')";
	$foldersClause .= " AND (file_folder.folderName LIKE '%".$db->escape($sSearch)."%')";
}

$sortColNameFiles = 'originalFilename';
$sortDirFiles = 'asc';
switch($sortCol)
{
    case 'order_by_filename_asc':
        $sortColNameFiles = 'originalFilename';
        $sortDirFiles = 'asc';
        break;
    case 'order_by_filename_desc':
        $sortColNameFiles = 'originalFilename';
        $sortDirFiles = 'desc';
        break;
    case 'order_by_uploaded_date_asc':
        $sortColNameFiles = 'uploadedDate';
        $sortDirFiles = 'asc';
        break;
    case 'order_by_uploaded_date_desc':
        $sortColNameFiles = 'uploadedDate';
        $sortDirFiles = 'desc';
        break;
    case 'order_by_downloads_asc':
        $sortColNameFiles = 'visits';
        $sortDirFiles = 'asc';
        break;
    case 'order_by_downloads_desc':
        $sortColNameFiles = 'visits';
        $sortDirFiles = 'desc';
        break;
    case 'order_by_filesize_asc':
        $sortColNameFiles = 'fileSize';
        $sortDirFiles = 'asc';
        break;
    case 'order_by_filesize_desc':
        $sortColNameFiles = 'fileSize';
        $sortDirFiles = 'desc';
        break;
    case 'order_by_last_access_date_asc':
        $sortColNameFiles = 'lastAccessed';
        $sortDirFiles = 'asc';
        break;
    case 'order_by_last_access_date_desc':
        $sortColNameFiles = 'lastAccessed';
        $sortDirFiles = 'desc';
        break;
}

// for recent uploads
if($nodeId == 'recent')
{
	$pageTitle = t('recent_files', 'Recent Files');
    $sortColNameFiles = 'uploadedDate';
    $sortDirFiles = 'desc';
	$foldersClause .= ' AND 1=2'; // disable
}

// all files
if($nodeId == 'all')
{
	$pageTitle = t('all_files', 'All Files');
	$foldersClause .= ' AND 1=2'; // disable
}

// trash can
if($nodeId == 'trash')
{
    $filesClause .= " AND statusId != 1";
	$foldersClause .= ' AND 1=2'; // disable
}
else
{
    $filesClause .= " AND statusId = 1";
}

// root folder listing
if($nodeId == -1)
{
	$pageTitle = t('file_manager', 'File Manager');
    $filesClause .= " AND folderId IS NULL";
    $foldersClause .= " AND file_folder.parentId IS NULL";
}

// folder listing
if((int)$nodeId > 0)
{
	$folder = fileFolder::loadById($nodeId);
	if($folder)
	{
		$pageTitle = $folder->folderName;
		$pageUrl = $folder->getFolderUrl();
	}
    $filesClause .= " AND folderId = ".(int)$nodeId;
    $foldersClause .= " AND file_folder.parentId = ".(int)$nodeId;
}

// filter by date range
if($filterUploadedDateRange !== null)
{
    // validate date
    $expDate = explode('|', $filterUploadedDateRange);
    if(COUNT($expDate) == 2)
    {
        $startDate = $expDate[0];
        $endDate = $expDate[1];
    }
    else
    {
        $startDate = $expDate[0];
        $endDate = $expDate[0];
    }

    if((validation::validDate($startDate, 'Y-m-d')) && (validation::validDate($endDate, 'Y-m-d')))
    {
        // dates are valid
        $filesClause .= " AND UNIX_TIMESTAMP(uploadedDate) >= ".coreFunctions::convertDateToTimestamp($startDate, SITE_CONFIG_DATE_FORMAT)." AND UNIX_TIMESTAMP(uploadedDate) <= ".(coreFunctions::convertDateToTimestamp($endDate, SITE_CONFIG_DATE_FORMAT)+(60*60*24)-1);
    }
}

// get file total for this account and filter
$allStatsFiles = $db->getRow('SELECT COUNT(id) AS totalFileCount, SUM(fileSize) AS totalFileSize FROM file ' . $filesClause);
$allStatsFolders = $db->getRow("SELECT COUNT(id) AS totalFolderCount FROM file_folder ".$foldersClause);

// load folders
$folders = $db->getRows("SELECT file_folder.id, file_folder.parentId, file_folder.folderName, file_folder.isPublic, (SELECT COUNT(file.id) AS fileCount FROM file WHERE file.folderId = file_folder.id AND statusId = 1) AS fileCount FROM file_folder ". $foldersClause ." ORDER BY folderName ASC LIMIT ".$s.", ".$l);

// allow for folders in paging
$newStart = floor($s - $allStatsFolders['totalFolderCount']);
if($newStart < 0)
{
	$newStart = 0;
}
$newLimit = $l - COUNT($folders);
$limit = ' LIMIT ' . $newStart . ',' . $newLimit;

// load limited page filtered
$files = $db->getRows('SELECT * FROM file ' . $filesClause . ' ORDER BY ' . $sortColNameFiles . ' ' . $sortDirFiles . $limit);

if (($files) || ($folders))
{
    echo '<ul class="fileListing">';    
    // header for list view
    echo '<li class="fileListingHeader">';
    echo '<span class="filesize">'.UCWords(t('filesize', 'filesize')).'</span>';
    echo '<span class="fileUploadDate">'.UCWords(t('added', 'added')).'</span>';
    echo '<span class="downloads">'.UCWords(t('downloads', 'downloads')).'</span>';
    echo '<span class="filename">'.UCWords(t('filename', 'filename')).'</span>';
    echo '</li>';
    
    if($folders)
    {
        foreach ($folders AS $folder)
        {
			// hydrate folder
			$folderObj = fileFolder::hydrate($folder);
			
            echo '<li id="folderItem'.(int)$folderObj->id.'" data-clipboard-action="copy" data-clipboard-target="#clipboard-placeholder" title="'.validation::safeOutputToScreen($folder['folderName']).' '.($folder['fileCount'] > 0 ? "(".$folder['fileCount'].")" : "").'" class="fileItem folderIconLi" folderId="'.$folder['id'].'" dtfoldername="'.validation::safeOutputToScreen($folder['folderName']).'" sharing-url="'.$folderObj->getFolderUrl().'">';
            echo '<div class="thumbIcon" style="cursor: pointer;" onClick="loadFolderFiles('.$folder['id'].'); return false;">';
            if($folder['fileCount'] == 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_fm_grid.png" />';
            }
            elseif($folder['fileCount'] > 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_grid.png" />';
            }
            elseif($folder['fileCount'] >= 0 && $folder['isPublic'] == 0)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_lock_fm_grid.png" />';
            }
            else
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_grid.png" />';
            }
            echo '</div>';
            echo '<div class="thumbList" style="cursor: pointer;" onClick="loadFolderFiles('.$folder['id'].'); return false;">';
            if($folder['fileCount'] == 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_fm_list.png" />';
            }
            elseif($folder['fileCount'] > 0 && $folder['isPublic'] == 1)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_list.png" />';
            }
            elseif($folder['fileCount'] >= 0 && $folder['isPublic'] == 0)
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_lock_fm_list.png" />';
            }
            else
            {
                echo '<img src="'.SITE_IMAGE_PATH.'/folder_full_fm_list.png" />';
            }            
            echo '</div>';
            echo '<span class="filename" style="cursor: pointer;" onClick="loadFolderFiles('.$folder['id'].'); return false;">'.validation::safeOutputToScreen($folder['folderName']).' '.($folder['fileCount'] > 0 ? "(".$folder['fileCount'].")" : "").$rspTotalPages.'</span>';
			
			echo '  <div class="fileOptions">';
			echo '    <a class="fileDownload" href="#"><i class="entypo-dot-3"></i></a>';
			echo '  </div>';
            echo '</li>';
        }
    }

	// output data
	if($files)
    {
		foreach ($files AS $file)
		{
			$fileObj = file::hydrate($file);
			$previewImageUrlLarge = file::getIconPreviewImageUrl($file, false, 48, false, 138, 116);
			$previewImageUrlMedium = file::getIconPreviewImageUrlMedium($file);
			
			$extraMenuItems = array();
			$params  = pluginHelper::includeAppends('account_home_file_list_menu_item.php', array('fileObj' => $fileObj, 'extraMenuItems' => $extraMenuItems));
			$extraMenuItems = $params['extraMenuItems'];

			$menuItemsStr = '';
			if(COUNT($extraMenuItems))
			{
				$menuItemsStr = json_encode($extraMenuItems);
			}

			echo '<li dttitle="'.validation::safeOutputToScreen($file['originalFilename']).'" dtsizeraw="'.validation::safeOutputToScreen($file['fileSize']).'" dtuploaddate="'.validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])).'" dtfullurl="'.validation::safeOutputToScreen($fileObj->getFullShortUrl()).'" dtfilename="'.validation::safeOutputToScreen($file['originalFilename']).'" dtstatsurl="'.validation::safeOutputToScreen($fileObj->getStatisticsUrl()).'" dturlhtmlcode="'.validation::safeOutputToScreen($fileObj->getHtmlLinkCode()).'" dturlbbcode="'.validation::safeOutputToScreen($fileObj->getForumLinkCode()).'" dtextramenuitems="'.validation::safeOutputToScreen($menuItemsStr).'" title="'.validation::safeOutputToScreen($file['originalFilename']).' ('.validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])).')" fileId="'.$file['id'].'" class="image-thumb fileItem'.$file['id'].' fileIconLi '.($file['statusId']!=1?'fileDeletedLi':'').'" onDblClick="dblClickFile('.$file['id'].'); return false;">';
			echo '<span class="filesize">'.validation::safeOutputToScreen(coreFunctions::formatSize($file['fileSize'])).'</span>';
			echo '<span class="fileUploadDate">'.validation::safeOutputToScreen(coreFunctions::formatDate($file['uploadedDate'])).'</span>';
			echo '<span class="downloads">'.validation::safeOutputToScreen($file['visits']).'</span>';
			echo '<div class="thumbIcon">';
			echo '<a name="link"><img src="'.((substr($previewImageUrlLarge, 0, 4)=='http')?$previewImageUrlLarge:(SITE_IMAGE_PATH.'/trans_1x1.gif')).'" alt="" class="'.((substr($previewImageUrlLarge, 0, 4)!='http')?$previewImageUrlLarge:'#').'"></a>';
			echo '</div>';
			echo '<div class="thumbList">';
			echo '<a name="link"><img src="'.$previewImageUrlMedium.'" alt=""></a>';
			echo '</div>';
			echo '<span class="filename">'.validation::safeOutputToScreen($file['originalFilename']).'</span>';
			
			echo '  <div class="fileOptions">';
			echo '    <a class="fileDownload" href="#"><i class="entypo-dot-3"></i></a>';
			echo '  </div>';
			echo '</li>';
		}
	}
	
    echo '</ul>';
}
else
{
    echo '<h2>'.t('no_files_found', 'No files found.').' '.t('click_to_upload', 'Click to <a href="#" onClick="uploadFiles(); return false;">Upload</a>').'</h2>';
}

// stats
echo '<input id="rspFolderTotalFiles" value="'.((int)$allStatsFiles['totalFileCount']+(int)$allStatsFolders['totalFolderCount']).'" type="hidden"/>';
echo '<input id="rspFolderTotalSize" value="'.$allStatsFiles['totalFileSize'].'" type="hidden"/>';
echo '<input id="rspTotalPerPage" value="'.(int)$l.'" type="hidden"/>';
echo '<input id="rspTotalResults" value="'.((int)$allStatsFiles['totalFileCount']+(int)$allStatsFolders['totalFolderCount']).'" type="hidden"/>';
echo '<input id="rspCurrentStart" value="'.(int)$s.'" type="hidden"/>';
echo '<input id="rspCurrentPage" value="'.ceil(((int)$s+(int)$l)/(int)$l).'" type="hidden"/>';
echo '<input id="rspTotalPages" value="'.ceil(((int)$allStatsFiles['totalFileCount']+(int)$allStatsFolders['totalFolderCount'])/(int)$l).'" type="hidden"/>';

echo '<input id="rspPageTitle" value="'.validation::safeOutputToScreen($pageTitle).'" type="hidden"/>';
echo '<input id="rspPageUrl" value="'.validation::safeOutputToScreen($pageUrl).'" type="hidden"/>';