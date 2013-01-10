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

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL auto_increment,
  `login` varchar(200) NOT NULL,
  `pw` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `ad_level` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- Default Admin: admin
-- Default PW: mx_router
INSERT INTO `user` (`id`, `login`, `pw`, `name`, `ad_level`) VALUES
(1, 'admin', '878bbcc0bad81a23192f0e55a037c189fb81a3fb', 'Default Admin', 5);
