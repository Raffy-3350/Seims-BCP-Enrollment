-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 05, 2026 at 07:04 AM
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
-- Database: `bcp_enrollment`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_details`
--

CREATE TABLE `admin_details` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `access_level` enum('Standard','Elevated','Full') DEFAULT 'Standard',
  `employment_type` enum('Permanent','Temporary','Contractual','Job Order') DEFAULT 'Permanent',
  `supervisor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `performed_by` varchar(255) DEFAULT NULL COMMENT 'Email or ID of the user who performed the action',
  `performed_by_role` varchar(50) DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `affected_entity` varchar(255) DEFAULT NULL COMMENT 'Name or ID of the user/role affected',
  `status` varchar(50) NOT NULL COMMENT 'e.g., Success, Failed, Blocked, Revoked'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `timestamp`, `performed_by`, `performed_by_role`, `event_type`, `details`, `affected_entity`, `status`) VALUES
(1, '2026-03-03 15:21:06', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Granted: \'academic.grades.view\', \'academic.grades.edit\', \'academic.transcript\', \'academic.subjects\', \'finance.view\', \'finance.post\', \'finance.scholarship\', \'finance.holds\', \'users.view\', \'users.edit\', \'users.delete\', \'users.password\', \'system.rbac\', \'system.audit\', \'system.config\', \'system.backup\', \'reports.view\', \'reports.export\'.', 'Superadmin Role', 'Success'),
(2, '2026-03-03 15:22:17', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(3, '2026-03-03 15:30:15', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(4, '2026-03-03 15:31:01', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(5, '2026-03-03 15:31:04', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Granted: \'enrollment.view\', \'enrollment.create\', \'enrollment.approve\', \'enrollment.schedule\'.', 'Superadmin Role', 'Success'),
(6, '2026-03-03 15:31:04', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(7, '2026-03-03 15:31:04', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(8, '2026-03-03 15:35:41', 'admin@bcp.edu.ph', 'superadmin', 'Role Changed', 'Changed role for \'Shadrach Hugh Reyna\' from \'superadmin\' to \'faculty\'. ', 'Shadrach Hugh Reyna', 'Success'),
(9, '2026-03-03 15:35:49', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Revoked: \'enrollment.view\', \'enrollment.create\', \'enrollment.approve\', \'enrollment.schedule\'.', 'Superadmin Role', 'Success'),
(10, '2026-03-04 09:20:32', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(11, '2026-03-04 09:21:13', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(12, '2026-03-04 09:24:50', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(13, '2026-03-04 09:25:02', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Granted: \'enrollment.view\', \'enrollment.create\', \'enrollment.approve\', \'enrollment.schedule\', \'academic.grades.view\', \'academic.grades.edit\', \'academic.transcript\', \'academic.subjects\', \'users.view\', \'reports.view\', \'reports.export\'.', 'Registrar Role', 'Success'),
(14, '2026-03-04 09:25:15', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Granted: \'finance.view\', \'finance.post\', \'finance.scholarship\', \'finance.holds\'.', 'Registrar Role', 'Success'),
(15, '2026-03-04 09:27:14', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Revoked: \'academic.grades.view\', \'academic.grades.edit\', \'academic.transcript\', \'academic.subjects\', \'finance.view\', \'finance.post\', \'finance.scholarship\', \'finance.holds\', \'users.view\', \'users.edit\', \'users.delete\', \'users.password\', \'system.rbac\', \'system.audit\', \'system.config\', \'system.backup\', \'reports.view\', \'reports.export\'.', 'Superadmin Role', 'Success'),
(16, '2026-03-04 09:27:53', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(17, '2026-03-04 09:32:21', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(18, '2026-03-04 09:34:49', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Granted: \'enrollment.view\', \'enrollment.create\', \'enrollment.approve\', \'enrollment.schedule\', \'academic.grades.view\', \'academic.grades.edit\', \'academic.transcript\', \'academic.subjects\', \'finance.view\', \'finance.post\', \'finance.scholarship\', \'finance.holds\', \'users.view\', \'users.edit\', \'users.delete\', \'users.password\', \'system.rbac\', \'system.audit\', \'system.config\', \'system.backup\', \'reports.view\', \'reports.export\'.', 'Superadmin Role', 'Success'),
(19, '2026-03-04 09:34:52', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Revoked: \'enrollment.view\', \'enrollment.create\', \'enrollment.approve\', \'enrollment.schedule\', \'academic.grades.view\', \'academic.grades.edit\', \'academic.transcript\', \'academic.subjects\', \'finance.view\', \'finance.post\', \'finance.scholarship\', \'finance.holds\', \'users.view\', \'users.edit\', \'users.delete\', \'users.password\', \'system.rbac\', \'system.audit\', \'system.config\', \'system.backup\', \'reports.view\', \'reports.export\'.', 'Superadmin Role', 'Success'),
(20, '2026-03-04 09:49:39', 'admin@bcp.edu.ph', 'superadmin', 'User Deleted', 'Deleted user \'Shadrach Hugh Reyna\' (ID: BCP-FAC-4621, Role: faculty).', 'Shadrach Hugh Reyna', 'Success'),
(21, '2026-03-04 09:49:41', 'admin@bcp.edu.ph', 'superadmin', 'User Deleted', 'Deleted user \'Aedanne  Keith Arcilla\' (ID: BCP-CSH-0001, Role: librarian).', 'Aedanne  Keith Arcilla', 'Success'),
(22, '2026-03-04 09:49:43', 'admin@bcp.edu.ph', 'superadmin', 'User Deleted', 'Deleted user \'Cromwell Del Mundo\' (ID: BCP-2026-0332, Role: student).', 'Cromwell Del Mundo', 'Success'),
(23, '2026-03-04 09:56:26', 'admin@bcp.edu.ph', 'superadmin', 'User Created', 'Created new user \'Cromwell Del Mundo\' with role \'student\'.', 'Cromwell Del Mundo', 'Success'),
(24, '2026-03-04 09:58:50', 'admin@bcp.edu.ph', 'superadmin', 'Role Changed', 'Changed role for \'Cromwell Del Mundo\' from \'student\' to \'librarian\'. ', 'Cromwell Del Mundo', 'Success'),
(25, '2026-03-04 09:59:10', 'admin@bcp.edu.ph', 'superadmin', 'User Deleted', 'Deleted user \'Cromwell Del Mundo\' (ID: BCP-LIB-304232, Role: librarian).', 'Cromwell Del Mundo', 'Success'),
(26, '2026-03-04 10:01:11', 'admin@bcp.edu.ph', 'superadmin', 'User Created', 'Created new user \'Cromwell Del Mundo\' with role \'student\'.', 'Cromwell Del Mundo', 'Success'),
(27, '2026-03-04 10:04:00', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(28, '2026-03-04 10:04:05', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(29, '2026-03-04 10:04:05', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(30, '2026-03-04 10:04:05', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(31, '2026-03-04 10:05:28', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Reviewed permissions for \'superadmin\' role. No changes were made.', 'Superadmin Role', 'Success'),
(32, '2026-03-04 10:06:36', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Granted: \'enrollment.view\', \'enrollment.create\', \'enrollment.approve\', \'enrollment.schedule\', \'academic.grades.view\', \'academic.grades.edit\', \'academic.transcript\', \'academic.subjects\', \'finance.view\', \'finance.post\', \'finance.scholarship\', \'finance.holds\', \'users.view\', \'users.edit\', \'users.delete\', \'users.password\', \'system.rbac\', \'system.audit\', \'system.config\', \'system.backup\', \'reports.view\', \'reports.export\'.', 'Superadmin Role', 'Success'),
(33, '2026-03-04 10:06:42', 'admin@bcp.edu.ph', 'superadmin', 'Permissions Changed', 'Revoked: \'enrollment.view\', \'enrollment.create\', \'enrollment.approve\', \'enrollment.schedule\', \'academic.grades.view\', \'academic.grades.edit\', \'academic.transcript\', \'academic.subjects\', \'finance.view\', \'finance.post\', \'finance.scholarship\', \'finance.holds\', \'users.view\', \'users.edit\', \'users.delete\', \'users.password\', \'system.rbac\', \'system.audit\', \'system.config\', \'system.backup\', \'reports.view\', \'reports.export\'.', 'Superadmin Role', 'Success'),
(34, '2026-03-04 10:07:02', 'admin@bcp.edu.ph', 'superadmin', 'Role Changed', 'Changed role for \'Cromwell Del Mundo\' from \'student\' to \'librarian\'. ', 'Cromwell Del Mundo', 'Success'),
(35, '2026-03-04 10:07:13', 'admin@bcp.edu.ph', 'superadmin', 'Role Changed', 'Changed role for \'Cromwell Del Mundo\' from \'librarian\' to \'student\'. Reason: Revoked — reverted from librarian back to student', 'Cromwell Del Mundo', 'Success');

-- --------------------------------------------------------

--
-- Table structure for table `cashier_details`
--

CREATE TABLE `cashier_details` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Permanent','Temporary','Contractual','Job Order') DEFAULT 'Permanent',
  `supervisor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculty_details`
--

CREATE TABLE `faculty_details` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `employment_type` enum('Full-time','Part-time','Contractual') DEFAULT 'Full-time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `id_sequences`
--

CREATE TABLE `id_sequences` (
  `id` int(11) NOT NULL,
  `role` enum('student','faculty','admin','superadmin','registrar','cashier','librarian') NOT NULL,
  `year` int(11) NOT NULL,
  `last_sequence` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_sequences`
--

INSERT INTO `id_sequences` (`id`, `role`, `year`, `last_sequence`) VALUES
(20, 'faculty', 2026, 1),
(21, 'admin', 2026, 0),
(22, 'superadmin', 2026, 0),
(23, 'registrar', 2026, 0),
(24, 'cashier', 2026, 1),
(25, 'librarian', 2026, 1);

-- --------------------------------------------------------

--
-- Table structure for table `librarian_details`
--

CREATE TABLE `librarian_details` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Permanent','Temporary','Contractual','Job Order') DEFAULT 'Permanent',
  `supervisor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_details`
--

CREATE TABLE `registrar_details` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Permanent','Temporary','Contractual','Job Order') DEFAULT 'Permanent',
  `supervisor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role` varchar(50) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`permissions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role`, `permissions`) VALUES
('registrar', '{\"enrollment.view\":true,\"enrollment.create\":true,\"enrollment.approve\":true,\"enrollment.schedule\":true,\"academic.grades.view\":true,\"academic.grades.edit\":true,\"academic.transcript\":true,\"academic.subjects\":true,\"finance.view\":true,\"finance.post\":true,\"finance.scholarship\":true,\"finance.holds\":true,\"users.view\":true,\"users.edit\":false,\"users.delete\":false,\"users.password\":false,\"system.rbac\":false,\"system.audit\":false,\"system.config\":false,\"system.backup\":false,\"reports.view\":true,\"reports.export\":true}'),
('superadmin', '{\"enrollment.view\":false,\"enrollment.create\":false,\"enrollment.approve\":false,\"enrollment.schedule\":false,\"academic.grades.view\":false,\"academic.grades.edit\":false,\"academic.transcript\":false,\"academic.subjects\":false,\"finance.view\":false,\"finance.post\":false,\"finance.scholarship\":false,\"finance.holds\":false,\"users.view\":false,\"users.edit\":false,\"users.delete\":false,\"users.password\":false,\"system.rbac\":false,\"system.audit\":false,\"system.config\":false,\"system.backup\":false,\"reports.view\":false,\"reports.export\":false}');

-- --------------------------------------------------------

--
-- Table structure for table `student_details`
--

CREATE TABLE `student_details` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `major` varchar(100) DEFAULT NULL,
  `enrollment_status` enum('Regular','Irregular','Transferee') DEFAULT 'Regular',
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_details`
--

INSERT INTO `student_details` (`id`, `user_id`, `year_level`, `program`, `major`, `enrollment_status`, `guardian_name`, `guardian_contact`, `guardian_relationship`) VALUES
(12, 'BCP-2026-100332', NULL, NULL, NULL, 'Regular', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `superadmin_details`
--

CREATE TABLE `superadmin_details` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employment_type` enum('Permanent','Temporary','Contractual','Job Order') DEFAULT 'Permanent',
  `supervisor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `role` enum('student','faculty','admin','superadmin','registrar','cashier','librarian') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('Male','Female') DEFAULT NULL,
  `lrn` varchar(12) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `personal_email` varchar(255) DEFAULT NULL,
  `institutional_email` varchar(255) NOT NULL,
  `temporary_password` varchar(255) NOT NULL,
  `street_address` text DEFAULT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `role`, `first_name`, `middle_name`, `last_name`, `birth_date`, `gender`, `lrn`, `mobile_number`, `personal_email`, `institutional_email`, `temporary_password`, `street_address`, `barangay`, `city`, `province`, `zip_code`, `created_at`, `updated_at`) VALUES
(21, 'BCP-2026-100332', 'student', 'Cromwell', 'C', 'Del Mundo', '2005-07-18', 'Male', '136634100332', '09152886921', 'cromwelldelmundo08@gmail.com', 'c.delmundo@bcp.edu.ph', '$2y$10$6WJQIpSi7l8bXDWO1k91MeJ41iKHEdz2zOgpv8bCAcD5QGQCh.rE.', '1318 Cadena De Amor', NULL, 'Caloocan', 'Metro Manila', '4000', '2026-03-04 10:01:11', '2026-03-04 10:07:13');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_admin_details`
-- (See below for the actual view)
--
CREATE TABLE `v_admin_details` (
`id` int(11)
,`user_id` varchar(50)
,`full_name` varchar(302)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`institutional_email` varchar(255)
,`department` varchar(100)
,`position` varchar(100)
,`access_level` enum('Standard','Elevated','Full')
,`employment_type` enum('Permanent','Temporary','Contractual','Job Order')
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_cashier_details`
-- (See below for the actual view)
--
CREATE TABLE `v_cashier_details` (
`id` int(11)
,`user_id` varchar(50)
,`full_name` varchar(302)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`institutional_email` varchar(255)
,`department` varchar(100)
,`position` varchar(100)
,`employment_type` enum('Permanent','Temporary','Contractual','Job Order')
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_faculty_details`
-- (See below for the actual view)
--
CREATE TABLE `v_faculty_details` (
`id` int(11)
,`user_id` varchar(50)
,`full_name` varchar(302)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`institutional_email` varchar(255)
,`department` varchar(100)
,`position` varchar(100)
,`specialization` varchar(255)
,`employment_type` enum('Full-time','Part-time','Contractual')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_librarian_details`
-- (See below for the actual view)
--
CREATE TABLE `v_librarian_details` (
`id` int(11)
,`user_id` varchar(50)
,`full_name` varchar(302)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`institutional_email` varchar(255)
,`department` varchar(100)
,`position` varchar(100)
,`employment_type` enum('Permanent','Temporary','Contractual','Job Order')
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_registrar_details`
-- (See below for the actual view)
--
CREATE TABLE `v_registrar_details` (
`id` int(11)
,`user_id` varchar(50)
,`full_name` varchar(302)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`institutional_email` varchar(255)
,`department` varchar(100)
,`position` varchar(100)
,`employment_type` enum('Permanent','Temporary','Contractual','Job Order')
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_student_details`
-- (See below for the actual view)
--
CREATE TABLE `v_student_details` (
`id` int(11)
,`user_id` varchar(50)
,`full_name` varchar(302)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`institutional_email` varchar(255)
,`year_level` varchar(20)
,`program` varchar(100)
,`major` varchar(100)
,`enrollment_status` enum('Regular','Irregular','Transferee')
,`guardian_name` varchar(255)
,`guardian_contact` varchar(20)
,`guardian_relationship` varchar(50)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_superadmin_details`
-- (See below for the actual view)
--
CREATE TABLE `v_superadmin_details` (
`id` int(11)
,`user_id` varchar(50)
,`full_name` varchar(302)
,`first_name` varchar(100)
,`middle_name` varchar(100)
,`last_name` varchar(100)
,`institutional_email` varchar(255)
,`department` varchar(100)
,`position` varchar(100)
,`employment_type` enum('Permanent','Temporary','Contractual','Job Order')
,`supervisor` varchar(255)
);

-- --------------------------------------------------------

--
-- Structure for view `v_admin_details`
--
DROP TABLE IF EXISTS `v_admin_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_admin_details`  AS SELECT `ad`.`id` AS `id`, `ad`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',ifnull(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`first_name` AS `first_name`, `u`.`middle_name` AS `middle_name`, `u`.`last_name` AS `last_name`, `u`.`institutional_email` AS `institutional_email`, `ad`.`department` AS `department`, `ad`.`position` AS `position`, `ad`.`access_level` AS `access_level`, `ad`.`employment_type` AS `employment_type`, `ad`.`supervisor` AS `supervisor` FROM (`admin_details` `ad` join `users` `u` on(`ad`.`user_id` = `u`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_cashier_details`
--
DROP TABLE IF EXISTS `v_cashier_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cashier_details`  AS SELECT `cd`.`id` AS `id`, `cd`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',ifnull(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`first_name` AS `first_name`, `u`.`middle_name` AS `middle_name`, `u`.`last_name` AS `last_name`, `u`.`institutional_email` AS `institutional_email`, `cd`.`department` AS `department`, `cd`.`position` AS `position`, `cd`.`employment_type` AS `employment_type`, `cd`.`supervisor` AS `supervisor` FROM (`cashier_details` `cd` join `users` `u` on(`cd`.`user_id` = `u`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_faculty_details`
--
DROP TABLE IF EXISTS `v_faculty_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_faculty_details`  AS SELECT `fd`.`id` AS `id`, `fd`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',ifnull(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`first_name` AS `first_name`, `u`.`middle_name` AS `middle_name`, `u`.`last_name` AS `last_name`, `u`.`institutional_email` AS `institutional_email`, `fd`.`department` AS `department`, `fd`.`position` AS `position`, `fd`.`specialization` AS `specialization`, `fd`.`employment_type` AS `employment_type` FROM (`faculty_details` `fd` join `users` `u` on(`fd`.`user_id` = `u`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_librarian_details`
--
DROP TABLE IF EXISTS `v_librarian_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_librarian_details`  AS SELECT `ld`.`id` AS `id`, `ld`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',ifnull(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`first_name` AS `first_name`, `u`.`middle_name` AS `middle_name`, `u`.`last_name` AS `last_name`, `u`.`institutional_email` AS `institutional_email`, `ld`.`department` AS `department`, `ld`.`position` AS `position`, `ld`.`employment_type` AS `employment_type`, `ld`.`supervisor` AS `supervisor` FROM (`librarian_details` `ld` join `users` `u` on(`ld`.`user_id` = `u`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_registrar_details`
--
DROP TABLE IF EXISTS `v_registrar_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_registrar_details`  AS SELECT `rd`.`id` AS `id`, `rd`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',ifnull(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`first_name` AS `first_name`, `u`.`middle_name` AS `middle_name`, `u`.`last_name` AS `last_name`, `u`.`institutional_email` AS `institutional_email`, `rd`.`department` AS `department`, `rd`.`position` AS `position`, `rd`.`employment_type` AS `employment_type`, `rd`.`supervisor` AS `supervisor` FROM (`registrar_details` `rd` join `users` `u` on(`rd`.`user_id` = `u`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_student_details`
--
DROP TABLE IF EXISTS `v_student_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_student_details`  AS SELECT `sd`.`id` AS `id`, `sd`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',ifnull(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`first_name` AS `first_name`, `u`.`middle_name` AS `middle_name`, `u`.`last_name` AS `last_name`, `u`.`institutional_email` AS `institutional_email`, `sd`.`year_level` AS `year_level`, `sd`.`program` AS `program`, `sd`.`major` AS `major`, `sd`.`enrollment_status` AS `enrollment_status`, `sd`.`guardian_name` AS `guardian_name`, `sd`.`guardian_contact` AS `guardian_contact`, `sd`.`guardian_relationship` AS `guardian_relationship` FROM (`student_details` `sd` join `users` `u` on(`sd`.`user_id` = `u`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_superadmin_details`
--
DROP TABLE IF EXISTS `v_superadmin_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_superadmin_details`  AS SELECT `sad`.`id` AS `id`, `sad`.`user_id` AS `user_id`, concat(`u`.`first_name`,' ',ifnull(`u`.`middle_name`,''),' ',`u`.`last_name`) AS `full_name`, `u`.`first_name` AS `first_name`, `u`.`middle_name` AS `middle_name`, `u`.`last_name` AS `last_name`, `u`.`institutional_email` AS `institutional_email`, `sad`.`department` AS `department`, `sad`.`position` AS `position`, `sad`.`employment_type` AS `employment_type`, `sad`.`supervisor` AS `supervisor` FROM (`superadmin_details` `sad` join `users` `u` on(`sad`.`user_id` = `u`.`user_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_details`
--
ALTER TABLE `admin_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_admin_user` (`user_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- Indexes for table `cashier_details`
--
ALTER TABLE `cashier_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cashier_user` (`user_id`);

--
-- Indexes for table `faculty_details`
--
ALTER TABLE `faculty_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_faculty_user` (`user_id`);

--
-- Indexes for table `id_sequences`
--
ALTER TABLE `id_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_year` (`role`,`year`);

--
-- Indexes for table `librarian_details`
--
ALTER TABLE `librarian_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_librarian_user` (`user_id`);

--
-- Indexes for table `registrar_details`
--
ALTER TABLE `registrar_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_registrar_user` (`user_id`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role`);

--
-- Indexes for table `student_details`
--
ALTER TABLE `student_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student_user` (`user_id`);

--
-- Indexes for table `superadmin_details`
--
ALTER TABLE `superadmin_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_superadmin_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `institutional_email` (`institutional_email`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_email` (`institutional_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_details`
--
ALTER TABLE `admin_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `cashier_details`
--
ALTER TABLE `cashier_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `faculty_details`
--
ALTER TABLE `faculty_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `id_sequences`
--
ALTER TABLE `id_sequences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `librarian_details`
--
ALTER TABLE `librarian_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `registrar_details`
--
ALTER TABLE `registrar_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_details`
--
ALTER TABLE `student_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `superadmin_details`
--
ALTER TABLE `superadmin_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_details`
--
ALTER TABLE `admin_details`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cashier_details`
--
ALTER TABLE `cashier_details`
  ADD CONSTRAINT `fk_cashier_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `faculty_details`
--
ALTER TABLE `faculty_details`
  ADD CONSTRAINT `fk_faculty_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `librarian_details`
--
ALTER TABLE `librarian_details`
  ADD CONSTRAINT `fk_librarian_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `registrar_details`
--
ALTER TABLE `registrar_details`
  ADD CONSTRAINT `fk_registrar_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_details`
--
ALTER TABLE `student_details`
  ADD CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `superadmin_details`
--
ALTER TABLE `superadmin_details`
  ADD CONSTRAINT `fk_superadmin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Add must_change_password field to users table for first login password change
--
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'must_change_password';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_schema = @dbname)
      AND (table_name = @tablename)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' TINYINT(1) NOT NULL DEFAULT 1 AFTER temporary_password')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

--
-- Drop existing tables if they exist (for clean import)
--
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `students`;

--
-- Table structure for table `role_permissions`
--
CREATE TABLE `role_permissions` (
  `role` varchar(50) NOT NULL,
  `permissions` longtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `students` (registration requests)
--
CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `registration_id` varchar(30) NOT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `birth_date` date NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL DEFAULT 'Male',
  `lrn` char(12) NOT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `personal_email` varchar(120) NOT NULL,
  `street_address` varchar(150) DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `province` varchar(80) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `program` varchar(120) NOT NULL,
  `year_level` varchar(20) NOT NULL DEFAULT '1st Year',
  `major` varchar(100) DEFAULT NULL,
  `enrollment_status` varchar(30) NOT NULL DEFAULT 'Regular',
  `guardian_name` varchar(120) NOT NULL,
  `guardian_relationship` varchar(40) NOT NULL DEFAULT 'Guardian',
  `guardian_contact` varchar(20) NOT NULL,
  `status` enum('pending','approved','rejected','account_created') NOT NULL DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `registration_id` (`registration_id`),
  UNIQUE KEY `lrn` (`lrn`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
