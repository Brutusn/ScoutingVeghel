
-- --------------------------------------------------------

--
-- Table structure for table `verhuur_huurder`
--

CREATE TABLE `verhuur_huurder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `naam` text NOT NULL,
  `contactpersoon` text DEFAULT NULL,
  `email` text NOT NULL,
  `telefoon` text NOT NULL,
  `adres` text NOT NULL,
  `postcode` text NOT NULL,
  `plaats` text NOT NULL,
  `ip` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
