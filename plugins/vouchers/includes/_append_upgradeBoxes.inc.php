<?php

$pluginConfig = pluginHelper::pluginSpecificConfiguration('vouchers');

$plugin_settings = json_decode($pluginConfig['data']['plugin_settings'], true);
if ($plugin_settings)
{
	$plugin_folder	 = $pluginConfig['data']['folder_name'];
	$disable_selling = $plugin_settings['disable_selling'];
	$dburl			 = $plugin_settings['url'];
}
if($disable_selling == '0')
{	
?>
	<div style="text-align: center; padding: 3px;">
	<a href="<?php echo $dburl; ?>" title="Buy Voucher" target="_blank">
	<img src="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['data']['folder_name']; ?>/assets/img/voucher-buy-now.png" alt="Buy Voucher" width="158" /></a>
	</div>
	<div style="text-align: center; padding: 3px;">
	<a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['data']['folder_name']; ?>/site/redeem.php" title="Redeem Voucher">
	<img src="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['data']['folder_name']; ?>/assets/img/redeem-voucher.png" alt="Redeem Voucher" width="158" /></a>
	</div>
<?php
}
else
{
?>
	<div style="text-align: center; padding: 3px;">
	<a href="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['data']['folder_name']; ?>/site/redeem.php" title="Redeem Voucher">
	<img src="<?php echo PLUGIN_WEB_ROOT; ?>/<?php echo $pluginConfig['data']['folder_name']; ?>/assets/img/redeem-voucher.png" alt="Redeem Voucher" width="158" /></a>
	</div>
<?php
}
?>