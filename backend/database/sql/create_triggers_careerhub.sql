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
