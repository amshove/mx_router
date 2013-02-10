#!/bin/bash

echo "                                   _            "
echo " _ __ ___ __  __   _ __ ___  _   _| |_ ___ _ __ "
echo "| '_ \` _ \\\\ \/ /  | '__/ _ \| | | | __/ _ \ '__|"
echo "| | | | | |>  <   | | | (_) | |_| | ||  __/ |   "
echo "|_| |_| |_/_/\_\  |_|  \___/ \__,_|\__\___|_|   "
echo "                                                "
echo ""

if [ `cat /proc/sys/net/ipv4/ip_forward` -eq 1 ]; then
  STATUS="\033[32mrunning\033[0m"
else
  STATUS="\033[31mstopped\033[0m"
fi

echo -e "mx_router is $STATUS"
echo ""


NET=`ip route | grep eth0 | grep link | grep -v default | cut -d " " -f 1` # Sowas wie 10.10.0.0/20
IP=`ifconfig eth0 | grep 'inet ' | cut -d: -f2 | awk '{ print $1}'`
GW=`ip route | grep default | cut -d " " -f 3`
#echo -e "                     \033[4mLokales Netz\033[0m"
#echo ""
#echo -e "\033[5G | IP \033[23G | NET \033[44G | GATEWAY"
#echo "--------------------------------------------------------------"
echo -e "\033[5G | IP \033[23G | NET \033[44G | GATEWAY \033[62G | TABLE"
echo "-----------------------------------------------------------------------------"
echo -e "\033[35meth0\033[5G | $IP \033[23G | $NET \033[44G | $GW \033[62G | \033[0m"
#echo ""
#for D in `grep nameserver /etc/resolv.conf | cut -d " " -f 2`; do
#  echo "DNS: $D"
#done
#echo ""
#
#echo ""
#echo -e "                     \033[4mInternet-Leitungen\033[0m"
#echo ""
#echo -e "\033[5G | IP \033[23G | NET \033[44G | GATEWAY \033[62G | TABLE"
#echo "-----------------------------------------------------------------------------"
for ETH in `ifconfig | grep ^eth | grep -v eth0 | cut -d " " -f 1`; do
  NET=`ip route | grep $ETH | grep link | grep -v default | cut -d " " -f 1` # Sowas wie 10.10.0.0/20
  IP=`ifconfig $ETH | grep 'inet ' | cut -d: -f2 | awk '{ print $1}'`
  GW=""
  RT=""
  PING_IP=`ip route | grep $ETH | grep -v default | grep -v link | cut -d " " -f 1`

  for TABLE in `cat /etc/iproute2/rt_tables | grep -v "#" | tail -n +5 | cut -d " " -f 2`; do
    OUT=`ip route show table $TABLE | grep default | grep $ETH 2>&1`
    if [ $? -eq 0 ]; then
      GW=`echo $OUT | cut -d " " -f 3`
      RT=$TABLE
    fi
  done

  ping -c 1 -w 1 -I $ETH $PING_IP > /dev/null 2>&1
  if [ $? -eq 0 ]; then
    PING="\033[32m"
  else
    PING="\033[31m"
  fi

  echo -e "$PING$ETH \033[5G | $IP \033[23G | $NET \033[44G | $GW \033[62G | $RT \033[0m"
done
echo ""
for D in `grep nameserver /etc/resolv.conf | cut -d " " -f 2`; do
  echo "DNS: $D"
done
echo ""
