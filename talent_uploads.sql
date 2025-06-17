-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2025 at 03:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `talent_uploads`
--

CREATE TABLE `talent_uploads` (
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
-- Dumping data for table `talent_uploads`
--

INSERT INTO `talent_uploads` (`id`, `user_id`, `talent_id`, `title`, `description`, `file_url`, `thumbnail_url`, `file_type`, `file_size`, `views`, `is_featured`, `upload_date`, `updated_at`) VALUES
(1, 8, 10, 'fumo', 'sparkle fumo', 'uploads/talentupload/684f1cabd980f_1750015147.png', NULL, 'image', 1076579, 0, NULL, '2025-06-15 19:19:07', '2025-06-15 19:19:07'),
(2, 8, 10, 'fumo', 'sparkle fumo', 'uploads/talentupload/684f1d66de226_1750015334.png', NULL, 'image', 1076579, 0, NULL, '2025-06-15 19:22:14', '2025-06-15 19:22:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `talent_uploads`
--
ALTER TABLE `talent_uploads`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `talent_uploads`
--
ALTER TABLE `talent_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
