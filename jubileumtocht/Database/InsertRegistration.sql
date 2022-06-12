DELIMITER $$
CREATE PROCEDURE `InsertRegistration`(IN `particpantid` INT, IN `slotid` INT, IN `walkers` INT)
INSERT INTO `Registrations`(`ID`, `Participant`, `Slot`, `AmountOfWalkers`)
VALUES (NULL,particpantid,slotid,walkers)$$
DELIMITER ;