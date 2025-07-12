-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 24, 2023 at 08:30 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gold_loan`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `location`) VALUES
(1, 'Main Branch', 'City Center'),
(2, 'North Branch', 'North Suburb');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `id_proof_type` varchar(50) DEFAULT NULL,
  `id_proof_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `address`, `contact_number`, `id_proof_type`, `id_proof_number`) VALUES
(1, 'John Doe', '123 Main St, Anytown', '555-1234', 'Passport', 'A12345678'),
(2, 'Jane Smith', '456 Oak Ave, Sometown', '555-5678', 'Driver\'s License', 'B87654321');

-- --------------------------------------------------------

--
-- Table structure for table `collateral_items`
--

CREATE TABLE `collateral_items` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `item_description` varchar(255) NOT NULL,
  `weight_grams` decimal(10,2) NOT NULL,
  `purity_karat` int(11) NOT NULL,
  `hallmark_verified` tinyint(1) DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `storage_location` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `collateral_items`
--

INSERT INTO `collateral_items` (`id`, `loan_id`, `item_description`, `weight_grams`, `purity_karat`, `hallmark_verified`, `image_path`, `storage_location`) VALUES
(1, 1, 'Gold Necklace', '50.00', 22, 1, 'uploads/necklace.jpg', 'Vault A, Shelf 1'),
(2, 1, 'Gold Coins (10)', '100.00', 24, 1, 'uploads/coins.jpg', 'Vault A, Shelf 1'),
(3, 2, 'Gold Bangle', '75.50', 22, 0, 'uploads/bangle.jpg', 'Vault B, Shelf 3');

-- --------------------------------------------------------

--
-- Table structure for table `gold_prices_history`
--

CREATE TABLE `gold_prices_history` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `price_24k_per_gram` decimal(10,2) NOT NULL,
  `price_22k_per_gram` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gold_prices_history`
--

INSERT INTO `gold_prices_history` (`id`, `date`, `price_24k_per_gram`, `price_22k_per_gram`) VALUES
(1, '2023-11-24', '55.00', '50.50');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `loan_number` varchar(50) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `loan_date` date NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `total_repayable` decimal(10,2) NOT NULL,
  `outstanding_amount` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'active',
  `branch_id` int(11) DEFAULT NULL,
  `officer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `loan_number`, `client_id`, `loan_date`, `due_date`, `principal_amount`, `interest_rate`, `total_repayable`, `outstanding_amount`, `status`, `branch_id`, `officer_id`) VALUES
(1, 'GL-2023-001', 1, '2023-11-01', '2024-05-01', '5000.00', '12.50', '5625.00', '5625.00', 'active', 1, 2),
(2, 'GL-2023-002', 2, '2023-11-15', '2024-11-15', '7500.00', '10.00', '8250.00', '8250.00', 'active', 1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `loan_id`, `transaction_type`, `amount`, `transaction_date`) VALUES
(1, 1, 'disbursement', '5000.00', '2023-11-01 08:00:00'),
(2, 2, 'disbursement', '7500.00', '2023-11-15 09:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `role`) VALUES
(1, 'admin', '$2y$10$H.PL3bS5isj4VjXyY03M/e5R.L3R0mS/b.I./b.I./b.I./b.I.', 'admin'),
(2, 'officer1', '$2y$10$H.PL3bS5isj4VjXyY03M/e5R.L3R0mS/b.I./b.I./b.I./b.I.', 'officer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `collateral_items`
--
ALTER TABLE `collateral_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `gold_prices_history`
--
ALTER TABLE `gold_prices_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `loan_number` (`loan_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `officer_id` (`officer_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `collateral_items`
--
ALTER TABLE `collateral_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gold_prices_history`
--
ALTER TABLE `gold_prices_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `collateral_items`
--
ALTER TABLE `collateral_items`
  ADD CONSTRAINT `collateral_items_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`officer_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
