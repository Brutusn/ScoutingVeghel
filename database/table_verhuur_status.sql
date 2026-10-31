
-- --------------------------------------------------------

--
-- Table structure for table `verhuur_status`
--

CREATE TABLE `verhuur_status` (
  `status_id` int(11) NOT NULL,
  `status` text NOT NULL,
  PRIMARY KEY (`status_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `verhuur_status`
--

INSERT INTO `verhuur_status` (`status_id`, `status`) VALUES
(0, 'Optie'),
(1, 'Bevestigd'),
(2, 'Goedgekeurd'),
(4, 'Betaald'),
(5, 'Bezig'),
(6, 'Nacontrole'),
(7, 'Borg terug te storten'),
(8, 'Afgehandeld'),
(10, 'Geannuleerd'),
(11, 'Vervallen'),
(12, 'onbetaald plaatsgevonden'),
(20, 'Verwijderd door beheerder'),
(21, '<span style=\"color: red;\">Verwijderd (definitief)</span>'),
(3, 'Borg betaald'),
(19, 'Afgekeurd door beheerder');
