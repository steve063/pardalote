						CREATE TABLE IF NOT EXISTS `#__downloads_log` (
						  `id` int NOT NULL auto_increment,
						  `type` tinyint unsigned NOT NULL default 0,
						  `date` datetime NOT NULL default '0000-00-00',
						  `userid` mediumint NOT NULL default 0,
						  `fileid` int NOT NULL default 0,
						  `value` int NOT NULL default 0,
						  `ipaddress` char (15) NOT NULL default '',
							UNIQUE KEY `id` (`id`),
							KEY `userid` (`type`,`userid`),
							KEY `fileid` (`type`,`fileid`),
							KEY `ipaddress` (`type`,`ipaddress`,`date`)
							) ENGINE=MyISAM DEFAULT CHARSET=utf8;
