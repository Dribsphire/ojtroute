ALTER TABLE workplace_change_requests
ADD COLUMN reviewed_by INT NULL,
ADD COLUMN reviewed_at TIMESTAMP NULL;
