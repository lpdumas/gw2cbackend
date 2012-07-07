# ************************************************************
# Sequel Pro SQL dump
# Version 3408
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.5.9)
# Database: gw2c-backend
# Generation Time: 2012-07-07 03:54:15 +0200
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table areas_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `areas_list`;

CREATE TABLE `areas_list` (
  `id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `rangeLvl` varchar(255) NOT NULL DEFAULT '',
  `neLat` float NOT NULL,
  `neLng` float NOT NULL,
  `swLat` float NOT NULL,
  `swLng` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `areas_list` WRITE;
/*!40000 ALTER TABLE `areas_list` DISABLE KEYS */;

INSERT INTO `areas_list` (`id`, `name`, `rangeLvl`, `neLat`, `neLng`, `swLat`, `swLng`)
VALUES
	(1,'Divinity\'s Reach','',43.1959,-31.5198,33.4498,-45.9558),
	(2,'Queensdale','1-17',33.3856,-23.8623,18.4067,-48.2739),
	(3,'Kessex Hills','15-25',8.36495,-23.5547,4.59833,-51.1743),
	(4,'Gendarran Fields','25-35',29.7658,5.68555,17.5766,-22.8843),
	(5,'Black Citadel','',20.7869,57.9419,11.0814,47.9004),
	(6,'Plains of Ashford','1-15',21.7646,85.6824,7.98308,58.7329),
	(7,'Diessa Plateau','15-25',35.5412,71.4771,21.4633,47.373),
	(8,'Hoelbrak','',22.9078,34.4971,12.7475,21.2805),
	(9,'Wayfarer Foothills','',34.7687,46.5381,8.26585,35.7495),
	(10,'Snowden Drifts','15-25',35.8979,34.4971,23.9561,6.61377),
	(11,'Lion\'s Arch','',17.0253,5.52612,6.2638,-10.099);

/*!40000 ALTER TABLE `areas_list` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table marker_group
# ------------------------------------------------------------

DROP TABLE IF EXISTS `marker_group`;

CREATE TABLE `marker_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `slug` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `marker_group` WRITE;
/*!40000 ALTER TABLE `marker_group` DISABLE KEYS */;

INSERT INTO `marker_group` (`id`, `name`, `slug`)
VALUES
	(1,'Generic','generic');

/*!40000 ALTER TABLE `marker_group` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table modification_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `modification_list`;

CREATE TABLE `modification_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date_added` datetime NOT NULL,
  `value` text NOT NULL,
  `id_reference_at_submission` int(11) DEFAULT NULL,
  `is_merged` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table options
# ------------------------------------------------------------

DROP TABLE IF EXISTS `options`;

CREATE TABLE `options` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `options` WRITE;
/*!40000 ALTER TABLE `options` DISABLE KEYS */;

INSERT INTO `options` (`id`, `value`)
VALUES
	('output-filepath','/output/config.js'),
	('output-minimization','0'),
	('resources-path','assets/images/icons/32x32/');

/*!40000 ALTER TABLE `options` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table reference_list
# ------------------------------------------------------------

DROP TABLE IF EXISTS `reference_list`;

CREATE TABLE `reference_list` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `value` text NOT NULL,
  `date_added` datetime NOT NULL,
  `id_merged_modification` int(11) DEFAULT NULL,
  `max_marker_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table marker_type
# ------------------------------------------------------------

DROP TABLE IF EXISTS `marker_type`;

CREATE TABLE `marker_type` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `id_marker_group` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `marker_type` WRITE;
/*!40000 ALTER TABLE `marker_type` DISABLE KEYS */;

INSERT INTO `marker_type` (`id`, `name`, `filename`, `id_marker_group`)
VALUES
	('asurasgates','Asuras\' Gates','asuraGate.png',1),
	('dungeons','Dungeons','dungeon.png',1),
	('hearts','Hearts','hearts.png',1),
	('jumpingpuzzles','Jumping Puzzles','puzzle.png',1),
	('poi','Points of interest','poi.png',1),
	('scouts','Scouts','scout.png',1),
	('skillpoints','Skill points','skillpoints.png',1),
	('waypoints','Waypoints','waypoints.png',1);

/*!40000 ALTER TABLE `marker_type` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
