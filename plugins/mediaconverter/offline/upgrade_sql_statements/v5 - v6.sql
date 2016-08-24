ALTER TABLE `plugin_mediaconverter_queue` CHANGE `status` `status` ENUM( 'pending', 'processing', 'completed', 'failed', 'reload', 'cancelled' ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'pending';

