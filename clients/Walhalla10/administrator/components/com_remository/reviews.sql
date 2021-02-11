				CREATE TABLE IF NOT EXISTS `#__downloads_reviews` (
						  `id` int NOT NULL auto_increment,
						  `sequence` int NOT NULL default 0,
						  `windowtitle` varchar(255) NOT NULL default '',
						  `keywords` varchar(255) NOT NULL default '',
						  `component` varchar (255) NOT NULL default '',
						  `itemid` int NOT NULL default 0,
						  `userid` mediumint NOT NULL default 0,
						  `userURL` varchar(255) NOT NULL default '',
						  `title` varchar (255) NOT NULL default '',
						  `comment` text NOT NULL default '',
						  `fullreview` text NOT NULL default '',
						  `date` datetime NOT NULL default '0000-00-00',
						  UNIQUE KEY `id` (`id`),
						  KEY `userid` (`component`,`itemid`,`userid`)
							)  ENGINE=MyISAM;
