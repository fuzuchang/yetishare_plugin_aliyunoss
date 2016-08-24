<?php

// includes and security
include_once('/home/resasundoro/public_html/core/includes/master.inc.php');
include_once(DOC_ROOT . '/' . ADMIN_FOLDER_NAME . '/_local_auth.inc.php');
include_once('../includes/functions.php');
define('ADMIN_PAGE_TITLE', 'View Voucher Codes');
	// page header
	include_once(ADMIN_ROOT . '/_header.inc.php');

	// Edit Voucher
	if(isset($_REQUEST['exp']))
	{
		$code = $_REQUEST['id'];
		$vcode = is_array($code) ? $code : explode(',', $code);
		foreach($vcode as $expire)
		{
			$db = Database::getDatabase();
			$db->query("UPDATE plugin_vouchers SET redeemed = '2' WHERE voucher = '$expire'");
			$vc = $db->getRow("SELECT * FROM plugin_vouchers_logs WHERE code = '$expire'");
			if($vc)
			{
				$users = $vc['user'];
				$usr = $db->getRow("SELECT * FROM users WHERE id = '$users' LIMIT 1");
				if($usr['level'] != 'admin')
				{
					$db->query("UPDATE users SET level = 'free user', paidExpiryDate = '0000-00-00 00:00:00' WHERE id = '$users'");
				}
			}
		}
		adminFunctions::setSuccess('Voucher(s) successfully updated.');
	}
	// Delete Voucher
	elseif(isset($_REQUEST['del']))
	{
		$del = $_REQUEST['id'];
		$del = is_array($del) ? $del : explode(',', $del);
		foreach($del as $delete)
		{
			$db->query("DELETE FROM plugin_vouchers WHERE voucher = '$delete'");
			$db->query("DELETE FROM plugin_vouchers_logs WHERE code = '$delete'");
		}
		adminFunctions::setSuccess('Voucher(s) successfully deleted.');
	}
	// Error message
	elseif(isset($_REQUEST['error']))
	{
		adminFunctions::setError(urldecode($_REQUEST['error']));
	}

	// get any params
	$filterByLength = '';
	if(isset($_REQUEST['filterByLength']))
	{
		$filterByLength = trim($_REQUEST['filterByLength']);
	}

	$filterByRedeemed = 'active';
	if(isset($_REQUEST['filterByRedeemed']))
	{
		$filterByRedeemed = trim($_REQUEST['filterByRedeemed']);
	}
	?>

	<script>
		oTable = null;
		gUserId = null;
		$(document).ready(function(){
			// datatable
			oTable = $('#fileTable').dataTable({
				"sPaginationType": "full_numbers",
				"bServerSide": true,
				"bProcessing": true,
				"sAjaxSource": 'ajax/view_vouchers.ajax.php',
				"bJQueryUI": true,
				"iDisplayLength": 25,
				"aaSorting": [[ 1, "asc" ]],
				"aoColumns" : [
					{ bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center" },
					{ bSortable: true, sName: 'id', sWidth: '5%', sClass: "center" },
					{ bSortable: false, sName: 'voucher_code', sWidth: '15%', sClass: "center" },
					{ bSortable: true, sName: 'valid_for', sWidth: '8%', sClass: "center" },
					{ bSortable: true, sName: 'expiry', sWidth: '10%', sClass: "center" },
					{ bSortable: false, sName: 'uses', sWidth: '10%', sClass: "center" },
					{ bSortable: true, sName: 'redeemed_by', sWidth: '15%', sClass: "center" },
					{ bSortable: false, sName: 'actions', sWidth: '4%', sClass: "center" }
				],
				"fnServerData": function ( sSource, aoData, fnCallback ) {
				aoData.push( { "name": "filterText", "value": $('#filterText').val() } );
				aoData.push( { "name": "filterByLength", "value": $('filterByLength').val() } );
				aoData.push( { "name": "filterByRedeemed", "value": $('filterByRedeemed').val() } );
				$.ajax({
					"dataType": 'json',
					"type": "GET",
					"url": "ajax/view_vouchers.ajax.php",
					"data": aoData,
					"success": fnCallback
				});
			}
			});
			
			// update custom filter
			$('.dataTables_filter').html($('#customFilter').html());
			
			// dialog box
			$( "#confirmDelete" ).dialog({
				modal: true,
				autoOpen: false,
				width: 450,
				buttons: {
					"Delete Voucher": function() {
						removeUser();
						$("#confirmDelete").dialog("close");
					},
					"Cancel": function() {
						$("#confirmDelete").dialog("close");
					}
				},
				open: function() {
					resetOverlays();
				}
			});
		});

		function reloadTable()
		{
			oTable.fnDraw(false);
		}
		
		function confirmRemoveVoucher(voucher)
		{
			$('#confirmDelete').dialog('open');
			gVoucherId = voucher;
		}
	</script>
	<script language='JavaScript'>
	checked = false;
	function checkedAll () {
	if (checked == false){checked = true}else{checked = false}
	for (var i = 0; i < document.getElementById('theform').elements.length; i++){
	document.getElementById('theform').elements[i].checked = checked;
	}}
	</script>
	<style>
	a.info {
	/* This is the key. */
	position: relative;
	z-index: 24;
	text-decoration: none;
	}
	a.info:hover {
	z-index: 25;
	color: #FFF; background-color: #900;
	}
	a.info span { display: none; }
	a.info:hover span.info {
	/* The span will display just on :hover state. */
	display: block;
	position: absolute;
	font-family: "Courier New", Courier, fixed;
	font-weight:bold;
	font-size: smaller;
	top: 2em; left: 5em; width: 15em;
	padding: 2px; border: 1px solid #333;
	color: #900; background-color: #EEE;
	text-align: left;
	}
	.discreet {
	border: 1px solid #DFDFDF;
	border-top: 1px solid #DFDFDF !important;
	vertical-align:middle !important;
	}
	</style>
	<div class="row clearfix">
		<div class="sectionLargeIcon" style="background: url(../assets/img/icons/128px.png) no-repeat;"></div>
		<div class="widget clearfix">
			<h2><?php echo adminFunctions::t('voucher_admin_page_title', 'Voucher List'); ?></h2>
			<div class="widget_inside">
				<?php echo adminFunctions::compileNotifications(); ?>
				<form action="view_vouchers.php" method="post" enctype="multipart/form-data" name="theform" id="theform">
				<div class="col_12">
					<table id='fileTable' class='dataTable'>
						<thead>
							<tr>
								<th></th>
								<th><?php echo adminFunctions::t('voucher_admin_id', 'ID'); ?></th>
								<th><?php echo adminFunctions::t('voucher_admin_code', 'Voucher Code'); ?></th>
								<th><?php echo adminFunctions::t('voucher_admin_validity', 'Days'); ?></th>
								<th><?php echo adminFunctions::t('voucher_admin_expiry', 'Expires On'); ?></th>
								<th><?php echo adminFunctions::t('voucher_admin_max', 'Used/Max'); ?></th>
								<th><?php echo adminFunctions::t('voucher_admin_redeemed_user', 'Used By'); ?></th>
								<th><?php echo adminFunctions::t('voucher_admin_select', 'Select'); ?></th>
							</tr>
						</thead>
						<tbody>
						</tbody>
						<tfoot>
						<tr>
						  <td colspan="7" align="right" class="discreet"><?php echo adminFunctions::t('voucher_selectall', 'Select All'); ?></td>
						  <td width="5%" align="center" class="discreet"><input type='checkbox' name='checkall' onclick='checkedAll();'></td>
						</tr>
						</tfoot>
					</table>
				</div>
				<div align="right">
				<input type="submit" id="exp" name="exp" value="<?php echo t("expire_voucher", "Expire Selected"); ?>" 
				onClick="return confirm('Are you sure you want to expire the selected vouchers?\n\n\nThis will also downgrade the associated user account to a \'Free user account\'.');" class="button blue" title="This will expire the selected voucher and remove premium status from all users associated with the voucher. To remove paid status from specific users, please click on the &quot;View Users&quot; link then select the users you want to remove paid status from." />

				<input type="submit" id="del" name="del" value="<?php echo t("delete_selected", "Delete Selected"); ?>" onClick="return confirm('Are you sure you want to delete the selected vouchers?');" class="button blue" title="This will delete the selected vouchers, it will NOT remove paid status from the associated users." />
				</div>
				</form>			
			</div>
		</div>
	</div>
	<!-- NOT IMPLIMENTED YET class="customFilter" id="customFilter" style="display: none;" -->
	<div style="display:none; visibility:hidden;">
		<label>
			Filter Results:
			<input name="filterText" id="filterText" type="text" onKeyUp="reloadTable(); return false;" style="width: 160px;"/>
		</label>
		<label style="padding-left: 6px;">
			By Length:
			<select name="filterByLength" id="filterByLength" onChange="reloadTable(); return false;" style="width: 160px;">
				<option value="">- all -</option>
				<option value="7">7 Days</option>
				<option value="30">30 Days</option>
				<option value="90">90 Days</option>
				<option value="180">180 Days</option>
				<option value="365">365 Days</option>
			</select>
		</label>
		<label style="padding-left: 6px;">
			By Status:
			<select name="filterByRedeemed" id="filterByRedeemed" onChange="reloadTable(); return false;" style="width: 120px;">
				<option value="" selected>- all -</option>
				<option value="1">Yes</option>
				<option value="0">No</option>
			</select>
		</label>
	</div>
	<?php
	
include_once(ADMIN_ROOT . '/_footer.inc.php');
?>