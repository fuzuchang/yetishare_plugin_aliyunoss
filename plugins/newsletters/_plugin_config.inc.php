<?php

// core plugin config
$pluginConfig = array();
$pluginConfig['plugin_name']             = 'Newsletters';
$pluginConfig['folder_name']             = 'newsletters';
$pluginConfig['plugin_description']      = 'Manage & send newsletters to your users.';
$pluginConfig['plugin_version']          = 3;
$pluginConfig['required_script_version'] = 4.0;
$pluginConfig['database_sql']            = 'offline/database.sql';

// plugin admin area settings, links are relative to the plugin root
// links in multi-dimentional arrays create sub-menus, first item in array is the top level
$pluginConfig['admin_settings'] = array();
$pluginConfig['admin_settings']['top_nav'] = array();
$pluginConfig['admin_settings']['top_nav'][] = array(
    array('link_url'  => '#', 'link_text' => 'Newsletters', 'link_key'  => 'newsletters'),
    array('link_url'  => 'admin/manage_newsletter.php?create=1', 'link_text' => 'Create Newsletter', 'link_key'  => 'newsletters_manage_newsletter'),
    array('link_url'  => 'admin/manage_newsletter.php', 'link_text' => 'Manage Newsletters', 'link_key'  => 'newsletters_manage_newsletter'),
    array('link_url'  => 'admin/export_user_data.php', 'link_text' => 'Export User Data', 'link_key'  => 'newsletters_export_user_data')
);