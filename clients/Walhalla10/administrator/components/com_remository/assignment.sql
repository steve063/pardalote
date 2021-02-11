						CREATE TABLE IF NOT EXISTS `#__assignments` (
						  `id` int(11) NOT NULL auto_increment,
						  `access_type` varchar(60) NOT NULL,
						  `access_id` text NOT NULL,
						  `role` varchar(60) NOT NULL,
						  PRIMARY KEY  (`id`),
						  KEY `access_type` (`access_type`,`access_id`(60),`role`)
						) ENGINE=MyISAM;
