-- ============================================================
-- SQL Query to Check Properties with Documents in Database
-- ============================================================
-- Run this in phpMyAdmin to see which properties have documents saved
-- ============================================================

-- Check all properties with any agreement documents
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
    status AS 'Status',
    -- Show actual file names stored in database
    identity_proof AS 'Identity Proof File',
    national_id_passport AS 'National ID File',
    utilities_bills AS 'Utilities Bills File',
    power_of_attorney AS 'Power of Attorney File',
    -- Document status
    IF(identity_proof IS NOT NULL AND identity_proof != '', 'YES', 'NO') AS 'Has Identity Proof',
    IF(national_id_passport IS NOT NULL AND national_id_passport != '', 'YES', 'NO') AS 'Has National ID',
    IF(utilities_bills IS NOT NULL AND utilities_bills != '', 'YES', 'NO') AS 'Has Utilities Bills',
    IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 'YES', 'NO') AS 'Has Power of Attorney',
    -- Admin Panel URL
    CONCAT('http://localhost:8000/property/', id, '/edit') AS 'Admin Edit URL'
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') OR
    (national_id_passport IS NOT NULL AND national_id_passport != '') OR
    (utilities_bills IS NOT NULL AND utilities_bills != '') OR
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY id DESC;

-- ============================================================
-- Summary Statistics
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
    )) AS 'Properties with Any Document'
FROM propertys;

