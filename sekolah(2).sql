-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2026 at 08:11 AM
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
-- Database: `sekolah`
--

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id` int(11) NOT NULL,
  `user` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mapel`
--

CREATE TABLE `mapel` (
  `id` int(11) NOT NULL,
  `nama_mapel` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mapel`
--

INSERT INTO `mapel` (`id`, `nama_mapel`) VALUES
(1, 'Pendidikan Agama dan Budi Pekerti'),
(2, 'Pendidikan Pancasila'),
(3, 'Bahasa Indonesia'),
(4, 'Matematika'),
(5, 'Ilmu Pengetahuan Alam'),
(6, 'Ilmu Pengetahuan Sosial'),
(7, 'Bahasa Inggris'),
(8, 'Pendidikan Jasmani Olahraga dan Kesehatan'),
(9, 'Informatika'),
(10, 'Seni, Budaya, dan Prakarya'),
(11, 'Muatan Lokal'),
(12, 'Bahasa Jawa'),
(13, 'BTQ/BTA');

-- --------------------------------------------------------

--
-- Table structure for table `nilai`
--

CREATE TABLE `nilai` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `mapel_id` int(11) NOT NULL,
  `nilai_ujian` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `s1` decimal(5,2) NOT NULL DEFAULT 0.00,
  `s2` decimal(5,2) NOT NULL DEFAULT 0.00,
  `s3` decimal(5,2) NOT NULL DEFAULT 0.00,
  `s4` decimal(5,2) NOT NULL DEFAULT 0.00,
  `s5` decimal(5,2) NOT NULL DEFAULT 0.00,
  `s6` decimal(5,2) NOT NULL DEFAULT 0.00,
  `semester` enum('1','2') NOT NULL DEFAULT '1',
  `tahun_ajaran` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai`
--

INSERT INTO `nilai` (`id`, `siswa_id`, `mapel_id`, `nilai_ujian`, `created_at`, `s1`, `s2`, `s3`, `s4`, `s5`, `s6`, `semester`, `tahun_ajaran`) VALUES
(1, 1, 3, 10.00, '2026-05-21 05:15:59', 10.00, 10.00, 10.00, 10.00, 10.00, 10.00, '1', '');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nis` varchar(30) NOT NULL,
  `nisn` varchar(30) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nama_orang_tua` varchar(100) DEFAULT NULL,
  `nomor_ijazah` varchar(100) DEFAULT NULL,
  `no_surat` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nama`, `nis`, `nisn`, `kelas`, `tempat_lahir`, `tanggal_lahir`, `nama_orang_tua`, `nomor_ijazah`, `no_surat`, `created_at`, `updated_at`) VALUES
(1, 'AFFAENAL ARIEF', '6804', '0104872259', '9 A', 'DEMA', '2010-12-29', 'Kasnadi', '', '1', '2026-05-21 04:48:15', NULL),
(2, 'AGIL SEFTIANI', '6836', '0115918541', '9 B', 'Demak', '2011-05-23', 'Nani', '', '2', '2026-05-21 04:48:15', NULL),
(3, 'AGUSTINA FERNITA ANGEL', '6868', '0104217433', '9 D', 'DEMAK', '2010-08-07', 'AKHMAD FAIZIN', '', '3', '2026-05-21 04:48:15', NULL),
(4, 'AHLAQUS SHIFA NURAINIYAH', '6898', '0127013484', '9 E', 'DEMAK', '2012-01-11', 'AHMAT FAUZI', '', '4', '2026-05-21 04:48:15', NULL),
(5, 'AHMAD FAHMI RAMADHANI', '6928', '0114340909', '9 B', 'DEMAK', '2011-08-28', 'IRSYAD', '', '5', '2026-05-21 04:48:15', NULL),
(6, 'AHMAD FIRMAN HIDAYAT', '6837', '0108823165', '9 A', 'Demak', '2010-12-27', 'Joko Utomo', '', '6', '2026-05-21 04:48:15', NULL),
(7, 'AHMAD GALIH ADIN SATRIO', '6805', '0116829264', '9 F', 'DEMAK', '2011-12-25', 'Nurudin', '', '7', '2026-05-21 04:48:15', NULL),
(8, 'AHMAD IVAN ROMADHANI', '6838', '0114749304', '9 C', 'Demak', '2011-08-21', 'DWI SUSILO PRANOTO', '', '8', '2026-05-21 04:48:15', NULL),
(9, 'AHMAD NUR ERVAN', '6869', '0118953159', '9 C', 'DEMAK', '2011-09-30', 'TURMUJI', '', '9', '2026-05-21 04:48:15', NULL),
(10, 'AHMAD SABILAN NAJA', '6994', '3113657263', '9 D', 'DEMAK', '2011-09-08', 'SUKARYADI', '', '10', '2026-05-21 04:48:15', NULL),
(11, 'AHMAD SYAIFUL MUJAB', '6870', '0102469153', '9 C', 'Demak', '2010-11-14', 'Nur Kholis', '', '11', '2026-05-21 04:48:15', NULL),
(12, 'AHMAD SYIFA AUN FAKHRI', '7240', '0117955802', '9 E', 'DEMAK', '2011-05-13', 'AHMAD SYAFI\'I', '', '12', '2026-05-21 04:48:15', NULL),
(13, 'AHMAD ULIL AIDI', '6929', '0122737689', '9 A', 'Demak', '2012-01-22', 'Tarmuji', '', '13', '2026-05-21 04:48:15', NULL),
(14, 'AHMAD YUSUF DZIKRULLOH', '6992', '0103381241', '9 D', 'Demak', '2010-08-12', 'AHMAD SOLIKUL HADI', '', '14', '2026-05-21 04:48:15', NULL),
(15, 'AKHMAD JEFRI', '6958', '0113300650', '9 C', 'DEMAK', '2011-02-06', 'HADI SUYITNO', '', '15', '2026-05-21 04:48:15', NULL),
(16, 'ALDA ICA NOVIA', '6806', '0118417850', '9 B', 'Demak', '2011-06-04', 'Ahmad Munadi', '', '16', '2026-05-21 04:48:15', NULL),
(17, 'ALFIATUN NADHIFAH', '6839', '0113373872', '9 B', 'Demak', '2011-04-08', 'SUWARNO', '', '17', '2026-05-21 04:48:15', NULL),
(18, 'ALIKA AHLAN NISA', '6871', '0114200977', '9 F', 'Demak', '2011-07-29', 'Kumairi', '', '18', '2026-05-21 04:48:15', NULL),
(19, 'ALWI DHARMAWAN ARROSYID', '6899', '0107158286', '9 B', 'DEMAK', '2010-01-18', 'Hesti Era Tri Yulianti', '', '19', '2026-05-21 04:48:15', NULL),
(20, 'ANGGITA OKTAVIA PUTRI', '6959', '0101556697', '9 E', 'Demak', '2010-10-06', 'HARTOYO', '', '20', '2026-05-21 04:48:15', NULL),
(21, 'APRILLIA INDAH SETYA NINGRUM', '6960', '3113255314', '9 A', 'DEMAK', '2011-04-24', 'MA\'RUB', '', '21', '2026-05-21 04:48:15', NULL),
(22, 'Ariva Ramandhani', '6807', '0111948720', '9 A', 'Demak', '2011-08-10', 'Pandi', '', '22', '2026-05-21 04:48:15', NULL),
(23, 'ARIYAN MAULANA REZKY', '6840', '0117233619', '9 A', 'Demak', '2011-03-20', 'Jumarin', '', '23', '2026-05-21 04:48:15', NULL),
(24, 'Azavira Oktavia Putri', '6872', '0119325183', '9 C', 'Demak', '2011-10-21', 'Turkamun', '', '24', '2026-05-21 04:48:15', NULL),
(25, 'AZKIYA AYU RAMADHANITA', '6900', '0115840576', '9 B', 'DEMAK', '2011-08-06', 'MUHAMAD TOHAMI', '', '25', '2026-05-21 04:48:15', NULL),
(26, 'AZRIL DIMAS SAPUTRA', '6930', '0116639133', '9 D', 'DEMAK', '2011-03-03', 'SUBANDI', '', '26', '2026-05-21 04:48:15', NULL),
(27, 'AZURRA DESFITA SUNI', '6931', '0117155440', '9 D', 'Demak', '2011-12-13', 'Suparto', '', '27', '2026-05-21 04:48:15', NULL),
(28, 'BAGAS ADITIA SAPUTRA', '6808', '0117491445', '9 E', 'Demak', '2011-07-04', 'Agus Triyono', '', '28', '2026-05-21 04:48:15', NULL),
(29, 'BAGUS SUGIYO UTOMO', '6841', '0128381973', '9 B', 'DEMAK', '2012-09-02', 'TEGUH TARYONO', '', '29', '2026-05-21 04:48:15', NULL),
(30, 'BARA AKRA SYANDANA', '6873', '0119917045', '9 A', 'Demak', '2011-07-08', 'Supri Kisnadi', '', '30', '2026-05-21 04:48:15', NULL),
(31, 'BEANDERA ANGGAY AHMADDAN', '6901', '0119454355', '9 B', 'Demak', '2011-08-12', 'Pujo Kurniawan', '', '31', '2026-05-21 04:48:15', NULL),
(32, 'BILQIS DWI ANGGRAINI', '6932', '0114891038', '9 E', 'DEMAK', '2011-06-18', 'SETYO BUDI', '', '32', '2026-05-21 04:48:15', NULL),
(33, 'CHOIRUL ANAM', '6933', '0112518499', '9 E', 'DEMAK', '2011-12-25', 'SUWARDI', '', '33', '2026-05-21 04:48:15', NULL),
(34, 'Cinta Adhofun Nisa Mubarok', '6961', '0126559424', '9 F', 'Demak', '2012-03-08', 'Robani', '', '34', '2026-05-21 04:48:15', NULL),
(35, 'CINTA KUMALA DEWI', '6809', '0126779824', '9 E', 'DEMAK', '2012-01-05', 'BUDI HARNO', '', '35', '2026-05-21 04:48:15', NULL),
(36, 'DEVA MAHBUB AL IHSAN', '6874', '0113385620', '9 B', 'Demak', '2011-12-26', 'M. Sholikhul Hadi', '', '36', '2026-05-21 04:48:15', NULL),
(37, 'DIAH SEPTIANI', '6962', '0114850630', '9 E', 'Demak', '2011-09-20', 'MASROKAN', '', '37', '2026-05-21 04:48:15', NULL),
(38, 'DIANA AYU LESTARI', '6902', '0113415560', '9 B', 'Demak', '2011-12-06', 'Ahmad Khoeron', '', '38', '2026-05-21 04:48:15', NULL),
(39, 'DINA NAJWA NUR SALSABILA', '6903', '0114631495', '9 A', 'Demak', '2011-04-27', 'Suwondo', '', '39', '2026-05-21 04:48:15', NULL),
(40, 'DINDA AYU LESTARI', '6963', '0108311441', '9 A', 'demak', '2010-12-12', 'Abdur Rohman', '', '40', '2026-05-21 04:48:15', NULL),
(41, 'DINDA PUTRI AINUN', '6810', '0103274935', '9 C', 'NABIRE', '2010-10-21', 'PONADI', '', '41', '2026-05-21 04:48:15', NULL),
(42, 'DINI YAMSIYATUL JANNAH', '6843', '0119528392', '9 F', 'Demak', '2011-05-21', 'SUGENG KAMBALI', '', '42', '2026-05-21 04:48:15', NULL),
(43, 'DIYAH AYU PRATAMA', '6875', '0111188035', '9 B', 'Demak', '2011-12-08', 'Kasnawi', '', '43', '2026-05-21 04:48:15', NULL),
(44, 'DIYAH NUR KHASANAH', '6964', '0118612959', '9 B', 'Demak', '2011-07-22', 'Nurhadi', '', '44', '2026-05-21 04:48:15', NULL),
(45, 'Dwi Sekar Taji', '6934', '0111039706', '9 D', 'Demak', '2011-04-06', 'Dulrokim', '', '45', '2026-05-21 04:48:15', NULL),
(46, 'EKA PUTRA ALDIANSYAH', '6965', '0118344205', '9 E', 'DEMAK', '2011-06-28', 'Sucipto', '', '46', '2026-05-21 04:48:15', NULL),
(47, 'Eka Renda Raditiya', '6876', '0113303393', '9 F', 'Demak', '2011-11-10', 'Daripin', '', '47', '2026-05-21 04:48:15', NULL),
(48, 'ENGGAR YOGA PRASTIA', '6844', '0118933539', '9 A', 'Demak', '2011-05-02', 'Nurul Anwar', '', '48', '2026-05-21 04:48:15', NULL),
(49, 'ENOS ALFA NATHANIEL', '6811', '0117573692', '9 F', 'DEMAK', '2011-09-15', 'LUSTIYONO', '', '49', '2026-05-21 04:48:15', NULL),
(50, 'ERIKA PUTRI AYU ALFARIK', '6904', '0116671789', '9 C', 'Demak', '2011-07-07', 'Achmad Jalali', '', '50', '2026-05-21 04:48:15', NULL),
(51, 'ERVINA PUTRI FATMAWATI', '6935', '0119852649', '9 C', 'DEMAK', '2011-01-30', 'SUHARTONO', '', '51', '2026-05-21 04:48:15', NULL),
(52, 'FADILLA GHUSWATUL AKBAR', '6966', '3104083109', '9 E', 'DEMAK', '2010-09-13', 'SUROSO', '', '52', '2026-05-21 04:48:15', NULL),
(53, 'FAHRI BUDI PRASETIYO', '6812', '0107511868', '9 F', 'DEMAK', '2010-10-21', 'Suprapto', '', '53', '2026-05-21 04:48:15', NULL),
(54, 'FAHRIL IRAWAN', '6845', '0118227975', '9 A', 'Demak', '2011-02-02', 'Solikin', '', '54', '2026-05-21 04:48:15', NULL),
(55, 'FARISKA AZZAHRA', '6877', '0112746046', '9 F', 'Demak', '2011-07-20', 'Ali Mufidi', '', '55', '2026-05-21 04:48:15', NULL),
(56, 'FATIMATUZ SILFIAH', '6905', '0119178069', '9 C', 'Demak', '2011-07-24', 'Munari', '', '56', '2026-05-21 04:48:15', NULL),
(57, 'FATIMATUZ ZAHRA', '6936', '0114430417', '9 A', 'Demak', '2011-04-17', 'Fadlan', '', '57', '2026-05-21 04:48:15', NULL),
(58, 'FEBRI HARYANTO', '6906', '0117581567', '9 E', 'Demak', '2011-02-03', 'Suhardi', '', '58', '2026-05-21 04:48:15', NULL),
(59, 'FEBY IKA KUSUMADEWI', '6813', '0115263308', '9 C', 'Demak', '2011-02-01', 'Sukoco', '', '59', '2026-05-21 04:48:15', NULL),
(60, 'FINA ZAHRA MAHARANY', '6846', '0122490746', '9 F', 'Demak', '2012-02-03', 'Teguh Setiwiyono', '', '60', '2026-05-21 04:48:15', NULL),
(61, 'Firmina Linda Sari', '6878', '0117932932', '9 F', 'Demak', '2011-10-24', 'Muh Khoerudin', '', '61', '2026-05-21 04:48:15', NULL),
(62, 'GILANG ARYA MAHENDRA', '6907', '0118521286', '9 D', 'Demak', '2011-10-18', 'Puji Aryanto', '', '62', '2026-05-21 04:48:15', NULL),
(63, 'HUILING AURORA MALIKA', '7250', '0101252656', '9 D', 'MADIUN', '2010-11-27', 'EKO SETYO MULYONO', '', '63', '2026-05-21 04:48:15', NULL),
(64, 'INDIRA REINIA LUTFIN', '6937', '0103075871', '9 D', 'Demak', '2010-12-28', 'Sapar Robiun', '', '64', '2026-05-21 04:48:15', NULL),
(65, 'INDRA MAULANA PUTRA', '6967', '0104362502', '9 C', 'SEMARANG', '2010-06-08', 'WARSITO SUHAR MULYONO', '', '65', '2026-05-21 04:48:15', NULL),
(66, 'INES NAFISA RAMADHANI', '6814', '0112706151', '9 D', 'Demak', '2011-08-13', 'Sismanto', '', '66', '2026-05-21 04:48:15', NULL),
(67, 'INTAN AYU LESTARI', '6847', '0119799536', '9 F', 'DEMAK', '2011-05-30', 'PURNOMO', '', '67', '2026-05-21 04:48:15', NULL),
(68, 'INTAN MULIA SARI', '6879', '0116678978', '9 D', 'DEMAK', '2011-02-26', 'SUNADJI', '', '68', '2026-05-21 04:48:15', NULL),
(69, 'IRNANDA TRIA PRAMESWARI', '6908', '0119632690', '9 B', 'Demak', '2011-03-29', 'Supat', '', '69', '2026-05-21 04:48:15', NULL),
(70, 'Isna Nur Muizah', '6938', '0124142223', '9 A', 'Demak', '2012-01-20', 'Muhamad Jamil', '', '70', '2026-05-21 04:48:15', NULL),
(71, 'JIHAN FARIDATUL HUSNA', '6968', '0116443650', '9 E', 'DEMAK', '2011-09-22', 'MUHAMMAD SODIQ', '', '71', '2026-05-21 04:48:15', NULL),
(72, 'JINGGA AIRINJANI', '6815', '0113668795', '9 A', 'Demak', '2011-06-14', 'Runtung Priyanto', '', '72', '2026-05-21 04:48:15', NULL),
(73, 'JOYO SATRIYO SANTOSO', '6848', '0092856063', '9 D', 'Demak', '2009-10-22', 'MUHAMAD JALIL', '', '73', '2026-05-21 04:48:15', NULL),
(74, 'KEVIN DWI WICAKSONO', '6880', '0116793291', '9 E', 'Demak', '2011-10-19', 'Sonhaji', '', '74', '2026-05-21 04:48:15', NULL),
(75, 'Kevin Harlino', '6969', '0097921564', '9 D', 'Karangrejo', '2009-05-27', 'Paiman', '', '75', '2026-05-21 04:48:15', NULL),
(76, 'Kharisma Dewi', '6909', '0112801830', '9 A', 'Demak', '2011-02-09', 'Zoel Fitri Siregar', '', '76', '2026-05-21 04:48:15', NULL),
(77, 'KHOIRUN NISA', '6970', '3111540798', '9 D', 'DEMAK', '2011-03-03', 'SURTONO', '', '77', '2026-05-21 04:48:15', NULL),
(78, 'KHOLID ERRY RISNANTA', '6939', '3116127355', '9 B', 'DEMAK ', '2011-03-14', 'ROHMAN', '', '78', '2026-05-21 04:48:15', NULL),
(79, 'KHUSNUL MUSTOFA', '6816', '0101332274', '9 B', 'DEMAK', '2010-06-27', 'TURMUJI', '', '79', '2026-05-21 04:48:15', NULL),
(80, 'LINTANG MAULANA IBROHIM', '6849', '0105358361', '9 B', 'DEMAK', '2010-04-30', 'HARTONO', '', '80', '2026-05-21 04:48:15', NULL),
(81, 'Lutfia Agustina Barokatussiam', '6881', '0127227576', '9 E', 'Demak', '2012-08-09', 'Satibi', '', '81', '2026-05-21 04:48:15', NULL),
(82, 'LUTHFI DHIYA\'UL HAQ', '6971', '0105466532', '9 D', 'DEMAK', '2010-10-19', 'HARNO PRAWIRO', '', '82', '2026-05-21 04:48:15', NULL),
(83, 'MALINA AYU OKTAVIANI', '6910', '3108495845', '9 C', 'DEMAK', '2010-10-22', '', '', '83', '2026-05-21 04:48:15', NULL),
(84, 'MARSHELL', '6817', '0116689313', '9 F', 'Demak', '2011-11-30', 'Bambang Edi', '', '84', '2026-05-21 04:48:15', NULL),
(85, 'MARTHA TINA TARISA', '6818', '0119127892', '9 F', 'Jombang', '2011-03-27', 'Achmad Subani', '', '85', '2026-05-21 04:48:15', NULL),
(86, 'MAULANA AGEL PAMUNGKAS', '6940', '0118844905', '9 A', 'Demak', '2011-02-08', 'Jarwoyo', '', '86', '2026-05-21 04:48:15', NULL),
(87, 'MAULANA COKKY ANDRIANO', '6850', '0128200913', '9 B', 'Demak', '2012-02-03', 'Dwi Yuli hartono', '', '87', '2026-05-21 04:48:15', NULL),
(88, 'MILA TRIMULYANI', '7482', '0117636468', '9 A', 'Demak', '2011-10-20', 'Sutono', '', '88', '2026-05-21 04:48:15', NULL),
(89, 'MUH SEPTA WAHYU SAPUTERA', '6882', '0111015496', '9 F', 'Demak', '2011-09-05', 'Muh Solikul Huda', '', '89', '2026-05-21 04:48:15', NULL),
(90, 'Muhamad Bagus Wicaksono', '6911', '0126332758', '9 F', 'Demak', '2012-01-08', 'Marzuki', '', '90', '2026-05-21 04:48:15', NULL),
(91, 'Muhamad Didik Arianto', '6941', '0104911371', '9 F', 'Demak', '2010-12-04', 'DARWANTO', '', '91', '2026-05-21 04:48:15', NULL),
(92, 'MUHAMAD KHASANUDIN', '6819', '0119362290', '9 B', 'Demak', '2011-07-25', 'Muthadhor', '', '92', '2026-05-21 04:48:15', NULL),
(93, 'MUHAMAD NADIB', '6851', '0101127437', '9 C', 'Demak', '2010-11-01', 'Muhammad Sholikhin', '', '93', '2026-05-21 04:48:15', NULL),
(94, 'Muhamad Nova Syaputra', '6883', '0117811485', '9 D', 'Demak', '2011-11-17', 'Muhamad Anwar', '', '94', '2026-05-21 04:48:15', NULL),
(95, 'MUHAMAD RADITYA SULISTIO', '6912', '0114268413', '9 E', 'Demak', '2012-01-07', 'Nur Hadi', '', '95', '2026-05-21 04:48:15', NULL),
(96, 'MUHAMAD REZA TAMA NOVIANTO', '6942', '0111667537', '9 B', 'Demak', '2011-11-05', 'Bunadi', '', '96', '2026-05-21 04:48:15', NULL),
(97, 'Muhammad Abdul Azis', '6943', '0114893856', '9 D', 'Demak', '2011-06-24', 'Ahmad Sholeh', '', '97', '2026-05-21 04:48:15', NULL),
(98, 'Muhammad Abdul Syukur', '6972', '0115413295', '9 F', 'Demak', '2011-05-05', 'SUKARTO', '', '98', '2026-05-21 04:48:15', NULL),
(99, 'MUHAMMAD ADRIAN AKBAR', '6820', '0113486703', '9 C', 'Demak', '2011-10-31', 'Saeful Anwar', '', '99', '2026-05-21 04:48:15', NULL),
(100, 'MUHAMMAD ARDIANSYAH', '6884', '0112472564', '9 B', 'DEMAK', '2011-06-14', 'SLAMET EDI', '', '100', '2026-05-21 04:48:15', NULL),
(101, 'MUHAMMAD ARIEL PRASETYO', '6913', '0117299903', '9 D', 'DEMAK', '2011-10-23', 'EKO SUPRAYITNO', '', '101', '2026-05-21 04:48:15', NULL),
(102, 'MUHAMMAD DAFID KURNIAWAN', '6973', '0111716100', '9 A', 'DEMAK', '2011-04-08', 'MUHAMMAD MUSTAIN', '', '102', '2026-05-21 04:48:15', NULL),
(103, 'MUHAMMAD DWI ANDIKA', '6944', '0112484785', '9 C', 'Demak', '2011-06-13', 'Muhamad Suparji', '', '103', '2026-05-21 04:48:15', NULL),
(104, 'Muhammad Fais Hidayatulloh', '6853', '0112654346', '9 F', 'Demak', '2011-06-09', 'Abunaim', '', '104', '2026-05-21 04:48:15', NULL),
(105, 'MUHAMMAD FAIZ HAIKAL', '6974', '3119017363', '9 B', 'DEMAK', '2011-07-22', 'SUPARDI', '', '105', '2026-05-21 04:48:15', NULL),
(106, 'MUHAMMAD FIRMANSYAH', '6914', '0112286954', '9 E', 'Demak', '2011-10-18', 'Wasdum', '', '106', '2026-05-21 04:48:15', NULL),
(107, 'MUHAMMAD GILANG SRI WIDODO', '6945', '0114921588', '9 F', 'DEMAK', '2011-09-12', 'AGUS WIDODO', '', '107', '2026-05-21 04:48:15', NULL),
(108, 'Muhammad Iqbal Mustajib', '6975', '0118934847', '9 E', 'Demak', '2011-05-20', 'Sabar', '', '108', '2026-05-21 04:48:15', NULL),
(109, 'MUHAMMAD ISHAL AMRI', '6822', '3117274951', '9 C', 'DEMAK ', '2011-05-24', 'MUSAFAK', '', '109', '2026-05-21 04:48:15', NULL),
(110, 'MUHAMMAD KHANIEF LUTHFIY', '6990', '0113662817', '9 A', 'DEMAK', '2011-04-20', 'SUMONO', '', '110', '2026-05-21 04:48:15', NULL),
(111, 'MUHAMMAD KURNIA RAMADHANI', '6854', '3115231333', '9 B', 'DEMAK', '2011-08-01', 'IMAM TRI CAHYONO', '', '111', '2026-05-21 04:48:15', NULL),
(112, 'MUHAMMAD NABIL MUJTABA', '6946', '0112512590', '9 D', 'Demak', '2011-06-29', 'Sumarlan', '', '112', '2026-05-21 04:48:15', NULL),
(113, 'MUHAMMAD NUR ROHMAN', '6915', '0092782022', '9 C', 'DEMAK', '2009-11-11', 'SUHARTONO', '', '113', '2026-05-21 04:48:15', NULL),
(114, 'MUHAMMAD RAJIB ADI NUGROHO', '6916', '3105582432', '9 C', 'DEMAK', '2010-06-26', 'SUWARNO', '', '114', '2026-05-21 04:48:15', NULL),
(115, 'MUHAMMAD RENDY APRILIANO', '6823', '0111043718', '9 C', 'Demak', '2011-04-02', 'Margono', '', '115', '2026-05-21 04:48:15', NULL),
(116, 'MUHAMMAD RIZKI HANIF', '6855', '0111150062', '9 D', 'Demak', '2011-07-09', 'Muhammad Purnawi', '', '116', '2026-05-21 04:48:15', NULL),
(117, 'MUHAMMAD RIZKY KHOERUL NU\'MA', '6887', '0113527633', '9 C', 'demak', '2011-10-02', 'Khoerul Anwar', '', '117', '2026-05-21 04:48:15', NULL),
(118, 'MUHAMMAD RIZKY RHAMADAN AL IMRON', '6976', '3111503107', '9 A', 'DEMAK', '2011-08-02', 'ALVIAN IMRONI', '', '118', '2026-05-21 04:48:15', NULL),
(119, 'MUHAMMAD RIZQI MAULANA', '6856', '0111112869', '9 F', 'demak', '2011-06-18', 'Sugiyanto', '', '119', '2026-05-21 04:48:15', NULL),
(120, 'MUHAMMAD SONI ADITIYA', '6917', '0118645131', '9 C', 'Demak', '2011-04-27', 'Ngatini', '', '120', '2026-05-21 04:48:15', NULL),
(121, 'MUHAMMAD VALEN FEBRIYAN', '6824', '0115340295', '9 A', 'DEMAK', '2011-02-14', 'KUMAIDI', '', '121', '2026-05-21 04:48:15', NULL),
(122, 'MUKHTAR FAIZ DEARA DWIKA PUTRA', '6857', '0118333684', '9 F', 'DEMAK', '2011-07-19', 'MUHTAROM', '', '122', '2026-05-21 04:48:15', NULL),
(123, 'MUTIARA DWI RAHMAWATI', '6888', '0119197644', '9 B', 'Demak', '2011-04-27', 'Wijaja', '', '123', '2026-05-21 04:48:15', NULL),
(124, 'NADIA SOFIANI', '6825', '0117856039', '9 F', 'Demak', '2011-08-08', 'Joko Kriswanto', '', '124', '2026-05-21 04:48:15', NULL),
(125, 'NAFIS RAZAN SYAHPUTRA', '6947', '0113887099', '9 A', 'Demak', '2011-06-10', 'Muhamad Sulkan', '', '125', '2026-05-21 04:48:15', NULL),
(126, 'NAIDA ALIKHA FARZANA', '6977', '0118770303', '9 B', 'Demak', '2011-05-25', 'Senawi', '', '126', '2026-05-21 04:48:15', NULL),
(127, 'NAJWA AZ ZAHRA', '6858', '0116003932', '9 A', 'DEMAK', '2011-02-26', 'ABDUR ROHMAN', '', '127', '2026-05-21 04:48:15', NULL),
(128, 'NANDINI YULIA NUR KHASANAH', '6826', '0115677656', '9 E', 'Demak', '2011-07-10', 'Joko Susanto', '', '128', '2026-05-21 04:48:15', NULL),
(129, 'NAUVAL GYAN ANANDA', '6889', '0108272137', '9 C', 'DEMAK', '2010-12-29', 'SUGIHARTO', '', '129', '2026-05-21 04:48:15', NULL),
(130, 'NAYA NISCAHYA AZAHRA', '6918', '3116050954', '9 C', 'DEMAK', '2011-05-07', 'MUHAMMAD AFIF SYARIFUDIN', '', '130', '2026-05-21 04:48:15', NULL),
(131, 'NGESTI EKA DEWI', '6948', '0105726624', '9 D', 'DEMAK', '2010-11-27', 'MISRI', '', '131', '2026-05-21 04:48:15', NULL),
(132, 'Nirmala Dewi', '6978', '0112905313', '9 C', 'Demak', '2011-02-09', 'Zoel Fitri Siregar', '', '132', '2026-05-21 04:48:15', NULL),
(133, 'NITYA SYIFA MAHESWARI', '6859', '0119615344', '9 C', 'Demak', '2011-11-08', 'ISWADI IDRIS', '', '133', '2026-05-21 04:48:15', NULL),
(134, 'NUGRAHENI DESY PAMUNGKAS', '6827', '0119483924', '9 F', 'Demak', '2011-12-11', 'Hirmanto ALM', '', '134', '2026-05-21 04:48:15', NULL),
(135, 'NUKE FADILLA ZAHRA', '6890', '0116941712', '9 E', 'DEMAK', '2011-10-30', 'MUHAMMAD KHABIB SWANTO', '', '135', '2026-05-21 04:48:15', NULL),
(136, 'NUR BUDI SETIAWAN', '6919', '0116646667', '9 D', 'DEMAK', '2011-04-02', 'GALIH ARI BAGUS KRISTIAWAN', '', '136', '2026-05-21 04:48:15', NULL),
(137, 'NURROHMAN', '6979', '0101343533', '9 D', 'CIAMIS', '2010-11-03', 'SUPARDI', '', '137', '2026-05-21 04:48:15', NULL),
(138, 'PASHA APRILIANTO', '6828', '0118074960', '9 F', 'Demak', '2011-04-23', 'Triantono', '', '138', '2026-05-21 04:48:15', NULL),
(139, 'PUTRI EVITASARI', '6860', '0106284748', '9 A', 'SEMARANG', '2010-10-04', 'AGUS WIJIANTO', '', '139', '2026-05-21 04:48:15', NULL),
(140, 'PUTRI FATIMAH', '6891', '0118950465', '9 A', 'Demak', '2011-11-24', 'Abdul Wakid', '', '140', '2026-05-21 04:48:15', NULL),
(141, 'PUTRI SUNDARI', '6920', '0118799305', '9 B', 'demak', '2011-04-04', 'Kapindi', '', '141', '2026-05-21 04:48:15', NULL),
(142, 'PUTRI VANEZA AULIA', '6949', '0119727295', '9 D', 'DEMAK', '2011-10-27', 'SURYANTO', '', '142', '2026-05-21 04:48:15', NULL),
(143, 'Rafi Ramadhani', '6921', '0119461700', '9 E', 'Demak', '2011-08-15', 'Zaenal Arifin', '', '143', '2026-05-21 04:48:15', NULL),
(144, 'RAHMA NATASYA PUTRI', '6922', '0115337052', '9 A', 'Demak', '2011-01-23', 'Muhamad Fauzi', '', '144', '2026-05-21 04:48:15', NULL),
(145, 'RANI DWI ANGGRAENI', '6861', '0117529736', '9 E', 'Demak', '2011-05-12', 'Muhammad Dhikron', '', '145', '2026-05-21 04:48:15', NULL),
(146, 'Rasya Bagus Kurniawan', '7481', '3118033711', '9 E', 'DEMAK', '2011-11-03', 'Bagus Purwadi', '', '146', '2026-05-21 04:48:15', NULL),
(147, 'REVANA SYAHRANI NURUL IZZAH', '6980', '0118085088', '9 D', 'DEMAK', '2011-01-11', 'MUH MUSLIKHUN', '', '147', '2026-05-21 04:48:15', NULL),
(148, 'Rhena Febri Sariana', '6950', '0125467869', '9 C', 'Demak', '2012-02-16', 'Moh Pamuji', '', '148', '2026-05-21 04:48:15', NULL),
(149, 'RIKI ADITYA SAPUTRA', '6981', '0112121388', '9 A', 'Demak', '2011-04-20', 'Suparjono', '', '149', '2026-05-21 04:48:15', NULL),
(150, 'RIRIN ARIANTI', '6829', '0119699058', '9 B', 'DEMAK', '2011-08-01', 'SARMADI', '', '150', '2026-05-21 04:48:15', NULL),
(151, 'Risma Anggraini', '6862', '0111933194', '9 E', 'Demak', '2011-12-01', 'Supandi', '', '151', '2026-05-21 04:48:15', NULL),
(152, 'Rizal Pratama Nugroho', '6892', '0111890877', '9 A', 'Demak', '2011-10-28', 'Triono', '', '152', '2026-05-21 04:48:15', NULL),
(153, 'RIZKI MAULANA', '6951', '0103345355', '9 A', 'Demak', '2010-12-19', 'Enda Suhenda', '', '153', '2026-05-21 04:48:15', NULL),
(154, 'RIZKI TRI KURNIAWAN', '6982', '0102864337', '9 E', 'DEMAK', '2010-11-15', 'Nanang Guntur Sutopo', '', '154', '2026-05-21 04:48:15', NULL),
(155, 'Rizky Nanda', '6952', '0107669325', '9 B', 'Demak', '2010-04-18', 'Solikin', '', '155', '2026-05-21 04:48:15', NULL),
(156, 'SA\'ADATUN NISWAH', '6830', '0102118940', '9 B', 'demak', '2010-11-18', 'Agus Hakim', '', '156', '2026-05-21 04:48:15', NULL),
(157, 'SAROYA AKHLANNISA', '6863', '0123941112', '9 B', 'Demak', '2012-02-17', 'Muhammad Arwani', '', '157', '2026-05-21 04:48:15', NULL),
(158, 'SASKIA DYAH NUR SANTI', '6893', '0117541992', '9 C', 'DEMAK', '2011-09-04', 'NUR HASAN', '', '158', '2026-05-21 04:48:15', NULL),
(159, 'SASKYA KARYA RAMADHANI', '6923', '3118153104', '9 D', 'DEMAK ', '2011-08-16', 'SUKARYO', '', '159', '2026-05-21 04:48:15', NULL),
(160, 'SHIDQI YARDAN SETYAWAN', '6953', '0113361855', '9 B', 'DEMAK', '2011-02-01', 'PONCO HERU SETYAWAN', '', '160', '2026-05-21 04:48:15', NULL),
(161, 'SHOFI HIDAYATULLOH', '6983', '0121952939', '9 D', 'Demak', '2012-06-04', 'Purnomo', '', '161', '2026-05-21 04:48:15', NULL),
(162, 'SHOLEH CANDRA SEPTYONO', '6831', '0112136129', '9 E', 'Demak', '2011-11-15', 'Sugiyono', '', '162', '2026-05-21 04:48:15', NULL),
(163, 'SILVIANA FITRIA RAMADANI', '6864', '0129192989', '9 F', 'DEMAK', '2011-07-31', 'AHMAD SOLHAN', '', '163', '2026-05-21 04:48:15', NULL),
(164, 'SILVIANA WIDIA WATI', '6894', '0111341529', '9 B', 'Demak', '2011-04-30', 'Slamet Nugraha', '', '164', '2026-05-21 04:48:15', NULL),
(165, 'SINDY DIAN SAFIRA', '6984', '3117291873', '9 F', 'DEMAK', '2011-05-19', 'RIYANTO', '', '165', '2026-05-21 04:48:15', NULL),
(166, 'Sintia Anil Hawa', '6954', '0111592585', '9 E', 'Demak', '2011-07-17', 'Abdul Kholik', '', '166', '2026-05-21 04:48:15', NULL),
(167, 'SITI ACHSANUL KHOTIMAH', '6924', '3110002408', '9 D', 'DEMAK', '2011-07-28', 'SOBRI', '', '167', '2026-05-21 04:48:15', NULL),
(168, 'Siti Alfiyatul Karimah', '6832', '0102634263', '9 C', 'Demak', '2010-11-18', 'Muhammad Muzazin', '', '168', '2026-05-21 04:48:15', NULL),
(169, 'SITI MUNAWAROH', '6833', '0115061421', '9 F', 'Demak', '2011-04-07', 'Partono', '', '169', '2026-05-21 04:48:15', NULL),
(170, 'SUKMA NENDEN SETIANI', '6865', '0118026370', '9 A', 'Demak', '2011-02-22', 'Sukirno', '', '170', '2026-05-21 04:48:15', NULL),
(171, 'Sutan Akbar', '6895', '0106326395', '9 E', 'Demak', '2010-11-16', 'AHMAD MUKIBIN', '', '171', '2026-05-21 04:48:15', NULL),
(172, 'SYOFI\'ATHUL MAFTHUHAH', '6985', '0119198120', '9 C', 'DEMAK', '2011-03-30', 'JATI WIBOWO', '', '172', '2026-05-21 04:48:15', NULL),
(173, 'TRI MULYATI', '6955', '0114525491', '9 C', 'demak', '2011-10-21', 'Nuryadin', '', '173', '2026-05-21 04:48:15', NULL),
(174, 'TRI MULYO PUTRA SAKTI', '6986', '0114703824', '9 E', 'Demak', '2011-07-28', 'Suwarno', '', '174', '2026-05-21 04:48:15', NULL),
(175, 'TRI YOGA SETIA', '6834', '0113386011', '9 E', 'Demak', '2011-05-24', 'Sumardi', '', '175', '2026-05-21 04:48:15', NULL),
(176, 'Umi Zahwa Kamila', '6991', '0117818482', '9 F', 'Demak', '2011-11-15', 'Eko Nur Cahyo', '', '176', '2026-05-21 04:48:15', NULL),
(177, 'VALENTINA FEBIA ELIANA', '1389', '0116304829', '9 D', 'GROBOGAN', '2011-02-14', 'SOALI', '', '177', '2026-05-21 04:48:15', NULL),
(178, 'VICA ANANDA BERLIANTI', '6866', '0117636728', '9 E', 'Demak', '2011-09-02', 'Anwar Misbah', '', '178', '2026-05-21 04:48:15', NULL),
(179, 'VIZA ZAHRA', '6896', '0103515273', '9 B', 'DEMAK', '2010-12-15', 'JOKO LAKSONO', '', '179', '2026-05-21 04:48:15', NULL),
(180, 'WAHYU DIAH NOVITASARI', '6925', '0128418401', '9 C', 'DEMAK', '2012-05-24', 'ARIS MUJIONO', '', '180', '2026-05-21 04:48:15', NULL),
(181, 'WAHYU NUR SEJATI', '6926', '3117151728', '9 C', 'DEMAK', '2011-09-20', 'KHOLIKUR ROHMAN', '', '181', '2026-05-21 04:48:15', NULL),
(182, 'WAHYU PUJI NINGSIH', '6987', '0112147925', '9 F', 'Demak', '2011-05-01', 'Sholikudin', '', '182', '2026-05-21 04:48:15', NULL),
(183, 'WINDA DUWI UTAMI', '6927', '0113860127', '9 A', 'Demak', '2011-02-01', 'Sumarno', '', '183', '2026-05-21 04:48:15', NULL),
(184, 'WULANDARI', '6867', '0112088139', '9 A', 'Demak', '2011-02-07', 'Ahmad Sudarwi', '', '184', '2026-05-21 04:48:15', NULL),
(185, 'YHAKHARIA KHAIRUNISAN', '6897', '0123044026', '9 D', 'Demak', '2012-02-25', 'Jamari', '', '185', '2026-05-21 04:48:15', NULL),
(186, 'YOLANDA SHERLY MARGARETA', '6835', '0114013662', '9 F', 'DEMAK', '2011-03-20', 'NGATMAN', '', '186', '2026-05-21 04:48:15', NULL),
(187, 'YULIANA RAHMA SAIDAH', '6956', '0116674620', '9 E', 'DEMAK', '2011-07-09', 'SAEFUL ANAM', '', '187', '2026-05-21 04:48:15', NULL),
(188, 'Zaskia Lutfiana', '6988', '3114362281', '9 D', 'Demak', '2011-04-13', 'MUHAMMAD ALI SOBIRIN', '', '188', '2026-05-21 04:48:15', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user` (`user`);

--
-- Indexes for table `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nilai`
--
ALTER TABLE `nilai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_nilai` (`siswa_id`,`mapel_id`),
  ADD KEY `idx_nilai_siswa` (`siswa_id`),
  ADD KEY `idx_nilai_mapel` (`mapel_id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_siswa_nama` (`nama`),
  ADD KEY `idx_siswa_kelas` (`kelas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `nilai_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_ibfk_2` FOREIGN KEY (`mapel_id`) REFERENCES `mapel` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
