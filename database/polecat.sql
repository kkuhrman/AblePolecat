-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 08, 2014 at 09:31 PM
-- Server version: 5.5.8
-- PHP Version: 5.3.5

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;
START TRANSACTION;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `polecat`
--

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
CREATE TABLE IF NOT EXISTS `class` (
  `className` varchar(255) NOT NULL COMMENT 'Name of concrete class',
  `classId` varchar(80) NOT NULL COMMENT 'Unique Id or UUID identifies a concrete Able Polecat service or service client. class',
  `classLibraryId` varchar(255) NOT NULL,
  `classScope` varchar(32) NOT NULL COMMENT 'Indicates if class is part of core class library, third-party module, other.',
  `isRequired` char(1) NOT NULL DEFAULT 'N' COMMENT 'Indicates if class definition is required or included by core.',
  `classFullPath` varchar(255) NOT NULL COMMENT 'Full registered path to class definition',
  `classFactoryMethod` varchar(255) NOT NULL DEFAULT '__construct' COMMENT 'Creational method',
  PRIMARY KEY (`className`),
  KEY `scope` (`classScope`,`isRequired`),
  KEY `prid` (`classId`),
  KEY `classLibraryId` (`classLibraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Able Polecat class registry';

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`className`, `classId`, `classLibraryId`, `classScope`, `isRequired`, `classFullPath`, `classFactoryMethod`) VALUES
('AblePolecat_Command_Target_FirePhp', 'ef12d460-73cf-11e3-981f-0800200c9a66', '3dc86b50-7228-11e3-981f-0800200c9a66', 'MOD', 'N', 'C:\\wamp\\www\\polecat\\private\\usr\\mods\\FirePhpLog\\Target.php', '__construct'),
('AblePolecat_Log_FirePhp', '7474a61d-35d9-4d43-bb7c-848c8576ce4e', '3dc86b50-7228-11e3-981f-0800200c9a66', 'MOD', 'N', 'C:\\wamp\\www\\polecat\\private\\usr\\mods\\FirePhpLog\\Log.php', 'wakeup'),
('AblePolecat_Service_Client_Apex', 'f5ec7a80-7159-11e2-bcfd-0800200c9a666', '60c3f480-7228-11e3-981f-0800200c9a66', 'MOD', 'N', 'C:\\wamp\\www\\polecat\\private\\usr\\mods\\Apex\\Client.php', 'wakeup'),
('AblePolecat_Service_Test', '66c8d7e0-672b-11e3-949a-0800200c9a66', 'ABLEDIST.UT', 'EXT', 'N', 'C:\\wamp\\www\\polecat\\private\\usr\\abledist\\ut\\svc\\Service.php', 'wakeup');

-- --------------------------------------------------------

--
-- Table structure for table `classlib`
--

DROP TABLE IF EXISTS `classlib`;
CREATE TABLE IF NOT EXISTS `classlib` (
  `classLibraryName` varchar(255) NOT NULL,
  `classLibraryId` varchar(80) NOT NULL,
  `classLibraryType` char(4) NOT NULL,
  `major` int(11) NOT NULL,
  `minor` int(11) NOT NULL,
  `revision` int(11) NOT NULL,
  `classLibraryDirectory` varchar(255) NOT NULL,
  PRIMARY KEY (`classLibraryName`),
  UNIQUE KEY `classLibraryId` (`classLibraryId`),
  KEY `classLibraryType` (`classLibraryType`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Third-party PHP class libraries used by modules';

--
-- Dumping data for table `classlib`
--

INSERT INTO `classlib` (`classLibraryName`, `classLibraryId`, `classLibraryType`, `major`, `minor`, `revision`, `classLibraryDirectory`) VALUES
('Able Distributors Unit Tests', 'ABLEDIST.UT', 'EXT', 0, 1, 0, 'C:\\wamp\\www\\polecat\\private\\usr\\abledist\\ut'),
('Able Polecat Core Version 0.4.0-DEV', 'ABLE_POLECAT_CORE_0_4_0_DEV', 'CORE', 0, 4, 0, 'C:\\wamp\\www\\polecat\\public\\core'),
('Apex', 'e63d28f0-7243-11e3-981f-0800200c9a66', 'API', 0, 0, 0, 'C:\\wamp\\www\\polecat\\private\\usr\\libs\\Apex'),
('ApexClient', '60c3f480-7228-11e3-981f-0800200c9a66', 'MOD', 0, 1, 0, 'C:\\wamp\\www\\polecat\\private\\usr\\mods\\Apex'),
('FirePHPCore', 'f25d6870-7243-11e3-981f-0800200c9a66', 'API', 0, 3, 2, 'C:\\wamp\\www\\polecat\\private\\usr\\libs\\FirePHPCore\\0.3.2'),
('FirePHPLog', '3dc86b50-7228-11e3-981f-0800200c9a66', 'MOD', 0, 1, 0, 'C:\\wamp\\www\\polecat\\private\\usr\\mods\\FirePhpLog'),
('ProvideXClient', '775711a0-7228-11e3-981f-0800200c9a66', 'MOD', 0, 1, 0, 'C:\\wamp\\www\\polecat\\private\\usr\\mods\\ProvideX');

-- --------------------------------------------------------

--
-- Table structure for table `interface`
--

DROP TABLE IF EXISTS `interface`;
CREATE TABLE IF NOT EXISTS `interface` (
  `interfaceName` varchar(255) NOT NULL,
  PRIMARY KEY (`interfaceName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `interface`
--

INSERT INTO `interface` (`interfaceName`) VALUES
('AblePolecat_AccessControl_AgentInterface'),
('AblePolecat_AccessControl_ArticleInterface'),
('AblePolecat_AccessControl_ResourceInterface'),
('AblePolecat_AccessControl_Resource_LocaterInterface'),
('AblePolecat_AccessControl_RoleInterface'),
('AblePolecat_AccessControl_Role_UserInterface'),
('AblePolecat_AccessControl_Role_User_AuthenticatedInterface'),
('AblePolecat_AccessControl_Role_User_Authenticated_OAuth2Interface'),
('AblePolecat_AccessControl_SubjectInterface'),
('AblePolecat_ArgsListInterface'),
('AblePolecat_CacheObjectInterface'),
('AblePolecat_ConfInterface'),
('AblePolecat_DatabaseInterface'),
('AblePolecat_DataInterface'),
('AblePolecat_Data_ExchangeMapInterface'),
('AblePolecat_Data_TxfrInterface'),
('AblePolecat_DynamicObjectInterface'),
('AblePolecat_EnvironmentInterface'),
('AblePolecat_LogInterface'),
('AblePolecat_MessageInterface'),
('AblePolecat_Message_RequestInterface'),
('AblePolecat_Message_ResponseInterface'),
('AblePolecat_MetaDataInterface'),
('AblePolecat_ModeInterface'),
('AblePolecat_OverloadableInterface'),
('AblePolecat_QueryLanguageInterface'),
('AblePolecat_QueryLanguage_ExpressionInterface'),
('AblePolecat_QueryLanguage_Expression_BinaryInterface'),
('AblePolecat_QueryLanguage_Expression_Binary_SqlInterface'),
('AblePolecat_QueryLanguage_StatementInterface'),
('AblePolecat_QueryLanguage_Statement_Sql_Interface'),
('AblePolecat_Server_CheckInterface'),
('AblePolecat_Service_ClientInterface'),
('AblePolecat_Service_DtxInterface'),
('AblePolecat_Service_InitiatorInterface'),
('AblePolecat_Service_Interface'),
('AblePolecat_SessionInterface'),
('AblePolecat_TransactionInterface');

-- --------------------------------------------------------

--
-- Table structure for table `locks`
--

DROP TABLE IF EXISTS `locks`;
CREATE TABLE IF NOT EXISTS `locks` (
  `service` varchar(255) NOT NULL,
  `id` char(18) NOT NULL,
  `createdbyid` char(18) NOT NULL,
  `type` char(18) NOT NULL DEFAULT 'pending',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`service`,`id`),
  KEY `createdbyid` (`createdbyid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `locks`
--


-- --------------------------------------------------------

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
CREATE TABLE IF NOT EXISTS `log` (
  `eventId` int(11) NOT NULL AUTO_INCREMENT,
  `eventTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `userId` int(11) NOT NULL DEFAULT '0',
  `eventSeverity` char(11) NOT NULL DEFAULT '0',
  `eventMessage` text NOT NULL,
  PRIMARY KEY (`eventId`),
  KEY `user_id` (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=41 ;

--
-- Dumping data for table `log`
--

INSERT INTO `log` (`eventId`, `eventTime`, `userId`, `eventSeverity`, `eventMessage`) VALUES
(32, '2014-01-03 15:46:26', 1, 'status', 'Error in Able Polecat. 8 Undefined index: defaultHeaders in C:wampwwwpolecatpubliccoreServer.php line 301'),
(33, '2014-01-03 15:46:26', 1, 'status', 'Error in Able Polecat. 8 Undefined index: mimeType in C:wampwwwpolecatpubliccoreServer.php line 302'),
(34, '2014-01-03 15:46:26', 1, 'status', 'Error in Able Polecat. 8 Undefined index: document in C:wampwwwpolecatpubliccoreServer.php line 304'),
(35, '2014-01-06 16:48:37', 1, 'status', 'Error in Able Polecat. 8 Undefined variable: Subject in C:wampwwwpolecatpubliccoreServiceBus.php line 216'),
(36, '2014-01-06 16:48:37', 1, 'status', 'Error in Able Polecat. 4096 Argument 1 passed to AblePolecat_Command_GetRegistry::invoke() must implement interface AblePolecat_AccessControl_SubjectInterface, null given, called in C:wampwwwpolecatpubliccoreServiceBus.php on line 216 and defined in C:wampwwwpolecatpubliccoreCommandGetRegistry.php line 41'),
(37, '2014-01-06 16:52:37', 1, 'status', 'Error in Able Polecat. 8 Undefined variable: ClassInfo in C:wampwwwpolecatpubliccoreServiceBus.php line 222'),
(38, '2014-01-06 17:19:16', 1, 'status', 'Error in Able Polecat. 8 Use of undefined constant ABLE_POLECAT_EXCEPTION_SVC_CLIENT_CONNECT_FAIL - assumed ''ABLE_POLECAT_EXCEPTION_SVC_CLIENT_CONNECT_FAIL'' in C:wampwwwpolecatprivateusrmodsApexClient.php line 66'),
(39, '2014-01-07 10:18:57', 1, 'status', 'Error in Able Polecat. 2 array_flip() expects parameter 1 to be array, null given in C:wampwwwpolecatpubliccoreRegistryClass.php line 189'),
(40, '2014-01-07 10:24:29', 1, 'status', 'Error in Able Polecat. 8 Undefined property: AblePolecat_Server::$Agent in C:wampwwwpolecatpubliccoreServer.php line 379');

-- --------------------------------------------------------

--
-- Table structure for table `outh2_login`
--

DROP TABLE IF EXISTS `outh2_login`;
CREATE TABLE IF NOT EXISTS `outh2_login` (
  `login_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary identifier for login attempt.',
  `login_time` int(11) NOT NULL DEFAULT '0' COMMENT 'UNIX timestamp indicating when login attempt occurred.',
  `uid` int(10) unsigned NOT NULL COMMENT 'Drupal uid of the user if new or existing or zero if login was rejected.',
  `oauth_user` text COMMENT 'Serialized OAuth 2.0 user information.',
  PRIMARY KEY (`login_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information on attempts to login with OAuth 2.0.' AUTO_INCREMENT=1 ;

--
-- Dumping data for table `outh2_login`
--


-- --------------------------------------------------------

--
-- Table structure for table `outh2_token`
--

DROP TABLE IF EXISTS `outh2_token`;
CREATE TABLE IF NOT EXISTS `outh2_token` (
  `session_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'User session id.',
  `service_provider` varchar(255) NOT NULL DEFAULT '' COMMENT 'Provider of OAuth 2.0 token.',
  `token` text COMMENT 'The actual serialized OAuth 2.0 token.',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores OAuth 2.0 tokens based on session id.';

--
-- Dumping data for table `outh2_token`
--


-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `session_id` varchar(255) NOT NULL,
  `interface` varchar(255) NOT NULL,
  `userId` int(11) NOT NULL DEFAULT '1',
  `session_data` longblob,
  PRIMARY KEY (`session_id`,`interface`),
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `role`
--


-- --------------------------------------------------------

--
-- Table structure for table `template`
--

DROP TABLE IF EXISTS `template`;
CREATE TABLE IF NOT EXISTS `template` (
  `resourceId` varchar(255) NOT NULL,
  `mimeType` varchar(255) NOT NULL DEFAULT 'Content-type: text/xml; charset=utf-8',
  `defaultHeaders` text,
  `document` text,
  PRIMARY KEY (`resourceId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `template`
--

INSERT INTO `template` (`resourceId`, `mimeType`, `defaultHeaders`, `document`) VALUES
('52c72b2cae817', 'Content-type: text/html', 'a:0:{}', '<!DOCTYPE html>\r\n<html>\r\n  <head>\r\n    <title>Able Polecat | Status</title>\r\n  </head>\r\n  <body>\r\n    <div id="container" style="left:12px;width:520px; style="font-family: "Lucida Console", "Verdana", Sans-serif; border-style: solid; border-color: black;">\r\n    <div id="caption" style="position:relative; padding:4px; opacity:0.8;height:64px;background-color:grey; border-style: solid; border-color: white;">\r\n    <h2>Able Polecat &copy; Project</h2>\r\n    </div>\r\n    <div id="version" style="position:relative; padding:4px; background-color:palegoldenrod; border-style: solid; border-color: white;">\r\n      <h3>{POLECAT_VERSION}</h3>\r\n      <h5>{POLECAT_DBSTATE}</h5>\r\n    </div>\r\n    <div id="notice" style="position:relative; padding:4px; opacity:0.8;height:100px;background-color:grey; border-style: solid; border-color: white;">\r\n      <p>\r\n        <small>The Able Polecat Core is free software released ''as is'' under a BSD II license (see <a href="https://github.com/kkuhrman/AblePolecat/blob/master/LICENSE.md">license</a> for more detail.)</small>\r\n      </p>\r\n      <p>Copyright &copy; 2008-2013 <a href="http://www.abledistributors.com" target="new">Able Distributors Inc.</a> All rights reserved.</p>\r\n    </div>\r\n  </body>\r\n</html>');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `userAlias` varchar(255) NOT NULL,
  `clientId` varchar(255) NOT NULL,
  `userName` varchar(255) NOT NULL,
  PRIMARY KEY (`userId`),
  KEY `clientId` (`clientId`,`userName`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userId`, `userAlias`, `clientId`, `userName`) VALUES
(0, 'Anonymous', 'Able Polecat', 'Anonymous'),
(1, 'System', 'Able Polecat', 'System');
SET FOREIGN_KEY_CHECKS=1;
COMMIT;
