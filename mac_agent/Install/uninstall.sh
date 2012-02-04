#!/bin/sh

echo "checking user..."
EffUser=`id -u -n`
if test "${EffUser}" != "root" 
then
	echo "This script needs to be run with root privileges. You are running as ${EffUser}"
	echo "If run with lower privileges, some portions of the program will remain on your system"
	echo "Please use sudo and an administrative account to remove the Syslist Agent with the command:"
	echo ""
	echo "    sudo uninstall.sh"
	
	exit 1
fi

echo "killing Syslist Daemons ..."
killall -TERM SCATimeEx

echo "removing application and system receipts..."
rm -rf /Library/StartupItems/SyslistExecutionTimer
rm -rf /Library/Receipts/SCA_Install*.pkg
rm -rf "/Applications/Syslist Companion Agent"

echo "The Syslist Agent has been removed from your system"

exit 0