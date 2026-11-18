-- Run after PresentationDemoSeeder, with its database selected.
-- Read-only examples using email lookups; auto-increment IDs are not assumed.

SELECT DATABASE() AS selected_database;
SET @company_id=(SELECT id FROM users WHERE email='company1@careerhub.test');
SET @seeker_id=(SELECT id FROM users WHERE email='user1@careerhub.test');
SET @application_id=(SELECT a.id FROM applications a
    JOIN job_listings j ON j.id=a.job_listing_id
    WHERE a.user_id=@seeker_id AND j.user_id=@company_id ORDER BY a.id LIMIT 1);
SET @job_id=(SELECT job_listing_id FROM applications WHERE id=@application_id);

SELECT @company_id AS company_id,@seeker_id AS seeker_id,@application_id AS application_id,@job_id AS job_id;

-- Fresh dataset: 5 applications, 3 bookmarks, 5 company jobs, 3 active jobs,
-- 5 company applications and 1 application for the selected closed vacancy.
SELECT ch_user_application_count(@seeker_id) AS user_applications,
       ch_user_saved_job_count(@seeker_id) AS saved_jobs,
       ch_company_job_count(@company_id) AS company_jobs,
       ch_company_active_job_count(@company_id) AS active_company_jobs,
       ch_company_application_count(@company_id) AS company_applications,
       ch_job_application_count(@job_id) AS selected_job_applications;

-- The fresh application is accepted and has created/status_changed events.
SELECT event_type,old_status,new_status,changed_at FROM application_status_history
WHERE application_id=@application_id ORDER BY id;

-- Fresh dataset row counts: 5, 5, 2. Results may change after website actions.
CALL ch_cursor_company_jobs(@company_id,0,100);
CALL ch_cursor_user_applications(@seeker_id,0,100);
CALL ch_cursor_application_history(@seeker_id,@application_id,0,100);
