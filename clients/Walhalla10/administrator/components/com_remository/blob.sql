						CREATE TABLE IF NOT EXISTS `#__downloads_blob` (
						  `id` int NOT NULL auto_increment,
						  `fileid` int NOT NULL default 0,
						  `chunkid` int NOT NULL default 0,
						  `bloblength` int NOT NULL default 0,
						  `datachunk` mediumblob NOT NULL,
							UNIQUE KEY `id` (`id`),
							UNIQUE KEY `filechunk` (`fileid`,`chunkid`),
							KEY `size` (`fileid`,`bloblength`)
						) ENGINE=MyISAM;
