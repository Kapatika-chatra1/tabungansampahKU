-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 07:13 AM
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
-- Database: `tabungansampahku`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(200) NOT NULL,
  `password` varchar(200) NOT NULL,
  `no_hp` varchar(15) NOT NULL,
  `alamat` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id_user`, `nama`, `password`, `no_hp`, `alamat`, `role`) VALUES
(1, 'Farel asu', '0', '085156061082', '', 'user'),
(2, 'farel', '0', '085183205606', '', 'user'),
(3, 'reel', '$2y$10$u/4hCS8f.9SWJZ8byJbncezsVjGrz6tmHk5bKtZxZuRoPs8pVp0Ey', '081212341234', '', 'admin'),
(4, 'falihKlitih', '$2y$10$grKuH/oI.HwODytsLYPftOsDmYF7vYJseLBFsS9o8NXpXoA89tLoa', '083412341234', '', 'user'),
(5, 'taurot', '$2y$10$bIFfV0dIGX8wH5XXCmyOkunPqj8cPCnBjc2b4VH49jASKhi3EH2ci', '081256065727', 'dalen rt 25', 'user'),
(6, 'Guntur', '$2y$10$kwuoaqDUqnkdpz.tLuhsG.N4qZozTjIPZBvqyO/usQda.A0I6A5mO', '089157275605', '', 'user'),
(7, 'Putri Kudus', '$2y$10$fFE2QKaW2E73p.yhxBIWF.svluNdtkLVUtZIkbMW4o1Ct.wIUdIH2', '089458923126', '', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id_history` int(50) NOT NULL,
  `id_user` int(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jumlah_setoran` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saldo`
--

CREATE TABLE `saldo` (
  `id_saldo` int(100) NOT NULL,
  `id_user` int(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `saldo` int(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saldo`
--

INSERT INTO `saldo` (`id_saldo`, `id_user`, `nama`, `saldo`) VALUES
(1, 4, 'falihKlitih', 310000),
(2, 7, 'Putri Kudus', 1146000);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id_trans` int(100) NOT NULL,
  `id_user` int(100) NOT NULL,
  `no_hp` varchar(15) NOT NULL,
  `jenis_sampah` enum('Botol Plastik','Aluminium','Kayu','kertas') NOT NULL,
  `tanggal` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `jumlah_setoran` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id_trans`, `id_user`, `no_hp`, `jenis_sampah`, `tanggal`, `jumlah_setoran`) VALUES
(1, 3, '081212341234', 'Botol Plastik', '2025-09-04 05:12:12.443082', 25),
(3, 4, '083412341234', 'Aluminium', '2025-09-04 05:12:12.443082', 21),
(5, 2, '085183205606', 'Kayu', '2025-09-04 05:12:12.443082', 120),
(6, 4, '083412341234', 'Botol Plastik', '2025-09-04 05:12:12.443082', 10),
(7, 4, '083412341234', 'Botol Plastik', '2025-09-04 05:12:12.443082', 12),
(8, 5, '081256065727', 'kertas', '2025-09-04 05:12:12.443082', 23),
(9, 4, '083412341234', 'Aluminium', '2025-09-04 05:12:12.443082', 24),
(10, 4, '083412341234', 'Botol Plastik', '2025-09-04 05:12:12.443082', 20),
(12, 7, '089458923126', 'Aluminium', '2025-09-04 05:12:12.443082', 50),
(14, 7, '089458923126', 'Botol Plastik', '2025-09-04 05:12:12.443082', 20),
(15, 7, '089458923126', 'Kayu', '2025-09-04 05:12:12.443082', 7),
(16, 7, '089458923126', 'Aluminium', '2025-09-04 05:12:12.443082', 54),
(17, 4, '083412341234', 'Kayu', '2025-09-04 05:12:12.443082', 21),
(18, 7, '089458923126', 'Aluminium', '2025-09-04 05:12:12.443082', 12),
(19, 7, '089458923126', 'Botol Plastik', '2025-09-04 05:12:12.443082', 11),
(20, 7, '089458923126', 'Botol Plastik', '2025-09-04 05:12:12.443082', 11),
(21, 7, '089458923126', 'Botol Plastik', '2025-09-04 05:12:12.443082', 11),
(22, 7, '089458923126', 'Botol Plastik', '2025-09-04 05:12:24.000000', 11);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `no_hp` (`no_hp`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id_history`),
  ADD KEY `iduser` (`id_user`),
  ADD KEY `jumlah_setoran` (`jumlah_setoran`);

--
-- Indexes for table `saldo`
--
ALTER TABLE `saldo`
  ADD PRIMARY KEY (`id_saldo`),
  ADD KEY `user_id` (`id_user`),
  ADD KEY `name` (`nama`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id_trans`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `no_hp` (`no_hp`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id_history` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saldo`
--
ALTER TABLE `saldo`
  MODIFY `id_saldo` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id_trans` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
