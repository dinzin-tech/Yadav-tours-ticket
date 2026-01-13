-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 03:27 PM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 8.0.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ticket_system`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_ticket_id` (IN `p_ticket_type` VARCHAR(10), OUT `p_ticket_id` VARCHAR(20))  BEGIN
    DECLARE v_prefix CHAR(1);
    DECLARE v_date_part CHAR(8);
    DECLARE v_seq_num INT;
    
    SET v_prefix = UPPER(LEFT(p_ticket_type, 1));
    SET v_date_part = DATE_FORMAT(NOW(), '%Y%m%d');
    
    -- Get next sequence number for today
    SELECT COALESCE(MAX(CAST(SUBSTRING(ticket_id, 10) AS UNSIGNED)), 0) + 1 INTO v_seq_num
    FROM tickets
    WHERE ticket_id LIKE CONCAT(v_prefix, v_date_part, '%');
    
    SET p_ticket_id = CONCAT(v_prefix, v_date_part, LPAD(v_seq_num, 4, '0'));
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'INSERT', 'tickets', 'T202512220424', NULL, '{\"ticket_id\": \"T202512220424\", \"ticket_type\": \"train\", \"pnr\": \"1234567890\", \"customer_name\": \"Charan S\", \"total_amount\": 590.00}', NULL, NULL, '2025-12-22 15:10:22'),
(2, 1, 'INSERT', 'tickets', 'T202512229474', NULL, '{\"ticket_id\": \"T202512229474\", \"ticket_type\": \"train\", \"pnr\": \"1234567890\", \"customer_name\": \"Charan S\", \"total_amount\": 590.00}', NULL, NULL, '2025-12-22 15:11:20'),
(3, 1, 'INSERT', 'tickets', 'T202512221430', NULL, '{\"ticket_id\": \"T202512221430\", \"ticket_type\": \"train\", \"pnr\": \"9353333945\", \"customer_name\": \"Charan S\", \"total_amount\": 640.00}', NULL, NULL, '2025-12-22 15:13:38'),
(4, 1, 'INSERT', 'tickets', 'T202512220040', NULL, '{\"ticket_id\": \"T202512220040\", \"ticket_type\": \"train\", \"pnr\": \"ABCD123456\", \"customer_name\": \"John Doe\", \"total_amount\": 1870.00}', NULL, NULL, '2025-12-22 15:14:05'),
(5, 1, 'INSERT', 'tickets', 'T202512224290', NULL, '{\"ticket_id\": \"T202512224290\", \"ticket_type\": \"train\", \"pnr\": \"ABCD123456\", \"customer_name\": \"John Doe\", \"total_amount\": 1870.00}', NULL, NULL, '2025-12-22 15:15:27'),
(6, 1, 'INSERT', 'tickets', 'T202512224625', NULL, '{\"ticket_id\": \"T202512224625\", \"ticket_type\": \"train\", \"pnr\": \"ABCD123456\", \"customer_name\": \"John Doe\", \"total_amount\": 1870.00}', NULL, NULL, '2025-12-22 15:17:54'),
(7, 1, 'INSERT', 'tickets', 'T202512227677', NULL, '{\"ticket_id\": \"T202512227677\", \"ticket_type\": \"train\", \"pnr\": \"1234567890\", \"customer_name\": \"Charan S\", \"total_amount\": 642.00}', NULL, NULL, '2025-12-22 15:18:52'),
(8, 1, 'INSERT', 'tickets', 'T202512220552', NULL, '{\"ticket_id\": \"T202512220552\", \"ticket_type\": \"train\", \"pnr\": \"9353333945\", \"customer_name\": \"Charan S\", \"total_amount\": 615.00}', NULL, NULL, '2025-12-22 16:01:11'),
(9, 1, 'INSERT', 'tickets', 'T202512222446', NULL, '{\"ticket_id\": \"T202512222446\", \"ticket_type\": \"train\", \"pnr\": \"ABCD123456\", \"customer_name\": \"John Doe\", \"total_amount\": 1870.00}', NULL, NULL, '2025-12-22 17:57:22'),
(10, 1, 'INSERT', 'tickets', 'T202512226300', NULL, '{\"ticket_id\": \"T202512226300\", \"ticket_type\": \"train\", \"pnr\": \"9353333945\", \"customer_name\": \"Charan S\", \"total_amount\": 642.00}', NULL, NULL, '2025-12-22 18:21:23'),
(11, 1, 'INSERT', 'tickets', 'F202512231763', NULL, '{\"ticket_id\": \"F202512231763\", \"ticket_type\": \"flight\", \"pnr\": \"9353333945\", \"customer_name\": \"Charan S\", \"total_amount\": 6400.00}', NULL, NULL, '2025-12-23 14:07:35'),
(12, 1, 'INSERT', 'tickets', 'T202512230549', NULL, '{\"ticket_id\": \"T202512230549\", \"ticket_type\": \"bus\", \"pnr\": \"0987456123\", \"customer_name\": \"Charan S\", \"total_amount\": 2385.00}', NULL, NULL, '2025-12-23 14:12:11'),
(13, 1, 'INSERT', 'tickets', 'T202512230451', NULL, '{\"ticket_id\": \"T202512230451\", \"ticket_type\": \"train\", \"pnr\": \"0987456123\", \"customer_name\": \"Charan S\", \"total_amount\": 5925.00}', NULL, NULL, '2025-12-23 15:16:12'),
(14, 1, 'INSERT', 'tickets', 'T202512241395', NULL, '{\"ticket_id\": \"T202512241395\", \"ticket_type\": \"train\", \"pnr\": \"0987456123\", \"customer_name\": \"Charan S\", \"total_amount\": 810.00}', NULL, NULL, '2025-12-24 12:25:28'),
(15, 1, 'INSERT', 'tickets', 'T202512241632', NULL, '{\"ticket_id\": \"T202512241632\", \"ticket_type\": \"flight\", \"pnr\": \"9353333945\", \"customer_name\": \"Charan S\", \"total_amount\": 30000.00}', NULL, NULL, '2025-12-24 12:28:45'),
(16, 1, 'INSERT', 'tickets', 'T202512261268', NULL, '{\"ticket_id\": \"T202512261268\", \"ticket_type\": \"bus\", \"pnr\": \"AONPCPL6G470\", \"customer_name\": \"Reshma \", \"total_amount\": 735.00}', NULL, NULL, '2025-12-26 12:38:24'),
(17, 1, 'INSERT', 'tickets', 'T202512263604', NULL, '{\"ticket_id\": \"T202512263604\", \"ticket_type\": \"bus\", \"pnr\": \"AONPCPL6G470\", \"customer_name\": \"Reshma \", \"total_amount\": 735.00}', NULL, NULL, '2025-12-26 13:58:29'),
(18, 1, 'INSERT', 'tickets', 'T202512262447', NULL, '{\"ticket_id\": \"T202512262447\", \"ticket_type\": \"bus\", \"pnr\": \"AONPCQJY36769\", \"customer_name\": \"Daivik V\", \"total_amount\": 735.00}', NULL, NULL, '2025-12-26 15:27:38'),
(19, 1, 'INSERT', 'tickets', 'T202601025927', NULL, '{\"ticket_id\": \"T202601025927\", \"ticket_type\": \"flight\", \"pnr\": \"R1TDPY\", \"customer_name\": \"S Devaraja\", \"total_amount\": 7300.70}', NULL, NULL, '2026-01-02 08:20:54'),
(20, 1, 'INSERT', 'tickets', 'T202601134108', NULL, '{\"ticket_id\": \"T202601134108\", \"ticket_type\": \"tour\", \"pnr\": \"N/A\", \"customer_name\": \"Charan S\", \"total_amount\": 63500.00}', NULL, NULL, '2026-01-13 08:41:54');

-- --------------------------------------------------------

--
-- Table structure for table `branding_settings`
--

CREATE TABLE `branding_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `setting_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `setting_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branding_settings`
--

INSERT INTO `branding_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `setting_group`, `created_at`, `updated_at`) VALUES
(1, 'brand_name', 'Yadav Tours and Travels', 'text', 'general', '2025-12-23 14:39:12', '2025-12-26 14:14:51'),
(2, 'company_address', 'Lohith Nagara, Nelamangala, Bangalore - 562123', 'text', 'contact', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(3, 'company_phone', '+91 7019409891', 'text', 'contact', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(4, 'company_email', 'contact@yadavtours.in', 'text', 'contact', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(5, 'website_url', 'https://www.yadavtours.in/', 'text', 'contact', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(6, 'primary_color', '#00e6e2', 'text', 'appearance', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(7, 'secondary_color', '#57595b', 'text', 'appearance', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(8, 'logo_path', '/assets/uploads/logo_path_1766758493.png', 'file', 'appearance', '2025-12-23 14:39:12', '2025-12-26 14:14:53'),
(9, 'favicon_path', '/assets/uploads/favicon_path_1766758493.png', 'file', 'appearance', '2025-12-23 14:39:12', '2025-12-26 14:14:53'),
(10, 'terms_conditions', '<p>1. This is a computer generated ticket, no signature required.</p><p>2. Please carry valid ID proof during journey.</p><p>3. Reporting time: 30 minutes before departure.</p>', 'text', 'legal', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(11, 'footer_text', '© 2025 Travel Ticket System. All rights reserved.', 'text', 'general', '2025-12-23 14:39:12', '2025-12-26 14:14:52'),
(12, 'email_signature', 'Best Regards,\nTravel Ticket System Team', 'textarea', 'communication', '2025-12-23 14:39:12', '2025-12-23 14:39:12'),
(13, 'invoice_prefix', 'INV-', 'text', 'invoice', '2025-12-23 14:39:12', '2025-12-26 14:14:53'),
(14, 'currency_symbol', '₹', 'text', 'invoice', '2025-12-23 14:39:12', '2025-12-26 14:14:53'),
(15, 'tax_percentage', '18', 'text', 'invoice', '2025-12-23 14:39:12', '2025-12-26 14:14:53'),
(16, 'enable_email_notifications', '0', 'checkbox', 'features', '2025-12-23 14:39:12', '2025-12-26 14:14:53'),
(17, 'enable_sms_notifications', '0', 'checkbox', 'features', '2025-12-23 14:39:12', '2025-12-26 14:14:53');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json','array') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'general',
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `updated_at`) VALUES
(1, 'company_name', 'Travel Agency Pvt Ltd', 'string', 'general', 'Company name for tickets', 0, '2025-12-22 12:31:01'),
(2, 'company_address', '123 Travel Street, Mumbai, India', 'string', 'general', 'Company address', 0, '2025-12-22 12:31:01'),
(3, 'company_phone', '+91 1234567890', 'string', 'general', 'Company phone number', 0, '2025-12-22 12:31:01'),
(4, 'company_email', 'info@travelagency.com', 'string', 'general', 'Company email', 0, '2025-12-22 12:31:01'),
(5, 'company_website', 'www.travelagency.com', 'string', 'general', 'Company website', 0, '2025-12-22 12:31:01'),
(6, 'currency_symbol', '₹', 'string', 'general', 'Currency symbol', 0, '2025-12-22 12:31:01'),
(7, 'tax_percentage', '18', 'number', 'financial', 'Tax percentage', 0, '2025-12-22 12:31:01'),
(8, 'receipt_footer', 'Thank you for choosing our services!', 'string', 'general', 'Receipt footer text', 0, '2025-12-22 12:31:01'),
(9, 'session_timeout', '30', 'number', 'security', 'Session timeout in minutes', 0, '2025-12-22 12:31:01'),
(10, 'max_login_attempts', '5', 'number', 'security', 'Maximum failed login attempts', 0, '2025-12-22 12:31:01'),
(11, 'enable_email_notifications', '0', 'boolean', 'notifications', 'Enable email notifications', 0, '2025-12-22 12:31:01'),
(12, 'pdf_quality', 'high', 'string', 'pdf', 'PDF generation quality', 0, '2025-12-22 12:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ticket_type` enum('train','flight','bus','cab','tour') COLLATE utf8mb4_unicode_ci NOT NULL,
  `pnr` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_date` date NOT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('paid','pending','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'paid',
  `ticket_status` enum('issued','cancelled','refunded') COLLATE utf8mb4_unicode_ci DEFAULT 'issued',
  `ticket_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`ticket_data`)),
  `pdf_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `ticket_type`, `pnr`, `booking_date`, `customer_name`, `customer_email`, `customer_phone`, `total_amount`, `payment_status`, `ticket_status`, `ticket_data`, `pdf_path`, `generated_by`, `generated_at`, `cancelled_at`, `cancelled_by`, `notes`) VALUES
('F202512231763', 'flight', '9353333945', '2025-12-23', 'Charan S', 'contact@dinzin.in', '', '6400.00', 'paid', 'issued', '{\"ticket_type\":\"flight\",\"customer_name\":\"Charan S\",\"customer_email\":\"contact@dinzin.in\",\"customer_phone\":\"\",\"pnr\":\"9353333945\",\"flight_number\":\"h334h\",\"airline\":\"akasha\",\"class\":\"Economy\",\"from_airport\":\"Bangalore\",\"to_airport\":\"Mumbai\",\"departure_datetime\":\"2025-12-23 00:00\",\"arrival_datetime\":\"2025-12-24 00:00\",\"seat\":\"12\",\"gate\":\"U1\",\"terminal\":\"2\",\"passenger_name\":[\"Charan\",\"Sanjeev\"],\"passenger_age\":[\"10\",\"12\"],\"passenger_gender\":[\"Male\",\"Male\"],\"passenger_id_type\":[\"Aadhar\",\"Aadhar\"],\"passenger_id_number\":[\"\",\"\"],\"passenger_seat\":[\"12\",\"13\"],\"base_fare\":\"5000\",\"tax_percent\":\"18\",\"tax_amount\":\"900.00\",\"service_charge\":\"500\",\"total_amount\":\"6400.00\",\"generated_by\":\"admin\",\"generated_at\":\"2025-12-23 15:07:35\",\"ticket_id\":\"F202512235593\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"10\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"12\",\"coach\":\"\"},{\"name\":\"Sanjeev\",\"age\":\"12\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"13\",\"coach\":\"\"}]}', NULL, 1, '2025-12-23 14:07:35', NULL, NULL, NULL),
('T202512220040', 'train', 'ABCD123456', '2025-12-22', 'John Doe', 'john@example.com', '1234567890', '1870.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"John Doe\",\"customer_email\":\"john@example.com\",\"customer_phone\":\"1234567890\",\"pnr\":\"ABCD123456\",\"train_number\":\"12345\",\"train_name\":\"Rajdhani Express\",\"class\":\"AC 2 Tier\",\"from_station\":\"Mumbai\",\"to_station\":\"Delhi\",\"departure_datetime\":\"2024-01-15 15:30\",\"arrival_datetime\":\"2024-01-16 08:30\",\"coach\":\"B3\",\"seat\":\"12\",\"passenger_name\":[\"John Doe\"],\"passenger_age\":[30],\"passenger_gender\":[\"Male\"],\"base_fare\":1500,\"tax_percent\":18,\"tax_amount\":270,\"service_charge\":100,\"total_amount\":1870,\"booking_date\":\"2025-12-22\",\"generated_by\":\"test_user\",\"generated_at\":\"2025-12-22 16:14:05\",\"passengers\":[{\"name\":\"John Doe\",\"age\":30,\"gender\":\"Male\",\"id_type\":\"\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 15:14:05', NULL, NULL, NULL),
('T202512220424', 'train', '1234567890', '2025-12-22', 'Charan S', 'dinzinp@gmail.com', '09353333945', '590.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"dinzinp@gmail.com\",\"customer_phone\":\"09353333945\",\"pnr\":\"1234567890\",\"train_number\":\"2252\",\"train_name\":\"Test\",\"class\":\"AC First Class\",\"from_station\":\"Bangalore\",\"to_station\":\"Mumbai\",\"departure_datetime\":\"\",\"arrival_datetime\":\"\",\"coach\":\"\",\"seat\":\"\",\"passenger_name\":[\"charan\"],\"passenger_age\":[\"\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"\"],\"passenger_seat\":[\"\"],\"base_fare\":\"500\",\"tax_percent\":\"18\",\"tax_amount\":\"90.00\",\"service_charge\":\"0\",\"total_amount\":\"590.00\",\"booking_date\":\"2025-12-22\",\"generated_by\":\"admin\",\"generated_at\":\"2025-12-22 16:10:22\",\"passengers\":[{\"name\":\"charan\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 15:10:22', NULL, NULL, NULL),
('T202512220552', 'train', '9353333945', '2025-12-22', 'Charan S', 'contact@dinzin.in', '9353333945', '615.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"contact@dinzin.in\",\"customer_phone\":\"9353333945\",\"pnr\":\"9353333945\",\"train_number\":\"5545\",\"train_name\":\"KAR Exp\",\"class\":\"AC First Class\",\"from_station\":\"Bangalore\",\"to_station\":\"Mumbai\",\"departure_datetime\":\"2025-12-22 00:00\",\"arrival_datetime\":\"2025-12-22 00:00\",\"coach\":\"\",\"seat\":\"\",\"passenger_name\":[\"Charan\"],\"passenger_age\":[\"\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"\"],\"passenger_seat\":[\"\"],\"base_fare\":\"500\",\"tax_percent\":\"18\",\"tax_amount\":\"90.00\",\"service_charge\":\"25\",\"total_amount\":\"615.00\",\"generated_by\":\"test_user\",\"generated_at\":\"2025-12-22 17:01:11\",\"ticket_id\":\"T202512223222\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 16:01:11', NULL, NULL, NULL),
('T202512221430', 'train', '9353333945', '2025-12-22', 'Charan S', 'contact@dinzin.in', '9353333945', '640.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"contact@dinzin.in\",\"customer_phone\":\"9353333945\",\"pnr\":\"9353333945\",\"train_number\":\"5545\",\"train_name\":\"KAR Exp\",\"class\":\"AC First Class\",\"from_station\":\"Bangalore\",\"to_station\":\"Mumbai\",\"departure_datetime\":\"2025-12-22 00:00\",\"arrival_datetime\":\"2025-12-22 00:00\",\"coach\":\"\",\"seat\":\"\",\"passenger_name\":[\"Charan\"],\"passenger_age\":[\"\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"\"],\"passenger_seat\":[\"\"],\"base_fare\":\"500\",\"tax_percent\":\"18\",\"tax_amount\":\"90.00\",\"service_charge\":\"50\",\"total_amount\":\"640.00\",\"booking_date\":\"2025-12-22\",\"generated_by\":\"admin\",\"generated_at\":\"2025-12-22 16:13:38\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 15:13:38', NULL, NULL, NULL),
('T202512222446', 'train', 'ABCD123456', '2025-12-22', 'John Doe', 'john@example.com', '1234567890', '1870.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"John Doe\",\"customer_email\":\"john@example.com\",\"customer_phone\":\"1234567890\",\"pnr\":\"ABCD123456\",\"train_number\":\"12345\",\"train_name\":\"Rajdhani Express\",\"class\":\"AC 2 Tier\",\"from_station\":\"Mumbai\",\"to_station\":\"Delhi\",\"departure_datetime\":\"2024-01-15 15:30\",\"arrival_datetime\":\"2024-01-16 08:30\",\"coach\":\"B3\",\"seat\":\"12\",\"passenger_name\":[\"John Doe\"],\"passenger_age\":[30],\"passenger_gender\":[\"Male\"],\"base_fare\":1500,\"tax_percent\":18,\"tax_amount\":270,\"service_charge\":100,\"total_amount\":1870,\"booking_date\":\"2025-12-22\",\"generated_by\":\"test_user\",\"generated_at\":\"2025-12-22 18:57:22\",\"ticket_id\":\"T202512222767\",\"passengers\":[{\"name\":\"John Doe\",\"age\":30,\"gender\":\"Male\",\"id_type\":\"\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 17:57:22', NULL, NULL, NULL),
('T202512224290', 'train', 'ABCD123456', '2025-12-22', 'John Doe', 'john@example.com', '1234567890', '1870.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"John Doe\",\"customer_email\":\"john@example.com\",\"customer_phone\":\"1234567890\",\"pnr\":\"ABCD123456\",\"train_number\":\"12345\",\"train_name\":\"Rajdhani Express\",\"class\":\"AC 2 Tier\",\"from_station\":\"Mumbai\",\"to_station\":\"Delhi\",\"departure_datetime\":\"2024-01-15 15:30\",\"arrival_datetime\":\"2024-01-16 08:30\",\"coach\":\"B3\",\"seat\":\"12\",\"passenger_name\":[\"John Doe\"],\"passenger_age\":[30],\"passenger_gender\":[\"Male\"],\"base_fare\":1500,\"tax_percent\":18,\"tax_amount\":270,\"service_charge\":100,\"total_amount\":1870,\"booking_date\":\"2025-12-22\",\"generated_by\":\"test_user\",\"generated_at\":\"2025-12-22 16:15:27\",\"passengers\":[{\"name\":\"John Doe\",\"age\":30,\"gender\":\"Male\",\"id_type\":\"\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 15:15:27', NULL, NULL, NULL),
('T202512224625', 'train', 'ABCD123456', '2025-12-22', 'John Doe', 'john@example.com', '1234567890', '1870.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"John Doe\",\"customer_email\":\"john@example.com\",\"customer_phone\":\"1234567890\",\"pnr\":\"ABCD123456\",\"train_number\":\"12345\",\"train_name\":\"Rajdhani Express\",\"class\":\"AC 2 Tier\",\"from_station\":\"Mumbai\",\"to_station\":\"Delhi\",\"departure_datetime\":\"2024-01-15 15:30\",\"arrival_datetime\":\"2024-01-16 08:30\",\"coach\":\"B3\",\"seat\":\"12\",\"passenger_name\":[\"John Doe\"],\"passenger_age\":[30],\"passenger_gender\":[\"Male\"],\"base_fare\":1500,\"tax_percent\":18,\"tax_amount\":270,\"service_charge\":100,\"total_amount\":1870,\"booking_date\":\"2025-12-22\",\"generated_by\":\"test_user\",\"generated_at\":\"2025-12-22 16:17:54\",\"ticket_id\":\"T202512227786\",\"passengers\":[{\"name\":\"John Doe\",\"age\":30,\"gender\":\"Male\",\"id_type\":\"\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 15:17:54', NULL, NULL, NULL),
('T202512226300', 'train', '9353333945', '2025-12-22', 'Charan S', 'contact@dinzin.in', '9353333945', '642.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"contact@dinzin.in\",\"customer_phone\":\"9353333945\",\"pnr\":\"9353333945\",\"train_number\":\"5545\",\"train_name\":\"KAR Exp\",\"class\":\"AC First Class\",\"from_station\":\"Bangalore\",\"to_station\":\"Mumbai\",\"departure_datetime\":\"2025-12-22 00:00\",\"arrival_datetime\":\"2025-12-22 00:00\",\"coach\":\"\",\"seat\":\"\",\"passenger_name\":[\"Charan\"],\"passenger_age\":[\"\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"\"],\"passenger_seat\":[\"\"],\"base_fare\":\"500\",\"tax_percent\":\"18\",\"tax_amount\":\"90.00\",\"service_charge\":\"52\",\"total_amount\":\"642.00\",\"generated_by\":\"test_user\",\"generated_at\":\"2025-12-22 19:21:23\",\"ticket_id\":\"T202512226723\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 18:21:23', NULL, NULL, NULL),
('T202512227677', 'train', '1234567890', '2025-12-22', 'Charan S', 'dinzinp@gmail.com', '09353333945', '642.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"dinzinp@gmail.com\",\"customer_phone\":\"09353333945\",\"pnr\":\"1234567890\",\"train_number\":\"kjk\",\"train_name\":\"45455\",\"class\":\"AC First Class\",\"from_station\":\"bal\",\"to_station\":\"kkki\",\"departure_datetime\":\"2025-12-22 00:00\",\"arrival_datetime\":\"2025-12-22 00:00\",\"coach\":\"\",\"seat\":\"\",\"passenger_name\":[\"Charan\"],\"passenger_age\":[\"\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"\"],\"passenger_seat\":[\"\"],\"base_fare\":\"500\",\"tax_percent\":\"18\",\"tax_amount\":\"90.00\",\"service_charge\":\"52\",\"total_amount\":\"642.00\",\"booking_date\":\"2025-12-22\",\"generated_by\":\"admin\",\"generated_at\":\"2025-12-22 16:18:52\",\"ticket_id\":\"T202512227129\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 15:18:52', NULL, NULL, NULL),
('T202512229474', 'train', '1234567890', '2025-12-22', 'Charan S', 'dinzinp@gmail.com', '09353333945', '590.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"dinzinp@gmail.com\",\"customer_phone\":\"09353333945\",\"pnr\":\"1234567890\",\"train_number\":\"2252\",\"train_name\":\"Test\",\"class\":\"AC First Class\",\"from_station\":\"Bangalore\",\"to_station\":\"Mumbai\",\"departure_datetime\":\"2025-12-22 00:00\",\"arrival_datetime\":\"2025-12-23 00:00\",\"coach\":\"\",\"seat\":\"\",\"passenger_name\":[\"Charan S\"],\"passenger_age\":[\"\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"\"],\"passenger_seat\":[\"\"],\"base_fare\":\"500\",\"tax_percent\":\"18\",\"tax_amount\":\"90.00\",\"service_charge\":\"0\",\"total_amount\":\"590.00\",\"booking_date\":\"2025-12-22\",\"generated_by\":\"admin\",\"generated_at\":\"2025-12-22 16:11:20\",\"passengers\":[{\"name\":\"Charan S\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}]}', NULL, 1, '2025-12-22 15:11:20', NULL, NULL, NULL),
('T202512230451', 'train', '0987456123', '2025-12-23', 'Charan S', 'contact@dinzin.in', '9353333945', '5925.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"contact@dinzin.in\",\"customer_phone\":\"9353333945\",\"pnr\":\"0987456123\",\"train_number\":\"5545\",\"train_name\":\"KAR Exp\",\"class\":\"AC First Class\",\"from_station\":\"Bangalore\",\"to_station\":\"Mumbai\",\"departure_datetime\":\"2025-12-23 00:00\",\"arrival_datetime\":\"2025-12-23 00:00\",\"coach\":\"21\",\"seat\":\"25\",\"passenger_name\":[\"Charan\"],\"passenger_age\":[\"22\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"21\"],\"passenger_seat\":[\"25\"],\"base_fare\":\"5000\",\"tax_percent\":\"18\",\"tax_amount\":900,\"service_charge\":\"25\",\"total_amount\":5925,\"booking_date\":\"2025-12-23\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"22\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"25\",\"coach\":\"21\"}],\"generated_by\":\"admin\",\"generated_at\":\"2025-12-23 16:16:12\",\"ticket_id\":\"T202512230451\"}', NULL, 1, '2025-12-23 15:16:12', NULL, NULL, NULL),
('T202512230549', 'bus', '0987456123', '2025-12-23', 'Charan S', 'test@gmail.com', '9353333945', '2385.00', 'paid', 'issued', '{\"ticket_type\":\"bus\",\"customer_name\":\"Charan S\",\"customer_email\":\"test@gmail.com\",\"customer_phone\":\"9353333945\",\"pnr\":\"0987456123\",\"operator\":\"VRL\",\"bus_type\":\"AC Sleeper\",\"seat\":\"22\",\"pickup_point\":\"Bangalore\",\"drop_point\":\"Mangalore\",\"reporting_time\":\"08:10\",\"departure_datetime\":\"2025-12-23 20:10\",\"arrival_datetime\":\"2025-12-23 00:00\",\"boarding_address\":\"Lohith Nagara, Nelmangala\",\"passenger_name\":[\"Charan\",\"Sanjeev\"],\"passenger_age\":[\"\",\"\"],\"passenger_gender\":[\"Male\",\"Male\"],\"passenger_id_type\":[\"Aadhar\",\"Aadhar\"],\"passenger_id_number\":[\"\",\"\"],\"base_fare\":\"2000\",\"tax_percent\":\"18\",\"tax_amount\":360,\"service_charge\":\"25\",\"total_amount\":2385,\"booking_date\":\"2025-12-23\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"},{\"name\":\"Sanjeev\",\"age\":\"\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}],\"generated_by\":\"admin\",\"generated_at\":\"2025-12-23 15:12:11\",\"ticket_id\":\"T202512230549\"}', NULL, 1, '2025-12-23 14:12:11', NULL, NULL, NULL),
('T202512241395', 'train', '0987456123', '2025-12-24', 'Charan S', 'contact@dinzin.in', '9353333945', '810.00', 'paid', 'issued', '{\"ticket_type\":\"train\",\"customer_name\":\"Charan S\",\"customer_email\":\"contact@dinzin.in\",\"customer_phone\":\"9353333945\",\"pnr\":\"0987456123\",\"train_number\":\"5545\",\"train_name\":\"KAR Exp\",\"class\":\"AC First Class\",\"from_station\":\"Bangalore\",\"to_station\":\"Mumbai\",\"departure_datetime\":\"2025-12-24 12:00\",\"arrival_datetime\":\"2025-12-25 00:00\",\"coach\":\"D2\",\"seat\":\"12\",\"passenger_name\":[\"Charan\"],\"passenger_age\":[\"22\"],\"passenger_gender\":[\"Male\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"passenger_coach\":[\"D2\"],\"passenger_seat\":[\"12\"],\"base_fare\":\"500\",\"tax_percent\":\"18\",\"tax_amount\":90,\"service_charge\":\"220\",\"total_amount\":810,\"booking_date\":\"2025-12-24\",\"passengers\":[{\"name\":\"Charan\",\"age\":\"22\",\"gender\":\"Male\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"12\",\"coach\":\"D2\"}],\"generated_by\":\"admin\",\"generated_at\":\"2025-12-24 13:25:28\",\"ticket_id\":\"T202512241395\",\"branding_info\":{\"company_name\":\"Yadav Tours and Travels\",\"primary_color\":\"#0d89fd\",\"secondary_color\":\"#6c757d\",\"company_address\":\"Lohith Nagara, Nelamangala, Bangalore - 562123\",\"company_phone\":\"+91 8884427588\",\"company_email\":\"contact@dinzin.in\"}}', NULL, 1, '2025-12-24 12:25:28', NULL, NULL, NULL),
('T202512261268', 'bus', 'AONPCPL6G470', '2025-12-26', 'Reshma ', '', '7676657698', '735.00', 'paid', 'issued', '{\"ticket_type\":\"bus\",\"customer_name\":\"Reshma \",\"customer_email\":\"\",\"customer_phone\":\"7676657698\",\"pnr\":\"AONPCPL6G470\",\"operator\":\"A1 Travels\",\"bus_type\":\"AC Seater\",\"seat\":\"2\",\"pickup_point\":\"Bangalore \",\"drop_point\":\"Tiruvannamalai\",\"reporting_time\":\"12:00\",\"departure_datetime\":\"2025-12-27 11:55\",\"arrival_datetime\":\"2025-12-27 17:45\",\"boarding_address\":\"Whitefield\",\"passenger_name\":[\"Reshma Madhukar \"],\"passenger_age\":[\"43\"],\"passenger_gender\":[\"Female\"],\"passenger_id_type\":[\"Aadhar\"],\"passenger_id_number\":[\"\"],\"base_fare\":\"700\",\"tax_percent\":\"5\",\"tax_amount\":35,\"service_charge\":\"0\",\"total_amount\":735,\"booking_date\":\"2025-12-26\",\"passengers\":[{\"name\":\"Reshma Madhukar \",\"age\":\"43\",\"gender\":\"Female\",\"id_type\":\"Aadhar\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}],\"generated_by\":\"admin\",\"generated_at\":\"2025-12-26 13:38:24\",\"ticket_id\":\"T202512261268\",\"branding_info\":{\"company_name\":\"Yadav Tours and Travels\",\"primary_color\":\"#00e6e2\",\"secondary_color\":\"#57595b\",\"company_address\":\"Lohith Nagara, Nelamangala, Bangalore - 562123\",\"company_phone\":\"+91 7019409891\",\"company_email\":\"contact@yadavtours.in\"}}', NULL, 1, '2025-12-26 12:38:24', NULL, NULL, NULL),
('T202512262447', 'bus', 'AONPCQJY36769', '2025-12-26', 'Daivik V', '', '7676657698', '735.00', 'paid', 'issued', '{\"ticket_type\":\"bus\",\"customer_name\":\"Daivik V\",\"customer_email\":\"\",\"customer_phone\":\"7676657698\",\"pnr\":\"AONPCQJY36769\",\"operator\":\"A1 Travels\",\"bus_type\":\"AC Seater\",\"bus_manager_contact\":\"9972995181\",\"boarding_address\":\"Bangalore, Whitefield NEAR ITPL BACK GATE HOLIDAY HOTEL\",\"drop_address\":\"Tiruvannamalai, Arunachalam Temple Ramanashramam\\r\\nMummy Daddy Kalyanamandapam, Samuthiram, thiruvannamalai\",\"pickup_point\":\"Whitefield NEAR ITPL BACK GATE HOLIDAY HOTEL\",\"drop_point\":\"Arunachalam Temple Ramanashramam Mummy Daddy Kalyanamandapam, Samuthiram, thiruvannamalai\",\"reporting_time\":\"10:50\",\"departure_datetime\":\"2025-12-27 12:00\",\"arrival_datetime\":\"2025-12-26 17:45\",\"passenger_name\":[\"Daivik V Bangera\"],\"passenger_age\":[\"26\"],\"passenger_gender\":[\"Male\"],\"passenger_seat\":[\"1\"],\"base_fare\":700,\"tax_percent\":5,\"tax_amount\":\"35.00\",\"service_charge\":0,\"total_amount\":\"735.00\",\"booking_date\":\"2025-12-26\",\"passengers\":[{\"name\":\"Daivik V Bangera\",\"age\":\"26\",\"gender\":\"Male\",\"seat\":\"1\",\"coach\":\"\"}],\"generated_by\":\"admin\",\"generated_at\":\"2025-12-26 16:27:38\",\"ticket_id\":\"T202512262447\",\"branding_info\":{\"company_name\":\"Yadav Tours and Travels\",\"primary_color\":\"#00e6e2\",\"secondary_color\":\"#57595b\",\"company_address\":\"Lohith Nagara, Nelamangala, Bangalore - 562123\",\"company_phone\":\"+91 7019409891\",\"company_email\":\"contact@yadavtours.in\"},\"seat\":\"\",\"updated_at\":\"2025-12-26 16:43:26\",\"updated_by\":\"admin\"}', NULL, 1, '2025-12-26 15:27:38', NULL, NULL, NULL),
('T202512263604', 'bus', 'AONPCPL6G470', '2025-12-26', 'Reshma ', '', '7676657698', '735.00', 'paid', 'issued', '{\"ticket_type\":\"bus\",\"customer_name\":\"Reshma \",\"customer_email\":\"\",\"customer_phone\":\"7676657698\",\"pnr\":\"AONPCPL6G470\",\"operator\":\"A1 Travels\",\"bus_type\":\"AC Seater\",\"bus_manager_contact\":\"9972995181\",\"boarding_address\":\"Whitefield\",\"drop_address\":\"Arunachalam Temple Ramanashramam\\r\\nMummy Daddy Kalyanamandapam, Samuthiram, thiruvannamalai\",\"pickup_point\":\"Bangalore\",\"drop_point\":\"Tiruvannamalai\",\"reporting_time\":\"11:50\",\"departure_datetime\":\"2025-12-27 10:50\",\"arrival_datetime\":\"2025-12-27 16:45\",\"passenger_name\":[\"Reshma Madhukar \"],\"passenger_age\":[\"43\"],\"passenger_gender\":[\"Female\"],\"passenger_seat\":[\"2\"],\"base_fare\":\"700\",\"tax_percent\":\"5\",\"tax_amount\":35,\"service_charge\":\"0\",\"total_amount\":735,\"booking_date\":\"2025-12-26\",\"passengers\":[{\"name\":\"Reshma Madhukar \",\"age\":\"43\",\"gender\":\"Female\",\"id_type\":\"\",\"id_number\":\"\",\"seat\":\"2\",\"coach\":\"\"}],\"generated_by\":\"admin\",\"generated_at\":\"2025-12-26 14:58:29\",\"ticket_id\":\"T202512263604\",\"branding_info\":{\"company_name\":\"Yadav Tours and Travels\",\"primary_color\":\"#00e6e2\",\"secondary_color\":\"#57595b\",\"company_address\":\"Lohith Nagara, Nelamangala, Bangalore - 562123\",\"company_phone\":\"+91 7019409891\",\"company_email\":\"contact@yadavtours.in\"}}', NULL, 1, '2025-12-26 13:58:29', NULL, NULL, NULL),
('T202601025927', 'flight', 'R1TDPY', '2026-01-02', 'S Devaraja', '', '', '7300.70', 'paid', 'issued', '{\"ticket_type\":\"flight\",\"customer_name\":\"S Devaraja\",\"customer_email\":\"\",\"customer_phone\":\"\",\"pnr\":\"R1TDPY\",\"flight_number\":\"IX-1089\",\"airline\":\"Air India Express\",\"class\":\"Economy\",\"from_airport\":\"Bangaluru\",\"to_airport\":\"Chandigarh\",\"departure_datetime\":\"2026-01-02 16:25\",\"arrival_datetime\":\"2026-01-02 19:25\",\"seat\":\"\",\"gate\":\"\",\"terminal\":\"2\",\"passenger_name\":[\"S Devaraja\"],\"passenger_age\":[\"38\"],\"passenger_gender\":[\"Male\"],\"passenger_seat\":[\"\"],\"base_fare\":\"5934\",\"tax_percent\":\"5\",\"tax_amount\":296.7,\"service_charge\":\"1070\",\"total_amount\":7300.7,\"booking_date\":\"2026-01-02\",\"passengers\":[{\"name\":\"S Devaraja\",\"age\":\"38\",\"gender\":\"Male\",\"id_type\":\"\",\"id_number\":\"\",\"seat\":\"\",\"coach\":\"\"}],\"generated_by\":\"admin\",\"generated_at\":\"2026-01-02 09:20:52\",\"ticket_id\":\"T202601025927\",\"branding_info\":{\"company_name\":\"Yadav Tours and Travels\",\"primary_color\":\"#00e6e2\",\"secondary_color\":\"#57595b\",\"company_address\":\"Lohith Nagara, Nelamangala, Bangalore - 562123\",\"company_phone\":\"+91 7019409891\",\"company_email\":\"contact@yadavtours.in\"}}', NULL, 1, '2026-01-02 08:20:54', NULL, NULL, NULL),
('T202601134108', 'tour', 'N/A', '2026-01-13', 'Charan S', 'contact@dinzin.in', '', '63500.00', 'paid', 'issued', '{\"ticket_type\":\"tour\",\"customer_name\":\"Charan S\",\"customer_email\":\"contact@dinzin.in\",\"customer_phone\":\"\",\"package_name\":\"Bangaloreq\",\"package_code\":\"450\",\"coordinator_contact\":\"9353333945\",\"emergency_contact\":\"\",\"persons\":\"5\",\"package_type\":\"Premium\",\"start_location\":\"Lohith Nagara, Nelmangala\",\"end_location\":\"Lalbag\",\"itinerary\":\"Day 1 Bangalore tour\\r\\nDay 2 Bangalore rural\\r\\nDay 3 Nelamangala\",\"inclusions\":\"This for testing qjhudg\",\"base_fare\":\"50000\",\"tax_percent\":\"18\",\"tax_amount\":9000,\"service_charge\":\"4500\",\"total_amount\":63500,\"booking_date\":\"2026-01-13\",\"generated_by\":\"admin\",\"generated_at\":\"2026-01-13 09:41:54\",\"ticket_id\":\"T202601134108\",\"branding_info\":{\"company_name\":\"Yadav Tours and Travels\",\"primary_color\":\"#00e6e2\",\"secondary_color\":\"#57595b\",\"company_address\":\"Lohith Nagara, Nelamangala, Bangalore - 562123\",\"company_phone\":\"+91 7019409891\",\"company_email\":\"contact@yadavtours.in\"}}', NULL, 1, '2026-01-13 08:41:54', NULL, NULL, NULL);

--
-- Triggers `tickets`
--
DELIMITER $$
CREATE TRIGGER `audit_ticket_insert` AFTER INSERT ON `tickets` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values)
    VALUES (NEW.generated_by, 'INSERT', 'tickets', NEW.ticket_id, 
            JSON_OBJECT(
                'ticket_id', NEW.ticket_id,
                'ticket_type', NEW.ticket_type,
                'pnr', NEW.pnr,
                'customer_name', NEW.customer_name,
                'total_amount', NEW.total_amount
            ));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `ticket_statistics`
-- (See below for the actual view)
--
CREATE TABLE `ticket_statistics` (
`user_id` int(11)
,`username` varchar(50)
,`total_tickets` bigint(21)
,`total_revenue` decimal(32,2)
,`today_tickets` bigint(21)
,`train_tickets` bigint(21)
,`flight_tickets` bigint(21)
,`bus_tickets` bigint(21)
,`cab_tickets` bigint(21)
,`tour_tickets` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `ticket_templates`
--

CREATE TABLE `ticket_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_type` enum('train','flight','bus','cab','tour') COLLATE utf8mb4_unicode_ci NOT NULL,
  `html_content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `css_content` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_templates`
--

INSERT INTO `ticket_templates` (`id`, `template_name`, `template_type`, `html_content`, `css_content`, `is_default`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Default Train Ticket', 'train', '<div>Train Ticket Template</div>', NULL, 1, 1, 1, '2025-12-22 12:31:01', '2025-12-22 12:31:01'),
(2, 'Default Flight Ticket', 'flight', '<div>Flight Ticket Template</div>', NULL, 1, 1, 1, '2025-12-22 12:31:01', '2025-12-22 12:31:01'),
(3, 'Default Bus Ticket', 'bus', '<div>Bus Ticket Template</div>', NULL, 1, 1, 1, '2025-12-22 12:31:01', '2025-12-22 12:31:01'),
(4, 'Default Cab Booking', 'cab', '<div>Cab Booking Template</div>', NULL, 1, 1, 1, '2025-12-22 12:31:01', '2025-12-22 12:31:01'),
(5, 'Default Tour Package', 'tour', '<div>Tour Package Template</div>', NULL, 1, 1, 1, '2025-12-22 12:31:01', '2025-12-22 12:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','staff','agent') COLLATE utf8mb4_unicode_ci DEFAULT 'staff',
  `agency_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agency_address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agency_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agency_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agency_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `failed_login_attempts` int(11) DEFAULT 0,
  `lockout_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slogan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_color` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '#1e3c72',
  `secondary_color` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '#1e3c72'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `email`, `full_name`, `phone`, `role`, `agency_name`, `agency_address`, `agency_phone`, `agency_email`, `agency_logo`, `is_active`, `failed_login_attempts`, `lockout_until`, `created_at`, `last_login`, `updated_at`, `website`, `slogan`, `brand_color`, `secondary_color`) VALUES
(1, 'admin', '$2y$10$6uEcp3/E4tcBkSYug8GVHu45xE9L6oV4qQXEwf4HzsrCxkGIq93bi', 'admin@travelagency.com', 'System Administrator', NULL, 'admin', 'Travel Agency Pvt Ltd', 'Lohith Nagara, Nelmangala', '+91 7019409891', 'contact@yadavtours.in', NULL, 1, 0, NULL, '2025-12-22 12:31:00', NULL, '2025-12-26 14:04:27', '', '', '#1e3c72', '#2a5298');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `ticket_statistics`
--
DROP TABLE IF EXISTS `ticket_statistics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `ticket_statistics`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, count(`t`.`ticket_id`) AS `total_tickets`, sum(`t`.`total_amount`) AS `total_revenue`, count(case when cast(`t`.`generated_at` as date) = curdate() then 1 end) AS `today_tickets`, count(case when `t`.`ticket_type` = 'train' then 1 end) AS `train_tickets`, count(case when `t`.`ticket_type` = 'flight' then 1 end) AS `flight_tickets`, count(case when `t`.`ticket_type` = 'bus' then 1 end) AS `bus_tickets`, count(case when `t`.`ticket_type` = 'cab' then 1 end) AS `cab_tickets`, count(case when `t`.`ticket_type` = 'tour' then 1 end) AS `tour_tickets` FROM (`users` `u` left join `tickets` `t` on(`u`.`id` = `t`.`generated_by`)) WHERE `u`.`is_active` = 1 GROUP BY `u`.`id`, `u`.`username` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `branding_settings`
--
ALTER TABLE `branding_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`),
  ADD KEY `idx_category` (`category`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `cancelled_by` (`cancelled_by`),
  ADD KEY `idx_pnr` (`pnr`),
  ADD KEY `idx_ticket_type` (`ticket_type`),
  ADD KEY `idx_generated_by` (`generated_by`),
  ADD KEY `idx_generated_at` (`generated_at`),
  ADD KEY `idx_customer_name` (`customer_name`),
  ADD KEY `idx_booking_date` (`booking_date`);

--
-- Indexes for table `ticket_templates`
--
ALTER TABLE `ticket_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_template_type` (`template_type`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `branding_settings`
--
ALTER TABLE `branding_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=289;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ticket_templates`
--
ALTER TABLE `ticket_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_templates`
--
ALTER TABLE `ticket_templates`
  ADD CONSTRAINT `ticket_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
