-- ============================================================
-- QUICK CHECK: Properties with Documents Saved
-- ============================================================
-- Run this query to see which properties have documents
-- Copy the "Admin Edit URL" to check them in admin panel
-- ============================================================

SELECT 
    id as 'Property ID',
    title as 'Property Title',
    added_by as 'Owner ID',
    CASE 
        WHEN property_classification = 1 THEN 'Sale'
        WHEN property_classification = 2 THEN 'Rent'
        WHEN property_classification = 3 THEN 'Vacation Home'
        WHEN property_classification = 4 THEN 'Apartment'
        WHEN property_classification = 5 THEN 'Hotel'
        ELSE 'Unknown'
    END as 'Type',
    status as 'Status',
    -- Document Status
    IF(identity_proof IS NOT NULL AND identity_proof != '', '✅', '❌') as 'ID Proof',
    IF(national_id_passport IS NOT NULL AND national_id_passport != '', '✅', '❌') as 'National ID',
    IF(utilities_bills IS NOT NULL AND utilities_bills != '', '✅', '❌') as 'Utilities',
    IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', '✅', '❌') as 'POA',
    -- Document Count
    (
        IF(identity_proof IS NOT NULL AND identity_proof != '', 1, 0) +
        IF(national_id_passport IS NOT NULL AND national_id_passport != '', 1, 0) +
        IF(utilities_bills IS NOT NULL AND utilities_bills != '', 1, 0) +
        IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 1, 0)
    ) as 'Docs',
    -- Admin Panel Link
    CONCAT('http://localhost:8000/property/', id, '/edit') as '👉 CHECK HERE'
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') OR
    (national_id_passport IS NOT NULL AND national_id_passport != '') OR
    (utilities_bills IS NOT NULL AND utilities_bills != '') OR
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY 
    -- Most documents first
    (
        IF(identity_proof IS NOT NULL AND identity_proof != '', 1, 0) +
        IF(national_id_passport IS NOT NULL AND national_id_passport != '', 1, 0) +
        IF(utilities_bills IS NOT NULL AND utilities_bills != '', 1, 0) +
        IF(power_of_attorney IS NOT NULL AND power_of_attorney != '', 1, 0)
    ) DESC,
    id DESC;

