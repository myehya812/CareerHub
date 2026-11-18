-- CareerHub complete application schema through Step 6, 17 November 2026.
-- Verified on MySQL 8.4.11 and MariaDB 10.4.32.
-- Import only into an empty, selected database as an alternative to artisan migrate.
-- Contains 15 tables, 17 migration records, ten procedures, six functions and nine triggers.
-- No account, recruitment or history data is included. No database is created or dropped.
-- Dedicated database accounts are provisioned separately using careerhub:database-access.


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_status_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `old_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  KEY `idx_history_application_sequence` (`application_id`,`id`),
  CONSTRAINT `application_status_history_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_history_event` CHECK ((((cast(`event_type` as char charset binary) in (_utf8mb4'baseline',_utf8mb4'created')) and (`old_status` is null)) or ((cast(`event_type` as char charset binary) = _utf8mb4'status_changed') and (`old_status` is not null) and (cast(`old_status` as char charset binary) <> cast(`new_status` as char charset binary))))),
  CONSTRAINT `chk_history_status` CHECK ((((`old_status` is null) or (cast(`old_status` as char charset binary) in (_utf8mb4'pending',_utf8mb4'accepted',_utf8mb4'rejected'))) and (cast(`new_status` as char charset binary) in (_utf8mb4'pending',_utf8mb4'accepted',_utf8mb4'rejected'))))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `job_listing_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_user_id_job_listing_id_unique` (`user_id`,`job_listing_id`),
  KEY `applications_job_listing_id_foreign` (`job_listing_id`),
  CONSTRAINT `applications_job_listing_id_foreign` FOREIGN KEY (`job_listing_id`) REFERENCES `job_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_application_status` CHECK ((cast(`status` as char charset binary) in (_utf8mb4'pending',_utf8mb4'accepted',_utf8mb4'rejected')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_listings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salary_min` int unsigned DEFAULT NULL,
  `salary_max` int unsigned DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_listings_user_id_foreign` (`user_id`),
  CONSTRAINT `job_listings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_job_salary` CHECK ((((`salary_min` is null) or (`salary_min` >= 0)) and ((`salary_max` is null) or (`salary_max` >= 0)) and ((`salary_min` is null) or (`salary_max` is null) or (`salary_min` <= `salary_max`)))),
  CONSTRAINT `chk_job_status` CHECK ((cast(`status` as char charset binary) in (_utf8mb4'active',_utf8mb4'draft',_utf8mb4'closed'))),
  CONSTRAINT `chk_job_type` CHECK ((cast(`type` as char charset binary) in (_utf8mb4'Full-Time',_utf8mb4'Part-Time',_utf8mb4'Internship',_utf8mb4'Contract')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `headline` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skills` json DEFAULT NULL,
  `experience` text COLLATE utf8mb4_unicode_ci,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resume_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resume_original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `profiles_user_id_unique` (`user_id`),
  CONSTRAINT `profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `job_listing_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `saved_jobs_user_id_job_listing_id_unique` (`user_id`,`job_listing_id`),
  KEY `saved_jobs_job_listing_id_foreign` (`job_listing_id`),
  CONSTRAINT `saved_jobs_job_listing_id_foreign` FOREIGN KEY (`job_listing_id`) REFERENCES `job_listings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `saved_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'job_seeker',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  CONSTRAINT `chk_users_role` CHECK ((cast(`role` as char charset binary) in (_utf8mb4'job_seeker',_utf8mb4'company')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;



/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_09_11_165804_create_job_listings_table',1),(5,'2026_09_11_175225_create_personal_access_tokens_table',1),(6,'2026_09_16_100946_add_role_to_users_table',1),(7,'2026_09_16_175814_add_user_id_to_job_listings_table',1),(8,'2026_09_17_104541_create_applications_table',1),(9,'2026_09_19_165642_create_profiles_table',1),(10,'2026_09_20_200533_add_resume_fields_to_profiles_table',1),(11,'2026_09_20_232408_rename_canmpany_name_to_company_name_in_profiles_table',1),(12,'2026_09_22_194514_create_saved_jobs_table',1),(13,'2026_11_17_000000_enforce_recruitment_values',1),(14,'2026_11_17_000100_create_careerhub_procedures',1),(15,'2026_11_17_000200_create_application_history_and_triggers',1),(16,'2026_11_17_000300_create_careerhub_functions',1),(18,'2026_11_17_000400_create_careerhub_cursors',2);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- CareerHub stored procedures, 17 November 2026.
-- Install after the Step 1 tables and constraints. No table or data is dropped.
-- All public procedures REQUIRE a caller-owned transaction:
-- START TRANSACTION; CALL ...; COMMIT; -- ROLLBACK on any error.
-- Laravel's RecruitmentTransactions service supplies this boundary automatically.
-- SQL SECURITY INVOKER preserves the caller's existing database privileges.

DELIMITER $$

CREATE PROCEDURE ch_require_transaction()
SQL SECURITY INVOKER
BEGIN
    DECLARE EXIT HANDLER FOR 1305
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30005,
            MESSAGE_TEXT = 'CareerHub procedures require an active caller transaction.';
    -- In autocommit mode no savepoint survives to RELEASE, so this fails
    -- before a public procedure can write. Never issue START TRANSACTION here.
    SAVEPOINT careerhub_transaction_probe;
    RELEASE SAVEPOINT careerhub_transaction_probe;
END$$

CREATE PROCEDURE ch_create_job(
    IN p_company_id BIGINT UNSIGNED,
    IN p_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN p_description TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN p_location VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    IN p_type VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN p_salary_min BIGINT, IN p_salary_max BIGINT,
    IN p_currency VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci, IN p_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
)
SQL SECURITY INVOKER
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing = TRUE;
    CALL ch_require_transaction();
    SELECT role INTO v_role FROM users WHERE id = p_company_id FOR UPDATE;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Company account not found.';
    END IF;
    IF BINARY v_role <> 'company' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'Only companies can create jobs.';
    END IF;
    IF p_title IS NULL OR CHAR_LENGTH(TRIM(p_title)) = 0
        OR p_description IS NULL OR CHAR_LENGTH(TRIM(p_description)) = 0
        OR p_location IS NULL OR CHAR_LENGTH(TRIM(p_location)) = 0
        OR p_type IS NULL OR BINARY p_type NOT IN ('Full-Time','Part-Time','Internship','Contract')
        OR p_status IS NULL OR BINARY p_status NOT IN ('active','draft','closed')
        OR p_currency IS NULL OR CHAR_LENGTH(p_currency) <> 3
        OR (p_salary_min IS NOT NULL AND (p_salary_min < 0 OR p_salary_min > 4294967295))
        OR (p_salary_max IS NOT NULL AND (p_salary_max < 0 OR p_salary_max > 4294967295))
        OR (p_salary_min IS NOT NULL AND p_salary_max IS NOT NULL AND p_salary_min > p_salary_max) THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30004, MESSAGE_TEXT = 'Invalid job fields or salary range.';
    END IF;
    INSERT INTO job_listings (user_id,title,description,location,type,salary_min,salary_max,currency,status,created_at,updated_at)
    VALUES (p_company_id,p_title,p_description,p_location,p_type,p_salary_min,p_salary_max,p_currency,p_status,UTC_TIMESTAMP(),UTC_TIMESTAMP());
    SELECT LAST_INSERT_ID() AS job_id;
END$$

CREATE PROCEDURE ch_submit_application(IN p_user_id BIGINT UNSIGNED, IN p_job_id BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_job BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_application BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing = TRUE;
    DECLARE EXIT HANDLER FOR 1062
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30003, MESSAGE_TEXT = 'You have already applied to this job.';
    CALL ch_require_transaction();
    -- Lock the actor before the job, consistently with bookmark procedures.
    SELECT role INTO v_role FROM users WHERE id = p_user_id FOR UPDATE;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'User not found.';
    END IF;
    IF BINARY v_role <> 'job_seeker' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'Only job seekers can apply to jobs.';
    END IF;
    SELECT id,status INTO v_job,v_status FROM job_listings WHERE id = p_job_id FOR UPDATE;
    IF v_job IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Job not found.';
    END IF;
    IF BINARY v_status <> 'active' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30003, MESSAGE_TEXT = 'This job is not accepting applications.';
    END IF;
    SELECT id INTO v_application FROM applications WHERE user_id=p_user_id AND job_listing_id=p_job_id FOR UPDATE;
    IF v_application IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30003, MESSAGE_TEXT = 'You have already applied to this job.';
    END IF;
    INSERT INTO applications (user_id,job_listing_id,status,created_at,updated_at)
    VALUES (p_user_id,p_job_id,'pending',UTC_TIMESTAMP(),UTC_TIMESTAMP());
    SELECT LAST_INSERT_ID() AS application_id;
END$$

CREATE PROCEDURE ch_update_application_status(
    IN p_company_id BIGINT UNSIGNED, IN p_application_id BIGINT UNSIGNED, IN p_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
)
SQL SECURITY INVOKER
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_job BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_owner BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_application BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing = TRUE;
    CALL ch_require_transaction();
    IF p_status IS NULL OR BINARY p_status NOT IN ('pending','accepted','rejected') THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30004, MESSAGE_TEXT = 'Invalid application status.';
    END IF;
    SELECT role INTO v_role FROM users WHERE id=p_company_id FOR UPDATE;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Company account not found.';
    END IF;
    IF BINARY v_role <> 'company' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'Only companies can update applications.';
    END IF;
    -- Discover the parent, then lock the job before its application.
    SELECT job_listing_id INTO v_job FROM applications WHERE id=p_application_id;
    IF v_job IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Application not found.';
    END IF;
    SELECT user_id INTO v_owner FROM job_listings WHERE id=v_job FOR UPDATE;
    IF v_owner IS NULL OR v_owner <> p_company_id THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'This application does not belong to your company.';
    END IF;
    SELECT id INTO v_application FROM applications WHERE id=p_application_id AND job_listing_id=v_job FOR UPDATE;
    IF v_application IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Application not found.';
    END IF;
    UPDATE applications SET status=p_status,updated_at=UTC_TIMESTAMP() WHERE id=p_application_id;
    SELECT p_application_id AS application_id;
END$$

CREATE PROCEDURE ch_save_job(IN p_user_id BIGINT UNSIGNED, IN p_job_id BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_job BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_saved BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing = TRUE;
    CALL ch_require_transaction();
    SELECT role INTO v_role FROM users WHERE id=p_user_id FOR UPDATE;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'User not found.';
    END IF;
    IF BINARY v_role <> 'job_seeker' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'Only job seekers can save jobs.';
    END IF;
    SELECT id INTO v_job FROM job_listings WHERE id=p_job_id FOR UPDATE;
    IF v_job IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Job not found.';
    END IF;
    SELECT id INTO v_saved FROM saved_jobs WHERE user_id=p_user_id AND job_listing_id=p_job_id FOR UPDATE;
    IF v_saved IS NOT NULL THEN
        SELECT v_saved AS saved_job_id,0 AS created;
    ELSE
        INSERT INTO saved_jobs (user_id,job_listing_id,created_at,updated_at)
        VALUES (p_user_id,p_job_id,UTC_TIMESTAMP(),UTC_TIMESTAMP());
        SELECT LAST_INSERT_ID() AS saved_job_id,1 AS created;
    END IF;
END$$

CREATE PROCEDURE ch_remove_saved_job(IN p_user_id BIGINT UNSIGNED, IN p_job_id BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_job BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_saved BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing = TRUE;
    CALL ch_require_transaction();
    SELECT role INTO v_role FROM users WHERE id=p_user_id FOR UPDATE;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'User not found.';
    END IF;
    IF BINARY v_role <> 'job_seeker' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'Only job seekers can remove saved jobs.';
    END IF;
    SELECT id INTO v_job FROM job_listings WHERE id=p_job_id FOR UPDATE;
    IF v_job IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Job not found.';
    END IF;
    SELECT id INTO v_saved FROM saved_jobs WHERE user_id=p_user_id AND job_listing_id=p_job_id FOR UPDATE;
    IF v_saved IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'This job is not saved.';
    END IF;
    DELETE FROM saved_jobs WHERE id=v_saved;
    SELECT v_saved AS saved_job_id;
END$$

CREATE PROCEDURE ch_delete_job(IN p_company_id BIGINT UNSIGNED, IN p_job_id BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_job BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_owner BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing = TRUE;
    CALL ch_require_transaction();
    SELECT role INTO v_role FROM users WHERE id=p_company_id FOR UPDATE;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Company account not found.';
    END IF;
    IF BINARY v_role <> 'company' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'Only companies can delete jobs.';
    END IF;
    SELECT id,user_id INTO v_job,v_owner FROM job_listings WHERE id=p_job_id FOR UPDATE;
    IF v_job IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30001, MESSAGE_TEXT = 'Job not found.';
    END IF;
    IF v_owner IS NULL OR v_owner <> p_company_id THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO = 30002, MESSAGE_TEXT = 'This job does not belong to your company.';
    END IF;
    -- Existing foreign keys atomically remove applications and bookmarks.
    DELETE FROM job_listings WHERE id=p_job_id;
    SELECT p_job_id AS job_id;
END$$

DELIMITER ;

-- CareerHub reporting functions, 17 November 2026.
-- Install after the CareerHub tables exist. No tables or records are modified.
-- Counts reflect the caller's transaction visibility and current database contents.
-- NULL and nonexistent nonnegative IDs return zero. IDs match unsigned bigint keys.

DELIMITER $$

CREATE FUNCTION ch_user_application_count(p_user_id BIGINT UNSIGNED)
RETURNS BIGINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY INVOKER
RETURN (SELECT COUNT(*) FROM applications WHERE user_id=p_user_id)$$

CREATE FUNCTION ch_user_saved_job_count(p_user_id BIGINT UNSIGNED)
RETURNS BIGINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY INVOKER
RETURN (SELECT COUNT(*) FROM saved_jobs WHERE user_id=p_user_id)$$

CREATE FUNCTION ch_company_job_count(p_company_id BIGINT UNSIGNED)
RETURNS BIGINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY INVOKER
RETURN (SELECT COUNT(*) FROM job_listings WHERE user_id=p_company_id)$$

CREATE FUNCTION ch_company_active_job_count(p_company_id BIGINT UNSIGNED)
RETURNS BIGINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY INVOKER
RETURN (SELECT COUNT(*) FROM job_listings WHERE user_id=p_company_id AND BINARY status='active')$$

CREATE FUNCTION ch_company_application_count(p_company_id BIGINT UNSIGNED)
RETURNS BIGINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY INVOKER
RETURN (SELECT COUNT(*) FROM applications a JOIN job_listings j ON j.id=a.job_listing_id WHERE j.user_id=p_company_id)$$

CREATE FUNCTION ch_job_application_count(p_job_id BIGINT UNSIGNED)
RETURNS BIGINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
SQL SECURITY INVOKER
RETURN (SELECT COUNT(*) FROM applications WHERE job_listing_id=p_job_id)$$

DELIMITER ;

-- CareerHub triggers, 17 November 2026.
-- Install after application_status_history and the Step 1 constraints exist.
-- Triggers participate in the triggering statement's transaction; never COMMIT here.
-- No DEFINER is pinned: installation uses the deploying database account.

DELIMITER $$

CREATE TRIGGER ch_job_before_insert BEFORE INSERT ON job_listings
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing=TRUE;
    -- Preserve explicitly supported ownerless legacy vacancies.
    IF NEW.user_id IS NOT NULL THEN
        SELECT role INTO v_role FROM users WHERE id=NEW.user_id LOCK IN SHARE MODE;
        IF v_role IS NULL OR BINARY v_role <> 'company' THEN
            SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Job publishers must be company accounts.';
        END IF;
    END IF;
END$$

CREATE TRIGGER ch_job_before_update BEFORE UPDATE ON job_listings
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing=TRUE;
    -- Unchanged owners remain protected by ch_user_before_update. Avoid an
    -- unnecessary parent lock when updating a job's content or active status.
    IF NOT (NEW.user_id <=> OLD.user_id) AND NEW.user_id IS NOT NULL THEN
        SELECT role INTO v_role FROM users WHERE id=NEW.user_id LOCK IN SHARE MODE;
        IF v_role IS NULL OR BINARY v_role <> 'company' THEN
            SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Job publishers must be company accounts.';
        END IF;
    END IF;
END$$

CREATE TRIGGER ch_application_before_insert BEFORE INSERT ON applications
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_job BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing=TRUE;
    SELECT role INTO v_role FROM users WHERE id=NEW.user_id LOCK IN SHARE MODE;
    IF v_role IS NULL OR BINARY v_role <> 'job_seeker' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Applicants must be job seeker accounts.';
    END IF;
    SELECT id,status INTO v_job,v_status FROM job_listings WHERE id=NEW.job_listing_id LOCK IN SHARE MODE;
    IF v_job IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='Job not found.';
    END IF;
    IF BINARY v_status <> 'active' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30003, MESSAGE_TEXT='This job is not accepting applications.';
    END IF;
END$$

CREATE TRIGGER ch_application_before_update BEFORE UPDATE ON applications
FOR EACH ROW
BEGIN
    IF NEW.id <> OLD.id OR NEW.user_id <> OLD.user_id OR NEW.job_listing_id <> OLD.job_listing_id THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='An application cannot be reassigned to another user or job.';
    END IF;
END$$

CREATE TRIGGER ch_application_after_insert AFTER INSERT ON applications
FOR EACH ROW
BEGIN
    INSERT INTO application_status_history (application_id,old_status,new_status,event_type,changed_at)
    VALUES (NEW.id,NULL,NEW.status,'created',CURRENT_TIMESTAMP(6));
END$$

CREATE TRIGGER ch_application_after_update AFTER UPDATE ON applications
FOR EACH ROW
BEGIN
    IF NOT (BINARY NEW.status <=> BINARY OLD.status) THEN
        INSERT INTO application_status_history (application_id,old_status,new_status,event_type,changed_at)
        VALUES (NEW.id,OLD.status,NEW.status,'status_changed',CURRENT_TIMESTAMP(6));
    END IF;
END$$

CREATE TRIGGER ch_saved_job_before_insert BEFORE INSERT ON saved_jobs
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing=TRUE;
    SELECT role INTO v_role FROM users WHERE id=NEW.user_id LOCK IN SHARE MODE;
    IF v_role IS NULL OR BINARY v_role <> 'job_seeker' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Only job seeker accounts can bookmark jobs.';
    END IF;
END$$

CREATE TRIGGER ch_saved_job_before_update BEFORE UPDATE ON saved_jobs
FOR EACH ROW
BEGIN
    IF NEW.id <> OLD.id OR NEW.user_id <> OLD.user_id OR NEW.job_listing_id <> OLD.job_listing_id THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='A bookmark cannot be reassigned to another user or job.';
    END IF;
END$$

CREATE TRIGGER ch_user_before_update BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    DECLARE v_reference BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_missing BOOLEAN DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_missing=TRUE;
    IF NOT (BINARY NEW.role <=> BINARY OLD.role) THEN
        IF BINARY NEW.role='job_seeker' THEN
            -- Locking reads see current committed rows even if the caller's
            -- REPEATABLE READ snapshot was created before a concurrent insert.
            SELECT id INTO v_reference FROM job_listings WHERE user_id=OLD.id LIMIT 1 LOCK IN SHARE MODE;
            IF v_reference IS NOT NULL THEN
                SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30003, MESSAGE_TEXT='A user publishing jobs must remain a company.';
            END IF;
        ELSEIF BINARY NEW.role='company' THEN
            SELECT id INTO v_reference FROM applications WHERE user_id=OLD.id LIMIT 1 LOCK IN SHARE MODE;
            IF v_reference IS NOT NULL THEN
                SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30003, MESSAGE_TEXT='A user with applications must remain a job seeker.';
            END IF;
            SET v_reference=NULL;
            SELECT id INTO v_reference FROM saved_jobs WHERE user_id=OLD.id LIMIT 1 LOCK IN SHARE MODE;
            IF v_reference IS NOT NULL THEN
                SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30003, MESSAGE_TEXT='A user with bookmarks must remain a job seeker.';
            END IF;
        END IF;
    END IF;
END$$

DELIMITER ;

-- CareerHub cursor reports, 17 November 2026.
-- These procedures read data and build bounded JSON in local variables only.
-- No recruitment data, temporary tables or transaction boundaries are changed.
-- after_id is the last emitted ID (NULL means zero); limit must be 1..200.

DELIMITER $$

CREATE PROCEDURE ch_cursor_company_jobs(IN p_company_id BIGINT UNSIGNED, IN p_after_id BIGINT, IN p_limit INT)
READS SQL DATA
SQL SECURITY INVOKER
BEGIN
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_open BOOLEAN DEFAULT FALSE;
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_id BIGINT UNSIGNED;
    DECLARE v_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_location VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_type VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_currency VARCHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_salary_min BIGINT UNSIGNED;
    DECLARE v_salary_max BIGINT UNSIGNED;
    DECLARE v_total BIGINT UNSIGNED;
    DECLARE v_pending BIGINT UNSIGNED;
    DECLARE v_accepted BIGINT UNSIGNED;
    DECLARE v_rejected BIGINT UNSIGNED;
    DECLARE v_report LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '[]';
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_next BIGINT UNSIGNED DEFAULT NULL;
    DECLARE report_cursor CURSOR FOR
        SELECT j.id,j.title,j.location,j.type,j.status,j.currency,j.salary_min,j.salary_max,
            COALESCE(s.total,0),COALESCE(s.pending,0),COALESCE(s.accepted,0),COALESCE(s.rejected,0)
        FROM job_listings j
        LEFT JOIN (SELECT a.job_listing_id,COUNT(*) AS total,
            SUM(a.status='pending') AS pending,SUM(a.status='accepted') AS accepted,SUM(a.status='rejected') AS rejected
            FROM applications a GROUP BY a.job_listing_id) s ON s.job_listing_id=j.id
        WHERE j.user_id=p_company_id AND j.id>COALESCE(p_after_id,0)
        ORDER BY j.id LIMIT p_limit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        IF v_open THEN CLOSE report_cursor; END IF;
        RESIGNAL;
    END;

    IF p_limit IS NULL OR p_limit<1 OR p_limit>200 OR p_after_id<0 THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='Cursor limit must be 1..200 and after_id must be nonnegative.';
    END IF;
    SELECT u.role INTO v_role FROM users u WHERE u.id=p_company_id;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='Company account not found.';
    END IF;
    IF BINARY v_role<>'company' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Only companies can read a company vacancy report.';
    END IF;
    SET v_done=FALSE;
    OPEN report_cursor;
    SET v_open=TRUE;
    read_loop: LOOP
        FETCH report_cursor INTO v_id,v_title,v_location,v_type,v_status,v_currency,v_salary_min,v_salary_max,v_total,v_pending,v_accepted,v_rejected;
        IF v_done THEN LEAVE read_loop; END IF;
        SET v_report=JSON_ARRAY_APPEND(v_report,'$',JSON_OBJECT(
            'job_id',v_id+0,'title',v_title,'location',v_location,'type',v_type,'status',v_status,
            'currency',v_currency,'salary_min',v_salary_min+0,'salary_max',v_salary_max+0,
            'applications_total',v_total+0,'pending',v_pending+0,'accepted',v_accepted+0,'rejected',v_rejected+0));
        SET v_count=v_count+1;
        SET v_next=v_id;
    END LOOP;
    CLOSE report_cursor;
    SET v_open=FALSE;
    SELECT v_report AS report_json,v_count AS row_count,v_next AS next_after_id;
END$$

CREATE PROCEDURE ch_cursor_user_applications(IN p_user_id BIGINT UNSIGNED, IN p_after_id BIGINT, IN p_limit INT)
READS SQL DATA
SQL SECURITY INVOKER
BEGIN
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_open BOOLEAN DEFAULT FALSE;
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_id BIGINT UNSIGNED;
    DECLARE v_job BIGINT UNSIGNED;
    DECLARE v_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_job_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_company_id BIGINT UNSIGNED;
    DECLARE v_company_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_created DATETIME(6);
    DECLARE v_updated DATETIME(6);
    DECLARE v_history BIGINT UNSIGNED;
    DECLARE v_report LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '[]';
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_next BIGINT UNSIGNED DEFAULT NULL;
    DECLARE report_cursor CURSOR FOR
        SELECT a.id,j.id,j.title,j.status,j.user_id,COALESCE(NULLIF(p.company_name,''),publisher.name),
            a.status,a.created_at,a.updated_at,COALESCE(h.events,0)
        FROM applications a JOIN job_listings j ON j.id=a.job_listing_id
        LEFT JOIN users publisher ON publisher.id=j.user_id
        LEFT JOIN profiles p ON p.user_id=publisher.id
        LEFT JOIN (SELECT history.application_id,COUNT(*) AS events FROM application_status_history history GROUP BY history.application_id) h ON h.application_id=a.id
        WHERE a.user_id=p_user_id AND a.id>COALESCE(p_after_id,0)
        ORDER BY a.id LIMIT p_limit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        IF v_open THEN CLOSE report_cursor; END IF;
        RESIGNAL;
    END;

    IF p_limit IS NULL OR p_limit<1 OR p_limit>200 OR p_after_id<0 THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='Cursor limit must be 1..200 and after_id must be nonnegative.';
    END IF;
    SELECT u.role INTO v_role FROM users u WHERE u.id=p_user_id;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='User not found.';
    END IF;
    IF BINARY v_role<>'job_seeker' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Only job seekers can read their application report.';
    END IF;
    SET v_done=FALSE;
    OPEN report_cursor;
    SET v_open=TRUE;
    read_loop: LOOP
        FETCH report_cursor INTO v_id,v_job,v_title,v_job_status,v_company_id,v_company_name,v_status,v_created,v_updated,v_history;
        IF v_done THEN LEAVE read_loop; END IF;
        SET v_report=JSON_ARRAY_APPEND(v_report,'$',JSON_OBJECT(
            'application_id',v_id+0,'job_id',v_job+0,'title',v_title,'job_status',v_job_status,
            'company_id',v_company_id+0,'company_name',v_company_name,'status',v_status,
            'created_at',v_created,'updated_at',v_updated,'history_events',v_history+0));
        SET v_count=v_count+1;
        SET v_next=v_id;
    END LOOP;
    CLOSE report_cursor;
    SET v_open=FALSE;
    SELECT v_report AS report_json,v_count AS row_count,v_next AS next_after_id;
END$$

CREATE PROCEDURE ch_cursor_application_history(IN p_actor_id BIGINT UNSIGNED, IN p_application_id BIGINT UNSIGNED, IN p_after_id BIGINT, IN p_limit INT)
READS SQL DATA
SQL SECURITY INVOKER
BEGIN
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_open BOOLEAN DEFAULT FALSE;
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_applicant BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_publisher BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_id BIGINT UNSIGNED;
    DECLARE v_event VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_old VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_new VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_changed DATETIME(6);
    DECLARE v_report LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '[]';
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_next BIGINT UNSIGNED DEFAULT NULL;
    DECLARE report_cursor CURSOR FOR
        SELECT h.id,h.event_type,h.old_status,h.new_status,h.changed_at
        FROM application_status_history h JOIN applications a ON a.id=h.application_id
        JOIN job_listings j ON j.id=a.job_listing_id
        WHERE h.application_id=p_application_id AND h.id>COALESCE(p_after_id,0)
            AND ((BINARY v_role='job_seeker' AND a.user_id=p_actor_id) OR (BINARY v_role='company' AND j.user_id=p_actor_id))
        ORDER BY h.id LIMIT p_limit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        IF v_open THEN CLOSE report_cursor; END IF;
        RESIGNAL;
    END;

    IF p_limit IS NULL OR p_limit<1 OR p_limit>200 OR p_after_id<0 THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='Cursor limit must be 1..200 and after_id must be nonnegative.';
    END IF;
    SELECT u.role INTO v_role FROM users u WHERE u.id=p_actor_id;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='User not found.';
    END IF;
    SELECT a.user_id,j.user_id INTO v_applicant,v_publisher FROM applications a JOIN job_listings j ON j.id=a.job_listing_id WHERE a.id=p_application_id;
    IF v_applicant IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='Application not found.';
    END IF;
    IF (BINARY v_role='job_seeker' AND v_applicant<>p_actor_id)
        OR (BINARY v_role='company' AND (v_publisher IS NULL OR v_publisher<>p_actor_id))
        OR BINARY v_role NOT IN ('company','job_seeker') THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='This application history is not available to this user.';
    END IF;
    SET v_done=FALSE;
    OPEN report_cursor;
    SET v_open=TRUE;
    read_loop: LOOP
        FETCH report_cursor INTO v_id,v_event,v_old,v_new,v_changed;
        IF v_done THEN LEAVE read_loop; END IF;
        SET v_report=JSON_ARRAY_APPEND(v_report,'$',JSON_OBJECT(
            'history_id',v_id+0,'application_id',p_application_id+0,'event_type',v_event,
            'old_status',v_old,'new_status',v_new,'changed_at',v_changed));
        SET v_count=v_count+1;
        SET v_next=v_id;
    END LOOP;
    CLOSE report_cursor;
    SET v_open=FALSE;
    SELECT v_report AS report_json,v_count AS row_count,v_next AS next_after_id;
END$$

DELIMITER ;
