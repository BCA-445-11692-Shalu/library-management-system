-- ============================================================
-- Library Management System
-- Online Library Management System
-- Database: library_db  |  Version: 3.0
--
-- HOW TO IMPORT (2 methods):
--
-- METHOD 1 - phpMyAdmin (Recommended):
--   1. Open phpMyAdmin
--   2. Do NOT select any database first
--   3. Click "Import" tab at the top
--   4. Choose this file → Click Go
--
-- METHOD 2 - Command line:
--   mysql -u root -p < library.sql
-- ============================================================

/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40101 SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40014 SET FOREIGN_KEY_CHECKS=0 */;

-- ── Create database ───────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `library_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- ── Select database (explicit before every statement) ─────
USE `library_db`;

-- ============================================================
-- DROP TABLES — child first, then parents
-- ============================================================
DROP TABLE IF EXISTS `library_db`.`tblissuedbookdetails`;
DROP TABLE IF EXISTS `library_db`.`tbl_audit_log`;
DROP TABLE IF EXISTS `library_db`.`tbl_notifications`;
DROP TABLE IF EXISTS `library_db`.`tblbooks`;
DROP TABLE IF EXISTS `library_db`.`tblstudents`;
DROP TABLE IF EXISTS `library_db`.`tbl_fine_config`;
DROP TABLE IF EXISTS `library_db`.`tblcategory`;
DROP TABLE IF EXISTS `library_db`.`tblauthors`;
DROP TABLE IF EXISTS `library_db`.`admin`;

-- ============================================================
-- CREATE TABLES
-- ============================================================

-- 1. admin
CREATE TABLE `library_db`.`admin` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `full_name`    VARCHAR(100) NOT NULL,
  `email`        VARCHAR(120) NOT NULL,
  `username`     VARCHAR(100) NOT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `role`         ENUM('admin','librarian') NOT NULL DEFAULT 'admin',
  `reset_token`  VARCHAR(255) DEFAULT NULL,
  `token_expiry` DATETIME     DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email`    (`email`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. tblcategory
CREATE TABLE `library_db`.`tblcategory` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `status`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. tblauthors
CREATE TABLE `library_db`.`tblauthors` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(159) NOT NULL,
  `email`      VARCHAR(120) DEFAULT NULL,
  `bio`        TEXT         DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. tblbooks  (FK → tblcategory, tblauthors)
CREATE TABLE `library_db`.`tblbooks` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `isbn`        VARCHAR(25)   NOT NULL,
  `title`       VARCHAR(255)  NOT NULL,
  `cat_id`      INT(11)       DEFAULT NULL,
  `author_id`   INT(11)       DEFAULT NULL,
  `price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `qty`         INT(11)       NOT NULL DEFAULT 0,
  `available`   INT(11)       NOT NULL DEFAULT 0,
  `cover`       VARCHAR(255)  NOT NULL DEFAULT 'no-cover.jpg',
  `description` TEXT          DEFAULT NULL,
  `publisher`   VARCHAR(150)  DEFAULT NULL,
  `edition`     VARCHAR(50)   DEFAULT NULL,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_book_isbn`     (`isbn`),
  KEY          `idx_book_cat`   (`cat_id`),
  KEY          `idx_book_author`(`author_id`),
  KEY          `idx_book_title` (`title`),
  CONSTRAINT `fk_book_cat`    FOREIGN KEY (`cat_id`)
    REFERENCES `library_db`.`tblcategory`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_book_author` FOREIGN KEY (`author_id`)
    REFERENCES `library_db`.`tblauthors`(`id`)  ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. tblstudents
CREATE TABLE `library_db`.`tblstudents` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `student_id`   VARCHAR(20)  NOT NULL,
  `username`     VARCHAR(50)  DEFAULT NULL,
  `full_name`    VARCHAR(120) NOT NULL,
  `email`        VARCHAR(120) NOT NULL,
  `mobile`       VARCHAR(15)  DEFAULT NULL,
  `address`      TEXT         DEFAULT NULL,
  `profile_pic`  VARCHAR(255) NOT NULL DEFAULT 'default.png',
  `password`     VARCHAR(255) NOT NULL,
  `status`       TINYINT(1)   NOT NULL DEFAULT 1,
  `reset_token`  VARCHAR(255) DEFAULT NULL,
  `token_expiry` DATETIME     DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stu_sid`   (`student_id`),
  UNIQUE KEY `uq_stu_uname` (`username`),
  UNIQUE KEY `uq_stu_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. tbl_fine_config
CREATE TABLE `library_db`.`tbl_fine_config` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `fine_per_day` DECIMAL(6,2) NOT NULL DEFAULT 5.00,
  `issue_days`   INT(11)      NOT NULL DEFAULT 14,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. tblissuedbookdetails  (FK → tblbooks)
CREATE TABLE `library_db`.`tblissuedbookdetails` (
  `id`            INT(11)       NOT NULL AUTO_INCREMENT,
  `book_id`       INT(11)       NOT NULL,
  `student_id`    VARCHAR(20)   NOT NULL,
  `issued_by`     INT(11)       DEFAULT NULL,
  `issue_date`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date`      DATE          NOT NULL,
  `return_date`   TIMESTAMP     NULL DEFAULT NULL,
  `return_status` TINYINT(1)    NOT NULL DEFAULT 0,
  `fine_per_day`  DECIMAL(6,2)  NOT NULL DEFAULT 5.00,
  `fine_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fine_paid`     TINYINT(1)    NOT NULL DEFAULT 0,
  `remark`        TEXT          DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_issue_book`    (`book_id`),
  KEY `idx_issue_student` (`student_id`),
  CONSTRAINT `fk_issue_book`
    FOREIGN KEY (`book_id`) REFERENCES `library_db`.`tblbooks`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. tbl_notifications
CREATE TABLE `library_db`.`tbl_notifications` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      DEFAULT NULL,
  `user_type`  ENUM('student','admin') NOT NULL DEFAULT 'student',
  `title`      VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. tbl_audit_log
CREATE TABLE `library_db`.`tbl_audit_log` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `admin_id`   INT(11)      DEFAULT NULL,
  `action`     VARCHAR(255) NOT NULL,
  `table_name` VARCHAR(100) DEFAULT NULL,
  `record_id`  INT(11)      DEFAULT NULL,
  `details`    TEXT         DEFAULT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERT SAMPLE DATA
-- ============================================================

-- admin  (password = Admin@123)
INSERT INTO `library_db`.`admin`
  (`full_name`, `email`, `username`, `password`, `role`) VALUES
('System Admin', 'admin@library.com', 'admin',
 '0e7517141fb53f21ee439b355b5a1d0a', 'admin');

-- tblcategory
INSERT INTO `library_db`.`tblcategory` (`name`, `description`, `status`) VALUES
('Technology',  'Books related to technology and computing',       1),
('Programming', 'Programming languages and software development',  1),
('Science',     'Physics, Chemistry, Biology and general science', 1),
('Mathematics', 'Pure and applied mathematics',                    1),
('Management',  'Business management and leadership',              1),
('General',     'General interest and fiction books',              1),
('History',     'World and regional history books',                1),
('Literature',  'Classic and modern literature',                   1);

-- tblauthors
INSERT INTO `library_db`.`tblauthors` (`name`, `email`, `bio`) VALUES
('Herbert Schildt',    'h.schildt@example.com', 'Expert C/C++ and Java author'),
('Chetan Bhagat',      'chetan@example.com',     'Popular Indian fiction author'),
('HC Verma',           'hcverma@example.com',    'Physics professor and author'),
('RD Sharma',          'rdsharma@example.com',   'Mathematics textbook author'),
('Robert T. Kiyosaki', 'robert@example.com',     'Author of Rich Dad Poor Dad'),
('Andrew Hunt',        'ahunt@example.com',      'Co-author of The Pragmatic Programmer'),
('Donald Knuth',       'dknuth@example.com',     'Author of The Art of Computer Programming');

-- tblbooks
INSERT INTO `library_db`.`tblbooks`
  (`isbn`,           `title`,                                  `cat_id`,`author_id`,`price`,  `qty`,`available`,`description`,                                       `publisher`,        `edition`) VALUES
('9780071481274', 'C++: The Complete Reference',                   2,     1,         450.00,   5,    5,         'Comprehensive reference for C++ programming',       'McGraw-Hill',      '4th'  ),
('9789351343981', 'Five Point Someone',                            6,     2,         199.00,   8,    8,         'A fictional story set in IIT Delhi',                'Rupa Publications','1st'  ),
('9788177092585', 'Concepts of Physics Vol 1',                     3,     3,         350.00,   10,   10,        'Best physics book for JEE preparation',             'Bharati Bhawan',   '1st'  ),
('9789387067714', 'Mathematics Class XI',                          4,     4,         400.00,   6,    6,         'Comprehensive maths for Class 11',                  'Dhanpat Rai',      '12th' ),
('9781612680194', 'Rich Dad Poor Dad',                             5,     5,         299.00,   7,    7,         'Personal finance and investment guide',             'Plata Publishing', '1st'  ),
('9780201633610', 'The Pragmatic Programmer',                      2,     6,         699.00,   4,    4,         'A guide to software craftsmanship',                 'Addison-Wesley',   '1st'  ),
('9780201896831', 'The Art of Computer Programming Vol 1',         2,     7,         1200.00,  3,    3,         'Foundational algorithms and programming techniques','Addison-Wesley',   '3rd'  );

-- tblstudents  (all passwords = Admin@123)
INSERT INTO `library_db`.`tblstudents`
  (`student_id`, `username`, `full_name`, `email`, `mobile`, `password`, `status`) VALUES
('SID001', 'anuj01', 'Anuj Kumar',       'anuj@gmail.com',   '9865472555', '0e7517141fb53f21ee439b355b5a1d0a', 1),
('SID002', 'priya02', 'Priya Sharma',     'priya@gmail.com',  '9876543210', '0e7517141fb53f21ee439b355b5a1d0a', 1),
('SID003', 'rahul03', 'Rahul Singh',      'rahul@gmail.com',  '8585856224', '0e7517141fb53f21ee439b355b5a1d0a', 1),
('SID004', 'sarita04', 'Sarita Pandey',    'sarita@gmail.com', '4672423754', '0e7517141fb53f21ee439b355b5a1d0a', 1),
('SID005', 'john05', 'John Doe',         'john@test.com',    '1234567890', '0e7517141fb53f21ee439b355b5a1d0a', 1),
('SID006', 'ajay06', 'Ajay Kumar Singh', 'ajay@test.com',    '9988776655', '0e7517141fb53f21ee439b355b5a1d0a', 1);

-- tbl_fine_config
INSERT INTO `library_db`.`tbl_fine_config` (`fine_per_day`, `issue_days`) VALUES (5.00, 14);

-- tblissuedbookdetails
INSERT INTO `library_db`.`tblissuedbookdetails`
  (`book_id`,`student_id`,`issued_by`,`issue_date`,            `due_date`,   `return_date`,           `return_status`,`fine_per_day`,`fine_amount`,`fine_paid`,`remark`) VALUES
(1,'SID001',1,'2025-03-01 10:00:00','2025-03-15','2025-03-14 09:00:00',1,5.00, 0.00,1,'Returned on time'),
(2,'SID002',1,'2025-03-05 11:00:00','2025-03-19','2025-03-25 14:00:00',1,5.00,30.00,1,'Returned 6 days late'),
(3,'SID003',1,'2025-03-10 09:30:00','2025-03-24',NULL,                 0,5.00, 0.00,0,'Still with student'),
(4,'SID004',1,'2025-03-12 14:00:00','2025-03-26',NULL,                 0,5.00, 0.00,0,'Still with student'),
(5,'SID001',1,'2025-03-15 10:00:00','2025-03-29',NULL,                 0,5.00, 0.00,0,'Second issue for SID001');

-- Reduce available qty for currently issued books
UPDATE `library_db`.`tblbooks` SET `available` = `available` - 1 WHERE `id` IN (3, 4, 5);

-- ── Re-enable FK checks ───────────────────────────────────
/*!40014 SET FOREIGN_KEY_CHECKS=1 */;

-- ============================================================
-- Import complete! 9 tables | 39 rows of sample data
--
-- Credentials (all passwords = Admin@123):
--   Admin   →  username : admin
--   Student →  email    : anuj@gmail.com
-- ============================================================
