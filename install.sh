#!/bin/bash
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

echo "###################################"
echo "# Bereite den Server als Router vor"
echo "###################################"

grep dhcp /etc/network/interfaces  > /dev/null 2>&1
if [ $? -eq 0 ]; then
  echo "# In /etc/network/interfaces steht noch ein Interface auf dhcp ..."
  echo "# eth0 muss fest auf die LAN-IP eingestellt sein"
  echo "# alle anderen Interfaces duerfen keine Konfiguration haben"
  echo "# Breche ab .. bitte erst /etc/network/interfaces bearbeiten"
  exit 1
fi

echo "# Deinstalliere resolvconf & appamor (mit funktioniert das ip_forwarding nicht)"
apt-get -y remove apparmor apparmor-utils resolvconf

echo "# Installiere fehlende Pakete"
apt-get -y install dnsmasq apache2 libapache2-mod-php5 php5-mysql php5 php5-cli mysql-server ntp ethtool conntrack

echo "# Erstelle /etc/sysctl.d/99-mx_router.conf"
cp source/99-mx_router.conf /etc/sysctl.d/99-mx_router.conf
chown root:root /etc/sysctl.d/99-mx_router.conf
chmod 644 /etc/sysctl.d/99-mx_router.conf
# service procps start bzw sysctl -p nicht noetig, weil spaeter reboot

echo "# Lege SUDO fuer www-data an (/etc/sudoers.d/mx_router)"
echo "www-data ALL= NOPASSWD: /sbin/iptables" > /etc/sudoers.d/mx_router
chown root:root /etc/sudoers.d/mx_router
chmod 0440 /etc/sudoers.d/mx_router

echo "# Ueberschreibe /etc/ntp.conf"
mv /etc/ntp.conf /etc/ntp.conf.orig
cp source/ntp.conf /etc/ntp.conf
chown root:root /etc/ntp.conf
chmod 644 /etc/ntp.conf

echo "# Richte /etc/resolv.conf ein"
DNS1=""
DNS2="8.8.8.8"
DOMAIN=""
read -p "Bitte den DNS-Server fuer das lokale Netz eingeben: " DNS1
read -p "Domain fuer das lokale Netz: " DOMAIN

mv /etc/resolv.conf /etc/resolv.conf.orig
if [ "$DOMAIN" != "" ]; then
  echo "domain $DOMAIN" >> /etc/resolv.conf
fi
if [ "$DNS1" != "" ]; then
  echo "nameserver $DNS1" >> /etc/resolv.conf
fi
echo "nameserver $DNS2" >> /etc/resolv.conf
chown root:root /etc/resolv.conf
chmod 644 /etc/resolv.conf

echo "###################################"
echo "# Richte MySQL-Server ein"
echo "###################################"

read -s -p "Bitte Passwort fuer MySQL-User 'root' eingeben: " MY_PW
mysql -u root --password=$MY_PW -e "SHOW DATABASES" > /dev/null 2>&1
RC=$?
while [ $RC -ne 0 ]; do
  echo ""
  echo "ERROR: Passwort falsch .."
  read -s -p "Bitte Passwort fuer MySQL-User 'root' eingeben: " MY_PW
  mysql -u root --password=$MY_PW -e "SHOW DATABASES" > /dev/null 2>&1
  RC=$?
done
echo ""

echo "# Generiere Passwort fuer mx_router User"
MX_PW=`date | md5sum | base64`

echo "# Lege MySQL-User und DB an"
sed s/\\*\\*\\*/$MX_PW/g source/create_mysql_user.sql | mysql -u root --password=$MY_PW
mysql -u mx_router --password=$MX_PW mx_router < source/create_mysql_db.sql

echo "###################################"
echo "# Richte Webinterface ein"
echo "###################################"

if [ ! -d "/var/www" ]; then
  echo "# Lege /var/www an"
  mkdir /var/www
  chmod 755 /var/www
fi

if [ `ls -1 /var/www/ | wc -l` -ne 0 ]; then
  DIR="/var/www_`date +%s`"
  echo "# /var/www ist nicht leer - verschiebe es nach $DIR"
  mv /var/www $DIR
  mkdir /var/www
  chmod 755 /var/www
fi

echo "# Kopiere Webinterface nach /var/www"
cp -r webinterface/* /var/www/
chown -R root:root /var/www/

echo "# Trage MySQL-PW in config.inc.php ein"
sed -i s/--MYSQL_PW--/$MX_PW/ /var/www/config.inc.php

echo "# Generiere PW fuer API und trage es in config.inc.php ein"
API_PW=`date | sha1sum | md5sum | base64`
sed -i s/--API_PW--/$API_PW/ /var/www/config.inc.php

echo "# Lege crontab-Eintrag an"
echo "" >> /etc/crontab
echo "# mx_router: Loeschen der zeitlich begrenzten Regeln" >> /etc/crontab
echo "*/1 *   * * *   root    /usr/bin/php /var/www/scripte/del_rules.php > /dev/null 2>&1" >> /etc/crontab

echo "###################################"
echo "# Richte Scripte ein"
echo "###################################"

echo "# Kopiere Scripte nach /opt/mx_router"
mkdir /opt/mx_router
cp -r source/opt/* /opt/mx_router
chown root:root -R /opt/mx_router
chmod 744 /opt/mx_router/*.sh

echo "# Richte Status-motd ein"
rm /etc/update-motd.d/*
ln -s /opt/mx_router/show_status.sh /etc/update-motd.d/99-mx_router-status

echo "# Lege Upstart-Config an (/etc/init/mx_router.conf)"
cp source/mx_router.conf /etc/init/mx_router.conf
chown root:root /etc/init/mx_router.conf
chmod 644 /etc/init/mx_router.conf
initctl reload-configuration

echo ""
echo ""
echo "###################################"
echo "# Installation durchgefuehrt      #"
echo "###################################"
echo "# Die Internet-Leitungen muessen in"
echo "#   /opt/mx_router/etc/leitungen.d/"
echo "# eingerichtet werden."
echo "#"
echo "# Danach werden die Aenderungen aktiv mit"
echo "#   /opt/mx_router/configure.sh"
echo "#"
echo "# Danach kann die Webseite benutzt werden"
echo "#   User: admin   PW: mx_router"
echo "###################################"
echo "# Alle Infos auch in: /opt/mx_router/README.txt"
echo "###################################"
echo ""
echo ""

echo "###################################"
echo "# Der Server wird jetzt neu gestartet .."
echo "# .. wenn du Enter drueckst"
echo "###################################"

read

reboot
