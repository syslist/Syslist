// TestNetEnum.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
#include <memory>
#include "resource.h"
#include "../TestWinCrypt/SimpleCrypt.h"
#include "../TestInstConfig/SyslistMethod.h"
#include "../TestInstConfig/SyslistProxyMethod.h"
#include "../TestInstConfig/SyslistRegistry.h"
#include "../TestInstConfig/SyslistIntLang.h"
#include "../TestInstConfig/SyslistVersion.h"
#include "io.h"
#include "stdlib.h"
#include <iostream.h>
#include <fstream.h>
#include <ACLapi.h>
#include "Wbemidl.h"
#include "Wbemcli.h"

#include <string>

using namespace std;

static const long ITERCOUNT = 16;
static const long ITERBUFF = 4096;
static const long INST_OK = 0;
static const long INST_CANCEL = -1;
static const long ITEM_TEXT_LEN = 256;

static const long CHECKED_INDEX = 2;
static const long UNCHECKED_INDEX = 1;

static const long UM_CHECKSTATECHANGE (WM_USER + 100);

static const LPARAM PARAM_SERVER = 1;
static const LPARAM PARAM_DOMAIN = 0;
static const LPARAM PARAM_CONTAINER = 2;
static const LPARAM PARAM_DUD = -1;

static const long CFG_STRING_LEN = 512;

static CmdMethodIndex SyslistConfigMethod = MethodDisable;

static char SyslistConfigServer[CFG_STRING_LEN] = "";
static char SyslistConfigUser[CFG_STRING_LEN] = "";
static char SyslistConfigAcctID[CFG_STRING_LEN] = "";
static char SyslistConfigPwd[CFG_STRING_LEN] = "";
static ProxyMethodIndex SyslistConfigProxyMethod = ProxyMethodDirect;
static char SyslistConfigProxyServer[CFG_STRING_LEN] = "";
static char SyslistConfigMachUser[CFG_STRING_LEN] = "";
static char SyslistConfigMachPwd[CFG_STRING_LEN] = "";
static char SyslistConfigMachDomain[CFG_STRING_LEN] = "";
static long SyslistConfigLangID = -1; //US English...

static const char * LANG_DLL_SPEC = "NetLang_*.dll";

static const WCHAR * INSTALL_TASK_NAME = L"SyslistInstallA";
static const DWORD MAX_RUN_TIME_MS = 600000;
static const DWORD MAX_START_RUN_TIME_MS = 10000;

static const DWORD TASK_POLL_INTERVAL_MS = 1000;

static const TCHAR * SCHED_SERVICE_APP_NAME = _T("mstask.exe");
static const TCHAR * SCHED_SERVICE_NAME = _T("Schedule");

static const WCHAR * INST_SERVICE_NAME = L"SCAInstallService";

static const char * ASP_SERVER_NAME = "https://www.Syslist.com";

#ifdef SYSLIST_DEMO
#ifdef ASP_DEMO
	static const char * LOCAL_SHARE_EXEC = "SCA_ASP_Demo.exe";
#else
	static const char * LOCAL_SHARE_EXEC = "SCA_Demo.exe";
#endif
#else
#ifdef SYSLIST_ACC
	static const char * LOCAL_SHARE_EXEC = "SCA_ASP.exe";
#else
	static const char * LOCAL_SHARE_EXEC = "SCA_Install.exe";
#endif
#endif

static const char * LOCAL_SHARE_SVC = "SCARIsvc.exe";
static const char * LOCAL_SHARE_UNINST = "SCAUn.exe";

//static WCHAR * REMOTE_NAME = L"NSLAINSR";
static WCHAR WShareName[MAX_PATH +1] = L"";

static TCHAR LOCAL_NAME[MAX_COMPUTERNAME_LENGTH + 1];
static TCHAR LOCAL_INSTALL_NAME[ MAX_COMPUTERNAME_LENGTH + MAX_PATH];
static TCHAR LOCAL_UNINSTALL_NAME[ MAX_COMPUTERNAME_LENGTH + MAX_PATH];
static TCHAR LOCAL_INSTALL_DIR[ MAX_COMPUTERNAME_LENGTH + MAX_PATH];

static TCHAR SHARE_INSTALL_LOG_PATH[MAX_PATH + 1];
static TCHAR SHARE_INSTALL_LOGBK_PATH[MAX_PATH + 1];
static TCHAR SHARE_INSTALL_LOG_FILE[MAX_PATH + 1] = "SysRInst.log";

static TCHAR LOCAL_SHARE_PATH[MAX_PATH + 1];
static TCHAR LOCAL_EXEC_PATH[MAX_PATH + 1];

PACL OrigDirDACL = NULL;
PACL OrigFileDACL = NULL;

PSECURITY_DESCRIPTOR OrigFileSec = NULL;
PSECURITY_DESCRIPTOR OrigDirSec = NULL;

BOOL OrigDACLValid = FALSE;

static ::ofstream LogFile;

static BOOL CancelPressed = FALSE;
static HINSTANCE g_hInstance = NULL;

static LangItem g_InstLangItems[] = {
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_DISABLED},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_STARTUP},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_DAILY},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_WEEKLY},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_MONTHLY},
	{ IDC_SYSLIST_SERVER_STATIC, WM_SETTEXT, IDS_SERVER_STATIC},
	{ IDC_SYSLIST_FREQ_STATIC, WM_SETTEXT, IDS_FREQ_STATIC},
	{ IDC_SYSLIST_USER_STATIC, WM_SETTEXT, IDS_USER_STATIC},
	{ IDC_SYSLIST_ACCTID_STATIC, WM_SETTEXT, IDS_ACCTID_STATIC},
	{ IDC_SYSLIST_PWD_STATIC, WM_SETTEXT, IDS_PWD_STATIC},
	{ IDC_MACH_USER_STATIC, WM_SETTEXT, IDS_MACHUSER_STATIC},
	{ IDC_MACH_PWD_STATIC, WM_SETTEXT, IDS_MACHPWD_STATIC},
	{ IDC_MACH_DOMAIN_STATIC, WM_SETTEXT, IDS_MACHDOMAIN_STATIC},
	{ IDC_MACH_LOCAL_CHECK, WM_SETTEXT, IDS_MACHLOCAL_STATIC},
	{ IDC_CANCEL, WM_SETTEXT, IDS_CANCEL},
	{ IDC_INSTALL, WM_SETTEXT, IDS_INSTALL},
	{ IDC_UNINSTALL, WM_SETTEXT, IDS_UNINSTALL},
	{ IDC_LOCAL_CHECK, WM_SETTEXT, IDS_LOCAL_SCAN},
	{ IDC_PRUNE_UNAVAIL_CHECK, WM_SETTEXT, IDS_PRUNE_UNAVAIL},
	{ IDC_PRUNE_INCOMPAT_CHECK, WM_SETTEXT, IDS_PRUNE_INCOMPAT},
	{ IDC_PROXY_SERVER_STATIC, WM_SETTEXT, IDS_PROXY_SERVER_STATIC},
	{ IDC_PROXY_PORT_STATIC, WM_SETTEXT, IDS_PROXY_PORT_STATIC},
	{ IDC_PROXY_COMBO, CB_ADDSTRING, IDS_NO_PROXY},
	{ IDC_PROXY_COMBO, CB_ADDSTRING, IDS_IE_PROXY},
	{ IDC_PROXY_COMBO, CB_ADDSTRING, IDS_MANUAL_PROXY},

	{ LANG_END }
};

static LangItem g_LicLangItems[] = {
	{ IDC_LIC_ACCEPT, WM_SETTEXT, IDS_LIC_ACCEPT},
	{ IDC_LIC_CANCEL, WM_SETTEXT, IDS_CANCEL},
	{ IDC_LANG_STATIC, WM_SETTEXT, IDS_LANG_STATIC},
	{ LANG_END }
};

static TCHAR * g_RemoteCopyLocs[][2] = {
	{"ADMIN$","%SystemRoot%"},
	{"C$", "c:"},
	{"D$", "d:"},
	{"E$", "e:"},
	{"F$", "f:"},
	{"G$", "g:"},
	{NULL, NULL}
};

long INSTALL_ERROR_LOC = 0;
long INSTALL_ERROR_NUM = 0;
long INSTALL_ERROR_ERR = 0;

#define SET_GEN_ERR(x,y) INSTALL_ERROR_LOC = x; INSTALL_ERROR_ERR = y
#define CLEAR_ERR() INSTALL_ERROR_LOC = 0; INSTALL_ERROR_NUM = 0; INSTALL_ERROR_ERR = 0
/////////////////////////////////////////////////////////////////////////////////////////
extern BOOL IsRunningAsAdmin();

/////////////////////////////////////////////////////////////////////////////////////////

long ConvertFrigginCRLF(TCHAR * Dest, TCHAR * Source, long DestLen)
{
	BOOL WasCR = FALSE;

	long CharCount;

	TCHAR * DestLoc;
	TCHAR * SourceLoc;

#define INSERT_CHAR(X) if((CharCount ++) == DestLen) return ERROR_GEN_FAILURE; (*DestLoc++) = X; 

	for (DestLoc = Dest, SourceLoc = Source, CharCount = 0, WasCR = FALSE;
		 CharCount < DestLen && (*SourceLoc) != '\0';
		 SourceLoc ++) {

		 switch ( *SourceLoc ) {

		 case '\r':
			 WasCR = TRUE;
			 break;

		 case '\n':
			 // Fixup to be damn DOS lines. Of ALL things, DOS!
			 if (!WasCR) {
				 INSERT_CHAR ('\r');
			 }
			 INSERT_CHAR ('\n');

			 WasCR = FALSE;

			 break;

		 default:
			 if (WasCR) {
				 INSERT_CHAR ('\r');
				 INSERT_CHAR ('\n');

				 WasCR = FALSE;
			 }
			 
			 (*DestLoc ++) = (*SourceLoc);
			
			 break;
		 }
	 }

    (*DestLoc) = '\0';

	return ERROR_SUCCESS;
}

long LocaleMsgBox(HWND Parent, long RsrcMessage, long RsrcCaption, UINT Type, long ErrLoc = 0, long ErrNum = ERROR_SUCCESS, long DefaultReturn = MB_OK)
{
	TCHAR ResStr[RES_STRING_SIZE];
	TCHAR ResCaption[RES_STRING_SIZE];

	LoadString (g_hLangInstance, RsrcMessage, ResStr, RES_STRING_SIZE);
	LoadString (g_hLangInstance, RsrcCaption, ResCaption, RES_STRING_SIZE);
	
	return MessageBox (Parent, ResStr, ResCaption, Type);
}

long FindLocalDir(char * ReturnDir)
{
	char ExecDrive[MAX_PATH + 1];
	char ExecDir[MAX_PATH + 1];
	char WholeExecPath[MAX_PATH + 1];

	// here we make a find file spec representing all
	// language dlls. These should be located in a 
	// folder under the executables directory.
	GetModuleFileName(NULL, WholeExecPath, MAX_PATH);
	_splitpath(WholeExecPath, ExecDrive, ExecDir, NULL, NULL);
	_makepath(ReturnDir, ExecDrive, ExecDir, NULL, NULL);
	ReturnDir[strlen(ReturnDir) - 1] = '\0';

	return ERROR_SUCCESS;
}

void ClearLog(HWND hLog)
{
	SendMessage(hLog, WM_SETTEXT, (WPARAM) NULL, (LPARAM) "");
}

void AppendLog(HWND hLog, const char * Message)
{
	SendMessage(hLog, EM_REPLACESEL, (WPARAM) NULL, (LPARAM) Message);
	LogFile <<  Message;
	LogFile.flush();
}

inline void AppendLogCRLF(HWND hLog)
{
	AppendLog (hLog, "\r\n");
}

void AppendResLog (HWND hLog, int ResID, BOOL AddCRLF = FALSE)
{
	TCHAR ResStr[RES_STRING_SIZE];
	LoadString (g_hLangInstance, ResID, ResStr, RES_STRING_SIZE);

	AppendLog(hLog, ResStr);

	if (AddCRLF)
		AppendLog (hLog, "\r\n");	
}

void AppendSystemLog (HWND hLog, long SysErrID, BOOL SuppressTab = FALSE, BOOL AddCRLF = FALSE)
{
	LPVOID lpMsgBuf;

	if (SysErrID == ERROR_EXTENDED_ERROR) {
		
		DWORD NetError;
		
		char lpstrNetMsgBuf[CFG_STRING_LEN];
		char lpstrNetNameBuf[CFG_STRING_LEN];

		WNetGetLastError (
			& NetError,
			lpstrNetMsgBuf,
			CFG_STRING_LEN,
			lpstrNetNameBuf,
			CFG_STRING_LEN);

		lpMsgBuf = (void *) lpstrNetMsgBuf;
	}

	else {
		FormatMessage( 
			FORMAT_MESSAGE_ALLOCATE_BUFFER | 
			FORMAT_MESSAGE_FROM_SYSTEM | 
			FORMAT_MESSAGE_IGNORE_INSERTS,
			NULL,
			SysErrID,
			MAKELANGID(LANG_NEUTRAL, SUBLANG_DEFAULT), // Default language
			//MAKELANGID(LANG_NEUTRAL, SyslistConfigLangID), // Syslist language
			(LPTSTR) &lpMsgBuf,
			0,
			NULL 
		);
	}

	if (lpMsgBuf == NULL)
		return;

	if (SuppressTab == FALSE)
		AppendLog(hLog, "\t");

	// Process any inserts in lpMsgBuf.
	// ...
	// Display the string.
	AppendLog (hLog, (const char *) lpMsgBuf);

	// Free the buffer.
	if (SysErrID != ERROR_EXTENDED_ERROR)
		LocalFree( lpMsgBuf );

	if (AddCRLF)
		AppendLog (hLog, "\r\n");	

}

void OpenLog()
{
	SYSTEMTIME CurrTime;
	GetLocalTime(&CurrTime);
	char LocalDir[2 * MAX_PATH + 1];
	char LogFileName[2 * MAX_PATH + 1];
	char WholeLogName [2 * MAX_PATH + 1];
	long Status;
	Status = FindLocalDir(LocalDir);
	
	sprintf (LogFileName, "SCALog-%d-%02d-%02d-%02d%02d-%02d.txt", 
		CurrTime.wYear, CurrTime.wMonth, CurrTime.wDay, 
		CurrTime.wHour, CurrTime.wMinute,
		CurrTime.wSecond);

	_makepath(WholeLogName,NULL, LocalDir, LogFileName, NULL);

	LogFile.open(WholeLogName);
}

void SpinDialog(HWND hDlg)
{
	MSG FoundMessage;
	// Keep the UI going while scanning
	while (PeekMessage(&FoundMessage, hDlg, 0, 0, PM_REMOVE))
		DispatchMessage (& FoundMessage);
}

long AddAuth(WCHAR * RemoteName, WCHAR * AuthItem, WCHAR * User, WCHAR * Pwd, WCHAR * Domain )
{
	USE_INFO_2 NetInfo;

	ZeroMemory(&NetInfo, sizeof(NetInfo));
	long Status;

	WCHAR ShareName[2*MAX_PATH +1];
	swprintf (ShareName, L"%s\\%s", RemoteName, AuthItem);

	WCHAR UserName[CFG_STRING_LEN];
	if (Domain[0] == '\0')
		wcscpy(UserName, User);
	else if (wcscmp (Domain, L".") == 0)
		swprintf (UserName, L"%s\\%s", &(RemoteName[2]), User);
	else
		swprintf (UserName, L"%s\\%s", Domain, User);

#if 0
	NetInfo.ui2_local = (char *) NULL;
	if (AuthItem == NULL)
		NetInfo.ui2_remote = (char *) NULL;
	else
		NetInfo.ui2_remote = (char *) ShareName;

	NetInfo.ui2_password = (char *) Pwd;
	NetInfo.ui2_username = (char *) User;
	NetInfo.ui2_asg_type = USE_IPC;

	unsigned long ParmError = 0;

	//Status = NetUseAdd((char *) RemoteName, 2, (PBYTE) &NetInfo, & ParmError);
	Status = NetUseAdd(NULL, 2, (PBYTE) &NetInfo, & ParmError);
#endif

	NETRESOURCEW NetRes;
	ZeroMemory (&NetRes, sizeof(NetRes));

	NetRes.dwScope = RESOURCE_GLOBALNET;
	NetRes.dwType = RESOURCETYPE_ANY;
	NetRes.dwDisplayType = RESOURCEDISPLAYTYPE_GENERIC;
	NetRes.dwUsage = RESOURCEUSAGE_CONNECTABLE;
	NetRes.lpLocalName = NULL;
	NetRes.lpRemoteName = ShareName;

	Status = WNetAddConnection2W(&NetRes, Pwd, UserName, 0);
	return Status;
}

long RemoveAuth(WCHAR * RemoteName, WCHAR * AuthItem, WCHAR * User, WCHAR * Pwd, WCHAR * Domain, BOOL Force = FALSE)
{
	WCHAR ShareName[MAX_PATH +1];
	swprintf (ShareName, L"%s\\%s", RemoteName, AuthItem);

#if 0
	NetUseDel(RemoteName, ShareName, FALSE);
#endif

	long Status;
	Status = WNetCancelConnection2W(ShareName, 0, Force);
	
	return Status;

}

long InstallRemoteByService (HWND hDlg, WCHAR * RemoteName, WCHAR * User, WCHAR * Pwd, WCHAR * Domain, WCHAR * RemoteExec, WCHAR * RemoteSvc, WCHAR * RemoteArgs)
{

	USES_CONVERSION;

	// Auth the user on IPC for Service Control Managememt
	long Status;
	RemoveAuth(RemoteName,L"IPC$", User, Pwd, Domain, TRUE);
	Status = AddAuth(RemoteName,L"IPC$", User, Pwd, Domain);

	if (Status != ERROR_SUCCESS) {
		
		SET_GEN_ERR(256, Status);
		return ERROR_CONNECTION_REFUSED;
	}

	SC_HANDLE   hSC = NULL;
	SC_HANDLE   hSchSvc = NULL;

	// For the strangest reason, sometimes we have to try
	// twice to open the connection! This usually happens
	// if we disturbed a previous connection.
	hSC = OpenSCManagerW(RemoteName, NULL, SC_MANAGER_CREATE_SERVICE);
	if (hSC == NULL)
		hSC = OpenSCManagerW(RemoteName, NULL, SC_MANAGER_CREATE_SERVICE);

	if (hSC == NULL)
	{
		SET_GEN_ERR(257, GetLastError());
		RemoveAuth(RemoteName,L"IPC$", User, Pwd, Domain);
		return ERROR_CONNECTION_INVALID;
	}

	// Delete previous attempts if needed
	hSchSvc = OpenServiceW(hSC,
						  INST_SERVICE_NAME,
						  SERVICE_ALL_ACCESS);

	if (hSchSvc != NULL) {
		SERVICE_STATUS DeleteStatus;
		ControlService(hSchSvc, SERVICE_CONTROL_STOP, & DeleteStatus);
		long DeleteTotalTime = 0;
		BOOL DeletePollOk = TRUE;

		do {

			if (DeleteTotalTime > MAX_START_RUN_TIME_MS)
				break;

			SpinDialog(hDlg);
			Sleep(TASK_POLL_INTERVAL_MS);

			if (QueryServiceStatus(hSchSvc, &DeleteStatus) == FALSE)
			{
			  SET_GEN_ERR(259, GetLastError());
			  DeletePollOk = FALSE;
			}

			DeleteTotalTime += TASK_POLL_INTERVAL_MS;
		}
			
		while (CancelPressed == FALSE && DeletePollOk && DeleteStatus.dwCurrentState != SERVICE_STOPPED);

		if (DeletePollOk == FALSE 
			|| DeleteStatus.dwCurrentState != SERVICE_STOPPED){
			
			SET_GEN_ERR(262, ERROR_SERVICE_ALREADY_RUNNING);

			CloseServiceHandle(hSchSvc);
			CloseServiceHandle(hSC);
			RemoveAuth(RemoteName,L"IPC$", User, Pwd, Domain);

			return ERROR_CONNECTION_INVALID;
		}

		BOOL DeleteOK = DeleteService(hSchSvc);

		if (DeleteOK == FALSE) {
			//SET_GEN_ERR(264, GetLastError());
			//return ERROR_CONNECTION_INVALID;
		}

		CloseServiceHandle(hSchSvc);
		hSchSvc = NULL;
	}

	
	// Format Username 'cause it requires a
	// complete path name
	WCHAR AugmentedName[256];
	if (wcscspn(User,L"\\") != 0) {
		swprintf (AugmentedName, L".\\%s", User);
	}
	else {
		wcscpy(AugmentedName, User);
	}

	hSchSvc =  CreateServiceW(hSC,							// handle to SCM database 
							 INST_SERVICE_NAME,			// name of service to start
							 L"Syslist Companion Agent Remote Installation Service",     // display name
							 SERVICE_ALL_ACCESS | SERVICE_START | SERVICE_STOP,			// type of access to service
							 SERVICE_WIN32_OWN_PROCESS,		// type of service
							 SERVICE_DEMAND_START,			// when to start service
							 SERVICE_ERROR_IGNORE,			// severity of service failure
							 RemoteSvc,						// name of binary file
							 NULL,							// name of load ordering group
							 NULL,							// tag identifier
							 L"RpcSs\0\0",							// array of dependency names
							 NULL,							// account name 
							 NULL );						// account password

	if (hSchSvc == NULL) {
		SET_GEN_ERR(258, GetLastError());
		CloseServiceHandle(hSC);
		RemoveAuth(RemoteName,L"IPC$", User, Pwd, Domain);
		return ERROR_CONNECTION_INVALID;
	}


	const WCHAR * ServiceStartArgs[5];

	//ServiceStartArgs[0] = INST_SERVICE_NAME;
	ServiceStartArgs[0] = RemoteExec;
	ServiceStartArgs[1] = RemoteArgs;
	ServiceStartArgs[2] = User;
	ServiceStartArgs[3] = Pwd;
	ServiceStartArgs[4] = Domain;

	if (StartServiceW(hSchSvc, 5, ServiceStartArgs) == FALSE)
	{
		SET_GEN_ERR(265, GetLastError());

		CloseServiceHandle(hSchSvc);
		CloseServiceHandle(hSC);
		RemoveAuth(RemoteName,L"IPC$", User, Pwd, Domain);

		//printf("Could not start Task Scheduler.\n");

		return ERROR_CONNECTION_INVALID;
	}

	SERVICE_STATUS SvcStatus;
	BOOL PollOk = TRUE;

	long TotalTime = 0;

	do {

		if (TotalTime > MAX_RUN_TIME_MS) {
			SET_GEN_ERR(266, ERROR_TIMEOUT);
			PollOk = FALSE;
			break;
		}

		SpinDialog(hDlg);
		Sleep(TASK_POLL_INTERVAL_MS);

		if (QueryServiceStatus(hSchSvc, &SvcStatus) == FALSE)
		{
		  SET_GEN_ERR(259, GetLastError());
		  PollOk = FALSE;
		}

		TotalTime += TASK_POLL_INTERVAL_MS;
	}
		
	while (CancelPressed == FALSE && PollOk && SvcStatus.dwCurrentState != SERVICE_STOPPED);


	SERVICE_STATUS DeleteStatus;
	ControlService(hSchSvc, SERVICE_CONTROL_STOP, & DeleteStatus);
	long DeleteTotalTime = 0;
	BOOL DeletePollOk = TRUE;

	do {

		if (DeleteTotalTime > MAX_START_RUN_TIME_MS)
			break;

		SpinDialog(hDlg);
		Sleep(TASK_POLL_INTERVAL_MS);

		if (QueryServiceStatus(hSchSvc, &DeleteStatus) == FALSE) {

		  DeletePollOk = FALSE;

		}

		DeleteTotalTime += TASK_POLL_INTERVAL_MS;
	}
			
	while (CancelPressed == FALSE && DeletePollOk && DeleteStatus.dwCurrentState != SERVICE_STOPPED);

	DeleteService(hSchSvc);

	CloseServiceHandle(hSchSvc);
	CloseServiceHandle(hSC);

	RemoveAuth(RemoteName,L"IPC$", User, Pwd, Domain);

	if (PollOk == FALSE)
		return SCHED_S_TASK_HAS_NOT_RUN;

	if (SvcStatus.dwWin32ExitCode != 0) {
	  SET_GEN_ERR(1024 + SvcStatus.dwServiceSpecificExitCode, SvcStatus.dwWin32ExitCode);
	  return E_FAIL;
	}

	return ERROR_SUCCESS;
}
							 

long SetProxyEnableState(HWND hDlg)
{
	HWND ProxyComboID = GetDlgItem(hDlg, IDC_PROXY_COMBO);

	ProxyMethodIndex CurrProxyMethod;

	BOOL IsEnabled = TRUE;

	long CurrSel  =	SendMessage(ProxyComboID, CB_GETCURSEL, NULL, NULL);
	CurrProxyMethod = (ProxyMethodIndex) SendMessage(ProxyComboID, CB_GETITEMDATA, CurrSel, NULL);
	
	if (CurrProxyMethod != ProxyMethodManual)
		IsEnabled = FALSE;

	EnableWindow( GetDlgItem( hDlg, IDC_PROXY_SERVER_STATIC), IsEnabled);
	EnableWindow( GetDlgItem( hDlg, IDC_PROXY_SERVER_EDIT), IsEnabled);
	EnableWindow( GetDlgItem( hDlg, IDC_PROXY_PORT_STATIC), IsEnabled);
	EnableWindow( GetDlgItem( hDlg, IDC_PROXY_PORT_EDIT), IsEnabled);

	return ERROR_SUCCESS;
}


long GetLocalNames()
{
	BOOL NameStatus;
	unsigned long NameLen = MAX_COMPUTERNAME_LENGTH;

	NameStatus = GetComputerName(LOCAL_NAME, &NameLen);
	if (NameStatus == FALSE)
		return ERROR_GEN_FAILURE;
	
	return ERROR_SUCCESS;
}

long ReadRegInfo()
{
	HKEY SyslistRegKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_READ, & SyslistRegKey);
	if (Status != ERROR_SUCCESS)
		return Status;

	char ReturnRegString[CFG_STRING_LEN];
	unsigned long ReturnSize;
	DWORD ReturnType;

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_LANG_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString , &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		SyslistConfigLangID = atoi(ReturnRegString);
	else
		SyslistConfigLangID = 1033;

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_METHOD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		SyslistConfigMethod = MethodFromString(ReturnRegString);
	else
		SyslistConfigMethod = MethodDisable;

#if  defined(ASP_DEMO) || defined (SYSLIST_ACC)
	strcpy(SyslistConfigServer, ASP_SERVER_NAME);
#else
	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_SERVER_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		strcpy(SyslistConfigServer,ReturnRegString);
	else
		SyslistConfigServer[0] = '\0';
#endif

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_PROXY_METHOD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		SyslistConfigProxyMethod = ProxyMethodFromString(ReturnRegString);
	else
		SyslistConfigProxyMethod = ProxyMethodDirect;

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_PROXY_SERVER_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		strcpy(SyslistConfigProxyServer,ReturnRegString);
	else
		SyslistConfigProxyServer[0] = '\0';

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';

	// These are encrypted via Windows Encryption...
	SimpleStringPWCrypt RegDecrypt(SyslistPhrase);

	Status = RegQueryValueEx(SyslistRegKey, REG_USER_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigUser, ReturnRegString, ReturnSize, ReturnType);
	}

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_PWD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigPwd, ReturnRegString, ReturnSize, ReturnType);
	}
	
#ifdef SYSLIST_ACC
	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_ACCTID_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigAcctID, ReturnRegString, ReturnSize, ReturnType);
	}
#endif

#if 0
	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_MACHUSER_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigMachUser, ReturnRegString, ReturnSize, ReturnType);
	}

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_MACHPWD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigMachPwd, ReturnRegString, ReturnSize, ReturnType);
	}
#endif

	RegCloseKey(SyslistRegKey);

	return ERROR_SUCCESS;
}

long DetermineNodeAttr ( long DisplayType, string & DescName, BOOL & Descend, BOOL & IsBold, LPARAM & ItemParam)
{
	Descend = FALSE;
	IsBold = FALSE;
	ItemParam = PARAM_DUD;

	TCHAR DescRes[RES_STRING_SIZE];

	switch (DisplayType) {

	case RESOURCEDISPLAYTYPE_DOMAIN: 
		Descend = TRUE;
		IsBold = TRUE;
		ItemParam = PARAM_DOMAIN;
		LoadString(g_hLangInstance , IDS_NET_ID_DOMAIN, DescRes, RES_STRING_SIZE);
		DescName = DescRes;
		break;

	case RESOURCEDISPLAYTYPE_SERVER: 
		ItemParam = PARAM_SERVER;
		DescName = "";
		break;

	case RESOURCEDISPLAYTYPE_SHARE: 
		DescName = "SHARE";
		break;

	case RESOURCEDISPLAYTYPE_GENERIC:
		DescName = "GENERIC";
		break;

	case RESOURCEDISPLAYTYPE_NETWORK:
		Descend = TRUE;
		IsBold = TRUE;
		ItemParam = PARAM_CONTAINER;
		LoadString(g_hLangInstance , IDS_NET_ID_NETWORK, DescRes, RES_STRING_SIZE);
		DescName = DescRes;
		break;

	case RESOURCEDISPLAYTYPE_ROOT:
		DescName = "ROOT";
		break;

	case RESOURCEDISPLAYTYPE_SHAREADMIN:
		DescName = "SHAREADMIN";
		break;

	case RESOURCEDISPLAYTYPE_DIRECTORY:
		DescName = "DIRECTORY";
		break;

	case RESOURCEDISPLAYTYPE_TREE:
		DescName = "TREE";
		break;

	case RESOURCEDISPLAYTYPE_NDSCONTAINER:
		Descend = TRUE;
		IsBold = TRUE;
		DescName = "NDSCONTAINER";
		break;

	default:
		DescName = "??? UNKNOWN";
		break;

	}
	return ERROR_SUCCESS;
}


BOOL ValidateSystemCount(HWND hLog, long ItemCount)
{
#ifdef SYSLIST_DEMO
	static BOOL SawStart = FALSE;

	if (ItemCount == 0) {
		SawStart = TRUE;
	}

	if (ItemCount > 11) { // limit to 10 systems on demo - 11 to inlcude parent
		
		if (SawStart) {

			SawStart = FALSE;

			AppendLog(hLog,"\r\nThe Number of computers allowed by the evaluation version has been exceeded.\r\n");
			AppendLog(hLog,"No further systems will be listed.\r\n");
		}

		return FALSE;
	}
#endif
	return TRUE;
}

long InitNetEnumDlgTree(HWND hDlg, LPNETRESOURCE NetResource, HTREEITEM Parent, 
						BOOL LocalOnly, BOOL PruneUnavail, BOOL PruneIncompat, 
						long * ItemCount)
{
	USES_CONVERSION;

	HWND hTreeView;
	HWND hLog;

	hTreeView = GetDlgItem(hDlg, IDC_NETTREE);
	hLog = GetDlgItem (hDlg, IDC_LOG);

#ifdef SYSLIST_DEMO
	if (ValidateSystemCount(hLog, *ItemCount) == FALSE) {
	
		return ERROR_SUCCESS;

	}

#endif

	DWORD NetStatus;
	HANDLE hNetEnum;

	if (Parent == NULL) {
		
		TreeView_DeleteAllItems(hTreeView);
		
		if (LocalOnly == TRUE) {
			TVINSERTSTRUCTA ParentRootItem;
			ParentRootItem.item.mask = TVIF_STATE | TVIF_TEXT | TVIF_PARAM;
			
			TCHAR LocalNetRes[RES_STRING_SIZE];
			LoadString(g_hLangInstance, IDS_NET_ID_LOCAL, LocalNetRes, RES_STRING_SIZE);

			ParentRootItem.item.pszText = LocalNetRes;
			ParentRootItem.item.lParam = PARAM_CONTAINER;
			ParentRootItem.item.state = TVIS_BOLD | TVIS_EXPANDED;
			ParentRootItem.hInsertAfter = TVI_SORT;
			ParentRootItem.item.stateMask = ParentRootItem.item.state;
			ParentRootItem.hParent = NULL;

			HTREEITEM ParentHandle;
			ParentHandle = TreeView_InsertItem ( hTreeView, &ParentRootItem);

			if (ParentHandle == NULL)
				return ERROR_GEN_FAILURE;

			InitNetEnumDlgTree (hDlg, NetResource, ParentHandle, LocalOnly, PruneUnavail, PruneIncompat, ItemCount);

			return ERROR_SUCCESS;

		}
	}

	NetStatus = WNetOpenEnum(
		LocalOnly?RESOURCE_CONTEXT:RESOURCE_GLOBALNET, 
		RESOURCETYPE_ANY,   // all resources
		0, // enumerate all resources
		NetResource,		 // NULL first time the function is called
		&hNetEnum);			 // handle to the resource

	if (NetStatus != NO_ERROR)
		return ERROR_GEN_FAILURE;

	char LocalBuf[ITERBUFF];
	LPNETRESOURCE LocalRes = (LPNETRESOURCE) LocalBuf;

	WKSTA_INFO_100 * TestCompInfo = NULL;

	DWORD FoundCount ;
	DWORD FoundSize ;

	TVINSERTSTRUCTA CurrInsertItem;
	CurrInsertItem.hInsertAfter = TVI_SORT;

	//TVITEM CurrInfo;

	CurrInsertItem.item.mask = TVIF_STATE | TVIF_TEXT | TVIF_PARAM;

	string DescName;
	string ItemName;
	string FinalText;

	HTREEITEM NewTreeItem;

	while (FoundCount = -1, FoundSize = ITERBUFF, 
		   CancelPressed == FALSE 
		   && WNetEnumResource(hNetEnum, &FoundCount, (void *) LocalRes, &FoundSize) != ERROR_NO_MORE_ITEMS) {

		long CurrIdx;
		BOOL Descend = FALSE;
		BOOL IsBold = FALSE;

		for (CurrIdx = 0; CurrIdx < FoundCount; CurrIdx ++) {

#ifdef SYSLIST_DEMO

			if (ValidateSystemCount(hLog, *ItemCount) == FALSE) {
			
				return ERROR_SUCCESS;

			}

#endif

			AppendLog(hLog,".");
			(*ItemCount) ++;
			
			if ( ((*ItemCount) % 10) == 0) {
				AppendLogCRLF(hLog);
			}
			
			SpinDialog(hDlg);

			CurrInsertItem.hParent = Parent;

			if (LocalRes[CurrIdx].lpRemoteName)
				ItemName = LocalRes[CurrIdx].lpRemoteName ;
			
			DetermineNodeAttr(LocalRes[CurrIdx].dwDisplayType, DescName, Descend, IsBold, CurrInsertItem.item.lParam);

			if (CurrInsertItem.item.lParam != PARAM_DUD 
				&& LocalRes[CurrIdx].dwScope != RESOURCE_REMEMBERED) {

				if ((PruneUnavail || PruneIncompat) 
					&& LocalRes[CurrIdx].dwDisplayType == RESOURCEDISPLAYTYPE_SERVER) {
					NetStatus = NetWkstaGetInfo (T2W(LocalRes[CurrIdx].lpRemoteName), 100, (PBYTE *) & TestCompInfo);
					
					if ((PruneUnavail || PruneIncompat)
						&& NetStatus != NERR_Success && NetStatus != ERROR_ACCESS_DENIED)
						continue;

					if (PruneIncompat 
						&& ( TestCompInfo == NULL || TestCompInfo->wki100_platform_id != PLATFORM_ID_NT))
						continue;

					NetApiBufferFree (TestCompInfo);
				}

				CurrInsertItem.item.state = 0;

				if (IsBold)
					CurrInsertItem.item.state = TVIS_BOLD;

				if ((LocalRes[CurrIdx].dwUsage &  RESOURCEUSAGE_CONTAINER) == RESOURCEUSAGE_CONTAINER
					&& Descend == TRUE) {

					CurrInsertItem.item.state |= TVIS_EXPANDED;

				}


				CurrInsertItem.item.stateMask = CurrInsertItem.item.state;

				FinalText = "";

				if (DescName.length() > 0)
					FinalText = "(" + DescName + ") ";

				FinalText += ItemName;
				
				CurrInsertItem.item.pszText = (char *) FinalText.c_str();
				CurrInsertItem.item.cchTextMax = strlen(CurrInsertItem.item.pszText);

				NewTreeItem = TreeView_InsertItem ( hTreeView, &CurrInsertItem);

				if ((LocalRes[CurrIdx].dwUsage &  RESOURCEUSAGE_CONTAINER) == RESOURCEUSAGE_CONTAINER
					&& Descend == TRUE) {

					InitNetEnumDlgTree(hDlg, &(LocalRes[CurrIdx]), NewTreeItem , LocalOnly, PruneUnavail, PruneIncompat, ItemCount);

					HTREEITEM FirstChild;
					FirstChild = TreeView_GetChild(hTreeView, NewTreeItem);
					if (FirstChild == NULL)
						TreeView_DeleteItem (hTreeView, NewTreeItem);
				}
			}

		}
	}

	if (CancelPressed)
		return E_FAIL;

	return ERROR_SUCCESS;
}

void SetDlgItemEnabled(HWND hDlg, int ItemID, BOOL EnableState)
{
	HWND hItem = GetDlgItem(hDlg, ItemID);
	EnableWindow(hItem, EnableState);
}

long RecurseSetParentChecked (HWND Tree, HTREEITEM RootItem)
{
	TVITEMEX ItemInfo;

	char ItemText[ITEM_TEXT_LEN];

	ItemInfo.pszText = ItemText;
	ItemInfo.cchTextMax = ITEM_TEXT_LEN;

	ItemInfo.mask = TVIF_TEXT | TVIF_STATE;
	ItemInfo.stateMask = TVIS_STATEIMAGEMASK;

	ItemInfo.hItem = RootItem;

	TreeView_GetItem(Tree, &ItemInfo);

	BOOL IsChecked;

	if ((ItemInfo.state & TVIS_STATEIMAGEMASK) == INDEXTOSTATEIMAGEMASK(CHECKED_INDEX))
		IsChecked = TRUE;
	else
		IsChecked = FALSE;

	ATLTRACE ("Setting Parent Check Recursively for %s, Checked = %d, item = %d\n", ItemText, IsChecked,
				(ItemInfo.state & TVIS_STATEIMAGEMASK));

	if (IsChecked == TRUE)
		return ERROR_SUCCESS;

	HTREEITEM Parent;
	TVITEMEX ParentInfo;

	Parent = TreeView_GetParent(Tree, RootItem);

	while (Parent != NULL) {
		ParentInfo.mask = TVIF_STATE;
		ParentInfo.stateMask = TVIS_STATEIMAGEMASK;
		ParentInfo.state = (ItemInfo.state & TVIS_STATEIMAGEMASK);
		ParentInfo.hItem = Parent;

		TreeView_SetItem(Tree, &ParentInfo);

		Parent = TreeView_GetParent(Tree, Parent);
	}

	return ERROR_SUCCESS;
}

long RecurseSetChildChecked( HWND Tree, HTREEITEM RootItem)
{
	TVITEMEX ItemInfo;

	char ItemText[ITEM_TEXT_LEN];

	ItemInfo.pszText = ItemText;
	ItemInfo.cchTextMax = ITEM_TEXT_LEN;

	ItemInfo.mask = TVIF_TEXT | TVIF_STATE;
	ItemInfo.stateMask = TVIS_STATEIMAGEMASK;

	ItemInfo.hItem = RootItem;

	TreeView_GetItem(Tree, &ItemInfo);

	BOOL IsChecked;

	if ((ItemInfo.state & TVIS_STATEIMAGEMASK) == INDEXTOSTATEIMAGEMASK(CHECKED_INDEX))
		IsChecked = TRUE;
	else
		IsChecked = FALSE;

	ATLTRACE ("Setting Child Check Recursively for %s, Checked = %d, item = %d\n", ItemText, IsChecked,
				(ItemInfo.state & TVIS_STATEIMAGEMASK));

	HTREEITEM Child;
	TVITEMEX ChildInfo;

	Child = TreeView_GetChild(Tree, RootItem);

	while (Child != NULL) {

		ChildInfo.mask = TVIF_STATE;
		ChildInfo.stateMask = TVIS_STATEIMAGEMASK;
		ChildInfo.state = (ItemInfo.state & TVIS_STATEIMAGEMASK);
		ChildInfo.hItem = Child;

		TreeView_SetItem(Tree, &ChildInfo);

		RecurseSetChildChecked(Tree, Child);

		Child = TreeView_GetNextSibling(Tree, Child);
	}
	

	return ERROR_SUCCESS;
}

long AppendShareLog(HWND hLog, TCHAR * ShareLoc = NULL)
{
	::ifstream ShareLogStream;

	if (ShareLoc == NULL)
		ShareLogStream.open(SHARE_INSTALL_LOG_PATH);
	else
		ShareLogStream.open (ShareLoc);

	char CurrLine[1024];
	while (ShareLogStream.eof() == FALSE) {
		ShareLogStream.getline(CurrLine, 1024);

		if (CurrLine[0] != '\0') {
			AppendLog(hLog, "\t");
			AppendLog(hLog, CurrLine);
			AppendLogCRLF(hLog);
		}
	}
	
	ShareLogStream.close();

	return ERROR_SUCCESS;
}

void AppendInstFailCode(HWND hLog, long Status, TCHAR * RemoteLog = NULL)
{
	char FmtErr[256];

	switch (Status) {

	case (ERROR_BAD_ENVIRONMENT):
		sprintf (FmtErr,"\tCould Not Set Up Remote Task (0x%08X,0x%08X)\r\n", INSTALL_ERROR_LOC, INSTALL_ERROR_ERR);
		AppendLog(hLog,FmtErr);
		if (INSTALL_ERROR_ERR != 0)
			AppendSystemLog(hLog, INSTALL_ERROR_ERR);
		break;

	case (ERROR_CONNECTION_REFUSED):
		sprintf (FmtErr,"\tCould not Authenticate User (0x%08X,0x%08X)\r\n", INSTALL_ERROR_LOC, INSTALL_ERROR_ERR);
		AppendLog(hLog,FmtErr);
		if (INSTALL_ERROR_ERR != 0)
			AppendSystemLog(hLog, INSTALL_ERROR_ERR);
		break;

	case (ERROR_CONNECTION_INVALID):
		AppendLog(hLog,"\tCould not start services on remote machine\r\n");
		if (INSTALL_ERROR_ERR != 0)
			AppendSystemLog(hLog, INSTALL_ERROR_ERR);

		break;

	case (SCHED_S_TASK_HAS_NOT_RUN):
		sprintf (FmtErr,"\tCould Not Execute Remote Task (0x%08X,0x%08X)\r\n", INSTALL_ERROR_LOC, INSTALL_ERROR_ERR);
		AppendLog(hLog,FmtErr);
		if (INSTALL_ERROR_ERR != 0)
			AppendSystemLog(hLog, INSTALL_ERROR_ERR);

		break;

	case (E_FAIL): {

			sprintf (FmtErr,"\tError from Remote Host (0x%08X,0x%08X)\r\n", INSTALL_ERROR_LOC, INSTALL_ERROR_ERR);
			AppendLog(hLog,FmtErr);

			TCHAR * FailLogLoc = RemoteLog;

			if (FailLogLoc == NULL)
				FailLogLoc = SHARE_INSTALL_LOG_PATH;

			// Get Fail message here....
			Status = SetNamedSecurityInfo(FailLogLoc, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, NULL, NULL);

			if (!access(FailLogLoc, 0)) {
				AppendShareLog(hLog, FailLogLoc);
				_unlink(FailLogLoc);
			}

			if (INSTALL_ERROR_ERR != 0)
				AppendSystemLog(hLog, INSTALL_ERROR_ERR);

		}
	}
}

long CopyInstallToRemote(WCHAR * RemoteName, 
						 WCHAR * ExecName, WCHAR * SvcName,
						 WCHAR * UserName, WCHAR * Pwd, WCHAR * Domain,
						 WCHAR * ReturnDestPathRemote, long MaxDestPathRemoteLen,
						 WCHAR * ReturnDestPathLocal, long MaxDestPathLocalLen,
						 WCHAR * ReturnDestDir, long MaxDestDirLen,
						 WCHAR * ReturnDestSvcRemote, long MaxDestSvcRemoteLen,
						 WCHAR * ReturnDestSvcLocal, long MaxDestSvcLocalLen,
						 WCHAR * ReturnDestLogRemote, long MaxDestLogRemoteLen,
						 WCHAR * ReturnDestLogLocal, long MaxDestLogLocalLen)
{
	USES_CONVERSION;

	TCHAR * CurrItemRemote;
	TCHAR * CurrItemLocal;
	TCHAR DestPathRemote[2 * MAX_PATH ];
	TCHAR DestPathLocal[2 * MAX_PATH ];
	TCHAR DestSvcRemote[2 * MAX_PATH ];
	TCHAR DestSvcLocal[2 * MAX_PATH ];
	TCHAR DestDir [2 * MAX_PATH ];
	TCHAR DestLogRemote [2 * MAX_PATH ];
	TCHAR DestLogLocal [2 * MAX_PATH ];
	TCHAR SourcePath[2 * MAX_PATH ];
	TCHAR SvcSourcePath[ 2* MAX_PATH ];

	char LocalDir[2 * MAX_PATH + 1];

	long Status;
	BOOL CopyStatus;

	Status = FindLocalDir(LocalDir);
	if (Status != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	_makepath(SourcePath, NULL, LocalDir, W2A(ExecName), NULL);
	_makepath(SvcSourcePath, NULL, LocalDir, W2A(SvcName), NULL);

	long ReturnError = ERROR_SUCCESS;

	// Search for the first location that accepts the file on the remote
	// machine
	long CurrIndex;
	for (CurrIndex = 0; g_RemoteCopyLocs[CurrIndex][0] != NULL; CurrIndex ++) {

		CurrItemRemote = g_RemoteCopyLocs[CurrIndex][0];
		CurrItemLocal =  g_RemoteCopyLocs[CurrIndex][1];

		sprintf(DestDir,"%S\\%s", RemoteName, CurrItemRemote);

		sprintf(DestPathRemote, "%s\\%S", DestDir, ExecName);
		sprintf(DestPathLocal, "%s\\%S", CurrItemLocal, ExecName);

		sprintf(DestSvcRemote, "%s\\%S", DestDir, SvcName);
		sprintf(DestSvcLocal, "%s\\%S", CurrItemLocal, SvcName);

		sprintf(DestLogRemote, "%s\\%s", DestDir, SHARE_INSTALL_LOG_FILE);
		sprintf(DestLogLocal, "%s\\%s", CurrItemLocal, SHARE_INSTALL_LOG_FILE);

		RemoveAuth(RemoteName, T2W(CurrItemRemote), UserName, Pwd, Domain);
		AddAuth(RemoteName, T2W(CurrItemRemote), UserName, Pwd, Domain);

		CopyStatus = CopyFile(SourcePath, DestPathRemote, FALSE);
		if (CopyStatus != FALSE) {

			CopyStatus = CopyFile(SvcSourcePath, DestSvcRemote, FALSE);
			if (CopyStatus == FALSE)
				_unlink (DestPathRemote);
		}
		
		ReturnError = GetLastError();
		// KARL EDIT
		string baseErr ("Err Num = 0x");
		char numBuf [32];

		baseErr += _itoa(ReturnError, numBuf, 16);
		baseErr += "\n";
		baseErr += DestPathRemote;
		baseErr += "\n";
		baseErr += SourcePath;

		MessageBox(NULL, baseErr.c_str(), "Err Diagnosis", MB_OK);
		// END EDIT

		RemoveAuth(RemoteName, T2W(CurrItemRemote), UserName, Pwd, Domain);

		if (CopyStatus != FALSE)
			break;
	}

	if ( g_RemoteCopyLocs[CurrIndex][0] == NULL) {
		if (ReturnError != ERROR_SUCCESS)
			SET_GEN_ERR(512, ReturnError);
		return ERROR_GEN_FAILURE;
	}

	if (strlen(DestPathRemote) > MaxDestPathRemoteLen)
		return ERROR_GEN_FAILURE;

	if (strlen(DestPathLocal) > MaxDestPathLocalLen)
		return ERROR_GEN_FAILURE;

	if (strlen(DestDir) > MaxDestDirLen)
		return ERROR_GEN_FAILURE;

	if (strlen(DestSvcRemote) > MaxDestSvcRemoteLen)
		return ERROR_GEN_FAILURE;

	if (strlen(DestSvcLocal) > MaxDestSvcLocalLen)
		return ERROR_GEN_FAILURE;

	if (strlen(DestLogRemote) > MaxDestLogRemoteLen)
		return ERROR_GEN_FAILURE;

	if (strlen(DestLogLocal) > MaxDestLogLocalLen)
		return ERROR_GEN_FAILURE;

	wcscpy(ReturnDestPathRemote, T2W(DestPathRemote));
	wcscpy(ReturnDestPathLocal, T2W(DestPathLocal));
	wcscpy(ReturnDestDir, T2W(DestDir));
	wcscpy(ReturnDestSvcRemote, T2W(DestSvcRemote));
	wcscpy(ReturnDestSvcLocal, T2W(DestSvcLocal));
	wcscpy(ReturnDestLogRemote, T2W(DestLogRemote));
	wcscpy(ReturnDestLogLocal, T2W(DestLogLocal));

	return ERROR_SUCCESS;
}


long ProcessChildItems(HWND hDlg, HWND hTree, HWND hLog, HTREEITEM Parent, TVITEMEX * ParentInfo, 
					   WCHAR * User, WCHAR *Pwd, WCHAR * Domain, WCHAR *RemoteExec, WCHAR * ExecOnly, WCHAR * SvcOnly, WCHAR * InstallArgs, 
					   long * PassCount, long * FailCount, long * AttemptCount)
{
	HTREEITEM Child;
	TVITEMEX ChildInfo;

	USES_CONVERSION;

	Child = TreeView_GetChild(hTree, Parent);
	char ItemText[ITEM_TEXT_LEN];
	char LogText[2 * ITEM_TEXT_LEN];
	char ServerUNC[ITEM_TEXT_LEN] = "";

	long Status;

	while (Child != NULL && CancelPressed == FALSE) {

		ChildInfo.mask = TVIF_PARAM | TVIF_TEXT | TVIF_STATE ;
		ChildInfo.stateMask = TVIS_STATEIMAGEMASK;
		ChildInfo.pszText  = ItemText;
		ChildInfo.cchTextMax = ITEM_TEXT_LEN;
		ChildInfo.hItem = Child;

		TreeView_GetItem(hTree, &ChildInfo);

		if (ChildInfo.lParam == PARAM_SERVER 
			&& ((ChildInfo.state & TVIS_STATEIMAGEMASK) ==  INDEXTOSTATEIMAGEMASK(CHECKED_INDEX))){

			BOOL BackupTry = FALSE;

			sprintf (LogText," \"%s\"...", ItemText);
			AppendResLog(hLog, IDS_MACH_PROCESSING);
			AppendLog(hLog, LogText);

			(*AttemptCount) ++;

			WCHAR RemoteExecPathRemote[2* MAX_PATH];
			WCHAR RemoteExecPathLocal[2* MAX_PATH];
			WCHAR RemoteExecDir[2* MAX_PATH];
			WCHAR RemoteExecSvcRemote[2* MAX_PATH];
			WCHAR RemoteExecSvcLocal[2* MAX_PATH];
			WCHAR RemoteExecLogRemote[2* MAX_PATH];
			WCHAR RemoteExecLogLocal[2* MAX_PATH];

			WCHAR FullArgs [ 6 * CFG_STRING_LEN];

			BOOL CopyFailed = FALSE;

			Status = CopyInstallToRemote (A2W(ItemText), 
				ExecOnly, SvcOnly,
				User, Pwd, Domain,
				RemoteExecPathRemote, 2* MAX_PATH, 
				RemoteExecPathLocal, 2* MAX_PATH, 
				RemoteExecDir, 2 * MAX_PATH,
				RemoteExecSvcRemote, 2 * MAX_PATH,
				RemoteExecSvcLocal, 2 * MAX_PATH,
				RemoteExecLogRemote, 2 * MAX_PATH,
				RemoteExecLogLocal, 2 * MAX_PATH);

			if (Status == ERROR_SUCCESS) {

				swprintf (FullArgs,L"%s /InstallShareLog \"%s\\%S\"", InstallArgs, RemoteExecDir, SHARE_INSTALL_LOG_FILE);

				Status = InstallRemoteByService (hDlg, A2W(ItemText), User, Pwd, Domain, RemoteExecPathLocal, RemoteExecSvcLocal, FullArgs);
			}
			else {
				CopyFailed = TRUE;
			}

			if (Status == ERROR_SUCCESS && CancelPressed == FALSE) {
				AppendResLog(hLog, IDS_MACH_PASSED, TRUE);
				(*PassCount) ++;
			}
			else {
				AppendResLog(hLog, IDS_MACH_FAILED, TRUE);

				//if (CancelPressed == TRUE)
					//AppendLog(hlog, "\t Processing was cancelled.\r\n");
				if (CopyFailed == TRUE)
					AppendLog(hLog,"\tUnable to Copy Neccesary files to Remote Machine.\r\n");

				AppendInstFailCode(hLog, Status, W2T(RemoteExecLogRemote));
				CLEAR_ERR();
				(*FailCount) ++;
			}

			//Remove pushed/shared files from remote server.
			_wunlink(RemoteExecPathRemote);
			_wunlink(RemoteExecSvcRemote);
			_wunlink(RemoteExecLogRemote);
		}

		ProcessChildItems(hDlg, hTree, hLog,  Child, &ChildInfo, User, Pwd, Domain, RemoteExec, ExecOnly, SvcOnly, InstallArgs, PassCount, FailCount, AttemptCount);

		Child = TreeView_GetNextSibling(hTree, Child);
	}

	return ERROR_SUCCESS;
}

void SetAcctInputState(HWND dlg)
{
	BOOL DomainEnabled;

	if ((IsDlgButtonChecked(dlg, IDC_MACH_LOCAL_CHECK) == BST_UNCHECKED)
		&& (IsWindowEnabled (GetDlgItem(dlg, IDC_MACH_LOCAL_CHECK)) != FALSE))
		DomainEnabled = TRUE;
	else
		DomainEnabled = FALSE;

	SetDlgItemEnabled(dlg, IDC_MACH_DOMAIN_STATIC, DomainEnabled);
	SetDlgItemEnabled(dlg, IDC_MACH_DOMAIN_EDIT, DomainEnabled);
}

void SetEditBoxesEnabled(HWND dlg, BOOL EnableState)
{
	SetDlgItemEnabled(dlg, IDC_MACH_USER_EDIT, EnableState);
	SetDlgItemEnabled(dlg, IDC_MACH_PWD_EDIT, EnableState);
	SetDlgItemEnabled(dlg, IDC_MACH_LOCAL_CHECK, EnableState);
	SetAcctInputState(dlg);
	SetDlgItemEnabled(dlg, IDC_FREQ_COMBO, EnableState);
	SetDlgItemEnabled(dlg, IDC_PROXY_COMBO, EnableState);
	SetProxyEnableState(dlg);
	SetDlgItemEnabled(dlg, IDC_SYSLIST_USER_EDIT, EnableState);
	SetDlgItemEnabled(dlg, IDC_SYSLIST_PWD_EDIT, EnableState);
#if !defined(ASP_DEMO) && !defined (SYSLIST_ACC)
	SetDlgItemEnabled(dlg, IDC_SYSLIST_SERVER_EDIT, EnableState);
#endif

#if defined (SYSLIST_ACC)
	SetDlgItemEnabled(dlg, IDC_SYSLIST_ACCTID_EDIT, EnableState);
#endif

}

void SetActionsEnabled(HWND dlg, BOOL EnableState)
{
	SetDlgItemEnabled(dlg, IDC_CLOSE, EnableState);
	SetDlgItemEnabled(dlg, IDC_SCAN_NET, EnableState);
	SetDlgItemEnabled(dlg, IDC_INSTALL, EnableState);
	SetDlgItemEnabled(dlg, IDC_UNINSTALL, EnableState);
	SetDlgItemEnabled(dlg, IDC_PRUNE_UNAVAIL_CHECK, EnableState);
	SetDlgItemEnabled(dlg, IDC_PRUNE_INCOMPAT_CHECK, EnableState);
#ifndef SYSLIST_DEMO
	SetDlgItemEnabled(dlg, IDC_LOCAL_CHECK, EnableState);
#endif
}

long ProcessDlg(HWND dlg, BOOL IsInstall = TRUE)
{
	USES_CONVERSION;

	HWND hLog = GetDlgItem(dlg, IDC_LOG);
	HWND hTree = GetDlgItem (dlg, IDC_NETTREE);

	ClearLog(hLog);
	SpinDialog(dlg);

	if (IsInstall)
		AppendResLog(hLog, IDS_INST_START, TRUE);
	else	
		AppendResLog(hLog, IDS_UNINST_START, TRUE);
	
	char DlgItemText[CFG_STRING_LEN + 1];


	WCHAR MachUser[CFG_STRING_LEN + 1];
	WCHAR MachPwd[CFG_STRING_LEN + 1];
	WCHAR MachDomain[CFG_STRING_LEN + 1];

	//GetDlgItemText(dlg, IDC_MACH_USER_EDIT, DlgItemText, CFG_STRING_LEN);
	//GetDlgItemText(dlg, IDC_MACH_PWD_EDIT, DlgItemText, CFG_STRING_LEN);


	GetDlgItemText(dlg, IDC_SYSLIST_USER_EDIT, SyslistConfigUser,CFG_STRING_LEN);
	GetDlgItemText(dlg, IDC_SYSLIST_PWD_EDIT, SyslistConfigPwd,CFG_STRING_LEN);
	GetDlgItemText(dlg, IDC_SYSLIST_SERVER_EDIT, SyslistConfigServer,CFG_STRING_LEN);
	GetDlgItemText(dlg, IDC_SYSLIST_ACCTID_EDIT, SyslistConfigAcctID,CFG_STRING_LEN);

	GetDlgItemText(dlg, IDC_MACH_USER_EDIT, SyslistConfigMachUser,CFG_STRING_LEN);
	wcscpy(MachUser, A2W(SyslistConfigMachUser));

	GetDlgItemText(dlg, IDC_MACH_PWD_EDIT, SyslistConfigMachPwd,CFG_STRING_LEN);
	wcscpy(MachPwd, A2W(SyslistConfigMachPwd));

	if (IsDlgButtonChecked(dlg, IDC_MACH_LOCAL_CHECK) == FALSE) 
		GetDlgItemText(dlg, IDC_MACH_DOMAIN_EDIT, SyslistConfigMachDomain,CFG_STRING_LEN);
	else 
		strcpy(SyslistConfigMachDomain, ".");

	wcscpy(MachDomain, A2W(SyslistConfigMachDomain));


	if (IsInstall) {

		BOOL DialogIncomplete = FALSE;

	
		if (SyslistConfigUser[0] == '\0'
			|| SyslistConfigPwd[0] =='\0'
			|| SyslistConfigServer[0] == '\0') 

			DialogIncomplete = TRUE;

#ifdef SYSLIST_ACC
		if (SyslistConfigAcctID[0] == '\0')
			DialogIncomplete = TRUE;
#endif

		if (DialogIncomplete) {	
			AppendResLog (hLog, IDS_PLEASE_FILL_FORM, TRUE);
			LocaleMsgBox (dlg, IDS_PLEASE_FILL_FORM, IDS_PLEASE_FILL_FORM_CAP, MB_ICONEXCLAMATION);
			return ERROR_SUCCESS;
		}
	}

	SetEditBoxesEnabled(dlg, FALSE);
	SetActionsEnabled(dlg, FALSE);
	SetDlgItemEnabled(dlg, IDC_CANCEL, TRUE);

	CancelPressed = FALSE;

	long CurrSel;

	HWND ProxyMethod = GetDlgItem(dlg, IDC_PROXY_COMBO);
	CurrSel = SendMessage (ProxyMethod, CB_GETCURSEL, NULL, NULL);
	SyslistConfigProxyMethod = (ProxyMethodIndex) SendMessage (ProxyMethod, CB_GETITEMDATA, CurrSel, NULL);

	DlgItemText[0] = '\0';

	GetDlgItemText(dlg, IDC_PROXY_SERVER_EDIT, SyslistConfigProxyServer, CFG_STRING_LEN);
	GetDlgItemText(dlg, IDC_PROXY_PORT_EDIT, DlgItemText, CFG_STRING_LEN);

	if (DlgItemText[0] != '\0') {
		strcat (SyslistConfigProxyServer, ":");
		strcat (SyslistConfigProxyServer, DlgItemText);
	}

	HWND hMethod = GetDlgItem(dlg, IDC_FREQ_COMBO);

	CurrSel = SendMessage (hMethod, CB_GETCURSEL, NULL, NULL);
	SyslistConfigMethod = (CmdMethodIndex) SendMessage (hMethod, CB_GETITEMDATA, CurrSel, NULL);

	WCHAR ArgString[ 5 * CFG_STRING_LEN + 1];

	if (IsInstall) {
		swprintf (ArgString, L"/S /Method %S /Server \"%S\" /User \"%S\" /Pwd \"%S\" /AccountID \"%S\" /Method \"%S\" /ProxyMethod \"%S\" /ProxyServer \"%S\"",
				StringFromMethod(SyslistConfigMethod), 
				SyslistConfigServer, 
				SyslistConfigUser, 
				SyslistConfigPwd,
				SyslistConfigAcctID,
				StringFromMethod(SyslistConfigMethod),
				StringFromProxyMethod(SyslistConfigProxyMethod),
				SyslistConfigProxyServer);

	}
	else {
		ArgString[0] = L'\0';
	}

#if 0
	long Status;

	Status = ShareSource();
	if (Status != ERROR_SUCCESS) {
		AppendResLog(hLog,IDS_ERR_NO_SOURCE_SHARE, TRUE);

		SetEditBoxesEnabled(dlg, TRUE);
		SetActionsEnabled(dlg, TRUE);
		SetDlgItemEnabled(dlg, IDC_CANCEL, FALSE);

		return ERROR_GEN_FAILURE;
	}
#endif
	
	long PassCount = 0;
	long FailCount = 0;
	long AttemptCount = 0;

	_unlink(SHARE_INSTALL_LOG_PATH);

	WCHAR RemoteExec[CFG_STRING_LEN];
	WCHAR SvcOnly[CFG_STRING_LEN];
	WCHAR ExecOnly[CFG_STRING_LEN];
	
	wcscpy (SvcOnly, T2W(LOCAL_SHARE_SVC));

	if (IsInstall) {
		wcscpy(RemoteExec,T2W(LOCAL_INSTALL_NAME));
		wcscpy(ExecOnly, T2W(LOCAL_SHARE_EXEC));
	}
	else {
		wcscpy(RemoteExec, T2W(LOCAL_UNINSTALL_NAME));
		wcscpy(ExecOnly, T2W(LOCAL_SHARE_UNINST));
	}
	
	ProcessChildItems(dlg, hTree, hLog, NULL, NULL, MachUser, MachPwd, MachDomain,RemoteExec, ExecOnly, SvcOnly, ArgString, &PassCount, &FailCount, &AttemptCount);

#if 0
	UnShareSource();
#endif

	char StatusText[1024];
	sprintf (StatusText, " %d\r\n", AttemptCount);
	if (IsInstall)
		AppendResLog(hLog, IDS_INST_TOTAL_ATTEMPT);
	else
		AppendResLog(hLog, IDS_UNINST_TOTAL_ATTEMPT);

	AppendLog(hLog,StatusText);

	sprintf (StatusText," %d\r\n", FailCount);
	if (IsInstall)
		AppendResLog(hLog, IDS_INST_TOTAL_FAIL);
	else
		AppendResLog(hLog, IDS_UNINST_TOTAL_FAIL);

	AppendLog(hLog,StatusText);

	sprintf (StatusText," %d\r\n", PassCount);
	if (IsInstall)
		AppendResLog(hLog, IDS_INST_TOTAL_PASS);
	else
		AppendResLog(hLog, IDS_UNINST_TOTAL_PASS);

	AppendLog(hLog,StatusText);

	if (CancelPressed) 
		AppendResLog(hLog, IDS_INST_CANCEL, TRUE);
	else {
		if (IsInstall)
			AppendResLog(hLog, IDS_INST_COMPLETE, TRUE);
		else
			AppendResLog(hLog, IDS_UNINST_COMPLETE, TRUE);

		if (AttemptCount == 0) {
			AppendResLog(hLog, IDS_NO_MACH, TRUE);
			LocaleMsgBox (dlg, IDS_PLEASE_CHOOSE_MACH, IDS_PLEASE_CHOOSE_MACH_CAP, MB_ICONEXCLAMATION);
		}
	}

	SetEditBoxesEnabled(dlg, TRUE);
	SetActionsEnabled(dlg, TRUE);
	SetDlgItemEnabled(dlg, IDC_CANCEL, FALSE);

	CancelPressed = FALSE;

	return ERROR_SUCCESS;
}

long InitDlg (HWND dlg)
{
	HWND hLog = GetDlgItem(dlg, IDC_LOG);

	ClearLog(hLog);
	SpinDialog(dlg);
	AppendResLog(hLog,IDS_INTRO_SCAN_INVITE, TRUE);

	HWND hLocalDomCheck = GetDlgItem(dlg, IDC_LOCAL_CHECK);
	SendMessage(hLocalDomCheck, BM_SETCHECK, BST_CHECKED, NULL);

#ifdef SYSLIST_DEMO
	SetDlgItemEnabled(dlg, IDC_LOCAL_CHECK, FALSE);
#endif

#ifndef SYSLIST_ACC
	SetDlgItemEnabled(dlg, IDC_SYSLIST_ACCTID_STATIC, FALSE);
	SetDlgItemEnabled(dlg, IDC_SYSLIST_ACCTID_EDIT, FALSE);
#endif

	char FinalVersionString[RES_STRING_SIZE];
	sprintf (FinalVersionString, "v%s", SyslistVersionString);

	SetDlgItemText(dlg, IDC_VERSION_STATIC, FinalVersionString);

	//HWND hPruneUnavailCheck = GetDlgItem(dlg, IDC_PRUNE_UNAVAIL_CHECK);
	//SendMessage(hPruneUnavailCheck, BM_SETCHECK, BST_CHECKED, NULL);

	//HWND hPruneIncompatCheck = GetDlgItem(dlg, IDC_PRUNE_INCOMPAT_CHECK);
	//SendMessage(hPruneIncompatCheck, BM_SETCHECK, BST_CHECKED, NULL);

	SetDlgItemEnabled(dlg, IDC_CANCEL, FALSE);

	InitDlgLangStrings (dlg, g_InstLangItems, g_hLangInstance);
	
	HWND hFreqCombo = GetDlgItem(dlg, IDC_FREQ_COMBO);

	SendMessage (hFreqCombo, CB_SETITEMDATA, 0, MethodDisable);
	SendMessage (hFreqCombo, CB_SETITEMDATA, 1, MethodStartup);
	SendMessage (hFreqCombo, CB_SETITEMDATA, 2, MethodDay);
	SendMessage (hFreqCombo, CB_SETITEMDATA, 3, MethodWeek);
	SendMessage (hFreqCombo, CB_SETITEMDATA, 4, MethodMonth);

	SetDlgItemText(dlg, IDC_SYSLIST_USER_EDIT, SyslistConfigUser);
	SetDlgItemText(dlg, IDC_SYSLIST_PWD_EDIT, SyslistConfigPwd);
	SetDlgItemText(dlg, IDC_SYSLIST_ACCTID_EDIT, SyslistConfigAcctID);

	
	if (SyslistConfigServer[0] == '\0') {
		strcpy(SyslistConfigServer, "https://www.SysList.com");
	}

#if defined(ASP_DEMO) || defined(SYSLIST_ACC)
	SetDlgItemEnabled(dlg, IDC_SYSLIST_SERVER_EDIT, FALSE);
#endif
	SetDlgItemText(dlg, IDC_SYSLIST_SERVER_EDIT, SyslistConfigServer);


	SetDlgItemText(dlg, IDC_MACH_USER_EDIT, SyslistConfigMachUser);
	SetDlgItemText(dlg, IDC_MACH_PWD_EDIT, SyslistConfigMachPwd);
	SetAcctInputState(dlg);

	long ComboCount = SendMessage(hFreqCombo, CB_GETCOUNT, NULL, NULL);

	for (long ComboIndex = 0; ComboIndex < ComboCount; ComboIndex ++) {
		CmdMethodIndex CurrIndex;

		CurrIndex = (CmdMethodIndex) SendMessage (hFreqCombo, CB_GETITEMDATA, ComboIndex, NULL);

		if (CurrIndex == SyslistConfigMethod) {
			SendMessage (hFreqCombo, CB_SETCURSEL, CurrIndex, NULL);
			break;
		}
	}


	HWND ProxyMethodID;
	ProxyMethodID = GetDlgItem(dlg, IDC_PROXY_COMBO);

	SendMessage (ProxyMethodID, CB_SETITEMDATA, 0, ProxyMethodDirect);
	SendMessage (ProxyMethodID, CB_SETITEMDATA, 1, ProxyMethodIE);
	SendMessage (ProxyMethodID, CB_SETITEMDATA, 2, ProxyMethodManual);
	//SendMessage (ProxyMethodID, CB_SETITEMDATA, 3, ProxyMethodAuto);

	ComboCount = SendMessage(ProxyMethodID, CB_GETCOUNT, NULL, NULL);

	for (ComboIndex = 0; ComboIndex < ComboCount; ComboIndex ++) {
		ProxyMethodIndex CurrIndex;

		CurrIndex = (ProxyMethodIndex) SendMessage (ProxyMethodID, CB_GETITEMDATA, ComboIndex, NULL);

		if (CurrIndex == SyslistConfigProxyMethod) {
			SendMessage (ProxyMethodID, CB_SETCURSEL, CurrIndex, NULL);
			break;
		}
	}


	char ProxyServerText[CFG_STRING_LEN];
	char ProxyPortText[CFG_STRING_LEN];

	ProxyPortText [0] = '\0';
	ProxyServerText [0] = '\0';

	sscanf (SyslistConfigProxyServer, "%[^:]:%s", ProxyServerText, ProxyPortText);

	SetDlgItemText(dlg, IDC_PROXY_SERVER_EDIT, ProxyServerText);
	SetDlgItemText(dlg, IDC_PROXY_PORT_EDIT, ProxyPortText);

	SetProxyEnableState(dlg);

	HICON IconHandle;

	IconHandle = LoadIcon(g_hInstance , MAKEINTRESOURCE(IDI_INSTALLER16));
	SendMessage (dlg, WM_SETICON, (WPARAM) ICON_SMALL, (LPARAM) IconHandle);

	IconHandle = LoadIcon(g_hInstance, MAKEINTRESOURCE(IDI_INSTALLER32));
	SendMessage (dlg, WM_SETICON, (WPARAM) ICON_BIG, (LPARAM) IconHandle);

	return ERROR_SUCCESS;
}

void DoScanNetwork(HWND hDlg) 
{
	HWND hLocalDomCheck = GetDlgItem(hDlg, IDC_LOCAL_CHECK);
	HWND hPruneUnvailCheck = GetDlgItem(hDlg, IDC_PRUNE_UNAVAIL_CHECK);
	HWND hPruneIncompat = GetDlgItem(hDlg, IDC_PRUNE_INCOMPAT_CHECK);

	HWND hLog = GetDlgItem(hDlg, IDC_LOG);

	CancelPressed = FALSE;
	
	SetActionsEnabled(hDlg, FALSE);
	SetDlgItemEnabled(hDlg, IDC_CANCEL, TRUE);

	BOOL IsLocal = SendMessage (hLocalDomCheck, BM_GETCHECK, NULL, NULL);
	BOOL PruneUnavail= SendMessage (hPruneUnvailCheck, BM_GETCHECK, NULL, NULL);
	BOOL PruneIncompat = SendMessage (hPruneIncompat, BM_GETCHECK, NULL, NULL);

	AppendResLog(hLog, IDS_NET_SCAN_START, TRUE);

	long ItemCount = 0;
	InitNetEnumDlgTree (hDlg, NULL, NULL, IsLocal, PruneUnavail, PruneIncompat, &ItemCount);

	AppendLogCRLF(hLog);

	if (CancelPressed)
		AppendResLog(hLog, IDS_NET_SCAN_CANCEL);
	else
		AppendResLog(hLog, IDS_NET_SCAN_DONE);

	SetActionsEnabled(hDlg, TRUE);
	SetDlgItemEnabled(hDlg, IDC_CANCEL, FALSE);
	
	CancelPressed = FALSE;
}

BOOL CALLBACK NetDialogProc(
  HWND hwndDlg,  // handle to dialog box
  UINT uMsg,     // message
  WPARAM wParam, // first message parameter
  LPARAM lParam) // second message parameter
{
	bool processed = false;
	static long ID = 0;
	switch (uMsg) {

	case WM_INITDIALOG:
		processed = true;

		InitDlg (hwndDlg);
		
		break;

	case WM_COMMAND:
		processed = true;

		switch (LOWORD ( wParam ) ) {

		case IDC_MACH_LOCAL_CHECK:
			SetAcctInputState (hwndDlg);
			break;

		case IDC_INSTALL:
			ProcessDlg (hwndDlg);
			break;

		case IDC_UNINSTALL:
			ProcessDlg (hwndDlg, FALSE);
			break;

		case IDC_CANCEL:
			CancelPressed = TRUE;
			break;

		case IDC_PROXY_COMBO:
			if (HIWORD (wParam) == CBN_SELCHANGE) {
				SetProxyEnableState(hwndDlg);
			}
			break;

		case IDC_CLOSE:
			EndDialog(hwndDlg, INST_OK);
			break;

		case IDC_SCAN_NET: 
			DoScanNetwork(hwndDlg);
			break;

		default:
			break;
		}

		break;

	case WM_CLOSE:
		processed = true;
		EndDialog(hwndDlg, INST_OK);
		break;

	case UM_CHECKSTATECHANGE:
	{
		HWND hTreeView;

		hTreeView = GetDlgItem(hwndDlg, IDC_NETTREE);

		HTREEITEM CheckItem = (HTREEITEM) lParam;
		hTreeView = GetDlgItem(hwndDlg, IDC_NETTREE);
		RecurseSetChildChecked(hTreeView, CheckItem);
		RecurseSetParentChecked(hTreeView, CheckItem);
		ATLTRACE ("CHECKED!!!!\n");
	}
		break;

	case WM_NOTIFY:

		//ATLTRACE("WM_NOTIFY %d, WPARAM = %d, LPARAM = %d\n", ID++, wParam, lParam);

		switch (wParam) {

		case IDC_NETTREE:
		{	
			LPNMHDR NotifyInfo;

			NotifyInfo = (LPNMHDR) lParam;

			//ATLTRACE("TREE CODE is %d\n", NotifyInfo->code);

			switch ( NotifyInfo->code) {

			case TVN_SELCHANGED:
				ATLTRACE("TVN_SELCHANGE\n");
				break;

			case NM_CLICK:
			{
				TVHITTESTINFO HitTest = {0};
				DWORD dwPos = GetMessagePos();
				
				HitTest.pt.x = GET_X_LPARAM(dwPos);
				HitTest.pt.y = GET_Y_LPARAM(dwPos);
				MapWindowPoints (HWND_DESKTOP, NotifyInfo->hwndFrom, & HitTest.pt, 1);
				
				TreeView_HitTest(NotifyInfo->hwndFrom, &HitTest);

				if (TVHT_ONITEMSTATEICON & HitTest.flags)
					PostMessage(hwndDlg, UM_CHECKSTATECHANGE, 0, (LPARAM) HitTest.hItem);

			}

			break;

			default:
				break;
			}
		}

		break;

		default:
			break;
		}

	default:
		break;
	}

	return processed;
}

long InitLicDlg(HWND hDlg)
{

	BOOL Status;

	HRSRC LicenseRes;
	HGLOBAL LicenseGlobal;
	DWORD LicenseSize;
	TCHAR * ResLicString;
	TCHAR * ModLicString;

	LicenseRes = FindResource(g_hLangInstance, MAKEINTRESOURCE(IDR_RAW_TEXT_LIC), _T("RAW_TEXT"));
	if (LicenseRes == NULL)
		LicenseRes = FindResource(g_hInstance, MAKEINTRESOURCE(IDR_RAW_TEXT_LIC), _T("RAW_TEXT"));

	LicenseSize = SizeofResource (g_hLangInstance, LicenseRes);

	LicenseGlobal = LoadResource (g_hLangInstance, LicenseRes);
	ResLicString = (TCHAR *) LockResource (LicenseGlobal);

	ModLicString = new TCHAR[LicenseSize + 1];
	memcpy (ModLicString, ResLicString, LicenseSize);
	ModLicString[LicenseSize] = '\0';

	Status = SetDlgItemText(hDlg, IDC_LIC_TEXT, ModLicString);

	delete ModLicString;

	char FinalVersionString[RES_STRING_SIZE];
	sprintf (FinalVersionString, "v%s", SyslistVersionString);

	SetDlgItemText(hDlg, IDC_LIC_VERSION_STATIC, FinalVersionString);

	InitDlgLangCombo (hDlg, IDC_LANG_COMBO, SyslistConfigLangID);
	InitDlgLangStrings (hDlg, g_LicLangItems, g_hLangInstance);

	return ERROR_SUCCESS;
}

BOOL CALLBACK LicDialogProc(
  HWND hwndDlg,  // handle to dialog box
  UINT uMsg,     // message
  WPARAM wParam, // first message parameter
  LPARAM lParam) // second message parameter
{
	bool processed = false;
	static long ID = 0;
	switch (uMsg) {

	case WM_INITDIALOG:
		processed = true;

		InitLicDlg (hwndDlg);
		
		break;

	case WM_COMMAND:
		processed = true;

		switch (LOWORD ( wParam ) ) {

		case IDC_LIC_ACCEPT:
			EndDialog(hwndDlg, INST_OK);
			break;

		case IDC_LIC_CANCEL:
			EndDialog(hwndDlg, INST_CANCEL);
			break;

		case IDC_LANG_COMBO:
			if (HIWORD (wParam) == CBN_SELCHANGE) {
				char NewLangName[RES_STRING_SIZE];
				long CurrSel  =	SendMessage((HWND) lParam, CB_GETCURSEL, NULL, NULL);
				SendMessage((HWND) lParam, WM_GETTEXT, RES_STRING_SIZE, (LPARAM) NewLangName);
				SetLangInstance (NewLangName, g_LLangNameInfo, SyslistConfigLangID);
				InitLicDlg(hwndDlg);
			}
			break;
		default:
			break;
		}

		break;

	case WM_CLOSE:
		processed = true;
		EndDialog(hwndDlg, INST_CANCEL);
		break;

	default:
		break;
	}

	return processed;
}



int APIENTRY WinMain(HINSTANCE hInstance,
                     HINSTANCE hPrevInstance,
                     LPSTR     lpCmdLine,
                     int       nCmdShow)
{
	int Status;
	int Error;
	BOOL Passed;

	
	if (IsRunningAsAdmin() == FALSE) {
		MessageBox( NULL, "This program must be run by an Administrator", "Admin Rights Required", MB_ICONEXCLAMATION);
		return -999;
	}

	g_hInstance = hInstance;

	CoInitialize(NULL);

	HRESULT hres = CoInitializeSecurity ( 
					NULL, -1, NULL, NULL, 
					RPC_C_AUTHN_LEVEL_PKT_PRIVACY, 
					RPC_C_IMP_LEVEL_IMPERSONATE, 
					NULL, 
					EOAC_SECURE_REFS, //change to EOAC_NONE if you change dwAuthnLevel to RPC_C_AUTHN_LEVEL_NONE
					NULL );
	
	INITCOMMONCONTROLSEX CommCtlInit;
	CommCtlInit.dwSize = sizeof (CommCtlInit);
	CommCtlInit.dwICC = ICC_TREEVIEW_CLASSES;

	Passed = InitCommonControlsEx(&CommCtlInit);
	if (Passed == FALSE) 
		return 1;

	ReadRegInfo();

	InitLangDLLInfo(hInstance,IDS_CFG_SLANG, IDS_CFG_LLANG, IDS_CFG_ILANG, LANG_DLL_SPEC);
	SetDefaultLangInstance(SyslistConfigLangID);

	Status = DialogBox(hInstance, MAKEINTRESOURCE(IDD_LIC_LANG), NULL, LicDialogProc);

	if (Status < 0 ) {
		return 1;
	}

	GetLocalNames();

	OpenLog();

	Status = DialogBox(hInstance, MAKEINTRESOURCE(IDD_NET_INSTALL), NULL, NetDialogProc);

	if (Status < 0 ) {
		Error = GetLastError();
		return 1;
	}

	CoUninitialize();

	return 0;
}



