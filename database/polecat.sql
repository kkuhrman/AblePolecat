-- phpMyAdmin SQL Dump
-- version 3.5.0-rc1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 26, 2013 at 08:54 AM
-- Server version: 5.5.21
-- PHP Version: 5.3.3

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
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `event_type` int(11) NOT NULL DEFAULT '0',
  `event_data` text NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Table structure for table `able_user_outh2_token`
--

CREATE TABLE IF NOT EXISTS `able_user_outh2_token` (
  `session_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'User session id.',
  `service_provider` varchar(255) NOT NULL DEFAULT '' COMMENT 'Provider of OAuth 2.0 token.',
  `token` text COMMENT 'The actual serialized OAuth 2.0 token.',
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores OAuth 2.0 tokens based on session id.';

--
-- Table structure for table `able_user_outh2_login`
--

CREATE TABLE IF NOT EXISTS `able_user_outh2_login` (
  `login_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary identifier for login attempt.',
  `login_time` int(11) NOT NULL DEFAULT '0' COMMENT 'UNIX timestamp indicating when login attempt occurred.',
  `uid` int(10) unsigned NOT NULL COMMENT 'Drupal uid of the user if new or existing or zero if login was rejected.',
  `oauth_user` text COMMENT 'Serialized OAuth 2.0 user information.',
  PRIMARY KEY (`login_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores information on attempts to login with OAuth 2.0.' AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
