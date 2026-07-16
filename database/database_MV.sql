-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 16 Tem 2026, 07:37:29
-- Sunucu sürümü: 8.4.7
-- PHP Sürümü: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `markvision`
--

DELIMITER $$
--
-- Yordamlar
--
DROP PROCEDURE IF EXISTS `InsertTestStudents`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `InsertTestStudents` ()   BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE random_dept_id INT;
    DECLARE current_student_no INT DEFAULT 230000001;
    
    SELECT COALESCE(MAX(student_no), 230000000) + 1 INTO current_student_no FROM `students` WHERE student_no < 240000000;

    WHILE i <= 100 DO
        SELECT id INTO random_dept_id FROM `departments` ORDER BY RAND() LIMIT 1;
        
        -- Yeni kolon isimlerine göre INSERT işlemi düzeltildi
        INSERT INTO `students` (`student_no`, `student_name`, `student_surname`, `department_id`)
        VALUES (
            current_student_no,
            CONCAT('Öğrenci_', i),
            CONCAT('Soyadı_', ELT(FLOOR(RAND() * 5) + 1, 'Yılmaz', 'Kaya', 'Demir', 'Şahin', 'Çelik')),
            random_dept_id
        );
        
        SET current_student_no = current_student_no + 1;
        SET i = i + 1;
    END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_course_code` (`course_code`),
  KEY `fk_course_department` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `courses`
--

INSERT INTO `courses` (`id`, `department_id`, `course_code`, `course_name`, `created_at`, `updated_at`) VALUES
(1, 5, 'BIL-102', 'Bilgisayar Programcılığına Giriş', '2026-07-14 10:39:06', NULL),
(2, 5, 'BIL-201', 'Web Tasarımının Temelleri', '2026-07-14 10:39:06', NULL),
(3, 14, 'BM-204', 'Nesne Yönelimli Programlama', '2026-07-14 10:39:06', NULL),
(4, 14, 'BM-301', 'Veritabanı Yönetim Sistemleri', '2026-07-14 10:39:06', NULL),
(5, 14, 'BM-302', 'Veri Yapıları ve Algoritmalar', '2026-07-14 10:39:06', NULL),
(6, 14, 'BM-401', 'Yazılım Mühendisliği', '2026-07-14 10:39:06', NULL),
(7, 15, 'ISG-101', 'İş Sağlığı ve Güvenliğine Giriş', '2026-07-14 10:39:06', NULL),
(8, 15, 'IST-202', 'İstatistik ve Olasılık', '2026-07-14 10:39:06', NULL),
(9, 1, 'AFET-101', 'Temel Afet Bilinci ve İlk Yardım', '2026-07-14 10:41:10', NULL),
(10, 1, 'AFET-205', 'Arama Kurtarma Teknikleri', '2026-07-14 10:41:10', NULL),
(11, 3, 'BS-102', 'Bankacılık İlkeleri ve Para Teorisi', '2026-07-14 10:41:10', NULL),
(12, 3, 'BS-204', 'Risk Yönetimi ve Sigortacılık Mevzuatı', '2026-07-14 10:41:10', NULL),
(13, 13, 'HIT-101', 'Halkla İlişkilerde İletişim Kuramları', '2026-07-14 10:41:10', NULL),
(14, 13, 'HIT-304', 'Kriz Yönetimi ve Reklam Kampanyaları', '2026-07-14 10:41:10', NULL),
(15, 258, 'GMS-101', 'Temel Mutfak Teknikleri ve Hijyen', '2026-07-14 10:41:10', NULL),
(16, 258, 'GMS-202', 'Dünya Mutfakları ve Pişirme Yöntemleri', '2026-07-14 10:41:10', NULL),
(17, 257, 'REH-104', 'Anadolu Arkeolojisi ve Mitoloji', '2026-07-14 10:41:10', NULL),
(18, 257, 'REH-301', 'Tur Planlaması ve Yönetimi', '2026-07-14 10:41:10', NULL),
(19, 190, 'TAB-201', 'Tahıllar ve Yemeklik Tane Baklagiller', '2026-07-14 10:41:10', NULL),
(20, 190, 'TAB-305', 'Endüstri Bitkileri Yetiştiriciliği', '2026-07-14 10:41:10', NULL),
(21, 41, 'SAY-102', 'Sağlık Kurumlarında Yönetim ve Organizasyon', '2026-07-14 10:41:10', NULL),
(22, 41, 'SAY-308', 'Sağlık Ekonomisi ve Finansmanı', '2026-07-14 10:41:10', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE IF NOT EXISTS `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faculty_id` int NOT NULL,
  `department_name` varchar(150) NOT NULL,
  `degree_type` enum('Önlisans','Lisans') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_dept_per_faculty` (`faculty_id`,`department_name`)
) ENGINE=InnoDB AUTO_INCREMENT=265 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `departments`
--

INSERT INTO `departments` (`id`, `faculty_id`, `department_name`, `degree_type`) VALUES
(1, 1, 'Acil Durum ve Afet Yönetimi', 'Önlisans'),
(3, 1, 'Bankacılık ve Sigortacılık', 'Önlisans'),
(5, 1, 'Bilgisayar Programcılığı', 'Önlisans'),
(6, 1, 'Büro Yönetimi ve Yönetici Asistanlığı', 'Önlisans'),
(13, 1, 'Halkla İlişkiler ve Tanıtım', 'Önlisans'),
(14, 1, 'İslami İlimler (İlahiyat)', 'Önlisans'),
(15, 1, 'İş Sağlığı ve Güvenliği', 'Önlisans'),
(31, 1, 'Yeni Medya ve Gazetecilik', 'Önlisans'),
(32, 1, 'Yerel Yönetimler', 'Önlisans'),
(36, 1, 'Halkla İlişkiler ve Tanıtım Lisans', 'Lisans'),
(41, 1, 'Sağlık Yönetimi Lisans', 'Lisans'),
(183, 23, 'Bahçe Bitkileri', 'Lisans'),
(184, 23, 'Bitki Koruma', 'Lisans'),
(189, 23, 'Tarımsal Yapılar ve Sulama', 'Lisans'),
(190, 23, 'Tarla Bitkileri', 'Lisans'),
(191, 23, 'Toprak Bilimi ve Bitki Besleme', 'Lisans'),
(195, 5, 'Fizik', 'Lisans'),
(196, 5, 'Kimya', 'Lisans'),
(207, 6, 'Seramik', 'Lisans'),
(211, 9, 'İktisat', 'Lisans'),
(221, 11, 'Yeni Medya Ve İletişim Bölümü', 'Lisans'),
(222, 12, 'Bilgisayar ve Öğretim Teknolojileri Öğretmenliği', 'Lisans'),
(229, 12, 'İlköğretim Matematik Öğretmenliği', 'Lisans'),
(236, 14, 'Çevre Mühendisliği', 'Lisans'),
(242, 14, 'Metalurji ve Malzeme Mühendisliği', 'Lisans'),
(246, 15, 'İşletme Bölümü', 'Lisans'),
(248, 15, 'Sosyal Hizmet Bölümü', 'Lisans'),
(251, 17, 'Rekreasyon Bölümü', 'Lisans'),
(257, 20, 'Turizm Rehberliği', 'Lisans'),
(258, 20, 'Gastronomi ve Mutfak Sanatları', 'Lisans'),
(259, 20, 'Rekreasyon Yönetimi', 'Lisans'),
(260, 21, 'Acil Yardım ve Afet Yönetimi', 'Lisans');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE IF NOT EXISTS `exams` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `course_id` int DEFAULT NULL,
  `course_name` varchar(100) NOT NULL,
  `exam_type` varchar(20) NOT NULL,
  `total_questions` int NOT NULL,
  `answer_key` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_exam_per_dept` (`department_id`,`course_name`,`exam_type`),
  KEY `fk_exam_teacher` (`teacher_id`),
  KEY `fk_exam_course` (`course_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `exams`
--

INSERT INTO `exams` (`id`, `department_id`, `teacher_id`, `course_id`, `course_name`, `exam_type`, `total_questions`, `answer_key`) VALUES
(1, 14, 46, NULL, 'Veritabanı Yönetim Sistemleri', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"E\", \"6\": \"A\", \"7\": \"B\", \"8\": \"C\", \"9\": \"D\", \"10\": \"E\"}'),
(2, 15, 14, NULL, 'Finansal Muhasebe', 'Final', 10, '{\"1\": \"B\", \"2\": \"B\", \"3\": \"C\", \"4\": \"A\", \"5\": \"E\", \"6\": \"A\", \"7\": \"D\", \"8\": \"C\", \"9\": \"D\", \"10\": \"A\"}'),
(9, 14, 46, NULL, 'Nesne Yönelimli Programlama', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"E\", \"6\": \"A\", \"7\": \"B\", \"8\": \"C\", \"9\": \"D\", \"10\": \"E\"}'),
(10, 14, 46, NULL, 'Veri Yapıları ve Algoritmalar', 'Final', 10, '{\"1\": \"B\", \"2\": \"C\", \"3\": \"D\", \"4\": \"A\", \"5\": \"E\", \"6\": \"B\", \"7\": \"C\", \"8\": \"D\", \"9\": \"A\", \"10\": \"E\"}'),
(11, 14, 46, NULL, 'Web Programlama', 'Bütünleme', 10, '{\"1\": \"C\", \"2\": \"D\", \"3\": \"A\", \"4\": \"B\", \"5\": \"E\", \"6\": \"C\", \"7\": \"D\", \"8\": \"A\", \"9\": \"B\", \"10\": \"E\"}'),
(12, 15, 14, NULL, 'İstatistik ve Olasılık', 'Vize', 10, '{\"1\": \"D\", \"2\": \"A\", \"3\": \"B\", \"4\": \"C\", \"5\": \"E\", \"6\": \"D\", \"7\": \"A\", \"8\": \"B\", \"9\": \"C\", \"10\": \"E\"}'),
(13, 15, 14, NULL, 'Yapay Zekaya Giriş', 'Final', 10, '{\"1\": \"A\", \"2\": \"E\", \"3\": \"D\", \"4\": \"C\", \"5\": \"B\", \"6\": \"A\", \"7\": \"E\", \"8\": \"D\", \"9\": \"C\", \"10\": \"B\"}'),
(14, 14, 46, NULL, 'Yazılım Mühendisliği', 'Vize', 10, '{\"1\": \"B\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"A\", \"6\": \"B\", \"7\": \"B\", \"8\": \"C\", \"9\": \"D\", \"10\": \"A\"}'),
(15, 14, 46, NULL, 'Veritabanı Yönetim Sistemleri II', 'Final', 10, '{\"1\": \"E\", \"2\": \"D\", \"3\": \"C\", \"4\": \"B\", \"5\": \"A\", \"6\": \"E\", \"7\": \"D\", \"8\": \"C\", \"9\": \"B\", \"10\": \"A\"}'),
(16, 15, 14, NULL, 'Mikroekonomi', 'Vize', 10, '{\"1\": \"A\", \"2\": \"C\", \"3\": \"E\", \"4\": \"B\", \"5\": \"D\", \"6\": \"A\", \"7\": \"C\", \"8\": \"E\", \"9\": \"B\", \"10\": \"D\"}'),
(17, 15, 14, NULL, 'Makroekonomi', 'Final', 10, '{\"1\": \"C\", \"2\": \"E\", \"3\": \"A\", \"4\": \"D\", \"5\": \"B\", \"6\": \"C\", \"7\": \"E\", \"8\": \"A\", \"9\": \"D\", \"10\": \"B\"}'),
(18, 14, 46, NULL, 'Mobil Uygulama Geliştirme', 'Vize', 10, '{\"1\": \"B\", \"2\": \"A\", \"3\": \"D\", \"4\": \"E\", \"5\": \"C\", \"6\": \"B\", \"7\": \"A\", \"8\": \"D\", \"9\": \"E\", \"10\": \"C\"}'),
(19, 14, 46, NULL, 'Bulut Bilişim', 'Final', 10, '{\"1\": \"E\", \"2\": \"A\", \"3\": \"B\", \"4\": \"C\", \"5\": \"D\", \"6\": \"E\", \"7\": \"A\", \"8\": \"B\", \"9\": \"C\", \"10\": \"D\"}'),
(20, 15, 14, NULL, 'Pazarlama İlkeleri', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"A\", \"4\": \"B\", \"5\": \"C\", \"6\": \"D\", \"7\": \"E\", \"8\": \"C\", \"9\": \"D\", \"10\": \"E\"}'),
(21, 15, 14, NULL, 'Örgütsel Davranış', 'Final', 10, '{\"1\": \"D\", \"2\": \"D\", \"3\": \"C\", \"4\": \"C\", \"5\": \"B\", \"6\": \"B\", \"7\": \"A\", \"8\": \"A\", \"9\": \"E\", \"10\": \"E\"}'),
(22, 14, 46, NULL, 'Siber Güvenliğe Giriş', 'Vize', 10, '{\"1\": \"C\", \"2\": \"C\", \"3\": \"B\", \"4\": \"A\", \"5\": \"E\", \"6\": \"D\", \"7\": \"C\", \"8\": \"B\", \"9\": \"A\", \"10\": \"E\"}'),
(23, 14, 46, NULL, 'Ağ Yönetimi ve Yönetimi', 'Final', 10, '{\"1\": \"A\", \"2\": \"D\", \"3\": \"B\", \"4\": \"E\", \"5\": \"C\", \"6\": \"A\", \"7\": \"D\", \"8\": \"B\", \"9\": \"E\", \"10\": \"C\"}'),
(24, 15, 14, NULL, 'İşletme Finansı', 'Vize', 10, '{\"1\": \"B\", \"2\": \"E\", \"3\": \"C\", \"4\": \"A\", \"5\": \"D\", \"6\": \"B\", \"7\": \"E\", \"8\": \"C\", \"9\": \"A\", \"10\": \"D\"}'),
(25, 15, 14, NULL, 'Maliyet Muhasebesi', 'Final', 10, '{\"1\": \"E\", \"2\": \"C\", \"3\": \"A\", \"4\": \"D\", \"5\": \"B\", \"6\": \"E\", \"7\": \"C\", \"8\": \"A\", \"9\": \"D\", \"10\": \"B\"}'),
(26, 14, 46, NULL, 'İşletim Sistemleri', 'Vize', 10, '{\"1\": \"D\", \"2\": \"B\", \"3\": \"E\", \"4\": \"A\", \"5\": \"C\", \"6\": \"D\", \"7\": \"B\", \"8\": \"E\", \"9\": \"A\", \"10\": \"C\"}'),
(27, 14, 46, NULL, 'Gömülü Sistemler', 'Final', 10, '{\"1\": \"C\", \"2\": \"A\", \"3\": \"D\", \"4\": \"B\", \"5\": \"E\", \"6\": \"C\", \"7\": \"A\", \"8\": \"D\", \"9\": \"B\", \"10\": \"E\"}'),
(28, 15, 14, NULL, 'Uluslararası Ticaret', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"E\", \"6\": \"E\", \"7\": \"D\", \"8\": \"C\", \"9\": \"B\", \"10\": \"A\"}'),
(29, 15, 14, NULL, 'Stratejik Yönetim', 'Final', 10, '{\"1\": \"B\", \"2\": \"C\", \"3\": \"D\", \"4\": \"E\", \"5\": \"A\", \"6\": \"A\", \"7\": \"E\", \"8\": \"D\", \"9\": \"C\", \"10\": \"B\"}'),
(30, 14, 46, NULL, 'Yazılım Testi ve Kalite', 'Vize', 10, '{\"1\": \"E\", \"2\": \"D\", \"3\": \"C\", \"4\": \"B\", \"5\": \"A\", \"6\": \"B\", \"7\": \"C\", \"8\": \"D\", \"9\": \"E\", \"10\": \"A\"}'),
(31, 14, 46, NULL, 'Büyük Veri Analitiği', 'Final', 10, '{\"1\": \"C\", \"2\": \"E\", \"3\": \"B\", \"4\": \"A\", \"5\": \"D\", \"6\": \"C\", \"7\": \"E\", \"8\": \"B\", \"9\": \"A\", \"10\": \"D\"}'),
(32, 15, 14, NULL, 'İnsan Kaynakları Yönetimi', 'Vize', 10, '{\"1\": \"D\", \"2\": \"A\", \"3\": \"E\", \"4\": \"C\", \"5\": \"B\", \"6\": \"D\", \"7\": \"A\", \"8\": \"E\", \"9\": \"C\", \"10\": \"B\"}'),
(33, 15, 14, NULL, 'E-Ticaret Sistemleri', 'Final', 10, '{\"1\": \"A\", \"2\": \"D\", \"3\": \"B\", \"4\": \"E\", \"5\": \"C\", \"6\": \"A\", \"7\": \"D\", \"8\": \"B\", \"9\": \"E\", \"10\": \"C\"}'),
(34, 14, 46, NULL, 'Paralel Programlama', 'Vize', 10, '{\"1\": \"B\", \"2\": \"C\", \"3\": \"A\", \"4\": \"D\", \"5\": \"E\", \"6\": \"B\", \"7\": \"C\", \"8\": \"A\", \"9\": \"D\", \"10\": \"E\"}'),
(35, 14, 46, NULL, 'Görüntü İşleme', 'Final', 10, '{\"1\": \"E\", \"2\": \"B\", \"3\": \"D\", \"4\": \"A\", \"5\": \"C\", \"6\": \"E\", \"7\": \"B\", \"8\": \"D\", \"9\": \"A\", \"10\": \"C\"}'),
(36, 15, 14, NULL, 'Yönetim Bilişim Sistemleri', 'Vize', 10, '{\"1\": \"C\", \"2\": \"A\", \"3\": \"E\", \"4\": \"D\", \"5\": \"B\", \"6\": \"C\", \"7\": \"A\", \"8\": \"E\", \"9\": \"D\", \"10\": \"B\"}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_no` int DEFAULT NULL,
  `exam_id` int NOT NULL,
  `student_answers` json NOT NULL,
  `correct_count` int DEFAULT '0',
  `wrong_count` int DEFAULT '0',
  `blank_count` int DEFAULT '0',
  `score` decimal(5,2) DEFAULT '0.00',
  `status` enum('success','pending_review','failed') DEFAULT 'success',
  `optical_image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_exam` (`student_no`,`exam_id`),
  KEY `fk_result_exam` (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `exam_results`
--

INSERT INTO `exam_results` (`id`, `student_no`, `exam_id`, `student_answers`, `correct_count`, `wrong_count`, `blank_count`, `score`, `status`, `optical_image_url`, `created_at`, `updated_at`) VALUES
(2, 230710025, 1, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"E\"}', 3, 1, 0, 75.00, 'pending_review', 'scanned_forms/optik_ornek.jpeg', '2026-07-13 12:01:42', '2026-07-13 09:19:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `faculties`
--

DROP TABLE IF EXISTS `faculties`;
CREATE TABLE IF NOT EXISTS `faculties` (
  `id` int NOT NULL AUTO_INCREMENT,
  `faculty_name` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_faculty` (`faculty_name`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `faculties`
--

INSERT INTO `faculties` (`id`, `faculty_name`) VALUES
(1, 'Açık ve Uzaktan Öğretim Fakültesi'),
(2, 'Diş Hekimliği Fakültesi'),
(3, 'Eczacılık Fakültesi'),
(4, 'Edebiyat Fakültesi'),
(5, 'Fen Fakültesi'),
(6, 'Güzel Sanatlar Fakültesi'),
(7, 'Hemşirelik Fakültesi'),
(8, 'Hukuk Fakültesi'),
(9, 'İktisadi ve İdari Bilimler Fakültesi'),
(10, 'İlahiyat Fakültesi'),
(11, 'İletişim Fakültesi'),
(12, 'Kazım Karabekir Eğitim Fakültesi'),
(13, 'Mimarlık ve Tasarım Fakültesi'),
(14, 'Mühendislik Fakültesi'),
(15, 'Oltu Beşeri ve Sosyal Bilimler Fakültesi'),
(16, 'Sağlık Bilimleri Fakültesi'),
(17, 'Spor Bilimleri Fakültesi'),
(18, 'Su Ürünleri Fakültesi'),
(19, 'Tıp Fakültesi'),
(20, 'Turizm Fakültesi'),
(21, 'Uygulamalı Bilimler Fakültesi'),
(22, 'Veteriner Fakültesi'),
(23, 'Ziraat Fakültesi');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `student_no` int NOT NULL,
  `student_name` varchar(50) NOT NULL,
  `student_surname` varchar(50) NOT NULL,
  `department_id` int NOT NULL,
  PRIMARY KEY (`student_no`),
  KEY `fk_student_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `students`
--

INSERT INTO `students` (`student_no`, `student_name`, `student_surname`, `department_id`) VALUES
(230710018, 'Ece', 'Genç', 1),
(230710025, 'Esmanur', 'Muşlu', 229),
(231300001, 'Ahmet', 'Yılmaz', 248),
(231300002, 'Mehmet', 'Kaya', 251),
(231300003, 'Ayşe', 'Demir', 41),
(231300004, 'Fatma', 'Şahin', 259),
(231300005, 'Ali', 'Çelik', 260),
(231300006, 'Can', 'Yıldız', 189),
(231300007, 'Ece', 'Öztürk', 221),
(231300008, 'Murat', 'Arslan', 236),
(231300009, 'Selin', 'Kılıç', 242),
(231300010, 'Umut', 'Aydın', 191),
(231300011, 'Burak', 'Özdemir', 258),
(231300012, 'Aslı', 'Doğan', 13),
(231300013, 'Deniz', 'Erdoğan', 1),
(231300014, 'Gamze', 'Şen', 257),
(231300015, 'Eren', 'Avcı', 196),
(231300016, 'Gökhan', 'Aksu', 211),
(231300017, 'Merve', 'Kocaoğlu', 3),
(231300018, 'Oğuz', 'Özkan', 36),
(231300019, 'Seda', 'Yavuz', 183),
(231300020, 'Emre', 'Karahan', 207),
(231300021, 'Gizem', 'Gündoğdu', 31),
(231300022, 'Hakan', 'Sönmez', 222),
(231300023, 'Büşra', 'Pekcan', 15),
(231300024, 'Kadir', 'Balkaya', 229),
(231300025, 'Elif', 'Sezgin', 246),
(231300026, 'Tarık', 'Dağdelen', 32),
(231300027, 'Zeynep', 'Yurtseven', 190),
(231300028, 'Alper', 'Çetinkaya', 207),
(231300029, 'Pınar', 'Sarıca', 184),
(231300030, 'Cem', 'Demir', 195),
(231315010, 'Beren', 'Sönmez', 15),
(231315011, 'Kaan', 'Pekcan', 15),
(231315012, 'Zeynep', 'Balkaya', 15),
(231315013, 'Umut', 'Sezgin', 15),
(231315014, 'Simge', 'Eren', 15),
(231315015, 'Burak', 'Gündoğdu', 15),
(231317001, 'Alperen', 'Yurtseven', 14),
(231317002, 'Selin', 'Dağdelen', 14),
(231317003, 'Batuhan', 'Kocaoğlu', 14),
(231317004, 'Derin', 'Aksu', 14),
(231317005, 'Görkem', 'Özkan', 14),
(231317006, 'Melisa', 'Sarıca', 14),
(231317007, 'Arda', 'Çetinkaya', 14),
(231317008, 'Ecem', 'Yavuz', 14),
(231317009, 'Mert', 'Karahan', 14),
(231323036, 'Hakan', 'Çelik', 23),
(231323037, 'Ezgi', 'Yıldız', 23),
(231323038, 'Emre', 'Arslan', 23),
(231323039, 'Tuğba', 'Kaya', 23),
(231323040, 'Barış', 'Demir', 23),
(231323041, 'Gizem', 'Öztürk', 23),
(231323042, 'Volkan', 'Aydın', 23),
(231323043, 'Gamze', 'Avcı', 23),
(231323044, 'Serkan', 'Erdoğan', 23),
(231323045, 'Hande', 'Polat', 23),
(231323046, 'Okan', 'Özdemir', 23),
(231323047, 'Dilek', 'Kılıç', 23),
(231323048, 'Cem', 'Yılmaz', 23),
(231323049, 'Nihal', 'Doğan', 23),
(231323050, 'Fatih', 'Şen', 23);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `teachers`
--

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tc_no` char(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(50) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tc` (`tc_no`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `teachers`
--

INSERT INTO `teachers` (`id`, `tc_no`, `password`, `name`, `surname`, `email`, `created_at`, `updated_at`, `remember_token`) VALUES
(1, '10000000001', '$2y$12$SEcCUO.wKuHTdyEh9HDOze9ON3dccowxDgYdl3jk3Zs2KCbNoVBAe', 'Ahmet', 'Yılmaz', 'ahmet.yilmaz@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 08:32:31', 'XATVfR3UMePoRLCRXUZUZW2kb3SavQuVDfW0FThKZMFM8XgWfane8d9XSEQ8'),
(2, '10000000002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mehmet', 'Kaya', 'mehmet.kaya@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(3, '10000000003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Can', 'Demir', 'can.demir@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(4, '10000000004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mustafa', 'Öztürk', 'mustafa.ozturk@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(5, '10000000005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Zeynep', 'Aydın', 'zeynep.aydin@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(6, '10000000006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Murat', 'Arslan', 'murat.arslan@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(7, '10000000007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hüseyin', 'Çelik', 'huseyin.celik@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(8, '10000000008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gökhan', 'Polat', 'gokhan.polat@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(9, '10000000009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Elif', 'Yıldız', 'elif.yildiz@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(10, '10000000010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hakan', 'Şahin', 'hakan.sahin@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(11, '10000000011', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Deniz', 'Koç', 'deniz.koc@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(12, '10000000012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Merve', 'Kurt', 'merve.kurt@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(13, '10000000013', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Seda', 'Özkan', 'seda.ozkan@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(14, '10000000014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ayşe', 'Şen', 'ayse.sen@universite.edu.tr', '2026-07-10 07:29:26', '2026-07-10 07:29:26', NULL),
(16, '10000000016', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Oğuz', 'Kılıç', 'oguz.kilic@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(17, '10000000017', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Büşra', 'Bulut', 'busra.bulut@universite.edu.tr', '2026-07-13 11:28:33', '2026-07-13 11:28:33', NULL),
(34, '10000000034', '$2y$12$i0PAmFxrG.7ip0FlSkwaGeAjfsBoYczCReO7IcTlOvybQ70tF39KS', 'Hande', 'Demir', 'hande.demir@universite.edu.tr', '2026-07-10 07:29:26', '2026-07-13 07:29:18', NULL),
(46, '10000000046', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Derya', 'Avcı', 'derya.avci@universite.edu.tr', '2026-07-10 07:29:26', '2026-07-10 07:29:26', NULL),
(55, '10000000055', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'İbrahim', 'Balkaya', 'ibrahim.balkaya@universite.edu.tr', '2026-07-10 07:29:26', '2026-07-10 07:29:26', NULL),
(67, '10000000067', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Burcu', 'Çelik', 'burcu.celik@universite.edu.tr', '2026-07-10 07:29:26', '2026-07-10 07:29:26', NULL);

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_department_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `faculties` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `fk_exam_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_exam_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `fk_result_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_result_student` FOREIGN KEY (`student_no`) REFERENCES `students` (`student_no`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_student_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
