
-- --------------------------------------------------------

--
-- Table structure for table `verhuur_mutaties`
--

CREATE TABLE `verhuur_mutaties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hoort_bij` int(11) NOT NULL DEFAULT 0,
  `naam` tinytext NOT NULL,
  `actie` tinytext NOT NULL,
  `datum` datetime NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
