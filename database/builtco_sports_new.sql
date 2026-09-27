-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 27, 2026 at 03:43 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `builtco_sports_new`
--

-- --------------------------------------------------------

--
-- Table structure for table `abandoned_carts`
--

CREATE TABLE `abandoned_carts` (
  `id` int(11) NOT NULL,
  `session_id` varchar(191) NOT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cart_data` text DEFAULT NULL COMMENT 'JSON snapshot of cart items',
  `cart_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `item_count` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','recovered') NOT NULL DEFAULT 'active',
  `reminder_sent_at` datetime DEFAULT NULL,
  `restore_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `abandoned_carts`
--

INSERT INTO `abandoned_carts` (`id`, `session_id`, `customer_name`, `email`, `phone`, `cart_data`, `cart_total`, `item_count`, `status`, `reminder_sent_at`, `restore_token`, `created_at`, `updated_at`) VALUES
(1, '2n7snf80fa5b0ecrsl792kvvpt', 'Verified Buyer', 'verified@example.com', '+923001112233', '{\"1-0\":{\"product_id\":1,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BG-001\",\"price\":100,\"image\":\"\",\"qty\":1}}', 100.00, 1, 'recovered', NULL, NULL, '2026-09-21 07:50:14', '2026-09-21 07:50:15'),
(2, 'mmooio1rafc4havtueuo5t99df', 'Invoice Test Buyer', 'invoicetest@example.com', '+923001112233', '{\"1-0\":{\"product_id\":1,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"\",\"price\":100,\"image\":\"\",\"qty\":1}}', 100.00, 1, 'recovered', NULL, NULL, '2026-09-21 18:11:11', '2026-09-21 18:11:11'),
(8, 'f7fiak28b3o67aouhn5n0v7d8r', NULL, NULL, NULL, '{\"10-0\":{\"product_id\":10,\"variation_id\":null,\"name\":\"Cart Recovery Test\",\"slug\":\"cart-recovery-test\",\"variation_key\":null,\"sku\":null,\"price\":350,\"image\":\"\",\"qty\":2}}', 700.00, 1, 'active', NULL, NULL, '2026-09-21 19:03:08', '2026-09-21 19:03:08'),
(9, 'c0psmdo52tq8d7tue0sps65c87', NULL, NULL, NULL, '{\"17-0-6ab219db4cfdb\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"text\":\"RONALDO 7\",\"text_color\":\"#ffffff\",\"has_logo\":true,\"preview_path\":\"customizations\\/custom-b8e3aa4f1ea36625.png\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-22 06:02:03', '2026-09-22 06:02:03'),
(10, '8qpvq27jj0ghbqsngmhnfrt9rj', NULL, NULL, NULL, '{\"17-0-6ab219e923af7\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"text\":\"RONALDO 7\",\"text_color\":\"#ffffff\",\"has_logo\":true,\"preview_path\":\"customizations\\/custom-694e11c530a04be4.png\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-22 06:02:17', '2026-09-22 06:02:17'),
(11, 'mq2locfhr8f5ojes3qgt6m01vr', NULL, NULL, NULL, '{\"17-0-6ab220ceb426d\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"text\":\"RONALDO 7\",\"text_color\":\"#ffffff\",\"has_logo\":true,\"preview_path\":\"customizations\\/custom-74919dc16696d734.png\",\"email\":\"lead@example.com\",\"whatsapp\":\"03001234567\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-22 06:31:42', '2026-09-22 06:31:42'),
(12, 'pirgouvdqboguspmp6r2cgirsl', NULL, NULL, NULL, '{\"17-0-6ab220fe48c3d\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"text\":\"LEADTEST99\",\"text_color\":\"#ffffff\",\"has_logo\":true,\"preview_path\":\"customizations\\/custom-ddc7055d35708d20.png\",\"email\":\"lead@example.com\",\"whatsapp\":\"03001234567\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-22 06:32:30', '2026-09-22 06:32:30'),
(13, '65ap6le0qiqvk175ftipdvimoo', NULL, NULL, NULL, '{\"17-0-6ab224ced887b\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"garment_color\":\"#00ff00\",\"front_logo_count\":2,\"back_name\":\"RONALDO\",\"back_number\":\"7\",\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-bed562e720a48406.png\",\"preview_back_path\":\"customizations\\/custom-309897bfd3e8bcc5.png\",\"email\":\"lead2@example.com\",\"whatsapp\":\"03111234567\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-22 06:48:46', '2026-09-22 06:48:46'),
(14, 'gi9d0tp237s9v5ajp5v5uqpq7e', NULL, NULL, NULL, '{\"17-0-6ab2296974fbc\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"garment_color\":\"#3355ff\",\"front_logo_count\":1,\"back_name\":\"ALI\",\"back_number\":\"9\",\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-ffef83a6721b9164.png\",\"preview_back_path\":\"customizations\\/custom-913043819e18ac3a.png\",\"email\":\"coach@example.com\",\"whatsapp\":\"03211234567\",\"team_order\":true}},\"17-0-6ab229697afef\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"garment_color\":\"#3355ff\",\"front_logo_count\":1,\"back_name\":\"HASSAN\",\"back_number\":\"10\",\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-ffef83a6721b9164.png\",\"preview_back_path\":\"customizations\\/custom-c0798cf90912ea5c.png\",\"email\":\"coach@example.com\",\"whatsapp\":\"03211234567\",\"team_order\":true}},\"17-0-6ab229697c1b3\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"garment_color\":\"#3355ff\",\"front_logo_count\":1,\"back_name\":\"BILAL\",\"back_number\":\"11\",\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-ffef83a6721b9164.png\",\"preview_back_path\":\"customizations\\/custom-76d541db1f50ba96.png\",\"email\":\"coach@example.com\",\"whatsapp\":\"03211234567\",\"team_order\":true}}}', 13500.00, 3, 'active', NULL, NULL, '2026-09-22 07:08:25', '2026-09-22 07:08:25'),
(15, '4uork1oqhblubn2kqh14du5cjt', NULL, NULL, NULL, '{\"17-0-6ab3e2b6de20b\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"garment_color\":\"#00ff00\",\"font\":\"Anton, sans-serif\",\"front_logo_count\":2,\"logo_vectors\":[{\"vector_path\":\"customizer-logos\\/vectors\\/vec-252d9c594241fb98.svg\",\"is_original_vector\":false},{\"vector_path\":\"customizer-logos\\/vectors\\/vec-b90b2d099f008979.svg\",\"is_original_vector\":true}],\"front_number_enabled\":true,\"front_number\":\"7\",\"back_name\":\"RONALDO\",\"back_name_size\":55,\"back_number\":\"7\",\"back_number_size\":170,\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-3fd63b54ba585086.png\",\"preview_back_path\":\"customizations\\/custom-cebec06dce0a4e91.png\",\"email\":\"lead3@example.com\",\"whatsapp\":\"03111234567\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-23 14:31:18', '2026-09-23 14:31:18'),
(16, 'ver8e8lc7rr33c8kq669g9g8mr', NULL, NULL, NULL, '{\"17-0-6ab3e4f4814e0\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"garment_color\":\"\",\"font\":\"Oswald, sans-serif\",\"front_logo_count\":1,\"logo_vectors\":[],\"front_number_enabled\":false,\"front_number\":\"\",\"back_name\":\"TESTER\",\"back_name_size\":50,\"back_number\":\"99\",\"back_number_size\":150,\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-9c179d49266f37ae.png\",\"preview_back_path\":\"customizations\\/custom-d53fcbbe2a159926.png\",\"email\":\"separate-page@example.com\",\"whatsapp\":\"03009999999\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-23 14:40:52', '2026-09-23 14:40:52'),
(17, '0h4i831bma04qf2ipuj41gpitj', NULL, NULL, NULL, '{\"17-0\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":null}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-23 14:41:00', '2026-09-23 14:41:00'),
(18, 'dp6f5sifls702te5a81g7lhc3m', NULL, NULL, NULL, '{\"17-0-6ab3e67c10665\":{\"product_id\":17,\"variation_id\":null,\"name\":\"Pro Boxing Gloves\",\"slug\":\"pro-boxing-gloves\",\"variation_key\":null,\"sku\":\"BC-DBCB28\",\"price\":4500,\"image\":\"products\\/prod-pro-boxing-gloves.jpg\",\"qty\":1,\"customization\":{\"color\":\"Black\",\"garment_color\":\"\",\"font\":\"Oswald, sans-serif\",\"front_logo_count\":0,\"logo_vectors\":[],\"front_number_enabled\":false,\"front_number\":\"\",\"back_name\":\"REVERTED\",\"back_name_size\":50,\"back_number\":\"1\",\"back_number_size\":150,\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-c0c26fa1bb96b70a.png\",\"preview_back_path\":\"customizations\\/custom-31e6958c07e7be8b.png\",\"email\":\"revert-check@example.com\",\"whatsapp\":\"03001112222\"}}}', 4500.00, 1, 'active', NULL, NULL, '2026-09-23 14:47:24', '2026-09-23 14:47:24'),
(19, 'vvqf2q89or5avffpm21dshvd5e', NULL, NULL, NULL, '{\"26-0-6ab3ed0fdbf94\":{\"product_id\":26,\"variation_id\":null,\"name\":\"Football Team Jersey Set\",\"slug\":\"football-team-jersey-set\",\"variation_key\":null,\"sku\":\"BC-F9C82A\",\"price\":5000,\"image\":\"products\\/prod-football-team-jersey-set.jpg\",\"qty\":1,\"customization\":{\"color\":\"\",\"garment_color\":\"\",\"font\":\"Oswald, sans-serif\",\"front_logo_count\":1,\"logo_vectors\":[],\"shorts_logo_count\":1,\"shorts_logo_vectors\":[],\"front_number_enabled\":false,\"front_number\":\"\",\"back_name\":\"KITTEST\",\"back_name_size\":50,\"back_number\":\"10\",\"back_number_size\":150,\"text_color\":\"#ffffff\",\"preview_path\":\"customizations\\/custom-4e764abf4c99d46b.png\",\"preview_back_path\":\"customizations\\/custom-8ecc9ce553598da1.png\",\"preview_shorts_path\":\"customizations\\/custom-c11576ff01dd289e.png\",\"email\":\"kit@example.com\",\"whatsapp\":\"03005556666\",\"is_kit\":true}}}', 5000.00, 1, 'active', NULL, NULL, '2026-09-23 15:15:27', '2026-09-23 15:15:27');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` varchar(300) DEFAULT NULL,
  `image` varchar(500) NOT NULL,
  `image_alt` varchar(255) DEFAULT NULL,
  `button_text` varchar(100) DEFAULT NULL,
  `button_url` varchar(500) DEFAULT NULL,
  `placement` enum('hero','promo') NOT NULL DEFAULT 'hero',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `subtitle`, `image`, `image_alt`, `button_text`, `button_url`, `placement`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(2, 'Gear Up For Victory', 'New Season Collection', 'banners/hero-0.jpg', 'Gear Up For Victory', 'Shop Now', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/category/footballs', 'hero', 0, 'active', '2026-09-21 19:24:57', '2026-09-21 19:24:57'),
(3, 'Built For Champions', 'Premium Sports Equipment, Made in Sialkot', 'banners/hero-1.jpg', 'Built For Champions', 'Explore Products', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/category/boxing-mma-gear', 'hero', 1, 'active', '2026-09-21 19:24:57', '2026-09-21 19:24:57'),
(4, 'Wholesale & Bulk Orders Welcome', 'Contact us for custom pricing on large orders', 'banners/promo-0.jpg', 'Bulk Orders', 'Get a Quote', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/contact', 'promo', 0, 'active', '2026-09-21 19:24:57', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `excerpt` varchar(500) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `featured_image_alt` varchar(255) DEFAULT NULL,
  `author` varchar(150) DEFAULT NULL,
  `meta_title` varchar(70) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `slug`, `excerpt`, `content`, `featured_image`, `featured_image_alt`, `author`, `meta_title`, `meta_description`, `views`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'Choosing the Right Football for Your Level', 'choosing-the-right-football-for-your-level', 'A guide to picking match vs training footballs based on your playing level and surface.', '<p>A guide to picking match vs training footballs based on your playing level and surface.</p><p>At BuiltCo Sports, we believe the right equipment makes all the difference. Our team has put together this guide based on years of experience supplying athletes, clubs, and academies.</p><p>Reach out to our team if you have questions about choosing the right gear for your needs.</p>', 'blog/blog-choosing-the-right-football-for-your-level.jpg', 'Choosing the Right Football for Your Level', 'BuiltCo Sports Team', NULL, NULL, 0, 'published', '2026-09-18 00:24:57', '2026-09-21 19:24:57', '2026-09-21 19:24:57'),
(2, '5 Boxing Gloves Care Tips to Extend Their Life', '5-boxing-gloves-care-tips-to-extend-their-life', 'Simple maintenance habits that keep your gloves in top shape for longer.', '<p>Simple maintenance habits that keep your gloves in top shape for longer.</p><p>At BuiltCo Sports, we believe the right equipment makes all the difference. Our team has put together this guide based on years of experience supplying athletes, clubs, and academies.</p><p>Reach out to our team if you have questions about choosing the right gear for your needs.</p>', 'blog/blog-5-boxing-gloves-care-tips-to-extend-their-life.jpg', '5 Boxing Gloves Care Tips to Extend Their Life', 'BuiltCo Sports Team', NULL, NULL, 0, 'published', '2026-09-14 00:24:57', '2026-09-21 19:24:57', '2026-09-21 19:24:57'),
(3, 'Why Bulk Buying Sports Gear Makes Sense for Clubs', 'why-bulk-buying-sports-gear-makes-sense-for-clubs', 'How academies and clubs can save significantly by ordering equipment in bulk.', '<p>How academies and clubs can save significantly by ordering equipment in bulk.</p><p>At BuiltCo Sports, we believe the right equipment makes all the difference. Our team has put together this guide based on years of experience supplying athletes, clubs, and academies.</p><p>Reach out to our team if you have questions about choosing the right gear for your needs.</p>', 'blog/blog-why-bulk-buying-sports-gear-makes-sense-for-clubs.jpg', 'Why Bulk Buying Sports Gear Makes Sense for Clubs', 'BuiltCo Sports Team', NULL, NULL, 0, 'published', '2026-09-10 00:24:57', '2026-09-21 19:24:57', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `bulk_inquiries`
--

CREATE TABLE `bulk_inquiries` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL COMMENT 'snapshot, survives product deletion',
  `customer_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `quantity_needed` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','contacted','quoted','closed') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL COMMENT 'Long SEO content shown on category page',
  `image` varchar(500) DEFAULT NULL,
  `image_alt` varchar(255) DEFAULT NULL COMMENT 'alt attribute for category image (SEO)',
  `meta_title` varchar(70) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `focus_keyword` varchar(150) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL COMMENT 'leave empty to use default category URL',
  `show_in_nav` tinyint(1) NOT NULL DEFAULT 1,
  `nav_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `parent_id`, `short_description`, `description`, `image`, `image_alt`, `meta_title`, `meta_description`, `focus_keyword`, `canonical_url`, `show_in_nav`, `nav_order`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Historical Costumes', 'historical-costumes', NULL, 'Historical costumes and uniforms, handcrafted with period-accurate detailing and premium materials.', 'Explore our collection of handcrafted historical costumes and period uniforms, made in Sialkot by skilled tailors and embroiderers. Each piece is built for reenactors, theatre and film productions, museums, and collectors who want authentic detail, from hand-finished braid and bullion wire embroidery to correctly cut tunics, coats, and jackets. We use premium wool, velvet, and cotton fabrics with era-appropriate buttons and trims. Custom sizing, bulk orders for troupes and productions, and made-to-order designs from your reference images are all available. Worldwide shipping.', 'categories/cat-f3db639a7c84.webp', 'Handcrafted historical costume uniform with embroidered detailing by BUILTCO Sports', 'Historical Costumes & Period Uniforms | BUILTCO Sports', 'Handcrafted historical costumes and period uniforms for reenactment, theatre and collectors. Period-accurate detailing, premium fabrics, custom sizing.', 'historical costumes', '', 1, 1, 'active', '2026-09-21 19:24:56', '2026-09-24 17:21:00'),
(4, 'Boxing & MMA Gear', 'boxing-mma-gear', NULL, 'Gloves, pads, and bags built for serious training.', NULL, 'categories/cat-boxing-mma-gear.jpg', 'Boxing & MMA Gear', NULL, NULL, NULL, NULL, 1, 1, 'active', '2026-09-21 19:24:56', '2026-09-21 19:24:56'),
(5, 'Gym & Fitness', 'gym-fitness', NULL, 'Free weights, resistance gear, and fitness accessories.', NULL, 'categories/cat-gym-fitness.jpg', 'Gym & Fitness', NULL, NULL, NULL, NULL, 1, 2, 'active', '2026-09-21 19:24:56', '2026-09-21 19:24:56'),
(6, 'Fantasy & Cosplay Costumes', 'fantasy-cosplay-costume', NULL, 'Handcrafted fantasy costumes, LARP armor and cosplay outfits in premium wool and leather.', 'Step into character with our handcrafted fantasy and cosplay costumes, made in Sialkot by skilled tailors and leatherworkers. From hooded battle mage robes and layered leather armor to warrior tunics, cloaks and ranger outfits, every piece is built for LARP events, Renaissance fairs, conventions, photoshoots and stage productions. We combine heavy wool and velvet fabrics with hand-stitched leather, antique brass buckles and riveted detailing that holds up to real wear, not just display. Custom sizing, colour changes, group orders and made-to-order designs from your own character concepts are all available, with worldwide shipping.', 'categories/cat-60a697dcbcd2.webp', 'Red hooded battle mage robe with brown leather armor, fantasy LARP costume by BUILTCO Sports', 'Fantasy & Cosplay Costumes | LARP Armor | BUILTCO Sports', 'Handcrafted fantasy and cosplay costumes, LARP armor and mage robes in premium wool and leather. Custom sizing and worldwide shipping.', 'fantasy costumes', '', 1, 2, 'active', '2026-09-21 19:24:56', '2026-09-24 17:27:43'),
(7, 'Team Uniforms', 'team-uniforms', NULL, 'Custom jerseys and kits for clubs and academies.', NULL, 'categories/cat-team-uniforms.jpg', 'Team Uniforms', NULL, NULL, NULL, NULL, 1, 4, 'active', '2026-09-21 19:24:56', '2026-09-21 19:24:56'),
(8, 'Cycling Gear', 'cycling-gear', NULL, 'Helmets, gloves, and apparel for every ride.', NULL, 'categories/cat-cycling-gear.jpg', 'Cycling Gear', NULL, NULL, NULL, NULL, 1, 5, 'active', '2026-09-21 19:24:56', '2026-09-21 19:24:56'),
(12, 'LARP Costumes & Armor', 'larp-costumes-armor', 6, 'Handmade LARP costumes and leather armor built for real events, battles and long wear.', 'Our LARP costumes and leather armor are made for players who actually move, fight and camp in their kit. Each piece is handcrafted in Sialkot using heavy wool robes and tunics paired with hand-stitched leather pauldrons, cuirasses, bracers and belts, finished with antique brass buckles and rivets. Straps are adjustable so armor fits over layers and moves with you through a full weekend event. Choose from battle mage robes, warrior sets, ranger outfits and individual armor pieces, or send us your character concept for a made-to-order build. Custom sizing, colour changes, guild and group orders are welcome, with worldwide shipping.', 'categories/cat-951e3b8c61c2.webp', 'Handmade LARP leather armor with pauldrons and cuirass over crimson wool robe by BUILTCO Sports', 'Handmade LARP Costumes & Leather Armor | BUILTCO Sports', 'Handmade LARP costumes and leather armor: mage robes, pauldrons, bracers and belts built for real events. Custom sizing, group orders, worldwide shipping.', 'LARP costumes', '', 1, 0, 'active', '2026-09-24 17:29:56', '2026-09-24 17:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `certifications`
--

CREATE TABLE `certifications` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `issuer` varchar(200) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certifications`
--

INSERT INTO `certifications` (`id`, `title`, `issuer`, `certificate_number`, `issued_date`, `image`, `status`, `sort_order`, `created_at`) VALUES
(4, 'Sialkot Chamber of Commerce & Industry', 'SCCI', 'MC-2024-0451', '2024-01-15', 'certifications/cert-0.jpg', 'active', 0, '2026-09-21 19:24:57'),
(5, 'ISO 9001:2015 Certified', 'SGS', 'ISO-88213', '2024-01-15', 'certifications/cert-1.jpg', 'active', 1, '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `expires_at` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `value`, `min_order`, `max_uses`, `used_count`, `expires_at`, `status`, `created_at`) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 2000.00, 100, 0, NULL, 'active', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `design_requests`
--

CREATE TABLE `design_requests` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL COMMENT 'snapshot, survives product deletion',
  `customer_name` varchar(150) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `original_image_path` varchar(500) NOT NULL,
  `vector_svg_path` varchar(500) DEFAULT NULL,
  `mockup_front_path` varchar(500) DEFAULT NULL,
  `mockup_back_path` varchar(500) DEFAULT NULL,
  `mockup_sleeve_path` varchar(500) DEFAULT NULL,
  `status` enum('new','vectorized','mockup_ready','approved','rejected') NOT NULL DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `features`
--

CREATE TABLE `features` (
  `id` int(11) NOT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'fa-star',
  `title` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `features`
--

INSERT INTO `features` (`id`, `icon`, `title`, `description`, `sort_order`, `status`, `created_at`) VALUES
(1, 'fa-truck-fast', 'Fast Shipping', 'Delivered across the country', 0, 'active', '2026-09-21 19:24:57'),
(2, 'fa-shield-halved', 'Secure Payment', 'Your payments are always protected', 1, 'active', '2026-09-21 19:24:57'),
(3, 'fa-medal', 'Quality Assured', 'Every product is quality-checked', 2, 'active', '2026-09-21 19:24:57'),
(4, 'fa-headset', '24/7 Support', 'We are here whenever you need us', 3, 'active', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `footer_columns`
--

CREATE TABLE `footer_columns` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `footer_columns`
--

INSERT INTO `footer_columns` (`id`, `title`, `sort_order`, `status`) VALUES
(1, 'Shop', 0, 'active'),
(2, 'Company', 1, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `footer_links`
--

CREATE TABLE `footer_links` (
  `id` int(11) NOT NULL,
  `column_id` int(11) NOT NULL,
  `label` varchar(150) NOT NULL,
  `url` varchar(500) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `footer_links`
--

INSERT INTO `footer_links` (`id`, `column_id`, `label`, `url`, `sort_order`, `status`) VALUES
(1, 1, 'Footballs', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/category/footballs', 0, 'active'),
(2, 1, 'Boxing & MMA Gear', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/category/boxing-mma-gear', 1, 'active'),
(3, 1, 'Gym & Fitness', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/category/gym-fitness', 2, 'active'),
(4, 1, 'Cricket Equipment', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/category/cricket-equipment', 3, 'active'),
(5, 2, 'About Us', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/about-us', 0, 'active'),
(6, 2, 'Privacy Policy', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/privacy-policy', 1, 'active'),
(7, 2, 'Terms & Conditions', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/terms-conditions', 2, 'active'),
(8, 2, 'FAQs', 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/faqs', 3, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `label` varchar(100) NOT NULL,
  `link_type` enum('category','custom') NOT NULL DEFAULT 'custom',
  `category_id` int(11) DEFAULT NULL,
  `custom_url` varchar(500) DEFAULT NULL,
  `open_new_tab` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `parent_id`, `label`, `link_type`, `category_id`, `custom_url`, `open_new_tab`, `sort_order`, `status`, `created_at`) VALUES
(1, NULL, 'Historical Costumes', 'category', 3, NULL, 0, 0, 'active', '2026-09-21 19:24:57'),
(2, NULL, 'Boxing & MMA Gear', 'category', 4, NULL, 0, 1, 'active', '2026-09-21 19:24:57'),
(3, NULL, 'Gym & Fitness', 'category', 5, NULL, 0, 2, 'active', '2026-09-21 19:24:57'),
(4, NULL, 'Fantasy & Cosplay Costumes', 'category', 6, NULL, 0, 3, 'active', '2026-09-21 19:24:57'),
(5, NULL, 'Team Uniforms', 'category', 7, NULL, 0, 4, 'active', '2026-09-21 19:24:57'),
(6, 4, 'LARP Costumes & Armor', 'category', 12, NULL, 0, 5, 'active', '2026-09-21 19:24:57'),
(7, NULL, 'Blog', 'custom', NULL, 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/blog', 0, 6, 'active', '2026-09-21 19:24:57'),
(8, NULL, 'Contact', 'custom', NULL, 'http://localhostC:/xampp/htdocs/builtco-sports-21-09-2026/contact', 0, 7, 'active', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('subscribed','unsubscribed') NOT NULL DEFAULT 'subscribed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `shipping_address` varchar(500) DEFAULT NULL,
  `shipping_city` varchar(100) DEFAULT NULL,
  `shipping_country` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cod',
  `payment_screenshot` varchar(500) DEFAULT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled','failed','refunded') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `customer_name`, `customer_email`, `customer_phone`, `customer_id`, `shipping_address`, `shipping_city`, `shipping_country`, `notes`, `subtotal`, `shipping_cost`, `discount`, `coupon_code`, `total`, `payment_method`, `payment_screenshot`, `status`, `created_at`, `updated_at`) VALUES
(4, 'ORD-2C24A30B-20260922', 'Demo Customer', 'demo-customer@example.com', '03001234567', NULL, '123 Model Town', 'Lahore', 'Pakistan', NULL, 15597.00, 0.00, 0.00, NULL, 15597.00, 'remitly', NULL, 'delivered', '2026-09-21 19:24:57', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variation_key` varchar(255) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `customization` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `variation_key`, `sku`, `customization`, `price`, `quantity`, `subtotal`) VALUES
(3, 4, 14, 'Pro Match Football Size 5', NULL, NULL, NULL, 2199.00, 3, 6597.00),
(4, 4, 17, 'Pro Boxing Gloves', NULL, NULL, NULL, 4500.00, 2, 9000.00);

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `content` longtext DEFAULT NULL,
  `banner_image` varchar(500) DEFAULT NULL,
  `banner_image_alt` varchar(255) DEFAULT NULL,
  `side_image` varchar(500) DEFAULT NULL,
  `side_image_alt` varchar(255) DEFAULT NULL,
  `meta_title` varchar(70) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `banner_image`, `banner_image_alt`, `side_image`, `side_image_alt`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'About Us', 'about-us', '<h2>Who We Are</h2><p>BuiltCo Sports is a Sialkot-based manufacturer and supplier of premium sports equipment, serving athletes, clubs, and retailers across Pakistan and internationally.</p><p>With years of craftsmanship rooted in Sialkot\'s renowned sporting goods industry, we combine traditional quality with modern manufacturing standards.</p><h2>Our Commitment</h2><p>Every product that leaves our facility is quality-checked to ensure it meets the standards our customers expect.</p>', NULL, NULL, NULL, NULL, NULL, NULL, 'published', '2026-09-21 19:24:57', '2026-09-21 19:24:57'),
(2, 'Privacy Policy', 'privacy-policy', '<p>This Privacy Policy explains how BuiltCo Sports collects, uses, and protects your personal information when you use our website.</p><h2>Information We Collect</h2><p>We collect information you provide directly, such as your name, email, and shipping address when placing an order.</p><h2>How We Use Your Information</h2><p>Your information is used solely to process orders, provide customer support, and improve our services.</p>', NULL, NULL, NULL, NULL, NULL, NULL, 'published', '2026-09-21 19:24:57', '2026-09-21 19:24:57'),
(3, 'Terms & Conditions', 'terms-conditions', '<p>By using this website and placing an order, you agree to the following terms.</p><h2>Orders & Payment</h2><p>All orders are subject to availability. Payment must be completed using one of our accepted payment methods before an order is processed.</p><h2>Shipping</h2><p>Delivery times may vary based on location and product availability.</p>', NULL, NULL, NULL, NULL, NULL, NULL, 'published', '2026-09-21 19:24:57', '2026-09-21 19:24:57'),
(4, 'FAQs', 'faqs', '<h2>Do you offer bulk/wholesale pricing?</h2><p>Yes — contact us with your requirements and we\'ll provide a custom quote.</p><h2>What payment methods do you accept?</h2><p>We currently accept payments via Remitly.</p><h2>Do you ship internationally?</h2><p>Yes, please contact us for shipping quotes to your country.</p>', NULL, NULL, NULL, NULL, NULL, NULL, 'published', '2026-09-21 19:24:57', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `page_visits`
--

CREATE TABLE `page_visits` (
  `id` int(11) NOT NULL,
  `url` varchar(500) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `is_unique` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = first visit of this browser session today',
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_visits`
--

INSERT INTO `page_visits` (`id`, `url`, `ip_address`, `referrer`, `is_unique`, `visited_at`) VALUES
(1, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 07:43:09'),
(2, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 07:43:10'),
(3, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 07:43:20'),
(4, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 07:43:20'),
(5, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 07:43:33'),
(6, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 07:43:33'),
(7, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 07:43:33'),
(8, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 07:49:52'),
(9, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 0, '2026-09-21 07:49:52'),
(10, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 07:50:01'),
(11, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 07:50:01'),
(12, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 07:50:14'),
(13, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 07:50:14'),
(14, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 0, '2026-09-21 07:50:15'),
(15, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 0, '2026-09-21 07:50:15'),
(16, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 07:50:26'),
(17, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 17:21:32'),
(18, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 17:21:42'),
(19, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 17:21:59'),
(20, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 17:22:05'),
(21, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 17:22:23'),
(22, '/builtco-sports-21-09-2026/about', '::1', '', 1, '2026-09-21 17:29:58'),
(23, '/builtco-sports-21-09-2026/about-us', '::1', '', 1, '2026-09-21 17:30:09'),
(24, '/builtco-sports-21-09-2026/about-us', '::1', '', 1, '2026-09-21 17:30:09'),
(25, '/builtco-sports-21-09-2026/page.php?slug=about-us', '::1', '', 1, '2026-09-21 17:30:17'),
(26, '/builtco-sports-21-09-2026/about', '::1', '', 1, '2026-09-21 17:31:15'),
(27, '/builtco-sports-21-09-2026/about', '::1', '', 1, '2026-09-21 17:31:24'),
(28, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 17:31:24'),
(29, '/builtco-sports-21-09-2026/contact', '::1', '', 1, '2026-09-21 17:31:24'),
(30, '/builtco-sports-21-09-2026/cart', '::1', '', 1, '2026-09-21 17:31:24'),
(31, '/builtco-sports-21-09-2026/about', '::1', '', 1, '2026-09-21 17:40:49'),
(32, '/builtco-sports-21-09-2026/about', '::1', '', 1, '2026-09-21 17:40:57'),
(33, '/builtco-sports-21-09-2026/blog', '::1', '', 1, '2026-09-21 17:45:59'),
(34, '/builtco-sports-21-09-2026/blog/5-tips-for-choosing-boxing-gloves', '::1', '', 1, '2026-09-21 17:45:59'),
(35, '/builtco-sports-21-09-2026/blog/5-tips-for-choosing-boxing-gloves', '::1', '', 1, '2026-09-21 17:46:08'),
(36, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 17:51:04'),
(37, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 17:59:20'),
(38, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 18:00:08'),
(39, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 18:00:36'),
(40, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 18:03:45'),
(41, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 18:03:45'),
(42, '/builtco-sports-21-09-2026/contact', '::1', '', 1, '2026-09-21 18:03:53'),
(43, '/builtco-sports-21-09-2026/cart', '::1', '', 1, '2026-09-21 18:03:53'),
(44, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-21 18:04:00'),
(45, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 18:11:10'),
(46, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:11:11'),
(47, '/builtco-sports-21-09-2026/order-success?order=ORD-F4AC08-20260921', '::1', '', 1, '2026-09-21 18:11:25'),
(48, '/builtco-sports-21-09-2026/foo/bar/baz-nonexistent', '::1', '', 1, '2026-09-21 18:21:59'),
(49, '/builtco-sports-21-09-2026/blog', '::1', '', 1, '2026-09-21 18:21:59'),
(50, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 18:21:59'),
(51, '/builtco-sports-21-09-2026/foo/bar/baz-nonexistent', '::1', '', 1, '2026-09-21 18:22:04'),
(52, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 18:22:12'),
(53, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 1, '2026-09-21 18:22:49'),
(54, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 0, '2026-09-21 18:22:55'),
(55, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 0, '2026-09-21 18:23:01'),
(56, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 1, '2026-09-21 18:23:14'),
(57, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 1, '2026-09-21 18:23:28'),
(58, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 0, '2026-09-21 18:23:28'),
(59, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 1, '2026-09-21 18:23:41'),
(60, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:23:41'),
(61, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:23:41'),
(62, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 1, '2026-09-21 18:23:53'),
(63, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 1, '2026-09-21 18:24:01'),
(64, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 0, '2026-09-21 18:24:08'),
(65, '/builtco-sports-21-09-2026/product/audit-test-product', '::1', '', 1, '2026-09-21 18:24:19'),
(66, '/builtco-sports-21-09-2026/cart', '::1', '', 0, '2026-09-21 18:24:19'),
(67, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:24:19'),
(68, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:24:19'),
(69, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:24:32'),
(70, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:24:32'),
(71, '/builtco-sports-21-09-2026/search?q=football', '::1', '', 1, '2026-09-21 18:31:06'),
(72, '/builtco-sports-21-09-2026/search?q=zzzznotfound', '::1', '', 1, '2026-09-21 18:31:06'),
(73, '/builtco-sports-21-09-2026/search', '::1', '', 1, '2026-09-21 18:31:06'),
(74, '/builtco-sports-21-09-2026/product/related-test-main', '::1', '', 1, '2026-09-21 18:31:50'),
(75, '/builtco-sports-21-09-2026/register', '::1', '', 1, '2026-09-21 18:35:50'),
(76, '/builtco-sports-21-09-2026/account', '::1', '', 0, '2026-09-21 18:35:55'),
(77, '/builtco-sports-21-09-2026/product/acct-test-product', '::1', '', 0, '2026-09-21 18:36:06'),
(78, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:36:06'),
(79, '/builtco-sports-21-09-2026/account', '::1', '', 0, '2026-09-21 18:36:12'),
(80, '/builtco-sports-21-09-2026/account', '::1', '', 0, '2026-09-21 18:36:20'),
(81, '/builtco-sports-21-09-2026/login', '::1', '', 0, '2026-09-21 18:36:21'),
(82, '/builtco-sports-21-09-2026/product/idor-test-product', '::1', '', 1, '2026-09-21 18:50:18'),
(83, '/builtco-sports-21-09-2026/checkout', '::1', '', 0, '2026-09-21 18:50:18'),
(84, '/builtco-sports-21-09-2026/order-success?order=ORD-E60D3890-20260921', '::1', '', 0, '2026-09-21 18:50:26'),
(85, '/builtco-sports-21-09-2026/order-success?order=ORD-E60D3890-20260921', '::1', '', 0, '2026-09-21 18:50:27'),
(86, '/builtco-sports-21-09-2026/order-success?order=ORD-E60D3890-20260921', '::1', '', 0, '2026-09-21 18:50:35'),
(87, '/builtco-sports-21-09-2026/product/schema-test-product', '::1', '', 1, '2026-09-21 18:51:04'),
(88, '/builtco-sports-21-09-2026/product/schema-test-product', '::1', '', 1, '2026-09-21 18:51:10'),
(89, '/builtco-sports-21-09-2026/product/wa-test-product', '::1', '', 1, '2026-09-21 18:57:30'),
(90, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 18:57:30'),
(91, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 18:57:41'),
(92, '/builtco-sports-21-09-2026/cart', '::1', '', 1, '2026-09-21 19:03:08'),
(93, '/builtco-sports-21-09-2026/track-order?order=ORD-TRACKTEST-20260922&email=track-test@test.com', '::1', '', 1, '2026-09-21 19:07:25'),
(94, '/builtco-sports-21-09-2026/track-order?order=ORD-TRACKTEST-20260922&email=track-test@test.com', '::1', '', 1, '2026-09-21 19:08:11'),
(95, '/builtco-sports-21-09-2026/track-order?order=ORD-TRACKTEST-20260922&email=wrong@test.com', '::1', '', 1, '2026-09-21 19:08:11'),
(96, '/builtco-sports-21-09-2026/product/stock-notify-test', '::1', '', 1, '2026-09-21 19:10:03'),
(97, '/builtco-sports-21-09-2026/product/stock-notify-test', '::1', '', 1, '2026-09-21 19:10:11'),
(98, '/builtco-sports-21-09-2026/product/stock-notify-test', '::1', '', 0, '2026-09-21 19:10:11'),
(99, '/builtco-sports-21-09-2026/register', '::1', '', 1, '2026-09-21 19:12:29'),
(100, '/builtco-sports-21-09-2026/product/wishlist-test-product', '::1', '', 0, '2026-09-21 19:12:29'),
(101, '/builtco-sports-21-09-2026/product/wishlist-test-product', '::1', '', 0, '2026-09-21 19:12:39'),
(102, '/builtco-sports-21-09-2026/product/wishlist-test-product', '::1', '', 0, '2026-09-21 19:12:39'),
(103, '/builtco-sports-21-09-2026/product/wishlist-test-product', '::1', '', 0, '2026-09-21 19:12:48'),
(104, '/builtco-sports-21-09-2026/product/wishlist-test-product', '::1', '', 0, '2026-09-21 19:12:48'),
(105, '/builtco-sports-21-09-2026/wishlist', '::1', '', 0, '2026-09-21 19:12:55'),
(106, '/builtco-sports-21-09-2026/wishlist', '::1', '', 0, '2026-09-21 19:13:03'),
(107, '/builtco-sports-21-09-2026/wishlist', '::1', '', 0, '2026-09-21 19:13:03'),
(108, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 19:18:15'),
(109, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 19:18:24'),
(110, '/builtco-sports-21-09-2026/contact', '::1', '', 1, '2026-09-21 19:20:04'),
(111, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 19:25:05'),
(112, '/builtco-sports-21-09-2026/category/footballs', '::1', '', 1, '2026-09-21 19:25:05'),
(113, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 19:25:05'),
(114, '/builtco-sports-21-09-2026/blog', '::1', '', 1, '2026-09-21 19:25:05'),
(115, '/builtco-sports-21-09-2026/about-us', '::1', '', 1, '2026-09-21 19:25:05'),
(116, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 19:25:13'),
(117, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 19:25:21'),
(118, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-21 19:25:30'),
(119, '/builtco-sports-21-09-2026/product/pro-boxing-gloves', '::1', '', 1, '2026-09-21 19:25:30'),
(120, '/builtco-sports-21-09-2026/category/footballs', '::1', '', 1, '2026-09-21 19:25:37'),
(121, '/builtco-sports-21-09-2026/track-order?order=ORD-2C24A30B-20260922&email=demo-customer@example.com', '::1', '', 1, '2026-09-21 19:25:38'),
(122, '/builtco-sports-21-09-2026/product/bot-test-product', '::1', '', 1, '2026-09-21 19:34:47'),
(123, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:00:42'),
(124, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:00:56'),
(125, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:01:51'),
(126, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:02:03'),
(127, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 06:02:03'),
(128, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:02:16'),
(129, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 06:02:17'),
(130, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:12:14'),
(131, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 0, '2026-09-22 06:12:14'),
(132, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-22 06:18:48'),
(133, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:18:48'),
(134, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-22 06:18:54'),
(135, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:20:59'),
(136, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:31:29'),
(137, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:31:42'),
(138, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 06:31:42'),
(139, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 06:31:42'),
(140, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:31:56'),
(141, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:32:07'),
(142, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 06:32:07'),
(143, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:32:30'),
(144, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 06:32:30'),
(145, '/builtco-sports-21-09-2026/', '::1', 'http://localhost/', 1, '2026-09-22 06:42:24'),
(146, '/builtco-sports-21-09-2026/category/footballs', '::1', 'http://localhost/builtco-sports-21-09-2026/', 0, '2026-09-22 06:42:28'),
(147, '/builtco-sports-21-09-2026/product/pro-match-football-size-5', '::1', 'http://localhost/builtco-sports-21-09-2026/category/footballs', 0, '2026-09-22 06:42:29'),
(148, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:48:36'),
(149, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:48:46'),
(150, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 06:48:46'),
(151, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 06:49:09'),
(152, '/builtco-sports-21-09-2026/product/pro-match-football-size-5', '::1', 'http://localhost/builtco-sports-21-09-2026/category/footballs', 0, '2026-09-22 06:52:26'),
(153, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 07:08:11'),
(154, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 07:08:25'),
(155, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 07:08:25'),
(156, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 07:08:59'),
(157, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 07:08:59'),
(158, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 07:41:30'),
(159, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 07:41:38'),
(160, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 0, '2026-09-22 07:41:38'),
(161, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-22 07:41:52'),
(162, '/builtco-sports-21-09-2026/', '::1', '', 0, '2026-09-22 07:41:52'),
(163, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 0, '2026-09-22 07:41:52'),
(164, '/builtco-sports-21-09-2026/', '::1', '', 0, '2026-09-22 07:41:52'),
(165, '/builtco-sports-21-09-2026/', '::1', '', 1, '2026-09-22 07:42:11'),
(166, '/builtco-sports-21-09-2026/index.php', '::1', '', 0, '2026-09-22 07:42:11'),
(167, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-22 07:42:11'),
(168, '/builtco-sports-21-09-2026/cart', '::1', '', 0, '2026-09-22 07:42:11'),
(169, '/builtco-sports-21-09-2026/category.php?slug=boxing', '::1', '', 0, '2026-09-22 07:42:11'),
(170, '/builtco-sports-21-09-2026/search.php?q=gloves', '::1', '', 0, '2026-09-22 07:42:11'),
(171, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-22 07:44:47'),
(172, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-22 07:44:47'),
(173, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-22 07:44:56'),
(174, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:30:38'),
(175, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:30:47'),
(176, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:30:57'),
(177, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:31:06'),
(178, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:31:18'),
(179, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-23 14:31:18'),
(180, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:31:30'),
(181, '/builtco-sports-21-09-2026/', '::1', 'http://localhost/', 1, '2026-09-23 14:32:13'),
(182, '/builtco-sports-21-09-2026/category/footballs', '::1', 'http://localhost/builtco-sports-21-09-2026/', 0, '2026-09-23 14:32:19'),
(183, '/builtco-sports-21-09-2026/product/pro-match-football-size-5', '::1', 'http://localhost/builtco-sports-21-09-2026/category/footballs', 0, '2026-09-23 14:32:21'),
(184, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-23 14:34:40'),
(185, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:40:35'),
(186, '/builtco-sports-21-09-2026/customize.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:40:35'),
(187, '/builtco-sports-21-09-2026/customize/pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:40:41'),
(188, '/builtco-sports-21-09-2026/customize.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:40:52'),
(189, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-23 14:40:52'),
(190, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:41:00'),
(191, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-23 14:41:00'),
(192, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:41:15'),
(193, '/builtco-sports-21-09-2026/product/pro-match-football-size-5', '::1', 'http://localhost/builtco-sports-21-09-2026/category/footballs', 0, '2026-09-23 14:41:29'),
(194, '/builtco-sports-21-09-2026/customize/pro-match-football-size-5', '::1', 'http://localhost/builtco-sports-21-09-2026/product/pro-match-football-size-5', 0, '2026-09-23 14:41:32'),
(195, '/builtco-sports-21-09-2026/customize/pro-match-football-size-5', '::1', 'http://localhost/builtco-sports-21-09-2026/product/pro-match-football-size-5', 0, '2026-09-23 14:42:27'),
(196, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:47:05'),
(197, '/builtco-sports-21-09-2026/customize/pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:47:11'),
(198, '/builtco-sports-21-09-2026/product.php?slug=pro-boxing-gloves', '::1', '', 1, '2026-09-23 14:47:23'),
(199, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-23 14:47:24'),
(200, '/builtco-sports-21-09-2026/kit-builder.php', '::1', '', 1, '2026-09-23 15:15:15'),
(201, '/builtco-sports-21-09-2026/kit-builder.php?slug=football-team-jersey-set', '::1', '', 1, '2026-09-23 15:15:16'),
(202, '/builtco-sports-21-09-2026/kit-builder/football-team-jersey-set', '::1', '', 1, '2026-09-23 15:15:16'),
(203, '/builtco-sports-21-09-2026/kit-builder.php?slug=football-team-jersey-set', '::1', '', 1, '2026-09-23 15:15:27'),
(204, '/builtco-sports-21-09-2026/cart.php', '::1', '', 0, '2026-09-23 15:15:27'),
(205, '/builtco-sports-21-09-2026/kit-builder.php', '::1', '', 1, '2026-09-23 15:16:23'),
(206, '/builtco-sports-21-09-2026/login', '::1', 'http://localhost/builtco-sports-21-09-2026/customize/pro-match-football-size-5', 1, '2026-09-24 17:09:33'),
(207, '/builtco-sports-21-09-2026/index.php', '::1', '', 1, '2026-09-24 17:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `instructions` text DEFAULT NULL COMMENT 'Shown to the customer at checkout',
  `youtube_url` varchar(500) DEFAULT NULL COMMENT 'Tutorial video on how to pay',
  `account_details` text DEFAULT NULL COMMENT 'e.g. account name/number to send payment to',
  `requires_screenshot` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Customer must upload proof of payment',
  `status` enum('active','inactive') NOT NULL DEFAULT 'inactive',
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `code`, `name`, `instructions`, `youtube_url`, `account_details`, `requires_screenshot`, `status`, `sort_order`) VALUES
(1, 'payfast', 'PayFast', 'Pay securely online via PayFast.', NULL, NULL, 0, 'inactive', 1),
(2, 'remitly', 'Remitly', 'Send your payment via Remitly, then upload a screenshot of the confirmation below.', '', '', 1, 'active', 2);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `status` enum('active','draft','archived') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_new_arrival` tinyint(1) NOT NULL DEFAULT 0,
  `is_coming_soon` tinyint(1) NOT NULL DEFAULT 0,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `views` int(11) NOT NULL DEFAULT 0,
  `track_stock` tinyint(1) NOT NULL DEFAULT 1,
  `weight_kg` decimal(10,3) DEFAULT NULL,
  `qty_price_rules` text DEFAULT NULL COMMENT 'JSON: [{"min_qty":10,"price":9.5}, ...]',
  `show_bulk_dm` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Show "Request Bulk Quote" CTA on product page',
  `is_customizable` tinyint(1) NOT NULL DEFAULT 1,
  `accepts_design_requests` tinyint(1) NOT NULL DEFAULT 0,
  `meta_title` varchar(70) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `focus_keyword` varchar(150) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `sku`, `category_id`, `short_description`, `description`, `tags`, `status`, `is_featured`, `is_new_arrival`, `is_coming_soon`, `base_price`, `sale_price`, `stock_quantity`, `views`, `track_stock`, `weight_kg`, `qty_price_rules`, `show_bulk_dm`, `is_customizable`, `accepts_design_requests`, `meta_title`, `meta_description`, `focus_keyword`, `canonical_url`, `created_at`, `updated_at`) VALUES
(14, 'Pro Match Football Size 5', 'pro-match-football-size-5', 'BC-096A28', 3, 'Premium quality Pro Match Football Size 5, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Pro Match Football Size 5, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 1, 1, 0, 2500.00, 2199.00, 41, 4, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-23 14:41:29'),
(15, 'Training Football Size 4', 'training-football-size-4', 'BC-7D36AE', 3, 'Premium quality Training Football Size 4, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Training Football Size 4, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 0, 1800.00, NULL, 45, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(16, 'Futsal Football', 'futsal-football', 'BC-28F433', 3, 'Premium quality Futsal Football, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Futsal Football, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 1, 2000.00, NULL, 0, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(17, 'Pro Boxing Gloves', 'pro-boxing-gloves', 'BC-DBCB28', 4, 'Premium quality Pro Boxing Gloves, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Pro Boxing Gloves, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 1, 0, 0, 4500.00, NULL, 40, 38, 1, NULL, '[{\"min_qty\":10,\"price\":4050},{\"min_qty\":50,\"price\":3600}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-23 14:47:23'),
(18, 'MMA Grappling Gloves', 'mma-grappling-gloves', 'BC-854E0F', 4, 'Premium quality MMA Grappling Gloves, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality MMA Grappling Gloves, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 1, 0, 3200.00, NULL, 44, 0, 1, NULL, '[{\"min_qty\":10,\"price\":2880},{\"min_qty\":50,\"price\":2560}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(19, 'Heavy Duty Punching Bag', 'heavy-duty-punching-bag', 'BC-FE4A9F', 4, 'Premium quality Heavy Duty Punching Bag, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Heavy Duty Punching Bag, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 0, 12000.00, 10999.00, 32, 0, 1, NULL, '[{\"min_qty\":10,\"price\":10800},{\"min_qty\":50,\"price\":9600}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(20, 'Adjustable Dumbbell Set', 'adjustable-dumbbell-set', 'BC-194635', 5, 'Premium quality Adjustable Dumbbell Set, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Adjustable Dumbbell Set, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 1, 0, 0, 15000.00, NULL, 19, 0, 1, NULL, '[{\"min_qty\":10,\"price\":13500},{\"min_qty\":50,\"price\":12000}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(21, 'Resistance Bands Set', 'resistance-bands-set', 'BC-CC3F2B', 5, 'Premium quality Resistance Bands Set, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Resistance Bands Set, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 1, 0, 1500.00, NULL, 21, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(22, 'Weight Lifting Belt', 'weight-lifting-belt', 'BC-11DD2B', 5, 'Premium quality Weight Lifting Belt, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Weight Lifting Belt, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 0, 2800.00, NULL, 48, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(23, 'Pro Cricket Bat - English Willow', 'pro-cricket-bat-english-willow', 'BC-1810A8', 6, 'Premium quality Pro Cricket Bat - English Willow, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Pro Cricket Bat - English Willow, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 1, 0, 0, 8500.00, 7999.00, 40, 0, 1, NULL, '[{\"min_qty\":10,\"price\":7650},{\"min_qty\":50,\"price\":6800}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(24, 'Cricket Batting Gloves', 'cricket-batting-gloves', 'BC-26B3F0', 6, 'Premium quality Cricket Batting Gloves, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Cricket Batting Gloves, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 0, 2200.00, NULL, 44, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(25, 'Cricket Helmet', 'cricket-helmet', 'BC-5D9EDE', 6, 'Premium quality Cricket Helmet, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Cricket Helmet, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 1, 0, 3500.00, NULL, 25, 0, 1, NULL, '[{\"min_qty\":10,\"price\":3150},{\"min_qty\":50,\"price\":2800}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(26, 'Football Team Jersey Set', 'football-team-jersey-set', 'BC-F9C82A', 7, 'Premium quality Football Team Jersey Set, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Football Team Jersey Set, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 0, 5000.00, NULL, 35, 0, 1, NULL, '[{\"min_qty\":10,\"price\":4500},{\"min_qty\":50,\"price\":4000}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:56', '2026-09-22 06:30:32'),
(27, 'Crimson Battlemage LARP Robe with Brown Leather Armor Set', 'larp-robe-with-leather-armor', 'BC-FCLCA-001', 12, 'Floor-length crimson hooded robe paired with a full brown leather armor set for a striking battlemage look.', '<h2>Crimson Battlemage LARP Robe with Leather Armor Set</h2><p>Step into the role of a battle-hardened mage with this <strong>LARP robe with leather armor</strong>. It combines a floor-length crimson hooded robe with a full brown leather armor set, and it\'s built for LARP events, cosplay conventions, renaissance faires and fantasy photoshoots.</p><h3>The Crimson Hooded Robe</h3><ul><li>Deep hood for a mysterious, dramatic silhouette</li><li>Wide sleeves with leather-guarded cuffs</li><li>Full-length front opening and rear walking slit for free movement</li><li>Flowing floor-length cut that moves dramatically in action</li></ul><h3>Full Leather Armor Set</h3><ul><li><strong>Chest plate</strong> with embossed shield crest and three adjustable buckle straps</li><li><strong>Layered shoulder pauldrons</strong> with riveted edges</li><li><strong>Raised leather collar</strong> for a structured, armored neckline</li><li><strong>Crossed back harness</strong> with antique brass rings holding the pauldrons in place</li></ul><h3>Belt, Pouches &amp; Tassets</h3><ul><li>Wide double belt with antique brass buckle</li><li>Side pouches for small props and essentials</li><li>Hanging brass O-rings for attaching accessories</li><li>Riveted leather tassets over the hips</li></ul><h3>Craftsmanship &amp; Details</h3><p>Every leather panel is edge-stitched and finished with antique brass rivets and buckles, giving the set a worn, battle-ready character that looks authentic up close and on camera.</p><h3>Adjustable Fit</h3><p>Buckles on the chest, arms and belt let you adjust the armor for a secure, comfortable fit over the robe.</p><h3>Who It\'s For</h3><ul><li>LARP players building a mage, sorcerer or battlemage character</li><li>Cosplayers looking for a ready-to-wear fantasy costume</li><li>Renaissance faire and medieval festival goers</li><li>Photographers and content creators needing a striking fantasy outfit</li></ul><h3>Sizing &amp; Custom Orders</h3><p>Contact us for sizing guidance, custom colors or bulk orders for groups and events before placing your order.</p>', 'larp costume, battlemage robe, leather larp armor, fantasy mage costume, red hooded robe, medieval cosplay armor, renaissance faire costume, fire mage cosplay', 'active', 1, 1, 1, 289.00, NULL, 1, 0, 1, 4.000, '[{\"min_qty\":10,\"price\":449.99},{\"min_qty\":50,\"price\":419.99}]', 1, 0, 1, 'LARP Robe with Leather Armor Set | BuiltCo Sports', 'Crimson LARP robe with leather armor: chest plate, pauldrons, belt and tassets. Adjustable fit for LARP, cosplay and faires. Order yours today.', 'larp robe with leather armor', '', '2026-09-21 19:24:57', '2026-09-24 17:55:23'),
(28, 'Custom Training Bibs', 'custom-training-bibs', 'BC-BF27E9', 7, 'Premium quality Custom Training Bibs, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Custom Training Bibs, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 0, 1200.00, NULL, 17, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:57', '2026-09-22 06:30:32'),
(29, 'Cycling Gloves', 'cycling-gloves', 'BC-A4C486', 8, 'Premium quality Cycling Gloves, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Cycling Gloves, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 0, 0, 1800.00, NULL, 37, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:57', '2026-09-22 06:30:32'),
(30, 'Cycling Helmet', 'cycling-helmet', 'BC-4095A1', 8, 'Premium quality Cycling Helmet, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Cycling Helmet, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 1, 0, 0, 4200.00, NULL, 15, 0, 1, NULL, '[{\"min_qty\":10,\"price\":3780},{\"min_qty\":50,\"price\":3360}]', 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:57', '2026-09-22 06:30:32'),
(31, 'Bicycle Jersey', 'bicycle-jersey', 'BC-690E11', 8, 'Premium quality Bicycle Jersey, built for performance and durability — a BuiltCo Sports favourite.', '<p>Premium quality Bicycle Jersey, built for performance and durability — a BuiltCo Sports favourite. Made with attention to detail using materials sourced and crafted in Sialkot, Pakistan — trusted by clubs and athletes worldwide.</p><p>Built to withstand serious use, whether for daily training or match day.</p>', NULL, 'active', 0, 1, 0, 2500.00, NULL, 44, 0, 1, NULL, NULL, 1, 1, 0, NULL, NULL, NULL, NULL, '2026-09-21 19:24:57', '2026-09-22 06:30:32');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `variation_value` varchar(100) DEFAULT NULL,
  `mockup_view` enum('front','back','sleeve','shorts') DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `variation_value`, `mockup_view`, `sort_order`, `is_primary`) VALUES
(1, 14, 'products/prod-pro-match-football-size-5.jpg', 'Pro Match Football Size 5', NULL, NULL, 0, 1),
(2, 15, 'products/prod-training-football-size-4.jpg', 'Training Football Size 4', NULL, NULL, 0, 1),
(3, 16, 'products/prod-futsal-football.jpg', 'Futsal Football', NULL, NULL, 0, 1),
(4, 17, 'products/prod-pro-boxing-gloves.jpg', 'Pro Boxing Gloves', NULL, NULL, 0, 1),
(5, 18, 'products/prod-mma-grappling-gloves.jpg', 'MMA Grappling Gloves', NULL, NULL, 0, 1),
(6, 19, 'products/prod-heavy-duty-punching-bag.jpg', 'Heavy Duty Punching Bag', NULL, NULL, 0, 1),
(7, 20, 'products/prod-adjustable-dumbbell-set.jpg', 'Adjustable Dumbbell Set', NULL, NULL, 0, 1),
(8, 21, 'products/prod-resistance-bands-set.jpg', 'Resistance Bands Set', NULL, NULL, 0, 1),
(9, 22, 'products/prod-weight-lifting-belt.jpg', 'Weight Lifting Belt', NULL, NULL, 0, 1),
(10, 23, 'products/prod-pro-cricket-bat-english-willow.jpg', 'Pro Cricket Bat - English Willow', NULL, NULL, 0, 1),
(11, 24, 'products/prod-cricket-batting-gloves.jpg', 'Cricket Batting Gloves', NULL, NULL, 0, 1),
(12, 25, 'products/prod-cricket-helmet.jpg', 'Cricket Helmet', NULL, NULL, 0, 1),
(13, 26, 'products/prod-football-team-jersey-set.jpg', 'Football Team Jersey Set', NULL, NULL, 0, 1),
(15, 28, 'products/prod-custom-training-bibs.jpg', 'Custom Training Bibs', NULL, NULL, 0, 1),
(16, 29, 'products/prod-cycling-gloves.jpg', 'Cycling Gloves', NULL, NULL, 0, 1),
(17, 30, 'products/prod-cycling-helmet.jpg', 'Cycling Helmet', NULL, NULL, 0, 1),
(18, 31, 'products/prod-bicycle-jersey.jpg', 'Bicycle Jersey', NULL, NULL, 0, 1),
(22, 27, 'products/prod-48a5e9a95d81.webp', 'Cricket Team Kit', NULL, 'front', 0, 1),
(23, 27, 'products/prod-1eaa9426311b.webp', 'Cricket Team Kit', NULL, 'back', 1, 0),
(24, 27, 'products/prod-adfac9e05ff8.webp', 'Cricket Team Kit', NULL, 'sleeve', 2, 0),
(25, 27, 'products/prod-991476023925.webp', 'Cricket Team Kit', NULL, 'shorts', 3, 0),
(26, 27, 'products/prod-1274fb9bfc39.webp', 'Cricket Team Kit', NULL, 'front', 4, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_spin_frames`
--

CREATE TABLE `product_spin_frames` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `frame_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_spin_frames`
--

INSERT INTO `product_spin_frames` (`id`, `product_id`, `image_path`, `frame_order`, `created_at`) VALUES
(9, 27, 'spin-frames/spin-5e17dcc95531.webp', 0, '2026-09-24 17:51:55'),
(10, 27, 'spin-frames/spin-04112a6fb910.webp', 1, '2026-09-24 17:51:57'),
(11, 27, 'spin-frames/spin-78e2871b368a.webp', 2, '2026-09-24 17:51:58'),
(12, 27, 'spin-frames/spin-d22d84238cb4.webp', 3, '2026-09-24 17:51:59'),
(13, 27, 'spin-frames/spin-e6e4240d0118.webp', 4, '2026-09-24 17:52:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_variations`
--

CREATE TABLE `product_variations` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `combination_key` varchar(255) NOT NULL COMMENT 'e.g. "Red / Large"',
  `price` decimal(10,2) DEFAULT NULL COMMENT 'overrides product base_price when set',
  `sale_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_variations`
--

INSERT INTO `product_variations` (`id`, `product_id`, `combination_key`, `price`, `sale_price`, `stock_quantity`, `sku`, `sort_order`, `status`) VALUES
(3, 17, '10oz', NULL, NULL, 17, 'BC-E973F7', 0, 'active'),
(4, 17, '12oz', NULL, NULL, 7, 'BC-D4C2C3', 1, 'active'),
(5, 17, '14oz', NULL, NULL, 20, 'BC-B9C64D', 2, 'active'),
(6, 26, 'S', NULL, NULL, 25, 'BC-A18CEF', 0, 'active'),
(7, 26, 'M', NULL, NULL, 8, 'BC-186A04', 1, 'active'),
(8, 26, 'L', NULL, NULL, 10, 'BC-8E7450', 2, 'active'),
(9, 26, 'XL', NULL, NULL, 14, 'BC-72C037', 3, 'active'),
(50, 27, 'XXS / Red / Robe Only', 289.00, NULL, 0, '', 0, 'active'),
(51, 27, 'XXS / Red / Armor Only', 269.00, NULL, 0, '', 1, 'active'),
(52, 27, 'XXS / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 2, 'active'),
(53, 27, 'XXS / Red / Full Set', 699.00, NULL, 0, '', 3, 'active'),
(54, 27, 'XS / Red / Robe Only', 289.00, NULL, 0, '', 4, 'active'),
(55, 27, 'XS / Red / Armor Only', 269.00, NULL, 0, '', 5, 'active'),
(56, 27, 'XS / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 6, 'active'),
(57, 27, 'XS / Red / Full Set', 699.00, NULL, 0, '', 7, 'active'),
(58, 27, 'S / Red / Robe Only', 289.00, NULL, 0, '', 8, 'active'),
(59, 27, 'S / Red / Armor Only', 269.00, NULL, 0, '', 9, 'active'),
(60, 27, 'S / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 10, 'active'),
(61, 27, 'S / Red / Full Set', 699.00, NULL, 0, '', 11, 'active'),
(62, 27, 'M / Red / Robe Only', 289.00, NULL, 0, '', 12, 'active'),
(63, 27, 'M / Red / Armor Only', 269.00, NULL, 0, '', 13, 'active'),
(64, 27, 'M / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 14, 'active'),
(65, 27, 'M / Red / Full Set', 699.00, NULL, 0, '', 15, 'active'),
(66, 27, 'L / Red / Robe Only', 289.00, NULL, 0, '', 16, 'active'),
(67, 27, 'L / Red / Armor Only', 269.00, NULL, 0, '', 17, 'active'),
(68, 27, 'L / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 18, 'active'),
(69, 27, 'L / Red / Full Set', 699.00, NULL, 0, '', 19, 'active'),
(70, 27, '2XL / Red / Robe Only', 289.00, NULL, 0, '', 20, 'active'),
(71, 27, '2XL / Red / Armor Only', 269.00, NULL, 0, '', 21, 'active'),
(72, 27, '2XL / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 22, 'active'),
(73, 27, '2XL / Red / Full Set', 699.00, NULL, 0, '', 23, 'active'),
(74, 27, '3XL / Red / Robe Only', 289.00, NULL, 0, '', 24, 'active'),
(75, 27, '3XL / Red / Armor Only', 269.00, NULL, 0, '', 25, 'active'),
(76, 27, '3XL / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 26, 'active'),
(77, 27, '3XL / Red / Full Set', 699.00, NULL, 0, '', 27, 'active'),
(78, 27, '4XL / Red / Robe Only', 289.00, NULL, 0, '', 28, 'active'),
(79, 27, '4XL / Red / Armor Only', 269.00, NULL, 0, '', 29, 'active'),
(80, 27, '4XL / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 30, 'active'),
(81, 27, '4XL / Red / Full Set', 699.00, NULL, 0, '', 31, 'active'),
(82, 27, '5XL / Red / Robe Only', 289.00, NULL, 0, '', 32, 'active'),
(83, 27, '5XL / Red / Armor Only', 269.00, NULL, 0, '', 33, 'active'),
(84, 27, '5XL / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 34, 'active'),
(85, 27, '5XL / Red / Full Set', 699.00, NULL, 0, '', 35, 'active'),
(86, 27, '6XL / Red / Robe Only', 289.00, NULL, 0, '', 36, 'active'),
(87, 27, '6XL / Red / Armor Only', 269.00, NULL, 0, '', 37, 'active'),
(88, 27, '6XL / Red / Robe + Chest Plate', 499.00, NULL, 0, '', 38, 'active'),
(89, 27, '6XL / Red / Full Set', 699.00, NULL, 0, '', 39, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = reviewer has a completed order containing this product',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `customer_name`, `customer_email`, `rating`, `title`, `comment`, `is_verified`, `status`, `created_at`) VALUES
(4, 14, 'Zeeshan A.', 'zeeshan.a.@example.com', 5, NULL, 'Excellent quality, exactly as described. Will order again.', 1, 'approved', '2026-09-21 19:24:57'),
(5, 14, 'Fatima N.', 'fatima.n.@example.com', 4, NULL, 'Good product, delivery took a bit longer than expected but worth the wait.', 1, 'approved', '2026-09-21 19:24:57'),
(6, 14, 'Usman T.', 'usman.t.@example.com', 5, NULL, 'Best sports gear I have bought online. Highly recommend BuiltCo Sports.', 1, 'approved', '2026-09-21 19:24:57'),
(7, 17, 'Zeeshan A.', 'zeeshan.a.@example.com', 5, NULL, 'Excellent quality, exactly as described. Will order again.', 1, 'approved', '2026-09-21 19:24:57'),
(8, 17, 'Fatima N.', 'fatima.n.@example.com', 4, NULL, 'Good product, delivery took a bit longer than expected but worth the wait.', 1, 'approved', '2026-09-21 19:24:57'),
(9, 17, 'Usman T.', 'usman.t.@example.com', 5, NULL, 'Best sports gear I have bought online. Highly recommend BuiltCo Sports.', 1, 'approved', '2026-09-21 19:24:57'),
(10, 23, 'Zeeshan A.', 'zeeshan.a.@example.com', 5, NULL, 'Excellent quality, exactly as described. Will order again.', 1, 'approved', '2026-09-21 19:24:57'),
(11, 23, 'Fatima N.', 'fatima.n.@example.com', 4, NULL, 'Good product, delivery took a bit longer than expected but worth the wait.', 1, 'approved', '2026-09-21 19:24:57'),
(12, 23, 'Usman T.', 'usman.t.@example.com', 5, NULL, 'Best sports gear I have bought online. Highly recommend BuiltCo Sports.', 1, 'approved', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('abandoned_cart_reminder_enabled', '1'),
('abandoned_cart_reminder_hours', '3'),
('ai_api_key', ''),
('ai_model', 'gpt-4o-mini'),
('ai_provider', 'openai'),
('contact_address', 'Sialkot, Punjab, Pakistan'),
('contact_email', 'info@builtcosports.com'),
('contact_phone', '+92 300 1234567'),
('currency_code', 'USD'),
('currency_symbol', '$'),
('custom_footer_scripts', ''),
('custom_header_scripts', ''),
('email_admin_bulk_inquiry_enabled', '1'),
('email_admin_new_customization_enabled', '1'),
('email_admin_new_order_enabled', '1'),
('email_order_confirmation_enabled', '1'),
('email_order_status_update_enabled', '1'),
('fb_pixel_id', ''),
('footer_about_text', 'Premium sports equipment manufacturer and wholesale supplier based in Sialkot, Pakistan.'),
('footer_copyright_text', '© 2026 BuiltCo Sports. All rights reserved.'),
('ga_measurement_id', ''),
('gsc_property_url', ''),
('gsc_verification', ''),
('homepage_category_ids', ''),
('homepage_intro_content', 'For years, BuiltCo Sports has supplied athletes, clubs, and retailers with premium sporting goods crafted in Sialkot. From footballs to boxing gear, every product reflects our commitment to quality and durability.'),
('homepage_intro_title', 'Why Choose BuiltCo Sports'),
('homepage_meta_description', 'Shop premium footballs, boxing gear, gym equipment, cricket gear, uniforms and more. Wholesale and bulk pricing available. Based in Sialkot, Pakistan.'),
('homepage_meta_title', 'BuiltCo Sports | Premium Sports Equipment & Wholesale Supplier'),
('meta_title_suffix', ' | BuiltCo Sports'),
('og_image', ''),
('payfast_merchant_id', ''),
('payfast_merchant_key', ''),
('payfast_mode', 'sandbox'),
('payfast_passphrase', ''),
('robots_txt', ''),
('section_bestsellers_enabled', '1'),
('section_bestsellers_title', 'Best Sellers'),
('section_category_enabled', '1'),
('section_category_title', 'Shop by Category'),
('section_certifications_enabled', '1'),
('section_certifications_title', 'Certifications & Memberships'),
('section_comingsoon_enabled', '1'),
('section_comingsoon_title', 'Coming Soon'),
('section_featured_enabled', '1'),
('section_featured_title', 'Featured Products'),
('section_hero_enabled', '1'),
('section_newsletter_enabled', '1'),
('section_newsletter_subtitle', 'Get updates on new products and offers.'),
('section_newsletter_title', 'Join Our Newsletter'),
('section_new_arrivals_enabled', '1'),
('section_new_arrivals_title', 'New Arrivals'),
('section_promo_enabled', '1'),
('section_testimonials_enabled', '1'),
('section_testimonials_title', 'What Our Customers Say'),
('section_trustbar_enabled', '1'),
('section_why_enabled', '1'),
('section_why_title', 'Why Choose Us'),
('shipping_cost', '0'),
('site_favicon', ''),
('site_logo', ''),
('site_name', 'BuiltCo Sports'),
('site_tagline', 'Premium Sports Equipment, Made in Sialkot'),
('smtp_encryption', 'tls'),
('smtp_from_email', ''),
('smtp_from_name', ''),
('smtp_host', ''),
('smtp_password', ''),
('smtp_port', '587'),
('smtp_username', ''),
('social_facebook', ''),
('social_instagram', ''),
('social_tiktok', ''),
('social_twitter', ''),
('social_youtube', ''),
('theme_ink_color', '#0b0f14'),
('theme_primary_color', '#ff4d2e'),
('theme_primary_dark', '#e0391d'),
('whatsapp_number', '');

-- --------------------------------------------------------

--
-- Table structure for table `stock_notifications`
--

CREATE TABLE `stock_notifications` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variation_key` varchar(255) DEFAULT NULL COMMENT 'NULL = whole (simple) product, else matches product_variations.combination_key',
  `email` varchar(255) NOT NULL,
  `notified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `role` varchar(150) DEFAULT NULL COMMENT 'e.g. job title / company',
  `quote` text NOT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `photo` varchar(500) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `name`, `role`, `quote`, `rating`, `photo`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Ahmed Raza', 'Gym Owner, Lahore', 'BuiltCo Sports has been our go-to supplier for gym equipment. Quality is consistently excellent.', 5, NULL, 0, 'active', '2026-09-21 19:24:57'),
(2, 'Sara Khan', 'Football Academy Coach', 'Ordered team jerseys in bulk — fast turnaround and the kids love the quality.', 5, NULL, 1, 'active', '2026-09-21 19:24:57'),
(3, 'Bilal Hussain', 'Sporting Goods Retailer', 'Reliable wholesale partner. Their bulk pricing makes it easy to stock up.', 4, NULL, 2, 'active', '2026-09-21 19:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `status`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'User', 'admin@builtco.com', '$2y$10$XeehNcp9TkArvgcjzTmm.u3qF9aVA5n04biJExu5vrj8d2hBXISne', 'admin', 'active', '2026-09-24 22:13:50', '2026-09-21 05:56:28', '2026-09-24 17:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `variation_options`
--

CREATE TABLE `variation_options` (
  `id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `value` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `variation_options`
--

INSERT INTO `variation_options` (`id`, `type_id`, `value`, `sort_order`) VALUES
(3, 2, '10oz', 0),
(4, 2, '12oz', 1),
(5, 2, '14oz', 2),
(6, 3, 'S', 0),
(7, 3, 'M', 1),
(8, 3, 'L', 2),
(9, 3, 'XL', 3),
(25, 7, 'XXS', 0),
(26, 7, 'XS', 1),
(27, 7, 'S', 2),
(28, 7, 'M', 3),
(29, 7, 'L', 4),
(30, 7, '2XL', 5),
(31, 7, '3XL', 6),
(32, 7, '4XL', 7),
(33, 7, '5XL', 8),
(34, 7, '6XL', 9),
(35, 8, 'Red', 0),
(36, 9, 'Robe Only', 0),
(37, 9, 'Armor Only', 1),
(38, 9, 'Robe + Chest Plate', 2),
(39, 9, 'Full Set', 3);

-- --------------------------------------------------------

--
-- Table structure for table `variation_types`
--

CREATE TABLE `variation_types` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `variation_types`
--

INSERT INTO `variation_types` (`id`, `product_id`, `name`, `sort_order`) VALUES
(2, 17, 'Weight', 0),
(3, 26, 'Size', 0),
(7, 27, 'Size', 0),
(8, 27, 'Primary Color', 1),
(9, 27, 'OutFit Selection', 2);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `abandoned_carts`
--
ALTER TABLE `abandoned_carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_restore_token` (`restore_token`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `bulk_inquiries`
--
ALTER TABLE `bulk_inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_bulk_product` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `design_requests`
--
ALTER TABLE `design_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_designreq_product` (`product_id`);

--
-- Indexes for table `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `footer_columns`
--
ALTER TABLE `footer_columns`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `footer_links`
--
ALTER TABLE `footer_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_footer_link_column` (`column_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `fk_menu_category` (`category_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `fk_orderitem_product` (`product_id`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `page_visits`
--
ALTER TABLE `page_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visited_at` (`visited_at`),
  ADD KEY `idx_url` (`url`(191));

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`);
ALTER TABLE `products` ADD FULLTEXT KEY `ft_search` (`name`,`tags`,`short_description`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `product_spin_frames`
--
ALTER TABLE `product_spin_frames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `product_variations`
--
ALTER TABLE `product_variations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `stock_notifications`
--
ALTER TABLE `stock_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_pending` (`product_id`,`variation_key`,`notified_at`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `variation_options`
--
ALTER TABLE `variation_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_voption_type` (`type_id`);

--
-- Indexes for table `variation_types`
--
ALTER TABLE `variation_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_customer_product` (`customer_id`,`product_id`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `abandoned_carts`
--
ALTER TABLE `abandoned_carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `bulk_inquiries`
--
ALTER TABLE `bulk_inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `certifications`
--
ALTER TABLE `certifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `design_requests`
--
ALTER TABLE `design_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `features`
--
ALTER TABLE `features`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `footer_columns`
--
ALTER TABLE `footer_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `footer_links`
--
ALTER TABLE `footer_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `page_visits`
--
ALTER TABLE `page_visits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=208;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `product_spin_frames`
--
ALTER TABLE `product_spin_frames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `product_variations`
--
ALTER TABLE `product_variations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `stock_notifications`
--
ALTER TABLE `stock_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `variation_options`
--
ALTER TABLE `variation_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `variation_types`
--
ALTER TABLE `variation_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bulk_inquiries`
--
ALTER TABLE `bulk_inquiries`
  ADD CONSTRAINT `fk_bulk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `design_requests`
--
ALTER TABLE `design_requests`
  ADD CONSTRAINT `fk_designreq_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `footer_links`
--
ALTER TABLE `footer_links`
  ADD CONSTRAINT `fk_footer_link_column` FOREIGN KEY (`column_id`) REFERENCES `footer_columns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_menu_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_pimg_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_spin_frames`
--
ALTER TABLE `product_spin_frames`
  ADD CONSTRAINT `fk_spinframe_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_variations`
--
ALTER TABLE `product_variations`
  ADD CONSTRAINT `fk_pvar_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_review_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `variation_options`
--
ALTER TABLE `variation_options`
  ADD CONSTRAINT `fk_voption_type` FOREIGN KEY (`type_id`) REFERENCES `variation_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `variation_types`
--
ALTER TABLE `variation_types`
  ADD CONSTRAINT `fk_vtype_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ----------------------------------------------------------------
-- Included migration: step 29 (3D Design Studio + extended SEO)
-- ----------------------------------------------------------------
-- ================================================================
-- Step 29: 3D Design Studio + extended SEO settings
--   * products.customizer_model   — which 3D model the studio uses
--       auto   = detect from product/category name (jersey, ball, else card)
--       jersey = 3D shirt, ball = 3D ball, flat = 3D photo card
--   * products.customizer_base_color — starting kit colour in the studio
--   * products.brand / gtin / mpn — richer Product structured data
--   * site_settings for SEO / merchant listings / 3D toggles
-- Safe to run more than once on MariaDB 10.4+ (IF NOT EXISTS).
-- ================================================================

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS customizer_model ENUM('auto','jersey','ball','flat') NOT NULL DEFAULT 'auto' AFTER is_customizable,
    ADD COLUMN IF NOT EXISTS customizer_base_color VARCHAR(7) DEFAULT NULL AFTER customizer_model,
    ADD COLUMN IF NOT EXISTS brand VARCHAR(120) DEFAULT NULL AFTER sku,
    ADD COLUMN IF NOT EXISTS gtin VARCHAR(20) DEFAULT NULL AFTER brand,
    ADD COLUMN IF NOT EXISTS mpn VARCHAR(64) DEFAULT NULL AFTER gtin;

ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS faq TEXT DEFAULT NULL;

INSERT INTO site_settings (setting_key, setting_value) VALUES
    ('seo_default_brand', ''),
    ('seo_twitter_handle', ''),
    ('seo_business_type', 'Organization'),
    ('seo_founding_year', ''),
    ('seo_price_valid_days', '365'),
    ('seo_return_days', '14'),
    ('seo_return_fees', 'FreeReturn'),
    ('seo_shipping_country', 'PK'),
    ('seo_handling_days_max', '3'),
    ('seo_transit_days_max', '7'),
    ('seo_noindex_site', '0'),
    ('seo_bing_verification', ''),
    ('seo_pinterest_verification', ''),
    ('seo_yandex_verification', ''),
    ('announcement_text', 'Looking to buy in bulk? Contact us for wholesale & custom pricing'),
    ('studio_enabled_3d', '1'),
    ('hero_3d_enabled', '1'),
    ('hero_3d_model', 'jersey')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
