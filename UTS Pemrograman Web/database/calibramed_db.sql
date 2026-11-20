-- =====================================================================
-- CALIBRAMED SYSTEM DATABASE SCHEMA & INITIAL DATA
-- Platform Manajemen Kalibrasi & Pemeliharaan Alat Kesehatan Faskes
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `calibramed_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `calibramed_db`;

-- Drop tables if exists
DROP TABLE IF EXISTS `maintenance_logs`;
DROP TABLE IF EXISTS `devices`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `hospitals`;

-- ---------------------------------------------------------------------
-- 1. Table: hospitals (10 Mitra Rumah Sakit & Faskes)
-- ---------------------------------------------------------------------
CREATE TABLE `hospitals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(25) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `type_class` VARCHAR(60) NOT NULL,
  `accreditation` VARCHAR(60) NOT NULL,
  `city` VARCHAR(80) NOT NULL,
  `pic_name` VARCHAR(120) NOT NULL,
  `pic_phone` VARCHAR(40) NOT NULL,
  `contract_tier` VARCHAR(40) NOT NULL,
  `contract_period` VARCHAR(80) NOT NULL,
  `target_devices` INT NOT NULL DEFAULT 0,
  `completed_devices` INT NOT NULL DEFAULT 0,
  `maintenance_devices` INT NOT NULL DEFAULT 0,
  `quarantine_devices` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. Table: users (Autentikasi Multi-Peran: Owner, Teknisi, Klien)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `username` VARCHAR(80) NOT NULL UNIQUE,
  `first_name` VARCHAR(80) NOT NULL,
  `last_name` VARCHAR(80) NOT NULL,
  `full_title` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'teknisi', 'klien') NOT NULL DEFAULT 'klien',
  `hospital_id` INT NULL,
  `phone` VARCHAR(40) NULL,
  `avatar` VARCHAR(120) DEFAULT 'user.jpg',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_user_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. Table: devices (Inventaris Alat Medis Lengkap dengan Gambar & Metrologi)
-- ---------------------------------------------------------------------
CREATE TABLE `devices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `device_code` VARCHAR(35) NOT NULL UNIQUE,
  `hospital_id` INT NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `brand_model` VARCHAR(120) NOT NULL,
  `serial_number` VARCHAR(80) NOT NULL,
  `room_location` VARCHAR(100) NOT NULL,
  `image_file` VARCHAR(120) NOT NULL DEFAULT 'default_device.svg',
  `condition_state` ENUM('Layak Pakai', 'Layak Bersyarat', 'Tidak Layak Pakai') NOT NULL DEFAULT 'Layak Pakai',
  `physical_note` TEXT NOT NULL,
  `operation_status` ENUM('Siap Digunakan', 'Dalam Maintenance', 'Karantina / Rusak') NOT NULL DEFAULT 'Siap Digunakan',
  `maintenance_type` ENUM('Preventif', 'Korektif', 'Kolektif', 'Kalibrasi Legal') NOT NULL DEFAULT 'Preventif',
  `maintenance_cycle` ENUM('Harian', 'Mingguan', 'Bulanan', 'Tahunan') NOT NULL DEFAULT 'Bulanan',
  `last_maintenance` DATE NOT NULL,
  `next_maintenance` DATE NOT NULL,
  `last_calibration` DATE NOT NULL,
  `next_calibration` DATE NOT NULL,
  `certificate_number` VARCHAR(80) NOT NULL,
  `certificate_status` ENUM('Valid', 'Expired', 'Dalam Pengujian') NOT NULL DEFAULT 'Valid',
  `error_deviation` VARCHAR(80) NOT NULL,
  `leakage_current` VARCHAR(80) NOT NULL,
  `pic_engineer` VARCHAR(120) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_device_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. Table: maintenance_logs (Catatan Inspeksi & Tindakan Teknisi)
-- ---------------------------------------------------------------------
CREATE TABLE `maintenance_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `device_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `log_date` DATETIME NOT NULL,
  `action_type` VARCHAR(80) NOT NULL,
  `findings` TEXT NOT NULL,
  `action_taken` TEXT NOT NULL,
  `result_condition` VARCHAR(50) NOT NULL,
  `result_status` VARCHAR(50) NOT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA: 10 MITRA RUMAH SAKIT
-- =====================================================================
INSERT INTO `hospitals` (`id`, `code`, `name`, `type_class`, `accreditation`, `city`, `pic_name`, `pic_phone`, `contract_tier`, `contract_period`, `target_devices`, `completed_devices`, `maintenance_devices`, `quarantine_devices`) VALUES
(1, 'RS-SUTOMO', 'RSUD Dr. Soetomo', 'Tipe A Pendidikan', 'Paripurna KARS', 'Surabaya', 'Dr. dr. Joni Wahyuhadi, Sp.BS', '0812-3344-5511', 'Platinum Tier', 'Jan 2026 - Des 2027', 420, 360, 42, 18),
(2, 'RS-SILOAM', 'RS Siloam Surabaya', 'Tipe B Swasta', 'JCI Accredited', 'Surabaya', 'dr. Maria Indah, MMRS', '0811-9988-7722', 'Gold Tier', 'Mar 2026 - Mar 2027', 280, 245, 25, 10),
(3, 'RS-PREMIER', 'RS Premier Surabaya', 'Tipe B Swasta', 'HASTA / JCI', 'Surabaya', 'dr. Hartono Susilo', '0813-4455-6633', 'Gold Tier', 'Mei 2026 - Mei 2027', 210, 180, 20, 10),
(4, 'RS-UNAIR', 'RS Universitas Airlangga', 'Tipe B Pendidikan', 'Paripurna KARS', 'Surabaya', 'Prof. Dr. dr. Nasronudin, Sp.PD', '0812-5566-7744', 'Platinum Tier', 'Jan 2026 - Jan 2028', 190, 165, 15, 10),
(5, 'RS-MITRA', 'RS Mitra Keluarga Kenjeran', 'Tipe B Swasta', 'Paripurna KARS', 'Surabaya', 'dr. Christian Sulistyo', '0812-8877-6655', 'Silver Tier', 'Jun 2026 - Jun 2027', 148, 135, 8, 5),
(6, 'RS-SARDJITO', 'RSUP Dr. Sardjito', 'Tipe A Rujukan Nasional', 'JCI & Paripurna', 'Yogyakarta', 'dr. Eniarti, M.Sc., Sp.KJ', '0811-2233-4466', 'Platinum Tier', 'Feb 2026 - Feb 2028', 350, 298, 38, 14),
(7, 'RS-SAIFUL', 'RSUD Dr. Saiful Anwar', 'Tipe A Pendidikan', 'Paripurna KARS', 'Malang', 'Dr. dr. Moch. Bachtiar Budianto', '0813-7788-9900', 'Gold Tier', 'Apr 2026 - Apr 2027', 260, 225, 24, 11),
(8, 'RS-HUSADA', 'RS Husada Utama', 'Tipe B Swasta', 'Paripurna KARS', 'Surabaya', 'dr. Dwi Wijayanto, MM', '0812-6655-4433', 'Silver Tier', 'Jul 2026 - Jul 2027', 125, 110, 11, 4),
(9, 'RS-JEMURSARI', 'RS Islam Surabaya Jemursari', 'Tipe B Syariah', 'Paripurna KARS', 'Surabaya', 'dr. Bangun Trapsila Purwaka', '0813-1122-3344', 'Silver Tier', 'Agu 2026 - Agu 2027', 115, 102, 9, 4),
(10, 'KLN-PARAHITA', 'Klinik Diagnostik Parahita', 'Klinik Utama Lab & Imaging', 'ISO 15189 / KARS', 'Surabaya', 'dr. Mulyadi Kartono, Sp.PK', '0811-7766-5544', 'Gold Tier', 'Jan 2026 - Des 2026', 85, 74, 7, 4);

-- =====================================================================
-- SEED DATA: USERS (OWNER, TEKNISI, KLIEN)
-- =====================================================================
INSERT INTO `users` (`id`, `email`, `username`, `first_name`, `last_name`, `full_title`, `password_hash`, `role`, `hospital_id`, `phone`, `avatar`) VALUES
-- 1. Akun OWNER Utama
(1, 'owner@calibramed.com', 'ihsan.owner', 'Ihsan Setyo', 'Nugroho', 'Prof. Dr.Eng. Ir. Ihsan Setyo Nugroho, S.Tr.T., M.Eng., IPU., ASEAN Eng., APEC Eng., CCE.', '$2y$10$SndESj3peZi71RzllLzO2uTUdYbKxpvbJDowKe293igOR/U6FrQIa', 'owner', NULL, '0812-3456-7890', 'user.jpg'),
-- 2. Akun TEKNISI Senior PT
(2, 'teknisi@calibramed.com', 'dimas.teknisi', 'Dimas', 'Prasetyo', 'Eng. Dimas Prasetyo, S.Tr.TEM', '$2y$10$QGPbZ4EAqHt1IT.q/Gc/g.9tVZsXbSWvBF5u1n.0kYXl6l.qiz.jm', 'teknisi', NULL, '0813-9876-5432', 'user.jpg'),
-- 3. Akun KLIEN RS (RSUD Dr. Soetomo)
(3, 'soetomo@hospital.com', 'soetomo.klien', 'Hendra', 'Kusuma', 'dr. Hendra Kusuma, Sp.An (Kepala Instalasi Pemeliharaan Sarana RS)', '$2y$10$6pdTN/Pz9kUudITAK.0Xu.rm4CTSymyvkelH6gHf6mZQYJ.O4luWu', 'klien', 1, '0812-3344-5511', 'user.jpg');

-- =====================================================================
-- SEED DATA: ALAT MEDIS LENGKAP DENGAN METROLOGI & STATUS
-- =====================================================================
INSERT INTO `devices` (`id`, `device_code`, `hospital_id`, `name`, `brand_model`, `serial_number`, `room_location`, `image_file`, `condition_state`, `physical_note`, `operation_status`, `maintenance_type`, `maintenance_cycle`, `last_maintenance`, `next_maintenance`, `last_calibration`, `next_calibration`, `certificate_number`, `certificate_status`, `error_deviation`, `leakage_current`, `pic_engineer`) VALUES
(1, 'CLM-DFB-014', 1, 'Defibrillator & Pacer', 'Mindray BeneHeart D3', 'SN-DFB-99201', 'Ruang IGD Resusitasi', 'defibrillator.svg', 'Layak Pakai', 'Kondisi fisik utuh, paddle bersih, pelepasan energi stabil', 'Siap Digunakan', 'Preventif', 'Bulanan', '2026-10-15', '2026-11-15', '2026-04-12', '2027-04-12', 'CERT/CAL/2026/04-0992', 'Valid', '+0.8% (Toleransi < ±5%)', '42 µA (Max: 100 µA)', 'Eng. Dimas P.'),
(2, 'CLM-VNT-008', 1, 'Mechanical Ventilator', 'Hamilton Medical C1', 'SN-VNT-44102', 'Ruang ICU Sentral Lt.3', 'ventilator.svg', 'Layak Pakai', 'Sensor O2 telah diganti, sensor flow terkalibrasi normal', 'Siap Digunakan', 'Kolektif', 'Mingguan', '2026-11-14', '2026-11-21', '2026-01-08', '2027-01-08', 'CERT/CAL/2026/01-0412', 'Valid', '-1.2% (Toleransi < ±3%)', '55 µA (Max: 100 µA)', 'Prof. Ihsan S.N.'),
(3, 'CLM-INF-102', 2, 'Infusion Pump Precision', 'Terumo TE-112 Smart', 'SN-INF-77812', 'Rawat Inap Melati 4', 'infusion_pump.svg', 'Layak Bersyarat', 'Daya baterai cadangan bertahan < 1 jam, perlu penggantian cell', 'Dalam Maintenance', 'Korektif', 'Bulanan', '2026-11-02', '2026-12-02', '2026-05-18', '2027-05-18', 'CERT/CAL/2026/05-1802', 'Valid', '+2.1% (Toleransi < ±5%)', '35 µA (Max: 100 µA)', 'Eng. Dimas P.'),
(4, 'CLM-ECG-031', 2, 'ECG 12-Lead Diagnostic', 'Nihon Kohden Cardiofax M', 'SN-ECG-33910', 'Poli Jantung & Vaskular', 'ecg_machine.svg', 'Layak Pakai', 'Lead kabel baru, kertas thermal lancar, sinyal bebas noise 50Hz', 'Siap Digunakan', 'Preventif', 'Harian', '2026-11-19', '2026-11-20', '2026-03-04', '2027-03-04', 'CERT/CAL/2026/03-0391', 'Valid', '+0.3% (Toleransi < ±2%)', '28 µA (Max: 50 µA)', 'Prof. Ihsan S.N.'),
(5, 'CLM-XRY-002', 3, 'Mobile Digital X-Ray', 'Siemens Polymobil Plus', 'SN-XRY-11209', 'Ruang Radiologi & Imaging', 'mobile_xray.svg', 'Tidak Layak Pakai', 'Collimator lamp putus dan kV output menurun drastis di luar toleransi', 'Karantina / Rusak', 'Korektif', 'Tahunan', '2026-11-10', '2026-11-24', '2025-11-14', '2026-11-14', 'UNFIT/QUARANTINE-2026/09', 'Expired', '-14.5% (Toleransi < ±5%)', '180 µA (Max: 100 µA)', 'Eng. Dimas P.'),
(6, 'CLM-MON-088', 1, 'Bedside Patient Monitor', 'Philips IntelliVue MX450', 'SN-MON-66504', 'Kamar Bedah Sentral (OK-04)', 'patient_monitor.svg', 'Layak Pakai', 'Modul SpO2, NIBP, dan ECG terkalibrasi presisi dengan simulator', 'Siap Digunakan', 'Kolektif', 'Mingguan', '2026-11-16', '2026-11-23', '2026-02-20', '2027-02-20', 'CERT/CAL/2026/02-0665', 'Valid', '+0.5% (Toleransi < ±2%)', '31 µA (Max: 100 µA)', 'Prof. Ihsan S.N.'),
(7, 'CLM-DNT-004', 4, 'Dental Unit Executive Chair', 'Gnatus S400 Lux Medical', 'SN-DNT-55219', 'Poli Gigi & Mulut Lt. 2', 'dental_unit.svg', 'Layak Bersyarat', 'Solenoid air scaler sedikit menetes, perlu seal kit baru', 'Dalam Maintenance', 'Preventif', 'Mingguan', '2026-11-17', '2026-11-24', '2026-06-10', '2027-06-10', 'CERT/CAL/2026/06-0552', 'Valid', '+1.0% (Toleransi < ±5%)', '49 µA (Max: 100 µA)', 'Eng. Dimas P.'),
(8, 'CLM-INC-011', 5, 'Infant Incubator Intensive', 'Draeger Isolette 8000 Plus', 'SN-INC-99321', 'Ruang Perinatologi / NICU', 'incubator.svg', 'Layak Pakai', 'Sensor suhu, kelembaban, dan alarm sirkulasi udara normal sempurna', 'Siap Digunakan', 'Kalibrasi Legal', 'Bulanan', '2026-11-05', '2026-12-05', '2026-08-22', '2027-08-22', 'CERT/CAL/2026/08-0993', 'Valid', '+0.2°C (Toleransi < ±0.5°C)', '38 µA (Max: 100 µA)', 'Prof. Ihsan S.N.'),
(9, 'CLM-SYR-045', 3, 'Syringe Pump Micro-Flow', 'B. Braun Perfusor Space', 'SN-SYR-12340', 'Ruang ICU Lantai 2', 'syringe_pump.svg', 'Layak Pakai', 'Akurasi dorong motor stepper teruji akurat pada laju 1.0 - 50.0 ml/jam', 'Siap Digunakan', 'Preventif', 'Harian', '2026-11-19', '2026-11-20', '2026-09-11', '2027-09-11', 'CERT/CAL/2026/09-0123', 'Valid', '-0.9% (Toleransi < ±3%)', '25 µA (Max: 100 µA)', 'Eng. Dimas P.'),
(10, 'CLM-ATC-003', 10, 'Autoclave Sterilizer Digital', 'Tuttnauer 3870EL Chamber', 'SN-ATC-88123', 'Laboratorium CSSD & Steril', 'autoclave.svg', 'Tidak Layak Pakai', 'Gasket pintu aus dan deviasi suhu chamber mencapai +6.8°C berbahaya', 'Karantina / Rusak', 'Korektif', 'Bulanan', '2026-11-01', '2026-11-25', '2025-11-05', '2026-11-05', 'UNFIT/QUARANTINE-2026/11', 'Expired', '+6.8°C (Toleransi < ±1.0°C)', '120 µA (Max: 100 µA)', 'Eng. Dimas P.'),
(11, 'CLM-USG-019', 6, 'USG 4D Color Doppler', 'GE Healthcare Voluson E8', 'SN-USG-77112', 'Poli Kandungan & Obgyn', 'usg_doppler.svg', 'Layak Pakai', 'Probe konveks dan transvaginal teruji impedansi dan resolusi akustik baik', 'Siap Digunakan', 'Preventif', 'Bulanan', '2026-11-12', '2026-12-12', '2026-07-15', '2027-07-15', 'CERT/CAL/2026/07-0771', 'Valid', '+0.4% (Toleransi < ±2%)', '41 µA (Max: 100 µA)', 'Prof. Ihsan S.N.'),
(12, 'CLM-SUC-022', 7, 'Heavy Duty Suction Pump', 'Atmos C341 Surgery Suction', 'SN-SUC-33211', 'Kamar Operasi Bedah Tulang', 'suction_pump.svg', 'Layak Pakai', 'Vakum maksimal mencapai -0.85 bar, filter bakteri baru terpasang', 'Siap Digunakan', 'Kolektif', 'Mingguan', '2026-11-18', '2026-11-25', '2026-03-20', '2027-03-20', 'CERT/CAL/2026/03-0332', 'Valid', '-1.5% (Toleransi < ±5%)', '32 µA (Max: 100 µA)', 'Eng. Dimas P.');

-- =====================================================================
-- SEED DATA: LOG AKTIVITAS PEMELIHARAAN
-- =====================================================================
INSERT INTO `maintenance_logs` (`device_id`, `user_id`, `log_date`, `action_type`, `findings`, `action_taken`, `result_condition`, `result_status`, `notes`) VALUES
(1, 1, '2026-10-15 10:30:00', 'Uji Kalibrasi Metrologis', 'Pelepasan energi di 200J menghasilkan output 198.5J', 'Pembersihan paddle kontak, kalibrasi internal zero-offset', 'Layak Pakai', 'Siap Digunakan', 'Alat berfungsi prima, stiker laik pakai hijau diterbitkan'),
(3, 2, '2026-11-02 14:15:00', 'Inspeksi & Tes Baterai', 'Baterai Li-ion drop setelah 45 menit uji kontinu', 'Penggantian baterai cadangan dipesan ke distributor Terumo', 'Layak Bersyarat', 'Dalam Maintenance', 'Hanya boleh digunakan tersambung daya PLN selama baterai belum tiba'),
(5, 2, '2026-11-10 11:00:00', 'Breakdown Maintenance', 'Lampu kolimator putus total, output kV drop 14.5%', 'Karantina alat, penghentian operasional radiologi mobile', 'Tidak Layak Pakai', 'Karantina / Rusak', 'Pemberian label merah Karantina Tidak Laik Pakai');
