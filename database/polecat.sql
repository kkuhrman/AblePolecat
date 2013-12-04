--
-- polecat.sql
-- Able Polecat database table structure
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


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

CREATE TABLE IF NOT EXISTS `class` (
  `name` varchar(255) NOT NULL COMMENT 'Name of concrete class',
  `path` varchar(255) NOT NULL COMMENT 'Full registered path to class definition',
  `method` varchar(255) NOT NULL DEFAULT '__construct' COMMENT 'Creational method',
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Able Polecat class registry';

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
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
CREATE TABLE IF NOT EXISTS `log` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `event_type` int(11) NOT NULL DEFAULT '0',
  `event_data` text NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2754 ;

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
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=43 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
