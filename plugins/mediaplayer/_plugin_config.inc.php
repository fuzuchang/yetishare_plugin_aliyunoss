<?php// core plugin config$pluginConfig = array();$pluginConfig['plugin_name']             = 'Media Player';$pluginConfig['folder_name']             = 'mediaplayer';$pluginConfig['plugin_description']      = 'Stream video and audio files stright from your site.';$pluginConfig['plugin_version']          = 10;$pluginConfig['required_script_version'] = "4.1";$pluginConfig['database_sql']            = 'offline/database.sql';// which players to use, by extension$pluginConfig['players'] = array();$pluginConfig['players']['avi']  = 'divx_web_player';$pluginConfig['players']['divx'] = 'divx_web_player';$pluginConfig['players']['mkv']  = 'divx_web_player';$pluginConfig['players']['mp4']  = 'html5_video';$pluginConfig['players']['mpv']  = 'html5_video';$pluginConfig['players']['ogg']  = 'html5_video';$pluginConfig['players']['ogv']  = 'html5_video';$pluginConfig['players']['webm'] = 'html5_video';$pluginConfig['players']['flv']  = 'html5_video';$pluginConfig['players']['m4v']  = 'html5_video';$pluginConfig['players']['mp3']  = 'html5_audio';$pluginConfig['players']['wav']  = 'html5_audio';$pluginConfig['players']['m4a']  = 'html5_audio';$pluginConfig['players']['wmv']  = 'windows_media_player';