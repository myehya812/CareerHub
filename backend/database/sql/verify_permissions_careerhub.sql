-- CareerHub Step 6, 17 November 2026. Run separately as each database account.
-- Read-only; SHOW GRANTS for the current account does not expose password hashes on MySQL.
-- MariaDB can include authentication details in SHOW GRANTS: do not publish its output.
SELECT DATABASE() AS database_name, USER() AS connected_identity, CURRENT_USER() AS matched_account;
SHOW GRANTS;
-- These read operations are permitted to all three accounts.
SELECT id,name,role FROM users ORDER BY id LIMIT 5;
SELECT user_id,company_name FROM profiles ORDER BY user_id LIMIT 5;
SELECT ch_company_job_count(NULL) AS expected_zero;
-- With real IDs, trusted reporters can also CALL the three cursor reports.
-- Expected-denial writes and sensitive reads are tested in verify_database_access.php.
