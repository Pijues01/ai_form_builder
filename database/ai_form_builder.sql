-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: ai_form_builder
-- ------------------------------------------------------
-- Server version	8.0.46

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
-- Table structure for table `ai_generations`
--

DROP TABLE IF EXISTS `ai_generations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_generations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `mode` enum('create','edit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'create',
  `prompt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `input` json DEFAULT NULL,
  `result` json DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `error` text COLLATE utf8mb4_unicode_ci,
  `provider` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokens_prompt` int unsigned DEFAULT NULL,
  `tokens_completion` int unsigned DEFAULT NULL,
  `tokens_total` int unsigned DEFAULT NULL,
  `latency_ms` int unsigned DEFAULT NULL,
  `repair_attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_generations_form_id_foreign` (`form_id`),
  KEY `ai_generations_user_id_index` (`user_id`),
  KEY `ai_generations_mode_index` (`mode`),
  KEY `ai_generations_status_index` (`status`),
  CONSTRAINT `ai_generations_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_generations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_generations`
--

LOCK TABLES `ai_generations` WRITE;
/*!40000 ALTER TABLE `ai_generations` DISABLE KEYS */;
/*!40000 ALTER TABLE `ai_generations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
-- Table structure for table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `data` json NOT NULL,
  `searchable` text COLLATE utf8mb4_unicode_ci,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_submissions_form_id_created_at_index` (`form_id`,`created_at`),
  KEY `form_submissions_ip_index` (`ip`),
  FULLTEXT KEY `form_submissions_searchable_fulltext` (`searchable`),
  CONSTRAINT `form_submissions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_submissions`
--

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_versions`
--

DROP TABLE IF EXISTS `form_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `version` int unsigned NOT NULL,
  `schema` json NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_versions_form_id_version_unique` (`form_id`,`version`),
  KEY `form_versions_created_by_foreign` (`created_by`),
  CONSTRAINT `form_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `form_versions_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_versions`
--

LOCK TABLES `form_versions` WRITE;
/*!40000 ALTER TABLE `form_versions` DISABLE KEYS */;
INSERT INTO `form_versions` VALUES (1,1,1,'{\"title\": \"Internship Application\", \"sections\": [{\"id\": \"sec_personal\", \"title\": \"Personal Details\", \"fields\": [{\"id\": \"fld_ZE2nmePN\", \"key\": \"full_name\", \"type\": \"text\", \"label\": \"Full Name\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": \"e.g. Rahul Sharma\"}, {\"id\": \"fld_VkFOGXhl\", \"key\": \"email\", \"type\": \"email\", \"label\": \"Email Address\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": \"you@example.com\"}, {\"id\": \"fld_nPgHJNNm\", \"key\": \"phone\", \"type\": \"phone\", \"label\": \"Phone Number\", \"default\": null, \"options\": [], \"required\": false, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": \"+91 98xxxxxx\"}, {\"id\": \"fld_8fOVJdus\", \"key\": \"dob\", \"type\": \"date\", \"label\": \"Date of Birth\", \"default\": null, \"options\": [], \"required\": false, \"help_text\": \"Optional, used for eligibility checks\", \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}]}, {\"id\": \"sec_education\", \"title\": \"Education & Skills\", \"fields\": [{\"id\": \"fld_4DdgT7eM\", \"key\": \"college\", \"type\": \"text\", \"label\": \"Current College\", \"default\": null, \"options\": [], \"required\": false, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}, {\"id\": \"fld_Wi9Kg9O9\", \"key\": \"education_history\", \"type\": \"textarea\", \"label\": \"Education History\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": \"List degrees, institutions and years\", \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}, {\"id\": \"fld_kCOgbi4X\", \"key\": \"skills\", \"type\": \"checkbox\", \"label\": \"Skills\", \"default\": null, \"options\": [{\"label\": \"PHP\", \"value\": \"PHP\"}, {\"label\": \"JavaScript\", \"value\": \"JavaScript\"}, {\"label\": \"Python\", \"value\": \"Python\"}, {\"label\": \"UI/UX Design\", \"value\": \"UI/UX Design\"}, {\"label\": \"Data Analysis\", \"value\": \"Data Analysis\"}], \"required\": false, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": 1}, \"placeholder\": null}, {\"id\": \"fld_VFSFblW2\", \"key\": \"programming_level\", \"type\": \"rating\", \"label\": \"Programming Level\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": \"1 = beginner, 5 = expert\", \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}]}, {\"id\": \"sec_resume\", \"title\": \"Resume & Availability\", \"fields\": [{\"id\": \"fld_uIUV94IM\", \"key\": \"resume\", \"type\": \"file\", \"label\": \"Resume Upload\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [\"pdf\", \"doc\", \"docx\"], \"pattern\": null, \"max_size\": 2048, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}, {\"id\": \"fld_1Hy1P7ag\", \"key\": \"availability\", \"type\": \"radio\", \"label\": \"Availability\", \"default\": null, \"options\": [{\"label\": \"Full-time\", \"value\": \"Full-time\"}, {\"label\": \"Part-time\", \"value\": \"Part-time\"}, {\"label\": \"Weekends only\", \"value\": \"Weekends only\"}], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}]}], \"description\": \"Tell us about yourself and why you want to join our internship program.\"}','Initial seed',1,'2026-08-04 19:57:27','2026-08-04 19:57:27');
/*!40000 ALTER TABLE `form_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `schema` json DEFAULT NULL,
  `schema_version` int unsigned NOT NULL DEFAULT '1',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_slug_unique` (`slug`),
  KEY `forms_user_id_index` (`user_id`),
  KEY `forms_status_index` (`status`),
  CONSTRAINT `forms_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES (1,1,'Internship Application','internship-application','Tell us about yourself and why you want to join our internship program.','{\"title\": \"Internship Application\", \"sections\": [{\"id\": \"sec_personal\", \"title\": \"Personal Details\", \"fields\": [{\"id\": \"fld_ZE2nmePN\", \"key\": \"full_name\", \"type\": \"text\", \"label\": \"Full Name\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": \"e.g. Rahul Sharma\"}, {\"id\": \"fld_VkFOGXhl\", \"key\": \"email\", \"type\": \"email\", \"label\": \"Email Address\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": \"you@example.com\"}, {\"id\": \"fld_nPgHJNNm\", \"key\": \"phone\", \"type\": \"phone\", \"label\": \"Phone Number\", \"default\": null, \"options\": [], \"required\": false, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": \"+91 98xxxxxx\"}, {\"id\": \"fld_8fOVJdus\", \"key\": \"dob\", \"type\": \"date\", \"label\": \"Date of Birth\", \"default\": null, \"options\": [], \"required\": false, \"help_text\": \"Optional, used for eligibility checks\", \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}]}, {\"id\": \"sec_education\", \"title\": \"Education & Skills\", \"fields\": [{\"id\": \"fld_4DdgT7eM\", \"key\": \"college\", \"type\": \"text\", \"label\": \"Current College\", \"default\": null, \"options\": [], \"required\": false, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}, {\"id\": \"fld_Wi9Kg9O9\", \"key\": \"education_history\", \"type\": \"textarea\", \"label\": \"Education History\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": \"List degrees, institutions and years\", \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}, {\"id\": \"fld_kCOgbi4X\", \"key\": \"skills\", \"type\": \"checkbox\", \"label\": \"Skills\", \"default\": null, \"options\": [{\"label\": \"PHP\", \"value\": \"PHP\"}, {\"label\": \"JavaScript\", \"value\": \"JavaScript\"}, {\"label\": \"Python\", \"value\": \"Python\"}, {\"label\": \"UI/UX Design\", \"value\": \"UI/UX Design\"}, {\"label\": \"Data Analysis\", \"value\": \"Data Analysis\"}], \"required\": false, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": 1}, \"placeholder\": null}, {\"id\": \"fld_VFSFblW2\", \"key\": \"programming_level\", \"type\": \"rating\", \"label\": \"Programming Level\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": \"1 = beginner, 5 = expert\", \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}]}, {\"id\": \"sec_resume\", \"title\": \"Resume & Availability\", \"fields\": [{\"id\": \"fld_uIUV94IM\", \"key\": \"resume\", \"type\": \"file\", \"label\": \"Resume Upload\", \"default\": null, \"options\": [], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [\"pdf\", \"doc\", \"docx\"], \"pattern\": null, \"max_size\": 2048, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}, {\"id\": \"fld_1Hy1P7ag\", \"key\": \"availability\", \"type\": \"radio\", \"label\": \"Availability\", \"default\": null, \"options\": [{\"label\": \"Full-time\", \"value\": \"Full-time\"}, {\"label\": \"Part-time\", \"value\": \"Part-time\"}, {\"label\": \"Weekends only\", \"value\": \"Weekends only\"}], \"required\": true, \"help_text\": null, \"conditions\": [], \"validation\": {\"max\": null, \"min\": null, \"step\": null, \"mimes\": [], \"pattern\": null, \"max_size\": null, \"max_files\": null, \"max_length\": null, \"min_length\": null, \"max_selections\": null, \"min_selections\": null}, \"placeholder\": null}]}], \"description\": \"Tell us about yourself and why you want to join our internship program.\"}',1,'published','{\"confirmation_message\": \"Thanks! We will get back to you soon.\"}','2026-08-04 19:57:27','2026-08-04 19:57:27');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `import_previews`
--

DROP TABLE IF EXISTS `import_previews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `import_previews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disk` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `result` json DEFAULT NULL,
  `warnings` json DEFAULT NULL,
  `error` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `import_previews_form_id_foreign` (`form_id`),
  KEY `import_previews_user_id_index` (`user_id`),
  KEY `import_previews_file_type_index` (`file_type`),
  KEY `import_previews_status_index` (`status`),
  CONSTRAINT `import_previews_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `import_previews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `import_previews`
--

LOCK TABLES `import_previews` WRITE;
/*!40000 ALTER TABLE `import_previews` DISABLE KEYS */;
/*!40000 ALTER TABLE `import_previews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
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
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_01_01_000001_create_forms_table',1),(5,'2025_01_01_000002_create_form_versions_table',1),(6,'2025_01_01_000003_create_form_submissions_table',1),(7,'2025_01_01_000004_create_ai_generations_table',1),(8,'2025_01_01_000005_create_import_previews_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('1Ufqbh9UhlEgd0wjncjL336v5A5ITSaDdbfwNDci',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoidVBWWVlncTVWQ1JDM2F4RzRraTA3WDZWZ1J0YVZpMmVkVGNPZDNTVyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785877722),('2o6OaMSbSgzJZaopOo2rTBnXwfNcj88sZOWY7QMU',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUk1wdG1RVHNKWTk1emlDSWl6Q0VKaWVjd0tON2t5QkJNeWlTb3FkbiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785877824),('3wZs88BI6m0rpZWxaHRjp3nUJ7rHp9gSCuAKCzSC',NULL,'172.18.0.1','curl/8.18.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU2lVeHRUdExCOVdlRHpJclFIakFpdUoyZjZRY3ZncUNyQ2tkazJCOSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9mb3JtcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MzoidXJsIjthOjE6e3M6ODoiaW50ZW5kZWQiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9mb3JtcyI7fX0=',1785873512),('c7VEISJyNI37ZPAKG7Zh9yL7LZ9URhez8iZv3bp0',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM241REJIREtUSVhsZmI5YnUwQjQ5d0UybHo4d3N4SEVLZVlzTFdnYSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785875355),('Duw6Z6wbhY541Vl5fyEoSleWlW9AiiRq0JfvbBdm',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYjFwOE43b1lKa0FDemNpNmxTajBXY1ZBVndnd1F4N2JFdE41NnNUMSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785874992),('e6uHA1LkvvhlHpvJsXH18iVTLvZWQFwDTIjUP73a',NULL,'172.18.0.1','curl/8.18.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiU3FEYTFCcGFGckhTa2dTRUJXTVdOQlNjblRBemNJbFpXeEFIeWdISCI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cDovL2xvY2FsaG9zdDo4MDAwL2Zvcm1zIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9mb3JtcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785873469),('fmF2VkivmmrVX8FeeQeFdUE7IMmhCZho7aQAVq5R',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZjBhMWQwdEs5cnBVZGdrdE4zdUU2dXFQNHpXdnViWWxDOW82T2pRUCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785873564),('k8vZ0YYA1pJqr4cN1CY4aQKsQy7GGuhZNkkaGnKO',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVXltQ3VQNkFZUGl3SGpKVnpHMUFhQ0NlbVpDbUh2N2NoVjlRY3NiTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785873520),('kxO4uhGiYHepMerGJ0oxwEIo1jA8Tq8BY8shjTGN',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNmQxRFJDTGZVRUFpUU1YT2k2a21KaTRCWnN3RjZWMWJrazc3dWdiQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785873470),('lrUQmtwYB1QpTTfwnijtStPBPvtkZYQi9zLOiz2m',1,'172.18.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoibHR4bWNHQnZ5UnpXamFNZ05DbVp0SWEwMmQ0eHJKREY5b3p1TGpsdyI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI4OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvaW1wb3J0Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MTt9',1785880192),('NM1NtRibAchDjKE8THad6KhfA9Asdr46EwerCBC2',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZEtISmVRaG5jSVJGUTJ0cHlZMkt3cFRUNll2cllXVWNiWUd4djhDTyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785877830),('NnW8bbiK1xKZKpetDUZqXQzSrV2yohbyg0hGZBQf',NULL,'172.18.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTXpYemlCZEpQWUpWZEt2ZjlxSjZzbUxua1lxVFEwR3lBYjR6UXk5QyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785875631),('t5i6KuBjxMby8gQ4aIP35JL5gJtB6NW6d7QH9pHt',NULL,'172.18.0.1','curl/8.18.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiaGV5MFV6VEV3d25qN3RXTGZrZ2NNdUlKNTJiTXVMTlZFV2Y4NWxZbSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyNzoiaHR0cDovL2xvY2FsaG9zdDo4MDAwL2Zvcm1zIjt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1785873506);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Demo Admin','demo@example.com',NULL,'$2y$12$I1Dk6.RAPwS1EzZ9tfnUre5.y50Wi2DmvssBhjl7HizhvXuyuwzj.',NULL,'2026-08-04 19:57:27','2026-08-04 21:10:10');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ai_form_builder'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-04 21:52:54
