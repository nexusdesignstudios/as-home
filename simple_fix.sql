-- Simple SQL to fix the reservations table
-- Run this in your database

ALTER TABLE reservations 
ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255) NULL AFTER customer_id,
ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(255) NULL AFTER customer_name,
ADD COLUMN IF NOT EXISTS customer_email VARCHAR(255) NULL AFTER customer_phone,
ADD COLUMN IF NOT EXISTS review_url VARCHAR(255) NULL AFTER transaction_id,
ADD COLUMN IF NOT EXISTS approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER review_url,
ADD COLUMN IF NOT EXISTS requires_approval BOOLEAN DEFAULT FALSE AFTER approval_status,
ADD COLUMN IF NOT EXISTS booking_type VARCHAR(255) NULL AFTER requires_approval,
ADD COLUMN IF NOT EXISTS property_details JSON NULL AFTER booking_type,
ADD COLUMN IF NOT EXISTS reservable_data JSON NULL AFTER property_details;
