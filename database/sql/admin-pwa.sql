ALTER TABLE orders
 ADD COLUMN IF NOT EXISTS origin VARCHAR(16) NOT NULL DEFAULT 'site',
 ADD COLUMN IF NOT EXISTS payment_status VARCHAR(16) NOT NULL DEFAULT 'pending',
 ADD COLUMN IF NOT EXISTS shipping_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
 ADD COLUMN IF NOT EXISTS revision INT UNSIGNED NOT NULL DEFAULT 1;
CREATE TABLE IF NOT EXISTS order_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 admin_id INT UNSIGNED NULL,
 message VARCHAR(255) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY history_order (order_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
UPDATE orders SET payment_status='paid',status=CASE status WHEN 'paid' THEN 'confirmed' WHEN 'completed' THEN 'delivered' ELSE status END WHERE status IN ('paid','completed');
UPDATE orders SET payment_status='paid' WHERE status='shipped' AND NOT EXISTS (SELECT 1 FROM order_history h WHERE h.order_id=orders.id);
INSERT INTO order_history(order_id,message) SELECT id,'Pedido anterior ao SØLID Admin; status preservado na migração' FROM orders WHERE NOT EXISTS (SELECT 1 FROM order_history h WHERE h.order_id=orders.id);
