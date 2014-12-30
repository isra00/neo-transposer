-- MySQL dump 10.13  Distrib 5.5.40, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: transposer
-- ------------------------------------------------------
-- Server version	5.5.40-0ubuntu0.14.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `book`
--

DROP TABLE IF EXISTS `book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `book` (
  `id_book` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang_name` varchar(50) NOT NULL,
  `details` varchar(100) NOT NULL,
  `chord_printer` varchar(50) NOT NULL,
  PRIMARY KEY (`id_book`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book`
--

LOCK TABLES `book` WRITE;
/*!40000 ALTER TABLE `book` DISABLE KEYS */;
INSERT INTO `book` VALUES (1,'Swahili','Tanzania - Kenya 2003','Swahili'),(2,'Español','España 2008','Spanish');
/*!40000 ALTER TABLE `book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `song`
--

DROP TABLE IF EXISTS `song`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `song` (
  `id_song` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_book` int(10) unsigned NOT NULL,
  `page` int(3) unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `key` enum('C','Cm','C#','C#m','Db','Dbm','D','Dm','D#','D#m','Eb','Ebm','E','Em','F','Fm','F#','F#m','Gb','Gbm','G','Gm','G#','G#m','Ab','Abm','A','Am','A#','A#m','Bb','Bbm','B','Bm') DEFAULT NULL,
  `lowest_note` char(3) NOT NULL,
  `highest_note` char(3) NOT NULL,
  PRIMARY KEY (`id_song`)
) ENGINE=MyISAM AUTO_INCREMENT=114 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `song`
--

LOCK TABLES `song` WRITE;
/*!40000 ALTER TABLE `song` DISABLE KEYS */;
INSERT INTO `song` VALUES (1,1,1,'Litania fupi ya Penitensia','Em','E2','D3'),(2,1,2,'Litania ya Penitensia','Em','E2','D3'),(3,1,95,'Yuaja Bwana Amejivika Adhama','Dm','E2','A#2'),(4,1,46,'Nani Atatutenga','Am','D2','D3'),(25,1,273,'Mbele yake wote uficha uso: Wimbo wa 4 wa Mtumishi wa Yahweh',NULL,'C2','G3'),(24,1,32,'Safari i ngumu',NULL,'E2','E3'),(23,2,NULL,'Abraham',NULL,'A1','D3'),(26,2,0,'No hay en Él parecer (Cuarto canto del Siervo de Yahveh)',NULL,'C2','G3'),(27,2,0,'Amén, Amén, Amén',NULL,'C2','A3'),(28,2,0,'A ti, Señor, levanto mi alma',NULL,'A1','D3'),(29,2,0,'Bendice, alma mía, a Yahveh',NULL,'E2','D3'),(30,1,4,'Utukufu kwa Mungu juu mbinguni',NULL,'D2','E3'),(31,1,5,'Yu Mtakatifu (Wakati wa Kwaresima)',NULL,'B1','C3'),(32,1,5,'Mtakatifu (Mwakani)',NULL,'G1','B2'),(33,1,6,'Mtakatifu ndiye Bwana - Mtakatifu wa Vibanda (Wakati wa Majilio)',NULL,'F2','F3'),(34,1,6,'Mtakatifu, Mtakatifu - Hosanna ya Matawi (wa Wakati wa Pasaka)',NULL,'D2','G3'),(35,1,7,'Mtakatifu (1983)',NULL,'A1','G3'),(36,1,7,'Mtakatifu, Mtakatifu (1988)',NULL,'E2','D3'),(37,1,24,'Wimbo wa Katikati (1)',NULL,'A1','B2'),(38,1,24,'Wimbo wa Katikati (2)',NULL,'B1','D3'),(39,1,26,'Aleluya ya Pasaka',NULL,'A1','D3'),(40,1,26,'Aleluya kwa Shangilio la Injili (1)',NULL,'A1','B2'),(41,1,26,'Aleluya kwa Shangilio la Injili (2)',NULL,'D2','B3'),(42,1,31,'Amefufuka',NULL,'E2','D3'),(43,1,32,'Kwako we, Mji wa Mungu',NULL,'E2','E3'),(44,1,35,'Yahweh u Mungu wangu',NULL,'D2','D3'),(45,1,37,'Tazama ilivyo vema',NULL,'E2','E3'),(46,1,37,'Jinsi ilivyo vema na kupendeza',NULL,'E2','F3'),(47,1,38,'Tazameni ilivyo vema, onjeni ulivyo mtamu',NULL,'A1','C3'),(48,1,39,'Asante Yahweh',NULL,'D2','F#3'),(49,1,40,'Wimbo wa vijana watatu katika tanuru',NULL,'E2','C3'),(50,1,41,'Wimbo wa vijana watatu katika tanuru (Sehemu ya pili)',NULL,'D2','D3'),(51,1,43,'Aleluya, msifuni Mungu',NULL,'A2','D3'),(52,1,44,'Evenu Shalom Alehem',NULL,'A1','D3'),(53,1,45,'Aleluya, umekuja Ufalme',NULL,'D2','B2'),(54,1,47,'Wimbo wa Bikira Maria',NULL,'E2','E3'),(55,1,48,'Nitatwaa na kuinua kikombe cha wokovu',NULL,'A1','B2'),(56,1,49,'Wakati Bwana alirejeza',NULL,'B1','B2'),(57,1,50,'Wimbo wa Zakaria',NULL,'E2','D3'),(58,1,51,'Ee mauti, u wapi ushindi wako?',NULL,'A1','G2'),(59,1,52,'Enyi mbingu, dondokeni toka juu',NULL,'E2','D3'),(60,1,53,'Pentekoste',NULL,'A1','C#3'),(61,1,54,'Njoo, Mwana wa Mtu',NULL,'E2','D#3'),(62,1,55,'Abrahamu',NULL,'A1','D3'),(63,1,57,'Mwaliko wa Pasaka',NULL,'D2','E3'),(64,1,57,'Msifuni Bwana watu wote wa dunia',NULL,'E2','E3'),(65,1,59,'Enyi malango inueni vichwa vyenu',NULL,'D2','C#3'),(66,1,62,'Nihurumie, ee Mungu',NULL,'E2','C3'),(67,1,63,'Nihurumie Bwana, nihurumie',NULL,'D2','C3'),(68,1,64,'Wimbo wa kujishusha kwake Yesu - Tenzi ya kenosis',NULL,'D#2','D#3'),(69,1,66,'Maria, mdogo Maria',NULL,'D2','F#3'),(70,1,67,'Nainua macho kwa milima',NULL,'G2','G3'),(71,1,68,'Ikiwa leo mwasikia sauti yake',NULL,'C#2','E3'),(72,1,74,'Amen, amen, amen',NULL,'A1','F#3'),(73,1,76,'Kwa upendo wa ndugu zangu',NULL,'A1','B2'),(74,1,77,'Nilingojea, nilingojea Bwana',NULL,'C1','C2'),(75,1,78,'Nataka kuimba',NULL,'E2','C#3'),(76,1,81,'Imbeni kwa furaha',NULL,'E2','D3'),(77,1,82,'Niamkapo, nishibishwe kwa sura yako, ee Bwana',NULL,'D2','D3'),(78,1,84,'Sitakufa',NULL,'D2','D3'),(79,1,86,'Waambieni waliovunjika moyo',NULL,'A1','F3'),(80,1,87,'Ee Mungu, u Mungu wangu',NULL,'E2','E3'),(81,1,90,'Mbele ya malaika',NULL,'B1','A2'),(82,1,92,'Mwimbie Yahweh, Yerusalemu',NULL,'D2','B2'),(83,1,93,'Nafsi yangu imbariki Bwana (Wimbo wa Tobiti)',NULL,'G1','C3'),(84,1,94,'Maskani yako yapendeza kama nini',NULL,'G2','G3'),(85,1,95,'Siku ya pumziko',NULL,'B1','C3'),(86,1,102,'Bwana atangaza habari',NULL,'D2','E3'),(87,1,104,'Binti za Yerusalemu (Utenzi wa mazishi)',NULL,'E2','E3'),(88,1,110,'Mbarikiwa Maria',NULL,'C2','D3'),(89,1,110,'Salaam, malkia wa Mbingu',NULL,'G#2','E3'),(90,1,112,'Maria, nyumba ya baraka',NULL,'B1','D3'),(91,1,113,'Nitamhimidi Bwana kila wakati',NULL,'F2','F3'),(92,1,117,'Wewe u mzuri',NULL,'B1','G#3'),(93,1,123,'Chipukizi latoka shinani mwa Yese',NULL,'G2','E3'),(94,1,128,'Utukufu (Nakuja kukusanya)',NULL,'B1','E3'),(95,1,129,'Furahini wenye haki katika Bwana',NULL,'D2','E3'),(96,1,134,'Utanijulisha njia ya uzima',NULL,'F2','G3'),(97,1,135,'Nendeni kutangazia ndugu zangu',NULL,'A1','E3'),(98,1,137,'Ee Bwana, sikiliza sala yangu',NULL,'A2','D3'),(99,1,206,'Naona mbingu wazi',NULL,'B1','E3'),(100,1,212,'Mbarikiwa awe Mungu',NULL,'F#1','F#2'),(101,1,217,'Nataka kuenda Yerusalemu',NULL,'D2','G3'),(102,1,219,'Utenzi wa Msalaba Mtukufu',NULL,'B1','F#3'),(103,1,263,'Kwa kuwa Mungu',NULL,'C#2','E3'),(104,1,264,'Kama wahukumiwa kifo',NULL,'G2','E3'),(105,1,267,'Anibusu kwa busu za kinywa chake',NULL,'C#2','E3'),(106,1,269,'Njoo toka Lebanoni',NULL,'D2','B2'),(107,1,272,'Kondoo jike wa Mungu',NULL,'G2','D3'),(108,1,274,'Wimbo wa Mwana Kondoo',NULL,'A1','D3'),(109,1,275,'Nani huyu apandaye toka jangwa',NULL,'A1','D3'),(110,1,279,'Ee Yesu, mpendwa wangu',NULL,'A1','A2'),(111,1,280,'Nitwae mbinguni',NULL,'B1','D#3'),(112,1,282,'Ishara kuu',NULL,'C1','E3'),(113,1,283,'Nanyosha mikono',NULL,'E2','E3');
/*!40000 ALTER TABLE `song` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `song_chord`
--

DROP TABLE IF EXISTS `song_chord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `song_chord` (
  `id_song` int(10) unsigned NOT NULL,
  `chord` char(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `position` int(10) unsigned NOT NULL,
  UNIQUE KEY `id_song_chord` (`id_song`,`chord`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `song_chord`
--

LOCK TABLES `song_chord` WRITE;
/*!40000 ALTER TABLE `song_chord` DISABLE KEYS */;
INSERT INTO `song_chord` VALUES (1,'Em',0),(1,'Am',0),(1,'C',0),(1,'B7',0),(2,'Em',0),(2,'Am',0),(2,'B7',0),(2,'C',0),(3,'Dm',0),(3,'Gm',0),(4,'Am',0),(4,'G',0),(4,'F',0),(4,'E',0),(4,'E7',0),(25,'Am',0),(25,'G7',0),(25,'F',0),(25,'G',0),(24,'Am',0),(24,'E',0),(24,'E7',0),(24,'A7',0),(24,'Dm',0),(24,'F',0),(23,'Am',0),(23,'Dm',0),(23,'E',0),(23,'G',0),(23,'F',0),(25,'C',0),(26,'F',3),(26,'G',2),(26,'Am',1),(26,'C',0),(27,'C',0),(27,'Am',1),(27,'G7',2),(27,'G',3),(27,'F',4),(27,'E',5),(28,'Am',0),(28,'E',1),(28,'Dm',2),(28,'F',3),(29,'Am',0),(29,'E',1),(29,'Dm',2),(30,'D',0),(30,'A',1),(30,'G',2),(30,'F#m',3),(30,'Em',4),(31,'Em',0),(31,'G',1),(31,'Am',2),(32,'Am',0),(32,'G',1),(32,'F',2),(32,'E',3),(33,'A',0),(33,'E',1),(33,'F',2),(33,'Am',3),(33,'E7',4),(34,'Dm',0),(34,'Gm',1),(34,'A',2),(34,'A7',3),(35,'Am',0),(35,'G',1),(35,'F',2),(35,'E',3),(35,'Dm',4),(36,'Am',0),(36,'G',1),(36,'F',2),(36,'E7',3),(37,'E',0),(37,'A',1),(38,'Em',0),(38,'B7',1),(38,'Am',2),(38,'C',3),(39,'D',0),(39,'A',1),(40,'D',0),(40,'G',1),(40,'A7',2),(40,'Bm',3),(40,'A',4),(41,'Em',0),(41,'D',1),(41,'Am',2),(42,'Am',0),(42,'G',1),(42,'F',2),(42,'E',3),(43,'Am',0),(43,'E',1),(43,'E7',2),(43,'G',3),(43,'F',4),(44,'Em',0),(44,'E7',1),(44,'F',2),(44,'G',3),(45,'Em',0),(45,'Am',1),(45,'B7',2),(45,'C',3),(45,'D',4),(45,'G',5),(45,'Bm',6),(46,'Am',0),(46,'Dm',1),(46,'E',2),(46,'F',3),(46,'G',4),(47,'Em',0),(47,'C',1),(47,'B7',2),(48,'Em',0),(48,'D',1),(48,'Am',2),(48,'B7',3),(48,'C',4),(48,'G',5),(48,'Bm',6),(49,'G',0),(49,'F#m',1),(49,'E',2),(49,'A',3),(49,'B',4),(49,'Em',5),(49,'D',6),(49,'C',7),(49,'B7',8),(50,'Em',0),(50,'E7',1),(51,'E',0),(51,'A',1),(51,'D',2),(52,'Dm',0),(52,'Gm',1),(52,'A',2),(52,'A7',3),(53,'G',0),(53,'B7',1),(53,'Em',2),(53,'D7',3),(54,'D',0),(54,'E',1),(54,'A',2),(54,'A7',3),(55,'D',0),(55,'F#m',1),(55,'D7',2),(55,'Em',3),(55,'G',4),(55,'A',5),(55,'Bm',6),(55,'E7',7),(56,'D',0),(56,'Em',1),(56,'D7',2),(56,'G',3),(56,'A',4),(57,'Em',0),(57,'Am',1),(57,'B7',2),(58,'Dm',0),(58,'Am',1),(58,'D7',2),(58,'G',3),(58,'Bm',4),(58,'C',5),(58,'F#',6),(58,'D',7),(59,'Am',0),(59,'F',1),(59,'G',2),(59,'E',3),(60,'Dm',0),(60,'F',1),(60,'A#',2),(60,'A7',3),(60,'C',4),(60,'A',5),(60,'G',6),(61,'Em',0),(61,'Am',1),(61,'B7',2),(61,'C',3),(62,'Am',0),(62,'Dm',1),(62,'E',2),(62,'G',3),(62,'F',4),(63,'G',0),(63,'D',1),(63,'C',2),(63,'D7',3),(63,'Bm',4),(63,'Em',5),(64,'G',0),(64,'Am',1),(64,'C',2),(64,'B7',3),(65,'E',0),(65,'G',1),(65,'A',2),(65,'B',3),(65,'Em',4),(65,'Am',5),(65,'Bm',6),(66,'Em',0),(66,'Am',1),(67,'Em',0),(67,'D',1),(67,'Am',2),(68,'Em',0),(68,'B7',1),(68,'G',2),(68,'Am',3),(68,'D',4),(68,'C',5),(69,'D',0),(69,'F#m',1),(69,'G',2),(69,'Em',3),(69,'Em6',4),(69,'A7',5),(69,'A',6),(70,'G',0),(70,'Bm',1),(70,'C',2),(70,'D',3),(71,'E',0),(71,'F#m',1),(71,'B7',2),(71,'C#m',3),(71,'G#',4),(72,'A',0),(72,'F#m',1),(72,'E',2),(72,'D',3),(72,'C#m',4),(73,'Em',0),(73,'Am',1),(73,'B7',2),(74,'Am',0),(74,'Em',1),(74,'F',2),(74,'E',3),(75,'D',0),(75,'F#m',1),(75,'G',2),(75,'A',3),(75,'Em',4),(76,'Am',0),(76,'G',1),(76,'F',2),(76,'E',3),(77,'Am',0),(77,'F',1),(77,'Dm',2),(77,'E',3),(78,'Em',0),(78,'C',1),(78,'B7',2),(78,'G',3),(79,'Am',0),(79,'Dm',1),(79,'F',2),(79,'E',3),(79,'C',4),(79,'Em',5),(80,'Am',0),(80,'Dm',1),(80,'F',2),(80,'E',3),(81,'C',0),(81,'E',1),(81,'Dm9',2),(81,'G',3),(81,'F',4),(82,'Em',0),(82,'A',1),(82,'Am',2),(82,'D',3),(82,'Bm',4),(83,'Am',0),(83,'G',1),(84,'C',0),(84,'Em',1),(84,'Am',2),(84,'F',3),(84,'G',4),(85,'Em',0),(85,'Am',1),(86,'G',0),(86,'Em',1),(86,'Am',2),(86,'Bm',3),(86,'A',4),(87,'Am',0),(87,'F',1),(87,'G',2),(87,'E',3),(88,'Am',0),(88,'G',1),(88,'F',2),(88,'E',3),(88,'Dm',4),(89,'Am',0),(89,'G',1),(89,'F',2),(89,'E',3),(89,'E7',4),(90,'Am',0),(90,'Dm',1),(90,'E',2),(90,'F',3),(90,'G',4),(91,'Dm',0),(91,'C',1),(91,'A',2),(91,'A#',3),(92,'E',0),(92,'C#m',1),(92,'G#',2),(92,'A',3),(92,'F#m',4),(92,'B',5),(93,'G',0),(93,'Em',1),(93,'C',2),(93,'D',3),(93,'B7',4),(93,'Am',5),(94,'E',0),(94,'A',1),(94,'F#',2),(94,'B7',3),(94,'C#m',4),(94,'G#',5),(95,'G',0),(95,'Em',1),(95,'C',2),(95,'Am',3),(95,'B7',4),(95,'D',5),(96,'Am',0),(96,'F',1),(96,'Dm',2),(96,'E',3),(97,'Am',0),(97,'G',1),(97,'E7',2),(97,'Dm',3),(97,'F',4),(97,'C',5),(98,'C',0),(98,'D',1),(98,'Cm',2),(98,'Gm',3),(98,'D7',4),(99,'E',0),(99,'F',1),(99,'G',2),(99,'C',3),(99,'Am',4),(100,'D',0),(100,'Em',1),(100,'A7',2),(100,'F#',3),(100,'Bm',4),(100,'A',5),(100,'G',6),(101,'E',0),(101,'Am',1),(101,'Dm',2),(101,'F',3),(102,'E',0),(102,'G#',1),(102,'C#m',2),(102,'F#m',3),(102,'B7',4),(103,'D',0),(103,'F#',1),(103,'Bm',2),(103,'A',3),(103,'G',4),(104,'Am',0),(104,'G',1),(104,'F',2),(104,'E',3),(105,'Dm',0),(105,'A#',1),(105,'A',2),(105,'A7',3),(106,'Em',0),(106,'D',1),(106,'G',2),(106,'A',3),(106,'Am',4),(106,'C',5),(107,'Em',0),(107,'Am',1),(107,'C',2),(107,'B7',3),(108,'Dm',0),(108,'Gm',1),(108,'A7',2),(108,'F',3),(108,'Am',4),(109,'Am',0),(109,'F',1),(109,'E',2),(109,'G',3),(110,'Am',0),(110,'F',1),(110,'E',2),(110,'G',3),(111,'E',0),(111,'C#m',1),(111,'G#',2),(111,'A',3),(111,'B7',4),(112,'Am',0),(112,'G',1),(112,'F',2),(112,'E',3),(113,'D',0),(113,'Bm',1),(113,'G',2),(113,'E',3),(113,'A',4),(113,'F#',5);
/*!40000 ALTER TABLE `song_chord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id_user` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `lowest_note` char(3) DEFAULT NULL,
  `highest_note` char(3) DEFAULT NULL,
  `id_book` int(10) unsigned DEFAULT NULL,
  `chord_printer` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-12-31  1:33:51
