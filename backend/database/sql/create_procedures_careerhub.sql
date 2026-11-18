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
