-- MariaDB dump 10.19  Distrib 10.6.12-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: gestRHdb
-- ------------------------------------------------------
-- Server version	10.6.12-MariaDB-0ubuntu0.22.10.1

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
-- Table structure for table `absences`
--

DROP TABLE IF EXISTS `absences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `annee` varchar(255) NOT NULL,
  `mois` varchar(255) NOT NULL,
  `nbr_jours` int(11) NOT NULL,
  `observation` varchar(255) DEFAULT NULL,
  `fileAbsence` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `absences_user_id_foreign` (`user_id`),
  CONSTRAINT `absences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `absences`
--

LOCK TABLES `absences` WRITE;
/*!40000 ALTER TABLE `absences` DISABLE KEYS */;
/*!40000 ALTER TABLE `absences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `initiateur` varchar(255) DEFAULT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `titre` varchar(60) NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `pays` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT '0',
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `lien_site` varchar(255) DEFAULT NULL,
  `code_secret` varchar(255) DEFAULT NULL,
  `couleur` varchar(255) DEFAULT NULL,
  `note_service` varchar(255) DEFAULT NULL,
  `typeactivite_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activities_service_id_foreign` (`service_id`),
  KEY `activities_typeactivite_id_foreign` (`typeactivite_id`),
  CONSTRAINT `activities_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  CONSTRAINT `activities_typeactivite_id_foreign` FOREIGN KEY (`typeactivite_id`) REFERENCES `typeactivites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_user`
--

DROP TABLE IF EXISTS `activity_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_user` (
  `activity_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`activity_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_user`
--

LOCK TABLES `activity_user` WRITE;
/*!40000 ALTER TABLE `activity_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `administrations`
--

DROP TABLE IF EXISTS `administrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) DEFAULT NULL,
  `sigle` varchar(255) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `administrations`
--

LOCK TABLES `administrations` WRITE;
/*!40000 ALTER TABLE `administrations` DISABLE KEYS */;
INSERT INTO `administrations` VALUES (1,'Agence de Regulation des Transferts de Fonds','ARFT',NULL,NULL,NULL),(2,'Direction Générale de la Monnaie et des Relations Financières','DGMRF',NULL,NULL,NULL),(3,'Agence Nationale des Investigations Financières','ANIF',NULL,NULL,NULL);
/*!40000 ALTER TABLE `administrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `affectation_user`
--

DROP TABLE IF EXISTS `affectation_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `affectation_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `affectation_id` bigint(20) unsigned DEFAULT NULL,
  `structureable_type` varchar(255) NOT NULL,
  `structureable_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `old_numero` varchar(255) DEFAULT NULL,
  `old_date` date DEFAULT NULL,
  `old_structure` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `affectation_user_user_id_foreign` (`user_id`),
  KEY `affectation_user_affectation_id_foreign` (`affectation_id`),
  KEY `affectation_user_structureable_type_structureable_id_index` (`structureable_type`,`structureable_id`),
  CONSTRAINT `affectation_user_affectation_id_foreign` FOREIGN KEY (`affectation_id`) REFERENCES `affectations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `affectation_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affectation_user`
--

LOCK TABLES `affectation_user` WRITE;
/*!40000 ALTER TABLE `affectation_user` DISABLE KEYS */;
INSERT INTO `affectation_user` VALUES (27,101,5,'App\\Models\\Direction',1,'2024-05-22 13:25:21','2024-05-22 13:25:21','null','0000-00-00','null'),(28,44,6,'App\\Models\\Bureau',8,'2024-05-22 13:30:54','2024-05-22 13:30:54','null','0000-00-00','null'),(29,101,6,'App\\Models\\Direction',4,'2024-05-22 13:31:28','2024-05-22 13:31:28','013 ARTF/DRHL-SLTCA-SRH-BP ','2021-01-14','DIRECTION GENERALE'),(30,10,6,'App\\Models\\Direction',4,'2024-05-22 13:39:51','2024-05-22 13:39:51','null','0000-00-00','null'),(31,42,6,'App\\Models\\Bureau',3,'2024-05-22 13:40:15','2024-05-22 13:40:15','null','0000-00-00','null'),(32,83,6,'App\\Models\\Direction',4,'2024-05-22 13:40:42','2024-05-22 13:40:42','null','0000-00-00','null'),(33,76,6,'App\\Models\\Bureau',3,'2024-05-22 13:40:53','2024-05-22 13:40:53','null','0000-00-00','null'),(34,74,6,'App\\Models\\Bureau',32,'2024-05-22 13:41:19','2024-05-22 13:41:19','null','0000-00-00','null'),(35,127,6,'App\\Models\\Service',16,'2024-05-22 13:42:56','2024-05-22 13:42:56','null','0000-00-00','null'),(36,104,6,'App\\Models\\Service',16,'2024-05-22 13:43:20','2024-05-22 13:43:20','null','0000-00-00','null'),(37,109,6,'App\\Models\\Service',16,'2024-05-22 13:43:32','2024-05-22 13:43:32','null','0000-00-00','null'),(38,146,6,'App\\Models\\Service',16,'2024-05-22 13:43:57','2024-05-22 13:43:57','null','0000-00-00','null'),(39,101,6,'App\\Models\\Service',5,'2024-05-22 13:44:12','2024-05-22 13:44:12','013 ARTF/DRHL-SLTCA-SRH-BP ','2021-01-14','DIRECTION GENERALE'),(40,142,6,'App\\Models\\Bureau',33,'2024-05-22 13:44:28','2024-05-22 13:44:28','null','0000-00-00','null'),(41,121,6,'App\\Models\\Bureau',34,'2024-05-22 13:47:31','2024-05-22 13:47:31','null','0000-00-00','null'),(42,19,7,'App\\Models\\Service',2,'2024-05-22 13:56:51','2024-05-22 13:56:51','null','0000-00-00','null'),(43,29,7,'App\\Models\\Service',9,'2024-05-22 14:04:13','2024-05-22 14:04:13','null','0000-00-00','null'),(44,156,7,'App\\Models\\Service',10,'2024-05-22 14:04:27','2024-05-22 14:04:27','null','0000-00-00','null'),(45,30,7,'App\\Models\\Service',10,'2024-05-22 14:04:55','2024-05-22 14:04:55','null','0000-00-00','null'),(46,20,7,'App\\Models\\Service',3,'2024-05-22 14:05:11','2024-05-22 14:05:11','null','0000-00-00','null'),(47,24,7,'App\\Models\\Service',5,'2024-05-22 14:05:55','2024-05-22 14:05:55','null','0000-00-00','null'),(48,127,7,'App\\Models\\Service',7,'2024-05-22 14:06:16','2024-05-22 14:06:16','053/ARTF-DRHL-SLTCA-SRH-BP ','2021-03-04','SERVICE LOGISTIQUE'),(49,127,7,'App\\Models\\Service',14,'2024-05-22 14:07:06','2024-05-22 14:07:06','053/ARTF-DRHL-SLTCA-SRH-BP ','2021-03-04','SERVICE LOGISTIQUE'),(50,113,7,'App\\Models\\Service',6,'2024-05-22 14:07:31','2024-05-22 14:07:31','null','0000-00-00','null'),(51,132,7,'App\\Models\\Service',19,'2024-05-22 14:07:48','2024-05-22 14:07:48','null','0000-00-00','null'),(52,152,7,'App\\Models\\Service',8,'2024-05-22 14:08:13','2024-05-22 14:08:13','null','0000-00-00','null'),(53,58,7,'App\\Models\\Service',18,'2024-05-22 14:08:24','2024-05-22 14:08:24','null','0000-00-00','null'),(54,107,7,'App\\Models\\Service',12,'2024-05-22 14:08:48','2024-05-22 14:08:48','null','0000-00-00','null'),(55,32,7,'App\\Models\\Service',13,'2024-05-22 14:09:08','2024-05-22 14:09:08','null','0000-00-00','null'),(56,33,7,'App\\Models\\Service',22,'2024-05-22 14:10:30','2024-05-22 14:10:30','null','0000-00-00','null'),(57,59,7,'App\\Models\\Service',20,'2024-05-22 14:25:34','2024-05-22 14:25:34','null','0000-00-00','null');
/*!40000 ALTER TABLE `affectation_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `affectations`
--

DROP TABLE IF EXISTS `affectations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `affectations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_affectation` varchar(255) NOT NULL,
  `date_affectation` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `noteAffectation` varchar(255) DEFAULT NULL,
  `annee` year(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `affectations`
--

LOCK TABLES `affectations` WRITE;
/*!40000 ALTER TABLE `affectations` DISABLE KEYS */;
INSERT INTO `affectations` VALUES (5,'013 ARTF/DRHL-SLTCA-SRH-BP ','2021-01-14','2024-05-22 13:26:45','2024-05-22 13:26:45','documents/affectation/1716384405.pdf',2021),(6,'053/ARTF-DRHL-SLTCA-SRH-BP ','2021-03-04','2024-05-22 13:48:33','2024-05-22 13:48:33','documents/affectation/1716385713.pdf',2021),(7,'001','2021-01-05','2024-05-22 14:01:15','2024-05-22 14:01:15','documents/affectation/1716386475.pdf',2021);
/*!40000 ALTER TABLE `affectations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agentevaluations`
--

DROP TABLE IF EXISTS `agentevaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentevaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `commission_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agentevaluations_user_id_foreign` (`user_id`),
  KEY `agentevaluations_commission_id_foreign` (`commission_id`),
  CONSTRAINT `agentevaluations_commission_id_foreign` FOREIGN KEY (`commission_id`) REFERENCES `commissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `agentevaluations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agentevaluations`
--

LOCK TABLES `agentevaluations` WRITE;
/*!40000 ALTER TABLE `agentevaluations` DISABLE KEYS */;
/*!40000 ALTER TABLE `agentevaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `agentexternes`
--

DROP TABLE IF EXISTS `agentexternes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentexternes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `structure` varchar(255) DEFAULT NULL,
  `telephone1` varchar(255) NOT NULL,
  `telephone2` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agentexternes_email_unique` (`email`),
  UNIQUE KEY `agentexternes_telephone1_unique` (`telephone1`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `agentexternes`
--

LOCK TABLES `agentexternes` WRITE;
/*!40000 ALTER TABLE `agentexternes` DISABLE KEYS */;
/*!40000 ALTER TABLE `agentexternes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assiduitetravails`
--

DROP TABLE IF EXISTS `assiduitetravails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assiduitetravails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ponctualite` double(8,2) NOT NULL DEFAULT 0.00,
  `disponibilite` double(8,2) NOT NULL DEFAULT 0.00,
  `serviabilite` double(8,2) NOT NULL DEFAULT 0.00,
  `note_total` double(8,2) NOT NULL DEFAULT 0.00,
  `user_id` bigint(20) unsigned NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assiduitetravails_user_id_foreign` (`user_id`),
  KEY `assiduitetravails_evaluation_id_foreign` (`evaluation_id`),
  CONSTRAINT `assiduitetravails_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assiduitetravails_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assiduitetravails`
--

LOCK TABLES `assiduitetravails` WRITE;
/*!40000 ALTER TABLE `assiduitetravails` DISABLE KEYS */;
/*!40000 ALTER TABLE `assiduitetravails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avis`
--

DROP TABLE IF EXISTS `avis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avis` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `etat_reevaluation` int(11) NOT NULL DEFAULT 0,
  `connaissance_technique` double(8,2) DEFAULT NULL,
  `capacite_anticiper` double(8,2) DEFAULT NULL,
  `autonomie_responsabilite` double(8,2) DEFAULT NULL,
  `capacite_deleguer` double(8,2) DEFAULT NULL,
  `qualite_redactionnelle` double(8,2) DEFAULT NULL,
  `prise_initiative` double(8,2) DEFAULT NULL,
  `fiabilite_qualite` double(8,2) DEFAULT NULL,
  `respect_delais` double(8,2) DEFAULT NULL,
  `rigueur_respect` double(8,2) DEFAULT NULL,
  `capacite_partager` double(8,2) DEFAULT NULL,
  `curiosite_professionnele` double(8,2) DEFAULT NULL,
  `capacite_identifier` double(8,2) DEFAULT NULL,
  `ponctualite` double(8,2) DEFAULT NULL,
  `disponibilite` double(8,2) DEFAULT NULL,
  `serviabilite` double(8,2) DEFAULT NULL,
  `capacite_animer` double(8,2) DEFAULT NULL,
  `adaptabilite` double(8,2) DEFAULT NULL,
  `communication` double(8,2) DEFAULT NULL,
  `rapport_hierarchie` double(8,2) DEFAULT NULL,
  `rapport_collegue` double(8,2) DEFAULT NULL,
  `qualite_accueil` double(8,2) DEFAULT NULL,
  `faculte_ecoute` double(8,2) DEFAULT NULL,
  `capacite_equipe` double(8,2) DEFAULT NULL,
  `respect_vestimentaire` double(8,2) DEFAULT NULL,
  `note_total` double(8,2) DEFAULT NULL,
  `auteur_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `avis_evaluation_id_foreign` (`evaluation_id`),
  KEY `avis_auteur_id_foreign` (`auteur_id`),
  CONSTRAINT `avis_auteur_id_foreign` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`),
  CONSTRAINT `avis_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avis`
--

LOCK TABLES `avis` WRITE;
/*!40000 ALTER TABLE `avis` DISABLE KEYS */;
/*!40000 ALTER TABLE `avis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `avispresidents`
--

DROP TABLE IF EXISTS `avispresidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `avispresidents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `auteur_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `avispresidents_evaluation_id_foreign` (`evaluation_id`),
  KEY `avispresidents_auteur_id_foreign` (`auteur_id`),
  CONSTRAINT `avispresidents_auteur_id_foreign` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`),
  CONSTRAINT `avispresidents_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `avispresidents`
--

LOCK TABLES `avispresidents` WRITE;
/*!40000 ALTER TABLE `avispresidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `avispresidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bureaus`
--

DROP TABLE IF EXISTS `bureaus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bureaus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `sigle` varchar(255) NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bureaus_service_id_foreign` (`service_id`),
  CONSTRAINT `bureaus_service_id_foreign` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bureaus`
--

LOCK TABLES `bureaus` WRITE;
/*!40000 ALTER TABLE `bureaus` DISABLE KEYS */;
INSERT INTO `bureaus` VALUES (1,'BUREAU EXPLOITATION','B.EXP',1,NULL,NULL,NULL),(2,'BUREAU SYSTEME ET RÉSEAUX','B.R',1,NULL,NULL,NULL),(3,'BUREAU FORMATION','B.F',2,NULL,NULL,NULL),(4,'BUREAU DE LA RECETTE','B.RCT',3,NULL,NULL,NULL),(5,'BUREAU DE LA DEPENSE','B.DEP',3,NULL,NULL,NULL),(6,'BUREAU DES ETUDES DE POLITIQUE BUDGETAIRES ET FINANCIERES','B.E.P.B.F',3,NULL,NULL,NULL),(7,'BUREAU ETUDE ET PLANIFICATION','B.PL',5,NULL,NULL,NULL),(8,'BUREAU PERSONNEL','B.P',2,NULL,NULL,NULL),(9,'BUREAU RECEPTION ET COURRIER','B.R.C',4,NULL,NULL,NULL),(10,'BUREAU DES OPERATIONS DE MOBILE MONEY ET ASSIMILES','B.O.M.M.A',8,NULL,NULL,NULL),(11,'BUREAU ETABLISSEMENT CRÉDIT','B.ETS.C',8,NULL,NULL,NULL),(12,'BUREAU RECOUVREMENT','B.REC',13,NULL,NULL,NULL),(13,'BUREAU CONTRÔLE ET DÉPENSE','B.C.DEP.',13,NULL,NULL,NULL),(14,'BUREAU DÉVELOPPEMENT','B.D.B.D.',1,NULL,NULL,NULL),(15,'BUREAU DES OPERATIONS AUPRES DE LA DGB','B.O.DGB',20,NULL,NULL,NULL),(16,'BUREAU  DES OPERATIONS AUPRES DE LA DGT','B.O.DGT',20,NULL,NULL,NULL),(17,'BUREAU MAINTENANCE','B.MAINT',1,NULL,NULL,NULL),(18,'BUREAU CONTROLE ET ENQUETES','B.C.E',7,NULL,NULL,NULL),(19,'BUREAU EVALUATIONS ET ANALYSES','B.E.A',7,NULL,NULL,NULL),(20,'BUREAU DE L\'INTELLIGENCE ECONOMIQUE','B.I.E',7,NULL,NULL,NULL),(21,'BUREAU DE COLLECTE DE DONNEES','B.C.D',17,NULL,NULL,NULL),(22,'BUREAU PREVISION ET BASES DE DONNEES','B.P.B.D',17,NULL,NULL,NULL),(23,'BUREAU DES COMPTES EXTERIEURS','B.C.E',17,NULL,NULL,NULL),(24,'BUREAU DES ETUDES GENERALES','B.E.G',6,NULL,NULL,NULL),(25,'BUREAU DES RAPPORT D\'ACTIVITES','B.R.A',6,NULL,NULL,NULL),(26,'BUREAU FINANCES EXTERIEURS','B.F.E',19,NULL,NULL,NULL),(27,'BUREAU CONTROLE DE CONFORMITE','B.C.C',19,NULL,NULL,NULL),(28,'BUREAU DES PRETS, DES EMPRUNTS ET DES TITRES','B.P.E.T',19,NULL,NULL,NULL),(29,'BUREAU DES OPERATEURS DE TRANSFERTS CLASSIQUES','B.R.A',6,NULL,NULL,NULL),(30,'BUREAU COMMUNICATION INTERNE','B.C.I.',14,NULL,NULL,NULL),(31,'BUREAU COMMUNICATION EXTERNE','B.C.E.',14,NULL,NULL,NULL),(32,'BUREAU SOLDE','B.S.',2,NULL,NULL,NULL),(33,'BUREAU RELATION SOCIALE','B.R.S',2,NULL,'2024-05-22 13:43:42','2024-05-22 13:43:42'),(34,'BUREAU MATERIEL DE BUREAU ','BMB',16,NULL,'2024-05-22 13:46:44','2024-05-22 13:46:44');
/*!40000 ALTER TABLE `bureaus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendars`
--

DROP TABLE IF EXISTS `calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendars`
--

LOCK TABLES `calendars` WRITE;
/*!40000 ALTER TABLE `calendars` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `valeur` char(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'A',NULL,NULL),(2,'B',NULL,NULL),(3,'C',NULL,NULL),(4,'D',NULL,NULL),(5,'E',NULL,NULL),(6,'F',NULL,NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `choixmotifdemandes`
--

DROP TABLE IF EXISTS `choixmotifdemandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `choixmotifdemandes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `motifdemande_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `choixmotifdemandes_motifdemande_id_foreign` (`motifdemande_id`),
  CONSTRAINT `choixmotifdemandes_motifdemande_id_foreign` FOREIGN KEY (`motifdemande_id`) REFERENCES `motifdemandes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `choixmotifdemandes`
--

LOCK TABLES `choixmotifdemandes` WRITE;
/*!40000 ALTER TABLE `choixmotifdemandes` DISABLE KEYS */;
INSERT INTO `choixmotifdemandes` VALUES (1,'Agent',1,NULL,NULL),(2,'Frere/soeur',1,NULL,NULL),(3,'Descendant',1,NULL,NULL),(4,'Conjointe',2,NULL,NULL),(5,'Agent',3,NULL,NULL),(6,'Enfants',3,NULL,NULL),(7,'Demenagement',4,NULL,NULL),(8,'Agent',5,NULL,NULL),(9,'Conjoint',5,NULL,NULL),(10,'Ascendant',5,NULL,NULL),(11,'Enfants',5,NULL,NULL),(12,'Conjoint (e)',6,NULL,NULL),(13,'Ascendant',6,NULL,NULL),(14,'Descendant',6,NULL,NULL),(15,'Parents par alliance',6,NULL,NULL),(16,'Retrait de deuil',7,NULL,NULL),(17,'Construction pierre tombale',7,NULL,NULL);
/*!40000 ALTER TABLE `choixmotifdemandes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `grade` varchar(255) NOT NULL,
  `ecart` int(11) NOT NULL,
  `categorie_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classes_categorie_id_foreign` (`categorie_id`),
  CONSTRAINT `classes_categorie_id_foreign` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'CLASSE I','Personnel de service',45,6,NULL,NULL),(2,'CLASSE II','Personnel de service spécialisé',50,6,NULL,NULL),(3,'CLASSE III','Commis',55,5,NULL,NULL),(4,'CLASSE IV','Commis Principal',60,4,NULL,NULL),(5,'CLASSE V','Controleur',75,3,NULL,NULL),(6,'CLASSE VI','Controleur Principal',90,2,NULL,NULL),(7,'CLASSE VII','Vérificateur de change',105,1,NULL,NULL),(8,'CLASSE VIII','Inspecteur de change',120,1,NULL,NULL),(9,'CLASSE IX','Inspecteur Principal de change',145,1,NULL,NULL),(10,'CLASSE X','Hors Classe',170,1,NULL,NULL);
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commissionagents`
--

DROP TABLE IF EXISTS `commissionagents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commissionagents` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_avancement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `matricule` varchar(255) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `sexe` char(255) NOT NULL,
  `fonction_id` bigint(20) unsigned NOT NULL,
  `structureable_type` varchar(255) NOT NULL,
  `structureable_id` bigint(20) unsigned NOT NULL,
  `etat` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commissionagents_session_avancement_id_foreign` (`session_avancement_id`),
  KEY `commissionagents_user_id_foreign` (`user_id`),
  KEY `commissionagents_fonction_id_foreign` (`fonction_id`),
  KEY `commissionagents_structureable_type_structureable_id_index` (`structureable_type`,`structureable_id`),
  CONSTRAINT `commissionagents_fonction_id_foreign` FOREIGN KEY (`fonction_id`) REFERENCES `fonctions` (`id`),
  CONSTRAINT `commissionagents_session_avancement_id_foreign` FOREIGN KEY (`session_avancement_id`) REFERENCES `session_avancements` (`id`),
  CONSTRAINT `commissionagents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commissionagents`
--

LOCK TABLES `commissionagents` WRITE;
/*!40000 ALTER TABLE `commissionagents` DISABLE KEYS */;
/*!40000 ALTER TABLE `commissionagents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commissionavancements`
--

DROP TABLE IF EXISTS `commissionavancements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commissionavancements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_avancement_id` bigint(20) unsigned NOT NULL,
  `dateDebut` datetime DEFAULT NULL,
  `dateFin` datetime DEFAULT NULL,
  `statut` int(11) NOT NULL DEFAULT 0,
  `moyene_retenue_a` int(11) DEFAULT NULL,
  `moyene_retenue_b` int(11) DEFAULT NULL,
  `moyene_retenue_c` int(11) DEFAULT NULL,
  `president_id` bigint(20) unsigned NOT NULL,
  `fileNoteService` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commissionavancements_session_avancement_id_foreign` (`session_avancement_id`),
  KEY `commissionavancements_president_id_foreign` (`president_id`),
  CONSTRAINT `commissionavancements_president_id_foreign` FOREIGN KEY (`president_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commissionavancements_session_avancement_id_foreign` FOREIGN KEY (`session_avancement_id`) REFERENCES `session_avancements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commissionavancements`
--

LOCK TABLES `commissionavancements` WRITE;
/*!40000 ALTER TABLE `commissionavancements` DISABLE KEYS */;
/*!40000 ALTER TABLE `commissionavancements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commissions`
--

DROP TABLE IF EXISTS `commissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_avancement_id` bigint(20) unsigned NOT NULL,
  `dateDebut` datetime DEFAULT NULL,
  `dateFin` datetime DEFAULT NULL,
  `statut` int(11) NOT NULL DEFAULT 0,
  `president_id` bigint(20) unsigned NOT NULL,
  `fileNoteService` varchar(255) DEFAULT NULL,
  `fileNoteSynthese` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commissions_session_avancement_id_foreign` (`session_avancement_id`),
  KEY `commissions_president_id_foreign` (`president_id`),
  CONSTRAINT `commissions_president_id_foreign` FOREIGN KEY (`president_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `commissions_session_avancement_id_foreign` FOREIGN KEY (`session_avancement_id`) REFERENCES `session_avancements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commissions`
--

LOCK TABLES `commissions` WRITE;
/*!40000 ALTER TABLE `commissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `commissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `competenceprofessionnelles`
--

DROP TABLE IF EXISTS `competenceprofessionnelles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `competenceprofessionnelles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connaissance_technique` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_anticiper` double(8,2) NOT NULL DEFAULT 0.00,
  `autonomie_responsabilite` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_deleguer` double(8,2) NOT NULL DEFAULT 0.00,
  `qualite_redactionnelle` double(8,2) NOT NULL DEFAULT 0.00,
  `prise_initiative` double(8,2) NOT NULL DEFAULT 0.00,
  `fiabilite_qualite` double(8,2) NOT NULL DEFAULT 0.00,
  `respect_delais` double(8,2) NOT NULL DEFAULT 0.00,
  `rigueur_respect` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_partager` double(8,2) NOT NULL DEFAULT 0.00,
  `curiosite_professionnele` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_identifier` double(8,2) NOT NULL DEFAULT 0.00,
  `note_total` double(8,2) NOT NULL DEFAULT 0.00,
  `user_id` bigint(20) unsigned NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `competenceprofessionnelles_user_id_foreign` (`user_id`),
  KEY `competenceprofessionnelles_evaluation_id_foreign` (`evaluation_id`),
  CONSTRAINT `competenceprofessionnelles_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `competenceprofessionnelles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `competenceprofessionnelles`
--

LOCK TABLES `competenceprofessionnelles` WRITE;
/*!40000 ALTER TABLE `competenceprofessionnelles` DISABLE KEYS */;
/*!40000 ALTER TABLE `competenceprofessionnelles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conge_annuels`
--

DROP TABLE IF EXISTS `conge_annuels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conge_annuels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nbr_jour_ouvrable` int(11) DEFAULT NULL,
  `nbr_jour_supplementaire` int(11) DEFAULT NULL,
  `date_reprise` date NOT NULL,
  `etat` char(255) NOT NULL DEFAULT '0',
  `user_id` bigint(20) unsigned NOT NULL,
  `valide_user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conge_annuels_user_id_foreign` (`user_id`),
  KEY `conge_annuels_valide_user_id_foreign` (`valide_user_id`),
  CONSTRAINT `conge_annuels_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conge_annuels_valide_user_id_foreign` FOREIGN KEY (`valide_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conge_annuels`
--

LOCK TABLES `conge_annuels` WRITE;
/*!40000 ALTER TABLE `conge_annuels` DISABLE KEYS */;
/*!40000 ALTER TABLE `conge_annuels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conge_user`
--

DROP TABLE IF EXISTS `conge_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conge_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conge_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `dateDebutConge` date NOT NULL,
  `dateRetourConge` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conge_user_conge_id_foreign` (`conge_id`),
  KEY `conge_user_user_id_foreign` (`user_id`),
  CONSTRAINT `conge_user_conge_id_foreign` FOREIGN KEY (`conge_id`) REFERENCES `conges` (`id`),
  CONSTRAINT `conge_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conge_user`
--

LOCK TABLES `conge_user` WRITE;
/*!40000 ALTER TABLE `conge_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `conge_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conges`
--

DROP TABLE IF EXISTS `conges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conges` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anneeConge` year(4) NOT NULL,
  `dateSignatureConge` date NOT NULL,
  `objet` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conges`
--

LOCK TABLES `conges` WRITE;
/*!40000 ALTER TABLE `conges` DISABLE KEYS */;
/*!40000 ALTER TABLE `conges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `connaissancecomps`
--

DROP TABLE IF EXISTS `connaissancecomps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connaissancecomps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `connaissancecomps_user_id_foreign` (`user_id`),
  CONSTRAINT `connaissancecomps_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `connaissancecomps`
--

LOCK TABLES `connaissancecomps` WRITE;
/*!40000 ALTER TABLE `connaissancecomps` DISABLE KEYS */;
/*!40000 ALTER TABLE `connaissancecomps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `decisions`
--

DROP TABLE IF EXISTS `decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `decisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nbre_mois_avancement` int(11) NOT NULL DEFAULT 24,
  `libelle` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `commission_avancement_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `decisions_user_id_foreign` (`user_id`),
  KEY `decisions_commission_avancement_id_foreign` (`commission_avancement_id`),
  CONSTRAINT `decisions_commission_avancement_id_foreign` FOREIGN KEY (`commission_avancement_id`) REFERENCES `commissionavancements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `decisions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `decisions`
--

LOCK TABLES `decisions` WRITE;
/*!40000 ALTER TABLE `decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `demandeabscences`
--

DROP TABLE IF EXISTS `demandeabscences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `demandeabscences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nbr_jour_ouvrable` int(11) DEFAULT NULL,
  `date_reprise` date NOT NULL,
  `type_demande` char(255) NOT NULL,
  `etat` char(255) NOT NULL DEFAULT '0',
  `choixmotifdemande_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `typedemandeabsence_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demandeabscences_choixmotifdemande_id_foreign` (`choixmotifdemande_id`),
  KEY `demandeabscences_user_id_foreign` (`user_id`),
  KEY `demandeabscences_typedemandeabsence_id_foreign` (`typedemandeabsence_id`),
  CONSTRAINT `demandeabscences_choixmotifdemande_id_foreign` FOREIGN KEY (`choixmotifdemande_id`) REFERENCES `choixmotifdemandes` (`id`),
  CONSTRAINT `demandeabscences_typedemandeabsence_id_foreign` FOREIGN KEY (`typedemandeabsence_id`) REFERENCES `typedemandeabsences` (`id`) ON DELETE CASCADE,
  CONSTRAINT `demandeabscences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `demandeabscences`
--

LOCK TABLES `demandeabscences` WRITE;
/*!40000 ALTER TABLE `demandeabscences` DISABLE KEYS */;
/*!40000 ALTER TABLE `demandeabscences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinataire_decisions`
--

DROP TABLE IF EXISTS `destinataire_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `destinataire_decisions` (
  `decision_id` bigint(20) unsigned NOT NULL,
  `destinable_type` varchar(255) NOT NULL,
  `destinable_id` bigint(20) unsigned NOT NULL,
  `date_reception` timestamp NULL DEFAULT NULL,
  `user_reception_id` bigint(20) unsigned DEFAULT NULL,
  `date_reponse` timestamp NULL DEFAULT NULL,
  `user_reponse_id` bigint(20) unsigned DEFAULT NULL,
  KEY `destinataire_decisions_decision_id_foreign` (`decision_id`),
  KEY `destinataire_decisions_destinable_type_destinable_id_index` (`destinable_type`,`destinable_id`),
  KEY `destinataire_decisions_user_reception_id_foreign` (`user_reception_id`),
  KEY `destinataire_decisions_user_reponse_id_foreign` (`user_reponse_id`),
  CONSTRAINT `destinataire_decisions_decision_id_foreign` FOREIGN KEY (`decision_id`) REFERENCES `decisions` (`id`),
  CONSTRAINT `destinataire_decisions_user_reception_id_foreign` FOREIGN KEY (`user_reception_id`) REFERENCES `users` (`id`),
  CONSTRAINT `destinataire_decisions_user_reponse_id_foreign` FOREIGN KEY (`user_reponse_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinataire_decisions`
--

LOCK TABLES `destinataire_decisions` WRITE;
/*!40000 ALTER TABLE `destinataire_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `destinataire_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinataires`
--

DROP TABLE IF EXISTS `destinataires`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `destinataires` (
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `destinable_type` varchar(255) NOT NULL,
  `destinable_id` bigint(20) unsigned NOT NULL,
  `date_reception` timestamp NULL DEFAULT NULL,
  `user_reception_id` bigint(20) unsigned DEFAULT NULL,
  `date_reponse` timestamp NULL DEFAULT NULL,
  `user_reponse_id` bigint(20) unsigned DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  KEY `destinataires_evaluation_id_foreign` (`evaluation_id`),
  KEY `destinataires_destinable_type_destinable_id_index` (`destinable_type`,`destinable_id`),
  KEY `destinataires_user_reception_id_foreign` (`user_reception_id`),
  KEY `destinataires_user_reponse_id_foreign` (`user_reponse_id`),
  CONSTRAINT `destinataires_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`),
  CONSTRAINT `destinataires_user_reception_id_foreign` FOREIGN KEY (`user_reception_id`) REFERENCES `users` (`id`),
  CONSTRAINT `destinataires_user_reponse_id_foreign` FOREIGN KEY (`user_reponse_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinataires`
--

LOCK TABLES `destinataires` WRITE;
/*!40000 ALTER TABLE `destinataires` DISABLE KEYS */;
/*!40000 ALTER TABLE `destinataires` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinataires_autorises`
--

DROP TABLE IF EXISTS `destinataires_autorises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `destinataires_autorises` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `destination_id` bigint(20) unsigned NOT NULL,
  `destination_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `destinataires_autorises_user_id_foreign` (`user_id`),
  CONSTRAINT `destinataires_autorises_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinataires_autorises`
--

LOCK TABLES `destinataires_autorises` WRITE;
/*!40000 ALTER TABLE `destinataires_autorises` DISABLE KEYS */;
INSERT INTO `destinataires_autorises` VALUES (1,2,2,'App\\Models\\Direction'),(2,3,2,'App\\Models\\Direction'),(3,4,3,'App\\Models\\Direction'),(4,5,3,'App\\Models\\Direction'),(5,6,3,'App\\Models\\Direction'),(6,8,4,'App\\Models\\Direction'),(7,7,4,'App\\Models\\Direction'),(8,11,5,'App\\Models\\Direction'),(9,12,6,'App\\Models\\Direction'),(10,13,6,'App\\Models\\Direction'),(11,15,6,'App\\Models\\Direction'),(12,16,7,'App\\Models\\Direction'),(13,17,1,'App\\Models\\Service'),(14,18,2,'App\\Models\\Service'),(15,19,3,'App\\Models\\Service'),(16,20,4,'App\\Models\\Service'),(17,21,4,'App\\Models\\Service'),(18,22,4,'App\\Models\\Service'),(19,23,5,'App\\Models\\Service'),(20,27,20,'App\\Models\\Service'),(21,28,17,'App\\Models\\Service'),(22,29,11,'App\\Models\\Service'),(23,30,12,'App\\Models\\Service'),(24,31,13,'App\\Models\\Service'),(25,32,14,'App\\Models\\Service'),(26,33,1,'App\\Models\\Bureau'),(27,34,1,'App\\Models\\Bureau'),(28,35,2,'App\\Models\\Bureau'),(29,36,2,'App\\Models\\Bureau'),(30,37,2,'App\\Models\\Bureau'),(31,38,14,'App\\Models\\Bureau'),(32,39,14,'App\\Models\\Bureau'),(33,40,14,'App\\Models\\Bureau'),(34,41,3,'App\\Models\\Bureau'),(35,48,9,'App\\Models\\Bureau'),(36,49,9,'App\\Models\\Bureau'),(37,50,9,'App\\Models\\Bureau'),(38,51,9,'App\\Models\\Bureau'),(39,52,10,'App\\Models\\Bureau'),(40,53,29,'App\\Models\\Bureau'),(41,56,1,'App\\Models\\Bureau'),(42,57,1,'App\\Models\\Bureau'),(43,52,18,'App\\Models\\Service'),(44,59,20,'App\\Models\\Service'),(45,60,5,'App\\Models\\Bureau'),(46,61,5,'App\\Models\\Bureau'),(47,62,5,'App\\Models\\Bureau'),(48,64,5,'App\\Models\\Bureau'),(49,65,26,'App\\Models\\Bureau'),(50,66,27,'App\\Models\\Bureau'),(51,67,28,'App\\Models\\Bureau'),(52,68,21,'App\\Models\\Service'),(54,15,7,'App\\Models\\Direction'),(55,17,17,'App\\Models\\Direction'),(56,24,6,'App\\Models\\Service'),(57,59,20,'App\\Models\\Service'),(58,60,5,'App\\Models\\Bureau'),(59,61,5,'App\\Models\\Bureau'),(60,62,5,'App\\Models\\Bureau'),(61,63,6,'App\\Models\\Bureau'),(62,64,5,'App\\Models\\Bureau'),(63,65,26,'App\\Models\\Bureau'),(64,67,28,'App\\Models\\Bureau'),(65,68,21,'App\\Models\\Service'),(66,64,5,'App\\Models\\Bureau'),(67,69,2,'App\\Models\\Direction');
/*!40000 ALTER TABLE `destinataires_autorises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `destinations`
--

DROP TABLE IF EXISTS `destinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `destinations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `structure_type` varchar(255) NOT NULL,
  `structure_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `destinations_structure_type_structure_id_index` (`structure_type`,`structure_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `destinations`
--

LOCK TABLES `destinations` WRITE;
/*!40000 ALTER TABLE `destinations` DISABLE KEYS */;
/*!40000 ALTER TABLE `destinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detailsnote`
--

DROP TABLE IF EXISTS `detailsnote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detailsnote` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dateDebutNote` date DEFAULT NULL,
  `dateFinNote` date DEFAULT NULL,
  `lieuMission` varchar(255) DEFAULT NULL,
  `noteservice_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detailsnote_noteservice_id_foreign` (`noteservice_id`),
  CONSTRAINT `detailsnote_noteservice_id_foreign` FOREIGN KEY (`noteservice_id`) REFERENCES `noteservices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detailsnote`
--

LOCK TABLES `detailsnote` WRITE;
/*!40000 ALTER TABLE `detailsnote` DISABLE KEYS */;
/*!40000 ALTER TABLE `detailsnote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diplome_user`
--

DROP TABLE IF EXISTS `diplome_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diplome_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referenceDiplome` varchar(255) NOT NULL,
  `dateDiplome` date NOT NULL,
  `annee` int(11) NOT NULL,
  `filiere` varchar(255) NOT NULL,
  `option` varchar(255) DEFAULT NULL,
  `fileDiplome` varchar(255) NOT NULL,
  `diplome_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `diplome_user_diplome_id_foreign` (`diplome_id`),
  KEY `diplome_user_user_id_foreign` (`user_id`),
  CONSTRAINT `diplome_user_diplome_id_foreign` FOREIGN KEY (`diplome_id`) REFERENCES `diplomes` (`id`),
  CONSTRAINT `diplome_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diplome_user`
--

LOCK TABLES `diplome_user` WRITE;
/*!40000 ALTER TABLE `diplome_user` DISABLE KEYS */;
INSERT INTO `diplome_user` VALUES (1,'1427/86','1986-05-05',1986,'Sécrétariat',NULL,'',15,19,'2023-10-04 15:30:25','2023-10-04 15:30:25'),(2,'Master','2013-06-07',2013,'SSciences juridiques et politiques','Sciences Juridiques et politiques','',24,24,'2023-10-04 15:41:00','2023-10-16 13:57:39'),(3,'attestation de réussite n°0506107','2007-01-26',2007,'science économique et de gestion','Gestion de la politique économique (DESS)','documents/diplomes/1696434883.pdf',3,8,'2023-10-04 15:54:43','2023-10-05 13:49:32'),(4,'Attestation','2019-03-08',2019,'Droit','Etudes Judiciaires','',22,43,'2023-10-04 16:01:10','2023-10-04 16:01:10'),(5,'0676/12EST-L','2015-01-15',2015,'Génie des télécommunications et réseaux','Informatique','documents/diplomes/1696435911.pdf',31,9,'2023-10-04 16:11:51','2023-10-04 16:11:51'),(6,'Attestation de réussite n°0506107','2012-01-12',2012,'sciences et techniques de la communication','documentation','documents/diplomes/1696436624.pdf',22,44,'2023-10-04 16:23:44','2023-10-04 16:23:44'),(7,'2187.GBAC/IGDE-DG','2016-05-30',2016,'Compabilité & Gestion Financière','','documents/diplomes/1696437188.pdf',20,30,'2023-10-04 16:33:08','2023-10-04 16:33:08'),(8,'0014/2000','2005-03-08',2005,'Technique Commerciale','G3','documents/diplomes/1696437870.pdf',18,16,'2023-10-04 16:44:30','2023-10-04 16:44:30'),(9,'N°0052/EADPNR/13','0011-05-13',11,'TECHNIQUE',NULL,'documents/diplomes/1697119666.pdf',18,17,'2023-10-04 16:54:22','2023-10-12 14:07:46'),(10,'055323770','1999-11-25',1999,'SCIENCES NATURELLES','SCIENCES NATURELLES','documents/diplomes/1696492940.pdf',18,75,'2023-10-05 08:02:20','2023-10-05 08:02:20'),(11,'00modifier','2023-10-04',2023,'modifier',NULL,'documents/diplomes/1697035868.pdf',15,73,'2023-10-05 08:12:16','2023-10-11 14:51:08'),(12,'3200/2005/METP/CAB/DEC ','2005-07-26',2005,'TECHNIQUES QUANTITATIVES DE GESTION','G2','documents/diplomes/1696495468.pdf',18,74,'2023-10-05 08:44:28','2023-10-05 08:44:28'),(13,'000930/2007','2008-03-01',2008,'Secretariat','Secretariat de direction ','documents/diplomes/1696496499.pdf',20,78,'2023-10-05 09:01:39','2023-10-05 09:01:39'),(14,'11ND 66965','2014-04-27',2014,'BEPC',NULL,'documents/diplomes/1696496561.pdf',15,83,'2023-10-05 09:02:41','2023-10-05 09:02:41'),(15,'026/TM/DASE/99','1999-07-22',1999,'ADMINISTRATION','SECRETAIRE DE DIRECTION','documents/diplomes/1696498637.pdf',20,79,'2023-10-05 09:37:17','2023-10-05 09:37:17'),(16,'1453','2011-12-27',2011,'Informatique','Electronique et Maintenance Informatique','documents/diplomes/1696498817.pdf',20,56,'2023-10-05 09:40:17','2023-10-05 09:40:17'),(17,'804 FD ','2015-08-04',2015,'DROIT PRIVEE','DROIT  ','',22,101,'2023-10-05 09:44:11','2023-10-05 09:44:11'),(18,'660197913/2014','2017-09-19',2017,'Relations internationales ','Relation internationales ','documents/diplomes/1696499481.pdf',24,100,'2023-10-05 09:51:21','2023-10-05 09:51:21'),(19,'2697','2014-07-04',2014,'Agronomie',NULL,'documents/diplomes/1696499514.pdf',37,98,'2023-10-05 09:51:54','2023-10-05 09:51:54'),(20,'0734/LP','2017-09-22',2017,'Banque et Assurance','','documents/diplomes/1696500246.pdf',22,97,'2023-10-05 10:04:06','2023-10-05 10:04:06'),(21,'ATTEESTATION DE DIPLOME DU 08/10/2014','2014-10-08',2014,'ECONOMIE ET SOCIETE','DROIT ECONOMIE GESTION','documents/diplomes/1696500630.pdf',30,77,'2023-10-05 10:10:30','2023-10-05 10:10:30'),(22,'AUCUN','1993-02-02',1993,'G2','Comptabilité','',18,102,'2023-10-05 10:11:44','2023-10-05 10:11:44'),(23,'450','2012-07-31',2012,'Agro-alimentaire ','Agro-alimentaire ','documents/diplomes/1696500860.pdf',35,103,'2023-10-05 10:14:20','2023-10-05 10:14:20'),(24,'KN00300','2001-01-09',2001,'BEPC','BEPC','',15,104,'2023-10-05 10:28:41','2023-10-05 10:28:41'),(25,'112361501','2017-10-03',2017,'R5 ','ECONOMIE GESTION COOPERATIVE','',18,106,'2023-10-05 10:34:24','2023-10-05 10:34:24'),(26,'0757/88','1991-03-18',1991,'Secretariat de direction','Secretariat de direction','documents/diplomes/1696502153.pdf',32,15,'2023-10-05 10:35:53','2023-10-05 10:35:53'),(27,'122/IHEM-ST/DA/SSE ','2015-07-22',2015,'GESTION COMMERCIALE','GESTION COMMERCIALE','documents/diplomes/1696502882.pdf',20,109,'2023-10-05 10:48:02','2023-10-05 10:48:02'),(28,'T00233','1995-12-13',1995,'Science naturelle','D','documents/diplomes/1696502917.pdf',18,27,'2023-10-05 10:48:37','2023-10-05 10:48:37'),(29,'096/CGF/IGT/14','2016-06-16',2016,'Comptabilité & gestion financière ','Comptabilité & gestion financière ','documents/diplomes/1696503032.pdf',20,107,'2023-10-05 10:50:32','2023-10-05 10:50:32'),(30,'0000','2000-01-01',2000,'BEPC',NULL,'',15,110,'2023-10-05 10:57:51','2023-10-05 10:57:51'),(31,'0000','1997-06-24',1997,'Statistique','Statistique','documents/diplomes/1696503735.pdf',31,31,'2023-10-05 11:02:15','2023-10-05 15:04:35'),(32,'ISCOM/098/DP//15','2014-09-05',2014,'Sciences de Gestion','Audit & Controle de Gestion','documents/diplomes/1696503882.pdf',32,111,'2023-10-05 11:04:42','2023-10-05 11:20:58'),(33,'AUCUN','2015-01-23',2015,'AUCUN','AUCUN','',5,108,'2023-10-05 11:08:28','2023-10-05 11:08:28'),(34,'13061611','2006-02-20',2006,'Gestion de la politique économique','','documents/diplomes/1696504377.pdf',25,5,'2023-10-05 11:12:57','2023-10-05 11:12:57'),(35,'3786/10/MEPSA/CAB/DEC','2010-06-01',2010,'LETTRE','GENERALE','',18,112,'2023-10-05 11:13:12','2023-10-05 11:13:12'),(36,'123456','2018-02-14',2018,'Sciences naturelles ','D','',18,114,'2023-10-05 11:21:49','2023-10-05 11:21:49'),(37,'2314/09','2011-03-20',2011,'lettre','lettre','documents/diplomes/1696504987.pdf',18,113,'2023-10-05 11:23:07','2023-10-05 11:23:07'),(38,'RMB 0003','1996-07-15',1996,'Techniques Commerciales','G3','documents/diplomes/1696505367.pdf',18,70,'2023-10-05 11:29:27','2023-10-05 13:14:59'),(39,'AUCUN','2022-10-12',2022,'AUCUN','AUCUN','documents/diplomes/1696505545.pdf',5,115,'2023-10-05 11:32:25','2023-10-05 11:32:25'),(40,'00000','2010-01-01',2010,'0000','mecanique','',5,116,'2023-10-05 11:34:36','2023-10-05 11:34:36'),(41,'000','1111-11-11',1111,'statistique','statistique','',20,117,'2023-10-05 11:38:53','2023-10-05 11:38:53'),(42,'AUCUN','1987-05-14',1987,'AUCUN','AUCUN','',18,119,'2023-10-05 11:54:10','2023-10-05 11:54:10'),(43,'015','2015-12-01',2015,'Banque et financière','Banque et financière','',22,120,'2023-10-05 12:07:56','2023-10-05 12:07:56'),(44,'AUCUN','2005-05-25',2005,'AUCUN','AUCUN','documents/diplomes/1696508069.pdf',5,121,'2023-10-05 12:14:29','2023-10-05 12:14:29'),(45,'011','2018-10-11',2018,'Transport et Logistique','Transport et Logistique','',30,123,'2023-10-05 12:14:34','2023-10-05 12:14:34'),(46,'8861/86','1991-03-21',1991,'BEPC','BEPC','documents/diplomes/1696508105.pdf',15,122,'2023-10-05 12:15:05','2023-10-05 12:15:05'),(47,'8860/DG/DE/2016','2016-07-06',2016,'ADMINISTRATION DE ENTREPRISES','ADMINISTRATION DES ENTREPRISES','',22,42,'2023-10-05 12:25:24','2023-10-05 12:25:24'),(48,'955/DG/DE/2016','2016-08-30',2016,'MANGEMENT EN ADMINISTRATION DES ENTREPRISES','RH','documents/diplomes/1696509074.pdf',24,125,'2023-10-05 12:31:14','2023-10-05 12:31:14'),(49,'3579/DG/DSE/2010','2010-11-09',2010,'GESTION FINANCIERE','GESTION FINANCIERE','',20,127,'2023-10-05 12:43:14','2023-10-05 12:43:14'),(50,'0678/12 EST-L','2015-01-16',2015,'Geni des systèmes industriels','TELECOMMUNICATION ET RESEAUX','documents/diplomes/1696509817.pdf',31,39,'2023-10-05 12:43:37','2023-10-05 12:43:37'),(51,'141/86','1986-07-11',1986,'Sciences naturelles','Sciences Naturelles ','documents/diplomes/1696512821.pdf',18,130,'2023-10-05 13:33:41','2023-10-05 13:33:41'),(52,'N°181/DG/DSE/96','1996-12-21',1996,'Informatique','Analyse Programmeur en Informatique de Gestion','documents/diplomes/1696512848.pdf',35,132,'2023-10-05 13:34:08','2023-10-06 08:51:46'),(53,'0097-SB','1997-01-08',1997,'Secrétariat de direction','Secrétariat de direction','documents/diplomes/1696513890.pdf',20,96,'2023-10-05 13:51:30','2023-10-05 13:51:30'),(54,'2.24 /9.7371','2005-01-11',2005,'Droit privé','Droit Privé','documents/diplomes/1697195386.pdf',30,29,'2023-10-05 13:54:53','2023-10-13 11:09:46'),(55,'4780/METPRJICS','2000-12-30',2000,'Préscolaire','Préscolaire','documents/diplomes/1696514483.pdf',16,95,'2023-10-05 14:01:23','2023-10-05 14:01:23'),(56,'03885210673','2021-09-03',2021,'Sciences Techniques Quantitatives','G2','documents/diplomes/1696515844.pdf',18,94,'2023-10-05 14:24:04','2023-10-05 14:24:04'),(57,'632/2010','2010-07-20',2010,'Technologie Réseaux et communication',NULL,'documents/diplomes/1696516335.pdf',22,93,'2023-10-05 14:32:15','2023-10-05 14:32:15'),(58,'791/DG/DE/2015','2015-04-27',2015,'MAE','Mangement des Ressources Humaines','documents/diplomes/1696516951.pdf',24,92,'2023-10-05 14:42:31','2023-10-05 14:42:31'),(59,'9375666','2012-11-21',2012,'Droit','Droit Economie et Gestion','documents/diplomes/1696519290.pdf',22,26,'2023-10-05 15:21:30','2023-10-05 15:21:30'),(60,'1612/DG/DE/2022','2013-04-23',2013,'DROIT ','DROIT','documents/diplomes/1696579494.pdf',22,32,'2023-10-06 08:04:54','2023-10-06 08:04:54'),(61,'150/ISTP14/GCE008D14','2014-12-08',2014,'Gestion comptable & Financière ','Gestion comptable & Financière ','documents/diplomes/1696584340.pdf',22,33,'2023-10-06 09:25:40','2023-10-06 09:25:40'),(62,'C 00581','1995-12-13',1995,'lettres ','A4','documents/diplomes/1696585550.pdf',18,134,'2023-10-06 09:45:50','2023-10-06 09:45:50'),(63,'09117559','2015-11-16',2015,'Droit Privé','Droit des Affaires','documents/diplomes/1696586578.pdf',22,118,'2023-10-06 10:02:58','2023-10-06 10:02:58'),(64,'118','1999-09-14',1999,'Primaire',NULL,'documents/diplomes/1696587454.pdf',15,136,'2023-10-06 10:17:34','2023-10-06 10:17:34'),(65,'00869','1999-04-26',1999,'Techniques administratives ','G1','documents/diplomes/1696587490.pdf',18,12,'2023-10-06 10:18:10','2023-10-06 10:18:10'),(66,'00904','1995-12-13',1995,'Sciences Naturelles','Serie D','documents/diplomes/1696588322.pdf',18,135,'2023-10-06 10:32:02','2023-10-06 10:32:02'),(67,'003/99','2002-11-21',2002,'Gestion','Informatique de Gestion','',20,138,'2023-10-06 11:20:59','2023-10-06 11:20:59'),(68,'007/EAD/DG/DAAC/19','2019-07-26',2019,'Magagement','Managemement des ressources Humaines','documents/diplomes/1696592491.pdf',22,76,'2023-10-06 11:41:31','2023-10-06 11:41:31'),(69,'995/DG/DE/2017','2017-03-30',2017,'Management des finances','Administration des entreprises','documents/diplomes/1696597925.pdf',24,54,'2023-10-06 13:12:05','2023-10-06 13:12:05'),(70,'883/DG/DE/2015','2015-10-09',2015,'Management commercial','Administration des entreprises','documents/diplomes/1696598777.pdf',24,7,'2023-10-06 13:26:17','2023-10-06 13:26:17'),(71,'07/81/705/04/14633','2004-06-08',2004,'Technologie','Génie électrique','documents/diplomes/1696600476.pdf',19,58,'2023-10-06 13:54:36','2023-10-06 13:54:36'),(72,'562/IHEM-ISTI/DA/SSE','2009-05-25',2009,'communication d\'entreprise et multimedia','communication d\'entreprise et multimedia ','documents/diplomes/1696600630.pdf',22,141,'2023-10-06 13:57:10','2023-10-06 13:57:10'),(73,'100/MEN-CAB-DGECOB-DEC-SECEM  ','1995-01-11',1995,'ENSEIGNEMENT ','C.F.P.A.B','documents/diplomes/1696601171.pdf',18,142,'2023-10-06 14:06:11','2023-10-06 14:48:29'),(74,'05/2011','2011-10-24',2011,'ingenirie financiere  ','ingenirie financiere  ','documents/diplomes/1696601242.pdf',30,140,'2023-10-06 14:07:22','2023-10-16 13:55:13'),(75,'9562163','2017-03-07',2017,'Logistique et transport','Management','documents/diplomes/1696601451.pdf',24,53,'2023-10-06 14:10:51','2023-10-06 14:10:51'),(76,'1108/DG/DSE/2004','2004-10-13',2004,'Gestion commercial','Gestion commercial','documents/diplomes/1696602225.pdf',20,47,'2023-10-06 14:23:45','2023-10-06 14:23:45'),(77,'0675/12 EST - L','2015-01-15',2015,'Génie des Systeme Qualité Hygiene Sécurité et Environnement ','QHSE ','documents/diplomes/1696602275.pdf',31,133,'2023-10-06 14:24:35','2023-10-06 14:24:35'),(78,'pas de reference','2011-10-26',2011,'Bancassurance Finance','Management et Sciences de Gestion','documents/diplomes/1696602538.pdf',24,67,'2023-10-06 14:28:58','2023-10-06 14:28:58'),(79,'1994 - 1995','1995-06-26',1995,'Ingenieur d\'application de la statistique ','Ingenieur d\'application de la statistique ','documents/diplomes/1696604090.pdf',30,13,'2023-10-06 14:54:50','2023-10-06 14:54:50'),(80,'442/DG/DSE/2000','2000-03-20',2000,'secrétariat de direction','Secrétariat de direction','documents/diplomes/1696604701.pdf',20,4,'2023-10-06 15:05:01','2023-10-06 15:05:01'),(81,'T 34109167 G','2016-05-10',2016,'Gestion des ressources humaines ','Gestion des ressources humaines ','documents/diplomes/1696605339.pdf',22,143,'2023-10-06 15:15:39','2023-10-06 15:15:39'),(82,'1964.NBI/IGDE-DG','2015-08-04',2015,'Gestion et Développement ECONOMIQUE','Analyse et programmation','documents/diplomes/1696605875.pdf',20,61,'2023-10-06 15:24:35','2023-10-06 15:24:35'),(83,'1576/IFIM/DAAS','2013-09-21',2013,'Assurances Banques & Finances ','Sciences et techniques economiques ','documents/diplomes/1696607139.pdf',22,144,'2023-10-06 15:45:39','2023-10-06 15:45:39'),(84,'+++++++','0004-04-04',4,'BEPC','BEPC','',15,146,'2023-10-09 09:42:49','2023-10-09 09:42:49'),(85,'R00390','1998-11-28',1998,'A4',NULL,'documents/diplomes/1696845591.pdf',18,91,'2023-10-09 09:59:51','2023-10-09 09:59:51'),(86,'359/94/C.E.P.01','2011-08-09',2011,'CEPE','CEPE','',13,147,'2023-10-09 10:01:01','2023-10-09 10:01:01'),(87,'08271/QLR/2009','2009-08-05',2009,'G2','Techniques quantitatives','documents/diplomes/1696845943.pdf',18,90,'2023-10-09 10:05:43','2023-10-09 10:05:43'),(88,'KP00519','1999-07-28',1999,'BEPC','BEPC','documents/diplomes/1696848636.pdf',15,148,'2023-10-09 10:50:36','2023-10-09 10:50:36'),(89,'002948','1991-12-11',1991,'COMMUNICATION','JOURNALISTE','documents/diplomes/1696855910.pdf',22,149,'2023-10-09 12:51:50','2023-10-09 12:51:50'),(90,'0126/IFIM/DAAS','2015-12-08',2015,'tecnicien en informatique','telecommunication et reseau','',22,150,'2023-10-09 13:09:32','2023-10-09 13:09:32'),(91,'099/2011/ECES','2011-01-01',2011,'GESTION','INFORMATIQUE DE GESTION','documents/diplomes/1696858026.pdf',22,151,'2023-10-09 13:27:06','2023-10-09 13:27:06'),(92,'129-21/METPFQE-CAB-DECTP-SI','2021-03-26',2021,'Baccalauréat Technologique','G2/Techniques Quantitatives de Gestion','documents/diplomes/1696860010.pdf',18,60,'2023-10-09 14:00:10','2023-10-09 14:00:10'),(93,'Attestation de succes n°234/UMNG.DSE.SSE.ENAM','2022-05-09',2022,'Budget','diplome superieur de l\'école nationale d\'administration et de magistrature','',22,20,'2023-10-09 14:15:09','2023-10-09 14:15:09'),(94,'025952/LD/12/001','2013-06-07',2013,'DROIT PRIVE','DROIT DES AFFAIRES','documents/diplomes/1696863833.pdf',22,66,'2023-10-09 15:03:53','2023-10-09 15:03:53'),(95,'1285','2016-08-31',2016,'Réseaux et Télecommunications',NULL,'documents/diplomes/1696864966.pdf',24,145,'2023-10-09 15:22:46','2023-10-09 15:22:46'),(96,'725/12 EST-L','2015-01-16',2015,'GSI',NULL,'documents/diplomes/1696865784.pdf',31,80,'2023-10-09 15:36:24','2023-10-09 15:36:24'),(97,'0864','2017-10-10',2017,'A4',NULL,'documents/diplomes/1696866043.pdf',18,81,'2023-10-09 15:40:43','2023-10-09 15:40:43'),(98,' EOE K27-491-019','2015-07-16',2015,'Droit','','documents/diplomes/1696866917.pdf',22,84,'2023-10-09 15:55:17','2023-10-09 15:55:17'),(99,'1315/87','1988-03-24',1988,'BEPC','BEPC','documents/diplomes/1696943434.pdf',15,46,'2023-10-10 13:10:34','2023-10-10 13:10:34'),(100,'0546/EADPNR/23','2023-06-28',2023,'Comptabilité sur informatique',NULL,'documents/diplomes/1696954097.pdf',20,85,'2023-10-10 16:08:17','2023-10-10 16:08:17'),(101,'1107','2015-08-17',2015,'Finance','Techniques Bancaires','documents/diplomes/1696954670.pdf',24,86,'2023-10-10 16:17:50','2023-10-10 16:17:50'),(102,'06RK06059','2007-10-03',2007,'Série D',NULL,'documents/diplomes/1696954961.pdf',18,87,'2023-10-10 16:22:41','2023-10-10 16:22:41'),(103,'036/93-94','1994-11-24',1994,'Informatique de Gestion',NULL,'documents/diplomes/1696955166.pdf',20,88,'2023-10-10 16:26:06','2023-10-10 16:26:06'),(104,'01279/REA/2003','2003-08-06',2003,'G3','Techniques Commerciales','documents/diplomes/1696955463.pdf',18,89,'2023-10-10 16:31:03','2023-10-10 16:31:03'),(105,'000','1980-11-11',1980,'S','D','',18,152,'2023-10-11 08:21:45','2023-10-11 08:21:45'),(106,'T00233','1995-12-13',1995,'Sciences naturelles','D','documents/diplomes/1697036629.pdf',18,152,'2023-10-11 08:23:31','2023-10-11 15:03:49'),(107,'1024/05/MEPSAC/CAB/DEC','2006-11-20',2006,'A4','A4','documents/diplomes/1697118108.pdf',18,154,'2023-10-12 13:41:48','2023-10-12 13:41:48'),(108,'12DN 20862','2017-01-26',2017,'A4','A4','documents/diplomes/1697119058.pdf',18,155,'2023-10-12 13:51:57','2023-10-12 13:57:38'),(109,'UNIS 750238609/2015','2015-06-01',2015,'Droit des Activités Maritimes','Sciences Juridiques et Politique','documents/diplomes/1697196771.pdf',24,156,'2023-10-13 11:32:51','2023-10-13 11:32:51'),(110,'112/89','1989-09-19',1989,'Enseignement General',NULL,'documents/diplomes/1697203626.pdf',32,65,'2023-10-13 13:24:03','2023-10-13 13:27:06'),(111,'004576 ','1998-08-31',1998,'Sciences Economiques','Economie et Organisation de l\'entreprise','documents/diplomes/1698055097.pdf',22,153,'2023-10-17 13:47:39','2023-10-23 09:58:17'),(112,'121200823/QAA/98/MEN/CAB/DEC ','1998-10-14',1998,'DTechnique Quantitatives de Gestion','G2','documents/diplomes/1697620406.pdf',18,157,'2023-10-18 08:43:35','2023-10-18 09:13:26'),(113,'329/85','1995-10-15',1995,'BEPC','BEPC','documents/diplomes/1697621571.pdf',15,158,'2023-10-18 09:32:51','2023-10-18 09:32:51'),(114,'00 00','1000-01-01',1000,'BEPC','BEPC','',15,159,'2023-10-18 13:12:34','2023-10-18 13:12:34'),(115,'1118CGE49IAE','2011-11-18',2011,'comptabilité et gestion des entreprises','Comptabilité et gestion des entreprises','documents/diplomes/1697636765.pdf',20,160,'2023-10-18 13:38:48','2023-10-18 13:46:05'),(116,'GAT-N40-430-930','2015-11-16',2015,'ETUDES JUDICIARES','ETUDES JUDICIAIRES','documents/diplomes/1697638095.pdf',20,161,'2023-10-18 14:00:59','2023-10-18 14:08:15'),(117,'11111','1999-01-01',1999,'BEPC','BEPC','',15,162,'2023-10-20 12:13:07','2023-10-20 12:13:07'),(118,'458/IGE/2002','2002-01-03',2002,'Gestion d\'entreprise','Comptabilité ','documents/diplomes/1698058011.pdf',20,28,'2023-10-23 10:46:51','2023-10-23 10:46:51'),(119,'0002892','1017-10-17',1017,'INFORMAIQUE','RESEAU-INFORMATIQUE','',31,36,'2023-10-26 08:02:23','2023-10-26 08:02:23'),(120,'1.21/056','2002-02-18',2002,'Droit','Droit privé','documents/diplomes/1698309807.pdf',30,25,'2023-10-26 08:43:27','2023-10-26 08:43:27'),(121,'12187/DG/DE/2020','2020-06-26',2020,'informatique et réseau','Réseau','',22,38,'2023-10-31 09:36:49','2023-10-31 09:36:49'),(122,'IG 43458','1999-06-08',1999,'Develloppement ','INFORMATIQUE DE GESTION','documents/diplomes/1698746384.pdf',20,18,'2023-10-31 09:59:44','2024-05-07 09:53:17'),(123,'IG 05/567/12','2014-07-14',2014,'informatique','Administrateur de réseau','documents/diplomes/1698748009.pdf',20,163,'2023-10-31 10:21:56','2023-10-31 10:26:49'),(124,'PAA01102','1990-07-08',1990,'00000','Technique','documents/diplomes/1698748940.pdf',18,21,'2023-10-31 10:37:34','2023-10-31 10:42:20'),(125,'097/0598/AC2','2000-09-12',2000,'INFORMATIQUE','ETUDE COMMERCIALE','documents/diplomes/1698997435.pdf',20,164,'2023-11-03 07:37:55','2023-11-03 07:43:55'),(126,'MD 98089','1999-10-18',1999,'juriste','Droit privé','',30,165,'2023-11-03 10:12:22','2023-11-03 10:12:22'),(127,'LSE3/98109','1999-11-03',1999,'economie','Economie et organisation de l\'entreprise','',22,166,'2023-11-03 10:23:55','2023-11-03 10:23:55'),(128,'1620/MENSTTTJCA-CAB-AETP','1999-03-09',1999,'COMPTABILITE','FINANCE','',20,167,'2023-11-03 13:01:07','2023-11-03 13:01:07'),(129,' 125','1999-07-28',1999,'géneral','général','',15,170,'2023-11-06 07:16:28','2023-11-06 07:16:28'),(130,'0000','1999-01-01',1999,'Général','Géneral','',20,172,'2023-11-06 07:28:32','2023-11-06 07:28:32'),(131,'1245','1999-07-21',1999,'Général','Général','',15,173,'2023-11-06 07:35:08','2023-11-06 07:35:08'),(132,'LSE3/97231','1998-08-31',1998,'economie','Economie et organisation de l\'entreprise','',22,174,'2023-11-06 07:43:27','2023-11-06 07:43:27'),(133,'07845/QNC/97/MEN/CAB/DEC','1998-04-08',1998,'Général','Général','',18,175,'2023-11-06 07:51:17','2023-11-06 07:51:17'),(134,'1221/MENRSE/CAB','1994-08-26',1994,'géneral','Général','',20,176,'2023-11-06 07:59:34','2023-11-06 07:59:34'),(135,'010','1998-01-01',1998,'general','general','',22,177,'2023-11-06 08:30:24','2023-11-06 08:30:24'),(136,'MSE/97011','1999-09-22',1999,'ECONOMIE','ECONOMIE ET RECHERCHE OPERATIONNELLE','',30,178,'2023-11-06 08:38:16','2023-11-06 08:38:16'),(137,'2000','1998-07-01',1998,'général','Général','',15,180,'2023-11-06 09:00:45','2023-11-06 09:00:45'),(138,'LD3/98007','1998-10-28',1998,'droit','droit privé','',22,181,'2023-11-06 10:19:26','2023-11-06 10:19:26'),(139,'0121','1999-08-27',1999,'général','général','',15,182,'2023-11-06 10:28:09','2023-11-06 10:28:09'),(140,'LD3/96072','1996-12-05',1996,'droit','droit privé','',22,183,'2023-11-06 10:33:00','2023-11-06 10:33:00'),(141,'45','1999-07-28',1999,'général','Général','',15,184,'2023-11-06 10:39:02','2023-11-06 10:39:02'),(142,'07362/QAA/97','1998-04-05',1998,'général','Général','',18,185,'2023-11-06 10:49:22','2023-11-06 10:49:22'),(143,'5571/86','1989-01-12',1989,'général','Général','',32,186,'2023-11-06 10:55:53','2023-11-06 10:55:53'),(144,'124','1999-08-28',1999,'général','Général','',15,187,'2023-11-07 06:45:42','2023-11-07 06:45:42'),(145,'241','1999-07-11',1999,'G2','G2','',18,188,'2023-11-07 06:54:37','2023-11-07 06:54:37'),(146,'5231/81','1982-04-26',1982,'géneral','Général','',32,189,'2023-11-07 06:59:35','2023-11-07 06:59:35'),(147,'00040/UMNG/VR/DSE','1998-01-12',1998,'ECONOMIE','ECONOMIE','',33,190,'2023-11-07 07:06:18','2023-11-07 07:06:18'),(148,'ABF0710/101','2012-04-23',2012,'/','/','documents/diplomes/1716385083.pdf',4,10,'2024-05-22 13:38:03','2024-05-22 13:38:03'),(149,'001/99','1999-08-09',1999,'PROGRAMMATION ','ANALYSTE PROGRAMMEUR ','documents/diplomes/1716387681.pdf',20,59,'2024-05-22 14:21:21','2024-05-22 14:21:21');
/*!40000 ALTER TABLE `diplome_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `diplomes`
--

DROP TABLE IF EXISTS `diplomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `diplomes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `classe_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `diplomes`
--

LOCK TABLES `diplomes` WRITE;
/*!40000 ALTER TABLE `diplomes` DISABLE KEYS */;
INSERT INTO `diplomes` VALUES (1,'Gardien ou agent de sécutité','Gardien ou agent de sécutité',1,NULL,NULL),(2,'Garçon de bureau','Garçon de bureau',1,NULL,NULL),(3,'Jardinier','Jardinier',1,NULL,NULL),(4,'Planton','Planton',1,NULL,NULL),(5,'Chauffeur','Chauffeur',1,NULL,NULL),(6,'Concierge','Concierge',1,NULL,NULL),(7,'Mécanicien','Mécanicien',1,NULL,NULL),(8,'Ouvrier qualifié','Ouvirer qualifié',1,NULL,NULL),(9,'Chauffeur Mécanicien','Chauffeur Mécanicien',2,NULL,NULL),(10,'Ouvrier spécialisé','Ouvrier spécialisé',2,NULL,NULL),(11,'Technicien spécialisé','technicien spécialisé',2,NULL,NULL),(12,'Technicien de  surface','technicien de  surface',2,NULL,NULL),(13,'CEPE','CEPE',3,NULL,NULL),(14,'CAP','Certificat d\'Haptitude Primaire',4,NULL,NULL),(15,'BEPC','Brevet d\'Etude du Premier Cycle',5,NULL,NULL),(16,'BET','Brevet d\'Etude Technique',5,NULL,NULL),(17,'BEP','Brevet d\'Etude Professionel',5,NULL,NULL),(18,'BAC','Baccalauréat',6,NULL,NULL),(19,'DUT','Diplôme Universitaire Technique',7,NULL,NULL),(20,'BTS','Brevet de Technicien Supérieur',7,NULL,NULL),(21,'BENAM','BENAM',7,NULL,NULL),(22,'Licence','Licence',7,NULL,NULL),(23,'DEA','Diplôme d\'Etude Approfondie',8,NULL,NULL),(24,'MASTER','MASTER',8,NULL,NULL),(25,'DESS','Diplôme d\'Etude Supérieur Spécialisé',8,NULL,NULL),(26,'DSENAM','Diplôme Supérieur de l\'ENAM',8,NULL,NULL),(27,'MBA','MBA',8,NULL,NULL),(28,'DUTS','Diplôme Supérieur Techniaue Supérieur',8,NULL,NULL),(29,'Doctorat','Doctorat',9,NULL,NULL),(30,'Maitrise','Maitrise',8,NULL,NULL),(31,'DST','Diplôme Superieur de Technologie',7,NULL,NULL),(32,'BEMG','Brevet Etude Moyen General',5,'2023-10-05 08:18:33','2023-10-05 08:18:33'),(33,'DEUG 1','Diplôme Etude Universitaire General ',8,'2023-10-05 09:01:07','2023-10-05 09:01:07'),(34,'DEUG 2','Diplôme Etude Universitaire General 2',8,'2023-10-05 09:01:37','2023-10-05 09:01:37'),(35,'BTSE','Brevet de Technicien Supérieur En Entreprise ',3,'2023-10-05 09:18:49','2023-10-05 09:18:49'),(36,'BEMT','Brevet Edute Moyen Technique',1,'2023-10-05 09:19:55','2023-10-05 09:19:55'),(37,'BACHELOR','BACHELOR',7,'2023-10-05 09:47:44','2023-10-05 09:47:44'),(38,'Permis de conduire','Permis de conduire',1,'2023-10-05 12:19:53','2023-10-05 12:19:53');
/*!40000 ALTER TABLE `diplomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `directions`
--

DROP TABLE IF EXISTS `directions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `directions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `sigle` varchar(255) NOT NULL,
  `localite_id` bigint(20) unsigned NOT NULL,
  `administration_id` bigint(20) unsigned DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'normal',
  `user_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `directions_localite_id_foreign` (`localite_id`),
  CONSTRAINT `directions_localite_id_foreign` FOREIGN KEY (`localite_id`) REFERENCES `localites` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `directions`
--

LOCK TABLES `directions` WRITE;
/*!40000 ALTER TABLE `directions` DISABLE KEYS */;
INSERT INTO `directions` VALUES (1,'DIRECTION GENERALE','D.G',1,NULL,'principal',NULL,NULL,NULL),(2,'DIRECTION FINANCIÈRE','D.F',1,NULL,'normal',NULL,NULL,NULL),(3,'DIRECTION DE LA REGULATION','D.R',1,NULL,'normal',NULL,NULL,NULL),(4,'DIRECTION DES RESSOURCES HUMAINES ET DE LA LOGISTIQUE','D.R.H.L',1,NULL,'normal',NULL,NULL,NULL),(5,'AGENCE COMPTABLE','A.C',1,NULL,'normal',NULL,NULL,NULL),(6,'DIRECTION DE L\'INSPECTION DES STAT. ET DES ÉTUDES','D.I.S.E',1,NULL,'normal',NULL,NULL,NULL),(7,'DIRECTION DES AFF. JURIDIQUES, DES INVEST. ET DE LA COOP.','D.A.J.I.C',1,NULL,'normal',NULL,NULL,NULL);
/*!40000 ALTER TABLE `directions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `echelon_grille`
--

DROP TABLE IF EXISTS `echelon_grille`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `echelon_grille` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `echelon_id` bigint(20) unsigned NOT NULL,
  `grille_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `echelon_grille_echelon_id_foreign` (`echelon_id`),
  KEY `echelon_grille_grille_id_foreign` (`grille_id`),
  CONSTRAINT `echelon_grille_echelon_id_foreign` FOREIGN KEY (`echelon_id`) REFERENCES `echelons` (`id`),
  CONSTRAINT `echelon_grille_grille_id_foreign` FOREIGN KEY (`grille_id`) REFERENCES `grilles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `echelon_grille`
--

LOCK TABLES `echelon_grille` WRITE;
/*!40000 ALTER TABLE `echelon_grille` DISABLE KEYS */;
INSERT INTO `echelon_grille` VALUES (1,1,1,NULL,NULL),(2,1,2,NULL,NULL),(3,1,3,NULL,NULL);
/*!40000 ALTER TABLE `echelon_grille` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `echelons`
--

DROP TABLE IF EXISTS `echelons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `echelons` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `valeur` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `echelons`
--

LOCK TABLES `echelons` WRITE;
/*!40000 ALTER TABLE `echelons` DISABLE KEYS */;
INSERT INTO `echelons` VALUES (1,'Echelon 1',NULL,NULL),(2,'Echelon 2',NULL,NULL),(3,'Echelon 3',NULL,NULL),(4,'Echelon 4',NULL,NULL),(5,'Echelon 5',NULL,NULL),(6,'Echelon 6',NULL,NULL),(7,'Echelon 7',NULL,NULL),(8,'Echelon 8',NULL,NULL),(9,'Echelon 9',NULL,NULL),(10,'Echelon 10',NULL,NULL),(11,'Echelon 11',NULL,NULL),(12,'Echelon 12',NULL,NULL);
/*!40000 ALTER TABLE `echelons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enfants`
--

DROP TABLE IF EXISTS `enfants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enfants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_extrait` varchar(255) NOT NULL,
  `dateExtrait` date NOT NULL,
  `nomEnfant` varchar(255) NOT NULL,
  `prenomEnfant` varchar(255) DEFAULT NULL,
  `NomMere` varchar(255) NOT NULL,
  `nomPere` varchar(255) DEFAULT NULL,
  `sexe` char(255) NOT NULL,
  `DateNaissanceEnfant` date NOT NULL,
  `LieuNaissanceEnfant` varchar(255) NOT NULL,
  `ACharge` tinyint(1) NOT NULL DEFAULT 1,
  `acteNaissance` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `enfants_user_id_foreign` (`user_id`),
  CONSTRAINT `enfants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enfants`
--

LOCK TABLES `enfants` WRITE;
/*!40000 ALTER TABLE `enfants` DISABLE KEYS */;
/*!40000 ALTER TABLE `enfants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluationgenerales`
--

DROP TABLE IF EXISTS `evaluationgenerales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluationgenerales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `connaissance_technique` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_anticiper` double(8,2) NOT NULL DEFAULT 0.00,
  `autonomie_responsabilite` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_deleguer` double(8,2) NOT NULL DEFAULT 0.00,
  `qualite_redactionnelle` double(8,2) NOT NULL DEFAULT 0.00,
  `prise_initiative` double(8,2) NOT NULL DEFAULT 0.00,
  `fiabilite_qualite` double(8,2) NOT NULL DEFAULT 0.00,
  `respect_delais` double(8,2) NOT NULL DEFAULT 0.00,
  `rigueur_respect` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_partager` double(8,2) NOT NULL DEFAULT 0.00,
  `curiosite_professionnele` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_identifier` double(8,2) NOT NULL DEFAULT 0.00,
  `ponctualite` double(8,2) NOT NULL DEFAULT 0.00,
  `disponibilite` double(8,2) NOT NULL DEFAULT 0.00,
  `serviabilite` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_animer` double(8,2) NOT NULL DEFAULT 0.00,
  `adaptabilite` double(8,2) NOT NULL DEFAULT 0.00,
  `communication` double(8,2) NOT NULL DEFAULT 0.00,
  `rapport_hierarchie` double(8,2) NOT NULL DEFAULT 0.00,
  `rapport_collegue` double(8,2) NOT NULL DEFAULT 0.00,
  `qualite_accueil` double(8,2) NOT NULL DEFAULT 0.00,
  `faculte_ecoute` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_equipe` double(8,2) NOT NULL DEFAULT 0.00,
  `respect_vestimentaire` double(8,2) NOT NULL DEFAULT 0.00,
  `note_total` double(8,2) NOT NULL DEFAULT 0.00,
  `commission_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `evaluateur_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluationgenerales_commission_id_foreign` (`commission_id`),
  KEY `evaluationgenerales_user_id_foreign` (`user_id`),
  KEY `evaluationgenerales_evaluateur_id_foreign` (`evaluateur_id`),
  CONSTRAINT `evaluationgenerales_commission_id_foreign` FOREIGN KEY (`commission_id`) REFERENCES `commissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluationgenerales_evaluateur_id_foreign` FOREIGN KEY (`evaluateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluationgenerales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluationgenerales`
--

LOCK TABLES `evaluationgenerales` WRITE;
/*!40000 ALTER TABLE `evaluationgenerales` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluationgenerales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `evaluations`
--

DROP TABLE IF EXISTS `evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_avancement_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `evaluateur_id` bigint(20) unsigned NOT NULL,
  `notecompetencepro_id` int(11) NOT NULL DEFAULT 0,
  `noteassiduitetravail_id` int(11) NOT NULL DEFAULT 0,
  `noterelationsociale_id` int(11) NOT NULL DEFAULT 0,
  `note_globale` double(8,2) NOT NULL DEFAULT 0.00,
  `notecommission_id` int(11) NOT NULL DEFAULT 0,
  `statut` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `evaluations_session_avancement_id_foreign` (`session_avancement_id`),
  KEY `evaluations_user_id_foreign` (`user_id`),
  KEY `evaluations_evaluateur_id_foreign` (`evaluateur_id`),
  CONSTRAINT `evaluations_evaluateur_id_foreign` FOREIGN KEY (`evaluateur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_session_avancement_id_foreign` FOREIGN KEY (`session_avancement_id`) REFERENCES `session_avancements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluations`
--

LOCK TABLES `evaluations` WRITE;
/*!40000 ALTER TABLE `evaluations` DISABLE KEYS */;
/*!40000 ALTER TABLE `evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fonctions`
--

DROP TABLE IF EXISTS `fonctions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fonctions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fonctions`
--

LOCK TABLES `fonctions` WRITE;
/*!40000 ALTER TABLE `fonctions` DISABLE KEYS */;
INSERT INTO `fonctions` VALUES (1,'Agent',NULL,NULL),(2,'Chef de Bureau',NULL,NULL),(3,'Chef de Service',NULL,NULL),(4,'Directeur',NULL,NULL),(5,'Directeur Général',NULL,NULL);
/*!40000 ALTER TABLE `fonctions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grilles`
--

DROP TABLE IF EXISTS `grilles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grilles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `classe_id` bigint(20) unsigned NOT NULL,
  `echelon_id` bigint(20) unsigned NOT NULL,
  `indice` int(11) NOT NULL,
  `salaire` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grilles_classe_id_foreign` (`classe_id`),
  KEY `grilles_echelon_id_foreign` (`echelon_id`),
  CONSTRAINT `grilles_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `grilles_echelon_id_foreign` FOREIGN KEY (`echelon_id`) REFERENCES `echelons` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=122 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grilles`
--

LOCK TABLES `grilles` WRITE;
/*!40000 ALTER TABLE `grilles` DISABLE KEYS */;
INSERT INTO `grilles` VALUES (1,1,1,490,147000,NULL,NULL),(2,1,2,535,160500,NULL,NULL),(3,1,3,580,174000,NULL,NULL),(4,1,4,625,187500,NULL,NULL),(5,1,5,670,201000,NULL,NULL),(6,1,6,715,214500,NULL,NULL),(7,1,7,760,228000,NULL,NULL),(8,1,8,805,241500,NULL,NULL),(9,1,9,850,255000,NULL,NULL),(10,1,10,895,268500,NULL,NULL),(11,1,11,940,282000,NULL,NULL),(12,1,12,985,295500,NULL,NULL),(13,2,1,590,177000,NULL,NULL),(14,2,2,640,192000,NULL,NULL),(15,2,3,690,207000,NULL,NULL),(16,2,4,740,222000,NULL,NULL),(17,2,5,790,237000,NULL,NULL),(18,2,6,840,252000,NULL,NULL),(19,2,7,890,267000,NULL,NULL),(20,2,8,940,282000,NULL,NULL),(21,2,9,990,297000,NULL,NULL),(22,2,10,1040,312000,NULL,NULL),(23,2,11,1090,327000,NULL,NULL),(24,2,12,1140,342000,NULL,NULL),(25,3,1,700,210000,NULL,NULL),(26,3,2,755,226500,NULL,NULL),(27,3,3,810,243000,NULL,NULL),(28,3,4,865,259500,NULL,NULL),(29,3,5,920,276000,NULL,NULL),(30,3,6,975,292500,NULL,NULL),(31,3,7,1030,309000,NULL,NULL),(32,3,8,1085,325500,NULL,NULL),(33,3,9,1140,342000,NULL,NULL),(34,3,10,1195,358500,NULL,NULL),(35,3,11,1250,375000,NULL,NULL),(36,3,12,1305,391500,NULL,NULL),(37,4,1,820,246000,NULL,NULL),(38,4,2,880,264000,NULL,NULL),(39,4,3,940,282000,NULL,NULL),(40,4,4,1000,300000,NULL,NULL),(41,4,5,1060,318000,NULL,NULL),(42,4,6,1120,336000,NULL,NULL),(43,4,7,1180,354000,NULL,NULL),(44,4,8,1240,372000,NULL,NULL),(45,4,9,1300,390000,NULL,NULL),(46,4,10,1360,408000,NULL,NULL),(47,4,11,1420,426000,NULL,NULL),(48,4,12,1480,444000,NULL,NULL),(49,5,1,970,291000,NULL,NULL),(50,5,2,1045,313500,NULL,NULL),(51,5,3,1120,336000,NULL,NULL),(52,5,4,1195,358500,NULL,NULL),(53,5,5,1270,381000,NULL,NULL),(54,5,6,1345,403500,NULL,NULL),(55,5,7,1420,426000,NULL,NULL),(56,5,8,1495,448500,NULL,NULL),(57,5,9,1570,471000,NULL,NULL),(58,5,10,1645,493500,NULL,NULL),(59,5,11,1720,516000,NULL,NULL),(60,5,12,1795,538500,NULL,NULL),(61,6,1,1050,315000,NULL,NULL),(62,6,2,1240,372000,NULL,NULL),(63,6,3,1330,399000,NULL,NULL),(64,6,4,1420,426000,NULL,NULL),(65,6,5,1510,453000,NULL,NULL),(66,6,6,1600,480000,NULL,NULL),(67,6,7,1690,507000,NULL,NULL),(68,6,8,1780,534000,NULL,NULL),(69,6,9,1870,561000,NULL,NULL),(70,6,10,1960,588000,NULL,NULL),(71,6,11,2050,615000,NULL,NULL),(72,6,12,2140,642000,NULL,NULL),(73,7,1,1360,408000,NULL,NULL),(74,7,2,1465,439500,NULL,NULL),(75,7,3,1570,471000,NULL,NULL),(76,7,4,1675,502500,NULL,NULL),(77,7,5,1780,534000,NULL,NULL),(78,7,6,1885,565500,NULL,NULL),(79,7,7,1990,597000,NULL,NULL),(80,7,8,2095,628500,NULL,NULL),(81,7,9,2200,660000,NULL,NULL),(82,7,10,2305,691500,NULL,NULL),(83,7,11,2410,723000,NULL,NULL),(84,7,12,2515,754500,NULL,NULL),(85,8,1,1600,480000,NULL,NULL),(86,8,2,1720,516000,NULL,NULL),(87,8,3,1840,552000,NULL,NULL),(88,8,4,1960,588000,NULL,NULL),(89,8,5,2080,624000,NULL,NULL),(90,8,6,2200,660000,NULL,NULL),(91,8,7,2320,696000,NULL,NULL),(92,8,8,2440,732000,NULL,NULL),(93,8,9,2560,768000,NULL,NULL),(94,8,10,2680,804000,NULL,NULL),(95,8,11,2800,840000,NULL,NULL),(96,8,12,2920,876000,NULL,NULL),(97,9,1,2180,654000,NULL,NULL),(98,9,2,2325,697500,NULL,NULL),(99,9,3,2470,741000,NULL,NULL),(100,9,4,2615,784500,NULL,NULL),(101,9,5,2760,828000,NULL,NULL),(102,9,6,2905,871500,NULL,NULL),(103,9,7,3050,915000,NULL,NULL),(104,9,8,3195,958500,NULL,NULL),(105,9,9,3340,1002000,NULL,NULL),(106,9,10,3485,1045500,NULL,NULL),(107,9,11,3630,1089000,NULL,NULL),(108,9,12,3775,1132500,NULL,NULL),(109,10,1,2860,858000,NULL,NULL),(110,10,2,3030,909000,NULL,NULL),(111,10,3,3200,960000,NULL,NULL),(112,10,3,3370,1011000,NULL,NULL),(113,10,4,3540,1062000,NULL,NULL),(114,10,5,3710,1113000,NULL,NULL),(115,10,6,3880,1164000,NULL,NULL),(116,10,7,3540,1062000,NULL,NULL),(117,10,8,4050,1215000,NULL,NULL),(118,10,9,4220,1266000,NULL,NULL),(119,10,10,4390,1317000,NULL,NULL),(120,10,11,4560,1368000,NULL,NULL),(121,10,12,4730,1419000,NULL,NULL);
/*!40000 ALTER TABLE `grilles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `localites`
--

DROP TABLE IF EXISTS `localites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `localites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `localites`
--

LOCK TABLES `localites` WRITE;
/*!40000 ALTER TABLE `localites` DISABLE KEYS */;
INSERT INTO `localites` VALUES (1,'Brazzaville',NULL,NULL),(2,'Pointe-noire',NULL,NULL),(3,'Ouesso',NULL,NULL),(4,'Dolisie',NULL,NULL);
/*!40000 ALTER TABLE `localites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `membres`
--

DROP TABLE IF EXISTS `membres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `membres` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `appartenance` int(11) NOT NULL,
  `commission_id` bigint(20) unsigned DEFAULT NULL,
  `commissionavancement_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membres`
--

LOCK TABLES `membres` WRITE;
/*!40000 ALTER TABLE `membres` DISABLE KEYS */;
/*!40000 ALTER TABLE `membres` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2013_10_10_215133_create_categories_table',1),(2,'2013_10_10_215135_create_classes_table',1),(3,'2013_10_10_215137_create_diplomes_table',1),(4,'2014_10_10_000000_create_echelons_table',1),(5,'2014_10_10_110857_create_localites_table',1),(6,'2014_10_10_110861_create_administrations_table',1),(7,'2014_10_10_110863_create_directions_table',1),(8,'2014_10_10_110865_create_services_table',1),(9,'2014_10_10_110873_create_bureaus_table',1),(10,'2014_10_11_000000_create_fonction_table',1),(11,'2015_09_05_145838_create_grades_table',1),(12,'2016_10_12_000000_create_users_table',1),(13,'2016_10_12_100000_create_password_resets_table',1),(14,'2019_08_19_000000_create_failed_jobs_table',1),(15,'2019_12_14_000001_create_personal_access_tokens_table',1),(16,'2021_07_05_094247_create_permissions_table',1),(17,'2021_08_05_095358_create_roles_table',1),(18,'2021_08_15_192537_create_role_user',1),(19,'2021_08_15_194030_create_permission_user',1),(20,'2021_09_05_073554_create_session_avancements_table',1),(21,'2021_09_05_073555_create_commissions_table',1),(22,'2021_09_05_073556_create_commissionavancements_table',1),(23,'2022_01_27_191623_create_evaluations_table',1),(24,'2022_01_27_191625_create_destinations_table',1),(25,'2022_01_27_192127_create_destinataires_table',1),(26,'2022_01_27_201904_create_destinataires_autorises_table',1),(27,'2022_03_21_124742_create_registre_depart_table',1),(28,'2022_03_21_124756_create_registre_arrive_table',1),(29,'2022_04_29_062504_create_roles_users_table',1),(30,'2022_09_07_152218_create_competenceprofessionnelles_table',1),(31,'2022_09_07_152745_create_assiduitetravails_table',1),(32,'2022_09_07_152831_create_relationsociales_table',1),(33,'2022_09_10_183133_create_connaissancecomps_table',1),(34,'2022_09_11_100531_create_avis_table',1),(35,'2022_09_13_132112_create_membres_table',1),(36,'2022_09_16_200230_create_evaluationgenerales_table',1),(37,'2022_09_17_160806_create_agentevaluations_table',1),(38,'2022_09_21_100752_create_reclamations_table',1),(39,'2022_09_21_183308_create_decisions_table',1),(40,'2022_09_21_184342_create_sanctions_table',1),(41,'2022_09_21_185115_create_absences_table',1),(42,'2022_09_30_160512_create_commissionagents_table',1),(43,'2022_10_12_053017_create_textes_table',1),(44,'2022_10_13_044014_create_avispresidents_table',1),(45,'2022_10_17_052104_create_grilles_table',1),(46,'2022_10_19_062111_create_echelon_grille_table',1),(47,'2022_10_21_113041_create_diplome_user_table',1),(48,'2022_10_26_032851_create_resumes_table',1),(49,'2022_11_15_121151_create_registre_decisions_table',1),(50,'2022_11_15_121610_create_destinataire_decisions_table',1),(51,'2023_01_04_093009_create_agentexternes_table',1),(52,'2023_05_06_130319_create_enfants_table',1),(53,'2023_06_17_175327_create_postures_table',1),(54,'2023_06_17_175330_create_type_notes_table',1),(55,'2023_06_17_175559_create_noteservices_table',1),(56,'2023_06_17_181141_create_noteservice_user_table',1),(57,'2023_06_18_060552_create_conges_table',1),(58,'2023_06_18_061311_create_conge_user_table',1),(59,'2023_06_29_105621_create_detailsnote_table',1),(60,'2023_07_25_140333_create_typedemandeabsences_table',1),(61,'2023_07_25_140334_create_motifdemandes_table',1),(62,'2023_07_25_140335_create_choixmotifdemandes_table',1),(63,'2023_07_25_140336_create_demandeabscences_table',1),(64,'2023_07_25_140713_create_validationdemandes_table',1),(65,'2023_07_27_182540_create_calendars_table',1),(66,'2023_07_27_182654_create_events_table',1),(67,'2023_07_31_100357_create_registredemandeabsences_table',1),(68,'2023_08_04_000034_create_jobs_table',1),(69,'2023_08_28_144427_create_typeactivites_table',1),(70,'2023_08_28_144428_create_activities_table',1),(71,'2023_08_28_152243_create_activity_user_table',1),(72,'2023_09_13_210932_create_conge_annuels_table',1),(73,'2023_09_13_211032_create_recrutements_table',1),(74,'2023_09_29_130427_create_affectations_table',1),(75,'2023_09_29_130444_create_nominations_table',1),(76,'2023_10_10_074152_drop_user_id_from_affectations_table',2),(77,'2023_10_10_112325_remove_structureable_id_and_structureable_type_columns_from_affectations_table',2),(78,'2023_10_13_065152_create_affectation_user_table',2),(79,'2023_10_13_201416_add_note_affectation_to_table',2),(80,'2023_10_14_105929_create_nominations_table',3),(81,'2023_10_14_111647_create_nomination_user_table',3),(82,'2023_10_19_211903_add_annee_to_table',4),(83,'2023_10_19_211920_add_annee_to_table',4),(84,'2023_10_22_073005_add_situations_to_table',4),(85,'2023_10_22_073045_add_situations_to_table',4);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `motifdemandes`
--

DROP TABLE IF EXISTS `motifdemandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `motifdemandes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `typedemandeabsence_id` bigint(20) unsigned NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `motifdemandes_typedemandeabsence_id_foreign` (`typedemandeabsence_id`),
  CONSTRAINT `motifdemandes_typedemandeabsence_id_foreign` FOREIGN KEY (`typedemandeabsence_id`) REFERENCES `typedemandeabsences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `motifdemandes`
--

LOCK TABLES `motifdemandes` WRITE;
/*!40000 ALTER TABLE `motifdemandes` DISABLE KEYS */;
INSERT INTO `motifdemandes` VALUES (1,1,'MARIAGE',NULL,NULL),(2,1,'ACCOUCHEMENT',NULL,NULL),(3,1,'ACTIVITE RELIGIEUSE',NULL,NULL),(4,1,'DEMENAGEMENT',NULL,NULL),(5,1,'MALADIE',NULL,NULL),(6,1,'DECES',NULL,NULL),(7,1,'ACTIVITES FAMILIALES',NULL,NULL),(8,1,'AUTRE',NULL,NULL);
/*!40000 ALTER TABLE `motifdemandes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nomination_user`
--

DROP TABLE IF EXISTS `nomination_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nomination_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `nomination_id` bigint(20) unsigned DEFAULT NULL,
  `fonction_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `old_numero` varchar(255) DEFAULT NULL,
  `old_date` date DEFAULT NULL,
  `old_fonction` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `nomination_user_user_id_foreign` (`user_id`),
  KEY `nomination_user_nomination_id_foreign` (`nomination_id`),
  KEY `nomination_user_fonction_id_foreign` (`fonction_id`),
  CONSTRAINT `nomination_user_fonction_id_foreign` FOREIGN KEY (`fonction_id`) REFERENCES `fonctions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nomination_user_nomination_id_foreign` FOREIGN KEY (`nomination_id`) REFERENCES `nominations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nomination_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nomination_user`
--

LOCK TABLES `nomination_user` WRITE;
/*!40000 ALTER TABLE `nomination_user` DISABLE KEYS */;
INSERT INTO `nomination_user` VALUES (9,152,1,3,'2023-11-07 06:47:00','2023-11-07 06:47:00','null','0000-00-00','Néant'),(10,65,1,2,'2023-11-07 06:47:24','2023-11-07 06:47:24','null','0000-00-00','Néant'),(11,66,1,2,'2023-11-07 06:47:39','2023-11-07 06:47:39','null','0000-00-00','Néant'),(12,58,1,2,'2023-11-07 06:49:49','2023-11-07 06:49:49','null','0000-00-00','Néant'),(13,53,1,2,'2023-11-07 06:50:07','2023-11-07 06:50:07','null','0000-00-00','Néant'),(14,18,2,3,'2023-11-07 07:14:36','2023-11-07 07:14:36','null','0000-00-00','Néant'),(15,70,2,2,'2023-11-07 07:14:50','2023-11-07 07:14:50','null','0000-00-00','Néant'),(16,164,2,2,'2023-11-07 07:15:18','2023-11-07 07:15:18','null','0000-00-00','Néant'),(17,119,2,3,'2023-11-07 07:15:37','2023-11-07 07:15:37','null','0000-00-00','Néant'),(21,164,4,2,'2023-11-14 14:20:25','2023-11-14 14:20:25','035/ARTF-PSIOMS','2015-09-10','Chef de Bureau'),(22,79,4,2,'2023-11-14 14:20:39','2023-11-14 14:20:39','null','0000-00-00','Néant'),(23,110,4,2,'2023-11-14 14:20:55','2023-11-14 14:20:55','null','0000-00-00','Néant'),(24,12,4,2,'2023-11-14 14:21:17','2023-11-14 14:21:17','015/ARTF-PMFRHL-DLTCA-DRH-SP','2018-02-07','Chef de Bureau'),(25,15,4,2,'2023-11-14 14:21:59','2023-11-14 14:21:59','015/ARTF-PMFRHL-DLTCA-DRH-SP','2018-02-07','Chef de Bureau'),(26,19,4,2,'2023-11-14 14:22:12','2023-11-14 14:22:12','null','0000-00-00','Néant'),(28,163,5,2,'2023-11-14 14:43:39','2023-11-14 14:43:39','null','0000-00-00','Néant'),(29,56,5,2,'2023-11-14 14:43:54','2023-11-14 14:43:54','null','0000-00-00','Néant'),(30,39,5,2,'2023-11-14 14:48:16','2023-11-14 14:48:16','null','0000-00-00','Néant'),(31,36,5,2,'2023-11-14 14:48:38','2023-11-14 14:48:38','null','0000-00-00','Néant'),(32,153,5,3,'2023-11-14 14:48:57','2023-11-14 14:48:57','null','0000-00-00','Néant'),(33,20,5,2,'2023-11-14 14:49:17','2023-11-14 14:49:17','null','0000-00-00','Néant'),(34,25,5,3,'2023-11-14 14:49:57','2023-11-14 14:49:57','null','0000-00-00','Néant'),(35,77,5,2,'2023-11-14 14:50:20','2023-11-14 14:50:20','null','0000-00-00','Néant'),(36,153,2,3,'2023-11-14 14:53:35','2023-11-14 14:53:35','146/ARTF-PMFRHL-DLTCA-DRH-SP','2018-12-14','Chef de Service'),(39,44,6,2,'2024-05-22 13:52:50','2024-05-22 13:52:50','null','0000-00-00','Néant'),(40,42,6,2,'2024-05-22 13:53:58','2024-05-22 13:53:58','null','0000-00-00','Néant'),(41,110,6,2,'2024-05-22 13:54:07','2024-05-22 13:54:07','008/ARTF','2015-03-10','Chef de Bureau'),(42,19,NULL,3,'2024-05-22 14:27:41','2024-05-22 14:27:41','008/ARTF','2015-03-10','Chef de Bureau');
/*!40000 ALTER TABLE `nomination_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nominations`
--

DROP TABLE IF EXISTS `nominations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nominations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_nomination` varchar(255) NOT NULL,
  `date_nomination` date NOT NULL,
  `content` varchar(255) DEFAULT NULL,
  `noteNomination` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `annee` year(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nominations`
--

LOCK TABLES `nominations` WRITE;
/*!40000 ALTER TABLE `nominations` DISABLE KEYS */;
INSERT INTO `nominations` VALUES (1,'010/ARTF-PMFRHL-DLTCA-DRH-SP','2019-02-13',NULL,'documents/nomination/1699339902.pdf','2023-11-07 06:51:42','2023-11-07 06:51:42',2019),(2,'035/ARTF-PSIOMS','2015-09-10',NULL,'documents/nomination/1699341390.pdf','2023-11-07 07:16:30','2023-11-07 07:16:30',2015),(3,'015/ARTF-PMFRHL-DLTCA-DRH-SP','2018-02-07',NULL,'documents/nomination/1699341728.pdf','2023-11-07 07:22:08','2023-11-07 07:22:08',2018),(4,'008/ARTF','2015-03-10',NULL,'documents/nomination/1699972255.pdf','2023-11-14 14:30:55','2023-11-14 14:30:55',2015),(5,'146/ARTF-PMFRHL-DLTCA-DRH-SP','2018-12-14',NULL,'documents/nomination/1699973539.pdf','2023-11-14 14:52:19','2023-11-14 14:52:19',2018),(6,'0249/ARTF-DRHL-SLTCA-SRH-BP ','2021-06-08',NULL,'documents/nomination/1716386107.pdf','2024-05-22 13:55:07','2024-05-22 13:55:07',2021);
/*!40000 ALTER TABLE `nominations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `noteservice_user`
--

DROP TABLE IF EXISTS `noteservice_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `noteservice_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `noteservice_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `posture_id` bigint(20) unsigned DEFAULT NULL,
  `fonction_id` bigint(20) unsigned DEFAULT NULL,
  `nouvelleStructure` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `noteservice_user_noteservice_id_foreign` (`noteservice_id`),
  KEY `noteservice_user_user_id_foreign` (`user_id`),
  KEY `noteservice_user_posture_id_foreign` (`posture_id`),
  CONSTRAINT `noteservice_user_noteservice_id_foreign` FOREIGN KEY (`noteservice_id`) REFERENCES `noteservices` (`id`),
  CONSTRAINT `noteservice_user_posture_id_foreign` FOREIGN KEY (`posture_id`) REFERENCES `postures` (`id`),
  CONSTRAINT `noteservice_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `noteservice_user`
--

LOCK TABLES `noteservice_user` WRITE;
/*!40000 ALTER TABLE `noteservice_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `noteservice_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `noteservices`
--

DROP TABLE IF EXISTS `noteservices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `noteservices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dateNote` date NOT NULL,
  `anneeNote` year(4) NOT NULL,
  `numeroNote` varchar(255) NOT NULL,
  `contente` text NOT NULL,
  `titreSignataire` varchar(255) NOT NULL,
  `designationSignataire` varchar(255) NOT NULL,
  `type_note_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `noteservices_type_note_id_foreign` (`type_note_id`),
  CONSTRAINT `noteservices_type_note_id_foreign` FOREIGN KEY (`type_note_id`) REFERENCES `type_notes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `noteservices`
--

LOCK TABLES `noteservices` WRITE;
/*!40000 ALTER TABLE `noteservices` DISABLE KEYS */;
/*!40000 ALTER TABLE `noteservices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permission_user`
--

DROP TABLE IF EXISTS `permission_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permission_user` (
  `user_id` bigint(20) unsigned NOT NULL,
  `permission_id` bigint(20) unsigned NOT NULL,
  KEY `permission_user_user_id_foreign` (`user_id`),
  KEY `permission_user_permission_id_foreign` (`permission_id`),
  CONSTRAINT `permission_user_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`),
  CONSTRAINT `permission_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permission_user`
--

LOCK TABLES `permission_user` WRITE;
/*!40000 ALTER TABLE `permission_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `permission_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nomPermission` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'create',NULL,NULL),(2,'editer',NULL,NULL),(3,'update',NULL,NULL),(4,'delete',NULL,NULL);
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `postures`
--

DROP TABLE IF EXISTS `postures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `postures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `postures`
--

LOCK TABLES `postures` WRITE;
/*!40000 ALTER TABLE `postures` DISABLE KEYS */;
/*!40000 ALTER TABLE `postures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reclamations`
--

DROP TABLE IF EXISTS `reclamations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reclamations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `auteur_id` bigint(20) unsigned NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reclamations_auteur_id_foreign` (`auteur_id`),
  KEY `reclamations_evaluation_id_foreign` (`evaluation_id`),
  CONSTRAINT `reclamations_auteur_id_foreign` FOREIGN KEY (`auteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reclamations_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reclamations`
--

LOCK TABLES `reclamations` WRITE;
/*!40000 ALTER TABLE `reclamations` DISABLE KEYS */;
/*!40000 ALTER TABLE `reclamations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recrutements`
--

DROP TABLE IF EXISTS `recrutements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recrutements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `matricule` varchar(255) DEFAULT NULL,
  `certificatMedical` varchar(255) NOT NULL,
  `certificatNationalite` varchar(255) NOT NULL,
  `casierJudiciaire` varchar(255) NOT NULL,
  `demandeManuscrite` varchar(255) NOT NULL,
  `CV` varchar(255) NOT NULL,
  `acteNaissance` varchar(255) NOT NULL,
  `valide` tinyint(1) NOT NULL DEFAULT 0,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recrutements_user_id_foreign` (`user_id`),
  CONSTRAINT `recrutements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recrutements`
--

LOCK TABLES `recrutements` WRITE;
/*!40000 ALTER TABLE `recrutements` DISABLE KEYS */;
INSERT INTO `recrutements` VALUES (1,'000379M','','','','','','',1,19,'2023-10-04 15:30:25','2023-10-04 15:30:25'),(2,'000442F','','','','','','',1,24,'2023-10-04 15:41:00','2023-10-04 16:03:16'),(3,'00299A','','','','','','',0,8,'2023-10-04 15:54:43','2023-10-05 13:49:32'),(4,'000490H','','','','','documents/CV/1696435270.pdf','',1,43,'2023-10-04 16:01:10','2023-10-04 16:01:10'),(5,'000514J','','documents/certificatNationalite/1697701083.pdf','documents/casierJudiciaire/1697701083.pdf','','documents/CV/1696435911.pdf','documents/acteNaissance/1696602923.pdf',1,9,'2023-10-04 16:11:51','2023-10-19 07:38:03'),(6,'000523T','','','','','documents/CV/1696436624.pdf','',1,44,'2023-10-04 16:23:44','2023-10-04 16:23:44'),(7,'000426N','','','','','documents/CV/1696437188.pdf','',0,30,'2023-10-04 16:33:08','2023-10-04 16:33:08'),(8,'000435X','','','','','documents/CV/1696437870.pdf','',1,16,'2023-10-04 16:44:30','2023-10-04 16:44:30'),(9,'000496P','','','','','documents/CV/1697119666.pdf','',1,17,'2023-10-04 16:54:22','2023-10-12 14:07:46'),(10,'000375H','','','','','documents/CV/1696492940.pdf','',1,75,'2023-10-05 08:02:20','2023-10-05 08:02:20'),(11,'000364V','','','','','documents/CV/1697036742.pdf','',1,73,'2023-10-05 08:12:16','2023-10-11 15:05:42'),(12,'000487E','','','','','documents/CV/1696495468.pdf','',1,74,'2023-10-05 08:44:28','2023-10-05 08:44:28'),(13,'000421H ','','documents/certificatNationalite/1696496499.pdf','documents/casierJudiciaire/1696496499.pdf','documents/demande/1696496499.pdf','documents/CV/1696496499.pdf','',1,78,'2023-10-05 09:01:39','2023-10-05 09:01:39'),(14,'000495N','','','','','documents/CV/1696496561.pdf','',1,83,'2023-10-05 09:02:41','2023-10-05 09:02:41'),(15,'000338S','','','','documents/demande/1696498637.pdf','documents/CV/1696498637.pdf','',1,79,'2023-10-05 09:37:17','2023-10-05 09:37:17'),(16,'000461B','','documents/certificatNationalite/1696498817.pdf','documents/casierJudiciaire/1696498817.pdf','documents/demande/1696498817.pdf','documents/CV/1696498817.pdf','documents/acteNaissance/1696498817.pdf',1,56,'2023-10-05 09:40:17','2023-10-05 09:40:17'),(17,'000423K','','','','','documents/CV/1696499051.pdf','',1,101,'2023-10-05 09:44:11','2023-10-05 09:44:11'),(18,'000438B ','','documents/certificatNationalite/1696499481.pdf','documents/casierJudiciaire/1696499481.pdf','documents/demande/1696499481.pdf','documents/CV/1696499481.pdf','',1,100,'2023-10-05 09:51:21','2023-10-05 09:51:21'),(19,'000479V','','','','','documents/CV/1696499514.pdf','',1,98,'2023-10-05 09:51:54','2023-10-05 09:51:54'),(20,'000434W','','','','','documents/CV/1696500246.pdf','',1,97,'2023-10-05 10:04:06','2023-10-05 10:04:06'),(21,'000437A','','','','','documents/CV/1696500630.pdf','',1,77,'2023-10-05 10:10:30','2023-10-05 10:10:30'),(22,'000344Y','documents/certificatMedical/1696500704.pdf','documents/certificatNationalite/1696500704.pdf','documents/casierJudiciaire/1696500704.pdf','documents/demande/1696500704.pdf','documents/CV/1696500704.pdf','',1,102,'2023-10-05 10:11:44','2023-10-05 10:11:44'),(23,'000458X ','','','','documents/demande/1696500860.pdf','documents/CV/1696500860.pdf','',1,103,'2023-10-05 10:14:20','2023-10-05 10:14:20'),(24,'000525V','','','','','documents/CV/1696501721.pdf','',1,104,'2023-10-05 10:28:41','2023-10-05 10:28:41'),(25,'000531C','','','','','','',1,106,'2023-10-05 10:34:24','2023-10-05 10:34:24'),(26,'000360R','','documents/certificatNationalite/1696502153.pdf','documents/casierJudiciaire/1696502153.pdf','documents/demande/1696502153.pdf','documents/CV/1696502153.pdf','documents/acteNaissance/1696502153.pdf',1,15,'2023-10-05 10:35:53','2023-10-05 10:35:53'),(27,'000466G','','','','','documents/CV/1696502882.pdf','',1,109,'2023-10-05 10:48:02','2023-10-05 10:48:02'),(28,'000341V','documents/certificatMedical/1696502917.pdf','documents/certificatNationalite/1696502917.pdf','documents/casierJudiciaire/1696502917.pdf','documents/demande/1696502917.pdf','documents/CV/1696502917.pdf','documents/acteNaissance/1696502917.pdf',1,27,'2023-10-05 10:48:37','2023-10-05 10:48:37'),(29,'000464E ','documents/certificatMedical/1696503032.pdf','documents/certificatNationalite/1696503032.pdf','documents/casierJudiciaire/1696503032.pdf','documents/demande/1696503032.pdf','documents/CV/1696503032.pdf','documents/acteNaissance/1696503032.pdf',1,107,'2023-10-05 10:50:32','2023-10-05 10:50:32'),(30,'000436Y','','','','','documents/CV/1696503471.pdf','',1,110,'2023-10-05 10:57:51','2023-10-05 10:57:51'),(31,'000398H','documents/certificatMedical/1696503735.pdf','documents/certificatNationalite/1696503735.pdf','documents/casierJudiciaire/1696503735.pdf','documents/demande/1696503735.pdf','documents/CV/1696503735.pdf','',1,31,'2023-10-05 11:02:15','2023-10-05 11:02:15'),(32,'000522S','documents/certificatMedical/1696503882.pdf','documents/certificatNationalite/1696503882.pdf','documents/casierJudiciaire/1696503882.pdf','','documents/CV/1696503882.pdf','',1,111,'2023-10-05 11:04:42','2023-10-06 11:38:54'),(33,'000550X','','documents/certificatNationalite/1696504108.pdf','documents/casierJudiciaire/1696504108.pdf','','','documents/acteNaissance/1696504108.pdf',1,108,'2023-10-05 11:08:28','2023-10-05 11:08:28'),(34,'000302D','','','','','','documents/acteNaissance/1696504377.pdf',1,5,'2023-10-05 11:12:57','2023-10-05 11:12:57'),(35,'498G','','','','','','',1,112,'2023-10-05 11:13:12','2023-10-05 11:13:12'),(36,'000492K ','','','','','documents/CV/1696504909.pdf','',1,114,'2023-10-05 11:21:49','2023-10-05 11:21:49'),(37,'000488F','','documents/certificatNationalite/1696504987.pdf','documents/casierJudiciaire/1696504987.pdf','documents/demande/1696504987.pdf','documents/CV/1696504987.pdf','documents/acteNaissance/1696504987.pdf',1,113,'2023-10-05 11:23:07','2023-10-05 11:23:07'),(38,'00037C','documents/certificatMedical/1696505367.pdf','documents/certificatNationalite/1696505367.pdf','documents/casierJudiciaire/1696505367.pdf','','documents/CV/1696505367.pdf','documents/acteNaissance/1696505367.pdf',1,70,'2023-10-05 11:29:27','2023-10-06 11:37:50'),(39,'000537J','','','','','','',1,115,'2023-10-05 11:32:25','2023-10-05 11:32:25'),(40,'000410','','','','','','',1,116,'2023-10-05 11:34:36','2023-10-05 11:34:36'),(41,'000456V','','','','','','',1,117,'2023-10-05 11:38:53','2023-10-05 11:38:53'),(42,'00351G','','','','','','',1,119,'2023-10-05 11:54:10','2023-10-05 11:54:10'),(43,'000429R','','','','','','',1,120,'2023-10-05 12:07:56','2023-10-05 12:07:56'),(44,'000540M','','','','','','',1,121,'2023-10-05 12:14:29','2023-10-05 12:14:29'),(45,NULL,'','','','','','',1,123,'2023-10-05 12:14:34','2023-10-05 12:14:34'),(46,'000384S','','','','','documents/CV/1696508105.pdf','',1,122,'2023-10-05 12:15:05','2023-10-05 12:15:05'),(47,'000468J','','','','','','',1,42,'2023-10-05 12:25:24','2023-10-05 12:25:24'),(48,'000450P','','','documents/casierJudiciaire/1696509074.pdf','','documents/CV/1696509074.pdf','documents/acteNaissance/1696509074.pdf',1,125,'2023-10-05 12:31:14','2023-10-05 12:31:14'),(49,'000524U','','','','','','',1,127,'2023-10-05 12:43:14','2023-10-05 12:43:14'),(50,'000518N','','','','','documents/CV/1696509817.pdf','',1,39,'2023-10-05 12:43:37','2023-10-05 12:43:37'),(51,'000303E','','','','documents/demande/1696512821.pdf','documents/CV/1696512821.pdf','',1,130,'2023-10-05 13:33:41','2023-10-05 13:33:41'),(52,NULL,'documents/certificatMedical/1696512848.pdf','documents/certificatNationalite/1696512848.pdf','documents/casierJudiciaire/1696512848.pdf','documents/demande/1696512848.pdf','documents/CV/1696512848.pdf','documents/acteNaissance/1696512848.pdf',1,132,'2023-10-05 13:34:08','2023-10-05 13:34:08'),(53,'000312P','','','','','documents/CV/1696513890.pdf','',1,96,'2023-10-05 13:51:30','2023-10-05 13:51:30'),(54,'000417D','','','','','documents/CV/1697195386.pdf','',1,29,'2023-10-05 13:54:53','2023-10-13 11:09:46'),(55,'000501U','','','','','documents/CV/1696514483.pdf','',1,95,'2023-10-05 14:01:23','2023-10-05 14:01:23'),(56,'000500T','','','','','documents/CV/1696515844.pdf','',1,94,'2023-10-05 14:24:04','2023-10-05 14:24:04'),(57,'000517M','','','','','documents/CV/1696516335.pdf','',1,93,'2023-10-05 14:32:15','2023-10-05 14:32:15'),(58,'000448M','','','','','documents/CV/1696516951.pdf','',1,92,'2023-10-05 14:42:31','2023-10-05 14:42:31'),(59,'000475R','documents/certificatMedical/1696519290.pdf','documents/certificatNationalite/1696519290.pdf','documents/casierJudiciaire/1696519290.pdf','documents/demande/1696519290.pdf','documents/CV/1696519290.pdf','documents/acteNaissance/1696519290.pdf',1,26,'2023-10-05 15:21:30','2023-10-05 15:21:30'),(60,'000463D','','documents/certificatNationalite/1696579494.pdf','documents/casierJudiciaire/1696579494.pdf','documents/demande/1696579494.pdf','documents/CV/1696579494.pdf','',1,32,'2023-10-06 08:04:54','2023-10-06 08:04:54'),(61,'000473P','documents/certificatMedical/1696584340.pdf','documents/certificatNationalite/1696584340.pdf','documents/casierJudiciaire/1696584340.pdf','','documents/CV/1696584340.pdf','documents/acteNaissance/1696584340.pdf',1,33,'2023-10-06 09:25:40','2023-10-06 09:25:40'),(62,'000356M ','','','','documents/demande/1696585550.pdf','documents/CV/1696585550.pdf','documents/acteNaissance/1696585550.pdf',1,134,'2023-10-06 09:45:50','2023-10-06 09:45:50'),(63,'000474Q','','documents/certificatNationalite/1696586578.pdf','documents/casierJudiciaire/1696586578.pdf','','documents/CV/1696586578.pdf','documents/acteNaissance/1696586578.pdf',1,118,'2023-10-06 10:02:58','2023-10-06 10:02:58'),(64,'000535G','documents/certificatMedical/1696587454.pdf','documents/certificatNationalite/1696587454.pdf','documents/casierJudiciaire/1696587454.pdf','','documents/CV/1696587454.pdf','documents/acteNaissance/1696587454.pdf',1,136,'2023-10-06 10:17:34','2023-10-06 10:17:34'),(65,'000355L','documents/certificatMedical/1696587490.pdf','documents/certificatNationalite/1696587490.pdf','documents/casierJudiciaire/1696587490.pdf','documents/demande/1696587490.pdf','documents/CV/1696587490.pdf','documents/acteNaissance/1696587490.pdf',1,12,'2023-10-06 10:18:10','2023-10-06 10:18:10'),(66,'000494M','','documents/certificatNationalite/1696588322.pdf','documents/casierJudiciaire/1696588322.pdf','','','documents/acteNaissance/1696588322.pdf',1,135,'2023-10-06 10:32:02','2023-10-06 10:32:02'),(67,'000324C','','','','','documents/CV/1696591259.pdf','',1,138,'2023-10-06 11:20:59','2023-10-06 11:20:59'),(68,'000449N','','','','','documents/CV/1696592491.pdf','',1,76,'2023-10-06 11:41:32','2023-10-06 11:41:32'),(69,'000443G','','documents/certificatNationalite/1696597925.pdf','documents/casierJudiciaire/1696597925.pdf','documents/demande/1696597925.pdf','documents/CV/1696597925.pdf','documents/acteNaissance/1696597925.pdf',1,54,'2023-10-06 13:12:05','2023-10-06 13:12:05'),(70,'000418E','','documents/certificatNationalite/1696598777.pdf','documents/casierJudiciaire/1696598777.pdf','documents/demande/1696598777.pdf','documents/CV/1696598777.pdf','documents/acteNaissance/1696598777.pdf',1,7,'2023-10-06 13:26:17','2023-10-06 13:26:17'),(71,'000460A','','documents/certificatNationalite/1696600476.pdf','documents/casierJudiciaire/1696600476.pdf','documents/demande/1696600476.pdf','','documents/acteNaissance/1696600476.pdf',1,58,'2023-10-06 13:54:36','2023-10-06 13:54:36'),(72,'000467H','documents/certificatMedical/1696600630.pdf','documents/certificatNationalite/1696600630.pdf','documents/casierJudiciaire/1696600630.pdf','documents/demande/1696600630.pdf','documents/CV/1696600630.pdf','documents/acteNaissance/1696600630.pdf',1,141,'2023-10-06 13:57:10','2023-10-06 13:57:10'),(73,'000346B','','documents/certificatNationalite/1696603709.pdf','documents/casierJudiciaire/1696603709.pdf','documents/demande/1696603709.pdf','documents/CV/1696603709.pdf','documents/acteNaissance/1696603709.pdf',1,142,'2023-10-06 14:06:11','2023-10-06 14:48:30'),(74,'000441E','documents/certificatMedical/1696601242.pdf','documents/certificatNationalite/1696601242.pdf','documents/casierJudiciaire/1696601242.pdf','documents/demande/1696601242.pdf','documents/CV/1696601242.pdf','documents/acteNaissance/1696601242.pdf',1,140,'2023-10-06 14:07:22','2023-10-06 14:07:22'),(75,'000505Y','','documents/certificatNationalite/1696601451.pdf','documents/casierJudiciaire/1696601451.pdf','documents/demande/1696601451.pdf','documents/CV/1696601451.pdf','documents/acteNaissance/1696601451.pdf',1,53,'2023-10-06 14:10:51','2023-10-06 14:10:51'),(76,'000424L','','documents/certificatNationalite/1696602225.pdf','documents/casierJudiciaire/1696602225.pdf','documents/demande/1696602225.pdf','','documents/acteNaissance/1696602225.pdf',1,47,'2023-10-06 14:23:45','2023-10-06 14:23:45'),(77,'000515K','','','','','documents/CV/1696602863.pdf','',1,133,'2023-10-06 14:24:35','2023-10-06 14:34:23'),(78,'000419F','','','','','documents/CV/1696602538.pdf','',1,67,'2023-10-06 14:28:58','2023-10-06 14:28:58'),(79,'000390Y','documents/certificatMedical/1696604090.pdf','documents/certificatNationalite/1696604090.pdf','documents/casierJudiciaire/1696604090.pdf','documents/demande/1696604090.pdf','documents/CV/1696604090.pdf','documents/acteNaissance/1696604090.pdf',1,13,'2023-10-06 14:54:50','2023-10-06 14:54:50'),(80,'000361S','documents/certificatMedical/1696604701.pdf','documents/certificatNationalite/1696604701.pdf','documents/casierJudiciaire/1696604701.pdf','documents/demande/1696604701.pdf','documents/CV/1696604701.pdf','documents/acteNaissance/1696604701.pdf',1,4,'2023-10-06 15:05:01','2023-10-06 15:05:01'),(81,'000480W ','','documents/certificatNationalite/1696605339.pdf','documents/casierJudiciaire/1696605339.pdf','documents/demande/1696605339.pdf','documents/CV/1696605339.pdf','documents/acteNaissance/1696605339.pdf',1,143,'2023-10-06 15:15:39','2023-10-06 15:15:39'),(82,'000471M','documents/certificatMedical/1696605875.pdf','documents/certificatNationalite/1696605875.pdf','documents/casierJudiciaire/1696605875.pdf','','documents/CV/1696605875.pdf','',1,61,'2023-10-06 15:24:35','2023-10-06 15:24:35'),(83,'000478U','','documents/certificatNationalite/1696607139.pdf','documents/casierJudiciaire/1696607139.pdf','documents/demande/1696607139.pdf','','documents/acteNaissance/1696607139.pdf',1,144,'2023-10-06 15:45:39','2023-10-06 15:45:39'),(84,'000498R','','','','','','',1,146,'2023-10-09 09:42:49','2023-10-09 09:42:49'),(85,'000469K','','','','','','',1,91,'2023-10-09 09:59:51','2023-10-09 09:59:51'),(86,'000503W','','','','','documents/CV/1696845661.pdf','',1,147,'2023-10-09 10:01:01','2023-10-09 10:01:01'),(87,'000493L','','','','','documents/CV/1696845943.pdf','',1,90,'2023-10-09 10:05:43','2023-10-09 10:05:43'),(88,'000371D','documents/certificatMedical/1696848636.pdf','documents/certificatNationalite/1696848636.pdf','documents/casierJudiciaire/1696848636.pdf','documents/demande/1696848636.pdf','documents/CV/1696848636.pdf','documents/acteNaissance/1696848636.pdf',1,148,'2023-10-09 10:50:36','2023-10-09 10:50:36'),(89,'000336Q','documents/certificatMedical/1696855910.pdf','documents/certificatNationalite/1696855910.pdf','documents/casierJudiciaire/1696855910.pdf','documents/demande/1696855910.pdf','documents/CV/1696855910.pdf','',1,149,'2023-10-09 12:51:50','2023-10-09 12:51:50'),(90,'000528y','','','','','','',1,150,'2023-10-09 13:09:32','2023-10-09 13:09:32'),(91,'000470L','','documents/certificatNationalite/1696858026.pdf','documents/casierJudiciaire/1696858026.pdf','documents/demande/1696858026.pdf','documents/CV/1696858026.pdf','documents/acteNaissance/1696858026.pdf',1,151,'2023-10-09 13:27:06','2023-10-09 13:27:06'),(92,'000485C','','','','','documents/CV/1696860010.pdf','',1,60,'2023-10-09 14:00:10','2023-10-09 14:00:10'),(93,'000516L','','','','','','',1,20,'2023-10-09 14:15:09','2023-10-09 14:15:09'),(94,'000453S','documents/certificatMedical/1696863833.pdf','documents/certificatNationalite/1696863833.pdf','documents/casierJudiciaire/1696863833.pdf','','','documents/acteNaissance/1696863833.pdf',1,66,'2023-10-09 15:03:53','2023-10-09 15:03:53'),(95,'000440D','','','','','documents/CV/1696864966.pdf','',1,145,'2023-10-09 15:22:46','2023-10-09 15:22:46'),(96,'000512G','','','','','documents/CV/1696865784.pdf','',1,80,'2023-10-09 15:36:24','2023-10-09 15:36:24'),(97,'000486D','','','','','documents/CV/1696866043.pdf','',1,81,'2023-10-09 15:40:43','2023-10-09 15:40:43'),(98,'000462C','','','','','documents/CV/1696866917.pdf','',1,84,'2023-10-09 15:55:17','2023-10-09 15:55:17'),(99,'000372E','documents/certificatMedical/1696943434.pdf','documents/certificatNationalite/1696943434.pdf','documents/casierJudiciaire/1696943434.pdf','documents/demande/1696943434.pdf','documents/CV/1696943434.pdf','',1,46,'2023-10-10 13:10:34','2023-10-10 13:10:34'),(100,'000513H','','','','','documents/CV/1696954097.pdf','',1,85,'2023-10-10 16:08:17','2023-10-10 16:08:17'),(101,'000452R','','','','','documents/CV/1696954670.pdf','',1,86,'2023-10-10 16:17:50','2023-10-10 16:17:50'),(102,'000465F','','','','','','',1,87,'2023-10-10 16:22:41','2023-10-10 16:22:41'),(103,'000327F','','','','','documents/CV/1696955166.pdf','',1,88,'2023-10-10 16:26:06','2023-10-10 16:26:06'),(104,'000430S','','','','','','',1,89,'2023-10-10 16:31:03','2023-10-10 16:31:03'),(105,'000341V','documents/certificatMedical/1697036629.pdf','documents/certificatNationalite/1697036629.pdf','documents/casierJudiciaire/1697036629.pdf','documents/demande/1697036629.pdf','documents/CV/1697036629.pdf','documents/acteNaissance/1697036629.pdf',1,152,'2023-10-11 08:21:45','2023-10-11 15:03:49'),(106,NULL,'','','','','','',1,152,'2023-10-11 08:23:31','2023-10-11 08:23:31'),(107,'000483','','','','','','',1,154,'2023-10-12 13:41:48','2023-10-12 13:41:48'),(108,'000484B','','','','','documents/CV/1697118717.pdf','',1,155,'2023-10-12 13:51:57','2023-10-12 13:57:38'),(109,'000445J','','','','','documents/CV/1697196771.pdf','',1,156,'2023-10-13 11:32:51','2023-10-18 13:07:21'),(110,'000358P','documents/certificatMedical/1697203626.pdf','documents/certificatNationalite/1697203626.pdf','documents/casierJudiciaire/1697203626.pdf','documents/demande/1697203626.pdf','documents/CV/1697203626.pdf','',1,65,'2023-10-13 13:24:03','2023-10-13 13:27:06'),(111,'000321E','documents/certificatMedical/1697550459.pdf','documents/certificatNationalite/1697550459.pdf','documents/casierJudiciaire/1697550459.pdf','','documents/CV/1697550459.pdf','',1,153,'2023-10-17 13:47:39','2023-10-17 13:47:39'),(112,'000348D','documents/certificatMedical/1697620406.pdf','documents/certificatNationalite/1697620406.pdf','documents/casierJudiciaire/1697620406.pdf','','documents/CV/1697620406.pdf','',1,157,'2023-10-18 08:43:35','2023-10-18 09:13:26'),(113,'000383R','','','','','documents/CV/1697621571.pdf','documents/acteNaissance/1697621571.pdf',1,158,'2023-10-18 09:32:51','2023-10-18 09:32:51'),(114,'000367Y','','','','','','',1,159,'2023-10-18 13:12:34','2023-10-18 13:12:34'),(115,'000432U','','','','','documents/CV/1697636765.pdf','',1,160,'2023-10-18 13:38:48','2023-10-18 13:46:05'),(116,'000472N','','documents/certificatNationalite/1697701023.pdf','documents/casierJudiciaire/1697701023.pdf','','documents/CV/1697637659.pdf','documents/acteNaissance/1697701023.pdf',1,161,'2023-10-18 14:00:59','2023-10-19 07:37:03'),(117,'000386U','','','','','','',1,162,'2023-10-20 12:13:07','2023-10-20 12:13:07'),(118,'000332L','documents/certificatMedical/1698058011.pdf','documents/certificatNationalite/1698058011.pdf','documents/casierJudiciaire/1698058011.pdf','documents/demande/1698058011.pdf','documents/CV/1698058011.pdf','',1,28,'2023-10-23 10:46:51','2023-10-23 10:46:51'),(119,'000504X','','','','','','',1,36,'2023-10-26 08:02:23','2023-10-26 08:02:23'),(120,'000451Q','documents/certificatMedical/1698309807.pdf','documents/certificatNationalite/1698309807.pdf','documents/casierJudiciaire/1698309807.pdf','documents/demande/1698309807.pdf','documents/CV/1698309807.pdf','documents/acteNaissance/1698309807.pdf',1,25,'2023-10-26 08:43:27','2023-10-26 08:43:27'),(121,'000529A','','','','','','',1,38,'2023-10-31 09:36:49','2023-10-31 09:36:49'),(122,'000313S','','documents/certificatNationalite/1698746384.pdf','documents/casierJudiciaire/1698746384.pdf','','documents/CV/1698746384.pdf','documents/acteNaissance/1715075597.pdf',1,18,'2023-10-31 09:59:44','2024-05-07 09:53:17'),(123,'000506A','documents/certificatMedical/1698748009.pdf','documents/certificatNationalite/1698748009.pdf','documents/casierJudiciaire/1698748009.pdf','documents/demande/1698748009.pdf','documents/CV/1698748009.pdf','documents/acteNaissance/1698748009.pdf',1,163,'2023-10-31 10:21:56','2023-10-31 10:26:49'),(124,'000350F','documents/certificatMedical/1698748940.pdf','documents/certificatNationalite/1698748940.pdf','documents/casierJudiciaire/1698748940.pdf','documents/demande/1698748940.pdf','documents/CV/1698748940.pdf','documents/acteNaissance/1698748940.pdf',1,21,'2023-10-31 10:37:34','2023-10-31 10:42:20'),(125,'000308K','documents/certificatMedical/1698997435.pdf','documents/certificatNationalite/1698997435.pdf','documents/casierJudiciaire/1698997435.pdf','documents/demande/1698997435.pdf','documents/CV/1698997435.pdf','documents/acteNaissance/1698997435.pdf',1,164,'2023-11-03 07:37:55','2023-11-03 07:43:55'),(126,'000304F','','','','','','',1,165,'2023-11-03 10:12:22','2023-11-03 10:12:22'),(127,'000303J','','','','','','',1,166,'2023-11-03 10:23:55','2023-11-03 10:23:55'),(128,'000322A','','','','','','',1,167,'2023-11-03 13:01:07','2023-11-03 13:01:07'),(129,'000380N','','','','','','',1,170,'2023-11-06 07:16:28','2023-11-06 07:16:28'),(130,'000335P','','','','','','',1,172,'2023-11-06 07:28:32','2023-11-06 07:28:32'),(131,'000373F','','','','','','',1,173,'2023-11-06 07:35:08','2023-11-06 07:35:08'),(132,'000333M','','','','','','',1,174,'2023-11-06 07:43:27','2023-11-06 07:43:27'),(133,'000345A','','','','','','',1,175,'2023-11-06 07:51:17','2023-11-06 07:51:17'),(134,'000318V','','','','','','',1,176,'2023-11-06 07:59:34','2023-11-06 07:59:34'),(135,'000311N','','','','','','',1,177,'2023-11-06 08:30:24','2023-11-06 08:30:24'),(136,'000300B','','','','','','',1,178,'2023-11-06 08:38:16','2023-11-06 08:38:16'),(137,'000359Q','','','','','','',1,180,'2023-11-06 09:00:45','2023-11-06 09:00:45'),(138,'000309L','','','','','','',1,181,'2023-11-06 10:19:26','2023-11-06 10:19:26'),(139,'000370C','','','','','','',1,182,'2023-11-06 10:28:09','2023-11-06 10:28:09'),(140,'000314R','','','','','','',1,183,'2023-11-06 10:33:00','2023-11-06 10:33:00'),(141,'000382Q','','','','','','',1,184,'2023-11-06 10:39:02','2023-11-06 10:39:02'),(142,'000352H','','','','','','',1,185,'2023-11-06 10:49:22','2023-11-06 10:49:22'),(143,'000378L','','','','','','',1,186,'2023-11-06 10:55:53','2023-11-06 10:55:53'),(144,'000376J','','','','','','',1,187,'2023-11-07 06:45:42','2023-11-07 06:45:42'),(145,'000343X','','','','','','',1,188,'2023-11-07 06:54:37','2023-11-07 06:54:37'),(146,'000366X','','','','','','',1,189,'2023-11-07 06:59:35','2023-11-07 06:59:35'),(147,'000316T','','','','','','',1,190,'2023-11-07 07:06:18','2023-11-07 07:06:18'),(148,'000295v','','','','','documents/CV/1716385083.pdf','',1,10,'2024-05-22 13:38:03','2024-05-22 13:38:03'),(149,'000499S','','','','','documents/CV/1716387681.pdf','',1,59,'2024-05-22 14:21:21','2024-05-22 14:21:21');
/*!40000 ALTER TABLE `recrutements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registre_arrive`
--

DROP TABLE IF EXISTS `registre_arrive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registre_arrive` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provenance` varchar(255) NOT NULL,
  `objet` varchar(255) NOT NULL,
  `annee_session_avancement` varchar(255) NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `destinataire_type` varchar(255) NOT NULL,
  `destinataire_id` bigint(20) unsigned NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registre_arrive_destinataire_type_destinataire_id_index` (`destinataire_type`,`destinataire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registre_arrive`
--

LOCK TABLES `registre_arrive` WRITE;
/*!40000 ALTER TABLE `registre_arrive` DISABLE KEYS */;
/*!40000 ALTER TABLE `registre_arrive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registre_decisions`
--

DROP TABLE IF EXISTS `registre_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registre_decisions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provenance` varchar(255) NOT NULL,
  `objet` varchar(255) NOT NULL,
  `annee_session_avancement` varchar(255) NOT NULL,
  `decision_id` bigint(20) unsigned DEFAULT NULL,
  `destinataire_type` varchar(255) NOT NULL,
  `destinataire_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registre_decisions_destinataire_type_destinataire_id_index` (`destinataire_type`,`destinataire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registre_decisions`
--

LOCK TABLES `registre_decisions` WRITE;
/*!40000 ALTER TABLE `registre_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `registre_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registre_depart`
--

DROP TABLE IF EXISTS `registre_depart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registre_depart` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `destination` varchar(255) NOT NULL,
  `objet` varchar(255) NOT NULL,
  `annee_session_avancement` varchar(255) NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `expediteur_type` varchar(255) NOT NULL,
  `expediteur_id` bigint(20) unsigned NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registre_depart_expediteur_type_expediteur_id_index` (`expediteur_type`,`expediteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registre_depart`
--

LOCK TABLES `registre_depart` WRITE;
/*!40000 ALTER TABLE `registre_depart` DISABLE KEYS */;
/*!40000 ALTER TABLE `registre_depart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registredemandeabsences`
--

DROP TABLE IF EXISTS `registredemandeabsences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registredemandeabsences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provenance` varchar(255) NOT NULL,
  `objet` varchar(255) NOT NULL,
  `demandeabscence_id` bigint(20) unsigned NOT NULL,
  `destinataire_type` varchar(255) NOT NULL,
  `destinataire_id` bigint(20) unsigned NOT NULL,
  `etat_reception` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `registredemandeabsences_destinataire_type_destinataire_id_index` (`destinataire_type`,`destinataire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registredemandeabsences`
--

LOCK TABLES `registredemandeabsences` WRITE;
/*!40000 ALTER TABLE `registredemandeabsences` DISABLE KEYS */;
/*!40000 ALTER TABLE `registredemandeabsences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `relationsociales`
--

DROP TABLE IF EXISTS `relationsociales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `relationsociales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `capacite_animer` double(8,2) NOT NULL DEFAULT 0.00,
  `adaptabilite` double(8,2) NOT NULL DEFAULT 0.00,
  `communication` double(8,2) NOT NULL DEFAULT 0.00,
  `rapport_hierarchie` double(8,2) NOT NULL DEFAULT 0.00,
  `rapport_collegue` double(8,2) NOT NULL DEFAULT 0.00,
  `qualite_accueil` double(8,2) NOT NULL DEFAULT 0.00,
  `faculte_ecoute` double(8,2) NOT NULL DEFAULT 0.00,
  `capacite_equipe` double(8,2) NOT NULL DEFAULT 0.00,
  `respect_vestimentaire` double(8,2) NOT NULL DEFAULT 0.00,
  `note_total` double(8,2) NOT NULL DEFAULT 0.00,
  `user_id` bigint(20) unsigned NOT NULL,
  `evaluation_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `relationsociales_user_id_foreign` (`user_id`),
  KEY `relationsociales_evaluation_id_foreign` (`evaluation_id`),
  CONSTRAINT `relationsociales_evaluation_id_foreign` FOREIGN KEY (`evaluation_id`) REFERENCES `evaluations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `relationsociales_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `relationsociales`
--

LOCK TABLES `relationsociales` WRITE;
/*!40000 ALTER TABLE `relationsociales` DISABLE KEYS */;
/*!40000 ALTER TABLE `relationsociales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `resumes`
--

DROP TABLE IF EXISTS `resumes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resumes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resumes`
--

LOCK TABLES `resumes` WRITE;
/*!40000 ALTER TABLE `resumes` DISABLE KEYS */;
/*!40000 ALTER TABLE `resumes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_user`
--

DROP TABLE IF EXISTS `role_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_user` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  KEY `role_user_user_id_foreign` (`user_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_user`
--

LOCK TABLES `role_user` WRITE;
/*!40000 ALTER TABLE `role_user` DISABLE KEYS */;
INSERT INTO `role_user` VALUES (18,1),(18,2),(18,3),(40,1),(40,2),(40,3),(44,2),(44,3),(44,4),(164,4),(37,4),(39,4),(38,4),(41,4),(3,4),(1,4),(57,4),(73,4),(24,4),(74,4),(56,4),(35,4),(34,4),(9,4),(10,4),(43,4),(42,4),(20,4),(75,4),(19,4),(36,1),(36,2),(36,3),(36,4);
/*!40000 ALTER TABLE `role_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nomRole` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'superadmin',NULL,NULL),(2,'admin',NULL,NULL),(3,'drhl',NULL,NULL),(4,'agent',NULL,NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles_users`
--

DROP TABLE IF EXISTS `roles_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles_users` (
  `user_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles_users`
--

LOCK TABLES `roles_users` WRITE;
/*!40000 ALTER TABLE `roles_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sanctions`
--

DROP TABLE IF EXISTS `sanctions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sanctions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `referenceCourrier` varchar(255) NOT NULL,
  `dateSanction` date NOT NULL,
  `annee` varchar(255) NOT NULL,
  `nature` varchar(255) NOT NULL,
  `motif` text NOT NULL,
  `sanctionFile` text NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sanctions_user_id_foreign` (`user_id`),
  CONSTRAINT `sanctions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sanctions`
--

LOCK TABLES `sanctions` WRITE;
/*!40000 ALTER TABLE `sanctions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sanctions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `services` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `sigle` varchar(255) NOT NULL,
  `direction_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_direction_id_foreign` (`direction_id`),
  CONSTRAINT `services_direction_id_foreign` FOREIGN KEY (`direction_id`) REFERENCES `directions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'SERVICE SYSTEME D\'INFORMATION','S.S.I',1,NULL,NULL,NULL),(2,'SERVICE DES RESSOURCES HUMAINES','S.R.H',4,NULL,NULL,NULL),(3,'SERVICE BUDGET','S.B',2,NULL,NULL,NULL),(4,'SÉCRETARIAT DIRECTION GÉNÉRALE','S.D.G',1,NULL,NULL,NULL),(5,'SERVICE LEGISLATION DU TRAVAIL ET CONFORMITÉ ADM.','S.L.T.C.A',4,NULL,NULL,NULL),(6,'SERVICE DES ÉTUDES','S.E',6,NULL,NULL,NULL),(7,'SERVICE DE L\'INSPECTION','S.I',6,NULL,NULL,NULL),(8,'SERVICE DES AGREMENTS ET DE LA REGULATION','S.A.R',3,NULL,NULL,NULL),(9,'SERVICE DES AFFAIRES JURIDIQUES','S.A.J',7,NULL,NULL,NULL),(10,'SERVICE CONTENTIEUX ET POURSUITES','S.C.P',7,NULL,NULL,NULL),(11,'SERVICE INVESTISSEMENTS ET CAPITAUX','S.I.C',3,NULL,NULL,NULL),(12,'SERVICE RECETTE','S.R',5,NULL,NULL,NULL),(13,'SERVICE DEPENSE','S.D',5,NULL,NULL,NULL),(14,'SERVICE COMMUNICATION','S.COMM',1,NULL,NULL,NULL),(15,'SERVICE AUDIT INTERNE','S.AUD',1,NULL,NULL,NULL),(16,'SERVICE LOGISTIQUE','S.LOG',4,NULL,NULL,NULL),(17,'SERVICE DES STATISTIQUES ET ANALYSES','S.S.A',6,NULL,NULL,NULL),(18,'SERVICE DES OPERATION EN CAPITAL','S.O.C',3,NULL,NULL,NULL),(19,'SERVICE DES TRANSACTIONS COURANTES','S.T.C',3,NULL,NULL,NULL),(20,'SERVICE ORDONNANCEMENT','S.O',2,NULL,NULL,NULL),(21,'SERVICE FONDS ET VALEURS','S.F.V',5,NULL,NULL,NULL),(22,'SERVICE COMPTABILITE ','SC',5,NULL,'2024-05-22 14:09:53','2024-05-22 14:09:53');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session_avancements`
--

DROP TABLE IF EXISTS `session_avancements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session_avancements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `anneeAvancement` year(4) NOT NULL,
  `dateDebutAvancement` datetime DEFAULT NULL,
  `dateFinAvancement` datetime DEFAULT NULL,
  `statut` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_avancements_anneeavancement_unique` (`anneeAvancement`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session_avancements`
--

LOCK TABLES `session_avancements` WRITE;
/*!40000 ALTER TABLE `session_avancements` DISABLE KEYS */;
/*!40000 ALTER TABLE `session_avancements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `textes`
--

DROP TABLE IF EXISTS `textes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `textes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `numero_decision` varchar(255) NOT NULL,
  `date_decision` date NOT NULL,
  `date_effet` date DEFAULT NULL,
  `nature` varchar(255) DEFAULT NULL,
  `decision_avancement` varchar(255) NOT NULL,
  `annee` int(11) NOT NULL,
  `indice` int(11) NOT NULL,
  `salaire` int(11) NOT NULL,
  `texte_actif` tinyint(1) NOT NULL DEFAULT 1,
  `classe_id` bigint(20) unsigned NOT NULL,
  `echelon_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `textes_classe_id_foreign` (`classe_id`),
  KEY `textes_echelon_id_foreign` (`echelon_id`),
  KEY `textes_user_id_foreign` (`user_id`),
  CONSTRAINT `textes_classe_id_foreign` FOREIGN KEY (`classe_id`) REFERENCES `classes` (`id`),
  CONSTRAINT `textes_echelon_id_foreign` FOREIGN KEY (`echelon_id`) REFERENCES `echelons` (`id`),
  CONSTRAINT `textes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `textes`
--

LOCK TABLES `textes` WRITE;
/*!40000 ALTER TABLE `textes` DISABLE KEYS */;
INSERT INTO `textes` VALUES (1,'013/DGCRF-DAP-SPF','1999-09-15',NULL,'Engagement','documents/decisions/1696433425.pdf',1999,970,291000,1,5,1,19,'2023-10-04 15:30:25','2023-10-04 15:30:25'),(2,'0010/ARTF-PMFRHL-DRH-SP.','2017-10-27',NULL,'Engagement','',2017,1600,480000,1,8,1,24,'2023-10-04 15:41:00','2023-10-06 10:52:00'),(3,'Décision n°013','2000-07-12',NULL,'Engagement','documents/decisions/1696434883.pdf',2000,1600,480000,1,8,1,8,'2023-10-04 15:54:43','2023-10-04 15:54:43'),(4,'050/ARTF-DRHL-SLTCA-SRH-BP','2020-09-07',NULL,'Engagement','documents/decisions/1696435270.pdf',2020,1675,502500,1,7,4,43,'2023-10-04 16:01:10','2023-10-04 16:01:10'),(5,'N°0070ARTF-PMFRHL-DRH-SP','2017-08-25',NULL,'Engagement','documents/decisions/1697701083.pdf',2017,1360,408000,1,7,1,9,'2023-10-04 16:11:51','2023-10-19 07:38:03'),(6,'note d\'engagement n°001/ARTF-PMFRHL-DLTCA-DRH-SP','2020-01-07',NULL,'Engagement','',2020,1465,439500,1,7,2,44,'2023-10-04 16:23:44','2023-10-04 16:23:44'),(7,'11111111','2017-10-27',NULL,'Engagement','documents/decisions/1696437188.pdf',2017,1360,408000,1,7,1,30,'2023-10-04 16:33:08','2023-10-04 16:33:08'),(8,'001045','2017-10-27',NULL,'Engagement','documents/decisions/1697116907.pdf',2017,1050,315000,1,6,1,16,'2023-10-04 16:44:30','2023-10-12 13:21:47'),(9,'146/ARTF-DRHL-SLTCA-SRH-BP','2020-12-22',NULL,'Engagement','documents/decisions/1696438462.pdf',2020,1050,315000,1,6,1,17,'2023-10-04 16:54:22','2023-10-04 16:54:22'),(10,'098/DGCRF-DAF-SPF du 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1696492940.pdf',1999,1050,315000,1,6,1,75,'2023-10-05 08:02:20','2023-10-06 13:37:23'),(11,'098/DGCRF/DAP/SP/27.12.1999','1999-09-15',NULL,'Engagement','documents/decisions/1697035868.pdf',1999,970,291000,1,5,1,73,'2023-10-05 08:12:16','2023-10-11 14:51:08'),(12,'006/ARTF de 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696495468.pdf',2017,1050,315000,1,6,1,74,'2023-10-05 08:44:28','2023-10-05 08:44:28'),(13,'104','2016-11-04',NULL,'Engagement','documents/decisions/1696496499.pdf',2016,1360,408000,1,7,1,78,'2023-10-05 09:01:39','2023-10-05 09:01:39'),(14,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696496561.pdf',2017,970,291000,1,5,1,83,'2023-10-05 09:02:41','2023-10-05 09:02:41'),(15,'098','1999-09-15',NULL,'Engagement','documents/decisions/1696498637.pdf',1999,1360,408000,1,7,1,79,'2023-10-05 09:37:17','2023-10-05 09:37:17'),(16,'006','2017-04-18',NULL,'Engagement','documents/decisions/1696498817.pdf',2017,1360,408000,1,7,1,56,'2023-10-05 09:40:17','2023-10-06 11:52:23'),(17,'006/ARTF','2015-10-03',NULL,'Engagement','documents/decisions/1696499051.pdf',2015,1360,408000,1,7,1,101,'2023-10-05 09:44:11','2023-10-05 09:44:11'),(18,'006','2017-04-18',NULL,'Engagement','documents/decisions/1696499481.pdf',2017,1600,480000,1,8,1,100,'2023-10-05 09:51:21','2023-10-05 09:51:21'),(19,'006','2017-04-17',NULL,'Engagement','documents/decisions/1696499514.pdf',2017,1570,471000,1,7,3,98,'2023-10-05 09:51:54','2023-10-05 09:51:54'),(20,'006','2015-10-05',NULL,'Engagement','documents/decisions/1696500246.pdf',2015,1675,502500,1,7,4,97,'2023-10-05 10:04:06','2023-10-05 10:04:06'),(21,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696500630.pdf',2017,1600,480000,1,8,1,77,'2023-10-05 10:10:30','2023-10-05 10:10:30'),(22,'098','1999-09-15',NULL,'Engagement','documents/decisions/1696500704.pdf',1999,1050,315000,1,6,1,102,'2023-10-05 10:11:44','2023-10-05 10:11:44'),(23,'006','1987-07-16',NULL,'Engagement','documents/decisions/1696500860.pdf',1987,1360,408000,1,7,1,103,'2023-10-05 10:14:20','2023-10-05 10:14:20'),(24,'009/ARTF-PMFRHL-DLTCA-DRH-SP du 04/02/2020','2020-02-04',NULL,'Engagement','documents/decisions/1696501721.pdf',2020,970,291000,1,5,1,104,'2023-10-05 10:28:41','2023-10-05 10:28:41'),(25,'032/ARTF-DRHL-SLTCA-SP DU 28/12/2020','2020-05-09',NULL,'Engagement','',2020,1050,315000,1,6,1,106,'2023-10-05 10:34:24','2023-10-06 13:40:13'),(26,'013','2000-03-23',NULL,'Engagement','documents/decisions/1696502153.pdf',2000,970,291000,1,5,1,15,'2023-10-05 10:35:53','2023-10-06 14:50:07'),(27,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696502882.pdf',2017,1360,408000,1,7,1,109,'2023-10-05 10:48:02','2023-10-05 10:48:02'),(28,'098/DGCRF-DAP-SP du 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1696502917.pdf',1999,1050,315000,1,6,1,27,'2023-10-05 10:48:37','2023-10-05 10:48:37'),(29,'006','2017-07-18',NULL,'Engagement','documents/decisions/1696503032.pdf',2017,1360,408000,1,7,1,107,'2023-10-05 10:50:32','2023-10-05 10:50:32'),(30,'011/ARTF DU 02/12/2014','2014-12-02',NULL,'Engagement','documents/decisions/1696503471.pdf',2014,970,291000,1,5,1,110,'2023-10-05 10:57:51','2023-10-05 10:57:51'),(31,'0012/DGCRF-DAP-SRH.','2003-01-07',NULL,'Engagement','documents/decisions/1696503735.pdf',2003,1360,408000,1,7,1,31,'2023-10-05 11:02:15','2023-10-05 15:04:35'),(32,'001','2017-12-19',NULL,'Engagement','documents/decisions/1696503882.pdf',2017,1600,480000,1,8,1,111,'2023-10-05 11:04:42','2023-10-05 13:13:35'),(33,'015','2021-02-12',NULL,'Engagement','documents/decisions/1696504108.pdf',2021,490,147000,1,1,1,108,'2023-10-05 11:08:28','2023-10-05 11:08:28'),(34,'098/DGRF-DAP-SPF du 27/12/1990','1990-09-15',NULL,'Engagement','documents/decisions/1696504377.pdf',1990,1600,480000,1,8,1,5,'2023-10-05 11:12:57','2023-10-05 11:12:57'),(35,'006/ARTF DU 23/01/2017','2017-04-18',NULL,'Engagement','',2017,1050,315000,1,6,1,112,'2023-10-05 11:13:12','2023-10-05 11:13:12'),(36,'006','2017-04-18',NULL,'Engagement','documents/decisions/1696504909.pdf',2017,1050,315000,1,6,1,114,'2023-10-05 11:21:49','2023-10-05 11:21:49'),(37,'0010','2017-09-19',NULL,'Engagement','documents/decisions/1696504987.pdf',2017,1050,315000,1,6,1,113,'2023-10-05 11:23:07','2023-10-06 14:56:08'),(38,'N°098','1999-09-15',NULL,'Engagement','documents/decisions/1696505367.pdf',1999,1050,315000,1,6,1,70,'2023-10-05 11:29:27','2023-10-05 13:14:59'),(39,'015','2021-02-12',NULL,'Engagement','documents/decisions/1696505545.pdf',2021,490,147000,1,1,1,115,'2023-10-05 11:32:25','2023-10-05 11:32:25'),(40,'4745/MFPRE/DGFP/DGEPE/SP DU 07/04/2014','2015-10-09',NULL,'Engagement','',2015,490,147000,1,1,1,116,'2023-10-05 11:34:36','2023-10-05 11:34:36'),(41,'0010','2017-04-18',NULL,'Engagement','documents/decisions/1696505933.pdf',2017,1360,408000,1,7,1,117,'2023-10-05 11:38:53','2023-10-06 14:55:40'),(42,'098','1999-09-15',NULL,'Engagement','documents/decisions/1696506850.pdf',1999,1050,315000,1,6,1,119,'2023-10-05 11:54:10','2023-10-05 11:54:10'),(43,'','2017-04-18',NULL,'Engagement','',2017,1360,408000,1,7,1,120,'2023-10-05 12:07:56','2023-10-06 14:54:26'),(44,'015','2021-02-12',NULL,'Engagement','documents/decisions/1696508069.pdf',2021,490,147000,1,1,1,121,'2023-10-05 12:14:29','2023-10-05 12:14:29'),(45,'','2020-02-01',NULL,'Engagement','',2020,1600,480000,1,8,1,123,'2023-10-05 12:14:34','2023-10-05 12:14:34'),(46,'098/DGCRF-DAP-SPF','1999-09-15',NULL,'Engagement','documents/decisions/1696508105.pdf',1999,970,291000,1,5,1,122,'2023-10-05 12:15:05','2023-10-05 12:15:05'),(47,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696508724.pdf',2017,1360,408000,1,7,1,42,'2023-10-05 12:25:24','2023-10-05 12:25:24'),(48,'0010','2017-04-18',NULL,'Engagement','',2017,1600,480000,1,8,1,125,'2023-10-05 12:31:14','2023-10-05 12:31:14'),(49,'020/ARTF-PMFRHL-DLTCA-DRH-SP du10/03/2020','2019-12-02',NULL,'Engagement','documents/decisions/1696509794.pdf',2019,1360,408000,1,7,1,127,'2023-10-05 12:43:14','2023-10-05 12:43:14'),(50,'0070','2016-10-25',NULL,'Engagement','documents/decisions/1696509817.pdf',2016,1360,408000,1,7,1,39,'2023-10-05 12:43:37','2023-10-05 12:43:37'),(51,'098','2000-03-23',NULL,'Engagement','documents/decisions/1696512821.pdf',2000,1600,480000,1,8,1,130,'2023-10-05 13:33:41','2023-10-05 13:33:41'),(52,'N°98','1999-10-15',NULL,'Engagement','',1999,1360,408000,1,7,1,132,'2023-10-05 13:34:08','2023-10-05 13:34:08'),(53,'098','1999-12-27',NULL,'Engagement','documents/decisions/1696513890.pdf',1999,1780,534000,1,7,5,96,'2023-10-05 13:51:30','2023-10-05 13:51:30'),(54,'0010','2017-10-27',NULL,'Engagement','documents/decisions/1697195844.pdf',2017,2180,654000,1,9,1,29,'2023-10-05 13:54:53','2023-10-13 11:17:24'),(55,'006','2017-04-18',NULL,'Engagement','documents/decisions/1696514483.pdf',2017,970,291000,1,5,1,95,'2023-10-05 14:01:23','2023-10-05 14:01:23'),(56,'N°006','2017-04-18',NULL,'Engagement','documents/decisions/1696515844.pdf',2017,970,291000,1,5,1,94,'2023-10-05 14:24:04','2023-10-05 14:24:04'),(57,'070','2016-07-25',NULL,'Engagement','documents/decisions/1696516335.pdf',2016,1360,408000,1,7,1,93,'2023-10-05 14:32:15','2023-10-05 14:32:15'),(58,'098.','2017-04-18',NULL,'Engagement','documents/decisions/1696516951.pdf',2017,1600,480000,1,8,1,92,'2023-10-05 14:42:31','2023-10-05 14:42:31'),(59,'010/ARTF/PMFRHL-DRH-SP.','2017-04-18',NULL,'Engagement','documents/decisions/1696519290.pdf',2017,1360,408000,1,7,1,26,'2023-10-05 15:21:30','2023-10-05 15:21:30'),(60,'','2017-04-18',NULL,'Engagement','',2017,1600,480000,1,8,1,32,'2023-10-06 08:04:54','2023-10-06 08:04:54'),(61,'','2017-07-18',NULL,'Engagement','documents/decisions/1696584340.pdf',2017,1360,408000,1,7,1,33,'2023-10-06 09:25:40','2023-10-06 09:25:40'),(62,'','1999-09-15',NULL,'Engagement','',1999,1050,315000,1,6,1,134,'2023-10-06 09:45:50','2023-10-06 09:45:50'),(63,'N°006','2017-04-18',NULL,'Engagement','documents/decisions/1696586578.pdf',2017,1360,408000,1,7,1,118,'2023-10-06 10:02:58','2023-10-06 10:02:58'),(64,'','2020-08-03',NULL,'Engagement','',2020,490,147000,1,1,1,136,'2023-10-06 10:17:34','2023-10-06 10:17:34'),(65,'098','1999-09-15',NULL,'Engagement','documents/decisions/1696587490.pdf',1999,1050,315000,1,6,1,12,'2023-10-06 10:18:10','2023-10-06 10:18:10'),(66,'010/ARTF-PMFRHL-DRH-SP.','2017-04-18',NULL,'Engagement','',2017,1050,315000,1,6,1,135,'2023-10-06 10:32:02','2023-10-06 10:32:02'),(67,'098/DGCRF-DAP-SPF du 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1696591259.pdf',1999,1360,408000,1,7,1,138,'2023-10-06 11:20:59','2023-10-06 11:20:59'),(68,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696592491.pdf',2017,1360,408000,1,7,1,76,'2023-10-06 11:41:31','2023-10-06 11:41:31'),(69,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696597925.pdf',2017,1600,480000,1,8,1,54,'2023-10-06 13:12:05','2023-10-06 13:12:05'),(70,'006/ARTF du 23/01/2017','2015-10-03',NULL,'Engagement','documents/decisions/1696598777.pdf',2015,1600,480000,1,8,1,7,'2023-10-06 13:26:17','2023-10-06 13:26:17'),(71,'006/ARTF','2017-04-18',NULL,'Engagement','documents/decisions/1696600476.pdf',2017,1360,408000,1,7,1,58,'2023-10-06 13:54:36','2023-10-06 13:54:36'),(72,'0010/ARTF-PMFRHL-DRH-SP','2017-04-18',NULL,'Engagement','documents/decisions/1696600630.pdf',2017,1600,480000,1,8,1,141,'2023-10-06 13:57:10','2023-10-06 13:57:10'),(73,'098/DGCRF-DAP-SPF do 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1696603709.pdf',1999,1050,315000,1,6,1,142,'2023-10-06 14:06:11','2023-10-06 14:48:29'),(74,'0010/ARTF-PMFRHL-DRH-SP','2017-04-18',NULL,'Engagement','',2017,1600,480000,1,8,1,140,'2023-10-06 14:07:22','2023-10-16 13:55:13'),(75,'006/ARTF','2017-04-18',NULL,'Engagement','documents/decisions/1696601451.pdf',2017,1600,480000,1,8,1,53,'2023-10-06 14:10:51','2023-10-06 14:10:51'),(76,'Décision n°0010/ARTF-PMFRHL-DRH-SP','2015-07-22',NULL,'Engagement','documents/decisions/1696602225.pdf',2015,1600,480000,1,8,1,47,'2023-10-06 14:23:45','2023-10-06 14:23:45'),(77,'0070/ARTF-PMFRHL-DRH-SP','2016-10-25',NULL,'Engagement','documents/decisions/1696602863.pdf',2016,1360,408000,1,7,1,133,'2023-10-06 14:24:35','2023-10-06 14:34:23'),(78,'006/ARTF du 23/01/2017','2015-10-03',NULL,'Engagement','documents/decisions/1696602538.pdf',2015,1600,480000,1,8,1,67,'2023-10-06 14:28:58','2023-10-06 14:28:58'),(79,'025/DGCRF-DAP-SRH','2002-04-22',NULL,'Engagement','documents/decisions/1696604090.pdf',2002,880,264000,1,4,2,13,'2023-10-06 14:54:50','2023-10-06 14:54:50'),(80,'Décision n°013/DGCRF-DAP-SPF du 12 juillet 2000','1999-09-15',NULL,'Engagement','documents/decisions/1696604701.pdf',1999,1360,408000,1,7,1,4,'2023-10-06 15:05:01','2023-10-06 15:05:01'),(81,'012','2017-04-18',NULL,'Engagement','documents/decisions/1696605339.pdf',2017,1360,408000,1,7,1,143,'2023-10-06 15:15:39','2023-10-06 15:15:39'),(82,'Décision n°0010/ARTF-PMFRHM-DRH-SP du 27 octobre 2017','2017-04-18',NULL,'Engagement','documents/decisions/1696605875.pdf',2017,1600,480000,1,8,1,61,'2023-10-06 15:24:35','2023-10-06 15:24:35'),(83,'012 ','2017-04-18',NULL,'Engagement','documents/decisions/1696607139.pdf',2017,1360,408000,1,7,1,144,'2023-10-06 15:45:39','2023-10-06 15:45:39'),(84,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696844569.pdf',2017,970,291000,1,5,1,146,'2023-10-09 09:42:49','2023-10-09 09:42:49'),(85,'010','2017-01-23',NULL,'Engagement','documents/decisions/1696845591.pdf',2017,1360,408000,1,7,1,91,'2023-10-09 09:59:51','2023-10-09 09:59:51'),(86,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1696845661.pdf',2017,700,210000,1,3,1,147,'2023-10-09 10:01:01','2023-10-09 10:01:01'),(87,'010','2017-04-18',NULL,'Engagement','',2017,820,246000,1,4,1,90,'2023-10-09 10:05:43','2023-10-09 10:05:43'),(88,'098/DGCRF-DAF-SPF du27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1696848636.pdf',1999,970,291000,1,5,1,148,'2023-10-09 10:50:36','2023-10-09 10:50:36'),(89,'098/DGCERF-DAP-SPF DU27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1696855910.pdf',1999,1360,408000,1,7,1,149,'2023-10-09 12:51:50','2023-10-09 12:51:50'),(90,'076/artf-drhl-sltca-sp du 06/07/2020','2020-07-06',NULL,'Engagement','',2020,1360,408000,1,7,1,150,'2023-10-09 13:09:32','2023-10-09 13:09:32'),(91,'006/ARTF DU 23/01/2017','2017-04-18',NULL,'Engagement','',2017,1360,408000,1,7,1,151,'2023-10-09 13:27:06','2023-10-09 13:27:06'),(92,'Décision n°0010/ARTF-PMFRHL-DRH-SP','2017-04-18',NULL,'Engagement','documents/decisions/1696860010.pdf',2017,1050,315000,1,6,1,60,'2023-10-09 14:00:10','2023-10-09 14:00:10'),(93,'Note de service n°0070/ARTF-PMFRHL-DRH-SP','2017-08-25',NULL,'Engagement','documents/decisions/1696861765.pdf',2017,1360,408000,1,7,1,20,'2023-10-09 14:15:09','2023-10-09 14:29:25'),(94,'006','2017-04-18',NULL,'Engagement','documents/decisions/1696863833.pdf',2017,1360,408000,1,7,1,66,'2023-10-09 15:03:53','2023-10-09 15:03:53'),(95,'010','2017-04-18',NULL,'Engagement','',2017,1600,480000,1,8,1,145,'2023-10-09 15:22:46','2023-10-09 15:22:46'),(96,'010','2017-04-18',NULL,'Engagement','',2017,1360,408000,1,7,1,80,'2023-10-09 15:36:24','2023-10-09 15:36:24'),(97,'010','2017-04-18',NULL,'Engagement','',2017,1050,315000,1,6,1,81,'2023-10-09 15:40:43','2023-10-09 15:40:43'),(98,'010','2017-04-18',NULL,'Engagement','',2017,1360,408000,1,7,1,84,'2023-10-09 15:55:17','2023-10-09 15:55:17'),(99,'Décision n°013/DGCRF-DAP-SPF du 12 juillet 2000','1999-09-15',NULL,'Engagement','documents/decisions/1696943434.pdf',1999,970,291000,1,5,1,46,'2023-10-10 13:10:34','2023-10-10 13:10:34'),(100,'010','2017-04-18',NULL,'Engagement','documents/decisions/1696954097.pdf',2017,1360,408000,1,7,1,85,'2023-10-10 16:08:17','2023-10-10 16:08:17'),(101,'010','2017-04-18',NULL,'Engagement','',2017,1600,480000,1,8,1,86,'2023-10-10 16:17:50','2023-10-10 16:17:50'),(102,'010','2017-04-18',NULL,'Engagement','',2017,1050,315000,1,6,1,87,'2023-10-10 16:22:41','2023-10-10 16:22:41'),(103,'098','1999-09-15',NULL,'Engagement','documents/decisions/1696955166.pdf',1999,1360,408000,1,7,1,88,'2023-10-10 16:26:06','2023-10-10 16:26:06'),(104,'010','2015-10-03',NULL,'Engagement','',2015,1050,315000,1,6,1,89,'2023-10-10 16:31:03','2023-10-10 16:31:03'),(105,'098/DGCRF-DAP-SPF','1999-09-15',NULL,'Engagement','documents/decisions/1697036629.pdf',1999,1050,315000,1,6,1,152,'2023-10-11 08:21:45','2023-10-12 14:13:17'),(106,'','1980-11-11',NULL,'Engagement','',1980,535,160500,1,1,2,152,'2023-10-11 08:23:31','2023-10-11 08:23:31'),(107,'A','2017-04-18',NULL,'Engagement','documents/decisions/1697118108.pdf',2017,1050,315000,1,6,1,154,'2023-10-12 13:41:48','2023-10-12 13:41:48'),(108,'006/ARTF du 23-1-2017','2017-01-23',NULL,'Engagement','documents/decisions/1697118717.pdf',2017,1050,315000,1,6,1,155,'2023-10-12 13:51:57','2023-10-12 13:51:57'),(109,'006/artf du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1697196771.pdf',2017,1600,480000,1,8,1,156,'2023-10-13 11:32:51','2023-10-18 13:07:21'),(110,'098/DGRCR-DAP-SPF du 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1697203626.pdf',1999,970,291000,1,5,1,65,'2023-10-13 13:24:03','2023-10-13 13:27:06'),(111,'098/DGCRF-DAP-SPF du 27-12-1999','1999-09-15',NULL,'Engagement','documents/decisions/1697550459.pdf',1999,1360,408000,1,7,1,153,'2023-10-17 13:47:39','2023-10-17 13:47:39'),(112,'098/DGCRF-DAP-SPF du 27-12-1999','1999-09-15',NULL,'Engagement','documents/decisions/1697620406.pdf',1999,1050,315000,1,6,1,157,'2023-10-18 08:43:35','2023-10-18 09:13:26'),(113,'098DGCRF-DAP-SPF du 27-12-1999','1999-06-15',NULL,'Engagement','documents/decisions/1697621571.pdf',1999,970,291000,1,5,1,158,'2023-10-18 09:32:51','2023-10-18 09:32:51'),(114,'098/DGCRF-DAP-SPF du 27-12-1999','1999-09-15',NULL,'Engagement','documents/decisions/1697634754.pdf',1999,970,291000,1,5,1,159,'2023-10-18 13:12:34','2023-10-18 13:12:34'),(115,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1697636328.pdf',2017,1360,408000,1,7,1,160,'2023-10-18 13:38:48','2023-10-18 13:38:48'),(116,'006/ARTF du 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1697701023.pdf',2017,1360,408000,1,7,1,161,'2023-10-18 14:00:59','2023-10-19 07:37:03'),(117,'098/DGCRF-DAP','1999-12-27',NULL,'Engagement','documents/decisions/1697803987.pdf',1999,970,291000,1,5,1,162,'2023-10-20 12:13:07','2023-10-20 12:13:07'),(118,'098/DGCRF-DAP-SPF du 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1698058011.pdf',1999,1360,408000,1,7,1,28,'2023-10-23 10:46:51','2023-10-23 10:46:51'),(119,'006ARTF DU 23/01/2017','2017-04-18',NULL,'Engagement','',2017,1360,408000,1,7,1,36,'2023-10-26 08:02:23','2023-10-26 08:02:23'),(120,'0010','2017-04-18',NULL,'Engagement','documents/decisions/1698309807.pdf',2017,1600,480000,1,8,1,25,'2023-10-26 08:43:27','2023-10-26 08:43:27'),(121,'076/ARTF-DRHL-SLTCA-SRH-BP DU 06/07/2020','2020-07-06',NULL,'Engagement','',2020,1360,408000,1,7,1,38,'2023-10-31 09:36:49','2023-10-31 09:36:49'),(122,'098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1698746384.pdf',1999,1360,408000,1,7,1,18,'2023-10-31 09:59:44','2023-10-31 09:59:44'),(123,'0006/ARTF DU 23/01/2017','2017-04-18',NULL,'Engagement','documents/decisions/1698748009.pdf',2017,1360,408000,1,7,1,163,'2023-10-31 10:21:56','2023-10-31 10:26:49'),(124,'098/DGCRF DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1698748940.pdf',1999,1050,315000,1,6,1,21,'2023-10-31 10:37:34','2023-10-31 10:42:20'),(125,'098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1698997435.pdf',1999,1360,408000,1,7,1,164,'2023-11-03 07:37:55','2023-11-03 07:43:55'),(126,'098/DGCRF-DAP-DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699006404.pdf',1999,1600,480000,1,8,1,165,'2023-11-03 10:12:22','2023-11-03 10:13:24'),(127,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699007058.pdf',1999,1360,408000,1,7,1,166,'2023-11-03 10:23:55','2023-11-03 10:24:18'),(128,'0098/DGCRF-DAP-DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699016467.pdf',1999,1360,408000,1,7,1,167,'2023-11-03 13:01:07','2023-11-03 13:01:07'),(129,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699255323.pdf',1999,970,291000,1,5,1,170,'2023-11-06 07:16:28','2023-11-06 07:22:03'),(130,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699255712.pdf',1999,1360,408000,1,7,1,172,'2023-11-06 07:28:32','2023-11-06 07:28:32'),(131,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699256108.pdf',1999,490,147000,1,1,1,173,'2023-11-06 07:35:08','2023-11-06 07:35:08'),(132,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','',1999,1360,408000,1,7,1,174,'2023-11-06 07:43:27','2023-11-06 07:43:27'),(133,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699257077.pdf',1999,1050,315000,1,6,1,175,'2023-11-06 07:51:17','2023-11-06 07:51:17'),(134,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699257574.pdf',1999,1360,408000,1,7,1,176,'2023-11-06 07:59:34','2023-11-06 07:59:34'),(135,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','',1999,1360,408000,1,7,1,177,'2023-11-06 08:30:24','2023-11-06 08:30:24'),(136,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699259896.pdf',1999,1600,480000,1,8,1,178,'2023-11-06 08:38:16','2023-11-06 08:38:16'),(137,'00098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699261245.pdf',1999,970,291000,1,5,1,180,'2023-11-06 09:00:45','2023-11-06 09:00:45'),(138,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699265966.pdf',1999,1360,408000,1,7,1,181,'2023-11-06 10:19:26','2023-11-06 10:19:26'),(139,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699266489.pdf',1999,970,291000,1,5,1,182,'2023-11-06 10:28:09','2023-11-06 10:28:09'),(140,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699266780.pdf',1999,1360,408000,1,7,1,183,'2023-11-06 10:33:00','2023-11-06 10:33:00'),(141,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699267142.pdf',1999,970,291000,1,5,1,184,'2023-11-06 10:39:02','2023-11-06 10:39:02'),(142,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','',1999,1050,315000,1,6,1,185,'2023-11-06 10:49:22','2023-11-06 10:49:22'),(143,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','',1999,970,291000,1,5,1,186,'2023-11-06 10:55:53','2023-11-06 10:55:53'),(144,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699339542.pdf',1999,970,291000,1,5,1,187,'2023-11-07 06:45:42','2023-11-07 06:45:42'),(145,'0098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699340077.pdf',1999,1050,315000,1,6,1,188,'2023-11-07 06:54:37','2023-11-07 06:54:37'),(146,'0098/DGCRF-DAP DU 27/12/19999','1999-09-15',NULL,'Engagement','documents/decisions/1699340375.pdf',1999,970,291000,1,5,1,189,'2023-11-07 06:59:35','2023-11-07 06:59:35'),(147,'00098/DGCRF-DAP DU 27/12/1999','1999-09-15',NULL,'Engagement','documents/decisions/1699340778.pdf',1999,1360,408000,1,7,1,190,'2023-11-07 07:06:18','2023-11-07 07:06:18'),(148,'053 DGCRF/DAP','1993-05-05',NULL,'Engagement','documents/decisions/1716385083.pdf',1993,490,147000,1,1,1,10,'2024-05-22 13:38:03','2024-05-22 13:38:03'),(149,'013/DGCRF','1999-09-15',NULL,'Engagement','documents/decisions/1716387681.pdf',1999,1360,408000,1,7,1,59,'2024-05-22 14:21:21','2024-05-22 14:21:21');
/*!40000 ALTER TABLE `textes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `type_notes`
--

DROP TABLE IF EXISTS `type_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `type_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelleType` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `type_notes`
--

LOCK TABLES `type_notes` WRITE;
/*!40000 ALTER TABLE `type_notes` DISABLE KEYS */;
INSERT INTO `type_notes` VALUES (1,'Attestation de présence au poste',NULL,NULL),(2,'Ordre de mission',NULL,NULL),(3,'Mission de service',NULL,NULL),(4,'Note de service',NULL,NULL),(5,'Note de retention',NULL,NULL),(6,'Autorisation d\'absence',NULL,NULL);
/*!40000 ALTER TABLE `type_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `typeactivites`
--

DROP TABLE IF EXISTS `typeactivites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `typeactivites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `typeactivites`
--

LOCK TABLES `typeactivites` WRITE;
/*!40000 ALTER TABLE `typeactivites` DISABLE KEYS */;
INSERT INTO `typeactivites` VALUES (1,'Mission',NULL,NULL),(2,'Commission',NULL,NULL),(3,'Réunion',NULL,NULL),(4,'Formation',NULL,NULL),(5,'Visio conférence',NULL,NULL),(6,'Célébration',NULL,NULL);
/*!40000 ALTER TABLE `typeactivites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `typedemandeabsences`
--

DROP TABLE IF EXISTS `typedemandeabsences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `typedemandeabsences` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `libelle` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `typedemandeabsences`
--

LOCK TABLES `typedemandeabsences` WRITE;
/*!40000 ALTER TABLE `typedemandeabsences` DISABLE KEYS */;
INSERT INTO `typedemandeabsences` VALUES (1,'ABSENCES CONVENTIONNELLE',NULL,NULL),(2,'ABSENCES NON CONVENTIONNELLE',NULL,NULL);
/*!40000 ALTER TABLE `typedemandeabsences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `matricule` varchar(255) DEFAULT NULL,
  `sexe` char(255) NOT NULL,
  `dateNaissance` date DEFAULT NULL,
  `lieuNaissance` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `telephone1` varchar(255) NOT NULL,
  `telephone2` varchar(255) DEFAULT NULL,
  `situationMatrimoniale` varchar(255) DEFAULT NULL,
  `photoAgent` varchar(255) DEFAULT NULL,
  `fonction_id` bigint(20) unsigned NOT NULL,
  `statut` varchar(255) DEFAULT NULL,
  `structureable_type` varchar(255) NOT NULL,
  `structureable_id` bigint(20) unsigned NOT NULL,
  `nomContact` varchar(255) DEFAULT NULL,
  `prenomContact` varchar(255) DEFAULT NULL,
  `emailContact` varchar(255) DEFAULT NULL,
  `telephoneContact` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_telephone1_unique` (`telephone1`),
  UNIQUE KEY `users_emailcontact_unique` (`emailContact`),
  UNIQUE KEY `users_telephonecontact_unique` (`telephoneContact`),
  KEY `users_fonction_id_foreign` (`fonction_id`),
  KEY `users_structureable_type_structureable_id_index` (`structureable_type`,`structureable_id`),
  CONSTRAINT `users_fonction_id_foreign` FOREIGN KEY (`fonction_id`) REFERENCES `fonctions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'BAZEBI','Jean Claude Basile',NULL,'M','1986-03-04','1001 Dominique Road\nPort Casimer, MS 46846-2029','jeanclaude.bazebi@artf.cg','773 Hortense River Apt. 063\nEast Jody, SD 48180-9037','838.678.9592','+1-712-415-8496',NULL,NULL,5,NULL,'App\\Models\\Direction',1,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','pSUKquD8eQ','2023-10-04 14:27:03','2023-10-04 14:27:03'),(2,'MAVANGA','JOËL',NULL,'M','1983-07-15','410 Coralie Junction\nPort Karson, WY 94292','joel.mavanga@artf.cg','3487 Schulist Path Apt. 268\nYundthaven, SD 87837-8636','714-295-0190','(629) 921-2342',NULL,NULL,1,NULL,'App\\Models\\Direction',1,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','9LKYaHbvE7','2023-10-04 14:27:03','2023-10-04 14:27:03'),(3,'AKOUALA','NUPTIA',NULL,'F','1990-03-10','755 Gerardo Common\nGibsontown, ND 88468-8240','nuptia.akouala@artf.cg','328 Nitzsche Valleys\nSchultzfurt, MS 63036','(470) 754-2810','540-727-6788',NULL,NULL,4,NULL,'App\\Models\\Direction',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','DzdUDzhEUP','2023-10-04 14:27:03','2023-10-04 14:27:03'),(4,'MILANDOU','VIRGINIE',NULL,'F','1971-02-20','Brazzaville','virginie.milandou@artf.cg','133 Okuneva Squares\nSouth Adalineside, NM 60123','628-835-7348','(240) 315-4763','Célibataire',NULL,1,NULL,'App\\Models\\Direction',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','AsB508wBZX','2023-10-04 14:27:03','2023-10-06 15:05:01'),(5,'MBOUBI NGAMI','Roch AVIT',NULL,'M','1970-11-16','BZV','avit.mboubi@artf.cg','63074 Doris Forge Apt. 100\nSouth Reinholdchester, ID 85187','(475) 729-2312','+1.385.796.4021','Marié(e)',NULL,4,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','1Ebpak5SpB','2023-10-04 14:27:03','2023-10-10 14:06:51'),(6,'BASSOKA','OLGA',NULL,'F','1991-08-24','33695 Russel Meadow Suite 955\nRempelport, MD 08908','olga.bassoka@artf.cg','847 Antwon Brook\nWest Alda, MN 12842-6371','+1 (716) 960-0637','+1 (940) 236-9702',NULL,NULL,1,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','bRoewyu1VJ','2023-10-04 14:27:03','2023-10-04 14:27:03'),(7,'LALEYE MAZABA','Sandra Dominique Kelly',NULL,'F','1990-04-22','BZV','sandra.laleye@artf.cg','29, Rue Owando/Talangaï','06 959-00-34','678-994-2265','Célibataire',NULL,1,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','cTJBLXBsUW','2023-10-04 14:27:03','2023-10-23 11:07:53'),(8,'EPENIT KAZABAND','Rostand Evence Marcel',NULL,'M','1969-03-04','Boundji','rostand.epenit@artf.cg','248 Angelo Valleys\nMcGlynnburgh, MN 94539','234.421.9329','(984) 597-3762','Célibataire',NULL,4,NULL,'App\\Models\\Direction',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','K5CqZgAMmE','2023-10-04 14:27:03','2023-10-04 15:54:43'),(9,'MIKANOU','GISELE SPRING RENATE',NULL,'F','1990-09-04','Pointe-Noire','gisele.mikanou@artf.cg','2145 Kennith Via\nDooleyborough, LA 04946','229.970.8232','276-410-5397','Célibataire',NULL,1,NULL,'App\\Models\\Direction',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','q1BcXYrxm6Y0lGS9c7xdMlEKI2uBHMY4pQ5lp6AK7W2YJzXQZVGNSDlFDtWW','2023-10-04 14:27:03','2023-10-04 16:11:51'),(10,'NDAMBA','BARTHELEMY',NULL,'M','1968-07-14','TALA NKOYI MFOUATI','barth.ndamba@artf.cg','56269 Shields Island\nLavonnefort, MO 86546-2477','678.989.6961','+1-424-351-2467','Marié(e)','1716385121.png',1,NULL,'App\\Models\\Direction',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','RXT07uOKBm','2023-10-04 14:27:03','2024-05-22 13:38:41'),(11,'OYOUA','PAPY',NULL,'M','1984-05-18','37606 Jerod Port\nSouth Gabrielshire, AR 44567','papy.oyoua@artf.cg','3209 Taylor Mountain\nMorissettemouth, NC 11042','281-627-5057','678.950.7692',NULL,NULL,1,NULL,'App\\Models\\Direction',5,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','BWkFFXdBr9','2023-10-04 14:27:03','2023-10-04 14:27:03'),(12,'SAMBA','Esther Blanche ',NULL,'F','1972-08-22','Mossaka ','esther.samba@artf.cg','795 Edwina Coves Apt. 339\nAdamburgh, AZ 74497-0614','+1-816-513-7953','+1 (530) 244-5883','Célibataire',NULL,2,NULL,'App\\Models\\Direction',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','xeaHcaynOV','2023-10-04 14:27:03','2023-10-06 10:18:10'),(13,'NGOMA','PHILIPPE',NULL,'M','1967-04-15','8399 Shanahan Brook Suite 974\nMaryjaneland, MI 62632-5540','philippe.ngoma@artf.cg','91725 Kilback Prairie\nSouth Kole, DC 36311','+1-570-284-3502','+1 (480) 539-0881','Veuf(ve)',NULL,1,NULL,'App\\Models\\Direction',6,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','xlMtntimYd','2023-10-04 14:27:03','2023-10-06 14:54:50'),(15,'BANIAKINA BIBELO','Solange Judith',NULL,'F','1972-07-07','Brazzaville','solange.baniakina@artf.cg','2378 Annalise Course Apt. 871\nWest Cesarview, LA 99224','(415) 423-8671','1-347-281-6254','Célibataire',NULL,2,NULL,'App\\Models\\Direction',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','kwuhLm4xhJ','2023-10-04 14:27:03','2023-10-05 10:35:53'),(16,'BALOSSA','ASTRIDE',NULL,'F','1980-01-26','Brazzaville','astride.balossa@artf.cg','128 Marvin Fields Apt. 424\nBradentown, GA 54395','+1-424-982-7894','(936) 666-2674','Marié(e)',NULL,3,NULL,'App\\Models\\Direction',7,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','Tkgvak6JyV','2023-10-04 14:27:03','2023-10-12 13:32:38'),(17,'KASSA','FATIMA',NULL,'F','1989-09-22','Loubomo','fatima.kassa@artf.cg','59 rue maman MBAYA/Massengo ','06 971 00 30','05 6375795','Célibataire',NULL,1,NULL,'App\\Models\\Direction',7,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','UlGmH2adgT','2023-10-04 14:27:03','2023-10-05 09:12:01'),(18,'DIAOUA BOUESSO','FLORA NADINE',NULL,'F','1974-12-13','POINTE-NOIRE','flora.diaoua@artf.cg','67 Bis rue NKOUKA-BATEKE','06 654 14 83','05 579 39 21','Célibataire','1715076113.jpg',3,NULL,'App\\Models\\Service',1,NULL,NULL,NULL,NULL,'$2y$10$lhOOLlKi5VRUCRoqI.yaQ.L.GH76n2RD0Sxr6NLWDFoCp8GoFLjRO','2023-10-04 14:27:03','6bZVr4equ1dMDQyNbmMSvNquSMJMSEbqxEaptpFKClZEcUR4pDoS1yfjAwvX','2023-10-04 14:27:03','2024-05-07 10:01:53'),(19,'OBAMBI','AIMÉE',NULL,'F','1969-01-11','Brazzaville','aimee.obambi@artf.cg','makambandilou ','06 615 03 68','06 615 03 68','Célibataire',NULL,3,NULL,'App\\Models\\Service',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','uDqvLHldQU','2023-10-04 14:27:03','2024-05-22 12:27:15'),(20,'NGUIE','Augustin Anderson',NULL,'M','1989-06-05','Brazzaville','augustin.nguie@artf.cg','88 bis rue Malima, Ouenze','0666637087','920-930-4841','Célibataire',NULL,2,NULL,'App\\Models\\Service',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','zly0c2ecGp','2023-10-04 14:27:03','2023-10-31 08:52:00'),(21,'MAYIKA','VIRGINIE',NULL,'F','1998-10-18','148 Balistreri Springs Suite 647\nNew Darryl, MN 87762-6869','virginie.mayika@artf.cg','42187 Mattie Road Suite 677\nMacifort, MD 62584-7607','1-925-934-7362','+15083141615','Célibataire',NULL,1,NULL,'App\\Models\\Service',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','WkAGf28GFM','2023-10-04 14:27:03','2023-10-31 10:37:34'),(22,'AYESSA','NEMIE',NULL,'F','1986-09-08','338 Nayeli Parkways\nNorth Myrnashire, OR 23787','nemie.ayessa@artf.cg','15234 Altenwerth Street Suite 869\nKuvalisport, MI 92857-7215','+1.702.723.1512','747.640.8031',NULL,NULL,1,NULL,'App\\Models\\Service',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','mLE0t9OAmH','2023-10-04 14:27:03','2023-10-04 14:27:03'),(23,'DANDOU','SIASSIA',NULL,'M','1980-05-29','4655 Doyle Gateway Apt. 491\nAmberville, MO 34731-8001','siassia.dandou@artf.cg','88947 Judge Prairie Suite 299\nWest Raphaelton, GA 80690','484-848-3787','706-348-5205',NULL,NULL,1,NULL,'App\\Models\\Service',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','5ce7QHPovH','2023-10-04 14:27:03','2023-10-04 14:27:03'),(24,'ELION','William Justice Malov',NULL,'M','1983-04-23','Gamboma','elion.justice@artf.cg','4255 Jenkins Drive\nLake Laishabury, TX 76889-3585','(617) 826-6393','+1.408.893.9170','Marié(e)',NULL,1,NULL,'App\\Models\\Service',5,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','VYw0rUnOqZ','2023-10-04 14:27:03','2023-10-04 15:41:00'),(25,'DIMI','ASTRIDE',NULL,'F','1979-09-27','Pointe-Noire','astride.dimi@artf.cg','2107 Russell Lake\nSouth Jade, PA 36546','415.450.3097','520-821-9407','Marié(e)',NULL,3,NULL,'App\\Models\\Service',6,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','dLlYF3yNXM','2023-10-04 14:27:03','2023-10-26 08:43:27'),(26,'ONDON','TONY',NULL,'M','1989-12-23','Brazzaville','tony.ondon@artf.cg','927 Kreiger Mountains Apt. 584\nEast Athenamouth, KS 51990','(346) 893-1013','+1-848-751-8337','Marié(e)',NULL,1,NULL,'App\\Models\\Service',7,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','zRNnsuStNl','2023-10-04 14:27:03','2023-10-05 15:21:30'),(27,'BITSENE','Joseph',NULL,'M','1971-09-29','Mbaya','joseph.bitsene@artf.cg','39, Rue Likouala/Poto-Poto/BZV','321.704.1436','662-955-6619','Célibataire',NULL,1,NULL,'App\\Models\\Service',8,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','EhFGEMZZBh','2023-10-04 14:27:03','2023-10-23 10:59:00'),(28,'MPOAMPION','Marina Sylvie',NULL,'F','1976-12-09','BZV','marina.mpoampion@artf.cg','114, Rue loualou CNSS-TSIEME/BZV','281.621.8642','786-748-1211','Célibataire',NULL,1,NULL,'App\\Models\\Service',17,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','vcIgLJisyd','2023-10-04 14:27:03','2023-10-23 10:52:05'),(29,'BIKOYI BAKOUMA','NADIA SYLVANIE STELLA',NULL,'F','1981-02-12','Brazzaville','nadia.bikoyi@artf.cg','747 Marietta Stream Apt. 338\nChrisberg, NC 64435','1-762-486-5915','+1-380-288-3152','Marié(e)',NULL,3,NULL,'App\\Models\\Service',9,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','NqARCEOEkN','2023-10-04 14:27:03','2023-10-05 13:54:53'),(30,'GOMA','Bernard Agnid Cedrick',NULL,'M','1981-02-21','Pointe-Noire','cedric.goma@artf.cg','25180 Jaylin Spurs\nTillmanbury, MD 47269-8251','432.202.7854','651-859-2629','Célibataire',NULL,1,NULL,'App\\Models\\Service',10,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','X3DRX1Uf6E','2023-10-04 14:27:03','2023-10-04 16:33:08'),(31,'KIBAMBA née NKOMBO MAKAYA','Mémé Aimée',NULL,'F','1976-02-19','Pointe-Noire','meem.kimbamba@artf.cg','81086 Hansen Groves\nWest Rachel, NJ 95630','901.712.0312','(380) 488-8536','Marié(e)',NULL,1,NULL,'App\\Models\\Service',17,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','svhKg0LVuK','2023-10-04 14:27:03','2023-10-05 11:02:15'),(32,'MA-MOUBIE','GRACIA SARAI ',NULL,'F','1991-09-22','Brazzaville','gracia.moubie@artf.cg','9565 Goyette Wall\nBogisichfurt, TX 26654','(740) 292-8400','(272) 664-8922','Célibataire',NULL,1,NULL,'App\\Models\\Service',13,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','kO1ha9fes8','2023-10-04 14:27:03','2023-10-06 08:04:54'),(33,'NSONO','GLORIA',NULL,'F','1989-12-21','Gamboma ','gloria.nsono@artf.cg','137 Crystel Light\nCristmouth, WA 55923-8086','+1-309-769-0263','+1-657-519-5604','Célibataire',NULL,1,NULL,'App\\Models\\Service',22,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','t8EBNGz9sQ','2023-10-04 14:27:03','2023-10-06 09:25:40'),(34,'MAMFOUMBI','IDELLE',NULL,'F','1997-08-08','16732 Reuben Ports Suite 807\nRogahnview, ND 55905-8661','idelle.mamfoumbi@artf.cg','513 Macy Road\nKrisstad, OR 44753','623.506.5731','+1.762.460.2081',NULL,NULL,1,NULL,'App\\Models\\Bureau',1,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','7AUCtVfVnwFvNpLVSwvZDe3hOjRnpoDkggCiqbnx91uCzMyMjNdQvF1T7var','2023-10-04 14:27:03','2023-10-04 14:27:03'),(35,'LOKO','ROGER',NULL,'M','1968-01-24','8164 Oberbrunner Lakes\nAylaton, OK 72002-3291','roger.loko@artf.cg','68069 Demetrius Village Apt. 441\nBrentchester, GA 40650-6773','+17146027057','308-664-1605',NULL,NULL,1,NULL,'App\\Models\\Bureau',1,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','Q8JNDPCk3z3dyCkRLQp26iY8lsBFVaPUF31ECVeAwWpxVijTtapOFmYqoCAF','2023-10-04 14:27:03','2023-10-04 14:27:03'),(36,'ENGOBO','GRACE FREDDY',NULL,'M','1997-11-11','823 Wunsch Courts Apt. 880\nLake Carmella, ME 39391-0151','grace.engobo@artf.cg','19123 Stamm Spur\nLake Britneyshire, WA 92302','+242 05 704 04 64','+1-716-752-9480','Célibataire','1716987931.jpg',2,NULL,'App\\Models\\Bureau',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','EpMsEDgLEMnHwoNt9oGsVyMmG6D24zoL7fYwp6P59BNEDgVWNAb8tDf7UJFH','2023-10-04 14:27:03','2024-05-29 13:09:08'),(37,'MAZOUMBOU','CAHEL',NULL,'M','1974-03-17','8583 Astrid Park Suite 304\nSouth Stefanton, WI 46092-0306','cahel.mazoumbou@artf.cg','7352 Pfannerstill Plaza Apt. 348\nDonnellyville, LA 57938','747-842-5831','(223) 234-6100',NULL,NULL,1,NULL,'App\\Models\\Bureau',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','Ssbh64ueWJr3y7muuZlA8A52doRaVvK11dDqjxCKolkjqNoXmQpNKK2XXhha','2023-10-04 14:27:03','2023-10-04 14:27:03'),(38,'KIMBEMBE','BELPHRON',NULL,'M','1968-05-20','627 Bauch Rest\nEast Hollieville, AZ 39554-1408','belphron.kimbembe@artf.cg','7531 Hackett Lodge\nLinniestad, NH 92531','1-276-651-1334','1-430-304-4969','Célibataire',NULL,1,NULL,'App\\Models\\Bureau',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','4p8atTfLkCVHWkmlzELwku7laELsBRFpB9Bw2jVHFrbJK2IpPbtdECJeVqzT','2023-10-04 14:27:03','2023-10-31 09:36:49'),(39,'TCHICAYA','DUC',NULL,'M','1993-12-11','Pointe noire','duc.tchicaya@artf.cg','NKOMBO BRAZZAVILLE','064538483','040875152','Marié(e)',NULL,2,NULL,'App\\Models\\Bureau',14,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','XhEsGnPeQL','2023-10-04 14:27:03','2023-10-05 12:46:02'),(40,'LOUPPE','THIERRY',NULL,'M','1994-06-19','212 Daryl Mall\nMadgeberg, AR 92573-1746','thierry.louppe@artf.cg','99255 Winston Prairie Suite 707\nNorth Gennarofort, CA 06127','540-710-0007','1-928-603-5176',NULL,NULL,2,NULL,'App\\Models\\Bureau',14,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','URUg5DqRaRcyOueRYgph0pF8WosJ2M7E7Jgp0vilZbaq9hOwB64Jq7rdAQyc','2023-10-04 14:27:03','2023-10-04 14:27:03'),(41,'FOULA','NESTEPHIE',NULL,'F','1993-12-03','980 Sabrina Divide\nNew Germaine, MN 54291','nestephie.foula@artf.cg','813 Waelchi Fork\nBergeport, SD 91419','1-856-428-4803','740-667-7710',NULL,NULL,1,NULL,'App\\Models\\Bureau',14,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','7mChsP0DPw','2023-10-04 14:27:03','2023-10-04 14:27:03'),(42,'NGONYA MOKE née MIERET','GRACE LESLIE PRINCILIA',NULL,'F','1992-11-02','BRAZZAVILLE','leslie.mieret@artf.cg','20 RUE MOULEKE TALANGAI','(064033087','053065434','Marié(e)',NULL,2,NULL,'App\\Models\\Bureau',3,'MIERET  ','MERVEIL','069896246','069826686','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','FyHP2hlEC7','2023-10-04 14:27:03','2023-10-05 12:28:46'),(43,'MONDZELE','EUNICE GAELLE',NULL,'F','1995-08-13','Brazzaville','eunice.moundzele@artf.cg','55292 Georgiana Vista Apt. 639\nPort Julienstad, MA 22079','(934) 379-1750','+13049171581','Célibataire',NULL,1,NULL,'App\\Models\\Bureau',48,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','3osfVejsjm','2023-10-04 14:27:03','2023-10-04 16:01:10'),(44,'KAMBA MAPILA','Leslye Mazeline',NULL,'F','1989-10-21','Brazzaville','leslye.kamba@artf.cg','995 Khalid Villages Apt. 389\nDestanyside, AK 35725','1-283-375-4905','1-765-298-4390','Célibataire',NULL,2,NULL,'App\\Models\\Bureau',8,NULL,NULL,NULL,NULL,'$2y$10$TaWy6ZHncWcxYH6R3VvlMeWrgpybtF3Glfsi4RScYNB0eAcLNLfmW','2023-10-04 14:27:03','ddwkICaWlxg4od1ajCx6tPH1Ai9KdFMJAYGV1wVePj7KNPhGfF3RHOJ8q707','2023-10-04 14:27:03','2024-05-22 07:59:36'),(45,'NKAKOUTOU','Godelive',NULL,'F','1964-01-23','72256 Armstrong Island\nWest Jerodville, WV 66955-1694','Godelive.NKAKOUTOU@artf.cg','85930 Hildegard Village\nTrevorchester, MD 24916-6914','(820) 247-6708','908.416.1388',NULL,NULL,1,NULL,'App\\Models\\Bureau',15,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','Z2a0NOpLqO','2023-10-04 14:27:03','2023-10-04 14:27:03'),(46,'OPA née NGAKALA','CATHERINE',NULL,'F','1969-01-24','Brazzaville','catherine.opa@artf.cg','979 Langosh Spurs Apt. 250\nNew Nyah, TN 91893-7094','828.606.7337','838.943.6383','Marié(e)',NULL,1,NULL,'App\\Models\\Bureau',16,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','0bSTW868nx','2023-10-04 14:27:03','2023-10-10 13:10:34'),(47,'BAZINGA née GOMA POUATHY ','Christelle Lydia',NULL,'F','1981-12-12','Brazzaville','lydia.bazinga@artf.cg','9592 Ervin Ford\nWest Andresland, VT 08792','06 935 35 37','06 935 35 37','Marié(e)','1716383940.jpg',1,NULL,'App\\Models\\Bureau',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','R7y1QJaxWc','2023-10-04 14:27:03','2024-05-22 13:19:00'),(48,'LEMBA','GERMAINE',NULL,'F','1971-08-02','628 Wuckert Streets Apt. 602\nRusselmouth, DC 10789-8116','germaine.lemba@artf.cg','90407 Paige Mission\nAnahifort, IN 29926','+12163931605','+1.585.266.8478',NULL,NULL,1,NULL,'App\\Models\\Bureau',20,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','S98Mpi55Is','2023-10-04 14:27:03','2023-10-04 14:27:03'),(49,'FOUTOU','LINELLE',NULL,'F','1974-08-27','8166 Juwan Bypass Apt. 911\nHarrisborough, WA 69287-6255','linelle.foutou@artf.cg','5865 Damon Mill\nSouth Sandrinemouth, NE 14074-8142','(469) 493-0874','1-239-426-4859',NULL,NULL,1,NULL,'App\\Models\\Bureau',9,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','9gWlhK9riV','2023-10-04 14:27:03','2023-10-04 14:27:03'),(50,'FRANCOISE','NKOUSSOU',NULL,'F','1992-09-29','743 Johnston Throughway Suite 818\nCronaville, HI 68633-6344','francoise.nkoussou@artf.cg','2414 Elinore Circle\nSouth Andrew, MD 27741-1270','385-334-0998','985.255.3907',NULL,NULL,1,NULL,'App\\Models\\Bureau',9,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','mva76t5zkh','2023-10-04 14:27:03','2023-10-04 14:27:03'),(51,'NSOUNGA','Vivien',NULL,'M','1961-11-20','699 Terrell Mission\nEast Edgardo, OK 40499','vivien.nsounga@artf.cg','3639 Cleta Curve Suite 930\nWest Lolaton, MT 48797-0634','+1-515-420-0012','678-739-8943',NULL,NULL,1,NULL,'App\\Models\\Bureau',9,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:03','YjTiBOFtTU','2023-10-04 14:27:03','2023-10-04 14:27:03'),(52,'MILANDOU','DAVY',NULL,'M','1960-01-10','7177 Mckenzie Falls\nHaagfort, WY 47918','davy.milandou@artf.cg','61660 Frami Lakes\nWest Etha, MN 57856','1-360-850-1507','959.485.5868',NULL,NULL,1,NULL,'App\\Models\\Bureau',9,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','2ZkkAlENUs','2023-10-04 14:27:04','2023-10-04 14:27:04'),(53,'NTONONGO WAKA','Joseph Donald',NULL,'M','1984-01-31','BZV','joseph.tonongo@artf.cg','07, Okoulou Mbié','06 632-76-46','','Célibataire',NULL,2,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','iNypc3CJf5','2023-10-04 14:27:04','2023-10-06 14:13:53'),(54,'KANGA','Flodia Helssa Jaime',NULL,'F','1989-10-20','BZV','jaime.kanga@artf.cg','1211, Avenue Loumou/Plateux des 15ans','+242 06 860-08-19','704-521-4109','Célibataire',NULL,1,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','D184RfSfIz','2023-10-04 14:27:04','2023-10-23 11:01:30'),(56,'KABOUH','STEVE',NULL,'M','1986-05-27','Brazzaville','steve.kabouh@artf.cg','38 Rue Mayama Moungali','066903542','','Célibataire',NULL,2,NULL,'App\\Models\\Bureau',17,'KABOUH ','Marx Olivier',NULL,'066483556','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','74nev2bb30Q0W793IVn8Kw0AmvZReKvMiuzB5YKk8rRnO3jhMpwjFCaFRy7s','2023-10-04 14:27:04','2023-10-05 09:42:41'),(57,'DIAOUA','PHILIPPE',NULL,'M','1967-09-21','31466 Wilfred Locks\nNew Floyd, NY 80358-5207','philippe.diaoua@artf.cg','27786 Bosco Junctions Suite 772\nNew Wyman, ME 08736-8557','(727) 218-4997','+1-607-515-3708',NULL,NULL,1,NULL,'App\\Models\\Bureau',1,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','W0KzwZ0PBwitzVU66wNBoUqe9L06sgmQF8gqQg6xOZyOT87Q3EaP5KHhRImP','2023-10-04 14:27:04','2023-10-04 14:27:04'),(58,'DIATHA','EMMANUEL',NULL,'M','1977-11-03','BZV','emmanuel.diatha@artf.cg','1538, Rue Noumbi/Plateaux des 15 ans','06 651-50-87','','Célibataire',NULL,2,NULL,'App\\Models\\Service',18,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','WcYeMnPfy7','2023-10-04 14:27:04','2023-10-06 13:57:47'),(59,'OKANDZI NEE  INGONBA','LYDIE RACHELLE',NULL,'F','1966-01-16','BRAZZAVILLE ','lydie.okandzi@artf.cg','20270 Kirstin Center\nRempelville, MD 97112-4255','066418427','06641 84 27','Marié(e)','1716387900.png',1,NULL,'App\\Models\\Service',20,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','M49KMHwCrz','2023-10-04 14:27:04','2024-05-22 14:25:00'),(60,'NGALIBI NEE ITOUA MATONDO','Chouchana',NULL,'F','1989-09-10','Brazzaville','souchana.ngalibi@artf.cg','831 Myrtle Ramp Suite 477\nWest Dellaberg, NM 43672-5032','1-364-982-7441','+1-573-927-5949','Marié(e)',NULL,1,NULL,'App\\Models\\Bureau',5,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','iOy07BOAiH','2023-10-04 14:27:04','2023-10-09 14:00:10'),(61,'MBOUBI NEE NKARI','Ida Blanche',NULL,'F','1978-07-09','Brazzaville','ida.mboubi@artf.cg','4670 Kathleen Court\nSouth Macy, AZ 58135','(252) 783-6492','+1 (689) 507-6344','Marié(e)',NULL,1,NULL,'App\\Models\\Bureau',5,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','hmXpE04ctx','2023-10-04 14:27:04','2023-10-06 15:24:35'),(62,'BAKIEKOLO','GORETTI',NULL,'F','1990-02-17','60225 Schuppe Springs Apt. 179\nHintzfurt, IA 33302','goretti.bakiekolo@artf.cg','52207 Tavares River Apt. 768\nNew Mozell, UT 93987','+1-352-389-5859','(276) 863-7084',NULL,NULL,1,NULL,'App\\Models\\Bureau',5,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','c5AWllwBtB','2023-10-04 14:27:04','2023-10-04 14:27:04'),(63,'TCHICAYA NEE MOLOMBA','CHRISVIE',NULL,'F','1967-05-27','2262 Jamar Circles Suite 830\nPort Thea, WY 38670','chrisvie.tchicaya@artf.cg','384 Mueller Forks\nAdriannafurt, ME 56392','928.789.8225','801-438-3360',NULL,NULL,1,NULL,'App\\Models\\Bureau',6,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','KGkqjRvh2l','2023-10-04 14:27:04','2023-10-04 14:27:04'),(64,'TABANGOLI','ALBIN',NULL,'M','1994-08-11','60386 Maiya Prairie Suite 768\nSouth Thalia, NV 22104-4099','albin.tabangoli@artf.cg','515 Rodriguez Centers\nBednarstad, IN 86615','346.640.5842','+1 (352) 820-6242',NULL,NULL,1,NULL,'App\\Models\\Bureau',5,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','7iX8DaDzL6','2023-10-04 14:27:04','2023-10-04 14:27:04'),(65,'AKABOH','Berenice',NULL,'F','1971-08-16','BZV','berenice.akaboh@artf.cg','186 Ledner Vista Suite 719\nSouth Izabellaville, VA 99575','+1.302.690.1574','1-930-874-8654','Célibataire',NULL,2,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','rn6cZekHn2','2023-10-04 14:27:04','2023-10-13 13:24:03'),(66,'ANKINA','GAV CHYA',NULL,'F','1990-07-18','BRAZZAVILLE','gav.ankina@artf.cg','25 RUE MABOUALE','06 660 97 21','04 480 81 42','Célibataire',NULL,2,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','nUmcXC9QsD','2023-10-04 14:27:04','2023-10-09 15:09:45'),(67,'MITCHA MOULOMA','Paule Cornelia',NULL,'F','1988-03-22','BZV','paule.mitcha@artf.cg','Pas d\'adrsse','Pas de telephone','','Célibataire',NULL,1,NULL,'App\\Models\\Direction',3,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','tNBXSg3p7P','2023-10-04 14:27:04','2023-10-06 14:31:17'),(68,'OLINGO BOUKA','M',NULL,'F','1991-01-24','73369 Morissette Hill Suite 470\nHaleybury, AK 30389','m.olingo@artf.cg','473 Donato Green\nPredovicberg, MT 19553','+1 (916) 876-4100','319.857.9412',NULL,NULL,1,NULL,'App\\Models\\Service',21,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','SPNqjw6c7B','2023-10-04 14:27:04','2023-10-04 14:27:04'),(69,'BANIAKINA','SISSILIA',NULL,'F','1997-08-21','5254 Schroeder Greens Apt. 712\nSouth Amieview, WY 02242','sissilia.baniakina@artf.cg','34155 Price Drives\nSouth Amelia, TX 30435-6285','276.657.6457','+1-908-376-7329',NULL,NULL,1,NULL,'App\\Models\\Direction',2,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','dKHH0NSHCk','2023-10-04 14:27:04','2023-10-04 14:27:04'),(70,'LOUBANZADIO ','Aymar',NULL,'M','1979-12-25',' Brazzaville','aymard.loubanzadio@artf.cg','56900 Kilback Streets\nNew Jadamouth, AK 32695','(838) 303-2900','725.236.2359','Célibataire',NULL,4,NULL,'App\\Models\\Service',15,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','nq3lyls8Fx','2023-10-04 14:27:04','2023-10-17 13:49:18'),(72,'BOUBOUTOU ','JUNIOR',NULL,'F','1997-02-14','660 Reichel Point\nPort Charlieport, NH 99379','junior.bouboutou@artf.cg','3027 Tremblay Crossing Suite 155\nWest Leanneborough, TX 56568','+13519973960','1-561-634-6325',NULL,NULL,1,NULL,'App\\Models\\Bureau',30,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2023-10-04 14:27:04','UKphPo6Rg3','2023-10-04 14:27:04','2023-10-04 14:27:04'),(73,'EBOULA','JOEL DIDACE',NULL,'M','1972-04-01','MAKOUA','joel.eboula@artf.cg','113 RUE KEBARA MIKALOU','06 911 98 40','05 335 98 14','Célibataire',NULL,1,NULL,'App\\Models\\Direction',3,'itoua ','chouchana',NULL,'06 6302197','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 07:18:22','2023-10-05 08:13:05'),(74,'Kianguebeni','Gracia Lorga Charel',NULL,'M','1987-09-26','Brazzaville','lorga.kianguebeni@artf.cg','18 Rue Capitaine Matingou Sangolo/O.M.S','066518961',NULL,'Célibataire',NULL,1,NULL,'App\\Models\\Bureau',32,'Nkounkou','Julie',NULL,'068492651','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 07:23:02','2023-10-05 07:23:02'),(75,'NKOUNKOU','JULIE PRISCA',NULL,'F','1975-08-19','BRAZZAVILLE','prisca.nkounkou@artf.cg','16 BIS RUE MASSOUKOU MOUNGALI','055323770','068492651','Célibataire','1716380718.jpg',1,NULL,'',0,' NDEBEKA ','JULIENNE',NULL,'064629679','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 07:26:45','2024-05-22 12:25:18'),(76,'OKIENE','Racia Benilla',NULL,'F','1994-07-23','Brazzaville','benilla.okiéné@artf.cg','973 rue ste-anne ouénzé','069126363',NULL,'Célibataire',NULL,1,NULL,'App\\Models\\Bureau',3,'OKIENE','Florent',NULL,'069272104','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 07:26:53','2023-10-05 07:26:53'),(77,'BAMBI ','Yassole Alice',NULL,'F','1987-02-28','Paris (France)','yassole.bambi@artf.cg','129 rue Mayombe Plateau des 15 ans  ','053814358',NULL,'Célibataire',NULL,2,NULL,'',0,'ZOABI','Yessei',NULL,'069584227 / 069002834','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 08:25:40','2023-10-05 08:25:40'),(78,'BALOUDAMA ','Amoun Carole Loeffele',NULL,'F','1983-08-27','Kinkala','amoun.baloudama@artf.cg','DIATA','068863962',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 08:27:19','2023-10-05 08:27:19'),(79,'BOUBOUTOU née TCHICAYA MISSAMOU','Estelle Linda Junior',NULL,'F','1971-08-22','Pointe-noire','juniorbouboutou@gmail.com','4 bis rue s ouamounou Mafouta BZV','055508048','069373868','Marié(e)',NULL,2,NULL,'App\\Models\\Service',14,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 08:33:24','2023-10-05 09:44:39'),(80,'DIABANGOUAYA née KOUKISSA NKENGUE ','Jora Emmanuel',NULL,'F','1993-09-27','Brazzaville','jora.koukissa@artf.cg','7 rue Kimbémbé B','56002325',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 08:44:06','2023-10-09 15:36:50'),(81,'GOMA KIFOULOU ','Jean-Teddy Sairah',NULL,'M','1994-12-02','Brazzaville','jean.goma@artf.cg','7 rue Kimbémbé B','56002323',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 08:49:49','2023-10-05 08:49:49'),(82,'GOUALA','Crépin',NULL,'M','1970-10-20','Brazzaville','crepin.gouala@artf.cg','7 rue Kimbémbé B','056002325',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 08:57:31','2023-10-05 08:57:31'),(83,'AKOLI','EMMANUEL',NULL,'M','1997-03-18','BRAZZAVILLE','emmanuel akoli@artf.cg','58 RUE OMBELE TALANGAI','064149525',NULL,'Célibataire',NULL,1,NULL,'App\\Models\\Direction',4,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 08:58:50','2023-10-05 08:58:50'),(84,'KAMOUFOUNOKO ','Even Dominique',NULL,'F','1991-04-08','Brazzaville','even.kamoufounoko@artf.cg','7 rue Kimbémbé B','00012313200',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:02:03','2023-10-05 09:02:03'),(85,'LOCKO ','Grace Georgia Miche',NULL,'F','1988-02-09','Brazzaville','grace.locko','7 rue Kimbémbé B','0415032',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:03:16','2023-10-05 09:03:16'),(86,'MAPAKOU Née SEKANGUE ONDONGA    ','Marina ',NULL,'F','1990-01-31','Brazzaville','marina.mapakou@artf.cg','7 rue Kimbémbé B','01247852',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:04:48','2023-10-05 09:04:48'),(87,'MATONDO MILANDOU ','Francis Crépin',NULL,'M','1987-12-03','Brazzaville','francis.matondo@artf.cg','7 rue Kimbémbé B','01258633',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:06:14','2023-10-05 09:06:14'),(88,'MAYOUMA BENGUI ','Rufin',NULL,'M','1965-06-14','Brazzaville','rufin.mayouma@artf.cg','7 rue Kimbémbé B','025346532',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:07:28','2023-10-05 09:07:28'),(89,'MOULOUNGUI KITSOUKOU',' Chandrel',NULL,'M','1981-10-17','Brazzaville','chandrel.mouloungui@artf.cg','7 rue Sah iron','012488653',NULL,'Célibataire','1716383277.png',1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:09:24','2024-05-22 13:07:57'),(90,'NGATSE née NTASSOU TOME ','Rodia Bardege',NULL,'F','1992-03-03','Brazzaville','rodia.ngatse@artf.cg','7 rue Kimbémbé B','544896523',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:12:51','2023-10-05 09:12:51'),(91,'NGOMA MOUILA ','Sylvie',NULL,'F','1971-09-16','Brazzaville','sylvie.ngoma@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','015646055',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:14:26','2023-10-05 09:14:26'),(92,'NGOUONIMBA ','Lusika Line',NULL,'F','1991-07-10','Brazzaville','line.ngouonimba@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','478535450',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:15:57','2023-10-05 09:15:57'),(93,'NZOUNGOU OSSO','Yessa',NULL,'M','1983-12-31','Brazzaville','yessa.nzoungou@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','47865165465','1054106465','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:17:04','2023-10-05 14:32:24'),(94,'OKOLA ','Arlette Patience Bienvenue',NULL,'F','1983-12-31','Brazzaville','arlette.okola@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','15422546554',NULL,'Célibataire','1716383614.jpg',1,NULL,'',0,'OKOLA ','HUGUES ',NULL,'05 558 35 77','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:18:24','2024-05-22 13:13:34'),(95,'OKOLA ','Estelle Kévine',NULL,'F','1980-08-21','Brazzaville','estelle.okola@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','4145468435231',NULL,'Célibataire','1716383666.jpg',1,NULL,'',0,'OKOLA ','HUGUES ',NULL,'05 558  35 77','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:21:08','2024-05-22 13:14:26'),(96,'SOUZA née BINIAKOUNOU ','Nicia Ghislaine',NULL,'F','1971-08-21','Brazzaville','nicia.souza@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','148555326','5165467495','Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:22:13','2023-10-05 13:51:44'),(97,'TCHITEMBO née PAMBOU ','Louisette Colombe Kele Mbongo ',NULL,'F','1991-08-06','Brazzaville','louisette.tchitembo@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','14684604654',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:24:23','2024-05-22 13:08:40'),(98,'TSIEHELA ','Josy Brunel',NULL,'M','1987-05-28','Brazzaville','josy.tsiehela@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','050262552','0622558225','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:25:31','2023-10-05 09:52:54'),(99,'BISSANGOUX-LOUTAYA','STECIA MEDINE',NULL,'F','1990-12-30','BRAZZAVILLE','stecia.bissangouxartf@.cg','115 RUE MONSEIGNEUR BEICHY MAKELEKELE','069673015','057299917','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:27:39','2023-10-05 09:27:39'),(100,'BOUKETTE ','Grissen ',NULL,'M','1990-02-09','Pointe noire ','grissen.boukette@artf.cg','Dolisie ','065687113','040590097','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:28:54','2023-10-05 09:53:15'),(101,'BISSANGOUX-LOUTAYA','STECIA MEDINE',NULL,'F','1990-12-30','BRAZZAVILLE','stécia bissangoux@artf.cg','115 Rue Monseigneur Biechy Makelekele','06 967 30 15','05 729 99 17','Célibataire',NULL,1,NULL,'App\\Models\\Service',5,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:37:35','2023-10-05 09:44:11'),(102,'ELION','Paul',NULL,'M','1969-02-28','Dolisie','paul.elion@artf.cg','Cité de 17','069514896','AUCUN','Veuf(ve)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:50:17','2023-10-05 10:16:16'),(103,'BOUKORO MOUNGABOU ','Lysia Exaucée ',NULL,'F','1987-07-16','Brazzaville ','lysia.boukoro @artf.cg','Dolisie ','066935581',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 09:59:42','2023-10-05 09:59:42'),(104,'NTSIEMBA SAH','IRON BUTTERFLY',NULL,'M','1979-08-15','MAKABANA','iron.ntsiemba@artf.cg','30 RUE MOUENGUE TALANGAI ','069552416',NULL,'Célibataire','1716382561.jpg',1,NULL,'App\\Models\\Service',16,'MONDZELE','EUNICE',NULL,'066529585','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:16:12','2024-05-22 12:56:01'),(105,'EBATA ATIPO ','Odilon Cartel ',NULL,'M','1990-11-18','Dolisie ','odilon.ebata@artf.cg','Dolisie ','069099042',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:16:56','2023-10-05 10:16:56'),(106,'MAZOUMBOU','Cael Bienvenu',NULL,'M','1986-05-13','BRAZZAVILLE','cahel bienvenu mazoubou','AVENUE O.U.A MAKELEKELE','06 669 16 58','000','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:17:48','2023-10-05 14:56:30'),(107,'MASSAMBA ','Fobrelle Patrianna ',NULL,'F','1991-09-12','Brazzaville','patrianna.massamba@artf.cg','Dolisie ','066270666','','Célibataire',NULL,1,NULL,'App\\Models\\Service',12,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:24:59','2023-10-05 10:24:59'),(108,'ENGAMBE GATCH-PO','Armel',NULL,'M','1995-07-31','Brazzaville','Aucun','Aucune','Aucun','Aucun','Célibataire','1696504144.jpg',1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:34:36','2023-10-05 11:09:04'),(109,'MBOUBI  ',' MPWOU',NULL,'F','1987-12-03','BRAZZAVILLE','wou.mboubi@artf.cg','21 RUE ITOUMBI','064065166','066693397','Célibataire',NULL,1,NULL,'App\\Models\\Service',16,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:40:20','2023-10-05 10:48:02'),(110,'MILANDOU','DAVY ANTOINE',NULL,'M','1980-10-20',' kIBOSSI','davy antoine milandou','04 rue loubaki gustave MFILOU','06 952 04 52 ','05 724 83 91','Célibataire',NULL,2,NULL,'App\\Models\\Direction',6,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:42:45','2023-10-05 10:42:45'),(111,'LOUNDOU OMOYE','Achabrel',NULL,'M','1986-08-20','Brazzaville','chazbrelloundou@gmail.com','05 RUE ST ANNE poto poto','065079620','0000000000','Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 10:55:00','2023-10-05 11:05:08'),(112,'KOUTOUPOT','CHRISTELLE AUGUIE LOIC',NULL,'F','1992-05-22','BRAZZAVILLE','christelle koutoupot@artf.cg','RUE NKENI 45 TALANGAI','06 465 00 14',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:05:53','2023-10-05 11:05:53'),(113,'KOUD ','Guedj Dimitri',NULL,'M','1983-08-18','Brazzaville','èui','1236, rue nkouma ouenze','064569316',NULL,'Célibataire',NULL,1,NULL,'App\\Models\\Service',6,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:07:04','2023-10-05 11:07:04'),(114,'NKOUNKOU MILANDOU ','Christ Steverick Desley ',NULL,'M','1992-08-09','Brazzaville','christ.nkounkou@artf.cg','Dolisie ','069169652','055300972','Célibataire','1716383046.png',1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:07:47','2024-05-22 13:04:06'),(115,'IWANDZA ITOUA','Hermann',NULL,'M','1983-04-04','MAKOUA','hermann.iwandza@artf.cg','4 RUE PENDA MIKALOU','0000','0000','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:23:47','2023-10-05 11:23:47'),(116,'MAVANGA','JOEL',NULL,'M','1970-11-03','BOUENDE PINDA','joel.mavanga','00000000','000000000','00000000','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:27:58','2023-10-05 11:35:02'),(117,'BATCHI BOUYOU','Vinel Antoine',NULL,'M','1995-02-08','Brazzaville','vinel.bouyou@artf.cg','00000','069967857',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:29:27','2023-10-05 11:29:27'),(118,'OLABI','Ingrid',NULL,'F','1988-01-03','Brazzaville','clarichel@yahoo.com','3987 RUE Mignono','00000000','111111111','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:44:37','2023-10-06 10:02:58'),(119,'NGUESSION ','Jered Hermann',NULL,'M','1973-03-21','MAKOLA','hermann.nguession@artf.cg','Brazzaville','066753020','0000','Célibataire',NULL,3,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 11:44:50','2023-10-05 11:54:31'),(120,'MBALOULA ','Fremma Amarelys',NULL,'F','1992-09-18','Brazzaville','fremma.mbaloula@artf.cg','34 bis, rue mabiala-manganga massina mfilou','065052514','','Célibataire',NULL,1,NULL,'',0,'NZONZEKA',' Drancy',NULL,'065121735','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:02:37','2023-10-05 12:02:37'),(121,'OKOMO','Steve Gyvon',NULL,'M','1985-04-30','Brazzaville','steve.okomo@artf.cg','AUCUN','11111','11111','Célibataire',NULL,1,NULL,'App\\Models\\Bureau',34,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:07:13','2023-10-05 12:07:13'),(122,'SITA','Ida Lydie',NULL,'F','1970-08-03','brazzaville','lydie.sita@artf.cg','34 RUE ARCHAMBAULT BACONGO BZV','055268741',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:11:21','2023-10-05 12:11:21'),(123,'MASSAMBA-DEBAT NSILOULOU','Jonathan YOANE',NULL,'M','1995-03-08','Brazzaville','Jonathan.massamba-debat@artf.cg','1516, rue biza makéléké','066065850','053917080','Célibataire',NULL,1,NULL,'',0,'MASSAMBA-DEBAT','Hortense',NULL,'067002205','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:11:50','2023-10-06 13:41:33'),(124,'TOUMBOU Nee NGOMA MVOUAMA  ','Naïda Colombe',NULL,'F','1993-01-27','BRAZZAVILLE','colombe.ngoma@artf.cg','46 RUE DIHESSE/DIATA','06 693 55 81',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:12:16','2023-10-05 12:12:16'),(125,'ONGALA','Jethro Edson',NULL,'M','1992-09-20','NKAYI','jethro.ongala@artf.cg','19 AVENUE DU 5 FEVRIER ','066667213','AUCUN','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:22:12','2023-10-05 12:22:12'),(126,'BOKOYI MALONGA','Josias',NULL,'M','1973-10-22','Brazzaville','josias@yahoo.fr','35 RUE MASSS','0000000',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:24:51','2023-10-05 12:24:51'),(127,'MONDONGO','CHRISTIAN DUCLER',NULL,'M','1983-04-30','BRAZZAVILLE','christian.mondongo@artf.cg','17 RUE ELILA (NKOMBO)','069186068','069186069','Célibataire',NULL,1,NULL,'App\\Models\\Service',14,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:32:08','2023-10-05 12:44:55'),(128,'ATSOUAYA ','Cedric Adré',NULL,'M','1995-08-01','BRAZZAVILE','cedric.atsouaya@artf.cg','45 rue kimbemza','05 583 23 74','06 685 86 95','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 12:45:25','2023-10-05 12:45:25'),(129,'DOMBI','Doria Clarichelle',NULL,'F','1992-05-15','Brazzaville','doria@yahoo.fr','56 RUE MASSENGO','000000','000000','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 13:06:13','2023-10-05 13:06:13'),(130,'MONGONDZA ','Guy Costode ',NULL,'M','1964-10-05','Mossaka ','costode.mongondza@artf.cg','Dolisie ','055574202 ','0022048485','Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 13:11:22','2023-10-05 13:41:26'),(131,'MADIAZA TOUNGOU','Alband Gervais',NULL,'M','1973-06-22','Brazzaville','MAD@YAHOO;COM','08878 RUER NJJKJ','008089','12345656','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 13:17:46','2023-10-05 13:17:46'),(132,'TIMPOLA BIDZOUTA','Guy Alain Serge',NULL,'M','1974-06-09','Brazzaville','alain@yahou.fr','908 RUE ;;;;;;','000090','9090','Célibataire',NULL,1,NULL,'App\\Models\\Service',19,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 13:26:36','2023-10-05 13:26:36'),(133,'MIKANOU PAMBOU ','Rose Samia Johane ',NULL,'F','1993-05-07','Pointe-Noire ','markovdelmir@gmail.com','64 avenu Simon KIMBANGOU, MPISSA BACONGO ','+242 06 469 23 20',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-05 15:30:02','2023-10-05 15:30:02'),(134,'SAMBA ','Eric Prosper ',NULL,'M','1971-07-22','Brazzaville','eric.prosper@artf.cg','Brazzaville ','06 653 41 56','06 653 41 56','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 09:30:05','2024-05-22 13:17:25'),(135,'OBAMBY MABOUERE','Nelly',NULL,'F','1971-05-30','Brazzaville','nelly.obamby@artf.cg','97, rue bordeaux ouenze','065150713',NULL,'Célibataire',NULL,1,NULL,'',0,'OBAMBY ','Aimée','aimée.obamby@artf.cg','066150368','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 09:59:38','2023-10-06 09:59:38'),(136,'MEFANGA DOUMAL','Symphorien',NULL,'M','1982-10-03','Ouesso','DFGG@yahoo.fr','34 rue MJLMKHF','09875468','0000','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 10:08:16','2023-10-06 10:08:16'),(137,'NSOUNGA','Vivien Serge',NULL,'M','1978-03-16','Brazzaville','Dhhhg@yahoo.fr','355 Rue KLJGD','3432135','98742','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 10:21:20','2023-10-06 10:21:20'),(138,'NIONIO née MIOKO','Rebecca',NULL,'F','1967-02-04','BRAZZAVILLE','rebecca.nionio@artf.cg','165 Rue Abolo Ouenzé','06 684 58 00','06 554 14 72','Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 11:14:36','2023-10-06 11:20:59'),(139,'KOUESSANI','Angèle',NULL,'F','1975-09-06','Brazzaville','fgggh@yahoo.fr','456 rue MKJGF','12345',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 11:15:43','2023-10-06 11:15:43'),(140,'DINGA OBA ','Beaudry Lebel',NULL,'M','1990-12-09','Brazzaville ','lebel.dingaoba@artf.cg','rue lebanitou, OCH','+242 06 950 50 50',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 13:26:49','2023-10-06 13:26:49'),(141,'MBOUBI ','NDOULOU',NULL,'F','1983-07-26','Brazzaville ','ndoulou.mboubi@artf.cg','rue likouala, TALANGAI ','+242 06 950 50 51',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 13:34:12','2023-10-06 13:34:12'),(142,'OSSETE née LEKE','Emma Marie-Noelle',NULL,'F','1969-12-10','BRAZZAVILLE','marienoelleossete@artf.cg','MPILA','066585109',NULL,'Marié(e)',NULL,1,NULL,'App\\Models\\Bureau',33,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 13:58:41','2023-10-06 13:58:41'),(143,'ZOLA ','Eulano Larry Scoot ',NULL,'M','1993-03-29','Brazzaville ','eulano.zola@artf.cg','Dolisie ','066999936','056999936','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 15:09:04','2023-10-06 15:09:04'),(144,'TSANA MABOULI ','Harvela Jirne Elvire ',NULL,'F','1990-02-17','Brazzaville ','jirne.tsana@artf.cg','Dolisie ','069151958','050409986','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 15:34:07','2023-10-06 15:34:07'),(145,'DIABANGOUAYA ','Cédric Fresnel',NULL,'M','1988-06-05','Brazzaville','fresnel.diabangouaya@artf.cg','275, rue Notre-Dame Est, bur. R.134 Montréal, QC H','052056526512',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-06 16:00:35','2023-10-06 16:00:35'),(146,'NGOMA NGOULOUBI ','Clid Philian',NULL,'M','1992-05-27','BRAZZAVILLE','clidngoma@artf.cg','************','***********',NULL,'Célibataire',NULL,1,NULL,'App\\Models\\Service',16,'NGOMA  ','Philippe',NULL,'06 836 19 18','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-09 09:39:12','2023-10-09 09:39:12'),(147,'OSSETE','Prisca Josiane',NULL,'M','1982-08-01','BRAZZAVILLE','priscaossete@artf.cg','11 Avenue du port M\'pila','05 543 13 02',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-09 09:50:54','2023-10-09 09:50:54'),(148,'MANIMA NSOUKILA','Hermann Steve',NULL,'M','1978-04-11','BRAZZAVILLE','******','46 rue MATIABOU MOUKOUNZINGOUAKA','06 683 72 40','06 683 72 40','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-09 10:37:25','2023-10-09 10:52:08'),(149,'ONDON née OBIALA','ANNICK ANNE',NULL,'F','1965-05-06','GAMBOMA','anne obila née ondon','RUE ABILA TALANGAI','0697922385',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-09 12:35:08','2023-10-09 12:51:50'),(150,'BISSILA HOUMBA','YANNICK ESPOIR',NULL,'M','1992-02-29','BRAZZAVILLE','yannbissila@gmail.com',' 02 RUE NGOYI MFILOU','066433873',NULL,'Célibataire',NULL,1,NULL,'',0,'BISSILA','MARTIN',NULL,'069287545','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-09 13:00:02','2023-10-09 15:13:57'),(151,'FOULA BONGUY née NGUESSION','Nestéphie',NULL,'F','1987-04-12','BRAZZAVILLE','nestefie.bongui@artf.cg','06 AVENUE DE LA STIEME ','06 818 02 85',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-09 13:18:27','2023-10-09 13:18:27'),(152,'BINTSENE','Joseph',NULL,'M','1971-09-29','Mbaya','sans email','39 rue Likouala/ Poto-Poto','sans numero','1111111','Célibataire',NULL,3,NULL,'App\\Models\\Service',8,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-10 14:19:46','2023-10-10 14:19:46'),(153,'LOUBASSOU N\'LABA','Ginette',NULL,'F','1969-10-05','BZV','ginetteloubassou1@gmail.com','1328 rue Mabirou Ouenze','055365676','068072772','Célibataire',NULL,3,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-11 15:39:49','2023-10-11 15:39:49'),(154,'DONGOU Ferdin Thanic',NULL,NULL,'M','1984-04-14','BRAZZAVILLE','ferdin.dongou@artf.cg','0000000',' 000 00 00 00','00000000','Célibataire',NULL,1,NULL,'',0,'00000',NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-12 13:37:20','2023-10-12 13:37:20'),(155,'EPENITH KASSIMBA','Magalie Fransnelle',NULL,'F','1993-04-20','BRAZZAVILLE','magali.epenitn@artf.cg','13 RUE ABOLO TALANGAI BZV','06 569 75 06','04 444 64 80','Célibataire',NULL,1,NULL,'',0,'EPENIT KASABAND ','Rostand Evence Marcel',NULL,'06644 80 33','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-12 13:47:48','2023-10-12 13:47:48'),(156,'KOUTIKI','Lardry Herz Friedemann',NULL,'M','1989-04-11','BRAZZAVILLE','lardry.koutiki@artf.cg','Villa n°839 Rue Cardinal Emile BIAYENDA Mpissa Bacongo','044926060','066064343','Marié(e)',NULL,1,NULL,'App\\Models\\Service',10,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-13 11:21:41','2023-10-16 13:46:01'),(157,'MABICKA','Fara Hermann',NULL,'M','1974-08-27','BRAZZAVILLA','fara.mabicka@artf.cg','111','000','000','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-13 11:35:44','2023-10-13 11:35:44'),(158,'MISSAMOU ','Rosine Iphigénie',NULL,'F','1965-06-16','Brazzaville','rosine.missamou@.cg','187 Rue Franceville Ouenzé Brazzaville','06 664 67 36','06 664 67 36','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-18 09:25:47','2023-10-18 09:25:47'),(159,'KOUBEMBA','Sidon Lié Blaise',NULL,'M','1969-08-23','brazzaville','blaise.koubemba@artf.cg','00 rue 000','00 000 00 00',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-18 13:09:47','2023-10-18 13:09:47'),(160,'NKODIA ','Chrishna Yasmine Simplicia',NULL,'F','1996-05-15','BRAZZAVILLE','symplicia.nkodia@artf.cg','37 Rue loubassou Lua Nuni Sangolo (OMS)','066105046',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-18 13:19:49','2023-10-18 13:19:49'),(161,'N\'KUKA','Giles Christ',NULL,'M','1994-03-07','KINKALA','gilles.nkuka@artf.cg','07 Rue Mère Essengo ,Massissia Brazzaville',' 06 840 32 84','06 840 32 84','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-18 13:50:14','2023-10-18 14:04:44'),(162,'YOLI','Françoise',NULL,'F','1965-02-10','BRAZZAVILLE','françoise.yoli@artf.cg','11 RUE 000','06 000 00 00','00 000 00 00','Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-20 12:06:20','2023-10-20 12:06:20'),(163,'MANFOUMBI SIMBOU ','Syrnonde Idelle',NULL,'F','1986-03-18','loubomo','syrnonde.manfoumbi@artf.com','22 rue mbila diata Makélékélé','06 9504019','055119217','Célibataire',NULL,2,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-10-31 10:12:17','2023-10-31 10:12:17'),(164,'MAMPOUYA née AKIANA','Frida Judith Nadine',NULL,'F','1969-06-21','Brazzaville','frida.akiana@artf.cg','10 Rue Sangolo MADIBOU','06 6661672',NULL,'Marié(e)',NULL,2,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-03 07:31:12','2023-11-03 07:31:12'),(165,'MONKA','Max Henri',NULL,'M','1968-04-15','Brazzaville','max.monka@artf.cg','s/c du centre missionaire Réhoboth','055500000','066200000','Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-03 10:06:43','2023-11-03 10:06:43'),(166,'NKOUA née MONTBOULI ','Evelyne lea Pulcherie',NULL,'F','1970-02-19','Brazzaville','evelyne.montbouli@ARTF.CG','10 rue nianga Talangai','066601576','0000000','Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-03 10:19:30','2023-11-03 10:23:55'),(167,'LOUEKO','Alain Chrystian',NULL,'M','1970-01-05','Brazzaville','alain.loueko','58 rue likouala potopoto','068586376',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-03 12:53:10','2023-11-03 12:53:10'),(170,'OBELO','Fabrice Charles Armel',NULL,'M','1976-09-30','Brazzaville','fabrice.obelo@artf.cg','makabandilou','06 651 82 30',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 07:13:00','2023-11-06 07:13:00'),(171,'MANIMA NSOUKILA','Steeve Hermann',NULL,'M','1978-04-11','Brazzaville','hermann.manima@artf.cg','matibou','066837240',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 07:19:11','2023-11-06 07:19:11'),(172,'NOUROUBIA','Ella Ginette',NULL,'F','1975-01-05','Brazzaville','ella.nouroubia@artf.cg','talangai','066500000',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 07:25:21','2023-11-06 07:25:21'),(173,'NGANDZALA','Habib Melaine',NULL,'M','1980-01-15','Brazzaville','habib.ngandzala@artf.cg','talangai','066500555',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 07:32:35','2023-11-06 07:32:35'),(174,'NDOMBI','Sosthéne Rodolph',NULL,'M','1970-06-26','Brazzaville','sosthéne.ndombi@artf.cg','potopoto','066691140',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 07:39:52','2023-11-06 07:39:52'),(175,'GOKABAND ANDZI','Bertille',NULL,'F','1973-02-04','brazzaville','bertille.gokaband','ouenze','055300000',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 07:46:50','2023-11-06 07:46:50'),(176,'DZON GUETSIE CISSE','Aimé Bienvenu',NULL,'M','1969-12-30','Brazzaville','bienvenu.dzon','centre ville','044560000',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 07:55:20','2023-11-06 07:55:20'),(177,'BENDABENDA','Maurice',NULL,'M','1967-03-05','oyo','maurice.bendabenda','avenue de la revolution lycée  THOMAS SANKARA','069738045',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 08:27:54','2023-11-06 08:27:54'),(178,'GOUALA','Crepin',NULL,'M','1970-10-20','Bouanza','crepin.gouala@rtf.cg','pointe noire','064506060',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 08:34:47','2023-11-06 08:34:47'),(179,'MAYOUMA BENGUI','Rufin',NULL,'M','1965-06-14','Brazzaville','rufin.bengui@artf.cg','Pointe noire','066303616',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 08:50:27','2023-11-06 08:50:27'),(180,'ATSOUAWE','Eyeas nina',NULL,'M','1977-07-21','brazzaville','eyeas.atsouawe@artf.cg','talangaii','066685802',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 08:57:16','2023-11-06 08:57:16'),(181,'ANGONA','Soreil',NULL,'M','1972-10-07','oka abala','soreil.angona@artf.cg','talangaii','068404017',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 10:16:06','2023-11-06 10:16:06'),(182,'MANDA MIKOUNGA','Constant Simplice',NULL,'M','1975-06-19','dombé - mossaka','simplice.manda@artf.cg','talangaii','0556500000',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 10:23:46','2023-11-06 10:23:46'),(183,'DIAFOUKA SITA','Céléstine',NULL,'M','1971-09-23','brazzaville','céléstine.diafouka','makélékélé','066550000',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 10:30:31','2023-11-06 10:30:31'),(184,' BIYEKOLA ZOU  née PEDRO ','Nadine Pamela Lethissia',NULL,'M','1977-03-06','brazzaville','nadine.pedro@artf.cg','bacongo','064563000',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 10:35:59','2024-05-22 12:58:04'),(185,'NDINGA née NGOUBI','Michelle Ghislaine',NULL,'F','1978-11-30','impfondo','michelle.ngoubi@artf.cg','masengo','066660000',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 10:46:38','2023-11-06 10:46:38'),(186,'OBBA','Andrée Josée Christine',NULL,'F','1967-02-01','brazzaville','josée.obba@artf.cg','ouenze','054500000',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-06 10:51:44','2023-11-06 10:51:44'),(187,'MBAKI née NIOKA BADINGA','Chantal Albertine',NULL,'F','1977-04-10','Pointe Noire','chantal.nioka@ar tf.cg',' 1711 rue matsiona -nzoulou batignoles ','055542100',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-07 06:39:31','2023-11-07 06:45:42'),(188,'OBAKA née BONGHO EBBA','Caroline',NULL,'F','1974-11-29','brazzaville','caroline.bongho@artf.cg','ouenze','056500002',NULL,'Marié(e)',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-07 06:51:02','2023-11-07 06:51:02'),(189,'GANGA','Ferdinand Samuel',NULL,'M','1969-05-29','brazzaville','ferdinand.ganga@artf.cg','potopto','066650101',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-07 06:56:45','2023-11-07 06:56:45'),(190,'DOUNIAMA -IBOUGNA',NULL,NULL,'M','1976-02-14','owando','douniama.ibougna','talangai mpila','066630000',NULL,'Célibataire',NULL,1,NULL,'',0,NULL,NULL,NULL,NULL,'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,'2023-11-07 07:02:27','2023-11-07 07:02:27');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `validationdemandes`
--

DROP TABLE IF EXISTS `validationdemandes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `validationdemandes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `demandeabscence_id` bigint(20) unsigned NOT NULL,
  `destinable_type` varchar(255) NOT NULL,
  `destinable_id` bigint(20) unsigned NOT NULL,
  `date_reception` timestamp NULL DEFAULT NULL,
  `user_reception_id` bigint(20) unsigned DEFAULT NULL,
  `date_reponse` timestamp NULL DEFAULT NULL,
  `motivation_refus` text DEFAULT NULL,
  `user_reponse_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `validationdemandes_demandeabscence_id_foreign` (`demandeabscence_id`),
  KEY `validationdemandes_destinable_type_destinable_id_index` (`destinable_type`,`destinable_id`),
  KEY `validationdemandes_user_reception_id_foreign` (`user_reception_id`),
  KEY `validationdemandes_user_reponse_id_foreign` (`user_reponse_id`),
  CONSTRAINT `validationdemandes_demandeabscence_id_foreign` FOREIGN KEY (`demandeabscence_id`) REFERENCES `demandeabscences` (`id`),
  CONSTRAINT `validationdemandes_user_reception_id_foreign` FOREIGN KEY (`user_reception_id`) REFERENCES `users` (`id`),
  CONSTRAINT `validationdemandes_user_reponse_id_foreign` FOREIGN KEY (`user_reponse_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `validationdemandes`
--

LOCK TABLES `validationdemandes` WRITE;
/*!40000 ALTER TABLE `validationdemandes` DISABLE KEYS */;
/*!40000 ALTER TABLE `validationdemandes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-07-01 12:05:41
