-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 17, 2025 at 04:32 PM
-- Server version: 8.0.42
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mmu_talent`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `action_description` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action_type`, `action_description`, `ip_address`, `created_at`) VALUES
(1, 6, 'login', 'User logged in', '127.0.0.1', '2025-06-11 14:45:07'),
(2, 6, 'talent_add', 'Added new talent: Fiction Writer', '127.0.0.1', '2025-06-11 15:03:44'),
(3, 6, 'profile_picture_update', 'Updated profile picture', '127.0.0.1', '2025-06-11 15:05:40'),
(4, 6, 'profile_picture_update', 'Updated profile picture', '127.0.0.1', '2025-06-11 15:05:48'),
(5, 6, 'profile_picture_update', 'Updated profile picture', '127.0.0.1', '2025-06-11 15:06:00'),
(6, 6, 'profile_picture_update', 'Updated profile picture', '127.0.0.1', '2025-06-11 15:06:05'),
(7, 6, 'profile_update', 'Updated profile information', '127.0.0.1', '2025-06-12 03:16:53'),
(8, 6, 'portfolio_upload', 'Uploaded portfolio item: Terran Republic', NULL, '2025-06-12 07:08:36'),
(9, 6, 'login', 'User logged in', '127.0.0.1', '2025-06-13 13:01:06'),
(10, 6, 'logout', 'User logged out', '127.0.0.1', '2025-06-13 13:12:37'),
(11, 6, 'login', 'User logged in', '127.0.0.1', '2025-06-16 05:24:31'),
(24, 6, 'login', 'User logged in', '127.0.0.1', '2025-06-17 13:29:52'),
(25, 6, 'order_placed', 'Placed order with total: RM 31.50', NULL, '2025-06-17 13:30:26'),
(26, 6, 'service_create', 'Created new service: Scriptwriting', NULL, '2025-06-17 13:31:48'),
(27, 6, 'forum_topic_create', 'Created new topic: New Events?', '127.0.0.1', '2025-06-17 14:06:04'),
(28, 6, 'forum_topic_create', 'Created new topic: New Events?', '127.0.0.1', '2025-06-17 14:06:27');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `announcement_type` enum('general','event','workshop','competition') COLLATE utf8mb4_general_ci DEFAULT 'general',
  `is_published` tinyint(1) DEFAULT '0',
  `publish_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expiry_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `image_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `image_id`, `user_id`, `content`, `created_at`) VALUES
(1, 1, 8, 'Hello', '2025-06-17 06:35:52'),
(2, 1, 8, 'test', '2025-06-17 06:40:40');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `question` text COLLATE utf8mb4_general_ci NOT NULL,
  `answer` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`id`, `category`, `question`, `answer`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'General', 'How do I create an account?', 'Click on the Register link and fill out the form with your MMU student email address.', 1, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(2, 'Portfolio', 'What file types can I upload?', 'You can upload images (JPG, PNG, GIF), videos (MP4, AVI), audio files (MP3, WAV), and documents (PDF, DOC).', 1, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(3, 'Profile', 'How do I update my profile information?', 'Go to your profile page and click the Edit Profile button to update your information.', 1, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(4, 'Talents', 'How do I add my talents?', 'Go to your profile and click on \"Manage Talents\" to add and edit your skills.', 1, '2025-06-11 14:43:23', '2025-06-11 14:43:23');

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories`
--

CREATE TABLE `forum_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_categories`
--

INSERT INTO `forum_categories` (`id`, `category_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'General Discussion', 'General conversations about talents and creative work', 1, '2025-06-16 02:26:46'),
(2, 'Collaboration', 'Find partners for creative projects', 1, '2025-06-16 02:26:46'),
(3, 'Technical Help', 'Get help with technical issues and tutorials', 1, '2025-06-16 02:26:46'),
(4, 'Showcase', 'Share your latest work and get feedback', 1, '2025-06-16 02:26:46'),
(5, 'Events & Workshops', 'Discuss upcoming events and workshops', 1, '2025-06-16 02:26:46'),
(6, 'Career & Opportunities', 'Job postings and career advice', 1, '2025-06-16 02:26:46');

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `id` int NOT NULL,
  `topic_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `parent_reply_id` int DEFAULT NULL,
  `is_solution` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE `forum_topics` (
  `id` int NOT NULL,
  `category_id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_pinned` tinyint(1) DEFAULT '0',
  `is_locked` tinyint(1) DEFAULT '0',
  `views` int DEFAULT '0',
  `reply_count` int DEFAULT '0',
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `last_reply_user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_topics`
--

INSERT INTO `forum_topics` (`id`, `category_id`, `user_id`, `title`, `content`, `is_pinned`, `is_locked`, `views`, `reply_count`, `last_reply_at`, `last_reply_user_id`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'New Events?', 'So, what new events are going to happen in the near future?', 0, 0, 0, 0, '2025-06-17 14:06:04', 6, '2025-06-17 14:06:04', '2025-06-17 14:06:04'),
(2, 1, 6, 'New Events?', 'So what events are going to happen in the future?', 0, 0, 0, 0, '2025-06-17 14:06:27', 6, '2025-06-17 14:06:27', '2025-06-17 14:06:27');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `media_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `media_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `media_type` enum('image','video','document') COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_board_comments`
--

CREATE TABLE `message_board_comments` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `user_id` int NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `parent_comment_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_board_posts`
--

CREATE TABLE `message_board_posts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `post_type` enum('general','collaboration','opportunity','event') COLLATE utf8mb4_general_ci DEFAULT 'general',
  `is_featured` tinyint(1) DEFAULT '0',
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_board_posts`
--

INSERT INTO `message_board_posts` (`id`, `user_id`, `title`, `content`, `post_type`, `is_featured`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 6, 'Hello world', 'Good morning everyone!', 'general', 0, NULL, '2025-06-17 13:35:26', '2025-06-17 13:35:26');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `buyer_id` int NOT NULL,
  `seller_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','refunded') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `order_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completion_date` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `buyer_id`, `seller_id`, `total_amount`, `status`, `payment_status`, `order_date`, `completion_date`, `notes`) VALUES
(1, 6, 3, 30.00, 'pending', 'pending', '2025-06-17 13:30:26', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `service_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `custom_requirements` text COLLATE utf8mb4_general_ci,
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `service_id`, `quantity`, `unit_price`, `custom_requirements`, `status`) VALUES
(1, 1, 2, 1, 30.00, '0', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_items`
--

CREATE TABLE `portfolio_items` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `talent_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `file_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` enum('image','video','audio','document','code','other') COLLATE utf8mb4_general_ci NOT NULL,
  `file_size` int DEFAULT NULL,
  `views` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portfolio_items`
--

INSERT INTO `portfolio_items` (`id`, `user_id`, `talent_id`, `title`, `description`, `file_url`, `thumbnail_url`, `file_type`, `file_size`, `views`, `is_featured`, `upload_date`, `updated_at`) VALUES
(1, 6, 7, 'Terran Republic', '', 'uploads/portfolio/684a7cf488fa9_1749712116.docx', NULL, 'document', 8433, 0, 1, '2025-06-12 07:08:36', '2025-06-12 07:08:36');

-- --------------------------------------------------------

--
-- Table structure for table `private_messages`
--

CREATE TABLE `private_messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `subject` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `is_deleted_by_sender` tinyint(1) DEFAULT '0',
  `is_deleted_by_recipient` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `private_messages`
--

INSERT INTO `private_messages` (`id`, `sender_id`, `recipient_id`, `subject`, `content`, `is_read`, `is_deleted_by_sender`, `is_deleted_by_recipient`, `created_at`, `read_at`) VALUES
(1, 6, 4, 'Hello there', 'Hello there', 0, 0, 0, '2025-06-17 13:42:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `reviewer_id` int NOT NULL,
  `reviewee_id` int NOT NULL,
  `rating` int DEFAULT NULL,
  `review_text` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `talent_id` int DEFAULT NULL,
  `service_title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `service_description` text COLLATE utf8mb4_general_ci,
  `price_range` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `delivery_time` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `price` decimal(10,2) DEFAULT NULL,
  `service_type` enum('commission','product','gig') COLLATE utf8mb4_general_ci DEFAULT 'commission',
  `duration_days` int DEFAULT NULL,
  `images` text COLLATE utf8mb4_general_ci,
  `terms_conditions` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `user_id`, `talent_id`, `service_title`, `service_description`, `price_range`, `delivery_time`, `is_available`, `created_at`, `updated_at`, `price`, `service_type`, `duration_days`, `images`, `terms_conditions`) VALUES
(1, 2, 1, 'Custom Portrait Illustration', 'I will create a custom digital portrait based on your photo', NULL, '5-7 days', 1, '2025-06-16 02:25:57', '2025-06-16 02:25:57', 50.00, 'commission', NULL, NULL, NULL),
(2, 3, 3, 'Acting Workshop', 'Personal acting coaching session', NULL, '2 hours', 1, '2025-06-16 02:25:57', '2025-06-16 02:25:57', 30.00, 'gig', NULL, NULL, NULL),
(3, 4, 4, 'Professional Headshots', 'High-quality headshot photography session', NULL, '1 day', 1, '2025-06-16 02:25:57', '2025-06-16 02:25:57', 80.00, 'gig', NULL, NULL, NULL),
(4, 5, 6, 'Custom Music Production', 'I will produce a custom track for your project', NULL, '2-3 weeks', 1, '2025-06-16 02:25:57', '2025-06-16 02:25:57', 120.00, 'commission', NULL, NULL, NULL),
(5, 6, 7, 'Scriptwriting', 'Need a script for your projects? It will be done', NULL, '2 weeks', 1, '2025-06-17 13:31:48', '2025-06-17 13:31:48', 10.00, 'commission', NULL, NULL, 'Please provide details on your project before discussion.');

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `service_id` int NOT NULL,
  `quantity` int DEFAULT '1',
  `custom_requirements` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `talent_categories`
--

CREATE TABLE `talent_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `talent_categories`
--

INSERT INTO `talent_categories` (`id`, `category_name`, `description`, `created_at`) VALUES
(1, 'Music', 'Musical talents including instruments, vocals, composition', '2025-06-11 14:43:23'),
(2, 'Acting', 'Theatre, film, and performance arts', '2025-06-11 14:43:23'),
(3, 'Visual Arts', 'Painting, drawing, digital art, illustration', '2025-06-11 14:43:23'),
(4, 'Photography', 'Portrait, landscape, event photography', '2025-06-11 14:43:23'),
(5, 'Dance', 'Various dance styles and choreography', '2025-06-11 14:43:23'),
(6, 'Modeling', 'Fashion, commercial, artistic modeling', '2025-06-11 14:43:23'),
(7, 'Singing', 'Vocal performance and recording', '2025-06-11 14:43:23'),
(8, 'Writing', 'Creative writing, journalism, content creation', '2025-06-11 14:43:23'),
(9, 'UI/UX Design', 'User interface and experience design', '2025-06-11 14:43:23'),
(10, 'Web Development', 'Frontend and backend web development', '2025-06-11 14:43:23'),
(11, 'Video Production', 'Video editing, cinematography, animation', '2025-06-11 14:43:23');

-- --------------------------------------------------------

--
-- Table structure for table `talent_uploads`
--

CREATE TABLE `talent_uploads` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `talent_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `file_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_type` enum('image','video','audio','document','code','other') COLLATE utf8mb4_general_ci NOT NULL,
  `file_size` int DEFAULT NULL,
  `views` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `upload_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `talent_uploads`
--

INSERT INTO `talent_uploads` (`id`, `user_id`, `talent_id`, `title`, `description`, `file_url`, `thumbnail_url`, `file_type`, `file_size`, `views`, `is_featured`, `upload_date`, `updated_at`) VALUES
(1, 8, 10, 'fumo', 'sparkle fumo', 'uploads/talentupload/684f1cabd980f_1750015147.png', NULL, 'image', 1076579, 0, NULL, '2025-06-15 19:19:07', '2025-06-15 19:19:07'),
(2, 8, 10, 'fumo', 'sparkle fumo', 'uploads/talentupload/684f1d66de226_1750015334.png', NULL, 'image', 1076579, 0, NULL, '2025-06-15 19:22:14', '2025-06-15 19:22:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `section` enum('TC1L','TC2L') COLLATE utf8mb4_general_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `short_bio` text COLLATE utf8mb4_general_ci,
  `profile_picture_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'uploads/avatars/default-avatar.jpg',
  `user_type` enum('student','admin') COLLATE utf8mb4_general_ci DEFAULT 'student',
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `student_id`, `section`, `phone_number`, `contact_email`, `short_bio`, `profile_picture_url`, `user_type`, `status`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@mmu.edu.my', 'System Administrator', '0000000000', 'TC1L', NULL, 'admin@mmu.edu.my', 'System Administrator Account', 'uploads/avatars/default-avatar.jpg', 'admin', 'active', '2025-06-11 14:43:23', NULL),
(2, 'john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john@student.mmu.edu.my', 'John Doe', '1191234567', 'TC1L', '0123456789', 'john@student.mmu.edu.my', 'Multimedia student passionate about digital storytelling.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_Mutsuki_newyear.png', 'student', 'active', '2025-06-11 14:43:23', NULL),
(3, 'jane_smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane@student.mmu.edu.my', 'Jane Smith', '1191234568', 'TC2L', '0192233445', 'jane@student.mmu.edu.my', 'Aspiring actor with campus theatre experience.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_CH0184.png', 'student', 'active', '2025-06-11 14:43:23', NULL),
(4, 'alice_tan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alice@student.mmu.edu.my', 'Alice Tan', '1191234569', 'TC1L', '0168899221', 'alice@student.mmu.edu.my', 'Final year student exploring portrait photography.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_CH0225.png', 'student', 'active', '2025-06-11 14:43:23', NULL),
(5, 'daniel_chong', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'daniel@student.mmu.edu.my', 'Daniel Chong', '1191234570', 'TC2L', '0116677889', 'daniel@student.mmu.edu.my', 'Fresh graduate in music production and sound design.', 'uploads/daniel.jpg', 'student', 'active', '2025-06-11 14:43:23', NULL),
(6, 'SonjaKrasny', '$2y$10$0WB5.CxcjuWnWDyx6SiDbuKRERoFyn/4N4Jyyqr8fydaSj23EQsXu', 'chewxieyang@student.mmu.edu.my', 'Chew Xie Yang', '1221304859', 'TC1L', '012-7116513', 'chewxieyang@student.mmu.edu.my', 'Just another prospective writer', 'uploads/avatars/68499b5dbe1b9_1749654365.jpeg', 'student', 'active', '2025-06-11 14:44:00', '2025-06-17 13:29:52');

-- --------------------------------------------------------

--
-- Table structure for table `user_talents`
--

CREATE TABLE `user_talents` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `category_id` int NOT NULL,
  `talent_title` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `talent_description` text COLLATE utf8mb4_general_ci,
  `skill_level` enum('beginner','intermediate','advanced','expert') COLLATE utf8mb4_general_ci DEFAULT 'beginner',
  `years_experience` int DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_talents`
--

INSERT INTO `user_talents` (`id`, `user_id`, `category_id`, `talent_title`, `talent_description`, `skill_level`, `years_experience`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 2, 7, 'Vocalist', 'Specializing in pop and acoustic covers', 'intermediate', 3, 0, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(2, 2, 8, 'Content Writing', 'Blog writing and social media content creation', 'advanced', 2, 0, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(3, 3, 2, 'Stage Acting', 'Theatre performance and character development', 'intermediate', 4, 0, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(4, 4, 4, 'Portrait Photography', 'Professional headshots and artistic portraits', 'advanced', 5, 0, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(5, 4, 6, 'Fashion Modeling', 'Runway and editorial modeling experience', 'intermediate', 2, 0, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(6, 5, 1, 'Music Production', 'Electronic music composition and sound design', 'expert', 6, 0, '2025-06-11 14:43:23', '2025-06-11 14:43:23'),
(7, 6, 8, 'Fiction Writer', 'Writer of a sci-fi series, \"Triumvirate Space\"', 'intermediate', 5, 0, '2025-06-11 15:03:44', '2025-06-11 15:03:44');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `image_id` (`image_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_reply_id` (`parent_reply_id`),
  ADD KEY `idx_forum_replies_topic` (`topic_id`),
  ADD KEY `idx_forum_replies_user` (`user_id`);

--
-- Indexes for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `last_reply_user_id` (`last_reply_user_id`),
  ADD KEY `idx_forum_topics_category` (`category_id`),
  ADD KEY `idx_forum_topics_user` (`user_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `message_board_comments`
--
ALTER TABLE `message_board_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_comment_id` (`parent_comment_id`),
  ADD KEY `idx_message_board_comments_post` (`post_id`);

--
-- Indexes for table `message_board_posts`
--
ALTER TABLE `message_board_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message_board_posts_user` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `talent_id` (`talent_id`);

--
-- Indexes for table `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_private_messages_sender` (`sender_id`),
  ADD KEY `idx_private_messages_recipient` (`recipient_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `reviewee_id` (`reviewee_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `talent_id` (`talent_id`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_service` (`user_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `talent_categories`
--
ALTER TABLE `talent_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `talent_uploads`
--
ALTER TABLE `talent_uploads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `user_talents`
--
ALTER TABLE `user_talents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `forum_categories`
--
ALTER TABLE `forum_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `media_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_board_comments`
--
ALTER TABLE `message_board_comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message_board_posts`
--
ALTER TABLE `message_board_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `talent_categories`
--
ALTER TABLE `talent_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `talent_uploads`
--
ALTER TABLE `talent_uploads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_talents`
--
ALTER TABLE `user_talents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_replies_ibfk_3` FOREIGN KEY (`parent_reply_id`) REFERENCES `forum_replies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD CONSTRAINT `forum_topics_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_topics_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_topics_ibfk_3` FOREIGN KEY (`last_reply_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_board_comments`
--
ALTER TABLE `message_board_comments`
  ADD CONSTRAINT `message_board_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `message_board_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_board_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `message_board_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `message_board_comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `message_board_posts`
--
ALTER TABLE `message_board_posts`
  ADD CONSTRAINT `message_board_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD CONSTRAINT `portfolio_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `portfolio_items_ibfk_2` FOREIGN KEY (`talent_id`) REFERENCES `user_talents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `private_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_messages_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reviewee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `services_ibfk_2` FOREIGN KEY (`talent_id`) REFERENCES `user_talents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_talents`
--
ALTER TABLE `user_talents`
  ADD CONSTRAINT `user_talents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_talents_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `talent_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
