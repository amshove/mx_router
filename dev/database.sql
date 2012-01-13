--
-- Tabellenstruktur für Tabelle `history`
--

CREATE TABLE IF NOT EXISTS `history` (
  `id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `ip` varchar(200) NOT NULL,
  `add_user` varchar(200) NOT NULL,
  `add_date` varchar(200) NOT NULL,
  `end_date` varchar(200) NOT NULL,
  `del_user` varchar(200) NOT NULL,
  `del_date` varchar(200) NOT NULL,
  `active` int(11) NOT NULL,
  `reason` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `history`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(200) NOT NULL,
  `pw` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `ad_level` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Daten für Tabelle `user`
--

INSERT INTO `user` (`id`, `login`, `pw`, `name`, `ad_level`) VALUES
(1, 'admin', '71910e4b0a625f5b8126095bda01df5fc76c2351', 'Default Admin', 5);
