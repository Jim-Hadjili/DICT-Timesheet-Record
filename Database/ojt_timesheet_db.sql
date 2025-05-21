-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 02:02 PM
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
-- Database: `ojt_timesheet_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `interns`
--

CREATE TABLE `interns` (
  `Intern_id` int(11) NOT NULL,
  `Intern_Name` varchar(255) NOT NULL,
  `Intern_School` varchar(255) NOT NULL,
  `Intern_BirthDay` date NOT NULL,
  `Intern_Age` int(255) NOT NULL,
  `Intern_Gender` varchar(255) NOT NULL,
  `Required_Hours_Rendered` int(255) NOT NULL,
  `Face_Registered` tinyint(1) DEFAULT 0,
  `Face_Image_Path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `interns`
--

INSERT INTO `interns` (`Intern_id`, `Intern_Name`, `Intern_School`, `Intern_BirthDay`, `Intern_Age`, `Intern_Gender`, `Required_Hours_Rendered`, `Face_Registered`, `Face_Image_Path`) VALUES
(6, 'Jim Hadjili', 'SCC', '2002-03-31', 23, 'Male', 240, 1, 'face_images/face_6_1747706724.png'),
(7, 'edrftgyhujikolp', 'SCC', '2002-06-06', 22, 'Male', 240, 0, ''),
(8, 'dfghyjuk', 'dfghjk', '2002-03-31', 23, 'Male', 240, 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `intern_notes`
--

CREATE TABLE `intern_notes` (
  `id` int(11) NOT NULL,
  `intern_id` varchar(50) NOT NULL,
  `note_date` date NOT NULL,
  `note_content` text NOT NULL,
  `noted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `intern_notes`
--

INSERT INTO `intern_notes` (`id`, `intern_id`, `note_date`, `note_content`, `noted`, `created_at`, `updated_at`) VALUES
(1, '6', '2025-06-27', 'asdfg', 0, '2025-05-20 15:42:40', '2025-05-20 15:42:40'),
(2, '6', '2025-05-21', 'xfghjkl;\'', 0, '2025-05-20 15:43:05', '2025-05-20 15:43:05'),
(3, '6', '2025-06-25', 'scdfvghjkl;', 0, '2025-05-20 15:58:23', '2025-05-20 15:58:23'),
(4, '6', '2025-05-23', 'wasredtfyguhijoikp', 0, '2025-05-20 15:59:31', '2025-05-20 15:59:31'),
(5, '7', '2025-05-20', 'ess', 0, '2025-05-20 16:03:04', '2025-05-20 17:29:22'),
(6, '', '0000-00-00', '', 0, '2025-05-20 16:24:26', '2025-05-20 16:24:36');

-- --------------------------------------------------------

--
-- Table structure for table `timesheet`
--

CREATE TABLE `timesheet` (
  `record_id` int(11) NOT NULL,
  `intern_id` int(255) NOT NULL,
  `intern_name` varchar(255) NOT NULL,
  `am_timein` time(6) NOT NULL,
  `am_timeOut` time(6) NOT NULL,
  `pm_timein` time(6) NOT NULL,
  `pm_timeout` time(6) NOT NULL,
  `am_hours_worked` time(6) NOT NULL,
  `pm_hours_worked` time(6) NOT NULL,
  `required_hours_rendered` int(255) NOT NULL,
  `day_total_hours` time(6) NOT NULL,
  `total_hours_rendered` time(6) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `overtime_start` time DEFAULT '00:00:00',
  `overtime_hours` time DEFAULT '00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timesheet`
--

INSERT INTO `timesheet` (`record_id`, `intern_id`, `intern_name`, `am_timein`, `am_timeOut`, `pm_timein`, `pm_timeout`, `am_hours_worked`, `pm_hours_worked`, `required_hours_rendered`, `day_total_hours`, `total_hours_rendered`, `created_at`, `overtime_start`, `overtime_hours`) VALUES
(1, 0, 'Jim Hadjili', '09:19:53.000000', '09:20:10.000000', '17:05:35.000000', '17:17:27.000000', '00:00:17.000000', '00:11:52.000000', 486, '00:12:09.000000', '00:00:00.000000', '', '00:00:00', '00:00:00'),
(2, 0, 'gelo', '09:08:06.000000', '09:08:17.000000', '20:04:49.000000', '00:00:00.000000', '00:00:11.000000', '00:00:00.000000', 240, '00:00:11.000000', '00:00:00.000000', '', '00:00:00', '00:00:00'),
(4, 6, 'Jim Hadjili', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 240, '00:00:00.000000', '11:09:39.000000', '2025-05-20', '00:00:00', '00:00:00'),
(5, 6, 'Jim Hadjili', '06:07:28.000000', '06:07:37.000000', '18:08:05.000000', '00:00:00.000000', '00:00:09.000000', '00:00:00.000000', 240, '00:00:09.000000', '11:09:39.000000', '2025-05-21', '00:00:00', '00:00:00'),
(6, 6, 'Jim Hadjili', '13:48:29.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 240, '00:00:00.000000', '11:09:39.000000', '2025-05-23', '00:00:00', '00:00:00'),
(7, 6, 'Jim Hadjili', '00:00:00.000000', '00:00:00.000000', '13:59:48.000000', '14:00:43.000000', '00:00:00.000000', '00:00:55.000000', 240, '00:00:55.000000', '11:09:39.000000', '2025-05-25 13:59:48', '00:00:00', '00:00:00'),
(8, 6, 'Jim Hadjili', '00:00:00.000000', '00:00:00.000000', '14:02:03.000000', '14:02:14.000000', '00:00:00.000000', '00:00:11.000000', 240, '00:00:11.000000', '11:09:39.000000', '2025-06-25 14:02:03', '00:00:00', '00:00:00'),
(9, 6, 'Jim Hadjili', '07:03:25.000000', '00:03:43.000000', '13:00:00.000000', '17:08:42.000000', '06:59:42.000000', '04:08:42.000000', 240, '11:08:24.000000', '11:09:39.000000', '2025-06-27 07:03:25', '00:00:00', '00:00:00'),
(10, 7, 'edrftgyhujikolp', '00:00:00.000000', '00:00:00.000000', '16:02:48.000000', '17:16:49.000000', '00:00:00.000000', '01:14:01.000000', 240, '01:14:01.000000', '01:14:01.000000', '2025-05-20 16:02:48', '00:00:00', '00:00:00'),
(11, 7, 'edrftgyhujikolp', '00:00:00.000000', '00:00:00.000000', '18:09:28.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 240, '00:00:00.000000', '00:00:00.000000', '2025-05-21 18:09:28', '00:00:00', '00:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `interns`
--
ALTER TABLE `interns`
  ADD PRIMARY KEY (`Intern_id`);

--
-- Indexes for table `intern_notes`
--
ALTER TABLE `intern_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `intern_date` (`intern_id`,`note_date`);

--
-- Indexes for table `timesheet`
--
ALTER TABLE `timesheet`
  ADD PRIMARY KEY (`record_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `interns`
--
ALTER TABLE `interns`
  MODIFY `Intern_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `intern_notes`
--
ALTER TABLE `intern_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `timesheet`
--
ALTER TABLE `timesheet`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
