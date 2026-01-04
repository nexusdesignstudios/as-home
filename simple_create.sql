INSERT INTO reservations (customer_id, property_id, reservable_id, reservable_type, check_in_date, check_out_date, status, payment_method, payment_status, total_price, number_of_guests, transaction_id, created_at, updated_at) VALUES 
(1, 357, 1, 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 1000, 2, 'TEST_942', NOW(), NOW()),
(1, 357, 2, 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 1500, 2, 'TEST_945', NOW(), NOW()),
(1, 357, 3, 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 800, 1, 'TEST_946', NOW(), NOW()),
(1, 357, 4, 'App\\Models\\HotelRoom', '2026-01-23', '2026-01-24', 'confirmed', 'manual', 'unpaid', 800, 1, 'TEST_947', NOW(), NOW());
