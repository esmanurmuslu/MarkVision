-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 20 Tem 2026, 08:16:25
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

DROP PROCEDURE IF EXISTS `Sp_Validate_And_Score_Optical`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `Sp_Validate_And_Score_Optical` (IN `p_student_no` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci, IN `p_student_name` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci, IN `p_student_surname` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci, IN `p_exam_id` INT, IN `p_student_answers_json` JSON, IN `p_optical_image_url` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci)   BEGIN
    DECLARE v_student_exists INT DEFAULT 0;
    DECLARE v_name_matches INT DEFAULT 0;
    DECLARE v_correct_count INT DEFAULT 0;
    DECLARE v_wrong_count INT DEFAULT 0;
    DECLARE v_blank_count INT DEFAULT 0;
    DECLARE v_total_questions INT DEFAULT 0;
    DECLARE v_final_score DECIMAL(5,2) DEFAULT 0.00;
    DECLARE v_answer_key_json JSON;
    DECLARE v_correct_answer_temp VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
    DECLARE v_student_answer_temp VARCHAR(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
    DECLARE i INT DEFAULT 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'HATA' AS status, 'Veritabanı işlemi sırasında teknik bir hata oluştu!' AS message;
    END;

    START TRANSACTION;

    --  DOĞRULAMA (VALIDATION)
    SELECT COUNT(*) INTO v_student_exists 
    FROM students 
    WHERE student_no = p_student_no;

    IF v_student_exists = 0 THEN
        SELECT 'HATA' AS status, 'Öğrenci numarası sistemde kayıtlı değil!' AS message;
        ROLLBACK;
    ELSE
        SELECT COUNT(*) INTO v_name_matches 
        FROM students 
        WHERE student_no = p_student_no 
          AND LOWER(student_name) COLLATE utf8mb4_turkish_ci = LOWER(p_student_name) COLLATE utf8mb4_turkish_ci
          AND LOWER(student_surname) COLLATE utf8mb4_turkish_ci = LOWER(p_student_surname) COLLATE utf8mb4_turkish_ci;

        IF v_name_matches = 0 THEN
            SELECT 'UYARI' AS status, 'Öğrenci numarası doğru ancak isim/soyisim sistemdeki kayıtla uyuşmuyor!' AS message;
            ROLLBACK;
        ELSE
            --  PUANLAMA VE KAYIT
            -- Soru sayısı ve cevap anahtarını döngüden ÖNCE tek seferde al
            SELECT total_questions, answer_key 
            INTO v_total_questions, v_answer_key_json
            FROM exams 
            WHERE id = p_exam_id;

            IF v_total_questions IS NULL OR v_total_questions = 0 THEN
                SELECT 'HATA' AS status, 'Bu sınava ait soru sayısı bilgisi bulunamadı!' AS message;
                ROLLBACK;
            ELSE
                WHILE i <= v_total_questions DO
                    SET v_correct_answer_temp = JSON_UNQUOTE(JSON_EXTRACT(v_answer_key_json, CONCAT('$.\"', i, '\"')));
                    SET v_student_answer_temp = JSON_UNQUOTE(JSON_EXTRACT(p_student_answers_json, CONCAT('$.\"', i, '\"')));
                    
                    IF v_student_answer_temp IS NULL OR v_student_answer_temp = '' THEN
                        SET v_blank_count = v_blank_count + 1;
                    ELSEIF LOWER(v_student_answer_temp) COLLATE utf8mb4_turkish_ci = LOWER(v_correct_answer_temp) COLLATE utf8mb4_turkish_ci THEN
                        SET v_correct_count = v_correct_count + 1;
                    ELSE
                        SET v_wrong_count = v_wrong_count + 1;
                    END IF;
                    
                    SET i = i + 1;
                END WHILE;

                SET v_final_score = (v_correct_count * 100.0) / v_total_questions;

                INSERT INTO exam_results (
                    student_no, exam_id, student_answers, correct_count, wrong_count, blank_count, score, status, optical_image_url, created_at, updated_at
                )
                VALUES (
                    p_student_no, p_exam_id, p_student_answers_json, v_correct_count, v_wrong_count, v_blank_count, v_final_score, 'pending_review', p_optical_image_url, NOW(), NOW()
                )
                ON DUPLICATE KEY UPDATE 
                    student_answers = p_student_answers_json,
                    correct_count = v_correct_count,
                    wrong_count = v_wrong_count,
                    blank_count = v_blank_count,
                    score = v_final_score,
                    optical_image_url = p_optical_image_url,
                    updated_at = NOW();

                COMMIT;

                SELECT 
                    'BAŞARILI' AS status, 
                    'Optik form başarıyla doğrulandı, puanlandı ve kaydedildi!' AS message,
                    v_correct_count AS dogru,
                    v_wrong_count AS yanlis,
                    v_blank_count AS bos,
                    v_final_score AS puan;
                    
            END IF;
        END IF;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(13, 36, 'HIT-101', 'Halkla İlişkilerde İletişim Kuramları', '2026-07-14 10:41:10', '2026-07-17 08:08:27'),
(14, 13, 'HIT-304', 'Kriz Yönetimi ve Reklam Kampanyaları', '2026-07-14 10:41:10', NULL),
(15, 258, 'GMS-101', 'Temel Mutfak Teknikleri ve Hijyen', '2026-07-14 10:41:10', NULL),
(16, 258, 'GMS-202', 'Dünya Mutfakları ve Pişirme Yöntemleri', '2026-07-14 10:41:10', NULL),
(17, 257, 'REH-104', 'Anadolu Arkeolojisi ve Mitoloji', '2026-07-14 10:41:10', NULL),
(18, 257, 'REH-301', 'Tur Planlaması ve Yönetimi', '2026-07-14 10:41:10', NULL),
(19, 190, 'TAB-201', 'Tahıllar ve Yemeklik Tane Baklagiller', '2026-07-14 10:41:10', NULL),
(20, 190, 'TAB-305', 'Endüstri Bitkileri Yetiştiriciliği', '2026-07-14 10:41:10', NULL),
(21, 41, 'SAY-102', 'Sağlık Kurumlarında Yönetim ve Organizasyon', '2026-07-14 10:41:10', NULL),
(22, 41, 'SAY-308', 'Sağlık Ekonomisi ve Finansmanı', '2026-07-14 10:41:10', NULL),
(23, 14, 'CRS-23', 'Ağ Yönetimi ve Yönetimi', '2026-07-20 06:51:18', NULL),
(24, 14, 'CRS-19', 'Bulut Bilişim', '2026-07-20 06:51:18', NULL),
(25, 14, 'CRS-31', 'Büyük Veri Analitiği', '2026-07-20 06:51:18', NULL),
(26, 14, 'CRS-27', 'Gömülü Sistemler', '2026-07-20 06:51:18', NULL),
(27, 14, 'CRS-35', 'Görüntü İşleme', '2026-07-20 06:51:18', NULL),
(28, 14, 'CRS-26', 'İşletim Sistemleri', '2026-07-20 06:51:18', NULL),
(29, 14, 'CRS-18', 'Mobil Uygulama Geliştirme', '2026-07-20 06:51:18', NULL),
(30, 14, 'CRS-34', 'Paralel Programlama', '2026-07-20 06:51:18', NULL),
(31, 14, 'CRS-22', 'Siber Güvenliğe Giriş', '2026-07-20 06:51:18', NULL),
(32, 14, 'CRS-15', 'Veritabanı Yönetim Sistemleri II', '2026-07-20 06:51:18', NULL),
(33, 14, 'CRS-11', 'Web Programlama', '2026-07-20 06:51:18', NULL),
(34, 14, 'CRS-30', 'Yazılım Testi ve Kalite', '2026-07-20 06:51:18', NULL),
(35, 15, 'CRS-33', 'E-Ticaret Sistemleri', '2026-07-20 06:51:18', NULL),
(36, 15, 'CRS-2', 'Finansal Muhasebe', '2026-07-20 06:51:18', NULL),
(37, 15, 'CRS-32', 'İnsan Kaynakları Yönetimi', '2026-07-20 06:51:18', NULL),
(38, 15, 'CRS-24', 'İşletme Finansı', '2026-07-20 06:51:18', NULL),
(39, 15, 'CRS-17', 'Makroekonomi', '2026-07-20 06:51:18', NULL),
(40, 15, 'CRS-25', 'Maliyet Muhasebesi', '2026-07-20 06:51:18', NULL),
(41, 15, 'CRS-16', 'Mikroekonomi', '2026-07-20 06:51:18', NULL),
(42, 15, 'CRS-21', 'Örgütsel Davranış', '2026-07-20 06:51:18', NULL),
(43, 15, 'CRS-20', 'Pazarlama İlkeleri', '2026-07-20 06:51:18', NULL),
(44, 15, 'CRS-29', 'Stratejik Yönetim', '2026-07-20 06:51:18', NULL),
(45, 15, 'CRS-28', 'Uluslararası Ticaret', '2026-07-20 06:51:18', NULL),
(46, 15, 'CRS-13', 'Yapay Zekaya Giriş', '2026-07-20 06:51:18', NULL),
(47, 15, 'CRS-36', 'Yönetim Bilişim Sistemleri', '2026-07-20 06:51:18', NULL),
(54, 31, 'YMG-101', 'Yeni Medyaya Giriş', '2026-07-20 07:53:48', NULL);

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
(36, 1, 'Halkla İlişkiler ve Tanıtım ', 'Lisans'),
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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `exams`
--

INSERT INTO `exams` (`id`, `department_id`, `teacher_id`, `course_id`, `course_name`, `exam_type`, `total_questions`, `answer_key`) VALUES
(1, 14, 46, 4, 'Veritabanı Yönetim Sistemleri', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"E\", \"6\": \"A\", \"7\": \"B\", \"8\": \"C\", \"9\": \"D\", \"10\": \"E\"}'),
(2, 15, 14, 36, 'Finansal Muhasebe', 'Final', 10, '{\"1\": \"B\", \"2\": \"B\", \"3\": \"C\", \"4\": \"A\", \"5\": \"E\", \"6\": \"A\", \"7\": \"D\", \"8\": \"C\", \"9\": \"D\", \"10\": \"A\"}'),
(9, 14, 46, 3, 'Nesne Yönelimli Programlama', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"E\", \"6\": \"A\", \"7\": \"B\", \"8\": \"C\", \"9\": \"D\", \"10\": \"E\"}'),
(10, 14, 46, 5, 'Veri Yapıları ve Algoritmalar', 'Final', 10, '{\"1\": \"B\", \"2\": \"C\", \"3\": \"D\", \"4\": \"A\", \"5\": \"E\", \"6\": \"B\", \"7\": \"C\", \"8\": \"D\", \"9\": \"A\", \"10\": \"E\"}'),
(11, 14, 46, 33, 'Web Programlama', 'Bütünleme', 10, '{\"1\": \"C\", \"2\": \"D\", \"3\": \"A\", \"4\": \"B\", \"5\": \"E\", \"6\": \"C\", \"7\": \"D\", \"8\": \"A\", \"9\": \"B\", \"10\": \"E\"}'),
(12, 15, 14, 8, 'İstatistik ve Olasılık', 'Vize', 10, '{\"1\": \"D\", \"2\": \"A\", \"3\": \"B\", \"4\": \"C\", \"5\": \"E\", \"6\": \"D\", \"7\": \"A\", \"8\": \"B\", \"9\": \"C\", \"10\": \"E\"}'),
(13, 15, 14, 46, 'Yapay Zekaya Giriş', 'Final', 10, '{\"1\": \"A\", \"2\": \"E\", \"3\": \"D\", \"4\": \"C\", \"5\": \"B\", \"6\": \"A\", \"7\": \"E\", \"8\": \"D\", \"9\": \"C\", \"10\": \"B\"}'),
(14, 14, 46, 6, 'Yazılım Mühendisliği', 'Vize', 10, '{\"1\": \"B\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"A\", \"6\": \"B\", \"7\": \"B\", \"8\": \"C\", \"9\": \"D\", \"10\": \"A\"}'),
(15, 14, 46, 32, 'Veritabanı Yönetim Sistemleri II', 'Final', 10, '{\"1\": \"E\", \"2\": \"D\", \"3\": \"C\", \"4\": \"B\", \"5\": \"A\", \"6\": \"E\", \"7\": \"D\", \"8\": \"C\", \"9\": \"B\", \"10\": \"A\"}'),
(16, 15, 14, 41, 'Mikroekonomi', 'Vize', 10, '{\"1\": \"A\", \"2\": \"C\", \"3\": \"E\", \"4\": \"B\", \"5\": \"D\", \"6\": \"A\", \"7\": \"C\", \"8\": \"E\", \"9\": \"B\", \"10\": \"D\"}'),
(17, 15, 14, 39, 'Makroekonomi', 'Final', 10, '{\"1\": \"C\", \"2\": \"E\", \"3\": \"A\", \"4\": \"D\", \"5\": \"B\", \"6\": \"C\", \"7\": \"E\", \"8\": \"A\", \"9\": \"D\", \"10\": \"B\"}'),
(18, 14, 46, 29, 'Mobil Uygulama Geliştirme', 'Vize', 10, '{\"1\": \"B\", \"2\": \"A\", \"3\": \"D\", \"4\": \"E\", \"5\": \"C\", \"6\": \"B\", \"7\": \"A\", \"8\": \"D\", \"9\": \"E\", \"10\": \"C\"}'),
(19, 14, 46, 24, 'Bulut Bilişim', 'Final', 10, '{\"1\": \"E\", \"2\": \"A\", \"3\": \"B\", \"4\": \"C\", \"5\": \"D\", \"6\": \"E\", \"7\": \"A\", \"8\": \"B\", \"9\": \"C\", \"10\": \"D\"}'),
(20, 15, 14, 43, 'Pazarlama İlkeleri', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"A\", \"4\": \"B\", \"5\": \"C\", \"6\": \"D\", \"7\": \"E\", \"8\": \"C\", \"9\": \"D\", \"10\": \"E\"}'),
(21, 15, 14, 42, 'Örgütsel Davranış', 'Final', 10, '{\"1\": \"D\", \"2\": \"D\", \"3\": \"C\", \"4\": \"C\", \"5\": \"B\", \"6\": \"B\", \"7\": \"A\", \"8\": \"A\", \"9\": \"E\", \"10\": \"E\"}'),
(22, 14, 46, 31, 'Siber Güvenliğe Giriş', 'Vize', 10, '{\"1\": \"C\", \"2\": \"C\", \"3\": \"B\", \"4\": \"A\", \"5\": \"E\", \"6\": \"D\", \"7\": \"C\", \"8\": \"B\", \"9\": \"A\", \"10\": \"E\"}'),
(23, 14, 46, 23, 'Ağ Yönetimi ve Yönetimi', 'Final', 10, '{\"1\": \"A\", \"2\": \"D\", \"3\": \"B\", \"4\": \"E\", \"5\": \"C\", \"6\": \"A\", \"7\": \"D\", \"8\": \"B\", \"9\": \"E\", \"10\": \"C\"}'),
(24, 15, 14, 38, 'İşletme Finansı', 'Vize', 10, '{\"1\": \"B\", \"2\": \"E\", \"3\": \"C\", \"4\": \"A\", \"5\": \"D\", \"6\": \"B\", \"7\": \"E\", \"8\": \"C\", \"9\": \"A\", \"10\": \"D\"}'),
(25, 15, 14, 40, 'Maliyet Muhasebesi', 'Final', 10, '{\"1\": \"E\", \"2\": \"C\", \"3\": \"A\", \"4\": \"D\", \"5\": \"B\", \"6\": \"E\", \"7\": \"C\", \"8\": \"A\", \"9\": \"D\", \"10\": \"B\"}'),
(26, 14, 46, 28, 'İşletim Sistemleri', 'Vize', 10, '{\"1\": \"D\", \"2\": \"B\", \"3\": \"E\", \"4\": \"A\", \"5\": \"C\", \"6\": \"D\", \"7\": \"B\", \"8\": \"E\", \"9\": \"A\", \"10\": \"C\"}'),
(27, 14, 46, 26, 'Gömülü Sistemler', 'Final', 10, '{\"1\": \"C\", \"2\": \"A\", \"3\": \"D\", \"4\": \"B\", \"5\": \"E\", \"6\": \"C\", \"7\": \"A\", \"8\": \"D\", \"9\": \"B\", \"10\": \"E\"}'),
(28, 15, 14, 45, 'Uluslararası Ticaret', 'Vize', 10, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"E\", \"6\": \"E\", \"7\": \"D\", \"8\": \"C\", \"9\": \"B\", \"10\": \"A\"}'),
(29, 15, 14, 44, 'Stratejik Yönetim', 'Final', 10, '{\"1\": \"B\", \"2\": \"C\", \"3\": \"D\", \"4\": \"E\", \"5\": \"A\", \"6\": \"A\", \"7\": \"E\", \"8\": \"D\", \"9\": \"C\", \"10\": \"B\"}'),
(30, 14, 46, 34, 'Yazılım Testi ve Kalite', 'Vize', 10, '{\"1\": \"E\", \"2\": \"D\", \"3\": \"C\", \"4\": \"B\", \"5\": \"A\", \"6\": \"B\", \"7\": \"C\", \"8\": \"D\", \"9\": \"E\", \"10\": \"A\"}'),
(31, 14, 46, 25, 'Büyük Veri Analitiği', 'Final', 10, '{\"1\": \"C\", \"2\": \"E\", \"3\": \"B\", \"4\": \"A\", \"5\": \"D\", \"6\": \"C\", \"7\": \"E\", \"8\": \"B\", \"9\": \"A\", \"10\": \"D\"}'),
(32, 15, 14, 37, 'İnsan Kaynakları Yönetimi', 'Vize', 10, '{\"1\": \"D\", \"2\": \"A\", \"3\": \"E\", \"4\": \"C\", \"5\": \"B\", \"6\": \"D\", \"7\": \"A\", \"8\": \"E\", \"9\": \"C\", \"10\": \"B\"}'),
(33, 15, 14, 35, 'E-Ticaret Sistemleri', 'Final', 10, '{\"1\": \"A\", \"2\": \"D\", \"3\": \"B\", \"4\": \"E\", \"5\": \"C\", \"6\": \"A\", \"7\": \"D\", \"8\": \"B\", \"9\": \"E\", \"10\": \"C\"}'),
(34, 14, 46, 30, 'Paralel Programlama', 'Vize', 10, '{\"1\": \"B\", \"2\": \"C\", \"3\": \"A\", \"4\": \"D\", \"5\": \"E\", \"6\": \"B\", \"7\": \"C\", \"8\": \"A\", \"9\": \"D\", \"10\": \"E\"}'),
(35, 14, 46, 27, 'Görüntü İşleme', 'Final', 10, '{\"1\": \"E\", \"2\": \"B\", \"3\": \"D\", \"4\": \"A\", \"5\": \"C\", \"6\": \"E\", \"7\": \"B\", \"8\": \"D\", \"9\": \"A\", \"10\": \"C\"}'),
(36, 15, 14, 47, 'Yönetim Bilişim Sistemleri', 'Vize', 10, '{\"1\": \"C\", \"2\": \"A\", \"3\": \"E\", \"4\": \"D\", \"5\": \"B\", \"6\": \"C\", \"7\": \"A\", \"8\": \"E\", \"9\": \"D\", \"10\": \"B\"}'),
(37, 31, 1, 54, 'Yeni Medyaya Giriş', 'Vize', 10, '{\"1\": \"C\", \"2\": \"A\", \"3\": \"D\", \"4\": \"B\", \"5\": \"E\", \"6\": \"D\", \"7\": \"C\", \"8\": \"A\", \"9\": \"E\", \"10\": \"B\"}');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exam_questions`
--

DROP TABLE IF EXISTS `exam_questions`;
CREATE TABLE IF NOT EXISTS `exam_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `question_number` int NOT NULL,
  `question_text` text,
  `correct_answer` char(1) NOT NULL,
  `points` decimal(4,2) DEFAULT '10.00',
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `exam_results`
--

INSERT INTO `exam_results` (`id`, `student_no`, `exam_id`, `student_answers`, `correct_count`, `wrong_count`, `blank_count`, `score`, `status`, `optical_image_url`, `created_at`, `updated_at`) VALUES
(2, 230710025, 1, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"A\", \"5\": \"B\", \"6\": \"C\", \"7\": \"A\", \"8\": \"B\", \"9\": \"C\", \"10\": \"A\"}', 3, 7, 0, 30.00, 'pending_review', 'scanned_forms/optik_esmanur.jpg', '2026-07-13 12:01:42', '2026-07-16 08:55:41'),
(4, 231300001, 1, '{\"1\": \"A\", \"2\": \"B\", \"3\": \"C\", \"4\": \"D\", \"5\": \"E\", \"6\": \"A\", \"7\": \"B\", \"8\": \"C\", \"9\": \"D\", \"10\": \"E\"}', 10, 0, 0, 100.00, 'pending_review', 'scanned_forms/optik_ahmet.jpg', '2026-07-16 09:04:07', '2026-07-16 09:04:07'),
(5, 231300021, 37, '{\"1\": \"C\", \"2\": \"A\", \"3\": \"D\", \"4\": \"B\", \"5\": \"E\", \"6\": \"D\", \"7\": \"C\", \"8\": \"A\", \"9\": \"E\", \"10\": \"B\"}', 10, 0, 0, 100.00, 'pending_review', 'scanned_forms/optik_gizem_gundogdu.jpg', '2026-07-20 07:53:49', '2026-07-20 08:16:07');

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
-- Tablo için tablo yapısı `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(125) COLLATE utf8mb3_unicode_ci NOT NULL,
  `connection` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `queue` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(125) COLLATE utf8mb3_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(125) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb3_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Tablo döküm verisi `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ogrenci_sonuclar`
--

DROP TABLE IF EXISTS `ogrenci_sonuclar`;
CREATE TABLE IF NOT EXISTS `ogrenci_sonuclar` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `sinav_id` bigint UNSIGNED NOT NULL,
  `ogrenci_no` varchar(15) COLLATE utf8mb3_unicode_ci NOT NULL,
  `ogrenci_ad_soyad` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ogrenci_cevaplari` json NOT NULL,
  `dogru_sayisi` int NOT NULL DEFAULT '0',
  `yanlis_sayisi` int NOT NULL DEFAULT '0',
  `bos_sayisi` int NOT NULL DEFAULT '0',
  `toplam_puan` decimal(5,2) NOT NULL DEFAULT '0.00',
  `gorsel_yolu` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb3_unicode_ci,
  `payload` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Tablo döküm verisi `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('g4W6SyoneGqRED3Qv1uHmXeoGcZUYyCW99IYeSPn', 55, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.129.0 Chrome/148.0.7778.280 Electron/42.6.0 Safari/537.36', 'eyJfdG9rZW4iOiJibkFuNFNYaGxwSzBEbDhXbVkxRmtkTnZ0Zkc3aElsVjFIUVR1bnN0IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL3JlZ2lzdGVyIiwicm91dGUiOiJyZWdpc3RlciJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6NTV9', 1784284312),
('o2pWh7mQlA57YDf4Mj2ud7JdXEeFstciYUaljWTd', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.128.0 Chrome/148.0.7778.271 Electron/42.5.0 Safari/537.36', 'eyJfdG9rZW4iOiJXaDFpZTFjRXp5aW4zenBDUlphQUpKNXdZMXo3NWdsUWNIUjV5bXdrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9nZWNtaXMtc29udWNsYXIiLCJyb3V0ZSI6InBhbmVsLmdlY21pcyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjozfQ==', 1784273873);

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

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Görünüm yapısı durumu `view_student_exam_details`
-- (Asıl görünüm için aşağıya bakın)
--
DROP VIEW IF EXISTS `view_student_exam_details`;
CREATE TABLE IF NOT EXISTS `view_student_exam_details` (
`result_id` bigint unsigned
,`student_no` int
,`student_fullname` varchar(101)
,`department_name` varchar(150)
,`course_code` varchar(20)
,`course_name` varchar(100)
,`exam_type` varchar(20)
,`score` decimal(5,2)
,`status` enum('success','pending_review','failed')
,`teacher_fullname` varchar(101)
,`graded_at` timestamp
);

-- --------------------------------------------------------

--
-- Görünüm yapısı `view_student_exam_details`
--
DROP TABLE IF EXISTS `view_student_exam_details`;

DROP VIEW IF EXISTS `view_student_exam_details`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_student_exam_details`  AS SELECT `er`.`id` AS `result_id`, `s`.`student_no` AS `student_no`, concat(`s`.`student_name`,' ',`s`.`student_surname`) AS `student_fullname`, `d`.`department_name` AS `department_name`, `c`.`course_code` AS `course_code`, `c`.`course_name` AS `course_name`, `e`.`exam_type` AS `exam_type`, `er`.`score` AS `score`, `er`.`status` AS `status`, concat(`t`.`name`,' ',`t`.`surname`) AS `teacher_fullname`, `er`.`updated_at` AS `graded_at` FROM (((((`exam_results` `er` join `students` `s` on((`er`.`student_no` = `s`.`student_no`))) join `exams` `e` on((`er`.`exam_id` = `e`.`id`))) join `courses` `c` on((`e`.`course_id` = `c`.`id`))) join `departments` `d` on((`s`.`department_id` = `d`.`id`))) join `teachers` `t` on((`e`.`teacher_id` = `t`.`id`))) ;

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
-- Tablo kısıtlamaları `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD CONSTRAINT `exam_questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE;

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
