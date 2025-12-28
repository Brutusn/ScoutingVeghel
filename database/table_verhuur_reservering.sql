
-- --------------------------------------------------------

--
-- Table structure for table `verhuur_reservering`
--

CREATE TABLE `verhuur_reservering` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `beschrijving` text NOT NULL,
  `begindatum` datetime NOT NULL,
  `einddatum` datetime NOT NULL,
  `personen` int(11) NOT NULL DEFAULT 0,
  `status_id` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
