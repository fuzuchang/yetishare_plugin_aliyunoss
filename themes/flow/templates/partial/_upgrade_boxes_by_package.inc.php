<style>
/* COMMON PRICING STYLES */
	.panel.price,
	.panel.price>.panel-heading{
		border-radius:0px;
		 -moz-transition: all .3s ease;
		-o-transition:  all .3s ease;
		-webkit-transition:  all .3s ease;
	}
	.panel.price:hover{
		box-shadow: 0px 0px 30px rgba(0,0,0, .2);
	}
	.panel.price:hover>.panel-heading{
		box-shadow: 0px 0px 30px rgba(0,0,0, .2) inset;
	}
	
			
	.panel.price>.panel-heading{
		box-shadow: 0px 5px 0px rgba(50,50,50, .2) inset;
		text-shadow:0px 3px 0px rgba(50,50,50, .6);
	}
		
	.price .list-group-item{
		border-bottom-:1px solid rgba(250,250,250, .5);
	}
	
	.panel.price .list-group-item:last-child {
		border-bottom-right-radius: 0px;
		border-bottom-left-radius: 0px;
	}
	.panel.price .list-group-item:first-child {
		border-top-right-radius: 0px;
		border-top-left-radius: 0px;
	}
	
	.price .panel-footer {
		color: #fff;
		border-bottom:0px;
		background-color:  rgba(0,0,0, .1);
		box-shadow: 0px 3px 0px rgba(0,0,0, .3);
	}
	
	
	.panel.price .btn{
		box-shadow: 0 -1px 0px rgba(50,50,50, .2) inset;
		border:0px;
	}
	
/* green panel */

	
	.price.panel-green>.panel-heading {
		color: #fff;
		background-color: #57AC57;
		border-color: #71DF71;
		border-bottom: 1px solid #71DF71;
	}
	
		
	.price.panel-green>.panel-body {
		color: #fff;
		background-color: #65C965;
	}
			
	
	.price.panel-green>.panel-body .lead{
			text-shadow: 0px 3px 0px rgba(50,50,50, .3);
	}
	
	.price.panel-green .list-group-item {
		color: #333;
		background-color: rgba(50,50,50, .01);
		font-weight:600;
		text-shadow: 0px 1px 0px rgba(250,250,250, .75);
	}
	
	/* blue panel */

	
	.price.panel-blue>.panel-heading {
		color: #fff;
		background-color: #608BB4;
		border-color: #78AEE1;
		border-bottom: 1px solid #78AEE1;
	}
	
		
	.price.panel-blue>.panel-body {
		color: #fff;
		background-color: #73A3D4;
	}
			
	
	.price.panel-blue>.panel-body .lead{
			text-shadow: 0px 3px 0px rgba(50,50,50, .3);
	}
	
	.price.panel-blue .list-group-item {
		color: #333;
		background-color: rgba(50,50,50, .01);
		font-weight:600;
		text-shadow: 0px 1px 0px rgba(250,250,250, .75);
	}
	
	/* red price */
	

	.price.panel-red>.panel-heading {
		color: #fff;
		background-color: #D04E50;
		border-color: #FF6062;
		border-bottom: 1px solid #FF6062;
	}
	
		
	.price.panel-red>.panel-body {
		color: #fff;
		background-color: #EF5A5C;
	}
	
	
	
	
	.price.panel-red>.panel-body .lead{
			text-shadow: 0px 3px 0px rgba(50,50,50, .3);
	}
	
	.price.panel-red .list-group-item {
		color: #333;
		background-color: rgba(50,50,50, .01);
		font-weight:600;
		text-shadow: 0px 1px 0px rgba(250,250,250, .75);
	}
	
	/* grey price */
	

	.price.panel-grey>.panel-heading {
		color: #fff;
		background-color: #6D6D6D;
		border-color: #B7B7B7;
		border-bottom: 1px solid #B7B7B7;
	}
	
		
	.price.panel-grey>.panel-body {
		color: #fff;
		background-color: #808080;
	}
	

	
	.price.panel-grey>.panel-body .lead{
			text-shadow: 0px 3px 0px rgba(50,50,50, .3);
	}
	
	.price.panel-grey .list-group-item {
		color: #333;
		background-color: rgba(50,50,50, .01);
		font-weight:600;
		text-shadow: 0px 1px 0px rgba(250,250,250, .75);
	}
	
	/* white price */
	

	.price.panel-white>.panel-heading {
		color: #333;
		background-color: #f9f9f9;
		border-color: #ccc;
		border-bottom: 1px solid #ccc;
		text-shadow: 0px 2px 0px rgba(250,250,250, .7);
	}
	
	.panel.panel-white.price:hover>.panel-heading{
		box-shadow: 0px 0px 30px rgba(0,0,0, .05) inset;
	}
		
	.price.panel-white>.panel-body {
		color: #fff;
		background-color: #dfdfdf;
	}
			
	.price.panel-white>.panel-body .lead{
			text-shadow: 0px 2px 0px rgba(250,250,250, .8);
			color:#666;
	}
	
	.price:hover.panel-white>.panel-body .lead{
			text-shadow: 0px 2px 0px rgba(250,250,250, .9);
			color:#333;
	}
	
	.price.panel-white .list-group-item {
		color: #333;
		background-color: rgba(50,50,50, .01);
		font-weight:600;
		text-shadow: 0px 1px 0px rgba(250,250,250, .75);
	}
</style>

<?php
// get packages
$packages = $db->getRows('SELECT id, level_id, label, level_type, max_storage_bytes FROM user_level WHERE on_upgrade_page = 1 ORDER BY level_type=\'free\' DESC, level_type=\'paid\' DESC, id ASC');
if ((!$packages) || COUNT($packages) == 0) {
    if (CURRENT_PAGE_KEY != 'index') {
        echo '<p>ERROR: No packages found, please contact the support team. (at least 1 package needs to have the option of \'On Upgrade Page\' to \'Yes\')</p>';
    }
} else {
    echo '<div class="pricing bottommargin clearfix">';
    $colSize = floor(12 / COUNT($packages));
    $foundPaid = false;
    $tracker = 0;
    foreach ($packages AS $k => $package) {
        $mostPopular = false;
        if ($tracker == 1) {
            $mostPopular = true;
        }

        $boxClass = ' panel-blue';
        $boxAddText = '';
        $boxButtonClass = '';
        if ($mostPopular == true) {
            $boxClass = ' best-price panel-green';
            $boxAddText = '<span>' . t('most_popular', 'Most Popular') . '</span>';
            $boxButtonClass = ' bgcolor border-color';
        }

        // prepare package limits for later
        $hdStorage = (int) $package['max_storage_bytes'] == 0 ? UCWords(t('unlimited', 'unlimited')) : coreFunctions::formatSize($package['max_storage_bytes']);

        $footerContent = '';
        $pricePerMonth = '';
        $featuresHtml = '';
        $prices = array();
        switch ($package['level_type']) {
            case 'free':
                $pricePerMonth = strtoupper(t('free', 'free'));
                $featuresHtml = '<li class="list-group-item"><i class="fa fa-hdd-o"></i> ' . $hdStorage . ' ' . t('storage', 'Storage') . '</li>';
                $featuresHtml .= '<li class="list-group-item"><i class="fa fa-user"></i> ' . t('unique_members_area', 'Unique Members Area') . '</li>';
                $featuresHtml .= '<li class="list-group-item"><i class="fa fa-bullhorn"></i> ' . t('advert_supported', 'Advert Supported') . '</li>';

                // different button for logged in users
                if ($Auth->loggedIn()) {
                    $pricingButton = '<a href="' . coreFunctions::getCoreSitePath() . '/account_home.' . SITE_CONFIG_PAGE_EXTENSION . '" class="btn btn-danger btn-block btn-lg' . $boxButtonClass . '">' . strtoupper(t('your_account', 'Your Account')) . '</a>';
                } else {
                    $pricingButton = '<a href="' . coreFunctions::getCoreSitePath() . '/register.' . SITE_CONFIG_PAGE_EXTENSION . '" class="btn btn-danger btn-block btn-lg' . $boxButtonClass . '">' . strtoupper(t('register_now', 'Register Now')) . '</a>';
                }
                break;
            case 'paid':
                // load all prices
                $period = '1M';
                $prices = $db->getRows('SELECT id, pricing_label, period, price FROM user_level_pricing WHERE user_level_id = ' . (int) $package['level_id'] . ' ORDER BY price ASC');
                if (COUNT($prices) > 0) {
                    // get lowest price
                    $lowest = null;
                    foreach ($prices AS $price) {
                        if ($lowest !== null) {
                            continue;
                        }
                        $lowest = $price['price'];
                        $period = $price['period'];
                    }
                    $pricePerMonth = $lowest;
                } else {
                    $pricePerMonth = strtoupper(t('free', 'free'));
                }

                $tracker++;
                if (CURRENT_PAGE_KEY == 'index') {
                    $pricingButton = '<a href="' . coreFunctions::getCoreSitePath() . '/upgrade.' . SITE_CONFIG_PAGE_EXTENSION . '?p=' . (int) $package['id'] . '" class="btn btn-danger btn-block btn-lg' . $boxButtonClass . '">' . strtoupper(t('upgrade_now', 'Upgrade Now'))
                            . '</a>';
                } else {
                    $pricingButton = '<a href="#" class="btn btn-danger btn-block btn-lg' . $boxButtonClass . '" data-toggle="modal" data-target="#pricing' . (int) $package['id'] . '">' . strtoupper(t('upgrade_now', 'Upgrade Now'))
                            . '</a>';
                }
                $foundPaid = true;

                $featuresHtml = '<li class="list-group-item"><i class="fa fa-hdd-o"></i> ' . $hdStorage . ' ' . t('storage', 'Storage') . '</li>';
                $featuresHtml .= '<li class="list-group-item"><i class="fa fa-user"></i> ' . t('unique_members_area', 'Unique Members Area') . '</li>';
                $featuresHtml .= '<li class="list-group-item"><i class="fa fa-check"></i> ' . t('advert_free', 'No Adverts') . '</li>';
                $featuresHtml .= '<li class="list-group-item"><i class="fa fa-lock"></i> ' . t('secure_payment', 'Secure Payment') . '</li>';
                $featuresHtml .= '<li class="list-group-item"><i class="fa fa-eye-slash"></i> ' . t('safe_and_anonymous', '100% Safe & Anonymous') . '</li>';
                if ($mostPopular == true) {
                    $featuresHtml .= '<li class="list-group-item"><i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i><i class="icon-star3"></i></li>';
                }
                break;
        }
        ?>
        <div class="col-md-<?php echo (int) $colSize; ?>">
            <div class="pricing-box<?php echo $boxClass; ?> panel price">
                <div class="panel-heading text-center">
                    <h3><?php echo validation::safeOutputToScreen(UCWords($package['label'])); ?></h3>
        <?php echo $boxAddText; ?>
                </div>
                <div class="panel-body text-center">
        <?php echo ($pricePerMonth == strtoupper(t('free', 'free'))) ? $pricePerMonth : ('<span class="price-unit">' . SITE_CONFIG_COST_CURRENCY_SYMBOL . '</span>' . number_format(str_replace(",", "", $pricePerMonth), 2) . '<span class="price-tenure">/' . $period . '</span>'); ?>
                </div>

        <?php if (strlen($featuresHtml)): ?>
                    <div class="list-group list-group-flush text-center">
            <?php echo $featuresHtml; ?>
                    </div>
        <?php endif; ?>

                <div class="panel-footer">
        <?php echo $pricingButton; ?>
                </div>
            </div>
        </div>
        <?php
        // output html for payment popup
        if ((COUNT($prices) > 0) && (CURRENT_PAGE_KEY != 'index')) {
            echo '<div id="pricing' . $package['id'] . '" class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="z-index:9999; top: 60px;">
  <div class="modal-dialog" style="width: 1100px;">
    <div class="modal-content">
      <div class="modal-body">';
            echo '<div class="row pricing-table pricing-popup">';
            $pricingColSizePercent = floor(100 / COUNT($prices));
            $totalPercent = 0;
            foreach ($prices AS $k => $price) {
                // make sure the last column fills the remaining space
                if (($k + 1) == COUNT($prices)) {
                    $pricingColSizePercent = 100 - $totalPercent;
                }

                $priceStr = $price['price'];
                $days = coreFunctions::convertStringDatePeriodToDays($price['period']); // for older plugin gateway code
                ?>
                <div class="col-md-12" style="width: <?php echo floor($pricingColSizePercent); ?>%;">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <h3 class="text-center"><strong><?php echo validation::safeOutputToScreen($price['pricing_label']); ?></strong> - <?php echo SITE_CONFIG_COST_CURRENCY_SYMBOL; ?><?php echo number_format(str_replace(",", "", $priceStr), 2); ?></h3>
                        </div>
                        <div class="panel-footer">
                <?php
                pluginHelper::outputPaymentLinks(array('days' => $days, 'price' => $price['price'], 'user_level_pricing_id' => $price['id'], 'user_level_package_id' => $package['id']));
                ?>
                        </div>
                    </div>
                </div>
                <?php
                $totalPercent = $totalPercent + $pricingColSizePercent;
            }
            echo '</div>';
            echo '</div>
    </div>
  </div>
</div>';
        }
    }

    echo '<div class="clear"></div>';
}
