// SCASetup.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
#include <string>
using namespace std;

#include "RASUser.h"
#include "SyslistAcct.h"
#include "../TestInstConfig/SyslistRegistry.h"
#include "../TestInstConfig/ConfigTaskSched.h"
#include "WinACLUtil.h"

extern int __argc;
extern char ** __argv;

static TCHAR * USER_NAME = "SCAAutoRun";
static TCHAR * TIME_SERVICE_NAME = "SCATimedExecutor";
static TCHAR * TIME_SERVICE_EXEC = "SCATimEx.exe";
static const TCHAR * TIME_SERVICE_DEPENDs = "RpcSs\0Tcpip\0\0";     // RPC

static const long TASK_POLL_INTERVAL_MS = 250;
static const long SERVICE_WAIT_TIME_MS = 10000;

static const long CFG_STRING_LEN = 1024;

static char SyslistConfigInstall[CFG_STRING_LEN] = "";

static const long CONTROL_RIGHTS = GENERIC_READ | GENERIC_WRITE | GENERIC_EXECUTE | SPECIFIC_RIGHTS_ALL | DELETE | MAXIMUM_ALLOWED | GENERIC_ALL;

long ReadInstallDir()
{
	HKEY SyslistRegKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC_MAIN, NULL, KEY_READ, & SyslistRegKey);
	if (Status != ERROR_SUCCESS)
		return Status;

	char ReturnRegString[CFG_STRING_LEN];
	unsigned long ReturnSize;
	DWORD ReturnType;

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, NULL, NULL, &ReturnType, (LPBYTE) ReturnRegString , &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		strcpy(SyslistConfigInstall,ReturnRegString);

	RegCloseKey(SyslistRegKey);

	return ERROR_SUCCESS;
}

DWORD ErrorDepot()
{
	return GetLastError();
}


int APIENTRY WinMain(HINSTANCE hInstance,
                     HINSTANCE hPrevInstance,
                     LPSTR     lpCmdLine,
                     int       nCmdShow)
{

	CoInitialize(NULL);

	StartLocalTaskSched();
	DestroyCollectorTask();
	

	// This only needs to run on Windows NT platform based machines
	BOOL VersionOK;
	OSVERSIONINFO VersionInfo;

	VersionInfo.dwOSVersionInfoSize = sizeof (OSVERSIONINFO);

	VersionOK = GetVersionEx(&VersionInfo);
	if (VersionOK == FALSE)
		return 1;

	if (VersionInfo.dwPlatformId != VER_PLATFORM_WIN32_NT)
		return 0;

	BOOL IsInstall = TRUE;

	long CurrArg;

	for (CurrArg = 0; CurrArg < __argc; CurrArg ++) {
		if (!stricmp(__argv[CurrArg],"/U") || !stricmp(__argv[CurrArg],"-U"))
			IsInstall = FALSE;
	}

	CRasUser UserAdmin;
	BOOL UserSuccess;

	SC_HANDLE   hSC = NULL;
	SC_HANDLE   hSCSvc = NULL;

	hSC = OpenSCManager(NULL, NULL, SC_MANAGER_CREATE_SERVICE);
	if (hSC == NULL)
	{
		return 1;
	}

	hSCSvc = OpenService(hSC,
						 TIME_SERVICE_NAME,
						 SERVICE_ALL_ACCESS);

	// Delete previous attempts if needed
	// We don't care if this works or not, because
	// many times it will not work (e.g. first install)
	if (hSCSvc != NULL) {
		SERVICE_STATUS DeleteStatus;
		ControlService(hSCSvc, SERVICE_CONTROL_STOP, & DeleteStatus);

		long StopElapseTime = 0;
		BOOL StopPollOK = TRUE;

		do {
			if (StopElapseTime > SERVICE_WAIT_TIME_MS)
				break;

			StopElapseTime += TASK_POLL_INTERVAL_MS;

			Sleep(TASK_POLL_INTERVAL_MS);
			
			if (QueryServiceStatus(hSCSvc, &DeleteStatus) == FALSE)
				StopPollOK = FALSE;
		}
		
		while (StopPollOK && DeleteStatus.dwCurrentState == SERVICE_STOP_PENDING);

		DeleteService(hSCSvc);
		CloseServiceHandle(hSCSvc);
		hSCSvc = NULL;
	}

	if (IsInstall) {


		ReadInstallDir();

		UserSuccess = UserAdmin.AddUserAccount(USER_NAME);

		if (UserSuccess == FALSE)
			return 1;


		string AcctTempPath;
		AcctTempPath = SyslistConfigInstall;
		AcctTempPath.append("\\TEMP");

		if (!AddAccessRights(SyslistConfigInstall, USER_NAME, CONTROL_RIGHTS ))
			return 1;

		if (!AddAccessRights((char *) AcctTempPath.c_str(), USER_NAME, CONTROL_RIGHTS ))
			return 1;

		if (!AddRegAccessRights (HKEY_LOCAL_MACHINE, (char *) SYSLIST_REG_LOC_MAIN, USER_NAME, CONTROL_RIGHTS))
			return 1;

		if (!AddRegAccessRights (HKEY_LOCAL_MACHINE, (char *) SYSLIST_REG_LOC, USER_NAME, CONTROL_RIGHTS))
			return 1;

		char LocalSvcPath [2*MAX_PATH];
		_makepath(LocalSvcPath, NULL, SyslistConfigInstall, TIME_SERVICE_EXEC, NULL);


		hSCSvc =  CreateService(hSC,					// handle to SCM database 
							 TIME_SERVICE_NAME,				// name of service to start
							 "Syslist Companion Agent Timed Execution Service",     // display name
							 SERVICE_ALL_ACCESS,			// type of access to service
							 SERVICE_WIN32_OWN_PROCESS,		// type of service
							 SERVICE_AUTO_START,			// when to start service
							 SERVICE_ERROR_IGNORE,			// severity of service failure
							 LocalSvcPath,					// name of binary file
							 NULL,							// name of load ordering group
							 NULL,							// tag identifier
							 TIME_SERVICE_DEPENDs,					// array of dependency names
							 NULL,							// account name 
							 NULL );						// account password

		if (hSCSvc == NULL) {
			return 1;
		}

		const char * StartupArgs[4];

		StartupArgs[0] = "/i";

		if (StartService(hSCSvc, 1, StartupArgs) == FALSE)
		{
		  CloseServiceHandle(hSCSvc);
		  return GetLastError();
		}

		SERVICE_STATUS SvcStatus;
		BOOL PollOk = TRUE;
		long StartElapseTime = 0;

		do {
			if (StartElapseTime > SERVICE_WAIT_TIME_MS)
				break;

			StartElapseTime += TASK_POLL_INTERVAL_MS;

			Sleep(TASK_POLL_INTERVAL_MS);
			
			if (QueryServiceStatus(hSCSvc, &SvcStatus) == FALSE)
				PollOk = FALSE;
		}
		
		while (PollOk && SvcStatus.dwCurrentState == SERVICE_START_PENDING);

		if (SvcStatus.dwCurrentState != SERVICE_RUNNING)
			return 1;

	}
	else {
		UserSuccess = UserAdmin.RemoveUserAccount((TCHAR *)SYSLIST_ACCT_NAME);
		if (UserSuccess)
			return 1;
	}

	CoUninitialize();

	return 0;
}



