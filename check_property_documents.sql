-- SQL Query to check which properties have agreement documents saved in database
-- Run this query in your database to see all properties with documents

-- Summary Statistics
SELECT 
    COUNT(*) as total_properties,
    SUM(CASE WHEN identity_proof IS NOT NULL AND identity_proof != '' THEN 1 ELSE 0 END) as has_identity_proof,
    SUM(CASE WHEN national_id_passport IS NOT NULL AND national_id_passport != '' THEN 1 ELSE 0 END) as has_national_id,
    SUM(CASE WHEN utilities_bills IS NOT NULL AND utilities_bills != '' THEN 1 ELSE 0 END) as has_utilities_bills,
    SUM(CASE WHEN power_of_attorney IS NOT NULL AND power_of_attorney != '' THEN 1 ELSE 0 END) as has_power_of_attorney,
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
    THEN 1 ELSE 0 END) as properties_with_all_documents
FROM propertys;

-- Detailed List: Properties with at least one document
SELECT 
    id,
    title,
    added_by as owner_id,
    property_classification,
    status,
    request_status,
    CASE WHEN identity_proof IS NOT NULL AND identity_proof != '' THEN 'Yes' ELSE 'No' END as has_identity_proof,
    CASE WHEN national_id_passport IS NOT NULL AND national_id_passport != '' THEN 'Yes' ELSE 'No' END as has_national_id,
    CASE WHEN utilities_bills IS NOT NULL AND utilities_bills != '' THEN 'Yes' ELSE 'No' END as has_utilities_bills,
    CASE WHEN power_of_attorney IS NOT NULL AND power_of_attorney != '' THEN 'Yes' ELSE 'No' END as has_power_of_attorney,
    (
        CASE WHEN identity_proof IS NOT NULL AND identity_proof != '' THEN 1 ELSE 0 END +
        CASE WHEN national_id_passport IS NOT NULL AND national_id_passport != '' THEN 1 ELSE 0 END +
        CASE WHEN utilities_bills IS NOT NULL AND utilities_bills != '' THEN 1 ELSE 0 END +
        CASE WHEN power_of_attorney IS NOT NULL AND power_of_attorney != '' THEN 1 ELSE 0 END
    ) as documents_count,
    identity_proof,
    national_id_passport,
    utilities_bills,
    power_of_attorney
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') OR
    (national_id_passport IS NOT NULL AND national_id_passport != '') OR
    (utilities_bills IS NOT NULL AND utilities_bills != '') OR
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY documents_count DESC, id DESC;

-- Properties with ALL 4 documents
SELECT 
    id,
    title,
    added_by as owner_id,
    property_classification,
    status,
    request_status,
    identity_proof,
    national_id_passport,
    utilities_bills,
    power_of_attorney
FROM propertys
WHERE 
    (identity_proof IS NOT NULL AND identity_proof != '') AND
    (national_id_passport IS NOT NULL AND national_id_passport != '') AND
    (utilities_bills IS NOT NULL AND utilities_bills != '') AND
    (power_of_attorney IS NOT NULL AND power_of_attorney != '')
ORDER BY id DESC;

-- Properties with NO documents
SELECT 
    id,
    title,
    added_by as owner_id,
    property_classification,
    status,
    request_status
FROM propertys
WHERE 
    (identity_proof IS NULL OR identity_proof = '') AND
    (national_id_passport IS NULL OR national_id_passport = '') AND
    (utilities_bills IS NULL OR utilities_bills = '') AND
    (power_of_attorney IS NULL OR power_of_attorney = '')
ORDER BY id DESC;

