-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 13, 2025 at 09:23 AM
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
(8, 6, 'portfolio_upload', 'Uploaded portfolio item: Terran Republic', NULL, '2025-06-12 07:08:36');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
(6, 'SonjaKrasny', '$2y$10$0WB5.CxcjuWnWDyx6SiDbuKRERoFyn/4N4Jyyqr8fydaSj23EQsXu', 'chewxieyang@student.mmu.edu.my', 'Chew Xie Yang', '1221304859', 'TC1L', '012-7116513', 'chewxieyang@student.mmu.edu.my', 'Just another prospective writer', 'uploads/avatars/68499b5dbe1b9_1749654365.jpeg', 'student', 'active', '2025-06-11 14:44:00', '2025-06-11 14:45:07');

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
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `talent_id` (`talent_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `talent_id` (`talent_id`);

--
-- Indexes for table `talent_categories`
--
ALTER TABLE `talent_categories`
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `media_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `talent_categories`
--
ALTER TABLE `talent_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_talents`
--
ALTER TABLE `user_talents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  ADD CONSTRAINT `portfolio_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `portfolio_items_ibfk_2` FOREIGN KEY (`talent_id`) REFERENCES `user_talents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `services_ibfk_2` FOREIGN KEY (`talent_id`) REFERENCES `user_talents` (`id`) ON DELETE SET NULL;

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
