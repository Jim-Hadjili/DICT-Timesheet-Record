-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 08:13 AM
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
(6, '', '0000-00-00', '', 0, '2025-05-20 16:24:26', '2025-05-20 16:24:36'),
(10, '8', '2025-05-22', 'sfsdf', 0, '2025-05-22 16:16:30', '2025-05-22 16:16:30'),
(11, '7', '2025-05-22', 's', 0, '2025-05-22 15:25:37', '2025-05-22 15:25:37');

-- --------------------------------------------------------

--
-- Table structure for table `pause_history`
--

CREATE TABLE `pause_history` (
  `id` int(11) NOT NULL,
  `timesheet_id` int(11) DEFAULT NULL,
  `intern_id` int(11) DEFAULT NULL,
  `pause_start` time DEFAULT NULL,
  `pause_end` time DEFAULT NULL,
  `pause_duration` time DEFAULT NULL,
  `pause_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet`
--

CREATE TABLE `timesheet` (
  `record_id` int(11) NOT NULL,
  `intern_id` int(255) NOT NULL,
  `intern_name` varchar(255) NOT NULL,
  `am_timein` time(6) NOT NULL,
  `am_timein_display` time DEFAULT NULL,
  `am_timeOut` time(6) NOT NULL,
  `pm_timein` time(6) NOT NULL,
  `pm_timeout` time(6) NOT NULL,
  `am_hours_worked` time(6) NOT NULL,
  `pm_hours_worked` time(6) NOT NULL,
  `required_hours_rendered` int(255) NOT NULL,
  `day_total_hours` time(6) NOT NULL,
  `total_hours_rendered` time(6) NOT NULL,
  `created_at` varchar(255) NOT NULL,
  `confirm_overtime` int(11) NOT NULL,
  `overtime_start` time(6) NOT NULL,
  `overtime_hours` time(6) NOT NULL,
  `overtime_end` time(6) NOT NULL,
  `overtime_manual` tinyint(1) DEFAULT 0,
  `pause_start` time DEFAULT '00:00:00',
  `pause_end` time DEFAULT '00:00:00',
  `pause_duration` time DEFAULT '00:00:00',
  `pause_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timesheet`
--

INSERT INTO `timesheet` (`record_id`, `intern_id`, `intern_name`, `am_timein`, `am_timein_display`, `am_timeOut`, `pm_timein`, `pm_timeout`, `am_hours_worked`, `pm_hours_worked`, `required_hours_rendered`, `day_total_hours`, `total_hours_rendered`, `created_at`, `confirm_overtime`, `overtime_start`, `overtime_hours`, `overtime_end`, `overtime_manual`, `pause_start`, `pause_end`, `pause_duration`, `pause_reason`, `notes`) VALUES
(82, 7, 'edrftgyhujikolp', '00:00:00.000000', '09:01:00', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 240, '00:00:00.000000', '00:00:00.000000', '2025-05-26', 0, '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 0, '00:00:00', '00:00:00', '00:00:00', NULL, NULL),
(83, 6, 'Jim Hadjili', '00:00:00.000000', '09:15:03', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 240, '00:00:00.000000', '00:00:00.000000', '2025-05-26', 0, '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 0, '00:00:00', '00:00:00', '00:00:00', NULL, NULL);

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
-- Indexes for table `pause_history`
--
ALTER TABLE `pause_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intern_id` (`intern_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pause_history`
--
ALTER TABLE `pause_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet`
--
ALTER TABLE `timesheet`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pause_history`
--
ALTER TABLE `pause_history`
  ADD CONSTRAINT `pause_history_ibfk_1` FOREIGN KEY (`intern_id`) REFERENCES `interns` (`Intern_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
