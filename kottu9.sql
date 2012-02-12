-- Released by Janith Leanage and Indi Samarajiva under
-- the GNU Affero General Public License

-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 11, 2011 at 02:17 PM
-- Server version: 5.5.8
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `kottu9`
--

-- --------------------------------------------------------

--
-- Table structure for table `blog`
--

CREATE TABLE IF NOT EXISTS `blog` (
  `bid` int(11) NOT NULL AUTO_INCREMENT,
  `blogName` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `blogURL` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `blogRSS` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `access_ts` int(11) NOT NULL DEFAULT '0',
  `author` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  PRIMARY KEY (`bid`),
  UNIQUE KEY `blogURL` (`blogURL`),
  UNIQUE KEY `blogURL_2` (`blogURL`,`blogRSS`),
  KEY `author` (`author`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE IF NOT EXISTS `comment` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `postid` int(11) NOT NULL,
  `author` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  `permalink` varchar(320) NOT NULL,
  PRIMARY KEY (`cid`),
  UNIQUE KEY `permalink` (`permalink`),
  KEY `author` (`author`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=276 ;


--
-- Table structure for table `community`
--

CREATE TABLE IF NOT EXISTS `community` (
  `comid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `coordinates` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`comid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;


-- --------------------------------------------------------

--
-- Table structure for table `commuser`
--

CREATE TABLE IF NOT EXISTS `commuser` (
  `comid` int(11) NOT NULL,
  `uid` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  `moderator` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`comid`,`uid`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `post`
--

CREATE TABLE IF NOT EXISTS `post` (
  `postID` int(11) NOT NULL AUTO_INCREMENT,
  `blogID` int(11) NOT NULL,
  `link` varchar(220) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(192) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  `lastcomcheck` int(11) NOT NULL,
  PRIMARY KEY (`postID`),
  UNIQUE KEY `link` (`link`),
  KEY `blogID` (`blogID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27462 ;



-- --------------------------------------------------------

--
-- Table structure for table `postrating`
--

CREATE TABLE IF NOT EXISTS `postrating` (
  `voter` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `postid` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  PRIMARY KEY (`voter`,`postid`),
  KEY `postid` (`postid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `uid` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(128) NOT NULL,
  `pw` varchar(64) NOT NULL,
  `ts` int(11) NOT NULL,
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `pic` varchar(320) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `bio` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `notifts` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `vote`
--

CREATE TABLE IF NOT EXISTS `vote` (
  `voter` varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `cid` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`voter`,`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `vote`
--


--
-- Constraints for dumped tables
--

--
-- Constraints for table `blog`
--
ALTER TABLE `blog`
  ADD CONSTRAINT `blog_ibfk_1` FOREIGN KEY (`author`) REFERENCES `user` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `commuser`
--
ALTER TABLE `commuser`
  ADD CONSTRAINT `commuser_ibfk_1` FOREIGN KEY (`comid`) REFERENCES `community` (`comid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `commuser_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `user` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`blogID`) REFERENCES `blog` (`bid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `postrating`
--
ALTER TABLE `postrating`
  ADD CONSTRAINT `postrating_ibfk_1` FOREIGN KEY (`voter`) REFERENCES `user` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `postrating_ibfk_2` FOREIGN KEY (`postid`) REFERENCES `post` (`postID`) ON DELETE CASCADE ON UPDATE CASCADE;
