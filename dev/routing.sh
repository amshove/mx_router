#!/bin/bash

ip addr flush eth1
ip addr flush eth2
ip route flush table kamp
ip route flush table koch
ip link set eth1 down
ip link set eth2 down


ip addr add 192.168.0.2/24 dev eth1
ip addr add 192.168.1.2/24 dev eth2
ip link set eth1 up
ip link set eth2 up
ip route add default via 192.168.0.1 dev eth1 src 192.168.0.2

ip route add 10.10.0.0/20 src 10.10.1.1 dev eth0 table kamp
ip route add 192.168.0.0/24 src 192.168.0.2 dev eth1 table kamp
ip route add 192.168.1.0/24 src 192.168.1.2 dev eth2 table kamp
ip route add default via 192.168.0.1 src 192.168.0.2 dev eth1 table kamp

ip route add 10.10.0.0/20 src 10.10.1.1 dev eth0 table koch
ip route add 192.168.0.0/24 src 192.168.0.2 dev eth1 table koch
ip route add 192.168.1.0/24 src 192.168.1.2 dev eth2 table koch
ip route add default via 192.168.1.1 src 192.168.1.2 dev eth2 table koch

ip rule add from 10.10.0.0/20 table kamp
ip rule add from 10.10.0.0/24 table koch
ip rule add from 10.10.1.0/24 table koch
ip rule add from 10.10.10.0/24 table koch

# Fuer Leitungs-Test
ip route add 80.237.237.160 via 192.168.0.1 src 192.168.0.2
ip route add 80.237.237.161 via 192.168.1.1 src 192.168.1.2
