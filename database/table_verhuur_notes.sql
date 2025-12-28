
-- --------------------------------------------------------

--
-- Table structure for table `verhuur_notes`
--

CREATE TABLE `verhuur_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hoort_bij` int(11) NOT NULL DEFAULT 0,
  `opmerking` text NOT NULL,
  `schrijver` text NOT NULL,
  `datum` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
