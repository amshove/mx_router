#!/bin/sh
#######################################################
# -------------------- mx_router -------------------- #
# Copyright (C) Torsten Amshove <torsten@amshove.net> #
# See: http://www.amshove.net                         #
#######################################################

tables=$(cat /proc/net/ip_tables_names 2>/dev/null)

for i in $tables ; do
    echo "##########################"
    echo "##### Table: $i"
    echo "##########################"
    iptables -t $i -L -n -v
    echo
done

