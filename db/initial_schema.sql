-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: gymboss_db
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `cart_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int unsigned NOT NULL,
  PRIMARY KEY (`cart_id`,`product_id`),
  KEY `idx_cart_items_product_id` (`product_id`),
  CONSTRAINT `fk_cart_items_carts` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_cart_items_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `chk_cart_items_quantity_positive` CHECK ((`quantity` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_converted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_carts_user_id` (`user_id`),
  KEY `idx_carts_session_id` (`session_id`),
  CONSTRAINT `fk_carts_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=67598 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
-- Table structure for table `delivery_types`
--

DROP TABLE IF EXISTS `delivery_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_delivery_types_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_types`
--

LOCK TABLES `delivery_types` WRITE;
/*!40000 ALTER TABLE `delivery_types` DISABLE KEYS */;
INSERT INTO `delivery_types` VALUES (1,'courier','Курьерская доставка'),(2,'pickup','Самовывоз');
/*!40000 ALTER TABLE `delivery_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_verification_tokens`
--

DROP TABLE IF EXISTS `email_verification_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_verification_tokens` (
  `user_id` int NOT NULL,
  `token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_email_verification_tokens_token` (`token`),
  CONSTRAINT `fk_email_verification_tokens_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_verification_tokens`
--

LOCK TABLES `email_verification_tokens` WRITE;
/*!40000 ALTER TABLE `email_verification_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_verification_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(254) NOT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_email_attempted_at` (`email`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `quantity` int unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `vat_code` tinyint NOT NULL,
  PRIMARY KEY (`product_id`,`order_id`),
  KEY `idx_order_items_order_id` (`order_id`),
  CONSTRAINT `fk_order_items_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `fk_order_items_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_order_items_price_non_negative` CHECK ((`price` >= 0)),
  CONSTRAINT `chk_order_items_quantity_positive` CHECK ((`quantity` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_statuses`
--

DROP TABLE IF EXISTS `order_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_statuses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_statuses_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_statuses`
--

LOCK TABLES `order_statuses` WRITE;
/*!40000 ALTER TABLE `order_statuses` DISABLE KEYS */;
INSERT INTO `order_statuses` VALUES (1,'pending_payment','Ожидает оплаты'),(2,'paid','Оплачен'),(3,'shipped','Отправлен'),(4,'ready_for_pickup','Готов к получению'),(5,'completed','Завершен'),(6,'canceled','Отменен');
/*!40000 ALTER TABLE `order_statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `total_quantity` int NOT NULL DEFAULT '0',
  `total_price` decimal(10,2) NOT NULL,
  `delivery_type_id` int DEFAULT NULL,
  `delivery_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_address_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `delivery_postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `store_id` int DEFAULT NULL,
  `status_id` int NOT NULL DEFAULT '1',
  `courier_delivery_from` datetime DEFAULT NULL,
  `courier_delivery_to` datetime DEFAULT NULL,
  `ready_for_pickup_from` datetime DEFAULT NULL,
  `ready_for_pickup_to` datetime DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `canceled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_user_id` (`user_id`),
  KEY `idx_orders_store_id` (`store_id`),
  KEY `idx_orders_delivery_type_id` (`delivery_type_id`),
  KEY `idx_orders_status_id` (`status_id`),
  KEY `idx_orders_user_id_status_id` (`user_id`,`status_id`),
  KEY `idx_orders_created_at` (`created_at`),
  KEY `idx_orders_status_id_created_at` (`status_id`,`created_at`),
  CONSTRAINT `fk_orders_delivery_types` FOREIGN KEY (`delivery_type_id`) REFERENCES `delivery_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_orders_order_statuses` FOREIGN KEY (`status_id`) REFERENCES `order_statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_orders_stores` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_orders_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `chk_orders_delivery_cost_non_negative` CHECK ((`delivery_cost` >= 0)),
  CONSTRAINT `chk_orders_total_price_non_negative` CHECK ((`total_price` >= 0)),
  CONSTRAINT `chk_orders_total_quantity_non_negative` CHECK ((`total_quantity` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=190 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `token` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`),
  UNIQUE KEY `uq_password_reset_tokens_token` (`token`),
  CONSTRAINT `fk_password_reset_tokens_users` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'pending',
  `amount` decimal(10,2) NOT NULL,
  `provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'yookassa',
  `provider_status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `external_payment_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `idempotency_key` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `confirmation_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `error_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `error_message` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_idempotency` (`idempotency_key`),
  UNIQUE KEY `uq_payments_provider_external_payment_id` (`provider`,`external_payment_id`),
  KEY `idx_payments_order_id` (`order_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_status_expires_at` (`status`,`expires_at`),
  CONSTRAINT `fk_payments_orders` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_payments_amount_positive` CHECK ((`amount` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product_id` (`product_id`),
  CONSTRAINT `fk_product_images_products` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,'/assets/images/products/1/1.png'),(2,1,'/assets/images/products/1/2.png'),(3,1,'/assets/images/products/1/3.png'),(4,1,'/assets/images/products/1/4.png'),(5,2,'/assets/images/products/2/1.png'),(6,2,'/assets/images/products/2/2.png'),(7,2,'/assets/images/products/2/3.png'),(9,1,'/assets/images/products/1/5.png'),(10,3,'/assets/images/products/3/1.png'),(11,3,'/assets/images/products/3/2.png'),(13,3,'/assets/images/products/3/3.png'),(14,9,'/assets/images/products/9/1.png'),(15,9,'/assets/images/products/9/2.png'),(16,9,'/assets/images/products/9/3.png'),(17,10,'/assets/images/products/10/1.png'),(18,10,'/assets/images/products/10/2.png'),(19,10,'/assets/images/products/10/3.png'),(20,11,'/assets/images/products/11/1.png'),(21,11,'/assets/images/products/11/2.png'),(22,11,'/assets/images/products/11/3.png'),(23,11,'/assets/images/products/11/4.png'),(24,12,'/assets/images/products/12/1.png'),(25,12,'/assets/images/products/12/2.png'),(26,12,'/assets/images/products/12/3.png'),(27,12,'/assets/images/products/12/4.png'),(28,13,'/assets/images/products/13/1.png'),(29,13,'/assets/images/products/13/2.png'),(30,13,'/assets/images/products/13/3.png'),(31,13,'/assets/images/products/13/4.png'),(32,13,'/assets/images/products/13/5.png'),(33,14,'/assets/images/products/14/1.png'),(34,14,'/assets/images/products/14/2.png'),(35,14,'/assets/images/products/14/3.png'),(36,15,'/assets/images/products/15/1.png'),(37,15,'/assets/images/products/15/2.png'),(38,15,'/assets/images/products/15/3.png'),(39,5,'/assets/images/products/5/1.png'),(40,5,'/assets/images/products/5/2.png'),(41,5,'/assets/images/products/5/3.png'),(42,5,'/assets/images/products/5/4.png'),(43,5,'/assets/images/products/5/5.png'),(44,16,'/assets/images/products/16/1.png'),(45,16,'/assets/images/products/16/2.png'),(46,16,'/assets/images/products/16/3.png'),(47,17,'/assets/images/products/17/1.png'),(48,17,'/assets/images/products/17/2.png'),(49,17,'/assets/images/products/17/3.png'),(50,17,'/assets/images/products/17/4.png'),(51,6,'/assets/images/products/6/1.png'),(52,6,'/assets/images/products/6/2.png'),(53,18,'/assets/images/products/18/1.png'),(54,18,'/assets/images/products/18/2.png'),(63,4,'/assets/images/products/4/1.png'),(64,4,'/assets/images/products/4/2.png'),(65,4,'/assets/images/products/4/3.png'),(67,19,'/assets/images/products/19/1.png'),(68,19,'/assets/images/products/19/2.png'),(69,7,'/assets/images/products/7/1.png');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `vat_code` tinyint NOT NULL DEFAULT '4',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category_id` (`category_id`),
  KEY `idx_products_price` (`price`),
  KEY `idx_products_category_id_price` (`category_id`,`price`),
  FULLTEXT KEY `ft_products_name_description` (`name`,`description`),
  CONSTRAINT `fk_products_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `chk_products_price_positive` CHECK ((`price` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,'bar-20kg-griff-20','Гриф для штанги 20 кг GriFF-20',5000.00,4,'Гриф для штанги 20 кг GriFF-20 – надёжная основа ваших тренировок\n\nГриф для штанги GriFF-20 – это профессиональный спортивный снаряд, созданный для интенсивных силовых тренировок в зале и дома. Изготовленный из высококачественной стали с антикоррозийным покрытием, гриф обладает исключительной прочностью и долговечностью, выдерживая серьёзные нагрузки.\n\nИдеально подходит для базовых упражнений: жима лёжа, становой тяги, приседаний и других силовых элементов.\n\nКлючевые особенности:\nВес: 20 кг – оптимальный вариант для тренировок с серьёзными весами\nДлина: 220 см (стандартный олимпийский размер)\nДиаметр грифа: 28-30 мм (удобный хват для большинства спортсменов)\nНагрузка: выдерживает до 300 кг (подходит для пауэрлифтинга и кроссфита)\nПокрытие: матовая антискользящая обработка для надёжного хвата\nРезьба на концах: надёжно фиксирует диски, предотвращая их соскальзывание\nСовместимость: подходит для стандартных олимпийских блинов (диаметр посадочного отверстия 50 мм)\n\nПреимущества:\nУниверсальность – подходит для различных упражнений и программ тренировок\nПрочность – усиленная конструкция гарантирует безопасность при работе с большими весами\nКомфортный хват – рифлёные участки в зоне захвата улучшают сцепление с ладонями\nДолгий срок службы – устойчивость к износу и деформации\n\nДля кого:\nГриф GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, фитнес-энтузиастов, а также для тренеров и спортивных залов.\n\nКомплектация:\nГриф для штанги GriFF-20 – 1 шт.\n\nДополнительно:\nДля максимальной эффективности тренировок рекомендуем использовать гриф вместе с качественными дисками и замками.\n\nУлучшите свои силовые показатели с GriFF-20 – стальным стержнем вашего прогресса!','2025-12-26 20:51:05','2025-12-26 20:51:05'),(2,2,'plate-d10cm-10kg-griff-20','Блин D 10 см 10 кг GriFF-20',1000.00,4,'Блин для штанги 10 кг GriFF-20 – идеальный утяжелитель для силовых тренировок\n\nОлимпийский блин GriFF-20 – это профессиональный спортивный снаряд, созданный для интенсивных силовых тренировок в зале и дома. Изготовленный из высококачественного чугуна с защитным покрытием, блин обладает исключительной прочностью и долговечностью, выдерживая серьёзные нагрузки.\n\nИдеально подходит для базовых упражнений: жима лёжа, становой тяги, приседаний и других силовых элементов.\n\nКлючевые особенности:\nВес: 10 кг – оптимальный вариант для прогрессивного увеличения нагрузки\nДиаметр: 45 см (стандартный олимпийский размер)\nПосадочное отверстие: 50 мм (стандартный олимпийский размер)\nМатериал: высокопрочный чугун с антикоррозийным покрытием\nПокрытие: износостойкая краска с чёткой маркировкой веса\nТочность веса: калибровка согласно международным стандартам\n\nПреимущества:\nУниверсальность – подходит для различных упражнений и программ тренировок\nПрочность – усиленная конструкция гарантирует безопасность при работе с большими весами\nКомпактность – удобное хранение и транспортировка\nДолгий срок службы – устойчивость к износу и деформации\nЧёткая маркировка – легко идентифицировать вес среди других блинов\n\nДля кого:\nБлин GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, фитнес-энтузиастов, а также для тренеров и спортивных залов.\n\nКомплектация:\nБлин для штанги GriFF-20 – 1 шт.\n\nДополнительно:\nДля максимальной эффективности тренировок рекомендуем использовать блины вместе с качественным олимпийским грифом и надёжными замками.\n\nУлучшите свои силовые показатели с GriFF-20 – надёжным партнёром вашего прогресса!','2025-12-26 20:51:05','2025-12-26 20:51:05'),(3,2,'plate-d10cm-5kg-griff-20','Блин D 10 см 5 кг GriFF-20',750.00,4,'Блин для штанги 5 кг GriFF-20 – надёжный утяжелитель для ваших тренировок\r\n\r\nБлин GriFF-20 весом 5 кг – это профессиональный спортивный снаряд, созданный для эффективных силовых тренировок в зале и дома. Изготовленный из высококачественного чугуна с защитным покрытием, блин обладает исключительной прочностью и долговечностью, выдерживая интенсивные нагрузки.\r\n\r\nИдеально подходит для базовых упражнений: жима лёжа, становой тяги, приседаний и других силовых элементов, а также для точной настройки рабочего веса.\r\n\r\nКлючевые особенности:\r\n\r\nВес: 5 кг – оптимальный вариант для плавного прогрессивного увеличения нагрузки\r\n\r\nДиаметр: 10 см (компактный размер для удобного хранения и использования)\r\n\r\nПосадочное отверстие: 50 мм (стандартный олимпийский размер)\r\n\r\nМатериал: высокопрочный чугун с антикоррозийным покрытием\r\n\r\nПокрытие: износостойкая краска с чёткой маркировкой веса\r\n\r\nТочность веса: калибровка согласно международным стандартам\r\n\r\nПреимущества:\r\n\r\nУниверсальность – подходит для различных упражнений и программ тренировок\r\n\r\nПрочность – усиленная конструкция гарантирует безопасность при работе с большими весами\r\n\r\nКомпактность – небольшой диаметр обеспечивает удобное хранение и транспортировку\r\n\r\nДолгий срок службы – устойчивость к износу и деформации\r\n\r\nЧёткая маркировка – легко идентифицировать вес среди других блинов\r\n\r\nТочная балансировка – равномерное распределение веса для комфортных тренировок\r\n\r\nДля кого:\r\n\r\nБлин GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, фитнес-энтузиастов, а также для тренеров и спортивных залов.\r\n\r\nКомплектация:\r\n\r\nБлин для штанги GriFF-20 – 1 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать блины вместе с качественным олимпийским грифом и надёжными замками.\r\n\r\nУлучшите свои силовые показатели с GriFF-20 – надёжным партнёром вашего прогресса!','2025-12-26 20:51:05','2026-04-24 20:07:46'),(4,4,'stand-for-bar-griff-stand','Силовая рама для штанги GriFF-Stand',7000.00,4,'Силовая рама для штанги GriFF-Stand – профессиональная силовая рама для безопасных тренировок\r\n\r\nСиловая стойка GriFF-Stand – это профессиональный спортивный снаряд, созданный для интенсивных силовых тренировок в зале и дома. Изготовленная из высокопрочной стали с антикоррозийным покрытием, стойка обладает исключительной устойчивостью и долговечностью, выдерживая максимальные нагрузки на протяжении многих лет использования.\r\n\r\nИдеально подходит для выполнения базовых упражнений со штангой: приседаний, жима стоя, жима лёжа со скамьи, тяг, подтягиваний и других силовых элементов.\r\n\r\nКлючевые особенности:\r\n\r\nКонструкция: усиленная стальная рама с цельносварными узлами и антикоррозийным покрытием\r\n\r\nРегулировка высоты: множество позиций крюков под рост спортсмена и тип упражнения\r\n\r\nСтраховочные упоры: горизонтальные рычаги для защиты при отказе мышц\r\n\r\nJ-крюки: усиленные держатели штанги с защитной вставкой против повреждения грифа\r\n\r\nПерекладина для подтягиваний: интегрирована в верхнюю часть конструкции\r\n\r\nМаксимальная нагрузка: выдерживает до 450 кг (рабочий вес штанги)\r\n\r\nСечение профиля: квадратная труба увеличенной толщины для максимальной жёсткости\r\n\r\nГабариты: оптимальные размеры рабочей зоны для комфортного выполнения упражнений\r\n\r\nОпоры: широкая база с прорезиненными ножками или отверстиями для анкерного крепления к полу\r\n\r\nПреимущества:\r\n\r\nБезопасность – страховочные упоры позволяют тренироваться в одиночку даже с предельными весами\r\n\r\nУниверсальность – одна стойка заменяет несколько тренажёров для разных упражнений\r\n\r\nУстойчивость – массивная конструкция и широкая база исключают раскачивание и опрокидывание\r\n\r\nУдобство – быстрая регулировка высоты крюков и страховочных упоров под любое упражнение\r\n\r\nЗащита оборудования – вставки на J-крюках предотвращают повреждение грифа\r\n\r\nПрочность – усиленные сварные швы и толстостенный профиль гарантируют надёжность\r\n\r\nДолгий срок службы – устойчивость к износу, деформации и интенсивной эксплуатации\r\n\r\nБонус – встроенная перекладина расширяет возможности тренировок\r\n\r\nДля кого:\r\n\r\nСиловая стойка GriFF-Stand предназначена для спортсменов разного уровня подготовки – от любителей до профессионалов. Она отлично подходит для пауэрлифтеров, бодибилдеров, кроссфитеров, тяжелоатлетов, фитнес-энтузиастов, а также для тренеров и оборудования спортивных залов и домашних тренажёрных комнат.\r\n\r\nКомплектация:\r\n\r\nСиловая рама для штанги GriFF-Stand – 1 шт.\r\n\r\nJ-крюки – 2 шт.\r\n\r\nСтраховочные упоры – 2 шт.\r\n\r\nКрепёжные элементы и инструкция по сборке\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать стойку вместе с качественным олимпийским грифом, блинами, замками и скамьёй.\r\n\r\nПокоряйте новые вершины с GriFF-Stand – стальной опорой вашего прогресса!','2025-12-26 20:51:05','2026-04-25 14:32:22'),(5,5,'adjustable-bench-layxxl','Скамья регулируемая LayXXL',6000.00,4,'Скамья регулируемая LayXXL – универсальная основа для эффективных силовых тренировок\r\n\r\nРегулируемая скамья LayXXL – это профессиональный спортивный снаряд, созданный для комплексных силовых тренировок в зале и дома. Изготовленная из высокопрочной стали с антикоррозийным покрытием, скамья обладает исключительной устойчивостью и долговечностью, выдерживая серьёзные нагрузки на протяжении многих лет использования.\r\n\r\nИдеально подходит для широкого спектра упражнений: жима лёжа, жима под углом, разведений гантелей, упражнений на пресс, подъёмов на бицепс с упором и других силовых элементов.\r\n\r\nКлючевые особенности:\r\n\r\nКонструкция: усиленная стальная рама с антикоррозийным покрытием\r\n\r\nРегулировка спинки: несколько положений – от отрицательного угла до вертикального\r\n\r\nРегулировка сиденья: изменяемый угол наклона для правильной фиксации тела\r\n\r\nМаксимальная нагрузка: выдерживает до 400 кг (вес пользователя + рабочий вес)\r\n\r\nОбивка: плотный наполнитель высокой жёсткости с износостойким покрытием\r\n\r\nРазмеры: увеличенная длина и ширина сиденья для комфорта спортсменов любой комплекции\r\n\r\nОпоры: широкие прорезиненные ножки для максимальной устойчивости\r\n\r\nТранспортировочные ролики: для удобного перемещения по залу\r\n\r\nПреимущества:\r\n\r\nУниверсальность – множество положений спинки позволяет выполнять упражнения на все группы мышц\r\n\r\nУстойчивость – усиленная рама и широкая база исключают раскачивание при работе с большими весами\r\n\r\nКомфорт – плотная обивка и анатомичная форма обеспечивают правильную поддержку спины\r\n\r\nНадёжность – качественный механизм регулировки гарантирует фиксацию в любом положении\r\n\r\nДолгий срок службы – устойчивость к износу, деформации и интенсивной эксплуатации\r\n\r\nБезопасность – продуманная конструкция минимизирует риск травм во время тренировок\r\n\r\nМобильность – транспортировочные ролики облегчают перемещение скамьи\r\n\r\nДля кого:\r\n\r\nСкамья LayXXL предназначена для спортсменов разного уровня подготовки – от любителей до профессионалов. Она отлично подходит для пауэрлифтеров, бодибилдеров, кроссфитеров, фитнес-энтузиастов, а также для тренеров и оборудования спортивных залов и домашних тренажёрных комнат.\r\n\r\nКомплектация:\r\n\r\nСкамья регулируемая LayXXL – 1 шт.\r\n\r\nКрепёжные элементы и инструкция по сборке\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать скамью вместе с качественным олимпийским грифом, блинами и стойками для штанги.\r\n\r\nТренируйтесь без ограничений с LayXXL – надёжной основой вашего прогресса!','2025-12-26 20:51:05','2026-04-25 08:11:07'),(6,8,'whey-protein-breero-2-5kg','Сывороточный протеин Breero 2.5 кг',750.00,3,'Сывороточный протеин Breero 2,5 кг – чистый источник белка для роста мышц и восстановления\r\n\r\nСывороточный протеин Breero – это профессиональная спортивная добавка, созданная для эффективного набора мышечной массы, восстановления после тренировок и поддержания формы. Изготовленный из высококачественного молочного сырья по современным технологиям, протеин обладает высокой степенью усвоения и насыщенным составом аминокислот.\r\n\r\nИдеально подходит для спортсменов, стремящихся к увеличению мышечной массы, ускорению восстановления и поддержанию белкового баланса в организме при интенсивных физических нагрузках.\r\n\r\nКлючевые особенности:\r\n\r\nВес: 2,5 кг – оптимальный объём для длительного курса приёма\r\n\r\nСодержание белка: до 24 г на порцию – высокая концентрация чистого протеина\r\n\r\nТип белка: концентрат сывороточного протеина с быстрым усвоением\r\n\r\nАминокислотный профиль: полный набор незаменимых аминокислот, включая BCAA\r\n\r\nНизкое содержание жиров и углеводов – чистый белок без лишних калорий\r\n\r\nРастворимость: легко смешивается с водой или молоком без комков\r\n\r\nВкус: насыщенный и приятный, без излишней сладости\r\n\r\nУпаковка: герметичный пакет с зип-застёжкой для длительного хранения\r\n\r\nПреимущества:\r\n\r\nБыстрое усвоение – сывороточный протеин начинает работать уже через 30 минут после приёма\r\n\r\nРост мышечной массы – высокое содержание белка стимулирует синтез мышечных волокон\r\n\r\nУскоренное восстановление – BCAA и другие аминокислоты сокращают время восстановления после тренировок\r\n\r\nПоддержка иммунитета – содержит иммуноглобулины и лактоферрин\r\n\r\nУдобство приёма – быстро готовится в шейкере в любых условиях\r\n\r\nКонтроль аппетита – помогает поддерживать чувство сытости при работе на рельеф\r\n\r\nУниверсальность – подходит как для набора массы, так и для сохранения мышц при похудении\r\n\r\nДля кого:\r\n\r\nСывороточный протеин Breero предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для бодибилдеров, пауэрлифтеров, кроссфитеров, фитнес-энтузиастов, представителей единоборств, а также для всех, кто ведёт активный образ жизни и нуждается в дополнительном источнике белка.\r\n\r\nСпособ применения:\r\n\r\nСмешайте 1 порцию (около 30 г) с 250-300 мл воды или молока. Принимайте 1-3 раза в день: после тренировки, между приёмами пищи или утром после пробуждения.\r\n\r\nКомплектация:\r\n\r\nСывороточный протеин Breero 2,5 кг – 1 шт.\r\n\r\nМерная ложка внутри упаковки\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности рекомендуем сочетать приём протеина с регулярными силовыми тренировками и сбалансированным питанием.\r\n\r\nДостигайте новых результатов с Breero – чистым источником вашего прогресса!','2025-12-26 20:51:05','2026-04-25 09:20:32'),(7,7,'lifting-belt-gachi-power-xl','Лифтёрский пояс Gachi-Power XL',750.00,4,'Лифтёрский пояс Gachi-Power XL – максимальная поддержка для рекордных весов\r\n\r\nЛифтёрский пояс Gachi-Power XL – это профессиональный спортивный аксессуар, созданный для безопасных и эффективных силовых тренировок в зале и дома. Изготовленный из высококачественной натуральной кожи с усиленной прошивкой, пояс обладает исключительной прочностью и долговечностью, сохраняя форму на протяжении многих лет интенсивной эксплуатации.\r\n\r\nИдеально подходит для выполнения базовых упражнений с предельными весами: приседаний со штангой, становой тяги, жима стоя и других силовых элементов, требующих стабилизации корпуса.\r\n\r\nКлючевые особенности:\r\n\r\nМатериал: натуральная кожа премиум-класса с многослойной структурой\r\n\r\nШирина: 10 см по всей длине – максимальная поддержка поясничного отдела\r\n\r\nТолщина: 10-13 мм для оптимального сочетания жёсткости и комфорта\r\n\r\nЗастёжка: одинарный или двойной стальной штырь с прочной пряжкой\r\n\r\nПрошивка: усиленная многорядная прошивка по всему периметру\r\n\r\nРазмер XL: подходит для атлетов с обхватом талии 95-115 см\r\n\r\nСтандарт IPF: соответствует требованиям международной федерации пауэрлифтинга\r\n\r\nВнутренняя поверхность: замшевая подкладка для комфортного прилегания к телу\r\n\r\nПреимущества:\r\n\r\nМаксимальная стабилизация – фиксирует корпус и создаёт внутрибрюшное давление для подъёма больших весов\r\n\r\nБезопасность – снижает нагрузку на поясничный отдел позвоночника при тяжёлых упражнениях\r\n\r\nПовышение результатов – позволяет работать с большими весами в базовых движениях\r\n\r\nНадёжность – стальная пряжка и усиленная прошивка выдерживают экстремальные нагрузки\r\n\r\nДолговечность – натуральная кожа со временем становится только удобнее и не теряет свойств\r\n\r\nКомфорт – замшевая подкладка предотвращает натирание и обеспечивает плотное прилегание\r\n\r\nСоответствие стандартам – подходит для использования на соревнованиях по пауэрлифтингу\r\n\r\nУниверсальность – эффективен в любых силовых дисциплинах\r\n\r\nДля кого:\r\n\r\nЛифтёрский пояс Gachi-Power XL предназначен для серьёзных спортсменов, работающих с большими весами. Он отлично подходит для пауэрлифтеров, бодибилдеров, тяжелоатлетов, кроссфитеров, стронгменов, а также для всех, кто стремится к максимальным силовым показателям и заботится о здоровье своей спины.\r\n\r\nРазмер XL рассчитан на атлетов с обхватом талии 95-115 см.\r\n\r\nКомплектация:\r\n\r\nЛифтёрский пояс Gachi-Power XL – 1 шт.\r\n\r\nРекомендации по использованию:\r\n\r\nНадевайте пояс только при работе с весами от 70-80% от максимума. Затягивайте плотно, но так, чтобы можно было сделать полный вдох животом для создания внутрибрюшного давления. После тренировки храните пояс в сухом месте вдали от прямых солнечных лучей.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать пояс вместе с качественной экипировкой: лямками, кистевыми бинтами и наколенными бинтами.\r\n\r\nПокоряйте новые рекорды с Gachi-Power XL – кожаной броней вашего прогресса!','2025-12-26 20:51:05','2026-04-25 14:41:41'),(9,2,'plate-d10cm-25kg-griff-20','Блин D 10 см 25 кг GriFF-20',1250.00,4,'Блин для штанги 25 кг GriFF-20 – мощный утяжелитель для серьёзных тренировок\r\n\r\nОлимпийский блин GriFF-20 весом 25 кг – это профессиональный спортивный снаряд, созданный для интенсивных силовых тренировок в зале и дома. Изготовленный из высококачественного чугуна с защитным покрытием, блин обладает исключительной прочностью и долговечностью, выдерживая максимальные нагрузки.\r\n\r\nИдеально подходит для базовых упражнений: жима лёжа, становой тяги, приседаний и других силовых элементов, а также для работы с большими весами в пауэрлифтинге и тяжёлой атлетике.\r\n\r\nКлючевые особенности:\r\n\r\nВес: 25 кг – максимальный олимпийский стандарт для работы с серьёзными нагрузками\r\n\r\nДиаметр: 45 см (стандартный олимпийский размер)\r\n\r\nПосадочное отверстие: 50 мм (стандартный олимпийский размер)\r\n\r\nМатериал: высокопрочный чугун с антикоррозийным покрытием\r\n\r\nПокрытие: износостойкая краска с чёткой маркировкой веса\r\n\r\nТочность веса: калибровка согласно международным стандартам\r\n\r\nПреимущества:\r\n\r\nУниверсальность – подходит для различных упражнений и программ тренировок\r\n\r\nПрочность – усиленная конструкция гарантирует безопасность при работе с предельными весами\r\n\r\nМаксимальная нагрузка – позволяет быстро набирать рабочий вес штанги\r\n\r\nДолгий срок службы – устойчивость к износу и деформации\r\n\r\nЧёткая маркировка – легко идентифицировать вес среди других блинов\r\n\r\nТочная балансировка – равномерное распределение веса для комфортных тренировок\r\n\r\nДля кого:\r\n\r\nБлин GriFF-20 весом 25 кг предназначен для опытных спортсменов и профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, бодибилдеров, а также для тренеров и оборудования профессиональных спортивных залов.\r\n\r\nКомплектация:\r\n\r\nБлин для штанги GriFF-20 – 1 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать блины вместе с качественным олимпийским грифом и надёжными замками.\r\n\r\nУлучшите свои силовые показатели с GriFF-20 – надёжным партнёром вашего прогресса!','2026-04-24 21:15:25','2026-04-24 21:15:25'),(10,2,'plate-d10cm-2-5kg-griff-20','Блин D 10 см 2,5 кг GriFF-20',500.00,4,'Блин для штанги 2,5 кг GriFF-20 – точный утяжелитель для тонкой настройки нагрузки\r\n\r\nБлин GriFF-20 весом 2,5 кг – это профессиональный спортивный снаряд, созданный для эффективных силовых тренировок в зале и дома. Изготовленный из высококачественного чугуна с защитным покрытием, блин обладает исключительной прочностью и долговечностью, сохраняя свои качества на протяжении многих лет использования.\r\n\r\nИдеально подходит для точной корректировки рабочего веса в базовых упражнениях: жиме лёжа, становой тяге, приседаниях и других силовых элементах.\r\n\r\nКлючевые особенности:\r\n\r\nВес: 2,5 кг – идеальный вариант для плавного и точного увеличения нагрузки\r\n\r\nДиаметр: 10 см (компактный размер для удобного хранения и использования)\r\n\r\nПосадочное отверстие: 50 мм (стандартный олимпийский размер)\r\n\r\nМатериал: высокопрочный чугун с антикоррозийным покрытием\r\n\r\nПокрытие: износостойкая краска с чёткой маркировкой веса\r\n\r\nТочность веса: калибровка согласно международным стандартам\r\n\r\nПреимущества:\r\n\r\nУниверсальность – подходит для различных упражнений и программ тренировок\r\n\r\nПлавный прогресс – позволяет минимально увеличивать нагрузку для постепенного роста силовых показателей\r\n\r\nКомпактность – небольшой размер обеспечивает удобное хранение и транспортировку\r\n\r\nДолгий срок службы – устойчивость к износу и деформации\r\n\r\nЧёткая маркировка – легко идентифицировать вес среди других блинов\r\n\r\nТочная балансировка – равномерное распределение веса для комфортных тренировок\r\n\r\nДля кого:\r\n\r\nБлин GriFF-20 весом 2,5 кг предназначен для спортсменов разного уровня подготовки – от новичков до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, фитнес-энтузиастов, а также для тренеров и спортивных залов, где важна точная регулировка веса штанги.\r\n\r\nКомплектация:\r\n\r\nБлин для штанги GriFF-20 – 1 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать блины вместе с качественным олимпийским грифом и надёжными замками.\r\n\r\nУлучшите свои силовые показатели с GriFF-20 – надёжным партнёром вашего прогресса!','2026-04-24 21:41:25','2026-04-24 21:41:25'),(11,1,'curl-bar-8kg-griff-20','Z-гриф для штанги 8 кг GriFF-20',3000.00,4,'Z-гриф для штанги 8 кг GriFF-20 – идеальное решение для изолированных упражнений\r\n\r\nZ-образный гриф GriFF-20 – это профессиональный спортивный снаряд, созданный для эффективной проработки мышц рук и плечевого пояса в зале и дома. Изготовленный из высококачественной стали с антикоррозийным покрытием, гриф обладает исключительной прочностью и долговечностью, выдерживая интенсивные нагрузки.\r\n\r\nИдеально подходит для изолированных упражнений: подъёма на бицепс, французского жима, тяги к подбородку и других элементов для проработки мышц рук.\r\n\r\nКлючевые особенности:\r\n\r\nВес: 8 кг – оптимальный вариант для целевой проработки мышц рук\r\n\r\nИзогнутая форма: Z-образный профиль снижает нагрузку на запястья и локти\r\n\r\nДиаметр грифа: 28-30 мм (удобный хват для большинства спортсменов)\r\n\r\nНагрузка: выдерживает до 120 кг (подходит для интенсивных изолированных тренировок)\r\n\r\nПокрытие: матовая антискользящая обработка для надёжного хвата\r\n\r\nРезьба на концах: надёжно фиксирует диски, предотвращая их соскальзывание\r\n\r\nСовместимость: подходит для стандартных олимпийских блинов (диаметр посадочного отверстия 50 мм)\r\n\r\nПреимущества:\r\n\r\nАнатомичность – изогнутая форма обеспечивает естественное положение кистей и снижает риск травм\r\n\r\nЭффективность – позволяет лучше акцентировать нагрузку на целевые мышцы\r\n\r\nПрочность – усиленная конструкция гарантирует безопасность при работе с большими весами\r\n\r\nКомфортный хват – рифлёные участки в зоне захвата улучшают сцепление с ладонями\r\n\r\nДолгий срок службы – устойчивость к износу и деформации\r\n\r\nДля кого:\r\n\r\nZ-гриф GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для бодибилдеров, фитнес-энтузиастов, пауэрлифтеров на подсобных упражнениях, а также для тренеров и спортивных залов.\r\n\r\nКомплектация:\r\n\r\nZ-гриф для штанги GriFF-20 – 1 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать гриф вместе с качественными дисками и замками.\r\n\r\nПрокачайте руки с GriFF-20 – стальным стержнем вашего прогресса!','2026-04-24 21:55:48','2026-04-24 21:55:48'),(12,1,'multi-grip-bar-8kg-griff-20','Гриф с паралельным хватом 8 кг GriFF-20',3000.00,4,'Гриф с параллельным хватом 8 кг GriFF-20 – универсальный снаряд для проработки мышц спины и рук\r\n\r\nГриф с параллельным хватом GriFF-20 – это профессиональный спортивный снаряд, созданный для эффективных тренировок в зале и дома. Изготовленный из высококачественной стали с антикоррозийным покрытием, гриф обладает исключительной прочностью и долговечностью, выдерживая интенсивные нагрузки.\r\n\r\nИдеально подходит для упражнений с нейтральным хватом: тяги в наклоне, жима узким хватом, французского жима, подъёма на бицепс молотковым хватом и других элементов для комплексной проработки мышц.\r\n\r\nКлючевые особенности:\r\n\r\nВес: 8 кг – оптимальный вариант для целевой проработки мышц спины, груди и рук\r\n\r\nКонструкция: параллельные рукояти для нейтрального хвата\r\n\r\nДиаметр грифа: 28-30 мм (удобный хват для большинства спортсменов)\r\n\r\nНагрузка: выдерживает до 150 кг (подходит для интенсивных тренировок)\r\n\r\nПокрытие: матовая антискользящая обработка для надёжного хвата\r\n\r\nРезьба на концах: надёжно фиксирует диски, предотвращая их соскальзывание\r\n\r\nСовместимость: подходит для стандартных олимпийских блинов (диаметр посадочного отверстия 50 мм)\r\n\r\nПреимущества:\r\n\r\nАнатомичность – нейтральный хват снижает нагрузку на плечевые суставы и запястья\r\n\r\nУниверсальность – позволяет выполнять широкий спектр упражнений для разных групп мышц\r\n\r\nЭффективность – параллельный хват активирует мышцы под другим углом, разнообразя тренировки\r\n\r\nПрочность – усиленная конструкция гарантирует безопасность при работе с большими весами\r\n\r\nКомфортный хват – рифлёные участки в зоне захвата улучшают сцепление с ладонями\r\n\r\nДолгий срок службы – устойчивость к износу и деформации\r\n\r\nДля кого:\r\n\r\nГриф с параллельным хватом GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для бодибилдеров, фитнес-энтузиастов, кроссфитеров, спортсменов с чувствительными плечевыми суставами, а также для тренеров и спортивных залов.\r\n\r\nКомплектация:\r\n\r\nГриф с параллельным хватом GriFF-20 – 1 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать гриф вместе с качественными дисками и замками.\r\n\r\nРазнообразьте тренировки с GriFF-20 – стальным стержнем вашего прогресса!','2026-04-24 22:20:26','2026-04-24 22:20:26'),(13,1,'trap-bar-8kg-griff-20','Trap-гриф 20 кг GriFF-20',3000.00,4,'Trap-гриф 20 кг GriFF-20 – революционный снаряд для безопасных и эффективных тренировок\r\n\r\nTrap-гриф GriFF-20 (также известный как шестигранный или ромбовидный гриф) – это профессиональный спортивный снаряд, созданный для эффективных силовых тренировок в зале и дома. Изготовленный из высококачественной стали с антикоррозийным покрытием, гриф обладает исключительной прочностью и долговечностью, выдерживая серьёзные нагрузки.\r\n\r\nИдеально подходит для выполнения становой тяги, шрагов, выпадов, приседаний и других базовых упражнений с минимальной нагрузкой на позвоночник.\r\n\r\nКлючевые особенности:\r\n\r\nВес: 20 кг – оптимальный вариант для тренировок с серьёзными весами\r\n\r\nКонструкция: шестигранная (ромбовидная) рама с параллельными рукоятями внутри\r\n\r\nДва вида хвата: стандартные и приподнятые рукояти для разной амплитуды движения\r\n\r\nДиаметр рукоятей: 28-30 мм (удобный хват для большинства спортсменов)\r\n\r\nНагрузка: выдерживает до 300 кг (подходит для пауэрлифтинга и кроссфита)\r\n\r\nПокрытие: матовая антискользящая обработка для надёжного хвата\r\n\r\nРезьба на концах: надёжно фиксирует диски, предотвращая их соскальзывание\r\n\r\nСовместимость: подходит для стандартных олимпийских блинов (диаметр посадочного отверстия 50 мм)\r\n\r\nПреимущества:\r\n\r\nБиомеханическое преимущество – центр тяжести расположен на уровне корпуса, что снижает нагрузку на поясницу\r\n\r\nБезопасность – уменьшает риск травм при выполнении становой тяги и других базовых упражнений\r\n\r\nНейтральный хват – естественное положение кистей снижает нагрузку на запястья и плечи\r\n\r\nУниверсальность – два варианта высоты хвата позволяют варьировать амплитуду движения\r\n\r\nПрочность – усиленная конструкция гарантирует безопасность при работе с большими весами\r\n\r\nДолгий срок службы – устойчивость к износу и деформации\r\n\r\nДля кого:\r\n\r\nTrap-гриф GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, бодибилдеров, спортсменов с проблемной спиной, новичков, осваивающих становую тягу, а также для тренеров и спортивных залов.\r\n\r\nКомплектация:\r\n\r\nTrap-гриф GriFF-20 – 1 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать гриф вместе с качественными дисками и замками.\r\n\r\nТренируйтесь мощно и безопасно с GriFF-20 – стальным стержнем вашего прогресса!','2026-04-24 22:30:55','2026-04-24 22:30:55'),(14,1,'spring-lock-griff-20','Замок пружинный GriFF-20 2шт.',750.00,4,'Замок пружинный GriFF-20 – надёжная фиксация дисков для безопасных тренировок\r\n\r\nПружинный замок-бабочка GriFF-20 – это профессиональный спортивный аксессуар, созданный для надёжной фиксации дисков на грифе во время силовых тренировок в зале и дома. Изготовленный из высококачественной пружинной стали с антикоррозийным покрытием, замок обладает исключительной прочностью и долговечностью, сохраняя упругость на протяжении длительного времени использования.\r\n\r\nИдеально подходит для использования с олимпийскими грифами при выполнении базовых упражнений: жима лёжа, становой тяги, приседаний и других силовых элементов.\r\n\r\nКлючевые особенности:\r\n\r\nТип: пружинный замок-бабочка с боковыми рукоятями для удобного захвата\r\n\r\nДиаметр: 50 мм (стандартный олимпийский размер)\r\n\r\nМатериал: высокопрочная пружинная сталь с антикоррозийным покрытием\r\n\r\nКонструкция: усиленные пружинные витки для надёжной фиксации\r\n\r\nЭргономичные ручки: удобное сжатие одной рукой\r\n\r\nСовместимость: подходит для всех стандартных олимпийских грифов\r\n\r\nПреимущества:\r\n\r\nБыстрая установка – моментальная фиксация и снятие диска без дополнительных инструментов\r\n\r\nНадёжность – мощная пружина прочно удерживает диски, предотвращая их смещение\r\n\r\nУдобство – эргономичные ручки позволяют легко работать с замком одной рукой\r\n\r\nБезопасность – исключает соскальзывание дисков во время выполнения упражнений\r\n\r\nКомпактность – небольшой размер обеспечивает удобное хранение и транспортировку\r\n\r\nДолгий срок службы – устойчивость к износу и сохранение упругости пружины\r\n\r\nДля кого:\r\n\r\nПружинный замок GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, бодибилдеров, фитнес-энтузиастов, а также для тренеров и спортивных залов.\r\n\r\nКомплектация:\r\n\r\nЗамок пружинный GriFF-20 – 2 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать замки вместе с качественным олимпийским грифом и блинами.\r\n\r\nТренируйтесь безопасно с GriFF-20 – надёжным партнёром вашего прогресса!','2026-04-25 06:28:44','2026-04-25 07:10:15'),(15,1,'olympic-lock-griff-20','Замок олимпийский 2,5кг GriFF-20 2шт.',1000.00,4,'Замок олимпийский 2,5 кг GriFF-20 – профессиональная фиксация дисков рычажного типа\r\n\r\nОлимпийский замок Lock-Jaw GriFF-20 – это профессиональный спортивный аксессуар, созданный для максимально надёжной фиксации дисков на грифе во время интенсивных силовых тренировок в зале и дома. Изготовленный из высокопрочного пластика с резиновой внутренней вставкой, замок обладает исключительной долговечностью и обеспечивает плотное прилегание к грифу без проскальзывания.\r\n\r\nИдеально подходит для использования с олимпийскими грифами при выполнении базовых упражнений: жима лёжа, становой тяги, приседаний, толчков, рывков и других силовых элементов.\r\n\r\nКлючевые особенности:\r\n\r\nТип: рычажный замок Lock-Jaw с механизмом быстрой фиксации\r\n\r\nВес: 2,5 кг (пара) – устойчивая и надёжная конструкция\r\n\r\nДиаметр: 50 мм (стандартный олимпийский размер)\r\n\r\nМатериал корпуса: ударопрочный пластик высокой плотности\r\n\r\nВнутренняя вставка: износостойкая резина для плотного прилегания к грифу\r\n\r\nМеханизм: боковой рычаг-защёлка для мгновенной фиксации и снятия\r\n\r\nСовместимость: подходит для всех стандартных олимпийских грифов\r\n\r\nПреимущества:\r\n\r\nМгновенная фиксация – рычажный механизм позволяет закрепить диски за пару секунд одним движением\r\n\r\nМаксимальная надёжность – резиновая вставка обеспечивает плотный обхват грифа без люфта\r\n\r\nБезопасность – исключает соскальзывание дисков даже при динамических упражнениях и резких движениях\r\n\r\nУдобство – не требует усилий при установке и снятии, в отличие от пружинных замков\r\n\r\nСовременный дизайн – стильный внешний вид и эргономичная форма\r\n\r\nДолгий срок службы – устойчивость к ударам, износу и деформации\r\n\r\nУниверсальность – подходит для любых типов тренировок, включая кроссфит и тяжёлую атлетику\r\n\r\nДля кого:\r\n\r\nОлимпийский замок GriFF-20 предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для пауэрлифтеров, кроссфитеров, тяжелоатлетов, бодибилдеров, фитнес-энтузиастов, а также для тренеров и спортивных залов, где требуется быстрая смена весов.\r\n\r\nКомплектация:\r\n\r\nЗамок олимпийский GriFF-20 – 2 шт.\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать замки вместе с качественным олимпийским грифом и блинами.\r\n\r\nТренируйтесь уверенно с GriFF-20 – надёжным партнёром вашего прогресса!','2026-04-25 07:04:00','2026-04-25 07:04:00'),(16,5,'bench-layxxl','Скамья LayXXL',3000.00,4,'Скамья LayXXL – надёжная основа для классических силовых упражнений\r\n\r\nГоризонтальная скамья LayXXL – это профессиональный спортивный снаряд, созданный для эффективных силовых тренировок в зале и дома. Изготовленная из высокопрочной стали с антикоррозийным покрытием, скамья обладает исключительной устойчивостью и долговечностью, выдерживая серьёзные нагрузки на протяжении многих лет использования.\r\n\r\nИдеально подходит для классических базовых упражнений: жима лёжа, разведений гантелей, пуловеров, французского жима, упражнений на трицепс и других силовых элементов в горизонтальном положении.\r\n\r\nКлючевые особенности:\r\n\r\nКонструкция: цельная усиленная стальная рама с антикоррозийным покрытием\r\n\r\nТип: горизонтальная скамья без регулировки – максимальная жёсткость и стабильность\r\n\r\nМаксимальная нагрузка: выдерживает до 400 кг (вес пользователя + рабочий вес)\r\n\r\nОбивка: плотный наполнитель высокой жёсткости с износостойким покрытием\r\n\r\nРазмеры: увеличенная длина и ширина сиденья для комфорта спортсменов любой комплекции\r\n\r\nОпоры: широкие прорезиненные ножки для максимальной устойчивости и защиты пола\r\n\r\nВысота: оптимальная для выполнения жимовых упражнений с гантелями и штангой\r\n\r\nПреимущества:\r\n\r\nМаксимальная стабильность – цельная конструкция без подвижных элементов исключает любой люфт\r\n\r\nУстойчивость – усиленная рама и широкая база гарантируют надёжность при работе с большими весами\r\n\r\nКомфорт – плотная обивка и анатомичная форма обеспечивают правильную поддержку спины\r\n\r\nПростота – отсутствие регулировок означает мгновенную готовность к тренировке\r\n\r\nНадёжность – минимум деталей означает максимум долговечности\r\n\r\nДолгий срок службы – устойчивость к износу, деформации и интенсивной эксплуатации\r\n\r\nБезопасность – продуманная конструкция минимизирует риск травм во время тренировок\r\n\r\nДля кого:\r\n\r\nСкамья LayXXL предназначена для спортсменов разного уровня подготовки – от любителей до профессионалов. Она отлично подходит для пауэрлифтеров, бодибилдеров, кроссфитеров, фитнес-энтузиастов, а также для тренеров и оборудования спортивных залов и домашних тренажёрных комнат.\r\n\r\nКомплектация:\r\n\r\nСкамья LayXXL – 1 шт.\r\n\r\nКрепёжные элементы и инструкция по сборке\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать скамью вместе с качественным олимпийским грифом, блинами и стойками для штанги.\r\n\r\nТренируйтесь стабильно с LayXXL – надёжной основой вашего прогресса!','2026-04-25 08:47:55','2026-04-25 08:47:55'),(17,5,'bench-press-layxxl','Скамья жимовая LayXXL',5000.00,4,'Скамья жимовая LayXXL – профессиональный снаряд для пауэрлифтинга и силовых тренировок\r\n\r\nЖимовая скамья LayXXL – это профессиональный спортивный снаряд, созданный для интенсивных силовых тренировок в зале и дома. Изготовленная из высокопрочной стали с антикоррозийным покрытием, скамья обладает исключительной устойчивостью и долговечностью, выдерживая максимальные нагрузки на протяжении многих лет использования.\r\n\r\nИдеально подходит для выполнения жима лёжа со штангой – ключевого упражнения пауэрлифтинга и бодибилдинга, а также жима с гантелями, разведений и других силовых элементов в горизонтальном положении.\r\n\r\nКлючевые особенности:\r\n\r\nКонструкция: цельная усиленная стальная рама с антикоррозийным покрытием\r\n\r\nТип: горизонтальная жимовая скамья со встроенными стойками для штанги\r\n\r\nРегулируемые стойки: несколько уровней высоты под рост спортсмена\r\n\r\nСтраховочные упоры: предотвращают падение штанги при отказе мышц\r\n\r\nМаксимальная нагрузка: выдерживает до 450 кг (вес пользователя + рабочий вес)\r\n\r\nОбивка: плотный наполнитель высокой жёсткости с износостойким покрытием\r\n\r\nРазмеры: увеличенная длина и ширина сиденья для комфорта спортсменов любой комплекции\r\n\r\nОпоры: широкие прорезиненные ножки с расширенной базой для максимальной устойчивости\r\n\r\nПреимущества:\r\n\r\nБезопасность – встроенные страховочные упоры позволяют тренироваться даже без напарника\r\n\r\nСтабильность – цельная конструкция и расширенная база исключают любое раскачивание\r\n\r\nУдобство – регулируемые стойки подстраиваются под индивидуальные параметры спортсмена\r\n\r\nЭффективность – правильная высота стоек обеспечивает оптимальный съём и постановку штанги\r\n\r\nПрочность – усиленная рама гарантирует надёжность при работе с предельными весами\r\n\r\nКомфорт – плотная обивка и анатомичная форма обеспечивают правильную поддержку спины\r\n\r\nДолгий срок службы – устойчивость к износу, деформации и интенсивной эксплуатации\r\n\r\nДля кого:\r\n\r\nЖимовая скамья LayXXL предназначена для спортсменов разного уровня подготовки – от любителей до профессионалов. Она отлично подходит для пауэрлифтеров, бодибилдеров, кроссфитеров, фитнес-энтузиастов, а также для тренеров и оборудования профессиональных спортивных залов и домашних тренажёрных комнат.\r\n\r\nКомплектация:\r\n\r\nСкамья жимовая LayXXL – 1 шт.\r\n\r\nКрепёжные элементы и инструкция по сборке\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать скамью вместе с качественным олимпийским грифом, блинами и надёжными замками.\r\n\r\nПокоряйте новые рекорды с LayXXL – надёжной основой вашего прогресса!','2026-04-25 09:01:17','2026-04-25 09:01:17'),(18,8,'creatine-monohydrate-breero','Креатин моногидрат Breero',300.00,3,'Креатин моногидрат Breero – мощный источник энергии для взрывных тренировок\r\n\r\nКреатин моногидрат Breero – это профессиональная спортивная добавка, созданная для увеличения силовых показателей, выносливости и роста мышечной массы. Произведённый по современным технологиям с высокой степенью очистки, креатин обладает максимальной биодоступностью и эффективностью.\r\n\r\nИдеально подходит для спортсменов, стремящихся к увеличению силы, мощности и работоспособности во время интенсивных тренировок, а также для ускорения восстановления и набора качественной мышечной массы.\r\n\r\nКлючевые особенности:\r\n\r\nФорма: 100% чистый креатин моногидрат – самая изученная и эффективная форма креатина\r\n\r\nСтепень очистки: микронизированный порошок для лучшего растворения и усвоения\r\n\r\nСодержание активного вещества: 5 г креатина на порцию – оптимальная рабочая дозировка\r\n\r\nБез примесей: не содержит сахара, красителей и искусственных добавок\r\n\r\nРастворимость: легко смешивается с водой, соком или протеиновым коктейлем\r\n\r\nБез вкуса: возможность сочетания с любыми напитками без изменения их вкуса\r\n\r\nУпаковка: герметичная банка с мерной ложкой для удобного дозирования\r\n\r\nПреимущества:\r\n\r\nРост силы – увеличивает максимальные силовые показатели уже через 1-2 недели приёма\r\n\r\nВзрывная мощность – повышает работоспособность в коротких интенсивных подходах\r\n\r\nУвеличение мышечной массы – способствует росту качественной мышечной ткани\r\n\r\nУскоренное восстановление – сокращает время отдыха между подходами и тренировками\r\n\r\nПовышение выносливости – позволяет выполнять больше повторений с рабочим весом\r\n\r\nУлучшение клеточной гидратации – способствует наполненности и объёму мышц\r\n\r\nДоказанная эффективность – одна из самых исследованных спортивных добавок в мире\r\n\r\nБезопасность – натуральное вещество, вырабатываемое в организме человека\r\n\r\nДля кого:\r\n\r\nКреатин моногидрат Breero предназначен для спортсменов разного уровня подготовки – от любителей до профессионалов. Он отлично подходит для бодибилдеров, пауэрлифтеров, кроссфитеров, тяжелоатлетов, спринтеров, представителей единоборств, а также для всех, кто стремится к увеличению силы и мышечной массы.\r\n\r\nСпособ применения:\r\n\r\nЗагрузочная фаза (по желанию): 20 г в день, разделённые на 4 приёма по 5 г, в течение 5-7 дней.\r\n\r\nПоддерживающая фаза: 5 г в день в любое удобное время, желательно после тренировки или с приёмом пищи.\r\n\r\nРазмешайте 1 мерную ложку (5 г) в 250-300 мл воды или сока. Для максимального эффекта принимайте курсом 1-2 месяца с обязательным употреблением достаточного количества воды (2-3 литра в день).\r\n\r\nКомплектация:\r\n\r\nКреатин моногидрат Breero – 1 шт.\r\n\r\nМерная ложка внутри упаковки\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности рекомендуем сочетать приём креатина с регулярными силовыми тренировками, сбалансированным питанием и качественным сывороточным протеином.\r\n\r\nРаскройте свой силовой потенциал с Breero – чистым источником вашего прогресса!','2026-04-25 09:29:07','2026-04-25 09:29:07'),(19,4,'light-stand-for-bar-griff-stand','Стойка для штанги GriFF-Stand',5000.00,4,'Стойка для штанги GriFF-Stand – компактное решение для силовых тренировок\r\n\r\nСтойка для штанги GriFF-Stand – это профессиональный спортивный снаряд, созданный для эффективных силовых тренировок в зале и дома. Изготовленная из высокопрочной стали с антикоррозийным покрытием, стойка обладает отличной устойчивостью и долговечностью, выдерживая серьёзные нагрузки на протяжении многих лет использования.\r\n\r\nИдеально подходит для выполнения базовых упражнений со штангой: приседаний, жима лёжа со скамьи, жима стоя, тяг и других силовых элементов.\r\n\r\nКлючевые особенности:\r\n\r\nКонструкция: две параллельные стойки с соединительной перекладиной из стали\r\n\r\nРегулировка высоты: множество позиций крюков под рост спортсмена и тип упражнения\r\n\r\nJ-крюки: усиленные держатели штанги с защитной вставкой против повреждения грифа\r\n\r\nСоединительная перекладина: обеспечивает дополнительную жёсткость и устойчивость конструкции\r\n\r\nМаксимальная нагрузка: выдерживает до 300 кг (рабочий вес штанги)\r\n\r\nСечение профиля: квадратная труба для оптимального соотношения веса и прочности\r\n\r\nГабариты: компактные размеры для размещения в небольших помещениях\r\n\r\nОпоры: широкие прорезиненные ножки для устойчивости и защиты пола\r\n\r\nПреимущества:\r\n\r\nКомпактность – занимает минимум места в домашнем зале или тренажёрной комнате\r\n\r\nЛёгкость – меньший вес упрощает сборку, перемещение и хранение\r\n\r\nПростота – минималистичная конструкция без лишних элементов, готовая к работе\r\n\r\nУстойчивость – соединительная перекладина обеспечивает надёжность всей конструкции\r\n\r\nУдобство – быстрая регулировка высоты крюков под любое упражнение\r\n\r\nЗащита оборудования – вставки на J-крюках предотвращают повреждение грифа\r\n\r\nПрочность – качественная сталь и сварные швы гарантируют долговечность\r\n\r\nДоступность – оптимальное соотношение цены и функциональности\r\n\r\nДля кого:\r\n\r\nСтойка GriFF-Stand предназначена для спортсменов разного уровня подготовки – от новичков до опытных атлетов. Она отлично подходит для бодибилдеров, фитнес-энтузиастов, любителей силовых тренировок, а также для оборудования домашних тренажёрных комнат и небольших спортивных залов.\r\n\r\nКомплектация:\r\n\r\nСтойка для штанги GriFF-Stand – 1 шт.\r\n\r\nJ-крюки – 2 шт.\r\n\r\nКрепёжные элементы и инструкция по сборке\r\n\r\nДополнительно:\r\n\r\nДля максимальной эффективности тренировок рекомендуем использовать стойку вместе с качественным олимпийским грифом, блинами, замками и скамьёй.\r\n\r\nТренируйтесь эффективно с GriFF-Stand – стальной опорой вашего прогресса!','2026-04-25 14:37:00','2026-04-25 14:37:00');
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
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `work_hours` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `latitude` decimal(9,6) DEFAULT NULL,
  `longitude` decimal(9,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_stores_latitude` CHECK ((`latitude` between -(90) and 90)),
  CONSTRAINT `chk_stores_longitude` CHECK ((`longitude` between -(180) and 180))
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stores`
--

LOCK TABLES `stores` WRITE;
/*!40000 ALTER TABLE `stores` DISABLE KEYS */;
INSERT INTO `stores` VALUES (1,'Р-н Люблино','Москва, Ул. Головачёва, вл8с1','+7 000 000 00 00','10:00 - 23:00 (будние дни)\n10:00 - 21:00 (выходные и праздники)',55.666208,37.816980),(2,'Р-н Отрадное','Москва, Ул. Станционная, 11','+7 000 000 00 00','10:00 - 22:00 (будние дни)\n10:00 - 21:00 (выходные и праздники)',55.846623,37.600648),(3,'Г. Дмитров','Дмитров, Историческая площадь, 5','+7 000 000 00 00','10:00 - 23:00 (будние дни)\n10:00 - 21:00 (выходные и праздники)',56.345304,37.520384);
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
  `email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `role` varchar(32) NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'gymboss_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-26 17:47:37
