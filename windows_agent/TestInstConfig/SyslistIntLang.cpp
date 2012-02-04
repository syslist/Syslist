 
#include "stdafx.h"
#include "SyslistIntLang.h"

LangIDInfoMap g_LangIDInfo;
LangNameInfoMap g_SLangNameInfo;
LangNameInfoMap g_LLangNameInfo;

HINSTANCE g_hLangInstance;

static HINSTANCE g_hLocalInstance;

static const char * LANG_DIR = "LangDLL";

long SetLangInstance(char * LangName, LangNameInfoMap & SearchMap, long & DestID)
{
	LangNameInfoMap::iterator LangMapIT;

	LangMapIT = SearchMap.find(LangName);

	if (LangMapIT == SearchMap.end())
		g_hLangInstance = g_hLocalInstance;
	else {
		g_hLangInstance = LangMapIT->second.Instance;
		DestID = LangMapIT->second.LangID;
	}

	return ERROR_SUCCESS;
}

long SetDefaultLangInstance(long & DestID)
{
	USES_CONVERSION;

	TCHAR LocaleLang[LC_LANG_LEN];
	LCID RQLocale;
	
	if (DestID > 0)
		RQLocale = (LCID) DestID ;
	else
		RQLocale = LOCALE_USER_DEFAULT;

	GetLocaleInfo(RQLocale, LOCALE_SABBREVLANGNAME, LocaleLang, LC_LANG_LEN);

	return SetLangInstance(T2A(LocaleLang), g_SLangNameInfo, DestID);
}

long InitDlgLangCombo(HWND Dlg, int ComboID, long CurrLangID)
{
	LangNameInfoMap::iterator LangMapIT;

	HWND LangComboID;
	LangComboID = GetDlgItem(Dlg, ComboID);
	long CurrItem;

	SendMessage(LangComboID, CB_RESETCONTENT, NULL, NULL);	

	for (LangMapIT = g_LLangNameInfo.begin(), CurrItem = 0; LangMapIT != g_LLangNameInfo.end(); LangMapIT ++, CurrItem ++) {
		SendMessage(LangComboID, CB_ADDSTRING, NULL, (LPARAM) LangMapIT->first.c_str());
		SendMessage(LangComboID, CB_SETITEMDATA, CurrItem, LangMapIT->second.LangID);

		if (LangMapIT->second.LangID == CurrLangID)
			SendMessage (LangComboID, CB_SETCURSEL, CurrItem, NULL);

	}

	return ERROR_SUCCESS;
}

long InitDlgLangStrings (HWND Dlg, LangItem * DlgStringTable, HINSTANCE StringSource)
{

	HWND CurrHandle= (HWND) -1;
	long LastID = -1;
	LangItem * CurrItem = NULL;
	TCHAR CurrString[RES_STRING_SIZE];
	
	long StringSize;

	for (CurrItem = DlgStringTable; CurrItem->CtrlID != LANG_END; CurrItem ++) {

		if (CurrItem->CtrlID != LastID) {
			CurrHandle = GetDlgItem (Dlg, CurrItem->CtrlID);
			LastID = CurrItem->CtrlID;
		}
	
		StringSize = LoadString (StringSource, CurrItem->MsgRsrcID, CurrString, RES_STRING_SIZE);
		if (StringSize == 0)
			LoadString (NULL, CurrItem->MsgRsrcID, CurrString, RES_STRING_SIZE);

		SendMessage (CurrHandle, CurrItem->WinMsgID, NULL, (LPARAM) CurrString);
	}

	return ERROR_SUCCESS;
}


long AddInstanceToLangMaps(HINSTANCE CurrInstance, int SResID, int LResID, int IResID)
{
	char SLangName[RES_STRING_SIZE] = "";
	char LLangName[RES_STRING_SIZE] = "";

	bool FoundShortID;
	bool FoundLongID;
	bool FoundLangID = false;

	long FoundStringLength;

	char LangIDString[RES_STRING_SIZE] = "";
	long LangID;

	FoundShortID = false;
	FoundLongID = false;

	LangInfo CurrLangInfo;

	CurrLangInfo.Instance = CurrInstance;

	FoundStringLength = LoadString(CurrInstance, LResID, LLangName, RES_STRING_SIZE);
	if (FoundStringLength > 0) {
		CurrLangInfo.LongName = LLangName;
		FoundLongID = true;
	}

	FoundStringLength = LoadString(CurrInstance, SResID, SLangName, RES_STRING_SIZE);
	if (FoundStringLength > 0) {
		CurrLangInfo.ShortName = SLangName;
		FoundShortID = true;
	}

	FoundStringLength = LoadString(CurrInstance, IResID, LangIDString, RES_STRING_SIZE);
	if (FoundStringLength > 0) {
		LangID = atoi(LangIDString);
		CurrLangInfo.LangID = LangID;
		FoundLangID = true;
	}

	if (FoundLangID)
		g_LangIDInfo[LangID] = CurrLangInfo;

	if (FoundShortID)
		g_SLangNameInfo[SLangName] = CurrLangInfo;

	if (FoundLongID)
		g_LLangNameInfo[LLangName] = CurrLangInfo;

	return ERROR_SUCCESS;
}

long InitLangDLLInfo(HINSTANCE LocalInstance, int SResID, int LResID, int IResID, const char * FileSpec)
{
	// Record Local/Default ID
	g_hLocalInstance = LocalInstance;

	// First add this executable to the Instance List
	// it won't appear in the language dll list
	AddInstanceToLangMaps(LocalInstance, SResID, LResID, IResID);

	WIN32_FIND_DATA FoundFile;
	HANDLE FindHandle = INVALID_HANDLE_VALUE;
	BOOL FindReturn = TRUE;
	HINSTANCE CurrInstance;

	char ExecJustDir[MAX_PATH];
	char FindSpec[MAX_PATH];
	char CurrFilePath[MAX_PATH];

	char ExecDrive[MAX_PATH];
	char ExecDir[MAX_PATH];
	char WholeExecPath[MAX_PATH];

	// here we make a find file spec representing all
	// language dlls. These should be located in a 
	// folder under the executables directory.
	GetModuleFileName(LocalInstance, WholeExecPath, MAX_PATH);
	_splitpath(WholeExecPath, ExecDrive, ExecDir, NULL, NULL);

	_makepath(ExecJustDir, ExecDrive, ExecDir, LANG_DIR, NULL);

	_makepath(FindSpec, NULL, ExecJustDir, FileSpec, NULL);
	
	for (
		FindHandle = FindFirstFile(FindSpec, &FoundFile);
		FindReturn != FALSE && FindHandle != INVALID_HANDLE_VALUE;
		FindReturn = FindNextFile(FindHandle, & FoundFile)
	)
	{
		_makepath (CurrFilePath, NULL, LANG_DIR, FoundFile.cFileName, NULL);

		CurrInstance = LoadLibrary(CurrFilePath);
		if (CurrInstance == NULL)
			continue;

		AddInstanceToLangMaps(CurrInstance, SResID, LResID, IResID);
	}

	return ERROR_SUCCESS;
}