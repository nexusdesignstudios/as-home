-- ============================================================
-- SQL Query to Check Properties with Agreement Documents
-- ============================================================
-- This query shows which properties have documents saved
-- so you can check them in the admin panel
-- ============================================================

-- QUERY 1: Summary - Quick Overview
-- Shows total counts of properties with documents
SELECT 
    'SUMMARY' as report_type,
    COUNT(*) as total_properties,
    SUM(CASE WHEN identity_proof IS NOT NULL AND identity_proof != '' THEN 1 ELSE 0 END) as properties_with_identity_proof,
    SUM(CASE WHEN national_id_passport IS NOT NULL AND national_id_passport != '' THEN 1 ELSE 0 END) as properties_with_national_id,
    SUM(CASE WHEN utilities_bills IS NOT NULL AND utilities_bills != '' THEN 1 ELSE 0 END) as properties_with_utilities_bills,
    SUM(CASE WHEN power_of_attorney IS NOT NULL AND power_of_attorney != '' THEN 1 ELSE 0 END) as properties_with_power_of_attorney,
    SUM(CASE WHEN 
        (identity_proof IS NOT NULL AND identity_proof != '') OR
        (national_id_passport IS NOT NULL AND national_id_passport != '') OR
        (utilities_bills IS NOT NULL AND utilities_bills != '') OR
        (power_of_attorney IS NOT NULL AND power_of_attorney != '')
    THEN 1 ELSE 0 END) as properties_with_any_document,
    SUM(CASE WHEN 
        (identity_proof IS NOT NULL AND identity_proof != '') AND
        (national_id_passport IS NOT NULL AND national_id_passport != '') AND
        (utilities_bills IS NOT NULL AND utilities_bills != '') AND
        (power_of_attorney IS NOT NULL AND power_of_attorney != '')
    THEN 1 ELSE 0 END) as properties_with_all_4_documents
FROM propertys;

-- ============================================================
-- QUERY 2: DETAILED LIST - Properties with Documents
-- Use this to see all properties that have at least one document
-- ============================================================
SELECT 
    id as 'Property ID',
    title as 'Property Title',
    added_by as 'Owner ID',
    property_classification as 'Classification',
    CASE 
        WHEN property_classification = 1 THEN 'Sale'
        WHEN property_classification = 2 THEN 'Rent'
        WHEN property_classification = 3 THEN 'Vacation Home'
        WHEN property_classification = 4 THEN 'Apartment'
        WHEN property_classification = 5 THEN 'Hotel'
        ELSE 'Unknown'
    END as 'Classification Name',
    status as 'Status',
    request_status as 'Request Status',
    -- Document Status
    CASE WHEN identity_proof IS NOT NULL AND identity_proof != '' THEN '✅ YES' ELSE '❌ NO' END as 'Identity Proof',
    CASE WHEN national_id_passport IS NOT NULL AND national_id_passport != '' THEN '✅ YES' ELSE '❌ NO' END as 'National ID/Passport',
    CASE WHEN utilities_bills IS NOT NULL AND utilities_bills != '' THEN '✅ YES' ELSE '❌ NO' END as 'Utilities Bills',
    CASE WHEN power_of_attorney IS NOT NULL AND power_of_attorney != '' THEN '✅ YES' ELSE '❌ NO' END as 'Power of Attorney',
    -- Document Count
    (
        CASE WHEN identity_proof IS NOT NULL AND identity_proof != '' THEN 1 ELSE 0 END +
        CASE WHEN national_id_passport IS NOT NULL AND national_id_passport != '' THEN 1 ELSE 0 END +
        CASE WHEN utilities_bills IS NOT NULL AND utilities_bills != '' THEN 1 ELSE 0 END +
        CASE WHEN power_of_attorney IS NOT NULL AND power_of_attorney != '' THEN 1 ELSE 0 END
    ) as 'Documents Count',
    -- Admin Panel URL (for easy access)
    CONCAT('http://localhost:8000/property/', id, '/edit') as 'Admin Edit URL'
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') OR
    (national_id_passport IS NOT NULL AND national_id_passport != '') OR
    (utilities_bills IS NOT NULL AND utilities_bills != '') OR
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY 
    -- Sort by document count (most documents first)
    (
        CASE WHEN identity_proof IS NOT NULL AND identity_proof != '' THEN 1 ELSE 0 END +
        CASE WHEN national_id_passport IS NOT NULL AND national_id_passport != '' THEN 1 ELSE 0 END +
        CASE WHEN utilities_bills IS NOT NULL AND utilities_bills != '' THEN 1 ELSE 0 END +
        CASE WHEN power_of_attorney IS NOT NULL AND power_of_attorney != '' THEN 1 ELSE 0 END
    ) DESC,
    id DESC;

-- ============================================================
-- QUERY 3: Properties with ALL 4 Documents (Complete)
-- ============================================================
SELECT 
    id as 'Property ID',
    title as 'Property Title',
    added_by as 'Owner ID',
    property_classification as 'Classification',
    status as 'Status',
    request_status as 'Request Status',
    CONCAT('http://localhost:8000/property/', id, '/edit') as 'Admin Edit URL'
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') AND
    (national_id_passport IS NOT NULL AND national_id_passport != '') AND
    (utilities_bills IS NOT NULL AND utilities_bills != '') AND
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY id DESC;

-- ============================================================
-- QUERY 4: Properties Missing Specific Documents
-- ============================================================

-- Properties missing Identity Proof
SELECT 
    id as 'Property ID',
    title as 'Property Title',
    'Missing Identity Proof' as 'Missing Document',
    CONCAT('http://localhost:8000/property/', id, '/edit') as 'Admin Edit URL'
FROM propertys
WHERE (identity_proof IS NULL OR identity_proof = '')
    AND (
        (national_id_passport IS NOT NULL AND national_id_passport != '') OR
        (utilities_bills IS NOT NULL AND utilities_bills != '') OR
        (power_of_attorney IS NOT NULL AND power_of_attorney != '')
    )
ORDER BY id DESC;

-- Properties missing National ID/Passport
SELECT 
    id as 'Property ID',
    title as 'Property Title',
    'Missing National ID/Passport' as 'Missing Document',
    CONCAT('http://localhost:8000/property/', id, '/edit') as 'Admin Edit URL'
FROM propertys
WHERE (national_id_passport IS NULL OR national_id_passport = '')
    AND (
        (identity_proof IS NOT NULL AND identity_proof != '') OR
        (utilities_bills IS NOT NULL AND utilities_bills != '') OR
        (power_of_attorney IS NOT NULL AND power_of_attorney != '')
    )
ORDER BY id DESC;

-- Properties missing Utilities Bills
SELECT 
    id as 'Property ID',
    title as 'Property Title',
    'Missing Utilities Bills' as 'Missing Document',
    CONCAT('http://localhost:8000/property/', id, '/edit') as 'Admin Edit URL'
FROM propertys
WHERE (utilities_bills IS NULL OR utilities_bills = '')
    AND (
        (identity_proof IS NOT NULL AND identity_proof != '') OR
        (national_id_passport IS NOT NULL AND national_id_passport != '') OR
        (power_of_attorney IS NOT NULL AND power_of_attorney != '')
    )
ORDER BY id DESC;

-- Properties missing Power of Attorney
SELECT 
    id as 'Property ID',
    title as 'Property Title',
    'Missing Power of Attorney' as 'Missing Document',
    CONCAT('http://localhost:8000/property/', id, '/edit') as 'Admin Edit URL'
FROM propertys
WHERE (power_of_attorney IS NULL OR power_of_attorney = '')
    AND (
        (identity_proof IS NOT NULL AND identity_proof != '') OR
        (national_id_passport IS NOT NULL AND national_id_passport != '') OR
        (utilities_bills IS NOT NULL AND utilities_bills != '')
    )
ORDER BY id DESC;

-- ============================================================
-- QUERY 5: Quick Check - Just Property IDs with Documents
-- ============================================================
SELECT 
    id,
    title,
    CONCAT('http://localhost:8000/property/', id, '/edit') as 'Check Here'
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') OR
    (national_id_passport IS NOT NULL AND national_id_passport != '') OR
    (utilities_bills IS NOT NULL AND utilities_bills != '') OR
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY id DESC;

