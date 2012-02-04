// TestCollect.cpp : Defines the entry point for the console application.
//

#include "stdafx.h"
#include "MasterCollector.h"
#include "../TestTransport/ScreenTransport.h"
#include "../TestTransport/FileTransport.h"
#include "../TestTransport/SyslistHTTPTransport.h"
#include "../TestInstConfig/SyslistRegistry.h"
#include "../TestInstConfig/SyslistProxyMethod.h"
#include "../TestInstConfig/SyslistMethod.h"
#include "WMIUtil.h"

extern int __argc;
extern char ** __argv;

static const char * ASP_SERVER_NAME = "https://www.Syslist.com";

static char SyslistConfigInstallDir[CFG_STRING_LEN] = "";
static char SyslistConfigServer[CFG_STRING_LEN] = "";
static char SyslistConfigUser[CFG_STRING_LEN] = "";
static char SyslistConfigPwd[CFG_STRING_LEN] = "";
static char SyslistConfigID[CFG_STRING_LEN] = "";
static CmdMethodIndex SyslistConfigMethod = MethodDisable;
static ProxyMethodIndex SyslistConfigProxyMethod = ProxyMethodDirect;
static char SyslistConfigProxyServer[CFG_STRING_LEN] = "";
static SYSTEMTIME SyslistConfigLastRun = {0};
static SYSTEMTIME SyslistConfigInstallRun = {0};
static long SyslistConfigFailedContact = 0;

static const __int64 OneSecond = 1e7L;     // 1 Second
static const __int64 OneMinute = OneSecond * 60;
static const __int64 OneHour = OneMinute * 60;
static const __int64 OneDay = OneHour * 24;
static const __int64 OneWeek = OneDay * 7;
static const __int64 OneMonth = OneDay * 30;

#ifdef DEBUG
	static const __int64 DemoTerm = 2 * OneMinute;
#else
	static const __int64 DemoTerm = 2 * OneWeek;
#endif

long ReadInstallDir()
{
	AutoRegKey SyslistRegKeyMain;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC_MAIN, NULL, KEY_READ, & SyslistRegKeyMain);
	if (Status != ERROR_SUCCESS)
		return Status;

	char ReturnRegString[CFG_STRING_LEN];
	unsigned long ReturnSize;
	DWORD ReturnType;

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKeyMain, NULL, NULL, &ReturnType, (LPBYTE) ReturnRegString , &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		strcpy(SyslistConfigInstallDir,ReturnRegString);

	BOOL Succeeded;

	Succeeded = SetCurrentDirectory(SyslistConfigInstallDir);
	Succeeded = SetEnvironmentVariable ("TMP", SyslistConfigInstallDir);
	Succeeded = SetEnvironmentVariable ("TEMP",  SyslistConfigInstallDir);

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

#if defined(ASP_DEMO) || defined(SYSLIST_ACC)
	strcpy(SyslistConfigServer, ASP_SERVER_NAME);
#else
	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_SERVER_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		strcpy(SyslistConfigServer,ReturnRegString);
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
	Status = RegQueryValueEx(SyslistRegKey, REG_METHOD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		SyslistConfigMethod = MethodFromString(ReturnRegString);
	else
		SyslistConfigMethod = MethodDisable;

	ReturnSize = sizeof(SYSTEMTIME);
	Status = RegQueryValueEx(SyslistRegKey, REG_LASTRUN_VALUE, NULL, &ReturnType, (LPBYTE) &SyslistConfigLastRun, &ReturnSize);
	if (Status != ERROR_SUCCESS || ReturnSize != sizeof(SYSTEMTIME) || ReturnType != REG_BINARY)
		ZeroMemory(&SyslistConfigLastRun, sizeof(SYSTEMTIME));

#ifdef SYSLIST_DEMO
	ReturnSize = sizeof(SYSTEMTIME);
	Status = RegQueryValueEx(SyslistRegKey, REG_INSTALLRUN_VALUE, NULL, &ReturnType, (LPBYTE) &SyslistConfigInstallRun, &ReturnSize);
	if (Status != ERROR_SUCCESS || ReturnSize != sizeof(SYSTEMTIME) || ReturnType != REG_BINARY)
		ZeroMemory(&SyslistConfigInstallRun, sizeof(SYSTEMTIME));

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_FAILED_CONTACT_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString , &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		SyslistConfigFailedContact = atoi(ReturnRegString);
	else
		SyslistConfigFailedContact = 0;

#endif
		
	return ERROR_SUCCESS;

}

long WriteRegInfo()
{
	AutoRegKey SyslistRegKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_WRITE, & SyslistRegKey);
	if (Status != ERROR_SUCCESS)
		return Status;

	GetSystemTime(&SyslistConfigLastRun);
	Status = RegSetValueEx (SyslistRegKey, REG_LASTRUN_VALUE, NULL, REG_BINARY, (BYTE *) & SyslistConfigLastRun, sizeof (SYSTEMTIME));

#ifdef SYSLIST_DEMO
	if (SyslistConfigInstallRun.wYear == 0)
		Status = RegSetValueEx (SyslistRegKey, REG_INSTALLRUN_VALUE, NULL, REG_BINARY, (BYTE *) & SyslistConfigLastRun, sizeof (SYSTEMTIME));

	char NumBuf[12];
	_itoa(SyslistConfigFailedContact, NumBuf, 10);
	Status = RegSetValueEx (SyslistRegKey, REG_FAILED_CONTACT_VALUE, NULL, REG_SZ, (BYTE *) NumBuf, strlen (NumBuf) + 1);

#endif
	
	RegDeleteValue (SyslistRegKey, REG_LASTRUN_ALIGN_VALUE);

	return ERROR_SUCCESS;
}


void LocalTimeTo64BitTime( SYSTEMTIME * InputTime, LARGE_INTEGER & Destination )
{
    FILETIME ftNow;
    ::SystemTimeToFileTime(InputTime, &ftNow );

    Destination.LowPart = ftNow.dwLowDateTime;
    Destination.HighPart = ftNow.dwHighDateTime;
}


BOOL CheckRun()
{
	
	SYSTEMTIME CurrSystemTime = {0};
	GetSystemTime (&CurrSystemTime);

	LARGE_INTEGER LastRunTime;
	LARGE_INTEGER CurrTime;

	__int64 Delta;

	LocalTimeTo64BitTime(&SyslistConfigLastRun, LastRunTime);
	LocalTimeTo64BitTime(&CurrSystemTime, CurrTime);

	Delta = CurrTime.QuadPart - LastRunTime.QuadPart;

	switch (SyslistConfigMethod) {

	case MethodDisable:  // Never Run
	case MethodStartup:  // Should already have a trigger for this
		return FALSE;

	case MethodDay:
		if (Delta > OneDay)
			return TRUE;

		break;

	case MethodWeek:
		if (Delta > OneWeek)
			return TRUE;

		break;

	case MethodMonth:
		if (Delta > OneMonth)
			return TRUE;

		break;
	}

	return FALSE;
}


BOOL CheckTerm() {
	
	if (SyslistConfigInstallRun.wYear == 0)
		return TRUE;

	SYSTEMTIME CurrSystemTime = {0};
	GetSystemTime (&CurrSystemTime);

	LARGE_INTEGER InstallRunTime;
	LARGE_INTEGER CurrTime;

	__int64 Delta;

	LocalTimeTo64BitTime(&SyslistConfigInstallRun, InstallRunTime);
	LocalTimeTo64BitTime(&CurrSystemTime, CurrTime);

	Delta = CurrTime.QuadPart - InstallRunTime.QuadPart;

	if (Delta < 0 || Delta > DemoTerm)
		return FALSE;

	return TRUE;

}


int APIENTRY WinMain(HINSTANCE hInstance,
                     HINSTANCE hPrevInstance,
                     LPSTR     lpCmdLine,
                     int       nCmdShow)
{

	long Status;
	Status = ReadInstallDir();
	if (Status != ERROR_SUCCESS)
		return -5;

	Status = ReadRegInfo();
	if (Status != ERROR_SUCCESS)
		return -5;


#ifdef SYSLIST_DEMO
#ifndef ASP_DEMO
	if (SyslistConfigFailedContact > 10 ) // one week of trying...
		return -15;

	if (CheckTerm() == FALSE) {
		return -7;
	}
#endif
#endif

	long CurrArg;
	BOOL IsCheckRun = FALSE;

	for (CurrArg = 0; CurrArg < __argc; CurrArg ++) {
		if (!stricmp(__argv[CurrArg],"/C") || !stricmp(__argv[CurrArg],"-c"))
			IsCheckRun = TRUE;
	}

	if (IsCheckRun) {
		BOOL ShouldRun;
		ShouldRun = CheckRun();
		if (ShouldRun == FALSE)
			return 0;
	}
		
	// For COM functionality that we *may* require
	CoInitialize(NULL);
	HRESULT hres = CoInitializeSecurity ( 
					NULL, -1, NULL, NULL, 
					RPC_C_AUTHN_LEVEL_PKT_PRIVACY, 
					RPC_C_IMP_LEVEL_IMPERSONATE, 
					NULL, 
					EOAC_SECURE_REFS, //change to EOAC_NONE if you change dwAuthnLevel to RPC_C_AUTHN_LEVEL_NONE
					NULL );
	
	
	Status =  WMIUtil::Init();
//	if (Status != ERROR_SUCCESS) {
//		MessageBox(NULL, "WMI Failed Init", "System Failure", MB_OK);
//		return -1;
//	}

	SyslistHTTPTransport<HTTPTransport> HTTPTransportObj ;
	SyslistHTTPTransport<HTTPSecureTransport> HTTPSecureTransportObj;

	FileTransport FileTransportObj;

	DataTransportProto * ReportTransport;
	HTTPTransport * ProxyTransport = NULL;

	char TransportScan[64] = "";

	sscanf(SyslistConfigServer,"%[^:]", TransportScan);
	
	if (!stricmp(TransportScan, HTTPTransportObj.HandlePrefix())) {
		ReportTransport = &HTTPTransportObj;
		ProxyTransport = &HTTPTransportObj;
	}
	else if (!stricmp(TransportScan, HTTPSecureTransportObj.HandlePrefix())) {
		ReportTransport = &HTTPSecureTransportObj;
		ProxyTransport = &HTTPSecureTransportObj;
	}	
	else if (!stricmp(TransportScan, FileTransportObj.HandlePrefix()))
		ReportTransport  = & FileTransportObj;
	else
		return -3;

	if (ProxyTransport != NULL) {
		ProxyTransport->SetProxyInfo (SyslistConfigProxyServer, SyslistConfigProxyMethod);
	}

	MasterCollector Collector;
	NVDataItem * Data;

	Status = Collector.Collect(&Data);
	if (Status != ERROR_SUCCESS) {
#ifdef INSTRUMENTED
		FileTransportObj.OpenURI ("File://c:/failFile.txt");
		FileTransportObj.TransmitData(Data);
		FileTransportObj.Close();
#endif
		return -4;
	}

	Status = ReportTransport->OpenURI(SyslistConfigServer);
	if (Status != ERROR_SUCCESS) {
		SyslistConfigFailedContact ++;
		WriteRegInfo();

		return -6;
	}

	Status = ReportTransport->TransmitData (Data);
	if (Status != ERROR_SUCCESS) {
		SyslistConfigFailedContact ++;
		WriteRegInfo();
		return Status;
	}

	Status = ReportTransport->Close();

	SyslistConfigFailedContact = 0;

	WriteRegInfo();

	WMIUtil::Uninit();
	CoUninitialize();

#ifdef INSTRUMENTED
	MessageBox (NULL, "Clean Exit", "Report", MB_OK);
#endif
	return 0;
}
