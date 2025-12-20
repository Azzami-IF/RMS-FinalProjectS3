-- Extend foods.image_url to support longer CDN URLs (Edamam/etc)
ALTER TABLE foods MODIFY COLUMN image_url VARCHAR(1024) NULL;
