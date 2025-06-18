-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2025 at 01:30 PM
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
-- Table structure for table `question_submissions`
--

CREATE TABLE `question_submissions` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `question_title` varchar(255) NOT NULL,
  `question_message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_submissions`
--

INSERT INTO `question_submissions` (`id`, `email`, `question_title`, `question_message`, `submitted_at`) VALUES
(1, 'wmatifts@gmail.com', 'how do i use this website', 'how to tesdt this website', '2025-06-18 11:23:56'),
(2, 'wmatifts@gmail.com', 'how do i use this website', 'how to tesdt this website', '2025-06-18 11:24:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `question_submissions`
--
ALTER TABLE `question_submissions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `question_submissions`
--
ALTER TABLE `question_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
