-- Create test reservations for Amazing 4 Star Hotel

INSERT INTO reservations (customer_id, property_id, reservable_id, reservable_type, check_in_date, check_out_date, status, payment_method, payment_status, total_price, number_of_guests, transaction_id, created_at, updated_at) VALUES 
(1, 357, (SELECT id FROM hotel_rooms WHERE property_id = 357 AND room_number = 767 LIMIT 1), 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 1000, 2, 'TEST_942', NOW(), NOW()),

(1, 357, (SELECT id FROM hotel_rooms WHERE property_id = 357 AND room_number = 763 AND id != (SELECT id FROM hotel_rooms WHERE property_id = 357 AND room_number = 767 LIMIT 1) LIMIT 1), 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 1000, 2, 'TEST_945', NOW(), NOW()),

(1, 357, (SELECT id FROM hotel_rooms WHERE property_id = 357 AND room_number = 768 LIMIT 1), 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 800, 1, 'TEST_946', NOW(), NOW()),

(1, 357, (SELECT id FROM hotel_rooms WHERE property_id = 357 AND room_number = 769 LIMIT 1), 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 800, 1, 'TEST_947', NOW(), NOW());
