-- CareerHub trigger demonstration, 17 November 2026.
-- Use a dedicated connection to a disposable migrated database.
-- Writes are rolled back; auto-increment counters can advance.

START TRANSACTION;
SET @fixture=REPLACE(UUID(),'-','');
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Trigger Demo Company',CONCAT('company-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','company',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @company_id=LAST_INSERT_ID();
INSERT INTO users (name,email,password,role,created_at,updated_at)
VALUES ('Trigger Demo Seeker',CONCAT('seeker-',@fixture,'@example.test'),'DEMO_ONLY_NO_LOGIN','job_seeker',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @seeker_id=LAST_INSERT_ID();
CALL ch_create_job(@company_id,'SQL Developer','CareerHub trigger example.','Beirut','Full-Time',1500,2500,'USD','active');
SET @job_id=LAST_INSERT_ID();

-- Direct SQL still invokes triggers; no controller or status procedure is used.
INSERT INTO applications (user_id,job_listing_id,status,created_at,updated_at)
VALUES (@seeker_id,@job_id,'pending',UTC_TIMESTAMP(),UTC_TIMESTAMP());
SET @application_id=LAST_INSERT_ID();
UPDATE applications SET status='accepted',updated_at=UTC_TIMESTAMP() WHERE id=@application_id;
UPDATE applications SET status='accepted',updated_at=UTC_TIMESTAMP() WHERE id=@application_id;

-- Expect exactly two rows: created NULL->pending and status_changed pending->accepted.
SELECT application_id,event_type,old_status,new_status,changed_at
FROM application_status_history WHERE application_id=@application_id ORDER BY id;
SELECT COUNT(*) AS history_events_expected_2 FROM application_status_history WHERE application_id=@application_id;
ROLLBACK;

-- Expect all zero; history is part of the same InnoDB transaction.
SELECT (SELECT COUNT(*) FROM users WHERE id IN (@company_id,@seeker_id)) AS remaining_demo_users,
       (SELECT COUNT(*) FROM job_listings WHERE id=@job_id) AS remaining_demo_jobs,
       (SELECT COUNT(*) FROM applications WHERE id=@application_id) AS remaining_demo_applications,
       (SELECT COUNT(*) FROM application_status_history WHERE application_id=@application_id) AS remaining_demo_history;
