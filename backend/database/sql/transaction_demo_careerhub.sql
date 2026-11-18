-- CareerHub transaction demonstration, 17 November 2026.
-- Run AFTER installing the procedures, in a disposable demonstration database.
-- Use a dedicated SQL connection with no existing transaction.
-- All fixture rows are rolled back; auto-increment counters can still advance.
-- If a CALL reports an error in an interactive client, issue ROLLBACK immediately.

START TRANSACTION;
SET @fixture = REPLACE(UUID(), '-', '');

-- These placeholder accounts are SQL fixtures, not usable application logins.
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Transaction Demo Company',CONCAT('company-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','company',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @company_id = LAST_INSERT_ID();
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Transaction Demo Seeker',CONCAT('seeker-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','job_seeker',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @seeker_id = LAST_INSERT_ID();

CALL ch_create_job(@company_id,'SQL Developer','CareerHub transaction example.','Beirut','Full-Time',1500,2500,'USD','active');
SET @job_id = LAST_INSERT_ID();
CALL ch_submit_application(@seeker_id,@job_id);
SET @application_id = LAST_INSERT_ID();
CALL ch_save_job(@seeker_id,@job_id);
CALL ch_update_application_status(@company_id,@application_id,'accepted');

-- Expect one row in each table, with application status accepted.
SELECT (SELECT COUNT(*) FROM job_listings WHERE id=@job_id) AS jobs_in_transaction,
       (SELECT COUNT(*) FROM applications WHERE id=@application_id) AS applications_in_transaction,
       (SELECT COUNT(*) FROM saved_jobs WHERE user_id=@seeker_id AND job_listing_id=@job_id) AS bookmarks_in_transaction;
SELECT id,user_id,job_listing_id,status FROM applications WHERE id=@application_id;

ROLLBACK;

-- Expect all zero. User accounts, job, application and bookmark were undone together.
SELECT (SELECT COUNT(*) FROM users WHERE id IN (@company_id,@seeker_id)) AS remaining_demo_users,
       (SELECT COUNT(*) FROM job_listings WHERE id=@job_id) AS remaining_demo_jobs,
       (SELECT COUNT(*) FROM applications WHERE id=@application_id) AS remaining_demo_applications,
       (SELECT COUNT(*) FROM saved_jobs WHERE user_id=@seeker_id AND job_listing_id=@job_id) AS remaining_demo_bookmarks;
