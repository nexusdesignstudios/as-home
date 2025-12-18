-- ============================================================
-- COPY AND PASTE THIS INTO phpMyAdmin
-- ============================================================
-- This query shows all properties that have documents saved
-- You can click the admin URL to check them directly
-- ============================================================

SELECT 
    id AS 'Property ID',
    title AS 'Property Title',
    added_by AS 'Owner ID',
    CASE 
        WHEN property_classification = 1 THEN 'Sale'
        WHEN property_classification = 2 THEN 'Rent'
        WHEN property_classification = 3 THEN 'Vacation Home'
        WHEN property_classification = 4 THEN 'Apartment'
        WHEN property_classification = 5 THEN 'Hotel'
        ELSE 'Unknown'
    END AS 'Type',
    CASE 
        WHEN status = 1 THEN 'Active'
        ELSE 'Inactive'
    END AS 'Status',
    request_status AS 'Request Status',
    -- Document Status with visual indicators
    IF(identity_proof IS NOT NULL AND identity_proof != '', 'YES ✓', 'NO ✗') AS 'Identity Proof',
    IF(national_id_passport IS NOT NULL AND national_id_passport != '', 'YES ✓', 'NO ✗') AS 'National ID/Passport',
    IF(utilities_bills IS NOT NULL AND utilities_bills != '', 'YES ✓', 'NO ✗') AS 'Utilities Bills',
    IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 'YES ✓', 'NO ✗') AS 'Power of Attorney',
    -- Document Count
    (
        IF(identity_proof IS NOT NULL AND identity_proof != '', 1, 0) +
        IF(national_id_passport IS NOT NULL AND national_id_passport != '', 1, 0) +
        IF(utilities_bills IS NOT NULL AND utilities_bills != '', 1, 0) +
        IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 1, 0)
    ) AS 'Total Docs',
    -- Admin Panel Link (you can click this in phpMyAdmin results)
    CONCAT('http://localhost:8000/property/', id, '/edit') AS 'Admin Edit Link'
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') OR
    (national_id_passport IS NOT NULL AND national_id_passport != '') OR
    (utilities_bills IS NOT NULL AND utilities_bills != '') OR
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY 
    -- Sort by most documents first, then by ID
    (
        IF(identity_proof IS NOT NULL AND identity_proof != '', 1, 0) +
        IF(national_id_passport IS NOT NULL AND national_id_passport != '', 1, 0) +
        IF(utilities_bills IS NOT NULL AND utilities_bills != '', 1, 0) +
        IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 1, 0)
    ) DESC,
    id DESC;

-- ============================================================
-- BONUS: Summary Statistics Query
-- Run this first to see overall statistics
-- ============================================================
SELECT 
    COUNT(*) AS 'Total Properties',
    SUM(IF(identity_proof IS NOT NULL AND identity_proof != '', 1, 0)) AS 'With Identity Proof',
    SUM(IF(national_id_passport IS NOT NULL AND national_id_passport != '', 1, 0)) AS 'With National ID',
    SUM(IF(utilities_bills IS NOT NULL AND utilities_bills != '', 1, 0)) AS 'With Utilities Bills',
    SUM(IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 1, 0)) AS 'With Power of Attorney',
    SUM(IF(
        (identity_proof IS NOT NULL AND identity_proof != '') OR
        (national_id_passport IS NOT NULL AND national_id_passport != '') OR
        (utilities_bills IS NOT NULL AND utilities_bills != '') OR
        (power_of_attorney IS NOT NULL AND power_of_attorney != ''),
        1, 0
    )) AS 'Properties with Any Document',
    SUM(IF(
        (identity_proof IS NOT NULL AND identity_proof != '') AND
        (national_id_passport IS NOT NULL AND national_id_passport != '') AND
        (utilities_bills IS NOT NULL AND utilities_bills != '') AND
        (power_of_attorney IS NOT NULL AND power_of_attorney != ''),
        1, 0
    )) AS 'Properties with ALL 4 Documents'
FROM propertys;

