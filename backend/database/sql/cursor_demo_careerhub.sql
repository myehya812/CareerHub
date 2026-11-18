-- CareerHub cursor demonstration, 17 November 2026.
-- Use a dedicated connection to a disposable migrated database.
-- Fixtures are rolled back; auto-increment counters may advance.
SET @cursor_demo_old_time_zone=@@session.time_zone;
SET SESSION time_zone='+00:00';
START TRANSACTION;
SET @fixture=REPLACE(UUID(),'-','');
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Cursor Demo Company',CONCAT('cursor-company-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','company',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @company_id=LAST_INSERT_ID();
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Cursor Demo Seeker',CONCAT('cursor-seeker-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','job_seeker',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @seeker_id=LAST_INSERT_ID();
CALL ch_create_job(@company_id,'SQL Developer','Cursor example.','Beirut','Full-Time',1500,2500,'USD','active');
SET @first_job_id=LAST_INSERT_ID();
CALL ch_submit_application(@seeker_id,@first_job_id);
SET @application_id=LAST_INSERT_ID();
CALL ch_update_application_status(@company_id,@application_id,'accepted');
CALL ch_create_job(@company_id,'Backend Developer','Cursor example.','Beirut','Contract',NULL,NULL,'USD','draft');
SET @last_job_id=LAST_INSERT_ID();
UPDATE job_listings SET status='closed' WHERE id=@first_job_id;

-- Two owned jobs, including closed/draft; the first has one accepted application.
CALL ch_cursor_company_jobs(@company_id,0,100);
-- Two pages of one row, then an empty page: no repeated final row.
CALL ch_cursor_company_jobs(@company_id,0,1);
CALL ch_cursor_company_jobs(@company_id,@first_job_id,1);
CALL ch_cursor_company_jobs(@company_id,@last_job_id,1);
-- One application, including the closed vacancy and two history events.
CALL ch_cursor_user_applications(@seeker_id,0,100);
-- The applicant and owning company can both read the same two events.
CALL ch_cursor_application_history(@seeker_id,@application_id,0,100);
CALL ch_cursor_application_history(@company_id,@application_id,0,100);
ROLLBACK;
SET SESSION time_zone=@cursor_demo_old_time_zone;
-- Expect zero for every fixture count after rollback.
SELECT (SELECT COUNT(*) FROM users WHERE id IN (@company_id,@seeker_id)) AS remaining_users,
       (SELECT COUNT(*) FROM job_listings WHERE user_id=@company_id) AS remaining_jobs,
       (SELECT COUNT(*) FROM applications WHERE user_id=@seeker_id) AS remaining_applications,
       (SELECT COUNT(*) FROM application_status_history WHERE application_id=@application_id) AS remaining_history;
