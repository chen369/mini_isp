#! /bin/sh
# this will put us on the default windows network that starts
# at 192.168.0.1 (windows side) that we use to temp connect to 
# a telephone modem network.  use wizard on windows to setup network
# share then run this command to add alias ip address.
# we should still be on the 192.168.1.101 address as eth0 so we 
# can switch back when the adsl starts working again.
# note.. I had to add 192.168.0.1 to /etc/resolv.conf
if route |grep -q "true" ; then
  echo "found true no need to change"
  #exit 
else
  echo "no true found in route will modify"
fi

sudo killall NetworkManager
sudo ifconfig eth0 down
sudo ifconfig eth0 192.168.2.250
sudo route del default
sudo route add default gw router
sudo cp /etc/resolv.conf.true /etc/resolv.conf
