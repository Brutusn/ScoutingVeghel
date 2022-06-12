DELIMITER $$
CREATE PROCEDURE `InsertParticipant`(IN `name` VARCHAR(255), IN `mail` VARCHAR(255))
INSERT INTO `Participant`(`ID`, `Name`, `Email`)
VALUES (NULL,name,mail)$$
DELIMITER ;