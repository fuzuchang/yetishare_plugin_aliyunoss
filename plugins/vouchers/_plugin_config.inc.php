<?php
// core plugin config
$pluginConfig = array();
$pluginConfig['plugin_name']				 = 'Vouchers';
$pluginConfig['folder_name']				 = 'vouchers';
$pluginConfig['admin_settings']['top_nav']   = array();
$pluginConfig['admin_settings']['top_nav'][] = array(
											   array('link_url' => '#', 'link_text' => 'Vouchers', 'link_key'  => 'vouchers'),
											   array('link_url' => 'admin/create_vouchers.php', 'link_text' => 'Create Vouchers', 'link_key'  => 'vouchers'),
											   array('link_url' => 'admin/view_vouchers.php', 'link_text' => 'View Vouchers', 'link_key'  => 'vouchers')					
											   );
$pluginConfig['plugin_description']			 = 'Allows users to redeem vouchers for premium status.';
$pluginConfig['plugin_version']				 = 1;
$pluginConfig['required_script_version']	 = 3.2;