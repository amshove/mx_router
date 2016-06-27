-- #######################################################
-- # -------------------- mx_router -------------------- #
-- # Copyright (C) Torsten Amshove <torsten@amshove.net> #
-- # See: http://www.amshove.net                         #
-- #######################################################

CREATE TABLE IF NOT EXISTS `arp_table` (
  `ip` varchar(15) NOT NULL,
  `mac` varchar(17) NOT NULL,
  `interface` varchar(6) NOT NULL,
  `last_seen` datetime NOT NULL,
  PRIMARY KEY (`ip`,`mac`,`interface`)
);

CREATE TABLE IF NOT EXISTS `history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(200) NOT NULL,
  `leitung` tinyint(4) NOT NULL DEFAULT '0',
  `add_user` varchar(200) NULL,
  `add_date` varchar(200) NULL,
  `end_date` varchar(200) NULL,
  `del_user` varchar(200) NULL,
  `del_date` varchar(200) NULL,
  `active` tinyint(4) NULL,
  `tcid` int(11) NULL,
  `old_id` int(11) NULL,
  `reason` text NULL,
  `traffic` varchar(100) NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `ports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NULL,
  `active` tinyint(1) NULL,
  `leitung` tinyint(4) NOT NULL DEFAULT '0',
  `tcp` varchar(200) NULL,
  `udp` varchar(200) NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `timeslots` (
  `ip` varchar(15) CHARACTER SET latin1 NOT NULL,
  `used` tinyint(3) unsigned NULL,
  `period_start` int(10) unsigned NULL,
  PRIMARY KEY (`ip`)
);

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(200) NULL,
  `pw` varchar(200) NULL,
  `name` varchar(200) NULL,
  `ad_level` int(11) NULL,
  PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `turniere` (
  `turnier_id` int(11) NOT NULL,
  `leitungen` varchar(200) NULL,
  PRIMARY KEY (`turnier_id`)
);

-- Default Admin: admin
-- Default PW: mx_router
INSERT INTO `user` (`login`, `pw`, `name`, `ad_level`) VALUES
('admin', '878bbcc0bad81a23192f0e55a037c189fb81a3fb', 'Default Admin', 5);

INSERT INTO `ports` (`name`, `active`, `leitung`, `tcp`, `udp`) VALUES
('Steam', 0, 0, '27014:27050', '27000:27030,4380,1500,3005,3101,28960'),
('Starcraft II', 0, 0, '1119', '1119'),
('Hearthstone', 0, 0, '1119,3724', '1119,3724'),
('ICQ', 0, 0, '5190', ''),
('CoD MW3', 0, 0, '3074,27000:27050', '3074,8766'),
('Xfire', 0, 0, '25999', ''),
('LoL (+ HTTP)', 0, 0, '80,443,2099,5223,56000:60000', '80,2001,3000:6000,10000:60000'),
('Mails', 0, 0, '110,143,25,465,585,993,995', ''),
('HTTP', 0, 0, '80,443', '');
