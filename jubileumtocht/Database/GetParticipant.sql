DELIMITER $$
CREATE PROCEDURE `GetParticipant`(IN `name` VARCHAR(255), IN `mail` VARCHAR(255))
SELECT `ID`
FROM `Participant`
WHERE `Name` = name AND `Email` = mail$$
DELIMITER ;