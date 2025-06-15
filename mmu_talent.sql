-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2025 at 01:06 PM
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
-- Database: `mmu_talent`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `action_description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
(9, 7, 'login', 'User logged in', '::1', '2025-06-15 09:49:44'),
(10, 7, 'talent_add', 'Added new talent: Game Development', NULL, '2025-06-15 09:52:56'),
(11, 7, 'talent_add', 'Added new talent: Professional Listener', NULL, '2025-06-15 09:53:42'),
(12, 7, 'portfolio_upload', 'Uploaded portfolio item: Kivotos Labyrinth', NULL, '2025-06-15 10:05:25'),
(13, 7, 'portfolio_upload', 'Uploaded portfolio item: Kivotos Labyrinth', NULL, '2025-06-15 10:05:26'),
(14, 7, 'portfolio_delete', 'Deleted portfolio item', NULL, '2025-06-15 10:05:41'),
(15, 7, 'profile_picture_update', 'Updated profile picture', '::1', '2025-06-15 10:06:15'),
(16, 7, 'portfolio_upload', 'Uploaded portfolio item: How to build Cartethyia', NULL, '2025-06-15 10:10:05'),
(17, 7, 'portfolio_upload', 'Uploaded portfolio item: A Study on Furina', NULL, '2025-06-15 10:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `announcement_type` enum('general','event','workshop','competition') DEFAULT 'general',
  `is_published` tinyint(1) DEFAULT 0,
  `publish_date` timestamp NULL DEFAULT current_timestamp(),
  `expiry_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `media_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `media_url` varchar(255) NOT NULL,
  `media_type` enum('image','video','document') NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_items`
--

CREATE TABLE `portfolio_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `talent_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `file_type` enum('image','video','audio','document','code','other') NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `upload_date` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `portfolio_items`
--

INSERT INTO `portfolio_items` (`id`, `user_id`, `talent_id`, `title`, `description`, `file_url`, `thumbnail_url`, `file_type`, `file_size`, `views`, `is_featured`, `upload_date`, `updated_at`) VALUES
(1, 6, 7, 'Terran Republic', '', 'uploads/portfolio/684a7cf488fa9_1749712116.docx', NULL, 'document', 8433, 0, 1, '2025-06-12 07:08:36', '2025-06-12 07:08:36'),
(3, 7, 8, 'Kivotos Labyrinth', 'A 3D Maze Escape fan game with Glitch-themed obstacles inspired by Blue Archive\'s in game locations and its musics. Complete with auto save and fastest time.', 'uploads/portfolio/684e9ae6a5dc2_1749981926.jpg', 'uploads/thumbnails/thumb_684e9ae6a5dc2_1749981926.jpg', 'image', 8746, 0, 1, '2025-06-15 10:05:26', '2025-06-15 10:05:26'),
(4, 7, 9, 'How to build Cartethyia', 'A in-depth guide on how to build the newly released 5-star resonator, Cartethyia from the hit game Wuthering Waves Version 2.4!', 'uploads/portfolio/684e9bfdc294f_1749982205.mp4', NULL, 'video', 7333617, 0, 1, '2025-06-15 10:10:05', '2025-06-15 10:10:05'),
(5, 7, 8, 'A Study on Furina', 'A journal study on Furina\'s character design, lore and gameplay which shaped the popularity of this character within the Genshin Impact community.', 'uploads/portfolio/684e9c7835b95_1749982328.pdf', NULL, 'document', 6479715, 0, 0, '2025-06-15 10:12:08', '2025-06-15 10:12:08');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `talent_id` int(11) DEFAULT NULL,
  `service_title` varchar(200) NOT NULL,
  `service_description` text DEFAULT NULL,
  `price_range` varchar(100) DEFAULT NULL,
  `delivery_time` varchar(100) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `talent_categories`
--

CREATE TABLE `talent_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `student_id` varchar(10) NOT NULL,
  `section` enum('TC1L','TC2L') NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `short_bio` text DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT 'uploads/avatars/default-avatar.jpg',
  `user_type` enum('student','admin') DEFAULT 'student',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `student_id`, `section`, `phone_number`, `contact_email`, `short_bio`, `profile_picture_url`, `user_type`, `status`, `created_at`, `last_login`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@mmu.edu.my', 'System Administrator', '0000000000', 'TC1L', NULL, 'admin@mmu.edu.my', 'System Administrator Account', 'uploads/avatars/default-avatar.jpg', 'admin', 'active', '2025-06-11 14:43:23', NULL),
(2, 'john_doe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john@student.mmu.edu.my', 'John Doe', '1191234567', 'TC1L', '0123456789', 'john@student.mmu.edu.my', 'Multimedia student passionate about digital storytelling.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_Mutsuki_newyear.png', 'student', 'active', '2025-06-11 14:43:23', NULL),
(3, 'jane_smith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane@student.mmu.edu.my', 'Jane Smith', '1191234568', 'TC2L', '0192233445', 'jane@student.mmu.edu.my', 'Aspiring actor with campus theatre experience.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_CH0184.png', 'student', 'active', '2025-06-11 14:43:23', NULL),
(4, 'alice_tan', 'password', 'alice@student.mmu.edu.my', 'Alice Tan', '1191234569', 'TC1L', '0168899221', 'alice@student.mmu.edu.my', 'Final year student exploring portrait photography.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_CH0225.png', 'student', 'active', '2025-06-11 14:43:23', NULL),
(5, 'daniel_chong', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'daniel@student.mmu.edu.my', 'Daniel Chong', '1191234570', 'TC2L', '0116677889', 'daniel@student.mmu.edu.my', 'Fresh graduate in music production and sound design.', 'uploads/daniel.jpg', 'student', 'active', '2025-06-11 14:43:23', NULL),
(6, 'SonjaKrasny', '$2y$10$0WB5.CxcjuWnWDyx6SiDbuKRERoFyn/4N4Jyyqr8fydaSj23EQsXu', 'chewxieyang@student.mmu.edu.my', 'Chew Xie Yang', '1221304859', 'TC1L', '012-7116513', 'chewxieyang@student.mmu.edu.my', 'Just another prospective writer', 'uploads/avatars/68499b5dbe1b9_1749654365.jpeg', 'student', 'active', '2025-06-11 14:44:00', '2025-06-11 14:45:07'),
(7, 'wmatif', '$2y$10$N5EfK0gTKR4vFZIutdCIheGyKqJt5dIMheITZCt9VrXC2hNWTbV46', 'wmatif@student.mmu.edu.my', 'Wan Muhammad Atif', '1211103154', 'TC1L', '011-10255127', 'wmatif@student.mmu.edu.my', NULL, 'uploads/avatars/684e9b1781205_1749981975.png', 'student', 'active', '2025-06-15 09:41:33', '2025-06-15 09:49:44');

-- --------------------------------------------------------

--
-- Table structure for table `user_talents`
--

CREATE TABLE `user_talents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `talent_title` varchar(150) NOT NULL,
  `talent_description` text DEFAULT NULL,
  `skill_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
  `years_experience` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(7, 6, 8, 'Fiction Writer', 'Writer of a sci-fi series, \"Triumvirate Space\"', 'intermediate', 5, 0, '2025-06-11 15:03:44', '2025-06-11 15:03:44'),
(8, 7, 3, 'Game Development', 'Experienced Unity and Unreal Engine Developer specialising in Game Design and Systems Development', 'intermediate', 2, 1, '2025-06-15 09:52:56', '2025-06-15 09:52:56'),
(9, 7, 1, 'Professional Listener', 'Experienced with listening to music since 5 years old.', 'expert', 22, 0, '2025-06-15 09:53:42', '2025-06-15 09:53:42');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portfolio_items`
--
ALTER TABLE `portfolio_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `talent_categories`
--
ALTER TABLE `talent_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_talents`
--
ALTER TABLE `user_talents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
