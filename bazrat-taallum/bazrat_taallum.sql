-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 10:12 PM
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
-- Database: `bazrat_taallum`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_logs`
--

CREATE TABLE `admin_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user_id` int(11) NOT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` int(11) NOT NULL DEFAULT 0,
  `meta_json` mediumtext DEFAULT NULL,
  `ip_address` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_login_attempts`
--

CREATE TABLE `auth_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(190) NOT NULL,
  `ip_address` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_login_attempts`
--

INSERT INTO `auth_login_attempts` (`id`, `email`, `ip_address`, `created_at`) VALUES
(1, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04'),
(2, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04'),
(3, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04'),
(4, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04'),
(5, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04'),
(6, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04'),
(7, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04'),
(8, 'e2e_20260406165701@example.com', '::1', '2026-04-06 16:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name_ar` varchar(120) NOT NULL,
  `name_en` varchar(120) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name_ar`, `name_en`, `created_at`) VALUES
(1, 'البرمجة', 'Programming', '2026-03-29 22:22:21'),
(2, 'الذكاء الاصطناعي', 'Artificial Intelligence', '2026-03-29 22:22:21'),
(3, 'الأعمال', 'Business', '2026-03-29 22:22:21'),
(4, 'الأمن السيبراني', 'Cybersecurity', '2026-04-06 14:13:02'),
(5, 'تحليل البيانات', 'Data Analysis', '2026-04-06 14:13:02'),
(6, 'الروبوتات', 'Robotics', '2026-04-06 14:13:02'),
(7, 'التسويق الإلكتروني', 'Digital Marketing', '2026-04-06 14:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `certificate_code` varchar(120) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `challenge_submissions`
--

CREATE TABLE `challenge_submissions` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_url` varchar(700) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `is_active`, `expires_at`, `created_at`) VALUES
(1, 'SEED10', 'percent', 10.00, 1, NULL, '2026-03-29 22:22:21'),
(2, 'WELCOME5', 'fixed', 5.00, 1, NULL, '2026-03-29 22:22:21'),
(3, 'yassin', '', 20.00, 1, NULL, '2026-03-30 00:52:27'),
(6, 'yassin0', '', 10.00, 1, '2026-03-31 17:53:00', '2026-03-30 00:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `track_id` int(11) DEFAULT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `description_ar` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `age_group` enum('8-12','13-15','16-18') DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `course_discount_type` enum('none','percent','fixed') NOT NULL DEFAULT 'none',
  `course_discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `category_id`, `track_id`, `title_ar`, `title_en`, `description_ar`, `description_en`, `thumbnail`, `instructor_id`, `level`, `age_group`, `price`, `is_free`, `course_discount_type`, `course_discount_value`, `is_published`, `created_at`) VALUES
(1, 1, NULL, 'مدخل إلى تطوير الويب', 'Introduction to Web Development', 'تعلم أساسيات HTML و CSS و JavaScript.', 'Learn HTML, CSS, and JavaScript basics.', 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6', 2, 'advanced', NULL, 49.99, 0, 'fixed', 10.00, 1, '2026-03-29 22:22:21'),
(2, 2, NULL, 'مقدمة في تعلم الآلة', 'Machine Learning Fundamentals', 'تعلم أساسيات تعلم الآلة وتطبيقاته.', 'Learn core machine learning concepts and applications.', 'https://images.unsplash.com/photo-1677442136019-21780ecad995', 2, 'advanced', NULL, 79.99, 0, 'none', 0.00, 1, '2026-03-29 22:22:21'),
(4, 1, 5, 'سكراتش للمبتدئين', 'Scratch for Beginners', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'beginner', '8-12', 0.00, 1, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(5, 1, 5, 'بايثون للمراهقين', 'Python for Teens', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'intermediate', '13-15', 29.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(6, 1, 5, 'تطوير الويب الكامل', 'Full Web Development', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'advanced', '16-18', 49.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(7, 2, 1, 'مدخل الذكاء الاصطناعي', 'AI Foundations', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'intermediate', '13-15', 39.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(8, 2, 1, 'تعلم الآلة العملي', 'Applied Machine Learning', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'advanced', '16-18', 59.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(9, 4, 2, 'أمن الإنترنت للمبتدئين', 'Internet Safety Basics', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'beginner', '8-12', 0.00, 1, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(10, 4, 2, 'أساسيات الأمن السيبراني', 'Cybersecurity Fundamentals', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'intermediate', '13-15', 35.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(11, 5, 4, 'تحليل البيانات بالبايثون', 'Data Analysis with Python', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'advanced', '16-18', 45.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(12, 6, 3, 'الروبوتات للصغار', 'Robotics for Kids', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'beginner', '8-12', 25.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(13, 6, 3, 'Arduino وإنترنت الأشياء', 'Arduino and IoT', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'intermediate', '13-15', 35.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(14, 7, 6, 'التسويق الرقمي للشباب', 'Digital Marketing for Youth', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'intermediate', '13-15', 30.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02'),
(15, 7, 6, 'استراتيجية نمو المشاريع الرقمية', 'Digital Growth Strategy', 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.', 'A hands-on course aligned with the project roadmap, including weekly practical activities.', NULL, NULL, 'advanced', '16-18', 42.00, 0, 'none', 0.00, 1, '2026-04-06 14:13:02');

-- --------------------------------------------------------

--
-- Table structure for table `course_projects`
--

CREATE TABLE `course_projects` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `description_ar` mediumtext DEFAULT NULL,
  `description_en` mediumtext DEFAULT NULL,
  `rubric_json` mediumtext DEFAULT NULL,
  `pass_score` int(11) NOT NULL DEFAULT 60,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_projects`
--

INSERT INTO `course_projects` (`id`, `course_id`, `title_ar`, `title_en`, `description_ar`, `description_en`, `rubric_json`, `pass_score`, `is_active`, `created_at`) VALUES
(1, 4, 'أساسيات تصميم المواقع', '', '', '', '', 60, 1, '2026-04-06 14:44:13');

-- --------------------------------------------------------

--
-- Table structure for table `course_quizzes`
--

CREATE TABLE `course_quizzes` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `questions_json` longtext NOT NULL,
  `pass_score` int(11) NOT NULL DEFAULT 70,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `progress` int(11) DEFAULT 0,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `progress`, `status`, `enrolled_at`) VALUES
(2, 6, 1, 0, 'active', '2026-03-30 01:03:48'),
(3, 4, 2, 100, 'active', '2026-03-30 12:52:25'),
(4, 5, 1, 100, 'cancelled', '2026-04-03 01:07:46'),
(5, 4, 1, 100, 'active', '2026-04-03 01:11:17'),
(6, 5, 2, 100, 'active', '2026-04-04 12:31:36'),
(8, 5, 4, 0, 'active', '2026-04-06 14:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `learning_tracks`
--

CREATE TABLE `learning_tracks` (
  `id` int(11) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `name_ar` varchar(160) NOT NULL,
  `name_en` varchar(160) NOT NULL,
  `description_ar` text DEFAULT NULL,
  `description_en` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_tracks`
--

INSERT INTO `learning_tracks` (`id`, `slug`, `name_ar`, `name_en`, `description_ar`, `description_en`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'ai-ml', 'الذكاء الاصطناعي وتعلم الآلة', 'AI & Machine Learning', NULL, NULL, 10, 1, '2026-04-06 14:12:46'),
(2, 'cyber-security', 'الأمن السيبراني', 'Cyber Security', NULL, NULL, 20, 1, '2026-04-06 14:12:46'),
(3, 'robotics-iot', 'الروبوتات وإنترنت الأشياء', 'Robotics & IoT', NULL, NULL, 30, 1, '2026-04-06 14:12:46'),
(4, 'data-analytics', 'تحليل البيانات', 'Data Analytics', NULL, NULL, 40, 1, '2026-04-06 14:12:46'),
(5, 'programming-dev', 'البرمجة والتطوير', 'Programming & Development', NULL, NULL, 50, 1, '2026-04-06 14:12:46'),
(6, 'digital-marketing', 'التسويق الإلكتروني', 'Digital Marketing', NULL, NULL, 60, 1, '2026-04-06 14:12:46');

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) NOT NULL,
  `content_ar` mediumtext DEFAULT NULL,
  `content_en` mediumtext DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `duration` varchar(32) DEFAULT NULL,
  `timed_transcript_json` mediumtext DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`id`, `course_id`, `title_ar`, `title_en`, `content_ar`, `content_en`, `video_url`, `duration`, `timed_transcript_json`, `sort_order`, `created_at`) VALUES
(1, 1, 'مقدمة', 'Introduction', 'هذه مقدمة الدورة', 'Course introduction', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', '7 minute', '', 1, '2026-03-29 22:22:21'),
(2, 1, 'أساسيات HTML', 'HTML Basics', 'تعلم هيكل صفحة الويب', 'Learn webpage structure', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', NULL, NULL, 2, '2026-03-29 22:22:21'),
(3, 2, 'ما هو تعلم الآلة؟', 'What is ML?', 'مفاهيم أساسية في تعلم الآلة', 'Basic ML concepts', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', NULL, NULL, 1, '2026-03-29 22:22:21'),
(4, 1, 'إتقان جافاسكريبت', 'learning Javascript', 'مقدّمة لجافا سكريبت\r\nدعونا نرى ما يميز جافا سكريبت ، وما يمكننا تحقيقه بها ، وأي التقنيات الأخرى التي تعمل بشكل جيد معها.\r\n\r\nماهي جافا سكريبت؟\r\nجافا سكريبت تم إنشائها في بادئ الأمر “لجعل الصفحات الإلكترونية حية”.\r\n\r\nالبرامج في هذه اللغة تسمّي سكريبتات. يمكن كتابتها مباشرة في الصفحات الإلكترونية HTML و سوف يتم تفعيلها آليا عند تحميل الصفحة.\r\n\r\nتتوفر السكريبتات وتنفذ كنص عادي. لا تحتاج الى تحضير خاص أو تحويل برمجي لتشتغل.\r\n\r\nفي هذا الجانب ، تختلف جافا سكريبت اختلافًا كبيرًا عن لغة أخرى تسمى جافا', 'An Introduction to JavaScript\r\nLet’s see what’s so special about JavaScript, what we can achieve with it, and what other technologies play well with it.\r\n\r\nWhat is JavaScript?\r\nJavaScript was initially created to “make web pages alive”.\r\n\r\nThe programs in this language are called scripts. They can be written right in a web page’s HTML and run automatically as the page loads.\r\n\r\nScripts are provided and executed as plain text. They don’t need special preparation or compilation to run.\r\n\r\nIn this aspect, JavaScript is very different from another language called Java.', '', '1h', '', 3, '2026-04-04 12:18:44'),
(5, 1, 'الروبوتات والاتمته', 'Roboticsi', '', '', 'http://localhost/learning-seeds2/bazrat-taallum/assets/video/python.MP4', '', '', 4, '2026-04-04 17:06:12'),
(6, 4, 'المقدمه', '', '', '', 'http://localhost/learning-seeds2/bazrat-taallum/assets/uploads/videos/20260406_191322_7e9062a3.mp4', '3.54m', '', 1, '2026-04-06 16:13:22');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_completions`
--

CREATE TABLE `lesson_completions` (
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lesson_completions`
--

INSERT INTO `lesson_completions` (`user_id`, `lesson_id`, `course_id`, `completed_at`) VALUES
(4, 1, 1, '2026-04-04 11:57:49'),
(4, 2, 1, '2026-04-04 13:38:57'),
(4, 3, 2, '2026-04-04 13:38:23'),
(4, 4, 1, '2026-04-04 13:40:04'),
(4, 5, 1, '2026-04-05 12:56:03'),
(5, 1, 1, '2026-04-06 13:13:02'),
(5, 2, 1, '2026-04-06 13:13:20'),
(5, 3, 2, '2026-04-06 13:11:32'),
(5, 4, 1, '2026-04-06 13:13:30'),
(5, 5, 1, '2026-04-06 13:13:46'),
(6, 1, 1, '2026-04-04 17:07:12'),
(6, 2, 1, '2026-04-04 17:07:18'),
(6, 4, 1, '2026-04-04 17:07:23'),
(6, 5, 1, '2026-04-04 17:07:38');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','paid','failed','cancelled','refunded') DEFAULT 'pending',
  `coupon_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `subtotal`, `discount`, `total`, `status`, `coupon_id`, `created_at`) VALUES
(1, 4, 59.99, 0.00, 59.99, 'pending', NULL, '2026-03-29 22:25:47'),
(2, 6, 49.99, 20.00, 29.99, 'failed', 3, '2026-03-30 01:03:48'),
(3, 4, 79.99, 10.00, 69.99, 'paid', 6, '2026-03-30 12:52:25'),
(4, 5, 49.99, 0.00, 49.99, 'cancelled', NULL, '2026-04-03 01:07:46'),
(5, 4, 49.99, 10.00, 39.99, 'paid', 6, '2026-04-03 01:11:17'),
(6, 5, 79.99, 10.00, 69.99, 'paid', 6, '2026-04-04 12:31:36'),
(7, 5, 39.99, 10.00, 29.99, 'cancelled', 6, '2026-04-06 13:12:55');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `course_id`, `price`) VALUES
(2, 2, 1, 49.99),
(3, 3, 2, 79.99),
(4, 4, 1, 49.99),
(5, 5, 1, 49.99),
(6, 6, 2, 79.99),
(7, 7, 1, 39.99);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `gateway` varchar(60) DEFAULT 'sandbox',
  `transaction_ref` varchar(120) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed','cancelled','refunded') DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `gateway`, `transaction_ref`, `amount`, `status`, `paid_at`, `created_at`) VALUES
(1, 1, 'sandbox', 'SBX-48DAB2EE', 59.99, 'success', '2026-03-29 15:25:47', '2026-03-29 22:25:47'),
(2, 2, 'sandbox', 'SBX-E0B347C3', 29.99, 'success', '2026-03-29 18:03:48', '2026-03-30 01:03:48'),
(3, 3, 'sandbox', 'SBX-CCB525D5', 69.99, 'success', '2026-03-30 05:52:25', '2026-03-30 12:52:25'),
(4, 4, 'sandbox', 'SBX-BCDE7C67', 49.99, 'success', '2026-04-02 18:07:46', '2026-04-03 01:07:46'),
(5, 5, 'sandbox', 'SBX-F705E861', 39.99, 'success', '2026-04-02 18:11:17', '2026-04-03 01:11:17'),
(6, 6, 'sandbox', 'SBX-81298ECC', 69.99, 'success', '2026-04-04 05:31:36', '2026-04-04 12:31:36'),
(7, 7, 'sandbox', 'SBX-1AB4BD2C', 29.99, 'success', '2026-04-06 06:12:55', '2026-04-06 13:12:55');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0,
  `answers_json` longtext DEFAULT NULL,
  `status` enum('passed','failed') NOT NULL DEFAULT 'failed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment_ar` text DEFAULT NULL,
  `comment_en` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'platform_name_ar', 'بذرة تعلم', '2026-03-29 22:22:21'),
(2, 'platform_name_en', 'Learning Seeds', '2026-03-29 22:22:21'),
(3, 'platform_logo', 'assets/images/logo.png', '2026-03-29 22:22:21'),
(4, 'currency', 'USD', '2026-03-29 22:22:21'),
(5, 'default_lang', 'ar', '2026-03-29 22:22:21'),
(10, 'service_fee_percent', '0', '2026-04-01 09:21:37'),
(11, 'smtp_host', 'smtp.gmail.com', '2026-04-04 13:54:05'),
(12, 'notification_email', 'info@learning-seeds.org', '2026-04-01 09:21:37'),
(16, 'email_tagline', 'منصة تعليم إلكتروني', '2026-04-04 13:54:04'),
(17, 'email_sig_team', 'فريق الدعم', '2026-04-04 13:54:05'),
(18, 'email_sig_admin', 'info@learmin-seeds.org', '2026-04-04 13:54:05'),
(19, 'email_sig_phone', '+249 11 0900 994', '2026-04-04 13:54:05'),
(20, 'email_sig_email', 'info@learmin-seeds.org', '2026-04-04 13:54:05'),
(21, 'email_verify_subject_ar', 'مرحبًا {{user_name}}،  نود إعلامك بأنه تم تسجيلك بنجاح في منصة **{{site_name}}** باستخدام البيانات التالية:  اسم المستخدم: {{email}} كلمة المرور: {{temp_password}}  يمكنك الآن تسجيل الدخول إلى حسابك من خلال الرابط التالي: [رابط المنصة]  شكرًا لانضمامك إلينا، ونتمنى لك تجربة تعليمية مميزة.  في حال واجهت أي مشكلة، لا تتردد في التواصل معنا عبر: [البريد الإلكتروني للدعم] [رقم الهاتف - إن وجد]  مع أطيب التحيات، فريق الدعم الفني {{site_name}}  ---  **ملاحظة:** هذه رسالة تلقائية، يرجى عدم الرد عليها. ', '2026-04-04 14:43:58'),
(22, 'email_verify_subject_en', '', '2026-04-04 13:54:05'),
(23, 'email_verify_body_html_ar', '<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0;background:#f4f4f5;font-family:Tahoma,Arial,sans-serif;font-size:15px;color:#333;line-height:1.6;\">\r\n<tr><td align=\"center\" style=\"padding:24px 12px;\">\r\n<table role=\"presentation\" width=\"600\" style=\"max-width:600px;width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;\">\r\n<tr><td style=\"padding:20px 24px;border-bottom:1px solid #e5e7eb;text-align:right;direction:rtl;\">\r\n{{logo_html}}\r\n<div style=\"font-size:12px;color:#6b7280;margin-top:8px;\">{{tagline}}</div>\r\n</td></tr>\r\n<tr><td style=\"padding:24px;text-align:right;direction:rtl;\">\r\n<p style=\"margin:0 0 16px;\">عزيزي/عزيزتي <strong>{{user_name}}</strong>،</p>\r\n<p style=\"margin:0 0 16px;\">تم تسجيلك في <strong>{{site_name}}</strong> بالبيانات التالية:</p>\r\n<table role=\"presentation\" style=\"width:100%;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin:16px 0;\">\r\n<tr><td style=\"padding:4px 0;\"><strong>البريد (اسم المستخدم):</strong></td></tr>\r\n<tr><td style=\"padding:4px 0;word-break:break-all;\">{{email}}</td></tr>\r\n<tr><td style=\"padding:12px 0 4px;\"><strong>كلمة المرور المؤقتة:</strong></td></tr>\r\n<tr><td style=\"padding:4px 0;\"><code style=\"background:#fff;padding:4px 8px;border-radius:4px;\">{{temp_password}}</code></td></tr>\r\n<tr><td style=\"padding:12px 0 4px;\"><strong>رمز التحقق:</strong></td></tr>\r\n<tr><td style=\"padding:4px 0;font-size:22px;font-weight:700;letter-spacing:4px;color:#111;\">{{verification_code}}</td></tr>\r\n</table>\r\n<p style=\"margin:0 0 16px;\">شكراً لتسجيلك في <strong>{{site_name}}</strong>.</p>\r\n<p style=\"margin:0 0 8px;\">رابط المنصة:</p>\r\n<p style=\"margin:0 0 20px;\"><a href=\"{{site_url}}\" style=\"color:#2563eb;\">{{site_url}}</a></p>\r\n<p style=\"margin:0 0 20px;color:#6b7280;font-size:14px;\">في حال واجهت مشكلة، تواصل معنا.</p>\r\n<p style=\"margin:0;\">مع التحية،<br><strong>{{sig_team}}</strong><br>{{sig_admin}}<br>\r\nهاتف: {{sig_phone}}<br>بريد الدعم: <a href=\"mailto:{{sig_email}}\" style=\"color:#2563eb;\">{{sig_email}}</a></p>\r\n</td></tr>\r\n<tr><td style=\"padding:16px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;direction:rtl;\">\r\nهذه رسالة تلقائية، يرجى عدم الرد عليها.\r\n</td></tr>\r\n</table>\r\n<p style=\"max-width:600px;margin:16px auto 0;font-size:11px;color:#9ca3af;text-align:center;\">{{footer_powered}}</p>\r\n</td></tr>\r\n</table>', '2026-04-04 13:54:05'),
(24, 'email_verify_body_html_en', '<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#333;line-height:1.6;\">\r\n<tr><td align=\"center\" style=\"padding:24px 12px;\">\r\n<table role=\"presentation\" width=\"600\" style=\"max-width:600px;width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;overflow:hidden;\">\r\n<tr><td style=\"padding:20px 24px;border-bottom:1px solid #e5e7eb;text-align:left;\">\r\n{{logo_html}}\r\n<div style=\"font-size:12px;color:#6b7280;margin-top:8px;\">{{tagline}}</div>\r\n</td></tr>\r\n<tr><td style=\"padding:24px;text-align:left;\">\r\n<p style=\"margin:0 0 16px;\">Dear <strong>{{user_name}}</strong>,</p>\r\n<p style=\"margin:0 0 16px;\">You are registered on <strong>{{site_name}}</strong> with the following details:</p>\r\n<table role=\"presentation\" style=\"width:100%;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin:16px 0;\">\r\n<tr><td style=\"padding:4px 0;\"><strong>Username (email):</strong></td></tr>\r\n<tr><td style=\"padding:4px 0;word-break:break-all;\">{{email}}</td></tr>\r\n<tr><td style=\"padding:12px 0 4px;\"><strong>Temporary password:</strong></td></tr>\r\n<tr><td style=\"padding:4px 0;\"><code style=\"background:#fff;padding:4px 8px;border-radius:4px;\">{{temp_password}}</code></td></tr>\r\n<tr><td style=\"padding:12px 0 4px;\"><strong>Verification code:</strong></td></tr>\r\n<tr><td style=\"padding:4px 0;font-size:22px;font-weight:700;letter-spacing:4px;color:#111;\">{{verification_code}}</td></tr>\r\n</table>\r\n<p style=\"margin:0 0 16px;\">Thanks for registering on <strong>{{site_name}}</strong>.</p>\r\n<p style=\"margin:0 0 8px;\">The address of <strong>{{site_name}}</strong> is:</p>\r\n<p style=\"margin:0 0 20px;\"><a href=\"{{site_url}}\" style=\"color:#2563eb;\">{{site_url}}</a></p>\r\n<p style=\"margin:0 0 20px;color:#6b7280;font-size:14px;\">In case of trouble, contact us.</p>\r\n<p style=\"margin:0;\">Sincerely,<br><strong>{{sig_team}}</strong><br>{{sig_admin}}<br>\r\nT. {{sig_phone}}<br>e-mail: <a href=\"mailto:{{sig_email}}\" style=\"color:#2563eb;\">{{sig_email}}</a></p>\r\n</td></tr>\r\n<tr><td style=\"padding:16px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;\">\r\nThis is an automatic email message. Please do not reply to it.\r\n</td></tr>\r\n</table>\r\n<p style=\"max-width:600px;margin:16px auto 0;font-size:11px;color:#9ca3af;text-align:center;\">{{footer_powered}}</p>\r\n</td></tr>\r\n</table>', '2026-04-04 13:54:05'),
(26, 'smtp_port', '587', '2026-04-04 13:54:05'),
(27, 'smtp_encryption', 'tls', '2026-04-04 13:54:05'),
(28, 'smtp_user', '', '2026-04-04 13:54:05'),
(29, 'mail_from_email', 'info@learmin-seeds.org', '2026-04-04 13:54:05'),
(30, 'mail_from_name', 'learning-seedsg', '2026-04-04 13:54:05'),
(34, 'smtp_verify_peer', '1', '2026-04-04 13:54:05'),
(35, 'allow_php_mail_fallback', '0', '2026-04-04 13:54:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('student','instructor','admin') NOT NULL DEFAULT 'student',
  `language_pref` enum('ar','en') DEFAULT 'ar',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `birth_year` int(11) DEFAULT NULL,
  `birth_month` int(11) DEFAULT NULL,
  `phone_country_code` varchar(16) DEFAULT NULL,
  `phone_number` varchar(64) DEFAULT NULL,
  `whatsapp` varchar(64) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `state` varchar(120) DEFAULT NULL,
  `parent_phone` varchar(64) DEFAULT NULL,
  `parent_name` varchar(120) DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_code` varchar(32) DEFAULT NULL,
  `temp_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `language_pref`, `is_active`, `created_at`, `birth_year`, `birth_month`, `phone_country_code`, `phone_number`, `whatsapp`, `city`, `state`, `parent_phone`, `parent_name`, `email_verified`, `verification_code`, `temp_password`) VALUES
(1, 'مدير النظام', 'admin@seed-learning.com', '$2y$10$wW9E5rV7M8L2a0mX8QfReeQf7o0sQy2x2S4rJr0K4rN5xXqS9VY6m', 'admin', 'ar', 1, '2026-03-29 22:22:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(2, 'أحمد المدرب', 'instructor@seed-learning.com', '$2y$10$wW9E5rV7M8L2a0mX8QfReeQf7o0sQy2x2S4rJr0K4rN5xXqS9VY6m', 'instructor', 'ar', 1, '2026-03-29 22:22:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(3, 'طالب تجريبي', 'student@seed-learning.com', '$2y$10$wW9E5rV7M8L2a0mX8QfReeQf7o0sQy2x2S4rJr0K4rN5xXqS9VY6m', 'student', 'ar', 1, '2026-03-29 22:22:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(4, 'yasin', 'yasser@gmail.com', '$2y$10$8moqozHO1CtyWqmWoIGuoOAiqUgAtuYlmvM5kFPAyGB2yESHeHQWq', 'student', 'en', 1, '2026-03-29 22:24:51', NULL, NULL, '+249', '110900994', '961985426', 'port sudan', 'red see', '911356575', 'مبارك', 0, NULL, NULL),
(5, 'أدمن رئيسي', 'admin@bazrat-taallum.com', '$2y$10$mszLHy/5CL92IBSbYwAlZetUc4ucLiqxTk/Q9h9cY4XVCICJzIlYG', 'admin', 'ar', 1, '2026-03-29 22:44:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(6, 'Yasin Mubarak Aldouma Shomain', 'admin@domain.ext', '$2y$10$JvnCynhrV.ii0SyJjX4Q1uh.V.eXkDEIotymhjlUBfykWbx0zFXNu', 'student', 'ar', 1, '2026-03-30 01:02:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(7, 'ali ahmed', 'yasynmbark92@gmail.com', '$2y$10$GcQP.OI2GzoLOKH/WUfsXu/9Hjlax7MudZPZTm5pV0FWHQjLZTbUS', 'student', 'en', 1, '2026-04-04 13:18:07', 2010, 7, '974', '7342563793', '0', '0', '0', '7342563793', '0', 0, '780922', '7TYq2XJBVNP8'),
(8, 'ali ahmed', 'yasynmbark908@gmail.com', '$2y$10$D6EKeEyXZWtYldA1VSTSNevmAXr0g2qryg2bw67HGj42eDkmPZccy', 'student', 'en', 1, '2026-04-04 13:19:49', 2010, 7, '974', '7342563793', '0', '0', '0', '7342563793', '0', 0, '319349', 'N3n5qi4$6Dmd'),
(9, 'ali ahmed', 'yasynmbark909@gmail.com', '$2y$10$f6cMr.PAJvcjuExt3xkKcOxcnkCT9Ijdk2fhkJYdNGgZQaVchxt62', 'student', 'ar', 1, '2026-04-04 13:52:05', 2009, 2, '966', '7342563793', '0', '0', '0', '0', '0', 0, '817394', 'ZwhEXgmy3iR6'),
(10, 'ali ahmed adam', 'yasynmbark0908@gmail.com', '$2y$10$y3tlUUc0GTo6G/bApIEW1OqFAcopEyO57OlBcYhIaB89wSXSAHiV.', 'student', 'ar', 1, '2026-04-04 13:54:57', 2009, 2, '966', '73425637935', '0', '0', '0', '0', '0', 0, '564792', '5DwVB6KA!3eE'),
(12, 'ali ahmed adam ali', 'xspacex16@gmail.com', '$2y$10$LZElsRrua2DWZvt4k/2ZX.IxhVF/KLIr8fve.3v8HWe5momZQF5Zu', 'student', 'ar', 1, '2026-04-04 15:19:10', 2013, 11, '+249', '0110900994', '', '', '', '', '', 0, '140417', '$1vVfYEBCXdi'),
(13, 'طالب اختبار تجريبي', 'e2e_20260406165701@example.com', '$2y$10$sRkEHLdZzEVpuczO2BrFievhheDIe2NG7gf10fDGXPdfR5Nji9Pbq', 'student', 'ar', 1, '2026-04-06 16:57:01', 2012, 5, '+966', '555123456', '', 'Riyadh', 'Riyadh', '555111222', 'ولي امر', 1, NULL, '40uJXofbczUV'),
(14, 'طالب اختبار ثاني', 'e2e2_20260406165727@example.com', '$2y$10$21B/L.9qFSUGo94PYCqOKeSpLlr1QNOiaifrEQkaWX8yDGocrKHzm', 'student', 'ar', 1, '2026-04-06 16:57:27', 2011, 6, '+966', '555987654', '', 'Jeddah', 'Makkah', '555222333', 'ولي امر', 1, NULL, 'E7vT$nWPFdB9');

-- --------------------------------------------------------

--
-- Table structure for table `weekly_challenges`
--

CREATE TABLE `weekly_challenges` (
  `id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `description_ar` mediumtext DEFAULT NULL,
  `description_en` mediumtext DEFAULT NULL,
  `challenge_type` enum('hackathon','project','quiz') NOT NULL DEFAULT 'project',
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_audit_created` (`created_at`),
  ADD KEY `idx_admin_audit_actor` (`admin_user_id`);

--
-- Indexes for table `auth_login_attempts`
--
ALTER TABLE `auth_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auth_attempt` (`email`,`ip_address`,`created_at`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_code` (`certificate_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `challenge_submissions`
--
ALTER TABLE `challenge_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_challenge_user` (`challenge_id`,`user_id`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `fk_courses_track` (`track_id`);

--
-- Indexes for table `course_projects`
--
ALTER TABLE `course_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `course_quizzes`
--
ALTER TABLE `course_quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_enrollment` (`user_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `learning_tracks`
--
ALTER TABLE `learning_tracks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `lesson_completions`
--
ALTER TABLE `lesson_completions`
  ADD PRIMARY KEY (`user_id`,`lesson_id`),
  ADD KEY `idx_lc_course` (`user_id`,`course_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_quiz_user` (`quiz_id`,`user_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `weekly_challenges`
--
ALTER TABLE `weekly_challenges`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `auth_login_attempts`
--
ALTER TABLE `auth_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `challenge_submissions`
--
ALTER TABLE `challenge_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `course_projects`
--
ALTER TABLE `course_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_quizzes`
--
ALTER TABLE `course_quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `learning_tracks`
--
ALTER TABLE `learning_tracks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `weekly_challenges`
--
ALTER TABLE `weekly_challenges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD CONSTRAINT `admin_audit_logs_ibfk_1` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `challenge_submissions`
--
ALTER TABLE `challenge_submissions`
  ADD CONSTRAINT `challenge_submissions_ibfk_1` FOREIGN KEY (`challenge_id`) REFERENCES `weekly_challenges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `challenge_submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_courses_track` FOREIGN KEY (`track_id`) REFERENCES `learning_tracks` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `course_projects`
--
ALTER TABLE `course_projects`
  ADD CONSTRAINT `course_projects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `course_quizzes`
--
ALTER TABLE `course_quizzes`
  ADD CONSTRAINT `course_quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `course_quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
