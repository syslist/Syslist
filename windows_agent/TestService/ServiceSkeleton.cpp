// TestService.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
#include "winsvc.h"
#include <string>

using namespace std;

SERVICE_STATUS          g_ServiceStatus;
SERVICE_STATUS_HANDLE   g_ServiceStatusHandle;
HINSTANCE               g_hInstance;

extern TCHAR * ServiceName;
 
VOID WINAPI ServiceSkeletonMain (DWORD dwArgc, LPTSTR *lpszArgv);

extern VOID WINAPI ServiceCtrlHandler (DWORD opcode);
extern DWORD WINAPI ServiceInit(DWORD argc, LPTSTR *argv, DWORD *specificError);
extern DWORD WINAPI ServiceMain();

typedef int ( * _ProcAddrType) ();
typedef int ( __stdcall * _RegisterServiceProcess_t)(DWORD dwProcessId, DWORD dwType);

extern int __argc;
extern char ** __argv;

int RunWin9xService() 
{
	HMODULE KernelModule = NULL;
	_RegisterServiceProcess_t RegisterServiceProcess = NULL;
	
	KernelModule = LoadLibrary ("Kernel32.DLL");

	if (KernelModule == NULL) 
		return 1;

	RegisterServiceProcess = (_RegisterServiceProcess_t) GetProcAddress(KernelModule, "RegisterServiceProcess");

	if (RegisterServiceProcess == NULL)
		return 1;

	BOOL RegisterOK = FALSE;

	RegisterOK = RegisterServiceProcess(GetCurrentProcessId(), 1);

	if (RegisterOK == FALSE)
		return 1;

	long Status;
	DWORD SpecificCode;

	Status = ServiceInit(__argc, __argv, &SpecificCode);
	if (Status != ERROR_SUCCESS)
		return 1;

	return ServiceMain();
}
	

int RunWinNTService()
{
	SERVICE_TABLE_ENTRY   DispatchTable[] = 
    { 
        { ServiceName, ServiceSkeletonMain }, 
        { NULL, NULL } 
    }; 
 
    if (!StartServiceCtrlDispatcher( DispatchTable)) 
    { 
		return 1;
    }

	return 0;
}

int APIENTRY WinMain(HINSTANCE hInstance,
                     HINSTANCE hPrevInstance,
                     LPSTR     lpCmdLine,
                     int       nCmdShow)
{

	g_hInstance = hInstance;

	BOOL VersionOK;
	OSVERSIONINFOEX WinVer;

	WinVer.dwOSVersionInfoSize = sizeof (OSVERSIONINFOEX);
	VersionOK = GetVersionEx((LPOSVERSIONINFO) &WinVer);

	if (VersionOK == FALSE)
		return 1;

	if (WinVer.dwPlatformId == VER_PLATFORM_WIN32_WINDOWS) {
		return RunWin9xService();
	}

	return RunWinNTService();
}


VOID WINAPI ServiceSkeletonMain(DWORD dwArgc,     // number of arguments
						LPTSTR *lpszArgv)  // array of arguments

{

	DWORD status; 
    DWORD specificError; 
 
    g_ServiceStatus.dwServiceType        = SERVICE_WIN32;
    g_ServiceStatus.dwCurrentState       = SERVICE_START_PENDING;
    g_ServiceStatus.dwControlsAccepted   = SERVICE_ACCEPT_STOP |
										   SERVICE_ACCEPT_SHUTDOWN;
    g_ServiceStatus.dwWin32ExitCode      = 0;
    g_ServiceStatus.dwServiceSpecificExitCode = 0; 
    g_ServiceStatus.dwCheckPoint         = 0; 
    g_ServiceStatus.dwWaitHint           = 0; 
 
    g_ServiceStatusHandle = RegisterServiceCtrlHandler( ServiceName,
														ServiceCtrlHandler); 

    if (g_ServiceStatusHandle == (SERVICE_STATUS_HANDLE) NULL) 
    { 
        return; 
    } 
 
    // Initialization code goes here. 
    status = ServiceInit(dwArgc,lpszArgv, &specificError); 
 
    // Handle error condition 
    if (status != NO_ERROR) 
    { 
        g_ServiceStatus.dwCurrentState       = SERVICE_STOPPED; 
        g_ServiceStatus.dwCheckPoint         = 0;
        g_ServiceStatus.dwWaitHint           = 0;
        g_ServiceStatus.dwWin32ExitCode      = status;
        g_ServiceStatus.dwServiceSpecificExitCode = specificError;
 
        SetServiceStatus (g_ServiceStatusHandle, &g_ServiceStatus); 
        return; 
    } 
 
    // Initialization complete - report running status. 
    g_ServiceStatus.dwCurrentState       = SERVICE_RUNNING; 
    g_ServiceStatus.dwCheckPoint         = 0; 
    g_ServiceStatus.dwWaitHint           = 0; 
 
    if (!SetServiceStatus (g_ServiceStatusHandle, &g_ServiceStatus)) 
    { 
        status = GetLastError(); 

    } 
 
    // This is where the service does its work. 
    status = ServiceMain();

	g_ServiceStatus.dwCurrentState       = SERVICE_STOPPED; 
    g_ServiceStatus.dwCheckPoint         = 0;
    g_ServiceStatus.dwWaitHint           = 0;
    g_ServiceStatus.dwWin32ExitCode      = status;
    //g_ServiceStatus.dwServiceSpecificExitCode = status;

    SetServiceStatus (g_ServiceStatusHandle, &g_ServiceStatus); 

    return; 
} 
 
