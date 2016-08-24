CREATE TABLE IF NOT EXISTS `plugin_docviewer_embed_token` (
  `token` varchar(32) COLLATE utf8_bin NOT NULL,
  `date_added` datetime NOT NULL,
  `file_id` int(11) NOT NULL,
  UNIQUE KEY `token` (`token`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `plugin_docviewer_embed_token` ADD `ip_address` VARCHAR( 15 ) NULL;

ALTER TABLE `file` CHANGE `fileType` `fileType` VARCHAR( 150 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' WHERE extension = 'xlsx';
UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.template' WHERE extension = 'xltx';
UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.presentationml.template' WHERE extension = 'potx';
UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow' WHERE extension = 'ppsx';
UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation' WHERE extension = 'pptx';
UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.presentationml.slide' WHERE extension = 'sldx';
UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' WHERE extension = 'docx';
UPDATE file SET fileType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template' WHERE extension = 'dotx';
UPDATE file SET fileType = 'application/vnd.ms-excel.addin.macroEnabled.12' WHERE extension = 'xlam';
UPDATE file SET fileType = 'application/vnd.ms-excel.sheet.binary.macroEnabled.12' WHERE extension = 'xlsb';

DROP TABLE `plugin_docviewer_embed_token`;
