#!/bin/bash

ip addr flush eth1
ip addr flush eth2
#ip addr flush eth3
ip route flush table kamp
ip route flush table kamphome
ip route flush table peter
ip link set eth1 down
ip link set eth2 down
#ip link set eth3 down


ip addr add 192.168.2.2/24 dev eth1 # Kamp Buero
ip addr add 192.168.2.3/24 dev eth2 label eth2:1 # Kamp Home
ip addr add 192.168.1.2/24 dev eth2 # Peter
ip link set eth1 up
ip link set eth2 up
#ip link set eth3 up
ip route add default via 192.168.2.1 dev eth1 src 192.168.2.2

ip route add 10.10.0.0/20 src 10.10.1.1 dev eth0 table kamp
ip route add 192.168.2.0/24 src 192.168.2.2 dev eth1 table kamp
#ip route add 192.168.2.0/24 src 192.168.2.2 dev eth2 table kamp
#ip route add 192.168.20.0/24 src 192.168.20.220 dev eth3 table kamp
ip route add default via 192.168.2.1 src 192.168.2.2 dev eth1 table kamp

ip route add 10.10.0.0/20 src 10.10.1.1 dev eth0 table kamphome
ip route add 192.168.2.0/24 src 192.168.2.3 dev eth2:1 table kamphome
#ip route add 192.168.2.0/24 src 192.168.2.2 dev eth2 table koch
#ip route add 192.168.20.0/24 src 192.168.20.220 dev eth3 table koch
ip route add default via 192.168.2.1 src 192.168.2.3 dev eth2:1 table kamphome

ip route add 10.10.0.0/20 src 10.10.1.1 dev eth0 table peter
ip route add 192.168.1.0/24 src 192.168.1.2 dev eth2 table peter
#ip route add 192.168.2.0/24 src 192.168.2.2 dev eth2 table peter
#ip route add 192.168.20.0/24 src 192.168.20.220 dev eth3 table peter
ip route add default via 192.168.1.1 src 192.168.1.2 dev eth2 table peter

ip rule add from 10.10.0.0/20 table kamp
ip rule add from 10.10.0.0/24 table peter
ip rule add from 10.10.1.0/24 table peter
ip rule add from 10.10.10.0/24 table kamp

# Fuer Leitungs-Test
ip route add 80.237.237.160 via 192.168.2.1 dev eth1 src 192.168.2.2
ip route add 80.237.237.161 via 192.168.2.1 dev eth2:1 src 192.168.2.3
ip route add 178.77.78.40 via 192.168.1.1  dev eth2 src 192.168.1.2
