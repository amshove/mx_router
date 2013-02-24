#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

Anleitungen und Infos zu den Features und zur Bedienung findest du auf http://www.amshove.net

###############
# Einrichtung #
###############
- Zum Einrichten der Leitungen geht man in das Verzeichnis /opt/mx_router/etc/leitungen.d/
cd /opt/mx_router/etc/leitungen.d/
- In diesem Verzeichnis muss man pro Internet-Leitung eine .conf-Datei anlegen. Dafuer kopiert man sich das Template:
sudo cp template.conf line1.conf
sudo cp template.conf line2.conf
(Der Name der .conf-Datei ist egal - denn kann man selbst waehlen)
- Danach editiert man diese Dateien. Die einzelnen Parameter sind in der Datei beschrieben.
sudo vi line1.conf
sudo vi line2.conf
- Nach der Definition der Leitungen muss man das configure-Script laufen lassen:
sudo /opt/mx_router/configure.sh
Dieses Script liest die Einstellungen ein und erstellt die beiden Scripte /opt/mx_router/start.sh und stop.sh
- Nun kann man sich auf der Webseite einloggen:
http://<ip des routers>/
User: admin
PW: mx_router

###################
# Weitere Befehle #
###################
- Mit folgendem Befehl kann man sich einen Status anzeigen lassen
sudo /opt/mx_router/show_status.sh
- Starten und stoppen kann man den Router entweder mit:
sudo stop mx_router
sudo start mx_router
oder mit:
sudo /opt/mx_router/stop.sh
sudo /opt/mx_router/start.sh


##########################
# Eigene Firewall-Regeln #
##########################
- Eigene Firewall-Regeln koennen in der Datei /opt/mx_router/etc/additional_rules.conf eingetragen werden
- Aktiv werden die Aenderungen erst nach dem Ausfuehren des configure-Scriptes:
sudo /opt/mx_router/configure.sh

###########
# HINWEIS #
###########
- Immer wenn man etwas unter /opt/mx_router/etc/ editiert, muss das configure-Script ausgefuehrt werden:
sudo /opt/mx_router/configure.sh
- Erst danach werden die Aenderungen aktiv

