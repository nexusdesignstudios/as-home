-- SQL Query to Find Reservation for Customer neclancer.eg@gmail.com
-- Checking out TODAY

-- Find reservation checking out today with that email
SELECT 
    r.id AS reservation_id,
    r.customer_id,
    r.property_id,
    r.check_out_date,
    r.status,
    r.feedback_token,
    r.feedback_email_sent_at,
    c.name AS customer_name,
    c.email AS customer_email
FROM reservations r
INNER JOIN customers c ON r.customer_id = c.id
WHERE c.email = 'neclancer.eg@gmail.com'
  AND DATE(r.check_out_date) = CURDATE()
  AND r.status IN ('confirmed', 'approved', 'completed')
ORDER BY r.check_out_date DESC
LIMIT 10;

-- If you want to find ALL reservations for this email (not just today)
-- Uncomment the query below:
/*
SELECT 
    r.id AS reservation_id,
    r.customer_id,
    r.property_id,
    r.check_out_date,
    r.status,
    r.feedback_token,
    r.feedback_email_sent_at,
    c.name AS customer_name,
    c.email AS customer_email
FROM reservations r
INNER JOIN customers c ON r.customer_id = c.id
WHERE c.email = 'neclancer.eg@gmail.com'
ORDER BY r.check_out_date DESC;
*/

