# other definitions
IFext="eth1"
IFint="eth0"
lannet="192.168.2.0/24"
admins="192.168.2.2/32"

logger -t iptables Setting default policies
# chain policies
# drop everything and open stuff as necessary
/sbin/iptables -P INPUT DROP
/sbin/iptables -P FORWARD DROP
/sbin/iptables -P OUTPUT ACCEPT

logger -t iptables Flushing tables
/sbin/iptables -F
/sbin/iptables -F INPUT
/sbin/iptables -F OUTPUT
/sbin/iptables -F FORWARD
/sbin/iptables -F -t mangle
/sbin/iptables -F -t nat
/sbin/iptables -X
/sbin/iptables -Z

logger -t iptables Creating user tables + rules
# create DUMP table
/sbin/iptables -N DUMP
/sbin/iptables -F DUMP
/sbin/iptables -A DUMP -p icmp -m limit --limit 1/m --limit-burst 5 -j LOG --log-level 6 --log-prefix "IPT ICMPDUMP: "
/sbin/iptables -A DUMP -p tcp -m limit --limit 1/m --limit-burst 5 -j LOG --log-level 6 --log-prefix "IPT TCPDUMP: "
/sbin/iptables -A DUMP -p udp -m limit --limit 6/h --limit-burst 5 -j LOG --log-level 6 --log-prefix "IPT UDPDUMP: "
/sbin/iptables -A DUMP -p tcp -j REJECT --reject-with tcp-reset
/sbin/iptables -A DUMP -p udp -j REJECT --reject-with icmp-port-unreachable
/sbin/iptables -A DUMP -j DROP

# Stateful table
/sbin/iptables -N STATEFUL
/sbin/iptables -F STATEFUL
/sbin/iptables -I STATEFUL -m state --state ESTABLISHED,RELATED -j ACCEPT
/sbin/iptables -A STATEFUL -j DUMP

# SSH protection table
#/sbin/iptables -N SSH
#/sbin/iptables -F SSH
#/sbin/iptables -A SSH ! -i ${IFext} -j RETURN
#/sbin/iptables -A SSH --source $admins -j RETURN
#/sbin/iptables -A SSH -m recent --name SSH --set --rsource
#/sbin/iptables -A SSH -m recent ! --rcheck --seconds 60 --hitcount 3 --name SSH --rsource -j RETURN
#/sbin/iptables -A SSH -j DUMP

# SYN protection table
/sbin/iptables -N SYN-FLOOD
/sbin/iptables -F SYN-FLOOD
/sbin/iptables -A SYN-FLOOD -m limit --limit 1/s --limit-burst 4 -j RETURN
/sbin/iptables -A SYN-FLOOD -j DROP

/sbin/iptables -A INPUT -p tcp --syn -j SYN-FLOOD
/sbin/iptables -A INPUT -p tcp ! --syn -m state --state NEW -j DROP

# watch out for fragments
/sbin/iptables -A INPUT -f -j LOG --log-prefix "IPT FRAGMENTS: "
/sbin/iptables -A INPUT -f -j DROP

logger -t iptables Setting input/output rules
# allow loopback in
/sbin/iptables -A INPUT -i lo -j ACCEPT
# Output steht eh auf ACCEPT

logger -t iptables Setting ICMP rules
# allow certain inbound ICMP types (on *any* interface)
/sbin/iptables -A INPUT -p icmp --icmp-type echo-reply -j ACCEPT
/sbin/iptables -A INPUT -p icmp --icmp-type destination-unreachable -j ACCEPT
/sbin/iptables -A INPUT -p icmp --icmp-type source-quench -j ACCEPT
/sbin/iptables -A INPUT -p icmp --icmp-type echo-request -j ACCEPT
/sbin/iptables -A INPUT -p icmp --icmp-type time-exceeded -j ACCEPT
/sbin/iptables -A INPUT -p icmp --icmp-type parameter-problem -j ACCEPT

logger -t iptables Setting TCP/UDP rules
# opened ports
#/sbin/iptables -A INPUT -p tcp --dport ssh -m state --state NEW -j SSH
/sbin/iptables -A INPUT -p tcp --source $admins --dport ssh -j ACCEPT
/sbin/iptables -A INPUT -p tcp -i $IFext --dport ssh -j ACCEPT
/sbin/iptables -A INPUT -p tcp --source $admins --dport http -j ACCEPT
/sbin/iptables -A INPUT -p tcp -i $IFext --dport http -j ACCEPT
/sbin/iptables -A INPUT -p tcp --sport ntp --dport ntp -j ACCEPT
/sbin/iptables -A INPUT -p udp --sport ntp --dport ntp -j ACCEPT

logger -t classifying packets for shaping
#voip="192.168.3.3"
# classify packets
# give "overhead" packets highest priority (VoIP, too)
#iptables -t mangle -A POSTROUTING -o ${IFext} --source ${voip} -j CLASSIFY --set-class 1:10
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --syn -m length --length 40:68 -j CLASSIFY --set-class 1:10
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --tcp-flags ALL SYN,ACK -m length --length 40:68 -j CLASSIFY --set-class 1:10
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --tcp-flags ALL ACK -m length --length 40:100 -j CLASSIFY --set-class 1:10
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --tcp-flags ALL RST -j CLASSIFY --set-class 1:10
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --tcp-flags ALL ACK,RST -j CLASSIFY --set-class 1:10
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --tcp-flags ALL ACK,FIN -j CLASSIFY --set-class 1:10
# interactive SSH traffic
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --sport ssh -m length --length 40:100 -j CLASSIFY --set-class 1:20
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --dport ssh -m length --length 40:100 -j CLASSIFY --set-class 1:20
# interactive mail or web traffic
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp -m multiport --sport http,imap,https,imaps -j CLASSIFY --set-class 1:30
# dns lookups
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --dport domain -j CLASSIFY --set-class 1:30
# ICMP, UDP
iptables -t mangle -A POSTROUTING -o ${IFext} -p udp -j CLASSIFY --set-class 1:40
iptables -t mangle -A POSTROUTING -o ${IFext} -p icmp -m length --length 28:1500 -m limit --limit 2/s --limit-burst 5 -j CLASSIFY --set-class 1:40
# bulk traffic
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --sport ssh -m length --length 101: -j CLASSIFY --set-class 1:50
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --dport ssh -m length --length 101: -j CLASSIFY --set-class 1:50
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --sport 25 -j CLASSIFY --set-class 1:50
iptables -t mangle -A POSTROUTING -o ${IFext} -p tcp --dport 6667 -j CLASSIFY --set-class 1:50

#logger -t iptables Setting port forwarding
#dip="192.168.3.10"
#bitstart="6881"
#bitend="6999"
#
# override stateful table
#/sbin/iptables -A FORWARD -i ${IFext} -o ${IFint} -j ACCEPT
#
# bittorrent
#/sbin/iptables -t nat -A PREROUTING -p tcp -i ${IFext} --dport 3724 -j DNAT --to ${dip}
#/sbin/iptables -A FORWARD -s ${dip} -p tcp --dport 3724 -j ACCEPT
#
#/sbin/iptables -t nat -A PREROUTING -p tcp -i ${IFext} --dport 6112 -j DNAT --to ${dip}
#/sbin/iptables -A FORWARD -s ${dip} -p tcp --dport 6112 -j ACCEPT
#
#/sbin/iptables -t nat -A PREROUTING -p tcp -i ${IFext} --dport ${bitstart}:${bitend} -j DNAT --to ${dip}
#/sbin/iptables -A FORWARD -s ${dip} -p tcp --dport ${bitstart}:${bitend} -j ACCEPT

logger -t iptables Finish up
# push everything else to state table
/sbin/iptables -A INPUT -j STATEFUL

# Alles was zurueck kommt und zu einer Verbindung gehoert erlauben
/sbin/iptables -A FORWARD -i $IFext -m state --state ESTABLISHED,RELATED -j ACCEPT

logger -t iptables Turning on NAT
# masquerade from internal network
/sbin/iptables -t nat -A POSTROUTING -s ${lannet} -o ${IFext} -j MASQUERADE
