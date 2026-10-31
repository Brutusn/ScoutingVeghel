-- Run once on the TEST database, before uploading the new administration code.
-- Existing passwords are retained until the first successful login, then cleared.
ALTER TABLE `verhuur_administrators`
    ADD COLUMN `pass_hash` VARCHAR(255) NULL DEFAULT NULL;
