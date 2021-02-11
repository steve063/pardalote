						CREATE TABLE IF NOT EXISTS `#__permissions` (
						  `id` int(11) NOT NULL auto_increment,
						  `role` varchar(60) NOT NULL,
						  `control` tinyint(3) unsigned NOT NULL default '0',
						  `action` varchar(60) NOT NULL,
						  `subject_type` varchar(60) NOT NULL,
						  `subject_id` text NOT NULL,
						  `system` smallint(5) unsigned NOT NULL default '0',
						  PRIMARY KEY  (`id`),
						  KEY `role_type` (`role`,`action`,`subject_type`,`subject_id`(60)),
						  KEY `subaction` (`subject_type`,`action`,`subject_id`(60))
						) ENGINE=MyISAM DEFAULT CHARSET=utf8;
