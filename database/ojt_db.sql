-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 25, 2026 at 09:56 AM
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
  `accuracyQualityPoints` decimal(5,2) DEFAULT NULL,
  `professionalPresentationPoints` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(56, 'Endorsement', 'Endorsement', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-05 17:00:44', '2026-01-05 17:00:44', '0000-00-00', 2),
(57, 'Application letter', 'Application Letter', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:19:52', '2026-01-06 05:19:52', '0000-00-00', 2),
(60, 'resume', 'resume', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:29:00', '2026-01-06 05:29:00', '0000-00-00', 2),
(61, 'certificate of attendance', 'Cert of attendance', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:30:18', '2026-01-06 05:30:18', '0000-00-00', 2),
(62, 'Medical Certificate', 'Med Cert', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:30:44', '2026-01-06 05:30:44', '0000-00-00', 2),
(64, 'Pledge of good conduct', 'Pledge of good conduct', 'pre_required', 1, 1, 'once', NULL, '../../storage/uploads/templates/template_64_1767677842.pdf', 1, '2026-01-06 05:31:34', '2026-01-06 05:37:22', '0000-00-00', 2),
(65, 'Misdemeanor Penalty Policy', 'Misdemeanor Penalty Policy', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:32:10', '2026-01-06 05:32:10', '0000-00-00', 2),
(67, 'parent consent', 'parent consent', 'pre_required', 1, 1, 'once', NULL, NULL, 1, '2026-01-06 05:39:51', '2026-01-06 05:39:51', '0000-00-00', 2),
(70, 'docs', 'docs', 'other', 0, 1, 'once', NULL, NULL, 1, '2026-01-07 02:48:11', '2026-01-07 02:48:11', '0000-00-00', 3),
(71, 'February Montly Record', 'Feb Montly Record', 'other', 0, 1, 'once', NULL, NULL, 1, '2026-01-07 17:00:14', '2026-01-07 17:00:14', '0000-00-00', 3),
(72, 's', 's', 'other', 0, 1, 'once', NULL, NULL, 1, '2026-01-07 17:06:36', '2026-01-07 17:06:36', '0000-00-00', 3);

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
(81, 'INS10052502', 'instructor1', 'test@example.com', '$2y$10$QlR1li.7/.aCVE79tB8xjOu3lBjBuPW8Gm.xuieZtelbJ.LciW4li', 'instructor', 'female', NULL, '0000000000', 'TestFB', '2025', NULL, 0, NULL, '2025-12-29 16:27:49', '2026-01-25 08:54:38'),
(82, 'INS10052503', 'instructor2 ', 'prof.garcia@chmsu.edu.ph', '$2y$10$jrGyqQAxYlULPQC7ymic/uXMQ5vNfCOZFQnpeNsbyKQpp2oncIK2u', 'instructor', 'male', NULL, '9123456794', 'Garcia', '2025', '../../storage/uploads/profile_pics/instructor_82_1767365465.jpg', 0, NULL, '2025-12-29 16:27:49', '2026-01-25 08:55:22'),
(83, 'INS10052504', 'instructor3', 'prof.rodriguez@gmail.com', '$2y$10$jOzr1imq1LEd79GRuazeLukykgHOMjNjWe8k6x/wsAY0.oyzeF88y', 'instructor', 'female', NULL, '09123456795', 'Rodriguezs', '2025', '../../storage/uploads/profile_pics/instructor_83_1767160263.png', 0, NULL, '2025-12-29 16:27:49', '2026-01-25 08:55:37'),
(118, 'ADM10052501', 'System admin 1', 'admin1@gmail.com', '$2y$10$bzU4tbhEju9tx9vtrR8xmeyWzRG8nFvs/L7RHq/YvXRkmDO3DZLNO', 'admin', 'male', NULL, '09461255468', NULL, '2025', 'storage/uploads/profiles/admin_118_1767714910.png', 0, NULL, '2026-01-06 14:49:07', '2026-01-25 08:54:14'),
(119, 'ADM10052500', 'System admin 2', 'admin2@gmail.com', '$2y$10$tStrbB/B7lw6izR6u9egwONP5V16A15nmjfcUMdIKdTC/XRZct252', 'admin', 'male', NULL, '09132465152', 'Manuel Coloradz III', '2025', NULL, 0, NULL, '2026-01-06 15:54:31', '2026-01-25 08:54:05');

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
  ADD KEY `idx_request_status` (`request_status`),
  ADD KEY `idx_attendance_student_date_block` (`student_id`,`attendance_date`,`block_type`),
  ADD KEY `idx_attendance_date_block_status` (`attendance_date`,`block_type`,`status`),
  ADD KEY `idx_attendance_created_at` (`created_at`);

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
  ADD KEY `idx_last_updated` (`last_updated`),
  ADD KEY `idx_ojt_summaries_student` (`student_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section` (`section_code`,`department`,`year`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_sections_instructor` (`instructor_id`);

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
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_student_workplaces_student_active` (`student_id`,`is_active`);

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
  ADD KEY `idx_is_archived` (`is_archived`),
  ADD KEY `idx_users_section_role` (`section_id`,`role`,`is_archived`);

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
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `document_submissions`
--
ALTER TABLE `document_submissions`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `excused_dates`
--
ALTER TABLE `excused_dates`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ojt_summaries`
--
ALTER TABLE `ojt_summaries`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

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
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `workplace_change_requests`
--
ALTER TABLE `workplace_change_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
