// SCANetUnInstall.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
#include "../TestInstConfig/SyslistRegistry.h"

#include <string>
using namespace std;

static const char * UNINST_EXEC = "Uninstall.exe";

static const long CFG_STRING_LEN = 512;
static char SyslistConfigInstall[CFG_STRING_LEN] = "";
static const UNINST_WAIT = 300000;


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

int APIENTRY WinMain(HINSTANCE hInstance,
                     HINSTANCE hPrevInstance,
                     LPSTR     lpCmdLine,
                     int       nCmdShow)
{
 	// TODO: Place code here.
	BOOL CreateState;
	STARTUPINFO CollectStartup;
	PROCESS_INFORMATION CollectInfo;


	long Status;

	Status = ReadInstallDir();
	if (Status != ERROR_SUCCESS)
		return -1;

	ZeroMemory(&CollectStartup, sizeof (STARTUPINFO));
	CollectStartup.cb = sizeof (STARTUPINFO);
	
	string UnInstPath = SyslistConfigInstall;
	UnInstPath += "\\";
	UnInstPath += UNINST_EXEC;

	string UnInstArgs;
	UnInstArgs = UNINST_EXEC;
	UnInstArgs += " /S";

	CreateState = CreateProcess(
			UnInstPath.c_str(), // App Name/path
			(char *) UnInstArgs.c_str(), // Command Line
			NULL, // Proc Security
			NULL, // Thread Security
			FALSE,  // Inherit Handles
			CREATE_NO_WINDOW | NORMAL_PRIORITY_CLASS, // Flags
			NULL, // Environ
			NULL, // Current Directory
			& CollectStartup, // Startup Info
			& CollectInfo); // Return Proc Info.

	if (CreateState == FALSE) {
		//LocaleMsgBox(NULL, IDS_ERR_COLLECT_NOT_RUN, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
		return -2;
	}

	DWORD WaitReturn;

	WaitReturn = WaitForSingleObject( CollectInfo.hProcess, UNINST_WAIT);

	if (WaitReturn != WAIT_OBJECT_0) {
		switch (WaitReturn) {
		case WAIT_TIMEOUT: 
			//LocaleMsgBox(NULL, IDS_ERR_COLLECT_HANG, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
			return -3;
		default:
			//LocaleMsgBox(NULL, IDS_ERR_COLLECT_NOT_RUN, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
			return -4;
		}

		return -5;
	}
	
	long ReturnValue;
	GetExitCodeProcess (CollectInfo.hProcess, (PDWORD) &ReturnValue);
	
	if (ReturnValue != 0) {
		return -6;
	}

	_unlink (SyslistConfigInstall);

	return ERROR_SUCCESS;
}



