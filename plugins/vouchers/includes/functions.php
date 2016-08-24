<?php

/*
$total = codes to create
$length = num dayes to upgrade for
$exp date = expiry date
$chars = use special chars
*/
function create_voucher_codes($total, $voucherLength, $length, $expiry, $chars) 
{
	$db = Database::getDatabase();
	$voucherLength = $voucherLength;
	$chars = $chars;
	$voucher = array();
	for ($i=0; $i<$total; $i++) 
	{
		$voucher[] = generateVoucher($voucherLength, $chars);
	}
	foreach($voucher as $v)
	{
		echo $v."\n";
		$db->query("INSERT INTO plugin_vouchers (voucher, length, expiry_date, redeemed, max_uses) VALUES ('$v', '$length', '$expiry', '0', '1');");
	}
}

function makesafe($unsafe)
{
	$db = Database::getDatabase();
	$safer = strip_tags($unsafe);
	$safe  = $db->escape($safer);
	return $safe;
}

function generateVoucher($length, $chars) 
{
	if(!$length)
	{
		$length = 12;
	}
	$voucher = "";	
	$possible = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if($chars == '1')
	{
		$possible .= "#*$@&";
	}
	$maxlength = strlen($possible);  
	if($length > $maxlength) 
	{
		$length = $maxlength;
	}
	$i = 0; 
	while($i < $length) 
	{ 
		$char = substr($possible, mt_rand(0, $maxlength-1), 1);
		if(!strstr($voucher, $char)) 
		{
			$voucher .= $char;
			$i++;
		}
	}
	return $voucher;
}

function check_valid_code($code, $userId)
{
	$db			= Database::getDatabase();
	$row		= $db->getRow("SELECT * FROM plugin_vouchers WHERE voucher = '$code' LIMIT 1");
	$max_uses	= $row['max_uses'];
	$times_used	= $row['times_used'];
	$redeemed	= $row['redeemed'];
	$days_valid	= $row['length'];
	$expires	= $row['expiry_date'];
	$unlimited	= $row['unlimited'];
	$now		= time();
	$code		= str_replace(' ', '', $code);

	$checkUsed = $db->getRow("SELECT * FROM plugin_vouchers_logs WHERE code = '$code' AND user = '$userId'");
	if($checkUsed == true)
	{
		$page = "redeem.".SITE_CONFIG_PAGE_EXTENSION.'?ss='.urlencode('You have already used this code, codes can only be used once per member.');
		echo '<meta http-equiv="refresh" content="0;url='.$page.'"/>';
		exit;
	}
	if($expires <= $now)
	{
		$page = "redeem.".SITE_CONFIG_PAGE_EXTENSION.'?ss='.urlencode('Sorry, that voucher has past it\'s expiry date.');
		$db->query("UPDATE plugin_vouchers SET redeemed = '1' WHERE voucher = '$code'");
		echo '<meta http-equiv="refresh" content="0;url='.$page.'"/>';
		exit;
	}
	if($redeemed == '1' || $redeemed == '2')
	{
		$page = "redeem.".SITE_CONFIG_PAGE_EXTENSION.'?ss='.urlencode('Sorry, that voucher has already been used.');
		$db->query("UPDATE plugin_vouchers SET redeemed = '1' WHERE voucher = '$code'");
		echo '<meta http-equiv="refresh" content="0;url='.$page.'"/>';
		exit;
	}

	$count = $db->numRows("SELECT * FROM plugin_vouchers_logs WHERE code = '$code'");
	if($count)
	{ 
		if($count >= $max_uses)
		{
			$page = "redeem.".SITE_CONFIG_PAGE_EXTENSION.'?ss='.urlencode('Sorry, that voucher has already been used the maximum amount of times allowed.');
			$db->query("UPDATE plugin_vouchers SET redeemed = '1' WHERE voucher = '$code'");
			echo '<meta http-equiv="refresh" content="0;url='.$page.'"/>';
			exit;
		}
	}
	if($unlimited == '1')
	{
		return true;
	}
}

function use_valid_code($code, $userId, $numdays)
{
	$db			= Database::getDatabase();
	$row		= $db->getRow("SELECT * FROM plugin_vouchers WHERE voucher = '$code' LIMIT 1");
	$max_uses	= $row['max_uses'];
	$times_used	= $row['times_used'];
	$redeemed	= $row['redeemed'];
	$days_valid	= $row['length'];
	$expires	= $row['expiry_date'];
	$unlimited	= $row['unlimited'];

	$secondCheck = $db->getRow("SELECT * FROM plugin_vouchers_logs WHERE code = '$code' AND user = '$userId'");
	if($secondCheck)
	{
		$page = "redeem.".SITE_CONFIG_PAGE_EXTENSION.'?ss='.urlencode('You have already used this code, codes can only be used once per member.');
		echo '<meta http-equiv="refresh" content="0;url='.$page.'"/>';
		exit;
	}
	$count = $db->numRows("SELECT * FROM plugin_vouchers_logs WHERE code = '$code'");
	if($count)
	{
		if($unlimited === '0' && $count >= $max_uses)
		{
			$page = "redeem.".SITE_CONFIG_PAGE_EXTENSION.'?ss='.urlencode('Sorry, that voucher has already been used the maximum amount of times allowed.');
			$db->query("UPDATE plugin_vouchers SET redeemed = '1' WHERE voucher = '$code'");
			echo '<meta http-equiv="refresh" content="0;url='.$page.'"/>';
			exit;
		}
	}
	$newTimesUsed = $times_used+1;
	$user = $db->getRow("SELECT * FROM users WHERE id = '$userId' LIMIT 1");
	if($user['paidExpiryDate'] === '0000-00-00 00:00:00' || $user['paidExpiryDate'] == '' || !$user['paidExpiryDate'])
	{
		$existingExpiryDate = time();
	}
	else
	{
		$existingExpiryDate	= strtotime($user['paidExpiryDate']);
	}
	$newExpiryDate	= $existingExpiryDate + ($numdays * (60 * 60 * 24));
	$dbExpiryDate	= date("Y-m-d 00:00:00", $newExpiryDate);

	if($unlimited === '1')
	{
		$affectedRows = $db->query("UPDATE plugin_vouchers SET times_used = '$newTimesUsed' WHERE voucher = '$code'");
		$affectedRows = $db->query("INSERT INTO plugin_vouchers_logs (code, user, date) VALUES ('".$code."', '".$userId."', '".time()."')");
		if($user['level_id'] === '20') 
		{
			$effectedRows = $db->query("UPDATE users SET paidExpiryDate = '$dbExpiryDate' WHERE id = '$userId'");
		}
		else
		{
			$effectedRows = $db->query("UPDATE users SET level_id = '2', paidExpiryDate = '$dbExpiryDate' WHERE id = '$user'");
		}
	}
	else
	{
		if($count <= $max_uses)
		{
			$affectedRows = $db->query("UPDATE plugin_vouchers SET times_used = '$newTimesUsed' WHERE voucher = '$code'");
		}
		else
		{
			$affectedRows = $db->query("UPDATE plugin_vouchers SET times_used = '$newTimesUsed', redeemed = '1' WHERE voucher = '$code'");
		}
		$affectedRows = $db->query("INSERT INTO plugin_vouchers_logs (code, user, date) VALUES ('".$code."', '".$userId."', '".time()."')");

		if($user['level_id'] === '20') 
		{
			$effectedRows = $db->query("UPDATE users SET paidExpiryDate = '$dbExpiryDate' WHERE id = '$userId'");
		}
		else
		{
			$effectedRows = $db->query("UPDATE users SET level_id = '2', paidExpiryDate = '$dbExpiryDate' WHERE id = '$userId'");
		}
	}
	if($affectedRows === false)
	{
		echo '<p>'. t('voucher_code_error', 'Sorry, but there has been an error, please go back and try again').'.</p>';
	}
	else
	{
		if ($effectedRows === false)
		{
			echo '<p>'. t('voucher_code_error', 'Sorry, but there has been an error, please go back and try again').'.</p>';
		}
		else
		{
			$page = "redeem.".SITE_CONFIG_PAGE_EXTENSION.'?ss='.urlencode('Your account has been upgraded and will now expire on '.date('d/m/Y', $newExpiryDate));
			echo '<meta http-equiv="refresh" content="0;url='.$page.'"/>';
		}
	}
}


?>