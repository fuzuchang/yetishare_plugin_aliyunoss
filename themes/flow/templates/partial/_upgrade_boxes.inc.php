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
	$prices = $db->getRows('SELECT id, pricing_label, period, price, package_pricing_type, download_allowance FROM user_level_pricing WHERE user_level_id = '.(int)$package_level_id.' ORDER BY price ASC');
	if(COUNT($prices) > 0)
	{
	?>
	<div class="row pricing-table">
		<?php
		$pricingColSizePercent = floor(100/COUNT($prices));
		foreach ($prices AS $k => $price)
		{
			$priceStr = $price['price'];
			$days = 0;
			if($price['package_pricing_type'] == 'period')
			{
				$days = coreFunctions::convertStringDatePeriodToDays($price['period']); // for older plugin gateway code
			}
		?>
			<div class="col-md-5ths col-xs-6" style="width: <?php echo floor($pricingColSizePercent); ?>%;">
				<div class="panel panel-success">
					<div class="panel-heading">
						<h3 class="text-center"><strong><?php echo validation::safeOutputToScreen($price['pricing_label']); ?></strong><br/><?php echo UCWords(t('premium', 'premium')); ?></h3>
					</div>
					<div class="panel-body text-center">
						<p class="lead total-price" style="font-size:40px"><strong><?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo number_format(str_replace(",", "", $priceStr), 2); ?></strong></p>
						<?php if($days > 0): ?>
						<p class="lead price-per-day" style="font-size:16px"><?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo number_format(str_replace(",", "", $priceStr) / $days, 2); ?> <?php echo UCWords(t('upgrade_boxes_per_day', 'per day')); ?></p>
						<?php endif; ?>
					</div>
					<ul class="list-group list-group-flush text-center">
						<li class="list-group-item"><i class="fa fa-lock"></i> <?php echo UCWords(t('secure_payment', 'secure payment')); ?></li>
						<li class="list-group-item"><i class="fa fa-eye-slash"></i> <?php echo UCWords(t('safe_and_anonymous', '100% Safe & Anonymous')); ?></li>
						<li class="list-group-item payment-method"><?php echo UCWords(t('select_payment_method', 'Select Payment Method:')); ?></li>
					</ul>
					<div class="panel-footer">
						<?php
						pluginHelper::outputPaymentLinks(array('days' => $days, 'price' => $price['price'], 'user_level_pricing_id' => $price['id'], 'user_level_package_id' => $package_level_id));
						?>
					</div>
				</div>
			</div>
		<?php
		}
		?>
	</div>
	<?php
	}
}
?>