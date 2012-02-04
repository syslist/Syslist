// TestInstConfig.cpp : Defines the entry point for the application.
//

#include "stdafx.h"
#include "Resource.h"
#include "../TestWinCrypt/SimpleCrypt.h"
#include "SyslistMethod.h"
#include "SyslistProxyMethod.h"
#include "SyslistRegistry.h"
#include "SyslistIntLang.h"
#include "SyslistVersion.h"
#include "../SCASetup/SyslistAcct.h"
#include "FileConfig.h"

#include <exdisp.h>
#include <ACLApi.h>

static const long CFG_CANCELLED = 1;
static const long CFG_OK = 0;
static const char * SYSLIST_DEFAULT_SERVER = "https://www.SysList.com";
static const long CMD_STRING_SIZE = 1024;
static const char * CMD_SEP_STR = " \t";
static const COLLECTOR_WAIT = 300000;

static const char * LANG_DLL_SPEC = "SyslistLang_*.dll";

static const char * COLLECTOR_EXEC = "SCAInv.exe";

static const char * INSTALL_POST_SUBURL = "login.php";

static const long CFG_STRING_LEN = 512;

static LangItem g_ConfigLangItems[] = {
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_DISABLED},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_STARTUP},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_DAILY},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_WEEKLY},
	{ IDC_FREQ_COMBO, CB_ADDSTRING, IDS_CB_MONTHLY},
	{ IDC_LANG_STATIC, WM_SETTEXT, IDS_LANG_STATIC},
	{ IDC_SERVER_STATIC, WM_SETTEXT, IDS_SERVER_STATIC},
	{ IDC_FREQ_STATIC, WM_SETTEXT, IDS_FREQ_STATIC},
	{ IDC_ID_STATIC, WM_SETTEXT, IDS_ID_STATIC},
	{ IDC_USER_STATIC, WM_SETTEXT, IDS_USER_STATIC},
	{ IDC_PWD_STATIC, WM_SETTEXT, IDS_PWD_STATIC},
	{ IDC_ACCTID_STATIC, WM_SETTEXT, IDS_ACCTID_STATIC},
	{ IDC_CFG_OK, WM_SETTEXT, IDS_OK},
	{ IDC_CFG_CANCEL, WM_SETTEXT, IDS_CANCEL},
	{ IDC_INVENTORY, WM_SETTEXT, IDS_INVENTORY},
	{ IDC_PROXY_COMBO_STATIC, WM_SETTEXT, IDS_PROXY_COMBO_STATIC},
	{ IDC_PROXY_SERVER_STATIC, WM_SETTEXT, IDS_PROXY_SERVER_STATIC},
	{ IDC_PROXY_PORT_STATIC, WM_SETTEXT, IDS_PROXY_PORT_STATIC},
	{ IDC_PROXY_COMBO, CB_ADDSTRING, IDS_NO_PROXY},
	{ IDC_PROXY_COMBO, CB_ADDSTRING, IDS_IE_PROXY},
	{ IDC_PROXY_COMBO, CB_ADDSTRING, IDS_MANUAL_PROXY},
	{ LANG_END }
};

//typedef map <string, pCommandHandler> CommandMap;

CommandHandler HandleSilent;
CommandHandler HandleMethod;
CommandHandler HandleServer;
CommandHandler HandleProxyMethod;
CommandHandler HandleProxyServer;
CommandHandler HandleAlign;
CommandHandler HandleLang;
CommandHandler HandleHelp;
CommandHandler HandleUser;
CommandHandler HandlePwd;
CommandHandler HandleAcctID;
CommandHandler HandleInstall;
CommandHandler HandleInstallShare;
CommandHandler HandleConfig;

static CommandLineItem g_CtrlCmdItems[] = {
	{"S"      ,	HandleSilent},
	{"Silent" , HandleSilent},
	{"Install" , HandleInstall},
	{"InstallShareLog", HandleInstallShare},
	{"Help"   , HandleHelp},
	{NULL}
};

static CommandLineItem g_ConfigCmdItem[] = {
	{"Config", HandleConfig},
	{NULL}
};

static CommandLineItem g_RegistryCmdItems[] = {
	{"Server" , HandleServer},
	{"Align"  , HandleAlign},
	{"User"   , HandleUser},
	{"Pwd"    , HandlePwd},
	{"AccountID"  , HandleAcctID},
	{"ProxyMethod", HandleProxyMethod},
	{"Method"	  , HandleMethod},
	{"ProxyServer", HandleProxyServer},
	{"Language" , HandleLang},
	{NULL}
};


static char SyslistID[CFG_STRING_LEN] = "www.SysList.com";

static CmdMethodIndex SyslistConfigMethod = MethodDisable;
static char SyslistConfigServer[CFG_STRING_LEN] = "";
static char SyslistConfigUser[CFG_STRING_LEN] = "";
static char SyslistConfigPwd[CFG_STRING_LEN] = "";
static char SyslistConfigAcctID[CFG_STRING_LEN] = "";
static char SyslistConfigID[CFG_STRING_LEN] = "";
static char SyslistConfigAlign[CFG_STRING_LEN] = "";
static char SyslistConfigInstall[CFG_STRING_LEN] = "";
static ProxyMethodIndex SyslistConfigProxyMethod = ProxyMethodDirect;
static char SyslistConfigProxyServer[CFG_STRING_LEN] = "";
static long SyslistConfigLangID = -1; //US English...
static long SyslistConfigFailedContact = 0;

static char InstallShare[CFG_STRING_LEN] = "";
static bool RunSilent = false;
static bool IsInstall = false;
static 	HINSTANCE g_hInstance;

// Need soft linking of AdvApi Security functions to
// allow this to run on win98.
static   DWORD ( WINAPI *SetNamedSecurityInfoSoft)  (
  LPTSTR pObjectName,                // object name
  SE_OBJECT_TYPE ObjectType,         // object type
  SECURITY_INFORMATION SecurityInfo, // type
  PSID psidOwner,                    // new owner SID
  PSID psidGroup,                    // new primary group SID
  PACL pDacl,                        // new DACL
  PACL pSacl                         // new SACL
) = NULL;

///////////////////////////////////////////////////////////////////////////////////////////
extern 	BOOL IsRunningAsAdmin();

///////////////////////////////////////////////////////////////////////////////////////////

void SoftLoadNTAPI()
{
	HMODULE AdvApiModule = NULL;

	AdvApiModule = LoadLibrary("AdvApi32.dll");

	if (AdvApiModule != NULL) {
		FARPROC * FoundSymbol = (FARPROC *) &SetNamedSecurityInfoSoft;
		*FoundSymbol = GetProcAddress(AdvApiModule, "SetNamedSecurityInfoA");
	}
}
bool FrontIsSwitchCommand (CommandLineQueue & CmdQueue) 
{
	if (CmdQueue.empty())
		return false;
	
	switch (CmdQueue.front().c_str()[0]) {
	
	case '/':
	case '-':
		return true;

	default:
		break;
	}

	return false;
}

long ParseCommandLine (LPSTR OrigCommandLineStr, CommandLineItem * CmdItems)
{
	char CmdLine[CMD_STRING_SIZE];
	char * CurrItem;
	
	CommandLineQueue ItemQueue;

	// Copy the string so we don't destroy the original
	strncpy (CmdLine, OrigCommandLineStr, CMD_STRING_SIZE -1 );
	CmdLine[CMD_STRING_SIZE - 1] = '\0';

	string CurrArg;
	
	BOOL InQuote = FALSE;
	BOOL Escape = FALSE;

	// Push each item on to a stack
	for( CurrItem = CmdLine;
		 CurrItem != NULL && *CurrItem != '\0';
		 CurrItem ++)
	{
		if (InQuote) {

			if (*CurrItem == '\"') {
				InQuote = FALSE;
				ItemQueue.push(CurrArg);
				CurrArg.erase(CurrArg.begin(), CurrArg.end());
				
				continue;
			}
		}

		else { // Not in Quote yet

			if (isspace( *CurrItem)) {
				
				if (CurrArg.size() > 0) {
					ItemQueue.push(CurrArg);
					CurrArg.erase(CurrArg.begin(), CurrArg.end());

				}
				continue;
			}
			else if (*CurrItem == '\"') {
				
				if (CurrArg.size() == 0) {
					InQuote = TRUE;

					continue;
				}
			}
		}

		CurrArg += *CurrItem;
	}
	
	// Last Item
	if (CurrArg.size() > 0)
		ItemQueue.push(CurrArg);

	// Throw the corresponding functions into a map for
	// easy indexing;
	CommandLineItem * CurrCmdLineItem;
	
	while (!ItemQueue.empty()) {
		
		string SomeString = ItemQueue.front();

		if (FrontIsSwitchCommand(ItemQueue)) {
			bool FoundItem = false;
			for (CurrCmdLineItem = CmdItems; CurrCmdLineItem->Command != NULL; CurrCmdLineItem ++) {
				if (!stricmp(&(ItemQueue.front().c_str()[1]),  CurrCmdLineItem->Command)) {
					FoundItem = true;
					ItemQueue.pop();
					CurrCmdLineItem->Handler(ItemQueue);
					break;
				}
			}
			if (!FoundItem)
				ItemQueue.pop();
		}
		else {
			ItemQueue.pop();
		}
	}

	return ERROR_SUCCESS;
}

long RetrieveArg(CommandLineQueue &CmdQueue, string & ReturnArg)
{
	if (FrontIsSwitchCommand(CmdQueue))
		return ERROR_GEN_FAILURE;

	if (CmdQueue.empty() == true)
		return ERROR_GEN_FAILURE;

	ReturnArg = CmdQueue.front();
	CmdQueue.pop();

	return ERROR_SUCCESS;
}

long HandleSilent(CommandLineQueue &CmdQueue)
{
	RunSilent = true;
	return ERROR_SUCCESS;
}

long HandleInstall(CommandLineQueue &CmdQueue)
{
	IsInstall = true;
	return ERROR_SUCCESS;
}

long HandleMethod(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	SyslistConfigMethod = MethodFromString(ArgString.c_str());

	return ERROR_SUCCESS;
}

long HandleServer(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;
#if !defined(ASP_DEMO) && !defined(SYSLIST_ACC)
	strcpy(SyslistConfigServer, ArgString.c_str());
#endif
	return ERROR_SUCCESS;
}

long HandleProxyMethod(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	SyslistConfigProxyMethod = ProxyMethodFromString(ArgString.c_str());

	return ERROR_SUCCESS;
}

long HandleProxyServer(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	strcpy(SyslistConfigProxyServer, ArgString.c_str());

	return ERROR_SUCCESS;
}


long HandleUser(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	strcpy(SyslistConfigUser, ArgString.c_str());

	return ERROR_SUCCESS;
}

long HandlePwd(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	strcpy(SyslistConfigPwd, ArgString.c_str());

	return ERROR_SUCCESS;

}

long HandleAcctID(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	strcpy(SyslistConfigAcctID, ArgString.c_str());

	return ERROR_SUCCESS;

}

long HandleInstallShare(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	strcpy(InstallShare, ArgString.c_str());

	return ERROR_SUCCESS;
}

long HandleAlign(CommandLineQueue &CmdQueue)
{
	return ERROR_SUCCESS;
}

long HandleLang(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue,ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	const char * LangStr = NULL;

	LangStr = ArgString.c_str();

	if (isdigit(LangStr[0]))
		SyslistConfigLangID = atoi(LangStr);
	else {
		
		LangNameInfoMap::iterator LocateID;

		LocateID = g_SLangNameInfo.find(LangStr);

		if (LocateID != g_SLangNameInfo.end())
			SyslistConfigLangID = LocateID->second.LangID;
	}
	return ERROR_SUCCESS;
}

long HandleHelp(CommandLineQueue &CmdQueue)
{
	return ERROR_SUCCESS;
}

long HandleConfig(CommandLineQueue &CmdQueue)
{
	string ArgString;
	if (RetrieveArg(CmdQueue, ArgString) != ERROR_SUCCESS)
		return ERROR_GEN_FAILURE;

	char * confPath = (char *) ArgString.c_str();
	FileConfig config;

	if (ReadFileConf(confPath, &config))
		return ERROR_GEN_FAILURE;

	strncpy(SyslistConfigServer, config.ServerURL, CFG_STRING_LEN);
	if (strlen(config.ServerPort)) {
		strncat(SyslistConfigServer, ":", CFG_STRING_LEN);
		strncat(SyslistConfigServer, config.ServerPort, CFG_STRING_LEN);
	}

	strncpy(SyslistConfigUser, config.AcctName, CFG_STRING_LEN);
	strncpy(SyslistConfigPwd, config.AcctPwd, CFG_STRING_LEN);
	strncpy(SyslistConfigAcctID, config.AcctCode, CFG_STRING_LEN);

	CmdMethodIndex finalMethod = MethodDisable;
	if (!stricmp("startup", config.ScanFreq)) {
		finalMethod = MethodStartup;
	}
	else if (!stricmp("day", config.ScanFreq)) {
		finalMethod = MethodDay;
	}
	else if (!stricmp("week", config.ScanFreq)) {
		finalMethod = MethodWeek;
	}
	else if (!stricmp("month", config.ScanFreq)) {
		finalMethod = MethodMonth;
	}
	SyslistConfigMethod = finalMethod;

	ProxyMethodIndex finalProxyMethod = ProxyMethodDirect;
	if (!stricmp("system", config.ProxyMethod)) {
		finalProxyMethod = ProxyMethodIE;
	}
	else if (!stricmp("manual", config.ProxyMethod)) {
		finalProxyMethod = ProxyMethodManual;
	}
	SyslistConfigProxyMethod = finalProxyMethod;

	strncpy(SyslistConfigProxyServer, config.ProxyServer, CFG_STRING_LEN);
	if (strlen(config.ProxyPort)) {
		strncat(SyslistConfigProxyServer, ":", CFG_STRING_LEN);
		strncat(SyslistConfigProxyServer, config.ProxyPort, CFG_STRING_LEN);
	}

	return ERROR_SUCCESS;
}

long LocaleMsgBox(HWND Parent, long RsrcMessage, long RsrcCaption, UINT Type, long LocNum = 0, long ErrNum = ERROR_SUCCESS, long DefaultReturn = MB_OK)
{
	TCHAR NoConfigStr[RES_STRING_SIZE];
	TCHAR FmtNoConfigStr[RES_STRING_SIZE * 2];
	TCHAR NoConfigCaption[RES_STRING_SIZE];

	LoadString (g_hLangInstance, RsrcMessage, NoConfigStr, RES_STRING_SIZE);
	LoadString (g_hLangInstance, RsrcCaption, NoConfigCaption, RES_STRING_SIZE);

	if (ErrNum != ERROR_SUCCESS || LocNum != 0) {
		wsprintf(FmtNoConfigStr, "%s (E=%d-0x%08x)", NoConfigStr, LocNum, ErrNum);
	}
	else {
		strcpy (FmtNoConfigStr, NoConfigStr);
	}

	
	if (RunSilent) {

		if (InstallShare[0] != '\0') {
			::ofstream RemoteErrorLog;
			RemoteErrorLog.open(InstallShare, ios_base::out);
			
			RemoteErrorLog << FmtNoConfigStr;

			RemoteErrorLog.close();

			if (SetNamedSecurityInfoSoft != NULL)
				SetNamedSecurityInfoSoft (InstallShare, SE_FILE_OBJECT, DACL_SECURITY_INFORMATION, NULL, NULL, NULL, NULL);
		}

		return DefaultReturn;
	}

	return MessageBox (Parent, FmtNoConfigStr, NoConfigCaption, Type);
}

long ExecuteCollector(BOOL ManualReport)
{
	BOOL CreateState;
	STARTUPINFO CollectStartup;
	PROCESS_INFORMATION CollectInfo;

	ZeroMemory(&CollectStartup, sizeof (STARTUPINFO));
	CollectStartup.cb = sizeof (STARTUPINFO);
	
	string CollectorPath = SyslistConfigInstall;
	CollectorPath += "\\";
	CollectorPath += COLLECTOR_EXEC;

	CreateState = CreateProcess(
			CollectorPath.c_str(), // App Name/path
			(char *) COLLECTOR_EXEC, // Command Line
			NULL, // Proc Security
			NULL, // Thread Security
			FALSE,  // Inherit Handles
			CREATE_NO_WINDOW | NORMAL_PRIORITY_CLASS, // Flags
			NULL, // Environ
			NULL, // Current Directory
			& CollectStartup, // Startup Info
			& CollectInfo); // Return Proc Info.

	if (CreateState == FALSE) {
		LocaleMsgBox(NULL, IDS_ERR_COLLECT_NOT_RUN, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
		return ERROR_GEN_FAILURE;
	}

	DWORD WaitReturn;

	WaitReturn = WaitForSingleObject( CollectInfo.hProcess, COLLECTOR_WAIT);

	if (WaitReturn != WAIT_OBJECT_0) {
		switch (WaitReturn) {
		case WAIT_TIMEOUT: 
			LocaleMsgBox(NULL, IDS_ERR_COLLECT_HANG, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
			break;
		default:
			LocaleMsgBox(NULL, IDS_ERR_COLLECT_NOT_RUN, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
			break;
		}

		return ERROR_GEN_FAILURE;
	}
	
	long ReturnValue;
	GetExitCodeProcess (CollectInfo.hProcess, (PDWORD) &ReturnValue);
	
	if (ReturnValue != 0) {
		long ErrMsgID;

		switch (ReturnValue) {
		case -8:
			ErrMsgID = IDS_ERR_COLLECT_NO_ID;
			break;
		case -7:
			ErrMsgID = IDS_ERR_COLLECT_DEMO_EXPIRED;
			break;
		case -6:
			ErrMsgID = IDS_ERR_COLLECT_OPEN_URI;
			break;
		case -5:
			ErrMsgID = IDS_ERR_COLLECT_READ_REG;
			break;
		case -4:
			ErrMsgID = IDS_ERR_COLLECT_FAIL;
			break;
		case -3:
			ErrMsgID = IDS_ERR_COLLECT_PROTO;
			break; 
		case -2:
			ErrMsgID = IDS_ERR_COLLECT_WRITE_ID;
			break;
		case 1:
			ErrMsgID = IDS_ERR_SYSLIST_REJECT;
			break;
		case 2:
			ErrMsgID = IDS_ERR_SYSLIST_AUTH;
			break;
		case 3:
			ErrMsgID = IDS_ERR_SYSLIST_INCOMPLETE_XML;
			break;
		case 4:
			ErrMsgID = IDS_ERR_SYSLIST_NO_INFO;
			break;
		case 9:
			ErrMsgID = IDS_ERR_SYSLIST_LIC_INVALID;
			break;
		case 10:
			ErrMsgID = IDS_ERR_SYSLIST_LIC_MAXED;
			break;
		case 404:
			ErrMsgID = IDS_ERR_SYSLIST_NO_AGENT_PAGE;
			break;
		default:
			ErrMsgID = IDS_ERR_SYSLIST_COMM;
			break;
		}

		LocaleMsgBox(NULL, ErrMsgID, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
		return ReturnValue;
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

long InitDlg (HWND Dlg)
{
	static bool FirstInit = false;

	SetDlgItemText(Dlg, IDC_SERVER_EDIT, SyslistConfigServer);

#if defined(ASP_DEMO) || defined(SYSLIST_ACC)
	EnableWindow (GetDlgItem(Dlg, IDC_SERVER_STATIC), FALSE);
	EnableWindow (GetDlgItem(Dlg, IDC_SERVER_EDIT), FALSE);
#endif

#ifndef SYSLIST_ACC
	EnableWindow (GetDlgItem(Dlg, IDC_ACCTID_STATIC), FALSE);
	EnableWindow (GetDlgItem(Dlg, IDC_ACCTID_EDIT), FALSE);
#endif

	//EnableWindow (GetDlgItem(Dlg, IDC_ACCTID_STATIC), FALSE);

	HWND FreqComboID;
	FreqComboID = GetDlgItem(Dlg, IDC_FREQ_COMBO);
	SendMessage(FreqComboID, CB_RESETCONTENT, NULL, NULL);
	
	HWND ProxyMethodID;
	ProxyMethodID = GetDlgItem(Dlg, IDC_PROXY_COMBO);
	SendMessage(ProxyMethodID, CB_RESETCONTENT, NULL, NULL);

	InitDlgLangStrings (Dlg, g_ConfigLangItems, g_hLangInstance);

	if (FirstInit == false) {

		InitDlgLangCombo(Dlg, IDC_LANG_COMBO, SyslistConfigLangID);

		FirstInit = true;
	}

	char ConfigString[RES_STRING_SIZE];
	long ConfigStringID;

	if (SyslistConfigID[0] == '\0')
		ConfigStringID = IDS_CFG_UNKNOWN;
	else
		ConfigStringID = IDS_CFG_KNOWN;


	// Here we use the Config String to hold the version
	// information temporarily. It gets wiped out in the
	// next set of setup steps.
	sprintf (ConfigString, "v%s", SyslistVersionString);

	SetDlgItemText(Dlg, IDC_VERSION_STATIC, ConfigString);

	LoadString(g_hLangInstance, ConfigStringID, ConfigString, RES_STRING_SIZE);
	SetDlgItemText(Dlg, IDC_IDVAL_STATIC, ConfigString);

	SetDlgItemText(Dlg, IDC_USER_EDIT, SyslistConfigUser);
	SetDlgItemText(Dlg, IDC_PWD_EDIT, SyslistConfigPwd);
	SetDlgItemText(Dlg, IDC_ACCTID_EDIT, SyslistConfigAcctID);

	SendMessage (FreqComboID, CB_SETITEMDATA, 0, MethodDisable);
	SendMessage (FreqComboID, CB_SETITEMDATA, 1, MethodStartup);
	SendMessage (FreqComboID, CB_SETITEMDATA, 2, MethodDay);
	SendMessage (FreqComboID, CB_SETITEMDATA, 3, MethodWeek);
	SendMessage (FreqComboID, CB_SETITEMDATA, 4, MethodMonth);

	long ComboIndex;
	long ComboCount = SendMessage(FreqComboID, CB_GETCOUNT, NULL, NULL);
	
	for (ComboIndex = 0; ComboIndex < ComboCount; ComboIndex ++) {
		CmdMethodIndex CurrIndex;

		CurrIndex = (CmdMethodIndex) SendMessage (FreqComboID, CB_GETITEMDATA, ComboIndex, NULL);

		if (CurrIndex == SyslistConfigMethod) {
			SendMessage (FreqComboID, CB_SETCURSEL, CurrIndex, NULL);
			break;
		}
	}

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

	SetDlgItemText(Dlg, IDC_PROXY_SERVER_EDIT, ProxyServerText);
	SetDlgItemText(Dlg, IDC_PROXY_PORT_EDIT, ProxyPortText);

	SetProxyEnableState(Dlg);

	HICON IconHandle;

	IconHandle = LoadIcon(g_hInstance , MAKEINTRESOURCE(IDI_CONFIG16));
	SendMessage (Dlg, WM_SETICON, (WPARAM) ICON_SMALL, (LPARAM) IconHandle);

	IconHandle = LoadIcon(g_hInstance, MAKEINTRESOURCE(IDI_CONFIG32));
	SendMessage (Dlg, WM_SETICON, (WPARAM) ICON_BIG, (LPARAM) IconHandle);

	if (IsInstall) {
		ShowWindow(GetDlgItem(Dlg, IDC_INVENTORY), SW_HIDE);
	}

	return ERROR_SUCCESS;
}

long ProcessDlg (HWND Dlg)
{
	long CurrSel;

	HWND CtrlID;
		
	CtrlID= GetDlgItem(Dlg, IDC_LANG_COMBO);
	
	CurrSel = SendMessage(CtrlID, CB_GETCURSEL, NULL, NULL);
	SyslistConfigLangID = SendMessage (CtrlID, CB_GETITEMDATA, CurrSel, NULL);

	CtrlID = GetDlgItem(Dlg, IDC_FREQ_COMBO);
	CurrSel = SendMessage (CtrlID, CB_GETCURSEL, NULL, NULL);
	SyslistConfigMethod = (CmdMethodIndex) SendMessage (CtrlID, CB_GETITEMDATA, CurrSel, NULL);

	CtrlID = GetDlgItem(Dlg, IDC_PROXY_COMBO);
	CurrSel = SendMessage (CtrlID, CB_GETCURSEL, NULL, NULL);
	SyslistConfigProxyMethod = (ProxyMethodIndex) SendMessage (CtrlID, CB_GETITEMDATA, CurrSel, NULL);

	char DlgItemText[CFG_STRING_LEN];
	DlgItemText[0] = '\0';

	GetDlgItemText(Dlg, IDC_PROXY_SERVER_EDIT, SyslistConfigProxyServer, CFG_STRING_LEN);
	GetDlgItemText(Dlg, IDC_PROXY_PORT_EDIT, DlgItemText, CFG_STRING_LEN);

	if (DlgItemText[0] != '\0') {
		strcat (SyslistConfigProxyServer, ":");
		strcat (SyslistConfigProxyServer, DlgItemText);
	}

	CtrlID = GetDlgItem(Dlg, IDC_SERVER_EDIT);
	SendMessage (CtrlID, WM_GETTEXT, CFG_STRING_LEN, (LPARAM) SyslistConfigServer);

	CtrlID = GetDlgItem(Dlg, IDC_USER_EDIT);
	SendMessage (CtrlID, WM_GETTEXT, CFG_STRING_LEN, (LPARAM) SyslistConfigUser);

	CtrlID = GetDlgItem(Dlg, IDC_PWD_EDIT);
	SendMessage (CtrlID, WM_GETTEXT, CFG_STRING_LEN, (LPARAM) SyslistConfigPwd);

	CtrlID = GetDlgItem(Dlg, IDC_ACCTID_EDIT);
	SendMessage (CtrlID, WM_GETTEXT, CFG_STRING_LEN, (LPARAM) SyslistConfigAcctID);

	BOOL FormIncomplete = FALSE;

	if (SyslistConfigServer[0] == '\0'
		|| SyslistConfigUser[0] == '\0'
		|| SyslistConfigPwd[0] == '\0') {
	
		FormIncomplete = TRUE;
	}

#ifdef SYSLIST_ACC
	if (SyslistConfigAcctID[0] == '\0')
		FormIncomplete = TRUE;
#endif

	if (FormIncomplete == TRUE) {
		LocaleMsgBox (Dlg, IDS_PLEASE_FILL_FORM, IDS_PLEASE_FILL_FORM_CAP, MB_ICONEXCLAMATION);
		return E_FAIL;
	}


	return ERROR_SUCCESS;
}

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

#if defined(ASP_DEMO) || defined (SYSLIST_ACC)
	strcpy(SyslistConfigServer, SYSLIST_DEFAULT_SERVER);
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

	// These are encrypted via Windows Encryption...
	SimpleStringPWCrypt RegDecrypt(SyslistPhrase);

	Status = RegQueryValueEx(SyslistRegKey, REG_USER_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigUser, ReturnRegString, ReturnSize, ReturnType);
	}
	else 
		SyslistConfigUser[0] = '\0';

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_PWD_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigPwd, ReturnRegString, ReturnSize, ReturnType);
	}
	else 
		SyslistConfigPwd[0] = '\0';

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_ID_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigID, ReturnRegString, ReturnSize, ReturnType);
	}
	else 
		SyslistConfigID[0] = '\0';

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_ACCTID_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString, &ReturnSize);
	if (Status == ERROR_SUCCESS) {
		Status = RegDecrypt.DecodePossibleRegCrypt (SyslistConfigAcctID, ReturnRegString, ReturnSize, ReturnType);
	}
	else 
		SyslistConfigID[0] = '\0';

	ReturnSize = CFG_STRING_LEN;
	ReturnRegString[0] = '\0';
	Status = RegQueryValueEx(SyslistRegKey, REG_FAILED_CONTACT_VALUE, NULL, &ReturnType, (LPBYTE) ReturnRegString , &ReturnSize);
	if (Status == ERROR_SUCCESS && strlen(ReturnRegString) > 0)
		SyslistConfigFailedContact = atoi(ReturnRegString);
	else
		SyslistConfigFailedContact = 0;

	RegCloseKey(SyslistRegKey);

	return ERROR_SUCCESS;

}

long WriteRegInfo()
{
	HKEY SyslistRegKey;
	long Status;

	Status = RegOpenKeyEx(HKEY_LOCAL_MACHINE, SYSLIST_REG_LOC, NULL, KEY_WRITE, & SyslistRegKey);
	if (Status != ERROR_SUCCESS)
		return Status;

	char NumBuf[12];
	_itoa(SyslistConfigLangID, NumBuf, 10);
	Status = RegSetValueEx (SyslistRegKey, REG_LANG_VALUE, NULL, REG_SZ, (BYTE *) NumBuf, strlen (NumBuf) + 1);

	Status = RegSetValueEx (SyslistRegKey, REG_FAILED_CONTACT_VALUE, NULL, REG_SZ, (BYTE *) "0", 2);

	const char * MethodString;
	MethodString = StringFromMethod (SyslistConfigMethod);
	Status = RegSetValueEx (SyslistRegKey, REG_METHOD_VALUE, NULL, REG_SZ, (BYTE *) MethodString, strlen(MethodString));

	MethodString = StringFromProxyMethod (SyslistConfigProxyMethod);
	Status = RegSetValueEx (SyslistRegKey, REG_PROXY_METHOD_VALUE, NULL, REG_SZ, (BYTE *) MethodString, strlen(MethodString));

	Status = RegSetValueEx (SyslistRegKey, REG_PROXY_SERVER_VALUE, NULL, REG_SZ, (BYTE *) SyslistConfigProxyServer, strlen(SyslistConfigProxyServer));
	
	Status = RegSetValueEx (SyslistRegKey, REG_SERVER_VALUE, NULL, REG_SZ, (BYTE *) SyslistConfigServer, strlen(SyslistConfigServer));

	// These next values are to be encrypted via Windows encryption
	SimpleStringPWCrypt RegEncrypt(SyslistPhrase);
	char CryptWorkBuf[CFG_STRING_LEN];
	DWORD RegType;
	unsigned long RegLen;
	
	Status = RegEncrypt.EncodePossibleRegCrypt(CryptWorkBuf, SyslistConfigUser, &RegLen, CFG_STRING_LEN, &RegType);
	Status = RegSetValueEx (SyslistRegKey, REG_USER_VALUE, NULL, RegType, (BYTE *) CryptWorkBuf, RegLen);

	Status = RegEncrypt.EncodePossibleRegCrypt(CryptWorkBuf, SyslistConfigPwd, &RegLen, CFG_STRING_LEN, &RegType);
	Status = RegSetValueEx (SyslistRegKey, REG_PWD_VALUE, NULL, RegType, (BYTE *) CryptWorkBuf, RegLen);

	Status = RegEncrypt.EncodePossibleRegCrypt(CryptWorkBuf, SyslistConfigAcctID, &RegLen, CFG_STRING_LEN, &RegType);
	Status = RegSetValueEx (SyslistRegKey, REG_ACCTID_VALUE, NULL, RegType, (BYTE *) CryptWorkBuf, RegLen);

	RegCloseKey(SyslistRegKey);

	return ERROR_SUCCESS;
}


long URLEncode (const char * Source, char * Dest)
{
 
	long CurrLoc;
	char * TransLoc; 


	for (CurrLoc = 0, TransLoc = Dest;
		 Source[CurrLoc] != L'\0'; 
		 CurrLoc ++) {

		switch (Source[CurrLoc]) {
		case L' ':
			*(TransLoc++) = L'+';
			break;
		case L'&':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'2';
			*(TransLoc++) = L'6';
			break;
		case '\t':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'0';
			*(TransLoc++) = L'9';
			break;
		case '\n':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'0';
			*(TransLoc++) = L'A';
			break;
		case '/':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'2';
			*(TransLoc++) = L'F';
			break;
		case '~':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'7';
			*(TransLoc++) = L'E';
			break;
		case ':':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'3';
			*(TransLoc++) = L'A';
			break;
		case ';':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'3';
			*(TransLoc++) = L'B';
			break;
		case '@':
			*(TransLoc++) = L'%';
			*(TransLoc++) = L'4';
			*(TransLoc++) = L'0';
			break;
		default:
			*(TransLoc++) = Source[CurrLoc];
			break;
		 }
	}

	*TransLoc = L'\0';

	return ERROR_SUCCESS;
}

long ShowBrowser()
{
	CComPtr<IWebBrowser2> Browser;
	HRESULT hr;
	
	CComBSTR ReportURL = SyslistConfigServer;

	if (ReportURL.m_str[ReportURL.Length() - 1] != L'/')
		ReportURL += L"/";

	ReportURL += INSTALL_POST_SUBURL;

	hr = Browser.CoCreateInstance(__uuidof(InternetExplorer));
	if (FAILED(hr))
		return ERROR_GEN_FAILURE;

	CComVariant NavFlags;
	//navOpenInNewWindow |
	NavFlags = (long) (  navNoHistory | navNoReadFromCache | navNoWriteToCache );

	char URLSyslistConfigID[2*RES_STRING_SIZE];
	char URLSyslistConfigUser[2*RES_STRING_SIZE];
	char URLSyslistConfigPwd[2*RES_STRING_SIZE];
	char URLSyslistConfigAcctID[2*RES_STRING_SIZE];

	URLEncode (SyslistConfigID, URLSyslistConfigID);
	URLEncode (SyslistConfigUser, URLSyslistConfigUser);
	URLEncode (SyslistConfigPwd, URLSyslistConfigPwd);
	URLEncode (SyslistConfigAcctID, URLSyslistConfigAcctID);

	char PostString[8192];

	sprintf (PostString, "hardwareID=%s&txtUserName=%s&txtPassword=%s&txtAcctCode=%s&btnSubmit=1&strRedir=showfull.php\r\n",
			 URLSyslistConfigID, URLSyslistConfigUser, URLSyslistConfigPwd, URLSyslistConfigAcctID);

	SAFEARRAY * saPostData = NULL;
	SAFEARRAYBOUND saboundsPostData = {strlen(PostString), 0};
	
	saPostData = SafeArrayCreate(VT_UI1, 1, &saboundsPostData);

	unsigned char * PostDataAccess = NULL;

	hr = SafeArrayAccessData (saPostData, (void **) &PostDataAccess);
	if (FAILED(hr))
		return ERROR_GEN_FAILURE;

	memcpy(PostDataAccess, PostString, strlen(PostString));

	hr = SafeArrayUnaccessData(saPostData);
	if (FAILED(hr))
		return ERROR_GEN_FAILURE;

	CComVariant PostData;
	PostData.vt = VT_ARRAY | VT_UI1;
	PostData.parray = saPostData;

	CComVariant TargetFrame = SysAllocString(L"");
	CComVariant Headers = SysAllocString(L"Content-Type: application/x-www-form-urlencoded\r\n");
	
	hr = Browser->Navigate(ReportURL, &NavFlags, &TargetFrame, &PostData, &Headers);
	
	if (FAILED(hr))
		return ERROR_GEN_FAILURE;

	hr = Browser->put_Visible(VARIANT_TRUE);
	if (FAILED(hr))
		return ERROR_GEN_FAILURE;


	return ERROR_SUCCESS;
}

BOOL CALLBACK CfgDialogProc(
  HWND hwndDlg,  // handle to dialog box
  UINT uMsg,     // message
  WPARAM wParam, // first message parameter
  LPARAM lParam) // second message parameter
{
	bool processed = false;
	long FormStatus = ERROR_SUCCESS;

	switch (uMsg) {

	case WM_INITDIALOG:
		processed = true;
		InitDlg (hwndDlg);

		break;

	case WM_COMMAND:
		processed = true;

		switch (LOWORD ( wParam ) ) {

		case IDC_CFG_OK: {
			long CollectStatus = ERROR_SUCCESS;

			FormStatus = ProcessDlg (hwndDlg);
			
			if (FormStatus == ERROR_SUCCESS) {
				if (IsInstall) {
					WriteRegInfo();
					
					CollectStatus = ExecuteCollector(FALSE);

					ReadRegInfo();
					InitDlg(hwndDlg);
				}
				
				if (IsInstall == FALSE || CollectStatus == ERROR_SUCCESS)
					EndDialog(hwndDlg, CFG_OK);
				}
			}
			break;

		case IDC_CFG_CANCEL:
			EndDialog(hwndDlg, CFG_CANCELLED);
			break;

		case IDC_LANG_COMBO:
			if (HIWORD (wParam) == CBN_SELCHANGE) {
				char NewLangName[RES_STRING_SIZE];
				long CurrSel  =	SendMessage((HWND) lParam, CB_GETCURSEL, NULL, NULL);
				SendMessage((HWND) lParam, WM_GETTEXT, RES_STRING_SIZE, (LPARAM) NewLangName);
				SetLangInstance (NewLangName, g_LLangNameInfo, SyslistConfigLangID);
				InitDlg(hwndDlg);
			}
			break;

		case IDC_PROXY_COMBO:
			if (HIWORD (wParam) == CBN_SELCHANGE) {
				SetProxyEnableState(hwndDlg);
			}
			break;

		case IDC_INVENTORY: 
			FormStatus = ProcessDlg(hwndDlg);

			if (FormStatus == ERROR_SUCCESS) {
				WriteRegInfo();
				if (ExecuteCollector(TRUE) == ERROR_SUCCESS) 
				{
					ReadRegInfo();
					InitDlg(hwndDlg);

					LocaleMsgBox (NULL, IDS_CFG_IMMED_INV_SUCCESS, IDS_CFG_GEN_CAP, MB_ICONASTERISK);
					//if (ShowBrowser() != ERROR_SUCCESS) {
					//	LocaleMsgBox(NULL, IDS_ERR_NO_SHOW_BROWSER, IDS_ERR_NO_SHOW_BROWSER_CAP, MB_ICONEXCLAMATION);
					//}
				}
				ReadRegInfo();
				InitDlg(hwndDlg);
			}
			break;

		default:
			break;
		}

		break;

	case WM_CLOSE:
		processed = true;
		EndDialog(hwndDlg, CFG_CANCELLED);
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

	if (IsRunningAsAdmin() == FALSE) {
		MessageBox( NULL, "This program must be run by an Administrator", "Admin Rights Required", MB_ICONEXCLAMATION);
		return -999;
	}

	SoftLoadNTAPI();

	g_hInstance = hInstance;
	CoInitialize(NULL);

	ParseCommandLine (lpCmdLine, g_CtrlCmdItems);

	Status = ReadInstallDir();
	if (Status != ERROR_SUCCESS) {
		LocaleMsgBox (NULL, IDS_ERR_CFG_REG, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
		return -8;
	}

	Status = ReadRegInfo();
	if (Status != ERROR_SUCCESS) {
		LocaleMsgBox (NULL, IDS_ERR_CFG_REG, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
		return -8;
	}

	ParseCommandLine (lpCmdLine, g_ConfigCmdItem);

	ParseCommandLine (lpCmdLine, g_RegistryCmdItems);

	int Error;

	InitLangDLLInfo(g_hInstance, IDS_CFG_SLANG, IDS_CFG_LLANG, IDS_CFG_ILANG, LANG_DLL_SPEC);
	SetDefaultLangInstance(SyslistConfigLangID);
	
	if (!RunSilent) {
		Status = DialogBox(hInstance, MAKEINTRESOURCE(IDD_CONFIGDLG), NULL, &CfgDialogProc);
		if (Status < 0 ) {
			Error = GetLastError();
			return -9;
		}

		if (Status == CFG_CANCELLED) {
			LocaleMsgBox (NULL, IDS_CFG_CANCELLED_STR, IDS_CFG_CANCELLED_CAPTION, MB_ICONASTERISK);
			return -10;
		}
		else if (IsInstall) {
			LocaleMsgBox (NULL, IDS_CFG_INSTALL_SUCCESS, IDS_CFG_INSTALL_SUCCESS_CAP, MB_ICONASTERISK);
			Status = ShowBrowser();
			if (Status != ERROR_SUCCESS) {
				LocaleMsgBox(NULL, IDS_ERR_NO_SHOW_BROWSER, IDS_ERR_NO_SHOW_BROWSER_CAP, MB_ICONEXCLAMATION);
				return -11;
			}
		}
	}

	Status = WriteRegInfo();
	if (Status != ERROR_SUCCESS) {
		LocaleMsgBox (NULL, IDS_ERR_CFG_REG_WRITE, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION);
		return 4;
	}

	if (RunSilent && IsInstall) {
		Status = ExecuteCollector(FALSE);
		if (Status != ERROR_SUCCESS)
			return Status;
	}

#if 0
	/////////////////////////////////////////////////////////////////////
	// NO LONGER NEEDED
	// We now install a local service that
	// watches the registry keys periodically
	// and runs the collector.
	Status = StartLocalTaskSched();
	if (Status != ERROR_SUCCESS) {
		LocaleMsgBox (NULL, IDS_ERR_CFG_SCHED_START, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION, SchedError, Status);
		return -6;
	}

	Status = ConfigCollectorTask(SyslistConfigMethod, (char *) SYSLIST_ACCT_NAME, (char *) SYSLIST_ACCT_PWD, SyslistConfigInstall);
	if (Status != ERROR_SUCCESS) {
		LocaleMsgBox (NULL, IDS_ERR_CFG_SCHED_TASK, IDS_ERROR_GEN_CAP, MB_ICONEXCLAMATION, SchedError, Status);
		return -7;
	}
	//
	/////////////////////////////////////////////////////////////////////
#endif
	CoUninitialize();

	return 0;
}



