<?php

include_once('/home/resasundoro/public_html/core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');
include_once('../includes/functions.php');
if(isset($_REQUEST['submit']))
{
	// Remove users premium status
	$id = $_REQUEST['id'];
	$id = is_array($id) ? $id : explode(',', $id);
	foreach($id as $fraud)
	{
		$db = Database::getDatabase();
		$vc = $db->getRow("SELECT * FROM plugin_vouchers_logs WHERE id = '$fraud'");
		if($vc)
		{
			$users = $vc['user'];
			$usr = $db->getRow("SELECT * FROM users WHERE id = '$users' LIMIT 1");
			if($usr['level'] != 'admin')
			{
				$db->query("UPDATE users SET level = 'free user', paidExpiryDate = '0000-00-00 00:00:00' WHERE id = '$users'");
				$db->query("UPDATE plugin_vouchers_logs SET fraud = '1' WHERE id = '$fraud'");
			}
		}
	}
}
	$code = trim($_GET['c']);

	echo '<!DOCTYPE HTML>
	<html>
	<title>Redeemed Voucher Information</title>
	<style type="text/css">
	@import url('.ADMIN_ROOT.'"/assets/style/style.css");
	</style>
	<link rel="stylesheet" href="'.ADMIN_WEB_ROOT.'/assets/styles/style.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="'.ADMIN_WEB_ROOT.'/assets/styles/global.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="'.ADMIN_WEB_ROOT.'/assets/styles/config.css" type="text/css" media="screen" />
	<body>
	<form action="view_vouchers.users.php?c='.$code.'" method="post" enctype="multipart/form-data" name="theform" id="theform"> 
	<table width="100%" border="0">
	<tr>
		<td colspan="3" align="center" class="fileTable regular" style="background-color:#B0E9E9; text-align:center;">Viewing Users for Voucher: '.$code.'</td>
	</tr>
	<tr>
		<td width="20%" align="center" class="fileTable regular" style="background-color:#B0E9E9">Date</td>
		<td width="65%" align="center" class="fileTable regular" style="background-color:#B0E9E9">Username</td>
		<td width="10%" align="center" class="fileTable regular" style="background-color:#B0E9E9">Select</td>
	</tr>';

	$db = Database::getDatabase();
	$results = $db->getRows("SELECT * FROM plugin_vouchers_logs WHERE code = '$code'");

	foreach($results as $result)
	{
		$getUser = $db->getRow("SELECT * FROM users WHERE id = '".$result['user']."'");
		echo '<tr>';
		echo '<td width="20%" class="discreet" align="center">'.date('d/m/Y', $result['date']).'</td>';
		
		if($result['fraud'] == '1')
		{
			echo '<td width="65%" class="discreet" align="center" style="background-color:#FFDFDF;" title="Fraudulent User">';
			echo '<span style="text-decoration:line-through;">'.$getUser['username'].'</span>';
		}
		else
		{
			echo '<td width="65%" class="discreet" align="center">';
			echo $getUser['username'];
		}
		echo '</td>';
		echo '<td width="10%" class="discreet" align="center"><input type="checkbox" name="id[]" id="id[]" value="'.$result['id'].'"/></td>';
		echo '</tr>';
	}

	echo '</table>';
	echo '<div>&nbsp;</div>';
	echo '<div align="right">';
	echo '<input type="submit" name="submit" id="submit" value="Mark As Fraudulent" title="If a user is known to have used a fraudulently obtained voucher, you can mark them as a fraud user." class="button blue" onClick="return confirm(\'Are you sure you want to mark this user as fraudulent?\');" />';
	echo '</div>';
	echo '</form>';
	echo '<div>&nbsp;</div>';
	echo '<div align="center"><a href="JavaScript:window.close()"><strong>Close Window</strong></a></div>';
	echo '<div>&nbsp;</div>';
	echo '<div>&nbsp;</div>';
	echo '</body>';
	echo '</html>';
?>