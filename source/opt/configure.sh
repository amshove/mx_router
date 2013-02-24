#!/bin/bash
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

echo "# ACHTUNG: Der Router wird gestoppt"
read -p "Soll der Router nach dem Konfigurieren wieder gestartet werden? (y/n) " START

if [ "$START" != "y" ] && [ "$START" != "n" ]; then
  echo "Unbekannte Eingabe - breche ab .."
  exit 1
fi

PFAD="/opt/mx_router"
FW_TMPL="$PFAD/etc/firewall.template"

LOCAL_NET=`ip route | grep eth0 | grep link | grep -v default | cut -d " " -f 1` # Sowas wie 10.10.0.0/20
LOCAL_IP=`ifconfig eth0 | grep 'inet ' | cut -d: -f2 | awk '{ print $1}'`

echo "# Stopping mx_router .."
stop mx_router

# Routing-Tables erstellen
echo "#
# reserved values
#
255	local
254	main
253	default
0	unspec
#
# local
#
#1	inr.ruhep" > /etc/iproute2/rt_tables
I=1 # Counter fuer /etc/iproute2/rt_tables

rm $PFAD/start.routing.tmp 2>/dev/null
rm $PFAD/stop.routing.tmp 2>/dev/null
rm $PFAD/start.firewall.tmp 2>/dev/null
rm $PFAD/leitungen.inc.php.tmp 2>/dev/null

if [ `ls -1 $PFAD/etc/leitungen.d/ | grep "\.conf$" | grep -v template.conf | wc -l` -lt 1 ]; then
  echo "ERROR: Es wurden keine Leitungen definiert"
  echo "Du musst erst in /opt/mx_router/etc/leitungen.d/ Konfigurationen anlegen"
  exit 1
fi

DEFAULT_GW="" # Wird vom letzten Element in der Schleife gefuellt
DEFAULT_GW_IF="" # Interface zum Default-GW
DEFAULT_GW_IP="" # Src-IP zum Default-GW
DEFAULT_FW_MARK="" # fw_mark zur default-Leitung

# Leitungs-Configs durchgehen und Scripte erstellen
for LEITUNG_CFG in `ls -1 $PFAD/etc/leitungen.d/ | grep "\.conf$" | grep -v template.conf`; do
  echo "### Leitung: $LEITUNG_CFG ###"
  ACTIVE=""
  DEFAULT=""
  INTERFACE=""
  WINAME=""
  NAME=""
  SUBNET=""
  IP=""
  GW=""
  PING_IP=""

  # Werte einlesen
  OLD_IFS=$IFS
  IFS=$'\012' # Sonst trennt cat auch Leerzeichen
  for LINE in `cat $PFAD/etc/leitungen.d/$LEITUNG_CFG | grep -v "^#"`; do
    KEY=`echo $LINE | cut -d "=" -f 1`
    VALUE=`echo $LINE | cut -d "=" -f 2`
    case $KEY in
      "ACTIVE") ACTIVE=$VALUE ;;
      "DEFAULT") DEFAULT=$VALUE ;;
      "INTERFACE") INTERFACE=$VALUE ;;
      "WINAME") WINAME=$VALUE ;;
      "NAME") NAME=$VALUE ;;
      "SUBNET") SUBNET=$VALUE ;;
      "IP") IP=$VALUE ;;
      "GW") GW=$VALUE ;;
      "PING_IP") PING_IP=$VALUE ;;
    esac
  done
  IFS=$OLD_OFS

  if [ "$ACTIVE" != "true" ]; then
    echo "# Lasse $LEITUNG_CFG aus - ist deaktiviert."
    continue
  fi
  if [ "$ACTIVE" == "" ] || [ "$DEFAULT" == "" ] || [ "$INTERFACE" == "" ] || [ "$WINAME" == "" ] || [ "$NAME" == "" ] || [ "$SUBNET" == "" ] || [ "$IP" == "" ] || [ "$GW" == "" ] || [ "$PING_IP" == "" ]; then
    echo "# Lasse $LEITUNG_CFG aus - es sind nicht alle Werte gesetzt."
    continue
  fi

  if [ "$DEFAULT" == "true" ]; then
    DEFAULT_GW=$GW
    DEFAULT_GW_IF=$INTERFACE
    DEFAULT_GW_IP=$IP
    DEFAULT_FW_MARK=$I
  fi

  MASK=`echo $SUBNET | cut -d "/" -f 2`
  WINAME=`echo $WINAME | sed s/^\"// | sed s/\"$//`

  # Routing-Table erstellen
  echo "$I $NAME" >> /etc/iproute2/rt_tables

  # Routing erstellen
  echo "### Leitung: $LEITUNG_CFG ###" >> $PFAD/start.routing.tmp
  echo "# Interface einstellen" >> $PFAD/start.routing.tmp
#  echo "ip addr flush $INTERFACE" >> $PFAD/start.routing.tmp
  echo "ip route flush table $NAME" >> $PFAD/start.routing.tmp
#  echo "ip link set $INTERFACE down" >> $PFAD/start.routing.tmp
  echo "ip addr add $IP/$MASK dev $INTERFACE" >> $PFAD/start.routing.tmp
  echo "ip link set $INTERFACE up" >> $PFAD/start.routing.tmp
  echo "" >> $PFAD/start.routing.tmp
  echo "# Routing-Tabelle '$NAME' einstellen" >> $PFAD/start.routing.tmp
  echo "ip route add $LOCAL_NET src $LOCAL_IP dev eth0 table $NAME" >> $PFAD/start.routing.tmp
  echo "ip route add $SUBNET src $IP dev $INTERFACE table $NAME" >> $PFAD/start.routing.tmp
  echo "ip route add default via $GW src $IP dev $INTERFACE table $NAME" >> $PFAD/start.routing.tmp
  echo "" >> $PFAD/start.routing.tmp
  echo "# FWmark-Regel setzen" >> $PFAD/start.routing.tmp
  echo "ip rule add fwmark $I table $NAME" >> $PFAD/start.routing.tmp
  echo "" >> $PFAD/start.routing.tmp
  echo "# PING_IP in default-routing-Tabelle eintragen" >> $PFAD/start.routing.tmp
  echo "ip route add $PING_IP via $GW dev $INTERFACE src $IP" >> $PFAD/start.routing.tmp
  echo "" >> $PFAD/start.routing.tmp
  echo "" >> $PFAD/start.routing.tmp

  echo "### Leitung: $LEITUNG_CFG ###" >> $PFAD/stop.routing.tmp
  echo "ip addr flush $INTERFACE" >> $PFAD/stop.routing.tmp
  echo "ip route flush table $NAME" >> $PFAD/stop.routing.tmp
  echo "ip link set $INTERFACE down" >> $PFAD/stop.routing.tmp

  # Firewall ersstellen
  echo "### Leitung: $LEITUNG_CFG ###" >> $PFAD/start.firewall.tmp
  echo "# Alles was zurueck kommt und zu einer Verbindung gehoert erlauben" >> $PFAD/start.firewall.tmp
  echo "/sbin/iptables -A FORWARD -i $INTERFACE -m state --state ESTABLISHED,RELATED -j ACCEPT -m comment --comment \"Bestehende Verbindungen von extern - $INTERFACE\"" >> $PFAD/start.firewall.tmp
  echo "" >> $PFAD/start.firewall.tmp
  echo "# NAT einstellen" >> $PFAD/start.firewall.tmp
  echo "iptables -t nat -A POSTROUTING -m mark --mark $I -j SNAT --to-source $IP" >> $PFAD/start.firewall.tmp
  echo "" >> $PFAD/start.firewall.tmp
  echo "" >> $PFAD/start.firewall.tmp

  # Webinterface Einstellung
  echo "  array(" >> $PFAD/leitungen.inc.php.tmp
  echo "    'name' => '$WINAME'," >> $PFAD/leitungen.inc.php.tmp
  echo "    'ip' => '$PING_IP'," >> $PFAD/leitungen.inc.php.tmp
  echo "    'eth' => '$INTERFACE'," >> $PFAD/leitungen.inc.php.tmp
  echo "    'table' => '$NAME'," >> $PFAD/leitungen.inc.php.tmp
  echo "    'fw_mark' => '$I'" >> $PFAD/leitungen.inc.php.tmp
  echo "  )," >> $PFAD/leitungen.inc.php.tmp

  let I=$I+1
done

# Wenn keine default-Leitung ausgewaehlt wurde, nehm die letzte ..
if [ "$DEFAULT_GW" == "" ]; then
  DEFAULT_GW=$GW
  DEFAULT_GW_IF=$INTERFACE
  DEFAULT_GW_IP=$IP
  let DEFAULT_FW_MARK=$I-1
fi

# Script-Header erstellen
echo "#!/bin/bash" > $PFAD/start.sh
echo "#######################################################" >> $PFAD/start.sh
echo "# -------------------- mx_router -------------------- #" >> $PFAD/start.sh
echo "# Copyright (C) Torsten Amshove <torsten@amshove.net> #" >> $PFAD/start.sh
echo "# See: http://www.amshove.net                         #" >> $PFAD/start.sh
echo "############################################################" >> $PFAD/start.sh
echo "# Dieses Script wird automatisch durch configure.sh erstellt" >> $PFAD/start.sh
echo "# Manuelle aenderungen werden ueberschrieben!" >> $PFAD/start.sh
echo "############################################################" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
cp $PFAD/start.sh $PFAD/stop.sh # Bis hier hin sind beide gleich

# start.sh erstellen
echo "###################" >> $PFAD/start.sh
echo "# ROUTING" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
cat $PFAD/start.routing.tmp >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "# Default-Route in Default-Tabelle, damit der Server auch ins Netz kommt" >> $PFAD/start.sh
echo "ip route add default via $DEFAULT_GW dev $DEFAULT_GW_IF src $DEFAULT_GW_IP" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
echo "# FIREWALL" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
cat $FW_TMPL >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
cat $PFAD/start.firewall.tmp >> $PFAD/start.sh
echo "### additional_rules.conf ###" >> $PFAD/start.sh
cat $PFAD/etc/additional_rules.conf >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "# Default-Leitung waehlen und MARK auf die komplette CONNECTION setzen" >> $PFAD/start.sh
echo "/sbin/iptables -t mangle -A PREROUTING -s $LOCAL_NET -j MARK --set-mark $DEFAULT_FW_MARK" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "# Alte Eintraege aus der DB wiederherstellen" >> $PFAD/start.sh
echo "/usr/bin/php /var/www/scripte/restore.php > /dev/null 2>&1" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
echo "# Forwarding aktivieren" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
#echo "echo 2 > /proc/sys/net/ipv4/conf/all/rp_filter # Source Routing = loose (alles bekannten Nezte sind erlaubt)" >> $PFAD/start.sh
echo "sysctl -w net.ipv4.ip_forward=1" >> $PFAD/start.sh

# stop.sh erstellen
echo "###################" >> $PFAD/stop.sh
echo "# Forwarding deaktivieren" >> $PFAD/stop.sh
echo "sysctl -w net.ipv4.ip_forward=0" >> $PFAD/stop.sh
echo "" >> $PFAD/stop.sh
echo "" >> $PFAD/stop.sh
echo "###################" >> $PFAD/stop.sh
echo "# ROUTING" >> $PFAD/stop.sh
echo "###################" >> $PFAD/stop.sh
echo "" >> $PFAD/stop.sh
cat $PFAD/stop.routing.tmp >> $PFAD/stop.sh
echo "" >> $PFAD/stop.sh
echo "# IP-Rules loeschen" >> $PFAD/stop.sh
echo "ip rule flush" >> $PFAD/stop.sh
echo "ip rule add table main pref 32766" >> $PFAD/stop.sh
echo "ip rule add table default pref 32767" >> $PFAD/stop.sh
echo "" >> $PFAD/stop.sh
echo "###################" >> $PFAD/stop.sh
echo "# FIREWALL" >> $PFAD/stop.sh
echo "###################" >> $PFAD/stop.sh
echo "" >> $PFAD/stop.sh
echo "/sbin/iptables -P INPUT ACCEPT" >> $PFAD/stop.sh
echo "/sbin/iptables -P FORWARD ACCEPT" >> $PFAD/stop.sh
echo "/sbin/iptables -P OUTPUT ACCEPT" >> $PFAD/stop.sh
echo "" >> $PFAD/stop.sh
echo "/sbin/iptables -F" >> $PFAD/stop.sh
echo "/sbin/iptables -F INPUT" >> $PFAD/stop.sh
echo "/sbin/iptables -F OUTPUT" >> $PFAD/stop.sh
echo "/sbin/iptables -F FORWARD" >> $PFAD/stop.sh
echo "/sbin/iptables -F -t mangle" >> $PFAD/stop.sh
echo "/sbin/iptables -F -t nat" >> $PFAD/stop.sh
echo "/sbin/iptables -X" >> $PFAD/stop.sh
echo "/sbin/iptables -Z" >> $PFAD/stop.sh

# leitungen.inc.php erstellen
echo "<?php" > /var/www/leitungen.inc.php
echo "#######################################################" >> /var/www/leitungen.inc.php
echo "# -------------------- mx_router -------------------- #" >> /var/www/leitungen.inc.php
echo "# Copyright (C) Torsten Amshove <torsten@amshove.net> #" >> /var/www/leitungen.inc.php
echo "# See: http://www.amshove.net                         #" >> /var/www/leitungen.inc.php
echo "############################################################" >> /var/www/leitungen.inc.php
echo "# Dieses Script wird automatisch durch configure.sh erstellt" >> /var/www/leitungen.inc.php
echo "# Manuelle aenderungen werden ueberschrieben!" >> /var/www/leitungen.inc.php
echo "############################################################" >> /var/www/leitungen.inc.php
echo "\$leitungen = array(" >> /var/www/leitungen.inc.php
cat $PFAD/leitungen.inc.php.tmp >> /var/www/leitungen.inc.php
echo ");" >> /var/www/leitungen.inc.php
echo "\$default_leitung = '$DEFAULT_FW_MARK';" >> /var/www/leitungen.inc.php
echo "\$max_fw_mark = '$(( I-1 ))';" >> /var/www/leitungen.inc.php
echo "\$local_net = '$LOCAL_NET';" >> /var/www/leitungen.inc.php
echo "?>" >> /var/www/leitungen.inc.php

rm $PFAD/start.routing.tmp
rm $PFAD/stop.routing.tmp
rm $PFAD/start.firewall.tmp
rm $PFAD/leitungen.inc.php.tmp

chown root:root $PFAD/start.sh
chmod 744 $PFAD/start.sh
chown root:root $PFAD/stop.sh
chmod 744 $PFAD/stop.sh

echo "#############################"
echo "# Scripte konfiguriert"
echo "# Du kannst den Router jetzt starten"
echo "#   start mx_router"
echo "#############################"

if [ "$START" == "y" ]; then
  echo ""
  echo "# Starte mx_router .."
  start mx_router
fi
