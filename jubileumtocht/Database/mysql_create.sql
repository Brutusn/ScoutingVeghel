CREATE TABLE `Times` (
	`ID` INT NOT NULL AUTO_INCREMENT,
	`Time` TIME NOT NULL,
	PRIMARY KEY (`ID`)
);

CREATE TABLE `Distances` (
	`ID` INT NOT NULL AUTO_INCREMENT,
	`Distance` DECIMAL NOT NULL,
	`MaxWalkersPerSlot` INT NOT NULL,
	PRIMARY KEY (`ID`)
);

CREATE TABLE `Participant` (
	`ID` INT NOT NULL AUTO_INCREMENT,
	`Name` varchar(255) NOT NULL,
	`Email` varchar(255) NOT NULL,
	PRIMARY KEY (`ID`)
);

CREATE TABLE `Registrations` (
	`ID` INT NOT NULL AUTO_INCREMENT,
	`Participant` INT NOT NULL,
	`Slot` INT NOT NULL,
	`AmountOfWalkers` INT NOT NULL,
	PRIMARY KEY (`ID`)
);

CREATE TABLE `Slots` (
	`ID` INT NOT NULL AUTO_INCREMENT,
	`Time` INT NOT NULL,
	`Distance` INT NOT NULL,
	PRIMARY KEY (`ID`)
);

ALTER TABLE `Registrations` ADD CONSTRAINT `Registrations_fk0` FOREIGN KEY (`Participant`) REFERENCES `Participant`(`ID`);

ALTER TABLE `Registrations` ADD CONSTRAINT `Registrations_fk1` FOREIGN KEY (`Slot`) REFERENCES `Slots`(`ID`);

ALTER TABLE `Slots` ADD CONSTRAINT `Slots_fk0` FOREIGN KEY (`Time`) REFERENCES `Times`(`ID`);

ALTER TABLE `Slots` ADD CONSTRAINT `Slots_fk1` FOREIGN KEY (`Distance`) REFERENCES `Distances`(`ID`);






