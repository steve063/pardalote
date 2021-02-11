						CREATE TABLE IF NOT EXISTS `#__downloads_file_classify` (
						  `file_id` int(11) NOT NULL default '0',
						  `classify_id` int(11) NOT NULL default '0',
						  PRIMARY KEY  (`file_id`,`classify_id`)
						) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

