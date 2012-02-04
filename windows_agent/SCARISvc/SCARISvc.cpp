// SCARISvc.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
#include "..\TestService\ServiceSkelton.h"

#include <string>
#include <iostream.h>
#include <fstream.h>

using namespace std;

#define TRACE_LOG FALSE

const long INSTALL_WAIT = 1000 * 60 * 5;  // 5 minutes in milliseconds

TCHAR * ServiceName = TEXT("SCAInstallService");
string g_InstallerPath;
string g_InstallerArgs;
string g_UserName;
string g_Pwd;
string g_Domain;

///////////////////////////////////////////////////////////////////////////////////////////
extern 	BOOL TokenIsAdmin(HANDLE ExecHandle);

// Stub initialization function. 
DWORD WINAPI ServiceInit(DWORD argc, LPTSTR  *argv, 
    DWORD *specificError) 
{ 
 
	if (argc != 6) {
		* specificError = 10000 + argc;
		return ERROR_BAD_ARGUMENTS;
	}
	char ExpInstallerPath[2 * MAX_PATH];

	long Status;

	Status = ExpandEnvironmentStrings(argv[1], ExpInstallerPath, 2*MAX_PATH);

	if (Status == 0) {
		*specificError = 16;
		return Status;
	}

	g_InstallerPath = ExpInstallerPath;

	g_InstallerArgs = g_InstallerPath + " " + argv[2];

	g_UserName = argv[3];

	g_Pwd = argv[4];

	g_Domain = argv[5];

    return NO_ERROR; 
} 

VOID WINAPI ServiceCtrlHandler (DWORD opcode)
{

}

DWORD ExecuteInstaller(string & InstallerPath, string & InstallerArgs)
{
	HANDLE LogonToken;
	BOOL LogonState = FALSE;

#if TRACE_LOG
	::ofstream LogStream;
	LogStream.open("SCALastLog.txt");

	LogStream << g_InstallerPath.c_str() << endl;
	LogStream << g_InstallerArgs.c_str() << endl;
	LogStream << g_UserName.c_str() << endl;
	LogStream << g_Pwd.c_str() << endl;
#endif

	const char * DomainArg = NULL;

	if (g_Domain.length() > 0)
		DomainArg = g_Domain.c_str();

	LogonState = LogonUser((char *) g_UserName.c_str(), 
						   (char *) DomainArg, 
						   (char *) g_Pwd.c_str(),
						   LOGON32_LOGON_BATCH, 
						   LOGON32_PROVIDER_DEFAULT,
						   &LogonToken);

	// This is a last ditch effort to attempt an
	// interactive login of the the specified user
	if (LogonState == FALSE) {

		LogonState = LogonUser((char *) g_UserName.c_str(), 
							   (char *) DomainArg, 
							   (char *) g_Pwd.c_str(),
							   LOGON32_LOGON_INTERACTIVE, 
							   LOGON32_PROVIDER_DEFAULT,
							   &LogonToken);

		if (LogonState == FALSE) {
			g_ServiceStatus.dwServiceSpecificExitCode = 5;
			return GetLastError();
		}
	}


	if (TokenIsAdmin(LogonToken) == FALSE) {
			g_ServiceStatus.dwServiceSpecificExitCode = 6;
			return ERROR_MEMBER_NOT_IN_GROUP;
	}
	
	BOOL CreateState;
	STARTUPINFO CollectStartup;
	PROCESS_INFORMATION CollectInfo;

	ZeroMemory(&CollectStartup, sizeof (STARTUPINFO));
	CollectStartup.cb = sizeof (STARTUPINFO);
	
	CreateState = CreateProcessAsUser(
			LogonToken,
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
		g_ServiceStatus.dwServiceSpecificExitCode = 1;
		return GetLastError();
	}

	DWORD WaitReturn;

	WaitReturn = WaitForSingleObject( CollectInfo.hProcess, INSTALL_WAIT);

	if (WaitReturn != WAIT_OBJECT_0) {

		switch (WaitReturn) {

		case WAIT_TIMEOUT: 
			return ERROR_TIMEOUT;

		case WAIT_FAILED:
			return GetLastError();

		default:
			break;
		}
		g_ServiceStatus.dwServiceSpecificExitCode = 2;

		return E_FAIL;
	}
	

	BOOL ExitCodeRecieved = FALSE;

	long ReturnValue = -1;
	ExitCodeRecieved = GetExitCodeProcess (CollectInfo.hProcess, (PDWORD) &ReturnValue);
	
	if (ExitCodeRecieved == FALSE) {
		g_ServiceStatus.dwServiceSpecificExitCode = 3;
		return GetLastError();
	}

	if (ReturnValue != 0) {
		g_ServiceStatus.dwServiceSpecificExitCode = 4;
		return ReturnValue;
	}

#if TRACE_LOG
	LogStream << "Complete with code " << ReturnValue << endl;
#endif

	return ERROR_SUCCESS;
}

DWORD WINAPI ServiceMain()
{
	return ExecuteInstaller(g_InstallerPath, g_InstallerArgs);
};
