-- Add seller_id column
ALTER TABLE feedback ADD COLUMN seller_id INT NULL AFTER customer_id;

-- Add foreign key constraint for seller_id
ALTER TABLE feedback ADD CONSTRAINT feedback_ibfk_2 FOREIGN KEY (seller_id) REFERENCES seller(SellerID) ON DELETE SET NULL;

-- Update existing seller feedback records
UPDATE feedback SET seller_id = customer_id WHERE feedback_source = 'seller';
UPDATE feedback SET customer_id = NULL WHERE feedback_source = 'seller'; 