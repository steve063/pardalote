						CREATE TABLE IF NOT EXISTS `#__downloads_text` (
						  `id` int NOT NULL auto_increment,
						  `fileid` int NOT NULL default 0,
						  `filetext` longtext NOT NULL,
							UNIQUE KEY `id` (`id`),
							KEY `fileid` (`fileid`),
							FULLTEXT `words` (`filetext`)
							)  ENGINE=MyISAM;
