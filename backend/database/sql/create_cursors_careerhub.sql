-- CareerHub cursor reports, 17 November 2026.
-- These procedures read data and build bounded JSON in local variables only.
-- No recruitment data, temporary tables or transaction boundaries are changed.
-- after_id is the last emitted ID (NULL means zero); limit must be 1..200.

DELIMITER $$

CREATE PROCEDURE ch_cursor_company_jobs(IN p_company_id BIGINT UNSIGNED, IN p_after_id BIGINT, IN p_limit INT)
READS SQL DATA
SQL SECURITY INVOKER
BEGIN
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_open BOOLEAN DEFAULT FALSE;
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_id BIGINT UNSIGNED;
    DECLARE v_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_location VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_type VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_currency VARCHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_salary_min BIGINT UNSIGNED;
    DECLARE v_salary_max BIGINT UNSIGNED;
    DECLARE v_total BIGINT UNSIGNED;
    DECLARE v_pending BIGINT UNSIGNED;
    DECLARE v_accepted BIGINT UNSIGNED;
    DECLARE v_rejected BIGINT UNSIGNED;
    DECLARE v_report LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '[]';
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_next BIGINT UNSIGNED DEFAULT NULL;
    DECLARE report_cursor CURSOR FOR
        SELECT j.id,j.title,j.location,j.type,j.status,j.currency,j.salary_min,j.salary_max,
            COALESCE(s.total,0),COALESCE(s.pending,0),COALESCE(s.accepted,0),COALESCE(s.rejected,0)
        FROM job_listings j
        LEFT JOIN (SELECT a.job_listing_id,COUNT(*) AS total,
            SUM(a.status='pending') AS pending,SUM(a.status='accepted') AS accepted,SUM(a.status='rejected') AS rejected
            FROM applications a GROUP BY a.job_listing_id) s ON s.job_listing_id=j.id
        WHERE j.user_id=p_company_id AND j.id>COALESCE(p_after_id,0)
        ORDER BY j.id LIMIT p_limit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        IF v_open THEN CLOSE report_cursor; END IF;
        RESIGNAL;
    END;

    IF p_limit IS NULL OR p_limit<1 OR p_limit>200 OR p_after_id<0 THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='Cursor limit must be 1..200 and after_id must be nonnegative.';
    END IF;
    SELECT u.role INTO v_role FROM users u WHERE u.id=p_company_id;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='Company account not found.';
    END IF;
    IF BINARY v_role<>'company' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Only companies can read a company vacancy report.';
    END IF;
    SET v_done=FALSE;
    OPEN report_cursor;
    SET v_open=TRUE;
    read_loop: LOOP
        FETCH report_cursor INTO v_id,v_title,v_location,v_type,v_status,v_currency,v_salary_min,v_salary_max,v_total,v_pending,v_accepted,v_rejected;
        IF v_done THEN LEAVE read_loop; END IF;
        SET v_report=JSON_ARRAY_APPEND(v_report,'$',JSON_OBJECT(
            'job_id',v_id+0,'title',v_title,'location',v_location,'type',v_type,'status',v_status,
            'currency',v_currency,'salary_min',v_salary_min+0,'salary_max',v_salary_max+0,
            'applications_total',v_total+0,'pending',v_pending+0,'accepted',v_accepted+0,'rejected',v_rejected+0));
        SET v_count=v_count+1;
        SET v_next=v_id;
    END LOOP;
    CLOSE report_cursor;
    SET v_open=FALSE;
    SELECT v_report AS report_json,v_count AS row_count,v_next AS next_after_id;
END$$

CREATE PROCEDURE ch_cursor_user_applications(IN p_user_id BIGINT UNSIGNED, IN p_after_id BIGINT, IN p_limit INT)
READS SQL DATA
SQL SECURITY INVOKER
BEGIN
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_open BOOLEAN DEFAULT FALSE;
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_id BIGINT UNSIGNED;
    DECLARE v_job BIGINT UNSIGNED;
    DECLARE v_title VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_job_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_company_id BIGINT UNSIGNED;
    DECLARE v_company_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_status VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_created DATETIME(6);
    DECLARE v_updated DATETIME(6);
    DECLARE v_history BIGINT UNSIGNED;
    DECLARE v_report LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '[]';
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_next BIGINT UNSIGNED DEFAULT NULL;
    DECLARE report_cursor CURSOR FOR
        SELECT a.id,j.id,j.title,j.status,j.user_id,COALESCE(NULLIF(p.company_name,''),publisher.name),
            a.status,a.created_at,a.updated_at,COALESCE(h.events,0)
        FROM applications a JOIN job_listings j ON j.id=a.job_listing_id
        LEFT JOIN users publisher ON publisher.id=j.user_id
        LEFT JOIN profiles p ON p.user_id=publisher.id
        LEFT JOIN (SELECT history.application_id,COUNT(*) AS events FROM application_status_history history GROUP BY history.application_id) h ON h.application_id=a.id
        WHERE a.user_id=p_user_id AND a.id>COALESCE(p_after_id,0)
        ORDER BY a.id LIMIT p_limit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        IF v_open THEN CLOSE report_cursor; END IF;
        RESIGNAL;
    END;

    IF p_limit IS NULL OR p_limit<1 OR p_limit>200 OR p_after_id<0 THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='Cursor limit must be 1..200 and after_id must be nonnegative.';
    END IF;
    SELECT u.role INTO v_role FROM users u WHERE u.id=p_user_id;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='User not found.';
    END IF;
    IF BINARY v_role<>'job_seeker' THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='Only job seekers can read their application report.';
    END IF;
    SET v_done=FALSE;
    OPEN report_cursor;
    SET v_open=TRUE;
    read_loop: LOOP
        FETCH report_cursor INTO v_id,v_job,v_title,v_job_status,v_company_id,v_company_name,v_status,v_created,v_updated,v_history;
        IF v_done THEN LEAVE read_loop; END IF;
        SET v_report=JSON_ARRAY_APPEND(v_report,'$',JSON_OBJECT(
            'application_id',v_id+0,'job_id',v_job+0,'title',v_title,'job_status',v_job_status,
            'company_id',v_company_id+0,'company_name',v_company_name,'status',v_status,
            'created_at',v_created,'updated_at',v_updated,'history_events',v_history+0));
        SET v_count=v_count+1;
        SET v_next=v_id;
    END LOOP;
    CLOSE report_cursor;
    SET v_open=FALSE;
    SELECT v_report AS report_json,v_count AS row_count,v_next AS next_after_id;
END$$

CREATE PROCEDURE ch_cursor_application_history(IN p_actor_id BIGINT UNSIGNED, IN p_application_id BIGINT UNSIGNED, IN p_after_id BIGINT, IN p_limit INT)
READS SQL DATA
SQL SECURITY INVOKER
BEGIN
    DECLARE v_done BOOLEAN DEFAULT FALSE;
    DECLARE v_open BOOLEAN DEFAULT FALSE;
    DECLARE v_role VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
    DECLARE v_applicant BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_publisher BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_id BIGINT UNSIGNED;
    DECLARE v_event VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_old VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_new VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    DECLARE v_changed DATETIME(6);
    DECLARE v_report LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '[]';
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_next BIGINT UNSIGNED DEFAULT NULL;
    DECLARE report_cursor CURSOR FOR
        SELECT h.id,h.event_type,h.old_status,h.new_status,h.changed_at
        FROM application_status_history h JOIN applications a ON a.id=h.application_id
        JOIN job_listings j ON j.id=a.job_listing_id
        WHERE h.application_id=p_application_id AND h.id>COALESCE(p_after_id,0)
            AND ((BINARY v_role='job_seeker' AND a.user_id=p_actor_id) OR (BINARY v_role='company' AND j.user_id=p_actor_id))
        ORDER BY h.id LIMIT p_limit;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done=TRUE;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        IF v_open THEN CLOSE report_cursor; END IF;
        RESIGNAL;
    END;

    IF p_limit IS NULL OR p_limit<1 OR p_limit>200 OR p_after_id<0 THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30004, MESSAGE_TEXT='Cursor limit must be 1..200 and after_id must be nonnegative.';
    END IF;
    SELECT u.role INTO v_role FROM users u WHERE u.id=p_actor_id;
    IF v_role IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='User not found.';
    END IF;
    SELECT a.user_id,j.user_id INTO v_applicant,v_publisher FROM applications a JOIN job_listings j ON j.id=a.job_listing_id WHERE a.id=p_application_id;
    IF v_applicant IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30001, MESSAGE_TEXT='Application not found.';
    END IF;
    IF (BINARY v_role='job_seeker' AND v_applicant<>p_actor_id)
        OR (BINARY v_role='company' AND (v_publisher IS NULL OR v_publisher<>p_actor_id))
        OR BINARY v_role NOT IN ('company','job_seeker') THEN
        SIGNAL SQLSTATE '45000' SET MYSQL_ERRNO=30002, MESSAGE_TEXT='This application history is not available to this user.';
    END IF;
    SET v_done=FALSE;
    OPEN report_cursor;
    SET v_open=TRUE;
    read_loop: LOOP
        FETCH report_cursor INTO v_id,v_event,v_old,v_new,v_changed;
        IF v_done THEN LEAVE read_loop; END IF;
        SET v_report=JSON_ARRAY_APPEND(v_report,'$',JSON_OBJECT(
            'history_id',v_id+0,'application_id',p_application_id+0,'event_type',v_event,
            'old_status',v_old,'new_status',v_new,'changed_at',v_changed));
        SET v_count=v_count+1;
        SET v_next=v_id;
    END LOOP;
    CLOSE report_cursor;
    SET v_open=FALSE;
    SELECT v_report AS report_json,v_count AS row_count,v_next AS next_after_id;
END$$

DELIMITER ;
