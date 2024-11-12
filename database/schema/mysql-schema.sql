/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lines` (
  `line_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `workers_count` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `updated_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `started_at` time DEFAULT NULL,
  `ended_at` time DEFAULT NULL,
  `down_from` time DEFAULT NULL,
  `down_time` int DEFAULT '0',
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancel_reason` int DEFAULT NULL,
  `type_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `master` int DEFAULT NULL,
  `engineer` int DEFAULT NULL,
  PRIMARY KEY (`line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs` (
  `log_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:39',
  `updated_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:39',
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extra` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `people_count` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `product_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_id` bigint NOT NULL,
  `workers_count` int NOT NULL,
  `shift` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `created_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `started_at` time NOT NULL,
  `ended_at` time NOT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products_categories` (
  `category_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent` int DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products_dictionary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products_dictionary` (
  `product_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `amount2parts` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parts2kg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kg2boil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cars` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products_order` (
  `order_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `amount` int NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products_plan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products_plan` (
  `plan_product_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `line_id` int NOT NULL,
  `slot_id` int NOT NULL,
  `started_at` time NOT NULL,
  `ended_at` time NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:39',
  `updated_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:39',
  `amount` int NOT NULL,
  PRIMARY KEY (`plan_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `products_slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products_slots` (
  `product_slot_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `line_id` int NOT NULL,
  `people_count` int NOT NULL,
  `perfomance` double NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `type_id` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`product_slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `responsible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `responsible` (
  `responsible_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '2024-11-12 09:36:38',
  `updated_at` datetime NOT NULL DEFAULT '2024-11-12 09:36:38',
  PRIMARY KEY (`responsible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slots` (
  `slot_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `worker_id` bigint NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `updated_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `started_at` time NOT NULL,
  `ended_at` time NOT NULL,
  `line_id` bigint NOT NULL,
  `down_time` int DEFAULT '0',
  `time_planned` int NOT NULL,
  PRIMARY KEY (`slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workers` (
  `worker_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `updated_at` datetime NOT NULL DEFAULT '2024-10-21 08:42:38',
  `break_started_at` time NOT NULL,
  `break_ended_at` time NOT NULL,
  `company` char(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`worker_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2024_08_25_182757_create_lines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2024_08_26_191007_create_workers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2024_08_26_191124_create_slots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2024_08_26_191305_change_lines_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2024_08_26_191805_add_worker_company',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2024_08_26_204005_slots_add_line_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2024_08_28_192204_create_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2024_09_03_142808_change_prod_field_and_delete_slot_title',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2024_09_09_152034_add_base_time_to_worker',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2024_09_09_185558_change_type_of_time_planned',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2024_09_10_195432_add_down_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2024_09_10_214012_set_default_down_time',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2024_09_10_222744_change_down_time_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2024_09_11_182826_change_time_planned',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2024_09_11_190652_add_color',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2024_09_12_091222_add)fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2024_09_29_102743_add_cancel_reason',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2024_10_03_144237_create_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2024_10_15_190826_products_update',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2024_10_16_133422_add_products_categories',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2024_10_20_120855_create_products_order',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2024_10_28_194216_add_type_field',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2024_10_29_181104_add_new_fields',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2024_11_03_083042_add_fields_to_slots',2);
