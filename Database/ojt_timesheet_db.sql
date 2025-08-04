-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 06:25 PM
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
  `notes` text DEFAULT NULL,
  `photo_data` longtext DEFAULT NULL,
  `photo_timestamp` timestamp NULL DEFAULT NULL,
  `photo_type` varchar(20) DEFAULT 'timein',
  `am_timein_photo` varchar(255) DEFAULT NULL,
  `am_timeout_photo` varchar(255) DEFAULT NULL,
  `pm_timein_photo` varchar(255) DEFAULT NULL,
  `pm_timeout_photo` varchar(255) DEFAULT NULL,
  `am_timein_image` varchar(255) DEFAULT NULL,
  `am_timeout_image` varchar(255) DEFAULT NULL,
  `pm_timein_image` varchar(255) DEFAULT NULL,
  `pm_timeout_image` varchar(255) DEFAULT NULL,
  `am_standard_end` time DEFAULT NULL,
  `pm_standard_end` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timesheet_photos`
--

CREATE TABLE `timesheet_photos` (
  `id` int(11) NOT NULL,
  `intern_id` int(11) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `photo_path` varchar(255) NOT NULL,
  `photo_type` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `timesheet_photos`
--
ALTER TABLE `timesheet_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intern_id` (`intern_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `interns`
--
ALTER TABLE `interns`
  MODIFY `Intern_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `intern_notes`
--
ALTER TABLE `intern_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pause_history`
--
ALTER TABLE `pause_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet`
--
ALTER TABLE `timesheet`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timesheet_photos`
--
ALTER TABLE `timesheet_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pause_history`
--
ALTER TABLE `pause_history`
  ADD CONSTRAINT `pause_history_ibfk_1` FOREIGN KEY (`intern_id`) REFERENCES `interns` (`Intern_id`);

--
-- Constraints for table `timesheet_photos`
--
ALTER TABLE `timesheet_photos`
  ADD CONSTRAINT `timesheet_photos_ibfk_1` FOREIGN KEY (`intern_id`) REFERENCES `interns` (`Intern_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
