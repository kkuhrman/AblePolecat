-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 21, 2015 at 05:37 PM
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
-- Table structure for table `component`
--

DROP TABLE IF EXISTS `component`;
CREATE TABLE IF NOT EXISTS `component` (
  `componentId` char(36) NOT NULL,
  `docType` text,
  `componentClassName` varchar(255) NOT NULL,
  `templateFullPath` varchar(255) NOT NULL,
  `lastModifiedTime` int(11) NOT NULL,
  PRIMARY KEY (`componentId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `connector`
--

DROP TABLE IF EXISTS `connector`;
CREATE TABLE IF NOT EXISTS `connector` (
  `resourceId` char(36) NOT NULL,
  `requestMethod` char(16) NOT NULL DEFAULT 'GET',
  `transactionClassName` varchar(255) NOT NULL,
  `authorityClassName` varchar(255) DEFAULT NULL,
  `accessDeniedCode` int(11) NOT NULL DEFAULT '403',
  PRIMARY KEY (`resourceId`,`requestMethod`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=98 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=322 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=526 ;

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
  `templateFullPath` varchar(255) DEFAULT 'C:\\wamp\\www\\tabby\\htdocs\\theme\\default\\template\\default\\page.tpl',
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
  `remoteAddress` varchar(32) NOT NULL,
  `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sessionNumber`),
  UNIQUE KEY `phpSessionId` (`phpSessionId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=48 ;

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
