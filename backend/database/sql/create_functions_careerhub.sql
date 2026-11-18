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
