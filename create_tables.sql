-- MySQL dump 10.13  Distrib 8.0.26, for Linux (x86_64)
--
-- Host: localhost    Database: transposer
-- ------------------------------------------------------
-- Server version	8.0.26-0ubuntu0.20.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `book`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book` (
  `id_book` int unsigned NOT NULL AUTO_INCREMENT,
  `lang_name` varchar(50) NOT NULL,
  `details` varchar(100) NOT NULL,
  `chord_printer` varchar(50) NOT NULL,
  `locale` char(2) NOT NULL,
  `song_count` smallint unsigned NOT NULL COMMENT 'Total # of songs that should be present. For management purposes only.',
  PRIMARY KEY (`id_book`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log_voice_range`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `log_voice_range` (
  `id_user` int NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `method` set('wizard','manual','auto_unhappy') NOT NULL,
  `lowest_note` char(3) NOT NULL,
  `highest_note` char(3) NOT NULL,
  KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `song`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `song` (
  `id_song` int unsigned NOT NULL AUTO_INCREMENT,
  `id_book` int unsigned NOT NULL,
  `page` int unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `lowest_note` char(3) NOT NULL,
  `highest_note` char(3) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `first_chord_is_tone` tinyint(1) DEFAULT '0',
  `people_lowest_note` char(3) DEFAULT NULL,
  `people_highest_note` char(3) DEFAULT NULL,
  `artistic_adjustment` tinyint DEFAULT NULL,
  PRIMARY KEY (`id_song`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM AUTO_INCREMENT=1205 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `song_chord`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `song_chord` (
  `id_song` int unsigned NOT NULL,
  `chord` char(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `position` int unsigned NOT NULL,
  UNIQUE KEY `id_song_chord` (`id_song`,`chord`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `transposition_feedback`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transposition_feedback` (
  `id_song` int unsigned NOT NULL,
  `id_user` int unsigned NOT NULL,
  `worked` tinyint(1) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_lowest_note` char(3) NOT NULL,
  `user_highest_note` char(3) NOT NULL,
  `not_equivalent_deviation` tinyint DEFAULT '0',
  `not_equivalent_capo` tinyint DEFAULT '0',
  `transposition` varchar(20) DEFAULT NULL,
  `pc_status` set('no_people_range_data','already_compatible','wider_than_singer','adjusted_wider','too_low_for_people','too_high_for_people','adjusted_well','not_adjusted_wider') DEFAULT NULL,
  `deviation_from_center` tinyint DEFAULT NULL,
  `centered_score_rate` float DEFAULT NULL,
  UNIQUE KEY `id_song_id_user` (`id_song`,`id_user`),
  KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unhappy_user`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unhappy_user` (
  `id_user` int unsigned NOT NULL,
  `time_unhappy` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `took_action` datetime DEFAULT NULL,
  `action` char(10) DEFAULT NULL,
  `perf_before_action` decimal(5,4) unsigned DEFAULT NULL,
  UNIQUE KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id_user` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `lowest_note` char(3) DEFAULT NULL,
  `highest_note` char(3) DEFAULT NULL,
  `id_book` int unsigned DEFAULT NULL,
  `register_ip` varchar(39) DEFAULT NULL COMMENT 'Length=39 for IPv6',
  `register_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `wizard_step1` varchar(13) DEFAULT NULL,
  `wizard_lowest_attempts` int unsigned DEFAULT NULL,
  `wizard_highest_attempts` int unsigned DEFAULT NULL,
  `country` char(2) DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=34500 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2022-03-20 23:56:56

# Deprecated?

DELIMITER ;;

DROP FUNCTION IF EXISTS `NoteToNumber`;;
CREATE FUNCTION `NoteToNumber` (`note_code` char(3)) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE note_number INT;
    SET note_number = CAST(SUBSTRING(note_code, LENGTH(note_code)) AS UNSIGNED);

    RETURN
        CASE
            WHEN note_code LIKE 'C#%' THEN 2 + 12 * (note_number - 1)
            WHEN note_code LIKE 'C%' THEN 1 + 12 * (note_number - 1)
            WHEN note_code LIKE 'D#%' THEN 4 + 12 * (note_number - 1)
            WHEN note_code LIKE 'D%' THEN 3 + 12 * (note_number - 1)
            WHEN note_code LIKE 'E%' THEN 5 + 12 * (note_number - 1)
            WHEN note_code LIKE 'F#%' THEN 7 + 12 * (note_number - 1)
            WHEN note_code LIKE 'F%' THEN 6 + 12 * (note_number - 1)
            WHEN note_code LIKE 'G#%' THEN 9 + 12 * (note_number - 1)
            WHEN note_code LIKE 'G%' THEN 8 + 12 * (note_number - 1)
            WHEN note_code LIKE 'A#%' THEN 11 + 12 * (note_number - 1)
            WHEN note_code LIKE 'A%' THEN 10 + 12 * (note_number - 1)
            WHEN note_code LIKE 'B%' THEN 12 + 12 * (note_number - 1)
            ELSE -1
        END;
END;;

DELIMITER ;
