-- Script para agregar todas las foreign keys faltantes

-- Foreign keys para purchases
ALTER TABLE purchases 
ADD CONSTRAINT fk_purchases_supplier 
FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE;

ALTER TABLE purchases 
ADD CONSTRAINT fk_purchases_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Foreign keys para purchase_items
ALTER TABLE purchase_items 
ADD CONSTRAINT fk_purchase_items_purchase 
FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE;

ALTER TABLE purchase_items 
ADD CONSTRAINT fk_purchase_items_product 
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- Foreign keys para quotes
ALTER TABLE quotes 
ADD CONSTRAINT fk_quotes_customer 
FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE;

ALTER TABLE quotes 
ADD CONSTRAINT fk_quotes_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Foreign keys para quote_items
ALTER TABLE quote_items 
ADD CONSTRAINT fk_quote_items_quote 
FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE;

ALTER TABLE quote_items 
ADD CONSTRAINT fk_quote_items_product 
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- Foreign keys para inventory_movements
ALTER TABLE inventory_movements 
ADD CONSTRAINT fk_inventory_movements_product 
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

ALTER TABLE inventory_movements 
ADD CONSTRAINT fk_inventory_movements_user 
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Índices para mejor rendimiento
CREATE INDEX idx_purchases_supplier ON purchases(supplier_id);
CREATE INDEX idx_purchases_user ON purchases(user_id);
CREATE INDEX idx_purchases_status ON purchases(status);
CREATE INDEX idx_purchases_date ON purchases(purchase_date);

CREATE INDEX idx_quotes_customer ON quotes(customer_id);
CREATE INDEX idx_quotes_user ON quotes(user_id);
CREATE INDEX idx_quotes_status ON quotes(status);
CREATE INDEX idx_quotes_date ON quotes(quote_date);

CREATE INDEX idx_purchase_items_purchase_product ON purchase_items(purchase_id, product_id);
CREATE INDEX idx_quote_items_quote_product ON quote_items(quote_id, product_id);
CREATE INDEX idx_inventory_movements_product ON inventory_movements(product_id);
CREATE INDEX idx_inventory_movements_type ON inventory_movements(type);