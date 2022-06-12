DELIMITER $$
CREATE PROCEDURE `GetAvailableWalkers`(IN `slotid` INT)
SELECT `Distances`.`MaxWalkersPerSlot` - `A`.`TotalRegistrations` AS `AmountAvailable`
FROM `Slots`
JOIN `Distances` ON `Slots`.`Distance` = `Distances`.`ID`
LEFT OUTER JOIN  (
    SELECT `Slots`.`ID` as `SlotID`, COALESCE(SUM(`Registrations`.`AmountOfWalkers`), 0) AS `TotalRegistrations`
	FROM `Slots`
    LEFT OUTER JOIN `Registrations` ON `Registrations`.`Slot` = `Slots`.`ID`
	GROUP BY `Slots`.`ID`
) as `A` ON `A`.`SlotID` = `Slots`.`ID`
WHERE `Slots`.`ID` = slotid$$
DELIMITER ;