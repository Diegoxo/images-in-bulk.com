-- Update script to add credits to the users table
USE images_in_bulk;
ALTER TABLE users ADD COLUMN credits INT DEFAULT 0;
-- Also update existing pro users to have 50000 credits (assuming they just started)
UPDATE users u 
INNER JOIN subscriptions s ON u.id = s.user_id 
SET u.credits = 50000 
WHERE s.plan_type = 'pro' AND s.status = 'active';
