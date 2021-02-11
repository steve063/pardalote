<?php

/**************************************************************
* This file is part of Remository
* Copyright (c) 2006 Martin Brampton
* Issued as open source under GNU/GPL
* For support and other information, visit http://remository.com
* To contact Martin Brampton, write to martin@remository.com
*
* Remository started life as the psx-dude script by psx-dude@psx-dude.net
* It was enhanced by Matt Smith up to version 2.10
* Since then development has been primarily by Martin Brampton,
* with contributions from other people gratefully accepted
*/

// Don't allow direct linking
if (!defined( '_VALID_MOS' ) AND !defined('_JEXEC')) die( 'Direct Access to this location is not allowed.' );

class remositoryInstaller {

	public function permission_all_from_dir($Dir){
		// delete everything in the directory
		$handle = @opendir($Dir);
		if ($handle) {
			while (($file = readdir($handle)) !== false) {
				if ($file == '.' || $file == '..') continue;
				$newpath = $Dir.$file;
				if (is_dir($newpath)) $this->permission_all_from_dir($newpath.'/');
				else $this->setFilePerms ($newpath);
			}
		}
		@closedir($handle);
		$this->setDirPerms($Dir);
	}

	public function setDirPerms ($dir) {
		$interface = remositoryInterface::getInstance();
   		$origmask = @umask(0);
		if ($interface->getCfg('dirperms')) {
	    	$mode = octdec($interface->getCfg('dirperms'));
			$result = @chmod($dir, $mode);
		}
		else $result = @chmod($dir,0755);
		@umask($origmask);
		return $result;
	}

	public function setFilePerms ($file) {
		$interface = remositoryInterface::getInstance();
   		$origmask = @umask(0);
		if ($interface->getCfg('fileperms')) {
	    	$mode = octdec($interface->getCfg('fileperms'));
	    	$result = @chmod($file, $mode);
		}
		else $result = @chmod($file,0644);
		@umask($origmask);
		return $result;
	}

	public function makeDefaultContainer () {
		$interface = remositoryInterface::getInstance();
		$database = $interface->getDB();
		$database->setQuery("SELECT count(id) FROM #__downloads_containers");
		if (!$database->loadResult()) {
			$container = new remositoryContainer();
			$container->name = _DOWN_SAMPLE;
			$container->description = _DOWN_SAMPLE_DESCRIPTION;
			$container->published = 1;
			$container->saveValues();
			$authoriser = aliroAuthorisationAdmin::getInstance();
			$authoriser->permit ('Registered', 2, 'upload', 'remosFolder', $container->id);
			$authoriser->permit ('Nobody', 2, 'edit', 'remosFolder', $container->id);
			$authoriser->permit ('Nobody', 2, 'selfApprove', 'remosFolder', $container->id);
		}
	}

	public function makeMenuEntry () {
		if (defined('_ALIRO_IS_PRESENT')) return;
		$interface = remositoryInterface::getInstance();
		$database = $interface->getDB();
		$database->setQuery("SELECT MIN(id) FROM `#__components` WHERE `option` = 'com_remository'");
		$remonum = intval($database->loadResult());
		$database->setQuery("SELECT count(*) FROM `#__menu` WHERE link LIKE 'index.php?option=com_remository%'");
		if (!$database->loadResult()) {
			$database->setQuery("SELECT MAX(ordering) FROM `#__menu`");
			$ordering = intval($database->loadResult() + 1);
			if (defined('_JEXEC') AND !defined('_ALIRO_IS_PRESENT')) $database->setQuery("INSERT INTO `#__menu` "
			." (`id`, `menutype`, `name`, `alias`, `link`, `type`, `published`, `parent`, `componentid`, `sublevel`, `ordering`, `checked_out`, `checked_out_time`, `pollid`, `browserNav`, `access`, `utaccess`, `params`) "
			." VALUES (NULL , 'mainmenu', 'Remository', 'remository', 'index.php?option=com_remository', 'components', '1', '0', $remonum, '0', $ordering, '0', '0000-00-00 00:00:00', '0', '0', '0', '0', '')");
			else $database->setQuery("INSERT INTO `#__menu` "
			." (`id`, `menutype`, `name`, `link`, `type`, `published`, `parent`, `componentid`, `sublevel`, `ordering`, `checked_out`, `checked_out_time`, `pollid`, `browserNav`, `access`, `utaccess`, `params`) "
			." VALUES (NULL , 'mainmenu', 'Remository', 'index.php?option=com_remository', 'components', '1', '0', $remonum, '0', $ordering, '0', '0000-00-00 00:00:00', '0', '0', '0', '0', '')");
			$database->query();
		}
		else {
			$database->setQuery("UPDATE #__menu SET componentid = $remonum WHERE link LIKE 'index.php?option=com_remository%'");
			$database->query();
		}
	}

	public function approverPermissions ($approver) {
		$interface = remositoryInterface::getInstance();
		$database = $interface->getDB();
		if (!defined('_ALIRO_IS_PRESENT')) {
			$database->setQuery("SELECT COUNT(*) FROM #__permissions WHERE action = 'selfApprove' AND subject_type = 'remosFolder'");
			if (0 == $database->loadResult()) {
				$database->setQuery("INSERT INTO #__permissions(SELECT 0 , '$approver', 2, 'selfApprove', 'remosFolder', id, 0 FROM #__downloads_containers)");
				$database->query();
			}
		}
	}
	
	public function dbupgrade () {
		$interface = remositoryInterface::getInstance();
		$database = $interface->getDB();

		if (!defined('_ALIRO_IS_PRESENT')) {
			$database->setQuery("SELECT COUNT(*) FROM #__permissions");
			if (0 == $database->loadResult()) {
				$database->setQuery("INSERT INTO #__permissions(SELECT 0 , 'Nobody', 2, 'edit', 'remosFolder', id, 0 FROM #__downloads_containers)");
				$database->query();
				$database->setQuery("INSERT INTO #__permissions(SELECT 0 , 'Registered', 2, 'upload', 'remosFolder', id, 0 FROM #__downloads_containers WHERE (userupload & 1) AND NOT (registered & 1))");
				$database->query();
				$database->setQuery("INSERT INTO #__permissions(SELECT 0 , 'Registered', 2, 'download', 'remosFolder', id, 0 FROM #__downloads_containers WHERE (userupload & 2) AND NOT (registered & 2))");
				$database->query();
				$database->setQuery("INSERT INTO #__permissions(SELECT 0 , 'Nobody', 2, 'upload', 'remosFolder', id, 0 FROM #__downloads_containers WHERE NOT(userupload & 1) AND NOT (registered & 1))");
				$database->query();
				$database->setQuery("INSERT INTO #__permissions(SELECT 0 , 'Nobody', 2, 'download', 'remosFolder', id, 0 FROM #__downloads_containers WHERE NOT(userupload & 2) AND NOT (registered & 2))");
				$database->query();
			}
		}

		$database->setQuery ("SHOW COLUMNS FROM #__downloads_repository");
		$fields = $database->loadObjectList();
		$fieldnames = array();
		foreach ($fields as $field) $fieldnames[] = $field->Field;
		
		$database->setQuery("ALTER TABLE #__downloads_repository MODIFY id int(11) NOT NULL auto_increment");
		$database->query();
		$database->setQuery("UPDATE #__downloads_repository AS r1 LEFT JOIN #__downloads_repository AS r2 ON r2.id = 1 SET r1.id = 1 WHERE r1.id = 0 AND r2.id IS NULL");
		$database->query();

		if (!in_array('Use_Database', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Use_Database` smallint NOT NULL default 1 AFTER `version`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('keywords', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `keywords` varchar(255) NOT NULL default \'\' AFTER `windowtitle`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Large_Image_Width', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Large_Image_Width` smallint NOT NULL default 600 AFTER `Small_Image_Height`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Large_Image_Height', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Large_Image_Height` smallint NOT NULL default 600 AFTER `Large_Image_Width`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Max_Thumbnails', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Max_Thumbnails` smallint NOT NULL default 0 AFTER `Favourites_Max`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Allow_Large_Images', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Allow_Large_Images` tinyint unsigned NOT NULL default 1 AFTER `Allow_Votes`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('download_text', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `download_text` text NOT NULL AFTER `Time_Stamp`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Max_Down_Per_Day', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Max_Down_Per_Day` int NOT NULL default 5 AFTER `Max_Up_Per_Day`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Max_Down_Reg_Day', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Max_Down_Reg_Day` int NOT NULL default 10 AFTER `Max_Down_Per_Day`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Max_Down_File_Day', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Max_Down_File_Day` int NOT NULL default 2 AFTER `Max_Down_Per_Day`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Allow_User_Delete', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Allow_User_Delete` tinyint unsigned NOT NULL default 0 AFTER `Allow_User_Edit`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Make_Auto_Thumbnail', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Make_Auto_Thumbnail` tinyint unsigned NOT NULL default 0 AFTER `Max_Thumbnails`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('preamble', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `preamble` text NOT NULL AFTER `download_text`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Default_Licence', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Default_Licence` text NOT NULL AFTER `preamble`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('customizer', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `customizer` text NOT NULL AFTER `Default_Licence`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('ExtsDisplay', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `ExtsDisplay` varchar(255) NOT NULL default  \'\' AFTER `ExtsOk`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Scribd', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Scribd` varchar(30) NOT NULL default  \'\' AFTER `ExtsDisplay`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Show_RSS_feeds', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Show_RSS_feeds` tinyint unsigned NOT NULL default 1 AFTER `See_Files_no_download`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Classification_Types', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Classification_Types` varchar(255) NOT NULL default \'\' AFTER `ExtsDisplay`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Remository_Pathway', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Remository_Pathway` tinyint unsigned NOT NULL default 0 AFTER `Allow_Large_Images`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('name', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `name` varchar(255) NOT NULL default \'\' AFTER `id`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('alias', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `alias` varchar(255) NOT NULL default \'\' AFTER `name`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Allow_File_Info', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Allow_File_Info` tinyint(3) unsigned NOT NULL default 1 AFTER `Show_RSS_feeds`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Show_Footer', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Show_Footer` tinyint(3) unsigned NOT NULL default 1 AFTER `Allow_File_Info`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Show_File_Folder_Counts', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Show_File_Folder_Counts` tinyint(3) unsigned NOT NULL default 1 AFTER `Show_Footer`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Count_Down', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Count_Down` tinyint(3) unsigned NOT NULL default 0 AFTER `Max_Down_File_Day`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Featured_Number', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Featured_Number` int(11) unsigned NOT NULL default 0 AFTER `Count_Down`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('ExtsAudio', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `ExtsAudio` varchar(255) NOT NULL default \'\' AFTER `ExtsDisplay`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('ExtsVideo', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `ExtsVideo` varchar(255) NOT NULL default \'\' AFTER `ExtsAudio`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Audio_Download', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Audio_Download` tinyint(3) unsigned NOT NULL default 0 AFTER `Enable_List_Download`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Video_Download', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Video_Download` tinyint(3) unsigned NOT NULL default 0 AFTER `Audio_Download`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('custom_names', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `custom_names` text NOT NULL AFTER `customizer`;';
			$database->setQuery($sql);
			$database->query();
		}
		
		if (!in_array('Min_Comment_length', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Min_Comment_length` smallint(6) NOT NULL default 1 AFTER `Max_Thumbnails`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Main_Authors', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Main_Authors` text NOT NULL AFTER `Default_Version`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Author_Threshold', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Author_Threshold` smallint(6) NOT NULL default 0 AFTER `Main_Authors`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Main_Page_Title', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Main_Page_Title` varchar(100) NOT NULL default \'\' AFTER `Use_Database`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Activate_AEC', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Activate_AEC` tinyint(3) unsigned NOT NULL default 0 AFTER `Allow_Large_Images`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Profile_URI', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Profile_URI` varchar(255) NOT NULL AFTER `Time_Stamp`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Set_date_locale', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Set_date_locale` varchar(20) NOT NULL AFTER `Date_Format`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Force_Language', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Force_Language` varchar(20) NOT NULL AFTER `Set_date_locale`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('Show_all_containers', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_repository`'
			   	.' ADD `Show_all_containers` tinyint(3) unsigned NOT NULL default 0 AFTER `Force_Language`;';
			$database->setQuery($sql);
			$database->query();
		}


		$database->setQuery ("SHOW COLUMNS FROM #__downloads_files");
		$fields = $database->loadObjectList();
		$fieldnames = array();
		foreach ($fields as $field) $fieldnames[] = $field->Field;

		if (in_array('filedate', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' MODIFY `filedate` datetime NOT NULL default \'0000-00-00 00:00:00\';';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('realwithid', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
			   	.' ADD `realwithid` tinyint unsigned NOT NULL default 0 AFTER `realname`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('keywords', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
			   	.' ADD `keywords` varchar(255) NOT NULL default \'\' AFTER `windowtitle`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('userid', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `userid` int NOT NULL default 0 AFTER `containerid`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('download_text', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `download_text` text NOT NULL AFTER `userupload`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('chunkcount', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `chunkcount` int NOT NULL default 0 AFTER `isblob`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('editgroup', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `editgroup` smallint NOT NULL default 0 AFTER `groupid`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('custom_1', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `custom_1` varchar(255) NOT NULL default \'\' AFTER `editgroup`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('custom_2', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `custom_2` varchar(255) NOT NULL default \'\' AFTER `custom_1`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('custom_3', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `custom_3` text NOT NULL AFTER custom_2;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('custom_4', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `custom_4` int NOT NULL default 0 AFTER custom_3;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('custom_5', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `custom_5` datetime NOT NULL default \'0000-00-00\' AFTER custom_4;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('metatype', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `metatype` tinyint NOT NULL default 0 AFTER keywords;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('oldid', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `oldid` int NOT NULL default 0 AFTER userid;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('publish_id', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `publish_id` varchar(50) NOT NULL default \'\' AFTER author_URL;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('publish_date', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `publish_date` date NOT NULL default \'0000-00-00\' AFTER publish_id;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('subtitle', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `subtitle` text NOT NULL AFTER filetitle;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('repnum', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `repnum` tinyint(3) unsigned NOT NULL default 1 AFTER id;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('publish_from', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `publish_from` datetime NOT NULL default \'0000-00-00 00:00:00\' AFTER published;';
			$database->setQuery($sql);
			$database->query();
		}
		
		if (!in_array('publish_to', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `publish_to` datetime NOT NULL default \'0000-00-00 00:00:00\' AFTER publish_from;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('republish_num', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `republish_num` tinyint(3) unsigned NOT NULL default 0 AFTER publish_to;';
			$database->setQuery($sql);
			$database->query();
		}
		
		if (!in_array('republish_unit', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `republish_unit` tinyint(3) unsigned NOT NULL default 1 AFTER republish_num;';
			$database->setQuery($sql);
			$database->query();
		}
		
		if (!in_array('listings', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `listings` int(11) unsigned NOT NULL default 0 AFTER `downloads`;';
			$database->setQuery($sql);
			$database->query();
		}
		
		if (!in_array('viewings', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `viewings` int(11) unsigned NOT NULL default 0 AFTER `listings`;';
			$database->setQuery($sql);
			$database->query();
		}
		
		if (!in_array('custom_values', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `custom_values` text NOT NULL AFTER `submitdate`;';
			$database->setQuery($sql);
			$database->query();
		}
		
		if (!in_array('author_email', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_files`'
				.' ADD `author_email` varchar(255) NOT NULL default \'\' AFTER `author_URL`;';
			$database->setQuery($sql);
			$database->query();
		}
		
		$database->setQuery ("SHOW COLUMNS FROM #__downloads_reviews");
		$fields = $database->loadObjectList();
		$fieldnames = array();
		foreach ($fields as $field) $fieldnames[] = $field->Field;

		if (!in_array('keywords', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_reviews`'
				.' ADD `keywords` varchar(255) NOT NULL default \'\' AFTER `windowtitle`;';
			$database->setQuery($sql);
			$database->query();
		}


		$database->setQuery ("SHOW COLUMNS FROM #__downloads_containers");
		$fields = $database->loadObjectList();
		$fieldnames = array();
		foreach ($fields as $field) $fieldnames[] = $field->Field;

		if (!in_array('keywords', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `keywords` varchar(255) NOT NULL default \'\' AFTER `windowtitle`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('editgroup', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `editgroup` smallint NOT NULL default 0 AFTER `groupid`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('adminauto', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `adminauto` tinyint unsigned NOT NULL default 0 AFTER `editgroup`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('userauto', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `userauto` tinyint unsigned NOT NULL default 0 AFTER `adminauto`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('autogroup', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `autogroup` smallint NOT NULL default 0 AFTER `userauto`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('userid', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `userid` int NOT NULL default 0 AFTER `autogroup`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('alias', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `alias` varchar(255) NOT NULL default \'\' AFTER `name`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('countdown', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `countdown` tinyint(3) unsigned NOT NULL default 0 AFTER `userupload`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('childcountdown', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `childcountdown` tinyint(3) unsigned NOT NULL default 0 AFTER `countdown`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('countup', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `countup` tinyint(3) unsigned NOT NULL default 0 AFTER `childcountdown`;';
			$database->setQuery($sql);
			$database->query();
		}

		if (!in_array('childcountup', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_containers`'
				.' ADD `childcountup` tinyint(3) unsigned NOT NULL default 0 AFTER `countup`;';
			$database->setQuery($sql);
			$database->query();
		}

		$database->setQuery ("SHOW COLUMNS FROM #__downloads_blob");
		$fields = $database->loadObjectList();
		$fieldnames = array();
		foreach ($fields as $field) $fieldnames[] = $field->Field;

		if (!in_array('bloblength', $fieldnames)) {
			$sql = 'ALTER TABLE `#__downloads_blob`'
			   	.' ADD `bloblength` int NOT NULL default 0 AFTER `chunkid`;';
			$database->setQuery($sql);
			$database->query();
			$sql = 'UPDATE `#__downloads_blob` SET bloblength = LENGTH(datachunk)';
			$database->setQuery($sql);
			$database->query();
			$sql = 'ALTER TABLE `#__downloads_blob` ADD INDEX `size` (`fileid`, `bloblength`)';
			$database->setQuery($sql);
			$database->query();
		}
	}
}

function com_install() {


	$installer = new remositoryInstaller();

	$remository_dir = str_replace('\\','/',dirname(__FILE__));
	$components_dir = dirname($remository_dir);
	$admin_dir = dirname($components_dir);
	$mosConfig_absolute_path = dirname($admin_dir);
	require_once($mosConfig_absolute_path.'/components/com_remository/remository.interface.php');
	$interface = remositoryInterface::getInstance();
	$mosConfig_live_site = $interface->getCfg('live_site');
	$mosConfig_lang = $interface->getCfg('lang');
	// Set some values arbitrarily to avoid errors
	$Small_Text_Len = 150;
	$Large_Text_Len = 500;
	$mosConfig_sitename = $interface->getCfg('sitename');

    $installer->dbupgrade();

	$repository = remositoryRepository::getInstance();
	$customobj = new remositoryCustomizer();
	
	$approver = $repository->Enable_User_Autoapp ? 'Registered' : 'Nobody';
	$installer->approverPermissions($approver);

	if (file_exists($mosConfig_absolute_path.'/classes')) {
		$installer->permission_all_from_dir($mosConfig_absolute_path.'/components/com_remository/');
		$admin_path = defined('_ALIRO_IS_PRESENT') ? _ALIRO_ADMIN_PATH : $mosConfig_absolute_path.'/administrator';
		$installer->permission_all_from_dir($admin_path.'/components/com_remository/');
	}
	if (!file_exists($mosConfig_absolute_path.'/components/com_remository_files')) {
		@mkdir($mosConfig_absolute_path.'/components/com_remository_files', 0755);
		$installer->setDirPerms($mosConfig_absolute_path.'/components/com_remository_files');
	}
	if (!is_dir($repository->Down_Path)) {
		$aboveroot = dirname($mosConfig_absolute_path).'/remos_downloads';
		if (is_writeable($aboveroot) AND @mkdir($aboveroot, 0755)) {
			$repository->Down_Path = $aboveroot;
			$repository->Up_Path = $aboveroot.'/uploads';
		}
		else {
			@mkdir($repository->Down_Path, 0755);
			$controlfile = $repository->Down_Path.'/.htaccess';
			if (!file_exists($controlfile) AND $fp = fopen($controlfile, 'wb')) {
				fwrite($fp, "order deny,allow\ndeny from all");
				fclose($fp);
			}
		}
		$downisok = $installer->setDirPerms ($repository->Down_Path);
		@mkdir($repository->Up_Path, 0755);
		$upisok = $installer->setDirPerms ($repository->Up_Path);
		$repository->saveValues();
	}

	$installer->makeDefaultContainer();
	$installer->makeMenuEntry();
	$itext1 = _DOWN_INSTALL_DONE1;
	$itext2 = _DOWN_INSTALL_DONE2;
	$itext3 = _DOWN_INSTALL_DONE3;
	$itext4 = _DOWN_INSTALL_DONE4;

	echo <<<INSTALL_DONE
	
	<h3>$itext1</h3>
	<h3>
	$itext2
	</h3>
	<h3>$itext3</h3>
	<p>	
		$itext4
	</p>
	<p>
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
	<input type="hidden" name="cmd" value="_s-xclick">
	<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" border="0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
	<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1">
	<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
	</form>
	</p>
	
INSTALL_DONE;

	return true;
}