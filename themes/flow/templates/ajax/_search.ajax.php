<?php

if(SITE_CONFIG_ENABLE_FILE_SEARCH == 'no')
{
	// this page can not be accessed
	coreFunctions::output404();
}

// setup initial params
$s = (int)$_REQUEST['iDisplayStart'];
$l = (int)$_REQUEST['iDisplayLength'];
$db = Database::getDatabase();

// create json response
$jsonRs = array();

// document types
require_once(SITE_TEMPLATES_PATH . '/partial/_search_document_types.inc.php');

// prepare clause
$clause = 'WHERE ';
$clause .= 'statusId = 1 ';
$clause .= 'AND file.isPublic > 0 ';

// only files in public folders
$publicFolderClause = 'SELECT ff1.id FROM file_folder ff1 WHERE ff1.isPublic > 0 AND IF(ff1.parentId IS NULL, 1, (SELECT IF(ff2.isPublic = 0, 0, IF(ff2.parentId IS NULL, ff2.isPublic, (SELECT ff3.isPublic FROM file_folder ff3 WHERE ff3.id = ff2.parentId))) FROM file_folder ff2 WHERE ff2.id = ff1.parentId)) > 0';
$clause .= 'AND (file.folderId IN ('.$publicFolderClause.') OR (file.folderId IS NULL))';

// only files in users which have opted to share them
$clause .= 'AND (file.userId IN (SELECT id FROM users WHERE isPublic = 1) OR (file.userId IS NULL))';

// filterText
$filterText = '';
if(isset($_REQUEST['filterText']))
{
	$filterText = $_REQUEST['filterText'];
	$filterText = str_replace(array('"', '%', '*'), '', $filterText);
}
if(strlen($filterText))
{
	$clause .= 'AND (file.shortUrl = '.$db->quote($filterText).' OR file.originalFilename LIKE "%'.$db->escape($filterText).'%") ';
}

// load stats for tabs
$jsonRs['iTotalAll'] = $db->getValue('SELECT COUNT(id) AS total FROM file '.$clause);
foreach($documentTypes AS $documentType=>$documentExt)
{
	$arrKey = 'iTotal'.UCFirst($documentType);
	$jsonRs[$arrKey] = $db->getValue('SELECT COUNT(id) AS total FROM file '.$clause.' AND extension IN ("'.implode('","', explode(',', $documentExt)).'")');
}

// filterType
$filterType = '';
if(isset($_REQUEST['filterType']))
{
	$filterType = $_REQUEST['filterType'];
}

// make filterType safe
$filterTypeArr = array();
if(strlen($filterType))
{
	if(isset($documentTypes[$filterType]))
	{
		$filterTypeExp = explode(',', $documentTypes[$filterType]);
		foreach($filterTypeExp AS $filterTypeExpItem)
		{
			// limit length
			$filterTypeExpItem = substr($filterTypeExpItem, 0, 10);
			$filterTypeExpItem = strtolower($filterTypeExpItem);
			
			// remove unwanted characters
			$filterTypeExpItem = str_replace(array(',',')','(','$','"','\'','&','-','*','%'), '', $filterTypeExpItem);
			if(strlen($filterTypeExpItem))
			{
				$filterTypeArr[] = $filterTypeExpItem;
			}
		}
	}
}
if(COUNT($filterTypeArr))
{
	$clause .= 'AND extension IN ("'.implode('","', $filterTypeArr).'") ';
}

// load all
$totalResults = $db->getValue('SELECT COUNT(id) AS total FROM file '.$clause);

// load filtered
$results = $db->getRows('SELECT * FROM file '.$clause.'ORDER BY uploadedDate DESC LIMIT '.$s.','.$l);

$data = array();
if ($results)
{
    foreach ($results AS $result)
    {
		$fileObj = file::hydrate($result);
		$previewImageUrlMedium = file::getIconPreviewImageUrl($result, false, 160, false, 68, 68, 'middle');

        $lrs = array();

		$cell1 = '';
		$cell1 .= '<div class="start-icon"><a href="'.validation::safeOutputToScreen($fileObj->getFullShortUrl()).'" target="_blank"><img src="'.$previewImageUrlMedium.'" alt="'.validation::safeOutputToScreen($result['extension']).'" title="'.validation::safeOutputToScreen($result['extension']).'"/></a></div>';
		
		$cell1 .= '<div class="main-text">';
		$cell1 .= '<h6><a href="'.validation::safeOutputToScreen($fileObj->getFullShortUrl()).'" target="_blank">'.validation::safeOutputToScreen($result['originalFilename']).'</a></h6>';
		$cell1 .= '<a class="resultUrl" href="'.validation::safeOutputToScreen($fileObj->getFullShortUrl()).'" target="_blank">'.$fileObj->getFullShortUrl().'</a>';
        $cell1 .= '<p>'.t('search_date_uploaded', 'Dated Uploaded').': '.validation::safeOutputToScreen(coreFunctions::formatDate($result['uploadedDate'], SITE_CONFIG_DATE_FORMAT)).'&nbsp;&nbsp;'.t('search_filesize', 'Filesize').': '.validation::safeOutputToScreen(coreFunctions::formatSize($result['fileSize'])).'<p>';
		$cell1 .= '</div>';
		
		$lrs[] = $cell1;
		$lrs[] = '<a href="'.validation::safeOutputToScreen($fileObj->getFullShortUrl()).'" target="_blank" class="btn btn-primary btn-file-download">'.t('download', 'download').'&nbsp;&nbsp;<i class="fa fa-download"></i></a>';
		
        $data[] = $lrs;
    }
}

$jsonRs['sEcho']                = intval($_GET['sEcho']);
$jsonRs['iTotalRecords']        = $totalResults;
$jsonRs['iTotalDisplayRecords'] = $totalResults;
$jsonRs['aaData']               = $data;

echo json_encode($jsonRs);
