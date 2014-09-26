-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 26, 2014 at 01:17 AM
-- Server version: 5.5.8
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `polecat`
--

-- --------------------------------------------------------

--
-- Table structure for table `article`
--

DROP TABLE IF EXISTS `article`;
CREATE TABLE IF NOT EXISTS `article` (
  `articleId` char(36) NOT NULL,
  `articleName` varchar(255) NOT NULL,
  PRIMARY KEY (`articleId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `resourceId` char(36) NOT NULL,
  `statusCode` int(11) NOT NULL,
  `mimeType` varchar(255) NOT NULL DEFAULT 'Content-type: text/xml; charset=utf-8',
  `lastModifiedTime` int(11) NOT NULL,
  `cacheData` blob NOT NULL,
  UNIQUE KEY `cacheEntryKey` (`resourceId`,`statusCode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `lastModifiedTime` int(11) NOT NULL,
  PRIMARY KEY (`className`),
  KEY `scope` (`classScope`,`isRequired`),
  KEY `prid` (`classId`),
  KEY `classLibraryId` (`classLibraryId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Able Polecat class registry';

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

-- --------------------------------------------------------

--
-- Table structure for table `constraint`
--

DROP TABLE IF EXISTS `constraint`;
CREATE TABLE IF NOT EXISTS `constraint` (
  `resourceId` char(36) NOT NULL,
  `constraintId` char(36) NOT NULL,
  `authorityId` char(36) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `permissionId` (`resourceId`,`constraintId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `error`
--

DROP TABLE IF EXISTS `error`;
CREATE TABLE IF NOT EXISTS `error` (
  `errorId` bigint(20) NOT NULL AUTO_INCREMENT,
  `errorTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `errorType` varchar(16) NOT NULL DEFAULT 'info',
  `errorFile` varchar(255) NOT NULL,
  `errorLine` int(11) NOT NULL,
  `errorClass` varchar(255) NOT NULL,
  `errorFunction` varchar(255) NOT NULL,
  `errorMessage` text NOT NULL,
  PRIMARY KEY (`errorId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `interface`
--

DROP TABLE IF EXISTS `interface`;
CREATE TABLE IF NOT EXISTS `interface` (
  `interfaceName` varchar(255) NOT NULL,
  PRIMARY KEY (`interfaceName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

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

-- --------------------------------------------------------

--
-- Table structure for table `permission`
--

DROP TABLE IF EXISTS `permission`;
CREATE TABLE IF NOT EXISTS `permission` (
  `sessionNumber` int(11) NOT NULL,
  `resourceId` char(36) NOT NULL,
  `constraintId` char(36) NOT NULL,
  `subjectId` char(36) NOT NULL,
  `authorityId` char(36) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `permissionId` (`sessionNumber`,`resourceId`,`constraintId`,`subjectId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

DROP TABLE IF EXISTS `request`;
CREATE TABLE IF NOT EXISTS `request` (
  `requestId` bigint(20) NOT NULL AUTO_INCREMENT,
  `requestTime` int(11) NOT NULL,
  `remoteAddress` varchar(32) NOT NULL,
  `remotePort` varchar(16) NOT NULL,
  `userAgent` varchar(255) NOT NULL,
  `requestMethod` varchar(16) NOT NULL,
  `requestUri` varchar(255) NOT NULL,
  `transactionId` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`requestId`),
  KEY `remoteAddress` (`remoteAddress`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `resource`
--

DROP TABLE IF EXISTS `resource`;
CREATE TABLE IF NOT EXISTS `resource` (
  `resourceId` char(36) NOT NULL DEFAULT 'UUID()',
  `hostName` varchar(255) NOT NULL,
  `resourceName` varchar(255) NOT NULL,
  `resourceClassName` varchar(255) NOT NULL,
  `transactionClassName` varchar(255) DEFAULT NULL,
  `authorityClassName` varchar(255) DEFAULT NULL COMMENT 'Immediate access control authority (first in CoR).',
  `resourceDenyCode` int(11) NOT NULL DEFAULT '403',
  `lastModifiedTime` int(11) NOT NULL,
  PRIMARY KEY (`resourceId`),
  UNIQUE KEY `resourceName` (`hostName`,`resourceName`),
  KEY `resourceClassName` (`resourceClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `response`
--

DROP TABLE IF EXISTS `response`;
CREATE TABLE IF NOT EXISTS `response` (
  `resourceId` char(36) NOT NULL,
  `statusCode` int(11) NOT NULL,
  `docType` text,
  `defaultHeaders` text,
  `responseClassName` varchar(255) NOT NULL,
  `templateFullPath` varchar(255) DEFAULT NULL,
  `lastModifiedTime` int(11) NOT NULL,
  UNIQUE KEY `responseKey` (`resourceId`,`statusCode`),
  KEY `responseClassName` (`responseClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `sessionNumber` int(11) NOT NULL,
  `roleId` char(36) NOT NULL,
  `userId` int(11) NOT NULL DEFAULT '1',
  `roleData` blob,
  KEY `userId` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `savepoint`
--

DROP TABLE IF EXISTS `savepoint`;
CREATE TABLE IF NOT EXISTS `savepoint` (
  `savepointId` varchar(24) NOT NULL,
  `transactionId` varchar(24) NOT NULL,
  `savepointName` varchar(255) NOT NULL,
  PRIMARY KEY (`savepointId`),
  KEY `transactionId` (`transactionId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
CREATE TABLE IF NOT EXISTS `session` (
  `sessionNumber` int(11) NOT NULL AUTO_INCREMENT,
  `phpSessionId` varchar(255) NOT NULL,
  `hostName` varchar(255) NOT NULL,
  `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sessionNumber`),
  UNIQUE KEY `phpSessionId` (`phpSessionId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

DROP TABLE IF EXISTS `transaction`;
CREATE TABLE IF NOT EXISTS `transaction` (
  `transactionId` varchar(24) NOT NULL,
  `sessionNumber` int(11) NOT NULL,
  `requestMethod` varchar(16) NOT NULL,
  `resourceId` varchar(36) NOT NULL,
  `createTime` int(11) NOT NULL,
  `updateTime` int(11) NOT NULL,
  `savepointId` varchar(24) NOT NULL DEFAULT 'OPEN',
  `status` varchar(32) NOT NULL DEFAULT 'PENDING',
  `parentTransactionId` varchar(24) DEFAULT NULL,
  PRIMARY KEY (`transactionId`),
  KEY `requestMethod` (`requestMethod`),
  KEY `resourceId` (`resourceId`),
  KEY `sessionNumber` (`sessionNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
