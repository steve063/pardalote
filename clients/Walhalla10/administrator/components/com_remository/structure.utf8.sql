						CREATE TABLE IF NOT EXISTS `#__downloads_structure` (
						  `id` int NOT NULL auto_increment,
						  `container` smallint NOT NULL default 0,
						  `item` mediumint NOT NULL default 0,
							UNIQUE KEY `id` (`id`),
							KEY `container` (`container`)
							) ENGINE=MyISAM DEFAULT CHARSET=utf8;
