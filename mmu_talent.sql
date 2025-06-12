-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2025 at 12:30 PM
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
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, 'Musician'),
(2, 'Actor'),
(3, 'Painter'),
(4, 'Photographer'),
(5, 'Dancer'),
(6, 'Model'),
(7, 'Singer'),
(8, 'Writer'),
(9, 'UI/UX Designer');

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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `short_bio` varchar(255) DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `short_bio`, `profile_picture_url`, `contact_email`, `phone_number`) VALUES
(1, 'Alicia Tan', 'Multimedia student passionate about digital storytelling.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_Mutsuki_newyear.png', 'alicia.tan@example.com', '0123456789'),
(2, 'Rahman Zulkifli', 'Aspiring actor with campus theatre experience.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_CH0184.png\r\n', 'rahmanz@example.com', '0192233445'),
(3, 'Lee Mei Ling', 'Final year student exploring portrait photography.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_CH0225.png', 'mei.ling@example.com', '0168899221'),
(4, 'Daniel Chong', 'Fresh graduate in music production and sound design.', 'uploads/daniel.jpg', 'daniel.chong@example.com', '0116677889'),
(5, 'Nisha Kumar', 'UI/UX designer with a passion for aesthetic interfaces.', 'https://blue-utils.me/img/common/profile/Skill_Portrait_CH0204.png', 'nisha.kumar@example.com', '0184455667');

-- --------------------------------------------------------

--
-- Table structure for table `user_categories`
--

CREATE TABLE `user_categories` (
  `user_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_categories`
--

INSERT INTO `user_categories` (`user_id`, `category_id`) VALUES
(1, 7),
(1, 8),
(2, 2),
(3, 3),
(3, 6),
(4, 1),
(5, 7);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_categories`
--
ALTER TABLE `user_categories`
  ADD PRIMARY KEY (`user_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_categories`
--
ALTER TABLE `user_categories`
  ADD CONSTRAINT `user_categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
