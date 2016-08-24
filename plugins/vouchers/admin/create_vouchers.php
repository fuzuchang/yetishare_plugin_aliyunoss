<?php

// initial constants
define('ADMIN_SELECTED_PAGE', 'plugins');
include_once('/home/resasundoro/public_html/core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');
include_once('../includes/functions.php');
define('ADMIN_PAGE_TITLE', 'Create Voucher Codes');

	// handle page submissions
	if (isset($_REQUEST['error']))
	{
		adminFunctions::setError(urldecode($_REQUEST['error']));
	}
	elseif (isset($_REQUEST['s']))
	{
		adminFunctions::setSuccess(urldecode($_REQUEST['s']));
	}
	if(isset($_REQUEST['submitcustom']) && $_REQUEST['custom'])
	{
		if($_REQUEST['custom_text'] == '' || !$_REQUEST['custom_text'])
		{
			adminFunctions::setError(adminFunctions::t("voucher_custom_no_voucher", "You did not enter anything for your custom voucher."));
		}
		else
		{
			$custom_voucher = strtoupper(trim($_REQUEST['custom_text']));
			$custom_voucher	= str_replace(' ', '', $custom_voucher);
			$custom_expire  = $_REQUEST['custom_expire'];
			$custom_usage	= $_REQUEST['custom_uses'];
			$custom_valid   = $_REQUEST['custom_valid'];

			if(strlen($custom_expire))
			{
				$expl = explode(" ", $custom_expire);
				$expl  = explode("/", $expl[0]);
				if(COUNT($expl) != 3)
				{
					adminFunctions::setError(adminFunctions::t("voucher_expiry_invalid_dd_mm_yy", "Voucher expiry date invalid, it should be in the format dd/mm/yyyy"));
				}
				else
				{
					$dbCustomExpiryDate = $expl[2] . '-' . $expl[1] . '-' . $expl[0] . ' 00:00:00';
					$customExpiryDate = strtotime($dbCustomExpiryDate);
				}
			}
			else
			{
				$customExpiryDate = '4070930400';
			}
			if(!$custom_usage || $custom_usage == '' || empty($custom_usage))
			{
				$unlimited = '1';
			}
			else
			{
				$unlimited = '0';
			}
			$sql = "INSERT INTO plugin_vouchers (voucher, length, expiry_date, redeemed, max_uses, unlimited) VALUES ('$custom_voucher', '$custom_valid', '$customExpiryDate', '0', '$custom_usage', '$unlimited');";
			$affectedRows = $db->query($sql);
			if ($affectedRows === false)
			{
				//error
				adminFunctions::redirect('create_vouchers.php?error='.urlencode('There was a problem creating the voucher.'));
			}
			else
			{
				// success
				adminFunctions::redirect('create_vouchers.php?s='.urlencode('Voucher successfully created.'));
			}
		}
	}

	include_once(ADMIN_ROOT . '/_header.inc.php');

	if(isset($_REQUEST['submitsimple']) && $_REQUEST['vouchers'])
	{	
		//Codes to create
		$pretotal = (int)$_REQUEST['total'];
		$customtotal = (int)$_REQUEST['custom_number'];
		$voucherLength = (int)$_REQUEST['voucher_length'];
		$voucherChars = (int)$_REQUEST['voucher_chars'];
		if(!$pretotal || $pretotal == '' || empty($pretotal))
		{
			$total = $customtotal;
		}
		else
		{
			$total = $pretotal;
		}

		// Expire after x days
		$valid = (int)$_REQUEST['valid'];	
		// Expiry date
		$expiry = trim($_REQUEST['expiry_date']);
		if(strlen($expiry))
		{
			$exp = explode(" ", $expiry);
			$exp  = explode("/", $exp[0]);
			if (COUNT($exp) != 3)
			{
				adminFunctions::setError(adminFunctions::t("voucher_expiry_invalid_dd_mm_yy", "Voucher expiry date invalid, it should be in the format dd/mm/yyyy"));
			}
			else
			{
				$dbExpiryDate = $exp[2] . '-' . $exp[1] . '-' . $exp[0] . ' 00:00:00';
				$expiryDate = strtotime($dbExpiryDate);
			}
		}
		else
		{
			$expiryDate = '4070930400';
		}
	?>
	<div class="row clearfix">
		<div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
			<div class="widget clearfix">
				<h2><?php echo adminFunctions::t('vouchers_generated', 'Generated Voucher Codes'); ?></h2>
				<div class="widget_inside">
					<?php echo adminFunctions::compileNotifications(); ?>
					 <div class="col_12">
					<form method="POST" action="create_vouchers.php" name="voucherform" id="voucherform" autocomplete="off">
						<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_generated', 'Generated Voucher Codes'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_generated_desc', 'The voucher codes you just created'); ?></p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<!-- CODES -->
										<h4 style="font-weight:bold;">
										<?php echo adminFunctions::t('vouchers_generated_your', 'Your'); ?> <?php echo $valid; ?> <?php echo adminFunctions::t('vouchers_generated_your_codes', 'day voucher codes.'); ?></h4>
										<p><textarea cols="60" rows="20" onclick="this.select()"><?php create_voucher_codes($total, $voucherLength, $valid, $expiryDate, $voucherChars); ?></textarea></p>
									</div>
								</div>
							</div>
						</div>
					</div>
			</div> 
		</div>
	</div>
	<?php
	include_once(ADMIN_ROOT . '/_footer.inc.php');
	exit;
	}
	?>
	<script src="../assets/js/jquery.numeric.js"></script>
	<script src="../assets/js/jquery.alphanumeric.js"></script>
	<script>
		$(function() {
			// formvalidator
			$("#userForm").validationEngine();

			// date picker
			$( "#expiry_date" ).datepicker({
				"dateFormat": "dd/mm/yy"
				});
			$( "#custom_expire" ).datepicker({
				"dateFormat": "dd/mm/yy"
			});
			$("#custom_text").keypress(function() {
			  var length = this.value.length;
			  if(length >= MAX) {
				$("#submitcustom").attr("disabled");
			  } else {
				$("#submitcustom").removeAttr("disabled", "disabled");
			  }
			});
			$("#custom_uses").numeric(false, function() { 
				this.value = ""; 
				this.focus(); 
			});
			$('.custom_text').alphanumeric();
		});
	</script>

	<div class="row clearfix">  
		<div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
			<div class="widget clearfix">
				<h2><?php echo adminFunctions::t('vouchers_create', 'Create Voucher Codes'); ?></h2>
				<div class="widget_inside">
					<?php echo adminFunctions::compileNotifications(); ?>
					<div class="col_12">
					 <form method="POST" action="create_vouchers.php" name="customform" id="customform" autocomplete="off">
						<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_create', 'Create Voucher Codes'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_create_predef_desc', 'Predefined number to create'); ?>?</p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<label><?php echo adminFunctions::t('vouchers_create_sml', 'Codes to create'); ?>:</label>
										<div class="input">
											<select name="total" id="total" class="medium">
											<option value="" SELECTED></option>
											<option value="5">5 Vouchers</option>
											<option value="10">10 Vouchers</option>
											<option value="25">25 Vouchers</option>
											<option value="50">50 Vouchers</option>
											<option value="75">75 Vouchers</option>
											<option value="100">100 Vouchers</option>
											<option value="250">250 Vouchers</option>
											<option value="500">500 Vouchers</option>
											</select>&nbsp;&nbsp; Leave blank to use a custom number below.
										</div>										
									</div>
								</div>
							</div>
						</div>
<!-- v1.5 -->
						<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_create', 'Create Voucher Codes'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_create_desc', 'How many vouchers to create'); ?>?</p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<label><?php echo adminFunctions::t('vouchers_create_sml', 'Codes to create'); ?>:</label>
										<div class="input"><input id="custom_number" name="custom_number" type="text"  onkeypress='return event.charCode >= 48 && event.charCode <= 57' class="small"/>&nbsp;&nbsp; Will only work if the above is left blank.
										</div>										
									</div>
								</div>
							</div>
						</div>

						<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_length', 'Voucher Code length'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_length_desc', 'How characters in the vouchers'); ?>?
								<br/><?php echo adminFunctions::t('vouchers_length_default', 'Default: 12 (Can be left blank to ignore)'); ?>.</p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<label><?php echo adminFunctions::t('vouchers_length_label', 'Voucher Length'); ?>:</label>
										<div class="input"><input id="voucher_length" name="voucher_length" type="text"  onkeypress='return event.charCode >= 48 && event.charCode <= 57' class="small" placeholder="12"/>
										</div>										
									</div>
								</div>
							</div>
						</div>

						<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_special_chars', 'Include Special Characters'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_special_chars_desc', 'Include the characters below into the codes'); ?>?
								<br/><?php echo adminFunctions::t('vouchers_special_chars_default', '# * $ @ &'); ?>.</p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<label><?php echo adminFunctions::t('vouchers_length_sml', 'Characters'); ?>:</label>
										<div class="input"><input id="voucher_chars" name="voucher_chars" type="checkbox" value="1"/>
										</div>										
									</div>
								</div>
							</div>
						</div>
<!-- v1.5 -->
						<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_valid', 'Valid for X days'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_valid_descri', 'How long are the vouchers valid for from <br/>the time of being used'); ?>?</p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<label><?php echo adminFunctions::t('vouchers_valid_label', 'Valid for'); ?>:</label>
										<div class="input">
											<select name="valid" id="valid" class="medium validate[required]">
											<option value="3">3 Days</option>
											<option value="7" SELECTED>7 Days</option>
											<option value="30">30 Days</option>
											<option value="60">60 Days</option>
											<option value="90">90 Days</option>
											<option value="180">180 Days</option>
											<option value="365">365 Days</option>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- VOUCHER EXPIRY DATE -->
						<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_expire_date', 'Voucher expiry date'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_expire_desc', 'The date which the vouchers have to be used by'); ?>.<br/>
								<?php echo adminFunctions::t('vouchers_expire_leave_blank', '(leave blank to never expire)'); ?>.</p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<label><?php echo adminFunctions::t('vouchers_valid_until', 'Valid until'); ?>:</label>
										<div class="input">
											<input id="expiry_date" name="expiry_date" type="text" class="small"/>&nbsp;&nbsp;(dd/mm/yyyy)
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- VOUCHER EXPIRY DATE -->
						<div class="clearfix col_12">
							<div class="col_4">&nbsp;</div>
							<div class="col_8 last">
								<div class="clearfix">
									<div class="input no-label">
										<input type="submit" name="submitsimple" id="submitsimple" value="Create Vouchers" class="button blue">
									</div>
								</div>
							</div>
						</div>
						<input type="hidden" name="vouchers" value="1"/>
					</form>
				</div>
			</div> 
		</div>
	</div>
	<!-- CUSTOM VOUCHERS -->
	<div class="row clearfix">
		<div class="col_12">
			<div class="widget clearfix">
				<h2><?php echo adminFunctions::t('vouchers_create_custom', 'Create Customized Voucher Codes'); ?></h2>
				<div class="widget_inside">				
					<form method="POST" action="create_vouchers.php" name="custom" id="custom" autocomplete="off">
					<div class="clearfix col_12">
						<div class="col_4">
							<h3><?php echo adminFunctions::t('vouchers_custom_text', 'Custom voucher text'); ?></h3>
							<p><?php echo adminFunctions::t('vouchers_custom_desc', 'Create your customized vouchers'); ?>.<br/>
							<?php echo adminFunctions::t('vouchers_custom_example', 'Example: (MYCUSTOMCODE1234)'); ?>.<br/>
							<?php echo adminFunctions::t('vouchers_custom_example_no_spaces', 'No Spaces between words'); ?>.</p>
						</div>
						<div class="col_8 last">
							<div class="form">
								<div class="clearfix alt-highlight">
									<label><?php echo adminFunctions::t('vouchers_custom_label_1', 'Voucher text'); ?>:</label>
									<div class="input">
										<input id="custom_text" name="custom_text" type="text" maxlength="25" style="text-transform:uppercase;" class="large validate[required]"/>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="clearfix col_12">
							<div class="col_4">
								<h3><?php echo adminFunctions::t('vouchers_custom_valid', 'Valid for X days'); ?></h3>
								<p><?php echo adminFunctions::t('vouchers_custom_valid_desc', 'How long is the voucher valid for from <br/>the time of being used'); ?>?</p>
							</div>
							<div class="col_8 last">
								<div class="form">
									<div class="clearfix alt-highlight">
										<label><?php echo adminFunctions::t('vouchers_custom_valid_label', 'Valid for'); ?>:</label>
										<div class="input">
											<select name="custom_valid" id="custom_valid" class="medium validate[required]">
											<option value="3">3 Days</option>
											<option value="7" SELECTED>7 Days</option>
											<option value="30">30 Days</option>
											<option value="60">60 Days</option>
											<option value="90">90 Days</option>
											<option value="180">180 Days</option>
											<option value="365">365 Days</option>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
					<div class="clearfix col_12">
						<div class="col_4">
							<h3><?php echo adminFunctions::t('vouchers_custom_expiry', 'Custom voucher expiry date'); ?></h3>
							<p><?php echo adminFunctions::t('vouchers_custom_expiry_desc', 'The date the custom vouchers expire'); ?>.<br/>
							<?php echo adminFunctions::t('vouchers_expire_leave_blank'); ?>.</p>
						</div>
						<div class="col_8 last">
							<div class="form">
								<div class="clearfix alt-highlight">
									<label><?php echo adminFunctions::t('vouchers_custom_label_2', 'Valid until'); ?>:</label>
									<div class="input">
										<input id="custom_expire" name="custom_expire" type="text" class="small"/>&nbsp;&nbsp;(dd/mm/yyyy)
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="clearfix col_12">
						<div class="col_4">
							<h3><?php echo adminFunctions::t('vouchers_custom_max_uses', 'Custom voucher maximum uses'); ?></h3>
							<p><?php echo adminFunctions::t('vouchers_custom__max_uses_desc', 'The maximum times a voucher can be used'); ?>.<br/>
							<?php echo adminFunctions::t('vouchers_custom_leave_blank_no_max', '(leave blank to not use a maximum limit)'); ?>.</p>
						</div>
						<div class="col_8 last">
							<div class="form">
								<div class="clearfix alt-highlight">
									<label><?php echo adminFunctions::t('vouchers_custom_label_3', 'Maximum uses'); ?>:</label>
									<div class="input">
										<input id="custom_uses" name="custom_uses" maxlength="5" type="text" class="small" />
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="clearfix col_12">
							<div class="col_4">&nbsp;</div>
							<div class="col_8 last">
								<div class="clearfix">
									<div class="input no-label">
										<input type="submit" name="submitcustom" id="submitcustom" value="Create Custom Vouchers" class="button blue">
									</div>
								</div>
							</div>
						</div>
						<input type="hidden" name="custom" value="1"/>
					</form>
				</div>
			</div> 
		</div>
	</div>
	<?php
	
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>