mx_router
=========
Packet Flow: http://xkr47.outerspace.dyndns.org/netfilter/packet_flow/

Installation
------------
- Ubuntu 16.04 installieren
- In der Datei /etc/network/interfaces das Interface fuer das lokale Netzwerk einstellen - alle anderen Interfaces muessen unkonfiguriert bleiben  
WICHTIG: Es darf kein Interface auf dhcp eingestellt sein!  
``auto eth0``  
``iface eth0 inet static``  
``  address 10.10.1.1``  
``  netmask 255.255.240.0``  

Waehrend der Installation muss der Server Zugriff auf das Internet haben, um Pakete installieren zu koennen. Dazu kannst du DHCP manuell verwenden: sudo dhclient eth0
- mx_router.tar.gz auf den Server kopieren
- Datei entpacken  
``tar xvzf mx_router-*.tar.gz``
- In das Verzeichnis wechseln  
``cd mx_router``
- Installation starten  
``sudo ./install.sh``  
Das Script deinstalliert resolvconf und apparmor, da diese stoeren wuerden.  
Die Meldung von resolvconf einfach mit OK bestaetigen.  
  
Dann werden einige Pakete installiert.  
Bei der Installation von MySQL wird ein PW abgefragt, das fuer MySQL als root-PW eingerichtet werden soll.  
Hier kann man sich ein sicheres PW aussuchen - der router selbst bekommt spaeter einen eigenen User.  
  
Nach der Installation der Ubuntu-Pakete wird das System konfiguriert.  
Es wird nach einem lokalen DNS-Server gefragt - hier traegt man die IP von dem Server ein, wenn es einen gibt - ansonsten mit Enter bestaetigen.  
Dann wird nach der lokalen DNS-Domain gefragt. Diese wird als search-Domain eingetragen - auch dieses kann man mit Enter uebserspringen.  
Es wird nochmals das MySQL-root-PW abgefragt. Das wird vom Script verwendet, um einen eigenen User anzulegen.  
  
Am Ende der Konfiguration muss der Server neu gestartet werden.  
Das passiert automatisch, nachdem man das Ende des Scriptes mit Enter bestaetigt.  
  
- Einrichtung  
Zur Einrichtung nach dem Neustart folgende Datei lesen: /opt/mx_router/README.txt  
