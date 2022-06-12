DELIMITER $$
CREATE PROCEDURE `GetAmountOfWalkers`(IN `SlotID` INT)
SELECT COALESCE(SUM(`Registrations`.`AmountOfWalkers`), 0) AS `TotalRegistrations`
FROM `Slots`
LEFT OUTER JOIN `Registrations` ON `Registrations`.`Slot` = `Slots`.`ID`
WHERE `Slots`.`ID`=SlotID
GROUP BY `Slots`.`ID`$$
DELIMITER ;