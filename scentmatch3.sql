-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 11, 2025 at 10:19 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `scentmatch3`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminID`, `name`, `password`) VALUES
(1, 'admin', '123');

-- --------------------------------------------------------

--
-- Table structure for table `concentration`
--

CREATE TABLE `concentration` (
  `ConcentrationID` int(11) NOT NULL,
  `ConcentrationName` varchar(50) NOT NULL,
  `ConcentrationPercentage` varchar(20) NOT NULL,
  `Longevity` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `concentration`
--

INSERT INTO `concentration` (`ConcentrationID`, `ConcentrationName`, `ConcentrationPercentage`, `Longevity`) VALUES
(1, 'Parfum', '20% - 40%', '8 - 12+ hours'),
(2, 'Eau de Parfum (EDP)', '15% - 20%', '6 - 8 hours'),
(3, 'Eau de Toilette (EDT)', '5% - 15%', '4 - 6 hours'),
(4, 'Eau de Cologne (EDC)', '2% - 5%', '2 - 4 hours'),
(5, 'Eau Fraiche', '1% - 3%', '1 - 2 hours');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `CustomerID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `DOB` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `Email` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `PreferencesCompleted` tinyint(1) DEFAULT 0,
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `Status` varchar(20) DEFAULT 'active',
  `SuspendedAt` datetime DEFAULT NULL,
  `suspension_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`CustomerID`, `Name`, `Username`, `DOB`, `phone`, `Email`, `address`, `PasswordHash`, `CreatedAt`, `PreferencesCompleted`, `reset_token_hash`, `reset_token_expires_at`, `Status`, `SuspendedAt`, `suspension_reason`) VALUES
(1, 'ahmad', 'kucing', '2024-12-10', '012345678', 'ahmad@gmail.com', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$R8kZJ59bRBeAPDlqo8ZDXuGaoSI9cx29z3cUgCGHGt.1Y.UvM7VwO', '2024-12-06 10:30:44', 0, NULL, NULL, 'active', NULL, NULL),
(6, 'Imran bin Jiwa', 'Imran', '2025-01-09', '0123456', 'imran@gmail.com', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$Ji6Z9k3j7LgoGsPrB6PWNeSfe7sbw7DqGm5eerNJ75rQb3ALf.eoe', '2025-01-20 10:31:56', 1, NULL, NULL, 'active', NULL, NULL),
(13, '', 'zulkarnain', NULL, '012345678', 'zul@gmail.com', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$igxF4KrYr.9QqIuJF3SQY.7j45o6crrg5jry/G20DXac0CSzijIC.', '2025-01-22 18:54:24', 1, NULL, NULL, 'active', NULL, NULL),
(17, '', 'test', NULL, '01234567', 'test@gmail.com', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$3NKqHH4int9PGvMm9fbuyeOYIDwq.8z7wMqHj8rLemfir5SIV9yZW', '2025-03-19 01:31:58', 0, NULL, NULL, 'active', NULL, NULL),
(23, '', 'sikat', NULL, '0123456789', 'sikat@gmail.com', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$/9rqJgODSGvNDAvHBnaUROvglzCkAdYbywTJ/D/qSmhcYPQR8TIuS', '2025-03-19 14:40:05', 1, NULL, NULL, 'active', NULL, NULL),
(27, 'Muhammad Dompet', 'dompet', '2025-05-31', '012345678', 'dompet@gmail.com', 'No. 12, Jalan Mawar 3,\r\nTaman Sri Indah,\r\n43000 Kajang,\r\nSelangor, Malaysia', '$2y$10$aFdtmZhR7qGdhVdFPMKnzuL/PbAdqMGVYyQQz4b7kpwnUJOsfgBzi', '2025-03-19 15:59:33', 1, NULL, NULL, 'active', NULL, NULL),
(28, 'Adibah binti Osman', 'dibo', '2000-01-21', '01234567', 'dibo@gmail.com', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$1dfc8KKmPLhsP6eKgGZZ0eTBKzFQNuMdIoUQTMc82K85JN5MmENq.', '2025-03-19 16:14:31', 1, NULL, NULL, 'suspended', '2025-06-09 01:33:44', 'spamming chat'),
(38, '', 'customerA', NULL, '012345678', 'customera@gmail.com', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$ahMRQHBoXdaWi4C1C1jlZOB4k/1RQud6jDzdekFVqhNZ6JD7x8VC2', '2025-05-07 15:52:33', 1, NULL, NULL, 'active', NULL, NULL),
(39, 'CustomerB', 'customerb', '', '0123456789', 'customerb@gmail.com', 'No. 57, Jalan Damai Perdana 2/2A, Taman Damai Perdana, 56000 Cheras, Kuala Lumpur, Malaysia', '$2y$10$3s8MdVs2.fXhTaU2q9T9XeIZuZvm4bTWesI7mvsNKuw1RCqOEevv2', '2025-05-07 15:59:38', 1, NULL, NULL, 'suspended', '2025-06-09 01:33:57', 'Fake review'),
(49, 'Muhammad Straw', 'straw', '', '012345678', 'straw@gmail.com', 'No. 27, Jalan Melur 3,\r\nTaman Melawati,\r\n53100 Kuala Lumpur,\r\nWilayah Persekutuan, Malaysia', '$2y$10$VjORju90QycndSrbXUPqm.uolvHkFjIk/hsmWqPRzg9Vf7uPd0FVC', '2025-05-21 02:55:13', 1, NULL, NULL, 'active', NULL, 'buying too much perfume'),
(53, '', 'toyol', NULL, '012345678', 'toyol@gmail.com', NULL, '$2y$10$iOBtRGAYDagwIsN84uOUpOBV9CKWKqf8lG7ia/tcKZq0Apg1pWALS', '2025-05-27 02:12:19', 0, NULL, NULL, 'active', NULL, NULL),
(54, 'Muhammad Harvey', 'Harvey', '2025-05-27', '012345678', 'harvey@gmail.com', 'No. 23, Jalan Anggerik Vanilla,\r\nKota Kemuning, Seksyen 31,\r\n40460 Shah Alam, Selangor,\r\nMalaysia.', '$2y$10$FeKYJ1NO//O8Y01rJEVPLeqsdFS6n1ifcNVC2B81aNbiOsjYFMQEG', '2025-05-27 03:14:24', 1, NULL, NULL, 'active', NULL, NULL),
(57, '', 'power', NULL, '012345678', 'power@gmail.com', NULL, '$2y$10$HG39AbQAgJ560gtxbQzvFu.meVps8q5b4hauhqTQ8YYlPnjMm/9Wu', '2025-06-03 02:34:32', 0, NULL, NULL, 'active', NULL, NULL),
(58, 'user bin test', 'usertest', '2025-06-03', '012345678', 'usertest@gmail.com', 'Jalan meranti 23/4 Taman Sri Indah', '$2y$10$/B.R5B0iYhjccLAR9IbxBeDAMpL2CDN7Fa.7WorsLfG/u4FzIxvxW', '2025-06-03 09:31:56', 1, NULL, NULL, 'active', NULL, NULL),
(59, 'test1 bin Abdullah', 'test1', '2025-06-01', '012345678', 'test1@gmail.com', '45 Jalan Merak 4/3 Taman Sri Indah', '$2y$10$Naeh.aFMqtDwYW/SCGlGzeKBUc5mmeBNClqCfqFGt.4AYZqWx25ye', '2025-06-03 15:05:55', 1, NULL, NULL, 'active', NULL, NULL),
(60, '', 'kotak', NULL, '012345678', 'kotak@gmail.com', NULL, '$2y$10$65WrmfSE9M6giynLWNDsE.el4cqMcssjyIuILjQzaGXgnqPhyax.S', '2025-06-04 01:30:00', 1, NULL, NULL, 'active', NULL, NULL),
(61, 'Samsung bin Android', 'samsung', '2003-05-12', '123456789', 'samsung@gmail.com', 'No. 27, Jalan Damai 3,\r\nTaman Damai,\r\n43000 Kajang,\r\nSelangor, Malaysia', '$2y$10$IGh3m2ckd90Fw1krBjEA9ueJzL.0Q2PVEDSybZcG19O2.16ujIJ9K', '2025-06-07 08:50:49', 1, NULL, NULL, 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL COMMENT 'NULL if feedback from guest',
  `seller_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `admin_response` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `status` enum('new','in_progress','resolved','rejected') NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `feedback_source` enum('customer','seller') NOT NULL DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `customer_id`, `seller_id`, `subject`, `message`, `rating`, `admin_response`, `resolved_at`, `status`, `created_at`, `feedback_source`) VALUES
(267, 39, NULL, 'product', 'oqeirgnojebg', 2, 'done', '2025-05-10 14:03:27', 'resolved', '2025-05-10 13:59:00', 'customer'),
(269, 27, NULL, 'design', 'getting better the design', 4, NULL, NULL, 'new', '2025-05-20 08:17:23', 'customer'),
(270, 27, NULL, 'suggestion - Design', 'hahahaa', 3, NULL, NULL, 'new', '2025-05-21 15:41:37', 'customer'),
(273, 27, NULL, 'complaint - Bosan', 'saje try je be', 2, NULL, NULL, 'new', '2025-05-21 15:54:01', 'customer'),
(274, 27, NULL, 'suggestion - Design', 'Good Improvement tho....', 3, NULL, NULL, 'new', '2025-05-21 16:01:30', 'customer'),
(276, 27, NULL, 'complaint - yhv6yh', '6yvh6', 4, NULL, NULL, 'new', '2025-05-23 13:19:31', 'customer'),
(279, 27, NULL, 'complaint - try lagi', 'rfrfdgrdge gefe', 5, 'Done', '2025-05-31 15:17:34', 'resolved', '2025-05-23 13:30:18', 'customer'),
(282, 27, NULL, 'praise - naiseee', 'well done', 5, 'Thank you brother', '2025-05-23 15:53:22', 'resolved', '2025-05-23 13:33:31', 'customer'),
(289, 58, NULL, 'praise - Goodjob', 'Nice work', 5, 'dadadadadad', '2025-06-04 01:40:15', 'resolved', '2025-06-03 09:45:12', 'customer'),
(290, 61, NULL, 'praise - Design', 'Nice design', 4, NULL, NULL, 'new', '2025-06-07 09:38:50', 'customer'),
(292, NULL, 4, 'Other', 'Thank you for the customer ban', 4, 'Welcome', '2025-06-10 04:31:23', 'resolved', '2025-06-08 18:10:22', 'seller');

-- --------------------------------------------------------

--
-- Table structure for table `lifestyle`
--

CREATE TABLE `lifestyle` (
  `LifestyleID` int(11) NOT NULL,
  `Description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lifestyle`
--

INSERT INTO `lifestyle` (`LifestyleID`, `Description`) VALUES
(1, 'Relaxing at a beach'),
(2, 'Enjoying a luxury dinner'),
(3, 'Hiking in nature'),
(4, 'Partying with friends'),
(5, 'Cozying up with a book');

-- --------------------------------------------------------

--
-- Table structure for table `manage_order`
--

CREATE TABLE `manage_order` (
  `OrderID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT 1,
  `Subtotal` decimal(10,2) NOT NULL,
  `TotalPrice` decimal(10,2) NOT NULL,
  `PaymentStatus` enum('Unpaid','Paid','Failed','Refunded') DEFAULT 'Unpaid',
  `Paid_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `Delivery_date` varchar(255) DEFAULT NULL,
  `ShipmentStatus` enum('Preparing','Shipped','Delivered','Completed','Cancelled') DEFAULT 'Preparing',
  `TrackingNumber` varchar(50) DEFAULT NULL,
  `CancelReason` varchar(255) DEFAULT NULL,
  `OtherReason` text DEFAULT NULL,
  `DeliveryType` enum('Pos Laju','J&T Express','DHL','Ninja Van','Shopee Xpress') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manage_order`
--

INSERT INTO `manage_order` (`OrderID`, `CustomerID`, `ProductID`, `Quantity`, `Subtotal`, `TotalPrice`, `PaymentStatus`, `Paid_time`, `Delivery_date`, `ShipmentStatus`, `TrackingNumber`, `CancelReason`, `OtherReason`, `DeliveryType`) VALUES
(9, 6, 61, 1, 32.00, 32.00, 'Unpaid', '2025-01-20 10:34:09', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(13, 13, 58, 1, 27.98, 27.98, 'Unpaid', '2025-01-22 19:04:57', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(14, 13, 61, 1, 32.00, 32.00, 'Unpaid', '2025-01-22 19:05:05', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(27, 17, 59, 1, 99.00, 99.00, 'Paid', '2025-03-19 01:35:17', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(30, 28, 58, 1, 27.98, 27.98, 'Paid', '2025-03-19 17:02:51', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(31, 28, 68, 4, 112.00, 112.00, 'Paid', '2025-03-19 17:02:51', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(32, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-03-19 17:28:22', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(33, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-03-19 17:33:20', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(34, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-03-19 17:33:59', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(36, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-03-19 17:36:03', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(37, 28, 58, 1, 27.98, 27.98, 'Paid', '2025-03-19 17:46:50', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(38, 28, 58, 1, 27.98, 27.98, 'Paid', '2025-03-19 17:51:52', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(39, 28, 58, 1, 27.98, 27.98, 'Paid', '2025-03-19 17:52:29', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(41, 28, 58, 1, 27.98, 27.98, 'Paid', '2025-03-19 17:52:57', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(46, 28, 68, 1, 28.00, 28.00, 'Paid', '2025-04-08 13:40:50', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(49, 28, 68, 1, 28.00, 28.00, 'Paid', '2025-04-08 13:42:13', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(50, 28, 64, 1, 38.00, 38.00, 'Paid', '2025-04-08 13:42:13', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(52, 28, 58, 2, 55.96, 55.96, 'Paid', '2025-04-08 16:14:46', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(54, 28, 68, 1, 28.00, 28.00, 'Paid', '2025-04-08 16:16:27', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(71, 1, 58, 1, 27.98, 27.98, 'Paid', '2025-04-10 08:51:28', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(72, 1, 68, 1, 28.00, 28.00, 'Paid', '2025-04-10 08:53:08', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(73, 1, 67, 1, 35.00, 35.00, 'Paid', '2025-04-10 08:55:37', NULL, 'Completed', NULL, NULL, NULL, NULL),
(74, 1, 59, 1, 99.00, 99.00, 'Paid', '2025-04-10 09:08:03', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(75, 1, 61, 1, 32.00, 32.00, 'Paid', '2025-04-10 09:14:00', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(76, 1, 60, 1, 89.00, 89.00, 'Paid', '2025-04-10 09:45:59', NULL, 'Completed', NULL, NULL, NULL, NULL),
(77, 1, 58, 1, 27.98, 27.98, 'Paid', '2025-04-11 07:57:21', NULL, 'Completed', NULL, NULL, NULL, NULL),
(78, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-04-16 03:47:36', NULL, 'Completed', NULL, NULL, NULL, NULL),
(79, 28, 61, 1, 32.00, 32.00, 'Paid', '2025-04-23 15:20:20', NULL, 'Cancelled', NULL, NULL, NULL, NULL),
(80, 28, 64, 1, 38.00, 38.00, 'Paid', '2025-04-28 13:47:22', NULL, 'Completed', NULL, NULL, NULL, NULL),
(81, 28, 67, 1, 35.00, 35.00, 'Paid', '2025-04-28 14:27:40', NULL, 'Delivered', 'ORD-680F905C0D2F8', NULL, NULL, NULL),
(82, 28, 67, 1, 35.00, 35.00, 'Paid', '2025-04-28 14:27:44', NULL, 'Cancelled', 'ORD-680F90605C1FD', NULL, NULL, NULL),
(83, 28, 67, 1, 35.00, 35.00, 'Paid', '2025-04-28 14:28:20', NULL, 'Cancelled', 'ORD-680F9083520A8', NULL, NULL, NULL),
(84, 28, 64, 1, 38.00, 38.00, 'Paid', '2025-04-28 14:31:41', NULL, 'Delivered', NULL, NULL, NULL, NULL),
(85, 28, 64, 1, 38.00, 38.00, 'Paid', '2025-04-28 14:46:42', NULL, 'Completed', NULL, NULL, NULL, NULL),
(86, 28, 64, 1, 38.00, 38.00, 'Paid', '2025-04-28 14:57:30', NULL, 'Delivered', NULL, NULL, NULL, NULL),
(87, 28, 64, 1, 38.00, 38.00, 'Paid', '2025-04-28 15:04:30', NULL, 'Delivered', NULL, NULL, NULL, NULL),
(88, 28, 65, 1, 10.00, 10.00, 'Paid', '2025-04-28 15:21:00', NULL, 'Completed', NULL, NULL, NULL, NULL),
(89, 28, 65, 1, 10.00, 10.00, 'Paid', '2025-04-28 15:22:05', NULL, 'Preparing', NULL, NULL, NULL, NULL),
(90, 28, 58, 1, 27.98, 27.98, 'Paid', '2025-04-28 17:55:55', NULL, 'Completed', NULL, NULL, NULL, NULL),
(91, 28, 68, 1, 28.00, 28.00, 'Paid', '2025-04-29 08:14:21', '2025-05-03', 'Preparing', 'ORDERA5D80A31', NULL, NULL, NULL),
(92, 28, 68, 1, 28.00, 28.00, 'Paid', '2025-04-29 08:38:20', '2025-04-30', 'Cancelled', 'ORDERFFC9E732', NULL, NULL, NULL),
(93, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-05-02 06:27:07', '2025-05-03', 'Shipped', 'ORDER5BB3DE67', NULL, NULL, NULL),
(94, 28, 64, 2, 76.00, 76.00, 'Paid', '2025-05-06 06:17:48', '2025-05-11', 'Cancelled', 'ORDER98C570C0', NULL, NULL, NULL),
(97, 38, 59, 4, 396.00, 396.00, 'Paid', '2025-05-07 17:33:08', '2025-05-09', 'Shipped', 'ORDER95463094', NULL, NULL, NULL),
(98, 38, 67, 5, 175.00, 175.00, 'Paid', '2025-05-07 17:33:08', '2025-05-09', 'Cancelled', 'ORDER95463094', NULL, NULL, NULL),
(99, 38, 65, 3, 30.00, 10.00, 'Paid', '2025-05-07 17:35:15', '2025-05-10', 'Delivered', 'ORDER9F617530', NULL, NULL, NULL),
(100, 39, 74, 1, 120.00, 120.00, 'Paid', '2025-05-09 15:31:57', '2025-05-16', 'Cancelled', 'ORDER00C194BD', NULL, NULL, NULL),
(101, 39, 75, 2, 66.00, 66.00, 'Paid', '2025-05-09 15:32:07', '2025-05-16', 'Completed', 'ORDER00C194BD', NULL, NULL, NULL),
(102, 39, 74, 1, 120.00, 120.00, 'Paid', '2025-05-09 15:37:28', '2025-05-14', 'Completed', 'ORDER138E27A1', NULL, NULL, NULL),
(111, 39, 60, 1, 89.00, 89.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Shipped', 'ORDER2369436A', NULL, NULL, NULL),
(112, 39, 59, 2, 198.00, 198.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Shipped', 'ORDER2369436A', NULL, NULL, NULL),
(113, 39, 65, 2, 28.00, 28.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Cancelled', 'ORDER2369436A', NULL, NULL, NULL),
(114, 39, 74, 1, 120.00, 120.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Shipped', 'ORDER2369436A', NULL, NULL, NULL),
(115, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Completed', 'ORDER2369436A', NULL, NULL, NULL),
(116, 39, 70, 2, 5.00, 5.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Completed', 'ORDER2369436A', NULL, NULL, NULL),
(117, 39, 63, 1, 44.00, 44.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Completed', 'ORDER2369436A', NULL, NULL, NULL),
(118, 39, 73, 1, 99.00, 99.00, 'Paid', '2025-05-19 06:39:50', '2025-05-24', 'Completed', 'ORDER2369436A', NULL, NULL, NULL),
(119, 39, 58, 1, 27.98, 27.98, 'Paid', '2025-05-19 08:08:10', NULL, 'Completed', NULL, NULL, NULL, NULL),
(120, 39, 58, 1, 27.98, 27.98, 'Paid', '2025-05-19 08:08:47', '2025-05-25', 'Cancelled', 'ORDER70FC02E9', NULL, NULL, NULL),
(125, 28, 70, 1, 2.38, 2.38, 'Paid', '2025-05-19 16:33:25', '2025-05-20', 'Delivered', 'ORDERD5501524', NULL, NULL, NULL),
(126, 28, 71, 1, 23.04, 23.04, 'Paid', '2025-05-19 16:33:25', '2025-05-20', 'Shipped', 'ORDERD5501524', NULL, NULL, NULL),
(127, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-05-19 16:33:25', '2025-05-20', 'Preparing', 'ORDERD5501524', NULL, NULL, NULL),
(128, 28, 67, 1, 35.00, 35.00, 'Paid', '2025-05-19 16:33:25', '2025-05-20', 'Cancelled', 'ORDERD5501524', NULL, NULL, NULL),
(129, 28, 59, 3, 277.20, 99.00, 'Unpaid', '2025-05-19 16:36:42', NULL, 'Delivered', NULL, NULL, NULL, NULL),
(130, 28, 61, 1, 32.00, 32.00, 'Unpaid', '2025-05-19 16:38:22', NULL, 'Delivered', NULL, NULL, NULL, NULL),
(131, 28, 59, 1, 99.00, 99.00, 'Paid', '2025-05-19 16:38:48', '2025-05-26', 'Shipped', 'ORDERE98BA352', NULL, NULL, NULL),
(132, 28, 61, 1, 32.00, 32.00, 'Paid', '2025-05-19 16:38:48', '2025-05-26', 'Preparing', 'ORDERE98BA352', NULL, NULL, NULL),
(134, 39, 67, 4, 140.00, 140.00, 'Paid', '2025-05-19 16:43:26', '2025-05-21', 'Completed', 'ORDERFAEEA44F', NULL, NULL, NULL),
(136, 39, 66, 3, 105.00, 105.00, 'Paid', '2025-05-19 16:49:04', '2025-05-21', 'Completed', 'ORDER10026F74', NULL, NULL, NULL),
(138, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-05-19 16:59:38', '2025-05-23', 'Completed', 'ORDER37A9DAE2', NULL, NULL, NULL),
(139, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-05-19 17:12:29', '2025-05-23', 'Cancelled', 'ORDER67D314E1', NULL, NULL, NULL),
(140, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-05-19 17:14:56', '2025-05-24', 'Cancelled', 'ORDER710B9355', NULL, NULL, NULL),
(141, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-05-19 17:17:11', '2025-05-22', 'Delivered', 'ORDER797CCBEB', NULL, NULL, NULL),
(143, 39, 58, 2, 55.96, 55.96, 'Paid', '2025-05-19 17:19:45', '2025-05-26', 'Completed', 'ORDER831B992C', NULL, NULL, NULL),
(146, 39, 60, 1, 89.00, 89.00, 'Paid', '2025-05-19 17:21:01', '2025-05-25', 'Shipped', 'ORDER87D954D7', NULL, NULL, NULL),
(147, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-19 17:21:01', '2025-05-25', 'Delivered', 'ORDER87D954D7', NULL, NULL, NULL),
(150, 39, 66, 1, 35.00, 35.00, 'Paid', '2025-05-19 17:23:17', '2025-05-24', 'Completed', 'ORDER90532E6A', NULL, NULL, NULL),
(152, 39, 60, 1, 89.00, 89.00, 'Paid', '2025-05-19 17:23:58', '2025-05-22', 'Shipped', 'ORDER92E43D64', NULL, NULL, NULL),
(153, 39, 60, 1, 89.00, 89.00, 'Paid', '2025-05-19 17:28:01', '2025-05-25', 'Completed', 'ORDERA215A638', NULL, NULL, NULL),
(155, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-19 17:28:57', '2025-05-22', 'Delivered', 'ORDERA598B13D', NULL, NULL, NULL),
(157, 39, 74, 1, 120.00, 120.00, 'Paid', '2025-05-19 17:30:30', '2025-05-21', 'Cancelled', 'ORDERAB624DFD', NULL, NULL, NULL),
(158, 39, 74, 1, 120.00, 120.00, 'Paid', '2025-05-19 17:34:05', '2025-05-20', 'Delivered', 'ORDERB8D9C788', NULL, NULL, NULL),
(160, 39, 63, 1, 44.00, 44.00, 'Paid', '2025-05-19 17:35:15', '2025-05-26', 'Cancelled', 'ORDERBD3664ED', NULL, NULL, NULL),
(162, 39, 64, 2, 76.00, 76.00, 'Paid', '2025-05-19 17:41:35', '2025-05-22', 'Cancelled', 'ORDERD4F46D3F', 'change_of_mind', '', NULL),
(164, 39, 65, 1, 14.00, 14.00, 'Paid', '2025-05-19 17:43:41', '2025-05-26', 'Cancelled', 'ORDERDCDB292F', NULL, NULL, NULL),
(165, 39, 65, 1, 14.00, 14.00, 'Paid', '2025-05-19 17:44:10', '2025-05-26', 'Cancelled', 'ORDERDEA7D858', NULL, NULL, NULL),
(169, 39, 65, 1, 12.88, 12.88, 'Paid', '2025-05-19 17:49:41', '2025-05-24', 'Cancelled', 'ORDERF35CE0EB', NULL, NULL, NULL),
(170, 39, 61, 1, 32.00, 32.00, 'Paid', '2025-05-19 17:49:41', '2025-05-24', 'Cancelled', 'ORDERF35CE0EB', NULL, NULL, NULL),
(171, 39, 65, 1, 12.88, 12.88, 'Paid', '2025-05-19 17:54:04', '2025-05-22', 'Cancelled', 'ORDER03CD2BB0', NULL, NULL, NULL),
(172, 39, 61, 1, 32.00, 32.00, 'Paid', '2025-05-19 17:54:04', '2025-05-22', 'Shipped', 'ORDER03CD2BB0', NULL, NULL, NULL),
(174, 39, 73, 2, 198.00, 198.00, 'Paid', '2025-05-19 17:54:43', '2025-05-20', 'Cancelled', 'ORDER06317969', NULL, NULL, NULL),
(176, 39, 59, 1, 99.00, 99.00, 'Paid', '2025-05-19 17:58:51', '2025-05-22', 'Cancelled', 'ORDER15B9AA82', NULL, NULL, NULL),
(178, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-19 18:00:44', '2025-05-22', 'Delivered', 'ORDER1CC995A7', NULL, NULL, NULL),
(180, 39, 71, 1, 23.04, 23.04, 'Paid', '2025-05-19 18:08:00', '2025-05-21', 'Cancelled', 'ORDER3803818B', NULL, NULL, NULL),
(182, 39, 65, 1, 12.88, 12.88, 'Paid', '2025-05-19 18:12:47', '2025-05-25', 'Completed', 'ORDER49FBAD31', NULL, NULL, NULL),
(192, 27, 58, 1, 27.98, 27.98, 'Paid', '2025-05-20 12:42:34', '2025-05-24', 'Preparing', 'ORDER8BAD2E7D', NULL, NULL, NULL),
(193, 27, 61, 3, 96.00, 96.00, 'Paid', '2025-05-20 12:42:34', '2025-05-24', 'Preparing', 'ORDER8BAD2E7D', NULL, NULL, NULL),
(196, 27, 58, 1, 27.98, 27.98, 'Paid', '2025-05-20 12:52:18', '2025-05-21', 'Preparing', 'ORDERB0209C86', NULL, NULL, NULL),
(197, 27, 68, 3, 84.00, 84.00, 'Paid', '2025-05-20 12:52:18', '2025-05-21', 'Completed', 'ORDERB0209C86', NULL, NULL, NULL),
(200, 27, 59, 1, 99.00, 99.00, 'Paid', '2025-05-20 12:54:26', '2025-05-27', 'Preparing', 'ORDERB82C6F97', NULL, NULL, NULL),
(201, 27, 67, 2, 70.00, 70.00, 'Paid', '2025-05-20 12:54:26', '2025-05-27', 'Completed', 'ORDERB82C6F97', NULL, NULL, NULL),
(204, 27, 63, 2, 88.00, 88.00, 'Paid', '2025-05-20 12:59:21', '2025-05-27', 'Completed', 'ORDERCA90E966', NULL, NULL, NULL),
(205, 27, 61, 1, 32.00, 32.00, 'Paid', '2025-05-20 12:59:21', '2025-05-27', 'Preparing', 'ORDERCA90E966', NULL, NULL, NULL),
(208, 27, 68, 1, 28.00, 28.00, 'Paid', '2025-05-20 13:02:55', '2025-05-22', 'Completed', 'ORDERD7F074A6', NULL, NULL, NULL),
(209, 27, 74, 1, 120.00, 120.00, 'Paid', '2025-05-20 13:02:55', '2025-05-22', 'Completed', 'ORDERD7F074A6', NULL, NULL, NULL),
(212, 27, 67, 2, 70.00, 70.00, 'Paid', '2025-05-20 13:04:52', '2025-05-21', 'Completed', 'ORDERDF497802', NULL, NULL, NULL),
(213, 27, 60, 1, 89.00, 89.00, 'Paid', '2025-05-20 13:04:52', '2025-05-21', 'Preparing', 'ORDERDF497802', NULL, NULL, NULL),
(214, 27, 64, 1, 38.00, 38.00, 'Paid', '2025-05-20 13:05:42', '2025-05-27', 'Completed', 'ORDERE26E09D3', NULL, NULL, NULL),
(217, 27, 58, 1, 27.98, 27.98, 'Paid', '2025-05-20 13:14:46', '2025-05-25', 'Preparing', 'ORDER0461144E', NULL, NULL, NULL),
(218, 27, 61, 1, 32.00, 32.00, 'Paid', '2025-05-20 13:14:46', '2025-05-25', 'Preparing', 'ORDER0461144E', NULL, NULL, NULL),
(221, 27, 75, 3, 99.00, 99.00, 'Paid', '2025-05-20 13:18:23', '2025-05-25', 'Completed', 'ORDER11FA773A', NULL, NULL, NULL),
(222, 27, 60, 1, 89.00, 89.00, 'Paid', '2025-05-20 13:18:23', '2025-05-25', 'Preparing', 'ORDER11FA773A', NULL, NULL, NULL),
(224, 27, 63, 2, 88.00, 88.00, 'Paid', '2025-05-20 13:21:42', '2025-05-23', 'Completed', 'ORDER1E668DF4', NULL, NULL, NULL),
(227, 27, 58, 1, 27.98, 27.98, 'Paid', '2025-05-20 13:31:28', '2025-05-26', 'Cancelled', 'ORDER430CAA26', NULL, NULL, NULL),
(229, 27, 59, 1, 99.00, 99.00, 'Paid', '2025-05-20 13:39:22', '2025-05-26', 'Cancelled', 'ORDER60A03F8B', NULL, NULL, NULL),
(230, 27, 59, 1, 99.00, 99.00, 'Paid', '2025-05-20 13:41:54', '2025-05-27', 'Cancelled', 'ORDER6A20DED2', NULL, NULL, NULL),
(232, 27, 58, 1, 27.98, 27.98, 'Paid', '2025-05-20 13:42:37', '2025-05-26', 'Preparing', 'ORDER6CDA2961', NULL, NULL, NULL),
(234, 27, 59, 1, 99.00, 99.00, 'Paid', '2025-05-20 14:10:57', '2025-05-22', 'Cancelled', 'ORDERD71602A8', NULL, NULL, NULL),
(235, 27, 59, 1, 99.00, 99.00, 'Paid', '2025-05-20 14:14:54', '2025-05-25', 'Cancelled', 'ORDERE5E1F1BB', NULL, NULL, NULL),
(241, 39, 59, 1, 99.00, 99.00, 'Paid', '2025-05-20 14:32:04', '2025-05-27', 'Preparing', 'ORDER264B9A58', NULL, NULL, NULL),
(242, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-20 14:32:04', '2025-05-27', 'Cancelled', 'ORDER264B9A58', NULL, NULL, NULL),
(243, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-05-20 14:32:04', '2025-05-27', 'Completed', 'ORDER264B9A58', NULL, NULL, NULL),
(247, 39, 71, 1, 23.04, 23.04, 'Paid', '2025-05-20 14:35:00', '2025-05-25', 'Completed', 'ORDER314CE1CE', NULL, NULL, NULL),
(249, 39, 70, 1, 2.38, 2.38, 'Paid', '2025-05-20 14:39:37', '2025-05-27', 'Completed', 'ORDER429AE42B', NULL, NULL, NULL),
(250, 39, 71, 1, 23.04, 23.04, 'Paid', '2025-05-20 14:39:37', '2025-05-27', 'Completed', 'ORDER429AE42B', NULL, NULL, NULL),
(251, 39, 60, 1, 89.00, 89.00, 'Paid', '2025-05-20 14:39:37', '2025-05-27', 'Preparing', 'ORDER429AE42B', NULL, NULL, NULL),
(252, 39, 59, 1, 99.00, 99.00, 'Paid', '2025-05-20 14:39:37', '2025-05-27', 'Preparing', 'ORDER429AE42B', NULL, NULL, NULL),
(253, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-20 14:39:37', '2025-05-27', 'Completed', 'ORDER429AE42B', NULL, NULL, NULL),
(254, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-05-20 14:39:37', '2025-05-27', 'Completed', 'ORDER429AE42B', NULL, NULL, NULL),
(257, 39, 71, 1, 23.04, 23.04, 'Paid', '2025-05-20 14:44:40', '2025-05-25', 'Completed', 'ORDER55898CD0', NULL, NULL, NULL),
(258, 39, 60, 1, 89.00, 89.00, 'Paid', '2025-05-20 14:44:40', '2025-05-25', 'Preparing', 'ORDER55898CD0', NULL, NULL, NULL),
(264, 39, 59, 1, 89.10, 89.10, 'Paid', '2025-05-20 15:04:46', '2025-05-25', 'Preparing', 'ORDERA0E0678B', NULL, NULL, NULL),
(265, 39, 74, 1, 90.00, 90.00, 'Paid', '2025-05-20 15:04:46', '2025-05-25', 'Completed', 'ORDERA0E0678B', NULL, NULL, NULL),
(266, 39, 73, 1, 86.13, 86.13, 'Paid', '2025-05-20 15:04:46', '2025-05-25', 'Completed', 'ORDERA0E0678B', NULL, NULL, NULL),
(268, 39, 70, 1, 2.50, 2.50, 'Paid', '2025-05-20 15:29:42', '2025-05-23', 'Completed', 'ORDERFE6D59CD', NULL, NULL, NULL),
(270, 39, 73, 1, 86.13, 86.13, 'Paid', '2025-05-20 15:32:37', '2025-05-24', 'Completed', 'ORDER095F287C', NULL, NULL, NULL),
(271, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-20 15:46:20', '2025-05-26', 'Cancelled', 'ORDER3CC8A4D5', NULL, NULL, NULL),
(276, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-20 15:46:20', '2025-05-26', 'Completed', 'ORDER3CC8A4D5', NULL, NULL, NULL),
(277, 39, 59, 1, 89.10, 89.10, 'Paid', '2025-05-20 15:47:18', '2025-05-24', 'Cancelled', 'ORDER4065FDE1', NULL, NULL, NULL),
(278, 39, 59, 1, 89.10, 89.10, 'Paid', '2025-05-20 15:47:18', '2025-05-24', 'Preparing', 'ORDER4065FDE1', NULL, NULL, NULL),
(279, 39, 60, 1, 75.65, 75.65, 'Paid', '2025-05-20 15:50:19', '2025-05-23', 'Delivered', 'ORDER4BB85C62', NULL, NULL, NULL),
(280, 39, 68, 1, 28.00, 28.00, 'Paid', '2025-05-20 15:51:15', '2025-05-25', 'Cancelled', 'ORDER4F388C44', NULL, NULL, NULL),
(282, 49, 59, 1, 89.10, 89.10, 'Paid', '2025-05-21 03:04:56', '2025-05-23', 'Cancelled', 'ORDER2D896E5B', NULL, NULL, NULL),
(284, 53, 77, 1, 53.00, 53.00, 'Unpaid', '2025-05-27 02:19:04', NULL, '', NULL, NULL, NULL, NULL),
(285, 27, 77, 1, 53.00, 53.00, 'Paid', '2025-05-27 02:19:55', '2025-05-29', 'Shipped', 'ORDER14B1C376', NULL, NULL, NULL),
(286, 27, 79, 1, 79.99, 79.99, 'Paid', '2025-05-27 02:32:58', '2025-05-29', 'Preparing', 'ORDER45AA3B0D', NULL, NULL, NULL),
(287, 49, 79, 1, 79.99, 79.99, 'Paid', '2025-05-27 02:46:55', '2025-05-28', 'Preparing', 'ORDER79FE7ABA', NULL, NULL, NULL),
(288, 54, 79, 1, 79.99, 79.99, 'Paid', '2025-05-27 03:19:14', '2025-05-31', 'Preparing', 'ORDERF328E7E1', NULL, NULL, NULL),
(289, 54, 79, 1, 79.99, 79.99, 'Paid', '2025-05-27 03:22:36', '2025-05-31', 'Preparing', 'ORDERFFC26458', NULL, NULL, NULL),
(290, 27, 73, 2, 172.26, 172.26, 'Paid', '2025-05-31 08:33:03', '2025-06-02', 'Completed', 'ORDEREBFD0B8F', NULL, NULL, 'Pos Laju'),
(291, 27, 61, 1, 32.00, 32.00, 'Paid', '2025-05-31 08:33:03', '2025-06-02', 'Completed', 'ORDEREBFD0B8F', NULL, NULL, 'Pos Laju'),
(293, 39, 84, 1, 79.00, 79.00, 'Paid', '2025-06-01 05:58:35', '2025-06-06', 'Preparing', 'ORDERC0B7B231', NULL, NULL, 'DHL'),
(294, 39, 86, 1, 55.00, 55.00, 'Paid', '2025-06-01 07:32:25', '2025-06-02', 'Preparing', 'ORDER209985FC', NULL, NULL, 'Ninja Van'),
(295, 39, 75, 1, 33.00, 33.00, 'Paid', '2025-06-01 07:32:25', '2025-06-02', 'Cancelled', 'ORDER209985FC', NULL, NULL, 'Ninja Van'),
(296, 39, 85, 2, 140.00, 140.00, 'Paid', '2025-06-01 07:32:25', '2025-06-02', 'Preparing', 'ORDER209985FC', NULL, NULL, 'Ninja Van'),
(298, 27, 84, 1, 79.00, 79.00, 'Paid', '2025-06-02 07:17:50', '2025-06-09', 'Cancelled', 'ORDER01E21200', NULL, NULL, 'DHL'),
(299, 39, 67, 1, 35.00, 35.00, 'Paid', '2025-06-02 08:38:48', '2025-06-04', 'Shipped', 'ORDER3187F73A', NULL, NULL, 'Pos Laju'),
(300, 39, 86, 1, 55.00, 55.00, 'Paid', '2025-06-02 08:46:41', '2025-06-05', 'Preparing', 'ORDER4F17E1F8', NULL, NULL, 'Pos Laju'),
(301, 39, 71, 1, 24.00, 24.00, 'Paid', '2025-06-02 08:50:21', '2025-06-03', 'Shipped', 'ORDER5CD8CB55', NULL, NULL, 'Pos Laju'),
(303, 27, 84, 1, 69.52, 69.52, 'Paid', '2025-06-03 12:18:45', '2025-06-10', 'Cancelled', 'ORDER825A6B21', NULL, NULL, 'DHL'),
(304, 58, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 09:40:08', '2025-06-10', 'Shipped', 'ORDER2F84D7EF', NULL, NULL, 'Ninja Van'),
(305, 58, 60, 2, 151.30, 151.30, 'Paid', '2025-06-03 09:48:56', '2025-06-10', 'Shipped', 'ORDER50830DC9', NULL, NULL, 'J&T Express'),
(306, 58, 74, 2, 114.84, 114.84, 'Paid', '2025-06-03 09:48:56', '2025-06-10', 'Shipped', 'ORDER50830DC9', NULL, NULL, 'J&T Express'),
(307, 27, 88, 2, 79.98, 79.98, 'Paid', '2025-06-03 12:18:45', '2025-06-10', 'Completed', 'ORDER825A6B21', NULL, NULL, 'DHL'),
(308, 27, 77, 1, 53.00, 53.00, 'Paid', '2025-06-03 12:41:23', '2025-06-08', 'Cancelled', 'ORDERD7391BD2', NULL, NULL, 'Pos Laju'),
(309, 27, 73, 1, 48.50, 48.50, 'Paid', '2025-06-03 13:31:44', '2025-06-08', 'Cancelled', 'ORDER94040208', NULL, NULL, 'Pos Laju'),
(310, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 13:40:58', '2025-06-05', 'Cancelled', 'ORDERB6AF2443', NULL, NULL, 'Pos Laju'),
(311, 27, 60, 1, 75.65, 75.65, 'Paid', '2025-06-03 13:43:58', '2025-06-09', 'Cancelled', 'ORDERC1E043B3', NULL, NULL, 'Pos Laju'),
(312, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 13:49:00', '2025-06-10', 'Cancelled', 'ORDERD4C03321', NULL, NULL, 'Pos Laju'),
(313, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 13:49:41', '2025-06-09', 'Cancelled', 'ORDERD75070AF', NULL, NULL, 'Pos Laju'),
(314, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 13:52:48', '2025-06-06', 'Cancelled', 'ORDERE301F331', NULL, NULL, 'Pos Laju'),
(315, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 13:53:56', '2025-06-08', 'Cancelled', 'ORDERE74F20CC', NULL, NULL, 'Pos Laju'),
(316, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 13:54:30', '2025-06-04', 'Cancelled', 'ORDERE9602F9F', NULL, NULL, 'Pos Laju'),
(317, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 13:56:03', '2025-06-06', 'Cancelled', 'ORDEREF3A325D', NULL, NULL, 'Pos Laju'),
(318, 27, 58, 1, 27.98, 27.98, 'Paid', '2025-06-03 14:00:07', '2025-06-07', 'Preparing', 'ORDERFE7B6022', NULL, NULL, 'Pos Laju'),
(319, 27, 88, 1, 39.99, 39.99, 'Paid', '2025-06-03 14:00:25', '2025-06-05', 'Preparing', 'ORDERFF9418B4', NULL, NULL, 'Pos Laju'),
(320, 27, 60, 1, 75.65, 75.65, 'Paid', '2025-06-03 14:00:44', '2025-06-07', 'Preparing', 'ORDER00C60A37', NULL, NULL, 'Pos Laju'),
(321, 27, 60, 1, 75.65, 75.65, 'Paid', '2025-06-03 14:00:44', '2025-06-07', 'Cancelled', 'ORDER00C60A37', NULL, NULL, 'Pos Laju'),
(322, 27, 61, 1, 29.12, 29.12, 'Paid', '2025-06-03 14:01:33', '2025-06-07', 'Cancelled', 'ORDER03D43F40', NULL, NULL, 'Pos Laju'),
(323, 27, 61, 1, 29.12, 29.12, 'Paid', '2025-06-03 14:01:33', '2025-06-07', 'Preparing', 'ORDER03D43F40', NULL, NULL, 'Pos Laju'),
(324, 27, 60, 1, 75.65, 75.65, 'Paid', '2025-06-03 14:02:47', '2025-06-10', 'Preparing', 'ORDER087922DC', NULL, NULL, 'Pos Laju'),
(325, 27, 60, 1, 75.65, 75.65, 'Paid', '2025-06-03 14:02:47', '2025-06-10', 'Preparing', 'ORDER087922DC', NULL, NULL, 'Pos Laju'),
(326, 27, 82, 1, 89.00, 89.00, 'Paid', '2025-06-03 14:04:11', '2025-06-05', 'Preparing', 'ORDER0DBD5138', NULL, NULL, 'Pos Laju'),
(327, 27, 58, 1, 27.98, 27.98, 'Paid', '2025-06-03 14:04:28', '2025-06-05', 'Preparing', 'ORDER0ECD2574', NULL, NULL, 'Pos Laju'),
(328, 59, 73, 1, 48.50, 48.50, 'Paid', '2025-06-03 15:08:47', '2025-06-10', 'Shipped', 'ORDERFFFAB32A', NULL, NULL, 'DHL'),
(329, 59, 61, 2, 58.24, 58.24, 'Paid', '2025-06-03 15:09:26', '2025-06-09', 'Preparing', 'ORDER026441A9', NULL, NULL, 'Shopee Xpress'),
(330, 59, 63, 2, 88.00, 88.00, 'Paid', '2025-06-03 15:09:26', '2025-06-09', 'Delivered', 'ORDER026441A9', NULL, NULL, 'Shopee Xpress'),
(331, 61, 79, 1, 79.99, 79.99, 'Paid', '2025-06-07 09:10:48', '2025-06-13', 'Shipped', 'ORDER218A6230', NULL, NULL, 'Pos Laju'),
(332, 61, 88, 1, 39.99, 39.99, 'Paid', '2025-06-07 09:11:21', '2025-06-10', 'Completed', 'ORDER239BB552', NULL, NULL, 'Pos Laju'),
(333, 61, 84, 1, 69.52, 69.52, 'Paid', '2025-06-07 09:11:21', '2025-06-10', 'Preparing', 'ORDER239BB552', NULL, NULL, 'Pos Laju');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personality`
--

CREATE TABLE `personality` (
  `PersonalityID` int(11) NOT NULL,
  `PersonalityType` varchar(50) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `Emoji` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personality`
--

INSERT INTO `personality` (`PersonalityID`, `PersonalityType`, `Description`, `Emoji`) VALUES
(1, 'bold', 'Bold & Confident', '🔥'),
(2, 'calm', 'Calm & Natural', '🌿'),
(3, 'elegant', 'Elegant & Sophisticated', '💫'),
(4, 'mysterious', 'Mysterious & Enigmatic', '🎭'),
(5, 'playful', 'Energetic & Playful', '🌞');

-- --------------------------------------------------------

--
-- Table structure for table `preference`
--

CREATE TABLE `preference` (
  `preferenceID` int(11) NOT NULL,
  `customerID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `preference`
--

INSERT INTO `preference` (`preferenceID`, `customerID`, `questionID`) VALUES
(1, 5, 4),
(2, 6, 5),
(3, 7, 6),
(4, 8, 7),
(5, 9, 8),
(6, 11, 9),
(7, 12, 10),
(8, 13, 11),
(9, 14, 12),
(10, 15, 13),
(11, 18, 16),
(12, 19, 17),
(13, 20, 18),
(14, 21, 19),
(15, 22, 20),
(16, 23, 21),
(17, 24, 22),
(18, 25, 23),
(19, 26, 24),
(20, 27, 25),
(21, 28, 26),
(22, 29, 27),
(23, 32, 39),
(24, 33, 40),
(25, 34, 41),
(26, 35, 42),
(27, 28, 48),
(28, 28, 49),
(29, 28, 50),
(30, 28, 51),
(31, 28, 52),
(32, 28, 53),
(33, 28, 54),
(34, 38, 55),
(35, 38, 56),
(36, 39, 57),
(37, 39, 58),
(38, 42, 59),
(39, 43, 60),
(40, 44, 61),
(41, 45, 62),
(42, 46, 63),
(43, 47, 64),
(44, 47, 65),
(45, 48, 66),
(46, 49, 67),
(47, 27, 68),
(48, 50, 69),
(49, 51, 70),
(50, 52, 71),
(51, 54, 72),
(52, 55, 73),
(53, 27, 74),
(54, 27, 75),
(55, 56, 76),
(56, 58, 77),
(57, 59, 78),
(58, 60, 79),
(59, 61, 80);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_quantity` int(11) NOT NULL DEFAULT 0,
  `product_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` varchar(20) NOT NULL,
  `ConcentrationID` int(11) DEFAULT NULL,
  `scent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `seller_id`, `product_image`, `product_name`, `product_quantity`, `product_description`, `price`, `rating`, `created_at`, `gender`, `ConcentrationID`, `scent_id`) VALUES
(58, 5, 'szindore1.jpg', 'SWEATING IN HEAVEN', 19, 'A daring and exhilarating fragrance that captures the sensation of boundless energy and divine indulgence. With its vibrant and sensual aroma, this perfume embodies the thrill of living on the edge and the purity of celestial serenity. Perfect for those who dare to dream and live passionately.', 27.98, NULL, '2025-01-14 15:45:23', 'UniSex', 2, 3),
(59, 5, 'szindore4.jpg', 'METRO VIBES', 21, 'A contemporary and dynamic fragrance that captures the essence of a bustling urban lifestyle. With fresh, vibrant notes blended seamlessly with a sophisticated depth, it embodies the energy, elegance, and bold spirit of the modern city dweller. Ideal for individuals who exude confidence and thrive in fast-paced environments.', 99.00, NULL, '2025-01-14 15:47:49', 'UniSex', 3, 5),
(60, 5, 'szindore5.jpg', 'ONE NIGHT DATE', 21, 'A sultry and enchanting fragrance designed for those unforgettable nights. ONE NIGHT DATE is a captivating blend of warm and sensual notes that evoke romance and mystery. Perfect for intimate evenings, it lingers softly, leaving an irresistible trail of charm and allure.', 89.00, NULL, '2025-01-14 15:49:32', 'UniSex', 2, 1),
(61, 5, 'blanco2.jpeg', 'ALEX SVG by BLANCO FRAGRANCE', 4, 'ALEX SVG by BLANCO FRAGRANCE embodies confidence and charisma with its bold and invigorating blend of scents. Perfect for day-to-night transitions, this fragrance exudes sophistication and charm, making it ideal for the modern, self-assured individual. Its dynamic combination of refreshing and warm notes creates a lasting impression.', 32.00, NULL, '2025-01-14 15:50:57', 'Man', 3, 3),
(63, 4, '6843ff3a1dc6c.jpeg', 'ALEX SVG by BLANCO FRAGRANCE', 21, 'ALEX SVG by BLANCO FRAGRANCE is a dynamic and invigorating scent, perfect for those who embrace bold energy and refined sophistication. This fragrance combines crisp and fresh citrus accords with warm, sensual undertones, creating a versatile and memorable aroma. Designed to captivate attention and evoke confidence, ALEX SVG is ideal for both casual and formal settings.', 44.00, NULL, '2025-01-14 16:15:08', 'Man', 2, 2),
(64, 4, 'blanco3.jpeg', 'AQUA FRESH by BLANCO FRAGRANCE', 32, 'AQUA FRESH is a refreshing and invigorating fragrance that embodies the essence of the ocean breeze. This scent opens with bright and zesty top notes that seamlessly blend into aquatic and herbal accords, leaving a clean and crisp trail. Perfect for energizing your day, AQUA FRESH is ideal for casual wear and warm weather.', 30.00, NULL, '2025-01-14 16:16:41', 'Unisex', 3, 5),
(65, 4, 'blanco4.jpeg', 'AVEN BLANCO PERFUME PREMIUM', 24, 'AVEN BLANCO PERFUME PREMIUM exudes sophistication and elegance, designed for those who command attention with effortless charm. This premium fragrance blends refined woody and aromatic notes with a touch of citrus freshness, creating a perfectly balanced and luxurious scent that transitions seamlessly from day to night.', 14.00, NULL, '2025-01-14 16:18:23', 'Man', 5, 3),
(66, 4, 'blanco6.jpeg', 'BLUE.DC by BLANCO FRAGRANCE', 37, 'BLUE.DC is a refreshing and invigorating fragrance designed for the modern adventurer. It opens with crisp aquatic notes, complemented by a burst of zesty citrus. The heart reveals aromatic herbs, while the base is grounded with warm woody undertones, embodying a fresh yet masculine allure.', 35.99, NULL, '2025-01-14 16:19:33', 'Man', 3, 5),
(67, 4, 'blanco7.jpeg', 'BOSSY BOTTLE BLANCO FRAGRANCE', 19, 'BOSSY BOTTLE embodies strength and sophistication, crafted for those who command attention. Opening with fresh citrus notes, the fragrance transitions into a warm and spicy heart, perfectly balanced by a deep, woody base. This scent captures boldness and charisma in a bottle.', 35.00, NULL, '2025-01-14 16:21:09', 'Man', 2, 3),
(68, 4, 'szindore6.jpg', 'YARAA', 3, 'Indulge in the captivating scent of SZINDORE YARAA Perfume for Woman, a luxurious fragrance that will leave you feeling confident and elegant.\\r\\n\\r\\nElegant and Sensual: Experience a blend of notes that are both elegant and sensual, perfect for any occasion.', 28.00, NULL, '2025-01-14 16:24:33', 'Woman', 2, 1),
(69, 4, '683e6b235120a.jpg', 'Someone Blanco', 30, 'The bottle has a sleek, modern design with a frosted, sculptural texture. The gradient colors (pastel blue, pink, and white) give it a clean, fresh, and sophisticated look—suggesting a unisex or light scent, possibly floral, aquatic, or powdery.', 24.00, NULL, '2025-04-29 14:16:53', 'Man', 3, 2),
(70, 4, '683e6d00d679f.jpeg', 'Ellis Passion', 48, 'Szindore ELLIS PASSION is a unisex fragrance crafted by Szindore, known for its tropical and fruity scent profile. It features top notes of pineapple and passion fruit, heart notes of bergamot and cedar, and base notes of peony, amber, and citrus', 30.00, NULL, '2025-05-09 14:47:34', 'Woman', 3, 3),
(71, 4, '683e6d9d17bf3.jpg', 'Sultan of Ani', 26, 'Sultan of Ani by Szindore is a unisex extrait de parfum that blends the rich, spicy warmth of Nishane Ani with the fresh, woody brightness of Nishane Hacivat, resulting in a luxurious and versatile fragrance.', 24.00, NULL, '2025-05-09 14:50:20', 'Man', 1, 1),
(73, 4, '683e6f353117a.jpg', 'Jadory Blanco', 24, 'A sophisticated and elegant perfume inspired by luxury and refinement. Housed in a minimalist bottle, Jadorey blends floral, powdery, and slightly musky notes—perfect for those who appreciate a clean yet alluring scent. Ideal for everyday elegance or special occasions.', 48.50, NULL, '2025-05-09 14:54:02', 'Woman', 2, 1),
(74, 4, '683e71dc8b6e4.jpg', 'Rizwan Blanco', 21, 'Rizwan Blanco by Blanco Fragrance is a unisex scent that combines warm, woody, and slightly sweet notes, making it ideal for cooler weather. Its composition features top notes of pine needles and olibanum, heart notes of benzoin and incense, and a base of Atlas, Himalayan, and Virginia cedar. This fragrance offers a cozy and elegant aroma, suitable for both men and women seeking a distinctive scent.', 63.80, NULL, '2025-05-09 14:56:51', 'Woman', 1, 1),
(75, 4, '683e7d5c64744.jpg', 'The Sleek', 43, 'THE SLEEK is an amber fougère fragrance for men, inspired by Jean Paul Gaultier\'s Le Male Elixir. It opens with refreshing notes of lavender and mint, transitions into a warm heart of vanilla and benzoin, and settles into a rich base of honey, tonka bean, and tobacco. This scent offers a bold yet refined profile, making it suitable for the modern man who embraces his individuality.', 33.00, NULL, '2025-05-09 14:59:05', 'UniSex', 2, 1),
(76, 19, '683e7f0fdb1b5.jpg', 'Blue Laverne', 33, 'Blue Laverne by Laverne is a unisex Eau de Parfum that embodies the free spirit of nature. It opens with vibrant citrus notes, including lemon, bergamot, and mandarin orange, complemented by saffron and daisy. The heart reveals a blend of frankincense, jasmine, patchouli, and leather, leading to a base of frankincense and musk. \r\n\r\nThis fragrance offers a bold yet balanced profile, making it suitable for daily wear. Its longevity is notable, with lasting power of 8+ hours on the skin and even longer on clothing. ', 24.00, NULL, '2025-05-23 15:06:21', 'Woman', 2, 1),
(77, 21, '6835229fbb960.jpg', 'YSL Libre', 18, 'YSL Libre Eau de Parfum is a bold and unapologetically sensual fragrance that celebrates freedom and empowerment. Blending the elegance of French lavender with the fiery intensity of Moroccan orange blossom and warm vanilla, Libre embodies a modern woman’s duality — strong yet delicate, rebellious yet sophisticated. Its sleek, couture-inspired bottle with a striking gold YSL logo and asymmetric black cap reflects the fragrance’s daring character. Perfect for both day and evening wear, Libre invites you to embrace your individuality with confidence.', 53.00, NULL, '2025-05-27 02:17:23', 'UniSex', 2, 1),
(79, 21, '68352277b5500_vistoria.jpg', 'BombShell VS', 38, 'Victoria’s Secret Bombshell Escape Eau de Parfum is a refreshing and uplifting scent that captures the essence of a breezy summer getaway. Part of the beloved Bombshell collection, Escape is a light, airy fragrance with a captivating blend of sea breeze accord, delicate jasmine petals, and a hint of pineapple. This composition creates a tropical, aquatic feel that’s both fresh and feminine, perfect for a relaxed day or a carefree evening. The turquoise gradient bottle with a chic coral pink ribbon mirrors the ocean’s beauty and the playful allure of a tropical escape.', 79.99, NULL, '2025-05-27 02:24:55', 'Woman', 2, 2),
(80, 21, '68353439ae752_soul.jpg', 'Soul Secret Blossom', 44, 'The Face Shop Soul Secret Blossom Eau de Parfum is a delicate and romantic fragrance inspired by the beauty of blooming flowers. This floral and fruity scent combines fresh top notes of bergamot and peach, followed by a lush heart of jasmine and rose, and grounded with warm musk and cedarwood. Its light yet captivating aroma is perfect for everyday wear, offering a touch of elegance and femininity. The simple yet chic clear bottle with a black cap reflects the understated sophistication of the fragrance.', 58.35, NULL, '2025-05-27 03:40:41', 'Woman', 2, 1),
(82, 19, '683ac6079be57_swim blue.jpg', 'Swim Blue Black Edition', 41, 'Swim Blue Black Edition is a unisex fragrance from Szindore\\\'s Black Edition Series, inspired by Louis Vuitton\\\'s Afternoon Swim. This refined perfume offers a refreshing and captivating scent, suitable for both men and women. It\\\'s designed to be worn on any occasion, providing an invigorating aroma that lasts throughout the day', 89.00, NULL, '2025-05-31 09:04:07', 'UniSex', 3, 5),
(84, 19, '683ac748d0549_de club.jpg', 'De Club Black Edition', 37, 'The Szindore DE CLUB Black Edition is a refined extrait de parfum crafted for the modern man who appreciates a blend of sophistication and boldness. This fragrance is part of Szindore\\\'s Black Edition Series and is inspired by Jean Paul Gaultier\\\'s Ultra Male, offering a captivating scent profile that transitions seamlessly from day to night.', 79.00, NULL, '2025-05-31 09:09:28', 'Man', 1, 9),
(85, 19, '683ac9360ce54_pineapple.jpg', 'PINEAPPLE HOLIDAY', 30, 'The Szindore Pineapple Holiday is a unisex fragrance that captures the essence of a tropical getaway with its sweet and fruity pineapple notes. This handcrafted perfume offers a refreshing and long-lasting scent, making it ideal for both men and women seeking a vibrant and exotic aroma.', 70.00, NULL, '2025-05-31 09:17:42', 'UniSex', 1, 2),
(86, 19, '683ac9e21c5f7_BLACK SHADOW.jpg', 'BLACK SHADOW', 19, 'BLACK SHADOW is a unisex fragrance that combines floral and woody notes, offering a captivating scent suitable for all occasions. This perfume is designed to exude elegance and mystery, making it ideal for both casual day outings and evening events.', 55.00, NULL, '2025-05-31 09:20:34', 'UniSex', 2, 1),
(87, 4, '683b31a95bf92_kahf.jpg', 'KAHF', 23, 'KAHFis a unisex fragrance that blends traditional oud with modern gourmand elements, offering a sweet scent profile.', 47.99, NULL, '2025-05-31 16:43:21', 'UniSex', 1, 9),
(88, 4, '683b344c0375c_afnan.jpg', 'AFNAN 9PM', 2, 'Afnan 9PM is a men’s fragrance known for its deep, rich, and warm character, making it perfect for evening wear. Inspired by Gourmand and Oriental scent profiles, it offers a blend of freshness and sweetness.', 39.99, NULL, '2025-05-31 16:54:36', 'Man', 2, 9);

-- --------------------------------------------------------

--
-- Table structure for table `product_scent`
--

CREATE TABLE `product_scent` (
  `product_scent_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `scent_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_scent`
--

INSERT INTO `product_scent` (`product_scent_id`, `product_id`, `scent_id`) VALUES
(1, 48, 1),
(2, 48, 3),
(3, 48, 4),
(4, 49, 2),
(5, 49, 4),
(6, 49, 5),
(7, 50, 2),
(8, 50, 3),
(9, 50, 8),
(10, 51, 1),
(11, 51, 5),
(12, 52, 4),
(13, 52, 5),
(14, 52, 7),
(15, 53, 3),
(16, 53, 8),
(17, 53, 12),
(18, 54, 5),
(19, 54, 7),
(20, 54, 10),
(21, 55, 3),
(22, 55, 5),
(23, 55, 8),
(24, 56, 2),
(25, 56, 7),
(26, 57, 2),
(27, 57, 5),
(28, 58, 3),
(29, 58, 5),
(30, 58, 8),
(31, 59, 5),
(32, 59, 10),
(33, 59, 11),
(34, 60, 1),
(35, 60, 11),
(36, 60, 12),
(37, 61, 3),
(38, 61, 5),
(39, 61, 8),
(40, 62, 3),
(41, 62, 8),
(42, 62, 11),
(46, 64, 5),
(47, 64, 10),
(48, 64, 11),
(51, 66, 5),
(52, 66, 10),
(53, 66, 12),
(54, 67, 3),
(55, 67, 8),
(56, 67, 11),
(57, 68, 1),
(58, 68, 2),
(62, 73, 1),
(63, 73, 3),
(64, 73, 5),
(65, 74, 1),
(66, 74, 2),
(67, 74, 3),
(92, 65, 3),
(93, 65, 8),
(94, 65, 9),
(95, 77, 1),
(96, 77, 9),
(99, 79, 2),
(100, 79, 10),
(101, 79, 1),
(102, 80, 1),
(103, 80, 2),
(104, 80, 3),
(110, 82, 5),
(111, 82, 3),
(112, 82, 7),
(116, 84, 9),
(117, 84, 11),
(118, 84, 8),
(119, 85, 2),
(120, 85, 10),
(121, 85, 13),
(122, 86, 13),
(123, 86, 6),
(124, 86, 7),
(125, 87, 9),
(126, 87, 16),
(127, 87, 11),
(128, 88, 9),
(129, 88, 4),
(130, 69, 1),
(131, 69, 10),
(132, 70, 2),
(133, 70, 1),
(134, 70, 15),
(135, 71, 8),
(136, 71, 5),
(137, 71, 15),
(138, 75, 1),
(139, 75, 6),
(140, 75, 4),
(141, 76, 10),
(142, 76, 1),
(143, 76, 15),
(144, 63, 2),
(145, 63, 5),
(146, 63, 6);

-- --------------------------------------------------------

--
-- Table structure for table `product_views`
--

CREATE TABLE `product_views` (
  `view_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `view_date` datetime NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_views`
--

INSERT INTO `product_views` (`view_id`, `product_id`, `view_date`, `ip_address`, `customer_id`) VALUES
(1, 69, '2025-05-13 16:40:31', '::1', NULL),
(2, 69, '2025-05-13 16:40:31', '::1', NULL),
(3, 70, '2025-05-13 16:40:42', '::1', NULL),
(4, 70, '2025-05-13 16:40:42', '::1', NULL),
(5, 68, '2025-05-13 16:40:48', '::1', NULL),
(6, 68, '2025-05-13 16:40:48', '::1', NULL),
(7, 70, '2025-05-13 16:41:12', '::1', NULL),
(8, 70, '2025-05-13 16:41:12', '::1', NULL),
(9, 70, '2025-05-13 16:41:22', '::1', NULL),
(10, 70, '2025-05-13 16:41:22', '::1', NULL),
(11, 71, '2025-05-13 16:43:31', '::1', NULL),
(12, 71, '2025-05-13 16:43:31', '::1', NULL),
(13, 71, '2025-05-13 16:43:38', '::1', NULL),
(14, 71, '2025-05-13 16:43:38', '::1', NULL),
(15, 71, '2025-05-13 16:43:50', '::1', NULL),
(16, 71, '2025-05-13 16:43:50', '::1', NULL),
(17, 71, '2025-05-13 16:44:01', '::1', NULL),
(18, 71, '2025-05-13 16:44:01', '::1', NULL),
(19, 71, '2025-05-13 16:44:09', '::1', NULL),
(20, 71, '2025-05-13 16:44:09', '::1', NULL),
(21, 71, '2025-05-13 16:53:29', '::1', NULL),
(22, 71, '2025-05-13 16:53:29', '::1', NULL),
(23, 71, '2025-05-13 16:53:37', '::1', NULL),
(24, 71, '2025-05-13 16:53:37', '::1', NULL),
(25, 71, '2025-05-13 16:57:11', '::1', NULL),
(26, 71, '2025-05-13 16:57:11', '::1', NULL),
(27, 71, '2025-05-13 16:57:16', '::1', NULL),
(28, 71, '2025-05-13 16:57:16', '::1', NULL),
(29, 71, '2025-05-13 16:58:47', '::1', NULL),
(30, 71, '2025-05-13 16:58:47', '::1', NULL),
(31, 58, '2025-05-13 16:59:02', '::1', NULL),
(32, 58, '2025-05-13 16:59:02', '::1', NULL),
(33, 73, '2025-05-13 16:59:09', '::1', NULL),
(34, 73, '2025-05-13 16:59:09', '::1', NULL),
(35, 73, '2025-05-13 16:59:20', '::1', NULL),
(36, 73, '2025-05-13 16:59:20', '::1', NULL),
(37, 73, '2025-05-13 17:00:49', '::1', NULL),
(39, 70, '2025-05-18 19:23:29', '::1', 39),
(40, 71, '2025-05-18 19:25:30', '::1', 39),
(41, 73, '2025-05-18 19:26:56', '::1', 39),
(42, 74, '2025-05-19 14:30:34', '::1', NULL),
(43, 74, '2025-05-19 14:30:57', '::1', NULL),
(44, 64, '2025-05-19 14:31:06', '::1', NULL),
(45, 73, '2025-05-19 14:37:17', '::1', NULL),
(46, 73, '2025-05-19 14:37:17', '::1', NULL),
(47, 73, '2025-05-19 14:37:31', '::1', NULL),
(48, 73, '2025-05-19 14:37:31', '::1', NULL),
(49, 58, '2025-05-19 14:38:00', '::1', NULL),
(50, 71, '2025-05-19 14:38:09', '::1', NULL),
(51, 71, '2025-05-19 14:38:16', '::1', NULL),
(52, 71, '2025-05-19 14:38:23', '::1', NULL),
(53, 67, '2025-05-19 14:38:36', '::1', NULL),
(54, 63, '2025-05-19 16:07:51', '::1', NULL),
(55, 58, '2025-05-19 16:08:00', '::1', NULL),
(56, 58, '2025-05-19 16:08:20', '::1', NULL),
(57, 67, '2025-05-20 00:25:46', '::1', NULL),
(58, 67, '2025-05-20 00:26:21', '::1', 28),
(59, 59, '2025-05-20 00:26:35', '::1', 28),
(60, 70, '2025-05-20 00:27:08', '::1', 28),
(61, 71, '2025-05-20 00:29:41', '::1', 28),
(62, 61, '2025-05-20 00:38:19', '::1', 28),
(63, 67, '2025-05-20 00:42:55', '::1', 39),
(64, 66, '2025-05-20 00:48:40', '::1', 39),
(65, 75, '2025-05-20 00:59:09', '::1', 39),
(66, 58, '2025-05-20 01:19:08', '::1', 39),
(67, 60, '2025-05-20 01:20:01', '::1', 39),
(68, 68, '2025-05-20 01:20:39', '::1', 39),
(69, 71, '2025-05-20 01:21:15', '::1', 39),
(70, 59, '2025-05-20 01:22:56', '::1', 39),
(71, 74, '2025-05-20 01:30:10', '::1', 39),
(72, 63, '2025-05-20 01:34:55', '::1', 39),
(73, 64, '2025-05-20 01:41:12', '::1', 39),
(74, 58, '2025-05-20 01:42:55', '::1', NULL),
(75, 65, '2025-05-20 01:43:18', '::1', NULL),
(76, 61, '2025-05-20 01:47:17', '::1', 39),
(77, 65, '2025-05-20 01:47:18', '::1', 39),
(78, 73, '2025-05-20 01:54:20', '::1', 39),
(79, 58, '2025-05-20 13:32:42', '::1', 28),
(80, 63, '2025-05-20 20:16:06', '::1', 27),
(81, 66, '2025-05-20 20:33:56', '::1', 27),
(82, 59, '2025-05-20 20:34:00', '::1', 27),
(83, 75, '2025-05-20 20:35:51', '::1', 27),
(84, 60, '2025-05-20 20:35:57', '::1', 27),
(85, 67, '2025-05-20 20:36:02', '::1', 27),
(86, 58, '2025-05-20 20:39:33', '::1', 27),
(87, 61, '2025-05-20 20:39:37', '::1', 27),
(88, 64, '2025-05-20 20:39:40', '::1', 27),
(89, 68, '2025-05-20 20:51:55', '::1', 27),
(90, 74, '2025-05-20 21:02:21', '::1', 27),
(91, 70, '2025-05-20 21:04:24', '::1', 27),
(92, 71, '2025-05-20 21:05:17', '::1', 27),
(93, 73, '2025-05-20 21:05:23', '::1', 27),
(94, 70, '2025-05-20 22:33:44', '::1', 39),
(95, 71, '2025-05-21 10:42:29', '::1', 39),
(96, 75, '2025-05-21 10:42:34', '::1', 39),
(97, 60, '2025-05-21 10:43:02', '::1', 39),
(98, 60, '2025-05-21 10:59:49', '::1', 49),
(99, 63, '2025-05-21 11:00:13', '::1', 49),
(100, 59, '2025-05-21 11:03:09', '::1', 49),
(101, 73, '2025-05-21 11:08:40', '::1', 39),
(102, 63, '2025-05-21 22:55:21', '::1', 27),
(103, 58, '2025-05-21 22:55:25', '::1', 27),
(104, 74, '2025-05-21 23:02:44', '::1', 27),
(105, 61, '2025-05-21 23:04:27', '::1', 27),
(106, 66, '2025-05-21 23:04:30', '::1', 27),
(107, 73, '2025-05-21 23:04:33', '::1', 27),
(108, 60, '2025-05-21 23:12:57', '::1', 27),
(109, 60, '2025-05-22 00:23:40', '::1', 27),
(110, 67, '2025-05-22 00:24:38', '::1', 27),
(111, 58, '2025-05-22 00:24:47', '::1', 27),
(112, 63, '2025-05-22 14:41:36', '::1', 27),
(113, 59, '2025-05-25 21:15:07', '::1', 27),
(114, 67, '2025-05-25 21:15:17', '::1', 27),
(115, 58, '2025-05-25 21:19:56', '::1', 27),
(116, 68, '2025-05-25 21:23:20', '::1', 27),
(117, 61, '2025-05-25 21:54:35', '::1', 27),
(118, 69, '2025-05-25 22:30:23', '::1', 27),
(119, 74, '2025-05-25 22:31:43', '::1', 27),
(120, 63, '2025-05-25 22:31:55', '::1', 27),
(121, 60, '2025-05-25 22:31:59', '::1', 27),
(122, 67, '2025-05-25 22:59:33', '::1', 49),
(123, 73, '2025-05-25 23:16:03', '::1', 27),
(124, 58, '2025-05-26 21:41:22', '::1', 27),
(125, 63, '2025-05-26 22:37:57', '::1', 27),
(126, 61, '2025-05-27 00:08:22', '::1', 27),
(127, 73, '2025-05-27 00:08:32', '::1', 27),
(128, 58, '2025-05-27 00:09:38', '::1', 27),
(129, 75, '2025-05-27 00:32:59', '::1', 27),
(130, 67, '2025-05-27 00:33:08', '::1', 27),
(131, 59, '2025-05-27 00:34:48', '::1', 27),
(132, 71, '2025-05-27 00:49:27', '::1', 27),
(133, 59, '2025-05-27 10:05:10', '::1', NULL),
(134, 61, '2025-05-27 10:05:37', '::1', NULL),
(135, 63, '2025-05-27 10:13:24', '::1', 53),
(136, 77, '2025-05-27 10:19:00', '::1', 53),
(137, 77, '2025-05-27 10:19:31', '::1', 27),
(138, 79, '2025-05-27 10:25:16', '::1', 27),
(139, 79, '2025-05-27 10:46:40', '::1', 49),
(140, 77, '2025-05-27 11:14:52', '::1', 54),
(141, 79, '2025-05-27 11:15:24', '::1', 54),
(142, 59, '2025-05-27 11:22:58', '::1', 54),
(143, 63, '2025-05-27 11:25:34', '::1', 54),
(144, 64, '2025-05-27 11:28:31', '::1', 54),
(145, 65, '2025-05-27 11:28:42', '::1', 54),
(146, 58, '2025-05-27 11:30:29', '::1', 54),
(147, 58, '2025-05-28 23:16:59', '::1', 27),
(148, 73, '2025-05-29 12:12:47', '::1', 27),
(149, 58, '2025-05-29 12:15:59', '::1', 27),
(150, 60, '2025-05-29 12:26:43', '::1', 27),
(151, 63, '2025-05-29 12:31:26', '::1', 27),
(152, 66, '2025-05-29 22:03:44', '::1', 27),
(153, 73, '2025-05-30 11:33:17', '::1', 27),
(154, 64, '2025-05-30 11:48:07', '::1', 27),
(155, 58, '2025-05-30 11:49:13', '::1', 27),
(156, 61, '2025-05-30 11:52:26', '::1', 27),
(157, 67, '2025-05-30 16:05:37', '::1', 27),
(158, 77, '2025-05-30 22:54:41', '::1', 27),
(159, 65, '2025-05-30 22:55:50', '::1', 27),
(160, 63, '2025-05-30 22:56:18', '::1', 27),
(161, 80, '2025-05-30 22:56:30', '::1', 27),
(162, 79, '2025-05-30 23:05:18', '::1', 27),
(163, 68, '2025-05-30 23:11:03', '::1', 27),
(164, 65, '2025-05-31 14:24:52', '::1', 27),
(165, 73, '2025-05-31 14:31:51', '::1', 27),
(166, 61, '2025-05-31 14:59:34', '::1', 27),
(167, 59, '2025-05-31 14:59:53', '::1', 27),
(168, 64, '2025-05-31 15:01:43', '::1', 27),
(169, 67, '2025-05-31 15:17:09', '::1', 27),
(170, 76, '2025-05-31 17:01:32', '::1', 27),
(171, 82, '2025-05-31 17:05:07', '::1', 27),
(172, 68, '2025-05-31 17:29:58', '::1', 27),
(173, 58, '2025-05-31 17:47:44', '::1', 27),
(174, 73, '2025-06-01 02:13:11', '::1', 27),
(175, 63, '2025-06-01 02:13:27', '::1', 27),
(176, 67, '2025-06-01 02:18:13', '::1', 27),
(177, 64, '2025-06-01 02:22:45', '::1', 27),
(178, 65, '2025-06-01 02:22:53', '::1', 27),
(179, 74, '2025-06-01 11:37:49', '::1', 27),
(180, 75, '2025-06-01 11:42:35', '::1', 27),
(181, 68, '2025-06-01 11:48:47', '::1', 27),
(182, 71, '2025-06-01 12:07:09', '::1', 39),
(183, 66, '2025-06-01 12:37:31', '::1', 39),
(184, 65, '2025-06-01 12:37:42', '::1', 39),
(185, 73, '2025-06-01 12:44:26', '::1', 39),
(186, 68, '2025-06-01 12:48:48', '::1', 39),
(187, 70, '2025-06-01 12:50:17', '::1', 39),
(188, 74, '2025-06-01 13:33:03', '::1', 39),
(189, 84, '2025-06-01 13:58:10', '::1', 39),
(190, 75, '2025-06-01 14:01:29', '::1', 39),
(191, 76, '2025-06-01 14:21:23', '::1', 39),
(192, 86, '2025-06-01 15:22:32', '::1', 39),
(193, 85, '2025-06-01 15:31:52', '::1', 39),
(194, 88, '2025-06-01 16:29:07', '::1', 27),
(195, 80, '2025-06-01 16:29:13', '::1', 27),
(196, 87, '2025-06-01 16:29:35', '::1', 27),
(197, 82, '2025-06-01 16:29:37', '::1', 27),
(198, 77, '2025-06-01 16:29:40', '::1', 27),
(199, 80, '2025-06-01 16:33:41', '::1', 39),
(200, 77, '2025-06-01 16:33:59', '::1', 39),
(201, 79, '2025-06-01 16:34:22', '::1', 39),
(202, 73, '2025-06-02 15:15:47', '::1', 27),
(203, 88, '2025-06-02 15:16:58', '::1', 27),
(204, 84, '2025-06-02 15:17:01', '::1', 27),
(205, 67, '2025-06-02 15:22:45', '::1', 39),
(206, 58, '2025-06-02 15:30:32', '::1', 39),
(207, 86, '2025-06-02 16:43:47', '::1', 39),
(208, 71, '2025-06-02 16:50:05', '::1', 39),
(209, 59, '2025-06-02 16:56:14', '::1', 27),
(210, 68, '2025-06-02 17:06:07', '::1', 27),
(211, 59, '2025-06-03 10:34:48', '::1', 57),
(212, 58, '2025-06-03 10:38:11', '::1', 57),
(213, 68, '2025-06-03 10:38:35', '::1', 57),
(214, 77, '2025-06-03 10:45:09', '::1', 57),
(215, 65, '2025-06-03 10:45:57', '::1', 57),
(216, 74, '2025-06-03 10:46:01', '::1', 57),
(217, 73, '2025-06-03 10:46:05', '::1', 57),
(218, 60, '2025-06-03 10:59:29', '::1', 57),
(219, 68, '2025-06-03 13:46:22', '::1', 27),
(220, 61, '2025-06-03 13:48:26', '::1', 27),
(221, 79, '2025-06-03 13:48:36', '::1', 27),
(222, 70, '2025-06-03 13:56:16', '::1', 27),
(223, 60, '2025-06-03 13:56:20', '::1', 27),
(224, 63, '2025-06-03 13:56:21', '::1', 27),
(225, 59, '2025-06-03 13:56:22', '::1', 27),
(226, 64, '2025-06-03 13:56:23', '::1', 27),
(227, 66, '2025-06-03 13:56:24', '::1', 27),
(228, 74, '2025-06-03 13:56:35', '::1', 27),
(229, 84, '2025-06-03 13:56:47', '::1', 27),
(230, 58, '2025-06-03 15:22:53', '::1', 27),
(231, 88, '2025-06-03 17:38:18', '::1', 58),
(232, 70, '2025-06-03 17:41:24', '::1', 58),
(233, 73, '2025-06-03 17:41:50', '::1', 58),
(234, 75, '2025-06-03 17:42:23', '::1', 58),
(235, 77, '2025-06-03 17:44:12', '::1', 58),
(236, 65, '2025-06-03 17:44:27', '::1', 58),
(237, 60, '2025-06-03 17:47:40', '::1', 58),
(238, 74, '2025-06-03 17:48:18', '::1', 58),
(239, 88, '2025-06-03 20:18:21', '::1', 27),
(240, 77, '2025-06-03 20:28:08', '::1', 27),
(241, 73, '2025-06-03 21:29:02', '::1', 27),
(242, 82, '2025-06-03 22:03:57', '::1', 27),
(243, 65, '2025-06-03 23:07:12', '::1', 59),
(244, 73, '2025-06-03 23:07:31', '::1', 59),
(245, 61, '2025-06-03 23:09:02', '::1', 59),
(246, 63, '2025-06-03 23:09:06', '::1', 59),
(247, 67, '2025-06-04 01:50:00', '::1', 27),
(248, 88, '2025-06-04 07:01:01', '::1', 27),
(249, 88, '2025-06-04 09:31:54', '::1', 60),
(250, 63, '2025-06-07 16:57:45', '::1', 61),
(251, 79, '2025-06-07 17:08:49', '::1', 61),
(252, 88, '2025-06-07 17:11:03', '::1', 61),
(253, 84, '2025-06-07 17:11:08', '::1', 61);

-- --------------------------------------------------------

--
-- Table structure for table `product_view_summary`
--

CREATE TABLE `product_view_summary` (
  `summary_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `view_date` date NOT NULL,
  `view_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_view_summary`
--

INSERT INTO `product_view_summary` (`summary_id`, `product_id`, `seller_id`, `view_date`, `view_count`) VALUES
(1, 80, 21, '2025-06-01', 1),
(2, 77, 21, '2025-06-01', 1),
(3, 79, 21, '2025-06-01', 1),
(4, 73, 4, '2025-06-02', 1),
(5, 88, 4, '2025-06-02', 1),
(6, 84, 19, '2025-06-02', 1),
(7, 67, 4, '2025-06-02', 1),
(8, 58, 5, '2025-06-02', 1),
(9, 86, 19, '2025-06-02', 1),
(10, 71, 4, '2025-06-02', 1),
(11, 59, 5, '2025-06-02', 1),
(12, 68, 4, '2025-06-02', 1),
(13, 59, 5, '2025-06-03', 2),
(14, 58, 5, '2025-06-03', 2),
(15, 68, 4, '2025-06-03', 2),
(16, 77, 21, '2025-06-03', 3),
(17, 65, 4, '2025-06-03', 3),
(18, 74, 4, '2025-06-03', 3),
(19, 73, 4, '2025-06-03', 4),
(20, 60, 5, '2025-06-03', 3),
(22, 61, 5, '2025-06-03', 2),
(23, 79, 21, '2025-06-03', 1),
(24, 70, 4, '2025-06-03', 2),
(26, 63, 4, '2025-06-03', 2),
(28, 64, 4, '2025-06-03', 1),
(29, 66, 4, '2025-06-03', 1),
(31, 84, 19, '2025-06-03', 1),
(33, 88, 4, '2025-06-03', 2),
(36, 75, 4, '2025-06-03', 1),
(44, 82, 19, '2025-06-03', 1),
(49, 67, 4, '2025-06-04', 1),
(50, 88, 4, '2025-06-04', 2),
(52, 63, 4, '2025-06-07', 1),
(53, 79, 21, '2025-06-07', 1),
(54, 88, 4, '2025-06-07', 1),
(55, 84, 19, '2025-06-07', 1);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promotion_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `promo_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `discount_type` enum('percentage','fixed_amount') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_discount_amount` decimal(10,2) DEFAULT NULL,
  `promo_code` varchar(20) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`promotion_id`, `seller_id`, `product_id`, `promo_name`, `description`, `discount_type`, `discount_value`, `start_date`, `end_date`, `min_order_amount`, `max_discount_amount`, `promo_code`, `usage_limit`, `used_count`, `status`, `created_at`, `updated_at`) VALUES
(19, 19, 84, 'Sale', NULL, 'percentage', 12.00, '2025-06-03 12:51:00', '2025-06-12 12:51:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 04:51:42', '2025-06-03 04:51:42'),
(24, 4, 74, 'Sale', NULL, 'percentage', 10.00, '2025-06-03 13:24:00', '2025-06-13 13:24:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 05:24:52', '2025-06-03 05:24:52'),
(26, 4, 70, 'Sale', NULL, 'percentage', 10.00, '2025-06-03 13:40:00', '2025-06-11 13:40:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 05:40:38', '2025-06-03 05:40:38'),
(28, 21, 79, 'Sale', NULL, 'percentage', 12.00, '2025-06-03 13:43:00', '2025-06-05 13:43:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 05:43:59', '2025-06-03 05:43:59'),
(29, 5, 61, 'Sale', NULL, 'percentage', 9.00, '2025-06-03 14:17:00', '2025-06-12 14:17:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 06:17:34', '2025-06-03 06:17:34'),
(31, 5, 59, 'Sale', NULL, 'percentage', 12.00, '2025-06-03 14:29:00', '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 06:29:19', '2025-06-03 06:29:19'),
(32, 5, 59, 'Sale', NULL, 'percentage', 12.00, '2025-06-03 14:30:00', '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 06:30:31', '2025-06-03 06:30:31'),
(33, 5, 59, 'Sale', NULL, 'percentage', 12.00, '2025-06-03 14:31:00', '2025-06-11 14:31:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 06:32:00', '2025-06-03 06:32:00'),
(34, 5, 60, 'Sale', NULL, 'percentage', 15.00, '2025-06-03 14:33:00', '2025-06-10 14:33:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-03 06:33:17', '2025-06-03 06:33:17'),
(40, 4, 64, 'Sale', NULL, 'percentage', 10.00, '2025-06-04 09:37:00', '2025-06-12 09:37:00', NULL, NULL, NULL, NULL, 0, 'active', '2025-06-04 01:38:03', '2025-06-04 01:38:03');

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `questionID` int(11) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `ConcentrationID` int(11) NOT NULL,
  `LifestyleID` int(11) DEFAULT NULL,
  `Personality` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`questionID`, `gender`, `ConcentrationID`, `LifestyleID`, `Personality`) VALUES
(4, 'man', 3, NULL, NULL),
(5, 'man', 3, NULL, NULL),
(6, 'woman', 4, NULL, NULL),
(7, 'woman', 4, NULL, NULL),
(8, 'woman', 1, NULL, NULL),
(9, 'man', 3, NULL, NULL),
(10, 'man', 2, NULL, NULL),
(11, 'man', 4, NULL, NULL),
(12, 'man', 4, NULL, NULL),
(13, 'woman', 2, NULL, NULL),
(16, 'woman', 5, NULL, NULL),
(17, 'man', 2, NULL, NULL),
(18, 'man', 3, NULL, NULL),
(19, 'man', 2, NULL, NULL),
(20, 'man', 1, NULL, NULL),
(21, 'woman', 2, NULL, NULL),
(22, 'man', 2, NULL, NULL),
(23, 'woman', 2, NULL, NULL),
(24, 'woman', 5, NULL, NULL),
(25, 'woman', 2, NULL, NULL),
(26, 'woman', 2, NULL, NULL),
(27, 'man', 2, NULL, NULL),
(39, 'woman', 1, 5, 'calm'),
(40, 'woman', 1, 3, 'calm'),
(41, 'man', 1, 1, 'bold'),
(42, 'woman', 1, 2, 'mysterious'),
(47, 'man', 1, 2, 'playful'),
(48, 'woman', 1, 2, 'calm'),
(49, 'man', 2, 2, 'bold'),
(50, 'woman', 1, 3, 'mysterious'),
(51, 'woman', 2, 4, 'calm'),
(52, 'woman', 1, 2, 'calm,elegant'),
(53, 'woman', 2, 3, 'bold'),
(54, 'woman', 2, 3, 'calm'),
(55, 'man', 2, 3, 'playful'),
(56, 'man', 2, 3, 'playful'),
(57, 'man', 2, 5, 'playful'),
(58, 'man', 2, 5, 'elegant,playful'),
(59, 'man', 2, 5, 'playful'),
(60, 'man', 2, 5, 'playful'),
(61, 'woman', 2, 3, 'mysterious'),
(62, 'man', 2, 3, 'calm'),
(63, 'man', 2, 3, 'calm'),
(64, 'woman', 2, 4, 'playful'),
(65, 'woman', 2, 4, 'playful'),
(66, 'man', 2, 5, 'mysterious'),
(67, 'woman', 2, 5, 'calm'),
(68, 'man', 2, 4, 'bold'),
(69, 'man', 2, 1, 'mysterious,calm'),
(70, 'woman', 2, 5, 'elegant'),
(71, 'man', 2, 2, 'bold'),
(72, 'man', 2, 1, 'elegant'),
(73, 'woman', 2, 3, 'mysterious'),
(74, 'man', 2, 2, 'calm'),
(75, 'man', 2, 1, 'elegant'),
(76, 'man', 2, 4, 'calm'),
(77, 'man', 2, 1, 'calm'),
(78, 'man', 2, 3, 'calm'),
(79, 'man', 2, 3, 'calm'),
(80, 'man', 2, 5, 'playful');

-- --------------------------------------------------------

--
-- Table structure for table `question_scent`
--

CREATE TABLE `question_scent` (
  `questionScentID` int(11) NOT NULL,
  `questionID` int(11) NOT NULL,
  `ScentID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_scent`
--

INSERT INTO `question_scent` (`questionScentID`, `questionID`, `ScentID`) VALUES
(23, 4, 5),
(24, 4, 3),
(25, 4, 8),
(26, 5, 5),
(27, 5, 7),
(28, 5, 9),
(29, 6, 7),
(30, 6, 4),
(31, 6, 5),
(32, 7, 2),
(33, 7, 9),
(34, 7, 7),
(35, 8, 4),
(36, 8, 5),
(37, 8, 1),
(38, 9, 4),
(39, 9, 8),
(40, 9, 1),
(41, 10, 3),
(42, 10, 12),
(43, 10, 8),
(44, 11, 2),
(45, 11, 3),
(46, 11, 7),
(47, 12, 3),
(48, 12, 10),
(49, 12, 13),
(50, 13, 1),
(51, 13, 3),
(52, 13, 5),
(59, 16, 2),
(60, 16, 8),
(61, 16, 11),
(62, 17, 4),
(63, 17, 7),
(64, 17, 12),
(65, 18, 5),
(66, 18, 7),
(67, 18, 4),
(68, 19, 3),
(69, 19, 5),
(70, 19, 9),
(71, 20, 10),
(72, 20, 7),
(73, 20, 12),
(74, 21, 3),
(75, 21, 2),
(76, 21, 7),
(77, 22, 3),
(78, 22, 4),
(79, 22, 7),
(80, 23, 2),
(81, 24, 3),
(82, 24, 2),
(83, 24, 8),
(84, 25, 3),
(85, 25, 4),
(86, 25, 7),
(87, 26, 2),
(88, 26, 9),
(89, 26, 11),
(90, 27, 3),
(91, 27, 7),
(92, 27, 8),
(93, 39, 3),
(94, 40, 3),
(95, 41, 9),
(96, 42, 3),
(97, 42, 9),
(106, 47, 3),
(107, 47, 12),
(108, 48, 8),
(109, 48, 9),
(110, 49, 9),
(111, 49, 8),
(112, 50, 8),
(113, 50, 10),
(114, 51, 8),
(115, 51, 9),
(116, 52, 7),
(117, 52, 8),
(118, 52, 9),
(119, 53, 7),
(120, 53, 11),
(121, 53, 10),
(122, 54, 3),
(123, 54, 8),
(124, 54, 12),
(125, 55, 8),
(126, 55, 5),
(127, 55, 6),
(128, 56, 11),
(129, 56, 8),
(130, 56, 7),
(131, 57, 8),
(132, 57, 12),
(133, 58, 6),
(134, 58, 13),
(135, 59, 9),
(136, 59, 13),
(137, 60, 5),
(138, 60, 10),
(139, 61, 13),
(140, 61, 10),
(141, 62, 1),
(142, 62, 5),
(143, 62, 2),
(144, 63, 5),
(145, 63, 8),
(146, 63, 7),
(147, 64, 8),
(148, 64, 9),
(149, 64, 11),
(150, 65, 8),
(151, 65, 9),
(152, 65, 11),
(153, 66, 2),
(154, 66, 1),
(155, 66, 4),
(156, 67, 4),
(157, 67, 2),
(158, 67, 11),
(159, 68, 7),
(160, 68, 5),
(161, 68, 8),
(162, 69, 8),
(163, 69, 1),
(164, 69, 7),
(165, 70, 2),
(166, 70, 5),
(167, 70, 1),
(168, 71, 5),
(169, 71, 6),
(170, 72, 5),
(171, 72, 10),
(172, 72, 11),
(173, 73, 5),
(174, 73, 7),
(175, 73, 1),
(176, 74, 5),
(177, 74, 1),
(178, 74, 3),
(179, 75, 1),
(180, 75, 5),
(181, 75, 7),
(182, 76, 1),
(183, 76, 14),
(184, 76, 15),
(185, 77, 14),
(186, 77, 3),
(187, 77, 1),
(188, 78, 1),
(189, 78, 3),
(190, 78, 8),
(191, 79, 4),
(192, 79, 1),
(193, 79, 8),
(194, 80, 14),
(195, 80, 12);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `ReviewID` int(11) NOT NULL,
  `OrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `CustomerID` int(11) NOT NULL,
  `Rating` int(1) NOT NULL,
  `ReviewText` text DEFAULT NULL,
  `ReviewImages` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `SellerResponse` text DEFAULT NULL,
  `ResponseDate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`ReviewID`, `OrderID`, `ProductID`, `CustomerID`, `Rating`, `ReviewText`, `ReviewImages`, `CreatedAt`, `SellerResponse`, `ResponseDate`) VALUES
(1, 77, 58, 1, 3, 'erewr', NULL, '2025-04-11 09:56:36', NULL, NULL),
(2, 76, 60, 1, 4, 'Nice smell', NULL, '2025-04-11 10:19:13', 'Glad you like it', '2025-06-03 07:05:46'),
(3, 73, 67, 1, 3, 'Sedap woo', NULL, '2025-04-11 10:47:10', 'Haah tau', '2025-05-04 15:09:14'),
(4, 80, 64, 28, 4, 'refreshing', NULL, '2025-04-29 13:54:21', 'Yes sir', '2025-04-29 15:54:21'),
(5, 88, 65, 28, 4, 'best', 'review_6810e254cfbbe0.30323205.jpg', '2025-04-29 14:29:40', 'thank you', '2025-04-29 15:22:14'),
(6, 153, 60, 39, 3, 'nice', 'review_682c2319572988.92282668.jpg', '2025-05-20 06:37:13', 'Thanks for buy', '2025-06-03 07:09:50'),
(7, 143, 58, 39, 4, 'I like the smell', 'review_682c235eed79f5.40200827.jpg', '2025-05-20 06:38:22', 'Thank you bro', '2025-06-03 07:03:00'),
(8, 119, 58, 39, 4, 'nice smell...I like it', 'review_682c244937e395.83355790.jpg', '2025-05-20 06:42:17', NULL, NULL),
(9, 117, 63, 39, 4, 'Segar bau nya...best', 'review_682c26335b98c4.48987914.jpg', '2025-05-20 06:50:27', NULL, NULL),
(10, 224, 63, 27, 4, 'sxax', NULL, '2025-06-01 03:35:31', NULL, NULL),
(11, 209, 74, 27, 4, 'nice smell', NULL, '2025-06-01 03:37:43', NULL, NULL),
(12, 221, 75, 27, 5, 'I like it a lot', NULL, '2025-06-01 03:42:22', NULL, NULL),
(13, 197, 68, 27, 5, 'Good', NULL, '2025-06-01 03:48:38', NULL, NULL),
(14, 214, 64, 27, 3, 'good smell', NULL, '2025-06-01 03:49:18', NULL, NULL),
(15, 201, 67, 27, 4, 'good', NULL, '2025-06-01 03:50:43', NULL, NULL),
(16, 212, 67, 27, 4, 'dadadada', NULL, '2025-06-01 03:56:16', NULL, NULL),
(17, 250, 71, 39, 4, 'asdasd', NULL, '2025-06-01 04:07:05', NULL, NULL),
(18, 257, 71, 39, 4, 'try again', NULL, '2025-06-01 04:22:44', NULL, NULL),
(19, 249, 70, 39, 3, 'hi', NULL, '2025-06-01 04:23:15', NULL, NULL),
(20, 253, 68, 39, 4, 'Nice smell', NULL, '2025-06-01 04:29:14', NULL, NULL),
(21, 182, 65, 39, 4, 'try', NULL, '2025-06-01 04:31:02', NULL, NULL),
(22, 150, 66, 39, 4, 'haha', NULL, '2025-06-01 04:33:01', NULL, NULL),
(23, 134, 67, 39, 4, 'kjkkj', NULL, '2025-06-01 04:37:21', NULL, NULL),
(24, 270, 73, 39, 4, 'try lagi', 'review_683bdaa5eef3c5.72095393.jpg', '2025-06-01 04:44:21', NULL, NULL),
(25, 276, 68, 39, 4, 'bismillah', 'review_683bdbaa40df32.36571212.jpg,review_683bdbaa4154e9.56321363.jpg', '2025-06-01 04:48:42', NULL, NULL),
(26, 268, 70, 39, 4, 'L like it...a lot', 'review_683bdc02509867.62967156.jpg,review_683bdc02514310.04476840.jpg', '2025-06-01 04:50:10', NULL, NULL),
(27, 266, 73, 39, 4, 'Thank you for the smell', 'review_683bdc94403d55.28443721.jpg,review_683bdc9440c105.20690046.jpg,review_683bdc94420f46.38815651.jpg', '2025-06-01 04:52:36', NULL, NULL),
(28, 265, 74, 39, 4, 'good', 'review_683be5b8e66ec2.40977371.jpg,review_683be5b8e8c3e9.31322092.jpg', '2025-06-01 05:31:36', NULL, NULL),
(29, 115, 75, 39, 4, 'Good smell', 'review_683be65d703c66.79260331.png,review_683be65d717899.91172408.jpg', '2025-06-01 05:34:21', NULL, NULL),
(30, 118, 73, 39, 4, 'nice', 'review_683be6f96bd6f2.32577397.jpg,review_683be6f96c8532.81560879.jpg', '2025-06-01 05:36:57', NULL, NULL),
(31, 290, 73, 27, 4, 'Nice', 'review_683be7b567fd46.72588972.jpg,review_683be7b5687f39.08598864.jpg', '2025-06-01 05:40:05', 'Thank you', '2025-06-02 08:57:28'),
(32, 208, 68, 27, 5, 'I like it a lot', 'review_683be918a58e05.01120212.jpg,review_683be918a65597.21700524.jpg,review_683be918a96038.00860366.jpg', '2025-06-01 05:46:00', 'I know it', '2025-06-02 09:03:43'),
(33, 204, 63, 27, 3, 'nice smell', 'review_683be9ebe4d456.19969845.jpg,review_683be9ebe55cb2.85846794.jpg,review_683be9ebe69cf4.79587328.jpg', '2025-06-01 05:49:31', NULL, NULL),
(34, 138, 75, 39, 4, 'try', 'review_683bea767888c0.87873821.jpg', '2025-06-01 05:51:50', NULL, NULL),
(35, 116, 70, 39, 4, 'fdasda', NULL, '2025-06-01 05:53:01', NULL, NULL),
(36, 136, 66, 39, 3, 'gtrfed', 'review_683beb25471b33.17746617.png', '2025-06-01 05:54:45', NULL, NULL),
(37, 102, 74, 39, 4, 'sasasas', 'review_683bec69213860.89189159.jpg', '2025-06-01 06:00:09', NULL, NULL),
(38, 101, 75, 39, 2, 'Not bad', 'review_683becabefbf16.92791887.jpg', '2025-06-01 06:01:15', 'Thank you', '2025-06-02 09:29:28'),
(39, 307, 88, 27, 4, 'Nice smell', 'review_683f7ea27de818.99080657.jpg,review_683f7ea27e7298.34171701.jpg', '2025-06-03 23:00:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `SaleID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `QuantitySold` int(11) NOT NULL,
  `SaleDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_released` tinyint(1) DEFAULT 0,
  `released_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`SaleID`, `ProductID`, `QuantitySold`, `SaleDate`, `payment_released`, `released_at`) VALUES
(1, 59, 1, '2025-03-10 16:08:09', 0, NULL),
(2, 61, 1, '2025-03-10 16:08:09', 0, NULL),
(3, 58, 2, '2025-03-10 16:08:09', 0, NULL),
(4, 58, 1, '2025-03-11 14:06:20', 0, NULL),
(5, 60, 1, '2025-03-11 16:40:26', 0, NULL),
(6, 58, 1, '2025-03-19 17:02:52', 0, NULL),
(7, 68, 4, '2025-03-19 17:02:52', 0, NULL),
(8, 59, 1, '2025-03-19 17:36:03', 0, NULL),
(9, 58, 1, '2025-03-19 17:52:57', 0, NULL),
(10, 59, 1, '2025-03-20 02:17:31', 0, NULL),
(11, 58, 1, '2025-03-25 16:43:57', 0, NULL),
(12, 68, 1, '2025-04-08 13:42:13', 0, NULL),
(13, 64, 1, '2025-04-08 13:42:13', 0, NULL),
(14, 58, 2, '2025-04-08 16:14:46', 0, NULL),
(15, 68, 1, '2025-04-08 16:16:27', 0, NULL),
(16, 58, 1, '2025-04-09 14:05:16', 0, NULL),
(17, 59, 1, '2025-04-09 14:05:16', 0, NULL),
(18, 68, 1, '2025-04-09 14:05:16', 0, NULL),
(19, 66, 1, '2025-04-09 14:39:33', 0, NULL),
(20, 58, 1, '2025-04-10 08:51:29', 0, NULL),
(21, 68, 1, '2025-04-29 08:38:20', 0, NULL),
(22, 59, 1, '2025-05-02 06:27:07', 0, NULL),
(23, 64, 2, '2025-05-06 06:17:48', 0, NULL),
(24, 59, 4, '2025-05-07 17:33:08', 0, NULL),
(25, 67, 5, '2025-05-07 17:33:08', 0, NULL),
(26, 65, 3, '2025-05-07 17:35:50', 0, NULL),
(27, 69, 2, '2025-05-09 15:08:24', 0, NULL),
(28, 70, 2, '2025-05-09 15:10:13', 0, NULL),
(29, 73, 1, '2025-05-09 15:11:41', 0, NULL),
(30, 63, 1, '2025-05-09 15:12:15', 0, NULL),
(31, 71, 1, '2025-05-09 15:16:48', 0, NULL),
(32, 71, 1, '2025-05-09 15:23:15', 0, NULL),
(33, 74, 1, '2025-05-09 15:24:00', 0, NULL),
(34, 74, 1, '2025-05-09 15:29:13', 0, NULL),
(35, 64, 1, '2025-05-09 15:29:57', 0, NULL),
(36, 74, 1, '2025-05-09 15:32:28', 0, NULL),
(37, 75, 2, '2025-05-09 15:32:28', 0, NULL),
(38, 74, 1, '2025-05-09 15:37:28', 0, NULL),
(39, 60, 1, '2025-05-19 06:39:50', 0, NULL),
(40, 59, 2, '2025-05-19 06:39:50', 0, NULL),
(41, 65, 2, '2025-05-19 06:39:50', 0, NULL),
(42, 74, 1, '2025-05-19 06:39:50', 0, NULL),
(43, 75, 1, '2025-05-19 06:39:50', 0, NULL),
(44, 70, 2, '2025-05-19 06:39:50', 0, NULL),
(45, 63, 1, '2025-05-19 06:39:50', 0, NULL),
(46, 73, 1, '2025-05-19 06:39:50', 0, NULL),
(47, 58, 1, '2025-05-19 08:08:47', 0, NULL),
(48, 70, 1, '2025-05-19 16:33:25', 0, NULL),
(49, 71, 1, '2025-05-19 16:33:25', 0, NULL),
(50, 59, 1, '2025-05-19 16:33:25', 0, NULL),
(51, 67, 1, '2025-05-19 16:33:25', 0, NULL),
(52, 59, 1, '2025-05-19 16:38:48', 0, NULL),
(53, 61, 1, '2025-05-19 16:38:48', 0, NULL),
(54, 67, 4, '2025-05-19 16:43:26', 0, NULL),
(55, 66, 3, '2025-05-19 16:49:04', 0, NULL),
(56, 75, 1, '2025-05-19 16:59:38', 0, NULL),
(57, 75, 1, '2025-05-19 17:12:29', 0, NULL),
(58, 75, 1, '2025-05-19 17:14:56', 0, NULL),
(59, 75, 1, '2025-05-19 17:17:11', 0, NULL),
(60, 58, 2, '2025-05-19 17:19:45', 0, NULL),
(61, 60, 1, '2025-05-19 17:21:01', 0, NULL),
(62, 68, 1, '2025-05-19 17:21:01', 0, NULL),
(63, 66, 1, '2025-05-19 17:23:17', 0, NULL),
(64, 60, 1, '2025-05-19 17:23:58', 0, NULL),
(65, 60, 1, '2025-05-19 17:28:01', 0, NULL),
(66, 68, 1, '2025-05-19 17:28:57', 0, NULL),
(67, 74, 1, '2025-05-19 17:30:30', 0, NULL),
(68, 74, 1, '2025-05-19 17:34:05', 0, NULL),
(69, 63, 1, '2025-05-19 17:35:15', 0, NULL),
(70, 64, 2, '2025-05-19 17:41:35', 0, NULL),
(71, 65, 1, '2025-05-19 17:43:41', 0, NULL),
(72, 65, 1, '2025-05-19 17:44:10', 0, NULL),
(73, 65, 1, '2025-05-19 17:49:41', 0, NULL),
(74, 61, 1, '2025-05-19 17:49:41', 0, NULL),
(75, 65, 1, '2025-05-19 17:54:04', 0, NULL),
(76, 61, 1, '2025-05-19 17:54:04', 0, NULL),
(77, 73, 2, '2025-05-19 17:54:43', 0, NULL),
(78, 59, 1, '2025-05-19 17:58:51', 0, NULL),
(79, 68, 1, '2025-05-19 18:00:44', 0, NULL),
(80, 71, 1, '2025-05-19 18:08:00', 0, NULL),
(81, 65, 1, '2025-05-19 18:12:47', 0, NULL),
(82, 58, 1, '2025-05-20 12:42:34', 0, NULL),
(83, 61, 3, '2025-05-20 12:42:34', 0, NULL),
(84, 58, 1, '2025-05-20 12:52:18', 0, NULL),
(85, 68, 3, '2025-05-20 12:52:18', 0, NULL),
(86, 59, 1, '2025-05-20 12:54:26', 0, NULL),
(87, 67, 2, '2025-05-20 12:54:26', 0, NULL),
(88, 63, 2, '2025-05-20 12:59:21', 0, NULL),
(89, 61, 1, '2025-05-20 12:59:21', 0, NULL),
(90, 68, 1, '2025-05-20 13:02:55', 0, NULL),
(91, 74, 1, '2025-05-20 13:02:55', 0, NULL),
(92, 67, 2, '2025-05-20 13:04:52', 0, NULL),
(93, 60, 1, '2025-05-20 13:04:52', 0, NULL),
(94, 64, 1, '2025-05-20 13:05:42', 0, NULL),
(95, 58, 1, '2025-05-20 13:14:46', 0, NULL),
(96, 61, 1, '2025-05-20 13:14:46', 0, NULL),
(97, 75, 3, '2025-05-20 13:18:23', 0, NULL),
(98, 60, 1, '2025-05-20 13:18:23', 0, NULL),
(99, 63, 2, '2025-05-20 13:21:42', 0, NULL),
(100, 58, 1, '2025-05-20 13:31:28', 0, NULL),
(101, 59, 1, '2025-05-20 13:39:22', 0, NULL),
(102, 59, 1, '2025-05-20 13:41:54', 0, NULL),
(103, 58, 1, '2025-05-20 13:42:37', 0, NULL),
(104, 59, 1, '2025-05-20 14:10:57', 0, NULL),
(105, 59, 1, '2025-05-20 14:14:54', 0, NULL),
(106, 59, 1, '2025-05-20 14:32:04', 0, NULL),
(107, 68, 1, '2025-05-20 14:32:04', 0, NULL),
(108, 75, 1, '2025-05-20 14:32:04', 0, NULL),
(109, 71, 1, '2025-05-20 14:35:00', 0, NULL),
(110, 70, 1, '2025-05-20 14:39:37', 0, NULL),
(111, 71, 1, '2025-05-20 14:39:37', 0, NULL),
(112, 60, 1, '2025-05-20 14:39:37', 0, NULL),
(113, 59, 1, '2025-05-20 14:39:37', 0, NULL),
(114, 68, 1, '2025-05-20 14:39:37', 0, NULL),
(115, 75, 1, '2025-05-20 14:39:37', 0, NULL),
(116, 71, 1, '2025-05-20 14:44:40', 0, NULL),
(117, 60, 1, '2025-05-20 14:44:40', 0, NULL),
(118, 59, 1, '2025-05-20 15:04:46', 0, NULL),
(119, 74, 1, '2025-05-20 15:04:46', 0, NULL),
(120, 73, 1, '2025-05-20 15:04:46', 0, NULL),
(121, 70, 1, '2025-05-20 15:29:42', 0, NULL),
(122, 73, 1, '2025-05-20 15:32:37', 0, NULL),
(123, 68, 1, '2025-05-20 15:46:20', 0, NULL),
(124, 59, 1, '2025-05-20 15:47:18', 0, NULL),
(125, 60, 1, '2025-05-20 15:50:19', 0, NULL),
(126, 68, 1, '2025-05-20 15:51:15', 0, NULL),
(127, 59, 1, '2025-05-21 03:04:56', 0, NULL),
(128, 77, 1, '2025-05-27 02:19:55', 0, NULL),
(129, 79, 4, '2025-05-27 02:32:58', 0, NULL),
(130, 73, 2, '2025-05-31 08:33:03', 0, NULL),
(131, 61, 1, '2025-05-31 08:33:03', 0, NULL),
(132, 84, 1, '2025-06-01 05:58:35', 0, NULL),
(133, 86, 1, '2025-06-01 07:32:25', 0, NULL),
(134, 75, 1, '2025-06-01 07:32:25', 0, NULL),
(135, 85, 2, '2025-06-01 07:32:25', 0, NULL),
(136, 84, 1, '2025-06-02 07:17:50', 0, NULL),
(137, 67, 1, '2025-06-02 08:38:48', 0, NULL),
(138, 86, 1, '2025-06-02 08:46:41', 0, NULL),
(139, 71, 1, '2025-06-02 08:50:21', 0, NULL),
(141, 74, 2, '2025-06-03 09:48:56', 0, NULL),
(142, 60, 2, '2025-06-03 09:48:56', 0, NULL),
(148, 74, 0, '2025-06-03 12:22:16', 0, NULL),
(149, 77, 1, '2025-06-03 12:41:23', 0, NULL),
(150, 73, 1, '2025-06-03 13:31:44', 0, NULL),
(152, 60, 1, '2025-06-03 13:43:58', 0, NULL),
(158, 88, 0, '2025-06-03 13:56:03', 0, NULL),
(159, 58, 1, '2025-06-03 14:00:07', 0, NULL),
(160, 88, 1, '2025-06-03 14:00:25', 0, NULL),
(161, 60, 1, '2025-06-03 14:00:44', 0, NULL),
(162, 61, 1, '2025-06-03 14:01:33', 0, NULL),
(163, 60, 1, '2025-06-03 14:02:47', 0, NULL),
(164, 82, 1, '2025-06-03 14:04:11', 0, NULL),
(165, 58, 1, '2025-06-03 14:04:28', 0, NULL),
(166, 73, 1, '2025-06-03 15:08:47', 0, NULL),
(167, 61, 2, '2025-06-03 15:09:26', 0, NULL),
(168, 63, 2, '2025-06-03 15:09:26', 0, NULL),
(169, 79, 1, '2025-06-07 09:10:48', 0, NULL),
(170, 84, 1, '2025-06-07 09:11:21', 0, NULL),
(171, 88, 1, '2025-06-07 09:11:21', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `scents`
--

CREATE TABLE `scents` (
  `ScentID` int(11) NOT NULL,
  `ScentName` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scents`
--

INSERT INTO `scents` (`ScentID`, `ScentName`, `Description`) VALUES
(1, 'Floral', 'Derived from flowers such as rose, jasmine, and lavender, often soft and romantic.'),
(2, 'Fruity', 'Includes fresh, sweet, and zesty notes like apple, peach, and citrus.'),
(3, 'Woody', 'Warm, earthy notes from woods like sandalwood, cedarwood, and patchouli.'),
(4, 'Oriental', 'Rich and exotic notes like amber, spices, and resins.'),
(5, 'Citrus', 'Fresh and invigorating scents from lime, lemon, and orange.'),
(6, 'Green', 'Fresh and grassy notes evoking the smell of nature and leaves.'),
(7, 'Herbal', 'Aromatic and earthy notes like rosemary, thyme, and basil.'),
(8, 'Spicy', 'Warm, sharp, and aromatic scents like cinnamon, clove, and pepper.'),
(9, 'Gourmand', 'Edible, sweet scents such as vanilla, caramel, and chocolate.'),
(10, 'Aquatic', 'Fresh and clean notes reminiscent of the sea or water.'),
(11, 'Amber', 'Warm, resinous, and slightly sweet scents often found in oriental perfumes.'),
(12, 'Musk', 'Soft and powdery scents often used as a base note to enhance longevity.'),
(13, 'Leather', 'Rich and smoky scents with hints of wood, tobacco, or burnt notes.'),
(14, 'Aromatic', 'A complex blend of herbs and spices with a fresh and invigorating character.'),
(15, 'Fresh', 'A clean, airy scent that evokes coolness and clarity, often associated with water, citrus, or green elements.'),
(16, 'Sweet', 'Delicious, sugary, and inviting scents like candy, vanilla, or caramel, adding a playful and comforting dimension.');

-- --------------------------------------------------------

--
-- Table structure for table `seller`
--

CREATE TABLE `seller` (
  `SellerID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Phone` varchar(255) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `CompanyName` varchar(255) NOT NULL,
  `LogoUrl` varchar(255) DEFAULT NULL,
  `OpenHours` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected','suspended') DEFAULT 'pending',
  `SuspendReason` text DEFAULT NULL,
  `BankName` varchar(100) DEFAULT NULL,
  `AccountNumber` varchar(50) DEFAULT NULL,
  `Latitude` decimal(10,8) DEFAULT NULL,
  `Longitude` decimal(11,8) DEFAULT NULL,
  `ResetToken` varchar(64) DEFAULT NULL,
  `ResetExpires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller`
--

INSERT INTO `seller` (`SellerID`, `Name`, `Email`, `Phone`, `Address`, `PasswordHash`, `CompanyName`, `LogoUrl`, `OpenHours`, `CreatedAt`, `status`, `SuspendReason`, `BankName`, `AccountNumber`, `Latitude`, `Longitude`, `ResetToken`, `ResetExpires`) VALUES
(4, 'Ahmad bin Kairul', 'ahmad@gmail.com', '012345678', '2, Jalan Permas 11, Bandar Baru Permas Jaya, 81750 Johor Bahru, Johor Darul Ta\'zim, Malaysia', '$2y$10$S7XSUawpr1RD27/sGCovbOKMC9tNFOJ6v5557GP2FPvXW3nEmyZ.G', 'Ahmad Enterprise Test', 'DALL·E 2025-01-11 09.24.21 - A simple and clean logo prominently featuring the letters \'A\' and \'E\'. The letters are styled in bold and modern typography, clearly distinguishable a.webp', '10:00 - 22:00', '2025-01-11 01:25:10', 'approved', NULL, 'Maybank2u', '1234 5678 1234', 1.49568734, 103.81290436, NULL, NULL),
(5, 'Kumar bin Ikmal', 'kumar@gmail.com', '0123456', '58 jalan puj 2/26 taman puncak jalil', '$2y$10$AlzNXMvcQuBEYUKWbYqiiOh6rOI/XJQNui8Zxw0rVcOifusOfS49O', 'Kumar Interprise', '683e9de5ba162_orang.jpg', '10:00 - 22:00', '2025-01-14 15:43:05', 'approved', NULL, 'Maybank', '1234 5678 1234', 3.01707400, 101.68060810, 'ca06620660f8697f1a6a8a87b1430ea2e4acbd691748b9c1f51ea699230bdb59', '2025-05-25 18:28:16'),
(19, 'dadadd', 'kotak@gmail.com', '12345356', '822 Recto Ave, Sampaloc, Manila, 1015 Metro Manila, Philippines', '$2y$10$2Fld.nBCJe53ft8O9OwGWO7r2qkcaGCFnRcFl2ipyZ5jytOXDSipS', 'Kotak Store', '68308cbb733c0_images.png', '09:00 - 18:00', '2025-05-23 14:56:24', 'approved', NULL, '34rewfef', '123345', 14.60178412, 120.98974085, NULL, NULL),
(20, 'Mousing', 'mouse@gmail.com', '12345678', 'JX3J+4RM, Calero St, Quiapo, Manila, 1008 Metro Manila, Philippines', '$2y$10$v6GvsiA7KXky60XzsJhOGeoenGFnbdeq6g54gH.zr3.MqAsDYOTLG', 'Mouse Store', '683090c8c42ab_water.jpg', '09:00 - 18:00', '2025-05-23 15:13:24', 'suspended', 'try harder kid', 'sdfghjkl;', '12345678', 14.60273929, 120.98231649, NULL, NULL),
(21, 'Saiful Aiman', 'saiful@gmail.com', '012335468', '2964 A. Bautista, Quiapo, Maynila, 1001 Kalakhang Maynila, Philippines', '$2y$10$FS7SjkXRiRpGF8Y97dtkMO3Lgr81Pk3Al5lHiCC9gz.MbhPoTGRo2', 'Saiful Store', '6830986474c46_org.jpg', '09:00 - 18:00', '2025-05-23 15:45:00', 'approved', NULL, 'hsajshajshajs', '1234568552', 14.59763116, 120.98450518, NULL, NULL),
(22, '', 'rosmanzahir@gmail.com', '', '', '$2y$10$Ct/qEnp89DQVLsc.fv9rSurmNVmyPRO3A7n5x7XvI/Ll1ujkG1BB.', 'Zahir Store', NULL, NULL, '2025-05-25 15:21:04', 'rejected', NULL, NULL, NULL, NULL, NULL, '3fb67671ff117cf3b1901753a90f007a8fb8fdff092f10a0f514832b4b6bf7b9', '2025-05-25 18:24:30'),
(23, '', 'kain@gmail.com', '', '', '$2y$10$xO75JHtH4BY1e2JaMUVleOUfnGtBJLBbT5q9cXLDDzG8UuINs5nxC', 'Kain Store', NULL, NULL, '2025-06-03 17:05:41', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'Muhammad xQc', 'baju@gmail.com', '123345456', 'HXXQ+VJ8, Pasaje del Carmen St, Quiapo, City Of Manila, 1001 Metro Manila, Philippines', '$2y$10$3Ztk2IY5nOUD8aHAggHy.OhrEQjULG1cKbR2psb5tYKde/ZD87JNK', 'Baju Store', '683fa281069b1_foto montange something red.jpg', '09:00 - 18:00', '2025-06-04 01:32:53', 'pending', NULL, 'Maybank', '123432456456', 14.59973907, 120.98904133, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminID`);

--
-- Indexes for table `concentration`
--
ALTER TABLE `concentration`
  ADD PRIMARY KEY (`ConcentrationID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`CustomerID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `reset_token_hash` (`reset_token_hash`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `lifestyle`
--
ALTER TABLE `lifestyle`
  ADD PRIMARY KEY (`LifestyleID`);

--
-- Indexes for table `manage_order`
--
ALTER TABLE `manage_order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `personality`
--
ALTER TABLE `personality`
  ADD PRIMARY KEY (`PersonalityID`);

--
-- Indexes for table `preference`
--
ALTER TABLE `preference`
  ADD PRIMARY KEY (`preferenceID`),
  ADD KEY `customerID` (`customerID`),
  ADD KEY `questionID` (`questionID`),
  ADD KEY `customerID_2` (`customerID`),
  ADD KEY `questionID_2` (`questionID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `ConcentrationID` (`ConcentrationID`),
  ADD KEY `fk_products_scent` (`scent_id`);

--
-- Indexes for table `product_scent`
--
ALTER TABLE `product_scent`
  ADD PRIMARY KEY (`product_scent_id`);

--
-- Indexes for table `product_views`
--
ALTER TABLE `product_views`
  ADD PRIMARY KEY (`view_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `product_view_summary`
--
ALTER TABLE `product_view_summary`
  ADD PRIMARY KEY (`summary_id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`view_date`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `promo_code` (`promo_code`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`questionID`),
  ADD KEY `ConcentrationID` (`ConcentrationID`);

--
-- Indexes for table `question_scent`
--
ALTER TABLE `question_scent`
  ADD PRIMARY KEY (`questionScentID`),
  ADD KEY `questionID` (`questionID`),
  ADD KEY `ScentID` (`ScentID`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`ReviewID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `CustomerID` (`CustomerID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`SaleID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `scents`
--
ALTER TABLE `scents`
  ADD PRIMARY KEY (`ScentID`);

--
-- Indexes for table `seller`
--
ALTER TABLE `seller`
  ADD PRIMARY KEY (`SellerID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `concentration`
--
ALTER TABLE `concentration`
  MODIFY `ConcentrationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=293;

--
-- AUTO_INCREMENT for table `lifestyle`
--
ALTER TABLE `lifestyle`
  MODIFY `LifestyleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `manage_order`
--
ALTER TABLE `manage_order`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=334;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `personality`
--
ALTER TABLE `personality`
  MODIFY `PersonalityID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `preference`
--
ALTER TABLE `preference`
  MODIFY `preferenceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=89;

--
-- AUTO_INCREMENT for table `product_scent`
--
ALTER TABLE `product_scent`
  MODIFY `product_scent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT for table `product_views`
--
ALTER TABLE `product_views`
  MODIFY `view_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `product_view_summary`
--
ALTER TABLE `product_view_summary`
  MODIFY `summary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `questionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `question_scent`
--
ALTER TABLE `question_scent`
  MODIFY `questionScentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `ReviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `SaleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=172;

--
-- AUTO_INCREMENT for table `scents`
--
ALTER TABLE `scents`
  MODIFY `ScentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `seller`
--
ALTER TABLE `seller`
  MODIFY `SellerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`CustomerID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_feedback_seller` FOREIGN KEY (`seller_id`) REFERENCES `seller` (`SellerID`) ON UPDATE CASCADE;

--
-- Constraints for table `manage_order`
--
ALTER TABLE `manage_order`
  ADD CONSTRAINT `manage_order_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`),
  ADD CONSTRAINT `manage_order_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`CustomerID`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_scent` FOREIGN KEY (`scent_id`) REFERENCES `scents` (`ScentID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `seller` (`SellerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`ConcentrationID`) REFERENCES `concentration` (`ConcentrationID`) ON DELETE SET NULL;

--
-- Constraints for table `product_views`
--
ALTER TABLE `product_views`
  ADD CONSTRAINT `product_views_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_views_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`CustomerID`) ON DELETE SET NULL;

--
-- Constraints for table `product_view_summary`
--
ALTER TABLE `product_view_summary`
  ADD CONSTRAINT `product_view_summary_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_view_summary_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `seller` (`SellerID`) ON DELETE CASCADE;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `question_ibfk_2` FOREIGN KEY (`ConcentrationID`) REFERENCES `concentration` (`ConcentrationID`) ON DELETE CASCADE;

--
-- Constraints for table `question_scent`
--
ALTER TABLE `question_scent`
  ADD CONSTRAINT `question_scent_ibfk_1` FOREIGN KEY (`questionID`) REFERENCES `question` (`questionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `question_scent_ibfk_2` FOREIGN KEY (`ScentID`) REFERENCES `scents` (`ScentID`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `manage_order` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`product_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
