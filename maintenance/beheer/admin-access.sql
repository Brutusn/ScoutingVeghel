-- Apply once, first locally, later explicitly on test. Never upload this file.
ALTER TABLE verhuur_administrators
 ADD COLUMN rol ENUM('beheer','verhuur') NOT NULL DEFAULT 'beheer',
 ADD COLUMN mfa_secret VARCHAR(32) NULL,
 ADD COLUMN mfa_last BIGINT NOT NULL DEFAULT -1,
 ADD COLUMN mfa_recovery TEXT NULL;
CREATE TABLE beheer_mail_templates (
 naam VARCHAR(80) PRIMARY KEY,
 inhoud TEXT NOT NULL
) CHARACTER SET utf8mb4;
