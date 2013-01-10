#!/bin/bash

PFAD="/opt/mx_router"
FW_TMPL="$PFAD/etc/firewall.template"

LOCAL_NET=`ip route | grep eth0 | grep -v default | cut -d " " -f 1` # Sowas wie 10.10.0.0/20
LOCAL_IP=`ifconfig eth0 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'`

echo "# Stopping mx_router .."
/etc/init.d/mx_router stop

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

# Leitungs-Configs durchgehen und Scripte erstellen
for LEITUNG_CFG in `ls -1 $PFAD/etc/leitungen.d/ | grep -v README | grep -v template.conf`; do
  echo "### Leitung: $LEITUNG_CFG ###"
  ACTIVE=""
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
  if [ "$ACTIVE" == "" ] || [ "$INTERFACE" == "" ] || [ "$WINAME" == "" ] || [ "$NAME" == "" ] || [ "$SUBNET" == "" ] || [ "$IP" == "" ] || [ "$GW" == "" ] || [ "$PING_IP" == ""]; then
    echo "# Lasse $LEITUNG_CFG aus - es sind nicht alle Werte gesetzt."
    continue
  fi
  MASK=`echo $SUBNET | cut -d "/" -f 2`

  # Routing-Table erstellen
  echo "$I $NAME" >> /etc/iproute2/rt_tables
  let I=$I+1

  # Routing erstellen
  echo "## Leitung $LEITUNG_CFG" >> $PFAD/start.routing.tmp
  echo "# Interface einstellen" >> $PFAD/start.routing.tmp
  echo "ip addr flush $INTERFACE" >> $PFAD/start.routing.tmp
  echo "ip route flush table $NAME" >> $PFAD/start.routing.tmp
  echo "ip link set $INTERFACE down" >> $PFAD/start.routing.tmp
  echo "ip addr add $IP/$MASK dev $INTERFACE" >> $PFAD/start.routing.tmp
  echo "ip link set $INTERFACE up" >> $PFAD/start.routing.tmp
  echo "" >> $PFAD/start.routing.tmp
  echo "# Routing-Tabelle '$NAME' einstellen" >> $PFAD/start.routing.tmp
  echo "ip route add $LOCAL_NET src $LOCAL_IP dev eth0 table $NAME"
  echo "ip route add $SUBNET src $IP dev $INTERFACE table $NAME"
  echo "ip route add default via $GW src $IP dev $INTERFACE table $NAME"
  echo "" >> $PFAD/start.routing.tmp
  echo "# PING_IP in default-routing-Tabelle eintragen" >> $PFAD/start.routing.tmp
  echo "ip route add $PING_IP via $GW dev $INTERFACE src $IP"
  echo "" >> $PFAD/start.routing.tmp
  echo "" >> $PFAD/start.routing.tmp

  echo "## Leitung $LEITUNG_CFG" >> $PFAD/stop.routing.tmp
  echo "ip addr flush $INTERFACE" >> $PFAD/stop.routing.tmp
  echo "ip route flush table $NAME" >> $PFAD/stop.routing.tmp
  echo "ip link set $INTERFACE down" >> $PFAD/stop.routing.tmp

  # Firewall ersstellen
  echo "## Leitung $LEITUNG_CFG" >> $PFAD/start.firewall.tmp
  echo "# Alles was zurueck kommt und zu einer Verbindung gehoert erlauben" >> $PFAD/start.firewall.tmp
  echo "/sbin/iptables -A FORWARD -i $INTERFACE -m state --state ESTABLISHED,RELATED -j ACCEPT -m comment --comment \"Bestehende Verbindungen von extern - $INTERFACE\"" >> $PFAD/start.firewall.tmp
  echo "" >> $PFAD/start.firewall.tmp
  echo "# NAT einstellen" >> $PFAD/start.firewall.tmp
  echo "iptables -t nat -A POSTROUTING -o $INTERFACE -j SNAT --to-source $IP" >> $PFAD/start.firewall.tmp
  echo "" >> $PFAD/start.firewall.tmp
  echo "" >> $PFAD/start.firewall.tmp
done

# Script-Header erstellen
echo "#!/bin/bash" > $PFAD/start.sh
echo "############################################################" >> $PFAD/start.sh
echo "# Router Webinterface                                      #" >> $PFAD/start.sh
echo "# Copyright (C) 2010 Torsten Amshove <torsten@amshove.net> #" >> $PFAD/start.sh
echo "############################################################" >> $PFAD/start.sh
echo "# Dieses Script wird automatisch durch configure.sh erstellt" >> $PFAD/start.sh
echo "# Manuelle aenderungen werden ueberschrieben!" >> $PFAD/start.sh
echo "############################################################" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "MYSQL_USER=\"mx_router\"" >> $PFAD/start.sh
echo "MYSQL_PW=\`cat $PFAD/etc/mysql.passwd\`" >> $PFAD/start.sh
echo "MYSQL_DB=\"mx_router\"" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "# Alte Eintraege in der DB achivieren" >> $PFAD/start.sh
echo "echo \"UPDATE history SET active = 0, del_user = 'start_script', del_date = '\`date +%s\`' WHERE active = 0\" | mysql -u \$MYSQL_USER --password=\$MYSQL_PW \$MYSQL_DB" >> $PFAD/start.sh
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
echo "ip route add default via $GW dev $INTERFACE src $IP" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
echo "# FIREWALL" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
cat $FW_TMPL >> $PFAD/start.sh
cat $PFAD/start.firewall.tmp >> $PFAD/start.sh
cat $PFAD/etc/additional_rules.conf >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "" >> $PFAD/start.sh
echo "###################" >> $PFAD/start.sh
echo "# Forwarding aktivieren" >> $PFAD/start.sh
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

rm $PFAD/start.routing.tmp
rm $PFAD/stop.routing.tmp
rm $PFAD/start.firewall.tmp

chown root:root $PFAD/start.sh
chmod 744 $PFAD/start.sh
chown root:root $PFAD/stop.sh
chmod 744 $PFAD/stop.sh
