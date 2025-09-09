-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2025 at 04:42 PM
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
  `role` enum('admin','user','super_admin') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id_user`, `nama`, `password`, `no_hp`, `alamat`, `role`) VALUES
(1, 'Farel asu', '0', '085156061082', '', 'user'),
(2, 'farel', '0', '085183205606', '', 'user'),
(3, 'reel', '$2y$10$u/4hCS8f.9SWJZ8byJbncezsVjGrz6tmHk5bKtZxZuRoPs8pVp0Ey', '081212341234', '', 'admin'),
(4, 'falihKlitih', '$2y$10$czw3bgyWvTvVp7fvBT3tCuUK1fqmmYI5spmwYYYcrSb59gnG1HO7O', '083412341234', '', 'user'),
(5, 'taurot', '$2y$10$bIFfV0dIGX8wH5XXCmyOkunPqj8cPCnBjc2b4VH49jASKhi3EH2ci', '081256065727', 'dalen rt 25', 'user'),
(6, 'Guntur', '$2y$10$kwuoaqDUqnkdpz.tLuhsG.N4qZozTjIPZBvqyO/usQda.A0I6A5mO', '089157275605', '', 'user'),
(7, 'Putri Kudus', '$2y$10$fFE2QKaW2E73p.yhxBIWF.svluNdtkLVUtZIkbMW4o1Ct.wIUdIH2', '089458923126', '', 'user'),
(8, 'Sorogaten II', '$2y$10$ntRl2Vnx4eg3A/i6eLHBR.dclow5x3E4KcuI/uMGpAGLDwsq7H4/C', '081312341234', 'Padukuhan Sorogaten II', 'user'),
(9, 'Kalurahan', '$2y$10$7.v8xPBsCpNPwJPsFvZjwui3cNA/KQw1mqQJ2Z6LPIJZJxC8LWFZG', '082312341234', 'Kalurahan Karangsewu', 'super_admin'),
(10, 'Wisnu Setiawan', '$2y$10$YtrTVmJ../QN6vKlC5vog.p4HQmA1dilnfT3Gu1EbwyfZY7YM9Dwe', '085612341234', 'Dalen', 'admin');

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
-- Table structure for table `jenis_sampah`
--

CREATE TABLE `jenis_sampah` (
  `id_jenis` int(10) NOT NULL,
  `id_kategori` int(10) NOT NULL,
  `jenis` varchar(20) NOT NULL,
  `harga` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenis_sampah`
--

INSERT INTO `jenis_sampah` (`id_jenis`, `id_kategori`, `jenis`, `harga`) VALUES
(1, 1, 'Botol PET', 5000),
(2, 1, 'HDPE (Jerigen, Botol', 6000),
(3, 1, 'PP (gelas plastik, s', 4500),
(4, 1, 'Kresek', 1000),
(5, 1, 'Plastik Keras (Mika,', 3500),
(6, 2, 'Kardus', 2000),
(7, 2, 'Kertas HVS', 1500),
(8, 2, 'Kertas Koran', 1000),
(9, 2, 'Buku Tulis', 3500),
(10, 2, 'Majalah, Brosur, Ker', 3500),
(11, 3, 'Kaleng Alumunium', 7500),
(12, 3, 'Besi Bekas (Paku, Se', 8500),
(13, 3, 'Aluminium', 8000),
(14, 3, 'Tembaga (kabel listr', 5500),
(15, 3, 'Kuningan (Perunggu, ', 6500),
(16, 4, 'Botol Kaca', 3500),
(17, 4, 'Pecahan Kaca', 2500),
(18, 4, 'Kaca Warna (Tanpa Kr', 3500),
(19, 5, 'Minyak Jelantah', 1000),
(20, 5, 'Aki Bekas', 4500),
(21, 5, 'Laptop Rusak', 6500),
(22, 5, 'Ban Bekas', 3500),
(23, 5, 'Tekstil (Baju Bekas)', 5500),
(24, 5, 'Hp Bekas', 9000);

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(50) NOT NULL,
  `kategori` varchar(20) NOT NULL DEFAULT 'Plastik'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `kategori`) VALUES
(1, 'Plastik'),
(2, 'Kertas'),
(3, 'Logam'),
(4, 'Kaca'),
(5, 'Elektronik'),
(6, 'Lain-lain');

-- --------------------------------------------------------

--
-- Table structure for table `location_points`
--

CREATE TABLE `location_points` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` enum('Pengepul','TPS','Bank Sampah','Lainnya') NOT NULL DEFAULT 'Pengepul',
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lng` decimal(10,7) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_points`
--

INSERT INTO `location_points` (`id`, `name`, `type`, `phone`, `address`, `lat`, `lng`, `active`, `updated_at`) VALUES
(2, 'TPS Timur Balai Desa', 'TPS', NULL, 'Dekat Balai Desa', -7.8721000, 110.1159000, 1, '2025-09-09 14:06:19'),
(3, 'Pengepul Pak Heru', 'Pengepul', '085566667777', 'Dalen', -7.9432049, 110.2131439, 0, '2025-09-09 14:11:04'),
(4, 'Pengepul Pak Heru', 'Pengepul', '0855666777', 'Padukuhan Dalen', -7.9431688, 110.2131380, 1, '2025-09-09 14:13:04');

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
(1, 4, 'falihKlitih', 358000),
(2, 7, 'Putri Kudus', 1146000),
(3, 8, 'Sorogaten II', 140000);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id_trans` int(100) NOT NULL,
  `id_user` int(100) NOT NULL,
  `no_hp` varchar(15) NOT NULL,
  `id_jenis` int(10) NOT NULL,
  `tanggal` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `jumlah_setoran` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id_trans`, `id_user`, `no_hp`, `id_jenis`, `tanggal`, `jumlah_setoran`) VALUES
(1, 4, '083412341234', 13, '2025-09-04 05:12:12.443082', 24),
(2, 7, '089458923126', 13, '2025-09-04 05:12:12.443082', 50),
(3, 7, '089458923126', 13, '2025-09-04 05:12:12.443082', 54),
(4, 7, '089458923126', 13, '2025-09-04 05:12:12.443082', 12),
(5, 8, '081312341234', 13, '2025-09-04 06:48:06.000000', 20);

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
-- Indexes for table `jenis_sampah`
--
ALTER TABLE `jenis_sampah`
  ADD PRIMARY KEY (`id_jenis`),
  ADD KEY `id_kat` (`id_kategori`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `location_points`
--
ALTER TABLE `location_points`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_type` (`active`,`type`),
  ADD KEY `idx_lat_lng` (`lat`,`lng`);

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
  ADD KEY `no_hp` (`no_hp`),
  ADD KEY `jenis` (`id_jenis`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id_history` int(50) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jenis_sampah`
--
ALTER TABLE `jenis_sampah`
  MODIFY `id_jenis` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `location_points`
--
ALTER TABLE `location_points`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `saldo`
--
ALTER TABLE `saldo`
  MODIFY `id_saldo` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id_trans` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jenis_sampah`
--
ALTER TABLE `jenis_sampah`
  ADD CONSTRAINT `id_kat` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `jenis` FOREIGN KEY (`id_jenis`) REFERENCES `jenis_sampah` (`id_jenis`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
