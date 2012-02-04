#!/bin/sh

# USAGE
# bsaretrieve <username> <password> <hostname file> <remote file path>

###########################
## Functions
###########################

######################################
## This function attempts to retrieve the data from a single machine
## In the future it may be neccessary to turn this into a
## single expect script with an appropriate return value.
function RetrieveSingle () {

	## Assign inputs to named args
	USERNAME=$1
	PASSWORD=$2
	HOSTNAME=$3
	REMOTEFILE=$4
	
	## For easy access
	LOCALFILE=DATA_${HOSTNAME}.xml
	
	echo
	echo =================================================================
	echo 
	echo attempting to retrieve from machine: ${HOSTNAME} as user: ${USERNAME} and file ${REMOTEFILE}
	echo
	
	## copy the file back from destination machine 
	## commands used:
	##		scp ${USERNAME}@${HOSTNAME}:${REMOTEFILE} ${LOCALFILE}
	echo "COPYING"
	expect -c "spawn scp \"${USERNAME}@${HOSTNAME}:${REMOTEFILE}\" ${LOCALFILE} ; \
		expect \
			-nocase \"are you sure you want to continue connecting (yes/no)? \$\" { send \"yes\n\" ; exp_continue } \
			-nocase \"password:\" { send \"${PASSWORD}\n\"; exp_continue} \
			-nocase \"permission denied\" { send_user \"PERMISSION DENIED\n\" ; exit 1 } \
			-nocase \"connection refused\" { send_user \"CONNECTION REFUSED (SSH DISABLED)\n\"; exit 1} \
			timeout { send_user \"FAILURE\n\" ; exit 1} \
			eof { send_user \"COMPLETE\n\"; exit }"
	
	## Verify copy operation, exit on failure.
	if test $? -ne 0 -o ! -f ${LOCALFILE}
	then
		echo 
		echo "COPY from ${HOSTNAME} FAILED($?)... aborted retrieval"
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
if test $# -lt 4
then
	echo Need four\(4\) aguments: \<username\> \<password\> \<hostname file\> \<remote file path\>
	exit 1
fi

## Transfer input arguments to named arguments
USERNAME=$1
PASSWORD=$2
TARGETFILE=$3
REMOTEFILE=$4

## make sure the destination exists
if test ! -f $3
then
	echo Hostname file does not exist
	exit 1
fi

## iterate through the names in the file and execute the inventory
for HOSTNAME in `cat ${TARGETFILE}`
do
	RetrieveSingle ${USERNAME} ${PASSWORD} ${HOSTNAME} ${REMOTEFILE}
done

exit 0