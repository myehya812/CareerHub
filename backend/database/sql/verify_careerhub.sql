-- CareerHub verification through Step 5, 17 November 2026.
-- Run against the database selected in your MySQL/MariaDB client.
-- Read-only: this file does not modify accounts, data, tables or permissions.
-- This uses the GitHub application's schema, not the earlier nine-table design.

SELECT DATABASE() AS database_name, VERSION() AS server_version;

SELECT
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM job_listings) AS job_listings,
    (SELECT COUNT(*) FROM profiles) AS profiles,
    (SELECT COUNT(*) FROM applications) AS applications,
    (SELECT COUNT(*) FROM saved_jobs) AS saved_jobs,
    (SELECT COUNT(*) FROM application_status_history) AS history_events;

-- Every violation count below should be zero.
SELECT 'duplicate_emails' AS check_name, COUNT(*) AS violations
FROM (SELECT email FROM users GROUP BY email HAVING COUNT(*) > 1) AS duplicates
UNION ALL
SELECT 'duplicate_profiles', COUNT(*)
FROM (SELECT user_id FROM profiles GROUP BY user_id HAVING COUNT(*) > 1) AS duplicates
UNION ALL
SELECT 'duplicate_applications', COUNT(*)
FROM (SELECT user_id, job_listing_id FROM applications GROUP BY user_id, job_listing_id HAVING COUNT(*) > 1) AS duplicates
UNION ALL
SELECT 'duplicate_saved_jobs', COUNT(*)
FROM (SELECT user_id, job_listing_id FROM saved_jobs GROUP BY user_id, job_listing_id HAVING COUNT(*) > 1) AS duplicates
UNION ALL
SELECT 'orphan_profiles', COUNT(*) FROM profiles p LEFT JOIN users u ON u.id = p.user_id WHERE u.id IS NULL
UNION ALL
SELECT 'orphan_job_owners', COUNT(*) FROM job_listings j LEFT JOIN users u ON u.id = j.user_id WHERE j.user_id IS NOT NULL AND u.id IS NULL
UNION ALL
SELECT 'orphan_applications', COUNT(*) FROM applications a LEFT JOIN users u ON u.id = a.user_id LEFT JOIN job_listings j ON j.id = a.job_listing_id WHERE u.id IS NULL OR j.id IS NULL
UNION ALL
SELECT 'orphan_saved_jobs', COUNT(*) FROM saved_jobs s LEFT JOIN users u ON u.id = s.user_id LEFT JOIN job_listings j ON j.id = s.job_listing_id WHERE u.id IS NULL OR j.id IS NULL
UNION ALL
SELECT 'invalid_roles', COUNT(*) FROM users WHERE BINARY role NOT IN ('job_seeker', 'company')
UNION ALL
SELECT 'invalid_job_types', COUNT(*) FROM job_listings WHERE BINARY type NOT IN ('Full-Time', 'Part-Time', 'Internship', 'Contract')
UNION ALL
SELECT 'invalid_job_statuses', COUNT(*) FROM job_listings WHERE BINARY status NOT IN ('active', 'draft', 'closed')
UNION ALL
SELECT 'invalid_application_statuses', COUNT(*) FROM applications WHERE BINARY status NOT IN ('pending', 'accepted', 'rejected')
UNION ALL
SELECT 'invalid_salary_ranges', COUNT(*) FROM job_listings WHERE salary_min < 0 OR salary_max < 0 OR salary_min > salary_max
UNION ALL
SELECT 'non_company_job_owners', COUNT(*) FROM job_listings j JOIN users u ON u.id = j.user_id WHERE u.role <> 'company'
UNION ALL
SELECT 'non_seeker_applicants', COUNT(*) FROM applications a JOIN users u ON u.id = a.user_id WHERE u.role <> 'job_seeker'
UNION ALL
SELECT 'non_seeker_bookmarks', COUNT(*) FROM saved_jobs s JOIN users u ON u.id = s.user_id WHERE u.role <> 'job_seeker';

-- Nullable legacy job owners are allowed by the existing migration.
-- Review these separately; do not invent an employer or delete real vacancies.
SELECT id, title FROM job_listings WHERE user_id IS NULL;

SELECT status, COUNT(*) AS total FROM applications GROUP BY status ORDER BY status;

SELECT a.id AS application_id, u.name AS applicant, j.title, c.name AS company, a.status
FROM applications a
JOIN users u ON u.id = a.user_id
JOIN job_listings j ON j.id = a.job_listing_id
LEFT JOIN users c ON c.id = j.user_id
ORDER BY a.id;

-- Column nullability, keys, indexes and constraints can be inspected without DDL.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('users', 'job_listings', 'profiles', 'applications', 'saved_jobs')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('users', 'job_listings', 'profiles', 'applications', 'saved_jobs')
ORDER BY TABLE_NAME, CONSTRAINT_TYPE, CONSTRAINT_NAME;

-- Through Step 2, expect six public procedures plus the transaction guard.
SELECT ROUTINE_NAME, ROUTINE_TYPE, SECURITY_TYPE
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE()
  AND ROUTINE_NAME IN ('ch_require_transaction','ch_create_job','ch_submit_application',
      'ch_update_application_status','ch_save_job','ch_remove_saved_job','ch_delete_job')
ORDER BY ROUTINE_NAME;

-- Through Step 3, expect nine triggers, including two AFTER history writers.
SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE, ACTION_TIMING, EVENT_MANIPULATION
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE()
  AND TRIGGER_NAME IN ('ch_job_before_insert','ch_job_before_update',
      'ch_application_before_insert','ch_application_before_update',
      'ch_application_after_insert','ch_application_after_update',
      'ch_saved_job_before_insert','ch_saved_job_before_update','ch_user_before_update')
ORDER BY TRIGGER_NAME;

-- Every history violation count should also be zero.
SELECT 'applications_without_history' AS check_name, COUNT(*) AS violations
FROM applications a LEFT JOIN application_status_history h ON h.application_id=a.id
WHERE h.id IS NULL
UNION ALL
SELECT 'latest_history_status_mismatches', COUNT(*)
FROM applications a JOIN application_status_history h
  ON h.id=(SELECT MAX(last_event.id) FROM application_status_history last_event WHERE last_event.application_id=a.id)
WHERE BINARY h.new_status <> BINARY a.status
UNION ALL
SELECT 'history_chain_errors', COUNT(*)
FROM application_status_history h
LEFT JOIN application_status_history previous
  ON previous.id=(SELECT MAX(prior.id) FROM application_status_history prior WHERE prior.application_id=h.application_id AND prior.id<h.id)
WHERE (previous.id IS NULL AND BINARY h.event_type NOT IN ('baseline','created'))
   OR (previous.id IS NOT NULL AND (BINARY h.event_type <> 'status_changed' OR NOT (BINARY h.old_status <=> BINARY previous.new_status)));

SELECT h.application_id, h.event_type, h.old_status, h.new_status, h.changed_at
FROM application_status_history h ORDER BY h.application_id,h.id;

-- Through Step 4, expect six read-only, non-deterministic reporting functions.
SELECT ROUTINE_NAME, ROUTINE_TYPE, SECURITY_TYPE, IS_DETERMINISTIC, SQL_DATA_ACCESS
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA=DATABASE()
  AND ROUTINE_NAME IN ('ch_user_application_count','ch_user_saved_job_count',
      'ch_company_job_count','ch_company_active_job_count','ch_company_application_count','ch_job_application_count')
ORDER BY ROUTINE_NAME;

-- Compare function results with independent grouped data. Expect zero mismatches.
SELECT 'user_application_function_mismatches' AS check_name, COUNT(*) AS violations
FROM users u LEFT JOIN (SELECT user_id,COUNT(*) AS total FROM applications GROUP BY user_id) counts ON counts.user_id=u.id
WHERE ch_user_application_count(u.id)<>COALESCE(counts.total,0)
UNION ALL
SELECT 'user_saved_job_function_mismatches', COUNT(*)
FROM users u LEFT JOIN (SELECT user_id,COUNT(*) AS total FROM saved_jobs GROUP BY user_id) counts ON counts.user_id=u.id
WHERE ch_user_saved_job_count(u.id)<>COALESCE(counts.total,0)
UNION ALL
SELECT 'company_job_function_mismatches', COUNT(*)
FROM users u LEFT JOIN (SELECT user_id,COUNT(*) AS total FROM job_listings GROUP BY user_id) counts ON counts.user_id=u.id
WHERE ch_company_job_count(u.id)<>COALESCE(counts.total,0)
UNION ALL
SELECT 'company_active_job_function_mismatches', COUNT(*)
FROM users u LEFT JOIN (SELECT user_id,COUNT(*) AS total FROM job_listings WHERE BINARY status='active' GROUP BY user_id) counts ON counts.user_id=u.id
WHERE ch_company_active_job_count(u.id)<>COALESCE(counts.total,0)
UNION ALL
SELECT 'company_application_function_mismatches', COUNT(*)
FROM users u LEFT JOIN (SELECT j.user_id,COUNT(a.id) AS total FROM job_listings j LEFT JOIN applications a ON a.job_listing_id=j.id GROUP BY j.user_id) counts ON counts.user_id=u.id
WHERE ch_company_application_count(u.id)<>COALESCE(counts.total,0)
UNION ALL
SELECT 'job_application_function_mismatches', COUNT(*)
FROM job_listings j LEFT JOIN (SELECT job_listing_id,COUNT(*) AS total FROM applications GROUP BY job_listing_id) counts ON counts.job_listing_id=j.id
WHERE ch_job_application_count(j.id)<>COALESCE(counts.total,0);

-- Through Step 5, expect three cursor procedures with READS SQL DATA / INVOKER.
SELECT ROUTINE_NAME, ROUTINE_TYPE, SECURITY_TYPE, SQL_DATA_ACCESS
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA=DATABASE()
  AND ROUTINE_NAME IN ('ch_cursor_company_jobs','ch_cursor_user_applications','ch_cursor_application_history')
ORDER BY ROUTINE_NAME;
