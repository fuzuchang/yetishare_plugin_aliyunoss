ALTER TABLE  `plugin_mediaconverter_queue` ADD  `convert_source` ENUM(  'auto',  'user',  'admin' ) NOT NULL DEFAULT  'auto';
ALTER TABLE  `plugin_mediaconverter_queue` ADD  `additional_data` TEXT NULL;