-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 06, 2026 at 02:00 PM
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
-- Database: `ojt_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to students',
  `attendance_date` date NOT NULL,
  `block_type` enum('morning','afternoon','overtime') NOT NULL COMMENT 'Time block: morning (6AM-11:59AM), afternoon (12PM-5:59PM), overtime (6PM-10PM)',
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL COMMENT 'Nullable if student forgot to time out',
  `hours` decimal(5,2) DEFAULT NULL COMMENT 'Calculated hours (derived from time_in/time_out)',
  `time_in_latitude` decimal(10,8) NOT NULL COMMENT 'GPS latitude when student timed in',
  `time_in_longitude` decimal(11,8) NOT NULL COMMENT 'GPS longitude when student timed in',
  `within_radius` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether student was within allowed radius (40 meters) of workplace',
  `photo_path` varchar(500) NOT NULL COMMENT 'Path to captured photo at time-in',
  `status` enum('ongoing','completed','pending_exception') NOT NULL DEFAULT 'ongoing',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `forgot_timeout_reason` text DEFAULT NULL,
  `forgot_timeout_file` varchar(500) DEFAULT NULL,
  `instructor_response` text DEFAULT NULL,
  `request_status` enum('pending','approved','rejected') DEFAULT NULL,
  `missing_timeout_flagged_at` datetime DEFAULT NULL COMMENT 'When the system flagged this as missing timeout'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `student_id`, `attendance_date`, `block_type`, `time_in`, `time_out`, `hours`, `time_in_latitude`, `time_in_longitude`, `within_radius`, `photo_path`, `status`, `created_at`, `updated_at`, `forgot_timeout_reason`, `forgot_timeout_file`, `instructor_response`, `request_status`, `missing_timeout_flagged_at`) VALUES
(1, 57, '2026-01-01', 'overtime', '2026-01-01 18:34:16', NULL, NULL, 10.66643990, 122.93614206, 1, '../../storage/uploads/attendance_images/att_57_20260101_113416.png', 'ongoing', '2026-01-01 10:34:16', '2026-01-03 06:20:02', 'forgot', '../uploads/documents/timeout_req_1_1767421202.pdf', NULL, 'pending', NULL),
(6, 58, '2026-01-03', 'afternoon', '2026-01-03 14:06:07', '2026-01-03 18:36:03', 4.50, 10.66276188, 123.03772370, 1, '../../storage/uploads/attendance_images/att_58_20260103_070607.png', 'completed', '2026-01-03 06:06:07', '2026-01-03 10:36:03', NULL, NULL, NULL, NULL, NULL),
(7, 58, '2026-01-02', 'morning', '0000-00-00 00:00:00', NULL, NULL, 0.00000000, 0.00000000, 1, '', 'pending_exception', '2026-01-03 06:10:03', '2026-01-03 14:46:13', NULL, NULL, NULL, NULL, '2026-01-03 22:46:13'),
(8, 58, '2026-01-03', 'overtime', '2026-01-03 18:38:29', '2026-01-03 22:00:00', 3.36, 10.66276249, 123.03773028, 1, '../../storage/uploads/attendance_images/att_58_20260103_113829.png', 'completed', '2026-01-03 10:38:29', '2026-01-05 15:00:03', 'test', '../uploads/documents/timeout_req_8_1767625179.pdf', 'Approved. 3.36 hours added based on overtime block end time.', 'approved', '2026-01-03 22:46:13'),
(10, 58, '2026-01-05', 'afternoon', '2026-01-05 17:38:55', NULL, NULL, 10.66289310, 123.03765898, 1, '../../storage/uploads/attendance_images/att_58_20260105_103855.png', 'ongoing', '2026-01-05 09:38:55', '2026-01-05 15:34:08', 'asdasasd', NULL, NULL, 'pending', NULL),
(11, 58, '2026-01-05', 'overtime', '2026-01-05 18:23:59', NULL, NULL, 10.66285205, 123.03775549, 1, '../../storage/uploads/attendance_images/att_58_20260105_112359.png', 'ongoing', '2026-01-05 10:23:59', '2026-01-05 10:23:59', NULL, NULL, NULL, NULL, NULL),
(12, 58, '2026-01-06', 'afternoon', '2026-01-06 13:16:07', '2026-01-06 16:04:13', 2.80, 10.66288700, 123.03766500, 1, '../../storage/uploads/attendance_images/att_58_20260106_061607.png', 'completed', '2026-01-06 05:16:07', '2026-01-06 08:04:13', NULL, NULL, NULL, NULL, NULL),
(13, 58, '2026-01-06', 'overtime', '2026-01-06 18:52:50', NULL, NULL, 10.66277915, 123.03766474, 1, '../../storage/uploads/attendance_images/att_58_20260106_115250.png', 'ongoing', '2026-01-06 10:52:50', '2026-01-06 10:52:50', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `document_submissions`
--

CREATE TABLE `document_submissions` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to students',
  `document_type_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to document_types',
  `file_path` varchar(500) NOT NULL COMMENT 'Path to uploaded file',
  `file_type` varchar(50) DEFAULT NULL COMMENT 'File extension/mime type',
  `file_size_bytes` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'File size in bytes',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','revise','rejected') NOT NULL DEFAULT 'pending',
  `reviewer_instructor_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'FK to instructors - nullable until reviewed',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `feedback` text DEFAULT NULL COMMENT 'Instructor remarks/feedback',
  `points` decimal(5,2) DEFAULT NULL COMMENT 'Bonus points awarded by instructor for document quality',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_submissions`
--

INSERT INTO `document_submissions` (`id`, `student_id`, `document_type_id`, `file_path`, `file_type`, `file_size_bytes`, `submitted_at`, `status`, `reviewer_instructor_id`, `reviewed_at`, `feedback`, `points`, `created_at`, `updated_at`) VALUES
(17, 58, 1, '../../storage/uploads/student_docs/doc_58_1_1767372385.pdf', NULL, NULL, '2026-01-02 16:46:25', 'approved', NULL, NULL, NULL, NULL, '2026-01-02 16:46:25', '2026-01-02 16:48:23'),
(18, 58, 2, '../../storage/uploads/student_docs/doc_58_2_1767372391.pdf', NULL, NULL, '2026-01-02 16:46:31', 'approved', NULL, NULL, NULL, NULL, '2026-01-02 16:46:31', '2026-01-02 16:48:23'),
(19, 58, 3, '../../storage/uploads/student_docs/doc_58_3_1767372397.pdf', NULL, NULL, '2026-01-02 16:46:37', 'approved', NULL, NULL, NULL, NULL, '2026-01-02 16:46:37', '2026-01-02 16:48:23'),
(20, 58, 4, '../../storage/uploads/student_docs/doc_58_4_1767372404.docx', NULL, NULL, '2026-01-02 16:46:44', 'approved', NULL, NULL, NULL, NULL, '2026-01-02 16:46:44', '2026-01-02 16:48:23'),
(21, 58, 5, '../../storage/uploads/student_docs/doc_58_5_1767372411.docx', NULL, NULL, '2026-01-02 16:46:51', 'approved', NULL, NULL, NULL, NULL, '2026-01-02 16:46:51', '2026-01-02 16:48:23'),
(22, 58, 6, '../../storage/uploads/student_docs/doc_58_6_1767372416.docx', NULL, NULL, '2026-01-02 16:46:56', 'approved', NULL, NULL, '', 5.00, '2026-01-02 16:46:56', '2026-01-06 06:54:16'),
(23, 58, 7, '../../storage/uploads/student_docs/doc_58_7_1767372424.docx', NULL, NULL, '2026-01-02 16:47:04', 'approved', NULL, NULL, '', 10.00, '2026-01-02 16:47:04', '2026-01-06 06:49:40'),
(24, 58, 8, '../../storage/uploads/student_docs/doc_58_8_1767372440.docx', NULL, NULL, '2026-01-02 16:47:20', 'approved', NULL, NULL, '', 10.00, '2026-01-02 16:47:20', '2026-01-06 06:49:36'),
(25, 58, 9, '../../storage/uploads/student_docs/doc_58_9_1767372448.pdf', NULL, NULL, '2026-01-02 16:47:28', 'approved', NULL, NULL, '', 3.00, '2026-01-02 16:47:28', '2026-01-06 06:49:30'),
(26, 58, 10, '../../storage/uploads/student_docs/doc_58_10_1767372456.pdf', NULL, NULL, '2026-01-02 16:47:36', 'approved', NULL, NULL, '', 5.00, '2026-01-02 16:47:36', '2026-01-06 06:49:25'),
(27, 57, 1, '../../storage/uploads/student_docs/doc_57_1_1767414249.docx', NULL, NULL, '2026-01-03 04:24:09', 'approved', NULL, NULL, NULL, NULL, '2026-01-03 04:24:09', '2026-01-03 04:27:47'),
(28, 57, 2, '../../storage/uploads/student_docs/doc_57_2_1767414258.docx', NULL, NULL, '2026-01-03 04:24:18', 'approved', NULL, NULL, NULL, NULL, '2026-01-03 04:24:18', '2026-01-03 04:27:47'),
(29, 57, 3, '../../storage/uploads/student_docs/doc_57_3_1767414265.pdf', NULL, NULL, '2026-01-03 04:24:25', 'approved', NULL, NULL, NULL, NULL, '2026-01-03 04:24:25', '2026-01-03 04:27:47'),
(37, 57, 4, '../../storage/uploads/student_docs/doc_57_4_1767456058.pdf', NULL, NULL, '2026-01-03 16:00:58', 'pending', NULL, NULL, NULL, NULL, '2026-01-03 16:00:58', '2026-01-03 16:00:58'),
(38, 81, 56, '../../storage/uploads/student_docs/doc_81_56_1767677625.docx', NULL, NULL, '2026-01-06 05:33:45', 'approved', NULL, NULL, NULL, NULL, '2026-01-06 05:33:45', '2026-01-06 05:42:54'),
(39, 81, 57, '../../storage/uploads/student_docs/doc_81_57_1767677632.pdf', NULL, NULL, '2026-01-06 05:33:52', 'approved', NULL, NULL, NULL, NULL, '2026-01-06 05:33:52', '2026-01-06 05:42:54'),
(40, 81, 60, '../../storage/uploads/student_docs/doc_81_60_1767677646.pdf', NULL, NULL, '2026-01-06 05:34:06', 'approved', NULL, NULL, NULL, NULL, '2026-01-06 05:34:06', '2026-01-06 05:42:54'),
(41, 81, 61, '../../storage/uploads/student_docs/doc_81_61_1767677663.pdf', NULL, NULL, '2026-01-06 05:34:23', 'approved', NULL, NULL, NULL, NULL, '2026-01-06 05:34:23', '2026-01-06 05:42:54'),
(42, 81, 62, '../../storage/uploads/student_docs/doc_81_62_1767678157.pdf', NULL, NULL, '2026-01-06 05:42:37', 'approved', NULL, NULL, 'tesyt', NULL, '2026-01-06 05:34:36', '2026-01-06 05:42:54'),
(43, 81, 64, '../../storage/uploads/student_docs/doc_81_64_1767677707.pdf', NULL, NULL, '2026-01-06 05:35:07', 'approved', NULL, NULL, 'test', NULL, '2026-01-06 05:35:07', '2026-01-06 05:41:16'),
(44, 81, 65, '../../storage/uploads/student_docs/doc_81_65_1767678148.docx', NULL, NULL, '2026-01-06 05:42:28', 'approved', NULL, NULL, 'test', NULL, '2026-01-06 05:35:18', '2026-01-06 05:42:54'),
(45, 81, 67, '../../storage/uploads/student_docs/doc_81_67_1767678027.docx', NULL, NULL, '2026-01-06 05:40:27', 'approved', NULL, NULL, 'test', NULL, '2026-01-06 05:40:27', '2026-01-06 05:41:09'),
(46, 58, 68, '../../storage/uploads/student_docs/doc_58_68_1767679308.pdf', NULL, NULL, '2026-01-06 06:01:48', 'pending', NULL, NULL, NULL, NULL, '2026-01-06 06:01:48', '2026-01-06 06:01:48');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL COMMENT 'Document name (e.g., "Memorandum of Agreement (MOA)")',
  `code` varchar(50) DEFAULT NULL COMMENT 'Short key (e.g., "MOA", "Application letter")',
  `category` enum('pre_required','weekly','monthly','excuse','other') NOT NULL DEFAULT 'other',
  `is_pre_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Must be approved before OJT attendance can start',
  `is_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Whether this is a mandatory requirement',
  `frequency` enum('once','weekly','monthly','per_incident') NOT NULL DEFAULT 'once',
  `description` text DEFAULT NULL,
  `template_path` varchar(255) DEFAULT NULL COMMENT 'Path to template file',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deadline` date DEFAULT NULL COMMENT 'Submission deadline for this document type',
  `instructor_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID of the instructor who created this document type'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `name`, `code`, `category`, `is_pre_required`, `is_required`, `frequency`, `description`, `template_path`, `is_active`, `created_at`, `updated_at`, `deadline`, `instructor_id`) VALUES
(1, 'Application Letter', 'APPLICATION_LETTER', 'pre_required', 1, 1, 'once', 'Formal application letter for OJT placement', '../../storage/uploads/templates/template_1_1767258162.pdf', 1, '2026-01-01 08:05:53', '2026-01-05 16:39:05', NULL, 3),
(2, 'Resume/Curriculum Vitae', 'RESUME', 'pre_required', 1, 1, 'once', 'Updated resume or CV', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:39:10', NULL, 3),
(3, 'Endorsement Letter', 'ENDORSEMENT_LETTER', 'pre_required', 1, 1, 'once', 'Endorsement letter from the school', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:39:14', NULL, 3),
(4, 'Medical Certificate', 'MEDICAL_CERT', 'pre_required', 1, 1, 'once', 'Medical certificate of fitness', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:39:18', NULL, 3),
(5, 'Parent Consent Form', 'PARENT_CONSENT', 'pre_required', 1, 1, 'once', 'Notarized parental consent (for minors)', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:39:23', NULL, 3),
(6, 'Pledge of Good Conduct', 'PLEDGE_CONDUCT', 'pre_required', 1, 1, 'once', 'Signed pledge of good conduct', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:39:51', NULL, 3),
(7, 'Misdemeanor Penalty Form', 'MISDEMEANOR_FORM', 'pre_required', 1, 1, 'once', 'Acknowledgment of misdemeanor penalties', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:39:59', NULL, 3),
(8, 'Certificate of Attendance', 'CERT_ATTENDANCE', 'pre_required', 1, 1, 'once', 'Certificate of attendance from company (submitted at end of OJT)', NULL, 1, '2026-01-01 08:05:53', '2026-01-06 05:01:40', NULL, 3),
(9, 'Memorandum of Agreement (MOA)', 'MOA', 'other', 0, 1, 'once', 'Signed MOA between school and company', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:40:06', NULL, 3),
(10, 'OJT Plan', 'OJT_PLAN', 'other', 0, 1, 'once', 'Detailed OJT work plan', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:40:10', NULL, 3),
(11, 'Weekly OJT Report', 'WEEKLY_REPORT', 'weekly', 0, 1, 'weekly', 'Weekly progress report', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:40:15', NULL, 3),
(12, 'Monthly OJT Report', 'MONTHLY_REPORT', 'monthly', 0, 1, 'monthly', 'Monthly summary report', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:40:19', NULL, 3),
(13, 'Excuse Letter', 'EXCUSE_LETTER', 'excuse', 0, 0, 'per_incident', 'Letter explaining absence or tardiness', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:40:22', NULL, 3),
(14, 'Medical Certificate (Excuse)', 'MEDICAL_EXCUSE', 'excuse', 0, 0, 'per_incident', 'Medical certificate for sick leave', NULL, 1, '2026-01-01 08:05:53', '2026-01-05 16:40:27', NULL, 3),
(55, 'sample doc', 'DOCsample', 'other', 0, 1, 'once', NULL, '../../storage/uploads/templates/template_55_1767630097.docx', 1, '2026-01-05 16:21:37', '2026-01-05 16:40:31', '2026-01-10', 2),
(56, 'Endorsement', 'Endorsement', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-05 17:00:44', '2026-01-05 17:00:44', '0000-00-00', 2),
(57, 'Application letter', 'Application Letter', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:19:52', '2026-01-06 05:19:52', '0000-00-00', 2),
(60, 'resume', 'resume', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:29:00', '2026-01-06 05:29:00', '0000-00-00', 2),
(61, 'certificate of attendance', 'Cert of attendance', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:30:18', '2026-01-06 05:30:18', '0000-00-00', 2),
(62, 'Medical Certificate', 'Med Cert', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:30:44', '2026-01-06 05:30:44', '0000-00-00', 2),
(64, 'Pledge of good conduct', 'Pledge of good conduct', 'pre_required', 1, 1, 'once', NULL, '../../storage/uploads/templates/template_64_1767677842.pdf', 1, '2026-01-06 05:31:34', '2026-01-06 05:37:22', '0000-00-00', 2),
(65, 'Misdemeanor Penalty Policy', 'Misdemeanor Penalty Policy', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:32:10', '2026-01-06 05:32:10', '0000-00-00', 2),
(67, 'parent consent', 'parent consent', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51', '0000-00-00', 2),
(68, 'sample late document', 'sample late document', 'other', 0, 1, 'once', NULL, NULL, 1, '2026-01-06 05:58:24', '2026-01-06 05:58:24', '2026-01-05', 3),
(69, 'deadline 2', 'deadline 2', 'other', 0, 1, 'once', NULL, NULL, 1, '2026-01-06 06:01:07', '2026-01-06 06:01:07', '2026-01-09', 3);

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `admin_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to users (admin role)',
  `recipient_scope` enum('all_students','all_instructors','specific_student') NOT NULL,
  `student_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'FK to students - used when recipient_scope = specific_student',
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `admin_id`, `recipient_scope`, `student_id`, `subject`, `body`, `sent_at`) VALUES
(1, 83, 'specific_student', NULL, 'twest', 'test', '2025-12-31 05:15:07'),
(2, 83, 'specific_student', NULL, 'test', 'test', '2025-12-31 05:16:47'),
(3, 83, 'specific_student', NULL, 'test', 'test', '2025-12-31 05:17:53'),
(4, 83, 'all_students', NULL, 'test', 'test', '2025-12-31 05:34:41'),
(5, 83, 'all_students', NULL, 'test', 'test', '2025-12-31 05:36:00'),
(6, 83, 'all_students', NULL, 'test', 'test', '2025-12-31 05:36:44'),
(7, 83, 'all_students', NULL, 'test', 'test', '2025-12-31 05:37:49'),
(8, 83, 'all_students', NULL, 'dasdas', 'asdasda', '2025-12-31 05:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `excused_dates`
--

CREATE TABLE `excused_dates` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to students table',
  `excused_date` date NOT NULL COMMENT 'The date marked as excused',
  `instructor_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to instructors table',
  `hours_added` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Hours credited for this excused date',
  `reason` text NOT NULL COMMENT 'Reason for the excused absence',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When this record was created'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks excused dates for students with hours credited by instructors';

--
-- Dumping data for table `excused_dates`
--

INSERT INTO `excused_dates` (`id`, `student_id`, `excused_date`, `instructor_id`, `hours_added`, `reason`, `created_at`) VALUES
(1, 58, '2026-01-05', 3, 8.00, 'test', '2026-01-05 11:04:10'),
(2, 55, '2026-01-05', 3, 8.00, 'test', '2026-01-05 11:18:11'),
(3, 55, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:49'),
(4, 57, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:49'),
(5, 58, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:49'),
(6, 59, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:49'),
(7, 60, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:49'),
(8, 61, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(9, 62, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(10, 63, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(11, 64, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(12, 65, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(13, 66, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(14, 67, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(15, 68, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(16, 69, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(17, 70, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(18, 71, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(19, 72, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(20, 73, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(21, 74, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(22, 75, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(23, 76, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(24, 77, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(25, 79, '2026-01-08', 3, 8.00, 'dasdasd', '2026-01-05 11:25:50'),
(26, 55, '2026-01-06', 3, 8.00, 'test', '2026-01-05 11:27:16'),
(27, 69, '2026-01-05', 3, 8.00, 'test', '2026-01-05 15:08:39'),
(28, 76, '2026-01-30', 3, 8.00, 'test test test', '2026-01-05 15:17:21');

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to users table',
  `department` varchar(100) DEFAULT NULL COMMENT 'Department/College',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`id`, `user_id`, `department`, `created_at`, `updated_at`) VALUES
(1, 81, 'College of Computer Studies', '2025-12-29 16:27:49', '2025-12-29 16:27:49'),
(2, 82, 'College of Computer Studies', '2025-12-29 16:27:49', '2025-12-29 16:27:49'),
(3, 83, 'College of Computer Studies', '2025-12-29 16:27:49', '2025-12-29 16:27:49');

-- --------------------------------------------------------

--
-- Table structure for table `ojt_summaries`
--

CREATE TABLE `ojt_summaries` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to students',
  `hours_completed` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Sum of attendance hours + manual adjustments',
  `hours_required` decimal(6,2) NOT NULL DEFAULT 600.00 COMMENT 'Target OJT hours',
  `manual_adjustment_hours` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT 'Instructor-added hours (e.g., +8 for school excuses)',
  `adjusted_by_instructor_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'FK to instructors - who made the adjustment',
  `adjustment_reason` text DEFAULT NULL COMMENT 'Reason for manual adjustment (e.g., "School activity", "Bulk adjustment")',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ojt_summaries`
--

INSERT INTO `ojt_summaries` (`id`, `student_id`, `hours_completed`, `hours_required`, `manual_adjustment_hours`, `adjusted_by_instructor_id`, `adjustment_reason`, `last_updated`, `created_at`) VALUES
(1, 55, 25.00, 600.00, 25.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-05: test; Excused on 2026-01-08: dasdasd; Excused on 2026-01-06: test', '2026-01-05 11:27:16', '2025-12-31 04:26:00'),
(3, 57, 25.00, 600.00, 34.00, 3, 'Manual adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:49', '2025-12-31 04:26:00'),
(4, 58, 74.16, 600.00, 108.50, 3, 'Excused absence on 2026-01-05: test; Excused on 2026-01-05: test; Excused on 2026-01-08: dasdasd', '2026-01-06 08:04:13', '2025-12-31 04:26:00'),
(5, 59, 19.00, 600.00, 32.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:49', '2025-12-31 04:26:00'),
(6, 60, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(7, 61, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(8, 62, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(9, 63, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(10, 64, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(11, 65, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(12, 66, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(13, 67, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(14, 68, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(15, 69, 20.00, 600.00, 20.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd; Excused on 2026-01-05: test', '2026-01-05 15:08:39', '2025-12-31 04:26:00'),
(16, 70, 13.00, 600.00, 90.00, 3, 'Manual adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(17, 71, 13.00, 600.00, 17.00, 3, 'Manual adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(18, 72, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(19, 73, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(20, 74, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(21, 75, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(22, 76, 20.00, 600.00, 20.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd; Excused on 2026-01-30: test test test', '2026-01-05 15:17:21', '2025-12-31 04:26:00'),
(23, 77, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00'),
(24, 79, 12.00, 600.00, 12.00, 3, 'Bulk hours adjustment by instructor; Excused on 2026-01-08: dasdasd', '2026-01-05 11:25:50', '2025-12-31 04:26:00');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(11) UNSIGNED NOT NULL,
  `section_code` varchar(10) NOT NULL COMMENT 'Short code like "4A", "4B", "4C"',
  `section_name` varchar(50) NOT NULL COMMENT 'Full name like "BSIT-4A", "BSIT-4B"',
  `department` varchar(100) NOT NULL COMMENT 'College of Computer Studies, College of Education, etc.',
  `instructor_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Assigned instructor/supervisor (FK to instructors)',
  `year` varchar(10) NOT NULL COMMENT 'Academic year e.g., "2025"',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Whether this section is currently active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `section_code`, `section_name`, `department`, `instructor_id`, `year`, `is_active`, `created_at`, `updated_at`) VALUES
(4, '4B', 'BSIT4B', 'College of Computer Studies', 3, '2025', 1, '2025-12-29 16:26:42', '2025-12-30 14:06:43'),
(5, '4A', 'BSIT4A', 'College of Computer Studies', 2, '2025', 1, '2025-12-29 17:26:13', '2026-01-02 14:49:31'),
(12, 'BSIS4B', 'BS information System 4B', 'College of Computer Studies', NULL, '2025', 0, '2025-12-30 14:57:31', '2025-12-30 14:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to users table',
  `department` varchar(100) DEFAULT NULL COMMENT 'Can be derived from sections but kept for convenience',
  `target_ojt_hours` decimal(6,2) NOT NULL DEFAULT 600.00 COMMENT 'Target OJT hours (e.g., 600)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `department`, `target_ojt_hours`, `created_at`, `updated_at`) VALUES
(55, 56, 'College of Computer Studies', 600.00, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(57, 58, 'College of Computer Studies', 600.00, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(58, 59, 'College of Computer Studies', 600.00, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(59, 60, 'College of Computer Studies', 600.00, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(60, 61, 'College of Computer Studies', 600.00, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(61, 62, 'College of Computer Studies', 600.00, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(62, 63, 'College of Computer Studies', 600.00, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(63, 64, 'College of Computer Studies', 600.00, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(64, 65, 'College of Computer Studies', 600.00, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(65, 66, 'College of Computer Studies', 600.00, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(66, 67, 'College of Computer Studies', 600.00, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(67, 68, 'College of Computer Studies', 600.00, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(68, 69, 'College of Computer Studies', 600.00, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(69, 70, 'College of Computer Studies', 600.00, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(70, 71, 'College of Computer Studies', 600.00, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(71, 72, 'College of Computer Studies', 600.00, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(72, 73, 'College of Computer Studies', 600.00, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(73, 74, 'College of Computer Studies', 600.00, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(74, 75, 'College of Computer Studies', 600.00, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(75, 76, 'College of Computer Studies', 600.00, '2025-12-29 16:26:45', '2025-12-29 16:26:45'),
(76, 77, 'College of Computer Studies', 600.00, '2025-12-29 16:26:45', '2025-12-29 16:26:45'),
(77, 78, 'College of Computer Studies', 600.00, '2025-12-29 16:26:45', '2025-12-29 16:26:45'),
(79, 80, 'College of Computer Studies', 600.00, '2025-12-29 16:26:45', '2025-12-29 16:26:45'),
(80, 84, 'College of Computer Studies', 600.00, '2025-12-29 17:26:13', '2025-12-29 17:26:13'),
(81, 85, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(82, 86, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(83, 87, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(84, 88, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(85, 89, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(86, 90, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(87, 91, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(88, 92, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(89, 93, 'College of Computer Studies', 600.00, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(90, 94, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(91, 95, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(92, 96, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(93, 97, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(94, 98, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(95, 99, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(96, 100, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(97, 101, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(98, 102, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(99, 103, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(100, 104, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(101, 105, 'College of Computer Studies', 600.00, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(102, 106, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(103, 107, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(104, 108, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(105, 109, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(106, 110, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(107, 111, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(108, 112, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(109, 113, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(110, 114, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(111, 115, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(112, 116, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(113, 117, 'College of Computer Studies', 600.00, '2026-01-03 15:54:35', '2026-01-03 15:54:35');

-- --------------------------------------------------------

--
-- Table structure for table `student_workplaces`
--

CREATE TABLE `student_workplaces` (
  `id` int(11) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to students',
  `company_name` varchar(200) NOT NULL,
  `company_head` varchar(100) DEFAULT NULL COMMENT 'Company head/supervisor name',
  `company_address` varchar(255) NOT NULL,
  `position_title` varchar(100) DEFAULT NULL COMMENT 'Student role (e.g., Software Development Intern)',
  `workplace_latitude` decimal(10,8) NOT NULL COMMENT 'GPS latitude for radius verification',
  `workplace_longitude` decimal(11,8) NOT NULL COMMENT 'GPS longitude for radius verification',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL COMMENT 'Nullable if currently active',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active placement flag',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_workplaces`
--

INSERT INTO `student_workplaces` (`id`, `student_id`, `company_name`, `company_head`, `company_address`, `position_title`, `workplace_latitude`, `workplace_longitude`, `start_date`, `end_date`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 55, 'Gen Tech', 'Clara Cruz', 'Sample Street', 'Intern', 10.66625800, 122.93593800, '2026-01-01', '2026-01-03', 0, '2026-01-01 06:59:50', '2026-01-03 10:19:12'),
(4, 57, 'asd', 'asd', 'asd', 'asd', 10.66631381, 122.93593940, '2026-01-01', NULL, 1, '2026-01-01 07:49:17', '2026-01-01 07:49:17'),
(5, 61, 'Glendale Tech Inc.', 'Ms. Morgana', 'Glendale Homes Blk 2 lot 4', 'Intern', 10.66283254, 123.03763183, '2026-01-02', NULL, 1, '2026-01-02 14:31:21', '2026-01-02 14:31:21'),
(6, 58, 'Glendale Tech Inc.', 'Ms. Morgana', 'Glendale Homes Blk 2 lot 4', 'Intern', 10.66285442, 123.03762644, '2026-01-03', NULL, 1, '2026-01-02 16:40:26', '2026-01-02 16:40:26'),
(7, 55, 'ComTech Inc', 'Mr. Ernesto Dela Cruz', '', 'Technical', 10.66265558, 123.03729917, '2026-01-03', NULL, 1, '2026-01-03 10:19:12', '2026-01-03 10:19:12'),
(8, 81, 'Chmsu', 'Ms. Gerondia', '11th street sample sample', 'intern', 10.64273926, 122.93970555, '2026-01-06', NULL, 1, '2026-01-05 16:54:47', '2026-01-05 16:54:47');

-- --------------------------------------------------------

--
-- Table structure for table `timeout_exceptions`
--

CREATE TABLE `timeout_exceptions` (
  `id` int(11) UNSIGNED NOT NULL,
  `attendance_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to attendance_records',
  `student_id` int(11) UNSIGNED NOT NULL COMMENT 'FK to students (redundant but convenient)',
  `block_type` enum('morning','afternoon','overtime') NOT NULL COMMENT 'Copied from attendance for display',
  `letter_file_path` varchar(500) DEFAULT NULL COMMENT 'Path to uploaded letter/supporting document',
  `letter_file_name` varchar(255) DEFAULT NULL COMMENT 'Original filename',
  `reason` text DEFAULT NULL COMMENT 'Student explanation',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `instructor_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'FK to instructors - only instructor from same section can approve',
  `instructor_response` text DEFAULT NULL COMMENT 'Instructor feedback/justification',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `school_id` varchar(20) NOT NULL COMMENT 'School/Employee ID used in CSV & listings',
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Hashed password (bcrypt/argon2)',
  `role` enum('admin','student','instructor') NOT NULL,
  `gender` enum('male','female','non-binary') DEFAULT NULL,
  `section_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'FK to sections - only for students',
  `contact` varchar(20) DEFAULT NULL COMMENT 'Contact number',
  `facebook_name` varchar(100) DEFAULT NULL COMMENT 'Facebook name/username',
  `year` varchar(10) DEFAULT NULL COMMENT 'Academic year',
  `profile_pic_path` varchar(255) DEFAULT NULL COMMENT 'Path to profile picture',
  `is_archived` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'For archive-by-year feature',
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `full_name`, `email`, `password_hash`, `role`, `gender`, `section_id`, `contact`, `facebook_name`, `year`, `profile_pic_path`, `is_archived`, `archived_at`, `created_at`, `updated_at`) VALUES
(1, 'ADM10052500', 'Manel Color', 'admin@ojt.local', '$2y$10$oEOBGhHihVag6TWfzJ/fCOxddINZ9wAWwv8pA8k6ZULjEUwSuuNYa', 'admin', 'male', NULL, '09432265985', 'Manwelzz', NULL, '../../storage/uploads/profiles/admin_1_1767107912.jpg', 0, NULL, '2025-12-29 11:07:13', '2025-12-30 18:08:36'),
(56, 'FKA11130200', 'Felix Kirk Amante', 'mzakcoloradz1@gmail.com', '$2y$10$kydT3amX3Y6jpz9JrY/hWO9EFieIDUnPxoGulnNpNc1vDtwh.WKfu', 'student', 'male', 4, '09246615000', 'Felixxxxxxxxx', '2025', '../../storage/uploads/profiles/profile_56_1767117384.png', 0, NULL, '2025-12-29 16:26:42', '2026-01-03 09:59:07'),
(58, 'RGA01180300', 'Renz Anne Gallero', 'SailorGallero@gmail.com', '$2y$10$rkVRcF83KTWHylxZnKScS.zQ0ozrpyCrASH4EVygN0atIEXTyp9py', 'student', 'female', 4, '9468516552', 'Renzeyy', '2025', '../../storage/uploads/profiles/profile_58_1767253839.jpg', 0, NULL, '2025-12-29 16:26:42', '2026-01-01 07:50:39'),
(59, 'SLD01130300', 'Sen Elizabeth Layante', 'Senny@gmail.com', '$2y$10$dTlEVemtA91UYStMhKe4n.b9uzji/aUMo9E1MPeUhHXIeT1/Kikw2', 'student', 'female', 4, '9092653645', 'Sen Elizabeth', '2025', NULL, 0, NULL, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(60, 'CMD01320300', 'Christian Louise Mellizo', 'Christian12@gmail.com', '$2y$10$NBCZjEldpiV/Z3VuA4k93eGgAdD2DKRZC9PtddOuWmrsbsFCHvv8G', 'student', 'male', 4, '9216645235', 'Christian Mellizo', '2025', NULL, 0, NULL, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(61, 'JAD01020400', 'John Paul Amar', 'Amar@gmail.com', '$2y$10$wOpHjLRt3rE.fbLHXQ9.vuf1vSo4KJHmzTM61bUJaf96nU9agJ4pK', 'student', 'male', 4, '9485511326', 'John Amar', '2025', NULL, 0, NULL, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(62, 'POC01230300', 'Oscar Pillado', 'PilladoOscar2@gmail.com', '$2y$10$3IUpyMIbDw7l43eLwdd4ReCe1pVqAauUw1/wk/6pq66a4.XRENIcK', 'student', 'male', 4, '9264515466', 'Pillado Oscar', '2025', NULL, 0, NULL, '2025-12-29 16:26:42', '2025-12-29 16:26:42'),
(63, 'HMD24020300', 'Jeremaia Robante', 'Robante35@gmail.com', '$2y$10$21ZHqiY4WCL1NOxx6qgUpeQtgm/F5Rpn077N3/N0xe7OMHEzucOSi', 'student', 'male', 4, '9265545855', 'Robante Jery', '2025', NULL, 0, NULL, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(64, 'RKD01020000', 'Kyla Marie Rolan', 'Rolankyla1@gmail.com', '$2y$10$k.K1NVzgRjvsPrxbwO3lRe9q7aAV1w9MWQEIzX2z/unp9NvPBPyjm', 'student', 'female', 4, '9461152364', 'Kyla Marie Rolan', '2025', NULL, 0, NULL, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(65, 'AES01050300', 'Elyza Aligaen', 'Aligaen@gmail.com', '$2y$10$zBTDqX3YYlSno0V.0rbd9ehUgH01ikmnOx8LpVRfN3ESFj1jQjcmi', 'student', 'female', 4, '9293564155', 'Elyza', '2025', NULL, 0, NULL, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(66, 'FPA07090300', 'Pia Julianne Fernandez', 'Fernandez18@gmail.com', '$2y$10$xwWaEZZAuVDRgFmav./8PurdCE/rLeXnPsD.g/llSXR0HCHWk0n7O', 'student', 'female', 4, '9293564166', 'Pia Fernandez', '2025', NULL, 0, NULL, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(67, 'LDB04020300', 'Diomed Latonero', 'Latonero334@gmail.com', '$2y$10$M9rPnDS6f5E0ug054neUzuqi5OSSokP2bYb0aZzgyk7O5HnWC3neC', 'student', 'male', 4, '9091642566', 'Diomed latonero', '2025', NULL, 0, NULL, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(68, 'SSC04060300', 'Sheila Solizar', 'SheilaG4@gmail.com', '$2y$10$rG9TSz9ReE2eLYX1udDSCuvyRBCqZZmpKhcQ2f4KFs7IWa2mpWrS6', 'student', 'female', 4, '9264458163', 'Sheila Solizar', '2025', NULL, 0, NULL, '2025-12-29 16:26:43', '2025-12-29 16:26:43'),
(69, 'AKR01320400', 'Karl John Ambos', 'Ambos36@gmail.com', '$2y$10$13nyT6MZcQgej2d3Clf0DeX2oVNMvG.t44DY0z3rZnSl8P7O9vtQ2', 'student', 'male', 4, '9465516859', 'Karl Ambos', '2025', NULL, 0, NULL, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(70, 'PAD04120300', 'Althea Alexis Perocho', 'Altheaxis@gmail.com', '$2y$10$2HQY7CCaIH0UBVXV2zlzI.Hx.vcD9BrKagCMzfE1kswH1.oOWYGAC', 'student', 'female', 4, '9264455186', 'Althea', '2025', NULL, 0, NULL, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(71, 'FKR0820300', 'Kyshia Francisco', 'Kryshia@gmail.com', '$2y$10$s6qcSVFMYwtQG03NfayzJ.8eSrdCTmG2AvMAgrkMd/uQBVWrV1MsS', 'student', 'female', 4, '9293615648', 'Kyshia Francisco', '2025', NULL, 0, NULL, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(72, 'NRR09270300', 'Rica Nicor', 'Ricanicor@gmail.com', '$2y$10$6sPgWQvqnXr/HDqieYGnmeljWEdfuIYhYQw3lrY/tq/Wko1Vr2cBm', 'student', 'female', 4, '9245685456', 'Rica Nicor', '2025', NULL, 0, NULL, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(73, 'LCH00614300', 'Luz Clarence', 'Clarence.11@gmail.com', '$2y$10$cNYAeEc9rawZRmnj.zMa7OkCJmZtpIS.zaSo9U7.WqjTJlMT0AzbO', 'student', 'male', 4, '9265456321', 'Luz Clarenze', '2025', NULL, 0, NULL, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(74, 'NNO0260300', 'Noeme Nasis', 'NasisNoems@gmail.com', '$2y$10$E7WjNKbjlO14SC6PTVKI3OJklECgT/1t97OnRpUgGh.w9ndhrJ52m', 'student', 'female', 4, '9123456652', 'Noeme Nasis', '2025', NULL, 0, NULL, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(75, 'DKJ12160300', 'Kim Diaz', 'Diaz46@gmail.com', '$2y$10$WMfbH1s8D90cWZ6BqsAFxe7gZQLtETfBN5FqlGvxcqY6Hebq2dprC', 'student', 'male', 4, '9468516426', 'Kim Diaz', '2025', NULL, 0, NULL, '2025-12-29 16:26:44', '2025-12-29 16:26:44'),
(76, 'LBJ01270300', 'Bryan Joseph Langa', 'Langaa@gmail.com', '$2y$10$vpAai0YGwX3t2xyOTaneF.DrGiSTv0GhdlrL1GP0eEE7LFhjwXYIa', 'student', 'female', 4, '9293561456', 'Bryan Slayer', '2025', NULL, 0, NULL, '2025-12-29 16:26:45', '2025-12-29 16:33:51'),
(77, 'HCF01140300', 'Cris Neil John Hulleza', 'Cris.exe@gmail.com', '$2y$10$DhFJmX6zbijz8u5wqJKPjOITDhojOKrmHwnD4NbEXbkI9tVp0v9Qe', 'student', 'male', 4, '9564841426', 'Cris Neil', '2025', NULL, 0, NULL, '2025-12-29 16:26:45', '2025-12-29 16:26:45'),
(78, 'CDG01310300', 'Darwin Carl Cortez', 'Cortezz@gmail.com', '$2y$10$cAyfZWCb96dPBX7B8gmop.VVN.8e2owKHNSv0RW9WQjFHu8nGr9Ea', 'student', 'male', 4, '9293366545', 'Yui Darcy', '2025', NULL, 0, NULL, '2025-12-29 16:26:45', '2025-12-29 16:26:45'),
(80, 'CDT08090100', 'Dan Vincent Canoy', 'Canoy34@gmail.com', '$2y$10$iz2f4Tj7Cl9nTLsuMwg6lOVxhe/juqmusIpGFu4iaGZtsskrbDRle', 'student', 'male', 4, '9293561645', 'Dan Canoy', '2025', NULL, 0, NULL, '2025-12-29 16:26:45', '2025-12-29 16:26:45'),
(81, 'INS10052502', 'Prof. Maria Martinez', 'test@example.com', '$2y$10$QlR1li.7/.aCVE79tB8xjOu3lBjBuPW8Gm.xuieZtelbJ.LciW4li', 'instructor', 'female', NULL, '0000000000', 'TestFB', '2025', NULL, 0, NULL, '2025-12-29 16:27:49', '2025-12-31 05:55:53'),
(82, 'INS10052503', 'Prof. Carlos Garcia', 'prof.garcia@chmsu.edu.ph', '$2y$10$jrGyqQAxYlULPQC7ymic/uXMQ5vNfCOZFQnpeNsbyKQpp2oncIK2u', 'instructor', 'male', NULL, '9123456794', 'Garcia', '2025', '../../storage/uploads/profile_pics/instructor_82_1767365465.jpg', 0, NULL, '2025-12-29 16:27:49', '2026-01-02 14:51:05'),
(83, 'INS10052504', 'Prof. Ana Rodriguez', 'prof.rodriguez@gmail.com', '$2y$10$jOzr1imq1LEd79GRuazeLukykgHOMjNjWe8k6x/wsAY0.oyzeF88y', 'instructor', 'female', NULL, '09123456795', 'Rodriguezs', '2025', '../../storage/uploads/profile_pics/instructor_83_1767160263.png', 0, NULL, '2025-12-29 16:27:49', '2025-12-31 05:52:22'),
(84, 'CMA12040300', 'Manuel Colorado', 'mzakcoloradz@gmail.com', '$2y$10$lZZLzfkJNfRn1w/Q8CbgPOpCgr5u7hRz2SqTdtbobEsGY0JNVxfe.', 'student', 'male', 5, '09509928296', 'Manuel Coloradz III', '2025', NULL, 0, NULL, '2025-12-29 17:26:13', '2025-12-31 05:37:19'),
(85, 'ALP01061200', 'Allem A. Planilla', 'Planilla@gmail.com', '$2y$10$1ttSLJ6I03FJzJchxrY7M.QguzhQC788x/vedPTVGRkUDKY9YJ/bG', 'student', 'non-binary', 5, '9451245625', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(86, 'ANC08120300', 'Angelica Joy G. Cortina', 'Angeliz.013@gmail.com', '$2y$10$.YnC9UKL.kb./4NMdg8L/OvnaNXc27sH.zI4EzZLrkBmTBIKMLnPy', 'student', 'female', 5, '9461326548', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(87, 'BRT10050300', 'Brock E. Togle', 'Brocker12@gmail.com', '$2y$10$m1csq3bUOmE4M7yt7KmaqOM8JcZ5sBcqc75NoKQQg.7WFLyxZotB.', 'student', 'male', 5, '3216458216', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(88, 'GAG06140200', 'Gabriel A. Gequillana', 'Gequillana4612@gmail.com', '$2y$10$M2O5KtgLwNewCUDVkb/lWORuxAROc21yMcL7tzBZsPipqfXWk/cNG', 'student', 'male', 5, '9246130521', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(89, 'GEA04150300', 'Gelyn D. Arquio', 'gelynarquio@gmail.com', '$2y$10$K4xd5qquaTXA9nEpmuka.uLr/aWGuhKg2SNF45wyCxjXpA63oCpRq', 'student', 'female', 5, '9263514265', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(90, 'JEM02140000', 'Jessie E. Magallanes', 'magallanesjessie12@gmail.com', '$2y$10$12ooJ0CV9oRR1SE2uWlqJOakot8qo/GaFsXhwjDn2qmkDDMEImzqW', 'student', 'female', 5, '9263514265', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(91, 'JON12270300', 'Joseph Norton F. Grecia', 'Norton23@gmail.com', '$2y$10$KVm/.HI175QzCvtfrdJDvuI1ZojDm7HSyMNKpAukV.WVefagAMi3O', 'student', 'male', 5, '9261322645', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(92, 'JEG06150200', 'Jesus Xander G. Galan', 'jxgalan1465@gmail.com', '$2y$10$CFz2yLimwXv1lA8O7IkLMexcskCIGY7W4fnWRfN8b99Xx/aA4u02a', 'student', 'male', 5, '9213316465', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(93, 'KEO10230000', 'Kert C. Odon', 'odonkert234@gmail.com', '$2y$10$Hk2Fri4Ss84N.VHz5n5ISuhjNzYeYXASi88r49HVDZ08X2u9Buwqa', 'student', 'male', 5, '9132465221', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:33', '2026-01-03 15:54:33'),
(94, 'KYP11030300', 'Kyle Christian A. Pe?alver', 'kylechristian@gmail.com', '$2y$10$UjM8qr7ro8R3LhHL6pjhk.a18R/QO.JsWhP3jz91D4/7wHXVBMbS2', 'student', 'male', 5, '9132246152', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(95, 'LYC03250300', 'Lyca Nicole C. Caparon', 'caparon25@gmail.com', '$2y$10$YjGSsDggwQU/6nV980P3Bug.8sQSUlD/4DamcYf3OT0ruMm84erX2', 'student', 'female', 5, '9241302135', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(96, 'MAO12230400', 'Maica F. Ogatis', 'ogatis32@gmail.com', '$2y$10$OxQIAtyT1QZ2JrFj1SAcEOR6Y56ovVWPXDsqpfOPF5JCBKWSL2.Aa', 'student', 'female', 5, '9316524650', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(97, 'MAH12310400', 'Mary Ann G. Hudierez', 'mary.ann.65@gmail.com', '$2y$10$ot47X4/3.mHU.OXr8l1Y..ZENK4zPXKzpWQ1f9UACnI5YG32f5Mg.', 'student', 'female', 5, '9092316420', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(98, 'MED09160300', 'Meikie F. De Leon', 'meikedeleon.34@gmail.com', '$2y$10$kCPUwYG5b88wkFdbPkxDVeJ1rwPWEMnbFTq8Qc/iNnceSOqrVYAoO', 'student', 'male', 5, '9093216426', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(99, 'NEP05020400', 'Neil E. Passion', 'passion.neil@gmail.com', '$2y$10$szMlU2hvE.pCNx2CxIBqbOKsp1jl6WImbPrri1k.vqbfN.hpmiB/S', 'student', 'male', 5, '9231652246', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(100, 'ROM10130200', 'Ronillo F. Medel Jr.', 'ronmedel@gmail.com', '$2y$10$/BYXPaC6FV835GOTqqgEre44zAfrps5bjnybj/72vg7M9KQHtMq0a', 'student', 'male', 5, '9231652460', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(101, 'SAD03150300', 'Salvador R. Dominggo', 'dominggo@gmail.com', '$2y$10$i7GGETj72FRyYNsxI/MpPO.V2TOy2txs38ZGI.PGUATxSG5d4mEPa', 'student', 'male', 5, '9003261235', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(102, 'EDT11040300', 'Edison D. Tenacio', 'tenacio67@gmail.com', '$2y$10$K5OTnlQIPH0o/wmnJ9n8UOh///dGtVPo1nI5dJwaS8Pn38mKVXIwq', 'student', 'male', 5, '9134462153', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(103, 'ZAN12160300', 'Zairyl L. Nuyad', 'nuyadzai@gmail.com', '$2y$10$WEj/4Kc6jBjaf72wMyrX/OVBid7fSNS83X.iqmsLHVA/FIp1/4cCO', 'student', 'female', 5, '9123026662', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(104, 'JOT04130300', 'Joyann L. Tingson', 'tingson10@gmail.com', '$2y$10$E03aqqkH/jPLj41/9.oCJ.2V.vsDYledMzjK2lRePX/BKWSZzMnKK', 'student', 'female', 5, '9134625662', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(105, 'AIM07120200', 'Aila D. Monterosa', 'monterosa13@gmail.com', '$2y$10$9OsL57uA5riK4PbWzXulW.rDwC8fUEKLTbGv4O3HvkUo7XzkK46R6', 'student', 'female', 5, '9213265321', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:34', '2026-01-03 15:54:34'),
(106, 'ANP08150300', 'Anna Joy Pe?averde', 'annajoy@gmail.com', '$2y$10$T3R.HWYGm6xkbZBfDm3INeRX4RjBurkoMu03NHdxpW0RjQgO7PVo2', 'student', 'female', 5, '9213461253', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(107, 'CYB12160300', 'Cyril Bajilidad', 'cyril@gmail.com', '$2y$10$k0GvvTVhBI.RbloiZN7jl.tiB4hVCx8SveaU87We.h6IQ8QikPS3y', 'student', 'male', 5, '9211124562', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(108, 'DAM12030300', 'Dancharl Mondia', 'dancharkmondia@gmail.com', '$2y$10$ShWJm100rdrGf7xzmarKIukbzEkwKw3MPchYtny.Uyn3hOxCOHW9e', 'student', 'male', 5, '9215423651', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(109, 'ELB090240200', 'Elijah F. Beronio', 'elijishberonio@gmail.com', '$2y$10$mZHDUq/m1Rab6YuGHhT9UOpp26KDIb4k6YQoutk993bi7w4eOVLyy', 'student', 'male', 5, '9546123562', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(110, 'JON11050100', 'John Mark B. Narrazo', 'jmnazarro@gmail.com', '$2y$10$wksoZ6e7rR7eURG1Vqhteu2.s6l8mD5eIWBOTIxTZCDngWxD1VdRC', 'student', 'male', 5, '9213465125', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(111, 'JOS10230300', 'Joash B. Sadiang-abay', 'joashjoash@gmail.com', '$2y$10$nhkN48.OPV6x8d5nKIChm.f4vMzVfC760d94wqoz9iMHdiOoCzT1K', 'student', 'male', 5, '9213546215', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(112, 'JOB10020200', 'John D. Blance', 'blancii.23@gmail.com', '$2y$10$xqv76bpREangdPTTGupyhOEf5hrzip1wHN19R3S.LEw0L2ijENEiW', 'student', 'male', 5, '9215462316', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(113, 'JOM11060300', 'Joseph E. Musni', 'musnijoseph123@gmail.com', '$2y$10$DwxLqRhDl5oUDzon6fwQ6.kOfR1Gpqr59fn0uDHQ4uIOm8Z28Nt2S', 'student', 'male', 5, '9295461325', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(114, 'JOG05120200', 'Joshua A. Gabriel', 'gabrieljoshuaa@gmail.com', '$2y$10$oVqTLjqWl0qogcXxxUvL2ue9kJ8qYUbDcl417z6U3zSSsdswEhowy', 'student', 'male', 5, '9213465125', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(115, 'VPJ10130300', 'Vince Philippe A. Judilla', 'judilla@gmail.com', '$2y$10$Zbqz/SVHSg0YUl3y1rF4nudPpwmRrEkGRTFUiN/9uLi6nn9.X.SaK', 'student', 'male', 5, '9421154626', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(116, 'ROJ04200200', 'Rowell A. Jaranilla', 'rowelljaranilla@gmail.com', '$2y$10$VghXAkADYVRZ78YsICSXauvWkcgYoijJ7nTRhSkHSVu6NGBjI8mTS', 'student', 'male', 5, '9213465125', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35'),
(117, 'SHS03150200', 'Sharlie D. Soriano', 'soriano@gmail.com', '$2y$10$vPdqIR24MxuiVYMXX7mFlu18D2xve511yhIk/bYqs2yDX1qVOAHFm', 'student', 'male', 5, '9213465213', NULL, '2025', NULL, 0, NULL, '2026-01-03 15:54:35', '2026-01-03 15:54:35');

-- --------------------------------------------------------

--
-- Table structure for table `workplace_change_requests`
--

CREATE TABLE `workplace_change_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(11) UNSIGNED NOT NULL,
  `workplace_name` varchar(255) NOT NULL,
  `workplace_address` text NOT NULL,
  `position_title` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `change_reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `workplace_change_requests`
--

INSERT INTO `workplace_change_requests` (`id`, `student_id`, `workplace_name`, `workplace_address`, `position_title`, `supervisor_name`, `latitude`, `longitude`, `change_reason`, `status`, `created_at`, `updated_at`, `reviewed_by`, `reviewed_at`) VALUES
(5, 56, 'ComTech Inc', 'Brgy sample street bacolod city', 'Technical', 'Mr. Ernesto Dela Cruz', 10.66265558, 123.03729917, 'lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet lorem ipsum dolor sit amet', 'pending', '2026-01-03 10:00:04', '2026-01-03 10:50:44', 83, '2026-01-03 10:19:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`attendance_date`,`block_type`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_attendance_date` (`attendance_date`),
  ADD KEY `idx_block_type` (`block_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_time_in` (`time_in`),
  ADD KEY `idx_attendance_student_date` (`student_id`,`attendance_date`),
  ADD KEY `idx_request_status` (`request_status`);

--
-- Indexes for table `document_submissions`
--
ALTER TABLE `document_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_document_type_id` (`document_type_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reviewer_instructor_id` (`reviewer_instructor_id`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `idx_document_student_type` (`student_id`,`document_type_id`),
  ADD KEY `idx_document_student_status` (`student_id`,`status`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_code_per_instructor` (`code`,`instructor_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_is_pre_required` (`is_pre_required`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_document_types_instructor_id` (`instructor_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_recipient_scope` (`recipient_scope`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `excused_dates`
--
ALTER TABLE `excused_dates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_date` (`student_id`,`excused_date`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_excused_date` (`excused_date`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_date_student` (`excused_date`,`student_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_id` (`user_id`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `ojt_summaries`
--
ALTER TABLE `ojt_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_id` (`student_id`),
  ADD KEY `idx_adjusted_by_instructor_id` (`adjusted_by_instructor_id`),
  ADD KEY `idx_last_updated` (`last_updated`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section` (`section_code`,`department`,`year`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `fk_sections_instructor` (`instructor_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_id` (`user_id`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `student_workplaces`
--
ALTER TABLE `student_workplaces`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_start_date` (`start_date`);

--
-- Indexes for table `timeout_exceptions`
--
ALTER TABLE `timeout_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_id` (`attendance_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_instructor_id` (`instructor_id`),
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `idx_timeout_student_status` (`student_id`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_id` (`school_id`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_section_id` (`section_id`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_is_archived` (`is_archived`);

--
-- Indexes for table `workplace_change_requests`
--
ALTER TABLE `workplace_change_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `document_submissions`
--
ALTER TABLE `document_submissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `excused_dates`
--
ALTER TABLE `excused_dates`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ojt_summaries`
--
ALTER TABLE `ojt_summaries`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `student_workplaces`
--
ALTER TABLE `student_workplaces`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `timeout_exceptions`
--
ALTER TABLE `timeout_exceptions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `workplace_change_requests`
--
ALTER TABLE `workplace_change_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `fk_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `document_submissions`
--
ALTER TABLE `document_submissions`
  ADD CONSTRAINT `fk_submissions_document_type` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_submissions_instructor` FOREIGN KEY (`reviewer_instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `document_types`
--
ALTER TABLE `document_types`
  ADD CONSTRAINT `fk_document_types_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `fk_email_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_email_logs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `excused_dates`
--
ALTER TABLE `excused_dates`
  ADD CONSTRAINT `fk_excused_dates_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_excused_dates_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `instructors`
--
ALTER TABLE `instructors`
  ADD CONSTRAINT `fk_instructors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ojt_summaries`
--
ALTER TABLE `ojt_summaries`
  ADD CONSTRAINT `fk_summaries_instructor` FOREIGN KEY (`adjusted_by_instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_summaries_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `fk_sections_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_workplaces`
--
ALTER TABLE `student_workplaces`
  ADD CONSTRAINT `fk_workplaces_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `timeout_exceptions`
--
ALTER TABLE `timeout_exceptions`
  ADD CONSTRAINT `fk_timeout_attendance` FOREIGN KEY (`attendance_id`) REFERENCES `attendance_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_timeout_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_timeout_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `workplace_change_requests`
--
ALTER TABLE `workplace_change_requests`
  ADD CONSTRAINT `workplace_change_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
