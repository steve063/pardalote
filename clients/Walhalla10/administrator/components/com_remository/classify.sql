						CREATE TABLE IF NOT EXISTS `#__downloads_classify` (
						  `id` int(11) NOT NULL auto_increment,
						  `sequence` int(11) NOT NULL default '0',
  						  `windowtitle` varchar(255) NOT NULL default '',
						  `keywords` varchar(255) NOT NULL default '',
						  `frequency` int(11) NOT NULL default '0',
						  `published` tinyint(3) unsigned NOT NULL default '0',
						  `hidden` tinyint(3) unsigned NOT NULL default '0',
						  `type` varchar(30) NOT NULL,
						  `name` varchar(100) NOT NULL,
						  `description` text NOT NULL,
						  PRIMARY KEY  (`id`)
						) ENGINE=MyISAM;
