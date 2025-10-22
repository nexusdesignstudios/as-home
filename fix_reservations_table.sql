-- SQL script to add missing columns to reservations table
-- Run this script in your database to fix the 500 error

-- Check if columns already exist before adding them
SET @sql = '';

-- Check for customer_name column
SELECT COUNT(*) INTO @customer_name_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'customer_name';

-- Check for customer_phone column  
SELECT COUNT(*) INTO @customer_phone_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'customer_phone';

-- Check for customer_email column
SELECT COUNT(*) INTO @customer_email_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'customer_email';

-- Check for review_url column
SELECT COUNT(*) INTO @review_url_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'review_url';

-- Check for approval_status column
SELECT COUNT(*) INTO @approval_status_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'approval_status';

-- Check for requires_approval column
SELECT COUNT(*) INTO @requires_approval_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'requires_approval';

-- Check for booking_type column
SELECT COUNT(*) INTO @booking_type_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'booking_type';

-- Check for property_details column
SELECT COUNT(*) INTO @property_details_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'property_details';

-- Check for reservable_data column
SELECT COUNT(*) INTO @reservable_data_exists 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'reservations' 
AND COLUMN_NAME = 'reservable_data';

-- Build ALTER TABLE statement with only missing columns
SET @sql = 'ALTER TABLE reservations ';

-- Add customer_name if missing
IF @customer_name_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN customer_name VARCHAR(255) NULL AFTER customer_id, ');
END IF;

-- Add customer_phone if missing
IF @customer_phone_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN customer_phone VARCHAR(255) NULL AFTER customer_name, ');
END IF;

-- Add customer_email if missing
IF @customer_email_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN customer_email VARCHAR(255) NULL AFTER customer_phone, ');
END IF;

-- Add review_url if missing
IF @review_url_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN review_url VARCHAR(255) NULL AFTER transaction_id, ');
END IF;

-- Add approval_status if missing
IF @approval_status_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN approval_status ENUM(\'pending\', \'approved\', \'rejected\') DEFAULT \'pending\' AFTER review_url, ');
END IF;

-- Add requires_approval if missing
IF @requires_approval_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN requires_approval BOOLEAN DEFAULT FALSE AFTER approval_status, ');
END IF;

-- Add booking_type if missing
IF @booking_type_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN booking_type VARCHAR(255) NULL AFTER requires_approval, ');
END IF;

-- Add property_details if missing
IF @property_details_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN property_details JSON NULL AFTER booking_type, ');
END IF;

-- Add reservable_data if missing
IF @reservable_data_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ADD COLUMN reservable_data JSON NULL AFTER property_details');
END IF;

-- Remove trailing comma if present
SET @sql = TRIM(TRAILING ', ' FROM @sql);

-- Execute the ALTER TABLE statement if there are columns to add
IF LENGTH(@sql) > LENGTH('ALTER TABLE reservations') THEN
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    SELECT 'Migration completed successfully! Missing columns have been added to the reservations table.' as result;
ELSE
    SELECT 'All columns already exist. No migration needed.' as result;
END IF;
