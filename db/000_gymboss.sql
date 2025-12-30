-- MySQL dump 10.13  Distrib 8.0.44, for Win64 (x86_64)
--
-- Host: localhost    Database: gymboss_db
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Грифы'),(2,'Блины'),(3,'Гантели'),(4,'Стойки'),(5,'Лавки'),(6,'Блоки'),(7,'Экипировка'),(8,'Спортпит');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_addresses`
--

DROP TABLE IF EXISTS `delivery_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address_line` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `delivery_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_addresses`
--

LOCK TABLES `delivery_addresses` WRITE;
/*!40000 ALTER TABLE `delivery_addresses` DISABLE KEYS */;
INSERT INTO `delivery_addresses` VALUES (40,84,'Москва, улица Головачёва, 5к2, подъезд 1, этаж 2, кв. 9','109380'),(41,84,'Москва, улица Головачёва',''),(42,84,'Москва, улица Головачёва, 2','109380'),(43,84,'Москва, улица Головачёва, вл3','109380'),(44,84,'Москва, улица Головачёва, 5к2, подъезд 1, этаж 1, кв. 2','109380'),(45,84,'Москва, Головинское шоссе',''),(46,84,'Москва, улица Головачёва, 1к2','109380');
/*!40000 ALTER TABLE `delivery_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `delivery_type` enum('delivery','pickup') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_address_id` int DEFAULT NULL,
  `store_id` int DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cart',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `paid_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `yookassa_payment_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `delivery_address_id` (`delivery_address_id`),
  KEY `orders_ibfk_1` (`store_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (39,84,NULL,4750.00,'delivery',750.00,40,NULL,'paid','2025-12-03 17:51:59','2025-12-03 17:52:48','2025-12-03 17:52:48',NULL,'30c28ca7-000f-5000-b000-15cd1f3ddcde','2025-12-03 21:22:39'),(40,84,NULL,4000.00,'pickup',0.00,NULL,3,'paid','2025-12-03 17:56:23','2025-12-03 17:57:15','2025-12-03 17:57:15',NULL,'30c28da0-000f-5000-8000-187844c81aab','2025-12-03 21:26:48'),(41,84,NULL,1000.00,'pickup',0.00,NULL,3,'paid','2025-12-04 14:40:17','2025-12-04 15:20:03','2025-12-04 15:20:03',NULL,'30c3b12b-000f-5000-8000-1ddf8e8a1d62','2025-12-04 18:10:43'),(42,84,NULL,4500.00,'delivery',750.00,42,NULL,'paid','2025-12-04 18:47:14','2025-12-04 18:48:13','2025-12-04 18:48:13',NULL,'30c3eb24-000f-5000-b000-16c541f517d0','2025-12-04 22:18:04'),(43,84,NULL,3750.00,'delivery',750.00,43,NULL,'paid','2025-12-05 12:14:31','2025-12-05 12:15:00','2025-12-05 12:15:00',NULL,'30c4e074-000f-5000-b000-1e81b100633b','2025-12-05 15:44:44'),(44,NULL,'cart_69386953c16d74.68407669',1000.00,NULL,0.00,NULL,NULL,'cart','2025-12-09 18:24:20','2025-12-09 18:24:20',NULL,NULL,NULL,NULL),(45,84,NULL,1750.00,'delivery',750.00,43,NULL,'paid','2025-12-09 18:25:51','2025-12-13 07:57:14','2025-12-13 07:57:14',NULL,'30cf3010-000f-5001-8000-1cd07444ee97','2025-12-13 11:27:04'),(46,84,NULL,3000.00,'pickup',0.00,NULL,1,'cancelled','2025-12-13 07:57:33','2025-12-13 08:09:45',NULL,'2025-12-13 08:09:45','30cf3037-000f-5001-8000-1d3b634b3cd0','2025-12-13 11:27:43'),(47,84,NULL,1750.00,'delivery',750.00,42,NULL,'paid','2025-12-13 08:09:13','2025-12-14 09:22:21','2025-12-14 09:22:21',NULL,'30d09585-000f-5000-b000-15ef316c932a','2025-12-14 12:52:13'),(48,84,NULL,2750.00,'delivery',750.00,40,NULL,'paid','2025-12-16 14:46:13','2025-12-16 14:46:33','2025-12-16 14:46:33',NULL,'30d38481-000f-5001-9000-10ce1a98714c','2025-12-16 18:16:25'),(49,84,NULL,1000.00,'pickup',0.00,NULL,3,'cancelled','2025-12-16 14:46:48','2025-12-16 15:49:08',NULL,'2025-12-16 15:49:08','30d384ab-000f-5001-8000-15c9bb304632','2025-12-16 18:17:07'),(50,84,NULL,1000.00,'pickup',0.00,NULL,3,'paid','2025-12-16 14:47:28','2025-12-16 14:47:50','2025-12-16 14:47:50',NULL,'30d384c7-000f-5000-b000-1465f9df5000','2025-12-16 18:17:35'),(51,84,NULL,6500.00,'delivery',0.00,40,NULL,'paid','2025-12-17 15:56:15','2025-12-17 15:58:31','2025-12-17 15:58:31',NULL,'30d4e6dd-000f-5001-8000-1d6a4df5b91f','2025-12-17 19:28:21'),(52,84,NULL,1000.00,'pickup',0.00,NULL,2,'paid','2025-12-17 15:58:50','2025-12-17 15:59:09','2025-12-17 15:59:09',NULL,'30d4e707-000f-5001-9000-100ef8eff328','2025-12-17 19:29:03'),(53,84,NULL,1000.00,'pickup',0.00,NULL,1,'paid','2025-12-17 15:59:23','2025-12-17 15:59:55','2025-12-17 15:59:55',NULL,'30d4e725-000f-5000-8000-1a807ad08e44','2025-12-17 19:29:33'),(54,84,NULL,3000.00,'pickup',0.00,NULL,2,'paid','2025-12-17 16:00:18','2025-12-17 16:00:47','2025-12-17 16:00:47',NULL,'30d4e767-000f-5001-9000-1a6602224303','2025-12-17 19:30:39'),(55,84,NULL,1750.00,'delivery',750.00,41,NULL,'paid','2025-12-24 20:23:12','2025-12-27 09:11:31','2025-12-27 09:11:31',NULL,'30e1b50e-000f-5001-8000-185113fec0f5','2025-12-27 09:35:18'),(57,84,NULL,3000.00,'pickup',0.00,NULL,3,'cancelled','2025-12-27 09:12:15','2025-12-27 09:16:09',NULL,'2025-12-27 09:16:09','30e1b6b8-000f-5001-9000-1e0a882fc752','2025-12-27 09:42:24'),(58,84,NULL,3000.00,'pickup',0.00,NULL,1,'cancelled','2025-12-27 09:12:37','2025-12-27 09:16:16',NULL,'2025-12-27 09:16:16','30e1b6dc-000f-5001-9000-1e19f97efb63','2025-12-27 09:43:00'),(59,84,NULL,17250.00,'pickup',0.00,NULL,2,'cancelled','2025-12-27 09:16:22','2025-12-27 09:17:20',NULL,'2025-12-27 09:17:20','30e1b7bb-000f-5000-b000-167a62b6b8be','2025-12-27 09:46:43'),(60,84,NULL,16500.00,'pickup',0.00,NULL,2,'cancelled','2025-12-27 09:16:58','2025-12-27 09:21:58',NULL,'2025-12-27 09:21:58','30e1b7f2-000f-5001-8000-1235f3ecfdfb','2025-12-27 09:47:38'),(61,84,NULL,15750.00,'pickup',0.00,NULL,1,'paid','2025-12-27 09:22:06','2025-12-27 09:24:32','2025-12-27 09:24:32',NULL,'30e1b91a-000f-5001-9000-15769dd5e569','2025-12-27 09:52:34'),(62,84,NULL,1750.00,'delivery',750.00,41,NULL,'paid','2025-12-27 09:27:23','2025-12-27 09:28:14','2025-12-27 09:28:14',NULL,'30e1ba4d-000f-5000-b000-19092fdcb5bd','2025-12-27 09:57:42'),(63,84,NULL,1000.00,'pickup',0.00,NULL,2,'paid','2025-12-27 09:31:40','2025-12-29 19:56:35','2025-12-29 19:56:35',NULL,'30e1bb45-000f-5001-9000-1f2cf0d39e8d','2025-12-27 10:01:49'),(64,84,NULL,10000.00,'delivery',0.00,42,NULL,'paid','2025-12-27 09:41:41','2025-12-27 09:44:05','2025-12-27 09:44:05',NULL,'30e1be19-000f-5001-8000-1c37f2e921ab','2025-12-27 10:13:54'),(65,84,NULL,4750.00,'delivery',750.00,42,NULL,'paid','2025-12-27 09:46:14','2025-12-27 09:47:17','2025-12-27 09:47:17',NULL,'30e1bec0-000f-5001-8000-1bd946ab3a59','2025-12-27 10:16:41'),(66,84,NULL,1750.00,'delivery',750.00,42,NULL,'paid','2025-12-27 09:49:23','2025-12-27 09:49:59','2025-12-27 09:49:59',NULL,'30e1bf7b-000f-5000-b000-15800cd311ad','2025-12-27 10:19:48'),(67,84,NULL,4000.00,'pickup',0.00,NULL,2,'cancelled','2025-12-27 09:50:26','2025-12-27 12:30:30',NULL,'2025-12-27 12:30:30','30e1e451-000f-5001-8000-1e4a0bc8e3cd','2025-12-27 12:56:58'),(68,84,NULL,1000.00,'pickup',0.00,NULL,1,'cancelled','2025-12-27 12:30:06','2025-12-27 12:30:36',NULL,'2025-12-27 12:30:36','30e1e518-000f-5001-9000-13d5a6dfe9aa','2025-12-27 13:00:17'),(69,84,NULL,1000.00,'pickup',0.00,NULL,1,'paid','2025-12-27 12:31:03','2025-12-27 12:33:08','2025-12-27 12:33:08',NULL,'30e1e54e-000f-5000-b000-1ab5871059ab','2025-12-27 13:01:11'),(70,84,NULL,1000.00,'pickup',0.00,NULL,1,'paid','2025-12-27 12:40:33','2025-12-27 12:40:54','2025-12-27 12:40:54',NULL,'30e1e78a-000f-5000-b000-18cd352ced67','2025-12-27 13:10:43'),(71,84,NULL,5000.00,'delivery',0.00,46,NULL,'cancelled','2025-12-27 12:40:58','2025-12-29 13:42:02',NULL,'2025-12-29 13:42:02','30e1e7a6-000f-5001-8000-14eac36644d2','2025-12-27 13:11:11'),(72,84,NULL,5000.00,'delivery',0.00,46,NULL,'paid','2025-12-27 13:01:51','2025-12-29 22:45:16','2025-12-29 22:45:16',NULL,'30e5182c-000f-5001-8000-140424d41aa6','2025-12-29 23:15:03'),(73,NULL,'cart_694972ed159415.88451108',11750.00,NULL,0.00,NULL,NULL,'cart','2025-12-27 20:56:44','2025-12-29 22:44:33',NULL,NULL,NULL,NULL),(74,84,NULL,1000.00,'pickup',0.00,NULL,2,'paid','2025-12-29 22:45:35','2025-12-29 22:45:54','2025-12-29 22:45:54',NULL,'30e51856-000f-5001-8000-125da85ee5ee','2025-12-29 23:15:45'),(75,84,NULL,11000.00,NULL,0.00,NULL,NULL,'cart','2025-12-29 22:58:13','2025-12-29 22:58:34',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`image_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,'/img_products\\1\\bar-20kg-griff-20-1-1.png'),(2,1,'/img_products\\1\\bar-20kg-griff-20-1-2.png'),(3,1,'/img_products\\1\\bar-20kg-griff-20-1-3.png'),(4,1,'/img_products\\1\\bar-20kg-griff-20-1-4.png'),(5,2,'/img_products\\2\\plate-d10cm-10kg-griff-20-2-1.png'),(6,2,'/img_products\\2\\plate-d10cm-10kg-griff-20-2-2.png'),(7,2,'/img_products\\2\\plate-d10cm-10kg-griff-20-2-3.png'),(8,2,'/img_products\\2\\plate-d10cm-10kg-griff-20-2-4.png'),(9,1,'/img_products\\1\\bar-20kg-griff-20-1-5.png');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_order`
--

DROP TABLE IF EXISTS `product_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_order` (
  `product_id` int NOT NULL,
  `order_id` int NOT NULL,
  `amount` int NOT NULL,
  PRIMARY KEY (`product_id`,`order_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `product_order_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `product_order_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_order`
--

LOCK TABLES `product_order` WRITE;
/*!40000 ALTER TABLE `product_order` DISABLE KEYS */;
INSERT INTO `product_order` VALUES (1,59,1),(1,60,1),(1,61,1),(1,75,2),(2,39,4),(2,40,4),(2,41,1),(2,42,3),(2,43,3),(2,44,1),(2,45,1),(2,46,3),(2,47,1),(2,48,2),(2,49,1),(2,50,1),(2,51,5),(2,52,1),(2,53,1),(2,54,3),(2,55,1),(2,57,3),(2,58,3),(2,59,10),(2,60,10),(2,61,10),(2,62,1),(2,63,1),(2,64,10),(2,65,4),(2,66,1),(2,67,4),(2,68,1),(2,69,1),(2,70,1),(2,71,5),(2,72,5),(2,73,5),(2,74,1),(2,75,1),(3,51,1),(3,73,1),(5,73,1),(6,42,1),(6,51,1),(6,59,3),(6,60,2),(6,61,1);
/*!40000 ALTER TABLE `product_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `price` int NOT NULL,
  `vat_code` tinyint NOT NULL DEFAULT '4',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  FULLTEXT KEY `name` (`name`,`description`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'bar-20kg-griff-20-1','Гриф для штанги 20 кг GriFF-20',5000,4,'Гриф для штанги 20 кг GriFF-20 – надёжная основа ваших тренировок\r\n                        <br><br>\r\n                        Гриф для штанги GriFF-20 – это профессиональный спортивный снаряд, созданный для интенсивных силовых тренировок в зале и дома. Изготовленный из высококачественной стали с антикоррозийным покрытием, гриф обладает исключительной прочностью и долговечностью, выдерживая серьёзные нагрузки.\r\n                        <br><br>\r\n                        Идеально подходит для базовых упражнений: жима лёжа, становой тяги, приседаний и других силовых элементов.\r\n                        <br><br>\r\n                        Ключевые особенности:<br>\r\n                        Вес: 20 кг – оптимальный вариант для тренировок с серьёзными весами<br>\r\n                        Длина: 220 см (стандартный олимпийский размер)<br>\r\n                        Диаметр грифа: 28-30 мм (удобный хват для большинства спортсменов)<br>\r\n                        Нагрузка: выдерживает до 300 кг (подходит для пауэрлифтинга и кроссфита)<br>\r\n                        Покрытие: матовая антискользящая обработка для надёжного хвата<br>\r\n                        Резьба на концах: надёжно фиксирует диски, предотвращая их соскальзывание<br>\r\n                        Совместимость: подходит для стандартных олимпийских блинов (диаметр посадочного отверстия 50 мм)\r\n                        <br><br>\r\n                        Преимущества:<br>\r\n                        Универсальность – подходит для различных упражнений и программ тренировок<br>\r\n                        Прочность – усиленная конструкция гарантирует безопасность при работе с большими весами<br>\r\n                        Комфортный хват – рифлёные участки в зоне захвата улучшают сцепление с ладонями<br>\r\n                        Долгий срок службы – устойчивость к износу и деформации\r\n                        <br><br>\r\n                        Для кого:<br>\r\n                        Гриф GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, фитнес-энтузиастов, а также для тренеров и спортивных залов.\r\n                        <br><br>\r\n                        Комплектация:<br>\r\n                        Гриф для штанги GriFF-20 – 1 шт.\r\n                        <br><br>\r\n                        Дополнительно:<br>\r\n                        Для максимальной эффективности тренировок рекомендуем использовать гриф вместе с качественными дисками и замками.\r\n                        <br><br>\r\n                        Улучшите свои силовые показатели с GriFF-20 – стальным стержнем вашего прогресса!','2025-12-26 20:51:05','2025-12-26 20:51:05'),(2,2,'plate-d10cm-10kg-griff-20-2','Блин D 10 см 10 кг GriFF-20',1000,4,'Блин для штанги 10 кг GriFF-20 – идеальный утяжелитель для силовых тренировок\r\n                        <br><br>\r\n                        Олимпийский блин GriFF-20 – это профессиональный спортивный снаряд, созданный для интенсивных силовых тренировок в зале и дома. Изготовленный из высококачественного чугуна с защитным покрытием, блин обладает исключительной прочностью и долговечностью, выдерживая серьёзные нагрузки.\r\n                        <br><br>\r\n                        Идеально подходит для базовых упражнений: жима лёжа, становой тяги, приседаний и других силовых элементов.\r\n                        <br><br>\r\n                        Ключевые особенности:<br>\r\n                        Вес: 10 кг – оптимальный вариант для прогрессивного увеличения нагрузки<br>\r\n                        Диаметр: 45 см (стандартный олимпийский размер)<br>\r\n                        Посадочное отверстие: 50 мм (стандартный олимпийский размер)<br>\r\n                        Материал: высокопрочный чугун с антикоррозийным покрытием<br>\r\n                        Покрытие: износостойкая краска с чёткой маркировкой веса<br>\r\n                        Точность веса: калибровка согласно международным стандартам\r\n                        <br><br>\r\n                        Преимущества:<br>\r\n                        Универсальность – подходит для различных упражнений и программ тренировок<br>\r\n                        Прочность – усиленная конструкция гарантирует безопасность при работе с большими весами<br>\r\n                        Компактность – удобное хранение и транспортировка<br>\r\n                        Долгий срок службы – устойчивость к износу и деформации<br>\r\n                        Чёткая маркировка – легко идентифицировать вес среди других блинов\r\n                        <br><br>\r\n                        Для кого:<br>\r\n                        Блин GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, фитнес-энтузиастов, а также для тренеров и спортивных залов.\r\n                        <br><br>\r\n                        Комплектация:<br>\r\n                        Блин для штанги GriFF-20 – 1 шт.\r\n                        <br><br>\r\n                        Дополнительно:<br>\r\n                        Для максимальной эффективности тренировок рекомендуем использовать блины вместе с качественным олимпийским грифом и надёжными замками.\r\n                        <br><br>\r\n                        Улучшите свои силовые показатели с GriFF-20 – надёжным партнёром вашего прогресса!','2025-12-26 20:51:05','2025-12-26 20:51:05'),(3,2,'plate-d10cm-5kg-griff-20-3','Блин D 10 см 5 кг GriFF-20',750,4,NULL,'2025-12-26 20:51:05','2025-12-26 20:51:05'),(4,4,'stand-for-bar-griff-stand-4','Стойка для штанги GriFF-Stand',7000,4,NULL,'2025-12-26 20:51:05','2025-12-26 20:51:05'),(5,5,'adjustable-bench-layxxl-5','Скамья регулируемая LayXXL',6000,4,NULL,'2025-12-26 20:51:05','2025-12-26 20:51:05'),(6,8,'whey-protein-breero-2-5kg-6','Сывороточный протеин Breero 2.5 кг',750,3,NULL,'2025-12-26 20:51:05','2025-12-26 20:51:05'),(7,7,'lifting-belt-gachi-power-xl-7','Лифтёрский пояс Gachi-Power XL',750,4,NULL,'2025-12-26 20:51:05','2025-12-26 20:51:05');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `work_hours` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `coordinates` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stores`
--

LOCK TABLES `stores` WRITE;
/*!40000 ALTER TABLE `stores` DISABLE KEYS */;
INSERT INTO `stores` VALUES (1,'Р-н Люблино','Москва, Ул. Головачёва, вл8с1','+7 000 000 00 00','10:00 - 23:00 (будние дни)<br>10:00 - 21:00 (выходные и праздники)','55.666208, 37.816980'),(2,'Р-н Отрадное','Москва, Ул. Станционная, 11','+7 000 000 00 00','10:00 - 22:00 (будние дни)<br>10:00 - 21:00 (выходные и праздники)','55.846623, 37.600648'),(3,'Г. Дмитров','Дмитров, Историческая площадь, 5','+7 000 000 00 00','10:00 - 23:00 (будние дни)<br>10:00 - 21:00 (выходные и праздники)','56.345304, 37.520384');
/*!40000 ALTER TABLE `stores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login (phone number)` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (84,'+79778743115','$2y$10$/fDxf3s5DU7qlaOJjetCsOrrdkZd9uOVyboHQ1qAcnNW2CGV8rVjm','Илья Сладков'),(88,'+79778743116','$2y$10$UKU7xeU6BeWVu8GIuGZgDO6UrVdc2SQQKFTmNbfegy42A7u0KAXEO','ИльяИлья');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-30 21:55:08
