#!/bin/sh

tables=$(cat /proc/net/ip_tables_names 2>/dev/null)

for i in $tables ; do
    echo table $i
    iptables -t $i -L -n
    echo
done

