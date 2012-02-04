#!/bin/sh

# USAGE
# bsaremote <username> <password> <hostname file>

###########################
## Functions
###########################

######################################
## This function attempts to install to a single machine
## In the future it may be neccessary to turn this into a
## single expect script with an appropriate return value.
function InstallSingle () {

	## Assign inputs to named args
	USERNAME=$1
	PASSWORD=$2
	HOSTNAME=$3
	
	echo
	echo =================================================================
	echo 
	echo attempting to inventory machine: ${HOSTNAME} as user: ${USERNAME}
	echo
	
	## copy the file over to destination machine 
	## commands used:
	##		scp -R BSAUtil.app.zip ${USERNAME}@${HOSTNAME}:/ 
	echo "COPYING"
	expect -c "spawn scp BSAUtil.app.zip ${USERNAME}@${HOSTNAME}:/ ; \
		expect \
			-nocase \"are you sure you want to continue connecting (yes/no)? \$\" { send \"yes\n\" ; exp_continue } \
			-nocase \"password:\" { send \"${PASSWORD}\n\"; exp_continue} \
			-nocase \"permission denied\" { send_user \"PERMISSION DENIED\n\" ; exit 1 } \
			-nocase \"connection refused\" { send_user \"CONNECTION REFUSED (SSH DISABLED)\n\"; exit 1} \
			-nocase \"no such file or directory\" {send_user \"MISSING SOURCE FILE\n\" ; exit 1 } \
			timeout { send_user \"FAILURE\n\" ; exit 1} \
			eof { send_user \"COMPLETE\n\"; exit }"
	
	## Verify copy operation, exit on failure.
	if test $? -ne 0
	then
		echo 
		echo "COPY to ${HOSTNAME} FAILED($?)... aborted inventory"
		return
	fi
	
	echo "EXECUTING"
	## execute the script on the destination machine
	## commands used:
	##		ssh ${USERNAME}@${HOSTNAME} 
	##
	## command executed remotely after the above:
	##			unzip /BSAUtil.app.zip 
	##			/BSAUtil/contents/MacOS/BSAUtil	
	expect -c "set timeout 60 ; spawn ssh ${USERNAME}@${HOSTNAME}; \
		expect \
			-nocase \"are you sure you want to continue connecting (yes/no)? \$\" { send \"yes\n\" ; exp_continue } \
			-nocase \"password:\" { send \"${PASSWORD}\nunzip -o /BSAUtil.app.zip -d / \n/BSAUtil.app/Contents/MacOS/BSAUtil\rexit\n\"; exp_continue} \
			timeout { send_user \"FAILURE\n\" ; exit 1} \
			eof { send_user \"COMPLETE\n\"; exit } "
			
	## Verify command execution, report and exit and failure
	if test $? -ne 0
	then
		echo
		echo "EXECUTION on ${HOSTNAME} FAILED($?)... aborted inventory"
		return
	fi
	
	echo 
	echo inventory for ${HOSTNAME} has SUCCEEDED!
	echo
	echo ----------------------------------------------
}

######################################
## Main Script Body
######################################

## check to make sure that we have all the needed arguments
if test $# -lt 3
then
	echo Need three aguments: \<username\> \<password\> \<hostname file\> 
	exit 1
fi

## Transfer input arguments to named arguments
USERNAME=$1
PASSWORD=$2
TARGETFILE=$3

## make sure the destination exists
if test ! -f $3
then
	echo Hostname file does not exist
	exit 1
fi

## make sure the source file exists
if test ! -f BSAUtil.app.zip
then
	echo Source File BSAUtil.app.zip does not exist in the local directory
	exit 1
fi

## iterate through the names in the file and execute the inventory
for HOSTNAME in `cat ${TARGETFILE}`
do
	InstallSingle ${USERNAME} ${PASSWORD} ${HOSTNAME}
done

exit 0