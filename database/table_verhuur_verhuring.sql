
-- --------------------------------------------------------

--
-- Table structure for table `verhuur_verhuring`
--

CREATE TABLE `verhuur_verhuring` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `huurder_id` int(11) NOT NULL DEFAULT 0,
  `reservering_id` int(11) NOT NULL DEFAULT 0,
  `datum` datetime NOT NULL,
  `confirm` text NOT NULL,
  `groep` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
