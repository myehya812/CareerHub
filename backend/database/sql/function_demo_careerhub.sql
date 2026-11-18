-- CareerHub function demonstration, 17 November 2026.
-- Use a dedicated connection to a disposable migrated database.
-- All fixtures are rolled back. Auto-increment counters can advance.

START TRANSACTION;
SET @fixture=REPLACE(UUID(),'-','');
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Function Demo Company',CONCAT('company-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','company',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @company_id=LAST_INSERT_ID();
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Other Demo Company',CONCAT('other-company-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','company',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @other_company_id=LAST_INSERT_ID();
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Function Demo Seeker',CONCAT('seeker-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','job_seeker',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @seeker_id=LAST_INSERT_ID();
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Other Demo Seeker',CONCAT('other-seeker-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','job_seeker',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @other_seeker_id=LAST_INSERT_ID();

CALL ch_create_job(@company_id,'SQL Developer','Function example.','Beirut','Full-Time',1500,2500,'USD','active');
SET @job_id=LAST_INSERT_ID();
CALL ch_create_job(@company_id,'Backend Developer','Function example.','Beirut','Full-Time',1500,2500,'USD','active');
SET @closing_job_id=LAST_INSERT_ID();
CALL ch_create_job(@other_company_id,'Data Analyst','Other company fixture.','Tripoli','Full-Time',1500,2500,'USD','active');
SET @other_job_id=LAST_INSERT_ID();

CALL ch_submit_application(@seeker_id,@job_id);
CALL ch_submit_application(@other_seeker_id,@job_id);
CALL ch_submit_application(@seeker_id,@closing_job_id);
SET @accepted_application_id=LAST_INSERT_ID();
CALL ch_update_application_status(@company_id,@accepted_application_id,'accepted');
UPDATE job_listings SET status='closed',updated_at=UTC_TIMESTAMP() WHERE id=@closing_job_id;
CALL ch_submit_application(@seeker_id,@other_job_id);
SET @rejected_application_id=LAST_INSERT_ID();
CALL ch_update_application_status(@other_company_id,@rejected_application_id,'rejected');
CALL ch_save_job(@seeker_id,@job_id);
CALL ch_save_job(@seeker_id,@other_job_id);

-- Expected values: 3, 2, 2, 1, 3, 2.
-- Multiple history entries and a closed job do not multiply or hide applications.
SELECT ch_user_application_count(@seeker_id) AS user_applications_expected_3,
       ch_user_saved_job_count(@seeker_id) AS saved_jobs_expected_2,
       ch_company_job_count(@company_id) AS company_jobs_expected_2,
       ch_company_active_job_count(@company_id) AS active_jobs_expected_1,
       ch_company_application_count(@company_id) AS company_applications_expected_3,
       ch_job_application_count(@job_id) AS job_applications_expected_2;

-- The other company is counted independently: expect 1 and 1.
SELECT ch_company_job_count(@other_company_id) AS other_company_jobs_expected_1,
       ch_company_application_count(@other_company_id) AS other_company_applications_expected_1;

SELECT ch_user_application_count(NULL) AS null_user_expected_0,
       ch_company_job_count(NULL) AS null_company_expected_0,
       ch_job_application_count(NULL) AS null_job_expected_0;

ROLLBACK;

-- Expected values: zero in every column after rollback.
SELECT ch_user_application_count(@seeker_id) AS remaining_applications,
       ch_user_saved_job_count(@seeker_id) AS remaining_saved_jobs,
       ch_company_job_count(@company_id) AS remaining_jobs,
       ch_company_active_job_count(@company_id) AS remaining_active_jobs,
       ch_company_application_count(@company_id) AS remaining_company_applications,
       ch_job_application_count(@job_id) AS remaining_job_applications;
