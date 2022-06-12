DELIMITER $$
CREATE PROCEDURE `GetSlotData`(IN `slotid` INT)
SELECT `Times`.`Time`, `Distances`.`Distance`
FROM `Slots`
JOIN `Times` ON `Times`.`ID` = `Slots`.`Time`
JOIN `Distances` ON `Distances`.`ID` = `Slots`.`Distance`
WHERE `Slots`.`ID` = slotid$$
DELIMITER ;