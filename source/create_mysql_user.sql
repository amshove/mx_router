-- #######################################################
-- # -------------------- mx_router -------------------- #
-- # Copyright (C) Torsten Amshove <torsten@amshove.net> #
-- # See: http://www.amshove.net                         #
-- #######################################################

CREATE USER 'mx_router'@'localhost' IDENTIFIED BY '***';
GRANT USAGE ON * . * TO 'mx_router'@'localhost' IDENTIFIED BY '***' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;
CREATE DATABASE IF NOT EXISTS `mx_router` ;
GRANT ALL PRIVILEGES ON `mx_router` . * TO 'mx_router'@'localhost';
FLUSH PRIVILEGES ;
