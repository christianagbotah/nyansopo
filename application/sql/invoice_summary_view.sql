-- Create invoice summary view for consolidated invoice display
CREATE OR REPLACE VIEW invoice_summary AS
SELECT 
    i.student_id,
    i.invoice_code,
    i.year,
    i.term,
    s.name as student_name,
    s.student_code,
    c.name as class_name,
    SUM(i.amount) as total_amount,
    SUM(i.amount_paid) as total_paid,
    SUM(i.due) as total_due,
    COALESCE(SUM(d.discount_amount), 0) as total_discount,
    COUNT(DISTINCT i.invoice_id) as item_count,
    MIN(i.creation_timestamp) as invoice_date,
    MAX(i.payment_timestamp) as last_payment_date,
    CASE 
        WHEN SUM(i.due) = 0 THEN 'paid'
        WHEN SUM(i.amount_paid) > 0 THEN 'partial'
        ELSE 'unpaid'
    END as payment_status
FROM invoice i
LEFT JOIN student s ON i.student_id = s.student_id
LEFT JOIN enroll e ON s.student_id = e.student_id AND i.year = e.year AND i.term = e.term
LEFT JOIN class c ON e.class_id = c.class_id
LEFT JOIN invoice_discounts d ON i.student_id = d.student_id AND i.invoice_code = d.invoice_code
GROUP BY i.student_id, i.invoice_code, i.year, i.term, s.name, s.student_code, c.name;
