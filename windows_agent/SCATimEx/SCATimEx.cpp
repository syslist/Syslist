// SCATimEx.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
#include "..\TestService\ServiceSkelton.h"
#include "..\TestCollect\AutoRegKey.h"
#include "..\TestInstConfig/SyslistRegistry.h"
#include "..\SCASetup\SyslistAcct.h"
#include "../TestInstConfig/SyslistMethod.h"

#include <string>
#include <iostream.h>
#include <fstream.h>

using namespace std;

#define PRODUCTION TRUE

#if PRODUCTION
static const UINT POLL_TIMER_MS = 1000 * 60 * 60 * 4; // 4 hours in milliseconds
#else
static const UINT POLL_TIMER_MS = 2000 * 60 ; // 1 minute in milliseconds
#endif

static const long CFG_STRING_LEN =1024;

static const char * COLLECTOR_EXEC = "SCAInv.exe";

static SYSTEMTIME SyslistConfigLastRun = {0};
static SYSTEMTIME SyslistConfigLastRunAlign = {0};
static CmdMethodIndex SyslistConfigMethod = MethodDisable;
static char SyslistConfigInstall[CFG_STRING_LEN] = "";

static const __int64 OneSecond = 1e7L;     // 1 Second in 100 nS units
static const __int64 OneMinute = OneSecond * 60;
static const __int64 OneHour = OneMinute * 60;
static const __int64 OneDay = OneHour * 24;
static const __int64 OneWeek = OneDay * 7;
static const __int64 OneMonth = OneDay * 30;

static const __int64 BufferTime = 10 * OneSecond;
static const __int64 DebugTime = OneMinute * 3;

static long serial = -1;

static BOOL IsInstall;

TCHAR * ServiceName = TEXT("SCATimedExecution");

// I don't like it as a global, and I'll probably change this
// in later revs.
::ofstream LogStream;

// Stub initialization function. 
DWORD WINAPI ServiceInit(DWORD argc, LPTSTR  *argv, 
    DWORD *specificError) 
{ 

	int ArgIndex;

	for (ArgIndex = 0; ArgIndex < argc; ArgIndex ++) {
		if ( stricmp (argv[ArgIndex],"/i") == 0
			|| stricmp (argv[ArgIndex], "-i") == 0)

			IsInstall = TRUE;

	}

    return NO_ERROR; 
} 

VOID WINAPI ServiceCtrlHandler (DWORD opcode)
{
	switch (opcode) {
	case SERVICE_CONTROL_SHUTDOWN: //Requests the service to perform cleanup tasks, because the system is shutting down. 
	case SERVICE_CONTROL_STOP: // Requests the service to stop.
		PostMessage (NULL, WM_QUIT, 0, 0);

		g_ServiceStatus.dwCurrentState       = SERVICE_STOPPED; 
		g_ServiceStatus.dwCheckPoint         = 0;
		g_ServiceStatus.dwWaitHint           = 0;
		g_ServiceStatus.dwWin32ExitCode      = 0;
		g_ServiceStatus.dwServiceSpecificExitCode = 0;

		SetServiceStatus (g_ServiceStatusHandle, &g_ServiceStatus); 

		break;

	case SERVICE_CONTROL_PAUSE: //Requests the service to pause.  
		break;

	case SERVICE_CONTROL_CONTINUE: //Requests the paused service to resume.  
		break;

	case SERVICE_CONTROL_INTERROGATE: //Requests the service to update immediately its current status information to the service control manager.  
		break;

	default:
		break;
	}

}

long ReadInstallDir()
{
	AutoRegKey SyslistRegKey;
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

	return ERROR_SUCCESS;
}

long ReadRegInfo()
{

	AutoRegKey SyslistRegKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_READ, & SyslistRegKey);
	if (Status != ERROR_SUCCESS)
		return Status;

	char ReturnRegString[CFG_STRING_LEN];
	unsigned long ReturnSize;
	DWORD ReturnType;
	
	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_METHOD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		SyslistConfigMethod = MethodFromString(ReturnRegString);
	else
		SyslistConfigMethod = MethodDisable;

	ReturnSize = sizeof(SYSTEMTIME);
	Status = RegQueryValueEx(SyslistRegKey, REG_LASTRUN_VALUE, NULL, &ReturnType, (LPBYTE) &SyslistConfigLastRun, &ReturnSize);
	if (Status != ERROR_SUCCESS || ReturnSize != sizeof(SYSTEMTIME) || ReturnType != REG_BINARY)
		ZeroMemory(&SyslistConfigLastRun, sizeof(SYSTEMTIME));	
	
	ReturnSize = sizeof(SYSTEMTIME);
	Status = RegQueryValueEx(SyslistRegKey, REG_LASTRUN_ALIGN_VALUE, NULL, &ReturnType, (LPBYTE) &SyslistConfigLastRunAlign, &ReturnSize);
	if (Status != ERROR_SUCCESS || ReturnSize != sizeof(SYSTEMTIME) || ReturnType != REG_BINARY)
		ZeroMemory(&SyslistConfigLastRunAlign, sizeof(SYSTEMTIME));

	return ERROR_SUCCESS;

}

long WriteRegInfo()
{
	AutoRegKey SyslistRegKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_WRITE, & SyslistRegKey);
	if (Status != ERROR_SUCCESS)
		return Status;

	Status = RegSetValueEx (SyslistRegKey, REG_LASTRUN_ALIGN_VALUE, NULL, REG_BINARY, (BYTE *) & SyslistConfigLastRunAlign, sizeof (SYSTEMTIME));
	
	return ERROR_SUCCESS;
}

DWORD ExecuteCollector()
{
	HANDLE LogonToken;
	BOOL LogonState = FALSE;

	BOOL VersionOK;
	OSVERSIONINFOEX WinVer;

	WinVer.dwOSVersionInfoSize = sizeof (OSVERSIONINFOEX);
	VersionOK = GetVersionEx((LPOSVERSIONINFO) &WinVer);

	if (VersionOK == FALSE)
		return GetLastError();

	BOOL CreateState;
	STARTUPINFO CollectStartup;
	PROCESS_INFORMATION CollectInfo;

	ZeroMemory(&CollectStartup, sizeof (STARTUPINFO));
	CollectStartup.cb = sizeof (STARTUPINFO);
	
	char CollectorPath[2*MAX_PATH];
	_makepath(CollectorPath, NULL, SyslistConfigInstall, COLLECTOR_EXEC, NULL);

	if (WinVer.dwPlatformId == VER_PLATFORM_WIN32_WINDOWS) {
		CreateState = CreateProcess(
				CollectorPath, // App Name/path
				(char *) "", // Command Line
				NULL, // Proc Security
				NULL, // Thread Security
				FALSE,  // Inherit Handles
				CREATE_NO_WINDOW | NORMAL_PRIORITY_CLASS, // Flags
				NULL, // Environ
				NULL, // Current Directory
				& CollectStartup, // Startup Info
				& CollectInfo); // Return Proc Info.

		LogStream << "WIN32_WINDOWS exec Path";
	}	

	else if (WinVer.dwPlatformId == VER_PLATFORM_WIN32_NT) {
#if 0
		LogonState = LogonUser((char *) SYSLIST_ACCT_NAME, 
							   NULL, 
							   (char *) SYSLIST_ACCT_PWD,
							   LOGON32_LOGON_BATCH, 
							   LOGON32_PROVIDER_DEFAULT,
							   &LogonToken);

		if (LogonState == FALSE) {
			g_ServiceStatus.dwServiceSpecificExitCode = 5;
			return GetLastError();
		}

		CreateState = CreateProcessAsUser(
				LogonToken,
				CollectorPath, // App Name/path
				(char *) "", // Command Line
				NULL, // Proc Security
				NULL, // Thread Security
				FALSE,  // Inherit Handles
				CREATE_NO_WINDOW | NORMAL_PRIORITY_CLASS, // Flags
				NULL, // Environ
				NULL, // Current Directory
				& CollectStartup, // Startup Info
				& CollectInfo); // Return Proc Info.
#endif
		CreateState = CreateProcess(
				CollectorPath, // App Name/path
				(char *) "", // Command Line
				NULL, // Proc Security
				NULL, // Thread Security
				FALSE,  // Inherit Handles
				CREATE_NO_WINDOW | NORMAL_PRIORITY_CLASS, // Flags
				NULL, // Environ
				NULL, // Current Directory
				& CollectStartup, // Startup Info
				& CollectInfo); // Return Proc Info.
		
		LogStream << "WIN32_NT exec Path";

	}

	if (CreateState == FALSE) {
		DWORD lastError= 0;

		g_ServiceStatus.dwServiceSpecificExitCode = 1;

		lastError = GetLastError();

		LogStream << "FAILED EXEC with GetLastError = " << lastError << endl;

		return lastError;
	}

	DWORD WaitReturn;
	DWORD lastError;

	WaitReturn = WaitForSingleObject( CollectInfo.hProcess, 300000);

	if (WaitReturn != WAIT_OBJECT_0) {

		switch (WaitReturn) {

		case WAIT_TIMEOUT: 
			LogStream << "Timeout While Collecting information (5 minutes!)\n";
			return ERROR_TIMEOUT;

		case WAIT_FAILED:
			lastError = GetLastError();
			LogStream << "General Wait Failure during execution (WAIT_FAILED) Error = " << lastError << endl;
			return lastError;

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
		lastError = GetLastError();
		LogStream << "Unable to get exit code, Error = " << lastError << endl;
		return lastError;
	}

	if (ReturnValue != 0) {
		g_ServiceStatus.dwServiceSpecificExitCode = 4;
		LogStream << "Failure Return Value " << ReturnValue << endl;
		return ReturnValue;
	}

	WriteRegInfo();

	return ERROR_SUCCESS;
}

void LocalTimeTo64BitTime( SYSTEMTIME * InputTime, __int64  & Destination )
{
    FILETIME ftNow;
    ::SystemTimeToFileTime(InputTime, &ftNow );

	LARGE_INTEGER TempDest;

    TempDest.LowPart = ftNow.dwLowDateTime;
    TempDest.HighPart = ftNow.dwHighDateTime;

	Destination = TempDest.QuadPart;
}

void _64BitTimeToLocalTime ( __int64 & InputTime,  SYSTEMTIME * Destination )
{
	FILETIME ftConvert;

	LARGE_INTEGER TempInput;
	
	TempInput.QuadPart = InputTime;

	ftConvert.dwLowDateTime = TempInput.LowPart;
	ftConvert.dwHighDateTime = TempInput.HighPart;

	::FileTimeToSystemTime(&ftConvert, Destination);
}

BOOL CheckTime(BOOL IsStartup, SYSTEMTIME & STNextTime, __int64 &DeltaCompare)
{
	
	if (ReadRegInfo() != ERROR_SUCCESS)
		return FALSE;

	SYSTEMTIME CurrSystemTime = {0};
	GetSystemTime (&CurrSystemTime);

	__int64 LastRunTime;
	__int64 LastRunAlignTime;
	__int64 CurrTime;
	__int64 NextTime;

	if (SyslistConfigLastRunAlign.wYear != 0)
		LocalTimeTo64BitTime(&SyslistConfigLastRunAlign, LastRunAlignTime);

	LocalTimeTo64BitTime(&SyslistConfigLastRun, LastRunTime);
	LocalTimeTo64BitTime(&CurrSystemTime, CurrTime);


	switch (SyslistConfigMethod) {

	case MethodDisable:  // Never Run
		DeltaCompare = 0;
		return FALSE;

	case MethodStartup:
		DeltaCompare = 0;

		if (IsStartup)
			return TRUE;
		else
			return FALSE;

	case MethodDay:
		DeltaCompare = OneDay;

		break;

	case MethodWeek:
		DeltaCompare = OneWeek;

		break;

	case MethodMonth:
		DeltaCompare = OneMonth;

		break;

	case MethodDebug:
		DeltaCompare = DebugTime;
		break;

	default:
		DeltaCompare = -1;
		return FALSE;
	}

	if (SyslistConfigLastRunAlign.wYear != 0)
		NextTime = LastRunAlignTime + DeltaCompare;
	else
		NextTime = LastRunTime + DeltaCompare;

	_64BitTimeToLocalTime(NextTime, & STNextTime);

	// So we dont *just* miss a timer event and give up till next
	// we add some time to the current time.
	CurrTime += BufferTime; 

	if (CurrTime >= NextTime) {

		if (SyslistConfigLastRun.wYear > 0) {

			// Find nearest previous align time and assign it here.
			__int64 TimeDelta = CurrTime - NextTime;
			__int64 QuantTimeDelta = (TimeDelta / DeltaCompare) * DeltaCompare;
			LastRunAlignTime = NextTime + QuantTimeDelta;
			_64BitTimeToLocalTime(LastRunAlignTime, &SyslistConfigLastRunAlign);
		}

		return TRUE;
	}

	return FALSE;
}

void PrintTimeToStream(SYSTEMTIME & SomeTime, ::ostream & SomeStream)
{
	SomeStream << "["
		<< SomeTime.wYear << "-"
		<< SomeTime.wMonth << "-"
		<< SomeTime.wDay << "]:["
		<< SomeTime.wHour << "-"
		<< SomeTime.wMinute << "-"
		<< SomeTime.wSecond << "."
		<< SomeTime.wMilliseconds 
		<< "]";
}

void CheckRun(BOOL IsStartup)
{
	__int64 DeltaTime;
	SYSTEMTIME NextTime;

	BOOL ShouldRun = CheckTime (IsStartup, NextTime, DeltaTime);
	
	SYSTEMTIME CurrTime;

	GetSystemTime(&CurrTime);

//	char uniquename[128];
//	sprintf (uniquename,"TimeCheck%d-%d-%d.log",GetCurrentThreadId(), GetCurrentProcessId(), serial ++); 

#if 0 
	::ofstream LogStream;
#endif

	char LogPath[2*MAX_PATH];
	_makepath(LogPath, NULL, SyslistConfigInstall, "TimeCheck.log", NULL);
//	_makepath(LogPath, NULL, SyslistConfigInstall,uniquename, NULL);

	LogStream.open(LogPath);

#ifdef DEBUG
	LogStream << "Special Debug LOG!!!!" << endl;
#endif

	LogStream << "SCATimeCheck Log" << endl;

	LogStream << "Execution check time is: ";
	PrintTimeToStream (CurrTime, LogStream);
	LogStream << " UTC" << endl;
	
	LogStream << "The last collection occured at: ";
	PrintTimeToStream (SyslistConfigLastRun, LogStream);
	LogStream << " UTC" << endl;

	if (SyslistConfigLastRunAlign.wYear > 0) {
		LogStream << "The last aligned collection occured at: ";
		PrintTimeToStream (SyslistConfigLastRunAlign, LogStream);
		LogStream << " UTC" << endl;
	}
	else {
		LogStream << "There is no aligned run time." << endl;
	}


	if (DeltaTime > 0) {
		LogStream << "The next collection will occur on or after: ";
		PrintTimeToStream (NextTime, LogStream);
		LogStream << " UTC" << endl;
	}
	else {
		LogStream << "The next Collection time is User Dependent" << endl;
	}

	LogStream << "Check Method is " << StringFromMethod(SyslistConfigMethod) << endl;

	if (IsStartup)
		LogStream << "This is a startup check." << endl;

	LogStream << "The Check Returned: " << (ShouldRun ? "TRUE" : "FALSE") << endl;

	if (ShouldRun == TRUE) {
		long Status;

		Status = ExecuteCollector();

		if (Status == ERROR_SUCCESS || Status == S_OK) {
			LogStream << "The Collector executed without error." << endl;
		}
		else {
			LogStream << "The Collector returned an error: " << Status << endl;
		}
	}
	else {
		LogStream << "It is not yet time to run the Collector." << endl;
	}
		
	LogStream.close();

	//SetProcessWorkingSetSize (GetCurrentProcess(), -1, -1);
}

DWORD WINAPI ServiceMain()
{
	if (ReadInstallDir() != ERROR_SUCCESS)
		return ERROR_BAD_ENVIRONMENT;

	if (IsInstall == FALSE) {
		//Sleep(45000);
		CheckRun(TRUE);
	}

	SetTimer(NULL, 0, POLL_TIMER_MS, NULL);

	MSG msg;
	ZeroMemory( &msg, sizeof(msg) );
	int MsgRtn;

	while( true ) {
		MsgRtn = GetMessage( &msg, NULL, 0U, 0U);
		
		if (MsgRtn == 0)
			return 0;

		else if (MsgRtn < 0) {
			return GetLastError();
		}
		
		else {
			switch (msg.message) {

			case WM_TIMER:
				CheckRun(FALSE);
				break;
	
			case WM_CLOSE:
			case WM_QUIT:
				return 0;

			default:
				TranslateMessage( &msg );
				DispatchMessage( &msg );
				break;
			}
		}
	}

	return 0;

};
