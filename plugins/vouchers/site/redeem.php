<?php

include('/home/resasundoro/public_html/plugins/vouchers/includes/functions.php');
include('/home/resasundoro/public_html/core/includes/master.inc.php');
$userId	= $Auth->id;

/* setup page */
define("PAGE_NAME", t("voucher_page_name", "Redeem Voucher"));
define("PAGE_DESCRIPTION", t("voucher_page_description", "Validate and redeem your voucher code."));
define("PAGE_KEYWORDS", t("voucher_page_keywords", "voucher, code, voucher code, redeem voucher, paid, free, premium, plans, plan, uploaders, download, file, hosting, site"));
include('/home/resasundoro/public_html/themes/gedo/templates/partial/_header.inc.php');
?>
<div class="contentPageWrapper">
		<!-- main section -->
		<div class="pageSectionMain ui-corner-all" style="width:100%;">
			<div class="pageSectionMainInternal">
				<div id="pageHeader">
					<h2><?php echo t("voucher_redeem_voucher_page_title", "Redeem Voucher"); ?></h2>
				</div>
					<div>
					<!-- ACC STATUS -->
					<div>
                <table class="accountStateTable">
                    <tbody>
                        <tr>
                            <td class="first">
                                <?php echo UCWords(t('account_type', 'account type')); ?>:
                            </td>
                            <td>
                                <?php echo UCWords($Auth->level); ?>
                            </td>
                        </tr>
                        <?php if ($Auth->level != 'free user'): ?>
                            <tr>
                                <td class="first">
                                    <?php echo UCWords(t('reverts_to_free_account', 'reverts to free account')); ?>:
                                </td>
                                <td>
                                    <?php echo($Auth->level == 'paid user') ? dater($Auth->paidExpiryDate) : UCWords(t('never', 'never')); ?>
                                </td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
			<div class="clear">&nbsp;</div>
					<!-- ACC STATUS -->
					<!-- CONTENT -->
					<?php
						//*********************************************************************************
						//
						//	Verify voucher is valid
						//
						//*********************************************************************************
						if(isset($_REQUEST['submit']) && $_REQUEST['validate'] == '1')
						{
							if(empty($_REQUEST['voucher']))
							{
								//empty code: redirect to redeem page
								@header('Location: redeem.php');
							}
							$code = strtoupper($_REQUEST['voucher']);
							
							$db = Database::getDatabase();
							$row = $db->getRow("SELECT * FROM `plugin_vouchers` WHERE `voucher` = '$code'");

							if($row) 
							{
								if(check_valid_code($code, $userId))
								{
									echo '<p>'. t('voucher_code_expired', 'Sorry, that voucher has expired.').'</p>';
									echo '<div class="clear"></div>
										</div>
										</div>
										</div>
										<div class="clear"><!-- --></div>
										</div>';
									require_once('/home/resasundoro/public_html/themes/gedo/templates/partial/_footer.inc.php');
								}
								else
								{
									echo '<h2>'.t("voucher_redeem_code_validated", "Code validated").'</h2>';
									echo '<form method="POST" action="redeem.php" name="vouchers" id="vouchers" autocomplete="off">';
									echo '<ul>';
									echo '<li>';
									echo 'Voucher ('.$code.') is valid for a '.$row['length'].' day premium account';
									echo '</li>';
									echo '<li><div class="clear">&nbsp;</div></li>';
									echo '<li><input type="hidden" name="voucher" id="voucher" value="'.$code.'" /></li>';
									echo '<li><div class="clear">&nbsp;</div></li>';
									echo '<li><input type="hidden" name="redeem" value="1" />';
									echo '<input type="hidden" name="validate" value="'.$row['length'].'" />';
									echo '<input type="submit" id="submit" name="submit" value="'.t("voucher_redeem_use_button", "Use Voucher").'" style="border: 1px solid #19559e !important; box-shadow:inset 0 1px 0px #58a4e9 !important; color: #fff !important;background: #3d90e3;background: -moz-linear-gradient(top, #3d90e3 0%, #2d6ad9 100%); background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#3d90e3), color-stop(100%,#2d6ad9)); background: -webkit-linear-gradient(top, #3d90e3 0%,#2d6ad9 100%); background: -o-linear-gradient(top, #3d90e3 0%,#2d6ad9 100%); background: -ms-linear-gradient(top, #3d90e3 0%,#2d6ad9 100%); -ms-filter: progid:DXImageTransform.Microsoft.gradient( startColorstr=\'#3d90e3\', endColorstr=\'#2d6ad9\',GradientType=0 ); background: linear-gradient(top, #3d90e3 0%,#2d6ad9 100%);" /></li>';
									echo '</ul>';
									echo '</form>';
								}
							}
							else 
							{
								echo '<p>'. t('voucher_code_not_found', 'Sorry, but that code could not be found in the database').'.</p>';
							}
						}
						//*****************************************************************************
						//
						//	Use voucher code	
						//
						//*****************************************************************************
						elseif(isset($_REQUEST['submit']) && $_REQUEST['redeem'] == '1')
						{
							$db			= Database::getDatabase();
							$code		= $_REQUEST['voucher'];
							$numdays	= $_REQUEST['validate'];
							
							use_valid_code($code, $userId, $numdays);							
						}
						else
						{
							if($_REQUEST['ss'])
							{
								echo '<p>'. urldecode($_REQUEST['ss']).'</p>';
							}
							else
							{
								?>					
								<p><?php echo t("voucher_redeem_validate", "Please enter the voucher code that you purchased in the box below."); ?></p>
								<form method="POST" action="redeem.php" name="vouchers" id="vouchers" autocomplete="off">
								<ul>
								<li><input type="text" name="voucher" id="voucher" maxlength="25" style="text-transform:uppercase;" required /></li>
								<li><div class="clear">&nbsp;</div></li>
								<li><input type="hidden" name="validate" value="1" />
								<input type="submit" id="submit" name="submit" value="<?php echo t("voucher_redeem_check_button", "Validate Code"); ?>" style="border: 1px solid #19559e !important; box-shadow:inset 0 1px 0px #58a4e9 !important; color: #fff !important;background: #3d90e3;background: -moz-linear-gradient(top, #3d90e3 0%, #2d6ad9 100%); background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#3d90e3), color-stop(100%,#2d6ad9)); background: -webkit-linear-gradient(top, #3d90e3 0%,#2d6ad9 100%); background: -o-linear-gradient(top, #3d90e3 0%,#2d6ad9 100%); background: -ms-linear-gradient(top, #3d90e3 0%,#2d6ad9 100%); -ms-filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#3d90e3', endColorstr='#2d6ad9',GradientType=0 ); background: linear-gradient(top, #3d90e3 0%,#2d6ad9 100%);" /></li>
								</ul>
								</form>
								<?php
							}
						}
										?>
					<!-- !CONTENT -->				
					<div class="clear"></div>
				</div>
			</div>
		</div>
		<div class="clear"><!-- --></div>
	</div>
<?php
require_once('/home/resasundoro/public_html/themes/gedo/templates/partial/_footer.inc.php');
?>