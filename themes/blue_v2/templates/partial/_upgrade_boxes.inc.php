<?php
$days = array(7, 30, 90, 180, 365);
// get packages
$package_level_id = $db->getValue('SELECT id FROM user_level WHERE on_upgrade_page = 1 AND level_type = \'paid\' LIMIT 1');
if(!$package_level_id)
{
    echo '<p>ERROR: No packages found, please contact the support team. (at least 1 package needs to have the option of \'On Upgrade Page\' to \'Yes\' with \'Package Type\' of \'Paid\')</p>';
}
else
{
	// load all prices
	$prices = $db->getRows('SELECT id, pricing_label, period, price FROM user_level_pricing WHERE user_level_id = '.(int)$package_level_id.' ORDER BY price ASC');
	if(COUNT($prices) > 0)
	{
		$pricingColSizePercent = floor(100/COUNT($prices));
		foreach ($prices AS $k => $price)
		{
			$priceStr = $price['price'];
			$days = coreFunctions::convertStringDatePeriodToDays($price['period']); // for older plugin gateway code
		?>
		<div class="upgradeSection upgradeBox <?php echo $k==(COUNT($prices)-1)?'last':''; ?>" style="width: <?php echo floor($pricingColSizePercent)-2; ?>%;">
			<div class="upgradeContent ui-corner-all">
				<div class="upgradeContentInternal">
					<div class="period">
						<?php echo validation::safeOutputToScreen($price['pricing_label']); ?>
					</div>
					<div class="clear"></div>
					<div class="premium">
						<?php echo UCWords(t('premium', 'premium')); ?>
					</div>
					<div class="clear"></div>
					<div class="totalPrice">
						<?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo number_format(str_replace(",", "", $priceStr), 2); ?>
					</div>
					<div class="clear"></div>
					<div class="pricePerDay">
						<?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo number_format(str_replace(",", "", $priceStr) / $days, 2); ?> <?php echo UCWords(t('upgrade_boxes_per_day', 'per day')); ?>
					</div>
					<div class="clear"></div>
					<?php
					pluginHelper::outputPaymentLinks(array('days' => $days, 'price' => $price['price'], 'user_level_pricing_id' => $price['id'], 'user_level_package_id' => $package_level_id));
					?>
					<div class="clear"></div>
					<div class="secure">
						<img src="<?php echo SITE_IMAGE_PATH; ?>/icon_padlock.gif" width="12" height="12" alt="<?php echo UCWords(t('secure_payment', 'secure payment')); ?>" style="vertical-align:middle;"/>
						<span style="vertical-align: middle;">&nbsp;<?php echo UCWords(t('safe_and_anonymous', '100% Safe & Anonymous')); ?></span>
					</div>
					<div class="clear"></div>
				</div>
			</div>
		</div>
		<?php
		}
		?>
	<div class="clear"></div>
	<?php
	}
}
?>