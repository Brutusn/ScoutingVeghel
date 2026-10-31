-- Independent beheer schema and procedures. Back up first; run on the shared test database.
-- Existing rental data is retained. SQL SECURITY INVOKER avoids hosting-specific definers.
SET @beheer_old_sql_mode = @@SESSION.sql_mode;
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode,'NO_ZERO_IN_DATE',''),'NO_ZERO_DATE','');
CREATE TABLE IF NOT EXISTS beheer_settings (
 naam VARCHAR(100) NOT NULL PRIMARY KEY, waarde TEXT NOT NULL,
 soort VARCHAR(20) NOT NULL DEFAULT 'text', omschrijving VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE verhuur_administrators ENGINE=InnoDB;
ALTER TABLE verhuur_eigen_groep ENGINE=InnoDB;
ALTER TABLE verhuur_huurder ENGINE=InnoDB;
ALTER TABLE verhuur_mutaties ENGINE=InnoDB;
ALTER TABLE verhuur_notes ENGINE=InnoDB;
ALTER TABLE verhuur_reservering ENGINE=InnoDB;
ALTER TABLE verhuur_status ENGINE=InnoDB;
ALTER TABLE verhuur_verhuring ENGINE=InnoDB;
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('MIN_AANTAL_PERSONEN','20','number','Min aantal personen');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('MAX_AANTAL_PERSONEN','60','number','Max aantal personen');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('MIN_AANTAL_UREN','0','number','Min aantal uren');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('MAX_AANTAL_UREN','8','number','Max aantal uren');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('MIN_AANTAL_OVERNACHTINGEN','0','number','Min aantal overnachtingen');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('MAX_AANTAL_OVERNACHTINGEN','14','number','Max aantal overnachtingen');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('KOSTEN_PPPN','5.00','number','Kosten pppn');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('KOSTEN_PER_UUR','10','number','Kosten per uur');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('NUMBER_OF_DAYS_OPTION_IS_VALID','14','number','Number of days option is valid');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('BEDRAG_BORG_DAGDEEL','50','number','Bedrag borg dagdeel');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('BEDRAG_BORG_NACHT','75','number','Bedrag borg nacht');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('BEDRAG_BORG_LANGER','300','number','Bedrag borg langer');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_VERHUURDER_NAME','','text','Hv verhuurder name');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_VERHUURDER_REP','','text','Hv verhuurder rep');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_VERHUURDER_ADDRESS','','text','Hv verhuurder address');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_VERHUURDER_POSTAL','','text','Hv verhuurder postal');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_VERHUURDER_PHONE','','text','Hv verhuurder phone');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_BEHEERDER','','text','Hv beheerder');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_BEHEERDER_PHONE','','text','Hv beheerder phone');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('HV_VERHUURDER_ACCOUNT','','text','Hv verhuurder account');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('DATETIME_FORMAT','%Y-%m-%d %T','text','Datetime format');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('PUBLIC_WEBSITE_URL','/','text','Public website url');
INSERT IGNORE INTO beheer_settings(naam,waarde,soort,omschrijving) VALUES ('MAINTENANCE_PREVIEW_MINUTES','30','number','Maintenance preview minutes');
DELIMITER $$
DROP PROCEDURE IF EXISTS beheer_settings_get$$
CREATE PROCEDURE beheer_settings_get()
SQL SECURITY INVOKER
BEGIN
SELECT naam,waarde,soort,omschrijving FROM beheer_settings ORDER BY naam;
END$$
DROP PROCEDURE IF EXISTS beheer_settings_set$$
CREATE PROCEDURE beheer_settings_set(IN p_key VARCHAR(100), IN p_value TEXT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
IF NOT EXISTS(SELECT 1 FROM beheer_settings WHERE naam=p_key) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Onbekende instelling'; END IF; UPDATE beheer_settings SET waarde=p_value WHERE naam=p_key; INSERT INTO verhuur_mutaties(hoort_bij,naam,actie,datum,`query`) VALUES(0,p_actor,'INSTELLING',NOW(),p_key); SELECT ROW_COUNT() AS updated;
END$$
DROP PROCEDURE IF EXISTS beheer_admin_get$$
CREATE PROCEDURE beheer_admin_get(IN p_name VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
SELECT naam,pass,pass_hash FROM verhuur_administrators WHERE naam=p_name LIMIT 1;
END$$
DROP PROCEDURE IF EXISTS beheer_admin_hash_upgrade$$
CREATE PROCEDURE beheer_admin_hash_upgrade(IN p_name VARCHAR(255), IN p_old VARCHAR(255), IN p_hash VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
UPDATE verhuur_administrators SET pass_hash=p_hash,pass='' WHERE naam=p_name AND (pass_hash <=> p_old); SELECT ROW_COUNT() AS updated;
END$$
DROP PROCEDURE IF EXISTS beheer_admin_touch$$
CREATE PROCEDURE beheer_admin_touch(IN p_name VARCHAR(255), IN p_ip VARCHAR(64))
SQL SECURITY INVOKER
BEGIN
UPDATE verhuur_administrators SET ip=p_ip,activity=NOW() WHERE naam=p_name;
END$$
DROP PROCEDURE IF EXISTS beheer_status_counts$$
CREATE PROCEDURE beheer_status_counts()
SQL SECURITY INVOKER
BEGIN
SELECT s.status,s.status_id,COUNT(r.id) AS `sum` FROM verhuur_status s LEFT JOIN verhuur_reservering r ON r.status_id=s.status_id WHERE s.status_id<21 GROUP BY s.status_id,s.status ORDER BY s.status_id;
END$$
DROP PROCEDURE IF EXISTS beheer_status_list$$
CREATE PROCEDURE beheer_status_list()
SQL SECURITY INVOKER
BEGIN
SELECT status_id,status FROM verhuur_status WHERE status_id<21 ORDER BY status_id;
END$$
DROP PROCEDURE IF EXISTS beheer_status_get$$
CREATE PROCEDURE beheer_status_get(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT status_id,status FROM verhuur_status WHERE status_id=p_id;
END$$
DROP PROCEDURE IF EXISTS beheer_groups$$
CREATE PROCEDURE beheer_groups()
SQL SECURITY INVOKER
BEGIN
SELECT DISTINCT h.id,h.naam FROM verhuur_eigen_groep g JOIN verhuur_huurder h ON h.id=g.huurder_id ORDER BY h.naam,h.id;
END$$
DROP PROCEDURE IF EXISTS beheer_tenants_list$$
CREATE PROCEDURE beheer_tenants_list()
SQL SECURITY INVOKER
BEGIN
SELECT * FROM verhuur_huurder ORDER BY naam,id;
END$$
DROP PROCEDURE IF EXISTS beheer_tenant_get$$
CREATE PROCEDURE beheer_tenant_get(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM verhuur_huurder WHERE id=p_id;
END$$
DROP PROCEDURE IF EXISTS beheer_tenant_create$$
CREATE PROCEDURE beheer_tenant_create(IN p_name TEXT, IN p_contact TEXT, IN p_address TEXT, IN p_postcode TEXT, IN p_city TEXT, IN p_email TEXT, IN p_phone TEXT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
IF TRIM(p_name)='' OR TRIM(p_email)='' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Naam en e-mailadres zijn verplicht'; END IF; INSERT INTO verhuur_huurder(naam,contactpersoon,adres,postcode,plaats,email,telefoon) VALUES(p_name,p_contact,p_address,p_postcode,p_city,p_email,p_phone); SELECT LAST_INSERT_ID() AS id;
END$$
DROP PROCEDURE IF EXISTS beheer_rental_get$$
CREATE PROCEDURE beheer_rental_get(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT h.id AS huurder_id,h.contactpersoon,h.naam,h.adres,h.postcode,h.plaats,h.email,h.telefoon,
r.id AS reservering_id,r.begindatum,r.einddatum,DATE_FORMAT(r.begindatum,'%Y-%m-%d %T') AS display_begindatum,DATE_FORMAT(r.einddatum,'%Y-%m-%d %T') AS display_einddatum,
r.personen,r.beschrijving,v.id,v.datum,DATE_FORMAT(v.datum,'%Y-%m-%d %T') AS display_datum,v.groep,s.status,v.confirm
FROM verhuur_verhuring v LEFT JOIN verhuur_reservering r ON r.id=v.reservering_id LEFT JOIN verhuur_huurder h ON h.id=v.huurder_id LEFT JOIN verhuur_status s ON s.status_id=r.status_id WHERE v.id=p_id LIMIT 1;
END$$
DROP PROCEDURE IF EXISTS beheer_rental_list$$
CREATE PROCEDURE beheer_rental_list(IN p_status INT)
SQL SECURITY INVOKER
BEGIN
SELECT v.id,h.naam,h.contactpersoon,r.beschrijving,DATE_FORMAT(v.datum,'%Y-%m-%d %T') AS DatumVanReservering FROM verhuur_verhuring v LEFT JOIN verhuur_reservering r ON r.id=v.reservering_id LEFT JOIN verhuur_huurder h ON h.id=v.huurder_id WHERE r.status_id=p_status AND h.naam<>'Scouting Veghel' ORDER BY v.datum DESC,v.id DESC;
END$$
DROP PROCEDURE IF EXISTS beheer_rental_search$$
CREATE PROCEDURE beheer_rental_search(IN p_id INT, IN p_tenant INT, IN p_group TEXT)
SQL SECURITY INVOKER
BEGIN
SELECT v.id,h.naam,r.beschrijving,DATE_FORMAT(v.datum,'%Y-%m-%d %T') AS DatumVanReservering FROM verhuur_verhuring v LEFT JOIN verhuur_reservering r ON r.id=v.reservering_id LEFT JOIN verhuur_huurder h ON h.id=v.huurder_id WHERE r.status_id<20 AND (p_id<0 OR v.id=p_id) AND (p_tenant<0 OR h.id=p_tenant) AND (p_group='' OR v.groep=p_group) ORDER BY v.datum DESC,v.id DESC;
END$$
DROP PROCEDURE IF EXISTS beheer_calendar$$
CREATE PROCEDURE beheer_calendar(IN p_start DATETIME, IN p_end DATETIME, IN p_active BOOLEAN)
SQL SECURITY INVOKER
BEGIN
SELECT v.id,h.naam,v.groep,r.begindatum,r.einddatum,DATE_FORMAT(r.begindatum,'%Y-%m-%d %T') AS begindatum_print,DATE_FORMAT(r.einddatum,'%Y-%m-%d %T') AS einddatum_print,s.status,DATE_FORMAT(v.datum,'%Y-%m-%d %T') AS datum FROM verhuur_verhuring v LEFT JOIN verhuur_reservering r ON r.id=v.reservering_id LEFT JOIN verhuur_huurder h ON h.id=v.huurder_id LEFT JOIN verhuur_status s ON s.status_id=r.status_id WHERE r.begindatum<=p_end AND r.einddatum>=p_start AND (NOT p_active OR r.status_id<10) ORDER BY r.begindatum,r.einddatum,v.id;
END$$
DROP PROCEDURE IF EXISTS beheer_notes$$
CREATE PROCEDURE beheer_notes(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM verhuur_notes WHERE hoort_bij=p_id ORDER BY id DESC;
END$$
DROP PROCEDURE IF EXISTS beheer_note_add$$
CREATE PROCEDURE beheer_note_add(IN p_id INT, IN p_text TEXT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
IF NOT EXISTS(SELECT 1 FROM verhuur_verhuring WHERE id=p_id) OR TRIM(p_text)='' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige opmerking'; END IF; INSERT INTO verhuur_notes(hoort_bij,opmerking,schrijver,datum) VALUES(p_id,p_text,p_actor,NOW());
END$$
DROP PROCEDURE IF EXISTS beheer_audit$$
CREATE PROCEDURE beheer_audit(IN p_id INT)
SQL SECURITY INVOKER
BEGIN
SELECT id,hoort_bij,naam,actie,datum FROM verhuur_mutaties WHERE hoort_bij=p_id ORDER BY id DESC;
END$$
DROP PROCEDURE IF EXISTS beheer_audit_add$$
CREATE PROCEDURE beheer_audit_add(IN p_id INT, IN p_action TEXT, IN p_summary TEXT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
INSERT INTO verhuur_mutaties(hoort_bij,naam,actie,datum,`query`) VALUES(p_id,p_actor,p_action,NOW(),p_summary);
END$$
DROP PROCEDURE IF EXISTS beheer_rental_tenant_set$$
CREATE PROCEDURE beheer_rental_tenant_set(IN p_id INT, IN p_name TEXT, IN p_contact TEXT, IN p_address TEXT, IN p_postcode TEXT, IN p_city TEXT, IN p_email TEXT, IN p_phone TEXT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
UPDATE verhuur_huurder h JOIN verhuur_verhuring v ON v.huurder_id=h.id SET h.naam=p_name,h.contactpersoon=p_contact,h.adres=p_address,h.postcode=p_postcode,h.plaats=p_city,h.email=p_email,h.telefoon=p_phone WHERE v.id=p_id; CALL beheer_audit_add(p_id,'HUURDER GEWIJZIGD','Huurdergegevens aangepast',p_actor);
END$$
DROP PROCEDURE IF EXISTS beheer_rental_status_set$$
CREATE PROCEDURE beheer_rental_status_set(IN p_id INT, IN p_status INT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
IF NOT EXISTS(SELECT 1 FROM verhuur_status WHERE status_id=p_status AND status_id<21) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige status'; END IF; UPDATE verhuur_reservering r JOIN verhuur_verhuring v ON v.reservering_id=r.id SET r.status_id=p_status WHERE v.id=p_id; CALL beheer_audit_add(p_id,'STATUS GEWIJZIGD',CAST(p_status AS CHAR),p_actor);
END$$
DROP PROCEDURE IF EXISTS beheer_rental_reservation_set$$
CREATE PROCEDURE beheer_rental_reservation_set(IN p_id INT, IN p_start DATETIME, IN p_end DATETIME, IN p_people INT, IN p_text TEXT, IN p_group TEXT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
DECLARE v_old TEXT;
IF p_start>=p_end OR p_people<1 OR p_people>10000 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige periode of aantal personen'; END IF;
SELECT groep INTO v_old FROM verhuur_verhuring WHERE id=p_id;
IF p_group<>'' AND NOT(p_group <=> v_old) AND NOT EXISTS(SELECT 1 FROM verhuur_eigen_groep g JOIN verhuur_huurder h ON h.id=g.huurder_id WHERE h.naam=p_group) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Kies een bestaande eigen groep'; END IF;
UPDATE verhuur_reservering r JOIN verhuur_verhuring v ON v.reservering_id=r.id SET r.begindatum=p_start,r.einddatum=p_end,r.personen=p_people,r.beschrijving=p_text WHERE v.id=p_id;
UPDATE verhuur_verhuring SET groep=NULLIF(p_group,'') WHERE id=p_id;
CALL beheer_audit_add(p_id,'RESERVERING GEWIJZIGD','Reserveringsgegevens aangepast',p_actor);
END$$
DROP PROCEDURE IF EXISTS beheer_rentals_create$$
CREATE PROCEDURE beheer_rentals_create(IN p_tenant INT, IN p_slots LONGTEXT, IN p_people INT, IN p_text TEXT, IN p_status INT, IN p_group TEXT, IN p_actor VARCHAR(255))
SQL SECURITY INVOKER
BEGIN
DECLARE v_index INT DEFAULT 0; DECLARE v_count INT; DECLARE v_res INT; DECLARE v_rental INT;
DECLARE v_start DATETIME; DECLARE v_end DATETIME; DECLARE v_own TEXT DEFAULT NULL; DECLARE v_ids LONGTEXT DEFAULT '[]';
DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
IF NOT JSON_VALID(p_slots) OR JSON_TYPE(p_slots)<>'ARRAY' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige perioden'; END IF;
SET v_count=JSON_LENGTH(p_slots);
IF v_count<1 OR v_count>100 OR p_people<1 OR p_people>10000 OR TRIM(p_text)='' OR NOT EXISTS(SELECT 1 FROM verhuur_huurder WHERE id=p_tenant) OR NOT EXISTS(SELECT 1 FROM verhuur_status WHERE status_id=p_status AND status_id<21) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige aanvraag'; END IF;
SELECT h.naam INTO v_own FROM verhuur_eigen_groep g JOIN verhuur_huurder h ON h.id=g.huurder_id WHERE h.id=p_tenant LIMIT 1;
IF v_own IS NULL AND COALESCE(p_group,'')<>'' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Groep hoort niet bij huurder'; END IF;
START TRANSACTION;
WHILE v_index<v_count DO
 SET v_start=CAST(JSON_UNQUOTE(JSON_EXTRACT(p_slots,CONCAT('$[',v_index,'].start'))) AS DATETIME);
 SET v_end=CAST(JSON_UNQUOTE(JSON_EXTRACT(p_slots,CONCAT('$[',v_index,'].end'))) AS DATETIME);
 IF v_start IS NULL OR v_end IS NULL OR v_start>=v_end THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige periode'; END IF;
 INSERT INTO verhuur_reservering(beschrijving,begindatum,einddatum,personen,status_id) VALUES(p_text,v_start,v_end,p_people,p_status); SET v_res=LAST_INSERT_ID();
 INSERT INTO verhuur_verhuring(huurder_id,reservering_id,datum,confirm,groep) VALUES(p_tenant,v_res,NOW(),SHA2(CONCAT(UUID(),RAND()),256),v_own); SET v_rental=LAST_INSERT_ID();
 CALL beheer_audit_add(v_rental,'TOEGEVOEGD','Nieuwe verhuring via beheer',p_actor);
 SET v_ids=JSON_ARRAY_APPEND(v_ids,'$',v_rental); SET v_index=v_index+1;
END WHILE;
COMMIT; SELECT v_count AS aantal,v_ids AS ids;
END$$
DROP PROCEDURE IF EXISTS beheer_maintenance_logs$$
CREATE PROCEDURE beheer_maintenance_logs(IN p_cutoff DATE)
SQL SECURITY INVOKER
BEGIN
SELECT * FROM verhuur_mutaties WHERE datum>'1000-01-01' AND datum<p_cutoff ORDER BY id;
END$$
DROP PROCEDURE IF EXISTS beheer_maintenance_rentals$$
CREATE PROCEDURE beheer_maintenance_rentals(IN p_scope VARCHAR(32), IN p_cutoff DATE)
SQL SECURITY INVOKER
BEGIN
SELECT v.* FROM verhuur_verhuring v JOIN verhuur_reservering r ON r.id=v.reservering_id WHERE ((p_scope='completed' AND r.status_id=8) OR (p_scope='cancelled' AND r.status_id IN(10,11,19,20,21))) AND r.einddatum>'1000-01-01' AND r.einddatum<p_cutoff ORDER BY v.id;
END$$
DROP PROCEDURE IF EXISTS beheer_maintenance_tenants$$
CREATE PROCEDURE beheer_maintenance_tenants(IN p_scope VARCHAR(32), IN p_cutoff DATE)
SQL SECURITY INVOKER
BEGIN
SELECT h.* FROM verhuur_huurder h WHERE LOWER(TRIM(h.naam))<>'scouting veghel' AND NOT EXISTS(SELECT 1 FROM verhuur_eigen_groep g WHERE g.huurder_id=h.id)
AND ((p_scope='unused_tenants' AND NOT EXISTS(SELECT 1 FROM verhuur_verhuring v WHERE v.huurder_id=h.id)) OR (p_scope='tenants'
AND EXISTS(SELECT 1 FROM verhuur_verhuring v WHERE v.huurder_id=h.id)
AND NOT EXISTS(SELECT 1 FROM verhuur_verhuring v LEFT JOIN verhuur_reservering r ON r.id=v.reservering_id WHERE v.huurder_id=h.id AND
((v.groep IS NOT NULL AND TRIM(v.groep)<>'') OR r.id IS NULL OR r.status_id NOT IN(8,10,11,19,20,21) OR r.einddatum<='1000-01-01' OR r.einddatum>=p_cutoff OR v.datum<='1000-01-01' OR v.datum>=p_cutoff)))) ORDER BY h.id;
END$$
DROP PROCEDURE IF EXISTS beheer_snapshot_rows$$
CREATE PROCEDURE beheer_snapshot_rows(IN p_kind VARCHAR(32), IN p_ids LONGTEXT)
SQL SECURITY INVOKER
BEGIN
IF NOT JSON_VALID(p_ids) OR JSON_TYPE(p_ids)<>'ARRAY' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige selectie'; END IF;
CASE p_kind
WHEN 'reservations' THEN SELECT * FROM verhuur_reservering WHERE JSON_CONTAINS(p_ids,CAST(id AS CHAR)) ORDER BY id;
WHEN 'tenants' THEN SELECT * FROM verhuur_huurder WHERE JSON_CONTAINS(p_ids,CAST(id AS CHAR)) ORDER BY id;
WHEN 'notes' THEN SELECT * FROM verhuur_notes WHERE JSON_CONTAINS(p_ids,CAST(hoort_bij AS CHAR)) ORDER BY hoort_bij,id;
WHEN 'statuses' THEN SELECT * FROM verhuur_status WHERE JSON_CONTAINS(p_ids,CAST(status_id AS CHAR)) ORDER BY status_id;
WHEN 'rentals_by_tenant' THEN SELECT * FROM verhuur_verhuring WHERE JSON_CONTAINS(p_ids,CAST(huurder_id AS CHAR)) ORDER BY id;
ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Onbekende selectie'; END CASE;
END$$
DROP PROCEDURE IF EXISTS beheer_maintenance_begin$$
CREATE PROCEDURE beheer_maintenance_begin()
SQL SECURITY INVOKER
BEGIN
IF EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN('verhuur_verhuring','verhuur_reservering','verhuur_notes','verhuur_mutaties','verhuur_huurder','verhuur_status','verhuur_eigen_groep') AND engine<>'InnoDB') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Voer eerst de InnoDB-migratie uit'; END IF;
SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
START TRANSACTION;
SELECT id FROM verhuur_verhuring FOR UPDATE;
SELECT id FROM verhuur_reservering FOR UPDATE;
SELECT id FROM verhuur_notes FOR UPDATE;
SELECT id FROM verhuur_mutaties FOR UPDATE;
SELECT id FROM verhuur_huurder FOR UPDATE;
SELECT status_id FROM verhuur_status FOR UPDATE;
SELECT huurder_id FROM verhuur_eigen_groep FOR UPDATE;
END$$
DROP PROCEDURE IF EXISTS beheer_maintenance_delete$$
CREATE PROCEDURE beheer_maintenance_delete(IN p_scope VARCHAR(32), IN p_rentals LONGTEXT, IN p_reservations LONGTEXT, IN p_tenants LONGTEXT, IN p_logs LONGTEXT)
SQL SECURITY INVOKER
BEGIN
DECLARE v_tenants INT DEFAULT 0;
IF @@in_transaction=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Geen onderhoudstransactie'; END IF;
IF p_scope='logs' THEN DELETE FROM verhuur_mutaties WHERE JSON_CONTAINS(p_logs,CAST(id AS CHAR));
ELSEIF p_scope IN('completed','cancelled','tenants','unused_tenants') THEN
 DELETE FROM verhuur_notes WHERE JSON_CONTAINS(p_rentals,CAST(hoort_bij AS CHAR));
 DELETE FROM verhuur_verhuring WHERE JSON_CONTAINS(p_rentals,CAST(id AS CHAR));
 DELETE FROM verhuur_reservering WHERE JSON_CONTAINS(p_reservations,CAST(id AS CHAR)) AND NOT EXISTS(SELECT 1 FROM verhuur_verhuring WHERE reservering_id=verhuur_reservering.id);
 IF p_scope IN('tenants','unused_tenants') THEN
 DELETE FROM verhuur_huurder WHERE JSON_CONTAINS(p_tenants,CAST(id AS CHAR)) AND NOT EXISTS(SELECT 1 FROM verhuur_verhuring WHERE huurder_id=verhuur_huurder.id) AND NOT EXISTS(SELECT 1 FROM verhuur_eigen_groep WHERE huurder_id=verhuur_huurder.id);
 SET v_tenants=ROW_COUNT();
 IF v_tenants<>JSON_LENGTH(p_tenants) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Huurdersselectie gewijzigd'; END IF;
 END IF;
ELSE SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Ongeldige opruimoptie'; END IF;
END$$
DROP PROCEDURE IF EXISTS beheer_maintenance_commit$$
CREATE PROCEDURE beheer_maintenance_commit()
SQL SECURITY INVOKER
BEGIN
COMMIT;
END$$
DROP PROCEDURE IF EXISTS beheer_maintenance_rollback$$
CREATE PROCEDURE beheer_maintenance_rollback()
SQL SECURITY INVOKER
BEGIN
ROLLBACK;
END$$
DELIMITER ;
SET SESSION sql_mode=@beheer_old_sql_mode;
