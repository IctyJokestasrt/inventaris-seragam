SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- 1. Struktur & Data `defect_reasons`
-- --------------------------------------------------------
CREATE TABLE `defect_reasons` (
  `id_reason` int(11) NOT NULL AUTO_INCREMENT,
  `nama_alasan` varchar(100) NOT NULL,
  PRIMARY KEY (`id_reason`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `defect_reasons` (`id_reason`, `nama_alasan`) VALUES
(1, 'Jahitan Lepas'),
(2, 'Kain Sobek'),
(3, 'Warna Luntur'),
(4, 'Ukuran Tidak Sesuai');

-- --------------------------------------------------------
-- 2. Struktur `internal_messages` (Log Aktivitas Admin & Notifikasi)
-- --------------------------------------------------------
CREATE TABLE `internal_messages` (
  `id_msg` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_role` enum('admin','manager') DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `tgl_kirim` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_msg`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------
-- 3. Struktur & Data `kas_kedai`
-- --------------------------------------------------------
CREATE TABLE `kas_kedai` (
  `id_kas` int(11) NOT NULL,
  `saldo_saat_ini` int(11) DEFAULT 0,
  PRIMARY KEY (`id_kas`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `kas_kedai` (`id_kas`, `saldo_saat_ini`) VALUES (1, 0);

-- --------------------------------------------------------
-- 4. Struktur & Data `master_sizes` (Lengkap: Numerik Kecil, Alpha, Besar, Allsize)
-- --------------------------------------------------------
CREATE TABLE `master_sizes` (
  `id_size` int(11) NOT NULL AUTO_INCREMENT,
  `tipe_size` enum('ALPHA','NUMERIC','ALLSIZE') NOT NULL,
  `nama_size` varchar(20) NOT NULL,
  `urutan` int(11) DEFAULT 0,
  PRIMARY KEY (`id_size`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `master_sizes` (`id_size`, `tipe_size`, `nama_size`, `urutan`) VALUES
(1, 'ALPHA', 'S', 1),
(2, 'ALPHA', 'M', 2),
(3, 'ALPHA', 'L', 3),
(4, 'ALPHA', 'XL', 4),
(5, 'ALPHA', '2XL', 5),
(6, 'ALPHA', '3XL', 6),
(7, 'ALPHA', '4XL', 7),
(8, 'ALPHA', '5XL', 8),
(9, 'ALPHA', '6XL', 9),
(10, 'ALPHA', '7XL', 10),
(11, 'ALPHA', '8XL', 11),
(12, 'ALPHA', '9XL', 12),
(13, 'ALPHA', '10XL', 13),
(14, 'ALPHA', '12XL', 15),
(15, 'NUMERIC', '0', 0),
(16, 'NUMERIC', '1', 1),
(17, 'NUMERIC', '2', 2),
(18, 'NUMERIC', '3', 3),
(19, 'NUMERIC', '4', 4),
(20, 'NUMERIC', '5', 5),
(21, 'NUMERIC', '6', 6),
(22, 'NUMERIC', '7', 7),
(23, 'NUMERIC', '8', 8),
(24, 'NUMERIC', '27', 20),
(25, 'NUMERIC', '28', 21),
(26, 'NUMERIC', '29', 22),
(27, 'NUMERIC', '30', 23),
(28, 'ALLSIZE', 'ALLSIZE', 1);

-- --------------------------------------------------------
-- 5. Struktur & Data `waves` (Sudah ditambah tgl_mulai & tgl_selesai)
-- --------------------------------------------------------
CREATE TABLE `waves` (
  `id_wave` int(11) NOT NULL AUTO_INCREMENT,
  `nama_gelombang` varchar(50) DEFAULT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_wave`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `waves` (`id_wave`, `nama_gelombang`, `tgl_mulai`, `tgl_selesai`, `is_active`) VALUES
(1, 'GELOMBANG 1', '2026-01-01', '2026-12-31', 1);

-- --------------------------------------------------------
-- 6. Struktur `orders` (Sudah ditambah nomor_hp)
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL AUTO_INCREMENT,
  `id_wave` int(11) DEFAULT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `nomor_hp` varchar(20) DEFAULT NULL,
  `kelas_calon` varchar(50) DEFAULT NULL,
  `jenjang_siswa` varchar(20) DEFAULT NULL,
  `bordir_nama` varchar(100) DEFAULT NULL,
  `tgl_ukur` datetime DEFAULT current_timestamp(),
  `status_order` enum('po','ready','diambil','batal') DEFAULT 'po',
  `nama_pengambil` varchar(100) DEFAULT NULL,
  `tgl_ambil` datetime DEFAULT NULL,
  `total_biaya` int(11) DEFAULT 0,
  `sudah_bayar` int(11) DEFAULT 0,
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `id_produksi` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_order`),
  KEY `id_wave` (`id_wave`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_wave`) REFERENCES `waves` (`id_wave`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------
-- 7. Struktur & Data `products` (Termasuk pemisahan Topi, Badge, Bordir Nama, Lokasi)
-- --------------------------------------------------------
CREATE TABLE `products` (
  `id_produk` int(11) NOT NULL AUTO_INCREMENT,
  `nama_produk` varchar(100) NOT NULL,
  `jenjang` enum('TK','SD','SMP','SMA','SMK','UMUM') NOT NULL,
  `kategori` enum('SERAGAM','OLAHRAGA','ATRIBUT') NOT NULL,
  `tipe_ukuran` enum('ALPHA','NUMERIC','ALLSIZE') DEFAULT 'ALPHA',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_produk`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `products` (`id_produk`, `nama_produk`, `jenjang`, `kategori`, `tipe_ukuran`, `is_active`) VALUES
(1, 'Baju Sailor', 'TK', 'SERAGAM', 'ALPHA', 1),
(2, 'Rok Sailor', 'TK', 'SERAGAM', 'NUMERIC', 1),
(3, 'Celana Sailor', 'TK', 'SERAGAM', 'NUMERIC', 1),
(4, 'Hem Putih TK', 'TK', 'SERAGAM', 'NUMERIC', 1),
(5, 'Rompi Pink TK', 'TK', 'SERAGAM', 'NUMERIC', 1),
(6, 'Rok Kulot Krem TK', 'TK', 'SERAGAM', 'NUMERIC', 1),
(7, 'Celana Pendek Krem TK', 'TK', 'SERAGAM', 'NUMERIC', 1),
(8, 'Kaos Olah Raga TK', 'TK', 'OLAHRAGA', 'NUMERIC', 1),
(9, 'Celana Olah Raga TK', 'TK', 'OLAHRAGA', 'ALPHA', 1),
(10, 'Rompi Biru SD', 'SD', 'SERAGAM', 'ALPHA', 1),
(11, 'Celana Pendek Krem SD', 'SD', 'SERAGAM', 'ALPHA', 1),
(12, 'Rok Kulot Krem SD', 'SD', 'SERAGAM', 'ALPHA', 1),
(13, 'Seragam Olah Raga SD', 'SD', 'OLAHRAGA', 'ALPHA', 1),
(14, 'Seragam Olah Raga SMP', 'SMP', 'OLAHRAGA', 'ALPHA', 1),
(15, 'Rompi Hitam SMP', 'SMP', 'SERAGAM', 'ALPHA', 1),
(16, 'Celana Panjang Krem SMP', 'SMP', 'SERAGAM', 'ALPHA', 1),
(17, 'Rok Kulot Krem SMP', 'SMP', 'SERAGAM', 'ALPHA', 1),
(18, 'Batik SMP', 'SMP', 'SERAGAM', 'ALPHA', 1),
(19, 'OSIS SMP', 'SMP', 'SERAGAM', 'ALPHA', 1),
(20, 'Seragam Olah Raga SMA', 'SMA', 'OLAHRAGA', 'ALPHA', 1),
(21, 'Rompi Merah SMA', 'SMA', 'SERAGAM', 'ALPHA', 1),
(22, 'Celana Panjang Krem SMA', 'SMA', 'SERAGAM', 'ALPHA', 1),
(23, 'Rok Kulot Kotak SMA', 'SMA', 'SERAGAM', 'ALPHA', 1),
(24, 'Seragam Olah Raga SMK', 'SMK', 'OLAHRAGA', 'ALPHA', 1),
(25, 'Hem Executive TKJ', 'SMK', 'SERAGAM', 'ALPHA', 1),
(26, 'Hem Executive MM', 'SMK', 'SERAGAM', 'ALPHA', 1),
(27, 'Hem Executive DKV', 'SMK', 'SERAGAM', 'ALPHA', 1),
(28, 'Hem Merah SMK', 'SMK', 'SERAGAM', 'ALPHA', 1),
(29, 'Celana Panjang Putih SMK', 'SMK', 'SERAGAM', 'ALPHA', 1),
(30, 'Topi Sekolah', 'UMUM', 'ATRIBUT', 'ALLSIZE', 1),
(31, 'Badge', 'UMUM', 'ATRIBUT', 'ALLSIZE', 1),
(32, 'Bordir Nama', 'UMUM', 'ATRIBUT', 'ALLSIZE', 1),
(33, 'Lokasi', 'UMUM', 'ATRIBUT', 'ALLSIZE', 1);

-- --------------------------------------------------------
-- 8. Struktur & Data `variants`
-- --------------------------------------------------------
CREATE TABLE `variants` (
  `id_variant` int(11) NOT NULL AUTO_INCREMENT,
  `id_produk` int(11) DEFAULT NULL,
  `ukuran` varchar(20) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `target_tahunan` int(11) DEFAULT 0,
  `kode_barcode` varchar(50) DEFAULT NULL,
  `catatan_po` text DEFAULT NULL,
  PRIMARY KEY (`id_variant`),
  UNIQUE KEY `kode_barcode` (`kode_barcode`),
  KEY `id_produk` (`id_produk`),
  CONSTRAINT `variants_ibfk_1` FOREIGN KEY (`id_produk`) REFERENCES `products` (`id_produk`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `variants` (`id_variant`, `id_produk`, `ukuran`, `stok`, `target_tahunan`, `kode_barcode`, `catatan_po`) VALUES
(1, 1, 'S', 0, 0, NULL, NULL), (2, 1, 'M', 0, 0, NULL, NULL), (3, 1, 'L', 0, 0, NULL, NULL), (4, 1, 'XL', 0, 0, NULL, NULL), (5, 1, 'L3', 0, 0, NULL, NULL), (6, 1, 'L4', 0, 0, NULL, NULL), (7, 1, 'L5', 0, 0, NULL, NULL),
(8, 2, '0', 0, 0, NULL, NULL), (9, 2, '1', 0, 0, NULL, NULL), (10, 2, '2', 0, 0, NULL, NULL), (11, 2, '3', 0, 0, NULL, NULL), (12, 2, '4', 0, 0, NULL, NULL), (13, 2, '5', 0, 0, NULL, NULL), (14, 2, '6', 0, 0, NULL, NULL), (15, 2, '7', 0, 0, NULL, NULL),
(16, 3, '0', 0, 0, NULL, NULL), (17, 3, '1', 0, 0, NULL, NULL), (18, 3, '2', 0, 0, NULL, NULL), (19, 3, '3', 0, 0, NULL, NULL), (20, 3, '4', 0, 0, NULL, NULL), (21, 3, '5', 0, 0, NULL, NULL), (22, 3, '6', 0, 0, NULL, NULL), (23, 3, '7', 0, 0, NULL, NULL),
(24, 4, '5', 0, 0, NULL, NULL), (25, 4, '6', 0, 0, NULL, NULL), (26, 4, '7', 0, 0, NULL, NULL), (27, 4, '8', 0, 0, NULL, NULL), (28, 4, '9', 0, 0, NULL, NULL), (29, 4, '10', 0, 0, NULL, NULL), (30, 4, '11', 0, 0, NULL, NULL), (31, 4, '12', 0, 0, NULL, NULL),
(32, 5, '0', 0, 0, NULL, NULL), (33, 5, '1', 0, 0, NULL, NULL), (34, 5, '2', 0, 0, NULL, NULL), (35, 5, '3', 0, 0, NULL, NULL), (36, 5, '4', 0, 0, NULL, NULL), (37, 5, '5', 0, 0, NULL, NULL), (38, 5, '6', 0, 0, NULL, NULL),
(39, 6, '1', 0, 0, NULL, NULL), (40, 6, '2', 0, 0, NULL, NULL), (41, 6, '3', 0, 0, NULL, NULL), (42, 6, '4', 0, 0, NULL, NULL), (43, 6, '5', 0, 0, NULL, NULL), (44, 6, '6', 0, 0, NULL, NULL), (45, 6, '7', 0, 0, NULL, NULL), (46, 6, '8', 0, 0, NULL, NULL),
(47, 7, '1', 0, 0, NULL, NULL), (48, 7, '2', 0, 0, NULL, NULL), (49, 7, '3', 0, 0, NULL, NULL), (50, 7, '4', 0, 0, NULL, NULL), (51, 7, '5', 0, 0, NULL, NULL), (52, 7, '6', 0, 0, NULL, NULL), (53, 7, '7', 0, 0, NULL, NULL), (54, 7, '8', 0, 0, NULL, NULL),
(55, 8, '0', 0, 0, NULL, NULL), (56, 8, '1', 0, 0, NULL, NULL), (57, 8, '2', 0, 0, NULL, NULL), (58, 8, '3', 0, 0, NULL, NULL), (59, 8, '4', 0, 0, NULL, NULL), (60, 8, '5', 0, 0, NULL, NULL), (61, 8, '6', 0, 0, NULL, NULL),
(62, 9, 'S', 0, 0, NULL, NULL), (63, 9, 'M', 0, 0, NULL, NULL), (64, 9, 'L', 0, 0, NULL, NULL), (65, 9, 'XL', 0, 0, NULL, NULL), (66, 9, 'L3', 0, 0, NULL, NULL), (67, 9, 'L4', 0, 0, NULL, NULL), (68, 9, 'L5', 0, 0, NULL, NULL),
(69, 10, 'S', 0, 0, NULL, NULL), (70, 10, 'M', 0, 0, NULL, NULL), (71, 10, 'L', 0, 0, NULL, NULL), (72, 10, 'XL', 0, 0, NULL, NULL), (73, 10, '2XL', 0, 0, NULL, NULL), (74, 10, '3XL', 0, 0, NULL, NULL), (75, 10, '4XL', 0, 0, NULL, NULL), (76, 10, '5XL', 0, 0, NULL, NULL), (77, 10, '6XL', 0, 0, NULL, NULL), (78, 10, '7XL', 0, 0, NULL, NULL), (79, 10, '8XL', 0, 0, NULL, NULL), (80, 10, '9XL', 0, 0, NULL, NULL), (81, 10, '10XL', 0, 0, NULL, NULL),
(82, 11, 'S', 0, 0, NULL, NULL), (83, 11, 'M', 0, 0, NULL, NULL), (84, 11, 'L', 0, 0, NULL, NULL), (85, 11, 'XL', 0, 0, NULL, NULL), (86, 11, '2XL', 0, 0, NULL, NULL), (87, 11, '3XL', 0, 0, NULL, NULL), (88, 11, '4XL', 0, 0, NULL, NULL), (89, 11, '5XL', 0, 0, NULL, NULL), (90, 11, '6XL', 0, 0, NULL, NULL), (91, 11, '7XL', 0, 0, NULL, NULL), (92, 11, '8XL', 0, 0, NULL, NULL), (93, 11, '9XL', 0, 0, NULL, NULL), (94, 11, '10XL', 0, 0, NULL, NULL),
(95, 12, 'S', 0, 0, NULL, NULL), (96, 12, 'M', 0, 0, NULL, NULL), (97, 12, 'L', 0, 0, NULL, NULL), (98, 12, 'XL', 0, 0, NULL, NULL), (99, 12, '2XL', 0, 0, NULL, NULL), (100, 12, '3XL', 0, 0, NULL, NULL), (101, 12, '4XL', 0, 0, NULL, NULL), (102, 12, '5XL', 0, 0, NULL, NULL), (103, 12, '6XL', 0, 0, NULL, NULL), (104, 12, '7XL', 0, 0, NULL, NULL), (105, 12, '8XL', 0, 0, NULL, NULL), (106, 12, '9XL', 0, 0, NULL, NULL), (107, 12, '10XL', 0, 0, NULL, NULL),
(108, 13, 'S', 0, 0, NULL, NULL), (109, 13, 'M', 0, 0, NULL, NULL), (110, 13, 'L', 0, 0, NULL, NULL), (111, 13, 'XL', 0, 0, NULL, NULL), (112, 13, '2XL', 0, 0, NULL, NULL), (113, 13, '3XL', 0, 0, NULL, NULL), (114, 13, '4XL', 0, 0, NULL, NULL), (115, 13, '5XL', 0, 0, NULL, NULL), (116, 13, '6XL', 0, 0, NULL, NULL), (117, 13, '7XL', 0, 0, NULL, NULL), (118, 13, '8XL', 0, 0, NULL, NULL), (119, 13, '9XL', 0, 0, NULL, NULL), (120, 13, '10XL', 0, 0, NULL, NULL),
(121, 14, 'S', 0, 0, NULL, NULL), (122, 14, 'M', 0, 0, NULL, NULL), (123, 14, 'L', 0, 0, NULL, NULL), (124, 14, 'XL', 0, 0, NULL, NULL), (125, 14, '2XL', 0, 0, NULL, NULL), (126, 14, '3XL', 0, 0, NULL, NULL), (127, 14, '4XL', 0, 0, NULL, NULL), (128, 14, '5XL', 0, 0, NULL, NULL), (129, 14, '6XL', 0, 0, NULL, NULL), (130, 14, '7XL', 0, 0, NULL, NULL), (131, 14, '8XL', 0, 0, NULL, NULL),
(132, 15, 'S', 0, 0, NULL, NULL), (133, 15, 'M', 0, 0, NULL, NULL), (134, 15, 'L', 0, 0, NULL, NULL), (135, 15, 'XL', 0, 0, NULL, NULL), (136, 15, '2XL', 0, 0, NULL, NULL), (137, 15, '3XL', 0, 0, NULL, NULL), (138, 15, '4XL', 0, 0, NULL, NULL), (139, 15, '5XL', 0, 0, NULL, NULL), (140, 15, '6XL', 0, 0, NULL, NULL), (141, 15, '7XL', 0, 0, NULL, NULL), (142, 15, '8XL', 0, 0, NULL, NULL),
(143, 16, 'S', 0, 0, NULL, NULL), (144, 16, 'M', 0, 0, NULL, NULL), (145, 16, 'L', 0, 0, NULL, NULL), (146, 16, 'XL', 0, 0, NULL, NULL), (147, 16, '2XL', 0, 0, NULL, NULL), (148, 16, '3XL', 0, 0, NULL, NULL), (149, 16, '4XL', 0, 0, NULL, NULL), (150, 16, '5XL', 0, 0, NULL, NULL), (151, 16, '6XL', 0, 0, NULL, NULL), (152, 16, '7XL', 0, 0, NULL, NULL), (153, 16, '8XL', 0, 0, NULL, NULL),
(154, 17, 'S', 0, 0, NULL, NULL), (155, 17, 'M', 0, 0, NULL, NULL), (156, 17, 'L', 0, 0, NULL, NULL), (157, 17, 'XL', 0, 0, NULL, NULL), (158, 17, '2XL', 0, 0, NULL, NULL), (159, 17, '3XL', 0, 0, NULL, NULL), (160, 17, '4XL', 0, 0, NULL, NULL), (161, 17, '5XL', 0, 0, NULL, NULL), (162, 17, '6XL', 0, 0, NULL, NULL), (163, 17, '7XL', 0, 0, NULL, NULL), (164, 17, '8XL', 0, 0, NULL, NULL),
(165, 18, 'S', 0, 0, NULL, NULL), (166, 18, 'M', 0, 0, NULL, NULL), (167, 18, 'L', 0, 0, NULL, NULL), (168, 18, 'XL', 0, 0, NULL, NULL), (169, 18, '2XL', 0, 0, NULL, NULL), (170, 18, '3XL', 0, 0, NULL, NULL), (171, 18, '4XL', 0, 0, NULL, NULL), (172, 18, '5XL', 0, 0, NULL, NULL), (173, 18, '6XL', 0, 0, NULL, NULL), (174, 18, '7XL', 0, 0, NULL, NULL), (175, 18, '8XL', 0, 0, NULL, NULL),
(176, 19, 'S', 0, 0, NULL, NULL), (177, 19, 'M', 0, 0, NULL, NULL), (178, 19, 'L', 0, 0, NULL, NULL), (179, 19, 'XL', 0, 0, NULL, NULL), (180, 19, '2XL', 0, 0, NULL, NULL), (181, 19, '3XL', 0, 0, NULL, NULL), (182, 19, '4XL', 0, 0, NULL, NULL), (183, 19, '5XL', 0, 0, NULL, NULL), (184, 19, '6XL', 0, 0, NULL, NULL), (185, 19, '7XL', 0, 0, NULL, NULL), (186, 19, '8XL', 0, 0, NULL, NULL),
(187, 20, 'S', 0, 0, NULL, NULL), (188, 20, 'M', 0, 0, NULL, NULL), (189, 20, 'L', 0, 0, NULL, NULL), (190, 20, 'XL', 0, 0, NULL, NULL), (191, 20, '2XL', 0, 0, NULL, NULL), (192, 20, '3XL', 0, 0, NULL, NULL), (193, 20, '4XL', 0, 0, NULL, NULL), (194, 20, '5XL', 0, 0, NULL, NULL), (195, 20, '6XL', 0, 0, NULL, NULL), (196, 20, '7XL', 0, 0, NULL, NULL), (197, 20, '8XL', 0, 0, NULL, NULL),
(198, 21, 'S', 0, 0, NULL, NULL), (199, 21, 'M', 0, 0, NULL, NULL), (200, 21, 'L', 0, 0, NULL, NULL), (201, 21, 'XL', 0, 0, NULL, NULL), (202, 21, '2XL', 0, 0, NULL, NULL), (203, 21, '3XL', 0, 0, NULL, NULL), (204, 21, '4XL', 0, 0, NULL, NULL), (205, 21, '5XL', 0, 0, NULL, NULL), (206, 21, '6XL', 0, 0, NULL, NULL), (207, 21, '7XL', 0, 0, NULL, NULL), (208, 21, '8XL', 0, 0, NULL, NULL),
(209, 22, 'S', 0, 0, NULL, NULL), (210, 22, 'M', 0, 0, NULL, NULL), (211, 22, 'L', 0, 0, NULL, NULL), (212, 22, 'XL', 0, 0, NULL, NULL), (213, 22, '2XL', 0, 0, NULL, NULL), (214, 22, '3XL', 0, 0, NULL, NULL), (215, 22, '4XL', 0, 0, NULL, NULL), (216, 22, '5XL', 0, 0, NULL, NULL), (217, 22, '6XL', 0, 0, NULL, NULL), (218, 22, '7XL', 0, 0, NULL, NULL), (219, 22, '8XL', 0, 0, NULL, NULL),
(220, 23, 'S', 0, 0, NULL, NULL), (221, 23, 'M', 0, 0, NULL, NULL), (222, 23, 'L', 0, 0, NULL, NULL), (223, 23, 'XL', 0, 0, NULL, NULL), (224, 23, '2XL', 0, 0, NULL, NULL), (225, 23, '3XL', 0, 0, NULL, NULL), (226, 23, '4XL', 0, 0, NULL, NULL), (227, 23, '5XL', 0, 0, NULL, NULL), (228, 23, '6XL', 0, 0, NULL, NULL), (229, 23, '7XL', 0, 0, NULL, NULL), (230, 23, '8XL', 0, 0, NULL, NULL),
(231, 24, 'S', 0, 0, NULL, NULL), (232, 24, 'M', 0, 0, NULL, NULL), (233, 24, 'L', 0, 0, NULL, NULL), (234, 24, 'XL', 0, 0, NULL, NULL), (235, 24, '2XL', 0, 0, NULL, NULL), (236, 24, '3XL', 0, 0, NULL, NULL), (237, 24, '4XL', 0, 0, NULL, NULL), (238, 24, '5XL', 0, 0, NULL, NULL), (239, 24, '6XL', 0, 0, NULL, NULL), (240, 24, '7XL', 0, 0, NULL, NULL), (241, 24, '8XL', 0, 0, NULL, NULL), (242, 24, '9XL', 0, 0, NULL, NULL), (243, 24, '10XL', 0, 0, NULL, NULL),
(244, 25, 'S', 0, 0, NULL, NULL), (245, 25, 'M', 0, 0, NULL, NULL), (246, 25, 'L', 0, 0, NULL, NULL), (247, 25, 'XL', 0, 0, NULL, NULL), (248, 25, '2XL', 0, 0, NULL, NULL), (249, 25, '3XL', 0, 0, NULL, NULL), (250, 25, '4XL', 0, 0, NULL, NULL), (251, 25, '5XL', 0, 0, NULL, NULL), (252, 25, '6XL', 0, 0, NULL, NULL), (253, 25, '7XL', 0, 0, NULL, NULL), (254, 25, '8XL', 0, 0, NULL, NULL), (255, 25, '9XL', 0, 0, NULL, NULL), (256, 25, '10XL', 0, 0, NULL, NULL),
(257, 26, 'S', 0, 0, NULL, NULL), (258, 26, 'M', 0, 0, NULL, NULL), (259, 26, 'L', 0, 0, NULL, NULL), (260, 26, 'XL', 0, 0, NULL, NULL), (261, 26, '2XL', 0, 0, NULL, NULL), (262, 26, '3XL', 0, 0, NULL, NULL), (263, 26, '4XL', 0, 0, NULL, NULL), (264, 26, '5XL', 0, 0, NULL, NULL), (265, 26, '6XL', 0, 0, NULL, NULL), (266, 26, '7XL', 0, 0, NULL, NULL), (267, 26, '8XL', 0, 0, NULL, NULL), (268, 26, '9XL', 0, 0, NULL, NULL), (269, 26, '10XL', 0, 0, NULL, NULL),
(270, 27, 'S', 0, 0, NULL, NULL), (271, 27, 'M', 0, 0, NULL, NULL), (272, 27, 'L', 0, 0, NULL, NULL), (273, 27, 'XL', 0, 0, NULL, NULL), (274, 27, '2XL', 0, 0, NULL, NULL), (275, 27, '3XL', 0, 0, NULL, NULL), (276, 27, '4XL', 0, 0, NULL, NULL), (277, 27, '5XL', 0, 0, NULL, NULL), (278, 27, '6XL', 0, 0, NULL, NULL), (279, 27, '7XL', 0, 0, NULL, NULL), (280, 27, '8XL', 0, 0, NULL, NULL), (281, 27, '9XL', 0, 0, NULL, NULL), (282, 27, '10XL', 0, 0, NULL, NULL),
(283, 28, 'S', 0, 0, NULL, NULL), (284, 28, 'M', 0, 0, NULL, NULL), (285, 28, 'L', 0, 0, NULL, NULL), (286, 28, 'XL', 0, 0, NULL, NULL), (287, 28, '2XL', 0, 0, NULL, NULL), (288, 28, '3XL', 0, 0, NULL, NULL), (289, 28, '4XL', 0, 0, NULL, NULL), (290, 28, '5XL', 0, 0, NULL, NULL), (291, 28, '6XL', 0, 0, NULL, NULL), (292, 28, '7XL', 0, 0, NULL, NULL), (293, 28, '8XL', 0, 0, NULL, NULL), (294, 28, '9XL', 0, 0, NULL, NULL), (295, 28, '10XL', 0, 0, NULL, NULL),
(296, 29, 'S', 0, 0, NULL, NULL), (297, 29, 'M', 0, 0, NULL, NULL), (298, 29, 'L', 0, 0, NULL, NULL), (299, 29, 'XL', 0, 0, NULL, NULL), (300, 29, '2XL', 0, 0, NULL, NULL), (301, 29, '3XL', 0, 0, NULL, NULL), (302, 29, '4XL', 0, 0, NULL, NULL), (303, 29, '5XL', 0, 0, NULL, NULL), (304, 29,