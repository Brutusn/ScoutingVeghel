-- Select the intended database explicitly before importing. No table/schema changes.
-- New name: existing procedures keep their original behavior until feature activation.
DROP PROCEDURE IF EXISTS PrivacyCreateBooking;
DELIMITER //
CREATE PROCEDURE PrivacyCreateBooking(
 IN p_name TEXT, IN p_contact TEXT, IN p_email TEXT, IN p_phone TEXT,
 IN p_address TEXT, IN p_postcode TEXT, IN p_city TEXT, IN p_ip TEXT,
 IN p_group INT, IN p_start DATETIME, IN p_end DATETIME,
 IN p_people INT, IN p_description TEXT, IN p_status INT, IN p_token TEXT
)
SQL SECURITY INVOKER
MODIFIES SQL DATA
BEGIN
 DECLARE h INT DEFAULT 0;
 DECLARE r INT DEFAULT 0;
 DECLARE v INT DEFAULT 0;
 DECLARE g TEXT DEFAULT NULL;
 DECLARE EXIT HANDLER FOR SQLEXCEPTION
 BEGIN
  IF v>0 THEN DELETE FROM verhuur_mutaties WHERE hoort_bij=v; DELETE FROM verhuur_verhuring WHERE id=v; END IF;
  IF r>0 THEN DELETE FROM verhuur_reservering WHERE id=r; END IF;
  IF p_group=0 AND h>0 THEN DELETE FROM verhuur_huurder WHERE id=h; END IF;
  RESIGNAL;
 END;
 IF p_start>=p_end OR p_people<1 OR CHAR_LENGTH(p_token)<32 THEN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid booking';
 END IF;
 IF p_group>0 THEN
  IF NOT EXISTS (SELECT 1 FROM verhuur_eigen_groep WHERE huurder_id=p_group) THEN
   SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Unknown own group';
  END IF;
  SET h=p_group;
  SELECT naam INTO g FROM verhuur_huurder WHERE id=h;
 ELSE
  INSERT INTO verhuur_huurder (naam,contactpersoon,email,telefoon,adres,postcode,plaats,ip)
  VALUES (p_name,p_contact,p_email,p_phone,p_address,p_postcode,p_city,p_ip);
  SET h=LAST_INSERT_ID();
 END IF;
 INSERT INTO verhuur_reservering (begindatum,einddatum,personen,beschrijving,status_id)
 VALUES (p_start,p_end,p_people,p_description,p_status);
 SET r=LAST_INSERT_ID();
 INSERT INTO verhuur_verhuring (huurder_id,reservering_id,datum,confirm,groep)
 VALUES (h,r,NOW(),p_token,g);
 SET v=LAST_INSERT_ID();
 INSERT INTO verhuur_mutaties (hoort_bij,naam,actie,datum,`query`)
 VALUES (v,'systeem','AANGEMAAKT',NOW(),'{"summary":"Verhuring aangemaakt"}');
 SELECT v AS id,h AS huurder_id,r AS reservering_id,p_token AS confirm;
END//
DELIMITER ;
