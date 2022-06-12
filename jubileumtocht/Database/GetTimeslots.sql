DELIMITER $$
CREATE PROCEDURE `GetTimeslots`()
SELECT `Slots`.`ID` AS `SlotID`, `Times`.`Time`, `Distances`.`Distance`, (`Distances`.`MaxWalkersPerSlot` - `A`.`TotalRegistrations`) AS `AmountAvailable`
FROM `Slots`
JOIN `Times` ON `Slots`.`Time` = `Times`.`ID`
JOIN `Distances` ON `Slots`.`Distance` = `Distances`.`ID`
LEFT OUTER JOIN  (
    SELECT `Slots`.`ID` as `SlotID`, COALESCE(SUM(`Registrations`.`AmountOfWalkers`), 0) AS `TotalRegistrations`
	FROM `Slots`
    LEFT OUTER JOIN `Registrations` ON `Registrations`.`Slot` = `Slots`.`ID`
	GROUP BY `Slots`.`ID`
) as `A` ON `A`.`SlotID` = `Slots`.`ID`$$
DELIMITER ;