#include "stdafx.h"

#include <string>
using namespace std;

TCHAR * ServiceName = TEXT("InstallService");

DWORD WINAPI ServiceMain()
{
	return ERROR_SUCCESS;
};

// Stub initialization function. 
DWORD WINAPI ServiceInit(DWORD argc, LPTSTR  *argv, 
    DWORD *specificError) 
{ 
 
    return NO_ERROR; 
} 

VOID WINAPI ServiceCtrlHandler (DWORD opcode)
{

}

long ExecuteInstaller(string InstallerPath, string InstallerArgs)
{
	BOOL CreateState;
	STARTUPINFO CollectStartup;
	PROCESS_INFORMATION CollectInfo;

	ZeroMemory(&CollectStartup, sizeof (STARTUPINFO));
	CollectStartup.cb = sizeof (STARTUPINFO);
	
	CreateState = CreateProcess(
			InstallerPath.c_str(), // App Name/path
			(char *) InstallerArgs.c_str(), // Command Line
			NULL, // Proc Security
			NULL, // Thread Security
			FALSE,  // Inherit Handles
			CREATE_NO_WINDOW | NORMAL_PRIORITY_CLASS, // Flags
			NULL, // Environ
			NULL, // Current Directory
			& CollectStartup, // Startup Info
			& CollectInfo); // Return Proc Info.

	if (CreateState == FALSE) {
		return ERROR_GEN_FAILURE;
	}

	DWORD WaitReturn;

	WaitReturn = WaitForSingleObject( CollectInfo.hProcess, 10000);

	if (WaitReturn != WAIT_OBJECT_0) {
		switch (WaitReturn) {
		case WAIT_TIMEOUT: 
			break;
		default:
			break;
		}

		return ERROR_GEN_FAILURE;
	}
	
	long ReturnValue;
	GetExitCodeProcess (CollectInfo.hProcess, (PDWORD) &ReturnValue);
	
	if (ReturnValue != 0) {
		return ReturnValue;
	}

	return ERROR_SUCCESS;
}