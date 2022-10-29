-- MariaDB dump 10.19  Distrib 10.4.20-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: 
-- ------------------------------------------------------
-- Server version	10.4.20-MariaDB-1:10.4.20+maria~buster-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client` varchar(5) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `account` varchar(50) DEFAULT NULL,
  `address1` varchar(30) DEFAULT NULL,
  `address2` varchar(30) DEFAULT NULL,
  `address3` varchar(30) DEFAULT NULL,
  `city` varchar(30) DEFAULT NULL,
  `state` char(2) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `country` char(2) DEFAULT 'us',
  `fuel` varchar(10) DEFAULT NULL,
  `btu` int(11) DEFAULT NULL,
  `sqft` int(11) DEFAULT NULL,
  `electric` int(11) DEFAULT NULL,
  `eui` int(11) DEFAULT NULL,
  `codename` char(5) DEFAULT NULL,
  `ip` binary(16) DEFAULT NULL,
  `firstname` varchar(30) DEFAULT NULL,
  `ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `elecRate` decimal(10,4) DEFAULT NULL,
  `fuelRate` decimal(10,4) DEFAULT NULL,
  `finRate` decimal(10,4) DEFAULT NULL,
  `finTerm` decimal(10,4) DEFAULT NULL,
  `hers` int(11) DEFAULT NULL,
  `estimated` tinyint(4) DEFAULT NULL,
  `gas` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=73583 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `api_calls`
--

DROP TABLE IF EXISTS `api_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api` char(3) NOT NULL,
  `monthcode` char(6) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `params` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api` (`api`,`ts`)
) ENGINE=InnoDB AUTO_INCREMENT=923 DEFAULT CHARSET=utf8 COMMENT='tracking API calls';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-10-29 19:18:07
