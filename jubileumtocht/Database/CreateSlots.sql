
-- Slots for 5.5 KM
INSERT INTO `Slots`(`Time`, `Distance`)
SELECT `Times`.`ID`, `Distances`.`ID` FROM `Times`, `Distances`
WHERE `Distances`.`Distance`<10;

-- SLots for 11 KM
INSERT INTO `Slots`(`Time`, `Distance`)
SELECT `Times`.`ID`, `Distances`.`ID` FROM `Times`, `Distances`
WHERE `Distances`.`Distance`>10 AND `Times`.`Time` < '13:46'