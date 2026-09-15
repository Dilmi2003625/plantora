-- Plantora Database Backup
-- Date: 2026-09-13 06:26:43

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_id` int(11) NOT NULL,
  `variation_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`cart_item_id`),
  KEY `cart_id` (`cart_id`),
  KEY `variation_id` (`variation_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`variation_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES ('1', 'Low-Light Plants', 'Plants that grow well in areas with limited sunlight.');
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES ('2', 'Air-Purifying Plants', 'Indoor plants that help improve indoor air quality.');
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES ('3', 'Easy-Care Plants', 'Low-maintenance plants suitable for beginners.');
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES ('4', 'Flowering Plants', 'Beautiful indoor plants that produce attractive flowers.');
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES ('5', 'Foliage Plants', 'Decorative plants grown mainly for their beautiful leaves.');
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES ('6', 'Cacti & Succulents', 'Drought-tolerant plants that require less watering.');

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `variation_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `variation_id` (`variation_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`variation_id`) REFERENCES `product_variations` (`variation_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_address` text NOT NULL,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('Pending','Confirmed','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` enum('Cash on Delivery','Online Payment') NOT NULL,
  `payment_status` enum('Pending','Paid','Failed') DEFAULT 'Pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `product_variations`;
CREATE TABLE `product_variations` (
  `variation_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size` enum('Small Plant','Mother Plant') DEFAULT NULL,
  `pot_option` enum('Without Pot','With Pot') DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`variation_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_variations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=165 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('1', '1', 'Small Plant', 'Without Pot', '1500.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('2', '1', 'Small Plant', 'With Pot', '2200.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('3', '1', 'Mother Plant', 'Without Pot', '3000.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('4', '1', 'Mother Plant', 'With Pot', '3700.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('5', '2', 'Small Plant', 'Without Pot', '1300.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('6', '2', 'Small Plant', 'With Pot', '2000.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('7', '2', 'Mother Plant', 'Without Pot', '2800.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('8', '2', 'Mother Plant', 'With Pot', '3500.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('9', '3', 'Small Plant', 'Without Pot', '1800.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('10', '3', 'Small Plant', 'With Pot', '2500.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('11', '3', 'Mother Plant', 'Without Pot', '3500.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('12', '3', 'Mother Plant', 'With Pot', '4200.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('13', '4', 'Small Plant', 'Without Pot', '1600.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('14', '4', 'Small Plant', 'With Pot', '2300.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('15', '4', 'Mother Plant', 'Without Pot', '3200.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('16', '4', 'Mother Plant', 'With Pot', '3900.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('17', '5', 'Small Plant', 'Without Pot', '1400.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('18', '5', 'Small Plant', 'With Pot', '2100.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('19', '5', 'Mother Plant', 'Without Pot', '2900.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('20', '5', 'Mother Plant', 'With Pot', '3600.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('21', '6', 'Small Plant', 'Without Pot', '1200.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('22', '6', 'Small Plant', 'With Pot', '1900.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('23', '6', 'Mother Plant', 'Without Pot', '2600.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('24', '6', 'Mother Plant', 'With Pot', '3300.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('25', '7', 'Small Plant', 'Without Pot', '1700.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('26', '7', 'Small Plant', 'With Pot', '2400.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('27', '7', 'Mother Plant', 'Without Pot', '3300.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('28', '7', 'Mother Plant', 'With Pot', '4000.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('29', '8', 'Small Plant', 'Without Pot', '1600.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('30', '8', 'Small Plant', 'With Pot', '2300.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('31', '8', 'Mother Plant', 'Without Pot', '3100.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('32', '8', 'Mother Plant', 'With Pot', '3800.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('33', '9', 'Small Plant', 'Without Pot', '1350.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('34', '9', 'Small Plant', 'With Pot', '2050.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('35', '9', 'Mother Plant', 'Without Pot', '2750.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('36', '9', 'Mother Plant', 'With Pot', '3450.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('37', '10', 'Small Plant', 'Without Pot', '1100.00', '15');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('38', '10', 'Small Plant', 'With Pot', '1750.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('39', '10', 'Mother Plant', 'Without Pot', '2200.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('40', '10', 'Mother Plant', 'With Pot', '2850.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('41', '11', 'Small Plant', 'Without Pot', '1500.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('42', '11', 'Small Plant', 'With Pot', '2200.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('43', '11', 'Mother Plant', 'Without Pot', '3000.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('44', '11', 'Mother Plant', 'With Pot', '3700.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('45', '12', 'Small Plant', 'Without Pot', '1300.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('46', '12', 'Small Plant', 'With Pot', '1950.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('47', '12', 'Mother Plant', 'Without Pot', '2600.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('48', '12', 'Mother Plant', 'With Pot', '3250.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('49', '13', 'Small Plant', 'Without Pot', '2400.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('50', '13', 'Small Plant', 'With Pot', '3200.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('51', '13', 'Mother Plant', 'Without Pot', '4200.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('52', '13', 'Mother Plant', 'With Pot', '5000.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('53', '14', 'Small Plant', 'Without Pot', '1800.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('54', '14', 'Small Plant', 'With Pot', '2500.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('55', '14', 'Mother Plant', 'Without Pot', '3500.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('56', '14', 'Mother Plant', 'With Pot', '4200.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('57', '15', 'Small Plant', 'Without Pot', '1200.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('58', '15', 'Small Plant', 'With Pot', '1850.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('59', '15', 'Mother Plant', 'Without Pot', '2400.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('60', '15', 'Mother Plant', 'With Pot', '3050.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('61', '16', 'Small Plant', 'Without Pot', '950.00', '15');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('62', '16', 'Small Plant', 'With Pot', '1550.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('63', '16', 'Mother Plant', 'Without Pot', '1950.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('64', '16', 'Mother Plant', 'With Pot', '2550.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('65', '17', 'Small Plant', 'Without Pot', '1750.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('66', '17', 'Small Plant', 'With Pot', '2450.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('67', '17', 'Mother Plant', 'Without Pot', '3400.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('68', '17', 'Mother Plant', 'With Pot', '4100.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('69', '18', 'Small Plant', 'Without Pot', '1150.00', '15');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('70', '18', 'Small Plant', 'With Pot', '1800.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('71', '18', 'Mother Plant', 'Without Pot', '2400.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('72', '18', 'Mother Plant', 'With Pot', '3050.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('73', '19', 'Small Plant', 'Without Pot', '1550.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('74', '19', 'Small Plant', 'With Pot', '2250.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('75', '19', 'Mother Plant', 'Without Pot', '3100.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('76', '19', 'Mother Plant', 'With Pot', '3800.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('77', '20', 'Small Plant', 'Without Pot', '1300.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('78', '20', 'Small Plant', 'With Pot', '1950.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('79', '20', 'Mother Plant', 'Without Pot', '2700.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('80', '20', 'Mother Plant', 'With Pot', '3350.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('81', '21', 'Small Plant', 'Without Pot', '1400.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('82', '21', 'Small Plant', 'With Pot', '2050.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('83', '21', 'Mother Plant', 'Without Pot', '2800.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('84', '21', 'Mother Plant', 'With Pot', '3450.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('85', '22', 'Small Plant', 'Without Pot', '2100.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('86', '22', 'Small Plant', 'With Pot', '2850.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('87', '22', 'Mother Plant', 'Without Pot', '3900.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('88', '22', 'Mother Plant', 'With Pot', '4650.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('89', '23', 'Small Plant', 'Without Pot', '1200.00', '14');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('90', '23', 'Small Plant', 'With Pot', '1850.00', '9');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('91', '23', 'Mother Plant', 'Without Pot', '2500.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('92', '23', 'Mother Plant', 'With Pot', '3150.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('93', '24', 'Small Plant', 'Without Pot', '1600.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('94', '24', 'Small Plant', 'With Pot', '2300.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('95', '24', 'Mother Plant', 'Without Pot', '3100.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('96', '24', 'Mother Plant', 'With Pot', '3800.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('97', '25', 'Small Plant', 'Without Pot', '2400.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('98', '25', 'Small Plant', 'With Pot', '3150.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('99', '25', 'Mother Plant', 'Without Pot', '4200.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('100', '25', 'Mother Plant', 'With Pot', '4950.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('101', '26', 'Small Plant', 'Without Pot', '2750.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('102', '26', 'Small Plant', 'With Pot', '3500.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('103', '26', 'Mother Plant', 'Without Pot', '4600.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('104', '26', 'Mother Plant', 'With Pot', '5350.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('105', '27', 'Small Plant', 'Without Pot', '1350.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('106', '27', 'Small Plant', 'With Pot', '1950.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('107', '27', 'Mother Plant', 'Without Pot', '2600.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('108', '27', 'Mother Plant', 'With Pot', '3200.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('109', '28', 'Small Plant', 'Without Pot', '1250.00', '15');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('110', '28', 'Small Plant', 'With Pot', '1850.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('111', '28', 'Mother Plant', 'Without Pot', '2450.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('112', '28', 'Mother Plant', 'With Pot', '3050.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('113', '29', 'Small Plant', 'Without Pot', '2200.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('114', '29', 'Small Plant', 'With Pot', '2900.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('115', '29', 'Mother Plant', 'Without Pot', '3800.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('116', '29', 'Mother Plant', 'With Pot', '4500.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('117', '30', 'Small Plant', 'Without Pot', '2500.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('118', '30', 'Small Plant', 'With Pot', '3300.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('119', '30', 'Mother Plant', 'Without Pot', '4400.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('120', '30', 'Mother Plant', 'With Pot', '5200.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('121', '31', 'Small Plant', 'Without Pot', '2100.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('122', '31', 'Small Plant', 'With Pot', '2800.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('123', '31', 'Mother Plant', 'Without Pot', '3700.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('124', '31', 'Mother Plant', 'With Pot', '4400.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('125', '32', 'Small Plant', 'Without Pot', '3200.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('126', '32', 'Small Plant', 'With Pot', '4100.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('127', '32', 'Mother Plant', 'Without Pot', '5500.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('128', '32', 'Mother Plant', 'With Pot', '6400.00', '2');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('129', '33', 'Small Plant', 'Without Pot', '1900.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('130', '33', 'Small Plant', 'With Pot', '2600.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('131', '33', 'Mother Plant', 'Without Pot', '3500.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('132', '33', 'Mother Plant', 'With Pot', '4200.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('133', '34', 'Small Plant', 'Without Pot', '2300.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('134', '34', 'Small Plant', 'With Pot', '3000.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('135', '34', 'Mother Plant', 'Without Pot', '4000.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('136', '34', 'Mother Plant', 'With Pot', '4700.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('137', '35', 'Small Plant', 'Without Pot', '1650.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('138', '35', 'Small Plant', 'With Pot', '2350.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('139', '35', 'Mother Plant', 'Without Pot', '3200.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('140', '35', 'Mother Plant', 'With Pot', '3900.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('141', '36', 'Small Plant', 'Without Pot', '1550.00', '15');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('142', '36', 'Small Plant', 'With Pot', '2150.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('143', '36', 'Mother Plant', 'Without Pot', '2950.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('144', '36', 'Mother Plant', 'With Pot', '3550.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('145', '37', 'Small Plant', 'Without Pot', '950.00', '15');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('146', '37', 'Small Plant', 'With Pot', '1550.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('147', '37', 'Mother Plant', 'Without Pot', '2050.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('148', '37', 'Mother Plant', 'With Pot', '2650.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('149', '38', 'Small Plant', 'Without Pot', '850.00', '18');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('150', '38', 'Small Plant', 'With Pot', '1450.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('151', '38', 'Mother Plant', 'Without Pot', '1950.00', '7');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('152', '38', 'Mother Plant', 'With Pot', '2550.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('153', '39', 'Small Plant', 'Without Pot', '1100.00', '15');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('154', '39', 'Small Plant', 'With Pot', '1700.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('155', '39', 'Mother Plant', 'Without Pot', '2300.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('156', '39', 'Mother Plant', 'With Pot', '2900.00', '4');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('157', '40', 'Small Plant', 'Without Pot', '1350.00', '12');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('158', '40', 'Small Plant', 'With Pot', '2000.00', '8');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('159', '40', 'Mother Plant', 'Without Pot', '2800.00', '5');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('160', '40', 'Mother Plant', 'With Pot', '3450.00', '3');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('161', '41', 'Small Plant', 'Without Pot', '900.00', '16');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('162', '41', 'Small Plant', 'With Pot', '1500.00', '10');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('163', '41', 'Mother Plant', 'Without Pot', '2100.00', '6');
INSERT INTO `product_variations` (`variation_id`, `product_id`, `size`, `pot_option`, `price`, `stock_quantity`) VALUES ('164', '41', 'Mother Plant', 'With Pot', '2700.00', '4');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `product_type` enum('Plant','Pot','Package') NOT NULL DEFAULT 'Plant',
  `description` text DEFAULT NULL,
  `care_instructions` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('1', '1', 'Peace Lily', '', 'Beautiful indoor plant that grows well in low-light areas.', 'Keep the soil slightly moist and place in indirect light.', 'peace-lily.jpeg', '2026-09-02 13:07:36');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('2', '1', 'Snake Plant', '', 'Easy-care indoor plant suitable for low-light spaces.', 'Water when the soil is dry and keep in indirect light.', 'snake-plant.jpeg', '2026-09-02 13:07:36');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('3', '1', 'ZZ Plant', '', 'Hardy and attractive plant that can tolerate low-light conditions.', 'Water only when the soil becomes dry and avoid overwatering.', 'zz-plant.jpeg', '2026-09-02 13:07:36');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('4', '1', 'Chinese Evergreen', '', 'Low-maintenance foliage plant that performs well in low-light rooms.', 'Keep in indirect light and water when the top soil is dry.', 'chinese-evergreen.jpeg', '2026-09-02 13:07:36');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('5', '1', 'Cast Iron Plant', '', 'A hardy indoor plant that grows well in low-light areas.', 'Water when the top layer of soil is dry and keep it in indirect light.', 'cast-iron.jpeg', '2026-09-02 13:19:27');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('6', '1', 'Pothos Plant', '', 'A beautiful trailing plant that is suitable for low-light indoor spaces.', 'Water when the soil becomes slightly dry and place in indirect light.', 'pothos-plant.jpeg', '2026-09-02 13:19:27');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('7', '1', 'Heartleaf Philodendron', '', 'An attractive heart-shaped foliage plant that adapts well to low-light conditions.', 'Keep the soil slightly moist and provide bright indirect or low light.', 'heartleaf-pilodendron.jpeg', '2026-09-02 13:19:27');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('8', '1', 'Prayer Plant', '', 'A decorative foliage plant known for its beautiful patterned leaves.', 'Keep the soil lightly moist and provide indirect light.', 'prayer-plant.jpeg', '2026-09-02 13:19:27');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('9', '2', 'Snake Plant Laurentii', 'Plant', 'Renowned for filtering airborne toxins including formaldehyde and benzene while releasing fresh oxygen at night.', 'Water every 2-3 weeks. Thrives in moderate to bright indirect sunlight. Allow soil to dry completely between waterings.', 'snake-plant.jpeg', '2026-09-12 21:46:53');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('10', '2', 'Spider Plant', 'Plant', 'Resilient air purifier with arching green and white ribbon leaves that naturally cleanse common household air pollutants.', 'Water once a week. Flourishes in bright, indirect light. Mist occasionally to keep foliage vibrant.', 'spider-plant.jpeg', '2026-09-12 21:46:53');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('11', '2', 'Peace Lily Spathiphyllum', 'Plant', 'Renowned air purifier featuring dark glossy leaves and elegant white flowering spathes that bring calm to any living space.', 'Water when the top inch of soil dries. Low to moderate indirect light. Keep away from cold drafts.', 'peace-lily.jpeg', '2026-09-12 21:46:53');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('12', '2', 'Boston Fern', 'Plant', 'Lush feather-like fronds that naturally humidify room air and filter everyday airborne impurities.', 'Keep soil consistently moist. Enjoys high humidity and bright indirect light. Mist fronds regularly.', 'boston-fern.jpeg', '2026-09-12 21:46:54');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('13', '2', 'Areca Palm', 'Plant', 'Graceful tropical indoor palm that excels at absorbing indoor airborne impurities and adding natural humidity.', 'Water when top inch of soil feels dry. Bright indirect sunlight and well-aerated soil mix.', 'areca-palm.jpeg', '2026-09-12 21:46:54');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('14', '2', 'Rubber Plant Burgundy', 'Plant', 'Broad, lustrous deep burgundy leaves that trap fine dust particles and improve room air quality.', 'Water every 1-2 weeks. Thrives in bright, filtered sunlight. Wipe broad leaves with a damp cloth monthly.', 'rubber-plant.jpeg', '2026-09-12 21:46:54');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('15', '2', 'English Ivy', 'Plant', 'Fast-growing climbing evergreen capable of absorbing airborne mold particles and toxic emissions.', 'Keep soil evenly moist. Moderate light conditions. Great for hanging baskets or trellises.', 'english-lvy.jpeg', '2026-09-12 21:46:54');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('16', '2', 'Aloe Vera Purifier', 'Plant', 'Soothing succulent with healing gel that continuously improves living space air and monitors air quality.', 'Water deeply every 2 to 3 weeks. Full sun to bright indirect light. Plant in porous succulent soil.', 'aleo-vera.jpeg', '2026-09-12 21:46:55');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('17', '3', 'ZZ Plant Emerald', 'Plant', 'Virtually indestructible houseplant that flourishes under neglect, drought, and low light conditions.', 'Water once a month. Tolerates very low light and dry indoor air. Minimal care required.', 'zz-plant.jpeg', '2026-09-12 21:46:55');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('18', '3', 'Golden Pothos', 'Plant', 'Hardy, fast-growing trailing vine that easily adapts to diverse conditions and bounces back quickly.', 'Water when top two inches of soil are dry. Any light level from low to bright indirect.', 'pothos-plant.jpeg', '2026-09-12 21:46:55');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('19', '3', 'Chinese Evergreen Silver', 'Plant', 'Tough, forgiving plant with patterned leaves that thrives with minimal attention in busy homes.', 'Water every 1-2 weeks. Thrives in medium to low light and warm room temperatures.', 'chinese-evergreen.jpeg', '2026-09-12 21:46:56');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('20', '3', 'Crassula Jade Plant', 'Plant', 'Long-lived succulent symbolizing good luck and prosperity, requiring very little moisture to thrive.', 'Water sparingly every 2-3 weeks. Needs bright light or sunny windowsill. Drought tolerant.', 'jade-plant.jpeg', '2026-09-12 21:46:56');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('21', '3', 'Watermelon Peperomia', 'Plant', 'Compact, delightful plant with striped foliage that rarely demands repotting and stays neat.', 'Water when top half of soil is dry. Moderate indirect sunlight and avoid overwatering.', 'peperomia.jpeg', '2026-09-12 21:46:56');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('22', '3', 'Ponytail Palm', 'Plant', 'Distinctive swollen trunk holding months of water, topped with playful cascading ribbon leaves.', 'Water every 3 weeks. Prefers bright light to partial direct sun. Extremely low maintenance.', 'ponytail-palm.jpeg', '2026-09-12 21:46:56');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('23', '3', 'Heartleaf Philodendron Carefree', 'Plant', 'Forgiving cascading plant with heart-shaped leaves that can withstand irregular watering schedules.', 'Allow soil to dry out between waterings. Medium to low light. Trim vines to encourage bushy growth.', 'philodendron.jpeg', '2026-09-12 21:46:56');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('24', '4', 'Spathiphyllum Peace Lily', 'Plant', 'Classic indoor flowering plant featuring graceful white sail-like flowers and shiny dark foliage.', 'Water weekly when slightly drooped. Filtered low to medium sunlight. Keep away from direct hot sun.', 'peace-lily-bloom.jpeg', '2026-09-12 21:46:57');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('25', '4', 'Red Anthurium', 'Plant', 'Exotic tropical beauty sporting glossy heart-shaped leaves and crimson blossom spathes that bloom all year.', 'Water when topsoil is slightly dry. Bright indirect light promotes reblooming and vibrant color.', 'anthurium-red.jpeg', '2026-09-12 21:46:57');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('26', '4', 'Phalaenopsis Moth Orchid', 'Plant', 'Sophisticated indoor orchid with arching stems of long-lasting, delicate blooms in soft pastel tones.', 'Water weekly with free drainage. Warm indirect lighting. Never leave standing water in saucer.', 'phalaenopsis-orchid.jpeg', '2026-09-12 21:46:57');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('27', '4', 'African Violet', 'Plant', 'Classic houseplant with velvety dark leaves and vibrant purple flowers that bloom repeatedly indoors.', 'Water from below with room temperature water. Bright filtered light. Avoid wetting the fuzzy leaves.', 'african-violet.jpeg', '2026-09-12 21:46:57');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('28', '4', 'Flaming Katy Kalanchoe', 'Plant', 'Cheerful succulent with long-lasting clusters of coral-pink blossoms perched above scalloped leaves.', 'Water thoroughly only when dry. Needs 4-6 hours of bright light. Pinch back spent flowers.', 'kalanchoe-blossfeldiana.jpeg', '2026-09-12 21:46:57');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('29', '4', 'Scarlet Guzmania Bromeliad', 'Plant', 'Architectural tropical plant with vivid scarlet center bracts that maintain their brilliance for months.', 'Keep central cup filled with clean rainwater or filtered water. Moderate filtered indoor light.', 'bromeliad-guzmania.jpeg', '2026-09-12 21:46:58');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('30', '5', 'Monstera Deliciosa', 'Plant', 'Celebrated Swiss Cheese plant featuring magnificent split emerald leaves that create an instant jungle vibe.', 'Water every 1-2 weeks. Moderate to bright indirect light. Wipe large leaves to maximize sunlight.', 'monstera-deliciosa.jpeg', '2026-09-12 21:46:58');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('31', '5', 'Calathea Orbifolia', 'Plant', 'Striking prayer plant with oversized round leaves marked by metallic silver brushstrokes on rich green.', 'Keep soil consistently moist with filtered water. Prefers humid rooms and gentle filtered shade.', 'calathea-orbifolia.jpeg', '2026-09-12 21:46:58');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('32', '5', 'Fiddle Leaf Fig', 'Plant', 'Statement indoor tree boasting massive violin-shaped sculpted leaves that add luxury to interior spaces.', 'Water deeply when top two inches dry. Requires abundant bright filtered light without direct scorching.', 'fiddle-leaf-fig.jpeg', '2026-09-12 21:46:58');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('33', '5', 'Philodendron Birkin', 'Plant', 'Designer houseplant showing off creamy white pinstripe variegation on deep glossy emerald green foliage.', 'Water when top inch of soil is dry. Bright indirect sun preserves sharp contrast on leaves.', 'philodendron-birkin.jpeg', '2026-09-12 21:46:58');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('34', '5', 'Alocasia Amazonica Polly', 'Plant', 'Dramatic African mask plant with scalloped dark leaves and bold sculptural white veining.', 'High humidity, warm temperatures, and evenly moist well-draining soil. Avoid cold drafts.', 'alocasia-amazonica.jpeg', '2026-09-12 21:46:59');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('35', '5', 'Chinese Evergreen Foliage', 'Plant', 'Spectacular patterned silver and green indoor foliage that adapts effortlessly to any interior setting.', 'Water every 1-2 weeks. Thrives in moderate to low indirect light. Clean foliage regularly.', 'chinese-evergreen.jpeg', '2026-09-12 21:46:59');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('36', '6', 'Golden Barrel Cactus', 'Plant', 'Globe-shaped desert cactus adorned with brilliant golden-yellow spines and handsome ribs.', 'Water once a month in growing season. Keep completely dry in winter. Needs full direct sun.', 'golden-barrel-cactus.jpeg', '2026-09-12 21:47:00');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('37', '6', 'Zebra Haworthia', 'Plant', 'Striking rosette succulent featuring raised white pearly stripes across deep green architectural foliage.', 'Water every 2-3 weeks. Partial to bright indirect light. Plant in gritty cactus mix.', 'zebra-haworthia.jpeg', '2026-09-12 21:47:00');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('38', '6', 'Echeveria Elegans', 'Plant', 'Sculpted blue-green Mexican snowball succulent forming charming floral rosettes with powdery coating.', 'Soak and dry watering technique. Needs 4-6 hours of daily sunlight. Avoid water in rosette center.', 'echeveria-elegans.jpeg', '2026-09-12 21:47:00');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('39', '6', 'Ruby Ball Moon Cactus', 'Plant', 'Bright neon-red spherical cactus grafted onto a sturdy green columnar cactus base.', 'Water every 2-3 weeks. Bright indirect sunlight to maintain vivid color without burning.', 'moon-cactus.jpeg', '2026-09-12 21:47:00');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('40', '6', 'Jade Money Tree', 'Plant', 'Sturdy succulent with fleshy oval leaves and miniature woody trunk symbolizing financial abundance.', 'Water every 2-3 weeks when soil is bone dry. Bright sunny windowsill. Excellent bonsai candidate.', 'crassula-ovata.jpeg', '2026-09-12 21:47:00');
INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `product_type`, `description`, `care_instructions`, `image`, `created_at`) VALUES ('41', '6', 'Medicinal Aloe Vera', 'Plant', 'Prized succulent with thick serrated gel-filled spears that soothe skin irritations and sunburns.', 'Water thoroughly every 3 weeks in sandy soil. Full to partial sun. Allow soil to dry between waterings.', 'aloe-vera-succulent.jpeg', '2026-09-12 21:47:01');

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS=1;
