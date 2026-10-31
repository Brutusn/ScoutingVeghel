-- Run once after admin-access.sql; first locally, later explicitly on test.
ALTER TABLE verhuur_administrators
 MODIFY COLUMN rol VARCHAR(64) NOT NULL DEFAULT 'beheer',
 ADD COLUMN auth_version INT UNSIGNED NOT NULL DEFAULT 0,
 ENGINE=InnoDB;
CREATE TABLE beheer_access_groups (
 code VARCHAR(64) PRIMARY KEY,
 naam VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB CHARACTER SET utf8mb4;
CREATE TABLE beheer_access_permissions (
 groep VARCHAR(64) NOT NULL,
 module VARCHAR(64) NOT NULL,
 PRIMARY KEY (groep,module),
 FOREIGN KEY (groep) REFERENCES beheer_access_groups(code) ON DELETE CASCADE
) ENGINE=InnoDB CHARACTER SET utf8mb4;
INSERT INTO beheer_access_groups VALUES ('beheer','Beheer'),('verhuur','Verhuur');
INSERT INTO beheer_access_permissions (groep,module) VALUES
 ('beheer','reserveringen'),('beheer','kalender'),('beheer','zoeken'),('beheer','toevoegen'),
 ('beheer','maillog'),('beheer','mailsjablonen'),('beheer','contact-sjablonen'),
 ('beheer','onderhoud'),('beheer','eigen-groepen'),('beheer','accounts'),
 ('verhuur','reserveringen'),('verhuur','kalender'),('verhuur','zoeken'),('verhuur','toevoegen'),
 ('verhuur','maillog'),('verhuur','mailsjablonen');
