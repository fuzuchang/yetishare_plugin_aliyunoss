<?php

// core plugin config
$pluginConfig = array();
$pluginConfig['plugin_name']             = 'Media Converter';
$pluginConfig['folder_name']             = 'mediaconverter';
$pluginConfig['plugin_description']      = 'Convert various video formats to mp4, flv or webm.';
$pluginConfig['plugin_version']          = 9;
$pluginConfig['required_script_version'] = "4.1";
$pluginConfig['database_sql']            = 'offline/database.sql';

// plugin admin area settings, links are relative to the plugin root
// plugin_manage_nav = shown on the plugin_manage.php page listing
$pluginConfig['admin_settings']              = array();
$pluginConfig['admin_settings']['plugin_manage_nav']   = array();
$pluginConfig['admin_settings']['plugin_manage_nav'][] = array('link_url'  => 'admin/view_queue.php', 'link_text' => 'View Queue', 'link_key'  => 'view_queue');