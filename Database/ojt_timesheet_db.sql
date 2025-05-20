-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2025 at 03:10 AM
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
(11, 'gelo', 'SCC', '0444-04-04', 1581, 'Male', 240, 0, ''),
(12, 'Joshua Cihm U. Paltingca', 'zppsu', '2002-06-08', 22, 'Male', 540, 1, 'face_images/face_12_1747392846.png'),
(13, 'Jim Hadjili', 'SCC', '5555-05-05', -3530, 'Male', 240, 1, 'face_images/face_13_1747392894.png'),
(14, 'saud', 'zppsu', '8888-08-08', -6864, 'Male', 240, 1, 'face_images/face_14_1747393030.png'),
(15, 'Sitti Rhaiza Marcos', 'zppsu', '2000-09-20', 24, 'Female', 486, 1, 'face_images/face_15_1747393143.png'),
(16, 'niji', 'SCC', '0000-00-00', -42419, 'Male', 240, 1, 'face_images/face_16_1747393385.png');

-- --------------------------------------------------------

--
-- Table structure for table `timesheet`
--

CREATE TABLE `timesheet` (
  `intern_id` int(11) NOT NULL,
  `intern_name` varchar(255) NOT NULL,
  `am_timein` time(6) NOT NULL,
  `am_timeOut` time(6) NOT NULL,
  `pm_timein` time(6) NOT NULL,
  `pm_timeout` time(6) NOT NULL,
  `am_hours_worked` time(6) NOT NULL,
  `pm_hours_worked` time(6) NOT NULL,
  `required_hours_rendered` int(255) NOT NULL,
  `day_total_hours` time(6) NOT NULL,
  `total_hours_rendered` time(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timesheet`
--

INSERT INTO `timesheet` (`intern_id`, `intern_name`, `am_timein`, `am_timeOut`, `pm_timein`, `pm_timeout`, `am_hours_worked`, `pm_hours_worked`, `required_hours_rendered`, `day_total_hours`, `total_hours_rendered`) VALUES
(11, 'gelo', '00:00:00.000000', '00:00:00.000000', '18:50:59.000000', '00:00:00.000000', '00:00:00.000000', '00:00:00.000000', 240, '00:00:00.000000', '00:00:00.000000'),
(12, 'Joshua Cihm U. Paltingca', '09:06:24.000000', '00:00:00.000000', '18:54:11.000000', '18:55:10.000000', '00:00:00.000000', '00:00:59.000000', 540, '00:00:59.000000', '00:00:00.000000'),
(13, 'Jim Hadjili', '00:00:00.000000', '00:00:00.000000', '18:54:59.000000', '18:55:18.000000', '00:00:00.000000', '00:00:19.000000', 240, '00:00:19.000000', '00:00:00.000000'),
(14, 'saud', '00:00:00.000000', '00:00:00.000000', '18:57:15.000000', '18:57:23.000000', '00:00:00.000000', '00:00:08.000000', 240, '00:00:08.000000', '00:00:00.000000'),
(15, 'Sitti Rhaiza Marcos', '00:00:00.000000', '00:00:00.000000', '18:59:08.000000', '18:59:28.000000', '00:00:00.000000', '00:00:20.000000', 486, '00:00:20.000000', '00:00:00.000000'),
(16, 'niji', '00:00:00.000000', '00:00:00.000000', '19:03:52.000000', '19:07:09.000000', '00:00:00.000000', '00:03:17.000000', 240, '00:03:17.000000', '00:00:00.000000');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `interns`
--
ALTER TABLE `interns`
  ADD PRIMARY KEY (`Intern_id`);

--
-- Indexes for table `timesheet`
--
ALTER TABLE `timesheet`
  ADD PRIMARY KEY (`intern_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `interns`
--
ALTER TABLE `interns`
  MODIFY `Intern_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `timesheet`
--
ALTER TABLE `timesheet`
  MODIFY `intern_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
