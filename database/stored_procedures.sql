-- Simplified stored procedures for local development/testing
-- These procedures emulate the production routines with basic pass-through logic.

DELIMITER $$

DROP PROCEDURE IF EXISTS GetHuurder $$
CREATE PROCEDURE GetHuurder (
    IN p_naam TEXT,
    IN p_contact TEXT,
    IN p_mail TEXT,
    IN p_telefoon TEXT,
    IN p_adres TEXT,
    IN p_postcode TEXT,
    IN p_plaats TEXT
)
BEGIN
    SELECT id
    FROM verhuur_huurder
    WHERE LOWER(email) = LOWER(p_mail)
      AND LOWER(naam) = LOWER(p_naam)
    ORDER BY id DESC
    LIMIT 1;
END $$

DROP PROCEDURE IF EXISTS CreateHuurder $$
CREATE PROCEDURE CreateHuurder (
    IN p_naam TEXT,
    IN p_contact TEXT,
    IN p_mail TEXT,
    IN p_telefoon TEXT,
    IN p_postcode TEXT,
    IN p_plaats TEXT,
    IN p_adres TEXT,
    IN p_ip TEXT
)
BEGIN
    INSERT INTO verhuur_huurder (
        naam,
        contactpersoon,
        email,
        telefoon,
        adres,
        postcode,
        plaats,
        ip
    )
    VALUES (
        p_naam,
        NULLIF(p_contact, ''),
        p_mail,
        p_telefoon,
        p_adres,
        p_postcode,
        p_plaats,
        NULLIF(p_ip, '')
    );
END $$

DROP PROCEDURE IF EXISTS HuurderIDGroepscode $$
CREATE PROCEDURE HuurderIDGroepscode (
    IN p_code TEXT
)
BEGIN
    SELECT huurder_id
    FROM verhuur_eigen_groep
    WHERE code = p_code
    LIMIT 1;
END $$

DROP PROCEDURE IF EXISTS GetHuurderFromID $$
CREATE PROCEDURE GetHuurderFromID (
    IN p_huurder_id INT
)
BEGIN
    SELECT naam, email
    FROM verhuur_huurder
    WHERE id = p_huurder_id
    LIMIT 1;
END $$

DROP PROCEDURE IF EXISTS InsertReservering $$
CREATE PROCEDURE InsertReservering (
    IN p_beschrijving TEXT,
    IN p_begindatum DATETIME,
    IN p_einddatum DATETIME,
    IN p_personen INT
)
BEGIN
    INSERT INTO verhuur_reservering (
        beschrijving,
        begindatum,
        einddatum,
        personen,
        status_id
    )
    VALUES (
        p_beschrijving,
        p_begindatum,
        p_einddatum,
        p_personen,
        0
    );
END $$

DROP PROCEDURE IF EXISTS GetReservering $$
CREATE PROCEDURE GetReservering (
    IN p_beschrijving TEXT,
    IN p_begindatum DATETIME,
    IN p_einddatum DATETIME,
    IN p_personen INT
)
BEGIN
    SELECT id
    FROM verhuur_reservering
    WHERE beschrijving = p_beschrijving
      AND begindatum = p_begindatum
      AND einddatum = p_einddatum
      AND personen = p_personen
    ORDER BY id DESC
    LIMIT 1;
END $$

DROP PROCEDURE IF EXISTS GetReservations $$
CREATE PROCEDURE GetReservations (
    IN p_start DATETIME,
    IN p_end DATETIME
)
BEGIN
    SELECT r.begindatum,
           r.einddatum,
           v.groep
    FROM verhuur_reservering r
    LEFT JOIN verhuur_verhuring v ON v.reservering_id = r.id
    WHERE r.begindatum <= p_end
      AND r.einddatum >= p_start
    ORDER BY r.begindatum;
END $$

DROP PROCEDURE IF EXISTS ConfirmReservering $$
CREATE PROCEDURE ConfirmReservering (
    IN p_reservering_id INT
)
BEGIN
    UPDATE verhuur_reservering
    SET status_id = 1
    WHERE id = p_reservering_id;

    UPDATE verhuur_verhuring
    SET datum = NOW()
    WHERE reservering_id = p_reservering_id;
END $$

DROP PROCEDURE IF EXISTS ReserveringConfirmable $$
CREATE PROCEDURE ReserveringConfirmable (
    IN p_reservering_id INT
)
BEGIN
    SELECT id
    FROM verhuur_reservering
    WHERE id = p_reservering_id
      AND status_id = 0
    LIMIT 1;
END $$

DROP PROCEDURE IF EXISTS InsertVerhuring $$
CREATE PROCEDURE InsertVerhuring (
    IN p_huurder_id INT,
    IN p_reservering_id INT,
    IN p_groepcode TEXT
)
BEGIN
    DECLARE v_confirm VARCHAR(128);
    SET v_confirm = SHA2(CONCAT(UUID(), '-', p_huurder_id, '-', p_reservering_id), 256);

    INSERT INTO verhuur_verhuring (
        huurder_id,
        reservering_id,
        datum,
        confirm,
        groep
    )
    VALUES (
        p_huurder_id,
        p_reservering_id,
        NOW(),
        v_confirm,
        NULLIF(p_groepcode, '')
    );
END $$

DROP PROCEDURE IF EXISTS GetVerhuringFromConfirm $$
CREATE PROCEDURE GetVerhuringFromConfirm (
    IN p_confirm TEXT
)
BEGIN
    SELECT id, huurder_id, reservering_id
    FROM verhuur_verhuring
    WHERE confirm = p_confirm
    LIMIT 1;
END $$

DROP PROCEDURE IF EXISTS GetConfirm $$
CREATE PROCEDURE GetConfirm (
    IN p_huurder_id INT,
    IN p_reservering_id INT
)
BEGIN
    SELECT confirm
    FROM verhuur_verhuring
    WHERE huurder_id = p_huurder_id
      AND reservering_id = p_reservering_id
    ORDER BY id DESC
    LIMIT 1;
END $$

DROP PROCEDURE IF EXISTS GetHuurovereenkomstData $$
CREATE PROCEDURE GetHuurovereenkomstData (
    IN p_verhuring_id INT
)
BEGIN
    SELECT h.naam,
           h.telefoon,
           h.adres,
           h.postcode,
           h.plaats,
           r.begindatum,
           r.einddatum,
           r.personen,
           DATE_FORMAT(DATE_SUB(r.begindatum, INTERVAL 14 DAY), '%Y-%m-%d') AS borg_limit,
           DATE_FORMAT(DATE_SUB(r.begindatum, INTERVAL 14 DAY), '%Y-%m-%d') AS pay_limit,
           v.datum
    FROM verhuur_verhuring v
    JOIN verhuur_huurder h ON h.id = v.huurder_id
    JOIN verhuur_reservering r ON r.id = v.reservering_id
    WHERE v.id = p_verhuring_id
    LIMIT 1;
END $$

DELIMITER ;
