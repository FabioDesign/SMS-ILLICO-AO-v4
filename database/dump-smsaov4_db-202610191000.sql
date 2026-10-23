/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.7.2-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: smsaov4_db
-- ------------------------------------------------------
-- Server version	11.5.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `account_type`
--

DROP TABLE IF EXISTS `account_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `en` varchar(50) NOT NULL,
  `pt` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_type`
--

LOCK TABLES `account_type` WRITE;
/*!40000 ALTER TABLE `account_type` DISABLE KEYS */;
INSERT INTO `account_type` VALUES
(1,'Personal account','Conta pessoal'),
(2,'Community','Comunidade'),
(3,'Educational institution','Instituição de ensino'),
(4,'Organisation','Organização'),
(5,'Company','Empresa');
/*!40000 ALTER TABLE `account_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bills` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `reference` varchar(15) NOT NULL,
  `volume1_sms` decimal(10,0) NOT NULL,
  `volume2_sms` decimal(10,0) NOT NULL,
  `price` decimal(10,0) NOT NULL DEFAULT 0,
  `fees` decimal(5,0) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bills`
--

LOCK TABLES `bills` WRITE;
/*!40000 ALTER TABLE `bills` DISABLE KEYS */;
INSERT INTO `bills` VALUES
(2,'881fecdd-5d7a-4b43-b2f4-08f70ebb2690','SI-2603-0002',5,5000,11,0,1,'2026-03-19 15:41:49','2026-03-19 16:52:17',1);
/*!40000 ALTER TABLE `bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bodysms`
--

DROP TABLE IF EXISTS `bodysms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bodysms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `sender` varchar(15) NOT NULL,
  `message` text NOT NULL,
  `volume` decimal(10,0) NOT NULL,
  `chars` decimal(10,0) NOT NULL,
  `pages` decimal(10,0) NOT NULL,
  `sending_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `smstype_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bodysms`
--

LOCK TABLES `bodysms` WRITE;
/*!40000 ALTER TABLE `bodysms` DISABLE KEYS */;
INSERT INTO `bodysms` VALUES
(1,'ceba0da8-5407-4b69-bcd0-8c248966a84d','SMSAPIv4','Juste une tâche d\'envoi de SMS pour tout le personnel qui travaille sur les actions en cours.',2,93,2,'2026-10-16 00:30:06',1,'2026-10-16 00:30:09','2026-10-16 00:30:09',2,1);
/*!40000 ALTER TABLE `bodysms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `checkotp`
--

DROP TABLE IF EXISTS `checkotp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkotp` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkotp_email_unique` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `checkotp`
--

LOCK TABLES `checkotp` WRITE;
/*!40000 ALTER TABLE `checkotp` DISABLE KEYS */;
/*!40000 ALTER TABLE `checkotp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contacts`
--

DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `label` varchar(255) NOT NULL,
  `number` varchar(20) NOT NULL,
  `gender` varchar(1) DEFAULT NULL,
  `date_at` date DEFAULT NULL,
  `field1` varchar(255) DEFAULT NULL,
  `field2` varchar(255) DEFAULT NULL,
  `field3` varchar(255) DEFAULT NULL,
  `blacklist` tinyint(4) NOT NULL DEFAULT 0,
  `publipostage` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`,`publipostage`,`user_id`),
  KEY `idx_contacts_blacklist` (`blacklist`),
  KEY `idx_contacts_number` (`number`)
) ENGINE=MyISAM AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contacts`
--

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
INSERT INTO `contacts` VALUES
(1,'335d5855-31b1-44f7-81fd-56e7e7c82a07','OGOU Franck','0101439969','M','2025-08-10',NULL,NULL,NULL,0,0,'2026-02-19 21:21:31','2026-02-19 21:21:31',1),
(3,'e504f670-a605-4827-a148-77746018e83f','OGOU Désirée','950546541','F','2025-10-18','Kingsley','Fionna','Ange',0,1,'2026-02-19 21:25:00','2026-03-01 12:45:19',1),
(5,'d77b6ee9-8121-4d84-9678-290f7988248d','OGOU Atito','9107546535',NULL,NULL,NULL,NULL,NULL,0,1,'2026-02-22 11:39:36','2026-02-22 11:39:36',2),
(7,'31853c66-3a62-4742-8855-16a267fc4889','OGOU Atito','0707546535',NULL,NULL,NULL,NULL,NULL,0,0,'2026-02-22 11:47:00','2026-02-22 11:47:00',3),
(10,'ed90b145-9c12-4950-a11b-a7eb809fda7f','OGOU Elvis','950754650',NULL,NULL,NULL,NULL,NULL,0,0,'2026-02-23 09:51:31','2026-02-23 09:51:31',2),
(12,'a8e48164-6b6a-482b-a1a8-537a6363ef90','Nom prenoms 2','0102030405','M','2026-02-27','texte','texte','texte',1,0,'2026-02-26 19:01:59','2026-03-01 09:06:25',1),
(13,'8d4b997a-b154-4d27-bd4e-859493b52758','Nom prenoms 3','930102031','M','2026-02-28','texte','texte','texte',0,0,'2026-02-26 19:01:59','2026-02-26 19:01:59',1),
(14,'37dabc97-bfac-4609-8612-8e263133db29','Nom prenoms 1','910102034','F','2026-02-21','texte','texte','texte',0,0,'2026-02-26 19:19:33','2026-02-26 19:19:33',1),
(27,'797f56e4-7a3b-4934-ac9a-ddd149d7a754','Nom prenoms 2','920102035','M','2026-02-22','texte 2','texte 2','texte 2',1,0,'2026-03-05 12:51:00','2026-03-05 12:51:00',1),
(17,'0d210454-1376-42b0-b8f2-5ca2c62d6327','Nom prenoms 4','940102034','F','2026-02-24','texte','texte','texte',0,0,'2026-02-26 19:19:33','2026-02-26 19:19:33',3),
(19,'fd4a0c8a-7d88-4138-8d8d-44122c53015a','N\'ZUE Valentine','950754650','F',NULL,NULL,NULL,NULL,0,1,'2026-02-27 01:21:08','2026-02-27 01:21:08',1),
(20,'47ebc452-0edb-4684-8f70-90bb56542df3','Nom prenoms 1','910102034','F','2026-02-21','texte','texte','texte',0,1,'2026-02-27 01:29:16','2026-02-27 01:29:16',1),
(26,'d8540c35-430f-4101-bcab-ee708c78a9c3','Nom prenoms 1','910102035','F','2026-02-21','texte 1','texte 1','texte 1',0,0,'2026-03-05 12:51:00','2026-03-05 12:51:00',1),
(25,'2aeebe88-b9fb-4a8c-8882-c1576d79fb87','OGOU Kevine','0203040506','F','2020-01-25','INFAS','Sage-Femme','Aboisso',0,0,'2026-03-01 12:44:04','2026-03-01 12:44:04',1),
(23,'5c2978d1-b6d4-4b92-b6f5-099686b54b05','Nom prenoms 4','940102034','F','2026-02-24','texte','texte','texte',0,1,'2026-02-27 01:29:16','2026-02-27 01:29:16',1),
(24,'adfe3edc-070b-4bab-ade8-f6a2019a9a46','Nom prenoms 5','950102034','M','2026-02-25','texte','texte','texte',0,1,'2026-02-27 01:29:16','2026-02-27 01:29:16',1),
(28,'c8140246-cada-4253-9301-0d7e59ed622a','Nom prenoms 3','930102035','M','2026-02-23','texte 3','texte 3','texte 3',0,0,'2026-03-05 12:51:00','2026-03-05 12:51:00',1),
(29,'339d0d99-bb21-4b0d-a54c-61171ee18807','Nom prenoms 4','0304050607','F','2026-02-24','texte 4','texte 4','texte 4',1,0,'2026-03-05 12:51:00','2026-03-05 12:51:00',1),
(30,'2d1982da-8619-4d51-a0cc-04d80986b523','Nom prenoms 5','950102035','M','2026-02-25','texte 5','texte 5','texte 5',0,0,'2026-03-05 12:51:00','2026-03-05 12:51:00',1),
(31,'b569fbc5-ca05-474a-a940-e1b35127d8ac','Nom prenoms 1','910102036','F','2026-02-21','texte 1','texte 1','texte 1',0,0,'2026-03-05 12:56:31','2026-03-05 12:56:31',1),
(32,'6ecd3ad1-aa2d-4aed-aacb-56265c00fa2b','Nom prenoms 2','920102036','M','2026-02-22','texte 2','texte 2','texte 2',0,0,'2026-03-05 12:56:31','2026-03-05 12:56:31',1),
(33,'b4f1ba8f-ac95-4a24-a36a-5a1c17735a03','Nom prenoms 3','0405060708','M','2026-02-23','texte 3','texte 3','texte 3',1,0,'2026-03-05 12:56:31','2026-03-05 12:56:31',1),
(34,'1c39e8f6-658d-48ec-afed-0d565d6dac69','Nom prenoms 4','940102036','F','2026-02-24','texte 4','texte 4','texte 4',0,0,'2026-03-05 12:56:31','2026-03-05 12:56:31',1),
(35,'41c64e92-06db-4088-96a5-4f6a9fcc3d1a','Nom prenoms 5','950102036','M','2026-02-25','texte 5','texte 5','texte 5',1,0,'2026-03-05 12:56:31','2026-03-05 12:56:31',2),
(36,'352c0c8c-8cdb-4874-812c-0b3eef62ce44','Nom prenoms 1','910102037','F','2026-02-21','texte 1','texte 1','texte 1',0,0,'2026-03-05 12:58:15','2026-03-05 12:58:15',1),
(37,'8e2892af-3383-49b6-bdd9-7610fb17af7c','Nom prenoms 2','920102037','M','2026-02-22','texte 2','texte 2','texte 2',0,0,'2026-03-05 12:58:15','2026-03-05 12:58:15',1),
(38,'dca20244-b06b-4599-aae2-c95acd2af374','Nom prenoms 3','930102037','M','2026-02-23','texte 3','texte 3','texte 3',0,0,'2026-03-05 12:58:15','2026-03-05 12:58:15',1),
(39,'219e53bd-1ae2-4114-a4b2-0283d070cd9e','Nom prenoms 4','940102037','F','2026-02-24','texte 4','texte 4','texte 4',0,0,'2026-03-05 12:58:15','2026-03-05 12:58:15',1),
(40,'fff4d14c-669a-4302-8534-b76944a0f359','Nom prenoms 5','950102037','M','2026-02-25','texte 5','texte 5','texte 5',0,0,'2026-03-05 12:58:15','2026-03-05 12:58:15',1),
(41,'6b7ba2e2-573a-4ce1-944a-caba08f4f06a','Nom prenoms 1','910102038','F','2026-02-21','texte 1','texte 1','texte 1',0,0,'2026-03-05 13:03:19','2026-03-05 13:03:19',1),
(42,'d911f219-41de-4f78-8c8f-4e6c17144539','Nom prenoms 2','920102038','M','2026-02-22','texte 2','texte 2','texte 2',0,0,'2026-03-05 13:03:19','2026-03-05 13:03:19',1),
(43,'fea9e6cc-1055-4ae8-a6d7-621504a6834f','Nom prenoms 3','930102038','M','2026-02-23','texte 3','texte 3','texte 3',0,0,'2026-03-05 13:03:19','2026-03-05 13:03:19',1),
(44,'852af9b3-4c46-4e83-8a58-8ca3f3c29ba0','Nom prenoms 4','940102038','F','2026-02-24','texte 4','texte 4','texte 4',0,0,'2026-03-05 13:03:19','2026-03-05 13:03:19',1),
(45,'84511995-d940-4269-be6a-aa31060ab337','Nom prenoms 5','950102038','M','2026-02-25','texte 5','texte 5','texte 5',0,0,'2026-03-05 13:03:19','2026-03-05 13:03:19',1),
(46,'b24d5932-3de4-4d28-acea-26ac0daedda7','Nom prenoms 2','920102039','M','2026-02-22','texte 2','texte 2','texte 2',0,0,'2026-03-05 13:03:58','2026-03-05 13:03:58',1),
(47,'3ad94a44-cc70-42ff-9c90-fd3865df98b8','Nom prenoms 4','940102039','F','2026-02-24','texte 4','texte 4','texte 4',0,0,'2026-03-05 13:03:58','2026-03-05 13:03:58',1),
(48,'028fa712-d0af-4f79-bc19-7b2c80a9a1de','Nom prenoms 5','950102039','M','2026-02-25','texte 5','texte 5','texte 5',0,0,'2026-03-05 13:03:58','2026-03-05 13:03:58',1),
(49,'19425026-2b56-4fc6-a9b1-6727261cf0ef','Nom prenoms 1','910102039','F','2026-02-21','texte 1','texte 1','texte 1',0,0,'2026-03-05 13:07:16','2026-03-05 13:07:16',1),
(50,'cf062db7-8359-448d-8fcf-728a350995c3','Nom prenoms 3','930102039','M','2026-02-23','texte 3','texte 3','texte 3',0,0,'2026-03-05 13:07:16','2026-03-05 13:07:16',1),
(51,'bc7b8352-c88b-406c-91fa-255052772ac2','OGOU Grâce','0506070809','F','2020-01-25','St Anne','Ecolière','Port-Bouêt',0,0,'2026-03-06 00:01:49','2026-03-06 00:01:49',1),
(52,'ca318c21-d62f-470a-ac0c-832cc60b3713','Nom prenoms 2','920102040','M','2026-02-22','texte 2','texte 2','texte 2',0,0,'2026-03-06 18:10:12','2026-03-06 18:10:12',1),
(53,'13585830-7f44-423e-b99c-fcaf640ff991','Nom prenoms 3','930102040','M','2026-02-23','texte 3','texte 3','texte 3',0,0,'2026-03-06 18:10:12','2026-03-06 18:10:12',1),
(54,'3ea7656a-73ad-4499-bf66-0c8da1b1be8b','Nom prenoms 4','940102040','F','2026-02-24','texte 4','texte 4','texte 4',0,0,'2026-03-06 18:10:12','2026-03-06 18:10:12',1);
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `country`
--

DROP TABLE IF EXISTS `country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `country` (
  `id` int(3) NOT NULL,
  `code` decimal(3,0) NOT NULL,
  `length` decimal(2,0) NOT NULL,
  `label` varchar(50) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `country`
--

LOCK TABLES `country` WRITE;
/*!40000 ALTER TABLE `country` DISABLE KEYS */;
INSERT INTO `country` VALUES
(1,244,9,'Angola');
/*!40000 ALTER TABLE `country` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_contact`
--

DROP TABLE IF EXISTS `group_contact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_contact` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blacklist` tinyint(4) NOT NULL DEFAULT 0,
  `contact_id` bigint(20) NOT NULL,
  `group_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_id` (`contact_id`,`group_id`),
  KEY `idx_gc_contact` (`contact_id`),
  KEY `idx_gc_blacklist` (`blacklist`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_contact`
--

LOCK TABLES `group_contact` WRITE;
/*!40000 ALTER TABLE `group_contact` DISABLE KEYS */;
INSERT INTO `group_contact` VALUES
(1,0,24,1),
(5,0,15,2),
(4,1,25,1),
(6,0,26,1),
(7,1,51,2),
(8,0,28,1),
(9,0,31,1),
(10,1,12,1),
(11,0,49,1),
(12,1,17,2),
(13,0,53,1),
(14,0,54,1),
(15,1,25,3);
/*!40000 ALTER TABLE `group_contact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `label` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `label` (`label`,`user_id`),
  KEY `idx_groups_id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` VALUES
(1,'7f1c88ef-ac46-4ca9-9b49-54b5d0872caa','Azerty','2026-02-22 16:48:20','2026-02-22 16:52:50',1),
(2,'7ed3caec-102d-49a7-a3a7-3f890bb91e21','Qwerty','2026-02-22 16:49:31','2026-02-22 16:49:31',1),
(3,'d6a92fbb-662c-4c4a-ab7a-7f77e5b94b7b','Test','2026-03-01 12:47:07','2026-03-01 12:47:07',1);
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'2014_10_12_000000_create_users_table',1),
(2,'2014_10_12_100000_create_password_reset_tokens_table',1),
(3,'2016_06_01_000001_create_oauth_auth_codes_table',1),
(4,'2016_06_01_000002_create_oauth_access_tokens_table',1),
(5,'2016_06_01_000003_create_oauth_refresh_tokens_table',1),
(6,'2016_06_01_000004_create_oauth_clients_table',1),
(7,'2016_06_01_000005_create_oauth_personal_access_clients_table',1),
(8,'2019_08_19_000000_create_failed_jobs_table',1),
(9,'2019_12_14_000001_create_personal_access_tokens_table',1),
(10,'2025_09_09_025132_create_checkotp_table',1),
(11,'2025_10_18_003321_create_prefix_table',1),
(12,'2025_10_18_004751_create_networks_table',1),
(13,'2025_10_18_012453_create_models_table',1),
(14,'2025_10_18_014226_create_towns_table',1),
(15,'2025_10_18_015223_create_groups_table',1),
(16,'2025_10_18_022438_create_contacts_table',1),
(17,'2025_10_18_024010_create_senders_table',1),
(18,'2025_10_18_033548_create_group_contact_table',1),
(19,'2025_10_18_035249_create_statistic_table',1),
(20,'2025_10_18_041528_create_bodysms_table',1),
(21,'2025_10_18_054342_create_numbersms_table',1),
(22,'2025_10_18_061555_create_bills_table',1),
(23,'2025_10_18_062922_create_account_type_table',1),
(24,'2025_10_18_063234_create_sms_type_table',1),
(25,'2026_03_13_194035_create_index_table',2),
(26,'2025_10_18_202438_create_prices_table',3);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `models`
--

DROP TABLE IF EXISTS `models`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `models` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `title` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `models`
--

LOCK TABLES `models` WRITE;
/*!40000 ALTER TABLE `models` DISABLE KEYS */;
/*!40000 ALTER TABLE `models` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `networks`
--

DROP TABLE IF EXISTS `networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `networks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(20) NOT NULL,
  `country_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `networks`
--

LOCK TABLES `networks` WRITE;
/*!40000 ALTER TABLE `networks` DISABLE KEYS */;
INSERT INTO `networks` VALUES
(1,'Movicel',1),
(2,'Unitel',1),
(3,'Africel',1);
/*!40000 ALTER TABLE `networks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `numbersms`
--

DROP TABLE IF EXISTS `numbersms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `numbersms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `number` varchar(20) NOT NULL,
  `volume` decimal(2,0) NOT NULL,
  `sending_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `bodysms_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `numbersms`
--

LOCK TABLES `numbersms` WRITE;
/*!40000 ALTER TABLE `numbersms` DISABLE KEYS */;
INSERT INTO `numbersms` VALUES
(1,'dcfc394f-50c2-4713-a33d-df0e852af825','926400467',2,NULL,0,1);
/*!40000 ALTER TABLE `numbersms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_access_tokens`
--

DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_access_tokens`
--

LOCK TABLES `oauth_access_tokens` WRITE;
/*!40000 ALTER TABLE `oauth_access_tokens` DISABLE KEYS */;
INSERT INTO `oauth_access_tokens` VALUES
('dafa1f5231e16f1cac86625924dfb3dbf08ef76dc888a698c76fae1d45ac48431b2c87eaa9f6cd38',1,1,'MyApp','[]',0,'2025-10-25 17:10:22','2025-10-25 17:10:22','2026-10-25 17:10:22'),
('81a7d35483b05755a4a590aeb0264deb4ad04a71b774b67b06e544119fea1cdffb72b202c322d210',1,1,'MyApp','[]',0,'2025-10-25 17:37:41','2025-10-25 17:37:41','2026-10-25 17:37:41'),
('e4f2fd6123454a79ef3a401ef50b6f5ac145d41d611aa2eb24176e3318912badd9811f048a0c9404',1,1,'MyApp','[]',0,'2025-10-28 16:58:08','2025-10-28 16:58:08','2026-10-28 16:58:08'),
('4b2de32859fd48c9e327c9bf6ec23a47dcd951ff541a5aff06be1b69a75ee98900024f7dc5913187',1,1,'MyApp','[]',0,'2026-02-15 16:33:34','2026-02-15 16:33:34','2027-02-15 16:33:34'),
('c8b9d35cb8d948d848f33da43ada77b668d4ee839c8cd14c65c09cb5c22d18abc781cfb36a348437',1,1,'MyApp','[]',0,'2026-02-15 17:34:01','2026-02-15 17:34:01','2027-02-15 17:34:01'),
('809326e430f58b273fab21f0065b1b67e13898f216123b10240bd09a8943f61a7d072c1781a55232',3,1,'MyApp','[]',0,'2026-02-15 17:36:35','2026-02-15 17:36:35','2027-02-15 17:36:35'),
('a6731d57fb1ad5d8f429907000f641a6f64df6a678951674daea50af2d13a74dc639c6a9f51ade23',3,1,'MyApp','[]',0,'2026-02-15 17:47:47','2026-02-15 17:47:47','2027-02-15 17:47:47'),
('a78c1b8cabb725c6a2ebdd087cb5c3ceb100004b77ab444541e2b74bcc7305d922b852a5e9b3bbc6',3,1,'MyApp','[]',0,'2026-02-15 18:00:07','2026-02-15 18:00:07','2027-02-15 18:00:07'),
('2f149846e89c39774e22eba7b3a137d1c35e8e81714218116b7c5ad32517f77f93d7dea7fd5f9c3e',3,1,'MyApp','[]',0,'2026-02-15 18:06:16','2026-02-15 18:06:16','2027-02-15 18:06:16'),
('df15767000995e0b78e6dcaeb70632cfbc821e059745c6b3ae83f6b8c60bdd77bde8dcb4d70dd6d2',3,1,'MyApp','[]',0,'2026-02-15 18:13:02','2026-02-15 18:13:02','2027-02-15 18:13:02'),
('0caba04137a13d21a63d46b7e59254783e5f355418f18497a31419dfa36bf1aff31617a1287349f4',3,1,'MyApp','[]',0,'2026-02-15 18:32:21','2026-02-15 18:32:21','2027-02-15 18:32:21'),
('bdaa1d0334c494fa35cb3ecd51cb92939da8ba9d11c09cffe4519597d04ba93fcdcdeb34f1e15e6e',3,1,'MyApp','[]',0,'2026-02-15 18:37:42','2026-02-15 18:37:42','2027-02-15 18:37:42'),
('16324fe1cb48a37bd6d6a8272e046d0f23d6c5526c6090a6b960bab61b7f3ab794ab7d9d9f50d978',3,1,'MyApp','[]',0,'2026-02-15 18:40:36','2026-02-15 18:40:36','2027-02-15 18:40:36'),
('6003c3231483d734db8b0134518258bfcfdb78eeb2d95a2e29f08d7842a6745642fa86eae41914f8',3,1,'MyApp','[]',0,'2026-02-15 18:41:42','2026-02-15 18:41:42','2027-02-15 18:41:42'),
('3594837ffcad60477112fa9fead2988083c3d226d009862dca42ad78008fd5ce07373c94e34ee471',3,1,'MyApp','[]',0,'2026-02-15 18:42:20','2026-02-15 18:42:20','2027-02-15 18:42:20'),
('0e8c956434f3c78759cb3516db8c6274b6d74cfc768a21c3c2eee30d8b9498de2c3c8637db2d180f',3,1,'MyApp','[]',0,'2026-02-15 18:42:51','2026-02-15 18:42:51','2027-02-15 18:42:51'),
('9d9d18330bed026fbc306f117145b9375360f1856aa9c8f1e20664ebb172a875f25175b3c6056b47',3,1,'MyApp','[]',0,'2026-02-15 18:43:25','2026-02-15 18:43:25','2027-02-15 18:43:25'),
('66519eeda188eb1fe4be49012319e5763e899a04fd47711073ca35fde6740a4c52c6723ef0e518e7',3,1,'MyApp','[]',0,'2026-02-15 18:44:01','2026-02-15 18:44:01','2027-02-15 18:44:01'),
('2af5b2e28893144bf4c2c7a85c64bfbc379206170d5d85cb5a8ace00b007dd97c6a3425c508358c4',1,1,'MyApp','[]',0,'2026-02-15 18:44:04','2026-02-15 18:44:04','2027-02-15 18:44:04'),
('ca054dd0b456a775c5a487191842ba6b4e287ba2b519935fb8f7ff1258d9d16782f4e21023e06729',3,1,'MyApp','[]',0,'2026-02-15 18:44:21','2026-02-15 18:44:21','2027-02-15 18:44:21'),
('c2dd4e89334fdfd7341f76628cdc7f41a6228d017b902650c3c120658843c305e353201c96e28431',1,1,'MyApp','[]',0,'2026-02-15 18:59:08','2026-02-15 18:59:08','2027-02-15 18:59:08'),
('b51d09ba617817d9d4b6fc7562161bb5cf09fc2b32c2ece8565d3cb57afd17657ad94ea36f09af90',3,1,'MyApp','[]',0,'2026-02-15 21:34:10','2026-02-15 21:34:10','2027-02-15 21:34:10'),
('5f401a13257bd1853cd819e5aaf75fb8ceac1ec18f2716849f36aa1f438267289ec1d9ccc020292a',3,1,'MyApp','[]',0,'2026-02-18 10:40:19','2026-02-18 10:40:19','2027-02-18 10:40:19'),
('1dc91c55b127ffa8caae90f4757eccfa1207de3d9671e3e388dbabe444c4d892cd77bb41aa69f989',1,1,'MyApp','[]',0,'2026-02-18 12:19:35','2026-02-18 12:19:35','2027-02-18 12:19:35'),
('f4c72e9e6723fc82183aaad6e4056d8a3360817626e915065bdd582e24be40b596816975f8eb8ede',1,1,'MyApp','[]',0,'2026-02-18 13:31:07','2026-02-18 13:31:07','2027-02-18 13:31:07'),
('75da674279e8c35a60064fff8803d2423b8b75cd6c1be8b7a943624ef191d6e97f805bf5eed1bd85',1,1,'MyApp','[]',0,'2026-02-19 21:09:08','2026-02-19 21:09:10','2027-02-19 21:09:08'),
('ce7b538e21b57e4edba30b54402d3edbe1d32363b42338ddb47ad62e3b3c892faa90ede4b61ec3a0',1,1,'MyApp','[]',1,'2026-02-22 11:39:04','2026-02-22 17:06:34','2027-02-22 11:39:04'),
('7bc8085c739724a5bcbee70237bf6df3c96b3b54bfcc69505b2cf2062e39dc09fcf3980257d7060c',1,1,'MyApp','[]',0,'2026-02-22 20:11:38','2026-02-22 20:11:38','2027-02-22 20:11:38'),
('8b76583cae6f655b64270caa727a4be0bf4a490fcd2eb70d11277be135f63cfb654decf4be347dbe',1,1,'MyApp','[]',0,'2026-02-23 09:11:50','2026-02-23 09:11:50','2027-02-23 09:11:50'),
('097c809f7d351ac340668ccfe139150f65f24ef423afd7991f77fc91f97da42f4bea285916ab2ec0',1,1,'MyApp','[]',0,'2026-02-23 19:36:57','2026-02-23 19:36:58','2027-02-23 19:36:57'),
('78cf46337a938d164821426bf99d2ee5b8163383ffc3bbf35c23ab8c4888b5164ff61b1a9cb1d4e5',2,1,'MyApp','[]',0,'2026-02-23 20:09:05','2026-02-23 20:09:05','2027-02-23 20:09:05'),
('ae00485c543a59ac91bbf705180bedb9194552850891d00eac232111ab9d68c98a0ced9353c5aab3',1,1,'MyApp','[]',0,'2026-02-23 20:13:45','2026-02-23 20:13:45','2027-02-23 20:13:45'),
('f6fbcef38eae1e8ce02d7bd412b5026ecc2caf77b802c458d9724dc34f5f6f49459736d9f8cec8ce',1,1,'MyApp','[]',0,'2026-02-23 21:20:52','2026-02-23 21:20:52','2027-02-23 21:20:52'),
('420385eff22e845504244b921cd9ca9441c447ffb3faea03484bd7e19e427809c4c5fe1f3ce3eab8',1,1,'MyApp','[]',0,'2026-02-23 21:29:35','2026-02-23 21:29:35','2027-02-23 21:29:35'),
('41df15eda288ff20ed61e046fbb198196444d42d95c486a7c661b3a8b9ca7ab8e7c8a1224c5017ad',1,1,'MyApp','[]',0,'2026-02-25 15:17:41','2026-02-25 15:17:43','2027-02-25 15:17:41'),
('e0bdd33b7db5d10ae318f65af618a5e9c3618972a0041c8ee96f5e74041f9d65e2a17374dee28072',1,1,'MyApp','[]',0,'2026-02-25 17:09:49','2026-02-25 17:09:50','2027-02-25 17:09:49'),
('9cd69a5e6a43b289cc743f15d72b49ca2511f8944a7b77edfef8ef6601481c276f365b19f1899d23',1,1,'MyApp','[]',0,'2026-02-26 16:42:00','2026-02-26 16:42:02','2027-02-26 16:42:00'),
('66debf3c941b348cbccaa3d523ba73a7831ab331973b4c9e3d29ec544673d98205b744180911e80d',1,1,'MyApp','[]',0,'2026-02-27 16:32:44','2026-02-27 16:32:44','2027-02-27 16:32:44'),
('ad07265f282b46ab18beb9a0b4f501610837b9da782315a1324573cd021f3101b0240ba1a77c0081',1,1,'MyApp','[]',0,'2026-03-01 06:47:10','2026-03-01 06:47:12','2027-03-01 06:47:10'),
('b8440c6a2c70a2031ada92597c57801c94637118b0b77e506ffcce776d682d367fd951f82e465858',1,1,'MyApp','[]',0,'2026-03-01 12:00:54','2026-03-01 12:00:54','2027-03-01 12:00:54'),
('3524442a67c4e21f275ee39250f56132f459909675972904efec0b396ed73bbd035758f6c17828a4',1,1,'MyApp','[]',0,'2026-03-01 12:17:04','2026-03-01 12:17:04','2027-03-01 12:17:04'),
('7ac38f8e82febf6e9b3f5e885ca0225abb479d7d465c3255466f25251068b46c625d8781e532d9c7',1,1,'MyApp','[]',0,'2026-03-01 12:23:42','2026-03-01 12:23:42','2027-03-01 12:23:42'),
('cb5dfb198407fa07a1c27bbb00ab5b4badf187c41cd0c8cb734829c6b0667fe9e682f31c37b6a101',1,1,'MyApp','[]',0,'2026-03-01 12:38:37','2026-03-01 12:38:37','2027-03-01 12:38:37'),
('0c9b75f9cc2729e3c887cc0a6eff423758f86acd847b262ac5a04791251f1672c9dd8e9b6acbe24a',1,1,'MyApp','[]',0,'2026-03-01 20:40:04','2026-03-01 20:40:05','2027-03-01 20:40:04'),
('dde187060f5efab40085a377e03dff065ed6098e278b53e22f77634d92ecb4a8ac1385e260071058',1,1,'MyApp','[]',0,'2026-03-01 20:45:40','2026-03-01 20:45:40','2027-03-01 20:45:40'),
('6da36782a7296bcdaea4345bd681b40b8984ae975445e83e14c466c4ee5df165040b971b709f895e',1,1,'MyApp','[]',0,'2026-03-01 20:47:56','2026-03-01 20:47:56','2027-03-01 20:47:56'),
('9c28d06557afbbcc044840482bda164a51679b8f6539f585fa6bc61af17e0af902ad616144c5f0a1',1,1,'MyApp','[]',0,'2026-03-02 02:32:45','2026-03-02 02:32:45','2027-03-02 02:32:45'),
('d038993a95cee4b3cfce3bcd5f7c902ba0d93acf42ef9d10255425f32946a4d5c5f6f246dd5455f5',1,1,'MyApp','[]',0,'2026-03-02 13:34:41','2026-03-02 13:34:41','2027-03-02 13:34:41'),
('851da92e3e7b9a79cf7ec19ea0e2eef1a1832893b47ac118250aed14c5087a5250851fb4c8e9dfc9',1,1,'MyApp','[]',0,'2026-03-02 13:35:24','2026-03-02 13:35:24','2027-03-02 13:35:24'),
('180726ba3286eb70592d43ad0a6f5c7dcc85436ee742c6356748f95b75e6f3eede16059495e9d49a',1,1,'MyApp','[]',0,'2026-03-03 10:20:00','2026-03-03 10:20:03','2027-03-03 10:20:00'),
('8f972d646bb5ebaf3e025eabd49e3e6f7f65e5735b242ab2d84686482ec13173c2042a4ff16613dc',1,1,'MyApp','[]',0,'2026-03-03 19:22:51','2026-03-03 19:22:52','2027-03-03 19:22:51'),
('17436279b8742b086e3f0a5de2e1dda3e4ce7ab83989aa98c03f05d689bf841bb5ab44f6fb228f58',1,1,'MyApp','[]',0,'2026-03-03 19:23:02','2026-03-03 19:23:02','2027-03-03 19:23:02'),
('ca24e04c4270cf029664a17603820dc77ef9d78070b46895c9149c71e82ed824036ab22d1a3bff3a',1,1,'MyApp','[]',0,'2026-03-05 12:50:17','2026-03-05 12:50:19','2027-03-05 12:50:17'),
('3ed3b4be54c216621143279c81a710c9a11f535de05fb23d54edc3c03a27ac7be243e3e7603d9345',1,1,'MyApp','[]',0,'2026-03-05 16:43:09','2026-03-05 16:43:09','2027-03-05 16:43:09'),
('96ef64390a935e30369f98e7bec4d778118b134a8621c7a0f74321941f3448b9dac948df8aec1bc0',1,1,'MyApp','[]',0,'2026-03-05 18:43:13','2026-03-05 18:43:13','2027-03-05 18:43:13'),
('92aafcdc902068cf4f297d10cf3443e3fa10242a64f0248c8821b044db31ecdbbb488d40458f8af3',1,1,'MyApp','[]',0,'2026-03-06 08:27:10','2026-03-06 08:27:10','2027-03-06 08:27:10'),
('b7338e271fbb6ac72842ee61a3b31526812c280450568bcf4d7088f073160a0a76d15b175164bd7e',1,1,'MyApp','[]',0,'2026-03-06 17:42:08','2026-03-06 17:42:08','2027-03-06 17:42:08'),
('0cf08e83414a00685698226678fb2f4816b37912afe76c182658f7fcd93fb7c822f5be0ac744c2de',1,1,'MyApp','[]',0,'2026-03-13 16:28:49','2026-03-13 16:28:51','2027-03-13 16:28:49'),
('4b98f61547e6012f6beeb9a34a170be501a310079358500f8e0a97e1a5163def3e93c6b6986372cb',1,1,'MyApp','[]',0,'2026-03-18 15:53:36','2026-03-18 15:53:37','2027-03-18 15:53:36'),
('26d56032cb1b4601dffb4e7241ff739934d2c82137c9ec959b2c92b0e6aca485e04d06fd5f8011e2',1,1,'MyApp','[]',0,'2026-03-20 11:00:10','2026-03-20 11:00:11','2027-03-20 11:00:10'),
('b2e523409a94eb33880222d37ac9c42f37b5d8bfdc20c90a7b85dc7caaa1cfaa71cdf3cbdb52294c',1,1,'MyApp','[]',0,'2026-03-22 00:22:54','2026-03-22 00:22:54','2027-03-22 00:22:54'),
('d8a41a2f29c318b2d2497e6f2d9bdcdefee247032a42bc40b59440bf53e589be74be96bb391037a2',1,1,'MyApp','[]',0,'2026-09-22 01:57:24','2026-09-22 01:57:25','2027-09-22 01:57:24'),
('ab5cfc40fa958f6c4158bc8a1102c66d4700fe84e4b7d2ccba59e14b3a611636a6b24bb87fe39fae',1,1,'MyApp','[]',0,'2026-10-12 23:59:00','2026-10-12 23:59:01','2027-10-12 23:59:00'),
('41547fb444b908388cae80c1da6c92ae291846a4d5f7ca290519ae27a7839a7a60eb7544da089e34',1,1,'MyApp','[]',0,'2026-10-12 23:59:37','2026-10-12 23:59:37','2027-10-12 23:59:37'),
('756107c2cce90456f904dd32946ccbe0490937990bddb193c864562c872ebf49cb9b6d8a67cd754f',1,1,'MyApp','[]',0,'2026-10-12 23:59:57','2026-10-12 23:59:57','2027-10-12 23:59:57'),
('47969781f12293ef4b4a1065cfeb836ffba16de845ec06ea60976c7f73f33e16671a0bfbb8f46a03',1,1,'MyApp','[]',0,'2026-10-13 23:25:46','2026-10-13 23:25:46','2027-10-13 23:25:46'),
('2f65d511f548c2d3419df4e794732aec3af63c325cab5fae7e336e7aa2ac03aeae15a31caaf30491',1,1,'MyApp','[]',0,'2026-10-16 00:18:51','2026-10-16 00:18:52','2027-10-16 00:18:51');
/*!40000 ALTER TABLE `oauth_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_auth_codes`
--

DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `scopes` text DEFAULT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_auth_codes_user_id_index` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_auth_codes`
--

LOCK TABLES `oauth_auth_codes` WRITE;
/*!40000 ALTER TABLE `oauth_auth_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_auth_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_clients`
--

DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `secret` varchar(100) DEFAULT NULL,
  `provider` varchar(191) DEFAULT NULL,
  `redirect` text NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_clients`
--

LOCK TABLES `oauth_clients` WRITE;
/*!40000 ALTER TABLE `oauth_clients` DISABLE KEYS */;
INSERT INTO `oauth_clients` VALUES
(1,NULL,'API SMS ANGOLA Personal Access Client','yTDF2ih0Cc2P2e2LrxpevcoteWxrM05wEZM934t6',NULL,'http://localhost',1,0,0,'2025-10-25 15:21:18','2025-10-25 15:21:18'),
(2,NULL,'API SMS ANGOLA Password Grant Client','OcD89UItLPxY4ywpSehFDfVrprjsEa8BfdXs3DYj','users','http://localhost',0,1,0,'2025-10-25 15:21:18','2025-10-25 15:21:18');
/*!40000 ALTER TABLE `oauth_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_personal_access_clients`
--

DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_personal_access_clients`
--

LOCK TABLES `oauth_personal_access_clients` WRITE;
/*!40000 ALTER TABLE `oauth_personal_access_clients` DISABLE KEYS */;
INSERT INTO `oauth_personal_access_clients` VALUES
(1,1,'2025-10-25 15:21:18','2025-10-25 15:21:18');
/*!40000 ALTER TABLE `oauth_personal_access_clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth_refresh_tokens`
--

DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) NOT NULL,
  `access_token_id` varchar(100) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth_refresh_tokens`
--

LOCK TABLES `oauth_refresh_tokens` WRITE;
/*!40000 ALTER TABLE `oauth_refresh_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth_refresh_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prefix`
--

DROP TABLE IF EXISTS `prefix`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prefix` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` char(2) NOT NULL,
  `network_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prefix`
--

LOCK TABLES `prefix` WRITE;
/*!40000 ALTER TABLE `prefix` DISABLE KEYS */;
INSERT INTO `prefix` VALUES
(1,'91',1),
(2,'99',1),
(3,'92',2),
(4,'93',2),
(5,'94',2),
(6,'95',3),
(7,'97',3);
/*!40000 ALTER TABLE `prefix` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prices`
--

DROP TABLE IF EXISTS `prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `prices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `en` varchar(50) NOT NULL,
  `pt` varchar(50) NOT NULL,
  `min` int(11) NOT NULL,
  `max` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prices`
--

LOCK TABLES `prices` WRITE;
/*!40000 ALTER TABLE `prices` DISABLE KEYS */;
INSERT INTO `prices` VALUES
(1,'STANDARD','PADRAO',500,30000,11),
(2,'BRONZE','BRONZE',30001,60000,9),
(3,'SILVER','PRATA',60001,100000,8),
(4,'GOLD','OURO',100001,500000,7),
(5,'PREMIUM','PREMIUM',500001,1000000,7);
/*!40000 ALTER TABLE `prices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `senders`
--

DROP TABLE IF EXISTS `senders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `senders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `label` varchar(15) NOT NULL,
  `bydefault` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `validated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `label` (`label`,`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `senders`
--

LOCK TABLES `senders` WRITE;
/*!40000 ALTER TABLE `senders` DISABLE KEYS */;
INSERT INTO `senders` VALUES
(2,'4993f163-b4f2-4860-baad-ce6f5fe7ca4f','Fabio225',0,1,NULL,'2026-03-18 16:12:07','2026-03-20 11:04:17',1),
(4,'1a255ad5-17e3-4795-95c9-565aa81dcd15','Dez225',0,0,NULL,'2026-03-19 04:11:40','2026-03-20 11:04:17',1),
(5,'c54360e2-f2ca-4db7-b8a1-bbbf97dab237','Fany225',0,0,NULL,'2026-03-19 12:41:05','2026-03-20 11:04:17',1);
/*!40000 ALTER TABLE `senders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_type`
--

DROP TABLE IF EXISTS `sms_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_type`
--

LOCK TABLES `sms_type` WRITE;
/*!40000 ALTER TABLE `sms_type` DISABLE KEYS */;
INSERT INTO `sms_type` VALUES
(1,'Draft'),
(2,'Standard'),
(3,'Scheduled'),
(4,'Publipostage');
/*!40000 ALTER TABLE `sms_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statistic`
--

DROP TABLE IF EXISTS `statistic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistic` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `year` year(4) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `volume` decimal(10,0) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statistic`
--

LOCK TABLES `statistic` WRITE;
/*!40000 ALTER TABLE `statistic` DISABLE KEYS */;
/*!40000 ALTER TABLE `statistic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `towns`
--

DROP TABLE IF EXISTS `towns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `towns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(20) NOT NULL,
  `country_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `towns`
--

LOCK TABLES `towns` WRITE;
/*!40000 ALTER TABLE `towns` DISABLE KEYS */;
INSERT INTO `towns` VALUES
(1,'Tchouk',1),
(2,'Luanda',1),
(3,'Tchoupy',1),
(4,'Kilamba',1),
(5,'Lobito',1),
(6,'Cabinda',1),
(7,'Ondjiva',1),
(8,'Huambo',1),
(9,'Lubango',1),
(10,'Lucapa',1),
(11,'Porto Amboim',1),
(12,'Moxico',1),
(13,'Landa',1),
(14,'Catumbela',1),
(15,'Benguela',1);
/*!40000 ALTER TABLE `towns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` char(36) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `firstname` varchar(255) NOT NULL,
  `number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `codepin` char(4) NOT NULL DEFAULT '7070',
  `company` varchar(255) DEFAULT NULL,
  `nif` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `volume_sms` decimal(10,0) NOT NULL DEFAULT 0,
  `lg` char(2) NOT NULL,
  `avatar` varchar(20) DEFAULT NULL,
  `avatar_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `password_at` timestamp NULL DEFAULT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_at` timestamp NULL DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `town_id` int(11) NOT NULL,
  `accountyp_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'70b0347f-539c-46c8-8485-2727e4b1d998','OGOU','Fabrice','0102030405','fabio225@yopmail.com','7070','NGSER','ANG-2025-05','Luanda av 50','www.ngser.com',13,'en','r6afemW0XxqYBhM.jpg','2026-03-01 20:47:40','$2y$12$aFm6e0oHfXICkb8D20fGjOqbMHt1VFZrT.Ni3qGs5xjoVmZh.fhi6','2026-03-02 13:35:07',NULL,NULL,'2026-10-16 00:18:52',1,'2025-10-25 17:05:51','2026-10-16 00:30:06',1,5),
(2,'07b17f04-8c30-4ccb-98d8-488eacc31946','OGOU','Franck','0102030406','fabrice_omfh@yahoo.fr','7070','POLICE','ANG-2025-05','Luanda av 50','www.police.ci',0,'en',NULL,NULL,'$2y$12$rshM.GfNN/iAm/7LI8/dhOadY8bpGgMVoI41ukwzrymvVpKofyDj.','2025-10-25 17:13:00',NULL,NULL,'2026-02-23 20:09:05',1,'2025-10-25 17:13:00','2026-02-23 20:09:05',5,5),
(3,'e6562733-56f0-46b0-b3ed-634c9c5673da','KONAN','Jacques-Cyrille','0707503925','jacques_cyrille@yahoo.fr','7070','','','','',0,'en',NULL,NULL,'$2y$12$6py5KTOvS0h2QFJgXR3KR.w/R/zFmlC3gnXuPPazNuk4YBhnHV2RO','2026-02-15 11:23:20',NULL,NULL,'2026-02-18 10:40:19',1,'2026-02-15 11:23:20','2026-02-18 10:40:19',12,1),
(4,'f7ead478-539e-414e-be25-625fbe0f2192','Koffi','Gregoire','0173592001','koffi.gregoire@gmail.com','7070','smsillico','nif','Luanda','smsillico.com',0,'en',NULL,NULL,'$2y$12$1/TFecqRlm2NFaCncaBGGOyjct2XLx01LsNc8DV1.ACDOZDjCRvDy','2026-02-15 12:12:41',NULL,NULL,NULL,0,'2026-02-15 12:12:42','2026-02-15 12:12:42',13,5);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'smsaov4_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-10-19 10:00:05
