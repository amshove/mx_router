-- #######################################################
-- # -------------------- mx_router -------------------- #
-- # Copyright (C) Torsten Amshove <torsten@amshove.net> #
-- # See: http://www.amshove.net                         #
-- #######################################################

CREATE TABLE IF NOT EXISTS `history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(200) NOT NULL,
  `leitung` tinyint(4) NOT NULL DEFAULT '0',
  `add_user` varchar(200) NOT NULL,
  `add_date` varchar(200) NOT NULL,
  `end_date` varchar(200) NOT NULL,
  `del_user` varchar(200) NOT NULL,
  `del_date` varchar(200) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `tcid` int(11) NOT NULL,
  `old_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `traffic` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `ports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `leitung` tinyint(4) NOT NULL DEFAULT '0',
  `tcp` varchar(200) NOT NULL,
  `udp` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `timeslots` (
  `ip` varchar(15) CHARACTER SET latin1 NOT NULL,
  `used` tinyint(3) unsigned NOT NULL,
  `period_start` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ip`)
);

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(200) NOT NULL,
  `pw` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `ad_level` int(11) NOT NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `turniere` (
  `turnier_id` int(11) NOT NULL,
  `leitungen` varchar(200) NOT NULL,
  PRIMARY KEY (`turnier_id`)
);

-- Default Admin: admin
-- Default PW: mx_router
INSERT INTO `user` (`login`, `pw`, `name`, `ad_level`) VALUES
('admin', '878bbcc0bad81a23192f0e55a037c189fb81a3fb', 'Default Admin', 5);

INSERT INTO `ports` (`name`, `active`, `leitung`, `tcp`, `udp`) VALUES
('Steam', 0, 0, '27014:27050', '27000:27030,4380,1500,3005,3101,28960'),
('Starcraft II', 0, 0, '1119', '1119'),
('ICQ', 0, 0, '5190', ''),
('CoD MW3', 0, 0, '3074,27000:27050', '3074,8766'),
('Xfire', 0, 0, '25999', ''),
('LoL (+ HTTP)', 0, 0, '80,443,2099,5223,56000:60000', '80,2001,3000:6000,10000:60000'),
('Mails', 0, 0, '110,143,25,465,585,993,995', ''),
('HTTP', 0, 0, '80,443', '');
