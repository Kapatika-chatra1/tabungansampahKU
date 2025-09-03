-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2025 at 08:46 PM
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
-- Database: `phpmyadmin`
--
CREATE DATABASE IF NOT EXISTS `phpmyadmin` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--

CREATE TABLE `pma__bookmark` (
  `id` int(10) UNSIGNED NOT NULL,
  `dbase` varchar(255) NOT NULL DEFAULT '',
  `user` varchar(255) NOT NULL DEFAULT '',
  `label` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `query` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Bookmarks';

-- --------------------------------------------------------

--
-- Table structure for table `pma__central_columns`
--

CREATE TABLE `pma__central_columns` (
  `db_name` varchar(64) NOT NULL,
  `col_name` varchar(64) NOT NULL,
  `col_type` varchar(64) NOT NULL,
  `col_length` text DEFAULT NULL,
  `col_collation` varchar(64) NOT NULL,
  `col_isNull` tinyint(1) NOT NULL,
  `col_extra` varchar(255) DEFAULT '',
  `col_default` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Central list of columns';

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

CREATE TABLE `pma__column_info` (
  `id` int(5) UNSIGNED NOT NULL,
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `column_name` varchar(64) NOT NULL DEFAULT '',
  `comment` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mimetype` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `transformation` varchar(255) NOT NULL DEFAULT '',
  `transformation_options` varchar(255) NOT NULL DEFAULT '',
  `input_transformation` varchar(255) NOT NULL DEFAULT '',
  `input_transformation_options` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Column information for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__designer_settings`
--

CREATE TABLE `pma__designer_settings` (
  `username` varchar(64) NOT NULL,
  `settings_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Settings related to Designer';

-- --------------------------------------------------------

--
-- Table structure for table `pma__export_templates`
--

CREATE TABLE `pma__export_templates` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `export_type` varchar(10) NOT NULL,
  `template_name` varchar(64) NOT NULL,
  `template_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved export templates';

-- --------------------------------------------------------

--
-- Table structure for table `pma__favorite`
--

CREATE TABLE `pma__favorite` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Favorite tables';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--

CREATE TABLE `pma__history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db` varchar(64) NOT NULL DEFAULT '',
  `table` varchar(64) NOT NULL DEFAULT '',
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp(),
  `sqlquery` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='SQL history for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__navigationhiding`
--

CREATE TABLE `pma__navigationhiding` (
  `username` varchar(64) NOT NULL,
  `item_name` varchar(64) NOT NULL,
  `item_type` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hidden items of navigation tree';

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

CREATE TABLE `pma__pdf_pages` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `page_nr` int(10) UNSIGNED NOT NULL,
  `page_descr` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='PDF relation pages for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__recent`
--

CREATE TABLE `pma__recent` (
  `username` varchar(64) NOT NULL,
  `tables` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Recently accessed tables';

--
-- Dumping data for table `pma__recent`
--

INSERT INTO `pma__recent` (`username`, `tables`) VALUES
('root', '[{\"db\":\"tabungansampahku\",\"table\":\"account\"},{\"db\":\"tabungansampahku\",\"table\":\"saldo\"},{\"db\":\"tabungansampahku\",\"table\":\"transaction\"},{\"db\":\"tabungansampahku\",\"table\":\"history\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--

CREATE TABLE `pma__relation` (
  `master_db` varchar(64) NOT NULL DEFAULT '',
  `master_table` varchar(64) NOT NULL DEFAULT '',
  `master_field` varchar(64) NOT NULL DEFAULT '',
  `foreign_db` varchar(64) NOT NULL DEFAULT '',
  `foreign_table` varchar(64) NOT NULL DEFAULT '',
  `foreign_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Relation table';

-- --------------------------------------------------------

--
-- Table structure for table `pma__savedsearches`
--

CREATE TABLE `pma__savedsearches` (
  `id` int(5) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL DEFAULT '',
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `search_name` varchar(64) NOT NULL DEFAULT '',
  `search_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Saved searches';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

CREATE TABLE `pma__table_coords` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `pdf_page_number` int(11) NOT NULL DEFAULT 0,
  `x` float UNSIGNED NOT NULL DEFAULT 0,
  `y` float UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table coordinates for phpMyAdmin PDF output';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

CREATE TABLE `pma__table_info` (
  `db_name` varchar(64) NOT NULL DEFAULT '',
  `table_name` varchar(64) NOT NULL DEFAULT '',
  `display_field` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Table information for phpMyAdmin';

--
-- Dumping data for table `pma__table_info`
--

INSERT INTO `pma__table_info` (`db_name`, `table_name`, `display_field`) VALUES
('tabungansampahku', 'saldo', 'nama');

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_uiprefs`
--

CREATE TABLE `pma__table_uiprefs` (
  `username` varchar(64) NOT NULL,
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `prefs` text NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Tables'' UI preferences';

-- --------------------------------------------------------

--
-- Table structure for table `pma__tracking`
--

CREATE TABLE `pma__tracking` (
  `db_name` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NOT NULL,
  `schema_snapshot` text NOT NULL,
  `schema_sql` text DEFAULT NULL,
  `data_sql` longtext DEFAULT NULL,
  `tracking` set('UPDATE','REPLACE','INSERT','DELETE','TRUNCATE','CREATE DATABASE','ALTER DATABASE','DROP DATABASE','CREATE TABLE','ALTER TABLE','RENAME TABLE','DROP TABLE','CREATE INDEX','DROP INDEX','CREATE VIEW','ALTER VIEW','DROP VIEW') DEFAULT NULL,
  `tracking_active` int(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Database changes tracking for phpMyAdmin';

-- --------------------------------------------------------

--
-- Table structure for table `pma__userconfig`
--

CREATE TABLE `pma__userconfig` (
  `username` varchar(64) NOT NULL,
  `timevalue` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `config_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User preferences storage for phpMyAdmin';

--
-- Dumping data for table `pma__userconfig`
--

INSERT INTO `pma__userconfig` (`username`, `timevalue`, `config_data`) VALUES
('root', '2025-08-26 17:06:27', '{\"Console\\/Mode\":\"collapse\"}');

-- --------------------------------------------------------

--
-- Table structure for table `pma__usergroups`
--

CREATE TABLE `pma__usergroups` (
  `usergroup` varchar(64) NOT NULL,
  `tab` varchar(64) NOT NULL,
  `allowed` enum('Y','N') NOT NULL DEFAULT 'N'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='User groups with configured menu items';

-- --------------------------------------------------------

--
-- Table structure for table `pma__users`
--

CREATE TABLE `pma__users` (
  `username` varchar(64) NOT NULL,
  `usergroup` varchar(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Users and their assignments to user groups';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pma__central_columns`
--
ALTER TABLE `pma__central_columns`
  ADD PRIMARY KEY (`db_name`,`col_name`);

--
-- Indexes for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `db_name` (`db_name`,`table_name`,`column_name`);

--
-- Indexes for table `pma__designer_settings`
--
ALTER TABLE `pma__designer_settings`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_type_template` (`username`,`export_type`,`template_name`);

--
-- Indexes for table `pma__favorite`
--
ALTER TABLE `pma__favorite`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__history`
--
ALTER TABLE `pma__history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`,`db`,`table`,`timevalue`);

--
-- Indexes for table `pma__navigationhiding`
--
ALTER TABLE `pma__navigationhiding`
  ADD PRIMARY KEY (`username`,`item_name`,`item_type`,`db_name`,`table_name`);

--
-- Indexes for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  ADD PRIMARY KEY (`page_nr`),
  ADD KEY `db_name` (`db_name`);

--
-- Indexes for table `pma__recent`
--
ALTER TABLE `pma__recent`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__relation`
--
ALTER TABLE `pma__relation`
  ADD PRIMARY KEY (`master_db`,`master_table`,`master_field`),
  ADD KEY `foreign_field` (`foreign_db`,`foreign_table`);

--
-- Indexes for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_savedsearches_username_dbname` (`username`,`db_name`,`search_name`);

--
-- Indexes for table `pma__table_coords`
--
ALTER TABLE `pma__table_coords`
  ADD PRIMARY KEY (`db_name`,`table_name`,`pdf_page_number`);

--
-- Indexes for table `pma__table_info`
--
ALTER TABLE `pma__table_info`
  ADD PRIMARY KEY (`db_name`,`table_name`);

--
-- Indexes for table `pma__table_uiprefs`
--
ALTER TABLE `pma__table_uiprefs`
  ADD PRIMARY KEY (`username`,`db_name`,`table_name`);

--
-- Indexes for table `pma__tracking`
--
ALTER TABLE `pma__tracking`
  ADD PRIMARY KEY (`db_name`,`table_name`,`version`);

--
-- Indexes for table `pma__userconfig`
--
ALTER TABLE `pma__userconfig`
  ADD PRIMARY KEY (`username`);

--
-- Indexes for table `pma__usergroups`
--
ALTER TABLE `pma__usergroups`
  ADD PRIMARY KEY (`usergroup`,`tab`,`allowed`);

--
-- Indexes for table `pma__users`
--
ALTER TABLE `pma__users`
  ADD PRIMARY KEY (`username`,`usergroup`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__column_info`
--
ALTER TABLE `pma__column_info`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__export_templates`
--
ALTER TABLE `pma__export_templates`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__history`
--
ALTER TABLE `pma__history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__pdf_pages`
--
ALTER TABLE `pma__pdf_pages`
  MODIFY `page_nr` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pma__savedsearches`
--
ALTER TABLE `pma__savedsearches`
  MODIFY `id` int(5) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- Database: `smbd242ur_21522362`
--
CREATE DATABASE IF NOT EXISTS `smbd242ur_21522362` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `smbd242ur_21522362`;

-- --------------------------------------------------------

--
-- Table structure for table `biaya`
--

CREATE TABLE `biaya` (
  `id_biaya` smallint(10) NOT NULL,
  `id_serv` varchar(8) NOT NULL,
  `rpbiaya` int(11) DEFAULT NULL,
  `tgljam_aw` datetime DEFAULT NULL,
  `tgljam_ak` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `biaya`
--

INSERT INTO `biaya` (`id_biaya`, `id_serv`, `rpbiaya`, `tgljam_aw`, `tgljam_ak`) VALUES
(1, '100', 30000, '2025-01-01 01:01:00', '2025-12-31 11:59:00'),
(2, '110', 50000, '2025-01-01 01:01:00', '2025-12-31 11:59:00'),
(3, '210', 125000, '2025-01-01 01:01:00', '2025-12-31 11:59:00'),
(4, '220', 175000, '2025-01-01 01:01:00', '2025-12-31 11:59:00'),
(5, '510', 40000, '2025-01-01 01:01:00', '2025-12-31 11:59:00');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id_cust` varchar(8) NOT NULL,
  `nama` varchar(70) NOT NULL,
  `alamat` varchar(100) DEFAULT NULL,
  `kab_kota` varchar(100) DEFAULT NULL,
  `propinsi` varchar(100) DEFAULT NULL,
  `jk` enum('L','P') DEFAULT NULL,
  `nohp` varchar(15) DEFAULT NULL,
  `aktif` char(1) DEFAULT 't'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id_cust`, `nama`, `alamat`, `kab_kota`, `propinsi`, `jk`, `nohp`, `aktif`) VALUES
('10001', 'FANIA ASTUTI', 'Jl. ABC 23', 'Sleman', 'D.I.Yogyakarta', 'P', '098765432124', 'y'),
('10002', 'FAHRIZAL WIJAYA', 'Jl. Prenjak 23 B', 'Sleman', 'D.I.Yogyakarta', 'L', NULL, 'y'),
('10003', 'ANNISA UMMU ZEE', 'Jl. Kiwo Tengen 17', 'Bantul', 'D.I.Yogyakarta', 'P', NULL, 'y'),
('10004', 'BUDI HERMANTO', 'Jl. BCD,no. 18B', 'Sleman', 'D.I.Yogyakarta', 'L', NULL, 'y'),
('21522362', 'Reydian Hikmawan', 'Jl. Kaliurang Km. 14.5', 'Sleman', 'DI Yogyakarta', 'L', '081212341234', 'y');

-- --------------------------------------------------------

--
-- Table structure for table `detailorder`
--

CREATE TABLE `detailorder` (
  `id_do` smallint(10) NOT NULL,
  `id_order` smallint(10) NOT NULL,
  `id_serv` varchar(8) NOT NULL,
  `keluhan` varchar(200) DEFAULT NULL,
  `st_tgs` enum('1','2','3','4','5') DEFAULT '1',
  `tgljam_st` datetime DEFAULT NULL,
  `ket` varchar(150) DEFAULT NULL,
  `jumlah` smallint(3) DEFAULT NULL,
  `biaya` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `detailorder`
--

INSERT INTO `detailorder` (`id_do`, `id_order`, `id_serv`, `keluhan`, `st_tgs`, `tgljam_st`, `ket`, `jumlah`, `biaya`) VALUES
(10, 2501, '100', 'sudah waktunya ganti oli mesin', '5', '2025-07-08 09:35:42', 'aaaaaaa', 1, 30000),
(11, 2501, '210', 'Mesin terasa tidak stabil', '5', '2025-07-08 11:42:33', 'bbbbbbb', 1, 125000),
(12, 2501, '510', 'Bunyi nggeiiik pada roda depan', '1', '2025-07-08 13:52:33', 'ccccccc', 1, 40000),
(13, 2502, '510', 'Rem tidak makan dan tidak stabil', '5', '2025-07-11 14:11:14', 'ggggggg', 1, 40000),
(14, 2503, '210', 'Sudah waktunya / sampai km tuneup kembali', '5', '2025-07-19 14:21:28', 'rrrrrrr', 1, 125000),
(15, 2504, '210', 'Roda ada bunyi gesek, mesin sering tidak stabil (tidak stasioner)', '5', '2025-08-29 09:14:52', 'telah di tune-up dan telah dites', 1, 125000),
(16, 2504, '510', 'Roda ada bunyi gesek, mesin sering tidak stabil (tidak stasioner) ', '1', '2025-08-29 09:04:13', 'Roda telah dicek & kampas rem sudah bersih', 1, 40000);

-- --------------------------------------------------------

--
-- Table structure for table `merk`
--

CREATE TABLE `merk` (
  `id_merk` varchar(4) NOT NULL,
  `merk` varchar(30) DEFAULT NULL,
  `ket` varchar(70) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `merk`
--

INSERT INTO `merk` (`id_merk`, `merk`, `ket`) VALUES
('110', 'Toyota', 'Toyota'),
('120', 'Honda', 'Honda'),
('130', 'Mitsubishi', 'Mitsubishi'),
('140', 'Nissan', 'Nissan'),
('150', 'Mazda', 'Mazda');

-- --------------------------------------------------------

--
-- Table structure for table `mobil`
--

CREATE TABLE `mobil` (
  `id_mobil` smallint(8) NOT NULL,
  `nopol` varchar(10) NOT NULL,
  `id_cust` varchar(8) NOT NULL,
  `id_merk` varchar(4) NOT NULL,
  `model` varchar(30) NOT NULL,
  `bbm` enum('Bensin','Solar','Listrik','Hidrogen') DEFAULT 'Bensin',
  `tahun` year(4) DEFAULT NULL,
  `tgldaftar` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `mobil`
--

INSERT INTO `mobil` (`id_mobil`, `nopol`, `id_cust`, `id_merk`, `model`, `bbm`, `tahun`, `tgldaftar`) VALUES
(41, 'AB1234CD', '10001', '110', 'Avansa G', 'Bensin', '2016', '2025-04-02 11:14:00'),
(42, 'AB5678EF', '10004', '130', 'Pajero Sport', 'Solar', '2019', '2025-04-21 09:34:00'),
(43, 'AD4321CV', '10003', '140', 'Grand Livina', 'Bensin', '2017', '2025-05-14 08:14:00'),
(44, 'AD5432DV', '10003', '120', 'Accord', 'Bensin', '2024', '2025-05-21 10:04:00'),
(45, 'H3310RK', '10002', '120', 'Mobilio', 'Bensin', '2024', '2025-06-17 09:22:00'),
(46, 'AB 2018 CD', '21522362', '110', 'Avansa Veloz', 'Bensin', '2018', '2025-08-29 09:12:11');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id_order` smallint(10) NOT NULL,
  `id_mobil` smallint(8) NOT NULL,
  `tgljam_order` datetime NOT NULL,
  `tgljam_in` datetime NOT NULL,
  `tgljam_out` datetime DEFAULT NULL,
  `st_ak` enum('1','2','3','4','5') DEFAULT '1',
  `km_mobil` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id_order`, `id_mobil`, `tgljam_order`, `tgljam_in`, `tgljam_out`, `st_ak`, `km_mobil`) VALUES
(2501, 43, '2025-07-08 09:15:33', '2025-07-08 09:15:33', '2025-07-08 14:47:33', '5', NULL),
(2502, 41, '2025-07-11 10:25:14', '2025-07-11 10:25:14', '2025-07-11 13:41:14', '5', NULL),
(2503, 44, '2025-07-19 13:13:24', '2025-07-19 13:15:24', '2025-07-19 14:38:24', '5', NULL),
(2504, 46, '2025-08-29 04:13:01', '2025-08-29 04:13:01', '2025-08-29 09:33:00', '5', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sdm`
--

CREATE TABLE `sdm` (
  `username` varchar(60) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `sdm`
--

INSERT INTO `sdm` (`username`, `password`) VALUES
('erlangga', '202cb962ac59075b964b07152d234b70');

-- --------------------------------------------------------

--
-- Table structure for table `service`
--

CREATE TABLE `service` (
  `id_serv` varchar(8) NOT NULL,
  `nm_service` varchar(100) NOT NULL,
  `deskripsi` varchar(70) DEFAULT NULL,
  `jenis` enum('10','20','30','40') DEFAULT '10',
  `tgljam_aw` datetime DEFAULT NULL,
  `tgljam_ak` datetime DEFAULT NULL,
  `status` enum('y','t') DEFAULT 'y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `service`
--

INSERT INTO `service` (`id_serv`, `nm_service`, `deskripsi`, `jenis`, `tgljam_aw`, `tgljam_ak`, `status`) VALUES
('100', 'Ganti oli mesin paket 4l', 'Ganti oli mesin paket 4l', '10', '2025-01-01 01:01:00', '2025-12-31 11:59:00', 'y'),
('110', 'Ganti oli transmisi', 'Ganti oli transmisi', '10', '2025-01-01 01:01:00', '2025-12-31 11:59:00', 'y'),
('210', 'Engine tuneup kat-1', 'Engine tuneup kat-1', '10', '2025-01-01 01:01:00', '2025-12-31 11:59:00', 'y'),
('220', 'Engine tuneup kat-2', 'Engine tuneup kat-2', '10', '2025-01-01 01:01:00', '2025-12-31 11:59:00', 'y'),
('510', 'Servis roda', 'Cek & bersihkan kampas rem dsb', '10', '2025-01-01 01:01:00', '2025-12-31 11:59:00', 'y');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `biaya`
--
ALTER TABLE `biaya`
  ADD PRIMARY KEY (`id_biaya`),
  ADD KEY `id_biaya` (`id_serv`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id_cust`);

--
-- Indexes for table `detailorder`
--
ALTER TABLE `detailorder`
  ADD PRIMARY KEY (`id_do`),
  ADD KEY `do_fk1` (`id_order`),
  ADD KEY `do_fk2` (`id_serv`);

--
-- Indexes for table `merk`
--
ALTER TABLE `merk`
  ADD PRIMARY KEY (`id_merk`);

--
-- Indexes for table `mobil`
--
ALTER TABLE `mobil`
  ADD PRIMARY KEY (`id_mobil`),
  ADD KEY `fk1_mobil_customer` (`id_cust`),
  ADD KEY `fk2_mobil_merk` (`id_merk`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `id_mobil` (`id_mobil`);

--
-- Indexes for table `service`
--
ALTER TABLE `service`
  ADD PRIMARY KEY (`id_serv`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `biaya`
--
ALTER TABLE `biaya`
  MODIFY `id_biaya` smallint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `detailorder`
--
ALTER TABLE `detailorder`
  MODIFY `id_do` smallint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` smallint(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2505;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `biaya`
--
ALTER TABLE `biaya`
  ADD CONSTRAINT `biaya_ibfk_1` FOREIGN KEY (`id_serv`) REFERENCES `service` (`id_serv`);

--
-- Constraints for table `detailorder`
--
ALTER TABLE `detailorder`
  ADD CONSTRAINT `do_fk1` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`),
  ADD CONSTRAINT `do_fk2` FOREIGN KEY (`id_serv`) REFERENCES `service` (`id_serv`);

--
-- Constraints for table `mobil`
--
ALTER TABLE `mobil`
  ADD CONSTRAINT `fk1_mobil_customer` FOREIGN KEY (`id_cust`) REFERENCES `customer` (`id_cust`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk2_mobil_merk` FOREIGN KEY (`id_merk`) REFERENCES `merk` (`id_merk`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`id_mobil`) REFERENCES `mobil` (`id_mobil`);
--
-- Database: `tabungansampahku`
--
CREATE DATABASE IF NOT EXISTS `tabungansampahku` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tabungansampahku`;

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
(2, 7, 'Putri Kudus', 842000);

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id_trans` int(100) NOT NULL,
  `id_user` int(100) NOT NULL,
  `no_hp` varchar(15) NOT NULL,
  `jenis_sampah` enum('Botol Plastik','Aluminium','Kayu','kertas') NOT NULL,
  `jumlah_setoran` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id_trans`, `id_user`, `no_hp`, `jenis_sampah`, `jumlah_setoran`) VALUES
(1, 3, '081212341234', 'Botol Plastik', 25),
(3, 4, '083412341234', 'Aluminium', 21),
(5, 2, '085183205606', 'Kayu', 120),
(6, 4, '083412341234', 'Botol Plastik', 10),
(7, 4, '083412341234', 'Botol Plastik', 12),
(8, 5, '081256065727', 'kertas', 23),
(9, 4, '083412341234', 'Aluminium', 24),
(10, 4, '083412341234', 'Botol Plastik', 20),
(12, 7, '089458923126', 'Aluminium', 50),
(14, 7, '089458923126', 'Botol Plastik', 20),
(15, 7, '089458923126', 'Kayu', 7),
(16, 7, '089458923126', 'Aluminium', 54),
(17, 4, '083412341234', 'Kayu', 21);

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
  MODIFY `id_trans` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `iduser` FOREIGN KEY (`id_user`) REFERENCES `account` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `jumlah_setoran` FOREIGN KEY (`jumlah_setoran`) REFERENCES `transaction` (`jumlah_setoran`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `saldo`
--
ALTER TABLE `saldo`
  ADD CONSTRAINT `name` FOREIGN KEY (`nama`) REFERENCES `account` (`nama`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_id` FOREIGN KEY (`id_user`) REFERENCES `account` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `id_user` FOREIGN KEY (`id_user`) REFERENCES `account` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `no_hp` FOREIGN KEY (`no_hp`) REFERENCES `account` (`no_hp`) ON DELETE CASCADE ON UPDATE CASCADE;
--
-- Database: `test`
--
CREATE DATABASE IF NOT EXISTS `test` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `test`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
